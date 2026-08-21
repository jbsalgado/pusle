-- ==============================================================================
-- SCRIPT DE RESET DE DADOS OPERACIONAIS E MOVIMENTAÇÕES (NOVO NEGÓCIO)
-- Sistema: Pulse ERP / Alex Bird
-- Data: 2026-08-21
-- ==============================================================================

BEGIN;

-- Trunca tabelas de movimentações e cadastros operacionais
TRUNCATE TABLE 
    public.prest_venda_itens,
    public.prest_parcelas,
    public.prest_vendas,
    public.prest_orcamento_itens,
    public.prest_orcamentos,
    public.orcamento_itens,
    public.orcamentos,
    public.prest_caixa_movimentacoes,
    public.prest_caixa,
    public.prest_cupons_fiscais,
    public.prest_produto_fotos,
    public.prest_produto_cards,
    public.prest_produto_kit_itens,
    public.prest_produtos,
    public.prest_itens_compra,
    public.prest_compras,
    public.prest_fornecedores,
    public.prest_estoque_movimentacoes,
    public.prest_dados_financeiros,
    public.prest_financeiro_mensal,
    public.prest_contas_pagar,
    public.prest_cobranca_historico,
    public.prest_historico_cobranca,
    public.prest_carteira_cobranca,
    public.prest_disparo_itens,
    public.prest_disparos_massa,
    public.prest_comissoes,
    public.prest_clientes,
    public.prest_colaboradores,
    public.prest_vendedores,
    public.delivery_pedido_itens,
    public.delivery_pedido_complementos,
    public.delivery_pedidos,
    public.delivery_movimentacoes_financeiras,
    public.delivery_enderecos_cliente,
    public.delivery_clientes,
    public.delivery_produtos,
    public.servico_pedido_venda_itens,
    public.servico_pedidos_venda,
    public.servico_movimentacoes_estoque,
    public.servico_contas_pagar,
    public.servico_contas_receber
CASCADE;

-- Limpa de prest_usuarios apenas os usuários que NÃO são o Dono da Loja principal
DELETE FROM public.prest_usuarios WHERE eh_dono_loja IS NOT TRUE AND is_admin IS NOT TRUE;

COMMIT;
