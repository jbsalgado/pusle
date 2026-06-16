-- Migration: Adiciona campo eh_administrador ao cadastro de colaboradores
-- Data: 2025-01-XX
-- Descrição: Adiciona flag para identificar colaboradores administradores que terão acesso a todos os módulos

-- Verifica se a tabela prest_colaboradores existe
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_colaboradores'
    ) THEN
        RAISE NOTICE 'AVISO: Tabela prest_colaboradores não encontrada. Execute após criar a tabela.';
        RETURN;
    END IF;

    -- Adiciona campo eh_administrador
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_colaboradores' 
        AND column_name = 'eh_administrador'
    ) THEN
        ALTER TABLE public.prest_colaboradores 
        ADD COLUMN eh_administrador BOOLEAN NOT NULL DEFAULT false;
        
        COMMENT ON COLUMN public.prest_colaboradores.eh_administrador IS 'Flag que indica se o colaborador é administrador e tem acesso a todos os módulos';
        
        RAISE NOTICE 'Campo eh_administrador adicionado com sucesso à tabela prest_colaboradores.';
    ELSE
        RAISE NOTICE 'Campo eh_administrador já existe na tabela prest_colaboradores.';
    END IF;
END $$;

