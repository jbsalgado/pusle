<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\modules\vendas\models\Parcela;
use app\modules\caixa\helpers\CaixaHelper;

/**
 * WebhookController - Recebe notificações automáticas de pagamentos externos
 */
class WebhookController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * Webhook para Mercado Pago
     */
    public function actionMercadoPago()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = Yii::$app->request->post();

        Yii::info("Webhook recebido (Mercado Pago): " . json_encode($data), 'webhook');

        // Lógica básica de processamento (simplificada para demonstração)
        // O MP envia o ID do recurso e o tipo (ex: payment)
        if (isset($data['type']) && $data['type'] === 'payment') {
            $paymentId = $data['data']['id'] ?? null;
            if ($paymentId) {
                // Aqui buscaríamos os detalhes do pagamento via SDK do MP
                // E vincularíamos à Parcela do sistema
                Yii::info("Processando pagamento MP ID: $paymentId", 'webhook');
            }
        }

        return ['status' => 'ok'];
    }

    /**
     * Webhook para Asaas
     */
    public function actionAsaas()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = Yii::$app->request->post();

        Yii::info("Webhook recebido (Asaas): " . json_encode($data), 'webhook');

        // Exemplo: PAYMENT_CONFIRMED, PAYMENT_RECEIVED
        $event = $data['event'] ?? null;
        $payment = $data['payment'] ?? null;

        if ($event === 'PAYMENT_RECEIVED' && $payment) {
            $externalId = $payment['id'];

            // Busca a parcela vinculada a este ID externo
            $parcela = Parcela::find()->where(['id_integracao_externa' => $externalId])->one();

            if ($parcela && !$parcela->isPaga()) {
                $parcela->status = Parcela::STATUS_PAGO;
                $parcela->data_pagamento = date('Y-m-d');

                if ($parcela->save(false)) {
                    // Registra entrada no caixa automaticamente
                    CaixaHelper::registrarEntradaParcela($parcela->id, $parcela->valor, $parcela->forma_pagamento_id);
                    Yii::info("✅ Parcela #{$parcela->id} baixada via Webhook Asaas", 'webhook');
                }
            }
        }

        return ['status' => 'ok'];
    }
}
