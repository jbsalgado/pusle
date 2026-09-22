<?php

namespace app\modules\vendas\helpers;

use Yii;
use yii\helpers\Url;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;
use app\modules\vendas\models\ClienteInbox;

/**
 * Helper para processamento, compressão inteligente e ciclo de vida
 * das mídias (fotos e imagens) do canal de comunicação interno e direct hub.
 */
class ChatMediaHelper
{
    const UPLOAD_DIR = '@app/web/uploads/chat';
    const URL_PREFIX = '/uploads/chat';
    const TEMP_UPLOAD_DIR = '@app/web/uploads/chat_temp';

    const MAX_WIDTH = 1280;
    const MAX_HEIGHT = 1280;
    const WEBP_QUALITY = 82;

    /**
     * Salva e comprime uma foto/imagem enviada, convertendo para WebP e redimensionando
     * proporcionalmente para no máximo 1280px para economia de disco de até 95%.
     *
     * @param UploadedFile|null $uploaded
     * @return array
     */
    public static function salvarEComprimirUpload(?UploadedFile $uploaded): array
    {
        if (!$uploaded || $uploaded->hasError) {
            return ['success' => false, 'message' => 'Nenhum arquivo válido enviado.'];
        }

        // Valida extensões permitidas
        $ext = strtolower($uploaded->extension);
        $extsValidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $extsValidas)) {
            return ['success' => false, 'message' => 'Formato de imagem inválido. Use JPG, PNG ou WebP.'];
        }

        $uploadDir = Yii::getAlias(self::UPLOAD_DIR);
        if (!is_dir($uploadDir)) {
            FileHelper::createDirectory($uploadDir, 0775, true);
        }

        $tempPath = $uploaded->tempName;
        if (!file_exists($tempPath)) {
            return ['success' => false, 'message' => 'Arquivo temporário não encontrado.'];
        }

        $tamanhoOriginal = filesize($tempPath);
        $salvoComSucesso = false;

        $nomeBase = 'chat_' . date('Ymd_His') . '_' . substr(md5(uniqid(rand(), true)), 0, 8);
        $nomeArquivoWebp = $nomeBase . '.webp';
        $destPathWebp = $uploadDir . DIRECTORY_SEPARATOR . $nomeArquivoWebp;

        // Processamento com GD (Redimensionamento + Conversão WebP)
        if (function_exists('imagewebp') && function_exists('imagecreatetruecolor')) {
            $img = null;
            switch ($ext) {
                case 'jpg':
                case 'jpeg':
                    if (function_exists('imagecreatefromjpeg')) {
                        $img = @imagecreatefromjpeg($tempPath);
                    }
                    break;
                case 'png':
                    if (function_exists('imagecreatefrompng')) {
                        $img = @imagecreatefrompng($tempPath);
                    }
                    break;
                case 'webp':
                    if (function_exists('imagecreatefromwebp')) {
                        $img = @imagecreatefromwebp($tempPath);
                    }
                    break;
                case 'gif':
                    if (function_exists('imagecreatefromgif')) {
                        $img = @imagecreatefromgif($tempPath);
                    }
                    break;
            }

            if ($img) {
                // Ajusta orientação EXIF se disponível
                if (function_exists('exif_read_data') && ($ext === 'jpg' || $ext === 'jpeg')) {
                    $exif = @exif_read_data($tempPath);
                    if (!empty($exif['Orientation'])) {
                        switch ($exif['Orientation']) {
                            case 3:
                                $img = imagerotate($img, 180, 0);
                                break;
                            case 6:
                                $img = imagerotate($img, -90, 0);
                                break;
                            case 8:
                                $img = imagerotate($img, 90, 0);
                                break;
                        }
                    }
                }

                $larguraOriginal = imagesx($img);
                $alturaOriginal = imagesy($img);

                $novaLargura = $larguraOriginal;
                $novaAltura = $alturaOriginal;

                if ($larguraOriginal > self::MAX_WIDTH || $alturaOriginal > self::MAX_HEIGHT) {
                    $ratio = min(self::MAX_WIDTH / $larguraOriginal, self::MAX_HEIGHT / $alturaOriginal);
                    $novaLargura = (int)round($larguraOriginal * $ratio);
                    $novaAltura = (int)round($alturaOriginal * $ratio);
                }

                $novaImg = imagecreatetruecolor($novaLargura, $novaAltura);

                // Preserva transparência para PNG e WebP
                imagealphablending($novaImg, false);
                imagesavealpha($novaImg, true);

                imagecopyresampled(
                    $novaImg, $img,
                    0, 0, 0, 0,
                    $novaLargura, $novaAltura,
                    $larguraOriginal, $alturaOriginal
                );

                if (@imagewebp($novaImg, $destPathWebp, self::WEBP_QUALITY)) {
                    $salvoComSucesso = true;
                }

                imagedestroy($img);
                imagedestroy($novaImg);
            }
        }

        // Fallback seguro caso a GD não consiga ler a imagem
        if (!$salvoComSucesso) {
            $nomeArquivoFinal = $nomeBase . '.' . $ext;
            $destPathFinal = $uploadDir . DIRECTORY_SEPARATOR . $nomeArquivoFinal;
            if (!$uploaded->saveAs($destPathFinal)) {
                return ['success' => false, 'message' => 'Falha ao salvar a imagem no servidor.'];
            }
        } else {
            $nomeArquivoFinal = $nomeArquivoWebp;
            $destPathFinal = $destPathWebp;
        }

        $tamanhoFinal = file_exists($destPathFinal) ? filesize($destPathFinal) : 0;
        $economiaPercentual = $tamanhoOriginal > 0 ? round((1 - ($tamanhoFinal / $tamanhoOriginal)) * 100, 1) : 0;

        $url = Url::to('@web/uploads/chat/' . $nomeArquivoFinal, true);
        $path = '/uploads/chat/' . $nomeArquivoFinal;

        return [
            'success' => true,
            'url' => $url,
            'path' => $path,
            'tamanho_original_kb' => round($tamanhoOriginal / 1024, 1),
            'tamanho_final_kb' => round($tamanhoFinal / 1024, 1),
            'economia' => max(0, $economiaPercentual) . '%'
        ];
    }

    /**
     * Remove fisicamente do disco todas as mídias vinculadas às mensagens de uma condição
     * (ex: ao limpar uma conversa específica).
     *
     * @param array $cond Condição do banco de dados (ex: ['usuario_id' => ..., 'cliente_id' => ...])
     * @return array Resumo de arquivos removidos e bytes liberados
     */
    public static function excluirMidiasPorCondicao(array $cond): array
    {
        $arquivosRemovidos = 0;
        $bytesLiberados = 0;

        try {
            // Busca todas as mensagens daquela conversa que têm anexo de mídia
            $mensagensComMidia = ClienteInbox::find()
                ->select(['id', 'midia_url'])
                ->where($cond)
                ->andWhere(['not', ['midia_url' => null]])
                ->andWhere(['!=', 'midia_url', ''])
                ->asArray()
                ->all();

            $baseUploadDir = realpath(Yii::getAlias(self::UPLOAD_DIR));
            $baseTempDir = realpath(Yii::getAlias(self::TEMP_UPLOAD_DIR));

            foreach ($mensagensComMidia as $msg) {
                $url = $msg['midia_url'];
                if (empty($url)) continue;

                $parts = parse_url($url);
                $path = $parts['path'] ?? $url;
                $filename = basename($path);

                if (empty($filename) || !preg_match('/^chat_[a-zA-Z0-9_\.]+\.(jpg|jpeg|png|webp|gif)$/i', $filename)) {
                    continue;
                }

                // Tenta localizar na pasta principal /uploads/chat/
                $candidatos = [];
                if ($baseUploadDir) {
                    $candidatos[] = $baseUploadDir . DIRECTORY_SEPARATOR . $filename;
                }
                if ($baseTempDir) {
                    $candidatos[] = $baseTempDir . DIRECTORY_SEPARATOR . $filename;
                }
                $candidatos[] = Yii::getAlias('@app/web') . DIRECTORY_SEPARATOR . ltrim($path, '/');

                foreach ($candidatos as $filePath) {
                    if (file_exists($filePath) && is_file($filePath)) {
                        $tamanho = filesize($filePath);
                        if (@unlink($filePath)) {
                            $arquivosRemovidos++;
                            $bytesLiberados += $tamanho;
                            break;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Yii::error('Erro ao excluir arquivos de mídia do chat: ' . $e->getMessage(), __METHOD__);
        }

        return [
            'arquivos_removidos' => $arquivosRemovidos,
            'bytes_liberados' => $bytesLiberados,
            'espaco_liberado_kb' => round($bytesLiberados / 1024, 1),
        ];
    }

    /**
     * Limpa fotos e mídias antigas com mais de $horas horas (Padrão: 720h / 30 dias)
     * tanto no diretório principal quanto na pasta temporária.
     *
     * @param int $horas
     * @return int Total de arquivos removidos
     */
    public static function limparMidiasAntigas($horas = 720): int
    {
        $limiteTempo = time() - ($horas * 3600);
        $totalRemovidos = 0;

        $diretorios = [
            Yii::getAlias(self::UPLOAD_DIR),
            Yii::getAlias(self::TEMP_UPLOAD_DIR),
        ];

        foreach ($diretorios as $dirBase) {
            if (!is_dir($dirBase)) continue;

            $iterador = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dirBase, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterador as $item) {
                if ($item->isFile()) {
                    if ($item->getMTime() < $limiteTempo) {
                        $nome = $item->getFilename();
                        if (preg_match('/^chat_.*\.(jpg|jpeg|png|webp|gif)$/i', $nome)) {
                            if (@unlink($item->getRealPath())) {
                                $totalRemovidos++;
                            }
                        }
                    }
                } elseif ($item->isDir()) {
                    $arquivos = @scandir($item->getRealPath());
                    if ($arquivos && count($arquivos) <= 2) {
                        @rmdir($item->getRealPath());
                    }
                }
            }
        }

        return $totalRemovidos;
    }
}
