<?php

use yii\db\Migration;

/**
 * Class m260904_210500_add_catalogo_ativo_to_loja_configuracao
 * Adiciona controle booleano de exibição pública do catálogo (Modo Implantação)
 */
class m260904_210500_add_catalogo_ativo_to_loja_configuracao extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = 'loja_configuracao';
        $schema = $this->db->getTableSchema($table, true);

        if ($schema) {
            if (!isset($schema->columns['catalogo_ativo'])) {
                $this->addColumn($table, 'catalogo_ativo', $this->boolean()->notNull()->defaultValue(true));
                $this->execute("COMMENT ON COLUMN {$table}.catalogo_ativo IS 'Define se o catálogo online está ativo para acesso público ou em modo implantação'");
            }

            if (!isset($schema->columns['mensagem_manutencao'])) {
                $this->addColumn($table, 'mensagem_manutencao', $this->string(500)->null());
                $this->execute("COMMENT ON COLUMN {$table}.mensagem_manutencao IS 'Mensagem personalizada exibida aos visitantes quando o catálogo estiver em implantação'");
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $table = 'loja_configuracao';
        $schema = $this->db->getTableSchema($table, true);

        if ($schema) {
            if (isset($schema->columns['mensagem_manutencao'])) {
                $this->dropColumn($table, 'mensagem_manutencao');
            }
            if (isset($schema->columns['catalogo_ativo'])) {
                $this->dropColumn($table, 'catalogo_ativo');
            }
        }

        return true;
    }
}
