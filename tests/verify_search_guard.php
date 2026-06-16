<?php

// tests/verify_search_guard.php

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config/console.php');
new yii\console\Application($config);

use app\modules\vendas\models\Produto;

echo "🔍 Verificando Trava de Segurança na Busca...\n";
echo "------------------------------------------\n";

$usuarioId = '5e449fee-4486-4536-a64f-74aed38a6987';

// 1. Encontra um Mestre que tenha filhos
$mestre = Produto::find()
    ->where(['parent_id' => null, 'usuario_id' => $usuarioId])
    ->andWhere(['IN', 'id', (new \yii\db\Query())->select('parent_id')->from('prest_produtos')->where(['IS NOT', 'parent_id', null])])
    ->one();

if ($mestre) {
    echo "Mestre Identificado: {$mestre->nome} ({$mestre->id})\n";
    
    // Simula a busca da API
    $query = Produto::find()
        ->where(['ativo' => true, 'usuario_id' => $usuarioId])
        ->andWhere([
            'OR',
            ['IS NOT', 'parent_id', null],
            ['NOT IN', 'id', (new \yii\db\Query())->select('parent_id')->from('prest_produtos')->where(['IS NOT', 'parent_id', null])]
        ]);
    
    $idsNoResultado = $query->column();
    
    if (in_array($mestre->id, $idsNoResultado)) {
        echo "❌ FAIL: O produto Mestre AINDA aparece na busca!\n";
    } else {
        echo "✅ PASS: O produto Mestre foi OCULTADO da busca.\n";
    }

    // Verifica se os filhos aparecem
    $filho = Produto::find()->where(['parent_id' => $mestre->id])->one();
    if ($filho && in_array($filho->id, $idsNoResultado)) {
        echo "✅ PASS: A variação '{$filho->nome}' APARECE na busca.\n";
    } else {
        echo "❌ FAIL: As variações sumiram da busca!\n";
    }
} else {
    echo "ℹ️ Nenhum produto mestre com variações encontrado para testar.\n";
}

echo "------------------------------------------\n";
echo "🏁 Verificação concluída.\n";
