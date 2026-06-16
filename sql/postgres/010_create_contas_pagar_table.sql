-- ===================================================================
-- MIGRATION: Criação da tabela de Contas a Pagar
-- Data: 2025-12-07
-- Descrição: Cria tabela prest_contas_pagar
-- ===================================================================

-- Tabela: prest_contas_pagar
-- Armazena contas a pagar da empresa
CREATE TABLE IF NOT EXISTS public.prest_contas_pagar (
    id UUID DEFAULT public.uuid_generate_v4() NOT NULL,
    usuario_id UUID NOT NULL,
    fornecedor_id UUID,
    compra_id UUID,
    descricao VARCHAR(255) NOT NULL,
    valor NUMERIC(10,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE,
    status VARCHAR(20) DEFAULT 'PENDENTE' NOT NULL,
    forma_pagamento_id UUID,
    observacoes TEXT,
    data_criacao TIMESTAMP WITH TIME ZONE DEFAULT NOW() NOT NULL,
    data_atualizacao TIMESTAMP WITH TIME ZONE DEFAULT NOW() NOT NULL,
    
    CONSTRAINT prest_contas_pagar_pkey PRIMARY KEY (id),
    CONSTRAINT prest_contas_pagar_usuario_id_fkey FOREIGN KEY (usuario_id) 
        REFERENCES public.prest_usuarios(id) ON DELETE CASCADE,
    CONSTRAINT prest_contas_pagar_fornecedor_id_fkey FOREIGN KEY (fornecedor_id) 
        REFERENCES public.prest_fornecedores(id) ON DELETE SET NULL,
    CONSTRAINT prest_contas_pagar_compra_id_fkey FOREIGN KEY (compra_id) 
        REFERENCES public.prest_compras(id) ON DELETE SET NULL,
    CONSTRAINT prest_contas_pagar_forma_pagamento_id_fkey FOREIGN KEY (forma_pagamento_id) 
        REFERENCES public.prest_formas_pagamento(id) ON DELETE SET NULL,
    CONSTRAINT prest_contas_pagar_status_check CHECK (status IN ('PENDENTE', 'PAGA', 'VENCIDA', 'CANCELADA')),
    CONSTRAINT prest_contas_pagar_valor_check CHECK (valor > 0)
);

COMMENT ON TABLE public.prest_contas_pagar IS 'Registra contas a pagar da empresa';
COMMENT ON COLUMN public.prest_contas_pagar.fornecedor_id IS 'Fornecedor relacionado (opcional)';
COMMENT ON COLUMN public.prest_contas_pagar.compra_id IS 'Compra relacionada (se a conta foi gerada a partir de uma compra)';
COMMENT ON COLUMN public.prest_contas_pagar.status IS 'PENDENTE, PAGA, VENCIDA ou CANCELADA';

-- Índices
CREATE INDEX IF NOT EXISTS idx_prest_contas_pagar_usuario_id ON public.prest_contas_pagar(usuario_id);
CREATE INDEX IF NOT EXISTS idx_prest_contas_pagar_fornecedor_id ON public.prest_contas_pagar(fornecedor_id);
CREATE INDEX IF NOT EXISTS idx_prest_contas_pagar_compra_id ON public.prest_contas_pagar(compra_id);
CREATE INDEX IF NOT EXISTS idx_prest_contas_pagar_status ON public.prest_contas_pagar(status);
CREATE INDEX IF NOT EXISTS idx_prest_contas_pagar_data_vencimento ON public.prest_contas_pagar(data_vencimento);
CREATE INDEX IF NOT EXISTS idx_prest_contas_pagar_data_pagamento ON public.prest_contas_pagar(data_pagamento);

