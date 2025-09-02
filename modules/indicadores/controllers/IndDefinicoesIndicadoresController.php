<?php

namespace app\modules\indicadores\controllers;

// 1. ADICIONE O 'use' PARA O SEU SEARCH MODEL
use app\modules\indicadores\search\IndDefinicoesIndicadoresSearch;
use app\modules\indicadores\models\IndDefinicoesIndicadores;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\helpers\Json;
use yii\widgets\ListView;

/**
 * IndDefinicoesIndicadoresController implements the CRUD actions for IndDefinicoesIndicadores model.
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
     * Lists all IndDefinicoesIndicadores models.
     * @return mixed
     */
    // public function actionIndex()
    // {
    //     // 2. SUBSTITUA A CRIAÇÃO DO DATAPROVIDER PELA LÓGICA DO SEARCH MODEL
    //     $searchModel = new IndDefinicoesIndicadoresSearch();
    //     $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

    //     // O bloco 'if (Yii::$app->request->isAjax)' foi removido pois
    //     // o Search Model já lida com os parâmetros da requisição,
    //     // simplificando o controller.

    //     return $this->render('index', [
    //         'searchModel' => $searchModel, // Opcional, mas boa prática
    //         'dataProvider' => $dataProvider,
    //     ]);
    // }
public function actionIndex()
    {
        $searchModel = new IndDefinicoesIndicadoresSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // 2. AJUSTE CRÍTICO: VERIFICA SE A REQUISIÇÃO É PJAX
        if (Yii::$app->request->isPjax) {
            // Se for Pjax, renderiza APENAS o widget ListView com os dados filtrados.
            // Isso envia um HTML limpo para o Pjax, evitando o loop de carregamento.
            return ListView::widget([
                'dataProvider' => $dataProvider,
                'itemView' => '_item',
                'layout' => "{summary}\n{items}\n{pager}",
                'summary' => '<div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 rounded-t-lg">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <p class="text-base text-gray-700">
                            Mostrando <span class="font-medium">{begin}</span> até <span class="font-medium">{end}</span>
                            de <span class="font-medium">{totalCount}</span> resultados
                        </p>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-base text-gray-700">
                                Mostrando <span class="font-medium">{begin}</span> até <span class="font-medium">{end}</span>
                                de <span class="font-medium">{totalCount}</span> resultados
                            </p>
                        </div>
                    </div>
                </div>',
                'itemOptions' => ['class' => 'mb-4'],
                'options' => ['class' => 'space-y-4'],
                'pager' => [
                    'class' => 'yii\widgets\LinkPager',
                    'options' => ['class' => 'pagination justify-content-center mt-4'],
                    'linkOptions' => ['class' => 'page-link'],
                    'activePageCssClass' => 'active',
                    'disabledPageCssClass' => 'disabled',
                    'prevPageLabel' => '‹ Anterior',
                    'nextPageLabel' => 'Próxima ›',
                    'firstPageLabel' => 'Primeira',
                    'lastPageLabel' => 'Última',
                ],
            ]);
        }

        // Para o carregamento inicial da página, renderiza a view completa com o layout
        return $this->render('index', [
            'searchModel' => $searchModel,
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
        Yii::$app->response->format = Response::FORMAT_JSON;
        try {
            $this->findModel($id)->delete();
            return ['success' => true, 'message' => 'Indicador excluído com sucesso!'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erro ao excluir o indicador. Verifique se ele não está sendo utilizado.'];
        }
    }

    /**
     * Toggle status ativo/inativo
     * @param int $id
     * @return mixed
     */
    public function actionToggleStatus($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = $this->findModel($id);
        $model->ativo = !$model->ativo;
        
        if ($model->save()) {
            $status = $model->ativo ? 'ativado' : 'desativado';
            return ['success' => true, 'message' => "Indicador {$status} com sucesso!", 'newStatus' => $model->ativo];
        } else {
            return ['success' => false, 'message' => 'Erro ao alterar status do indicador'];
        }
    }

    /**
     * Finds the IndDefinicoesIndicadores model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id
     * @return IndDefinicoesIndicadores the loaded model
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
