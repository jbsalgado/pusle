<?php
namespace app\modules\caixa\controllers;

use Yii;
use app\modules\caixa\models\Caixa;
use app\modules\caixa\models\CaixaMovimentacao;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;

/**
 * CaixaController implementa as ações CRUD para Caixa
 */
class CaixaController extends Controller
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
                    'fechar' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lista todos os caixas
     * @return string
     */
    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;
        
        // Verifica se há caixas do dia anterior abertos
        $caixasDiaAnterior = Caixa::find()
            ->where(['usuario_id' => $usuarioId, 'status' => Caixa::STATUS_ABERTO])
            ->all();
        
        $temCaixaDiaAnterior = false;
        foreach ($caixasDiaAnterior as $caixa) {
            if ($caixa->isAbertoDiaAnterior()) {
                $temCaixaDiaAnterior = true;
                break;
            }
        }
        
        // Verifica se há caixa aberto do dia atual
        $caixaAbertoHoje = \app\modules\caixa\helpers\CaixaHelper::getCaixaAberto($usuarioId, false);
        
        $dataProvider = new ActiveDataProvider([
            'query' => Caixa::find()
                ->where(['usuario_id' => $usuarioId])
                ->orderBy(['data_abertura' => SORT_DESC]),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'temCaixaDiaAnterior' => $temCaixaDiaAnterior,
            'caixaAbertoHoje' => $caixaAbertoHoje,
        ]);
    }

    /**
     * Visualiza um caixa específico
     * @param string $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        // Busca movimentações do caixa
        $movimentacoesDataProvider = new ActiveDataProvider([
            'query' => CaixaMovimentacao::find()
                ->where(['caixa_id' => $id])
                ->orderBy(['data_movimento' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('view', [
            'model' => $model,
            'movimentacoesDataProvider' => $movimentacoesDataProvider,
        ]);
    }

    /**
     * Abre um novo caixa
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $usuarioId = Yii::$app->user->id;
        
        // Verifica se já existe caixa aberto para este usuário
        $caixaAberto = Caixa::find()
            ->where(['usuario_id' => $usuarioId, 'status' => Caixa::STATUS_ABERTO])
            ->one();
        
        if ($caixaAberto) {
            // Verifica se é do dia anterior
            if ($caixaAberto->isAbertoDiaAnterior()) {
                // Fecha automaticamente o caixa do dia anterior
                $caixaAberto->fecharAutomaticamente('Fechado automaticamente: caixa do dia anterior detectado ao abrir novo caixa.');
                Yii::$app->session->setFlash('warning', 'O caixa do dia anterior foi fechado automaticamente.');
            } else {
                // Caixa aberto do dia atual - não permite abrir outro
                Yii::$app->session->setFlash('error', 'Já existe um caixa aberto para esta loja. Feche o caixa atual antes de abrir um novo.');
                return $this->redirect(['view', 'id' => $caixaAberto->id]);
            }
        }

        $model = new Caixa();
        $model->usuario_id = $usuarioId;
        $model->status = Caixa::STATUS_ABERTO;
        $model->valor_inicial = 0;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Caixa aberto com sucesso!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Atualiza um caixa existente
     * @param string $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        // Não permite editar caixa fechado
        if ($model->isFechado()) {
            Yii::$app->session->setFlash('error', 'Não é possível editar um caixa fechado.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Caixa atualizado com sucesso!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Fecha um caixa
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionFechar($id)
    {
        $model = $this->findModel($id);

        if (!$model->isAberto()) {
            Yii::$app->session->setFlash('error', 'Apenas caixas abertos podem ser fechados.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        // Calcula valor esperado
        $model->valor_esperado = $model->calcularValorEsperado();
        
        // Se valor_final não foi informado, usa o valor esperado
        if (empty($model->valor_final)) {
            $model->valor_final = $model->valor_esperado;
        }

        // Calcula diferença
        $model->diferenca = $model->valor_final - $model->valor_esperado;
        $model->data_fechamento = date('Y-m-d H:i:s');
        $model->status = Caixa::STATUS_FECHADO;

        if ($model->save()) {
            Yii::$app->session->setFlash('success', 'Caixa fechado com sucesso!');
        } else {
            Yii::$app->session->setFlash('error', 'Erro ao fechar caixa: ' . implode(', ', $model->getFirstErrors()));
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Deleta um caixa
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        // Não permite deletar caixa com movimentações
        if ($model->movimentacoes) {
            Yii::$app->session->setFlash('error', 'Não é possível deletar um caixa com movimentações.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $model->delete();
        Yii::$app->session->setFlash('success', 'Caixa deletado com sucesso!');

        return $this->redirect(['index']);
    }

    /**
     * Encontra o modelo Caixa baseado no ID
     * @param string $id
     * @return Caixa
     * @throws NotFoundHttpException se o modelo não for encontrado
     */
    protected function findModel($id)
    {
        $usuarioId = Yii::$app->user->id;
        
        if (($model = Caixa::findOne(['id' => $id, 'usuario_id' => $usuarioId])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('O caixa solicitado não foi encontrado.');
    }
}

