<?php

namespace app\modules\prestanista\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\data\Pagination;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\VendaItem;
use app\modules\vendas\models\Parcela;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\HistoricoCobranca;
use app\modules\vendas\models\Produto;

/**
 * Gestão de Cartões de Crediário Prestanista
 */
class CartaoController extends Controller
{
    public function actionIndex($q = null, $status = null, $vendedor_id = null, $cobrador_id = null)
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario->loja_id ?? $usuario->id;

        $query = Venda::find()
            ->alias('v')
            ->leftJoin('prest_clientes c', 'c.id = v.cliente_id')
            ->where(['v.usuario_id' => $usuarioId])
            ->orderBy(['v.id' => SORT_DESC]);

        if ($q) {
            $query->andFilterWhere([
                'or',
                ['ilike', 'c.nome_completo', $q],
                ['ilike', 'c.cpf', $q],
                ['ilike', 'c.telefone', $q],
                ['ilike', 'c.endereco_bairro', $q],
                ['ilike', 'c.endereco_logradouro', $q],
                ['cast(v.id as text)' => $q]
            ]);
        }

        if ($status === 'ABERTO') {
            $query->andWhere(['v.status_venda_codigo' => 'EM_ABERTO']);
        } elseif ($status === 'QUITADO') {
            $query->andWhere(['v.status_venda_codigo' => 'FINALIZADA']);
        }

        if ($vendedor_id) {
            $query->andWhere(['v.colaborador_vendedor_id' => $vendedor_id]);
        }

        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count(), 'pageSize' => 15]);
        $cartoes = $query->offset($pages->offset)->limit($pages->limit)->all();

        $vendedores = Colaborador::find()->where(['usuario_id' => $usuarioId, 'ativo' => true])->all();

        return $this->render('index', [
            'cartoes' => $cartoes,
            'pages' => $pages,
            'q' => $q,
            'status' => $status,
            'vendedor_id' => $vendedor_id,
            'vendedores' => $vendedores,
        ]);
    }

    public function actionView($id)
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario->loja_id ?? $usuario->id;

        $cartao = Venda::find()
            ->where(['id' => $id, 'usuario_id' => $usuarioId])
            ->with(['cliente', 'itens.produto', 'parcelas'])
            ->one();

        if (!$cartao) {
            throw new NotFoundHttpException('Cartão de crediário não encontrado.');
        }

        // Histórico de cobranças e baixas
        $historico = HistoricoCobranca::find()
            ->where(['usuario_id' => $usuarioId, 'cliente_id' => $cartao->cliente_id])
            ->with('cobrador')
            ->orderBy(['data_acao' => SORT_ASC])
            ->all();

        return $this->render('view', [
            'cartao' => $cartao,
            'historico' => $historico,
        ]);
    }

    public function actionImprimir($id, $formato = 'a4')
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario->loja_id ?? $usuario->id;

        $cartao = Venda::find()
            ->where(['id' => $id, 'usuario_id' => $usuarioId])
            ->with(['cliente', 'itens.produto', 'parcelas'])
            ->one();

        if (!$cartao) {
            throw new NotFoundHttpException('Cartão de crediário não encontrado.');
        }

        $this->layout = false; // Layout limpo para impressão direta

        return $this->render('imprimir', [
            'cartao' => $cartao,
            'formato' => $formato,
        ]);
    }

    public function actionNovo()
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario->loja_id ?? $usuario->id;

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $clienteId = $post['cliente_id'] ?? null;
                $vendedorId = $post['vendedor_id'] ?? null;
                $frequencia = (int)($post['frequencia'] ?? 7);
                $parcelasQtd = max(1, (int)($post['numero_parcelas'] ?? 1));
                $itens = $post['itens'] ?? [];
                $entrada = (float)str_replace(',', '.', str_replace('.', '', $post['valor_entrada'] ?? '0'));

                if (!$clienteId) {
                    throw new \Exception('Selecione um cliente.');
                }
                if (empty($itens)) {
                    throw new \Exception('Adicione ao menos um produto no cartão.');
                }

                $valorTotal = 0;
                foreach ($itens as $item) {
                    $valorTotal += ((float)$item['preco']) * ((int)$item['quantidade']);
                }

                $venda = new Venda();
                $venda->usuario_id = $usuarioId;
                $venda->cliente_id = $clienteId;
                $venda->colaborador_vendedor_id = $vendedorId;
                $venda->valor_total = $valorTotal;
                $venda->numero_parcelas = $parcelasQtd;
                $venda->data_venda = date('Y-m-d H:i:s');
                $venda->status_venda_codigo = 'EM_ABERTO';
                $venda->observacoes = 'Cartão Prestanista emitido via Gestão';

                if (!$venda->save()) {
                    throw new \Exception('Erro ao criar cartão: ' . json_encode($venda->errors));
                }

                foreach ($itens as $item) {
                    $vi = new VendaItem();
                    $vi->venda_id = $venda->id;
                    $vi->produto_id = $item['produto_id'];
                    $vi->quantidade = $item['quantidade'];
                    $vi->preco_unitario_venda = $item['preco'];
                    $vi->valor_total_item = $vi->quantidade * $vi->preco_unitario_venda;
                    if (!$vi->save()) {
                        throw new \Exception('Erro ao salvar item: ' . json_encode($vi->errors));
                    }
                }

                // Gera parcelas
                $saldoAFinanciar = max(0, $valorTotal - $entrada);
                $primeiroVencimento = !empty($post['primeiro_vencimento']) ? $post['primeiro_vencimento'] : date('Y-m-d', strtotime("+{$frequencia} days"));
                
                $venda->gerarParcelas(null, $primeiroVencimento, $frequencia);

                // Se teve entrada, dá baixa na 1ª parcela ou cria registro de entrada
                if ($entrada > 0) {
                    $p1 = Parcela::find()->where(['venda_id' => $venda->id])->orderBy(['numero_parcela' => SORT_ASC])->one();
                    if ($p1) {
                        $p1->valor_pago = $entrada;
                        if ($entrada >= $p1->valor_parcela) {
                            $p1->status_parcela_codigo = 'PAGA';
                            $p1->data_pagamento = date('Y-m-d');
                        }
                        $p1->save(false);
                    }
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', "Cartão #{$venda->id} emitido com sucesso!");
                return $this->redirect(['view', 'id' => $venda->id]);

            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        $clientes = Cliente::find()->where(['usuario_id' => $usuarioId])->orderBy(['nome_completo' => SORT_ASC])->limit(100)->all();
        $produtos = Produto::find()->where(['usuario_id' => $usuarioId])->orderBy(['nome' => SORT_ASC])->limit(100)->all();
        $vendedores = Colaborador::find()->where(['usuario_id' => $usuarioId, 'ativo' => true])->all();

        return $this->render('novo', [
            'clientes' => $clientes,
            'produtos' => $produtos,
            'vendedores' => $vendedores,
        ]);
    }
}
