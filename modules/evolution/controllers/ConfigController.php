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
        $empresaId = Yii::$app->user->identity->getTenantId();
        $service   = new EvolutionService();

        // Consulta o status em tempo real e sincroniza o banco local
        $connected = $service->checkStatus($empresaId);
        $config    = WhatsappConfig::findByEmpresa($empresaId);

        return $this->render('index', [
            'config'    => $config,
            'connected' => $connected,
        ]);
    }

    /**
     * Inicia o fluxo de conexão: cria (ou recria) a instância no motor Go
     * e exibe a view com o QR Code para pareamento.
     */
    public function actionConnect(): string
    {
        $empresaId = Yii::$app->user->identity->getTenantId();
        $service   = new EvolutionService();

        $qrCodeBase64 = $service->createInstance($empresaId);

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
        $empresaId = Yii::$app->user->identity->getTenantId();
        $service   = new EvolutionService();

        $service->deleteInstance($empresaId);

        Yii::$app->session->setFlash('success', 'WhatsApp desconectado com sucesso.');

        return $this->redirect(['index']);
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
            
            $config->delay_min = isset($post['delay_min']) ? (int)$post['delay_min'] : 1500;
            $config->delay_max = isset($post['delay_max']) ? (int)$post['delay_max'] : 2500;
            $config->simular_digitacao = isset($post['simular_digitacao']) ? (int)$post['simular_digitacao'] : 0;

            if ($config->save()) {
                Yii::$app->session->setFlash('success', 'Configurações de anti-banimento salvas com sucesso.');
            } else {
                $errors = implode(', ', $config->getErrorSummary(true));
                Yii::$app->session->setFlash('error', 'Erro ao salvar configurações: ' . $errors);
            }
        }

        return $this->redirect(['index']);
    }
}
