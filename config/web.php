<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'pulse-gestao', // Um ID único para a aplicação
    'name' => 'THAUSZ-PULSE GESTÃO',
    'basePath' => dirname(__DIR__),
    'sourceLanguage' => 'pt-BR',
    'language' => 'pt-BR',
    'timeZone' => 'America/Sao_Paulo', // Recomendado: definir o fuso horário
    'charset' => 'UTF-8',
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],

    // Rota padrão do sistema (para onde vai após o login)
    'defaultRoute' => 'dashboard/index',

    'components' => [
        'request' => [
            'cookieValidationKey' => '4b0ff248a157546be93bf1b3ff881897', // <-- IMPORTANTE: Coloque uma chave secreta aqui!
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        
        // ===================================================================
        // ✅ CONFIGURAÇÃO DO USUÁRIO (A PARTE MAIS IMPORTANTE)
        // ===================================================================
        'user' => [
            // Aponta para o seu model de Usuário, que implementa IdentityInterface
            'identityClass' => 'app\models\Usuario', 
            
            // Habilita o login automático (funcionalidade "Lembrar-me")
            'enableAutoLogin' => true, 
            
            // Para onde redirecionar se o usuário tentar acessar uma página restrita sem estar logado
            'loginUrl' => ['auth/login'], 
            
            // Tempo da sessão em segundos (30 minutos)
            'authTimeout' => 1800,
        ],
        
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            
            // Para desenvolvimento (emails salvos em arquivos)
            'useFileTransport' => true,
            
            // Para produção (descomente e configure):
            'useFileTransport' => false,
            'transport' => [
                'scheme' => 'smtp',
                'host' => 'smtp.gmail.com',
                'username' => 'seu-email@gmail.com',
                'password' => 'sua-senha-app',
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
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => true,
            'enableStrictParsing' => false,
            'rules' => [
                // ==========================================================
                // ✅ ADICIONE ESTAS REGRAS PARA A API
                // ==========================================================
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => [
                        'api/produto', // Mapeia para app/modules/api/controllers/ProdutoController
                        'api/pedido',   // Mapeia para app/modules/api/controllers/PedidoController
                        'api/forma-pagamento', // <-- ADICIONAR (singular, Yii pluraliza se 'pluralize'=true)
                        'api/cliente'       // <-- ADICIONAR (singular, Yii pluraliza se 'pluralize'=true)
                    ],
                    'pluralize' => false, // Isso garante que 'api/produto' responda em 'api/produtos'
                                         // e 'api/pedido' responda em 'api/pedidos'
                ],
                
                // ... Adicione outras regras de URL personalizadas aqui, se necessário
            ],
        ],
    ],

    // ===================================================================
    // ✅ MÓDULOS DA APLICAÇÃO
    // ===================================================================
    'modules' => [
        'vendas' => [
            'class' => 'app\modules\vendas\Module',
        ],
        'porrinha' => [
            'class' => 'app\modules\porrinha\Porrinha', // Verifique se o namespace está correto
        ],
        'metricas' => [
            'class' => 'app\modules\indicadores\Metricas', // Verifique se o namespace está correto
        ],
        'saas' => [
            'class' => 'app\modules\servicos\Saas', // Verifique se o namespace está correto
        ],
        'api' => [ 'class' => 'app\modules\api\Module', ],
    ],

    'params' => $params,
];

// Configurações para o ambiente de desenvolvimento (DEV)
if (YII_ENV_DEV) {
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
