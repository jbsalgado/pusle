<?php

namespace app\modules\marketplace\components;

use Yii;
use yii\base\Component;
use app\modules\marketplace\models\MarketplacePedido;
use app\modules\marketplace\models\MarketplacePedidoItem;
use app\modules\marketplace\models\MarketplaceProduto;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\Cliente;

/**
 * Processador de eventos de pedidos dos marketplaces
 * 
 * Responsável por criar/atualizar pedidos no sistema
 */
class OrderEventProcessor extends Component
{
    /**
     * Processa novo pedido recebido do marketplace
     * 
     * @param array $orderData Dados do pedido
     * @param string $marketplace Nome do marketplace
     * @param string $usuarioId ID do usuário
     * @return array Resultado do processamento
     */
    public function processNewOrder($orderData, $marketplace, $usuarioId)
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            // 1. Verificar se pedido já existe
            $existingOrder = MarketplacePedido::findOne([
                'marketplace' => $marketplace,
                'marketplace_pedido_id' => $orderData['id'],
            ]);

            if ($existingOrder) {
                Yii::info("Pedido {$orderData['id']} já existe, atualizando...", __METHOD__);
                return $this->processOrderUpdate($orderData, $marketplace, $usuarioId);
            }

            // 2. Criar pedido
            $pedido = new MarketplacePedido();
            $pedido->usuario_id = $usuarioId;
            $pedido->marketplace = $marketplace;
            $pedido->marketplace_pedido_id = $orderData['id'];

            // Dados do cliente
            $buyer = $orderData['buyer'] ?? [];
            $pedido->cliente_nome = $buyer['nickname'] ?? $buyer['first_name'] ?? 'Cliente Marketplace';
            $pedido->cliente_email = $buyer['email'] ?? null;
            $pedido->cliente_telefone = $buyer['phone']['area_code'] . $buyer['phone']['number'] ?? null;
            $pedido->cliente_documento = $this->extractDocument($buyer);

            // Endereço de entrega
            $shipping = $orderData['shipping'] ?? [];
            $receiver = $shipping['receiver_address'] ?? [];
            $pedido->endereco_completo = $this->formatAddress($receiver);
            $pedido->endereco_cep = $receiver['zip_code'] ?? null;
            $pedido->endereco_cidade = $receiver['city']['name'] ?? null;
            $pedido->endereco_estado = $receiver['state']['id'] ?? null;

            // Valores
            $pedido->valor_total = $orderData['total_amount'] ?? 0;
            $pedido->valor_frete = $shipping['cost'] ?? 0;
            $pedido->valor_desconto = $orderData['coupon_amount'] ?? 0;
            $pedido->valor_produtos = ($pedido->valor_total - $pedido->valor_frete + $pedido->valor_desconto);

            // Status
            $pedido->status = $orderData['status'] ?? 'unknown';
            $pedido->status_pagamento = $orderData['payments'][0]['status'] ?? 'unknown';
            $pedido->status_envio = $shipping['status'] ?? 'unknown';

            // Rastreamento
            $pedido->codigo_rastreio = $shipping['tracking_number'] ?? null;
            $pedido->transportadora = $shipping['logistic_type'] ?? null;

            // Datas
            $pedido->data_pedido = $orderData['date_created'] ?? date('Y-m-d H:i:s');
            $pedido->data_entrega_prevista = $shipping['estimated_delivery'] ?? null;

            // Dados completos
            $pedido->dados_completos = $orderData;
            $pedido->importado = false;

            if (!$pedido->save()) {
                throw new \Exception('Erro ao salvar pedido: ' . json_encode($pedido->errors));
            }

            // 3. Criar itens do pedido
            $items = $orderData['order_items'] ?? [];
            foreach ($items as $itemData) {
                $item = new MarketplacePedidoItem();
                $item->pedido_id = $pedido->id;
                $item->marketplace_produto_id = $itemData['item']['id'] ?? null;
                $item->titulo = $itemData['item']['title'] ?? 'Produto sem título';
                $item->quantidade = $itemData['quantity'] ?? 1;
                $item->preco_unitario = $itemData['unit_price'] ?? 0;
                $item->preco_total = $itemData['full_unit_price'] ?? ($item->quantidade * $item->preco_unitario);
                $item->sku = $itemData['item']['seller_sku'] ?? null;
                $item->variacao = $itemData['item']['variation_attributes'] ?? null;
                $item->dados_completos = $itemData;

                // Tentar vincular com produto local
                if ($item->marketplace_produto_id) {
                    $marketplaceProduto = MarketplaceProduto::findOne([
                        'marketplace' => $marketplace,
                        'marketplace_produto_id' => $item->marketplace_produto_id,
                    ]);

                    if ($marketplaceProduto) {
                        $item->produto_id = $marketplaceProduto->produto_id;
                    }
                }

                if (!$item->save()) {
                    throw new \Exception('Erro ao salvar item: ' . json_encode($item->errors));
                }
            }

