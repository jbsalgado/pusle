<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\ComissaoConfig;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\Categoria;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;

class ComissaoConfigController extends Controller
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

    /**
     * Lista todas as configurações de comissão
     */
    public function actionIndex()
    {
        $query = ComissaoConfig::find()
            ->where(['usuario_id' => Yii::$app->user->id])
            ->with(['colaborador', 'categoria']);

        // Filtros
        $colaboradorId = Yii::$app->request->get('colaborador_id');
        $categoriaId = Yii::$app->request->get('categoria_id');
        $tipoComissao = Yii::$app->request->get('tipo_comissao');
        $ativo = Yii::$app->request->get('ativo');

        if ($colaboradorId) {
            $query->andWhere(['colaborador_id' => $colaboradorId]);
        }

        if ($categoriaId !== null && $categoriaId !== '') {
            if ($categoriaId === 'null') {
                // Filtrar por "Todas as Categorias" (categoria_id IS NULL)
                $query->andWhere(['categoria_id' => null]);
            } else {
                $query->andWhere(['categoria_id' => $categoriaId]);
            }
        }

        if ($tipoComissao) {
            $query->andWhere(['tipo_comissao' => $tipoComissao]);
        }

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
                    'data_criacao' => SORT_DESC,
                ]
            ],
        ]);

        // Buscar dados para os filtros
        $colaboradores = Colaborador::find()
            ->where(['usuario_id' => Yii::$app->user->id, 'ativo' => true])
            ->orderBy(['nome_completo' => SORT_ASC])
            ->all();

        $categorias = Categoria::find()
            ->where(['usuario_id' => Yii::$app->user->id, 'ativo' => true])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'colaboradores' => $colaboradores,
            'categorias' => $categorias,
        ]);
    }

    /**
     * Exibe uma configuração específica
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Cria uma nova configuração de comissão
     */
    public function actionCreate()
    {
        $model = new ComissaoConfig();
        $model->usuario_id = Yii::$app->user->id;
        $model->ativo = true;

        if ($model->load(Yii::$app->request->post())) {
            // Converter string vazia em NULL para categoria_id
            if ($model->categoria_id === '') {
                $model->categoria_id = null;
            }
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Configuração de comissão cadastrada com sucesso!');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Atualiza uma configuração existente
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            // Converter string vazia em NULL para categoria_id
            if ($model->categoria_id === '') {
                $model->categoria_id = null;
            }
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Configuração de comissão atualizada com sucesso!');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deleta uma configuração
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->delete();
        
        Yii::$app->session->setFlash('success', 'Configuração de comissão excluída com sucesso!');
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = ComissaoConfig::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('A página solicitada não existe.');
    }
}

