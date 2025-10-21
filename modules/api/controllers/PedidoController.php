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
                'text/html' => Response::FORMAT_JSON, // Aceita HTML mas responde JSON
            ],
        ];
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'optional' => ['index', 'create'], // Permite criar sem token, ajuste se necessário
        ];
        // Desabilitar CSRF para a API
        $behaviors['csrf'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'create' => ['POST'],
                 // Adicione outros métodos se necessário
            ],
           // 'csrfParam' => null, // Não esperar por _csrf
        ];
        return $behaviors;
    }

    protected function verbs()
    {
        return [
            'index' => ['GET', 'HEAD'],
            'create' => ['POST'],
        ];
    }

    public function actionIndex()
    {
        $clienteId = Yii::$app->request->get('cliente_id');
        
        if ($clienteId === null) {
            throw new BadRequestHttpException('Parâmetro cliente_id é obrigatório.');
        }
        
        $query = Venda::find()
            ->where(['cliente_id' => $clienteId])
            ->with(['itens.produto']) // Carrega itens e produtos relacionados
            ->orderBy(['data_venda' => SORT_DESC]);
        
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 10], // Ajuste o tamanho da página
            'serializer' => [
                'class' => 'yii\rest\Serializer',
                'collectionEnvelope' => 'items' // Formato comum para listas em APIs
            ],
        ]);
    }

    /**
     * Cria novo pedido
     * POST /api/pedido
     */
    public function actionCreate()
    {
        // Ler e decodificar JSON
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
        $clienteIdVazio = !isset($data['cliente_id']) || empty($data['cliente_id']);
        $formaPgtoVazia = !isset($data['forma_pagamento_id']) || empty($data['forma_pagamento_id']);
        
        Yii::error("Verificação inicial: itens=" . ($itensVazios ? 'VAZIO' : 'OK') . 
                   ", cliente_id=" . ($clienteIdVazio ? 'VAZIO' : 'OK') . 
                   ", forma_pgto=" . ($formaPgtoVazia ? 'VAZIO' : 'OK'), 'api');

        if ($itensVazios || $clienteIdVazio || $formaPgtoVazia) {
            Yii::error('Validação inicial falhou.', 'api');
            throw new BadRequestHttpException('Dados incompletos: itens, cliente_id ou forma_pagamento_id faltando.');
        }

        $formaPagamentoId = $data['forma_pagamento_id'];
        $clienteId = $data['cliente_id'];

        // Identificar usuário da loja (importante!)
        // Assume-se que todos os produtos pertencem ao mesmo usuário
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
                // ... (validação do item, busca produto, verifica estoque) ...
                 if (empty($itemData['produto_id']) || empty($itemData['quantidade']) || !isset($itemData['preco_unitario'])) { throw new Exception("Item #{$index} tem dados incompletos."); }
                 $produtoId = $itemData['produto_id']; $quantidadePedida = (int)$itemData['quantidade']; $precoUnitario = (float)$itemData['preco_unitario'];
                 if ($quantidadePedida <= 0) { throw new Exception("Item #{$index}: quantidade deve ser maior que zero."); }
                 if ($precoUnitario < 0) { throw new Exception("Item #{$index}: preço não pode ser negativo."); }
                 $produto = Produto::findOne($produtoId);
                 if (!$produto || $produto->usuario_id !== $usuarioId) { throw new Exception("Item #{$index}: produto inválido ou não pertence à loja."); }
                 if (!$produto->temEstoque($quantidadePedida)) { throw new Exception("Produto '{$produto->nome}' sem estoque suficiente."); }
                 $valorTotalVenda += $quantidadePedida * $precoUnitario;
                 Yii::error("Item #{$index} ({$produto->nome}): Qtd={$quantidadePedida}, Preço={$precoUnitario}. Total parcial={$valorTotalVenda}", 'api');
            }
            Yii::error("Pré-cálculo concluído. Valor Total = {$valorTotalVenda}", 'api');
            if ($valorTotalVenda <= 0 && count($data['itens']) > 0) { throw new Exception('Valor total do pedido não pode ser zero.'); }

            // ===== CRIAR E SALVAR VENDA =====
            $venda = new Venda();
            $venda->usuario_id = $usuarioId;
            $venda->cliente_id = $clienteId;
            $venda->data_venda = date('Y-m-d H:i:s');
            $venda->observacoes = $data['observacoes'] ?? 'Pedido PWA';
            $venda->numero_parcelas = max(1, (int)($data['numero_parcelas'] ?? 1));
            $venda->status_venda_codigo = \app\modules\vendas\models\StatusVenda::EM_ABERTO;
            $venda->valor_total = $valorTotalVenda;

            // === INÍCIO DA ATUALIZAÇÃO (Request 2 - Backend) ===
            // Adiciona o ID do vendedor, se ele foi enviado na requisição e não está vazio
            $colaboradorId = $data['colaborador_vendedor_id'] ?? null;
            if (!empty($colaboradorId)) {
                // (Opcional) Validar se o colaborador realmente existe e pertence ao usuário
                // $colab = Colaborador::findOne(['id' => $colaboradorId, 'usuario_id' => $usuarioId, 'ativo' => true, 'eh_vendedor' => true]);
                // if (!$colab) throw new Exception("Colaborador vendedor inválido.");
                $venda->colaborador_vendedor_id = $colaboradorId;
            } else {
                $venda->colaborador_vendedor_id = null;
            }
            Yii::info("ID Colaborador Vendedor definido: " . ($venda->colaborador_vendedor_id ?? 'Nenhum'), 'api');
            // === FIM DA ATUALIZAÇÃO ===

            Yii::error("Atributos VENDA antes de save(): " . print_r($venda->attributes, true), 'api');
            
            if (!$venda->save()) {
                $erros = $venda->getFirstErrors();
                Yii::error("❌ FALHA ao salvar Venda: " . print_r($venda->errors, true), 'api');
                throw new Exception('Erro ao salvar venda: ' . implode(', ', $erros));
            }
            Yii::error("✅ Venda ID {$venda->id} salva com valor R$ {$venda->valor_total}", 'api');

            // ===== LOOP 2: CRIAR ITENS E ATUALIZAR ESTOQUE =====
            Yii::error("Iniciando criação de itens...", 'api');
            foreach ($data['itens'] as $index => $itemData) {
                $produto = Produto::findOne($itemData['produto_id']); // Busca novamente para garantir
                $item = new VendaItem();
                $item->venda_id = $venda->id;
                $item->produto_id = $produto->id;
                $item->quantidade = (int)$itemData['quantidade'];
                $item->preco_unitario_venda = (float)$itemData['preco_unitario'];
                // valor_total_item é calculado no beforeSave do VendaItem

                Yii::error("Tentando salvar item #{$index}: " . print_r($item->attributes, true), 'api');
                if (!$item->save()) {
                    $errosItem = $item->getFirstErrors();
                    Yii::error("❌ FALHA ao salvar VendaItem #{$index}: " . print_r($item->errors, true), 'api');
                    throw new Exception("Erro ao salvar item #{$index}: " . implode(', ', $errosItem));
                }
                Yii::error("✅ Item ID {$item->id} salvo com sucesso", 'api');

                // Atualizar estoque
                $produto->estoque_atual -= $item->quantidade;
                if (!$produto->save(false, ['estoque_atual'])) { // Salva apenas o campo estoque
                    Yii::error("❌ FALHA ao atualizar estoque do produto {$produto->id}", 'api');
                    throw new Exception("Erro ao atualizar estoque do produto '{$produto->nome}'.");
                }
                Yii::error("✅ Estoque de '{$produto->nome}' atualizado para {$produto->estoque_atual}", 'api');
            }
            Yii::error("Criação de itens concluída.", 'api');

            // ===== GERAR PARCELAS =====
            $venda->gerarParcelas($formaPagamentoId);
            Yii::error("Parcelas geradas para Venda ID {$venda->id}", 'api');

            // ===== COMMIT =====
            $transaction->commit();
            Yii::error("✅ Transação commitada com sucesso!", 'api');

            // Retorna a venda criada com sucesso (status 201)
            Yii::$app->response->statusCode = 201;
            $venda->refresh(); // Recarrega para pegar dados atualizados (como IDs gerados)
            
            // Expande as relações desejadas para retornar no JSON
            return $venda->toArray([], ['itens.produto', 'parcelas', 'cliente', 'vendedor']); // Adicionado 'vendedor'

        } catch (BadRequestHttpException $e) { // Erros de validação de input
            $transaction->rollBack();
            Yii::error("Rollback: BadRequest - " . $e->getMessage(), 'api');
            throw $e; // Re-lança para retornar 400
            
        } catch (Exception $e) { // Erros de lógica de negócio (estoque, etc)
            $transaction->rollBack();
            Yii::error("Rollback: Exception - " . $e->getMessage(), 'api');
            // Retorna 500 com a mensagem de erro específica
            throw new ServerErrorHttpException('Erro ao processar pedido: ' . $e->getMessage());
            
        } catch (\Throwable $t) { // Erros inesperados/críticos (ex: classe não encontrada)
            $transaction->rollBack();
            Yii::error("Rollback: Throwable - " . $t->getMessage(), 'api');
            // Retorna 500 com uma mensagem genérica ou a mensagem real
            throw new ServerErrorHttpException('Erro crítico ao processar pedido: ' . $t->getMessage());
        }
    }
}