<?php

use yii\db\Migration;

/**
 * Class m250101_000005_replace_uuid_generate_v4_with_gen_random_uuid
 * 
 * Substitui DEFAULT uuid_generate_v4() por gen_random_uuid() nas tabelas
 * para evitar incompatibilidade com versões do PostgreSQL
 */
class m250101_000005_replace_uuid_generate_v4_with_gen_random_uuid extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Lista de tabelas que usam uuid_generate_v4() como DEFAULT
        // Incluindo todas as tabelas prest_* que são críticas para o sistema
        $tables = [
            'prest_vendas' => 'id',
            'prest_venda_itens' => 'id',
            'prest_parcelas' => 'id',
            'prest_usuarios' => 'id',
            'prest_caixa' => 'id',
            'prest_caixa_movimentacoes' => 'id',
            'prest_carteira_cobranca' => 'id',
            'prest_categorias' => 'id',
            'prest_clientes' => 'id',
            'prest_colaboradores' => 'id',
            'prest_comissoes' => 'id',
        ];

        foreach ($tables as $table => $column) {
            // Verifica se a coluna existe e tem o DEFAULT antigo
            $checkSql = "
                SELECT column_default 
                FROM information_schema.columns 
                WHERE table_schema = 'public' 
                AND table_name = '{$table}' 
                AND column_name = '{$column}'
            ";
            
            $result = $this->db->createCommand($checkSql)->queryOne();
            
            if ($result && strpos($result['column_default'], 'uuid_generate_v4') !== false) {
                echo "Alterando DEFAULT da coluna {$column} na tabela {$table}...\n";
                
                // Remove o DEFAULT antigo
                $this->execute("ALTER TABLE public.{$table} ALTER COLUMN {$column} DROP DEFAULT");
                
                // Adiciona o novo DEFAULT usando gen_random_uuid()
                $this->execute("ALTER TABLE public.{$table} ALTER COLUMN {$column} SET DEFAULT gen_random_uuid()");
                
                echo "✅ Coluna {$column} da tabela {$table} atualizada com sucesso.\n";
            } else {
                echo "ℹ️  Coluna {$column} da tabela {$table} não usa uuid_generate_v4() ou não existe.\n";
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Reverter para uuid_generate_v4() (requer extensão uuid-ossp)
        $tables = [
            'prest_vendas' => 'id',
            'prest_venda_itens' => 'id',
            'prest_parcelas' => 'id',
            'prest_usuarios' => 'id',
            'prest_caixa' => 'id',
            'prest_caixa_movimentacoes' => 'id',
            'prest_carteira_cobranca' => 'id',
            'prest_categorias' => 'id',
            'prest_clientes' => 'id',
            'prest_colaboradores' => 'id',
            'prest_comissoes' => 'id',
        ];

        foreach ($tables as $table => $column) {
            try {
                $this->execute("ALTER TABLE public.{$table} ALTER COLUMN {$column} DROP DEFAULT");
                $this->execute("ALTER TABLE public.{$table} ALTER COLUMN {$column} SET DEFAULT public.uuid_generate_v4()");
                echo "Revertido DEFAULT da coluna {$column} na tabela {$table}.\n";
            } catch (\Exception $e) {
                echo "⚠️  Erro ao reverter {$table}.{$column}: " . $e->getMessage() . "\n";
            }
        }
    }
}

