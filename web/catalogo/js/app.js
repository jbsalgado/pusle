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

// ==========================================================================
// VARIÁVEIS GLOBAIS
// ==========================================================================

let produtos = [];
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
        await carregarConfigLoja();
        
        // 3️⃣ Registrar Service Worker
        await registrarServiceWorker();
        
        // 4️⃣ Carregar carrinho salvo
        await carregarCarrinhoInicial();
        
        // 5️⃣ Carregar produtos
        await carregarProdutos();
        
        // 6️⃣ Inicializar event listeners
        inicializarEventListeners();
        
        // 7️⃣ Configurar listener do Service Worker
        configurarListenerServiceWorker();
        
        // 8️⃣ Atualizar badge do carrinho
        atualizarBadgeCarrinho();
        
        // 9️⃣ Inicializar monitoramento de rede (status online/offline)
        inicializarMonitoramentoRede();
        
        console.log('[App] ✅ Aplicação inicializada com sucesso!');
        
    } catch (error) {
        console.error('[App] ❌ Erro na inicialização:', error);
        mostrarErro('Erro ao inicializar a aplicação. Por favor, recarregue a página.');
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
            urlImagem = `${CONFIG.URL_BASE_WEB}/${item.fotos[0].arquivo_path}`;
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
        
        renderizarProdutos(produtos);
        atualizarIndicadoresCarrinho();
        
    } catch (error) {
        console.error('[App] Erro ao carregar produtos:', error);
        mostrarErro('Erro ao carregar produtos. Verifique sua conexão.');
    }
}

function renderizarProdutos(listaProdutos) {
    const container = document.getElementById('catalogo-produtos');
    
    if (!container) {
        console.error('[App] Container de produtos não encontrado');
        return;
    }
    
    if (listaProdutos.length === 0) {
        container.innerHTML = `
            <div class="col-span-full text-center py-16">
                <p class="text-gray-500 text-lg">Nenhum produto disponível no momento.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = listaProdutos.map(produto => {
        // Construir URL da imagem corretamente
        let urlImagem = 'https://dummyimage.com/300x200/cccccc/ffffff.png&text=Sem+Imagem';
        if (produto.fotos && produto.fotos.length > 0 && produto.fotos[0].arquivo_path) {
            urlImagem = `${CONFIG.URL_BASE_WEB}/${produto.fotos[0].arquivo_path}`;
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
                    ? `${CONFIG.URL_BASE_WEB}/${produto.fotos[0].arquivo_path}`
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
 * @param {Array} formas - Array de objetos {id, nome}
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
    
    select.disabled = false;
    select.options[0] = new Option('Selecione o pagamento...', '');
    
    formas.forEach(forma => {
        if (forma.id && forma.nome) {
            const option = new Option(forma.nome, forma.id);
            // Armazena o tipo no atributo data-tipo para facilitar acesso
            option.setAttribute('data-tipo', forma.tipo || '');
            select.options[select.options.length] = option;
        }
    });
    
    // Armazena formas de pagamento globalmente
    formasPagamento = formas;
    // Disponibiliza globalmente para validação em order.js
    window.formasPagamento = formas;
    
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
    
    // Se for DINHEIRO ou PIX, desabilita parcelamento
    if (tipo === 'DINHEIRO' || tipo === 'PIX') {
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
        
        if (response.ok) {
            clienteAtual = await response.json();
            
            // Tratar diferentes estruturas de resposta possíveis
            const nomeCliente = clienteAtual.nome_completo || 
                              clienteAtual.cliente?.nome_completo || 
                              clienteAtual.nome || 
                              'Cliente encontrado';
            
            document.getElementById('nome-cliente-info').textContent = nomeCliente;
            document.getElementById('info-cliente').classList.remove('hidden');
            
            // Armazenar o ID do cliente no input hidden
            const clienteId = clienteAtual.id || clienteAtual.cliente?.id;
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

        } else {
            alert('Cliente não encontrado. Cadastre-o primeiro no sistema.');
            clienteAtual = null;

            // CORREÇÃO: Desabilitar o botão se a busca falhar
            const btnConfirmar = document.getElementById('btn-confirmar-pedido');
            if (btnConfirmar) {
                btnConfirmar.disabled = true;
            }
            const msgHabilitar = document.getElementById('msg-habilitar-botao');
            if (msgHabilitar) {
                msgHabilitar.classList.remove('hidden');
            }
        }
    } catch (error) {
        console.error('[App] Erro ao buscar cliente:', error);
        alert('Erro ao buscar cliente');
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
    const permiteParcelamento = tipoFormaPagamento !== 'DINHEIRO' && tipoFormaPagamento !== 'PIX';
    
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
            
            // ===============================================
            // ✅ AJUSTE PARA POLLING DO PIX (da etapa anterior)
            // ===============================================
            if (resultado.redirecionado) {
                // Se foi redirecionado (ex: MercadoPago), não faz mais nada aqui
                return; 
            }
            
            // Se o modal PIX foi exibido, o gateway-pagamento.js cuida do resto.
            // O app.js só precisa esperar pelo evento 'pagamentoConfirmado'.
            if (resultado.mensagem === 'Modal PIX exibido. Aguardando pagamento.') {
                 console.log('[App] Modal PIX exibido. Aguardando confirmação...');
                 // Não faz mais nada aqui, o polling está ativo
                 return; // Sai da função, mas o botão continua "Processando..."
            }
            // ===============================================

            // Para fluxos normais (offline, boleto, interno)
            alert(resultado.mensagem || 'Pedido realizado com sucesso!');
            
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
            if (e.target === modal && modal.id !== 'modal-pix-asaas') {
                fecharModal(modal.id);
            }
        });
    });

    // ==================================================================
    // ✅ AJUSTE: OUVIR A CONFIRMAÇÃO DE PAGAMENTO DO GATEWAY
    // (Este foi o listener que adicionei na etapa anterior)
    // ==================================================================
    window.addEventListener('pagamentoConfirmado', (event) => {
        console.log('[App] 💳 Pagamento confirmado recebido!', event.detail);
        
        // Exibe alerta de sucesso
        alert(`Pagamento confirmado com sucesso!\nPedido ID: ${event.detail.pedidoId || 'N/A'}`);
        
        // Limpa o carrinho
        limparCarrinho();
        atualizarBadgeCarrinho();
        
        // Fecha o modal de pedido (onde o cliente inseriu o CPF)
        fecharModal('modal-cliente-pedido');

        // Re-habilita o botão de confirmar, caso o usuário abra de novo
        const btnConfirmar = document.getElementById('btn-confirmar-pedido');
        if (btnConfirmar) {
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = '✅ Confirmar Pedido';
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