<?php

namespace app\modules\caixa\helpers;

use Yii;
use app\modules\caixa\models\Caixa;
use app\modules\caixa\models\CaixaMovimentacao;

/**
 * Helper para operações relacionadas ao Caixa
 * 
 * Este helper fornece métodos estáticos para registrar movimentações
 * no caixa de forma automática, integrando com outros módulos do sistema.
 */
class CaixaHelper
{
    /**
     * Registra entrada no caixa quando uma venda direta é finalizada
     * 
     * @param string $vendaId ID da venda
     * @param float $valor Valor da venda
     * @param string|null $formaPagamentoId ID da forma de pagamento (opcional)
     * @param string|null $usuarioId ID do usuário (se null, usa o usuário logado)
     * @return bool|CaixaMovimentacao Retorna a movimentação criada ou false em caso de erro
     */
    public static function registrarEntradaVenda($vendaId, $valor, $formaPagamentoId = null, $usuarioId = null)
    {
        try {
            $usuarioId = $usuarioId ?: Yii::$app->user->id;

            if (!$usuarioId) {
                Yii::warning("Tentativa de registrar venda no caixa sem usuário identificado", 'caixa');
                return false;
            }

            // Busca caixa aberto do usuário
            $caixa = Caixa::find()
                ->where(['usuario_id' => $usuarioId, 'status' => Caixa::STATUS_ABERTO])
                ->orderBy(['data_abertura' => SORT_DESC])
                ->one();

            if (!$caixa) {
                Yii::warning("⚠️ VENDA REALIZADA COM CAIXA FECHADO. Venda ID: {$vendaId}, Usuário ID: {$usuarioId}, Valor: R$ {$valor}. A venda foi processada, mas não foi registrada no caixa. É necessário abrir um caixa e registrar a movimentação manualmente.", 'caixa');
                // Não lança exceção, apenas registra no log
                // O sistema pode funcionar sem caixa aberto (vendas podem ser registradas depois)
                return false;
            }

            // Verifica se o caixa é do dia anterior
            if ($caixa->isAbertoDiaAnterior()) {
                // Fecha automaticamente o caixa do dia anterior
                $caixa->fecharAutomaticamente("Fechado automaticamente: caixa do dia anterior detectado ao registrar venda #{$vendaId}.");
                Yii::warning("⚠️ VENDA REALIZADA COM CAIXA DO DIA ANTERIOR. O caixa foi fechado automaticamente. Venda ID: {$vendaId}, Usuário ID: {$usuarioId}, Valor: R$ {$valor}. É necessário abrir um novo caixa para registrar esta e futuras vendas.", 'caixa');
                // Não registra a movimentação no caixa fechado
                return false;
            }

            // Verifica se o caixa é do dia atual
            if (!$caixa->isAbertoHoje()) {
                // Caso raro: caixa aberto mas não é de hoje nem de ontem (pode ser bug)
                Yii::error("ERRO: Caixa aberto com data inválida. Caixa ID: {$caixa->id}, Data Abertura: {$caixa->data_abertura}, Venda ID: {$vendaId}", 'caixa');
                return false;
            }

            // Cria a movimentação
            $movimentacao = new CaixaMovimentacao();
            $movimentacao->caixa_id = $caixa->id;
            // Nota: usuario_id não existe na tabela movimentacoes, o usuário é obtido através do caixa
            $movimentacao->tipo = CaixaMovimentacao::TIPO_ENTRADA;
            $movimentacao->categoria = CaixaMovimentacao::CATEGORIA_VENDA;
            $movimentacao->valor = $valor;
            $movimentacao->descricao = "Venda #" . substr($vendaId, 0, 8);
            $movimentacao->venda_id = $vendaId;
            $movimentacao->forma_pagamento_id = $formaPagamentoId;
            $movimentacao->data_movimento = date('Y-m-d H:i:s');

            if (!$movimentacao->save()) {
                $erros = $movimentacao->getFirstErrors();
                Yii::error("Erro ao registrar movimentação no caixa: " . implode(', ', $erros), 'caixa');
                return false;
            }

            Yii::info("✅ Movimentação registrada no caixa: Venda #{$vendaId}, Valor: R$ {$valor}, Caixa: {$caixa->id}", 'caixa');

            return $movimentacao;
        } catch (\Exception $e) {
            Yii::error("Exceção ao registrar entrada de venda no caixa: " . $e->getMessage(), 'caixa');
            return false;
        }
    }

