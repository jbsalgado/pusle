// config.js - Configurações centralizadas da aplicação

// Detecta automaticamente o ambiente
const isProduction = window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1';

// Função para pegar o ID da loja baseado no path
const getLojaId = () => {
    const pathname = window.location.pathname;
    const segments = pathname.split('/').filter(p => p); // Remove vazios
    
    // Pega o ÚLTIMO segmento do path (nome da loja)
    const lojaPath = segments[segments.length - 1];
    
    // Mapa de paths para IDs de loja
    const lojaMap = {
        'catalogo': 'a99a38a9-e368-4a47-a4bd-02ba3bacaa76', // Loja padrão
        'alexbird': '5eb98116-77c2-4a01-bd60-50db21eaa206', // Exemplo: Alex Bird
        'nike': '87654321-4321-4321-4321-210987654321',     // Exemplo: Nike
        // Adicione mais lojas aqui conforme necessário
    };
    
    const lojaId = lojaMap[lojaPath];
    
    if (!lojaId) {
        console.warn(`⚠️ Loja não encontrada para path: "${lojaPath}". Usando loja padrão.`);
        return lojaMap['catalogo']; // Fallback para loja padrão
    }
    
    console.log(`🏪 Loja detectada: ${lojaPath} (ID: ${lojaId})`);
    return lojaId;
};

export const CONFIG = {
    URL_API: isProduction ? '/pulse/web/index.php' : '/pulse/basic/web/index.php',
    URL_BASE_WEB: isProduction ? '/pulse/web' : '/pulse/basic/web',
    CACHE_NAME: 'catalogo-cache-v4',
    SYNC_TAG: 'sync-novo-pedido',
    ID_USUARIO_LOJA: getLojaId() // Agora detecta corretamente!
};

export const API_ENDPOINTS = {
    PRODUTO: `${CONFIG.URL_API}/api/produto`,
    CLIENTE: `${CONFIG.URL_API}/api/cliente`,
    CLIENTE_BUSCA_CPF: `${CONFIG.URL_API}/api/cliente/buscar-cpf`,
    CLIENTE_LOGIN: `${CONFIG.URL_API}/api/cliente/login`,
    COLABORADOR_BUSCA_CPF: `${CONFIG.URL_API}/api/colaborador/buscar-cpf`,
    FORMA_PAGAMENTO: `${CONFIG.URL_API}/api/forma-pagamento`,
    CALCULO_PARCELA: `${CONFIG.URL_API}/api/calculo/calcular-parcelas`,
    PEDIDO: `${CONFIG.URL_API}/api/pedido`
};

export const STORAGE_KEYS = {
    CARRINHO: 'carrinho_atual',
    PEDIDO_PENDENTE: 'pedido_pendente'
};

export const ELEMENTOS_CRITICOS = [
    'catalogo-produtos',
    'btn-abrir-carrinho',
    'modal-carrinho',
    'modal-cliente-pedido'
];