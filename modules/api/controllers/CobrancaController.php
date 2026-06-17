<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use app\modules\vendas\models\Parcela;
use app\modules\vendas\models\HistoricoCobranca;
use app\modules\vendas\models\StatusParcela;
use app\modules\vendas\models\FormaPagamento;

/**
 * API Controller para Cobranças
 */
class CobrancaController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;

        // CORS - Corrigido: não pode usar '*' com credentials: true
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['http://localhost', 'http://localhost:*', 'http://127.0.0.1', 'http://127.0.0.1:*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => false, // Não pode ser true com wildcard
                'Access-Control-Max-Age' => 86400,
            ],
        ];

        return $behaviors;
    }

    /**
     * POST /api/cobranca/registrar-pagamento
     * Registra um pagamento de parcela (pode vir de sincronização offline)
     */
    public function actionRegistrarPagamento()
    {
        try {
            $data = Yii::$app->request->post();

            // Validações
            $required = ['parcela_id', 'cobrador_id', 'cliente_id', 'usuario_id', 'tipo_acao'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    Yii::$app->response->statusCode = 400;
                    return ['erro' => "Campo obrigatório ausente: {$field}"];
                }
            }

            // Para PAGAMENTO, valor_recebido e forma_pagamento são obrigatórios
            if ($data['tipo_acao'] === HistoricoCobranca::TIPO_PAGAMENTO) {
                if (!isset($data['valor_recebido']) || !isset($data['forma_pagamento'])) {
                    Yii::$app->response->statusCode = 400;
                    return ['erro' => 'Para pagamento, valor_recebido e forma_pagamento são obrigatórios'];
                }
            } else {
                // Para outros tipos, valor_recebido é 0 e forma_pagamento pode ser vazio
                $data['valor_recebido'] = $data['valor_recebido'] ?? 0;
                $data['forma_pagamento'] = $data['forma_pagamento'] ?? '';
            }

            // Busca parcela
            $parcela = Parcela::findOne($data['parcela_id']);
            if (!$parcela) {
                Yii::$app->response->statusCode = 404;
                return ['erro' => 'Parcela não encontrada'];
            }

            // Só atualiza parcela se for PAGAMENTO
            if ($data['tipo_acao'] === HistoricoCobranca::TIPO_PAGAMENTO) {
                // Busca ou cria forma de pagamento ANTES de atualizar a parcela (para usar na integração com caixa)
                $formaPagamento = null;
                if (!empty($data['forma_pagamento'])) {
                    $formaPagamento = FormaPagamento::find()
                        ->where(['usuario_id' => $data['usuario_id'], 'nome' => $data['forma_pagamento']])
                        ->one();

                    if (!$formaPagamento) {
                        // Cria forma de pagamento se não existir (DINHEIRO ou PIX)
                        $formaPagamento = new FormaPagamento();
                        $formaPagamento->usuario_id = $data['usuario_id'];
                        $formaPagamento->nome = $data['forma_pagamento'];
                        $formaPagamento->ativo = true;
                        if (!$formaPagamento->save()) {
                            Yii::warning("Erro ao criar forma de pagamento: " . json_encode($formaPagamento->errors), __METHOD__);
                        }
                    }
                }

                // Verifica se já está paga (evita duplicação)
                if ($parcela->status_parcela_codigo === 'PAGA') {
                    // Se já está paga, apenas registra o histórico (idempotência)
                    Yii::warning("Tentativa de registrar pagamento de parcela já paga: {$parcela->id}", __METHOD__);
                } else {
                    // Atualiza parcela apenas se for pagamento
                    $parcela->status_parcela_codigo = 'PAGA';
                    $parcela->data_pagamento = $data['data_acao'] ? date('Y-m-d', strtotime($data['data_acao'])) : date('Y-m-d');
                    $parcela->valor_pago = $data['valor_recebido'];

                    // Atualiza forma de pagamento na parcela se foi informada
                    if ($formaPagamento) {
                        $parcela->forma_pagamento_id = $formaPagamento->id;
                    }

                    if (!$parcela->save()) {
                        Yii::$app->response->statusCode = 500;
                        return ['erro' => 'Erro ao atualizar parcela', 'erros' => $parcela->errors];
                    }

                    // ===== INTEGRAÇÃO COM CAIXA =====
                    // Registra entrada no caixa quando parcela é paga
                    try {
                        $movimentacao = \app\modules\caixa\helpers\CaixaHelper::registrarEntradaParcela(
                            $parcela->id,
                            $parcela->valor_pago,
                            $formaPagamento ? $formaPagamento->id : null,
                            $data['usuario_id']
                        );

                        if ($movimentacao) {
                            Yii::info("✅ Entrada registrada no caixa para Parcela ID {$parcela->id}", 'api');
                        } else {
                            // Não falha o pagamento se não houver caixa aberto, apenas registra no log
                            Yii::warning("⚠️ Não foi possível registrar entrada no caixa para Parcela ID {$parcela->id} (caixa pode não estar aberto)", 'api');
                        }
                    } catch (\Exception $e) {
                        // Não falha o pagamento se houver erro no caixa, apenas registra no log
                        Yii::error("Erro ao registrar entrada no caixa (não crítico): " . $e->getMessage(), 'api');
                    }
                }
            }
            // Para outros tipos de ação (VISITA, AUSENTE, RECUSA, NEGOCIACAO), apenas registra no histórico

            // Registra histórico de cobrança
            $historico = new HistoricoCobranca();
            $historico->parcela_id = $data['parcela_id'];
            $historico->cobrador_id = $data['cobrador_id'];
            $historico->cliente_id = $data['cliente_id'];
            $historico->usuario_id = $data['usuario_id'];
            $historico->tipo_acao = $data['tipo_acao'];
            $historico->valor_recebido = $data['valor_recebido'];
            $historico->observacao = $data['observacao'] ?? '';
            $historico->localizacao_lat = $data['localizacao_lat'] ?? null;
            $historico->localizacao_lng = $data['localizacao_lng'] ?? null;
            $historico->data_acao = $data['data_acao'] ? date('Y-m-d H:i:s', strtotime($data['data_acao'])) : date('Y-m-d H:i:s');

            if (!$historico->save()) {
                Yii::$app->response->statusCode = 500;
                return ['erro' => 'Erro ao registrar histórico', 'erros' => $historico->errors];
            }

            $mensagens = [
                HistoricoCobranca::TIPO_PAGAMENTO => 'Pagamento registrado com sucesso',
                HistoricoCobranca::TIPO_VISITA => 'Visita registrada com sucesso',
                HistoricoCobranca::TIPO_AUSENTE => 'Visita registrada: Cliente ausente',
                HistoricoCobranca::TIPO_RECUSA => 'Visita registrada: Cliente recusou pagamento',
                HistoricoCobranca::TIPO_NEGOCIACAO => 'Visita registrada: Negociação realizada',
            ];

            return [
                'sucesso' => true,
                'parcela_id' => $parcela->id,
                'historico_id' => $historico->id,
                'tipo_acao' => $data['tipo_acao'],
                'mensagem' => $mensagens[$data['tipo_acao']] ?? 'Ação registrada com sucesso'
            ];
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            Yii::error("Erro ao registrar pagamento: " . $e->getMessage(), __METHOD__);
            return ['erro' => 'Erro ao registrar pagamento: ' . $e->getMessage()];
        }
    }

    /**
     * POST /api/cobranca/registrar-venda
     * Registra uma nova venda vinda do módulo Prestanista (offline sync)
     */
    public function actionRegistrarVenda()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $data = Yii::$app->request->post();

            // Validações básicas
            $required = ['cliente_id', 'cobrador_id', 'usuario_id', 'valor_total', 'numero_parcelas', 'itens'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    throw new \Exception("Campo obrigatório ausente: {$field}");
                }
            }

            // 1. Criar a Venda
            $venda = new \app\modules\vendas\models\Venda();
            $venda->usuario_id = $data['usuario_id'];
            $venda->cliente_id = $data['cliente_id'];
            $venda->colaborador_vendedor_id = $data['cobrador_id'];
            $venda->valor_total = $data['valor_total'];
            $venda->numero_parcelas = $data['numero_parcelas'];
            $venda->data_venda = $data['data_venda'] ?? date('Y-m-d H:i:s');
            $venda->status_venda_codigo = 'EM_ABERTO';
            $venda->observacoes = $data['observacoes'] ?? 'Venda Prestanista Digital';

            if (!$venda->save()) {
                throw new \Exception("Erro ao salvar venda: " . json_encode($venda->errors));
            }

            // 2. Criar os Itens
            foreach ($data['itens'] as $itemData) {
                $item = new \app\modules\vendas\models\VendaItem();
                $item->venda_id = $venda->id;
                $item->produto_id = $itemData['produto_id'];
                $item->quantidade = $itemData['quantidade'];
                $item->preco_unitario_venda = $itemData['preco_unitario'];
                $item->valor_total_item = $item->quantidade * $item->preco_unitario_venda;

                if (!$item->save()) {
                    throw new \Exception("Erro ao salvar item: " . json_encode($item->errors));
                }
            }

            // 3. Gerar Parcelas
            $intervalo = $data['intervalo_dias'] ?? 30; // 7, 15 ou 30
            $venda->gerarParcelas(null, $data['data_primeiro_vencimento'] ?? null, $intervalo);

            $transaction->commit();

            return [
                'sucesso' => true,
                'venda_id' => $venda->id,
                'mensagem' => 'Venda registrada com sucesso'
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->response->statusCode = 500;
            return ['erro' => $e->getMessage()];
        }
    }
}
