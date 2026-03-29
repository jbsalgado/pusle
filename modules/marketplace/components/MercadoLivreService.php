<?php

namespace app\modules\marketplace\components;

use Yii;

/**
 * MercadoLivreService - Integração com Mercado Livre
 */
class MercadoLivreService extends MarketplaceService
{
    protected $marketplaceName = 'MERCADO_LIVRE';

    public function authenticate()
    {
        return true;
    }
    public function refreshToken()
    {
        return true;
    }
    public function syncProdutos($produtoIds = [])
    {
        return ['success' => true];
    }

    public function syncEstoque($produtoId, $quantidade)
    {
        // TODO: Implementar chamada real à API do ML
        Yii::info("MERCADO_LIVRE: Simulando sync de estoque para {$produtoId}: {$quantidade}", 'marketplace');
        return true;
    }

    public function importPedidos($dataInicio = null, $dataFim = null)
    {
        return [];
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
