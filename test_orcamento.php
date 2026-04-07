<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

// Add missing method hack since it's console request, user might not exist in console apps.
// We just mock it if it breaks.
try {
    $model = \app\modules\vendas\models\Orcamento::findOne(119);
    echo "Model exists: " . ($model !== null ? 'Yes' : 'No') . "\n";
    if ($model) {
        print_r($model->attributes);

        echo "\n\nTesting getItens:\n";
        $itens = $model->itens;
        echo count($itens) . " Itens\n";

        if (count($itens) > 0) {
            $item = $itens[0];
            echo "Item attributes:\n";
            print_r($item->attributes);
            echo "\nProduto:\n";
            print_r($item->produto->attributes ?? 'No Produto');
        }

        echo "\n\nTesting actionDetalhes Data:\n";
        echo "Esta Vencido: " . ($model->EstaVencido ? 'Yes' : 'No') . "\n";
    }

    echo "\n\nTesting actionResumo logics:\n";
    $count = \app\modules\vendas\models\Orcamento::find()
        ->where(['status' => 'PENDENTE'])
        ->andWhere(['>=', 'data_validade', date('Y-m-d')])
        ->count();
    echo "Count Resumo: $count\n";
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
} catch (\Error $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
