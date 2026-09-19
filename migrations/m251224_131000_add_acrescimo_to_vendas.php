<?php

use yii\db\Migration;

/**
 * Class m251224_131000_add_acrescimo_to_vendas
 */
class m251224_131000_add_acrescimo_to_vendas extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = $this->db->schema->getTableSchema('{{%prest_vendas}}');
        if (!isset($table->columns['acrescimo_valor'])) {
            $this->addColumn('{{%prest_vendas}}', 'acrescimo_valor', $this->decimal(10, 2)->defaultValue(0));
        }
        if (!isset($table->columns['acrescimo_tipo'])) {
            $this->addColumn('{{%prest_vendas}}', 'acrescimo_tipo', $this->string(50)->null());
        }
        if (!isset($table->columns['observacao_acrescimo'])) {
            $this->addColumn('{{%prest_vendas}}', 'observacao_acrescimo', $this->text()->null());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%prest_vendas}}', 'acrescimo_valor');
        $this->dropColumn('{{%prest_vendas}}', 'acrescimo_tipo');
        $this->dropColumn('{{%prest_vendas}}', 'observacao_acrescimo');
    }
}
