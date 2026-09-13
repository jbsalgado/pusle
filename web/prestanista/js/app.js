// app.js - Aplicação principal do Módulo Prestanista

import { CONFIG, API_ENDPOINTS, STATUS_PARCELA, TIPO_ACAO, FORMAS_PAGAMENTO_COBRADOR } from './config.js';
import { 
    initDB, 
    salvarCobrador, 
    carregarCobrador, 
    salvarRotaDia, 
    carregarRotaDia,
    adicionarPagamentoPendente,
    adicionarVendaPendente,
    carregarPagamentosPendentes,
    carregarVendasPendentes,
    carregarClientesCache,
    carregarParcelasCache,
    limparDadosLocais
} from './storage.js';
import { 
    sincronizarPagamentosPendentes,
    sincronizarVendasPendentes,
    baixarRotaDia, 
    estaOnline,
    iniciarMonitoramentoConexao
} from './sync.js';
import { 
    formatarMoeda, 
    formatarData, 
    formatarDataParcela, 
    obterGeolocalizacao, 
    mostrarToast,
    formatarCPF,
    formatarTelefone
} from './utils.js';

// Variáveis Globais
let cobradorAtual = null;
let rotaDia = [];
let clienteSelecionado = null;
let parcelasClienteSelecionado = [];
let vendasClienteSelecionado = [];
let formaPagamentoSelecionada = null;
let parcelaSelecionada = null;
let todasVendas = []; // Array com todas as vendas para navegação
let cardAtualIndex = 0; // Índice do card atualmente visível

// Estado da Nova Venda
let estadoNovaVenda = {
    cliente_id: '',
    itens: [],
    valor_total: 0,
    numero_parcelas: 1,
    frequencia: 30,
    data_primeiro_vencimento: ''
};

// Inicialização
async function init() {
    try {
        console.log('[App] 🚀 Iniciando Módulo Prestanista...');
        
        // Inicializa IndexedDB
        await initDB();
        
        // Carrega cobrador salvo
        cobradorAtual = await carregarCobrador();
        
        if (!cobradorAtual) {
            // Se não houver cobrador, solicita login
            solicitarLogin();
            return;
        }
        
        // Registra Service Worker
        await registrarServiceWorker();
        
        // Carrega rota do dia
        rotaDia = await carregarRotaDia();
        
        // Aplica pagamentos pendentes localmente na rota carregada
        await aplicarPagamentosPendentesNaRota();
        
        // Inicializa UI
        inicializarUI();
        inicializarEventListeners();
        atualizarStatusConexao();
        iniciarMonitoramentoConexao(sincronizarPagamentosPendentes);
        
        // Carrega logo da empresa
        await carregarLogoEmpresa();
        
        // Esconde loading
        document.getElementById('loading-screen').classList.add('hidden');
        document.getElementById('app-container').classList.remove('hidden');
        
        // Atualiza dashboard
        atualizarDashboard();
        renderizarRota();
        
        console.log('[App] ✅ Módulo Prestanista inicializado!');
    } catch (error) {
        console.error('[App] ❌ Erro na inicialização:', error);
        mostrarToast('Erro ao inicializar aplicação', 'error');
    } finally {
        // Garante que o loading suma
        const loadScreen = document.getElementById('loading-screen');
        const appContainer = document.getElementById('app-container');
        if (loadScreen) loadScreen.classList.add('hidden');
        if (appContainer) appContainer.classList.remove('hidden');
    }
}

/**
 * Solicita login do cobrador
 */
function solicitarLogin() {
    const cpf = prompt('Digite seu CPF (apenas números):');
    if (!cpf) {
        mostrarToast('CPF é obrigatório', 'error');
        return;
    }
    
    fazerLogin(cpf);
}

/**
 * Faz login do cobrador
 */
async function fazerLogin(cpf) {
    try {
        // Busca colaborador por CPF
        // Nota: Será necessário criar endpoint de login ou usar busca por CPF
        const response = await fetch(
            `${API_ENDPOINTS.COLABORADOR_BUSCA_CPF}?cpf=${cpf}&usuario_id=${obterUsuarioId()}`
        );
        
        if (!response.ok) {
            throw new Error('Erro ao buscar cobrador');
        }
        
        const data = await response.json();
        
        if (!data.existe || !data.colaborador) {
            mostrarToast('Cobrador não encontrado', 'error');
            return;
        }
        
        // Verifica se é cobrador
        // Nota: Será necessário adicionar verificação de eh_cobrador na API
        
        cobradorAtual = {
            id: data.colaborador.id,
            nome_completo: data.colaborador.nome_completo,
            cpf: cpf
        };
        
        await salvarCobrador(cobradorAtual);
        mostrarToast(`Bem-vindo, ${cobradorAtual.nome_completo}!`, 'success');
        
        // Recarrega a aplicação
        location.reload();
    } catch (error) {
        console.error('[App] ❌ Erro no login:', error);
        mostrarToast('Erro ao fazer login', 'error');
    }
}

/**
 * Faz logout do cobrador
 */
async function fazerLogout() {
    try {
        // Confirma logout
        const confirmar = confirm('Deseja realmente sair do módulo Prestanista?');
        if (!confirmar) {
            return;
        }
        
        // Limpa todos os dados locais
        await limparDadosLocais();
        
        // Limpa variáveis globais
        cobradorAtual = null;
        rotaDia = [];
        todasVendas = [];
        
        mostrarToast('Logout realizado com sucesso', 'success');
        
        // Recarrega a página para solicitar novo login
        setTimeout(() => {
            location.reload();
        }, 1000);
        
    } catch (error) {
        console.error('[App] ❌ Erro no logout:', error);
        mostrarToast('Erro ao fazer logout', 'error');
    }
}

/**
 * Obtém ID do usuário (loja) - temporário, deve vir da autenticação
 */
function obterUsuarioId() {
    // TODO: Implementar autenticação adequada
    // Por enquanto, usa um ID fixo ou busca da URL
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('usuario_id') || 'a99a38a9-e368-4a47-a4bd-02ba3bacaa76';
}

/**
 * Inicializa UI
 */
function inicializarUI() {
    atualizarStatusConexao();
}

/**
 * Atualiza status de conexão
 */
function atualizarStatusConexao() {
    const statusOnline = document.getElementById('status-online');
    const statusOffline = document.getElementById('status-offline');
    
    if (estaOnline()) {
        statusOnline.classList.remove('hidden');
        statusOffline.classList.add('hidden');
    } else {
        statusOnline.classList.add('hidden');
        statusOffline.classList.remove('hidden');
    }
}

/**
 * Inicializa event listeners
 */
