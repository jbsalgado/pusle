<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\Configuracao;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\db\Expression;

class ConfiguracaoController extends Controller
{
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
                    'update' => ['POST', 'GET'],
                ],
            ],
        ];
    }

    /**
     * Exibe ou redireciona para edição da configuração
     */
    public function actionIndex()
    {
        $model = Configuracao::getConfiguracaoAtual();
        return $this->redirect(['view']);
    }

    /**
     * Exibe a configuração atual
     */
    public function actionView()
    {
        $model = Configuracao::getConfiguracaoAtual();
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Atualiza a configuração
     */
    public function actionUpdate()
    {
        $model = Configuracao::getConfiguracaoAtual();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Configurações atualizadas com sucesso!');
            return $this->redirect(['view']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Cria configuração inicial (se não existir)
     */
    public function actionCreate()
    {
        $usuarioId = Yii::$app->user->id;
        
        // Verifica se já existe
        $existing = Configuracao::findOne(['usuario_id' => $usuarioId]);
        if ($existing) {
            Yii::$app->session->setFlash('info', 'Você já possui uma configuração. Redirecionando para edição...');
            return $this->redirect(['update']);
        }

        $model = new Configuracao();
        $model->usuario_id = $usuarioId;
        $model->cor_primaria = '#3B82F6';
        $model->cor_secundaria = '#10B981';
        $model->catalogo_publico = false;
        $model->aceita_orcamentos = true;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Configurações criadas com sucesso!');
            return $this->redirect(['view']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }
}

