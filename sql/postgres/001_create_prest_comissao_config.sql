-- ===================================================================
-- Script PostgreSQL: Criar tabela prest_comissao_config
-- ===================================================================
-- Descrição: Tabela para configurações flexíveis de comissões
--            Permite múltiplas configurações por colaborador com
--            diferentes percentuais por categoria ou todas as categorias
-- ===================================================================
-- Data: 2024-12-12
-- ===================================================================

-- Criar tabela prest_comissao_config
CREATE TABLE IF NOT EXISTS prest_comissao_config (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    usuario_id VARCHAR(36) NOT NULL,
    colaborador_id VARCHAR(36) NOT NULL,
    tipo_comissao VARCHAR(20) NOT NULL,
    categoria_id VARCHAR(36) NULL,
    percentual DECIMAL(5,2) NOT NULL DEFAULT 0,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    data_inicio DATE NULL,
    data_fim DATE NULL,
    observacoes TEXT NULL,
    data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Constraints
    CONSTRAINT chk_tipo_comissao CHECK (tipo_comissao IN ('VENDA', 'COBRANCA')),
    CONSTRAINT chk_percentual CHECK (percentual >= 0 AND percentual <= 100),
    CONSTRAINT chk_data_fim CHECK (data_fim IS NULL OR data_inicio IS NULL OR data_fim >= data_inicio),
    
    -- Foreign Keys
    CONSTRAINT fk_comissao_config_usuario 
        FOREIGN KEY (usuario_id) 
        REFERENCES prest_usuarios(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_comissao_config_colaborador 
        FOREIGN KEY (colaborador_id) 
        REFERENCES prest_colaboradores(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_comissao_config_categoria 
        FOREIGN KEY (categoria_id) 
        REFERENCES prest_categorias(id) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
);

-- Comentários nas colunas
COMMENT ON TABLE prest_comissao_config IS 'Configurações de comissões para colaboradores - permite múltiplas configurações por colaborador';
COMMENT ON COLUMN prest_comissao_config.id IS 'ID único da configuração (UUID)';
COMMENT ON COLUMN prest_comissao_config.usuario_id IS 'Usuário proprietário da configuração';
COMMENT ON COLUMN prest_comissao_config.colaborador_id IS 'Colaborador que receberá a comissão';
COMMENT ON COLUMN prest_comissao_config.tipo_comissao IS 'Tipo de comissão: VENDA ou COBRANCA';
COMMENT ON COLUMN prest_comissao_config.categoria_id IS 'NULL = todas as categorias, ou ID específico de uma categoria';
COMMENT ON COLUMN prest_comissao_config.percentual IS 'Percentual de comissão (0-100)';
COMMENT ON COLUMN prest_comissao_config.ativo IS 'Se a configuração está ativa';
COMMENT ON COLUMN prest_comissao_config.data_inicio IS 'Data de início da vigência (opcional)';
COMMENT ON COLUMN prest_comissao_config.data_fim IS 'Data de fim da vigência (opcional)';
COMMENT ON COLUMN prest_comissao_config.observacoes IS 'Observações sobre a configuração';

-- Criar índices para melhor performance
CREATE INDEX IF NOT EXISTS idx_comissao_config_colaborador 
    ON prest_comissao_config(colaborador_id);

CREATE INDEX IF NOT EXISTS idx_comissao_config_usuario 
    ON prest_comissao_config(usuario_id);

CREATE INDEX IF NOT EXISTS idx_comissao_config_categoria 
    ON prest_comissao_config(categoria_id);

CREATE INDEX IF NOT EXISTS idx_comissao_config_busca 
    ON prest_comissao_config(usuario_id, colaborador_id, tipo_comissao, categoria_id, ativo);

CREATE INDEX IF NOT EXISTS idx_comissao_config_vigencia 
    ON prest_comissao_config(data_inicio, data_fim) 
    WHERE data_inicio IS NOT NULL OR data_fim IS NOT NULL;

-- Criar trigger para atualizar data_atualizacao automaticamente
CREATE OR REPLACE FUNCTION update_prest_comissao_config_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.data_atualizacao = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_update_prest_comissao_config_timestamp
    BEFORE UPDATE ON prest_comissao_config
    FOR EACH ROW
    EXECUTE FUNCTION update_prest_comissao_config_timestamp();

-- ===================================================================
-- Fim do script
-- ===================================================================