function inicializarEventListeners() {
    // Botão toggle filtros
    const btnToggleFiltros = document.getElementById('btn-toggle-filtros');
    const containerFiltros = document.getElementById('container-filtros');
    
    if (btnToggleFiltros && containerFiltros) {
        btnToggleFiltros.addEventListener('click', () => {
            const estaVisivel = !containerFiltros.classList.contains('hidden');
            if (estaVisivel) {
                containerFiltros.classList.add('hidden');
            } else {
                containerFiltros.classList.remove('hidden');
            }
        });
    }
    
    // Filtros
    const filtroNome = document.getElementById('filtro-nome');
    const filtroCpf = document.getElementById('filtro-cpf');
    const filtroData = document.getElementById('filtro-data');
    const filtroDataCobranca = document.getElementById('filtro-data-cobranca');
    const btnLimparFiltros = document.getElementById('btn-limpar-filtros');
    
    // Função para aplicar filtros
    const aplicarFiltros = () => {
        const nomeFiltro = (filtroNome.value || '').toLowerCase().trim();
        const cpfFiltro = (filtroCpf.value || '').replace(/[^0-9]/g, '').trim();
        const dataFiltro = filtroData.value || '';
        const dataCobrancaFiltro = filtroDataCobranca.value || '';
        
        const cards = document.querySelectorAll('.card-ficha');
        let cardsVisiveis = 0;
        
        cards.forEach(card => {
            const clienteNome = card.getAttribute('data-cliente-nome') || '';
            const clienteCpf = card.getAttribute('data-cliente-cpf') || '';
            const vendaData = card.getAttribute('data-venda-data') || '';
            const cobrancaData = card.getAttribute('data-cobranca-data') || '';
            
            let mostrar = true;
            
            // Filtro por nome
            if (nomeFiltro && !clienteNome.includes(nomeFiltro)) {
                mostrar = false;
            }
            
            // Filtro por CPF
            if (cpfFiltro && !clienteCpf.includes(cpfFiltro)) {
                mostrar = false;
            }
            
            // Filtro por data da venda
            if (dataFiltro && vendaData !== dataFiltro) {
                mostrar = false;
            }
            
            // Filtro por data da cobrança
            if (dataCobrancaFiltro && cobrancaData !== dataCobrancaFiltro) {
                mostrar = false;
            }
            
            if (mostrar) {
                card.style.display = '';
                cardsVisiveis++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Mostra mensagem se não houver resultados
        const container = document.getElementById('lista-rotas');
        let mensagemVazia = container.querySelector('.mensagem-sem-resultados');
        
        if (cardsVisiveis === 0 && cards.length > 0) {
            if (!mensagemVazia) {
                mensagemVazia = document.createElement('div');
                mensagemVazia.className = 'mensagem-sem-resultados text-center text-gray-500 py-12';
                mensagemVazia.innerHTML = `
                    <p class="text-sm">Nenhuma venda encontrada com os filtros aplicados.</p>
                    <p class="text-xs mt-1">Tente ajustar os filtros ou limpar para ver todas as vendas.</p>
                `;
                container.appendChild(mensagemVazia);
            }
        } else if (mensagemVazia) {
            mensagemVazia.remove();
        }
    };
    
    // Event listeners para filtros
    if (filtroNome) {
        filtroNome.addEventListener('input', aplicarFiltros);
    }
    
    if (filtroCpf) {
        filtroCpf.addEventListener('input', (e) => {
            // Formata CPF enquanto digita
            let valor = e.target.value.replace(/[^0-9]/g, '');
            if (valor.length > 11) valor = valor.substring(0, 11);
            
            if (valor.length > 0) {
                valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            }
            e.target.value = valor;
            aplicarFiltros();
        });
    }
    
    if (filtroData) {
        filtroData.addEventListener('change', aplicarFiltros);
    }
    
    if (filtroDataCobranca) {
        filtroDataCobranca.addEventListener('change', aplicarFiltros);
    }
    
    if (btnLimparFiltros) {
        btnLimparFiltros.addEventListener('click', () => {
            filtroNome.value = '';
            filtroCpf.value = '';
            filtroData.value = '';
            filtroDataCobranca.value = '';
            aplicarFiltros();
        });
    }
    
    // Botão sincronizar rota
    document.getElementById('btn-sincronizar-rota').addEventListener('click', async () => {
        await sincronizarRota();
    });
    
    // Botão sincronizar
    document.getElementById('btn-sincronizar').addEventListener('click', async () => {
        await sincronizarTudo();
    });
    
    // Botão sair/logout
    document.getElementById('btn-sair').addEventListener('click', async () => {
        await fazerLogout();
    });
    
    // Botão voltar da ficha
    document.getElementById('btn-voltar-ficha').addEventListener('click', () => {
        mostrarViewDashboard();
    });
    
    // Modal de recebimento
    document.getElementById('btn-cancelar-recebimento').addEventListener('click', () => {
        fecharModalRecebimento();
    });
    
    document.getElementById('btn-confirmar-recebimento').addEventListener('click', async () => {
        await confirmarRecebimento();
    });
    
    document.getElementById('btn-pagamento-dinheiro').addEventListener('click', () => {
        selecionarFormaPagamento('DINHEIRO');
    });
    
    document.getElementById('btn-pagamento-pix').addEventListener('click', () => {
        selecionarFormaPagamento('PIX');
    });
    
    // Event listeners do modal de visita
    document.getElementById('btn-cancelar-visita').addEventListener('click', () => {
        window.fecharModalVisita();
    });
    
    document.getElementById('btn-confirmar-visita').addEventListener('click', async () => {
        await window.confirmarVisita();
    });
    
    document.querySelectorAll('.btn-tipo-visita').forEach((btn) => {
        btn.addEventListener('click', () => {
            const tipo = btn.getAttribute('data-tipo');
            window.selecionarTipoVisita(tipo);
        });
    });
    
    // Atualiza status de conexão periodicamente
    setInterval(atualizarStatusConexao, 5000);

    // --- Listeners: Novo Cartão ---
    document.getElementById('btn-abrir-nova-venda').addEventListener('click', abrirNovaVenda);
    document.getElementById('btn-fechar-nova-venda').addEventListener('click', fecharNovaVenda);
    document.getElementById('busca-produto').addEventListener('input', debounce(buscarProdutos, 500));
    document.getElementById('venda-parcelas').addEventListener('input', (e) => {
        estadoNovaVenda.numero_parcelas = parseInt(e.target.value) || 1;
        atualizarPreviaVenda();
    });
    document.getElementById('venda-frequencia').addEventListener('change', (e) => {
        estadoNovaVenda.frequencia = parseInt(e.target.value) || 30;
        atualizarPreviaVenda();
    });
    document.getElementById('venda-data-primeira').addEventListener('change', (e) => {
        estadoNovaVenda.data_primeiro_vencimento = e.target.value;
    });
    document.getElementById('btn-finalizar-venda').addEventListener('click', finalizarVenda);
}

// --- Funções: Novo Cartão ---

function abrirNovaVenda() {
    // Reseta estado
    estadoNovaVenda = {
        cliente_id: '',
        itens: [],
        valor_total: 0,
        numero_parcelas: 1,
        frequencia: 30,
        data_primeiro_vencimento: new Date().toISOString().split('T')[0]
    };
    
    // Preenche select de clientes
    const select = document.getElementById('select-cliente-venda');
    select.innerHTML = '<option value="">Selecione um cliente...</option>';
    
    // Pega clientes únicos da rota
    const clientes = [];
    rotaDia.forEach(item => {
        if (item.cliente && !clientes.find(c => c.id === item.cliente.id)) {
            clientes.push(item.cliente);
        }
    });
    
    clientes.sort((a, b) => a.nome_completo.localeCompare(b.nome_completo))
        .forEach(c => {
            select.innerHTML += `<option value="${c.id}">${c.nome_completo}</option>`;
        });

    document.getElementById('venda-data-primeira').value = estadoNovaVenda.data_primeiro_vencimento;
    document.getElementById('carrinho-venda').innerHTML = '';
    document.getElementById('venda-total-label').textContent = 'R$ 0,00';
    
    document.getElementById('view-nova-venda').classList.remove('hidden');
    document.getElementById('btn-abrir-nova-venda').classList.add('hidden');
}

function fecharNovaVenda() {
    document.getElementById('view-nova-venda').classList.add('hidden');
    document.getElementById('btn-abrir-nova-venda').classList.remove('hidden');
}

async function buscarProdutos(e) {
    const q = e.target.value.trim();
    const lista = document.getElementById('lista-busca-produtos');
    
    if (q.length < 2) {
        lista.classList.add('hidden');
        return;
    }

    try {
        const response = await fetch(`${API_ENDPOINTS.PRODUTO_BUSCA}?q=${q}&usuario_id=${obterUsuarioId()}`);
        const data = await response.json();
        
        if (data.sucesso && data.produtos.length > 0) {
            lista.innerHTML = '';
            data.produtos.forEach(p => {
                const item = document.createElement('div');
                item.className = 'busca-resultado-item';
                item.innerHTML = `
                    <div class="flex flex-col">
                        <span class="text-sm font-medium">${p.nome}</span>
                        <span class="text-xs text-gray-500">${p.codigo || ''}</span>
                    </div>
                    <span class="text-blue-600 font-bold">${formatarMoeda(p.preco)}</span>
                `;
                item.onclick = () => adicionarProduto(p);
                lista.appendChild(item);
            });
            lista.classList.remove('hidden');
        } else {
            lista.classList.add('hidden');
        }
    } catch (err) {
        console.error('Erro na busca:', err);
    }
}

function adicionarProduto(p) {
    const existe = estadoNovaVenda.itens.find(i => i.produto_id === p.id);
    if (existe) {
        existe.quantidade++;
    } else {
        estadoNovaVenda.itens.push({
            produto_id: p.id,
            nome: p.nome,
            preco_unitario: p.preco,
            quantidade: 1
        });
    }
    
    document.getElementById('busca-produto').value = '';
    document.getElementById('lista-busca-produtos').classList.add('hidden');
    atualizarPreviaVenda();
}

function removerProduto(id) {
    estadoNovaVenda.itens = estadoNovaVenda.itens.filter(i => i.produto_id !== id);
    atualizarPreviaVenda();
}

function atualizarPreviaVenda() {
    const container = document.getElementById('carrinho-venda');
    container.innerHTML = '';
    
    let total = 0;
    estadoNovaVenda.itens.forEach(item => {
        total += item.preco_unitario * item.quantidade;
        const div = document.createElement('div');
        div.className = 'flex justify-between items-center bg-gray-50 p-2 rounded border';
        div.innerHTML = `
            <div class="flex flex-col">
                <span class="text-xs font-bold">${item.nome}</span>
                <span class="text-[10px] text-gray-500">${item.quantidade}x ${formatarMoeda(item.preco_unitario)}</span>
            </div>
            <button onclick="removerProduto('${item.produto_id}')" class="text-red-500 p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        `;
        container.appendChild(div);
    });
    
    estadoNovaVenda.valor_total = total;
    document.getElementById('venda-total-label').textContent = formatarMoeda(total);
}

async function finalizarVenda() {
    estadoNovaVenda.cliente_id = document.getElementById('select-cliente-venda').value;
    
    if (!estadoNovaVenda.cliente_id) {
        mostrarToast('Selecione um cliente', 'error');
        return;
    }
    if (estadoNovaVenda.itens.length === 0) {
        mostrarToast('Adicione ao menos um produto', 'error');
        return;
    }

    const payload = {
        ...estadoNovaVenda,
        cobrador_id: cobradorAtual.id,
        usuario_id: obterUsuarioId()
    };

    try {
        if (!estaOnline()) {
            // Salvar para sincronizar depois
            const idLocal = await adicionarVendaPendente(payload);
            if (idLocal) {
                mostrarToast('Venda salva offline. Será sincronizada automaticamente.', 'success');
                fecharNovaVenda();
                // Opcional: Adicionar localmente à rota para exibição imediata
            } else {
                throw new Error('Erro ao salvar venda offline');
            }
            return;
        }

        const response = await fetch(API_ENDPOINTS.REGISTRAR_VENDA, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (response.ok) {
            mostrarToast('Cartão emitido com sucesso!', 'success');
            fecharNovaVenda();
            await sincronizarRota();
        } else {
            throw new Error('Erro ao emitir cartão');
        }
    } catch (err) {
        mostrarToast(err.message, 'error');
    }
}

// Auxiliares
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}

// Injetar funções globais para o HTML
window.removerProduto = removerProduto;

/**
 * Sincroniza rota do dia
 */
async function sincronizarRota() {
    if (!estaOnline()) {
        mostrarToast('Sem conexão com internet', 'warning');
        return;
    }
    
    if (!cobradorAtual) {
        mostrarToast('Cobrador não identificado', 'error');
        return;
    }
    
    const btn = document.getElementById('btn-sincronizar-rota');
    btn.disabled = true;
    btn.textContent = 'Sincronizando...';
    
    try {
        const usuarioId = obterUsuarioId();
        rotaDia = await baixarRotaDia(cobradorAtual.id, usuarioId);
        
        // Aplica pagamentos pendentes na rota baixada do servidor
        await aplicarPagamentosPendentesNaRota();
        
        renderizarRota();
        atualizarDashboard();
        mostrarToast('Rota sincronizada com sucesso!', 'success');
    } catch (error) {
        console.error('[App] ❌ Erro ao sincronizar rota:', error);
        mostrarToast('Erro ao sincronizar rota', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Sincronizar Rota do Dia
        `;
    }
}

/**
 * Sincroniza tudo (rota + pagamentos pendentes)
 */
async function sincronizarTudo() {
    const btn = document.getElementById('btn-sincronizar');
    btn.disabled = true;
    
    try {
        await sincronizarRota();
        const resultado = await sincronizarPagamentosPendentes();
        
        if (resultado.sucesso) {
            if (resultado.sincronizados > 0) {
                mostrarToast(`${resultado.sincronizados} pagamento(s) sincronizado(s)`, 'success');
            } else {
                mostrarToast('Tudo sincronizado!', 'success');
            }
        } else {
            mostrarToast('Alguns itens não puderam ser sincronizados', 'warning');
        }
    } catch (error) {
        console.error('[App] ❌ Erro ao sincronizar:', error);
        mostrarToast('Erro ao sincronizar', 'error');
    } finally {
        btn.disabled = false;
    }
}

/**
 * Atualiza dashboard
 */
function atualizarDashboard() {
    // Verifica se o DOM está pronto
    if (document.readyState === 'loading') {
        console.warn('[Dashboard] DOM ainda não está pronto, aguardando...');
        setTimeout(atualizarDashboard, 100);
        return;
    }
    
    try {
        let valorAReceber = 0;
        let valorRecebido = 0;
        let visitasPendentes = 0;
        
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);
        
        for (const item of rotaDia) {
            if (!item.vendas || item.vendas.length === 0) {
                continue; // Pula se não houver vendas
            }
            
            // Filtra vendas válidas (com parcelas e tipo correto)
            const vendasValidas = item.vendas.filter(venda => {
                // Verifica se tem parcelas
                if (!venda.total_parcelas || venda.total_parcelas === 0) {
                    return false;
                }
                
                // Verifica tipo de pagamento (exclui cartão de crédito/débito, inclui outras formas de cobrança manual)
                const tipoPagamento = venda.forma_pagamento_tipo ? venda.forma_pagamento_tipo.toUpperCase() : null;
                if (tipoPagamento && ['CARTAO_CREDITO', 'CARTAO', 'CARTAO DE CREDITO', 'CARTAO_DEBITO', 'CARTAO DE DEBITO'].includes(tipoPagamento)) {
                    return false; // Exclui cartão (cobrança automática)
                }
                // Inclui: BOLETO, DINHEIRO, PIX, CHEQUE, TRANSFERENCIA, OUTRO (cobrança manual/CARNE)
                
                return true;
            });
            
            if (vendasValidas.length === 0) {
                continue; // Pula se não houver vendas válidas
            }
            
            if (item.parcelas) {
                // Agrupa parcelas por venda para verificar tipo de pagamento
                const parcelasPorVenda = {};
                item.parcelas.forEach(parcela => {
                    const vendaId = parcela.venda_id || 'sem-venda';
                    if (!parcelasPorVenda[vendaId]) {
                        parcelasPorVenda[vendaId] = [];
                    }
                    parcelasPorVenda[vendaId].push(parcela);
                });
                
                for (const parcela of item.parcelas) {
                    // Verifica se a venda desta parcela é válida
                    const vendaParcela = vendasValidas.find(v => v.id === parcela.venda_id);
                    if (!vendaParcela) {
                        continue; // Pula parcelas de vendas inválidas
                    }
                    
                    if (parcela.status_parcela_codigo === STATUS_PARCELA.PENDENTE) {
                        // Verifica se a parcela pode ser cobrada (atrasada ou do mês atual)
                        const vencimento = new Date(parcela.data_vencimento);
                        vencimento.setHours(0, 0, 0, 0);
                        
                        const estaVencida = vencimento < hoje;
                        const mesmoMes = vencimento.getMonth() === hoje.getMonth() && 
                                       vencimento.getFullYear() === hoje.getFullYear();
                        
                        // Só conta se estiver vencida ou for do mês atual
                        if (estaVencida || mesmoMes) {
                            valorAReceber += parseFloat(parcela.valor_parcela || 0);
                        }
                    } else if (parcela.status_parcela_codigo === STATUS_PARCELA.PAGA) {
                        valorRecebido += parseFloat(parcela.valor_pago || parcela.valor_parcela || 0);
                    }
                }
            }
        }
        
        // Conta visitas pendentes (clientes com parcelas que podem ser cobradas)
        const clientesComParcelasCobravel = new Set();
        for (const item of rotaDia) {
            if (!item.vendas || item.vendas.length === 0) {
                continue;
            }
            
            // Filtra vendas válidas
            const vendasValidas = item.vendas.filter(venda => {
                if (!venda.total_parcelas || venda.total_parcelas === 0) {
                    return false;
                }
                const tipoPagamento = venda.forma_pagamento_tipo ? venda.forma_pagamento_tipo.toUpperCase() : null;
                if (tipoPagamento && ['CARTAO_CREDITO', 'CARTAO', 'CARTAO DE CREDITO'].includes(tipoPagamento)) {
                    return false;
                }
                return true;
            });
            
            if (vendasValidas.length === 0) {
                continue;
            }
            
            if (item.parcelas && item.cliente) {
                // Verifica se há parcelas que podem ser cobradas
                const temParcelaCobravel = item.parcelas.some(parcela => {
                    if (parcela.status_parcela_codigo !== STATUS_PARCELA.PENDENTE) {
                        return false;
                    }
                    
                    const vendaParcela = vendasValidas.find(v => v.id === parcela.venda_id);
                    if (!vendaParcela) {
                        return false;
                    }
                    
                    const vencimento = new Date(parcela.data_vencimento);
                    vencimento.setHours(0, 0, 0, 0);
                    
                    const estaVencida = vencimento < hoje;
                    const mesmoMes = vencimento.getMonth() === hoje.getMonth() && 
                                   vencimento.getFullYear() === hoje.getFullYear();
                    
                    return estaVencida || mesmoMes;
                });
                
                if (temParcelaCobravel) {
                    clientesComParcelasCobravel.add(item.cliente.id);
                }
            }
        }
        
        visitasPendentes = clientesComParcelasCobravel.size;
        
        // Atualiza elementos do dashboard (verifica se existem antes de atualizar)
        const valorAReceberEl = document.getElementById('valor-a-receber');
        const valorRecebidoEl = document.getElementById('valor-recebido');
        const visitasPendentesEl = document.getElementById('visitas-pendentes');
        const dataRotaEl = document.getElementById('data-rota');
        
        // Verifica se os elementos existem antes de tentar atualizar
        if (valorAReceberEl && valorAReceberEl instanceof HTMLElement) {
            try {
                const valorFormatado = formatarMoeda(valorAReceber);
                valorAReceberEl.textContent = valorFormatado || 'R$ 0,00';
            } catch (e) {
                console.error('[Dashboard] Erro ao atualizar valor-a-receber:', e);
            }
        } else {
            console.warn('[Dashboard] Elemento valor-a-receber não encontrado ou inválido');
        }
        
        if (valorRecebidoEl && valorRecebidoEl instanceof HTMLElement) {
            try {
                const valorFormatado = formatarMoeda(valorRecebido);
                valorRecebidoEl.textContent = valorFormatado || 'R$ 0,00';
            } catch (e) {
                console.error('[Dashboard] Erro ao atualizar valor-recebido:', e);
            }
        } else {
            console.warn('[Dashboard] Elemento valor-recebido não encontrado ou inválido');
        }
        
        if (visitasPendentesEl && visitasPendentesEl instanceof HTMLElement) {
            try {
                visitasPendentesEl.textContent = String(visitasPendentes || 0);
            } catch (e) {
                console.error('[Dashboard] Erro ao atualizar visitas-pendentes:', e);
            }
        } else {
            console.warn('[Dashboard] Elemento visitas-pendentes não encontrado ou inválido');
        }
        
        // Atualiza data da rota (se o elemento existir)
        if (dataRotaEl && dataRotaEl instanceof HTMLElement) {
            try {
                const hoje = new Date();
                dataRotaEl.textContent = formatarData(hoje);
            } catch (e) {
                console.error('[Dashboard] Erro ao atualizar data-rota:', e);
            }
        }
    } catch (error) {
        console.error('[Dashboard] Erro ao atualizar dashboard:', error);
        // Não propaga o erro para não quebrar o fluxo
    }
}

/**
 * Aplica pagamentos pendentes localmente na rota carregada
 * Isso garante que os pagamentos feitos offline apareçam mesmo após reload
 */
/**
 * Constrói endereço completo do cliente para uso no Google Maps
 */
function construirEnderecoCompleto(cliente) {
    const partes = [];
    
    // A API retorna 'endereco' como string completa OU campos separados
    if (cliente.endereco) {
        // Se já vem como string completa da API
        partes.push(cliente.endereco);
    } else {
        // Monta a partir dos campos separados
        if (cliente.endereco_logradouro || cliente.logradouro) {
            let endereco = cliente.endereco_logradouro || cliente.logradouro || '';
            if (cliente.endereco_numero || cliente.numero) {
                endereco += ', ' + (cliente.endereco_numero || cliente.numero);
            }
            if (cliente.endereco_complemento || cliente.complemento) {
                endereco += ' - ' + (cliente.endereco_complemento || cliente.complemento);
            }
            if (endereco.trim()) {
                partes.push(endereco);
            }
        }
    }
    
    // Bairro
    if (cliente.endereco_bairro || cliente.bairro) {
        partes.push(cliente.endereco_bairro || cliente.bairro);
    }
    
    // Cidade e Estado
    if (cliente.endereco_cidade || cliente.cidade) {
        let cidadeEstado = cliente.endereco_cidade || cliente.cidade;
        if (cliente.endereco_estado || cliente.estado) {
            cidadeEstado += ' - ' + (cliente.endereco_estado || cliente.estado);
        }
        partes.push(cidadeEstado);
    }
    
    // CEP
    if (cliente.endereco_cep || cliente.cep) {
        partes.push(cliente.endereco_cep || cliente.cep);
    }
    
    return partes.join(', ');
}

/**
 * Abre Google Maps com rota até o endereço do cliente
 */
window.abrirGoogleMaps = function(endereco) {
    if (!endereco || endereco.trim() === '') {
        mostrarToast('Endereço não disponível', 'warning');
        return;
    }
    
    // URL do Google Maps com direções
    const url = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(endereco)}`;
    
    // Abre em nova aba
    window.open(url, '_blank');
};

async function aplicarPagamentosPendentesNaRota() {
    try {
        const pagamentosPendentes = await carregarPagamentosPendentes();
        
        if (pagamentosPendentes.length === 0) {
            console.log('[App] ℹ️ Nenhum pagamento pendente para aplicar');
            return; // Nenhum pagamento pendente
        }
        
        console.log(`[App] 🔄 Aplicando ${pagamentosPendentes.length} pagamento(s) pendente(s) na rota local...`);
        console.log('[App] 📋 Pagamentos pendentes:', pagamentosPendentes);
        console.log('[App] 📋 Rota atual (antes):', rotaDia.length, 'itens');
        
        let pagamentosAplicados = 0;
        
        // Para cada pagamento pendente, atualiza a parcela correspondente na rota
        for (const pagamento of pagamentosPendentes) {
            // Encontra o item da rota do cliente
            const itemRota = rotaDia.find(r => r.cliente?.id === pagamento.cliente_id);
            
            if (itemRota && itemRota.parcelas) {
                // Encontra a parcela
                const parcela = itemRota.parcelas.find(p => p.id === pagamento.parcela_id);
                
                if (parcela) {
                    // Atualiza a parcela com o status de pago
                    parcela.status_parcela_codigo = STATUS_PARCELA.PAGA;
                    parcela.data_pagamento = pagamento.data_acao ? pagamento.data_acao.split('T')[0] : new Date().toISOString().split('T')[0];
                    parcela.valor_pago = pagamento.valor_recebido;
                    pagamentosAplicados++;
                    
                    console.log(`[App] ✅ Pagamento aplicado localmente: parcela ${parcela.numero_parcela} (ID: ${parcela.id}) do cliente ${itemRota.cliente?.nome}`);
                    console.log(`[App] 📝 Status: ${parcela.status_parcela_codigo}, Valor pago: ${parcela.valor_pago}, Data: ${parcela.data_pagamento}`);
                } else {
                    console.warn(`[App] ⚠️ Parcela não encontrada: ID ${pagamento.parcela_id} no cliente ${pagamento.cliente_id}`);
                }
            } else {
                console.warn(`[App] ⚠️ Cliente não encontrado na rota: ID ${pagamento.cliente_id}`);
            }
        }
        
        // Salva a rota atualizada no IndexedDB
        await salvarRotaDia(rotaDia);
        console.log(`[App] ✅ Rota atualizada com ${pagamentosAplicados} pagamento(s) pendente(s) e salva no IndexedDB`);
        
    } catch (error) {
        console.error('[App] ❌ Erro ao aplicar pagamentos pendentes:', error);
    }
}

/**
 * Renderiza lista de rotas
 */
function renderizarRota() {
    const container = document.getElementById('lista-rotas');
    
    if (rotaDia.length === 0) {
        container.innerHTML = `
            <div class="text-center text-gray-500 py-12">
                <p class="text-sm">Nenhuma rota carregada.</p>
                <p class="text-xs mt-1">Clique em "Sincronizar" para começar.</p>
            </div>
        `;
        return;
    }
    
    // Agrupa todas as vendas de todos os clientes
    // Usa variável global para poder atualizar depois
    todasVendas = [];
    rotaDia.forEach((itemRota, indexRota) => {
        const cliente = itemRota.cliente || {};
        let vendas = itemRota.vendas || [];
        
        // Filtra vendas válidas (com parcelas e tipo correto)
        vendas = vendas.filter(venda => {
            // Verifica se tem parcelas
            if (!venda.total_parcelas || venda.total_parcelas === 0) {
                return false; // Exclui vendas sem parcelas
            }
            
                // Verifica tipo de pagamento (exclui cartão de crédito/débito, inclui outras formas de cobrança manual)
                const tipoPagamento = venda.forma_pagamento_tipo ? venda.forma_pagamento_tipo.toUpperCase() : null;
                if (tipoPagamento && ['CARTAO_CREDITO', 'CARTAO', 'CARTAO DE CREDITO', 'CARTAO_DEBITO', 'CARTAO DE DEBITO'].includes(tipoPagamento)) {
                    return false; // Exclui cartão (cobrança automática)
                }
                // Inclui: BOLETO, DINHEIRO, PIX, CHEQUE, TRANSFERENCIA, OUTRO (cobrança manual/CARNE)
            
            return true;
        });
        
        // Se não houver vendas válidas explícitas, tenta criar uma venda virtual com as parcelas
        if (vendas.length === 0 && itemRota.parcelas && itemRota.parcelas.length > 0) {
            // Agrupa parcelas por venda_id
            const parcelasPorVenda = {};
            itemRota.parcelas.forEach(parcela => {
                const vendaId = parcela.venda_id || 'default';
                if (!parcelasPorVenda[vendaId]) {
                    parcelasPorVenda[vendaId] = [];
                }
                parcelasPorVenda[vendaId].push(parcela);
            });
            
            Object.keys(parcelasPorVenda).forEach(vendaId => {
                todasVendas.push({
                    cliente: cliente,
                    venda: {
                        id: vendaId,
                        data_venda: itemRota.parcelas[0].data_vencimento || new Date().toISOString(),
                        valor_total: parcelasPorVenda[vendaId].reduce((sum, p) => sum + parseFloat(p.valor_parcela || 0), 0),
                        itens: []
                    },
                    parcelas: parcelasPorVenda[vendaId],
                    indexRota: indexRota
                });
            });
        } else {
            // Processa vendas explícitas
            vendas.forEach(venda => {
                const parcelasVenda = (itemRota.parcelas || []).filter(p => p.venda_id === venda.id);
                todasVendas.push({
                    cliente: cliente,
                    venda: venda,
                    parcelas: parcelasVenda,
                    indexRota: indexRota
                });
            });
        }
    });
    
    // Ordena vendas: primeiro as com parcelas pendentes, depois por data de vencimento
    todasVendas.sort((a, b) => {
        const aTemPendente = a.parcelas.some(p => p.status_parcela_codigo === STATUS_PARCELA.PENDENTE);
        const bTemPendente = b.parcelas.some(p => p.status_parcela_codigo === STATUS_PARCELA.PENDENTE);
        
        if (aTemPendente && !bTemPendente) return -1;
        if (!aTemPendente && bTemPendente) return 1;
        
        // Ordena por data de vencimento da primeira parcela pendente
        const aParcelaPendente = a.parcelas.find(p => p.status_parcela_codigo === STATUS_PARCELA.PENDENTE);
        const bParcelaPendente = b.parcelas.find(p => p.status_parcela_codigo === STATUS_PARCELA.PENDENTE);
        
        if (aParcelaPendente && bParcelaPendente) {
            return new Date(aParcelaPendente.data_vencimento) - new Date(bParcelaPendente.data_vencimento);
        }
        
        return 0;
    });
    
    // Renderiza lista simples de vendas (uma abaixo da outra)
    if (todasVendas.length === 0) {
        container.innerHTML = `
            <div class="text-center text-gray-500 py-12">
                <p class="text-sm">Nenhuma venda encontrada.</p>
                <p class="text-xs mt-1">Clique em "Sincronizar" para carregar as vendas.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = todasVendas.map((item, index) => {
        const cliente = item.cliente;
        const venda = item.venda;
        const parcelas = item.parcelas || [];
        
        // Ordena parcelas por data de vencimento
        const parcelasOrdenadas = [...parcelas].sort((a, b) => {
            return new Date(a.data_vencimento) - new Date(b.data_vencimento);
        });
        
        const parcelasPendentes = parcelas.filter(p => p.status_parcela_codigo === STATUS_PARCELA.PENDENTE);
        const parcelasPagas = parcelas.filter(p => p.status_parcela_codigo === STATUS_PARCELA.PAGA);
        const valorTotalPendente = parcelasPendentes.reduce((sum, p) => sum + parseFloat(p.valor_parcela || 0), 0);
        const primeiraParcelaPendente = parcelasPendentes.length > 0 ? parcelasPendentes[0] : null;
        
        // Data da cobrança: data de vencimento da primeira parcela pendente (ou mais próxima)
        // Ordena parcelas pendentes por data de vencimento para pegar a mais próxima
        const parcelasPendentesOrdenadas = [...parcelasPendentes].sort((a, b) => {
            return new Date(a.data_vencimento) - new Date(b.data_vencimento);
        });
        const dataCobranca = parcelasPendentesOrdenadas.length > 0 
            ? new Date(parcelasPendentesOrdenadas[0].data_vencimento).toISOString().split('T')[0] 
            : '';
        
        // Verifica se há parcelas pendentes que podem ser cobradas (vencidas ou do mês atual)
        // Parcelas futuras não devem aparecer, pois não podem ser marcadas
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);
        
        const parcelasCobravel = parcelasPendentes.filter(parcela => {
            const vencimento = new Date(parcela.data_vencimento);
            vencimento.setHours(0, 0, 0, 0);
            
            // Verifica se está vencida
            const estaVencida = vencimento < hoje;
            
            // Verifica se é do mês atual (mesmo mês e ano)
            const mesmoMes = vencimento.getMonth() === hoje.getMonth() && 
                           vencimento.getFullYear() === hoje.getFullYear();
            
            // Pode marcar visita se estiver vencida OU for do mês atual
            // Parcelas futuras (não vencidas e não do mês atual) não contam
            return estaVencida || mesmoMes;
        });
        
        // Verifica se há parcelas cobráveis para mostrar o botão "MARCAR VISITA"
        // Se todas as parcelas estão pagas ou são futuras, não precisa marcar visita
        const temParcelasCobravel = parcelasCobravel.length > 0;
        
        // Formata número da venda com zeros à esquerda
        const numeroVenda = String(index + 1).padStart(5, '0');
        
        // Prepara dados dos produtos
        const itensVenda = venda.itens && venda.itens.length > 0 ? venda.itens : [];
        
        // Prepara endereço completo para Google Maps
        const enderecoCompleto = construirEnderecoCompleto(cliente);
        // Verifica se tem endereço suficiente para o mapa (endereco OU logradouro) E cidade
        const temEnderecoParaMapa = (cliente.endereco || cliente.endereco_logradouro || cliente.logradouro) && (cliente.endereco_cidade || cliente.cidade || cliente.bairro);
        
        const totalPago = parcelasPagas.reduce((sum, p) => sum + parseFloat(p.valor_pago || p.valor_parcela || 0), 0);
        const saldoDevedor = Math.max(0, parseFloat(venda.valor_total || 0) - totalPago);
        const proximaParcela = parcelasPendentesOrdenadas.length > 0 ? parcelasPendentesOrdenadas[0] : null;

        return `
            <div class="card-ficha bg-[#fffdf7] border-2 border-slate-900 rounded-2xl shadow-md overflow-hidden text-slate-950 font-mono mb-5" 
                 data-index="${index}"
                 data-cliente-id="${cliente.id}"
                 data-venda-id="${venda.id}"
                 data-cliente-nome="${(cliente.nome || '').toLowerCase()}"
                 data-cliente-cpf="${(cliente.cpf || '').replace(/[^0-9]/g, '')}"
                 data-venda-data="${venda.data_venda ? new Date(venda.data_venda).toISOString().split('T')[0] : ''}"
                 data-cobranca-data="${dataCobranca}">
                
                <!-- 1. CABEÇALHO DO CARTÃO FÍSICO -->
                <div class="bg-amber-100/70 border-b-2 border-slate-900 p-3 text-center">
                    <p class="text-[9px] uppercase font-bold tracking-widest text-slate-600">Nosso prazer é atendê-lo bem</p>
                    <h3 class="text-base sm:text-lg font-black uppercase tracking-tight text-slate-950">CREDIÁRIOS & UTILIDADES</h3>
                    <div class="flex items-center justify-between text-[10px] font-bold mt-1 pt-1 border-t border-slate-400">
                        <span>DATA: <strong class="font-sans">${formatarData(venda.data_venda)}</strong></span>
                        <span>FLS: <strong>01</strong></span>
                        <span class="text-amber-900">Nº: <strong>#${numeroVenda}</strong></span>
                    </div>
                </div>

                <div class="p-3.5 space-y-3">
                    <!-- 2. OBJETOS (Mercadorias) -->
                    <div class="border-b border-slate-300 pb-2">
                        <div class="flex justify-between text-[10px] font-bold uppercase text-slate-500 border-b border-slate-200 pb-0.5 mb-1">
                            <span>OBJETOS</span>
                            <span>VALOR R$</span>
                        </div>
                        ${itensVenda.length > 0 ? itensVenda.map(item => `
                            <div class="flex justify-between text-xs py-0.5">
                                <span class="font-bold truncate pr-2">${item.produto_nome || 'Mercadoria'} <span class="text-slate-500 font-normal">(${item.quantidade || 1}x)</span></span>
                                <span class="font-black whitespace-nowrap">${formatarMoeda(item.valor_total || 0)}</span>
                            </div>
                        `).join('') : `
                            <div class="flex justify-between text-xs py-0.5">
                                <span class="font-bold">Mercadorias Diversas</span>
                                <span class="font-black">${formatarMoeda(venda.valor_total || 0)}</span>
                            </div>
                        `}
                        <div class="flex justify-between items-center pt-1 font-sans font-black text-xs border-t border-slate-300 mt-1">
                            <span class="uppercase">TOTAL DO CARTÃO:</span>
                            <span class="text-amber-900 text-sm">${formatarMoeda(venda.valor_total || 0)}</span>
                        </div>
                    </div>

                    <!-- 3. COMPRADOR -->
                    <div class="border-b-2 border-slate-900 pb-2 text-xs leading-snug space-y-0.5">
                        <div><strong class="text-slate-600">Sr.(a):</strong> <span class="font-sans font-black text-sm text-slate-950">${cliente.nome || cliente.nome_completo || 'Cliente'}</span></div>
                        <div><strong class="text-slate-600">Rua:</strong> ${cliente.endereco || cliente.endereco_logradouro || cliente.logradouro || '—'}${cliente.endereco_numero || cliente.numero ? ', ' + (cliente.endereco_numero || cliente.numero) : ''}</div>
                        <div class="flex justify-between">
                            <div><strong class="text-slate-600">Bairro:</strong> ${cliente.endereco_bairro || cliente.bairro || '—'}</div>
                            <div><strong class="text-slate-600">Cidade:</strong> ${cliente.endereco_cidade || cliente.cidade || '—'}</div>
                        </div>
                        <div class="flex justify-between items-center pt-1 text-[11px] border-t border-slate-200">
                            <div><strong class="text-slate-600">Tel:</strong> ${formatarTelefone(cliente.telefone || '')}</div>
                            <div class="font-bold text-amber-900 text-[10px]">[X] SEMANAL</div>
                        </div>
                    </div>

                    <!-- 4. GRADE DE BAIXAS / RECEBIMENTOS (CARTELA DUPLA DATA | DINHEIRO | SALDO) -->
                    <div>
                        <div class="flex items-center justify-between text-[10px] font-bold uppercase text-slate-600 mb-1">
                            <span>GRADE DE BAIXAS</span>
                            <span class="text-amber-900 font-black">Saldo: ${formatarMoeda(saldoDevedor)}</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-[10px] border border-slate-900 text-center border-collapse">
                                <thead class="bg-slate-200/90 border-b border-slate-900 font-bold">
                                    <tr>
                                        <th class="p-1 border-r border-slate-400">DATA</th>
                                        <th class="p-1 border-r border-slate-400">DINHEIRO</th>
                                        <th class="p-1 border-r-2 border-slate-900">SALDO</th>
                                        <th class="p-1 border-r border-slate-400">DATA</th>
                                        <th class="p-1 border-r border-slate-400">DINHEIRO</th>
                                        <th class="p-1">SALDO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${(function() {
                                        const totalLinhas = Math.max(4, Math.ceil(parcelasOrdenadas.length / 2));
                                        let rowsHtml = '';
                                        let saldoTmp = parseFloat(venda.valor_total || 0);
                                        for (let l = 0; l < totalLinhas; l++) {
                                            const p1 = parcelasOrdenadas[l] || null;
                                            const p2 = parcelasOrdenadas[l + totalLinhas] || null;
                                            
                                            let p1Pago = p1 && p1.status_parcela_codigo === STATUS_PARCELA.PAGA;
                                            let p2Pago = p2 && p2.status_parcela_codigo === STATUS_PARCELA.PAGA;
                                            
                                            let s1Text = '';
                                            if (p1) {
                                                if (p1Pago) saldoTmp -= parseFloat(p1.valor_pago || p1.valor_parcela || 0);
                                                s1Text = formatarMoeda(Math.max(0, saldoTmp));
                                            }
                                            let s2Text = '';
                                            if (p2) {
                                                if (p2Pago) saldoTmp -= parseFloat(p2.valor_pago || p2.valor_parcela || 0);
                                                s2Text = formatarMoeda(Math.max(0, saldoTmp));
                                            }

                                            rowsHtml += `
                                                <tr class="h-6 border-b border-slate-300 ${p1Pago ? 'bg-emerald-50/70 text-emerald-950 font-bold' : ''}">
                                                    <td class="p-0.5 border-r border-slate-300">${p1 ? formatarData(p1.data_pagamento || p1.data_vencimento).slice(0, 5) : ''}</td>
                                                    <td class="p-0.5 border-r border-slate-300 font-bold">${p1 ? (p1Pago ? formatarMoeda(p1.valor_pago || p1.valor_parcela) : '-') : ''}</td>
                                                    <td class="p-0.5 border-r-2 border-slate-900 font-black text-amber-950">${s1Text}</td>
                                                    <td class="p-0.5 border-r border-slate-300">${p2 ? formatarData(p2.data_pagamento || p2.data_vencimento).slice(0, 5) : ''}</td>
                                                    <td class="p-0.5 border-r border-slate-300 font-bold">${p2 ? (p2Pago ? formatarMoeda(p2.valor_pago || p2.valor_parcela) : '-') : ''}</td>
                                                    <td class="p-0.5 font-black text-amber-950">${s2Text}</td>
                                                </tr>
                                            `;
                                        }
                                        return rowsHtml;
                                    })()}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 5. AÇÃO PRINCIPAL DE BAIXA / RECEBIMENTO -->
                    ${proximaParcela ? `
                        <div class="pt-1">
                            <button type="button" 
                                    onclick="event.stopPropagation(); window.abrirModalRecebimento('${proximaParcela.id}')"
                                    class="w-full py-3.5 px-4 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-black text-sm rounded-xl shadow-md active:scale-98 transition flex items-center justify-center gap-2">
                                <span class="text-base">💵</span>
                                <span>RECEBER PARCELA (${formatarMoeda(proximaParcela.valor_parcela)})</span>
                            </button>
                        </div>
                    ` : `
                        <div class="p-2.5 bg-emerald-100/90 border border-emerald-300 text-emerald-800 rounded-xl text-center font-bold text-xs">
                            ✓ CARTÃO TOTALMENTE QUITADO!
                        </div>
                    `}

                    <!-- 6. BOTÕES DE APOIO DO COBRADOR (WhatsApp, Visita, Maps) -->
                    <div class="grid grid-cols-3 gap-2 pt-1">
                        <button type="button" class="btn-marcar-visita py-2 px-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] rounded-lg border border-slate-300 transition"
                                data-cliente-id="${cliente.id}"
                                data-cliente-nome="${cliente.nome || 'Cliente'}">
                            🚪 Visita
                        </button>
                        ${cliente.telefone ? `
                            <a href="https://api.whatsapp.com/send?phone=55${cliente.telefone.replace(/\\D/g, '')}&text=${encodeURIComponent('Olá ' + (cliente.nome || '') + '! Passando para lembrar do seu Cartão Prestanista #' + numeroVenda + '. Saldo restante: ' + formatarMoeda(saldoDevedor))}" 
                               target="_blank" 
                               class="py-2 px-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-[11px] rounded-lg border border-emerald-300 text-center transition flex items-center justify-center gap-1">
                                📱 WhatsApp
                            </a>
                        ` : `
                            <button type="button" class="btn-gerar-cartao py-2 px-2 bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold text-[11px] rounded-lg border border-amber-300 transition"
                                    data-venda-id="${venda.id}"
                                    data-cliente-id="${cliente.id}"
                                    data-venda-index="${index}">
                                🖨️ Cartão
                            </button>
                        `}
                        ${temEnderecoParaMapa ? `
                            <button type="button" class="btn-google-maps py-2 px-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-[11px] rounded-lg border border-indigo-300 transition flex items-center justify-center gap-1"
                                    data-endereco="${enderecoCompleto.replace(/"/g, '&quot;').replace(/'/g, '&#39;')}">
                                🗺️ Mapa
                            </button>
                        ` : `
                            <button type="button" class="btn-ver-detalhes py-2 px-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] rounded-lg border border-slate-300 transition"
                                    data-venda-id="${venda.id}"
                                    data-cliente-id="${cliente.id}">
                                👁️ Ficha
                            </button>
                        `}
                    </div>

                    <div class="text-[9px] text-slate-500 text-center pt-1 border-t border-slate-300">
                        « Deus é Fiel » • Obs.: Não aceitamos devolução.
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    // Remove mensagem de "sem resultados" se existir
    const mensagemVazia = container.querySelector('.mensagem-sem-resultados');
    if (mensagemVazia) {
        mensagemVazia.remove();
    }
    
    // Aplica filtros se houver valores nos campos de filtro
    const filtroNome = document.getElementById('filtro-nome');
    const filtroCpf = document.getElementById('filtro-cpf');
    const filtroData = document.getElementById('filtro-data');
    
    if (filtroNome && filtroNome.value) {
        filtroNome.dispatchEvent(new Event('input'));
    } else if (filtroCpf && filtroCpf.value) {
        filtroCpf.dispatchEvent(new Event('input'));
    } else if (filtroData && filtroData.value) {
        filtroData.dispatchEvent(new Event('change'));
    }
    
    // Adiciona event listeners aos botões de detalhes
    container.querySelectorAll('.btn-ver-detalhes').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation(); // Evita que o clique no card também seja acionado
            const vendaId = btn.getAttribute('data-venda-id');
            const clienteId = btn.getAttribute('data-cliente-id');
            abrirDetalhesVenda(vendaId, clienteId);
        });
    });
    
    // Adiciona event listeners aos botões de gerar cartão
    container.querySelectorAll('.btn-gerar-cartao').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const vendaIndex = parseInt(btn.getAttribute('data-venda-index'));
            gerarImagemCartao(vendaIndex);
        });
    });
    
    // Adiciona event listeners aos botões do Google Maps
    container.querySelectorAll('.btn-google-maps').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const endereco = btn.getAttribute('data-endereco');
            if (endereco) {
                window.abrirGoogleMaps(endereco);
            }
        });
    });
    
    // Adiciona event listeners aos botões de marcar visita
    container.querySelectorAll('.btn-marcar-visita').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const clienteId = btn.getAttribute('data-cliente-id');
            const clienteNome = btn.getAttribute('data-cliente-nome');
            window.abrirModalVisita(clienteId, clienteNome);
        });
    });
    
    // Adiciona event listeners aos botões de marcar visita
    container.querySelectorAll('.btn-marcar-visita').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const clienteId = btn.getAttribute('data-cliente-id');
            const clienteNome = btn.getAttribute('data-cliente-nome');
            abrirModalVisita(clienteId, clienteNome);
        });
    });
}


