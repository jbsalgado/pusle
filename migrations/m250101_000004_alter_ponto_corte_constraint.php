<?php
/**
 * Migration: Alterar constraint de ponto_corte para ser >= estoque_minimo
 * 
 * Execute: php yii migrate
 */

use yii\db\Migration;

class m250101_000004_alter_ponto_corte_constraint extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Remove constraint antiga se existir
        $this->execute("
            ALTER TABLE {{%prest_produtos}} 
            DROP CONSTRAINT IF EXISTS check_ponto_corte_menor_igual_minimo;
        ");
        
        // Atualiza produtos existentes que não atendem a nova regra
        // Se ponto_corte < estoque_minimo, ajusta ponto_corte = estoque_minimo
        $this->execute("
            UPDATE {{%prest_produtos}} 
            SET ponto_corte = estoque_minimo 
            WHERE ponto_corte < estoque_minimo;
        ");
        
        // Adiciona nova constraint: ponto_corte >= estoque_minimo
        $this->execute("
            ALTER TABLE {{%prest_produtos}} 
            ADD CONSTRAINT check_ponto_corte_maior_igual_minimo 
            CHECK (ponto_corte >= estoque_minimo);
        ");
        
        // Atualiza comentário na coluna
        $this->execute("
            COMMENT ON COLUMN {{%prest_produtos}}.ponto_corte IS 'Ponto de corte (reorder point). Deve ser maior ou igual ao estoque mínimo. Quando o estoque atual chegar neste valor, é recomendado fazer resuprimento urgente.';
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Remove constraint nova
        $this->execute("
            ALTER TABLE {{%prest_produtos}} 
            DROP CONSTRAINT IF EXISTS check_ponto_corte_maior_igual_minimo;
        ");
        
        // Restaura constraint antiga
        $this->execute("
            ALTER TABLE {{%prest_produtos}} 
            ADD CONSTRAINT check_ponto_corte_menor_igual_minimo 
            CHECK (ponto_corte <= estoque_minimo);
        ");
    }
}

