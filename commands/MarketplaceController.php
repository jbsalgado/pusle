<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\modules\marketplace\models\MarketplaceConfig;
use app\modules\marketplace\components\MercadoLivreService;
use app\modules\marketplace\components\ShopeeService;

/**
 * MarketplaceController - Comandos de Console e Tarefas Agendadas (Cron) do Marketplace Hub
 */
class MarketplaceController extends Controller
{
    /**
     * Renova tokens OAuth que estão expirando nos próximos 30 minutos
     * Uso no crontab: * /15 * * * * php /path/to/yii marketplace/refresh-tokens
     */
    public function actionRefreshTokens()
    {
        $this->stdout("Iniciando verificação de tokens de marketplace...\n");

        $configs = MarketplaceConfig::find()
            ->where(['ativo' => true])
            ->andWhere(['in', 'marketplace', [MarketplaceConfig::MARKETPLACE_MERCADO_LIVRE, MarketplaceConfig::MARKETPLACE_SHOPEE]])
            ->all();

        $renovados = 0;

        foreach ($configs as $config) {
            if ($config->isTokenExpired()) {
                $this->stdout("Renovando token de {$config->getMarketplaceNome()} (Conta: {$config->apelido_conta}, ID: {$config->id})...\n");

                $service = null;
                if ($config->marketplace === MarketplaceConfig::MARKETPLACE_MERCADO_LIVRE) {
                    $service = new MercadoLivreService();
                } elseif ($config->marketplace === MarketplaceConfig::MARKETPLACE_SHOPEE) {
                    $service = new ShopeeService();
                }

                if ($service) {
                    $service->setConfig($config->attributes);
                    if ($service->refreshToken()) {
                        $this->stdout("  [OK] Token renovado com sucesso!\n");
                        $renovados++;
                    } else {
                        $this->stderr("  [ERRO] Falha ao renovar token.\n");
                    }
                }
            }
        }

        $this->stdout("Concluído. Total de tokens renovados: {$renovados}.\n");
        return ExitCode::OK;
    }

    /**
     * Importação de contingência de pedidos recentes (caso webhooks oscilem)
     * Uso no crontab: 0 * /2 * * * php /path/to/yii marketplace/sync-orders
     */
    public function actionSyncOrders()
    {
        $this->stdout("Iniciando sincronização periódica de pedidos dos marketplaces...\n");

        $configs = MarketplaceConfig::find()
            ->where(['ativo' => true, 'sincronizar_pedidos' => true])
            ->all();

        foreach ($configs as $config) {
            $this->stdout("Buscando pedidos recentes em {$config->getMarketplaceNome()} ({$config->apelido_conta})...\n");

            $serviceClass = null;
            switch ($config->marketplace) {
                case MarketplaceConfig::MARKETPLACE_MERCADO_LIVRE:
                    $serviceClass = MercadoLivreService::class;
                    break;
                case MarketplaceConfig::MARKETPLACE_SHOPEE:
                    $serviceClass = ShopeeService::class;
                    break;
                case MarketplaceConfig::MARKETPLACE_MAGAZINE_LUIZA:
                    $serviceClass = \app\modules\marketplace\components\MagaluService::class;
                    break;
                case MarketplaceConfig::MARKETPLACE_TEMU:
                    $serviceClass = \app\modules\marketplace\components\TemuService::class;
                    break;
            }

            if ($serviceClass && class_exists($serviceClass)) {
                try {
                    $service = new $serviceClass();
                    $service->setConfig($config->attributes);
                    $pedidos = $service->importPedidos(date('Y-m-d', strtotime('-2 days')), date('Y-m-d'));
                    $count = count($pedidos);
                    $this->stdout("  [OK] {$count} pedidos verificados/processados.\n");
                } catch (\Throwable $e) {
                    $this->stderr("  [ERRO] Falha ao importar pedidos: " . $e->getMessage() . "\n");
                }
            }
        }

        return ExitCode::OK;
    }
}