/**
 * Verifica se uma parcela pode ser paga
 * Regras:
 * 1. Deve estar vencida OU ser do mês atual
 * 2. Não pode ter parcelas anteriores pendentes ou atrasadas
 */
function podePagarParcela(parcela, todasParcelas) {
    // Se já está paga, não pode pagar novamente
    if (parcela.status_parcela_codigo === STATUS_PARCELA.PAGA) {
        return false;
    }
    
    // Ordena todas as parcelas por número
    const parcelasOrdenadas = [...todasParcelas].sort((a, b) => (a.numero_parcela || 0) - (b.numero_parcela || 0));
    
    // Verifica se há parcelas anteriores pendentes ou atrasadas
    const numeroParcelaAtual = parcela.numero_parcela || 0;
    const parcelasAnteriores = parcelasOrdenadas.filter(p => 
        (p.numero_parcela || 0) < numeroParcelaAtual && 
        p.venda_id === parcela.venda_id
    );
    
    // Verifica se há parcelas anteriores não pagas
    const parcelasAnterioresNaoPagas = parcelasAnteriores.filter(p => 
        p.status_parcela_codigo !== STATUS_PARCELA.PAGA
    );
    
    if (parcelasAnterioresNaoPagas.length > 0) {
        // Há parcelas anteriores não pagas, não pode pagar esta
        return false;
    }
    
    // Verifica se a parcela está vencida ou é do mês atual
    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);
    
    const vencimento = new Date(parcela.data_vencimento);
    vencimento.setHours(0, 0, 0, 0);
    
    // Verifica se está vencida
    const estaVencida = vencimento < hoje;
    
    // Verifica se é do mês atual (mesmo mês e ano)
    const mesmoMes = vencimento.getMonth() === hoje.getMonth() && 
                     vencimento.getFullYear() === hoje.getFullYear();
    
    // Pode pagar se estiver vencida OU for do mês atual
    return estaVencida || mesmoMes;
}

