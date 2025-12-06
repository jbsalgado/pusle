// app.js - Aplicação principal do Módulo Prestanista

import { CONFIG, API_ENDPOINTS, STATUS_PARCELA, TIPO_ACAO, FORMAS_PAGAMENTO_COBRADOR } from './config.js';
import { 
    initDB, 
    salvarCobrador, 
    carregarCobrador, 
    salvarRotaDia, 
    carregarRotaDia,
    adicionarPagamentoPendente,
    carregarPagamentosPendentes,
    carregarClientesCache,
    carregarParcelasCache
} from './storage.js';
import { 
    sincronizarPagamentosPendentes, 
    baixarRotaDia, 
    estaOnline,
    iniciarMonitoramentoConexao
} from './sync.js';
import { 
    formatarMoeda, 
    formatarData, 
    formatarDataParcela, 
    obterGeolocalizacao, 
    mostrarToast 
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
    // Botão sincronizar rota
    document.getElementById('btn-sincronizar-rota').addEventListener('click', async () => {
        await sincronizarRota();
    });
    
    // Botão sincronizar
    document.getElementById('btn-sincronizar').addEventListener('click', async () => {
        await sincronizarTudo();
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
    
    // Atualiza status de conexão periodicamente
    setInterval(atualizarStatusConexao, 5000);
}

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
        
        // Formata número da venda com zeros à esquerda
        const numeroVenda = String(index + 1).padStart(5, '0');
        
        // Prepara dados dos produtos
        const itensVenda = venda.itens && venda.itens.length > 0 ? venda.itens : [];
        
        return `
            <div class="card-ficha" 
                 data-index="${index}"
                 data-cliente-id="${cliente.id}"
                 data-venda-id="${venda.id}">
                <div class="p-4">
                    <!-- COMPRADOR -->
                    <div class="mb-4">
                        <h2 class="text-sm font-bold text-gray-700 uppercase mb-2">COMPRADOR</h2>
                        <h3 class="text-base font-bold text-gray-900">${cliente.nome || 'Cliente'}</h3>
                    </div>
                    
                    <!-- VENDA - DT VENDA - TOTAL VENDA -->
                    <div class="mb-4 pb-3 border-b-2 border-gray-300">
                        <div class="grid grid-cols-3 gap-2 text-xs">
                            <div>
                                <p class="font-bold text-gray-700 uppercase mb-1">VENDA</p>
                                <p class="text-sm font-bold text-gray-900">#${numeroVenda}</p>
                            </div>
                            <div>
                                <p class="font-bold text-gray-700 uppercase mb-1">DT VENDA</p>
                                <p class="text-sm font-medium text-gray-900">${formatarData(venda.data_venda)}</p>
                            </div>
                            <div>
                                <p class="font-bold text-gray-700 uppercase mb-1">TOTAL VENDA</p>
                                <p class="text-sm font-bold text-blue-600">${formatarMoeda(venda.valor_total)}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- PRODUTOS -->
                    ${itensVenda.length > 0 ? `
                        <div class="mb-4 pb-3 border-b-2 border-gray-300">
                            <h3 class="text-xs font-bold text-gray-700 uppercase mb-2">PRODUTOS</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs border-collapse">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th class="text-left p-2 border border-gray-300 font-bold text-gray-700">PRODUTO</th>
                                            <th class="text-center p-2 border border-gray-300 font-bold text-gray-700">QTD</th>
                                            <th class="text-right p-2 border border-gray-300 font-bold text-gray-700">VL.UNIT</th>
                                            <th class="text-right p-2 border border-gray-300 font-bold text-gray-700">VL. TOTAL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${itensVenda.map(item => {
                                            const valorUnitario = item.quantidade > 0 ? item.valor_total / item.quantidade : 0;
                                            return `
                                                <tr>
                                                    <td class="p-2 border border-gray-300 text-gray-900">${item.produto_nome || 'Produto'}</td>
                                                    <td class="p-2 border border-gray-300 text-center text-gray-900">${item.quantidade || 0}</td>
                                                    <td class="p-2 border border-gray-300 text-right text-gray-900">${formatarMoeda(valorUnitario)}</td>
                                                    <td class="p-2 border border-gray-300 text-right font-medium text-gray-900">${formatarMoeda(item.valor_total)}</td>
                                                </tr>
                                            `;
                                        }).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- PARCELAS -->
                    <div class="mb-4 pb-3 border-b-2 border-gray-300">
                        <h3 class="text-xs font-bold text-gray-700 uppercase mb-2">PARCELAS</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs border-collapse">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="text-center p-2 border border-gray-300 font-bold text-gray-700">PARCELA</th>
                                        <th class="text-center p-2 border border-gray-300 font-bold text-gray-700">VENCIMENTO</th>
                                        <th class="text-right p-2 border border-gray-300 font-bold text-gray-700">VL PARCELA</th>
                                        <th class="text-center p-2 border border-gray-300 font-bold text-gray-700">SITUACAO</th>
                                        <th class="text-center p-2 border border-gray-300 font-bold text-gray-700">AÇÃO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${parcelasOrdenadas.map((parcela, idx) => {
                                        const estaPaga = parcela.status_parcela_codigo === STATUS_PARCELA.PAGA;
                                        const estaVencida = new Date(parcela.data_vencimento) < new Date() && !estaPaga;
                                        const situacao = estaPaga ? 'PAGA' : (estaVencida ? 'VENCIDA' : 'PENDENTE');
                                        const situacaoClass = estaPaga ? 'text-green-600' : (estaVencida ? 'text-red-600' : 'text-yellow-600');
                                        const numeroParcela = String(parcela.numero_parcela || idx + 1).padStart(2, '0');
                                        const totalParcelas = String(parcelas.length).padStart(2, '0');
                                        
                                        // Verifica se pode pagar esta parcela
                                        const podePagar = podePagarParcela(parcela, parcelasOrdenadas);
                                        
                                        return `
                                            <tr class="${estaPaga ? 'bg-green-50' : (estaVencida ? 'bg-red-50' : '')}">
                                                <td class="p-2 border border-gray-300 text-center text-gray-900 font-medium">${numeroParcela}/${totalParcelas}</td>
                                                <td class="p-2 border border-gray-300 text-center text-gray-900">${formatarData(parcela.data_vencimento)}</td>
                                                <td class="p-2 border border-gray-300 text-right text-gray-900">${formatarMoeda(parcela.valor_parcela)}</td>
                                                <td class="p-2 border border-gray-300 text-center ${situacaoClass} font-medium">${situacao}</td>
                                                <td class="p-2 border border-gray-300 text-center">
                                                    ${estaPaga ? '<span class="text-green-600 text-xs">✓</span>' : podePagar ? `
                                                        <button class="bg-blue-600 text-white px-3 py-1 rounded text-xs font-medium hover:bg-blue-700 active:bg-blue-800" 
                                                                onclick="event.stopPropagation(); window.abrirModalRecebimento('${parcela.id}')">
                                                            PAGAR
                                                        </button>
                                                    ` : '<span class="text-gray-400 text-xs" title="Pague as parcelas anteriores primeiro">-</span>'}
                                                </td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Botão Ver Detalhes -->
                    <div class="text-center">
                        <button class="btn-ver-detalhes bg-blue-600 text-white px-6 py-2 rounded-lg font-medium text-sm hover:bg-blue-700 active:bg-blue-800 transition-colors"
                                data-venda-id="${venda.id}"
                                data-cliente-id="${cliente.id}">
                            VER DETALHES DA VENDA
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    // Adiciona event listeners aos botões de detalhes
    container.querySelectorAll('.btn-ver-detalhes').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation(); // Evita que o clique no card também seja acionado
            const vendaId = btn.getAttribute('data-venda-id');
            const clienteId = btn.getAttribute('data-cliente-id');
            abrirDetalhesVenda(vendaId, clienteId);
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
function renderizarLinhasParcelas(parcelas) {
    let html = '';
    const linhas = Math.ceil(parcelas.length / 2);
    
    for (let i = 0; i < linhas; i++) {
        const parcela1 = parcelas[i * 2];
        const parcela2 = parcelas[i * 2 + 1];
        
        html += '<tr>';
        
        // Primeira parcela (colunas 1-3)
        if (parcela1) {
            const estaPaga = parcela1.status_parcela_codigo === STATUS_PARCELA.PAGA;
            const estaVencida = new Date(parcela1.data_vencimento) < new Date() && !estaPaga;
            const classeLinha = estaPaga ? 'parcela-paga' : (estaVencida ? 'parcela-vencida' : 'parcela-pendente');
            
            html += `
                <td class="${classeLinha}">${formatarDataParcela(parcela1.data_vencimento)}</td>
                <td class="${classeLinha}">${estaPaga ? formatarMoeda(parcela1.valor_pago || parcela1.valor_parcela) : formatarMoeda(parcela1.valor_parcela)}</td>
                <td class="${classeLinha}">${estaPaga ? '✓' : ''}</td>
            `;
        } else {
            html += '<td></td><td></td><td></td>';
        }
        
        // Segunda parcela (colunas 4-6)
        if (parcela2) {
            const estaPaga = parcela2.status_parcela_codigo === STATUS_PARCELA.PAGA;
            const estaVencida = new Date(parcela2.data_vencimento) < new Date() && !estaPaga;
            const classeLinha = estaPaga ? 'parcela-paga' : (estaVencida ? 'parcela-vencida' : 'parcela-pendente');
            
            html += `
                <td class="${classeLinha}">${formatarDataParcela(parcela2.data_vencimento)}</td>
                <td class="${classeLinha}">${estaPaga ? formatarMoeda(parcela2.valor_pago || parcela2.valor_parcela) : formatarMoeda(parcela2.valor_parcela)}</td>
                <td class="${classeLinha}">${estaPaga ? '✓' : ''}</td>
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
    
    container.innerHTML = `
        <h2 class="text-xl font-bold text-gray-900 mb-3">FICHA DE PRESTAÇÃO</h2>
        <div class="space-y-2 text-sm">
            <div>
                <span class="font-medium text-gray-700">Cliente:</span>
                <span class="text-gray-900 ml-2">${cliente.nome || 'N/A'}</span>
            </div>
            <div>
                <span class="font-medium text-gray-700">Endereço:</span>
                <span class="text-gray-900 ml-2">
                    ${[cliente.endereco, cliente.bairro, cliente.cidade, cliente.estado].filter(Boolean).join(', ')}
                </span>
            </div>
            ${cliente.telefone ? `
                <div>
                    <span class="font-medium text-gray-700">Telefone:</span>
                    <span class="text-gray-900 ml-2">${cliente.telefone}</span>
                </div>
            ` : ''}
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
    
    let html = `
        <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="text-center p-2 border border-gray-300 font-bold text-gray-700">PARCELA</th>
                        <th class="text-center p-2 border border-gray-300 font-bold text-gray-700">VENCIMENTO</th>
                        <th class="text-right p-2 border border-gray-300 font-bold text-gray-700">VL PARCELA</th>
                        <th class="text-center p-2 border border-gray-300 font-bold text-gray-700">SITUACAO</th>
                        <th class="text-center p-2 border border-gray-300 font-bold text-gray-700">AÇÃO</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    parcelasOrdenadas.forEach((parcela) => {
        const estaPaga = parcela.status_parcela_codigo === STATUS_PARCELA.PAGA;
        const estaVencida = new Date(parcela.data_vencimento) < new Date() && !estaPaga;
        const situacao = estaPaga ? 'PAGA' : (estaVencida ? 'VENCIDA' : 'PENDENTE');
        const situacaoClass = estaPaga ? 'text-green-600' : (estaVencida ? 'text-red-600' : 'text-yellow-600');
        const numeroParcela = String(parcela.numero_parcela || 0).padStart(2, '0');
        const totalParcelas = String(parcelas.length).padStart(2, '0');
        
        // Verifica se pode pagar esta parcela
        const podePagar = podePagarParcela(parcela, parcelasOrdenadas);
        
        html += `
            <tr class="${estaPaga ? 'bg-green-50' : (estaVencida ? 'bg-red-50' : '')}">
                <td class="p-2 border border-gray-300 text-center text-gray-900 font-medium">${numeroParcela}/${totalParcelas}</td>
                <td class="p-2 border border-gray-300 text-center text-gray-900">${formatarData(parcela.data_vencimento)}</td>
                <td class="p-2 border border-gray-300 text-right text-gray-900">${formatarMoeda(parcela.valor_parcela)}</td>
                <td class="p-2 border border-gray-300 text-center ${situacaoClass} font-medium">${situacao}</td>
                <td class="p-2 border border-gray-300 text-center">
                    ${estaPaga ? '<span class="text-green-600 text-xs font-bold">✓</span>' : podePagar ? `
                        <button class="bg-blue-600 text-white px-3 py-1 rounded text-xs font-medium hover:bg-blue-700 active:bg-blue-800" 
                                onclick="event.stopPropagation(); window.abrirModalRecebimento('${parcela.id}')">
                            PAGAR
                        </button>
                    ` : '<span class="text-gray-400 text-xs" title="Pague as parcelas anteriores primeiro">-</span>'}
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
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

// Inicializa quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

