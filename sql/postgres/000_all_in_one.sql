-- ===================================================================
-- Script PostgreSQL: Sistema Completo de Configuração de Comissões
-- ===================================================================
-- Descrição: Script consolidado que executa todas as alterações
--            necessárias para o sistema de configuração de comissões
-- ===================================================================
-- Data: 2024-12-12
-- ===================================================================
-- ATENÇÃO: Execute este script apenas se ainda não executou os 
--          scripts individuais (001 e 002)
-- ===================================================================

BEGIN;

-- ===================================================================
-- PARTE 1: Criar tabela prest_comissao_config
-- ===================================================================

-- Criar tabela prest_comissao_config
CREATE TABLE IF NOT EXISTS prest_comissao_config (
    id UUID NOT NULL PRIMARY KEY,
    usuario_id UUID NOT NULL,
    colaborador_id UUID NOT NULL,
    tipo_comissao VARCHAR(20) NOT NULL,
    categoria_id UUID NULL,
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
    CONSTRAINT chk_data_fim CHECK (data_fim IS NULL OR data_inicio IS NULL OR data_fim >= data_inicio)
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

-- Criar índices
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

-- ===================================================================
-- PARTE 1.5: Converter tipos VARCHAR(36) para UUID se necessário
-- ===================================================================
-- Se a tabela já foi criada com VARCHAR(36), converter para UUID
-- para compatibilidade com as tabelas referenciadas

DO $$
BEGIN
    -- Verificar se a tabela existe e se as colunas são VARCHAR(36)
    IF EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_comissao_config'
    ) THEN
        -- Converter id se necessário
        IF EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = 'prest_comissao_config'
            AND column_name = 'id'
            AND data_type = 'character varying'
        ) THEN
            ALTER TABLE prest_comissao_config 
            ALTER COLUMN id TYPE UUID USING id::UUID;
        END IF;
        
        -- Converter usuario_id se necessário
        IF EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = 'prest_comissao_config'
            AND column_name = 'usuario_id'
            AND data_type = 'character varying'
        ) THEN
            ALTER TABLE prest_comissao_config 
            ALTER COLUMN usuario_id TYPE UUID USING usuario_id::UUID;
        END IF;
        
        -- Converter colaborador_id se necessário
        IF EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = 'prest_comissao_config'
            AND column_name = 'colaborador_id'
            AND data_type = 'character varying'
        ) THEN
            ALTER TABLE prest_comissao_config 
            ALTER COLUMN colaborador_id TYPE UUID USING colaborador_id::UUID;
        END IF;
        
        -- Converter categoria_id se necessário
        IF EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = 'prest_comissao_config'
            AND column_name = 'categoria_id'
            AND data_type = 'character varying'
        ) THEN
            ALTER TABLE prest_comissao_config 
            ALTER COLUMN categoria_id TYPE UUID USING categoria_id::UUID;
        END IF;
    END IF;
END $$;

-- ===================================================================
-- PARTE 2: Adicionar foreign keys (IGNORA ERROS SE JÁ EXISTIREM)
-- ===================================================================

-- Foreign key para usuario
DO $$
BEGIN
    -- Verificar se a tabela prest_usuarios existe
    IF EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_usuarios'
    ) THEN
        -- Verificar se a constraint já existe
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.table_constraints 
            WHERE constraint_schema = 'public' 
            AND constraint_name = 'fk_comissao_config_usuario'
            AND table_name = 'prest_comissao_config'
        ) THEN
            ALTER TABLE prest_comissao_config
            ADD CONSTRAINT fk_comissao_config_usuario 
                FOREIGN KEY (usuario_id) 
                REFERENCES prest_usuarios(id) 
                ON DELETE CASCADE 
                ON UPDATE CASCADE;
        END IF;
    ELSE
        RAISE NOTICE 'AVISO: Tabela prest_usuarios não encontrada. Execute após criar a tabela.';
    END IF;
END $$;

-- Foreign key para colaborador
DO $$
BEGIN
    -- Verificar se a tabela prest_colaboradores existe
    IF EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_colaboradores'
    ) THEN
        -- Verificar se a constraint já existe
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.table_constraints 
            WHERE constraint_schema = 'public' 
            AND constraint_name = 'fk_comissao_config_colaborador'
            AND table_name = 'prest_comissao_config'
        ) THEN
            ALTER TABLE prest_comissao_config
            ADD CONSTRAINT fk_comissao_config_colaborador 
                FOREIGN KEY (colaborador_id) 
                REFERENCES prest_colaboradores(id) 
                ON DELETE CASCADE 
                ON UPDATE CASCADE;
        END IF;
    ELSE
        RAISE NOTICE 'AVISO: Tabela prest_colaboradores não encontrada. Execute após criar a tabela.';
    END IF;
