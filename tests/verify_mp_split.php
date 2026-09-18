<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';
$app = new yii\web\Application($config);

use app\modules\api\controllers\MercadoPagoController;
use yii\base\Module;

echo "🔍 Buscando usuário para teste...\n";

// Ids conhecidos de lojas
$candidatos = [
    '5e449fee-4486-4536-a64f-74aed38a6987', // Top Construções
    'a99a38a9-e368-4a47-a4bd-02ba3bacaa76'  // Catalogo Default
];

$usuario = null;

// TENTATIVA 1: Usuários Preferenciais
foreach ($candidatos as $id) {
    $u = Yii::$app->db->createCommand("SELECT * FROM prest_usuarios WHERE id = :id")
        ->bindValue(':id', $id)
        ->queryOne();

    if ($u) {
        // Verifica token em ambos os campos
        $token = $u['mercadopago_access_token'] ?? $u['mp_access_token'];
        if (!empty($token)) {
            $usuario = $u;
            // Normaliza o token para o teste
            $usuario['mercadopago_access_token'] = $token;
            echo "✅ Usuário encontrado: " . $u['nome'] . " (ID: $id)\n";
            break;
        } else {
            echo "⚠️ Usuário encontrado ($id), mas SEM TOKEN (MP: " . ($u['mercadopago_access_token'] ? 'Sim' : 'Não') . ", OAuth: " . ($u['mp_access_token'] ? 'Sim' : 'Não') . ")\n";
        }
    }
}

if (!$usuario) {
    echo "⚠️ Nenhum usuário preferencial válido. Buscando qualquer um com MP configurado...\n";
    // Tenta encontrar alguém com token
    $usuario = Yii::$app->db->createCommand(
        "
        SELECT * FROM prest_usuarios 
        WHERE (mercadopago_access_token IS NOT NULL OR mp_access_token IS NOT NULL) 
        LIMIT 1"
    )->queryOne();

    if ($usuario) {
        $usuario['mercadopago_access_token'] = $usuario['mercadopago_access_token'] ?? $usuario['mp_access_token'];
        // Força api_de_pagamento true para teste se tiver token
        if (!$usuario['api_de_pagamento']) {
            echo "⚠️ Usuário tem token mas api_de_pagamento=false. Forçando true para teste.\n";
            $usuario['api_de_pagamento'] = true;
            // Nota: Isso não altera o banco, apenas o array local, mas o controller lê do banco...
            // O controller faz: $usuario = $this->buscarUsuarioPorId($id);
            // ENTÃO NÃO ADIANTA MUDAR AQUI.
            // Precisamos achar um que tenha api_de_pagamento = true
        }
    }
}

if (!$usuario) {
    echo "❌ FALHA: Nenhum usuário com Mercado Pago configurado encontrado no banco. Impossível testar integração real.\n";
    // Vamos listar alguns usuários para debug
    $debugUsers = Yii::$app->db->createCommand("SELECT id, nome, api_de_pagamento, gateway_pagamento FROM prest_usuarios LIMIT 5")->queryAll();
    print_r($debugUsers);
    exit(1);
}

// Se o usuário tem token mas api_de_pagamento false, o controller vai bloquear.
// Vamos verificar isso.
$dbUser = Yii::$app->db->createCommand("SELECT api_de_pagamento FROM prest_usuarios WHERE id = :id")->bindValue(':id', $usuario['id'])->queryScalar();
if (!$dbUser) {
    echo "⚠️ Usuário tem token mas api_de_pagamento está DESATIVADO no banco. O teste irá falhar propositalmente.\n";
    // Se quisermos forçar o sucesso do teste, teríamos que atualizar o banco temporariamente.
    // Melhor não alterar dados de produção.
}

echo "👤 Testando com usuário: " . $usuario['nome'] . "\n";
echo "🔐 Token configurado: " . (substr($usuario['mercadopago_access_token'], 0, 10) . '...') . "\n";

// Simulando Payload
$payload = [
    'usuario_id' => $usuario['id'],
    'itens' => [
        [
            'nome' => 'Produto Teste Split',
            'descricao' => 'Teste de verificação automatizada',
            'quantidade' => 1,
            'preco_unitario' => 100.00
        ]
    ],
    'cliente' => [
        'email' => 'test_user_qa@test.com',
        'nome' => 'QA',
        'sobrenome' => 'Tester'
    ],
    'ambiente' => 'sandbox' // Forçar sandbox se possível, ou confiar na config do user
];

// Injetando dados no Request do Yii
Yii::$app->request->setBodyParams($payload);

// Instanciando Controller
// O controller precisa de um módulo para ser instanciado, criamos um dummy ou pegamos o real
$apiModule = Yii::$app->getModule('api');
if (!$apiModule) {
    $apiModule = new Module('api');
}

$controller = new MercadoPagoController('mercado-pago', $apiModule);

echo "🚀 Executando actionCriarPreferencia...\n";

try {
    // Executa a action diretamente
    // Nota: runAction protege com behaviors. Se falhar por auth, chamamos o método direto.
    // Mas actionCriarPreferencia é pública no behavior, então deve passar.
    $result = $controller->runAction('criar-preferencia');

    echo "\n────────────────────────────────────────\n";
    echo "📊 RESULTADO DO TESTE\n";
    echo "────────────────────────────────────────\n";

    if (isset($result['sucesso']) && $result['sucesso']) {
        echo "✅ STATUS: SUCESSO\n";
        echo "🆔 Preference ID: " . $result['preference_id'] . "\n";
        echo "🔗 Init Point: " . $result['init_point'] . "\n";

        if (isset($result['marketplace_fee'])) {
            echo "💰 Marketplace Fee (Split): R$ " . $result['marketplace_fee'] . "\n";
            if ($result['marketplace_fee'] > 0) {
                echo "✅ Lógica de Split aplicada corretamente!\n";
            } else {
                echo "⚠️ Marketplace Fee é zero (verifique calculo, 0.99% de 100 deveria ser 0.99)\n";
            }
        } else {
            echo "❌ ERRO: Marketplace Fee não retornado na resposta!\n";
        }
    } else {
        echo "❌ STATUS: FALHA\n";
        echo "Mensagem: " . ($result['mensagem'] ?? 'N/A') . "\n";
        echo "Motivo: " . ($result['motivo'] ?? 'N/A') . "\n";
        if (isset($result['detalhes'])) print_r($result['detalhes']);
    }
} catch (\Exception $e) {
    echo "\n❌ EXCEÇÃO FATAL: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString();
} catch (\Throwable $t) {
    echo "\n❌ ERRO FATAL: " . $t->getMessage() . "\n";
}
