// app.js - Aplicação principal do VENDA DIRETA PWA
import { CONFIG, API_ENDPOINTS, carregarConfigLoja } from './config.js';
import { 
    getCarrinho, setCarrinho, adicionarAoCarrinho, removerDoCarrinho,
    aumentarQuantidadeItem, diminuirQuantidadeItem, calcularTotalCarrinho,
    calcularTotalItens, limparCarrinho, atualizarIndicadoresCarrinho,
    atualizarBadgeProduto, aplicarDescontoItem, getAcrescimo, setAcrescimo
} from './cart.js?v=surcharge_fix';
import { carregarCarrinho, limparDadosLocaisPosSinc, carregarFormasPagamentoCache, obterFilaPedidos } from './storage.js';
import { finalizarPedido } from './order.js';
import { carregarFormasPagamento } from './payment.js';
import { validarCPF, maskCPF, maskPhone, maskCEP, formatarMoeda, formatarCPF, verificarElementosCriticos } from './utils.js';
import { ELEMENTOS_CRITICOS } from './config.js';
import { mostrarModalPixEstatico } from './pix.js'; // Importação do novo módulo
import { verificarAutenticacao, getColaboradorData, login } from './auth.js'; // Importação do módulo de autenticação
import { buscarClientePorCpf, cadastrarCliente, getClienteAtual, setClienteAtual } from './customer.js'; // Importação do módulo de cliente

// Variáveis Globais
let produtos = [];
let produtosFiltrados = []; // Produtos filtrados pela busca
let colaboradorAtual = null;
let formasPagamento = [];
let usuarioData = null;
let clienteAtual = null; // Cliente para orçamentos parceladas

// Cache de páginas já carregadas (melhora performance)
const cacheProdutos = new Map();
let paginaAtual = 1;
let metadadosPaginacao = null;

// Disponibiliza CONFIG no window para compatibilidade com módulos que não usam import
window.CONFIG = CONFIG;

// Inicialização
async function init() {
    try {
        console.log('[App] 🚀 Iniciando aplicação ORÇAMENTO...');
        
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
        await atualizarIndicadorPendentes();
        inicializarMonitoramentoRede();
        
        console.log('[App] ✅ Aplicação inicializada!');
    } catch (error) {
        console.error('[App] ❌ Erro na inicialização:', error);
    }
}

// Listener para exibir modal de login (disparado pelo auth.js quando 401)
window.addEventListener('exibir-login-modal', () => {
    const modal = document.getElementById('modal-login');
    if (modal) {
        modal.classList.remove('hidden');
        const inputUser = document.getElementById('login-username');
        if (inputUser) setTimeout(() => inputUser.focus(), 100);
    }
});

