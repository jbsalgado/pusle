<?php

use yii\db\Migration;

/**
 * Cria a tabela pulse_whatsapp_config para persistir os metadados de conexão
 * WhatsApp de cada cliente com a Evolution API Go (Engine v0.7.1).
 *
 * O campo empresa_id armazena o id (UUID) do registro em prest_usuarios,
 * que identifica univocamente cada empresa/tenant no PULSE-PLUS.
 */
class m260608_020000_create_pulse_whatsapp_config extends Migration
{
    public function safeUp(): void
    {
        // 1. Cria a tabela principal
        $this->execute("
            CREATE TABLE IF NOT EXISTS pulse_whatsapp_config (
                id            SERIAL PRIMARY KEY,
                empresa_id    UUID         NOT NULL,
                instance_name VARCHAR(255) NOT NULL,
                token         VARCHAR(255) NOT NULL DEFAULT '',
                status        VARCHAR(50)  NOT NULL DEFAULT 'DISCONNECTED',
                created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

                CONSTRAINT fk_whatsapp_config_empresa
                    FOREIGN KEY (empresa_id)
                    REFERENCES prest_usuarios(id)
                    ON DELETE CASCADE,

                CONSTRAINT uq_whatsapp_config_empresa
                    UNIQUE (empresa_id)
            )
        ");

        // 2. Índice para busca rápida por empresa_id
        $this->execute("
            CREATE INDEX IF NOT EXISTS idx_whatsapp_config_empresa
                ON pulse_whatsapp_config (empresa_id)
        ");

        // 3. Função de trigger para atualizar updated_at automaticamente
        $this->execute("
            CREATE OR REPLACE FUNCTION update_pulse_whatsapp_config_updated_at()
            RETURNS TRIGGER AS \$\$
            BEGIN
                NEW.updated_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql
        ");

        // 4. Trigger que invoca a função acima
        $this->execute("
            CREATE TRIGGER trigger_whatsapp_config_updated_at
                BEFORE UPDATE ON pulse_whatsapp_config
                FOR EACH ROW
                EXECUTE FUNCTION update_pulse_whatsapp_config_updated_at()
        ");

        echo "✅ Tabela pulse_whatsapp_config criada com sucesso!\n";
    }

    public function safeDown(): void
    {
        $this->execute("DROP TRIGGER IF EXISTS trigger_whatsapp_config_updated_at ON pulse_whatsapp_config");
        $this->execute("DROP FUNCTION IF EXISTS update_pulse_whatsapp_config_updated_at()");
        $this->execute("DROP TABLE IF EXISTS pulse_whatsapp_config CASCADE");

        echo "✅ Tabela pulse_whatsapp_config removida!\n";
    }
}
