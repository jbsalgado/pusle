// storage.js - Gerenciamento de IndexedDB e cache

import { STORAGE_KEYS, CONFIG } from './config.js';

/**
 * Salva carrinho no IndexedDB
 */
export async function salvarCarrinho(carrinho) {
    try {
        await idbKeyval.set(STORAGE_KEYS.CARRINHO, carrinho);
        console.log('[Storage] Carrinho salvo');
        return true;
    } catch (err) {
        console.error('[Storage] Erro ao salvar carrinho:', err);
        return false;
    }
}

/**
 * Carrega carrinho do IndexedDB
 */
export async function carregarCarrinho() {
    try {
        const carrinho = await idbKeyval.get(STORAGE_KEYS.CARRINHO);
        return Array.isArray(carrinho) ? carrinho : [];
    } catch (err) {
        console.error('[Storage] Erro ao carregar carrinho:', err);
        return [];
    }
}

/**
 * Remove carrinho do IndexedDB
 */
export async function limparCarrinho() {
    try {
        await idbKeyval.del(STORAGE_KEYS.CARRINHO);
        console.log('[Storage] Carrinho removido');
        return true;
    } catch (err) {
        console.error('[Storage] Erro ao limpar carrinho:', err);
        return false;
    }
}

/**
 * Salva pedido pendente no IndexedDB
 */
export async function salvarPedidoPendente(pedido) {
    try {
        await idbKeyval.set(STORAGE_KEYS.PEDIDO_PENDENTE, pedido);
        console.log('[Storage] Pedido pendente salvo');
        return true;
    } catch (err) {
        console.error('[Storage] Erro ao salvar pedido:', err);
        return false;
    }
}

/**
 * Limpa cache de produtos
 */
export async function limparCacheProdutos() {
    try {
        if ('caches' in window) {
            const cache = await caches.open(CONFIG.CACHE_NAME);
            const API_PRODUTO_URL = `${CONFIG.URL_API}/api/produto`;
            await cache.delete(API_PRODUTO_URL);
            console.log('[Storage] Cache de produtos limpo');
            return true;
        }
        return false;
    } catch (err) {
        console.error('[Storage] Erro ao limpar cache:', err);
        return false;
    }
}

/**
 * Limpa todos os dados locais após sincronização
 */
export async function limparDadosLocaisPosSinc() {
    console.log('[Storage] Limpando dados locais após sincronização...');
    
    await limparCarrinho();
    await limparCacheProdutos();
    
    return true;
}