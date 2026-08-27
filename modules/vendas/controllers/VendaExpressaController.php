<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\VendaItem;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\FormaPagamento;
use app\modules\vendas\models\StatusVenda;
use app\modules\vendas\models\Colaborador;

class VendaExpressaController extends Controller
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
                    'salvar' => ['POST'],
                ],
            ],
        ];
    }

    protected function getLojaId()
    {
        $usuario = Yii::$app->user->identity;
        if (!$usuario) return null;

        if ($usuario->eh_dono_loja === true || $usuario->eh_dono_loja === 't' || $usuario->eh_dono_loja === 1) {
            return $usuario->id;
        }

        $colaborador = Colaborador::getColaboradorLogado();
        return $colaborador ? $colaborador->usuario_id : $usuario->id;
    }

    /**
     * Tela Principal de Venda Expressa (Focado em Encarte & Catálogo)
     */
    public function actionIndex()
    {
        $lojaId = $this->getLojaId();

        $produtos = Produto::find()
            ->where(['usuario_id' => $lojaId, 'ativo' => true])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        $formasPagamento = FormaPagamento::find()
            ->where(['ativo' => true])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        // Resumo de Vendas de Hoje
        $resumoHoje = $this->getResumoHoje($lojaId);

        return $this->render('index', [
            'produtos' => $produtos,
            'formasPagamento' => $formasPagamento,
            'resumoHoje' => $resumoHoje,
            'lojaId' => $lojaId,
        ]);
    }

    /**
     * Ação AJAX para efetivar Venda Expressa
     */
    public function actionSalvar()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();

        if (!$lojaId) {
            return ['success' => false, 'message' => 'Usuário não autenticado.'];
        }

        $request = Yii::$app->request;
        $itensPost = $request->post('itens', []);
        $formaPagamentoId = $request->post('forma_pagamento_id', null);
        $observacoes = trim($request->post('observacoes', ''));

        if (empty($itensPost) || !is_array($itensPost)) {
            return ['success' => false, 'message' => 'Nenhum produto foi adicionado à venda.'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $valorTotalVenda = 0;
            $itensValidados = [];

            foreach ($itensPost as $itemRaw) {
                $prodId = $itemRaw['produto_id'] ?? null;
                $qtd = max(0.001, (float)($itemRaw['quantidade'] ?? 1));
                $precoUnit = (float)($itemRaw['preco_unitario'] ?? 0);

                if (!$prodId) continue;

                $produto = Produto::findOne(['id' => $prodId, 'usuario_id' => $lojaId]);
                if (!$produto) continue;

                if ($precoUnit <= 0) {
                    $precoUnit = (float)$produto->preco_venda_sugerido;
                }

                $subtotal = $qtd * $precoUnit;
                $valorTotalVenda += $subtotal;

                $itensValidados[] = [
                    'produto' => $produto,
                    'quantidade' => $qtd,
                    'preco_unitario' => $precoUnit,
                    'subtotal' => $subtotal,
                ];
            }

            if (empty($itensValidados)) {
                throw new \Exception('Nenhum produto válido foi encontrado para o registro da venda.');
            }

            // Criar Venda
            $venda = new Venda();
            $venda->usuario_id = $lojaId;
            $venda->data_venda = date('Y-m-d H:i:s');
            $venda->valor_total = $valorTotalVenda;
            $venda->numero_parcelas = 1;
            $venda->status_venda_codigo = StatusVenda::QUITADA;
            $venda->forma_pagamento_id = !empty($formaPagamentoId) ? $formaPagamentoId : null;
            $venda->observacoes = !empty($observacoes) ? $observacoes : 'Venda Expressa (Encarte & Catálogo)';

            if (!$venda->save()) {
                throw new \Exception('Erro ao salvar venda: ' . implode(', ', $venda->getFirstErrors()));
            }

            // Criar VendaItens e dar baixa de estoque se configurado
            foreach ($itensValidados as $itemData) {
                $p = $itemData['produto'];
                $vendaItem = new VendaItem();
                $vendaItem->venda_id = $venda->id;
                $vendaItem->produto_id = $p->id;
                $vendaItem->quantidade = $itemData['quantidade'];
                $vendaItem->preco_unitario_venda = $itemData['preco_unitario'];
                $vendaItem->valor_total_item = $itemData['subtotal'];
                $vendaItem->desconto_valor = 0;
                $vendaItem->desconto_percentual = 0;

                if (!$vendaItem->save()) {
                    throw new \Exception('Erro ao salvar item da venda.');
                }

                // Baixa de estoque opcional (se produto tiver estoque_atual cadastrado > 0)
                if ($p->estoque_atual !== null && $p->estoque_atual > 0) {
                    $p->estoque_atual = max(0, $p->estoque_atual - $itemData['quantidade']);
                    $p->save(false, ['estoque_atual']);
                }
            }

            // Gerar parcela paga para relatório de caixa
            $venda->gerarParcelas($formaPagamentoId, date('Y-m-d'), 30, true);

            $transaction->commit();

            return [
                'success' => true,
                'message' => 'Venda Expressa registrada com sucesso!',
                'venda_id' => $venda->id,
                'valor_total' => number_format($valorTotalVenda, 2, ',', '.'),
                'resumoHoje' => $this->getResumoHoje($lojaId),
            ];

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("VendaExpressaController::actionSalvar erro: " . $e->getMessage(), __METHOD__);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Calcula o resumo estatístico das vendas de hoje
     */
    protected function getResumoHoje($lojaId)
    {
        $todayStart = date('Y-m-d 00:00:00');

        $totalHoje = (float)Venda::find()
            ->where(['>=', 'data_venda', $todayStart])
            ->andWhere(['usuario_id' => $lojaId])
            ->andWhere(['status_venda_codigo' => StatusVenda::QUITADA])
            ->sum('valor_total');

        $qtdHoje = (int)Venda::find()
            ->where(['>=', 'data_venda', $todayStart])
            ->andWhere(['usuario_id' => $lojaId])
            ->andWhere(['status_venda_codigo' => StatusVenda::QUITADA])
            ->count();

        // Produto mais vendido hoje
        $topProdRow = (new \yii\db\Query())
            ->select(['p.nome', 'SUM(vi.quantidade) as total_qtd'])
            ->from('prest_venda_itens vi')
            ->innerJoin('prest_vendas v', 'v.id = vi.venda_id')
            ->innerJoin('prest_produtos p', 'p.id = vi.produto_id')
            ->where(['>=', 'v.data_venda', $todayStart])
            ->andWhere(['v.usuario_id' => $lojaId])
            ->andWhere(['v.status_venda_codigo' => StatusVenda::QUITADA])
            ->groupBy(['p.nome'])
            ->orderBy(['total_qtd' => SORT_DESC])
            ->limit(1)
            ->one();

        $topProdutoNome = $topProdRow ? $topProdRow['nome'] . ' (' . (float)$topProdRow['total_qtd'] . ' un)' : 'Nenhum item ainda';

        return [
            'valor_total' => number_format($totalHoje, 2, ',', '.'),
            'total_vendas' => $qtdHoje,
            'top_produto' => $topProdutoNome,
        ];
    }
}
