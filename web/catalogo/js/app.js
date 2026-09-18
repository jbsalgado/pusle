// app.js - Aplicação principal do catálogo PWA
// ✅ VERSÃO COMPLETA E CORRIGIDA (Baseado no app-old.js)
//
// ⚠️ ATENÇÃO: A função popularFormasPagamento() contém lógica crítica de filtragem
// que NÃO deve ser alterada sem revisão. Ela garante que apenas formas de pagamento
// apropriadas sejam exibidas baseado em api_de_pagamento=true.
// 
// REGRAS CRÍTICAS (ver função popularFormasPagamento para detalhes):
// - BOLETO, CARTAO_CREDITO, CARTAO_DEBITO, CARTAO, PIX (dinâmico) só aparecem se api_de_pagamento=true
// - DINHEIRO sempre removido
// - PIX_ESTATICO e PAGAR_AO_ENTREGADOR sempre disponíveis

import { CONFIG, API_ENDPOINTS, carregarConfigLoja } from './config.js';
import { 
    getCarrinho, 
    setCarrinho, 
    adicionarAoCarrinho, 
    removerDoCarrinho,
    aumentarQuantidadeItem,
    diminuirQuantidadeItem,
    calcularTotalCarrinho,
    calcularTotalItens,
    calcularTotalPecas,
    limparCarrinho,
    atualizarIndicadoresCarrinho,
    atualizarBadgeProduto
} from './cart.js';
import { carregarCarrinho, limparDadosLocaisPosSinc } from './storage.js';
import { finalizarPedido } from './order.js?v=20260916_v1';
import { 
    carregarFormasPagamento, 
    calcularParcelas, 
    formatarInfoParcelas 
} from './payment.js?v=20260911_08';
import { 
    validarCPF, 
    formatarCPF, 
    maskCPF, 
    maskPhone,
    formatarMoeda,
    formatarQuantidade,
    verificarElementosCriticos
} from './utils.js';
import { ELEMENTOS_CRITICOS } from './config.js';
import { inicializarMonitoramentoRede } from './network.js';
import { cadastrarCliente } from './customer.js';

import { mostrarModalPixEstatico } from './pix.js';
import { inicializarSocial, toggleSelecaoProduto } from './social.js';

// Disponibiliza CONFIG e funções do carrinho no window para compatibilidade geral
window.CONFIG = CONFIG;
window.getCarrinho = getCarrinho;
window.setCarrinho = setCarrinho;
window.calcularTotalCarrinho = calcularTotalCarrinho;
window.calcularTotalItens = calcularTotalItens;
window.calcularTotalPecas = calcularTotalPecas;

// ==========================================================================
// VARIÁVEIS GLOBAIS
// ==========================================================================

let produtos = [];
let produtosFiltrados = []; // Produtos filtrados pela busca
let clienteAtual = null;
let colaboradorAtual = null;
let formasPagamento = [];
let slideshowProdutos = [];
let slideshowIndex = 0;
let slideshowInterval = null;
let ordemAtual = 'padrao';

// ==========================================================================
// INICIALIZAÇÃO
// ==========================================================================

async function init() {
    try {
        console.log('[App] 🚀 Iniciando aplicação...');
        console.log('[App] 🏪 Loja ID:', CONFIG.ID_USUARIO_LOJA);
        
        // 1️⃣ Verificar elementos críticos do DOM
        verificarElementosCriticos(ELEMENTOS_CRITICOS);
        
        // 2️⃣ Carregar configuração da loja (gateways de pagamento)
        console.log('[App] ⚙️ Carregando configuração da loja...');
        const gatewayConfig = await carregarConfigLoja();

        // ✅ MULTI-TENANCY: Se nenhuma loja foi identificada → redireciona para vitrine de lojas
        if (gatewayConfig?.lojaIdentificada === false) {
            console.warn('[App] 🏬 Loja não identificada. Redirecionando para vitrine de lojas...');
            window.location.replace('lojas.html');
            return;
        }

        // ✅ MODO IMPLANTAÇÃO: Se o catálogo estiver desativado pelo lojista
        const isCatalogoInativo = gatewayConfig?.catalogoAtivo === false
            || CONFIG?.LOJA_INFO?.catalogo_ativo === false
            || window.CONFIG?.LOJA_INFO?.catalogo_ativo === false
            || (gatewayConfig?.lojaInfo && gatewayConfig.lojaInfo.catalogo_ativo === false);

        if (isCatalogoInativo) {
            console.warn('[App] 🚧 Catálogo desativado pelo lojista (Modo Implantação).');
            const info = gatewayConfig?.lojaInfo || CONFIG?.LOJA_INFO || window.CONFIG?.LOJA_INFO || {};
            renderizarTelaManutencao(info);
            return;
        }

        // Disponibilizar GATEWAY_CONFIG no window para uso em outras funções
        window.GATEWAY_CONFIG = gatewayConfig;

        // Configura o botão Entrar no topo
        const btnEntrarLoja = document.getElementById('btn-entrar-loja');
        if (btnEntrarLoja) {
            const loginUrl = CONFIG.URL_API.replace('/index.php', '') + '/index.php/auth/login?loja=' + encodeURIComponent(CONFIG._slugDetectado || '');
            btnEntrarLoja.href = loginUrl;
        }

        // 2.5️⃣ Carregar dados da loja e hero banner
        await carregarDadosLojaHero();
        
        // 2.6️⃣ Carregar slideshow de destaques (promoções / mais vendidos)
        await carregarSlideshowDestaques();
        
        // 3️⃣ Registrar Service Worker
        await registrarServiceWorker();
        
        // 4️⃣ Carregar carrinho salvo
        await carregarCarrinhoInicial();
        
        // 5️⃣ Carregar produtos
        await carregarProdutos();
        
        // 6️⃣ Inicializar busca de produtos
        inicializarBuscaProdutos();
        
        // 7️⃣ Inicializar event listeners
        inicializarEventListeners();
        
        // 8️⃣ Configurar listener do Service Worker
        configurarListenerServiceWorker();
        
        // 9️⃣ Atualizar badge do carrinho
        atualizarBadgeCarrinho();
        
        // 🔟 Inicializar monitoramento de rede (status online/offline)
        // 11️⃣ Inicializar módulo social
        inicializarSocial();

        // 12️⃣ Verificar se retornou do checkout (Mercado Pago / Asaas)
        await verificarRetornoCheckout();
        
        console.log('[App] ✅ Aplicação inicializada com sucesso!');
        
    } catch (error) {
        console.error('[App] ❌ Erro na inicialização:', error);
        mostrarErro('Erro ao inicializar a aplicação. Por favor, recarregue a página.');
    }
}

/**
 * ✅ NOVO: Verifica se o usuário retornou de um checkout externo
 */
async function verificarRetornoCheckout() {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const externalRef = urlParams.get('external_reference');
    const paymentId = urlParams.get('payment_id') || urlParams.get('collection_id');

    if (externalRef && (status || paymentId)) {
        console.log('[App] 💳 Retorno de checkout detectado:', { status, externalRef, paymentId });
        
        // Se o status for "approved", "pending" ou apenas tivermos a referência
        if (['approved', 'pending', 'in_process'].includes(status) || !status) {
            
            // Cria um overlay de "Verificando Pagamento"
            const overlay = document.createElement('div');
            overlay.id = 'mp-verificando-overlay';
            overlay.className = 'fixed inset-0 bg-white bg-opacity-90 flex flex-col items-center justify-center z-[100] p-6 text-center';
            overlay.innerHTML = `
                <div class="w-16 h-16 border-4 border-brand-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                <h2 class="text-xl font-bold mb-2">Verificando seu pagamento...</h2>
                <p class="text-gray-600 mb-4">Aguarde um instante enquanto confirmamos a transação com o Mercado Pago.</p>
                <p id="mp-status-text" class="text-sm font-medium text-brand-600">Status: PROCESSANDO</p>
            `;
            document.body.appendChild(overlay);

            // Inicia o polling usando a função exportada de gateway-pagamento.js
            const { iniciarPollingStatusVenda } = await import('./gateway-pagamento.js');
            iniciarPollingStatusVenda(externalRef, 'mercadopago');
            
            // Limpa a URL para não processar novamente se der refresh
            const url = new URL(window.location);
            url.searchParams.delete('status');
            url.searchParams.delete('collection_id');
            url.searchParams.delete('collection_status');
            url.searchParams.delete('external_reference');
            url.searchParams.delete('payment_id');
            url.searchParams.delete('preference_id');
            url.searchParams.delete('site_id');
            url.searchParams.delete('processing_mode');
            url.searchParams.delete('merchant_account_id');
            window.history.replaceState({}, document.title, url);
        }
    }
}

// ==========================================================================
// LOGO DA EMPRESA
// ==========================================================================

// ==========================================================================
// DADOS DA LOJA & HERO BANNER
// ==========================================================================

