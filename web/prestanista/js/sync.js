// sync.js - Serviço de sincronização em background

import { API_ENDPOINTS, CONFIG, STATUS_PARCELA } from './config.js';
import { 
    carregarPagamentosPendentes, 
    removerPagamentoPendente,
    atualizarUltimaSincronizacao,
    salvarRotaDia,
    salvarClientesCache,
    salvarParcelasCache
} from './storage.js';

/**
 * Verifica se está online
 */
export function estaOnline() {
    return navigator.onLine;
}

/**
 * Sincroniza pagamentos pendentes com o servidor
 */
export async function sincronizarPagamentosPendentes() {
    if (!estaOnline()) {
        console.log('[Sync] ⚠️ Offline - não é possível sincronizar');
        return { sucesso: false, motivo: 'offline' };
    }

    const pagamentos = await carregarPagamentosPendentes();
    
    if (pagamentos.length === 0) {
        console.log('[Sync] ✅ Nenhum pagamento pendente');
        return { sucesso: true, sincronizados: 0 };
    }

    console.log(`[Sync] 🔄 Sincronizando ${pagamentos.length} pagamento(s) pendente(s)...`);

    const resultados = {
        sucesso: true,
        sincronizados: 0,
        falhas: 0,
        erros: []
    };

    for (const pagamento of pagamentos) {
        try {
            const response = await fetch(API_ENDPOINTS.REGISTRAR_PAGAMENTO, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(pagamento)
            });

            if (response.ok) {
                await removerPagamentoPendente(pagamento.id_local);
                resultados.sincronizados++;
                console.log(`[Sync] ✅ Pagamento sincronizado: ${pagamento.id_local}`);
            } else {
                const erro = await response.json();
                resultados.falhas++;
                resultados.erros.push({ id: pagamento.id_local, erro: erro });
                console.error(`[Sync] ❌ Erro ao sincronizar pagamento ${pagamento.id_local}:`, erro);
            }
        } catch (error) {
            resultados.falhas++;
            resultados.erros.push({ id: pagamento.id_local, erro: error.message });
            console.error(`[Sync] ❌ Exceção ao sincronizar pagamento ${pagamento.id_local}:`, error);
        }
    }

    if (resultados.sincronizados > 0) {
        await atualizarUltimaSincronizacao();
    }

    return resultados;
}

/**
 * Aplica pagamentos pendentes em uma rota baixada do servidor
 * Esta função é usada quando baixamos a rota do servidor para garantir
 * que os pagamentos offline sejam mantidos
 */
async function aplicarPagamentosPendentesNaRotaBaixada(rota) {
    try {
        const pagamentosPendentes = await carregarPagamentosPendentes();
        
        if (pagamentosPendentes.length === 0) {
            return; // Nenhum pagamento pendente
        }
        
        console.log(`[Sync] 🔄 Aplicando ${pagamentosPendentes.length} pagamento(s) pendente(s) na rota baixada...`);
        
        let pagamentosAplicados = 0;
        
        // Para cada pagamento pendente, atualiza a parcela correspondente na rota
        for (const pagamento of pagamentosPendentes) {
            // Encontra o item da rota do cliente
            const itemRota = rota.find(r => r.cliente?.id === pagamento.cliente_id);
            
            if (itemRota && itemRota.parcelas) {
                // Encontra a parcela
                const parcela = itemRota.parcelas.find(p => p.id === pagamento.parcela_id);
                
                if (parcela) {
                    // Atualiza a parcela com o status de pago
                    parcela.status_parcela_codigo = STATUS_PARCELA.PAGA;
                    parcela.data_pagamento = pagamento.data_acao ? pagamento.data_acao.split('T')[0] : new Date().toISOString().split('T')[0];
                    parcela.valor_pago = pagamento.valor_recebido;
                    pagamentosAplicados++;
                    
                    console.log(`[Sync] ✅ Pagamento aplicado: parcela ${parcela.numero_parcela} (ID: ${parcela.id})`);
                }
            }
        }
        
        if (pagamentosAplicados > 0) {
            console.log(`[Sync] ✅ ${pagamentosAplicados} pagamento(s) aplicado(s) na rota baixada`);
        }
        
    } catch (error) {
        console.error('[Sync] ❌ Erro ao aplicar pagamentos pendentes na rota baixada:', error);
    }
}