            $transaction->commit();

            Yii::info("Pedido {$pedido->marketplace_pedido_id} criado com sucesso", __METHOD__);

            return [
                'action' => 'created',
                'pedido_id' => $pedido->id,
                'marketplace_pedido_id' => $pedido->marketplace_pedido_id,
                'valor_total' => $pedido->valor_total,
                'itens_count' => count($items),
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("Erro ao processar novo pedido: " . $e->getMessage(), __METHOD__);
            throw $e;
        }
    }

    /**
     * Processa atualização de pedido existente
     * 
     * @param array $orderData Dados do pedido
     * @param string $marketplace Nome do marketplace
     * @param string $usuarioId ID do usuário
     * @return array Resultado do processamento
     */
    public function processOrderUpdate($orderData, $marketplace, $usuarioId)
    {
        $pedido = MarketplacePedido::findOne([
            'marketplace' => $marketplace,
            'marketplace_pedido_id' => $orderData['id'],
        ]);

        if (!$pedido) {
            Yii::warning("Pedido {$orderData['id']} não encontrado para atualização", __METHOD__);
            return $this->processNewOrder($orderData, $marketplace, $usuarioId);
        }

        // Atualizar status
        $pedido->status = $orderData['status'] ?? $pedido->status;
        $pedido->status_pagamento = $orderData['payments'][0]['status'] ?? $pedido->status_pagamento;

        $shipping = $orderData['shipping'] ?? [];
        $pedido->status_envio = $shipping['status'] ?? $pedido->status_envio;
        $pedido->codigo_rastreio = $shipping['tracking_number'] ?? $pedido->codigo_rastreio;

        $pedido->dados_completos = $orderData;
        $pedido->save();

        Yii::info("Pedido {$pedido->marketplace_pedido_id} atualizado", __METHOD__);

        return [
            'action' => 'updated',
            'pedido_id' => $pedido->id,
            'marketplace_pedido_id' => $pedido->marketplace_pedido_id,
            'status' => $pedido->status,
        ];
    }

    /**
     * Processa cancelamento de pedido
     * 
     * @param string $orderId ID do pedido no marketplace
     * @param string $marketplace Nome do marketplace
     * @return array Resultado do processamento
     */
    public function processOrderCancellation($orderId, $marketplace)
    {
        $pedido = MarketplacePedido::findOne([
            'marketplace' => $marketplace,
            'marketplace_pedido_id' => $orderId,
        ]);

        if (!$pedido) {
            Yii::warning("Pedido {$orderId} não encontrado para cancelamento", __METHOD__);
            return ['action' => 'not_found'];
        }

        $pedido->status = 'cancelled';
        $pedido->save();

        Yii::info("Pedido {$pedido->marketplace_pedido_id} cancelado", __METHOD__);

        return [
            'action' => 'cancelled',
            'pedido_id' => $pedido->id,
            'marketplace_pedido_id' => $pedido->marketplace_pedido_id,
        ];
    }

    /**
     * Extrai documento (CPF/CNPJ) dos dados do comprador
     * 
     * @param array $buyer Dados do comprador
     * @return string|null
     */
    protected function extractDocument($buyer)
    {
        if (isset($buyer['billing_info']['doc_number'])) {
            return $buyer['billing_info']['doc_number'];
        }

        if (isset($buyer['identification']['number'])) {
            return $buyer['identification']['number'];
        }

        return null;
    }

    /**
     * Formata endereço completo
     * 
     * @param array $address Dados do endereço
     * @return string
     */
    protected function formatAddress($address)
    {
        $parts = [];

        if (!empty($address['street_name'])) {
            $street = $address['street_name'];
            if (!empty($address['street_number'])) {
                $street .= ', ' . $address['street_number'];
            }
            $parts[] = $street;
        }

        if (!empty($address['comment'])) {
            $parts[] = $address['comment'];
        }

        if (!empty($address['neighborhood']['name'])) {
            $parts[] = $address['neighborhood']['name'];
        }

        if (!empty($address['city']['name'])) {
            $parts[] = $address['city']['name'];
        }

        if (!empty($address['state']['name'])) {
            $parts[] = $address['state']['name'];
        }

        if (!empty($address['zip_code'])) {
            $parts[] = 'CEP: ' . $address['zip_code'];
        }

        return implode(' - ', $parts);
    }
}
