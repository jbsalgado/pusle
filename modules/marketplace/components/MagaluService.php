<?php

namespace app\modules\marketplace\components;

use Yii;
use app\modules\marketplace\models\MarketplaceConfig;
use app\modules\marketplace\models\MarketplaceProduto;
use app\modules\marketplace\dto\MarketplaceOrderDTO;
use app\modules\marketplace\dto\MarketplaceOrderItemDTO;
use app\modules\vendas\models\Produto;

/**
 * MagaluService - Conector de Integração com a API do Magazine Luiza (Magalu Marketplace / LuizaLabs)
 */
class MagaluService extends MarketplaceService
{
    protected $marketplaceName = 'MAGAZINE_LUIZA';
    protected string $apiBaseUrl = 'https://api.magazineluiza.com.br/v1';

    public function authenticate(): bool
    {
        return !empty($this->config['access_token']) || !empty($this->config['client_secret']);
    }

    public function refreshToken(): bool
    {
        return true;
    }

    /**
     * Sincroniza estoque no Magalu
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
                $url = "{$this->apiBaseUrl}/products/{$sku}/stock";

                $this->request('PUT', $url, [
                    'headers' => $this->getAuthHeaders(),
                    'json' => [
                        'quantity' => max(0, (int)$quantidade),
                    ],
                ]);

                $vinculo->estoque_marketplace = (int)$quantidade;
                $vinculo->ultima_sync = new \yii\db\Expression('NOW()');
                $vinculo->erro_sync = null;
                $vinculo->save(false);

                Yii::info("[MagaluService] Estoque atualizado no SKU {$sku} ({$quantidade} un).", 'marketplace');
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
     * Sincroniza preço no Magalu aplicando markup
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
                $url = "{$this->apiBaseUrl}/products/{$sku}/price";

                $this->request('PUT', $url, [
                    'headers' => $this->getAuthHeaders(),
                    'json' => [
                        'price' => $precoFinal,
                        'list_price' => $precoFinal,
                    ],
                ]);

                $vinculo->preco_marketplace = $precoFinal;
                $vinculo->ultima_sync = new \yii\db\Expression('NOW()');
                $vinculo->save(false);

                Yii::info("[MagaluService] Preço atualizado no SKU {$sku} para R$ {$precoFinal}.", 'marketplace');
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
     * Importa pedidos pendentes de faturamento do Magalu
     */
    public function importPedidos($dataInicio = null, $dataFim = null): array
    {
        $url = "{$this->apiBaseUrl}/orders/status/approved";

        try {
            $response = $this->request('GET', $url, [
                'headers' => $this->getAuthHeaders(),
            ]);

            $orders = $response['orders'] ?? $response ?? [];
            if (!is_array($orders)) {
                return [];
            }

            $processador = new OrderEventProcessor();
            $pedidosProcessados = [];

            foreach ($orders as $orderData) {
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
     * Envia faturamento / NF-e para o Magalu
     */
    public function uploadNfe(string $orderId, string $chaveAcesso, ?string $xml = null): bool
    {
        $url = "{$this->apiBaseUrl}/orders/{$orderId}/invoice";

        try {
            $this->request('POST', $url, [
                'headers' => $this->getAuthHeaders(),
                'json' => [
                    'key' => preg_replace('/\D/', '', $chaveAcesso),
                    'xml' => $xml ? base64_encode($xml) : null,
                ],
            ]);

            Yii::info("[MagaluService] NF-e vinculada com sucesso ao pedido {$orderId}.", 'marketplace');
            return true;
        } catch (\Throwable $e) {
            $this->handleError($e, "uploadNfe ({$orderId})");
            return false;
        }
    }

    /**
     * Normaliza pedido do Magalu para o DTO canônico
     */
    public function normalizeOrderToDTO(array $orderData): MarketplaceOrderDTO
    {
        $dto = new MarketplaceOrderDTO();
        $dto->marketplace = $this->marketplaceName;
        $dto->usuarioId = $this->usuarioId;
        $dto->marketplaceOrderId = (string)($orderData['id'] ?? $orderData['code'] ?? uniqid('magalu_'));
        $dto->status = strtolower($orderData['status'] ?? 'approved');
        $dto->totalAmount = (float)($orderData['total_amount'] ?? 0);
        $dto->shippingAmount = (float)($orderData['shipping_amount'] ?? 0);
        $dto->dateCreated = date('Y-m-d H:i:s', strtotime($orderData['created_at'] ?? 'now'));
        $dto->rawPayload = $orderData;

        // Comprador
        $buyer = $orderData['customer'] ?? [];
        $dto->buyerName = $buyer['name'] ?? 'Cliente Magazine Luiza';
        $dto->buyerEmail = $buyer['email'] ?? null;
        $dto->buyerDocument = $buyer['document_number'] ?? $buyer['cpf'] ?? null;
        $dto->buyerPhone = $buyer['phone'] ?? null;

        // Endereço
        $addr = $orderData['shipping_address'] ?? [];
        $dto->shippingStreet = $addr['street'] ?? null;
        $dto->shippingNumber = $addr['number'] ?? null;
        $dto->shippingComplement = $addr['complement'] ?? null;
        $dto->shippingNeighborhood = $addr['neighborhood'] ?? null;
        $dto->shippingCity = $addr['city'] ?? null;
        $dto->shippingState = $addr['state'] ?? null;
        $dto->shippingZipCode = $addr['zip_code'] ?? null;

        // Itens
        $items = $orderData['items'] ?? [];
        foreach ($items as $itemData) {
            $itemDTO = new MarketplaceOrderItemDTO();
            $itemDTO->marketplaceItemId = (string)($itemData['sku'] ?? $itemData['product_id'] ?? '');
            $itemDTO->title = (string)($itemData['name'] ?? 'Produto Magalu');
            $itemDTO->quantity = (float)($itemData['quantity'] ?? 1);
            $itemDTO->unitPrice = (float)($itemData['price'] ?? 0);
            $itemDTO->totalPrice = $itemDTO->quantity * $itemDTO->unitPrice;
            $itemDTO->sellerSku = (string)($itemData['sku'] ?? '');
            $itemDTO->rawItemData = $itemData;
            $dto->items[] = $itemDTO;
        }

        $dto->productsAmount = array_sum(array_map(fn($i) => $i->totalPrice, $dto->items));

        return $dto;
    }

    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . ($this->config['access_token'] ?? $this->config['client_secret'] ?? ''),
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
