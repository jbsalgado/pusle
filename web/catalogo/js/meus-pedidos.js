// js/meus-pedidos.js
import { maskCPF } from './utils.js';
import { CONFIG, API_ENDPOINTS } from './config.js';

const TOKEN_KEY = 'cliente_auth_token';
const CLIENTE_DATA_KEY = 'cliente_data';

// --- Elementos DOM ---
const loginSection = document.getElementById('login-section');
const mainContent = document.getElementById('main-content');
const loginForm = document.getElementById('login-form');
const inputCpf = document.getElementById('login-cpf');
const inputSenha = document.getElementById('login-senha');
const btnLogin = document.getElementById('btn-login');
const loginErrorDiv = document.getElementById('login-error');
const loginErrorMsg = document.getElementById('login-error-msg');
const pedidosContainer = document.getElementById('pedidos-container');
const loadingMsg = document.getElementById('loading-msg');
const noOrdersMsg = document.getElementById('no-orders-msg');
const errorLoadingMsg = document.getElementById('error-loading-msg');
const btnLogout = document.getElementById('btn-logout');
const clienteNomeSpan = document.getElementById('cliente-nome');
const btnViewList = document.getElementById('btn-view-list');
const btnViewGrid = document.getElementById('btn-view-grid');


// --- Funções ---

/**
 * Tenta fazer login chamando a API.
 */
async function fazerLogin(cpf, senha, usuarioId) {
    btnLogin.disabled = true;
    btnLogin.textContent = 'Entrando...';
    loginErrorDiv.classList.add('hidden');

    if (!usuarioId) {
        console.error('[meus-pedidos.js] ERRO CRÍTICO: ID_USUARIO_LOJA não definido em config.js!');
        loginErrorMsg.textContent = 'Erro de configuração: ID da loja não encontrado.';
        loginErrorDiv.classList.remove('hidden');
        btnLogin.disabled = false;
        btnLogin.textContent = 'Entrar';
        return;
    }

    try {
        const response = await fetch(API_ENDPOINTS.CLIENTE_LOGIN, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                cpf: cpf.replace(/\D/g, ''),
                senha: senha,
                usuario_id: usuarioId
            })
        });

        const data = await response.json();

        if (!response.ok) {
            const errorMessage = data.message || data.title || `Erro ${response.status}: ${response.statusText}`;
            if (response.status === 401 || errorMessage.toLowerCase().includes('senha incorreta')) {
                throw new Error('Senha incorreta.');
            }
            if (response.status === 404 || errorMessage.toLowerCase().includes('cliente não encontrado')) {
                throw new Error('Cliente não encontrado.');
            }
            throw new Error(errorMessage);
        }

        console.log('Login OK:', data);
        sessionStorage.setItem(TOKEN_KEY, data.token);
        if (data.cliente && data.cliente.usuario_id) {
            sessionStorage.setItem(CLIENTE_DATA_KEY, JSON.stringify(data.cliente));
            mostrarConteudoPrincipal(data.cliente);
            await carregarPedidos(data.token);
        } else {
            console.error("Erro: Resposta do login não continha dados esperados do cliente.", data);
            throw new Error("Resposta do servidor inválida após login.");
        }

    } catch (error) {
        console.error('Erro no login:', error);
        loginErrorMsg.textContent = error.message || 'Não foi possível fazer login. Verifique CPF/Senha e conexão.';
        loginErrorDiv.classList.remove('hidden');
        btnLogin.disabled = false;
        btnLogin.textContent = 'Entrar';
    }
}

/**
 * Carrega os pedidos do cliente autenticado.
 */
