<?php

namespace app\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\RetryableJobInterface;
use app\models\SocialPost;
use app\models\SocialAccount;
use app\components\MetaGraphService;

/**
 * Job assíncrono para publicação de mídias (Imagens e Vídeos/Reels)
 * no Instagram Business e Facebook Pages via Meta Graph API.
 * 
 * Implementa retentativas configuráveis e polling de status para vídeos/Reels.
 */
class PublishSocialMediaJob extends BaseObject implements RetryableJobInterface
{
    /** @var string ID do registro SocialPost (UUID) */
    public $postId;

    /** @var int Número máximo de checagens do container de vídeo no Instagram */
    public $maxContainerChecks = 12;

    /** @var int Intervalo em segundos entre cada checagem do container */
    public $checkIntervalSeconds = 10;

    /**
     * Tempo máximo que o Job pode aguardar no worker (em segundos).
     *
     * @return int
     */
    public function getTtl()
    {
        return 600; // 10 minutos
    }

    /**
     * Define quantas tentativas o yii2-queue fará se a exceção persistir.
     *
     * @return int
     */
    public function getRetryTtl()
    {
        return 300; // 5 minutos entre retentativas de fila
    }

    /**
     * Quantas vezes a fila pode re-tentar executar o Job.
     *
     * @return bool|int
     */
    public function canRetry($attempt, $error)
    {
        // Re-tenta no máximo 3 vezes se for erro temporário de rede ou rate limit
        return $attempt < 3;
    }

