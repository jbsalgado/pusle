<?php

namespace app\modules\vendas\services;

use Yii;
use app\components\TenantHelper;
use app\modules\vendas\models\ProdutoVideo;
use app\modules\vendas\models\ProdutoCard;
use app\modules\vendas\models\LojaConfiguracao;
use yii\db\Expression;

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
        // 1. Limpa automaticamente cards expirados há mais de 24 horas para liberar espaço
        self::limparCardsExpirados(24);

        // 2. Tenta purgar possíveis arquivos órfãos não vinculados para manter a integridade
        self::limparArquivosOrfaosCards();

        $stats = self::getEstatisticasCards($usuarioId);
        if ($stats['excedido']) {
            throw new \Exception(sprintf(
                "Limite de 50 MB de armazenamento de cards da loja atingido! Você já utilizou %.2f MB dos %d MB disponíveis. Exclua cards antigos ou baixe os gerados para liberar espaço.",
                $stats['usado_mb'],
                $stats['limite_mb']
            ));
        }
    }

    /**
     * Remove arquivos de imagem órfãos no diretório uploads/cards que não estejam vinculados a nenhum registro no banco de dados.
     *
     * @return int Quantidade de arquivos removidos
     */
    public static function limparArquivosOrfaosCards()
    {
        $diretorio = Yii::getAlias('@app/web/uploads/cards');
        if (!is_dir($diretorio)) {
            return 0;
        }

        $todosCards = ProdutoCard::find()->select(['card_path'])->column();
        $caminhosRegistrados = array_map(function ($p) {
            return basename($p);
        }, array_filter($todosCards));

        $arquivosNoDisco = glob($diretorio . '/*');
        $removidos = 0;

        foreach ($arquivosNoDisco as $arquivo) {
            if (!is_file($arquivo)) {
                continue;
            }
            $nomeBase = basename($arquivo);
            // Se o arquivo tiver mais de 30 minutos e não constar no DB, remove
            if (!in_array($nomeBase, $caminhosRegistrados) && (time() - filemtime($arquivo) > 1800)) {
                if (@unlink($arquivo)) {
                    $removidos++;
                }
            }
        }

        return $removidos;
    }

    /**
     * Otimiza ou remove cards legados PNG do usuário e limpa arquivos órfãos.
     *
     * @param string|null $usuarioId
     * @return array
     */
    public static function otimizarCardsLegadosPng($usuarioId = null)
    {
        $usuarioId = self::resolveUsuarioId($usuarioId);
        if (!$usuarioId) {
            return ['otimizados' => 0, 'removidos_orfaos' => 0, 'espaco_liberado_mb' => 0];
        }

        $removidosOrfaos = self::limparArquivosOrfaosCards();

        $cardsPng = ProdutoCard::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['like', 'card_path', '.png'])
            ->all();

        $otimizadosCount = 0;
        $bytesLiberados = 0;

        foreach ($cardsPng as $c) {
            $caminhoAbsoluto = Yii::getAlias('@app/web/') . ltrim($c->card_path, '/');
            if (file_exists($caminhoAbsoluto)) {
                $tamanhoOriginal = filesize($caminhoAbsoluto);
                
                // Se o arquivo for PNG, converte para WebP se GD estiver instalado
                if (function_exists('imagecreatefrompng') && function_exists('imagewebp')) {
                    $novoCaminhoRelativo = preg_replace('/\.png$/i', '.webp', $c->card_path);
                    $novoCaminhoAbsoluto = Yii::getAlias('@app/web/') . ltrim($novoCaminhoRelativo, '/');

                    $img = @imagecreatefrompng($caminhoAbsoluto);
                    if ($img) {
                        imagepalettetotruecolor($img);
                        imagealphablending($img, true);
                        imagesavealpha($img, true);
                        if (@imagewebp($img, $novoCaminhoAbsoluto, 85)) {
                            imagedestroy($img);
                            @unlink($caminhoAbsoluto);
                            $tamanhoNovo = filesize($novoCaminhoAbsoluto);
                            $bytesLiberados += max(0, $tamanhoOriginal - $tamanhoNovo);
                            
                            $c->card_path = $novoCaminhoRelativo;
                            $c->card_url = preg_replace('/\.png$/i', '.webp', $c->card_url);
                            $c->save(false);
                            $otimizadosCount++;
                            continue;
                        }
                        imagedestroy($img);
                    }
                }
            }
        }

        return [
            'otimizados' => $otimizadosCount,
            'removidos_orfaos' => $removidosOrfaos,
            'espaco_liberado_mb' => round($bytesLiberados / (1024 * 1024), 2),
            'stats' => self::getEstatisticasCards($usuarioId)
        ];
    }

    /**
     * Remove cards e arquivos ZIP compactados com mais de 24 horas (ou período configurado).
     *
     * @param int $horas Limite de horas para expiração (padrão 24h)
     * @return array Resumo de itens removidos e espaço liberado
     */
    public static function limparCardsExpirados($horas = 24)
    {
        $horas = max(1, (int)$horas);
        $cardsExpirados = ProdutoCard::find()
            ->where(['<', 'data_criacao', new Expression("NOW() - INTERVAL '{$horas} HOURS'")])
            ->all();

        $cardsRemovidos = 0;
        $bytesLiberados = 0;

        foreach ($cardsExpirados as $card) {
            if (!empty($card->card_path)) {
                $caminhoAbsoluto = Yii::getAlias('@app/web/') . ltrim($card->card_path, '/');
                if (file_exists($caminhoAbsoluto)) {
                    $bytesLiberados += filesize($caminhoAbsoluto);
                    @unlink($caminhoAbsoluto);
                }
            }
            if ($card->delete()) {
                $cardsRemovidos++;
            }
        }

        // Limpar também arquivos ZIP antigos no diretório de cards
        $diretorioZip = Yii::getAlias('@app/web/uploads/cards/zip');
        $zipsRemovidos = 0;
        if (is_dir($diretorioZip)) {
            $arquivosZip = glob($diretorioZip . '/*.zip');
            $tempoLimite = time() - ($horas * 3600);
            foreach ($arquivosZip as $zipFile) {
                if (is_file($zipFile) && filemtime($zipFile) < $tempoLimite) {
                    $bytesLiberados += filesize($zipFile);
                    if (@unlink($zipFile)) {
                        $zipsRemovidos++;
                    }
                }
            }
        }

        // Purgar órfãos soltos
        $orfaosRemovidos = self::limparArquivosOrfaosCards();

        return [
            'cards_removidos' => $cardsRemovidos,
            'zips_removidos' => $zipsRemovidos,
            'orfaos_removidos' => $orfaosRemovidos,
            'espaco_liberado_mb' => round($bytesLiberados / (1024 * 1024), 2)
        ];
    }
}

