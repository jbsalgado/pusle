<?php
/**
 * Migration: Aumentar precisão do campo markup_percentual em prest_produtos
 * 
 * O campo markup_percentual estava definido como DECIMAL(5,2) que permite valores até 999.99.
 * Em produtos com custo muito baixo, o markup pode ultrapassar esse valor (ex: 1100%).
 * 
 * Esta migration aumenta a precisão para DECIMAL(10,2) permitindo valores até 99.999.999,99.
 * 
 * Execute: php yii migrate
 */

use yii\db\Migration;

class m251213_000011_increase_markup_percentual_precision extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Altera a precisão do campo markup_percentual de DECIMAL(5,2) para DECIMAL(10,2)
        $this->execute("
            ALTER TABLE {{%prest_produtos}} 
            ALTER COLUMN markup_percentual TYPE NUMERIC(10, 2);
        ");
        
        // Atualiza o comentário da coluna para refletir a mudança
        $this->execute("
            COMMENT ON COLUMN {{%prest_produtos}}.markup_percentual IS 'Markup em percentual calculado sobre o custo: ((Preço Venda - Custo) / Custo) * 100. Suporta valores até 99.999.999,99%.';
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Reverte para DECIMAL(5,2) - ATENÇÃO: Isso pode causar perda de dados se houver valores > 999.99
        $this->execute("
            ALTER TABLE {{%prest_produtos}} 
            ALTER COLUMN markup_percentual TYPE NUMERIC(5, 2);
        ");
        
        // Restaura o comentário original
        $this->execute("
            COMMENT ON COLUMN {{%prest_produtos}}.markup_percentual IS 'Markup em percentual calculado sobre o custo: ((Preço Venda - Custo) / Custo) * 100';
        ");
    }
}

