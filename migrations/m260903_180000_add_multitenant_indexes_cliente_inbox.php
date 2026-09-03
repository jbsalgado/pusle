<?php

use yii\db\Migration;

/**
 * Class m260903_180000_add_multitenant_indexes_cliente_inbox
 * Cria índices compostos de alta performance para a arquitetura multi-tenant do ClienteInbox
 */
class m260903_180000_add_multitenant_indexes_cliente_inbox extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("CREATE INDEX IF NOT EXISTS idx_cliente_inbox_tenant_created ON prest_cliente_inbox (usuario_id, created_at DESC);");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_cliente_inbox_tenant_nao_lidos ON prest_cliente_inbox (usuario_id) WHERE lido = FALSE;");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute("DROP INDEX IF EXISTS idx_cliente_inbox_tenant_created;");
        $this->execute("DROP INDEX IF EXISTS idx_cliente_inbox_tenant_nao_lidos;");
    }
}
