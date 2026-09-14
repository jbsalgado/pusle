<?php

namespace app\modules\prestanista\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\Parcela;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\HistoricoCobranca;
use app\modules\vendas\models\FormaPagamento;
use app\modules\vendas\models\StatusParcela;
use app\modules\vendas\models\StatusVenda;
use app\modules\vendas\models\PrestGatewayTransacao;
use app\modules\prestanista\controllers\CartaoController;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;

/**
 * CobradorController - App Mobile First do Cobrador de Rua (Offline-Ready)
 * Gerenciamento de Rotas, Baixas e Recebimentos em campo e Emissão de Cartões Atualizados
 */
class CobradorController extends Controller
{
    public $enableCsrfValidation = false; // Permite sincronização via Fetch / Service Worker offline

    /**
     * Tela Principal do App do Cobrador
     */
    public function actionIndex($cobrador_id = null, $loja_id = null)
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : ($loja_id ?: null);

        $colaboradorLogado = null;
        $ehSupervisor = false;

        if ($usuario) {
            $ehSupervisor = $usuario->eh_dono_loja || $usuario->is_admin || $usuario->isGestorPrestanista();
            $colaboradorLogado = $usuario->colaborador;
        }

        if (!$usuarioId && $cobrador_id) {
            $colab = Colaborador::findOne(['id' => $cobrador_id, 'ativo' => true]);
            if ($colab) {
                $usuarioId = $colab->usuario_id;
            }
        }

        if (!$usuarioId) {
            $primeiroColab = Colaborador::find()->where(['ativo' => true])->one();
            $usuarioId = $primeiroColab ? $primeiroColab->usuario_id : null;
        }

        $cobradores = [];
        $lojaNome = 'Pulse Prestanista';
        $mpConectado = false;
        $mpPublicKey = '';

        if ($usuarioId) {
            $uLoja = \app\models\Usuario::findOne($usuarioId);
            $lojaNome = $uLoja ? ($uLoja->nome_loja ?? $uLoja->nome ?? 'Pulse Prestanista') : 'Pulse Prestanista';
            $mpConectado = $uLoja ? $uLoja->temMercadoPagoConfigurado() : false;
            $mpPublicKey = $uLoja ? ($uLoja->mp_public_key ?: $uLoja->mercadopago_public_key) : '';

            // Se for colaborador cobrador comum, lista apenas a si mesmo e trava no seu id
            if ($colaboradorLogado && !$ehSupervisor) {
                $cobradores = [$colaboradorLogado];
                $cobrador_id = $colaboradorLogado->id;
            } else {
                $cobradores = Colaborador::find()
                    ->where(['usuario_id' => $usuarioId, 'ativo' => true])
                    ->andWhere(['or', ['eh_cobrador' => true], ['eh_cobrador' => null]])
                    ->orderBy(['nome_completo' => SORT_ASC])
                    ->all();
            }
        }

        $this->layout = false; // Layout mobile app dedicado

