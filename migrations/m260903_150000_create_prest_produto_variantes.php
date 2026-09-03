<?php

use yii\db\Migration;

/**
 * Migration: Cria a tabela prest_produto_variantes e estende prest_produto_fotos para vincular fotos a cores/variantes.
 */
class m260903_150000_create_prest_produto_variantes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Cria a tabela de variantes dedicada
        $this->createTable('prest_produto_variantes', [
            'id'                   => 'UUID PRIMARY KEY DEFAULT gen_random_uuid()',
            'produto_id'           => 'UUID NOT NULL',
            'cor'                  => 'VARCHAR(50) NOT NULL',
            'tamanho'              => 'VARCHAR(20) NOT NULL',
            'estoque_atual'        => 'NUMERIC(10, 2) NOT NULL DEFAULT 0',
            'preco_venda_sugerido' => 'NUMERIC(10, 2) NULL',
            'preco_custo'          => 'NUMERIC(10, 2) NULL',
            'codigo_barras'        => 'VARCHAR(100) NULL',
            'codigo_referencia'    => 'VARCHAR(100) NULL',
            'ativo'                => 'BOOLEAN NOT NULL DEFAULT TRUE',
            'data_criacao'         => 'TIMESTAMPTZ NOT NULL DEFAULT NOW()',
            'data_atualizacao'     => 'TIMESTAMPTZ NOT NULL DEFAULT NOW()',
        ]);

        // FK para produto mestre
        $this->addForeignKey(
            'fk_produto_variantes_produto',
            'prest_produto_variantes',
            'produto_id',
            'prest_produtos',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Unique constraint para impedir duplicidade de mesma cor e tamanho no produto
        $this->createIndex(
            'unq_produto_variantes_cor_tamanho',
            'prest_produto_variantes',
            ['produto_id', 'cor', 'tamanho'],
            true
        );

        // Índices para performance em PDV e relatórios
        $this->createIndex('idx_produto_variantes_produto_id', 'prest_produto_variantes', 'produto_id');
        $this->createIndex('idx_produto_variantes_codigo_barras', 'prest_produto_variantes', 'codigo_barras');
        $this->createIndex('idx_produto_variantes_codigo_ref', 'prest_produto_variantes', 'codigo_referencia');

        // 2. Extensão não destrutiva da tabela prest_produto_fotos
        $this->addColumn('prest_produto_fotos', 'cor', 'VARCHAR(50) NULL');
        $this->addColumn('prest_produto_fotos', 'variante_id', 'UUID NULL');

        $this->addForeignKey(
            'fk_produto_fotos_variante',
            'prest_produto_fotos',
            'variante_id',
            'prest_produto_variantes',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->createIndex('idx_produto_fotos_cor', 'prest_produto_fotos', ['produto_id', 'cor']);

        // 3. Adiciona modo_grade em prest_produtos
        $this->addColumn('prest_produtos', 'modo_grade', "VARCHAR(20) NOT NULL DEFAULT 'legado'");
        $this->createIndex('idx_produtos_modo_grade', 'prest_produtos', 'modo_grade');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx_produtos_modo_grade', 'prest_produtos');
        $this->dropColumn('prest_produtos', 'modo_grade');

        $this->dropForeignKey('fk_produto_fotos_variante', 'prest_produto_fotos');
        $this->dropIndex('idx_produto_fotos_cor', 'prest_produto_fotos');
        $this->dropColumn('prest_produto_fotos', 'variante_id');
        $this->dropColumn('prest_produto_fotos', 'cor');

        $this->dropTable('prest_produto_variantes');
    }
}
