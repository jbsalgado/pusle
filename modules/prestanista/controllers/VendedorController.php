<?php

namespace app\modules\prestanista\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\VendaItem;
use app\modules\vendas\models\Parcela;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\FormaPagamento;
use app\modules\vendas\models\StatusParcela;
use app\modules\vendas\models\StatusVenda;
use app\modules\prestanista\controllers\CartaoController;

/**
 * VendedorController - App Mobile First do Vendedor Ambulante (Offline-Ready)
 */
class VendedorController extends Controller
{
    public $enableCsrfValidation = false; // Permite sincronização via Fetch / Service Worker offline

    /**
     * Tela Principal do App do Vendedor
     */
    public function actionIndex()
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : null;

        // Se não autenticado via sessão, tenta recuperar usuário padrão ou aguarda sync
        $vendedores = [];
        $produtos = [];
        $clientes = [];
        $lojaNome = 'Pulse Prestanista';

        if ($usuarioId) {
            $lojaNome = $usuario->nome_loja ?? $usuario->nome ?? 'Pulse Prestanista';
            $vendedores = Colaborador::find()
                ->where(['usuario_id' => $usuarioId, 'ativo' => true])
                ->andWhere(['or', ['eh_vendedor' => true], ['eh_vendedor' => null]])
                ->orderBy(['nome_completo' => SORT_ASC])
                ->all();

            $produtos = Produto::find()
                ->where(['usuario_id' => $usuarioId, 'ativo' => true])
                ->orderBy(['nome' => SORT_ASC])
                ->limit(300)
                ->all();

            $clientes = Cliente::find()
                ->where(['usuario_id' => $usuarioId, 'ativo' => true])
                ->orderBy(['nome_completo' => SORT_ASC])
                ->limit(300)
                ->all();
        }

        $this->layout = false; // Layout mobile app dedicado