/**
 * Renderiza linhas da tabela de parcelas (2 colunas por linha)
 */
function renderizarLinhasParcelas(parcelas, valorTotalVenda) {
    let html = '';
    const totalLinhas = Math.max(4, Math.ceil(parcelas.length / 2));
    let saldoCorrente = parseFloat(valorTotalVenda || 0);
    
    for (let i = 0; i < totalLinhas; i++) {
        const parcela1 = parcelas[i];
        const parcela2 = parcelas[i + totalLinhas];
        
        html += '<tr>';
        
        // Primeira parcela (colunas 1-3: DATA | DINHEIRO | SALDO)
        if (parcela1) {
            const estaPaga = parcela1.status_parcela_codigo === STATUS_PARCELA.PAGA;
            const valorPago = parseFloat(parcela1.valor_pago || parcela1.valor_parcela || 0);
            if (estaPaga) saldoCorrente -= valorPago;
            const classeLinha = estaPaga ? 'parcela-paga font-bold text-emerald-800' : 'parcela-pendente cursor-pointer';
            
            html += `
                <td class="${classeLinha}" onclick="${!estaPaga ? `window.abrirModalRecebimento('${parcela1.id}')` : ''}">${formatarDataParcela(parcela1.data_pagamento || parcela1.data_vencimento)}</td>
                <td class="${classeLinha}" onclick="${!estaPaga ? `window.abrirModalRecebimento('${parcela1.id}')` : ''}">${estaPaga ? formatarMoeda(valorPago) : '-'}</td>
                <td class="${classeLinha} font-bold text-amber-900" onclick="${!estaPaga ? `window.abrirModalRecebimento('${parcela1.id}')` : ''}">${formatarMoeda(Math.max(0, saldoCorrente))}</td>
            `;
        } else {
            html += '<td></td><td></td><td></td>';
        }
        
        // Segunda parcela (colunas 4-6: DATA | DINHEIRO | SALDO)
        if (parcela2) {
            const estaPaga = parcela2.status_parcela_codigo === STATUS_PARCELA.PAGA;
            const valorPago = parseFloat(parcela2.valor_pago || parcela2.valor_parcela || 0);
            if (estaPaga) saldoCorrente -= valorPago;
            const classeLinha = estaPaga ? 'parcela-paga font-bold text-emerald-800' : 'parcela-pendente cursor-pointer';
            
            html += `
                <td class="${classeLinha}" onclick="${!estaPaga ? `window.abrirModalRecebimento('${parcela2.id}')` : ''}">${formatarDataParcela(parcela2.data_pagamento || parcela2.data_vencimento)}</td>
                <td class="${classeLinha}" onclick="${!estaPaga ? `window.abrirModalRecebimento('${parcela2.id}')` : ''}">${estaPaga ? formatarMoeda(valorPago) : '-'}</td>
                <td class="${classeLinha} font-bold text-amber-900" onclick="${!estaPaga ? `window.abrirModalRecebimento('${parcela2.id}')` : ''}">${formatarMoeda(Math.max(0, saldoCorrente))}</td>
            `;
        } else {
            html += '<td></td><td></td><td></td>';
        }
        
        html += '</tr>';
    }
    
    return html;
}

