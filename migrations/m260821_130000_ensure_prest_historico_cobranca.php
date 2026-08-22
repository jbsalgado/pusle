<?php

use yii\db\Migration;

/**
 * Migration para garantir a criação da tabela prest_historico_cobranca caso ela não exista.
 */
class m260821_130000_ensure_prest_historico_cobranca extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('prest_historico_cobranca');
        if ($tableSchema === null) {
            $this->createTable('prest_historico_cobranca', [
                'id'              => 'UUID PRIMARY KEY DEFAULT uuid_generate_v4()',
                'parcela_id'      => 'UUID NOT NULL',
                'cobrador_id'     => 'UUID NOT NULL',
                'cliente_id'      => 'UUID NOT NULL',
                'usuario_id'      => 'UUID NOT NULL',
                'tipo_acao'       => 'VARCHAR(20) NOT NULL',
                'valor_recebido' => 'NUMERIC(10,2) DEFAULT NULL',
                'observacao'     => 'TEXT DEFAULT NULL',
                'localizacao_lat' => 'NUMERIC(10,6) DEFAULT NULL',
                'localizacao_lng' => 'NUMERIC(10,6) DEFAULT NULL',
                'data_acao'       => 'TIMESTAMPTZ NOT NULL DEFAULT NOW()',
            ]);

            $this->addForeignKey(
                'prest_historico_cobranca_parcela_id_fkey',
                'prest_historico_cobranca',
                'parcela_id',
                'prest_parcelas',
                'id',
                'CASCADE',
                'RESTRICT'
            );

            $this->addForeignKey(
                'prest_historico_cobranca_cobrador_id_fkey',
                'prest_historico_cobranca',
                'cobrador_id',
                'prest_colaboradores',
                'id',
                'RESTRICT',
                'RESTRICT'
            );

            $this->addForeignKey(
                'prest_historico_cobranca_cliente_id_fkey',
                'prest_historico_cobranca',
                'cliente_id',
                'prest_clientes',
                'id',
                'RESTRICT',
                'RESTRICT'
            );

            $this->addForeignKey(
                'prest_historico_cobranca_usuario_id_fkey',
                'prest_historico_cobranca',
                'usuario_id',
                'prest_usuarios',
                'id',
                'RESTRICT',
                'RESTRICT'
            );

            $this->createIndex('idx_hist_cobranca_parcela_id', 'prest_historico_cobranca', 'parcela_id');
            $this->createIndex('idx_hist_cobranca_cobrador_id', 'prest_historico_cobranca', 'cobrador_id');
            $this->createIndex('idx_hist_cobranca_data', 'prest_historico_cobranca', 'data_acao');
        }
    }

    public function safeDown()
    {
        $tableSchema = $this->db->getTableSchema('prest_historico_cobranca');
        if ($tableSchema !== null) {
            $this->dropTable('prest_historico_cobranca');
        }
    }
}
