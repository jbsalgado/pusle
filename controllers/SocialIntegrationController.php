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
use app\models\SocialAccount;
use app\models\SocialPost;
use app\jobs\PublishSocialMediaJob;

/**
 * SocialIntegrationController — Controller para gerenciamento de conexões
 * com a Meta Graph API e agendamento/despacho de publicações sociais.
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
                    'connect' => ['POST'],
                    'accounts' => ['GET'],
                    'publish' => ['POST'],
                    'posts' => ['GET'],
                    'status' => ['GET'],
                ],
            ],
        ];
    }

    /**
     * Define o formato de resposta da requisição como JSON.
     */
    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    /**
     * Endpoint para conectar/vincular uma conta da Meta.
     * Recebe um Short-Lived User Access Token, realiza a troca por um Long-Lived Token (60 dias),
     * descobre as Páginas do FB e Contas IG Business associadas e salva/atualiza na tabela do tenant.
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
        $metaService = Yii::$app->get('metaGraphService', false);
        if (!$metaService) {
            $metaService = new MetaGraphService();
        }

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

            $savedAccounts = [];

            foreach ($pagesAndAccounts as $item) {
                $fbPageId = $item['facebook_page_id'];
                
                // Busca conta existente do tenant ou cria nova
                $account = SocialAccount::findOne([
                    'tenant_id' => $tenantId,
                    'facebook_page_id' => $fbPageId,
                ]);

                if (!$account) {
                    $account = new SocialAccount();
                    $account->tenant_id = $tenantId;
                    $account->facebook_page_id = $fbPageId;
                }

                $account->page_name = $item['page_name'];
                $account->instagram_business_account_id = $item['instagram_business_account_id'];
                $account->token_expires_at = $expiresAt;
                $account->status = SocialAccount::STATUS_ACTIVE;
                
                // Criptografa o token de acesso antes de salvar
                $account->setEncryptedAccessToken($item['access_token']);

                if ($account->save()) {
                    $savedAccounts[] = [
                        'id' => $account->id,
                        'page_name' => $account->page_name,
                        'facebook_page_id' => $account->facebook_page_id,
                        'instagram_business_account_id' => $account->instagram_business_account_id,
                        'status' => $account->status,
                        'token_expires_at' => $account->token_expires_at,
                    ];
                } else {
                    Yii::error("Erro ao salvar SocialAccount: " . json_encode($account->errors), __METHOD__);
                }
            }

            return [
                'success' => true,
                'message' => count($savedAccounts) . " conta(s) sociais conectada(s) com sucesso!",
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
     * Retorna a lista de contas sociais conectadas do tenant atual.
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
            ->asArray()
            ->all();

        // Remove tokens criptografados da resposta JSON para segurança
        foreach ($accounts as &$acc) {
            unset($acc['access_token']);
        }

        return [
            'success' => true,
            'data' => $accounts,
        ];
    }

    /**
     * Endpoint de despacho para agendar e enfileirar a publicação de uma mídia.
     * Validando payload e despachando PublishSocialMediaJob para a fila.
     *
     * POST /social-integration/publish
     * Body JSON:
     * {
     *   "social_account_id": "UUID-DA-CONTA",
     *   "platform": "INSTAGRAM" | "FACEBOOK" | "BOTH",
     *   "media_type": "IMAGE" | "REELS" | "VIDEO",
     *   "media_url": "https://meudominio.com/midia.mp4",
     *   "caption": "Legenda da publicação promocional"
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

        // Validações básicas de payload
        if (empty($accountId)) {
            throw new BadRequestHttpException("O parâmetro 'social_account_id' é obrigatório.");
        }
        if (empty($mediaUrl) || !filter_var($mediaUrl, FILTER_VALIDATE_URL)) {
            throw new BadRequestHttpException("O parâmetro 'media_url' deve ser uma URL pública HTTP/HTTPS válida.");
        }

        // Valida se a conta social pertence ao tenant do usuário logado
        $account = SocialAccount::findOne([
            'id' => $accountId,
            'tenant_id' => $tenantId,
        ]);

        if (!$account) {
            throw new NotFoundHttpException("Conta social selecionada não foi encontrada ou não pertence à sua loja.");
        }

        if ($account->status === SocialAccount::STATUS_EXPIRED || $account->isTokenExpired()) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'error' => 'O token de acesso desta conta social está expirado. Por favor, reconecte a conta.',
            ];
        }

        // Instancia e persiste o histórico de postagem com status PENDING
        $post = new SocialPost();
        $post->tenant_id = $tenantId;
        $post->social_account_id = $account->id;
        $post->platform = $platform;
        $post->media_type = $mediaType;
        $post->media_url = $mediaUrl;
        $post->caption = $caption;
        $post->status = SocialPost::STATUS_PENDING;

        if (!$post->save()) {
            Yii::$app->response->statusCode = 422;
            return [
                'success' => false,
                'errors' => $post->errors,
            ];
        }

        // Despacha a tarefa para a fila de execução assíncrona (yii2-queue)
        $jobId = Yii::$app->queue->push(new PublishSocialMediaJob([
            'postId' => $post->id,
        ]));

        Yii::$app->response->statusCode = 202; // Accepted
        return [
            'success' => true,
            'message' => 'Publicação enfileirada com sucesso!',
            'job_id' => $jobId,
            'post' => [
                'id' => $post->id,
                'platform' => $post->platform,
                'media_type' => $post->media_type,
                'status' => $post->status,
                'created_at' => $post->created_at,
            ],
        ];
    }

    /**
     * Retorna o histórico de publicações do tenant com filtros opcionais.
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
     * Retorna os detalhes e o status de um post específico.
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
}
