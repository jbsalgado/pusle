<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use app\modules\api\models\Orcamento;
use app\modules\api\models\OrcamentoItem;
use app\modules\vendas\models\Produto;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\NotFoundHttpException;
use Exception;

class OrcamentoController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
            ],
        ];
        return $behaviors;
    }

    protected function verbs()
    {
        return [
            'create' => ['POST', 'OPTIONS'],
            'confirmar-recebimento' => ['POST', 'OPTIONS'],
        ];
    }

    public function actionCreate()
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $data = Yii::$app->request->post();

            // Validação básica
            if (empty($data['itens']) || !is_array($data['itens'])) {
                throw new BadRequestHttpException('O pedido deve conter pelo menos um item.');
            }

            $orcamento = new Orcamento();
            $orcamento->load($data, '');

            // Campos que podem não vir no load() direto ou precisam de tratamento
            $orcamento->usuario_id = $data['usuario_id'] ?? null;
            $orcamento->cliente_id = $data['cliente_id'] ?? null;
            $orcamento->colaborador_vendedor_id = $data['colaborador_vendedor_id'] ?? null;
            $orcamento->forma_pagamento_id = $data['forma_pagamento_id'] ?? null;
            $orcamento->valor_total = 0; // Será recalculado
            $orcamento->observacoes = $data['observacoes'] ?? 'Orçamento PWA';
            $orcamento->status = 'PENDENTE';

            // Tratamento de acréscimo
            $orcamento->acrescimo_valor = $data['acrescimo_valor'] ?? 0;
            $orcamento->acrescimo_tipo = $data['acrescimo_tipo'] ?? null;
            $orcamento->observacao_acrescimo = $data['observacao_acrescimo'] ?? null;

            if (!$orcamento->save()) {
                throw new ServerErrorHttpException('Erro ao criar orçamento: ' . json_encode($orcamento->errors));
            }

            $valorTotalItens = 0;

            foreach ($data['itens'] as $itemData) {
                $produto = Produto::findOne($itemData['produto_id']);
                if (!$produto) {
                    continue; // Pula produto inexistente
                }

                $item = new OrcamentoItem();
                $item->orcamento_id = $orcamento->id;
                $item->produto_id = $produto->id;
                $item->quantidade = $itemData['quantidade'];
                $item->preco_unitario = $itemData['preco_unitario'] ?? $produto->preco_venda; // Usa preço enviado ou do cadastro
                $item->desconto_valor = $itemData['desconto_valor'] ?? 0;

                // Calcula subtotal
                $subtotal = ($item->quantidade * $item->preco_unitario) - $item->desconto_valor;
                $item->subtotal = max(0, $subtotal);
                $item->observacoes = $itemData['observacoes'] ?? null;

                if (!$item->save()) {
                    throw new ServerErrorHttpException('Erro ao salvar item do orçamento: ' . json_encode($item->errors));
                }

                $valorTotalItens += $item->subtotal;
            }

            // Atualiza totais do orçamento
            $orcamento->valor_total = $valorTotalItens + $orcamento->acrescimo_valor;
            $orcamento->save(false); // Salva sem validar novamente

            $transaction->commit();

            // Retorna formato compatível com o frontend (Flattened)
            // IMPORTANTE: Retornando diretamente o objeto para evitar aninhamento excessivo 'data.data' no frontend
            $orcamento->refresh();

            return array_merge($orcamento->toArray(), [
                'itens' => $orcamento->itens,
                'is_orcamento' => true, // Flag explícita
                'id' => $orcamento->id, // Garante ID numérico no topo
                'success' => true,
                'message' => 'Orçamento criado com sucesso!'
            ]);
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::error("Erro ao criar orçamento: " . $e->getMessage(), 'api');
            throw new ServerErrorHttpException($e->getMessage());
        }
    }

    public function actionConfirmarRecebimento()
    {
        $data = Yii::$app->request->post();
        $id = $data['venda_id'] ?? null; // Front envia como venda_id para compatibilidade

        if (!$id) {
            throw new BadRequestHttpException('ID do orçamento não fornecido.');
        }

        $orcamento = Orcamento::findOne($id);

        if (!$orcamento) {
            throw new NotFoundHttpException('Orçamento não encontrado.');
        }

        // Apenas atualiza o status, sem movimentar estoque ou caixa
        $orcamento->status = 'APROVADO'; // Ou FINALIZADO, conforme regra de negócio

        if ($orcamento->save()) {
            return [
                'success' => true,
                'message' => 'Orçamento confirmado com sucesso!',
                'data' => $orcamento
            ];
        } else {
            throw new ServerErrorHttpException('Erro ao confirmar orçamento: ' . json_encode($orcamento->errors));
        }
    }
}
