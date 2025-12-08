<?php

use yii\db\Migration;

/**
 * Class m250101_000006_fix_plpgsql_trigger_issue
 * 
 * Desabilita triggers que usam plpgsql devido a incompatibilidade de versão
 * O Yii2 TimestampBehavior já cuida da atualização de data_atualizacao
 */
class m250101_000006_fix_plpgsql_trigger_issue extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Lista de tabelas críticas que usam trigger_set_timestamp()
        // O Yii2 TimestampBehavior já cuida da atualização de data_atualizacao
        $tables = [
            'prest_produtos',
            'prest_vendas',
            'prest_clientes',
            'prest_categorias',
            'prest_colaboradores',
            'prest_compras',
            'prest_configuracoes',
            'prest_fornecedores',
            'prest_orcamentos',
            'prest_usuarios',
            'prest_vendedores',
        ];

        foreach ($tables as $table) {
            // Verifica se o trigger existe
            $checkSql = "
                SELECT trigger_name
                FROM information_schema.triggers
                WHERE trigger_schema = 'public'
                AND event_object_table = '{$table}'
                AND action_statement LIKE '%trigger_set_timestamp%'
                LIMIT 1
            ";
            
            $result = $this->db->createCommand($checkSql)->queryOne();
            
            if ($result && !empty($result['trigger_name'])) {
                $triggerName = $result['trigger_name'];
                echo "Desabilitando trigger {$triggerName} na tabela {$table}...\n";
                
                try {
                    // Desabilita o trigger
                    $this->execute("ALTER TABLE public.{$table} DISABLE TRIGGER {$triggerName}");
                    echo "✅ Trigger {$triggerName} da tabela {$table} desabilitado com sucesso.\n";
                } catch (\Exception $e) {
                    echo "⚠️  Erro ao desabilitar trigger na tabela {$table}: " . $e->getMessage() . "\n";
                }
            } else {
                echo "ℹ️  Nenhum trigger set_timestamp encontrado na tabela {$table}.\n";
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Reabilita os triggers
        $tables = [
            'prest_produtos',
            'prest_vendas',
            'prest_clientes',
            'prest_categorias',
            'prest_colaboradores',
            'prest_compras',
            'prest_configuracoes',
            'prest_fornecedores',
            'prest_orcamentos',
            'prest_usuarios',
            'prest_vendedores',
        ];

        foreach ($tables as $table) {
            $checkSql = "
                SELECT trigger_name
                FROM information_schema.triggers
                WHERE trigger_schema = 'public'
                AND event_object_table = '{$table}'
                AND action_statement LIKE '%trigger_set_timestamp%'
                LIMIT 1
            ";
            
            $result = $this->db->createCommand($checkSql)->queryOne();
            
            if ($result && !empty($result['trigger_name'])) {
                $triggerName = $result['trigger_name'];
                try {
                    $this->execute("ALTER TABLE public.{$table} ENABLE TRIGGER {$triggerName}");
                    echo "Reabilitado trigger {$triggerName} na tabela {$table}.\n";
                } catch (\Exception $e) {
                    echo "⚠️  Erro ao reabilitar {$table}.{$triggerName}: " . $e->getMessage() . "\n";
                }
            }
        }
    }
}

