-- Migration: Adicionar campo prest_usuario_login_id em prest_colaboradores
-- Data: 2024-12-08
-- Descrição: Adiciona campo para relacionar colaborador com seu registro de login em prest_usuarios
--            Mantém usuario_id apontando para o dono da loja (identifica a loja)

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

    -- Adiciona campo prest_usuario_login_id (opcional)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_colaboradores' 
        AND column_name = 'prest_usuario_login_id'
    ) THEN
        ALTER TABLE public.prest_colaboradores 
        ADD COLUMN prest_usuario_login_id UUID;
        
        COMMENT ON COLUMN public.prest_colaboradores.prest_usuario_login_id IS 'FK para prest_usuarios.id - Registro de login do colaborador (eh_dono_loja = false). NULL se colaborador não tem login próprio.';
        
        RAISE NOTICE 'Campo prest_usuario_login_id adicionado com sucesso à tabela prest_colaboradores.';
    ELSE
        RAISE NOTICE 'Campo prest_usuario_login_id já existe na tabela prest_colaboradores.';
    END IF;

    -- Adiciona Foreign Key
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_colaboradores' 
        AND constraint_name = 'fk_colaborador_prest_usuario_login'
    ) THEN
        ALTER TABLE public.prest_colaboradores
        ADD CONSTRAINT fk_colaborador_prest_usuario_login 
        FOREIGN KEY (prest_usuario_login_id) 
        REFERENCES public.prest_usuarios(id) 
        ON DELETE SET NULL;
        
        RAISE NOTICE 'Foreign Key fk_colaborador_prest_usuario_login criada com sucesso.';
    ELSE
        RAISE NOTICE 'Foreign Key fk_colaborador_prest_usuario_login já existe.';
    END IF;

    -- Cria índice para melhorar performance
    IF NOT EXISTS (
        SELECT 1 FROM pg_indexes 
        WHERE tablename = 'prest_colaboradores' 
        AND indexname = 'idx_colaboradores_prest_usuario_login_id'
    ) THEN
        CREATE INDEX idx_colaboradores_prest_usuario_login_id 
        ON public.prest_colaboradores(prest_usuario_login_id);
        
        RAISE NOTICE 'Índice idx_colaboradores_prest_usuario_login_id criado com sucesso.';
    ELSE
        RAISE NOTICE 'Índice idx_colaboradores_prest_usuario_login_id já existe.';
    END IF;

    RAISE NOTICE 'Migração concluída com sucesso!';
END $$;

