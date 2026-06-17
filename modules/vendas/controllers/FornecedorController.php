<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\Fornecedor;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * FornecedorController implementa as ações CRUD para o model Fornecedor.
 */
class FornecedorController extends Controller
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
     * Lista todos os Fornecedores com filtros e paginação.
     * @return string
     */
    public function actionIndex()
    {
        $query = Fornecedor::find()
            ->where(['usuario_id' => Yii::$app->user->id]);

        // Filtros
        $busca = Yii::$app->request->get('busca');
        $ativo = Yii::$app->request->get('ativo');

        if ($busca) {
            $query->andWhere([
                'or',
                ['like', 'nome_fantasia', $busca],
                ['like', 'razao_social', $busca],
                ['like', 'cnpj', $busca],
                ['like', 'cpf', $busca],
                ['like', 'email', $busca],
            ]);
        }

        if ($ativo !== null && $ativo !== '') {
            $query->andWhere(['ativo' => $ativo]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'nome_fantasia' => SORT_ASC,
                ]
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Exibe um único modelo Fornecedor.
     * @param string $id
     * @return string
     * @throws NotFoundHttpException se o modelo não for encontrado
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Cria um novo modelo Fornecedor.
     * Se a criação for bem-sucedida, o navegador será redirecionado para a página 'view'.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Fornecedor();
        $model->usuario_id = Yii::$app->user->id;
        $model->ativo = true;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Fornecedor cadastrado com sucesso!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Atualiza um modelo Fornecedor existente.
     * Se a atualização for bem-sucedida, o navegador será redirecionado para a página 'view'.
     * @param string $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException se o modelo não for encontrado
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Fornecedor atualizado com sucesso!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deleta um modelo Fornecedor existente.
     * Se a exclusão for bem-sucedida, o navegador será redirecionado para a página 'index'.
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException se o modelo não for encontrado
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        // Verifica se há compras associadas
        if ($model->compras) {
            Yii::$app->session->setFlash('error', 'Não é possível excluir o fornecedor pois existem compras associadas a ele.');
            return $this->redirect(['index']);
        }
        
        $model->delete();
        Yii::$app->session->setFlash('success', 'Fornecedor excluído com sucesso!');
        return $this->redirect(['index']);
    }

    /**
     * Encontra o modelo Fornecedor baseado no valor da chave primária.
     * Se o modelo não for encontrado, uma exceção HTTP 404 será lançada.
     * @param string $id
     * @return Fornecedor o modelo carregado
     * @throws NotFoundHttpException se o modelo não for encontrado
     */
    protected function findModel($id)
    {
        if (($model = Fornecedor::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('O fornecedor solicitado não existe.');
    }
}

