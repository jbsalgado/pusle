-- Migration: Criação de tabelas para gestão de fornecedores e compras/resuprimentos
-- Data: 2025-01-XX
-- Descrição: Cria tabelas para gerenciar fornecedores, compras e histórico de compras por produto

-- ===================================================================
-- TABELA: prest_fornecedores
-- ===================================================================
-- Armazena informações dos fornecedores
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_fornecedores'
    ) THEN
        CREATE TABLE public.prest_fornecedores (
            id UUID DEFAULT gen_random_uuid() NOT NULL PRIMARY KEY,
            usuario_id UUID NOT NULL REFERENCES public.prest_usuarios(id) ON DELETE CASCADE,
            nome_fantasia VARCHAR(150) NOT NULL,
            razao_social VARCHAR(255),
            cnpj VARCHAR(18),
            cpf VARCHAR(14),
            inscricao_estadual VARCHAR(50),
            telefone VARCHAR(20),
            email VARCHAR(100),
            endereco VARCHAR(255),
            numero VARCHAR(20),
            complemento VARCHAR(100),
            bairro VARCHAR(100),
            cidade VARCHAR(100),
            estado VARCHAR(2),
            cep VARCHAR(9),
            observacoes TEXT,
            ativo BOOLEAN DEFAULT true NOT NULL,
            data_criacao TIMESTAMP WITH TIME ZONE DEFAULT NOW() NOT NULL,
            data_atualizacao TIMESTAMP WITH TIME ZONE DEFAULT NOW() NOT NULL,
            
            CONSTRAINT prest_fornecedores_usuario_fk FOREIGN KEY (usuario_id) 
                REFERENCES public.prest_usuarios(id) ON DELETE CASCADE
        );
        
        COMMENT ON TABLE public.prest_fornecedores IS 'Cadastro de fornecedores de cada prestanista';
        COMMENT ON COLUMN public.prest_fornecedores.id IS 'Identificador único do fornecedor (UUID)';
        COMMENT ON COLUMN public.prest_fornecedores.usuario_id IS 'Referência ao prestanista (dono da loja)';
        COMMENT ON COLUMN public.prest_fornecedores.nome_fantasia IS 'Nome fantasia do fornecedor';
        COMMENT ON COLUMN public.prest_fornecedores.razao_social IS 'Razão social do fornecedor (se pessoa jurídica)';
        COMMENT ON COLUMN public.prest_fornecedores.cnpj IS 'CNPJ do fornecedor (se pessoa jurídica)';
        COMMENT ON COLUMN public.prest_fornecedores.cpf IS 'CPF do fornecedor (se pessoa física)';
        COMMENT ON COLUMN public.prest_fornecedores.ativo IS 'Indica se o fornecedor está ativo';
        
        -- Índices para melhor performance
        CREATE INDEX idx_prest_fornecedores_usuario_id ON public.prest_fornecedores(usuario_id);
        CREATE INDEX idx_prest_fornecedores_ativo ON public.prest_fornecedores(ativo);
        CREATE INDEX idx_prest_fornecedores_cnpj ON public.prest_fornecedores(cnpj) WHERE cnpj IS NOT NULL;
        CREATE INDEX idx_prest_fornecedores_cpf ON public.prest_fornecedores(cpf) WHERE cpf IS NOT NULL;
        
        RAISE NOTICE 'Tabela prest_fornecedores criada com sucesso.';
    ELSE
        RAISE NOTICE 'Tabela prest_fornecedores já existe.';
    END IF;
END $$;

