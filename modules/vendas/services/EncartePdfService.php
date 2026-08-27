<?php

namespace app\modules\vendas\services;

use Yii;
use app\modules\vendas\models\Encarte;
use kartik\mpdf\Pdf;
use yii\helpers\Url;
use yii\helpers\Html;

class EncartePdfService
{
    /**
     * Gera o objeto mPDF para o encarte informado.
     *
     * @param Encarte $encarte
     * @param string $destination Destino do mPDF (DEST_BROWSER, DEST_FILE, DEST_STRING_RETURN)
     * @return mixed
     */
    public static function gerarPdf(Encarte $encarte, $destination = Pdf::DEST_BROWSER)
    {
        ini_set('pcre.backtrack_limit', '5000000');
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $loja = $encarte->usuario;
        $encarteProdutos = $encarte->encarteProdutos;
        $ppp = $encarte->produtos_por_pagina ?: 6;

        // Divisão dos produtos em páginas (lâminas)
        $paginas = array_chunk($encarteProdutos, $ppp);

        $html = self::renderHtmlTabloide($encarte, $paginas, $loja);

        $cssInline = self::getCssTabloide($encarte->cor_tema);

        $pdf = new Pdf([
            'mode' => Pdf::MODE_UTF8,
            'format' => Pdf::FORMAT_A4,
            'orientation' => Pdf::ORIENT_PORTRAIT,
            'destination' => $destination,
            'content' => $html,
            'cssInline' => $cssInline,
            'options' => [
                'title' => $encarte->titulo,
                'subject' => $encarte->subtitulo ?: 'Encarte de Ofertas',
            ],
            'methods' => [
                'SetHeader' => [$encarte->titulo . ' - Ofertas Imbatíveis||Página {PAGENO}'],
                'SetFooter' => ['Confira mais em nosso site ou WhatsApp||Pulse Sistema'],
            ]
        ]);

        return $pdf->render();
    }

