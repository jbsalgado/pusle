// utils.js - Funções utilitárias reutilizáveis

/**
 * Implementação mínima de idbKeyval para IndexedDB (Correção do SyntaxError)
 * Usa a API nativa IndexedDB para gerenciar o armazenamento de chave/valor.
 */
const DB_NAME = 'catalogo-db';
const STORE_NAME = 'keyval-store';

function openDb() {
    return new Promise((resolve, reject) => {
        if (!('indexedDB' in window)) {
            reject(new Error("IndexedDB not supported."));
            return;
        }

        const request = indexedDB.open(DB_NAME, 1);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                // Cria o Object Store para armazenar as chaves (carrinho, pedido pendente, etc.)
                db.createObjectStore(STORE_NAME);
            }
        };

        request.onsuccess = (event) => {
            resolve(event.target.result);
        };

        request.onerror = (event) => {
            reject(event.target.error);
        };
    });
}

export const idbKeyval = {
    async get(key) {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readonly');
            const store = tx.objectStore(STORE_NAME);
            const request = store.get(key);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    },
    async set(key, val) {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            const request = store.put(val, key);

            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    },
    async del(key) {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            const request = store.delete(key);

            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }
};
// Fim da implementação idbKeyval

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
    cpf = cpf.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
    return cpf;
}

/**
 * Aplica máscara de CPF no input
 */
export function maskCPF(input) {
    let value = input.value.replace(/[^\d]/g, '');
    value = value.slice(0, 11); // Limita a 11 dígitos
    if (value.length > 9) {
        value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
    } else if (value.length > 6) {
        value = value.replace(/^(\d{3})(\d{3})(\d{3})$/, '$1.$2.$3');
    } else if (value.length > 3) {
        value = value.replace(/^(\d{3})(\d{3})$/, '$1.$2');
    }
    input.value = value;
}

/**
 * Aplica máscara de telefone no input
 */
export function maskPhone(input) {
    let value = input.value.replace(/[^\d]/g, '');
    value = value.slice(0, 11); // Limita a 11 dígitos
    
    // 11 dígitos: (XX) 9XXXX-XXXX
    if (value.length === 11) {
        value = value.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
    // 10 dígitos: (XX) XXXX-XXXX
    } else if (value.length === 10) {
        value = value.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
    // 9 dígitos: (XX) XXXX-XXXX (máscara incompleta)
    } else if (value.length > 2) {
        value = value.replace(/^(\d{2})(\d+)/, '($1) $2');
    }

    input.value = value;
}

/**
 * Aplica máscara de CEP no input
 */
export function maskCEP(input) {
    if (!input) return;
    let value = input.value.replace(/[^\d]/g, '');
    value = value.slice(0, 8); // Limita a 8 dígitos
    if (value.length > 5) {
        value = value.replace(/^(\d{5})(\d{3})$/, '$1-$2');
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
            console.error(`Elemento crítico não encontrado: #${id}`);
            elementosFaltando.push(id);
        }
    }
    
    if (elementosFaltando.length > 0) {
        const mensagem = `A aplicação não pode iniciar. Elementos críticos faltando: ${elementosFaltando.join(', ')}.`;
        document.body.innerHTML = `<div style="padding: 20px; color: red; font-family: sans-serif;">${mensagem}</div>`;
        throw new Error(mensagem);
    }
}