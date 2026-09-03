<?php

use yii\db\Migration;

/**
 * Class m260903_160000_add_matriz_fields_to_prest_encarte_produtos
 */
class m260903_160000_add_matriz_fields_to_prest_encarte_produtos extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $schema = $this->db->getTableSchema('{{%prest_encarte_produtos}}');
        if ($schema) {
            if (!isset($schema->columns['variante_id'])) {
                $this->addColumn('{{%prest_encarte_produtos}}', 'variante_id', $this->string(36)->null());
            }
            if (!isset($schema->columns['cor'])) {
                $this->addColumn('{{%prest_encarte_produtos}}', 'cor', $this->string(50)->null());
            }
            if (!isset($schema->columns['tamanho'])) {
                $this->addColumn('{{%prest_encarte_produtos}}', 'tamanho', $this->text()->null());
            }
            if (!isset($schema->columns['quantidade'])) {
                $this->addColumn('{{%prest_encarte_produtos}}', 'quantidade', $this->decimal(10, 2)->null()->defaultValue(0));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $schema = $this->db->getTableSchema('{{%prest_encarte_produtos}}');
        if ($schema) {
            if (isset($schema->columns['quantidade'])) {
                $this->dropColumn('{{%prest_encarte_produtos}}', 'quantidade');
            }
            if (isset($schema->columns['tamanho'])) {
                $this->dropColumn('{{%prest_encarte_produtos}}', 'tamanho');
            }
            if (isset($schema->columns['cor'])) {
                $this->dropColumn('{{%prest_encarte_produtos}}', 'cor');
            }
            if (isset($schema->columns['variante_id'])) {
                $this->dropColumn('{{%prest_encarte_produtos}}', 'variante_id');
            }
        }
    }
}
