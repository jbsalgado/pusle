<?php

use yii\db\Migration;

/**
 * Adiciona campos de PIX à tabela loja_configuracao
 */
class m260330_172000_add_pix_fields_to_loja_configuracao extends Migration
{
    public function safeUp()
    {
        $this->addColumn('loja_configuracao', 'pix_chave', $this->string(255)->after('logo_path'));
        $this->addColumn('loja_configuracao', 'pix_nome', $this->string(255)->after('pix_chave'));
        $this->addColumn('loja_configuracao', 'pix_cidade', $this->string(255)->after('pix_nome'));

        echo "✅ Campos de PIX adicionados com sucesso!\n";
    }

    public function safeDown()
    {
        $this->dropColumn('loja_configuracao', 'pix_cidade');
        $this->dropColumn('loja_configuracao', 'pix_nome');
        $this->dropColumn('loja_configuracao', 'pix_chave');

        echo "✅ Campos de PIX removidos!\n";
    }
}
