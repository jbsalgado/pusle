<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\Orcamento;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\modules\vendas\search\OrcamentoSearch;
use yii\helpers\Url;

class OrcamentoController extends Controller
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
        $searchModel = new OrcamentoSearch();
        $searchModel->usuario_id = Yii::$app->user->id;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // Registra CSS
        $this->view->registerCssFile(
            '@web/css/orcamento-list.css',
            ['depends' => [\yii\web\YiiAsset::class]]
        );

        // Registra JS
        $this->view->registerJsFile(
            '@web/js/orcamento-list.js',
            ['depends' => [\yii\web\JqueryAsset::class]]
        );

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Redireciona para o módulo de Venda Direta carregando o orçamento no carrinho
     * @param string $id UUID do orçamento
     */
    public function actionConverter($id)
    {
        $model = $this->findModel($id);

        if ($model->status === Orcamento::STATUS_CONVERTIDO) {
            Yii::$app->session->setFlash('warning', 'Este orçamento já foi convertido em venda.');
            return $this->redirect(['index']);
        }

        // Constrói URL para o PWA de Venda Direta
        // Usa o alias @web para garantir que aponte para a pasta web/venda-direta/index.html
        $urlVendaDireta = Url::to("@web/venda-direta/index.html", true) . "?orcamento_id=" . $id;

        return $this->redirect($urlVendaDireta);
    }

    /**
     * Retorna detalhes completos de um orçamento para impressão
     * @param int $id
     * @return array JSON
     */
    public function actionDetalhes($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $model = $this->findModel($id);

        // Carrega itens com produtos
        $itens = [];
        foreach ($model->itens as $item) {
            $itens[] = [
                'id' => $item->produto_id, // ID do produto para o PWA
                'nome' => $item->produto ? $item->produto->nome : 'Produto',
                'quantidade' => (float) $item->quantidade,
                'preco_venda_sugerido' => (float) $item->preco_unitario, // Para compatibilidade com cart.js
                'preco_final' => (float) $item->preco_unitario,
                'subtotal' => (float) $item->subtotal,
                'unidade_medida' => $item->produto ? $item->produto->unidade_medida : 'un',
                'venda_fracionada' => $item->produto ? (bool)$item->produto->venda_fracionada : false,
                'fotos' => $item->produto && $item->produto->fotos ? $item->produto->fotos : [],
            ];
        }

        // Dados do cliente se houver
        $cliente = null;
        if ($model->cliente) {
            $cliente = [
                'nome' => $model->cliente->nome_completo ?? $model->cliente->nome ?? '',
                'cpf' => $model->cliente->cpf ?? '',
                'telefone' => $model->cliente->telefone ?? '',
                'endereco' => $model->cliente->endereco_logradouro ?? $model->cliente->endereco ?? '',
                'numero' => $model->cliente->endereco_numero ?? $model->cliente->numero ?? '',
                'complemento' => $model->cliente->endereco_complemento ?? $model->cliente->complemento ?? '',
                'bairro' => $model->cliente->endereco_bairro ?? $model->cliente->bairro ?? '',
                'cidade' => $model->cliente->endereco_cidade ?? $model->cliente->cidade ?? '',
                'estado' => $model->cliente->endereco_estado ?? $model->cliente->estado ?? '',
                'cep' => $model->cliente->endereco_cep ?? $model->cliente->cep ?? '',
            ];
        }

        return [
            'id' => $model->id,
            'usuario_id' => $model->usuario_id, // ID do Dono da Loja
            'valor_total' => (float) $model->valor_total,
            'status' => $model->status,
            'data_criacao' => $model->data_criacao,
            'observacoes' => $model->observacoes,
            'cliente' => $cliente,
            'itens' => $itens,
            'forma_pagamento' => 'A Combinar',
            'numero_parcelas' => 1,
        ];
    }

    protected function findModel($id)
    {
        if (($model = Orcamento::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('A página solicitada não existe.');
    }
}
