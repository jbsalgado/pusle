<?php
/**
 * Migration: Garantir constraint UNIQUE para codigo_referencia por usuario_id
 * 
 * Execute: php yii migrate
 */

use yii\db\Migration;

class m250101_000003_ensure_codigo_referencia_unique extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Remove constraint se já existir (para evitar erro se já existir)
        $this->execute("
            ALTER TABLE {{%prest_produtos}} 
            DROP CONSTRAINT IF EXISTS prest_produtos_usuario_id_codigo_referencia_key;
        ");
        
        // Adiciona constraint UNIQUE para garantir que codigo_referencia seja único por usuario_id
        // Permite NULL (produtos sem código de referência)
        $this->execute("
            ALTER TABLE {{%prest_produtos}} 
            ADD CONSTRAINT prest_produtos_usuario_id_codigo_referencia_key 
            UNIQUE (usuario_id, codigo_referencia);
        ");
        
        // Comentário na constraint
        $this->addCommentOnColumn('{{%prest_produtos}}', 'codigo_referencia', 'Código de referência único por usuário. Deve ser único para cada prestanista.');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute("
            ALTER TABLE {{%prest_produtos}} 
            DROP CONSTRAINT IF EXISTS prest_produtos_usuario_id_codigo_referencia_key;
        ");
    }
}

