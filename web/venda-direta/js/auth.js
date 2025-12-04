// auth.js - Módulo de autenticação e gerenciamento de usuário
import { CONFIG, API_ENDPOINTS } from './config.js';

const STORAGE_KEY_USER = 'venda_direta_user_data';
const STORAGE_KEY_COLABORADOR = 'venda_direta_colaborador_data';

/**
 * Verifica se o usuário está autenticado e busca seus dados
 */
export async function verificarAutenticacao() {
    try {
        // Primeiro, tenta buscar dados do localStorage
        const dadosSalvos = localStorage.getItem(STORAGE_KEY_USER);
        if (dadosSalvos) {
            try {
                const dados = JSON.parse(dadosSalvos);
                console.log('[Auth] ✅ Dados do usuário encontrados no localStorage');
                return dados;
            } catch (e) {
                console.warn('[Auth] ⚠️ Erro ao parsear dados salvos:', e);
                localStorage.removeItem(STORAGE_KEY_USER);
            }
        }

        // Se não tem dados salvos, busca da API
        console.log('[Auth] 🔍 Buscando dados do usuário da API...');
        const response = await fetch(`${CONFIG.URL_API}/api/usuario/me`, {
            method: 'GET',
            credentials: 'include', // Importante para enviar cookies de sessão
            headers: {
                'Accept': 'application/json',
            }
        });

        if (response.status === 401) {
            // Usuário não autenticado - redireciona para login
            console.warn('[Auth] ❌ Usuário não autenticado, redirecionando...');
            window.location.href = `${CONFIG.URL_API}/auth/login`;
            return null;
        }

        if (!response.ok) {
            throw new Error(`Erro ao buscar dados do usuário: ${response.status}`);
        }

        const dados = await response.json();
        
        if (dados.erro) {
            throw new Error(dados.erro);
        }

        // Salva no localStorage
        localStorage.setItem(STORAGE_KEY_USER, JSON.stringify(dados));
        
        // Se tem colaborador, salva separadamente também
        if (dados.colaborador) {
            localStorage.setItem(STORAGE_KEY_COLABORADOR, JSON.stringify(dados.colaborador));
        }

        console.log('[Auth] ✅ Dados do usuário carregados e salvos:', dados);
        return dados;

    } catch (error) {
        console.error('[Auth] ❌ Erro ao verificar autenticação:', error);
        
        // Se for erro de rede, tenta usar dados salvos
        const dadosSalvos = localStorage.getItem(STORAGE_KEY_USER);
        if (dadosSalvos) {
            console.log('[Auth] ⚠️ Usando dados salvos (offline)');
            return JSON.parse(dadosSalvos);
        }
        
        // Se não tem dados salvos e está offline, redireciona para login
        if (!navigator.onLine) {
            alert('Você precisa estar online e autenticado para usar o sistema.');
            window.location.href = `${CONFIG.URL_API}/auth/login`;
            return null;
        }
        
        throw error;
    }
}

/**
 * Retorna dados do colaborador (vendedor) se existir
 */
export function getColaboradorData() {
    try {
        const dados = localStorage.getItem(STORAGE_KEY_COLABORADOR);
        return dados ? JSON.parse(dados) : null;
    } catch (e) {
        console.error('[Auth] Erro ao buscar dados do colaborador:', e);
        return null;
    }
}

/**
 * Retorna dados do usuário
 */
export function getUserData() {
    try {
        const dados = localStorage.getItem(STORAGE_KEY_USER);
        return dados ? JSON.parse(dados) : null;
    } catch (e) {
        console.error('[Auth] Erro ao buscar dados do usuário:', e);
        return null;
    }
}

/**
 * Limpa dados do usuário (logout)
 */
export function limparDadosUsuario() {
    localStorage.removeItem(STORAGE_KEY_USER);
    localStorage.removeItem(STORAGE_KEY_COLABORADOR);
}

