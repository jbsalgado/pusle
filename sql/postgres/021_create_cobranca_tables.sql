-- ============================================================================================================
-- Migration: Tabelas do Módulo de Cobranças
-- ============================================================================================================
-- Autor: Antigravity AI
-- Data: 2026-02-11
-- Descrição: Cria as tabelas necessárias para o sistema de automação de cobranças via WhatsApp
-- ============================================================================================================
-- Tabela: prest_cobranca_configuracao
-- Armazena as configurações de integração WhatsApp e parâmetros de envio
CREATE TABLE IF NOT EXISTS prest_cobranca_configuracao (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    usuario_id UUID NOT NULL REFERENCES prest_usuarios(id) ON DELETE CASCADE,
    -- Configurações de integração
    ativo BOOLEAN DEFAULT true,
    whatsapp_provider VARCHAR(20) DEFAULT 'zapi',
    -- 'zapi', 'twilio', 'evolution'
    zapi_instance_id VARCHAR(100),
    zapi_token VARCHAR(255),
    -- Configurações de envio
    dias_antes_vencimento INTEGER DEFAULT 3,
    enviar_dia_vencimento BOOLEAN DEFAULT true,
    dias_apos_vencimento INTEGER DEFAULT 1,
    horario_envio TIME DEFAULT '09:00:00',
    -- Auditoria
    data_criacao TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    data_atualizacao TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    CONSTRAINT uq_cobranca_config_usuario UNIQUE(usuario_id)
);
CREATE INDEX idx_cobranca_config_usuario ON prest_cobranca_configuracao(usuario_id);
CREATE INDEX idx_cobranca_config_ativo ON prest_cobranca_configuracao(ativo);
COMMENT ON TABLE prest_cobranca_configuracao IS 'Configurações de automação de cobranças por usuário';
COMMENT ON COLUMN prest_cobranca_configuracao.ativo IS 'Define se a automação está ativa';
COMMENT ON COLUMN prest_cobranca_configuracao.dias_antes_vencimento IS 'Quantos dias antes do vencimento enviar lembrete';
COMMENT ON COLUMN prest_cobranca_configuracao.horario_envio IS 'Horário padrão para envio das mensagens';
-- ============================================================================================================
-- Tabela: prest_cobranca_template
-- Armazena os templates de mensagens personalizáveis
CREATE TABLE IF NOT EXISTS prest_cobranca_template (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    usuario_id UUID NOT NULL REFERENCES prest_usuarios(id) ON DELETE CASCADE,
    -- Dados do template
    tipo VARCHAR(20) NOT NULL,
    -- 'ANTES', 'DIA', 'APOS'
    titulo VARCHAR(100) NOT NULL,
    mensagem TEXT NOT NULL,
    ativo BOOLEAN DEFAULT true,
    -- Auditoria
    data_criacao TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    data_atualizacao TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    CONSTRAINT uq_cobranca_template_usuario_tipo UNIQUE(usuario_id, tipo),
    CONSTRAINT chk_cobranca_template_tipo CHECK (tipo IN ('ANTES', 'DIA', 'APOS'))
);
CREATE INDEX idx_cobranca_template_usuario ON prest_cobranca_template(usuario_id);
CREATE INDEX idx_cobranca_template_tipo ON prest_cobranca_template(tipo);
CREATE INDEX idx_cobranca_template_ativo ON prest_cobranca_template(ativo);
COMMENT ON TABLE prest_cobranca_template IS 'Templates de mensagens para cobranças';
COMMENT ON COLUMN prest_cobranca_template.tipo IS 'Tipo de template: ANTES (antes do vencimento), DIA (dia do vencimento), APOS (após vencimento)';
COMMENT ON COLUMN prest_cobranca_template.mensagem IS 'Texto do template com variáveis: {nome}, {valor}, {vencimento}, {parcela}';
-- ============================================================================================================
-- Tabela: prest_cobranca_historico
-- Registra todas as tentativas de envio de cobranças
CREATE TABLE IF NOT EXISTS prest_cobranca_historico (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    usuario_id UUID NOT NULL REFERENCES prest_usuarios(id) ON DELETE CASCADE,
    parcela_id UUID NOT NULL REFERENCES prest_parcelas(id) ON DELETE CASCADE,
    -- Dados do envio
    tipo VARCHAR(20) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    mensagem TEXT NOT NULL,
    -- Status e resposta
    status VARCHAR(20) DEFAULT 'PENDENTE',
    -- 'ENVIADO', 'FALHA', 'PENDENTE'
    resposta_api TEXT,
    tentativas INTEGER DEFAULT 0,
    -- Datas
    data_envio TIMESTAMP WITH TIME ZONE,
    data_criacao TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    CONSTRAINT chk_cobranca_historico_tipo CHECK (tipo IN ('ANTES', 'DIA', 'APOS')),
    CONSTRAINT chk_cobranca_historico_status CHECK (status IN ('ENVIADO', 'FALHA', 'PENDENTE'))
);
CREATE INDEX idx_cobranca_historico_usuario ON prest_cobranca_historico(usuario_id);
CREATE INDEX idx_cobranca_historico_parcela ON prest_cobranca_historico(parcela_id);
CREATE INDEX idx_cobranca_historico_tipo ON prest_cobranca_historico(tipo);
CREATE INDEX idx_cobranca_historico_status ON prest_cobranca_historico(status);
CREATE INDEX idx_cobranca_historico_data_envio ON prest_cobranca_historico(data_envio);
CREATE INDEX idx_cobranca_historico_data_criacao ON prest_cobranca_historico(data_criacao);
COMMENT ON TABLE prest_cobranca_historico IS 'Histórico de envios de cobranças via WhatsApp';
COMMENT ON COLUMN prest_cobranca_historico.status IS 'Status do envio: ENVIADO (sucesso), FALHA (erro), PENDENTE (aguardando)';
COMMENT ON COLUMN prest_cobranca_historico.tentativas IS 'Número de tentativas de envio';
COMMENT ON COLUMN prest_cobranca_historico.resposta_api IS 'Resposta JSON da API do WhatsApp';
-- ============================================================================================================
-- Inserir templates padrão para todos os usuários existentes
INSERT INTO prest_cobranca_template (usuario_id, tipo, titulo, mensagem, ativo)
SELECT id as usuario_id,
    'ANTES' as tipo,
    'Lembrete - 3 dias antes' as titulo,
    E'Olá {nome}! 👋\n\nLembramos que a parcela {parcela} no valor de R$ {valor} vence em {vencimento}.\n\nPara evitar juros, realize o pagamento até a data de vencimento.\n\nQualquer dúvida, estamos à disposição!' as mensagem,
    true as ativo
