<?php

use yii\db\Migration;

/**
 * Class m260831_170000_create_prest_cliente_inbox
 */
class m260831_170000_create_prest_cliente_inbox extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableSchema = $this->db->schema->getTableSchema('prest_cliente_inbox');
        if (!$tableSchema) {
            $this->createTable('prest_cliente_inbox', [
                'id' => 'uuid DEFAULT gen_random_uuid() NOT NULL PRIMARY KEY',
                'usuario_id' => 'uuid NOT NULL',
                'cliente_id' => 'uuid',
                'mesa_id' => 'uuid',
                'comanda_id' => 'uuid',
                'tipo' => $this->string(30)->notNull()->defaultValue('texto'),
                'titulo' => $this->string(255),
                'conteudo_texto' => $this->text(),
                'midia_url' => $this->text(),
                'acoes_json' => 'jsonb',
                'lido' => $this->boolean()->notNull()->defaultValue(false),
                'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->notNull(),
            ]);

            $this->createIndex('idx_cliente_inbox_usuario', 'prest_cliente_inbox', 'usuario_id');
            $this->createIndex('idx_cliente_inbox_mesa', 'prest_cliente_inbox', 'mesa_id');
            $this->createIndex('idx_cliente_inbox_comanda', 'prest_cliente_inbox', 'comanda_id');
            $this->createIndex('idx_cliente_inbox_cliente', 'prest_cliente_inbox', 'cliente_id');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $tableSchema = $this->db->schema->getTableSchema('prest_cliente_inbox');
        if ($tableSchema) {
            $this->dropTable('prest_cliente_inbox');
        }
    }
}
