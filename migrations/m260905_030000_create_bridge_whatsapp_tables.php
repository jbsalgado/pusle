<?php

use yii\db\Migration;

/**
 * Cria as tabelas para o Pulse Bridge WhatsApp (Agente Local Go Whatsmeow)
 * 100% isolado de qualquer módulo existente da Evolution API.
 */
class m260905_030000_create_bridge_whatsapp_tables extends Migration
{
    public function safeUp(): void
    {
        // 1. Tabela de configuração e status do agente local da loja
        $this->execute("
            CREATE TABLE IF NOT EXISTS prest_bridge_whatsapp_lojas (
                id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                usuario_id          UUID NOT NULL,
                token_agente        VARCHAR(64) NOT NULL,
                status_conexao      VARCHAR(32) NOT NULL DEFAULT 'disconnected',
                qr_code_base64      TEXT NULL,
                telefone_conectado  VARCHAR(32) NULL,
                push_name           VARCHAR(255) NULL,
                ip_origem_agente    VARCHAR(64) NULL,
                ultimo_heartbeat    TIMESTAMP NULL,
                created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

                CONSTRAINT fk_bridge_whatsapp_loja_usuario
                    FOREIGN KEY (usuario_id)
                    REFERENCES prest_usuarios(id)
                    ON DELETE CASCADE,

                CONSTRAINT uq_bridge_whatsapp_loja_usuario
                    UNIQUE (usuario_id),

                CONSTRAINT uq_bridge_whatsapp_loja_token
                    UNIQUE (token_agente)
            )
        ");

        $this->execute("CREATE INDEX IF NOT EXISTS idx_bridge_whatsapp_lojas_usuario ON prest_bridge_whatsapp_lojas (usuario_id)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_bridge_whatsapp_lojas_token ON prest_bridge_whatsapp_lojas (token_agente)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_bridge_whatsapp_lojas_status ON prest_bridge_whatsapp_lojas (status_conexao)");

        // 2. Tabela de mensagens (fila de envio e mensagens recebidas via agente local)
        $this->execute("
            CREATE TABLE IF NOT EXISTS prest_bridge_whatsapp_mensagens (
                id                   UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                usuario_id           UUID NOT NULL,
                direcao              VARCHAR(16) NOT NULL DEFAULT 'outbound',
                numero_destino       VARCHAR(32) NOT NULL,
                numero_remetente      VARCHAR(32) NULL,
                tipo                 VARCHAR(32) NOT NULL DEFAULT 'text',
                conteudo_texto       TEXT NULL,
                midia_url            TEXT NULL,
                status               VARCHAR(32) NOT NULL DEFAULT 'pending',
                mensagem_id_whatsapp VARCHAR(128) NULL,
                erro_motivo          TEXT NULL,
                created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

                CONSTRAINT fk_bridge_whatsapp_msg_usuario
                    FOREIGN KEY (usuario_id)
                    REFERENCES prest_usuarios(id)
                    ON DELETE CASCADE
            )
        ");

        $this->execute("CREATE INDEX IF NOT EXISTS idx_bridge_whatsapp_msg_usuario ON prest_bridge_whatsapp_mensagens (usuario_id)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_bridge_whatsapp_msg_status ON prest_bridge_whatsapp_mensagens (status)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_bridge_whatsapp_msg_direcao ON prest_bridge_whatsapp_mensagens (direcao)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_bridge_whatsapp_msg_created ON prest_bridge_whatsapp_mensagens (created_at)");
    }

    public function safeDown(): void
    {
        $this->execute("DROP TABLE IF EXISTS prest_bridge_whatsapp_mensagens CASCADE;");
        $this->execute("DROP TABLE IF EXISTS prest_bridge_whatsapp_lojas CASCADE;");
    }
}
