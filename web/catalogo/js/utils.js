// utils.js - Funções utilitárias reutilizáveis

/**
 * Valida CPF segundo algoritmo oficial
 */
export function validarCPF(cpf) {
    if (!cpf) return false;
    
    cpf = String(cpf).replace(/[^\d]/g, '');

    if (cpf.length !== 11) return false;
    if (/^(\d)\1{10}$/.test(cpf)) return false;

    let soma = 0;
    let resto;

    for (let i = 1; i <= 9; i++) {
        soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
    }
    resto = (soma * 10) % 11;
    if (resto === 10 || resto === 11) resto = 0;
    if (resto !== parseInt(cpf.substring(9, 10))) return false;

    soma = 0;

    for (let i = 1; i <= 10; i++) {
        soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
    }
    resto = (soma * 10) % 11;
    if (resto === 10 || resto === 11) resto = 0;
    if (resto !== parseInt(cpf.substring(10, 11))) return false;

    return true;
}

/**
 * Formata CPF para exibição
 */
export function formatarCPF(cpf) {
    if (!cpf) return '';
    cpf = String(cpf).replace(/[^\d]/g, '');
    if (cpf.length !== 11) return cpf;
    return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

/**
 * Aplica máscara de CPF em input
 */
export function maskCPF(input) {
    let value = input.value.replace(/[^\d]/g, '');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    input.value = value;
}

/**
 * Aplica máscara de telefone em input
 */
export function maskPhone(input) {
    let value = input.value.replace(/[^\d]/g, '');
    value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
    if (value.length > 14) {
        value = value.replace(/(\d{5})(\d{4}).*/, '$1-$2');
    } else if (value.length > 10) {
        value = value.replace(/(\d{4})(\d{4}).*/, '$1-$2');
    }
    if (value.length === 14 && value.length > 13) {
        value = value.replace(/(\d{4})-(\d{4})/, '$1$2');
        value = value.replace(/(\d{5})(\d{4})/, '$1-$2');
    }
    input.value = value;
}

/**
 * Valida UUID
 */
export function validarUUID(uuid) {
    const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
    return uuidRegex.test(uuid);
}

/**
 * Formata valor monetário
 */
export function formatarMoeda(valor) {
    return `R$ ${parseFloat(valor).toFixed(2).replace('.', ',')}`;
}

/**
 * Verifica elementos críticos do DOM
 */
export function verificarElementosCriticos(elementosIds) {
    const elementosFaltando = [];
    
    for (const id of elementosIds) {
        if (!document.getElementById(id)) {
            console.error(`Elemento crítico não encontrado: ${id}`);
            elementosFaltando.push(id);
        }
    }
    
    return elementosFaltando.length === 0;
}

/**
 * Debounce para otimizar eventos
 */
export function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}