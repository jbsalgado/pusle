<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'your-secret-key-here-change-in-production',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\Usuario',
            'enableAutoLogin' => true,
            'loginUrl' => ['auth/login'],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\symfonymailer\Mailer',
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true, // Habilitado para suportar rotas REST
            'showScriptName' => true, // Mostra index.php na URL (necessário para funcionar com index.php)
            'rules' => [
                // Regras REST específicas para pedido - POST vai para create
                'POST api/pedido' => 'api/pedido/create',
                'GET api/pedido' => 'api/pedido/index',
                // Regras genéricas para módulo API - suporta hífens em actions
                'api/<controller:\w+>/<action:[\w-]+>' => 'api/<controller>/<action>',
                'api/<controller:\w+>' => 'api/<controller>/index',
            ],
        ],
    ],
    'modules' => [
        'vendas' => [
            'class' => 'app\modules\vendas\Vendas',
        ],
        'indicadores' => [
            'class' => 'app\modules\indicadores\Metricas',
        ],
        'api' => [
            'class' => 'app\modules\api\Module',
        ],
        'caixa' => [
            'class' => 'app\modules\caixa\Module',
        ],
        'contas-pagar' => [
            'class' => 'app\modules\contas-pagar\Module',
        ],
        'servicos' => [
            'class' => 'app\modules\servicos\Saas',
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;