-- ===================================================================
-- TABELA: prest_compras
-- ===================================================================
-- Armazena o cabeçalho de cada compra/resuprimento
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_compras'
    ) THEN
        CREATE TABLE public.prest_compras (
            id UUID DEFAULT gen_random_uuid() NOT NULL PRIMARY KEY,
            usuario_id UUID NOT NULL REFERENCES public.prest_usuarios(id) ON DELETE CASCADE,
            fornecedor_id UUID NOT NULL REFERENCES public.prest_fornecedores(id) ON DELETE RESTRICT,
            numero_nota_fiscal VARCHAR(50),
            serie_nota_fiscal VARCHAR(10),
            data_compra DATE NOT NULL DEFAULT CURRENT_DATE,
            data_vencimento DATE,
            valor_total NUMERIC(10,2) NOT NULL DEFAULT 0.00,
            valor_frete NUMERIC(10,2) DEFAULT 0.00,
            valor_desconto NUMERIC(10,2) DEFAULT 0.00,
            forma_pagamento VARCHAR(50),
            status_compra VARCHAR(20) DEFAULT 'PENDENTE' NOT NULL,
            observacoes TEXT,
            data_criacao TIMESTAMP WITH TIME ZONE DEFAULT NOW() NOT NULL,
            data_atualizacao TIMESTAMP WITH TIME ZONE DEFAULT NOW() NOT NULL,
            
            CONSTRAINT prest_compras_usuario_fk FOREIGN KEY (usuario_id) 
                REFERENCES public.prest_usuarios(id) ON DELETE CASCADE,
            CONSTRAINT prest_compras_fornecedor_fk FOREIGN KEY (fornecedor_id) 
                REFERENCES public.prest_fornecedores(id) ON DELETE RESTRICT
        );
        
        COMMENT ON TABLE public.prest_compras IS 'Cabeçalho de compras/resuprimentos realizados';
        COMMENT ON COLUMN public.prest_compras.id IS 'Identificador único da compra (UUID)';
        COMMENT ON COLUMN public.prest_compras.usuario_id IS 'Referência ao prestanista (dono da loja)';
        COMMENT ON COLUMN public.prest_compras.fornecedor_id IS 'Referência ao fornecedor';
        COMMENT ON COLUMN public.prest_compras.numero_nota_fiscal IS 'Número da nota fiscal';
        COMMENT ON COLUMN public.prest_compras.data_compra IS 'Data da compra';
        COMMENT ON COLUMN public.prest_compras.valor_total IS 'Valor total da compra (soma dos itens)';
        COMMENT ON COLUMN public.prest_compras.status_compra IS 'Status da compra: PENDENTE, CONCLUIDA, CANCELADA';
        
        -- Índices para melhor performance
        CREATE INDEX idx_prest_compras_usuario_id ON public.prest_compras(usuario_id);
        CREATE INDEX idx_prest_compras_fornecedor_id ON public.prest_compras(fornecedor_id);
        CREATE INDEX idx_prest_compras_data_compra ON public.prest_compras(data_compra);
        CREATE INDEX idx_prest_compras_status ON public.prest_compras(status_compra);
        
        RAISE NOTICE 'Tabela prest_compras criada com sucesso.';
    ELSE
        RAISE NOTICE 'Tabela prest_compras já existe.';
    END IF;
END $$;

