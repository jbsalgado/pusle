<?php

namespace app\modules\evolution\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\modules\evolution\models\WhatsappConfig;
use app\modules\vendas\models\DisparoMassa;

/**
 * WebhookController — Receptor de webhooks e eventos assíncronos da Evolution API Go.
 *
 * Trata eventos de mudança de estado de conexão (connection.update) e desconexão
 * para pausar imediatamente campanhas ativas e blindar o sistema contra erros em cascata.
 */
class WebhookController extends Controller
{
    /**
     * Desabilita validação CSRF para permitir recebimento de webhooks POST da Evolution API.
     */
    public $enableCsrfValidation = false;

    /**
     * Endpoint padrão para recepção de webhooks.
     * POST /evolution/webhook/index
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $rawBody = Yii::$app->request->getRawBody();
        if (empty($rawBody)) {
            return $this->asJson(['status' => 'ignored', 'reason' => 'empty_body']);
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            return $this->asJson(['status' => 'ignored', 'reason' => 'invalid_json']);
        }

        $event = $payload['event'] ?? $payload['type'] ?? null;
        $instanceName = $payload['instance'] ?? $payload['instanceName'] ?? null;

        if (empty($instanceName)) {
            // Tenta obter da estrutura de data
            $instanceName = $payload['data']['instanceName'] ?? $payload['data']['instance'] ?? null;
        }

        if (empty($instanceName)) {
            return $this->asJson(['status' => 'ignored', 'reason' => 'missing_instance']);
        }

        $config = WhatsappConfig::findOne(['instance_name' => $instanceName]);
        if (!$config) {
            return $this->asJson(['status' => 'ignored', 'reason' => 'instance_not_found']);
        }

        // Tratamento de eventos de conexão
        $eventLower = strtolower((string)$event);
        if ($eventLower === 'connection.update' || $eventLower === 'connection_update' || $eventLower === 'status.instance') {
            $data = $payload['data'] ?? [];
            $state = strtolower((string)($data['state'] ?? $data['status'] ?? $data['connection'] ?? ''));

            if (in_array($state, ['close', 'closed', 'disconnected', 'unpaired', 'refused'])) {
                $config->status = 'DISCONNECTED';
                $config->save(false);

                // Pausa imediatamente campanhas em andamento desta empresa
                $this->pausarCampanhasDaEmpresa($config->empresa_id);

                Yii::warning("Evolution Webhook: Instância '{$instanceName}' desconectada (estado: {$state}). Campanhas ativas foram pausadas.", __METHOD__);
            } elseif (in_array($state, ['open', 'connected', 'connecting'])) {
                if ($state === 'open' || $state === 'connected') {
                    $config->status = 'CONNECTED';
                    $config->save(false);
                    Yii::info("Evolution Webhook: Instância '{$instanceName}' conectada com sucesso.", __METHOD__);
                }
            }
        }

        return $this->asJson(['status' => 'success', 'event' => $event]);
    }

    /**
     * Pausa todas as campanhas em andamento de uma empresa para evitar tentativas contra socket desconectado.
     *
     * @param string $empresaId
     */
    private function pausarCampanhasDaEmpresa(string $empresaId): void
    {
        try {
            $campanhas = DisparoMassa::find()
                ->where([
                    'usuario_id' => $empresaId,
                    'status'     => [DisparoMassa::STATUS_PENDENTE, DisparoMassa::STATUS_PROCESSANDO]
                ])
                ->all();

            foreach ($campanhas as $campanha) {
                $campanha->status = DisparoMassa::STATUS_PAUSADO;
                $campanha->save(false);
            }
        } catch (\Throwable $t) {
            Yii::error("WebhookController::pausarCampanhasDaEmpresa — Erro ao pausar campanhas: " . $t->getMessage(), __METHOD__);
        }
    }
}
