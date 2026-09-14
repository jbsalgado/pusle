<?php

namespace app\modules\prestanista\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\Parcela;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\HistoricoCobranca;
use app\modules\vendas\models\FormaPagamento;
use app\modules\vendas\models\StatusParcela;
use app\modules\vendas\models\StatusVenda;
use app\modules\prestanista\controllers\CartaoController;

/**
 * CobradorController - App Mobile First do Cobrador de Rua (Offline-Ready)
 * Gerenciamento de Rotas, Baixas e Recebimentos em campo e Emissão de Cartões Atualizados
 */
class CobradorController extends Controller
{
    public $enableCsrfValidation = false; // Permite sincronização via Fetch / Service Worker offline

    /**
     * Tela Principal do App do Cobrador
     */
    public function actionIndex($cobrador_id = null, $loja_id = null)
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : ($loja_id ?: null);

        if (!$usuarioId && $cobrador_id) {
            $colab = Colaborador::findOne(['id' => $cobrador_id, 'ativo' => true]);
            if ($colab) {
                $usuarioId = $colab->usuario_id;
            }
        }

        if (!$usuarioId) {
            $primeiroColab = Colaborador::find()->where(['ativo' => true])->one();
            $usuarioId = $primeiroColab ? $primeiroColab->usuario_id : null;
        }

        $cobradores = [];
        $lojaNome = 'Pulse Prestanista';

        if ($usuarioId) {
            $uLoja = \app\models\Usuario::findOne($usuarioId);
            $lojaNome = $uLoja ? ($uLoja->nome_loja ?? $uLoja->nome ?? 'Pulse Prestanista') : 'Pulse Prestanista';
            $cobradores = Colaborador::find()
                ->where(['usuario_id' => $usuarioId, 'ativo' => true])
                ->andWhere(['or', ['eh_cobrador' => true], ['eh_cobrador' => null]])
                ->orderBy(['nome_completo' => SORT_ASC])
                ->all();
        }

        $this->layout = false; // Layout mobile app dedicado

