<?php

namespace app\modules\marketplace\components;

use Yii;

/**
 * Handler de webhooks da Shopee
 * 
 * Processa notificações da Shopee sobre:
 * - Pedidos (order_create, order_update)
 * - Produtos (item_update)
 * - Estoque (stock_update)
 */
class ShopeeWebhookHandler extends BaseWebhookHandler
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
     * Valida assinatura do webhook da Shopee
     * 
     * @param string $rawBody Corpo bruto
     * @param array $headers Headers
     * @return bool
     */
    protected function validateSignature($rawBody, $headers)
    {
        $signature = $headers['authorization'] ?? $headers['Authorization'] ?? null;
        $timestamp = $headers['timestamp'] ?? $headers['Timestamp'] ?? null;

        if (!$signature || !$timestamp) {
            Yii::warning('Headers Authorization ou Timestamp não encontrados', __METHOD__);
            return false;
        }

        $secret = $this->config['client_secret'] ?? null;

        if (!$secret) {
            Yii::error('Client Secret não configurado para Shopee', __METHOD__);
            return false;
        }

        $path = Yii::$app->request->url;
        $validator = new WebhookSignatureValidator();
        return $validator->validateShopee($signature, $rawBody, $path, $timestamp, $secret);
    }

    /**
     * Identifica o tipo de evento do webhook
     * 
     * @param array $payload Payload decodificado
     * @return string|null
     */
    protected function getEventType($payload)
    {
        // Shopee envia o tipo no campo 'code' ou 'event'
        return $payload['code'] ?? $payload['event'] ?? null;
    }

    /**
     * Processa o evento específico
     * 
     * @param string $eventType Tipo do evento
     * @param array $payload Dados do evento
     * @return array Resultado do processamento
     */
    protected function processEvent($eventType, $payload)
    {
        switch ($eventType) {
            case 'order_create':
                return $this->processOrderCreate($payload);

            case 'order_update':
                return $this->processOrderUpdate($payload);

            case 'item_update':
                return $this->processItemUpdate($payload);

            case 'stock_update':
                return $this->processStockUpdate($payload);

            default:
                Yii::warning("Tipo de evento não suportado: {$eventType}", __METHOD__);
                return [
                    'processed' => false,
                    'reason' => 'Event type not supported',
                    'event_type' => $eventType,
                ];
        }
    }

    /**
     * Processa criação de pedido
     * 
     * @param array $payload Dados do evento
     * @return array Resultado
     */
    protected function processOrderCreate($payload)
    {
        $data = $payload['data'] ?? [];
        $orderId = $data['ordersn'] ?? null;

        if (!$orderId) {
            throw new \Exception('Order ID não encontrado no payload');
        }

        // Buscar dados completos do pedido via API
        $orderData = $this->fetchOrderData($orderId);

        if (!$orderData) {
            throw new \Exception('Não foi possível buscar dados do pedido');
        }

        // Processar pedido
        $result = $this->orderProcessor->processNewOrder(
            $orderData,
            'SHOPEE',
            $this->config['usuario_id']
        );

        return $result;
    }

    /**
     * Processa atualização de pedido
     * 
     * @param array $payload Dados do evento
     * @return array Resultado
     */
    protected function processOrderUpdate($payload)
    {
        $data = $payload['data'] ?? [];
        $orderId = $data['ordersn'] ?? null;

        if (!$orderId) {
            throw new \Exception('Order ID não encontrado no payload');
        }

        // Buscar dados completos do pedido via API
        $orderData = $this->fetchOrderData($orderId);

        if (!$orderData) {
            throw new \Exception('Não foi possível buscar dados do pedido');
        }

        // Processar atualização
        $result = $this->orderProcessor->processOrderUpdate(
            $orderData,
            'SHOPEE',
            $this->config['usuario_id']
        );

        return $result;
    }

    /**
     * Processa atualização de produto
     * 
     * @param array $payload Dados do evento
     * @return array Resultado
     */
    protected function processItemUpdate($payload)
    {
        // TODO: Implementar processamento de atualização de produtos
        Yii::info('Evento de atualização de produto recebido: ' . json_encode($payload), __METHOD__);

        return [
            'processed' => true,
            'action' => 'logged',
            'item_id' => $payload['data']['item_id'] ?? null,
        ];
    }

    /**
     * Processa atualização de estoque
     * 
     * @param array $payload Dados do evento
     * @return array Resultado
     */
    protected function processStockUpdate($payload)
    {
        // TODO: Implementar processamento de atualização de estoque
        Yii::info('Evento de atualização de estoque recebido: ' . json_encode($payload), __METHOD__);

        return [
            'processed' => true,
            'action' => 'logged',
            'item_id' => $payload['data']['item_id'] ?? null,
        ];
    }

    /**
     * Busca dados completos do pedido via API da Shopee
     * 
     * @param string $orderId ID do pedido
     * @return array|null Dados do pedido
     */
    protected function fetchOrderData($orderId)
    {
        // TODO: Implementar chamada real à API da Shopee
        // Por enquanto, retorna dados mock
        Yii::warning('fetchOrderData da Shopee ainda não implementado', __METHOD__);

        return null;
    }
}
