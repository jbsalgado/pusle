<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%prest_disparos_massa}}` and `{{%prest_disparo_itens}}`.
 */
class m260821_120000_create_prest_disparos_massa extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');

        // Tabela principal de campanhas de disparo em massa
        $this->createTable('{{%prest_disparos_massa}}', [
            'id' => 'uuid PRIMARY KEY DEFAULT gen_random_uuid()',
            'usuario_id' => $this->string(36)->notNull(),
            'titulo' => $this->string(255)->notNull(),
            'canais' => 'jsonb NOT NULL DEFAULT \'[]\'::jsonb',
            'configuracoes' => 'jsonb DEFAULT \'{}\'::jsonb',
            'mensagem_texto' => $this->text(),
            'status' => $this->string(30)->notNull()->defaultValue('pendente'),
            'total_itens' => $this->integer()->notNull()->defaultValue(0),
            'itens_enviados' => $this->integer()->notNull()->defaultValue(0),
            'itens_erro' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_disparos_massa_usuario', '{{%prest_disparos_massa}}', 'usuario_id');
        $this->createIndex('idx_disparos_massa_status', '{{%prest_disparos_massa}}', 'status');

        // Tabela de itens individuais do disparo (fila)
        $this->createTable('{{%prest_disparo_itens}}', [
            'id' => 'uuid PRIMARY KEY DEFAULT gen_random_uuid()',
            'disparo_id' => 'uuid NOT NULL',
            'produto_id' => $this->string(36)->notNull(),
            'cliente_id' => $this->string(36)->null(),
            'canal' => $this->string(30)->notNull(), // 'status', 'whatsapp', 'email'
            'destino' => $this->string(255)->null(), // telefone ou email
            'card_path' => $this->string(500)->null(),
            'card_url' => $this->string(500)->null(),
            'mensagem_personalizada' => $this->text(),
            'status' => $this->string(30)->notNull()->defaultValue('pendente'), // 'pendente', 'processando', 'enviado', 'erro'
            'erro_mensagem' => $this->text(),
            'enviado_em' => $this->timestamp()->null(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_disparo_itens_disparo', '{{%prest_disparo_itens}}', 'disparo_id');
        $this->createIndex('idx_disparo_itens_status', '{{%prest_disparo_itens}}', 'status');
        $this->addForeignKey(
            'fk_disparo_itens_disparo',
            '{{%prest_disparo_itens}}',
            'disparo_id',
            '{{%prest_disparos_massa}}',
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
        $this->dropForeignKey('fk_disparo_itens_disparo', '{{%prest_disparo_itens}}');
        $this->dropTable('{{%prest_disparo_itens}}');
        $this->dropTable('{{%prest_disparos_massa}}');
    }
}
