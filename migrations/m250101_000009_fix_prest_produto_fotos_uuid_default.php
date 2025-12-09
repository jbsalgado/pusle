<?php

use yii\db\Migration;

/**
 * Class m250101_000009_fix_prest_produto_fotos_uuid_default
 * 
 * Corrige o DEFAULT da coluna id na tabela prest_produto_fotos
 * para usar gen_random_uuid() em vez de uuid_generate_v4()
 * para evitar incompatibilidade com versões do PostgreSQL
 */
class m250101_000009_fix_prest_produto_fotos_uuid_default extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Verifica se a coluna existe e tem o DEFAULT antigo
        $checkSql = "
            SELECT column_default 
            FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = 'prest_produto_fotos' 
            AND column_name = 'id'
        ";
        
        $result = $this->db->createCommand($checkSql)->queryOne();
        
        if ($result && (strpos($result['column_default'], 'uuid_generate_v4') !== false || 
                       strpos($result['column_default'], 'uuid-ossp') !== false)) {
            echo "Alterando DEFAULT da coluna id na tabela prest_produto_fotos...\n";
            
            // Remove o DEFAULT antigo
            $this->execute("ALTER TABLE public.prest_produto_fotos ALTER COLUMN id DROP DEFAULT");
            
            // Adiciona o novo DEFAULT usando gen_random_uuid() (nativo do PostgreSQL 13+)
            $this->execute("ALTER TABLE public.prest_produto_fotos ALTER COLUMN id SET DEFAULT gen_random_uuid()");
            
            echo "✅ Coluna id da tabela prest_produto_fotos atualizada com sucesso.\n";
        } else {
            echo "ℹ️  Coluna id da tabela prest_produto_fotos não usa uuid_generate_v4() ou já está atualizada.\n";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Reverter para uuid_generate_v4() (requer extensão uuid-ossp)
        try {
            $this->execute("ALTER TABLE public.prest_produto_fotos ALTER COLUMN id DROP DEFAULT");
            $this->execute("ALTER TABLE public.prest_produto_fotos ALTER COLUMN id SET DEFAULT public.uuid_generate_v4()");
            echo "Revertido DEFAULT da coluna id na tabela prest_produto_fotos.\n";
        } catch (\Exception $e) {
            echo "⚠️  Erro ao reverter prest_produto_fotos.id: " . $e->getMessage() . "\n";
        }
    }
}

