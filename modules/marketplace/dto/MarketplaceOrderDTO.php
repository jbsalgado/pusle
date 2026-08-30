<?php

namespace app\modules\marketplace\dto;

/**
 * MarketplaceOrderDTO - DTO canônico normalizado para pedidos de qualquer marketplace
 */
class MarketplaceOrderDTO
{
    /** @var string Nome do marketplace (MERCADO_LIVRE, SHOPEE, MAGAZINE_LUIZA, TEMU, etc.) */
    public string $marketplace;

    /** @var string UUID do tenant/dono da loja */
    public string $usuarioId;

    /** @var string ID do pedido no marketplace */
    public string $marketplaceOrderId;

    /** @var string Status do pedido no marketplace */
    public string $status = 'pending';

    /** @var string Status do pagamento */
    public string $paymentStatus = 'pending';

    /** @var string Status do envio */
    public string $shippingStatus = 'to_ship';

    /** @var float Valor total pago */
    public float $totalAmount = 0.0;

    /** @var float Valor dos produtos */
    public float $productsAmount = 0.0;

    /** @var float Valor do frete */
    public float $shippingAmount = 0.0;

    /** @var float Valor de desconto */
    public float $discountAmount = 0.0;

    // --- Dados do Comprador ---
    public string $buyerName = 'Cliente Marketplace';
    public ?string $buyerDocument = null; // CPF ou CNPJ
    public ?string $buyerEmail = null;
    public ?string $buyerPhone = null;

    // --- Endereço de Entrega ---
    public ?string $shippingStreet = null;
    public ?string $shippingNumber = null;
    public ?string $shippingComplement = null;
    public ?string $shippingNeighborhood = null;
    public ?string $shippingCity = null;
    public ?string $shippingState = null;
    public ?string $shippingZipCode = null;
    public ?string $shippingAddressFull = null;

    // --- Rastreamento & Logística ---
    public ?string $trackingCode = null;
    public ?string $shippingCarrier = null;
    public ?string $logisticType = null; // fulfillment, crossdocking, self_service
    public ?string $estimatedDeliveryDate = null;

    // --- Datas ---
    public string $dateCreated;
    public ?string $dateClosed = null;

    /** @var MarketplaceOrderItemDTO[] */
    public array $items = [];

    /** @var array Dados brutos completos recebidos da API do marketplace */
    public array $rawPayload = [];

    public function __construct(array $data = [])
    {
        $this->dateCreated = date('Y-m-d H:i:s');
        foreach ($data as $key => $val) {
            if ($key === 'items' && is_array($val)) {
                $this->items = [];
                foreach ($val as $item) {
                    $this->items[] = ($item instanceof MarketplaceOrderItemDTO) ? $item : new MarketplaceOrderItemDTO($item);
                }
            } elseif (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
    }

    /**
     * Retorna endereço completo formatado
     */
    public function getFormattedAddress(): string
    {
        if (!empty($this->shippingAddressFull)) {
            return $this->shippingAddressFull;
        }

        $parts = [];
        if ($this->shippingStreet) {
            $street = $this->shippingStreet;
            if ($this->shippingNumber) {
                $street .= ', ' . $this->shippingNumber;
            }
            if ($this->shippingComplement) {
                $street .= ' (' . $this->shippingComplement . ')';
            }
            $parts[] = $street;
        }
        if ($this->shippingNeighborhood) {
            $parts[] = $this->shippingNeighborhood;
        }
        if ($this->shippingCity) {
            $parts[] = $this->shippingCity . ($this->shippingState ? '/' . $this->shippingState : '');
        }
        if ($this->shippingZipCode) {
            $parts[] = 'CEP: ' . $this->shippingZipCode;
        }

        return implode(' - ', $parts);
    }
}
