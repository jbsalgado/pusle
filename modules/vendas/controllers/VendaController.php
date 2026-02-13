<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\Venda;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\web\Response;

/**
 * Controller para listagem de vendas efetivadas
 */
class VendaController extends Controller
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
     * Listagem de vendas com Grid e Card
     */
    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;

        $dataProvider = new ActiveDataProvider([
            'query' => Venda::find()
                ->where(['usuario_id' => $usuarioId])
                ->with(['cliente', 'formaPagamento']),
            'pagination' => ['pageSize' => 20],
            'sort' => ['defaultOrder' => ['data_criacao' => SORT_DESC]],
        ]);

        // Registra assets específicos (serão criados)
        $this->view->registerCssFile(
            '@web/css/venda-list.css',
            ['depends' => [\yii\web\YiiAsset::class]]
        );

        $this->view->registerJsFile(
            '@web/js/venda-list.js',
            ['depends' => [\yii\web\JqueryAsset::class]]
        );

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Retorna detalhes completos de uma venda para impressão (API JSON)
     */
    public function actionDetalhes($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = $this->findModel($id);

        $itens = [];
        foreach ($model->itens as $item) {
            $itens[] = [
                'nome' => $item->produto ? $item->produto->nome : 'Produto',
                'quantidade' => (float) $item->quantidade,
                'preco' => (float) $item->preco_unitario_venda,
                'subtotal' => (float) $item->valor_total_item,
            ];
        }

        $cliente = null;
        if ($model->cliente) {
            $cliente = [
                'nome' => $model->cliente->nome_completo ?? '',
                'cpf' => $model->cliente->cpf ?? '',
                'telefone' => $model->cliente->telefone ?? '',
                'endereco' => $model->cliente->endereco_logradouro ?? '',
                'numero' => $model->cliente->endereco_numero ?? '',
                'bairro' => $model->cliente->endereco_bairro ?? '',
                'cidade' => $model->cliente->endereco_cidade ?? '',
                'estado' => $model->cliente->endereco_estado ?? '',
            ];
        }

        return [
            'id' => $model->id,
            'usuario_id' => $model->usuario_id, // Identificador da loja
            'valor_total' => (float) $model->valor_total,
            'data_criacao' => $model->data_criacao,
            'status' => $model->status_venda_codigo, // Ex: QUITADA
            'forma_pagamento' => $model->formaPagamento ? $model->formaPagamento->nome : 'N/A',
            'cliente' => $cliente,
            'itens' => $itens,
        ];
    }

    protected function findModel($id)
    {
        if (($model = Venda::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('Venda não encontrada.');
    }
}
