-- Migration: Adaptar prest_usuarios para autenticação completa
-- Data: 2024-12-08
-- Descrição: Adiciona campos necessários para que prest_usuarios tenha as mesmas finalidades de user,
--            permitindo que todos (donos e colaboradores) tenham seu próprio usuário e senha,
--            com flag eh_dono_loja para identificar o dono da loja.

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

    -- Adiciona campo username (para login)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_usuarios' 
        AND column_name = 'username'
    ) THEN
        ALTER TABLE public.prest_usuarios 
        ADD COLUMN username VARCHAR(50);
        
        -- Gera username baseado no email para registros existentes
        UPDATE public.prest_usuarios 
        SET username = email 
        WHERE username IS NULL AND email IS NOT NULL;
        
        -- Se ainda houver NULL, usa CPF
        UPDATE public.prest_usuarios 
        SET username = cpf 
        WHERE username IS NULL AND cpf IS NOT NULL;
        
        -- Adiciona constraint UNIQUE após popular dados
        ALTER TABLE public.prest_usuarios 
        ADD CONSTRAINT prest_usuarios_username_unique UNIQUE (username);
        
        -- Torna NOT NULL após popular
        ALTER TABLE public.prest_usuarios 
        ALTER COLUMN username SET NOT NULL;
        
        COMMENT ON COLUMN public.prest_usuarios.username IS 'Nome de usuário único para login (pode ser email ou CPF)';
        
        RAISE NOTICE 'Campo username adicionado com sucesso à tabela prest_usuarios.';
    ELSE
        RAISE NOTICE 'Campo username já existe na tabela prest_usuarios.';
    END IF;

    -- Adiciona campo eh_dono_loja (flag para identificar dono)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_usuarios' 
        AND column_name = 'eh_dono_loja'
    ) THEN
        ALTER TABLE public.prest_usuarios 
        ADD COLUMN eh_dono_loja BOOLEAN NOT NULL DEFAULT true;
        
        -- Registros existentes são considerados donos
        UPDATE public.prest_usuarios 
        SET eh_dono_loja = true 
        WHERE eh_dono_loja IS NULL;
        
        COMMENT ON COLUMN public.prest_usuarios.eh_dono_loja IS 'Flag que indica se o usuário é dono da loja (true) ou colaborador (false)';
        
        RAISE NOTICE 'Campo eh_dono_loja adicionado com sucesso à tabela prest_usuarios.';
    ELSE
        RAISE NOTICE 'Campo eh_dono_loja já existe na tabela prest_usuarios.';
    END IF;

    -- Adiciona campo blocked_at (para bloquear usuário)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_usuarios' 
        AND column_name = 'blocked_at'
    ) THEN
        ALTER TABLE public.prest_usuarios 
        ADD COLUMN blocked_at TIMESTAMP;
        
        COMMENT ON COLUMN public.prest_usuarios.blocked_at IS 'Data/hora em que o usuário foi bloqueado. NULL = usuário ativo, não NULL = usuário bloqueado';
        
        RAISE NOTICE 'Campo blocked_at adicionado com sucesso à tabela prest_usuarios.';
    ELSE
        RAISE NOTICE 'Campo blocked_at já existe na tabela prest_usuarios.';
    END IF;

    -- Adiciona campo confirmed_at (para confirmar email)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'prest_usuarios' 
        AND column_name = 'confirmed_at'
    ) THEN
        ALTER TABLE public.prest_usuarios 
        ADD COLUMN confirmed_at TIMESTAMP;
        
        -- Registros existentes são considerados confirmados
        UPDATE public.prest_usuarios 
        SET confirmed_at = data_criacao 
        WHERE confirmed_at IS NULL;
        
        COMMENT ON COLUMN public.prest_usuarios.confirmed_at IS 'Data/hora em que o email foi confirmado. NULL = não confirmado, não NULL = confirmado';
        
        RAISE NOTICE 'Campo confirmed_at adicionado com sucesso à tabela prest_usuarios.';
    ELSE
        RAISE NOTICE 'Campo confirmed_at já existe na tabela prest_usuarios.';
    END IF;

    -- Cria índice para melhorar performance de buscas
    IF NOT EXISTS (
        SELECT 1 FROM pg_indexes 
        WHERE tablename = 'prest_usuarios' 
        AND indexname = 'idx_prest_usuarios_username'
    ) THEN
        CREATE INDEX idx_prest_usuarios_username ON public.prest_usuarios(username);
        RAISE NOTICE 'Índice idx_prest_usuarios_username criado com sucesso.';
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM pg_indexes 
        WHERE tablename = 'prest_usuarios' 
        AND indexname = 'idx_prest_usuarios_eh_dono_loja'
    ) THEN
        CREATE INDEX idx_prest_usuarios_eh_dono_loja ON public.prest_usuarios(eh_dono_loja);
        RAISE NOTICE 'Índice idx_prest_usuarios_eh_dono_loja criado com sucesso.';
    END IF;

    RAISE NOTICE 'Migração concluída com sucesso!';
END $$;

