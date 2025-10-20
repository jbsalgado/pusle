<?php
$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'name' => 'THAUSZ-PULSE GESTÃO',
    'sourceLanguage' => 'pt-BR',
    'language' => 'pt-BR',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
        // Substituímos o identityClass do Dektrium pelo nosso modelo
        'user' => [
            'identityClass' => 'app\models\Users',  // NOSSO MODELO PERSONALIZADO
            'enableAutoLogin' => true,
            'loginUrl' => ['login/index'],  // Rota para nosso controlador de login
            'authTimeout' => 1800, // auth expire in 30 minutes
        ],
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            //'showScriptName' => false,
            // Adicionamos regras para nossos controladores personalizados
            'rules' => [
                'signup' => 'signup/index',     // Cadastro
                'login' => 'login/index',       // Login
                'logout' => 'login/logout',    // Logout
                'profile' => 'profile/index',  // Perfil do usuário
            ],
        ],
        'request' => [
            'cookieValidationKey' => 'qJpZnsb8NKCPDiyTsGhzeEfJGUcQY5bI',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'view' => [
            'class' => 'yii\web\View',
            // 'theme' => [
            //     'class' => 'yii\base\Theme',
            //     'pathMap' => [
            //         '@app/views' => '@app/themes/lte'
            //     ],
            // ],
        ],
        'assetManager' => [
            'bundles' => [
                // 'dmstr\web\AdminLteAsset' => [
                //     'skin' => 'skin-green',
                // ],
            ],
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.gmail.com',
                'username' => '',
                'password' => '',
                'port' => '587',
                'encryption' => 'tls',
            ],
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
        'i18n' => [
            'translations' => [
                '*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'en',
                    'fileMap' => [],
                ],
            ],
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
            'defaultRoles' => ['guest', 'user'],
        ],
    ],
    'as beforeRequest' => [
    'class' => 'yii\filters\AccessControl',
    'rules' => [
        // 🔴 GARANTA QUE TODAS AS AÇÕES PERMITIDAS ESTÃO LISTADAS
        [
            'actions' => ['login', 'error', 'forgot', 'register', 'resend', 'signup'],
            'allow' => true,
        ],
        [
            'allow' => true,
            'roles' => ['@'], // Permite acesso a usuários logados
        ],
    ],
   ] ,
    'modules' => [
        // REMOVEMOS O MÓDULO DEKTRIUM E ADICIONAMOS NOSSOS CONTROLADORES
        'rbac' => [
            'class' => 'yii2mod\rbac\Module',
        ],
        'gridview' => [
            'class' => 'kartik\grid\Module',
        ],
        'datecontrol' => [
            'class' => 'kartik\datecontrol\Module',
            'displaySettings' => [
                'date' => 'd-m-Y',
                'time' => 'H:i:s A',
                'datetime' => 'd-m-Y H:i:s A',
            ],
            'saveSettings' => [
                'date' => 'Y-m-d',
                'time' => 'H:i:s',
                'datetime' => 'Y-m-d H:i:s',
            ],
            'autoWidget' => true,
        ],
        'porrinha' => [
            'class' => 'app\modules\porrinha\Porrinha',
        ],
        'metricas' => [
            'class' => 'app\modules\indicadores\Metricas',
        ],
        'saas' => [
            'class' => 'app\modules\servicos\Saas',
        ],
        'vendas' => [ 'class' => 'app\modules\vendas\Vendas', ],
    ],
    'params' => $params,
];

// Configurações de ambiente de desenvolvimento
if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'generators' => [
            'kartikgii-crud' => ['class' => 'warrence\kartikgii\crud\Generator'],
            'crud' => [
                'class' => 'yii\gii\generators\crud\Generator',
                'templates' => [
                    'adminlte' => '@vendor/dmstr/yii2-adminlte-asset/gii/templates/crud/simple',
                ],
            ],
        ],
    ];
}

return $config;
