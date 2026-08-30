<?php

namespace app\modules\marketplace\components;

use Yii;
use app\modules\marketplace\models\MarketplaceConfig;
use app\modules\marketplace\models\MarketplaceProduto;
use app\modules\marketplace\dto\MarketplaceOrderDTO;
use app\modules\marketplace\dto\MarketplaceOrderItemDTO;
use app\modules\vendas\models\Produto;

/**
 * MercadoLivreService - Conector de Integração Oficial com a API do Mercado Livre (MELI)
 */
class MercadoLivreService extends MarketplaceService
{
    protected $marketplaceName = 'MERCADO_LIVRE';
    protected string $apiBaseUrl = 'https://api.mercadolibre.com';

    /**
     * Autentica ou troca o Authorization Code pelo Access Token
     * 
     * @param string|null $authCode Código recebido no redirect OAuth
     * @param string|null $redirectUri URL de callback registrada no Meli Developers
     * @return bool
     */
    public function authenticate(?string $authCode = null, ?string $redirectUri = null): bool
    {
        if (!$authCode) {
            return false;
        }

        try {
            $response = $this->request('POST', "{$this->apiBaseUrl}/oauth/token", [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => $this->config['client_id'],
                    'client_secret' => $this->config['client_secret'],
                    'code' => $authCode,
                    'redirect_uri' => $redirectUri,
                ],
            ]);

            if (isset($response['access_token'])) {
                $this->salvarTokens($response);
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            $this->handleError($e, 'authenticate');
            return false;
        }
    }

    /**
     * Atualiza o Access Token usando o Refresh Token
     * 
     * @return bool
     */
    public function refreshToken(): bool
    {
        if (empty($this->config['refresh_token'])) {
            Yii::error("[MercadoLivreService] Refresh Token não configurado.", 'marketplace');
            return false;
        }

        try {
            $response = $this->request('POST', "{$this->apiBaseUrl}/oauth/token", [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'client_id' => $this->config['client_id'],
                    'client_secret' => $this->config['client_secret'],
                    'refresh_token' => $this->config['refresh_token'],
                ],
            ]);

            if (isset($response['access_token'])) {
                $this->salvarTokens($response);
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            $this->handleError($e, 'refreshToken');
            return false;
        }
    }

