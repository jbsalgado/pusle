<?php
define('YII_DEBUG', true);
define('YII_ENV', 'dev');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';
(new yii\web\Application($config));

echo "=== USUARIOS (prest_usuarios) ===\n";
$usuarios = Yii::$app->db->createCommand("SELECT id, nome, email, username FROM prest_usuarios LIMIT 10")->queryAll();
foreach ($usuarios as $u) {
    echo "ID: {$u['id']} | Nome: {$u['nome']} | User: {$u['username']}\n";
}

echo "\n=== WHATSAPP CONFIG (pulse_whatsapp_config) ===\n";
try {
    $configs = Yii::$app->db->createCommand("SELECT * FROM pulse_whatsapp_config")->queryAll();
    if (empty($configs)) {
        echo "Nenhuma configuração de WhatsApp cadastrada no banco!\n";
    } else {
        foreach ($configs as $c) {
            echo "ID: {$c['id']} | Empresa ID: {$c['empresa_id']} | Instance: {$c['instance_name']} | Token: " . (empty($c['token']) ? '[VAZIO]' : '[OK]') . " | Status: {$c['status']}\n";
        }
    }
} catch (\Exception $e) {
    echo "Erro ao consultar pulse_whatsapp_config: " . $e->getMessage() . "\n";
}
