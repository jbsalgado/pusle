<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\Categoria;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;

class CategoriaController extends Controller
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
     * Lista todas as categorias
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Categoria::find()
                ->where(['usuario_id' => Yii::$app->user->id])
                ->with(['produtos']),
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'ordem' => SORT_ASC,
                    'nome' => SORT_ASC,
                ]
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Exibe uma categoria específica
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        $produtosProvider = new ActiveDataProvider([
            'query' => $model->getProdutos(),
            'pagination' => [
                'pageSize' => 12,
            ],
        ]);

        return $this->render('view', [
            'model' => $model,
            'produtosProvider' => $produtosProvider,
        ]);
    }

    /**
     * Cria uma nova categoria
     */
    public function actionCreate()
    {
        $model = new Categoria();
        $model->usuario_id = Yii::$app->user->id;
        $model->ativo = true;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Categoria cadastrada com sucesso!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Atualiza uma categoria existente
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Categoria atualizada com sucesso!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deleta uma categoria
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        if ($model->getTotalProdutos() > 0) {
            Yii::$app->session->setFlash('error', 'Não é possível excluir uma categoria com produtos associados.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $model->delete();
        Yii::$app->session->setFlash('success', 'Categoria excluída com sucesso!');

        return $this->redirect(['index']);
    }

    /**
     * Busca o modelo baseado no ID
     */
    protected function findModel($id)
    {
        if (($model = Categoria::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('A categoria solicitada não existe.');
    }
}