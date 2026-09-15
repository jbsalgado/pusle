-- ============================================================
-- Migration 024: tornar cobrador_id nullable em prest_historico_cobranca
-- 
-- MOTIVO: cobrador_id era incorretamente preenchido com usuario_id
-- (prest_usuarios) quando nenhum cobrador de rua estava atribuído,
-- causando FK violation pois usuario_id não existe em prest_colaboradores.
-- Agora cobrador_id é NULL quando o pagamento é registrado diretamente
-- pelo dono da loja sem rota de cobrança ativa.
-- ============================================================

ALTER TABLE prest_historico_cobranca
    ALTER COLUMN cobrador_id DROP NOT NULL;

COMMENT ON COLUMN prest_historico_cobranca.cobrador_id
    IS 'Cobrador de rua responsável — NULL quando pagamento registrado diretamente pelo dono da loja sem rota de cobrança';
