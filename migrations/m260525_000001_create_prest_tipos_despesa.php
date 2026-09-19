<?php

use yii\db\Migration;

/**
 * Migration: Cria tabela prest_tipos_despesa e adiciona tipo_despesa_id em prest_contas_pagar
 *
 * Grupos fixos (hard-coded):
 *   - FIXA       → Despesas Fixas
 *   - VARIAVEL   → Despesas Variáveis
 *   - MERCADORIA → Compras de Mercadorias
 *
 * Os tipos são categorias genéricas e reutilizáveis (ex: "Aluguel", "Energia Elétrica",
 * "Compra de Mercadoria"). O detalhe de cada lançamento vai no campo descrição da conta.
 */
class m260525_000001_create_prest_tipos_despesa extends Migration
{
    public function safeUp()
    {
        // -------------------------------------------------------
        // 1. Criar tabela prest_tipos_despesa
        // -------------------------------------------------------
        $this->createTable('prest_tipos_despesa', [
            'id'               => 'UUID PRIMARY KEY DEFAULT uuid_generate_v4()',
            'usuario_id'       => 'UUID NOT NULL',
            'nome'             => 'VARCHAR(100) NOT NULL',
            'grupo'            => "VARCHAR(30) NOT NULL CHECK (grupo IN ('FIXA', 'VARIAVEL', 'MERCADORIA'))",
            'descricao'        => 'TEXT',
            'ativo'            => 'BOOLEAN NOT NULL DEFAULT TRUE',
            'data_criacao'     => 'TIMESTAMPTZ NOT NULL DEFAULT NOW()',
            'data_atualizacao' => 'TIMESTAMPTZ NOT NULL DEFAULT NOW()',
        ]);

        // FK para prest_usuarios
        $this->addForeignKey(
            'fk_tipos_despesa_usuario',
            'prest_tipos_despesa',
            'usuario_id',
            'prest_usuarios',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Índices
        $this->createIndex('idx_prest_tipos_despesa_usuario', 'prest_tipos_despesa', 'usuario_id');
        $this->createIndex('idx_prest_tipos_despesa_grupo',   'prest_tipos_despesa', 'grupo');
        $this->createIndex('idx_prest_tipos_despesa_ativo',   'prest_tipos_despesa', 'ativo');

        // -------------------------------------------------------
        // 2. Adicionar tipo_despesa_id em prest_contas_pagar
        // -------------------------------------------------------
        if ($this->db->schema->getTableSchema('prest_contas_pagar') === null) {
            $this->execute("
                CREATE TABLE IF NOT EXISTS prest_contas_pagar (
                    id uuid NOT NULL DEFAULT gen_random_uuid(),
                    usuario_id uuid NOT NULL REFERENCES prest_usuarios(id) ON DELETE CASCADE,
                    fornecedor_id uuid REFERENCES prest_fornecedores(id) ON DELETE SET NULL,
                    compra_id uuid REFERENCES prest_compras(id) ON DELETE SET NULL,
                    descricao varchar(255) NOT NULL,
                    valor numeric(10,2) NOT NULL,
                    data_vencimento date NOT NULL,
                    data_pagamento date,
                    status varchar(20) NOT NULL DEFAULT 'PENDENTE',
                    forma_pagamento_id uuid REFERENCES prest_formas_pagamento(id) ON DELETE SET NULL,
                    observacoes text,
                    data_criacao timestamptz NOT NULL DEFAULT now(),
                    data_atualizacao timestamptz NOT NULL DEFAULT now(),
                    arquivo_comprovante varchar(255),
                    tipo_despesa_id uuid REFERENCES prest_tipos_despesa(id) ON DELETE SET NULL,
                    PRIMARY KEY (id)
                )
            ");
            $this->execute("CREATE INDEX IF NOT EXISTS idx_prest_contas_pagar_usuario_id ON prest_contas_pagar(usuario_id)");
            $this->execute("CREATE INDEX IF NOT EXISTS idx_prest_contas_pagar_tipo_id ON prest_contas_pagar(tipo_despesa_id)");
        } else {
            $tableContasPagar = $this->db->schema->getTableSchema('prest_contas_pagar');
            if (!isset($tableContasPagar->columns['tipo_despesa_id'])) {
                $this->addColumn('prest_contas_pagar', 'tipo_despesa_id', 'UUID DEFAULT NULL');
                $this->addForeignKey(
                    'fk_contas_pagar_tipo_despesa',
                    'prest_contas_pagar',
                    'tipo_despesa_id',
                    'prest_tipos_despesa',
                    'id',
                    'SET NULL',
                    'CASCADE'
                );
                $this->createIndex('idx_prest_contas_pagar_tipo_id', 'prest_contas_pagar', 'tipo_despesa_id');
            }
        }
    }

    public function safeDown()
    {
        // Remove FK e índice em prest_contas_pagar
        $this->dropIndex('idx_prest_contas_pagar_tipo_id', 'prest_contas_pagar');
        $this->dropForeignKey('fk_contas_pagar_tipo_despesa', 'prest_contas_pagar');
        $this->dropColumn('prest_contas_pagar', 'tipo_despesa_id');

        // Remove tabela prest_tipos_despesa
        $this->dropTable('prest_tipos_despesa');
    }
}