    /**
     * Busca o caixa aberto atual do usuário (do dia atual)
     * 
     * @param string|null $usuarioId ID do usuário (se null, usa o usuário logado)
     * @param bool $fecharDiaAnterior Se true, fecha automaticamente caixas do dia anterior
     * @return Caixa|null Retorna o caixa aberto do dia atual ou null se não houver
     */
    public static function getCaixaAberto($usuarioId = null, $fecharDiaAnterior = true)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;

        if (!$usuarioId) {
            return null;
        }

        $caixasAbertos = Caixa::find()
            ->where(['usuario_id' => $usuarioId, 'status' => Caixa::STATUS_ABERTO])
            ->orderBy(['data_abertura' => SORT_DESC])
            ->all();

        if (empty($caixasAbertos)) {
            return null;
        }

        // Se há múltiplos caixas abertos, fecha os do dia anterior
        if (count($caixasAbertos) > 1 && $fecharDiaAnterior) {
            foreach ($caixasAbertos as $caixa) {
                if ($caixa->isAbertoDiaAnterior()) {
                    $caixa->fecharAutomaticamente('Fechado automaticamente: múltiplos caixas abertos detectados.');
                    Yii::warning("Caixa do dia anterior fechado automaticamente: {$caixa->id}", 'caixa');
                }
            }
        }

        // Retorna o primeiro caixa do dia atual
        foreach ($caixasAbertos as $caixa) {
            if ($caixa->isAbertoHoje()) {
                return $caixa;
            }
        }

