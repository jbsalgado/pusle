<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\Colaborador;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * ColaboradorController implementa as ações CRUD para o model Colaborador.
 */
class ColaboradorController extends Controller
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
     * Lista todos os Colaboradores com filtros e paginação.
     * @return string
     */
    public function actionIndex()
    {
        $query = Colaborador::find()
            ->where(['usuario_id' => Yii::$app->user->id])
            ->orderBy(['nome_completo' => SORT_ASC]);

        // Aplicar filtros
        $busca = Yii::$app->request->get('busca');
        if ($busca) {
            $query->andFilterWhere(['or',
                ['like', 'nome_completo', $busca],
                ['like', 'cpf', $busca],
                ['like', 'email', $busca],
            ]);
        }

        $papel = Yii::$app->request->get('papel');
        if ($papel === 'vendedor') {
            $query->andWhere(['eh_vendedor' => true]);
        } elseif ($papel === 'cobrador') {
            $query->andWhere(['eh_cobrador' => true]);
        } elseif ($papel === 'ambos') {
            $query->andWhere(['eh_vendedor' => true, 'eh_cobrador' => true]);
        }

        $ativo = Yii::$app->request->get('ativo');
        if ($ativo !== null && $ativo !== '') {
            $query->andWhere(['ativo' => (int)$ativo]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'nome_completo' => SORT_ASC,
                ]
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Exibe um único modelo Colaborador.
     * @param string $id
     * @return string
     * @throws NotFoundHttpException se o modelo não puder ser encontrado
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Cria um novo modelo Colaborador.
     * Se a criação for bem-sucedida, o navegador será redirecionado para a página 'view'.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Colaborador();
        $model->usuario_id = Yii::$app->user->id;
        $model->ativo = true;

        if ($model->load(Yii::$app->request->post())) {
            
            // Validação customizada: pelo menos um papel deve estar marcado
            if (!$model->eh_vendedor && !$model->eh_cobrador) {
                Yii::$app->session->setFlash('error', 'O colaborador deve ser vendedor e/ou cobrador.');
                return $this->render('create', ['model' => $model]);
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Colaborador cadastrado com sucesso!');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Erro ao salvar colaborador. Verifique os dados.');
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Atualiza um modelo Colaborador existente.
     * Se a atualização for bem-sucedida, o navegador será redirecionado para a página 'view'.
     * @param string $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException se o modelo não puder ser encontrado
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            
            // Validação customizada: pelo menos um papel deve estar marcado
            if (!$model->eh_vendedor && !$model->eh_cobrador) {
                Yii::$app->session->setFlash('error', 'O colaborador deve ser vendedor e/ou cobrador.');
                return $this->render('update', ['model' => $model]);
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Colaborador atualizado com sucesso!');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Erro ao atualizar colaborador. Verifique os dados.');
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deleta um modelo Colaborador existente.
     * Se a exclusão for bem-sucedida, o navegador será redirecionado para a página 'index'.
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException se o modelo não puder ser encontrado
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        try {
            // Exclusão lógica ao invés de física
            $model->ativo = false;
            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', 'Colaborador desativado com sucesso!');
            } else {
                Yii::$app->session->setFlash('error', 'Erro ao desativar colaborador.');
            }
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Erro ao desativar colaborador: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    /**
     * Encontra o modelo Colaborador baseado em seu valor de chave primária.
     * Se o modelo não for encontrado, uma exceção HTTP 404 será lançada.
     * @param string $id
     * @return Colaborador o modelo carregado
     * @throws NotFoundHttpException se o modelo não puder ser encontrado
     */
    protected function findModel($id)
    {
        if (($model = Colaborador::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('O colaborador solicitado não existe.');
    }
}