        return $this->render('index', [
            'lojaNome' => $lojaNome,
            'vendedores' => $vendedores,
            'produtos' => $produtos,
            'clientes' => $clientes,
            'usuarioId' => $usuarioId,
        ]);
    }

    /**
     * API para carga e atualização do banco offline do vendedor
     */
    public function actionDadosIniciais()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : null;

        if (!$usuarioId) {
            // Tenta pegar primeiro tenant ativo para demo ou campo
            $primeiroColab = Colaborador::find()->where(['ativo' => true])->one();
            $usuarioId = $primeiroColab ? $primeiroColab->usuario_id : null;
        }

        $vendedores = Colaborador::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->andWhere(['or', ['eh_vendedor' => true], ['eh_vendedor' => null]])
            ->orderBy(['nome_completo' => SORT_ASC])
            ->all();

        $produtos = Produto::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->orderBy(['nome' => SORT_ASC])
            ->limit(500)
            ->all();

        $clientes = Cliente::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->orderBy(['nome_completo' => SORT_ASC])
            ->limit(500)
            ->all();

        return [
            'success' => true,
            'usuario_id' => $usuarioId,
            'loja_nome' => $usuario ? ($usuario->nome_loja ?? $usuario->nome) : 'Pulse Prestanista',
            'vendedores' => array_map(function ($v) {
                return [
                    'id' => (string)$v->id,
                    'nome' => $v->nome_completo,
                    'telefone' => $v->telefone,
                ];
            }, $vendedores),
            'produtos' => array_map(function ($p) {
                return [
                    'id' => (string)$p->id,
                    'nome' => $p->nome,
                    'preco' => (float)($p->preco_venda_sugerido ?: $p->preco_custo ?: 0),
                    'codigo' => $p->codigo_referencia ?: substr($p->id, 0, 6),
                    'estoque' => (int)$p->estoque_atual,
                ];
            }, $produtos),
            'clientes' => array_map(function ($c) {
                return [
                    'id' => (string)$c->id,
                    'nome' => $c->nome_completo,
                    'cpf' => $c->cpf,
                    'telefone' => $c->getTelefoneFormatado() ?: $c->telefone,
                    'logradouro' => $c->endereco_logradouro,
                    'numero' => $c->endereco_numero,
                    'bairro' => $c->endereco_bairro,
                    'cidade' => $c->endereco_cidade,
                    'estado' => $c->endereco_estado,
                    'cep' => $c->endereco_cep,
                ];
            }, $clientes),
        ];
    }

    /**
     * Recebe lote de vendas criadas offline para persistência e geração oficial de cartões
     */
    public function actionSincronizar()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $rawBody = Yii::$app->request->getRawBody();
        $payload = json_decode($rawBody, true) ?: Yii::$app->request->post();

        $vendasOffline = $payload['vendas_offline'] ?? [];
        $tenantId = $payload['usuario_id'] ?? null;

        if (!$tenantId) {
            $usuario = Yii::$app->user->identity;
            $tenantId = $usuario ? $usuario->getTenantId() : null;
        }

        if (!$tenantId) {
            // Fallback tenant
            $primeiroColab = Colaborador::find()->where(['ativo' => true])->one();
            $tenantId = $primeiroColab ? $primeiroColab->usuario_id : null;
        }

        if (empty($vendasOffline)) {
            return [
                'success' => true,
                'mensagem' => 'Nenhuma venda offline para sincronizar.',
                'sincronizados' => [],
                'erros' => [],
            ];
        }

        // Garante Forma de Pagamento Prestanista
        $formaPagamento = FormaPagamento::find()
            ->where(['usuario_id' => $tenantId, 'ativo' => true])
            ->andWhere(['or',
                ['ilike', 'nome', 'crediario'],
                ['ilike', 'nome', 'carnê'],
                ['ilike', 'nome', 'carne'],
                ['ilike', 'nome', 'prestanista'],
                ['tipo' => [FormaPagamento::TIPO_BOLETO, FormaPagamento::TIPO_OUTRO]]
            ])
            ->one();

        if (!$formaPagamento) {
            $formaPagamento = FormaPagamento::find()->where(['usuario_id' => $tenantId, 'ativo' => true])->one();
        }

        if (!$formaPagamento) {
            $formaPagamento = new FormaPagamento();
            $formaPagamento->usuario_id = $tenantId;
            $formaPagamento->nome = 'Crediário / Prestanista';
            $formaPagamento->tipo = FormaPagamento::TIPO_BOLETO;
            $formaPagamento->ativo = true;
            $formaPagamento->aceita_parcelamento = true;
            $formaPagamento->save(false);
        }

        $sincronizados = [];
        $erros = [];

        foreach ($vendasOffline as $itemVenda) {
            $tempId = $itemVenda['temp_id'] ?? uniqid('venda_');
            $transaction = Yii::$app->db->beginTransaction();

            try {
                // 1. Resolve ou Cadastra Cliente
                $clienteData = $itemVenda['cliente'] ?? [];
                $clienteId = $clienteData['id'] ?? null;

                $cliente = null;
                if ($clienteId) {
                    $cliente = Cliente::findOne(['id' => $clienteId, 'usuario_id' => $tenantId]);
                }

                if (!$cliente && !empty($clienteData['nome'])) {
                    // Tenta achar por CPF ou telefone
                    if (!empty($clienteData['cpf'])) {
                        $cliente = Cliente::find()
                            ->where(['usuario_id' => $tenantId])
                            ->andWhere(['cpf' => preg_replace('/\D/', '', $clienteData['cpf'])])
                            ->one();
                    }

                    if (!$cliente) {
                        $cliente = new Cliente();
                        $cliente->usuario_id = $tenantId;
                        $cliente->nome_completo = trim($clienteData['nome']);
                        $cliente->cpf = !empty($clienteData['cpf']) ? preg_replace('/\D/', '', $clienteData['cpf']) : null;
                        $cliente->telefone = !empty($clienteData['telefone']) ? preg_replace('/\D/', '', $clienteData['telefone']) : '00000000000';
                        $cliente->senha = '123456';
                        $cliente->endereco_logradouro = !empty($clienteData['logradouro']) ? $clienteData['logradouro'] : 'Rua';
                        $cliente->endereco_numero = !empty($clienteData['numero']) ? $clienteData['numero'] : 'S/N';
                        $cliente->endereco_bairro = !empty($clienteData['bairro']) ? $clienteData['bairro'] : 'Bairro';
                        $cliente->endereco_cidade = !empty($clienteData['cidade']) ? $clienteData['cidade'] : 'Franca';
                        $cliente->endereco_estado = $clienteData['estado'] ?? 'SP';
                        $cliente->endereco_cep = !empty($clienteData['cep']) ? preg_replace('/\D/', '', $clienteData['cep']) : null;
                        $cliente->ativo = true;
                        if (!$cliente->save()) {
                            throw new \Exception('Erro ao cadastrar cliente: ' . json_encode($cliente->errors));
                        }
                    }
                }

                if (!$cliente) {
                    throw new \Exception('Cliente não informado ou inválido na venda ' . $tempId);
                }

                // 2. Calcula Totais dos Itens
                $itens = $itemVenda['itens'] ?? [];
                if (empty($itens)) {
                    throw new \Exception('A venda não contém itens.');
                }

                $valorTotal = 0;
                foreach ($itens as $it) {
                    $qtd = max(1, (int)($it['quantidade'] ?? 1));
                    $prc = (float)($it['preco'] ?? 0);
                    $valorTotal += ($qtd * $prc);
                }

                $frequencia = (int)($itemVenda['frequencia'] ?? 7);
                $numeroParcelas = max(1, (int)($itemVenda['numero_parcelas'] ?? 1));
                $vendedorId = $itemVenda['vendedor_id'] ?? null;
                $dataVendaInput = !empty($itemVenda['data_venda']) ? $itemVenda['data_venda'] : date('Y-m-d');
                $primeiroVencimento = !empty($itemVenda['data_primeiro_vencimento']) 
                    ? $itemVenda['data_primeiro_vencimento'] 
                    : date('Y-m-d', strtotime("{$dataVendaInput} +{$frequencia} days"));
                $entrada = (float)($itemVenda['valor_entrada'] ?? 0);

                // 3. Cria Venda Prestanista
                $venda = new Venda();
                $venda->usuario_id = $tenantId;
                $venda->cliente_id = $cliente->id;
                $venda->colaborador_vendedor_id = $vendedorId;
                $venda->forma_pagamento_id = $formaPagamento->id;
                $venda->valor_total = $valorTotal;
                $venda->numero_parcelas = $numeroParcelas;
                $venda->data_venda = $dataVendaInput . ' ' . date('H:i:s');
                $venda->data_primeiro_vencimento = $primeiroVencimento;
                $venda->status_venda_codigo = 'EM_ABERTO';
                $venda->observacoes = "[PRESTANISTA] [FREQ:{$frequencia}] Venda Ambulante Offline sincronizada via App";

                if (!$venda->save()) {
                    throw new \Exception('Erro ao salvar venda: ' . json_encode($venda->errors));
                }

                // 4. Cria VendaItens
                foreach ($itens as $it) {
                    $produtoId = $it['produto_id'] ?? null;
                    // Se não tiver produtoId, localiza ou usa genérico
                    if (!$produtoId) {
                        $prodGen = Produto::find()->where(['usuario_id' => $tenantId, 'ativo' => true])->one();
                        $produtoId = $prodGen ? $prodGen->id : null;
                    }

                    $vi = new VendaItem();
                    $vi->venda_id = $venda->id;
                    $vi->produto_id = $produtoId;
                    $vi->quantidade = max(1, (int)($it['quantidade'] ?? 1));
                    $vi->preco_unitario_venda = (float)($it['preco'] ?? 0);
                    $vi->valor_total_item = $vi->quantidade * $vi->preco_unitario_venda;
                    if (!$vi->save()) {
                        throw new \Exception('Erro ao salvar item da venda: ' . json_encode($vi->errors));
                    }
                }

                // 5. Gera Parcelas Oficiais
                $venda->gerarParcelas($formaPagamento->id, $primeiroVencimento, $frequencia);

                // 6. Registra Entrada se houver
                if ($entrada > 0) {
                    $p1 = Parcela::find()->where(['venda_id' => $venda->id])->orderBy(['numero_parcela' => SORT_ASC])->one();
                    if ($p1) {
                        $p1->valor_pago = $entrada;
                        if ($entrada >= $p1->valor_parcela) {
                            $p1->status_parcela_codigo = StatusParcela::PAGA;
                            $p1->data_pagamento = $dataVendaInput;
                        }
                        $p1->save(false);
                    }
                }

                $transaction->commit();

                // Gera URL Pública Oficial do Cartão
                $publicUrl = CartaoController::getPublicUrl($venda->id);

                $sincronizados[] = [
                    'temp_id' => $tempId,
                    'venda_id' => (string)$venda->id,
                    'cliente_nome' => $cliente->nome,
                    'valor_total' => $valorTotal,
                    'public_url' => $publicUrl,
                    'numero_cartao' => substr($venda->id, 0, 8),
                ];

            } catch (\Exception $e) {
                $transaction->rollBack();
                $erros[] = [
                    'temp_id' => $tempId,
                    'erro' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => true,
            'total_processados' => count($vendasOffline),
            'total_sincronizados' => count($sincronizados),
            'sincronizados' => $sincronizados,
            'erros' => $erros,
        ];
    }
}
