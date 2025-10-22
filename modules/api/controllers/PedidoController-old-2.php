<?php
namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\VendaItem;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\Colaborador; // Importar Colaborador
use yii\data\ActiveDataProvider;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\ForbiddenHttpException; // Para erro de autenticação/autorização
use yii\web\NotFoundHttpException; // Para cliente não encontrado
use yii\web\UnauthorizedHttpException; // Para token inválido/ausente
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
            // AGORA 'index' REQUER AUTENTICAÇÃO
            'optional' => ['create'],
        ];
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
             'cors' => [
                'Origin' => ['*'], // Em produção, restrinja
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*','Authorization'], // Garantir Authorization
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [],
            ],
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

    /**
     * Lista os pedidos do cliente autenticado.
     * Requer autenticação via Bearer Token (mesmo que base64 simples por enquanto).
     * O cliente_id é obtido do token decodificado.
     * GET /api/pedido
     * Header: Authorization: Bearer <seu_token_base64>
     */
     public function actionIndex()
     {
         $authHeader = Yii::$app->request->getHeaders()->get('Authorization');
         $clienteId = null;
         $tokenValido = false;

         Yii::info("Authorization Header recebido: " . ($authHeader ? 'Sim' : 'Não'), 'api');

         if ($authHeader !== null && preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
             $token = $matches[1];
             Yii::info("Token extraído: " . substr($token, 0, 10) . "...", 'api'); // Log parcial do token
             // Decodifica o token base64 simples gerado no ClienteController
             // !!! ATENÇÃO: ISTO NÃO É SEGURO PARA PRODUÇÃO !!!
             $payloadJson = base64_decode($token);
             if ($payloadJson) {
                 $payload = json_decode($payloadJson, true);
                 // Validação básica do payload (MELHORAR COM JWT REAL)
                 if (isset($payload['cliente_id']) && isset($payload['exp']) && $payload['exp'] > time()) {
                      $clienteId = $payload['cliente_id'];
                      $tokenValido = true;
                      Yii::info("Token decodificado com sucesso. Cliente ID: {$clienteId}, Exp: " . date('Y-m-d H:i:s', $payload['exp']), 'api');
                 } else {
                      Yii::warning("Payload do token inválido ou expirado: " . $payloadJson, 'api');
                 }
             } else {
                 Yii::warning("Falha ao decodificar token base64.", 'api');
             }
         } else {
             Yii::warning("Cabeçalho Authorization ausente ou mal formatado.", 'api');
         }

         // Se não conseguiu extrair um clienteId válido do token
         if (!$tokenValido || $clienteId === null) {
              throw new UnauthorizedHttpException('Autenticação inválida, ausente ou expirada.');
         }

         // [Opcional, mas recomendado] Verificar se o cliente realmente existe no banco
         $cliente = Cliente::findOne($clienteId);
         if (!$cliente || !$cliente->ativo) {
             Yii::error("Cliente ID {$clienteId} do token não encontrado ou inativo no banco.", 'api');
             throw new UnauthorizedHttpException('Cliente associado ao token não é válido.');
         }
         Yii::info("Cliente {$cliente->nome_completo} (ID: {$clienteId}) autenticado com sucesso para listar pedidos.", 'api');

         // Busca os pedidos DO CLIENTE AUTENTICADO
         $query = Venda::find()
             ->with(['itens.produto', 'cliente', 'statusVenda', 'parcelas']) // Incluir relações necessárias
             ->where(['cliente_id' => $clienteId]) // Filtra pelo cliente_id do token
             ->orderBy(['data_venda' => SORT_DESC]);

         // Use ActiveDataProvider para paginação e ordenação se necessário
         // Para retornar todos os pedidos sem paginação: return $query->asArray()->all();
         $dataProvider = new ActiveDataProvider([
             'query' => $query,
             'pagination' => [
                 'pageSize' => 50, // Ajuste o tamanho da página ou remova para sem paginação
             ],
             // A linha 'serializer' foi removida pois causava erro
         ]);

         // O ActiveDataProvider será automaticamente serializado para JSON
         return $dataProvider;
     }


    /**
     * Cria novo pedido
     * POST /api/pedido
     */
    public function actionCreate()
    {
        // ... (código da actionCreate permanece o mesmo que você enviou) ...
        // Desabilitar validação CSRF para APIs stateless
        Yii::$app->request->enableCsrfValidation = false;

        $rawBody = Yii::$app->request->getRawBody();
        Yii::info('Corpo Cru Recebido (Pedido Create): ' . $rawBody, 'api'); // Log como info

        $data = json_decode($rawBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Yii::error('Falha ao decodificar JSON (Pedido Create): ' . json_last_error_msg(), 'api');
            throw new BadRequestHttpException('JSON inválido: ' . json_last_error_msg());
        }

        if (!is_array($data)) {
            Yii::error('json_decode não retornou array (Pedido Create). RawBody: '. $rawBody, 'api');
            throw new BadRequestHttpException('Dados em formato inesperado.');
        }

        Yii::info('Dados Decodificados ($data Pedido Create): ' . print_r($data, true), 'api'); // Log como info

        // Validação inicial
        $itensVazios = !isset($data['itens']) || empty($data['itens']) || !is_array($data['itens']);
        $clienteIdVazio = !isset($data['cliente_id']) || empty($data['cliente_id']);
        $formaPgtoVazia = !isset($data['forma_pagamento_id']) || empty($data['forma_pagamento_id']);

        Yii::info("Verificação inicial (Pedido Create): itens=" . ($itensVazios ? 'VAZIO' : 'OK') .
                   ", cliente_id=" . ($clienteIdVazio ? 'VAZIO' : 'OK') .
                   ", forma_pgto=" . ($formaPgtoVazia ? 'VAZIO' : 'OK'), 'api'); // Log como info

        if ($itensVazios || $clienteIdVazio || $formaPgtoVazia) {
            Yii::error('Validação inicial falhou (Pedido Create).', 'api');
            throw new BadRequestHttpException('Dados incompletos: itens, cliente_id ou forma_pagamento_id faltando.');
        }

        $formaPagamentoId = $data['forma_pagamento_id'];
        $clienteId = $data['cliente_id'];
        $numeroParcelas = max(1, (int)($data['numero_parcelas'] ?? 1));

        // === VALIDAÇÃO ATUALIZADA: Data do primeiro pagamento e intervalo ===
        $dataPrimeiroPagamento = null;
        $intervaloDiasParcelas = 30; // Valor padrão

        if ($numeroParcelas > 1) {
            // Valida data do primeiro pagamento
            if (empty($data['data_primeiro_pagamento'])) {
                 Yii::error("Erro validação: Data 1º pagamento ausente para {$numeroParcelas} parcelas.", 'api');
                throw new BadRequestHttpException('Data do primeiro pagamento é obrigatória para vendas parceladas.');
            }
            $dataPrimeiroPagamento = $data['data_primeiro_pagamento'];

            // Valida formato da data
            $dataObj = \DateTime::createFromFormat('Y-m-d', $dataPrimeiroPagamento);
            if (!$dataObj || $dataObj->format('Y-m-d') !== $dataPrimeiroPagamento) {
                Yii::error("Erro validação: Formato data inválido '{$dataPrimeiroPagamento}'.", 'api');
                throw new BadRequestHttpException('Formato de data inválido. Use YYYY-MM-DD.');
            }

            // Valida se a data não é anterior a hoje
            $hoje = new \DateTime(); $hoje->setTime(0, 0, 0); $dataObj->setTime(0, 0, 0);
            if ($dataObj < $hoje) {
                 Yii::error("Erro validação: Data '{$dataPrimeiroPagamento}' anterior a hoje.", 'api');
                throw new BadRequestHttpException('A data do primeiro pagamento não pode ser anterior à data de hoje.');
            }

            // Valida intervalo entre parcelas
            if (!isset($data['intervalo_dias_parcelas'])) { // Checa se existe, mesmo que seja '0' ou null
                 Yii::error("Erro validação: Intervalo de dias ausente para {$numeroParcelas} parcelas.", 'api');
                 throw new BadRequestHttpException('Intervalo entre parcelas é obrigatório para vendas parceladas.');
            }
            $intervaloDiasParcelas = (int)$data['intervalo_dias_parcelas'];
            if ($intervaloDiasParcelas < 1) {
                Yii::error("Erro validação: Intervalo {$intervaloDiasParcelas} inválido (<1).", 'api');
                throw new BadRequestHttpException('Intervalo entre parcelas deve ser no mínimo 1 dia.');
            }
            if ($intervaloDiasParcelas > 365) {
                Yii::error("Erro validação: Intervalo {$intervaloDiasParcelas} inválido (>365).", 'api');
                throw new BadRequestHttpException('Intervalo entre parcelas não pode ser maior que 365 dias.');
            }

            Yii::info("Pedido Parcelado: Data 1º Pgto={$dataPrimeiroPagamento}, Intervalo={$intervaloDiasParcelas} dias.", 'api');

        } else {
             Yii::info("Pedido à vista (1 parcela).", 'api');
        }
        // === FIM VALIDAÇÃO DATA/INTERVALO ===


        // Identificar usuário da loja (dono dos produtos)
        $primeiroProdutoId = $data['itens'][0]['produto_id'] ?? null;
        if (!$primeiroProdutoId) {
             Yii::error('ID do primeiro produto ausente ou carrinho vazio.', 'api');
             throw new BadRequestHttpException('ID do primeiro produto inválido ou carrinho vazio.');
        }
        $primeiroProduto = Produto::findOne($primeiroProdutoId);
        if (!$primeiroProduto) {
             Yii::error("Produto ID {$primeiroProdutoId} não encontrado.", 'api');
             throw new NotFoundHttpException('Produto não encontrado.');
        }
        $usuarioId = $primeiroProduto->usuario_id;
        if (!$usuarioId) {
              Yii::error("Produto ID {$primeiroProdutoId} não tem usuario_id associado.", 'api');
              throw new ServerErrorHttpException('Não foi possível identificar o usuário da loja.');
        }
        Yii::info("Loja identificada pelo produto {$primeiroProdutoId}: Usuario ID {$usuarioId}", 'api');

        $transaction = Yii::$app->db->beginTransaction();
        $valorTotalVenda = 0; // Valor base

        try {
            // ===== LOOP 1: PRÉ-CÁLCULO E VALIDAÇÃO DE ESTOQUE =====
            Yii::info("Iniciando pré-cálculo e validação...", 'api');
            foreach ($data['itens'] as $index => $itemData) {
                 if (empty($itemData['produto_id']) || !isset($itemData['quantidade']) || !isset($itemData['preco_unitario'])) {
                      Yii::error("Item #{$index} tem dados incompletos: " . print_r($itemData, true), 'api');
                     throw new BadRequestHttpException("Item #{$index} tem dados incompletos.");
                 }
                 $produtoId = $itemData['produto_id'];
                 $quantidadePedida = (int)$itemData['quantidade'];
                 $precoUnitario = (float)$itemData['preco_unitario'];

                 if ($quantidadePedida <= 0) {
                      Yii::error("Item #{$index}: quantidade {$quantidadePedida} inválida.", 'api');
                     throw new BadRequestHttpException("Item #{$index}: quantidade deve ser maior que zero.");
                 }
                 if ($precoUnitario < 0) {
                      Yii::error("Item #{$index}: preço {$precoUnitario} inválido.", 'api');
                     throw new BadRequestHttpException("Item #{$index}: preço não pode ser negativo.");
                 }

                 $produto = Produto::findOne($produtoId);
                 if (!$produto || $produto->usuario_id !== $usuarioId) {
                      Yii::error("Item #{$index}: produto ID {$produtoId} inválido ou não pertence à loja {$usuarioId}.", 'api');
                     throw new BadRequestHttpException("Item #{$index}: produto inválido ou não pertence à loja.");
                 }
                 if ($produto->estoque_atual < $quantidadePedida) {
                      Yii::error("Estoque insuficiente para '{$produto->nome}' (ID: {$produto->id}). Disponível: {$produto->estoque_atual}, Pedido: {$quantidadePedida}", 'api');
                      throw new BadRequestHttpException("Produto '{$produto->nome}' sem estoque suficiente. Disponível: {$produto->estoque_atual}.");
                 }

                 $valorTotalVenda += $quantidadePedida * $precoUnitario;
                 Yii::info("Item #{$index} ({$produto->nome}): Qtd={$quantidadePedida}, Preço={$precoUnitario}. Total parcial={$valorTotalVenda}", 'api');
            }

            Yii::info("Pré-cálculo concluído. Valor Total Base = {$valorTotalVenda}", 'api');
            if ($valorTotalVenda < 0 && count($data['itens']) > 0) { // Permitir 0 se for brinde? Mas validação de item impede.
                Yii::error("Valor total do pedido (base) {$valorTotalVenda} inválido.", 'api');
                 throw new BadRequestHttpException('Valor total do pedido (base) não pode ser negativo.');
            }

            // ===== CRIAR E SALVAR VENDA =====
            $venda = new Venda();
            $venda->usuario_id = $usuarioId;
            $venda->cliente_id = $clienteId;
            $venda->data_venda = date('Y-m-d H:i:s');
            $venda->observacoes = mb_strtoupper($data['observacoes'] ?? 'Pedido PWA', 'UTF-8'); // Convertendo para maiúsculo
            $venda->numero_parcelas = $numeroParcelas;
            $venda->status_venda_codigo = \app\modules\vendas\models\StatusVenda::EM_ABERTO;
            $venda->valor_total = $valorTotalVenda;

            $colaboradorId = $data['colaborador_vendedor_id'] ?? null;
            if (!empty($colaboradorId)) {
                $vendedor = Colaborador::findOne(['id' => $colaboradorId, 'usuario_id' => $usuarioId, 'ativo' => true]);
                if ($vendedor) {
                    $venda->colaborador_vendedor_id = $colaboradorId;
                     Yii::info("Associando vendedor ID {$colaboradorId} ({$vendedor->nome_completo})", 'api');
                } else {
                     Yii::warning("Vendedor ID {$colaboradorId} inválido/inativo para loja {$usuarioId}. Ignorando.", 'api');
                     $venda->colaborador_vendedor_id = null;
                }
            } else {
                 $venda->colaborador_vendedor_id = null;
                 Yii::info("Nenhum vendedor associado.", 'api');
            }

            if ($dataPrimeiroPagamento) {
                $venda->data_primeiro_vencimento = $dataPrimeiroPagamento;
                Yii::info("Data Primeiro Vencimento definida para {$dataPrimeiroPagamento}", 'api');
            } else {
                 Yii::info("Data Primeiro Vencimento não aplicável (à vista).", 'api');
            }

            Yii::info("Atributos VENDA antes de save(): " . print_r($venda->attributes, true), 'api');

            if (!$venda->save()) {
                $erros = $venda->getFirstErrors();
                Yii::error("❌ FALHA ao salvar Venda: " . print_r($venda->errors, true), 'api');
                throw new ServerErrorHttpException('Erro ao salvar venda: ' . reset($erros));
            }
            Yii::info("✅ Venda ID {$venda->id} salva com valor base R$ {$venda->valor_total}", 'api');

            // ===== LOOP 2: CRIAR ITENS E ATUALIZAR ESTOQUE =====
            Yii::info("Iniciando criação de itens para Venda ID {$venda->id}...", 'api');
            foreach ($data['itens'] as $index => $itemData) {
                $produto = Produto::findOne($itemData['produto_id']);
                if (!$produto) {
                    Yii::error("Erro crítico: Produto {$itemData['produto_id']} não encontrado no segundo loop.", 'api');
                    throw new ServerErrorHttpException("Erro crítico: Produto do item #{$index} não encontrado.");
                }

                $item = new VendaItem();
                $item->venda_id = $venda->id;
                $item->produto_id = $produto->id;
                $item->quantidade = (int)$itemData['quantidade'];
                $item->preco_unitario_venda = (float)$itemData['preco_unitario'];

                Yii::info("Tentando salvar item #{$index} (Prod ID {$produto->id}): " . print_r($item->attributes, true), 'api');
                if (!$item->save()) {
                    $errosItem = $item->getFirstErrors();
                    Yii::error("❌ FALHA ao salvar VendaItem #{$index}: " . print_r($item->errors, true), 'api');
                    throw new ServerErrorHttpException("Erro ao salvar item #{$index}: " . reset($errosItem));
                }
                 Yii::info("✅ Item ID {$item->id} (Prod ID {$produto->id}) salvo.", 'api');

                // Atualizar estoque
                $estoqueAnterior = $produto->estoque_atual;
                $produto->estoque_atual -= $item->quantidade;
                if (!$produto->save(false, ['estoque_atual'])) {
                     Yii::error("❌ FALHA ao atualizar estoque do produto {$produto->id}. Estoque anterior: {$estoqueAnterior}, Tentativa: {$produto->estoque_atual}", 'api');
                     throw new ServerErrorHttpException("Erro ao atualizar estoque do produto '{$produto->nome}'.");
                }
                Yii::info("✅ Estoque de '{$produto->nome}' (ID: {$produto->id}) atualizado de {$estoqueAnterior} para {$produto->estoque_atual}", 'api');
            }
            Yii::info("Criação de itens concluída para Venda ID {$venda->id}.", 'api');

            // ===== GERAR PARCELAS =====
            Yii::info("Gerando parcelas para Venda ID {$venda->id}...", 'api');
            $venda->gerarParcelas($formaPagamentoId, $dataPrimeiroPagamento, $intervaloDiasParcelas);
            Yii::info("Parcelas geradas para Venda ID {$venda->id}. 1º pagto em {$dataPrimeiroPagamento}, intervalo {$intervaloDiasParcelas} dias.", 'api');


            // ===== COMMIT =====
            $transaction->commit();
            Yii::info("✅ Transação commitada com sucesso para Venda ID {$venda->id}!", 'api');

            Yii::$app->response->statusCode = 201;
            $venda->refresh();

            // Retorna a venda criada com relações
            return $venda->toArray([], ['itens.produto', 'parcelas', 'cliente', 'vendedor', 'statusVenda']);

        } catch (BadRequestHttpException $e) {
            $transaction->rollBack(); Yii::error("Rollback (Pedido Create - Bad Request): " . $e->getMessage(), 'api');
            throw $e;
        } catch (NotFoundHttpException $e) {
             $transaction->rollBack(); Yii::error("Rollback (Pedido Create - Not Found): " . $e->getMessage(), 'api');
             throw $e;
        } catch (ServerErrorHttpException $e) {
             $transaction->rollBack(); Yii::error("Rollback (Pedido Create - Server Error): " . $e->getMessage(), 'api');
             throw $e;
        } catch (Exception $e) {
            $transaction->rollBack(); Yii::error("Rollback (Pedido Create - Exception): " . $e->getMessage(), 'api');
            throw new ServerErrorHttpException('Erro ao processar pedido: ' . $e->getMessage());
        } catch (\Throwable $t) {
            $transaction->rollBack(); Yii::error("Rollback (Pedido Create - Throwable): " . $t->getMessage() . ' File: ' . $t->getFile() . ' Line: ' . $t->getLine(), 'api');
            throw new ServerErrorHttpException('Erro crítico ao processar pedido: ' . $t->getMessage());
        }
    } // Fim actionCreate
} // Fim Controller