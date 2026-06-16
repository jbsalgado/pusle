<?php

use yii\db\Migration;

class m260209_123710_add_asaas_wallet_id_to_colaboradores extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('prest_colaboradores', 'asaas_wallet_id', $this->string(100)->null());
        $this->createIndex('idx-colaborador-asaas-wallet', 'prest_colaboradores', 'asaas_wallet_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-colaborador-asaas-wallet', 'prest_colaboradores');
        $this->dropColumn('prest_colaboradores', 'asaas_wallet_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260209_123710_add_asaas_wallet_id_to_colaboradores cannot be reverted.\n";

        return false;
    }
    */
}