END $$;

-- Foreign key para categoria (pode ser NULL)
DO $$
BEGIN
    -- Verificar se a tabela prest_categorias existe
    IF EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_categorias'
    ) THEN
        -- Verificar se a constraint já existe
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.table_constraints 
            WHERE constraint_schema = 'public' 
            AND constraint_name = 'fk_comissao_config_categoria'
            AND table_name = 'prest_comissao_config'
        ) THEN
            ALTER TABLE prest_comissao_config
            ADD CONSTRAINT fk_comissao_config_categoria 
                FOREIGN KEY (categoria_id) 
                REFERENCES prest_categorias(id) 
                ON DELETE SET NULL 
                ON UPDATE CASCADE;
        END IF;
    ELSE
        RAISE NOTICE 'AVISO: Tabela prest_categorias não encontrada. Execute após criar a tabela.';
    END IF;
END $$;

-- Criar trigger para atualizar data_atualizacao automaticamente
CREATE OR REPLACE FUNCTION update_prest_comissao_config_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.data_atualizacao = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_update_prest_comissao_config_timestamp ON prest_comissao_config;
CREATE TRIGGER trg_update_prest_comissao_config_timestamp
    BEFORE UPDATE ON prest_comissao_config
    FOR EACH ROW
    EXECUTE FUNCTION update_prest_comissao_config_timestamp();

-- ===================================================================
-- PARTE 2: Adicionar comissao_config_id na tabela prest_comissoes
-- ===================================================================

-- Verificar se a tabela prest_comissoes existe
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_comissoes'
    ) THEN
        RAISE NOTICE 'Tabela prest_comissoes não encontrada. A coluna será criada quando a tabela existir.';
    ELSE
        -- Adicionar coluna comissao_config_id
        ALTER TABLE prest_comissoes 
        ADD COLUMN IF NOT EXISTS comissao_config_id UUID NULL;

        -- Converter tipo se a coluna já existir como VARCHAR(36)
        IF EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = 'prest_comissoes'
            AND column_name = 'comissao_config_id'
            AND data_type = 'character varying'
        ) THEN
            ALTER TABLE prest_comissoes 
            ALTER COLUMN comissao_config_id TYPE UUID USING comissao_config_id::UUID;
        END IF;

        -- Adicionar comentário na coluna
        COMMENT ON COLUMN prest_comissoes.comissao_config_id IS 'Referência à configuração de comissão usada para calcular esta comissão';

        -- Criar índice
        CREATE INDEX IF NOT EXISTS idx_comissao_config_id 
            ON prest_comissoes(comissao_config_id) 
            WHERE comissao_config_id IS NOT NULL;

        -- Adicionar foreign key (se a tabela prest_comissao_config existir)
        IF EXISTS (
            SELECT 1 FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'prest_comissao_config'
        ) THEN
            IF NOT EXISTS (
                SELECT 1 FROM information_schema.table_constraints 
                WHERE constraint_schema = 'public' 
                AND constraint_name = 'fk_comissao_config_id'
                AND table_name = 'prest_comissoes'
            ) THEN
                ALTER TABLE prest_comissoes
                ADD CONSTRAINT fk_comissao_config_id 
                    FOREIGN KEY (comissao_config_id) 
                    REFERENCES prest_comissao_config(id) 
                    ON DELETE SET NULL 
                    ON UPDATE CASCADE;
            END IF;
        END IF;
    END IF;
END $$;

COMMIT;

-- ===================================================================
-- Fim do script consolidado
-- ===================================================================
-- 
-- Para verificar se tudo foi criado corretamente, execute:
-- 
-- SELECT 
--     'prest_comissao_config' as tabela,
--     COUNT(*) as total_registros
-- FROM prest_comissao_config
-- UNION ALL
-- SELECT 
--     'prest_comissoes' as tabela,
--     COUNT(*) as total_registros
-- FROM prest_comissoes;
-- 
-- \d prest_comissao_config
-- \d prest_comissoes
-- ===================================================================

