<?php

namespace app\modules\marketplace\components;

use Yii;
use yii\base\Component;
use app\modules\marketplace\models\MarketplaceSyncLog;

/**
 * Classe base abstrata para handlers de webhooks de marketplaces
 * 
 * Cada marketplace deve estender esta classe e implementar os métodos abstratos
 */
abstract class BaseWebhookHandler extends Component
{
    /**
     * @var string Nome do marketplace (MERCADO_LIVRE, SHOPEE, etc)
     */
    protected $marketplace;

    /**
     * @var array Configuração do marketplace
     */
    protected $config;

    /**
     * @var int Timestamp de início do processamento
     */
    protected $startTime;

    /**
     * Construtor
     * @param string $marketplace Nome do marketplace
     * @param array $config Configuração do marketplace
     */
    public function __construct($marketplace, $config = [])
    {
        $this->marketplace = $marketplace;
        $this->config = $config;
        parent::__construct();
    }

    /**
     * Processa o webhook recebido
     * @param string $rawBody Corpo bruto da requisição
     * @param array $headers Headers da requisição
     * @return array Resultado do processamento
     */
    public function process($rawBody, $headers = [])
    {
        $this->startTime = microtime(true);

        try {
            // 1. Validar assinatura
            if (!$this->validateSignature($rawBody, $headers)) {
                return $this->logError('Assinatura inválida', [
                    'headers' => $headers,
                ]);
            }

            // 2. Decodificar payload
            $payload = $this->decodePayload($rawBody);
            if (!$payload) {
                return $this->logError('Payload inválido', [
                    'raw_body' => $rawBody,
                ]);
            }

            // 3. Identificar tipo de evento
            $eventType = $this->getEventType($payload);
            if (!$eventType) {
                return $this->logError('Tipo de evento não identificado', [
                    'payload' => $payload,
                ]);
            }

            // 4. Processar evento
            $result = $this->processEvent($eventType, $payload);

            // 5. Registrar sucesso
            return $this->logSuccess($eventType, $result);
        } catch (\Exception $e) {
            Yii::error($e->getMessage(), __METHOD__);
            return $this->logError($e->getMessage(), [
                'exception' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Valida a assinatura do webhook
     * @param string $rawBody Corpo bruto
     * @param array $headers Headers
     * @return bool
     */
    abstract protected function validateSignature($rawBody, $headers);

    /**
     * Decodifica o payload do webhook
     * @param string $rawBody Corpo bruto
     * @return array|null
     */
    protected function decodePayload($rawBody)
    {
        $payload = json_decode($rawBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Yii::error('Erro ao decodificar JSON: ' . json_last_error_msg(), __METHOD__);
            return null;
        }

        return $payload;
    }

    /**
     * Identifica o tipo de evento do webhook
     * @param array $payload Payload decodificado
     * @return string|null
     */
    abstract protected function getEventType($payload);

    /**
     * Processa o evento específico
     * @param string $eventType Tipo do evento
     * @param array $payload Dados do evento
     * @return array Resultado do processamento
     */
    abstract protected function processEvent($eventType, $payload);

    /**
     * Registra log de sucesso
     * @param string $eventType Tipo do evento
     * @param array $result Resultado do processamento
     * @return array
     */
    protected function logSuccess($eventType, $result)
    {
        $executionTime = (int)((microtime(true) - $this->startTime) * 1000);

        $log = new MarketplaceSyncLog();
        $log->usuario_id = $this->config['usuario_id'] ?? null;
        $log->marketplace = $this->marketplace;
        $log->tipo_sync = 'WEBHOOK';
        $log->status = 'SUCESSO';
        $log->itens_processados = 1;
        $log->itens_sucesso = 1;
        $log->itens_erro = 0;
        $log->mensagem = "Webhook processado: {$eventType}";
        $log->detalhes = [
            'event_type' => $eventType,
            'result' => $result,
        ];
        $log->tempo_execucao_ms = $executionTime;
        $log->data_fim = new \yii\db\Expression('NOW()');
        $log->save();

        Yii::info("Webhook {$this->marketplace} processado com sucesso: {$eventType}", __METHOD__);

        return [
            'success' => true,
            'event_type' => $eventType,
            'execution_time_ms' => $executionTime,
            'result' => $result,
        ];
    }

    /**
     * Registra log de erro
     * @param string $message Mensagem de erro
     * @param array $details Detalhes adicionais
     * @return array
     */
    protected function logError($message, $details = [])
    {
        $executionTime = (int)((microtime(true) - $this->startTime) * 1000);

        $log = new MarketplaceSyncLog();
        $log->usuario_id = $this->config['usuario_id'] ?? null;
        $log->marketplace = $this->marketplace;
        $log->tipo_sync = 'WEBHOOK';
        $log->status = 'ERRO';
        $log->itens_processados = 1;
        $log->itens_sucesso = 0;
        $log->itens_erro = 1;
        $log->mensagem = $message;
        $log->detalhes = $details;
        $log->tempo_execucao_ms = $executionTime;
        $log->data_fim = new \yii\db\Expression('NOW()');
        $log->save();

        Yii::error("Erro ao processar webhook {$this->marketplace}: {$message}", __METHOD__);

        return [
            'success' => false,
            'error' => $message,
            'execution_time_ms' => $executionTime,
            'details' => $details,
        ];
    }

    /**
     * Obtém configuração do marketplace do banco de dados
     * @param string $marketplace Nome do marketplace
     * @param string $usuarioId ID do usuário (opcional)
     * @return \app\modules\marketplace\models\MarketplaceConfig|null
     */
    protected function getMarketplaceConfig($marketplace, $usuarioId = null)
    {
        $query = \app\modules\marketplace\models\MarketplaceConfig::find()
            ->where(['marketplace' => $marketplace]);

        if ($usuarioId) {
            $query->andWhere(['usuario_id' => $usuarioId]);
        }

        return $query->one();
    }
}
