// seller.js - Gerenciamento de vendedores/colaboradores

import { API_ENDPOINTS } from './config.js';
import { validarCPF, formatarCPF } from './utils.js';

/**
 * Busca vendedor por CPF
 */
export async function buscarVendedorPorCpf(cpf, idUsuarioLoja) {
    if (!validarCPF(cpf)) {
        throw new Error('CPF inválido');
    }

    if (!idUsuarioLoja) {
        throw new Error('ID da loja não identificado');
    }

    const cpfLimpo = cpf.replace(/[^\d]/g, '');
    
    const response = await fetch(`${API_ENDPOINTS.COLABORADOR_BUSCA_CPF}?cpf=${cpfLimpo}&usuario_id=${idUsuarioLoja}`);
    const data = await response.json();

    if (!response.ok) {
        throw new Error('Erro ao buscar vendedor');
    }

    return {
        existe: data.existe,
        colaborador: data.colaborador || null
    };
}

/**
 * Formata dados do vendedor para exibição
 */
export function formatarDadosVendedor(vendedor) {
    if (!vendedor) return '';

    return {
        nome: vendedor.nome_completo,
        cpf: formatarCPF(vendedor.cpf),
        id: vendedor.id
    };
}