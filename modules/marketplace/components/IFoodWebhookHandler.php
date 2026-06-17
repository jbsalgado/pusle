<?php

namespace app\modules\marketplace\components;

use Yii;
use app\modules\marketplace\models\MarketplacePedido;

/**
 * IFoodWebhookHandler - Processador de webhooks do iFood (V2)
 */
class IFoodWebhookHandler extends BaseWebhookHandler
{
    /**
     * Valida a assinatura do iFood
     * @param string $rawBody
     * @param array $headers
     * @return bool
     */
    protected function validateSignature($rawBody, $headers)
    {
        // TODO: Implementar validação real usando x-ifood-signature
        // https://developer.ifood.com.br/docs/guides/webhooks#signature-validation
        return true;
    }

    /**
     * Identifica o tipo de evento (iFood V2 usa o campo 'code')
     */
    protected function getEventType($payload)
    {
        // Eventos comuns: PLACED, CONFIRMED, CANCELLED, READY_TO_PICKUP
        return $payload['code'] ?? null;
    }

    /**
     * Processa o evento específico do iFood
     */
    protected function processEvent($eventType, $payload)
    {
        $orderId = $payload['orderId'] ?? null;
        if (!$orderId) {
            throw new \Exception('orderId não encontrado no payload do iFood');
        }

        $processor = new OrderEventProcessor();
        $usuarioId = $this->config['usuario_id'] ?? null;

        switch ($eventType) {
            case 'PLACED':
                // Novo pedido. Importante: no iFood V2, o payload do webhook é pequeno.
                // Geralmente precisamos fazer um GET /orders/{id} para pegar os detalhes completos.
                // Como estamos em Mock/Groundwork, vamos simular os dados básicos.
                $orderData = $this->mockOrderData($orderId);
                return $processor->processNewOrder($orderData, 'IFOOD', $usuarioId);

            case 'CANCELLED':
                return $processor->processOrderCancellation($orderId, 'IFOOD');

            case 'CONFIRMED':
                // Atualiza status local para 'confirmed'
                $pedido = MarketplacePedido::findOne([
                    'marketplace' => 'IFOOD',
                    'marketplace_pedido_id' => $orderId
                ]);
                if ($pedido) {
                    $pedido->status = 'confirmed';
                    $pedido->save();
                }
                return ['action' => 'status_updated', 'status' => 'confirmed'];

            default:
                Yii::info("Evento iFood ignorado: {$eventType}", 'marketplace');
                return ['action' => 'ignored'];
        }
    }

    /**
     * Simula dados completos de um pedido para a Fase 2 (Mock)
     */
    private function mockOrderData($orderId)
    {
        return [
            'id' => $orderId,
            'status' => 'placed',
            'date_created' => date('Y-m-d H:i:s'),
            'total_amount' => 50.00,
            'buyer' => [
                'nickname' => 'Cliente iFood',
                'email' => 'cliente@ifood.com.br',
                'phone' => ['area_code' => '11', 'number' => '999999999'],
                'identification' => ['number' => '00000000000']
            ],
            'shipping' => [
                'cost' => 5.00,
                'status' => 'pending',
                'receiver_address' => [
                    'street_name' => 'Rua do iFood',
                    'street_number' => '123',
                    'city' => ['name' => 'São Paulo'],
                    'state' => ['id' => 'SP'],
                    'zip_code' => '01000000'
                ]
            ],
            'order_items' => [
                [
                    'item' => ['id' => 'IF-PROD-001', 'title' => 'Hambúrguer Gourmet', 'seller_sku' => 'SKU-001'],
                    'quantity' => 1,
                    'unit_price' => 45.00
                ]
            ]
        ];
    }
}