async function carregarDadosLojaHero() {
    try {
        console.log('[App] 🏪 Carregando dados e hero da loja...');
        const logoImg = document.getElementById('logo-empresa');
        const heroLogoImg = document.getElementById('loja-hero-logo');
        const heroAvatarFallback = document.getElementById('loja-hero-avatar-fallback');
        const heroNome = document.getElementById('loja-hero-nome');
        const heroIniciais = document.getElementById('loja-hero-iniciais');
        const heroCidadeUf = document.getElementById('loja-hero-cidade-uf');
        const btnWhats = document.getElementById('hero-btn-whatsapp');
        const btnTel = document.getElementById('hero-btn-telefone');
        const txtTel = document.getElementById('hero-texto-telefone');

        // Fallback imediato síncrono da loja resolvida
        if (CONFIG.LOJA_INFO && CONFIG.LOJA_INFO.nome) {
            if (heroNome) heroNome.textContent = CONFIG.LOJA_INFO.nome;
            document.title = `${CONFIG.LOJA_INFO.nome} - Catálogo Online`;
        }

        if (!CONFIG.ID_USUARIO_LOJA) {
            console.warn('[App] ⚠️ ID_USUARIO_LOJA não definido ao carregar hero.');
            return;
        }

        const response = await fetch(`${API_ENDPOINTS.USUARIO_DADOS_LOJA}?usuario_id=${CONFIG.ID_USUARIO_LOJA}`);
        if (!response.ok) {
            console.warn('[App] ⚠️ Erro ao buscar dados da loja. Status:', response.status);
            return;
        }

        const dadosLoja = await response.json();
        const nomeLoja = dadosLoja.nome_loja || dadosLoja.nome || 'Catálogo Oficial';

        // Atualizar título da página e nome no Hero
        document.title = `${nomeLoja} - Catálogo Online`;
        if (heroNome) {
            heroNome.textContent = nomeLoja;
        }

        // Aplicar tema customizado de cores se houver
        if (dadosLoja.aparencia) {
            aplicarAparenciaDinamica(dadosLoja.aparencia);
        }

        // Configurar Logo e Avatar
        let logoUrl = null;
        if (dadosLoja.logo_path) {
            logoUrl = dadosLoja.logo_path.trim();
            if (!logoUrl.match(/^https?:\/\//)) {
                logoUrl = logoUrl.replace(/^\//, '');
                const baseUrl = (CONFIG && CONFIG.URL_BASE_WEB) ? CONFIG.URL_BASE_WEB.replace(/\/$/, '') : window.location.origin;
                logoUrl = `${baseUrl}/${logoUrl}`;
            }
        }

        if (logoUrl) {
            if (logoImg) {
                logoImg.src = logoUrl;
                logoImg.classList.remove('hidden');
            }
            if (heroLogoImg) {
                heroLogoImg.onload = function() {
                    this.classList.remove('hidden');
                    if (heroAvatarFallback) heroAvatarFallback.classList.add('hidden');
                };
                heroLogoImg.onerror = function() {
                    this.classList.add('hidden');
                    if (heroAvatarFallback) heroAvatarFallback.classList.remove('hidden');
                };
                heroLogoImg.src = logoUrl;
            }
        } else {
            // Monograma com iniciais da loja
            const palavras = nomeLoja.trim().split(/\s+/);
            let iniciais = palavras[0] ? palavras[0][0].toUpperCase() : '🏪';
            if (palavras.length > 1 && palavras[1]) {
                iniciais += palavras[1][0].toUpperCase();
            }
            if (heroIniciais) heroIniciais.textContent = iniciais;
            if (heroAvatarFallback) heroAvatarFallback.classList.remove('hidden');
            if (heroLogoImg) heroLogoImg.classList.add('hidden');
        }

        // Localização (Cidade / Estado)
        if (heroCidadeUf) {
            if (dadosLoja.cidade) {
                heroCidadeUf.textContent = `${dadosLoja.cidade}${dadosLoja.estado ? ' - ' + dadosLoja.estado : ''}`;
            } else if (dadosLoja.endereco) {
                heroCidadeUf.textContent = dadosLoja.endereco;
            } else {
                heroCidadeUf.textContent = 'Atendimento Online';
            }
        }

        // WhatsApp e Telefone
        const telefoneBruto = dadosLoja.celular || dadosLoja.telefone || '';
        let telefoneLimpo = telefoneBruto.replace(/\D/g, '');
        if (telefoneLimpo) {
            if (telefoneLimpo.length === 10 || telefoneLimpo.length === 11) {
                telefoneLimpo = '55' + telefoneLimpo;
            }
            const msgWhats = `Olá! Vim pelo catálogo da loja ${nomeLoja} e gostaria de tirar dúvidas.`;
            if (btnWhats) {
                btnWhats.href = `https://wa.me/${telefoneLimpo}?text=${encodeURIComponent(msgWhats)}`;
            }
            if (btnTel) {
                btnTel.href = `tel:+${telefoneLimpo}`;
                if (txtTel && telefoneBruto) txtTel.textContent = telefoneBruto;
            }
        } else {
            if (btnWhats) btnWhats.classList.add('hidden');
            if (btnTel) btnTel.classList.add('hidden');
        }

    } catch (error) {
        console.error('[App] ❌ Erro ao carregar dados da loja no hero:', error);
    }
}

// Manter alias retrocompatível
const carregarLogoEmpresa = carregarDadosLojaHero;

// ==========================================================================
// SLIDESHOW / CARROSSEL DE DESTAQUES E PROMOÇÕES
// ==========================================================================

async function carregarSlideshowDestaques() {
    try {
        const secao = document.getElementById('slideshow-destaques-secao');
        const track = document.getElementById('slideshow-track');
        const dots = document.getElementById('slideshow-dots');
        const titulo = document.getElementById('slideshow-titulo');
        const icone = document.getElementById('slideshow-icone');
        if (!secao || !track) return;

        const url = `${API_ENDPOINTS.PRODUTO_DESTAQUES}?usuario_id=${CONFIG.ID_USUARIO_LOJA}`;
        const resp = await fetch(url);
        if (!resp.ok) return;

        const json = await resp.json();
        slideshowProdutos = json.data || [];
        if (!Array.isArray(slideshowProdutos) || slideshowProdutos.length === 0) {
            secao.classList.add('hidden');
            return;
        }

        // Identifica tema dos destaques
        const temPromo = slideshowProdutos.some(p => p.tag_destaque === 'PROMOÇÃO');
        const temMaisVendido = slideshowProdutos.some(p => p.tag_destaque === 'MAIS VENDIDO');

        if (temPromo) {
            if (icone) icone.textContent = '🔥';
            if (titulo) titulo.textContent = 'Super Ofertas & Promoções';
        } else if (temMaisVendido) {
            if (icone) icone.textContent = '⭐';
            if (titulo) titulo.textContent = 'Mais Vendidos da Loja';
        } else {
            if (icone) icone.textContent = '✨';
            if (titulo) titulo.textContent = 'Destaques Recomendados';
        }

        renderizarSlideshow();
        secao.classList.remove('hidden');
        configurarControlesSlideshow();
        iniciarAutoPlaySlideshow();
    } catch (err) {
        console.warn('[Slideshow] Erro ao carregar slideshow de destaques:', err);
    }
}

function renderizarSlideshow() {
    const track = document.getElementById('slideshow-track');
    const dots = document.getElementById('slideshow-dots');
    if (!track) return;

    track.innerHTML = slideshowProdutos.map((item) => {
        let tagBadgeHtml = '';
        if (item.tag_destaque === 'PROMOÇÃO') {
            const desc = item.desconto_percentual > 0 ? ` -${item.desconto_percentual}%` : '';
            tagBadgeHtml = `<span class="bg-gradient-to-r from-red-600 to-rose-500 text-white font-black text-[11px] px-2.5 py-1 rounded-full shadow flex items-center gap-1">🔥 OFERTA${desc}</span>`;
        } else if (item.tag_destaque === 'MAIS VENDIDO') {
            tagBadgeHtml = `<span class="bg-gradient-to-r from-amber-500 to-orange-500 text-white font-bold text-[11px] px-2.5 py-1 rounded-full shadow flex items-center gap-1">⭐ MAIS VENDIDO</span>`;
        } else {
            tagBadgeHtml = `<span class="bg-gradient-to-r from-brand-600 to-brand-500 text-white font-bold text-[11px] px-2.5 py-1 rounded-full shadow flex items-center gap-1">✨ DESTAQUE</span>`;
        }

        const imgUrl = item.imagem_destaque || 'https://dummyimage.com/400x300/f3f4f6/9ca3af.png&text=Produto';
        const temDesconto = item.desconto_percentual > 0 && item.preco_original > item.preco_final;

        return `
        <div class="slideshow-slide w-full sm:w-1/2 lg:w-1/3 flex-shrink-0 p-2 sm:p-3 select-none">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 p-3.5 flex flex-col h-full relative group">
                <!-- Imagem com Badge -->
                <div class="relative w-full h-44 sm:h-48 rounded-xl overflow-hidden bg-gray-50 flex items-center justify-center mb-3">
                    <img
                        src="${imgUrl}"
                        alt="${item.nome}"
                        class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300"
                        loading="lazy"
                        onerror="this.src='https://dummyimage.com/400x300/f3f4f6/9ca3af.png&text=Sem+Foto'"
                    />
                    <div class="absolute top-2 left-2 z-10">
                        ${tagBadgeHtml}
                    </div>
                </div>

                <!-- Textos -->
                <div class="flex-1 flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm sm:text-base line-clamp-1 mb-1" title="${item.nome}">
                            ${item.nome}
                        </h3>
                        ${item.descricao ? `<p class="text-xs text-gray-500 line-clamp-1 mb-2">${item.descricao}</p>` : ''}
                    </div>

                    <div class="mt-2 pt-2 border-t border-gray-100">
                        <!-- Preços -->
                        <div class="flex items-baseline gap-2 mb-3">
                            <span class="text-xl sm:text-2xl font-black text-brand-600">
                                ${formatarMoeda(item.preco_final)}
                            </span>
                            ${temDesconto ? `
                                <span class="text-xs text-gray-400 line-through">
                                    ${formatarMoeda(item.preco_original)}
                                </span>
                            ` : ''}
                        </div>

                        <!-- Botão 1-Clique -->
                        ${item.possui_grade ? `
                            <button
                                onclick="window.abrirModalVariacoes('${item.id}')"
                                class="w-full py-2.5 px-3 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 active:scale-[0.98] text-white font-bold text-xs sm:text-sm rounded-xl shadow transition-all duration-150 flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                🏷️ Escolher Opções
                            </button>
                        ` : `
                            <button
                                onclick="window.adicionarAoCarrinho1Click('${item.id}')"
                                class="w-full py-2.5 px-3 bg-gradient-to-r from-brand-600 to-brand-500 hover:from-brand-700 hover:to-brand-600 active:scale-[0.98] text-white font-bold text-xs sm:text-sm rounded-xl shadow transition-all duration-150 flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <span>Adicionar ao Carrinho</span>
                            </button>
                        `}
                    </div>
                </div>
            </div>
        </div>
        `;
    }).join('');

    // Dots
    if (dots) {
        const totalSlides = slideshowProdutos.length;
        dots.innerHTML = Array.from({ length: totalSlides }).map((_, i) => `
            <button
                class="slideshow-dot w-2 h-2 rounded-full transition-all duration-300 ${i === 0 ? 'bg-brand-600 w-5' : 'bg-gray-300'}"
                onclick="window.irParaSlide(${i})"
                aria-label="Slide ${i + 1}"
            ></button>
        `).join('');
    }
}

function configurarControlesSlideshow() {
    const btnPrev = document.getElementById('slideshow-btn-prev');
    const btnNext = document.getElementById('slideshow-btn-next');
    const wrapper = document.getElementById('slideshow-track-wrapper');

    if (btnPrev) {
        btnPrev.onclick = () => {
            pausarAutoPlay();
            navegarSlideshow(-1);
            iniciarAutoPlaySlideshow();
        };
    }
    if (btnNext) {
        btnNext.onclick = () => {
            pausarAutoPlay();
            navegarSlideshow(1);
            iniciarAutoPlaySlideshow();
        };
    }

    if (wrapper) {
        wrapper.onmouseenter = pausarAutoPlay;
        wrapper.onmouseleave = iniciarAutoPlaySlideshow;

        let startX = 0;
        wrapper.ontouchstart = (e) => {
            startX = e.touches[0].clientX;
            pausarAutoPlay();
        };
        wrapper.ontouchend = (e) => {
            const endX = e.changedTouches[0].clientX;
            const diffX = startX - endX;
            if (Math.abs(diffX) > 40) {
                if (diffX > 0) navegarSlideshow(1);
                else navegarSlideshow(-1);
            }
            iniciarAutoPlaySlideshow();
        };
    }
}

function navegarSlideshow(direcao) {
    if (!slideshowProdutos.length) return;
    const track = document.getElementById('slideshow-track');
    const dots = document.querySelectorAll('.slideshow-dot');
    const slides = document.querySelectorAll('.slideshow-slide');
    if (!track || !slides.length) return;

    const containerWidth = track.parentElement.clientWidth;
    const slideWidth = slides[0].clientWidth;
    const slidesVisiveis = Math.max(1, Math.round(containerWidth / slideWidth));
    const maxIndex = Math.max(0, slideshowProdutos.length - slidesVisiveis);

    slideshowIndex += direcao;
    if (slideshowIndex > maxIndex) {
        slideshowIndex = 0;
    } else if (slideshowIndex < 0) {
        slideshowIndex = maxIndex;
    }

    const deslocamento = slideshowIndex * slideWidth;
    track.style.transform = `translateX(-${deslocamento}px)`;

    dots.forEach((dot, idx) => {
        if (idx === slideshowIndex) {
            dot.classList.add('bg-brand-600', 'w-5');
            dot.classList.remove('bg-gray-300', 'w-2');
        } else {
            dot.classList.remove('bg-brand-600', 'w-5');
            dot.classList.add('bg-gray-300', 'w-2');
        }
    });
}

window.irParaSlide = function(index) {
    pausarAutoPlay();
    slideshowIndex = index;
    navegarSlideshow(0);
    iniciarAutoPlaySlideshow();
};

function iniciarAutoPlaySlideshow() {
    pausarAutoPlay();
    slideshowInterval = setInterval(() => {
        navegarSlideshow(1);
    }, 4500);
}

function pausarAutoPlay() {
    if (slideshowInterval) {
        clearInterval(slideshowInterval);
        slideshowInterval = null;
    }
}

// ==========================================================================
// ADIÇÃO AO CARRINHO EM 1 CLIQUE & FEEDBACK TOAST
// ==========================================================================

window.adicionarAoCarrinho1Click = function(produtoId) {
    try {
        let produto = produtos.find(p => String(p.id) === String(produtoId));
        if (!produto) {
            produto = slideshowProdutos.find(p => String(p.id) === String(produtoId));
        }
        if (!produto) {
            console.warn('[1-Click] Produto não localizado:', produtoId);
            return;
        }

        // Se o produto possui variações (grade), abre seletor tátil
        if (produto.possui_grade) {
            abrirModalVariacoes(produtoId);
            return;
        }

        // Imagem formatada
        let urlImagem = 'https://dummyimage.com/300x200/cccccc/ffffff.png&text=Sem+Imagem';
        if (produto.imagem_destaque) {
            urlImagem = produto.imagem_destaque;
        } else if (produto.fotos && produto.fotos.length > 0 && produto.fotos[0].arquivo_path) {
            const arq = produto.fotos[0].arquivo_path.replace(/^\//, '');
            const base = (CONFIG && CONFIG.URL_BASE_WEB) ? CONFIG.URL_BASE_WEB.replace(/\/$/, '') : '';
            urlImagem = `${base}/${arq}`;
        }

        const precoUnitario = produto.em_promocao && produto.preco_promocional > 0
            ? parseFloat(produto.preco_promocional)
            : parseFloat(produto.preco_venda_sugerido || produto.preco_final || 0);

        const itemParaCarrinho = {
            ...produto,
            preco_venda_sugerido: precoUnitario,
            preco_final: precoUnitario,
            imagem: urlImagem
        };

        const res = adicionarAoCarrinho(itemParaCarrinho, 1);
        if (res) {
            atualizarBadgeProduto(produtoId, true);
            atualizarBadgeCarrinho();

            // Animação no ícone do carrinho
            const btnCarrinho = document.getElementById('btn-abrir-carrinho');
            if (btnCarrinho) {
                btnCarrinho.classList.add('scale-125', 'ring-4', 'ring-emerald-400');
                setTimeout(() => {
                    btnCarrinho.classList.remove('scale-125', 'ring-4', 'ring-emerald-400');
                }, 400);
            }

            // Vibração tátil em mobile
            if (navigator.vibrate) {
                try { navigator.vibrate([35, 25, 35]); } catch(e) {}
            }

            // Toast de notificação
            mostrarToastCarrinho(produto.nome, res.jaExistia);
        }
    } catch (err) {
        console.error('[1-Click] Erro ao adicionar com 1 clique:', err);
    }
};

function mostrarToastCarrinho(nomeProduto, jaExistia = false) {
    const toast = document.getElementById('toast-carrinho');
    const toastTitulo = document.getElementById('toast-carrinho-titulo');
    const toastMsg = document.getElementById('toast-carrinho-msg');
    if (!toast) return;

    if (toastTitulo) {
        toastTitulo.textContent = jaExistia ? 'Quantidade atualizada (+1)!' : 'Adicionado ao carrinho!';
    }
    if (toastMsg) {
        toastMsg.textContent = nomeProduto;
    }

    toast.classList.remove('opacity-0', 'translate-y-8', 'pointer-events-none');
    toast.classList.add('opacity-100', 'translate-y-0');

    clearTimeout(window._toastTimeout);
    window._toastTimeout = setTimeout(() => {
        toast.classList.remove('opacity-100', 'translate-y-0');
        toast.classList.add('opacity-0', 'translate-y-8', 'pointer-events-none');
    }, 3200);
}

// ==========================================================================
// ORDENAÇÃO DINÂMICA
// ==========================================================================

window.aplicarOrdenacao = function(ordem, btnElement) {
    ordemAtual = ordem;
    
    // Atualizar chips visuais
    document.querySelectorAll('.chip-ordem').forEach(btn => {
        btn.classList.remove('bg-brand-500', 'text-white', 'shadow-sm', 'active');
        btn.classList.add('bg-gray-100', 'text-gray-700');
    });

    if (btnElement) {
        btnElement.classList.add('bg-brand-500', 'text-white', 'shadow-sm', 'active');
        btnElement.classList.remove('bg-gray-100', 'text-gray-700');
    }

    cacheProdutos.clear();
    carregarProdutos(1, true);
};

function aplicarAparenciaDinamica(aparencia) {
    if (!aparencia || !aparencia.escala_cores) {
        console.log('[App] 🎨 Sem dados de aparência customizada. Usando cores padrão.');
        return;
    }

    console.log('[App] 🎨 Aplicando tema de cores dinâmico:', aparencia.tema);

    // 1. Aplica diretamente no style inline do root (<html>) para efeito imediato
    const root = document.documentElement;
    Object.entries(aparencia.escala_cores).forEach(([peso, hex]) => {
        root.style.setProperty(`--brand-${peso}`, hex);
    });

    // 2. Injeta regras no <style id="dynamic-theme-vars"> para garantir suporte pleno a gradientes Tailwind CDN
    let styleEl = document.getElementById('dynamic-theme-vars');
    if (!styleEl) {
        styleEl = document.createElement('style');
        styleEl.id = 'dynamic-theme-vars';
        document.head.appendChild(styleEl);
    }

    let cssRules = ':root {\n';
    Object.entries(aparencia.escala_cores).forEach(([peso, hex]) => {
        cssRules += `  --brand-${peso}: ${hex};\n`;
    });
    cssRules += '}\n\n';

    cssRules += `
.bg-brand-500 { background-color: var(--brand-500) !important; }
.bg-brand-600 { background-color: var(--brand-600) !important; }
.bg-brand-700 { background-color: var(--brand-700) !important; }
.text-brand-600 { color: var(--brand-600) !important; }
.text-brand-500 { color: var(--brand-500) !important; }
.border-brand-500 { border-color: var(--brand-500) !important; }
.border-brand-600 { border-color: var(--brand-600) !important; }
.from-brand-700 { --tw-gradient-from: var(--brand-700) var(--tw-gradient-from-position) !important; --tw-gradient-to: rgba(0,0,0,0) var(--tw-gradient-to-position) !important; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to) !important; }
.via-brand-600 { --tw-gradient-to: rgba(0,0,0,0) var(--tw-gradient-to-position) !important; --tw-gradient-stops: var(--tw-gradient-from), var(--brand-600) var(--tw-gradient-via-position), var(--tw-gradient-to) !important; }
.to-brand-800 { --tw-gradient-to: var(--brand-800) var(--tw-gradient-to-position) !important; }
.from-brand-600 { --tw-gradient-from: var(--brand-600) var(--tw-gradient-from-position) !important; --tw-gradient-to: rgba(0,0,0,0) var(--tw-gradient-to-position) !important; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to) !important; }
.to-brand-500 { --tw-gradient-to: var(--brand-500) var(--tw-gradient-to-position) !important; }
`;

    styleEl.textContent = cssRules;

    // 3. Atualiza meta tag theme-color no navegador
    const metaTheme = document.querySelector('meta[name="theme-color"]');
    if (metaTheme && aparencia.escala_cores['600']) {
        metaTheme.setAttribute('content', aparencia.escala_cores['600']);
    }

    // 4. Salva no sessionStorage para renderização imediata sem flash em acessos subsequentes
    try {
        const slug = new URLSearchParams(window.location.search).get('loja') || sessionStorage.getItem('loja_slug');
        if (slug) {
            sessionStorage.setItem('loja_aparencia_' + slug, JSON.stringify(aparencia));
        }
    } catch (e) {}
}

window.aplicarAparenciaDinamica = aplicarAparenciaDinamica;

// ==========================================================================
// SERVICE WORKER
// ==========================================================================

async function registrarServiceWorker() {
    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.register(`${CONFIG.URL_BASE_WEB}/catalogo/sw.js`);
            console.log('[SW] ✅ Service Worker registrado:', registration.scope);
            
            // Verificar atualizações
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                console.log('[SW] 🔄 Nova versão encontrada');
                
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        console.log('[SW] 📦 Nova versão instalada, aguardando ativação...');
                        
                        // Salva o worker globalmente para forceSystemUpdate
                        window.newServiceWorker = newWorker;
                        
                        // Mostra o banner de atualização
                        const bannerAtualizacao = document.getElementById('banner-atualizacao');
                        if (bannerAtualizacao) {
                            bannerAtualizacao.classList.remove('hidden');
                        }
                    }
                });
            });
            
            // Função global para forçar atualização
            window.forceSystemUpdate = function() {
                if (window.newServiceWorker) {
                    window.newServiceWorker.postMessage({ type: 'SKIP_WAITING' });
                } else {
                    // Fallback se não houver worker pendente, apenas recarrega
                    window.location.reload();
                }
            };
            
            // Recarregar a página quando o novo SW assumir o controle (após SKIP_WAITING)
            let refreshing = false;
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                if (!refreshing) {
                    refreshing = true;
                    window.location.reload();
                }
            });
            
        } catch (error) {
            console.warn('[SW] ⚠️ Erro ao registrar Service Worker:', error);
        }
    } else {
        console.warn('[SW] ⚠️ Service Worker não suportado neste navegador');
    }
}

function configurarListenerServiceWorker() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', async (event) => {
            const { type, pedido, error } = event.data;
            
            console.log('[SW Message] Mensagem recebida:', type);
            
            if (type === 'SYNC_SUCCESS') {
                console.log('[App] ✅ Pedido sincronizado com sucesso!', pedido);
                
                // Limpar dados locais
                await limparDadosLocaisPosSinc();
                
                // Recarregar carrinho (agora vazio)
                await carregarCarrinhoInicial();
                atualizarBadgeCarrinho();
                renderizarCarrinho();
                
                // Notificar usuário
                alert('Pedido offline enviado com sucesso!');
                
                // Fechar modal se estiver aberto
                fecharModal('modal-cliente-pedido');
                
            } else if (type === 'SYNC_ERROR') {
                console.error('[App] ❌ Erro na sincronização:', error);
                alert(`Erro ao enviar pedido: ${error}`);
            }
        });
    }
}

// ==========================================================================
// CARRINHO
// ==========================================================================

async function carregarCarrinhoInicial() {
    try {
        const carrinhoSalvo = await carregarCarrinho();
        console.log('[App] 🛒 Carrinho carregado:', carrinhoSalvo.length, 'itens');
        setCarrinho(carrinhoSalvo);
    } catch (error) {
        console.error('[App] Erro ao carregar carrinho:', error);
        setCarrinho([]);
    }
}

function atualizarBadgeCarrinho() {
    const totalItens = calcularTotalItens();
    const totalValor = calcularTotalCarrinho();
    const badge = document.getElementById('contador-carrinho');
    const btnCarrinho = document.getElementById('btn-abrir-carrinho');
    
    if (badge) {
        badge.textContent = totalItens;
        badge.classList.toggle('hidden', totalItens === 0);
    }
    
    if (btnCarrinho) {
        btnCarrinho.disabled = false;
    }

    // Atualiza Barra Flutuante Mobile
    const floatingBar = document.getElementById('floating-cart-bar');
    const floatingQtd = document.getElementById('floating-cart-qtd');
    const floatingTotal = document.getElementById('floating-cart-total');

    if (floatingBar) {
        if (totalItens > 0) {
            floatingBar.classList.remove('translate-y-full');
            if (floatingQtd) floatingQtd.textContent = totalItens;
            if (floatingTotal) floatingTotal.textContent = formatarMoeda(totalValor);
        } else {
            floatingBar.classList.add('translate-y-full');
        }
    }
}