    /**
     * Monta a estrutura HTML completa do folheto impresso A4
     */
    private static function renderHtmlTabloide(Encarte $encarte, array $paginas, $loja)
    {
        $titulo = Html::encode($encarte->titulo);
        $subtitulo = Html::encode($encarte->subtitulo ?: 'Super Ofertas da Semana - Aproveite!');
        $nomeLoja = $loja ? Html::encode($loja->nome ?: 'Nossa Loja') : 'Pulse Vendas';
        $telefoneLoja = $loja ? Html::encode($loja->telefone ?: '') : '';

        $totalPaginas = count($paginas);

        ob_start();
        ?>
        <div class="encarte-container">
            <?php foreach ($paginas as $indexPagina => $itensPagina): ?>
                <div class="lamina-page <?= $indexPagina > 0 ? 'page-break' : '' ?>">
                    
                    <!-- Cabeçalho Promocional da Lâmina -->
                    <div class="header-banner">
                        <table class="header-table">
                            <tr>
                                <td class="header-left">
                                    <div class="store-tag"><?= mb_strtoupper($nomeLoja) ?></div>
                                    <h1 class="main-title"><?= $titulo ?></h1>
                                    <div class="sub-title"><?= $subtitulo ?></div>
                                </td>
                                <td class="header-right">
                                    <?php if ($telefoneLoja): ?>
                                        <div class="whatsapp-box">
                                            <span class="wp-icon">📱</span> Peça no WhatsApp:<br>
                                            <strong><?= $telefoneLoja ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    <div class="page-badge">Página <?= ($indexPagina + 1) ?> de <?= $totalPaginas ?></div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Grade de Produtos da Lâmina -->
                    <div class="grid-produtos grid-count-<?= count($itensPagina) ?>">
                        <table class="grid-table">
                            <?php 
                            // Renderiza em linhas de 2 produtos para formato A4 de alta legibilidade
                            $chunksLinhas = array_chunk($itensPagina, 2);
                            foreach ($chunksLinhas as $linha): 
                            ?>
                                <tr>
                                    <?php foreach ($linha as $encarteProd): 
                                        $produto = $encarteProd->produto;
                                        if (!$produto) continue;

                                        $precoVal = $encarteProd->getPrecoFinal();
                                        $precoFormatado = number_format($precoVal, 2, ',', '.');
                                        $partesPreco = explode(',', $precoFormatado);

                                        // Foto do produto
                                        $foto = $produto->fotoPrincipal ?: ($produto->fotos[0] ?? null);
                                        $srcFoto = null;
                                        if ($foto && !empty($foto->arquivo_path)) {
                                            $caminhoAbs = Yii::getAlias('@app/web/') . ltrim($foto->arquivo_path, '/');
                                            if (file_exists($caminhoAbs)) {
                                                $srcFoto = $caminhoAbs;
                                            }
                                        }
                                    ?>
                                        <td class="product-cell <?= count($linha) == 1 ? 'cell-full' : '' ?>">
                                            <div class="product-card">
                                                
                                                <!-- Badge Oferta -->
                                                <div class="starburst-badge">
                                                    <span>OFERTA</span>
                                                </div>

                                                <!-- Imagem -->
                                                <div class="product-img-box">
                                                    <?php if ($srcFoto): ?>
                                                        <img src="<?= $srcFoto ?>" class="product-img" alt="<?= Html::encode($produto->nome) ?>">
                                                    <?php else: ?>
                                                        <div class="no-img">FOTO DO PRODUTO</div>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Detalhes -->
                                                <div class="product-info">
                                                    <?php if ($produto->marca): ?>
                                                        <div class="product-brand"><?= Html::encode(mb_strtoupper($produto->marca)) ?></div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="product-name"><?= Html::encode($produto->nome) ?></div>
                                                    
                                                    <div class="price-container">
                                                        <span class="currency">R$</span>
                                                        <span class="price-main"><?= $partesPreco[0] ?></span>
                                                        <span class="price-cents">,<?= $partesPreco[1] ?></span>
                                                        <span class="unit-label">/ <?= Html::encode($produto->unidade_medida ?: 'un') ?></span>
                                                    </div>
                                                </div>

                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                    
                                    <!-- Célula vazia se a linha tiver ímpar -->
                                    <?php if (count($linha) == 1): ?>
                                        <td class="product-cell empty-cell"></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>

                    <!-- Rodapé da Lâmina -->
                    <div class="footer-banner">
                        Ofertas válidas enquanto durarem os estoques. Imagens meramente ilustrativas.
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Retorna o CSS do Folheto Promocional para o mPDF
     */
    private static function getCssTabloide($tema = 'red_gold')
    {
        $corPrimaria = '#dc2626'; // Red 600
        $corSecundaria = '#f59e0b'; // Amber 500
        $corDark = '#7f1d1d'; // Red 900

        if ($tema === 'emerald_fresh') {
            $corPrimaria = '#059669';
            $corSecundaria = '#10b981';
            $corDark = '#064e3b';
        } elseif ($tema === 'ocean_blue') {
            $corPrimaria = '#2563eb';
            $corSecundaria = '#3b82f6';
            $corDark = '#1e3a8a';
        } elseif ($tema === 'dark_vip') {
            $corPrimaria = '#18181b';
            $corSecundaria = '#eab308';
            $corDark = '#09090b';
        }

        return "
            @page {
                margin: 8mm;
            }
            body {
                font-family: Arial, Helvetica, sans-serif;
                color: #1f2937;
                background-color: #ffffff;
            }
            .page-break {
                page-break-before: always;
            }
            .header-banner {
                background-color: {$corPrimaria};
                color: #ffffff;
                padding: 10px 15px;
                border-radius: 8px;
                margin-bottom: 12px;
            }
            .header-table {
                width: 100%;
                border-collapse: collapse;
            }
            .header-left {
                text-align: left;
                width: 70%;
            }
            .header-right {
                text-align: right;
                width: 30%;
                vertical-align: middle;
            }
            .store-tag {
                background-color: {$corSecundaria};
                color: #000000;
                font-weight: bold;
                font-size: 10px;
                display: inline-block;
                padding: 2px 8px;
                border-radius: 4px;
                text-transform: uppercase;
            }
            .main-title {
                font-size: 20px;
                font-weight: 900;
                margin: 4px 0 2px 0;
                text-transform: uppercase;
                letter-spacing: -0.5px;
            }
            .sub-title {
                font-size: 11px;
                opacity: 0.9;
            }
            .whatsapp-box {
                background: rgba(255, 255, 255, 0.2);
                padding: 5px 10px;
                border-radius: 6px;
                font-size: 10px;
                margin-bottom: 4px;
            }
            .page-badge {
                font-size: 9px;
                opacity: 0.8;
            }
            .grid-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 10px;
            }
            .product-cell {
                width: 50%;
                vertical-align: top;
            }
            .empty-cell {
                visibility: hidden;
            }
            .product-card {
                border: 2px solid #e5e7eb;
                border-radius: 12px;
                padding: 10px;
                position: relative;
                background-color: #fafafa;
                text-align: center;
                height: 220px;
            }
            .starburst-badge {
                background-color: {$corSecundaria};
                color: #000000;
                font-weight: 900;
                font-size: 9px;
                padding: 3px 8px;
                border-radius: 20px;
                display: inline-block;
                margin-bottom: 5px;
            }
            .product-img-box {
                height: 100px;
                text-align: center;
                margin-bottom: 6px;
            }
            .product-img {
                max-height: 100px;
                max-width: 140px;
                object-fit: contain;
            }
            .no-img {
                height: 100px;
                line-height: 100px;
                background-color: #e5e7eb;
                color: #9ca3af;
                font-size: 9px;
                font-weight: bold;
                border-radius: 6px;
            }
            .product-brand {
                font-size: 8px;
                font-weight: bold;
                color: #6b7280;
                margin-bottom: 2px;
            }
            .product-name {
                font-size: 12px;
                font-weight: bold;
                color: #111827;
                height: 28px;
                overflow: hidden;
                margin-bottom: 6px;
            }
            .price-container {
                background-color: {$corPrimaria};
                color: #ffffff;
                border-radius: 8px;
                padding: 4px 8px;
                display: inline-block;
            }
            .currency {
                font-size: 10px;
                font-weight: bold;
                vertical-align: super;
            }
            .price-main {
                font-size: 22px;
                font-weight: 900;
            }
            .price-cents {
                font-size: 12px;
                font-weight: bold;
                vertical-align: super;
            }
            .unit-label {
                font-size: 9px;
                opacity: 0.8;
                margin-left: 2px;
            }
            .footer-banner {
                margin-top: 15px;
                text-align: center;
                font-size: 9px;
                color: #6b7280;
                border-top: 1px dashed #d1d5db;
                padding-top: 6px;
            }
        ";
    }
}
