<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\TaxaEntrega;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;

/**
 * Controller para gestão de fretes por Cidade, Bairro e CEP
 */
class TaxaEntregaController extends Controller
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
     * Listagem das regras de frete com suporte a busca
     */
    public function actionIndex()
    {
        $query = TaxaEntrega::find()
            ->where(['usuario_id' => Yii::$app->user->id, 'ativo' => true]);

        // Filtro de busca
        $busca = Yii::$app->request->get('busca');
        if ($busca && trim($busca) !== '') {
            $termo = '%' . trim($busca) . '%';
            $query->andWhere([
                'OR',
                ['ilike', new \yii\db\Expression('unaccent(cidade)'), new \yii\db\Expression('unaccent(:p)', [':p' => $termo])],
                ['ilike', new \yii\db\Expression('unaccent(bairro)'), new \yii\db\Expression('unaccent(:p)', [':p' => $termo])],
                ['ilike', 'cep', $termo],
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => [
                    'cidade' => SORT_ASC,
                    'bairro' => SORT_ASC,
                ]
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'busca' => $busca,
        ]);
    }

    /**
     * Cadastro de nova regra de frete
     */
    public function actionCreate()
    {
        $model = new TaxaEntrega();
        $model->usuario_id = Yii::$app->user->id;
        $model->ativo = true;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Taxa de entrega cadastrada!');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Alteração de regra existente
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Taxa de entrega atualizada!');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Exclusão lógica da regra
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->ativo = false;
        $model->save(false);

        Yii::$app->session->setFlash('success', 'Regra removida!');
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = TaxaEntrega::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('A regra solicitada não existe.');
    }
}
