<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%prest_financeiro_mensal}}`.
 */
class m251224_112000_create_financeiro_mensal_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%prest_financeiro_mensal}}', [
            'id' => $this->primaryKey(),
            'usuario_id' => $this->string()->notNull(),
            'mes_referencia' => $this->date()->notNull(),
            'faturamento_total' => $this->decimal(15, 2)->defaultValue(0),
            'despesas_fixas_total' => $this->decimal(15, 2)->defaultValue(0),
            'despesas_variaveis_total' => $this->decimal(15, 2)->defaultValue(0),
            'custo_mercadoria_vendida' => $this->decimal(15, 2)->defaultValue(0),
            'data_criacao' => $this->dateTime()->defaultExpression('NOW()'),
            'data_atualizacao' => $this->dateTime()->defaultExpression('NOW()'),
        ]);

        // Index para busca rápida por usuário e mês
        $this->createIndex(
            '{{%idx-financeiro_mensal-usuario-mes}}',
            '{{%prest_financeiro_mensal}}',
            ['usuario_id', 'mes_referencia'],
            true // Unique para evitar duplicidade do mesmo mês pro mesmo usuário
        );

        // FK para usuário (opcional, dependendo se tabela existe nos seus requisitos, mas boa prática)
        // $this->addForeignKey(...)
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%prest_financeiro_mensal}}');
    }
}
