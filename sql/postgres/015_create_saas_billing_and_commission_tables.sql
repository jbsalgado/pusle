-- ==============================================================================
-- MIGRATION 015: Estrutura de Monetização, Planos e Gestão Financeira do SaaS
-- Data: 2026-08-29
-- ==============================================================================

-- 1. TABELA DE PLANOS DE ASSINATURA DO SAAS
CREATE TABLE IF NOT EXISTS prest_saas_planos (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    valor_mensalidade NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
    percentual_comissao_catalogo NUMERIC(5, 2) NOT NULL DEFAULT 2.50,    -- % sobre vendas no Catálogo/PDV próprio
    percentual_comissao_marketplace NUMERIC(5, 2) NOT NULL DEFAULT 1.00, -- % sobre GMV importado de marketplaces
    limite_pedidos_inclusos INT NOT NULL DEFAULT 300,                     -- Franquia mensal de pedidos inclusos
    valor_pedido_excedente NUMERIC(10, 2) NOT NULL DEFAULT 0.50,          -- Valor cobrado por pedido acima da franquia
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    destaque BOOLEAN NOT NULL DEFAULT FALSE,
    data_criacao TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 2. TABELA DE CONFIGURAÇÃO DE COBRANÇA POR LOJA (TENANT)
CREATE TABLE IF NOT EXISTS prest_saas_loja_config (
    id SERIAL PRIMARY KEY,
    usuario_id UUID NOT NULL UNIQUE REFERENCES prest_usuarios(id) ON DELETE CASCADE,
    plano_id INT REFERENCES prest_saas_planos(id) ON DELETE SET NULL,
    dia_vencimento INT NOT NULL DEFAULT 10,
    percentual_custom_catalogo NUMERIC(5, 2),        -- Sobrescreve a taxa do plano se preenchido
    percentual_custom_marketplace NUMERIC(5, 2),     -- Sobrescreve a taxa do plano se preenchido
    valor_custom_mensalidade NUMERIC(10, 2),          -- Sobrescreve o valor do plano se preenchido
    status_cobranca VARCHAR(30) NOT NULL DEFAULT 'adimplente', -- adimplente, inadimplente, bloqueado, isento
    dias_carencia_bloqueio INT NOT NULL DEFAULT 5,
    observacoes TEXT,
    data_criacao TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_saas_loja_config_usuario ON prest_saas_loja_config(usuario_id);
CREATE INDEX IF NOT EXISTS idx_saas_loja_config_status ON prest_saas_loja_config(status_cobranca);

-- 3. TABELA DE FATURAS MENSAIS DO SAAS COBRADAS DOS LOJISTAS
CREATE TABLE IF NOT EXISTS prest_saas_faturas (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    usuario_id UUID NOT NULL REFERENCES prest_usuarios(id) ON DELETE CASCADE,
    mes_referencia VARCHAR(7) NOT NULL,              -- Formato: 'AAAA-MM' (ex: '2026-08')
    data_fechamento DATE NOT NULL,
    data_vencimento DATE NOT NULL,
    
    -- Métricas e GMV apurado no mês
    gmv_marketplace NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    gmv_catalogo NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    total_pedidos_marketplace INT NOT NULL DEFAULT 0,
    total_pedidos_catalogo INT NOT NULL DEFAULT 0,
    
    -- Valores calculados
    valor_mensalidade NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
    valor_comissao_marketplace NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
    valor_comissao_catalogo NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
    valor_pedidos_excedentes NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
    valor_descontos NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
    valor_total NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
    
    -- Status e Pagamento
    status VARCHAR(30) NOT NULL DEFAULT 'pendente',  -- pendente, paga, atrasada, cancelada
    data_pagamento TIMESTAMP WITHOUT TIME ZONE,
    metodo_pagamento VARCHAR(50),                     -- PIX, CARTAO, BOLETO, SALDO_SPLIT
    
    -- Integração Gateway para Recebimento do SaaS
    qr_code_pix TEXT,
    codigo_pix TEXT,
    link_pagamento TEXT,
    transacao_gateway_id VARCHAR(150),
    
    detalhes_json JSONB DEFAULT '{}'::jsonb,
    data_criacao TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_saas_faturas_usuario_mes ON prest_saas_faturas(usuario_id, mes_referencia);
CREATE INDEX IF NOT EXISTS idx_saas_faturas_status ON prest_saas_faturas(status);
CREATE INDEX IF NOT EXISTS idx_saas_faturas_vencimento ON prest_saas_faturas(data_vencimento);

-- 4. TABELA DE CONFIGURAÇÕES GLOBAIS DO SAAS (Master Credentials & Defaults)
CREATE TABLE IF NOT EXISTS prest_saas_config_global (
    id SERIAL PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    descricao VARCHAR(255),
    data_atualizacao TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- INSERÇÃO DE PLANOS PADRÃO
INSERT INTO prest_saas_planos (nome, descricao, valor_mensalidade, percentual_comissao_catalogo, percentual_comissao_marketplace, limite_pedidos_inclusos, valor_pedido_excedente, ativo, destaque)
VALUES 
    ('Plano Start', 'Ideal para quem está começando a vender online', 49.00, 2.50, 1.00, 100, 0.50, TRUE, FALSE),
    ('Plano Pro', 'Para lojistas em crescimento e alta rotatividade', 99.00, 2.00, 0.80, 500, 0.40, TRUE, TRUE),
    ('Plano Scale', 'Para grandes operações e alta demanda em marketplaces', 199.00, 1.50, 0.50, 2000, 0.25, TRUE, FALSE)
ON CONFLICT DO NOTHING;

-- INSERÇÃO DE CONFIGURAÇÕES GLOBAIS PADRÃO
INSERT INTO prest_saas_config_global (chave, valor, descricao)
VALUES 
    ('mercado_pago_master_access_token', '', 'Access Token Master da conta recebedora do SaaS no Mercado Pago'),
    ('mercado_pago_master_public_key', '', 'Public Key Master do Mercado Pago'),
    ('mercado_pago_master_sponsor_id', '', 'ID do Sponsor / Master User ID para Split de Pagamento'),
    ('asaas_master_api_key', '', 'Chave de API Master Asaas para emissão de Boletos/Pix do SaaS'),
    ('taxa_padrao_catalogo_split', '2.50', 'Percentual padrão de split retido pelo SaaS no catálogo online'),
    ('taxa_padrao_marketplace_gmv', '1.00', 'Percentual padrão de comissão sobre vendas de marketplaces'),
    ('dias_carencia_inadimplencia', '5', 'Dias após o vencimento antes do bloqueio automático')
ON CONFLICT (chave) DO NOTHING;
