-- ============================================================
-- Migration 023: Suporte a NF-e emitida por Sistema Externo
-- e rastreamento de envio ao Mercado Livre
-- ============================================================

-- 1. Novos campos em prest_configuracoes
ALTER TABLE prest_configuracoes
    ADD COLUMN IF NOT EXISTS nfe_webhook_token   VARCHAR(128),
    ADD COLUMN IF NOT EXISTS nfe_sistema_externo VARCHAR(100);

COMMENT ON COLUMN prest_configuracoes.nfe_webhook_token IS
    'Token seguro para autenticar callbacks de sistemas fiscais externos (ERP, contador, Omie, Bling...)';
COMMENT ON COLUMN prest_configuracoes.nfe_sistema_externo IS
    'Nome do sistema externo de emissão fiscal (ex: Omie, Bling, NFe.io)';
COMMENT ON COLUMN prest_configuracoes.faturador_ml_tipo IS
    'PULSE_ERP = Emissão interna via NFePHP | MERCADO_LIVRE = Faturador nativo ML | SISTEMA_EXTERNO = ERP/contador externo';

-- 2. Novos campos em prest_notas_fiscais
ALTER TABLE prest_notas_fiscais
    ADD COLUMN IF NOT EXISTS fonte_emissao  VARCHAR(30) NOT NULL DEFAULT 'PULSE_ERP',
    ADD COLUMN IF NOT EXISTS enviada_ml     BOOLEAN NOT NULL DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS data_envio_ml  TIMESTAMPTZ;

COMMENT ON COLUMN prest_notas_fiscais.fonte_emissao IS
    'Origem da emissão: PULSE_ERP | MERCADO_LIVRE | SISTEMA_EXTERNO';
COMMENT ON COLUMN prest_notas_fiscais.enviada_ml IS
    'Indica se a NF-e foi enviada ao Mercado Livre (upload de dados fiscais para liberar etiqueta)';
COMMENT ON COLUMN prest_notas_fiscais.data_envio_ml IS
    'Timestamp do envio da NF-e ao Mercado Livre';

-- 3. Índice para reprocessador de pendências ML
CREATE INDEX IF NOT EXISTS idx_notas_fiscais_pendentes_ml
    ON prest_notas_fiscais (usuario_id, status_sefaz, enviada_ml)
    WHERE status_sefaz = 'AUTORIZADA' AND enviada_ml = FALSE;

-- 4. Ajustar notas existentes: todas as anteriores foram emitidas pelo Pulse ERP
UPDATE prest_notas_fiscais
    SET fonte_emissao = 'PULSE_ERP'
    WHERE fonte_emissao IS NULL OR fonte_emissao = '';

-- 5. Notas já autorizadas pelo webhook 'invoices' do ML eram de faturador nativo
UPDATE prest_notas_fiscais
    SET fonte_emissao = 'MERCADO_LIVRE'
    WHERE xmotivo LIKE '%Faturador Nativo do Mercado Livre%';

-- 6. Marcar como enviadas ao ML as notas que já foram autorizadas pelo próprio ML
-- (o ML já sabe delas, não precisa reenviar)
UPDATE prest_notas_fiscais
    SET enviada_ml = TRUE, data_envio_ml = data_autorizacao
    WHERE fonte_emissao = 'MERCADO_LIVRE'
      AND status_sefaz = 'AUTORIZADA';
