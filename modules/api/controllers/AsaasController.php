<?php
namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\db\Expression;
// ATUALIZADO: Removido o 'use GuzzleHttp\Client;' original
// MANTIDO: As exceções do Guzzle para compatibilidade com o código original (catch blocks)
use GuzzleHttp\Exception\GuzzleException; 
use GuzzleHttp\Exception\ConnectException; 
use GuzzleHttp\Exception\RequestException; 
use GuzzleHttp\Exception\ClientException; 
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request; 
use GuzzleHttp\Psr7\Response as GuzzleResponse; 
// ADICIONADO: Necessário para logar o CSRF
use yii\base\ActionEvent;


class AsaasController extends Controller
{
    /**
     * Desabilita verificação CSRF para APIs (Mantido do original)
     */
    public $enableCsrfValidation = false;

    /**
     * URLs da API Asaas (Mantido do original)
     */
    private const API_URL_PRODUCTION = 'https://api.asaas.com/v3';
    private const API_URL_SANDBOX = 'https://sandbox.asaas.com/api/v3';

    /**
     * Configura formato de resposta como JSON (Mantido do original)
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        
        // Desabilita CSRF se for webhook (Garantia extra de que o beforeAction será executado)
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\CompositeAuth::class,
            'except' => ['webhook', 'consultar-status'], // Permite acesso público a estes
        ];
        
        return $behaviors;
    }

    /**
     * ✅ CORREÇÃO CSRF: Garante que o CSRF é desabilitado antes de tudo para o webhook
     */
    public function beforeAction($action)
    {
        if ($action->id === 'webhook') {
            Yii::$app->request->enableCsrfValidation = false;
            Yii::info('CSRF desabilitado para actionWebhook', 'asaas'); 
        }
        return parent::beforeAction($action);
    }

    /**
     * ========================================================================
     * ENDPOINT: POST /api/asaas/criar-cobranca
     * ========================================================================
     */
    public function actionCriarCobranca()
    {
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            $rawBody = Yii::$app->request->getRawBody();
            $request = json_decode($rawBody, true);
            
            if (is_null($request)) {
                Yii::error('Falha ao decodificar JSON body. Body recebido: ' . $rawBody, 'asaas');
                throw new \Exception('Requisição JSON inválida ou vazia.');
            }
            
            // 1️⃣ VALIDAÇÃO
            $this->validarRequestCobranca($request);
            
            // 2️⃣ BUSCAR USUÁRIO (LOJA)
            $usuario = $this->buscarUsuarioComValidacao($request['usuario_id']);
            
            // 3️⃣ BUSCAR OU CRIAR CLIENTE NA ASAAS
            $asaasCustomerId = $this->buscarOuCriarClienteAsaas($request['cliente'], $usuario);
            
            // 4️⃣ GERAR REFERÊNCIA ÚNICA
            $externalReference = $this->gerarExternalReference($usuario['id']);
            
            // 5️⃣ PREPARAR DADOS DA COBRANÇA
            $dadosCobranca = [
                'customer' => $asaasCustomerId,
                'billingType' => $request['metodo_pagamento'], // PIX, BOLETO, CREDIT_CARD
                'value' => floatval($request['valor']),
                'dueDate' => $request['vencimento'] ?? date('Y-m-d', strtotime('+3 days')),
                'description' => $request['descricao'] ?? 'Pedido',
                'externalReference' => $externalReference,
                'postalService' => false
            ];
            
            // Configurações específicas por método
            if ($request['metodo_pagamento'] === 'CREDIT_CARD' && isset($request['cartao'])) {
                $dadosCobranca = array_merge($dadosCobranca, [
                    'creditCard' => $this->prepararDadosCartao($request['cartao']),
                    'creditCardHolderInfo' => $this->prepararDadosTitular($request['cliente'])
                ]);
            }
            
            // Parcelamento (se aplicável)
            if (isset($request['parcelas']) && $request['parcelas'] > 1) {
                $dadosCobranca['installmentCount'] = intval($request['parcelas']);
                $dadosCobranca['installmentValue'] = round($dadosCobranca['value'] / $request['parcelas'], 2);
            }
            
            // 6️⃣ CRIAR COBRANÇA NA ASAAS
            $cobranca = $this->chamarApiAsaas(
                'POST',
                '/payments',
                $dadosCobranca,
                $usuario
            );
            
            if (!is_array($cobranca) || !isset($cobranca['id'])) {
                Yii::error('Resposta inesperada da Asaas ao criar cobrança: ' . json_encode($cobranca), 'asaas');
                throw new \Exception('Falha ao criar cobrança. Resposta inválida da Asaas.');
            }

            Yii::info([
                'action' => 'cobranca_criada',
                'payment_id' => $cobranca['id'],
                'external_reference' => $externalReference,
                'valor' => $dadosCobranca['value']
            ], 'asaas');
            
            // 7️⃣ SALVAR NO POSTGRES
            $cobrancaLocalId = $this->salvarCobrancaNoBanco([
                'payment_id' => $cobranca['id'],
                'external_reference' => $externalReference,
                'usuario_id' => $usuario['id'],
                'cliente_id' => $request['cliente_id'] ?? null,
                'customer_asaas_id' => $asaasCustomerId,
                'valor' => $dadosCobranca['value'],
                'metodo_pagamento' => $request['metodo_pagamento'],
                'status' => $cobranca['status'],
                'status_asaas' => $cobranca['status'],
                'vencimento' => $dadosCobranca['dueDate'],
                'dados_request' => $request,
                'dados_cobranca' => $cobranca,
                'ambiente' => $usuario['asaas_sandbox'] ? 'sandbox' : 'producao',
                'pedido_id' => null
            ]);
            
            $transaction->commit();
            
            // 8️⃣ PREPARAR RESPOSTA
            $resposta = [
                'sucesso' => true,
                'payment_id' => $cobranca['id'],
                'external_reference' => $externalReference,
                'status' => $cobranca['status'],
                'valor' => $cobranca['value'],
                'vencimento' => $cobranca['dueDate'],
                'cobranca_local_id' => $cobrancaLocalId
            ];
            
            // Adicionar dados específicos por método de pagamento
            switch ($request['metodo_pagamento']) {
                case 'PIX':
                    try {
                        $pixData = $this->chamarApiAsaas(
                            'GET',
                            "/payments/{$cobranca['id']}/pixQrCode",
                            [],
                            $usuario
                        );
                        $resposta['pix'] = [
                            'payload' => $pixData['payload'] ?? null,
                            'encoded_image' => $pixData['encodedImage'] ?? null,
                            'expiracao' => $pixData['expirationDate'] ?? null
                        ];
                    } catch (\Exception $e) {
                         $resposta['pix'] = [
                            'payload' => $cobranca['pixCopyPasteCode'] ?? null,
                            'expiracao' => $cobranca['pixExpirationDate'] ?? null
                        ];
                    }
                    break;
                    
                case 'BOLETO':
                    $resposta['boleto'] = [
                        'url' => $cobranca['bankSlipUrl'] ?? null,
                        'codigo_barras' => $cobranca['identificationField'] ?? null,
                        'linha_digitavel' => $cobranca['nossoNumero'] ?? null
                    ];
                    break;
                    
                case 'CREDIT_CARD':
                    $resposta['cartao'] = [
                        'status' => $cobranca['status'],
                        'transacao_id' => $cobranca['transactionReceiptUrl'] ?? null
                    ];
                    break;
            }
            
            return $resposta;
            
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $transaction->rollBack();
            
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null;
            $response = $responseBody ? json_decode($responseBody, true) : null;
            
            Yii::error([
                'action' => 'erro_criar_cobranca',
                'error' => $e->getMessage(),
                'response' => $response
            ], 'asaas');
            
            // Tenta extrair a mensagem de erro específica da Asaas
            $errorMessage = $response['errors'][0]['description'] ?? 'Erro na API Asaas';
            
            // Se a resposta for o HTML de login (erro de autenticação)
            if (strpos($responseBody, '<title>Login</title>') !== false) {
                 $errorMessage = 'Erro de autenticação com Asaas. Verifique a API Key.';
                 Yii::error('Asaas retornou página de login. API Key provavelmente inválida.', 'asaas');
            }
            
            return $this->errorResponse(
                $errorMessage,
                $e->getCode()
            );
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            
            Yii::error([
                'action' => 'erro_criar_cobranca',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'asaas');
            
            return $this->errorResponse('Erro interno: ' . $e->getMessage(), 500);
        }
    }

