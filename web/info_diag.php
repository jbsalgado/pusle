<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';
new yii\web\Application($config);

header('Content-Type: text/plain');
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'N/A') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . "\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "Alias @vendor: " . Yii::getAlias('@vendor') . "\n";
echo "Alias @app: " . Yii::getAlias('@app') . "\n";
echo "Alias @runtime: " . Yii::getAlias('@runtime') . "\n";
echo "FPDF Class exists: " . (class_exists('FPDF') ? 'YES' : 'NO') . "\n";
