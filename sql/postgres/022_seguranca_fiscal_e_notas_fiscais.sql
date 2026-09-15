-- Migration 022: Segurança Fiscal Multitenant e Tabela de Notas Fiscais
-- Sistema: Pulse ERP

BEGIN;

-- 1. Novos campos de controle fiscal e regime tributário na tabela prest_configuracoes
ALTER TABLE prest_configuracoes 
    ADD COLUMN IF NOT EXISTS nfe_serie INTEGER DEFAULT 1,
    ADD COLUMN IF NOT EXISTS nfe_numero_atual INTEGER DEFAULT 0,
    ADD COLUMN IF NOT EXISTS faturador_ml_tipo VARCHAR(30) DEFAULT 'PULSE_ERP',
    ADD COLUMN IF NOT EXISTS ibge_municipio VARCHAR(7),
    ADD COLUMN IF NOT EXISTS uf_sigla VARCHAR(2),
    ADD COLUMN IF NOT EXISTS cnae VARCHAR(10);

-- Comentários descritivos
COMMENT ON COLUMN prest_configuracoes.nfe_serie IS 'Série atual da NF-e Modelo 55';
COMMENT ON COLUMN prest_configuracoes.nfe_numero_atual IS 'Último número sequencial de NF-e emitido';
COMMENT ON COLUMN prest_configuracoes.faturador_ml_tipo IS 'Tipo de faturamento no ML: PULSE_ERP ou MERCADO_LIVRE';
COMMENT ON COLUMN prest_configuracoes.ibge_municipio IS 'Código IBGE de 7 dígitos do município da empresa';
COMMENT ON COLUMN prest_configuracoes.uf_sigla IS 'Sigla da UF da empresa (ex: SP, RJ, PE)';

-- 2. Tabela de Notas Fiscais Multitenant (NF-e 55 e NFC-e 65)
CREATE TABLE IF NOT EXISTS prest_notas_fiscais (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    usuario_id UUID NOT NULL REFERENCES prest_usuarios(id) ON DELETE CASCADE,
    venda_id UUID REFERENCES prest_vendas(id) ON DELETE SET NULL,
    marketplace_pedido_id VARCHAR(100),
    marketplace VARCHAR(50),
    modelo VARCHAR(2) NOT NULL DEFAULT '55', -- '55' = NF-e Mercantil, '65' = NFC-e Consumidor
    serie INTEGER NOT NULL DEFAULT 1,
    numero INTEGER NOT NULL,
    chave_acesso VARCHAR(44) UNIQUE,
    protocolo_autorizacao VARCHAR(100),
    status_sefaz VARCHAR(30) NOT NULL DEFAULT 'PENDENTE', -- PENDENTE, PROCESSANDO, AUTORIZADA, REJEITADA, CANCELADA
    cstat VARCHAR(10),
    xmotivo TEXT,
    xml_envio TEXT,
    xml_autorizado TEXT,
    pdf_danfe_path VARCHAR(500),
    ambiente SMALLINT NOT NULL DEFAULT 2, -- 1 = Produção, 2 = Homologação
    data_emissao TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    data_autorizacao TIMESTAMP WITH TIME ZONE,
    data_criacao TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    data_atualizacao TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Índices de performance e isolamento multitenant
CREATE INDEX IF NOT EXISTS idx_notas_fiscais_tenant ON prest_notas_fiscais(usuario_id);
CREATE INDEX IF NOT EXISTS idx_notas_fiscais_venda ON prest_notas_fiscais(venda_id);
CREATE INDEX IF NOT EXISTS idx_notas_fiscais_chave ON prest_notas_fiscais(chave_acesso);
CREATE INDEX IF NOT EXISTS idx_notas_fiscais_status ON prest_notas_fiscais(status_sefaz);
CREATE INDEX IF NOT EXISTS idx_notas_fiscais_mp_pedido ON prest_notas_fiscais(marketplace, marketplace_pedido_id);

COMMIT;
