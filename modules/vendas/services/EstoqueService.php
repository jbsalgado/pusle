<?php

namespace app\modules\vendas\services;

use Yii;
use app\modules\vendas\models\Produto;
use yii\base\Component;
use yii\base\UserException;

/**
 * EstoqueInsuficienteException - Exceção lançada quando não há saldo suficiente
 */
class EstoqueInsuficienteException extends UserException
{
}

/**
 * EstoqueService - Serviço de Gestão e Movimentação Atômica de Estoque
 * 
 * Garante que baixas e entradas de estoque sejam 100% atômicas no banco de dados (PostgreSQL),
 * prevenindo race conditions, lost updates e overselling em vendas simultâneas (PDV + Marketplaces).
 */
class EstoqueService extends Component
{
    /**
     * Realiza a baixa atômica de estoque de um produto no banco de dados.
     * 
     * @param string $produtoId UUID do produto
     * @param float $quantidade Quantidade a ser deduzida (> 0)
     * @param string|null $motivo Motivo ou referência da movimentação (ex: "Venda #1234", "Pedido Mercado Livre #MLB999")
     * @param bool $ignorarLimite Se true, permite saldo negativo independentemente da flag do produto
     * @return float Novo saldo em estoque
     * @throws EstoqueInsuficienteException Se não houver saldo suficiente
     * @throws \Exception Se o produto não for encontrado
     */
    public static function baixarEstoque(string $produtoId, float $quantidade, ?string $motivo = null, bool $ignorarLimite = false): float
    {
        if ($quantidade <= 0) {
            throw new \InvalidArgumentException("Quantidade para baixa de estoque deve ser maior que zero.");
        }

        $db = Yii::$app->db;

        // 1. Obter informações de permissão de estoque negativo
        $produto = Produto::findOne($produtoId);
        if (!$produto) {
            throw new \Exception("Produto ID '{$produtoId}' não encontrado para baixa de estoque.");
        }

        $permiteNegativo = $ignorarLimite || (bool)$produto->permite_estoque_negativo;

        // 2. Executar UPDATE atômico com validação no WHERE
        if ($permiteNegativo) {
            $sql = "
                UPDATE prest_produtos
                SET estoque_atual = estoque_atual - :qtd,
                    data_atualizacao = NOW()
                WHERE id = :id
                RETURNING estoque_atual;
            ";
        } else {
            $sql = "
                UPDATE prest_produtos
                SET estoque_atual = estoque_atual - :qtd,
                    data_atualizacao = NOW()
                WHERE id = :id AND estoque_atual >= :qtd
                RETURNING estoque_atual;
            ";
        }

        $novoEstoque = $db->createCommand($sql, [
            ':qtd' => $quantidade,
            ':id' => $produtoId,
        ])->queryScalar();

        // 3. Se nenhum registro foi alterado (afetados = 0 / scalar false), houve tentativa de overselling
        if ($novoEstoque === false || $novoEstoque === null) {
            $saldoAtual = (float)($produto->estoque_atual ?? 0);
            throw new EstoqueInsuficienteException(
                "Estoque insuficiente para '{$produto->nome}'. Saldo atual: {$saldoAtual}, solicitado: {$quantidade}."
            );
        }

        $novoEstoque = (float)$novoEstoque;

        Yii::info(sprintf(
            "[EstoqueService] Baixa realizada no produto %s (%s). Qtd: -%s, Saldo novo: %s. Motivo: %s",
            $produto->id,
            $produto->nome,
            $quantidade,
            $novoEstoque,
            $motivo ?? 'Não informado'
        ), 'estoque');

        // 4. Se o produto pertencer a um kit ou tiver variações, processa em cascata
        if ($produto->eh_kit && !empty($produto->kitItens)) {
            foreach ($produto->kitItens as $kitItem) {
                $qtdComponente = $kitItem->quantidade * $quantidade;
                self::baixarEstoque($kitItem->produto_componente_id, $qtdComponente, "Componente do Kit: {$produto->nome}", $permiteNegativo);
            }
        }

        // 5. Dispara evento assíncrono para atualizar marketplaces
        self::enfileirarSincronizacaoMarketplaces($produto->usuario_id, $produto->id, $novoEstoque);

        return $novoEstoque;
    }

    /**
     * Realiza a adição atômica de estoque (estorno, devolução ou entrada de compra).
     * 
     * @param string $produtoId UUID do produto
     * @param float $quantidade Quantidade a ser somada (> 0)
     * @param string|null $motivo Motivo da entrada
     * @return float Novo saldo em estoque
     */
    public static function adicionarEstoque(string $produtoId, float $quantidade, ?string $motivo = null): float
    {
        if ($quantidade <= 0) {
            throw new \InvalidArgumentException("Quantidade para adição de estoque deve ser maior que zero.");
        }

        $db = Yii::$app->db;

        $sql = "
            UPDATE prest_produtos
            SET estoque_atual = estoque_atual + :qtd,
                data_atualizacao = NOW()
            WHERE id = :id
            RETURNING estoque_atual, usuario_id, nome;
        ";

        $row = $db->createCommand($sql, [
            ':qtd' => $quantidade,
            ':id' => $produtoId,
        ])->queryOne();

        if (!$row) {
            throw new \Exception("Produto ID '{$produtoId}' não encontrado para entrada de estoque.");
        }

        $novoEstoque = (float)$row['estoque_atual'];
        $usuarioId = $row['usuario_id'];

        Yii::info(sprintf(
            "[EstoqueService] Adição de estoque no produto %s (%s). Qtd: +%s, Saldo novo: %s. Motivo: %s",
            $produtoId,
            $row['nome'] ?? '',
            $quantidade,
            $novoEstoque,
            $motivo ?? 'Não informado'
        ), 'estoque');

        // Enfileira sincronização nos marketplaces
        self::enfileirarSincronizacaoMarketplaces($usuarioId, $produtoId, $novoEstoque);

        return $novoEstoque;
    }

    /**
     * Enfileira job assíncrono para atualizar todos os marketplaces conectados
     */
    protected static function enfileirarSincronizacaoMarketplaces(string $usuarioId, string $produtoId, float $novoEstoque): void
    {
        try {
            if (Yii::$app->has('queue')) {
                Yii::$app->queue->push(new \app\modules\marketplace\jobs\SyncEstoqueMarketplaceJob([
                    'tenantId' => $usuarioId,
                    'produtoId' => $produtoId,
                    'novoEstoque' => (int)max(0, $novoEstoque),
                ]));
            }
        } catch (\Throwable $e) {
            Yii::error("Falha ao enfileirar SyncEstoqueMarketplaceJob: " . $e->getMessage(), 'marketplace');
        }
    }
}
