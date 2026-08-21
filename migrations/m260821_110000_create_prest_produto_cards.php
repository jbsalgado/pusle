<?php

use yii\db\Migration;

/**
 * Migration: Cria tabela prest_produto_cards para armazenar o histórico de cards gerados para redes sociais.
 */
class m260821_110000_create_prest_produto_cards extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('prest_produto_cards', [
            'id'               => 'UUID PRIMARY KEY DEFAULT gen_random_uuid()',
            'produto_id'       => 'UUID NOT NULL',
            'usuario_id'       => 'UUID NOT NULL',
            'formato'          => "VARCHAR(20) NOT NULL CHECK (formato IN ('feed', 'stories'))",
            'card_path'        => 'VARCHAR(500) NOT NULL',
            'card_url'         => 'VARCHAR(500) NULL',
            'metadata'         => 'JSONB NULL',
            'data_criacao'     => 'TIMESTAMPTZ NOT NULL DEFAULT NOW()',
            'data_atualizacao' => 'TIMESTAMPTZ NOT NULL DEFAULT NOW()',
        ]);

        // FK para prest_produtos
        $this->addForeignKey(
            'fk_produto_cards_produto',
            'prest_produto_cards',
            'produto_id',
            'prest_produtos',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // FK para prest_usuarios
        $this->addForeignKey(
            'fk_produto_cards_usuario',
            'prest_produto_cards',
            'usuario_id',
            'prest_usuarios',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Índices de busca
        $this->createIndex('idx_prest_produto_cards_produto', 'prest_produto_cards', 'produto_id');
        $this->createIndex('idx_prest_produto_cards_usuario', 'prest_produto_cards', 'usuario_id');
        $this->createIndex('idx_prest_produto_cards_formato', 'prest_produto_cards', 'formato');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('prest_produto_cards');
    }
}
