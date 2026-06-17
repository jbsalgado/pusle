<?php
namespace app\modules\caixa\controllers;

use Yii;
use app\modules\caixa\models\CaixaMovimentacao;
use app\modules\caixa\models\Caixa;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

/**
 * MovimentacaoController implementa ações para movimentações de caixa
 */
class MovimentacaoController extends Controller
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
     * Cria uma nova movimentação
     * @param string $caixa_id ID do caixa
     * @return string|\yii\web\Response
     */
    public function actionCreate($caixa_id)
    {
        // Verifica se o caixa existe e está aberto
        $caixa = Caixa::findOne(['id' => $caixa_id, 'usuario_id' => Yii::$app->user->id]);
        if (!$caixa) {
            throw new NotFoundHttpException('Caixa não encontrado.');
        }

        if (!$caixa->isAberto()) {
            Yii::$app->session->setFlash('error', 'Apenas caixas abertos podem receber movimentações.');
            return $this->redirect(['caixa/view', 'id' => $caixa_id]);
        }

        $model = new CaixaMovimentacao();
        $model->caixa_id = $caixa_id;
        $model->data_movimento = date('Y-m-d H:i:s');

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Movimentação registrada com sucesso!');
            return $this->redirect(['caixa/view', 'id' => $caixa_id]);
        }

        return $this->render('create', [
            'model' => $model,
            'caixa' => $caixa,
        ]);
    }

    /**
     * Atualiza uma movimentação existente
     * @param string $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $caixa = $model->caixa;

        // Verifica se o caixa está aberto
        if (!$caixa->isAberto()) {
            Yii::$app->session->setFlash('error', 'Apenas caixas abertos podem ter movimentações editadas.');
            return $this->redirect(['caixa/view', 'id' => $caixa->id]);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Movimentação atualizada com sucesso!');
            return $this->redirect(['caixa/view', 'id' => $caixa->id]);
        }

        return $this->render('update', [
            'model' => $model,
            'caixa' => $caixa,
        ]);
    }

    /**
     * Deleta uma movimentação
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $caixaId = $model->caixa_id;
        $caixa = $model->caixa;

        // Verifica se o caixa está aberto
        if (!$caixa->isAberto()) {
            Yii::$app->session->setFlash('error', 'Apenas caixas abertos podem ter movimentações deletadas.');
            return $this->redirect(['caixa/view', 'id' => $caixaId]);
        }

        $model->delete();
        Yii::$app->session->setFlash('success', 'Movimentação deletada com sucesso!');

        return $this->redirect(['caixa/view', 'id' => $caixaId]);
    }

    /**
     * Encontra o modelo CaixaMovimentacao baseado no ID
     * @param string $id
     * @return CaixaMovimentacao
     * @throws NotFoundHttpException se o modelo não for encontrado
     */
    protected function findModel($id)
    {
        $usuarioId = Yii::$app->user->id;
        
        $model = CaixaMovimentacao::find()
            ->joinWith('caixa')
            ->where([
                CaixaMovimentacao::tableName() . '.id' => $id,
                Caixa::tableName() . '.usuario_id' => $usuarioId
            ])
            ->one();

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('A movimentação solicitada não foi encontrada.');
    }
}

