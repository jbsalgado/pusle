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
    limparCarrinho,
    atualizarIndicadoresCarrinho,
    atualizarBadgeProduto
} from './cart.js';
import { carregarCarrinho, limparDadosLocaisPosSinc } from './storage.js';
import { finalizarPedido } from './order.js';
import { 
    carregarFormasPagamento, 
    calcularParcelas, 
    formatarInfoParcelas 
} from './payment.js';
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

// Disponibiliza CONFIG no window para compatibilidade com módulos que não usam import
window.CONFIG = CONFIG;

// ==========================================================================
// VARIÁVEIS GLOBAIS
// ==========================================================================

let produtos = [];
let produtosFiltrados = []; // Produtos filtrados pela busca
let clienteAtual = null;
let colaboradorAtual = null;
let formasPagamento = [];

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
        // Disponibilizar GATEWAY_CONFIG no window para uso em outras funções
        window.GATEWAY_CONFIG = gatewayConfig;
        
        // 2.5️⃣ Carregar logo da empresa
        await carregarLogoEmpresa();
        
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
                <div class="w-16 h-16 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                <h2 class="text-xl font-bold mb-2">Verificando seu pagamento...</h2>
                <p class="text-gray-600 mb-4">Aguarde um instante enquanto confirmamos a transação com o Mercado Pago.</p>
                <p id="mp-status-text" class="text-sm font-medium text-blue-600">Status: PROCESSANDO</p>
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

