<?php
require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
$config = require(__DIR__ . '/../config/web.php');
(new yii\web\Application($config));

use app\modules\vendas\models\VendaItem;
use app\modules\vendas\models\Venda;

$lastVenda = Venda::find()->orderBy(['data_venda' => SORT_DESC])->limit(1)->one();

if (!$lastVenda) {
    echo "Nenhuma venda encontrada.\n";
    exit;
}

echo "Ultima Venda: " . $lastVenda->id . " em " . $lastVenda->data_venda . "\n";
echo "Valor Total: " . $lastVenda->valor_total . "\n";
echo "Venda Usuario ID: " . $lastVenda->usuario_id . "\n";
echo "Usuario Logado ID: " . (Yii::$app->user->id ?? 'Null') . "\n\n";

$itens = VendaItem::find()->where(['venda_id' => $lastVenda->id])->all();

foreach ($itens as $item) {
    echo "Item ID: " . $item->id . "\n";
    echo "Produto ID: " . $item->produto_id . "\n";
    echo "Nome (Manual): '" . $item->nome_item_manual . "'\n";
    echo "Nome (Produto): " . ($item->produto ? $item->produto->nome : 'N/A') . "\n";
    echo "Resolvido: " . ($item->avulso_resolvido ? 'SIM' : 'NAO') . "\n";
    echo "-----------------------------------\n";
}
