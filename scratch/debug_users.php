<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
$config = require __DIR__ . '/../config/web.php';
new yii\web\Application($config);

$users = \app\models\Usuario::find()->all();
foreach ($users as $u) {
    echo "ID: {$u->id}\n";
    echo "Nome: {$u->nome}\n";
    echo "Email: {$u->email}\n";
    echo "eh_dono_loja: " . var_export($u->eh_dono_loja, true) . "\n";
    echo "is_admin: " . var_export($u->is_admin, true) . "\n";
    echo "----------------------------------------\n";
}
