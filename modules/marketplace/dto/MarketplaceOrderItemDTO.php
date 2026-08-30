<?php

namespace app\modules\marketplace\dto;

/**
 * MarketplaceOrderItemDTO - DTO canônico para item de pedido de marketplace
 */
class MarketplaceOrderItemDTO
{
    /** @var string|null ID do anúncio/item no marketplace */
    public ?string $marketplaceItemId = null;

    /** @var string|null ID da variação no marketplace */
    public ?string $marketplaceVariationId = null;

    /** @var string|null SKU do vendedor */
    public ?string $sellerSku = null;

    /** @var string Título do produto */
    public string $title = '';

    /** @var float Quantidade */
    public float $quantity = 1.0;

    /** @var float Preço unitário de venda */
    public float $unitPrice = 0.0;

    /** @var float Preço total do item (qtd * unitPrice) */
    public float $totalPrice = 0.0;

    /** @var float Desconto rateado no item */
    public float $discount = 0.0;

    /** @var array Atributos de variação (ex: ['Cor' => 'Azul', 'Tamanho' => 'G']) */
    public array $variationAttributes = [];

    /** @var array Dados brutos originais do marketplace */
    public array $rawItemData = [];

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
    }
}
