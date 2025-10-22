// config.js - Configurações centralizadas da aplicação

// Detecta automaticamente o ambiente
const isProduction = window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1';

export const CONFIG = {
    URL_API: isProduction ? '/pulse/web/index.php' : '/pulse/basic/web/index.php',
    URL_BASE_WEB: isProduction ? '/pulse/web' : '/pulse/basic/web',
    CACHE_NAME: 'catalogo-cache-v4',
    SYNC_TAG: 'sync-novo-pedido',
    ID_USUARIO_LOJA: 'a99a38a9-e368-4a47-a4bd-02ba3bacaa76' // ID da loja/usuário principal (CONFIRME ESTE VALOR)
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