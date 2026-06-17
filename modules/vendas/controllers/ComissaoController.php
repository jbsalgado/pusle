<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\Comissao;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;

class ComissaoController extends Controller
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
                    'marcar-paga' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lista todas as comissões
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Comissao::find()
                ->where(['usuario_id' => Yii::$app->user->id])
                ->with(['colaborador', 'venda', 'parcela']),
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'data_criacao' => SORT_DESC,
                ]
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Exibe uma comissão específica
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Cria uma nova comissão
     */
    public function actionCreate()
    {
        $model = new Comissao();
        $model->usuario_id = Yii::$app->user->id;
        $model->status = Comissao::STATUS_PENDENTE;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Comissão cadastrada com sucesso!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Atualiza uma comissão existente
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Comissão atualizada com sucesso!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deleta uma comissão
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->delete();
        
        Yii::$app->session->setFlash('success', 'Comissão excluída com sucesso!');
        return $this->redirect(['index']);
    }

    /**
     * Marca comissão como paga
     */
    public function actionMarcarPaga($id)
    {
        $model = $this->findModel($id);
        
        if ($model->marcarComoPaga()) {
            Yii::$app->session->setFlash('success', 'Comissão marcada como paga!');
        } else {
            Yii::$app->session->setFlash('error', 'Erro ao marcar comissão como paga.');
        }
        
        return $this->redirect(['view', 'id' => $id]);
    }

    protected function findModel($id)
    {
        if (($model = Comissao::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('A página solicitada não existe.');
    }
}

