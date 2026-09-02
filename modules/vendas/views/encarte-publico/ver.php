<?php

use yii\helpers\Html;
use yii\helpers\Url;

$titulo = Html::encode($encarte->titulo);
$subtitulo = Html::encode($encarte->subtitulo ?: 'Ofertas Imbatíveis');

// Dados cadastrais completos da empresa/loja
$nomeFantasia = $lojaConfig && $lojaConfig->nome_fantasia ? Html::encode($lojaConfig->nome_fantasia) : ($loja ? Html::encode($loja->nome ?: 'Nossa Loja') : 'Pulse Vendas');
$nomeLoja = $nomeFantasia;
$razaoSocial  = $lojaConfig && $lojaConfig->razao_social  ? Html::encode($lojaConfig->razao_social)  : '';
$cnpj         = $lojaConfig && $lojaConfig->cpf_cnpj      ? Html::encode($lojaConfig->cpf_cnpj)      : '';
$telefoneLoja = $lojaConfig && $lojaConfig->telefone       ? Html::encode($lojaConfig->telefone)      : ($loja ? Html::encode($loja->telefone ?: '') : '');
$emailLoja    = $lojaConfig && $lojaConfig->email          ? Html::encode($lojaConfig->email)         : ($loja ? Html::encode($loja->email    ?: '') : '');
$siteLoja     = $lojaConfig && $lojaConfig->site           ? Html::encode($lojaConfig->site)          : '';

$whatsappClean = preg_replace('/[^0-9]/', '', $telefoneLoja);
if (!empty($whatsappClean) && strlen($whatsappClean) >= 10 && strlen($whatsappClean) <= 11 && strpos($whatsappClean, '55') !== 0) {
    $whatsappClean = '55' . $whatsappClean;
}

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
$logoUrl  = null;
if (!empty($logoPath)) {
    $caminhoLogoAbs = Yii::getAlias('@app/web/') . ltrim($logoPath, '/');
    if (file_exists($caminhoLogoAbs)) {
        $logoUrl = Url::to('@web/' . ltrim($logoPath, '/'), true);
    }
}

$ppp = $encarte->produtos_por_pagina ?: 6;
$paginas = array_chunk($encarteProdutos, $ppp);
$totalLaminas = count($paginas);
$totalPaginas = $totalLaminas + 1; // 1 Capa + N Lâminas de produtos
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