FROM prest_usuarios
WHERE NOT EXISTS (
        SELECT 1
        FROM prest_cobranca_template
        WHERE usuario_id = prest_usuarios.id
            AND tipo = 'ANTES'
    );
INSERT INTO prest_cobranca_template (usuario_id, tipo, titulo, mensagem, ativo)
SELECT id as usuario_id,
    'DIA' as tipo,
    'Vencimento Hoje' as titulo,
    E'Olá {nome}! 📅\n\nHoje é o vencimento da parcela {parcela} no valor de R$ {valor}.\n\nPor favor, realize o pagamento para evitar juros e multa.\n\nObrigado!' as mensagem,
    true as ativo
FROM prest_usuarios
WHERE NOT EXISTS (
        SELECT 1
        FROM prest_cobranca_template
        WHERE usuario_id = prest_usuarios.id
            AND tipo = 'DIA'
    );
INSERT INTO prest_cobranca_template (usuario_id, tipo, titulo, mensagem, ativo)
SELECT id as usuario_id,
    'APOS' as tipo,
    'Pagamento Vencido' as titulo,
    E'Olá {nome}! ⚠️\n\nA parcela {parcela} no valor de R$ {valor} venceu em {vencimento}.\n\nPor favor, regularize o pagamento o quanto antes para evitar acréscimos.\n\nEstamos à disposição para negociação.' as mensagem,
    true as ativo
FROM prest_usuarios
WHERE NOT EXISTS (
        SELECT 1
        FROM prest_cobranca_template
        WHERE usuario_id = prest_usuarios.id
            AND tipo = 'APOS'
    );
-- ============================================================================================================
-- FIM DA MIGRATION
-- ============================================================================================================