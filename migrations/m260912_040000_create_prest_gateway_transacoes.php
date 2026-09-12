<?php

use yii\db\Migration;

/**
 * Migration: Cria tabela prest_gateway_transacoes para registro unificado
 * e auditoria completa de transações de gateways de pagamento (Mercado Pago, Asaas, etc.) por tenant.
 */
class m260912_040000_create_prest_gateway_transacoes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        if ($this->db->getTableSchema('prest_gateway_transacoes') === null) {
            $this->createTable('prest_gateway_transacoes', [
                'id'                     => 'UUID PRIMARY KEY DEFAULT gen_random_uuid()',
                'tenant_id'              => 'UUID NOT NULL',
                'venda_id'               => 'UUID NULL',
                'gateway'                => 'VARCHAR(50) NOT NULL',
                'transacao_id'           => 'VARCHAR(100) NOT NULL',
                'tipo_pagamento'         => 'VARCHAR(50) NULL',
                'valor_bruto'            => 'NUMERIC(12,2) NOT NULL DEFAULT 0.00',
                'taxa_gateway'           => 'NUMERIC(12,2) NOT NULL DEFAULT 0.00',
                'taxa_saas'              => 'NUMERIC(12,2) NOT NULL DEFAULT 0.00',
                'valor_liquido'          => 'NUMERIC(12,2) NOT NULL DEFAULT 0.00',
                'status'                 => 'VARCHAR(50) NOT NULL',
                'status_detail'          => 'VARCHAR(100) NULL',
                'cartao_bandeira'        => 'VARCHAR(50) NULL',
                'cartao_ultimos_digitos' => 'VARCHAR(4) NULL',
                'parcelas'               => 'INT NOT NULL DEFAULT 1',
                'payload_requisicao'     => 'JSONB NULL',
                'payload_resposta'       => 'JSONB NULL',
                'created_at'             => 'TIMESTAMPTZ NOT NULL DEFAULT NOW()',
                'updated_at'             => 'TIMESTAMPTZ NOT NULL DEFAULT NOW()',
            ]);

            // FK com prest_usuarios (tenant)
            $this->addForeignKey(
                'fk_gtw_transacoes_tenant',
                'prest_gateway_transacoes',
                'tenant_id',
                'prest_usuarios',
                'id',
                'CASCADE',
                'CASCADE'
            );

            // FK com prest_vendas (venda / pedido)
            $this->addForeignKey(
                'fk_gtw_transacoes_venda',
                'prest_gateway_transacoes',
                'venda_id',
                'prest_vendas',
                'id',
                'SET NULL',
                'CASCADE'
            );

            // Índices para consultas de alta performance
            $this->createIndex('idx_gtw_transacoes_tenant', 'prest_gateway_transacoes', 'tenant_id');
            $this->createIndex('idx_gtw_transacoes_venda', 'prest_gateway_transacoes', 'venda_id');
            $this->createIndex('idx_gtw_transacoes_transacao', 'prest_gateway_transacoes', 'transacao_id');
            $this->createIndex('idx_gtw_transacoes_gateway', 'prest_gateway_transacoes', 'gateway');
            $this->createIndex('idx_gtw_transacoes_status', 'prest_gateway_transacoes', 'status');
            $this->createIndex('idx_gtw_transacoes_created', 'prest_gateway_transacoes', 'created_at');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        if ($this->db->getTableSchema('prest_gateway_transacoes') !== null) {
            $this->dropTable('prest_gateway_transacoes');
        }
    }
}
