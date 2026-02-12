<?php

namespace app\modules\cobranca\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use app\modules\cobranca\models\CobrancaConfiguracao;
use app\modules\cobranca\components\WhatsAppService;

/**
 * ConfiguracaoController
 * 
 * Gerencia as configurações de automação de cobranças
 */
class ConfiguracaoController extends Controller
{
    public $layout = 'main';

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Exibe e atualiza as configurações
     */
    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;
        $model = CobrancaConfiguracao::getOrCreateForUser($usuarioId);

        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Configurações salvas com sucesso!');
                return $this->refresh();
            } else {
                Yii::$app->session->setFlash('error', 'Erro ao salvar configurações.');
            }
        }

        return $this->render('index', [
            'model' => $model,
        ]);
    }

    /**
     * Testa a conexão com WhatsApp
     */
    public function actionTestarConexao()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $usuarioId = Yii::$app->user->id;
        $config = CobrancaConfiguracao::findOne(['usuario_id' => $usuarioId]);

        if (!$config) {
            return [
                'success' => false,
                'message' => 'Configuração não encontrada. Salve as configurações primeiro.',
            ];
        }

        if (!$config->hasCredentials()) {
            return [
                'success' => false,
                'message' => 'Credenciais não configuradas.',
            ];
        }

        $whatsapp = new WhatsAppService($config);
        return $whatsapp->testarConexao();
    }
}
