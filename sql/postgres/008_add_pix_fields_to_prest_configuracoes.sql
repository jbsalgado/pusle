-- ===================================================================
-- Script PostgreSQL: Adicionar campos PIX na tabela prest_configuracoes
-- ===================================================================
-- Descrição: Adiciona campos para armazenar configurações PIX
--            (chave PIX, nome do recebedor, cidade) na tabela de configurações
-- ===================================================================
-- Data: 2024-12-XX
-- ===================================================================

BEGIN;

-- Adicionar colunas PIX na tabela prest_configuracoes
ALTER TABLE prest_configuracoes
    ADD COLUMN IF NOT EXISTS pix_chave VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS pix_nome VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS pix_cidade VARCHAR(50) NULL;

-- Comentários nas colunas
COMMENT ON COLUMN prest_configuracoes.pix_chave IS 'Chave PIX (celular, CPF, CNPJ, email ou chave aleatória)';
COMMENT ON COLUMN prest_configuracoes.pix_nome IS 'Nome do recebedor para QR Code PIX (máx 25 caracteres, sem acentos)';
COMMENT ON COLUMN prest_configuracoes.pix_cidade IS 'Cidade do recebedor para QR Code PIX (máx 15 caracteres, sem acentos)';

COMMIT;

