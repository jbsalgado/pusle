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

function renderPedido(pedido) {
    const div = document.createElement('div');
    div.className = 'bg-white rounded-lg shadow p-4 transition-shadow hover:shadow-md';

    let valorTotalCalculado = 0;
    if (pedido.parcelas && pedido.parcelas.length > 0) {
        valorTotalCalculado = pedido.parcelas.reduce((sum, p) => sum + parseFloat(p.valor_parcela || 0), 0);
    } else {
        valorTotalCalculado = parseFloat(pedido.valor_total || 0);
    }

    div.innerHTML = `
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b pb-2 mb-3 gap-2">
            <div>
                 <p class="text-xs text-gray-500">Pedido #${pedido.id.substring(0, 8)}</p>
                <span class="text-sm font-medium text-gray-700">${new Date(pedido.data_venda).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' })}</span>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full ${getStatusClass(pedido.statusVenda?.codigo || pedido.status_venda_codigo)}">
                ${pedido.statusVenda?.descricao || pedido.status_venda_codigo || 'Desconhecido'}
            </span>
        </div>
        <div class="space-y-1 mb-3">
            ${pedido.itens?.length > 0 ? pedido.itens.map(item => `
                <div class="text-sm flex justify-between items-center text-gray-600">
                    <span class="flex-1 mr-2">${item.quantidade}x ${item.produto?.nome || 'Produto desconhecido'}</span>
                    <span class="font-medium">R$ ${(parseFloat(item.quantidade * item.preco_unitario_venda)).toFixed(2).replace('.', ',')}</span>
                </div>
            `).join('') : '<p class="text-sm text-gray-500 italic">Itens não carregados.</p>'}
        </div>
        <div class="border-t pt-2 mt-2 flex justify-between items-center font-bold text-gray-800">
            <span>Total:</span>
            <span class="text-lg text-blue-600">R$ ${valorTotalCalculado.toFixed(2).replace('.', ',')}</span>
        </div>
        ${pedido.observacoes ? `<p class="text-xs text-gray-500 mt-2 italic">Obs: ${pedido.observacoes}</p>` : ''}
         ${pedido.parcelas?.length > 1 ? `
            <details class="text-xs mt-2">
                <summary class="cursor-pointer text-gray-500 hover:text-gray-700">Ver Parcelas (${pedido.numero_parcelas}x)</summary>
                <ul class="mt-1 pl-4 list-disc list-inside bg-gray-50 p-2 rounded border max-h-32 overflow-y-auto">
                    ${pedido.parcelas.map(parc => `
                        <li>${parc.numero_parcela}/${pedido.numero_parcelas}: R$ ${parseFloat(parc.valor_parcela).toFixed(2).replace('.', ',')} - Venc: ${new Date(parc.data_vencimento + 'T00:00:00').toLocaleDateString('pt-BR')} <span class="${getParcelaStatusClass(parc.status_parcela_codigo)}">${parc.status_parcela_codigo}</span></li>
                    `).join('')}
                </ul>
            </details>
         ` : ''}
    `;
    return div;
}

function getStatusClass(status) {
    switch (status) {
        case 'EM_ABERTO': return 'bg-blue-100 text-blue-800';
        case 'CONCLUIDA': case 'PAGO': return 'bg-green-100 text-green-800';
        case 'CANCELADA': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
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