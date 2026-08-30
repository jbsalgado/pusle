-- ==============================================================================
-- MIGRATION 016: Regras de Precificação e Markup por Canal de Marketplace
-- Data: 2026-08-29
-- ==============================================================================

-- 1. ADICIONAR CAMPOS DE MARKUP EM PREST_MARKETPLACE_CONFIG
ALTER TABLE prest_marketplace_config 
ADD COLUMN IF NOT EXISTS markup_percentual NUMERIC(5, 2) NOT NULL DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS markup_valor_fixo NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS arredondar_centavos_99 BOOLEAN NOT NULL DEFAULT FALSE;

-- 2. GARANTIR CAMPOS ADICIONAIS EM PREST_MARKETPLACE_CATEGORIA_MAP
CREATE TABLE IF NOT EXISTS prest_marketplace_categoria_map (
    id SERIAL PRIMARY KEY,
    categoria_id UUID NOT NULL REFERENCES prest_categorias(id) ON DELETE CASCADE,
    marketplace VARCHAR(50) NOT NULL,
    marketplace_categoria_id VARCHAR(100) NOT NULL,
    marketplace_categoria_nome VARCHAR(255),
    atributos_obrigatorios JSONB DEFAULT '[]'::jsonb,
    atributos_valores JSONB DEFAULT '{}'::jsonb,
    usuario_id UUID NOT NULL REFERENCES prest_usuarios(id) ON DELETE CASCADE,
    data_criacao TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(usuario_id, marketplace, categoria_id)
);

CREATE INDEX IF NOT EXISTS idx_mktp_cat_map_user ON prest_marketplace_categoria_map(usuario_id, marketplace);
