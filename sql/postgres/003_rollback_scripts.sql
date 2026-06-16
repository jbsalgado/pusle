-- ===================================================================
-- Script PostgreSQL: Rollback (Reverter alterações)
-- ===================================================================
-- Descrição: Scripts para reverter as alterações feitas pelos scripts
--            de criação e modificação das tabelas de comissões
-- ===================================================================
-- Data: 2024-12-12
-- ===================================================================

-- ===================================================================
-- Rollback 002: Remover comissao_config_id de prest_comissoes
-- ===================================================================

-- Remover foreign key se existir
ALTER TABLE prest_comissoes 
DROP CONSTRAINT IF EXISTS fk_comissao_config_id;

-- Remover índice se existir
DROP INDEX IF EXISTS idx_comissao_config_id;

-- Remover coluna se existir
ALTER TABLE prest_comissoes 
DROP COLUMN IF EXISTS comissao_config_id;

-- ===================================================================
-- Rollback 001: Remover tabela prest_comissao_config
-- ===================================================================

-- Remover trigger se existir
DROP TRIGGER IF EXISTS trg_update_prest_comissao_config_timestamp ON prest_comissao_config;

-- Remover função do trigger se existir
DROP FUNCTION IF EXISTS update_prest_comissao_config_timestamp();

-- Remover índices se existirem
DROP INDEX IF EXISTS idx_comissao_config_vigencia;
DROP INDEX IF EXISTS idx_comissao_config_busca;
DROP INDEX IF EXISTS idx_comissao_config_categoria;
DROP INDEX IF EXISTS idx_comissao_config_usuario;
DROP INDEX IF EXISTS idx_comissao_config_colaborador;

-- Remover tabela se existir
DROP TABLE IF EXISTS prest_comissao_config CASCADE;

-- ===================================================================
-- Fim do script de rollback
-- ===================================================================

