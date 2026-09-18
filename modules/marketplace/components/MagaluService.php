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

    public function __construct($config = [])
    {
        if ($config instanceof MarketplaceConfig) {
            $attrs = $config->attributes;
            parent::__construct([]);
            $this->setConfig($attrs);
            return;
        }

        parent::__construct($config);
    }

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
     * Busca os dados completos de um pedido pelo ID na API do Magalu
     * 
     * @param string $orderId ID ou código do pedido no Magalu
     * @return array Dados completos do pedido
     * @throws \Exception
     */
    public function getOrder(string $orderId): array
    {
        $orderId = trim($orderId);
        $url = "{$this->apiBaseUrl}/orders/{$orderId}";

        try {
            $response = $this->request('GET', $url, [
                'headers' => $this->getAuthHeaders(),
            ]);

            return $response['order'] ?? $response ?? [];
        } catch (\Throwable $e) {
            $this->handleError($e, "getOrder ({$orderId})");
            throw $e;
        }
    }

    /**
     * Normaliza pedido do Magalu para o DTO canônico MarketplaceOrderDTO
     */
    public function normalizeOrderToDTO(array $orderData): MarketplaceOrderDTO
    {
        $dto = new MarketplaceOrderDTO();
        $dto->marketplace = $this->marketplaceName;
        $dto->usuarioId = $this->usuarioId ?? '';
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

        // Tratamento Logístico Dual (Magalu Entregas vs Envio Próprio)
        $shipping = is_array($orderData['shipping'] ?? null) ? $orderData['shipping'] : [];
        $isMagalu = $this->isMagaluEntregas($orderData);
        $dto->logisticType = $isMagalu ? 'MAGALU_ENTREGAS' : ($shipping['type'] ?? $orderData['delivery_type'] ?? 'DIRECT');
        
        $carrier = $orderData['shipping_carrier'] ?? $orderData['carrier'] ?? $shipping['carrier'] ?? null;
        if ($isMagalu) {
            $dto->shippingCarrier = ($carrier && strtoupper($carrier) !== 'MAGALU') ? $carrier : 'Magalu Entregas';
        } else {
            $dto->shippingCarrier = $carrier;
        }

        $dto->trackingCode = $orderData['tracking_code'] ?? $orderData['tracking']['code'] ?? $shipping['tracking_code'] ?? null;
        $dto->shippingAmount = (float)($orderData['shipping_amount'] ?? $shipping['cost'] ?? $shipping['price'] ?? 0);

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

    // =========================================================================
    // 🚚 TRATAMENTO LOGÍSTICO DUAL (MAGALU ENTREGAS vs ENVIO PRÓPRIO)
    // =========================================================================

    /**
     * Verifica se o pedido utiliza o modelo logístico Magalu Entregas (Fulfillment / Coleta)
     */
    public function isMagaluEntregas(array $orderData): bool
    {
        $shipping = is_array($orderData['shipping'] ?? null) ? $orderData['shipping'] : [];

        $deliveryType = strtolower((string)(
            $orderData['delivery_type'] 
            ?? $orderData['shipping_type'] 
            ?? $shipping['type'] 
            ?? $shipping['delivery_type'] 
            ?? $orderData['channel'] 
            ?? ''
        ));

        $carrier = strtoupper((string)(
            $orderData['shipping_carrier'] 
            ?? $orderData['carrier'] 
            ?? $shipping['carrier'] 
            ?? ''
        ));

        return (
            strpos($deliveryType, 'magalu') !== false ||
            strpos($deliveryType, 'fulfillment') !== false ||
            strpos($deliveryType, 'coleta') !== false ||
            strpos($deliveryType, 'plp') !== false ||
            $carrier === 'MAGALU' ||
            strpos($carrier, 'MAGALU') !== false ||
            !empty($orderData['magalu_entregas'])
        );
    }

    /**
     * Gera e fecha a Pré-Lista de Postagem (PLP) para pedidos Magalu Entregas
     * @param array $orderIds Lista de IDs de pedidos
     * @return array Dados da PLP gerada
     */
    public function gerarPlp(array $orderIds): array
    {
        $url = "{$this->apiBaseUrl}/shipments/plp";

        try {
            $response = $this->request('POST', $url, [
                'headers' => $this->getAuthHeaders(),
                'json' => [
                    'order_ids' => $orderIds,
                ],
            ]);

            Yii::info("[MagaluService] PLP gerada com sucesso para " . count($orderIds) . " pedidos.", 'marketplace');
            return $response;
        } catch (\Throwable $e) {
            $this->handleError($e, 'gerarPlp');
            throw $e;
        }
    }

    /**
     * Obtém a etiqueta de envio oficial de um pedido Magalu Entregas
     * @param string $orderId
     * @return string|null URL ou conteúdo da etiqueta
     */
    public function obterEtiquetaEnvio(string $orderId): ?string
    {
        $url = "{$this->apiBaseUrl}/orders/{$orderId}/shipment/label";

        try {
            $response = $this->request('GET', $url, [
                'headers' => $this->getAuthHeaders(),
            ]);

            return $response['label_url'] ?? $response['content'] ?? null;
        } catch (\Throwable $e) {
            $this->handleError($e, "obterEtiquetaEnvio ({$orderId})");
            return null;
        }
    }

    /**
     * Notifica o despacho para pedidos com Envio Próprio (Transportadora contratada pelo lojista)
     * 
     * @param string $orderId
     * @param string $carrierName Nome da transportadora
     * @param string $trackingCode Código de rastreamento
     * @param string|null $trackingUrl URL para consulta do rastreamento
     * @return bool
     */
    public function despacharEnvioProprio(string $orderId, string $carrierName, string $trackingCode, ?string $trackingUrl = null): bool
    {
        $url = "{$this->apiBaseUrl}/orders/{$orderId}/dispatch";

        try {
            $payload = [
                'carrier_name' => trim($carrierName),
                'tracking_code' => trim($trackingCode),
            ];
            if ($trackingUrl) {
                $payload['tracking_url'] = trim($trackingUrl);
            }

            $this->request('POST', $url, [
                'headers' => $this->getAuthHeaders(),
                'json' => $payload,
            ]);

            Yii::info("[MagaluService] Pedido {$orderId} despachado via Envio Próprio ({$carrierName} - {$trackingCode}).", 'marketplace');
            return true;
        } catch (\Throwable $e) {
            $this->handleError($e, "despacharEnvioProprio ({$orderId})");
            return false;
        }
    }

    // =========================================================================
    // 📦 MODELAGEM DE CATÁLOGO E VARIAÇÕES (PAI x FILHOS)
    // =========================================================================

    /**
     * Constrói e valida o payload hierárquico Pai x Filhos para envio à API do Magalu
     * 
     * @param Produto $produto
     * @return array
     * @throws \yii\base\UserException Se houver violação de atributos obrigatórios
     */
    public function buildProdutoPayload(Produto $produto): array
    {
        // 1. Validação estrita dos atributos obrigatórios do Produto Pai
        $titulo = trim((string)$produto->nome);
        if (empty($titulo)) {
            throw new \yii\base\UserException("Produto ID '{$produto->id}' não possui título/nome cadastrado.");
        }

        $ncm = preg_replace('/\D/', '', (string)$produto->ncm);
        if (!preg_match('/^\d{8}$/', $ncm)) {
            throw new \yii\base\UserException("NCM do produto '{$titulo}' inválido ('{$produto->ncm}'). O Magalu exige exatamente 8 dígitos numéricos.");
        }

        $marca = 'Genérica';
        if (!empty($produto->marca)) {
            $marca = trim($produto->marca);
        } elseif ($produto->canGetProperty('fornecedor') && $produto->fornecedor && !empty($produto->fornecedor->nome_fantasia)) {
            $marca = trim($produto->fornecedor->nome_fantasia);
        }

        $descricao = strip_tags((string)($produto->descricao ?: $produto->nome));
        if (mb_strlen($descricao) < 10) {
            $descricao = $titulo . ' - Produto comercializado com garantia e nota fiscal.';
        }

        $skuPai = (string)($produto->codigo_referencia ?: $produto->id);

        // Preço base calculado com markup da conta se configurado
        $precoBase = (float)($produto->preco_venda_sugerido ?? $produto->preco_venda ?? 0);
        $configModel = MarketplaceConfig::findOne(['usuario_id' => $this->usuarioId, 'marketplace' => $this->marketplaceName, 'ativo' => true]);
        $precoCalculado = $configModel ? $configModel->calcularPrecoComMarkup($precoBase) : $precoBase;

        // Imagens do produto
        $imagens = [];
        try {
            if (!empty($produto->fotos)) {
                foreach ($produto->fotos as $foto) {
                    if (is_string($foto)) {
                        $imagens[] = $foto;
                    } elseif (is_object($foto)) {
                        $imgUrl = method_exists($foto, 'getUrlCompleta') ? $foto->getUrlCompleta() : (method_exists($foto, 'getUrl') ? $foto->getUrl() : null);
                        if ($imgUrl && !in_array($imgUrl, $imagens)) {
                            $imagens[] = $imgUrl;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {}

        if (empty($imagens)) {
            throw new \yii\base\UserException("O produto '{$titulo}' deve conter pelo menos uma imagem para sincronização com o Magalu.");
        }

        // Dimensões físicas para frete
        $peso = max(0.1, (float)($produto->peso_bruto ?? $produto->peso_liquido ?? $produto->peso ?? 0.3));
        $altura = max(2.0, (float)($produto->altura_cm ?? $produto->altura ?? 10.0));
        $largura = max(5.0, (float)($produto->largura_cm ?? $produto->largura ?? 15.0));
        $profundidade = max(5.0, (float)($produto->comprimento_cm ?? $produto->comprimento ?? 20.0));

        // 2. Montagem dos SKUs Filhos (Variações)
        $variacoesPayload = [];
        $variantes = [];
        try {
            if ($produto->id && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string)$produto->id)) {
                $variantes = \app\modules\vendas\models\ProdutoVariante::find()
                    ->where(['produto_id' => $produto->id, 'ativo' => true])
                    ->all();
            }
        } catch (\Throwable $e) {
            Yii::warning("Erro ao buscar variantes para produto {$produto->id}: " . $e->getMessage(), __METHOD__);
        }

        if (!empty($variantes)) {
            foreach ($variantes as $var) {
                $skuFilho = (string)($var->codigo_referencia ?: ($skuPai . '-' . ($var->cor ?: 'PADRAO') . '-' . ($var->tamanho ?: 'U')));
                $precoFilho = $var->preco_venda_sugerido 
                    ? ($configModel ? $configModel->calcularPrecoComMarkup((float)$var->preco_venda_sugerido) : (float)$var->preco_venda_sugerido) 
                    : $precoCalculado;

                $ean = preg_replace('/\D/', '', (string)($var->codigo_barras ?: $produto->codigo_barras));
                if (strlen($ean) < 8 || strlen($ean) > 14) {
                    $ean = 'SEM GTIN';
                }

                // Imagens específicas da variante se houver
                $varImgs = [];
                if (!empty($var->fotos)) {
                    foreach ($var->fotos as $fotoVar) {
                        $urlV = $fotoVar->getUrlCompleta();
                        if ($urlV) $varImgs[] = $urlV;
                    }
                }
                if (empty($varImgs)) {
                    $varImgs = $imagens;
                }

                $variacoesPayload[] = [
                    'sku' => $skuFilho,
                    'seller_sku' => $skuFilho,
                    'ean' => $ean,
                    'attributes' => [
                        'color' => $var->cor ?: 'Única',
                        'size' => $var->tamanho ?: 'U',
                    ],
                    'price' => round($precoFilho, 2),
                    'list_price' => round($precoFilho, 2),
                    'stock_quantity' => (int)max(0, $var->estoque_atual),
                    'images' => $varImgs,
                    'dimensions' => [
                        'weight' => $peso,
                        'height' => $altura,
                        'width' => $largura,
                        'depth' => $profundidade,
                    ],
                ];
            }
        } else {
            // Produto Simples (sem variação): Gera 1 SKU Filho canônico
            $ean = preg_replace('/\D/', '', (string)$produto->codigo_barras);
            if (strlen($ean) < 8 || strlen($ean) > 14) {
                $ean = 'SEM GTIN';
            }

            $variacoesPayload[] = [
                'sku' => $skuPai,
                'seller_sku' => $skuPai,
                'ean' => $ean,
                'attributes' => [
                    'color' => 'Única',
                    'size' => 'U',
                ],
                'price' => round($precoCalculado, 2),
                'list_price' => round($precoCalculado, 2),
                'stock_quantity' => (int)max(0, $produto->estoque_atual),
                'images' => $imagens,
                'dimensions' => [
                    'weight' => $peso,
                    'height' => $altura,
                    'width' => $largura,
                    'depth' => $profundidade,
                ],
            ];
        }

        return [
            'sku' => $skuPai,
            'title' => mb_substr($titulo, 0, 255),
            'brand' => $marca,
            'ncm' => $ncm,
            'description' => $descricao,
            'category_id' => (string)($produto->categoria_id ?? 'GERAL'),
            'variations' => $variacoesPayload,
        ];
    }

    /**
     * Publica ou atualiza um produto completo (Pai x Filhos) no catálogo Magalu
     * @param string $produtoId
     * @return array
     */
    public function publishProduto(string $produtoId): array
    {
        $produto = Produto::findOne($produtoId);
        if (!$produto) {
            throw new \Exception("Produto ID '{$produtoId}' não encontrado.");
        }

        $payload = $this->buildProdutoPayload($produto);
        $url = "{$this->apiBaseUrl}/products";

        try {
            $response = $this->request('POST', $url, [
                'headers' => $this->getAuthHeaders(),
                'json' => $payload,
            ]);

            Yii::info("[MagaluService] Produto {$produto->id} ({$payload['sku']}) publicado com sucesso no Magalu.", 'marketplace');

            // Registra ou atualiza vínculo do produto pai
            $mpProduto = MarketplaceProduto::findOne([
                'marketplace' => $this->marketplaceName,
                'produto_id' => $produto->id,
                'usuario_id' => $this->usuarioId,
            ]) ?: new MarketplaceProduto();

            $mpProduto->usuario_id = $this->usuarioId;
            $mpProduto->produto_id = $produto->id;
            $mpProduto->marketplace = $this->marketplaceName;
            $mpProduto->marketplace_produto_id = $payload['sku'];
            $mpProduto->sku_marketplace = $payload['sku'];
            $mpProduto->titulo_marketplace = $payload['title'];
            $mpProduto->preco_marketplace = $payload['variations'][0]['price'] ?? $produto->preco_venda;
            $mpProduto->estoque_marketplace = (int)$produto->estoque_atual;
            $mpProduto->status = MarketplaceProduto::STATUS_ATIVO;
            $mpProduto->ultima_sync = new \yii\db\Expression('NOW()');
            $mpProduto->erro_sync = null;
            $mpProduto->save(false);

            return ['success' => true, 'response' => $response];
        } catch (\Throwable $e) {
            $this->handleError($e, "publishProduto ({$produtoId})");
            throw $e;
        }
    }

    /**
     * Sincroniza lista de produtos com o Magalu
     */
    public function syncProdutos($produtoIds = []): array
    {
        if (empty($produtoIds)) {
            $produtoIds = Produto::find()
                ->where(['usuario_id' => $this->usuarioId, 'ativo' => true])
                ->select('id')
                ->column();
        }

        $resultados = ['sucesso' => 0, 'erro' => 0, 'erros' => []];

        foreach ($produtoIds as $id) {
            try {
                $this->publishProduto((string)$id);
                $resultados['sucesso']++;
            } catch (\Throwable $e) {
                $resultados['erro']++;
                $resultados['erros'][$id] = $e->getMessage();
            }
        }

        return $resultados;
    }

    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . ($this->config['access_token'] ?? $this->config['client_secret'] ?? ''),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
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
