-- ===================================================================
-- Script PostgreSQL: Permitir cliente_id NULL em prest_vendas
-- ===================================================================
-- Descrição: Permite que vendas diretas sejam criadas sem cliente_id
--            Necessário para o módulo de venda direta (loja física)
-- ===================================================================
-- Data: 2024-12-XX
-- ===================================================================

-- 1. Remover a constraint NOT NULL da coluna cliente_id
ALTER TABLE public.prest_vendas 
    ALTER COLUMN cliente_id DROP NOT NULL;

-- 2. A foreign key constraint já permite NULL por padrão no PostgreSQL
--    Mas vamos garantir que está configurada corretamente
--    (A constraint existente já deve funcionar com NULL)

-- 3. Comentário na coluna para documentar
COMMENT ON COLUMN public.prest_vendas.cliente_id IS 
    'ID do cliente. NULL para vendas diretas (loja física sem cliente cadastrado).';