    /**
     * ========================================================================
     * ENDPOINT: GET /api/asaas/consultar-status?payment_id=...&usuario_id=...
     * ✅ NOVO MÉTODO PARA POLLING (SONDAGEM)
     * ========================================================================
     */
    // public function actionConsultarStatus($payment_id, $usuario_id)
    // {
    //     Yii::info("[POLLING] Consultando status - Payment: {$payment_id}, User: {$usuario_id}", 'asaas');
        
    //     // ✅ FORÇA RESPOSTA JSON
    //     Yii::$app->response->format = Response::FORMAT_JSON;
        
    //     $transaction = Yii::$app->db->beginTransaction();
        
    //     try {
    //         if (!$this->validarUUID($usuario_id)) {
    //             Yii::error("[POLLING] UUID inválido: {$usuario_id}", 'asaas');
    //             return $this->errorResponse('ID de usuário inválido', 400);
    //         }
            
    //         $usuario = $this->buscarUsuarioComValidacao($usuario_id);
    //         $cobrancaLocal = $this->buscarCobrancaPorPaymentId($payment_id);
            
    //         if (!$cobrancaLocal) {
    //             Yii::error("[POLLING] Cobrança não encontrada: {$payment_id}", 'asaas');
    //             return $this->errorResponse('Cobrança local não encontrada', 404);
    //         }
            
    //         // 1. Consultar a Asaas API
    //         $cobrancaAsaas = $this->chamarApiAsaas(
    //             'GET',
    //             "/payments/{$payment_id}",
    //             [],
    //             $usuario
    //         );
            
    //         $statusAsaas = $cobrancaAsaas['status'] ?? 'PENDING';
    //         Yii::info("[POLLING] Status Asaas: {$statusAsaas}", 'asaas');
            
    //         // 2. Processar pedido se pago
    //         $pedidoCriadoId = $cobrancaLocal['pedido_id']; 
    //         $statusAtualizado = $cobrancaLocal['status'];

    //         if (in_array($statusAsaas, ['RECEIVED', 'CONFIRMED'])) {
    //             Yii::info("[POLLING] Pagamento confirmado! Processando pedido...", 'asaas');
                
    //             if ($cobrancaLocal['status'] !== 'pago' || empty($cobrancaLocal['pedido_id'])) {
    //                 try {
    //                     $pedidoCriadoId = $this->criarPedidoSeNecessario($cobrancaAsaas, $cobrancaLocal, $usuario);
    //                     Yii::info("[POLLING] ✅ Pedido criado: {$pedidoCriadoId}", 'asaas');
    //                 } catch (\Exception $e) {
    //                     Yii::error("[POLLING] Erro ao criar pedido: {$e->getMessage()}", 'asaas');
    //                     throw $e;
    //                 }
    //             } else {
    //                 $pedidoCriadoId = $cobrancaLocal['pedido_id'];
    //                 Yii::info("[POLLING] Pedido já existe: {$pedidoCriadoId}", 'asaas');
    //             }
    //             $statusAtualizado = 'pago'; 
    //         } else {
    //             // 3. Atualiza status localmente
    //             $this->atualizarStatusCobranca($cobrancaLocal['id'], [
    //                 'status' => strtolower($statusAsaas), 
    //                 'status_asaas' => $statusAsaas,
    //                 'valor_recebido' => $cobrancaAsaas['value'] ?? null,
    //                 'data_pagamento' => $cobrancaAsaas['paymentDate'] ?? null,
    //                 'dados_cobranca' => $cobrancaAsaas
    //             ]);
    //             $statusAtualizado = strtolower($statusAsaas);
    //         }
            
    //         $transaction->commit();
            
    //         $resposta = [
    //             'sucesso' => true,
    //             'status' => $statusAtualizado, 
    //             'status_asaas' => $statusAsaas, 
    //             'pedido_id' => $pedidoCriadoId,
    //             'valor' => $cobrancaAsaas['value'] ?? $cobrancaLocal['valor']
    //         ];
            
    //         Yii::info("[POLLING] Resposta: " . json_encode($resposta), 'asaas');
    //         return $resposta;
            
