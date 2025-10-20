<?php
namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\FormaPagamento;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * FormaPagamentoController implementa as ações CRUD para o model FormaPagamento.
 */
class FormaPagamentoController extends Controller
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
     * Lista todos os FormaPagamento models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => FormaPagamento::find()
                ->where(['usuario_id' => Yii::$app->user->id])
                ->orderBy(['nome' => SORT_ASC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $viewMode = Yii::$app->request->get('view', 'cards');

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'viewMode' => $viewMode,
        ]);
    }

    /**
     * Exibe um único FormaPagamento model.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException se o model não for encontrado
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Cria um novo FormaPagamento model.
     * Se a criação for bem-sucedida, redireciona para a página 'view'.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new FormaPagamento();
        $model->usuario_id = Yii::$app->user->id;
        $model->ativo = true;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Forma de pagamento criada com sucesso!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Atualiza um FormaPagamento model existente.
     * Se a atualização for bem-sucedida, redireciona para a página 'view'.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException se o model não for encontrado
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Forma de pagamento atualizada com sucesso!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deleta um FormaPagamento model existente.
     * Se a exclusão for bem-sucedida, redireciona para a página 'index'.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException se o model não for encontrado
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        // Verifica se existem parcelas associadas
        if ($model->getParcelas()->count() > 0) {
            Yii::$app->session->setFlash('error', 'Não é possível excluir esta forma de pagamento pois existem parcelas associadas.');
            return $this->redirect(['index']);
        }

        $model->delete();
        Yii::$app->session->setFlash('success', 'Forma de pagamento excluída com sucesso!');

        return $this->redirect(['index']);
    }

    /**
     * Encontra o FormaPagamento model baseado no valor da chave primária.
     * Se o model não for encontrado, uma exceção 404 HTTP será lançada.
     * @param string $id
     * @return FormaPagamento o model carregado
     * @throws NotFoundHttpException se o model não for encontrado
     */
    protected function findModel($id)
    {
        $model = FormaPagamento::findOne([
            'id' => $id,
            'usuario_id' => Yii::$app->user->id
        ]);

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('A página solicitada não existe.');
    }
}