// Função global para realizar login via modal
window.realizarLogin = async function() {
    const user = document.getElementById('login-username').value;
    const pass = document.getElementById('login-password').value;
    const errorMsg = document.getElementById('login-error');
    const btn = document.getElementById('btn-login-submit');

    if (!user || !pass) {
        if (errorMsg) {
            errorMsg.textContent = 'Preencha usuário e senha.';
            errorMsg.classList.remove('hidden');
        }
        return;
    }

    try {
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Entrando...';
        }
        if (errorMsg) errorMsg.classList.add('hidden');

        await login(user, pass);

        // Sucesso
        const modal = document.getElementById('modal-login');
        if (modal) modal.classList.add('hidden');
        
        // Recarrega para reinicializar
        window.location.reload();

    } catch (err) {
        console.error('Erro no login:', err);
        if (errorMsg) {
            errorMsg.textContent = err.message || 'Erro ao entrar. Verifique suas credenciais.';
            errorMsg.classList.remove('hidden');
        }
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Entrar';
        }
    }
};

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
            const registration = await navigator.serviceWorker.register(`${CONFIG.URL_BASE_WEB}/orcamento/sw.js`);
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
                alert('Orçamento offline enviada com sucesso!');
                fecharModal('modal-cliente-pedido');
            } else if (type === 'SYNC_ERROR') {
                alert(`Erro ao enviar orçamento: ${error}`);
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
        // ✅ CORREÇÃO: Usar preço promocional se disponível
        const precoUnitario = item.preco_final || item.preco_venda_sugerido;
        const subtotal = precoUnitario * item.quantidade;
        let valorDesconto = 0;
        if (item.descontoValor > 0) {
            valorDesconto = item.descontoValor;
        } else if (item.descontoPercentual > 0) {
            valorDesconto = subtotal * (item.descontoPercentual / 100);
        }
        const totalItem = Math.max(0, subtotal - valorDesconto);
        const emPromocao = item.em_promocao || false;

        return `
        <div class="cart-item">
            <button onclick="removerItem(${index})" class="cart-item-remove" title="Remover item">✖</button>
            <div class="cart-item-container">
                <img src="${urlImagem}" alt="${item.nome}" class="cart-item-image">
                <div class="cart-item-info">
                    <h3 class="cart-item-name">${item.nome}${emPromocao ? ' <span class="text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded">PROMOÇÃO</span>' : ''}</h3>
                    <p class="cart-item-price">${formatarMoeda(precoUnitario)} un.${emPromocao && item.preco_venda_sugerido ? ` <span class="text-xs text-gray-500 line-through">${formatarMoeda(item.preco_venda_sugerido)}</span>` : ''}</p>
                    <div class="cart-item-controls">
                        <button onclick="diminuirQtd('${item.id}')" class="qty-btn">−</button>
                        <span class="qty-value">${item.quantidade}</span>
                        <button onclick="aumentarQtd('${item.id}')" class="qty-btn">+</button>
                    </div>
                    
                    <!-- Área de Desconto -->
                    <div class="mt-2 p-2 bg-gray-50 rounded text-sm">
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs text-gray-500">Desconto:</label>
                            <select onchange="alterarTipoDesconto('${item.id}', this.value)" class="text-xs border rounded p-0.5 bg-white">
                                <option value="valor" ${item.descontoValor > 0 ? 'selected' : ''}>R$</option>
                                <option value="porcentagem" ${item.descontoPercentual > 0 ? 'selected' : ''}>%</option>
                            </select>
                        </div>
                        <input type="tel" 
                            value="${(item.descontoValor > 0 ? item.descontoValor : (item.descontoPercentual || 0)).toLocaleString('pt-BR', {minimumFractionDigits: 2})}" 
                            data-desconto-item="${item.id}"
                            oninput="formatarEntradaDesconto(this)"
                            onchange="aplicarDesconto('${item.id}', this.value)" 
                            class="w-full border rounded p-1 text-right text-xs" 
                            placeholder="0,00">
                    </div>

                    <div class="cart-item-total mt-2">
                        <p class="cart-item-subtotal">Total</p>
                        <div class="text-right">
                            ${valorDesconto > 0 ? `<span class="text-xs text-red-500 line-through block">${formatarMoeda(subtotal)}</span>` : ''}
                            <p class="cart-item-total-price">${formatarMoeda(totalItem)}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');
    
    // Atualiza lógica de acréscimo no render
    const acrescimoAtual = getAcrescimo();
    const inputValor = document.getElementById('input-acrescimo-valor');
    const inputTipo = document.getElementById('input-acrescimo-tipo');
    const inputObs = document.getElementById('input-acrescimo-obs');

    if (inputValor && acrescimoAtual.valor > 0) {
        // Se já tem valor setado (redraw), restaura
        if (document.activeElement !== inputValor) {
             inputValor.value = acrescimoAtual.valor.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        }
    }
    if (inputTipo) inputTipo.value = acrescimoAtual.tipo;
    if (inputObs) inputObs.value = acrescimoAtual.observacao;

    // Atualiza Total com acréscimo
    const totalComAcrescimo = calcularTotalCarrinho();
    if (totalElement) totalElement.textContent = formatarMoeda(totalComAcrescimo);
    if (totalItensFooter) totalItensFooter.textContent = calcularTotalItens();
}

// Funções globais para Acréscimo
window.toggleAcrescimos = function() {
    const container = document.getElementById('container-acrescimos');
    const seta = document.getElementById('seta-acrescimo');
    if (container) {
        container.classList.toggle('hidden');
        seta.classList.toggle('rotate-180');
    }
};

window.formatarMoedaInput = function(input) {
    let valor = input.value.replace(/\D/g, '');
    valor = (valor / 100).toFixed(2) + '';
    valor = valor.replace(".", ",");
    valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
    input.value = valor;
    
    // Atualiza estado global
    atualizarAcrescimoGlobal();
};

// Listener para select e obs também atualizarem o estado
document.addEventListener('change', function(e) {
    if (e.target.id === 'input-acrescimo-tipo' || e.target.id === 'input-acrescimo-obs') {
        atualizarAcrescimoGlobal();
    }
});

function atualizarAcrescimoGlobal() {
    const inputValor = document.getElementById('input-acrescimo-valor');
    const inputTipo = document.getElementById('input-acrescimo-tipo');
    const inputObs = document.getElementById('input-acrescimo-obs');
    
    if (inputValor) {
        let valorClean = inputValor.value.replace(/\./g, '').replace(',', '.');
        setAcrescimo(valorClean, inputTipo.value, inputObs.value);
        
        // Atualiza apenas o texto do total, sem re-renderizar tudo (para não perder foco)
        const totalElement = document.getElementById('valor-total-carrinho');
        if (totalElement) {
             totalElement.textContent = formatarMoeda(calcularTotalCarrinho());
        }
    }
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

window.aplicarDesconto = function(produtoId, valorFormatado) {
    const select = document.querySelector(`select[onchange^="alterarTipoDesconto('${produtoId}'"]`);
    const tipo = select ? select.value : 'valor';
    
    // Converte "1.234,56" ou "10,00" para float
    // Remove tudo que não é dígito, exceto a vírgula decimal
    let valorNumerico = 0;
    
    // Se for string vazia, é 0
    if (valorFormatado && typeof valorFormatado === 'string') {
        // Remove pontos de milhar e troca vírgula por ponto
        const limpo = valorFormatado.replace(/\./g, '').replace(',', '.');
        valorNumerico = parseFloat(limpo) || 0;
    } else {
        valorNumerico = parseFloat(valorFormatado) || 0;
    }

    if (aplicarDescontoItem(produtoId, tipo, valorNumerico)) {
        renderizarCarrinho();
    }
};

window.alterarTipoDesconto = function(produtoId, novoTipo) {
    // Apenas recalcula visualmente
    // Pega o input atual associado
    const input = document.querySelector(`input[data-desconto-item="${produtoId}"]`);
    if (input) {
        // Dispara a aplicação do desconto (que vai reler o tipo do select)
        window.aplicarDesconto(produtoId, input.value);
    }
};

window.formatarEntradaDesconto = function(input) {
    let valor = input.value.replace(/\D/g, ''); // Remove tudo que não é dígito
    
    // Se vazio, fica vazio ou 0,00
    if (valor === '') {
        input.value = '';
        return;
    }
    
    // Converte para centavos
    valor = (parseInt(valor) / 100).toFixed(2);
    
    // Formata para PT-BR
    valor = valor.replace('.', ',');
    
    // Adiciona separador de milhar se necessário (ex: 1.234,56)
    valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    
    input.value = valor;
    
    // Opcional: já aplicar o desconto enquanto digita (pode ser pesado, melhor no onchange ou debounce)
    // Para UX imediata:
    // const produtoId = input.dataset.descontoItem;
    // if(produtoId) window.aplicarDesconto(produtoId, input.value);
};

// ==========================================================================
// PRODUTOS
// ==========================================================================

/**
 * Carrega uma página específica de produtos (paginação real)
 * @param {number} pagina - Número da página a carregar (padrão: 1)
 * @param {boolean} forcarRecarregar - Se true, ignora cache e recarrega
 * @param {string} termoBusca - Termo de busca opcional para filtrar produtos
 */
async function carregarProdutos(pagina = 1, forcarRecarregar = false, termoBusca = '') {
    try {
        // Para busca, não usa cache (busca sempre precisa ser atualizada)
        const cacheKey = termoBusca ? `${pagina}_${termoBusca}` : pagina;
        if (!forcarRecarregar && !termoBusca && cacheProdutos.has(pagina)) {
            console.log(`[App] 📦 Usando cache da página ${pagina}`);
            const dadosCache = cacheProdutos.get(pagina);
            produtos = dadosCache.produtos;
            produtosFiltrados = produtos;
            paginaAtual = pagina;
            metadadosPaginacao = dadosCache.metadados;
            renderizarProdutos(produtosFiltrados);
            atualizarControlesPaginacao();
            ocultarCarregando();
            return;
        }
        
        console.log('[App] 📦 Carregando produtos (página', pagina, termoBusca ? `, busca: "${termoBusca}"` : '', ')...');
        mostrarCarregando();
        
        // Constrói URL com parâmetros de busca se houver
        let url = `${API_ENDPOINTS.PRODUTO}?usuario_id=${CONFIG.ID_USUARIO_LOJA}&page=${pagina}&per-page=20`;
        if (termoBusca && termoBusca.trim() !== '') {
            url += `&q=${encodeURIComponent(termoBusca.trim())}`;
        }
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`Erro ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        // A API do Yii2 retorna um objeto com items, _links e _meta quando usa ActiveDataProvider
        let produtosPagina = [];
        let metadados = null;
        
        if (data.items && Array.isArray(data.items)) {
            // Formato paginado do Yii2
            produtosPagina = data.items;
            
            // Tenta diferentes formatos de metadados
            if (data._meta) {
                metadados = {
                    totalCount: data._meta.totalCount || data._meta.total || 0,
                    pageCount: data._meta.pageCount || Math.ceil((data._meta.totalCount || 0) / (data._meta.perPage || 20)) || 1,
                    currentPage: data._meta.currentPage || data._meta.page || pagina,
                    perPage: data._meta.perPage || data._meta.pageSize || 20
                };
            } else {
                // Fallback: calcula baseado nos items retornados
                const perPage = 20;
                const totalEstimado = produtosPagina.length < perPage 
                    ? (pagina - 1) * perPage + produtosPagina.length
                    : null;
                
                metadados = {
                    totalCount: totalEstimado || produtosPagina.length * pagina,
                    pageCount: totalEstimado ? Math.ceil(totalEstimado / perPage) : pagina + 1,
                    currentPage: pagina,
                    perPage: perPage
                };
                
                console.warn('[App] ⚠️ Metadados de paginação não encontrados. Usando estimativas:', metadados);
            }
        } else if (Array.isArray(data)) {
            // Formato direto (array) - fallback
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
                perPage: 20
            };
        }
        
        // Salva no cache apenas se não for busca (busca não deve ser cacheada)
        if (!termoBusca) {
            cacheProdutos.set(pagina, {
                produtos: produtosPagina,
                metadados: metadados
            });
        }
        
        // Atualiza variáveis globais
        produtos = produtosPagina; // Apenas produtos da página atual
        produtosFiltrados = produtos; // Quando vem da API com busca, já está filtrado
        paginaAtual = pagina;
        metadadosPaginacao = metadados;
        window.paginacaoMetadados = metadados;
        
        console.log(`[App] ✅ Página ${pagina} carregada: ${produtosPagina.length} produto(s) de ${metadados.totalCount} total${termoBusca ? ` (busca: "${termoBusca}")` : ''}`);
        
        // Renderiza os produtos (já filtrados pela API se houver busca)
        renderizarProdutos(produtosFiltrados);
        atualizarControlesPaginacao();
        ocultarCarregando();
        
    } catch (error) {
        console.error('[App] ❌ Erro ao carregar produtos:', error);
        const container = document.getElementById('catalogo-produtos');
        if (container) {
            container.innerHTML = '<div class="col-span-full text-center py-16"><p class="text-red-600">Erro ao carregar produtos. Verifique sua conexão.</p></div>';
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
        return; // Controles de paginação não existem ainda
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

/**
 * Filtra produtos - se houver termo de busca, recarrega da API
 * Caso contrário, mostra todos os produtos da página atual
 */
async function filtrarProdutos() {
    const termoBusca = document.getElementById('busca-produto')?.value?.trim() || '';
    const btnLimpar = document.getElementById('btn-limpar-busca');
    
    // Mostra/oculta botão de limpar busca
    if (btnLimpar) {
        btnLimpar.classList.toggle('hidden', !termoBusca);
    }
    
    if (!termoBusca) {
        // Sem busca: usa produtos já carregados
        produtosFiltrados = produtos;
        renderizarProdutos(produtosFiltrados);
    } else {
        // Com busca: recarrega da API (vai buscar em todas as páginas, não apenas na atual)
        console.log('[App] 🔍 Buscando produtos na API com termo:', termoBusca);
        await carregarProdutos(1, true, termoBusca); // Sempre começa na página 1 quando busca
    }
}

function inicializarBuscaProdutos() {
    const inputBusca = document.getElementById('busca-produto');
    if (!inputBusca) return;
    
    // Filtra enquanto o usuário digita (debounce)
    let timeoutId;
    inputBusca.addEventListener('input', (e) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(async () => {
            await filtrarProdutos();
        }, 300); // Aguarda 300ms após parar de digitar
    });
    
    // Filtra ao pressionar Enter
    inputBusca.addEventListener('keypress', async (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            await filtrarProdutos();
        }
    });
}

