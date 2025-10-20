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
    
    // ============================================================================================================
    // HOME URL - Define para onde vai após login
    // ============================================================================================================
    'defaultRoute' => 'vendas/inicio/index', // Página inicial padrão
    
    'components' => [
        // ============================================================================================================
        // USER COMPONENT - Login Único (Global)
        // ============================================================================================================
        'user' => [
            'identityClass' => 'app\models\Usuario',  // Model global
            'enableAutoLogin' => true,
            'loginUrl' => ['auth/login'],  // Login global
            'authTimeout' => 1800,
        ],
        
        // ============================================================================================================
        // URL MANAGER
        // ============================================================================================================
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            //'showScriptName' => false,
            'rules' => [
                // Rotas de Autenticação Global
                'login' => 'auth/login',
                'cadastro' => 'auth/signup',
                'signup' => 'auth/signup',
                'logout' => 'auth/logout',
                'esqueci-senha' => 'auth/forgot-password',
                
                // Rotas do módulo vendas (com suporte a UUID)
                'vendas/<controller:\w+>/<action:\w+>/<id:[0-9a-f\-]+>' => 'vendas/<controller>/<action>',
                'vendas/<controller:\w+>/<action:\w+>' => 'vendas/<controller>/<action>',
                'vendas/<controller:\w+>' => 'vendas/<controller>/index',
                'vendas' => 'vendas/inicio/index',
                
                // Home
                '' => 'vendas/inicio/index',
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
        ],
        'assetManager' => [
            'bundles' => [],
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
    
    // ============================================================================================================
    // ACCESS CONTROL GLOBAL - Protege TODOS os módulos
    // ============================================================================================================
    'as beforeRequest' => [
        'class' => 'yii\filters\AccessControl',
        'except' => [
            // Rotas públicas (sem login necessário)
            'auth/login',
            'auth/signup',
            'auth/logout',
            'auth/forgot-password',
            'auth/reset-password',
            'site/error',
        ],
        'rules' => [
            [
                'allow' => true,
                'roles' => ['@'], // Apenas usuários autenticados
            ],
        ],
    ],
    
    // ============================================================================================================
    // MODULES
    // ============================================================================================================
    'modules' => [
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
        
        // ========================================
        // Módulos do Sistema (SEM autenticação própria)
        // ========================================
        'vendas' => [
            'class' => 'app\modules\vendas\Module',
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