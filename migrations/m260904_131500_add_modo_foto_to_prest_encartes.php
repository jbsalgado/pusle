<?php

use yii\db\Migration;

/**
 * Migration: Adiciona coluna modo_foto na tabela prest_encartes para controle de enquadramento (contain/cover)
 */
class m260904_131500_add_modo_foto_to_prest_encartes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = $this->db->schema->getTableSchema('prest_encartes');
        if ($table !== null && !isset($table->columns['modo_foto'])) {
            $this->addColumn('prest_encartes', 'modo_foto', $this->string(20)->defaultValue('contain'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $table = $this->db->schema->getTableSchema('prest_encartes');
        if ($table !== null && isset($table->columns['modo_foto'])) {
            $this->dropColumn('prest_encartes', 'modo_foto');
        }
    }
}
