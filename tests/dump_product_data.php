<?php

// tests/dump_product_data.php

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config/console.php');
new yii\console\Application($config);

use app\modules\vendas\models\Produto;

$id = 'd4143bf8-cfd0-455a-85dc-13a6c64f99e4';
$mestre = Produto::findOne($id);

if ($mestre) {
    echo "MESTRE:\n";
    echo "ID: {$mestre->id}\n";
    echo "Nome: {$mestre->nome}\n";
    echo "Parent ID: " . ($mestre->parent_id ?: 'NULL') . "\n";
    
    echo "\nVARIACOES:\n";
    $variacoes = Produto::find()->where(['parent_id' => $id])->all();
    foreach ($variacoes as $v) {
        echo "- ID: {$v->id}\n";
        echo "  Nome: {$v->nome}\n";
        echo "  Cor: '{$v->cor}'\n";
        echo "  Tamanho: '{$v->tamanho}'\n";
    }
} else {
    echo "Mestre não encontrado.\n";
}
