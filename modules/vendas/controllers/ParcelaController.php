<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\Parcela;
use app\modules\vendas\models\StatusParcela;
use app\modules\vendas\models\FormaPagamento;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

class ParcelaController extends Controller
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
                    'receber' => ['POST'],
                    'cancelar' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;

        // Query base com joins para filtros
        $query = Parcela::find()
            ->alias('p')
            ->joinWith(['venda v'])
            ->leftJoin('prest_clientes c', 'c.id = v.cliente_id')
            ->where(['p.usuario_id' => $usuarioId])
            ->with(['venda', 'venda.cliente', 'statusParcela', 'formaPagamento']);

        // Filtros
        $clienteNome = Yii::$app->request->get('cliente_nome');
        if ($clienteNome) {
            $query->andFilterWhere(['like', 'c.nome_completo', $clienteNome]);
        }

        $clienteCpf = Yii::$app->request->get('cliente_cpf');
        if ($clienteCpf) {
            $cpfLimpo = preg_replace('/[^0-9]/', '', $clienteCpf);
            if ($cpfLimpo) {
                $query->andWhere(['c.cpf' => $cpfLimpo]);
            }
        }

        $dataCompra = Yii::$app->request->get('data_compra');
        if ($dataCompra) {
            $query->andWhere(['DATE(v.data_venda)' => $dataCompra]);
        }

        $dataVencimento = Yii::$app->request->get('data_vencimento');
        if ($dataVencimento) {
            $query->andWhere(['p.data_vencimento' => $dataVencimento]);
        }

        $status = Yii::$app->request->get('status');
        if ($status) {
            $query->andWhere(['p.status_parcela_codigo' => $status]);
        }

        $valorMin = Yii::$app->request->get('valor_min');
        if ($valorMin) {
            $query->andWhere(['>=', 'p.valor_parcela', $valorMin]);
        }

        $valorMax = Yii::$app->request->get('valor_max');
        if ($valorMax) {
            $query->andWhere(['<=', 'p.valor_parcela', $valorMax]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => ['data_vencimento' => SORT_ASC],
                'attributes' => [
                    'data_vencimento',
                    'valor_parcela',
                    'status_parcela_codigo',
                    'data_pagamento',
                    'venda.data_venda' => [
                        'asc' => ['v.data_venda' => SORT_ASC],
                        'desc' => ['v.data_venda' => SORT_DESC],
                    ],
                    'cliente.nome_completo' => [
                        'asc' => ['c.nome_completo' => SORT_ASC],
                        'desc' => ['c.nome_completo' => SORT_DESC],
                    ],
                ],
            ],
        ]);

        // Buscar status para filtro
        $statusList = ArrayHelper::map(StatusParcela::find()->all(), 'codigo', 'descricao');

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'statusList' => $statusList,
        ]);
    }

    /**
     * Visualiza uma parcela específica
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Atualiza uma parcela existente
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        // Não permite editar parcela paga ou cancelada
        if (
            $model->status_parcela_codigo === StatusParcela::PAGA ||
            $model->status_parcela_codigo === StatusParcela::CANCELADA
        ) {
            Yii::$app->session->setFlash('error', 'Não é possível editar uma parcela paga ou cancelada.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Parcela atualizada com sucesso!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        // Buscar formas de pagamento para dropdown
        $formasPagamento = ArrayHelper::map(
            FormaPagamento::find()
                ->where(['usuario_id' => Yii::$app->user->id, 'ativo' => true])
                ->orderBy(['nome' => SORT_ASC])
                ->all(),
            'id',
            'nome'
        );

        return $this->render('update', [
            'model' => $model,
            'formasPagamento' => $formasPagamento,
        ]);
    }

    /**
     * Marca uma parcela como recebida (paga)
     */
    public function actionReceber($id)
    {
        $model = $this->findModel($id);

        if ($model->status_parcela_codigo === StatusParcela::PAGA) {
            Yii::$app->session->setFlash('error', 'Esta parcela já está paga.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($model->status_parcela_codigo === StatusParcela::CANCELADA) {
            Yii::$app->session->setFlash('error', 'Não é possível receber uma parcela cancelada.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $post = Yii::$app->request->post();
        $valorPago = $post['valor_pago'] ?? $model->valor_parcela;
        $formaPagamentoId = $post['forma_pagamento_id'] ?? null;
        $dataPagamento = $post['data_pagamento'] ?? date('Y-m-d');

        if ($model->registrarPagamento($valorPago, null, $formaPagamentoId)) {
            // Atualiza data de pagamento se foi informada
            if ($dataPagamento && $dataPagamento !== $model->data_pagamento) {
                $model->data_pagamento = $dataPagamento;
                $model->save(false);
            }

            // ✅ TASK-001: INTEGRAÇÃO AUTOMÁTICA COM CAIXA
            // Registra entrada no caixa quando parcela é recebida
            $movimentacao = \app\modules\caixa\helpers\CaixaHelper::registrarEntradaParcela(
                $model->id,
                $valorPago,
                $formaPagamentoId,
                Yii::$app->user->id
            );

            $msg = 'Parcela marcada como recebida com sucesso!';
            if ($movimentacao) {
                $msg .= ' Entrada de ' . Yii::$app->formatter->asCurrency($valorPago) . ' registrada no caixa.';
            } else {
                $msg .= ' <br><small>⚠️ A entrada não foi registrada no caixa (verifique se o caixa está aberto).</small>';
            }

            Yii::$app->session->setFlash('success', $msg);
        } else {
            Yii::$app->session->setFlash('error', 'Erro ao marcar parcela como recebida: ' . implode(', ', $model->getFirstErrors()));
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Cancela uma parcela
     */
    public function actionCancelar($id)
    {
        $model = $this->findModel($id);

        if ($model->status_parcela_codigo === StatusParcela::PAGA) {
            Yii::$app->session->setFlash('error', 'Não é possível cancelar uma parcela já paga.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($model->status_parcela_codigo === StatusParcela::CANCELADA) {
            Yii::$app->session->setFlash('error', 'Esta parcela já está cancelada.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $model->status_parcela_codigo = StatusParcela::CANCELADA;
        $model->observacoes = ($model->observacoes ? $model->observacoes . "\n" : '') .
            'Cancelada em ' . date('d/m/Y H:i:s') .
            (Yii::$app->request->post('observacao') ? ': ' . Yii::$app->request->post('observacao') : '');

        if ($model->save()) {
            Yii::$app->session->setFlash('success', 'Parcela cancelada com sucesso!');
        } else {
            Yii::$app->session->setFlash('error', 'Erro ao cancelar parcela: ' . implode(', ', $model->getFirstErrors()));
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    protected function findModel($id)
    {
        if (($model = Parcela::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('A página solicitada não existe.');
    }
}
