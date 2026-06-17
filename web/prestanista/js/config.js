// config.js - Configuração do Módulo Prestanista

const isProduction = window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1';

/**
 * Detecta automaticamente o caminho base da API a partir da URL atual
 * Remove /prestanista ou /prestanista/index do pathname e garante /index.php no final
 */
const detectApiBaseUrl = () => {
    const pathname = window.location.pathname;
    
    // Localiza a pasta /prestanista para extrair o caminho base correto
    const idx = pathname.indexOf('/prestanista');
    let basePath = idx !== -1 ? pathname.substring(0, idx) : pathname;
    
    // Se o path contém index.php, extrai tudo até /index.php
    if (basePath.includes('/index.php')) {
        const match = basePath.match(/^(.+\/index\.php)/);
        if (match) {
            basePath = match[1];
        } else {
            const parts = basePath.split('/index.php');
            basePath = parts[0] + '/index.php';
        }
    } else {
        basePath = basePath.replace(/\/$/, '');
        
        if (!basePath || basePath === '/') {
            basePath = '/index.php';
        } else {
            basePath = basePath + '/index.php';
        }
    }
    
    if (!basePath.startsWith('/')) {
        basePath = '/' + basePath;
    }
    
    return basePath;
};

/**
 * Detecta automaticamente o caminho base do web (sem index.php)
 */
const detectWebBaseUrl = () => {
    const pathname = window.location.pathname;
    
    // Localiza a pasta /prestanista para extrair o caminho base correto
    const idx = pathname.indexOf('/prestanista');
    let basePath = idx !== -1 ? pathname.substring(0, idx) : pathname;
    
    basePath = basePath.replace(/\/index\.php.*$/, '');
    basePath = basePath.replace(/\/$/, '');
    
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
    CACHE_NAME: 'prestanista-cache-v1',
    SYNC_TAG: 'sync-cobranca-prestanista',
    DB_NAME: 'prestanista_db',
    DB_VERSION: 1
};

// Log da configuração final
console.log('[Config] ✅ Configuração final:', {
    URL_API: CONFIG.URL_API,
    URL_BASE_WEB: CONFIG.URL_BASE_WEB
});

export const API_ENDPOINTS = {
    // Autenticação
    COLABORADOR_BUSCA_CPF: `${CONFIG.URL_API}/api/colaborador/buscar-cpf`,
    COLABORADOR_LOGIN: `${CONFIG.URL_API}/api/colaborador/login`, // A ser criado
    
    // Rotas e Cobrança
    ROTA_COBRANCA: `${CONFIG.URL_API}/api/rota-cobranca`,
    ROTA_COBRANCA_DIA: `${CONFIG.URL_API}/api/rota-cobranca/dia`, // Rota do dia para o cobrador
    PARCELAS_CLIENTE: `${CONFIG.URL_API}/api/parcelas/cliente`, // Parcelas de um cliente
    REGISTRAR_PAGAMENTO: `${CONFIG.URL_API}/api/cobranca/registrar-pagamento`, // Registrar pagamento offline
    REGISTRAR_VENDA: `${CONFIG.URL_API}/api/cobranca/registrar-venda`, // Registrar nova venda offline
    PRODUTO_BUSCA: `${CONFIG.URL_API}/api/produto-api/buscar`, // Buscar produtos
    
    // Formas de Pagamento (apenas DINHEIRO e PIX)
    FORMA_PAGAMENTO: `${CONFIG.URL_API}/api/forma-pagamento`,
    
    // Dados da Loja
    USUARIO_DADOS_LOJA: `${CONFIG.URL_API}/api/usuario/dados-loja`,
};

export const STORAGE_KEYS = {
    COBRADOR: 'prestanista_cobrador',
    ROTA_DIA: 'prestanista_rota_dia',
    PAGAMENTOS_PENDENTES: 'prestanista_pagamentos_pendentes',
    ULTIMA_SINCRONIZACAO: 'prestanista_ultima_sincronizacao',
    CLIENTES_CACHE: 'prestanista_clientes_cache',
    PARCELAS_CACHE: 'prestanista_parcelas_cache',
    VENDAS_PENDENTES: 'prestanista_vendas_pendentes'
};

// Status de Parcelas
export const STATUS_PARCELA = {
    PENDENTE: 'PENDENTE',
    PAGA: 'PAGA',
    ATRASADA: 'ATRASADA',
    CANCELADA: 'CANCELADA'
};

// Tipos de Ação de Cobrança
export const TIPO_ACAO = {
    VISITA: 'VISITA',
    PAGAMENTO: 'PAGAMENTO',
    AUSENTE: 'AUSENTE',
    RECUSA: 'RECUSA',
    NEGOCIACAO: 'NEGOCIACAO'
};

// Formas de Pagamento permitidas para cobradores
export const FORMAS_PAGAMENTO_COBRADOR = ['DINHEIRO', 'PIX', 'PIX_ESTATICO'];

