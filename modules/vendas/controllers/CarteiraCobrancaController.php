<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\CarteiraCobranca;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;

class CarteiraCobrancaController extends Controller
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
        $dataProvider = new ActiveDataProvider([
            'query' => CarteiraCobranca::find()
                ->where(['usuario_id' => Yii::$app->user->id])
                ->with(['cliente', 'cobrador', 'rota']),
            'pagination' => ['pageSize' => 20],
            'sort' => ['defaultOrder' => ['data_distribuicao' => SORT_DESC]],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    protected function findModel($id)
    {
        if (($model = CarteiraCobranca::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('A página solicitada não existe.');
    }
}