/**
 * Abre detalhes de uma venda específica
 */
async function abrirDetalhesVenda(vendaId, clienteId) {
    // Encontra o item da rota do cliente
    const itemRota = rotaDia.find(r => r.cliente?.id === clienteId);
    if (!itemRota) {
        mostrarToast('Venda não encontrada', 'error');
        return;
    }
    
    // Encontra a venda específica
    const venda = todasVendas.find(v => v.venda.id === vendaId && v.cliente.id === clienteId);
    if (!venda) {
        mostrarToast('Venda não encontrada', 'error');
        return;
    }
    
    clienteSelecionado = venda.cliente;
    // Filtra apenas as parcelas desta venda específica
    parcelasClienteSelecionado = venda.parcelas || [];
    vendasClienteSelecionado = [venda.venda]; // Apenas esta venda
    
    // Renderiza cabeçalho
    renderizarCabecalhoFicha();
    
    // Renderiza parcelas apenas desta venda
    renderizarParcelasFichaVenda(venda);
    
    // Mostra view da ficha
    mostrarViewFicha();
}

/**
 * Abre ficha digital do cliente (método antigo, mantido para compatibilidade)
 */
async function abrirFichaCliente(itemRota) {
    clienteSelecionado = itemRota.cliente;
    parcelasClienteSelecionado = itemRota.parcelas || [];
    vendasClienteSelecionado = itemRota.vendas || [];
    
    // Renderiza cabeçalho
    renderizarCabecalhoFicha();
    
    // Renderiza parcelas
    renderizarParcelasFicha();
    
    // Mostra view da ficha
    mostrarViewFicha();
}

/**
 * Renderiza cabeçalho da ficha
 */
function renderizarCabecalhoFicha() {
    const container = document.getElementById('ficha-cabecalho');
    const cliente = clienteSelecionado;
    
    // Calcula totais da venda atual exibida
    const totalVenda = parcelasClienteSelecionado.reduce((acc, p) => acc + parseFloat(p.valor_parcela || 0), 0);
    const totalPago = parcelasClienteSelecionado.reduce((acc, p) => acc + parseFloat(p.valor_pago || 0), 0);
    const saldoDevedor = totalVenda - totalPago;

    container.innerHTML = `
        <div class="ficha-digital-papel shadow-sm mb-4">
            <div class="flex justify-between items-start border-b pb-2 mb-2">
                <div>
                    <h2 class="text-blue-600 font-black text-lg leading-tight">MIQUEIAS UTILIDADES</h2>
                    <p class="text-[10px] text-gray-500 uppercase font-bold">Móveis • Eletro • Celulares</p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded font-bold uppercase">Digital</span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-1 text-[11px]">
                <div class="flex border-b border-dotted pb-1">
                    <span class="font-bold text-gray-400 w-16 uppercase italic">Nome:</span>
                    <span class="text-gray-900 font-bold uppercase">${cliente.nome_completo || cliente.nome || 'N/A'}</span>
                </div>
                <div class="flex border-b border-dotted pb-1">
                    <span class="font-bold text-gray-400 w-16 uppercase italic">Endereço:</span>
                    <span class="text-gray-900 uppercase truncate">${cliente.endereco_logradouro || cliente.endereco || 'N/A'}</span>
                </div>
                <div class="flex border-b border-dotted pb-1">
                    <span class="font-bold text-gray-400 w-16 uppercase italic">Cidade:</span>
                    <span class="text-gray-900 uppercase">${cliente.endereco_cidade || cliente.cidade || 'N/A'} - ${cliente.endereco_bairro || cliente.bairro || ''}</span>
                </div>
                <div class="grid grid-cols-2 gap-2 mt-1">
                    <div class="text-center p-1 bg-gray-50 rounded">
                        <span class="block text-[8px] text-gray-400 uppercase font-bold">Total Venda</span>
                        <span class="block font-black text-blue-600">${formatarMoeda(totalVenda)}</span>
                    </div>
                    <div class="text-center p-1 bg-blue-600 rounded">
                        <span class="block text-[8px] text-white/70 uppercase font-bold">Saldo Devedor</span>
                        <span class="block font-black text-white">${formatarMoeda(saldoDevedor)}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Renderiza grid de parcelas de uma venda específica
 */
function renderizarParcelasFichaVenda(venda) {
    const container = document.getElementById('ficha-parcelas');
    const parcelas = venda.parcelas || [];
    
    if (parcelas.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-500 py-4">Nenhuma parcela encontrada para esta venda.</p>';
        return;
    }
    
    // Ordena parcelas por número
    const parcelasOrdenadas = [...parcelas].sort((a, b) => (a.numero_parcela || 0) - (b.numero_parcela || 0));
    
    const valorTotal = venda.venda?.valor_total || venda.valor_total || 0;

    let html = `
        <div class="ficha-digital-papel">
            <table class="tabela-parcelas">
                <thead>
                    <tr>
                        <th>DATA</th>
                        <th>DINHEIRO</th>
                        <th>SALDO</th>
                        <th>DATA</th>
                        <th>DINHEIRO</th>
                        <th>SALDO</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    html += renderizarLinhasParcelas(parcelasOrdenadas, valorTotal);
    
    html += `
                </tbody>
            </table>
            <div class="mt-2 p-2 bg-yellow-50 border border-yellow-100 rounded text-[10px] text-yellow-800 text-center italic">
                * Toque em uma parcela para registrar o recebimento.
            </div>
        </div>
    `;

    container.innerHTML = html;
}


/**
 * Renderiza grid de parcelas (todas as vendas do cliente)
 */
