<?php

namespace app\modules\contas_pagar\controllers;

use Yii;
use app\modules\contas_pagar\models\TipoDespesa;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;

/**
 * TipoDespesaController — CRUD para categorias genéricas de despesa
 *
 * URL base: /contas-pagar/tipo-despesa
 */
class TipoDespesaController extends Controller
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
                    'delete'        => ['POST'],
                    'toggle-ativo'  => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lista todos os tipos de despesa do usuário logado.
     * Permite filtro por grupo via GET ?grupo=FIXA|VARIAVEL|MERCADORIA
     */
    public function actionIndex()
    {
        $usuarioId = \app\components\TenantHelper::getId();
        $grupoFiltro = Yii::$app->request->get('grupo');

        $query = TipoDespesa::find()
            ->where(['usuario_id' => $usuarioId])
            ->orderBy(['grupo' => SORT_ASC, 'nome' => SORT_ASC]);

        if ($grupoFiltro && in_array($grupoFiltro, [TipoDespesa::GRUPO_FIXA, TipoDespesa::GRUPO_VARIAVEL, TipoDespesa::GRUPO_MERCADORIA])) {
            $query->andWhere(['grupo' => $grupoFiltro]);
        }

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 30],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'grupoFiltro'  => $grupoFiltro,
            'gruposMap'    => TipoDespesa::getGruposMap(),
        ]);
    }

    /**
     * Cria um novo tipo de despesa.
     */
    public function actionCreate()
    {
        $model = new TipoDespesa();
        $model->usuario_id = \app\components\TenantHelper::getId();
        $model->ativo = true;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Tipo de despesa criado com sucesso!');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model'     => $model,
            'gruposMap' => TipoDespesa::getGruposMap(),
        ]);
    }

    /**
     * Edita um tipo de despesa existente.
     * @param string $id
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Tipo de despesa atualizado com sucesso!');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model'     => $model,
            'gruposMap' => TipoDespesa::getGruposMap(),
        ]);
    }

    /**
     * Exclui um tipo de despesa.
     * Bloqueia exclusão se houver contas a pagar vinculadas.
     * @param string $id
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if ($model->temContasVinculadas()) {
            Yii::$app->session->setFlash(
                'error',
                "Não é possível excluir o tipo \"{$model->nome}\" pois existem contas a pagar vinculadas a ele. " .
                "Desative-o em vez de excluir."
            );
            return $this->redirect(['index']);
        }

        $model->delete();
        Yii::$app->session->setFlash('success', "Tipo de despesa \"{$model->nome}\" excluído com sucesso!");

        return $this->redirect(['index']);
    }

    /**
     * Alterna o status ativo/inativo de um tipo.
     * @param string $id
     */
    public function actionToggleAtivo($id)
    {
        $model = $this->findModel($id);
        $model->ativo = !$model->ativo;

        if ($model->save(false)) {
            $status = $model->ativo ? 'ativado' : 'desativado';
            Yii::$app->session->setFlash('success', "Tipo \"{$model->nome}\" {$status} com sucesso!");
        } else {
            Yii::$app->session->setFlash('error', 'Erro ao alterar status do tipo.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Encontra o modelo TipoDespesa pelo ID (restrito ao usuário logado).
     * @param string $id
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $usuarioId = \app\components\TenantHelper::getId();

        $model = TipoDespesa::findOne(['id' => $id, 'usuario_id' => $usuarioId]);
        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('O tipo de despesa solicitado não foi encontrado.');
    }
}
