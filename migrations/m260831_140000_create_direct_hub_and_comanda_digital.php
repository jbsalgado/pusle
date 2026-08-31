<?php

use yii\db\Migration;

/**
 * Migration: Adiciona suporte a Direct Hub, Comanda Digital e Web Push para Clientes
 */
class m260831_140000_create_direct_hub_and_comanda_digital extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Adiciona magic_token e push_subscriptions em prest_clientes
        $this->execute("
            ALTER TABLE prest_clientes
            ADD COLUMN IF NOT EXISTS magic_token VARCHAR(64) NULL,
            ADD COLUMN IF NOT EXISTS push_subscriptions JSONB NULL;
        ");

        $this->execute("
            CREATE UNIQUE INDEX IF NOT EXISTS uq_prest_clientes_magic_token ON prest_clientes(magic_token) WHERE magic_token IS NOT NULL;
        ");

        // 2. Cria tabela prest_cliente_inbox para mensagens, vídeos, ofertas e status de comanda
        $this->execute("
            CREATE TABLE IF NOT EXISTS prest_cliente_inbox (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                usuario_id UUID NOT NULL REFERENCES prest_usuarios(id) ON DELETE CASCADE,
                cliente_id UUID NULL REFERENCES prest_clientes(id) ON DELETE CASCADE,
                mesa_id UUID NULL REFERENCES prest_mesas(id) ON DELETE SET NULL,
                comanda_id UUID NULL REFERENCES prest_comandas(id) ON DELETE SET NULL,
                tipo VARCHAR(30) NOT NULL DEFAULT 'texto', -- texto, video, card, status_pedido, conta, chamado
                titulo VARCHAR(255) NULL,
                conteudo_texto TEXT NULL,
                midia_url TEXT NULL,
                acoes_json JSONB NULL,
                lido BOOLEAN NOT NULL DEFAULT FALSE,
                created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $this->execute("CREATE INDEX IF NOT EXISTS idx_cliente_inbox_usuario ON prest_cliente_inbox(usuario_id);");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_cliente_inbox_cliente ON prest_cliente_inbox(cliente_id);");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_cliente_inbox_mesa ON prest_cliente_inbox(mesa_id);");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_cliente_inbox_comanda ON prest_cliente_inbox(comanda_id);");

        // 3. Preenche magic_token para clientes existentes que ainda não possuem
        $this->execute("
            UPDATE prest_clientes 
            SET magic_token = md5(id::text || usuario_id::text || random()::text)
            WHERE magic_token IS NULL;
        ");

        echo "✅ Estrutura para Direct Hub e Comanda Digital criada com sucesso!\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute("DROP TABLE IF EXISTS prest_cliente_inbox CASCADE;");
        $this->execute("ALTER TABLE prest_clientes DROP COLUMN IF EXISTS push_subscriptions;");
        $this->execute("ALTER TABLE prest_clientes DROP COLUMN IF EXISTS magic_token;");
    }
}