    //     } catch (\Exception $e) {
    //         $transaction->rollBack();
    //         Yii::error("[POLLING] ERRO: {$e->getMessage()}\nTrace: {$e->getTraceAsString()}", 'asaas');
    //         return $this->errorResponse('Erro na consulta de status: ' . $e->getMessage(), 500);
    //     }
    // }

    
    public function actionConsultarStatus($payment_id, $usuario_id)
    {
        Yii::info("[POLLING] Consultando status - Payment: {$payment_id}, User: {$usuario_id}", 'asaas');
        
        // ✅ FORÇA RESPOSTA JSON
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            if (!$this->validarUUID($usuario_id)) {
                Yii::error("[POLLING] UUID inválido: {$usuario_id}", 'asaas');
                return $this->errorResponse('ID de usuário inválido', 400);
            }
            
            $usuario = $this->buscarUsuarioComValidacao($usuario_id);
            $cobrancaLocal = $this->buscarCobrancaPorPaymentId($payment_id);
            
            if (!$cobrancaLocal) {
                Yii::error("[POLLING] Cobrança não encontrada: {$payment_id}", 'asaas');
                return $this->errorResponse('Cobrança local não encontrada', 404);
            }
            
            // 1. Consultar a Asaas API
            $cobrancaAsaas = $this->chamarApiAsaas(
                'GET',
                "/payments/{$payment_id}",
                [],
                $usuario
            );
            
            $statusAsaas = $cobrancaAsaas['status'] ?? 'PENDING';
            Yii::info("[POLLING] Status Asaas: {$statusAsaas}", 'asaas');
            
            // 2. Processar pedido se pago
            $pedidoCriadoId = $cobrancaLocal['pedido_id']; 
            $statusAtualizado = $cobrancaLocal['status'];

            if (in_array($statusAsaas, ['RECEIVED', 'CONFIRMED'])) {
                Yii::info("[POLLING] Pagamento confirmado! Processando pedido...", 'asaas');
                
                if ($cobrancaLocal['status'] !== 'QUITADA' || empty($cobrancaLocal['pedido_id'])) {
                    try {
                        $pedidoCriadoId = $this->criarPedidoSeNecessario($cobrancaAsaas, $cobrancaLocal, $usuario);
                        Yii::info("[POLLING] ✅ Pedido criado: {$pedidoCriadoId}", 'asaas');
                    } catch (\Exception $e) {
                        Yii::error("[POLLING] Erro ao criar pedido: {$e->getMessage()}", 'asaas');
                        throw $e;
                    }
                } else {
                    $pedidoCriadoId = $cobrancaLocal['pedido_id'];
                    Yii::info("[POLLING] Pedido já existe: {$pedidoCriadoId}", 'asaas');
                }
                $statusAtualizado = 'QUITADA'; 
            } else {
                // 3. Atualiza status localmente
                $this->atualizarStatusCobranca($cobrancaLocal['id'], [
                    'status' => strtolower($statusAsaas), 
                    'status_asaas' => $statusAsaas,
                    'valor_recebido' => $cobrancaAsaas['value'] ?? null,
                    'data_pagamento' => $cobrancaAsaas['paymentDate'] ?? null,
                    'dados_cobranca' => $cobrancaAsaas
                ]);
                $statusAtualizado = strtolower($statusAsaas);
            }
            
            $transaction->commit();
            
            $resposta = [
                'sucesso' => true,
                'status' => $statusAtualizado, 
                'status_asaas' => $statusAsaas, 
                'pedido_id' => $pedidoCriadoId,
                'valor' => $cobrancaAsaas['value'] ?? $cobrancaLocal['valor']
            ];
            
            Yii::info("[POLLING] Resposta: " . json_encode($resposta), 'asaas');

            // ✅✅✅ INÍCIO DA CORREÇÃO (SERVER-SIDE) ✅✅✅
            // Estas linhas ordenam ao navegador para NUNCA guardar esta resposta
            // em cache. É uma garantia extra.
            $headers = Yii::$app->response->headers;
            $headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $headers->set('Pragma', 'no-cache');
            $headers->set('Expires', gmdate('D, d M Y H:i:s') . ' GMT');
            // ✅✅✅ FIM DA CORREÇÃO (SERVER-SIDE) ✅✅✅

            return $resposta;
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("[POLLING] ERRO: {$e->getMessage()}\nTrace: {$e->getTraceAsString()}", 'asaas');
            
            // Adiciona headers anti-cache também no erro
            $headers = Yii::$app->response->headers;
            $headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $headers->set('Pragma', 'no-cache');
            
            return $this->errorResponse('Erro na consulta de status: ' . $e->getMessage(), 500);
        }
    }


    /**
     * ========================================================================
     * ENDPOINT: POST /api/asaas/webhook
     * ========================================================================
     */
    public function actionWebhook()
    {
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            $body = Yii::$app->request->getRawBody();
            $data = json_decode($body, true);
            
            Yii::info([
                'action' => 'webhook_recebido',
                'event' => $data['event'] ?? null,
                'data' => $data,
                'headers' => Yii::$app->request->headers->toArray()
            ], 'asaas');
            
            if (!isset($data['event']) || !isset($data['payment'])) {
                return $this->errorResponse('Webhook inválido', 400);
            }
            
            $event = $data['event'];
            $payment = $data['payment'];
            $paymentId = $payment['id'];
            
            Yii::info("📥 Webhook: {$event} para payment: {$paymentId}", 'asaas');
            
            $cobranca = $this->buscarCobrancaPorPaymentId($paymentId);
            
            if (!$cobranca) {
                Yii::warning("Cobrança não encontrada: {$paymentId}", 'asaas');
                return ['status' => 'warning', 'message' => 'Cobrança não encontrada'];
            }
            
            $usuario = $this->buscarUsuarioPorId($cobranca['usuario_id']);
            
            if (!$usuario) {
                throw new \Exception("Usuário não encontrado: {$cobranca['usuario_id']}");
            }
            
            // Consultar a Asaas para obter dados completos e evitar fraude simples
            $cobrancaAtualizada = $this->chamarApiAsaas(
                'GET',
                "/payments/{$paymentId}",
                [],
                $usuario
            );
            
            $statusAsaas = $cobrancaAtualizada['status'] ?? 'UNKNOWN';

            $resultado = $this->processarEventoWebhook($event, $cobrancaAtualizada, $cobranca, $usuario);

            // 1. Tenta criar o pedido APENAS se o status for de sucesso e ainda não tiver pedido
            if (in_array($statusAsaas, ['RECEIVED', 'CONFIRMED']) && empty($cobranca['pedido_id'])) {
                $resultado['pedido_id'] = $this->criarPedidoSeNecessario($cobrancaAtualizada, $cobranca, $usuario);
            }
            
            // 2. Atualiza o status localmente (inclusive se for cancelamento/vencido)
            $this->atualizarStatusCobranca($cobranca['id'], [
                'status' => strtolower($statusAsaas),
                'status_asaas' => $statusAsaas,
                'valor_recebido' => $cobrancaAtualizada['value'] ?? null,
                'data_pagamento' => $cobrancaAtualizada['paymentDate'] ?? null,
                'dados_cobranca' => $cobrancaAtualizada
            ]);
            
            $transaction->commit();
            
            return [
                'status' => 'ok',
                'event' => $event,
                'payment_id' => $paymentId,
                'processado' => $resultado
            ];
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error(['action' => 'webhook_erro', 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 'asaas');
            return $this->errorResponse('Erro interno: ' . $e->getMessage(), 500);
        }
    }

    /**
     * ========================================================================
     * ENDPOINT: GET /api/asaas/consultar-cobranca (Mantido do original)
     * ========================================================================
     */
    public function actionConsultarCobranca($payment_id, $usuario_id)
    {
        try {
            if (!$this->validarUUID($usuario_id)) {
                return $this->errorResponse('ID de usuário inválido', 400);
            }
            
            $usuario = $this->buscarUsuarioPorId($usuario_id);
            
            if (!$usuario || !$usuario['asaas_api_key']) {
                return $this->errorResponse('Usuário não autorizado', 403);
            }
            
            $cobranca = $this->chamarApiAsaas(
                'GET',
                "/payments/{$payment_id}",
                [],
                $usuario
            );
            
            return [
                'sucesso' => true,
                'payment_id' => $cobranca['id'],
                'status' => $cobranca['status'],
                'valor' => $cobranca['value'],
                'vencimento' => $cobranca['dueDate'],
                'data_pagamento' => $cobranca['paymentDate'] ?? null,
                'metodo' => $cobranca['billingType'],
                'external_reference' => $cobranca['externalReference']
            ];
            
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents(), true);
            return $this->errorResponse(
                $response['errors'][0]['description'] ?? 'Erro ao consultar cobrança',
                $e->getCode()
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Erro: ' . $e->getMessage(), 500);
        }
    }

    /**
     * ========================================================================
     * ENDPOINT: GET /api/asaas/gerar-qrcode-pix (Mantido do original)
     * ========================================================================
     */
    public function actionGerarQrcodePix($payment_id, $usuario_id)
    {
        try {
            $usuario = $this->buscarUsuarioPorId($usuario_id);
            
            if (!$usuario) {
                return $this->errorResponse('Usuário não encontrado', 404);
            }
            
            $pixData = $this->chamarApiAsaas(
                'GET',
                "/payments/{$payment_id}/pixQrCode",
                [],
                $usuario
            );
            
            return [
                'sucesso' => true,
                'qrcode_id' => $pixData['id'] ?? null,
                'payload' => $pixData['payload'] ?? null,
                'encoded_image' => $pixData['encodedImage'] ?? null,
                'expiracao' => $pixData['expirationDate'] ?? null
            ];
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao gerar QR Code: ' . $e->getMessage(), 500);
        }
    }

    /**
     * ========================================================================
     * ENDPOINT: GET /api/asaas/listar-cobrancas (Mantido do original)
     * ========================================================================
     */
    public function actionListarCobrancas($usuario_id, $limit = 20, $offset = 0, $status = null)
    {
        try {
            if (!$this->validarUUID($usuario_id)) {
                return $this->errorResponse('ID de usuário inválido', 400);
            }
            
            $query = "
                SELECT 
                    id,
                    payment_id,
                    external_reference,
                    customer_asaas_id,
                    valor,
                    metodo_pagamento,
                    status,
                    status_asaas,
                    vencimento,
                    data_pagamento,
                    created_at,
                    ultima_atualizacao,
                    pedido_id
                FROM asaas_cobrancas
                WHERE usuario_id = :usuario_id::uuid 
            ";
            
            $params = [':usuario_id' => $usuario_id];
            
            if ($status) {
                $query .= " AND status_asaas = :status";
                $params[':status'] = $status;
            }
            
            $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $params[':limit'] = (int)$limit;
            $params[':offset'] = (int)$offset;
            
            $cobrancas = Yii::$app->db->createCommand($query, $params)->queryAll();
            
            $countQuery = "SELECT COUNT(*) FROM asaas_cobrancas WHERE usuario_id = :usuario_id::uuid";
            $countParams = [':usuario_id' => $usuario_id]; 
            if ($status) {
                $countQuery .= " AND status_asaas = :status";
                $countParams[':status'] = $status; 
            }
            
            $total = Yii::$app->db->createCommand($countQuery, $countParams)->queryScalar(); 
            
            return [
                'sucesso' => true,
                'total' => (int)$total,
                'limit' => (int)$limit,
                'offset' => (int)$offset,
                'cobrancas' => $cobrancas
            ];
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao listar cobranças: ' . $e->getMessage(), 500);
        }
    }

    // ========================================================================
    // MÉTODOS DE INTEGRAÇÃO COM ASAAS
    // ========================================================================

    /**
     * ========================================================================
     * ✅ FUNÇÃO CORRIGIDA: Substituído Guzzle por cURL (MANTENDO A ESTRUTURA DO SEU CÓDIGO)
     * ========================================================================
     * Chama API da Asaas (método genérico)
     */
    private function chamarApiAsaas($method, $endpoint, $data, $usuario)
    {
        $baseUrl = $usuario['asaas_sandbox'] 
            ? self::API_URL_SANDBOX 
            : self::API_URL_PRODUCTION;
        
        $url = $baseUrl . $endpoint;
        $apiKey = $usuario['asaas_api_key'];
        $method = strtoupper($method);

        // Log de debug
        Yii::info([
            'action' => 'chamar_api_asaas_curl', 
            'method' => $method,
            'endpoint' => $endpoint,
            'payload_enviado' => $data
        ], 'asaas');
        
        $ch = curl_init();
        
        // Configurar URL (adiciona query string para GET)
        if ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // Configurar método
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        // Configurar cabeçalhos (Exatamente como o proxy-asaas.php)
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'User-Agent: PWA-Catalogo/1.0 (cURL)', 
            'access_token: ' . $apiKey
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Configurar corpo (POST/PUT)
        if (!in_array($method, ['GET', 'DELETE']) && !empty($data)) {
            $jsonData = json_encode($data);
            if (json_last_error() !== JSON_ERROR_NONE) {
                 Yii::error('chamar_api_asaas_curl_falha_json_encode_body', 'asaas');
                 curl_close($ch);
                 throw new \Exception('Erro ao codificar corpo da requisição para JSON.');
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }
        
        // Configurações cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45); 
        curl_setopt($ch, CURLOPT_FAILONERROR, false); 

        // Executar
        $responseBody = curl_exec($ch);
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrorNo = curl_errno($ch);
        $curlErrorMsg = curl_error($ch);
        
        curl_close($ch);
        
        // Tratamento de Erro (Rede/cURL)
        if ($curlErrorNo) {
            Yii::error(['action' => 'chamar_api_asaas_curl_falha_rede', 'curl_error' => $curlErrorMsg], 'asaas');
            throw new ConnectException("cURL Error ({$curlErrorNo}): {$curlErrorMsg}", new Request($method, $url));
        }
        
        // Tratamento da Resposta (JSON)
        $decodedResponse = json_decode($responseBody, true);
        
        // Se falhou o decode (ex: HTML de login)
        if (json_last_error() !== JSON_ERROR_NONE) {
            Yii::error(['action' => 'chamar_api_asaas_falha_json_decode', 'http_status' => $httpStatusCode, 'raw_response_body' => substr($responseBody, 0, 500)], 'asaas');
            
            if ($httpStatusCode >= 400) {
                 throw new RequestException("Asaas returned non-JSON error (Status {$httpStatusCode})", new Request($method, $url), new GuzzleResponse($httpStatusCode, [], $responseBody));
            }
            
            return null; 
        }

        // Se for JSON mas for um erro (ex: 401, 404, 422)
        if ($httpStatusCode >= 400) {
             throw new RequestException("Asaas returned error (Status {$httpStatusCode})", new Request($method, $url), new GuzzleResponse($httpStatusCode, [], $responseBody));
        }

        // ✅ LOG: Ver resposta completa da Asaas
        Yii::info("[DEBUG] Resposta Asaas completa: " . json_encode($decodedResponse), 'asaas');

        // Sucesso!
        return $decodedResponse;
    }


    /**
     * Busca ou cria cliente na Asaas (Mantido do original)
     */
    private function buscarOuCriarClienteAsaas($dadosCliente, $usuario)
    {
        if (empty($dadosCliente)) {
            throw new \Exception('Dados do cliente são obrigatórios');
        }
        
        $cpfCnpj = preg_replace('/[^0-9]/', '', $dadosCliente['cpf'] ?? '');
        
        if (empty($cpfCnpj)) {
            throw new \Exception('CPF/CNPJ do cliente é obrigatório');
        }
        
        // Verificar se cliente já existe no banco local
        $clienteLocal = $this->buscarClienteAsaasPorCpf($cpfCnpj, $usuario['id']);
        
        if ($clienteLocal && $clienteLocal['customer_asaas_id']) {
            return $clienteLocal['customer_asaas_id'];
        }
        
        // Buscar na Asaas por CPF
        $clientes = null;
        try {
            $clientes = $this->chamarApiAsaas(
                'GET',
                '/customers',
                ['cpfCnpj' => $cpfCnpj],
                $usuario
            );
        } catch (RequestException $e) {
            if ($e->getCode() != 404) {
                Yii::error("Erro ao BUSCAR cliente Asaas: " . $e->getMessage(), 'asaas');
                throw $e;
            }
            Yii::info("Cliente {$cpfCnpj} não encontrado (404), será criado.", 'asaas');
        }
            
        if (is_array($clientes) && !empty($clientes['data'])) {
            $customerId = $clientes['data'][0]['id'];
            
            $this->salvarClienteAsaasNoBanco([
                'usuario_id' => $usuario['id'],
                'customer_asaas_id' => $customerId,
                'cpf_cnpj' => $cpfCnpj,
                'nome' => $dadosCliente['nome'] ?? '',
                'email' => $dadosCliente['email'] ?? ''
            ]);
            
            return $customerId;
        }
        
        // Criar novo cliente na Asaas
        $novoCliente = $this->chamarApiAsaas(
            'POST',
            '/customers',
            [
                'name' => $dadosCliente['nome'] ?? 'Cliente',
                'cpfCnpj' => $cpfCnpj,
                'email' => $dadosCliente['email'] ?? null,
                'phone' => preg_replace('/[^0-9]/', '', $dadosCliente['telefone'] ?? ''),
                'mobilePhone' => preg_replace('/[^0-9]/', '', $dadosCliente['telefone'] ?? ''),
                'postalCode' => preg_replace('/[^0-9]/', '', $dadosCliente['cep'] ?? ''),
                'address' => $dadosCliente['endereco'] ?? null,
                'addressNumber' => $dadosCliente['numero'] ?? null,
                'complement' => $dadosCliente['complemento'] ?? null,
                'province' => $dadosCliente['bairro'] ?? null,
                'city' => $dadosCliente['cidade'] ?? null,
                'state' => $dadosCliente['estado'] ?? null,
                'notificationDisabled' => false
            ],
            $usuario
        );
        
        if (!is_array($novoCliente) || !isset($novoCliente['id'])) {
            Yii::error('Resposta inesperada da Asaas ao CRIAR cliente: ' . json_encode($novoCliente), 'asaas');
            throw new \Exception('Falha ao criar novo cliente. Resposta inválida da Asaas.');
        }

        $this->salvarClienteAsaasNoBanco([
            'usuario_id' => $usuario['id'],
            'customer_asaas_id' => $novoCliente['id'],
            'cpf_cnpj' => $cpfCnpj,
            'nome' => $dadosCliente['nome'] ?? '',
            'email' => $dadosCliente['email'] ?? ''
        ]);
        
        return $novoCliente['id'];
    }

    /**
     * Processa eventos do webhook (Mantido do original)
     */
    private function processarEventoWebhook($event, $payment, $cobranca, $usuario)
    {
        switch ($event) {
            case 'PAYMENT_RECEIVED':
            case 'PAYMENT_CONFIRMED':
                return $this->processarPagamentoRecebido($payment, $cobranca, $usuario);
                
            case 'PAYMENT_OVERDUE':
                return $this->processarPagamentoVencido($payment, $cobranca);
                
            case 'PAYMENT_DELETED':
            case 'PAYMENT_REFUNDED':
                return $this->processarPagamentoCancelado($payment, $cobranca);
                
            default:
                Yii::info("Evento ignorado: {$event}", 'asaas');
                return ['processado' => false, 'motivo' => 'evento_nao_tratado'];
        }
    }

    /**
     * ✅ NOVO MÉTODO AUXILIAR: Cria o pedido em prest_vendas se ainda não existir
     */
    private function criarPedidoSeNecessario($payment, $cobranca, $usuario)
    {
        if (!empty($cobranca['pedido_id'])) {
            Yii::info("Pedido já existe ({$cobranca['pedido_id']}). Nenhuma ação necessária.", 'asaas');
            return $cobranca['pedido_id'];
        }
        
        $dadosOriginais = $cobranca['dados_request'];
        
        if (!isset($dadosOriginais['itens']) || !is_array($dadosOriginais['itens']) || empty($dadosOriginais['itens'])) {
             Yii::error("Dados originais dos itens não encontrados para a cobrança ID {$cobranca['id']}.", 'asaas');
             throw new \Exception("Dados dos itens inválidos ou ausentes para criar o pedido.");
        }

        // 1. Atualiza status localmente como pago
        $this->atualizarStatusCobranca($cobranca['id'], [
            'status' => 'QUITADA',
            'status_asaas' => $payment['status'],
            'valor_recebido' => $payment['value'] ?? null,
            'data_pagamento' => $payment['paymentDate'] ?? null,
            'dados_cobranca' => $payment
        ]);
        
        // 2. Obtém a forma de pagamento interna
        $formaPagamentoId = $this->obterFormaPagamentoAsaas($cobranca['usuario_id']);
        
        // 3. Cria o Pedido
        $pedidoId = $this->criarPedido([
            'usuario_id' => $cobranca['usuario_id'],
            'cliente_id' => $cobranca['cliente_id'],
            'forma_pagamento_id' => $formaPagamentoId,
            'data_venda' => $payment['paymentDate'] ?? date('Y-m-d H:i:s'),
            'valor_total' => $payment['value'],
            'observacoes' => "Pagamento via Asaas (Confirmação)\nID: {$payment['id']}\nMétodo: {$payment['billingType']}\nRef: {$payment['externalReference']}",
            'status' => 'QUITADA',
            'itens' => $dadosOriginais['itens']
        ]);
        
        // 4. Vincula a cobrança ao novo pedido
        $this->vincularPedidoCobranca($cobranca['id'], $pedidoId);
        
        Yii::info("✅ Pedido criado via auxiliar: {$pedidoId} (Ref: {$payment['externalReference']})", 'asaas');
        
        return $pedidoId;
    }


    /**
     * Processa pagamento recebido - (Mantido do original, mas agora chama o método auxiliar)
     */
    private function processarPagamentoRecebido($payment, $cobranca, $usuario)
    {
        try {
            Yii::info("Pagamento recebido via Webhook. Tentando criar/vincular pedido...", 'asaas');
            $pedidoId = $this->criarPedidoSeNecessario($payment, $cobranca, $usuario);

            return ['processado' => true, 'pedido_id' => $pedidoId];
            
        } catch (\Exception $e) {
            Yii::error("Erro ao processar pagamento recebido: {$e->getMessage()}", 'asaas');
            throw $e;
        }
    }
    
    /**
     * Processa pagamento vencido (Mantido do original)
     */
    private function processarPagamentoVencido($payment, $cobranca)
    {
        Yii::info("⏰ Pagamento vencido: {$payment['id']}", 'asaas');
        return ['processado' => true, 'status' => 'vencido'];
    }

    /**
     * Processa pagamento cancelado (Mantido do original)
     */
    private function processarPagamentoCancelado($payment, $cobranca)
    {
        Yii::info("❌ Pagamento cancelado: {$payment['id']}", 'asaas');
        
        $this->cancelarPedido(
            $payment['externalReference'], 
            'Pagamento cancelado/estornado pela Asaas',
            $cobranca['pedido_id']
        );
        
        return ['processado' => true, 'status' => 'CANCELADA'];
    }

    // ========================================================================
    // MÉTODOS AUXILIARES ESPECÍFICOS DA ASAAS (Mantidos do original)
    // ========================================================================

    private function prepararDadosCartao($cartao)
    {
        return [
            'holderName' => $cartao['titular'],
            'number' => preg_replace('/[^0-9]/', '', $cartao['numero']),
            'expiryMonth' => $cartao['mes_validade'],
            'expiryYear' => $cartao['ano_validade'],
            'ccv' => $cartao['cvv']
        ];
    }

    private function prepararDadosTitular($cliente)
    {
        return [
            'name' => $cliente['nome'],
            'email' => $cliente['email'],
            'cpfCnpj' => preg_replace('/[^0-9]/', '', $cliente['cpf']),
            'postalCode' => preg_replace('/[^0-9]/', '', $cliente['cep'] ?? ''),
            'addressNumber' => $cliente['numero'] ?? '',
            'phone' => preg_replace('/[^0-9]/', '', $cliente['telefone'] ?? '')
        ];
    }

    // ========================================================================
    // MÉTODOS DE BANCO DE DADOS (POSTGRES) - ASAAS (Mantidos do original)
    // ========================================================================

    private function salvarCobrancaNoBanco($dados)
    {
        $sql = "
            INSERT INTO asaas_cobrancas (
                payment_id,
                external_reference,
                usuario_id,
                cliente_id,
                customer_asaas_id,
                valor,
                metodo_pagamento,
                status,
                status_asaas,
                vencimento,
                dados_request,
                dados_cobranca,
                ambiente,
                created_at,
                ultima_atualizacao,
                pedido_id
            ) VALUES (
                :payment_id,
                :external_reference,
                :usuario_id::uuid,
                :cliente_id::uuid,
                :customer_asaas_id,
                :valor,
                :metodo_pagamento,
                :status,
                :status_asaas,
                :vencimento,
                :dados_request::jsonb,
                :dados_cobranca::jsonb,
                :ambiente,
                NOW(),
                NOW(),
                :pedido_id::uuid
            )
            RETURNING id
        ";
        
        return Yii::$app->db->createCommand($sql, [
            ':payment_id' => $dados['payment_id'],
            ':external_reference' => $dados['external_reference'],
            ':usuario_id' => $dados['usuario_id'],
            ':cliente_id' => $dados['cliente_id'],
            ':customer_asaas_id' => $dados['customer_asaas_id'],
            ':valor' => $dados['valor'],
            ':metodo_pagamento' => $dados['metodo_pagamento'],
            ':status' => $dados['status'],
            ':status_asaas' => $dados['status'],
            ':vencimento' => $dados['vencimento'],
            ':dados_request' => json_encode($dados['dados_request']),
            ':dados_cobranca' => json_encode($dados['dados_cobranca']),
            ':ambiente' => $dados['ambiente'],
            ':pedido_id' => $dados['pedido_id']
        ])->queryScalar();
    }
    
    private function vincularPedidoCobranca($cobrancaId, $pedidoId)
    {
        $sql = "
            UPDATE asaas_cobrancas
            SET pedido_id = :pedido_id::uuid
            WHERE id = :id
        ";
        
        Yii::$app->db->createCommand($sql, [
            ':pedido_id' => $pedidoId,
            ':id' => $cobrancaId
        ])->execute();
    }


    private function buscarCobrancaPorPaymentId($paymentId)
    {
        $sql = "
            SELECT *,
                   dados_request::text as dados_request,
                   dados_cobranca::text as dados_cobranca
            FROM asaas_cobrancas
            WHERE payment_id = :payment_id
            LIMIT 1
        ";
        
        $result = Yii::$app->db->createCommand($sql, [
            ':payment_id' => $paymentId
        ])->queryOne();
        
        if ($result) {
            $result['dados_request'] = json_decode($result['dados_request'], true);
            $result['dados_cobranca'] = json_decode($result['dados_cobranca'], true);
        }
        
        return $result;
    }

    private function atualizarStatusCobranca($id, $dados)
    {
        $sql = "
            UPDATE asaas_cobrancas
            SET 
                status = :status,
                status_asaas = :status_asaas,
                valor_recebido = :valor_recebido,
                data_pagamento = :data_pagamento,
                dados_cobranca = :dados_cobranca::jsonb,
                ultima_atualizacao = NOW()
            WHERE id = :id
        ";
        
        Yii::$app->db->createCommand($sql, [
            ':id' => $id,
            ':status' => $dados['status'],
            ':status_asaas' => $dados['status_asaas'],
            ':valor_recebido' => $dados['valor_recebido'],
            ':data_pagamento' => $dados['data_pagamento'],
            ':dados_cobranca' => json_encode($dados['dados_cobranca'])
        ])->execute();
    }

    private function salvarClienteAsaasNoBanco($dados)
    {
        $sql = "
            INSERT INTO asaas_clientes (
                usuario_id,
                customer_asaas_id,
                cpf_cnpj,
                nome,
                email,
                created_at
            ) VALUES (
                :usuario_id::uuid,
                :customer_asaas_id,
                :cpf_cnpj,
                :nome,
                :email,
                NOW()
            )
            ON CONFLICT (usuario_id, cpf_cnpj) 
            DO UPDATE SET 
                customer_asaas_id = EXCLUDED.customer_asaas_id,
                nome = EXCLUDED.nome,
                email = EXCLUDED.email
        ";
        
        Yii::$app->db->createCommand($sql, [
            ':usuario_id' => $dados['usuario_id'],
            ':customer_asaas_id' => $dados['customer_asaas_id'],
            ':cpf_cnpj' => $dados['cpf_cnpj'],
            ':nome' => $dados['nome'],
            ':email' => $dados['email']
        ])->execute();
    }

    private function buscarClienteAsaasPorCpf($cpf, $usuarioId)
    {
        $sql = "
            SELECT * FROM asaas_clientes
            WHERE cpf_cnpj = :cpf
            AND usuario_id = :usuario_id::uuid
            LIMIT 1
        ";
        
        return Yii::$app->db->createCommand($sql, [
            ':cpf' => $cpf,
            ':usuario_id' => $usuarioId
        ])->queryOne();
    }

    // private function obterFormaPagamentoAsaas($usuarioId)
    // {
    //     $sql = "
    //         SELECT id FROM forma_pagamento
    //         WHERE usuario_id = :usuario_id::uuid
    //         AND LOWER(nome) LIKE '%asaas%'
    //         LIMIT 1
    //     ";
        
    //     $id = Yii::$app->db->createCommand($sql, [
    //         ':usuario_id' => $usuarioId
    //     ])->queryScalar();
        
    //     if (!$id) {
    //         $sql = "
    //             INSERT INTO forma_pagamento (
    //                 usuario_id,
    //                 nome,
    //                 ativo,
    //                 created_at
    //             ) VALUES (
    //                 :usuario_id::uuid,
    //                 'Asaas',
    //                 true,
    //                 NOW()
    //             )
    //             RETURNING id
    //         ";
            
    //         $id = Yii::$app->db->createCommand($sql, [
    //             ':usuario_id' => $usuarioId
    //         ])->queryScalar();
    //     }
        
    //     return $id;
    // }

    private function obterFormaPagamentoAsaas($usuarioId)
    {
        $nomeTabelaFormaPagamento = 'prest_formas_pagamento';

        $sql = "
            SELECT id FROM {$nomeTabelaFormaPagamento}
            WHERE usuario_id = :usuario_id::uuid
            AND LOWER(nome) LIKE '%asaas%'
            LIMIT 1
        ";
        
        $id = Yii::$app->db->createCommand($sql, [
            ':usuario_id' => $usuarioId
        ])->queryScalar();
        
        if (!$id) {
            Yii::warning("Forma de pagamento 'Asaas' não encontrada para usuario_id {$usuarioId}. Tentando criar...", 'asaas');
            
            // ✅ CORREÇÃO: Removido created_at, usando apenas data_criacao (que tem default NOW())
            $sql = "
                INSERT INTO {$nomeTabelaFormaPagamento} (
                    usuario_id,
                    nome,
                    tipo,
                    aceita_parcelamento,
                    ativo
                ) VALUES (
                    :usuario_id::uuid,
                    'Asaas',
                    'OUTROS',
                    false,
                    true
                )
                RETURNING id
            ";
            
            try {
                $id = Yii::$app->db->createCommand($sql, [
                    ':usuario_id' => $usuarioId
                ])->queryScalar();
                Yii::info("Forma de pagamento 'Asaas' criada com ID: {$id}", 'asaas');
            } catch (\Exception $e) {
                 Yii::error("Falha ao criar forma de pagamento 'Asaas': " . $e->getMessage(), 'asaas');
                 throw new \yii\db\Exception("Erro crítico: Não foi possível encontrar ou criar a forma de pagamento 'Asaas'. Verifique se a tabela '{$nomeTabelaFormaPagamento}' existe e tem as colunas corretas (incluindo 'tipo' e 'aceita_parcelamento').", [], $e->getCode(), $e);
            }
        }
        
        return $id;
    }


    private function validarRequestCobranca($request)
    {
        if (!isset($request['usuario_id'])) {
            throw new \Exception('Campo usuario_id é obrigatório');
        }
        if (!isset($request['valor']) || $request['valor'] <= 0) {
            throw new \Exception('Valor deve ser maior que zero');
        }
        if (!isset($request['metodo_pagamento'])) {
            throw new \Exception('Método de pagamento é obrigatório');
        }
        $metodosValidos = ['PIX', 'BOLETO', 'CREDIT_CARD'];
        if (!in_array($request['metodo_pagamento'], $metodosValidos)) {
            throw new \Exception('Método de pagamento inválido');
        }
        if (!isset($request['cliente']) || empty($request['cliente'])) {
            throw new \Exception('Dados do cliente são obrigatórios');
        }
    }

    // ========================================================================
    // MÉTODOS DE BANCO DE DADOS (PADRONIZADOS) (Mantidos do original)
    // ========================================================================
    
    private function buscarUsuarioComValidacao($usuarioId)
    {
        if (!$this->validarUUID($usuarioId)) {
            throw new \Exception('ID de usuário inválido');
        }
        
        $usuario = $this->buscarUsuarioPorId($usuarioId);
        
        if (!$usuario) {
            throw new \Exception('Usuário não encontrado');
        }
        if (!$usuario['api_de_pagamento']) {
            throw new \Exception('API de pagamento não habilitada');
        }
        if (empty($usuario['asaas_api_key'])) {
            throw new \Exception('API Key da Asaas não configurada');
        }
        
        return $usuario;
    }
    
    private function buscarUsuarioPorId($usuarioId)
    {
        $sql = "
            SELECT 
                id,
                nome, 
                api_de_pagamento,
                asaas_api_key,
                asaas_sandbox,
                catalogo_path
            FROM prest_usuarios
            WHERE id = :id::uuid
            LIMIT 1
        ";
        
        return Yii::$app->db->createCommand($sql, [':id' => $usuarioId])->queryOne();
    }
    
    private function gerarExternalReference($usuarioId)
    {
        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        $userShort = substr(str_replace('-', '', $usuarioId), 0, 8);
        return "ped_asaas_{$userShort}_{$timestamp}_{random}";
    }
    
    private function validarUUID($uuid)
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }
    
    private function errorResponse($message, $code = 400)
    {
        Yii::$app->response->statusCode = $code;
        return [
            'sucesso' => false,
            'erro' => $message,
            'codigo' => $code,
            'timestamp' => date('c')
        ];
    }
    
    /**
     * Cria Pedido (Mantido do original, mas com saneamento extra de itens)
     */
    private function criarPedido($dados)
    {
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            // ✅ CORREÇÃO SQL: Trocado 'created_at' e 'updated_at' pelos nomes corretos da tabela
            $sqlVenda = "
                INSERT INTO prest_vendas (
                    usuario_id,
                    cliente_id,
                    forma_pagamento_id,
                    data_venda,
                    valor_total,
                    observacoes,
                    status_venda_codigo,
                    data_criacao,
                    data_atualizacao
                ) VALUES (
                    :usuario_id::uuid,
                    :cliente_id::uuid,
                    :forma_pagamento_id::uuid,
                    :data_venda,
                    :valor_total,
                    :observacoes,
                    :status_venda_codigo,
                    NOW(),
                    NOW()
                )
                RETURNING id
            ";
            
            $vendaId = Yii::$app->db->createCommand($sqlVenda, [
                ':usuario_id' => $dados['usuario_id'],
                ':cliente_id' => $dados['cliente_id'],
                ':forma_pagamento_id' => $dados['forma_pagamento_id'],
                ':data_venda' => $dados['data_venda'] ?? date('Y-m-d H:i:s'),
                ':valor_total' => $dados['valor_total'],
                ':observacoes' => $dados['observacoes'],
                ':status_venda_codigo' => $dados['status']
            ])->queryScalar();
            
            if (!isset($dados['itens']) || !is_array($dados['itens'])) {
                 Yii::error("CriarPedido: Array de itens inválido ou ausente. Venda ID: {$vendaId}.", 'asaas');
                 throw new \Exception("Dados dos itens inválidos para criar o pedido.");
            }

            foreach ($dados['itens'] as $item) {
                 // Saneamento e validação dos dados do item
                $produtoId = $item['produto_id'] ?? ($item['id'] ?? null); 
                $quantidade = (isset($item['quantidade']) && is_numeric($item['quantidade']) && $item['quantidade'] > 0) ? $item['quantidade'] : 1;
                $precoUnitario = (isset($item['preco_unitario']) && is_numeric($item['preco_unitario']) && $item['preco_unitario'] >= 0) ? $item['preco_unitario'] : 0;
                
                if (!$produtoId || !$this->validarUUID($produtoId)) {
                    Yii::warning("CriarPedido: Item com produto_id inválido ou ausente ({$produtoId}). Item ignorado.", 'asaas');
                    continue; 
                }
                
                // ✅ CORREÇÃO: Nome correto da tabela é prest_venda_itens (não prest_itens_venda)
                // ✅ CORREÇÃO: Campos corretos: preco_unitario_venda e valor_total_item (não preco_unitario, subtotal, created_at)
                $sqlItem = "
                    INSERT INTO prest_venda_itens (
                        venda_id,
                        produto_id,
                        quantidade,
                        preco_unitario_venda,
                        valor_total_item
                    ) VALUES (
                        :venda_id::uuid,
                        :produto_id::uuid,
                        :quantidade,
                        :preco_unitario_venda,
                        :valor_total_item
                    )
                ";
                
                $valorTotalItem = $quantidade * $precoUnitario;
                
                Yii::$app->db->createCommand($sqlItem, [
                    ':venda_id' => $vendaId,
                    ':produto_id' => $produtoId,
                    ':quantidade' => $quantidade,
                    ':preco_unitario_venda' => $precoUnitario,
                    ':valor_total_item' => $valorTotalItem
                ])->execute();
            }
            
            $transaction->commit();
            return $vendaId;
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("Rollback da transação para criação do pedido. Erro: " . $e->getMessage(), 'asaas');
            throw $e;
        }
    }
  
    private function cancelarPedido($externalReference, $motivo, $pedidoId = null)
    {
        $idParaCancelar = $pedidoId;

        if (!$idParaCancelar) {
            $sqlBusca = "
                SELECT pedido_id FROM asaas_cobrancas
                WHERE external_reference = :external_ref
                AND pedido_id IS NOT NULL
                LIMIT 1
            ";
            $idParaCancelar = Yii::$app->db->createCommand($sqlBusca, [
                ':external_ref' => $externalReference
            ])->queryScalar();
        }

        if ($idParaCancelar) {
            // ✅ CORREÇÃO SQL: Trocado 'updated_at' pelo nome correto da tabela
            $sql = "
                UPDATE prest_vendas
                SET 
                    status_venda_codigo = 'CANCELADA',
                    observacoes = CONCAT(COALESCE(observacoes, ''), E'\n\n[ASAAS Webhook] ', :motivo),
                    data_atualizacao = NOW()
                WHERE id = :id::uuid
            ";
            
            Yii::$app->db->createCommand($sql, [
                ':id' => $idParaCancelar,
                ':motivo' => $motivo
            ])->execute();
        }
    }
    
}