async function carregarLogoEmpresa() {
    try {
        console.log('[App] 🖼️ Carregando logo da empresa...');
        const logoImg = document.getElementById('logo-empresa');
        if (!logoImg) {
            console.warn('[App] ⚠️ Elemento logo-empresa não encontrado no DOM');
            return;
        }
        
        const response = await fetch(`${API_ENDPOINTS.USUARIO_DADOS_LOJA}?usuario_id=${CONFIG.ID_USUARIO_LOJA}`);
        console.log('[App] 📡 Resposta da API dados-loja:', response.status);
        
        if (response.ok) {
            const dadosLoja = await response.json();
            console.log('[App] 📋 Dados da loja recebidos:', { 
                tem_logo_path: !!dadosLoja.logo_path, 
                logo_path: dadosLoja.logo_path 
            });
            
            if (dadosLoja.logo_path) {
                let logoUrl = dadosLoja.logo_path.trim();
                
                // Se não for URL completa (http:// ou https://), precisa construir a URL completa
                if (!logoUrl.match(/^https?:\/\//)) {
                    // Remove barra inicial se houver
                    logoUrl = logoUrl.replace(/^\//, '');
                    
                    // Garante que CONFIG.URL_BASE_WEB está definido
                    if (!CONFIG || !CONFIG.URL_BASE_WEB) {
                        console.error('[App] ⚠️ CONFIG.URL_BASE_WEB não está definido!');
                        // Fallback: usa window.location
                        const baseUrl = window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/');
                        logoUrl = baseUrl + '/' + logoUrl;
                    } else {
                        // Remove barra final do URL_BASE_WEB se houver
                        const baseUrl = CONFIG.URL_BASE_WEB.replace(/\/$/, '');
                        logoUrl = baseUrl + '/' + logoUrl;
                    }
                }
                
                console.log('[App] 🔗 URL da logo construída:', logoUrl);
                
                // Remove o onerror que esconde a imagem
                logoImg.onerror = function() {
                    console.warn('[App] ⚠️ Erro ao carregar imagem da logo:', logoUrl);
                    this.style.display = 'none';
                };
                
                // Adiciona onload para confirmar que carregou
                logoImg.onload = function() {
                    console.log('[App] ✅ Logo carregada com sucesso!');
                    this.classList.remove('hidden');
                };
                
                logoImg.src = logoUrl;
                logoImg.classList.remove('hidden'); // Remove hidden imediatamente
            } else {
                console.log('[App] ℹ️ Logo não configurada para esta loja');
            }
        } else {
            console.warn('[App] ⚠️ Erro ao buscar dados da loja. Status:', response.status);
        }
    } catch (error) {
        console.error('[App] ❌ Erro ao carregar logo da empresa:', error);
    }
}

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
    const badge = document.getElementById('contador-carrinho'); // CORRIGIDO: ID correto do HTML
    const btnCarrinho = document.getElementById('btn-abrir-carrinho');
    
    if (badge) {
        badge.textContent = totalItens;
        badge.classList.toggle('hidden', totalItens === 0);
    }
    
    if (btnCarrinho) {
        btnCarrinho.disabled = totalItens === 0;
    }
}

function renderizarCarrinho() {
    const container = document.getElementById('itens-carrinho');
    const totalElement = document.getElementById('valor-total-carrinho');
    const totalItensFooter = document.getElementById('total-itens-footer');
    const btnFinalizar = document.getElementById('btn-finalizar-pedido');
    
    const carrinho = getCarrinho();
    
    if (carrinho.length === 0) {
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
    
    // Atualizar total
    const total = calcularTotalCarrinho();
    if (totalElement) {
        totalElement.textContent = formatarMoeda(total);
    }
    
    // Atualizar contador de itens no footer
    const totalItens = calcularTotalItens();
    if (totalItensFooter) {
        totalItensFooter.textContent = totalItens;
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
let paginaAtual = 1;
let metadadosPaginacao = null;

/**
 * Carrega uma página específica de produtos (paginação real)
 * @param {number} pagina - Número da página a carregar (padrão: 1)
 * @param {boolean} forcarRecarregar - Se true, ignora cache e recarrega
 */
async function carregarProdutos(pagina = 1, forcarRecarregar = false) {
    try {
        const termoBusca = document.getElementById('busca-produto')?.value?.trim() || '';
        
        // Verifica cache primeiro (a menos que seja forçado recarregar, ou se houver pesquisa)
        if (!forcarRecarregar && !termoBusca && cacheProdutos.has(pagina)) {
            console.log(`[App] 📦 Usando cache da página ${pagina}`);
            const dadosCache = cacheProdutos.get(pagina);
            produtos = dadosCache.produtos;
            produtosFiltrados = produtos;
            paginaAtual = pagina;
            metadadosPaginacao = dadosCache.metadados;
            renderizarProdutos(produtosFiltrados);
            atualizarIndicadoresCarrinho();
            atualizarControlesPaginacao();
            ocultarCarregando();
            return;
        }
        
        console.log('[App] 📦 Carregando produtos (página', pagina, ')...');
        mostrarCarregando();
        
        // Usa per-page padrão de 100 (configurado no backend) e expande variações (e suas fotos), fotos do pai e categoria
        let url = `${API_ENDPOINTS.PRODUTO}?usuario_id=${CONFIG.ID_USUARIO_LOJA}&page=${pagina}&per-page=100&expand=variacoes.fotos,fotos,categoria`;

        if (termoBusca) {
            url += `&q=${encodeURIComponent(termoBusca)}`;
        }
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`Erro ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        // DEBUG: Log completo da resposta para identificar o formato
        console.log('[App] 🔍 DEBUG - Resposta completa da API:', {
            temItems: !!data.items,
            temMeta: !!data._meta,
            temLinks: !!data._links,
            chaves: Object.keys(data),
            totalItems: data.items?.length || (Array.isArray(data) ? data.length : 0),
            estrutura: JSON.stringify(data).substring(0, 500) // Primeiros 500 chars
        });
        
        // A API do Yii2 retorna um objeto com items, _links e _meta quando usa ActiveDataProvider
        // Mas o formato pode variar dependendo do serializer configurado
        let produtosPagina = [];
        let metadados = null;
        
        if (data.items && Array.isArray(data.items)) {
            // Formato paginado do Yii2 (serializer padrão)
            produtosPagina = data.items;
            
            // Tenta diferentes formatos de metadados
            if (data._meta) {
                // Formato padrão do Yii2
                metadados = {
                    totalCount: data._meta.totalCount || data._meta.total || 0,
                    pageCount: data._meta.pageCount || Math.ceil((data._meta.totalCount || 0) / (data._meta.perPage || 100)) || 1,
                    currentPage: data._meta.currentPage || data._meta.page || pagina,
                    perPage: data._meta.perPage || data._meta.pageSize || 100
                };
            } else if (data._links) {
                // Tenta extrair dos links
                const lastLink = data._links.last;
                if (lastLink) {
                    const lastPageMatch = lastLink.match(/page=(\d+)/);
                    const lastPage = lastPageMatch ? parseInt(lastPageMatch[1]) : 1;
                    metadados = {
                        totalCount: data._meta?.totalCount || 0,
                        pageCount: lastPage,
                        currentPage: pagina,
                        perPage: 100
                    };
                }
            } else {
                // Fallback: calcula baseado nos items retornados
                // Se retornou menos que per-page, é a última página
                const perPage = 100;
                const totalEstimado = produtosPagina.length < perPage 
                    ? (pagina - 1) * perPage + produtosPagina.length
                    : null; // Não sabemos o total exato
                
                metadados = {
                    totalCount: totalEstimado || produtosPagina.length * pagina, // Estimativa
                    pageCount: totalEstimado ? Math.ceil(totalEstimado / perPage) : pagina + 1, // Estimativa
                    currentPage: pagina,
                    perPage: perPage
                };
                
                console.warn('[App] ⚠️ Metadados de paginação não encontrados. Usando estimativas:', metadados);
            }
        } else if (Array.isArray(data)) {
            // Formato direto (array) - sem paginação ou serializer diferente
            produtosPagina = data;
            metadados = {
                totalCount: data.length,
                pageCount: 1,
                currentPage: 1,
                perPage: data.length
            };
            console.warn('[App] ⚠️ Resposta não está paginada. Retornou array direto com', data.length, 'itens');
        } else {
            console.warn('[App] ⚠️ Formato de resposta inesperado:', data);
            produtosPagina = [];
            metadados = {
                totalCount: 0,
                pageCount: 1,
                currentPage: 1,
                perPage: 100
            };
        }
        
        // DEBUG: Log dos metadados extraídos
        console.log('[App] 🔍 DEBUG - Metadados extraídos:', metadados);
        
        // Salva no cache apenas se não houver pesquisa
        if (!termoBusca) {
            cacheProdutos.set(pagina, {
                produtos: produtosPagina,
                metadados: metadados
            });
        }
        
        // Atualiza variáveis globais
        produtos = produtosPagina; // Apenas produtos da página atual
        produtosFiltrados = produtos;
        paginaAtual = pagina;
        metadadosPaginacao = metadados;
        window.paginacaoMetadados = metadados;
        
        console.log(`[App] ✅ Página ${pagina} carregada: ${produtosPagina.length} produto(s) de ${metadados.totalCount} total`);
        
        // Renderiza apenas os produtos da página atual aplicando filtros locais
        aplicarFiltrosLocais();
        atualizarIndicadoresCarrinho();
        atualizarControlesPaginacao();
        ocultarCarregando();
        
    } catch (error) {
        console.error('[App] Erro ao carregar produtos:', error);
        mostrarErro('Erro ao carregar produtos. Verifique sua conexão.');
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
        console.warn('[App] ⚠️ Containers de controles de paginação não encontrados');
        return;
    }
    
    const metadados = metadadosPaginacao || window.paginacaoMetadados;
    
    // DEBUG: Log dos metadados
    console.log('[App] 🔍 DEBUG atualizarControlesPaginacao:', {
        metadadosPaginacao: metadadosPaginacao,
        windowPaginacaoMetadados: window.paginacaoMetadados,
        metadados: metadados,
        pageCount: metadados?.pageCount,
        totalCount: metadados?.totalCount
    });
    
    if (!metadados) {
        console.warn('[App] ⚠️ Metadados de paginação não disponíveis. Ocultando controles.');
        if (containerPaginacao) containerPaginacao.classList.add('hidden');
        if (containerPaginacaoRodape) containerPaginacaoRodape.classList.add('hidden');
        return;
    }
    
    // Mostra controles se houver mais de 1 página OU se totalCount > perPage
    const deveMostrar = metadados.pageCount > 1 || (metadados.totalCount > metadados.perPage);
    
    if (!deveMostrar) {
        console.log('[App] ℹ️ Apenas 1 página ou menos de perPage produtos. Ocultando controles.');
        if (containerPaginacao) containerPaginacao.classList.add('hidden');
        if (containerPaginacaoRodape) containerPaginacaoRodape.classList.add('hidden');
        return;
    }
    
    console.log('[App] ✅ Mostrando controles de paginação:', {
        pageCount: metadados.pageCount,
        currentPage: metadados.currentPage,
        totalCount: metadados.totalCount,
        perPage: metadados.perPage
    });
    
    // Calcula informações de exibição
    const inicio = (metadados.currentPage - 1) * metadados.perPage + 1;
    const fim = Math.min(metadados.currentPage * metadados.perPage, metadados.totalCount);
    const textoInfo = `Mostrando ${inicio}-${fim} de ${metadados.totalCount} produtos`;
    const textoPagina = `Página ${metadados.currentPage} de ${metadados.pageCount}`;
    const podeAnterior = metadados.currentPage > 1;
    const podeProxima = metadados.currentPage < metadados.pageCount;
    
    // Atualiza controles do topo
    if (containerPaginacao) {
        containerPaginacao.classList.remove('hidden');
        
        const infoPaginacao = document.getElementById('info-paginacao');
        if (infoPaginacao) {
            infoPaginacao.textContent = textoInfo;
        }
        
        const paginaAtualInfo = document.getElementById('pagina-atual-info');
        if (paginaAtualInfo) {
            paginaAtualInfo.textContent = textoPagina;
        }
        
        const btnAnterior = document.getElementById('btn-pagina-anterior');
        const btnProxima = document.getElementById('btn-pagina-proxima');
        
        if (btnAnterior) {
            btnAnterior.disabled = !podeAnterior;
        }
        
        if (btnProxima) {
            btnProxima.disabled = !podeProxima;
        }
    }
    
    // Atualiza controles do rodapé
    if (containerPaginacaoRodape) {
        containerPaginacaoRodape.classList.remove('hidden');
        
        const infoPaginacaoRodape = document.getElementById('info-paginacao-rodape');
        if (infoPaginacaoRodape) {
            infoPaginacaoRodape.textContent = textoInfo;
        }
        
        const paginaAtualInfoRodape = document.getElementById('pagina-atual-info-rodape');
        if (paginaAtualInfoRodape) {
            paginaAtualInfoRodape.textContent = textoPagina;
        }
        
        const btnAnteriorRodape = document.getElementById('btn-pagina-anterior-rodape');
        const btnProximaRodape = document.getElementById('btn-pagina-proxima-rodape');
        
        if (btnAnteriorRodape) {
            btnAnteriorRodape.disabled = !podeAnterior;
        }
        
        if (btnProximaRodape) {
            btnProximaRodape.disabled = !podeProxima;
        }
    }
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
    
    console.log('[App] Navegando para página:', novaPagina);
    carregarProdutos(novaPagina);
    
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
    const arquivoPath = novaFoto.arquivo_path.replace(/^\//, '');
    const baseUrl = CONFIG.URL_BASE_WEB.replace(/\/$/, '');
    img.src = `${baseUrl}/${arquivoPath}`;

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
        const arquivoPath = fotos[0].arquivo_path.replace(/^\//, '');
        const baseUrl = CONFIG.URL_BASE_WEB.replace(/\/$/, '');
        urlImagemPadrao = `${baseUrl}/${arquivoPath}`;
    }

    if (fotos.length <= 1) {
        const urlParaModal = urlImagemPadrao.replace(/'/g, "\\'");
        const fotosJson = JSON.stringify(fotos).replace(/'/g, "&apos;");
        return `
            <div class="w-full h-48 bg-gray-50 flex items-center justify-center overflow-hidden p-2 cursor-zoom-in" 
                 onclick="abrirGaleria(0, '${fotosJson}')">
                <img src="${urlImagemPadrao}" 
                     alt="${produto.nome}"
                     class="w-full h-full object-contain"
                     onerror="this.src='https://dummyimage.com/300x200/cccccc/ffffff.png&text=Erro'">
            </div>
        `;
    }

    // Gerar dots
    const dotsHtml = fotos.map((_, idx) => `
        <div class="slideshow-dot w-1.5 h-1.5 rounded-full transition-all duration-300 ${idx === 0 ? 'bg-white scale-125' : 'bg-white/50 shadow-sm'}"></div>
    `).join('');

    return `
        <div class="slideshow-container relative w-full h-48 bg-gray-50 group overflow-hidden cursor-zoom-in" 
             data-fotos='${JSON.stringify(fotos)}' 
             data-current-index="0"
             onclick="abrirGaleria(parseInt(this.getAttribute('data-current-index')), this.getAttribute('data-fotos'))">
            
            <!-- Imagem Principal -->
            <img src="${urlImagemPadrao}" 
                 alt="${produto.nome}"
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
 * Renderiza uma tabela compacta com a prévia da grade (variações) do produto
 */
function renderizarPreviaGrade(produto) {
    if (!produto.variacoes || produto.variacoes.length === 0) return '';

    // Limita a 5 variações para não quebrar o layout
    const variacoesExibir = produto.variacoes.slice(0, 5);
    const temMais = produto.variacoes.length > 5;

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
                <td class="py-1 text-center ${v.estoque_atual > 0 ? 'text-green-600' : 'text-red-500'}">${v.estoque_atual || 0}</td>
                <td class="py-1 text-right font-bold text-blue-600">${formatarMoeda(preco)}</td>
            </tr>
        `;
    });

    html += `
            </tbody>
        </table>
        ${temMais ? `
            <div class="text-[9px] text-center text-orange-500 font-bold mt-1 uppercase italic">
                + ${produto.variacoes.length - 5} variações disponíveis
            </div>
        ` : ''}
    </div>
    `;

    return html;
}

function renderizarProdutos(listaProdutos) {
    console.log(`[App] 🎨 Renderizando ${listaProdutos.length} produtos...`, listaProdutos);
    const container = document.getElementById('catalogo-produtos');
    
    if (!container) {
        console.error('[App] Container de produtos não encontrado');
        return;
    }
    
    if (listaProdutos.length === 0) {
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
    
    container.innerHTML = listaProdutos.map(produto => {
        return `
        <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300 relative"
             data-produto-card="${produto.id}">
            
            <div class="badge-no-carrinho hidden absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full z-10">
                ✓ No Carrinho
            </div>
            ${renderizarEspacoFoto(produto)}
            
            <div class="p-4">
                <h3 class="text-lg font-bold text-gray-800 mb-2">${produto.nome}</h3>
                
                ${produto.descricao ? `
                    <p class="text-sm text-gray-600 mb-2 line-clamp-1">${produto.descricao}</p>
                ` : ''}
                
                ${renderizarPreviaGrade(produto)}
                
                <div class="flex items-center justify-between mb-4">
                    <span class="text-2xl font-bold text-blue-600">
                        ${formatarMoeda(produto.preco_venda_sugerido)}
                    </span>
                    
                    <span class="text-xs ${produto.estoque_atual > 0 || !!produto.possui_grade ? 'text-green-600' : 'text-red-600'} font-semibold">
                        ${!!produto.possui_grade ? 'Várias opções' : (produto.estoque_atual > 0 ? `${formatarQuantidade(produto.estoque_atual, produto.venda_fracionada)} em estoque` : 'Sem estoque')}
                    </span>
                </div>
                
                ${!!produto.possui_grade ? `
                    <button onclick="abrirModalVariacoes('${produto.id}')"
                            class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded-lg flex items-center justify-center gap-2 transition duration-200">
                        🏷️ Escolher Opções
                    </button>
                ` : `
                    <button onclick="abrirModalQuantidade('${produto.id}')"
                            class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 ${produto.estoque_atual <= 0 ? 'opacity-50 cursor-not-allowed' : ''}"
                            ${produto.estoque_atual <= 0 ? 'disabled' : ''}>
                        🛒 Adicionar ao Carrinho
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
    
    // Adicionar listener de clique nos cards para seleção social
    // Usamos delegação de evento ou adicionamos a cada card? Vamos adicionar ao container para perfomance
    // Mas como o renderizarProdutos sobrescreve o HTML, precisamos re-adicionar ou usar onclick inline?
    // Melhor: Adicionar onclick no div principal do card via JS logo após renderizar
    
    container.querySelectorAll('[data-produto-card]').forEach(card => {
        card.addEventListener('click', (e) => {
            // Se estiver em modo social (verificado pela classe no body)
            if (document.body.classList.contains('modo-social')) {
                e.preventDefault();
                e.stopPropagation();
                
                const id = card.getAttribute('data-produto-card');
                // Encontrar o objeto produto completo
                const produto = listaProdutos.find(p => String(p.id) === String(id));
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
        // REGRA 3: PIX_ESTATICO sempre disponível (não requer gateway)
        // ====================================================================
        if (tipo === 'PIX_ESTATICO') {
            console.log(`[App] ✅ MANTIDO: ${nome} (PIX_ESTATICO sempre disponível)`);
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
    
    const tipo = formaSelecionada.tipo || '';
    
    // Se for DINHEIRO, PIX ou PIX ESTATICO, desabilita parcelamento
    if (tipo === 'DINHEIRO' || tipo === 'PIX' || tipo === 'PIX_ESTATICO') {
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
        
        console.log('[App] 🔒 Parcelamento desabilitado para forma de pagamento:', tipo);
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
    const tipoFormaPagamento = formaPagamentoSelecionada?.tipo || '';
    const permiteParcelamento = tipoFormaPagamento !== 'DINHEIRO' && tipoFormaPagamento !== 'PIX' && tipoFormaPagamento !== 'PIX_ESTATICO' && tipoFormaPagamento !== 'PAGAR_AO_ENTREGADOR';
    
    // Se não permite parcelamento, força para 1 parcela
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
            numero_parcelas: numeroParcelas,
            data_primeiro_pagamento: permiteParcelamento && numeroParcelas > 1 ? document.getElementById('data-primeiro-pagamento')?.value || null : null,
            intervalo_dias_parcelas: permiteParcelamento && numeroParcelas > 1 ? parseInt(document.getElementById('intervalo-dias')?.value || 30, 10) : null,
            
            // ✅ MAPEAMENTO DE LOGÍSTICA PARA CAMPOS PADRÃO DO PULSE
            acrescimo_valor: parseFloat(document.getElementById('taxa-entrega')?.value || 0),
            acrescimo_tipo: 'FIXO',
            observacao_acrescimo: `Entrega: ${document.querySelector('input[name="tipo_entrega"]:checked')?.value || 'RETIRADA'}`
        };
        
        const carrinho = getCarrinho();
        
        console.log('[App] 📤 Enviando pedido...', dadosPedido);
        
        const resultado = await finalizarPedido(dadosPedido, carrinho);
        
        console.log('[App] 📥 Resultado:', resultado);
        
        if (resultado.sucesso) {
            const vendaId = resultado.dados?.id || resultado.dados?.venda?.id;
            
            // ===============================================
            // ✅ AJUSTE: Verifica se é PIX ESTATICO para abrir modal
            // ===============================================
            const isPixEstatico = tipoFormaPagamento === 'PIX_ESTATICO';
            const isVista = numeroParcelas === 1;
            
            if (isPixEstatico && isVista && !resultado.offline && !resultado.redirecionado) {
                console.log('[App] 🟢 Venda PIX Estático à vista detectada. Gerando QR Code...');
                
                const valorTotal = calcularTotalCarrinho();
                
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
                 // Não faz mais nada aqui, o polling está ativo
                 return;
            }
            // ===============================================

            // ✅ CORREÇÃO: Para vendas online, comprovante só é exibido após confirmação de pagamento
            // PAGAR_AO_ENTREGADOR também não gera comprovante imediatamente (aguarda confirmação na entrega)
            const isPagarAoEntregador = tipoFormaPagamento === 'PAGAR_AO_ENTREGADOR';
            
            if (isPagarAoEntregador) {
                alert('Pedido realizado com sucesso! O comprovante será gerado após a confirmação do pagamento na entrega.');
            } else {
                // Para outras formas de pagamento online, aguarda confirmação
                alert('Pedido realizado com sucesso! Aguardando confirmação de pagamento...');
            }
            
            if (!resultado.offline) {
                // Limpar carrinho apenas se não for offline
                limparCarrinho();
                await carregarCarrinhoInicial();
                atualizarBadgeCarrinho();
            }
            
            fecharModal('modal-cliente-pedido');

        } else {
            // Se finalizarPedido falhou
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
    
    if (foto && foto.arquivo_path) {
        const arquivoPath = foto.arquivo_path.replace(/^\//, '');
        const baseUrl = CONFIG.URL_BASE_WEB.replace(/\/$/, '');
        img.src = `${baseUrl}/${arquivoPath}`;
        contador.textContent = `${galeriaAtual.index + 1} / ${galeriaAtual.fotos.length}`;
        
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
            
            const vendaConfirmada = await response.json();
            console.log('[App] ✅ Recebimento confirmado:', vendaConfirmada);
            
            // Gera comprovante após confirmação
            const { gerarComprovanteVenda } = await import('./receipt.js');
            const carrinho = vendaConfirmada.itens || [];
            
            // Busca parcelas se houver
            let parcelas = null;
            if (vendaConfirmada.numero_parcelas > 1) {
                try {
                    const response = await fetch(`${API_ENDPOINTS.PEDIDO_PARCELAS}?venda_id=${vendaId}`);
                    if (response.ok) {
                        const dadosParcelas = await response.json();
                        parcelas = dadosParcelas.parcelas || null;
                    }
                } catch (error) {
                    console.warn('[App] Erro ao buscar parcelas:', error);
                }
            }
            
            await gerarComprovanteVenda(carrinho, {
                venda_id: vendaId,
                itens: carrinho,
                valorTotal: vendaConfirmada.valor_total,
                forma_pagamento: vendaConfirmada.formaPagamento?.nome || 'Não informado',
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
                        class="mt-4 bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600">
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
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mx-auto"></div>
            <p class="text-sm text-gray-500 mt-4 font-medium">Buscando opções...</p>
        </div>
    `;

    try {
        const produtoMestre = produtos.find(p => p.id === produtoId);
        if (titulo) titulo.textContent = produtoMestre ? produtoMestre.nome : 'Opções Disponíveis';
        if (subtitulo) subtitulo.textContent = 'Este item possui diferentes tamanhos e cores.';

        // Busca variações reais da API com expand=variacoes
        const response = await fetch(`${API_ENDPOINTS.PRODUTO}/${produtoId}?expand=variacoes`);
        if (!response.ok) throw new Error('Erro ao buscar variações');
        
        const data = await response.json();
        const produtoFull = data.data || data;
        const variacoes = produtoFull.variacoes || [];

        if (variacoes.length === 0) {
            container.innerHTML = `<div class="p-6 text-center text-gray-500">Nenhuma variação disponível no momento.</div>`;
            return;
        }

        container.innerHTML = variacoes.map(v => `
            <div onclick="adicionarVariacaoDireto('${v.id}', '${produtoId}')" class="flex justify-between items-center p-4 border border-gray-100 rounded-xl hover:border-blue-300 hover:bg-blue-50 cursor-pointer transition-all active:scale-[0.98] group bg-white shadow-sm">
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-[10px] font-bold rounded uppercase">${v.tamanho || 'U'}</span>
                        <span class="font-bold text-gray-800">${v.cor || 'Única'}</span>
                    </div>
                    <span class="text-[10px] text-gray-400 mt-1">Ref: ${v.codigo_referencia || 'N/A'}</span>
                </div>
                <div class="flex flex-col items-end">
                    <span class="text-lg font-bold text-blue-600">${formatarMoeda(v.preco_venda_sugerido)}</span>
                    <span class="text-[9px] ${v.estoque_atual > 0 ? 'text-green-500' : 'text-red-400'}">
                        Estoque: ${v.estoque_atual || 0}
                    </span>
                </div>
            </div>
        `).join('');

    } catch (error) {
        console.error('[App] Erro ao carregar variações:', error);
        container.innerHTML = `<div class="p-6 text-center text-red-500 font-medium">Falha ao carregar opções. Tente novamente.</div>`;
    }
};

window.adicionarVariacaoDireto = async function(idVariacao, idMestre) {
    try {
        mostrarCarregando();
        // Busca os dados completos da variação
        const response = await fetch(`${API_ENDPOINTS.PRODUTO}/${idVariacao}`);
        if (!response.ok) throw new Error('Não foi possível carregar dados da variação');
        
        const resJson = await response.json();
        const variacao = resJson.data || resJson;
        
        // Constrói imagem conforme lógica do catálogo
        const variacaoComImagem = {
            ...variacao,
            imagem: variacao.fotos && variacao.fotos.length > 0 && variacao.fotos[0].arquivo_path
                ? (() => {
                    const arquivoPath = variacao.fotos[0].arquivo_path.replace(/^\//, '');
                    const baseUrl = CONFIG.URL_BASE_WEB.replace(/\/$/, '');
                    return `${baseUrl}/${arquivoPath}`;
                })()
                : 'https://dummyimage.com/300x200/cccccc/ffffff.png&text=Sem+Imagem'
        };

        // Adiciona ao carrinho (quantidade 1 por padrão no seletor rápido)
        if (adicionarAoCarrinho(variacaoComImagem, 1)) {
            fecharModal('modal-variacoes');
            atualizarBadgeCarrinho();
            
            // Feedback visual no card mestre
            atualizarBadgeProduto(idMestre, true);
            
            // Feedback visual
            if (typeof mostrarNotificacao === 'function') {
                mostrarNotificacao(`${variacao.nome || 'Item'} adicionado ao carrinho!`);
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
// LÓGICA DE FRETE CENTRALIZADO
// ==========================================================================

/**
 * Consulta a API de frete e atualiza o campo de taxa de entrega
 */
window.atualizarFrete = async function() {
    console.log('[Frete] 🚚 Calculando frete...');
    
    // Verifica se é entrega ou retirada
    const tipoEntrega = document.querySelector('input[name="tipo_entrega"]:checked')?.value;
    const campoTaxa = document.getElementById('taxa-entrega');
    
    if (tipoEntrega === 'RETIRADA') {
        if (campoTaxa) {
            campoTaxa.value = '0.00';
            // Disparar evento change para atualizar totais (se houver listener)
            campoTaxa.dispatchEvent(new Event('change', { bubbles: true }));
        }
        return;
    }

    // Se for ENTREGA, busca os valores dos campos
    // Pode vir tanto da modal de "Finalizar" quanto da modal de "Cadastro" se estiver aberta
    const cep = (document.getElementById('cadastro-cep')?.value || '').trim();
    const bairro = (document.getElementById('cadastro-bairro')?.value || '').trim();
    const cidade = (document.getElementById('cadastro-cidade')?.value || '').trim();

    // Só busca se tiver pelo menos um dado relevante
    if (!cep && !bairro && !cidade) {
        console.log('[Frete] ⚠️ Nenhum dado de endereço para calcular frete.');
        return;
    }

    // Busca o maior porte presente no carrinho
    const identificarMaiorPorte = () => {
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
    };

    const maiorPorte = identificarMaiorPorte();
    console.log(`[Frete] 📦 Maior porte detectado no carrinho: ${maiorPorte}`);

    try {
        const url = `${CONFIG.URL_API}/api/frete/calcular?usuario_id=${CONFIG.ID_USUARIO_LOJA}&cep=${encodeURIComponent(cep)}&bairro=${encodeURIComponent(bairro)}&cidade=${encodeURIComponent(cidade)}&porte=${maiorPorte}`;
        
        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            let valorFrete = parseFloat(data.valor || 0);
            const msgFrete = document.getElementById('mensagem-frete-gratis');
            
            // Lógica de Frete Grátis por Ticket Médio
            if (data.valor_minimo_frete_gratis) {
                const subtotal = typeof window.calcularTotalCarrinho === 'function' ? window.calcularTotalCarrinho() : 0;
                const minParaGratis = parseFloat(data.valor_minimo_frete_gratis);
                
                if (subtotal >= minParaGratis) {
                    console.log(`[Frete] 🎁 Ticket atingido (R$ ${subtotal} >= R$ ${minParaGratis}). Frete Grátis!`);
                    valorFrete = 0;
                    if (msgFrete) {
                        msgFrete.textContent = '✅ Frete Grátis aplicado ao seu pedido!';
                        msgFrete.className = 'mt-1 text-xs font-semibold text-green-600';
                        msgFrete.classList.remove('hidden');
                    }
                } else {
                    const falta = minParaGratis - subtotal;
                    if (msgFrete) {
                        msgFrete.textContent = `🎁 Compre mais R$ ${falta.toFixed(2).replace('.', ',')} para ganhar Frete Grátis!`;
                        msgFrete.className = 'mt-1 text-xs font-semibold text-blue-600';
                        msgFrete.classList.remove('hidden');
                    }
                }
            } else if (msgFrete) {
                msgFrete.classList.add('hidden');
            }

            console.log(`[Frete] ✅ Ajustado para: R$ ${valorFrete}`);
            if (campoTaxa) {
                campoTaxa.value = valorFrete.toFixed(2);
                // Forçar recálculo do total do pedido
                if (typeof window.atualizarTotaisPedido === 'function') {
                    window.atualizarTotaisPedido();
                } else {
                    campoTaxa.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        }
    } catch (error) {
        console.error('[Frete] ❌ Erro ao calcular frete:', error);
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
            // Para CEP, também dispara após preencher via consulta automática se houver
            el.addEventListener('blur', () => window.atualizarFrete());
        }
    });

    // Também ouve a mudança do tipo de entrega
    const radios = document.querySelectorAll('input[name="tipo_entrega"]');
    radios.forEach(r => {
        r.addEventListener('change', () => window.atualizarFrete());
    });
}

// Chamar inicialização no final ou no init()
setTimeout(inicializarOuvintesFrete, 1000);
