<?php

namespace app\modules\marketplace\components;

use Yii;
use yii\base\Component;
use app\modules\marketplace\dto\MarketplaceOrderDTO;
use app\modules\marketplace\dto\MarketplaceOrderItemDTO;
use app\modules\marketplace\models\MarketplacePedido;
use app\modules\marketplace\models\MarketplacePedidoItem;
use app\modules\marketplace\models\MarketplaceProduto;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\VendaItem;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\StatusVenda;
use app\modules\vendas\services\EstoqueService;

/**
 * OrderEventProcessor - Processador Canônico de Pedidos de Marketplaces
 * 
 * Cria e atualiza pedidos no Pulse ERP de forma idempotente, segura e transacional,
 * executando baixa atômica de estoque e gerando a venda oficial.
 */
class OrderEventProcessor extends Component
{
    /**
     * Processa pedido a partir do DTO canônico ou array legado
     * 
     * @param MarketplaceOrderDTO|array $orderInput
     * @param string|null $marketplace
     * @param string|null $usuarioId
     * @return array
     */
    public function processOrder($orderInput, ?string $marketplace = null, ?string $usuarioId = null): array
    {
        if ($orderInput instanceof MarketplaceOrderDTO) {
            $dto = $orderInput;
        } else {
            $dto = $this->convertLegacyArrayToDTO($orderInput, $marketplace, $usuarioId);
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // 1. Verificar se pedido já existe (Idempotência)
            $pedido = MarketplacePedido::findOne([
                'marketplace' => $dto->marketplace,
                'marketplace_pedido_id' => $dto->marketplaceOrderId,
            ]);

            if ($pedido) {
                $result = $this->updateExistingOrder($pedido, $dto);
                $transaction->commit();
                return $result;
            }

            // 2. Criar novo MarketplacePedido
            $pedido = new MarketplacePedido();
            $pedido->usuario_id = $dto->usuarioId;
            $pedido->marketplace = $dto->marketplace;
            $pedido->marketplace_pedido_id = $dto->marketplaceOrderId;

            $pedido->cliente_nome = $dto->buyerName;
            $pedido->cliente_email = $dto->buyerEmail;
            $pedido->cliente_telefone = $dto->buyerPhone;
            $pedido->cliente_documento = $dto->buyerDocument;

            $pedido->endereco_completo = $dto->getFormattedAddress();
            $pedido->endereco_cep = $dto->shippingZipCode;
            $pedido->endereco_cidade = $dto->shippingCity;
            $pedido->endereco_estado = $dto->shippingState;

            $pedido->valor_total = $dto->totalAmount;
            $pedido->valor_produtos = $dto->productsAmount;
            $pedido->valor_frete = $dto->shippingAmount;
            $pedido->valor_desconto = $dto->discountAmount;

            $pedido->status = $dto->status;
            $pedido->status_pagamento = $dto->paymentStatus;
            $pedido->status_envio = $dto->shippingStatus;

            $pedido->codigo_rastreio = $dto->trackingCode;
            $pedido->transportadora = $dto->shippingCarrier;
            $pedido->data_pedido = $dto->dateCreated;
            $pedido->data_entrega_prevista = $dto->estimatedDeliveryDate;
            $pedido->dados_completos = $dto->rawPayload;
            $pedido->importado = false;

            if (!$pedido->save()) {
                throw new \Exception('Erro ao salvar MarketplacePedido: ' . json_encode($pedido->errors));
            }

            // 3. Criar itens do pedido e resolver produtos locais
            $itensProcessados = [];
            foreach ($dto->items as $itemDTO) {
                $item = new MarketplacePedidoItem();
                $item->pedido_id = $pedido->id;
                $item->marketplace_produto_id = $itemDTO->marketplaceItemId;
                $item->titulo = $itemDTO->title;
                $item->quantidade = $itemDTO->quantity;
                $item->preco_unitario = $itemDTO->unitPrice;
                $item->preco_total = $itemDTO->totalPrice;
                $item->sku = $itemDTO->sellerSku;
                $item->variacao = $itemDTO->variationAttributes;
                $item->dados_completos = $itemDTO->rawItemData;

                // Resolução de produto local por Vínculo explícito ou SKU/Código de barras
                $produtoLocalId = $this->resolveLocalProductId($pedido->usuario_id, $dto->marketplace, $itemDTO);
                if ($produtoLocalId) {
                    $item->produto_id = $produtoLocalId;
                }

                if (!$item->save()) {
                    throw new \Exception('Erro ao salvar MarketplacePedidoItem: ' . json_encode($item->errors));
                }

                $itensProcessados[] = $item;
            }

            // 4. Se o pedido já estiver pago / aprovado, converter em Venda Oficial e reservar estoque
            if (in_array(strtolower($dto->status), ['paid', 'approved', 'ready_to_ship', 'to_ship', 'shipped'])) {
                $this->convertToOfficialSale($pedido, $itensProcessados);
            }

            $transaction->commit();

            Yii::info(sprintf(
                "[OrderEventProcessor] Pedido %s (%s) criado com sucesso. Venda vinculada: %s",
                $pedido->marketplace_pedido_id,
                $pedido->marketplace,
                $pedido->venda_id ?? 'Nenhuma'
            ), 'marketplace');

            return [
                'action' => 'created',
                'pedido_id' => $pedido->id,
                'marketplace_pedido_id' => $pedido->marketplace_pedido_id,
                'venda_id' => $pedido->venda_id,
                'valor_total' => $pedido->valor_total,
                'itens_count' => count($itensProcessados),
            ];
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error("[OrderEventProcessor] Falha ao processar pedido: " . $e->getMessage(), 'marketplace');
            throw $e;
        }
    }

