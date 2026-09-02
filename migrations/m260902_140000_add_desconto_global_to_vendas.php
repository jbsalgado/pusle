<?php

use yii\db\Migration;

/**
 * Class m260902_140000_add_desconto_global_to_vendas
 */
class m260902_140000_add_desconto_global_to_vendas extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%prest_vendas}}', 'desconto_global_valor', $this->decimal(10, 2)->defaultValue(0));
        $this->addColumn('{{%prest_vendas}}', 'desconto_global_tipo', $this->string(50)->null());
        $this->addColumn('{{%prest_vendas}}', 'observacao_desconto_global', $this->text()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%prest_vendas}}', 'desconto_global_valor');
        $this->dropColumn('{{%prest_vendas}}', 'desconto_global_tipo');
        $this->dropColumn('{{%prest_vendas}}', 'observacao_desconto_global');
    }
}
