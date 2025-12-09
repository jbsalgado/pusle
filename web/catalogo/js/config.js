// config.js - VERSÃO ATUALIZADA

const isProduction = window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1';

const getLojaId = () => {
    const pathname = window.location.pathname;
    const segments = pathname.split('/').filter(p => p);
    const lojaPath = segments[segments.length - 1];
    
    const lojaMap = {
        'catalogo': '5e449fee-4486-4536-a64f-74aed38a6987', // Top Construções
        'top-construcoes': '5e449fee-4486-4536-a64f-74aed38a6987', // Top Construções
        'alexbird': '5eb98116-77c2-4a01-bd60-50db21eaa206',
        'victor':'0b633731-25a1-4991-b1c4-c46acc6bce06',
    };
    
    return lojaMap[lojaPath] || lojaMap['catalogo'];
};

/**
 * Detecta automaticamente o caminho base da API a partir da URL atual
 * Remove /catalogo ou /catalogo/index do pathname e garante /index.php no final
 */
const detectApiBaseUrl = () => {
    const pathname = window.location.pathname;
    
    // Remove /catalogo ou /catalogo/index do final do path (se existir)
    let basePath = pathname.replace(/\/catalogo(\/index)?(\/)?$/, '');
    
    // Se o path contém index.php, extrai tudo até /index.php
    if (basePath.includes('/index.php')) {
        // Pega tudo até /index.php (incluindo)
        const match = basePath.match(/^(.+\/index\.php)/);
        if (match) {
            basePath = match[1];
        } else {
            // Se não encontrou padrão, tenta pegar o diretório que contém index.php
            const parts = basePath.split('/index.php');
            basePath = parts[0] + '/index.php';
        }
    } else {
        // Se não tem index.php no path, pode estar usando pretty URLs
        // Tenta encontrar o caminho base removendo o controller/action
        // Remove barra final se existir
        basePath = basePath.replace(/\/$/, '');
        
        // Se o path está vazio ou é apenas /, usa /index.php
        if (!basePath || basePath === '/') {
            basePath = '/index.php';
        } else {
            // Adiciona /index.php ao final do caminho base
            basePath = basePath + '/index.php';
        }
    }
    
    // Garante que comece com /
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
    
    // Remove /catalogo ou /catalogo/index do final do path (se existir)
    let basePath = pathname.replace(/\/catalogo(\/index)?\/?$/, '');
    
    // Remove /index.php se existir
    basePath = basePath.replace(/\/index\.php.*$/, '');
    
    // Remove barra final se existir
    basePath = basePath.replace(/\/$/, '');
    
    // Garante que comece com /
    if (!basePath.startsWith('/')) {
        basePath = '/' + basePath;
    }
    
    // Se estiver vazio, retorna /
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
    CACHE_NAME: 'catalogo-cache-v10',
    SYNC_TAG: 'sync-novo-pedido',
    ID_USUARIO_LOJA: getLojaId()
};

// Log da configuração final
console.log('[Config] ✅ Configuração final:', {
    URL_API: CONFIG.URL_API,
    URL_BASE_WEB: CONFIG.URL_BASE_WEB,
    ID_USUARIO_LOJA: CONFIG.ID_USUARIO_LOJA
});

export const API_ENDPOINTS = {
    PRODUTO: `${CONFIG.URL_API}/api/produto`,
    CLIENTE: `${CONFIG.URL_API}/api/cliente`,
    CLIENTE_BUSCA_CPF: `${CONFIG.URL_API}/api/cliente/buscar-cpf`,
    COLABORADOR_BUSCA_CPF: `${CONFIG.URL_API}/api/colaborador/buscar-cpf`,
    PEDIDO: `${CONFIG.URL_API}/api/pedido`,
    
    // ✅ NOVOS ENDPOINTS
    USUARIO_CONFIG: `${CONFIG.URL_API}/api/usuario/config`,
    USUARIO_DADOS_LOJA: `${CONFIG.URL_API}/api/usuario/dados-loja`, // Endpoint para dados da loja (comprovantes)
    
    // =======================================================
    // ✅ CORREÇÃO: ENDPOINTS ADICIONADOS DO BACKUP
    // =======================================================
    CLIENTE_LOGIN: `${CONFIG.URL_API}/api/cliente/login`,
    FORMA_PAGAMENTO: `${CONFIG.URL_API}/api/forma-pagamento`,
    CALCULO_PARCELA: `${CONFIG.URL_API}/api/calculo/calcular-parcelas`,
    // =======================================================
    
    // Mercado Pago
    MERCADOPAGO_CRIAR_PREFERENCIA: `${CONFIG.URL_API}/api/mercado-pago/criar-preferencia`,
    
    // Asaas
    ASAAS_CRIAR_COBRANCA: `${CONFIG.URL_API}/api/asaas/criar-cobranca`,
    ASAAS_GERAR_QR_PIX: `${CONFIG.URL_API}/api/asaas/gerar-qrcode-pix`,

    // ✅ NOVO ENDPOINT DE CONSULTA PARA POLLING (ADICIONADO)
    ASAAS_CONSULTAR_STATUS: `${CONFIG.URL_API}/api/asaas/consultar-status`,
    
    // ✅ NOVO: Endpoint para buscar parcelas de uma venda
    PEDIDO_PARCELAS: `${CONFIG.URL_API}/api/pedido/parcelas`,
    // ✅ NOVO: Endpoint para confirmar recebimento de venda
    PEDIDO_CONFIRMAR_RECEBIMENTO: `${CONFIG.URL_API}/api/pedido/confirmar-recebimento`,
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

// ✅ NOVA: Função para carregar config da loja
export async function carregarConfigLoja() {
    try {
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
        
        console.log('[Config] Gateway:', GATEWAY_CONFIG.gateway, 
                    GATEWAY_CONFIG.habilitado ? '✅ HABILITADO' : '❌ DESABILITADO');
        
        return GATEWAY_CONFIG;
        
    } catch (error) {
        console.error('[Config] Erro ao carregar:', error);
        return GATEWAY_CONFIG;
    }
}

export const ELEMENTOS_CRITICOS = [
    'catalogo-produtos',
    'btn-abrir-carrinho',
    'modal-carrinho',
    'modal-cliente-pedido'
];
