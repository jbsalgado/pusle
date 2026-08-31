<?php

namespace app\modules\evolution\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\modules\evolution\models\WhatsappConfig;
use app\modules\evolution\models\WhatsappTemplate;

/**
 * MetaWebhookController
 *
 * Recebe eventos oficiais da Meta WhatsApp Cloud API (handshake de verificação e webhooks de eventos).
 */
class MetaWebhookController extends Controller
{
    /**
     * Desabilita validação CSRF para webhooks externos da Meta
     */
    public $enableCsrfValidation = false;

    /**
     * Endpoint unificado do Webhook da Meta
     */
    public function actionIndex(): Response
    {
        $response = Yii::$app->response;

        // 1. Validação de Handshake (GET) da Meta
        if (Yii::$app->request->isGet) {
            $mode        = Yii::$app->request->get('hub_mode') ?: Yii::$app->request->get('hub.mode');
            $verifyToken = Yii::$app->request->get('hub_verify_token') ?: Yii::$app->request->get('hub.verify_token');
            $challenge   = Yii::$app->request->get('hub_challenge') ?: Yii::$app->request->get('hub.challenge');

            $expectedToken = Yii::$app->params['meta_webhook_verify_token'] ?? 'pulse_meta_webhook_token_2026';

            if ($mode === 'subscribe' && ($verifyToken === $expectedToken || !empty(WhatsappConfig::findOne(['meta_webhook_verify_token' => $verifyToken])))) {
                $response->format = Response::FORMAT_RAW;
                $response->data = (string)$challenge;
                return $response;
            }

            $response->statusCode = 403;
            $response->data = 'Token de verificação inválido.';
            return $response;
        }

        // 2. Recepção de Eventos (POST) da Meta
        if (Yii::$app->request->isPost) {
            $rawBody = Yii::$app->request->getRawBody();
            $payload = json_decode($rawBody, true);

            if (empty($payload) || !isset($payload['entry'])) {
                $response->statusCode = 200;
                $response->data = 'OK - Ignored';
                return $response;
            }

            try {
                foreach ($payload['entry'] as $entry) {
                    $changes = $entry['changes'] ?? [];
                    foreach ($changes as $change) {
                        $field = $change['field'] ?? '';
                        $value = $change['value'] ?? [];

                        // A. Atualização de status de Template HSM
                        if ($field === 'message_template_status_update') {
                            $this->handleTemplateUpdate($value);
                        }

                        // B. Status de envio de mensagens (sent, delivered, read, failed)
                        if ($field === 'messages' && !empty($value['statuses'])) {
                            $this->handleMessageStatuses($value['statuses']);
                        }

                        // C. Mensagens recebidas de clientes
                        if ($field === 'messages' && !empty($value['messages'])) {
                            $this->handleIncomingMessages($value['messages'], $value['metadata'] ?? []);
                        }
                    }
                }
            } catch (\Throwable $t) {
                Yii::error('Erro ao processar Webhook Meta: ' . $t->getMessage(), __METHOD__);
            }

            $response->statusCode = 200;
            $response->format = Response::FORMAT_JSON;
            $response->data = ['status' => 'EVENT_RECEIVED'];
            return $response;
        }

        $response->statusCode = 405;
        return $response;
    }

    /**
     * Atualiza o status de aprovação de templates na base do Pulse
     */
    private function handleTemplateUpdate(array $data): void
    {
        $templateId = $data['message_template_id'] ?? null;
        $name       = $data['message_template_name'] ?? null;
        $language   = $data['message_template_language'] ?? 'pt_BR';
        $event      = strtoupper($data['event'] ?? '');

        if (empty($name)) return;

        $template = WhatsappTemplate::findOne(['name' => $name, 'language' => $language]);
        if ($template !== null) {
            if ($event === 'APPROVED') {
                $template->status = WhatsappTemplate::STATUS_APPROVED;
            } elseif ($event === 'REJECTED') {
                $template->status = WhatsappTemplate::STATUS_REJECTED;
            } elseif ($event === 'PAUSED') {
                $template->status = WhatsappTemplate::STATUS_PAUSED;
            } elseif ($event === 'DISABLED') {
                $template->status = WhatsappTemplate::STATUS_DISABLED;
            }
            if (!empty($templateId)) {
                $template->meta_template_id = (string)$templateId;
            }
            $template->save(false);
            Yii::info("Template {$name} atualizado para status {$template->status} via Meta Webhook.", __METHOD__);
        }
    }

    /**
     * Trata recibos de entrega, leitura e falhas de mensagens
     */
    private function handleMessageStatuses(array $statuses): void
    {
        foreach ($statuses as $st) {
            $msgId     = $st['id'] ?? '';
            $status    = $st['status'] ?? '';
            $recipient = $st['recipient_id'] ?? '';

            Yii::info("Status Meta WhatsApp [{$recipient}]: {$msgId} -> {$status}", __METHOD__);

            if ($status === 'failed') {
                $errors = $st['errors'] ?? [];
                Yii::warning("Falha de entrega WhatsApp Meta [{$recipient}]: " . json_encode($errors), __METHOD__);
            }
        }
    }

    /**
     * Trata mensagens enviadas por clientes (início de janela de 24h)
     */
    private function handleIncomingMessages(array $messages, array $metadata): void
    {
        $phoneNumberId = $metadata['phone_number_id'] ?? null;
        foreach ($messages as $msg) {
            $from = $msg['from'] ?? '';
            $type = $msg['type'] ?? '';
            $text = $msg['text']['body'] ?? ($msg['button']['text'] ?? '');

            Yii::info("Mensagem recebida via Meta WhatsApp de {$from} (phone_id: {$phoneNumberId}): [{$type}] {$text}", __METHOD__);
        }
    }
}
