<?php

use yii\db\Migration;

/**
 * Class m260917_183000_add_venda_avulsa_ativa_to_loja_configuracao
 * Adiciona controle booleano para permitir ou não itens avulsos na Venda Direta (Padrão: false)
 */
class m260917_183000_add_venda_avulsa_ativa_to_loja_configuracao extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = 'loja_configuracao';
        $schema = $this->db->getTableSchema($table, true);

        if ($schema && !isset($schema->columns['venda_avulsa_ativa'])) {
            $this->addColumn($table, 'venda_avulsa_ativa', $this->boolean()->notNull()->defaultValue(false));
            $this->execute("COMMENT ON COLUMN {$table}.venda_avulsa_ativa IS 'Define se a opção de venda avulsa (item não cadastrado) está habilitada na tela de Venda Direta'");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $table = 'loja_configuracao';
        $schema = $this->db->getTableSchema($table, true);

        if ($schema && isset($schema->columns['venda_avulsa_ativa'])) {
            $this->dropColumn($table, 'venda_avulsa_ativa');
        }

        return true;
    }
}
