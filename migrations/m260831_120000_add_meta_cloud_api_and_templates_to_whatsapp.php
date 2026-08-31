<?php

use yii\db\Migration;

/**
 * Migration para adicionar suporte à API Oficial da Meta (WhatsApp Cloud API)
 * e criar tabela de gerenciamento de Templates HSM.
 */
class m260831_120000_add_meta_cloud_api_and_templates_to_whatsapp extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Adicionar colunas de suporte à Meta Cloud API na tabela de configuração
        $this->execute("
            ALTER TABLE pulse_whatsapp_config
            ADD COLUMN IF NOT EXISTS provider VARCHAR(30) NOT NULL DEFAULT 'evolution',
            ADD COLUMN IF NOT EXISTS meta_waba_id VARCHAR(100) NULL,
            ADD COLUMN IF NOT EXISTS meta_phone_number_id VARCHAR(100) NULL,
            ADD COLUMN IF NOT EXISTS meta_access_token TEXT NULL,
            ADD COLUMN IF NOT EXISTS meta_webhook_verify_token VARCHAR(100) NULL;
        ");

        // 2. Criar tabela de templates HSM do WhatsApp
        $this->execute("
            CREATE TABLE IF NOT EXISTS pulse_whatsapp_templates (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                empresa_id UUID NOT NULL REFERENCES prest_usuarios(id) ON DELETE CASCADE,
                name VARCHAR(100) NOT NULL,
                language VARCHAR(10) NOT NULL DEFAULT 'pt_BR',
                category VARCHAR(50) NOT NULL DEFAULT 'UTILITY',
                header_type VARCHAR(20) NOT NULL DEFAULT 'NONE',
                header_text TEXT NULL,
                body_text TEXT NOT NULL,
                footer_text VARCHAR(255) NULL,
                buttons_json JSONB NULL,
                components_json JSONB NULL,
                meta_template_id VARCHAR(100) NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'PENDING',
                created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT uq_whatsapp_template_empresa_name UNIQUE (empresa_id, name, language)
            );
        ");

        $this->execute("CREATE INDEX IF NOT EXISTS idx_whatsapp_templates_empresa ON pulse_whatsapp_templates (empresa_id);");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_whatsapp_templates_status ON pulse_whatsapp_templates (empresa_id, status);");

        echo "✅ Estrutura para Meta Cloud API e Templates HSM criada com sucesso!\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute("DROP TABLE IF EXISTS pulse_whatsapp_templates;");
        $this->execute("
            ALTER TABLE pulse_whatsapp_config
            DROP COLUMN IF EXISTS provider,
            DROP COLUMN IF EXISTS meta_waba_id,
            DROP COLUMN IF EXISTS meta_phone_number_id,
            DROP COLUMN IF EXISTS meta_access_token,
            DROP COLUMN IF EXISTS meta_webhook_verify_token;
        ");
    }
}
