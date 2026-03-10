<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/web.php';
(new yii\web\Application($config));

$users = Yii::$app->db->createCommand("
    SELECT id, nome, api_de_pagamento, mercadopago_sandbox 
    FROM prest_usuarios 
    WHERE api_de_pagamento = true AND gateway_pagamento = 'mercadopago' AND (mercadopago_access_token IS NOT NULL OR mp_access_token IS NOT NULL)
")->queryAll();

echo json_encode($users, JSON_PRETTY_PRINT);
