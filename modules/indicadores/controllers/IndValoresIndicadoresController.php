<?php

namespace app\modules\indicadores\controllers;

use app\modules\indicadores\models\IndDefinicoesIndicadores;
use app\modules\indicadores\models\IndFontesDados;
use app\modules\indicadores\models\IndNiveisAbrangencia;
use Yii;
use app\modules\indicadores\models\IndValoresIndicadores;
use app\modules\indicadores\search\IndValoresIndicadoresSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use \yii\web\Response;
use yii\helpers\Html;

/**
 * IndValoresIndicadoresController implements the CRUD actions for IndValoresIndicadores model.
 */
class IndValoresIndicadoresController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                    'bulk-delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all IndValoresIndicadores models.
     * @return mixed
     */
    public function actionIndex()
    {    
        $searchModel = new IndValoresIndicadoresSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }


    /**
     * Displays a single IndValoresIndicadores model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {   
        $request = Yii::$app->request;
        if($request->isAjax){
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                    'title'=> "IndValoresIndicadores #".$id,
                    'content'=>$this->renderAjax('view', [
                        'model' => $this->findModel($id),
                    ]),
                    'footer'=> Html::button('Close',['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                            Html::a('Edit',['update','id'=>$id],['class'=>'btn btn-primary','role'=>'modal-remote'])
                ];    
        }else{
            return $this->render('view', [
                'model' => $this->findModel($id),
            ]);
        }
    }

    /**
     * Creates a new IndValoresIndicadores model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new IndValoresIndicadores();

        // Dados para os dropdowns
        $indicadores = ArrayHelper::map(IndDefinicoesIndicadores::find()->all(), 'id_indicador', 'nome_indicador');
        $niveisAbrangencia = ArrayHelper::map(IndNiveisAbrangencia::find()->all(), 'id_nivel_abrangencia', 'nome_nivel');
        $fontesDados = ArrayHelper::map(IndFontesDados::find()->all(), 'id_fonte', 'nome_fonte');


        if ($request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                Yii::$app->session->setFlash('success', 'Valor do indicador registrado com sucesso!');
                return $this->redirect(['view', 'id' => $model->id_valor]);
            } else {
                Yii::$app->session->setFlash('error', 'Erro ao registrar o valor do indicador.');
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
            'indicadores' => $indicadores,
            'niveisAbrangencia' => $niveisAbrangencia,
            'fontesDados' => $fontesDados,
        ]);
    }

    /**
     * Retorna dados para dropdown via AJAX
     */
    public function actionGetIndicadorInfo($id)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $indicador = IndDefinicoesIndicadores::find()
            ->with(['unidadeMedida', 'dimensao'])
            ->where(['id_indicador' => $id])
            ->one();
            
        if ($indicador) {
            return [
                'success' => true,
                'data' => [
                    'nome' => $indicador->nome_indicador,
                    'unidade' => $indicador->unidadeMedida->sigla_unidade ?? '',
                    'polaridade' => $indicador->polaridade,
                    'tipo' => $indicador->tipo_especifico,
                ]
            ];
        }
        
        return ['success' => false];
    }

    /**
     * Updates an existing IndValoresIndicadores model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);       

        if($request->isAjax){
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if($request->isGet){
                return [
                    'title'=> "Update IndValoresIndicadores #".$id,
                    'content'=>$this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer'=> Html::button('Close',['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                                Html::button('Save',['class'=>'btn btn-primary','type'=>"submit"])
                ];         
            }else if($model->load($request->post()) && $model->save()){
                return [
                    'forceReload'=>'#crud-datatable-pjax',
                    'title'=> "IndValoresIndicadores #".$id,
                    'content'=>$this->renderAjax('view', [
                        'model' => $model,
                    ]),
                    'footer'=> Html::button('Close',['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                            Html::a('Edit',['update','id'=>$id],['class'=>'btn btn-primary','role'=>'modal-remote'])
                ];    
            }else{
                 return [
                    'title'=> "Update IndValoresIndicadores #".$id,
                    'content'=>$this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer'=> Html::button('Close',['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                                Html::button('Save',['class'=>'btn btn-primary','type'=>"submit"])
                ];        
            }
        }else{
            /*
            *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id_valor]);
            } else {
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
        }
    }

    /**
     * Delete an existing IndValoresIndicadores model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $request = Yii::$app->request;
        $this->findModel($id)->delete();

        if($request->isAjax){
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose'=>true,'forceReload'=>'#crud-datatable-pjax'];
        }else{
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }


    }

     /**
     * Delete multiple existing IndValoresIndicadores model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionBulkDelete()
    {        
        $request = Yii::$app->request;
        $pks = explode(',', $request->post( 'pks' )); // Array or selected records primary keys
        foreach ( $pks as $pk ) {
            $model = $this->findModel($pk);
            $model->delete();
        }

        if($request->isAjax){
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose'=>true,'forceReload'=>'#crud-datatable-pjax'];
        }else{
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }
       
    }

    /**
     * Finds the IndValoresIndicadores model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return IndValoresIndicadores the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = IndValoresIndicadores::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
