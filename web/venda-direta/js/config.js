// config.js - VERSÃO VENDA DIRETA

const isProduction = window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1';

const getLojaId = () => {
    const pathname = window.location.pathname;
    const segments = pathname.split('/').filter(p => p);
    const lojaPath = segments[segments.length - 1];
    
    const lojaMap = {
        'catalogo': 'a99a38a9-e368-4a47-a4bd-02ba3bacaa76',
        'alexbird': '5eb98116-77c2-4a01-bd60-50db21eaa206',
        'victor': '0b633731-25a1-4991-b1c4-c46acc6bce06',
        'venda-direta': 'a99a38a9-e368-4a47-a4bd-02ba3bacaa76', // Usa mesmo ID do catálogo por padrão
    };
    
    return lojaMap[lojaPath] || lojaMap['venda-direta'];
};

export const CONFIG = {
    URL_API: isProduction ? '/pulse/web/index.php' : '/pulse/basic/web/index.php',
    URL_BASE_WEB: isProduction ? '/pulse/web' : '/pulse/basic/web',
    CACHE_NAME: 'venda-direta-cache-v1',
    SYNC_TAG: 'sync-novo-pedido-venda-direta',
    ID_USUARIO_LOJA: getLojaId()
};

export const API_ENDPOINTS = {
    PRODUTO: `${CONFIG.URL_API}/api/produto`,
    CLIENTE: `${CONFIG.URL_API}/api/cliente`,
    CLIENTE_BUSCA_CPF: `${CONFIG.URL_API}/api/cliente/buscar-cpf`,
    COLABORADOR_BUSCA_CPF: `${CONFIG.URL_API}/api/colaborador/buscar-cpf`,
    PEDIDO: `${CONFIG.URL_API}/api/pedido`,
    
    // ✅ NOVOS ENDPOINTS
    USUARIO_CONFIG: `${CONFIG.URL_API}/api/usuario/config`,
    USUARIO_ME: `${CONFIG.URL_API}/api/usuario/me`, // Endpoint para dados do usuário logado
    
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
};

export const STORAGE_KEYS = {
    CARRINHO: 'carrinho_venda_direta',
    PEDIDO_PENDENTE: 'pedido_pendente_venda_direta'
};

// ✅ NOVA: Configuração de gateway (carregada dinamicamente)
export let GATEWAY_CONFIG = {
    habilitado: false,
    gateway: 'nenhum', // 'mercadopago' | 'asaas' | 'nenhum'
    mercadopago_public_key: null,
    asaas_sandbox: false
};

// ✅ Configuração da Chave PIX Estática
export const PIX_CONFIG = {
    chave: '81992888872', // Chave PIX (celular)
    nome: 'JOSE BARBOSA DOS SANTOS',
    cidade: 'CARUARU'
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

