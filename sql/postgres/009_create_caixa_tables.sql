-- ===================================================================
-- MIGRATION: Criação das tabelas de Fluxo de Caixa
-- Data: 2025-12-07
-- Descrição: Cria tabelas prest_caixa e prest_caixa_movimentacoes
-- ===================================================================

-- Tabela: prest_caixa
-- Armazena informações sobre abertura e fechamento de caixa
CREATE TABLE IF NOT EXISTS public.prest_caixa (
    id UUID DEFAULT public.uuid_generate_v4() NOT NULL,
    usuario_id UUID NOT NULL,
    colaborador_id UUID,
    data_abertura TIMESTAMP WITH TIME ZONE DEFAULT NOW() NOT NULL,
    data_fechamento TIMESTAMP WITH TIME ZONE,
    valor_inicial NUMERIC(10,2) DEFAULT 0 NOT NULL,
    valor_final NUMERIC(10,2),
    valor_esperado NUMERIC(10,2),
    diferenca NUMERIC(10,2),
    status VARCHAR(20) DEFAULT 'ABERTO' NOT NULL,
    observacoes TEXT,
    data_criacao TIMESTAMP WITH TIME ZONE DEFAULT NOW() NOT NULL,
    data_atualizacao TIMESTAMP WITH TIME ZONE DEFAULT NOW() NOT NULL,
    
    CONSTRAINT prest_caixa_pkey PRIMARY KEY (id),
    CONSTRAINT prest_caixa_usuario_id_fkey FOREIGN KEY (usuario_id) 
        REFERENCES public.prest_usuarios(id) ON DELETE CASCADE,
    CONSTRAINT prest_caixa_colaborador_id_fkey FOREIGN KEY (colaborador_id) 
        REFERENCES public.prest_colaboradores(id) ON DELETE SET NULL,
    CONSTRAINT prest_caixa_status_check CHECK (status IN ('ABERTO', 'FECHADO', 'CANCELADO')),
    CONSTRAINT prest_caixa_valor_inicial_check CHECK (valor_inicial >= 0)
);

COMMENT ON TABLE public.prest_caixa IS 'Registra abertura e fechamento de caixa';
COMMENT ON COLUMN public.prest_caixa.valor_inicial IS 'Valor inicial do caixa na abertura';
COMMENT ON COLUMN public.prest_caixa.valor_final IS 'Valor final do caixa no fechamento';
COMMENT ON COLUMN public.prest_caixa.valor_esperado IS 'Valor esperado calculado (inicial + entradas - saídas)';
COMMENT ON COLUMN public.prest_caixa.diferenca IS 'Diferença entre valor final e valor esperado';

-- Índices
CREATE INDEX IF NOT EXISTS idx_prest_caixa_usuario_id ON public.prest_caixa(usuario_id);
CREATE INDEX IF NOT EXISTS idx_prest_caixa_colaborador_id ON public.prest_caixa(colaborador_id);
CREATE INDEX IF NOT EXISTS idx_prest_caixa_status ON public.prest_caixa(status);
CREATE INDEX IF NOT EXISTS idx_prest_caixa_data_abertura ON public.prest_caixa(data_abertura);

-- ===================================================================

-- Tabela: prest_caixa_movimentacoes
-- Armazena todas as movimentações (entradas e saídas) do caixa
CREATE TABLE IF NOT EXISTS public.prest_caixa_movimentacoes (
    id UUID DEFAULT public.uuid_generate_v4() NOT NULL,
    caixa_id UUID NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    categoria VARCHAR(50),
    valor NUMERIC(10,2) NOT NULL,
    descricao TEXT NOT NULL,
    forma_pagamento_id UUID,
    venda_id UUID,
    parcela_id UUID,
    conta_pagar_id UUID,
    data_movimento TIMESTAMP WITH TIME ZONE DEFAULT NOW() NOT NULL,
    observacoes TEXT,
    data_criacao TIMESTAMP WITH TIME ZONE DEFAULT NOW() NOT NULL,
    
    CONSTRAINT prest_caixa_movimentacoes_pkey PRIMARY KEY (id),
    CONSTRAINT prest_caixa_movimentacoes_caixa_id_fkey FOREIGN KEY (caixa_id) 
        REFERENCES public.prest_caixa(id) ON DELETE CASCADE,
    CONSTRAINT prest_caixa_movimentacoes_forma_pagamento_id_fkey FOREIGN KEY (forma_pagamento_id) 
        REFERENCES public.prest_formas_pagamento(id) ON DELETE SET NULL,
    CONSTRAINT prest_caixa_movimentacoes_venda_id_fkey FOREIGN KEY (venda_id) 
        REFERENCES public.prest_vendas(id) ON DELETE SET NULL,
    CONSTRAINT prest_caixa_movimentacoes_parcela_id_fkey FOREIGN KEY (parcela_id) 
        REFERENCES public.prest_parcelas(id) ON DELETE SET NULL,
    CONSTRAINT prest_caixa_movimentacoes_tipo_check CHECK (tipo IN ('ENTRADA', 'SAIDA')),
    CONSTRAINT prest_caixa_movimentacoes_valor_check CHECK (valor > 0)
);

COMMENT ON TABLE public.prest_caixa_movimentacoes IS 'Registra todas as movimentações de entrada e saída do caixa';
COMMENT ON COLUMN public.prest_caixa_movimentacoes.tipo IS 'ENTRADA ou SAIDA';
COMMENT ON COLUMN public.prest_caixa_movimentacoes.categoria IS 'Categoria da movimentação (ex: VENDA, PAGAMENTO, SUPRIMENTO, SANGRIA)';
COMMENT ON COLUMN public.prest_caixa_movimentacoes.venda_id IS 'Referência à venda (se a movimentação for relacionada a uma venda)';
COMMENT ON COLUMN public.prest_caixa_movimentacoes.parcela_id IS 'Referência à parcela (se a movimentação for relacionada a um pagamento de parcela)';
COMMENT ON COLUMN public.prest_caixa_movimentacoes.conta_pagar_id IS 'Referência à conta a pagar (se a movimentação for relacionada a um pagamento)';

-- Índices
CREATE INDEX IF NOT EXISTS idx_prest_caixa_movimentacoes_caixa_id ON public.prest_caixa_movimentacoes(caixa_id);
CREATE INDEX IF NOT EXISTS idx_prest_caixa_movimentacoes_tipo ON public.prest_caixa_movimentacoes(tipo);
CREATE INDEX IF NOT EXISTS idx_prest_caixa_movimentacoes_data_movimento ON public.prest_caixa_movimentacoes(data_movimento);
CREATE INDEX IF NOT EXISTS idx_prest_caixa_movimentacoes_venda_id ON public.prest_caixa_movimentacoes(venda_id);
CREATE INDEX IF NOT EXISTS idx_prest_caixa_movimentacoes_parcela_id ON public.prest_caixa_movimentacoes(parcela_id);