/**
 * Baixa rota do dia do servidor
 */
export async function baixarRotaDia(cobradorId, usuarioId) {
    if (!estaOnline()) {
        throw new Error('Sem conexão com internet');
    }

    try {
        const response = await fetch(
            `${API_ENDPOINTS.ROTA_COBRANCA_DIA}?cobrador_id=${cobradorId}&usuario_id=${usuarioId}&data=${new Date().toISOString().split('T')[0]}`
        );

        if (!response.ok) {
            throw new Error(`Erro HTTP: ${response.status}`);
        }

        const rota = await response.json();
        
        // Verifica se há erro na resposta
        if (rota.erro) {
            console.error('[Sync] ❌ Erro do servidor:', rota.erro);
            if (rota.debug) {
                console.error('[Sync] 🔍 Debug info:', JSON.stringify(rota.debug, null, 2));
                
                // Se houver períodos encontrados, mostra informações
                if (rota.debug.periodos_encontrados) {
                    console.error('[Sync] 📋 Períodos encontrados no sistema:');
                    rota.debug.periodos_encontrados.forEach(p => {
                        console.error(`  - ${p.descricao} (Status: ${p.status}, ID: ${p.id})`);
                    });
                }
                
                // Se houver carteiras sem filtro, mostra informações
                if (rota.debug.carteiras_info) {
                    console.error('[Sync] 📋 Carteiras do cobrador (sem filtro de período):');
                    rota.debug.carteiras_info.forEach(c => {
                        console.error(`  - Carteira ID: ${c.id}, Período ID: ${c.periodo_id}, Ativo: ${c.ativo}`);
                    });
                }
            }
            throw new Error(rota.erro);
        }
        
        // Verifica se rota é um array válido
        if (!Array.isArray(rota)) {
            console.error('[Sync] ❌ Resposta inválida do servidor:', rota);
            throw new Error('Resposta inválida do servidor. Esperado array, recebido: ' + typeof rota);
        }
        
        // IMPORTANTE: Aplica pagamentos pendentes ANTES de salvar
        // Isso garante que os pagamentos offline sejam mantidos mesmo após baixar do servidor
        await aplicarPagamentosPendentesNaRotaBaixada(rota);
        
        // Salva localmente (já com pagamentos pendentes aplicados)
        await salvarRotaDia(rota);
        
        // Atualiza cache de clientes e parcelas
        const clientesCache = {};
        const parcelasCache = {};
        
        for (const item of rota) {
            if (item.cliente) {
                clientesCache[item.cliente.id] = item.cliente;
            }
            if (item.parcelas) {
                parcelasCache[item.cliente?.id] = item.parcelas;
            }
        }
        
        await salvarClientesCache(clientesCache);
        await salvarParcelasCache(parcelasCache);
        
        await atualizarUltimaSincronizacao();
        
        console.log('[Sync] ✅ Rota do dia baixada:', rota.length, 'clientes');
        return rota;
    } catch (error) {
        console.error('[Sync] ❌ Erro ao baixar rota do dia:', error);
        throw error;
    }
}

/**
 * Registra Service Worker para sincronização em background
 */
export async function registrarServiceWorkerSync() {
    if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
        try {
            const registration = await navigator.serviceWorker.ready;
            
            // Registra tag de sincronização
            await registration.sync.register(CONFIG.SYNC_TAG);
            console.log('[Sync] ✅ Service Worker Sync registrado');
            return true;
        } catch (error) {
            console.error('[Sync] ❌ Erro ao registrar Service Worker Sync:', error);
            return false;
        }
    } else {
        console.warn('[Sync] ⚠️ Service Worker Sync não suportado');
        return false;
    }
}

/**
 * Monitora conexão e sincroniza automaticamente quando voltar online
 */
export function iniciarMonitoramentoConexao(callbackSincronizacao) {
    window.addEventListener('online', async () => {
        console.log('[Sync] 🌐 Conexão restabelecida - iniciando sincronização...');
        if (callbackSincronizacao) {
            await callbackSincronizacao();
        } else {
            await sincronizarPagamentosPendentes();
        }
    });

    window.addEventListener('offline', () => {
        console.log('[Sync] 📴 Conexão perdida - modo offline ativado');
    });
}

