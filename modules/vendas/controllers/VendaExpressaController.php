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
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\LojaConfiguracao;
use app\modules\vendas\helpers\FormaPagamentoHelper;
use app\models\Usuario;

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
        $usuarioLoja = Usuario::findOne($lojaId);
        $temMercadoPago = $usuarioLoja ? $usuarioLoja->temMercadoPagoConfigurado() : false;

        $produtos = Produto::find()
            ->where(['usuario_id' => $lojaId, 'ativo' => true])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        // Formas de pagamento estritamente da loja logada (elimina duplicidades)
        $formasPagamento = FormaPagamento::find()
            ->where(['usuario_id' => $lojaId, 'ativo' => true])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        if (empty($formasPagamento)) {
            FormaPagamentoHelper::createDefaults($lojaId);
            $formasPagamento = FormaPagamento::find()
                ->where(['usuario_id' => $lojaId, 'ativo' => true])
                ->orderBy(['nome' => SORT_ASC])
                ->all();
        }

        // Carrega Dados da Loja para QR Code PIX e Mercado Pago
        $lojaConfig = LojaConfiguracao::findOne(['usuario_id' => $lojaId]);

        // Resumo de Vendas de Hoje
        $resumoHoje = $this->getResumoHoje($lojaId);

        return $this->render('index', [
            'produtos' => $produtos,
            'formasPagamento' => $formasPagamento,
            'resumoHoje' => $resumoHoje,
            'lojaConfig' => $lojaConfig,
            'temMercadoPago' => $temMercadoPago,
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

        // Dados do Cliente (para disparos WhatsApp via Evolution API)
        $clienteNome = trim($request->post('cliente_nome', ''));
        $clienteCpfRaw = trim($request->post('cliente_cpf', ''));
        $clienteWhatsappRaw = trim($request->post('cliente_whatsapp', ''));

        // Desconto e Acréscimo Geral
        $descontoValorInput = (float)str_replace(',', '.', $request->post('desconto_valor', 0));
        $descontoTipo = strtoupper(trim($request->post('desconto_tipo', 'VALOR'))); // VALOR ou PERCENTUAL
        $acrescimoValorInput = (float)str_replace(',', '.', $request->post('acrescimo_valor', 0));
        $acrescimoTipo = strtoupper(trim($request->post('acrescimo_tipo', 'VALOR'))); // VALOR ou PERCENTUAL

        if (empty($formaPagamentoId)) {
            $fpPadrao = FormaPagamento::find()->where(['ativo' => true])->orderBy(['nome' => SORT_ASC])->one();
            if ($fpPadrao) {
                $formaPagamentoId = $fpPadrao->id;
            }
        }

        if (empty($itensPost) || !is_array($itensPost)) {
            return ['success' => false, 'message' => 'Nenhum produto foi adicionado à venda.'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Processamento do Cadastro de Cliente
            $cliente = null;
            $cpfClean = preg_replace('/[^0-9]/', '', $clienteCpfRaw);
            $whatsappClean = preg_replace('/[^0-9]/', '', $clienteWhatsappRaw);

            if (!empty($whatsappClean)) {
                $cliente = Cliente::find()
                    ->where(['usuario_id' => $lojaId])
                    ->andWhere(['or', ['telefone' => $whatsappClean], ['like', 'telefone', $whatsappClean]])
                    ->one();
            }

            if (!$cliente && !empty($cpfClean)) {
                $cliente = Cliente::find()
                    ->where(['usuario_id' => $lojaId, 'cpf' => $cpfClean])
                    ->one();
            }

            if (!$cliente && (!empty($clienteNome) || !empty($whatsappClean) || !empty($cpfClean))) {
                $cliente = new Cliente();
                $cliente->usuario_id = $lojaId;
                $cliente->nome_completo = !empty($clienteNome) ? $clienteNome : 'Cliente ' . ($whatsappClean ?: 'WhatsApp');
                $cliente->telefone = !empty($whatsappClean) ? $whatsappClean : '00000000000';
                $cliente->cpf = (!empty($cpfClean) && strlen($cpfClean) === 11) ? $cpfClean : null;
                $cliente->ativo = true;
                $cliente->senha = '1234';
                $cliente->endereco_logradouro = 'Não informado';
                $cliente->endereco_numero = 'S/N';
                $cliente->endereco_bairro = 'Geral';
                $cliente->endereco_cidade = 'Caruaru';
                $cliente->save(false);
            } else if ($cliente) {
                if (!empty($clienteNome)) $cliente->nome_completo = $clienteNome;
                if (!empty($cpfClean) && strlen($cpfClean) === 11) $cliente->cpf = $cpfClean;
                if (!empty($whatsappClean)) $cliente->telefone = $whatsappClean;
                $cliente->save(false);
            }

            $subtotalItens = 0;
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
                $subtotalItens += $subtotal;

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

            // Cálculo do Desconto Geral
            $valDesconto = 0;
            if ($descontoValorInput > 0) {
                if ($descontoTipo === 'PERCENTUAL') {
                    $valDesconto = $subtotalItens * ($descontoValorInput / 100);
                } else {
                    $valDesconto = $descontoValorInput;
                }
            }

            // Cálculo do Acréscimo Geral
            $valAcrescimo = 0;
            if ($acrescimoValorInput > 0) {
                if ($acrescimoTipo === 'PERCENTUAL') {
                    $valAcrescimo = $subtotalItens * ($acrescimoValorInput / 100);
                } else {
                    $valAcrescimo = $acrescimoValorInput;
                }
            }

            $valorTotalFinal = max(0, $subtotalItens - $valDesconto + $valAcrescimo);

            // Criar Venda
            $venda = new Venda();
            $venda->usuario_id = $lojaId;
            $venda->cliente_id = $cliente ? $cliente->id : null;
            $venda->cpf_consumidor = (!empty($cpfClean) && strlen($cpfClean) === 11) ? $cpfClean : ($cliente ? $cliente->cpf : null);
            $venda->data_venda = date('Y-m-d H:i:s');
            $venda->valor_total = $valorTotalFinal;
            $venda->acrescimo_valor = $valAcrescimo;
            $venda->acrescimo_tipo = $valAcrescimo > 0 ? $acrescimoTipo : null;
            $venda->numero_parcelas = 1;
            $venda->status_venda_codigo = StatusVenda::QUITADA;
            $venda->forma_pagamento_id = !empty($formaPagamentoId) ? $formaPagamentoId : null;

            $obsCompleta = [];
            if (!empty($observacoes)) $obsCompleta[] = $observacoes;
            if ($valDesconto > 0) $obsCompleta[] = 'Desconto Aplicado: R$ ' . number_format($valDesconto, 2, ',', '.');
            if ($valAcrescimo > 0) $obsCompleta[] = 'Acréscimo Aplicado: R$ ' . number_format($valAcrescimo, 2, ',', '.');
            $venda->observacoes = implode(' | ', $obsCompleta) ?: 'Venda Expressa (Encarte & Catálogo)';

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
                'valor_total' => number_format($valorTotalFinal, 2, ',', '.'),
                'cliente_nome' => $cliente ? $cliente->nome_completo : null,
                'cliente_telefone' => $cliente ? $cliente->telefone : $clienteWhatsappRaw,
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
