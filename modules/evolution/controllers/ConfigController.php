<?php

namespace app\modules\evolution\controllers;

use app\modules\evolution\models\WhatsappConfig;
use app\modules\evolution\services\EvolutionService;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

/**
 * ConfigController — Painel de gerenciamento da integração WhatsApp por empresa.
 *
 * Todas as ações são restritas a usuários autenticados. O isolamento multi-loja
 * é garantido usando o ID do tenant ativo via Yii::$app->user->identity->id,
 * que corresponde ao campo id (UUID) da tabela prest_usuarios.
 */
class ConfigController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow'   => true,
                        'roles'   => ['@'], // Apenas usuários autenticados
                    ],
                ],
            ],
        ];
    }

    // =========================================================================
    // ACTIONS
    // =========================================================================

    /**
     * Painel central: exibe status atual da integração WhatsApp da empresa logada.
     */
    public function actionIndex(): string
    {
        $connected       = false;
        $config          = null;
        $connectedNumber = null;
        $templates       = [];

        try {
            $empresaId = Yii::$app->user->identity ? Yii::$app->user->identity->getTenantId() : null;
            if ($empresaId) {
                $service   = new EvolutionService();
                $connected = $service->checkStatus($empresaId);
                $config    = WhatsappConfig::findByEmpresa($empresaId);
                if ($connected) {
                    $connectedNumber = $service->getConnectedNumber($empresaId);
                }
                $templates = \app\modules\evolution\models\WhatsappTemplate::find()
                    ->where(['empresa_id' => $empresaId])
                    ->orderBy(['created_at' => SORT_DESC])
                    ->all();
            }
        } catch (\Throwable $t) {
            Yii::error("ConfigController::actionIndex — Erro ao verificar status: " . $t->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('warning', 'Não foi possível comunicar com o serviço do WhatsApp no momento.');
        }

        return $this->render('index', [
            'config'          => $config,
            'connected'       => $connected,
            'connectedNumber' => $connectedNumber,
            'templates'       => $templates,
        ]);
    }

    /**
     * Salva as configurações da API Oficial da Meta (Cloud API).
     */
    public function actionSaveMeta(): Response
    {
        $empresaId = Yii::$app->user->identity->getTenantId();
        $config    = WhatsappConfig::findByEmpresa($empresaId);

        if ($config === null) {
            $config = new WhatsappConfig();
            $config->empresa_id = $empresaId;
            $config->instance_name = 'pulse_empresa_id_' . substr(str_replace('-', '', $empresaId), 0, 12);
        }

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();

            $config->provider                  = $post['provider'] ?? WhatsappConfig::PROVIDER_EVOLUTION;
            $config->meta_waba_id              = !empty($post['meta_waba_id']) ? trim((string)$post['meta_waba_id']) : null;
            $config->meta_phone_number_id      = !empty($post['meta_phone_number_id']) ? trim((string)$post['meta_phone_number_id']) : null;
            $config->meta_access_token         = !empty($post['meta_access_token']) ? trim((string)$post['meta_access_token']) : null;
            $config->meta_webhook_verify_token = !empty($post['meta_webhook_verify_token']) ? trim((string)$post['meta_webhook_verify_token']) : null;

            if ($config->isMetaOficial()) {
                $config->status = 'CONNECTED';
            }

            if ($config->save()) {
                Yii::$app->session->setFlash('success', 'Configurações da API Oficial da Meta salvas com sucesso.');
            } else {
                $errors = implode(', ', $config->getErrorSummary(true));
                Yii::$app->session->setFlash('error', 'Erro ao salvar credenciais Meta: ' . $errors);
            }
        }

        return $this->redirect(['index']);
    }

    /**
     * Sincroniza templates aprovados na conta da Meta (WABA).
     */
    public function actionSyncTemplates(): Response
    {
        $empresaId = Yii::$app->user->identity->getTenantId();
        $config    = WhatsappConfig::findByEmpresa($empresaId);

        if ($config === null || !$config->isMetaOficial()) {
            Yii::$app->session->setFlash('error', 'Configure e salve o WABA ID e Token da Meta antes de sincronizar templates.');
            return $this->redirect(['index']);
        }

        $metaService = new \app\modules\evolution\services\MetaWhatsAppService();
        $result = $metaService->syncTemplates($config->meta_waba_id, $config->meta_access_token, $empresaId);

        if ($result['success']) {
            Yii::$app->session->setFlash('success', $result['message']);
        } else {
            Yii::$app->session->setFlash('error', 'Falha ao sincronizar templates com a Meta: ' . $result['message']);
        }

        return $this->redirect(['index']);
    }

    /**
     * Cria e submete um novo template HSM à Meta
     */
    public function actionCreateTemplate(): Response
    {
        $empresaId = Yii::$app->user->identity->getTenantId();
        $config    = WhatsappConfig::findByEmpresa($empresaId);

        if ($config === null || !$config->isMetaOficial()) {
            Yii::$app->session->setFlash('error', 'Configure a API Oficial da Meta antes de criar templates.');
            return $this->redirect(['index']);
        }

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();

            $template = new \app\modules\evolution\models\WhatsappTemplate();
            $template->empresa_id   = $empresaId;
            $template->name         = strtolower(preg_replace('/[^a-z0-9_]/', '_', trim($post['name'] ?? '')));
            $template->category     = $post['category'] ?? \app\modules\evolution\models\WhatsappTemplate::CATEGORY_UTILITY;
            $template->language     = $post['language'] ?? 'pt_BR';
            $template->header_type  = $post['header_type'] ?? 'NONE';
            $template->header_text  = !empty($post['header_text']) ? trim($post['header_text']) : null;
            $template->body_text    = trim($post['body_text'] ?? '');
            $template->footer_text  = !empty($post['footer_text']) ? trim($post['footer_text']) : null;

            if ($template->validate()) {
                $metaService = new \app\modules\evolution\services\MetaWhatsAppService();
                $submitted = $metaService->createTemplate($config->meta_waba_id, $config->meta_access_token, $template);

                if ($submitted) {
                    Yii::$app->session->setFlash('success', "Template '{$template->name}' submetido à Meta com sucesso para análise.");
                } else {
                    Yii::$app->session->setFlash('error', 'Erro ao submeter template para a Meta: ' . ($metaService->lastError ?: 'Falha na requisição.'));
                }
            } else {
                $errors = implode(', ', $template->getErrorSummary(true));
                Yii::$app->session->setFlash('error', 'Erro de validação do template: ' . $errors);
            }
        }

        return $this->redirect(['index']);
    }

    /**
     * Inicia o fluxo de conexão: cria (ou recria) a instância no motor Go
     * e exibe a view com o QR Code para pareamento.
     */
    public function actionConnect(): string
    {
        $qrCodeBase64 = null;

        try {
            $empresaId = Yii::$app->user->identity ? Yii::$app->user->identity->getTenantId() : null;
            if ($empresaId) {
                $service = new EvolutionService();
                // Deleta a instância antiga para invalidar a sessão desincronizada e forçar novo QR Code
                $service->deleteInstance($empresaId);
                $qrCodeBase64 = $service->createInstance($empresaId);
            }
        } catch (\Throwable $t) {
            Yii::error("ConfigController::actionConnect — Erro ao criar instância: " . $t->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'Não foi possível estabelecer comunicação com a Evolution API. Verifique se o serviço está ativo.');
        }

        return $this->render('connect', [
            'qrCodeBase64' => $qrCodeBase64,
        ]);
    }

    /**
     * Desconecta a instância no motor Go e atualiza o status local para DISCONNECTED.
     * Redireciona de volta para o painel após a operação.
     */
    public function actionDisconnect(): Response
    {
        try {
            $empresaId = Yii::$app->user->identity ? Yii::$app->user->identity->getTenantId() : null;
            if ($empresaId) {
                $service = new EvolutionService();
                $service->deleteInstance($empresaId);
                Yii::$app->session->setFlash('success', 'WhatsApp desconectado com sucesso.');
            }
        } catch (\Throwable $t) {
            Yii::error("ConfigController::actionDisconnect — Erro ao desconectar: " . $t->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'Erro ao desconectar WhatsApp: ' . $t->getMessage());
        }

        return $this->redirect(['/evolution/config/index']);
    }

    /**
     * Endpoint Ajax exclusivo para polling de status em background.
     *
     * Retorna estritamente JSON no formato:
     *   { "connected": true }  ou  { "connected": false }
     *
     * O layout é desabilitado para garantir resposta JSON pura.
     */
    public function actionCheckStatusAjax(): array
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $empresaId = \Yii::$app->user->identity->getTenantId();
        $service   = new EvolutionService();

        $connected = $service->checkStatus($empresaId);

        return ['connected' => $connected];
    }

    /**
     * Salva as configurações de anti-banimento (delay e simulação de digitação).
     */
    public function actionSaveSettings(): Response
    {
        $empresaId = Yii::$app->user->identity->getTenantId();
        $config    = WhatsappConfig::findByEmpresa($empresaId);

        if ($config === null) {
            Yii::$app->session->setFlash('error', 'Nenhuma configuração encontrada para esta empresa.');
            return $this->redirect(['index']);
        }

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            
            $config->delay_min = isset($post['delay_min']) ? (int)$post['delay_min'] : 15000;
            $config->delay_max = isset($post['delay_max']) ? (int)$post['delay_max'] : 45000;
            $config->simular_digitacao = isset($post['simular_digitacao']) ? (int)$post['simular_digitacao'] : 1;
            
            // Proxy dedicado
            $config->proxy_host = !empty($post['proxy_host']) ? trim((string)$post['proxy_host']) : null;
            $config->proxy_user = !empty($post['proxy_user']) ? trim((string)$post['proxy_user']) : null;
            $config->proxy_pass = !empty($post['proxy_pass']) ? trim((string)$post['proxy_pass']) : null;

            // Controle de lotes e pausas
            $config->lote_tamanho = isset($post['lote_tamanho']) ? (int)$post['lote_tamanho'] : 15;
            $config->lote_pausa_segundos = isset($post['lote_pausa_segundos']) ? (int)$post['lote_pausa_segundos'] : 120;

            // Limite diário de mensagens
            $config->limite_diario_mensagens = isset($post['limite_diario_mensagens']) ? (int)$post['limite_diario_mensagens'] : 150;

            if ($config->save()) {
                Yii::$app->session->setFlash('success', 'Configurações de anti-banimento e limites salvas com sucesso.');
            } else {
                $errors = implode(', ', $config->getErrorSummary(true));
                Yii::$app->session->setFlash('error', 'Erro ao salvar configurações: ' . $errors);
            }
        }

        return $this->redirect(['index']);
    }
}
