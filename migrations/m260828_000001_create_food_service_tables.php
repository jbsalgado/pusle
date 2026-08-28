<?php

use yii\db\Migration;

/**
 * Migration: Criar tabelas do módulo Food Service (Bares, Lanchonetes e Restaurantes)
 */
class m260828_000001_create_food_service_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Tabela prest_mesas
        $this->execute("
            CREATE TABLE IF NOT EXISTS public.prest_mesas (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                usuario_id UUID NOT NULL,
                numero_mesa VARCHAR(20) NOT NULL,
                nome_identificador VARCHAR(100),
                status VARCHAR(30) DEFAULT 'livre',
                lugares INTEGER DEFAULT 4,
                data_criacao TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
                data_atualizacao TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
            )
        ");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_mesas_usuario_id ON public.prest_mesas(usuario_id)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_mesas_status ON public.prest_mesas(usuario_id, status)");

        // 2. Tabela prest_comandas
        $this->execute("
            CREATE TABLE IF NOT EXISTS public.prest_comandas (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                usuario_id UUID NOT NULL,
                mesa_id UUID NULL,
                numero_comanda VARCHAR(30) NOT NULL,
                cliente_nome VARCHAR(150),
                status VARCHAR(30) DEFAULT 'aberta',
                data_abertura TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
                data_fechamento TIMESTAMP WITHOUT TIME ZONE NULL
            )
        ");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_comandas_usuario_id ON public.prest_comandas(usuario_id)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_comandas_status ON public.prest_comandas(usuario_id, status)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_comandas_mesa_id ON public.prest_comandas(mesa_id)");

        // 3. Tabela prest_comanda_itens
        $this->execute("
            CREATE TABLE IF NOT EXISTS public.prest_comanda_itens (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                comanda_id UUID NOT NULL,
                produto_id UUID NOT NULL,
                quantidade NUMERIC(10,3) NOT NULL DEFAULT 1,
                valor_unitario NUMERIC(10,2) NOT NULL,
                observacoes TEXT,
                destino_preparo VARCHAR(30) DEFAULT 'cozinha',
                status_preparo VARCHAR(30) DEFAULT 'pendente',
                data_pedido TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
            )
        ");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_comanda_itens_comanda_id ON public.prest_comanda_itens(comanda_id)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_comanda_itens_destino ON public.prest_comanda_itens(destino_preparo, status_preparo)");

        // 4. Tabela prest_produto_opcionais
        $this->execute("
            CREATE TABLE IF NOT EXISTS public.prest_produto_opcionais (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                produto_id UUID NOT NULL,
                nome VARCHAR(100) NOT NULL,
                valor_adicional NUMERIC(10,2) DEFAULT 0.00,
                ativo BOOLEAN DEFAULT TRUE
            )
        ");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_produto_opcionais_produto_id ON public.prest_produto_opcionais(produto_id)");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute("DROP TABLE IF EXISTS public.prest_produto_opcionais CASCADE");
        $this->execute("DROP TABLE IF EXISTS public.prest_comanda_itens CASCADE");
        $this->execute("DROP TABLE IF EXISTS public.prest_comandas CASCADE");
        $this->execute("DROP TABLE IF EXISTS public.prest_mesas CASCADE");
    }
}
