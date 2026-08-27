<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%prest_encartes}}` and `{{%prest_encarte_produtos}}`.
 */
class m260827_120000_create_prest_encartes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');

        // Tabela principal de encartes/catálogos digitais
        $this->createTable('{{%prest_encartes}}', [
            'id' => 'uuid PRIMARY KEY DEFAULT gen_random_uuid()',
            'usuario_id' => $this->string(36)->notNull(),
            'titulo' => $this->string(255)->notNull(),
            'subtitulo' => $this->string(255)->null(),
            'token_publico' => $this->string(64)->notNull(),
            'estilo_layout' => $this->string(50)->notNull()->defaultValue('flipsnack_supermarket'),
            'produtos_por_pagina' => $this->integer()->notNull()->defaultValue(6),
            'cor_tema' => $this->string(50)->notNull()->defaultValue('red_gold'),
            'status' => $this->string(20)->notNull()->defaultValue('ativo'),
            'visualizacoes_count' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_encartes_usuario', '{{%prest_encartes}}', 'usuario_id');
        $this->createIndex('idx_encartes_token_publico', '{{%prest_encartes}}', 'token_publico', true);

        // Tabela de relacionamento encarte <-> produtos
        $this->createTable('{{%prest_encarte_produtos}}', [
            'id' => 'uuid PRIMARY KEY DEFAULT gen_random_uuid()',
            'encarte_id' => 'uuid NOT NULL',
            'produto_id' => $this->string(36)->notNull(),
            'preco_oferta' => $this->decimal(10, 2)->null(),
            'ordem' => $this->integer()->notNull()->defaultValue(0),
            'destaque' => $this->boolean()->notNull()->defaultValue(false),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_encarte_produtos_encarte', '{{%prest_encarte_produtos}}', 'encarte_id');
        $this->createIndex('idx_encarte_produtos_produto', '{{%prest_encarte_produtos}}', 'produto_id');

        $this->addForeignKey(
            'fk_encarte_produtos_encarte',
            '{{%prest_encarte_produtos}}',
            'encarte_id',
            '{{%prest_encartes}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_encarte_produtos_encarte', '{{%prest_encarte_produtos}}');
        $this->dropTable('{{%prest_encarte_produtos}}');
        $this->dropTable('{{%prest_encartes}}');
    }
}
