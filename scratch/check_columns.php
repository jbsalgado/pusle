<?php
require 'vendor/autoload.php';
require 'vendor/yiisoft/yii2/Yii.php';
$config = require 'config/web.php';
new yii\web\Application($config);

$table = Yii::$app->db->getTableSchema('orcamentos');
foreach ($table->columns as $column) {
    echo $column->name . ' (' . $column->dbType . ')' . PHP_EOL;
}
