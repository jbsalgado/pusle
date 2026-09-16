-- Migração de Frete: Solução Interna por Estado/Faixas de Preço e Integração Melhor Envio

-- 1. Colunas para prest_taxas_entrega
ALTER TABLE public.prest_taxas_entrega
    ADD COLUMN IF NOT EXISTS estado character varying(2),
    ADD COLUMN IF NOT EXISTS faixa_preco_min numeric(10,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS faixa_preco_max numeric(10,2),
    ADD COLUMN IF NOT EXISTS prazo_dias_min integer DEFAULT 1,
    ADD COLUMN IF NOT EXISTS prazo_dias_max integer DEFAULT 5,
    ADD COLUMN IF NOT EXISTS nome_servico character varying(100) DEFAULT 'Entrega Padrão',
    ADD COLUMN IF NOT EXISTS tipo_regra character varying(20) DEFAULT 'ESTADO';

CREATE INDEX IF NOT EXISTS idx_taxas_entrega_estado ON public.prest_taxas_entrega USING btree (usuario_id, estado);
CREATE INDEX IF NOT EXISTS idx_taxas_entrega_faixa_preco ON public.prest_taxas_entrega USING btree (faixa_preco_min, faixa_preco_max);

-- 2. Colunas para loja_configuracao (Melhor Envio)
ALTER TABLE public.loja_configuracao
    ADD COLUMN IF NOT EXISTS melhor_envio_ativo boolean DEFAULT false,
    ADD COLUMN IF NOT EXISTS melhor_envio_ambiente character varying(10) DEFAULT 'production',
    ADD COLUMN IF NOT EXISTS melhor_envio_token text,
    ADD COLUMN IF NOT EXISTS melhor_envio_cep_origem character varying(10),
    ADD COLUMN IF NOT EXISTS melhor_envio_servicos text,
    ADD COLUMN IF NOT EXISTS melhor_envio_acrescimo_dias integer DEFAULT 0,
    ADD COLUMN IF NOT EXISTS melhor_envio_acrescimo_valor numeric(10,2) DEFAULT 0.00;
