<?php

// Define constants
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

// Register Composer autoloader
require __DIR__ . '/vendor/autoload.php';

// Include Yii class file
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

// Load application configuration
$config = require __DIR__ . '/config/web.php';

// Create application instance
new yii\web\Application($config);

use app\models\Usuario;
use app\components\JwtHelper;

echo "--- Iniciando Verificação JWT ---\n";

// 1. Busca um usuário de teste
$usuario = Usuario::find()->one();

if (!$usuario) {
    die("Nenhum usuário encontrado para teste.\n");
}

echo "Usuário de teste: {$usuario->username} (ID: {$usuario->id})\n";

// 2. Gera o Token
try {
    $token = $usuario->generateJwt();
    echo "✔ Token gerado com sucesso:\n$token\n";
} catch (Exception $e) {
    die("❌ Erro ao gerar token: " . $e->getMessage() . "\n");
}

// 3. Valida o Token manualmente via Helper
$secret = Yii::$app->request->cookieValidationKey;
echo "Segredo usado: " . substr($secret, 0, 5) . "...\n";

$payload = JwtHelper::decode($token, $secret);

if ($payload) {
    echo "✔ Payload decodificado com sucesso:\n";
    print_r($payload);
} else {
    die("❌ Falha ao decodificar token via Helper.\n");
}

// 4. Valida via Model (simulando autenticação)
$identity = Usuario::findIdentityByAccessToken($token);

if ($identity && $identity->id == $usuario->id) {
    echo "✔ findIdentityByAccessToken funcionou corretamente! Identidade recuperada.\n";
} else {
    die("❌ findIdentityByAccessToken falhou.\n");
}

// 5. Teste de Expiração (Token curto)
echo "\n--- Teste de Expiração ---\n";
// Hack para gerar token expirado usando o helper diretamente com payload customizado
$payloadExpirado = [
    'sub' => $usuario->id,
    'iat' => time() - 3600,
    'exp' => time() - 10 // Expirou há 10 segundos
];
$tokenExpirado = JwtHelper::encode($payloadExpirado, $secret);
echo "Token expirado gerado.\n";

$check = JwtHelper::decode($tokenExpirado, $secret);
if ($check === null) {
    echo "✔ Token expirado foi rejeitado corretamente (retornou null).\n";
} else {
    echo "❌ Token expirado foi ACEITO indevidamente!\n";
}

echo "\n--- Verificação Concluída ---\n";
