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
     * Monta a estrutura HTML completa do folheto impresso A4
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
                // Determina classe de densidade para ajuste dinâmico de altura dos cards
                $densityClass = 'density-normal';
                if ($countItens <= 2) {
                    $densityClass = 'density-large-2';
                } elseif ($countItens <= 4) {
                    $densityClass = 'density-large-4';
                } elseif ($countItens > 6) {
                    $densityClass = 'density-compact';
                }
            ?>
                <div class="lamina-page <?= $indexPagina > 0 ? 'page-break' : '' ?> <?= $densityClass ?>">
                    
                    <!-- Cabeçalho Promocional da Lâmina -->
                    <div class="header-banner">
                        <table class="header-table">
                            <tr>
                                <td class="header-left">
                                    <div class="store-badge"><?= mb_strtoupper($nomeLoja) ?></div>
                                    <h1 class="main-title"><?= mb_strtoupper($titulo) ?></h1>
                                    <div class="sub-title"><?= $subtitulo ?></div>
                                </td>
                                <td class="header-right">
                                    <?php if ($telefoneLoja): ?>
                                        <div class="whatsapp-box">
                                            <span class="wp-icon">📱</span> Peça pelo WhatsApp:<br>
                                            <strong class="wp-number"><?= $telefoneLoja ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    <div class="page-badge">Lâmina <?= ($indexPagina + 1) ?> de <?= $totalPaginas ?></div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Grade de Produtos da Lâmina -->
                    <div class="grid-container">
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

                                        $codigoRef = $produto->codigo_barras ?: $produto->codigo_referencia ?: '';
                                    ?>
                                        <td class="product-cell <?= count($linha) == 1 ? 'cell-full' : '' ?>">
                                            <div class="product-card">
                                                
                                                <!-- Selo Promocional Starburst -->
                                                <div class="starburst-badge">
                                                    <span>OFERTA IMPERDÍVEL</span>
                                                </div>

                                                <!-- Imagem do Produto -->
                                                <div class="product-img-box">
                                                    <?php if ($srcFoto): ?>
                                                        <img src="<?= $srcFoto ?>" class="product-img" alt="<?= Html::encode($produto->nome) ?>">
                                                    <?php else: ?>
                                                        <div class="no-img">FOTO DO PRODUTO</div>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Informações do Produto -->
                                                <div class="product-info">
                                                    <div class="meta-row">
                                                        <?php if ($produto->marca): ?>
                                                            <span class="product-brand"><?= Html::encode(mb_strtoupper($produto->marca)) ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($codigoRef): ?>
                                                            <span class="product-ref">REF: <?= Html::encode($codigoRef) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="product-name"><?= Html::encode(mb_strtoupper($produto->nome)) ?></div>
                                                    
                                                    <!-- Splash Container de Preço -->
                                                    <div class="price-splash">
                                                        <div class="price-label">PREÇO ESPECIAL</div>
                                                        <div class="price-row">
                                                            <span class="currency">R$</span>
                                                            <span class="price-main"><?= $partesPreco[0] ?></span>
                                                            <span class="price-cents">,<?= $partesPreco[1] ?></span>
                                                            <span class="unit-label">/<?= Html::encode(mb_strtoupper($produto->unidade_medida ?: 'UN')) ?></span>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                    
                                    <!-- Célula vazia para alinhar se a linha tiver número ímpar de itens -->
                                    <?php if (count($linha) == 1): ?>
                                        <td class="product-cell empty-cell"></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>

                    <!-- Rodapé da Lâmina Pinned no Fim da Página -->
                    <div class="footer-banner">
                        <table class="footer-table">
                            <tr>
                                <td class="footer-left">
                                    *Ofertas válidas enquanto durarem os estoques. Fotos meramente ilustrativas. Reservamo-nos o direito de corrigir eventuais erros de digitação.
                                </td>
                                <td class="footer-right">
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
     * Retorna o CSS do Folheto Promocional para o mPDF
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
                padding: 12px 18px;
                border-radius: 12px;
                margin-bottom: 12px;
                border-bottom: 4px solid {$corSecundaria};
            }
            .header-table {
                width: 100%;
                border-collapse: collapse;
            }
            .header-left {
                text-align: left;
                width: 65%;
                vertical-align: middle;
            }
            .header-right {
                text-align: right;
                width: 35%;
                vertical-align: middle;
            }
            .store-badge {
                background-color: {$corSecundaria} !important;
                color: #000000 !important;
                font-weight: 900;
                font-size: 11px;
                display: inline-block;
                padding: 3px 10px;
                border-radius: 6px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            h1, .main-title, h1.main-title {
                color: {$corTextoTitulo} !important;
                font-size: 22px;
                font-weight: 900;
                margin: 6px 0 2px 0;
                text-transform: uppercase;
                letter-spacing: -0.5px;
                line-height: 1.1;
            }
            .sub-title {
                color: {$corSubtitulo} !important;
                font-size: 11px;
                font-weight: bold;
            }
            .whatsapp-box {
                background-color: #ffffff !important;
                color: #0f172a !important;
                border: 2px solid #22c55e;
                padding: 6px 12px;
                border-radius: 10px;
                font-size: 10px;
                display: inline-block;
                text-align: center;
                margin-bottom: 4px;
            }
            .wp-number {
                color: #16a34a !important;
                font-size: 12px;
                font-weight: 900;
            }
            .page-badge {
                font-size: 9px;
                color: {$corTextoTitulo};
                opacity: 0.85;
            }

            /* Grade de Produtos */
            .grid-container {
                width: 100%;
                margin-bottom: 10px;
            }
            .grid-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 12px;
            }
            .product-cell {
                width: 50%;
                vertical-align: top;
            }
            .empty-cell {
                visibility: hidden;
            }
            .product-card {
                border: 2px solid #cbd5e1;
                border-radius: 16px;
                padding: 12px;
                position: relative;
                background-color: #ffffff;
                text-align: center;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }

            /* Selo Starburst Promo */
            .starburst-badge {
                background-color: {$corSecundaria};
                color: #000000;
                font-weight: 900;
                font-size: 9px;
                padding: 4px 10px;
                border-radius: 20px;
                display: inline-block;
                margin-bottom: 8px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                border: 1px solid #d97706;
            }

            /* Imagem do Produto */
            .product-img-box {
                text-align: center;
                margin-bottom: 8px;
                background-color: #f8fafc;
                border-radius: 10px;
                padding: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .product-img {
                object-fit: contain;
                margin: 0 auto;
            }
            .no-img {
                background-color: #e2e8f0;
                color: #64748b;
                font-size: 10px;
                font-weight: bold;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* Metadados e Nome do Produto */
            .meta-row {
                font-size: 8px;
                font-weight: bold;
                color: #64748b;
                margin-bottom: 4px;
            }
            .product-brand {
                background-color: #e2e8f0;
                color: #334155;
                padding: 1px 5px;
                border-radius: 4px;
                margin-right: 4px;
            }
            .product-ref {
                color: #94a3b8;
            }
            .product-name {
                font-size: 13px;
                font-weight: 900;
                color: #0f172a;
                margin-bottom: 8px;
                text-transform: uppercase;
                line-height: 1.2;
            }

            /* Container de Preço */
            .price-splash {
                background-color: {$corPrimaria};
                color: #ffffff !important;
                border-radius: 12px;
                padding: 8px 12px;
                border: 2px solid {$corSecundaria};
                text-align: center;
            }
            .price-label {
                font-size: 8px;
                font-weight: 900;
                color: {$corSecundaria};
                letter-spacing: 0.5px;
                margin-bottom: 2px;
            }
            .price-row {
                line-height: 1;
            }
            .currency {
                font-size: 13px;
                font-weight: 900;
                color: #ffffff;
                vertical-align: super;
            }
            .price-main {
                font-size: 32px;
                font-weight: 900;
                color: #ffffff !important;
                letter-spacing: -1px;
            }
            .price-cents {
                font-size: 16px;
                font-weight: 900;
                color: #ffffff !important;
                vertical-align: super;
            }
            .unit-label {
                font-size: 10px;
                font-weight: bold;
                color: {$corSecundaria};
                margin-left: 2px;
            }

            /* Ajustes Dinâmicos de Altura conforme a quantidade de produtos (Density) */
            /* Se 1 a 2 produtos na página: Cards grandes ocupando a lâmina de forma imersiva */
            .density-large-2 .product-card {
                height: 660px;
                padding: 24px;
            }
            .density-large-2 .product-img-box {
                height: 360px;
            }
            .density-large-2 .product-img {
                max-height: 350px;
                max-width: 320px;
            }
            .density-large-2 .no-img {
                height: 360px;
                line-height: 360px;
            }
            .density-large-2 .product-name {
                font-size: 18px;
                height: 52px;
            }
            .density-large-2 .price-main {
                font-size: 54px;
            }
            .density-large-2 .price-cents {
                font-size: 26px;
            }

            /* Se 3 a 4 produtos na página */
            .density-large-4 .product-card {
                height: 300px;
                padding: 14px;
            }
            .density-large-4 .product-img-box {
                height: 140px;
            }
            .density-large-4 .product-img {
                max-height: 135px;
                max-width: 200px;
            }
            .density-large-4 .no-img {
                height: 140px;
                line-height: 140px;
            }
            .density-large-4 .product-name {
                font-size: 14px;
                height: 38px;
            }

            /* Se 5 a 6 produtos na página (Normal) */
            .density-normal .product-card {
                height: 230px;
            }
            .density-normal .product-img-box {
                height: 95px;
            }
            .density-normal .product-img {
                max-height: 90px;
                max-width: 150px;
            }
            .density-normal .no-img {
                height: 95px;
                line-height: 95px;
            }
            .density-normal .product-name {
                font-size: 11px;
                height: 30px;
            }
            .density-normal .price-main {
                font-size: 26px;
            }

            /* Se mais de 6 produtos (Compacto) */
            .density-compact .product-card {
                height: 180px;
                padding: 8px;
            }
            .density-compact .product-img-box {
                height: 70px;
            }
            .density-compact .product-img {
                max-height: 65px;
                max-width: 120px;
            }
            .density-compact .no-img {
                height: 70px;
                line-height: 70px;
            }
            .density-compact .product-name {
                font-size: 10px;
                height: 24px;
            }
            .density-compact .price-main {
                font-size: 22px;
            }

            /* Rodapé Banner */
            .footer-banner {
                margin-top: 15px;
                background-color: #f1f5f9;
                border-top: 2px dashed #cbd5e1;
                padding: 8px 12px;
                border-radius: 8px;
                color: #475569;
                font-size: 9px;
            }
            .footer-table {
                width: 100%;
                border-collapse: collapse;
            }
            .footer-left {
                text-align: left;
                width: 75%;
            }
            .footer-right {
                text-align: right;
                width: 25%;
                vertical-align: middle;
            }
        ";
    }
}
