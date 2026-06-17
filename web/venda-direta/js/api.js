/**
 * api.js - Módulo centralizado para chamadas de API com autenticação
 */
import { getToken, removerToken } from './storage.js';
import { CONFIG } from './config.js';

/**
 * Realiza um fetch incluindo automaticamente o token de autenticação
 * @param {string} url - URL da requisição
 * @param {Object} options - Opções do fetch (method, body, headers, etc)
 * @returns {Promise<Response>}
 */
export async function fetchWithAuth(url, options = {}) {
    const token = await getToken();
    
    // Prepara os headers mantendo os originais se existirem
    const headers = {
        'Accept': 'application/json',
        ...options.headers
    };
    
    // Adiciona o token se disponível
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }
    
    try {
        const response = await fetch(url, {
            ...options,
            headers: headers
        });
        
        // Trata erro de autenticação (401 ou 403)
        if (response.status === 401 || response.status === 403) {
            console.warn(`[API] ⚠️ Erro de autenticação detectado (${response.status}) na URL: ${url}`);
            
            // Dispara evento global para que a aplicação possa reagir (ex: redirecionar para login)
            window.dispatchEvent(new CustomEvent('auth:unauthorized', { 
                detail: { status: response.status, url: url } 
            }));
            
            // Opcional: Se for 401, o token é inválido/expirado
            if (response.status === 401) {
                // await removerToken(); // Descomentar se desejar limpar token imediatamente
            }
        }
        
        return response;
    } catch (error) {
        console.error(`[API] ❌ Erro na requisição para ${url}:`, error);
        throw error;
    }
}