function renderizarParcelasFicha() {
    const container = document.getElementById('ficha-parcelas');
    
    if (parcelasClienteSelecionado.length === 0) {
        container.innerHTML = '<p class="col-span-full text-center text-gray-500">Nenhuma parcela encontrada</p>';
        return;
    }
    
    // Filtra apenas parcelas pendentes para exibição (ou todas se quiser ver histórico)
    const parcelasPendentes = parcelasClienteSelecionado.filter(p => p.status_parcela_codigo === STATUS_PARCELA.PENDENTE);
    const parcelasParaExibir = parcelasPendentes.length > 0 ? parcelasPendentes : parcelasClienteSelecionado;
    
    // Ordena parcelas: primeiro por data de vencimento, depois por número da parcela
    const parcelasOrdenadas = [...parcelasParaExibir].sort((a, b) => {
        const dataA = new Date(a.data_vencimento).getTime();
        const dataB = new Date(b.data_vencimento).getTime();
        if (dataA !== dataB) {
            return dataA - dataB; // Ordena por data de vencimento
        }
        // Se mesma data, ordena por número da parcela
        return (a.numero_parcela || 0) - (b.numero_parcela || 0);
    });
    
    // Agrupa parcelas por venda para exibição organizada
    const parcelasPorVenda = {};
    parcelasOrdenadas.forEach(parcela => {
        const vendaId = parcela.venda_id || 'sem-venda';
        if (!parcelasPorVenda[vendaId]) {
            parcelasPorVenda[vendaId] = [];
        }
        parcelasPorVenda[vendaId].push(parcela);
    });
    
    // Renderiza parcelas agrupadas por venda
    let html = '';
    const numVendas = Object.keys(parcelasPorVenda).length;
    
    Object.keys(parcelasPorVenda).forEach(vendaId => {
        const parcelasVenda = parcelasPorVenda[vendaId];
        const primeiraParcela = parcelasVenda[0];
        
        // Se há múltiplas vendas, mostra cabeçalho destacado da venda
        if (numVendas > 1) {
            const parcelasPendentesVenda = parcelasVenda.filter(p => p.status_parcela_codigo === STATUS_PARCELA.PENDENTE).length;
            const parcelasPagasVenda = primeiraParcela.parcelas_pagas_venda || 0;
            const totalParcelasVenda = primeiraParcela.total_parcelas_venda || parcelasVenda.length;
            const valorTotalVenda = primeiraParcela.venda_valor_total || 0;
            const dataVenda = primeiraParcela.venda_data ? formatarData(primeiraParcela.venda_data) : '';
            
            html += `
                <div class="col-span-full mb-3 pb-3 border-b-2 border-blue-300 bg-blue-50 rounded-lg p-3">
                    <div class="flex justify-between items-start mb-1">
                        <div>
                            <p class="text-sm font-bold text-gray-900">Venda ${dataVenda ? `de ${dataVenda}` : ''}</p>
                            <p class="text-xs text-gray-600">Total: ${formatarMoeda(valorTotalVenda)}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-semibold text-gray-700">
                                ${parcelasPendentesVenda} pendente${parcelasPendentesVenda !== 1 ? 's' : ''} / ${totalParcelasVenda} total
                            </p>
                            <p class="text-xs text-gray-600">
                                ${parcelasPagasVenda} paga${parcelasPagasVenda !== 1 ? 's' : ''}
                            </p>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Renderiza parcelas desta venda (ordenadas por número)
        const parcelasOrdenadasVenda = [...parcelasVenda].sort((a, b) => (a.numero_parcela || 0) - (b.numero_parcela || 0));
        
        parcelasOrdenadasVenda.forEach(parcela => {
            const estaPaga = parcela.status_parcela_codigo === STATUS_PARCELA.PAGA;
            const estaAtrasada = new Date(parcela.data_vencimento) < new Date() && !estaPaga;
            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);
            const vencimento = new Date(parcela.data_vencimento);
            vencimento.setHours(0, 0, 0, 0);
            const estaVencendoHoje = vencimento.getTime() === hoje.getTime() && !estaPaga;
            
            // Verifica se pode pagar esta parcela
            const podePagar = podePagarParcela(parcela, parcelasOrdenadasVenda);
            
            // Mostra número da parcela se houver total de parcelas da venda
            const labelParcela = parcela.total_parcelas_venda 
                ? `Parcela ${parcela.numero_parcela}/${parcela.total_parcelas_venda}`
                : `Parcela ${parcela.numero_parcela}`;
            
            html += `
                <div class="border rounded-lg p-2 ${estaPaga ? 'bg-green-50 border-green-300' : estaAtrasada ? 'bg-red-50 border-red-300' : estaVencendoHoje ? 'bg-yellow-50 border-yellow-300' : 'bg-white border-gray-300'}">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-xs font-medium text-gray-600">${formatarDataParcela(parcela.data_vencimento)}</span>
                        ${estaPaga ? '<span class="text-green-600 text-xs">✓ PAGO</span>' : estaAtrasada ? '<span class="text-red-600 text-xs">ATRASADA</span>' : estaVencendoHoje ? '<span class="text-yellow-600 text-xs">VENCE HOJE</span>' : ''}
                    </div>
                    ${parcela.total_parcelas_venda ? `<div class="text-xs text-gray-500 mb-1">${labelParcela}</div>` : ''}
                    <div class="text-sm font-bold text-gray-900 mb-2">${formatarMoeda(parcela.valor_parcela)}</div>
                    ${estaPaga ? `
                        <div class="text-xs text-gray-600">
                            <div>Pago: ${formatarData(parcela.data_pagamento)}</div>
                            ${parcela.valor_pago ? `<div>Valor: ${formatarMoeda(parcela.valor_pago)}</div>` : ''}
                        </div>
                    ` : podePagar ? `
                        <button 
                            class="w-full bg-blue-600 text-white py-1.5 rounded text-xs font-medium hover:bg-blue-700 active:bg-blue-800"
                            data-parcela-id="${parcela.id}"
                            onclick="window.abrirModalRecebimento('${parcela.id}')"
                        >
                            RECEBER
                        </button>
                    ` : `
                        <div class="w-full bg-gray-200 text-gray-500 py-1.5 rounded text-xs font-medium text-center">
                            Pague as anteriores
                        </div>
                    `}
                </div>
            `;
        });
    });
    
    container.innerHTML = html;
}

/**
 * Abre modal de recebimento
 */
window.abrirModalRecebimento = async function(parcelaId) {
    // Primeiro tenta buscar na lista de parcelas do cliente selecionado (se houver)
    parcelaSelecionada = parcelasClienteSelecionado.find(p => p.id === parcelaId);
    
    // Se não encontrou, busca em todasVendas (cards da lista)
    if (!parcelaSelecionada) {
        for (const vendaItem of todasVendas) {
            parcelaSelecionada = vendaItem.parcelas.find(p => p.id === parcelaId);
            if (parcelaSelecionada) {
                // Define o cliente selecionado para o contexto do modal
                clienteSelecionado = vendaItem.cliente;
                break;
            }
        }
    }
    
    // Se ainda não encontrou, busca em rotaDia
    if (!parcelaSelecionada) {
        for (const itemRota of rotaDia) {
            if (itemRota.parcelas) {
                parcelaSelecionada = itemRota.parcelas.find(p => p.id === parcelaId);
                if (parcelaSelecionada) {
                    clienteSelecionado = itemRota.cliente;
                    break;
                }
            }
        }
    }
    
    if (!parcelaSelecionada) {
        console.error('[App] Parcela não encontrada. ID:', parcelaId);
        console.error('[App] parcelasClienteSelecionado:', parcelasClienteSelecionado);
        console.error('[App] todasVendas:', todasVendas);
        mostrarToast('Parcela não encontrada', 'error');
        return;
    }
    
    // Preenche informações no modal
    document.getElementById('modal-parcela-info').textContent = 
        `Parcela ${parcelaSelecionada.numero_parcela} - ${formatarData(parcelaSelecionada.data_vencimento)}`;
    
    document.getElementById('modal-valor').value = parcelaSelecionada.valor_parcela;
    
    // Reseta forma de pagamento
    formaPagamentoSelecionada = null;
    document.getElementById('btn-confirmar-recebimento').disabled = true;
    document.getElementById('btn-pagamento-dinheiro').classList.remove('ring-4', 'ring-blue-300');
    document.getElementById('btn-pagamento-pix').classList.remove('ring-4', 'ring-blue-300');
    
    // Mostra modal
    document.getElementById('modal-recebimento').classList.remove('hidden');
};

/**
 * Seleciona forma de pagamento
 */
function selecionarFormaPagamento(forma) {
    formaPagamentoSelecionada = forma;
    
    // Atualiza UI dos botões
    document.getElementById('btn-pagamento-dinheiro').classList.toggle('ring-4', forma === 'DINHEIRO');
    document.getElementById('btn-pagamento-dinheiro').classList.toggle('ring-blue-300', forma === 'DINHEIRO');
    document.getElementById('btn-pagamento-pix').classList.toggle('ring-4', forma === 'PIX');
    document.getElementById('btn-pagamento-pix').classList.toggle('ring-blue-300', forma === 'PIX');
    
    // Habilita botão de confirmar
    document.getElementById('btn-confirmar-recebimento').disabled = false;
}

/**
 * Confirma recebimento
 */
async function confirmarRecebimento() {
    if (!parcelaSelecionada || !formaPagamentoSelecionada) {
        mostrarToast('Selecione a forma de pagamento', 'warning');
        return;
    }
    
    const valor = parseFloat(document.getElementById('modal-valor').value);
    if (!valor || valor <= 0) {
        mostrarToast('Valor inválido', 'error');
        return;
    }
    
    // Obtém geolocalização
    let geolocalizacao = null;
    try {
        geolocalizacao = await obterGeolocalizacao();
    } catch (error) {
        console.warn('[App] ⚠️ Erro ao obter geolocalização:', error);
        // Continua sem geolocalização
    }
    
    // Cria registro de pagamento
    const pagamento = {
        parcela_id: parcelaSelecionada.id,
        cobrador_id: cobradorAtual.id,
        cliente_id: clienteSelecionado.id,
        usuario_id: obterUsuarioId(),
        tipo_acao: TIPO_ACAO.PAGAMENTO,
        valor_recebido: valor,
        forma_pagamento: formaPagamentoSelecionada,
        observacao: document.getElementById('modal-obs-text').value || '',
        localizacao_lat: geolocalizacao?.lat || null,
        localizacao_lng: geolocalizacao?.lng || null,
        data_acao: new Date().toISOString()
    };
    
    // Adiciona aos pendentes
    await adicionarPagamentoPendente(pagamento);
    
    // Atualiza parcela localmente
    parcelaSelecionada.status_parcela_codigo = STATUS_PARCELA.PAGA;
    parcelaSelecionada.data_pagamento = new Date().toISOString().split('T')[0];
    parcelaSelecionada.valor_pago = valor;
    
    // Atualiza a parcela no array todasVendas
    const vendaIndex = todasVendas.findIndex(v => 
        v.cliente.id === clienteSelecionado.id && 
        v.parcelas.some(p => p.id === parcelaSelecionada.id)
    );
    
    if (vendaIndex !== -1) {
        const parcelaIndex = todasVendas[vendaIndex].parcelas.findIndex(p => p.id === parcelaSelecionada.id);
        if (parcelaIndex !== -1) {
            todasVendas[vendaIndex].parcelas[parcelaIndex] = { ...parcelaSelecionada };
        }
    }
    
    // Atualiza também no rotaDia
    const itemRota = rotaDia.find(r => r.cliente?.id === clienteSelecionado.id);
    if (itemRota && itemRota.parcelas) {
        const parcelaIndexRota = itemRota.parcelas.findIndex(p => p.id === parcelaSelecionada.id);
        if (parcelaIndexRota !== -1) {
            itemRota.parcelas[parcelaIndexRota] = { ...parcelaSelecionada };
        }
    }
    
    // IMPORTANTE: Salva a rota atualizada no IndexedDB para persistir as alterações
    await salvarRotaDia(rotaDia);
    console.log('[App] ✅ Rota atualizada salva no IndexedDB após pagamento');
    
    // Re-renderiza parcelas na ficha
    if (vendasClienteSelecionado && vendasClienteSelecionado.length === 1) {
        // Se está vendo apenas uma venda, usa a função específica
        const vendaAtual = todasVendas.find(v => 
            v.cliente.id === clienteSelecionado.id && 
            v.venda.id === vendasClienteSelecionado[0].id
        );
        if (vendaAtual) {
            renderizarParcelasFichaVenda(vendaAtual);
        }
    } else {
        renderizarParcelasFicha();
    }
    
    // Re-renderiza os cards da lista principal
    renderizarRota();
    
    // Atualiza dashboard
    atualizarDashboard();
    
    // Fecha modal
    fecharModalRecebimento();
    
    // Tenta sincronizar se estiver online
    if (estaOnline()) {
        const resultado = await sincronizarPagamentosPendentes();
        if (resultado.sucesso && resultado.sincronizados > 0) {
            // Se sincronizou com sucesso, recarrega a rota do servidor para ter dados atualizados
            try {
                const usuarioId = obterUsuarioId();
                rotaDia = await baixarRotaDia(cobradorAtual.id, usuarioId);
                // Aplica pagamentos pendentes restantes (caso algum não tenha sido sincronizado)
                await aplicarPagamentosPendentesNaRota();
                renderizarRota();
                atualizarDashboard();
            } catch (error) {
                console.warn('[App] ⚠️ Erro ao recarregar rota após sincronização:', error);
            }
        }
    } else {
        mostrarToast('Pagamento registrado offline. Será sincronizado quando houver conexão.', 'info');
    }
    
    mostrarToast('Pagamento registrado com sucesso!', 'success');
}

/**
 * Fecha modal de recebimento
 */
function fecharModalRecebimento() {
    document.getElementById('modal-recebimento').classList.add('hidden');
    parcelaSelecionada = null;
    formaPagamentoSelecionada = null;
}

/**
 * Variáveis globais para modal de visita
 */
let clienteVisitaSelecionado = null;
let tipoVisitaSelecionado = null;

/**
 * Abre modal de visita sem pagamento
 */
window.abrirModalVisita = function(clienteId, clienteNome) {
    clienteVisitaSelecionado = todasVendas.find(v => v.cliente.id === clienteId);
    
    if (!clienteVisitaSelecionado) {
        mostrarToast('Cliente não encontrado', 'error');
        return;
    }
    
    document.getElementById('modal-visita-cliente-info').textContent = clienteNome;
    tipoVisitaSelecionado = null;
    document.getElementById('btn-confirmar-visita').disabled = true;
    document.getElementById('modal-visita-obs').value = '';
    document.querySelectorAll('.btn-tipo-visita').forEach(btn => {
        btn.classList.remove('ring-4', 'ring-blue-300');
    });
    document.getElementById('modal-visita').classList.remove('hidden');
};

/**
 * Seleciona tipo de visita
 */
window.selecionarTipoVisita = function(tipo) {
    tipoVisitaSelecionado = tipo;
    document.querySelectorAll('.btn-tipo-visita').forEach(btn => {
        const btnTipo = btn.getAttribute('data-tipo');
        if (btnTipo === tipo) {
            btn.classList.add('ring-4', 'ring-blue-300');
        } else {
            btn.classList.remove('ring-4', 'ring-blue-300');
        }
    });
    document.getElementById('btn-confirmar-visita').disabled = false;
};

/**
 * Confirma visita sem pagamento
 */
window.confirmarVisita = async function() {
    if (!clienteVisitaSelecionado || !tipoVisitaSelecionado) {
        mostrarToast('Selecione o tipo de visita', 'warning');
        return;
    }
    
    // Filtra apenas parcelas pendentes que podem ser cobradas (vencidas ou do mês atual)
    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);
    
    const parcelasPendentes = clienteVisitaSelecionado.parcelas.filter(p => {
        if (p.status_parcela_codigo !== STATUS_PARCELA.PENDENTE) {
            return false;
        }
        
        const vencimento = new Date(p.data_vencimento);
        vencimento.setHours(0, 0, 0, 0);
        
        // Verifica se está vencida
        const estaVencida = vencimento < hoje;
        
        // Verifica se é do mês atual
        const mesmoMes = vencimento.getMonth() === hoje.getMonth() && 
                        vencimento.getFullYear() === hoje.getFullYear();
        
        // Pode marcar visita se estiver vencida OU for do mês atual
        return estaVencida || mesmoMes;
    });
    
    if (parcelasPendentes.length === 0) {
        mostrarToast('Cliente não possui parcelas pendentes no período de cobrança (vencidas ou do mês atual)', 'warning');
        window.fecharModalVisita();
        return;
    }
    
    const parcelaReferencia = parcelasPendentes[0];
    let geolocalizacao = null;
    try {
        geolocalizacao = await obterGeolocalizacao();
    } catch (error) {
        console.warn('[App] ⚠️ Erro ao obter geolocalização:', error);
    }
    
    const visita = {
        parcela_id: parcelaReferencia.id,
        cobrador_id: cobradorAtual.id,
        cliente_id: clienteVisitaSelecionado.cliente.id,
        usuario_id: obterUsuarioId(),
        tipo_acao: tipoVisitaSelecionado,
        valor_recebido: 0,
        forma_pagamento: '',
        observacao: document.getElementById('modal-visita-obs').value || '',
        localizacao_lat: geolocalizacao?.lat || null,
        localizacao_lng: geolocalizacao?.lng || null,
        data_acao: new Date().toISOString()
    };
    
    await adicionarPagamentoPendente(visita);
    
    const mensagens = {
        [TIPO_ACAO.VISITA]: 'Visita registrada com sucesso',
        [TIPO_ACAO.AUSENTE]: 'Visita registrada: Cliente ausente',
        [TIPO_ACAO.RECUSA]: 'Visita registrada: Cliente recusou pagamento',
        [TIPO_ACAO.NEGOCIACAO]: 'Visita registrada: Negociação realizada',
    };
    
    mostrarToast(mensagens[tipoVisitaSelecionado] || 'Visita registrada', 'success');
    window.fecharModalVisita();
    
    if (estaOnline()) {
        const resultado = await sincronizarPagamentosPendentes();
        if (resultado.sucesso && resultado.sincronizados > 0) {
            try {
                const usuarioId = obterUsuarioId();
                rotaDia = await baixarRotaDia(cobradorAtual.id, usuarioId);
                await aplicarPagamentosPendentesNaRota();
                renderizarRota();
                atualizarDashboard();
            } catch (error) {
                console.error('[App] Erro ao recarregar rota:', error);
            }
        }
    }
};

/**
 * Fecha modal de visita
 */
window.fecharModalVisita = function() {
    document.getElementById('modal-visita').classList.add('hidden');
    clienteVisitaSelecionado = null;
    tipoVisitaSelecionado = null;
    document.getElementById('modal-visita-obs').value = '';
    document.querySelectorAll('.btn-tipo-visita').forEach(btn => {
        btn.classList.remove('ring-4', 'ring-blue-300');
    });
};

/**
 * Mostra view do dashboard
 */
function mostrarViewDashboard() {
    document.getElementById('view-dashboard').classList.remove('hidden');
    document.getElementById('view-ficha').classList.add('hidden');
}

/**
 * Mostra view da ficha
 */
function mostrarViewFicha() {
    document.getElementById('view-dashboard').classList.add('hidden');
    document.getElementById('view-ficha').classList.remove('hidden');
}

/**
 * Registra Service Worker
 */
async function registrarServiceWorker() {
    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.register(`${CONFIG.URL_BASE_WEB}/prestanista/sw.js`);
            console.log('[SW] ✅ Service Worker registrado:', registration.scope);
            
            // Listener para mensagens do Service Worker
            navigator.serviceWorker.addEventListener('message', (event) => {
                if (event.data.type === 'SYNC_PAGAMENTOS') {
                    sincronizarPagamentosPendentes();
                }
            });
        } catch (error) {
            console.error('[SW] ❌ Erro ao registrar Service Worker:', error);
        }
    }
}

/**
 * Carrega logo da empresa
 */
async function carregarLogoEmpresa() {
    try {
        const logoImg = document.getElementById('logo-empresa');
        if (!logoImg) return;
        
        const response = await fetch(`${API_ENDPOINTS.USUARIO_DADOS_LOJA}?usuario_id=${obterUsuarioId()}`);
        if (response.ok) {
            const dadosLoja = await response.json();
            if (dadosLoja.logo_path) {
                let logoUrl = dadosLoja.logo_path;
                if (!logoUrl.match(/^(https?:\/\/|\/)/)) {
                    logoUrl = CONFIG.URL_BASE_WEB + '/' + logoUrl.replace(/^\//, '');
                }
                logoImg.src = logoUrl;
                logoImg.classList.remove('hidden');
            }
        }
    } catch (error) {
        console.warn('[App] ⚠️ Erro ao carregar logo:', error);
    }
}

/**
 * Gera imagem do cartão da venda exatamente como exibido na interface
 * Dimensões: 10cm x 15cm (378px x 567px a 96 DPI)
 */
async function gerarImagemCartao(vendaIndex) {
    try {
        const vendaItem = todasVendas[vendaIndex];
        if (!vendaItem) {
            mostrarToast('Venda não encontrada', 'error');
            return;
        }

        const cliente = vendaItem.cliente;
        const venda = vendaItem.venda;
        const parcelas = vendaItem.parcelas || [];
        
        // Busca dados da empresa
        let dadosEmpresa = null;
        let logoUrl = null;
        try {
            const response = await fetch(`${API_ENDPOINTS.USUARIO_DADOS_LOJA}?usuario_id=${obterUsuarioId()}`);
            if (response.ok) {
                dadosEmpresa = await response.json();
                if (dadosEmpresa.logo_path) {
                    logoUrl = dadosEmpresa.logo_path;
                    if (!logoUrl.match(/^(https?:\/\/|\/)/)) {
                        logoUrl = CONFIG.URL_BASE_WEB + '/' + logoUrl.replace(/^\//, '');
                    }
                }
            }
        } catch (error) {
            console.warn('[App] ⚠️ Erro ao buscar dados da empresa:', error);
        }
        
        // Ordena parcelas por data de vencimento
        const parcelasOrdenadas = [...parcelas].sort((a, b) => {
            return new Date(a.data_vencimento) - new Date(b.data_vencimento);
        });
        
        const itensVenda = venda.itens || [];
        const numeroVenda = String(vendaIndex + 1).padStart(5, '0');
        
        // Dimensões: 10cm x 15cm = 378px x 567px (96 DPI)
        const largura = 378;
        const altura = 567;
        
        // Cria canvas
        const canvas = document.createElement('canvas');
        canvas.width = largura;
        canvas.height = altura;
        const ctx = canvas.getContext('2d');
        
        // Fundo branco
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, largura, altura);
        
        // Configurações de fonte
        const fonteTitulo = 'bold 11px Arial';
        const fonteNormal = '10px Arial';
        const fontePequena = '9px Arial';
        const fonteBold = 'bold 10px Arial';
        const fonteEmpresa = 'bold 12px Arial';
        const fonteEmpresaPequena = '9px Arial';
        
        let y = 10; // Posição vertical inicial
        const margem = 12;
        const espacamentoLinha = 14;
        const alturaLinha = 20;
        
        // Cabeçalho com Logo e Dados da Empresa
        if (logoUrl) {
            try {
                const logo = await carregarImagem(logoUrl);
                if (logo) {
                    // Desenha logo (máximo 80px de altura)
                    const logoAltura = 30;
                    const logoLargura = (logo.width / logo.height) * logoAltura;
                    const logoX = margem;
                    const logoY = y;
                    
                    ctx.drawImage(logo, logoX, logoY, logoLargura, logoAltura);
                    y += logoAltura + 8;
                }
            } catch (error) {
                console.warn('[App] ⚠️ Erro ao carregar logo:', error);
            }
        }
        
        // Dados da Empresa
        if (dadosEmpresa) {
            ctx.fillStyle = '#111827';
            ctx.font = fonteEmpresa;
            ctx.textAlign = 'left';
            
            if (dadosEmpresa.nome_empresa || dadosEmpresa.nome_fantasia) {
                ctx.fillText(dadosEmpresa.nome_empresa || dadosEmpresa.nome_fantasia || 'Empresa', margem, y);
                y += espacamentoLinha;
            }
            
            ctx.font = fonteEmpresaPequena;
            ctx.fillStyle = '#4B5563';
            
            // Endereço da empresa
            if (dadosEmpresa.endereco || dadosEmpresa.endereco_logradouro) {
                const enderecoEmpresa = [
                    dadosEmpresa.endereco_logradouro || dadosEmpresa.endereco,
                    dadosEmpresa.endereco_numero ? `Nº ${dadosEmpresa.endereco_numero}` : null,
                    dadosEmpresa.endereco_bairro,
                    dadosEmpresa.endereco_cidade,
                    dadosEmpresa.endereco_estado,
                    dadosEmpresa.endereco_cep
                ].filter(Boolean).join(', ');
                
                ctx.fillText(enderecoEmpresa, margem, y);
                y += 10;
            }
            
            // Telefone e outros dados
            if (dadosEmpresa.telefone) {
                ctx.fillText(`Tel: ${dadosEmpresa.telefone}`, margem, y);
                y += 10;
            }
            
            if (dadosEmpresa.email) {
                ctx.fillText(`Email: ${dadosEmpresa.email}`, margem, y);
                y += 10;
            }
            
            y += 5;
            
            // Linha separadora
            ctx.strokeStyle = '#E5E7EB';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(margem, y);
            ctx.lineTo(largura - margem, y);
            ctx.stroke();
            y += 10;
        }
        
        // Seção COMPRADOR (com gradiente índigo/azul como no header)
        // Gradiente: indigo-700 -> indigo-600 -> blue-700
        const gradientComprador = ctx.createLinearGradient(0, y - 5, 0, y + 75);
        gradientComprador.addColorStop(0, '#4f46e5'); // indigo-700
        gradientComprador.addColorStop(0.5, '#4338ca'); // indigo-600
        gradientComprador.addColorStop(1, '#1e40af'); // blue-700
        ctx.fillStyle = gradientComprador;
        ctx.fillRect(0, y - 5, largura, 80); // Fundo com gradiente
        
        ctx.fillStyle = '#FFFFFF'; // Texto branco
        ctx.font = fonteTitulo;
        ctx.textAlign = 'left';
        ctx.fillText('COMPRADOR', margem, y);
        y += espacamentoLinha;
        
        ctx.font = fonteBold;
        ctx.fillText(cliente.nome || cliente.nome_completo || 'N/A', margem, y);
        y += espacamentoLinha;
        
        // Informações de localização do comprador
        ctx.font = fontePequena;
        ctx.fillStyle = '#FFFFFF';
        
        // CPF
        if (cliente.cpf) {
            ctx.fillText(`CPF: ${formatarCPF(cliente.cpf)}`, margem, y);
            y += 10;
        }
        
        // Telefone
        if (cliente.telefone) {
            ctx.fillText(`Tel: ${formatarTelefone(cliente.telefone)}`, margem, y);
            y += 10;
        }
        
        // Endereço
        if (cliente.endereco || cliente.endereco_logradouro || cliente.logradouro) {
            const enderecoTexto = cliente.endereco || 
                (cliente.endereco_logradouro || cliente.logradouro || '') + 
                (cliente.endereco_numero || cliente.numero ? ', ' + (cliente.endereco_numero || cliente.numero) : '') + 
                (cliente.endereco_complemento || cliente.complemento ? ' - ' + (cliente.endereco_complemento || cliente.complemento) : '');
            ctx.fillText(`End: ${enderecoTexto}`, margem, y);
            y += 10;
        }
        
        // Bairro
        if (cliente.endereco_bairro || cliente.bairro) {
            ctx.fillText(`Bairro: ${cliente.endereco_bairro || cliente.bairro}`, margem, y);
            y += 10;
        }
        
        // Cidade e Estado
        if (cliente.endereco_cidade || cliente.cidade || cliente.endereco_estado || cliente.estado) {
            const cidadeEstado = (cliente.endereco_cidade || cliente.cidade || '') + 
                (cliente.endereco_estado || cliente.estado ? ' - ' + (cliente.endereco_estado || cliente.estado) : '');
            ctx.fillText(`Cidade: ${cidadeEstado}`, margem, y);
            y += 10;
        }
        
        y += 5; // Espaço extra após o header
        
        // Seção VENDA - DT VENDA - TOTAL VENDA (com gradiente slate como no header)
        const alturaSecaoVenda = 35;
        // Gradiente: slate-700 -> slate-600 -> slate-700
        const gradientVenda = ctx.createLinearGradient(0, y - 5, 0, y + alturaSecaoVenda);
        gradientVenda.addColorStop(0, '#475569'); // slate-700
        gradientVenda.addColorStop(0.5, '#64748b'); // slate-600
        gradientVenda.addColorStop(1, '#475569'); // slate-700
        ctx.fillStyle = gradientVenda;
        ctx.fillRect(0, y - 5, largura, alturaSecaoVenda); // Fundo com gradiente
        
        // Grid de 3 colunas: VENDA, DT VENDA, TOTAL VENDA
        const larguraColuna = (largura - (margem * 2)) / 3;
        const xCol1 = margem;
        const xCol2 = margem + larguraColuna;
        const xCol3 = margem + (larguraColuna * 2);
        
        ctx.fillStyle = '#FFFFFF'; // Texto branco
        ctx.font = fonteBold;
        ctx.fillText('VENDA', xCol1, y);
        ctx.fillText('DT VENDA', xCol2, y);
        ctx.fillText('TOTAL VENDA', xCol3, y);
        y += 8;
        
        ctx.font = fonteNormal;
        ctx.fillText(`#${numeroVenda}`, xCol1, y);
        ctx.fillText(formatarData(venda.data_venda), xCol2, y);
        ctx.fillText(formatarMoeda(venda.valor_total), xCol3, y);
        y += espacamentoLinha + 8;
        
        // Linha separadora
        ctx.strokeStyle = '#E5E7EB';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(margem, y);
        ctx.lineTo(largura - margem, y);
        ctx.stroke();
        y += 10;
        
        // Seção PRODUTOS
        if (itensVenda.length > 0) {
            ctx.fillStyle = '#374151';
            ctx.font = fonteTitulo;
            ctx.fillText('PRODUTOS', margem, y);
            y += espacamentoLinha;
            
            // Cabeçalho da tabela
            const larguraProduto = (largura - (margem * 2)) * 0.45;
            const larguraQtd = (largura - (margem * 2)) * 0.15;
            const larguraUnit = (largura - (margem * 2)) * 0.20;
            const larguraTotal = (largura - (margem * 2)) * 0.20;
            
            const xProduto = margem;
            const xQtd = xProduto + larguraProduto;
            const xUnit = xQtd + larguraQtd;
            const xTotal = xUnit + larguraUnit;
            
            // Fundo cinza claro para cabeçalho
            ctx.fillStyle = '#F3F4F6';
            ctx.fillRect(margem, y - 12, largura - (margem * 2), 16);
            
            ctx.fillStyle = '#374151';
            ctx.font = fonteBold;
            ctx.fillText('PRODUTO', xProduto, y);
            ctx.fillText('QTD', xQtd, y);
            ctx.fillText('VL.UNIT', xUnit, y);
            ctx.fillText('VL. TOTAL', xTotal, y);
            y += espacamentoLinha;
            
            // Linha separadora
            ctx.strokeStyle = '#E5E7EB';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(margem, y - 5);
            ctx.lineTo(largura - margem, y - 5);
            ctx.stroke();
            
            // Itens da tabela
            ctx.fillStyle = '#111827';
            ctx.font = fontePequena;
            itensVenda.forEach(item => {
                const valorUnitario = item.quantidade > 0 ? item.valor_total / item.quantidade : 0;
                
                // Quebra nome do produto se necessário
                const nomeProduto = item.produto_nome || 'Produto';
                const linhasProduto = quebrarTexto(ctx, nomeProduto, larguraProduto - 4);
                
                linhasProduto.forEach((linha, idx) => {
                    ctx.fillText(linha, xProduto + 2, y + (idx * 10));
                });
                
                const alturaItem = Math.max(linhasProduto.length * 10, 12);
                ctx.fillText(String(item.quantidade || 0), xQtd + 2, y);
                ctx.fillText(formatarMoeda(valorUnitario), xUnit + 2, y);
                ctx.font = fonteBold;
                ctx.fillText(formatarMoeda(item.valor_total), xTotal + 2, y);
                ctx.font = fontePequena;
                
                // Linha separadora entre itens
                ctx.strokeStyle = '#E5E7EB';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(margem, y + alturaItem - 2);
                ctx.lineTo(largura - margem, y + alturaItem - 2);
                ctx.stroke();
                
                y += alturaItem + 2;
            });
            
            y += 5;
        }
        
        // Linha separadora
        ctx.strokeStyle = '#D1D5DB';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(margem, y);
        ctx.lineTo(largura - margem, y);
        ctx.stroke();
        y += 10;
        
        // Seção PARCELAS
        ctx.fillStyle = '#374151';
        ctx.font = fonteTitulo;
        ctx.fillText('PARCELAS', margem, y);
        y += espacamentoLinha;
        
        // Cabeçalho da tabela de parcelas
        const larguraParcela = (largura - (margem * 2)) * 0.15;
        const larguraVenc = (largura - (margem * 2)) * 0.25;
        const larguraValor = (largura - (margem * 2)) * 0.20;
        const larguraSituacao = (largura - (margem * 2)) * 0.20;
        const larguraAcao = (largura - (margem * 2)) * 0.20;
        
        const xParcela = margem;
        const xVenc = xParcela + larguraParcela;
        const xValor = xVenc + larguraVenc;
        const xSituacao = xValor + larguraValor;
        const xAcao = xSituacao + larguraSituacao;
        
        // Fundo cinza claro para cabeçalho
        ctx.fillStyle = '#F3F4F6';
        ctx.fillRect(margem, y - 12, largura - (margem * 2), 16);
        
        ctx.fillStyle = '#374151';
        ctx.font = fonteBold;
        ctx.fillText('PARCELA', xParcela + 2, y);
        ctx.fillText('VENCIMENTO', xVenc + 2, y);
        ctx.fillText('VL PARCELA', xValor + 2, y);
        ctx.fillText('SITUACAO', xSituacao + 2, y);
        ctx.fillText('AÇÃO', xAcao + 2, y);
        y += espacamentoLinha;
        
        // Linha separadora
        ctx.strokeStyle = '#E5E7EB';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(margem, y - 5);
        ctx.lineTo(largura - margem, y - 5);
        ctx.stroke();
        
        // Linhas da tabela de parcelas
        ctx.font = fontePequena;
        parcelasOrdenadas.forEach((parcela, idx) => {
            const estaPaga = parcela.status_parcela_codigo === STATUS_PARCELA.PAGA;
            const estaVencida = new Date(parcela.data_vencimento) < new Date() && !estaPaga;
            
            // Cor de fundo da linha
            if (estaPaga) {
                ctx.fillStyle = '#DCFCE7'; // Verde claro
            } else if (estaVencida) {
                ctx.fillStyle = '#FEE2E2'; // Vermelho claro
            } else {
                ctx.fillStyle = '#FEF3C7'; // Amarelo claro
            }
            ctx.fillRect(margem, y - 10, largura - (margem * 2), alturaLinha);
            
            // Texto da parcela
            const numeroParcela = String(parcela.numero_parcela || idx + 1).padStart(2, '0');
            const totalParcelas = String(parcelas.length).padStart(2, '0');
            
            ctx.fillStyle = '#111827';
            ctx.font = fonteBold;
            ctx.fillText(`${numeroParcela}/${totalParcelas}`, xParcela + 2, y);
            
            ctx.font = fontePequena;
            ctx.fillText(formatarData(parcela.data_vencimento), xVenc + 2, y);
            ctx.fillText(formatarMoeda(parcela.valor_parcela), xValor + 2, y);
            
            // Situação
            let situacao = 'PENDENTE';
            if (estaPaga) {
                situacao = 'PAGA';
                ctx.fillStyle = '#16A34A';
            } else if (estaVencida) {
                situacao = 'VENCIDA';
                ctx.fillStyle = '#DC2626';
            } else {
                situacao = 'PENDENTE';
                ctx.fillStyle = '#CA8A04';
            }
            ctx.font = fonteBold;
            ctx.fillText(situacao, xSituacao + 2, y);
            
            // Ação
            ctx.fillStyle = '#111827';
            if (estaPaga) {
                ctx.fillText('✓', xAcao + 2, y);
            } else {
                ctx.fillText('-', xAcao + 2, y);
            }
            
            // Linha separadora
            ctx.strokeStyle = '#E5E7EB';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(margem, y + alturaLinha - 10);
            ctx.lineTo(largura - margem, y + alturaLinha - 10);
            ctx.stroke();
            
            y += alturaLinha;
        });
        
        // Converte canvas para imagem e faz download
        canvas.toBlob((blob) => {
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `cartao-venda-${venda.id.substring(0, 8)}-${Date.now()}.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            
            mostrarToast('Cartão gerado com sucesso!', 'success');
        }, 'image/png');
        
    } catch (error) {
        console.error('[App] ❌ Erro ao gerar imagem do cartão:', error);
        mostrarToast('Erro ao gerar cartão', 'error');
    }
}

/**
 * Quebra texto em múltiplas linhas se exceder largura máxima
 */
function quebrarTexto(ctx, texto, maxWidth) {
    const palavras = texto.split(' ');
    const linhas = [];
    let linhaAtual = '';
    
    palavras.forEach(palavra => {
        const teste = linhaAtual + (linhaAtual ? ' ' : '') + palavra;
        const metrica = ctx.measureText(teste);
        
        if (metrica.width > maxWidth && linhaAtual) {
            linhas.push(linhaAtual);
            linhaAtual = palavra;
        } else {
            linhaAtual = teste;
        }
    });
    
    if (linhaAtual) {
        linhas.push(linhaAtual);
    }
    
    return linhas;
}

/**
 * Carrega imagem de uma URL e retorna como Image object
 */
function carregarImagem(url) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous'; // Permite carregar imagens de outros domínios
        img.onload = () => resolve(img);
        img.onerror = () => reject(new Error('Erro ao carregar imagem'));
        img.src = url;
    });
}

// Inicializa quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

