<?php
/**
 * Migration: Adicionar campos de frete, margem de lucro e markup à tabela prest_produtos
 * 
 * Execute: php yii migrate
 */

use yii\db\Migration;

class m250101_000001_add_frete_margem_markup_to_prest_produtos extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Adicionar coluna valor_frete (valor do frete)
        $this->addColumn('{{%prest_produtos}}', 'valor_frete', $this->decimal(10, 2)->defaultValue(0.00)->notNull()->after('preco_custo'));
        
        // Adicionar coluna margem_lucro_percentual (percentual de margem de lucro)
        // Margem = (Preço de Venda - Custo) / Preço de Venda * 100
        $this->addColumn('{{%prest_produtos}}', 'margem_lucro_percentual', $this->decimal(5, 2)->defaultValue(null)->after('preco_venda_sugerido'));
        
        // Adicionar coluna markup_percentual (percentual de markup)
        // Markup = (Preço de Venda - Custo) / Custo * 100
        $this->addColumn('{{%prest_produtos}}', 'markup_percentual', $this->decimal(5, 2)->defaultValue(null)->after('margem_lucro_percentual'));
        
        // Comentários nas colunas
        $this->addCommentOnColumn('{{%prest_produtos}}', 'valor_frete', 'Valor do frete do produto em R$');
        $this->addCommentOnColumn('{{%prest_produtos}}', 'margem_lucro_percentual', 'Margem de lucro em percentual calculada sobre o preço de venda: ((Preço Venda - Custo) / Preço Venda) * 100');
        $this->addCommentOnColumn('{{%prest_produtos}}', 'markup_percentual', 'Markup em percentual calculado sobre o custo: ((Preço Venda - Custo) / Custo) * 100');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%prest_produtos}}', 'markup_percentual');
        $this->dropColumn('{{%prest_produtos}}', 'margem_lucro_percentual');
        $this->dropColumn('{{%prest_produtos}}', 'valor_frete');
    }
}

