// app.js - Aplicação principal do VENDA DIRETA PWA
import { CONFIG, API_ENDPOINTS, carregarConfigLoja } from './config.js';
import { 
    getCarrinho, setCarrinho, adicionarAoCarrinho, removerDoCarrinho,
    aumentarQuantidadeItem, diminuirQuantidadeItem, calcularTotalCarrinho,
    calcularTotalItens, limparCarrinho, atualizarIndicadoresCarrinho,
    atualizarBadgeProduto
} from './cart.js';
import { carregarCarrinho, limparDadosLocaisPosSinc, carregarFormasPagamentoCache } from './storage.js';
import { finalizarPedido } from './order.js';
import { carregarFormasPagamento } from './payment.js';
import { validarCPF, maskCPF, maskPhone, maskCEP, formatarMoeda, formatarCPF, verificarElementosCriticos } from './utils.js';
import { ELEMENTOS_CRITICOS } from './config.js';
import { mostrarModalPixEstatico } from './pix.js'; // Importação do novo módulo
import { verificarAutenticacao, getColaboradorData } from './auth.js'; // Importação do módulo de autenticação
import { buscarClientePorCpf, cadastrarCliente, getClienteAtual, setClienteAtual } from './customer.js'; // Importação do módulo de cliente

// Variáveis Globais
let produtos = [];
let produtosFiltrados = []; // Produtos filtrados pela busca
let colaboradorAtual = null;
let formasPagamento = [];
let usuarioData = null;
let clienteAtual = null; // Cliente para vendas parceladas

// Disponibiliza CONFIG no window para compatibilidade com módulos que não usam import
window.CONFIG = CONFIG;

// Inicialização
async function init() {
    try {
        console.log('[App] 🚀 Iniciando aplicação VENDA DIRETA...');
        
        // Verificar autenticação primeiro
        usuarioData = await verificarAutenticacao();
        if (!usuarioData) {
            console.error('[App] ❌ Falha na autenticação');
            return;
        }
        
        verificarElementosCriticos(ELEMENTOS_CRITICOS);
        popularOpcoesParcelas();
        await carregarConfigLoja();
        await carregarLogoEmpresa(); // Carrega logo da empresa
        await registrarServiceWorker();
        await carregarCarrinhoInicial();
        await carregarProdutos();
        inicializarEventListeners();
        inicializarBuscaProdutos(); // Inicializa o filtro de busca
        configurarListenerServiceWorker();
        
        // ✅ Verifica se há comprovante para exibir após reload
        verificarComprovantePosReload();
        atualizarBadgeCarrinho();
        inicializarMonitoramentoRede();
        
        console.log('[App] ✅ Aplicação inicializada!');
    } catch (error) {
        console.error('[App] ❌ Erro na inicialização:', error);
    }
}

// Carregar Logo da Empresa
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

// Service Worker Registration
async function registrarServiceWorker() {
    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.register(`${CONFIG.URL_BASE_WEB}/venda-direta/sw.js`);
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
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
    }
}

function configurarListenerServiceWorker() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', async (event) => {
            const { type, pedido, error } = event.data;
            if (type === 'SYNC_SUCCESS') {
                await limparDadosLocaisPosSinc();
                await carregarCarrinhoInicial();
                atualizarBadgeCarrinho();
                renderizarCarrinho();
                alert('Venda offline enviada com sucesso!');
                fecharModal('modal-cliente-pedido');
            } else if (type === 'SYNC_ERROR') {
                alert(`Erro ao enviar venda: ${error}`);
            }
        });
    }
}