window.limparBusca = async function() {
    const inputBusca = document.getElementById('busca-produto');
    if (inputBusca) {
        inputBusca.value = '';
        // Recarrega produtos sem busca (volta para página 1)
        await carregarProdutos(1, true, '');
        inputBusca.focus();
    }
};

function renderizarProdutos(listaProdutos) {
    const container = document.getElementById('catalogo-produtos');
    if (!container) {
        console.error('[App] ❌ Container de produtos não encontrado');
        return;
    }
    
    console.log('[App] 🎨 Renderizando produtos:', {
        total: listaProdutos.length,
        produtos: listaProdutos.slice(0, 3).map(p => ({ id: p.id, nome: p.nome })) // Primeiros 3 para debug
    });
    
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
            container.innerHTML = `<div class="col-span-full text-center py-16"><p class="text-gray-500">Nenhum produto disponível no momento.</p></div>`;
        }
        console.warn('[App] ⚠️ Nenhum produto para renderizar');
        return;
    }
    
    container.innerHTML = listaProdutos.map(produto => {
        let urlImagem = 'https://dummyimage.com/300x200/cccccc/ffffff.png&text=Sem+Imagem';
        if (produto.fotos && produto.fotos.length > 0 && produto.fotos[0].arquivo_path) {
            const arquivoPath = produto.fotos[0].arquivo_path.replace(/^\//, '');
            const baseUrl = CONFIG.URL_BASE_WEB.replace(/\/$/, '');
            urlImagem = `${baseUrl}/${arquivoPath}`;
        }
        // ✅ CORREÇÃO: Usar preço promocional se disponível
        const precoExibido = produto.preco_final || produto.preco_venda_sugerido;
        const emPromocao = produto.em_promocao || false;
        const precoOriginal = emPromocao ? produto.preco_venda_sugerido : null;
        
        return `
        <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl relative" data-produto-card="${produto.id}">
            <div class="badge-no-carrinho hidden absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full z-10">✓ No Carrinho</div>
            ${emPromocao ? '<div class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded z-10">PROMOÇÃO</div>' : ''}
            <div class="w-full h-48 bg-gray-50 overflow-hidden">
                <img src="${urlImagem}" alt="${produto.nome}" class="w-full h-full object-contain">
            </div>
            <div class="p-4">
                <h3 class="text-lg font-bold text-gray-800 mb-2">${produto.nome}</h3>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex flex-col">
                        ${emPromocao && precoOriginal ? `
                            <span class="text-sm text-gray-500 line-through">${formatarMoeda(precoOriginal)}</span>
                            <span class="text-2xl font-bold text-red-600">${formatarMoeda(precoExibido)}</span>
                        ` : `
                            <span class="text-2xl font-bold text-blue-600">${formatarMoeda(precoExibido)}</span>
                        `}
                    </div>
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
    // ✅ CORREÇÃO: Usar preço promocional se disponível
    const precoExibido = produto.preco_final || produto.preco_venda_sugerido;
    document.getElementById('preco-produto-modal').textContent = formatarMoeda(precoExibido);
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
    
    // Campo de cliente (apenas para orçamentos parceladas)
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
            // Mostra campo de cliente para orçamentos parceladas
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
            // Oculta campo de cliente para orçamentos à vista
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
        
        // Para orçamentos parceladas, cliente é obrigatório
        let clienteId = null;
        if (numeroParcelas > 1) {
            clienteId = clienteAtual?.id || document.getElementById('cliente_id')?.value || null;
            if (!clienteId) {
                alert('Para orçamentos parceladas, é necessário buscar e cadastrar o cliente.');
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
            
            // Tenta extrair o ID de várias fontes possíveis na resposta
            const vendaId = resultado.dados?.id || 
                           resultado.dados?.data?.id || 
                           resultado.dados?.orçamento?.id || 
                           resultado.dados?.data?.orçamento?.id || 
                           resultado.id;
            
            if (!vendaId) {
                console.error('[App] ❌ ID da orçamento não encontrado no resultado', resultado);
                alert('Erro: ID da orçamento não encontrado. Verifique o console para mais detalhes.');
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = '✅ Confirmar Orçamento';
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
                console.log('[App] 🟢 Orçamento PIX à vista detectada. Mostrando modal de confirmação...');
                
                const valorTotal = calcularTotalCarrinho();
                
                // Gera TxID limpo: OrçamentoDireta + DDMMYYYY + HHMM
                const now = new Date();
                const dia = String(now.getDate()).padStart(2, '0');
                const mes = String(now.getMonth() + 1).padStart(2, '0');
                const ano = String(now.getFullYear());
                const hora = String(now.getHours()).padStart(2, '0');
                const minuto = String(now.getMinutes()).padStart(2, '0');
                const txId = `OrçamentoDireta${dia}${mes}${ano}${hora}${minuto}`;

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
                btnConfirmar.textContent = '✅ Confirmar Orçamento';
                return; // Encerra aqui - confirmação será feita quando usuário clicar em "Confirmar Recebimento"
            }

            // DINHEIRO: Mostra modal de confirmação similar ao PIX
            if (isDinheiro && isVista) {
                console.log('[App] 💵 Orçamento DINHEIRO à vista detectada. Mostrando modal de confirmação...');
                
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
                btnConfirmar.textContent = '✅ Confirmar Orçamento';
                return; // Encerra aqui - confirmação será feita quando usuário clicar em "Confirmar Recebimento"
                }
                
            // Outros pagamentos (cartão, boleto, etc): Confirma recebimento imediatamente
            console.log('[App] 🔄 Confirmando recebimento da orçamento:', vendaId);
            
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
                
                // Salva dados do orçamento no sessionStorage para exibir comprovante após reload
                sessionStorage.setItem('orcamento_confirmado_comprovante', JSON.stringify({
                    orçamento: vendaConfirmada,
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
                btnConfirmar.textContent = '✅ Confirmar Orçamento';
                return;
            }
        } else if (resultado.sucesso && resultado.offline) {
            // Offline: orçamento salva localmente
            alert(resultado.mensagem || 'Orçamento salva localmente. Será enviada quando a conexão for restaurada.');
            fecharModal('modal-cliente-pedido');
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = '✅ Confirmar Orçamento';
        } else {
            alert(`Erro: ${resultado.mensagem}`);
        }
    } catch (error) {
        console.error('Erro:', error);
        alert(`Erro ao finalizar orçamento: ${error.message}`);
    } finally {
        // Só reabilita se não tiver aberto o modal PIX (que reabilita antes do return)
        if (document.getElementById('modal-pix-estatico').classList.contains('hidden')) {
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = '✅ Confirmar Orçamento';
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
                // Mostra campo de cliente para orçamentos parceladas
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
                // Oculta campo de cliente para orçamentos à vista
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
// BUSCA E CADASTRO DE CLIENTE (para orçamentos parceladas)
// ==========================================================================

window.buscarClienteOrcamento = async function() {
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

window.salvarClienteOrcamento = async function() {
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
        const dadosSalvos = sessionStorage.getItem('orcamento_confirmado_comprovante');
        if (!dadosSalvos) {
            return; // Não há comprovante para exibir
        }
        
        const dados = JSON.parse(dadosSalvos);
        sessionStorage.removeItem('orcamento_confirmado_comprovante'); // Remove para não exibir novamente
        
        console.log('[App] 📋 Exibindo comprovante após reload...', dados);
        
        // Aguarda um pouco para garantir que a página carregou completamente
        await new Promise(resolve => setTimeout(resolve, 500));
        
        // Importa função de comprovante
        const { gerarComprovanteOrcamento } = await import('./pix.js');
        
        // Gera e exibe o comprovante
        await gerarComprovanteOrcamento(dados.carrinho, {
            ...dados.dadosPedido,
            orçamento: dados.orçamento, // Passa objeto completo da orçamento (incluindo acréscimos)
            venda_id: dados.orçamento.id,
            itens: dados.carrinho,
            valorTotal: dados.orçamento.valor_total,
            forma_pagamento: dados.formaPagamento,
            parcelas: dados.orçamento.parcelas || null,
            cliente: dados.orçamento.cliente || null
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
        alert('Erro: ID da orçamento não encontrado. A orçamento pode não ter sido criada corretamente.');
        return;
    }
    
    console.log('[Dinheiro] 🔄 Confirmando recebimento da orçamento:', vendaId);
    
    try {
        const { API_ENDPOINTS } = await import('./config.js');
        const { getToken } = await import('./storage.js');
        const token = await getToken();

        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };

        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const response = await fetch(API_ENDPOINTS.PEDIDO_CONFIRMAR_RECEBIMENTO, {
            method: 'POST',
            headers: headers,
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
        sessionStorage.setItem('orcamento_confirmado_comprovante', JSON.stringify({
            orçamento: vendaConfirmada,
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
/**
 * Atualiza o indicador (badge) de pedidos aguardando sincronização
 */
async function atualizarIndicadorPendentes(count = null) {
    try {
        if (count === null) {
            const fila = await obterFilaPedidos();
            count = fila.length;
        }
        
        const badgeDiv = document.getElementById('status-pendente');
        const countSpan = document.getElementById('badge-pendente-count');
        
        if (badgeDiv && countSpan) {
            if (count > 0) {
                countSpan.textContent = count;
                badgeDiv.classList.remove('hidden');
                badgeDiv.classList.add('flex');
                console.log(`[App] 📦 Badge pendente atualizado: ${count}`);
            } else {
                badgeDiv.classList.add('hidden');
                badgeDiv.classList.remove('flex');
            }
        }
    } catch (err) {
        console.error('[App] Erro ao atualizar indicador de pendentes:', err);
    }
}

// Listener para atualiza\u00e7\u00e3o da fila (disparado pelo storage.js)
window.addEventListener('fila-pedidos-atualizada', (e) => {
    atualizarIndicadorPendentes(e.detail.count);
});

// Listener para mensagens do Service Worker
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('message', async (event) => {
        if (event.data && event.data.type === 'SYNC_SUCCESS') {
            console.log('[App] \ud83d\udce4 Sincroniza\u00e7\u00e3o autom\u00e1tica via SW bem-sucedida!');
            await atualizarIndicadorPendentes();
        }
    });
}
