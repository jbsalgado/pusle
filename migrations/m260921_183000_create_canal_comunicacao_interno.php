<?php

use yii\db\Migration;

/**
 * Migration: Criação da estrutura do Canal de Comunicação Interno (Grupos/Setores Multi-Tenant)
 */
class m260921_183000_create_canal_comunicacao_interno extends Migration
{
    public function safeUp()
    {
        // 1. Tabela de Setores / Grupos do Canal Interno
        $this->execute("
            CREATE TABLE IF NOT EXISTS public.prest_canal_setores (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                usuario_id UUID NOT NULL REFERENCES public.prest_usuarios(id) ON DELETE CASCADE,
                nome VARCHAR(100) NOT NULL,
                descricao VARCHAR(255),
                icone VARCHAR(50) DEFAULT '💬',
                cor VARCHAR(50) DEFAULT 'emerald',
                ativo BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->execute("CREATE INDEX IF NOT EXISTS idx_canal_setores_tenant ON public.prest_canal_setores(usuario_id, ativo)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_canal_setores_nome ON public.prest_canal_setores(usuario_id, nome)");

        // 2. Tabela de Colaboradores Vinculados aos Setores
        $this->execute("
            CREATE TABLE IF NOT EXISTS public.prest_canal_setor_colaboradores (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                setor_id UUID NOT NULL REFERENCES public.prest_canal_setores(id) ON DELETE CASCADE,
                colaborador_id UUID NOT NULL REFERENCES public.prest_colaboradores(id) ON DELETE CASCADE,
                usuario_id UUID NOT NULL REFERENCES public.prest_usuarios(id) ON DELETE CASCADE,
                created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT uq_canal_setor_colaborador UNIQUE (setor_id, colaborador_id)
            )
        ");

        $this->execute("CREATE INDEX IF NOT EXISTS idx_canal_setor_colab_tenant ON public.prest_canal_setor_colaboradores(usuario_id)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_canal_setor_colab_setor ON public.prest_canal_setor_colaboradores(setor_id)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_canal_setor_colab_colab ON public.prest_canal_setor_colaboradores(colaborador_id)");

        // 3. Adiciona coluna setor_id em prest_cliente_inbox
        $this->execute("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.columns 
                    WHERE table_name = 'prest_cliente_inbox' AND column_name = 'setor_id'
                ) THEN
                    ALTER TABLE public.prest_cliente_inbox 
                    ADD COLUMN setor_id UUID REFERENCES public.prest_canal_setores(id) ON DELETE SET NULL;
                END IF;
            END $$;
        ");

        $this->execute("CREATE INDEX IF NOT EXISTS idx_cliente_inbox_setor ON public.prest_cliente_inbox(usuario_id, setor_id)");
    }

    public function safeDown()
    {
        $this->execute("DROP INDEX IF EXISTS idx_cliente_inbox_setor");
        $this->execute("ALTER TABLE public.prest_cliente_inbox DROP COLUMN IF EXISTS setor_id");
        $this->execute("DROP TABLE IF EXISTS public.prest_canal_setor_colaboradores");
        $this->execute("DROP TABLE IF EXISTS public.prest_canal_setores");
    }
}
