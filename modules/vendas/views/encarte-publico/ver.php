<?php

use yii\helpers\Html;
use yii\helpers\Url;

$titulo = Html::encode($encarte->titulo);
$subtitulo = Html::encode($encarte->subtitulo ?: 'Ofertas Imbatíveis');
$nomeLoja = $loja ? Html::encode($loja->nome ?: 'Nossa Loja') : 'Pulse Vendas';
$telefoneLoja = $loja ? Html::encode($loja->telefone ?: '') : '';
$whatsappClean = preg_replace('/[^0-9]/', '', $telefoneLoja);
if (!empty($whatsappClean) && strlen($whatsappClean) >= 10 && strlen($whatsappClean) <= 11 && strpos($whatsappClean, '55') !== 0) {
    $whatsappClean = '55' . $whatsappClean;
}

$ppp = $encarte->produtos_por_pagina ?: 6;
$paginas = array_chunk($encarteProdutos, $ppp);
$totalPaginas = count($paginas);
$urlPublica = $encarte->getUrlPublica();
$urlPdf = $encarte->getUrlPdf();

$fraseCreditoOnlyCode = "UM PRODUTO DESENVOLVIDO PELA ONLY CODE - WHATSAPP 81 9 9288-8872 - JOSE BARBOSA DOS SANTOS, CARUARU/PE";

// Coletar categorias únicas presentes no encarte para o filtro
$categoriasPresentes = [];
foreach ($encarteProdutos as $ep) {
    if ($ep->produto && $ep->produto->categoria) {
        $catNome = $ep->produto->categoria->nome;
        if (!in_array($catNome, $categoriasPresentes)) {
            $categoriasPresentes[] = $catNome;
        }
    }
}
sort($categoriasPresentes);

// Calcular maior quantidade de produtos em uma única lâmina para ajustar a altura do canvas 3D
$maxItensPorPagina = 0;
foreach ($paginas as $p) {
    $c = count($p);
    if ($c > $maxItensPorPagina) {
        $maxItensPorPagina = $c;
    }
}