function renderizarCarrinho() {
    const container = document.getElementById('itens-carrinho');
    const totalElement = document.getElementById('valor-total-carrinho');
    const totalItensFooter = document.getElementById('total-itens-footer');
    const btnFinalizar = document.getElementById('btn-finalizar-pedido');
    
    const carrinho = getCarrinho();
    
    if (carrinho.length === 0) {
        window.opcaoFreteSelecionada = null;
        window.opcoesFreteDisponiveis = [];
        const resumoEl = document.getElementById('resumo-valores-carrinho');
        if (resumoEl) resumoEl.classList.add('hidden');
        const containerOpcoes = document.getElementById('carrinho-opcoes-frete');
        if (containerOpcoes) {
            containerOpcoes.innerHTML = '';
            containerOpcoes.classList.add('hidden');
        }
        if (container) container.innerHTML = '<p id="carrinho-vazio-msg" class="text-center text-gray-500 py-8"><svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>Seu carrinho está vazio</p>';
        if (btnFinalizar) btnFinalizar.disabled = true;
        if (totalElement) totalElement.textContent = 'R$ 0,00';
        if (totalItensFooter) totalItensFooter.textContent = '0';
        return;
    }
    
    if (btnFinalizar) btnFinalizar.disabled = false;
    
    // Renderizar itens com novo layout mobile-first
    container.innerHTML = carrinho.map((item, index) => {
        let urlImagem = 'https://dummyimage.com/100x100/cccccc/ffffff.png&text=Sem+Imagem';
        if (item.fotos && item.fotos.length > 0 && item.fotos[0].arquivo_path) {
            const arquivoPath = item.fotos[0].arquivo_path.replace(/^\//, '');
            const baseUrl = CONFIG.URL_BASE_WEB.replace(/\/$/, '');
            urlImagem = `${baseUrl}/${arquivoPath}`;
        } else if (item.imagem) {
            urlImagem = item.imagem;
        }
        
        const subtotal = item.preco_venda_sugerido * item.quantidade;
        
        return `
        <div class="cart-item">
            <button onclick="removerItem(${index})" class="cart-item-remove" title="Remover item">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
            
            <div class="cart-item-container">
                <img src="${urlImagem}" 
                     alt="${item.nome}"
                     class="cart-item-image"
                     onerror="this.src='https://dummyimage.com/100x100/cccccc/ffffff.png&text=Erro'">
                
                <div class="cart-item-info">
                    <h3 class="cart-item-name">${item.nome}</h3>
                    <p class="cart-item-price">${formatarMoeda(item.preco_venda_sugerido)} un.</p>
                    
                    <div class="cart-item-controls">
                        <button onclick="diminuirQtd('${item.id}')" class="qty-btn">−</button>
                        <span class="qty-value">${formatarQuantidade(item.quantidade, item.venda_fracionada)}</span>
                        <button onclick="aumentarQtd('${item.id}')" class="qty-btn">+</button>
                    </div>
                    
                    <div class="cart-item-total">
                        <p class="cart-item-subtotal">Subtotal</p>
                        <p class="cart-item-total-price">${formatarMoeda(subtotal)}</p>
                    </div>
                </div>
            </div>
        </div>
        `;
    }).join('');
    
    // Atualizar subtotal, frete e total final
    const subtotal = calcularTotalCarrinho();
    let taxaEntrega = 0;

    if (window.opcaoFreteSelecionada) {
        taxaEntrega = window.opcaoFreteSelecionada.tipo === 'RETIRADA' 
            ? 0.00 
            : parseFloat(window.opcaoFreteSelecionada.valor || 0);

        const resumoEl = document.getElementById('resumo-valores-carrinho');
        const subtotalEl = document.getElementById('subtotal-produtos-carrinho');
        const labelFreteEl = document.getElementById('label-frete-escolhido');
        const valorFreteEl = document.getElementById('valor-frete-escolhido');

        if (resumoEl) resumoEl.classList.remove('hidden');
        if (subtotalEl) subtotalEl.textContent = formatarMoeda(subtotal);
        if (labelFreteEl) labelFreteEl.textContent = `Frete (${window.opcaoFreteSelecionada.servico}):`;
        if (valorFreteEl) {
            const opt = window.opcaoFreteSelecionada;
            if (opt.gratis && opt.valor_original && opt.valor_original > 0) {
                valorFreteEl.innerHTML = `<span class="line-through text-gray-400 font-normal mr-1.5 text-xs">${formatarMoeda(opt.valor_original)}</span><span class="text-emerald-600 font-bold">Grátis 🎉</span>`;
            } else if (opt.gratis || taxaEntrega === 0) {
                valorFreteEl.textContent = 'Grátis';
            } else {
                valorFreteEl.textContent = formatarMoeda(taxaEntrega);
            }
        }
    }

    const totalFinal = subtotal + taxaEntrega;
    if (totalElement) {
        totalElement.textContent = formatarMoeda(totalFinal);
    }
    
    // Atualizar contador de itens no footer
    const totalItens = calcularTotalItens();
    const totalPecas = calcularTotalPecas();
    if (totalItensFooter) {
        totalItensFooter.innerHTML = `${totalItens} <span class="text-xs text-gray-500 font-normal">(${totalPecas} un.)</span>`;
    }
}

// Funções globais para os botões
window.aumentarQtd = function(produtoId) {
    if (aumentarQuantidadeItem(produtoId)) {
        renderizarCarrinho();
        atualizarBadgeCarrinho();
    }
};

window.diminuirQtd = function(produtoId) {
    if (diminuirQuantidadeItem(produtoId)) {
        renderizarCarrinho();
        atualizarBadgeCarrinho();
    }
};

window.removerItem = function(index) {
    const produtoId = removerDoCarrinho(index);
    if (produtoId) {
        atualizarBadgeProduto(produtoId, false);
        renderizarCarrinho();
        atualizarBadgeCarrinho();
    }
};

window.limparCarrinhoCompleto = function() {
    if (confirm('Deseja realmente limpar todo o carrinho?')) {
        limparCarrinho();
        renderizarCarrinho();
        atualizarBadgeCarrinho();
    }
};

// ==========================================================================
// PRODUTOS
// ==========================================================================

// Cache de páginas já carregadas (melhora performance)
const cacheProdutos = new Map();
const ITENS_POR_PAGINA = 50;
let paginaAtual = 1;
let metadadosPaginacao = null;
let isCarregandoMais = false;
let infiniteScrollObserver = null;

/**
 * Carrega uma página específica de produtos (paginação real + infinite scroll)
 * @param {number} pagina - Número da página a carregar (padrão: 1)
 * @param {boolean} forcarRecarregar - Se true, ignora cache e recarrega
 * @param {boolean} anexar - Se true (infinite scroll), concatena os produtos em vez de substituir
 */
async function carregarProdutos(pagina = 1, forcarRecarregar = false, anexar = false) {
    try {
        const termoBusca = document.getElementById('busca-produto')?.value?.trim() || '';
        
        // Verifica cache primeiro (apenas se for ordem padrão, sem busca e sem anexar)
        if (!forcarRecarregar && !termoBusca && ordemAtual === 'padrao' && !anexar && cacheProdutos.has(pagina)) {
            console.log(`[App] 📦 Usando cache da página ${pagina}`);
            const dadosCache = cacheProdutos.get(pagina);
            produtos = dadosCache.produtos;
            produtosFiltrados = produtos;
            paginaAtual = pagina;
            metadadosPaginacao = dadosCache.metadados;
            renderizarProdutos(produtosFiltrados, false);
            atualizarIndicadoresCarrinho();
            atualizarControlesPaginacao();
            configurarSentinelaInfiniteScroll();
            ocultarCarregando();
            return;
        }
        
        console.log(`[App] 📦 Carregando produtos (página ${pagina}, itens: ${ITENS_POR_PAGINA}, ordem: ${ordemAtual}, anexar: ${anexar})...`);
        
        if (!anexar) {
            mostrarCarregando();
        } else {
            const spinner = document.getElementById('infinite-scroll-spinner');
            if (spinner) spinner.classList.remove('hidden');
        }
        
        // Parâmetros de busca e ordenação
        let url = `${API_ENDPOINTS.PRODUTO}?usuario_id=${CONFIG.ID_USUARIO_LOJA}&page=${pagina}&per-page=${ITENS_POR_PAGINA}&expand=variacoes.fotos,fotos,categoria`;

        if (ordemAtual && ordemAtual !== 'padrao') {
            url += `&ordem=${encodeURIComponent(ordemAtual)}`;
        }

        if (termoBusca) {
            url += `&q=${encodeURIComponent(termoBusca)}`;
        }
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`Erro ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        let produtosPagina = [];
        let metadados = null;
        
        if (data.data && Array.isArray(data.data)) {
            produtosPagina = data.data;
            metadados = data.meta || {
                totalCount: produtosPagina.length,
                pageCount: 1,
                currentPage: pagina,
                perPage: ITENS_POR_PAGINA
            };
        } else if (data.items && Array.isArray(data.items)) {
            produtosPagina = data.items;
            metadados = data.meta || data._meta || {
                totalCount: produtosPagina.length,
                pageCount: 1,
                currentPage: pagina,
                perPage: ITENS_POR_PAGINA
            };
        } else if (Array.isArray(data)) {
            produtosPagina = data;
            metadados = {
                totalCount: data.length,
                pageCount: 1,
                currentPage: 1,
                perPage: data.length
            };
        } else {
            console.warn('[App] ⚠️ Formato de resposta inesperado:', data);
            produtosPagina = [];
            metadados = {
                totalCount: 0,
                pageCount: 1,
                currentPage: 1,
                perPage: ITENS_POR_PAGINA
            };
        }
        
        // Atualiza contador de produtos no topo
        const badgeContador = document.getElementById('catalogo-contador-badge');
        if (badgeContador) {
            const total = metadados?.totalCount !== undefined ? metadados.totalCount : produtosPagina.length;
            badgeContador.textContent = `${total} produto(s)`;
            badgeContador.classList.remove('hidden');
        }

        // Salva no cache apenas se não houver pesquisa, for ordem padrão e não for anexação
        if (!termoBusca && ordemAtual === 'padrao' && !anexar) {
            cacheProdutos.set(pagina, {
                produtos: produtosPagina,
                metadados: metadados
            });
        }
        
        // Atualiza variáveis globais
        if (anexar) {
            produtos = produtos.concat(produtosPagina);
            produtosFiltrados = produtosFiltrados.concat(produtosPagina);
        } else {
            produtos = produtosPagina;
            produtosFiltrados = produtos;
        }
        
        paginaAtual = pagina;
        metadadosPaginacao = metadados;
        window.paginacaoMetadados = metadados;
        
        console.log(`[App] ✅ Página ${pagina} carregada: +${produtosPagina.length} produto(s) (${produtos.length} visíveis de ${metadados.totalCount} total)`);
        
        if (anexar) {
            renderizarProdutos(produtosPagina, true);
        } else {
            aplicarFiltrosLocais();
        }
        
        atualizarIndicadoresCarrinho();
        atualizarControlesPaginacao();
        configurarSentinelaInfiniteScroll();
        ocultarCarregando();
        
    } catch (error) {
        console.error('[App] Erro ao carregar produtos:', error);
        if (!anexar) {
            mostrarErro('Erro ao carregar produtos. Verifique sua conexão.');
        }
        ocultarCarregando();
    }
}

/**
 * Mostra indicador de carregamento
 */
function mostrarCarregando() {
    const carregando = document.getElementById('carregando-produtos');
    if (carregando) {
        carregando.classList.remove('hidden');
    }
}

/**
 * Oculta indicador de carregamento
 */
function ocultarCarregando() {
    const carregando = document.getElementById('carregando-produtos');
    if (carregando) {
        carregando.classList.add('hidden');
    }
}

/**
 * Atualiza controles de paginação na interface (topo e rodapé)
 */
function atualizarControlesPaginacao() {
    const containerPaginacao = document.getElementById('controles-paginacao');
    const containerPaginacaoRodape = document.getElementById('controles-paginacao-rodape');
    
    if (!containerPaginacao && !containerPaginacaoRodape) {
        return;
    }
    
    const metadados = metadadosPaginacao || window.paginacaoMetadados;
    if (!metadados) {
        if (containerPaginacao) containerPaginacao.classList.add('hidden');
        if (containerPaginacaoRodape) containerPaginacaoRodape.classList.add('hidden');
        return;
    }
    
    // Mostra controles se houver mais de 1 página OU se totalCount > perPage
    const deveMostrar = metadados.pageCount > 1 || (metadados.totalCount > metadados.perPage);
    
    if (!deveMostrar) {
        if (containerPaginacao) containerPaginacao.classList.add('hidden');
        if (containerPaginacaoRodape) containerPaginacaoRodape.classList.add('hidden');
        return;
    }
    
    const totalVisivel = produtos.length;
    const totalCount = metadados.totalCount || totalVisivel;
    const textoInfo = `Mostrando 1-${Math.min(totalVisivel, totalCount)} de ${totalCount} produtos`;
    const textoPagina = `Página ${paginaAtual} de ${metadados.pageCount}`;
    const podeAnterior = paginaAtual > 1;
    const podeProxima = paginaAtual < metadados.pageCount;
    
    // Atualiza controles do topo
    if (containerPaginacao) {
        containerPaginacao.classList.remove('hidden');
        const infoPaginacao = document.getElementById('info-paginacao');
        if (infoPaginacao) infoPaginacao.textContent = textoInfo;
        const paginaAtualInfo = document.getElementById('pagina-atual-info');
        if (paginaAtualInfo) paginaAtualInfo.textContent = textoPagina;
        const btnAnterior = document.getElementById('btn-pagina-anterior');
        const btnProxima = document.getElementById('btn-pagina-proxima');
        if (btnAnterior) btnAnterior.disabled = !podeAnterior;
        if (btnProxima) btnProxima.disabled = !podeProxima;
    }
    
    // Atualiza controles do rodapé
    if (containerPaginacaoRodape) {
        containerPaginacaoRodape.classList.remove('hidden');
        const infoPaginacaoRodape = document.getElementById('info-paginacao-rodape');
        if (infoPaginacaoRodape) infoPaginacaoRodape.textContent = textoInfo;
        const paginaAtualInfoRodape = document.getElementById('pagina-atual-info-rodape');
        if (paginaAtualInfoRodape) paginaAtualInfoRodape.textContent = textoPagina;
        const btnAnteriorRodape = document.getElementById('btn-pagina-anterior-rodape');
        const btnProximaRodape = document.getElementById('btn-pagina-proxima-rodape');
        if (btnAnteriorRodape) btnAnteriorRodape.disabled = !podeAnterior;
        if (btnProximaRodape) btnProximaRodape.disabled = !podeProxima;
    }
}

/**
 * Configura observador de rolagem infinita (Infinite Scroll)
 */
function configurarSentinelaInfiniteScroll() {
    const sentinela = document.getElementById('sentinela-infinite-scroll');
    const spinner = document.getElementById('infinite-scroll-spinner');
    const msgFim = document.getElementById('infinite-scroll-fim');
    if (!sentinela) return;

    const metadados = metadadosPaginacao || window.paginacaoMetadados;
    if (!metadados || metadados.totalCount <= ITENS_POR_PAGINA) {
        sentinela.classList.add('hidden');
        return;
    }

    sentinela.classList.remove('hidden');

    if (paginaAtual >= metadados.pageCount) {
        // Chegou ao fim de todas as páginas
        if (spinner) spinner.classList.add('hidden');
        if (msgFim) msgFim.classList.remove('hidden');
        if (infiniteScrollObserver) {
            infiniteScrollObserver.disconnect();
        }
        return;
    }

    // Ainda há mais páginas disponíveis para rolar
    if (spinner) spinner.classList.remove('hidden');
    if (msgFim) msgFim.classList.add('hidden');

    if (infiniteScrollObserver) {
        infiniteScrollObserver.disconnect();
    }

    infiniteScrollObserver = new IntersectionObserver((entries) => {
        const entry = entries[0];
        if (entry && entry.isIntersecting && !isCarregandoMais) {
            const m = metadadosPaginacao || window.paginacaoMetadados;
            if (m && paginaAtual < m.pageCount) {
                console.log(`[App] 📜 Infinite Scroll ativado! Carregando página ${paginaAtual + 1}...`);
                isCarregandoMais = true;
                carregarProdutos(paginaAtual + 1, false, true).finally(() => {
                    isCarregandoMais = false;
                });
            }
        }
    }, {
        root: null,
        rootMargin: '400px', // Dispara antecipadamente 400px antes do rodapé para fluidez máxima
        threshold: 0.1
    });

    infiniteScrollObserver.observe(sentinela);
}

/**
 * Navega para próxima/anterior página
 */
window.navegarPagina = function(direcao) {
    const metadados = metadadosPaginacao || window.paginacaoMetadados;
    if (!metadados) {
        console.warn('[App] Metadados de paginação não disponíveis');
        return;
    }
    
    const novaPagina = paginaAtual + direcao;
    if (novaPagina < 1 || novaPagina > metadados.pageCount) {
        console.warn('[App] Página inválida:', novaPagina);
        return;
    }
    
    console.log('[App] Navegando manualmente para página:', novaPagina);
    carregarProdutos(novaPagina, false, false);
    
    // Scroll para o topo do catálogo
    const container = document.getElementById('catalogo-produtos');
    if (container) {
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

function aplicarFiltrosLocais() {
    // Filtro base: todos os produtos da página atual
    let listaFiltrada = produtos;

    // 1. Filtro por Social (Link Compartilhado)
    if (window.FILTRO_IDS_SOCIAL && window.FILTRO_IDS_SOCIAL.length > 0) {
        console.log('[App] 🔍 Aplicando filtro social:', window.FILTRO_IDS_SOCIAL);
        listaFiltrada = listaFiltrada.filter(p => 
            window.FILTRO_IDS_SOCIAL.includes(String(p.id))
        );
        
        // Se filtrou e não sobrou nada, apenas mostra o que coincidir na página.
    }

    produtosFiltrados = listaFiltrada;
    renderizarProdutos(produtosFiltrados);
}

function filtrarProdutos() {
    const termoBusca = document.getElementById('busca-produto')?.value?.trim() || '';
    const btnLimpar = document.getElementById('btn-limpar-busca');
    
    // Mostra/oculta botão de limpar busca
    if (btnLimpar) {
        btnLimpar.classList.toggle('hidden', !!termoBusca);
    }
    
    // Busca no backend a página 1 usando o termo atual
    carregarProdutos(1, true);
}

function inicializarBuscaProdutos() {
    const inputBusca = document.getElementById('busca-produto');
    if (!inputBusca) return;
    
    // Desabilita autocomplete do navegador
    inputBusca.setAttribute('autocomplete', 'off');
    inputBusca.setAttribute('autocapitalize', 'off');
    inputBusca.setAttribute('autocorrect', 'off');
    inputBusca.setAttribute('spellcheck', 'false');
    
    // Muda o tipo para 'search'
    if (inputBusca.type !== 'search') {
        inputBusca.type = 'search';
    }
    
    let timeoutId;
    
    // Filtra com debounce
    inputBusca.addEventListener('input', (e) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            filtrarProdutos();
        }, 300);
    });
    
    // Filtra ao pressionar Enter
    inputBusca.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(timeoutId);
            filtrarProdutos();
        }
    });
}

window.limparBusca = function() {
    const inputBusca = document.getElementById('busca-produto');
    if (inputBusca) {
        inputBusca.value = '';
        filtrarProdutos();
        inputBusca.focus();
    }
};

/**
 * Função global para trocar a foto do slideshow
 * @param {HTMLElement} btn - O botão clicado (seta)
 * @param {number} direcao - 1 para próxima, -1 para anterior
 * @param {Event} event - Evento de clique
 */
window.trocarFoto = function(btn, direcao, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    const container = btn.closest('.slideshow-container');
    if (!container) return;

    const img = container.querySelector('.slideshow-img');
    const dots = container.querySelectorAll('.slideshow-dot');
    const fotos = JSON.parse(container.getAttribute('data-fotos') || '[]');
    let currentIndex = parseInt(container.getAttribute('data-current-index') || '0');

    if (fotos.length <= 1) return;

    // Calcula novo índice
    currentIndex += direcao;
    if (currentIndex >= fotos.length) currentIndex = 0;
    if (currentIndex < 0) currentIndex = fotos.length - 1;

    // Atualiza container
    container.setAttribute('data-current-index', currentIndex);

    // Atualiza imagem
    const novaFoto = fotos[currentIndex];
    if (novaFoto) {
        const rawPath = typeof novaFoto === 'string' ? novaFoto : (novaFoto.arquivo_path || novaFoto.url || '');
        if (rawPath.startsWith('http') || rawPath.startsWith('data:')) {
            img.src = rawPath;
        } else if (rawPath) {
            const arquivoPath = rawPath.replace(/^\//, '');
            const baseUrl = (CONFIG && CONFIG.URL_BASE_WEB ? CONFIG.URL_BASE_WEB : '').replace(/\/$/, '');
            img.src = `${baseUrl}/${arquivoPath}`;
        }
    }

    // Atualiza dots
    dots.forEach((dot, idx) => {
        if (idx === currentIndex) {
            dot.classList.add('bg-white', 'scale-125');
            dot.classList.remove('bg-white/50');
        } else {
            dot.classList.remove('bg-white', 'scale-125');
            dot.classList.add('bg-white/50');
        }
    });
};

/**
 * Renderiza o espaço da foto com suporte a slideshow se houver múltiplas fotos
 */
function renderizarEspacoFoto(produto) {
    let fotos = produto.fotos || [];
    
    // ✅ BUBBLE-UP: Se o produto pai não tem fotos, coleta fotos de todas as variações (grade)
    if (fotos.length === 0 && produto.variacoes && produto.variacoes.length > 0) {
        fotos = produto.variacoes.reduce((acc, v) => {
            if (v.fotos && v.fotos.length > 0) {
                // Adiciona fotos da variação ao array, evitando duplicatas por arquivo_path
                v.fotos.forEach(f => {
                    if (!acc.some(existente => existente.arquivo_path === f.arquivo_path)) {
                        acc.push(f);
                    }
                });
            }
            return acc;
        }, []);
    }

    let urlImagemPadrao = 'https://dummyimage.com/300x200/cccccc/ffffff.png&text=Sem+Imagem';
    
    if (fotos.length > 0 && fotos[0].arquivo_path) {
        const rawPath = fotos[0].arquivo_path;
        if (rawPath.startsWith('http') || rawPath.startsWith('data:')) {
            urlImagemPadrao = rawPath;
        } else {
            const arquivoPath = rawPath.replace(/^\//, '');
            const baseUrl = (CONFIG && CONFIG.URL_BASE_WEB ? CONFIG.URL_BASE_WEB : '').replace(/\/$/, '');
            urlImagemPadrao = `${baseUrl}/${arquivoPath}`;
        }
    } else if (produto.imagem_destaque) {
        urlImagemPadrao = produto.imagem_destaque;
    }

    const nomeEscapado = (produto.nome || 'Produto').replace(/"/g, '&quot;');

    if (fotos.length <= 1) {
        const fotosParaGaleria = (fotos && fotos.length > 0)
            ? fotos
            : [{ arquivo_path: urlImagemPadrao }];
        const fotosAttr = JSON.stringify(fotosParaGaleria).replace(/'/g, "&apos;");
        return `
            <div class="w-full h-48 bg-gray-50 flex items-center justify-center overflow-hidden p-2 cursor-zoom-in group" 
                 data-fotos='${fotosAttr}'
                 onclick="abrirGaleria(0, this.getAttribute('data-fotos'))">
                <img src="${urlImagemPadrao}" 
                     alt="${nomeEscapado}"
                     class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-105"
                     onerror="this.src='https://dummyimage.com/300x200/cccccc/ffffff.png&text=Erro'">
            </div>
        `;
    }

    // Gerar dots
    const dotsHtml = fotos.map((_, idx) => `
        <div class="slideshow-dot w-1.5 h-1.5 rounded-full transition-all duration-300 ${idx === 0 ? 'bg-white scale-125' : 'bg-white/50 shadow-sm'}"></div>
    `).join('');

    const fotosAttrMulti = JSON.stringify(fotos).replace(/'/g, "&apos;");

    return `
        <div class="slideshow-container relative w-full h-48 bg-gray-50 group overflow-hidden cursor-zoom-in" 
             data-fotos='${fotosAttrMulti}' 
             data-current-index="0"
             onclick="abrirGaleria(parseInt(this.getAttribute('data-current-index')), this.getAttribute('data-fotos'))">
            
            <!-- Imagem Principal -->
            <img src="${urlImagemPadrao}" 
                 alt="${nomeEscapado}"
                 class="slideshow-img w-full h-full object-contain p-2 transition-opacity duration-300"
                 onerror="this.src='https://dummyimage.com/300x200/cccccc/ffffff.png&text=Erro'">

            <!-- Setas de Navegação -->
            <button onclick="trocarFoto(this, -1, event)" 
                    class="absolute left-1 top-1/2 -translate-y-1/2 bg-black/30 hover:bg-black/50 text-white p-1 rounded-full opacity-80 group-hover:opacity-100 transition-opacity z-20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button onclick="trocarFoto(this, 1, event)" 
                    class="absolute right-1 top-1/2 -translate-y-1/2 bg-black/30 hover:bg-black/50 text-white p-1 rounded-full opacity-80 group-hover:opacity-100 transition-opacity z-20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <!-- Indicadores (Dots) -->
            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5 z-20">
                ${dotsHtml}
            </div>
        </div>
    `;
}

/**
 * Renderiza a tela amigável e moderna de Modo Implantação / Manutenção
 * quando o catálogo estiver desativado pelo lojista.
 */
function renderizarTelaManutencao(lojaInfo) {
    // 1. Esconde botões operacionais e busca do catálogo
    const idsParaEsconder = [
        'btn-abrir-carrinho',
        'btn-modo-divulgacao',
        'btn-meus-pedidos',
        'controles-paginacao',
        'controles-paginacao-rodape',
        'carregando-produtos'
    ];
    idsParaEsconder.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    const campoBusca = document.getElementById('busca-produto');
    if (campoBusca) {
        const wrapBusca = campoBusca.closest('.relative');
        if (wrapBusca) wrapBusca.style.display = 'none';
    }

    const main = document.querySelector('main.container');
    if (!main) return;

    const nomeLoja = lojaInfo.nome || lojaInfo.nome_loja || 'Nossa Loja';
    const msg = lojaInfo.mensagem_manutencao && lojaInfo.mensagem_manutencao.trim() !== ''
        ? lojaInfo.mensagem_manutencao.trim()
        : 'Estamos organizando nosso estoque, ajustando os detalhes e preparando produtos incríveis para você. Nosso catálogo online estará liberado para compras muito em breve!';

    const baseUrl = (CONFIG.URL_BASE_WEB || '').replace(/\/$/, '');
    const logoSrc = lojaInfo.logo_path 
        ? (lojaInfo.logo_path.startsWith('http') ? lojaInfo.logo_path : `${baseUrl}/${lojaInfo.logo_path.replace(/^\//, '')}`) 
        : null;

    const telefoneLimpo = (lojaInfo.telefone || '').replace(/\D/g, '');
    const whatsappUrl = telefoneLimpo 
        ? `https://wa.me/55${telefoneLimpo}?text=${encodeURIComponent(`Olá! Gostaria de mais informações sobre a loja ${nomeLoja}.`)}` 
        : null;

    const loginUrl = CONFIG.URL_API.replace('/index.php', '') + '/index.php/auth/login?loja=' + encodeURIComponent(CONFIG._slugDetectado || '');

    main.innerHTML = `
        <div class="max-w-2xl mx-auto my-6 sm:my-14 px-4">
            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-6 sm:p-12 text-center relative overflow-hidden">
                <!-- Barra de destaque com gradiente no topo -->
                <div class="absolute top-0 left-0 right-0 h-2.5 bg-gradient-to-r from-amber-400 via-orange-500 to-amber-500"></div>

                <!-- Logo ou Ícone da Loja -->
                <div class="mb-6 flex justify-center">
                    ${logoSrc ? `
                        <div class="p-3 bg-gray-50 border border-gray-100 rounded-2xl shadow-sm inline-block">
                            <img src="${logoSrc}" alt="${nomeLoja}" class="h-20 sm:h-24 w-auto object-contain max-w-[220px]" />
                        </div>
                    ` : `
                        <div class="w-20 h-20 sm:w-24 sm:h-24 bg-gradient-to-br from-amber-100 to-orange-100 text-amber-600 rounded-3xl flex items-center justify-center text-4xl sm:text-5xl shadow-inner border border-amber-200">
                            🚧
                        </div>
                    `}
                </div>

                <!-- Badge de Status -->
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-xs sm:text-sm font-bold uppercase tracking-wider mb-4 animate-pulse">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    Catálogo em Implantação
                </div>

                <!-- Título e Nome da Loja -->
                <h1 class="text-2xl sm:text-4xl font-extrabold text-gray-900 mb-2 tracking-tight">
                    ${nomeLoja}
                </h1>
                <p class="text-base sm:text-lg font-semibold text-amber-600 mb-6">
                    Acesso Externo Temporariamente Restrito
                </p>

                <!-- Mensagem Informativa -->
                <div class="bg-amber-50/40 rounded-2xl p-5 sm:p-6 text-gray-700 text-sm sm:text-base leading-relaxed mb-8 border border-amber-100">
                    <p class="italic">"${msg}"</p>
                </div>

                <!-- Botões de Ação -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    ${whatsappUrl ? `
                        <a href="${whatsappUrl}" target="_blank" rel="noopener noreferrer"
                           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl shadow-md hover:shadow-lg transition-all duration-200 active:scale-95">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.968.54 1.77.828 2.788.828 3.18 0 5.767-2.587 5.768-5.766.001-3.181-2.585-5.766-5.766-5.766zm9.969 5.828c0 5.518-4.482 10-10 10-1.748 0-3.385-.45-4.819-1.238l-4.181 1.094 1.115-4.08c-.896-1.503-1.415-3.255-1.415-5.126 0-5.518 4.482-10 10-10s10 4.482 10 10z"/>
                            </svg>
                            Falar no WhatsApp
                        </a>
                    ` : ''}

                    <a href="${loginUrl}"
                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl border border-gray-200 transition-all duration-200 active:scale-95">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        Área do Lojista (Entrar)
                    </a>
                </div>

                <p class="text-xs text-gray-400 mt-8">
                    &copy; ${new Date().getFullYear()} ${nomeLoja} &bull; Desenvolvido com Pulse
                </p>
            </div>
        </div>
    `;
}

/**
 * Renderiza uma tabela compacta com a prévia da grade (variações) do produto
 * Exibe apenas variações com estoque disponível (> 0)
 */
function renderizarPreviaGrade(produto) {
    if (!produto.variacoes || produto.variacoes.length === 0) return '';

    // Filtra apenas variações com estoque positivo disponível
    const variacoesDisponiveis = produto.variacoes.filter(v => parseFloat(v.estoque_atual || 0) > 0);
    if (variacoesDisponiveis.length === 0) return '';

    // Limita a 5 variações com estoque para não quebrar o layout
    const variacoesExibir = variacoesDisponiveis.slice(0, 5);
    const temMais = variacoesDisponiveis.length > 5;

    let html = `
    <div class="mt-3 mb-4 border-t border-gray-100 pt-2">
        <table class="w-full text-[10px] text-gray-600 font-mono">
            <thead>
                <tr class="text-gray-400 uppercase border-b border-gray-50 pb-1">
                    <th class="text-left font-bold pb-1">COR</th>
                    <th class="text-center font-bold pb-1">TAM</th>
                    <th class="text-center font-bold pb-1">EST</th>
                    <th class="text-right font-bold pb-1">VALOR</th>
                </tr>
            </thead>
            <tbody>
    `;

    variacoesExibir.forEach(v => {
        const preco = v.preco_promocional > 0 ? v.preco_promocional : v.preco_venda_sugerido;
        html += `
            <tr class="border-b border-gray-50 last:border-0">
                <td class="py-1 truncate max-w-[60px]" title="${v.cor || '-'}">${v.cor || '-'}</td>
                <td class="py-1 text-center font-bold">${v.tamanho || '-'}</td>
                <td class="py-1 text-center text-green-600 font-bold">${v.estoque_atual || 0}</td>
                <td class="py-1 text-right font-bold text-brand-600">${formatarMoeda(preco)}</td>
            </tr>
        `;
    });

    html += `
            </tbody>
        </table>
        ${temMais ? `
            <div class="text-[9px] text-center text-orange-500 font-bold mt-1 uppercase italic">
                + ${variacoesDisponiveis.length - 5} variações disponíveis
            </div>
        ` : ''}
    </div>
    `;

    return html;
}

function renderizarProdutos(listaProdutos, anexar = false) {
    console.log(`[App] 🎨 Renderizando ${listaProdutos.length} produtos (anexar: ${anexar})...`);
    const container = document.getElementById('catalogo-produtos');
    
    if (!container) {
        console.error('[App] Container de produtos não encontrado');
        return;
    }
    
    if (!anexar && listaProdutos.length === 0) {
        const termoBusca = document.getElementById('busca-produto')?.value?.trim() || '';
        if (termoBusca) {
            container.innerHTML = `
                <div class="col-span-full text-center py-16">
                    <svg class="h-16 w-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <p class="text-gray-600 font-medium">Nenhum produto encontrado</p>
                    <p class="text-sm text-gray-500 mt-2">Tente buscar com outro termo</p>
                </div>
            `;
        } else {
            container.innerHTML = `
                <div class="col-span-full text-center py-16">
                    <p class="text-gray-500 text-lg">Nenhum produto disponível no momento.</p>
                </div>
            `;
        }
        return;
    }
    
    const htmlCards = listaProdutos.map(produto => {
        const variacoesComEstoque = (produto.variacoes || []).filter(v => parseFloat(v.estoque_atual || 0) > 0);
        const temEstoqueVariacoes = produto.variacoes ? (variacoesComEstoque.length > 0) : (parseFloat(produto.estoque_atual || 0) > 0);

        const precoFinal = (produto.em_promocao && parseFloat(produto.preco_promocional) > 0)
            ? parseFloat(produto.preco_promocional)
            : parseFloat(produto.preco_final || produto.preco_venda_sugerido || 0);
        const precoOriginal = parseFloat(produto.preco_venda_sugerido || 0);
        const temDesconto = (produto.em_promocao && precoFinal < precoOriginal && precoOriginal > 0);

        return `
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 relative flex flex-col justify-between"
             data-produto-card="${produto.id}">
            
            <div class="badge-no-carrinho hidden absolute top-2 right-2 bg-emerald-500 text-white text-xs font-bold px-2.5 py-1 rounded-full z-10 shadow">
                ✓ No Carrinho
            </div>
            ${temDesconto ? `
                <div class="absolute top-2 left-2 bg-gradient-to-r from-red-600 to-rose-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full z-10 shadow">
                    OFERTA
                </div>
            ` : ''}
            
            <div>
                ${renderizarEspacoFoto(produto)}
                
                <div class="p-4">
                    <h3 class="text-base font-bold text-gray-800 mb-1 line-clamp-1" title="${produto.nome}">${produto.nome}</h3>
                    
                    ${produto.descricao ? `
                        <p class="text-xs text-gray-500 mb-2 line-clamp-1">${produto.descricao}</p>
                    ` : ''}
                    
                    ${renderizarPreviaGrade(produto)}
                </div>
            </div>
            
            <div class="p-4 pt-0">
                <div class="flex items-baseline justify-between mb-3">
                    <div>
                        <span class="text-xl font-black text-brand-600">
                            ${formatarMoeda(precoFinal)}
                        </span>
                        ${temDesconto ? `
                            <span class="text-xs text-gray-400 line-through ml-1.5">
                                ${formatarMoeda(precoOriginal)}
                            </span>
                        ` : ''}
                    </div>
                    
                    <span class="text-[11px] ${produto.possui_grade ? (temEstoqueVariacoes ? 'text-green-600' : 'text-red-600') : (produto.estoque_atual > 0 ? 'text-green-600' : 'text-red-600')} font-semibold">
                        ${!!produto.possui_grade ? (temEstoqueVariacoes ? 'Várias opções' : 'Sem opções') : (produto.estoque_atual > 0 ? `${formatarQuantidade(produto.estoque_atual, produto.venda_fracionada)} em estoque` : 'Sem estoque')}
                    </span>
                </div>
                
                ${!!produto.possui_grade ? `
                    <button onclick="window.abrirModalVariacoes('${produto.id}')"
                            class="w-full ${temEstoqueVariacoes ? 'bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 active:scale-[0.98] cursor-pointer' : 'bg-gray-400 opacity-60 cursor-not-allowed'} text-white font-bold py-2.5 px-4 rounded-xl flex items-center justify-center gap-2 shadow-sm transition-all duration-150"
                            ${!temEstoqueVariacoes ? 'disabled' : ''}>
                        🏷️ Escolher Opções
                    </button>
                ` : `
                    <button onclick="window.adicionarAoCarrinho1Click('${produto.id}')"
                            class="w-full bg-gradient-to-r from-brand-600 to-brand-500 hover:from-brand-700 hover:to-brand-600 active:scale-[0.98] text-white font-bold py-2.5 px-4 rounded-xl flex items-center justify-center gap-2 shadow-sm hover:shadow transition-all duration-150 cursor-pointer ${produto.estoque_atual <= 0 ? 'opacity-50 cursor-not-allowed' : ''}"
                            ${produto.estoque_atual <= 0 ? 'disabled' : ''}>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Adicionar ao Carrinho</span>
                    </button>
                `}
            </div>
            
            <!-- Overlay de Seleção Social -->
            <div class="social-check hidden">
                <div>
                   <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                   </svg>
                </div>
            </div>
        </div>
    `;
    }).join('');
    
    if (anexar) {
        container.insertAdjacentHTML('beforeend', htmlCards);
    } else {
        container.innerHTML = htmlCards;
    }

    container.querySelectorAll('[data-produto-card]:not([data-click-bound])').forEach(card => {
        card.setAttribute('data-click-bound', 'true');
        card.addEventListener('click', (e) => {
            if (document.body.classList.contains('modo-social')) {
                e.preventDefault();
                e.stopPropagation();
                const id = card.getAttribute('data-produto-card');
                const produto = produtos.find(p => String(p.id) === String(id));
                if (produto) {
                    toggleSelecaoProduto(produto);
                }
            }
        });
    });
}

// ==========================================================================
// MODAL DE QUANTIDADE
// ==========================================================================

window.abrirModalQuantidade = function(produtoId) {
     // ✅ CORREÇÃO: Buscar por 'id'
    const produto = produtos.find(p => p.id === produtoId);
    
    if (!produto) {
        alert('Produto não encontrado');
        return;
    }
    
    const modal = document.getElementById('modal-quantidade');
    const nomeProduto = document.getElementById('nome-produto-modal');
    const precoProduto = document.getElementById('preco-produto-modal');
    const inputQtd = document.getElementById('input-quantidade');
    const btnConfirmar = document.getElementById('btn-confirmar-adicionar');
    
    nomeProduto.textContent = produto.nome;
    // ✅ CORREÇÃO: Usando 'preco_venda_sugerido'
    precoProduto.textContent = formatarMoeda(produto.preco_venda_sugerido);
    
    // Configura input para fracionados
    const permiteFracionado = !!produto.venda_fracionada;
    inputQtd.value = permiteFracionado ? "1,000" : "1";
    inputQtd.step = permiteFracionado ? "0.001" : "1";
     // ✅ CORREÇÃO: Usando 'estoque_atual'
    inputQtd.max = produto.estoque_atual;
    
    btnConfirmar.onclick = () => {
        const valorRaw = inputQtd.value.replace(',', '.');
        const quantidade = parseFloat(valorRaw);
        
        if (quantidade > 0 && quantidade <= parseFloat(produto.estoque_atual)) {
            // Preparar produto com imagem para adicionar ao carrinho
            const produtoComImagem = {
                ...produto,
                imagem: produto.fotos && produto.fotos.length > 0 && produto.fotos[0].arquivo_path
                    ? (() => {
                        const arquivoPath = produto.fotos[0].arquivo_path.replace(/^\//, '');
                        const baseUrl = CONFIG.URL_BASE_WEB.replace(/\/$/, '');
                        return `${baseUrl}/${arquivoPath}`;
                    })()
                    : 'https://dummyimage.com/300x200/cccccc/ffffff.png&text=Sem+Imagem'
            };
            
            if (adicionarAoCarrinho(produtoComImagem, quantidade)) {
                atualizarBadgeProduto(produtoId, true);
                atualizarBadgeCarrinho();
                fecharModal('modal-quantidade');
                
                // Feedback visual
                mostrarNotificacao(`${produto.nome} adicionado ao carrinho!`);
            }
        } else {
            alert(`Quantidade inválida. Máximo disponível: ${formatarQuantidade(produto.estoque_atual, permiteFracionado)}`);
        }
    };
    
    abrirModal('modal-quantidade');
};

// ==========================================================================
// MODAL DO CARRINHO
// ==========================================================================

window.abrirCarrinho = function() {
    renderizarCarrinho();
    abrirModal('modal-carrinho');
};

// ==========================================================================
// (NOVO) FUNÇÃO POPULAR FORMAS DE PAGAMENTO
// ==========================================================================

/**
 * Popula o dropdown de Formas de Pagamento
 * @param {Array} formas - Array de objetos {id, nome, tipo}
 */
/**
 * Filtra e popula as formas de pagamento disponíveis para vendas online
 * 
 * REGRAS IMPORTANTES (NÃO ALTERAR SEM REVISÃO):
 * 1. DINHEIRO: SEMPRE removido (não disponível em vendas online)
 * 2. Formas que REQUEREM api_de_pagamento=true:
 *    - BOLETO
 *    - CARTAO_CREDITO
 *    - CARTAO_DEBITO
 *    - CARTAO (genérico)
 *    - PIX (dinâmico - quando tipo é apenas "PIX", não "PIX_ESTATICO")
 * 3. Formas que SEMPRE aparecem (não requerem gateway):
 *    - PIX_ESTATICO (QR Code fixo)
 *    - PAGAR_AO_ENTREGADOR
 * 4. Outras formas: REMOVIDAS por padrão
 */
function popularFormasPagamento(formas) {
    const select = document.getElementById('forma-pagamento');
    if (!select) {
        console.error('[App] ❌ Select de forma de pagamento não encontrado!');
        return;
    }

    select.innerHTML = ''; // Limpa "Carregando..."

    if (!formas || formas.length === 0) {
        select.options[0] = new Option('Nenhuma forma de pgto.', '');
        select.disabled = true;
        return;
    }
    
    // ========================================================================
    // VALIDAÇÃO CRÍTICA: Verificar se GATEWAY_CONFIG está disponível
    // ========================================================================
    if (!window.GATEWAY_CONFIG) {
        console.warn('[App] ⚠️ GATEWAY_CONFIG não está disponível! Tentando carregar...');
        // Tenta carregar se não estiver disponível
        carregarConfigLoja().then(config => {
            window.GATEWAY_CONFIG = config;
            console.log('[App] ✅ GATEWAY_CONFIG carregado:', config);
            // Recarrega as formas de pagamento após carregar a config
            popularFormasPagamento(formas);
        }).catch(error => {
            console.error('[App] ❌ Erro ao carregar GATEWAY_CONFIG:', error);
        });
        return;
    }
    
    // ========================================================================
    // LOGS PARA DEBUG
    // ========================================================================
    console.log('[App] 🔍 ========== FILTRAGEM DE FORMAS DE PAGAMENTO ==========');
    console.log('[App] 🔍 Total de formas recebidas:', formas.length);
    console.log('[App] 🔍 Formas recebidas:', formas.map(f => ({ nome: f.nome, tipo: f.tipo })));
    console.log('[App] 🔍 GATEWAY_CONFIG:', window.GATEWAY_CONFIG);
    
    // Verificar se api_de_pagamento está habilitado
    const gatewayHabilitado = window.GATEWAY_CONFIG && 
                              window.GATEWAY_CONFIG.habilitado === true;
    console.log('[App] 🔍 api_de_pagamento habilitado?', gatewayHabilitado);
    
    // ========================================================================
    // LISTA EXPLÍCITA DE FORMAS QUE REQUEREM GATEWAY
    // IMPORTANTE: Estas formas SÓ aparecem se api_de_pagamento = true
    // ========================================================================
    const FORMAS_QUE_REQUEREM_GATEWAY = [
        'BOLETO',
        'CARTAO_CREDITO',
        'CARTAO_DEBITO',
        'CARTAO', // Genérico (pode ser crédito ou débito)
        'PIX' // PIX dinâmico (não confundir com PIX_ESTATICO)
    ];
    
    // ========================================================================
    // FILTRAGEM DAS FORMAS DE PAGAMENTO
    // ========================================================================
    const formasFiltradas = formas.filter(forma => {
        const tipo = (forma.tipo || '').toUpperCase().trim();
        const nome = forma.nome || 'Sem nome';
        
        console.log(`[App] 🔍 Analisando: "${nome}" (tipo: "${tipo}")`);
        
        // ====================================================================
        // REGRA 1: DINHEIRO sempre removido
        // ====================================================================
        if (tipo === 'DINHEIRO') {
            console.log(`[App] ❌ REMOVIDO: ${nome} (DINHEIRO não disponível em vendas online)`);
            return false;
        }
        
        // ====================================================================
        // REGRA 2: Formas que requerem gateway
        // ====================================================================
        const requerGateway = FORMAS_QUE_REQUEREM_GATEWAY.includes(tipo);
        if (requerGateway) {
            if (!gatewayHabilitado) {
                console.log(`[App] ❌ REMOVIDO: ${nome} (${tipo} requer api_de_pagamento=true, mas está desabilitado)`);
                return false;
            }
            console.log(`[App] ✅ MANTIDO: ${nome} (${tipo} - gateway habilitado)`);
            return true;
        }
        
        // ====================================================================
        // REGRA 3: PIX_ESTATICO (chave da loja)
        // Bloqueado se a loja tiver Mercado Pago conectado e sem cota do admin
        // ====================================================================
        if (tipo === 'PIX_ESTATICO') {
            if (window.GATEWAY_CONFIG && window.GATEWAY_CONFIG.pix_estatico_bloqueado === true) {
                console.log(`[App] ❌ REMOVIDO: ${nome} (PIX_ESTATICO bloqueado pelo Admin da SaaS - Mercado Pago ativo sem cota)`);
                return false;
            }
            console.log(`[App] ✅ MANTIDO: ${nome} (PIX_ESTATICO disponível)`);
            return true;
        }
        
        // ====================================================================
        // REGRA 4: PAGAR_AO_ENTREGADOR sempre disponível
        // ====================================================================
        if (tipo === 'PAGAR_AO_ENTREGADOR') {
            console.log(`[App] ✅ MANTIDO: ${nome} (PAGAR_AO_ENTREGADOR sempre disponível)`);
            return true;
        }
        
        // ====================================================================
        // REGRA 5: Outras formas são removidas por padrão
        // ====================================================================
        console.log(`[App] ❌ REMOVIDO: ${nome} (tipo "${tipo}" não permitido em vendas online)`);
        return false;
    });
    
    
    // ========================================================================
    // RESULTADO DA FILTRAGEM
    // ========================================================================
    console.log('[App] ✅ ========== RESULTADO DA FILTRAGEM ==========');
    console.log('[App] ✅ Total de formas filtradas:', formasFiltradas.length);
    console.log('[App] ✅ Formas disponíveis:', formasFiltradas.map(f => ({ nome: f.nome, tipo: f.tipo })));
    console.log('[App] ✅ ============================================');
    
    if (formasFiltradas.length === 0) {
        select.options[0] = new Option('Nenhuma forma de pgto. disponível', '');
        select.disabled = true;
        console.warn('[App] ⚠️ Nenhuma forma de pagamento disponível após filtragem!');
        return;
    }
    
    select.disabled = false;
    select.options[0] = new Option('Selecione o pagamento...', '');
    
    // Popula o select com as formas filtradas
    formasFiltradas.forEach(forma => {
        if (forma.id && forma.nome) {
            const option = new Option(forma.nome, forma.id);
            // Armazena o tipo no atributo data-tipo para facilitar acesso
            option.setAttribute('data-tipo', forma.tipo || '');
            select.options[select.options.length] = option;
        }
    });
    
    // Armazena formas de pagamento filtradas globalmente
    formasPagamento = formasFiltradas;
    // Disponibiliza globalmente para validação em order.js
    window.formasPagamento = formasFiltradas;
    
    // Adiciona listener para controlar parcelas baseado na forma de pagamento
    // Remove listener anterior se existir para evitar duplicatas
    const oldSelect = select;
    const novoSelect = oldSelect.cloneNode(true);
    oldSelect.parentNode.replaceChild(novoSelect, oldSelect);
    novoSelect.addEventListener('change', controlarParcelasPorFormaPagamento);
}

/**
 * Controla o campo de parcelas baseado na forma de pagamento selecionada
 * Se for DINHEIRO ou PIX, desabilita parcelamento e força "À vista"
 */
function controlarParcelasPorFormaPagamento() {
    const selectFormaPagamento = document.getElementById('forma-pagamento');
    const selectParcelas = document.getElementById('numero-parcelas');
    const campoDataPrimeiroPagamento = document.getElementById('campo-data-primeiro-pagamento');
    const campoIntervaloParcelas = document.getElementById('campo-intervalo-parcelas');
    
    if (!selectFormaPagamento || !selectParcelas) return;
    
    const formaPagamentoId = selectFormaPagamento.value;
    if (!formaPagamentoId) {
        // Se nenhuma forma foi selecionada, habilita parcelas normalmente
        selectParcelas.disabled = false;
        return;
    }
    
    // Busca a forma de pagamento selecionada
    const formaSelecionada = formasPagamento.find(f => f.id === formaPagamentoId);
    if (!formaSelecionada) return;
    
    const tipo = (formaSelecionada.tipo || '').toUpperCase().trim();
    const nome = (formaSelecionada.nome || '').toLowerCase();
    const textoOption = selectFormaPagamento?.selectedOptions?.[0]?.text?.toLowerCase() || '';

    const isDebito = (tipo === 'CARTAO_DEBITO') ||
                     nome.includes('débito') ||
                     nome.includes('debito') ||
                     textoOption.includes('débito') ||
                     textoOption.includes('debito');
    
    // Se for DINHEIRO, PIX, PIX ESTATICO ou CARTAO_DEBITO, desabilita parcelamento
    if (tipo === 'DINHEIRO' || tipo === 'PIX' || tipo === 'PIX_ESTATICO' || isDebito) {
        // SEMPRE força para "À vista" - IMPORTANTE: fazer ANTES de desabilitar
        selectParcelas.value = '1';
        // Dispara evento change para atualizar campos relacionados
        selectParcelas.dispatchEvent(new Event('change', { bubbles: true }));
        selectParcelas.disabled = true;
        
        // Oculta campos de parcelamento e limpa valores
        if (campoDataPrimeiroPagamento) {
            campoDataPrimeiroPagamento.classList.add('hidden');
            campoDataPrimeiroPagamento.value = '';
        }
        if (campoIntervaloParcelas) {
            campoIntervaloParcelas.classList.add('hidden');
        }
        
        console.log('[App] 🔒 Parcelamento desabilitado para forma de pagamento:', formaSelecionada.nome, '(isDebito:', isDebito, ')');
    } else {
        // Habilita parcelamento para outras formas
        selectParcelas.disabled = false;
        
        // Mostra/oculta campos de parcelamento baseado no número de parcelas
        const numeroParcelas = parseInt(selectParcelas.value, 10) || 1;
        if (numeroParcelas > 1) {
            if (campoDataPrimeiroPagamento) {
                campoDataPrimeiroPagamento.classList.remove('hidden');
            }
            if (campoIntervaloParcelas) {
                campoIntervaloParcelas.classList.remove('hidden');
            }
        } else {
            if (campoDataPrimeiroPagamento) {
                campoDataPrimeiroPagamento.classList.add('hidden');
            }
            if (campoIntervaloParcelas) {
                campoIntervaloParcelas.classList.add('hidden');
            }
        }
        
        console.log('[App] ✅ Parcelamento habilitado para forma de pagamento:', tipo);
    }
}

// ==========================================================================
// (MODIFICADO) MODAL DE PEDIDO
// ==========================================================================

// Tornar a função async
window.abrirModalPedido = async function() {
    fecharModal('modal-carrinho');
    
    // Limpar formulário
    document.getElementById('form-cliente-pedido').reset();
    clienteAtual = null;
    colaboradorAtual = null;
    
    // Resetar campos
    document.getElementById('info-cliente').classList.add('hidden');
    document.getElementById('info-vendedor').classList.add('hidden');
    
    // Resetar estado do botão e campo vendedor
    const campoVendedor = document.getElementById('campo-vendedor-cpf');
    if (campoVendedor) campoVendedor.classList.add('hidden');
    
    const btnConfirmar = document.getElementById('btn-confirmar-pedido');
    if (btnConfirmar) btnConfirmar.disabled = true;
    
    const msgHabilitar = document.getElementById('msg-habilitar-botao');
    if (msgHabilitar) msgHabilitar.classList.remove('hidden');
    
    // Abrir o modal primeiro
    abrirModal('modal-cliente-pedido');
    
    // ===============================================
    // CORREÇÃO: Carregar formas de pagamento
    try {
        console.log('[App] 💳 Carregando formas de pagamento...');
        // Seta "Carregando..." manually caso o HTML mude
        const selectPgto = document.getElementById('forma-pagamento');
        if (selectPgto) {
            selectPgto.innerHTML = '<option value="">Carregando...</option>';
            selectPgto.disabled = true;
        }

        const formas = await carregarFormasPagamento(CONFIG.ID_USUARIO_LOJA);
        popularFormasPagamento(formas);
        console.log('[App] ✅ Formas de pagamento carregadas:', formas.length);
        // Verifica se já há uma forma selecionada (após popular)
        // Usa setTimeout para garantir que o DOM foi atualizado
        setTimeout(() => {
            controlarParcelasPorFormaPagamento();
            // Força novamente após um pequeno delay para garantir
            setTimeout(() => controlarParcelasPorFormaPagamento(), 50);
        }, 100);
    } catch (error) {
        console.error('[App] ❌ Erro ao carregar formas de pagamento:', error);
        popularFormasPagamento([]); // Popula com erro
    }
    // ===============================================

    // Ajusta link do cadastro de cliente para a base correta
    const linkCadastro = document.getElementById('link-cadastro-cliente');
    if (linkCadastro) {
        linkCadastro.onclick = (e) => {
            e.preventDefault();
            const base = (CONFIG.URL_BASE_WEB || '').replace(/\/$/, '');
            // Sem rota pública de cliente; direciona para login do painel
            const url = `${base}/index.php/auth/login`;
            window.open(url, '_blank');
        };
    }
};

// ==========================================================================
// (NOVO) FUNÇÃO PARA ALTERNAR CAMPO VENDEDOR
// ==========================================================================

/**
 * Mostra ou esconde o campo de busca do vendedor
 */
function alternarCampoVendedor() {
    const campoVendedor = document.getElementById('campo-vendedor-cpf');
    const radioVendedor = document.getElementById('tipo_venda_vendedor');
    
    if (!campoVendedor || !radioVendedor) return;

    if (radioVendedor.checked) {
        campoVendedor.classList.remove('hidden');
    } else {
        campoVendedor.classList.add('hidden');
        // Limpa os campos do vendedor ao esconder
        const inputCpf = document.getElementById('vendedor_cpf_busca');
        const inputId = document.getElementById('colaborador_vendedor_id');
        const infoBox = document.getElementById('info-vendedor');
        
        if (inputCpf) inputCpf.value = '';
        if (inputId) inputId.value = '';
        if (infoBox) infoBox.classList.add('hidden');
        
        // Limpa a variável global
        colaboradorAtual = null;
    }
}

// ==========================================================================
// BUSCA DE CLIENTE E COLABORADOR
// ==========================================================================

window.buscarCliente = async function() {
    const cpfInput = document.getElementById('cliente-cpf-busca'); // CORRIGIDO: ID correto do HTML
    if (!cpfInput) {
        alert('Campo de CPF do cliente não encontrado');
        return;
    }
    const cpf = cpfInput.value.replace(/[^\d]/g, '');
    
    if (!validarCPF(cpf)) {
        alert('CPF inválido');
        return;
    }
    
    try {
        const response = await fetch(`${API_ENDPOINTS.CLIENTE_BUSCA_CPF}?cpf=${cpf}&usuario_id=${CONFIG.ID_USUARIO_LOJA}`);
        
        if (!response.ok) {
            throw new Error(`Status ${response.status}`);
        }

        const data = await response.json();

        // API pode retornar {existe: bool, cliente: {...}}
        const existe = data.existe ?? !!data.id ?? !!data.cliente;
        const cliente = data.cliente || data;

        if (!existe || !cliente) {
            // Abre modal de cadastro e preenche o CPF já digitado
            if (typeof abrirModal === 'function') {
                const cpfInput = document.getElementById('cliente-cpf-busca');
                const cpfCadastro = document.getElementById('cadastro-cpf');
                if (cpfInput && cpfCadastro) {
                    cpfCadastro.value = cpfInput.value;
                }
                abrirModal('modal-cadastro-cliente');
            }
            clienteAtual = null;
            const inputClienteId = document.getElementById('cliente_id');
            if (inputClienteId) inputClienteId.value = '';
            document.getElementById('info-cliente').classList.add('hidden');

            const btnConfirmar = document.getElementById('btn-confirmar-pedido');
            if (btnConfirmar) btnConfirmar.disabled = true;
            const msgHabilitar = document.getElementById('msg-habilitar-botao');
            if (msgHabilitar) msgHabilitar.classList.remove('hidden');
            return;
        }

        clienteAtual = cliente;
        window.clienteAtual = cliente;

        // Tratar diferentes estruturas de resposta possíveis
        const nomeCliente = cliente.nome_completo || 
                          cliente.nome || 
                          'Cliente encontrado';
        
        document.getElementById('nome-cliente-info').textContent = nomeCliente;
        document.getElementById('info-cliente').classList.remove('hidden');
        
        // CORREÇÃO: Preencher campos de endereço para cálculo de frete
        const camposEnd = {
            'cadastro-cep': cliente.cep,
            'cadastro-bairro': cliente.bairro,
            'cadastro-cidade': cliente.cidade,
            'cadastro-logradouro': cliente.logradouro,
            'cadastro-numero': cliente.numero
        };
        
        for (const [id, valor] of Object.entries(camposEnd)) {
            const el = document.getElementById(id);
            if (el && valor) {
                el.value = valor;
            }
        }

        // Se houver endereço, atualizar o frete automaticamente
        if (cliente.cep || cliente.bairro || cliente.cidade) {
            window.atualizarFrete();
        }

        // Armazenar o ID do cliente no input hidden
        const clienteId = cliente.id;
        const inputClienteId = document.getElementById('cliente_id');
        if (inputClienteId && clienteId) {
            inputClienteId.value = clienteId;
        }
        
        console.log('[App] ✅ Cliente encontrado e endereço preenchido:', clienteAtual);

        // CORREÇÃO: Habilitar o botão de confirmar
        const btnConfirmar = document.getElementById('btn-confirmar-pedido');
        if (btnConfirmar) {
            btnConfirmar.disabled = false;
        }
        const msgHabilitar = document.getElementById('msg-habilitar-botao');
        if (msgHabilitar) {
            msgHabilitar.classList.add('hidden');
        }

    } catch (error) {
        console.error('[App] Erro ao buscar cliente:', error);
        alert('Erro ao buscar cliente');
        clienteAtual = null;
        const inputClienteId = document.getElementById('cliente_id');
        if (inputClienteId) inputClienteId.value = '';
        document.getElementById('info-cliente').classList.add('hidden');
        const btnConfirmar = document.getElementById('btn-confirmar-pedido');
        if (btnConfirmar) btnConfirmar.disabled = true;
        const msgHabilitar = document.getElementById('msg-habilitar-botao');
        if (msgHabilitar) msgHabilitar.classList.remove('hidden');
    }
};

// ==========================================================================
// (MODIFICADO) BUSCAR VENDEDOR
// ==========================================================================
window.buscarVendedor = async function() {
    const cpfInput = document.getElementById('vendedor_cpf_busca');
    if (!cpfInput) {
        alert('Campo de CPF do vendedor não encontrado');
        return;
    }
    const cpf = cpfInput.value.replace(/[^\d]/g, '');
    
    if (!validarCPF(cpf)) {
        alert('CPF inválido');
        return;
    }
    
    try {
        const response = await fetch(`${API_ENDPOINTS.COLABORADOR_BUSCA_CPF}?cpf=${cpf}&usuario_id=${CONFIG.ID_USUARIO_LOJA}`);
        
        if (response.ok) {
            // A resposta é {existe: true, colaborador: {id: '...', nome_completo: '...'}}
            const respostaVendedor = await response.json(); 
            
            // ===============================================
            // CORREÇÃO: Acessar o objeto 'colaborador' dentro da resposta
            // ===============================================
            if (respostaVendedor.existe && respostaVendedor.colaborador) {
                colaboradorAtual = respostaVendedor.colaborador; // Armazena SÓ o colaborador
                
                // Usar 'nome_completo' do objeto aninhado
                document.getElementById('nome-vendedor-info').textContent = colaboradorAtual.nome_completo; 
                document.getElementById('info-vendedor').classList.remove('hidden');
                
                console.log('[App] ✅ Vendedor encontrado:', respostaVendedor);
            } else {
                 alert('Vendedor não encontrado (dados incompletos na resposta).');
                 colaboradorAtual = null;
                 document.getElementById('info-vendedor').classList.add('hidden');
            }
            // ===============================================

        } else {
            alert('Vendedor não encontrado');
            colaboradorAtual = null;
            document.getElementById('info-vendedor').classList.add('hidden');
            document.getElementById('nome-vendedor-info').textContent = '';
        }
    } catch (error) {
        console.error('[App] Erro ao buscar vendedor:', error);
        alert('Erro ao buscar vendedor');
    }
};

// ==========================================================================
// CADASTRAR CLIENTE (MODAL PÚBLICO)
// ==========================================================================

window.cadastrarClienteModal = async function() {
    try {
        // Coletar dados do formulário
        const nomeCompleto = document.getElementById('cadastro-nome')?.value.trim();
        const cpf = document.getElementById('cadastro-cpf')?.value.trim();
        const telefone = document.getElementById('cadastro-telefone')?.value.trim();
        const email = document.getElementById('cadastro-email')?.value.trim();
        const senha = document.getElementById('cadastro-senha')?.value;
        const senhaConfirm = document.getElementById('cadastro-senha-confirm')?.value;
        const logradouro = document.getElementById('cadastro-logradouro')?.value.trim();
        const numero = document.getElementById('cadastro-numero')?.value.trim();
        const bairro = document.getElementById('cadastro-bairro')?.value.trim();
        const cidade = document.getElementById('cadastro-cidade')?.value.trim();
        const estado = document.getElementById('cadastro-estado')?.value.trim();
        const cep = document.getElementById('cadastro-cep')?.value.trim();

        // Validações básicas
        if (!nomeCompleto) {
            alert('Por favor, informe o nome completo.');
            return;
        }

        if (!cpf || !validarCPF(cpf)) {
            alert('Por favor, informe um CPF válido.');
            return;
        }

        if (!telefone) {
            alert('Por favor, informe o telefone.');
            return;
        }

        if (!senha || senha.length < 4) {
            alert('A senha deve ter no mínimo 4 caracteres.');
            return;
        }

        if (senha !== senhaConfirm) {
            alert('As senhas não coincidem.');
            return;
        }

        if (!logradouro) {
            alert('Por favor, informe o logradouro.');
            return;
        }

        if (!numero) {
            alert('Por favor, informe o número do endereço.');
            return;
        }

        if (!bairro) {
            alert('Por favor, informe o bairro.');
            return;
        }

        if (!cidade) {
            alert('Por favor, informe a cidade.');
            return;
        }

        if (!estado) {
            alert('Por favor, informe o estado.');
            return;
        }

        // Preparar dados para envio
        const dadosCliente = {
            nome_completo: nomeCompleto,
            cpf: cpf,
            telefone: telefone,
            email: email || null,
            senha: senha,
            endereco_logradouro: logradouro,
            endereco_numero: numero,
            endereco_bairro: bairro,
            endereco_cidade: cidade,
            endereco_estado: estado.toUpperCase(),
            endereco_cep: cep || null,
            usuario_id: CONFIG.ID_USUARIO_LOJA
        };

        console.log('[App] 📝 Cadastrando cliente:', dadosCliente);

        // Desabilitar botão durante o cadastro
        const btnCadastrar = document.querySelector('#modal-cadastro-cliente button[onclick*="cadastrarClienteModal"]');
        if (btnCadastrar) {
            btnCadastrar.disabled = true;
            btnCadastrar.textContent = 'Cadastrando...';
        }

        // Chamar função de cadastro
        const clienteCadastrado = await cadastrarCliente(dadosCliente);

        console.log('[App] ✅ Cliente cadastrado com sucesso:', clienteCadastrado);

        // Fechar modal
        fecharModal('modal-cadastro-cliente');

        // Preencher cliente no pedido
        clienteAtual = clienteCadastrado;
        window.clienteAtual = clienteCadastrado;
        const inputClienteId = document.getElementById('cliente_id');
        if (inputClienteId && clienteCadastrado.id) {
            inputClienteId.value = clienteCadastrado.id;
        }

        // Atualizar interface
        const nomeCliente = clienteCadastrado.nome_completo || clienteCadastrado.nome || 'Cliente cadastrado';
        document.getElementById('nome-cliente-info').textContent = nomeCliente;
        document.getElementById('info-cliente').classList.remove('hidden');

        // Habilitar botão de confirmar
        const btnConfirmar = document.getElementById('btn-confirmar-pedido');
        if (btnConfirmar) {
            btnConfirmar.disabled = false;
        }
        const msgHabilitar = document.getElementById('msg-habilitar-botao');
        if (msgHabilitar) {
            msgHabilitar.classList.add('hidden');
        }

        // Limpar formulário
        document.getElementById('form-cliente-pedido')?.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]').forEach(input => {
            if (input.id && input.id.startsWith('cadastro-')) {
                input.value = '';
            }
        });

        alert('✅ Cliente cadastrado com sucesso! Você pode finalizar o pedido.');

    } catch (error) {
        console.error('[App] ❌ Erro ao cadastrar cliente:', error);
        alert('Erro ao cadastrar cliente: ' + error.message);

        // Reabilitar botão
        const btnCadastrar = document.querySelector('#modal-cadastro-cliente button[onclick*="cadastrarClienteModal"]');
        if (btnCadastrar) {
            btnCadastrar.disabled = false;
            btnCadastrar.textContent = 'Cadastrar e usar no pedido';
        }
    }
};

// ==========================================================================
// FINALIZAR PEDIDO
// ==========================================================================

window.confirmarPedido = async function() {
    // Verificar se cliente foi selecionado - flexível com diferentes estruturas
    const clienteId = clienteAtual?.id || clienteAtual?.cliente?.id || document.getElementById('cliente_id')?.value;
    
    if (!clienteId) {
        alert('Por favor, busque o CPF do cliente antes de finalizar o pedido');
        return;
    }

    // ===============================================
    // (NOVO) Validação da Forma de Pagamento
    // ===============================================
    const formaPagamentoId = document.getElementById('forma-pagamento')?.value;
    if (!formaPagamentoId) {
        alert('Por favor, selecione uma forma de pagamento.');
        return;
    }
    
    // Verifica se a forma de pagamento permite parcelamento antes de pegar o valor
    const formaPagamentoSelecionada = formasPagamento.find(fp => fp.id === formaPagamentoId);
    const tipoFormaPagamento = (formaPagamentoSelecionada?.tipo || '').toUpperCase().trim();
    const nomeFormaPagamento = (formaPagamentoSelecionada?.nome || '').toLowerCase();
    const textoOptionPgto    = document.getElementById('forma-pagamento')?.selectedOptions?.[0]?.text?.toLowerCase() || '';

    const isDebito           = (tipoFormaPagamento === 'CARTAO_DEBITO') ||
                               nomeFormaPagamento.includes('débito') ||
                               nomeFormaPagamento.includes('debito') ||
                               textoOptionPgto.includes('débito') ||
                               textoOptionPgto.includes('debito');

    const permiteParcelamento = !['DINHEIRO', 'PIX', 'PIX_ESTATICO', 'PAGAR_AO_ENTREGADOR'].includes(tipoFormaPagamento) && !isDebito;
    
    // ===============================================
    // ✅ CHECKOUT TRANSPARENTE — Cartão via Mercado Pago
    // Se o gateway MP está ativo e a forma é cartão, exibe o CardForm
    // antes de prosseguir para que o token seja gerado pelo SDK.
    // ===============================================
    const isCartaoGateway = ['CARTAO_CREDITO', 'CARTAO_DEBITO', 'CARTAO'].includes(tipoFormaPagamento) ||
                            nomeFormaPagamento.includes('cartão') || nomeFormaPagamento.includes('cartao') ||
                            textoOptionPgto.includes('cartão') || textoOptionPgto.includes('cartao');
    const tipoCartao      = isDebito ? 'debit_card' : 'credit_card';
    const gatewayMpAtivo  = window.GATEWAY_CONFIG?.gateway === 'mercadopago' && window.GATEWAY_CONFIG?.habilitado;

    if (isCartaoGateway && gatewayMpAtivo) {
        // Calcula o valor total para o CardForm
        const carrinhoAtual = getCarrinho();
        const valorTotal    = carrinhoAtual.reduce((acc, item) =>
            acc + ((item.preco_venda_sugerido || item.preco || 0) * (item.quantidade || 1)), 0
        );

        try {
            const { inicializarCardForm, gerarHtmlFormCartao, destruirCardForm, inicializarWalletBrick, destruirWalletBrick } = await import('./mp-card-form.js?v=20260912_1');

            // Cria modal do CardForm se ainda não existir
            let modalCartao = document.getElementById('modal-mp-cartao');
            if (!modalCartao) {
                modalCartao = document.createElement('div');
                modalCartao.id = 'modal-mp-cartao';
                modalCartao.style.cssText = [
                    'position:fixed;inset:0;z-index:99998',
                    'background:rgba(0,0,0,0.75)',
                    'display:flex;align-items:center;justify-content:center;padding:16px',
                ].join(';');
                document.body.appendChild(modalCartao);

                // Estilos do formulário
                if (!document.getElementById('mp-card-form-styles')) {
                    const style = document.createElement('style');
                    style.id = 'mp-card-form-styles';
                    style.textContent = `
                        .mp-card-form-container{display:flex;flex-direction:column;gap:14px}
                        .mp-field-group{display:flex;flex-direction:column;gap:5px}
                        .mp-field-row{display:flex;gap:12px}
                        .mp-field-row .mp-field-group{flex:1}
                        .mp-label{font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.5px}
                        .mp-sdk-field,.mp-select,.mp-input{height:44px;border:1.5px solid #cbd5e1;border-radius:8px;padding:0 12px;font-size:14px;width:100%;box-sizing:border-box;background:#f8fafc;color:#1e293b;transition:border-color .2s}
                        .mp-sdk-field{padding:0;position:relative;overflow:hidden;display:flex;align-items:center}
                        .mp-sdk-field iframe{width:100%!important;height:100%!important;border:none!important;background:transparent!important}
                        .mp-sdk-field:focus-within{border-color:#7c3aed;background:#fff}
                        .mp-input:focus,.mp-select:focus{border-color:#7c3aed;outline:none;background:#fff}
                        .mp-error-msg{color:#ef4444;font-size:13px;min-height:18px;margin:0;font-weight:600}
                        .mp-field-error{border-color:#ef4444!important;background:#fff1f2!important;box-shadow:0 0 0 3px rgba(239,68,68,0.15)!important}
                        .mp-btn-pay{background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;border:none;border-radius:10px;padding:14px;font-size:15px;font-weight:700;cursor:pointer;width:100%;transition:opacity .2s;margin-top:4px}
                        .mp-btn-pay:hover{opacity:.92}
                        .mp-btn-pay:disabled{opacity:.55;cursor:not-allowed}
                    `;
                    document.head.appendChild(style);
                }
            }

            const dadosClienteParaCartao = clienteAtual?.cliente || clienteAtual;
            const tituloModal = isDebito ? '💳 Pagamento com Cartão de Débito' : '💳 Pagamento com Cartão de Crédito';
            const subTituloModal = isDebito 
                ? 'Pagamento à vista cobrado do saldo da sua conta corrente.' 
                : 'Seus dados são protegidos e tokenizados pelo Mercado Pago.';

            // Garante HTML limpo e pré-preenchido com dados do cliente a cada abertura
            modalCartao.innerHTML = `
                <div style="background:#fff;border-radius:16px;padding:28px 24px;width:100%;max-width:420px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3)">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
                        <h3 style="font-size:18px;font-weight:700;color:#1e293b;margin:0">${tituloModal}</h3>
                        <button id="btn-fechar-modal-cartao" style="background:none;border:none;font-size:22px;cursor:pointer;color:#64748b;line-height:1">&times;</button>
                    </div>
                    <p style="font-size:13px;color:#64748b;margin:0 0 18px">
                        ${subTituloModal}
                    </p>
                    ${gerarHtmlFormCartao(dadosClienteParaCartao, tipoCartao)}
                </div>`;

            modalCartao.style.display = 'flex';

            // Carrega Wallet Brick em segundo plano para o pagamento por aproximação / 1-clique
            let pollingWalletTimer = null;
            const carregarWalletModal = async () => {
                try {
                    const respPref = await fetch(API_ENDPOINTS.MERCADOPAGO_CRIAR_PREFERENCIA_CARTEIRA, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            tenant_id: CONFIG.ID_USUARIO_LOJA,
                            valor_total: valorTotal,
                            cliente: dadosClienteParaCartao,
                            itens: carrinhoAtual.map(it => ({
                                title: it.nome || 'Produto',
                                quantidade: it.quantidade || 1,
                                preco_unitario: it.preco_venda_sugerido || it.preco || 0
                            }))
                        })
                    });
                    const dataPref = await respPref.json();
                    if (dataPref.sucesso && dataPref.preference_id) {
                        await inicializarWalletBrick('wallet-brick-container', dataPref.preference_id, {
                            onSubmit: () => {
                                console.log('[App] ⚡ Wallet Brick acionado. Iniciando verificação de aprovação...');
                                if (pollingWalletTimer) clearInterval(pollingWalletTimer);
                                pollingWalletTimer = setInterval(async () => {
                                    try {
                                        const urlSt = `${API_ENDPOINTS.MERCADOPAGO_CONSULTAR_STATUS_PREFERENCIA}?external_reference=${encodeURIComponent(dataPref.external_reference)}&preference_id=${encodeURIComponent(dataPref.preference_id)}&tenant_id=${encodeURIComponent(CONFIG.ID_USUARIO_LOJA)}`;
                                        const resSt = await fetch(urlSt);
                                        const dtSt = await resSt.json();
                                        if (dtSt.sucesso && dtSt.status === 'approved') {
                                            clearInterval(pollingWalletTimer);
                                            pollingWalletTimer = null;
                                            modalCartao.style.display = 'none';
                                            destruirWalletBrick();
                                            destruirCardForm();

                                            alert('🎉 Pagamento Aprovado com Sucesso via Carteira Digital! Seu pedido foi confirmado e está sendo preparado.');
                                            try {
                                                const { gerarComprovanteVenda } = await import('./receipt.js?v=20260911_05');
                                                await gerarComprovanteVenda(carrinhoAtual, {
                                                    venda_id: dataPref.external_reference,
                                                    forma_pagamento: 'Carteira Digital (Google Pay / Apple Pay / MP)',
                                                    numero_parcelas: 1
                                                });
                                            } catch (errComp) {
                                                console.warn('[App] ⚠️ Erro comprovante wallet:', errComp);
                                            }
                                            fecharModal('modal-cliente-pedido');
                                            limparCarrinho();
                                            await carregarCarrinhoInicial();
                                            atualizarBadgeCarrinho();
                                            const btnConfirmar = document.getElementById('btn-confirmar-pedido');
                                            if (btnConfirmar) {
                                                btnConfirmar.disabled = false;
                                                btnConfirmar.textContent = '✅ Confirmar Pedido';
                                            }
                                        }
                                    } catch (errPoll) {
                                        console.warn('[App] Erro polling carteira:', errPoll);
                                    }
                                }, 2500);
                            }
                        });
                    }
                } catch (eWb) {
                    console.warn('[App] Aviso ao preparar Carteira Digital:', eWb);
                    const wbContainer = document.getElementById('wallet-brick-container');
                    if (wbContainer) {
                        wbContainer.innerHTML = '<span style="font-size:11px;color:#94a3b8">Utilize os campos do cartão abaixo</span>';
                    }
                }
            };
            carregarWalletModal();

            // Inicializa o CardForm do MP e aguarda o token ou cancelamento
            await new Promise((resolve, reject) => {
                const btnFechar = document.getElementById('btn-fechar-modal-cartao');
                if (btnFechar) {
                    btnFechar.onclick = () => {
                        if (pollingWalletTimer) {
                            clearInterval(pollingWalletTimer);
                            pollingWalletTimer = null;
                        }
                        modalCartao.style.display = 'none';
                        destruirWalletBrick();
                        destruirCardForm();
                        reject(new Error('PAGAMENTO_CANCELADO'));
                    };
                }

                inicializarCardForm('form-checkout-mp-cartao', valorTotal, async (token, installments, paymentMethodId, issuerId) => {
                    if (pollingWalletTimer) {
                        clearInterval(pollingWalletTimer);
                        pollingWalletTimer = null;
                    }
                    destruirWalletBrick();
                    // Token gerado com sucesso — fecha modal e prossegue
                    modalCartao.style.display = 'none';
                    window.mpCardToken       = token;
                    window.mpInstallments    = isDebito ? 1 : installments;
                    window.mpPaymentMethodId = paymentMethodId;
                    window.mpIssuerId        = issuerId;
                    window.mpTipoCartao      = tipoCartao;
                    resolve();
                }, tipoCartao).catch(reject);
            });

        } catch (err) {
            if (err.message === 'PAGAMENTO_CANCELADO') {
                const btnConfirmar = document.getElementById('btn-confirmar-pedido');
                if (btnConfirmar) {
                    btnConfirmar.disabled = false;
                    btnConfirmar.textContent = '✅ Confirmar Pedido';
                }
                return;
            }
            console.error('[App] ❌ Erro ao inicializar CardForm MP:', err);
            alert('Não foi possível carregar o formulário de cartão: ' + err.message);
            const btnConfirmar = document.getElementById('btn-confirmar-pedido');
            if (btnConfirmar) {
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = '✅ Confirmar Pedido';
            }
            return;
        }
    }


    const selectParcelas = document.getElementById('numero-parcelas');
    let numeroParcelas = parseInt(selectParcelas?.value || 1, 10);
    if (!permiteParcelamento && numeroParcelas > 1) {
        numeroParcelas = 1;
        if (selectParcelas) {
            selectParcelas.value = '1';
        }
    }
    // ===============================================
    
    const btnConfirmar = document.getElementById('btn-confirmar-pedido');
    btnConfirmar.disabled = true;
    btnConfirmar.textContent = 'Processando...';
    
    try {
        const dadosPedido = {
            cliente_id: clienteId, // Usar o ID já validado
            observacoes: document.getElementById('observacoes-pedido').value || null,
            colaborador_vendedor_id: colaboradorAtual?.id || null, // Pega o ID do objeto colaborador
            forma_pagamento_id: formaPagamentoId, // Usar a variável validada
            forma_pagamento_tipo: isDebito ? 'CARTAO_DEBITO' : tipoFormaPagamento,
            forma_pagamento_nome: formaPagamentoSelecionada?.nome || (isDebito ? 'Cartão de Débito' : 'Cartão de Crédito'),
            tipo_cartao: tipoCartao,
            numero_parcelas: isDebito ? 1 : numeroParcelas,
            data_primeiro_pagamento: permiteParcelamento && numeroParcelas > 1 ? document.getElementById('data-primeiro-pagamento')?.value || null : null,
            intervalo_dias_parcelas: permiteParcelamento && numeroParcelas > 1 ? parseInt(document.getElementById('intervalo-dias')?.value || 30, 10) : null,
            
            // ✅ MAPEAMENTO DE LOGÍSTICA PARA CAMPOS PADRÃO DO PULSE
            acrescimo_valor: (document.querySelector('input[name="tipo_entrega"]:checked')?.value === 'RETIRADA') 
                ? 0 
                : parseFloat(document.getElementById('taxa-entrega')?.value || 0),
            acrescimo_tipo: 'FIXO',
            observacao_acrescimo: (() => {
                const tipoEntrega = document.querySelector('input[name="tipo_entrega"]:checked')?.value || 'RETIRADA';
                if (tipoEntrega === 'RETIRADA') return 'Entrega: Retirada na Loja';
                const servico = window.opcaoFreteSelecionada?.servico || document.getElementById('opcao-frete-servico')?.value;
                const prazo = window.opcaoFreteSelecionada?.prazo_descricao || document.getElementById('opcao-frete-prazo')?.value;
                if (servico && prazo) return `Entrega: ${servico} (${prazo})`;
                if (servico) return `Entrega: ${servico}`;
                return `Entrega: Receber em Casa`;
            })()
        };
        
        const carrinho = getCarrinho();
        
        console.log('[App] 📤 Enviando pedido...', dadosPedido);
        
        const resultado = await finalizarPedido(dadosPedido, carrinho);
        
        console.log('[App] 📥 Resultado:', resultado);
        
        if (resultado.sucesso) {
            const vendaId = resultado.dados?.id || resultado.dados?.venda?.id || resultado.dados?.order_id || window.pedidoPreventivoId;
            
            // ===============================================
            // ✅ CARTÃO MERCADO PAGO APROVADO ONLINE
            // ===============================================
            const isCartaoAprovadoMP = resultado.gateway === 'mercadopago' && (resultado.status === 'approved' || resultado.dados?.status === 'approved');
            if (isCartaoAprovadoMP) {
                console.log('[App] 🎉 Pagamento com cartão Mercado Pago aprovado!');
                alert('🎉 Pagamento Aprovado com Sucesso! Seu pedido foi confirmado e está sendo preparado.');
                try {
                    const { gerarComprovanteVenda } = await import('./receipt.js?v=20260911_05');
                    const carrinhoSnapshot = getCarrinho();
                    await gerarComprovanteVenda(carrinhoSnapshot, {
                        venda_id: vendaId,
                        forma_pagamento: formaPagamentoSelecionada?.nome || 'Cartão (Mercado Pago)',
                        numero_parcelas: numeroParcelas || 1,
                        parcelas: null
                    });
                } catch (errComp) {
                    console.warn('[App] ⚠️ Erro ao gerar comprovante:', errComp);
                }
                fecharModal('modal-cliente-pedido');
                limparCarrinho();
                await carregarCarrinhoInicial();
                atualizarBadgeCarrinho();
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = '✅ Confirmar Pedido';
                return;
            }

            // ===============================================
            // ✅ GATEWAY DINÂMICO: Se foi redirecionado ou exibiu modal de pagamento
            // o gateway-pagamento.js já cuida do resto — não fazer nada aqui
            // ===============================================
            if (resultado.redirecionado) {
                return; // MP Redirect ou processamento assíncrono
            }

            if (resultado.mensagem === 'Modal PIX exibido. Aguardando pagamento.') {
                 console.log('[App] ✅ Modal PIX dinâmico exibido. Aguardando confirmação...');
                 fecharModal('modal-cliente-pedido');
                 btnConfirmar.disabled = false;
                 btnConfirmar.textContent = '✅ Confirmar Pedido';
                 return;
            }

            // ✅ PIX DINÂMICO via gateway: se o tipo é PIX e gateway está ativo,
            // mostrarModalPix() já foi chamado — verificar se o modal existe
            const isPixDinamico = (tipoFormaPagamento === 'PIX' || tipoFormaPagamento === 'PIX_DINAMICO');
            const gatewayAtivo = window.GATEWAY_CONFIG?.gateway && window.GATEWAY_CONFIG.gateway !== 'nenhum';
            if (isPixDinamico && gatewayAtivo && window.GATEWAY_CONFIG?.habilitado) {
                // O modal PIX já foi criado pelo gateway-pagamento.js
                // Só precisamos fechar o modal de pedido — o carrinho permanece intacto até o pagamento ser confirmado
                const modalPixExiste = !!document.getElementById('modal-pix-asaas');
                if (modalPixExiste) {
                    console.log('[App] ✅ Modal PIX dinâmico ativo. Aguardando confirmação...');
                    fecharModal('modal-cliente-pedido');
                    btnConfirmar.disabled = false;
                    btnConfirmar.textContent = '✅ Confirmar Pedido';
                    return;
                }
            }
            
            // ===============================================
            // ✅ AJUSTE: Verifica se é PIX ESTATICO para abrir modal
            // ===============================================
            const isPixEstatico = tipoFormaPagamento === 'PIX_ESTATICO';
            const isVista = numeroParcelas === 1;
            
            if (isPixEstatico && isVista && !resultado.offline && !resultado.redirecionado) {
                console.log('[App] 🟢 Venda PIX Estático à vista detectada. Gerando QR Code...');
                
                const taxaEntregaPix = (dadosPedido.acrescimo_valor && !isNaN(dadosPedido.acrescimo_valor)) 
                    ? parseFloat(dadosPedido.acrescimo_valor) 
                    : 0;
                const valorTotal = calcularTotalCarrinho() + taxaEntregaPix;
                
                // Gera TxID limpo: Catalogo + DDMMYYYY + HHMM
                const now = new Date();
                const dia = String(now.getDate()).padStart(2, '0');
                const mes = String(now.getMonth() + 1).padStart(2, '0');
                const ano = String(now.getFullYear());
                const hora = String(now.getHours()).padStart(2, '0');
                const minuto = String(now.getMinutes()).padStart(2, '0');
                const txId = `Catalogo${dia}${mes}${ano}${hora}${minuto}`;

                // Abre o Modal PIX com dados do pedido
                await mostrarModalPixEstatico(valorTotal, txId, {
                    ...dadosPedido,
                    itens: carrinho,
                    valorTotal: valorTotal,
                    venda_id: vendaId
                }, CONFIG.ID_USUARIO_LOJA);
                
                // Limpa carrinho pois a venda foi registrada
                limparCarrinho();
                await carregarCarrinhoInicial();
                atualizarBadgeCarrinho();
                fecharModal('modal-cliente-pedido');
                
                // Restaura botão
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = '✅ Confirmar Pedido';
                return; // Encerra aqui para manter o modal PIX aberto
            }
            
            // ===============================================
            // ✅ AJUSTE PARA POLLING DO PIX DINÂMICO (gateway)
            // ===============================================
            if (resultado.redirecionado) {
                // Se foi redirecionado (ex: MercadoPago), não faz mais nada aqui
                return; 
            }
            
            // Se o modal PIX dinâmico foi exibido, o gateway-pagamento.js cuida do resto.
            if (resultado.mensagem === 'Modal PIX exibido. Aguardando pagamento.') {
                 console.log('[App] Modal PIX dinâmico exibido. Aguardando confirmação...');
                 fecharModal('modal-cliente-pedido');
                 btnConfirmar.disabled = false;
                 btnConfirmar.textContent = '✅ Confirmar Pedido';
                 return;
            }
            // ===============================================

            // ✅ CORREÇÃO: Para vendas online, comprovante só é exibido após confirmação de pagamento
            // PAGAR_AO_ENTREGADOR também não gera comprovante imediatamente (aguarda confirmação na entrega)
            const isPagarAoEntregador = tipoFormaPagamento === 'PAGAR_AO_ENTREGADOR';
            const gatewayMpAtivoConfirmar = window.GATEWAY_CONFIG?.gateway === 'mercadopago' && window.GATEWAY_CONFIG?.habilitado;
            const isCartaoSemGateway = ['CARTAO_CREDITO', 'CARTAO_DEBITO', 'CARTAO'].includes(tipoFormaPagamento) && !gatewayMpAtivoConfirmar;
            
            if (isPagarAoEntregador) {
                alert('Pedido realizado com sucesso! O comprovante será gerado após a confirmação do pagamento na entrega.');
                limparCarrinho();
                await carregarCarrinhoInicial();
                atualizarBadgeCarrinho();
            } else if (isCartaoSemGateway) {
                // Cobrança presencial apenas se a loja NÃO possuir gateway MP configurado
                const nomeForma = formaPagamentoSelecionada?.nome || 'Cartão';
                alert(`✅ Pedido registrado com sucesso! Pagamento via ${nomeForma} será processado presencialmente / na entrega.`);
                // Gera comprovante imediatamente com dados disponíveis
                try {
                    const { gerarComprovanteVenda } = await import('./receipt.js?v=20260911_05');
                    const carrinhoSnapshot = getCarrinho();
                    await gerarComprovanteVenda(carrinhoSnapshot, {
                        venda_id: vendaId,
                        forma_pagamento: nomeForma,
                        numero_parcelas: numeroParcelas || 1,
                        parcelas: null
                    });
                } catch (errComp) {
                    console.warn('[App] ⚠️ Erro ao gerar comprovante de cartão:', errComp);
                }
                limparCarrinho();
                await carregarCarrinhoInicial();
                atualizarBadgeCarrinho();
            } else if (resultado.gateway === 'mercadopago' && resultado.status !== 'approved') {
                console.warn('[App] ⚠️ Pagamento de cartão não aprovado ou pendente. Carrinho mantido.');
            } else {
                // Para outras formas de pagamento online, aguarda confirmação
                alert('Pedido realizado com sucesso! Aguardando confirmação de pagamento...');
            }
            
            fecharModal('modal-cliente-pedido');

        } else {
            // Se finalizarPedido retornou insucesso
            if (resultado.gateway === 'mercadopago' && (resultado.status === 'in_process' || resultado.status === 'pending')) {
                alert(`⏳ Atenção: ${resultado.mensagem || 'Seu pagamento com cartão está em análise pelo Mercado Pago. Você será notificado assim que for concluído.'}`);
                fecharModal('modal-cliente-pedido');
                return;
            }
            if (resultado.gateway === 'mercadopago' && resultado.status === 'rejected') {
                console.log('[App] ℹ️ Pagamento com cartão recusado. Carrinho e formulário mantidos para nova tentativa.');
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = '✅ Confirmar Pedido';
                if (resultado.acao === 'outro_cartao') {
                    window.mpCardToken = null;
                    window.mpInstallments = null;
                    window.mpPaymentMethodId = null;
                    window.mpIssuerId = null;
                }
                return;
            }
            alert(`Erro: ${resultado.mensagem || 'Erro desconhecido ao processar pedido.'}`);
        }
        
    } catch (error) {
        // Se ocorreu um erro inesperado
        console.error('[App] Erro ao finalizar pedido:', error);
        alert(`Erro ao finalizar pedido: ${error.message}`);
    } finally {
        // ✅ AJUSTE PARA POLLING DO PIX
        // Só re-habilita o botão se NÃO for um PIX aguardando
        const modalPix = document.getElementById('modal-pix-asaas');
        if (!modalPix) { 
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = '✅ Confirmar Pedido';
        }
    }
};

