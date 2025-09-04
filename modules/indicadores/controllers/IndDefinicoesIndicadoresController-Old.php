<?php

namespace app\modules\indicadores\controllers;

use app\modules\indicadores\search\IndDefinicoesIndicadoresSearch;
use app\modules\indicadores\models\IndDefinicoesIndicadores;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\helpers\Json;

/**
 * DefinicaoIndicadorController implements the CRUD actions for DefinicaoIndicador model.
 */
class IndDefinicoesIndicadoresController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all DefinicaoIndicador models.
     * @return mixed
     */
    // public function actionIndex()
    // {
    //     $dataProvider = new ActiveDataProvider([
    //         'query' => IndDefinicoesIndicadores::find()
    //             ->with(['dimensao', 'unidadeMedida', 'periodicidadeMedicao', 'periodicidadeDivulgacao', 'fontePadrao'])
    //             ->orderBy(['data_atualizacao' => SORT_DESC]),
    //         'pagination' => [
    //             'pageSize' => 20,
    //         ],
    //     ]);

    //     // Verificar se é uma requisição AJAX para busca
    //     if (Yii::$app->request->isAjax) {
    //         $searchTerm = Yii::$app->request->get('q', '');
    //         if (!empty($searchTerm)) {
    //             $dataProvider->query->andWhere([
    //                 'or',
    //                 ['ilike', 'nome_indicador', $searchTerm],
    //                 ['ilike', 'cod_indicador', $searchTerm],
    //                 ['ilike', 'descricao_completa', $searchTerm],
    //                 ['ilike', 'palavras_chave', $searchTerm],
    //             ]);
    //         }
            
    //         return $this->renderAjax('_list', [
    //             'dataProvider' => $dataProvider,
    //         ]);
    //     }

    //     return $this->render('index', [
    //         'dataProvider' => $dataProvider,
    //     ]);
    // }

    public function actionIndex()
    {
        // 2. SUBSTITUA A CRIAÇÃO DO DATAPROVIDER PELA LÓGICA DO SEARCH MODEL
        $searchModel = new IndDefinicoesIndicadoresSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // O bloco 'if (Yii::$app->request->isAjax)' foi removido pois
        // o Search Model já lida com os parâmetros da requisição,
        // simplificando o controller.

        return $this->render('index', [
            'searchModel' => $searchModel, // Opcional, mas boa prática
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single DefinicaoIndicador model.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('_view', [
                'model' => $model,
            ]);
        }

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new DefinicaoIndicador model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new IndDefinicoesIndicadores();

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            
            if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                if ($model->save()) {
                    return [
                        'success' => true,
                        'message' => 'Indicador criado com sucesso!',
                        'redirect' => \yii\helpers\Url::to(['view', 'id' => $model->id_indicador])
                    ];
                }
            }
            
            return [
                'success' => false,
                'message' => 'Erro ao criar indicador',
                'errors' => $model->errors
            ];
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Indicador criado com sucesso!');
            return $this->redirect(['view', 'id' => $model->id_indicador]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing DefinicaoIndicador model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            
            if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                if ($model->save()) {
                    return [
                        'success' => true,
                        'message' => 'Indicador atualizado com sucesso!',
                        'redirect' => \yii\helpers\Url::to(['view', 'id' => $model->id_indicador])
                    ];
                }
            }
            
            return [
                'success' => false,
                'message' => 'Erro ao atualizar indicador',
                'errors' => $model->errors
            ];
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Indicador atualizado com sucesso!');
            return $this->redirect(['view', 'id' => $model->id_indicador]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing DefinicaoIndicador model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        try {
            $model->delete();
            
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return [
                    'success' => true,
                    'message' => 'Indicador excluído com sucesso!'
                ];
            }
            
            Yii::$app->session->setFlash('success', 'Indicador excluído com sucesso!');
        } catch (\Exception $e) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return [
                    'success' => false,
                    'message' => 'Erro ao excluir indicador: ' . $e->getMessage()
                ];
            }
            
            Yii::$app->session->setFlash('error', 'Erro ao excluir indicador: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    /**
     * Toggle status ativo/inativo
     * @param int $id
     * @return mixed
     */
    public function actionToggleStatus($id)
    {
        $model = $this->findModel($id);
        $model->ativo = !$model->ativo;
        
        if ($model->save()) {
            $status = $model->ativo ? 'ativado' : 'desativado';
            $message = "Indicador {$status} com sucesso!";
            
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return [
                    'success' => true,
                    'message' => $message,
                    'newStatus' => $model->ativo
                ];
            }
            
            Yii::$app->session->setFlash('success', $message);
        } else {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return [
                    'success' => false,
                    'message' => 'Erro ao alterar status do indicador'
                ];
            }
            
            Yii::$app->session->setFlash('error', 'Erro ao alterar status do indicador');
        }
        
        return $this->redirect(['index']);
    }

    /**
     * Buscar indicadores para autocomplete
     */
    public function actionSearch()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $searchTerm = Yii::$app->request->get('q', '');
        $results = [];
        
        if (!empty($searchTerm)) {
            $indicators = IndDefinicoesIndicadores::find()
                ->select(['id_indicador', 'nome_indicador', 'cod_indicador'])
                ->where([
                    'or',
                    ['ilike', 'nome_indicador', $searchTerm],
                    ['ilike', 'cod_indicador', $searchTerm],
                ])
                ->andWhere(['ativo' => true])
                ->limit(10)
                ->all();
                
            foreach ($indicators as $indicator) {
                $results[] = [
                    'id' => $indicator->id_indicador,
                    'text' => $indicator->cod_indicador . ' - ' . $indicator->nome_indicador,
                    'name' => $indicator->nome_indicador,
                    'code' => $indicator->cod_indicador,
                ];
            }
        }
        
        return ['results' => $results];
    }

    /**
     * Finds the DefinicaoIndicador model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return DefinicaoIndicador the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = IndDefinicoesIndicadores::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('A página solicitada não existe.');
    }
}