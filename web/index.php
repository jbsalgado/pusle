<?php
<<<<<<< HEAD

=======
>>>>>>> 1807f8af550fdb45e5b96858ecc61281d2ecd2ae
require __DIR__ . '/../vendor/autoload.php';

// Carrega as variáveis de ambiente do arquivo .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

defined('YII_DEBUG') or define('YII_DEBUG', isset($_ENV['YII_DEBUG']) ? filter_var($_ENV['YII_DEBUG'], FILTER_VALIDATE_BOOLEAN) : false);
defined('YII_ENV') or define('YII_ENV', $_ENV['YII_ENV'] ?? 'prod');
<<<<<<< HEAD

=======
>>>>>>> 1807f8af550fdb45e5b96858ecc61281d2ecd2ae
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
