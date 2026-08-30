<?php

namespace app\modules\marketplace\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use app\modules\marketplace\models\MarketplaceConfig;
use app\modules\marketplace\models\MarketplaceSyncLog;
use app\components\TelegramHelper;

/**
 * ProcessarWebhookJob - Job assíncrono para processar o payload de webhooks dos marketplaces
 */
class ProcessarWebhookJob extends BaseObject implements JobInterface
{
    /** @var string Nome do marketplace (MERCADO_LIVRE, SHOPEE, MAGAZINE_LUIZA, TEMU, etc.) */
    public $marketplace;

    /** @var string UUID da configuração do marketplace */
    public $configId;

    /** @var string Payload bruto recebido no webhook */
    public $rawBody;

    /** @var array Headers recebidos na requisição HTTP */
    public $headers;

    /**
     * Execução assíncrona do processamento
     * @param \yii\queue\Queue $queue
     */
    public function execute($queue)
    {
        $inicio = microtime(true);

        Yii::info(sprintf(
            "[ProcessarWebhookJob] Processando webhook assíncrono de %s (Config ID: %s)",
            $this->marketplace,
            $this->configId
        ), 'marketplace');

        $config = MarketplaceConfig::findOne($this->configId);
        if (!$config) {
            Yii::error("[ProcessarWebhookJob] Configuração ID {$this->configId} não encontrada.", 'marketplace');
            return;
        }

        $handler = $this->getHandler($this->marketplace, $config);
        if (!$handler) {
            Yii::error("[ProcessarWebhookJob] Handler não disponível para marketplace {$this->marketplace}.", 'marketplace');
            return;
        }

        try {
            $result = $handler->process($this->rawBody, $this->headers);
            $tempoMs = (int)((microtime(true) - $inicio) * 1000);

            $this->registrarLog(
                $config,
                $result['success'] ? MarketplaceSyncLog::STATUS_SUCESSO : MarketplaceSyncLog::STATUS_ERRO,
                $result['success'] ? "Webhook processado: " . ($result['message'] ?? 'Sucesso') : ($result['error'] ?? 'Erro no webhook'),
                ['result' => $result, 'body' => json_decode($this->rawBody, true)],
                $tempoMs
            );
        } catch (\Throwable $e) {
            $tempoMs = (int)((microtime(true) - $inicio) * 1000);
            Yii::error("[ProcessarWebhookJob] Erro crítico no webhook de {$this->marketplace}: " . $e->getMessage(), 'marketplace');

            $this->registrarLog(
                $config,
                MarketplaceSyncLog::STATUS_ERRO,
                "Exceção ao processar webhook: " . $e->getMessage(),
                ['raw_body' => $this->rawBody, 'trace' => $e->getTraceAsString()],
                $tempoMs
            );
        }
    }

    /**
     * Instancia o handler do webhook
     */
    protected function getHandler(string $marketplace, MarketplaceConfig $config)
    {
        $handlerConfig = [
            'usuario_id' => $config->usuario_id,
            'client_id' => $config->client_id,
            'client_secret' => $config->client_secret,
            'access_token' => $config->access_token,
            'seller_id_externo' => $config->seller_id_externo,
            'config_id' => $config->id,
        ];

        switch ($marketplace) {
            case MarketplaceConfig::MARKETPLACE_MERCADO_LIVRE:
                return new \app\modules\marketplace\components\MercadoLivreWebhookHandler($marketplace, $handlerConfig);

            case MarketplaceConfig::MARKETPLACE_SHOPEE:
                return new \app\modules\marketplace\components\ShopeeWebhookHandler($marketplace, $handlerConfig);

            case MarketplaceConfig::MARKETPLACE_IFOOD:
                return new \app\modules\marketplace\components\IFoodWebhookHandler($marketplace, $handlerConfig);

            default:
                return null;
        }
    }

    /**
     * Grava log de auditoria
     */
    protected function registrarLog(MarketplaceConfig $config, string $status, string $mensagem, array $detalhes, int $tempoMs): void
    {
        $log = new MarketplaceSyncLog();
        $log->usuario_id = $config->usuario_id;
        $log->marketplace = $config->marketplace;
        $log->tipo_sync = MarketplaceSyncLog::TIPO_WEBHOOK;
        $log->status = $status;
        $log->mensagem = $mensagem;
        $log->detalhes = $detalhes;
        $log->tempo_execucao_ms = $tempoMs;
        $log->itens_processados = 1;
        $log->itens_sucesso = ($status === MarketplaceSyncLog::STATUS_SUCESSO) ? 1 : 0;
        $log->itens_erro = ($status === MarketplaceSyncLog::STATUS_ERRO) ? 1 : 0;
        $log->save();
    }
}
