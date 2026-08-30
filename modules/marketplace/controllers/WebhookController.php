<?php

namespace app\modules\marketplace\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\Response;
use app\modules\marketplace\models\MarketplaceConfig;
use app\modules\marketplace\jobs\ProcessarWebhookJob;
use app\modules\marketplace\components\WebhookSignatureValidator;

/**
 * WebhookController - Ingestão Assíncrona e Segura de Webhooks (Fast-ACK Architecture)
 * 
 * Recebe notificações de eventos dos marketplaces em tempo real, valida assinaturas,
 * identifica o tenant determinísticamente e enfileira o processamento pesado na Queue.
 */
class WebhookController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['receive'], // Webhook público
                    ],
                ],
            ],
        ];
    }

    /**
     * Desabilita validação CSRF para webhooks
     */
    public function beforeAction($action)
    {
        if ($action->id === 'receive') {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    /**
     * Ponto de entrada universal para webhooks de marketplaces
     * 
     * @param string $marketplace Nome do marketplace (mercado-livre, shopee, magalu, temu, etc)
     * @return Response
     */
    public function actionReceive($marketplace)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $rawBody = Yii::$app->request->getRawBody();
        $headers = $this->getHeadersArray();
        $marketplaceName = $this->normalizeMarketplaceName($marketplace);

        Yii::info(sprintf(
            "[WebhookController] Recebido de %s. Headers: %s. Body: %s",
            $marketplaceName,
            json_encode($headers),
            $rawBody
        ), 'marketplace');

        try {
            // 1. Identificar configuração do seller de forma estritamente isolada (Sem Fallback Inseguro)
            $config = $this->resolveMarketplaceConfig($marketplaceName, $rawBody, $headers);

            if (!$config) {
                Yii::warning(sprintf(
                    "[WebhookController] Nenhuma conta ativa encontrada para %s no payload fornecido.",
                    $marketplaceName
                ), 'marketplace');

                // Retorna 200 OK para evitar reenvio infinito de contas desativadas ou testes de ping
                return $this->successResponse([
                    'status' => 'ignored',
                    'reason' => 'Conta ou seller_id não associado a nenhum tenant ativo',
                ]);
            }

            // 2. Validação da assinatura criptográfica se configurada
            if (!$this->validateWebhookSignature($marketplaceName, $config, $rawBody, $headers)) {
                Yii::warning("[WebhookController] Assinatura do webhook inválida para {$marketplaceName}.", 'marketplace');
                return $this->errorResponse('Assinatura inválida', 401);
            }

            // 3. Fast ACK: Enfileira processamento assíncrono e responde imediatamente
            if (Yii::$app->has('queue')) {
                Yii::$app->queue->push(new ProcessarWebhookJob([
                    'marketplace' => $marketplaceName,
                    'configId' => $config->id,
                    'rawBody' => $rawBody,
                    'headers' => $headers,
                ]));
            } else {
                // Fallback síncrono emergencial caso fila esteja desativada
                $job = new ProcessarWebhookJob([
                    'marketplace' => $marketplaceName,
                    'configId' => $config->id,
                    'rawBody' => $rawBody,
                    'headers' => $headers,
                ]);
                $job->execute(null);
            }

            return $this->successResponse([
                'status' => 'queued',
                'marketplace' => $marketplaceName,
                'received_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            Yii::error("[WebhookController] Exceção crítica: " . $e->getMessage(), 'marketplace');
            return $this->errorResponse('Erro interno ao processar webhook', 500, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Identifica a conta/configuração do seller sem risco de vazamento entre tenants
     */
    protected function resolveMarketplaceConfig(string $marketplace, string $rawBody, array $headers): ?MarketplaceConfig
    {
        $payload = json_decode($rawBody, true) ?: [];

        // 1. Verificação por parâmetro explícito na URL (?config_id=UUID ou ?token=SECRET)
        $configId = Yii::$app->request->get('config_id');
        if ($configId) {
            return MarketplaceConfig::findOne(['id' => $configId, 'ativo' => true]);
        }

        // 2. Extração do seller_id externo conforme cada plataforma
        $sellerId = null;

        switch ($marketplace) {
            case MarketplaceConfig::MARKETPLACE_MERCADO_LIVRE:
                // Mercado Livre envia "user_id" no JSON
                $sellerId = $payload['user_id'] ?? null;
                break;

            case MarketplaceConfig::MARKETPLACE_SHOPEE:
                // Shopee envia "shop_id" no JSON ou query param
                $sellerId = $payload['shop_id'] ?? Yii::$app->request->get('shop_id');
                break;

            case MarketplaceConfig::MARKETPLACE_MAGAZINE_LUIZA:
                $sellerId = $payload['seller_id'] ?? $headers['x-seller-id'] ?? Yii::$app->request->get('seller_id');
                break;

            case MarketplaceConfig::MARKETPLACE_TEMU:
                $sellerId = $payload['seller_id'] ?? $payload['mall_id'] ?? Yii::$app->request->get('seller_id');
                break;

            case MarketplaceConfig::MARKETPLACE_IFOOD:
                $sellerId = $payload['merchantId'] ?? $payload['merchant_id'] ?? null;
                break;
        }

        if ($sellerId) {
            $config = MarketplaceConfig::findBySellerIdExterno($marketplace, (string)$sellerId);
            if ($config) {
                return $config;
            }
        }

        return null;
    }

    /**
     * Valida assinatura criptográfica
     */
    protected function validateWebhookSignature(string $marketplace, MarketplaceConfig $config, string $rawBody, array $headers): bool
    {
        $validator = new WebhookSignatureValidator();

        if ($marketplace === MarketplaceConfig::MARKETPLACE_MERCADO_LIVRE && !empty($config->client_secret)) {
            $signature = $headers['x-signature'] ?? null;
            if ($signature) {
                return $validator->validateMercadoLivre($signature, $rawBody, $config->client_secret);
            }
        }

        if ($marketplace === MarketplaceConfig::MARKETPLACE_SHOPEE && !empty($config->client_secret)) {
            $authorization = $headers['authorization'] ?? null;
            if ($authorization) {
                return $validator->validateShopee($authorization, $rawBody, $config->client_secret);
            }
        }

        // Por padrão, se não há header de assinatura obrigatório, aceita
        return true;
    }

    /**
     * Normaliza nome do marketplace
     */
    protected function normalizeMarketplaceName(string $marketplace): string
    {
        $map = [
            'mercado-livre' => MarketplaceConfig::MARKETPLACE_MERCADO_LIVRE,
            'mercadolivre' => MarketplaceConfig::MARKETPLACE_MERCADO_LIVRE,
            'ml' => MarketplaceConfig::MARKETPLACE_MERCADO_LIVRE,
            'shopee' => MarketplaceConfig::MARKETPLACE_SHOPEE,
            'magazine-luiza' => MarketplaceConfig::MARKETPLACE_MAGAZINE_LUIZA,
            'magazineluiza' => MarketplaceConfig::MARKETPLACE_MAGAZINE_LUIZA,
            'magalu' => MarketplaceConfig::MARKETPLACE_MAGAZINE_LUIZA,
            'temu' => MarketplaceConfig::MARKETPLACE_TEMU,
            'amazon' => MarketplaceConfig::MARKETPLACE_AMAZON,
            'ifood' => MarketplaceConfig::MARKETPLACE_IFOOD,
        ];

        $normalized = strtolower(trim($marketplace));
        return $map[$normalized] ?? strtoupper($marketplace);
    }

    /**
     * Obtém headers normalizados em minúsculas
     */
    protected function getHeadersArray(): array
    {
        $headers = [];
        foreach (Yii::$app->request->headers as $name => $values) {
            $headers[strtolower($name)] = is_array($values) ? $values[0] : $values;
        }
        return $headers;
    }

    /**
     * Resposta de sucesso padronizada
     */
    protected function successResponse(array $data = []): array
    {
        Yii::$app->response->statusCode = 200;
        return array_merge([
            'success' => true,
            'message' => 'Webhook recebido com sucesso',
        ], $data);
    }

    /**
     * Resposta de erro padronizada
     */
    protected function errorResponse(string $message, int $statusCode = 400, array $data = []): array
    {
        Yii::$app->response->statusCode = $statusCode;
        return array_merge([
            'success' => false,
            'error' => $message,
        ], $data);
    }
}
