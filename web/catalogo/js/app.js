// app.js - Aplicação principal do catálogo PWA
// ✅ VERSÃO COMPLETA E CORRIGIDA (Baseado no app-old.js)

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
    verificarElementosCriticos
} from './utils.js';
import { ELEMENTOS_CRITICOS } from './config.js';
import { inicializarMonitoramentoRede } from './network.js';
import { cadastrarCliente } from './customer.js';
import { mostrarModalPixEstatico } from './pix.js';

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
        inicializarMonitoramentoRede();
        
        console.log('[App] ✅ Aplicação inicializada com sucesso!');
        
    } catch (error) {
        console.error('[App] ❌ Erro na inicialização:', error);
        mostrarErro('Erro ao inicializar a aplicação. Por favor, recarregue a página.');
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
                        console.log('[SW] 📦 Nova versão instalada, recarregando...');
                        
                        // Notificar usuário
                        if (confirm('Nova versão disponível! Deseja atualizar agora?')) {
                            newWorker.postMessage({ type: 'SKIP_WAITING' });
                            window.location.reload();
                        }
                    }
                });
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
                        <span class="qty-value">${item.quantidade}</span>
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

async function carregarProdutos() {
    try {
        console.log('[App] 📦 Carregando produtos...');
        
        const url = `${API_ENDPOINTS.PRODUTO}?usuario_id=${CONFIG.ID_USUARIO_LOJA}`;
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`Erro ${response.status}: ${response.statusText}`);
        }
        
        produtos = await response.json();
        console.log('[App] ✅ Produtos carregados:', produtos.length);
        
        produtosFiltrados = produtos; // Inicializa com todos os produtos
        filtrarProdutos(); // Aplica filtro atual (se houver)
        atualizarIndicadoresCarrinho();
        
    } catch (error) {
        console.error('[App] Erro ao carregar produtos:', error);
        mostrarErro('Erro ao carregar produtos. Verifique sua conexão.');
    }
}

function filtrarProdutos() {
    const termoBusca = document.getElementById('busca-produto')?.value?.toLowerCase().trim() || '';
    const btnLimpar = document.getElementById('btn-limpar-busca');
    
    // Mostra/oculta botão de limpar busca
    if (btnLimpar) {
        btnLimpar.classList.toggle('hidden', !termoBusca);
    }
    
    if (!termoBusca) {
        produtosFiltrados = produtos;
    } else {
        produtosFiltrados = produtos.filter(produto => 
            produto.nome.toLowerCase().includes(termoBusca)
        );
    }
    
    renderizarProdutos(produtosFiltrados);
}

function inicializarBuscaProdutos() {
    const inputBusca = document.getElementById('busca-produto');
    if (!inputBusca) return;
    
    // Filtra enquanto o usuário digita (debounce)
    let timeoutId;
    inputBusca.addEventListener('input', (e) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            filtrarProdutos();
        }, 300); // Aguarda 300ms após parar de digitar
    });
    
    // Filtra ao pressionar Enter
    inputBusca.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
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

