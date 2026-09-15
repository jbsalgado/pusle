<?php

namespace app\modules\marketplace\components;

use Yii;

/**
 * Handler de webhooks do Mercado Livre
 * 
 * Processa notificações do Mercado Livre sobre:
 * - Pedidos (orders)
 * - Produtos (items)
 * - Perguntas (questions)
 * - Mensagens (messages)
 */
class MercadoLivreWebhookHandler extends BaseWebhookHandler
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
     * Valida assinatura do webhook do Mercado Livre
     * 
     * @param string $rawBody Corpo bruto
     * @param array $headers Headers
     * @return bool
     */
    protected function validateSignature($rawBody, $headers)
    {
        $signature = $headers['x-signature'] ?? $headers['X-Signature'] ?? null;

        if (!$signature) {
            Yii::warning('Header X-Signature não encontrado', __METHOD__);
            return false;
        }

        $secret = $this->config['client_secret'] ?? null;

        if (!$secret) {
            Yii::error('Client Secret não configurado para Mercado Livre', __METHOD__);
            return false;
        }

        $validator = new WebhookSignatureValidator();
        return $validator->validateMercadoLivre($signature, $rawBody, $secret);
    }

    /**
     * Identifica o tipo de evento do webhook
     * 
     * @param array $payload Payload decodificado
     * @return string|null
     */
    protected function getEventType($payload)
    {
        // Mercado Livre envia o tipo no campo 'topic'
        return $payload['topic'] ?? null;
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
            case 'orders_v2':
            case 'orders':
                return $this->processOrderEvent($payload);

            case 'items':
                return $this->processItemEvent($payload);

            case 'questions':
                return $this->processQuestionEvent($payload);

            case 'messages':
                return $this->processMessageEvent($payload);

            case 'shipments':
                return $this->processShipmentEvent($payload);

            case 'invoices':
                return $this->processInvoiceEvent($payload);

            case 'payments':
                Yii::info("Evento de {$eventType} recebido: " . json_encode($payload), __METHOD__);
                return [
                    'processed' => true,
                    'action' => 'logged',
                    'event_type' => $eventType,
                    'resource' => $payload['resource'] ?? null,
                ];

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
     * Processa evento de pedido
     * 
     * @param array $payload Dados do evento
     * @return array Resultado
     */
    protected function processOrderEvent($payload)
    {
        // Mercado Livre envia apenas a notificação, precisamos buscar os dados completos
        $resource = $payload['resource'] ?? null;
        $userId = $payload['user_id'] ?? null;

        if (!$resource) {
            throw new \Exception('Resource não encontrado no payload');
        }

        // Buscar dados completos do pedido via API
        $orderData = $this->fetchOrderData($resource);

        if (!$orderData) {
            throw new \Exception('Não foi possível buscar dados do pedido');
        }

        // Processar pedido
        $result = $this->orderProcessor->processNewOrder(
            $orderData,
            'MERCADO_LIVRE',
            $this->config['usuario_id']
        );

        return $result;
    }

    /**
     * Processa evento de produto
     * 
     * @param array $payload Dados do evento
     * @return array Resultado
     */
    protected function processItemEvent($payload)
    {
        // TODO: Implementar processamento de eventos de produtos
        // Por enquanto, apenas registra
        Yii::info('Evento de produto recebido: ' . json_encode($payload), __METHOD__);

        return [
            'processed' => true,
            'action' => 'logged',
            'resource' => $payload['resource'] ?? null,
        ];
    }

    /**
     * Processa evento de pergunta
     * 
     * @param array $payload Dados do evento
     * @return array Resultado
     */
    protected function processQuestionEvent($payload)
    {
        // TODO: Implementar processamento de perguntas
        // Por enquanto, apenas registra
        Yii::info('Evento de pergunta recebido: ' . json_encode($payload), __METHOD__);

        return [
            'processed' => true,
            'action' => 'logged',
            'resource' => $payload['resource'] ?? null,
        ];
    }

    /**
     * Processa evento de mensagem
     * 
     * @param array $payload Dados do evento
     * @return array Resultado
     */
    protected function processMessageEvent($payload)
    {
        // TODO: Implementar processamento de mensagens
        // Por enquanto, apenas registra
        Yii::info('Evento de mensagem recebido: ' . json_encode($payload), __METHOD__);

        return [
            'processed' => true,
            'action' => 'logged',
            'resource' => $payload['resource'] ?? null,
        ];
    }

    /**
     * Processa evento de envio (Mercado Envios)
     * 
     * @param array $payload Dados do evento
     * @return array Resultado
     */
    protected function processShipmentEvent($payload)
    {
        $resource = $payload['resource'] ?? null;
        if (!$resource) {
            return ['processed' => false, 'reason' => 'Resource vazio'];
        }

        $shipmentData = $this->fetchOrderData($resource);
        if (!$shipmentData) {
            return ['processed' => false, 'reason' => 'Falha ao buscar dados do envio'];
        }

        $orderId = (string)($shipmentData['order_id'] ?? '');
        $statusEnvio = (string)($shipmentData['status'] ?? '');
        $substatus = (string)($shipmentData['substatus'] ?? '');
        $trackingNumber = (string)($shipmentData['tracking_number'] ?? '');
        $carrier = (string)($shipmentData['tracking_method'] ?? 'Mercado Envios');

        if ($orderId) {
            $pedido = \app\modules\marketplace\models\MarketplacePedido::findOne([
                'marketplace' => 'MERCADO_LIVRE',
                'marketplace_pedido_id' => $orderId,
            ]);

            if ($pedido) {
                $pedido->status_envio = $statusEnvio;
                if ($trackingNumber) {
                    $pedido->codigo_rastreio = $trackingNumber;
                }
                if ($carrier) {
                    $pedido->transportadora = $carrier;
                }
                $pedido->save(false);

                Yii::info("[MercadoLivreWebhookHandler] Pedido {$orderId} atualizado com status de envio [{$statusEnvio}]. Rastreio: {$trackingNumber}", __METHOD__);
            }
        }

        return [
            'processed' => true,
            'action' => 'shipment_updated',
            'order_id' => $orderId,
            'status' => $statusEnvio,
        ];
    }

    /**
     * Processa evento de faturamento nativo (Cenário A - Faturador do Mercado Livre)
     * 
     * @param array $payload Dados do evento
     * @return array Resultado
     */
    protected function processInvoiceEvent($payload)
    {
        $resource = $payload['resource'] ?? null;
        if (!$resource) {
            return ['processed' => false, 'reason' => 'Resource vazio'];
        }

        $invoiceData = $this->fetchOrderData($resource);
        if (!$invoiceData) {
            return ['processed' => false, 'reason' => 'Falha ao buscar dados da nota fiscal'];
        }

        $orderId = (string)($invoiceData['order_id'] ?? ($payload['user_id'] ?? ''));
        $chaveAcesso = (string)($invoiceData['fiscal_key'] ?? ($invoiceData['key'] ?? ''));

        if ($orderId && $chaveAcesso) {
            $pedido = \app\modules\marketplace\models\MarketplacePedido::findOne([
                'marketplace' => 'MERCADO_LIVRE',
                'marketplace_pedido_id' => $orderId,
            ]);

            if ($pedido && $pedido->venda_id) {
                $nota = \app\modules\vendas\models\NotaFiscal::findOne(['venda_id' => $pedido->venda_id]);
                if (!$nota) {
                    $nota = new \app\modules\vendas\models\NotaFiscal();
                    $nota->usuario_id = $pedido->usuario_id;
                    $nota->venda_id = $pedido->venda_id;
                    $nota->marketplace = 'MERCADO_LIVRE';
                    $nota->marketplace_pedido_id = $orderId;
                    $nota->modelo = '55';
                    $nota->numero = (int)($invoiceData['number'] ?? 0);
                    $nota->serie = (int)($invoiceData['series'] ?? 1);
                }

                $nota->chave_acesso = $chaveAcesso;
                $nota->status_sefaz = \app\modules\vendas\models\NotaFiscal::STATUS_AUTORIZADA;
                $nota->xmotivo = 'Autorizada via Faturador Nativo do Mercado Livre (Cenário A)';
                $nota->save(false);

                Yii::info("[MercadoLivreWebhookHandler] NF-e nativa do ML vinculada à venda {$pedido->venda_id}. Chave: {$chaveAcesso}", __METHOD__);
            }
        }

        return [
            'processed' => true,
            'action' => 'invoice_registered',
            'order_id' => $orderId,
        ];
    }

    /**
     * Busca dados completos do pedido via API do Mercado Livre
     * 
     * @param string $resource URL do recurso (ex: /orders/123456)
     * @return array|null Dados do pedido
     */
    protected function fetchOrderData($resource)
    {
        $accessToken = $this->config['access_token'] ?? null;

        if (!$accessToken) {
            Yii::error('Access Token não configurado para Mercado Livre', __METHOD__);
            return null;
        }

        // URL base da API do Mercado Livre
        $apiUrl = 'https://api.mercadolibre.com' . $resource;

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get($apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody()->getContents(), true);
            }

            Yii::error('Erro ao buscar pedido: HTTP ' . $response->getStatusCode(), __METHOD__);
            return null;
        } catch (\Exception $e) {
            Yii::error('Exceção ao buscar pedido: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }
}
