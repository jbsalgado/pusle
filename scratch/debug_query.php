<?php
require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
$config = require(__DIR__ . '/../config/web.php');
(new yii\web\Application($config));

use app\modules\vendas\models\VendaItem;
use app\modules\vendas\models\Venda;

$lojaId = '5e449fee-4486-4536-a64f-74aed38a6987';

$query = VendaItem::find()
    ->alias('vi')
    ->select([
        'vi.nome_item_manual',
        'COUNT(*) as total_vendas',
        'SUM(vi.quantidade) as total_quantidade',
        'SUM(vi.quantidade * vi.preco_unitario_venda - COALESCE(vi.desconto_valor, 0)) as total_receita',
        'MAX(v.data_venda) as ultima_venda'
    ])
    ->innerJoin(Venda::tableName() . ' v', 'v.id = vi.venda_id')
    ->where(['not', ['vi.nome_item_manual' => null]])
    ->andWhere(['v.usuario_id' => $lojaId])
    ->andWhere(['vi.avulso_resolvido' => false])
    ->groupBy('vi.nome_item_manual')
    ->orderBy(['total_vendas' => SORT_DESC]);

$results = $query->asArray()->all();

echo "Resultados para Loja " . $lojaId . ":\n";
if (empty($results)) {
    echo "Nenhum resultado encontrado.\n";
} else {
    foreach ($results as $row) {
        print_r($row);
    }
}

// Verifica se existem os itens individualmente
$count = VendaItem::find()
    ->alias('vi')
    ->innerJoin(Venda::tableName() . ' v', 'v.id = vi.venda_id')
    ->where(['not', ['vi.nome_item_manual' => null]])
    ->andWhere(['v.usuario_id' => $lojaId])
    ->andWhere(['vi.avulso_resolvido' => false])
    ->count();

echo "\nTotal individual de itens pendentes: " . $count . "\n";
