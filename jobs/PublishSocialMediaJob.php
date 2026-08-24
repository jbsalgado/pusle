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

        try {
            $publishedIds = [];

            // -----------------------------------------------------------------
            // 1. FLUXO INSTAGRAM BUSINESS
            // -----------------------------------------------------------------
            if (($post->platform === SocialPost::PLATFORM_INSTAGRAM || $post->platform === SocialPost::PLATFORM_BOTH) && !empty($account->instagram_business_account_id)) {
                
                // Passo 1.1: Criar Container de Mídia
                $creationId = $metaService->createInstagramMediaContainer(
                    $account->instagram_business_account_id,
                    $accessToken,
                    $post->media_type,
                    $post->media_url,
                    $post->caption
                );

                $post->markAsProcessing($creationId);

                // Passo 1.2: Polling de Status (Obrigatório para Vídeos/Reels)
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

                // Passo 1.3: Disparo da Publicação Final
                $igPublishedId = $metaService->publishInstagramContainer(
                    $account->instagram_business_account_id,
                    $creationId,
                    $accessToken
                );

                $publishedIds[] = "IG:" . $igPublishedId;
            }

            // -----------------------------------------------------------------
            // 2. FLUXO FACEBOOK PAGE
            // -----------------------------------------------------------------
            if (($post->platform === SocialPost::PLATFORM_FACEBOOK || $post->platform === SocialPost::PLATFORM_BOTH) && !empty($account->facebook_page_id)) {
                
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
                throw new Exception("Nenhum destino válido (Instagram ID ou Facebook Page ID) configurado para a publicação.");
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
