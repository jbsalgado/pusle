<?php

use yii\db\Migration;

class m260209_123954_add_colaborador_id_to_asaas_cobrancas extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('asaas_cobrancas', 'colaborador_id', $this->string()->null());
        $this->createIndex('idx-asaas-cobranca-colaborador', 'asaas_cobrancas', 'colaborador_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-asaas-cobranca-colaborador', 'asaas_cobrancas');
        $this->dropColumn('asaas_cobrancas', 'colaborador_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260209_123954_add_colaborador_id_to_asaas_cobrancas cannot be reverted.\n";

        return false;
    }
    */
}
