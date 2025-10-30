<?php
namespace app\modules\api\controllers;
use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\db\Expression;
use yii\db\JsonExpression;

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
        return $behaviors;
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
            
            // 5️⃣ GERAR REFERÊNCIA ÚNICA USANDO POSTGRES
            $externalReference = $this->gerarExternalReference($usuario['id']);
            
            // 6️⃣ CONFIGURAR URLs DE RETORNO
            $baseUrl = $this->getBaseUrl();
            $catalogoPath = $usuario['catalogo_path'] ?? 'catalogo';
            
            // 7️⃣ MONTAR PAYLOAD DA PREFERÊNCIA
            $statementDescriptor = isset($usuario['nome']) && !empty($usuario['nome']) 
                ? mb_substr($usuario['nome'], 0, 22) 
                : "Loja Online";
            
            $preferenceData = [
                "items" => $items,
                "payer" => $this->montarDadosPagador($request['cliente'] ?? []),
                "back_urls" => [
                    "success" => "{$baseUrl}/{$catalogoPath}/payment-success.html",
                    "failure" => "{$baseUrl}/{$catalogoPath}/payment-failure.html",
                    "pending" => "{$baseUrl}/{$catalogoPath}/payment-pending.html"
                ],
                "auto_return" => "approved",
                "external_reference" => $externalReference,
                "statement_descriptor" => $statementDescriptor,
                "notification_url" => "{$baseUrl}/pulse/web/index.php/api/mercado-pago/webhook",
                "expires" => true,
                "expiration_date_from" => date('c'),
                "expiration_date_to" => date('c', strtotime('+24 hours')),
                "metadata" => [
                    "usuario_id" => $usuario['id'],
                    "cliente_id" => $request['cliente_id'] ?? null,
                    "origem" => "pwa_catalogo"
                ]
            ];
            
            // 8️⃣ CRIAR PREFERÊNCIA COM SDK 3.7
            $client = new PreferenceClient();
            $preference = $client->create($preferenceData);
            
            Yii::info([
                'action' => 'preferencia_criada',
                'preference_id' => $preference->id,
                'external_reference' => $externalReference,
                'valor_total' => $valorTotal
            ], 'mercadopago');
            
            // 9️⃣ SALVAR NO POSTGRES PARA RASTREAMENTO
            $preferenciaId = $this->salvarPreferenciaNoBanco([
                'preference_id' => $preference->id,
                'external_reference' => $externalReference,
                'usuario_id' => $usuario['id'],
                'cliente_id' => $request['cliente_id'] ?? null,
                'valor_total' => $valorTotal,
                'status' => 'pending',
                'dados_request' => $request, // Será convertido para JSONB
                'ambiente' => $usuario['mercadopago_sandbox'] ? 'sandbox' : 'producao'
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
                'preferencia_local_id' => $preferenciaId
            ];
            
        } catch (MPApiException $e) {
            $transaction->rollBack();
            
            Yii::error([
                'action' => 'erro_criar_preferencia',
                'error' => $e->getMessage(),
                'api_response' => $e->getApiResponse(),
                'status_code' => $e->getStatusCode()
            ], 'mercadopago');
            
            return $this->errorResponse(
                'Erro na API do Mercado Pago: ' . $e->getMessage(),
                $e->getStatusCode()
            );
            
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
            
            // Buscar dados do pagamento na API do MP
            $pagamentoMP = $this->consultarPagamentoMP($paymentId);
            
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
     * Consulta dados do pagamento no Mercado Pago
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
            SELECT id, mercadopago_access_token
            FROM prest_usuarios
            WHERE api_de_pagamento = true
            AND gateway_pagamento = 'mercadopago'
            AND mercadopago_access_token IS NOT NULL
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
        
        if (empty($externalReference)) {
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
            'status_detail' => $statusDetail
        ], 'mercadopago');
        
        // Atualizar preferência
        $this->atualizarStatusPreferencia($externalReference, [
            'status' => $status,
            'payment_id' => $pagamento->id,
            'status_detail' => $statusDetail,
            'dados_pagamento' => json_encode($pagamento)
        ]);
        
        // Ações baseadas no status
        switch ($status) {
            case 'approved':
                $this->criarPedidoNoSistema($externalReference, $pagamento);
                break;
                
            case 'rejected':
            case 'cancelled':
                $this->cancelarPedido($externalReference, "Pagamento {$status}: {$statusDetail}");
                break;
                
            case 'refunded':
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