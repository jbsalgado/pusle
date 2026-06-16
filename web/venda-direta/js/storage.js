// storage.js - Gerenciamento de IndexedDB e cache

import { idbKeyval } from './utils.js'; // Assumindo que idbKeyval está sendo importado de utils
import { STORAGE_KEYS, CONFIG } from './config.js';

// Adiciona chave para Token JWT
const TOKEN_KEY = 'venda_direta_token_jwt';

/**
 * Salva o token JWT no IndexedDB
 */
export async function salvarToken(token) {
    if (!token) return;
    try {
        await idbKeyval.set(TOKEN_KEY, token);
        console.log('[Storage] 🔑 Token JWT salvo');
    } catch (err) {
        console.error('[Storage] Erro ao salvar token:', err);
    }
}

/**
 * Obtém o token JWT do IndexedDB
 */
export async function getToken() {
    try {
        return await idbKeyval.get(TOKEN_KEY);
    } catch (err) {
        console.error('[Storage] Erro ao obter token:', err);
        return null;
    }
}

/**
 * Remove o token JWT
 */
export async function removerToken() {
    try {
        await idbKeyval.del(TOKEN_KEY);
        console.log('[Storage] 🔑 Token JWT removido');
    } catch (err) {
        console.error('[Storage] Erro ao remover token:', err);
    }
}

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
        // Garante que retorne um array vazio se a chave não existir
        return Array.isArray(carrinho) ? carrinho : []; 
    } catch (err) {
        console.error('[Storage] Erro ao carregar carrinho:', err);
        return [];
    }
}

/**
 * Remove carrinho do IndexedDB (usando del)
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
 * Se 'pedido' for null, remove a chave.
 */
export async function salvarPedidoPendente(pedido) {
    try {
        if (pedido === null) {
            await idbKeyval.del(STORAGE_KEYS.PEDIDO_PENDENTE); 
            console.log('[Storage] Todos os pedidos pendentes removidos');
            return true;
        }
        
        // Carrega pedidos existentes ou cria novo array
        const pedidosExistentes = await idbKeyval.get(STORAGE_KEYS.PEDIDO_PENDENTE) || [];
        const listaPedidos = Array.isArray(pedidosExistentes) ? pedidosExistentes : [pedidosExistentes];
        
        // Adiciona o novo pedido com um ID temporário local
        pedido.id_local = Date.now() + Math.random().toString(36).substr(2, 9);
        listaPedidos.push(pedido);
        
        await idbKeyval.set(STORAGE_KEYS.PEDIDO_PENDENTE, listaPedidos);
        console.log(`[Storage] Pedido pendente salvo (${listaPedidos.length} no total)`);
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
 * Salva formas de pagamento no IndexedDB para uso offline
 */
export async function salvarFormasPagamento(formas) {
    try {
        await idbKeyval.set(STORAGE_KEYS.FORMAS_PAGAMENTO, formas);
        console.log('[Storage] Formas de pagamento salvas no cache');
        return true;
    } catch (err) {
        console.error('[Storage] Erro ao salvar formas de pagamento:', err);
        return false;
    }
}

/**
 * Carrega formas de pagamento do IndexedDB (cache offline)
 */
export async function carregarFormasPagamentoCache() {
    try {
        const formas = await idbKeyval.get(STORAGE_KEYS.FORMAS_PAGAMENTO);
        return Array.isArray(formas) ? formas : [];
    } catch (err) {
        console.error('[Storage] Erro ao carregar formas de pagamento do cache:', err);
        return [];
    }
}

/**
 * Limpa todos os dados locais após sincronização
 */
export async function limparDadosLocaisPosSinc() {
    console.log('[Storage] Limpando dados locais pós-sincronização...');
    
    // 1. Limpa o pedido pendente
    await salvarPedidoPendente(null);
    
    // 2. Limpa o cache de produtos
    await limparCacheProdutos();
    
    // 3. Remove o carrinho do IndexedDB
    await limparCarrinho(); 
    
    console.log('[Storage] Limpeza de dados locais concluída.');
}