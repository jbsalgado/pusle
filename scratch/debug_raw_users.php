<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
$config = require __DIR__ . '/../config/web.php';
new yii\web\Application($config);

$db = Yii::$app->db;
$users = $db->createCommand("SELECT id, nome, email, username, eh_dono_loja, is_admin FROM prest_usuarios")->queryAll();
print_r($users);
