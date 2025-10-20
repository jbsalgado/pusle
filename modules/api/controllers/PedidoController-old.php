<?php
// Namespace correto
namespace app\modules\api\controllers;

// Use statements corretos
use Yii;
use yii\rest\Controller;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\VendaItem;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\StatusVenda;
use yii\data\ActiveDataProvider;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use Exception; // Importar Exception padrão

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
        // Autenticação (opcional por enquanto)
        $behaviors['authenticator'] = [
             'class' => \yii\filters\auth\HttpBearerAuth::class,
             'optional' => ['index', 'create'], // Deixa actions abertas para teste
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
        // ... (código actionIndex sem mudanças) ...
        $clienteId = Yii::$app->request->get('cliente_id');
        if ($clienteId === null) { throw new BadRequestHttpException('...'); }
        $query = Venda::find()
            ->where(['cliente_id' => $clienteId])
            ->with(['itens.produto'])
            ->orderBy(['data_venda' => SORT_DESC]);
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 10],
             'serializer' => ['class' => 'yii\rest\Serializer', 'collectionEnvelope' => 'items'],
        ]);
    }

    /**
     * Cria um novo pedido recebido da PWA.
     * POST /api/pedido
     */
    public function actionCreate()
    {
        // Desabilitar CSRF
        Yii::$app->request->enableCsrfValidation = false;

        // Ler e decodificar JSON manualmente
        $rawBody = Yii::$app->request->getRawBody();
        Yii::error('Corpo Cru Recebido (Pedido): ' . $rawBody, 'api'); // Log
        $data = json_decode($rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Yii::error('Falha ao decodificar JSON (Pedido): ' . json_last_error_msg(), 'api');
            throw new BadRequestHttpException('JSON inválido recebido: ' . json_last_error_msg());
        }
        if (!is_array($data)) {
            Yii::error('json_decode não retornou array (Pedido). RawBody: '. $rawBody, 'api');
            throw new BadRequestHttpException('Dados recebidos em formato inesperado (Pedido).');
        }
        Yii::error('Dados Decodificados ($data Pedido): ' . print_r($data, true), 'api'); // Log

        // Validação inicial (chaves principais)
        $itensEstaoVazios = !isset($data['itens']) || empty($data['itens']) || !is_array($data['itens']);
        $clienteIdEstaVazio = !isset($data['cliente_id']) || empty($data['cliente_id']);
        $formaPgtoEstaVazia = !isset($data['forma_pagamento_id']) || empty($data['forma_pagamento_id']);
        Yii::error("Verificação inicial (Pedido): itens=" . ($itensEstaoVazios ? 'SIM' : 'NÃO') . ", cliente_id=" . ($clienteIdEstaVazio ? 'SIM' : 'NÃO') . ", forma_pgto=" . ($formaPgtoEstaVazia ? 'SIM' : 'NÃO'), 'api'); // Log

        if ($itensEstaoVazios || $clienteIdEstaVazio || $formaPgtoEstaVazia) {
             Yii::error('Validação inicial falhou (Pedido).', 'api');
             throw new BadRequestHttpException('Dados do pedido incompletos (itens, cliente_id ou forma_pagamento_id faltando/inválido).');
        }
        $formaPagamentoId = $data['forma_pagamento_id'];
        $clienteId = $data['cliente_id']; // ID REAL do cliente cadastrado
        // TODO: Validar se $formaPagamentoId e $clienteId existem no DB e são válidos/UUIDs

        // Identificar usuário
        $primeiroProdutoId = $data['itens'][0]['produto_id'] ?? null;
        if (!$primeiroProdutoId) { throw new BadRequestHttpException('ID do primeiro produto inválido (Pedido).'); }
        $primeiroProduto = Produto::findOne($primeiroProdutoId);
        if (!$primeiroProduto) { throw new BadRequestHttpException('Produto inicial não encontrado no pedido.'); }
        $usuarioId = $primeiroProduto->usuario_id;
        // TODO: Validar se $clienteId pertence a $usuarioId


        $transaction = Yii::$app->db->beginTransaction();
        $valorTotalVenda = 0;

        try {
            // ✅ 1. PRIMEIRO LOOP: Calcular valor total e validar estoque ANTES de salvar Venda
            Yii::error("Iniciando pré-cálculo de valor e validação de estoque (Pedido)...", 'api');
            foreach ($data['itens'] as $index => $itemData) {
                 if (empty($itemData['produto_id']) || empty($itemData['quantidade']) || !isset($itemData['preco_unitario'])) { /* ... erro item inválido ... */ }
                 $produtoId = $itemData['produto_id'];
                 $quantidadePedida = (int)$itemData['quantidade'];
                 $precoUnitario = (float)$itemData['preco_unitario'];
                 if ($quantidadePedida <= 0) { throw new Exception("..."); }
                 if ($precoUnitario < 0) { throw new Exception("..."); }
                 $produto = Produto::findOne($produtoId);
                 if (!$produto || $produto->usuario_id !== $usuarioId) { throw new Exception("..."); }
                 if (!$produto->temEstoque($quantidadePedida)) { throw new Exception("Produto {$produto->nome} (item #{$index}) sem estoque."); }
                 $valorTotalVenda += $quantidadePedida * $precoUnitario;
                 Yii::error("Item #{$index} (Produto {$produto->nome}): Qtd={$quantidadePedida}, Preço={$precoUnitario}. Total parcial = {$valorTotalVenda}", 'api');
            }
            Yii::error("Pré-cálculo concluído. Valor Total = {$valorTotalVenda}", 'api');
            if ($valorTotalVenda <= 0 && count($data['itens']) > 0) { throw new Exception('O valor total do pedido não pode ser zero...'); }


            // ✅ 2. Criar e Salvar a Venda com o valor total calculado
            $venda = new Venda();
            $venda->usuario_id = $usuarioId;
            $venda->cliente_id = $clienteId; // ID REAL
            $venda->data_venda = date('Y-m-d H:i:s');
            $venda->observacoes = $data['observacoes'] ?? 'Pedido PWA';
            $venda->numero_parcelas = max(1, (int)($data['numero_parcelas'] ?? 1));
            $venda->status_venda_codigo = StatusVenda::EM_ABERTO;
            $venda->valor_total = $valorTotalVenda; // <-- Define o valor ANTES de salvar

            Yii::error("Atributos VENDA ANTES do primeiro save(): " . print_r($venda->attributes, true), 'api'); // Log extra
            if (!$venda->save()) {
                 $erros = $venda->getFirstErrors();
                 Yii::error("Falha no PRIMEIRO save() da Venda: " . print_r($erros, true), 'api'); // Log detalhado do erro
                 throw new Exception('Erro ao salvar dados principais da venda: ' . implode(', ', $erros));
            }
            Yii::error("Venda ID {$venda->id} salva com valor total R$ {$venda->valor_total}.", 'api');


            // ✅ 3. SEGUNDO LOOP: Criar VendaItens e atualizar estoque
            Yii::error("Iniciando criação de VendaItens e atualização de estoque...", 'api');
            foreach ($data['itens'] as $index => $itemData) {
                $produto = Produto::findOne($itemData['produto_id']);
                $item = new VendaItem();
                $item->venda_id = $venda->id; // Associa ID
                $item->produto_id = $produto->id;
                $item->quantidade = (int)$itemData['quantidade'];
                $item->preco_unitario_venda = (float)$itemData['preco_unitario'];

                if (!$item->save()) { /* ... erro save item ... */ }

                // Baixa no estoque
                $produto->estoque_atual -= $item->quantidade;
                if (!$produto->save(false, ['estoque_atual'])) { /* ... erro save produto ... */ }
                Yii::error("Item ID {$item->id} (Produto {$produto->nome}) salvo. Estoque atualizado.", 'api');
            }
            Yii::error("Criação de VendaItens e atualização de estoque concluídos.", 'api');

            // ✅ 4. Gerar as Parcelas
            $venda->gerarParcelas($formaPagamentoId);
            Yii::error("Parcelas geradas para Venda ID {$venda->id}.", 'api');

            // ✅ 5. Finalizar Transação
            $transaction->commit();
            Yii::error("Transação commitada para Venda ID {$venda->id}.", 'api');

            Yii::$app->response->statusCode = 201;
            $venda->refresh();
            return $venda->toArray([], ['itens', 'parcelas', 'cliente']);

        // Blocos Catch (sem mudanças)
        } catch (BadRequestHttpException $e) { /* ... */ }
        catch (Exception $e) { /* ... */ }
        catch (\Throwable $t) { /* ... */ }
    }

} // Fim da classe