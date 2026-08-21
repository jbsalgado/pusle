<?php
/**
 * Script de Configuração do Proprietário do Sistema (Pulse ERP)
 * Define ALEX PEDRO DA SILVA como Dono da Loja com as credenciais fornecidas.
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
$config = require __DIR__ . '/../config/console.php';

new yii\console\Application($config);

use app\models\Usuario;
use yii\db\Expression;

$email = 'alexpedro06@gmail.com';
$cpf = '03658974427';
$nome = 'ALEX PEDRO DA SILVA';
$senha = '@#628928@#';
$username = 'alexpedro';

echo "=== Configurando proprietario: $nome ($email) ===\n";

// Procura por email, CPF ou username existente
$usuario = Usuario::find()
    ->where(['LOWER(email)' => strtolower($email)])
    ->orWhere(['cpf' => preg_replace('/[^0-9]/', '', $cpf)])
    ->orWhere(['LOWER(username)' => strtolower($username)])
    ->one();

if (!$usuario) {
    // Se não encontrou, pega qualquer dono existente para atualizar ou cria novo
    $usuario = Usuario::find()->where(['eh_dono_loja' => true])->one();
}

if (!$usuario) {
    // Se ainda não existir nenhum usuário, cria novo
    $usuario = new Usuario();
    $usuario->id = sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$usuario->nome = $nome;
$usuario->email = $email;
$usuario->cpf = preg_replace('/[^0-9]/', '', $cpf);
$usuario->username = $username;
$usuario->setPassword($senha);
$usuario->generateAuthKey();
$usuario->eh_dono_loja = true;
$usuario->is_admin = true;
$usuario->status_loja = 'ativa';

if (empty($usuario->telefone)) {
    $usuario->telefone = '00000000000';
}

if ($usuario->save(false)) {
    echo "SUCCESS: Usuario proprietario atualizado/criado com sucesso! ID: {$usuario->id}\n";
    echo "Login: {$usuario->email} ou {$usuario->username}\n";
    echo "CPF: {$usuario->cpf}\n";
} else {
    echo "ERROR ao salvar usuario proprietario:\n";
    print_r($usuario->getErrors());
    exit(1);
}
