-- ===================================================================
-- Script PostgreSQL: Adicionar comissao_config_id na tabela prest_comissoes
-- ===================================================================
-- Descrição: Adiciona coluna para referenciar qual configuração de 
--            comissão foi usada para calcular cada comissão registrada
-- ===================================================================
-- Data: 2024-12-12
-- ===================================================================

-- Verificar se a tabela prest_comissoes existe
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_comissoes'
    ) THEN
        RAISE EXCEPTION 'Tabela prest_comissoes não encontrada! Execute primeiro os scripts de criação das tabelas base.';
    END IF;
END $$;

-- Adicionar coluna comissao_config_id
-- Nota: PostgreSQL não suporta AFTER diretamente, então adicionamos ao final
ALTER TABLE prest_comissoes 
ADD COLUMN IF NOT EXISTS comissao_config_id VARCHAR(36) NULL;

-- Adicionar comentário na coluna
COMMENT ON COLUMN prest_comissoes.comissao_config_id IS 'Referência à configuração de comissão usada para calcular esta comissão';

-- Criar índice para melhor performance
CREATE INDEX IF NOT EXISTS idx_comissao_config_id 
    ON prest_comissoes(comissao_config_id) 
    WHERE comissao_config_id IS NOT NULL;

-- Adicionar foreign key (após criar a tabela prest_comissao_config)
-- Verificar se a tabela prest_comissao_config existe primeiro
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_comissao_config'
    ) THEN
        -- Verificar se a constraint já existe
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
    ELSE
        RAISE NOTICE 'Tabela prest_comissao_config não encontrada. Execute o script 001_create_prest_comissao_config.sql primeiro. A foreign key será criada após a criação da tabela.';
    END IF;
END $$;

-- ===================================================================
-- Fim do script
-- ===================================================================

