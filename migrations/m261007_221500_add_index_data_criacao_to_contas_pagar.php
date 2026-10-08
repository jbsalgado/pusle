<?php

use yii\db\Migration;

/**
 * Class m261007_221500_add_index_data_criacao_to_contas_pagar
 */
class m261007_221500_add_index_data_criacao_to_contas_pagar extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("
            CREATE INDEX IF NOT EXISTS idx_prest_contas_pagar_user_created 
            ON prest_contas_pagar (usuario_id, data_criacao DESC);
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute("
            DROP INDEX IF EXISTS idx_prest_contas_pagar_user_created;
        ");
    }
}
