<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\CarteiraCobranca;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;

class CarteiraCobrancaController extends Controller
{
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

    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->identity->id ?? Yii::$app->user->id;
        
        $dataProvider = new ActiveDataProvider([
            'query' => CarteiraCobranca::find()
                ->where(['usuario_id' => $usuarioId])
                ->with(['cliente', 'cobrador', 'rota', 'periodo']),
            'pagination' => ['pageSize' => 20],
            'sort' => ['defaultOrder' => ['data_distribuicao' => SORT_DESC]],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new CarteiraCobranca();
        $model->usuario_id = Yii::$app->user->identity->id ?? Yii::$app->user->id;
        $model->ativo = true;
        $model->data_distribuicao = date('Y-m-d');

        if (Yii::$app->request->isPost) {
            $postData = Yii::$app->request->post();
            
            // Converte rota_id vazio para null antes de carregar
            if (isset($postData['CarteiraCobranca']['rota_id']) && $postData['CarteiraCobranca']['rota_id'] === '') {
                $postData['CarteiraCobranca']['rota_id'] = null;
            }
            
            if ($model->load($postData)) {
                // Garante que rota_id seja null se vazio
                if ($model->rota_id === '') {
                    $model->rota_id = null;
                }
                
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', 'Carteira de cobrança criada com sucesso.');
                    return $this->redirect(['view', 'id' => $model->id]);
                } else {
                    Yii::error('Erros ao salvar CarteiraCobranca: ' . json_encode($model->errors), __METHOD__);
                    Yii::$app->session->setFlash('error', 'Erro ao criar carteira de cobrança. Verifique os dados: ' . implode(', ', $model->getFirstErrors()));
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if (Yii::$app->request->isPost) {
            $postData = Yii::$app->request->post();
            
            // Converte rota_id vazio para null antes de carregar
            if (isset($postData['CarteiraCobranca']['rota_id']) && $postData['CarteiraCobranca']['rota_id'] === '') {
                $postData['CarteiraCobranca']['rota_id'] = null;
            }
            
            if ($model->load($postData)) {
                // Garante que rota_id seja null se vazio
                if ($model->rota_id === '') {
                    $model->rota_id = null;
                }
                
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', 'Carteira de cobrança atualizada com sucesso.');
                    return $this->redirect(['view', 'id' => $model->id]);
                } else {
                    Yii::error('Erros ao atualizar CarteiraCobranca: ' . json_encode($model->errors), __METHOD__);
                    Yii::$app->session->setFlash('error', 'Erro ao atualizar carteira de cobrança. Verifique os dados: ' . implode(', ', $model->getFirstErrors()));
                }
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Carteira de cobrança excluída com sucesso.');
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        $usuarioId = Yii::$app->user->identity->id ?? Yii::$app->user->id;
        if (($model = CarteiraCobranca::findOne(['id' => $id, 'usuario_id' => $usuarioId])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('A página solicitada não existe.');
    }
}

