<?php

namespace app\modules\vendas\controllers;

use app\modules\vendas\models\Clientes;
use Yii;
use app\modules\vendas\models\PrestClientes;
use app\modules\vendas\models\Regioes;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * PrestClientesController implementa as ações CRUD para o model PrestClientes.
 */
class ClientesController extends Controller
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
     * Lista todos os Clientes com filtros e paginação.
     * @return string
     */
    public function actionIndex()
    {
        $query = Clientes::find()
            ->where(['usuario_id' => Yii::$app->user->id])
            ->with(['regiao'])
            ->orderBy(['nome_completo' => SORT_ASC]);

        // Aplicar filtros
        $busca = Yii::$app->request->get('busca');
        if ($busca) {
            $query->andFilterWhere(['or',
                ['like', 'nome_completo', $busca],
                ['like', 'cpf', $busca],
                ['like', 'telefone', $busca],
                ['like', 'email', $busca],
            ]);
        }

        $regiaoId = Yii::$app->request->get('regiao_id');
        if ($regiaoId) {
            $query->andWhere(['regiao_id' => $regiaoId]);
        }

        $cidade = Yii::$app->request->get('cidade');
        if ($cidade) {
            $query->andFilterWhere(['like', 'endereco_cidade', $cidade]);
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

        // Buscar regiões para o filtro
        $regioes = Regioes::find()
            ->where(['usuario_id' => Yii::$app->user->id])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'regioes' => $regioes,
        ]);
    }

    /**
     * Exibe um único modelo Cliente.
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
     * Cria um novo modelo Cliente.
     * Se a criação for bem-sucedida, o navegador será redirecionado para a página 'view'.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Clientes();
        $model->usuario_id = Yii::$app->user->id;
        $model->ativo = true;

        // Buscar regiões para o dropdown
        $regioes = Regioes::find()
            ->where(['usuario_id' => Yii::$app->user->id])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        if ($model->load(Yii::$app->request->post())) {
            
            // Limpar CPF antes de salvar (remover pontos e traços)
            if ($model->cpf) {
                $model->cpf = preg_replace('/[^0-9]/', '', $model->cpf);
            }

            // Limpar CEP antes de salvar
            if ($model->endereco_cep) {
                $model->endereco_cep = preg_replace('/[^0-9]/', '', $model->endereco_cep);
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Cliente cadastrado com sucesso!');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Erro ao salvar cliente. Verifique os dados.');
            }
        }

        return $this->render('create', [
            'model' => $model,
            'regioes' => $regioes,
        ]);
    }

    /**
     * Atualiza um modelo Cliente existente.
     * Se a atualização for bem-sucedida, o navegador será redirecionado para a página 'view'.
     * @param string $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException se o modelo não puder ser encontrado
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        // Buscar regiões para o dropdown
        $regioes = Regioes::find()
            ->where(['usuario_id' => Yii::$app->user->id])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        if ($model->load(Yii::$app->request->post())) {
            
            // Limpar CPF antes de salvar (remover pontos e traços)
            if ($model->cpf) {
                $model->cpf = preg_replace('/[^0-9]/', '', $model->cpf);
            }

            // Limpar CEP antes de salvar
            if ($model->endereco_cep) {
                $model->endereco_cep = preg_replace('/[^0-9]/', '', $model->endereco_cep);
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Cliente atualizado com sucesso!');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Erro ao atualizar cliente. Verifique os dados.');
            }
        }

        return $this->render('update', [
            'model' => $model,
            'regioes' => $regioes,
        ]);
    }

    /**
     * Deleta um modelo Cliente existente.
     * Se a exclusão for bem-sucedida, o navegador será redirecionado para a página 'index'.
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException se o modelo não puder ser encontrado
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        try {
            // Exclusão lógica ao invés de física
            $model->ativo = false;
            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', 'Cliente desativado com sucesso!');
            } else {
                Yii::$app->session->setFlash('error', 'Erro ao desativar cliente.');
            }
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Erro ao desativar cliente: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    /**
     * Busca CEP via API ViaCEP (método AJAX)
     * @return \yii\web\Response
     */
    public function actionBuscarCep()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $cep = Yii::$app->request->post('cep');
        $cep = preg_replace('/[^0-9]/', '', $cep);
        
        if (strlen($cep) !== 8) {
            return ['success' => false, 'message' => 'CEP inválido'];
        }
        
        try {
            $url = "https://viacep.com.br/ws/{$cep}/json/";
            $response = file_get_contents($url);
            $data = json_decode($response, true);
            
            if (isset($data['erro'])) {
                return ['success' => false, 'message' => 'CEP não encontrado'];
            }
            
            return [
                'success' => true,
                'data' => [
                    'logradouro' => $data['logradouro'] ?? '',
                    'bairro' => $data['bairro'] ?? '',
                    'cidade' => $data['localidade'] ?? '',
                    'estado' => $data['uf'] ?? '',
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erro ao buscar CEP'];
        }
    }

    /**
     * Encontra o modelo Cliente baseado em seu valor de chave primária.
     * Se o modelo não for encontrado, uma exceção HTTP 404 será lançada.
     * @param string $id
     * @return PrestClientes o modelo carregado
     * @throws NotFoundHttpException se o modelo não puder ser encontrado
     */
    protected function findModel($id)
    {
        if (($model = Clientes::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('O cliente solicitado não existe.');
    }
}