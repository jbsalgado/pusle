<?php

use yii\db\Migration;

/**
 * Adiciona suporte a código de balança (PLU), pesagem no PDV e configurações de balança/etiqueta.
 */
class m261116_100500_add_pdv_balanca_support extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->schema->getTableSchema('prest_produtos', true);
        if ($tableSchema) {
            if (!isset($tableSchema->columns['codigo_balanca'])) {
                $this->addColumn('prest_produtos', 'codigo_balanca', $this->string(20)->null()->comment('Código PLU na balança'));
            }
            if (!isset($tableSchema->columns['balanca_pesavel'])) {
                $this->addColumn('prest_produtos', 'balanca_pesavel', $this->boolean()->notNull()->defaultValue(false)->comment('Produto pesável em balança no checkout'));
            }
            if (!isset($tableSchema->columns['tara_padrao'])) {
                $this->addColumn('prest_produtos', 'tara_padrao', $this->decimal(10, 3)->notNull()->defaultValue(0)->comment('Tara padrão da embalagem em kg'));
            }
        }

        if ($tableSchema && isset($tableSchema->columns['codigo_barras'])) {
            $this->execute("CREATE INDEX IF NOT EXISTS idx_prest_produtos_usuario_ean ON prest_produtos (usuario_id, codigo_barras) WHERE codigo_barras IS NOT NULL");
        }
        $this->execute("CREATE INDEX IF NOT EXISTS idx_prest_produtos_usuario_balanca ON prest_produtos (usuario_id, codigo_balanca) WHERE codigo_balanca IS NOT NULL");

        if ($this->db->schema->getTableSchema('prest_pdv_configuracoes', true) === null) {
            $this->createTable('prest_pdv_configuracoes', [
                'id' => 'UUID PRIMARY KEY DEFAULT gen_random_uuid()',
                'usuario_id' => 'UUID NOT NULL UNIQUE',
                'etiqueta_prefixo' => $this->string(5)->notNull()->defaultValue('2'),
                'etiqueta_tamanho_codigo' => $this->integer()->notNull()->defaultValue(5),
                'etiqueta_tipo_valor' => $this->string(10)->notNull()->defaultValue('PESO'),
                'balanca_protocolo' => $this->string(20)->notNull()->defaultValue('TOLEDO'),
                'balanca_baud_rate' => $this->integer()->notNull()->defaultValue(9600),
                'imprimir_cupom_auto' => $this->boolean()->notNull()->defaultValue(false),
                'som_leitor_ativo' => $this->boolean()->notNull()->defaultValue(true),
                'data_atualizacao' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            ]);
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('prest_pdv_configuracoes', true) !== null) {
            $this->dropTable('prest_pdv_configuracoes');
        }
        $this->execute("DROP INDEX IF EXISTS idx_prest_produtos_usuario_balanca");
        $this->execute("DROP INDEX IF EXISTS idx_prest_produtos_usuario_ean");

        $tableSchema = $this->db->schema->getTableSchema('prest_produtos', true);
        if ($tableSchema) {
            if (isset($tableSchema->columns['tara_padrao'])) {
                $this->dropColumn('prest_produtos', 'tara_padrao');
            }
            if (isset($tableSchema->columns['balanca_pesavel'])) {
                $this->dropColumn('prest_produtos', 'balanca_pesavel');
            }
            if (isset($tableSchema->columns['codigo_balanca'])) {
                $this->dropColumn('prest_produtos', 'codigo_balanca');
            }
        }
    }
}

