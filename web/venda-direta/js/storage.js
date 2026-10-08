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
 * Salva carrinho no IndexedDB isolado por tenant
 * @param {Array} carrinho 
 * @param {string|null} tenantId 
 */
export async function salvarCarrinho(carrinho, tenantId = null) {
    try {
        const key = STORAGE_KEYS.getCarrinhoKey(tenantId);
        await idbKeyval.set(key, carrinho);
        console.log(`[Storage] Carrinho salvo para chave: ${key}`);
        return true;
    } catch (err) {
        console.error('[Storage] Erro ao salvar carrinho:', err);
        return false;
    }
}

/**
 * Carrega carrinho do IndexedDB isolado por tenant
 * @param {string|null} tenantId 
 */
export async function carregarCarrinho(tenantId = null) {
    try {
        const activeTenant = tenantId || CONFIG.ID_USUARIO_LOJA;
        const key = STORAGE_KEYS.getCarrinhoKey(activeTenant);
        const carrinho = await idbKeyval.get(key);

        // Limpeza de segurança: se existir chave legada não-isolada, purga para evitar vazamento
        try {
            const legacyCarrinho = await idbKeyval.get(STORAGE_KEYS.CARRINHO);
            if (legacyCarrinho) {
                console.warn('[Storage] 🧹 Purgando carrinho legado sem isolamento multi-tenant...');
                await idbKeyval.del(STORAGE_KEYS.CARRINHO);
            }
        } catch (_) {}

        if (Array.isArray(carrinho)) {
            // Filtro estrito: garante que nenhum produto de outro tenant seja aceito
            const itensValidos = carrinho.filter(item => {
                if (!item.tenant_id) return true; // Itens legados da própria loja
                return !activeTenant || item.tenant_id === activeTenant;
            });
            return itensValidos;
        }
        return [];
    } catch (err) {
        console.error('[Storage] Erro ao carregar carrinho:', err);
        return [];
    }
}

/**
 * Remove carrinho do IndexedDB isolado por tenant
 * @param {string|null} tenantId 
 */
export async function limparCarrinho(tenantId = null) {
    try {
        const key = STORAGE_KEYS.getCarrinhoKey(tenantId);
        await idbKeyval.del(key);
        // Também remove o legado se existir
        try { await idbKeyval.del(STORAGE_KEYS.CARRINHO); } catch (_) {}
        console.log(`[Storage] Carrinho removido da chave: ${key}`);
        return true;
    } catch (err) {
        console.error('[Storage] Erro ao limpar carrinho:', err);
        return false;
    }
}

/**
 * Salva pedido pendente no IndexedDB isolado por tenant
 * Se 'pedido' for null, remove a chave do tenant.
 * @param {Object|null} pedido 
 * @param {string|null} tenantId 
 */
export async function salvarPedidoPendente(pedido, tenantId = null) {
    try {
        const key = STORAGE_KEYS.getPedidoPendenteKey(tenantId);
        if (pedido === null) {
            await idbKeyval.del(key); 
            console.log(`[Storage] Todos os pedidos pendentes removidos para chave: ${key}`);
            return true;
        }
        
        // Carrega pedidos existentes ou cria novo array
        const pedidosExistentes = await idbKeyval.get(key) || [];
        const listaPedidos = Array.isArray(pedidosExistentes) ? pedidosExistentes : [pedidosExistentes];
        
        // Adiciona o novo pedido com um ID temporário local
        pedido.id_local = Date.now() + Math.random().toString(36).substr(2, 9);
        if (!pedido.usuario_id && CONFIG.ID_USUARIO_LOJA) {
            pedido.usuario_id = CONFIG.ID_USUARIO_LOJA;
        }
        listaPedidos.push(pedido);
        
        await idbKeyval.set(key, listaPedidos);
        console.log(`[Storage] Pedido pendente salvo (${listaPedidos.length} no total) para chave: ${key}`);
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
 * Salva formas de pagamento no IndexedDB para uso offline isolado por tenant
 * @param {Array} formas 
 * @param {string|null} tenantId 
 */
export async function salvarFormasPagamento(formas, tenantId = null) {
    try {
        const key = STORAGE_KEYS.getFormasPagamentoKey(tenantId);
        await idbKeyval.set(key, formas);
        console.log(`[Storage] Formas de pagamento salvas no cache para chave: ${key}`);
        return true;
    } catch (err) {
        console.error('[Storage] Erro ao salvar formas de pagamento:', err);
        return false;
    }
}

/**
 * Carrega formas de pagamento do IndexedDB (cache offline) isolado por tenant
 * @param {string|null} tenantId 
 */
export async function carregarFormasPagamentoCache(tenantId = null) {
    try {
        const key = STORAGE_KEYS.getFormasPagamentoKey(tenantId);
        const formas = await idbKeyval.get(key);
        return Array.isArray(formas) ? formas : [];
    } catch (err) {
        console.error('[Storage] Erro ao carregar formas de pagamento do cache:', err);
        return [];
    }
}

/**
 * Limpa todos os dados locais após sincronização
 * @param {string|null} tenantId 
 */
export async function limparDadosLocaisPosSinc(tenantId = null) {
    console.log('[Storage] Limpando dados locais pós-sincronização...');
    
    // 1. Limpa o pedido pendente do tenant
    await salvarPedidoPendente(null, tenantId);
    
    // 2. Limpa o cache de produtos
    await limparCacheProdutos();
    
    // 3. Remove o carrinho do IndexedDB do tenant
    await limparCarrinho(tenantId); 
    
    console.log('[Storage] Limpeza de dados locais concluída.');
}