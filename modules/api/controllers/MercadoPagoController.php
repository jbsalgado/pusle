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
use app\modules\vendas\models\PrestGatewayTransacao;
use app\modules\caixa\helpers\CaixaHelper;

// SDK 3.7 - Importações
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;

class MercadoPagoController extends Controller
{
    /**
     * Taxa de comissão específica para o lojista atual.
     */
    private $taxaComissao = null;

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

            // ✅ order_id deve ser UUID (venda do Pulse). Se não for enviado ou for formato provisório de PDV, gera UUID válido.
            if (!$orderId || !$this->validarUUID($orderId)) {
                $orderId = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
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

            // 1️⃣ INICIALIZAR SDK
            $this->initSdk($usuario);

            $baseUrl = $this->resolveBaseUrl();
            $notificationUrl = $baseUrl . '/index.php/api/mercado-pago/webhook?tenant_id=' . $tenantId;
            $isLocalhost = (strpos($baseUrl, 'localhost') !== false || strpos($baseUrl, '127.0.0.1') !== false);

            $paymentData = [
                'transaction_amount' => $amount,
                'description' => $request['description'] ?? 'Pedido ' . $orderId,
                'payment_method_id' => 'pix',
                'external_reference' => $orderId,
                'metadata' => [
                    'tenant_id' => $tenantId,
                    'order_id' => $orderId,
                ],
            ];

            // application_fee só se aplica para contas de lojistas terceiros autorizados via OAuth.
            // Para a conta da própria plataforma ou transações diretas, o MP rejeita com código 2059.
            $appUserId = $_ENV['MP_COLLECTOR_ID'] ?? '24111981';
            $vendedorUserId = (string)($usuario['mp_user_id'] ?? '');
            $ehPropriaConta = ($vendedorUserId !== '' && $vendedorUserId === (string)$appUserId);

            if ($applicationFee > 0 && !$ehPropriaConta) {
                $paymentData['application_fee'] = $applicationFee;
            }

            // Mercado Pago rejeita notification_url com localhost / IP privado
            if (!$isLocalhost && filter_var($notificationUrl, FILTER_VALIDATE_URL)) {
                $paymentData['notification_url'] = $notificationUrl;
            }

            if (!empty($request['payer']) && is_array($request['payer'])) {
                $paymentData['payer'] = $this->formatarPayerParaPayment($request['payer']);
            } elseif (!empty($request['cliente']) && is_array($request['cliente'])) {
                $paymentData['payer'] = $this->montarDadosPagador($request['cliente'], true);
            }

            // Fallback de dados do pagador exigidos pelo Mercado Pago para emissão de Pix
            if (empty($paymentData['payer']['email'])) {
                $paymentData['payer']['email'] = !empty($usuario['email']) ? $usuario['email'] : 'comprador@oncode.app.br';
            }
            if (empty($paymentData['payer']['first_name'])) {
                $paymentData['payer']['first_name'] = 'Cliente';
                $paymentData['payer']['last_name'] = 'Pulse';
            }

            $client = new PaymentClient();

            // Execução com retry inteligente caso o Mercado Pago recuse application_fee (Erro 2059)
            try {
                $payment = $client->create($paymentData);
            } catch (MPApiException $e) {
                $apiResp = $e->getApiResponse();
                $respContent = is_object($apiResp) && method_exists($apiResp, 'getContent') ? $apiResp->getContent() : [];
                $errorMsg = is_array($respContent) ? ($respContent['message'] ?? '') : '';
                $causeCode = is_array($respContent) ? ($respContent['cause'][0]['code'] ?? 0) : 0;

                if (($causeCode === 2059 || strpos($errorMsg, 'application_fee') !== false) && isset($paymentData['application_fee'])) {
                    Yii::warning("Mercado Pago rejeitou application_fee (código 2059). Refazendo requisição PIX direta sem fee...", 'mercadopago');
                    unset($paymentData['application_fee']);
                    $applicationFee = 0.0;
                    $payment = $client->create($paymentData);
                } else {
                    throw $e;
                }
            }

            Yii::info([
                'action' => 'pix_split_criado',
                'payment_id' => $payment->id,
                'tenant_id' => $tenantId,
                'order_id' => $orderId,
                'application_fee' => $applicationFee,
                'amount' => $amount,
            ], 'mercadopago');

            // Registro auditável unificado da transação do gateway
            PrestGatewayTransacao::registrar([
                'tenant_id'          => $tenantId,
                'venda_id'           => $orderId,
                'gateway'            => PrestGatewayTransacao::GATEWAY_MERCADOPAGO,
                'transacao_id'       => (string)$payment->id,
                'tipo_pagamento'     => PrestGatewayTransacao::TIPO_PIX,
                'valor_bruto'        => $amount,
                'taxa_saas'          => $applicationFee,
                'status'             => $payment->status ?? 'pending',
                'status_detail'      => $payment->status_detail ?? null,
                'payload_requisicao' => $paymentData,
                'payload_resposta'   => $payment,
            ]);

            return [
                'sucesso' => true,
                'payment_id' => $payment->id,
                'order_id' => $orderId,
                'external_reference' => $orderId,
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
     * ENDPOINT: POST /api/mercado-pago/pagar-cartao
     * Checkout transparente — processa pagamento via cartão de crédito/débito.
     * Recebe o card token gerado pelo SDK MP no front-end e cobra diretamente.
     * ========================================================================
     */
    public function actionPagarCartao()
    {
        try {
            $request = Yii::$app->request->post();

            // --- Validações ---
            $tenantId    = $request['tenant_id']    ?? null;
            $orderId     = $request['order_id']     ?? null;
            $cardToken   = $request['token']        ?? null;
            $installments = isset($request['installments']) ? (int)$request['installments'] : 1;
            $amount      = isset($request['amount'])  ? (float)$request['amount']  : null;

            if (!$tenantId || !$this->validarUUID($tenantId)) {
                return $this->errorResponse('tenant_id é obrigatório e deve ser um UUID válido.');
            }
            if (!$orderId || !$this->validarUUID($orderId)) {
                $orderId = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
            }
            if ($amount === null || $amount <= 0) {
                return $this->errorResponse('amount deve ser maior que zero.', 400);
            }
            if ($installments < 1 || $installments > 12) {
                return $this->errorResponse('installments deve estar entre 1 e 12.', 400);
            }

            // --- Buscar loja e credenciais ---
            $usuario = $this->buscarUsuarioPorId($tenantId);
            if (!$usuario) {
                return $this->errorResponse('Loja não encontrada.', 404);
            }

            $accessToken = $this->obterTokenVendedor($usuario);
            if (!$accessToken) {
                return $this->errorResponse('Loja não conectada ao Mercado Pago via OAuth.', 422);
            }

            // Se token não foi enviado mas recebemos dados do cartão (PDV balcão), tokeniza via API
            if (empty($cardToken) && !empty($request['card_number'])) {
                $tokenResp = $this->criarTokenCartaoApi($usuario, $request);
                if (!empty($tokenResp['id'])) {
                    $cardToken = $tokenResp['id'];
                } else {
                    return $this->errorResponse('Erro ao validar dados do cartão: ' . ($tokenResp['message'] ?? 'Dados inválidos'), 400);
                }
            }

            if (empty($cardToken)) {
                return $this->errorResponse('token do cartão é obrigatório.', 400);
            }

            // --- Inicializar SDK com token do vendedor ---
            $this->initSdk($usuario);

            $baseUrl         = $this->resolveBaseUrl();
            $isLocalhost     = (strpos($baseUrl, 'localhost') !== false || strpos($baseUrl, '127.0.0.1') !== false);
            $notificationUrl = $baseUrl . '/index.php/api/mercado-pago/webhook?tenant_id=' . $tenantId;

            // --- Montagem do pagador ---
            $payer = [];
            if (!empty($request['cliente']) && is_array($request['cliente'])) {
                $payer = $this->montarDadosPagador($request['cliente'], true);
            }
            if (empty($payer['email'])) {
                $payer['email'] = !empty($usuario['email']) ? $usuario['email'] : 'comprador@oncode.app.br';
            }
            if (empty($payer['first_name'])) {
                $payer['first_name'] = 'Cliente';
                $payer['last_name']  = 'Pulse';
            }

            // --- Application fee (split) ---
            $applicationFee = $this->calcularApplicationFee($amount);
            $appUserId      = $_ENV['MP_COLLECTOR_ID'] ?? '24111981';
            $vendedorUserId = (string)($usuario['mp_user_id'] ?? '');
            $ehPropriaConta = ($vendedorUserId !== '' && $vendedorUserId === (string)$appUserId);

            $tipoCartao  = $request['tipo_cartao'] ?? $request['payment_type_id'] ?? 'credit_card';
            $isDebito    = ($tipoCartao === 'debit_card');
            if ($isDebito) {
                $installments = 1;
            }

            // --- Payload do pagamento ---
            $paymentMethodId = $request['payment_method_id'] ?? null;
            if ($isDebito && strtolower((string)$paymentMethodId) === 'elo') {
                $paymentMethodId = 'debelo';
            }

            $paymentData = [
                'transaction_amount' => $amount,
                'token'              => $cardToken,
                'description'        => $request['description'] ?? ('Pedido ' . $orderId . ($isDebito ? ' (Débito)' : '')),
                'installments'       => $installments,
                'payment_method_id'  => $paymentMethodId, // Ex: 'visa', 'master', 'debelo'
                'issuer_id'          => isset($request['issuer_id']) ? (int)$request['issuer_id'] : null,
                'external_reference' => $orderId,
                'capture'            => true, // Captura automática
                'payer'              => $payer,
                'metadata'           => [
                    'tenant_id'   => $tenantId,
                    'order_id'    => $orderId,
                    'tipo_cartao' => $tipoCartao,
                ],
            ];

            // Habilita three_d_secure_mode para permitir autenticação em cartões de débito e crédito que exigem 3DS
            $paymentData['three_d_secure_mode'] = 'optional';
            if ($isDebito) {
                $paymentData['payment_type_id'] = 'debit_card';
            }

            // Remove campos nulos opcionais para evitar rejeição da API
            foreach (['payment_method_id', 'issuer_id'] as $optKey) {
                if (empty($paymentData[$optKey])) {
                    unset($paymentData[$optKey]);
                }
            }

            if ($applicationFee > 0 && !$ehPropriaConta) {
                $paymentData['application_fee'] = $applicationFee;
            }
            if (!$isLocalhost && filter_var($notificationUrl, FILTER_VALIDATE_URL)) {
                $paymentData['notification_url'] = $notificationUrl;
            }

            Yii::info([
                'action'       => 'cartao_pagamento_iniciando',
                'tenant_id'    => $tenantId,
                'order_id'     => $orderId,
                'amount'       => $amount,
                'installments' => $installments,
                'tipo_cartao'  => $tipoCartao,
                'method_id'    => $paymentData['payment_method_id'] ?? null,
            ], 'mercadopago');

            $client  = new PaymentClient();

            // --- Execução com retry sem application_fee (erro 2059) ---
            try {
                $payment = $client->create($paymentData);
            } catch (MPApiException $e) {
                $apiResp     = $e->getApiResponse();
                $respContent = is_object($apiResp) && method_exists($apiResp, 'getContent') ? $apiResp->getContent() : [];
                $causeCode   = is_array($respContent) ? ($respContent['cause'][0]['code'] ?? 0) : 0;

                if (($causeCode === 2059 || strpos($respContent['message'] ?? '', 'application_fee') !== false)
                    && isset($paymentData['application_fee'])
                ) {
                    Yii::warning('Cartão: retentativa sem application_fee (código 2059).', 'mercadopago');
                    unset($paymentData['application_fee']);
                    $applicationFee = 0.0;
                    $payment = $client->create($paymentData);
                } else {
                    throw $e;
                }
            }

            $status       = $payment->status;
            $statusDetail = $payment->status_detail;
            $paymentId    = $payment->id;

            Yii::info([
                'action'        => 'cartao_pagamento_processado',
                'payment_id'    => $paymentId,
                'status'        => $status,
                'status_detail' => $statusDetail,
                'tenant_id'     => $tenantId,
                'order_id'      => $orderId,
            ], 'mercadopago');

            // Registro auditável unificado da transação do cartão
            $taxaGateway = 0.0;
            if (!empty($payment->fee_details) && is_array($payment->fee_details)) {
                foreach ($payment->fee_details as $f) {
                    $taxaGateway += (float)(is_object($f) ? ($f->amount ?? 0) : (is_array($f) ? ($f['amount'] ?? 0) : 0));
                }
            }
            $bandeiraCartao = $payment->payment_method_id ?? null;
            $ultimosDigitos = null;
            if (is_object($payment) && isset($payment->card) && is_object($payment->card) && isset($payment->card->last_four_digits)) {
                $ultimosDigitos = (string)$payment->card->last_four_digits;
            } elseif (is_object($payment) && isset($payment->card) && is_array($payment->card) && isset($payment->card['last_four_digits'])) {
                $ultimosDigitos = (string)$payment->card['last_four_digits'];
            } elseif (is_array($payment) && isset($payment['card']['last_four_digits'])) {
                $ultimosDigitos = (string)$payment['card']['last_four_digits'];
            }

            PrestGatewayTransacao::registrar([
                'tenant_id'              => $tenantId,
                'venda_id'               => $orderId,
                'gateway'                => PrestGatewayTransacao::GATEWAY_MERCADOPAGO,
                'transacao_id'           => (string)$paymentId,
                'tipo_pagamento'         => $isDebito ? PrestGatewayTransacao::TIPO_DEBIT_CARD : PrestGatewayTransacao::TIPO_CREDIT_CARD,
                'valor_bruto'            => $amount,
                'taxa_gateway'           => $taxaGateway,
                'taxa_saas'              => $applicationFee,
                'status'                 => $status,
                'status_detail'          => $statusDetail,
                'cartao_bandeira'        => $bandeiraCartao,
                'cartao_ultimos_digitos' => $ultimosDigitos,
                'parcelas'               => (int)$installments,
                'payload_requisicao'     => $paymentData,
                'payload_resposta'       => $payment,
            ]);

            // --- Retorno por status ---
            if ($status === 'approved') {
                // Baixa estoque, gera parcelas e registra caixa
                $this->registrarLogFinanceiro($tenantId, $orderId, $paymentId, $amount, $applicationFee, 'approved');
                $this->liberarPedido($tenantId, $orderId, $amount, $paymentId, $applicationFee);

                return [
                    'sucesso'        => true,
                    'status'         => 'approved',
                    'status_detail'  => $statusDetail,
                    'payment_id'     => $paymentId,
                    'order_id'       => $orderId,
                    'installments'   => $installments,
                    'amount'         => $amount,
                    'mensagem'       => 'Pagamento aprovado com sucesso!',
                ];
            }

            // --- Verificação de desafio 3DS (Three-D Secure) ---
            $threeDsUrl = null;
            if (!empty($payment->transaction_details) && !empty($payment->transaction_details->external_resource_url)) {
                $threeDsUrl = $payment->transaction_details->external_resource_url;
            } elseif (!empty($payment->point_of_interaction) 
                && !empty($payment->point_of_interaction->transaction_data) 
                && !empty($payment->point_of_interaction->transaction_data->ticket_url)
            ) {
                $threeDsUrl = $payment->point_of_interaction->transaction_data->ticket_url;
            } elseif (is_array($payment) && !empty($payment['transaction_details']['external_resource_url'])) {
                $threeDsUrl = $payment['transaction_details']['external_resource_url'];
            }

            if ($threeDsUrl && ($status === 'in_process' || $status === 'pending' || $status === 'requires_action' || $statusDetail === 'pending_challenge')) {
                Yii::info([
                    'action'       => 'cartao_desafio_3ds_detectado',
                    'payment_id'   => $paymentId,
                    'three_ds_url' => $threeDsUrl,
                ], 'mercadopago');

                return [
                    'sucesso'        => false,
                    'status'         => 'requires_action',
                    'action_type'    => '3ds_challenge',
                    'three_ds_url'   => $threeDsUrl,
                    'payment_id'     => $paymentId,
                    'order_id'       => $orderId,
                    'status_detail'  => $statusDetail,
                    'tipo_cartao'    => $tipoCartao,
                    'mensagem'       => 'Autenticação de segurança 3DS necessária no banco emissor do cartão.',
                ];
            }

            if ($status === 'in_process' || $status === 'pending') {
                return [
                    'sucesso'       => false,
                    'status'        => $status,
                    'status_detail' => $statusDetail,
                    'payment_id'    => $paymentId,
                    'order_id'      => $orderId,
                    'mensagem'      => 'Pagamento em análise. Você será notificado quando for aprovado.',
                ];
            }

            // rejected / cancelled
            if ($isDebito) {
                $mensagensRecusa = [
                    'cc_rejected_bad_filled_card_number'   => 'Este cartão não autorizou cobrança no débito online nesta operadora. Por favor, selecione "Cartão de Crédito" (à vista) ou pague via PIX.',
                    'cc_rejected_bad_filled_security_code' => 'Código de segurança (CVV) incorreto.',
                    'cc_rejected_bad_filled_date'          => 'Data de validade incorreta.',
                    'cc_rejected_bad_filled_other'         => 'Dados do cartão incorretos. Por favor, revise as informações.',
                    'cc_rejected_insufficient_amount'      => 'Saldo insuficiente na conta bancária vinculada ao cartão de débito.',
                    'cc_rejected_call_for_authorize'       => 'Transação não autorizada. Verifique se as compras no débito online estão habilitadas no aplicativo do seu banco.',
                    'cc_rejected_card_disabled'            => 'Cartão de débito bloqueado ou desativado. Entre em contato com seu banco.',
                    'cc_rejected_duplicated_payment'       => 'Pagamento duplicado detectado para esta compra.',
                    'cc_rejected_high_risk'                => 'Pagamento de débito não autorizado pela análise de segurança. Recomendamos concluir via PIX.',
                    'cc_rejected_max_attempts'             => 'Limite de tentativas excedido para este cartão de débito. Tente pagar via PIX.',
                    'cc_rejected_card_type_not_allowed'    => 'Este cartão não autorizou débito via e-commerce. Recomendamos concluir via PIX ou Cartão de Crédito à vista.',
                    'cc_rejected_blacklist'                => 'Cartão de débito não autorizado pela instituição bancária.',
                ];
            } else {
                $mensagensRecusa = [
                    'cc_rejected_bad_filled_card_number'   => 'Número do cartão incorreto ou inválido. Por favor, confira os números digitados.',
                    'cc_rejected_bad_filled_security_code' => 'Código de segurança (CVV) incorreto.',
                    'cc_rejected_bad_filled_date'          => 'Data de validade incorreta.',
                    'cc_rejected_bad_filled_other'         => 'Dados do cartão incorretos. Por favor, revise as informações.',
                    'cc_rejected_insufficient_amount'      => 'Saldo ou limite insuficiente no cartão.',
                    'cc_rejected_call_for_authorize'       => 'Autorização pendente. Entre em contato com a administradora do seu cartão para autorizar a compra.',
                    'cc_rejected_card_disabled'            => 'Cartão bloqueado ou desativado. Entre em contato com seu banco.',
                    'cc_rejected_duplicated_payment'       => 'Pagamento duplicado detectado para esta compra.',
                    'cc_rejected_high_risk'                => 'Pagamento recusado pela análise de segurança. Recomendamos pagar via PIX ou utilizar outro cartão.',
                    'cc_rejected_max_attempts'             => 'Limite de tentativas excedido para este cartão. Tente pagar via PIX.',
                    'cc_rejected_card_type_not_allowed'    => 'Tipo de cartão não aceito. Verifique se o cartão é de crédito ou débito válido.',
                    'cc_rejected_blacklist'                => 'Cartão não autorizado pela instituição bancária.',
                ];
            }
            $mensagem = $mensagensRecusa[$statusDetail] ?? "Pagamento não aprovado pela operadora ({$statusDetail}). Tente novamente ou use outro cartão.";

            return [
                'sucesso'       => false,
                'status'        => $status,
                'status_detail' => $statusDetail,
                'payment_id'    => $paymentId,
                'order_id'      => $orderId,
                'tipo_cartao'   => $tipoCartao,
                'mensagem'      => $mensagem,
            ];

        } catch (MPApiException $e) {
            $apiResp     = $e->getApiResponse();
            $content     = is_object($apiResp) && method_exists($apiResp, 'getContent') ? $apiResp->getContent() : [];
            $msg         = is_array($content) ? ($content['message'] ?? $e->getMessage()) : $e->getMessage();
            $causeCode   = 0;
            $causeDesc   = '';

            if (is_array($content) && !empty($content['cause']) && is_array($content['cause'])) {
                $causeCode = (int)($content['cause'][0]['code'] ?? 0);
                $causeDesc = (string)($content['cause'][0]['description'] ?? '');
            }

            Yii::error([
                'action'       => 'cartao_erro_mp_api',
                'error'        => $msg,
                'cause_code'   => $causeCode,
                'cause_desc'   => $causeDesc,
                'api_response' => $content,
            ], 'mercadopago');

            // Tradução amigável e humana dos códigos técnicos da API do Mercado Pago
            $mensagemAmigavel = null;

            if ($causeCode === 10102 || $msg === 'not_result_by_params' || strpos((string)$msg, 'not_result_by_params') !== false) {
                if ($isDebito) {
                    $mensagemAmigavel = 'Este cartão não é aceito para compras no débito online. No Brasil, o Mercado Pago autoriza débito online direto apenas para cartões compatíveis (ex: Elo Débito). Por favor, selecione a opção "Cartão de Crédito" ou finalize via PIX.';
                } else {
                    $mensagemAmigavel = 'Dados ou modalidade do cartão não autorizados pela operadora para esta transação. Por favor, tente com outro cartão ou pague via PIX.';
                }
            } elseif ($causeCode === 3003 || strpos((string)$msg, 'card_token_id') !== false) {
                $mensagemAmigavel = 'Os dados de segurança do cartão expiraram ou são inválidos. Por favor, redigite o número, validade e CVV.';
            } elseif ($causeCode === 2059 || strpos((string)$msg, 'application_fee') !== false) {
                $mensagemAmigavel = 'Instabilidade temporária na comunicação com a operadora. Por favor, tente novamente ou pague via PIX.';
            } elseif ($causeCode === 2060 || strpos((string)$msg, 'customer') !== false) {
                $mensagemAmigavel = 'Não foi possível validar os dados do titular junto à operadora. Verifique o CPF e e-mail informados.';
            } elseif (strpos((string)$msg, 'payment_method_not_found') !== false || $causeCode === 2006) {
                $mensagemAmigavel = 'Bandeira ou cartão não suportado para esta operação. Por favor, tente outro cartão ou pague via PIX.';
            }

            if (!$mensagemAmigavel) {
                $mensagemAmigavel = 'Não foi possível processar o pagamento com este cartão (' . ($msg ?: 'Operação não autorizada') . '). Recomendamos tentar com outro cartão ou pagar via PIX.';
            }

            return $this->errorResponse($mensagemAmigavel, $e->getStatusCode() ?: 422);
        } catch (\Throwable $e) {
            Yii::error([
                'action' => 'cartao_erro_interno',
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ], 'mercadopago');
            return $this->errorResponse('Erro interno ao processar pagamento com cartão.', 500);
        }
    }

    /**
     * ========================================================================
     * ENDPOINT: GET /api/mercado-pago/buscar-parcelas
     * Retorna as opções de parcelamento disponíveis para um BIN de cartão.
     * Parâmetros: tenant_id, amount, bin (primeiros 6 dígitos do cartão), payment_method_id
     * ========================================================================
     */
    public function actionBuscarParcelas()
    {
        try {
            $tenantId        = Yii::$app->request->get('tenant_id');
            $amount          = (float)(Yii::$app->request->get('amount') ?? 0);
            $bin             = Yii::$app->request->get('bin');
            $paymentMethodId = Yii::$app->request->get('payment_method_id', 'credit_card');

            if (!$tenantId || !$this->validarUUID($tenantId)) {
                return $this->errorResponse('tenant_id é obrigatório.');
            }
            if ($amount <= 0) {
                return $this->errorResponse('amount deve ser maior que zero.');
            }

            $usuario = $this->buscarUsuarioPorId($tenantId);
            if (!$usuario) {
                return $this->errorResponse('Loja não encontrada.', 404);
            }

            $accessToken = $this->obterTokenVendedor($usuario);
            if (!$accessToken) {
                return $this->errorResponse('Loja não conectada ao Mercado Pago.', 422);
            }

            // Consulta a API de parcelamento do MP via Guzzle
            $queryParams = http_build_query(array_filter([
                'amount'            => $amount,
                'bin'               => $bin,
                'payment_method_id' => $paymentMethodId,
            ]));

            $httpClient = new Client();
            $response   = $httpClient->get("https://api.mercadopago.com/v1/payment_methods/installments?{$queryParams}", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type'  => 'application/json',
                ],
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $data       = json_decode($response->getBody()->getContents(), true);

            if ($statusCode !== 200 || !is_array($data)) {
                return $this->errorResponse('Não foi possível buscar as opções de parcelamento.', 422);
            }

            // Extrai apenas as parcelas até 12x do primeiro resultado
            $parcelas = [];
            if (!empty($data[0]['payer_costs'])) {
                foreach ($data[0]['payer_costs'] as $option) {
                    if ($option['installments'] > 12) continue;
                    $parcelas[] = [
                        'installments'           => $option['installments'],
                        'installment_rate'        => $option['installment_rate'],
                        'total_amount'            => $option['total_amount'],
                        'installment_amount'      => $option['installment_amount'],
                        'recommended_message'     => $option['recommended_message'] ?? null,
                        'labels'                  => $option['labels'] ?? [],
                    ];
                }
            }

            return [
                'sucesso'  => true,
                'parcelas' => $parcelas,
                'amount'   => $amount,
            ];

        } catch (\Throwable $e) {
            Yii::error([
                'action' => 'buscar_parcelas_erro',
                'error'  => $e->getMessage(),
            ], 'mercadopago');
            return $this->errorResponse('Erro ao buscar parcelas: ' . $e->getMessage(), 500);
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
            $accessToken = $this->obterTokenVendedor($usuario);
            if (empty($accessToken)) {
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

            // 3️⃣ INICIALIZAR SDK
            $this->initSdk($usuario);

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
            $baseUrl = $this->resolveBaseUrl();

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
                "notification_url" => "{$baseUrl}/index.php/api/mercado-pago/webhook?tenant_id={$usuario['id']}",
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

            // Registro unificado da transação do gateway
            PrestGatewayTransacao::registrar([
                'tenant_id'          => $usuario['id'],
                'venda_id'           => ($externalReference && $this->validarUUID($externalReference)) ? $externalReference : null,
                'gateway'            => PrestGatewayTransacao::GATEWAY_MERCADOPAGO,
                'transacao_id'       => (string)$preference->id,
                'tipo_pagamento'     => 'checkout_pro',
                'valor_bruto'        => $valorTotal,
                'taxa_saas'          => $marketplaceFee,
                'status'             => 'pending',
                'payload_requisicao' => $request,
                'payload_resposta'   => $responseBody ?? null,
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
                'marketplace_fee' => $marketplaceFee,
                'public_key' => $usuario['mp_public_key'] ?? $usuario['mercadopago_public_key'] ?? null,
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
     * ENDPOINT: POST /api/mercado-pago/criar-preferencia-carteira-digital
     * Cria preferência otimizada para Carteiras Digitais (Google Pay, Apple Pay e Wallet Brick 1-Clique)
     * com split da SaaS já retido.
     */
    public function actionCriarPreferenciaCarteiraDigital()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $request = Yii::$app->request->post() ?: json_decode(Yii::$app->request->getRawBody(), true) ?: [];
        $vendaId = $request['venda_id'] ?? $request['order_id'] ?? null;
        $tenantId = $request['usuario_id'] ?? $request['tenant_id'] ?? null;

        // Se veio venda_id, busca os dados da venda existente
        if ($vendaId && !$tenantId) {
            $venda = Venda::findOne(['id' => $vendaId]);
            if ($venda) {
                $tenantId = $venda->usuario_id;
            }
        }

        if (!$tenantId) {
            $tenantId = \app\components\TenantHelper::getId();
        }

        if (!$tenantId) {
            return [
                'sucesso' => false,
                'mensagem' => 'Identificador da loja (tenant_id) não informado.'
            ];
        }

        $usuario = $this->buscarUsuarioPorId($tenantId);
        if (!$usuario) {
            return [
                'sucesso' => false,
                'mensagem' => 'Loja não encontrada.'
            ];
        }

        $accessToken = $this->obterTokenVendedor($usuario);
        if (empty($accessToken)) {
            return [
                'sucesso' => false,
                'mensagem' => 'Mercado Pago não configurado para esta loja.'
            ];
        }

        $itens = [];
        $valorTotal = 0;

        if (!empty($request['itens']) && is_array($request['itens'])) {
            foreach ($request['itens'] as $item) {
                $precoUnit = floatval($item['preco_unitario'] ?? $item['preco'] ?? 0);
                $qtd = intval($item['quantidade'] ?? 1);
                $subtotal = $precoUnit * $qtd;
                $valorTotal += $subtotal;
                $itens[] = [
                    'title' => mb_substr($item['nome'] ?? $item['title'] ?? 'Produto', 0, 256),
                    'quantity' => $qtd,
                    'unit_price' => $precoUnit,
                    'currency_id' => 'BRL'
                ];
            }
        } elseif ($vendaId) {
            $venda = Venda::findOne(['id' => $vendaId]);
            if ($venda) {
                $valorTotal = (float)$venda->valor_total;
                if (!empty($venda->itens)) {
                    foreach ($venda->itens as $item) {
                        $itens[] = [
                            'title' => mb_substr($item->produto->nome ?? 'Item da Venda', 0, 256),
                            'quantity' => (int)$item->quantidade,
                            'unit_price' => (float)$item->preco_unitario_venda,
                            'currency_id' => 'BRL'
                        ];
                    }
                }
            }
        }

        // Se ainda não tiver itens montados, monta item com valor total informado
        if (empty($itens)) {
            $valorTotal = floatval($request['valor_total'] ?? $request['amount'] ?? 0);
            if ($valorTotal <= 0) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Valor total inválido para cobrança.'
                ];
            }
            $itens[] = [
                'title' => 'Pedido ' . ($vendaId ? '#' . substr($vendaId, 0, 8) : ($usuario['nome'] ?? 'Pulse')),
                'quantity' => 1,
                'unit_price' => $valorTotal,
                'currency_id' => 'BRL'
            ];
        }

        $marketplaceFee = $this->calcularApplicationFee($valorTotal);
        $externalReference = $vendaId ?: $this->gerarExternalReference($usuario['id']);
        $baseUrl = $this->resolveBaseUrl();
        $catalogoPath = $usuario['catalogo_path'] ?? 'catalogo';

        $payer = [];
        if (!empty($request['cliente'])) {
            $cli = $request['cliente'];
            $payer = [
                'name' => $cli['nome'] ?? 'Cliente',
                'email' => $cli['email'] ?? 'cliente@loja.com.br',
            ];
            if (!empty($cli['cpf'])) {
                $payer['identification'] = [
                    'type' => 'CPF',
                    'number' => preg_replace('/\D/', '', $cli['cpf'])
                ];
            }
        } else {
            $payer = [
                'name' => 'Cliente',
                'email' => 'cliente@loja.com.br'
            ];
        }

        $backUrls = [
            'success' => "{$baseUrl}/{$catalogoPath}/payment-success.html",
            'failure' => "{$baseUrl}/{$catalogoPath}/payment-failure.html",
            'pending' => "{$baseUrl}/{$catalogoPath}/payment-pending.html"
        ];
        if (strpos($baseUrl, 'localhost') !== false || strpos($baseUrl, '127.0.0.1') !== false) {
            $backUrls = [
                'success' => "https://catalogos.oncode.app.br/{$catalogoPath}/payment-success.html",
                'failure' => "https://catalogos.oncode.app.br/{$catalogoPath}/payment-failure.html",
                'pending' => "https://catalogos.oncode.app.br/{$catalogoPath}/payment-pending.html"
            ];
        }

        $preferenceData = [
            'items' => $itens,
            'payer' => $payer,
            'external_reference' => (string)$externalReference,
            'marketplace_fee' => $marketplaceFee,
            'statement_descriptor' => mb_substr($usuario['nome'] ?? 'Loja Online', 0, 22),
            'notification_url' => (strpos($baseUrl, 'localhost') !== false || strpos($baseUrl, '127.0.0.1') !== false)
                ? "https://catalogos.oncode.app.br/index.php/api/mercado-pago/webhook?tenant_id={$usuario['id']}"
                : "{$baseUrl}/index.php/api/mercado-pago/webhook?tenant_id={$usuario['id']}",
            'back_urls' => $backUrls,
            'auto_return' => 'approved',
            'payment_methods' => [
                'installments' => 12
            ],
            'metadata' => [
                'usuario_id' => $usuario['id'],
                'venda_id' => $vendaId,
                'origem' => 'carteira_digital_wallet'
            ]
        ];

        try {
            $httpClient = new Client(['base_uri' => 'https://api.mercadopago.com']);
            $response = $httpClient->post('/checkout/preferences', [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type' => 'application/json',
                    'X-Idempotency-Key' => 'pref_wallet_' . substr(md5($externalReference . time()), 0, 16)
                ],
                'json' => $preferenceData,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode !== 200 && $statusCode !== 201) {
                $msg = $body['message'] ?? 'Erro ao gerar preferência no Mercado Pago.';
                return [
                    'sucesso' => false,
                    'mensagem' => $msg,
                    'detalhes' => $body
                ];
            }

            $publicKey = $usuario['mp_public_key'] ?? $usuario['mercadopago_public_key'] ?? null;

            // Registro unificado da transação do gateway (Carteira Digital)
            PrestGatewayTransacao::registrar([
                'tenant_id'          => $usuario['id'],
                'venda_id'           => ($vendaId && $this->validarUUID($vendaId)) ? $vendaId : null,
                'gateway'            => PrestGatewayTransacao::GATEWAY_MERCADOPAGO,
                'transacao_id'       => (string)$body['id'],
                'tipo_pagamento'     => PrestGatewayTransacao::TIPO_WALLET,
                'valor_bruto'        => $valorTotal,
                'taxa_saas'          => $marketplaceFee,
                'status'             => 'pending',
                'payload_requisicao' => $preferenceData,
                'payload_resposta'   => $body,
            ]);

            return [
                'sucesso' => true,
                'preference_id' => $body['id'],
                'init_point' => $body['init_point'] ?? null,
                'sandbox_init_point' => $body['sandbox_init_point'] ?? null,
                'external_reference' => $externalReference,
                'public_key' => $publicKey,
                'valor_total' => $valorTotal,
                'marketplace_fee' => $marketplaceFee,
                'tenant_id' => $usuario['id']
            ];

        } catch (\Throwable $e) {
            Yii::error('Erro ao criar preferência de carteira digital: ' . $e->getMessage(), 'mercadopago');
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao comunicar com Mercado Pago: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ========================================================================
     * ENDPOINT: GET|POST /api/mercado-pago/consultar-status-preferencia
     * Consulta status do pagamento associado a uma preferência ou external_reference.
     * Utilizado para polling imediato quando o cliente paga via Carteira Digital / 1-Clique.
     * ========================================================================
     */
    public function actionConsultarStatusPreferencia()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $externalReference = Yii::$app->request->get('external_reference') ?: Yii::$app->request->post('external_reference');
        $preferenceId = Yii::$app->request->get('preference_id') ?: Yii::$app->request->post('preference_id');
        $tenantId = Yii::$app->request->get('tenant_id') ?: Yii::$app->request->post('tenant_id');

        if (!$externalReference && !$preferenceId) {
            return $this->errorResponse('external_reference ou preference_id é obrigatório.');
        }

        if (!$tenantId) {
            $tenantId = \app\components\TenantHelper::getId();
        }

        // Se a venda já foi liberada pelo Webhook, retorna aprovado imediatamente
        if ($externalReference && $this->validarUUID($externalReference)) {
            $venda = Venda::findOne(['id' => $externalReference]);
            if ($venda) {
                if (!$tenantId) {
                    $tenantId = $venda->usuario_id;
                }
                if ($venda->status_venda_codigo === StatusVenda::QUITADA) {
                    return [
                        'sucesso' => true,
                        'status' => 'approved',
                        'venda_id' => $venda->id,
                        'mensagem' => 'Venda já quitada e liberada.'
                    ];
                }
            }
        }

        if (!$tenantId) {
            return $this->errorResponse('tenant_id não informado.');
        }

        $usuario = $this->buscarUsuarioPorId($tenantId);
        if (!$usuario) {
            return $this->errorResponse('Loja não encontrada.');
        }

        $accessToken = $this->obterTokenVendedor($usuario);
        if (empty($accessToken)) {
            return $this->errorResponse('Token de acesso do Mercado Pago não configurado.');
        }

        try {
            $client = new Client();
            $payments = [];

            // 1. Busca no endpoint de search de pagamentos por external_reference
            if ($externalReference) {
                $resp = $client->get('https://api.mercadopago.com/v1/payments/search', [
                    'headers' => [
                        'Authorization' => "Bearer {$accessToken}"
                    ],
                    'query' => [
                        'external_reference' => (string)$externalReference,
                        'sort' => 'date_created',
                        'criteria' => 'desc'
                    ],
                    'http_errors' => false
                ]);

                if ($resp->getStatusCode() === 200) {
                    $searchBody = json_decode($resp->getBody()->getContents(), true);
                    $payments = $searchBody['results'] ?? [];
                }
            }

            // 2. Se não achou por external_reference e tiver preference_id, busca em merchant_orders
            if (empty($payments) && $preferenceId) {
                $respMo = $client->get('https://api.mercadopago.com/merchant_orders', [
                    'headers' => [
                        'Authorization' => "Bearer {$accessToken}"
                    ],
                    'query' => [
                        'preference_id' => (string)$preferenceId
                    ],
                    'http_errors' => false
                ]);
                if ($respMo->getStatusCode() === 200) {
                    $moBody = json_decode($respMo->getBody()->getContents(), true);
                    $orders = $moBody['elements'] ?? [];
                    foreach ($orders as $ord) {
                        if (!empty($ord['payments'])) {
                            $payments = array_merge($payments, $ord['payments']);
                        }
                    }
                }
            }

            // Avalia os pagamentos encontrados
            foreach ($payments as $payment) {
                $status = $payment['status'] ?? '';
                if ($status === 'approved') {
                    $paymentId = $payment['id'] ?? null;
                    $amount = (float)($payment['transaction_amount'] ?? 0);
                    $fee = (float)($payment['fee_details'][0]['amount'] ?? 0);

                    if ($externalReference && $this->validarUUID($externalReference)) {
                        $this->liberarPedido($tenantId, $externalReference, $amount, $paymentId, $fee);
                    }

                    return [
                        'sucesso' => true,
                        'status' => 'approved',
                        'payment_id' => $paymentId,
                        'status_detail' => $payment['status_detail'] ?? 'acreditado',
                        'date_approved' => $payment['date_approved'] ?? date('Y-m-d H:i:s'),
                        'venda_id' => $externalReference
                    ];
                }
            }

            $latestStatus = !empty($payments[0]['status']) ? $payments[0]['status'] : 'pending';
            return [
                'sucesso' => true,
                'status' => $latestStatus,
                'status_detail' => $payments[0]['status_detail'] ?? null
            ];

        } catch (\Throwable $e) {
            Yii::error('Erro ao consultar status da preferência MP: ' . $e->getMessage(), 'mercadopago');
            return $this->errorResponse('Erro ao consultar status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * ========================================================================    /**
     * ENDPOINT: GET /api/mercado-pago/dispositivos
     * Lista dispositivos de pagamento (maquinistas) vinculados ao tenant.
     */
    public function actionListarDispositivos($tenant_id = null)
    {
        $tenantId = $tenant_id ?? Yii::$app->request->get('tenant_id');

        if (!$tenantId) {
            return $this->errorResponse('tenant_id não informado');
        }

        $dispositivos = Yii::$app->db->createCommand("
            SELECT id, nome, device_id, status FROM prest_dispositivos_pagamento
            WHERE usuario_id = :usuario_id AND status = 'ativo'
        ", [':usuario_id' => $tenantId])->queryAll();

        return [
            'sucesso' => true,
            'dispositivos' => $dispositivos
        ];
    }

    /**
     * ENDPOINT: POST /api/mercado-pago/criar-pagamento-point
     * Envia uma intenção de pagamento para uma maquineta física.
     */
    public function actionCriarPagamentoPoint()
    {
        $request = Yii::$app->request->post();
        $tenantId = $request['tenant_id'] ?? null;
        $deviceId = $request['device_id'] ?? null;
        $orderId = $request['order_id'] ?? null;
        $amount = (float)($request['amount'] ?? 0);

        if (!$tenantId || !$deviceId || $amount <= 0) {
            return $this->errorResponse('Parâmetros inválidos (tenant_id, device_id e amount são obrigatórios)');
        }

        $usuario = $this->buscarUsuarioPorId($tenantId);
        if (!$usuario) return $this->errorResponse('Usuário não encontrado');

        $this->initSdk($usuario);
        $applicationFee = $this->calcularApplicationFee($amount);

        try {
            $client = new Client();
            $response = $client->post("https://api.mercadopago.com/point/integration-api/devices/{$deviceId}/payment-intents", [
                'headers' => [
                    'Authorization' => 'Bearer ' . ($usuario['mercadopago_access_token'] ?? $usuario['mp_access_token']),
                    'Content-Type' => 'application/json',
                    'x-test-scope' => ($usuario['mercadopago_sandbox'] ? 'sandbox' : '')
                ],
                'json' => [
                    'amount' => (int)($amount * 100), // Em centavos para Point API
                    'description' => "Pedido Pulse #{$orderId}",
                    'payment' => [
                        'installments' => 1,
                        'type' => 'credit_card', // Pode ser dinâmico no futuro
                    ],
                    'additional_info' => [
                        'external_reference' => $orderId, // Usamos o ID do pedido para o webhook
                        'print_on_terminal' => true
                    ],
                    'application_fee' => (int)($applicationFee * 100)
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            Yii::info([
                'action' => 'point_intent_criada',
                'device_id' => $deviceId,
                'intent_id' => $result['id'] ?? null
            ], 'mercadopago');

            // Registro unificado da transação do gateway (Point)
            PrestGatewayTransacao::registrar([
                'tenant_id'          => $tenantId,
                'venda_id'           => ($orderId && $this->validarUUID($orderId)) ? $orderId : null,
                'gateway'            => PrestGatewayTransacao::GATEWAY_MERCADOPAGO,
                'transacao_id'       => (string)($result['id'] ?? $orderId),
                'tipo_pagamento'     => PrestGatewayTransacao::TIPO_POINT,
                'valor_bruto'        => $amount,
                'taxa_saas'          => $applicationFee,
                'status'             => $result['status'] ?? 'pending',
                'payload_requisicao' => [
                    'device_id'       => $deviceId,
                    'amount'          => $amount,
                    'order_id'        => $orderId,
                    'application_fee' => $applicationFee,
                ],
                'payload_resposta'   => $result,
            ]);

            return [
                'sucesso' => true,
                'data' => $result
            ];
        } catch (\Exception $e) {
            Yii::error('Erro ao criar intent Point: ' . $e->getMessage(), 'mercadopago');
            return $this->errorResponse('Erro na comunicação com o Mercado Pago Point: ' . $e->getMessage());
        }
    }

    /**
     * ENDPOINT: GET/POST /api/mercado-pago/consultar-status-point
     * Consulta status da intenção de pagamento na maquininha Point
     */
    public function actionConsultarStatusPoint()
    {
        $intentId = Yii::$app->request->get('intent_id') ?: Yii::$app->request->post('intent_id');
        $tenantId = Yii::$app->request->get('tenant_id') ?: Yii::$app->request->post('tenant_id');

        if (!$intentId || !$tenantId) {
            return $this->errorResponse('intent_id e tenant_id são obrigatórios');
        }

        $usuario = $this->buscarUsuarioPorId($tenantId);
        if (!$usuario) return $this->errorResponse('Usuário não encontrado');

        try {
            $client = new Client();
            $response = $client->get("https://api.mercadopago.com/point/integration-api/payment-intents/{$intentId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . ($usuario['mercadopago_access_token'] ?? $usuario['mp_access_token'])
                ]
            ]);

            $intent = json_decode($response->getBody()->getContents(), true);
            return [
                'sucesso' => true,
                'status' => $intent['status'] ?? 'OPEN',
                'payment' => $intent['payment'] ?? null,
                'intent' => $intent
            ];
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao consultar Point: ' . $e->getMessage());
        }
    }

    /**
     * ENDPOINT: POST /api/mercado-pago/cancelar-pagamento-point
     * Cancela a cobrança pendente na maquininha Point
     */
    public function actionCancelarPagamentoPoint()
    {
        $request = Yii::$app->request->post();
        $deviceId = $request['device_id'] ?? null;
        $intentId = $request['intent_id'] ?? null;
        $tenantId = $request['tenant_id'] ?? null;

        if (!$deviceId || !$intentId || !$tenantId) {
            return $this->errorResponse('device_id, intent_id e tenant_id são obrigatórios');
        }

        $usuario = $this->buscarUsuarioPorId($tenantId);
        if (!$usuario) return $this->errorResponse('Usuário não encontrado');

        try {
            $client = new Client();
            $response = $client->delete("https://api.mercadopago.com/point/integration-api/devices/{$deviceId}/payment-intents/{$intentId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . ($usuario['mercadopago_access_token'] ?? $usuario['mp_access_token'])
                ]
            ]);

            return [
                'sucesso' => true,
                'status' => 'CANCELLED'
            ];
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao cancelar na maquineta: ' . $e->getMessage());
        }
    }

    /**
     * ENDPOINT: GET/POST /api/mercado-pago/consultar-status-pix
     * Consulta status do pagamento PIX diretamente na API do Mercado Pago
     */
    public function actionConsultarStatusPix()
    {
        $paymentId = Yii::$app->request->get('payment_id') ?: Yii::$app->request->post('payment_id');
        $tenantId = Yii::$app->request->get('tenant_id') ?: Yii::$app->request->post('tenant_id');

        if (!$paymentId || !$tenantId) {
            return $this->errorResponse('payment_id e tenant_id são obrigatórios');
        }

        $usuario = $this->buscarUsuarioPorId($tenantId);
        if (!$usuario) return $this->errorResponse('Usuário não encontrado');

        try {
            $client = new Client();
            $resp = $client->get("https://api.mercadopago.com/v1/payments/{$paymentId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . ($usuario['mercadopago_access_token'] ?? $usuario['mp_access_token'])
                ]
            ]);
            $data = json_decode($resp->getBody()->getContents(), true);
            $status = $data['status'] ?? 'pending';

            // Atualizar status e payload completo na auditoria do gateway
            $externalRef = $data['external_reference'] ?? null;
            $amount = (float)($data['transaction_amount'] ?? 0);
            $fee = (float)($data['fee_details'][0]['amount'] ?? 0);

            PrestGatewayTransacao::registrar([
                'tenant_id'          => $tenantId,
                'venda_id'           => ($externalRef && $this->validarUUID($externalRef)) ? $externalRef : null,
                'gateway'            => PrestGatewayTransacao::GATEWAY_MERCADOPAGO,
                'transacao_id'       => (string)$paymentId,
                'tipo_pagamento'     => PrestGatewayTransacao::TIPO_PIX,
                'valor_bruto'        => $amount,
                'taxa_gateway'       => $fee,
                'status'             => $status,
                'status_detail'      => $data['status_detail'] ?? null,
                'payload_resposta'   => $data,
            ]);

            // Se aprovado, garante liberação da venda (idempotente)
            if ($status === 'approved') {
                if ($externalRef && $this->validarUUID($externalRef)) {
                    $this->liberarPedido($tenantId, $externalRef, $amount, $paymentId, $fee);
                }
            }

            return [
                'sucesso' => true,
                'status' => $status,
                'status_detail' => $data['status_detail'] ?? null,
                'date_approved' => $data['date_approved'] ?? null
            ];
        } catch (\Exception $ex) {
            return $this->errorResponse('Erro ao consultar status do pagamento: ' . $ex->getMessage());
        }
    }

    /**
     * ENDPOINT: POST /api/mercado-pago/estornar-pagamento
     * Estorna um pagamento aprovado via Mercado Pago (API de Refunds)
     * e reverte os lançamentos de estoque, parcelas, caixa e status da venda.
     *
     * Parâmetros aceitos (JSON ou form-data):
     * - order_id / venda_id: UUID da venda no Pulse
     * - payment_id: ID do pagamento no Mercado Pago (opcional se order_id for fornecido)
     * - amount: Valor a ser estornado (opcional, default = total)
     * - motivo: Motivo do cancelamento/estorno
     */
    public function actionEstornarPagamento()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $request = Yii::$app->request;
        $orderId = $request->post('order_id') ?: $request->post('venda_id') ?: $request->get('order_id');
        $paymentId = $request->post('payment_id') ?: $request->get('payment_id');
        $amount = $request->post('amount') ?: $request->get('amount');
        $motivo = $request->post('motivo') ?: 'Solicitação de estorno do lojista';

        // 1. Localizar a venda
        $venda = null;
        if ($orderId) {
            $venda = Venda::findOne(['id' => $orderId]);
        }

        // Se não achou por order_id, tenta localizar pelo payment_id no saas_financial_logs
        if (!$venda && $paymentId) {
            $log = \app\modules\vendas\models\SaasFinancialLog::findOne(['mp_payment_id' => (string)$paymentId]);
            if ($log && $log->order_id) {
                $venda = Venda::findOne(['id' => $log->order_id]);
                $orderId = $log->order_id;
            }
        }

        if (!$venda) {
            return [
                'sucesso' => false,
                'mensagem' => 'Venda não encontrada para estorno.',
            ];
        }

        // Validação de Tenant / Permissão
        $usuarioLogadoId = Yii::$app->user->id ?? \app\components\TenantHelper::getId();
        if ($usuarioLogadoId && $venda->usuario_id !== $usuarioLogadoId) {
            $colab = \app\modules\vendas\models\Colaborador::findOne(['prest_usuario_login_id' => $usuarioLogadoId]);
            if (!$colab || $colab->usuario_id !== $venda->usuario_id) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Acesso negado: Você não tem permissão para estornar esta venda.',
                ];
            }
        }

        $tenantId = $venda->usuario_id;
        $usuario = $this->buscarUsuarioPorId($tenantId);
        if (!$usuario) {
            return [
                'sucesso' => false,
                'mensagem' => 'Configuração do lojista não encontrada.',
            ];
        }

        $accessToken = $this->obterTokenVendedor($usuario);
        if (empty($accessToken)) {
            return [
                'sucesso' => false,
                'mensagem' => 'Token de acesso do Mercado Pago não configurado para esta loja.',
            ];
        }

        // Se paymentId não veio explícito, pega da venda (via log ou observações)
        if (empty($paymentId)) {
            $paymentId = $venda->getMpPaymentId();
        }

        if (empty($paymentId)) {
            return [
                'sucesso' => false,
                'mensagem' => 'ID do pagamento Mercado Pago não identificado nesta venda.',
            ];
        }

        // 2. Chamar a API oficial de Refunds do Mercado Pago
        // POST https://api.mercadopago.com/v1/payments/{id}/refunds
        try {
            $client = new Client();
            $url = "https://api.mercadopago.com/v1/payments/{$paymentId}/refunds";

            $payload = [];
            if (!empty($amount) && (float)$amount > 0 && (float)$amount < (float)$venda->valor_total) {
                $payload['amount'] = (float)$amount;
            }

            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                    'X-Idempotency-Key' => uniqid('ref_' . $paymentId . '_', true),
                ],
                'http_errors' => false,
            ];
            if (!empty($payload)) {
                $options['json'] = $payload;
            }

            $response = $client->post($url, $options);
            $statusCode = $response->getStatusCode();
            $bodyRaw = $response->getBody()->getContents();
            $result = json_decode($bodyRaw, true);

            Yii::info([
                'action' => 'mp_refund_response',
                'order_id' => $venda->id,
                'payment_id' => $paymentId,
                'status_code' => $statusCode,
                'response' => $result,
            ], 'mercadopago');

            // Sucesso do MP pode ser status 200 ou 201 com status 'approved'
            $refundApproved = ($statusCode === 200 || $statusCode === 201)
                && isset($result['status'])
                && in_array($result['status'], ['approved', 'in_process']);

            // Tratar caso onde o pagamento já estava estornado no MP
            $alreadyRefunded = false;
            if ($statusCode === 400 && isset($result['message']) && stripos($result['message'], 'refund') !== false) {
                $alreadyRefunded = true;
            }

            if (!$refundApproved && !$alreadyRefunded) {
                $erroMsg = $result['message'] ?? 'Falha ao processar estorno no Mercado Pago.';
                if (!empty($result['cause']) && is_array($result['cause'])) {
                    $causes = array_map(function($c) { return $c['description'] ?? ''; }, $result['cause']);
                    $erroMsg .= ' (' . implode(', ', array_filter($causes)) . ')';
                }
                return [
                    'sucesso' => false,
                    'mensagem' => $erroMsg,
                    'detalhes' => $result,
                ];
            }

            $refundId = $result['id'] ?? null;
            $refundAmount = $result['amount'] ?? ($amount ?: $venda->valor_total);

            // 3. Atualizar saas_financial_logs para 'refunded'
            $log = \app\modules\vendas\models\SaasFinancialLog::findOne([
                'tenant_id' => $tenantId,
                'order_id' => $venda->id,
            ]);
            if ($log) {
                $log->status = \app\modules\vendas\models\SaasFinancialLog::STATUS_REFUNDED;
                $log->save(false, ['status']);
            } else {
                $this->registrarLogFinanceiro($tenantId, $venda->id, $paymentId, (float)$refundAmount, 0, 'refunded');
            }

            // Atualizar auditoria unificada do gateway para 'refunded'
            PrestGatewayTransacao::registrar([
                'tenant_id'        => $tenantId,
                'venda_id'         => $venda->id,
                'gateway'          => PrestGatewayTransacao::GATEWAY_MERCADOPAGO,
                'transacao_id'     => (string)$paymentId,
                'status'           => PrestGatewayTransacao::STATUS_REFUNDED,
                'status_detail'    => 'refunded',
                'payload_resposta' => $result,
            ]);

            // 4. Executar transição da venda para CANCELADA (estorno de estoque, parcelas e caixa)
            try {
                $venda->alterarStatus(StatusVenda::CANCELADA);
            } catch (\Throwable $e) {
                Yii::warning("Aviso ao alterar status da venda no estorno: " . $e->getMessage(), 'mercadopago');
                $venda->status_venda_codigo = StatusVenda::CANCELADA;
                $venda->data_atualizacao = new Expression('NOW()');
                $venda->save(false, ['status_venda_codigo', 'data_atualizacao']);
            }

            // Registrar observação de auditoria na venda
            $obsEstorno = "\n[ESTORNO AUTOMÁTICO MERCADO PAGO]\n"
                . "Refund ID: " . ($refundId ?: 'N/A') . "\n"
                . "Data: " . date('d/m/Y H:i:s') . "\n"
                . "Valor: R$ " . number_format($refundAmount, 2, ',', '.') . "\n"
                . "Motivo: " . $motivo;
            $venda->observacoes = trim(($venda->observacoes ?? '') . $obsEstorno);
            $venda->save(false, ['observacoes']);

            return [
                'sucesso' => true,
                'mensagem' => 'Pagamento estornado com sucesso no Mercado Pago!',
                'refund_id' => $refundId,
                'payment_id' => $paymentId,
                'valor_estornado' => (float)$refundAmount,
                'status' => 'refunded',
            ];

        } catch (\Throwable $e) {
            Yii::error("Exceção ao estornar pagamento MP: " . $e->getMessage(), 'mercadopago');
            return [
                'sucesso' => false,
                'mensagem' => 'Erro interno ao comunicar com o Mercado Pago: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ENDPOINT: GET/POST /api/mercado-pago/consultar-split-venda
     * Consulta detalhes do split de uma venda
     */
    public function actionConsultarSplitVenda()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $orderId = Yii::$app->request->get('order_id') ?: Yii::$app->request->post('order_id');
        if (!$orderId) {
            return ['sucesso' => false, 'mensagem' => 'order_id é obrigatório.'];
        }

        $venda = Venda::findOne(['id' => $orderId]);
        if (!$venda) {
            return ['sucesso' => false, 'mensagem' => 'Venda não encontrada.'];
        }

        $log = $venda->saasFinancialLog;
        $paymentId = $venda->getMpPaymentId();

        if (!$log && !$paymentId) {
            return [
                'sucesso' => true,
                'possui_split' => false,
                'mensagem' => 'Esta venda não possui registros de split no Mercado Pago.',
            ];
        }

        $totalBruto = $log ? (float)$log->total_amount : (float)$venda->valor_total;
        $taxaSaaS = $log ? (float)$log->platform_fee : 0.0;
        $liquidoLojista = max(0, $totalBruto - $taxaSaaS);
        $status = $log ? $log->status : ($venda->status_venda_codigo === StatusVenda::CANCELADA ? 'refunded' : 'approved');

        return [
            'sucesso' => true,
            'possui_split' => true,
            'mp_payment_id' => $paymentId,
            'total_bruto' => $totalBruto,
            'taxa_saas' => $taxaSaaS,
            'liquido_lojista' => $liquidoLojista,
            'percentual_taxa' => $totalBruto > 0 ? round(($taxaSaaS / $totalBruto) * 100, 2) : 0,
            'status' => $status,
            'data_criacao' => $log ? $log->created_at : $venda->data_venda,
        ];
    }

    /**
     * ENDPOINT: GET/POST /api/mercado-pago/webhook
     * ========================================================================
     */
    public function actionWebhook()
    {
        // Headers de CORS para permitir requisições de teste do painel do Mercado Pago
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: *');

        if (Yii::$app->request->isOptions) {
            Yii::$app->response->statusCode = 200;
            return ['status' => 'ok'];
        }

        try {
            // Obter dados da requisição (JSON body, query params ou form-data)
            $body = file_get_contents('php://input');
            $data = !empty($body) ? json_decode($body, true) : [];
            if (!is_array($data)) {
                $data = [];
            }

            Yii::info([
                'action' => 'webhook_recebido',
                'data' => $data,
                'get' => Yii::$app->request->get(),
                'headers' => getallheaders()
            ], 'mercadopago');

            $type = $data['type'] ?? $data['topic'] ?? Yii::$app->request->get('type') ?? Yii::$app->request->get('topic') ?? Yii::$app->request->post('type') ?? Yii::$app->request->post('topic') ?? null;
            $paymentId = $data['data']['id'] ?? $data['id'] ?? Yii::$app->request->get('id') ?? Yii::$app->request->get('data_id') ?? Yii::$app->request->post('id') ?? null;
            $tenantId = Yii::$app->request->get('tenant_id');

            // 🟢 TRATAMENTO IMEDIATO PARA TESTES DO PAINEL DO MERCADO PAGO ("Experimentar" com ID 123456 ou teste vazio)
            if ($paymentId === '123456' || $paymentId === 123456 || ($type === 'payment' && empty($paymentId))) {
                Yii::$app->response->statusCode = 200;
                return ['status' => 'ok', 'message' => 'Notificação de teste recebida com sucesso.'];
            }

            // 🔐 VALIDAÇÃO DE SEGURANÇA DA ASSINATURA (x-signature)
            if (!$this->validarAssinaturaWebhook($data)) {
                Yii::warning('Assinatura do webhook inválida (x-signature)', 'mercadopago');
                return ['status' => 'error', 'message' => 'Assinatura x-signature inválida.'];
            }

            // 🟢 TRATAMENTO PARA POINT (MAQUINETA)
            if ($type === 'payment_intent') {
                $intentId = $data['data']['id'] ?? $data['id'] ?? $paymentId;
                if (!$tenantId) {
                    $mpUserId = $data['user_id'] ?? ($data['data']['user_id'] ?? null);
                    if ($mpUserId) {
                        $usuario = $this->buscarUsuarioPorMpUserId($mpUserId);
                        if ($usuario) {
                            $tenantId = $usuario['id'];
                        }
                    }
                }
                return $this->processarWebhookPoint($intentId, $tenantId);
            }

            // Validar tipo de notificação padrão
            if ($type !== 'payment' && $type !== 'merchant_order') {
                Yii::info('Notificação ignorada: tipo diferente de payment/payment_intent/merchant_order', 'mercadopago');
                return ['status' => 'ok', 'message' => 'Tipo de notificação não processado'];
            }

            if (!$paymentId) {
                return ['status' => 'ok', 'message' => 'ID do pagamento não informado'];
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
            $tenantId = Yii::$app->request->get('tenant_id') ?? ($webhookData['data']['metadata']['tenant_id'] ?? ($webhookData['metadata']['tenant_id'] ?? null));
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

            // Otimização: Tenta consultar com o token master da plataforma (O(1))
            $masterToken = getenv('MP_CLIENT_SECRET') ?: getenv('MERCADO_PAGO_CLIENT_SECRET');
            if ($masterToken) {
                try {
                    $payment = $this->consultarPagamentoComToken($paymentId, $masterToken);
                    if ($payment) {
                        return $payment;
                    }
                } catch (\Throwable $e) {
                    Yii::info('Falha ao consultar pagamento com token master da plataforma, prosseguindo para fallback.', 'mercadopago');
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

        $this->initSdk(['mercadopago_access_token' => $accessToken]);
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
                        $this->initSdk([
                            'mercadopago_access_token' => $usuario['access_token'],
                            'mercadopago_sandbox' => $usuario['mercadopago_sandbox'],
                            'id' => $usuario['id']
                        ]);
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
            $this->initSdk($usuario);

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
            SELECT id, 
                   COALESCE(mp_access_token, mercadopago_access_token) AS access_token,
                   mercadopago_sandbox
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
                'payment_status' => $status,
                'payment_type' => $pagamento->payment_type_id ?? null,
                'transaction_amount' => $pagamento->transaction_amount ?? null
            ]);
        }

        // Atualizar auditoria unificada de transação do gateway
        $targetTenantId = $tenantId;
        $targetVendaId  = $orderId;
        if (!$targetTenantId && !empty($externalReference)) {
            $pref = $this->buscarPreferenciaPorExternalRef($externalReference);
            if ($pref) {
                $targetTenantId = $pref['usuario_id'] ?? null;
            }
        }
        if (!$targetVendaId && !empty($externalReference) && $this->validarUUID($externalReference)) {
            $targetVendaId = $externalReference;
        }
        if (!$targetTenantId && $targetVendaId) {
            $vendaObj = Venda::findOne($targetVendaId);
            if ($vendaObj) {
                $targetTenantId = $vendaObj->usuario_id;
            }
        }

        if ($targetTenantId) {
            $taxaGateway = 0.0;
            if (!empty($pagamento->fee_details) && is_array($pagamento->fee_details)) {
                foreach ($pagamento->fee_details as $f) {
                    $taxaGateway += (float)(is_object($f) ? ($f->amount ?? 0) : (is_array($f) ? ($f['amount'] ?? 0) : 0));
                }
            }

            $bandeiraCartao = $pagamento->payment_method_id ?? null;
            $ultimosDigitos = null;
            if (is_object($pagamento) && isset($pagamento->card) && is_object($pagamento->card) && isset($pagamento->card->last_four_digits)) {
                $ultimosDigitos = (string)$pagamento->card->last_four_digits;
            } elseif (is_object($pagamento) && isset($pagamento->card) && is_array($pagamento->card) && isset($pagamento->card['last_four_digits'])) {
                $ultimosDigitos = (string)$pagamento->card['last_four_digits'];
            } elseif (is_array($pagamento) && isset($pagamento['card']['last_four_digits'])) {
                $ultimosDigitos = (string)$pagamento['card']['last_four_digits'];
            }

            PrestGatewayTransacao::registrar([
                'tenant_id'              => $targetTenantId,
                'venda_id'               => $targetVendaId,
                'gateway'                => PrestGatewayTransacao::GATEWAY_MERCADOPAGO,
                'transacao_id'           => (string)$pagamento->id,
                'tipo_pagamento'         => $pagamento->payment_type_id ?? null,
                'valor_bruto'            => $valorTotal,
                'taxa_gateway'           => $taxaGateway,
                'taxa_saas'              => $platformFee,
                'status'                 => $status,
                'status_detail'          => $statusDetail,
                'cartao_bandeira'        => $bandeiraCartao,
                'cartao_ultimos_digitos' => $ultimosDigitos,
                'parcelas'               => (int)($pagamento->installments ?? 1),
                'payload_resposta'       => $pagamento,
            ]);
        }

        // Ações baseadas no status
        switch ($status) {
            case 'approved':
                if ($tenantId && $orderId) {
                    $this->registrarLogFinanceiro($tenantId, $orderId, $pagamento->id, $valorTotal, $platformFee, 'approved');
                    $this->liberarPedido($tenantId, $orderId, $valorTotal, $pagamento->id, $platformFee);
                } else {
                    // Fluxo legado baseado em preferência
                    $pedidoId = $this->criarPedidoNoSistema($externalReference, $pagamento);
                    if ($pedidoId) {
                        $preferencia = $this->buscarPreferenciaPorExternalRef($externalReference);
                        $this->registrarLogFinanceiro($preferencia['usuario_id'], $pedidoId, $pagamento->id, $valorTotal, $platformFee, 'approved');
                        $this->liberarPedido($preferencia['usuario_id'], $pedidoId, $valorTotal, $pagamento->id, $platformFee);
                    }
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
            $vendaExistente = \app\modules\vendas\models\Venda::find()
                ->where(['like', 'observacoes', "External Ref: {$externalReference}"])
                ->one();

            if ($vendaExistente) {
                Yii::info([
                    'action' => 'pedido_ja_existe',
                    'external_reference' => $externalReference
                ], 'mercadopago');
                return $vendaExistente->id;
            }

            $dadosRequest = json_decode($preferencia['dados_request'], true);

            // Obter forma de pagamento "Mercado Pago"
            $formaPagamentoId = $this->obterFormaPagamentoMercadoPago($preferencia['usuario_id']);

            $obsCliente = !empty($dadosRequest['observacoes']) ? "Observações Cliente: {$dadosRequest['observacoes']}\n" : "";

            // Criar pedido
            $pedidoId = $this->criarPedido([
                'usuario_id' => $preferencia['usuario_id'],
                'cliente_id' => $dadosRequest['cliente_id'],
                'colaborador_vendedor_id' => $dadosRequest['colaborador_vendedor_id'] ?? null,
                'forma_pagamento_id' => $formaPagamentoId,
                'data_venda' => $pagamento->date_approved ?? date('Y-m-d H:i:s'),
                'valor_total' => $preferencia['valor_total'],
                'itens' => $dadosRequest['itens'] ?? [],
                'observacoes' => "{$obsCliente}Pedido via Mercado Pago\nPayment ID: {$pagamento->id}\nExternal Ref: {$externalReference}",
                'status' => 'pago', // Ou 'confirmado'
                'numero_parcelas' => $dadosRequest['numero_parcelas'] ?? 1,
                'intervalo_dias_parcelas' => $dadosRequest['intervalo_dias_parcelas'] ?? 30,
                'data_primeiro_pagamento' => $dadosRequest['data_primeiro_pagamento'] ?? null
            ]);


            Yii::info([
                'action' => 'pedido_criado',
                'pedido_id' => $pedidoId,
                'external_reference' => $externalReference
            ], 'mercadopago');

            return $pedidoId;
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
                valor_total,
                status,
                dados_request,
                created_at,
                ultima_atualizacao
            ) VALUES (
                :preference_id,
                :external_reference,
                :usuario_id::uuid,
                :valor_total,
                :status,
                :dados_request::jsonb,
                NOW(),
                NOW()
            )
            RETURNING id
        ";

        return Yii::$app->db->createCommand($sql, [
            ':preference_id' => $dados['preference_id'],
            ':external_reference' => $dados['external_reference'],
            ':usuario_id' => $dados['usuario_id'],
            ':valor_total' => $dados['valor_total'],
            ':status' => $dados['status'],
            ':dados_request' => json_encode($dados['dados_request'])
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
                payment_status = :payment_status,
                payment_type = :payment_type,
                transaction_amount = :transaction_amount,
                ultima_atualizacao = NOW()
            WHERE external_reference = :external_ref
        ";

        Yii::$app->db->createCommand($sql, [
            ':status' => $dados['status'],
            ':payment_id' => $dados['payment_id'],
            ':payment_status' => $dados['payment_status'],
            ':payment_type' => $dados['payment_type'],
            ':transaction_amount' => $dados['transaction_amount'],
            ':external_ref' => $externalReference
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
                    colaborador_vendedor_id,
                    forma_pagamento_id,
                    data_venda,
                    valor_total,
                    observacoes,
                    status_venda_codigo,
                    numero_parcelas,
                    data_primeiro_vencimento,
                    data_criacao,
                    data_atualizacao
                ) VALUES (
                    :usuario_id::uuid,
                    :cliente_id::uuid,
                    :colaborador_vendedor_id::uuid,
                    :forma_pagamento_id::uuid,
                    :data_venda,
                    :valor_total,
                    :observacoes,
                    :status,
                    :numero_parcelas,
                    :data_primeiro_vencimento,
                    NOW(),
                    NOW()
                )
                RETURNING id
            ";

            $vendaId = Yii::$app->db->createCommand($sqlVenda, [
                ':usuario_id' => $dados['usuario_id'],
                ':cliente_id' => $dados['cliente_id'],
                ':colaborador_vendedor_id' => $dados['colaborador_vendedor_id'] ?? null,
                ':forma_pagamento_id' => $dados['forma_pagamento_id'],
                ':data_venda' => $dados['data_venda'] ?? date('Y-m-d H:i:s'),
                ':valor_total' => $dados['valor_total'],
                ':observacoes' => $dados['observacoes'],
                ':status' => 'EM_ABERTO', // Criar como aberta para liberarPedido processar
                ':numero_parcelas' => $dados['numero_parcelas'] ?? 1,
                ':data_primeiro_vencimento' => $dados['data_primeiro_pagamento'] ?? date('Y-m-d')
            ])->queryScalar();

            // Criar itens
            foreach ($dados['itens'] as $item) {
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
                        :preco_unitario,
                        :subtotal
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

            // Garante a criação das parcelas em prest_parcelas para consistência financeira imediata
            $vendaModel = \app\modules\vendas\models\Venda::findOne($vendaId);
            if ($vendaModel) {
                $vendaModel->gerarParcelas(
                    $dados['forma_pagamento_id'],
                    $dados['data_primeiro_pagamento'] ?? date('Y-m-d'),
                    $dados['intervalo_dias_parcelas'] ?? 30,
                    true
                );
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

        if ($preferencia && !empty($preferencia['pedido_id'])) {
            $venda = Venda::findOne(['id' => $preferencia['pedido_id']]);
            if ($venda) {
                try {
                    $venda->alterarStatus(StatusVenda::CANCELADA);
                } catch (\Throwable $e) {
                    $venda->status_venda_codigo = StatusVenda::CANCELADA;
                    $venda->data_atualizacao = new Expression('NOW()');
                    $venda->save(false, ['status_venda_codigo', 'data_atualizacao']);
                }
                $venda->observacoes = trim(($venda->observacoes ?? '') . "\n\n" . $motivo);
                $venda->save(false, ['observacoes']);
            }
        }
    }

    /**
     * Estorna pedido
     */
    private function estornarPedido($externalReference)
    {
        $this->cancelarPedido($externalReference, 'Pagamento estornado no Mercado Pago');
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
    private function liberarPedido($tenantId, $orderId, $valorTotal, $paymentId, $platformFee = null)
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

        if ($platformFee === null && $valorTotal > 0) {
            $platformFee = $this->calcularApplicationFee($valorTotal);
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

                // Garante que as parcelas sejam geradas se não existirem
                try {
                    $parcelasExistentes = \app\modules\vendas\models\Parcela::find()
                        ->where(['venda_id' => $venda->id])
                        ->count();
                    if ($parcelasExistentes == 0) {
                        $venda->gerarParcelas(
                            $venda->forma_pagamento_id,
                            $venda->data_primeiro_vencimento ?? date('Y-m-d'),
                            30, // Fallback para intervalo padrão de 30 dias
                            true
                        );
                    }
                } catch (\Throwable $e) {
                    Yii::error("Erro ao gerar parcelas no webhook: " . $e->getMessage(), 'mercadopago');
                }

                // Marcar parcelas geradas como pagas no banco de dados para consistência financeira
                try {
                    $parcelas = \app\modules\vendas\models\Parcela::findAll(['venda_id' => $venda->id]);
                    foreach ($parcelas as $parcela) {
                        if ($parcela->status_parcela_codigo !== \app\modules\vendas\models\StatusParcela::PAGA) {
                            $parcela->status_parcela_codigo = \app\modules\vendas\models\StatusParcela::PAGA;
                            $parcela->data_pagamento = date('Y-m-d');
                            $parcela->valor_pago = $parcela->valor_parcela;
                            $parcela->forma_pagamento_id = $venda->forma_pagamento_id;
                            $parcela->save(false);
                        }
                    }
                } catch (\Throwable $e) {
                    Yii::error("Erro ao marcar parcelas como pagas no webhook: " . $e->getMessage(), 'mercadopago');
                }
            }

            try {
                // Registrar Entrada Bruta
                $valorVenda = $valorTotal ?: $venda->valor_total;
                \app\modules\caixa\helpers\CaixaHelper::registrarEntradaVenda(
                    $venda->id,
                    $valorVenda,
                    $venda->forma_pagamento_id,
                    $venda->usuario_id
                );

                // Registrar Saída de Taxa/Split se aplicável para conciliação no Caixa
                if ($platformFee > 0) {
                    $caixa = \app\modules\caixa\helpers\CaixaHelper::getCaixaAberto($venda->usuario_id);
                    if ($caixa) {
                        $movimentacaoSaida = new \app\modules\caixa\models\CaixaMovimentacao();
                        $movimentacaoSaida->caixa_id = $caixa->id;
                        $movimentacaoSaida->tipo = \app\modules\caixa\models\CaixaMovimentacao::TIPO_SAIDA;
                        $movimentacaoSaida->categoria = \app\modules\caixa\models\CaixaMovimentacao::CATEGORIA_OUTRO;
                        $movimentacaoSaida->valor = $platformFee;
                        $movimentacaoSaida->descricao = "Tarifa Split Plataforma - Venda #" . substr($venda->id, 0, 8);
                        $movimentacaoSaida->venda_id = $venda->id;
                        $movimentacaoSaida->forma_pagamento_id = $venda->forma_pagamento_id;
                        $movimentacaoSaida->data_movimento = date('Y-m-d H:i:s');
                        
                        if (!$movimentacaoSaida->save()) {
                            $erros = $movimentacaoSaida->getFirstErrors();
                            Yii::error("Erro ao registrar saida de split no caixa: " . implode(', ', $erros), 'mercadopago');
                        } else {
                            Yii::info("Saída de split R$ {$platformFee} registrada no caixa #{$caixa->id}", 'mercadopago');
                        }
                    }
                }
            } catch (\Throwable $e) {
                Yii::error("Erro ao registrar entrada/saída no caixa: " . $e->getMessage(), 'mercadopago');
            }

            $transaction->commit();
            
            // =========================================================================
            // Disparo Automático de Comprovante via WhatsApp (Evolution API)
            // =========================================================================
            try {
                $this->enviarNotificacaoWhatsAppAprovacao($venda);
            } catch (\Throwable $e) {
                Yii::error("Erro ao enviar WhatsApp automático: " . $e->getMessage(), 'mercadopago');
            }
            
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error([
                'action' => 'erro_liberar_pedido',
                'order_id' => $orderId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ], 'mercadopago');
            throw $e;
        }
    }

    /**
     * Envia notificação automática de aprovação via WhatsApp (Evolution API)
     */
    private function enviarNotificacaoWhatsAppAprovacao($venda)
    {
        // 1. Verifica se a venda tem cliente
        if (empty($venda->cliente_id)) return;
        $cliente = \app\modules\vendas\models\Cliente::findOne($venda->cliente_id);
        if (!$cliente || empty($cliente->telefone)) return;

        // 2. Verifica configuração de WhatsApp do lojista
        $config = \app\modules\evolution\models\WhatsappConfig::findByEmpresa($venda->usuario_id);
        if ($config === null || empty($config->token)) return;

        // 3. Formata e Normaliza o número (Regra do 9)
        $numero = preg_replace('/[^0-9]/', '', $cliente->telefone);
        if (strlen($numero) < 10) return;

        if (strlen($numero) === 11) {
            $numero = '55' . $numero;
        } elseif (strlen($numero) === 10) {
            $ddd  = substr($numero, 0, 2);
            $rest = substr($numero, 2);
            $numero = '55' . $ddd . '9' . $rest;
        }

        if (strlen($numero) === 13 && strpos($numero, '55') === 0) {
            $ddd = (int) substr($numero, 2, 2);
            if ($ddd >= 20 && substr($numero, 4, 1) === '9') {
                $numero = '55' . $ddd . substr($numero, 5);
            }
        }

        // 4. Formata mensagem do Recibo de Confirmação
        $sql = "SELECT nome_loja, nome FROM prest_usuarios WHERE id = :id";
        $loja = Yii::$app->db->createCommand($sql, [':id' => $venda->usuario_id])->queryOne();
        $nomeLoja = $loja ? ($loja['nome_loja'] ?: $loja['nome']) : 'Nossa Loja';
        
        $primeiroNome = explode(' ', trim($cliente->nome))[0];
        $valorTotal = number_format($venda->valor_total, 2, ',', '.');
        
        $mensagem = "Olá, *{$primeiroNome}*! Tudo bem?\n\n";
        $mensagem .= "Seu pagamento via Mercado Pago no valor de *R$ {$valorTotal}* (Pedido #{$venda->id}) foi *APROVADO* com sucesso! ✅\n\n";
        $mensagem .= "Nós da *{$nomeLoja}* já estamos preparando o seu pedido.\n";
        $mensagem .= "Obrigado pela preferência!\n\n_Ref: " . substr(uniqid(), -5) . '_';

        // 5. Configura e Dispara API Evolution Go
        $evolutionConfig = Yii::$app->params['evolution'] ?? [];
        $baseUrl = rtrim($evolutionConfig['baseUrl'] ?? 'http://localhost:8080', '/');

        $client = new \yii\httpclient\Client(['baseUrl' => $baseUrl]);
        $url = '/message/sendText/' . urlencode($config->instance_name);
        
        $payload = [
            'number' => $numero,
            'text'   => $mensagem,
            'delay'  => 1500
        ];

        try {
            $response = $client->post($url, $payload, [
                'Apikey' => $config->token,
                'Content-Type' => 'application/json'
            ])->send();

            if (!$response->isOk) {
                Yii::error("Erro Evolution Go Webhook Automático: " . $response->content, 'mercadopago');
            } else {
                Yii::info("WhatsApp automático enviado com sucesso para {$numero} (Pedido #{$venda->id})", 'mercadopago');
            }
        } catch (\Exception $e) {
            Yii::error("Exceção disparando WhatsApp Automático: " . $e->getMessage(), 'mercadopago');
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
            SELECT id FROM prest_formas_pagamento
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
            INSERT INTO prest_formas_pagamento (
                usuario_id,
                nome,
                tipo,
                ativo,
                data_criacao
            ) VALUES (
                :usuario_id::uuid,
                'Mercado Pago',
                'OUTROS',
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
        $lojaId = $usuarioId;

        // Se o ID informado pertencer a um colaborador, mapeia para o ID do dono/empresa.
        if (!empty($lojaId) && $this->validarUUID($lojaId)) {
            $checkColab = \app\modules\vendas\models\Colaborador::find()
                ->where(['prest_usuario_login_id' => $lojaId])
                ->one();
            if ($checkColab) {
                $lojaId = $checkColab->usuario_id;
            }
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
            WHERE id = :id::uuid
            LIMIT 1
        ";

        return Yii::$app->db->createCommand($sql, [
            ':id' => $lojaId
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
     * Inicializa o SDK do Mercado Pago com as credenciais do usuário e configura o ambiente.
     */
    private function initSdk($usuario)
    {
        $accessToken = $usuario['mercadopago_access_token'] ?? $usuario['mp_access_token'] ?? null;

        if (!$accessToken) {
            return;
        }

        MercadoPagoConfig::setAccessToken($accessToken);

        // Verifica se é sandbox. Aceita tanto 'mercadopago_sandbox' quanto o valor vindo do banco.
        $isSandbox = !empty($usuario['mercadopago_sandbox']);

        if ($isSandbox) {
            MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
        } else {
            // Garante que volta para produção caso tenha sido setado local anteriormente
            // (O SDK mantém estado estático na classe MercadoPagoConfig)
            MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::SERVER);
        }

        // Armazena a taxa de comissão do usuário para uso no cálculo da fee
        $this->taxaComissao = isset($usuario['taxa_comissao']) ? (float)$usuario['taxa_comissao'] : (Yii::$app->params['pulse_platform_fee_percent'] ?? 0.005);

        Yii::info([
            'message' => 'SDK Mercado Pago Inicializado',
            'ambiente' => $isSandbox ? 'SANDBOX' : 'PRODUÇÃO',
            'tenant_id' => $usuario['id'] ?? 'N/A',
            'taxa_comissao' => $this->taxaComissao
        ], 'mercadopago');
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
        return $this->resolveBaseUrl() . '/index.php/api/mercado-pago/oauth-callback';
    }

    /**
     * Retorna token do vendedor com prioridade para OAuth.
     * Se o mp_access_token estiver prestes a expirar (dentro de 7 dias) ou expirado,
     * renova o token automaticamente usando o mp_refresh_token.
     */
    private function obterTokenVendedor(?array $usuario): ?string
    {
        if (!$usuario) {
            return null;
        }

        if (!empty($usuario['mp_access_token'])) {
            $expiration = $usuario['mp_token_expiration'] ?? null;
            $precisaRenovar = false;

            if (!empty($expiration)) {
                $expTimestamp = strtotime($expiration);
                if ($expTimestamp && ($expTimestamp - time() < 604800)) {
                    $precisaRenovar = true;
                }
            }

            if ($precisaRenovar && !empty($usuario['mp_refresh_token'])) {
                $novoToken = $this->renovarTokenOauth($usuario);
                if ($novoToken) {
                    return $novoToken;
                }
            }

            return $usuario['mp_access_token'];
        }

        return $usuario['mercadopago_access_token'] ?? null;
    }

    /**
     * Renova o token de acesso OAuth usando o refresh_token do vendedor.
     */
    private function renovarTokenOauth(array $usuario): ?string
    {
        $tenantId = $usuario['id'] ?? null;
        $refreshToken = $usuario['mp_refresh_token'] ?? null;
        $config = $this->getMpAppConfig();

        if (!$tenantId || !$refreshToken || empty($config['client_secret'])) {
            return null;
        }

        try {
            Yii::info("Renovando token OAuth MP automaticamente para tenant {$tenantId}", 'mercadopago');
            $client = new Client(['base_uri' => 'https://api.mercadopago.com']);

            $response = $client->post('/oauth/token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'client_id' => $config['app_id'],
                    'client_secret' => $config['client_secret'],
                    'refresh_token' => $refreshToken,
                ]
            ]);

            $payload = json_decode((string)$response->getBody(), true);
            if (!empty($payload['access_token'])) {
                $this->salvarTokensOauth($tenantId, $payload);
                Yii::info("Token OAuth MP renovado com sucesso para tenant {$tenantId}", 'mercadopago');
                return $payload['access_token'];
            }
        } catch (\Throwable $e) {
            Yii::error([
                'action' => 'renovar_token_oauth_erro',
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ], 'mercadopago');
        }

        return null;
    }

    /**
     * Valida a assinatura de segurança (x-signature) enviada pelo Mercado Pago.
     */
    private function validarAssinaturaWebhook($data): bool
    {
        $headers = getallheaders();
        $headersNormalized = [];
        foreach ($headers as $key => $value) {
            $headersNormalized[strtolower($key)] = $value;
        }

        $xSignature = $headersNormalized['x-signature'] ?? null;
        $xRequestId = $headersNormalized['x-request-id'] ?? null;

        if (!$xSignature || !$xRequestId) {
            Yii::info('Webhook sem x-signature/x-request-id, ignorando validação HMAC.', 'mercadopago');
            return true;
        }

        $config = $this->getMpAppConfig();
        $secret = $config['client_secret'];
        if (empty($secret)) {
            return true;
        }

        $ts = null;
        $v1 = null;
        $parts = explode(',', $xSignature);
        foreach ($parts as $part) {
            $keyValue = explode('=', trim($part), 2);
            if (count($keyValue) === 2) {
                if ($keyValue[0] === 'ts') $ts = $keyValue[1];
                if ($keyValue[0] === 'v1') $v1 = $keyValue[1];
            }
        }

        if (!$ts || !$v1) {
            return false;
        }

        $dataId = $data['data']['id'] ?? $data['id'] ?? Yii::$app->request->get('id');
        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
        $hash = hash_hmac('sha256', $manifest, $secret);

        $valido = hash_equals($hash, $v1);
        if (!$valido) {
            Yii::warning([
                'action' => 'webhook_assinatura_invalida',
                'manifest' => $manifest,
                'hash_calculado' => $hash,
                'v1_recebido' => $v1
            ], 'mercadopago');
        }

        return $valido;
    }

    /**
     * Tokeniza dados do cartão via API do Mercado Pago (para fluxos de PDV balcão)
     */
    private function criarTokenCartaoApi($usuario, array $cardData)
    {
        $accessToken = $this->obterTokenVendedor($usuario);
        if (!$accessToken) {
            return ['error' => true, 'message' => 'Token do lojista não encontrado'];
        }

        try {
            $client = new \GuzzleHttp\Client();
            $cardNum = preg_replace('/\D/', '', $cardData['card_number'] ?? '');
            $holder = trim($cardData['cardholder_name'] ?? ($cardData['nome'] ?? 'TITULAR'));
            $docNum = preg_replace('/\D/', '', $cardData['payer_cpf'] ?? ($cardData['cliente']['cpf'] ?? '00000000000'));
            if (strlen($docNum) !== 11 && strlen($docNum) !== 14) {
                $docNum = '00000000000';
            }

            $body = [
                'card_number' => $cardNum,
                'cardholder' => [
                    'name' => $holder ?: 'TITULAR',
                    'identification' => [
                        'type' => strlen($docNum) === 14 ? 'CNPJ' : 'CPF',
                        'number' => $docNum,
                    ],
                ],
                'expiration_month' => (int)($cardData['expiration_month'] ?? 0),
                'expiration_year' => (int)($cardData['expiration_year'] ?? 0),
                'security_code' => trim($cardData['security_code'] ?? ($cardData['cvv'] ?? '')),
            ];

            $resp = $client->post('https://api.mercadopago.com/v1/card_tokens', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]);

            return json_decode($resp->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $resp = $e->getResponse();
            $respData = $resp ? json_decode($resp->getBody()->getContents(), true) : [];
            $msg = $respData['message'] ?? $e->getMessage();
            if (!empty($respData['cause']) && is_array($respData['cause'])) {
                $causes = [];
                foreach ($respData['cause'] as $c) {
                    if (!empty($c['description'])) $causes[] = $c['description'];
                }
                if (!empty($causes)) {
                    $msg .= ' (' . implode(', ', $causes) . ')';
                }
            }
            return ['error' => true, 'message' => $msg];
        } catch (\Exception $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    /**
     * Calcula application_fee (split da plataforma) com segurança.
     */
    private function calcularApplicationFee(float $valor): float
    {
        if ($valor <= 0) {
            return 0;
        }

        // Se o lojista tiver uma taxa de comissão específica no banco, usa ela.
        // Caso contrário, usa a taxa padrão da plataforma definida no config/params.php.
        $percent = $this->taxaComissao ?? (Yii::$app->params['pulse_platform_fee_percent'] ?? 0.005);

        $fee = round($valor * $percent, 2);
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
     * @param array $cliente
     * @param bool|array $paraPayment Se true, usa first_name/last_name exigidos pela API /v1/payments
     */
    private function montarDadosPagador($cliente, $paraPayment = false)
    {
        if (empty($cliente)) {
            return [];
        }

        $isPayment = is_bool($paraPayment) ? $paraPayment : false;

        $payer = [];

        $nome = $cliente['nome'] ?? '';
        $sobrenome = $cliente['sobrenome'] ?? '';
        if (empty($sobrenome) && !empty($nome)) {
            $partes = explode(' ', trim($nome), 2);
            $nome = $partes[0];
            $sobrenome = $partes[1] ?? 'Cliente';
        }

        if ($isPayment) {
            if (!empty($nome)) {
                $payer['first_name'] = $nome;
            }
            if (!empty($sobrenome)) {
                $payer['last_name'] = $sobrenome;
            }
        } else {
            if (isset($cliente['nome'])) {
                $payer['name'] = $cliente['nome'];
            }
            if (isset($cliente['sobrenome'])) {
                $payer['surname'] = $cliente['sobrenome'];
            }
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
     * Normaliza array de payer para /v1/payments (troca name/surname por first_name/last_name se necessário)
     */
    private function formatarPayerParaPayment(array $payer): array
    {
        if (isset($payer['name']) && !isset($payer['first_name'])) {
            $nome = $payer['name'];
            $sobrenome = $payer['surname'] ?? '';
            if (empty($sobrenome)) {
                $partes = explode(' ', trim($nome), 2);
                $nome = $partes[0];
                $sobrenome = $partes[1] ?? 'Cliente';
            }
            $payer['first_name'] = $nome;
            $payer['last_name'] = $sobrenome;
            unset($payer['name'], $payer['surname']);
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
     * Resolve a URL base dinamicamente com base na requisição do Yii2.
     * Retorna a URL completa sem a barra no final.
     */
    private function resolveBaseUrl(): string
    {
        // ✅ FIX: Usa APP_URL do .env se disponível.
        // Necessário quando o servidor está atrás de proxy reverso (Nginx/Apache)
        // pois o PHP enxerga HTTP_HOST=127.0.0.1 em vez do domínio real.
        $appUrl = getenv('APP_URL') ?: null;
        if ($appUrl) {
            return rtrim($appUrl, '/');
        }

        if (Yii::$app instanceof \yii\web\Application && Yii::$app->request->hasMethod('getHostInfo')) {
            return rtrim(Yii::$app->request->hostInfo . Yii::$app->request->baseUrl, '/');
        }
        return rtrim($this->getBaseUrl() . '/pulse/web', '/');
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
     * Remove (desvincula) um dispositivo de pagamento.
     */
    public function actionRemoverDispositivo($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            $model = \app\models\PrestDispositivosPagamento::findOne($id);

            if (!$model) {
                return ['sucesso' => false, 'erro' => 'Dispositivo não encontrado.'];
            }

            if ($model->delete()) {
                return ['sucesso' => true, 'mensagem' => 'Dispositivo removido com sucesso.'];
            }

            return ['sucesso' => false, 'erro' => 'Falha ao remover dispositivo no banco.'];
        } catch (\Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
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

    /**
     * ENDPOINT: POST /api/mercado-pago/registrar-dispositivo
     * Registra uma maquineta Point no banco de dados.
     */
    public function actionRegistrarDispositivo()
    {
        $request = Yii::$app->request->post();
        $tenantId = $request['tenant_id'] ?? null;
        $nome = $request['nome'] ?? 'Maquineta';
        $deviceId = $request['device_id'] ?? null;

        if (!$tenantId || !$deviceId) {
            return $this->errorResponse('tenant_id e device_id são obrigatórios');
        }

        try {
            Yii::$app->db->createCommand()->insert('prest_dispositivos_pagamento', [
                'usuario_id' => $tenantId,
                'nome' => $nome,
                'device_id' => $deviceId,
                'status' => 'ativo'
            ])->execute();

            return [
                'sucesso' => true,
                'mensagem' => 'Dispositivo registrado com sucesso'
            ];
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao registrar dispositivo: ' . $e->getMessage());
        }
    }

    /**
     * Processa webhook específico para Mercado Pago Point
     */
    private function processarWebhookPoint($intentId, $tenantId)
    {
        if (!$tenantId) {
            Yii::error('Tenant ID não informado no webhook Point', 'mercadopago');
            return ['status' => 'error', 'message' => 'tenant_id missing'];
        }

        $usuario = $this->buscarUsuarioPorId($tenantId);
        if (!$usuario) return $this->errorResponse('Tenant não encontrado no webhook Point');

        $this->initSdk($usuario);

        try {
            $client = new Client();
            $response = $client->get("https://api.mercadopago.com/point/integration-api/payment-intents/{$intentId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . ($usuario['mercadopago_access_token'] ?? $usuario['mp_access_token'])
                ]
            ]);

            $intent = json_decode($response->getBody()->getContents(), true);
            $statusIntent = $intent['status'] ?? null;
            $orderId = $intent['additional_info']['external_reference'] ?? null;

            if ($statusIntent === 'FINISHED') {
                $paymentId = $intent['payment']['id'] ?? null;
                $amount = (float)($intent['amount'] / 100);

                if ($orderId) {
                    $this->liberarPedido($tenantId, $orderId, $amount, $paymentId);
                    Yii::info("Pedido {$orderId} liberado via Webhook Point", 'mercadopago');
                }
            } elseif (in_array($statusIntent, ['CANCELED', 'CANCELLED', 'FAILED', 'EXPIRED', 'ABORTED', 'ERROR'])) {
                if ($orderId) {
                    $this->cancelarPedido($orderId, "Pagamento RECUSADO na maquineta Point ({$statusIntent})");
                    Yii::warning("Pedido {$orderId} marcado como RECUSADO via Webhook Point ({$statusIntent})", 'mercadopago');
                }
            }

            return ['status' => 'OK'];
        } catch (\Exception $e) {
            Yii::error('Erro ao processar webhook Point: ' . $e->getMessage(), 'mercadopago');
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
