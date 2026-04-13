<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/web.php';
new yii\web\Application($config);

echo "CWD: " . getcwd() . "\n";
echo "DIR: " . __DIR__ . "\n";
echo "Vendor Alias: " . Yii::getAlias('@vendor') . "\n";
echo "Autoload Real: " . realpath(Yii::getAlias('@vendor') . '/setasign/fpdf/fpdf.php') . "\n";
