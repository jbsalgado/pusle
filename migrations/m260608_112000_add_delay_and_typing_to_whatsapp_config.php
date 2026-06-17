<?php

use yii\db\Migration;

/**
 * Adiciona campos delay_min, delay_max e simular_digitacao
 * na tabela pulse_whatsapp_config.
 */
class m260608_112000_add_delay_and_typing_to_whatsapp_config extends Migration
{
    public function safeUp(): void
    {
        $this->execute("
            ALTER TABLE pulse_whatsapp_config
            ADD COLUMN delay_min INTEGER NOT NULL DEFAULT 1500,
            ADD COLUMN delay_max INTEGER NOT NULL DEFAULT 2500,
            ADD COLUMN simular_digitacao SMALLINT NOT NULL DEFAULT 1;
        ");
        echo "✅ Colunas de delay e simulação de digitação adicionadas com sucesso!\n";
    }

    public function safeDown(): void
    {
        $this->execute("
            ALTER TABLE pulse_whatsapp_config
            DROP COLUMN delay_min,
            DROP COLUMN delay_max,
            DROP COLUMN simular_digitacao;
        ");
        echo "✅ Colunas de delay e simulação de digitação removidas!\n";
    }
}
