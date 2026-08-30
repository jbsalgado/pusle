<?php

namespace app\modules\marketplace\components;

use Yii;
use app\modules\marketplace\models\MarketplaceConfig;
use app\modules\marketplace\models\MarketplaceProduto;
use app\modules\marketplace\dto\MarketplaceOrderDTO;
use app\modules\marketplace\dto\MarketplaceOrderItemDTO;
use app\modules\vendas\models\Produto;

/**
 * ShopeeService - Conector de Integração Oficial com a Shopee Open Platform API v2
 */
class ShopeeService extends MarketplaceService
{
    protected $marketplaceName = 'SHOPEE';
    protected string $apiBaseUrl = 'https://partner.shopeemobile.com';

    /**
     * Gera assinatura HMAC-SHA256 padrão da API v2 da Shopee
     */
    public function generateSignature(string $path, int $timestamp, ?string $accessToken = null, ?string $shopId = null): string
    {
        $partnerId = (int)($this->config['client_id'] ?? 0);
        $partnerKey = (string)($this->config['client_secret'] ?? '');

        $baseString = $partnerId . $path . $timestamp;
        if ($accessToken !== null) {
            $baseString .= $accessToken;
        }
        if ($shopId !== null) {
            $baseString .= $shopId;
        }

        return hash_hmac('sha256', $baseString, $partnerKey);
    }