$canvasHeight3D = 750;
if ($maxItensPorPagina > 15) {
    $canvasHeight3D = 1180;
} elseif ($maxItensPorPagina > 12) {
    $canvasHeight3D = 1060;
} elseif ($maxItensPorPagina > 9) {
    $canvasHeight3D = 940;
} elseif ($maxItensPorPagina > 6) {
    $canvasHeight3D = 840;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= $titulo ?> | <?= $nomeLoja ?></title>
    
    <!-- Meta Tags SEO & Redes Sociais -->
    <meta name="description" content="<?= $subtitulo ?> - Confira os melhores preços no encarte digital da <?= $nomeLoja ?>">
    <meta property="og:title" content="<?= $titulo ?> - <?= $nomeLoja ?>">
    <meta property="og:description" content="<?= $subtitulo ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $urlPublica ?>">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts Inter & Montserrat -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    
    <!-- StPageFlip JS Library CDN -->
    <script src="https://cdn.jsdelivr.net/npm/page-flip@2.0.7/dist/js/page-flip.browser.js"></script>

    <style>
        html, body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            overflow-x: hidden;
            touch-action: pan-y;
            -webkit-overflow-scrolling: touch;
        }

        .font-montserrat {
            font-family: 'Montserrat', sans-serif;
        }

        /* Container do Folheto */
        .flipbook-stage {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 180px);
            padding: 15px 10px;
        }

        .flipbook-container {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            border-radius: 16px;
            background-color: #ffffff;
            overflow: hidden;
        }

        /* Estilo da Lâmina do Tabloide */
        .page-sheet {
            background-color: #ffffff !important;
            color: #0f172a;
            box-sizing: border-box;
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            user-select: none;
            border-radius: 16px;
            overflow: hidden;
            height: 100%;
            transform: translateZ(0);
        }

        /* Correção da Transição 3D StPageFlip */
        .stpageflip--page, .stpageflip--page-back {
            background-color: #ffffff !important;
            backface-visibility: hidden !important;
            -webkit-backface-visibility: hidden !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        }

        .stpageflip--canvas {
            background-color: transparent !important;
        }

        /* Tema Red Gold */
        .theme-red_gold .header-tabloide { background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #fff; }
        .theme-red_gold .badge-promo { background-color: #f59e0b; color: #000; }
        .theme-red_gold .price-tag { background-color: #dc2626; color: #fff; }
        
        /* Tema Emerald Fresh */
        .theme-emerald_fresh .header-tabloide { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #fff; }
        .theme-emerald_fresh .badge-promo { background-color: #10b981; color: #fff; }
        .theme-emerald_fresh .price-tag { background-color: #059669; color: #fff; }

        /* Tema Ocean Blue */
        .theme-ocean_blue .header-tabloide { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #fff; }
        .theme-ocean_blue .badge-promo { background-color: #3b82f6; color: #fff; }
        .theme-ocean_blue .price-tag { background-color: #2563eb; color: #fff; }

        /* Tema Dark VIP */
        .theme-dark_vip .header-tabloide { background: linear-gradient(135deg, #18181b 0%, #09090b 100%); color: #eab308; }
        .theme-dark_vip .badge-promo { background-color: #eab308; color: #000; }
        .theme-dark_vip .price-tag { background-color: #18181b; color: #eab308; border: 1px solid #eab308; }

        /* Card de Produto Interativo (Hotspot) */
        .hotspot-card {
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1.5px solid #e2e8f0;
            touch-action: manipulation;
            -webkit-tap-highlight-color: rgba(239, 68, 68, 0.2);
        }

        .hotspot-card:active, .hotspot-card:hover {
            transform: scale(0.98);
            border-color: #ef4444;
            box-shadow: 0 8px 20px -4px rgba(239, 68, 68, 0.25);
        }

        .glass-modal {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between theme-<?= Html::encode($encarte->cor_tema) ?>">

    <!-- Top Banner Créditos Only Code & Cronômetro Regressivo -->
    <div class="bg-gradient-to-r from-red-700 via-amber-600 to-red-700 text-white text-[10px] sm:text-xs font-bold py-1.5 px-4 text-center tracking-wide uppercase shadow-md flex flex-wrap items-center justify-center gap-x-4 gap-y-1">
        <span>⚡ <?= $fraseCreditoOnlyCode ?></span>
        <span class="bg-black/30 px-2.5 py-0.5 rounded-full border border-white/20 font-montserrat flex items-center gap-1 text-amber-300">
            ⏱️ OFERTAS ENCERRAM EM: <strong id="timerDisplay">02D 18H 45M 30S</strong>
        </span>
    </div>

    <!-- Top Bar Navegação e Ações -->
    <header class="sticky top-0 z-40 bg-slate-900/95 backdrop-blur-md border-b border-slate-800 px-4 py-2.5 shadow-xl">
        <div class="max-w-7xl mx-auto flex flex-col gap-2.5">
            
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <!-- Branding Loja -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-red-600 to-amber-500 flex items-center justify-center font-montserrat font-extrabold text-white text-lg shadow-lg">
                        <?= mb_substr($nomeLoja, 0, 1) ?>
                    </div>
                    <div>
                        <h1 class="font-extrabold text-sm sm:text-base text-white truncate max-w-xs sm:max-w-md"><?= $titulo ?></h1>
                        <p class="text-xs text-slate-400 font-medium"><?= $nomeLoja ?> • <?= $subtitulo ?></p>
                    </div>
                </div>

                <!-- Controles do Folheto -->
                <div class="flex items-center gap-2 flex-wrap justify-center">
                    <!-- Páginas -->
                    <div class="bg-slate-800 text-slate-200 px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-700 flex items-center gap-2">
                        <button id="btnPrevPage" class="hover:text-amber-400 transition p-1">◀</button>
                        <span id="pageIndicator">Página 1 / <?= $totalPaginas ?></span>
                        <button id="btnNextPage" class="hover:text-amber-400 transition p-1">▶</button>
                    </div>

                    <!-- Baixar PDF -->
                    <a href="<?= $urlPdf ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl transition shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        PDF
                    </a>

                    <!-- Compartilhar WhatsApp -->
                    <button onclick="compartilharEncarte()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white font-bold text-xs rounded-xl transition shadow-md">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.705 1.754zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-1.15 4.2 4.293-1.125z"/></svg>
                        Compartilhar
                    </button>
                </div>
            </div>

            <!-- Barra de Pesquisa Rápida e Filtros por Categoria -->
            <div class="flex flex-col sm:flex-row items-center gap-2 pt-1 border-t border-slate-800">
                <!-- Campo Busca -->
                <div class="relative w-full sm:w-72">
                    <input type="text" id="inputBuscaProduto" oninput="filtrarProdutos()" placeholder="🔍 Buscar produto no encarte..." class="w-full bg-slate-800 text-white placeholder-slate-400 text-xs rounded-xl px-3 py-1.5 border border-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>

                <!-- Chips Categorias -->
                <?php if (!empty($categoriasPresentes)): ?>
                    <div class="flex items-center gap-1.5 overflow-x-auto w-full no-scrollbar py-0.5">
                        <button onclick="filtrarCategoria('TODAS')" class="btn-cat-chip bg-amber-400 text-slate-900 font-extrabold text-[10px] px-2.5 py-1 rounded-lg uppercase whitespace-nowrap shadow transition">
                            Todas
                        </button>
                        <?php foreach ($categoriasPresentes as $catNome): ?>
                            <button onclick="filtrarCategoria('<?= Html::encode($catNome) ?>')" class="btn-cat-chip bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-[10px] px-2.5 py-1 rounded-lg uppercase whitespace-nowrap transition border border-slate-700">
                                <?= Html::encode($catNome) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </header>

    <!-- Área Principal do Folheto -->
    <main class="flipbook-stage">
        <div id="flipbookContainer" class="flipbook-container w-full max-w-5xl space-y-6 sm:space-y-0">
            
            <?php foreach ($paginas as $indexPagina => $itensPagina): 
                $countItensPag = count($itensPagina);

                if ($countItensPag <= 2) {
                    $gridColsRows = 'grid-cols-1 sm:grid-cols-2';
                    $imgHeightClass = 'h-48 sm:h-56';
                    $cardPaddingClass = 'p-4';
                    $titleFontClass = 'text-sm sm:text-base line-clamp-2';
                    $priceFontClass = 'text-2xl sm:text-3xl';
                    $priceDecFontClass = 'text-sm';
                    $gapClass = 'gap-4';
                    $headerPaddingClass = 'p-3 sm:p-4';
                } elseif ($countItensPag <= 6) {
                    $gridColsRows = 'grid-cols-2 sm:grid-cols-3';
                    $imgHeightClass = 'h-24 sm:h-28';
                    $cardPaddingClass = 'p-2.5 sm:p-3';
                    $titleFontClass = 'text-xs sm:text-sm line-clamp-2';
                    $priceFontClass = 'text-lg sm:text-xl';
                    $priceDecFontClass = 'text-xs';
                    $gapClass = 'gap-2.5 sm:gap-3';
                    $headerPaddingClass = 'p-2.5 sm:p-3';
                } elseif ($countItensPag <= 12) {
                    $gridColsRows = 'grid-cols-2 sm:grid-cols-3';
                    $imgHeightClass = 'h-14 sm:h-16';
                    $cardPaddingClass = 'p-2';
                    $titleFontClass = 'text-[10px] sm:text-xs line-clamp-2';
                    $priceFontClass = 'text-sm sm:text-lg';
                    $priceDecFontClass = 'text-[9px]';
                    $gapClass = 'gap-2';
                    $headerPaddingClass = 'p-2 sm:p-3';
                } else {
                    $gridColsRows = 'grid-cols-2 sm:grid-cols-3';
                    $imgHeightClass = 'h-10 sm:h-12';
                    $cardPaddingClass = 'p-1 sm:p-1.5';
                    $titleFontClass = 'text-[8px] sm:text-[9px] line-clamp-1';
                    $priceFontClass = 'text-xs sm:text-sm';
                    $priceDecFontClass = 'text-[7px] sm:text-[8px]';
                    $gapClass = 'gap-1 sm:gap-1.5';
                    $headerPaddingClass = 'p-1.5 sm:p-2';
                }
            ?>
                <div id="lamina-<?= ($indexPagina + 1) ?>" class="page-sheet w-full h-full overflow-hidden flex flex-col justify-between p-2 sm:p-3.5 mb-6 sm:mb-0 shadow-lg border border-slate-100">
                    
                    <!-- Topo Lâmina -->
                    <div class="header-tabloide <?= $headerPaddingClass ?> rounded-xl shadow-sm mb-1 flex items-center justify-between flex-shrink-0">
                        <div>
                            <span class="badge-promo px-2 py-0.5 rounded font-montserrat font-extrabold text-[7px] sm:text-[8px] uppercase tracking-wider">OFERTA ESPECIAL</span>
                            <h2 class="font-montserrat font-black text-xs sm:text-base uppercase tracking-tight leading-none mt-0.5"><?= $titulo ?></h2>
                            <p class="text-[8px] sm:text-[9px] opacity-90 font-medium truncate max-w-[260px] sm:max-w-xs"><?= $subtitulo ?></p>
                        </div>
                        <?php if ($telefoneLoja): ?>
                            <div class="hidden sm:block text-right">
                                <div class="text-[7px] font-bold uppercase opacity-80">Peça no Zap</div>
                                <div class="text-[10px] sm:text-xs font-black"><?= $telefoneLoja ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Grade de Produtos Dinamicamente Ajustada -->
                    <div class="grid <?= $gridColsRows ?> <?= $gapClass ?> flex-1 items-start my-1 pr-0.5">
                        <?php foreach ($itensPagina as $idxP => $encarteProd): 
                            $produto = $encarteProd->produto;
                            if (!$produto) continue;

                            $precoVal = $encarteProd->getPrecoFinal();
                            $precoFormatado = number_format($precoVal, 2, ',', '.');
                            $partesPreco = explode(',', $precoFormatado);

                            $foto = $produto->fotoPrincipal ?: ($produto->fotos[0] ?? null);
                            $urlFoto = $foto ? Url::to('@web/' . ltrim($foto->arquivo_path, '/'), true) : null;
                            $catNome = $produto->categoria ? $produto->categoria->nome : 'Geral';

                            // Badges dinâmicos ou personalizados
                            $tagCustom = $encarteProd->tag_promocional;
                            $badgeTexto = "OFERTA";
                            $badgeColor = "bg-amber-400 text-black";
                            $exibirBadge = true;

                            if (!empty($tagCustom) && $tagCustom !== 'AUTO') {
                                if ($tagCustom === 'NENHUMA') {
                                    $exibirBadge = false;
                                } elseif ($tagCustom === 'OFERTA_ESPECIAL') {
                                    $badgeTexto = "🌟 OFERTA ESPECIAL";
                                    $badgeColor = "bg-amber-500 text-white";
                                } elseif ($tagCustom === 'SUPER_OFERTA') {
                                    $badgeTexto = "🔥 SUPER OFERTA";
                                    $badgeColor = "bg-red-600 text-white";
                                } elseif ($tagCustom === 'MAIS_VENDIDO') {
                                    $badgeTexto = "⭐ MAIS VENDIDO";
                                    $badgeColor = "bg-amber-500 text-white";
                                } elseif ($tagCustom === 'OFERTA') {
                                    $badgeTexto = "OFERTA";
                                    $badgeColor = "bg-amber-400 text-black";
                                }
                            } else {
                                if ($encarteProd->destaque || $idxP % 4 === 1) {
                                    $badgeTexto = "🔥 SUPER OFERTA";
                                    $badgeColor = "bg-red-600 text-white";
                                } elseif ($idxP % 4 === 2) {
                                    $badgeTexto = "⭐ MAIS VENDIDO";
                                    $badgeColor = "bg-amber-500 text-white";
                                }
                            }

                            $jsonProdData = Html::encode(json_encode([
                                'id' => $produto->id,
                                'nome' => $produto->nome,
                                'marca' => $produto->marca ?: '',
                                'categoria' => $catNome,
                                'precoVal' => $precoVal,
                                'preco' => $precoFormatado,
                                'unidade' => $produto->unidade_medida ?: 'un',
                                'foto' => $urlFoto,
                                'codigo' => $produto->codigo_barras ?: $produto->codigo_referencia ?: ''
                            ]));
                        ?>
                            <div data-prod-nome="<?= Html::encode(mb_strtolower($produto->nome)) ?>" data-prod-cat="<?= Html::encode($catNome) ?>" onclick="abrirModalDetalheProduto(<?= $jsonProdData ?>)" ontouchend="abrirModalDetalheProduto(<?= $jsonProdData ?>)" class="hotspot-card bg-slate-50 rounded-xl <?= $cardPaddingClass ?> flex flex-col justify-between relative overflow-hidden group">
                                
                                <?php if ($exibirBadge): ?>
                                    <!-- Badge Starburst Oferta -->
                                    <div class="absolute top-1 right-1 <?= $badgeColor ?> font-montserrat font-black text-[7px] sm:text-[8px] px-1.5 py-0.5 rounded-full uppercase tracking-tighter shadow-sm z-10">
                                        <?= $badgeTexto ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Imagem -->
                                <div class="<?= $imgHeightClass ?> w-full flex items-center justify-center mb-1 p-1 bg-white rounded-lg flex-shrink-0">
                                    <?php if ($urlFoto): ?>
                                        <img src="<?= $urlFoto ?>" alt="<?= Html::encode($produto->nome) ?>" class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform duration-300">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-slate-200 rounded-lg flex items-center justify-center text-slate-400 text-[8px] font-bold">FOTO</div>
                                    <?php endif; ?>
                                </div>

                                <!-- Nome e Marca -->
                                <div class="mb-1 text-center flex-1">
                                    <?php if ($produto->marca): ?>
                                        <div class="text-[7px] sm:text-[8px] font-bold text-slate-500 uppercase tracking-wider mb-0.5 leading-none truncate"><?= Html::encode($produto->marca) ?></div>
                                    <?php endif; ?>
                                    <div class="<?= $titleFontClass ?> font-extrabold text-slate-900 leading-tight group-hover:text-red-600 transition-colors">
                                        <?= Html::encode($produto->nome) ?>
                                    </div>
                                </div>

                                <!-- Preço Destacado e Botão Sacola Rápida -->
                                <div class="space-y-1 flex-shrink-0">
                                    <div class="price-tag py-0.5 px-1.5 rounded-lg text-center shadow-sm flex items-baseline justify-center gap-0.5">
                                        <span class="text-[8px] sm:text-[9px] font-extrabold">R$</span>
                                        <span class="font-montserrat font-black <?= $priceFontClass ?> leading-none"><?= $partesPreco[0] ?></span>
                                        <span class="<?= $priceDecFontClass ?> font-bold">,<?= $partesPreco[1] ?></span>
                                        <span class="text-[7px] sm:text-[8px] font-semibold opacity-90 ml-0.5">/<?= Html::encode($produto->unidade_medida ?: 'un') ?></span>
                                    </div>

                                    <button onclick="event.stopPropagation(); adicionarDirectoSacola(<?= $jsonProdData ?>)" class="w-full py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-[8px] sm:text-[9px] rounded-lg shadow transition flex items-center justify-center gap-1">
                                        <span>+ Sacola</span>
                                    </button>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Rodapé da Lâmina com Créditos -->
                    <div class="mt-1 pt-1.5 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between text-[7px] sm:text-[8px] text-slate-500 gap-1 flex-shrink-0">
                        <div class="truncate max-w-full">Ofertas válidas enquanto durarem os estoques. • <?= $fraseCreditoOnlyCode ?></div>
                        <div class="font-bold whitespace-nowrap">Lâmina <?= ($indexPagina + 1) ?>/<?= $totalPaginas ?></div>
                    </div>

                </div>
            <?php endforeach; ?>

        </div>
    </main>

    <!-- Botões Flutuantes de Navegação de Lâminas / Páginas -->
    <div class="fixed bottom-24 right-4 z-40 flex flex-col items-center gap-1.5 bg-slate-900/90 p-2 rounded-2xl shadow-2xl border-2 border-white/20 backdrop-blur-md">
        <button onclick="paginaAnterior()" title="Lâmina Anterior" class="w-10 h-10 bg-slate-800 hover:bg-slate-700 active:scale-95 text-white rounded-xl flex items-center justify-center transition cursor-pointer">
            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"/></svg>
        </button>
        <div id="floatingPageBadge" class="text-[10px] font-black text-amber-300 font-montserrat px-1 text-center py-0.5">
            1/<?= $totalPaginas ?>
        </div>
        <button onclick="proximaPagina()" title="Próxima Lâmina" class="w-10 h-10 bg-slate-800 hover:bg-slate-700 active:scale-95 text-white rounded-xl flex items-center justify-center transition cursor-pointer">
            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
        </button>
    </div>

    <!-- Barra Flutuante da Sacola de Ofertas no Canto Inferior -->
    <div id="barrasacola" onclick="tratarCliqueSacola(event)" class="fixed bottom-3 right-4 z-50 bg-gradient-to-r from-emerald-600 via-green-600 to-emerald-600 text-white shadow-2xl rounded-2xl p-2.5 sm:p-4 flex items-center gap-3 border-2 border-white/20 hover:scale-105 transition-all cursor-pointer">
        <div class="relative bg-white/20 p-2 sm:p-2.5 rounded-xl">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 11h14l1 12H4l1-12z"/></svg>
            <span id="sacolaBadge" class="absolute -top-2 -right-2 bg-red-600 text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center border-2 border-white">0</span>
        </div>
        <div>
            <div class="text-[9px] sm:text-[10px] uppercase font-extrabold text-emerald-100">Minha Sacola</div>
            <div class="text-xs sm:text-base font-montserrat font-black">R$ <span id="sacolaTotalText">0,00</span></div>
        </div>
        <button class="bg-white text-emerald-900 font-extrabold text-xs px-2.5 py-1.5 sm:px-3 sm:py-2 rounded-xl shadow ml-1 flex items-center gap-1">
            <span>Ver Pedido</span>
            <span>➔</span>
        </button>
    </div>

    <!-- Rodapé Público -->
    <footer class="bg-slate-900 border-t border-slate-800 py-3 px-4 text-center text-xs text-slate-400 font-medium">
        <?= $nomeLoja ?> © <?= date('Y') ?> • <?= $fraseCreditoOnlyCode ?>
    </footer>

    <!-- Modal Interativo de Detalhes do Produto -->
    <div id="modalDetalheProduto" class="fixed inset-0 z-[100] hidden glass-modal flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden text-slate-900 border border-slate-100 transform transition-all flex flex-col max-h-[92vh] relative">
            
            <button onclick="fecharModalDetalheProduto()" class="absolute top-3 right-3 sm:top-4 sm:right-4 z-30 bg-slate-900 hover:bg-slate-800 text-white rounded-full p-2.5 shadow-2xl transition border-2 border-white flex items-center justify-center w-10 h-10 cursor-pointer">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <div class="relative bg-slate-100 p-6 flex items-center justify-center h-64 border-b border-slate-200">
                <img id="modalProdFoto" src="" class="max-h-full max-w-full object-contain drop-shadow-md">
            </div>

            <div class="p-6 space-y-4 overflow-y-auto">
                <div>
                    <span id="modalProdCategoria" class="px-2.5 py-1 bg-red-100 text-red-800 font-bold text-[10px] rounded-md uppercase tracking-wider"></span>
                    <h3 id="modalProdNome" class="text-xl font-extrabold text-slate-900 mt-1"></h3>
                    <p id="modalProdMarca" class="text-xs text-slate-500 font-semibold"></p>
                </div>

                <div class="flex items-center justify-between bg-slate-50 p-4 rounded-2xl border border-slate-200">
                    <div>
                        <div class="text-xs font-bold text-slate-500 uppercase">Preço Promocional</div>
                        <div class="text-3xl font-montserrat font-black text-red-600">R$ <span id="modalProdPreco"></span></div>
                    </div>
                    <div class="text-right text-xs">
                        <div class="text-slate-500 font-bold">Unidade</div>
                        <div id="modalProdUnidade" class="font-extrabold text-slate-800 uppercase"></div>
                    </div>
                </div>

                <!-- Botões de Ação na Modal -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                    <button onclick="adicionarModalNaSacola()" class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-lg transition flex items-center justify-center gap-2 text-sm">
                        🛒 Adicionar à Sacola
                    </button>

                    <button id="btnPedirWhatsapp" onclick="enviarPedidoZap()" class="w-full py-3.5 bg-green-600 hover:bg-green-700 text-white font-extrabold rounded-xl shadow-lg transition flex items-center justify-center gap-2 text-sm">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.705 1.754zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-1.15 4.2 4.293-1.125z"/></svg>
                        Pedir Só Este Item
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal da Sacola de Ofertas (Checkout Multi-Itens) -->
    <div id="modalSacola" class="fixed inset-0 z-[110] hidden glass-modal flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden text-slate-900 border border-slate-100 transform transition-all flex flex-col max-h-[92vh] relative">
            
            <!-- Header Modal Sacola -->
            <div class="bg-slate-900 text-white p-4 flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🛒</span>
                    <div>
                        <h3 class="font-extrabold text-base">Minha Sacola de Ofertas</h3>
                        <p class="text-xs text-slate-400">Finalize e envie seu pedido completo pelo WhatsApp</p>
                    </div>
                </div>
                <button onclick="fecharModalSacola()" class="text-slate-400 hover:text-white p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Corpo Modal Sacola -->
            <div class="p-4 space-y-4 overflow-y-auto flex-1">
                
                <!-- Lista de Itens -->
                <div id="listaItensSacola" class="space-y-2 max-h-60 overflow-y-auto pr-1">
                    <!-- Gerado Dinamicamente por JS -->
                </div>

                <!-- Totalizador -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 flex items-center justify-between">
                    <span class="font-extrabold text-slate-700 text-sm">TOTAL DO PEDIDO:</span>
                    <span class="font-montserrat font-black text-2xl text-emerald-600">R$ <span id="sacolaModalTotal">0,00</span></span>
                </div>

                <!-- Formulário Opcional do Cliente -->
                <div class="space-y-2 pt-2 border-t border-slate-200">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Seu Nome (Opcional):</label>
                        <input type="text" id="inputNomeCliente" placeholder="Ex: Maria Silva" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Endereço de Entrega / Obs (Opcional):</label>
                        <input type="text" id="inputEnderecoCliente" placeholder="Ex: Rua das Flores, 123 - Centro" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                </div>

            </div>

            <!-- Rodapé Modal Sacola -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col gap-2">
                <button onclick="finalizarPedidoSacolaWhatsapp()" class="w-full py-4 bg-green-600 hover:bg-green-700 text-white font-black text-base rounded-2xl shadow-xl transition flex items-center justify-center gap-2">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.705 1.754zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-1.15 4.2 4.293-1.125z"/></svg>
                    Enviar Pedido Completo no WhatsApp
                </button>
                <button onclick="limparSacola()" class="text-xs font-bold text-slate-500 hover:text-red-600 transition text-center py-1">
                    Esvaziar Sacola
                </button>
            </div>

        </div>
    </div>

    <script>
        let pageFlipInstance = null;
        let produtoAtualModal = null;
        let paginaAtualNum = 1;
        const totalPaginasCount = <?= $totalPaginas ?>;
        const whatsappLojaClean = '<?= $whatsappClean ?>';
        const nomeLojaStr = '<?= addslashes($nomeLoja) ?>';
        const canvasHeightDynamic = <?= $canvasHeight3D ?>;

        // Estado da Sacola de Compras
        let sacolaItens = {};

        // Funções de Rolagem Rápida Flutuante (Subir / Descer)
        function rolarParaTopo() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function rolarParaBaixo() {
            window.scrollBy({ top: 600, behavior: 'smooth' });
        }

        // Trava de Toque Anti-Arrasto na Sacola Flutuante
        let sacolaTouchStartY = 0;
        let sacolaIsDragging = false;

        function tratarCliqueSacola(e) {
            if (sacolaIsDragging) {
                sacolaIsDragging = false;
                return;
            }
            abrirModalSacola();
        }

        document.addEventListener("DOMContentLoaded", function() {
            const elSacola = document.getElementById('barrasacola');
            if (elSacola) {
                elSacola.addEventListener('touchstart', function(e) {
                    sacolaTouchStartY = e.touches[0].clientY;
                    sacolaIsDragging = false;
                }, { passive: true });

                elSacola.addEventListener('touchmove', function(e) {
                    const diffY = Math.abs(e.touches[0].clientY - sacolaTouchStartY);
                    if (diffY > 8) {
                        sacolaIsDragging = true;
                    }
                }, { passive: true });
            }

            carregarSacolaLocalStorage();
            iniciarCronometroRegressivo();

            const container = document.getElementById('flipbookContainer');
            const isDesktopScreen = window.innerWidth >= 768;

            if (isDesktopScreen && container && typeof St !== 'undefined' && St.PageFlip) {
                try {
                    pageFlipInstance = new St.PageFlip(container, {
                        width: 550,
                        height: canvasHeightDynamic,
                        size: "stretch",
                        minWidth: 320,
                        maxWidth: 1000,
                        minHeight: 450,
                        maxHeight: 1600,
                        drawShadow: true,
                        maxShadowOpacity: 0.6,
                        showCover: false,
                        mobileScrollSupport: true,
                        useMouseEvents: true,
                        flippingTime: 700
                    });

                    pageFlipInstance.loadFromHTML(document.querySelectorAll('.page-sheet'));

                    pageFlipInstance.on('flip', (e) => {
                        atualizarIndicadoresPagina(e.data + 1);
                    });

                } catch(e) {
                    console.warn("StPageFlip init warning, fallback to scroll layout:", e);
                }
            }

            document.getElementById('btnPrevPage').addEventListener('click', paginaAnterior);
            document.getElementById('btnNextPage').addEventListener('click', proximaPagina);

            observarLaminasScroll();
        });

        function paginaAnterior() {
            if (pageFlipInstance) {
                pageFlipInstance.flipPrev();
            } else {
                if (paginaAtualNum > 1) {
                    irParaLamina(paginaAtualNum - 1);
                } else {
                    rolarParaTopo();
                }
            }
        }

        function proximaPagina() {
            if (pageFlipInstance) {
                pageFlipInstance.flipNext();
            } else {
                if (paginaAtualNum < totalPaginasCount) {
                    irParaLamina(paginaAtualNum + 1);
                }
            }
        }

        function atualizarIndicadoresPagina(num) {
            paginaAtualNum = num;
            const elHeader = document.getElementById('pageIndicator');
            if (elHeader) elHeader.textContent = `Página ${num} / ${totalPaginasCount}`;

            const elFloat = document.getElementById('floatingPageBadge');
            if (elFloat) elFloat.textContent = `${num}/${totalPaginasCount}`;
        }

        function observarLaminasScroll() {
            const laminas = document.querySelectorAll('.page-sheet');
            if (!laminas.length || typeof IntersectionObserver === 'undefined') return;

            const observerOptions = {
                root: null,
                rootMargin: '-30% 0px -40% 0px',
                threshold: [0.1, 0.4]
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.id;
                        if (id && id.startsWith('lamina-')) {
                            const num = parseInt(id.replace('lamina-', ''), 10);
                            if (num) {
                                atualizarIndicadoresPagina(num);
                            }
                        }
                    }
                });
            }, observerOptions);

            laminas.forEach(lam => observer.observe(lam));
        }

        function irParaLamina(num) {
            if (num < 1) num = 1;
            if (num > totalPaginasCount) num = totalPaginasCount;

            atualizarIndicadoresPagina(num);

            const el = document.getElementById('lamina-' + num);
            if (el) {
                const headerOffset = 110;
                const elementPosition = el.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: Math.max(0, offsetPosition),
                    behavior: 'smooth'
                });
            }
        }

        // Cronômetro Regressivo Simulado (2 dias)
        function iniciarCronometroRegressivo() {
            let tempoSegundos = (2 * 24 * 3600) + (18 * 3600) + (45 * 60);
            const el = document.getElementById('timerDisplay');
            if (!el) return;

            setInterval(() => {
                if (tempoSegundos <= 0) return;
                tempoSegundos--;

                const d = Math.floor(tempoSegundos / (3600 * 24));
                const h = Math.floor((tempoSegundos % (3600 * 24)) / 3600);
                const m = Math.floor((tempoSegundos % 3600) / 60);
                const s = tempoSegundos % 60;

                el.textContent = `${String(d).padStart(2, '0')}D ${String(h).padStart(2, '0')}H ${String(m).padStart(2, '0')}M ${String(s).padStart(2, '0')}S`;
            }, 1000);
        }

        // Filtro por Nome e Categoria
        function filtrarProdutos() {
            const termo = document.getElementById('inputBuscaProduto').value.toLowerCase().trim();
            const cards = document.querySelectorAll('.hotspot-card');

            cards.forEach(card => {
                const nome = card.getAttribute('data-prod-nome') || '';
                if (nome.includes(termo)) {
                    card.style.opacity = '1';
                    card.style.filter = 'none';
                } else {
                    card.style.opacity = '0.25';
                    card.style.filter = 'grayscale(80%)';
                }
            });
        }

        function filtrarCategoria(catNome) {
            const cards = document.querySelectorAll('.hotspot-card');
            
            // Atualizar botões
            document.querySelectorAll('.btn-cat-chip').forEach(btn => {
                if (btn.textContent.trim().toUpperCase() === catNome.toUpperCase() || (catNome === 'TODAS' && btn.textContent.trim() === 'Todas')) {
                    btn.className = 'btn-cat-chip bg-amber-400 text-slate-900 font-extrabold text-[10px] px-2.5 py-1 rounded-lg uppercase whitespace-nowrap shadow transition';
                } else {
                    btn.className = 'btn-cat-chip bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-[10px] px-2.5 py-1 rounded-lg uppercase whitespace-nowrap transition border border-slate-700';
                }
            });

            cards.forEach(card => {
                const cat = card.getAttribute('data-prod-cat') || '';
                if (catNome === 'TODAS' || cat.toUpperCase() === catNome.toUpperCase()) {
                    card.style.opacity = '1';
                    card.style.filter = 'none';
                } else {
                    card.style.opacity = '0.2';
                    card.style.filter = 'grayscale(100%)';
                }
            });
        }

        window.abrirModalDetalheProduto = function(prod) {
            if (typeof prod === 'string') {
                try { prod = JSON.parse(prod); } catch(e) {}
            }
            produtoAtualModal = prod;
            document.getElementById('modalProdNome').textContent = prod.nome;
            document.getElementById('modalProdMarca').textContent = prod.marca ? 'Marca: ' + prod.marca : '';
            document.getElementById('modalProdCategoria').textContent = prod.categoria || 'Geral';
            document.getElementById('modalProdPreco').textContent = prod.preco;
            document.getElementById('modalProdUnidade').textContent = prod.unidade;
            
            const img = document.getElementById('modalProdFoto');
            if (prod.foto) {
                img.src = prod.foto;
                img.style.display = 'block';
            } else {
                img.style.display = 'none';
            }

            document.getElementById('modalDetalheProduto').classList.remove('hidden');
        };

        window.fecharModalDetalheProduto = function() {
            document.getElementById('modalDetalheProduto').classList.add('hidden');
        };

        // Gerenciamento da Sacola de Ofertas
        function adicionarDirectoSacola(prod) {
            if (typeof prod === 'string') {
                try { prod = JSON.parse(prod); } catch(e) {}
            }
            if (!sacolaItens[prod.id]) {
                sacolaItens[prod.id] = {
                    id: prod.id,
                    nome: prod.nome,
                    precoVal: parseFloat(prod.precoVal),
                    precoStr: prod.preco,
                    unidade: prod.unidade,
                    qtd: 1
                };
            } else {
                sacolaItens[prod.id].qtd++;
            }
            salvarAtualizarSacola();
        }

        function adicionarModalNaSacola() {
            if (!produtoAtualModal) return;
            adicionarDirectoSacola(produtoAtualModal);
            fecharModalDetalheProduto();
            abrirModalSacola();
        }

        function alterarQtdSacola(id, delta) {
            if (sacolaItens[id]) {
                sacolaItens[id].qtd += delta;
                if (sacolaItens[id].qtd <= 0) {
                    delete sacolaItens[id];
                }
                salvarAtualizarSacola();
                renderizarModalSacola();
            }
        }

        function limparSacola() {
            sacolaItens = {};
            salvarAtualizarSacola();
            renderizarModalSacola();
        }

        function salvarAtualizarSacola() {
            localStorage.setItem('sacola_encarte_pulse', JSON.stringify(sacolaItens));
            
            let totalVal = 0;
            let totalQtd = 0;

            Object.values(sacolaItens).forEach(item => {
                totalVal += item.precoVal * item.qtd;
                totalQtd += item.qtd;
            });

            document.getElementById('sacolaBadge').textContent = totalQtd;
            document.getElementById('sacolaTotalText').textContent = totalVal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('sacolaModalTotal').textContent = totalVal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function carregarSacolaLocalStorage() {
            try {
                const data = localStorage.getItem('sacola_encarte_pulse');
                if (data) {
                    sacolaItens = JSON.parse(data);
                }
            } catch(e) {}
            salvarAtualizarSacola();
        }

        function abrirModalSacola() {
            renderizarModalSacola();
            document.getElementById('modalSacola').classList.remove('hidden');
        }

        function fecharModalSacola() {
            document.getElementById('modalSacola').classList.add('hidden');
        }

        function renderizarModalSacola() {
            const container = document.getElementById('listaItensSacola');
            container.innerHTML = '';

            const lista = Object.values(sacolaItens);

            if (lista.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8 text-slate-400 space-y-2">
                        <span class="text-4xl block">🛍️</span>
                        <p class="text-xs font-bold">Sua sacola está vazia.</p>
                        <p class="text-[10px]">Clique nos produtos do encarte para adicionar!</p>
                    </div>
                `;
                return;
            }

            lista.forEach(item => {
                const subtotal = item.precoVal * item.qtd;
                const div = document.createElement('div');
                div.className = 'flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs';
                div.innerHTML = `
                    <div class="flex-1 pr-2">
                        <div class="font-extrabold text-slate-900 truncate">${item.nome}</div>
                        <div class="text-slate-500 font-semibold text-[10px]">R$ ${item.precoStr} / ${item.unidade}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center bg-slate-200 rounded-lg">
                            <button onclick="alterarQtdSacola('${item.id}', -1)" class="px-2 py-0.5 font-bold hover:bg-slate-300 rounded-l-lg">-</button>
                            <span class="px-2 font-black">${item.qtd}</span>
                            <button onclick="alterarQtdSacola('${item.id}', 1)" class="px-2 py-0.5 font-bold hover:bg-slate-300 rounded-r-lg">+</button>
                        </div>
                        <div class="font-montserrat font-black text-emerald-700 w-16 text-right">R$ ${subtotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                    </div>
                `;
                container.appendChild(div);
            });
        }

        function finalizarPedidoSacolaWhatsapp() {
            const lista = Object.values(sacolaItens);
            if (lista.length === 0) {
                alert('Adicione pelo menos um produto à sacola antes de enviar.');
                return;
            }

            const nomeCli = document.getElementById('inputNomeCliente').value.trim();
            const endCli = document.getElementById('inputEnderecoCliente').value.trim();

            let totalVal = 0;
            let textoItens = '';

            lista.forEach(item => {
                const sub = item.precoVal * item.qtd;
                totalVal += sub;
                textoItens += `• *${item.qtd}x* ${item.nome} (R$ ${sub.toLocaleString('pt-BR', { minimumFractionDigits: 2 })})\n`;
            });

            let textoFinal = `🛒 *NOVO PEDIDO DO ENCARTE DIGITAL*\n🏪 *Loja:* ${nomeLojaStr}\n\n📋 *ITENS SELECIONADOS:*\n${textoItens}\n💰 *TOTAL DO PEDIDO:* R$ ${totalVal.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}\n`;

            if (nomeCli) textoFinal += `👤 *Cliente:* ${nomeCli}\n`;
            if (endCli) textoFinal += `📍 *Endereço/Obs:* ${endCli}\n`;

            textoFinal += `\nComo faço para confirmar a compra e entrega?`;

            let urlZap = '';
            if (whatsappLojaClean) {
                urlZap = `https://wa.me/${whatsappLojaClean}?text=${encodeURIComponent(textoFinal)}`;
            } else {
                urlZap = `https://wa.me/?text=${encodeURIComponent(textoFinal)}`;
            }

            window.open(urlZap, '_blank');
        }

        function enviarPedidoZap() {
            if (!produtoAtualModal) return;
            const texto = `Olá *${nomeLojaStr}*! Vi o produto no Encarte Digital e gostaria de pedir:\n\n📌 *${produtoAtualModal.nome}*\n💰 Preço: R$ ${produtoAtualModal.preco} / ${produtoAtualModal.unidade}\n\nComo faço para concluir o pedido?`;
            
            let urlZap = '';
            if (whatsappLojaClean) {
                urlZap = `https://wa.me/${whatsappLojaClean}?text=${encodeURIComponent(texto)}`;
            } else {
                urlZap = `https://wa.me/?text=${encodeURIComponent(texto)}`;
            }

            window.open(urlZap, '_blank');
        }

        function compartilharEncarte() {
            const text = `🔥 *Confira o Encarte de Ofertas da ${nomeLojaStr}!* 🔥\n\n📖 Acesse o folheto digital: ${window.location.href}`;
            if (navigator.share) {
                navigator.share({
                    title: '<?= $titulo ?>',
                    text: text,
                    url: window.location.href
                }).catch(() => {});
            } else {
                window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
            }
        }
    </script>
</body>
</html>