        // Se não há caixa do dia atual, retorna null
        return null;
    }

    /**
     * Fecha automaticamente todos os caixas do dia anterior para um usuário
     * 
     * @param string|null $usuarioId ID do usuário (se null, usa o usuário logado)
     * @return int Número de caixas fechados
     */
    public static function fecharCaixasDiaAnterior($usuarioId = null)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;

        if (!$usuarioId) {
            return 0;
        }

        $caixasDiaAnterior = Caixa::find()
            ->where(['usuario_id' => $usuarioId, 'status' => Caixa::STATUS_ABERTO])
            ->all();

        $fechados = 0;
        foreach ($caixasDiaAnterior as $caixa) {
            if ($caixa->isAbertoDiaAnterior()) {
                if ($caixa->fecharAutomaticamente('Fechado automaticamente: limpeza de caixas do dia anterior.')) {
                    $fechados++;
                }
            }
        }

        if ($fechados > 0) {
            Yii::info("Fechados {$fechados} caixa(s) do dia anterior para usuário {$usuarioId}", 'caixa');
        }

        return $fechados;
    }

    /**
     * Registra entrada no caixa quando uma parcela é paga
     * 
     * @param string $parcelaId ID da parcela
     * @param float $valor Valor da parcela
     * @param string|null $formaPagamentoId ID da forma de pagamento (opcional)
     * @param string|null $usuarioId ID do usuário (se null, usa o usuário da parcela)
     * @return bool|CaixaMovimentacao Retorna a movimentação criada ou false em caso de erro
     */
    public static function registrarEntradaParcela($parcelaId, $valor, $formaPagamentoId = null, $usuarioId = null)
    {
        try {
            // Busca a parcela para obter o usuario_id se não foi informado
            $parcela = \app\modules\vendas\models\Parcela::findOne($parcelaId);
            if (!$parcela) {
                Yii::warning("Tentativa de registrar parcela no caixa: parcela não encontrada. Parcela ID: {$parcelaId}", 'caixa');
                return false;
            }

            $usuarioId = $usuarioId ?: $parcela->usuario_id ?: Yii::$app->user->id;

            if (!$usuarioId) {
                Yii::warning("Tentativa de registrar parcela no caixa sem usuário identificado. Parcela ID: {$parcelaId}", 'caixa');
                return false;
            }

            // Verifica se já existe movimentação para esta parcela (evita duplicação)
            $movimentacaoExistente = CaixaMovimentacao::find()
                ->where(['parcela_id' => $parcelaId])
                ->one();

            if ($movimentacaoExistente) {
                Yii::info("Movimentação já existe para parcela {$parcelaId}. Evitando duplicação. Movimentação ID: {$movimentacaoExistente->id}", 'caixa');
                return $movimentacaoExistente;
            }

            // Busca caixa aberto do dia atual
            $caixa = self::getCaixaAberto($usuarioId);

            if (!$caixa) {
                Yii::warning("⚠️ PARCELA PAGA COM CAIXA FECHADO. Parcela ID: {$parcelaId}, Usuário ID: {$usuarioId}, Valor: R$ {$valor}. A parcela foi marcada como paga, mas não foi registrada no caixa. É necessário abrir um caixa e registrar a movimentação manualmente.", 'caixa');
                // Não lança exceção, apenas registra no log
                // O sistema pode funcionar sem caixa aberto (parcelas podem ser registradas depois)
                return false;
            }

            // Cria a movimentação
            $movimentacao = new CaixaMovimentacao();
            $movimentacao->caixa_id = $caixa->id;
            $movimentacao->tipo = CaixaMovimentacao::TIPO_ENTRADA;
            $movimentacao->categoria = CaixaMovimentacao::CATEGORIA_PAGAMENTO;
            $movimentacao->valor = $valor;
            $movimentacao->descricao = "Pagamento de parcela #" . substr($parcelaId, 0, 8);
            $movimentacao->parcela_id = $parcelaId;
            $movimentacao->forma_pagamento_id = $formaPagamentoId;
            $movimentacao->data_movimento = date('Y-m-d H:i:s');

            if (!$movimentacao->save()) {
                $erros = $movimentacao->getFirstErrors();
                Yii::error("Erro ao registrar movimentação de parcela no caixa: " . implode(', ', $erros), 'caixa');
                return false;
            }

            Yii::info("✅ Movimentação registrada no caixa: Parcela #{$parcelaId}, Valor: R$ {$valor}, Caixa: {$caixa->id}", 'caixa');

            return $movimentacao;
        } catch (\Exception $e) {
            Yii::error("Exceção ao registrar entrada de parcela no caixa: " . $e->getMessage(), 'caixa');
            return false;
        }
    }

    /**
     * Verifica se há saldo suficiente no caixa para uma saída
     * 
     * @param string $caixaId ID do caixa
     * @param float $valor Valor da saída
     * @return bool Retorna true se há saldo suficiente
     */
    public static function verificarSaldoSuficiente($caixaId, $valor)
    {
        $caixa = Caixa::findOne($caixaId);

        if (!$caixa || !$caixa->isAberto()) {
            return false;
        }

        $saldoAtual = $caixa->calcularValorEsperado();
        return $saldoAtual >= $valor;
    }
    /**
     * Registra saída no caixa quando uma conta é paga
     * 
     * @param string $contaPagarId ID da conta a pagar
     * @param float $valor Valor pago
     * @param string|null $usuarioId ID do usuário (se null, usa o usuário logado)
     * @return bool|CaixaMovimentacao Retorna a movimentação criada ou false em caso de erro
     */
    public static function registrarSaidaContaPagar($contaPagarId, $valor, $usuarioId = null)
    {
        try {
            $usuarioId = $usuarioId ?: Yii::$app->user->id;

            // Busca caixa aberto do dia atual
            $caixa = self::getCaixaAberto($usuarioId);

            if (!$caixa) {
                Yii::warning("⚠️ CONTA PAGA COM CAIXA FECHADO. Conta ID: {$contaPagarId}. Valor: R$ {$valor}. A conta foi marcada como paga, mas não debitada do caixa.", 'caixa');
                return false;
            }

            // Opcional: Verificar saldo (sistema permite saldo negativo?)
            // Por enquanto permite, mas loga se ficar negativo

            $conta = \app\modules\contas_pagar\models\ContaPagar::findOne($contaPagarId);
            $desc = $conta ? "Pagamento: " . substr($conta->descricao, 0, 50) : "Pagamento Conta #{$contaPagarId}";

            // Cria a movimentação
            $movimentacao = new CaixaMovimentacao();
            $movimentacao->caixa_id = $caixa->id;
            $movimentacao->tipo = CaixaMovimentacao::TIPO_SAIDA;
            $movimentacao->categoria = CaixaMovimentacao::CATEGORIA_CONTA_PAGAR;
            $movimentacao->valor = $valor;
            $movimentacao->descricao = $desc;
            $movimentacao->conta_pagar_id = $contaPagarId;
            $movimentacao->forma_pagamento_id = $conta->forma_pagamento_id ?? null;
            $movimentacao->data_movimento = date('Y-m-d H:i:s');

            if (!$movimentacao->save()) {
                $erros = $movimentacao->getFirstErrors();
                Yii::error("Erro ao registrar saída de conta no caixa: " . implode(', ', $erros), 'caixa');
                return false;
            }

            Yii::info("✅ Saída registrada no caixa: Conta #{$contaPagarId}, Valor: R$ {$valor}", 'caixa');

            return $movimentacao;
        } catch (\Exception $e) {
            Yii::error("Exceção ao registrar saída de conta no caixa: " . $e->getMessage(), 'caixa');
            return false;
        }
    }
}