    /**
     * Autentica ou troca o Authorization Code pelo Access Token da Shopee
     * 
     * @param string $authCode Código retornado no callback OAuth da Shopee
     * @param string $shopId ID da loja na Shopee
     * @return bool
     */
    public function authenticate(?string $authCode = null, ?string $shopId = null): bool
    {
        if (!$authCode || !$shopId) {
            return false;
        }

        $path = '/api/v2/auth/token/get';
        $timestamp = time();
        $sign = $this->generateSignature($path, $timestamp);

        try {
            $url = "{$this->apiBaseUrl}{$path}?partner_id={$this->config['client_id']}&timestamp={$timestamp}&sign={$sign}";
            $response = $this->request('POST', $url, [
                'json' => [
                    'code' => $authCode,
                    'shop_id' => (int)$shopId,
                    'partner_id' => (int)$this->config['client_id'],
                ],
            ]);

            if (!empty($response['access_token'])) {
                $this->salvarTokens($response, (string)$shopId);
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            $this->handleError($e, 'authenticate');
            return false;
        }
    }

    /**
     * Renova o Access Token expirado da Shopee
     * 
     * @return bool
     */
    public function refreshToken(): bool
    {
        if (empty($this->config['refresh_token']) || empty($this->config['seller_id_externo'])) {
            Yii::error("[ShopeeService] Refresh Token ou Shop ID não configurados.", 'marketplace');
            return false;
        }

        $path = '/api/v2/auth/access_token/get';
        $timestamp = time();
        $shopId = (int)$this->config['seller_id_externo'];
        $sign = $this->generateSignature($path, $timestamp);

        try {
            $url = "{$this->apiBaseUrl}{$path}?partner_id={$this->config['client_id']}&timestamp={$timestamp}&sign={$sign}";
            $response = $this->request('POST', $url, [
                'json' => [
                    'refresh_token' => $this->config['refresh_token'],
                    'shop_id' => $shopId,
                    'partner_id' => (int)$this->config['client_id'],
                ],
            ]);

            if (!empty($response['access_token'])) {
                $this->salvarTokens($response, (string)$shopId);
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            $this->handleError($e, 'refreshToken');
            return false;
        }
    }

    /**
     * Sincroniza estoque de produto na Shopee
     * 
     * @param string $produtoId UUID do produto no ERP Pulse
     * @param int $quantidade Quantidade disponível
     * @return bool
     */
    public function syncEstoque($produtoId, $quantidade): bool
    {
        $this->garantirTokenValido();

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

        $path = '/api/v2/product/update_stock';
        $timestamp = time();
        $shopId = (string)($this->config['seller_id_externo'] ?? '');
        $accessToken = (string)($this->config['access_token'] ?? '');
        $sign = $this->generateSignature($path, $timestamp, $accessToken, $shopId);

        $url = "{$this->apiBaseUrl}{$path}?partner_id={$this->config['client_id']}&timestamp={$timestamp}&access_token={$accessToken}&shop_id={$shopId}&sign={$sign}";

        $todosSucesso = true;

        foreach ($vinculos as $vinculo) {
            try {
                $itemId = (int)$vinculo->marketplace_produto_id;
                $stockList = [];

                if (!empty($vinculo->marketplace_variacao_id)) {
                    $stockList[] = [
                        'model_id' => (int)$vinculo->marketplace_variacao_id,
                        'normal_stock' => max(0, (int)$quantidade),
                    ];
                } else {
                    $stockList[] = [
                        'model_id' => 0,
                        'normal_stock' => max(0, (int)$quantidade),
                    ];
                }

                $this->request('POST', $url, [
                    'json' => [
                        'item_id' => $itemId,
                        'stock_list' => $stockList,
                    ],
                ]);

                $vinculo->estoque_marketplace = (int)$quantidade;
                $vinculo->ultima_sync = new \yii\db\Expression('NOW()');
                $vinculo->erro_sync = null;
                $vinculo->save(false);

                Yii::info("[ShopeeService] Estoque atualizado no anúncio {$itemId} ({$quantidade} un).", 'marketplace');
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
     * Sincroniza preço de produto na Shopee com base no markup configurado
     */
    public function syncPreco($produtoId, $novoPreco = null): bool
    {
        $this->garantirTokenValido();

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

        $path = '/api/v2/product/update_price';
        $timestamp = time();
        $shopId = (string)($this->config['seller_id_externo'] ?? '');
        $accessToken = (string)($this->config['access_token'] ?? '');
        $sign = $this->generateSignature($path, $timestamp, $accessToken, $shopId);

        $url = "{$this->apiBaseUrl}{$path}?partner_id={$this->config['client_id']}&timestamp={$timestamp}&access_token={$accessToken}&shop_id={$shopId}&sign={$sign}";

        $todosSucesso = true;

        foreach ($vinculos as $vinculo) {
            try {
                $itemId = (int)$vinculo->marketplace_produto_id;
                $precoFinal = $novoPreco ? (float)$novoPreco : $vinculo->getPrecoFinal();

                $priceList = [
                    [
                        'original_price' => $precoFinal,
                    ]
                ];

                if (!empty($vinculo->marketplace_variacao_id)) {
                    $priceList[0]['model_id'] = (int)$vinculo->marketplace_variacao_id;
                }

                $this->request('POST', $url, [
                    'json' => [
                        'item_id' => $itemId,
                        'price_list' => $priceList,
                    ],
                ]);

                $vinculo->preco_marketplace = $precoFinal;
                $vinculo->ultima_sync = new \yii\db\Expression('NOW()');
                $vinculo->save(false);

                Yii::info("[ShopeeService] Preço atualizado no item {$itemId} para R$ {$precoFinal}.", 'marketplace');
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
     * Importa pedidos da Shopee
     */
    public function importPedidos($dataInicio = null, $dataFim = null): array
    {
        $this->garantirTokenValido();

        $path = '/api/v2/order/get_order_list';
        $timestamp = time();
        $shopId = (string)($this->config['seller_id_externo'] ?? '');
        $accessToken = (string)($this->config['access_token'] ?? '');
        $sign = $this->generateSignature($path, $timestamp, $accessToken, $shopId);

        $timeFrom = $dataInicio ? strtotime($dataInicio) : strtotime('-15 days');
        $timeTo = $dataFim ? strtotime($dataFim) : time();

        $url = "{$this->apiBaseUrl}{$path}?partner_id={$this->config['client_id']}&timestamp={$timestamp}&access_token={$accessToken}&shop_id={$shopId}&sign={$sign}&time_range_field=create_time&time_from={$timeFrom}&time_to={$timeTo}&page_size=50";

        try {
            $response = $this->request('GET', $url);
            $orderList = $response['response']['order_list'] ?? [];

            if (empty($orderList)) {
                return [];
            }

            $orderSnList = array_column($orderList, 'order_sn');
            $detalhes = $this->getOrderDetails($orderSnList);

            $processador = new OrderEventProcessor();
            $pedidosProcessados = [];

            foreach ($detalhes as $orderData) {
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
     * Obtém detalhes de múltiplos pedidos da Shopee
     */
    protected function getOrderDetails(array $orderSnList): array
    {
        $path = '/api/v2/order/get_order_detail';
        $timestamp = time();
        $shopId = (string)($this->config['seller_id_externo'] ?? '');
        $accessToken = (string)($this->config['access_token'] ?? '');
        $sign = $this->generateSignature($path, $timestamp, $accessToken, $shopId);

        $orderSns = implode(',', array_slice($orderSnList, 0, 50));
        $url = "{$this->apiBaseUrl}{$path}?partner_id={$this->config['client_id']}&timestamp={$timestamp}&access_token={$accessToken}&shop_id={$shopId}&sign={$sign}&order_sn_list={$orderSns}&response_optional_fields=buyer_user_id,buyer_username,recipient_address,item_list,invoice_data";

        $response = $this->request('GET', $url);
        return $response['response']['order_list'] ?? [];
    }

    /**
     * Envia informações de NF-e para a Shopee
     */
    public function uploadNfe(string $orderSn, string $chaveAcesso, float $totalFaturado): bool
    {
        $this->garantirTokenValido();

        $path = '/api/v2/order/set_invoice_info';
        $timestamp = time();
        $shopId = (string)($this->config['seller_id_externo'] ?? '');
        $accessToken = (string)($this->config['access_token'] ?? '');
        $sign = $this->generateSignature($path, $timestamp, $accessToken, $shopId);

        $url = "{$this->apiBaseUrl}{$path}?partner_id={$this->config['client_id']}&timestamp={$timestamp}&access_token={$accessToken}&shop_id={$shopId}&sign={$sign}";

        try {
            $this->request('POST', $url, [
                'json' => [
                    'order_sn' => $orderSn,
                    'invoice_number' => substr(preg_replace('/\D/', '', $chaveAcesso), 25, 9),
                    'access_key' => preg_replace('/\D/', '', $chaveAcesso),
                    'total_value' => $totalFaturado,
                ],
            ]);

            Yii::info("[ShopeeService] NF-e vinculada com sucesso ao pedido Shopee {$orderSn}.", 'marketplace');
            return true;
        } catch (\Throwable $e) {
            $this->handleError($e, "uploadNfe ({$orderSn})");
            return false;
        }
    }

    /**
     * Normaliza pedido da Shopee para o DTO canônico
     */
    public function normalizeOrderToDTO(array $orderData): MarketplaceOrderDTO
    {
        $dto = new MarketplaceOrderDTO();
        $dto->marketplace = $this->marketplaceName;
        $dto->usuarioId = $this->usuarioId;
        $dto->marketplaceOrderId = (string)$orderData['order_sn'];
        $dto->status = strtolower($orderData['order_status'] ?? 'pending');
        $dto->totalAmount = (float)($orderData['total_amount'] ?? 0);
        $dto->dateCreated = date('Y-m-d H:i:s', (int)($orderData['create_time'] ?? time()));
        $dto->rawPayload = $orderData;

        // Comprador
        $dto->buyerName = $orderData['buyer_username'] ?? 'Cliente Shopee';

        // Endereço
        $addr = $orderData['recipient_address'] ?? [];
        $dto->shippingStreet = $addr['full_address'] ?? null;
        $dto->shippingCity = $addr['city'] ?? null;
        $dto->shippingState = $addr['state'] ?? null;
        $dto->shippingZipCode = $addr['zipcode'] ?? null;
        $dto->buyerPhone = $addr['phone'] ?? null;
        $dto->buyerName = $addr['name'] ?? $dto->buyerName;

        // Itens
        $items = $orderData['item_list'] ?? [];
        foreach ($items as $itemData) {
            $itemDTO = new MarketplaceOrderItemDTO();
            $itemDTO->marketplaceItemId = (string)($itemData['item_id'] ?? '');
            $itemDTO->marketplaceVariationId = !empty($itemData['model_id']) ? (string)$itemData['model_id'] : null;
            $itemDTO->title = (string)($itemData['item_name'] ?? 'Produto Shopee');
            $itemDTO->quantity = (float)($itemData['model_quantity_purchased'] ?? 1);
            $itemDTO->unitPrice = (float)($itemData['model_discounted_price'] ?? 0);
            $itemDTO->totalPrice = $itemDTO->quantity * $itemDTO->unitPrice;
            $itemDTO->sellerSku = (string)($itemData['item_sku'] ?? $itemData['model_sku'] ?? '');
            $itemDTO->rawItemData = $itemData;
            $dto->items[] = $itemDTO;
        }

        $dto->productsAmount = array_sum(array_map(fn($i) => $i->totalPrice, $dto->items));

        return $dto;
    }

    /**
     * Garante renovação automática de token
     */
    protected function garantirTokenValido(): void
    {
        if ($this->isTokenExpired()) {
            $this->refreshToken();
        }
    }

    /**
     * Salva novos tokens no banco
     */
    protected function salvarTokens(array $tokenData, string $shopId): void
    {
        $configModel = MarketplaceConfig::findOne([
            'usuario_id' => $this->usuarioId,
            'marketplace' => $this->marketplaceName,
        ]);

        if ($configModel) {
            $configModel->access_token = $tokenData['access_token'];
            $configModel->refresh_token = $tokenData['refresh_token'] ?? $configModel->refresh_token;
            $configModel->seller_id_externo = $shopId;
            if (isset($tokenData['expire_in'])) {
                $expiraEm = new \DateTime();
                $expiraEm->modify("+{$tokenData['expire_in']} seconds");
                $configModel->token_expira_em = $expiraEm->format('Y-m-d H:i:s');
            }
            $configModel->save(false);
            $this->config = $configModel->attributes;
        }
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
