<?php
/**
 * Migration: Criar tabela prest_dados_financeiros para Precificação Inteligente
 * 
 * Esta tabela armazena as configurações financeiras (taxas e lucro) de forma centralizada.
 * Pode ser configurada por usuário/loja (global) ou por produto específico.
 * 
 * Estrutura:
 * - usuario_id: Configuração global da loja
 * - produto_id: NULL para configuração global, ou ID do produto para configuração específica
 * - taxa_fixa_percentual: Taxas fixas (impostos fixos, taxas de plataforma, etc.)
 * - taxa_variavel_percentual: Taxas variáveis (comissões, taxas de pagamento, etc.)
 * - lucro_liquido_percentual: Lucro líquido desejado
 * 
 * Execute: php yii migrate
 */

use yii\db\Migration;

class m250101_000007_create_prest_dados_financeiros extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Cria tabela usando SQL direto para garantir tipos UUID corretos
        $this->execute("
            CREATE TABLE {{%prest_dados_financeiros}} (
                id SERIAL PRIMARY KEY,
                usuario_id UUID NOT NULL,
                produto_id UUID NULL,
                taxa_fixa_percentual DECIMAL(5, 2) DEFAULT 0.00 NOT NULL,
                taxa_variavel_percentual DECIMAL(5, 2) DEFAULT 0.00 NOT NULL,
                lucro_liquido_percentual DECIMAL(5, 2) DEFAULT 0.00 NOT NULL,
                data_criacao TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                data_atualizacao TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Índices para performance
        $this->createIndex('idx_prest_dados_financeiros_usuario', '{{%prest_dados_financeiros}}', 'usuario_id');
        $this->createIndex('idx_prest_dados_financeiros_produto', '{{%prest_dados_financeiros}}', 'produto_id');
        $this->createIndex('idx_prest_dados_financeiros_usuario_produto', '{{%prest_dados_financeiros}}', ['usuario_id', 'produto_id'], true);

        // Foreign keys
        $this->addForeignKey(
            'fk_prest_dados_financeiros_usuario',
            '{{%prest_dados_financeiros}}',
            'usuario_id',
            '{{%prest_usuarios}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_prest_dados_financeiros_produto',
            '{{%prest_dados_financeiros}}',
            'produto_id',
            '{{%prest_produtos}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Comentários
        $this->addCommentOnTable('{{%prest_dados_financeiros}}', 'Configurações financeiras para precificação inteligente (Markup Divisor). Pode ser global por loja ou específica por produto.');
        $this->addCommentOnColumn('{{%prest_dados_financeiros}}', 'produto_id', 'NULL = configuração global da loja, preenchido = configuração específica do produto');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_prest_dados_financeiros_produto', '{{%prest_dados_financeiros}}');
        $this->dropForeignKey('fk_prest_dados_financeiros_usuario', '{{%prest_dados_financeiros}}');
        $this->dropTable('{{%prest_dados_financeiros}}');
    }
}

