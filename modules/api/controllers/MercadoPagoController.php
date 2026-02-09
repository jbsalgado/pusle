<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\db\Expression;
use yii\db\JsonExpression;
use yii\db\Exception as DbException;
use yii\helpers\Html;
use GuzzleHttp\Client;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\StatusVenda;
use app\modules\vendas\models\Produto;
use app\modules\caixa\helpers\CaixaHelper;

// SDK 3.7 - Importações
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;

class MercadoPagoController extends Controller
{
    /**
     * Desabilita verificação CSRF para APIs
     */
    public $enableCsrfValidation = false;

    /**
     * Configura formato de resposta como JSON
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_HTML;
        return $behaviors;
    }

    /**
     * ========================================================================
     * ENDPOINT: GET /api/mercado-pago/connect-url
     * Gera a URL de autorização OAuth para o vendedor/tenant.
     * ========================================================================
     */
    public function actionConnectUrl()
    {
        $tenantId = Yii::$app->request->get('tenant_id') ?? Yii::$app->user->id;
        $config = $this->getMpAppConfig();

        if (empty($config['app_id']) || empty($config['client_secret'])) {
            return $this->errorResponse('Credenciais do Mercado Pago não configuradas. Defina MP_APP_ID e MP_CLIENT_SECRET no ambiente.', 500);
        }

        if (!$tenantId || !$this->validarUUID($tenantId)) {
            return $this->errorResponse('tenant_id inválido para gerar URL de conexão.');
        }

        $redirectUri = $config['redirect_uri'] ?? $this->buildDefaultRedirectUri();

        $rawState = $tenantId . ':' . Yii::$app->security->generateRandomString(12);
        $state = Yii::$app->security->hashData($rawState, $config['client_secret']);
        Yii::$app->session->set('mp_oauth_state', $state);
        Yii::$app->session->set('mp_oauth_raw', $rawState);

        $authUrl = sprintf(
            'https://auth.mercadopago.com/authorization?response_type=code&client_id=%s&redirect_uri=%s&state=%s',
            urlencode($config['app_id']),
            urlencode($redirectUri),
            urlencode($state)
        );

        return [
            'sucesso' => true,
            'url' => $authUrl,
            'tenant_id' => $tenantId,
            'redirect_uri' => $redirectUri,
        ];
    }

    /**
     * ========================================================================
     * ENDPOINT: GET /api/mercado-pago/oauth-callback
     * Callback do OAuth: troca o code por tokens e salva no tenant.
     * ========================================================================
     */
    public function actionOauthCallback()
    {
        $code = Yii::$app->request->get('code');
        $state = Yii::$app->request->get('state');
        $config = $this->getMpAppConfig();

        if (empty($code) || empty($state)) {
            return $this->renderContent('<h3>Conexão Mercado Pago falhou: parâmetros ausentes.</h3>');
        }

        $tenantId = null;
        $expectedState = Yii::$app->session->get('mp_oauth_state');
        $rawState = Yii::$app->session->get('mp_oauth_raw');

        if ($expectedState && hash_equals($expectedState, $state) && $rawState) {
            $tenantId = explode(':', $rawState)[0] ?? null;
        } else {
            $tenantId = Yii::$app->request->get('tenant_id');
        }

        if (!$tenantId || !$this->validarUUID($tenantId)) {
            Yii::error([
                'action' => 'oauth_callback',
                'error' => 'tenant_id inválido',
                'state' => $state
            ], 'mercadopago');
            return $this->renderContent('<h3>Não foi possível identificar a loja para salvar o token.</h3>');
        }

        try {
            $redirectUri = $config['redirect_uri'] ?? $this->buildDefaultRedirectUri();
            $client = new Client(['base_uri' => 'https://api.mercadopago.com']);

            $response = $client->post('/oauth/token', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => $config['app_id'],
                    'client_secret' => $config['client_secret'],
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                ]
            ]);

            $payload = json_decode((string)$response->getBody(), true);
            $this->salvarTokensOauth($tenantId, $payload);