// ==========================================================================
// MODAIS
// ==========================================================================

function abrirModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
}

function fecharModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }
}

window.abrirModal = abrirModal;

// ==========================================================================
// ✅ LIGHTBOX (GALERIA DE FOTOS AMPLIADA)
// ==========================================================================

let galeriaAtual = {
    fotos: [],
    index: 0
};

window.abrirGaleria = function(idx, fotosJson) {
    try {
        const fotos = typeof fotosJson === 'string' ? JSON.parse(fotosJson) : fotosJson;
        if (!fotos || fotos.length === 0) return;
        
        galeriaAtual.fotos = fotos;
        galeriaAtual.index = idx || 0;
        
        const modal = document.getElementById('modal-galeria');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden'; // Bloqueia scroll
            atualizarGaleria();
        }
    } catch (e) {
        console.error('[Lightbox] Erro ao abrir galeria:', e);
    }
};

function atualizarGaleria() {
    const img = document.getElementById('img-ampliada');
    const contador = document.getElementById('contador-galeria');
    const foto = galeriaAtual.fotos[galeriaAtual.index];
    
    if (foto) {
        let src = '';
        const rawPath = typeof foto === 'string' 
            ? foto 
            : (foto.arquivo_path || foto.url || foto.imagem || '');
            
        if (rawPath.startsWith('http') || rawPath.startsWith('data:')) {
            src = rawPath;
        } else if (rawPath) {
            const arquivoPath = rawPath.replace(/^\//, '');
            const baseUrl = (CONFIG && CONFIG.URL_BASE_WEB ? CONFIG.URL_BASE_WEB : '').replace(/\/$/, '');
            src = `${baseUrl}/${arquivoPath}`;
        }
        
        if (src && img) {
            img.src = src;
        }
        
        if (contador) {
            contador.textContent = `${galeriaAtual.index + 1} / ${galeriaAtual.fotos.length}`;
        }
        
        // Controle de visibilidade das setas
        const btnPrev = document.getElementById('modal-prev');
        const btnNext = document.getElementById('modal-next');
        if (galeriaAtual.fotos.length <= 1) {
            if (btnPrev) btnPrev.classList.add('hidden');
            if (btnNext) btnNext.classList.add('hidden');
        } else {
            if (btnPrev) btnPrev.classList.remove('hidden');
            if (btnNext) btnNext.classList.remove('hidden');
        }
    }
}

window.navegarGaleria = function(direcao, event) {
    if (event) event.stopPropagation();
    galeriaAtual.index += direcao;
    
    if (galeriaAtual.index < 0) galeriaAtual.index = galeriaAtual.fotos.length - 1;
    if (galeriaAtual.index >= galeriaAtual.fotos.length) galeriaAtual.index = 0;
    
    const img = document.getElementById('img-ampliada');
    if (img) {
        img.style.opacity = '0.3';
        img.style.transform = 'scale(0.95)';
        setTimeout(() => {
            atualizarGaleria();
            img.style.opacity = '1';
            img.style.transform = 'scale(1)';
        }, 150);
    }
};

window.fecharGaleria = function() {
    const modal = document.getElementById('modal-galeria');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = ''; // Libera scroll
    }
};

