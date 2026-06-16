<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\UnidadeMedidaVolume;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * UnidadeMedidaController implements the CRUD actions for UnidadeMedidaVolume model.
 */
class UnidadeMedidaController extends \yii\web\Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all UnidadeMedidaVolume models.
     * @return mixed
     */
    public function actionIndex()
    {
        $nome = Yii::$app->request->get('nome');
        $descricao = Yii::$app->request->get('descricao');
        $ativo = Yii::$app->request->get('ativo');

        $query = UnidadeMedidaVolume::find();

        if ($nome) {
            $query->andWhere(new \yii\db\Expression(
                "unaccent(nome) ILIKE unaccent(:n)",
                [':n' => "%{$nome}%"]
            ));
        }

        if ($descricao) {
            $query->andWhere(new \yii\db\Expression(
                "unaccent(descricao) ILIKE unaccent(:d)",
                [':d' => "%{$descricao}%"]
            ));
        }

        if ($ativo !== null && $ativo !== '') {
            $query->andWhere(['ativo' => $ativo]);
        }

        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => [
                    'nome' => SORT_ASC,
                ],
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'filtros' => [
                'nome' => $nome,
                'descricao' => $descricao,
                'ativo' => $ativo,
            ]
        ]);
    }

    /**
     * Displays a single UnidadeMedidaVolume model.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new UnidadeMedidaVolume model.
     * If creation is successful, the browser will be redirected to the 'index' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new UnidadeMedidaVolume();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Unidade de medida criada com sucesso!');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing UnidadeMedidaVolume model.
     * If update is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Unidade de medida atualizada com sucesso!');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing UnidadeMedidaVolume model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Unidade de medida excluída com sucesso!');
        return $this->redirect(['index']);
    }

    /**
     * Finds the UnidadeMedidaVolume model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return UnidadeMedidaVolume the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = UnidadeMedidaVolume::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('A unidade de medida solicitada não existe.');
    }
}
