<?php

use yii\db\Migration;

/**
 * Migration: Adiciona suporte SaaS multi-tenant ao sistema
 *
 * Mudanças:
 * 1. Coluna `is_admin`    (boolean) em prest_usuarios — identifica admins do sistema SaaS
 * 2. Coluna `status_loja` (varchar) em prest_usuarios — ciclo de vida: pendente|ativa|suspensa|rejeitada
 * 3. Índice em `status_loja` para buscas rápidas no painel admin
 */
class m260611_000002_add_is_admin_status_loja extends Migration
{
    public function safeUp()
    {
        // -----------------------------------------------------------------
        // 1. Adicionar coluna is_admin em prest_usuarios
        // -----------------------------------------------------------------
        $colunasExistentes = $this->db->getTableSchema('prest_usuarios')->columnNames;

        if (!in_array('is_admin', $colunasExistentes)) {
            $this->addColumn(
                'prest_usuarios',
                'is_admin',
                $this->boolean()->notNull()->defaultValue(false)->comment('TRUE = Administrador do sistema SaaS (acesso ao painel /admin)')
            );
            $this->execute("COMMENT ON COLUMN prest_usuarios.is_admin IS 'TRUE = Administrador do sistema SaaS (acesso ao painel /admin)'");
        } else {
            echo "    > Coluna is_admin já existe. Pulando.\n";
        }

        // -----------------------------------------------------------------
        // 2. Adicionar coluna status_loja em prest_usuarios
        // -----------------------------------------------------------------
        if (!in_array('status_loja', $colunasExistentes)) {
            $this->addColumn(
                'prest_usuarios',
                'status_loja',
                $this->string(20)->notNull()->defaultValue('ativa')
                    ->comment('Ciclo de vida da loja: pendente | ativa | suspensa | rejeitada')
            );
            $this->execute("COMMENT ON COLUMN prest_usuarios.status_loja IS 'Ciclo de vida SaaS: pendente|ativa|suspensa|rejeitada'");
        } else {
            echo "    > Coluna status_loja já existe. Pulando.\n";
        }

        // -----------------------------------------------------------------
        // 3. Índice em status_loja para buscas rápidas
        // -----------------------------------------------------------------
        $indexes = $this->db->createCommand(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'prest_usuarios' AND indexname = 'idx_usuarios_status_loja'"
        )->queryScalar();

        if (!$indexes) {
            $this->createIndex(
                'idx_usuarios_status_loja',
                'prest_usuarios',
                'status_loja'
            );
        } else {
            echo "    > Índice idx_usuarios_status_loja já existe. Pulando.\n";
        }

        // -----------------------------------------------------------------
        // 4. Garantir que o primeiro usuário com eh_dono_loja = true
        //    que existir com CPF do admin tenha is_admin = true
        //    (Ajuste manual necessário pelo operador após a migration)
        // -----------------------------------------------------------------
        echo "\n    [ATENÇÃO] Para tornar um usuário administrador do sistema, execute:\n";
        echo "    UPDATE prest_usuarios SET is_admin = true WHERE email = 'seu-email@aqui.com';\n\n";
    }

    public function safeDown()
    {
        // Remove índice primeiro
        $indexes = $this->db->createCommand(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'prest_usuarios' AND indexname = 'idx_usuarios_status_loja'"
        )->queryScalar();
        if ($indexes) {
            $this->dropIndex('idx_usuarios_status_loja', 'prest_usuarios');
        }

        // Remove colunas
        $colunasExistentes = $this->db->getTableSchema('prest_usuarios')->columnNames;
        if (in_array('status_loja', $colunasExistentes)) {
            $this->dropColumn('prest_usuarios', 'status_loja');
        }
        if (in_array('is_admin', $colunasExistentes)) {
            $this->dropColumn('prest_usuarios', 'is_admin');
        }
    }
}
