<?php

use yii\db\Migration;

/**
 * Migration: Cria tabela prest_trilhas_sonoras para gerenciamento de músicas e efeitos especiais nos vídeos.
 */
class m260822_000003_create_prest_trilhas_sonoras extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Cria a tabela se ainda não existir
        if ($this->db->getTableSchema('prest_trilhas_sonoras') === null) {
            $this->createTable('prest_trilhas_sonoras', [
                'id'            => 'UUID PRIMARY KEY DEFAULT gen_random_uuid()',
                'usuario_id'    => 'UUID NOT NULL',
                'titulo'        => 'VARCHAR(255) NOT NULL',
                'descricao'     => 'TEXT NULL',
                'arquivo_nome'  => 'VARCHAR(255) NOT NULL',
                'arquivo_path'  => 'VARCHAR(500) NOT NULL',
                'formato'       => 'VARCHAR(10) NULL',
                'tamanho_bytes' => 'BIGINT NULL',
                'tipo'          => "VARCHAR(30) NOT NULL DEFAULT 'musica'",
                'ativo'         => 'BOOLEAN NOT NULL DEFAULT true',
                'created_at'    => 'TIMESTAMPTZ NOT NULL DEFAULT NOW()',
                'updated_at'    => 'TIMESTAMPTZ NOT NULL DEFAULT NOW()',
            ]);

            // FK para prest_usuarios
            $this->addForeignKey(
                'fk_trilhas_sonoras_usuario',
                'prest_trilhas_sonoras',
                'usuario_id',
                'prest_usuarios',
                'id',
                'CASCADE',
                'CASCADE'
            );

            // Índices de busca
            $this->createIndex('idx_prest_trilhas_sonoras_usuario', 'prest_trilhas_sonoras', 'usuario_id');
            $this->createIndex('idx_prest_trilhas_sonoras_tipo', 'prest_trilhas_sonoras', 'tipo');
            $this->createIndex('idx_prest_trilhas_sonoras_ativo', 'prest_trilhas_sonoras', 'ativo');
        } else {
            // Se a tabela já existir, adiciona o campo tipo caso não exista
            $table = $this->db->getTableSchema('prest_trilhas_sonoras');
            if (!isset($table->columns['tipo'])) {
                $this->addColumn('prest_trilhas_sonoras', 'tipo', "VARCHAR(30) NOT NULL DEFAULT 'musica'");
                $this->createIndex('idx_prest_trilhas_sonoras_tipo', 'prest_trilhas_sonoras', 'tipo');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('prest_trilhas_sonoras');
    }
}
