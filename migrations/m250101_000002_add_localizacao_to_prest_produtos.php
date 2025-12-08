<?php
/**
 * Migration: Adicionar campo localizacao à tabela prest_produtos
 * 
 * Execute: php yii migrate
 */

use yii\db\Migration;

class m250101_000002_add_localizacao_to_prest_produtos extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Adicionar coluna localizacao (varchar(30))
        $this->addColumn('{{%prest_produtos}}', 'localizacao', $this->string(30)->defaultValue(null)->after('ponto_corte'));
        
        // Comentário na coluna
        $this->addCommentOnColumn('{{%prest_produtos}}', 'localizacao', 'Localização física onde o produto está armazenado (ex: Prateleira A3, Estoque 2, etc.)');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%prest_produtos}}', 'localizacao');
    }
}

