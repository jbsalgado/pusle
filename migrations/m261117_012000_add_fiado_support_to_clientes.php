<?php

use yii\db\Migration;

/**
 * Adiciona controle de permissão e limite de fiado por cliente em prest_clientes.
 */
class m261117_012000_add_fiado_support_to_clientes extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('prest_clientes', true);
        if ($schema) {
            if (!isset($schema->columns['permite_fiado'])) {
                $this->addColumn('prest_clientes', 'permite_fiado', $this->boolean()->notNull()->defaultValue(false)->comment('Autoriza compras a prazo / fiado no PDV'));
            }
            if (!isset($schema->columns['limite_fiado'])) {
                $this->addColumn('prest_clientes', 'limite_fiado', $this->decimal(10, 2)->null()->comment('Limite máximo de crédito em aberto no fiado (opcional)'));
            }
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('prest_clientes', true);
        if ($schema) {
            if (isset($schema->columns['limite_fiado'])) {
                $this->dropColumn('prest_clientes', 'limite_fiado');
            }
            if (isset($schema->columns['permite_fiado'])) {
                $this->dropColumn('prest_clientes', 'permite_fiado');
            }
        }
    }
}
