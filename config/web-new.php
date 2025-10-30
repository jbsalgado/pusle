<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'pulse-gestao',
    'name' => 'THAUSZ-PULSE GESTÃO',
    'basePath' => dirname(__DIR__),
    'sourceLanguage' => 'pt-BR',
    'language' => 'pt-BR',
    'timeZone' => 'America/Sao_Paulo',
    'charset' => 'UTF-8',
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],

    // ✅ AJUSTE 1: Rota padrão corrigida
    'defaultRoute' => 'site/index',

    'components' => [
        'request' => [
            'cookieValidationKey' => '4b0ff248a157546be93bf1b3ff881897',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],

        'user' => [
            'identityClass' => 'app\models\Usuario',
            'enableAutoLogin' => true,
            'loginUrl' => ['auth/login'],
            'authTimeout' => 1800,
        ],

        // ✅ AJUSTE 2: Corrigido o local da ação de erro
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            'useFileTransport' => false, // Defina como false para enviar emails reais
            'transport' => [
                'scheme' => 'smtp',
                'host' => 'smtp.gmail.com', // Exemplo para Gmail
                'username' => 'seu-email@gmail.com', // Substitua pelo seu email
                'password' => 'sua-senha-app', // Substitua pela sua senha de app do Gmail
                'port' => 587,
                'encryption' => 'tls',
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                     // Adicione 'info' se quiser logs mais detalhados
                    // 'levels' => ['error', 'warning', 'info'],
                    // 'logVars' => [], // Descomente para não logar $_SERVER, $_GET, etc.
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info', 'error', 'warning'],
                    'categories' => ['mercadopago', 'asaas'], // Logs específicos
                    'logFile' => '@runtime/logs/pagamentos.log',
                    'maxFileSize' => 10240, // 10MB
                    // 'logVars' => [], // Descomente para não logar $_SERVER, $_GET, etc.
                ],
                 // ✅ Log específico para API
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info', 'error', 'warning'], // Adicione 'trace' para debug extremo
                    'categories' => ['api'], // Categoria 'api' usada nos logs do ClienteController
                    'logFile' => '@runtime/logs/api.log',
                    'maxFileSize' => 10240, // 10MB
                    'logVars' => [], // Descomente para não logar $_SERVER, $_GET, etc. em logs da API
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => true, 
            'enableStrictParsing' => true, 
            'rules' => [
                
                // ✅ AJUSTE 3: Regras explícitas para Auth e Módulos
                // Autenticação
                'auth/<action:\w+>' => 'auth/<action>',
                
                // Módulo Vendas
                'vendas' => 'vendas/dashboard/index', // Rota padrão para /vendas
                'vendas/dashboard' => 'vendas/dashboard/index', // Rota explícita
                'vendas/<controller:\w+>/<action:\w+>' => 'vendas/<controller>/<action>',
                
                // ✅ REGRA DO WEBHOOK (MOVIDA PARA CIMA E REFORÇADA)
                // Usando a sintaxe de array para garantir que o verbo POST seja forçado
                [
                    'pattern' => 'api/asaas/webhook',
                    'route' => 'api/asaas/webhook',
                    'verb' => ['POST'],
                ],
                
                // ROTAS CUSTOMIZADAS (Abaixo do webhook)
                'POST api/mercado-pago/criar-preferencia' => 'api/mercado-pago/criar-preferencia',
                'POST api/asaas/criar-cobranca' => 'api/asaas/criar-cobranca',
                
                // ==========================================================
                // ✅ AJUSTE: REGRA PARA O POLLING (VERIFICADOR) DE STATUS
                // ==========================================================
                'GET api/asaas/consultar-status' => 'api/asaas/consultar-status',

                // ROTAS CUSTOMIZADAS - USUÁRIO/LOJA/COLABORADOR/CALCULO
                'GET api/usuario/config' => 'api/usuario/config',
                'GET api/colaborador/buscar-cpf' => 'api/colaborador/buscar-cpf',
                'GET api/calculo/calcular-parcelas' => 'api/calculo/calcular-parcelas',

                // REGRA ESPECÍFICA PARA CLIENTE
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'api/cliente',
                    'pluralize' => false,
                    'patterns' => [
                        'GET <id>' => 'view',    
                        'GET'      => 'index',   
                        'POST'     => 'create',  
                    ],
                    'extraPatterns' => [
                        'GET buscar-cpf' => 'buscar-cpf',
                        'POST login'     => 'login',
                    ],
                ],

                // ROTAS DA API REST (Padrão Yii2) - SEM 'api/cliente'
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => [
                        'api/produto',
                        'api/pedido',
                        'api/forma-pagamento',
                        'api/colaborador',
                    ],
                    'pluralize' => false,
                ],
                // Adicione outras regras específicas aqui, se necessário
                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
                '<module:\w+>/<controller:\w+>/<action:\w+>' => '<module>/<controller>/<action>',
            ],
        ],
        // ... (outros componentes)
    ], // Fim do array 'components'

    // ===================================================================
    // MÓDULOS DA APLICAÇÃO
    // ===================================================================
    'modules' => [
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
        'api' => [
            'class' => 'app\modules\api\Module',
        ],
        // Gii e Debug serão adicionados abaixo se YII_ENV_DEV for true
    ], // Fim do array 'modules'

    'params' => $params,

]; 

// Configurações para o ambiente de desenvolvimento (DEV)
if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
}