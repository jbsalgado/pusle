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
     * @param string $destination Destino do mPDF (DEST_BROWSER, DEST_FILE, DEST_STRING)
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
                'SetHeader' => [''],
                'SetFooter' => [''],
            ]
        ]);

        return $pdf->render();
    }

    /**
     * Monta a estrutura HTML completa do folheto impresso A4 usando tabelas mPDF de alta precisão
     */
    private static function renderHtmlTabloide(Encarte $encarte, array $paginas, $loja)
    {
        $titulo = Html::encode($encarte->titulo ?: 'OFERTA IMBATÍVEL DA SEMANA');
        $subtitulo = Html::encode($encarte->subtitulo ?: 'Ofertas válidas enquanto durarem os estoques!');
        $nomeLoja = $loja ? Html::encode($loja->nome ?: 'Nossa Loja') : 'Pulse Vendas';
        $telefoneLoja = $loja ? Html::encode($loja->telefone ?: '') : '';

        $totalPaginas = count($paginas);

        ob_start();
        ?>
        <div class="encarte-document">
            <?php foreach ($paginas as $indexPagina => $itensPagina): 
                $countItens = count($itensPagina);
                
                // Configuração das alturas de imagem e tamanhos de fonte mPDF baseadas na densidade
                $imgBoxHeight = 110;
                $imgMaxHeight = 100;
                $titleFontSize = '11px';
                $priceIntSize = '28px';
                $priceDecSize = '14px';

                if ($countItens <= 2) {
                    $imgBoxHeight = 260;
                    $imgMaxHeight = 240;
                    $titleFontSize = '15px';
                    $priceIntSize = '42px';
                    $priceDecSize = '20px';
                } elseif ($countItens <= 4) {
                    $imgBoxHeight = 160;
                    $imgMaxHeight = 150;
                    $titleFontSize = '13px';
                    $priceIntSize = '34px';
                    $priceDecSize = '16px';
                }
            ?>
                <div class="lamina-page <?= $indexPagina > 0 ? 'page-break' : '' ?>">
                    
                    <!-- Cabeçalho Promocional Tabloide -->
                    <div class="header-banner">
                        <table class="header-table" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="header-left" width="65%" valign="middle">
                                    <div class="store-badge"><?= mb_strtoupper($nomeLoja) ?></div>
                                    <h1 class="main-title"><?= mb_strtoupper($titulo) ?></h1>
                                    <div class="sub-title"><?= $subtitulo ?></div>
                                </td>
                                <td class="header-right" width="35%" valign="middle" align="right">
                                    <?php if ($telefoneLoja): ?>
                                        <div class="whatsapp-box">
                                            Peça pelo WhatsApp:<br>
                                            <strong class="wp-number"><?= $telefoneLoja ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    <div class="page-badge">Lâmina <?= ($indexPagina + 1) ?> de <?= $totalPaginas ?></div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Grade de Produtos em Tabela mPDF Perfeita -->
                    <div class="grid-container">
                        <table class="grid-table" width="100%" cellpadding="0" cellspacing="8">
                            <?php 
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

                                        $codigoRef = $produto->codigo_barras ?: $produto->codigo_referencia ?: '';
                                    ?>
                                        <td class="product-cell" width="50%" valign="top">
                                            <table class="card-inner-table" width="100%" cellpadding="0" cellspacing="0">
                                                
                                                <!-- 1. Tag de Oferta -->
                                                <tr>
                                                    <td align="center" style="padding-top: 6px; padding-bottom: 4px;">
                                                        <span class="starburst-badge">OFERTA IMPERDÍVEL</span>
                                                    </td>
                                                </tr>

                                                <!-- 2. Box de Imagem com limitação estrita de altura -->
                                                <tr>
                                                    <td align="center" valign="middle" style="height: <?= $imgBoxHeight ?>px; background-color: #f8fafc; border-radius: 8px; padding: 6px;">
                                                        <?php if ($srcFoto): ?>
                                                            <img src="<?= $srcFoto ?>" style="max-height: <?= $imgMaxHeight ?>px; max-width: 95%; width: auto; height: auto;" alt="<?= Html::encode($produto->nome) ?>">
                                                        <?php else: ?>
                                                            <div style="line-height: <?= $imgMaxHeight ?>px; color: #94a3b8; font-size: 10px; font-weight: bold;">FOTO DO PRODUTO</div>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>

                                                <!-- 3. Marca / Referência -->
                                                <tr>
                                                    <td align="center" style="padding-top: 6px; padding-bottom: 2px; font-size: 8px; color: #64748b; font-weight: bold;">
                                                        <?php if ($produto->marca): ?>
                                                            <span style="background-color: #e2e8f0; color: #334155; padding: 1px 4px; border-radius: 3px;"><?= Html::encode(mb_strtoupper($produto->marca)) ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($codigoRef): ?>
                                                            <span style="color: #94a3b8; margin-left: 4px;">REF: <?= Html::encode($codigoRef) ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>

                                                <!-- 4. Nome do Produto -->
                                                <tr>
                                                    <td align="center" valign="middle" style="padding: 4px 6px;">
                                                        <div class="product-title-text" style="font-size: <?= $titleFontSize ?>;"><?= Html::encode(mb_strtoupper($produto->nome)) ?></div>
                                                    </td>
                                                </tr>

                                                <!-- 5. Splash de Preço -->
                                                <tr>
                                                    <td align="center" style="padding: 6px 4px 8px 4px;">
                                                        <div class="price-splash">
                                                            <table width="100%" cellpadding="0" cellspacing="0">
                                                                <tr>
                                                                    <td align="center" class="price-label">PREÇO ESPECIAL</td>
                                                                </tr>
                                                                <tr>
                                                                    <td align="center" valign="baseline" style="line-height: 1;">
                                                                        <span class="price-curr">R$</span>
                                                                        <span class="price-int" style="font-size: <?= $priceIntSize ?>;"><?= $partesPreco[0] ?></span>
                                                                        <span class="price-dec" style="font-size: <?= $priceDecSize ?>;">,<?= $partesPreco[1] ?></span>
                                                                        <span class="price-un">/<?= Html::encode(mb_strtoupper($produto->unidade_medida ?: 'UN')) ?></span>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                    </td>
                                                </tr>

                                            </table>
                                        </td>
                                    <?php endforeach; ?>
                                    
                                    <!-- Célula vazia se a linha for ímpar -->
                                    <?php if (count($linha) == 1): ?>
                                        <td width="50%"></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>

                    <!-- Rodapé da Lâmina Pinned no Fim da Página -->
                    <div class="footer-banner">
                        <table class="footer-table" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="footer-left" width="75%" align="left">
                                    *Ofertas válidas enquanto durarem os estoques. Fotos meramente ilustrativas. Reservamo-nos o direito de corrigir eventuais erros de digitação.
                                </td>
                                <td class="footer-right" width="25%" align="right">
                                    <strong><?= mb_strtoupper($nomeLoja) ?></strong> • Pulse Vendas
                                </td>
                            </tr>
                        </table>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Retorna o CSS do Folheto Promocional otimizado para o mPDF
     */
    private static function getCssTabloide($tema = 'red_gold')
    {
        // Paletas de Cores por Tema
        $corPrimaria = '#dc2626';     // Vermelho Varejo
        $corSecundaria = '#f59e0b';   // Amarelo Ouro
        $corDark = '#991b1b';         // Vermelho Escuro
        $corTextoTitulo = '#ffffff';  // Branco
        $corSubtitulo = '#fef08a';    // Amarelo Suave

        if ($tema === 'emerald_fresh') {
            $corPrimaria = '#059669';
            $corSecundaria = '#f59e0b';
            $corDark = '#064e3b';
            $corTextoTitulo = '#ffffff';
            $corSubtitulo = '#a7f3d0';
        } elseif ($tema === 'ocean_blue') {
            $corPrimaria = '#1d4ed8';
            $corSecundaria = '#f59e0b';
            $corDark = '#1e3a8a';
            $corTextoTitulo = '#ffffff';
            $corSubtitulo = '#bfdbfe';
        } elseif ($tema === 'dark_vip') {
            $corPrimaria = '#18181b';
            $corSecundaria = '#f59e0b';
            $corDark = '#09090b';
            $corTextoTitulo = '#fbbf24'; // Dourado VIP
            $corSubtitulo = '#ffffff';
        }

        return "
            @page {
                margin: 5mm;
            }
            body {
                font-family: Arial, Helvetica, sans-serif;
                color: #0f172a;
                background-color: #ffffff;
                margin: 0;
                padding: 0;
            }
            .encarte-document {
                width: 100%;
            }
            .page-break {
                page-break-before: always;
            }
            .lamina-page {
                width: 100%;
                box-sizing: border-box;
            }

            /* Header Banner */
            .header-banner {
                background-color: {$corPrimaria};
                color: {$corTextoTitulo};
                padding: 10px 14px;
                border-radius: 10px;
                margin-bottom: 10px;
                border-bottom: 4px solid {$corSecundaria};
            }
            .header-table {
                width: 100%;
                border-collapse: collapse;
            }
            .store-badge {
                background-color: {$corSecundaria} !important;
                color: #000000 !important;
                font-weight: 900;
                font-size: 10px;
                display: inline-block;
                padding: 2px 8px;
                border-radius: 4px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            h1, .main-title, h1.main-title {
                color: {$corTextoTitulo} !important;
                font-size: 20px;
                font-weight: 900;
                margin: 4px 0 2px 0;
                text-transform: uppercase;
                letter-spacing: -0.5px;
                line-height: 1.1;
            }
            .sub-title {
                color: {$corSubtitulo} !important;
                font-size: 10px;
                font-weight: bold;
            }
            .whatsapp-box {
                background-color: #ffffff !important;
                color: #0f172a !important;
                border: 2px solid #22c55e;
                padding: 4px 10px;
                border-radius: 8px;
                font-size: 9px;
                font-weight: bold;
                display: inline-block;
                text-align: center;
                margin-bottom: 4px;
            }
            .wp-number {
                color: #16a34a !important;
                font-size: 11px;
                font-weight: 900;
            }
            .page-badge {
                font-size: 9px;
                color: {$corTextoTitulo};
                opacity: 0.85;
            }

            /* Inner Card Table */
            .grid-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 10px;
            }
            .product-cell {
                width: 50%;
                vertical-align: top;
            }
            .card-inner-table {
                border: 2px solid #cbd5e1;
                border-radius: 12px;
                background-color: #ffffff;
                padding: 6px;
            }

            /* Selo Starburst */
            .starburst-badge {
                background-color: {$corSecundaria} !important;
                color: #000000 !important;
                font-weight: 900;
                font-size: 9px;
                padding: 3px 8px;
                border-radius: 12px;
                display: inline-block;
                text-transform: uppercase;
                border: 1px solid #d97706;
            }

            /* Titulo do Produto */
            .product-title-text {
                font-weight: 900;
                color: #0f172a;
                text-transform: uppercase;
                line-height: 1.2;
                text-align: center;
            }

            /* Container de Preço */
            .price-splash {
                background-color: {$corPrimaria};
                color: #ffffff !important;
                border-radius: 10px;
                padding: 6px 8px;
                border: 2px solid {$corSecundaria};
                text-align: center;
            }
            .price-label {
                font-size: 8px;
                font-weight: 900;
                color: {$corSecundaria} !important;
                letter-spacing: 0.5px;
                padding-bottom: 2px;
            }
            .price-curr {
                font-size: 12px;
                font-weight: 900;
                color: #ffffff !important;
                vertical-align: super;
            }
            .price-int {
                font-weight: 900;
                color: #ffffff !important;
                letter-spacing: -1px;
            }
            .price-dec {
                font-weight: 900;
                color: #ffffff !important;
                vertical-align: super;
            }
            .price-un {
                font-size: 9px;
                font-weight: bold;
                color: {$corSecundaria} !important;
                margin-left: 2px;
            }

            /* Rodapé Banner */
            .footer-banner {
                margin-top: 10px;
                background-color: #f1f5f9;
                border-top: 2px dashed #cbd5e1;
                padding: 6px 10px;
                border-radius: 6px;
                color: #475569;
                font-size: 8px;
            }
            .footer-table {
                width: 100%;
                border-collapse: collapse;
            }
        ";
    }
}