        return $this->render('index', [
            'lojaNome' => $lojaNome,
            'cobradores' => $cobradores,
            'cobradorId' => $cobrador_id,
            'usuarioId' => $usuarioId,
        ]);
    }

    /**
     * Retorna a lista de clientes, cartões e parcelas da rota do cobrador para armazenar offline
     */
    public function actionDadosRota($cobrador_id = null, $loja_id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : ($loja_id ?: Yii::$app->request->get('loja_id'));

        if (!$usuarioId && $cobrador_id) {
            $colab = Colaborador::findOne(['id' => $cobrador_id, 'ativo' => true]);
            if ($colab) {
                $usuarioId = $colab->usuario_id;
            }
        }

        if (!$usuarioId) {
            $primeiroColab = Colaborador::find()->where(['ativo' => true])->one();
            $usuarioId = $primeiroColab ? $primeiroColab->usuario_id : null;
        }

        if (!$usuarioId) {
            return [
                'success' => true,
                'total' => 0,
                'rotas' => [],
                'mensagem' => 'Nenhuma loja identificada para carregar a rota.'
            ];
        }

        $query = Venda::findPrestanista($usuarioId)
            ->leftJoin('prest_clientes c', 'c.id = v.cliente_id')
            ->with(['cliente', 'itens.produto', 'parcelas.formaPagamento', 'vendedor'])
            ->andWhere(['v.status_venda_codigo' => ['EM_ABERTO', 'PARCIALMENTE_PAGA']])
            ->andWhere([
                'exists',
                (new \yii\db\Query())
                    ->from('prest_parcelas pp_pend')
                    ->where('pp_pend.venda_id = v.id')
                    ->andWhere(['pp_pend.status_parcela_codigo' => StatusParcela::PENDENTE])
            ])
            ->orderBy(['c.endereco_bairro' => SORT_ASC, 'c.endereco_logradouro' => SORT_ASC, 'v.id' => SORT_ASC]);

        if ($cobrador_id) {
            $query->andWhere([
                'exists',
                (new \yii\db\Query())
                    ->from('prest_parcelas pp')
                    ->where('pp.venda_id = v.id')
                    ->andWhere(['pp.cobrador_id' => $cobrador_id])
                    ->andWhere(['pp.status_parcela_codigo' => StatusParcela::PENDENTE])
            ]);
        }

        $cartoes = $query->all();

        $dados = [];
        foreach ($cartoes as $cartao) {
            $cliente = $cartao->cliente;
            $parcelas = $cartao->parcelas;

            $totalPago = 0;
            $parcelasData = [];
            $proximaPendente = null;

            foreach ($parcelas as $p) {
                $pago = ($p->status_parcela_codigo === StatusParcela::PAGA);
                if ($pago) {
                    $totalPago += (float)($p->valor_pago ?: $p->valor_parcela);
                } elseif (!$proximaPendente) {
                    $proximaPendente = $p;
                }

                $parcelasData[] = [
                    'id' => (string)$p->id,
                    'numero' => (int)$p->numero_parcela,
                    'data_vencimento' => $p->data_vencimento ? date('d/m/Y', strtotime($p->data_vencimento)) : '',
                    'data_vencimento_raw' => $p->data_vencimento,
                    'valor_parcela' => (float)$p->valor_parcela,
                    'status' => $p->status_parcela_codigo,
                    'data_pagamento' => $p->data_pagamento ? date('d/m/Y', strtotime($p->data_pagamento)) : '',
                    'valor_pago' => (float)($p->valor_pago ?: 0),
                    'tipo_pagamento' => $p->formaPagamento ? $p->formaPagamento->nome : '',
                ];
            }

            $saldoDevedor = max(0, (float)$cartao->valor_total - $totalPago);

            $produtosDesc = [];
            if (!empty($cartao->itens)) {
                foreach ($cartao->itens as $it) {
                    $nomeP = $it->produto ? $it->produto->nome : 'Item';
                    $produtosDesc[] = "{$it->quantidade}x {$nomeP}";
                }
            }

            $dados[] = [
                'venda_id' => (string)$cartao->id,
                'numero_cartao' => substr($cartao->id, 0, 8),
                'cliente' => [
                    'id' => $cliente ? (string)$cliente->id : null,
                    'nome' => $cliente ? $cliente->nome_completo : 'Sem Cliente',
                    'telefone' => $cliente ? ($cliente->getTelefoneFormatado() ?: $cliente->telefone) : '',
                    'logradouro' => $cliente ? $cliente->endereco_logradouro : '',
                    'numero' => $cliente ? $cliente->endereco_numero : '',
                    'bairro' => $cliente ? $cliente->endereco_bairro : '',
                    'cidade' => $cliente ? $cliente->endereco_cidade : '',
                    'complemento' => $cliente ? $cliente->endereco_complemento : '',
                ],
                'vendedor_nome' => $cartao->vendedor ? $cartao->vendedor->nome_completo : 'Venda Direta',
                'data_venda' => $cartao->data_venda ? date('d/m/Y', strtotime($cartao->data_venda)) : '',
                'valor_total' => (float)$cartao->valor_total,
                'total_pago' => (float)$totalPago,
                'saldo_devedor' => (float)$saldoDevedor,
                'numero_parcelas' => (int)$cartao->numero_parcelas,
                'produtos_descricao' => implode(', ', $produtosDesc),
                'public_url' => CartaoController::getPublicUrl($cartao->id),
                'parcelas' => $parcelasData,
                'proxima_parcela' => $proximaPendente ? [
                    'id' => (string)$proximaPendente->id,
                    'numero' => (int)$proximaPendente->numero_parcela,
                    'data_vencimento' => date('d/m/Y', strtotime($proximaPendente->data_vencimento)),
                    'valor' => (float)$proximaPendente->valor_parcela,
                ] : null,
            ];
        }

        return [
            'success' => true,
            'total' => count($dados),
            'rotas' => $dados,
        ];
    }

    /**
     * Sincroniza pagamentos/baixas realizados offline pelo cobrador de rua
     */
    public function actionSincronizar()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $rawBody = Yii::$app->request->getRawBody();
        $payload = json_decode($rawBody, true) ?: Yii::$app->request->post();

        $pagamentosOffline = $payload['pagamentos_offline'] ?? [];
        $tenantId = $payload['usuario_id'] ?? null;

        if (!$tenantId) {
            $usuario = Yii::$app->user->identity;
            $tenantId = $usuario ? $usuario->getTenantId() : null;
        }

        if (!$tenantId && !empty($pagamentosOffline[0]['cobrador_id'])) {
            $colab = Colaborador::findOne(['id' => $pagamentosOffline[0]['cobrador_id'], 'ativo' => true]);
            if ($colab) {
                $tenantId = $colab->usuario_id;
            }
        }

        if (!$tenantId) {
            $primeiroColab = Colaborador::find()->where(['ativo' => true])->one();
            $tenantId = $primeiroColab ? $primeiroColab->usuario_id : null;
        }

        if (empty($pagamentosOffline)) {
            return [
                'success' => true,
                'mensagem' => 'Nenhum pagamento offline para sincronizar.',
                'sincronizados' => [],
                'erros' => [],
            ];
        }

        $sincronizados = [];
        $erros = [];

        foreach ($pagamentosOffline as $item) {
            $offlineId = $item['offline_id'] ?? uniqid('pag_');
            $parcelaId = $item['parcela_id'] ?? null;
            $vendaId = $item['venda_id'] ?? null;
            $valorPago = (float)($item['valor_pago'] ?? 0);
            $dataPagamento = !empty($item['data_pagamento']) ? $item['data_pagamento'] : date('Y-m-d');
            $tipoPagamento = trim($item['tipo_pagamento'] ?? 'DINHEIRO');
            $cobradorId = $item['cobrador_id'] ?? null;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                $parcela = null;
                if ($parcelaId) {
                    $parcela = Parcela::findOne(['id' => $parcelaId]);
                } elseif ($vendaId) {
                    // Pega primeira pendente da venda
                    $parcela = Parcela::find()
                        ->where(['venda_id' => $vendaId, 'status_parcela_codigo' => StatusParcela::PENDENTE])
                        ->orderBy(['numero_parcela' => SORT_ASC])
                        ->one();
                }

                if (!$parcela) {
                    throw new \Exception("Parcela não encontrada para o pagamento {$offlineId}");
                }

                $cartao = Venda::findOne(['id' => $parcela->venda_id]);
                if (!$cartao) {
                    throw new \Exception("Venda não encontrada para a parcela {$parcela->id}");
                }

                if ($valorPago <= 0) {
                    $valorPago = (float)$parcela->valor_parcela;
                }

                // Localiza ou cria FormaPagamento
                $formaPagamento = FormaPagamento::find()
                    ->where(['usuario_id' => $tenantId, 'ativo' => true])
                    ->andWhere(['or',
                        ['ilike', 'nome', $tipoPagamento],
                        ['tipo' => $tipoPagamento]
                    ])
                    ->one();

                if (!$formaPagamento) {
                    $formaPagamento = new FormaPagamento();
                    $formaPagamento->usuario_id = $tenantId;
                    $formaPagamento->nome = mb_strtoupper($tipoPagamento, 'UTF-8');
                    $formaPagamento->tipo = in_array($tipoPagamento, [FormaPagamento::TIPO_PIX, FormaPagamento::TIPO_DINHEIRO, FormaPagamento::TIPO_CARTAO, FormaPagamento::TIPO_BOLETO]) ? $tipoPagamento : FormaPagamento::TIPO_OUTRO;
                    $formaPagamento->ativo = true;
                    $formaPagamento->save(false);
                }

                // Atualiza Parcela
                $parcela->status_parcela_codigo = StatusParcela::PAGA;
                $parcela->data_pagamento = $dataPagamento;
                $parcela->valor_pago = $valorPago;
                $parcela->forma_pagamento_id = $formaPagamento->id;
                if ($cobradorId) {
                    $parcela->cobrador_id = $cobradorId;
                }
                $parcela->save(false);

                // Registra Histórico de Cobrança
                $hist = new HistoricoCobranca();
                $hist->usuario_id = $tenantId;
                $hist->parcela_id = $parcela->id;
                $hist->cliente_id = $cartao->cliente_id;
                $hist->cobrador_id = $cobradorId ?: $cartao->colaborador_vendedor_id;
                $hist->tipo_acao = HistoricoCobranca::TIPO_PAGAMENTO;
                $hist->valor_recebido = $valorPago;
                $hist->observacao = "Recebimento da {$parcela->numero_parcela}ª prestação via {$formaPagamento->nome} (App Cobrador)";
                $hist->data_acao = $dataPagamento . ' ' . date('H:i:s');
                $hist->save(false);

                // Atualiza status da venda
                $pendentes = Parcela::find()
                    ->where(['venda_id' => $cartao->id])
                    ->andWhere(['!=', 'status_parcela_codigo', StatusParcela::PAGA])
                    ->count();

                if ($pendentes == 0) {
                    $cartao->status_venda_codigo = StatusVenda::QUITADA;
                } else {
                    $cartao->status_venda_codigo = StatusVenda::PARCIALMENTE_PAGA;
                }
                $cartao->save(false);

                $transaction->commit();

                $publicUrl = CartaoController::getPublicUrl($cartao->id);

                $sincronizados[] = [
                    'offline_id' => $offlineId,
                    'parcela_id' => (string)$parcela->id,
                    'venda_id' => (string)$cartao->id,
                    'numero_parcela' => $parcela->numero_parcela,
                    'valor_pago' => $valorPago,
                    'public_url' => $publicUrl,
                ];

            } catch (\Exception $e) {
                $transaction->rollBack();
                $erros[] = [
                    'offline_id' => $offlineId,
                    'erro' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => true,
            'total_processados' => count($pagamentosOffline),
            'total_sincronizados' => count($sincronizados),
            'sincronizados' => $sincronizados,
            'erros' => $erros,
        ];
    }
}
