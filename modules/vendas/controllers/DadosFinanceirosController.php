<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\DadosFinanceiros;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

class DadosFinanceirosController extends Controller
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
     * Lista todas as configurações financeiras (global + específicas)
     */
    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;
        
        // Busca configuração global
        $configuracaoGlobal = DadosFinanceiros::getConfiguracaoGlobal($usuarioId);
        
        // Busca todas as configurações específicas de produtos
        $configuracoesEspecificas = DadosFinanceiros::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['IS NOT', 'produto_id', null])
            ->with('produto')
            ->orderBy(['data_atualizacao' => SORT_DESC])
            ->all();

        return $this->render('index', [
            'configuracaoGlobal' => $configuracaoGlobal,
            'configuracoesEspecificas' => $configuracoesEspecificas,
        ]);
    }

    /**
     * Visualiza/Edita configuração global
     */
    public function actionGlobal()
    {
        $usuarioId = Yii::$app->user->id;
        $model = DadosFinanceiros::getConfiguracaoGlobal($usuarioId);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Configuração global atualizada com sucesso!');
            return $this->redirect(['index']);
        }

        return $this->render('global', [
            'model' => $model,
        ]);
    }

    /**
     * Cria ou atualiza configuração específica de um produto
     */
    public function actionProduto($produto_id = null)
    {
        $usuarioId = Yii::$app->user->id;
        
        if ($produto_id) {
            // Busca configuração existente ou cria nova
            $model = DadosFinanceiros::find()
                ->where(['produto_id' => $produto_id, 'usuario_id' => $usuarioId])
                ->one();
            
            if (!$model) {
                $model = new DadosFinanceiros();
                $model->usuario_id = $usuarioId;
                $model->produto_id = $produto_id;
                // Carrega valores da configuração global como padrão
                $global = DadosFinanceiros::getConfiguracaoGlobal($usuarioId);
                $model->taxa_fixa_percentual = $global->taxa_fixa_percentual;
                $model->taxa_variavel_percentual = $global->taxa_variavel_percentual;
                $model->lucro_liquido_percentual = $global->lucro_liquido_percentual;
            }
        } else {
            throw new NotFoundHttpException('Produto não informado.');
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Configuração do produto atualizada com sucesso!');
            return $this->redirect(['index']);
        }

        return $this->render('produto', [
            'model' => $model,
        ]);
    }

    /**
     * Deleta configuração específica de um produto
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        // Só permite deletar configurações específicas (não globais)
        if ($model->produto_id === null) {
            Yii::$app->session->setFlash('error', 'Não é possível deletar a configuração global.');
            return $this->redirect(['index']);
        }
        
        $produtoId = $model->produto_id;
        $model->delete();
        
        Yii::$app->session->setFlash('success', 'Configuração específica removida. O produto usará a configuração global.');
        return $this->redirect(['index']);
    }

    /**
     * Busca o model pelo ID
     */
    protected function findModel($id)
    {
        $model = DadosFinanceiros::findOne($id);
        
        if (!$model) {
            throw new NotFoundHttpException('Configuração não encontrada.');
        }
        
        // Verifica se pertence ao usuário logado
        if ($model->usuario_id !== Yii::$app->user->id) {
            throw new NotFoundHttpException('Você não tem permissão para acessar esta configuração.');
        }
        
        return $model;
    }
}