// Teclado
document.addEventListener('keydown', (e) => {
    const modal = document.getElementById('modal-galeria');
    if (modal && !modal.classList.contains('hidden')) {
        if (e.key === 'Escape') fecharGaleria();
        if (e.key === 'ArrowLeft') navegarGaleria(-1);
        if (e.key === 'ArrowRight') navegarGaleria(1);
    }
});
window.fecharModal = fecharModal;

// ==========================================================================
// EVENT LISTENERS
// ==========================================================================

function inicializarEventListeners() {
    // Botão de abrir carrinho
    const btnCarrinho = document.getElementById('btn-abrir-carrinho');
    if (btnCarrinho) {
        btnCarrinho.addEventListener('click', window.abrirCarrinho);
    }
    
    // Botão de finalizar pedido - ADICIONADO
    const btnFinalizarPedido = document.getElementById('btn-finalizar-pedido');
    if (btnFinalizarPedido) {
        btnFinalizarPedido.addEventListener('click', window.abrirModalPedido);
    }
    
    // Máscaras de CPF
    const inputsCPF = document.querySelectorAll('[data-mask="cpf"]');
    inputsCPF.forEach(input => {
        input.addEventListener('input', (e) => maskCPF(e.target));
    });

    // CORREÇÃO: Adicionar listeners para tipo de venda
    const radioVendaCliente = document.getElementById('tipo_venda_cliente');
    if (radioVendaCliente) {
        radioVendaCliente.addEventListener('change', alternarCampoVendedor);
    }
    
    const radioVendaVendedor = document.getElementById('tipo_venda_vendedor');
    if (radioVendaVendedor) {
        radioVendaVendedor.addEventListener('change', alternarCampoVendedor);
    }
    
    // Máscaras de telefone
    const inputsTel = document.querySelectorAll('[data-mask="phone"]');
    inputsTel.forEach(input => {
        input.addEventListener('input', (e) => maskPhone(e.target));
    });
    
    // Listener para mudança no número de parcelas
    const selectParcelas = document.getElementById('numero-parcelas');
    if (selectParcelas) {
        selectParcelas.addEventListener('change', function() {
            const numeroParcelas = parseInt(this.value, 10) || 1;
            const campoDataPrimeiroPagamento = document.getElementById('campo-data-primeiro-pagamento');
            const campoIntervaloParcelas = document.getElementById('campo-intervalo-parcelas');
            
            if (numeroParcelas > 1) {
                if (campoDataPrimeiroPagamento) {
                    campoDataPrimeiroPagamento.classList.remove('hidden');
                }
                if (campoIntervaloParcelas) {
                    campoIntervaloParcelas.classList.remove('hidden');
                }
            } else {
                if (campoDataPrimeiroPagamento) {
                    campoDataPrimeiroPagamento.classList.add('hidden');
                }
                if (campoIntervaloParcelas) {
                    campoIntervaloParcelas.classList.add('hidden');
                }
            }
        });
    }
    
    // Fechar modais ao clicar fora
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
             // ✅ AJUSTE: Não fecha o modal PIX ao clicar fora
            if (e.target === modal && modal.id !== 'modal-pix-asaas' && modal.id !== 'modal-pix-estatico') {
                fecharModal(modal.id);
            }
        });
    });
    
    // Listener específico para fechar modal PIX Estático ao clicar fora
    const modalPixEstatico = document.getElementById('modal-pix-estatico');
    if (modalPixEstatico) {
        modalPixEstatico.addEventListener('click', (e) => {
            if (e.target === modalPixEstatico) {
                window.fecharModalPixEstatico();
            }
        });
    }

    // ==================================================================
    // ✅ AJUSTE: OUVIR A CONFIRMAÇÃO DE PAGAMENTO DO GATEWAY
    // (Este foi o listener que adicionei na etapa anterior)
    // ==================================================================
    // ✅ AJUSTE: Listener para pagamento confirmado - chama endpoint de confirmação
    // ==================================================================
    window.addEventListener('pagamentoConfirmado', async (event) => {
        console.log('[App] 💳 Pagamento confirmado recebido!', event.detail);
        
        const vendaId = event.detail.pedidoId || event.detail.venda_id;
        
        if (!vendaId) {
            console.error('[App] ❌ Venda ID não encontrado no evento de pagamento confirmado');
            alert('Erro: ID da venda não encontrado.');
            return;
        }
        
        try {
            // Chama endpoint de confirmação de recebimento
            const response = await fetch(API_ENDPOINTS.PEDIDO_CONFIRMAR_RECEBIMENTO, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ venda_id: vendaId })
            });
            
            if (!response.ok) {
                throw new Error(`Erro ao confirmar recebimento: ${response.status}`);
            }
            
            const respostaConfirmacao = await response.json();
            console.log('[App] 🔍 Resposta confirmar-recebimento (raw):', respostaConfirmacao);

            // ✅ FIX #1: O backend envolve o retorno em { sucesso, data, mensagem } —
            // desempacotar .data para acessar itens, valor_total, formaPagamento, etc.
            const vendaConfirmada = respostaConfirmacao.data || respostaConfirmacao;
            console.log('[App] ✅ Recebimento confirmado (dados desempacotados):', vendaConfirmada);
            console.log('[App] 🔍 vendaConfirmada.itens:', vendaConfirmada.itens);
            
            // Gera comprovante após confirmação
            const { gerarComprovanteVenda } = await import('./receipt.js');
            const carrinho = vendaConfirmada.itens || [];
            
            // Busca parcelas se houver
            let parcelas = null;
            const numeroParcConfirmada = vendaConfirmada.numero_parcelas || 0;
            if (numeroParcConfirmada > 1) {
                try {
                    const respParcelas = await fetch(`${API_ENDPOINTS.PEDIDO_PARCELAS}?venda_id=${vendaId}`);
                    if (respParcelas.ok) {
                        const dadosParcelas = await respParcelas.json();
                        // Desempacota envelope .data se presente
                        const parcelasPayload = dadosParcelas.data || dadosParcelas;
                        parcelas = parcelasPayload.parcelas || null;
                    }
                } catch (error) {
                    console.warn('[App] Erro ao buscar parcelas:', error);
                }
            }
            
            await gerarComprovanteVenda(carrinho, {
                venda_id: vendaId,
                itens: carrinho,
                valorTotal: vendaConfirmada.valor_total,
                // ✅ Tenta ambas as chaves (snake_case e camelCase) para compatibilidade
                forma_pagamento: vendaConfirmada.forma_pagamento?.nome
                    || vendaConfirmada.formaPagamento?.nome
                    || 'Não informado',
                numero_parcelas: numeroParcConfirmada,
                parcelas: parcelas,
                cliente: vendaConfirmada.cliente
            });
            
            // Exibe alerta de sucesso
            alert('Pagamento confirmado com sucesso! Comprovante gerado.');
            
            // Limpa o carrinho
            limparCarrinho();
            atualizarBadgeCarrinho();
            
            // Fecha o modal de pedido
            fecharModal('modal-cliente-pedido');
            
            // Re-habilita o botão de confirmar
            const btnConfirmar = document.getElementById('btn-confirmar-pedido');
            if (btnConfirmar) {
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = '✅ Confirmar Pedido';
            }
            
        } catch (error) {
            console.error('[App] ❌ Erro ao confirmar recebimento:', error);
            alert('Erro ao confirmar recebimento: ' + error.message);
        }
    });
    // ==================================================================
    // FIM DO AJUSTE
    // ==================================================================
    
    console.log('[App] ✅ Event listeners inicializados');
}

