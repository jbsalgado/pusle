// config.js - VERSÃO DINÂMICA (Multi-Tenant)

const isProduction = window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1';

/**
 * Detecta o slug/id da loja a partir de:
 * 1. Query param ?loja=... ou ?slug=... ou ?id=...
 * 2. Subdomínio (ex: alexbird.seudominio.com)
 * 3. Segmento de pathname antes de /catalogo/
 * Retorna null quando não detectado (será resolvido via API com fallback).
 */
const getLojaSlugOrId = () => {
    // 1. Query string
    const params = new URLSearchParams(window.location.search);
    const qLoja = params.get('loja') || params.get('slug') || params.get('id');
    if (qLoja) return qLoja;

    // 2. Subdomínio (ignora 'www', 'localhost', IPs)
    const hostname = window.location.hostname;
    const hostParts = hostname.split('.');
    if (hostParts.length > 2 && !['www', 'localhost', '127'].includes(hostParts[0])) {
        return hostParts[0];
    }

    // 3. Pathname: segmento imediatamente ANTES de /catalogo/
    const pathname = window.location.pathname;
    const segments = pathname.split('/').filter(p => p);
    const catIndex = segments.indexOf('catalogo');
    if (catIndex > 0) {
        const candidate = segments[catIndex - 1].split('.')[0];
        // Ignora segmentos genéricos do servidor (web, pulse, basic)
        if (!['web', 'pulse', 'basic', 'index.php'].includes(candidate)) {
            return candidate;
        }
    }

    return null; // Sem slug; o backend usará o fallback (primeira loja)
};

/**
 * Detecta automaticamente o caminho base da API a partir da URL atual
 */
const detectApiBaseUrl = () => {
    const pathname = window.location.pathname;
    
    // O base path é tudo o que vem ANTES de '/catalogo'
    const parts = pathname.split('/catalogo');
    let basePath = parts[0];
    
    // Se o path contém index.php dentro do basePath (ex: /pulse/web/index.php/catalogo)
    if (basePath.includes('/index.php')) {
        const match = basePath.match(/^(.+\/index\.php)/);
        if (match) return match[1];
    }
    
    // Remove barra final se existir
    basePath = basePath.replace(/\/$/, '');
    
    // Adiciona /index.php ao final
    return (basePath || '') + '/index.php';
};

/**
 * Detecta automaticamente o caminho base do web (sem index.php)
 */
const detectWebBaseUrl = () => {
    const pathname = window.location.pathname;
    
    // O base path é tudo o que vem ANTES de '/catalogo'
    const parts = pathname.split('/catalogo');
    let basePath = parts[0];
    
    // Remove /index.php se existir no base path
    basePath = basePath.replace(/\/index\.php.*$/, '');
    
    // Remove barra final se existir
    basePath = basePath.replace(/\/$/, '');
    
    // Garante que comece com /
    if (!basePath.startsWith('/')) {
        basePath = '/' + basePath;
    }
    
    return basePath || '/';
};

// Detecta automaticamente os caminhos base
const detectedApiUrl = detectApiBaseUrl();
const detectedWebUrl = detectWebBaseUrl();

// Fallback para desenvolvimento local
const fallbackApiUrl = isProduction ? '/pulse/web/index.php' : '/pulse/basic/web/index.php';
const fallbackWebUrl = isProduction ? '/pulse/web' : '/pulse/basic/web';

// Log para debug (pode ser removido em produção)
console.log('[Config] 🔍 Detecção automática de URLs:', {
    pathname: window.location.pathname,
    detectedApiUrl,
    detectedWebUrl,
    fallbackApiUrl,
    fallbackWebUrl
});

export const CONFIG = {
    URL_API: detectedApiUrl || fallbackApiUrl,
    URL_BASE_WEB: detectedWebUrl || fallbackWebUrl,
    CACHE_NAME: 'catalogo-cache-v17',
    SYNC_TAG: 'sync-novo-pedido',
    ID_USUARIO_LOJA: null, // Será preenchido dinamicamente em carregarConfigLoja()
    _slugDetectado: getLojaSlugOrId()
};

// Log da configuração inicial (sem ID ainda)
console.log('[Config] ℹ️ Config inicial (ID será resolvido via API):', {
    URL_API: CONFIG.URL_API,
    URL_BASE_WEB: CONFIG.URL_BASE_WEB,
    _slugDetectado: CONFIG._slugDetectado
});

// Adiciona o endpoint dinâmico de resolução de slug
const _urlApiBase = CONFIG.URL_API;

