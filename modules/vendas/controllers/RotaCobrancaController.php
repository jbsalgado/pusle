<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\RotaCobranca;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;

class RotaCobrancaController extends Controller
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
            'query' => RotaCobranca::find()
                ->where(['usuario_id' => $usuarioId])
                ->with(['cobrador', 'periodo']),
            'pagination' => ['pageSize' => 20],
            'sort' => ['defaultOrder' => ['ordem_execucao' => SORT_ASC]],
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
        $model = new RotaCobranca();
        $usuarioId = Yii::$app->user->identity->id ?? Yii::$app->user->id;
        $model->usuario_id = $usuarioId;
        $model->ordem_execucao = 0;

        if (Yii::$app->request->isPost) {
            $postData = Yii::$app->request->post();
            Yii::info('Dados POST recebidos: ' . json_encode($postData), __METHOD__);
            
            if ($model->load($postData)) {
                Yii::info('Model carregado. Dados: ' . json_encode($model->attributes), __METHOD__);
                
                // Converte dia_semana vazio para null antes de validar
                if ($model->dia_semana === '' || $model->dia_semana === null) {
                    $model->dia_semana = null;
                }
                
                // Validação manual antes de salvar
                if (!$model->validate()) {
                    Yii::error('Erros de validação: ' . json_encode($model->errors), __METHOD__);
                    Yii::$app->session->setFlash('error', 'Erro de validação. Verifique os campos obrigatórios.');
                } elseif ($model->save(false)) { // false para pular validação duplicada
                    Yii::$app->session->setFlash('success', 'Rota de cobrança criada com sucesso.');
                    return $this->redirect(['view', 'id' => $model->id]);
                } else {
                    // Log dos erros para debug
                    Yii::error('Erros ao salvar RotaCobranca: ' . json_encode($model->errors), __METHOD__);
                    Yii::error('SQL Error: ' . json_encode($model->getFirstErrors()), __METHOD__);
                    Yii::$app->session->setFlash('error', 'Erro ao salvar rota de cobrança. Verifique os dados informados: ' . implode(', ', $model->getFirstErrors()));
                }
            } else {
                Yii::error('Erro ao carregar dados no model. POST: ' . json_encode($postData), __METHOD__);
                Yii::$app->session->setFlash('error', 'Erro ao processar os dados do formulário.');
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            // Converte dia_semana vazio para null antes de validar
            if ($model->dia_semana === '') {
                $model->dia_semana = null;
            }
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Rota de cobrança atualizada com sucesso.');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                // Log dos erros para debug
                Yii::error('Erros ao atualizar RotaCobranca: ' . json_encode($model->errors), __METHOD__);
                Yii::$app->session->setFlash('error', 'Erro ao atualizar rota de cobrança. Verifique os dados informados.');
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Rota de cobrança excluída com sucesso.');
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        $usuarioId = Yii::$app->user->identity->id ?? Yii::$app->user->id;
        if (($model = RotaCobranca::findOne(['id' => $id, 'usuario_id' => $usuarioId])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('A página solicitada não existe.');
    }
}