    /**
     * Atualiza pedido existente
     */
    protected function updateExistingOrder(MarketplacePedido $pedido, MarketplaceOrderDTO $dto): array
    {
        $pedido->status = $dto->status ?: $pedido->status;
        $pedido->status_pagamento = $dto->paymentStatus ?: $pedido->status_pagamento;
        $pedido->status_envio = $dto->shippingStatus ?: $pedido->status_envio;
        $pedido->codigo_rastreio = $dto->trackingCode ?: $pedido->codigo_rastreio;
        $pedido->dados_completos = $dto->rawPayload ?: $pedido->dados_completos;
        $pedido->save();

        // Se o status mudou para pago e ainda não tinha venda gerada
        if (!$pedido->importado && in_array(strtolower($dto->status), ['paid', 'approved', 'ready_to_ship', 'to_ship', 'shipped'])) {
            $this->convertToOfficialSale($pedido, $pedido->itens);
        }

        // Se o pedido foi cancelado no marketplace e tinha venda gerada, cancela a venda e estorna estoque
        if (in_array(strtolower($dto->status), ['cancelled', 'canceled', 'refunded']) && $pedido->venda_id) {
            $venda = Venda::findOne($pedido->venda_id);
            if ($venda && $venda->status_venda_codigo !== StatusVenda::CANCELADA) {
                $venda->transicionarStatus(StatusVenda::CANCELADA);
            }
        }

        return [
            'action' => 'updated',
            'pedido_id' => $pedido->id,
            'marketplace_pedido_id' => $pedido->marketplace_pedido_id,
            'status' => $pedido->status,
        ];
    }

    /**
     * Resolve o ID do produto local (ERP) a partir de vínculos de marketplace, SKU ou Código de barras
     */
    protected function resolveLocalProductId(string $usuarioId, string $marketplace, MarketplaceOrderItemDTO $itemDTO): ?string
    {
        // 1. Busca por vínculo direto na tabela prest_marketplace_produto
        if ($itemDTO->marketplaceItemId) {
            $query = MarketplaceProduto::find()
                ->where(['marketplace' => $marketplace, 'marketplace_produto_id' => $itemDTO->marketplaceItemId]);
            
            if ($itemDTO->marketplaceVariationId) {
                $query->andWhere(['marketplace_variacao_id' => $itemDTO->marketplaceVariationId]);
            }

            $mpProduto = $query->one();
            if ($mpProduto && $mpProduto->produto_id) {
                return $mpProduto->produto_id;
            }
        }

        // 2. Busca por SKU/Código de Referência local do seller
        if ($itemDTO->sellerSku) {
            $produto = Produto::find()
                ->where(['usuario_id' => $usuarioId])
                ->andWhere(['or',
                    ['codigo_referencia' => $itemDTO->sellerSku],
                    ['codigo_barras' => $itemDTO->sellerSku],
                    ['id' => $itemDTO->sellerSku]
                ])
                ->one();

            if ($produto) {
                return $produto->id;
            }
        }

        return null;
    }

