<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\httpclient\Client;

/**
 * Service Component desacoplado para integração nativa com a Meta Graph API (v19.0+).
 * 
 * Suporta:
 * - Troca de short-lived por long-lived tokens
 * - Obtenção de Páginas do Facebook e Contas do Instagram Business
 * - Publicação de Imagens e Vídeos/Reels no Instagram Business (Processamento Assíncrono com Containers)
 * - Publicação no Feed/Vídeos de Páginas do Facebook
 * - Tratamento estruturado de erros e exceções da Meta API
 */
class MetaGraphService extends Component
{
    /** @var string ID do App no Meta for Developers */
    public $appId;

    /** @var string Secret do App no Meta for Developers */
    public $appSecret;

    /** @var string Versão da Meta Graph API */
    public $apiVersion = 'v19.0';

    /** @var string URL Base da API da Meta */
    public $baseUrl = 'https://graph.facebook.com';

    /** @var Client Instância do Yii2 HTTP Client */
    private $_httpClient;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        // Carrega credenciais do params se não foram passadas na inicialização
        if (empty($this->appId) && !empty(Yii::$app->params['meta_app_id'])) {
            $this->appId = Yii::$app->params['meta_app_id'];
        }
        if (empty($this->appSecret) && !empty(Yii::$app->params['meta_app_secret'])) {
            $this->appSecret = Yii::$app->params['meta_app_secret'];
        }
        if (!empty(Yii::$app->params['meta_api_version'])) {
            $this->apiVersion = Yii::$app->params['meta_api_version'];
        }

