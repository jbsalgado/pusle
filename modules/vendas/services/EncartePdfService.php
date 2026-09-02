<?php

namespace app\modules\vendas\services;

use Yii;
use app\modules\vendas\models\Encarte;
use app\modules\vendas\models\LojaConfiguracao;
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
     * Gera o objeto mPDF para o encarte informado com Capa Profissional e Lâminas A4.
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

        // Busca dados cadastrais detalhados da loja se existirem
        $lojaConfig = null;
        if ($loja && $loja->id) {
            $lojaConfig = LojaConfiguracao::findOne(['usuario_id' => $loja->id]);
        }

        // Divisão dos produtos em páginas (lâminas)
        $paginas = array_chunk($encarteProdutos, $ppp);

        $html = self::renderHtmlTabloide($encarte, $paginas, $loja, $lojaConfig);
        $cssInline = self::getCssTabloide($encarte->cor_tema);

        $tempDir = Yii::getAlias('@runtime/mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }
        if (!is_writable($tempDir)) {
            $tempDir = sys_get_temp_dir() . '/mpdf';
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0777, true);
            }
        }

        $pdf = new Pdf([
            'mode' => Pdf::MODE_UTF8,
            'format' => Pdf::FORMAT_A4,
            'orientation' => Pdf::ORIENT_PORTRAIT,
            'destination' => $destination,
            'tempPath' => $tempDir,
            'marginTop' => 4,
            'marginBottom' => 4,
            'marginLeft' => 4,
            'marginRight' => 4,
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

        $prevErrorLevel = error_reporting(E_ERROR | E_PARSE);
        $result = $pdf->render();
        error_reporting($prevErrorLevel);
        return $result;
    }

    /**
     * Monta a estrutura HTML completa: Capa Institucional (Página 1) + Lâminas de Produtos A4
     */
    public static function renderHtmlTabloide(Encarte $encarte, array $paginas, $loja, $lojaConfig = null)
    {
        $titulo = Html::encode($encarte->titulo ?: 'OFERTA IMBATÍVEL DA SEMANA');
        $subtitulo = Html::encode($encarte->subtitulo ?: 'Ofertas válidas enquanto durarem os estoques!');
        $nomeLoja = $lojaConfig && $lojaConfig->nome_fantasia ? Html::encode($lojaConfig->nome_fantasia) : ($loja ? Html::encode($loja->nome) : 'Pulse Vendas');
        $telefoneLoja = $lojaConfig && $lojaConfig->telefone ? Html::encode($lojaConfig->telefone) : ($loja ? Html::encode($loja->telefone ?: '') : '');
        $fraseCredito = "UM PRODUTO DESENVOLVIDO PELA ONLY CODE - WHATSAPP 81 9 9288-8872 - JOSE BARBOSA DOS SANTOS, CARUARU/PE";

        $cores = self::getThemeColors($encarte->cor_tema);
        $corPrimaria = $cores['corPrimaria'];
        $corSecundaria = $cores['corSecundaria'];

        $totalLaminas = count($paginas);
        $totalProdutos = count($encarte->encarteProdutos);
        $totalFolhasPdf = $totalLaminas + 1; // 1 Capa + N Lâminas

        ob_start();
        ?>
        <div class="encarte-document">
            
            <!-- ========================================== -->
            <!-- PÁGINA 1: CAPA PROFISSIONAL DA EMPRESA    -->
            <!-- ========================================== -->
            <?= self::renderCapaTabloide($encarte, $loja, $lojaConfig, $totalLaminas, $totalProdutos, $totalFolhasPdf) ?>

            <!-- ========================================== -->
            <!-- PÁGINAS 2 EM DIANTE: LÂMINAS DE PRODUTOS   -->
            <!-- ========================================== -->
            <?php foreach ($paginas as $indexPagina => $itensPagina): 
                $countItens = count($itensPagina);
                $numeroPaginaPdf = $indexPagina + 2;
                
                // Configurações calibradas para encaixar exatamente em 1 página A4 sem overflow
                $colsPerRow   = 3;
                $numLinhas    = 4;
                $imgBoxHeight = 110;
                $imgMaxHeight = 100;
                $titleFontSize   = '9px';
                $priceIntSize    = '18px';
                $priceDecSize    = '9px';
                $priceHeaderSize = '6px';
                $cardCellPadding = '2px 2px';
                $gridSpacing     = '2';
                $showBadge       = true;
                $showMarca       = true;
                $showRef         = true;

                if ($countItens <= 2) {
                    $colsPerRow      = 2;
                    $numLinhas       = 1;
                    $imgBoxHeight    = 520;
                    $imgMaxHeight    = 500;
                    $titleFontSize   = '18px';
                    $priceIntSize    = '48px';
                    $priceDecSize    = '24px';
                    $priceHeaderSize = '12px';
                    $cardCellPadding = '12px 12px';
                    $gridSpacing     = '8';
                    $showBadge       = true;
                    $showMarca       = true;
                    $showRef         = true;
                } elseif ($countItens <= 4) {
                    $colsPerRow      = 2;
                    $numLinhas       = 2;
                    $imgBoxHeight    = 250;
                    $imgMaxHeight    = 238;
                    $titleFontSize   = '14px';
                    $priceIntSize    = '34px';
                    $priceDecSize    = '17px';
                    $priceHeaderSize = '9px';
                    $cardCellPadding = '8px 8px';
                    $gridSpacing     = '6';
                    $showBadge       = true;
                    $showMarca       = true;
                    $showRef         = true;
                } elseif ($countItens <= 6) {
                    $colsPerRow      = 2;
                    $numLinhas       = 3;
                    $imgBoxHeight    = 160;
                    $imgMaxHeight    = 150;
                    $titleFontSize   = '12px';
                    $priceIntSize    = '26px';
                    $priceDecSize    = '13px';
                    $priceHeaderSize = '8px';
                    $cardCellPadding = '5px 5px';
                    $gridSpacing     = '5';
                    $showBadge       = true;
                    $showMarca       = true;
                    $showRef         = true;
                } elseif ($countItens <= 8) {
                    $colsPerRow      = 2;
                    $numLinhas       = 4;
                    $imgBoxHeight    = 115;
                    $imgMaxHeight    = 108;
                    $titleFontSize   = '11px';
                    $priceIntSize    = '22px';
                    $priceDecSize    = '11px';
                    $priceHeaderSize = '7px';
                    $cardCellPadding = '4px 4px';
                    $gridSpacing     = '4';
                    $showBadge       = true;
                    $showMarca       = true;
                    $showRef         = true;
                } elseif ($countItens <= 9) {
                    $colsPerRow      = 3;
                    $numLinhas       = 3;
                    $imgBoxHeight    = 160;
                    $imgMaxHeight    = 150;
                    $titleFontSize   = '11px';
                    $priceIntSize    = '24px';
                    $priceDecSize    = '12px';
                    $priceHeaderSize = '7.5px';
                    $cardCellPadding = '4px 4px';
                    $gridSpacing     = '4';
                    $showBadge       = true;
                    $showMarca       = true;
                    $showRef         = true;
                } elseif ($countItens <= 12) {
                    $colsPerRow      = 3;
                    $numLinhas       = 4;
                    $imgBoxHeight    = 115;
                    $imgMaxHeight    = 108;
                    $titleFontSize   = '10px';
                    $priceIntSize    = '20px';
                    $priceDecSize    = '10px';
                    $priceHeaderSize = '6.5px';
                    $cardCellPadding = '3px 3px';
                    $gridSpacing     = '3';
                    $showBadge       = true;
                    $showMarca       = true;
                    $showRef         = ($countItens <= 10);
                } elseif ($countItens <= 15) {
                    $colsPerRow      = 3;
                    $numLinhas       = 5;
                    $imgBoxHeight    = 88;
                    $imgMaxHeight    = 82;
                    $titleFontSize   = '8.5px';
                    $priceIntSize    = '16px';
                    $priceDecSize    = '8px';
                    $priceHeaderSize = '6px';
                    $cardCellPadding = '2px 3px';
                    $gridSpacing     = '2';
                    $showBadge       = true;
                    $showMarca       = true;
                    $showRef         = false;
                } else {
                    $colsPerRow      = 3;
                    $numLinhas       = 6;
                    $imgBoxHeight    = 88;
                    $imgMaxHeight    = 82;
                    $titleFontSize   = '9px';
                    $priceIntSize    = '18px';
                    $priceDecSize    = '9px';
                    $priceHeaderSize = '6px';
                    $cardCellPadding = '3px 3px';
                    $gridSpacing     = '3';
                    $showBadge       = false;
                    $showMarca       = ($countItens <= 18);
                    $showRef         = false;
                }


                $colWidthPercent = ($colsPerRow == 3) ? '33.33%' : '50%';
                $isUltimaPagina  = ($indexPagina === $totalLaminas - 1);
            ?>
                <div class="lamina-page" style="<?= !$isUltimaPagina ? 'page-break-after: always;' : '' ?>">
                    
                    <!-- Cabeçalho Promocional Tabloide Compacto -->
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
                                            Peça no Zap: <strong class="wp-number"><?= $telefoneLoja ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    <div class="page-badge">Lâmina <?= ($indexPagina + 1) ?> de <?= $totalLaminas ?> • Pág. <?= $numeroPaginaPdf ?>/<?= $totalFolhasPdf ?></div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Grade de Produtos em Tabela mPDF -->
                    <div class="grid-container">
                        <table class="grid-table" width="100%" cellpadding="0" cellspacing="<?= $gridSpacing ?>">
                            <?php 
                            $chunksLinhas = array_chunk($itensPagina, $colsPerRow);
                            foreach ($chunksLinhas as $linha): 
                            ?>
                                <tr style="page-break-inside: avoid;">
                                    <?php foreach ($linha as $encarteProd): 
                                        $produto = $encarteProd->produto;
                                        if (!$produto) continue;

                                        $precoVal = $encarteProd->getPrecoFinal();
                                        $precoFormatado = number_format($precoVal, 2, ',', '.');
                                        $partesPreco = explode(',', $precoFormatado);
                                        $codigoRef = $produto->codigo_referencia ?: $produto->codigo_barras;

                                        // Foto: busca relação de fotos do produto (fotoPrincipal ou fotos[0]) com fallback para categoria
                                        $srcFoto = null;
                                        $foto = $produto->fotoPrincipal ?: ($produto->fotos[0] ?? null);
                                        if ($foto && $foto->arquivo_path) {
                                            $caminhoLocal = Yii::getAlias('@app/web/') . ltrim($foto->arquivo_path, '/');
                                            if (file_exists($caminhoLocal)) {
                                                $srcFoto = $caminhoLocal;
                                            } else {
                                                $srcFoto = Url::to('@web/' . ltrim($foto->arquivo_path, '/'), true);
                                            }
                                        } elseif ($produto->categoria && !empty($produto->categoria->foto_path)) {
                                            $caminhoLocalCat = Yii::getAlias('@app/web/') . ltrim($produto->categoria->foto_path, '/');
                                            if (file_exists($caminhoLocalCat)) {
                                                $srcFoto = $caminhoLocalCat;
                                            }
                                        }

                                        // Badge do produto
                                        $badgeTextoPdf = "OFERTA";
                                        $exibirBadgePdf = $showBadge;
                                        $tagCustom = $encarteProd->tag_promocional ?: $encarte->tag_promocional;
                                        if ($tagCustom) {
                                            $exibirBadgePdf = true;
                                            if ($tagCustom === 'DESTAQUE') {
                                                $badgeTextoPdf = "DESTAQUE";
                                            } elseif ($tagCustom === 'QUEIMA_ESTOQUE') {
                                                $badgeTextoPdf = "QUEIMA TOTAL";
                                            } elseif ($tagCustom === 'SUPER_OFERTA') {
                                                $badgeTextoPdf = "SUPER OFERTA";
                                            } elseif ($tagCustom === 'NOVIDADE') {
                                                $badgeTextoPdf = "NOVIDADE";
                                            } elseif ($tagCustom === 'OFERTA') {
                                                $badgeTextoPdf = "OFERTA";
                                            }
                                        } else {
                                            if ($exibirBadgePdf && $encarteProd->destaque) {
                                                $badgeTextoPdf = "SUPER OFERTA";
                                            }
                                        }
                                    ?>
                                        <td class="product-cell" width="<?= $colWidthPercent ?>" valign="top" style="padding: <?= $cardCellPadding ?>;">
                                            <table class="card-inner-table" width="100%" cellpadding="0" cellspacing="0">
                                                
                                                <!-- 1. Badge de Oferta -->
                                                <?php if ($exibirBadgePdf): ?>
                                                <tr>
                                                    <td align="center" style="padding-top: 1px; padding-bottom: 1px;">
                                                        <div class="starburst-badge"><?= $badgeTextoPdf ?></div>
                                                    </td>
                                                </tr>
                                                <?php endif; ?>

                                                <!-- 2. Box de Imagem -->
                                                <tr>
                                                    <td align="center" valign="middle" style="height: <?= $imgBoxHeight ?>px; background-color: #f8fafc; padding: 2px;">
                                                        <?php if ($srcFoto): ?>
                                                            <img src="<?= $srcFoto ?>" height="<?= $imgMaxHeight ?>" style="height: <?= $imgMaxHeight ?>px; max-width: 90%;" alt="<?= Html::encode($produto->nome) ?>">
                                                        <?php else: ?>
                                                            <div style="height: <?= $imgMaxHeight ?>px; line-height: <?= $imgMaxHeight ?>px; color: #94a3b8; font-size: 7.5px; font-weight: bold; text-align: center;">SEM FOTO</div>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>

                                                <!-- 3. Marca / Referência -->
                                                <?php if ($showMarca && ($produto->marca || ($showRef && $codigoRef))): ?>
                                                <tr>
                                                    <td align="center" style="padding-top: 1px; font-size: 6.5px; color: #64748b; font-weight: bold;">
                                                        <?php if ($produto->marca): ?>
                                                            <div style="display: inline-block; background-color: #dbeafe; color: #1e40af; padding: 1px 3px; font-size: 6px;"><?= Html::encode(mb_strtoupper($produto->marca)) ?></div>
                                                        <?php endif; ?>
                                                        <?php if ($showRef && $codigoRef): ?>
                                                            <div style="display: inline-block; color: #94a3b8; margin-left: 2px; font-size: 6px;">REF: <?= Html::encode($codigoRef) ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endif; ?>

                                                <!-- 4. Nome do Produto -->
                                                <tr>
                                                    <td align="center" valign="middle" style="padding: 1px 3px 1px 3px;">
                                                        <div class="product-title-text" style="font-size: <?= $titleFontSize ?>; line-height: 1.15;"><?= Html::encode(mb_strtoupper($produto->nome)) ?></div>
                                                    </td>
                                                </tr>

                                                <!-- 5. Splash de Preco Premium -->
                                                <tr>
                                                    <td align="center" style="padding: 2px 2px 2px 2px;">
                                                        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: <?= $corPrimaria ?>; border: 2px solid <?= $corSecundaria ?>;">
                                                            <?php if ($showBadge): ?>
                                                            <tr>
                                                                <td align="center" style="font-size: <?= $priceHeaderSize ?>; font-weight: 900; color: <?= $corSecundaria ?>; padding-top: 2px; padding-bottom: 1px;">
                                                                    PRECO ESPECIAL
                                                                </td>
                                                            </tr>
                                                            <?php endif; ?>
                                                            <tr>
                                                                <td align="center" style="padding-bottom: 2px;">
                                                                    <span style="font-size: 7.5px; font-weight: 900; color: #fde68a; vertical-align: super;">R$</span>
                                                                    <span style="font-size: <?= $priceIntSize ?>; font-weight: 900; color: #ffffff;"><?= $partesPreco[0] ?></span>
                                                                    <span style="font-size: <?= $priceDecSize ?>; font-weight: 900; color: #fde68a; vertical-align: super;">,<?= $partesPreco[1] ?></span>
                                                                    <span style="font-size: 6px; font-weight: bold; color: <?= $corSecundaria ?>; margin-left: 1px;">/<?= Html::encode(mb_strtoupper($produto->unidade_medida ?: 'UN')) ?></span>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>

                                            </table>
                                        </td>
                                    <?php endforeach; ?>
                                    
                                    <!-- Celula vazia se a linha for incompleta -->
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

                    <!-- Rodapé da Lâmina no Fluxo Normal -->
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
     * Renderiza a Capa Profissional da Empresa como Página 1 do PDF A4
     */
    public static function renderCapaTabloide(Encarte $encarte, $loja, $lojaConfig, $totalLaminas, $totalProdutos, $totalFolhasPdf)
    {
        $titulo = Html::encode($encarte->titulo ?: 'OFERTA IMBATIVEL DA SEMANA');
        $subtitulo = Html::encode($encarte->subtitulo ?: 'Ofertas validas enquanto durarem os estoques!');
        $nomeFantasia = $lojaConfig && $lojaConfig->nome_fantasia ? Html::encode($lojaConfig->nome_fantasia) : ($loja ? Html::encode($loja->nome) : 'Nossa Loja');
        $razaoSocial  = $lojaConfig && $lojaConfig->razao_social  ? Html::encode($lojaConfig->razao_social)  : '';
        $cnpj         = $lojaConfig && $lojaConfig->cpf_cnpj      ? Html::encode($lojaConfig->cpf_cnpj)      : '';
        $telefone     = $lojaConfig && $lojaConfig->telefone       ? Html::encode($lojaConfig->telefone)      : ($loja ? Html::encode($loja->telefone ?: '') : '');
        $email        = $lojaConfig && $lojaConfig->email          ? Html::encode($lojaConfig->email)         : ($loja ? Html::encode($loja->email    ?: '') : '');
        $site         = $lojaConfig && $lojaConfig->site           ? Html::encode($lojaConfig->site)          : '';
        
        $enderecoPartes = [];
        if ($lojaConfig) {
            if ($lojaConfig->logradouro) $enderecoPartes[] = $lojaConfig->logradouro . ($lojaConfig->numero ? ', ' . $lojaConfig->numero : '');
            if ($lojaConfig->complemento) $enderecoPartes[] = $lojaConfig->complemento;
            if ($lojaConfig->bairro)      $enderecoPartes[] = $lojaConfig->bairro;
            if ($lojaConfig->cidade)      $enderecoPartes[] = $lojaConfig->cidade . ($lojaConfig->estado ? '/' . $lojaConfig->estado : '');
            if ($lojaConfig->cep)         $enderecoPartes[] = 'CEP ' . $lojaConfig->cep;
        }
        $enderecoCompleto = implode(' - ', $enderecoPartes);

        $logoPath = ($lojaConfig && $lojaConfig->logo_path) ? $lojaConfig->logo_path : ($loja ? $loja->logo_path : null);
        $logoSrc  = null;
        if (!empty($logoPath)) {
            $caminhoLogoAbs = Yii::getAlias('@app/web/') . ltrim($logoPath, '/');
            if (file_exists($caminhoLogoAbs)) {
                $logoSrc = $caminhoLogoAbs;
            }
        }

        $cores        = self::getThemeColors($encarte->cor_tema);
        $corPrimaria  = $cores['corPrimaria'];
        $corSecundaria = $cores['corSecundaria'];
        $corDark      = $cores['corDark'];
        $corTxtTitulo = $cores['corTextoTitulo'];
        $corSubtitulo = $cores['corSubtitulo'];

        ob_start();
        ?>
        <div class="cover-page">
            <!-- SUB-HEADER: Badge institucional -->
            <div class="cover-super-badge-wrap" align="center">
                <div class="cover-super-badge">** ENCARTE OFICIAL DE OFERTAS E PROMOCOES **</div>
            </div>

            <!-- LOGO OU EMBLEMA DA EMPRESA -->
            <div align="center" style="margin-top: 24px; margin-bottom: 15px;">
                <?php if ($logoSrc): ?>
                    <img src="<?= $logoSrc ?>" height="110" style="height: 110px; max-width: 320px;" alt="<?= $nomeFantasia ?>">
                    <div class="cover-store-name"><?= mb_strtoupper($nomeFantasia) ?></div>
                <?php else: ?>
                    <div class="cover-logo-emblem">
                        <div class="cover-emblem-text"><?= mb_strtoupper($nomeFantasia) ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if ($razaoSocial || $cnpj): ?>
                    <div class="cover-store-legal" style="margin-top: 8px;">
                        <?= $razaoSocial ?><?= ($razaoSocial && $cnpj) ? ' | ' : '' ?><?= $cnpj ? 'CNPJ: ' . $cnpj : '' ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- HERO BANNER -->
            <div class="cover-hero-banner" align="center">
                <div class="cover-hero-ribbon">** PRECOS ESPECIAIS DE TABLOIDE **</div>
                <div class="cover-hero-title"><?= mb_strtoupper($titulo) ?></div>
                <div class="cover-hero-subtitle"><?= $subtitulo ?></div>
                <div class="cover-hero-badge">QUALIDADE + ECONOMIA + ATENDIMENTO DIFERENCIADO</div>
            </div>

            <!-- ESTATISTICAS + WHATSAPP -->
            <div class="cover-stats-box">
                <table width="100%" cellpadding="0" cellspacing="6">
                    <tr>
                        <td width="33.33%" align="center" class="cover-stat-card">
                            <div class="cover-stat-number"><?= $totalProdutos ?></div>
                            <div class="cover-stat-label">OFERTAS SELECIONADAS</div>
                        </td>
                        <td width="33.33%" align="center" class="cover-stat-card">
                            <div class="cover-stat-number"><?= $totalLaminas ?></div>
                            <div class="cover-stat-label">LAMINAS DE PRODUTOS</div>
                        </td>
                        <td width="33.33%" align="center" class="cover-stat-card">
                            <div class="cover-stat-number">100%</div>
                            <div class="cover-stat-label">ECONOMIA REAL</div>
                        </td>
                    </tr>
                </table>

                <?php if ($telefone): ?>
                    <div class="cover-whatsapp-action" align="center">
                        <div class="cover-whatsapp-title">FALE CONOSCO PELO WHATSAPP</div>
                        <div class="cover-whatsapp-phone"><?= $telefone ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- CHAMADA PARA VIRAR A PAGINA -->
            <div class="cover-turn-page-prompt" align="center">
                &raquo;&raquo; <strong>VIRE A PAGINA E CONFIRA TODAS AS NOSSAS OFERTAS IMPERDIVEIS</strong> &laquo;&laquo;
            </div>

            <!-- RODAPE INSTITUCIONAL -->
            <div class="cover-footer-section">
                <table width="100%" cellpadding="4" cellspacing="0" class="cover-footer-table">
                    <?php if ($enderecoCompleto): ?>
                    <tr>
                        <td align="center" class="cover-footer-address">
                            <strong>Endereco:</strong> <?= $enderecoCompleto ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td align="center" class="cover-footer-contacts">
                            <?php if ($telefone): ?><strong>Tel/Zap:</strong> <?= $telefone ?><?php endif; ?>
                            <?php if ($email): ?>&nbsp;&nbsp;|&nbsp;&nbsp;<strong>E-mail:</strong> <?= $email ?><?php endif; ?>
                            <?php if ($site): ?>&nbsp;&nbsp;|&nbsp;&nbsp;<strong>Site:</strong> <?= $site ?><?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" class="cover-footer-credit">
                            Tabloide gerado em <?= date('d/m/Y') ?> | Sistema Pulse Vendas | Only Code
                        </td>
                    </tr>
                </table>
            </div>
        </div>


        <?php
        return ob_get_clean();
    }

    /**
     * Retorna o CSS do Folheto Promocional otimizado para o mPDF
     */
    public static function getCssTabloide($tema = 'red_gold')
    {
        $cores = self::getThemeColors($tema);
        $corPrimaria = $cores['corPrimaria'];
        $corSecundaria = $cores['corSecundaria'];
        $corDark = $cores['corDark'];
        $corTextoTitulo = $cores['corTextoTitulo'];
        $corSubtitulo = $cores['corSubtitulo'];

        return "
            /* ========= RESET BASE ========= */
            body {
                font-family: 'Helvetica', 'Arial', sans-serif;
                background-color: #ffffff;
                color: #0f172a;
                margin: 0;
                padding: 0;
            }

            /* ======================================================= */
            /* =================== CAPA PROFISSIONAL ================= */
            /* ======================================================= */
            .cover-page {
                width: 100%;
                box-sizing: border-box;
                border-left: 6mm solid {$corPrimaria};
                border-right: 6mm solid {$corPrimaria};
                padding: 6mm 8mm;
                background-color: #ffffff;
                page-break-inside: avoid;
                page-break-after: always;
            }

            /* Badge topo */
            .cover-super-badge-wrap {
                margin-bottom: 8px;
            }
            .cover-super-badge {
                display: inline-block;
                background-color: {$corPrimaria};
                color: #ffffff;
                font-size: 8.5px;
                font-weight: 900;
                padding: 5px 22px;
                text-transform: uppercase;
                border-bottom: 3px solid {$corSecundaria};
            }

            /* Logo / Emblema */
            .cover-logo-emblem {
                display: inline-block;
                background-color: {$corPrimaria};
                padding: 12px 28px;
                margin-bottom: 0;
            }
            .cover-emblem-text {
                font-size: 22px;
                font-weight: 900;
                color: #ffffff;
                text-transform: uppercase;
            }

            /* Nome da empresa */
            .cover-store-name {
                font-size: 22px;
                font-weight: 900;
                color: {$corPrimaria};
                margin: 6px 0 2px 0;
                text-transform: uppercase;
                text-align: center;
            }
            .cover-store-legal {
                font-size: 8px;
                color: #64748b;
                font-weight: bold;
                text-transform: uppercase;
                text-align: center;
            }

            /* Hero Banner */
            .cover-hero-banner {
                background-color: {$corPrimaria};
                border-top: 4px solid {$corSecundaria};
                border-bottom: 4px solid {$corSecundaria};
                padding: 18px 16px;
                margin-top: 14px;
                margin-bottom: 14px;
            }
            .cover-hero-ribbon {
                display: inline-block;
                background-color: {$corSecundaria};
                color: #000000;
                font-size: 9px;
                font-weight: 900;
                padding: 3px 14px;
                text-transform: uppercase;
                margin-bottom: 8px;
            }
            .cover-hero-title {
                font-size: 28px;
                font-weight: 900;
                color: {$corTextoTitulo};
                margin: 0 0 4px 0;
                padding: 0;
                line-height: 1.1;
                text-transform: uppercase;
            }
            .cover-hero-subtitle {
                font-size: 11px;
                font-weight: bold;
                color: {$corSubtitulo};
                margin-top: 3px;
                margin-bottom: 8px;
            }
            .cover-hero-badge {
                display: inline-block;
                background-color: {$corDark};
                color: {$corSecundaria};
                font-size: 8px;
                font-weight: 900;
                padding: 4px 14px;
                text-transform: uppercase;
            }

            /* Estatísticas */
            .cover-stats-box {
                background-color: #f8fafc;
                border: 1px solid #e2e8f0;
                padding: 12px;
                margin-bottom: 14px;
            }
            .cover-stat-card {
                background-color: #ffffff;
                border: 1.5px solid {$corPrimaria};
                padding: 8px 4px;
            }
            .cover-stat-number {
                font-size: 24px;
                font-weight: 900;
                color: {$corPrimaria};
                line-height: 1;
            }
            .cover-stat-label {
                font-size: 6.5px;
                font-weight: 900;
                color: #475569;
                margin-top: 3px;
                text-transform: uppercase;
            }

            /* WhatsApp box */
            .cover-whatsapp-action {
                background-color: #15803d;
                border: 2px solid #22c55e;
                padding: 8px 14px;
                margin-top: 10px;
            }
            .cover-whatsapp-title {
                font-size: 8px;
                font-weight: 900;
                color: #dcfce7;
                text-transform: uppercase;
            }
            .cover-whatsapp-phone {
                font-size: 22px;
                font-weight: 900;
                color: #ffffff;
                margin-top: 2px;
            }

            /* Chamada para virar página */
            .cover-turn-page-prompt {
                background-color: #fffbeb;
                border: 2px dashed {$corSecundaria};
                padding: 8px 14px;
                font-size: 9.5px;
                color: #92400e;
                margin-bottom: 14px;
                text-align: center;
            }

            /* Rodapé da capa */
            .cover-footer-section {
                border-top: 2px solid {$corPrimaria};
                padding-top: 8px;
                margin-top: 4px;
            }
            .cover-footer-address {
                font-size: 7.5px;
                color: #475569;
            }
            .cover-footer-contacts {
                font-size: 7.5px;
                color: #334155;
                font-weight: bold;
            }
            .cover-footer-credit {
                font-size: 6.5px;
                color: #94a3b8;
            }


            /* ======================================================= */
            /* ================= LAMINAS DE PRODUTOS ================= */
            /* ======================================================= */
            .lamina-page {
                width: 100%;
                box-sizing: border-box;
                padding: 3px 4px;
                page-break-inside: avoid;
                background-color: #ffffff;
            }

            /* Header da lamina */
            .header-banner {
                background-color: {$corPrimaria};
                color: #ffffff;
                padding: 4px 8px;
                margin-bottom: 3px;
                border-bottom: 2px solid {$corSecundaria};
            }
            .store-badge {
                display: inline-block;
                background-color: {$corSecundaria};
                color: #000000;
                font-size: 6px;
                font-weight: 900;
                padding: 1px 4px;
                text-transform: uppercase;
                margin-bottom: 1px;
            }
            .main-title {
                font-size: 13px;
                font-weight: 900;
                color: {$corTextoTitulo};
                margin: 0;
                padding: 0;
                line-height: 1;
                text-transform: uppercase;
            }
            .sub-title {
                font-size: 7px;
                color: {$corSubtitulo};
                font-weight: bold;
                margin-top: 1px;
            }
            .whatsapp-box {
                background-color: rgba(0, 0, 0, 0.2);
                padding: 2px 4px;
                font-size: 6.5px;
                color: #ffffff;
                text-align: right;
                display: inline-block;
                margin-bottom: 2px;
            }
            .wp-number {
                font-size: 8px;
                font-weight: 900;
                color: {$corSecundaria};
            }
            .page-badge {
                font-size: 6.5px;
                font-weight: bold;
                color: #ffffff;
                background-color: rgba(0, 0, 0, 0.35);
                padding: 1px 4px;
                display: inline-block;
            }

            /* Grid de produtos */
            .grid-container {
                width: 100%;
                margin-bottom: 2px;
                page-break-inside: avoid;
            }
            .grid-table {
                page-break-inside: avoid;
                width: 100%;
            }

            /* Card do produto */
            .product-cell {
                background-color: #ffffff;
                border: 1px solid #e2e8f0;
                box-sizing: border-box;
                page-break-inside: avoid;
                vertical-align: top;
            }
            .card-inner-table {
                width: 100%;
            }

            /* Badge de oferta */
            .starburst-badge {
                background-color: {$corSecundaria};
                color: #000000;
                font-size: 5.5px;
                font-weight: 900;
                padding: 1px 4px;
                text-transform: uppercase;
            }

            /* Nome do produto */
            .product-title-text {
                font-weight: 900;
                color: #0f172a;
                line-height: 1.15;
                text-transform: uppercase;
                text-align: center;
            }

            /* Rodape da lamina */
            .footer-banner {
                width: 100%;
                border-top: 1px solid {$corPrimaria};
                padding-top: 2px;
                font-size: 5.5px;
                color: #64748b;
                page-break-inside: avoid;
                background-color: #f8fafc;
                padding: 2px 4px;
            }
            .footer-table td {
                vertical-align: middle;
            }
            .footer-left {
                font-size: 5.5px;
                color: #94a3b8;
            }
            .footer-right {
                font-size: 6px;
                color: {$corPrimaria};
                font-weight: 900;
                text-align: right;
            }
        ";
    }
}
