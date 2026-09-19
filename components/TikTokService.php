<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\httpclient\Client;

/**
 * TikTokService — Componente desacoplado para integração nativa com a TikTok Content Posting API (v2).
 * 
 * Suporta:
 * - Autenticação OAuth 2.0 (Geração de URL de autorização e troca de código)
 * - Renovação automática de token de acesso de 24h via Refresh Token
 * - Consulta de informações do criador (Creator Info)
 * - Publicação direta de Vídeos promocionais via PULL_FROM_URL
 * - Publicação de Fotos/Cards em Carrossel via Direct Post
 * - Polling de checagem do status de publicação
 */
class TikTokService extends Component
{
    /** @var string Client Key do App no TikTok for Developers */
    public $clientKey;

    /** @var string Client Secret do App no TikTok for Developers */
    public $clientSecret;

    /** @var string URL de Callback OAuth 2.0 */
    public $redirectUri;

    /** @var string URL Base da API v2 do TikTok */
    public $baseUrl = 'https://open.tiktokapis.com/v2/';

    /** @var string URL Base de Autorização do TikTok */
    public $authUrl = 'https://www.tiktok.com/v2/auth/authorize/';

    /** @var Client Instância do Yii2 HTTP Client */
    private $_httpClient;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        if (empty($this->clientKey) && !empty(Yii::$app->params['tiktok_client_key'])) {
            $this->clientKey = Yii::$app->params['tiktok_client_key'];
        }
        if (empty($this->clientSecret) && !empty(Yii::$app->params['tiktok_client_secret'])) {
            $this->clientSecret = Yii::$app->params['tiktok_client_secret'];
        }
        if (empty($this->redirectUri) && !empty(Yii::$app->params['tiktok_redirect_uri'])) {
            $this->redirectUri = Yii::$app->params['tiktok_redirect_uri'];
        }

