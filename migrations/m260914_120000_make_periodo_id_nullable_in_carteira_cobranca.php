<?php

use yii\db\Migration;

/**
 * Class m260914_120000_make_periodo_id_nullable_in_carteira_cobranca
 * 
 * Altera a coluna periodo_id da tabela prest_carteira_cobranca para permitir NULL.
 * Isso evita falhas de restrição Not Null ao atribuir cartões a cobradores quando o período
 * ainda não foi gerado ou está sendo gerenciado de forma contínua pelo crediário.
 */
class m260914_120000_make_periodo_id_nullable_in_carteira_cobranca extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = '{{%prest_carteira_cobranca}}';
        $schema = $this->db->getTableSchema($table);

        if ($schema && isset($schema->columns['periodo_id'])) {
            $this->execute("ALTER TABLE {$table} ALTER COLUMN periodo_id DROP NOT NULL;");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $table = '{{%prest_carteira_cobranca}}';
        $schema = $this->db->getTableSchema($table);

        if ($schema && isset($schema->columns['periodo_id'])) {
            $this->execute("ALTER TABLE {$table} ALTER COLUMN periodo_id SET NOT NULL;");
        }
    }
}
