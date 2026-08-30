<?php

namespace app\modules\marketplace\components;

use Yii;
use app\modules\marketplace\models\MarketplaceConfig;
use app\modules\marketplace\models\MarketplaceProduto;
use app\modules\marketplace\dto\MarketplaceOrderDTO;
use app\modules\marketplace\dto\MarketplaceOrderItemDTO;
use app\modules\vendas\models\Produto;

/**
 * TemuService - Conector de Integração com a Temu Open Platform (Local-to-Local Brasil)
 */
class TemuService extends MarketplaceService
{
    protected $marketplaceName = 'TEMU';
    protected string $apiBaseUrl = 'https://open-api.temu.com';

    public function authenticate(): bool
    {
        return !empty($this->config['access_token']) || !empty($this->config['client_id']);
    }

    public function refreshToken(): bool
    {
        return true;
    }

    /**
     * Sincroniza estoque de produto na Temu (L2L)
     */
    public function syncEstoque($produtoId, $quantidade): bool
    {
        $vinculos = MarketplaceProduto::find()
            ->where([
                'marketplace' => $this->marketplaceName,
                'produto_id' => $produtoId,
                'usuario_id' => $this->usuarioId,
            ])
            ->all();

        if (empty($vinculos)) {
            return true;
        }

        $todosSucesso = true;

        foreach ($vinculos as $vinculo) {
            try {
                $sku = $vinculo->sku_marketplace ?: $vinculo->marketplace_produto_id;
                $url = "{$this->apiBaseUrl}/bg/goods/local/inventory/update";

                $this->request('POST', $url, [
                    'headers' => $this->getAuthHeaders(),
                    'json' => [
                        'sku_id' => $sku,
                        'available_quantity' => max(0, (int)$quantidade),
                    ],
                ]);

                $vinculo->estoque_marketplace = (int)$quantidade;
                $vinculo->ultima_sync = new \yii\db\Expression('NOW()');
                $vinculo->erro_sync = null;
                $vinculo->save(false);

                Yii::info("[TemuService] Estoque atualizado na Temu SKU {$sku} ({$quantidade} un).", 'marketplace');
            } catch (\Throwable $e) {
                $todosSucesso = false;
                $vinculo->erro_sync = $e->getMessage();
                $vinculo->save(false);
                $this->handleError($e, "syncEstoque ({$vinculo->marketplace_produto_id})");
            }
        }

        return $todosSucesso;
    }

    /**
     * Sincroniza preço na Temu aplicando markup
     */
    public function syncPreco($produtoId, $novoPreco = null): bool
    {
        $vinculos = MarketplaceProduto::find()
            ->where([
                'marketplace' => $this->marketplaceName,
                'produto_id' => $produtoId,
                'usuario_id' => $this->usuarioId,
            ])
            ->all();

        if (empty($vinculos)) {
            return true;
        }

        $todosSucesso = true;

        foreach ($vinculos as $vinculo) {
            try {
                $sku = $vinculo->sku_marketplace ?: $vinculo->marketplace_produto_id;
                $precoFinal = $novoPreco ? (float)$novoPreco : $vinculo->getPrecoFinal();
                $url = "{$this->apiBaseUrl}/bg/goods/local/price/update";

                $this->request('POST', $url, [
                    'headers' => $this->getAuthHeaders(),
                    'json' => [
                        'sku_id' => $sku,
                        'price' => $precoFinal,
                    ],
                ]);

                $vinculo->preco_marketplace = $precoFinal;
                $vinculo->ultima_sync = new \yii\db\Expression('NOW()');
                $vinculo->save(false);

                Yii::info("[TemuService] Preço atualizado na Temu SKU {$sku} para R$ {$precoFinal}.", 'marketplace');
            } catch (\Throwable $e) {
                $todosSucesso = false;
                $vinculo->erro_sync = $e->getMessage();
                $vinculo->save(false);
                $this->handleError($e, "syncPreco ({$vinculo->marketplace_produto_id})");
            }
        }

        return $todosSucesso;
    }

    /**
     * Importa pedidos locais da Temu
     */
    public function importPedidos($dataInicio = null, $dataFim = null): array
    {
        $url = "{$this->apiBaseUrl}/bg/order/local/list";

        try {
            $response = $this->request('POST', $url, [
                'headers' => $this->getAuthHeaders(),
                'json' => [
                    'status' => 'PENDING_SHIPMENT',
                    'page_size' => 50,
                ],
            ]);

            $orderList = $response['data']['order_list'] ?? [];
            if (empty($orderList)) {
                return [];
            }

            $processador = new OrderEventProcessor();
            $pedidosProcessados = [];

            foreach ($orderList as $orderData) {
                $dto = $this->normalizeOrderToDTO($orderData);
                $pedidosProcessados[] = $processador->processOrder($dto);
            }

            return $pedidosProcessados;
        } catch (\Throwable $e) {
            $this->handleError($e, 'importPedidos');
            return [];
        }
    }

    /**
     * Normaliza pedido da Temu para o DTO canônico
     */
    public function normalizeOrderToDTO(array $orderData): MarketplaceOrderDTO
    {
        $dto = new MarketplaceOrderDTO();
        $dto->marketplace = $this->marketplaceName;
        $dto->usuarioId = $this->usuarioId;
        $dto->marketplaceOrderId = (string)($orderData['order_sn'] ?? $orderData['parent_order_sn'] ?? uniqid('temu_'));
        $dto->status = strtolower($orderData['order_status'] ?? 'pending_shipment');
        $dto->totalAmount = (float)($orderData['order_amount'] ?? 0);
        $dto->dateCreated = date('Y-m-d H:i:s', (int)($orderData['created_time'] ?? time()));
        $dto->rawPayload = $orderData;

        // Comprador & Endereço
        $addr = $orderData['shipping_info'] ?? [];
        $dto->buyerName = $addr['receiver_name'] ?? 'Cliente Temu';
        $dto->buyerPhone = $addr['receiver_phone'] ?? null;
        $dto->shippingStreet = $addr['street'] ?? null;
        $dto->shippingCity = $addr['city'] ?? null;
        $dto->shippingState = $addr['state'] ?? null;
        $dto->shippingZipCode = $addr['zip_code'] ?? null;

        // Itens
        $items = $orderData['order_goods_list'] ?? [];
        foreach ($items as $itemData) {
            $itemDTO = new MarketplaceOrderItemDTO();
            $itemDTO->marketplaceItemId = (string)($itemData['goods_id'] ?? '');
            $itemDTO->marketplaceVariationId = (string)($itemData['sku_id'] ?? '');
            $itemDTO->title = (string)($itemData['goods_name'] ?? 'Produto Temu');
            $itemDTO->quantity = (float)($itemData['goods_quantity'] ?? 1);
            $itemDTO->unitPrice = (float)($itemData['goods_price'] ?? 0);
            $itemDTO->totalPrice = $itemDTO->quantity * $itemDTO->unitPrice;
            $itemDTO->sellerSku = (string)($itemData['out_sku_sn'] ?? '');
            $itemDTO->rawItemData = $itemData;
            $dto->items[] = $itemDTO;
        }

        $dto->productsAmount = array_sum(array_map(fn($i) => $i->totalPrice, $dto->items));

        return $dto;
    }

    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . ($this->config['access_token'] ?? ''),
            'app-key' => $this->config['client_id'] ?? '',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function syncProdutos($produtoIds = [])
    {
        return ['success' => true];
    }

    public function updatePedidoStatus($pedidoId, $status, $dados = [])
    {
        return true;
    }

    public function processWebhook($payload)
    {
        return true;
    }
}
