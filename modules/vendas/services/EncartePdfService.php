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
     * Retorna o mapa centralizado de cores por tema
     */
    public static function getThemeColors($tema = 'red_gold')
    {
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

        return [
            'corPrimaria' => $corPrimaria,
            'corSecundaria' => $corSecundaria,
            'corDark' => $corDark,
            'corTextoTitulo' => $corTextoTitulo,
            'corSubtitulo' => $corSubtitulo,
        ];
    }

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
        $fraseCredito = "UM PRODUTO DESENVOLVIDO PELA ONLY CODE - WHATSAPP 81 9 9288-8872 - JOSE BARBOSA DOS SANTOS, CARUARU/PE";

        $cores = self::getThemeColors($encarte->cor_tema);
        $corPrimaria = $cores['corPrimaria'];
        $corSecundaria = $cores['corSecundaria'];

        $totalPaginas = count($paginas);

        ob_start();
        ?>
        <div class="encarte-document">
            <?php foreach ($paginas as $indexPagina => $itensPagina): 
                $countItens = count($itensPagina);
                
                // Configuração de dimensões mPDF por densidade
                $colsPerRow = 2;
                $imgBoxHeight = 110;
                $imgMaxHeight = 100;
                $titleFontSize = '11px';
                $priceIntSize = '28px';
                $priceDecSize = '14px';

                if ($countItens <= 2) {
                    $colsPerRow = 2;
                    $imgBoxHeight = 260;
                    $imgMaxHeight = 240;
                    $titleFontSize = '15px';
                    $priceIntSize = '42px';
                    $priceDecSize = '20px';
                } elseif ($countItens <= 4) {
                    $colsPerRow = 2;
                    $imgBoxHeight = 160;
                    $imgMaxHeight = 150;
                    $titleFontSize = '13px';
                    $priceIntSize = '34px';
                    $priceDecSize = '16px';
                } elseif ($countItens <= 6) {
                    $colsPerRow = 2;
                    $imgBoxHeight = 110;
                    $imgMaxHeight = 100;
                    $titleFontSize = '11px';
                    $priceIntSize = '28px';
                    $priceDecSize = '14px';
                } elseif ($countItens <= 12) {
                    $colsPerRow = 3;
                    $imgBoxHeight = 65;
                    $imgMaxHeight = 60;
                    $titleFontSize = '9px';
                    $priceIntSize = '20px';
                    $priceDecSize = '10px';
                } else {
                    // 13 a 18 produtos (5x3 ou 6x3)
                    $colsPerRow = 3;
                    $imgBoxHeight = 45;
                    $imgMaxHeight = 40;
                    $titleFontSize = '8px';
                    $priceIntSize = '15px';
                    $priceDecSize = '8px';
                }

                $colWidthPercent = ($colsPerRow == 3) ? '33.33%' : '50%';
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
                        <table class="grid-table" width="100%" cellpadding="0" cellspacing="6">
                            <?php 
                            $chunksLinhas = array_chunk($itensPagina, $colsPerRow);
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
                                        <td class="product-cell" width="<?= $colWidthPercent ?>" valign="top">
                                            <table class="card-inner-table" width="100%" cellpadding="0" cellspacing="0">
                                                
                                                <!-- 1. Tag de Oferta -->
                                                <tr>
                                                    <td align="center" style="padding-top: 4px; padding-bottom: 2px;">
                                                        <span class="starburst-badge">OFERTA IMPERDÍVEL</span>
                                                    </td>
                                                </tr>

                                                <!-- 2. Box de Imagem com limitação estrita de altura -->
                                                <tr>
                                                    <td align="center" valign="middle" style="height: <?= $imgBoxHeight ?>px; background-color: #f8fafc; border-radius: 6px; padding: 4px;">
                                                        <?php if ($srcFoto): ?>
                                                            <img src="<?= $srcFoto ?>" style="max-height: <?= $imgMaxHeight ?>px; max-width: 95%; width: auto; height: auto;" alt="<?= Html::encode($produto->nome) ?>">
                                                        <?php else: ?>
                                                            <div style="line-height: <?= $imgMaxHeight ?>px; color: #94a3b8; font-size: 9px; font-weight: bold;">FOTO DO PRODUTO</div>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>

                                                <!-- 3. Marca / Referência -->
                                                <tr>
                                                    <td align="center" style="padding-top: 4px; padding-bottom: 2px; font-size: 7px; color: #64748b; font-weight: bold;">
                                                        <?php if ($produto->marca): ?>
                                                            <span style="background-color: #e2e8f0; color: #334155; padding: 1px 3px; border-radius: 2px;"><?= Html::encode(mb_strtoupper($produto->marca)) ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($codigoRef): ?>
                                                            <span style="color: #94a3b8; margin-left: 3px;">REF: <?= Html::encode($codigoRef) ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>

                                                <!-- 4. Nome do Produto -->
                                                <tr>
                                                    <td align="center" valign="middle" style="padding: 2px 4px;">
                                                        <div class="product-title-text" style="font-size: <?= $titleFontSize ?>;"><?= Html::encode(mb_strtoupper($produto->nome)) ?></div>
                                                    </td>
                                                </tr>

                                                <!-- 5. Splash de Preço NATIVO mPDF -->
                                                <tr>
                                                    <td align="center" style="padding: 4px 2px 6px 2px;">
                                                        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: <?= $corPrimaria ?>; border: 1.5px solid <?= $corSecundaria ?>; border-radius: 6px;">
                                                            <tr>
                                                                <td align="center" style="font-size: 7px; font-weight: 900; color: <?= $corSecundaria ?>; letter-spacing: 0.5px; padding-top: 2px; padding-bottom: 1px; background-color: <?= $corPrimaria ?>;">
                                                                    PREÇO ESPECIAL
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td align="center" valign="baseline" style="line-height: 1; padding-bottom: 4px; background-color: <?= $corPrimaria ?>;">
                                                                    <span style="font-size: 10px; font-weight: 900; color: #ffffff; vertical-align: super;">R$</span>
                                                                    <span style="font-size: <?= $priceIntSize ?>; font-weight: 900; color: #ffffff; letter-spacing: -1px;"><?= $partesPreco[0] ?></span>
                                                                    <span style="font-size: <?= $priceDecSize ?>; font-weight: 900; color: #ffffff; vertical-align: super;">,<?= $partesPreco[1] ?></span>
                                                                    <span style="font-size: 8px; font-weight: bold; color: <?= $corSecundaria ?>; margin-left: 2px;">/<?= Html::encode(mb_strtoupper($produto->unidade_medida ?: 'UN')) ?></span>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>

                                            </table>
                                        </td>
                                    <?php endforeach; ?>
                                    
                                    <!-- Célula vazia se a linha for incompleta -->
                                    <?php 
                                    $faltam = $colsPerRow - count($linha);
                                    for ($f = 0; $f < $faltam; $f++): 
                                    ?>
                                        <td width="<?= $colWidthPercent ?>"></td>
                                    <?php endfor; ?>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>

                    <!-- Rodapé da Lâmina Pinned no Fim da Página -->
                    <div class="footer-banner">
                        <table class="footer-table" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="footer-left" width="70%" align="left">
                                    *Ofertas válidas enquanto durarem os estoques. Fotos ilustrativas. <?= $fraseCredito ?>
                                </td>
                                <td class="footer-right" width="30%" align="right">
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
        $cores = self::getThemeColors($tema);
        $corPrimaria = $cores['corPrimaria'];
        $corSecundaria = $cores['corSecundaria'];
        $corDark = $cores['corDark'];
        $corTextoTitulo = $cores['corTextoTitulo'];
        $corSubtitulo = $cores['corSubtitulo'];

        return "
            @page {
                margin: 0;
                padding: 0;
            }
            body {
                font-family: 'Helvetica', 'Arial', sans-serif;
                background-color: #ffffff;
                color: #0f172a;
                margin: 0;
                padding: 0;
            }
            .page-break {
                page-break-before: always;
            }
            .lamina-page {
                width: 100%;
                height: 100%;
                box-sizing: border-box;
                padding: 15px;
                position: relative;
            }
            .header-banner {
                background-color: {$corPrimaria};
                color: #ffffff;
                border-radius: 12px;
                padding: 12px 16px;
                margin-bottom: 10px;
                border-bottom: 4px solid {$corSecundaria};
            }
            .store-badge {
                display: inline-block;
                background-color: {$corSecundaria};
                color: #000000;
                font-size: 8px;
                font-weight: 900;
                padding: 2px 8px;
                border-radius: 4px;
                text-transform: uppercase;
                margin-bottom: 4px;
            }
            .main-title {
                font-size: 18px;
                font-weight: 900;
                color: {$corTextoTitulo};
                margin: 0;
                padding: 0;
                line-height: 1.1;
                text-transform: uppercase;
            }
            .sub-title {
                font-size: 10px;
                color: {$corSubtitulo};
                font-weight: bold;
                margin-top: 2px;
            }
            .whatsapp-box {
                background-color: rgba(255, 255, 255, 0.15);
                padding: 4px 8px;
                border-radius: 6px;
                font-size: 8px;
                color: #ffffff;
                text-align: right;
                display: inline-block;
                margin-bottom: 4px;
            }
            .wp-number {
                font-size: 11px;
                font-weight: 900;
                color: {$corSecundaria};
            }
            .page-badge {
                font-size: 9px;
                font-weight: bold;
                color: #ffffff;
                background-color: rgba(0, 0, 0, 0.3);
                padding: 2px 6px;
                border-radius: 4px;
                display: inline-block;
            }
            .grid-container {
                width: 100%;
                margin-bottom: 10px;
            }
            .product-cell {
                background-color: #ffffff;
                border: 1.5px solid #e2e8f0;
                border-radius: 10px;
                padding: 4px;
                box-sizing: border-box;
            }
            .starburst-badge {
                background-color: {$corSecundaria};
                color: #000000;
                font-size: 7px;
                font-weight: 900;
                padding: 1px 6px;
                border-radius: 10px;
                text-transform: uppercase;
            }
            .product-title-text {
                font-weight: 900;
                color: #0f172a;
                line-height: 1.2;
                text-transform: uppercase;
            }
            .footer-banner {
                position: absolute;
                bottom: 12px;
                left: 15px;
                right: 15px;
                border-top: 1px solid #cbd5e1;
                padding-top: 6px;
                font-size: 7px;
                color: #64748b;
            }
        ";
    }
}
