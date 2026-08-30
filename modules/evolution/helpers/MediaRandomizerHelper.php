<?php

namespace app\modules\evolution\helpers;

use Yii;

/**
 * Helper responsável por quebrar a correspondência de Hash MD5/SHA256 em mídias enviadas pelo WhatsApp.
 * Evita o fingerprinting e banimento algorítmico da Meta por envio em massa de arquivos idênticos.
 */
class MediaRandomizerHelper
{
    /**
     * Altera imperceptivelmente os dados binários da imagem para forçar a geração de um hash MD5 único.
     *
     * @param string $sourcePath Caminho absoluto ou relativo do arquivo de imagem
     * @return string Conteúdo Base64 limpo da imagem com hash alterado
     */
    public static function randomizeImageHash(string $sourcePath): string
    {
        if (empty($sourcePath)) {
            return '';
        }

        // Se for Base64 puro já passado
        if (strpos($sourcePath, 'data:image') === 0 || preg_match('/^[a-zA-Z0-9\/+=\s]+$/', $sourcePath) && strlen($sourcePath) > 500) {
            $rawBase64 = preg_replace('/^data:image\/[a-zA-Z0-9]+;base64,/i', '', trim($sourcePath));
            $rawBase64 = preg_replace('/\s+/', '', $rawBase64);
            $binaryData = base64_decode($rawBase64);
            if ($binaryData && function_exists('imagecreatefromstring')) {
                $img = @imagecreatefromstring($binaryData);
                if ($img) {
                    return self::processAndOutputImage($img);
                }
            }
            return $rawBase64;
        }

        if (!file_exists($sourcePath)) {
            return '';
        }

        // Se a extensão GD não estiver disponível, faz fallback para o arquivo original
        if (!function_exists('imagecreatefromjpeg') && !function_exists('imagecreatefrompng')) {
            return base64_encode(file_get_contents($sourcePath));
        }

        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            return base64_encode(file_get_contents($sourcePath));
        }

        $mime = $imageInfo['mime'] ?? '';
        $img = null;

        try {
            switch ($mime) {
                case 'image/jpeg':
                case 'image/jpg':
                    $img = @imagecreatefromjpeg($sourcePath);
                    break;
                case 'image/png':
                    $img = @imagecreatefrompng($sourcePath);
                    break;
                case 'image/webp':
                    if (function_exists('imagecreatefromwebp')) {
                        $img = @imagecreatefromwebp($sourcePath);
                    }
                    break;
                default:
                    $img = null;
            }

            if (!$img) {
                return base64_encode(file_get_contents($sourcePath));
            }

            return self::processAndOutputImage($img);
        } catch (\Throwable $t) {
            Yii::warning("MediaRandomizerHelper::randomizeImageHash — Erro ao processar imagem: " . $t->getMessage(), __METHOD__);
            return base64_encode(file_get_contents($sourcePath));
        }
    }

    /**
     * Aplica micro-alteração de 1 pixel e comprime com qualidade dinâmica gerando um novo hash binário.
     *
     * @param resource|\GdImage $img
     * @return string Base64 do resultado
     */
    private static function processAndOutputImage($img): string
    {
        $width = imagesx($img);
        $height = imagesy($img);

        if ($width > 0 && $height > 0) {
            // Escolhe um pixel aleatório em uma das 4 bordas extremas
            $edge = rand(1, 4);
            switch ($edge) {
                case 1: $randX = rand(0, min(3, $width - 1)); $randY = 0; break;
                case 2: $randX = rand(max(0, $width - 4), $width - 1); $randY = 0; break;
                case 3: $randX = 0; $randY = rand(0, min(3, $height - 1)); break;
                default: $randX = 0; $randY = rand(max(0, $height - 4), $height - 1); break;
            }

            $rgb = imagecolorat($img, $randX, $randY);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            // Injeta micro-variação de ±1 no canal vermelho
            $newR = max(0, min(255, $r + (rand(0, 1) === 1 ? 1 : -1)));
            $newColor = imagecolorallocate($img, $newR, $g, $b);
            imagesetpixel($img, $randX, $randY, $newColor);
        }

        ob_start();
        // Variação randômica de compressão JPEG entre 92 e 96 garante bytes finais imprevisíveis
        $quality = rand(92, 96);
        imagejpeg($img, null, $quality);
        $binaryOutput = ob_get_clean();

        if (is_resource($img) || (is_object($img) && get_class($img) === 'GdImage')) {
            imagedestroy($img);
        }

        return base64_encode($binaryOutput);
    }
}