// Carrinho
async function carregarCarrinhoInicial() {
    try {
        const carrinhoSalvo = await carregarCarrinho();
        setCarrinho(carrinhoSalvo);
    } catch (error) {
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
    if (btnCarrinho) btnCarrinho.disabled = totalItens === 0;
}

function renderizarCarrinho() {
    const container = document.getElementById('itens-carrinho');
    const totalElement = document.getElementById('valor-total-carrinho');
    const totalItensFooter = document.getElementById('total-itens-footer');
    const btnFinalizar = document.getElementById('btn-finalizar-pedido');
    
    const carrinho = getCarrinho();
    
    if (carrinho.length === 0) {
        if (container) container.innerHTML = '<p id="carrinho-vazio-msg" class="text-center text-gray-500 py-8">Seu carrinho está vazio</p>';
        if (btnFinalizar) btnFinalizar.disabled = true;
        if (totalElement) totalElement.textContent = 'R$ 0,00';
        if (totalItensFooter) totalItensFooter.textContent = '0';
        return;
    }
    
    if (btnFinalizar) btnFinalizar.disabled = false;
    
    container.innerHTML = carrinho.map((item, index) => {
        let urlImagem = 'https://dummyimage.com/100x100/cccccc/ffffff.png&text=Sem+Imagem';
        if (item.fotos && item.fotos.length > 0 && item.fotos[0].arquivo_path) {
            // Remove barra inicial se existir e constrói URL correta
            const arquivoPath = item.fotos[0].arquivo_path.replace(/^\//, '');
            // Garante que URL_BASE_WEB não termine com / e arquivoPath não comece com /
            const baseUrl = CONFIG.URL_BASE_WEB.replace(/\/$/, '');
            urlImagem = `${baseUrl}/${arquivoPath}`;
        }
        const subtotal = item.preco_venda_sugerido * item.quantidade;
        return `
        <div class="cart-item">
            <button onclick="removerItem(${index})" class="cart-item-remove" title="Remover item">✖</button>
            <div class="cart-item-container">
                <img src="${urlImagem}" alt="${item.nome}" class="cart-item-image">
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
        </div>`;
    }).join('');
    
    if (totalElement) totalElement.textContent = formatarMoeda(calcularTotalCarrinho());
    if (totalItensFooter) totalItensFooter.textContent = calcularTotalItens();
}

window.aumentarQtd = function(produtoId) {
    if (aumentarQuantidadeItem(produtoId)) { renderizarCarrinho(); atualizarBadgeCarrinho(); }
};
window.diminuirQtd = function(produtoId) {
    if (diminuirQuantidadeItem(produtoId)) { renderizarCarrinho(); atualizarBadgeCarrinho(); }
};
window.removerItem = function(index) {
    const produtoId = removerDoCarrinho(index);
    if (produtoId) { atualizarBadgeProduto(produtoId, false); renderizarCarrinho(); atualizarBadgeCarrinho(); }
};

// Produtos
async function carregarProdutos() {
    try {
        const url = `${API_ENDPOINTS.PRODUTO}?usuario_id=${CONFIG.ID_USUARIO_LOJA}`;
        const response = await fetch(url);
        if (!response.ok) throw new Error(`Erro ${response.status}`);
        produtos = await response.json();
        produtosFiltrados = produtos; // Inicializa com todos os produtos
        filtrarProdutos(); // Aplica filtro atual (se houver)
    } catch (error) {
        console.error('Erro ao carregar produtos:', error);
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
    if (!container) return;
    
    if (listaProdutos.length === 0) {
        const termoBusca = document.getElementById('busca-produto')?.value?.trim() || '';
        if (termoBusca) {
            container.innerHTML = `<div class="col-span-full text-center py-16">
                <svg class="h-16 w-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <p class="text-gray-600 font-medium">Nenhum produto encontrado</p>
                <p class="text-sm text-gray-500 mt-2">Tente buscar com outro termo</p>
            </div>`;
        } else {
            container.innerHTML = `<div class="col-span-full text-center py-16"><p>Nenhum produto disponível.</p></div>`;
        }
        return;
    }
    
    container.innerHTML = listaProdutos.map(produto => {
        let urlImagem = 'https://dummyimage.com/300x200/cccccc/ffffff.png&text=Sem+Imagem';
        if (produto.fotos && produto.fotos.length > 0 && produto.fotos[0].arquivo_path) {
            const arquivoPath = produto.fotos[0].arquivo_path.replace(/^\//, '');
            const baseUrl = CONFIG.URL_BASE_WEB.replace(/\/$/, '');
            urlImagem = `${baseUrl}/${arquivoPath}`;
        }
        return `
        <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl relative" data-produto-card="${produto.id}">
            <div class="badge-no-carrinho hidden absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full z-10">✓ No Carrinho</div>
            <img src="${urlImagem}" alt="${produto.nome}" class="w-full h-48 object-cover">
            <div class="p-4">
                <h3 class="text-lg font-bold text-gray-800 mb-2">${produto.nome}</h3>
                <div class="flex items-center justify-between mb-4">
                    <span class="text-2xl font-bold text-blue-600">${formatarMoeda(produto.preco_venda_sugerido)}</span>
                    <span class="text-xs ${produto.estoque_atual > 0 ? 'text-green-600' : 'text-red-600'} font-semibold">
                        ${produto.estoque_atual > 0 ? `${produto.estoque_atual} em estoque` : 'Sem estoque'}
                    </span>
                </div>
                <button onclick="abrirModalQuantidade('${produto.id}')" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg" ${produto.estoque_atual <= 0 ? 'disabled opacity-50' : ''}>
                    🛒 Adicionar
                </button>
            </div>
        </div>`;
    }).join('');
}

window.abrirModalQuantidade = function(produtoId) {
    const produto = produtos.find(p => p.id === produtoId);
    if (!produto) return;
    
    const inputQtd = document.getElementById('input-quantidade');
    document.getElementById('nome-produto-modal').textContent = produto.nome;
    document.getElementById('preco-produto-modal').textContent = formatarMoeda(produto.preco_venda_sugerido);
    inputQtd.value = 1;
    inputQtd.max = produto.estoque_atual;
    
    document.getElementById('btn-confirmar-adicionar').onclick = () => {
        const quantidade = parseInt(inputQtd.value, 10);
        if (quantidade > 0 && quantidade <= produto.estoque_atual) {
            const arquivoPath = produto.fotos?.[0]?.arquivo_path?.replace(/^\//, '') || '';
            const baseUrl = CONFIG.URL_BASE_WEB.replace(/\/$/, '');
            const produtoComImagem = { ...produto, imagem: arquivoPath ? `${baseUrl}/${arquivoPath}` : null };
            if (adicionarAoCarrinho(produtoComImagem, quantidade)) {
                atualizarBadgeProduto(produtoId, true);
                atualizarBadgeCarrinho();
                fecharModal('modal-quantidade');
            }
        } else {
            alert(`Quantidade inválida. Máximo: ${produto.estoque_atual}`);
        }
    };
    abrirModal('modal-quantidade');
};

window.abrirCarrinho = function() { renderizarCarrinho(); abrirModal('modal-carrinho'); };

function popularFormasPagamento(formas, usandoCache = false) {
    const select = document.getElementById('forma-pagamento');
    if (!select) return;
    
    // Remove listener anterior se existir (evita duplicação)
    const novoSelect = select.cloneNode(true);
    select.parentNode.replaceChild(novoSelect, select);
    const selectAtual = document.getElementById('forma-pagamento');
    
    selectAtual.innerHTML = '<option value="">Selecione o pagamento...</option>';
    
    // Remove avisos anteriores se existirem
    const avisoAnterior = document.querySelector('.aviso-offline-pagamento');
    if (avisoAnterior) {
        avisoAnterior.remove();
    }
    
    if (formas && formas.length > 0) {
        selectAtual.disabled = false;
        formas.forEach(forma => {
            const option = new Option(forma.nome, forma.id);
            // Armazena o tipo no atributo data-tipo para facilitar acesso
            option.setAttribute('data-tipo', forma.tipo || '');
            selectAtual.options[selectAtual.options.length] = option;
        });
        formasPagamento = formas;
        // Disponibiliza globalmente para validação em order.js
        window.formasPagamento = formas;
        
        // Adiciona listener para controlar parcelas baseado na forma de pagamento
        selectAtual.addEventListener('change', controlarParcelasPorFormaPagamento);
        
        // Mostra aviso se estiver usando cache offline
        if (usandoCache && !navigator.onLine) {
            const avisoOffline = document.createElement('div');
            avisoOffline.className = 'bg-yellow-50 border border-yellow-200 rounded-lg p-2 mt-2 text-xs text-yellow-800 aviso-offline-pagamento';
            avisoOffline.innerHTML = 'ℹ️ <strong>Modo Offline:</strong> Usando formas de pagamento salvas. Algumas opções podem estar desatualizadas.';
            const parent = selectAtual.parentElement;
            if (parent) {
                parent.appendChild(avisoOffline);
            }
        }
    } else {
        selectAtual.options[0] = new Option('Nenhuma forma de pgto.', '');
        selectAtual.disabled = true;
        
        // Se estiver offline e não tiver formas, mostra mensagem
        if (!navigator.onLine) {
            const avisoOffline = document.createElement('div');
            avisoOffline.className = 'bg-red-50 border border-red-200 rounded-lg p-2 mt-2 text-xs text-red-800 aviso-offline-pagamento';
            avisoOffline.innerHTML = '⚠️ <strong>Modo Offline:</strong> Nenhuma forma de pagamento encontrada no cache. Conecte-se à internet para carregar as opções.';
            const parent = selectAtual.parentElement;
            if (parent) {
                parent.appendChild(avisoOffline);
            }
        }
    }
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
    
    // Campo de cliente (apenas para vendas parceladas)
    const campoCliente = document.getElementById('campo-cliente-parcelado');
    
    // Se for DINHEIRO, PIX ou PIX ESTATICO, desabilita parcelamento
    if (tipo === 'DINHEIRO' || tipo === 'PIX' || tipo === 'PIX_ESTATICO') {
        // SEMPRE força para "À vista" - IMPORTANTE: fazer ANTES de desabilitar
        selectParcelas.value = '1';
        // Dispara evento change para atualizar campos relacionados
        selectParcelas.dispatchEvent(new Event('change', { bubbles: true }));
        selectParcelas.disabled = true;
        
        // Oculta campos de parcelamento e cliente
        if (campoDataPrimeiroPagamento) {
            campoDataPrimeiroPagamento.classList.add('hidden');
            campoDataPrimeiroPagamento.value = '';
        }
        if (campoIntervaloParcelas) {
            campoIntervaloParcelas.classList.add('hidden');
        }
        if (campoCliente) {
            campoCliente.classList.add('hidden');
            // Limpa dados do cliente
            clienteAtual = null;
            setClienteAtual(null);
            document.getElementById('cliente_id').value = '';
            document.getElementById('info-cliente').classList.add('hidden');
            document.getElementById('msg-cadastrar-cliente').classList.add('hidden');
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
            // Mostra campo de cliente para vendas parceladas
            if (campoCliente) {
                campoCliente.classList.remove('hidden');
            }
        } else {
            if (campoDataPrimeiroPagamento) {
                campoDataPrimeiroPagamento.classList.add('hidden');
            }
            if (campoIntervaloParcelas) {
                campoIntervaloParcelas.classList.add('hidden');
            }
            // Oculta campo de cliente para vendas à vista
            if (campoCliente) {
                campoCliente.classList.add('hidden');
                clienteAtual = null;
                setClienteAtual(null);
                document.getElementById('cliente_id').value = '';
                document.getElementById('info-cliente').classList.add('hidden');
                document.getElementById('msg-cadastrar-cliente').classList.add('hidden');
            }
        }
        
        console.log('[App] ✅ Parcelamento habilitado para forma de pagamento:', tipo);
    }
}

window.abrirModalPedido = async function() {
    fecharModal('modal-carrinho');
    document.getElementById('form-cliente-pedido').reset();
    colaboradorAtual = null;
    clienteAtual = null;
    setClienteAtual(null);
    document.getElementById('info-vendedor').classList.add('hidden');
    document.getElementById('info-cliente').classList.add('hidden');
    document.getElementById('msg-cadastrar-cliente').classList.add('hidden');
    document.getElementById('campo-cliente-parcelado').classList.add('hidden');
    popularOpcoesParcelas();
    abrirModal('modal-cliente-pedido');
    
    // Preencher automaticamente CPF do vendedor se o usuário logado for vendedor
    preencherDadosVendedor();
    
    try {
        const estaOffline = !navigator.onLine;
        const formas = await carregarFormasPagamento(CONFIG.ID_USUARIO_LOJA);
        
        // Remove avisos anteriores se existirem
        const avisoAnterior = document.querySelector('.aviso-offline-pagamento');
        if (avisoAnterior) {
            avisoAnterior.remove();
        }
        
        // Passa flag indicando se está usando cache
        popularFormasPagamento(formas, estaOffline);
        
        // Mostra aviso se estiver offline e usando cache
        if (estaOffline && formas.length > 0) {
            console.log('[App] ℹ️ Usando formas de pagamento do cache (modo offline)');
        }
        
        // Verifica se já há uma forma selecionada (após popular)
        // Usa setTimeout para garantir que o DOM foi atualizado
        setTimeout(() => {
            controlarParcelasPorFormaPagamento();
            // Força novamente após um pequeno delay para garantir
            setTimeout(() => controlarParcelasPorFormaPagamento(), 50);
        }, 100);
    } catch (error) {
        console.error('[App] ❌ Erro ao carregar formas de pagamento:', error);
        // Tenta carregar do cache mesmo em caso de erro
        try {
            const formasCache = await carregarFormasPagamentoCache();
            if (formasCache.length > 0) {
                console.log('[App] 📦 Usando formas de pagamento do cache após erro');
                popularFormasPagamento(formasCache, !navigator.onLine);
            } else {
                popularFormasPagamento([]);
            }
        } catch (cacheError) {
            console.error('[App] ❌ Erro ao carregar do cache:', cacheError);
            popularFormasPagamento([]);
        }
    }
};

/**
 * Preenche automaticamente os dados do vendedor se o usuário logado for colaborador/vendedor
 */
function preencherDadosVendedor() {
    const colaborador = getColaboradorData();
    if (colaborador && colaborador.cpf) {
        const cpfInput = document.getElementById('vendedor_cpf_busca');
        if (cpfInput) {
            // Formata o CPF com máscara (formato: 000.000.000-00)
            const cpfLimpo = colaborador.cpf.replace(/[^\d]/g, '');
            const cpfFormatado = cpfLimpo.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            cpfInput.value = cpfFormatado;
            
            // Define o colaborador atual
            colaboradorAtual = colaborador;
            const colaboradorIdInput = document.getElementById('colaborador_vendedor_id');
            if (colaboradorIdInput) {
                colaboradorIdInput.value = colaborador.id;
            }
            
            // Mostra informações do vendedor
            const nomeVendedorInfo = document.getElementById('nome-vendedor-info');
            const infoVendedor = document.getElementById('info-vendedor');
            if (nomeVendedorInfo) {
                nomeVendedorInfo.textContent = colaborador.nome_completo;
            }
            if (infoVendedor) {
                infoVendedor.classList.remove('hidden');
            }
            
            console.log('[App] ✅ CPF do vendedor preenchido automaticamente:', cpfFormatado);
        }
    }
}

function popularOpcoesParcelas() {
    const selectParcelas = document.getElementById('numero-parcelas');
    if (!selectParcelas) return;
    selectParcelas.innerHTML = '<option value="1">À vista</option>';
    for (let i = 2; i <= 24; i++) {
        const option = document.createElement('option');
        option.value = i; option.textContent = `${i}x`;
        selectParcelas.appendChild(option);
    }
}

window.buscarVendedor = async function() {
    const cpfInput = document.getElementById('vendedor_cpf_busca');
    const cpf = cpfInput.value.replace(/[^\d]/g, '');
    if (!cpf) { colaboradorAtual = null; document.getElementById('info-vendedor').classList.add('hidden'); return; }
    
    try {
        const response = await fetch(`${API_ENDPOINTS.COLABORADOR_BUSCA_CPF}?cpf=${cpf}&usuario_id=${CONFIG.ID_USUARIO_LOJA}`);
        if (response.ok) {
            const data = await response.json();
            if (data.existe && data.colaborador) {
                colaboradorAtual = data.colaborador;
                document.getElementById('nome-vendedor-info').textContent = colaboradorAtual.nome_completo;
                document.getElementById('info-vendedor').classList.remove('hidden');
                document.getElementById('colaborador_vendedor_id').value = colaboradorAtual.id;
            } else { alert('Vendedor não encontrado'); }
        }
    } catch (error) { alert('Erro ao buscar vendedor'); }
};

// 🔥 FUNÇÃO PRINCIPAL DE VENDA COM PIX ESTÁTICO INTEGRADO 🔥
window.confirmarPedido = async function() {
    const formaPagamentoId = document.getElementById('forma-pagamento')?.value;
    if (!formaPagamentoId) { alert('Selecione uma forma de pagamento.'); return; }
    
    const btnConfirmar = document.getElementById('btn-confirmar-pedido');
    btnConfirmar.disabled = true;
    btnConfirmar.textContent = 'Processando...';
    
    try {
        // Verifica se a forma de pagamento permite parcelamento antes de pegar o valor
        const formaPagamentoSelecionada = formasPagamento.find(fp => fp.id === formaPagamentoId);
        const tipoFormaPagamento = formaPagamentoSelecionada?.tipo || '';
        const permiteParcelamento = tipoFormaPagamento !== 'DINHEIRO' && tipoFormaPagamento !== 'PIX' && tipoFormaPagamento !== 'PIX_ESTATICO';
        
        // Se não permite parcelamento, força para 1 parcela
        const selectParcelas = document.getElementById('numero-parcelas');
        let numeroParcelas = parseInt(selectParcelas?.value || 1, 10);
        if (!permiteParcelamento && numeroParcelas > 1) {
            numeroParcelas = 1;
            if (selectParcelas) {
                selectParcelas.value = '1';
            }
        }
        
        // Para vendas parceladas, cliente é obrigatório
        let clienteId = null;
        if (numeroParcelas > 1) {
            clienteId = clienteAtual?.id || document.getElementById('cliente_id')?.value || null;
            if (!clienteId) {
                alert('Para vendas parceladas, é necessário buscar e cadastrar o cliente.');
                document.getElementById('campo-cliente-parcelado').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
        }
        
        const dadosPedido = {
            cliente_id: clienteId,
            observacoes: document.getElementById('observacoes-pedido').value || null,
            colaborador_vendedor_id: colaboradorAtual?.id || null,
            forma_pagamento_id: formaPagamentoId,
            numero_parcelas: numeroParcelas,
            data_primeiro_pagamento: permiteParcelamento && numeroParcelas > 1 ? document.getElementById('data-primeiro-pagamento')?.value || null : null,
            intervalo_dias_parcelas: permiteParcelamento && numeroParcelas > 1 ? parseInt(document.getElementById('intervalo-dias')?.value || 30, 10) : null
        };
        
        const carrinho = getCarrinho();
        const resultado = await finalizarPedido(dadosPedido, carrinho);
        
        console.log('[App] 🔍 DEBUG - Resultado completo do finalizarPedido:', {
            sucesso: resultado.sucesso,
            tem_dados: !!resultado.dados,
            tipo_dados: typeof resultado.dados,
            chaves_dados: resultado.dados ? Object.keys(resultado.dados) : [],
            tem_parcelas: !!resultado.dados?.parcelas,
            parcelas_tipo: typeof resultado.dados?.parcelas,
            parcelas_length: resultado.dados?.parcelas?.length || 0,
            dados_raw: resultado.dados,
            resultado_completo: resultado  // Adiciona o resultado completo para debug
        });
        
        if (resultado.sucesso && !resultado.offline) {
            // ✅ NOVO FLUXO: NÃO confirma recebimento imediatamente
            // Confirmação só acontece quando usuário clicar em "Confirmar Recebimento"
            const vendaId = resultado.dados?.id || resultado.dados?.venda?.id || resultado.id;
            
            if (!vendaId) {
                console.error('[App] ❌ ID da venda não encontrado no resultado');
                alert('Erro: ID da venda não encontrado.');
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = '✅ Confirmar Venda';
                return;
            }

            // Verifica o tipo de pagamento para decidir o fluxo
            const formaPagamentoSelecionada = formasPagamento.find(fp => fp.id == formaPagamentoId);
            const tipoPagamento = formaPagamentoSelecionada?.tipo || '';
            const isPix = tipoPagamento === 'PIX' || tipoPagamento === 'PIX_ESTATICO';
            const isDinheiro = tipoPagamento === 'DINHEIRO';
            const isVista = dadosPedido.numero_parcelas === 1;

            // PIX ESTÁTICO: Mostra modal PIX e aguarda confirmação do usuário
            if (isPix && isVista) {
                console.log('[App] 🟢 Venda PIX à vista detectada. Mostrando modal de confirmação...');
                
                const valorTotal = calcularTotalCarrinho();
                
                // Gera TxID limpo: VendaDireta + DDMMYYYY + HHMM
                const now = new Date();
                const dia = String(now.getDate()).padStart(2, '0');
                const mes = String(now.getMonth() + 1).padStart(2, '0');
                const ano = String(now.getFullYear());
                const hora = String(now.getHours()).padStart(2, '0');
                const minuto = String(now.getMinutes()).padStart(2, '0');
                const txId = `VendaDireta${dia}${mes}${ano}${hora}${minuto}`;

                // Abre o Modal PIX - NÃO limpa carrinho ainda, NÃO confirma recebimento
                await mostrarModalPixEstatico(valorTotal, txId, {
                    ...dadosPedido,
                    venda_id: vendaId,
                    itens: carrinho,
                    valorTotal: valorTotal
                }, CONFIG.ID_USUARIO_LOJA);
                
                // Fecha modal de pedido mas mantém carrinho
                fecharModal('modal-cliente-pedido');
                
                // Restaura botão
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = '✅ Confirmar Venda';
                return; // Encerra aqui - confirmação será feita quando usuário clicar em "Confirmar Recebimento"
            }

            // DINHEIRO: Mostra modal de confirmação similar ao PIX
            if (isDinheiro && isVista) {
                console.log('[App] 💵 Venda DINHEIRO à vista detectada. Mostrando modal de confirmação...');
                
                const valorTotal = calcularTotalCarrinho();
                
                // Mostra modal de confirmação para dinheiro
                await mostrarModalDinheiro(valorTotal, {
                    ...dadosPedido,
                    venda_id: vendaId,
                    itens: carrinho,
                    valorTotal: valorTotal
                });
                
                // Fecha modal de pedido mas mantém carrinho
                fecharModal('modal-cliente-pedido');
                
                // Restaura botão
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = '✅ Confirmar Venda';
                return; // Encerra aqui - confirmação será feita quando usuário clicar em "Confirmar Recebimento"
            }

            // Outros pagamentos (cartão, boleto, etc): Confirma recebimento imediatamente
            console.log('[App] 🔄 Confirmando recebimento da venda:', vendaId);
            
            try {
                // Chama endpoint de confirmação de recebimento
                const { API_ENDPOINTS } = await import('./config.js');
                const response = await fetch(API_ENDPOINTS.PEDIDO_CONFIRMAR_RECEBIMENTO, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ venda_id: vendaId })
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`Erro ao confirmar recebimento: ${response.status} - ${errorText}`);
                }

                const vendaConfirmada = await response.json();
                console.log('[App] ✅ Recebimento confirmado com sucesso!', vendaConfirmada);

                // ✅ NOVO: Recarrega página PRIMEIRO para atualizar estoques
                console.log('[App] 🔄 Recarregando página para atualizar estoques...');
                
                // Salva dados da venda no sessionStorage para exibir comprovante após reload
                sessionStorage.setItem('venda_confirmada_comprovante', JSON.stringify({
                    venda: vendaConfirmada,
                    dadosPedido: dadosPedido,
                    carrinho: carrinho,
                    formaPagamento: formaPagamentoSelecionada?.nome || 'Não informado'
                }));
                
                // Limpa carrinho antes do reload
                limparCarrinho();
                fecharModal('modal-cliente-pedido');
                
                // Recarrega página imediatamente
                window.location.reload();
                
            } catch (error) {
                console.error('[App] ❌ Erro ao confirmar recebimento:', error);
                alert('Erro ao confirmar recebimento: ' + error.message);
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = '✅ Confirmar Venda';
                return;
            }
        } else if (resultado.sucesso && resultado.offline) {
            // Offline: venda salva localmente
            alert(resultado.mensagem || 'Venda salva localmente. Será enviada quando a conexão for restaurada.');
            fecharModal('modal-cliente-pedido');
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = '✅ Confirmar Venda';
        } else {
            alert(`Erro: ${resultado.mensagem}`);
        }
    } catch (error) {
        console.error('Erro:', error);
        alert(`Erro ao finalizar venda: ${error.message}`);
    } finally {
        // Só reabilita se não tiver aberto o modal PIX (que reabilita antes do return)
        if (document.getElementById('modal-pix-estatico').classList.contains('hidden')) {
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = '✅ Confirmar Venda';
        }
    }
};

function abrirModal(id) { document.getElementById(id)?.classList.remove('hidden'); document.getElementById(id)?.classList.add('flex'); }
function fecharModal(id) { document.getElementById(id)?.classList.add('hidden'); document.getElementById(id)?.classList.remove('flex'); }

window.abrirModal = abrirModal;
window.fecharModal = fecharModal;

function inicializarEventListeners() {
    document.getElementById('btn-abrir-carrinho')?.addEventListener('click', window.abrirCarrinho);
    document.getElementById('btn-finalizar-pedido')?.addEventListener('click', window.abrirModalPedido);
    document.querySelectorAll('[data-mask="cpf"]').forEach(i => i.addEventListener('input', e => maskCPF(e.target)));
    document.querySelectorAll('[data-mask="phone"]').forEach(i => i.addEventListener('input', e => maskPhone(e.target)));
    document.querySelectorAll('[data-mask="cep"]').forEach(i => i.addEventListener('input', e => maskCEP(e.target)));
    document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', e => { if (e.target === m) fecharModal(m.id); }));
    
    // Listener para mudança no número de parcelas
    const selectParcelas = document.getElementById('numero-parcelas');
    if (selectParcelas) {
        selectParcelas.addEventListener('change', function() {
            const numeroParcelas = parseInt(this.value, 10) || 1;
            const campoDataPrimeiroPagamento = document.getElementById('campo-data-primeiro-pagamento');
            const campoIntervaloParcelas = document.getElementById('campo-intervalo-parcelas');
            
            const campoCliente = document.getElementById('campo-cliente-parcelado');
            
            if (numeroParcelas > 1) {
                if (campoDataPrimeiroPagamento) {
                    campoDataPrimeiroPagamento.classList.remove('hidden');
                }
                if (campoIntervaloParcelas) {
                    campoIntervaloParcelas.classList.remove('hidden');
                }
                // Mostra campo de cliente para vendas parceladas
                if (campoCliente) {
                    campoCliente.classList.remove('hidden');
                }
            } else {
                if (campoDataPrimeiroPagamento) {
                    campoDataPrimeiroPagamento.classList.add('hidden');
                }
                if (campoIntervaloParcelas) {
                    campoIntervaloParcelas.classList.add('hidden');
                }
                // Oculta campo de cliente para vendas à vista
                if (campoCliente) {
                    campoCliente.classList.add('hidden');
                    clienteAtual = null;
                    setClienteAtual(null);
                    document.getElementById('cliente_id').value = '';
                    document.getElementById('info-cliente').classList.add('hidden');
                    document.getElementById('msg-cadastrar-cliente').classList.add('hidden');
                }
            }
        });
    }
}

// ==========================================================================
// BUSCA E CADASTRO DE CLIENTE (para vendas parceladas)
// ==========================================================================

window.buscarClienteVendaDireta = async function() {
    const cpfInput = document.getElementById('cliente-cpf-busca');
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
        const resultado = await buscarClientePorCpf(cpf, CONFIG.ID_USUARIO_LOJA);
        
        if (resultado.existe && resultado.cliente) {
            clienteAtual = resultado.cliente;
            setClienteAtual(resultado.cliente);
            
            const nomeCliente = clienteAtual.nome_completo || clienteAtual.nome || 'Cliente encontrado';
            document.getElementById('nome-cliente-info').textContent = nomeCliente;
            document.getElementById('info-cliente').classList.remove('hidden');
            document.getElementById('msg-cadastrar-cliente').classList.add('hidden');
            
            const clienteId = clienteAtual.id || clienteAtual.cliente?.id;
            const inputClienteId = document.getElementById('cliente_id');
            if (inputClienteId && clienteId) {
                inputClienteId.value = clienteId;
            }
            
            console.log('[App] ✅ Cliente encontrado:', clienteAtual);
        } else {
            // Cliente não encontrado - mostra opção de cadastro
            clienteAtual = null;
            setClienteAtual(null);
            document.getElementById('info-cliente').classList.add('hidden');
            document.getElementById('msg-cadastrar-cliente').classList.remove('hidden');
            
            // Preenche CPF no modal de cadastro
            const cadastroCpf = document.getElementById('cadastro-cpf');
            if (cadastroCpf) {
                cadastroCpf.value = cpfInput.value;
            }
            
            console.log('[App] ⚠️ Cliente não encontrado');
        }
    } catch (error) {
        console.error('[App] Erro ao buscar cliente:', error);
        alert('Erro ao buscar cliente: ' + error.message);
    }
};

window.abrirModalCadastroCliente = function() {
    const cpfInput = document.getElementById('cliente-cpf-busca');
    const cadastroCpf = document.getElementById('cadastro-cpf');
    
    // Preenche CPF se já foi buscado
    if (cpfInput && cadastroCpf) {
        cadastroCpf.value = cpfInput.value;
    }
    
    fecharModal('modal-cliente-pedido');
    abrirModal('modal-cadastro-cliente');
};

window.salvarClienteVendaDireta = async function() {
    const form = document.getElementById('form-cadastro-cliente');
    if (!form) return;
    
    const btnSalvar = document.getElementById('btn-salvar-cliente');
    btnSalvar.disabled = true;
    btnSalvar.textContent = 'Salvando...';
    
    try {
        const formData = new FormData(form);
        const dadosCliente = {
            nome_completo: formData.get('nome_completo'),
            cpf: formData.get('cpf'),
            telefone: formData.get('telefone'),
            email: formData.get('email') || null,
            senha: formData.get('senha'),
            endereco_logradouro: formData.get('endereco_logradouro'),
            endereco_numero: formData.get('endereco_numero'),
            endereco_complemento: formData.get('endereco_complemento') || null,
            endereco_bairro: formData.get('endereco_bairro'),
            endereco_cidade: formData.get('endereco_cidade'),
            endereco_estado: formData.get('endereco_estado')?.toUpperCase() || null,
            endereco_cep: formData.get('endereco_cep') || null,
        };
        
        const cliente = await cadastrarCliente(dadosCliente);
        
        clienteAtual = cliente;
        setClienteAtual(cliente);
        
        // Preenche campos no modal de pedido
        const inputClienteId = document.getElementById('cliente_id');
        if (inputClienteId) {
            inputClienteId.value = cliente.id;
        }
        
        const nomeClienteInfo = document.getElementById('nome-cliente-info');
        if (nomeClienteInfo) {
            nomeClienteInfo.textContent = cliente.nome_completo;
        }
        
        document.getElementById('info-cliente').classList.remove('hidden');
        document.getElementById('msg-cadastrar-cliente').classList.add('hidden');
        
        // Preenche CPF no campo de busca
        const cpfBusca = document.getElementById('cliente-cpf-busca');
        if (cpfBusca) {
            const cpfFormatado = formatarCPF(cliente.cpf);
            cpfBusca.value = cpfFormatado;
        }
        
        // Fecha modal de cadastro e volta para o modal de pedido
        fecharModal('modal-cadastro-cliente');
        abrirModal('modal-cliente-pedido');
        
        alert('Cliente cadastrado com sucesso!');
        
    } catch (error) {
        console.error('[App] Erro ao cadastrar cliente:', error);
        alert('Erro ao cadastrar cliente: ' + error.message);
    } finally {
        btnSalvar.disabled = false;
        btnSalvar.textContent = 'Salvar Cliente';
    }
};

/**
 * Inicializa monitoramento de status online/offline
 */
function inicializarMonitoramentoRede() {
    const htmlTag = document.documentElement;
    
    function atualizarStatusOnline() {
        const isOnline = navigator.onLine;
        
        // Remove ambas as classes primeiro
        htmlTag.classList.remove('online', 'offline');
        
        if (isOnline) {
            htmlTag.classList.add('online');
            console.log('[Network] ✅ Status: ONLINE');
        } else {
            htmlTag.classList.add('offline');
            console.log('[Network] ⚠️ Status: OFFLINE');
        }
    }
    
    // Verifica status inicial imediatamente
    atualizarStatusOnline();
    
    // Adiciona listeners para mudanças de status
    window.addEventListener('online', async () => {
        // Atualiza cache de formas de pagamento quando voltar online
        try {
            console.log('[App] 🔄 Conexão restaurada: atualizando cache de formas de pagamento...');
            const formas = await carregarFormasPagamento(CONFIG.ID_USUARIO_LOJA);
            if (formas.length > 0) {
                console.log('[App] ✅ Cache de formas de pagamento atualizado');
                // Se o modal de pedido estiver aberto, atualiza as opções
                const modalPedido = document.getElementById('modal-cliente-pedido');
                if (modalPedido && !modalPedido.classList.contains('hidden')) {
                    popularFormasPagamento(formas, false);
                }
            }
        } catch (error) {
            console.warn('[App] ⚠️ Erro ao atualizar cache de formas de pagamento:', error);
        }
        atualizarStatusOnline();
        console.log('[Network] 🌐 Conexão restaurada');
    });
    
    window.addEventListener('offline', () => {
        atualizarStatusOnline();
        console.log('[Network] 📴 Conexão perdida');
    });
    
    // Verificação adicional: atualiza periodicamente (a cada 5 segundos) para garantir
    // Isso ajuda em casos onde navigator.onLine pode estar desatualizado
    setInterval(() => {
        atualizarStatusOnline();
    }, 5000);
}

/**
 * Verifica se há comprovante salvo no sessionStorage para exibir após reload
 */
async function verificarComprovantePosReload() {
    try {
        const dadosSalvos = sessionStorage.getItem('venda_confirmada_comprovante');
        if (!dadosSalvos) {
            return; // Não há comprovante para exibir
        }
        
        const dados = JSON.parse(dadosSalvos);
        sessionStorage.removeItem('venda_confirmada_comprovante'); // Remove para não exibir novamente
        
        console.log('[App] 📋 Exibindo comprovante após reload...', dados);
        
        // Aguarda um pouco para garantir que a página carregou completamente
        await new Promise(resolve => setTimeout(resolve, 500));
        
        // Importa função de comprovante
        const { gerarComprovanteVenda } = await import('./pix.js');
        
        // Gera e exibe o comprovante
        await gerarComprovanteVenda(dados.carrinho, {
            ...dados.dadosPedido,
            venda_id: dados.venda.id,
            itens: dados.carrinho,
            valorTotal: dados.venda.valor_total,
            forma_pagamento: dados.formaPagamento,
            parcelas: dados.venda.parcelas || null,
            cliente: dados.venda.cliente || null
        });
        
    } catch (error) {
        console.error('[App] ❌ Erro ao exibir comprovante após reload:', error);
    }
}

/**
 * Mostra modal de confirmação para pagamento em DINHEIRO
 */
async function mostrarModalDinheiro(valorTotal, dadosPedido) {
    const { formatarMoeda } = await import('./utils.js');
    
    // Armazena dados do pedido globalmente
    window.dadosPedidoDinheiro = {
        ...dadosPedido,
        valorTotal: valorTotal
    };
    
    // Atualiza valores no modal
    const modal = document.getElementById('modal-dinheiro');
    if (!modal) {
        console.error('[App] ❌ Modal de dinheiro não encontrado');
        return;
    }
    
    document.getElementById('dinheiro-valor').textContent = formatarMoeda(valorTotal);
    
    // Mostra o modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

/**
 * Confirma recebimento de pagamento em DINHEIRO
 */
window.confirmarRecebimentoDinheiro = async function() {
    if (!window.dadosPedidoDinheiro) {
        alert('Erro: Dados do pedido não encontrados. Por favor, recarregue a página.');
        return;
    }
    
    const vendaId = window.dadosPedidoDinheiro.venda_id;
    
    if (!vendaId) {
        alert('Erro: ID da venda não encontrado. A venda pode não ter sido criada corretamente.');
        return;
    }
    
    console.log('[Dinheiro] 🔄 Confirmando recebimento da venda:', vendaId);
    
    try {
        const { API_ENDPOINTS } = await import('./config.js');
        const response = await fetch(API_ENDPOINTS.PEDIDO_CONFIRMAR_RECEBIMENTO, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ venda_id: vendaId })
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Erro ao confirmar recebimento: ${response.status} - ${errorText}`);
        }

        const vendaConfirmada = await response.json();
        console.log('[Dinheiro] ✅ Recebimento confirmado com sucesso!', vendaConfirmada);
        
        // Recupera carrinho
        const { getCarrinho } = await import('./cart.js');
        let carrinho = getCarrinho();
        if (carrinho.length === 0 && window.dadosPedidoDinheiro.itens) {
            carrinho = window.dadosPedidoDinheiro.itens;
        }
        
        // Salva dados para exibir comprovante após reload
        sessionStorage.setItem('venda_confirmada_comprovante', JSON.stringify({
            venda: vendaConfirmada,
            dadosPedido: window.dadosPedidoDinheiro,
            carrinho: carrinho,
            formaPagamento: 'Dinheiro'
        }));
        
        // Limpa carrinho
        const { limparCarrinho } = await import('./cart.js');
        limparCarrinho();
        
        // Fecha modal
        const modal = document.getElementById('modal-dinheiro');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        document.body.style.overflow = '';
        
        // Limpa dados
        window.dadosPedidoDinheiro = null;
        
        // ✅ Reload da página PRIMEIRO, comprovante será exibido após reload
        console.log('[Dinheiro] 🔄 Recarregando página para atualizar estoques...');
        window.location.reload();
        
    } catch (error) {
        console.error('[Dinheiro] ❌ Erro ao confirmar recebimento:', error);
        alert('Erro ao confirmar recebimento: ' + error.message);
    }
};

/**
 * Fecha modal de dinheiro
 */
window.fecharModalDinheiro = function() {
    const modal = document.getElementById('modal-dinheiro');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }
    window.dadosPedidoDinheiro = null;
};

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
export { init, carregarProdutos, abrirModal, fecharModal };