<?php

namespace app\modules\vendas\helpers;

use Yii;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;

class ChatMediaHelper
{
    const UPLOAD_DIR = '@webroot/uploads/chat_temp';
    const URL_PREFIX = '/uploads/chat_temp';

    /**
     * Salva uma imagem enviada via UploadedFile ou $_FILES
     * @param string $inputName Nome do campo no formulário (ex: 'imagem' ou 'foto')
     * @return string|null URL relativa da imagem ou null se não houver upload
     */
    public static function salvarUpload($inputName = 'imagem')
    {
        $uploaded = UploadedFile::getInstanceByName($inputName);
        if (!$uploaded || $uploaded->hasError) {
            return null;
        }

        // Valida extensões permitidas
        $ext = strtolower($uploaded->extension);
        $extsValidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $extsValidas)) {
            return null;
        }

        $hoje = date('Y-m-d');
        $pastaAbsoluta = Yii::getAlias(self::UPLOAD_DIR . '/' . $hoje);
        if (!is_dir($pastaAbsoluta)) {
            FileHelper::createDirectory($pastaAbsoluta, 0775, true);
        }

        $nomeArquivo = sprintf('%s_%s.%s', date('His'), bin2hex(random_bytes(8)), $ext);
        $caminhoCompleto = $pastaAbsoluta . '/' . $nomeArquivo;

        if ($uploaded->saveAs($caminhoCompleto)) {
            // Executa limpeza probabilística (5% de chance)
            if (mt_rand(1, 20) === 1) {
                self::limparMidiasAntigas(24);
            }

            return self::URL_PREFIX . '/' . $hoje . '/' . $nomeArquivo;
        }

        return null;
    }

    /**
     * Limpa fotos e pastas com mais de $horas horas (Padrão: 24h)
     * @param int $horas
     * @return int Total de arquivos removidos
     */
    public static function limparMidiasAntigas($horas = 24)
    {
        $dirBase = Yii::getAlias(self::UPLOAD_DIR);
        if (!is_dir($dirBase)) {
            return 0;
        }

        $limiteTempo = time() - ($horas * 3600);
        $totalRemovidos = 0;

        $iterador = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirBase, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterador as $item) {
            if ($item->isFile()) {
                if ($item->getMTime() < $limiteTempo) {
                    @unlink($item->getRealPath());
                    $totalRemovidos++;
                }
            } elseif ($item->isDir()) {
                // Remove pasta se estiver vazia
                $arquivos = @scandir($item->getRealPath());
                if ($arquivos && count($arquivos) <= 2) {
                    @rmdir($item->getRealPath());
                }
            }
        }

        return $totalRemovidos;
    }
}
