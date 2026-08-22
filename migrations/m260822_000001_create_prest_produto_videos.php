<?php

use yii\db\Migration;

/**
 * Migration: Cria tabela prest_produto_videos para armazenar o histórico e status de vídeos promocionais.
 */
class m260822_000001_create_prest_produto_videos extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('prest_produto_videos', [
            'id'               => 'UUID PRIMARY KEY DEFAULT gen_random_uuid()',
            'produto_id'       => 'UUID NOT NULL',
            'usuario_id'       => 'UUID NOT NULL',
            'duracao'          => 'INT NOT NULL DEFAULT 15',
            'formato'          => "VARCHAR(20) NOT NULL DEFAULT 'stories'",
            'status'           => "VARCHAR(20) NOT NULL DEFAULT 'pendente'",
            'video_path'       => 'VARCHAR(500) NULL',
            'video_url'        => 'VARCHAR(500) NULL',
            'erro_mensagem'    => 'TEXT NULL',
            'metadata'         => 'JSONB NULL',
            'data_criacao'     => 'TIMESTAMPTZ NOT NULL DEFAULT NOW()',
            'data_atualizacao' => 'TIMESTAMPTZ NOT NULL DEFAULT NOW()',
        ]);

        // FK para prest_produtos
        $this->addForeignKey(
            'fk_produto_videos_produto',
            'prest_produto_videos',
            'produto_id',
            'prest_produtos',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // FK para prest_usuarios
        $this->addForeignKey(
            'fk_produto_videos_usuario',
            'prest_produto_videos',
            'usuario_id',
            'prest_usuarios',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Índices de busca
        $this->createIndex('idx_prest_produto_videos_produto', 'prest_produto_videos', 'produto_id');
        $this->createIndex('idx_prest_produto_videos_usuario', 'prest_produto_videos', 'usuario_id');
        $this->createIndex('idx_prest_produto_videos_status', 'prest_produto_videos', 'status');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('prest_produto_videos');
    }
}
