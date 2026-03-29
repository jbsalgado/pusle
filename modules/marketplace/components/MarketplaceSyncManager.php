<?php

namespace app\modules\marketplace\components;

use Yii;
use yii\base\Component;
use app\modules\marketplace\models\MarketplaceConfig;
use app\components\TelegramHelper;

/**
 * MarketplaceSyncManager - Gerenciador central de sincronização
 * 
 * Orquestra a comunicação entre o sistema Pulse e os diversos marketplaces
 */
class MarketplaceSyncManager extends Component
{
    /**
     * Sincroniza o estoque de um produto em todos os marketplaces ativos do usuário
     */
    public function syncEstoqueGlobal($usuarioId, $produtoId, $quantidade)
    {
        $configs = MarketplaceConfig::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true, 'sincronizar_estoque' => true])
            ->all();

        $resultados = [];

        foreach ($configs as $config) {
            try {
                $service = $this->getService($config);
                if ($service) {
                    $sucesso = $service->syncEstoque($produtoId, $quantidade);
                    $resultados[$config->marketplace] = $sucesso;

                    if (!$sucesso) {
                        $this->sendErrorAlert($config, $produtoId, "Erro retornado pelo serviço");
                    }

                    Yii::info("Sincronização de estoque via SyncManager: Produto {$produtoId} no marketplace {$config->marketplace} -> " . ($sucesso ? 'Sucesso' : 'Erro'), 'marketplace');
                }
            } catch (\Exception $e) {
                Yii::error("Erro ao sincronizar estoque no marketplace {$config->marketplace}: " . $e->getMessage(), 'marketplace');
                $this->sendErrorAlert($config, $produtoId, $e->getMessage());
                $resultados[$config->marketplace] = false;
            }
        }

        return $resultados;
    }

    /**
     * Envia alerta de erro para o Telegram
     */
    protected function sendErrorAlert($config, $produtoId, $errorMsg)
    {
        $marketplace = $config->getMarketplaceNome();
        $msg = "⚠️ *Erro de Sincronização de Estoque*\n\n";
        $msg .= "🏢 *Marketplace:* {$marketplace}\n";
        $msg .= "📦 *ID Produto:* `{$produtoId}`\n";
        $msg .= "❌ *Erro:* {$errorMsg}\n";
        $msg .= "🕒 " . date('d/m/Y H:i:s');

        TelegramHelper::sendMessage($msg);
    }

    /**
     * Factory para obter a instância correta do serviço de marketplace
     */
    protected function getService($config)
    {
        $className = null;

        switch ($config->marketplace) {
            case MarketplaceConfig::MARKETPLACE_MERCADO_LIVRE:
                $className = 'app\modules\marketplace\components\MercadoLivreService';
                break;
            case MarketplaceConfig::MARKETPLACE_SHOPEE:
                $className = 'app\modules\marketplace\components\ShopeeService';
                break;
            case MarketplaceConfig::MARKETPLACE_IFOOD:
                $className = 'app\modules\marketplace\components\IFoodService';
                break;
        }

        if ($className && class_exists($className)) {
            $service = new $className();
            $service->setConfig($config->attributes);
            return $service;
        }

        return null;
    }
}