        return $this->render('index', [
            'lojaNome' => $lojaNome,
            'cobradores' => $cobradores,
            'cobradorId' => $cobrador_id,
            'usuarioId' => $usuarioId,
            'colaboradorLogado' => $colaboradorLogado,
            'ehSupervisor' => $ehSupervisor,
            'mpConectado' => $mpConectado,
            'mpPublicKey' => $mpPublicKey,
        ]);
    }

    /**
     * Retorna a lista de clientes, cartões e parcelas da rota do cobrador para armazenar offline
     */
    public function actionDadosRota($cobrador_id = null, $loja_id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : ($loja_id ?: Yii::$app->request->get('loja_id'));

        $colaboradorLogado = null;
        $ehSupervisor = false;
        if ($usuario) {
            $ehSupervisor = $usuario->eh_dono_loja || $usuario->is_admin || $usuario->isGestorPrestanista();
            $colaboradorLogado = $usuario->colaborador;
        }

        // Se for colaborador de rua e não supervisor, força a rota do próprio colaborador logado
        if ($colaboradorLogado && !$ehSupervisor) {
            $cobrador_id = $colaboradorLogado->id;
        }

        if (!$usuarioId && $cobrador_id) {
            $colab = Colaborador::findOne(['id' => $cobrador_id, 'ativo' => true]);
            if ($colab) {
                $usuarioId = $colab->usuario_id;
            }
        }

        if (!$usuarioId) {
            $primeiroColab = Colaborador::find()->where(['ativo' => true])->one();
            $usuarioId = $primeiroColab ? $primeiroColab->usuario_id : null;
        }

        if (!$usuarioId) {
            return [
                'success' => true,
                'total' => 0,
                'rotas' => [],
                'mensagem' => 'Nenhuma loja identificada para carregar a rota.'
            ];
        }

        // Uma rota de cobrança de rua SÓ EXISTE se houver um cobrador especificado
        // e se houver cartões com parcelas expressamente atribuídas a ele.
        // Vendas sem cobrador atribuído NUNCA compõem rotas de cobrança.
        if (!$cobrador_id) {
            return [
                'success' => true,
                'total' => 0,
                'rotas' => [],
                'mensagem' => 'Selecione um cobrador para carregar sua rota de cobrança atribuída.'
            ];
        }

        $query = Venda::findPrestanista($usuarioId)
            ->leftJoin('prest_clientes c', 'c.id = v.cliente_id')
            ->with(['cliente', 'itens.produto', 'parcelas.formaPagamento', 'vendedor'])
            ->andWhere(['v.status_venda_codigo' => ['EM_ABERTO', 'PARCIALMENTE_PAGA']])
            ->andWhere(['v.tipo_venda' => Venda::TIPO_PRESTANISTA])
            ->andWhere(['>', 'v.numero_parcelas', 1])
            ->andWhere([
                'exists',
                (new \yii\db\Query())
                    ->from('prest_parcelas pp')
                    ->where('pp.venda_id = v.id')
                    ->andWhere(['pp.cobrador_id' => $cobrador_id])
                    ->andWhere(['pp.status_parcela_codigo' => StatusParcela::PENDENTE])
            ])
            ->orderBy(['c.endereco_bairro' => SORT_ASC, 'c.endereco_logradouro' => SORT_ASC, 'v.id' => SORT_ASC]);

        $cartoes = $query->all();

        $dados = [];
        foreach ($cartoes as $cartao) {
            $cliente = $cartao->cliente;
            $parcelas = $cartao->parcelas;

            $totalPago = 0;
            $parcelasData = [];
            $proximaPendente = null;

            foreach ($parcelas as $p) {
                $pago = ($p->status_parcela_codigo === StatusParcela::PAGA);
                if ($pago) {
                    $totalPago += (float)($p->valor_pago ?: $p->valor_parcela);
                } elseif (!$proximaPendente) {
                    $proximaPendente = $p;
                }

                $parcelasData[] = [
                    'id' => (string)$p->id,
                    'numero' => (int)$p->numero_parcela,
                    'data_vencimento' => $p->data_vencimento ? date('d/m/Y', strtotime($p->data_vencimento)) : '',
                    'data_vencimento_raw' => $p->data_vencimento,
                    'valor_parcela' => (float)$p->valor_parcela,
                    'status' => $p->status_parcela_codigo,
                    'data_pagamento' => $p->data_pagamento ? date('d/m/Y', strtotime($p->data_pagamento)) : '',
                    'valor_pago' => (float)($p->valor_pago ?: 0),
                    'tipo_pagamento' => $p->formaPagamento ? $p->formaPagamento->nome : '',
                ];
            }

            $saldoDevedor = max(0, (float)$cartao->valor_total - $totalPago);

            $produtosDesc = [];
            if (!empty($cartao->itens)) {
                foreach ($cartao->itens as $it) {
                    $nomeP = $it->produto ? $it->produto->nome : 'Item';
                    $produtosDesc[] = "{$it->quantidade}x {$nomeP}";
                }
            }

            $dados[] = [
                'venda_id' => (string)$cartao->id,
                'numero_cartao' => substr($cartao->id, 0, 8),
                'cliente' => [
                    'id' => $cliente ? (string)$cliente->id : null,
                    'nome' => $cliente ? $cliente->nome_completo : 'Sem Cliente',
                    'telefone' => $cliente ? ($cliente->getTelefoneFormatado() ?: $cliente->telefone) : '',
                    'logradouro' => $cliente ? $cliente->endereco_logradouro : '',
                    'numero' => $cliente ? $cliente->endereco_numero : '',
                    'bairro' => $cliente ? $cliente->endereco_bairro : '',
                    'cidade' => $cliente ? $cliente->endereco_cidade : '',
                    'complemento' => $cliente ? $cliente->endereco_complemento : '',
                ],
                'vendedor_nome' => $cartao->vendedor ? $cartao->vendedor->nome_completo : 'Venda Direta',
                'data_venda' => $cartao->data_venda ? date('d/m/Y', strtotime($cartao->data_venda)) : '',
                'valor_total' => (float)$cartao->valor_total,
                'total_pago' => (float)$totalPago,
                'saldo_devedor' => (float)$saldoDevedor,
                'numero_parcelas' => (int)$cartao->numero_parcelas,
                'produtos_descricao' => implode(', ', $produtosDesc),
                'public_url' => CartaoController::getPublicUrl($cartao->id),
                'parcelas' => $parcelasData,
                'proxima_parcela' => $proximaPendente ? [
                    'id' => (string)$proximaPendente->id,
                    'numero' => (int)$proximaPendente->numero_parcela,
                    'data_vencimento' => date('d/m/Y', strtotime($proximaPendente->data_vencimento)),
                    'valor' => (float)$proximaPendente->valor_parcela,
                ] : null,
            ];
        }

        $uLoja = $usuarioId ? \app\models\Usuario::findOne($usuarioId) : null;
        $mpConectado = $uLoja ? $uLoja->temMercadoPagoConfigurado() : false;
        $mpPublicKey = $uLoja ? ($uLoja->mp_public_key ?: $uLoja->mercadopago_public_key) : '';

        return [
            'success' => true,
            'total' => count($dados),
            'rotas' => $dados,
            'mp_conectado' => $mpConectado,
            'mp_public_key' => $mpPublicKey,
            'loja_id' => $usuarioId,
        ];
    }

    /**
     * Gera um PIX dinâmico do Mercado Pago para uma parcela específica
     */
    public function actionGerarPixParcela()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $rawBody = Yii::$app->request->getRawBody();
        $payload = json_decode($rawBody, true) ?: Yii::$app->request->post();

        $parcelaId = $payload['parcela_id'] ?? null;
        $valor = isset($payload['valor']) ? (float)$payload['valor'] : null;
        $cobradorId = $payload['cobrador_id'] ?? null;
        $usuarioId = $payload['usuario_id'] ?? null;

        if (!$parcelaId) {
            return ['success' => false, 'mensagem' => 'ID da parcela é obrigatório.'];
        }

        $parcela = Parcela::findOne(['id' => $parcelaId]);
        if (!$parcela) {
            return ['success' => false, 'mensagem' => 'Parcela não encontrada.'];
        }

        if ($parcela->status_parcela_codigo === StatusParcela::PAGA) {
            return ['success' => false, 'mensagem' => 'Esta parcela já foi paga anteriormente.'];
        }

        $cartao = Venda::findOne(['id' => $parcela->venda_id]);
        if (!$cartao) {
            return ['success' => false, 'mensagem' => 'Venda/Cartão não encontrado.'];
        }

        $tenantId = $cartao->usuario_id ?: $usuarioId;
        $uLoja = \app\models\Usuario::findOne($tenantId);
        if (!$uLoja || !$uLoja->temMercadoPagoConfigurado()) {
            return ['success' => false, 'mensagem' => 'A loja não possui integração com o Mercado Pago configurada.'];
        }

        $accessToken = $uLoja->mp_access_token ?: $uLoja->mercadopago_access_token;
        MercadoPagoConfig::setAccessToken($accessToken);
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::SERVER);

        $amount = ($valor && $valor > 0) ? $valor : (float)$parcela->valor_parcela;
        $cliente = $cartao->cliente;

        $payer = [
            'email' => !empty($cliente->email) ? $cliente->email : (!empty($uLoja->email) ? $uLoja->email : 'cliente@oncode.app.br'),
            'first_name' => $cliente ? explode(' ', trim($cliente->nome_completo))[0] : 'Cliente',
            'last_name' => $cliente ? (strstr(trim($cliente->nome_completo), ' ') ? trim(strstr(trim($cliente->nome_completo), ' ')) : 'Pulse') : 'Prestanista',
        ];

        if ($cliente && !empty($cliente->cpf_cnpj)) {
            $cpfLimpo = preg_replace('/\D/', '', $cliente->cpf_cnpj);
            if (strlen($cpfLimpo) === 11) {
                $payer['identification'] = [
                    'type' => 'CPF',
                    'number' => $cpfLimpo,
                ];
            }
        }

        $paymentData = [
            'transaction_amount' => round($amount, 2),
            'description' => "Prestação #{$parcela->numero_parcela} - Cartão #" . substr($cartao->id, 0, 8) . ($cliente ? " - " . $cliente->nome_completo : ""),
            'payment_method_id' => 'pix',
            'payer' => $payer,
            'external_reference' => (string)$parcela->id,
            'metadata' => [
                'tenant_id' => (string)$tenantId,
                'venda_id' => (string)$cartao->id,
                'parcela_id' => (string)$parcela->id,
                'cobrador_id' => (string)$cobradorId,
                'origem' => 'app_cobrador',
            ],
        ];

        try {
            $client = new PaymentClient();
            $payment = $client->create($paymentData);

            // Registro no log auditável de transações do gateway
            PrestGatewayTransacao::registrar([
                'tenant_id'          => $tenantId,
                'venda_id'           => (string)$cartao->id,
                'gateway'            => PrestGatewayTransacao::GATEWAY_MERCADOPAGO,
                'transacao_id'       => (string)$payment->id,
                'tipo_pagamento'     => PrestGatewayTransacao::TIPO_PIX,
                'valor_bruto'        => $amount,
                'taxa_saas'          => 0,
                'status'             => $payment->status ?? 'pending',
                'status_detail'      => $payment->status_detail ?? null,
                'payload_requisicao' => $paymentData,
                'payload_resposta'   => $payment,
            ]);

            $qrCode = $payment->point_of_interaction->transaction_data->qr_code ?? null;
            $qrCodeBase64 = $payment->point_of_interaction->transaction_data->qr_code_base64 ?? null;

            return [
                'success' => true,
                'payment_id' => (string)$payment->id,
                'parcela_id' => (string)$parcela->id,
                'venda_id' => (string)$cartao->id,
                'status' => $payment->status,
                'qr_code' => $qrCode,
                'qr_code_base64' => $qrCodeBase64,
                'valor' => $amount,
            ];
        } catch (MPApiException $e) {
            $apiResp = $e->getApiResponse();
            $content = is_object($apiResp) && method_exists($apiResp, 'getContent') ? $apiResp->getContent() : [];
            $msg = is_array($content) ? ($content['message'] ?? $e->getMessage()) : $e->getMessage();
            return [
                'success' => false,
                'mensagem' => 'Erro na API do Mercado Pago: ' . $msg,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'mensagem' => 'Erro interno ao gerar PIX: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Consulta o status do PIX no Mercado Pago e efetiva a baixa imediata se aprovado
     */
    public function actionConsultarPixParcela()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $paymentId = Yii::$app->request->get('payment_id') ?: Yii::$app->request->post('payment_id');
        $parcelaId = Yii::$app->request->get('parcela_id') ?: Yii::$app->request->post('parcela_id');
        $cobradorId = Yii::$app->request->get('cobrador_id') ?: Yii::$app->request->post('cobrador_id');

        if (!$paymentId || !$parcelaId) {
            return ['success' => false, 'mensagem' => 'payment_id e parcela_id são obrigatórios.'];
        }

        $parcela = Parcela::findOne(['id' => $parcelaId]);
        if (!$parcela) {
            return ['success' => false, 'mensagem' => 'Parcela não encontrada.'];
        }

        $cartao = Venda::findOne(['id' => $parcela->venda_id]);
        if (!$cartao) {
            return ['success' => false, 'mensagem' => 'Venda não encontrada.'];
        }

        // Se já foi marcada como paga, retorna status aprovado
        if ($parcela->status_parcela_codigo === StatusParcela::PAGA) {
            return [
                'success' => true,
                'status' => 'approved',
                'ja_paga' => true,
                'public_url' => CartaoController::getPublicUrl($cartao->id),
                'mensagem' => 'Parcela confirmada com sucesso!',
            ];
        }

        $tenantId = $cartao->usuario_id;
        $uLoja = \app\models\Usuario::findOne($tenantId);
        if (!$uLoja || !$uLoja->temMercadoPagoConfigurado()) {
            return ['success' => false, 'mensagem' => 'Mercado Pago não configurado na loja.'];
        }

        $accessToken = $uLoja->mp_access_token ?: $uLoja->mercadopago_access_token;
        MercadoPagoConfig::setAccessToken($accessToken);
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::SERVER);

        try {
            $client = new PaymentClient();
            $payment = $client->get((int)$paymentId);

            $status = $payment->status ?? 'pending';
            $statusDetail = $payment->status_detail ?? null;

            // Atualiza registro no gateway
            PrestGatewayTransacao::registrar([
                'tenant_id'          => $tenantId,
                'venda_id'           => (string)$cartao->id,
                'gateway'            => PrestGatewayTransacao::GATEWAY_MERCADOPAGO,
                'transacao_id'       => (string)$paymentId,
                'tipo_pagamento'     => PrestGatewayTransacao::TIPO_PIX,
                'status'             => $status,
                'status_detail'      => $statusDetail,
                'payload_resposta'   => $payment,
            ]);

            if ($status === 'approved') {
                $valorPago = (float)($payment->transaction_amount ?: $parcela->valor_parcela);
                self::efetivarBaixaParcela($parcela, $cartao, $valorPago, $cobradorId, 'PIX', (string)$paymentId, 'MERCADOPAGO');

                return [
                    'success' => true,
                    'status' => 'approved',
                    'public_url' => CartaoController::getPublicUrl($cartao->id),
                    'mensagem' => 'Pagamento PIX recebido e confirmado com sucesso!',
                ];
            }

            return [
                'success' => true,
                'status' => $status,
                'status_detail' => $statusDetail,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'mensagem' => 'Erro ao consultar pagamento no Mercado Pago: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Processa pagamento de parcela com Cartão Transparente (Crédito ou Débito) via Mercado Pago API
     */
    public function actionPagarCartaoParcela()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $rawBody = Yii::$app->request->getRawBody();
        $payload = json_decode($rawBody, true) ?: Yii::$app->request->post();

        $parcelaId       = $payload['parcela_id'] ?? null;
        $cardToken       = $payload['token'] ?? null;
        $paymentMethodId = $payload['payment_method_id'] ?? null;
        $issuerId        = !empty($payload['issuer_id']) ? (int)$payload['issuer_id'] : null;
        $tipoCartao      = $payload['tipo_cartao'] ?? 'credit_card'; // credit_card ou debit_card
        $cobradorId      = $payload['cobrador_id'] ?? null;
        $amount          = isset($payload['amount']) ? (float)$payload['amount'] : null;
        $cardholderName  = $payload['cardholder_name'] ?? null;
        $docNumber       = $payload['identification_number'] ?? null;

        if (!$parcelaId || !$cardToken) {
            return ['success' => false, 'mensagem' => 'Parcela e token do cartão são obrigatórios.'];
        }

        $parcela = Parcela::findOne(['id' => $parcelaId]);
        if (!$parcela) {
            return ['success' => false, 'mensagem' => 'Parcela não encontrada.'];
        }

        if ($parcela->status_parcela_codigo === StatusParcela::PAGA) {
            return ['success' => false, 'mensagem' => 'Esta parcela já foi paga.'];
        }

        $cartao = Venda::findOne(['id' => $parcela->venda_id]);
        if (!$cartao) {
            return ['success' => false, 'mensagem' => 'Venda/Cartão não encontrado.'];
        }

        $tenantId = $cartao->usuario_id;
        $uLoja = \app\models\Usuario::findOne($tenantId);
        if (!$uLoja || !$uLoja->temMercadoPagoConfigurado()) {
            return ['success' => false, 'mensagem' => 'A loja não possui integração com o Mercado Pago configurada.'];
        }

        $accessToken = $uLoja->mp_access_token ?: $uLoja->mercadopago_access_token;
        MercadoPagoConfig::setAccessToken($accessToken);
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::SERVER);

        $isDebito = ($tipoCartao === 'debit_card');
        if ($isDebito && strtolower((string)$paymentMethodId) === 'elo') {
            $paymentMethodId = 'debelo';
        }

        $valorFinal = ($amount && $amount > 0) ? $amount : (float)$parcela->valor_parcela;
        $cliente = $cartao->cliente;

        $payer = [
            'email' => !empty($cliente->email) ? $cliente->email : (!empty($uLoja->email) ? $uLoja->email : 'cliente@oncode.app.br'),
            'first_name' => $cardholderName ? explode(' ', trim($cardholderName))[0] : ($cliente ? explode(' ', trim($cliente->nome_completo))[0] : 'Cliente'),
            'last_name' => $cardholderName ? (strstr(trim($cardholderName), ' ') ? trim(strstr(trim($cardholderName), ' ')) : 'Pulse') : 'Prestanista',
        ];

        $cpfLimpo = preg_replace('/\D/', '', (string)($docNumber ?: ($cliente->cpf_cnpj ?? '')));
        if (strlen($cpfLimpo) === 11) {
            $payer['identification'] = [
                'type' => 'CPF',
                'number' => $cpfLimpo,
            ];
        }

        $paymentData = [
            'transaction_amount' => round($valorFinal, 2),
            'token'              => $cardToken,
            'description'        => "Prestação #{$parcela->numero_parcela} - Cartão #" . substr($cartao->id, 0, 8) . ($isDebito ? ' (Débito)' : ' (Crédito)'),
            'installments'       => 1,
            'payment_method_id'  => $paymentMethodId,
            'issuer_id'          => $issuerId,
            'external_reference' => (string)$parcela->id,
            'capture'            => true,
            'payer'              => $payer,
            'metadata'           => [
                'tenant_id'   => (string)$tenantId,
                'venda_id'    => (string)$cartao->id,
                'parcela_id'  => (string)$parcela->id,
                'cobrador_id' => (string)$cobradorId,
                'origem'      => 'app_cobrador',
                'tipo_cartao' => $tipoCartao,
            ],
            'three_d_secure_mode' => 'optional',
        ];

        if ($isDebito) {
            $paymentData['payment_type_id'] = 'debit_card';
        }

        foreach (['payment_method_id', 'issuer_id'] as $optKey) {
            if (empty($paymentData[$optKey])) {
                unset($paymentData[$optKey]);
            }
        }

        try {
            $client = new PaymentClient();
            $payment = $client->create($paymentData);

            $status = $payment->status ?? 'pending';
            $statusDetail = $payment->status_detail ?? null;
            $paymentId = (string)$payment->id;

            // Grava histórico no gateway
            PrestGatewayTransacao::registrar([
                'tenant_id'              => $tenantId,
                'venda_id'               => (string)$cartao->id,
                'gateway'                => PrestGatewayTransacao::GATEWAY_MERCADOPAGO,
                'transacao_id'           => $paymentId,
                'tipo_pagamento'         => $isDebito ? PrestGatewayTransacao::TIPO_DEBIT_CARD : PrestGatewayTransacao::TIPO_CREDIT_CARD,
                'valor_bruto'            => $valorFinal,
                'taxa_gateway'           => 0,
                'taxa_saas'              => 0,
                'status'                 => $status,
                'status_detail'          => $statusDetail,
                'cartao_bandeira'        => $payment->payment_method_id ?? null,
                'cartao_ultimos_digitos' => isset($payment->card->last_four_digits) ? (string)$payment->card->last_four_digits : null,
                'parcelas'               => 1,
                'payload_requisicao'     => $paymentData,
                'payload_resposta'       => $payment,
            ]);

            if ($status === 'approved') {
                $tipoNome = $isDebito ? 'CARTAO_DEBITO' : 'CARTAO_CREDITO';
                self::efetivarBaixaParcela($parcela, $cartao, $valorFinal, $cobradorId, $tipoNome, $paymentId, 'MERCADOPAGO');

                return [
                    'success' => true,
                    'status' => 'approved',
                    'payment_id' => $paymentId,
                    'public_url' => CartaoController::getPublicUrl($cartao->id),
                    'mensagem' => 'Pagamento no cartão aprovado com sucesso!',
                ];
            }

            if ($status === 'in_process' || $status === 'pending') {
                return [
                    'success' => false,
                    'status' => $status,
                    'status_detail' => $statusDetail,
                    'mensagem' => 'Pagamento em análise pela operadora do cartão.',
                ];
            }

            // Recusado
            $mensagemAmigavel = $this->traduzirErroCartaoMP($statusDetail, $isDebito);
            return [
                'success' => false,
                'status' => 'rejected',
                'status_detail' => $statusDetail,
                'mensagem' => $mensagemAmigavel,
            ];

        } catch (MPApiException $e) {
            $apiResp = $e->getApiResponse();
            $content = is_object($apiResp) && method_exists($apiResp, 'getContent') ? $apiResp->getContent() : [];
            $msg = is_array($content) ? ($content['message'] ?? $e->getMessage()) : $e->getMessage();
            return [
                'success' => false,
                'mensagem' => 'Erro no Mercado Pago: ' . $msg,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'mensagem' => 'Erro interno ao processar cartão: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Traduz códigos de erro do Mercado Pago para mensagens claras ao usuário/cobrador
     */
    public function traduzirErroCartaoMP($statusDetail, $isDebito = false)
    {
        if ($isDebito) {
            $map = [
                'cc_rejected_bad_filled_card_number'   => 'Número do cartão incorreto ou inválido no débito.',
                'cc_rejected_bad_filled_security_code' => 'Código de segurança (CVV) incorreto.',
                'cc_rejected_bad_filled_date'          => 'Data de validade incorreta.',
                'cc_rejected_bad_filled_other'         => 'Dados do cartão incorretos. Por favor revise.',
                'cc_rejected_insufficient_amount'      => 'Saldo insuficiente na conta bancária do cliente.',
                'cc_rejected_call_for_authorize'       => 'Transação de débito não autorizada pelo banco emissor.',
                'cc_rejected_card_disabled'            => 'Cartão de débito bloqueado ou desativado.',
                'cc_rejected_duplicated_payment'       => 'Pagamento duplicado detectado.',
                'cc_rejected_high_risk'                => 'Transação de débito não autorizada pela segurança bancária. Use PIX ou Dinheiro.',
                'cc_rejected_card_type_not_allowed'    => 'Cartão não autorizado para débito nesta operadora. Tente Crédito ou PIX.',
                'cc_rejected_blacklist'                => 'Cartão não autorizado pela instituição bancária.',
            ];
        } else {
            $map = [
                'cc_rejected_bad_filled_card_number'   => 'Número do cartão incorreto ou inválido.',
                'cc_rejected_bad_filled_security_code' => 'Código de segurança (CVV) incorreto.',
                'cc_rejected_bad_filled_date'          => 'Data de validade incorreta.',
                'cc_rejected_bad_filled_other'         => 'Dados do cartão incorretos. Por favor revise.',
                'cc_rejected_insufficient_amount'      => 'Saldo ou limite insuficiente no cartão do cliente.',
                'cc_rejected_call_for_authorize'       => 'Autorização pendente. O cliente precisa autorizar no app do cartão.',
                'cc_rejected_card_disabled'            => 'Cartão bloqueado ou desativado.',
                'cc_rejected_duplicated_payment'       => 'Pagamento duplicado detectado.',
                'cc_rejected_high_risk'                => 'Pagamento recusado pela análise de risco. Tente PIX ou Dinheiro.',
                'cc_rejected_max_attempts'             => 'Tentativas excedidas para este cartão.',
                'cc_rejected_card_type_not_allowed'    => 'Tipo de cartão não aceito. Verifique se é crédito válido.',
                'cc_rejected_blacklist'                => 'Cartão não autorizado pelo banco.',
            ];
        }

        return $map[$statusDetail] ?? "Pagamento não aprovado pela operadora ({$statusDetail}). Tente novamente, troque o cartão ou pague via PIX.";
    }

    /**
     * Efetiva a baixa de uma parcela (paga via PIX, Cartão MP ou Dinheiro) de forma unificada e transacional
     */
    public static function efetivarBaixaParcela($parcela, $cartao, $valorPago, $cobradorId, $tipoNome, $transacaoId = null, $gateway = null)
    {
        $tenantId = $cartao->usuario_id;
        
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Localiza ou cria FormaPagamento
            $formaPagamento = FormaPagamento::find()
                ->where(['usuario_id' => $tenantId, 'ativo' => true])
                ->andWhere(['or',
                    ['ilike', 'nome', $tipoNome],
                    ['tipo' => $tipoNome]
                ])
                ->one();

            if (!$formaPagamento) {
                $formaPagamento = new FormaPagamento();
                $formaPagamento->usuario_id = $tenantId;
                $formaPagamento->nome = mb_strtoupper($tipoNome, 'UTF-8');
                $formaPagamento->tipo = in_array($tipoNome, [FormaPagamento::TIPO_PIX, FormaPagamento::TIPO_DINHEIRO, FormaPagamento::TIPO_CARTAO, FormaPagamento::TIPO_BOLETO]) ? $tipoNome : FormaPagamento::TIPO_OUTRO;
                $formaPagamento->ativo = true;
                $formaPagamento->save(false);
            }

            // Atualiza Parcela
            $parcela->status_parcela_codigo = StatusParcela::PAGA;
            $parcela->data_pagamento = date('Y-m-d');
            $parcela->valor_pago = $valorPago;
            $parcela->forma_pagamento_id = $formaPagamento->id;
            if ($cobradorId) {
                $parcela->cobrador_id = $cobradorId;
            }
            $parcela->save(false);

            // Registra Histórico de Cobrança
            $origemDesc = $gateway ? "Gateway {$gateway} (Transação #{$transacaoId})" : "App Cobrador";
            $hist = new HistoricoCobranca();
            $hist->usuario_id = $tenantId;
            $hist->parcela_id = $parcela->id;
            $hist->cliente_id = $cartao->cliente_id;
            $hist->cobrador_id = $cobradorId ?: $cartao->colaborador_vendedor_id;
            $hist->tipo_acao = HistoricoCobranca::TIPO_PAGAMENTO;
            $hist->valor_recebido = $valorPago;
            $hist->observacao = "Recebimento da {$parcela->numero_parcela}ª prestação via {$formaPagamento->nome} ({$origemDesc})";
            $hist->data_acao = date('Y-m-d H:i:s');
            $hist->save(false);

            // Atualiza status da venda
            $pendentes = Parcela::find()
                ->where(['venda_id' => $cartao->id])
                ->andWhere(['!=', 'status_parcela_codigo', StatusParcela::PAGA])
                ->count();

            if ($pendentes == 0) {
                $cartao->status_venda_codigo = StatusVenda::QUITADA;
            } else {
                $cartao->status_venda_codigo = StatusVenda::PARCIALMENTE_PAGA;
            }
            $cartao->save(false);

            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("Erro ao efetivar baixa de parcela: " . $e->getMessage(), 'prestanista');
            throw $e;
        }
    }

    /**
     * Sincroniza pagamentos/baixas realizados offline pelo cobrador de rua
     */
    public function actionSincronizar()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $rawBody = Yii::$app->request->getRawBody();
        $payload = json_decode($rawBody, true) ?: Yii::$app->request->post();

        $pagamentosOffline = $payload['pagamentos_offline'] ?? [];
        $tenantId = $payload['usuario_id'] ?? null;

        $usuario = Yii::$app->user->identity;
        $colaboradorLogado = $usuario ? $usuario->colaborador : null;
        $ehSupervisor = $usuario ? ($usuario->eh_dono_loja || $usuario->is_admin || $usuario->isGestorPrestanista()) : false;

        if (!$tenantId) {
            $tenantId = $usuario ? $usuario->getTenantId() : null;
        }

        if (!$tenantId && !empty($pagamentosOffline[0]['cobrador_id'])) {
            $colab = Colaborador::findOne(['id' => $pagamentosOffline[0]['cobrador_id'], 'ativo' => true]);
            if ($colab) {
                $tenantId = $colab->usuario_id;
            }
        }

        if (!$tenantId) {
            $primeiroColab = Colaborador::find()->where(['ativo' => true])->one();
            $tenantId = $primeiroColab ? $primeiroColab->usuario_id : null;
        }

        if (empty($pagamentosOffline)) {
            return [
                'success' => true,
                'mensagem' => 'Nenhum pagamento offline para sincronizar.',
                'sincronizados' => [],
                'erros' => [],
            ];
        }

        $sincronizados = [];
        $erros = [];

        foreach ($pagamentosOffline as $item) {
            $offlineId = $item['offline_id'] ?? uniqid('pag_');
            $parcelaId = $item['parcela_id'] ?? null;
            $vendaId = $item['venda_id'] ?? null;
            $valorPago = (float)($item['valor_pago'] ?? 0);
            $tipoPagamento = trim($item['tipo_pagamento'] ?? 'DINHEIRO');
            $cobradorId = $item['cobrador_id'] ?? null;

            // Se for colaborador autenticado de rua e não supervisor, trava o ID do cobrador nele mesmo
            if ($colaboradorLogado && !$ehSupervisor) {
                $cobradorId = $colaboradorLogado->id;
            }

            try {
                $parcela = null;
                if ($parcelaId) {
                    $parcela = Parcela::findOne(['id' => $parcelaId]);
                } elseif ($vendaId) {
                    // Pega primeira pendente da venda
                    $parcela = Parcela::find()
                        ->where(['venda_id' => $vendaId, 'status_parcela_codigo' => StatusParcela::PENDENTE])
                        ->orderBy(['numero_parcela' => SORT_ASC])
                        ->one();
                }

                if (!$parcela) {
                    throw new \Exception("Parcela não encontrada para o pagamento {$offlineId}");
                }

                $cartao = Venda::findOne(['id' => $parcela->venda_id]);
                if (!$cartao) {
                    throw new \Exception("Venda não encontrada para a parcela {$parcela->id}");
                }

                if ($valorPago <= 0) {
                    $valorPago = (float)$parcela->valor_parcela;
                }

                self::efetivarBaixaParcela($parcela, $cartao, $valorPago, $cobradorId, $tipoPagamento);

                $publicUrl = CartaoController::getPublicUrl($cartao->id);

                $sincronizados[] = [
                    'offline_id' => $offlineId,
                    'parcela_id' => (string)$parcela->id,
                    'venda_id' => (string)$cartao->id,
                    'numero_parcela' => $parcela->numero_parcela,
                    'valor_pago' => $valorPago,
                    'public_url' => $publicUrl,
                ];

            } catch (\Exception $e) {
                $erros[] = [
                    'offline_id' => $offlineId,
                    'erro' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => true,
            'total_processados' => count($pagamentosOffline),
            'total_sincronizados' => count($sincronizados),
            'sincronizados' => $sincronizados,
            'erros' => $erros,
        ];
    }
}

