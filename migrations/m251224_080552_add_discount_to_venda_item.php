<?php

use yii\db\Migration;

/**
 * Class m251224_080552_add_discount_to_venda_item
 */
class m251224_080552_add_discount_to_venda_item extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('prest_venda_itens', 'desconto_percentual', $this->decimal(10, 2)->defaultValue(0.00));
        $this->addColumn('prest_venda_itens', 'desconto_valor', $this->decimal(10, 2)->defaultValue(0.00));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('prest_venda_itens', 'desconto_percentual');
        $this->dropColumn('prest_venda_itens', 'desconto_valor');
    }
}
