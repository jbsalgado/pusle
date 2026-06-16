<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\PeriodoCobranca;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;

class PeriodoCobrancaController extends Controller
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
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->identity->id ?? Yii::$app->user->id;
        
        $dataProvider = new ActiveDataProvider([
            'query' => PeriodoCobranca::find()
                ->where(['usuario_id' => $usuarioId])
                ->orderBy(['ano_referencia' => SORT_DESC, 'mes_referencia' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new PeriodoCobranca();
        $model->usuario_id = Yii::$app->user->identity->id ?? Yii::$app->user->id;
        $model->status = PeriodoCobranca::STATUS_ABERTO;
        
        // Define valores padrão
        $hoje = new \DateTime();
        $model->ano_referencia = (int)$hoje->format('Y');
        $model->mes_referencia = (int)$hoje->format('n');
        $model->data_inicio = $hoje->format('Y-m-d');
        $ultimoDia = new \DateTime($hoje->format('Y-m-t'));
        $model->data_fim = $ultimoDia->format('Y-m-d');

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Período de cobrança criado com sucesso.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Período de cobrança atualizado com sucesso.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Período de cobrança excluído com sucesso.');
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        $usuarioId = Yii::$app->user->identity->id ?? Yii::$app->user->id;
        if (($model = PeriodoCobranca::findOne(['id' => $id, 'usuario_id' => $usuarioId])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('A página solicitada não existe.');
    }
}

