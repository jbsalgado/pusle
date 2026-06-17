-- ============================================================================================================
-- Migration: Integração com Marketplaces
-- ============================================================================================================
-- Descrição: Cria tabelas para gerenciar integrações com marketplaces (Mercado Livre, Shopee, Magazine Luiza, Amazon)
-- Data: 2026-02-11
-- Autor: Sistema Pulse
-- ============================================================================================================
-- Tabela de configurações de marketplaces
CREATE TABLE IF NOT EXISTS prest_marketplace_config (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    usuario_id UUID NOT NULL REFERENCES prest_usuarios(id) ON DELETE CASCADE,
    marketplace VARCHAR(50) NOT NULL,
    -- 'MERCADO_LIVRE', 'SHOPEE', 'MAGAZINE_LUIZA', 'AMAZON'
    ativo BOOLEAN DEFAULT FALSE,
    -- Credenciais (criptografadas em produção)
    client_id VARCHAR(255),
    client_secret VARCHAR(255),
    access_token TEXT,
    refresh_token TEXT,
    token_expira_em TIMESTAMP,
    -- Configurações de sincronização
    sincronizar_produtos BOOLEAN DEFAULT TRUE,
    sincronizar_estoque BOOLEAN DEFAULT TRUE,
    sincronizar_pedidos BOOLEAN DEFAULT TRUE,
    intervalo_sync_minutos INTEGER DEFAULT 15,
    -- Metadados
    ultima_sync TIMESTAMP,
    data_criacao TIMESTAMP DEFAULT NOW(),
    data_atualizacao TIMESTAMP DEFAULT NOW(),
    UNIQUE(usuario_id, marketplace)
);
COMMENT ON TABLE prest_marketplace_config IS 'Configurações e credenciais de integração com marketplaces';
COMMENT ON COLUMN prest_marketplace_config.marketplace IS 'Nome do marketplace: MERCADO_LIVRE, SHOPEE, MAGAZINE_LUIZA, AMAZON';
COMMENT ON COLUMN prest_marketplace_config.ativo IS 'Se a integração está ativa para este marketplace';
COMMENT ON COLUMN prest_marketplace_config.intervalo_sync_minutos IS 'Intervalo em minutos entre sincronizações automáticas';
-- Tabela de vínculo produto ↔ marketplace
CREATE TABLE IF NOT EXISTS prest_marketplace_produto (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    produto_id UUID NOT NULL REFERENCES prest_produtos(id) ON DELETE CASCADE,
    marketplace VARCHAR(50) NOT NULL,
    marketplace_produto_id VARCHAR(255) NOT NULL,
    -- ID do produto no marketplace
    -- Dados específicos do marketplace
    titulo_marketplace VARCHAR(255),
    descricao_marketplace TEXT,
    preco_marketplace NUMERIC(10, 2),
    estoque_marketplace INTEGER,
    sku_marketplace VARCHAR(100),
    url_marketplace TEXT,
    categoria_marketplace VARCHAR(255),
    -- Status
    status VARCHAR(20) DEFAULT 'ATIVO',
    -- ATIVO, PAUSADO, ERRO, REMOVIDO
    ultima_sync TIMESTAMP,
    erro_sync TEXT,
    -- Metadados
    dados_completos JSONB,
    -- Dados brutos do marketplace
    data_criacao TIMESTAMP DEFAULT NOW(),
    data_atualizacao TIMESTAMP DEFAULT NOW(),
    UNIQUE(produto_id, marketplace),
    UNIQUE(marketplace, marketplace_produto_id)
);
COMMENT ON TABLE prest_marketplace_produto IS 'Vínculo entre produtos locais e produtos nos marketplaces';
COMMENT ON COLUMN prest_marketplace_produto.marketplace_produto_id IS 'ID do produto no marketplace (ex: MLB123456789)';
COMMENT ON COLUMN prest_marketplace_produto.status IS 'Status do produto: ATIVO, PAUSADO, ERRO, REMOVIDO';
-- Tabela de pedidos de marketplaces
CREATE TABLE IF NOT EXISTS prest_marketplace_pedido (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    usuario_id UUID NOT NULL REFERENCES prest_usuarios(id) ON DELETE CASCADE,
    marketplace VARCHAR(50) NOT NULL,
    marketplace_pedido_id VARCHAR(255) NOT NULL,
    -- Dados do cliente
    cliente_nome VARCHAR(255),
    cliente_email VARCHAR(255),
    cliente_telefone VARCHAR(50),
    cliente_documento VARCHAR(20),
    -- Endereço de entrega
    endereco_completo TEXT,
    endereco_cep VARCHAR(10),
    endereco_cidade VARCHAR(100),
    endereco_estado VARCHAR(2),
    -- Valores
    valor_total NUMERIC(10, 2) NOT NULL,
    valor_frete NUMERIC(10, 2) DEFAULT 0,
    valor_desconto NUMERIC(10, 2) DEFAULT 0,
    valor_produtos NUMERIC(10, 2) NOT NULL,
    -- Status
    status VARCHAR(50),
    -- Varia por marketplace
    status_pagamento VARCHAR(50),
    status_envio VARCHAR(50),
    -- Rastreamento
    codigo_rastreio VARCHAR(100),
    transportadora VARCHAR(100),
    data_envio TIMESTAMP,
    data_entrega_prevista TIMESTAMP,
    -- Integração com sistema local
    venda_id UUID REFERENCES prest_vendas(id),
    importado BOOLEAN DEFAULT FALSE,
    erro_importacao TEXT,
    -- Dados brutos (JSON completo do marketplace)
    dados_completos JSONB,
    -- Datas
    data_pedido TIMESTAMP NOT NULL,
    data_importacao TIMESTAMP DEFAULT NOW(),
    data_atualizacao TIMESTAMP DEFAULT NOW(),
    UNIQUE(marketplace, marketplace_pedido_id)
);
COMMENT ON TABLE prest_marketplace_pedido IS 'Pedidos importados dos marketplaces';
COMMENT ON COLUMN prest_marketplace_pedido.marketplace_pedido_id IS 'ID do pedido no marketplace';
COMMENT ON COLUMN prest_marketplace_pedido.importado IS 'Se o pedido já foi convertido em venda no sistema local';
COMMENT ON COLUMN prest_marketplace_pedido.venda_id IS 'ID da venda criada no sistema local a partir deste pedido';
-- Tabela de itens do pedido
CREATE TABLE IF NOT EXISTS prest_marketplace_pedido_item (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pedido_id UUID NOT NULL REFERENCES prest_marketplace_pedido(id) ON DELETE CASCADE,
    marketplace_produto_id VARCHAR(255),
    produto_id UUID REFERENCES prest_produtos(id),
    -- Dados do item
    titulo VARCHAR(255) NOT NULL,
    quantidade INTEGER NOT NULL,
    preco_unitario NUMERIC(10, 2) NOT NULL,
    preco_total NUMERIC(10, 2) NOT NULL,
    sku VARCHAR(100),
    variacao VARCHAR(255),
    -- Ex: Cor: Azul, Tamanho: M
    -- Metadados
    dados_completos JSONB,
    data_criacao TIMESTAMP DEFAULT NOW()
);
COMMENT ON TABLE prest_marketplace_pedido_item IS 'Itens dos pedidos importados dos marketplaces';
COMMENT ON COLUMN prest_marketplace_pedido_item.produto_id IS 'ID do produto local vinculado (se encontrado)';
COMMENT ON COLUMN prest_marketplace_pedido_item.marketplace_produto_id IS 'ID do produto no marketplace';
-- Tabela de logs de sincronização
CREATE TABLE IF NOT EXISTS prest_marketplace_sync_log (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    usuario_id UUID NOT NULL REFERENCES prest_usuarios(id) ON DELETE CASCADE,
    marketplace VARCHAR(50) NOT NULL,
    tipo_sync VARCHAR(50) NOT NULL,
    -- 'PRODUTOS', 'ESTOQUE', 'PEDIDOS', 'WEBHOOK'
    -- Resultado
    status VARCHAR(20) NOT NULL,
    -- 'SUCESSO', 'ERRO', 'PARCIAL'
    itens_processados INTEGER DEFAULT 0,
    itens_sucesso INTEGER DEFAULT 0,
    itens_erro INTEGER DEFAULT 0,
    -- Detalhes
    mensagem TEXT,
    detalhes JSONB,
    -- Performance
    tempo_execucao_ms INTEGER,
    data_inicio TIMESTAMP DEFAULT NOW(),
    data_fim TIMESTAMP
);
COMMENT ON TABLE prest_marketplace_sync_log IS 'Logs de sincronização com marketplaces';
COMMENT ON COLUMN prest_marketplace_sync_log.tipo_sync IS 'Tipo de sincronização: PRODUTOS, ESTOQUE, PEDIDOS, WEBHOOK';
COMMENT ON COLUMN prest_marketplace_sync_log.status IS 'Resultado: SUCESSO, ERRO, PARCIAL';
-- ============================================================================================================
-- ÍNDICES
-- ============================================================================================================
-- Índices para marketplace_config
CREATE INDEX IF NOT EXISTS idx_marketplace_config_usuario ON prest_marketplace_config(usuario_id);
CREATE INDEX IF NOT EXISTS idx_marketplace_config_marketplace ON prest_marketplace_config(marketplace);
CREATE INDEX IF NOT EXISTS idx_marketplace_config_ativo ON prest_marketplace_config(ativo);
-- Índices para marketplace_produto
CREATE INDEX IF NOT EXISTS idx_marketplace_produto_produto ON prest_marketplace_produto(produto_id);
CREATE INDEX IF NOT EXISTS idx_marketplace_produto_marketplace ON prest_marketplace_produto(marketplace);
CREATE INDEX IF NOT EXISTS idx_marketplace_produto_status ON prest_marketplace_produto(status);
CREATE INDEX IF NOT EXISTS idx_marketplace_produto_marketplace_id ON prest_marketplace_produto(marketplace_produto_id);
-- Índices para marketplace_pedido
CREATE INDEX IF NOT EXISTS idx_marketplace_pedido_usuario ON prest_marketplace_pedido(usuario_id);
CREATE INDEX IF NOT EXISTS idx_marketplace_pedido_marketplace ON prest_marketplace_pedido(marketplace);
CREATE INDEX IF NOT EXISTS idx_marketplace_pedido_status ON prest_marketplace_pedido(status);
CREATE INDEX IF NOT EXISTS idx_marketplace_pedido_importado ON prest_marketplace_pedido(importado);
CREATE INDEX IF NOT EXISTS idx_marketplace_pedido_data ON prest_marketplace_pedido(data_pedido);
CREATE INDEX IF NOT EXISTS idx_marketplace_pedido_venda ON prest_marketplace_pedido(venda_id);
-- Índices para marketplace_pedido_item
CREATE INDEX IF NOT EXISTS idx_marketplace_pedido_item_pedido ON prest_marketplace_pedido_item(pedido_id);
CREATE INDEX IF NOT EXISTS idx_marketplace_pedido_item_produto ON prest_marketplace_pedido_item(produto_id);
-- Índices para marketplace_sync_log
CREATE INDEX IF NOT EXISTS idx_marketplace_sync_log_usuario ON prest_marketplace_sync_log(usuario_id);
CREATE INDEX IF NOT EXISTS idx_marketplace_sync_log_marketplace ON prest_marketplace_sync_log(marketplace);
CREATE INDEX IF NOT EXISTS idx_marketplace_sync_log_tipo ON prest_marketplace_sync_log(tipo_sync);
CREATE INDEX IF NOT EXISTS idx_marketplace_sync_log_status ON prest_marketplace_sync_log(status);
CREATE INDEX IF NOT EXISTS idx_marketplace_sync_log_data ON prest_marketplace_sync_log(data_inicio);
-- ============================================================================================================
-- FIM DA MIGRATION
-- ============================================================================================================