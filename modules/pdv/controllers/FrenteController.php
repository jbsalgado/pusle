<?php

namespace app\modules\pdv\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\helpers\Url;
use app\modules\pdv\models\PdvConfiguracao;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\ProdutoVariante;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\VendaItem;
use app\modules\vendas\models\FormaPagamento;
use app\modules\vendas\models\StatusVenda;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\CupomFiscal;
use app\modules\caixa\models\Caixa;
use app\modules\caixa\models\CaixaMovimentacao;

/**
 * FrenteController - PDV Frente de Caixa (Leitor de Código de Barras + Balança)
 */
class FrenteController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionIndex()
    {
        $usuario = Yii::$app->user->identity;
        $tenantId = $usuario ? $usuario->getTenantId() : null;

        $caixaAberto = Caixa::find()
            ->where(['usuario_id' => $tenantId, 'status' => Caixa::STATUS_ABERTO])
            ->orderBy(['data_abertura' => SORT_DESC])
            ->one();

        $formasPagamento = FormaPagamento::find()
            ->where(['usuario_id' => $tenantId, 'ativo' => true])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        $pdvConfig = PdvConfiguracao::obterOuPadrao($tenantId);
        $colaborador = Colaborador::getColaboradorLogado();
        $operadorNome = $colaborador ? $colaborador->nome_completo : ($usuario->nome ?? $usuario->username ?? 'Operador');
        $lojaNome = $usuario->nome_loja ?? $usuario->nome ?? 'Pulse PDV';
        $lojaDados = [
            'nome' => $lojaNome,
            'documento' => $usuario->cnpj ?? $usuario->cpf ?? '',
            'endereco' => $usuario->endereco ?? '',
            'telefone' => $usuario->telefone ?? '',
        ];

        return $this->render('index', [
            'tenantId' => $tenantId,
            'lojaNome' => $lojaNome,
            'lojaDados' => $lojaDados,
            'operadorNome' => $operadorNome,
            'caixaAberto' => $caixaAberto,
            'formasPagamento' => $formasPagamento,
            'pdvConfig' => $pdvConfig,
        ]);
    }

    /**
     * Busca rápida por código de barras (EAN), multiplicador (QTD*COD) ou etiqueta de balança (prefixo 2)
     */
    public function actionBuscarCodigo($codigo = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $usuario = Yii::$app->user->identity;
        $tenantId = $usuario ? $usuario->getTenantId() : null;
        $raw = trim((string)$codigo);

        if ($raw === '') {
            return ['success' => false, 'message' => 'Informe ou bipe um código.'];
        }

        // 1. Detecta multiplicador: ex. "3*7891000100103" ou "1,5*1234"
        $multiplicador = 1.0;
        if (strpos($raw, '*') !== false) {
            [$qtdStr, $codResto] = explode('*', $raw, 2);
            $qtdParsed = (float)str_replace(',', '.', trim($qtdStr));
            if ($qtdParsed > 0) {
                $multiplicador = $qtdParsed;
            }
            $raw = trim($codResto);
        }

        $config = PdvConfiguracao::obterOuPadrao($tenantId);
        $prefixo = (string)($config->etiqueta_prefixo ?: '2');
        $tamCodigo = (int)($config->etiqueta_tamanho_codigo ?: 5);

        $produto = null;
        $variante = null;
        $quantidadeFinal = $multiplicador;
        $origemLeitura = 'EAN';

        // 2. Verifica etiqueta de balança (EAN-13 começando com prefixo configurado, ex: "2")
        if (
            strlen($raw) === 13
            && ctype_digit($raw)
            && $prefixo !== ''
            && strpos($raw, $prefixo) === 0
        ) {
            $inicioCod = strlen($prefixo);
            $pluBruto = substr($raw, $inicioCod, $tamCodigo);
            $pluSemZeros = ltrim($pluBruto, '0') ?: '0';
            $valorOuPesoBruto = (int)substr($raw, 7, 5); // 5 dígitos antes do dígito verificador

            $produto = Produto::find()
                ->where(['usuario_id' => $tenantId, 'ativo' => true])
                ->andWhere(['or',
                    ['codigo_balanca' => $pluBruto],
                    ['codigo_balanca' => $pluSemZeros],
                    ['codigo_barras' => $pluBruto],
                    ['codigo_referencia' => $pluBruto],
                ])
                ->one();

            if ($produto) {
                $precoUnit = $this->resolverPrecoProduto($produto);
                if ($config->etiqueta_tipo_valor === PdvConfiguracao::TIPO_VALOR_PRECO && $precoUnit > 0) {
                    $valorEtiqueta = $valorOuPesoBruto / 100;
                    $quantidadeFinal = round(($valorEtiqueta / $precoUnit) * $multiplicador, 3);
                } else {
                    $pesoKg = $valorOuPesoBruto / 1000;
                    $quantidadeFinal = round($pesoKg * $multiplicador, 3);
                }
                if ($quantidadeFinal <= 0) {
                    $quantidadeFinal = 0.001;
                }
                $origemLeitura = 'ETIQUETA_BALANCA';
            }
        }

        // 3. Busca exata por código de barras na Variante
        if (!$produto) {
            $variante = ProdutoVariante::find()
                ->joinWith('produto p')
                ->where(['p.usuario_id' => $tenantId, 'p.ativo' => true])
                ->andWhere(['or',
                    ['prest_produto_variantes.codigo_barras' => $raw],
                    ['prest_produto_variantes.codigo_referencia' => $raw],
                ])
                ->one();

            if ($variante && $variante->produto) {
                $produto = $variante->produto;
            }
        }

        // 4. Busca exata por código de barras, código de balança ou referência no Produto
        if (!$produto) {
            $produto = Produto::find()
                ->where(['usuario_id' => $tenantId, 'ativo' => true])
                ->andWhere(['or',
                    ['codigo_barras' => $raw],
                    ['codigo_balanca' => $raw],
                    ['codigo_referencia' => $raw],
                ])
                ->one();
        }

        if (!$produto) {
            return [
                'success' => false,
                'message' => "Produto não encontrado para o código: {$raw}",
            ];
        }

        $precoUnitario = $this->resolverPrecoProduto($produto, $variante);
        $nomeExibicao = $produto->nome;
        if ($variante) {
            $det = trim(($variante->cor ?? '') . ' ' . ($variante->tamanho ?? ''));
            if ($det !== '') {
                $nomeExibicao .= " ({$det})";
            }
        }

        $foto = $this->resolverFotoProduto($produto);

        return [
            'success' => true,
            'origem' => $origemLeitura,
            'item' => [
                'produto_id' => (string)$produto->id,
                'variante_id' => $variante ? (string)$variante->id : null,
                'codigo' => $variante && $variante->codigo_barras ? $variante->codigo_barras : ($produto->codigo_barras ?: ($produto->codigo_balanca ?: $raw)),
                'nome' => $nomeExibicao,
                'unidade' => $produto->unidade_medida ?: 'UN',
                'preco_unitario' => $precoUnitario,
                'quantidade' => $quantidadeFinal,
                'subtotal' => round($precoUnitario * $quantidadeFinal, 2),
                'balanca_pesavel' => (bool)$produto->balanca_pesavel,
                'venda_fracionada' => (bool)$produto->venda_fracionada,
                'tara_padrao' => (float)($produto->tara_padrao ?? 0),
                'estoque_atual' => (float)($variante ? $variante->estoque_atual : $produto->estoque_atual),
                'foto_url' => $foto,
            ],
        ];
    }

    /**
     * Autocomplete rápido de produtos por nome/código (atalho F2)
     */
    public function actionBuscarProdutos($q = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $usuario = Yii::$app->user->identity;
        $tenantId = $usuario ? $usuario->getTenantId() : null;
        $termo = trim((string)$q);

        $query = Produto::find()
            ->where(['usuario_id' => $tenantId, 'ativo' => true]);

        if ($termo !== '') {
            $query->andWhere(['or',
                ['ilike', 'nome', $termo],
                ['ilike', 'codigo_barras', $termo],
                ['ilike', 'codigo_balanca', $termo],
                ['ilike', 'codigo_referencia', $termo],
            ]);
        }

        $produtos = $query->orderBy(['nome' => SORT_ASC])->limit(30)->all();
        $lista = [];
        foreach ($produtos as $p) {
            $lista[] = [
                'produto_id' => (string)$p->id,
                'variante_id' => null,
                'codigo' => $p->codigo_barras ?: ($p->codigo_balanca ?: ($p->codigo_referencia ?: '')),
                'nome' => $p->nome,
                'unidade' => $p->unidade_medida ?: 'UN',
                'preco_unitario' => $this->resolverPrecoProduto($p),
                'balanca_pesavel' => (bool)$p->balanca_pesavel,
                'venda_fracionada' => (bool)$p->venda_fracionada,
                'tara_padrao' => (float)($p->tara_padrao ?? 0),
                'estoque_atual' => (float)$p->estoque_atual,
                'foto_url' => $this->resolverFotoProduto($p),
            ];
        }

        return ['success' => true, 'produtos' => $lista];
    }

    /**
     * Retorna catálogo leve para cache offline do PDV
     */
    public function actionCatalogoOffline()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $usuario = Yii::$app->user->identity;
        $tenantId = $usuario ? $usuario->getTenantId() : null;

        $produtos = Produto::find()
            ->where(['usuario_id' => $tenantId, 'ativo' => true])
            ->orderBy(['nome' => SORT_ASC])
            ->limit(2000)
            ->all();

        $itens = [];
        foreach ($produtos as $p) {
            $itens[] = [
                'produto_id' => (string)$p->id,
                'codigo_barras' => $p->codigo_barras ?: '',
                'codigo_balanca' => $p->codigo_balanca ?: '',
                'codigo_referencia' => $p->codigo_referencia ?: '',
                'nome' => $p->nome,
                'unidade' => $p->unidade_medida ?: 'UN',
                'preco_unitario' => $this->resolverPrecoProduto($p),
                'balanca_pesavel' => (bool)$p->balanca_pesavel,
                'venda_fracionada' => (bool)$p->venda_fracionada,
                'tara_padrao' => (float)($p->tara_padrao ?? 0),
            ];
        }

        return ['success' => true, 'total' => count($itens), 'catalogo' => $itens];
    }

    /**
     * Abertura rápida de caixa pelo próprio PDV
     */
    public function actionAbrirCaixa()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $usuario = Yii::$app->user->identity;
        $tenantId = $usuario ? $usuario->getTenantId() : null;
        $payload = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();

        $existente = Caixa::find()
            ->where(['usuario_id' => $tenantId, 'status' => Caixa::STATUS_ABERTO])
            ->one();

        if ($existente) {
            return ['success' => true, 'caixa_id' => (string)$existente->id, 'message' => 'Caixa já está aberto.'];
        }

        $colaborador = Colaborador::getColaboradorLogado();
        $caixa = new Caixa();
        $caixa->usuario_id = $tenantId;
        $caixa->colaborador_id = $colaborador ? $colaborador->id : null;
        $caixa->valor_inicial = max(0, (float)($payload['valor_inicial'] ?? 0));
        $caixa->data_abertura = date('Y-m-d H:i:s');
        $caixa->status = Caixa::STATUS_ABERTO;
        $caixa->observacoes = trim((string)($payload['observacoes'] ?? 'Abertura via PDV Frente de Caixa'));

        if ($caixa->save()) {
            return ['success' => true, 'caixa_id' => (string)$caixa->id, 'message' => 'Caixa aberto com sucesso!'];
        }

        return ['success' => false, 'message' => implode(', ', $caixa->getFirstErrors())];
    }

    /**
     * Sangria ou Suprimento rápido no caixa aberto (atalho F9)
     */
    public function actionMovimentacaoCaixa()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $usuario = Yii::$app->user->identity;
        $tenantId = $usuario ? $usuario->getTenantId() : null;
        $payload = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();

        $caixa = Caixa::find()
            ->where(['usuario_id' => $tenantId, 'status' => Caixa::STATUS_ABERTO])
            ->orderBy(['data_abertura' => SORT_DESC])
            ->one();

        if (!$caixa) {
            return ['success' => false, 'message' => 'Nenhum caixa aberto no momento.'];
        }

        $tipo = ($payload['tipo'] ?? '') === 'SANGRIA' ? CaixaMovimentacao::TIPO_SAIDA : CaixaMovimentacao::TIPO_ENTRADA;
        $categoria = ($payload['tipo'] ?? '') === 'SANGRIA' ? CaixaMovimentacao::CATEGORIA_SANGRIA : CaixaMovimentacao::CATEGORIA_SUPRIMENTO;
        $valor = (float)($payload['valor'] ?? 0);
        $descricao = trim((string)($payload['descricao'] ?? ''));

        if ($valor <= 0) {
            return ['success' => false, 'message' => 'Informe um valor maior que zero.'];
        }

        $mov = new CaixaMovimentacao();
        $mov->caixa_id = $caixa->id;
        $mov->tipo = $tipo;
        $mov->categoria = $categoria;
        $mov->valor = $valor;
        $mov->descricao = $descricao ?: ($categoria === CaixaMovimentacao::CATEGORIA_SANGRIA ? 'Sangria no PDV' : 'Suprimento no PDV');
        $mov->data_movimento = date('Y-m-d H:i:s');

        if ($mov->save()) {
            return ['success' => true, 'message' => 'Movimentação registrada no caixa!'];
        }

        return ['success' => false, 'message' => implode(', ', $mov->getFirstErrors())];
    }

    /**
     * Salva configurações de etiqueta de balança e protocolo serial
     */
    public function actionSalvarConfig()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $usuario = Yii::$app->user->identity;
        $tenantId = $usuario ? $usuario->getTenantId() : null;
        $payload = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();

        $config = PdvConfiguracao::findOne(['usuario_id' => $tenantId]);
        if (!$config) {
            $config = new PdvConfiguracao();
            $config->usuario_id = $tenantId;
        }

        $config->etiqueta_prefixo = trim((string)($payload['etiqueta_prefixo'] ?? '2'));
        $config->etiqueta_tamanho_codigo = (int)($payload['etiqueta_tamanho_codigo'] ?? 5);
        $config->etiqueta_tipo_valor = ($payload['etiqueta_tipo_valor'] ?? 'PESO') === 'PRECO' ? 'PRECO' : 'PESO';
        $config->balanca_protocolo = in_array($payload['balanca_protocolo'] ?? '', ['TOLEDO', 'FILIZOLA', 'URANO', 'ELGIN']) ? $payload['balanca_protocolo'] : 'TOLEDO';
        $config->balanca_baud_rate = (int)($payload['balanca_baud_rate'] ?? 9600);
        $config->som_leitor_ativo = !empty($payload['som_leitor_ativo']);
        $config->imprimir_cupom_auto = !empty($payload['imprimir_cupom_auto']);
        $config->data_atualizacao = date('Y-m-d H:i:s');

        if ($config->save()) {
            return ['success' => true, 'message' => 'Configurações do PDV salvas!', 'config' => $config->toArray()];
        }

        return ['success' => false, 'message' => implode(', ', $config->getFirstErrors())];
    }

    /**
     * Finaliza a venda do PDV, dá baixa no estoque e registra no Caixa Aberto
     */
    public function actionFinalizarVenda()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $usuario = Yii::$app->user->identity;
        $tenantId = $usuario ? $usuario->getTenantId() : null;
        $payload = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();

        try {
            return $this->registrarVendaTransacional($tenantId, $payload);
        } catch (\Throwable $e) {
            Yii::error('Erro ao finalizar venda PDV: ' . $e->getMessage(), __METHOD__);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Sincroniza vendas realizadas no modo offline do PDV com idempotência por offline_id
     */
    public function actionSincronizarOffline()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $usuario = Yii::$app->user->identity;
        $tenantId = $usuario ? $usuario->getTenantId() : null;
        $payload = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $vendasOffline = $payload['vendas_offline'] ?? [];

        if (empty($vendasOffline)) {
            return ['success' => true, 'total_sincronizados' => 0, 'sincronizados' => [], 'erros' => []];
        }

        $sincronizados = [];
        $erros = [];

        foreach ($vendasOffline as $vendaPayload) {
            $offlineId = trim((string)($vendaPayload['offline_id'] ?? uniqid('pdv_off_')));
            try {
                // Idempotência: evita duplicar se esse offline_id já foi sincronizado
                $jaExiste = Venda::find()
                    ->where(['usuario_id' => $tenantId])
                    ->andWhere(['like', 'observacoes', "[OFFLINE:{$offlineId}]"])
                    ->one();

                if ($jaExiste) {
                    $sincronizados[] = [
                        'offline_id' => $offlineId,
                        'venda_id' => (string)$jaExiste->id,
                        'ja_registrada' => true,
                    ];
                    continue;
                }

                $res = $this->registrarVendaTransacional($tenantId, $vendaPayload, $offlineId);
                $sincronizados[] = [
                    'offline_id' => $offlineId,
                    'venda_id' => $res['venda_id'],
                    'numero_cupom' => $res['numero_cupom'],
                ];
            } catch (\Throwable $e) {
                $erros[] = [
                    'offline_id' => $offlineId,
                    'erro' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => (count($sincronizados) > 0 || empty($erros)),
            'total_sincronizados' => count($sincronizados),
            'sincronizados' => $sincronizados,
            'erros' => $erros,
        ];
    }

    /**
     * Núcleo transacional de registro de venda do PDV (online ou sincronização offline)
     */
    private function registrarVendaTransacional($tenantId, array $payload, ?string $offlineId = null): array
    {
        $itensReq = $payload['itens'] ?? [];
        $pagamentosReq = $payload['pagamentos'] ?? [];
        $descontoGeral = max(0, (float)($payload['desconto'] ?? 0));
        $cpfConsumidor = preg_replace('/\D/', '', (string)($payload['cpf_consumidor'] ?? ''));
        $dataVenda = !empty($payload['data_venda']) ? $payload['data_venda'] : date('Y-m-d H:i:s');

        if (empty($itensReq)) {
            throw new \Exception('O cupom está vazio. Bipe ao menos um produto.');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $subtotalBruto = 0;
            $itensValidados = [];

            foreach ($itensReq as $row) {
                $produto = Produto::findOne(['id' => $row['produto_id'] ?? null, 'usuario_id' => $tenantId]);
                if (!$produto) {
                    throw new \Exception('Produto inválido ou não encontrado no cupom.');
                }
                $variante = !empty($row['variante_id'])
                    ? ProdutoVariante::findOne(['id' => $row['variante_id'], 'produto_id' => $produto->id])
                    : null;

                $qtd = round((float)($row['quantidade'] ?? 0), 3);
                if ($qtd <= 0) {
                    throw new \Exception("Quantidade inválida para {$produto->nome}.");
                }

                $precoUnit = (float)($row['preco_unitario'] ?? $this->resolverPrecoProduto($produto, $variante));
                $sub = round($qtd * $precoUnit, 2);
                $subtotalBruto += $sub;

                $itensValidados[] = [
                    'produto' => $produto,
                    'variante' => $variante,
                    'quantidade' => $qtd,
                    'preco_unitario' => $precoUnit,
                    'subtotal' => $sub,
                ];
            }

            $valorTotalFinal = max(0.01, round($subtotalBruto - $descontoGeral, 2));

            $formaPrincipalId = !empty($pagamentosReq[0]['forma_pagamento_id']) ? $pagamentosReq[0]['forma_pagamento_id'] : null;
            if (!$formaPrincipalId) {
                $fpPadrao = FormaPagamento::find()->where(['usuario_id' => $tenantId, 'ativo' => true])->one();
                $formaPrincipalId = $fpPadrao ? $fpPadrao->id : null;
            }

            $colaborador = Colaborador::getColaboradorLogado();

            $venda = new Venda();
            $venda->usuario_id = $tenantId;
            $venda->colaborador_vendedor_id = $colaborador ? $colaborador->id : null;
            $venda->cpf_consumidor = (strlen($cpfConsumidor) === 11) ? $cpfConsumidor : null;
            $venda->data_venda = $dataVenda;
            $venda->valor_total = $valorTotalFinal;
            $venda->numero_parcelas = 1;
            $venda->tipo_venda = Venda::TIPO_BALCAO;
            $venda->status_venda_codigo = StatusVenda::QUITADA;
            $venda->forma_pagamento_id = $formaPrincipalId;

            $obsPag = [];
            foreach ($pagamentosReq as $pg) {
                $obsPag[] = ($pg['nome'] ?? 'Pagamento') . ' R$ ' . number_format((float)($pg['valor'] ?? 0), 2, ',', '.');
            }
            $tagOffline = $offlineId ? " [OFFLINE:{$offlineId}]" : '';
            $venda->observacoes = 'PDV Frente de Caixa' . $tagOffline . (!empty($obsPag) ? ' | ' . implode(' + ', $obsPag) : '');

            if (!$venda->save()) {
                throw new \Exception('Erro ao salvar venda: ' . implode(', ', $venda->getFirstErrors()));
            }

            $totalItens = count($itensValidados);
            $descDist = 0;

            foreach ($itensValidados as $idx => $itemData) {
                $p = $itemData['produto'];
                $var = $itemData['variante'];

                $vItem = new VendaItem();
                $vItem->venda_id = $venda->id;
                $vItem->produto_id = $p->id;
                $vItem->variante_id = $var ? $var->id : null;
                $vItem->quantidade = $itemData['quantidade'];
                $vItem->preco_unitario_venda = $itemData['preco_unitario'];

                $itemDesc = 0;
                if ($descontoGeral > 0 && $subtotalBruto > 0) {
                    if ($idx === $totalItens - 1) {
                        $itemDesc = round($descontoGeral - $descDist, 2);
                    } else {
                        $itemDesc = round(($itemData['subtotal'] / $subtotalBruto) * $descontoGeral, 2);
                        $descDist += $itemDesc;
                    }
                }
                $vItem->desconto_valor = max(0, $itemDesc);
                $vItem->desconto_percentual = $itemData['subtotal'] > 0 ? round(($itemDesc / $itemData['subtotal']) * 100, 2) : 0;
                $vItem->valor_total_item = max(0, $itemData['subtotal'] - $vItem->desconto_valor);

                if (!$vItem->save()) {
                    throw new \Exception('Erro ao salvar item: ' . implode(', ', $vItem->getFirstErrors()));
                }

                if ($var) {
                    $saldoAnt = (float)($var->estoque_atual ?? 0);
                    $var->baixarEstoque($itemData['quantidade']);
                    $var->refresh();
                    $saldoNovo = (float)($var->estoque_atual ?? 0);
                } else {
                    $saldoAnt = (float)($p->estoque_atual ?? 0);
                    $p->baixarEstoque($itemData['quantidade']);
                    $p->refresh();
                    $saldoNovo = (float)($p->estoque_atual ?? 0);
                }

                try {
                    Yii::$app->db->createCommand()->insert('prest_estoque_movimentacoes', [
                        'id' => Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar(),
                        'produto_id' => $p->id,
                        'variante_id' => $var ? $var->id : null,
                        'usuario_id' => $tenantId,
                        'tipo_movimentacao' => 'SAIDA',
                        'quantidade' => $itemData['quantidade'],
                        'saldo_anterior' => $saldoAnt,
                        'saldo_novo' => $saldoNovo,
                        'venda_id' => $venda->id,
                        'observacao' => 'Saída PDV #' . strtoupper(substr($venda->id, 0, 8)),
                        'data_movimentacao' => $dataVenda,
                    ])->execute();
                } catch (\Throwable $eMov) {
                    Yii::warning('Falha ao registrar movimentação estoque PDV: ' . $eMov->getMessage(), __METHOD__);
                }
            }

            $venda->gerarParcelas($formaPrincipalId, date('Y-m-d', strtotime($dataVenda)), 30, true, $pagamentosReq);

            $caixaAberto = Caixa::find()
                ->where(['usuario_id' => $tenantId, 'status' => Caixa::STATUS_ABERTO])
                ->orderBy(['data_abertura' => SORT_DESC])
                ->one();

            if ($caixaAberto) {
                $mov = new CaixaMovimentacao();
                $mov->caixa_id = $caixaAberto->id;
                $mov->venda_id = $venda->id;
                $mov->forma_pagamento_id = $formaPrincipalId;
                $mov->tipo = CaixaMovimentacao::TIPO_ENTRADA;
                $mov->categoria = CaixaMovimentacao::CATEGORIA_VENDA;
                $mov->valor = $valorTotalFinal;
                $mov->descricao = 'Venda PDV #' . strtoupper(substr($venda->id, 0, 8));
                $mov->data_movimento = $dataVenda;
                $mov->save(false);
            }

            $transaction->commit();

            return [
                'success' => true,
                'venda_id' => (string)$venda->id,
                'numero_cupom' => strtoupper(substr($venda->id, 0, 8)),
                'valor_total' => $valorTotalFinal,
                'desconto' => $descontoGeral,
                'data_hora' => date('d/m/Y H:i:s', strtotime($dataVenda)),
                'message' => 'Venda finalizada com sucesso!',
            ];
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Emite ou recupera Cupom Fiscal (NFC-e) para uma venda finalizada no PDV
     */
    public function actionEmitirNfce()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $usuario = Yii::$app->user->identity;
        $tenantId = $usuario ? $usuario->getTenantId() : null;
        $payload = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $vendaId = $payload['venda_id'] ?? Yii::$app->request->get('venda_id');

        if (!$vendaId) {
            return ['success' => false, 'message' => 'Venda não informada para emissão fiscal.'];
        }

        $venda = Venda::findOne(['id' => $vendaId, 'usuario_id' => $tenantId]);
        if (!$venda) {
            return ['success' => false, 'message' => 'Venda não encontrada para esta loja.'];
        }

        $cupomExistente = CupomFiscal::findOne(['venda_id' => $venda->id, 'status' => CupomFiscal::STATUS_AUTORIZADA]);
        if ($cupomExistente) {
            return [
                'success' => true,
                'cupom_id' => (string)$cupomExistente->id,
                'chave_acesso' => $cupomExistente->chave_acesso,
                'pdf_url' => Url::to(['/vendas/cupom-fiscal/pdf', 'id' => $cupomExistente->id]),
                'message' => 'NFC-e já autorizada!',
            ];
        }

        if (!Yii::$app->has('nfwService')) {
            return ['success' => false, 'message' => 'Serviço fiscal (nfwService) não configurado neste ambiente.'];
        }

        try {
            $resultado = Yii::$app->nfwService->emitirCupom($venda->id);
            if (!empty($resultado['success'])) {
                $cupom = CupomFiscal::find()
                    ->where(['venda_id' => $venda->id])
                    ->orderBy(['id' => SORT_DESC])
                    ->one();

                return [
                    'success' => true,
                    'cupom_id' => $cupom ? (string)$cupom->id : null,
                    'chave_acesso' => $cupom ? $cupom->chave_acesso : ($resultado['chave'] ?? null),
                    'pdf_url' => $cupom ? Url::to(['/vendas/cupom-fiscal/pdf', 'id' => $cupom->id]) : null,
                    'message' => 'Cupom Fiscal (NFC-e) emitido com sucesso!',
                ];
            }

            return [
                'success' => false,
                'message' => $resultado['message'] ?? 'Não foi possível autorizar a NFC-e.',
            ];
        } catch (\Throwable $e) {
            Yii::error('Erro ao emitir NFC-e no PDV: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'message' => 'Erro na emissão fiscal: ' . $e->getMessage(),
            ];
        }
    }

    private function resolverPrecoProduto(Produto $produto, ?ProdutoVariante $variante = null): float
    {
        if ($variante && isset($variante->preco_venda) && (float)$variante->preco_venda > 0) {
            return (float)$variante->preco_venda;
        }
        $hoje = date('Y-m-d');
        if (
            (float)$produto->preco_promocional > 0
            && (!$produto->data_inicio_promocao || $produto->data_inicio_promocao <= $hoje)
            && (!$produto->data_fim_promocao || $produto->data_fim_promocao >= $hoje)
        ) {
            return (float)$produto->preco_promocional;
        }
        return (float)$produto->preco_venda_sugerido;
    }

    private function resolverFotoProduto(Produto $produto): ?string
    {
        if (!empty($produto->foto_principal)) {
            return $produto->foto_principal;
        }
        return null;
    }
}

