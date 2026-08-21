<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'modules' => [
        'vendas' => [
            'class' => 'app\modules\vendas\Vendas',
        ],
        'caixa' => [
            'class' => 'app\modules\caixa\Module',
        ],
        'contas-pagar' => [
            'class' => 'app\modules\contas_pagar\Module',
        ],
        'marketplace' => [
            'class' => 'app\modules\marketplace\Module',
        ],
        'cobranca' => [
            'class' => 'app\modules\cobranca\Module',
        ],
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'formatter' => [
            'class' => 'yii\i18n\Formatter',
            'locale' => 'pt-BR',
            'defaultTimeZone' => 'America/Recife',
            'currencyCode' => 'BRL',
            'decimalSeparator' => ',',
            'thousandSeparator' => '.',
        ],
        'db' => $db,
        'mailer' => [
            'class' => 'yii\symfonymailer\Mailer',
            'viewPath' => '@app/mail',
            'useFileTransport' => false,
            'transport' => [
                'scheme' => 'smtps',
                'host' => 'smtp.gmail.com',
                'username' => 'only.code.cru@gmail.com',
                'password' => 'dxnctubwrfcnbeus',
                'port' => 465,
            ],
        ],
    ],
    'params' => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
