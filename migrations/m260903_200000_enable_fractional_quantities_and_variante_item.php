<?php

use yii\db\Migration;

/**
 * Migration: Habilita suporte definitivo a frações de estoque/venda e vinculação de variante nos itens de venda.
 */
class m260903_200000_enable_fractional_quantities_and_variante_item extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Ajustar colunas de quantidade e estoque para NUMERIC(12, 3)
        $this->alterColumn('prest_produtos', 'estoque_atual', 'NUMERIC(12, 3) DEFAULT 0');
        $this->alterColumn('prest_produtos', 'estoque_minimo', 'NUMERIC(12, 3) DEFAULT 0');
        $this->alterColumn('prest_produtos', 'estoque_maximo', 'NUMERIC(12, 3) NULL');
        $this->alterColumn('prest_produtos', 'ponto_corte', 'NUMERIC(12, 3) DEFAULT 0');

        $this->alterColumn('prest_venda_itens', 'quantidade', 'NUMERIC(12, 3) NOT NULL');

        $tableMov = $this->db->schema->getTableSchema('prest_estoque_movimentacoes');
        if ($tableMov !== null) {
            $this->alterColumn('prest_estoque_movimentacoes', 'quantidade', 'NUMERIC(12, 3) NOT NULL');
            $this->alterColumn('prest_estoque_movimentacoes', 'saldo_anterior', 'NUMERIC(12, 3) NOT NULL');
            $this->alterColumn('prest_estoque_movimentacoes', 'saldo_novo', 'NUMERIC(12, 3) NOT NULL');
        }

        // 2. Adicionar variante_id em prest_venda_itens
        $tableVendaItens = $this->db->schema->getTableSchema('prest_venda_itens');
        if ($tableVendaItens !== null && !isset($tableVendaItens->columns['variante_id'])) {
            $this->addColumn('prest_venda_itens', 'variante_id', 'UUID NULL');
            $this->addForeignKey(
                'fk_prest_venda_itens_variante',
                'prest_venda_itens',
                'variante_id',
                'prest_produto_variantes',
                'id',
                'SET NULL',
                'CASCADE'
            );
            $this->createIndex('idx_prest_venda_itens_variante', 'prest_venda_itens', 'variante_id');
        }

        // 3. Adicionar variante_id em prest_estoque_movimentacoes para rastreabilidade de estoque por grade
        if ($tableMov !== null && !isset($tableMov->columns['variante_id'])) {
            $this->addColumn('prest_estoque_movimentacoes', 'variante_id', 'UUID NULL');
            $this->addForeignKey(
                'fk_prest_estoque_mov_variante',
                'prest_estoque_movimentacoes',
                'variante_id',
                'prest_produto_variantes',
                'id',
                'SET NULL',
                'CASCADE'
            );
            $this->createIndex('idx_prest_estoque_mov_variante', 'prest_estoque_movimentacoes', 'variante_id');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_prest_estoque_mov_variante', 'prest_estoque_movimentacoes');
        $this->dropIndex('idx_prest_estoque_mov_variante', 'prest_estoque_movimentacoes');
        $this->dropColumn('prest_estoque_movimentacoes', 'variante_id');

        $this->dropForeignKey('fk_prest_venda_itens_variante', 'prest_venda_itens');
        $this->dropIndex('idx_prest_venda_itens_variante', 'prest_venda_itens');
        $this->dropColumn('prest_venda_itens', 'variante_id');

        $this->alterColumn('prest_estoque_movimentacoes', 'saldo_novo', 'INTEGER NOT NULL');
        $this->alterColumn('prest_estoque_movimentacoes', 'saldo_anterior', 'INTEGER NOT NULL');
        $this->alterColumn('prest_estoque_movimentacoes', 'quantidade', 'INTEGER NOT NULL');

        $this->alterColumn('prest_venda_itens', 'quantidade', 'INTEGER NOT NULL');

        $this->alterColumn('prest_produtos', 'ponto_corte', 'INTEGER DEFAULT 0');
        $this->alterColumn('prest_produtos', 'estoque_maximo', 'INTEGER NULL');
        $this->alterColumn('prest_produtos', 'estoque_minimo', 'INTEGER DEFAULT 0');
        $this->alterColumn('prest_produtos', 'estoque_atual', 'INTEGER DEFAULT 0');
    }
}
