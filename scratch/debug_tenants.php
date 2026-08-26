<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
$config = require __DIR__ . '/../config/web.php';
new yii\web\Application($config);

$db = Yii::$app->db;
echo "=== PREST_USUARIOS ===\n";
$users = $db->createCommand("SELECT id, nome, email, username, eh_dono_loja, is_admin FROM prest_usuarios")->queryAll();
print_r($users);

echo "\n=== PREST_COLABORADORES ===\n";
$colabs = $db->createCommand("SELECT id, usuario_id, prest_usuario_login_id, nome_completo, email, eh_vendedor, eh_cobrador, eh_administrador, ativo FROM prest_colaboradores")->queryAll();
print_r($colabs);
