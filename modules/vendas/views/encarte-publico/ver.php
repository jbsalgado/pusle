<?php

use yii\helpers\Html;
use yii\helpers\Url;

$titulo = Html::encode($encarte->titulo);
$subtitulo = Html::encode($encarte->subtitulo ?: 'Ofertas Imbatíveis');
$nomeLoja = $loja ? Html::encode($loja->nome ?: 'Nossa Loja') : 'Pulse Vendas';
$telefoneLoja = $loja ? Html::encode($loja->telefone ?: '') : '';
$whatsappClean = preg_replace('/[^0-9]/', '', $telefoneLoja);

$ppp = $encarte->produtos_por_pagina ?: 6;
$paginas = array_chunk($encarteProdutos, $ppp);
$totalPaginas = count($paginas);
$urlPublica = $encarte->getUrlPublica();
$urlPdf = $encarte->getUrlPdf();
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
            min-height: calc(100vh - 120px);
            padding: 15px 10px;
        }

        .flipbook-container {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            border-radius: 16px;
            background-color: #ffffff;
        }

        /* Estilo da Lâmina do Tabloide */
        .page-sheet {
            background-color: #ffffff;
            color: #0f172a;
            box-sizing: border-box;
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            user-select: none;
            border-radius: 16px;
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
            transition: all 0.25s ease;
            cursor: pointer;
            border: 2px solid #e2e8f0;
            touch-action: manipulation;
            -webkit-tap-highlight-color: rgba(239, 68, 68, 0.2);
        }

        .hotspot-card:active, .hotspot-card:hover {
            transform: scale(0.98);
            border-color: #ef4444;
            box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.25);
        }

        /* Modal Glassmorphism de Produto */
        .glass-modal {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between theme-<?= Html::encode($encarte->cor_tema) ?>">

    <!-- Top Bar Navegação e Ações -->
    <header class="sticky top-0 z-40 bg-slate-900/95 backdrop-blur-md border-b border-slate-800 px-4 py-3 shadow-xl">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
            
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
                    Baixar PDF
                </a>

                <!-- Compartilhar WhatsApp -->
                <button onclick="compartilharEncarte()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white font-bold text-xs rounded-xl transition shadow-md">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.705 1.754zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-1.15 4.2 4.293-1.125z"/></svg>
                    Compartilhar
                </button>
            </div>

        </div>
    </header>

    <!-- Área Principal do Folheto (Suporta Feed Responsivo e Flipbook 3D) -->
    <main class="flipbook-stage">
        <div id="flipbookContainer" class="flipbook-container w-full max-w-5xl space-y-6 sm:space-y-0">
            
            <?php foreach ($paginas as $indexPagina => $itensPagina): 
                $countItensPag = count($itensPagina);
                $gridCols = ($countItensPag <= 2) ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-2 sm:grid-cols-3';
            ?>
                <div id="lamina-<?= ($indexPagina + 1) ?>" class="page-sheet w-full min-h-[500px] flex flex-col justify-between p-4 sm:p-6 mb-6 sm:mb-0 shadow-lg border border-slate-100">
                    
                    <!-- Topo Lâmina -->
                    <div class="header-tabloide p-4 rounded-2xl shadow-md mb-4 flex items-center justify-between">
                        <div>
                            <span class="badge-promo px-2.5 py-0.5 rounded-md font-montserrat font-extrabold text-[10px] uppercase tracking-wider">OFERTA ESPECIAL</span>
                            <h2 class="font-montserrat font-black text-lg sm:text-2xl uppercase tracking-tight leading-none mt-1"><?= $titulo ?></h2>
                            <p class="text-xs opacity-90 font-medium"><?= $subtitulo ?></p>
                        </div>
                        <?php if ($telefoneLoja): ?>
                            <div class="hidden sm:block text-right">
                                <div class="text-[10px] font-bold uppercase opacity-80">Peça no Zap</div>
                                <div class="text-sm font-black"><?= $telefoneLoja ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Grade de Produtos Nativamente Rolável -->
                    <div class="grid <?= $gridCols ?> gap-4 flex-1 align-content-start my-2">
                        <?php foreach ($itensPagina as $encarteProd): 
                            $produto = $encarteProd->produto;
                            if (!$produto) continue;

                            $precoVal = $encarteProd->getPrecoFinal();
                            $precoFormatado = number_format($precoVal, 2, ',', '.');
                            $partesPreco = explode(',', $precoFormatado);

                            $foto = $produto->fotoPrincipal ?: ($produto->fotos[0] ?? null);
                            $urlFoto = $foto ? Url::to('@web/' . ltrim($foto->arquivo_path, '/'), true) : null;

                            $jsonProdData = Html::encode(json_encode([
                                'id' => $produto->id,
                                'nome' => $produto->nome,
                                'marca' => $produto->marca ?: '',
                                'categoria' => $produto->categoria ? $produto->categoria->nome : '',
                                'preco' => $precoFormatado,
                                'unidade' => $produto->unidade_medida ?: 'un',
                                'foto' => $urlFoto,
                                'codigo' => $produto->codigo_barras ?: $produto->codigo_referencia ?: '',
                                'estoque' => (float)$produto->estoque_atual
                            ]));
                        ?>
                            <div onclick="abrirModalDetalheProduto(<?= $jsonProdData ?>)" ontouchend="abrirModalDetalheProduto(<?= $jsonProdData ?>)" class="hotspot-card bg-slate-50 rounded-2xl p-4 flex flex-col justify-between relative overflow-hidden group">
                                
                                <!-- Badge Starburst Oferta -->
                                <div class="absolute top-2 right-2 bg-amber-400 text-black font-montserrat font-black text-[9px] px-2.5 py-1 rounded-full uppercase tracking-tighter shadow-md z-10">
                                    OFERTA
                                </div>

                                <!-- Imagem -->
                                <div class="h-40 sm:h-44 w-full flex items-center justify-center mb-3 p-2 bg-white rounded-xl">
                                    <?php if ($urlFoto): ?>
                                        <img src="<?= $urlFoto ?>" alt="<?= Html::encode($produto->nome) ?>" class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform duration-300">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-slate-200 rounded-xl flex items-center justify-center text-slate-400 text-xs font-bold">FOTO DO PRODUTO</div>
                                    <?php endif; ?>
                                </div>

                                <!-- Nome e Marca -->
                                <div class="mb-3 text-center">
                                    <?php if ($produto->marca): ?>
                                        <div class="text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-0.5"><?= Html::encode($produto->marca) ?></div>
                                    <?php endif; ?>
                                    <div class="text-xs sm:text-sm font-extrabold text-slate-900 line-clamp-2 leading-snug group-hover:text-red-600 transition-colors">
                                        <?= Html::encode($produto->nome) ?>
                                    </div>
                                </div>

                                <!-- Preço Destacado Tabloide -->
                                <div class="price-tag p-2.5 rounded-xl text-center shadow-md flex items-baseline justify-center gap-0.5">
                                    <span class="text-[11px] font-extrabold">R$</span>
                                    <span class="font-montserrat font-black text-2xl sm:text-3xl leading-none"><?= $partesPreco[0] ?></span>
                                    <span class="text-sm font-bold">,<?= $partesPreco[1] ?></span>
                                    <span class="text-[10px] font-semibold opacity-90 ml-0.5">/<?= Html::encode($produto->unidade_medida ?: 'un') ?></span>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Rodapé da Lâmina -->
                    <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-[10px] text-slate-500">
                        <div>Ofertas válidas enquanto durarem os estoques. Imagens ilustrativas.</div>
                        <div class="font-bold">Lâmina <?= ($indexPagina + 1) ?>/<?= $totalPaginas ?></div>
                    </div>

                </div>
            <?php endforeach; ?>

        </div>
    </main>

    <!-- Rodapé Público -->
    <footer class="bg-slate-900 border-t border-slate-800 py-4 text-center text-xs text-slate-400">
        <?= $nomeLoja ?> © <?= date('Y') ?> • Catálogo Gerado via Pulse Vendas
    </footer>

    <!-- Modal Interativo de Detalhes do Produto (Hotspot Click / Touch) -->
    <div id="modalDetalheProduto" class="fixed inset-0 z-[100] hidden glass-modal flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden text-slate-900 border border-slate-100 transform transition-all flex flex-col max-h-[90vh]">
            
            <div class="relative bg-slate-100 p-6 flex items-center justify-center h-64 border-b border-slate-200">
                <button onclick="fecharModalDetalheProduto()" class="absolute top-4 right-4 bg-white/90 hover:bg-white text-slate-700 p-2.5 rounded-full shadow-lg transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
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

                <!-- Botão Pedir pelo WhatsApp -->
                <button id="btnPedirWhatsapp" onclick="enviarPedidoZap()" class="w-full py-4 bg-green-600 hover:bg-green-700 text-white font-extrabold rounded-2xl shadow-xl transition flex items-center justify-center gap-2 text-base">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.705 1.754zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-1.15 4.2 4.293-1.125z"/></svg>
                    Pedir no WhatsApp da Loja
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

        document.addEventListener("DOMContentLoaded", function() {
            const container = document.getElementById('flipbookContainer');
            const isDesktopScreen = window.innerWidth >= 768;

            if (isDesktopScreen && container && typeof St !== 'undefined' && St.PageFlip) {
                try {
                    container.classList.add('aspect-[4/3]');
                    pageFlipInstance = new St.PageFlip(container, {
                        width: 550,
                        height: 750,
                        size: "stretch",
                        minWidth: 320,
                        maxWidth: 1000,
                        minHeight: 450,
                        maxHeight: 1350,
                        maxShadowOpacity: 0.5,
                        showCover: false,
                        mobileScrollSupport: true
                    });

                    pageFlipInstance.loadFromHTML(document.querySelectorAll('.page-sheet'));

                    pageFlipInstance.on('flip', (e) => {
                        paginaAtualNum = e.data + 1;
                        document.getElementById('pageIndicator').textContent = `Página ${paginaAtualNum} / ${totalPaginasCount}`;
                    });

                } catch(e) {
                    console.warn("StPageFlip init warning, fallback to scroll layout:", e);
                }
            }

            document.getElementById('btnPrevPage').addEventListener('click', () => {
                if (pageFlipInstance) {
                    pageFlipInstance.flipPrev();
                } else {
                    if (paginaAtualNum > 1) {
                        paginaAtualNum--;
                        irParaLamina(paginaAtualNum);
                    }
                }
            });

            document.getElementById('btnNextPage').addEventListener('click', () => {
                if (pageFlipInstance) {
                    pageFlipInstance.flipNext();
                } else {
                    if (paginaAtualNum < totalPaginasCount) {
                        paginaAtualNum++;
                        irParaLamina(paginaAtualNum);
                    }
                }
            });
        });

        function irParaLamina(num) {
            paginaAtualNum = num;
            document.getElementById('pageIndicator').textContent = `Página ${num} / ${totalPaginasCount}`;
            const el = document.getElementById('lamina-' + num);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
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
