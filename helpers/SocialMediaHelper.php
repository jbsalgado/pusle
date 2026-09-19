<?php

namespace app\helpers;

use Yii;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Colaborador;

/**
 * SocialMediaHelper — Utilitários para formatação, adequação de mídia e geração de copywriting
 * para publicação automatizada no Instagram, Facebook e TikTok.
 */
class SocialMediaHelper
{
    /**
     * Garante que a URL da imagem seja convertida para JPEG (.jpg) de alta qualidade,
     * pois a Meta Graph API (Instagram) e TikTok rejeitam imagens .webp.
     *
     * @param string $mediaUrl URL original da imagem ou vídeo
     * @return string URL absoluta da mídia compatível
     */
    public static function ensureJpegForSocial(string $mediaUrl): string
    {
        $mediaUrl = self::ensureAbsoluteUrl($mediaUrl);
        $pathInfo = parse_url($mediaUrl, PHP_URL_PATH);
        if (!$pathInfo) {
            return $mediaUrl;
        }

        $extension = strtolower(pathinfo($pathInfo, PATHINFO_EXTENSION));
        if ($extension !== 'webp') {
            return $mediaUrl;
        }

        // Caminho físico do arquivo no servidor
        $webroot = Yii::getAlias('@app/web');
        $relativeFile = ltrim($pathInfo, '/');

        // Se o path começar com subdiretório do app
        if (strpos($relativeFile, 'uploads/') !== false) {
            $relativeFile = substr($relativeFile, strpos($relativeFile, 'uploads/'));
        }

        $webpPath = $webroot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeFile);
        $jpgRelative = preg_replace('/\.webp$/i', '.jpg', $relativeFile);
        $jpgPath = $webroot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $jpgRelative);

        // Se já foi convertido anteriormente e o arquivo existe
        if (file_exists($jpgPath) && filemtime($jpgPath) >= (file_exists($webpPath) ? filemtime($webpPath) : 0)) {
            return preg_replace('/\.webp(\?.*)?$/i', '.jpg$1', $mediaUrl);
        }

        // Se o arquivo .webp existe localmente, converte usando GD
        if (file_exists($webpPath) && function_exists('imagecreatefromwebp') && function_exists('imagejpeg')) {
            try {
                $im = @imagecreatefromwebp($webpPath);
                if ($im !== false) {
                    // Cria imagem truecolor com fundo branco se houver transparência
                    $width = imagesx($im);
                    $height = imagesy($im);
                    $bg = imagecreatetruecolor($width, $height);
                    $white = imagecolorallocate($bg, 255, 255, 255);
                    imagefill($bg, 0, 0, $white);
                    imagecopy($bg, $im, 0, 0, 0, 0, $width, $height);

                    imagejpeg($bg, $jpgPath, 95);
                    imagedestroy($im);
                    imagedestroy($bg);

                    return preg_replace('/\.webp(\?.*)?$/i', '.jpg$1', $mediaUrl);
                }
            } catch (\Throwable $e) {
                Yii::warning("Falha ao converter WebP para JPG: " . $e->getMessage(), __METHOD__);
            }
        }

        return $mediaUrl;
    }

    /**
     * Garante que uma URL seja pública e absoluta (https://...),
     * essencial para workers de fila (CLI) e requisições da Meta e TikTok.
     *
     * @param string $url
     * @return string
     */
    public static function ensureAbsoluteUrl(string $url): string
    {
        $url = trim($url);
        if (empty($url)) {
            return $url;
        }

        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            return $url;
        }

        $caminhoRelativo = ltrim($url, '/');

        // Se estamos em contexto web com hostInfo disponível
        if (Yii::$app->has('request') && Yii::$app->get('request') instanceof \yii\web\Request && !empty(Yii::$app->request->hostInfo)) {
            return rtrim(Yii::$app->request->hostInfo, '/') . '/' . $caminhoRelativo;
        }

        // Fallback para domínio configurado em params
        $domain = Yii::$app->params['domain'] ?? (
            !empty($_ENV['APP_URL']) ? $_ENV['APP_URL'] : 'https://catalogos.oncode.app.br'
        );

        return rtrim($domain, '/') . '/' . $caminhoRelativo;
    }

    /**
     * Gera automaticamente uma legenda atraente e vendedora (copywriting)
     * com dados do produto, link do catálogo, identificação de afiliado e hashtags.
     *
     * @param Produto|null $produto
     * @param string|null $customCaption
     * @param Colaborador|null $colaborador
     * @param string|null $customLink
     * @return string
     */
    public static function generateSocialCaption(
        ?Produto $produto = null,
        ?string $customCaption = null,
        ?Colaborador $colaborador = null,
        ?string $customLink = null
    ): string {
        if (!empty($customCaption)) {
            return $customCaption;
        }

        if (!$produto) {
            return "✨ Confira nossas ofertas exclusivas! Acesse nosso catálogo e aproveite as promoções imperdíveis. #ofertas #promocao #novidades";
        }

        $precoFinal = $produto->getPrecoFinal();
        $precoFormatado = 'R$ ' . number_format($precoFinal, 2, ',', '.');
        $emPromocao = $produto->getEmPromocao();

        // Linha de Preço
        $linhaPreco = "💰 Por apenas {$precoFormatado}";
        if ($emPromocao && $produto->preco_venda_sugerido > $precoFinal) {
            $precoOriginal = 'R$ ' . number_format($produto->preco_venda_sugerido, 2, ',', '.');
            $linhaPreco = "🔥 OFERTA IMPERDÍVEL! De {$precoOriginal} por apenas {$precoFormatado}";
        }

        // Linha de Link (Catálogo com suporte a Afiliado)
        $domain = Yii::$app->params['domain'] ?? (
            (Yii::$app->has('request') && Yii::$app->get('request') instanceof \yii\web\Request && !empty(Yii::$app->request->hostInfo))
                ? Yii::$app->request->hostInfo
                : 'https://catalogos.oncode.app.br'
        );

        if (!empty($customLink)) {
            $linkFinal = $customLink;
        } else {
            $params = ['u' => $produto->usuario_id];
            if ($colaborador) {
                $params['ref'] = $colaborador->id;
            }
            $params['utm_source'] = 'social';
            $params['utm_medium'] = 'post';

            $linkFinal = rtrim($domain, '/') . '/catalogo/?' . http_build_query($params) . '#/produto/' . $produto->id;
        }

        // Hashtags automáticas
        $hashtags = ['#ofertas', '#promocao', '#compras', '#lojaonline', '#novidades'];
        if (!empty($produto->categoria)) {
            $catTag = '#' . preg_replace('/[^a-zA-Z0-9]/', '', strtolower($produto->categoria->nome));
            if (strlen($catTag) > 2) {
                $hashtags[] = $catTag;
            }
        }
        if (!empty($produto->marca)) {
            $marcaTag = '#' . preg_replace('/[^a-zA-Z0-9]/', '', strtolower($produto->marca));
            if (strlen($marcaTag) > 2) {
                $hashtags[] = $marcaTag;
            }
        }

        $hashtagsStr = implode(' ', array_unique($hashtags));

        $caption = "✨ {$produto->nome}\n\n";
        $caption .= "{$linhaPreco}\n\n";

        if (!empty($produto->descricao)) {
            $resumoDesc = mb_substr(strip_tags($produto->descricao), 0, 180);
            $caption .= "📝 " . trim($resumoDesc) . "...\n\n";
        }

        $caption .= "👉 Garanta o seu agora mesmo no link:\n{$linkFinal}\n\n";
        $caption .= "💬 Dúvidas? Chame a gente no direct ou clique no link da bio!\n\n";
        $caption .= "{$hashtagsStr}";

        return $caption;
    }
}
