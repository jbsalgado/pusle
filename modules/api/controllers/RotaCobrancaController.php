<?php
namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use app\modules\vendas\models\CarteiraCobranca;
use app\modules\vendas\models\Parcela;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\VendaItem;
use app\modules\vendas\models\PeriodoCobranca;

/**
 * API Controller para Rotas de Cobrança
 */
class RotaCobrancaController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        
        // CORS - Corrigido: não pode usar '*' com credentials: true
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['http://localhost', 'http://localhost:*', 'http://127.0.0.1', 'http://127.0.0.1:*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => false, // Não pode ser true com wildcard
                'Access-Control-Max-Age' => 86400,
            ],
        ];
        
        return $behaviors;
    }

    /**
     * GET /api/rota-cobranca/dia?cobrador_id=xxx&usuario_id=yyy&data=2025-01-15
     * Retorna a rota do dia para um cobrador específico
     */
    public function actionDia($cobrador_id = null, $usuario_id = null, $data = null)
    {
        try {
            if (!$cobrador_id || !$usuario_id) {
                Yii::$app->response->statusCode = 400;
                return ['erro' => 'cobrador_id e usuario_id são obrigatórios'];
            }

            // Se não informada, usa data de hoje
            if (!$data) {
                $data = date('Y-m-d');
            }

            // Busca período ativo (EM_COBRANCA ou ABERTO) para o usuário
            $periodoAtual = PeriodoCobranca::getPeriodoAtual($usuario_id);
            
            if (!$periodoAtual) {
                // Log para debug
                Yii::warning("Nenhum período ativo encontrado para usuario_id: {$usuario_id}", __METHOD__);
                
                // Lista todos os períodos para debug
                $todosPeriodos = PeriodoCobranca::find()
                    ->where(['usuario_id' => $usuario_id])
                    ->orderBy(['ano_referencia' => SORT_DESC, 'mes_referencia' => SORT_DESC])
                    ->all();
                
                $periodosInfo = [];
                foreach ($todosPeriodos as $p) {
                    $periodosInfo[] = [
                        'id' => $p->id,
                        'descricao' => $p->descricao,
                        'status' => $p->status,
                        'data_inicio' => $p->data_inicio,
                        'data_fim' => $p->data_fim
                    ];
                }
                
                Yii::$app->response->statusCode = 404;
                return [
                    'erro' => 'Nenhum período de cobrança ativo encontrado. Crie um período e defina-o como "Em Cobrança" ou "Aberto".',
                    'debug' => [
                        'usuario_id' => $usuario_id,
                        'periodos_encontrados' => $periodosInfo
                    ]
                ];
            }

            // Log para debug
            Yii::info("Período ativo encontrado: {$periodoAtual->id} - {$periodoAtual->descricao} (Status: {$periodoAtual->status})", __METHOD__);

            // Busca carteiras de cobrança ativas do cobrador APENAS do período ativo
            $carteiras = CarteiraCobranca::find()
                ->alias('c')
                ->leftJoin('prest_rotas_cobranca r', 'r.id = c.rota_id')
                ->where([
                    'c.cobrador_id' => $cobrador_id,
                    'c.usuario_id' => $usuario_id,
                    'c.periodo_id' => $periodoAtual->id, // ✅ FILTRO POR PERÍODO ATIVO
                    'c.ativo' => true
                ])
                ->with(['cliente', 'rota', 'periodo'])
                ->orderBy(['r.ordem_execucao' => SORT_ASC, 'c.data_distribuicao' => SORT_ASC])
                ->all();
            
            // Log para debug
            Yii::info("Carteiras encontradas para cobrador {$cobrador_id} no período {$periodoAtual->id}: " . count($carteiras), __METHOD__);
            
            if (count($carteiras) === 0) {
                // Debug: verifica se há carteiras sem filtro de período
                $todasCarteiras = CarteiraCobranca::find()
                    ->where([
                        'cobrador_id' => $cobrador_id,
                        'usuario_id' => $usuario_id,
                        'ativo' => true
                    ])
                    ->all();
                
                $carteirasInfo = [];
                foreach ($todasCarteiras as $c) {
                    $carteirasInfo[] = [
                        'id' => $c->id,
                        'periodo_id' => $c->periodo_id,
                        'cliente_id' => $c->cliente_id,
                        'ativo' => $c->ativo
                    ];
                }
                
                Yii::warning("Nenhuma carteira encontrada para o período ativo. Total de carteiras do cobrador (sem filtro de período): " . count($todasCarteiras), __METHOD__);
                
                return [
                    'erro' => 'Nenhuma carteira encontrada para este cobrador no período ativo.',
                    'debug' => [
                        'periodo_ativo_id' => $periodoAtual->id,
                        'periodo_ativo_descricao' => $periodoAtual->descricao,
                        'periodo_ativo_status' => $periodoAtual->status,
                        'cobrador_id' => $cobrador_id,
                        'total_carteiras_sem_filtro' => count($todasCarteiras),
                        'carteiras_info' => $carteirasInfo
                    ],
                    'rota' => [] // Retorna array vazio mas com informações de debug
                ];
            }

            $rota = [];

            foreach ($carteiras as $carteira) {
                $cliente = $carteira->cliente;
                if (!$cliente) continue;

                // Busca TODAS as vendas do cliente (não apenas a mais recente)
                $vendas = Venda::find()
                    ->where(['cliente_id' => $cliente->id, 'usuario_id' => $usuario_id])
                    ->orderBy(['data_venda' => SORT_DESC])
                    ->all();

                // Agrupa parcelas por venda e ordena corretamente
                $parcelasAgrupadas = [];
                $todasParcelas = [];
                $todasVendas = [];

                foreach ($vendas as $venda) {
                    // Busca todas as parcelas desta venda (pendentes e pagas para contexto)
                    $parcelasVenda = Parcela::find()
                        ->where(['venda_id' => $venda->id])
                        ->orderBy(['numero_parcela' => SORT_ASC])
                        ->all();

                    // Se não houver parcelas, pula esta venda (vendas à vista não devem aparecer)
                    if (count($parcelasVenda) === 0) {
                        continue;
                    }

                    // Busca forma de pagamento da venda
                    $formaPagamento = null;
                    $tipoPagamento = null;
                    if ($venda->forma_pagamento_id) {
                        $formaPagamento = \app\modules\vendas\models\FormaPagamento::findOne($venda->forma_pagamento_id);
                        if ($formaPagamento) {
                            $tipoPagamento = $formaPagamento->tipo;
                        }
                    }

                    // Filtra vendas: inclui apenas vendas parceladas que podem ser cobradas manualmente
                    // Exclui: cartão de crédito, cartão de débito, e vendas sem parcelas (já filtrado acima)
                    // Inclui: BOLETO, DINHEIRO, PIX, e outras formas que permitem cobrança manual (CARNE)
                    if ($tipoPagamento) {
                        $tipoPagamentoUpper = strtoupper($tipoPagamento);
                        // Exclui cartão de crédito e débito (cobrança automática)
                        if (in_array($tipoPagamentoUpper, ['CARTAO_CREDITO', 'CARTAO', 'CARTAO DE CREDITO', 'CARTAO_DEBITO', 'CARTAO DE DEBITO'])) {
                            continue; // Pula vendas no cartão (cobrança automática)
                        }
                        // Inclui: BOLETO, DINHEIRO, PIX, CHEQUE, TRANSFERENCIA, OUTRO (cobrança manual/CARNE)
                    }
                    // Se não tiver forma de pagamento definida, assume que pode ser cobrada (compatibilidade)

                    // Busca itens da venda
                    $itensVenda = [];
                    $itens = VendaItem::find()
                        ->where(['venda_id' => $venda->id])
                        ->with('produto')
                        ->all();
                    
                    foreach ($itens as $item) {
                        $itensVenda[] = [
                            'produto_nome' => $item->produto ? $item->produto->nome : 'Produto',
                            'quantidade' => $item->quantidade,
                            'valor_total' => (float)$item->valor_total_item
                        ];
                    }

                    // Prepara dados da venda
                    $dadosVenda = [
                        'id' => $venda->id,
                        'data_venda' => $venda->data_venda,
                        'valor_total' => (float)$venda->valor_total,
                        'total_parcelas' => count($parcelasVenda),
                        'parcelas_pagas' => count(array_filter($parcelasVenda, function($p) { return $p->status_parcela_codigo === 'PAGA'; })),
                        'forma_pagamento_tipo' => $tipoPagamento, // Adiciona tipo de pagamento
                        'itens' => $itensVenda,
                    ];

                    // Adiciona parcelas com informações da venda
                    foreach ($parcelasVenda as $parcela) {
                        $todasParcelas[] = [
                            'id' => $parcela->id,
                            'venda_id' => $venda->id,
                            'venda_data' => $venda->data_venda,
                            'venda_valor_total' => (float)$venda->valor_total,
                            'numero_parcela' => $parcela->numero_parcela,
                            'total_parcelas_venda' => count($parcelasVenda),
                            'parcelas_pagas_venda' => count(array_filter($parcelasVenda, function($p) { return $p->status_parcela_codigo === 'PAGA'; })),
                            'valor_parcela' => (float)$parcela->valor_parcela,
                            'data_vencimento' => $parcela->data_vencimento,
                            'status_parcela_codigo' => $parcela->status_parcela_codigo,
                            'data_pagamento' => $parcela->data_pagamento,
                            'valor_pago' => $parcela->valor_pago ? (float)$parcela->valor_pago : null,
                        ];
                    }

                    $todasVendas[] = $dadosVenda;
                }

                // Ordena todas as parcelas: primeiro por data de vencimento, depois por número da parcela
                usort($todasParcelas, function($a, $b) {
                    $dataA = strtotime($a['data_vencimento']);
                    $dataB = strtotime($b['data_vencimento']);
                    if ($dataA !== $dataB) {
                        return $dataA - $dataB; // Ordena por data de vencimento
                    }
                    // Se mesma data, ordena por número da parcela
                    return $a['numero_parcela'] - $b['numero_parcela'];
                });

                $rota[] = [
                    'cliente' => [
                        'id' => $cliente->id,
                        'nome' => $cliente->nome_completo,
                        'cpf' => $cliente->cpf,
                        'telefone' => $cliente->telefone,
                        'endereco' => $cliente->endereco_logradouro . ($cliente->endereco_numero ? ', ' . $cliente->endereco_numero : '') . ($cliente->endereco_complemento ? ' - ' . $cliente->endereco_complemento : ''),
                        'bairro' => $cliente->endereco_bairro,
                        'cidade' => $cliente->endereco_cidade,
                        'estado' => $cliente->endereco_estado,
                        'cep' => $cliente->endereco_cep,
                        'ponto_referencia' => $cliente->ponto_referencia,
                    ],
                    'parcelas' => $todasParcelas,
                    'vendas' => $todasVendas, // Todas as vendas do cliente
                ];
            }

            return $rota;

        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            Yii::error("Erro ao buscar rota do dia: " . $e->getMessage(), __METHOD__);
            return ['erro' => 'Erro ao buscar rota do dia: ' . $e->getMessage()];
        }
    }
}

