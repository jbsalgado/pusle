<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\VendaItem;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Orcamento;
use yii\data\ActiveDataProvider;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\NotFoundHttpException;
use Exception;

class PedidoController extends BaseController
{
    public $enableCsrfValidation = false; // Desabilita CSRF para API

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        // Configura autenticação: todas as actions são opcionais (sem autenticação obrigatória)
        // Usa 'except' para garantir que confirmar-recebimento não exija autenticação
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'optional' => ['create', 'index', 'parcelas', 'confirmar-recebimento'],
        ];
        // VerbFilter já é tratado pelo rest\Controller se configurado, mas mantemos local se necessário
        return $behaviors;
    }

    protected function verbs()
    {
        return [
            'index' => ['GET', 'HEAD'],
            'create' => ['POST'],
            'parcelas' => ['GET', 'HEAD'],
            'confirmar-recebimento' => ['POST'],
        ];
    }

    /**
     * Lista os pedidos do cliente
     * GET /api/pedido?cliente_id=XXX
     */
    public function actionIndex()
    {
        $clienteId = Yii::$app->request->get('cliente_id');

        if ($clienteId === null) {
            throw new BadRequestHttpException('Parâmetro cliente_id é obrigatório.');
        }

        // ========================================
        // 🔧 CORREÇÃO: Adicionar fotos dos produtos nos relacionamentos
        // ========================================

        $query = Venda::find()
            ->where(['cliente_id' => $clienteId])
            ->with([
                'itens' => function ($query) {
                    $query->with([
                        'produto' => function ($query) {
                            // Carrega TODAS as fotos do produto (ordenadas)
                            $query->with('fotos');
                        }
                    ]);
                },
                'parcelas',           // Parcelas da venda
                'statusVenda'         // Status da venda
            ])
            ->orderBy(['data_venda' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
                'page' => Yii::$app->request->get('page', 0),
            ],
        ]);

        return $this->success($dataProvider);
    }

    /**
     * Consulta apenas o status de uma venda (Lightweight)
     * GET /api/pedido/status?venda_id=XXX
     */
    public function actionStatus($venda_id)
    {
        if (!$venda_id) {
            throw new BadRequestHttpException('venda_id é obrigatório');
        }

        $venda = Venda::find()->where(['id' => $venda_id])->one();

        if (!$venda) {
            throw new NotFoundHttpException('Venda não encontrada');
        }

        return [
            'sucesso' => true,
            'venda_id' => $venda->id,
            'status' => $venda->status_venda_codigo,
            'pago' => ($venda->status_venda_codigo === 'QUITADA' || $venda->status_venda_codigo === 'PAGO'),
            'data_atualizacao' => $venda->data_atualizacao
        ];
    }

    /**
     * Cria novo pedido
     * POST /api/pedido
     */
    public function actionCreate()
    {
        $rawBody = Yii::$app->request->getRawBody();
        Yii::error('Corpo Cru Recebido (Pedido): ' . $rawBody, 'api');

        $data = json_decode($rawBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Yii::error('Falha ao decodificar JSON (Pedido): ' . json_last_error_msg(), 'api');
            throw new BadRequestHttpException('JSON inválido: ' . json_last_error_msg());
        }

        if (!is_array($data)) {
            Yii::error('json_decode não retornou array. RawBody: ' . $rawBody, 'api');
            throw new BadRequestHttpException('Dados em formato inesperado.');
        }

        Yii::error('Dados Decodificados ($data Pedido): ' . print_r($data, true), 'api');

        // Validação inicial
        $itensVazios = !isset($data['itens']) || empty($data['itens']) || !is_array($data['itens']);

        // VENDA DIRETA: cliente_id pode ser null (opcional)
        // Se não for null, deve ser uma string não vazia
        $clienteIdVazio = false;
        if (isset($data['cliente_id'])) {
            // Se foi enviado, não pode ser string vazia (mas pode ser null)
            if ($data['cliente_id'] === '') {
                $clienteIdVazio = true;
            }
        }
        // Se não foi enviado, também é válido (será null)

        $formaPgtoVazia = !isset($data['forma_pagamento_id']) || empty($data['forma_pagamento_id']);

        Yii::error("Verificação inicial: itens=" . ($itensVazios ? 'VAZIO' : 'OK') .
            ", cliente_id=" . (isset($data['cliente_id']) ? ($data['cliente_id'] === null ? 'NULL (OK)' : ($clienteIdVazio ? 'VAZIO' : 'OK')) : 'NÃO ENVIADO (OK)') .
            ", forma_pgto=" . ($formaPgtoVazia ? 'VAZIO' : 'OK'), 'api');

        if ($itensVazios || $clienteIdVazio || $formaPgtoVazia) {
            Yii::error('Validação inicial falhou.', 'api');
            throw new BadRequestHttpException('Dados incompletos: itens, cliente_id (não pode ser string vazia) ou forma_pagamento_id faltando.');
        }

        $formaPagamentoId = $data['forma_pagamento_id'];
        // Garante que não seja string vazia
        if ($formaPagamentoId === '') {
            $formaPagamentoId = null;
        }
        Yii::error("Forma de Pagamento ID recebida: " . var_export($formaPagamentoId, true), 'api');
        Yii::error("Tipo do Forma de Pagamento ID: " . gettype($formaPagamentoId), 'api');

        // VENDA DIRETA: cliente_id pode ser null
        $clienteId = $data['cliente_id'] ?? null;
        $numeroParcelas = max(1, (int)($data['numero_parcelas'] ?? 1));

        // NOVOS CAMPOS DE ACRÉSCIMO
        $acrescimoValor = isset($data['acrescimo_valor']) ? (float)$data['acrescimo_valor'] : 0.0;
        $acrescimoTipo = isset($data['acrescimo_tipo']) ? $data['acrescimo_tipo'] : null;
        $observacaoAcrescimo = isset($data['observacao_acrescimo']) ? $data['observacao_acrescimo'] : null;

        if ($acrescimoValor < 0) {
            throw new BadRequestHttpException('O valor do acréscimo não pode ser negativo.');
        }

        // === VALIDAÇÃO: DINHEIRO e PIX não permitem parcelamento ===
        if ($numeroParcelas > 1 && $formaPagamentoId) {
            $formaPagamento = \app\modules\vendas\models\FormaPagamento::findOne($formaPagamentoId);
            if ($formaPagamento) {
                $tipo = $formaPagamento->tipo;
                if ($tipo === 'DINHEIRO' || $tipo === 'PIX') {
                    throw new BadRequestHttpException('Forma de pagamento ' . $tipo . ' não permite parcelamento. Apenas pagamento à vista é permitido.');
                }
            }
        }

        // === NOVA VALIDAÇÃO: Data do primeiro pagamento ===
        $dataPrimeiroPagamento = null;
        if ($numeroParcelas > 1) {
            if (empty($data['data_primeiro_pagamento'])) {
                throw new BadRequestHttpException('Data do primeiro pagamento é obrigatória para vendas parceladas.');
            }

            $dataPrimeiroPagamento = $data['data_primeiro_pagamento'];

            // Valida formato da data
            $dataObj = \DateTime::createFromFormat('Y-m-d', $dataPrimeiroPagamento);
            if (!$dataObj || $dataObj->format('Y-m-d') !== $dataPrimeiroPagamento) {
                throw new BadRequestHttpException('Formato de data inválido. Use YYYY-MM-DD.');
            }

            // Valida se a data não é anterior a hoje
            $hoje = new \DateTime();
            $hoje->setTime(0, 0, 0);

            if ($dataObj < $hoje) {
                throw new BadRequestHttpException('A data do primeiro pagamento não pode ser anterior à data de hoje.');
            }

            Yii::info("Data do primeiro pagamento: {$dataPrimeiroPagamento}", 'api');
        }

        // Identificar usuário da loja
        $primeiroProdutoId = $data['itens'][0]['produto_id'] ?? null;
        if (!$primeiroProdutoId) {
            throw new BadRequestHttpException('ID do primeiro produto inválido.');
        }
        $primeiroProduto = Produto::findOne($primeiroProdutoId);
        if (!$primeiroProduto) {
            throw new BadRequestHttpException('Produto não encontrado.');
        }
        $usuarioId = $primeiroProduto->usuario_id;
        if (!$usuarioId) {
            throw new ServerErrorHttpException('Não foi possível identificar o usuário da loja.');
        }

        $transaction = Yii::$app->db->beginTransaction();
        $valorTotalVenda = 0;

        try {

            // ===== LOOP 1: PRÉ-CÁLCULO E VALIDAÇÃO =====
            Yii::error("Iniciando pré-cálculo e validação...", 'api');
            foreach ($data['itens'] as $index => $itemData) {
                if (empty($itemData['produto_id']) || empty($itemData['quantidade']) || !isset($itemData['preco_unitario'])) {
                    throw new Exception("Item #{$index} tem dados incompletos.");
                }
                $produtoId = $itemData['produto_id'];
                $quantidadePedida = (float)$itemData['quantidade'];
                $precoUnitario = (float)$itemData['preco_unitario'];

                // Novos campos de desconto
                $descontoPercentual = isset($itemData['desconto_percentual']) ? (float)$itemData['desconto_percentual'] : 0.0;
                $descontoValor = isset($itemData['desconto_valor']) ? (float)$itemData['desconto_valor'] : 0.0;

                if ($quantidadePedida <= 0) {
                    throw new Exception("Item #{$index}: quantidade deve ser maior que zero.");
                }
                if ($precoUnitario < 0) {
                    throw new Exception("Item #{$index}: preço não pode ser negativo.");
                }

                $produto = Produto::findOne($produtoId);
                if (!$produto || $produto->usuario_id !== $usuarioId) {
                    throw new Exception("Item #{$index}: produto inválido ou não pertence à loja.");
                }

                // Pula validação de estoque se for orçamento
                $isOrcamento = isset($data['is_orcamento']) && $data['is_orcamento'] === true;
                if (!$isOrcamento && !$produto->temEstoque($quantidadePedida)) {
                    throw new Exception("Produto '{$produto->nome}' sem estoque suficiente.");
                }

                // Calcula subtotal do item
                $subtotalItem = $quantidadePedida * $precoUnitario;

                // Calcula o valor do desconto monetário se foi passado percentual
                if ($descontoPercentual > 0 && $descontoValor == 0) {
                    $descontoValor = $subtotalItem * ($descontoPercentual / 100);
                }

                // Valida se desconto não ultrapassa o valor
                if ($descontoValor > $subtotalItem) {
                    throw new Exception("Item #{$index} ({$produto->nome}): Desconto (R$ {$descontoValor}) excede o valor do item (R$ {$subtotalItem}).");
                }

                // Soma ao total da venda (subtraindo desconto)
                $valorTotalVenda += ($subtotalItem - $descontoValor);

                Yii::error("Item #{$index} ({$produto->nome}): Qtd={$quantidadePedida}, Preço={$precoUnitario}, Desc={$descontoValor}. Total parcial={$valorTotalVenda}", 'api');
            }

            Yii::error("Pré-cálculo concluído. Valor Total Itens = {$valorTotalVenda}", 'api');
            if ($valorTotalVenda <= 0 && count($data['itens']) > 0) {
                throw new Exception('Valor total do pedido não pode ser zero.');
            }

            // Soma o acréscimo ao total da venda
            $valorTotalItens = $valorTotalVenda;
            $valorTotalVenda += $acrescimoValor;
            Yii::info("Adicionando acréscimo: R$ {$acrescimoValor}. Novo Total: R$ {$valorTotalVenda}", 'api');

            // ===== CRIAR E SALVAR VENDA =====
            // ✅ NOVO FLUXO: Vendas diretas são criadas com status EM_ABERTO
            // Processamento (estoque, caixa, etc) só acontece após confirmação de recebimento
            // Verifica se há indicação explícita de venda direta no request
            $isVendaDireta = isset($data['is_venda_direta']) && $data['is_venda_direta'] === true;
            $isOrcamento = isset($data['is_orcamento']) && $data['is_orcamento'] === true;

            $venda = new Venda();
            $venda->usuario_id = $usuarioId;
            $venda->cliente_id = $clienteId;
            $venda->data_venda = date('Y-m-d H:i:s');

            if ($isOrcamento) {
                $venda->observacoes = $data['observacoes'] ?? 'Orçamento PWA';
                $venda->status_venda_codigo = \app\modules\vendas\models\StatusVenda::ORCAMENTO;
            } else {
                $venda->observacoes = $data['observacoes'] ?? ($isVendaDireta ? 'Venda Direta' : 'Pedido PWA');
                $venda->status_venda_codigo = \app\modules\vendas\models\StatusVenda::EM_ABERTO;
            }

            $venda->valor_total = $valorTotalVenda;

            // Salva dados do acréscimo
            $venda->acrescimo_valor = $acrescimoValor;
            $venda->acrescimo_tipo = $acrescimoTipo;
            $venda->observacao_acrescimo = $observacaoAcrescimo;

            // Adiciona a forma de pagamento
            $venda->forma_pagamento_id = $formaPagamentoId;
            Yii::info("Forma de Pagamento ID atribuída à venda: " . ($formaPagamentoId ?? 'NULL'), 'api');

            // Adiciona o ID do vendedor, se foi enviado e existir
            $colaboradorId = $data['colaborador_vendedor_id'] ?? null;
            if (!empty($colaboradorId)) {
                // Valida se o colaborador existe antes de atribuir
                $colaborador = \app\modules\vendas\models\Colaborador::findOne($colaboradorId);
                if ($colaborador && $colaborador->usuario_id === $usuarioId) {
                    $venda->colaborador_vendedor_id = $colaboradorId;
                    Yii::info("Colaborador vendedor validado: {$colaboradorId}", 'api');
                } else {
                    Yii::warning("Colaborador vendedor ID {$colaboradorId} não encontrado ou não pertence à loja. Ignorando.", 'api');
                    $venda->colaborador_vendedor_id = null;
                }
            } else {
                $venda->colaborador_vendedor_id = null;
            }

            // === VENDA DIRETA: Data do primeiro vencimento = data da venda ===
            // === VENDA NORMAL: Usa data informada ou calcula ===
            if ($isVendaDireta) {
                // Venda direta: data do primeiro vencimento = data da venda (hoje)
                $venda->data_primeiro_vencimento = date('Y-m-d');
                Yii::info("Venda Direta detectada - data_primeiro_vencimento = data da venda", 'api');
            } elseif ($dataPrimeiroPagamento) {
                // Venda normal: usa data informada
                $venda->data_primeiro_vencimento = $dataPrimeiroPagamento;
            }

            Yii::info("ID Colaborador Vendedor: " . ($venda->colaborador_vendedor_id ?? 'Nenhum'), 'api');
            Yii::info("Data Primeiro Pagamento: " . ($venda->data_primeiro_vencimento ?? 'Não informada'), 'api');
            Yii::info("Tipo de Venda: " . ($isVendaDireta ? 'VENDA DIRETA (EM_ABERTO - aguardando confirmação)' : 'VENDA ONLINE (EM_ABERTO - aguardando pagamento)'), 'api');
            Yii::info("Forma de Pagamento ID: " . ($formaPagamentoId ?? 'Não informada'), 'api');

            Yii::error("Atributos VENDA antes de save(): " . print_r($venda->attributes, true), 'api');

            if (!$venda->save()) {
                $erros = $venda->getFirstErrors();
                Yii::error("❌ FALHA ao salvar Venda: " . print_r($venda->errors, true), 'api');
                throw new Exception('Erro ao salvar venda: ' . implode(', ', $erros));
            }

            // Recarrega a venda do banco para verificar se forma_pagamento_id foi salvo
            $venda->refresh();
            Yii::error("✅ Venda ID {$venda->id} salva com valor R$ {$venda->valor_total}", 'api');
            Yii::error("✅ Venda forma_pagamento_id após save: " . ($venda->forma_pagamento_id ?? 'NULL'), 'api');

            // ===== LOOP 2: CRIAR ITENS =====
            // ✅ CORREÇÃO: Para vendas online, estoque só é baixado após confirmação de pagamento
            Yii::error("Iniciando criação de itens...", 'api');
            foreach ($data['itens'] as $index => $itemData) {
                $produto = Produto::findOne($itemData['produto_id']);

                $item = new VendaItem();
                $item->venda_id = $venda->id;
                $item->produto_id = $produto->id;
                $item->quantidade = (float)$itemData['quantidade'];
                $item->preco_unitario_venda = (float)$itemData['preco_unitario'];

                // Processa descontos
                $descontoPercentual = isset($itemData['desconto_percentual']) ? (float)$itemData['desconto_percentual'] : 0.0;
                $descontoValor = isset($itemData['desconto_valor']) ? (float)$itemData['desconto_valor'] : 0.0;

                $subtotalItem = $item->quantidade * $item->preco_unitario_venda;

                // Se percentual > 0 e valor == 0, calcula valor
                if ($descontoPercentual > 0 && $descontoValor == 0) {
                    $descontoValor = $subtotalItem * ($descontoPercentual / 100);
                }

                // Se valor > 0 e percentual == 0, calcula percentual (informativo)
                if ($descontoValor > 0 && $descontoPercentual == 0 && $subtotalItem > 0) {
                    $descontoPercentual = ($descontoValor / $subtotalItem) * 100;
                }

                $item->desconto_percentual = $descontoPercentual;
                $item->desconto_valor = $descontoValor;

                Yii::error("Tentando salvar item #{$index}: " . print_r($item->attributes, true), 'api');
                if (!$item->save()) {
                    $errosItem = $item->getFirstErrors();
                    Yii::error("❌ FALHA ao salvar VendaItem #{$index}: " . print_r($item->errors, true), 'api');
                    throw new Exception("Erro ao salvar item #{$index}: " . implode(', ', $errosItem));
                }
                Yii::error("✅ Item ID {$item->id} salvo com sucesso", 'api');

                // ✅ NOVO FLUXO: Estoque NÃO é baixado aqui
                // Estoque será baixado apenas após confirmação de recebimento (actionConfirmarRecebimento)
                Yii::info("⏳ Estoque de '{$produto->nome}' será baixado após confirmação de recebimento", 'api');
            }
            Yii::error("Criação de itens concluída.", 'api');

            // ===== GERAR PARCELAS (Pular se for Orçamento) =====
            if (!$isOrcamento) {
                $intervaloDiasParcelas = isset($data['intervalo_dias_parcelas'])
                    ? (int)$data['intervalo_dias_parcelas']
                    : 30;

                Yii::error("Chamando gerarParcelas com: formaPagamentoId={$formaPagamentoId}, isVendaDireta=" . ($isVendaDireta ? 'true' : 'false'), 'api');

                // ✅ NOVO FLUXO: Parcelas são criadas mas NÃO marcadas como pagas
                // Serão marcadas como pagas apenas após confirmação de recebimento
                $venda->gerarParcelas(
                    $formaPagamentoId,
                    $isVendaDireta ? date('Y-m-d') : $dataPrimeiroPagamento,
                    $intervaloDiasParcelas,
                    false // ✅ NÃO marca como paga - será feito na confirmação
                );
                Yii::error("Parcelas geradas para Venda ID {$venda->id} (não marcadas como pagas)", 'api');
            } else {
                Yii::info("Orçamento detectado: pulando geração de parcelas e financeira.", 'api');
            }

            // ✅ NOVO FLUXO: Caixa NÃO é registrado aqui
            // Entrada no caixa será registrada apenas após confirmação de recebimento
            Yii::info("⏳ Entrada no caixa será registrada após confirmação de recebimento", 'api');

            // ===== AUTO-CONFIRMAÇÃO (OPCIONAL) PARA VENDA DIRETA =====
            $confirmarImediato = isset($data['confirmar_imediato']) && $data['confirmar_imediato'] === true;

            // Se for DINHEIRO ou PIX_ESTATICO e venda direta, auto-confirma por padrão
            // Isso evita que vendas de balcão fiquem presas em EM_ABERTO por falha de rede na segunda chamada
            if ($isVendaDireta && !$confirmarImediato && !$isOrcamento) {
                $fp = \app\modules\vendas\models\FormaPagamento::findOne($formaPagamentoId);
                if ($fp && in_array($fp->tipo, [
                    \app\modules\vendas\models\FormaPagamento::TIPO_DINHEIRO,
                    \app\modules\vendas\models\FormaPagamento::TIPO_PIX_ESTATICO
                ])) {
                    $confirmarImediato = true;
                }
            }

            if ($confirmarImediato && !$isOrcamento) {
                Yii::info("🚀 AUTO-CONFIRMANDO Venda ID {$venda->id}", 'api');
                // Usa a lógica centralizada que já trata estoque e parcelas
                if (!$venda->alterarStatus(\app\modules\vendas\models\StatusVenda::QUITADA)) {
                    throw new Exception('Erro ao auto-confirmar venda.');
                }
            }

            // ===== ATUALIZAR ORÇAMENTO (Se houver) =====
            $orcamentoId = $data['orcamento_id'] ?? null;
            if ($orcamentoId) {
                $orcamento = Orcamento::findOne($orcamentoId);
                if ($orcamento && $orcamento->usuario_id === $usuarioId) {
                    $orcamento->status = Orcamento::STATUS_CONVERTIDO;
                    if (!$orcamento->save()) {
                        Yii::error("❌ FALHA ao atualizar Orcamento {$orcamentoId}: " . print_r($orcamento->errors, true), 'api');
                        // Não lançamos exception para não dar rollback na venda, pois a venda é mais importante
                        // mas registramos o erro.
                    } else {
                        Yii::info("✅ Orçamento {$orcamentoId} marcado como CONVERTIDO para Venda {$venda->id}", 'api');
                    }
                }
            }

            // ===== COMMIT =====
            $transaction->commit();
            Yii::error("✅ Transação commitada com sucesso!", 'api');

            // ===== TELEGRAM ALERT (Novo Pedido) =====
            try {
                $msg = \app\components\TelegramHelper::formatVendaAlerta($venda);
                $msg = "🔔 *[PEDIDO REALIZADO]*\n" . $msg;
                \app\components\TelegramHelper::sendMessage($msg);
            } catch (\Exception $e) {
                Yii::error("Erro ao enviar alerta Telegram (Novo Pedido): " . $e->getMessage());
            }

            Yii::$app->response->statusCode = 201;
            $venda->refresh();

            return $this->success($venda->toArray([], ['itens.produto', 'parcelas', 'cliente', 'vendedor']), 'Pedido criado com sucesso');
        } catch (BadRequestHttpException $e) {
            $transaction->rollBack();
            Yii::error("Rollback: BadRequest - " . $e->getMessage(), 'api');
            throw $e;
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::error("Rollback: Exception - " . $e->getMessage(), 'api');
            throw new ServerErrorHttpException('Erro ao processar pedido: ' . $e->getMessage());
        } catch (\Throwable $t) {
            $transaction->rollBack();
            Yii::error("Rollback: Throwable - " . $t->getMessage(), 'api');
            throw new ServerErrorHttpException('Erro crítico ao processar pedido: ' . $t->getMessage());
        }
    }

    /**
     * Busca parcelas de uma venda
     * GET /api/pedido/parcelas?venda_id=XXX
     */
    public function actionParcelas()
    {
        try {
            $vendaId = Yii::$app->request->get('venda_id');

            if (empty($vendaId)) {
                throw new BadRequestHttpException('Parâmetro venda_id é obrigatório.');
            }

            $venda = Venda::findOne($vendaId);
            if (!$venda) {
                throw new BadRequestHttpException('Venda não encontrada.');
            }

            $parcelas = \app\modules\vendas\models\Parcela::find()
                ->where(['venda_id' => $vendaId])
                ->orderBy(['numero_parcela' => SORT_ASC])
                ->asArray()
                ->all();

            return $this->success([
                'venda_id' => $vendaId,
                'numero_parcelas' => $venda->numero_parcelas,
                'valor_total' => $venda->valor_total,
                'parcelas' => $parcelas
            ]);
        } catch (\Exception $e) {
            Yii::error('Erro ao buscar parcelas: ' . $e->getMessage(), 'api');
            throw new ServerErrorHttpException('Erro ao buscar parcelas: ' . $e->getMessage());
        }
    }

    /**
     * Confirma recebimento de venda e emite comprovante
     * POST /api/pedido/confirmar-recebimento
     * Body: {venda_id: "uuid", forma_pagamento_entrega: "uuid" (opcional para PAGAR_AO_ENTREGADOR)}
     */
    public function actionConfirmarRecebimento()
    {
        try {
            $rawBody = Yii::$app->request->getRawBody();
            $data = json_decode($rawBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new BadRequestHttpException('JSON inválido: ' . json_last_error_msg());
            }

            if (empty($data['venda_id'])) {
                throw new BadRequestHttpException('venda_id é obrigatório.');
            }

            $vendaId = $data['venda_id'];
            $venda = Venda::findOne($vendaId);

            if (!$venda) {
                throw new NotFoundHttpException('Venda não encontrada.');
            }

            // Verifica se a venda já está quitada
            if ($venda->status_venda_codigo === \app\modules\vendas\models\StatusVenda::QUITADA) {
                Yii::info("Venda {$vendaId} já está quitada", 'api');
            } else {
                // Se for PAGAR_AO_ENTREGADOR e foi informada forma de pagamento na entrega
                if ($venda->formaPagamento && $venda->formaPagamento->tipo === 'PAGAR_AO_ENTREGADOR') {
                    if (!empty($data['forma_pagamento_entrega'])) {
                        // Atualiza a forma de pagamento para a escolhida na entrega
                        $venda->forma_pagamento_id = $data['forma_pagamento_entrega'];
                        $venda->save(false, ['forma_pagamento_id']);
                    }
                }

                // ✅ Usa a lógica centralizada no model Venda
                if (!$venda->alterarStatus(\app\modules\vendas\models\StatusVenda::QUITADA)) {
                    throw new Exception('Erro ao confirmar recebimento da venda.');
                }

                Yii::info("✅ Venda {$vendaId} confirmada via API", 'api');
            }

            // === TELEGRAM ALERT (Pagamento Confirmado) ===
            try {
                $msg = \app\components\TelegramHelper::formatVendaAlerta($venda);
                $msg = "✅ *[PAGAMENTO CONFIRMADO]*\n" . $msg;
                \app\components\TelegramHelper::sendMessage($msg);
            } catch (\Exception $e) {
                Yii::error("Erro ao enviar alerta Telegram (Confirmação): " . $e->getMessage());
            }

            // === INTEGRAÇÃO FISCAL (OPCIONAL) ===
            // Se solicitado no request, tenta emitir NFCe
            if (isset($data['emitir_fiscal']) && $data['emitir_fiscal'] === true) {
                try {
                    Yii::info("🚀 Iniciando emissão fiscal automática para Venda ID {$venda->id}", 'api');
                    $fiscalService = new \app\components\NFwService();
                    $resultadoFiscal = $fiscalService->emitirCupom($venda->id);

                    if ($resultadoFiscal['success']) {
                        Yii::info("✅ NFCe emitida com sucesso: " . ($resultadoFiscal['cupom_id'] ?? ''), 'api');
                    } else {
                        Yii::error("❌ Falha na emissão fiscal automática: " . ($resultadoFiscal['message'] ?? 'Erro desconhecido'), 'api');
                        // Não lançamos exception para não invalidar a venda já confirmada
                    }
                } catch (\Exception $e) {
                    Yii::error("❌ Erro crítico no serviço fiscal: " . $e->getMessage(), 'api');
                }
            }

            // Recarrega venda com relacionamentos
            $venda->refresh();
            $venda->populateRelation('itens', $venda->itens);
            $venda->populateRelation('cliente', $venda->cliente);
            $venda->populateRelation('parcelas', $venda->parcelas);

            foreach ($venda->itens as $item) {
                $item->populateRelation('produto', $item->produto);
            }

            Yii::$app->response->statusCode = 200;
            return $this->success($venda->toArray([], ['itens.produto', 'parcelas', 'cliente', 'vendedor', 'formaPagamento']), 'Recebimento confirmado');
        } catch (\Exception $e) {
            Yii::error('Erro ao confirmar recebimento: ' . $e->getMessage(), 'api');
            throw new ServerErrorHttpException('Erro ao confirmar recebimento: ' . $e->getMessage());
        }
    }
}
