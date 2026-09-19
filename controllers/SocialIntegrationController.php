<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\components\TenantHelper;
use app\components\MetaGraphService;
use app\components\TikTokService;
use app\helpers\SocialMediaHelper;
use app\models\SocialAccount;
use app\models\SocialPost;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\Produto;
use app\jobs\PublishSocialMediaJob;

/**
 * SocialIntegrationController — Gerenciamento unificado de conexões com Meta (Instagram Business & Facebook Pages)
 * e TikTok Content Posting API v2, além de agendamento e despacho automatizado de mídias sociais.
 */
class SocialIntegrationController extends Controller
{
    /** @var bool Desabilita verificação CSRF para chamadas de API JSON */
    public $enableCsrfValidation = false;

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'connect'          => ['POST'],
                    'disconnect'       => ['POST'],
                    'accounts'         => ['GET'],
                    'publish'          => ['POST'],
                    'posts'            => ['GET'],
                    'status'           => ['GET'],
                    'generate-caption' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Formatação da resposta: HTML para páginas e JSON para APIs REST.
     */
    public function beforeAction($action)
    {
        if (in_array($action->id, ['index', 'tiktok-auth', 'tiktok-callback'])) {
            Yii::$app->response->format = Response::FORMAT_HTML;
        } else {
            Yii::$app->response->format = Response::FORMAT_JSON;
        }
        return parent::beforeAction($action);
    }