-- ===================================================================
-- TABELA: prest_itens_compra
-- ===================================================================
-- Armazena os itens de cada compra (produtos comprados)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_itens_compra'
    ) THEN
        CREATE TABLE public.prest_itens_compra (
            id UUID DEFAULT gen_random_uuid() NOT NULL PRIMARY KEY,
            compra_id UUID NOT NULL REFERENCES public.prest_compras(id) ON DELETE CASCADE,
            produto_id UUID NOT NULL REFERENCES public.prest_produtos(id) ON DELETE RESTRICT,
            quantidade NUMERIC(10,3) NOT NULL,
            preco_unitario NUMERIC(10,2) NOT NULL,
            valor_total_item NUMERIC(10,2) NOT NULL,
            data_criacao TIMESTAMP WITH TIME ZONE DEFAULT NOW() NOT NULL,
            
            CONSTRAINT prest_itens_compra_compra_fk FOREIGN KEY (compra_id) 
                REFERENCES public.prest_compras(id) ON DELETE CASCADE,
            CONSTRAINT prest_itens_compra_produto_fk FOREIGN KEY (produto_id) 
                REFERENCES public.prest_produtos(id) ON DELETE RESTRICT,
            CONSTRAINT prest_itens_compra_quantidade_check CHECK (quantidade > 0),
            CONSTRAINT prest_itens_compra_preco_check CHECK (preco_unitario >= 0),
            CONSTRAINT prest_itens_compra_valor_check CHECK (valor_total_item >= 0)
        );
        
        COMMENT ON TABLE public.prest_itens_compra IS 'Itens de cada compra (produtos comprados)';
        COMMENT ON COLUMN public.prest_itens_compra.id IS 'Identificador único do item (UUID)';
        COMMENT ON COLUMN public.prest_itens_compra.compra_id IS 'Referência à compra';
        COMMENT ON COLUMN public.prest_itens_compra.produto_id IS 'Referência ao produto comprado';
        COMMENT ON COLUMN public.prest_itens_compra.quantidade IS 'Quantidade comprada do produto';
        COMMENT ON COLUMN public.prest_itens_compra.preco_unitario IS 'Preço unitário pago pelo produto nesta compra';
        COMMENT ON COLUMN public.prest_itens_compra.valor_total_item IS 'Valor total do item (quantidade * preco_unitario)';
        
        -- Índices para melhor performance
        CREATE INDEX idx_prest_itens_compra_compra_id ON public.prest_itens_compra(compra_id);
        CREATE INDEX idx_prest_itens_compra_produto_id ON public.prest_itens_compra(produto_id);
        
        -- Índice composto para consultas de histórico por produto
        CREATE INDEX idx_prest_itens_compra_produto_compra ON public.prest_itens_compra(produto_id, compra_id);
        
        RAISE NOTICE 'Tabela prest_itens_compra criada com sucesso.';
    ELSE
        RAISE NOTICE 'Tabela prest_itens_compra já existe.';
    END IF;
END $$;

-- ===================================================================
-- TRIGGER: Atualizar data_atualizacao automaticamente
-- ===================================================================
-- Usa a função trigger_set_timestamp() que já existe no sistema
DO $$
BEGIN
    -- Trigger para prest_fornecedores
    IF NOT EXISTS (
        SELECT 1 FROM pg_trigger WHERE tgname = 'set_timestamp'
            AND tgrelid = 'public.prest_fornecedores'::regclass
    ) THEN
        CREATE TRIGGER set_timestamp
            BEFORE UPDATE ON public.prest_fornecedores
            FOR EACH ROW
            EXECUTE FUNCTION public.trigger_set_timestamp();
        
        RAISE NOTICE 'Trigger set_timestamp criado para prest_fornecedores.';
    END IF;
    
    -- Trigger para prest_compras
    IF NOT EXISTS (
        SELECT 1 FROM pg_trigger WHERE tgname = 'set_timestamp'
            AND tgrelid = 'public.prest_compras'::regclass
    ) THEN
        CREATE TRIGGER set_timestamp
            BEFORE UPDATE ON public.prest_compras
            FOR EACH ROW
            EXECUTE FUNCTION public.trigger_set_timestamp();
        
        RAISE NOTICE 'Trigger set_timestamp criado para prest_compras.';
    END IF;
END $$;

-- ===================================================================
-- VIEW: Histórico de compras por produto (para comparação de preços)
-- ===================================================================
CREATE OR REPLACE VIEW public.vw_historico_compras_produto AS
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
ORDER BY ic.produto_id, c.data_compra DESC;

COMMENT ON VIEW public.vw_historico_compras_produto IS 'View para consultar histórico de compras por produto, incluindo fornecedor e preços';

-- Migration concluída: Tabelas de fornecedores e compras criadas com sucesso!