        $this->_httpClient = new Client([
            'baseUrl' => $this->baseUrl,
            'requestConfig' => [
                'format' => Client::FORMAT_JSON,
            ],
            'responseConfig' => [
                'format' => Client::FORMAT_JSON,
            ],
        ]);
    }

    /**
     * Gera a URL para o lojista/afiliado autorizar o aplicativo no TikTok.
     *
     * @param string $state Código anti-CSRF ou identificador de sessão
     * @param array $scopes Escopos de permissão necessários
     * @return string
     */
    public function getAuthorizationUrl(string $state, array $scopes = ['user.info.basic', 'video.publish', 'video.upload']): string
    {
        if (empty($this->clientKey) || empty($this->redirectUri)) {
            throw new Exception("Configuração de 'tiktok_client_key' e 'tiktok_redirect_uri' é obrigatória.");
        }

        $params = [
            'client_key' => $this->clientKey,
            'scope' => implode(',', $scopes),
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
        ];

        return $this->authUrl . '?' . http_build_query($params);
    }

    /**
     * Troca o código temporário recebido no callback pelo Access Token e Refresh Token.
     *
     * @param string $code Código de autorização retornado pelo TikTok
     * @return array ['access_token', 'expires_in', 'refresh_token', 'refresh_expires_in', 'open_id', 'scope']
     * @throws Exception
     */
    public function exchangeCodeForTokens(string $code): array
    {
        $client = new Client();
        $response = $client->post('https://open.tiktokapis.com/v2/oauth/token/', [
            'client_key' => $this->clientKey,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
        ], [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Cache-Control' => 'no-cache',
        ])->send();

        $data = $this->handleResponse($response, "Troca de Código por Tokens do TikTok");
        $dataPayload = $data['data'] ?? $data;

        if (empty($dataPayload['access_token'])) {
            throw new Exception("Resposta do TikTok não retornou access_token válido.");
        }

        return $dataPayload;
    }

    /**
     * Renova um Access Token expirado utilizando o Refresh Token (válido por 365 dias).
     *
     * @param string $refreshToken
     * @return array
     * @throws Exception
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        $client = new Client();
        $response = $client->post('https://open.tiktokapis.com/v2/oauth/token/', [
            'client_key' => $this->clientKey,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ], [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->send();

        $data = $this->handleResponse($response, "Renovação de Access Token do TikTok");
        $dataPayload = $data['data'] ?? $data;

        if (empty($dataPayload['access_token'])) {
            throw new Exception("Falha ao renovar Access Token do TikTok com Refresh Token.");
        }

        return $dataPayload;
    }

    /**
     * Obtém informações básicas do perfil do usuário do TikTok.
     *
     * @param string $accessToken
     * @return array ['open_id', 'display_name', 'avatar_url']
     * @throws Exception
     */
    public function getUserInfo(string $accessToken): array
    {
        $response = $this->_httpClient->get('user/info/', [
            'fields' => 'open_id,union_id,avatar_url,display_name',
        ], [
            'Authorization' => 'Bearer ' . $accessToken,
        ])->send();

        $data = $this->handleResponse($response, "Busca de Dados do Usuário TikTok");
        return $data['data']['user'] ?? ($data['data'] ?? []);
    }

    /**
     * Consulta as permissões e restrições do criador de conteúdo no TikTok.
     * Etapa obrigatória do TikTok antes de iniciar publicação direta.
     *
     * @param string $accessToken
     * @return array
     * @throws Exception
     */
    public function queryCreatorInfo(string $accessToken): array
    {
        $response = $this->_httpClient->post('post/publish/creator/info/query/', [], [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->send();

        $data = $this->handleResponse($response, "Consulta de Informações do Criador TikTok");
        return $data['data'] ?? [];
    }

    /**
     * Dispara a publicação direta de um vídeo vertical (9:16) no perfil do TikTok.
     *
     * @param string $accessToken Token de acesso
     * @param string $videoUrl URL pública e acessível do arquivo de vídeo MP4
     * @param string $caption Legenda / Descrição com hashtags
     * @param array $options Opções adicionais de publicação
     * @return string ID de publicação retornado pelo TikTok (publish_id)
     * @throws Exception
     */
    public function publishVideo(
        string $accessToken,
        string $videoUrl,
        string $caption,
        array $options = []
    ): string {
        $privacyLevel = $options['privacy_level'] ?? 'PUBLIC_TO_EVERYONE';
        $disableDuet = !empty($options['disable_duet']);
        $disableStitch = !empty($options['disable_stitch']);
        $disableComment = !empty($options['disable_comment']);

        // TikTok limita o título/legenda a 2200 caracteres
        if (mb_strlen($caption) > 2200) {
            $caption = mb_substr($caption, 0, 2197) . '...';
        }

        $payload = [
            'post_info' => [
                'title' => $caption,
                'privacy_level' => $privacyLevel,
                'disable_duet' => $disableDuet,
                'disable_stitch' => $disableStitch,
                'disable_comment' => $disableComment,
                'video_cover_timestamp_ms' => 1000,
            ],
            'source_info' => [
                'source' => 'PULL_FROM_URL',
                'video_url' => $videoUrl,
            ],
        ];

        $response = $this->_httpClient->post('post/publish/video/init/', $payload, [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->send();

        $data = $this->handleResponse($response, "Início de Publicação de Vídeo no TikTok");
        $publishId = $data['data']['publish_id'] ?? null;

        if (empty($publishId)) {
            throw new Exception("TikTok API não retornou o publish_id da publicação de vídeo.");
        }

        return (string) $publishId;
    }

    /**
     * Dispara a publicação direta de Cards / Fotos em Carrossel no TikTok.
     *
     * @param string $accessToken Token de acesso
     * @param array $imageUrls Array de URLs públicas (JPEG/PNG) das imagens
     * @param string $title Título da postagem
     * @param string $description Descrição / Legenda detalhada
     * @return string ID de publicação retornado pelo TikTok (publish_id)
     * @throws Exception
     */
    public function publishPhotoPost(
        string $accessToken,
        array $imageUrls,
        string $title,
        string $description = ''
    ): string {
        if (empty($imageUrls)) {
            throw new Exception("É necessário fornecer ao menos uma imagem para a publicação no TikTok.");
        }

        $payload = [
            'post_info' => [
                'title' => mb_substr($title, 0, 150),
                'description' => mb_substr($description, 0, 2200),
                'privacy_level' => 'PUBLIC_TO_EVERYONE',
            ],
            'source_info' => [
                'source' => 'PULL_FROM_URL',
                'photo_cover_index' => 1,
                'photo_images' => array_values($imageUrls),
            ],
            'post_mode' => 'DIRECT_POST',
            'media_type' => 'PHOTO',
        ];

        $response = $this->_httpClient->post('post/publish/content/init/', $payload, [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->send();

        $data = $this->handleResponse($response, "Início de Publicação de Fotos no TikTok");
        $publishId = $data['data']['publish_id'] ?? null;

        if (empty($publishId)) {
            throw new Exception("TikTok API não retornou o publish_id para a postagem de fotos.");
        }

        return (string) $publishId;
    }

    /**
     * Consulta o status de processamento e publicação de um post no TikTok.
     *
     * @param string $accessToken
     * @param string $publishId
     * @return array ['status' => 'PROCESSING_DOWNLOAD'|'PROCESSING_UPLOAD'|'SUCCESS'|'FAILED', 'fail_reason' => ...]
     * @throws Exception
     */
    public function checkPublishStatus(string $accessToken, string $publishId): array
    {
        $response = $this->_httpClient->post('post/publish/status/fetch/', [
            'publish_id' => $publishId,
        ], [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->send();

        $data = $this->handleResponse($response, "Verificação de Status no TikTok ({$publishId})");
        return $data['data'] ?? [];
    }

    /**
     * Tratamento centralizado de resposta e erros estruturados da TikTok API v2.
     *
     * @param \yii\httpclient\Response $response
     * @param string $context
     * @return array
     * @throws Exception
     */
    protected function handleResponse($response, string $context): array
    {
        $data = $response->getData();

        if ($response->getIsOk()) {
            $error = $data['error'] ?? [];
            $code = $error['code'] ?? 'ok';

            if ($code === 'ok' || $code === 0 || empty($error)) {
                return is_array($data) ? $data : [];
            }

            $msg = $error['message'] ?? 'Erro retornado pela TikTok API.';
            throw new Exception("Erro no TikTok [{$context}]: {$msg} (Code: {$code})");
        }

        $error = $data['error'] ?? [];
        $errorMessage = $error['message'] ?? ($data['message'] ?? 'Erro de comunicação HTTP com o TikTok.');
        $errorCode = $error['code'] ?? $response->getStatusCode();

        $detailedMsg = sprintf("Falha na TikTok API [%s]: %s (HTTP %s, Code: %s)", $context, $errorMessage, $response->getStatusCode(), $errorCode);
        Yii::error($detailedMsg, __METHOD__);

        throw new Exception($detailedMsg, (int) $response->getStatusCode());
    }
}
