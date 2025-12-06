// storage.js - Gerenciamento de IndexedDB para armazenamento offline

import { STORAGE_KEYS, CONFIG } from './config.js';

/**
 * Implementação de idbKeyval para IndexedDB
 * Similar ao padrão usado em venda-direta
 */
const DB_NAME = 'prestanista-db';
const STORE_NAME = 'keyval-store';

let dbPromise;

function openDb() {
    if (!dbPromise) {
        dbPromise = new Promise((resolve, reject) => {
            if (!('indexedDB' in window)) {
                reject(new Error("IndexedDB not supported."));
                return;
            }

            const request = indexedDB.open(DB_NAME, 1);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(STORE_NAME)) {
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
    return dbPromise;
}

const idbKeyval = {
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
    },
    async createStore(name, storeName) {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(name, 1);
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(storeName)) {
                    db.createObjectStore(storeName);
                }
            };
            request.onsuccess = () => {
                const db = request.result;
                resolve((mode, callback) => {
                    return Promise.resolve(callback(db.transaction(storeName, mode).objectStore(storeName)));
                });
            };
            request.onerror = () => reject(request.error);
        });
    }
};

/**
 * Inicializa o IndexedDB para o módulo Prestanista
 */
export async function initDB() {
    try {
        // Abre o banco de dados (já cria a store se não existir)
        await openDb();
        console.log('[Storage] ✅ IndexedDB inicializado');
        return true;
    } catch (error) {
        console.error('[Storage] ❌ Erro ao inicializar IndexedDB:', error);
        throw error;
    }
}

/**
 * Salva dados do cobrador logado
 */
export async function salvarCobrador(cobrador) {
    try {
        await idbKeyval.set(STORAGE_KEYS.COBRADOR, cobrador);
        console.log('[Storage] ✅ Cobrador salvo:', cobrador.nome_completo);
        return true;
    } catch (error) {
        console.error('[Storage] ❌ Erro ao salvar cobrador:', error);
        return false;
    }
}

/**
 * Carrega dados do cobrador
 */
export async function carregarCobrador() {
    try {
        const cobrador = await idbKeyval.get(STORAGE_KEYS.COBRADOR);
        return cobrador || null;
    } catch (error) {
        console.error('[Storage] ❌ Erro ao carregar cobrador:', error);
        return null;
    }
}

/**
 * Salva rota do dia
 */
export async function salvarRotaDia(rota) {
    try {
        await idbKeyval.set(STORAGE_KEYS.ROTA_DIA, rota);
        console.log('[Storage] ✅ Rota do dia salva:', rota.length, 'clientes');
        return true;
    } catch (error) {
        console.error('[Storage] ❌ Erro ao salvar rota:', error);
        return false;
    }
}

/**
 * Carrega rota do dia
 */
export async function carregarRotaDia() {
    try {
        const rota = await idbKeyval.get(STORAGE_KEYS.ROTA_DIA);
        return Array.isArray(rota) ? rota : [];
    } catch (error) {
        console.error('[Storage] ❌ Erro ao carregar rota:', error);
        return [];
    }
}

/**
 * Adiciona pagamento pendente de sincronização
 */
