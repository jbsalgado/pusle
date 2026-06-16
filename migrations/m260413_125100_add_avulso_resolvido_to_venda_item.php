<?php

use yii\db\Migration;

/**
 * Class m260413_125100_add_avulso_resolvido_to_venda_item
 */
class m260413_125100_add_avulso_resolvido_to_venda_item extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%prest_venda_itens}}', 'avulso_resolvido', $this->boolean()->defaultValue(false));
        $this->createIndex('idx-venda_itens-avulso_resolvido', '{{%prest_venda_itens}}', 'avulso_resolvido');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-venda_itens-avulso_resolvido', '{{%prest_venda_itens}}');
        $this->dropColumn('{{%prest_venda_itens}}', 'avulso_resolvido');
    }
}
