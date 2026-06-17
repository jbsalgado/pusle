// payment.js - Gerenciamento de formas de pagamento e parcelas

import { API_ENDPOINTS, CONFIG } from './config.js';

/**
 * Carrega formas de pagamento disponíveis
 */
export async function carregarFormasPagamento(idUsuarioLoja) {
    if (!idUsuarioLoja) {
        throw new Error('ID da loja não identificado');
    }

    const response = await fetch(`${API_ENDPOINTS.FORMA_PAGAMENTO}?usuario_id=${idUsuarioLoja}`);
    
    if (!response.ok) {
        throw new Error(`Status: ${response.status}`);
    }

    const formas = await response.json();
    return formas || [];
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