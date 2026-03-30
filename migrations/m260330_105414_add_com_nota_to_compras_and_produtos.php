<?php

use yii\db\Migration;

class m260330_105414_add_com_nota_to_compras_and_produtos extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('prest_compras', 'com_nota', $this->boolean()->notNull()->defaultValue(false));
        $this->addColumn('prest_produtos', 'com_nota', $this->boolean()->notNull()->defaultValue(false));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('prest_produtos', 'com_nota');
        $this->dropColumn('prest_compras', 'com_nota');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260330_105414_add_com_nota_to_compras_and_produtos cannot be reverted.\n";

        return false;
    }
    */
}
