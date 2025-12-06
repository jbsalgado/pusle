// config.js - Configuração do Módulo Prestanista

const isProduction = window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1';

export const CONFIG = {
    URL_API: isProduction ? '/pulse/web/index.php' : '/pulse/basic/web/index.php',
    URL_BASE_WEB: isProduction ? '/pulse/web' : '/pulse/basic/web',
    CACHE_NAME: 'prestanista-cache-v1',
    SYNC_TAG: 'sync-cobranca-prestanista',
    DB_NAME: 'prestanista_db',
    DB_VERSION: 1
};

export const API_ENDPOINTS = {
    // Autenticação
    COLABORADOR_BUSCA_CPF: `${CONFIG.URL_API}/api/colaborador/buscar-cpf`,
    COLABORADOR_LOGIN: `${CONFIG.URL_API}/api/colaborador/login`, // A ser criado
    
    // Rotas e Cobrança
    ROTA_COBRANCA: `${CONFIG.URL_API}/api/rota-cobranca`,
    ROTA_COBRANCA_DIA: `${CONFIG.URL_API}/api/rota-cobranca/dia`, // Rota do dia para o cobrador
    PARCELAS_CLIENTE: `${CONFIG.URL_API}/api/parcelas/cliente`, // Parcelas de um cliente
    REGISTRAR_PAGAMENTO: `${CONFIG.URL_API}/api/cobranca/registrar-pagamento`, // Registrar pagamento offline
    
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
    PARCELAS_CACHE: 'prestanista_parcelas_cache'
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
export const FORMAS_PAGAMENTO_COBRADOR = ['DINHEIRO', 'PIX'];

