<?php

namespace app\modules\marketplace\components;

use Yii;
use app\modules\marketplace\dto\MarketplaceOrderDTO;

/**
 * MagaluWebhookHandler - Handler especializado para eventos de webhooks do Magazine Luiza
 * 
 * Processa notificações assíncronas do Magalu:
 * - Pedidos criados / aprovados / cancelados
 * - Consulta o pedido completo via GET /orders/{order_id} (Fast-ACK + Ingestão Completa)
 * - Encaminha ao OrderEventProcessor com idempotência estrita
 */
class MagaluWebhookHandler extends BaseWebhookHandler
{
    /**
     * @var OrderEventProcessor Processador de eventos de pedidos
     */
    protected $orderProcessor;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->orderProcessor = new OrderEventProcessor();
    }

    /**
     * Valida assinatura ou token do webhook do Magalu
     * 
     * @param string $rawBody Corpo bruto
     * @param array $headers Headers
     * @return bool
     */
    protected function validateSignature($rawBody, $headers)
    {
        $secret = $this->config['client_secret'] ?? null;

        // Se não houver secret configurado no tenant, aceita (modo sandbox / sem assinatura obrigatória)
        if (empty($secret)) {
            return true;
        }

        $signature = $headers['x-magalu-signature'] 
            ?? $headers['x-signature'] 
            ?? $headers['x-magalu-token'] 
            ?? $headers['authorization'] 
            ?? null;

        if (!$signature) {
            // Verifica se o token veio via query param
            $tokenParam = Yii::$app->request->get('token');
            if ($tokenParam && hash_equals($secret, $tokenParam)) {
                return true;
            }

            Yii::warning('[MagaluWebhookHandler] Header de assinatura/token não encontrado.', 'marketplace');
            return false;
        }

        $validator = new WebhookSignatureValidator();
        return $validator->validateMagalu($signature, $rawBody, $secret);
    }

    /**
     * Identifica o tipo de evento do webhook
     * 
     * @param array $payload Payload decodificado
     * @return string|null
     */
    protected function getEventType($payload)
    {
        return $payload['event'] 
            ?? $payload['type'] 
            ?? $payload['topic'] 
            ?? $payload['action'] 
            ?? (isset($payload['order_id']) || isset($payload['data']['order_id']) ? 'order.updated' : null);
    }

    /**
     * Processa o evento específico recebido do Magalu
     * 
     * @param string $eventType Tipo do evento
     * @param array $payload Dados do evento
     * @return array Resultado do processamento
     */
    protected function processEvent($eventType, $payload)
    {
        $eventLower = strtolower(trim((string)$eventType));

        Yii::info("[MagaluWebhookHandler] Processando evento '{$eventType}' do Magalu.", 'marketplace');

        // Extrai identificador do pedido do payload enxuto
        $orderId = $payload['order_id'] 
            ?? $payload['data']['order_id'] 
            ?? $payload['code'] 
            ?? $payload['data']['code'] 
            ?? $payload['id'] 
            ?? null;

        if ($orderId) {
            return $this->processOrderWebhook((string)$orderId, $eventType, $payload);
        }

        return [
            'success' => true,
            'event' => $eventType,
            'message' => 'Evento recebido sem ID de pedido associado, ignorado com segurança.',
        ];
    }

    /**
     * Executa a busca do pedido completo via API do Magalu e processa no ERP
     * 
     * @param string $orderId ID do pedido
     * @param string $eventType
     * @param array $rawPayload
     * @return array
     */
    protected function processOrderWebhook(string $orderId, string $eventType, array $rawPayload): array
    {
        $service = new MagaluService();
        $service->setConfig($this->config);

        Yii::info("[MagaluWebhookHandler] Buscando dados completos do pedido {$orderId} na API Magalu...", 'marketplace');

        // Busca o pedido completo na API (GET /orders/{order_id})
        $orderData = $service->getOrder($orderId);

        if (empty($orderData)) {
            Yii::error("[MagaluWebhookHandler] Pedido {$orderId} não retornou dados na API Magalu.", 'marketplace');
            return [
                'success' => false,
                'error' => "Pedido {$orderId} não encontrado na API Magalu.",
            ];
        }

        // Anexa o rawPayload do webhook para auditoria
        $orderData['_webhook_event'] = $eventType;
        $orderData['_webhook_received_at'] = date('Y-m-d H:i:s');

        // Normaliza para o DTO canônico
        $dto = $service->normalizeOrderToDTO($orderData);

        // Processa no OrderEventProcessor de forma idempotente e transacional
        $result = $this->orderProcessor->processOrder($dto);

        return [
            'success' => true,
            'order_id' => $orderId,
            'event' => $eventType,
            'action' => $result['action'] ?? 'processed',
            'venda_id' => $result['venda_id'] ?? null,
            'message' => "Pedido {$orderId} processado com sucesso.",
        ];
    }
}