    /**
     * Painel Visual Central do Marketing Social (Social Hub).
     *
     * GET /social-integration ou /social-integration/index
     */
    public function actionIndex()
    {
        $tenantId = TenantHelper::getId();
        if (empty($tenantId)) {
            return $this->redirect(['/auth/login']);
        }

        $accounts = SocialAccount::find()
            ->where(['tenant_id' => $tenantId])
            ->andWhere(['!=', 'status', SocialAccount::STATUS_DISCONNECTED])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $recentPosts = SocialPost::find()
            ->where(['tenant_id' => $tenantId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(30)
            ->all();

        $stats = [
            'total_posts' => (int) SocialPost::find()->where(['tenant_id' => $tenantId])->count(),
            'published'   => (int) SocialPost::find()->where(['tenant_id' => $tenantId, 'status' => SocialPost::STATUS_PUBLISHED])->count(),
            'processing'  => (int) SocialPost::find()->where(['tenant_id' => $tenantId, 'status' => SocialPost::STATUS_PROCESSING])->count(),
            'failed'      => (int) SocialPost::find()->where(['tenant_id' => $tenantId, 'status' => SocialPost::STATUS_FAILED])->count(),
        ];

        $metaAppId = Yii::$app->params['meta_app_id'] ?? '';
        $tiktokClientKey = Yii::$app->params['tiktok_client_key'] ?? '';

        return $this->render('index', [
            'accounts'        => $accounts,
            'recentPosts'     => $recentPosts,
            'stats'           => $stats,
            'metaAppId'       => $metaAppId,
            'tiktokClientKey' => $tiktokClientKey,
        ]);
    }

    /**
     * Inicia o fluxo de autorização OAuth 2.0 com o TikTok.
     *
     * GET /social-integration/tiktok-auth
     */
    public function actionTiktokAuth()
    {
        $tenantId = TenantHelper::getId();
        if (empty($tenantId)) {
            return $this->redirect(['/auth/login']);
        }

        /** @var TikTokService $tikTokService */
        $tikTokService = Yii::$app->get('tikTokService', false) ?: new TikTokService();

        $state = bin2hex(random_bytes(16));
        Yii::$app->session->set('tiktok_oauth_state', $state);

        try {
            $authUrl = $tikTokService->getAuthorizationUrl($state);
            return $this->redirect($authUrl);
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', 'Erro ao iniciar conexão com o TikTok: ' . $e->getMessage());
            return $this->redirect(['index']);
        }
    }

    /**
     * Callback de retorno do OAuth 2.0 do TikTok.
     *
     * GET /social-integration/tiktok-callback?code=...&state=...
     */
    public function actionTiktokCallback()
    {
        $tenantId = TenantHelper::getId();
        if (empty($tenantId)) {
            return $this->redirect(['/auth/login']);
        }

        $code = Yii::$app->request->get('code');
        $state = Yii::$app->request->get('state');
        $error = Yii::$app->request->get('error');
        $errorDescription = Yii::$app->request->get('error_description');

        if (!empty($error)) {
            Yii::$app->session->setFlash('error', "Autorização cancelada ou recusada no TikTok: {$errorDescription}");
            return $this->redirect(['index']);
        }

        if (empty($code)) {
            Yii::$app->session->setFlash('error', "Código de autorização não retornado pelo TikTok.");
            return $this->redirect(['index']);
        }

        /** @var TikTokService $tikTokService */
        $tikTokService = Yii::$app->get('tikTokService', false) ?: new TikTokService();

        try {
            // Troca o código temporário por tokens definitivos
            $tokenData = $tikTokService->exchangeCodeForTokens($code);
            $accessToken = $tokenData['access_token'] ?? null;
            $refreshToken = $tokenData['refresh_token'] ?? null;
            $openId = $tokenData['open_id'] ?? null;
            $expiresIn = (int)($tokenData['expires_in'] ?? 86400);
            $refreshExpiresIn = (int)($tokenData['refresh_expires_in'] ?? 31536000);

            if (empty($accessToken) || empty($openId)) {
                throw new \Exception("TikTok não retornou dados de token válidos.");
            }

            // Busca dados do usuário para preencher perfil
            $userInfo = [];
            try {
                $userInfo = $tikTokService->getUserInfo($accessToken);
            } catch (\Throwable $e) {
                Yii::warning("Não foi possível obter dados extras do usuário TikTok: " . $e->getMessage(), __METHOD__);
            }

            $displayName = $userInfo['display_name'] ?? 'Conta TikTok';
            $avatarUrl = $userInfo['avatar_url'] ?? null;

            // Salva ou atualiza a conta do tenant
            $account = SocialAccount::findOne([
                'tenant_id' => $tenantId,
                'provider'  => SocialAccount::PROVIDER_TIKTOK,
                'tiktok_open_id' => $openId,
            ]);

            if (!$account) {
                $account = new SocialAccount();
                $account->tenant_id = $tenantId;
                $account->provider = SocialAccount::PROVIDER_TIKTOK;
                $account->tiktok_open_id = $openId;
            }

            $account->page_name = $displayName;
            $account->account_avatar_url = $avatarUrl;
            $account->setEncryptedAccessToken($accessToken);
            $account->token_expires_at = date('Y-m-d H:i:s', time() + $expiresIn);

            if (!empty($refreshToken)) {
                $account->setEncryptedRefreshToken($refreshToken);
                $account->tiktok_refresh_expires_at = date('Y-m-d H:i:s', time() + $refreshExpiresIn);
            }

            $colab = Colaborador::getColaboradorLogado();
            if ($colab) {
                $account->colaborador_id = $colab->id;
            }

            $account->status = SocialAccount::STATUS_ACTIVE;

            if (!$account->save()) {
                throw new \Exception("Erro ao salvar conta TikTok no banco: " . json_encode($account->errors));
            }

            Yii::$app->session->setFlash('success', "🎉 Conta do TikTok (@{$displayName}) conectada com sucesso!");
        } catch (\Throwable $e) {
            Yii::error("Erro no callback do TikTok: " . $e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', "Falha ao conectar conta TikTok: " . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    /**
     * Endpoint para conectar/vincular uma conta da Meta via Short-Lived Token.
     *
     * POST /social-integration/connect
     * Body JSON: { "short_lived_token": "..." }
     */
    public function actionConnect()
    {
        $tenantId = TenantHelper::getId();
        if (empty($tenantId)) {
            Yii::$app->response->statusCode = 401;
            return ['success' => false, 'error' => 'Usuário ou Tenant não autenticado.'];
        }

        $requestData = Yii::$app->request->getBodyParams();
        $shortLivedToken = $requestData['short_lived_token'] ?? null;

        if (empty($shortLivedToken)) {
            throw new BadRequestHttpException("O parâmetro 'short_lived_token' é obrigatório.");
        }

        /** @var MetaGraphService $metaService */
        $metaService = Yii::$app->get('metaGraphService', false) ?: new MetaGraphService();

        try {
            // 1. Troca por Token Longa Duração (~60 dias)
            $tokenData = $metaService->exchangeForLongLivedUserToken($shortLivedToken);
            $longLivedUserToken = $tokenData['access_token'] ?? null;
            $expiresInSeconds = $tokenData['expires_in'] ?? 5184000;

            if (empty($longLivedUserToken)) {
                throw new \Exception("Falha ao obter Long-Lived Access Token da Meta.");
            }

            $expiresAt = date('Y-m-d H:i:s', time() + (int)$expiresInSeconds);

            // 2. Busca Páginas do FB e Contas do Instagram Business associadas
            $pagesAndAccounts = $metaService->getConnectedPagesAndInstagramAccounts($longLivedUserToken);

            if (empty($pagesAndAccounts)) {
                return [
                    'success' => false,
                    'message' => 'Nenhuma Página do Facebook ou Conta do Instagram Business foi encontrada para este usuário.',
                ];
            }

            $colab = Colaborador::getColaboradorLogado();
            $colabId = $colab ? $colab->id : null;
            $savedAccounts = [];

            foreach ($pagesAndAccounts as $item) {
                $fbPageId = $item['facebook_page_id'];
                
                $account = SocialAccount::findOne([
                    'tenant_id' => $tenantId,
                    'facebook_page_id' => $fbPageId,
                ]);

                if (!$account) {
                    $account = new SocialAccount();
                    $account->tenant_id = $tenantId;
                    $account->facebook_page_id = $fbPageId;
                }

                $account->provider = SocialAccount::PROVIDER_META;
                $account->page_name = $item['page_name'];
                $account->instagram_business_account_id = $item['instagram_business_account_id'];
                $account->token_expires_at = $expiresAt;
                $account->status = SocialAccount::STATUS_ACTIVE;
                $account->colaborador_id = $colabId;
                $account->setEncryptedAccessToken($item['access_token']);

                if ($account->save()) {
                    $savedAccounts[] = [
                        'id' => $account->id,
                        'provider' => $account->provider,
                        'page_name' => $account->page_name,
                        'facebook_page_id' => $account->facebook_page_id,
                        'instagram_business_account_id' => $account->instagram_business_account_id,
                        'status' => $account->status,
                        'token_expires_at' => $account->token_expires_at,
                    ];
                }
            }

            return [
                'success' => true,
                'message' => count($savedAccounts) . " conta(s) Meta conectada(s) com sucesso!",
                'data' => $savedAccounts,
                'accounts' => $savedAccounts,
            ];

        } catch (\Throwable $e) {
            Yii::error("Erro no actionConnect do SocialIntegrationController: " . $e->getMessage(), __METHOD__);
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Desconecta uma conta social (Meta ou TikTok).
     *
     * POST /social-integration/disconnect
     */
    public function actionDisconnect()
    {
        $tenantId = TenantHelper::getId();
        if (empty($tenantId)) {
            Yii::$app->response->statusCode = 401;
            return ['success' => false, 'error' => 'Usuário ou Tenant não autenticado.'];
        }

        $id = Yii::$app->request->post('id') ?: Yii::$app->request->get('id');
        if (empty($id)) {
            throw new BadRequestHttpException("ID da conta é obrigatório.");
        }

        $account = SocialAccount::findOne([
            'id' => $id,
            'tenant_id' => $tenantId,
        ]);

        if (!$account) {
            throw new NotFoundHttpException("Conta social não encontrada.");
        }

        $account->status = SocialAccount::STATUS_DISCONNECTED;
        $account->save(false, ['status', 'updated_at']);

        return [
            'success' => true,
            'message' => 'Conta desconectada com sucesso!',
        ];
    }

    /**
     * Retorna a lista unificada de contas sociais ativas do tenant (Meta e TikTok).
     *
     * GET /social-integration/accounts
     */
    public function actionAccounts()
    {
        $tenantId = TenantHelper::getId();
        if (empty($tenantId)) {
            Yii::$app->response->statusCode = 401;
            return ['success' => false, 'error' => 'Usuário ou Tenant não autenticado.'];
        }

        $accounts = SocialAccount::find()
            ->where(['tenant_id' => $tenantId])
            ->andWhere(['!=', 'status', SocialAccount::STATUS_DISCONNECTED])
            ->orderBy(['provider' => SORT_ASC, 'page_name' => SORT_ASC])
            ->asArray()
            ->all();

        foreach ($accounts as &$acc) {
            unset($acc['access_token'], $acc['tiktok_refresh_token']);
        }

        return [
            'success'  => true,
            'data'     => $accounts,
            'accounts' => $accounts, // Compatibilidade retroativa
        ];
    }

    /**
     * Despacha a publicação de mídia (Imagem, Stories, Vídeo/Reels) para uma ou múltiplas redes.
     *
     * POST /social-integration/publish
     * Body JSON:
     * {
     *   "social_account_id": "UUID-DA-CONTA", // Opcional se platform='ALL'
     *   "platform": "INSTAGRAM" | "FACEBOOK" | "BOTH" | "TIKTOK" | "ALL",
     *   "media_type": "IMAGE" | "REELS" | "VIDEO" | "STORIES" | "CAROUSEL",
     *   "media_url": "https://...",
     *   "caption": "Legenda com hashtags"
     * }
     */
    public function actionPublish()
    {
        $tenantId = TenantHelper::getId();
        if (empty($tenantId)) {
            Yii::$app->response->statusCode = 401;
            return ['success' => false, 'error' => 'Usuário ou Tenant não autenticado.'];
        }

        $params = Yii::$app->request->getBodyParams();

        $accountId = $params['social_account_id'] ?? null;
        $platform = strtoupper($params['platform'] ?? SocialPost::PLATFORM_INSTAGRAM);
        $mediaType = strtoupper($params['media_type'] ?? SocialPost::MEDIA_TYPE_IMAGE);
        $mediaUrl = $params['media_url'] ?? null;
        $caption = $params['caption'] ?? null;

        if (empty($mediaUrl)) {
            throw new BadRequestHttpException("O parâmetro 'media_url' é obrigatório.");
        }

        // Garante que a URL seja absoluta
        $mediaUrl = SocialMediaHelper::ensureAbsoluteUrl($mediaUrl);

        $colab = Colaborador::getColaboradorLogado();
        $colabId = $colab ? $colab->id : null;

        // Se platform for ALL e não informou conta específica, publica em todas as contas conectadas
        $contasParaDisparo = [];
        if ($platform === SocialPost::PLATFORM_ALL && empty($accountId)) {
            $contasParaDisparo = SocialAccount::find()
                ->where(['tenant_id' => $tenantId, 'status' => SocialAccount::STATUS_ACTIVE])
                ->all();

            if (empty($contasParaDisparo)) {
                throw new NotFoundHttpException("Nenhuma conta social conectada e ativa encontrada para publicação.");
            }
        } else {
            if (empty($accountId)) {
                throw new BadRequestHttpException("O parâmetro 'social_account_id' é obrigatório para esta plataforma.");
            }

            $account = SocialAccount::findOne([
                'id' => $accountId,
                'tenant_id' => $tenantId,
            ]);

            if (!$account) {
                throw new NotFoundHttpException("Conta social selecionada não foi encontrada.");
            }

            $contasParaDisparo[] = $account;
        }

        $enfileirados = [];

        foreach ($contasParaDisparo as $acc) {
            $post = new SocialPost();
            $post->tenant_id = $tenantId;
            $post->social_account_id = $acc->id;
            $post->platform = ($acc->provider === SocialAccount::PROVIDER_TIKTOK) ? SocialPost::PLATFORM_TIKTOK : $platform;
            $post->media_type = $mediaType;
            $post->media_url = $mediaUrl;
            $post->caption = $caption;
            $post->colaborador_id = $colabId;
            $post->status = SocialPost::STATUS_PENDING;

            if ($post->save()) {
                $jobId = Yii::$app->queue->push(new PublishSocialMediaJob([
                    'postId' => $post->id,
                ]));

                $enfileirados[] = [
                    'post_id' => $post->id,
                    'job_id' => $jobId,
                    'account' => $acc->page_name,
                    'provider' => $acc->provider,
                ];
            } else {
                Yii::error("Erro ao salvar SocialPost: " . json_encode($post->errors), __METHOD__);
            }
        }

        if (empty($enfileirados)) {
            Yii::$app->response->statusCode = 422;
            return [
                'success' => false,
                'error' => 'Não foi possível enfileirar a publicação nas contas selecionadas.',
            ];
        }

        Yii::$app->response->statusCode = 202; // Accepted
        return [
            'success' => true,
            'message' => count($enfileirados) . ' publicação(ões) enfileirada(s) com sucesso para processamento!',
            'dispatches' => $enfileirados,
        ];
    }

    /**
     * Retorna o histórico de publicações com filtros opcionais.
     *
     * GET /social-integration/posts?status=PUBLISHED&limit=20
     */
    public function actionPosts()
    {
        $tenantId = TenantHelper::getId();
        if (empty($tenantId)) {
            Yii::$app->response->statusCode = 401;
            return ['success' => false, 'error' => 'Usuário ou Tenant não autenticado.'];
        }

        $status = Yii::$app->request->get('status');
        $platform = Yii::$app->request->get('platform');
        $limit = (int) Yii::$app->request->get('limit', 50);

        $query = SocialPost::find()
            ->where(['tenant_id' => $tenantId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit);

        if (!empty($status)) {
            $query->andWhere(['status' => $status]);
        }
        if (!empty($platform)) {
            $query->andWhere(['platform' => $platform]);
        }

        $posts = $query->asArray()->all();

        return [
            'success' => true,
            'data' => $posts,
        ];
    }

    /**
     * Retorna o status de um post específico.
     *
     * GET /social-integration/status?id=UUID
     */
    public function actionStatus($id = null)
    {
        $tenantId = TenantHelper::getId();
        if (empty($tenantId)) {
            Yii::$app->response->statusCode = 401;
            return ['success' => false, 'error' => 'Usuário ou Tenant não autenticado.'];
        }

        $postId = $id ?: Yii::$app->request->get('id');
        if (empty($postId)) {
            throw new BadRequestHttpException("O parâmetro 'id' do post é obrigatório.");
        }

        $post = SocialPost::findOne([
            'id' => $postId,
            'tenant_id' => $tenantId,
        ]);

        if (!$post) {
            throw new NotFoundHttpException("Registro de post não foi encontrado.");
        }

        return [
            'success' => true,
            'post' => [
                'id' => $post->id,
                'platform' => $post->platform,
                'media_type' => $post->media_type,
                'creation_id' => $post->creation_id,
                'published_media_id' => $post->published_media_id,
                'status' => $post->status,
                'error_payload' => $post->getParsedErrorPayload(),
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ],
        ];
    }

    /**
     * Gera copywriting inteligente para legendas de produtos com link de afiliado.
     *
     * POST /social-integration/generate-caption
     * Body JSON: { "produto_id": "UUID", "custom_caption": "..." }
     */
    public function actionGenerateCaption()
    {
        $tenantId = TenantHelper::getId();
        if (empty($tenantId)) {
            Yii::$app->response->statusCode = 401;
            return ['success' => false, 'error' => 'Usuário ou Tenant não autenticado.'];
        }

        $produtoId = Yii::$app->request->post('produto_id');
        $customCaption = Yii::$app->request->post('custom_caption');

        $produto = null;
        if (!empty($produtoId)) {
            $produto = Produto::findOne(['id' => $produtoId, 'usuario_id' => $tenantId]);
        }

        $colab = Colaborador::getColaboradorLogado();
        $caption = SocialMediaHelper::generateSocialCaption($produto, $customCaption, $colab);

        return [
            'success' => true,
            'caption' => $caption,
        ];
    }
}
