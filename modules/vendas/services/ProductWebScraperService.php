<?php

namespace app\modules\vendas\services;

use Yii;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\ProdutoFoto;
use app\modules\vendas\models\ProdutoVideo;
use yii\helpers\FileHelper;

class ProductWebScraperService
{
    /**
     * User-Agent realista para requisições HTTP
     */
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';

    /**
     * Executa requisição HTTP cURL segura com headers adequados
     */
    public static function executarCurl($url, $isJson = false, $timeout = 15)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/json',
            'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control: no-cache',
        ];
        if ($isJson) {
            $headers[] = 'Accept: application/json';
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 400 && $response) {
            return $response;
        }

        return null;
    }

    /**
     * Busca mídias (imagens e vídeos) para um produto a partir de nome, marca e EAN
     *
     * @param string $nome
     * @param string|null $marca
     * @param string|null $ean
     * @param int $maxFotos
     * @param int $maxVideos
     * @return array
     */
    public function buscarMidiasParaProduto($nome, $marca = '', $ean = '', $maxFotos = 6, $maxVideos = 2)
    {
        $fotos = [];
        $videos = [];
        $dadosEncontrados = [
            'ean' => $ean,
            'marca' => $marca,
            'nome_sugerido' => $nome,
            'descricao' => '',
        ];

        // 1. Busca por Código de Barras (EAN-13) em bases públicas (OpenFoodFacts / Cosmos)
        if (!empty($ean) && preg_match('/^\d{7,14}$/', trim($ean))) {
            $dadosEan = $this->consultarBaseOpenFoodFactsPorEan(trim($ean));
            if ($dadosEan) {
                if (!empty($dadosEan['fotos'])) {
                    foreach ($dadosEan['fotos'] as $f) {
                        $fotos[] = $f;
                    }
                }
                if (!empty($dadosEan['marca']) && empty($marca)) {
                    $dadosEncontrados['marca'] = $dadosEan['marca'];
                }
                if (!empty($dadosEan['nome'])) {
                    $dadosEncontrados['nome_sugerido'] = $dadosEan['nome'];
                }
            }
        }

        // 2. Busca por Nome + Marca no OpenFoodFacts
        $termoBusca = trim(($marca ? $marca . ' ' : '') . $nome);
        if (count($fotos) < $maxFotos) {
            $dadosBuscaOFF = $this->consultarBaseOpenFoodFactsPorTexto($termoBusca, $maxFotos);
            foreach ($dadosBuscaOFF['fotos'] as $f) {
                if (!in_array($f, $fotos)) {
                    $fotos[] = $f;
                }
            }
            if (empty($dadosEncontrados['ean']) && !empty($dadosBuscaOFF['ean'])) {
                $dadosEncontrados['ean'] = $dadosBuscaOFF['ean'];
            }
        }

        // 3. Busca de Imagens na Web Geral (DuckDuckGo / Bing Scraper)
        if (count($fotos) < $maxFotos) {
            $fotosWeb = $this->buscarImagensDuckDuckGo($termoBusca, $maxFotos - count($fotos));
            foreach ($fotosWeb as $fw) {
                if (!in_array($fw, $fotos)) {
                    $fotos[] = $fw;
                }
            }
        }

        // 4. Busca de Vídeos Promocionais (YouTube embeds / MP4 links)
        if ($maxVideos > 0) {
            $videos = $this->buscarVideosPromocionaisWeb($termoBusca, $maxVideos);
        }

        // Limita quantidades
        $fotos = array_slice(array_unique($fotos), 0, $maxFotos);
        $videos = array_slice($videos, 0, $maxVideos);

        return [
            'success' => true,
            'termo_buscado' => $termoBusca,
            'dados' => $dadosEncontrados,
            'fotos' => $fotos,
            'videos' => $videos,
            'total_fotos' => count($fotos),
            'total_videos' => count($videos),
        ];
    }

    /**
     * Consulta OpenFoodFacts por EAN
     */
    public function consultarBaseOpenFoodFactsPorEan($ean)
    {
        $url = "https://world.openfoodfacts.org/api/v2/product/{$ean}.json";
        $json = self::executarCurl($url, true, 8);
        if (!$json) return null;

        $data = json_decode($json, true);
        if (empty($data['product'])) return null;

        $p = $data['product'];
        $fotos = [];

        if (!empty($p['image_url'])) $fotos[] = $p['image_url'];
        if (!empty($p['image_front_url'])) $fotos[] = $p['image_front_url'];
        if (!empty($p['image_ingredients_url'])) $fotos[] = $p['image_ingredients_url'];
        if (!empty($p['image_nutrition_url'])) $fotos[] = $p['image_nutrition_url'];
        if (!empty($p['image_packaging_url'])) $fotos[] = $p['image_packaging_url'];

        // Outras fotos de alta resolução
        if (!empty($p['selected_images']['front']['display']['pt'])) {
            $fotos[] = $p['selected_images']['front']['display']['pt'];
        }

        return [
            'nome' => $p['product_name_pt'] ?? ($p['product_name'] ?? ''),
            'marca' => $p['brands'] ?? '',
            'ean' => $ean,
            'fotos' => array_values(array_unique($fotos)),
        ];
    }

    /**
     * Consulta OpenFoodFacts por texto / marcas
     */
    public function consultarBaseOpenFoodFactsPorTexto($termo, $limit = 6)
    {
        $url = "https://br.openfoodfacts.org/cgi/search.pl?search_terms=" . urlencode($termo) . "&search_simple=1&action=process&json=1&page_size=" . min($limit * 2, 20);
        $json = self::executarCurl($url, true, 8);
        
        $fotos = [];
        $ean = null;
        $produtos = [];

        if ($json) {
            $data = json_decode($json, true);
            if (!empty($data['products']) && is_array($data['products'])) {
                foreach ($data['products'] as $prod) {
                    $img = $prod['image_front_url'] ?? ($prod['image_url'] ?? '');
                    if ($img && !in_array($img, $fotos)) {
                        $fotos[] = $img;
                    }
                    if (!$ean && !empty($prod['code'])) {
                        $ean = $prod['code'];
                    }

                    $produtos[] = [
                        'nome' => $prod['product_name_pt'] ?? ($prod['product_name'] ?? ''),
                        'marca' => $prod['brands'] ?? '',
                        'ean' => $prod['code'] ?? '',
                        'foto' => $img,
                        'categoria' => $prod['categories'] ?? '',
                    ];
                }
            }
        }

        return [
            'fotos' => $fotos,
            'ean' => $ean,
            'produtos' => $produtos,
        ];
    }

    /**
     * Busca imagens via motor DuckDuckGo Images
     */
    public function buscarImagensDuckDuckGo($query, $limit = 6)
    {
        $imagens = [];
        $queryFormatada = $query . ' produto embalagem';

        // 1. Obtém o token vqd do DuckDuckGo
        $urlInit = "https://duckduckgo.com/?q=" . urlencode($queryFormatada);
        $htmlInit = self::executarCurl($urlInit, false, 8);
        
        $vqd = null;
        if ($htmlInit && preg_match('/vqd=([0-9-_]+)/', $htmlInit, $matches)) {
            $vqd = $matches[1];
        } elseif ($htmlInit && preg_match('/vqd=[\'"]([0-9-_]+)[\'"]/', $htmlInit, $matches)) {
            $vqd = $matches[1];
        }

        if ($vqd) {
            $urlImages = "https://duckduckgo.com/i.js?l=wt-wt&o=json&q=" . urlencode($queryFormatada) . "&vqd={$vqd}&f=,,,&p=1";
            $jsonImages = self::executarCurl($urlImages, true, 8);
            if ($jsonImages) {
                $res = json_decode($jsonImages, true);
                if (!empty($res['results']) && is_array($res['results'])) {
                    foreach ($res['results'] as $r) {
                        if (!empty($r['image']) && filter_var($r['image'], FILTER_VALIDATE_URL)) {
                            // Filtra formatos suportados e ignora ícones minúsculos
                            $largura = (int)($r['width'] ?? 500);
                            $altura = (int)($r['height'] ?? 500);
                            if ($largura >= 200 && $altura >= 200) {
                                $imagens[] = $r['image'];
                                if (count($imagens) >= $limit) break;
                            }
                        }
                    }
                }
            }
        }

        // Fallback: Busca via Wikimedia / Commons se necessário
        if (empty($imagens)) {
            $urlWiki = "https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrnamespace=6&gsrsearch=" . urlencode($query) . "&gsrlimit={$limit}&prop=imageinfo&iiprop=url|size&format=json";
            $jsonWiki = self::executarCurl($urlWiki, true, 8);
            if ($jsonWiki) {
                $dataWiki = json_decode($jsonWiki, true);
                if (!empty($dataWiki['query']['pages'])) {
                    foreach ($dataWiki['query']['pages'] as $page) {
                        if (!empty($page['imageinfo'][0]['url'])) {
                            $imagens[] = $page['imageinfo'][0]['url'];
                        }
                    }
                }
            }
        }

        return array_values(array_unique($imagens));
    }

    /**
     * Busca vídeos promocionais na Web (YouTube embeds ou links diretos)
     */
    public function buscarVideosPromocionaisWeb($termo, $limit = 2)
    {
        $videos = [];
        $queryVideo = $termo . ' comercial promocional oficial';

        // Busca no DuckDuckGo Videos
        $urlInit = "https://duckduckgo.com/?q=" . urlencode($queryVideo);
        $htmlInit = self::executarCurl($urlInit, false, 8);

        $vqd = null;
        if ($htmlInit && preg_match('/vqd=([0-9-_]+)/', $htmlInit, $matches)) {
            $vqd = $matches[1];
        }

        if ($vqd) {
            $urlVideos = "https://duckduckgo.com/v.js?l=wt-wt&o=json&q=" . urlencode($queryVideo) . "&vqd={$vqd}&f=,,,&p=1";
            $jsonVideos = self::executarCurl($urlVideos, true, 8);
            if ($jsonVideos) {
                $res = json_decode($jsonVideos, true);
                if (!empty($res['results']) && is_array($res['results'])) {
                    foreach ($res['results'] as $r) {
                        $videoUrl = null;
                        $embedUrl = null;
                        $thumbnail = $r['images']['large'] ?? ($r['images']['medium'] ?? '');

                        if (!empty($r['content'])) {
                            $videoUrl = $r['content'];
                        }

                        // Se for link do YouTube, formata embed
                        if ($videoUrl && preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $videoUrl, $mYt)) {
                            $embedUrl = "https://www.youtube.com/embed/{$mYt[1]}";
                        }

                        $videos[] = [
                            'titulo' => $r['title'] ?? 'Vídeo Promocional',
                            'duracao' => $r['duration'] ?? '',
                            'url' => $videoUrl,
                            'embed_url' => $embedUrl ?: $videoUrl,
                            'thumbnail' => $thumbnail,
                            'origem' => $r['publisher'] ?? 'YouTube/Web',
                        ];

                        if (count($videos) >= $limit) break;
                    }
                }
            }
        }

        return $videos;
    }

    /**
     * Extrai metadados, imagens, vídeos e dados estruturados de uma URL fornecida
     */
    public function extrairDeUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'message' => 'URL inválida informada.'];
        }

        $html = self::executarCurl($url, false, 20);
        if (!$html) {
            return ['success' => false, 'message' => 'Não foi possível acessar a página informada. Verifique se o link está acessível.'];
        }

        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($doc);

        $titulo = '';
        $descricao = '';
        $marca = '';
        $preco = null;
        $ean = null;
        $fotos = [];
        $videos = [];

        // 1. OpenGraph & Meta Tags
        $ogTitle = $xpath->query('//meta[@property="og:title"]/@content')->item(0);
        $metaTitle = $xpath->query('//title')->item(0);
        $titulo = $ogTitle ? $ogTitle->nodeValue : ($metaTitle ? $metaTitle->nodeValue : '');

        $ogDesc = $xpath->query('//meta[@property="og:description"]/@content')->item(0);
        $metaDesc = $xpath->query('//meta[@name="description"]/@content')->item(0);
        $descricao = $ogDesc ? $ogDesc->nodeValue : ($metaDesc ? $metaDesc->nodeValue : '');

        $ogImage = $xpath->query('//meta[@property="og:image"]/@content');
        foreach ($ogImage as $node) {
            $imgUrl = self::normalizarUrl($node->nodeValue, $url);
            if ($imgUrl && !in_array($imgUrl, $fotos)) $fotos[] = $imgUrl;
        }

        $ogVideo = $xpath->query('//meta[@property="og:video"]/@content | //meta[@property="og:video:url"]/@content');
        foreach ($ogVideo as $node) {
            $vUrl = self::normalizarUrl($node->nodeValue, $url);
            if ($vUrl) {
                $videos[] = [
                    'titulo' => $titulo,
                    'url' => $vUrl,
                    'embed_url' => $vUrl,
                    'thumbnail' => $fotos[0] ?? '',
                    'origem' => 'OpenGraph Video',
                ];
            }
        }

        // 2. Schema.org JSON-LD
        $scriptsJsonLd = $xpath->query('//script[@type="application/ld+json"]');
        foreach ($scriptsJsonLd as $scriptNode) {
            $jsonContent = trim($scriptNode->nodeValue);
            if ($jsonContent) {
                $jsonData = json_decode($jsonContent, true);
                if ($jsonData) {
                    $this->processarSchemaJsonLd($jsonData, $titulo, $descricao, $marca, $preco, $ean, $fotos, $videos, $url);
                }
            }
        }

        // 3. Imagens da página (tags <img> relevantes)
        if (count($fotos) < 6) {
            $imgs = $xpath->query('//img[@src or @data-src or @data-zoom-image]');
            foreach ($imgs as $imgNode) {
                $src = $imgNode->getAttribute('data-zoom-image') 
                    ?: ($imgNode->getAttribute('data-src') ?: $imgNode->getAttribute('src'));
                $srcFull = self::normalizarUrl($src, $url);
                
                // Ignora ícones minúsculos e logos decorativos
                if ($srcFull && !in_array($srcFull, $fotos) && !preg_match('/(icon|logo|avatar|badge|spacer|blank|pixel)/i', $srcFull)) {
                    $fotos[] = $srcFull;
                    if (count($fotos) >= 10) break;
                }
            }
        }

        // 4. Tags <video> e iframes do YouTube/Vimeo
        $iframes = $xpath->query('//iframe[@src]');
        foreach ($iframes as $iframe) {
            $src = $iframe->getAttribute('src');
            if (preg_match('/(?:youtube\.com\/embed\/|player\.vimeo\.com\/video\/)/', $src)) {
                $videos[] = [
                    'titulo' => $titulo,
                    'url' => $src,
                    'embed_url' => $src,
                    'thumbnail' => $fotos[0] ?? '',
                    'origem' => 'Embed Vídeo',
                ];
            }
        }

        // Limpa título
        $tituloLimpo = preg_replace('/(\s*[-|–]\s*(Mercado Livre|Amazon|Magazine Luiza|Shopee|Carrefour|Pão de Açúcar).*)$/i', '', trim($titulo));

        return [
            'success' => true,
            'url_origem' => $url,
            'dados' => [
                'nome' => $tituloLimpo ?: 'Produto Importado',
                'descricao' => trim($descricao),
                'marca' => trim($marca),
                'preco' => $preco,
                'ean' => $ean,
            ],
            'fotos' => array_values(array_unique($fotos)),
            'videos' => $videos,
            'total_fotos' => count($fotos),
            'total_videos' => count($videos),
        ];
    }

    /**
     * Auxiliar para processar Schema.org JSON-LD recursivamente
     */
    private function processarSchemaJsonLd($data, &$titulo, &$descricao, &$marca, &$preco, &$ean, &$fotos, &$videos, $baseUrl)
    {
        if (isset($data['@graph']) && is_array($data['@graph'])) {
            foreach ($data['@graph'] as $subItem) {
                $this->processarSchemaJsonLd($subItem, $titulo, $descricao, $marca, $preco, $ean, $fotos, $videos, $baseUrl);
            }
            return;
        }

        $tipo = $data['@type'] ?? '';
        if (is_array($tipo)) $tipo = implode(' ', $tipo);

        if (stripos($tipo, 'Product') !== false || stripos($tipo, 'ItemPage') !== false) {
            if (empty($titulo) && !empty($data['name'])) $titulo = $data['name'];
            if (empty($descricao) && !empty($data['description'])) $descricao = $data['description'];
            if (empty($marca)) {
                if (is_string($data['brand'] ?? '')) $marca = $data['brand'];
                elseif (is_array($data['brand'] ?? '') && !empty($data['brand']['name'])) $marca = $data['brand']['name'];
            }
            if (empty($ean)) {
                $ean = $data['gtin13'] ?? ($data['gtin'] ?? ($data['gtin8'] ?? ($data['sku'] ?? null)));
            }
            if (!$preco && !empty($data['offers'])) {
                $offers = isset($data['offers']['price']) ? [$data['offers']] : ($data['offers'][0] ?? []);
                if (!empty($offers['price'])) {
                    $preco = (float)$offers['price'];
                }
            }

            if (!empty($data['image'])) {
                $imgs = is_array($data['image']) ? $data['image'] : [$data['image']];
                foreach ($imgs as $imgItem) {
                    $imgUrl = is_string($imgItem) ? $imgItem : ($imgItem['url'] ?? ($imgItem['contentUrl'] ?? ''));
                    $imgNorm = self::normalizarUrl($imgUrl, $baseUrl);
                    if ($imgNorm && !in_array($imgNorm, $fotos)) $fotos[] = $imgNorm;
                }
            }

            if (!empty($data['video'])) {
                $v = $data['video'];
                $vUrl = is_string($v) ? $v : ($v['contentUrl'] ?? ($v['embedUrl'] ?? ''));
                if ($vUrl) {
                    $videos[] = [
                        'titulo' => $data['name'] ?? 'Vídeo do Produto',
                        'url' => $vUrl,
                        'embed_url' => $vUrl,
                        'thumbnail' => $fotos[0] ?? '',
                        'origem' => 'Schema.org Video',
                    ];
                }
            }
        }
    }

    /**
     * Pesquisa catálogo de produtos populares para uma lista de marcas (separadas por vírgula)
     */
    public function pesquisarProdutosPorMarcas(array $marcas, $itensPorMarca = 8)
    {
        $resultado = [];

        foreach ($marcas as $marca) {
            $marcaLimpa = trim($marca);
            if (empty($marcaLimpa)) continue;

            $itensMarca = [];

            // 1. Busca no OpenFoodFacts pela Marca
            $dadosOff = $this->consultarBaseOpenFoodFactsPorTexto($marcaLimpa, $itensPorMarca);
            if (!empty($dadosOff['produtos'])) {
                foreach ($dadosOff['produtos'] as $p) {
                    if (!empty($p['nome']) && !empty($p['foto'])) {
                        $itensMarca[] = [
                            'nome' => $p['nome'],
                            'marca' => $p['marca'] ?: $marcaLimpa,
                            'ean' => $p['ean'] ?: '',
                            'fotos' => [$p['foto']],
                            'preco_sugerido' => 0.00,
                            'categoria_sugerida' => $p['categoria'] ?: 'Geral',
                        ];
                        if (count($itensMarca) >= $itensPorMarca) break;
                    }
                }
            }

            // 2. Se a marca não teve produtos suficientes, busca produtos web da marca
            if (count($itensMarca) < $itensPorMarca) {
                $termosSugeridos = [
                    "{$marcaLimpa} produto",
                    "{$marcaLimpa} lançamento",
                ];
                foreach ($termosSugeridos as $termo) {
                    if (count($itensMarca) >= $itensPorMarca) break;
                    $fotosWeb = $this->buscarImagensDuckDuckGo($termo, 4);
                    foreach ($fotosWeb as $fw) {
                        $itensMarca[] = [
                            'nome' => "{$marcaLimpa} Produto " . (count($itensMarca) + 1),
                            'marca' => $marcaLimpa,
                            'ean' => '',
                            'fotos' => [$fw],
                            'preco_sugerido' => 0.00,
                            'categoria_sugerida' => 'Geral',
                        ];
                        if (count($itensMarca) >= $itensPorMarca) break;
                    }
                }
            }

            $resultado[$marcaLimpa] = $itensMarca;
        }

        return [
            'success' => true,
            'marcas' => $resultado,
            'total_marcas' => count($resultado),
            'total_produtos_encontrados' => array_sum(array_map('count', $resultado)),
        ];
    }

    /**
     * Baixa imagem externa, valida, otimiza com GD (max 1200px) e salva em prest_produto_fotos
     */
    public function baixarESalvarFoto($imageUrl, $produtoId, $ehPrincipal = false, $ordem = 0)
    {
        $produto = Produto::findOne($produtoId);
        if (!$produto) {
            throw new \Exception("Produto {$produtoId} não encontrado.");
        }

        $conteudo = self::executarCurl($imageUrl, false, 15);
        if (!$conteudo) {
            throw new \Exception("Não foi possível fazer o download da imagem: {$imageUrl}");
        }

        // Valida imagem com GD
        $imgResource = @imagecreatefromstring($conteudo);
        if (!$imgResource) {
            throw new \Exception("O arquivo baixado não é uma imagem válida.");
        }

        $uploadDir = Yii::getAlias("@app/web/uploads/produtos/{$produtoId}");
        FileHelper::createDirectory($uploadDir, 0777);

        $larguraOriginal = imagesx($imgResource);
        $alturaOriginal = imagesy($imgResource);

        $maxDim = 1200;
        $novaLargura = $larguraOriginal;
        $novaAltura = $alturaOriginal;

        if ($larguraOriginal > $maxDim || $alturaOriginal > $maxDim) {
            if ($larguraOriginal > $alturaOriginal) {
                $novaLargura = $maxDim;
                $novaAltura = (int)($alturaOriginal * ($maxDim / $larguraOriginal));
            } else {
                $novaAltura = $maxDim;
                $novaLargura = (int)($larguraOriginal * ($maxDim / $alturaOriginal));
            }
        }

        $imgRedimensionada = imagecreatetruecolor($novaLargura, $novaAltura);
        
        // Fundo branco para preservar transparência de PNGs ao converter para JPG
        $branco = imagecolorallocate($imgRedimensionada, 255, 255, 255);
        imagefilledrectangle($imgRedimensionada, 0, 0, $novaLargura, $novaAltura, $branco);

        imagecopyresampled(
            $imgRedimensionada, $imgResource,
            0, 0, 0, 0,
            $novaLargura, $novaAltura,
            $larguraOriginal, $alturaOriginal
        );

        $filename = uniqid('web_') . '.jpg';
        $caminhoCompleto = $uploadDir . '/' . $filename;
        imagejpeg($imgRedimensionada, $caminhoCompleto, 85);

        imagedestroy($imgResource);
        imagedestroy($imgRedimensionada);

        $caminhoRelativo = "uploads/produtos/{$produtoId}/{$filename}";

        // Verifica se o produto possui alguma foto física válida existente no disco
        $fotosExistentes = ProdutoFoto::find()->where(['produto_id' => $produtoId])->all();
        $temFotoFisicaValida = false;
        foreach ($fotosExistentes as $fEx) {
            $caminhoFisicoEx = Yii::getAlias('@app/web/' . ltrim($fEx->arquivo_path, '/'));
            if (file_exists($caminhoFisicoEx)) {
                $temFotoFisicaValida = true;
                break;
            }
        }

        // Se não houver foto física válida, a nova foto baixada DEVE ser a principal
        if ($ehPrincipal || !$temFotoFisicaValida) {
            $ehPrincipal = true;
            ProdutoFoto::updateAll(['eh_principal' => false], ['produto_id' => $produtoId]);
        }

        $foto = new ProdutoFoto();
        $foto->produto_id = $produtoId;
        $foto->arquivo_nome = $filename;
        $foto->arquivo_path = $caminhoRelativo;
        $foto->ordem = $ordem;
        $foto->eh_principal = $ehPrincipal;

        if (!$foto->save()) {
            throw new \Exception("Erro ao salvar ProdutoFoto: " . json_encode($foto->getErrors()));
        }

        return $foto;
    }

    /**
     * Registra vídeo promocional para o produto
     */
    public function salvarVideoPromocional($videoUrl, $produtoId, $usuarioId, $titulo = '')
    {
        $produto = Produto::findOne(['id' => $produtoId, 'usuario_id' => $usuarioId]);
        if (!$produto) {
            throw new \Exception("Produto não encontrado.");
        }

        $video = new ProdutoVideo();
        $video->produto_id = $produtoId;
        $video->usuario_id = $usuarioId;
        $video->duracao = 15;
        $video->formato = ProdutoVideo::FORMATO_STORIES;
        $video->status = ProdutoVideo::STATUS_CONCLUIDO;
        $video->video_url = $videoUrl;
        $video->video_path = $videoUrl;
        $video->metadata = [
            'origem' => 'web_promocional',
            'titulo' => $titulo ?: 'Vídeo Promocional da Web',
            'url_original' => $videoUrl,
        ];

        if (!$video->save()) {
            throw new \Exception("Erro ao salvar ProdutoVideo: " . json_encode($video->getErrors()));
        }

        return $video;
    }

    /**
     * Normaliza URLs relativas
     */
    public static function normalizarUrl($url, $baseUrl)
    {
        if (empty($url)) return null;
        $url = trim($url);

        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }

        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            return $url;
        }

        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';

        if (strpos($url, '/') === 0) {
            return "{$scheme}://{$host}{$url}";
        }

        return "{$scheme}://{$host}/{$url}";
    }
}
