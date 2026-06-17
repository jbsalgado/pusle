<?php
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';

$config = require dirname(__DIR__) . '/config/web.php';
(new yii\web\Application($config));

try {
    // 1. Adicionar coluna nome_item_manual
    Yii::$app->db->createCommand("ALTER TABLE prest_venda_itens ADD COLUMN IF NOT EXISTS nome_item_manual VARCHAR(255)")->execute();
    echo "Coluna nome_item_manual adicionada com sucesso!\n";

    // 2. Garantir Produto Avulso
    $idAvulso = '00000000-0000-0000-0000-000000000000';
    $exists = (new \yii\db\Query())
        ->from('prest_produtos')
        ->where(['id' => $idAvulso])
        ->exists();

    if (!$exists) {
        // Tenta achar um usuário válido para associar
        $usuarioId = (new \yii\db\Query())->from('user')->select('id')->scalar();
        
        Yii::$app->db->createCommand()->insert('prest_produtos', [
            'id' => $idAvulso,
            'usuario_id' => $usuarioId ?: '19ef6c80-fcdb-4cf8-955b-1536f7c2f823', 
            'categoria_id' => null,
            'nome' => 'ITEM AVULSO / DIVERSOS',
            'preco_venda_sugerido' => 0,
            'estoque_atual' => 999999,
            'codigo_referencia' => 'AVULSO',
            'ativo' => true
        ])->execute();
        echo "Produto Âncora (AVULSO) criado!\n";
    }

} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
