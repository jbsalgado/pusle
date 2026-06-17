<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\Regiao;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;

class RegiaoController extends Controller
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
     * Lista todas as regiões
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Regiao::find()
                ->where(['usuario_id' => Yii::$app->user->id]),
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'nome' => SORT_ASC,
                ]
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Cria uma nova região
     */
    public function actionCreate()
    {
        $model = new Regiao();
        $model->usuario_id = Yii::$app->user->id;
        $model->ativo = true;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Região criada com sucesso!');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Atualiza uma região existente
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Região atualizada com sucesso!');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deleta (desativa) uma região
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        // Exclusão física ou lógica? O model sugere lógica ($ativo)
        // Mas vamos implementar lógica por segurança, como no ClientesController
        $model->ativo = false;
        if ($model->save(false)) {
            Yii::$app->session->setFlash('success', 'Região excluída com sucesso.');
        } else {
            Yii::$app->session->setFlash('error', 'Erro ao excluir região.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Encontra o model baseado no id
     */
    protected function findModel($id)
    {
        if (($model = Regiao::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('A página solicitada não existe.');
    }
}
