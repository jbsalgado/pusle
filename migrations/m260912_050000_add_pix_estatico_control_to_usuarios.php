<?php

use yii\db\Migration;

/**
 * Migration para adicionar colunas de controle de liberação e cota de PIX Estático por lojista.
 */
class m260912_050000_add_pix_estatico_control_to_usuarios extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = '{{%prest_usuarios}}';
        $schema = $this->db->getTableSchema($table);

        if ($schema) {
            if (!isset($schema->columns['pix_estatico_liberado_admin'])) {
                $this->addColumn($table, 'pix_estatico_liberado_admin', $this->boolean()->defaultValue(false));
                $this->execute("COMMENT ON COLUMN {$table}.pix_estatico_liberado_admin IS 'Indica se o SaaS Admin autorizou o lojista a usar PIX Estático (chave própria) mesmo com MP conectado';");
            }

            if (!isset($schema->columns['pix_estatico_limite_vendas'])) {
                $this->addColumn($table, 'pix_estatico_limite_vendas', $this->integer()->defaultValue(0));
                $this->execute("COMMENT ON COLUMN {$table}.pix_estatico_limite_vendas IS 'Limite de vendas permitidas via PIX Estático (50, 100, 500 etc. NULL ou -1 = ilimitado, 0 = bloqueado)';");
            }

            if (!isset($schema->columns['pix_estatico_vendas_realizadas'])) {
                $this->addColumn($table, 'pix_estatico_vendas_realizadas', $this->integer()->defaultValue(0));
                $this->execute("COMMENT ON COLUMN {$table}.pix_estatico_vendas_realizadas IS 'Contador de vendas realizadas com PIX Estático durante a vigência da cota';");
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $table = '{{%prest_usuarios}}';
        $schema = $this->db->getTableSchema($table);

        if ($schema) {
            if (isset($schema->columns['pix_estatico_vendas_realizadas'])) {
                $this->dropColumn($table, 'pix_estatico_vendas_realizadas');
            }
            if (isset($schema->columns['pix_estatico_limite_vendas'])) {
                $this->dropColumn($table, 'pix_estatico_limite_vendas');
            }
            if (isset($schema->columns['pix_estatico_liberado_admin'])) {
                $this->dropColumn($table, 'pix_estatico_liberado_admin');
            }
        }
    }
}
