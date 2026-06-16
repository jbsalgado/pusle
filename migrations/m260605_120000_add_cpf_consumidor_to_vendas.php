<?php

use yii\db\Migration;

/**
 * Adiciona coluna cpf_consumidor à tabela prest_vendas.
 * Campo opcional para registrar o CPF do consumidor final em vendas diretas
 * (útil para NFC-e e relatórios). Pode ser preenchido automaticamente com
 * o CPF do colaborador logado.
 */
class m260605_120000_add_cpf_consumidor_to_vendas extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%prest_vendas}}',
            'cpf_consumidor',
            $this->string(14)->null()->comment('CPF do consumidor final (opcional). Armazenado com pontuação (000.000.000-00).')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%prest_vendas}}', 'cpf_consumidor');
    }
}
