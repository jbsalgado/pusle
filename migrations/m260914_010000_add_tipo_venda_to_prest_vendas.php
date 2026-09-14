<?php

use yii\db\Migration;

/**
 * Class m260914_010000_add_tipo_venda_to_prest_vendas
 * 
 * Adiciona a coluna tipo_venda na tabela prest_vendas para isolar definitivamente
 * vendas do tipo Prestanista (crediário ambulante de porta em porta) das vendas
 * de PDV balcão, catálogo online PWA e comandas/mesas.
 */
class m260914_010000_add_tipo_venda_to_prest_vendas extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = '{{%prest_vendas}}';
        $schema = $this->db->getTableSchema($table);

        if ($schema && !isset($schema->columns['tipo_venda'])) {
            $this->addColumn($table, 'tipo_venda', $this->string(30)->notNull()->defaultValue('BALCAO'));
            $this->execute("COMMENT ON COLUMN {$table}.tipo_venda IS 'Canal/Modalidade da venda: PRESTANISTA, BALCAO, CATALOGO_PWA, MESA';");

            $this->createIndex(
                'idx_prest_vendas_tipo_venda',
                $table,
                ['usuario_id', 'tipo_venda', 'status_venda_codigo']
            );

            // Saneamento retroativo dos registros existentes:
            // 1. Identifica como PRESTANISTA vendas com tag explícita ou que possuem parcelas com cobrador atribuído
            $this->execute("
                UPDATE {$table}
                SET tipo_venda = 'PRESTANISTA'
                WHERE observacoes ILIKE '%[PRESTANISTA]%'
                   OR id IN (
                       SELECT DISTINCT venda_id 
                       FROM {{%prest_parcelas}} 
                       WHERE cobrador_id IS NOT NULL
                   );
            ");

            // 2. Identifica pedidos do catálogo online PWA
            $this->execute("
                UPDATE {$table}
                SET tipo_venda = 'CATALOGO_PWA'
                WHERE tipo_venda != 'PRESTANISTA'
                  AND (observacoes ILIKE '%Pedido PWA%' OR observacoes ILIKE '%Orçamento PWA%');
            ");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $table = '{{%prest_vendas}}';
        $schema = $this->db->getTableSchema($table);

        if ($schema && isset($schema->columns['tipo_venda'])) {
            $this->dropIndex('idx_prest_vendas_tipo_venda', $table);
            $this->dropColumn($table, 'tipo_venda');
        }
    }
}
