<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\LojaConfiguracao;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * Controller para gerenciar configurações da loja
 */
class LojaConfiguracaoController extends Controller
{
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
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Exibe/edita configuração da loja do usuário logado
     * @return mixed
     */
    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;

        // Busca ou cria configuração
        $model = LojaConfiguracao::findOne(['usuario_id' => $usuarioId]);

        if (!$model) {
            $model = new LojaConfiguracao();
            $model->usuario_id = $usuarioId;
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Configuração salva com sucesso!');
            return $this->redirect(['index']);
        }

        return $this->render('index', [
            'model' => $model,
        ]);
    }
}
