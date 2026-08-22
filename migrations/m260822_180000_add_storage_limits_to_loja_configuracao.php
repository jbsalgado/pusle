<?php

use yii\db\Migration;

/**
 * Migration: Adiciona limites de armazenamento de vídeos e cards em MB na tabela loja_configuracao.
 */
class m260822_180000_add_storage_limits_to_loja_configuracao extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('loja_configuracao');

        if (!isset($tableSchema->columns['limite_armazenamento_videos_mb'])) {
            $this->addColumn('loja_configuracao', 'limite_armazenamento_videos_mb', $this->integer()->notNull()->defaultValue(50));
        }

        if (!isset($tableSchema->columns['limite_armazenamento_cards_mb'])) {
            $this->addColumn('loja_configuracao', 'limite_armazenamento_cards_mb', $this->integer()->notNull()->defaultValue(50));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $tableSchema = $this->db->getTableSchema('loja_configuracao');

        if (isset($tableSchema->columns['limite_armazenamento_videos_mb'])) {
            $this->dropColumn('loja_configuracao', 'limite_armazenamento_videos_mb');
        }

        if (isset($tableSchema->columns['limite_armazenamento_cards_mb'])) {
            $this->dropColumn('loja_configuracao', 'limite_armazenamento_cards_mb');
        }
    }
}
