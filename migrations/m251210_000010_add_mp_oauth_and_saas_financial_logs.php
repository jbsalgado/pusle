<?php

use yii\db\Migration;

/**
 * Adiciona campos de OAuth do Mercado Pago em prest_usuarios
 * e cria a tabela de auditoria financeira saas_financial_logs.
 */
class m251210_000010_add_mp_oauth_and_saas_financial_logs extends Migration
{
    public function safeUp()
    {
        // === Campos OAuth no tenant (prest_usuarios) ===
        $this->addColumn('{{%prest_usuarios}}', 'mp_access_token', $this->text()->null());
        $this->addColumn('{{%prest_usuarios}}', 'mp_refresh_token', $this->text()->null());
        $this->addColumn('{{%prest_usuarios}}', 'mp_public_key', $this->string(255)->null());
        $this->addColumn('{{%prest_usuarios}}', 'mp_user_id', $this->string(50)->null());
        $this->addColumn('{{%prest_usuarios}}', 'mp_token_expiration', $this->dateTime()->null());

        // Comentários para documentação no banco
        $this->execute("COMMENT ON COLUMN {{%prest_usuarios}}.mp_access_token IS 'Access token do vendedor obtido via OAuth do Mercado Pago';");
        $this->execute("COMMENT ON COLUMN {{%prest_usuarios}}.mp_refresh_token IS 'Refresh token do vendedor obtido via OAuth do Mercado Pago';");
        $this->execute("COMMENT ON COLUMN {{%prest_usuarios}}.mp_public_key IS 'Public key do vendedor (Checkout/Pix) obtida via OAuth';");
        $this->execute("COMMENT ON COLUMN {{%prest_usuarios}}.mp_user_id IS 'Identificador do usuário Mercado Pago (seller_id)';");
        $this->execute("COMMENT ON COLUMN {{%prest_usuarios}}.mp_token_expiration IS 'Data/hora de expiração do access token do vendedor';");

        // === Tabela de logs financeiros do SaaS ===
        $this->execute("
            CREATE TABLE IF NOT EXISTS {{%saas_financial_logs}} (
                id BIGSERIAL PRIMARY KEY,
                tenant_id UUID NOT NULL REFERENCES {{%prest_usuarios}}(id) ON DELETE CASCADE,
                order_id UUID NOT NULL REFERENCES {{%prest_vendas}}(id) ON DELETE CASCADE,
                mp_payment_id VARCHAR(100),
                total_amount NUMERIC(12,2) NOT NULL,
                platform_fee NUMERIC(12,2) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            );
        ");

        $this->createIndex('idx_saas_fin_logs_tenant', '{{%saas_financial_logs}}', 'tenant_id');
        $this->createIndex('idx_saas_fin_logs_order', '{{%saas_financial_logs}}', 'order_id');
        $this->createIndex('idx_saas_fin_logs_payment', '{{%saas_financial_logs}}', 'mp_payment_id');
        $this->createIndex('uq_saas_fin_logs_payment', '{{%saas_financial_logs}}', ['tenant_id', 'order_id', 'mp_payment_id'], true);

        $this->execute("COMMENT ON TABLE {{%saas_financial_logs}} IS 'Auditoria financeira das comissões da plataforma (split Mercado Pago).';");
        $this->execute("COMMENT ON COLUMN {{%saas_financial_logs}}.platform_fee IS 'Valor da comissão da plataforma retida na transação.';");
    }

    public function safeDown()
    {
        $this->dropIndex('idx_saas_fin_logs_payment', '{{%saas_financial_logs}}');
        $this->dropIndex('idx_saas_fin_logs_order', '{{%saas_financial_logs}}');
        $this->dropIndex('idx_saas_fin_logs_tenant', '{{%saas_financial_logs}}');
        $this->dropIndex('uq_saas_fin_logs_payment', '{{%saas_financial_logs}}');
        $this->dropTable('{{%saas_financial_logs}}');

        $this->dropColumn('{{%prest_usuarios}}', 'mp_token_expiration');
        $this->dropColumn('{{%prest_usuarios}}', 'mp_user_id');
        $this->dropColumn('{{%prest_usuarios}}', 'mp_public_key');
        $this->dropColumn('{{%prest_usuarios}}', 'mp_refresh_token');
        $this->dropColumn('{{%prest_usuarios}}', 'mp_access_token');
    }
}

