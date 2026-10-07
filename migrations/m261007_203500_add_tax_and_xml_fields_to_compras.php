<?php

use yii\db\Migration;

/**
 * Migration para adicionar campos de impostos, despesas e dados de importação de XML
 * nas tabelas prest_compras e prest_itens_compra.
 */
class m261007_203500_add_tax_and_xml_fields_to_compras extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Campos em prest_compras (Totais e Impostos da Nota Fiscal)
        $this->addColumn('prest_compras', 'valor_produtos', $this->decimal(15, 2)->defaultValue(0));
        $this->addColumn('prest_compras', 'valor_seguro', $this->decimal(15, 2)->defaultValue(0));
        $this->addColumn('prest_compras', 'valor_outras_despesas', $this->decimal(15, 2)->defaultValue(0));
        $this->addColumn('prest_compras', 'valor_ipi', $this->decimal(15, 2)->defaultValue(0));
        $this->addColumn('prest_compras', 'valor_icms_st', $this->decimal(15, 2)->defaultValue(0));
        $this->addColumn('prest_compras', 'valor_fcp_st', $this->decimal(15, 2)->defaultValue(0));
        $this->addColumn('prest_compras', 'valor_icms', $this->decimal(15, 2)->defaultValue(0));
        $this->addColumn('prest_compras', 'valor_base_icms', $this->decimal(15, 2)->defaultValue(0));
        $this->addColumn('prest_compras', 'valor_pis', $this->decimal(15, 2)->defaultValue(0));
        $this->addColumn('prest_compras', 'valor_cofins', $this->decimal(15, 2)->defaultValue(0));
        $this->addColumn('prest_compras', 'chave_acesso', $this->string(44)->null());

        // Inicializa valor_produtos para compras existentes se estiver zerado
        $this->execute("UPDATE prest_compras SET valor_produtos = COALESCE((
            SELECT SUM(valor_total_item) FROM prest_itens_compra WHERE compra_id = prest_compras.id
        ), valor_total) WHERE valor_produtos IS NULL OR valor_produtos = 0;");

        // 2. Campos em prest_itens_compra (Rateios, Tributos do Item e Marca)
        $this->addColumn('prest_itens_compra', 'marca', $this->string(100)->null());
        $this->addColumn('prest_itens_compra', 'ncm', $this->string(10)->null());
        $this->addColumn('prest_itens_compra', 'cfop', $this->string(10)->null());
        $this->addColumn('prest_itens_compra', 'valor_desconto', $this->decimal(15, 2)->defaultValue(0));
        $this->addColumn('prest_itens_compra', 'valor_frete', $this->decimal(15, 2)->defaultValue(0));
        $this->addColumn('prest_itens_compra', 'valor_seguro', $this->decimal(15, 2)->defaultValue(0));
        $this->addColumn('prest_itens_compra', 'valor_outras_despesas', $this->decimal(15, 2)->defaultValue(0));
        $this->addColumn('prest_itens_compra', 'valor_ipi', $this->decimal(15, 2)->defaultValue(0));
        $this->addColumn('prest_itens_compra', 'valor_icms_st', $this->decimal(15, 2)->defaultValue(0));
        $this->addColumn('prest_itens_compra', 'custo_unitario_real', $this->decimal(15, 5)->defaultValue(0));

        // Inicializa custo_unitario_real com preco_unitario para itens existentes
        $this->execute("UPDATE prest_itens_compra SET custo_unitario_real = preco_unitario WHERE custo_unitario_real IS NULL OR custo_unitario_real = 0;");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Remove campos de prest_itens_compra
        $this->dropColumn('prest_itens_compra', 'custo_unitario_real');
        $this->dropColumn('prest_itens_compra', 'valor_icms_st');
        $this->dropColumn('prest_itens_compra', 'valor_ipi');
        $this->dropColumn('prest_itens_compra', 'valor_outras_despesas');
        $this->dropColumn('prest_itens_compra', 'valor_seguro');
        $this->dropColumn('prest_itens_compra', 'valor_frete');
        $this->dropColumn('prest_itens_compra', 'valor_desconto');
        $this->dropColumn('prest_itens_compra', 'cfop');
        $this->dropColumn('prest_itens_compra', 'ncm');
        $this->dropColumn('prest_itens_compra', 'marca');

        // Remove campos de prest_compras
        $this->dropColumn('prest_compras', 'chave_acesso');
        $this->dropColumn('prest_compras', 'valor_cofins');
        $this->dropColumn('prest_compras', 'valor_pis');
        $this->dropColumn('prest_compras', 'valor_base_icms');
        $this->dropColumn('prest_compras', 'valor_icms');
        $this->dropColumn('prest_compras', 'valor_fcp_st');
        $this->dropColumn('prest_compras', 'valor_icms_st');
        $this->dropColumn('prest_compras', 'valor_ipi');
        $this->dropColumn('prest_compras', 'valor_outras_despesas');
        $this->dropColumn('prest_compras', 'valor_seguro');
        $this->dropColumn('prest_compras', 'valor_produtos');
    }
}
