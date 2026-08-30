<?php

namespace app\modules\marketplace\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use app\modules\marketplace\models\MarketplaceConfig;
use app\modules\marketplace\models\MarketplaceSyncLog;
use app\components\TelegramHelper;

/**
 * SyncEstoqueMarketplaceJob - Job assíncrono resiliente para propagação de estoque com Rate Limiting e Backoff Exponencial
 */
class SyncEstoqueMarketplaceJob extends BaseObject implements JobInterface
{
    /** @var string UUID do tenant/dono da loja */
    public $tenantId;

    /** @var string UUID do produto local */
    public $produtoId;

    /** @var int Nova quantidade de estoque */
    public $novoEstoque;

    /** @var int Contador de tentativas de retry */
    public $tentativa = 1;

    /**
     * Execução do Job pelo worker com tratamento de Rate Limit (HTTP 429) e Backoff
     * @param \yii\queue\Queue $queue
     */
    public function execute($queue)
    {
        Yii::info(sprintf(
            "[SyncEstoqueMarketplaceJob] Iniciando sync de estoque (Tentativa %d). Tenant: %s, Produto: %s, Qtd: %d",
            $this->tentativa,
            $this->tenantId,
            $this->produtoId,
            $this->novoEstoque
        ), 'marketplace');

        $configs = MarketplaceConfig::find()
            ->where(['usuario_id' => $this->tenantId, 'ativo' => true, 'sincronizar_estoque' => true])
            ->all();

        if (empty($configs)) {
            Yii::info("[SyncEstoqueMarketplaceJob] Nenhuma integração de marketplace ativa para o tenant {$this->tenantId}.", 'marketplace');
            return;
        }

        $precisaRetry = false;

        foreach ($configs as $config) {
            $inicio = microtime(true);
            $service = $this->getService($config);

            if (!$service) {
                Yii::warning("[SyncEstoqueMarketplaceJob] Serviço não encontrado para marketplace {$config->marketplace}", 'marketplace');
                continue;
            }

            try {
                $sucesso = $service->syncEstoque($this->produtoId, $this->novoEstoque);
                $tempoMs = (int)((microtime(true) - $inicio) * 1000);

                $this->registrarLog(
                    $config,
                    $sucesso ? MarketplaceSyncLog::STATUS_SUCESSO : MarketplaceSyncLog::STATUS_ERRO,
                    $sucesso ? "Estoque sincronizado com sucesso ({$this->novoEstoque} un)" : "Falha retornada pelo marketplace",
                    ['produto_id' => $this->produtoId, 'quantidade' => $this->novoEstoque, 'tentativa' => $this->tentativa],
                    $tempoMs
                );

                if (!$sucesso) {
                    $this->notificarErro($config, "Falha na sincronização de estoque no {$config->getMarketplaceNome()}");
                }
            } catch (\Throwable $e) {
                $tempoMs = (int)((microtime(true) - $inicio) * 1000);
                $mensagem = $e->getMessage();
                $isRateLimit = (strpos($mensagem, '429') !== false || strpos($mensagem, 'Too Many Requests') !== false || strpos($mensagem, '503') !== false);

                Yii::error(sprintf(
                    "[SyncEstoqueMarketplaceJob] Erro na sincronização com %s (RateLimit=%s): %s",
                    $config->marketplace,
                    $isRateLimit ? 'SIM' : 'NAO',
                    $mensagem
                ), 'marketplace');

                $this->registrarLog(
                    $config,
                    MarketplaceSyncLog::STATUS_ERRO,
                    ($isRateLimit ? "[Rate Limit 429] " : "") . "Exceção: " . $mensagem,
                    ['produto_id' => $this->produtoId, 'quantidade' => $this->novoEstoque, 'tentativa' => $this->tentativa],
                    $tempoMs
                );

                if ($isRateLimit && $this->tentativa < 5) {
                    $precisaRetry = true;
                } else {
                    $this->notificarErro($config, $mensagem);
                }
            }
        }

        // Backoff exponencial para reagendamento se atingiu Rate Limit
        if ($precisaRetry && $this->tentativa < 5 && $queue) {
            $delaySegundos = pow(2, $this->tentativa) * 15; // 30s, 60s, 120s, 240s
            Yii::warning(sprintf(
                "[SyncEstoqueMarketplaceJob] Rate limit detectado. Reenfileirando job com delay de %d segundos (Próxima tentativa: %d).",
                $delaySegundos,
                $this->tentativa + 1
            ), 'marketplace');

            $queue->delay($delaySegundos)->push(new self([
                'tenantId' => $this->tenantId,
                'produtoId' => $this->produtoId,
                'novoEstoque' => $this->novoEstoque,
                'tentativa' => $this->tentativa + 1,
            ]));
        }
    }

    /**
     * Instancia o adapter correto de acordo com a configuração
     */
    protected function getService(MarketplaceConfig $config)
    {
        $map = [
            MarketplaceConfig::MARKETPLACE_MERCADO_LIVRE => 'app\modules\marketplace\components\MercadoLivreService',
            MarketplaceConfig::MARKETPLACE_SHOPEE => 'app\modules\marketplace\components\ShopeeService',
            MarketplaceConfig::MARKETPLACE_MAGAZINE_LUIZA => 'app\modules\marketplace\components\MagaluService',
            MarketplaceConfig::MARKETPLACE_TEMU => 'app\modules\marketplace\components\TemuService',
            MarketplaceConfig::MARKETPLACE_IFOOD => 'app\modules\marketplace\components\IFoodService',
        ];

        $class = $map[$config->marketplace] ?? null;
        if ($class && class_exists($class)) {
            $service = new $class();
            $service->setConfig($config->attributes);
            return $service;
        }

        return null;
    }

    /**
     * Registra o log no banco de dados
     */
    protected function registrarLog(MarketplaceConfig $config, string $status, string $mensagem, array $detalhes = [], int $tempoMs = 0)
    {
        try {
            $log = new MarketplaceSyncLog([
                'marketplace' => $config->marketplace,
                'tipo' => MarketplaceSyncLog::TIPO_ESTOQUE,
                'status' => $status,
                'mensagem' => $mensagem,
                'detalhes' => $detalhes,
                'tempo_execucao_ms' => $tempoMs,
            ]);
            $log->save(false);
        } catch (\Throwable $e) {
            Yii::error("[SyncEstoqueMarketplaceJob] Falha ao salvar log: " . $e->getMessage(), 'marketplace');
        }
    }

    /**
     * Notifica erro via Telegram se configurado
     */
    protected function notificarErro(MarketplaceConfig $config, string $erro)
    {
        try {
            $msg = sprintf(
                "🚨 *Erro de Sincronização de Estoque*\n*Canal:* %s\n*Loja:* %s\n*Produto:* `%s`\n*Detalhe:* %s",
                $config->getMarketplaceNome(),
                $config->apelido_conta ?: $config->id,
                $this->produtoId,
                $erro
            );
            TelegramHelper::sendAlert($msg);
        } catch (\Throwable $e) {
            // Silencia falha de alerta para não interromper fluxo principal
        }
    }
}