export async function adicionarPagamentoPendente(pagamento) {
    try {
        const pagamentos = await carregarPagamentosPendentes();
        pagamento.id_local = `local_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        pagamento.timestamp = new Date().toISOString();
        pagamentos.push(pagamento);
        await idbKeyval.set(STORAGE_KEYS.PAGAMENTOS_PENDENTES, pagamentos);
        console.log('[Storage] ✅ Pagamento pendente adicionado:', pagamento.id_local);
        return pagamento.id_local;
    } catch (error) {
        console.error('[Storage] ❌ Erro ao adicionar pagamento pendente:', error);
        return null;
    }
}

/**
 * Carrega pagamentos pendentes de sincronização
 */
export async function carregarPagamentosPendentes() {
    try {
        const pagamentos = await idbKeyval.get(STORAGE_KEYS.PAGAMENTOS_PENDENTES);
        return Array.isArray(pagamentos) ? pagamentos : [];
    } catch (error) {
        console.error('[Storage] ❌ Erro ao carregar pagamentos pendentes:', error);
        return [];
    }
}

/**
 * Remove pagamento pendente após sincronização bem-sucedida
 */
export async function removerPagamentoPendente(idLocal) {
    try {
        const pagamentos = await carregarPagamentosPendentes();
        const filtrados = pagamentos.filter(p => p.id_local !== idLocal);
        await idbKeyval.set(STORAGE_KEYS.PAGAMENTOS_PENDENTES, filtrados);
        console.log('[Storage] ✅ Pagamento pendente removido:', idLocal);
        return true;
    } catch (error) {
        console.error('[Storage] ❌ Erro ao remover pagamento pendente:', error);
        return false;
    }
}

/**
 * Salva cache de clientes
 */
export async function salvarClientesCache(clientes) {
    try {
        await idbKeyval.set(STORAGE_KEYS.CLIENTES_CACHE, clientes);
        return true;
    } catch (error) {
        console.error('[Storage] ❌ Erro ao salvar cache de clientes:', error);
        return false;
    }
}

/**
 * Carrega cache de clientes
 */
export async function carregarClientesCache() {
    try {
        const clientes = await idbKeyval.get(STORAGE_KEYS.CLIENTES_CACHE);
        return clientes || {};
    } catch (error) {
        console.error('[Storage] ❌ Erro ao carregar cache de clientes:', error);
        return {};
    }
}

/**
 * Salva cache de parcelas
 */
export async function salvarParcelasCache(parcelas) {
    try {
        await idbKeyval.set(STORAGE_KEYS.PARCELAS_CACHE, parcelas);
        return true;
    } catch (error) {
        console.error('[Storage] ❌ Erro ao salvar cache de parcelas:', error);
        return false;
    }
}

/**
 * Carrega cache de parcelas
 */
export async function carregarParcelasCache() {
    try {
        const parcelas = await idbKeyval.get(STORAGE_KEYS.PARCELAS_CACHE);
        return parcelas || {};
    } catch (error) {
        console.error('[Storage] ❌ Erro ao carregar cache de parcelas:', error);
        return {};
    }
}

/**
 * Atualiza última sincronização
 */
export async function atualizarUltimaSincronizacao() {
    try {
        await idbKeyval.set(STORAGE_KEYS.ULTIMA_SINCRONIZACAO, new Date().toISOString());
        return true;
    } catch (error) {
        console.error('[Storage] ❌ Erro ao atualizar última sincronização:', error);
        return false;
    }
}

/**
 * Obtém última sincronização
 */
export async function obterUltimaSincronizacao() {
    try {
        const timestamp = await idbKeyval.get(STORAGE_KEYS.ULTIMA_SINCRONIZACAO);
        return timestamp || null;
    } catch (error) {
        console.error('[Storage] ❌ Erro ao obter última sincronização:', error);
        return null;
    }
}

/**
 * Limpa todos os dados locais (logout)
 */
export async function limparDadosLocais() {
    try {
        await Promise.all([
            idbKeyval.del(STORAGE_KEYS.COBRADOR),
            idbKeyval.del(STORAGE_KEYS.ROTA_DIA),
            idbKeyval.del(STORAGE_KEYS.PAGAMENTOS_PENDENTES),
            idbKeyval.del(STORAGE_KEYS.CLIENTES_CACHE),
            idbKeyval.del(STORAGE_KEYS.PARCELAS_CACHE),
        ]);
        console.log('[Storage] ✅ Dados locais limpos');
        return true;
    } catch (error) {
        console.error('[Storage] ❌ Erro ao limpar dados locais:', error);
        return false;
    }
}