async function carregarPedidos(token) {
    loadingMsg.style.display = 'block';
    noOrdersMsg.style.display = 'none';
    errorLoadingMsg.style.display = 'none';
    pedidosContainer.innerHTML = '';
    pedidosContainer.appendChild(loadingMsg);

    try {
        // Extrair cliente_id
        let clienteId = null;
        
        // OPÇÃO 1: Do sessionStorage
        const clienteDataJson = sessionStorage.getItem(CLIENTE_DATA_KEY);
        if (clienteDataJson) {
            try {
                const clienteData = JSON.parse(clienteDataJson);
                clienteId = clienteData.cliente_id || clienteData.id;
            } catch (e) {
                console.warn('Erro ao parsear dados do cliente:', e);
            }
        }
        
        // OPÇÃO 2: Do JWT (fallback)
        if (!clienteId && token) {
            try {
                const payload = JSON.parse(atob(token.split('.')[1]));
                clienteId = payload.cliente_id;
            } catch (e) {
                console.error('Erro ao decodificar token JWT:', e);
            }
        }
        
        if (!clienteId) {
            throw new Error('Não foi possível identificar o cliente. Faça login novamente.');
        }
        
        const url = `${API_ENDPOINTS.PEDIDO}?cliente_id=${encodeURIComponent(clienteId)}`;
        
        console.log('🔍 Carregando pedidos para cliente_id:', clienteId);
        console.log('📡 URL da requisição:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });

        console.log('📥 Status da resposta:', response.status, response.statusText);
        
        if (response.status === 401 || response.status === 403) {
            console.warn('⚠️ Não autorizado (401/403), fazendo logout...');
            fazerLogout(); 
            return; 
        }
        
        if (!response.ok) {
            // Tentar ler detalhes do erro
            let errorDetails = '';
            try {
                const contentType = response.headers.get('content-type');
                
                if (contentType && contentType.includes('application/json')) {
                    const errorData = await response.json();
                    errorDetails = errorData.message || errorData.title || JSON.stringify(errorData);
                    console.error('❌ Erro do servidor (JSON):', errorData);
                } else {
                    const errorText = await response.text();
                    errorDetails = errorText.substring(0, 500);
                    console.error('❌ Erro do servidor (TEXT):', errorText);
                }
            } catch (parseError) {
                console.error('❌ Não foi possível parsear resposta de erro:', parseError);
            }
            
            if (response.status === 500) {
                console.error('🔴 ERRO CRÍTICO NO SERVIDOR (500)');
                console.error('Detalhes:', errorDetails);
                throw new Error('Erro interno no servidor. Verifique o arquivo app.log no backend.');
            }
            
            throw new Error(`Erro ${response.status}: ${response.statusText}${errorDetails ? ' - ' + errorDetails : ''}`); 
        }
        
        const data = await response.json();
        console.log('✅ Dados recebidos:', data);
        
        const pedidos = Array.isArray(data) ? data : (data.items || []);
        console.log('📦 Total de pedidos:', pedidos.length);
        
        loadingMsg.style.display = 'none';
        pedidosContainer.innerHTML = '';
        
        if (pedidos.length > 0) {
            pedidos.forEach(pedido => pedidosContainer.appendChild(renderPedido(pedido)));
        } else {
            noOrdersMsg.style.display = 'block';
            pedidosContainer.appendChild(noOrdersMsg);
        }

    } catch (error) {
        console.error('💥 Erro ao carregar pedidos:', error);
        console.error('Stack trace:', error.stack);
        loadingMsg.style.display = 'none';
        pedidosContainer.innerHTML = '';
        
        if (errorLoadingMsg) {
            const errorText = errorLoadingMsg.querySelector('p') || errorLoadingMsg;
            if (error.message.includes('500')) {
                errorText.textContent = 'Erro no servidor. Por favor, contate o administrador.';
            } else if (error.message.includes('identificar o cliente')) {
                errorText.textContent = error.message;
            } else {
                errorText.textContent = 'Não foi possível carregar os pedidos. Tente novamente.';
            }
            errorLoadingMsg.style.display = 'block';
            pedidosContainer.appendChild(errorLoadingMsg);
        }
    }
}

/**
 * 🆕 Função auxiliar para obter a URL da foto do produto
 */
function getFotoProduto(produto) {
    // Verifica se tem fotos
    if (!produto || !produto.fotos || !Array.isArray(produto.fotos) || produto.fotos.length === 0) {
        return null;
    }
    
    // Tenta pegar a foto principal
    const fotoPrincipal = produto.fotos.find(foto => foto.eh_principal);
    const foto = fotoPrincipal || produto.fotos[0]; // Pega a primeira se não tiver principal
    
    if (!foto || !foto.arquivo_path) {
        return null;
    }
    
    // Constrói a URL completa
    // Remove barras duplicadas se houver
    const path = foto.arquivo_path.startsWith('/') ? foto.arquivo_path : '/' + foto.arquivo_path;
    return `${CONFIG.URL_BASE_WEB}${path}`;
}

/**
 * 🆕 Renderiza um item da venda com foto e valores
 */
function renderItemVenda(item, index) {
    const produto = item.produto || {};
    const fotoUrl = getFotoProduto(produto);
    const valorTotalItem = parseFloat(item.quantidade * item.preco_unitario_venda).toFixed(2);
    const precoUnitario = parseFloat(item.preco_unitario_venda).toFixed(2);
    
    return `
        <div class="flex gap-3 p-2 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
            ${fotoUrl ? `
                <div class="flex-shrink-0">
                    <img 
                        src="${fotoUrl}" 
                        alt="${produto.nome || 'Produto'}"
                        class="w-16 h-16 object-cover rounded-md border border-gray-200"
                        onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2264%22 height=%2264%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23cbd5e0%22 stroke-width=%222%22%3E%3Crect x=%223%22 y=%223%22 width=%2218%22 height=%2218%22 rx=%222%22/%3E%3Ccircle cx=%228.5%22 cy=%228.5%22 r=%221.5%22/%3E%3Cpath d=%22M21 15l-5-5L5 21%22/%3E%3C/svg%3E';"
                    >
                </div>
            ` : `
                <div class="flex-shrink-0 w-16 h-16 bg-gray-200 rounded-md flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            `}
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">${produto.nome || 'Produto desconhecido'}</p>
                <div class="text-xs text-gray-500 mt-1 space-y-0.5">
                    <p>Quantidade: <span class="font-medium text-gray-700">${item.quantidade} un</span></p>
                    <p>Preço unitário: <span class="font-medium text-gray-700">R$ ${precoUnitario.replace('.', ',')}</span></p>
                </div>
            </div>
            <div class="flex-shrink-0 text-right">
                <p class="text-sm font-bold text-blue-600">R$ ${valorTotalItem.replace('.', ',')}</p>
            </div>
        </div>
    `;
}

/**
 * 🆕 Renderiza as parcelas em formato de tabela/lista melhorada
 */
function renderParcelas(parcelas, numeroParcelas) {
    if (!parcelas || parcelas.length === 0) {
        return '';
    }
    
    return `
        <div class="mt-4 border-t pt-3">
            <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Parcelas (${numeroParcelas}x)
            </h4>
            <div class="space-y-2 max-h-64 overflow-y-auto">
                ${parcelas.map(parc => {
                    const statusClass = getParcelaStatusClass(parc.status_parcela_codigo);
                    const statusBadgeClass = getParcelaStatusBadgeClass(parc.status_parcela_codigo);
                    const valorParcela = parseFloat(parc.valor_parcela).toFixed(2).replace('.', ',');
                    const dataVencimento = new Date(parc.data_vencimento + 'T00:00:00').toLocaleDateString('pt-BR');
                    
                    return `
                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded text-xs">
                            <div class="flex-1">
                                <span class="font-medium text-gray-700">Parcela ${parc.numero_parcela}/${numeroParcelas}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-gray-600">Venc: ${dataVencimento}</span>
                                <span class="font-bold text-gray-900">R$ ${valorParcela}</span>
                                <span class="${statusBadgeClass} px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap">
                                    ${getParcelaStatusTexto(parc.status_parcela_codigo)}
                                </span>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
    `;
}

/**
 * 🆕 Renderiza um pedido completo com novo layout
 */
function renderPedido(pedido) {
    const div = document.createElement('div');
    div.className = 'bg-white rounded-lg shadow-md p-5 transition-all hover:shadow-lg';

    let valorTotalCalculado = 0;
    if (pedido.parcelas && pedido.parcelas.length > 0) {
        valorTotalCalculado = pedido.parcelas.reduce((sum, p) => sum + parseFloat(p.valor_parcela || 0), 0);
    } else {
        valorTotalCalculado = parseFloat(pedido.valor_total || 0);
    }

    // Renderiza os itens com fotos
    let itensHtml = '';
    if (pedido.itens && Array.isArray(pedido.itens) && pedido.itens.length > 0) {
        itensHtml = pedido.itens.map((item, index) => renderItemVenda(item, index)).join('');
    } else {
        itensHtml = '<p class="text-sm text-gray-500 italic p-4 text-center bg-gray-50 rounded">Itens não carregados.</p>';
    }

    div.innerHTML = `
        <!-- Cabeçalho do Pedido -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-3 mb-4 border-b-2 border-gray-100 gap-2">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Pedido</p>
                <p class="text-lg font-bold text-gray-900">#${pedido.id.substring(0, 8)}</p>
                <p class="text-xs text-gray-600 mt-0.5">${new Date(pedido.data_venda).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' })}</p>
            </div>
            <span class="text-xs font-semibold px-3 py-1.5 rounded-full ${getStatusClass(pedido.statusVenda?.codigo || pedido.status_venda_codigo)}">
                ${pedido.statusVenda?.descricao || pedido.status_venda_codigo || 'Desconhecido'}
            </span>
        </div>

        <!-- Lista de Itens -->
        <div class="space-y-2 mb-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                Itens do Pedido
            </h3>
            ${itensHtml}
        </div>

        <!-- Total -->
        <div class="border-t-2 border-gray-200 pt-3 mt-4 flex justify-between items-center">
            <span class="text-base font-bold text-gray-800">Total:</span>
            <span class="text-xl font-bold text-blue-600">R$ ${valorTotalCalculado.toFixed(2).replace('.', ',')}</span>
        </div>

        <!-- Observações -->
        ${pedido.observacoes ? `
            <div class="mt-3 p-2 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                <p class="text-xs text-gray-700">
                    <span class="font-semibold">Obs:</span> ${pedido.observacoes}
                </p>
            </div>
        ` : ''}

        <!-- Parcelas -->
        ${pedido.parcelas && pedido.parcelas.length > 0 ? renderParcelas(pedido.parcelas, pedido.numero_parcelas) : ''}
    `;
    
    return div;
}

function getStatusClass(status) {
    switch (status) {
        case 'EM_ABERTO': return 'bg-blue-100 text-blue-800 border border-blue-200';
        case 'CONCLUIDA': case 'PAGO': return 'bg-green-100 text-green-800 border border-green-200';
        case 'CANCELADA': return 'bg-red-100 text-red-800 border border-red-200';
        default: return 'bg-gray-100 text-gray-800 border border-gray-200';
    }
}

function getParcelaStatusClass(status) {
    switch (status) {
        case 'PENDENTE': return 'text-yellow-600 font-medium';
        case 'PAGA': return 'text-green-600 font-medium';
        case 'VENCIDA': return 'text-red-600 font-medium';
        case 'CANCELADA': return 'text-gray-500 line-through';
        default: return 'text-gray-500';
    }
}

/**
 * 🆕 Retorna classe CSS para o badge de status da parcela
 */
function getParcelaStatusBadgeClass(status) {
    switch (status) {
        case 'PENDENTE': return 'bg-yellow-100 text-yellow-800';
        case 'PAGA': return 'bg-green-100 text-green-800';
        case 'VENCIDA': return 'bg-red-100 text-red-800';
        case 'CANCELADA': return 'bg-gray-100 text-gray-500';
        default: return 'bg-gray-100 text-gray-600';
    }
}

/**
 * 🆕 Retorna texto amigável para o status da parcela
 */
function getParcelaStatusTexto(status) {
    switch (status) {
        case 'PENDENTE': return 'Pendente';
        case 'PAGA': return 'Paga';
        case 'VENCIDA': return 'Vencida';
        case 'CANCELADA': return 'Cancelada';
        default: return status;
    }
}

function mostrarConteudoPrincipal(clienteData) {
    if (clienteData && clienteData.nome_completo) {
        clienteNomeSpan.textContent = clienteData.nome_completo.split(' ')[0];
    } else {
        clienteNomeSpan.textContent = "Cliente";
    }
    loginSection.classList.add('hidden');
    mainContent.classList.remove('hidden');
}

function fazerLogout() {
    sessionStorage.removeItem(TOKEN_KEY);
    sessionStorage.removeItem(CLIENTE_DATA_KEY);
    window.location.reload();
}

function configurarMascaraCpf() { 
    if (inputCpf) { 
        inputCpf.addEventListener('input', () => { maskCPF(inputCpf); }); 
    } 
}

function setupViewToggle() {
    btnViewList.addEventListener('click', () => {
        pedidosContainer.classList.remove('md:grid-cols-2', 'lg:grid-cols-3');
        btnViewList.classList.add('active-view');
        btnViewGrid.classList.remove('active-view');
    });
    btnViewGrid.addEventListener('click', () => {
        pedidosContainer.classList.add('md:grid-cols-2', 'lg:grid-cols-3');
        btnViewGrid.classList.add('active-view');
        btnViewList.classList.remove('active-view');
    });
}


// --- Inicialização ---
document.addEventListener('DOMContentLoaded', async () => {
    configurarMascaraCpf();
    setupViewToggle();

    const token = sessionStorage.getItem(TOKEN_KEY);
    const clienteDataJson = sessionStorage.getItem(CLIENTE_DATA_KEY);
    let clienteData = null;
    if (clienteDataJson) {
        try { 
            clienteData = JSON.parse(clienteDataJson); 
        } catch(e) { 
            console.error("Erro parse cliente data:", e);
        }
    }

    if (token) {
        console.log('Token encontrado, carregando pedidos...');
        mostrarConteudoPrincipal(clienteData);
        await carregarPedidos(token);
    } else {
        console.log('Nenhum token encontrado, exibindo login.');
        loginSection.classList.remove('hidden');
        mainContent.classList.add('hidden');
    }

    // Adiciona listener para o formulário de login
    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const cpf = inputCpf.value;
        const senha = inputSenha.value;
        const usuarioId = CONFIG.ID_USUARIO_LOJA;

        if (cpf && senha && usuarioId) {
            await fazerLogin(cpf, senha, usuarioId);
        } else if (!usuarioId) {
            console.error("ERRO GRAVE: ID_USUARIO_LOJA não definido em config.js");
            loginErrorMsg.textContent = 'Erro de configuração interna. Contate o suporte.';
            loginErrorDiv.classList.remove('hidden');
        } else {
            loginErrorMsg.textContent = 'CPF e Senha são obrigatórios.';
            loginErrorDiv.classList.remove('hidden');
        }
    });

    // Adiciona listener para o botão de logout
    btnLogout.addEventListener('click', fazerLogout);
});