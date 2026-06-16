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
