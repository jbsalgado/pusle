// payment.js - Gerenciamento de formas de pagamento e parcelas

import { API_ENDPOINTS, CONFIG } from './config.js';
import { salvarFormasPagamento, carregarFormasPagamentoCache } from './storage.js';

/**
 * Carrega formas de pagamento disponíveis
 * Tenta carregar da API quando online, usa cache quando offline
 */
export async function carregarFormasPagamento(idUsuarioLoja) {
    if (!idUsuarioLoja) {
        throw new Error('ID da loja não identificado');
    }

    const estaOnline = navigator.onLine;
    
    // Se estiver online, tenta carregar da API
    if (estaOnline) {
        try {
            const response = await fetch(`${API_ENDPOINTS.FORMA_PAGAMENTO}?usuario_id=${idUsuarioLoja}`);
            
            if (!response.ok) {
                throw new Error(`Status: ${response.status}`);
            }

            const formas = await response.json();
            const formasArray = Array.isArray(formas) ? formas : [];
            
            // Salva no cache para uso offline
            if (formasArray.length > 0) {
                await salvarFormasPagamento(formasArray);
                console.log('[Payment] ✅ Formas de pagamento carregadas da API e salvas no cache');
            }
            
            return formasArray;
        } catch (error) {
            console.warn('[Payment] ⚠️ Erro ao carregar formas de pagamento da API:', error);
            // Se falhar, tenta usar o cache
            const formasCache = await carregarFormasPagamentoCache();
            if (formasCache.length > 0) {
                console.log('[Payment] 📦 Usando formas de pagamento do cache (API falhou)');
                return formasCache;
            }
            throw error;
        }
    } else {
        // Se estiver offline, usa o cache
        console.log('[Payment] 📦 Modo offline: carregando formas de pagamento do cache');
        const formasCache = await carregarFormasPagamentoCache();
        
        if (formasCache.length > 0) {
            console.log('[Payment] ✅ Formas de pagamento carregadas do cache (offline)');
            return formasCache;
        } else {
            console.warn('[Payment] ⚠️ Nenhuma forma de pagamento encontrada no cache');
            // Retorna array vazio em vez de lançar erro, para não bloquear a interface
            return [];
        }
    }
}

/**
 * Calcula valor das parcelas
 */
export async function calcularParcelas(valorBase, numeroParcelas, idUsuarioLoja) {
    if (numeroParcelas <= 1) {
        return null;
    }

    if (valorBase <= 0) {
        return null;
    }

    if (!idUsuarioLoja) {
        throw new Error('ID da loja não identificado');
    }

    const url = `${API_ENDPOINTS.CALCULO_PARCELA}?valor_base=${valorBase}&numero_parcelas=${numeroParcelas}&usuario_id=${idUsuarioLoja}`;
    const response = await fetch(url);
    
    if (!response.ok) {
        throw new Error(`Erro ${response.status} ao calcular parcelas`);
    }

    const data = await response.json();
    
    return {
        numeroParcelas: data.numero_parcelas,
        valorParcela: data.valor_parcela,
        valorTotalPrazo: data.valor_total_prazo,
        acrescimoPercentual: data.acrescimo_percentual || 0
    };
}

/**
 * Formata informação de parcelas para exibição
 */
export function formatarInfoParcelas(dadosParcela) {
    if (!dadosParcela) return '';

    const valorParcelaFmt = dadosParcela.valorParcela.toFixed(2).replace('.', ',');
    const valorTotalFmt = dadosParcela.valorTotalPrazo.toFixed(2).replace('.', ',');

    if (dadosParcela.acrescimoPercentual > 0) {
        return `
            <span class="font-bold text-blue-600">${dadosParcela.numeroParcelas}x de R$ ${valorParcelaFmt}</span>
            <br>
            <span class="text-xs">(Total a prazo: R$ ${valorTotalFmt})</span>
        `;
    } else {
        return `
            <span class="font-bold text-blue-600">${dadosParcela.numeroParcelas}x de R$ ${valorParcelaFmt}</span>
            <br>
            <span class="text-xs">(Total: R$ ${valorTotalFmt}, sem acréscimo)</span>
        `;
    }
}