        $this->_httpClient = new Client([
            'baseUrl' => rtrim($this->baseUrl, '/') . '/' . trim($this->apiVersion, '/'),
            'requestConfig' => [
                'format' => Client::FORMAT_JSON,
            ],
            'responseConfig' => [
                'format' => Client::FORMAT_JSON,
            ],
        ]);
    }

    /**
     * Troca um Short-Lived User Access Token por um Long-Lived User Access Token (válido por ~60 dias).
     *
     * @param string $shortLivedToken
     * @return array ['access_token' => string, 'token_type' => string, 'expires_in' => int]
     * @throws Exception
     */
    public function exchangeForLongLivedUserToken(string $shortLivedToken): array
    {
        if (empty($this->appId) || empty($this->appSecret)) {
            throw new Exception("Configuração do Meta App ID e Secret é obrigatória.");
        }

        $response = $this->_httpClient->get('oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ])->send();

        return $this->handleResponse($response, "Troca de Token Curto por Longo");
    }

    /**
     * Obtém as Páginas do Facebook vinculadas ao usuário e suas respectivas contas do Instagram Business.
     *
     * @param string $longLivedUserToken
     * @return array Lista de contas contendo facebook_page_id, page_name, page_access_token e instagram_business_account_id
     * @throws Exception
     */
    public function getConnectedPagesAndInstagramAccounts(string $longLivedUserToken): array
    {
        $response = $this->_httpClient->get('me/accounts', [
            'fields' => 'id,name,access_token,instagram_business_account',
            'access_token' => $longLivedUserToken,
        ])->send();

        $data = $this->handleResponse($response, "Busca de Páginas e Instagram Business");

        $result = [];
        if (!empty($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $item) {
                $pageId = $item['id'] ?? null;
                $pageName = $item['name'] ?? 'Página do Facebook';
                $pageAccessToken = $item['access_token'] ?? $longLivedUserToken;
                $igAccount = $item['instagram_business_account']['id'] ?? null;

                $result[] = [
                    'facebook_page_id' => $pageId,
                    'page_name' => $pageName,
                    'access_token' => $pageAccessToken,
                    'instagram_business_account_id' => $igAccount,
                ];
            }
        }

        return $result;
    }

    /**
     * Cria um container de mídia no Instagram Business (Passo 1 da Publicação no Instagram).
     *
     * @param string $igAccountId ID da Conta Instagram Business
     * @param string $accessToken Access Token da Página/Usuário
     * @param string $mediaType 'IMAGE' ou 'REELS' / 'VIDEO'
     * @param string $mediaUrl URL pública acessível da imagem ou vídeo
     * @param string|null $caption Legenda da publicação
     * @return string ID do Container (creation_id)
     * @throws Exception
     */
    public function createInstagramMediaContainer(
        string $igAccountId,
        string $accessToken,
        string $mediaType,
        string $mediaUrl,
        ?string $caption = null
    ): string {
        $params = [
            'access_token' => $accessToken,
        ];

        if (!empty($caption)) {
            $params['caption'] = $caption;
        }

        $upperMediaType = strtoupper($mediaType);
        if ($upperMediaType === 'REELS' || $upperMediaType === 'VIDEO') {
            $params['media_type'] = 'REELS';
            $params['video_url'] = $mediaUrl;
        } else {
            $params['image_url'] = $mediaUrl;
        }

        $response = $this->_httpClient->post("{$igAccountId}/media", $params)->send();
        $data = $this->handleResponse($response, "Criação de Container no Instagram ({$mediaType})");

        if (empty($data['id'])) {
            throw new Exception("Resposta da Meta API não retornou o creation_id do container.");
        }

        return (string) $data['id'];
    }

    /**
     * Checa o status de processamento do container de mídia no Instagram.
     * Útil principalmente para vídeos/Reels que são renderizados assincronamente pela Meta.
     *
     * @param string $creationId ID do container
     * @param string $accessToken Access Token
     * @return array ['status_code' => 'FINISHED'|'IN_PROGRESS'|'ERROR'|'EXPIRED'|'PUBLISHED', 'status' => string]
     * @throws Exception
     */
    public function checkInstagramContainerStatus(string $creationId, string $accessToken): array
    {
        $response = $this->_httpClient->get($creationId, [
            'fields' => 'status_code,status,error_message',
            'access_token' => $accessToken,
        ])->send();

        return $this->handleResponse($response, "Verificação de Status do Container ({$creationId})");
    }

    /**
     * Publica um container previamente criado e finalizado no Instagram Business (Passo 2/Final).
     *
     * @param string $igAccountId ID da Conta Instagram Business
     * @param string $creationId ID do Container (creation_id)
     * @param string $accessToken Access Token
     * @return string ID do post publicado na Meta
     * @throws Exception
     */
    public function publishInstagramContainer(string $igAccountId, string $creationId, string $accessToken): string
    {
        $response = $this->_httpClient->post("{$igAccountId}/media_publish", [
            'creation_id' => $creationId,
            'access_token' => $accessToken,
        ])->send();

        $data = $this->handleResponse($response, "Publicação Final no Instagram Container ({$creationId})");

        if (empty($data['id'])) {
            throw new Exception("Resposta da Meta API não retornou o ID da mídia publicada.");
        }

        return (string) $data['id'];
    }

    /**
     * Publica uma Imagem ou Vídeo/Reels diretamente na Página do Facebook.
     *
     * @param string $pageId ID da Página do Facebook
     * @param string $pageAccessToken Access Token da Página
     * @param string $mediaType 'IMAGE' ou 'REELS' / 'VIDEO'
     * @param string $mediaUrl URL pública acessível do recurso
     * @param string|null $caption Texto do post ou descrição do vídeo
     * @return string ID do post/vídeo publicado no Facebook
     * @throws Exception
     */
    public function publishToFacebookPage(
        string $pageId,
        string $pageAccessToken,
        string $mediaType,
        string $mediaUrl,
        ?string $caption = null
    ): string {
        $upperMediaType = strtoupper($mediaType);

        if ($upperMediaType === 'REELS' || $upperMediaType === 'VIDEO') {
            // Endpoint de Vídeos da Página no Facebook
            $params = [
                'file_url' => $mediaUrl,
                'access_token' => $pageAccessToken,
            ];
            if (!empty($caption)) {
                $params['description'] = $caption;
            }

            $response = $this->_httpClient->post("{$pageId}/videos", $params)->send();
            $data = $this->handleResponse($response, "Publicação de Vídeo na Página do Facebook");
        } else {
            // Endpoint de Fotos da Página no Facebook
            $params = [
                'url' => $mediaUrl,
                'access_token' => $pageAccessToken,
            ];
            if (!empty($caption)) {
                $params['caption'] = $caption;
            }

            $response = $this->_httpClient->post("{$pageId}/photos", $params)->send();
            $data = $this->handleResponse($response, "Publicação de Foto na Página do Facebook");
        }

        $publishedId = $data['id'] ?? ($data['post_id'] ?? null);
        if (empty($publishedId)) {
            throw new Exception("Facebook API não retornou o ID do post publicado.");
        }

        return (string) $publishedId;
    }

    /**
     * Tratamento centralizado de resposta HTTP e erros estruturados da Meta Graph API.
     *
     * @param \yii\httpclient\Response $response
     * @param string $context
     * @return array
     * @throws Exception
     */
    protected function handleResponse($response, string $context): array
    {
        $data = $response->getData();

        if ($response->getIsOk() && !isset($data['error'])) {
            return is_array($data) ? $data : [];
        }

        // Extrai código de erro e payload da Meta se existir
        $errorPayload = $data['error'] ?? [];
        $errorMessage = $errorPayload['message'] ?? 'Erro desconhecido ao comunicar com a Meta Graph API.';
        $errorCode = $errorPayload['code'] ?? $response->getStatusCode();
        $errorSubcode = $errorPayload['error_subcode'] ?? null;
        $fbTraceId = $errorPayload['fbtrace_id'] ?? null;

        $detailedMsg = sprintf(
            "Erro no Meta Graph API [%s]: %s (Code: %s%s, TraceID: %s)",
            $context,
            $errorMessage,
            $errorCode,
            $errorSubcode ? ", Subcode: {$errorSubcode}" : '',
            $fbTraceId ?: 'N/A'
        );

        Yii::error($detailedMsg, __METHOD__);

        $ex = new Exception($detailedMsg, (int) $errorCode);
        // Anexa dados técnicos adicionais à exceção
        throw $ex;
    }
}
