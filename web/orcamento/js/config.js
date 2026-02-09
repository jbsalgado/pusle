// config.js - VERSÃO VENDA DIRETA

const isProduction = window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1';

const getLojaId = () => {
    const pathname = window.location.pathname;
    const segments = pathname.split('/').filter(p => p);
    const lojaPath = segments[segments.length - 1];
    
    // ⚠️ ATENÇÃO: Ajuste conforme a loja ativa. Para "Top Construções":
    // usuario_id = 5e449fee-4486-4536-a64f-74aed38a6987
    const lojaMap = {
        'catalogo': 'a99a38a9-e368-4a47-a4bd-02ba3bacaa76',
        'alexbird': '5eb98116-77c2-4a01-bd60-50db21eaa206',
        'victor': '0b633731-25a1-4991-b1c4-c46acc6bce06',
        'venda-direta': '5e449fee-4486-4536-a64f-74aed38a6987', // Top Construções
        'orcamento': '5e449fee-4486-4536-a64f-74aed38a6987',
        'top-construcoes': '5e449fee-4486-4536-a64f-74aed38a6987',
    };
    
    return lojaMap[lojaPath] || lojaMap['venda-direta'];
};

/**
 * Detecta automaticamente o caminho base da API a partir da URL atual
 * Remove /orcamento ou /orcamento/index do pathname e garante /index.php no final
 */
const detectApiBaseUrl = () => {
    const pathname = window.location.pathname;
    
    // Remove /orcamento ou /orcamento/index do final do path
    let basePath = pathname.replace(/\/orcamento(\/index)?(\/)?$/, '');
    
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
    const origin = window.location.origin;
    
    // Remove /orcamento ou /orcamento/index do final do path
    let basePath = pathname.replace(/\/orcamento(\/index)?\/?$/, '');
    
    // Remove /index.php se existir
    basePath = basePath.replace(/\/index\.php.*$/, '');
    
    // Remove barra final se existir
    basePath = basePath.replace(/\/$/, '');
    
    // Garante que comece com /
    if (!basePath.startsWith('/')) {
        basePath = '/' + basePath;
    }
    
    // Se estiver vazio, retorna /
    basePath = basePath || '/';
    
    // Em produção, retorna caminho relativo (sem origin)
    // O navegador vai resolver automaticamente baseado na origem atual
    return basePath;
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
    CACHE_NAME: 'orcamento-cache-v1',
    SYNC_TAG: 'sync-novo-orcamento',
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
    PEDIDO: `${CONFIG.URL_API}/api/pedido`, // GET - listar pedidos
    PEDIDO_CREATE: `${CONFIG.URL_API}/api/pedido/create`, // POST - criar pedido
    
    // ✅ NOVOS ENDPOINTS
    USUARIO_CONFIG: `${CONFIG.URL_API}/api/usuario/config`,
    USUARIO_ME: `${CONFIG.URL_API}/api/usuario/me`, // Endpoint para dados do usuário logado
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
    // ✅ NOVO: Endpoint para confirmar recebimento (processa estoque, caixa, etc)
    PEDIDO_CONFIRMAR_RECEBIMENTO: `${CONFIG.URL_API}/api/pedido/confirmar-recebimento`,
};

export const STORAGE_KEYS = {
    CARRINHO: 'carrinho_orcamento',
    FILA_PEDIDOS: 'fila_orcamentos_pendentes',
    FORMAS_PAGAMENTO: 'formas_pagamento_orcamento' // Cache offline de formas de pagamento
};

// ✅ NOVA: Configuração de gateway (carregada dinamicamente)
export let GATEWAY_CONFIG = {
    habilitado: false,
    gateway: 'nenhum', // 'mercadopago' | 'asaas' | 'nenhum'
    mercadopago_public_key: null,
    asaas_sandbox: false,
    imprimir_automatico: false
};
window.GATEWAY_CONFIG = GATEWAY_CONFIG;

// ✅ Configuração da Chave PIX Estática
// DEPRECATED: Agora os dados PIX são carregados da API (tabela prest_configuracoes)
// Mantido apenas para compatibilidade, mas não deve ser usado
// Use carregarConfigPix() do módulo pix.js ao invés disso
export const PIX_CONFIG = {
    chave: null, // Carregado da API
    nome: null,  // Carregado da API
    cidade: null // Carregado da API
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
        GATEWAY_CONFIG.imprimir_automatico = config.imprimir_automatico || false;
        
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

