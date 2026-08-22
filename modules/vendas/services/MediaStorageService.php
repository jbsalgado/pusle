<?php

namespace app\modules\vendas\services;

use Yii;
use app\components\TenantHelper;
use app\modules\vendas\models\ProdutoVideo;
use app\modules\vendas\models\ProdutoCard;
use app\modules\vendas\models\LojaConfiguracao;

/**
 * Service para cálculo e validação de cota multi-tenant de armazenamento em disco (Vídeos e Cards)
 */
class MediaStorageService
{
    const LIMITE_PADRAO_VIDEOS_MB = 50;
    const LIMITE_PADRAO_CARDS_MB = 50;

    /**
     * Resolve o ID do usuário/tenant de forma segura em contexto Web e Console CLI.
     */
    public static function resolveUsuarioId($usuarioId = null)
    {
        if (!empty($usuarioId)) {
            return $usuarioId;
        }

        if (Yii::$app->has('user') && Yii::$app->get('user') !== null && !Yii::$app->user->isGuest) {
            return \app\components\TenantHelper::getId();
        }

        return null;
    }

    /**
     * Retorna o limite de vídeos em MB para o tenant
     *
     * @param string|null $usuarioId
     * @return int Limite em Megabytes
     */
    public static function getLimiteVideosMB($usuarioId = null)
    {
        $usuarioId = self::resolveUsuarioId($usuarioId);
        if ($usuarioId) {
            $loja = LojaConfiguracao::findOne(['usuario_id' => $usuarioId]);
            if ($loja && isset($loja->limite_armazenamento_videos_mb) && (int)$loja->limite_armazenamento_videos_mb > 0) {
                return (int)$loja->limite_armazenamento_videos_mb;
            }
        }
        return self::LIMITE_PADRAO_VIDEOS_MB;
    }

    /**
     * Retorna o limite de cards em MB para o tenant
     *
     * @param string|null $usuarioId
     * @return int Limite em Megabytes
     */
    public static function getLimiteCardsMB($usuarioId = null)
    {
        $usuarioId = self::resolveUsuarioId($usuarioId);
        if ($usuarioId) {
            $loja = LojaConfiguracao::findOne(['usuario_id' => $usuarioId]);
            if ($loja && isset($loja->limite_armazenamento_cards_mb) && (int)$loja->limite_armazenamento_cards_mb > 0) {
                return (int)$loja->limite_armazenamento_cards_mb;
            }
        }
        return self::LIMITE_PADRAO_CARDS_MB;
    }

    /**
     * Calcula o espaço em Bytes utilizado pelos vídeos do tenant
     *
     * @param string|null $usuarioId
     * @return int Tamanho em bytes
     */
    public static function getUsoVideosBytes($usuarioId = null)
    {
        $usuarioId = self::resolveUsuarioId($usuarioId);
        if (!$usuarioId) {
            return 0;
        }

        $videos = ProdutoVideo::find()
            ->where(['usuario_id' => $usuarioId])
            ->all();

        $totalBytes = 0;
        foreach ($videos as $v) {
            if (!empty($v->video_path)) {
                $path = Yii::getAlias('@app/web/') . ltrim($v->video_path, '/');
                if (file_exists($path)) {
                    $totalBytes += filesize($path);
                }
            }
        }

        return $totalBytes;
    }

    /**
     * Calcula o espaço em Bytes utilizado pelos cards do tenant
     *
     * @param string|null $usuarioId
     * @return int Tamanho em bytes
     */
    public static function getUsoCardsBytes($usuarioId = null)
    {
        $usuarioId = self::resolveUsuarioId($usuarioId);
        if (!$usuarioId) {
            return 0;
        }

        $cards = ProdutoCard::find()
            ->where(['usuario_id' => $usuarioId])
            ->all();

        $totalBytes = 0;
        foreach ($cards as $c) {
            if (!empty($c->card_path)) {
                $path = Yii::getAlias('@app/web/') . ltrim($c->card_path, '/');
                if (file_exists($path)) {
                    $totalBytes += filesize($path);
                }
            }
        }

        return $totalBytes;
    }