    /**
     * Executado pelo worker do yii2-queue em segundo plano.
     *
     * @param \yii\queue\Queue $queue
     * @throws \Exception
     */
    public function execute($queue)
    {
        Yii::info("Iniciando Job de publicação social para SocialPost ID: {$this->postId}", __METHOD__);

        /** @var SocialPost|null $post */
        $post = SocialPost::findOne($this->postId);

        if (!$post) {
            Yii::error("SocialPost ID {$this->postId} não foi encontrado no banco de dados.", __METHOD__);
            return;
        }

        if ($post->status === SocialPost::STATUS_PUBLISHED) {
            Yii::warning("SocialPost ID {$this->postId} já consta como publicado.", __METHOD__);
            return;
        }

        /** @var SocialAccount|null $account */
        $account = $post->socialAccount;
        if (!$account) {
            $msg = "Conta social vinculada ao post {$this->postId} não existe mais.";
            $post->markAsFailed($msg);
            throw new \Exception($msg);
        }

        $accessToken = $account->getDecryptedAccessToken();
        if (empty($accessToken)) {
            $msg = "Token de acesso da conta social {$account->id} é inválido ou vazio.";
            $post->markAsFailed($msg);
            throw new \Exception($msg);
        }

        /** @var MetaGraphService $metaService */
        $metaService = Yii::$app->get('metaGraphService', false);
        if (!$metaService) {
            $metaService = new MetaGraphService();
        }

        /** @var \app\components\TikTokService $tikTokService */
        $tikTokService = Yii::$app->get('tikTokService', false);
        if (!$tikTokService) {
            $tikTokService = new \app\components\TikTokService();
        }

        try {
            $publishedIds = [];

            // =================================================================
            // FLUXO 1. TIKTOK CONTENT POSTING API
            // =================================================================
            if ($account->provider === SocialAccount::PROVIDER_TIKTOK || $post->platform === SocialPost::PLATFORM_TIKTOK) {
                Yii::info("Iniciando fluxo de publicação no TikTok para SocialPost ID: {$this->postId}", __METHOD__);

                // Checagem de expiração e auto-renovação de Access Token via Refresh Token
                if ($account->isTokenExpired() && !$account->isRefreshTokenExpired()) {
                    $refreshToken = $account->getDecryptedRefreshToken();
                    if (!empty($refreshToken)) {
                        try {
                            $tokenData = $tikTokService->refreshAccessToken($refreshToken);
                            $accessToken = $tokenData['access_token'];
                            $account->setEncryptedAccessToken($accessToken);
                            $account->token_expires_at = date('Y-m-d H:i:s', time() + (int)($tokenData['expires_in'] ?? 86400));
                            if (!empty($tokenData['refresh_token'])) {
                                $account->setEncryptedRefreshToken($tokenData['refresh_token']);
                                $account->tiktok_refresh_expires_at = date('Y-m-d H:i:s', time() + (int)($tokenData['refresh_expires_in'] ?? 31536000));
                            }
                            $account->status = SocialAccount::STATUS_ACTIVE;
                            $account->save(false);
                            Yii::info("Access Token do TikTok renovado com sucesso para a conta {$account->id}", __METHOD__);
                        } catch (\Throwable $tokenEx) {
                            Yii::error("Falha ao renovar token do TikTok: " . $tokenEx->getMessage(), __METHOD__);
                        }
                    }
                }

                $isMediaVideo = in_array($post->media_type, [SocialPost::MEDIA_TYPE_REELS, SocialPost::MEDIA_TYPE_VIDEO]);
                if ($isMediaVideo) {
                    $videoUrl = \app\helpers\SocialMediaHelper::ensureAbsoluteUrl($post->media_url);
                    $publishId = $tikTokService->publishVideo($accessToken, $videoUrl, $post->caption ?: '');
                } else {
                    $photoUrl = \app\helpers\SocialMediaHelper::ensureJpegForSocial($post->media_url);
                    $publishId = $tikTokService->publishPhotoPost(
                        $accessToken,
                        [$photoUrl],
                        mb_substr($post->caption ?: 'Oferta Especial', 0, 100),
                        $post->caption ?: ''
                    );
                }

                $post->markAsProcessing($publishId);

                // Polling de checagem de processamento no TikTok
                $checks = 0;
                $isFinished = false;
                while ($checks < $this->maxContainerChecks) {
                    sleep($this->checkIntervalSeconds);
                    $statusData = $tikTokService->checkPublishStatus($accessToken, $publishId);
                    $status = strtoupper($statusData['status'] ?? '');

                    Yii::info("Checagem de Publicação TikTok ({$publishId}): Tentativa {$checks}/{$this->maxContainerChecks} -> Status: {$status}", __METHOD__);

                    if ($status === 'SUCCESS' || $status === 'PUBLISH_COMPLETE') {
                        $isFinished = true;
                        break;
                    }

                    if ($status === 'FAILED') {
                        $failReason = $statusData['fail_reason'] ?? 'Falha no processamento pelo TikTok.';
                        throw new \Exception("TikTok rejeitou a publicação: {$failReason}");
                    }

                    $checks++;
                }

                $publishedIds[] = "TIKTOK:" . $publishId;
            }

            // =================================================================
            // FLUXO 2. INSTAGRAM BUSINESS (Feed, Reels & Stories)
            // =================================================================
            if ($account->provider !== SocialAccount::PROVIDER_TIKTOK && 
                ($post->platform === SocialPost::PLATFORM_INSTAGRAM || $post->platform === SocialPost::PLATFORM_BOTH || $post->platform === SocialPost::PLATFORM_ALL) && 
                !empty($account->instagram_business_account_id)) {
                
                // Passo 2.1: Criar Container de Mídia
                $creationId = $metaService->createInstagramMediaContainer(
                    $account->instagram_business_account_id,
                    $accessToken,
                    $post->media_type,
                    $post->media_url,
                    $post->caption
                );

                $post->markAsProcessing($creationId);

                // Passo 2.2: Polling de Status (Obrigatório para Vídeos/Reels)
                if ($post->media_type === SocialPost::MEDIA_TYPE_REELS || $post->media_type === SocialPost::MEDIA_TYPE_VIDEO) {
                    $isFinished = false;
                    $checks = 0;

                    while ($checks < $this->maxContainerChecks) {
                        $statusData = $metaService->checkInstagramContainerStatus($creationId, $accessToken);
                        $statusCode = strtoupper($statusData['status_code'] ?? '');

                        Yii::info("Checagem de Container ({$creationId}): Tentativa {$checks}/{$this->maxContainerChecks} -> Status: {$statusCode}", __METHOD__);

                        if ($statusCode === 'FINISHED') {
                            $isFinished = true;
                            break;
                        }

                        if ($statusCode === 'ERROR' || $statusCode === 'EXPIRED') {
                            $errorMsg = $statusData['error_message'] ?? "Container retornado com status de falha: {$statusCode}";
                            throw new \Exception("Falha no processamento do vídeo pela Meta API: {$errorMsg}");
                        }

                        $checks++;
                        sleep($this->checkIntervalSeconds);
                    }

                    if (!$isFinished) {
                        throw new \Exception("Timeout aguardando processamento do vídeo/Reels pela Meta API ({$this->maxContainerChecks} tentativas).");
                    }
                }

                // Passo 2.3: Disparo da Publicação Final
                $igPublishedId = $metaService->publishInstagramContainer(
                    $account->instagram_business_account_id,
                    $creationId,
                    $accessToken
                );

                $publishedIds[] = "IG:" . $igPublishedId;
            }

            // =================================================================
            // FLUXO 3. FACEBOOK PAGE
            // =================================================================
            if ($account->provider !== SocialAccount::PROVIDER_TIKTOK && 
                ($post->platform === SocialPost::PLATFORM_FACEBOOK || $post->platform === SocialPost::PLATFORM_BOTH || $post->platform === SocialPost::PLATFORM_ALL) && 
                !empty($account->facebook_page_id)) {
                
                $fbPublishedId = $metaService->publishToFacebookPage(
                    $account->facebook_page_id,
                    $accessToken,
                    $post->media_type,
                    $post->media_url,
                    $post->caption
                );

                $publishedIds[] = "FB:" . $fbPublishedId;
            }

            if (empty($publishedIds)) {
                throw new Exception("Nenhum canal social de destino válido (Instagram, Facebook ou TikTok) foi executado com sucesso.");
            }

            // -----------------------------------------------------------------
            // 3. ATUALIZAÇÃO DE SUCESSO NO BANCO
            // -----------------------------------------------------------------
            $finalMediaId = implode('|', $publishedIds);
            $post->markAsPublished($finalMediaId);

            Yii::info("Publicação concluída com sucesso para SocialPost ID: {$this->postId} (Media ID: {$finalMediaId})", __METHOD__);

        } catch (\Throwable $e) {
            Yii::error("Erro no PublishSocialMediaJob para SocialPost ID {$this->postId}: " . $e->getMessage(), __METHOD__);
            
            $post->markAsFailed([
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'failed_at' => date('Y-m-d H:i:s'),
            ]);

            throw $e;
        }
    }
}
