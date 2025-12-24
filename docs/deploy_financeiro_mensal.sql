-- Migration: m251224_112000_create_financeiro_mensal_table
-- Description: Cria tabela para histórico financeiro mensal
-- Database: PostgreSQL
CREATE TABLE "prest_financeiro_mensal" (
    "id" SERIAL PRIMARY KEY,
    "usuario_id" varchar(255) NOT NULL,
    "mes_referencia" date NOT NULL,
    "faturamento_total" numeric(15, 2) DEFAULT 0,
    "despesas_fixas_total" numeric(15, 2) DEFAULT 0,
    "despesas_variaveis_total" numeric(15, 2) DEFAULT 0,
    "custo_mercadoria_vendida" numeric(15, 2) DEFAULT 0,
    "data_criacao" timestamp DEFAULT NOW(),
    "data_atualizacao" timestamp DEFAULT NOW()
);
-- Index para garantir unicidade do mês por usuário e permitir busca rápida
CREATE UNIQUE INDEX "idx-financeiro_mensal-usuario-mes" ON "prest_financeiro_mensal" ("usuario_id", "mes_referencia");