// ==========================================================================
// UTILITÁRIOS
// ==========================================================================

function mostrarErro(mensagem) {
    const container = document.getElementById('catalogo-produtos');
    if (container) {
        container.innerHTML = `
            <div class="col-span-full text-center py-16">
                <div class="text-red-500 text-xl mb-4">⚠️</div>
                <p class="text-gray-700 text-lg">${mensagem}</p>
                <button onclick="location.reload()" 
                        class="mt-4 bg-brand-500 text-white px-6 py-2 rounded-lg hover:bg-brand-600">
                    Tentar Novamente
                </button>
            </div>
        `;
    }
}

function mostrarNotificacao(mensagem) {
    const notif = document.createElement('div');
    notif.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-bounce';
    notif.textContent = mensagem;
    
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.remove();
    }, 3000);
}

// ==========================================================================
// INICIAR QUANDO DOM ESTIVER PRONTO
// ==========================================================================

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

/**
 * ✅ NOVO: Alterna visibilidade do campo de taxa de entrega
 */
window.toggleTaxaEntrega = function(show) {
    const container = document.getElementById('container-taxa-entrega');
    const input = document.getElementById('taxa-entrega');
    if (container) {
        if (show) {
            container.classList.remove('hidden');
            // NOVO: Calcular frete ao mostrar o campo
            if (typeof window.atualizarFrete === 'function') {
                window.atualizarFrete();
            }
        } else {
            container.classList.add('hidden');
            if (input) {
                input.value = '0.00';
                // Notificar mudança para atualizar totais
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    }
};

// Exportar funções para uso em outros módulos
// --- LÓGICA DE VARIAÇÕES (GRADE) - ESTILO SHOPEE ---
window.abrirModalVariacoes = async function(produtoId) {
    const modal = document.getElementById('modal-variacoes');
    const container = document.getElementById('variacoes-lista');
    const titulo = document.getElementById('modal-variacoes-titulo');
    const subtitulo = document.getElementById('modal-variacoes-subtitulo');
    
    if (!modal || !container) return;

    // Mostra modal com loading
    modal.classList.remove('hidden');
    container.innerHTML = `
        <div class="text-center py-12">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-brand-600 mx-auto"></div>
            <p class="text-sm text-gray-500 mt-4 font-medium">Buscando opções...</p>
        </div>
    `;

    try {
        const produtoMestre = produtos.find(p => p.id === produtoId);
        if (titulo) titulo.textContent = produtoMestre ? produtoMestre.nome : 'Opções Disponíveis';
        if (subtitulo) subtitulo.textContent = 'Este item possui diferentes tamanhos e cores.';

        let variacoes = (produtoMestre && produtoMestre.variacoes && produtoMestre.variacoes.length > 0)
            ? produtoMestre.variacoes
            : null;

        if (!variacoes) {
            // Busca variações da API com expand=variacoes filtrando estoque disponível
            const response = await fetch(`${API_ENDPOINTS.PRODUTO}/${produtoId}?somente_com_estoque=1&expand=variacoes`);
            if (!response.ok) throw new Error('Erro ao buscar variações');
            
            const data = await response.json();
            const produtoFull = data.data || data;
            variacoes = produtoFull.variacoes || [];
            if (produtoMestre) {
                produtoMestre.variacoes = variacoes;
            }
        }

        // Filtra apenas variações que possuem estoque disponível (> 0)
        const variacoesComEstoque = variacoes.filter(v => parseFloat(v.estoque_atual || 0) > 0);

        if (variacoesComEstoque.length === 0) {
            container.innerHTML = `<div class="p-6 text-center text-gray-500 font-medium">Nenhuma opção com estoque disponível no momento.</div>`;
            return;
        }

        // Armazena no mapa de cache global para adição instantânea sem novo fetch
        window._variacoesMap = window._variacoesMap || {};
        const baseUrl = (CONFIG.URL_BASE_WEB || '').replace(/\/$/, '');

        variacoesComEstoque.forEach(v => {
            const fotoObj = v.fotos && v.fotos.length > 0 ? v.fotos[0] : null;
            const imgPath = fotoObj && fotoObj.arquivo_path ? fotoObj.arquivo_path.replace(/^\//, '') : null;
            v.imagem = imgPath 
                ? `${baseUrl}/${imgPath}` 
                : (produtoMestre && produtoMestre.imagem ? produtoMestre.imagem : 'https://dummyimage.com/300x200/cccccc/ffffff.png&text=Sem+Imagem');
            v.produto_id = produtoId;
            window._variacoesMap[v.id] = v;
        });

        container.innerHTML = variacoesComEstoque.map(v => {
            return `
            <div 
                onclick="adicionarVariacaoDireto('${v.id}', '${produtoId}')" 
                class="flex justify-between items-center p-3 sm:p-4 border border-gray-100 hover:border-brand-300 hover:bg-brand-50 cursor-pointer active:scale-[0.98] rounded-xl transition-all shadow-sm bg-white"
                title="Adicionar esta opção"
            >
                <div class="flex items-center gap-3">
                    ${v.imagem ? `<img src="${v.imagem}" class="w-12 h-12 object-cover rounded-lg border border-gray-100 shrink-0" alt="${v.cor || ''}">` : ''}
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-[10px] font-bold rounded uppercase">${v.tamanho || 'U'}</span>
                            <span class="font-bold text-gray-800">${v.cor || 'Única'}</span>
                        </div>
                        <span class="text-[10px] text-gray-400 mt-1">Ref: ${v.codigo_referencia || 'N/A'}</span>
                    </div>
                </div>
                <div class="flex flex-col items-end shrink-0">
                    <span class="text-base sm:text-lg font-bold text-brand-600">${formatarMoeda(v.preco_venda_sugerido)}</span>
                    <span class="text-[10px] text-green-600 font-semibold">
                        Estoque: ${formatarQuantidade(v.estoque_atual, false)}
                    </span>
                </div>
            </div>`;
        }).join('');

    } catch (error) {
        console.error('[App] Erro ao carregar variações:', error);
        container.innerHTML = `<div class="p-6 text-center text-red-500 font-medium">Falha ao carregar opções. Tente novamente.</div>`;
    }
};

window.adicionarVariacaoDireto = async function(idVariacao, idMestre) {
    try {
        mostrarCarregando();
        const produtoMestre = produtos.find(p => p.id === idMestre);
        let variacao = (window._variacoesMap && window._variacoesMap[idVariacao]) ? window._variacoesMap[idVariacao] : null;

        // Se não estiver em memória, busca os dados da API
        if (!variacao) {
            const response = await fetch(`${API_ENDPOINTS.PRODUTO}/${idVariacao}`);
            if (!response.ok) throw new Error('Não foi possível carregar dados da variação');
            const resJson = await response.json();
            variacao = resJson.data || resJson;
        }

        const baseUrl = (CONFIG.URL_BASE_WEB || '').replace(/\/$/, '');
        const fotoObj = variacao.fotos && variacao.fotos.length > 0 ? variacao.fotos[0] : null;
        const imgPath = fotoObj && fotoObj.arquivo_path ? fotoObj.arquivo_path.replace(/^\//, '') : null;
        const imagem = imgPath 
            ? `${baseUrl}/${imgPath}`
            : (variacao.imagem || (produtoMestre && produtoMestre.imagem ? produtoMestre.imagem : 'https://dummyimage.com/300x200/cccccc/ffffff.png&text=Sem+Imagem'));

        // Constrói objeto consistente para o carrinho
        const variacaoComImagem = {
            ...variacao,
            id: idVariacao,
            produto_id: idMestre,
            variante_id: idVariacao,
            nome: variacao.nome || (produtoMestre ? `${produtoMestre.nome} (${variacao.cor || ''} ${variacao.tamanho || ''})`.trim() : 'Item'),
            preco_venda_sugerido: parseFloat(variacao.preco_venda_sugerido || (produtoMestre ? produtoMestre.preco_venda_sugerido : 0)),
            imagem: imagem
        };

        // Adiciona ao carrinho (quantidade 1 por padrão no seletor rápido)
        if (adicionarAoCarrinho(variacaoComImagem, 1)) {
            fecharModal('modal-variacoes');
            atualizarBadgeCarrinho();
            
            // Feedback visual no card mestre
            atualizarBadgeProduto(idMestre, true);
            
            // Feedback visual
            if (typeof mostrarNotificacao === 'function') {
                mostrarNotificacao(`${variacaoComImagem.nome} adicionado ao carrinho!`);
            }
        }
    } catch (error) {
        alert('Erro ao adicionar variação: ' + error.message);
    } finally {
        ocultarCarregando();
    }
};

export { init, carregarProdutos, abrirModal, fecharModal };
// ==========================================================================
// LÓGICA DE FRETE CENTRALIZADO (ESTADOS, FAIXAS DE PREÇO E MELHOR ENVIO)
// ==========================================================================

window.opcaoFreteSelecionada = null;
window.opcoesFreteDisponiveis = [];
let cacheViaCep = {};

/**
 * Identifica o maior porte presente nos itens do carrinho
 */
function identificarMaiorPorteCarrinho() {
    const itens = typeof getCarrinho === 'function' ? getCarrinho() : [];
    if (!itens.length) return 'P';
    
    const pesos = { 'X': 4, 'G': 3, 'M': 2, 'P': 1 };
    let maior = 'P';
    
    itens.forEach(item => {
        const porteItem = (item.porte || 'P').toUpperCase();
        if (pesos[porteItem] > pesos[maior]) {
            maior = porteItem;
        }
    });
    return maior;
}

/**
 * Calcula opções de frete diretamente no Carrinho de Compras
 */
window.calcularFreteCarrinho = async function(cepParam = null) {
    const inputCep = document.getElementById('carrinho-cep-input');
    const containerOpcoes = document.getElementById('carrinho-opcoes-frete');
    const statusEl = document.getElementById('carrinho-frete-status');
    const msgGratis = document.getElementById('carrinho-msg-frete-gratis');
    const btnCalc = document.getElementById('btn-calcular-frete-carrinho');

    let cep = (cepParam || inputCep?.value || '').replace(/\D/g, '');

    if (cep.length !== 8) {
        if (statusEl) statusEl.innerHTML = '<span class="text-red-500 font-semibold">Informe um CEP válido (8 dígitos)</span>';
        return;
    }

    if (inputCep) {
        inputCep.value = cep.replace(/^(\d{5})(\d{3})$/, '$1-$2');
    }

    if (statusEl) statusEl.innerHTML = '<span class="text-blue-600 animate-pulse font-medium">Cotando frete...</span>';
    if (btnCalc) btnCalc.disabled = true;

    try {
        // 1. Obter Cidade e Estado via ViaCEP se ainda não tivermos em cache
        let endereco = cacheViaCep[cep];
        if (!endereco) {
            try {
                const resCep = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                const dadosCep = await resCep.json();
                if (!dadosCep.erro) {
                    endereco = {
                        cidade: dadosCep.localidade || '',
                        estado: dadosCep.uf || '',
                        bairro: dadosCep.bairro || ''
                    };
                    cacheViaCep[cep] = endereco;
                }
            } catch (errCep) {
                console.warn('[Frete] ⚠️ Erro ao consultar ViaCEP:', errCep);
            }
        }

        const cidade = endereco?.cidade || '';
        const estado = endereco?.estado || '';
        const bairro = endereco?.bairro || '';

        // Preencher também campos da modal de cadastro/checkout se estiverem vazios
        const campoCepCheckout = document.getElementById('cadastro-cep');
        const campoCidadeCheckout = document.getElementById('cadastro-cidade');
        const campoBairroCheckout = document.getElementById('cadastro-bairro');
        const campoEstadoCheckout = document.getElementById('cadastro-estado');
        if (campoCepCheckout && !campoCepCheckout.value) campoCepCheckout.value = inputCep?.value || cep;
        if (campoCidadeCheckout && !campoCidadeCheckout.value && cidade) campoCidadeCheckout.value = cidade;
        if (campoBairroCheckout && !campoBairroCheckout.value && bairro) campoBairroCheckout.value = bairro;
        if (campoEstadoCheckout && !campoEstadoCheckout.value && estado) campoEstadoCheckout.value = estado;

        const subtotal = calcularTotalCarrinho();
        const maiorPorte = identificarMaiorPorteCarrinho();

        const url = `${CONFIG.URL_API}/api/frete/cotar?usuario_id=${CONFIG.ID_USUARIO_LOJA}&cep=${encodeURIComponent(cep)}&cidade=${encodeURIComponent(cidade)}&estado=${encodeURIComponent(estado)}&bairro=${encodeURIComponent(bairro)}&subtotal=${subtotal}&porte=${maiorPorte}`;

        const response = await fetch(url);
        const data = await response.json();

        if (data.success && Array.isArray(data.opcoes) && data.opcoes.length > 0) {
            window.opcoesFreteDisponiveis = data.opcoes;
            renderizarOpcoesFreteCarrinho(data.opcoes);
            
            if (statusEl) {
                statusEl.innerHTML = `<span class="text-emerald-600 font-semibold">${cidade ? cidade + ' - ' + estado : 'Calculado'}</span>`;
            }

            // Seleciona a opção de entrega mais adequada (prioriza entrega ao calcular CEP)
            const opcaoSalva = (window.opcaoFreteSelecionada && window.opcaoFreteSelecionada.tipo !== 'RETIRADA')
                ? data.opcoes.find(o => o.id === window.opcaoFreteSelecionada.id)
                : null;

            if (opcaoSalva) {
                window.selecionarOpcaoFrete(opcaoSalva.id);
            } else {
                // Primeira opção de entrega real (não retirada, se houver)
                const primeiraEntrega = data.opcoes.find(o => o.tipo !== 'RETIRADA') || data.opcoes[0];
                window.selecionarOpcaoFrete(primeiraEntrega.id);
            }
        } else {
            if (statusEl) statusEl.innerHTML = '<span class="text-amber-600">Nenhuma taxa cadastrada</span>';
            if (containerOpcoes) containerOpcoes.classList.add('hidden');
        }

    } catch (err) {
        console.error('[Frete] ❌ Erro ao calcular frete no carrinho:', err);
        if (statusEl) statusEl.innerHTML = '<span class="text-red-500">Erro na cotação</span>';
    } finally {
        if (btnCalc) btnCalc.disabled = false;
    }
};

/**
 * Renderiza as opções de frete retornadas no carrinho
 */
function renderizarOpcoesFreteCarrinho(opcoes) {
    const container = document.getElementById('carrinho-opcoes-frete');
    const msgGratis = document.getElementById('carrinho-msg-frete-gratis');
    if (!container) return;

    // Verificar se alguma opção de entrega tem frete grátis por promoção ou quanto falta
    const opcaoEntrega = opcoes.find(o => o.tipo !== 'RETIRADA');
    if (msgGratis) {
        if (opcaoEntrega?.gratis && (opcaoEntrega?.economia > 0 || opcaoEntrega?.valor_original > 0)) {
            const economizou = parseFloat(opcaoEntrega.economia || opcaoEntrega.valor_original || 0);
            msgGratis.className = 'mt-2 p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-2 shadow-xs';
            msgGratis.innerHTML = `<span>🎉</span><span><strong>Parabéns!</strong> Você ganhou <strong>Frete Grátis</strong> (Economia de <strong>R$ ${economizou.toFixed(2).replace('.', ',')}</strong>)</span>`;
            msgGratis.classList.remove('hidden');
        } else if (opcaoEntrega && opcaoEntrega.falta_para_frete_gratis > 0) {
            const falta = parseFloat(opcaoEntrega.falta_para_frete_gratis);
            msgGratis.className = 'mt-2 p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-medium flex items-center gap-2 shadow-xs';
            msgGratis.innerHTML = `<span>💡</span><span>Adicione mais <strong>R$ ${falta.toFixed(2).replace('.', ',')}</strong> para ganhar <strong>Frete Grátis</strong>!</span>`;
            msgGratis.classList.remove('hidden');
        } else {
            msgGratis.classList.add('hidden');
        }
    }

    container.innerHTML = opcoes.map(opt => {
        const isSelected = window.opcaoFreteSelecionada?.id === opt.id;
        const icone = opt.tipo === 'RETIRADA' ? '🏬' : (opt.tipo === 'MELHOR_ENVIO' ? '📦' : '🚚');
        const valorOriginal = parseFloat(opt.valor_original || 0);

        let valorHtml = '';
        if (opt.gratis) {
            if (valorOriginal > 0) {
                valorHtml = `
                    <div class="text-right">
                        <span class="text-[11px] text-gray-400 line-through mr-1">R$ ${valorOriginal.toFixed(2).replace('.', ',')}</span>
                        <span class="text-xs font-black text-emerald-600">Grátis</span>
                        <p class="text-[10px] text-emerald-600 font-bold leading-tight">🎉 Frete Grátis</p>
                    </div>
                `;
            } else {
                valorHtml = `<span class="text-xs font-black text-emerald-600">Grátis</span>`;
            }
        } else {
            valorHtml = `<span class="text-xs font-black text-gray-900">R$ ${parseFloat(opt.valor).toFixed(2).replace('.', ',')}</span>`;
        }

        const badgePromo = opt.gratis && valorOriginal > 0 
            ? `<span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-emerald-800 rounded-full">🎉 Economizou R$ ${valorOriginal.toFixed(2).replace('.', ',')}</span>` 
            : '';

        return `
            <label class="flex items-center justify-between p-2.5 rounded-xl border cursor-pointer transition-all ${isSelected ? 'border-brand-500 bg-brand-50/50 shadow-sm ring-1 ring-brand-500' : 'border-gray-200 hover:bg-gray-50 bg-white'}" onclick="window.selecionarOpcaoFrete('${opt.id}')">
                <div class="flex items-center gap-2.5 min-w-0">
                    <input type="radio" name="opcao_frete_radio" value="${opt.id}" ${isSelected ? 'checked' : ''} class="w-4 h-4 text-brand-600">
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-gray-800 truncate">${icone} ${opt.servico}</p>
                        <p class="text-[11px] text-gray-500">${opt.prazo_descricao || 'Prazo sob consulta'}</p>
                        ${badgePromo}
                    </div>
                </div>
                <div class="text-right flex-shrink-0 ml-2">
                    ${valorHtml}
                </div>
            </label>
        `;
    }).join('');

    container.classList.remove('hidden');
}

/**
 * Seleciona uma opção de frete e atualiza os totais e inputs do checkout
 */
window.selecionarOpcaoFrete = function(opcaoId) {
    const opcao = (window.opcoesFreteDisponiveis || []).find(o => o.id === opcaoId);
    if (!opcao) return;

    window.opcaoFreteSelecionada = opcao;
    console.log('[Frete] ✅ Opção selecionada:', opcao);

    // Atualiza marcação visual nos cards do carrinho
    renderizarOpcoesFreteCarrinho(window.opcoesFreteDisponiveis);

    // Atualiza resumo no carrinho
    const subtotal = calcularTotalCarrinho();
    const taxaEntrega = opcao.tipo === 'RETIRADA' ? 0.00 : parseFloat(opcao.valor || 0);
    const totalFinal = subtotal + taxaEntrega;

    const resumoEl = document.getElementById('resumo-valores-carrinho');
    const subtotalEl = document.getElementById('subtotal-produtos-carrinho');
    const labelFreteEl = document.getElementById('label-frete-escolhido');
    const valorFreteEl = document.getElementById('valor-frete-escolhido');
    const totalEl = document.getElementById('valor-total-carrinho');

    if (resumoEl) resumoEl.classList.remove('hidden');
    if (subtotalEl) subtotalEl.textContent = `R$ ${subtotal.toFixed(2).replace('.', ',')}`;
    if (labelFreteEl) labelFreteEl.textContent = `Frete (${opcao.servico}):`;
    
    if (valorFreteEl) {
        if (opcao.gratis && opcao.valor_original && opcao.valor_original > 0) {
            valorFreteEl.innerHTML = `<span class="line-through text-gray-400 font-normal mr-1.5 text-xs">R$ ${parseFloat(opcao.valor_original).toFixed(2).replace('.', ',')}</span><span class="text-emerald-600 font-bold">Grátis 🎉</span>`;
        } else if (opcao.gratis || taxaEntrega === 0) {
            valorFreteEl.textContent = 'Grátis';
        } else {
            valorFreteEl.textContent = `R$ ${taxaEntrega.toFixed(2).replace('.', ',')}`;
        }
    }
    
    if (totalEl) totalEl.textContent = `R$ ${totalFinal.toFixed(2).replace('.', ',')}`;

    // Sincroniza com a modal de checkout
    const campoTaxaCheckout = document.getElementById('taxa-entrega');
    const badgeServico = document.getElementById('servico-entrega-badge');
    const hiddenServico = document.getElementById('opcao-frete-servico');
    const hiddenPrazo = document.getElementById('opcao-frete-prazo');
    const infoPrazoCheckout = document.getElementById('info-prazo-entrega-checkout');

    if (campoTaxaCheckout) campoTaxaCheckout.value = taxaEntrega.toFixed(2);
    if (badgeServico) badgeServico.textContent = opcao.servico;
    if (hiddenServico) hiddenServico.value = opcao.servico;
    if (hiddenPrazo) hiddenPrazo.value = opcao.prazo_descricao || '';
    if (infoPrazoCheckout) {
        infoPrazoCheckout.textContent = `Prazo estimado: ${opcao.prazo_descricao || 'Não informado'}`;
        infoPrazoCheckout.classList.remove('hidden');
    }

    // Sincroniza os radios de tipo de entrega
    const radioRetirada = document.querySelector('input[name="tipo_entrega"][value="RETIRADA"]');
    const radioEntrega = document.querySelector('input[name="tipo_entrega"][value="ENTREGA"]');
    const containerTaxa = document.getElementById('container-taxa-entrega');

    if (opcao.tipo === 'RETIRADA') {
        if (radioRetirada) radioRetirada.checked = true;
        if (containerTaxa) containerTaxa.classList.add('hidden');
    } else {
        if (radioEntrega) radioEntrega.checked = true;
        if (containerTaxa) containerTaxa.classList.remove('hidden');
    }

    // Recalcula totais gerais se houver ouvinte
    if (typeof window.atualizarTotaisPedido === 'function') {
        window.atualizarTotaisPedido();
    }
};

/**
 * Consulta a API de frete e atualiza o campo de taxa de entrega (legado e checkout)
 */
window.atualizarFrete = async function() {
    console.log('[Frete] 🚚 Atualizando frete no checkout...');
    
    const tipoEntrega = document.querySelector('input[name="tipo_entrega"]:checked')?.value;
    const campoTaxa = document.getElementById('taxa-entrega');
    
    if (tipoEntrega === 'RETIRADA') {
        if (campoTaxa) {
            campoTaxa.value = '0.00';
            campoTaxa.dispatchEvent(new Event('change', { bubbles: true }));
        }
        return;
    }

    // Se já temos uma opção escolhida no carrinho e ela não é retirada, mantém
    if (window.opcaoFreteSelecionada && window.opcaoFreteSelecionada.tipo !== 'RETIRADA') {
        if (campoTaxa) campoTaxa.value = parseFloat(window.opcaoFreteSelecionada.valor || 0).toFixed(2);
        return;
    }

    const cep = (document.getElementById('cadastro-cep')?.value || '').trim();
    if (cep) {
        window.calcularFreteCarrinho(cep);
    }
};

/**
 * Inicializa ouvintes nos campos de endereço para cálculo de frete
 */
function inicializarOuvintesFrete() {
    const campos = ['cadastro-cep', 'cadastro-bairro', 'cadastro-cidade'];
    campos.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', () => window.atualizarFrete());
            el.addEventListener('blur', () => window.atualizarFrete());
        }
    });

    const radios = document.querySelectorAll('input[name="tipo_entrega"]');
    radios.forEach(r => {
        r.addEventListener('change', () => window.atualizarFrete());
    });

    // Máscara de CEP no input do carrinho
    const cepCarrinhoInput = document.getElementById('carrinho-cep-input');
    if (cepCarrinhoInput) {
        cepCarrinhoInput.addEventListener('input', (e) => {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 5) {
                v = v.substring(0, 5) + '-' + v.substring(5, 8);
            }
            e.target.value = v;
            if (v.replace(/\D/g, '').length === 8) {
                window.calcularFreteCarrinho(v);
            }
        });
    }
}

setTimeout(inicializarOuvintesFrete, 800);

