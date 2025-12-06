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
            $required = ['parcela_id', 'cobrador_id', 'cliente_id', 'usuario_id', 'tipo_acao', 'valor_recebido', 'forma_pagamento'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    Yii::$app->response->statusCode = 400;
                    return ['erro' => "Campo obrigatório ausente: {$field}"];
                }
            }

            // Busca parcela
            $parcela = Parcela::findOne($data['parcela_id']);
            if (!$parcela) {
                Yii::$app->response->statusCode = 404;
                return ['erro' => 'Parcela não encontrada'];
            }

            // Verifica se já está paga (evita duplicação)
            if ($parcela->status_parcela_codigo === 'PAGA') {
                // Se já está paga, apenas registra o histórico (idempotência)
                Yii::warning("Tentativa de registrar pagamento de parcela já paga: {$parcela->id}", __METHOD__);
            } else {
                // Atualiza parcela
                $parcela->status_parcela_codigo = 'PAGA';
                $parcela->data_pagamento = $data['data_acao'] ? date('Y-m-d', strtotime($data['data_acao'])) : date('Y-m-d');
                $parcela->valor_pago = $data['valor_recebido'];
                
                if (!$parcela->save()) {
                    Yii::$app->response->statusCode = 500;
                    return ['erro' => 'Erro ao atualizar parcela', 'erros' => $parcela->errors];
                }
            }

            // Busca ou cria forma de pagamento
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

            return [
                'sucesso' => true,
                'parcela_id' => $parcela->id,
                'historico_id' => $historico->id,
                'mensagem' => 'Pagamento registrado com sucesso'
            ];

        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            Yii::error("Erro ao registrar pagamento: " . $e->getMessage(), __METHOD__);
            return ['erro' => 'Erro ao registrar pagamento: ' . $e->getMessage()];
        }
    }
}

