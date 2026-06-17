// auth.js - Módulo de autenticação e gerenciamento de usuário
import { CONFIG, API_ENDPOINTS } from './config.js';
import { getToken, salvarToken, removerToken } from './storage.js';

const STORAGE_KEY_USER = 'venda_direta_user_data';
const STORAGE_KEY_COLABORADOR = 'venda_direta_colaborador_data';

/**
 * Verifica se o usuário está autenticado e busca seus dados
 */
export async function verificarAutenticacao() {
    try {
        // ✅ SSO Bridge: Verifica se há token na URL (login direto do backend)
        const urlParams = new URLSearchParams(window.location.search);
        const tokenUrl = urlParams.get('token');

        if (tokenUrl) {
            console.log('[Auth] 🔗 Token encontrado na URL. Realizando login via Bridge...');
            await salvarToken(tokenUrl);
            
            // Limpa a URL para não expor o token
            const novaUrl = window.location.pathname;
            window.history.replaceState({}, document.title, novaUrl);
            
            // Continua para buscar da API (que vai validar o token)
        } else {
            // Fluxo normal: tenta buscar dados do localStorage
            const dadosSalvos = localStorage.getItem(STORAGE_KEY_USER);
            const tokenSalvo = await getToken();

            if (dadosSalvos && tokenSalvo) {
                try {
                    const dados = JSON.parse(dadosSalvos);
                    console.log('[Auth] ✅ Dados do usuário e Token encontrados');
                    return dados;
                } catch (e) {
                    console.warn('[Auth] ⚠️ Erro ao parsear dados salvos:', e);
                    localStorage.removeItem(STORAGE_KEY_USER);
                }
            } else if (dadosSalvos && !tokenSalvo) {
                console.warn('[Auth] ⚠️ Dados encontrados mas TOKEN ausente. Forçando re-autenticação.');
                localStorage.removeItem(STORAGE_KEY_USER);
                localStorage.removeItem(STORAGE_KEY_COLABORADOR);
                // Continua para buscar da API (que vai falhar e pedir login)
            }
        }

        // Se não tem dados salvos, busca da API
        console.log('[Auth] 🔍 Buscando dados do usuário da API...');
        
        const token = await getToken();
        const headers = {
            'Accept': 'application/json',
        };
        
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        const response = await fetch(`${CONFIG.URL_API}/api/usuario/me`, {
            method: 'GET',
            headers: headers
        });

        if (response.status === 401 || response.status === 403) {
            // Usuário não autenticado
            console.warn('[Auth] ❌ Usuário não autenticado (status:', response.status, ')');
            
            // Se tinha token, ele é inválido
            if (token) await removerToken();
            
            // Redireciona para login com redirect_url para voltar ao PWA com token
            const redirectUrl = encodeURIComponent(window.location.href);
            window.location.href = `${CONFIG.URL_API}/auth/login?redirect_url=${redirectUrl}`;
            return null;
        }

        if (!response.ok) {
            const errorText = await response.text();
            console.error('[Auth] ❌ Erro na resposta:', response.status, errorText);
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
export async function limparDadosUsuario() {
    localStorage.removeItem(STORAGE_KEY_USER);
    localStorage.removeItem(STORAGE_KEY_COLABORADOR);
    await removerToken();
    window.location.reload();
}

/**
 * Realiza login na API para obter JWT
 */
export async function login(username, password) {
    try {
        const response = await fetch(`${CONFIG.URL_API}/api/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ username, password })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || data.erro || 'Erro ao realizar login');
        }

        if (data.success && data.data && data.data.token) {
            // Novo formato padrão BaseController
            await salvarToken(data.data.token);
            localStorage.setItem(STORAGE_KEY_USER, JSON.stringify(data.data.usuario));
            if (data.data.colaborador) {
                localStorage.setItem(STORAGE_KEY_COLABORADOR, JSON.stringify(data.data.colaborador));
            }
            return data.data.usuario;
        } else if (data.token) {
             // Formato direto (fallback)
            await salvarToken(data.token);
            localStorage.setItem(STORAGE_KEY_USER, JSON.stringify(data.usuario));
            if (data.colaborador) {
                localStorage.setItem(STORAGE_KEY_COLABORADOR, JSON.stringify(data.colaborador));
            }
            return data.usuario;
        } else {
             throw new Error('Token não recebido');
        }
        
    } catch (error) {
        console.error('[Auth] Erro no login:', error);
        throw error;
    }
}