function renderizarProdutos(listaProdutos) {
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
        // Construir URL da imagem corretamente
        let urlImagem = 'https://dummyimage.com/300x200/cccccc/ffffff.png&text=Sem+Imagem';
        if (produto.fotos && produto.fotos.length > 0 && produto.fotos[0].arquivo_path) {
            const arquivoPath = produto.fotos[0].arquivo_path.replace(/^\//, '');
            const baseUrl = CONFIG.URL_BASE_WEB.replace(/\/$/, '');
            urlImagem = `${baseUrl}/${arquivoPath}`;
        }
        
        return `
        <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300 relative"
             data-produto-card="${produto.id}">
            
            <div class="badge-no-carrinho hidden absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full z-10">
                ✓ No Carrinho
            </div>
            
            <img src="${urlImagem}" 
                 alt="${produto.nome}"
                 class="w-full h-48 object-cover"
                 onerror="this.src='https://dummyimage.com/300x200/cccccc/ffffff.png&text=Erro'">
            
            <div class="p-4">
                <h3 class="text-lg font-bold text-gray-800 mb-2">${produto.nome}</h3>
                
                ${produto.descricao ? `
                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">${produto.descricao}</p>
                ` : ''}
                
                <div class="flex items-center justify-between mb-4">
                    <span class="text-2xl font-bold text-blue-600">
                        ${formatarMoeda(produto.preco_venda_sugerido)}
                    </span>
                    
                    ${produto.estoque_atual > 0 ? `
                        <span class="text-xs text-green-600 font-semibold">
                            ${produto.estoque_atual} em estoque
                        </span>
                    ` : `
                        <span class="text-xs text-red-600 font-semibold">
                            Sem estoque
                        </span>
                    `}
                </div>
                
                <button onclick="abrirModalQuantidade('${produto.id}')"
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 ${produto.estoque_atual <= 0 ? 'opacity-50 cursor-not-allowed' : ''}"
                        ${produto.estoque_atual <= 0 ? 'disabled' : ''}>
                    🛒 Adicionar ao Carrinho
                </button>
            </div>
        </div>
    `;
    }).join('');
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
    inputQtd.value = 1;
     // ✅ CORREÇÃO: Usando 'estoque_atual'
    inputQtd.max = produto.estoque_atual;
    
    btnConfirmar.onclick = () => {
        const quantidade = parseInt(inputQtd.value, 10);
        
        if (quantidade > 0 && quantidade <= produto.estoque_atual) {
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
            alert(`Quantidade inválida. Máximo disponível: ${produto.estoque_atual}`);
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
function popularFormasPagamento(formas) {
    const select = document.getElementById('forma-pagamento');
    if (!select) return;

    select.innerHTML = ''; // Limpa "Carregando..."

    if (!formas || formas.length === 0) {
        select.options[0] = new Option('Nenhuma forma de pgto.', '');
        select.disabled = true;
        return;
    }
    
    // Filtrar formas de pagamento para vendas online
    // 1. Remover DINHEIRO (não disponível em vendas online)
    // 2. Mostrar apenas formas online se api_de_pagamento estiver habilitado
    console.log('[App] 🔍 Filtrando formas de pagamento. Total recebido:', formas.length);
    console.log('[App] 🔍 Formas recebidas:', formas.map(f => ({ nome: f.nome, tipo: f.tipo })));
    console.log('[App] 🔍 GATEWAY_CONFIG:', window.GATEWAY_CONFIG);
    
    // ✅ CORREÇÃO: Verificar GATEWAY_CONFIG antes de filtrar
    const gatewayHabilitado = window.GATEWAY_CONFIG && window.GATEWAY_CONFIG.habilitado === true;
    console.log('[App] 🔍 Gateway habilitado:', gatewayHabilitado);
    
    const formasFiltradas = formas.filter(forma => {
        const tipo = (forma.tipo || '').toUpperCase();
        
        console.log(`[App] 🔍 Analisando forma: ${forma.nome} (tipo: ${tipo})`);
        
        // Sempre remover DINHEIRO em vendas online
        if (tipo === 'DINHEIRO') {
            console.log(`[App] ❌ Removendo ${forma.nome} (DINHEIRO não disponível em vendas online)`);
            return false;
        }
        
        // ✅ CORREÇÃO: Formas de pagamento online que requerem gateway
        // BOLETO, CARTAO_CREDITO, CARTAO_DEBITO, CARTAO, PIX (dinâmico) só aparecem se api_de_pagamento = true
        const formasOnline = ['BOLETO', 'CARTAO_CREDITO', 'CARTAO_DEBITO', 'CARTAO', 'PIX'];
        const ehFormaOnline = formasOnline.includes(tipo);
        
        // Se for forma online, só mostrar se api_de_pagamento estiver habilitado
        if (ehFormaOnline) {
            if (!gatewayHabilitado) {
                console.log(`[App] ❌ Removendo ${forma.nome} (${tipo} requer api_de_pagamento=true)`);
                return false;
            }
            console.log(`[App] ✅ Mantendo ${forma.nome} (${tipo} - gateway habilitado)`);
            return true;
        }
        
        // PAGAR_AO_ENTREGADOR sempre disponível (não requer gateway)
        if (tipo === 'PAGAR_AO_ENTREGADOR') {
            console.log(`[App] ✅ Mantendo ${forma.nome} (PAGAR_AO_ENTREGADOR sempre disponível)`);
            return true;
        }
        
        // PIX_ESTATICO pode aparecer mesmo sem gateway (QR Code fixo)
        if (tipo === 'PIX_ESTATICO') {
            console.log(`[App] ✅ Mantendo ${forma.nome} (PIX_ESTATICO sempre disponível)`);
            return true;
        }
        
        // ✅ CORREÇÃO: Outras formas NÃO aparecem por padrão (só as específicas acima)
        console.log(`[App] ❌ Removendo ${forma.nome} (tipo ${tipo} não permitido em vendas online)`);
        return false;
    });
    
    console.log('[App] ✅ Formas filtradas:', formasFiltradas.length, formasFiltradas.map(f => f.nome));
    
    if (formasFiltradas.length === 0) {
        select.options[0] = new Option('Nenhuma forma de pgto. disponível', '');
        select.disabled = true;
        return;
    }
    
    select.disabled = false;
    select.options[0] = new Option('Selecione o pagamento...', '');
    
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
        
        // Armazenar o ID do cliente no input hidden
        const clienteId = cliente.id;
        const inputClienteId = document.getElementById('cliente_id');
        if (inputClienteId && clienteId) {
            inputClienteId.value = clienteId;
        }
        
        console.log('[App] ✅ Cliente encontrado:', clienteAtual);

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
            intervalo_dias_parcelas: permiteParcelamento && numeroParcelas > 1 ? parseInt(document.getElementById('intervalo-dias')?.value || 30, 10) : null
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

// Exportar funções para uso em outros módulos
export { init, carregarProdutos, abrirModal, fecharModal };