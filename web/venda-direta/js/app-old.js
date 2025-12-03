// app.js - Aplicação principal do VENDA DIRETA PWA
// ✅ VERSÃO MODIFICADA PARA VENDA DIRETA (sem cliente, vendedor opcional)

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
import { mostrarModalPixEstatico } from './pix.js';

// ==========================================================================
// VARIÁVEIS GLOBAIS
// ==========================================================================

let produtos = [];
let colaboradorAtual = null; // Vendedor (opcional)
let formasPagamento = [];

// ==========================================================================
// INICIALIZAÇÃO
// ==========================================================================

async function init() {
    try {
        console.log('[App] 🚀 Iniciando aplicação VENDA DIRETA...');
        console.log('[App] 🏪 Loja ID:', CONFIG.ID_USUARIO_LOJA);
        
        // 1️⃣ Verificar elementos críticos do DOM
        verificarElementosCriticos(ELEMENTOS_CRITICOS);
        
        // 1.5️⃣ Popular opções de parcelas (antes de qualquer outra coisa)
        popularOpcoesParcelas();
        
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
            const registration = await navigator.serviceWorker.register(`${CONFIG.URL_BASE_WEB}/venda-direta/sw.js`);
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
                alert('Venda offline enviada com sucesso!');
                
                // Fechar modal se estiver aberto
                fecharModal('modal-cliente-pedido');
                
            } else if (type === 'SYNC_ERROR') {
                console.error('[App] ❌ Erro na sincronização:', error);
                alert(`Erro ao enviar venda: ${error}`);
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
    const badge = document.getElementById('contador-carrinho');
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
    precoProduto.textContent = formatarMoeda(produto.preco_venda_sugerido);
    inputQtd.value = 1;
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
// FUNÇÃO POPULAR FORMAS DE PAGAMENTO
// ==========================================================================

function popularFormasPagamento(formas) {
    const select = document.getElementById('forma-pagamento');
    if (!select) return;

    select.innerHTML = '';

    if (!formas || formas.length === 0) {
        select.options[0] = new Option('Nenhuma forma de pgto.', '');
        select.disabled = true;
        return;
    }
    
    select.disabled = false;
    select.options[0] = new Option('Selecione o pagamento...', '');
    
    formas.forEach(forma => {
        if (forma.id && forma.nome) {
            select.options[select.options.length] = new Option(forma.nome, forma.id);
        }
    });
    
    // Salva formas de pagamento com tipo para verificação posterior
    formasPagamento = formas;
    console.log('[App] 💳 Formas de pagamento salvas:', formasPagamento);
}

// ==========================================================================
// MODAL DE PEDIDO (VENDA DIRETA)
// ==========================================================================

window.abrirModalPedido = async function() {
    fecharModal('modal-carrinho');
    
    // Limpar formulário
    document.getElementById('form-cliente-pedido').reset();
    colaboradorAtual = null;
    
    // Resetar campos
    document.getElementById('info-vendedor').classList.add('hidden');
    
    // Popular opções de parcelas (sem usar document.write)
    popularOpcoesParcelas();
    
    // Botão sempre habilitado (não precisa de cliente)
    const btnConfirmar = document.getElementById('btn-confirmar-pedido');
    if (btnConfirmar) btnConfirmar.disabled = false;
    
    // Abrir o modal primeiro
    abrirModal('modal-cliente-pedido');
    
    // Carregar formas de pagamento
    try {
        console.log('[App] 💳 Carregando formas de pagamento...');
        const selectPgto = document.getElementById('forma-pagamento');
        if (selectPgto) {
            selectPgto.innerHTML = '<option value="">Carregando...</option>';
            selectPgto.disabled = true;
        }

        const formas = await carregarFormasPagamento(CONFIG.ID_USUARIO_LOJA);
        popularFormasPagamento(formas);
        console.log('[App] ✅ Formas de pagamento carregadas:', formas.length);
    } catch (error) {
        console.error('[App] ❌ Erro ao carregar formas de pagamento:', error);
        popularFormasPagamento([]);
    }
};

// ==========================================================================
// FUNÇÃO PARA POPULAR OPÇÕES DE PARCELAS (SEM document.write)
// ==========================================================================

function popularOpcoesParcelas() {
    const selectParcelas = document.getElementById('numero-parcelas');
    if (!selectParcelas) return;
    
    // Limpar opções existentes (exceto "À vista")
    selectParcelas.innerHTML = '<option value="1">À vista</option>';
    
    // Adicionar opções de 2x até 24x
    for (let i = 2; i <= 24; i++) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = `${i}x`;
        selectParcelas.appendChild(option);
    }
}

// ==========================================================================
// BUSCAR VENDEDOR (OPCIONAL)
// ==========================================================================

window.buscarVendedor = async function() {
    const cpfInput = document.getElementById('vendedor_cpf_busca');
    if (!cpfInput) {
        alert('Campo de CPF do vendedor não encontrado');
        return;
    }
    const cpf = cpfInput.value.replace(/[^\d]/g, '');
    
    if (!cpf || cpf.length === 0) {
        // Se o campo estiver vazio, apenas limpa o vendedor
        colaboradorAtual = null;
        document.getElementById('info-vendedor').classList.add('hidden');
        document.getElementById('colaborador_vendedor_id').value = '';
        return;
    }
    
    if (!validarCPF(cpf)) {
        alert('CPF inválido');
        return;
    }
    
    try {
        const response = await fetch(`${API_ENDPOINTS.COLABORADOR_BUSCA_CPF}?cpf=${cpf}&usuario_id=${CONFIG.ID_USUARIO_LOJA}`);
        
        if (response.ok) {
            const respostaVendedor = await response.json(); 
            
            if (respostaVendedor.existe && respostaVendedor.colaborador) {
                colaboradorAtual = respostaVendedor.colaborador;
                
                document.getElementById('nome-vendedor-info').textContent = colaboradorAtual.nome_completo; 
                document.getElementById('info-vendedor').classList.remove('hidden');
                document.getElementById('colaborador_vendedor_id').value = colaboradorAtual.id;
                
                console.log('[App] ✅ Vendedor encontrado:', respostaVendedor);
            } else {
                alert('Vendedor não encontrado.');
                colaboradorAtual = null;
                document.getElementById('info-vendedor').classList.add('hidden');
                document.getElementById('colaborador_vendedor_id').value = '';
            }
        } else {
            alert('Vendedor não encontrado');
            colaboradorAtual = null;
            document.getElementById('info-vendedor').classList.add('hidden');
            document.getElementById('colaborador_vendedor_id').value = '';
        }
    } catch (error) {
        console.error('[App] Erro ao buscar vendedor:', error);
        alert('Erro ao buscar vendedor');
    }
};

// ==========================================================================
// FINALIZAR PEDIDO (VENDA DIRETA)
// ==========================================================================

window.confirmarPedido = async function() {
    // Validação da Forma de Pagamento
    const selectFormaPagamento = document.getElementById('forma-pagamento');
    const formaPagamentoId = selectFormaPagamento?.value;
    
    console.log('[App] Forma de Pagamento selecionada:', formaPagamentoId);
    console.log('[App] Select element:', selectFormaPagamento);
    
    if (!formaPagamentoId || formaPagamentoId === '') {
        alert('Por favor, selecione uma forma de pagamento.');
        return;
    }
    
    const btnConfirmar = document.getElementById('btn-confirmar-pedido');
    btnConfirmar.disabled = true;
    btnConfirmar.textContent = 'Processando...';
    
    try {
        const dadosPedido = {
            cliente_id: null, // VENDA DIRETA: não precisa de cliente
            observacoes: document.getElementById('observacoes-pedido').value || null,
            colaborador_vendedor_id: colaboradorAtual?.id || null, // Opcional
            forma_pagamento_id: formaPagamentoId, // Já validado acima
            numero_parcelas: parseInt(document.getElementById('numero-parcelas')?.value || 1, 10),
            data_primeiro_pagamento: document.getElementById('data-primeiro-pagamento')?.value || null,
            intervalo_dias_parcelas: parseInt(document.getElementById('intervalo-dias')?.value || 30, 10)
        };
        
        const carrinho = getCarrinho();
        
        console.log('[App] 📤 Enviando venda direta...', dadosPedido);
        console.log('[App] 📤 forma_pagamento_id no payload:', dadosPedido.forma_pagamento_id);
        console.log('[App] 📤 Tipo do forma_pagamento_id:', typeof dadosPedido.forma_pagamento_id);
        
        const resultado = await finalizarPedido(dadosPedido, carrinho);
        
        console.log('[App] 📥 Resultado:', resultado);
        
        if (resultado.sucesso) {
            if (resultado.redirecionado) {
                return; 
            }
            
            if (resultado.mensagem === 'Modal PIX exibido. Aguardando pagamento.') {
                console.log('[App] Modal PIX exibido. Aguardando confirmação...');
                return;
            }

            // Verificar se é venda à vista com PIX estático
            const numeroParcelas = dadosPedido.numero_parcelas;
            const formaPagamentoSelecionada = formasPagamento.find(fp => fp.id === formaPagamentoId);
            const isPix = formaPagamentoSelecionada?.tipo === 'PIX';
            const isVendaAVista = numeroParcelas === 1;
            
            console.log('[App] 🔍 Verificando PIX estático:', { 
                numeroParcelas, 
                isPix, 
                isVendaAVista,
                formaPagamentoId,
                formaPagamento: formaPagamentoSelecionada,
                todasFormas: formasPagamento
            });
            
            // Se for venda à vista com PIX, mostrar modal PIX estático
            if (isVendaAVista && isPix) {
                console.log('[App] ✅ Condições atendidas! Venda à vista com PIX. Mostrando modal...');
                
                // Calcular valor total do carrinho
                const valorTotal = calcularTotalCarrinho();
                console.log('[App] 💰 Valor total calculado:', valorTotal);
                console.log('[App] 📞 Chamando mostrarModalPixEstatico...');
                
                try {
                    // Gera TxID sem caracteres especiais (apenas letras e números)
                    const dataAtual = new Date();
                    const dia = String(dataAtual.getDate()).padStart(2, '0');
                    const mes = String(dataAtual.getMonth() + 1).padStart(2, '0');
                    const ano = dataAtual.getFullYear();
                    const hora = String(dataAtual.getHours()).padStart(2, '0');
                    const minuto = String(dataAtual.getMinutes()).padStart(2, '0');
                    // Formato: VendaDireta + data + hora (sem caracteres especiais)
                    const txId = `VendaDireta${dia}${mes}${ano}${hora}${minuto}`;
                    
                    mostrarModalPixEstatico(valorTotal, txId);
                    console.log('[App] ✅ mostrarModalPixEstatico executado sem erros');
                } catch (error) {
                    console.error('[App] ❌ Erro ao mostrar modal PIX:', error);
                    alert('Erro ao gerar QR Code PIX: ' + error.message);
                }
                
                // Limpar carrinho e fechar modal após mostrar PIX
                if (!resultado.offline) {
                    limparCarrinho();
                    await carregarCarrinhoInicial();
                    atualizarBadgeCarrinho();
                }
                
                fecharModal('modal-cliente-pedido');
                return;
            } else {
                console.log('[App] ⚠️ Condições NÃO atendidas para PIX estático:', {
                    isVendaAVista,
                    isPix,
                    motivo: !isVendaAVista ? 'Não é venda à vista' : 'Não é PIX'
                });
            }

            // Para fluxos normais (offline, boleto, interno)
            alert(resultado.mensagem || 'Venda realizada com sucesso!');
            
            if (!resultado.offline) {
                limparCarrinho();
                await carregarCarrinhoInicial();
                atualizarBadgeCarrinho();
            }
            
            fecharModal('modal-cliente-pedido');

        } else {
            alert(`Erro: ${resultado.mensagem || 'Erro desconhecido ao processar venda.'}`);
        }
        
    } catch (error) {
        console.error('[App] Erro ao finalizar venda:', error);
        alert(`Erro ao finalizar venda: ${error.message}`);
    } finally {
        const modalPix = document.getElementById('modal-pix-asaas');
        if (!modalPix) { 
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = '✅ Confirmar Venda';
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
    
    // Botão de finalizar pedido
    const btnFinalizarPedido = document.getElementById('btn-finalizar-pedido');
    if (btnFinalizarPedido) {
        btnFinalizarPedido.addEventListener('click', window.abrirModalPedido);
    }
    
    // Máscaras de CPF
    const inputsCPF = document.querySelectorAll('[data-mask="cpf"]');
    inputsCPF.forEach(input => {
        input.addEventListener('input', (e) => maskCPF(e.target));
    });
    
    // Máscaras de telefone
    const inputsTel = document.querySelectorAll('[data-mask="phone"]');
    inputsTel.forEach(input => {
        input.addEventListener('input', (e) => maskPhone(e.target));
    });
    
    // Fechar modais ao clicar fora
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal && modal.id !== 'modal-pix-asaas') {
                fecharModal(modal.id);
            }
        });
    });

    // Ouvir confirmação de pagamento do gateway
    window.addEventListener('pagamentoConfirmado', (event) => {
        console.log('[App] 💳 Pagamento confirmado recebido!', event.detail);
        
        alert(`Pagamento confirmado com sucesso!\nVenda ID: ${event.detail.pedidoId || 'N/A'}`);
        
        limparCarrinho();
        atualizarBadgeCarrinho();
        
        fecharModal('modal-cliente-pedido');

        const btnConfirmar = document.getElementById('btn-confirmar-pedido');
        if (btnConfirmar) {
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = '✅ Confirmar Venda';
        }
    });
    
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

