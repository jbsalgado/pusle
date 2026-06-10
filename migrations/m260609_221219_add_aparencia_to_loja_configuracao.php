<?php

use yii\db\Migration;

class m260609_221219_add_aparencia_to_loja_configuracao extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('loja_configuracao', 'aparencia_tema', $this->string(50)->defaultValue('azul'));
        $this->addColumn('loja_configuracao', 'aparencia_cor_primaria', $this->string(7));
        $this->addColumn('loja_configuracao', 'aparencia_cor_secundaria', $this->string(7));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('loja_configuracao', 'aparencia_tema');
        $this->dropColumn('loja_configuracao', 'aparencia_cor_primaria');
        $this->dropColumn('loja_configuracao', 'aparencia_cor_secundaria');
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260609_221219_add_aparencia_to_loja_configuracao cannot be reverted.\n";

        return false;
    }
    */
}