    /**
     * Retorna estatísticas estruturadas do armazenamento de vídeos do tenant
     *
     * @param string|null $usuarioId
     * @return array
     */
    public static function getEstatisticasVideos($usuarioId = null)
    {
        $usuarioId = self::resolveUsuarioId($usuarioId);
        $limiteMb = self::getLimiteVideosMB($usuarioId);
        $limiteBytes = $limiteMb * 1024 * 1024;
        $usadoBytes = self::getUsoVideosBytes($usuarioId);
        $usadoMb = round($usadoBytes / (1024 * 1024), 2);
        $disponivelBytes = max(0, $limiteBytes - $usadoBytes);
        $disponivelMb = round($disponivelBytes / (1024 * 1024), 2);
        $percentual = $limiteBytes > 0 ? min(100, round(($usadoBytes / $limiteBytes) * 100, 1)) : 0;
        $excedido = $usadoBytes >= $limiteBytes;

        return [
            'limite_mb' => $limiteMb,
            'limite_bytes' => $limiteBytes,
            'usado_bytes' => $usadoBytes,
            'usado_mb' => $usadoMb,
            'disponivel_bytes' => $disponivelBytes,
            'disponivel_mb' => $disponivelMb,
            'percentual' => $percentual,
            'excedido' => $excedido,
        ];
    }

    /**
     * Retorna estatísticas estruturadas do armazenamento de cards do tenant
     *
     * @param string|null $usuarioId
     * @return array
     */
    public static function getEstatisticasCards($usuarioId = null)
    {
        $usuarioId = self::resolveUsuarioId($usuarioId);
        $limiteMb = self::getLimiteCardsMB($usuarioId);
        $limiteBytes = $limiteMb * 1024 * 1024;
        $usadoBytes = self::getUsoCardsBytes($usuarioId);
        $usadoMb = round($usadoBytes / (1024 * 1024), 2);
        $disponivelBytes = max(0, $limiteBytes - $usadoBytes);
        $disponivelMb = round($disponivelBytes / (1024 * 1024), 2);
        $percentual = $limiteBytes > 0 ? min(100, round(($usadoBytes / $limiteBytes) * 100, 1)) : 0;
        $excedido = $usadoBytes >= $limiteBytes;

        return [
            'limite_mb' => $limiteMb,
            'limite_bytes' => $limiteBytes,
            'usado_bytes' => $usadoBytes,
            'usado_mb' => $usadoMb,
            'disponivel_bytes' => $disponivelBytes,
            'disponivel_mb' => $disponivelMb,
            'percentual' => $percentual,
            'excedido' => $excedido,
        ];
    }

    /**
     * Valida se a geração de vídeo é permitida perante a cota do tenant
     *
     * @param string|null $usuarioId
     * @throws \Exception Se o limite for excedido
     */
    public static function validarEspacoVideos($usuarioId = null)
    {
        $stats = self::getEstatisticasVideos($usuarioId);
        if ($stats['excedido']) {
            throw new \Exception(sprintf(
                "Limite de armazenamento de vídeos excedido! Você já utilizou %.2f MB dos %d MB disponíveis para sua loja. Apague vídeos antigos para liberar espaço antes de gerar novos vídeos.",
                $stats['usado_mb'],
                $stats['limite_mb']
            ));
        }
    }

    /**
     * Valida se a geração de card é permitida perante a cota do tenant
     *
     * @param string|null $usuarioId
     * @throws \Exception Se o limite for excedido
     */
    public static function validarEspacoCards($usuarioId = null)
    {
        $stats = self::getEstatisticasCards($usuarioId);
        if ($stats['excedido']) {
            throw new \Exception(sprintf(
                "Limite de armazenamento de cards excedido! Você já utilizou %.2f MB dos %d MB disponíveis para sua loja. Apague cards antigos para liberar espaço antes de gerar novos cards.",
                $stats['usado_mb'],
                $stats['limite_mb']
            ));
        }
    }
}
