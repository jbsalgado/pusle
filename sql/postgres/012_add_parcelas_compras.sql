-- ============================================================================================================
-- Migration: Adiciona suporte a parcelamento em compras
-- ============================================================================================================
-- Descrição: Adiciona campos num_parcelas e intervalo_parcelas à tabela prest_compras
--            para suportar geração automática de contas a pagar parceladas
-- ============================================================================================================
-- Adiciona colunas para parcelamento
ALTER TABLE prest_compras
ADD COLUMN IF NOT EXISTS num_parcelas INTEGER DEFAULT 1,
    ADD COLUMN IF NOT EXISTS intervalo_parcelas INTEGER DEFAULT 30;
-- dias entre parcelas
-- Comentários
COMMENT ON COLUMN prest_compras.num_parcelas IS 'Número de parcelas para pagamento (1 = à vista)';
COMMENT ON COLUMN prest_compras.intervalo_parcelas IS 'Intervalo em dias entre as parcelas (padrão: 30 dias)';
-- Atualiza registros existentes
UPDATE prest_compras
SET num_parcelas = 1
WHERE num_parcelas IS NULL;
UPDATE prest_compras
SET intervalo_parcelas = 30
WHERE intervalo_parcelas IS NULL;