// Proporção exata da folha A4 (595px largura x 842px altura - Aspect Ratio 1 : 1.414)
$canvasHeight3D = 842;
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

        /* Container e Palco do Folheto (Estilo Flipsnack) */
        .flipbook-stage {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 180px);
            padding: 20px 10px;
            background: radial-gradient(circle at center, #1e293b 0%, #0f172a 100%);
            perspective: 2500px;
        }

        .flipbook-container {
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.8), 0 0 30px rgba(0, 0, 0, 0.4);
            border-radius: 16px;
            background-color: #ffffff;
            overflow: visible;
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
            border-radius: 12px;
            overflow: hidden;
            height: 100%;
            transform: translateZ(0);
        }

        /* Transição 3D Realista de Papel (Estilo Flipsnack) */
        .stpageflip--page, .stpageflip--page-back {
            background-color: #ffffff !important;
            backface-visibility: hidden !important;
            -webkit-backface-visibility: hidden !important;
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.08), 0 20px 45px -10px rgba(0, 0, 0, 0.5), 0 0 15px rgba(0, 0, 0, 0.15);
            transition: transform 0.1s ease-out;
        }

        .stpageflip--page-left {
            border-radius: 12px 0 0 12px !important;
        }
        .stpageflip--page-right {
            border-radius: 0 12px 12px 0 !important;
        }

        /* Sombreamento Gradiente Realista da Lombada Central (Spine Fold - Flipsnack) */
        .stpageflip--page-left::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 36px;
            background: linear-gradient(to left, rgba(0, 0, 0, 0.24) 0%, rgba(0, 0, 0, 0.08) 45%, rgba(0, 0, 0, 0) 100%);
            pointer-events: none;
            z-index: 40;
        }

        .stpageflip--page-right::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 36px;
            background: linear-gradient(to right, rgba(0, 0, 0, 0.24) 0%, rgba(0, 0, 0, 0.08) 45%, rgba(0, 0, 0, 0) 100%);
            pointer-events: none;
            z-index: 40;
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
            
            <!-- ========================================== -->
            <!-- PÁGINA 1: CAPA INSTITUCIONAL PREMIUM       -->
            <!-- ========================================== -->
            <div id="lamina-1" class="page-sheet w-full h-full overflow-hidden flex flex-col justify-between p-3.5 sm:p-6 mb-6 sm:mb-0 shadow-2xl border-l-[6px] border-r-[6px] border-red-600 border-t border-b border-slate-200 relative bg-white select-none">
                
                <!-- 1. Topo: Badge Super Oficial -->
                <div class="flex justify-center mb-1.5 flex-shrink-0">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-gradient-to-r from-red-600 via-red-500 to-red-600 text-white font-montserrat font-black text-[8px] sm:text-[10px] uppercase tracking-wider rounded-full shadow-sm border-b-2 border-amber-400">
                        ⭐ ENCARTE OFICIAL DE OFERTAS & PROMOÇÕES ⭐
                    </span>
                </div>

                <!-- 2. Logo / Emblema & Dados Jurídicos -->
                <div class="flex flex-col items-center justify-center text-center my-auto py-1 sm:py-2 flex-shrink-0">
                    <?php if ($logoUrl): ?>
                        <div class="p-2 bg-slate-50 rounded-2xl border border-slate-100 shadow-sm mb-1.5 max-w-[220px] sm:max-w-[280px]">
                            <img src="<?= $logoUrl ?>" alt="<?= $nomeFantasia ?>" class="h-14 sm:h-20 w-auto object-contain mx-auto">
                        </div>
                        <h1 class="font-montserrat font-black text-lg sm:text-2xl text-slate-900 uppercase tracking-tight"><?= $nomeFantasia ?></h1>
                    <?php else: ?>
                        <div class="inline-flex items-center justify-center px-5 py-2.5 sm:px-6 sm:py-3.5 bg-gradient-to-br from-red-600 to-red-700 rounded-2xl shadow-md border-2 border-amber-400 mb-1.5">
                            <span class="font-montserrat font-black text-lg sm:text-2xl text-white uppercase tracking-wider"><?= $nomeFantasia ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($razaoSocial || $cnpj): ?>
                        <div class="flex flex-wrap items-center justify-center gap-1.5 sm:gap-2 text-[8px] sm:text-[9.5px] text-slate-500 font-semibold uppercase tracking-wide mt-0.5">
                            <?php if ($razaoSocial): ?><span><?= $razaoSocial ?></span><?php endif; ?>
                            <?php if ($razaoSocial && $cnpj): ?><span>•</span><?php endif; ?>
                            <?php if ($cnpj): ?><span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-700">CNPJ: <?= $cnpj ?></span><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 3. Hero Banner Promocional de Alto Impacto -->
                <div class="header-tabloide rounded-2xl p-3 sm:p-5 shadow-lg my-auto text-center border-t-4 border-b-4 border-amber-400 flex-shrink-0">
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-amber-400 text-slate-900 font-montserrat font-extrabold text-[7.5px] sm:text-[9px] uppercase tracking-wider rounded-md shadow-sm mb-1">
                        🔥 PREÇOS ESPECIAIS DE TABLOIDE 🔥
                    </div>
                    <h2 class="font-montserrat font-black text-lg sm:text-2xl uppercase tracking-tight leading-tight text-white drop-shadow-sm">
                        <?= $titulo ?>
                    </h2>
                    <p class="text-amber-200 text-[10px] sm:text-xs font-semibold mt-0.5 sm:mt-1 max-w-md mx-auto line-clamp-2">
                        <?= $subtitulo ?>
                    </p>
                    <div class="inline-block mt-1.5 sm:mt-2 px-2.5 py-0.5 sm:px-3 sm:py-1 bg-black/30 rounded-full text-[7.5px] sm:text-[8.5px] font-bold text-amber-300 uppercase tracking-wide">
                        QUALIDADE • ECONOMIA • ATENDIMENTO DIFERENCIADO
                    </div>
                </div>

                <!-- 4. Estatísticas & Destaques (Grid de 3 Colunas) -->
                <div class="grid grid-cols-3 gap-1.5 sm:gap-3 my-auto flex-shrink-0">
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-2 sm:p-3 text-center shadow-xs">
                        <div class="font-montserrat font-black text-base sm:text-2xl text-red-600 leading-none"><?= count($encarteProdutos) ?></div>
                        <div class="text-[6.5px] sm:text-[8px] font-extrabold text-slate-600 uppercase tracking-wider mt-1">Ofertas Selecionadas</div>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-2 sm:p-3 text-center shadow-xs">
                        <div class="font-montserrat font-black text-base sm:text-2xl text-red-600 leading-none"><?= $totalLaminas ?></div>
                        <div class="text-[6.5px] sm:text-[8px] font-extrabold text-slate-600 uppercase tracking-wider mt-1">Lâminas de Produtos</div>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-2 sm:p-3 text-center shadow-xs">
                        <div class="font-montserrat font-black text-base sm:text-2xl text-emerald-600 leading-none">100%</div>
                        <div class="text-[6.5px] sm:text-[8px] font-extrabold text-slate-600 uppercase tracking-wider mt-1">Economia Real</div>
                    </div>
                </div>

                <!-- 5. Call To Action WhatsApp Direto -->
                <?php if ($whatsappClean): ?>
                    <div class="my-auto flex-shrink-0">
                        <a href="https://wa.me/<?= $whatsappClean ?>?text=<?= urlencode('Olá, vi o encarte digital ' . $encarte->titulo . ' e gostaria de mais informações!') ?>" target="_blank" class="w-full flex items-center justify-between p-2.5 sm:p-3 bg-gradient-to-r from-emerald-600 via-emerald-500 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white rounded-2xl shadow-md hover:shadow-lg transition transform hover:-translate-y-0.5 border border-emerald-400 group">
                            <div class="flex items-center gap-2 sm:gap-2.5 min-w-0">
                                <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.705 1.754zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-1.15 4.2 4.293-1.125z"/></svg>
                                </div>
                                <div class="text-left truncate">
                                    <div class="text-[7.5px] sm:text-[8.5px] font-bold uppercase tracking-wider text-emerald-100">Atendimento & Pedidos pelo Zap</div>
                                    <div class="font-montserrat font-black text-xs sm:text-sm tracking-tight truncate"><?= $telefoneLoja ?></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 text-[8px] sm:text-[9.5px] font-extrabold uppercase bg-white text-emerald-800 px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-xl shadow-xs group-hover:scale-105 transition flex-shrink-0">
                                <span>Chamar</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </div>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- 6. Chamada Interativa para Virar a Página -->
                <div onclick="proximaPagina()" class="my-auto cursor-pointer flex items-center justify-center gap-1.5 sm:gap-2 p-2 sm:p-2.5 bg-amber-50 hover:bg-amber-100 text-amber-900 border-2 border-dashed border-amber-400 rounded-xl transition shadow-xs flex-shrink-0 group">
                    <span class="animate-pulse text-xs sm:text-sm">👉</span>
                    <span class="font-montserrat font-extrabold text-[8px] sm:text-[10px] uppercase tracking-wide group-hover:underline">
                        VIRE A PÁGINA E CONFIRA TODAS AS NOSSAS OFERTAS
                    </span>
                    <span class="animate-pulse text-xs sm:text-sm">👈</span>
                </div>

                <!-- 7. Rodapé Institucional com Endereço e Contatos -->
                <div class="pt-2 sm:pt-3 border-t border-slate-200 text-center text-slate-500 text-[7.5px] sm:text-[8.5px] space-y-0.5 flex-shrink-0">
                    <?php if ($enderecoCompleto): ?>
                        <p class="font-medium text-slate-700 truncate max-w-lg mx-auto">
                            📍 <strong>Endereço:</strong> <?= $enderecoCompleto ?>
                        </p>
                    <?php endif; ?>
                    <div class="flex flex-wrap items-center justify-center gap-x-2.5 gap-y-0.5 text-slate-600 font-semibold text-[7.5px] sm:text-[8.5px]">
                        <?php if ($telefoneLoja): ?><span>📞 <?= $telefoneLoja ?></span><?php endif; ?>
                        <?php if ($emailLoja): ?><span>✉️ <?= $emailLoja ?></span><?php endif; ?>
                        <?php if ($siteLoja): ?><span>🌐 <a href="<?= $siteLoja ?>" target="_blank" class="text-blue-600 hover:underline"><?= preg_replace('(^https?://)', '', $siteLoja) ?></a></span><?php endif; ?>
                    </div>
                    <p class="text-[7px] text-slate-400 pt-0.5">
                        Tabloide gerado em <?= date('d/m/Y') ?> • Sistema Pulse Vendas • Only Code
                    </p>
                </div>

            </div>

            <!-- ========================================== -->
            <!-- LÂMINAS DE PRODUTOS                        -->
            <!-- ========================================== -->
            <?php foreach ($paginas as $indexPagina => $itensPagina): 
                $countItensPag = count($itensPagina);

                if ($countItensPag <= 2) {
                    $gridColsRows = 'grid-cols-1 sm:grid-cols-2';
                    $imgHeightClass = 'h-64 sm:h-80 md:h-96';
                    $cardPaddingClass = 'p-3 sm:p-4';
                    $titleFontClass = 'text-sm sm:text-base line-clamp-2';
                    $priceFontClass = 'text-2xl sm:text-3xl';
                    $priceDecFontClass = 'text-sm';
                    $gapClass = 'gap-3 sm:gap-4';
                    $headerPaddingClass = 'p-3 sm:p-4';
                } elseif ($countItensPag <= 4) {
                    $gridColsRows = 'grid-cols-2';
                    $imgHeightClass = 'h-44 sm:h-56 md:h-64';
                    $cardPaddingClass = 'p-2.5 sm:p-3';
                    $titleFontClass = 'text-xs sm:text-sm line-clamp-2';
                    $priceFontClass = 'text-xl sm:text-2xl';
                    $priceDecFontClass = 'text-xs sm:text-sm';
                    $gapClass = 'gap-2.5 sm:gap-3';
                    $headerPaddingClass = 'p-2.5 sm:p-3';
                } elseif ($countItensPag <= 6) {
                    $gridColsRows = 'grid-cols-2 sm:grid-cols-3';
                    $imgHeightClass = 'h-36 sm:h-44 md:h-48';
                    $cardPaddingClass = 'p-2 sm:p-2.5';
                    $titleFontClass = 'text-xs sm:text-sm line-clamp-2';
                    $priceFontClass = 'text-lg sm:text-xl';
                    $priceDecFontClass = 'text-xs';
                    $gapClass = 'gap-2 sm:gap-2.5';
                    $headerPaddingClass = 'p-2 sm:p-2.5';
                } elseif ($countItensPag <= 12) {
                    $gridColsRows = 'grid-cols-2 sm:grid-cols-3';
                    $imgHeightClass = 'h-28 sm:h-36 md:h-40';
                    $cardPaddingClass = 'p-1.5 sm:p-2';
                    $titleFontClass = 'text-[10px] sm:text-xs line-clamp-2';
                    $priceFontClass = 'text-sm sm:text-base md:text-lg';
                    $priceDecFontClass = 'text-[9px] sm:text-[10px]';
                    $gapClass = 'gap-1.5 sm:gap-2';
                    $headerPaddingClass = 'p-2 sm:p-2.5';
                } else {
                    $gridColsRows = 'grid-cols-2 sm:grid-cols-3';
                    $imgHeightClass = 'h-20 sm:h-24';
                    $cardPaddingClass = 'p-1 sm:p-1.5';
                    $titleFontClass = 'text-[8px] sm:text-[9px] line-clamp-1';
                    $priceFontClass = 'text-xs sm:text-sm';
                    $priceDecFontClass = 'text-[7px] sm:text-[8px]';
                    $gapClass = 'gap-1 sm:gap-1.5';
                    $headerPaddingClass = 'p-1.5 sm:p-2';
                }
            ?>
                <div id="lamina-<?= ($indexPagina + 2) ?>" class="page-sheet w-full h-full overflow-hidden flex flex-col justify-between p-2 sm:p-3 mb-6 sm:mb-0 shadow-lg border border-slate-100">
                    
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
                            $fotoUrl = $foto ? $foto->getUrl() : null;
                            if (!$fotoUrl && $produto->categoria && $produto->categoria->foto_path) {
                                $caminhoCatAbs = Yii::getAlias('@app/web/') . ltrim($produto->categoria->foto_path, '/');
                                if (file_exists($caminhoCatAbs)) {
                                    $fotoUrl = Url::to('@web/' . ltrim($produto->categoria->foto_path, '/'), true);
                                }
                            }

                            $jsonProdData = Html::encode(json_encode([
                                'id' => $produto->id,
                                'nome' => $produto->nome,
                                'marca' => $produto->marca ?: '',
                                'categoria' => $produto->categoria ? $produto->categoria->nome : 'Geral',
                                'preco' => (float)$precoVal,
                                'precoVal' => (float)$precoVal,
                                'preco_formatado' => $precoFormatado,
                                'unidade' => $produto->unidade_medida ?: 'un',
                                'foto' => $fotoUrl ?: '',
                                'descricao' => $produto->descricao ?: '',
                                'codigo_barras' => $produto->codigo_barras ?: '',
                            ]));

                        ?>
                            <!-- Card Individual de Produto -->
                            <div data-prod-nome="<?= Html::encode(mb_strtolower($produto->nome)) ?>" data-prod-cat="<?= Html::encode($produto->categoria ? $produto->categoria->nome : 'Geral') ?>" onclick="abrirModalDetalheProduto(<?= $jsonProdData ?>)" class="hotspot-card bg-white rounded-xl <?= $cardPaddingClass ?> flex flex-col justify-between shadow-xs group h-full select-none">
                                
                                <!-- Categoria & Badge -->
                                <div class="flex items-center justify-between mb-0.5 flex-shrink-0">
                                    <span class="text-[7px] font-bold text-slate-400 uppercase tracking-wider truncate max-w-[70px]">
                                        <?= $produto->categoria ? Html::encode($produto->categoria->nome) : 'Oferta' ?>
                                    </span>
                                    <?php if ($encarteProd->preco_oferta): ?>
                                        <span class="badge-promo text-[6.5px] font-extrabold px-1 rounded uppercase">PROMO</span>
                                    <?php endif; ?>
                                </div>


                                <!-- Imagem Centralizada com Aspect Ratio Perfeito -->
                                <div class="w-full <?= $imgHeightClass ?> flex items-center justify-center p-1 relative overflow-hidden flex-shrink-0">
                                    <?php if ($fotoUrl): ?>
                                        <img src="<?= $fotoUrl ?>" alt="<?= Html::encode($produto->nome) ?>" loading="lazy" class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform duration-200">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-slate-100 rounded-lg flex items-center justify-center text-slate-400 text-[8px] font-bold">FOTO</div>
                                    <?php endif; ?>
                                </div>

                                <!-- Nome e Marca -->
                                <div class="mb-1 text-center flex-1 min-w-0">
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

                                    <button onclick="event.stopPropagation(); adicionarDirectoSacola(<?= $jsonProdData ?>)" class="w-full py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-[8px] sm:text-[9px] rounded-lg shadow transition flex items-center justify-center gap-1 cursor-pointer">
                                        <span>+ Sacola</span>
                                    </button>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Rodapé da Lâmina com Créditos -->
                    <div class="mt-1 pt-1.5 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between text-[7px] sm:text-[8px] text-slate-500 gap-1 flex-shrink-0">
                        <div class="truncate max-w-full">Ofertas válidas enquanto durarem os estoques. • <?= $fraseCreditoOnlyCode ?></div>
                        <div class="font-bold whitespace-nowrap">Lâmina <?= ($indexPagina + 1) ?> de <?= $totalLaminas ?> • Pág. <?= ($indexPagina + 2) ?>/<?= $totalPaginas ?></div>
                    </div>

                </div>
            <?php endforeach; ?>

        </div>
    </main>

    <!-- Botões Flutuantes de Navegação de Lâminas / Páginas -->

    <div class="fixed bottom-24 right-4 z-40 flex flex-col items-center gap-1.5 bg-slate-900/90 p-2 rounded-2xl shadow-2xl border-2 border-white/20 backdrop-blur-md">
        <button id="btnFloatPrevPage" onclick="paginaAnterior()" title="Lâmina Anterior" class="w-10 h-10 bg-slate-800 hover:bg-slate-700 active:scale-95 text-white rounded-xl flex items-center justify-center transition cursor-pointer">
            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"/></svg>
        </button>
        <div id="floatingPageBadge" class="text-[10px] font-black text-amber-300 font-montserrat px-1 text-center py-0.5">
            1/<?= $totalPaginas ?>
        </div>
        <button id="btnFloatNextPage" onclick="proximaPagina()" title="Próxima Lâmina" class="w-10 h-10 bg-slate-800 hover:bg-slate-700 active:scale-95 text-white rounded-xl flex items-center justify-center transition cursor-pointer">
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

    <!-- Modal Interativo de Detalhes e Pedido do Produto Clicado -->
    <div id="modalDetalheProduto" class="fixed inset-0 z-[100] hidden glass-modal flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden text-slate-900 border border-slate-100 transform transition-all flex flex-col max-h-[92vh] relative">
            
            <!-- Header do Modal de Detalhe / Sacola com Título do Card -->
            <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white p-3.5 sm:p-4 flex items-center justify-between border-b border-slate-700/60 relative z-20 flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center font-black text-base">
                        🛍️
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm sm:text-base text-white tracking-tight">Adicionar à Sacola &amp; Pedido</h3>
                        <p class="text-[10px] text-slate-300">Confira a oferta e adicione ao seu pedido</p>
                    </div>
                </div>
                <button type="button" onclick="fecharModalDetalheProduto()" class="text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 p-2 rounded-full transition cursor-pointer">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Foto do Produto -->
            <div class="relative bg-slate-100 p-4 sm:p-6 flex items-center justify-center h-48 sm:h-56 border-b border-slate-200 flex-shrink-0">
                <img id="modalProdFoto" src="" class="max-h-full max-w-full object-contain drop-shadow-md">
            </div>

            <!-- Detalhes, Quantidade e Formulário do Pedido Individual -->
            <div class="p-4 sm:p-5 space-y-3 overflow-y-auto flex-1">
                <div>
                    <span id="modalProdCategoria" class="px-2.5 py-0.5 bg-red-100 text-red-800 font-bold text-[10px] rounded-md uppercase tracking-wider"></span>
                    <h3 id="modalProdNome" class="text-lg sm:text-xl font-extrabold text-slate-900 mt-1 leading-tight"></h3>
                    <p id="modalProdMarca" class="text-xs text-slate-500 font-semibold"></p>
                </div>

                <!-- Card de Preço, Quantidade e Subtotal -->
                <div class="bg-slate-50 p-3 sm:p-4 rounded-2xl border border-slate-200 flex items-center justify-between gap-2">
                    <div>
                        <div class="text-[10px] font-bold text-slate-500 uppercase">Preço Unitário</div>
                        <div class="text-lg sm:text-xl font-montserrat font-black text-red-600">
                            R$ <span id="modalProdPreco"></span> <span class="text-xs text-slate-500 font-normal">/ <span id="modalProdUnidade"></span></span>
                        </div>
                    </div>

                    <!-- Seletor de Quantidade -->
                    <div class="flex flex-col items-center">
                        <div class="text-[9px] font-bold text-slate-500 uppercase mb-1">Quantidade</div>
                        <div class="flex items-center bg-slate-200 rounded-xl p-0.5">
                            <button onclick="alterarQtdModalProduto(-1)" class="w-8 h-8 flex items-center justify-center font-black text-base hover:bg-slate-300 rounded-lg transition cursor-pointer text-slate-800">-</button>
                            <span id="modalProdQtd" class="w-8 text-center font-montserrat font-black text-sm text-slate-900">1</span>
                            <button onclick="alterarQtdModalProduto(1)" class="w-8 h-8 flex items-center justify-center font-black text-base hover:bg-slate-300 rounded-lg transition cursor-pointer text-slate-800">+</button>
                        </div>
                    </div>

                    <!-- Subtotal do Item -->
                    <div class="text-right">
                        <div class="text-[10px] font-bold text-slate-500 uppercase">Total do Item</div>
                        <div class="text-lg sm:text-xl font-montserrat font-black text-emerald-600">
                            R$ <span id="modalProdSubtotal">0,00</span>
                        </div>
                    </div>
                </div>

                <!-- Opção de Canal de Comunicação Interno / Próprio (Fixado e Obrigatório) -->
                <div class="p-2.5 sm:p-3 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div>
                            <div class="text-[11px] sm:text-xs font-black text-slate-900 flex items-center gap-1.5">
                                <span>Canal Interno Pulse</span>
                                <span class="bg-emerald-600 text-white text-[7.5px] font-black px-1.5 py-0.2 rounded-full uppercase tracking-wider">Ativo • Obrigatório</span>
                            </div>
                            <div class="text-[9px] sm:text-[10px] text-slate-500 font-medium">Registrado automaticamente no painel de atendimento da loja</div>
                        </div>
                    </div>
                    <label class="relative flex items-center cursor-not-allowed">
                        <input type="checkbox" checked disabled readonly class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-0 cursor-not-allowed opacity-90 accent-emerald-600 pointer-events-none">
                    </label>
                </div>

                <!-- Formulário do Cliente com WhatsApp Obrigatório -->
                <div class="space-y-2.5 pt-2 border-t border-slate-200">
                    <div>
                        <label class="block text-[11px] font-black text-slate-800 mb-1 flex items-center justify-between">
                            <span>Seu WhatsApp (Celular): <span class="text-red-600 font-black">*</span></span>
                            <span class="text-[9px] text-emerald-700 font-bold bg-emerald-50 border border-emerald-200 px-1.5 py-0.2 rounded-full">Lembrar para próximos pedidos</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-emerald-600 font-black text-xs">
                                📱
                            </div>
                            <input type="tel" id="inputTelefoneClienteProd" placeholder="(00) 00000-0000" maxlength="15" oninput="aplicarMascaraTelefone(this); salvarDadosClienteLocalStorage();" class="w-full pl-8 pr-3 py-2 bg-white border-2 border-slate-300 focus:border-emerald-500 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-emerald-200 focus:outline-none transition">
                        </div>
                        <p id="msgErroZapProd" class="hidden text-[10px] text-red-600 font-bold mt-0.5">⚠️ Informe seu WhatsApp com DDD para incluir na sacola ou pedir.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-0.5">Seu Nome (Opcional):</label>
                            <input type="text" id="inputNomeClienteProd" placeholder="Ex: Maria Silva" oninput="salvarDadosClienteLocalStorage()" onchange="salvarDadosClienteLocalStorage()" onblur="salvarDadosClienteLocalStorage()" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-0.5">Endereço / Obs (Opcional):</label>
                            <input type="text" id="inputEnderecoClienteProd" placeholder="Ex: Rua das Flores, 123" oninput="salvarDadosClienteLocalStorage()" onchange="salvarDadosClienteLocalStorage()" onblur="salvarDadosClienteLocalStorage()" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rodapé com Ações -->
            <div class="p-3 sm:p-4 bg-slate-50 border-t border-slate-200 flex flex-col gap-2 flex-shrink-0">
                <button id="btnPedirWhatsappItem" onclick="enviarPedidoProdutoIndividual()" class="w-full py-3.5 bg-green-600 hover:bg-green-700 active:scale-98 text-white font-black text-sm sm:text-base rounded-2xl shadow-xl transition flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.705 1.754zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-1.15 4.2 4.293-1.125z"/></svg>
                    <span>Pedir Apenas Este Produto</span>
                </button>

                <button onclick="adicionarModalNaSacola()" class="w-full py-2.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-900 font-extrabold text-xs rounded-xl transition flex items-center justify-center gap-1.5 cursor-pointer">
                    🛒 + Adicionar à Sacola Multi-Itens
                </button>
            </div>

        </div>
    </div>


    <!-- Modal da Sacola de Ofertas (Checkout Multi-Itens) -->
    <div id="modalSacola" class="fixed inset-0 z-[110] hidden glass-modal flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden text-slate-900 border border-slate-100 transform transition-all flex flex-col max-h-[92vh] relative">
            
            <!-- Header Modal Sacola -->
            <div class="bg-slate-900 text-white p-4 flex items-center justify-between border-b border-slate-800 flex-shrink-0">
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

                <!-- Formulário do Cliente & Canais de Envio -->
                <div class="space-y-3 pt-2 border-t border-slate-200">
                    
                    <!-- Opção de Canal de Comunicação Interno / Próprio (Fixado e Obrigatório) -->
                    <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between shadow-xs">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div>
                                <div class="text-xs font-black text-slate-900 flex items-center gap-1.5">
                                    <span>Canal Interno Pulse</span>
                                    <span class="bg-emerald-600 text-white text-[7.5px] font-black px-1.5 py-0.2 rounded-full uppercase tracking-wider">Ativo • Obrigatório</span>
                                </div>
                                <div class="text-[10px] text-slate-500 font-medium">Registrado automaticamente no painel de atendimento da loja</div>
                            </div>
                        </div>
                        <label class="relative flex items-center cursor-not-allowed">
                            <input type="checkbox" id="checkCanalInterno" checked disabled readonly class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-0 cursor-not-allowed opacity-90 accent-emerald-600 pointer-events-none">
                        </label>
                    </div>

                    <!-- WhatsApp Obrigatório na Sacola -->
                    <div>
                        <label class="block text-xs font-black text-slate-800 mb-1 flex items-center justify-between">
                            <span>Seu WhatsApp (Celular): <span class="text-red-600 font-black">*</span></span>
                            <span class="text-[9px] text-emerald-700 font-bold bg-emerald-50 border border-emerald-200 px-1.5 py-0.2 rounded-full">Obrigatório</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-emerald-600 font-black text-xs">
                                📱
                            </div>
                            <input type="tel" id="inputTelefoneCliente" placeholder="(00) 00000-0000" maxlength="15" oninput="aplicarMascaraTelefone(this); salvarDadosClienteLocalStorage();" onchange="salvarDadosClienteLocalStorage()" onblur="salvarDadosClienteLocalStorage()" class="w-full pl-8 pr-3 py-2 bg-white border-2 border-slate-300 focus:border-emerald-500 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-emerald-200 focus:outline-none transition">
                        </div>
                        <p id="msgErroZapSacola" class="hidden text-[10px] text-red-600 font-bold mt-0.5">⚠️ Informe seu WhatsApp com DDD para finalizar o pedido.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Seu Nome (Opcional):</label>
                        <input type="text" id="inputNomeCliente" placeholder="Ex: Maria Silva" oninput="salvarDadosClienteLocalStorage()" onchange="salvarDadosClienteLocalStorage()" onblur="salvarDadosClienteLocalStorage()" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Endereço de Entrega / Obs (Opcional):</label>
                        <input type="text" id="inputEnderecoCliente" placeholder="Ex: Rua das Flores, 123 - Centro" oninput="salvarDadosClienteLocalStorage()" onchange="salvarDadosClienteLocalStorage()" onblur="salvarDadosClienteLocalStorage()" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                </div>

            </div>

            <!-- Rodapé Modal Sacola -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col gap-2 flex-shrink-0">
                <button id="btnFinalizarPedido" onclick="finalizarPedidoSacolaWhatsapp()" class="w-full py-4 bg-green-600 hover:bg-green-700 active:scale-98 text-white font-black text-base rounded-2xl shadow-xl transition flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.705 1.754zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-1.15 4.2 4.293-1.125z"/></svg>
                    <span>Enviar Pedido (Canal Interno &amp; WhatsApp)</span>
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
        const totalLaminasCount = <?= $totalLaminas ?>;
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

            if (container && typeof St !== 'undefined' && St.PageFlip) {
                try {
                    pageFlipInstance = new St.PageFlip(container, {
                        width: 595,
                        height: 842,
                        size: "stretch",
                        minWidth: 320,
                        maxWidth: 1400,
                        minHeight: 452,
                        maxHeight: 1980,
                        drawShadow: true,
                        maxShadowOpacity: 0.85,
                        showCover: true,
                        mobileScrollSupport: true,
                        useMouseEvents: true,
                        flippingTime: 700
                    });

                    pageFlipInstance.loadFromHTML(document.querySelectorAll('.page-sheet'));

                    pageFlipInstance.on('flip', () => {
                        atualizarIndicadoresEBotoes();
                    });

                    pageFlipInstance.on('changeOrientation', () => {
                        atualizarIndicadoresEBotoes();
                    });

                } catch(e) {
                    console.warn("StPageFlip init warning, fallback to scroll layout:", e);
                    pageFlipInstance = null;
                }
            }

            document.getElementById('btnPrevPage').addEventListener('click', paginaAnterior);
            document.getElementById('btnNextPage').addEventListener('click', proximaPagina);

            observarLaminasScroll();
            atualizarIndicadoresEBotoes();
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

        function atualizarIndicadoresEBotoes() {
            let textoHeader = '';
            let textoFloat = '';
            let podeVoltar = false;
            let podeAvancar = false;

            if (pageFlipInstance) {
                const curIndex = pageFlipInstance.getCurrentPageIndex(); // 0-indexed
                const totalPages = totalPaginasCount;
                const orientation = pageFlipInstance.getOrientation(); // 'portrait' ou 'landscape'

                if (orientation === 'landscape') {
                    if (curIndex === 0) {
                        textoHeader = `Capa Oficial • Pág. 1 / ${totalPages}`;
                        textoFloat = `1/${totalPages}`;
                        podeVoltar = false;
                        podeAvancar = totalPages > 1;
                    } else {
                        const pagEsquerda = curIndex + 1;
                        const pagDireita = Math.min(curIndex + 2, totalPages);

                        if (pagEsquerda === pagDireita) {
                            textoHeader = (pagEsquerda === 1 ? 'Capa' : `Lâmina ${pagEsquerda - 1}`) + ` • Pág. ${pagEsquerda}/${totalPages}`;
                            textoFloat = `${pagEsquerda}/${totalPages}`;
                        } else {
                            textoHeader = `Páginas ${pagEsquerda}-${pagDireita} / ${totalPages}`;
                            textoFloat = `${pagEsquerda}-${pagDireita}/${totalPages}`;
                        }

                        podeVoltar = true;
                        podeAvancar = pagDireita < totalPages;
                    }
                } else {
                    const pagNum = curIndex + 1;
                    if (pagNum === 1) {
                        textoHeader = `Capa Oficial • Pág. 1 / ${totalPages}`;
                    } else {
                        textoHeader = `Lâmina ${pagNum - 1} de ${totalLaminasCount} • Pág. ${pagNum}/${totalPages}`;
                    }
                    textoFloat = `${pagNum}/${totalPages}`;
                    podeVoltar = pagNum > 1;
                    podeAvancar = pagNum < totalPages;
                }

                paginaAtualNum = curIndex + 1;
            } else {
                if (paginaAtualNum === 1) {
                    textoHeader = `Capa Oficial • Pág. 1 / ${totalPaginasCount}`;
                } else {
                    textoHeader = `Lâmina ${paginaAtualNum - 1} de ${totalLaminasCount} • Pág. ${paginaAtualNum}/${totalPaginasCount}`;
                }
                textoFloat = `${paginaAtualNum}/${totalPaginasCount}`;
                podeVoltar = paginaAtualNum > 1;
                podeAvancar = paginaAtualNum < totalPaginasCount;
            }


            const elHeader = document.getElementById('pageIndicator');
            if (elHeader) elHeader.textContent = textoHeader;

            const elFloat = document.getElementById('floatingPageBadge');
            if (elFloat) elFloat.textContent = textoFloat;

            const btnPrev = document.getElementById('btnPrevPage');
            const btnFloatPrev = document.getElementById('btnFloatPrevPage');
            const btnNext = document.getElementById('btnNextPage');
            const btnFloatNext = document.getElementById('btnFloatNextPage');

            [btnPrev, btnFloatPrev].forEach(btn => {
                if (!btn) return;
                if (!podeVoltar) {
                    btn.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                } else {
                    btn.classList.remove('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                }
            });

            [btnNext, btnFloatNext].forEach(btn => {
                if (!btn) return;
                if (!podeAvancar) {
                    btn.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                } else {
                    btn.classList.remove('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                }
            });
        }

        function observarLaminasScroll() {
            if (pageFlipInstance) return;
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
                                paginaAtualNum = num;
                                atualizarIndicadoresEBotoes();
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

            if (pageFlipInstance) {
                try {
                    pageFlipInstance.flip(num - 1);
                } catch(e) {}
                atualizarIndicadoresEBotoes();
            } else {
                paginaAtualNum = num;
                atualizarIndicadoresEBotoes();

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

        function extrairPrecoNumerico(prod) {
            if (!prod) return 0;
            let val = undefined;
            if (prod.precoVal !== undefined && prod.precoVal !== null && !isNaN(Number(prod.precoVal))) {
                val = Number(prod.precoVal);
            } else if (prod.preco !== undefined && prod.preco !== null && !isNaN(Number(prod.preco))) {
                val = Number(prod.preco);
            } else if (prod.precoStr) {
                val = parseFloat(String(prod.precoStr).replace('R$', '').replace(/\./g, '').replace(',', '.').trim());
            } else if (prod.preco_formatado) {
                val = parseFloat(String(prod.preco_formatado).replace('R$', '').replace(/\./g, '').replace(',', '.').trim());
            }
            if (val === undefined || isNaN(val) || val < 0) {
                val = 0;
            }
            return val;
        }

        function aplicarMascaraTelefone(input) {
            if (!input) return;
            let v = input.value.replace(/\D/g, '');
            if (v.length > 11) v = v.substring(0, 11);
            if (v.length > 10) {
                input.value = `(${v.substring(0, 2)}) ${v.substring(2, 7)}-${v.substring(7)}`;
            } else if (v.length > 6) {
                input.value = `(${v.substring(0, 2)}) ${v.substring(2, 6)}-${v.substring(6)}`;
            } else if (v.length > 2) {
                input.value = `(${v.substring(0, 2)}) ${v.substring(2)}`;
            } else if (v.length > 0) {
                input.value = `(${v}`;
            } else {
                input.value = '';
            }
        }

        function validarTelefoneCliente(valor) {
            if (!valor) return false;
            const limpo = String(valor).replace(/\D/g, '');
            return limpo.length >= 10 && limpo.length <= 11;
        }

        function salvarDadosClienteLocalStorage() {
            const zapProd = document.getElementById('inputTelefoneClienteProd') ? document.getElementById('inputTelefoneClienteProd').value.trim() : '';
            const nomeProd = document.getElementById('inputNomeClienteProd') ? document.getElementById('inputNomeClienteProd').value.trim() : '';
            const endProd = document.getElementById('inputEnderecoClienteProd') ? document.getElementById('inputEnderecoClienteProd').value.trim() : '';

            const zapSacola = document.getElementById('inputTelefoneCliente') ? document.getElementById('inputTelefoneCliente').value.trim() : '';
            const nomeSacola = document.getElementById('inputNomeCliente') ? document.getElementById('inputNomeCliente').value.trim() : '';
            const endSacola = document.getElementById('inputEnderecoCliente') ? document.getElementById('inputEnderecoCliente').value.trim() : '';

            const zap = zapProd || zapSacola || localStorage.getItem('cliente_zap_pulse') || '';
            const nome = nomeProd || nomeSacola || localStorage.getItem('cliente_nome_pulse') || '';
            const end = endProd || endSacola || localStorage.getItem('cliente_endereco_pulse') || '';

            if (zap) localStorage.setItem('cliente_zap_pulse', zap);
            if (nome) localStorage.setItem('cliente_nome_pulse', nome);
            if (end) localStorage.setItem('cliente_endereco_pulse', end);

            // Sincroniza campos entre modais
            const elZapProd = document.getElementById('inputTelefoneClienteProd');
            const elZapSacola = document.getElementById('inputTelefoneCliente');
            if (elZapProd && zap && elZapProd.value !== zap) elZapProd.value = zap;
            if (elZapSacola && zap && elZapSacola.value !== zap) elZapSacola.value = zap;

            const elNomeProd = document.getElementById('inputNomeClienteProd');
            const elNomeSacola = document.getElementById('inputNomeCliente');
            if (elNomeProd && nome && elNomeProd.value !== nome) elNomeProd.value = nome;
            if (elNomeSacola && nome && elNomeSacola.value !== nome) elNomeSacola.value = nome;

            const elEndProd = document.getElementById('inputEnderecoClienteProd');
            const elEndSacola = document.getElementById('inputEnderecoCliente');
            if (elEndProd && end && elEndProd.value !== end) elEndProd.value = end;
            if (elEndSacola && end && elEndSacola.value !== end) elEndSacola.value = end;
        }

        function carregarDadosClienteLocalStorage() {
            const zap = localStorage.getItem('cliente_zap_pulse') || '';
            const nome = localStorage.getItem('cliente_nome_pulse') || '';
            const end = localStorage.getItem('cliente_endereco_pulse') || '';

            const zapProd = document.getElementById('inputTelefoneClienteProd');
            const nomeProd = document.getElementById('inputNomeClienteProd');
            const endProd = document.getElementById('inputEnderecoClienteProd');

            const zapSacola = document.getElementById('inputTelefoneCliente');
            const nomeSacola = document.getElementById('inputNomeCliente');
            const endSacola = document.getElementById('inputEnderecoCliente');

            if (zapProd && zap) zapProd.value = zap;
            if (zapSacola && zap) zapSacola.value = zap;

            if (nomeProd && nome) nomeProd.value = nome;
            if (nomeSacola && nome) nomeSacola.value = nome;

            if (endProd && end) endProd.value = end;
            if (endSacola && end) endSacola.value = end;
        }

        let modalProdQtdVal = 1;

        window.abrirModalDetalheProduto = function(prod) {
            if (typeof prod === 'string') {
                try { prod = JSON.parse(prod); } catch(e) {}
            }
            if (!prod) return;
            produtoAtualModal = prod;
            modalProdQtdVal = 1;

            const val = extrairPrecoNumerico(prod);
            const precoFormatado = prod.preco_formatado || (typeof val === 'number' ? val.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0,00');

            document.getElementById('modalProdNome').textContent = prod.nome || 'Produto';
            document.getElementById('modalProdMarca').textContent = prod.marca ? 'Marca: ' + prod.marca : '';
            document.getElementById('modalProdCategoria').textContent = prod.categoria || 'Geral';
            document.getElementById('modalProdPreco').textContent = precoFormatado;
            document.getElementById('modalProdUnidade').textContent = prod.unidade || 'un';
            document.getElementById('modalProdQtd').textContent = '1';
            document.getElementById('modalProdSubtotal').textContent = precoFormatado;
            
            const img = document.getElementById('modalProdFoto');
            if (prod.foto) {
                img.src = prod.foto;
                img.style.display = 'block';
            } else {
                img.style.display = 'none';
            }

            const errZap = document.getElementById('msgErroZapProd');
            if (errZap) errZap.classList.add('hidden');

            carregarDadosClienteLocalStorage();
            document.getElementById('modalDetalheProduto').classList.remove('hidden');
        };

        window.alterarQtdModalProduto = function(delta) {
            if (!produtoAtualModal) return;
            modalProdQtdVal += delta;
            if (modalProdQtdVal < 1) modalProdQtdVal = 1;

            const val = extrairPrecoNumerico(produtoAtualModal);
            const subtotal = val * modalProdQtdVal;

            document.getElementById('modalProdQtd').textContent = modalProdQtdVal;
            document.getElementById('modalProdSubtotal').textContent = subtotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        window.fecharModalDetalheProduto = function() {
            document.getElementById('modalDetalheProduto').classList.add('hidden');
        };

        async function enviarPedidoProdutoIndividual() {
            if (!produtoAtualModal) return;

            const elZap = document.getElementById('inputTelefoneClienteProd') || document.getElementById('inputTelefoneCliente');
            const zapVal = (elZap ? elZap.value.trim() : '') || localStorage.getItem('cliente_zap_pulse') || '';
            const errZap = document.getElementById('msgErroZapProd');

            if (!validarTelefoneCliente(zapVal)) {
                if (errZap) errZap.classList.remove('hidden');
                if (elZap) {
                    elZap.focus();
                    elZap.classList.add('border-red-500', 'bg-red-50');
                    setTimeout(() => {
                        elZap.classList.remove('border-red-500', 'bg-red-50');
                    }, 2500);
                }
                mostrarToastFeedback('⚠️ Por favor, informe seu WhatsApp com DDD para pedir.');
                return;
            }

            if (errZap) errZap.classList.add('hidden');
            salvarDadosClienteLocalStorage();

            const prod = produtoAtualModal;
            const qtd = modalProdQtdVal || 1;
            const val = extrairPrecoNumerico(prod);
            const subtotal = val * qtd;

            const elNome = document.getElementById('inputNomeClienteProd') || document.getElementById('inputNomeCliente');
            const elEnd = document.getElementById('inputEnderecoClienteProd') || document.getElementById('inputEnderecoCliente');

            const nomeCli = (elNome ? elNome.value.trim() : '') || localStorage.getItem('cliente_nome_pulse') || '';
            const endCli = (elEnd ? elEnd.value.trim() : '') || localStorage.getItem('cliente_endereco_pulse') || '';
            const btn = document.getElementById('btnPedirWhatsappItem');

            if (btn) {
                btn.disabled = true;
                btn.classList.add('opacity-75');
            }

            // 1. Registro no Canal Interno Pulse (ClienteInbox)
            try {
                const itemData = {
                    id: String(prod.id),
                    nome: prod.nome || 'Produto',
                    precoVal: val,
                    qtd: qtd
                };
                const payloadInterno = {
                    nome_cliente: nomeCli,
                    telefone_cliente: zapVal,
                    endereco_cliente: endCli,
                    total: subtotal,
                    itens: [itemData]
                };

                await fetch('<?= Url::to(['/vendas/encarte-publico/enviar-pedido', 'token' => $encarte->token_publico]) ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payloadInterno)
                });
            } catch (err) {
                console.warn('Registro no canal interno:', err);
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.classList.remove('opacity-75');
                }
            }

            // 2. Montagem e Abertura do WhatsApp
            let texto = `🛒 *NOVO PEDIDO DO ENCARTE DIGITAL*\n🏪 *Loja:* ${nomeLojaStr}\n\n`;
            texto += `📌 *Produto:* ${prod.nome}\n`;
            texto += `🔢 *Quantidade:* ${qtd} ${prod.unidade || 'un'}\n`;
            texto += `💰 *Valor:* R$ ${subtotal.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}\n`;
            texto += `📱 *WhatsApp:* ${zapVal}\n`;

            if (nomeCli) texto += `👤 *Cliente:* ${nomeCli}\n`;
            if (endCli) texto += `📍 *Endereço/Obs:* ${endCli}\n`;

            texto += `\n(Pedido registrado no Canal Interno Pulse)\nComo faço para concluir o pagamento e entrega?`;

            let urlZap = '';
            if (whatsappLojaClean) {
                urlZap = `https://wa.me/${whatsappLojaClean}?text=${encodeURIComponent(texto)}`;
            } else {
                urlZap = `https://wa.me/?text=${encodeURIComponent(texto)}`;
            }

            window.open(urlZap, '_blank');
        }

        function adicionarModalNaSacola() {
            if (!produtoAtualModal) return;

            const zapInput = document.getElementById('inputTelefoneClienteProd');
            const zapVal = zapInput ? zapInput.value.trim() : '';
            const errZap = document.getElementById('msgErroZapProd');

            if (!validarTelefoneCliente(zapVal)) {
                if (errZap) errZap.classList.remove('hidden');
                if (zapInput) {
                    zapInput.focus();
                    zapInput.classList.add('border-red-500', 'bg-red-50');
                    setTimeout(() => {
                        zapInput.classList.remove('border-red-500', 'bg-red-50');
                    }, 2500);
                }
                mostrarToastFeedback('⚠️ Por favor, informe seu WhatsApp com DDD para adicionar à sacola.');
                return;
            }

            if (errZap) errZap.classList.add('hidden');
            salvarDadosClienteLocalStorage();

            const prod = produtoAtualModal;
            const qtdAdicionar = modalProdQtdVal || 1;
            const val = extrairPrecoNumerico(prod);
            const str = prod.preco_formatado || val.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const idKey = String(prod.id);

            if (!sacolaItens[idKey]) {
                sacolaItens[idKey] = {
                    id: idKey,
                    nome: prod.nome || 'Produto',
                    precoVal: val,
                    precoStr: str,
                    unidade: prod.unidade || 'un',
                    qtd: qtdAdicionar
                };
            } else {
                sacolaItens[idKey].qtd = (sacolaItens[idKey].qtd || 0) + qtdAdicionar;
                sacolaItens[idKey].precoVal = val;
                sacolaItens[idKey].precoStr = str;
            }

            salvarAtualizarSacola();
            fecharModalDetalheProduto();
            mostrarToastFeedback(`✅ ${qtdAdicionar}x ${prod.nome} adicionado à sacola!`);
        }

        function adicionarDirectoSacola(prod) {
            if (typeof prod === 'string') {
                try { prod = JSON.parse(prod); } catch(e) {}
            }
            if (!prod || !prod.id) return;

            // Se o cliente ainda não informou o WhatsApp obrigatório, abre o modal para solicitar uma única vez
            const zapSalvo = localStorage.getItem('cliente_zap_pulse');
            if (!validarTelefoneCliente(zapSalvo)) {
                abrirModalDetalheProduto(prod);
                mostrarToastFeedback('📱 Informe seu WhatsApp uma vez para incluir itens na sacola.');
                return;
            }

            const val = extrairPrecoNumerico(prod);
            const str = prod.preco_formatado || val.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const idKey = String(prod.id);

            if (!sacolaItens[idKey]) {
                sacolaItens[idKey] = {
                    id: idKey,
                    nome: prod.nome || 'Produto',
                    precoVal: val,
                    precoStr: str,
                    unidade: prod.unidade || 'un',
                    qtd: 1
                };
            } else {
                sacolaItens[idKey].qtd = (sacolaItens[idKey].qtd || 0) + 1;
                sacolaItens[idKey].precoVal = val;
                sacolaItens[idKey].precoStr = str;
            }

            salvarAtualizarSacola();
            mostrarToastFeedback('✅ ' + (prod.nome || 'Produto') + ' adicionado à sacola!');
        }

        function alterarQtdSacola(id, delta) {
            const idKey = String(id);
            if (sacolaItens[idKey]) {
                sacolaItens[idKey].qtd = (sacolaItens[idKey].qtd || 0) + delta;
                if (sacolaItens[idKey].qtd <= 0) {
                    delete sacolaItens[idKey];
                }
                salvarAtualizarSacola();
                renderizarModalSacola();
            }
        }

        function removerItemSacola(id) {
            const idKey = String(id);
            if (sacolaItens[idKey]) {
                const nome = sacolaItens[idKey].nome || 'Item';
                delete sacolaItens[idKey];
                salvarAtualizarSacola();
                renderizarModalSacola();
                mostrarToastFeedback(`🗑️ ${nome} removido da sacola.`);
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
                const sub = (item.precoVal || 0) * (item.qtd || 0);
                if (!isNaN(sub)) {
                    totalVal += sub;
                }
                totalQtd += (item.qtd || 0);
            });

            const badgeEl = document.getElementById('sacolaBadge');
            if (badgeEl) badgeEl.textContent = totalQtd;

            const txtEl = document.getElementById('sacolaTotalText');
            if (txtEl) txtEl.textContent = totalVal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const modalTotEl = document.getElementById('sacolaModalTotal');
            if (modalTotEl) modalTotEl.textContent = totalVal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function carregarSacolaLocalStorage() {
            try {
                const data = localStorage.getItem('sacola_encarte_pulse');
                if (data) {
                    const parsed = JSON.parse(data);
                    sacolaItens = {};
                    if (parsed && typeof parsed === 'object') {
                        Object.entries(parsed).forEach(([k, item]) => {
                            if (!item || !item.id) return;
                            const val = extrairPrecoNumerico(item);
                            let qtd = parseInt(item.qtd, 10);
                            if (isNaN(qtd) || qtd <= 0) qtd = 1;
                            const str = item.precoStr || val.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            sacolaItens[String(item.id)] = {
                                id: String(item.id),
                                nome: item.nome || 'Produto',
                                precoVal: val,
                                precoStr: str,
                                unidade: item.unidade || 'un',
                                qtd: qtd
                            };
                        });
                    }
                }
            } catch(e) {
                sacolaItens = {};
            }
            salvarAtualizarSacola();
            carregarDadosClienteLocalStorage();
        }

        function abrirModalSacola() {
            carregarDadosClienteLocalStorage();
            renderizarModalSacola();
            const errZap = document.getElementById('msgErroZapSacola');
            if (errZap) errZap.classList.add('hidden');
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
                const subtotal = (item.precoVal || 0) * (item.qtd || 0);
                const div = document.createElement('div');
                div.className = 'flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs shadow-2xs gap-2';
                div.innerHTML = `
                    <div class="flex-1 pr-2 min-w-0">
                        <div class="font-extrabold text-slate-900 truncate">${item.nome}</div>
                        <div class="text-slate-500 font-semibold text-[10px]">R$ ${item.precoStr} / ${item.unidade}</div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <div class="flex items-center bg-slate-200 rounded-lg">
                            <button type="button" onclick="alterarQtdSacola('${item.id}', -1)" class="px-2 py-0.5 font-bold hover:bg-slate-300 rounded-l-lg transition cursor-pointer text-slate-800">-</button>
                            <span class="px-2 font-black text-slate-800">${item.qtd}</span>
                            <button type="button" onclick="alterarQtdSacola('${item.id}', 1)" class="px-2 py-0.5 font-bold hover:bg-slate-300 rounded-r-lg transition cursor-pointer text-slate-800">+</button>
                        </div>
                        <div class="font-montserrat font-black text-emerald-700 w-16 text-right text-xs">R$ ${subtotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                        <button type="button" onclick="removerItemSacola('${item.id}')" title="Remover item da sacola" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                `;
                container.appendChild(div);
            });
        }

        async function finalizarPedidoSacolaWhatsapp() {
            const lista = Object.values(sacolaItens);
            if (lista.length === 0) {
                alert('Adicione pelo menos um produto à sacola antes de enviar.');
                return;
            }

            const elZap = document.getElementById('inputTelefoneCliente') || document.getElementById('inputTelefoneClienteProd');
            const zapVal = (elZap ? elZap.value.trim() : '') || localStorage.getItem('cliente_zap_pulse') || '';
            const errZap = document.getElementById('msgErroZapSacola');

            if (!validarTelefoneCliente(zapVal)) {
                if (errZap) errZap.classList.remove('hidden');
                if (elZap) {
                    elZap.focus();
                    elZap.classList.add('border-red-500', 'bg-red-50');
                    setTimeout(() => {
                        elZap.classList.remove('border-red-500', 'bg-red-50');
                    }, 2500);
                }
                mostrarToastFeedback('⚠️ Por favor, informe seu WhatsApp com DDD para finalizar o pedido.');
                return;
            }

            if (errZap) errZap.classList.add('hidden');
            salvarDadosClienteLocalStorage();

            const elNome = document.getElementById('inputNomeCliente') || document.getElementById('inputNomeClienteProd');
            const elEnd = document.getElementById('inputEnderecoCliente') || document.getElementById('inputEnderecoClienteProd');

            const nomeCli = (elNome ? elNome.value.trim() : '') || localStorage.getItem('cliente_nome_pulse') || '';
            const endCli = (elEnd ? elEnd.value.trim() : '') || localStorage.getItem('cliente_endereco_pulse') || '';
            const btn = document.getElementById('btnFinalizarPedido');

            let totalVal = 0;
            let textoItens = '';

            lista.forEach(item => {
                const sub = (item.precoVal || 0) * (item.qtd || 0);
                totalVal += sub;
                textoItens += `• *${item.qtd}x* ${item.nome} (R$ ${sub.toLocaleString('pt-BR', { minimumFractionDigits: 2 })})\n`;
            });

            // 1. Envio Assíncrono para o Canal de Comunicação Interno (Pulse Inbox)
            if (btn) {
                btn.disabled = true;
                btn.classList.add('opacity-75');
            }

            try {
                const payloadInterno = {
                    nome_cliente: nomeCli,
                    telefone_cliente: zapVal,
                    endereco_cliente: endCli,
                    total: totalVal,
                    itens: lista
                };

                await fetch('<?= Url::to(['/vendas/encarte-publico/enviar-pedido', 'token' => $encarte->token_publico]) ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payloadInterno)
                });
            } catch (err) {
                console.warn('Registro no canal interno:', err);
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.classList.remove('opacity-75');
                }
            }

            // 2. Montagem e Abertura do WhatsApp
            let textoFinal = `🛒 *NOVO PEDIDO DO ENCARTE DIGITAL*\n🏪 *Loja:* ${nomeLojaStr}\n\n📋 *ITENS SELECIONADOS:*\n${textoItens}\n💰 *TOTAL DO PEDIDO:* R$ ${totalVal.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}\n`;
            textoFinal += `📱 *WhatsApp:* ${zapVal}\n`;

            if (nomeCli) textoFinal += `👤 *Cliente:* ${nomeCli}\n`;
            if (endCli) textoFinal += `📍 *Endereço/Obs:* ${endCli}\n`;

            textoFinal += `\n(Pedido registrado no Canal Interno Pulse)\nComo faço para confirmar o pagamento e entrega?`;

            let urlZap = '';
            if (whatsappLojaClean) {
                urlZap = `https://wa.me/${whatsappLojaClean}?text=${encodeURIComponent(textoFinal)}`;
            } else {
                urlZap = `https://wa.me/?text=${encodeURIComponent(textoFinal)}`;
            }

            window.open(urlZap, '_blank');

            // 3. Limpeza automática da sacola após envio do pedido
            limparSacola();
            fecharModalSacola();
            mostrarToastFeedback('✅ Pedido enviado com sucesso! Sua sacola foi limpa.');
        }

        function mostrarToastFeedback(msg) {
            let toast = document.getElementById('sacolaToastFeedback');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'sacolaToastFeedback';
                toast.className = 'fixed top-16 left-1/2 transform -translate-x-1/2 z-[120] bg-slate-900 text-white font-bold text-xs px-4 py-2 rounded-2xl shadow-2xl border border-emerald-500 transition-all duration-300 pointer-events-none opacity-0';
                document.body.appendChild(toast);
            }
            toast.textContent = msg;
            toast.classList.remove('opacity-0', 'translate-y-2');
            toast.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => {
                toast.classList.remove('opacity-100', 'translate-y-0');
                toast.classList.add('opacity-0', 'translate-y-2');
            }, 2000);
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
