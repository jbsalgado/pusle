<?php

use yii\db\Migration;

class m260209_122115_create_prest_cupons_fiscais extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("
            CREATE TABLE {{%prest_cupons_fiscais}} (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                venda_id UUID NOT NULL,
                usuario_id UUID NOT NULL,
                numero INTEGER,
                serie INTEGER,
                modelo VARCHAR(2) DEFAULT '65',
                chave_acesso VARCHAR(44),
                xml_path TEXT,
                pdf_path TEXT,
                status VARCHAR(20) DEFAULT 'PENDENTE',
                ambiente INTEGER DEFAULT 2,
                mensagem_retorno TEXT,
                data_emissao TIMESTAMP WITH TIME ZONE,
                data_criacao TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                data_atualizacao TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $this->createIndex('idx_cupons_fiscais_venda', '{{%prest_cupons_fiscais}}', 'venda_id');
        $this->createIndex('idx_cupons_fiscais_usuario', '{{%prest_cupons_fiscais}}', 'usuario_id');
        $this->createIndex('idx_cupons_fiscais_status', '{{%prest_cupons_fiscais}}', 'status');
        $this->createIndex('idx_cupons_fiscais_chave', '{{%prest_cupons_fiscais}}', 'chave_acesso', true);

        $this->addForeignKey(
            'fk_cupons_fiscais_venda',
            '{{%prest_cupons_fiscais}}',
            'venda_id',
            '{{%prest_vendas}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_cupons_fiscais_usuario',
            '{{%prest_cupons_fiscais}}',
            'usuario_id',
            '{{%prest_usuarios}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addCommentOnTable('{{%prest_cupons_fiscais}}', 'Registro de emissão de cupons fiscais (NFe/NFCe) vinculados a vendas.');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_cupons_fiscais_usuario', '{{%prest_cupons_fiscais}}');
        $this->dropForeignKey('fk_cupons_fiscais_venda', '{{%prest_cupons_fiscais}}');
        $this->dropTable('{{%prest_cupons_fiscais}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260209_122115_create_prest_cupons_fiscais cannot be reverted.\n";

        return false;
    }
    */
}
