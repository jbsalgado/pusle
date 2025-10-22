// customer.js - Gerenciamento de clientes

import { API_ENDPOINTS } from './config.js';
import { validarCPF, formatarCPF } from './utils.js';

let clienteAtual = null;

/**
 * Obtém cliente atual
 */
export function getClienteAtual() {
    return clienteAtual;
}

/**
 * Define cliente atual
 */
export function setClienteAtual(cliente) {
    clienteAtual = cliente;
}

/**
 * Busca cliente por CPF
 */
export async function buscarClientePorCpf(cpf, idUsuarioLoja) {
    if (!validarCPF(cpf)) {
        throw new Error('CPF inválido');
    }

    if (!idUsuarioLoja) {
        throw new Error('ID da loja não identificado');
    }

    const cpfLimpo = cpf.replace(/[^\d]/g, '');
    
    const response = await fetch(`${API_ENDPOINTS.CLIENTE_BUSCA_CPF}?cpf=${cpfLimpo}&usuario_id=${idUsuarioLoja}`);
    const data = await response.json();

    if (!response.ok) {
        throw new Error('Erro ao buscar cliente');
    }

    return {
        existe: data.existe,
        cliente: data.cliente || null
    };
}

/**
 * Faz login do cliente
 */
export async function fazerLogin(cpf, senha, idUsuarioLoja) {
    const response = await fetch(API_ENDPOINTS.CLIENTE_LOGIN, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            cpf: cpf,
            senha: senha,
            usuario_id: idUsuarioLoja
        })
    });

    const data = await response.json();

    if (!response.ok) {
        if (response.status === 401) {
            throw new Error('Senha incorreta');
        }
        throw new Error(data.message || 'Erro ao fazer login');
    }

    clienteAtual = data.cliente;
    return data.cliente;
}

/**
 * Cadastra novo cliente
 */
export async function cadastrarCliente(dadosCliente) {
    // Validações
    if (dadosCliente.cpf && !validarCPF(dadosCliente.cpf)) {
        throw new Error('CPF inválido');
    }

    if (!dadosCliente.nome_completo) {
        throw new Error('Nome completo é obrigatório');
    }

    if (!dadosCliente.telefone) {
        throw new Error('Telefone é obrigatório');
    }

    if (!dadosCliente.senha || dadosCliente.senha.length < 4) {
        throw new Error('Senha deve ter no mínimo 4 caracteres');
    }

    if (!dadosCliente.endereco_logradouro) {
        throw new Error('Logradouro é obrigatório');
    }

    if (!dadosCliente.endereco_numero) {
        throw new Error('Número do endereço é obrigatório');
    }

    if (!dadosCliente.endereco_bairro) {
        throw new Error('Bairro é obrigatório');
    }

    if (!dadosCliente.endereco_cidade) {
        throw new Error('Cidade é obrigatória');
    }

    const response = await fetch(API_ENDPOINTS.CLIENTE, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dadosCliente)
    });

    const data = await response.json();

    if (!response.ok) {
        if (response.status === 422) {
            let errorMsg = 'Erros de validação:\n';
            for (const field in data.errors) {
                errorMsg += `- ${data.errors[field].join(', ')}\n`;
            }
            throw new Error(errorMsg);
        }
        throw new Error(data.message || 'Erro ao cadastrar cliente');
    }

    clienteAtual = data;
    return data;
}

/**
 * Formata dados do cliente para exibição
 */
export function formatarDadosCliente(cliente) {
    if (!cliente) return '';

    return {
        nome: cliente.nome_completo,
        cpf: formatarCPF(cliente.cpf),
        endereco: `${cliente.endereco_logradouro}, ${cliente.endereco_numero}`,
        bairro: cliente.endereco_bairro,
        cidade: cliente.endereco_cidade,
        estado: cliente.endereco_estado || ''
    };
}