    /**
     * Sincroniza estoque de um produto no Mercado Livre
     * 
     * @param string $produtoId UUID do produto no Pulse ERP
     * @param int $quantidade Nova quantidade disponível
     * @return bool
     */
    public function syncEstoque($produtoId, $quantidade): bool
    {
        $this->garantirTokenValido();

        // Buscar todos os vínculos de anúncio deste produto no ML
        $vinculos = MarketplaceProduto::find()
            ->where([
                'marketplace' => $this->marketplaceName,
                'produto_id' => $produtoId,
                'usuario_id' => $this->usuarioId,
            ])
            ->all();

        if (empty($vinculos)) {
            Yii::info("[MercadoLivreService] Nenhum anúncio vinculado para o produto {$produtoId}.", 'marketplace');
            return true;
        }

        $todosSucesso = true;

        foreach ($vinculos as $vinculo) {
            try {
                $itemId = $vinculo->marketplace_produto_id;
                $payload = ['available_quantity' => max(0, (int)$quantidade)];

                // Se houver variação específica vinculada
                if (!empty($vinculo->marketplace_variacao_id)) {
                    $url = "{$this->apiBaseUrl}/items/{$itemId}/variations/{$vinculo->marketplace_variacao_id}";
                } else {
                    $url = "{$this->apiBaseUrl}/items/{$itemId}";
                }

                $this->request('PUT', $url, [
                    'headers' => $this->getAuthHeaders(),
                    'json' => $payload,
                ]);

                $vinculo->estoque_marketplace = (int)$quantidade;
                $vinculo->ultima_sync = new \yii\db\Expression('NOW()');
                $vinculo->erro_sync = null;
                $vinculo->save(false);

                Yii::info("[MercadoLivreService] Estoque atualizado no anúncio {$itemId} ({$quantidade} un).", 'marketplace');
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
     * Sincroniza preço no Mercado Livre aplicando regras de markup
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

        $todosSucesso = true;

        foreach ($vinculos as $vinculo) {
            try {
                $itemId = $vinculo->marketplace_produto_id;
                $precoFinal = $novoPreco ? (float)$novoPreco : $vinculo->getPrecoFinal();

                $payload = ['price' => $precoFinal];

                if (!empty($vinculo->marketplace_variacao_id)) {
                    $url = "{$this->apiBaseUrl}/items/{$itemId}/variations/{$vinculo->marketplace_variacao_id}";
                } else {
                    $url = "{$this->apiBaseUrl}/items/{$itemId}";
                }

                $this->request('PUT', $url, [
                    'headers' => $this->getAuthHeaders(),
                    'json' => $payload,
                ]);

                $vinculo->preco_marketplace = $precoFinal;
                $vinculo->ultima_sync = new \yii\db\Expression('NOW()');
                $vinculo->save(false);

                Yii::info("[MercadoLivreService] Preço atualizado no item {$itemId} para R$ {$precoFinal}.", 'marketplace');
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
     * Importa pedidos do Mercado Livre dentro de um intervalo de datas
     * 
     * @param string|null $dataInicio Data início (Y-m-d)
     * @param string|null $dataFim Data fim (Y-m-d)
     * @return array Lista de pedidos processados
     */
    public function importPedidos($dataInicio = null, $dataFim = null): array
    {
        $this->garantirTokenValido();

        $sellerId = $this->config['seller_id_externo'] ?? 'me';
        $params = ['seller' => $sellerId, 'sort' => 'date_desc'];

        if ($dataInicio) {
            $params['order.date_created.from'] = date('c', strtotime($dataInicio));
        }
        if ($dataFim) {
            $params['order.date_created.to'] = date('c', strtotime($dataFim));
        }

        try {
            $response = $this->request('GET', "{$this->apiBaseUrl}/orders/search", [
                'headers' => $this->getAuthHeaders(),
                'query' => $params,
            ]);

            $results = $response['results'] ?? [];
            $processador = new OrderEventProcessor();
            $pedidosImportados = [];

            foreach ($results as $orderData) {
                $dto = $this->normalizeOrderToDTO($orderData);
                $resultado = $processador->processOrder($dto);
                $pedidosImportados[] = $resultado;
            }

            return $pedidosImportados;
        } catch (\Throwable $e) {
            $this->handleError($e, 'importPedidos');
            return [];
        }
    }

    /**
     * Envia documento fiscal (Chave de Acesso NF-e / XML) para o Mercado Livre
     * 
     * @param string $orderId ID do pedido no Mercado Livre
     * @param string $chaveAcesso Chave de 44 dígitos da NF-e
     * @param string|null $xml Conteúdo XML da NF-e
     * @return bool
     */
    public function uploadNfe(string $orderId, string $chaveAcesso, ?string $xml = null): bool
    {
        $this->garantirTokenValido();

        try {
            $payload = [
                'fiscal_key' => preg_replace('/\D/', '', $chaveAcesso),
            ];

            if ($xml) {
                $payload['xml_file'] = base64_encode($xml);
            }

            $this->request('POST', "{$this->apiBaseUrl}/orders/{$orderId}/fiscal_documents", [
                'headers' => $this->getAuthHeaders(),
                'json' => $payload,
            ]);

            Yii::info("[MercadoLivreService] NF-e vinculada com sucesso ao pedido {$orderId}.", 'marketplace');
            return true;
        } catch (\Throwable $e) {
            $this->handleError($e, "uploadNfe ({$orderId})");
            return false;
        }
    }

    /**
     * Baixa a etiqueta de envio do Mercado Envios em formato PDF
     * 
     * @param string|int $shipmentId ID do envio no Mercado Envios
     * @return string|null Conteúdo binário do PDF
     */
    public function getShippingLabelPdf($shipmentId): ?string
    {
        $this->garantirTokenValido();

        try {
            $response = $this->httpClient->get("{$this->apiBaseUrl}/shipment_labels", [
                'headers' => $this->getAuthHeaders(),
                'query' => [
                    'shipment_ids' => $shipmentId,
                    'response_type' => 'pdf',
                ],
            ]);

            return (string)$response->getBody();
        } catch (\Throwable $e) {
            $this->handleError($e, "getShippingLabelPdf ({$shipmentId})");
            return null;
        }
    }

    /**
     * Normaliza dados brutos da API do Mercado Livre para o DTO Canônico
     */
    public function normalizeOrderToDTO(array $orderData): MarketplaceOrderDTO
    {
        $dto = new MarketplaceOrderDTO();
        $dto->marketplace = $this->marketplaceName;
        $dto->usuarioId = $this->usuarioId;
        $dto->marketplaceOrderId = (string)$orderData['id'];
        $dto->status = $orderData['status'] ?? 'pending';
        $dto->totalAmount = (float)($orderData['total_amount'] ?? 0);
        $dto->dateCreated = date('Y-m-d H:i:s', strtotime($orderData['date_created'] ?? 'now'));
        $dto->rawPayload = $orderData;

        // Comprador
        $buyer = $orderData['buyer'] ?? [];
        $dto->buyerName = $buyer['nickname'] ?? trim(($buyer['first_name'] ?? '') . ' ' . ($buyer['last_name'] ?? '')) ?: 'Cliente Mercado Livre';
        $dto->buyerEmail = $buyer['email'] ?? null;
        $dto->buyerDocument = $buyer['billing_info']['doc_number'] ?? null;
        if (isset($buyer['phone']['area_code'], $buyer['phone']['number'])) {
            $dto->buyerPhone = $buyer['phone']['area_code'] . $buyer['phone']['number'];
        }

        // Frete
        $shipping = $orderData['shipping'] ?? [];
        $receiver = $shipping['receiver_address'] ?? [];
        $dto->shippingStreet = $receiver['street_name'] ?? null;
        $dto->shippingNumber = $receiver['street_number'] ?? null;
        $dto->shippingComplement = $receiver['comment'] ?? null;
        $dto->shippingNeighborhood = $receiver['neighborhood']['name'] ?? null;
        $dto->shippingCity = $receiver['city']['name'] ?? null;
        $dto->shippingState = $receiver['state']['id'] ?? null;
        $dto->shippingZipCode = $receiver['zip_code'] ?? null;
        $dto->trackingCode = $shipping['tracking_number'] ?? null;
        $dto->shippingCarrier = $shipping['logistic_type'] ?? null;
        $dto->shippingAmount = (float)($shipping['cost'] ?? 0);

        // Itens
        $items = $orderData['order_items'] ?? [];
        foreach ($items as $itemData) {
            $item = $itemData['item'] ?? [];
            $itemDTO = new MarketplaceOrderItemDTO();
            $itemDTO->marketplaceItemId = (string)($item['id'] ?? '');
            $itemDTO->marketplaceVariationId = isset($item['variation_id']) ? (string)$item['variation_id'] : null;
            $itemDTO->title = (string)($item['title'] ?? 'Produto Mercado Livre');
            $itemDTO->quantity = (float)($itemData['quantity'] ?? 1);
            $itemDTO->unitPrice = (float)($itemData['unit_price'] ?? 0);
            $itemDTO->totalPrice = $itemDTO->quantity * $itemDTO->unitPrice;
            $itemDTO->sellerSku = (string)($item['seller_sku'] ?? '');
            $itemDTO->variationAttributes = $item['variation_attributes'] ?? [];
            $itemDTO->rawItemData = $itemData;
            $dto->items[] = $itemDTO;
        }

        $dto->productsAmount = array_sum(array_map(fn($i) => $i->totalPrice, $dto->items));

        return $dto;
    }

    /**
     * Headers com Bearer Token
     */
    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . ($this->config['access_token'] ?? ''),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Garante que o token atual está válido ou renova antes de fazer chamadas
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
    protected function salvarTokens(array $tokenData): void
    {
        $configModel = MarketplaceConfig::findOne([
            'usuario_id' => $this->usuarioId,
            'marketplace' => $this->marketplaceName,
        ]);

        if ($configModel) {
            $configModel->access_token = $tokenData['access_token'];
            $configModel->refresh_token = $tokenData['refresh_token'] ?? $configModel->refresh_token;
            if (isset($tokenData['user_id'])) {
                $configModel->seller_id_externo = (string)$tokenData['user_id'];
            }
            if (isset($tokenData['expires_in'])) {
                $expiraEm = new \DateTime();
                $expiraEm->modify("+{$tokenData['expires_in']} seconds");
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
