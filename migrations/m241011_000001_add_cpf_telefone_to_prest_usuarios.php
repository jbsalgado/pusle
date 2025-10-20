<?php
/**
 * Migration: Adicionar CPF e Telefone à tabela prest_usuarios
 * 
 * Execute: php yii migrate/create add_cpf_telefone_to_prest_usuarios
 * E substitua o conteúdo pelo código abaixo
 */

use yii\db\Migration;

class m241011_000001_add_cpf_telefone_to_prest_usuarios extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Adicionar coluna CPF
        $this->addColumn('{{%prest_usuarios}}', 'cpf', 'VARCHAR(11) AFTER email');
        
        // Adicionar coluna Telefone
        $this->addColumn('{{%prest_usuarios}}', 'telefone', 'VARCHAR(15) AFTER cpf');
        
        // Criar índice único para CPF
        $this->createIndex(
            'idx_usuario_cpf',
            '{{%prest_usuarios}}',
            'cpf',
            true // único
        );
        
        // Tornar email opcional (permitir NULL)
        $this->alterColumn('{{%prest_usuarios}}', 'email', 'VARCHAR(100) NULL');
        
        // Comentários nas colunas
        $this->addCommentOnColumn('{{%prest_usuarios}}', 'cpf', 'CPF do usuário (somente números, 11 dígitos)');
        $this->addCommentOnColumn('{{%prest_usuarios}}', 'telefone', 'Telefone/WhatsApp do usuário');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx_usuario_cpf', '{{%prest_usuarios}}');
        $this->dropColumn('{{%prest_usuarios}}', 'cpf');
        $this->dropColumn('{{%prest_usuarios}}', 'telefone');
        
        // Reverter email para NOT NULL
        $this->alterColumn('{{%prest_usuarios}}', 'email', 'VARCHAR(100) NOT NULL');
    }
}