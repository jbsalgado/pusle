<?php

namespace app\modules\indicadores\controllers;

use app\modules\indicadores\models\IndDimensoesIndicadores;
use Yii;
use app\modules\indicadores\models\SysModulos;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\helpers\Json;

/**
 * SysModulosController implements the CRUD actions for SysModulos model.
 */
class SysModulosController extends Controller
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
     * Lists all SysModulos models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => SysModulos::find()->with('dimensoesIndicadores'),
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'modulo' => SORT_ASC,
                ]
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single SysModulos model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new SysModulos model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new SysModulos();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Módulo criado com sucesso!');
            return $this->redirect(['/index.php/metricas/sys-modulos/view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing SysModulos model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Módulo atualizado com sucesso!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing SysModulos model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        try {
            $this->findModel($id)->delete();
            Yii::$app->session->setFlash('success', 'Módulo excluído com sucesso!');
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Erro ao excluir módulo: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    /**
     * Toggle status of a SysModulos model via AJAX.
     * @param integer $id
     * @return Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionToggleStatus($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $model = $this->findModel($id);
        $model->status = !$model->status;
        
        if ($model->save(false)) {
            return [
                'success' => true,
                'status' => $model->status,
                'message' => 'Status alterado com sucesso!'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Erro ao alterar status!'
        ];
    }

    /**
     * Get dimensions for Select2 via AJAX.
     * @return Response
     */
    public function actionGetDimensoes()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $search = Yii::$app->request->get('q', '');
        
        $query = IndDimensoesIndicadores::find()
            ->orderBy('nome_dimensao ASC');
            
        if (!empty($search)) {
            $query->andWhere(['or',
                ['ilike', 'nome_dimensao', $search],
                ['ilike', 'descricao', $search]
            ]);
        }
        
        $dimensoes = $query->limit(50)->all();
        
        $results = [];
        foreach ($dimensoes as $dimensao) {
            $text = $dimensao->nome_dimensao;
            if ($dimensao->descricao) {
                $text .= ' - ' . substr($dimensao->descricao, 0, 50) . '...';
            }
            
            $results[] = [
                'id' => $dimensao->id_dimensao,
                'text' => $text
            ];
        }
        
        return ['results' => $results];
    }

    /**
     * Finds the SysModulos model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return SysModulos the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = SysModulos::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('A página solicitada não existe.');
    }
}