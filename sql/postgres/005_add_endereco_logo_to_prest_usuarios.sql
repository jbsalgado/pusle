-- Migration: Adiciona campos de endereço e logo ao cadastro de usuários (dono da loja)
-- Data: 2025-01-XX
-- Descrição: Adiciona campos de endereço (endereco, bairro, cidade, estado) e logo_path para prest_usuarios

-- Verifica se a tabela prest_usuarios existe
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_usuarios'
    ) THEN
        RAISE NOTICE 'AVISO: Tabela prest_usuarios não encontrada. Execute após criar a tabela.';
        RETURN;
    END IF;

    -- Adiciona campo endereco (logradouro)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_usuarios' 
        AND column_name = 'endereco'
    ) THEN
        ALTER TABLE public.prest_usuarios 
        ADD COLUMN endereco VARCHAR(255);
        
        COMMENT ON COLUMN public.prest_usuarios.endereco IS 'Endereço (logradouro) da empresa/loja';
    END IF;

    -- Adiciona campo bairro
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_usuarios' 
        AND column_name = 'bairro'
    ) THEN
        ALTER TABLE public.prest_usuarios 
        ADD COLUMN bairro VARCHAR(100);
        
        COMMENT ON COLUMN public.prest_usuarios.bairro IS 'Bairro da empresa/loja';
    END IF;

    -- Adiciona campo cidade
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_usuarios' 
        AND column_name = 'cidade'
    ) THEN
        ALTER TABLE public.prest_usuarios 
        ADD COLUMN cidade VARCHAR(100);
        
        COMMENT ON COLUMN public.prest_usuarios.cidade IS 'Cidade da empresa/loja';
    END IF;

    -- Adiciona campo estado
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_usuarios' 
        AND column_name = 'estado'
    ) THEN
        ALTER TABLE public.prest_usuarios 
        ADD COLUMN estado VARCHAR(2);
        
        COMMENT ON COLUMN public.prest_usuarios.estado IS 'Estado (UF) da empresa/loja';
    END IF;

    -- Adiciona campo logo_path (caminho da logo da empresa)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_usuarios' 
        AND column_name = 'logo_path'
    ) THEN
        ALTER TABLE public.prest_usuarios 
        ADD COLUMN logo_path VARCHAR(500);
        
        COMMENT ON COLUMN public.prest_usuarios.logo_path IS 'Caminho/URL da logo da empresa para uso em comprovantes e documentos';
    END IF;

    RAISE NOTICE 'Campos de endereço e logo adicionados com sucesso à tabela prest_usuarios.';
END $$;

