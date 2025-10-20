<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\StatusParcela;
use app\modules\vendas\search\StatusParcelaSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * StatusParcelaController implementa as ações CRUD para o modelo StatusParcela.
 */
class StatusParcelaController extends Controller
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
     * Lista todos os modelos StatusParcela.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new StatusParcelaSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Exibe um único modelo StatusParcela.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException se o modelo não for encontrado
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Cria um novo modelo StatusParcela.
     * Se a criação for bem-sucedida, o navegador será redirecionado para a página 'view'.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new StatusParcela();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Status de Parcela criado com sucesso.');
            return $this->redirect(['view', 'id' => $model->codigo]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Atualiza um modelo StatusParcela existente.
     * Se a atualização for bem-sucedida, o navegador será redirecionado para a página 'view'.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException se o modelo não for encontrado
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Status de Parcela atualizado com sucesso.');
            return $this->redirect(['view', 'id' => $model->codigo]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Apaga um modelo StatusParcela existente.
     * Se a exclusão for bem-sucedida, o navegador será redirecionado para a página 'index'.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException se o modelo não for encontrado
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Status de Parcela apagado com sucesso.');
        return $this->redirect(['index']);
    }

    /**
     * Encontra o modelo StatusParcela com base no seu valor de chave primária.
     * Se o modelo não for encontrado, uma exceção HTTP 404 será lançada.
     * @param string $id
     * @return StatusParcela o modelo carregado
     * @throws NotFoundHttpException se o modelo não for encontrado
     */
    protected function findModel($id)
    {
        if (($model = StatusParcela::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('A página solicitada não existe.');
    }
}