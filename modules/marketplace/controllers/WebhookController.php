<?php

namespace app\modules\marketplace\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\Response;
use app\modules\marketplace\components\MercadoLivreWebhookHandler;
use app\modules\marketplace\components\WebhookSignatureValidator;
use app\modules\marketplace\models\MarketplaceConfig;

/**
 * Webhook Controller para receber notificações dos marketplaces
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
                        'actions' => ['receive'], // Webhook é público (sem autenticação)
                    ],
                ],
            ],
        ];
    }

    /**
     * Desabilita CSRF para webhooks
     */
    public function beforeAction($action)
    {
        if ($action->id === 'receive') {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    /**
     * Recebe webhook de marketplace
     * 
     * @param string $marketplace Nome do marketplace (mercado-livre, shopee, etc)
     * @return Response
     */
    public function actionReceive($marketplace)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $rawBody = Yii::$app->request->getRawBody();
        $headers = $this->getHeadersArray();

        // Log inicial
        Yii::info(sprintf(
            'Webhook recebido de %s - Headers: %s - Body: %s',
            $marketplace,
            json_encode($headers),
            $rawBody
        ), __METHOD__);

        try {
            // 1. Normalizar nome do marketplace
            $marketplaceName = $this->normalizeMarketplaceName($marketplace);

            // 2. Buscar configuração do marketplace
            $config = $this->getMarketplaceConfig($marketplaceName, $rawBody, $headers);

            if (!$config) {
                return $this->errorResponse('Marketplace não configurado', 404);
            }

            // 3. Obter handler apropriado
            $handler = $this->getHandler($marketplaceName, $config);

            if (!$handler) {
                return $this->errorResponse('Handler não disponível para este marketplace', 501);
            }

            // 4. Processar webhook
            $result = $handler->process($rawBody, $headers);

            // 5. Retornar resultado
            if ($result['success']) {
                return $this->successResponse($result);
            } else {
                return $this->errorResponse($result['error'], 422, $result);
            }
        } catch (\Exception $e) {
            Yii::error('Erro ao processar webhook: ' . $e->getMessage(), __METHOD__);
            Yii::error($e->getTraceAsString(), __METHOD__);

            return $this->errorResponse('Erro interno ao processar webhook', 500, [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Normaliza nome do marketplace
     * 
     * @param string $marketplace Nome recebido na URL
     * @return string Nome normalizado
     */
    protected function normalizeMarketplaceName($marketplace)
    {
        $map = [
            'mercado-livre' => 'MERCADO_LIVRE',
            'mercadolivre' => 'MERCADO_LIVRE',
            'ml' => 'MERCADO_LIVRE',
            'shopee' => 'SHOPEE',
            'magazine-luiza' => 'MAGAZINE_LUIZA',
            'magazineluiza' => 'MAGAZINE_LUIZA',
            'magalu' => 'MAGAZINE_LUIZA',
            'amazon' => 'AMAZON',
        ];

        $normalized = strtolower($marketplace);
        return $map[$normalized] ?? strtoupper($marketplace);
    }

    /**
     * Busca configuração do marketplace
     * 
     * @param string $marketplace Nome do marketplace
     * @param string $rawBody Corpo da requisição
     * @param array $headers Headers
     * @return MarketplaceConfig|null
     */
    protected function getMarketplaceConfig($marketplace, $rawBody, $headers)
    {
        // Para Mercado Livre, tenta identificar usuário pelo user_id no payload
        if ($marketplace === 'MERCADO_LIVRE') {
            $payload = json_decode($rawBody, true);
            $userId = $payload['user_id'] ?? null;

            if ($userId) {
                // Busca config que tenha este user_id nas credenciais
                // (Mercado Livre envia o user_id do vendedor)
                $config = MarketplaceConfig::find()
                    ->where(['marketplace' => $marketplace])
                    ->andWhere(['ativo' => true])
                    ->one();

                if ($config) {
                    return $config;
                }
            }
        }

        // Fallback: busca primeira configuração ativa do marketplace
        return MarketplaceConfig::findOne([
            'marketplace' => $marketplace,
            'ativo' => true,
        ]);
    }

    /**
     * Obtém handler apropriado para o marketplace
     * 
     * @param string $marketplace Nome do marketplace
     * @param MarketplaceConfig $config Configuração
     * @return BaseWebhookHandler|null
     */
    protected function getHandler($marketplace, $config)
    {
        $handlerConfig = [
            'usuario_id' => $config->usuario_id,
            'client_id' => $config->client_id,
            'client_secret' => $config->client_secret,
            'access_token' => $config->access_token,
        ];

        switch ($marketplace) {
            case 'MERCADO_LIVRE':
                return new MercadoLivreWebhookHandler($marketplace, $handlerConfig);

            case 'SHOPEE':
                // TODO: Implementar ShopeeWebhookHandler
                Yii::warning('ShopeeWebhookHandler ainda não implementado', __METHOD__);
                return null;

            case 'MAGAZINE_LUIZA':
                // TODO: Implementar MagazineLuizaWebhookHandler
                Yii::warning('MagazineLuizaWebhookHandler ainda não implementado', __METHOD__);
                return null;

            case 'AMAZON':
                // TODO: Implementar AmazonWebhookHandler
                Yii::warning('AmazonWebhookHandler ainda não implementado', __METHOD__);
                return null;

            default:
                Yii::error("Marketplace não suportado: {$marketplace}", __METHOD__);
                return null;
        }
    }

    /**
     * Obtém headers como array associativo
     * 
     * @return array
     */
    protected function getHeadersArray()
    {
        $headers = [];
        foreach (Yii::$app->request->headers as $name => $values) {
            $headers[strtolower($name)] = is_array($values) ? $values[0] : $values;
        }
        return $headers;
    }

    /**
     * Retorna resposta de sucesso
     * 
     * @param array $data Dados adicionais
     * @return array
     */
    protected function successResponse($data = [])
    {
        Yii::$app->response->statusCode = 200;
        return array_merge([
            'success' => true,
            'message' => 'Webhook processado com sucesso',
        ], $data);
    }

    /**
     * Retorna resposta de erro
     * 
     * @param string $message Mensagem de erro
     * @param int $statusCode Código HTTP
     * @param array $data Dados adicionais
     * @return array
     */
    protected function errorResponse($message, $statusCode = 400, $data = [])
    {
        Yii::$app->response->statusCode = $statusCode;
        return array_merge([
            'success' => false,
            'error' => $message,
        ], $data);
    }
}
