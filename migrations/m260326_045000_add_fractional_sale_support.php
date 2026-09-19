<?php

use yii\db\Migration;

/**
 * Class m260326_045000_add_fractional_sale_support
 */
class m260326_045000_add_fractional_sale_support extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Ajustes na tabela prest_produtos
        $table = $this->db->schema->getTableSchema('prest_produtos');
        if (isset($table->columns['estoque_atual'])) {
            $this->alterColumn('prest_produtos', 'estoque_atual', $this->decimal(12, 3)->defaultValue(0));
        }
        if (isset($table->columns['estoque_minimo'])) {
            $this->alterColumn('prest_produtos', 'estoque_minimo', $this->decimal(12, 3)->defaultValue(0));
        }
        if (isset($table->columns['estoque_maximo'])) {
            $this->alterColumn('prest_produtos', 'estoque_maximo', $this->decimal(12, 3));
        } else {
            $this->addColumn('prest_produtos', 'estoque_maximo', $this->decimal(12, 3));
        }
        if (isset($table->columns['ponto_corte'])) {
            $this->alterColumn('prest_produtos', 'ponto_corte', $this->decimal(12, 3)->defaultValue(0));
        }

        if (!isset($table->columns['venda_fracionada'])) {
            $this->addColumn('prest_produtos', 'venda_fracionada', $this->boolean()->defaultValue(false));
        }
        if (!isset($table->columns['unidade_medida'])) {
            $this->addColumn('prest_produtos', 'unidade_medida', $this->string(10)->defaultValue('UN'));
        }

        // 2. Ajustes na tabela prest_venda_itens
        $tableVendaItens = $this->db->schema->getTableSchema('prest_venda_itens');
        if (isset($tableVendaItens->columns['quantidade'])) {
            $this->alterColumn('prest_venda_itens', 'quantidade', $this->decimal(12, 3)->notNull());
        }

        // 3. Ajustes na tabela prest_itens_compra
        $tableItensCompra = $this->db->schema->getTableSchema('prest_itens_compra');
        if (isset($tableItensCompra->columns['quantidade'])) {
            $this->alterColumn('prest_itens_compra', 'quantidade', $this->decimal(12, 3)->notNull());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('prest_produtos', 'unidade_medida');
        $this->dropColumn('prest_produtos', 'venda_fracionada');

        $this->alterColumn('prest_produtos', 'ponto_corte', $this->integer()->defaultValue(0));
        $this->alterColumn('prest_produtos', 'estoque_maximo', $this->integer());
        $this->alterColumn('prest_produtos', 'estoque_minimo', $this->integer()->defaultValue(0));
        $this->alterColumn('prest_produtos', 'estoque_atual', $this->integer()->defaultValue(0));

        $this->alterColumn('prest_venda_itens', 'quantidade', $this->integer()->notNull());
        $this->alterColumn('prest_itens_compra', 'quantidade', $this->integer()->notNull());
    }
}
