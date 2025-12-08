<?php
namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\VendaItem;
use app\modules\vendas\models\Produto;
use yii\data\ActiveDataProvider;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use Exception;

class PedidoController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator'] = [
            'class' => \yii\filters\ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
                'text/html' => Response::FORMAT_JSON,
            ],
        ];
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'optional' => ['index', 'create', 'parcelas'],
        ];
        return $behaviors;
    }

    protected function verbs()
    {
        return [
            'index' => ['GET', 'HEAD'],
            'create' => ['POST'],
            'parcelas' => ['GET', 'HEAD'],
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
                'itens' => function($query) {
                    $query->with([
                        'produto' => function($query) {
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
        
        // Retornar apenas os models (sem metadados de paginação)
        return $dataProvider->getModels();
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
            Yii::error('json_decode não retornou array. RawBody: '. $rawBody, 'api');
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
                 $quantidadePedida = (int)$itemData['quantidade']; 
                 $precoUnitario = (float)$itemData['preco_unitario'];
                 
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
                 if (!$produto->temEstoque($quantidadePedida)) { 
                     throw new Exception("Produto '{$produto->nome}' sem estoque suficiente."); 
                 }
                 
                 $valorTotalVenda += $quantidadePedida * $precoUnitario;
                 Yii::error("Item #{$index} ({$produto->nome}): Qtd={$quantidadePedida}, Preço={$precoUnitario}. Total parcial={$valorTotalVenda}", 'api');
            }
            
            Yii::error("Pré-cálculo concluído. Valor Total = {$valorTotalVenda}", 'api');
            if ($valorTotalVenda <= 0 && count($data['itens']) > 0) { 
                throw new Exception('Valor total do pedido não pode ser zero.'); 
            }

            // ===== CRIAR E SALVAR VENDA =====
            // VENDA DIRETA: Detecta se é venda direta (cliente_id null)
            $isVendaDireta = ($clienteId === null);
            
            $venda = new Venda();
            $venda->usuario_id = $usuarioId;
            $venda->cliente_id = $clienteId;
            $venda->data_venda = date('Y-m-d H:i:s');
            $venda->observacoes = $data['observacoes'] ?? ($isVendaDireta ? 'Venda Direta' : 'Pedido PWA');
            $venda->numero_parcelas = $numeroParcelas;
            
            // VENDA DIRETA: Status QUITADA (pagamento na hora)
            // VENDA NORMAL: Status EM_ABERTO (aguardando pagamento)
            $venda->status_venda_codigo = $isVendaDireta 
                ? \app\modules\vendas\models\StatusVenda::QUITADA 
                : \app\modules\vendas\models\StatusVenda::EM_ABERTO;
            
            $venda->valor_total = $valorTotalVenda;
            
            // Adiciona a forma de pagamento
            $venda->forma_pagamento_id = $formaPagamentoId;
            Yii::info("Forma de Pagamento ID atribuída à venda: " . ($formaPagamentoId ?? 'NULL'), 'api');

            // Adiciona o ID do vendedor, se foi enviado
            $colaboradorId = $data['colaborador_vendedor_id'] ?? null;
            if (!empty($colaboradorId)) {
                $venda->colaborador_vendedor_id = $colaboradorId;
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
            Yii::info("Tipo de Venda: " . ($isVendaDireta ? 'VENDA DIRETA (QUITADA)' : 'VENDA NORMAL (EM_ABERTO)'), 'api');
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

            // ===== LOOP 2: CRIAR ITENS E ATUALIZAR ESTOQUE =====
            Yii::error("Iniciando criação de itens...", 'api');
            foreach ($data['itens'] as $index => $itemData) {
                $produto = Produto::findOne($itemData['produto_id']);
                $item = new VendaItem();
                $item->venda_id = $venda->id;
                $item->produto_id = $produto->id;
                $item->quantidade = (int)$itemData['quantidade'];
                $item->preco_unitario_venda = (float)$itemData['preco_unitario'];

                Yii::error("Tentando salvar item #{$index}: " . print_r($item->attributes, true), 'api');
                if (!$item->save()) {
                    $errosItem = $item->getFirstErrors();
                    Yii::error("❌ FALHA ao salvar VendaItem #{$index}: " . print_r($item->errors, true), 'api');
                    throw new Exception("Erro ao salvar item #{$index}: " . implode(', ', $errosItem));
                }
                Yii::error("✅ Item ID {$item->id} salvo com sucesso", 'api');

                // Atualizar estoque
                $produto->estoque_atual -= $item->quantidade;
                if (!$produto->save(false, ['estoque_atual'])) {
                    Yii::error("❌ FALHA ao atualizar estoque do produto {$produto->id}", 'api');
                    throw new Exception("Erro ao atualizar estoque do produto '{$produto->nome}'.");
                }
                Yii::error("✅ Estoque de '{$produto->nome}' atualizado para {$produto->estoque_atual}", 'api');
            }
            Yii::error("Criação de itens concluída.", 'api');

            // ===== GERAR PARCELAS =====
            $intervaloDiasParcelas = isset($data['intervalo_dias_parcelas']) 
                ? (int)$data['intervalo_dias_parcelas'] 
                : 30;
            
            Yii::error("Chamando gerarParcelas com: formaPagamentoId={$formaPagamentoId}, isVendaDireta=" . ($isVendaDireta ? 'true' : 'false'), 'api');
            
            $venda->gerarParcelas(
                $formaPagamentoId, 
                $isVendaDireta ? date('Y-m-d') : $dataPrimeiroPagamento, 
                $intervaloDiasParcelas,
                $isVendaDireta // Indica se é venda direta (para marcar como PAGA)
            );
            Yii::error("Parcelas geradas para Venda ID {$venda->id}", 'api');

            // ===== INTEGRAÇÃO COM CAIXA (VENDA DIRETA) =====
            // Registra entrada no caixa apenas para vendas diretas (pagamento na hora)
            if ($isVendaDireta) {
                try {
                    $movimentacao = \app\modules\caixa\helpers\CaixaHelper::registrarEntradaVenda(
                        $venda->id,
                        $venda->valor_total,
                        $venda->forma_pagamento_id,
                        $venda->usuario_id
                    );
                    
                    if ($movimentacao) {
                        Yii::info("✅ Entrada registrada no caixa para Venda ID {$venda->id}", 'api');
                    } else {
                        // Não falha a venda se não houver caixa aberto, apenas registra no log
                        Yii::warning("⚠️ Não foi possível registrar entrada no caixa para Venda ID {$venda->id} (caixa pode não estar aberto)", 'api');
                    }
                } catch (\Exception $e) {
                    // Não falha a venda se houver erro no caixa, apenas registra no log
                    Yii::error("Erro ao registrar entrada no caixa (não crítico): " . $e->getMessage(), 'api');
                }
            }

            // ===== COMMIT =====
            $transaction->commit();
            Yii::error("✅ Transação commitada com sucesso!", 'api');

            Yii::$app->response->statusCode = 201;
            $venda->refresh();
            
            return $venda->toArray([], ['itens.produto', 'parcelas', 'cliente', 'vendedor']);

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
            
            return [
                'venda_id' => $vendaId,
                'numero_parcelas' => $venda->numero_parcelas,
                'valor_total' => $venda->valor_total,
                'parcelas' => $parcelas
            ];
            
        } catch (\Exception $e) {
            Yii::error('Erro ao buscar parcelas: ' . $e->getMessage(), 'api');
            throw new ServerErrorHttpException('Erro ao buscar parcelas: ' . $e->getMessage());
        }
    }
}