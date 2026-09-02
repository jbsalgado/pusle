<?php

use yii\db\Migration;

/**
 * Migration para alterar preco_unitario em prest_itens_compra e preco_custo em prest_produtos para NUMERIC(15,5).
 */
class m260901_130000_alter_preco_unitario_to_5_decimals extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("DROP VIEW IF EXISTS public.vw_historico_compras_produto CASCADE;");
        $this->alterColumn('prest_itens_compra', 'preco_unitario', 'NUMERIC(15,5)');
        $this->alterColumn('prest_produtos', 'preco_custo', 'NUMERIC(15,5)');

        $sqlView = "CREATE OR REPLACE VIEW public.vw_historico_compras_produto AS
        SELECT 
            ic.produto_id,
            p.nome AS nome_produto,
            ic.compra_id,
            c.data_compra,
            c.fornecedor_id,
            f.nome_fantasia AS nome_fornecedor,
            ic.preco_unitario,
            ic.quantidade,
            ic.valor_total_item,
            c.numero_nota_fiscal,
            c.status_compra,
            ROW_NUMBER() OVER (
                PARTITION BY ic.produto_id, c.fornecedor_id 
                ORDER BY c.data_compra DESC
            ) AS ordem_compra_fornecedor,
            ROW_NUMBER() OVER (
                PARTITION BY ic.produto_id 
                ORDER BY c.data_compra DESC
            ) AS ordem_compra_geral
        FROM public.prest_itens_compra ic
        INNER JOIN public.prest_compras c ON ic.compra_id = c.id
        INNER JOIN public.prest_produtos p ON ic.produto_id = p.id
        INNER JOIN public.prest_fornecedores f ON c.fornecedor_id = f.id
        WHERE c.status_compra != 'CANCELADA'
        ORDER BY ic.produto_id, c.data_compra DESC;";

        $this->execute($sqlView);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute("DROP VIEW IF EXISTS public.vw_historico_compras_produto CASCADE;");
        $this->alterColumn('prest_itens_compra', 'preco_unitario', 'NUMERIC(10,2)');
        $this->alterColumn('prest_produtos', 'preco_custo', 'NUMERIC(10,2)');
    }
}
