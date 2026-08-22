<?php

use yii\db\Migration;

/**
 * Migration: Cria a tabela `queue` para o driver de Banco de Dados do yii2-queue.
 */
class m260822_000002_create_queue_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('queue', [
            'id'          => $this->primaryKey(),
            'channel'     => $this->string()->notNull(),
            'job'         => $this->binary()->notNull(),
            'pushed_at'   => $this->integer()->notNull(),
            'ttr'         => $this->integer()->notNull(),
            'delay'       => $this->integer()->notNull()->defaultValue(0),
            'priority'    => $this->integer()->unsigned()->notNull()->defaultValue(1024),
            'reserved_at' => $this->integer()->null(),
            'attempt'     => $this->integer()->null(),
            'done_at'     => $this->integer()->null(),
        ]);

        $this->createIndex('idx_queue_channel', 'queue', 'channel');
        $this->createIndex('idx_queue_reserved_at', 'queue', 'reserved_at');
        $this->createIndex('idx_queue_priority', 'queue', 'priority');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('queue');
    }
}