    /**
     * Converte o pedido do marketplace em Venda Oficial no Pulse ERP com baixa de estoque atômica
     */
    protected function convertToOfficialSale(MarketplacePedido $pedido, array $itens): void
    {
        if ($pedido->importado && $pedido->venda_id) {
            return;
        }

        // 1. Buscar ou cadastrar cliente
        $cliente = null;
        if (!empty($pedido->cliente_documento)) {
            $cliente = Cliente::findOne([
                'usuario_id' => $pedido->usuario_id,
                'cpf_cnpj' => preg_replace('/\D/', '', $pedido->cliente_documento),
            ]);
        }

        if (!$cliente) {
            $cliente = new Cliente();
            $cliente->usuario_id = $pedido->usuario_id;
            $cliente->nome = $pedido->cliente_nome ?: 'Cliente ' . $pedido->getMarketplaceNome();
            $cliente->email = $pedido->cliente_email;
            $cliente->telefone = $pedido->cliente_telefone;
            $cliente->cpf_cnpj = !empty($pedido->cliente_documento) ? preg_replace('/\D/', '', $pedido->cliente_documento) : null;
            $cliente->tipo_pessoa = (strlen($cliente->cpf_cnpj ?? '') > 11) ? 'J' : 'F';
            $cliente->endereco = $pedido->endereco_completo;
            $cliente->cep = $pedido->endereco_cep;
            $cliente->cidade = $pedido->endereco_cidade;
            $cliente->estado = $pedido->endereco_estado;
            $cliente->save(false);
        }

        // 2. Criar a Venda
        $venda = new Venda();
        $venda->usuario_id = $pedido->usuario_id;
        $venda->cliente_id = $cliente->id;
        $venda->valor_total = $pedido->valor_total;
        $venda->valor_desconto = $pedido->valor_desconto ?: 0;
        $venda->valor_frete = $pedido->valor_frete ?: 0;
        $venda->status_venda_codigo = StatusVenda::QUITADA;
        $venda->observacoes = sprintf("Pedido importado automaticamente de %s (#%s)", $pedido->getMarketplaceNome(), $pedido->marketplace_pedido_id);
        $venda->data_venda = $pedido->data_pedido ?: date('Y-m-d H:i:s');

        if (!$venda->save(false)) {
            throw new \Exception('Erro ao criar Venda oficial: ' . json_encode($venda->errors));
        }

        // 3. Criar Itens da Venda e Realizar Baixa Atômica de Estoque
        foreach ($itens as $item) {
            $vendaItem = new VendaItem();
            $vendaItem->venda_id = $venda->id;
            $vendaItem->produto_id = $item->produto_id;
            $vendaItem->nome_item_manual = $item->titulo;
            $vendaItem->quantidade = $item->quantidade;
            $vendaItem->preco_unitario = $item->preco_unitario;
            $vendaItem->valor_total_item = $item->preco_total;
            $vendaItem->save(false);

            // Baixa atômica se o produto local foi identificado
            if ($item->produto_id) {
                try {
                    EstoqueService::baixarEstoque(
                        $item->produto_id,
                        (float)$item->quantidade,
                        "Pedido {$pedido->getMarketplaceNome()} #{$pedido->marketplace_pedido_id}"
                    );
                } catch (\Throwable $e) {
                    Yii::error(sprintf(
                        "[OrderEventProcessor] Falha na baixa de estoque do produto %s no pedido %s: %s",
                        $item->produto_id,
                        $pedido->marketplace_pedido_id,
                        $e->getMessage()
                    ), 'marketplace');
                }
            }
        }

        $pedido->venda_id = $venda->id;
        $pedido->importado = true;
        $pedido->save(false);
    }

    /**
     * Converte array de pedido legado (Mercado Livre, Shopee, etc.) em MarketplaceOrderDTO
     */
    protected function convertLegacyArrayToDTO(array $data, ?string $marketplace, ?string $usuarioId): MarketplaceOrderDTO
    {
        $dto = new MarketplaceOrderDTO();
        $dto->marketplace = $marketplace ?? 'MERCADO_LIVRE';
        $dto->usuarioId = $usuarioId ?? '';
        $dto->marketplaceOrderId = (string)($data['id'] ?? $data['order_sn'] ?? uniqid('order_'));
        $dto->totalAmount = (float)($data['total_amount'] ?? $data['total_price'] ?? 0);
        $dto->status = (string)($data['status'] ?? 'pending');
        $dto->rawPayload = $data;

        // Comprador
        $buyer = $data['buyer'] ?? [];
        $dto->buyerName = $buyer['nickname'] ?? $buyer['first_name'] ?? $buyer['name'] ?? 'Cliente Marketplace';
        $dto->buyerEmail = $buyer['email'] ?? null;
        $dto->buyerDocument = $buyer['billing_info']['doc_number'] ?? $buyer['identification']['number'] ?? null;

        // Frete
        $shipping = $data['shipping'] ?? [];
        $receiver = $shipping['receiver_address'] ?? $data['recipient_address'] ?? [];
        $dto->shippingStreet = $receiver['street_name'] ?? $receiver['full_address'] ?? null;
        $dto->shippingNumber = $receiver['street_number'] ?? null;
        $dto->shippingZipCode = $receiver['zip_code'] ?? null;
        $dto->shippingCity = $receiver['city']['name'] ?? $receiver['city'] ?? null;
        $dto->shippingState = $receiver['state']['id'] ?? $receiver['state'] ?? null;
        $dto->trackingCode = $shipping['tracking_number'] ?? $data['tracking_no'] ?? null;

        // Itens
        $items = $data['order_items'] ?? $data['item_list'] ?? [];
        foreach ($items as $itemData) {
            $itemDTO = new MarketplaceOrderItemDTO();
            $itemDTO->marketplaceItemId = (string)($itemData['item']['id'] ?? $itemData['item_id'] ?? '');
            $itemDTO->title = (string)($itemData['item']['title'] ?? $itemData['item_name'] ?? 'Produto');
            $itemDTO->quantity = (float)($itemData['quantity'] ?? $itemData['model_quantity_purchased'] ?? 1);
            $itemDTO->unitPrice = (float)($itemData['unit_price'] ?? $itemData['model_discounted_price'] ?? 0);
            $itemDTO->totalPrice = $itemDTO->quantity * $itemDTO->unitPrice;
            $itemDTO->sellerSku = (string)($itemData['item']['seller_sku'] ?? $itemData['item_sku'] ?? '');
            $itemDTO->rawItemData = $itemData;
            $dto->items[] = $itemDTO;
        }

        return $dto;
    }
}
