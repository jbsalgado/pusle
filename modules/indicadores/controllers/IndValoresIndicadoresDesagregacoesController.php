<?php

namespace app\modules\indicadores\controllers;

use Yii;
use app\modules\indicadores\models\IndValoresIndicadoresDesagregacoes;
use app\modules\indicadores\search\IndValoresIndicadoresDesagregacoesSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;

/**
 * IndValoresIndicadoresDesagregacoesController implements the CRUD actions for IndValoresIndicadoresDesagregacoes model.
 */
class IndValoresIndicadoresDesagregacoesController extends Controller
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
     * Lists all IndValoresIndicadoresDesagregacoes models.
     * @return mixed
     */
    public function actionIndex()
    {    
        $searchModel = new IndValoresIndicadoresDesagregacoesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }


    /**
     * Displays a single IndValoresIndicadoresDesagregacoes model.
     * @param integer $id_valor_indicador
     * @param integer $id_opcao_desagregacao
     * @return mixed
     */
    public function actionView($id_valor_indicador, $id_opcao_desagregacao)
    {   
        $request = Yii::$app->request;
        if($request->isAjax){
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                    'title'=> "IndValoresIndicadoresDesagregacoes #".$id_valor_indicador, $id_opcao_desagregacao,
                    'content'=>$this->renderAjax('view', [
                        'model' => $this->findModel($id_valor_indicador, $id_opcao_desagregacao),
                    ]),
                    'footer'=> Html::button('Close',['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                            Html::a('Edit',['update','id_valor_indicador, $id_opcao_desagregacao'=>$id_valor_indicador, $id_opcao_desagregacao],['class'=>'btn btn-primary','role'=>'modal-remote'])
                ];    
        }else{
            return $this->render('view', [
                'model' => $this->findModel($id_valor_indicador, $id_opcao_desagregacao),
            ]);
        }
    }

    /**
     * Creates a new IndValoresIndicadoresDesagregacoes model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new IndValoresIndicadoresDesagregacoes();  

        if($request->isAjax){
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if($request->isGet){
                return [
                    'title'=> "Create new IndValoresIndicadoresDesagregacoes",
                    'content'=>$this->renderAjax('create', [
                        'model' => $model,
                    ]),
                    'footer'=> Html::button('Close',['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                                Html::button('Save',['class'=>'btn btn-primary','type'=>"submit"])
        
                ];         
            }else if($model->load($request->post()) && $model->save()){
                return [
                    'forceReload'=>'#crud-datatable-pjax',
                    'title'=> "Create new IndValoresIndicadoresDesagregacoes",
                    'content'=>'<span class="text-success">Create IndValoresIndicadoresDesagregacoes success</span>',
                    'footer'=> Html::button('Close',['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                            Html::a('Create More',['create'],['class'=>'btn btn-primary','role'=>'modal-remote'])
        
                ];         
            }else{           
                return [
                    'title'=> "Create new IndValoresIndicadoresDesagregacoes",
                    'content'=>$this->renderAjax('create', [
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
                return $this->redirect(['view', 'id_valor_indicador' => $model->id_valor_indicador, 'id_opcao_desagregacao' => $model->id_opcao_desagregacao]);
            } else {
                return $this->render('create', [
                    'model' => $model,
                ]);
            }
        }
       
    }

    /**
     * Updates an existing IndValoresIndicadoresDesagregacoes model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id_valor_indicador
     * @param integer $id_opcao_desagregacao
     * @return mixed
     */
    public function actionUpdate($id_valor_indicador, $id_opcao_desagregacao)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id_valor_indicador, $id_opcao_desagregacao);       

        if($request->isAjax){
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if($request->isGet){
                return [
                    'title'=> "Update IndValoresIndicadoresDesagregacoes #".$id_valor_indicador, $id_opcao_desagregacao,
                    'content'=>$this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer'=> Html::button('Close',['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                                Html::button('Save',['class'=>'btn btn-primary','type'=>"submit"])
                ];         
            }else if($model->load($request->post()) && $model->save()){
                return [
                    'forceReload'=>'#crud-datatable-pjax',
                    'title'=> "IndValoresIndicadoresDesagregacoes #".$id_valor_indicador, $id_opcao_desagregacao,
                    'content'=>$this->renderAjax('view', [
                        'model' => $model,
                    ]),
                    'footer'=> Html::button('Close',['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                            Html::a('Edit',['update','id_valor_indicador, $id_opcao_desagregacao'=>$id_valor_indicador, $id_opcao_desagregacao],['class'=>'btn btn-primary','role'=>'modal-remote'])
                ];    
            }else{
                 return [
                    'title'=> "Update IndValoresIndicadoresDesagregacoes #".$id_valor_indicador, $id_opcao_desagregacao,
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
                return $this->redirect(['view', 'id_valor_indicador' => $model->id_valor_indicador, 'id_opcao_desagregacao' => $model->id_opcao_desagregacao]);
            } else {
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
        }
    }

    /**
     * Delete an existing IndValoresIndicadoresDesagregacoes model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id_valor_indicador
     * @param integer $id_opcao_desagregacao
     * @return mixed
     */
    public function actionDelete($id_valor_indicador, $id_opcao_desagregacao)
    {
        $request = Yii::$app->request;
        $this->findModel($id_valor_indicador, $id_opcao_desagregacao)->delete();

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
     * Delete multiple existing IndValoresIndicadoresDesagregacoes model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id_valor_indicador
     * @param integer $id_opcao_desagregacao
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
     * Finds the IndValoresIndicadoresDesagregacoes model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id_valor_indicador
     * @param integer $id_opcao_desagregacao
     * @return IndValoresIndicadoresDesagregacoes the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id_valor_indicador, $id_opcao_desagregacao)
    {
        if (($model = IndValoresIndicadoresDesagregacoes::findOne(['id_valor_indicador' => $id_valor_indicador, 'id_opcao_desagregacao' => $id_opcao_desagregacao])) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
