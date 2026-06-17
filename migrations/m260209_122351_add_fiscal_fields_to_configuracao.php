<?php

use yii\db\Migration;

class m260209_122351_add_fiscal_fields_to_configuracao extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%prest_configuracoes}}', 'razao_social', $this->string(255));
        $this->addColumn('{{%prest_configuracoes}}', 'cnpj', $this->string(14));
        $this->addColumn('{{%prest_configuracoes}}', 'ie', $this->string(20));
        $this->addColumn('{{%prest_configuracoes}}', 'crt', $this->integer()->defaultValue(1)); // 1=Simples, 3=Normal
        $this->addColumn('{{%prest_configuracoes}}', 'nfe_ambiente', $this->integer()->defaultValue(2)); // 1=Prod, 2=Homo
        $this->addColumn('{{%prest_configuracoes}}', 'nfce_csc', $this->string(100));
        $this->addColumn('{{%prest_configuracoes}}', 'nfce_csc_id', $this->string(10));
        $this->addColumn('{{%prest_configuracoes}}', 'certificado_pfx', $this->text()); // Base64 or path
        $this->addColumn('{{%prest_configuracoes}}', 'certificado_senha', $this->string(100));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%prest_configuracoes}}', 'certificado_senha');
        $this->dropColumn('{{%prest_configuracoes}}', 'certificado_pfx');
        $this->dropColumn('{{%prest_configuracoes}}', 'nfce_csc_id');
        $this->dropColumn('{{%prest_configuracoes}}', 'nfce_csc');
        $this->dropColumn('{{%prest_configuracoes}}', 'nfe_ambiente');
        $this->dropColumn('{{%prest_configuracoes}}', 'crt');
        $this->dropColumn('{{%prest_configuracoes}}', 'ie');
        $this->dropColumn('{{%prest_configuracoes}}', 'cnpj');
        $this->dropColumn('{{%prest_configuracoes}}', 'razao_social');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260209_122351_add_fiscal_fields_to_configuracao cannot be reverted.\n";

        return false;
    }
    */
}
