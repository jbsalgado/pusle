-- ============================================================================================================
-- Migration: 014_marketplace_multitenant_fix_and_product_dimensions.sql
-- Descrição: Ajustes de isolamento multi-tenant, suporte a multi-contas, variações e dimensões logísticas
-- ============================================================================================================

-- 1. Enriquecimento da tabela prest_produtos (Logística, Dimensões e Fisco)
ALTER TABLE prest_produtos
    ADD COLUMN IF NOT EXISTS peso_bruto NUMERIC(10,3) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS peso_liquido NUMERIC(10,3) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS altura_cm NUMERIC(10,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS largura_cm NUMERIC(10,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS comprimento_cm NUMERIC(10,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS ncm VARCHAR(10),
    ADD COLUMN IF NOT EXISTS cest VARCHAR(10),
    ADD COLUMN IF NOT EXISTS ean_gtin VARCHAR(20),
    ADD COLUMN IF NOT EXISTS origem_mercadoria CHAR(1) DEFAULT '0',
    ADD COLUMN IF NOT EXISTS permite_estoque_negativo BOOLEAN DEFAULT FALSE;

COMMENT ON COLUMN prest_produtos.peso_bruto IS 'Peso bruto em kg com embalagem para cálculo de frete';
COMMENT ON COLUMN prest_produtos.peso_liquido IS 'Peso líquido em kg do produto';
COMMENT ON COLUMN prest_produtos.altura_cm IS 'Altura da embalagem em centímetros';
COMMENT ON COLUMN prest_produtos.largura_cm IS 'Largura da embalagem em centímetros';
COMMENT ON COLUMN prest_produtos.comprimento_cm IS 'Comprimento da embalagem em centímetros';
COMMENT ON COLUMN prest_produtos.ncm IS 'Nomenclatura Comum do Mercosul para NF-e';
COMMENT ON COLUMN prest_produtos.cest IS 'Código Especificador da Substituição Tributária';
COMMENT ON COLUMN prest_produtos.ean_gtin IS 'Código de barras global (EAN/GTIN/UPC)';
COMMENT ON COLUMN prest_produtos.origem_mercadoria IS '0-Nacional, 1-Estrangeira Direta, 2-Estrangeira Adquirida mercado interno, etc';
COMMENT ON COLUMN prest_produtos.permite_estoque_negativo IS 'Se permite vender mesmo com saldo zerado ou negativo';

-- 2. Ajustes na tabela prest_marketplace_config (Multi-tenant seguro e multi-contas)
ALTER TABLE prest_marketplace_config
    ADD COLUMN IF NOT EXISTS seller_id_externo VARCHAR(100),
    ADD COLUMN IF NOT EXISTS apelido_conta VARCHAR(100) DEFAULT 'Conta Principal',
    ADD COLUMN IF NOT EXISTS dados_adicionais JSONB DEFAULT '{}'::jsonb;

COMMENT ON COLUMN prest_marketplace_config.seller_id_externo IS 'ID externo do seller no marketplace (ex: user_id do ML, shop_id da Shopee)';
COMMENT ON COLUMN prest_marketplace_config.apelido_conta IS 'Nome amigável para identificar a conta caso o seller possua mais de uma';
COMMENT ON COLUMN prest_marketplace_config.dados_adicionais IS 'Metadados adicionais, escopos, credenciais auxiliares e webhooks';

CREATE INDEX IF NOT EXISTS idx_marketplace_config_seller_ext ON prest_marketplace_config(marketplace, seller_id_externo);

-- 3. Ajustes na tabela prest_marketplace_produto (Vínculo de tenant e variações)
ALTER TABLE prest_marketplace_produto
    ADD COLUMN IF NOT EXISTS usuario_id UUID REFERENCES prest_usuarios(id) ON DELETE CASCADE,
    ADD COLUMN IF NOT EXISTS marketplace_variacao_id VARCHAR(100);

-- Preencher usuario_id com base no produto_id para registros legados
UPDATE prest_marketplace_produto mp
SET usuario_id = p.usuario_id
FROM prest_produtos p
WHERE mp.produto_id = p.id AND mp.usuario_id IS NULL;

-- Atualizar índices e constraints de unicidade
ALTER TABLE prest_marketplace_produto DROP CONSTRAINT IF EXISTS prest_marketplace_produto_produto_id_marketplace_key;
ALTER TABLE prest_marketplace_produto DROP CONSTRAINT IF EXISTS prest_marketplace_produto_marketplace_marketplace_produto_id_key;

CREATE INDEX IF NOT EXISTS idx_marketplace_produto_usuario ON prest_marketplace_produto(usuario_id);
CREATE INDEX IF NOT EXISTS idx_marketplace_produto_variacao ON prest_marketplace_produto(marketplace_variacao_id);

-- 4. Tabela de Mapeamento de Categorias de Marketplaces
CREATE TABLE IF NOT EXISTS prest_marketplace_categoria_map (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    usuario_id UUID NOT NULL REFERENCES prest_usuarios(id) ON DELETE CASCADE,
    categoria_id UUID NOT NULL REFERENCES prest_categorias(id) ON DELETE CASCADE,
    marketplace VARCHAR(50) NOT NULL,
    marketplace_categoria_id VARCHAR(100) NOT NULL,
    marketplace_categoria_nome VARCHAR(255),
    regras_atributos JSONB DEFAULT '{}'::jsonb,
    data_criacao TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    data_atualizacao TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE(usuario_id, categoria_id, marketplace)
);

COMMENT ON TABLE prest_marketplace_categoria_map IS 'Mapeamento de categorias do ERP para a taxonomia de cada marketplace';
CREATE INDEX IF NOT EXISTS idx_marketplace_cat_map_usuario ON prest_marketplace_categoria_map(usuario_id);
CREATE INDEX IF NOT EXISTS idx_marketplace_cat_map_cat ON prest_marketplace_categoria_map(categoria_id);
