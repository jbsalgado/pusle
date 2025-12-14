<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

// Detecta automaticamente se deve mostrar index.php nas URLs
// Se a REQUEST_URI contém /index.php, usa index.php nas URLs geradas
// Caso contrário, assume que o .htaccess está funcionando e não usa index.php
$showScriptName = false; // Padrão: não mostra index.php (assume .htaccess funcionando)
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/index.php') !== false) {
    $showScriptName = true; // Se a URL atual tem index.php, usa nas URLs geradas
}

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
            // Detecta automaticamente o baseUrl baseado no SCRIPT_NAME
            // Isso permite que funcione tanto com /pulse/web/index.php quanto sem
            // Na VPS, se o DocumentRoot está em /pulse/web, o SCRIPT_NAME será /index.php
            // Se o DocumentRoot está na raiz, o SCRIPT_NAME será /pulse/web/index.php
            'baseUrl' => (function() {
                if (!isset($_SERVER['SCRIPT_NAME'])) {
                    return '';
                }
                
                $scriptName = $_SERVER['SCRIPT_NAME'];
                
                // Remove /index.php do final do SCRIPT_NAME para obter o baseUrl
                $baseUrl = dirname($scriptName);
                
                // Normaliza o caminho (remove barras duplas, pontos, etc)
                $baseUrl = str_replace(['\\', '//'], '/', $baseUrl);
                $baseUrl = rtrim($baseUrl, '/');
                
                // Se o baseUrl for apenas /, retorna vazio
                if ($baseUrl === '/' || $baseUrl === '' || $baseUrl === '.') {
                    return '';
                }
                
                // Retorna o baseUrl normalizado (garante que comece com /)
                return $baseUrl;
            })(),
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
            // Detecta automaticamente se deve mostrar index.php baseado na URL atual
            // Se a REQUEST_URI contém /index.php, usa index.php nas URLs geradas
            // Caso contrário, assume que o .htaccess está funcionando e não usa index.php
            'showScriptName' => $showScriptName,
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

