<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\HistoricoCobranca;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;

class HistoricoCobrancaController extends Controller
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

    /**
     * Lista todo o histórico de cobranças
     */
    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->identity->id ?? Yii::$app->user->id;
        
        $query = HistoricoCobranca::find()
            ->where(['usuario_id' => $usuarioId])
            ->with(['parcela', 'cobrador', 'cliente']);

        // Filtros
        $tipoAcao = Yii::$app->request->get('tipo_acao');
        if ($tipoAcao) {
            $query->andWhere(['tipo_acao' => $tipoAcao]);
        }

        $cobradorId = Yii::$app->request->get('cobrador_id');
        if ($cobradorId) {
            $query->andWhere(['cobrador_id' => $cobradorId]);
        }

        $dataInicio = Yii::$app->request->get('data_inicio');
        if ($dataInicio) {
            $query->andWhere(['>=', 'data_acao', $dataInicio . ' 00:00:00']);
        }

        $dataFim = Yii::$app->request->get('data_fim');
        if ($dataFim) {
            $query->andWhere(['<=', 'data_acao', $dataFim . ' 23:59:59']);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'data_acao' => SORT_DESC,
                ]
            ],
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

    protected function findModel($id)
    {
        $usuarioId = Yii::$app->user->identity->id ?? Yii::$app->user->id;
        if (($model = HistoricoCobranca::findOne(['id' => $id, 'usuario_id' => $usuarioId])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('A página solicitada não existe.');
    }
}