export const API_ENDPOINTS = {
    PRODUTO: `${_urlApiBase}/api/produto`,
    CLIENTE: `${_urlApiBase}/api/cliente`,
    CLIENTE_BUSCA_CPF: `${_urlApiBase}/api/cliente/buscar-cpf`,
    COLABORADOR_BUSCA_CPF: `${_urlApiBase}/api/colaborador/buscar-cpf`,
    PEDIDO: `${_urlApiBase}/api/pedido`, // GET - listar pedidos
    PEDIDO_CREATE: `${_urlApiBase}/api/pedido/create`, // POST - criar pedido
    
    // ✅ ENDPOINTS DE USUÁRIO
    USUARIO_CONFIG: `${_urlApiBase}/api/usuario/config`,
    USUARIO_CONFIG_BY_SLUG: `${_urlApiBase}/api/usuario/config-by-slug`, // ✅ NOVO endpoint dinâmico
    USUARIO_DADOS_LOJA: `${_urlApiBase}/api/usuario/dados-loja`,
    
    // =======================================================
    // ✅ CORREÇÃO: ENDPOINTS ADICIONADOS DO BACKUP
    // =======================================================
    CLIENTE_LOGIN: `${_urlApiBase}/api/cliente/login`,
    FORMA_PAGAMENTO: `${_urlApiBase}/api/forma-pagamento`,
    CALCULO_PARCELA: `${_urlApiBase}/api/calculo/calcular-parcelas`,
    // =======================================================
    
    // Mercado Pago
    MERCADOPAGO_CRIAR_PREFERENCIA: `${_urlApiBase}/api/mercado-pago/criar-preferencia`,
    MERCADOPAGO_CRIAR_PIX_SPLIT: `${_urlApiBase}/api/mercado-pago/criar-pagamento-pix-split`,
    
    // Asaas
    ASAAS_CRIAR_COBRANCA: `${_urlApiBase}/api/asaas/criar-cobranca`,
    ASAAS_GERAR_QR_PIX: `${_urlApiBase}/api/asaas/gerar-qrcode-pix`,

    // ✅ NOVO ENDPOINT DE CONSULTA PARA POLLING (ADICIONADO)
    ASAAS_CONSULTAR_STATUS: `${_urlApiBase}/api/asaas/consultar-status`,

    // ✅ NOVO: Endpoint genérico para consulta de status de pedido/venda
    PEDIDO_STATUS: `${_urlApiBase}/api/pedido/status`,
    
    // ✅ NOVO: Endpoint para buscar parcelas de uma venda
    PEDIDO_PARCELAS: `${_urlApiBase}/api/pedido/parcelas`,
    // ✅ NOVO: Endpoint para confirmar recebimento de venda
    PEDIDO_CONFIRMAR_RECEBIMENTO: `${_urlApiBase}/api/pedido/confirmar-recebimento`,
};

export const STORAGE_KEYS = {
    CARRINHO: 'carrinho_pwa',
    PEDIDO_PENDENTE: 'pedido_pendente_pwa'
};

// ✅ NOVA: Configuração de gateway (carregada dinamicamente)
export let GATEWAY_CONFIG = {
    habilitado: false,
    gateway: 'nenhum', // 'mercadopago' | 'asaas' | 'nenhum'
    mercadopago_public_key: null,
    asaas_sandbox: false
};

// ✅ NOVA: Função para carregar config da loja (com resolução dinâmica de slug)
export async function carregarConfigLoja() {
    try {
        // ✅ ETAPA 1: Se não há slug detectado → sinaliza para o app redirecionar à vitrine
        if (!CONFIG.ID_USUARIO_LOJA && !CONFIG._slugDetectado) {
            console.warn('[Config] ⚠️ Nenhum slug de loja detectado. Redirecionando para vitrine.');
            return { lojaIdentificada: false };
        }

        // ✅ ETAPA 2: Resolver o ID da loja a partir do slug detectado
        if (!CONFIG.ID_USUARIO_LOJA) {
            const slug = CONFIG._slugDetectado;
            const slugResp = await fetch(
                `${API_ENDPOINTS.USUARIO_CONFIG_BY_SLUG}?slug=${encodeURIComponent(slug)}`
            );

            if (slugResp.status === 400 || slugResp.status === 404) {
                console.warn('[Config] ⚠️ Slug inválido ou loja não encontrada:', slug);
                return { lojaIdentificada: false };
            }

            if (slugResp.ok) {
                const lojaInfo = await slugResp.json();
                CONFIG.ID_USUARIO_LOJA = lojaInfo.id;
                console.log('[Config] ✅ ID da loja resolvido:', CONFIG.ID_USUARIO_LOJA, '| Loja:', lojaInfo.nome);
            } else {
                console.error('[Config] ❌ Falha ao resolver slug:', slugResp.status);
                return { lojaIdentificada: false };
            }
        }

        // ✅ ETAPA 3: Carregar configurações de gateway usando o ID já resolvido
        if (!CONFIG.ID_USUARIO_LOJA) {
            console.error('[Config] ❌ ID da loja não pôde ser determinado.');
            return { lojaIdentificada: false };
        }

        // Disponibiliza no window para outros módulos
        window.CONFIG = CONFIG;

        const response = await fetch(
            `${API_ENDPOINTS.USUARIO_CONFIG}?usuario_id=${CONFIG.ID_USUARIO_LOJA}`
        );

        if (!response.ok) {
            throw new Error('Erro ao carregar configuração');
        }

        const config = await response.json();

        GATEWAY_CONFIG.habilitado = config.api_de_pagamento || false;
        GATEWAY_CONFIG.gateway = config.gateway_pagamento || 'nenhum';
        GATEWAY_CONFIG.mercadopago_public_key = config.mercadopago_public_key;
        GATEWAY_CONFIG.asaas_sandbox = config.asaas_sandbox || false;

        console.log('[Config] ℹ️ Gateway:', GATEWAY_CONFIG.gateway,
                    GATEWAY_CONFIG.habilitado ? '✅ HABILITADO' : '❌ DESABILITADO');
        console.log('[Config] ✅ Loja ativa: ID=', CONFIG.ID_USUARIO_LOJA);

        // Sinaliza loja identificada com sucesso
        return { ...GATEWAY_CONFIG, lojaIdentificada: true };

    } catch (error) {
        console.error('[Config] Erro ao carregar:', error);
        return { ...GATEWAY_CONFIG, lojaIdentificada: false };
    }
}


export const ELEMENTOS_CRITICOS = [
    'catalogo-produtos',
    'btn-abrir-carrinho',
    'modal-carrinho',
    'modal-cliente-pedido'
];
