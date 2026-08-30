<?php

use yii\db\Migration;

/**
 * Adiciona colunas de proxy, anti-banimento e limites diários
 * na tabela pulse_whatsapp_config.
 */
class m260830_032000_add_proxy_and_anti_ban_settings_to_whatsapp_config extends Migration
{
    public function safeUp(): void
    {
        // 1. Colunas para isolamento de rede via Proxy por tenant
        $this->execute("
            ALTER TABLE pulse_whatsapp_config
            ADD COLUMN IF NOT EXISTS proxy_host VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS proxy_user VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS proxy_pass VARCHAR(255) NULL;
        ");

        // 2. Colunas de controle de lotes e pausas
        $this->execute("
            ALTER TABLE pulse_whatsapp_config
            ADD COLUMN IF NOT EXISTS lote_tamanho INTEGER NOT NULL DEFAULT 15,
            ADD COLUMN IF NOT EXISTS lote_pausa_segundos INTEGER NOT NULL DEFAULT 120;
        ");

        // 3. Colunas de limite diário de mensagens e controle diário
        $this->execute("
            ALTER TABLE pulse_whatsapp_config
            ADD COLUMN IF NOT EXISTS limite_diario_mensagens INTEGER NOT NULL DEFAULT 150,
            ADD COLUMN IF NOT EXISTS mensagens_enviadas_hoje INTEGER NOT NULL DEFAULT 0,
            ADD COLUMN IF NOT EXISTS data_contador_diario DATE NULL;
        ");

        // 4. Ajuste dos defaults de delay para faixas seguras de anti-banimento (15s a 45s)
        $this->execute("
            ALTER TABLE pulse_whatsapp_config
            ALTER COLUMN delay_min SET DEFAULT 15000,
            ALTER COLUMN delay_max SET DEFAULT 45000;
        ");

        echo "✅ Colunas de proxy, anti-banimento e limites diários adicionadas à pulse_whatsapp_config!\n";
    }

    public function safeDown(): void
    {
        $this->execute("
            ALTER TABLE pulse_whatsapp_config
            DROP COLUMN IF EXISTS proxy_host,
            DROP COLUMN IF EXISTS proxy_user,
            DROP COLUMN IF EXISTS proxy_pass,
            DROP COLUMN IF EXISTS lote_tamanho,
            DROP COLUMN IF EXISTS lote_pausa_segundos,
            DROP COLUMN IF EXISTS limite_diario_mensagens,
            DROP COLUMN IF EXISTS mensagens_enviadas_hoje,
            DROP COLUMN IF EXISTS data_contador_diario;
        ");

        echo "✅ Colunas de proxy, anti-banimento e limites diários removidas!\n";
    }
}