            return $this->renderContent('<h3>Conta Mercado Pago conectada com sucesso. Você já pode fechar esta janela.</h3>');
        } catch (\Throwable $e) {
            Yii::error([
                'action' => 'oauth_callback',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tenant_id' => $tenantId,
            ], 'mercadopago');

            return $this->renderContent('<h3>Erro ao conectar Mercado Pago: ' . Html::encode($e->getMessage()) . '</h3>');
        }
    }

    /**
     * ========================================================================
     * ENDPOINT: POST /api/mercado-pago/pix-split
     * Cria um pagamento PIX com split (application_fee 0,5%).
     * ========================================================================
     */
    public function actionCriarPagamentoPixSplit()
    {
        try {
            $request = Yii::$app->request->post();

            $tenantId = $request['tenant_id'] ?? null;
            $orderId = $request['order_id'] ?? null;
            $amount = isset($request['amount']) ? (float)$request['amount'] : null;

            if (!$tenantId || !$this->validarUUID($tenantId)) {
                return $this->errorResponse('tenant_id é obrigatório.');
            }

            if (!$orderId || !$this->validarUUID($orderId)) {
                return $this->errorResponse('order_id é obrigatório.');
            }

            if ($amount === null || $amount <= 0) {
                return $this->errorResponse('amount deve ser maior que zero.');
            }

            $usuario = $this->buscarUsuarioPorId($tenantId);
            if (!$usuario) {
                return $this->errorResponse('Loja não encontrada.');
            }

            $accessToken = $this->obterTokenVendedor($usuario);
            if (!$accessToken) {
                return $this->errorResponse('Loja não conectada ao Mercado Pago via OAuth.');
            }

            $applicationFee = $this->calcularApplicationFee($amount);
            if ($applicationFee > $amount) {
                return $this->errorResponse('application_fee não pode ser maior que o valor da transação.');
            }

            MercadoPagoConfig::setAccessToken($accessToken);

            $paymentData = [
                'transaction_amount' => $amount,
                'description' => $request['description'] ?? 'Pedido ' . $orderId,
                'payment_method_id' => 'pix',
                'notification_url' => $this->getBaseUrl() . '/pulse/web/index.php/api/mercado-pago/webhook',
                'external_reference' => $orderId,
                'metadata' => [
                    'tenant_id' => $tenantId,
                    'order_id' => $orderId,
                ],
                'application_fee' => $applicationFee,
            ];

            if (!empty($request['payer']) && is_array($request['payer'])) {
                $paymentData['payer'] = $request['payer'];
            } elseif (!empty($request['cliente']) && is_array($request['cliente'])) {
                $paymentData['payer'] = $this->montarDadosPagador($request['cliente']);
            }

            $client = new PaymentClient();
            $payment = $client->create($paymentData);

            Yii::info([
                'action' => 'pix_split_criado',
                'payment_id' => $payment->id,
                'tenant_id' => $tenantId,
                'order_id' => $orderId,
                'application_fee' => $applicationFee,
                'amount' => $amount,
            ], 'mercadopago');

            return [
                'sucesso' => true,
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'qr_code' => $payment->point_of_interaction->transaction_data->qr_code ?? null,
                'qr_code_base64' => $payment->point_of_interaction->transaction_data->qr_code_base64 ?? null,
                'point_of_interaction' => $payment->point_of_interaction ?? null,
                'application_fee' => $applicationFee,
            ];
        } catch (MPApiException $e) {
            Yii::error([
                'action' => 'pix_split_criado',
                'error' => $e->getMessage(),
                'api_response' => $e->getApiResponse(),
            ], 'mercadopago');
            return $this->errorResponse('Erro no Mercado Pago: ' . $e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            Yii::error([
                'action' => 'pix_split_criado',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 'mercadopago');
            return $this->errorResponse('Erro interno ao criar pagamento PIX.', 500);
        }
    }

    /**
     * ========================================================================
     * ENDPOINT: POST /api/mercado-pago/criar-preferencia
     * ========================================================================
     */
    public function actionCriarPreferencia()
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $request = Yii::$app->request->post();

            // 1️⃣ VALIDAÇÃO DE DADOS
            $this->validarRequestPreferencia($request);

            // 2️⃣ BUSCAR USUÁRIO
            $usuario = $this->buscarUsuarioPorId($request['usuario_id']);

            if (!$usuario) {
                throw new \Exception('Usuário não encontrado');
            }

            // ✅ VERIFICAR SE API DE PAGAMENTO ESTÁ HABILITADA
            if (!$usuario['api_de_pagamento']) {
                $transaction->rollBack();
                Yii::info([
                    'action' => 'api_pagamento_desabilitada',
                    'usuario_id' => $usuario['id']
                ], 'mercadopago');

                return [
                    'sucesso' => false,
                    'motivo' => 'api_desabilitada',
                    'mensagem' => 'API de pagamento não está habilitada para esta loja',
                    'continuar_fluxo_normal' => true
                ];
            }

            // ✅ VERIFICAR SE MERCADO PAGO ESTÁ CONFIGURADO
            if (empty($usuario['mercadopago_access_token'])) {
                $transaction->rollBack();
                Yii::info([
                    'action' => 'mercadopago_nao_configurado',
                    'usuario_id' => $usuario['id']
                ], 'mercadopago');

                return [
                    'sucesso' => false,
                    'motivo' => 'mercadopago_nao_configurado',
                    'mensagem' => 'Mercado Pago não está configurado para esta loja',
                    'continuar_fluxo_normal' => true
                ];
            }

            // 3️⃣ CONFIGURAR SDK 3.7
            MercadoPagoConfig::setAccessToken($usuario['mercadopago_access_token']);

            // Configurar ambiente (sandbox ou produção)
            if ($usuario['mercadopago_sandbox']) {
                MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
            }

            Yii::info([
                'action' => 'configuracao_sdk',
                'usuario_id' => $usuario['id'],
                'ambiente' => $usuario['mercadopago_sandbox'] ? 'SANDBOX' : 'PRODUÇÃO'
            ], 'mercadopago');

            // 4️⃣ PREPARAR ITENS DA PREFERÊNCIA
            $items = [];
            $valorTotal = 0;

            foreach ($request['itens'] as $item) {
                $precoUnit = floatval($item['preco_unitario']);
                $quantidade = intval($item['quantidade']);
                $subtotal = $precoUnit * $quantidade;
                $valorTotal += $subtotal;

                $items[] = [
                    "title" => mb_substr($item['nome'] ?? 'Produto', 0, 256), // Limite do MP
                    "description" => isset($item['descricao']) ? mb_substr($item['descricao'], 0, 256) : null,
                    "quantity" => $quantidade,
                    "unit_price" => $precoUnit,
                    "currency_id" => "BRL"
                ];
            }

            // Calcular Fee (Split)
            $marketplaceFee = $this->calcularApplicationFee($valorTotal);

            // 5️⃣ GERAR REFERÊNCIA ÚNICA USANDO POSTGRES
            $externalReference = $this->gerarExternalReference($usuario['id']);

            // 6️⃣ CONFIGURAR URLs DE RETORNO
            $baseUrl = $this->getBaseUrl();
            // Garante que não tenha barra dupla
            $baseUrl = rtrim($baseUrl, '/');

            // Define o caminho do catálogo (catalogo ou nome da loja)
            $catalogoPath = $usuario['catalogo_path'] ?? 'catalogo';

            // 7️⃣ MONTAR PAYLOAD DA PREFERÊNCIA
            $statementDescriptor = isset($usuario['nome']) && !empty($usuario['nome'])
                ? mb_substr($usuario['nome'], 0, 22)
                : "Loja Online";

            $preferenceData = [
                "items" => $items,
                "payer" => $this->montarDadosPagador($request['cliente'] ?? [], $usuario),
                "back_urls" => [
                    "success" => "{$baseUrl}/{$catalogoPath}/payment-success.html",
                    "failure" => "{$baseUrl}/{$catalogoPath}/payment-failure.html",
                    "pending" => "{$baseUrl}/{$catalogoPath}/payment-pending.html"
                ],
                "auto_return" => "approved", // Força o retorno automático se aprovado
                "external_reference" => $externalReference,
                "statement_descriptor" => $statementDescriptor,
                "notification_url" => "{$baseUrl}/index.php/api/mercado-pago/webhook",
                "marketplace_fee" => $marketplaceFee, // ✅ ADICIONADO: Split Fee
                "expires" => true,
                "expiration_date_from" => date('c'),
                "expiration_date_to" => date('c', strtotime('+24 hours')),
                "metadata" => [
                    "usuario_id" => $usuario['id'],
                    "cliente_id" => $request['cliente_id'] ?? null,
                    "origem" => "pwa_catalogo"
                ]
            ];

            // 8️⃣ CRIAR PREFERÊNCIA
            // ✅ WORKAROUND: Remover city/city_name do payload (lógica do pulse-new)
            $cityBackup = null;
            if (isset($preferenceData['payer']['address']['city'])) {
                $cityBackup = $preferenceData['payer']['address']['city'];
                unset($preferenceData['payer']['address']['city']);
            }
            if (isset($preferenceData['payer']['address']['city_name'])) {
                if ($cityBackup === null) {
                    $cityBackup = $preferenceData['payer']['address']['city_name'];
                }
                unset($preferenceData['payer']['address']['city_name']);
            }

            // Usar Guzzle diretamente para evitar problema de deserialização do SDK
            $accessToken = $usuario['mercadopago_access_token'];
            $baseUri = 'https://api.mercadopago.com';

            try {
                $httpClient = new Client(['base_uri' => $baseUri]);
                $response = $httpClient->post('/checkout/preferences', [
                    'headers' => [
                        'Authorization' => "Bearer {$accessToken}",
                        'Content-Type' => 'application/json',
                        'X-Idempotency-Key' => $externalReference
                    ],
                    'json' => $preferenceData,
                    'http_errors' => false
                ]);

                $statusCode = $response->getStatusCode();
                $responseBody = json_decode($response->getBody()->getContents(), true);

                if ($statusCode !== 201 && $statusCode !== 200) {
                    // Error handling adapted from pulse-new
                    $errorMessage = $responseBody['message'] ?? 'Erro desconhecido';
                    if (isset($responseBody['cause'])) {
                        if (is_array($responseBody['cause'])) {
                            $causes = array_map(function ($cause) {
                                return $cause['description'] ?? $cause['code'] ?? '';
                            }, $responseBody['cause']);
                            $errorMessage .= ': ' . implode(', ', $causes);
                        } else {
                            $errorMessage .= ': ' . json_encode($responseBody['cause']);
                        }
                    }
                    throw new \Exception("Erro ao criar preferência: {$errorMessage}", $statusCode);
                }

                // Criar objeto Preference manualmente
                $preference = new \stdClass();
                $preference->id = $responseBody['id'] ?? null;
                $preference->init_point = $responseBody['init_point'] ?? null;
                $preference->sandbox_init_point = $responseBody['sandbox_init_point'] ?? null;

                Yii::info([
                    'action' => 'preferencia_criada_via_guzzle',
                    'preference_id' => $preference->id,
                    'city_omitido' => $cityBackup !== null,
                    'motivo' => 'Workaround para erro de deserialização do SDK (city)'
                ], 'mercadopago');
            } catch (GuzzleException $e) {
                Yii::error([
                    'action' => 'erro_guzzle_criar_preferencia',
                    'error' => $e->getMessage()
                ], 'mercadopago');
                throw new \Exception('Erro ao criar preferência: ' . $e->getMessage(), 500);
            }

            Yii::info([
                'action' => 'preferencia_criada',
                'preference_id' => $preference->id,
                'external_reference' => $externalReference,
                'valor_total' => $valorTotal,
                'marketplace_fee' => $marketplaceFee
            ], 'mercadopago');

            // 9️⃣ SALVAR NO POSTGRES PARA RASTREAMENTO
            $request['ambiente'] = $usuario['mercadopago_sandbox'] ? 'sandbox' : 'producao';

            $preferenciaId = $this->salvarPreferenciaNoBanco([
                'preference_id' => $preference->id,
                'external_reference' => $externalReference,
                'usuario_id' => $usuario['id'],
                'cliente_id' => $request['cliente_id'] ?? null,
                'valor_total' => $valorTotal,
                'status' => 'pending',
                'dados_request' => $request,
                'ambiente' => $request['ambiente']
            ]);

            $transaction->commit();

            // 🔟 RETORNAR DADOS
            return [
                'sucesso' => true,
                'preference_id' => $preference->id,
                'init_point' => $preference->init_point,
                'sandbox_init_point' => $preference->sandbox_init_point,
                'external_reference' => $externalReference,
                'valor_total' => $valorTotal,
                'preferencia_local_id' => $preferenciaId,
                'marketplace_fee' => $marketplaceFee
            ];
        } catch (MPApiException $e) {
            $transaction->rollBack();
            Yii::error([
                'action' => 'erro_criar_preferencia',
                'error' => $e->getMessage(),
                'api_response' => $e->getApiResponse(),
                'status_code' => $e->getStatusCode()
            ], 'mercadopago');
            return $this->errorResponse('Erro na API do Mercado Pago: ' . $e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error([
                'action' => 'erro_criar_preferencia',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'mercadopago');
            return $this->errorResponse('Erro interno: ' . $e->getMessage(), 500);
        }
    }

    /**
     * ========================================================================
     * ENDPOINT: POST /api/mercado-pago/webhook
     * ========================================================================
     */
    public function actionWebhook()
    {
        try {
            // Obter dados da requisição
            $body = file_get_contents('php://input');
            $data = json_decode($body, true);

            Yii::info([
                'action' => 'webhook_recebido',
                'data' => $data,
                'headers' => getallheaders()
            ], 'mercadopago');

            // Validar tipo de notificação
            if (!isset($data['type']) || $data['type'] !== 'payment') {
                Yii::info('Notificação ignorada: tipo diferente de payment', 'mercadopago');
                return ['status' => 'ok', 'message' => 'Tipo de notificação não processado'];
            }

            // Obter ID do pagamento
            $paymentId = $data['data']['id'] ?? null;

            if (!$paymentId) {
                throw new \Exception('ID do pagamento não informado');
            }

            // Buscar dados do pagamento na API do MP (usando token do vendedor via OAuth)
            $pagamentoMP = $this->consultarPagamentoMarketplace($paymentId, $data);

            if (!$pagamentoMP) {
                throw new \Exception('Pagamento não encontrado no Mercado Pago');
            }

            // Processar notificação de pagamento
            $this->processarNotificacaoPagamento($pagamentoMP);

            return ['status' => 'ok', 'message' => 'Webhook processado com sucesso'];
        } catch (\Exception $e) {
            Yii::error([
                'action' => 'erro_webhook',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'mercadopago');

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Consulta dados do pagamento no Mercado Pago usando tokens de sellers conectados.
     */
    private function consultarPagamentoMarketplace($paymentId, array $webhookData = [])
    {
        try {
            $tenantId = $webhookData['data']['metadata']['tenant_id'] ?? ($webhookData['metadata']['tenant_id'] ?? null);
            $mpUserId = $webhookData['user_id'] ?? ($webhookData['data']['user_id'] ?? null);

            if ($tenantId && $this->validarUUID($tenantId)) {
                $usuario = $this->buscarUsuarioPorId($tenantId);
                $token = $this->obterTokenVendedor($usuario);
                if ($token) {
                    $payment = $this->consultarPagamentoComToken($paymentId, $token);
                    if ($payment) {
                        return $payment;
                    }
                }
            }

            if ($mpUserId) {
                $usuario = $this->buscarUsuarioPorMpUserId($mpUserId);
                $token = $usuario ? $this->obterTokenVendedor($usuario) : null;
                if ($token) {
                    $payment = $this->consultarPagamentoComToken($paymentId, $token);
                    if ($payment) {
                        return $payment;
                    }
                }
            }

            // Fallback: tenta com todas as contas ativas (menos eficiente, mas resiliente)
            foreach ($this->buscarUsuariosComMpAtivo() as $usuarioMp) {
                try {
                    $payment = $this->consultarPagamentoComToken($paymentId, $usuarioMp['access_token']);
                    if ($payment) {
                        return $payment;
                    }
                } catch (MPApiException $e) {
                    continue;
                }
            }

            throw new \Exception('Pagamento não encontrado em nenhuma conta MP configurada');
        } catch (\Throwable $e) {
            Yii::error([
                'action' => 'erro_consultar_pagamento_mp_marketplace',
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ], 'mercadopago');
            return null;
        }
    }

    /**
     * Consulta pagamento com token específico.
     */
    private function consultarPagamentoComToken($paymentId, $accessToken)
    {
        if (!$accessToken) {
            return null;
        }

        MercadoPagoConfig::setAccessToken($accessToken);
        $client = new PaymentClient();
        return $client->get($paymentId);
    }

    /**
     * Consulta dados do pagamento no Mercado Pago (legado)
     */
    private function consultarPagamentoMP($paymentId)
    {
        try {
            // Buscar preferência associada ao pagamento (ou pela external_reference se o payment_id não estiver)
            // Primeiro, tentamos buscar pelo payment_id
            $preferencia = $this->buscarPreferenciaPorPaymentId($paymentId);

            $usuarioId = null;

            if ($preferencia) {
                $usuarioId = $preferencia['usuario_id'];
            } else {
                // Se não achar, não temos como saber qual Access Token usar
                // Precisamos buscar o pagamento, e DELE, pegar a external_reference
                // Isso é um problema... como saber qual token usar?
                // O MP não informa o usuário na notificação inicial.

                // SOLUÇÃO: A notificação de 'payment' não tem a external_reference
                // Precisamos consultar o pagamento $paymentId
                // Mas com QUAL access token?

                // Vamos assumir que a notificação é do tipo 'application/json'
                // e que estamos usando o 'topic' 'payment'.

                // Vamos tentar uma abordagem diferente:
                // 1. O webhook recebe o paymentId
                // 2. Consultamos a API de pagamento
                // 3. DO PAGAMENTO, pegamos a external_reference
                // 4. DA EXTERNAL_REFERENCE, buscamos a preferência e o usuário

                // O problema é o passo 2. Qual token usar?
                // A notificação pode ser 'IPN' (antiga) ou 'Webhooks' (nova).
                // IPN (topic=payment) manda ?topic=payment&id=123
                // Webhooks (POST) manda JSON com 'data.id'

                // Vamos assumir que o $paymentId é válido e que precisamos descobrir o token
                // ISSO É UM PROBLEMA SÉRIO SE VOCÊ TIVER MÚLTIPLOS USUÁRIOS

                // PELA LÓGICA DO SEU CÓDIGO, você salva a preferência ANTES
                // Mas você só salva o payment_id DEPOIS

                // VAMOS REVER O FLUXO:
                // 1. Webhook chega com $paymentId [OK]
                // 2. $this->consultarPagamentoMP($paymentId) é chamado [OK]
                // 3. $this->buscarPreferenciaPorPaymentId($paymentId) [FALHA, payment_id ainda não foi salvo]

                // O fluxo está invertido. Deveria ser:
                // 1. Webhook com $paymentId
                // 2. Chamar API do MP com $paymentId (MAS QUAL TOKEN?)

                // TENTATIVA DE CORREÇÃO DE FLUXO
                // Precisamos primeiro consultar a API do MP, e SÓ DEPOIS buscar a preferência

                // Como não sabemos o token, vamos buscar em TODAS as preferências
                // o payment_id. Isso é ineficiente, mas é uma saída.
                // A função buscarPreferenciaPorPaymentId já faz isso.

                // SE ELA FALHAR, significa que é um pagamento novo.
                // A LÓGICA ATUAL ESTÁ CORRETA, o problema é que ela assume
                // que o 'payment_id' já foi salvo, o que não é verdade na primeira notificação

                // VAMOS MUDAR A LÓGICA DE 'consultarPagamentoMP'

                // --- INÍCIO DA CORREÇÃO DE LÓGICA ---
                // Não podemos buscar a preferência pelo paymentId, pois ele ainda não existe.

                // A notificação (data) pode ter o 'user_id' se for de um App
                // Vamos assumir que não tem.

                // A ÚNICA FORMA é iterar por todos os usuários que usam MP
                // e tentar consultar o pagamento com o token deles até um dar certo.
                // Isso é muito ruim.

                // VAMOS MANTER A LÓGICA ATUAL (que busca a preferência pelo payment_id)
                // e corrigir a 'processarNotificacaoPagamento'

                // A lógica em 'processarNotificacaoPagamento' JÁ ATUALIZA o payment_id
                // O problema é: como o 'consultarPagamentoMP' obtém o Access Token
                // se ele busca a preferência pelo payment_id, e o payment_id
                // ainda não está na preferência?

                // A função 'consultarPagamentoMP' (linha 228) está errada.
                // Ela não pode buscar a preferência pelo paymentId.

                // Vamos simplificar: O webhook NÃO VAI consultar a API.
                // O webhook VAI APENAS ATUALIZAR O STATUS com base no que recebeu.

                // ---- REFAZENDO actionWebhook ----

                // Vamos usar a 'external_reference' que DEVERIA VIR no pagamento
                // Vamos consultar o pagamento primeiro

                // O problema persiste: QUAL TOKEN USAR?
                // O 'MercadoPagoController' (linha 228) TEM UMA FALHA DE LÓGICA

                // --- VAMOS USAR A ÚNICA SAÍDA ---
                // O 'payment' (objeto da SDK) tem 'external_reference'

                // 1. O 'consultarPagamentoMP' precisa do token.
                // 2. Ele tenta 'buscarPreferenciaPorPaymentId' (linha 231) [FALHA]

                // SOLUÇÃO PROPOSTA:
                // O webhook (actionWebhook) deve receber o $paymentId.
                // Ele deve iterar por TODOS os usuários com MP ativo
                // e tentar 'PaymentClient()->get($paymentId)'
                // Se der sucesso, ele achou o usuário e o pagamento.

                // VAMOS IMPLEMENTAR ISSO

                $usuariosMP = $this->buscarUsuariosComMpAtivo();

                foreach ($usuariosMP as $usuario) {
                    try {
                        MercadoPagoConfig::setAccessToken($usuario['mercadopago_access_token']);
                        $client = new PaymentClient();
                        $payment = $client->get($paymentId);

                        // ACHAMOS O PAGAMENTO E O USUÁRIO
                        Yii::info([
                            'action' => 'pagamento_consultado_com_sucesso',
                            'payment_id' => $paymentId,
                            'usuario_id' => $usuario['id']
                        ], 'mercadopago');

                        return $payment; // Retorna o objeto $payment

                    } catch (MPApiException $e) {
                        // Token errado, ou pagamento não encontrado. Tenta o próximo.
                        continue;
                    }
                }

                // Se chegou aqui, não achou o pagamento em nenhuma conta
                throw new \Exception('Pagamento não encontrado em nenhuma conta MP configurada');
            }

            // Se a preferência foi encontrada, usa o token dela (fluxo de re-consulta)
            $usuario = $this->buscarUsuarioPorId($preferencia['usuario_id']);
            MercadoPagoConfig::setAccessToken($usuario['mercadopago_access_token']);

            $client = new PaymentClient();
            $payment = $client->get($paymentId);

            return $payment;
        } catch (\Exception $e) {
            Yii::error([
                'action' => 'erro_consultar_pagamento',
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ], 'mercadopago');

            return null;
        }
    }

    /**
     * Busca todos os usuários que tem MP ativo
     */
    private function buscarUsuariosComMpAtivo()
    {
        $sql = "
            SELECT id, COALESCE(mp_access_token, mercadopago_access_token) AS access_token
            FROM prest_usuarios
            WHERE api_de_pagamento = true
            AND gateway_pagamento = 'mercadopago'
            AND (mp_access_token IS NOT NULL OR mercadopago_access_token IS NOT NULL)
        ";
        return Yii::$app->db->createCommand($sql)->queryAll();
    }


    /**
     * Processa notificação de pagamento
     */
    private function processarNotificacaoPagamento($pagamento)
    {
        $externalReference = $pagamento->external_reference;
        $status = $pagamento->status;
        $statusDetail = $pagamento->status_detail;
        $metadata = isset($pagamento->metadata) ? (array)$pagamento->metadata : [];
        $tenantId = $metadata['tenant_id'] ?? null;
        $orderId = $metadata['order_id'] ?? null;
        $valorTotal = isset($pagamento->transaction_amount) ? (float)$pagamento->transaction_amount : 0.0;
        $platformFee = $this->calcularApplicationFee($valorTotal);

        if (empty($externalReference) && empty($orderId)) {
            Yii::warning([
                'action' => 'pagamento_sem_external_reference',
                'payment_id' => $pagamento->id
            ], 'mercadopago');
            return;
        }

        Yii::info([
            'action' => 'processar_notificacao',
            'external_reference' => $externalReference,
            'status' => $status,
            'status_detail' => $statusDetail,
            'tenant_id' => $tenantId,
            'order_id' => $orderId
        ], 'mercadopago');

        // Atualizar preferência (legado)
        if (!empty($externalReference)) {
            $this->atualizarStatusPreferencia($externalReference, [
                'status' => $status,
                'payment_id' => $pagamento->id,
                'status_detail' => $statusDetail,
                'dados_pagamento' => json_encode($pagamento)
            ]);
        }

        // Ações baseadas no status
        switch ($status) {
            case 'approved':
                if ($tenantId && $orderId) {
                    $this->registrarLogFinanceiro($tenantId, $orderId, $pagamento->id, $valorTotal, $platformFee, 'approved');
                    $this->liberarPedido($tenantId, $orderId, $valorTotal, $pagamento->id);
                } else {
                    // Fluxo legado baseado em preferência
                    $this->criarPedidoNoSistema($externalReference, $pagamento);
                }
                break;

            case 'rejected':
            case 'cancelled':
                $this->cancelarPedido($externalReference, "Pagamento {$status}: {$statusDetail}");
                break;

            case 'refunded':
                if ($tenantId && $orderId) {
                    $this->registrarLogFinanceiro($tenantId, $orderId, $pagamento->id, $valorTotal, $platformFee, 'refunded');
                }
                $this->estornarPedido($externalReference);
                break;
        }
    }

    /**
     * Cria pedido no sistema após aprovação
     */
    private function criarPedidoNoSistema($externalReference, $pagamento)
    {
        try {
            $preferencia = $this->buscarPreferenciaPorExternalRef($externalReference);

            if (!$preferencia) {
                throw new \Exception('Preferência não encontrada');
            }

            // Verificar se já existe pedido criado
            if ($preferencia['pedido_id']) {
                Yii::info([
                    'action' => 'pedido_ja_existe',
                    'pedido_id' => $preferencia['pedido_id']
                ], 'mercadopago');
                return;
            }

            $dadosRequest = json_decode($preferencia['dados_request'], true);

            // Obter forma de pagamento "Mercado Pago"
            $formaPagamentoId = $this->obterFormaPagamentoMercadoPago($preferencia['usuario_id']);

            // Criar pedido
            $pedidoId = $this->criarPedido([
                'usuario_id' => $preferencia['usuario_id'],
                'cliente_id' => $preferencia['cliente_id'],
                'forma_pagamento_id' => $formaPagamentoId,
                'data_venda' => $pagamento->date_approved ?? date('Y-m-d H:i:s'),
                'valor_total' => $preferencia['valor_total'],
                'itens' => $dadosRequest['itens'] ?? [],
                'observacoes' => "Pedido via Mercado Pago\nPayment ID: {$pagamento->id}\nExternal Ref: {$externalReference}",
                'status' => 'pago' // Ou 'confirmado'
            ]);

            // Vincular pedido à preferência
            $this->vincularPedidoPreferencia($preferencia['id'], $pedidoId);

            Yii::info([
                'action' => 'pedido_criado',
                'pedido_id' => $pedidoId,
                'external_reference' => $externalReference
            ], 'mercadopago');
        } catch (\Exception $e) {
            Yii::error([
                'action' => 'erro_criar_pedido',
                'external_reference' => $externalReference,
                'error' => $e->getMessage()
            ], 'mercadopago');
        }
    }

    /**
     * Salva preferência no banco
     */
    private function salvarPreferenciaNoBanco($dados)
    {
        $sql = "
            INSERT INTO mercadopago_preferencias (
                preference_id,
                external_reference,
                usuario_id,
                cliente_id,
                valor_total,
                status,
                dados_request,
                ambiente,
                created_at,
                updated_at
            ) VALUES (
                :preference_id,
                :external_reference,
                :usuario_id::uuid,
                :cliente_id::uuid,
                :valor_total,
                :status,
                :dados_request::jsonb,
                :ambiente,
                NOW(),
                NOW()
            )
            RETURNING id
        ";

        return Yii::$app->db->createCommand($sql, [
            ':preference_id' => $dados['preference_id'],
            ':external_reference' => $dados['external_reference'],
            ':usuario_id' => $dados['usuario_id'],
            ':cliente_id' => $dados['cliente_id'],
            ':valor_total' => $dados['valor_total'],
            ':status' => $dados['status'],
            ':dados_request' => json_encode($dados['dados_request']),
            ':ambiente' => $dados['ambiente']
        ])->queryScalar();
    }

    /**
     * Atualiza status da preferência
     */
    private function atualizarStatusPreferencia($externalReference, $dados)
    {
        $sql = "
            UPDATE mercadopago_preferencias
            SET 
                status = :status,
                payment_id = :payment_id,
                status_detail = :status_detail,
                dados_pagamento = :dados_pagamento::jsonb,
                updated_at = NOW()
            WHERE external_reference = :external_ref
        ";

        Yii::$app->db->createCommand($sql, [
            ':status' => $dados['status'],
            ':payment_id' => $dados['payment_id'],
            ':status_detail' => $dados['status_detail'],
            ':dados_pagamento' => $dados['dados_pagamento'],
            ':external_ref' => $externalReference
        ])->execute();
    }

    /**
     * Vincula pedido à preferência
     */
    private function vincularPedidoPreferencia($preferenciaId, $pedidoId)
    {
        $sql = "
            UPDATE mercadopago_preferencias
            SET pedido_id = :pedido_id::uuid
            WHERE id = :id
        ";

        Yii::$app->db->createCommand($sql, [
            ':pedido_id' => $pedidoId,
            ':id' => $preferenciaId
        ])->execute();
    }

    /**
     * Cria pedido na tabela 'prest_vendas'
     */
    private function criarPedido($dados)
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Criar venda
            $sqlVenda = "
                INSERT INTO prest_vendas (
                    usuario_id,
                    cliente_id,
                    forma_pagamento_id,
                    data_venda,
                    valor_total,
                    observacoes,
                    status,
                    created_at,
                    updated_at
                ) VALUES (
                    :usuario_id::uuid,
                    :cliente_id::uuid,
                    :forma_pagamento_id::uuid,
                    :data_venda,
                    :valor_total,
                    :observacoes,
                    :status,
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
                ':status' => $dados['status']
            ])->queryScalar();

            // Criar itens
            foreach ($dados['itens'] as $item) {
                $sqlItem = "
                    INSERT INTO prest_itens_venda (
                        venda_id,
                        produto_id,
                        quantidade,
                        preco_unitario,
                        subtotal,
                        created_at
                    ) VALUES (
                        :venda_id::uuid,
                        :produto_id::uuid,
                        :quantidade,
                        :preco_unitario,
                        :subtotal,
                        NOW()
                    )
                ";

                $subtotal = ($item['quantidade'] ?? 0) * ($item['preco_unitario'] ?? 0);

                Yii::$app->db->createCommand($sqlItem, [
                    ':venda_id' => $vendaId,
                    ':produto_id' => $item['produto_id'],
                    ':quantidade' => $item['quantidade'],
                    ':preco_unitario' => $item['preco_unitario'],
                    ':subtotal' => $subtotal
                ])->execute();
            }

            $transaction->commit();
            return $vendaId;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Busca preferência por payment_id
     */
    private function buscarPreferenciaPorPaymentId($paymentId)
    {
        $sql = "
            SELECT * FROM mercadopago_preferencias
            WHERE payment_id = :payment_id
            LIMIT 1
        ";

        return Yii::$app->db->createCommand($sql, [
            ':payment_id' => $paymentId
        ])->queryOne();
    }

    /**
     * Busca preferência por external_reference
     */
    private function buscarPreferenciaPorExternalRef($externalReference)
    {
        $sql = "
            SELECT * FROM mercadopago_preferencias
            WHERE external_reference = :external_ref
            LIMIT 1
        ";

        return Yii::$app->db->createCommand($sql, [
            ':external_ref' => $externalReference
        ])->queryOne();
    }

    /**
     * Cancela pedido em 'prest_vendas'
     */
    private function cancelarPedido($externalReference, $motivo)
    {
        $preferencia = $this->buscarPreferenciaPorExternalRef($externalReference);

        if ($preferencia && $preferencia['pedido_id']) {
            $sql = "
                UPDATE prest_vendas
                SET 
                    status = 'cancelado',
                    observacoes = CONCAT(observacoes, E'\n\n', :motivo),
                    updated_at = NOW()
                WHERE id = :id::uuid
            ";

            Yii::$app->db->createCommand($sql, [
                ':id' => $preferencia['pedido_id'],
                ':motivo' => $motivo
            ])->execute();
        }
    }

    /**
     * Estorna pedido
     */
    private function estornarPedido($externalReference)
    {
        $this->cancelarPedido($externalReference, 'Pagamento estornado');
    }

    /**
     * Registra auditoria financeira da plataforma (split).
     */
    private function registrarLogFinanceiro($tenantId, $orderId, $paymentId, $totalAmount, $platformFee, $status = 'pending')
    {
        if (!$tenantId || !$orderId) {
            Yii::warning([
                'action' => 'saas_financial_log_sem_ids',
                'payment_id' => $paymentId,
            ], 'mercadopago');
            return;
        }

        $status = $status ?: 'pending';

        try {
            $existingId = Yii::$app->db->createCommand("
                SELECT id FROM saas_financial_logs
                WHERE tenant_id = :tenant_id::uuid
                  AND order_id = :order_id::uuid
                  AND mp_payment_id = :payment_id
                LIMIT 1
            ", [
                ':tenant_id' => $tenantId,
                ':order_id' => $orderId,
                ':payment_id' => $paymentId
            ])->queryScalar();

            if ($existingId) {
                Yii::$app->db->createCommand()->update('saas_financial_logs', [
                    'total_amount' => $totalAmount,
                    'platform_fee' => $platformFee,
                    'status' => $status,
                ], ['id' => $existingId])->execute();
                return;
            }

            Yii::$app->db->createCommand()->insert('saas_financial_logs', [
                'tenant_id' => $tenantId,
                'order_id' => $orderId,
                'mp_payment_id' => $paymentId,
                'total_amount' => $totalAmount,
                'platform_fee' => $platformFee,
                'status' => $status,
                'created_at' => new Expression('NOW()'),
            ])->execute();
        } catch (DbException $e) {
            // Se a tabela não existe (migration pendente), loga mas não interrompe o fluxo
            if (strpos($e->getMessage(), 'does not exist') !== false) {
                Yii::warning([
                    'action' => 'saas_financial_log_table_not_found',
                    'message' => 'Tabela saas_financial_logs não encontrada. Execute a migration m251210_000010_add_mp_oauth_and_saas_financial_logs',
                    'payment_id' => $paymentId,
                ], 'mercadopago');
            } else {
                // Outros erros de banco devem ser logados como erro
                Yii::error('Erro ao registrar log financeiro: ' . $e->getMessage(), 'mercadopago');
            }
        }
    }

    /**
     * Marca venda como quitada, baixa estoque e registra caixa.
     */
    private function liberarPedido($tenantId, $orderId, $valorTotal, $paymentId)
    {
        $venda = Venda::findOne(['id' => $orderId, 'usuario_id' => $tenantId]);

        if (!$venda) {
            Yii::warning([
                'action' => 'venda_nao_encontrada_webhook',
                'order_id' => $orderId,
                'tenant_id' => $tenantId
            ], 'mercadopago');
            return;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if ($venda->status_venda_codigo !== StatusVenda::QUITADA) {
                $venda->status_venda_codigo = StatusVenda::QUITADA;
                $venda->data_atualizacao = new Expression('NOW()');
                $venda->forma_pagamento_id = $this->obterFormaPagamentoMercadoPago($tenantId);
                $observacaoExtra = "\nPagamento aprovado Mercado Pago #" . $paymentId;
                $venda->observacoes = trim(($venda->observacoes ?? '') . $observacaoExtra);

                if (!$venda->save(false, ['status_venda_codigo', 'data_atualizacao', 'forma_pagamento_id', 'observacoes'])) {
                    throw new \Exception('Erro ao atualizar status da venda.');
                }

                $this->baixarEstoqueVenda($venda);
            }

            try {
                CaixaHelper::registrarEntradaVenda(
                    $venda->id,
                    $valorTotal ?: $venda->valor_total,
                    $venda->forma_pagamento_id,
                    $venda->usuario_id
                );
            } catch (\Throwable $e) {
                Yii::error("Erro ao registrar entrada no caixa: " . $e->getMessage(), 'mercadopago');
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error([
                'action' => 'erro_liberar_pedido',
                'order_id' => $orderId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ], 'mercadopago');
        }
    }

    /**
     * Baixa estoque dos itens da venda (se existentes).
     */
    private function baixarEstoqueVenda(Venda $venda): void
    {
        foreach ($venda->itens as $item) {
            $produto = $item->produto ?? null;
            if (!$produto) {
                continue;
            }

            $produto->refresh();
            $novoEstoque = max(0, (float)$produto->estoque_atual - (float)$item->quantidade);
            $produto->estoque_atual = $novoEstoque;
            $produto->save(false, ['estoque_atual']);
        }
    }

    /**
     * Obtém forma de pagamento "Mercado Pago" da loja
     */
    private function obterFormaPagamentoMercadoPago($usuarioId)
    {
        $sql = "
            SELECT id FROM forma_pagamento
            WHERE usuario_id = :usuario_id::uuid
            AND LOWER(nome) LIKE '%mercado%pago%'
            LIMIT 1
        ";

        $id = Yii::$app->db->createCommand($sql, [
            ':usuario_id' => $usuarioId
        ])->queryScalar();

        // Se não existir, criar
        if (!$id) {
            $id = $this->criarFormaPagamentoMercadoPago($usuarioId);
        }

        return $id;
    }

    /**
     * Cria forma de pagamento "Mercado Pago"
     */
    private function criarFormaPagamentoMercadoPago($usuarioId)
    {
        $sql = "
            INSERT INTO forma_pagamento (
                usuario_id,
                nome,
                ativo,
                created_at
            ) VALUES (
                :usuario_id::uuid,
                'Mercado Pago',
                true,
                NOW()
            )
            RETURNING id
        ";

        return Yii::$app->db->createCommand($sql, [
            ':usuario_id' => $usuarioId
        ])->queryScalar();
    }

    // ========================================================================
    // MÉTODOS AUXILIARES
    // ========================================================================

    /**
     * Busca usuário por ID com campos corretos
     */
    private function buscarUsuarioPorId($usuarioId)
    {
        $sql = "
            SELECT 
                id,
                nome,
                api_de_pagamento,
                mercadopago_access_token,
                mp_access_token,
                mp_refresh_token,
                mp_public_key,
                mp_user_id,
                mp_token_expiration,
                gateway_pagamento,
                mercadopago_public_key,
                mercadopago_sandbox,
                catalogo_path
            FROM prest_usuarios
            WHERE id = :id::uuid
            LIMIT 1
        ";

        return Yii::$app->db->createCommand($sql, [
            ':id' => $usuarioId
        ])->queryOne();
    }

    /**
     * Busca usuário pelo mp_user_id retornado pelo OAuth do Mercado Pago.
     */
    private function buscarUsuarioPorMpUserId($mpUserId)
    {
        if (!$mpUserId) {
            return null;
        }

        $sql = "
            SELECT 
                id,
                nome,
                api_de_pagamento,
                mercadopago_access_token,
                mp_access_token,
                mp_refresh_token,
                mp_public_key,
                mp_user_id,
                mp_token_expiration,
                gateway_pagamento,
                mercadopago_public_key,
                mercadopago_sandbox,
                catalogo_path
            FROM prest_usuarios
            WHERE mp_user_id = :mp_user_id
            LIMIT 1
        ";

        return Yii::$app->db->createCommand($sql, [
            ':mp_user_id' => (string)$mpUserId
        ])->queryOne();
    }

    /**
     * Salva tokens OAuth no tenant.
     */
    private function salvarTokensOauth($tenantId, array $payload)
    {
        $expiration = null;
        if (!empty($payload['expires_in'])) {
            $expiration = (new \DateTimeImmutable('now'))
                ->add(new \DateInterval('PT' . (int)$payload['expires_in'] . 'S'))
                ->format('Y-m-d H:i:sP');
        }

        Yii::$app->db->createCommand()->update('prest_usuarios', [
            'mp_access_token' => $payload['access_token'] ?? null,
            'mp_refresh_token' => $payload['refresh_token'] ?? null,
            'mp_public_key' => $payload['public_key'] ?? null,
            'mp_user_id' => isset($payload['user_id']) ? (string)$payload['user_id'] : null,
            'mp_token_expiration' => $expiration,
            'gateway_pagamento' => 'mercadopago',
            'api_de_pagamento' => true,
        ], 'id = :id', [
            ':id' => $tenantId
        ])->execute();

        Yii::info([
            'action' => 'oauth_tokens_salvos',
            'tenant_id' => $tenantId,
            'mp_user_id' => $payload['user_id'] ?? null
        ], 'mercadopago');
    }

    /**
     * Retorna configuração da aplicação Mercado Pago via env.
     */
    private function getMpAppConfig(): array
    {
        return [
            'app_id' => getenv('MP_APP_ID') ?: getenv('MERCADO_PAGO_APP_ID'),
            'client_secret' => getenv('MP_CLIENT_SECRET') ?: getenv('MERCADO_PAGO_CLIENT_SECRET'),
            'redirect_uri' => getenv('MP_REDIRECT_URI') ?: null,
        ];
    }

    /**
     * URL padrão de callback caso não seja definida por env.
     */
    private function buildDefaultRedirectUri(): string
    {
        return $this->getBaseUrl() . '/pulse/web/index.php/api/mercado-pago/oauth-callback';
    }

    /**
     * Retorna token do vendedor com prioridade para OAuth.
     */
    private function obterTokenVendedor(?array $usuario): ?string
    {
        if (!$usuario) {
            return null;
        }

        if (!empty($usuario['mp_access_token'])) {
            return $usuario['mp_access_token'];
        }

        return $usuario['mercadopago_access_token'] ?? null;
    }

    /**
     * Calcula application_fee de 0.5% com segurança.
     */
    private function calcularApplicationFee(float $valor): float
    {
        if ($valor <= 0) {
            return 0;
        }

        $fee = round($valor * 0.005, 2);
        return min($fee, $valor);
    }

    /**
     * Valida requisição de criação de preferência
     */
    private function validarRequestPreferencia($request)
    {
        if (!isset($request['usuario_id'])) {
            throw new \Exception('Campo usuario_id é obrigatório');
        }

        if (!isset($request['itens']) || !is_array($request['itens']) || empty($request['itens'])) {
            throw new \Exception('Campo itens é obrigatório e deve conter pelo menos um item');
        }

        foreach ($request['itens'] as $item) {
            if (!isset($item['nome']) || !isset($item['quantidade']) || !isset($item['preco_unitario'])) {
                throw new \Exception('Cada item deve ter nome, quantidade e preco_unitario');
            }

            if ($item['quantidade'] <= 0) {
                throw new \Exception('Quantidade deve ser maior que zero');
            }

            if ($item['preco_unitario'] < 0) { // Preço pode ser zero (brinde?)
                throw new \Exception('Preço unitário não pode ser negativo');
            }
        }
    }

    /**
     * Monta dados do pagador
     */
    private function montarDadosPagador($cliente)
    {
        if (empty($cliente)) {
            return [];
        }

        $payer = [];

        if (isset($cliente['nome'])) {
            $payer['name'] = $cliente['nome'];
        }

        if (isset($cliente['sobrenome'])) {
            $payer['surname'] = $cliente['sobrenome'];
        }

        if (isset($cliente['email'])) {
            $payer['email'] = $cliente['email'];
        }

        if (isset($cliente['telefone'])) {
            $payer['phone'] = [
                'area_code' => $this->extrairDDD($cliente['telefone']),
                'number' => $this->extrairTelefone($cliente['telefone'])
            ];
        }

        if (isset($cliente['cpf'])) {
            $payer['identification'] = [
                'type' => 'CPF',
                'number' => preg_replace('/[^0-9]/', '', $cliente['cpf'])
            ];
        }

        if (isset($cliente['cep'])) {
            $payer['address'] = [
                'zip_code' => preg_replace('/[^0-9]/', '', $cliente['cep']),
                'street_name' => $cliente['logradouro'] ?? '',
                'street_number' => $cliente['numero'] ?? ''
            ];
        }

        return $payer;
    }

    /**
     * Gera external_reference único usando PostgreSQL
     */
    private function gerarExternalReference($usuarioId)
    {
        // Formato: ped_UUID-SHORT_TIMESTAMP_RANDOM
        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        $userShort = substr(str_replace('-', '', $usuarioId), 0, 8);

        return "ped_mp_{$userShort}_{$timestamp}_{$random}"; // Adicionado 'mp' para diferenciar
    }

    /**
     * Obtém base URL
     */
    private function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "{$protocol}://{$host}";
    }

    /**
     * Extrai DDD do telefone
     */
    private function extrairDDD($telefone)
    {
        $numero = preg_replace('/[^0-9]/', '', $telefone);
        if (strlen($numero) < 10) return '';
        return substr($numero, 0, 2);
    }

    /**
     * Extrai número sem DDD
     */
    private function extrairTelefone($telefone)
    {
        $numero = preg_replace('/[^0-9]/', '', $telefone);
        if (strlen($numero) < 10) return $numero;
        return substr($numero, 2);
    }

    /**
     * Valida UUID
     */
    private function validarUUID($uuid)
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Formata resposta de erro
     */
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
}
