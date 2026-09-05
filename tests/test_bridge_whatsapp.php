<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';
new yii\web\Application($config);

use app\models\Usuario;
use app\modules\vendas\models\BridgeWhatsappLoja;
use app\modules\vendas\models\BridgeWhatsappMensagem;
use app\modules\vendas\services\BridgeWhatsappService;

echo "=== TESTANDO ENDPOINTS E SERVIÇOS DO PULSE BRIDGE WHATSAPP ===\n\n";

// 1. Busca uma loja existente
$usuario = Usuario::find()->where(['eh_dono_loja' => true])->one();
if (!$usuario) {
    $usuario = Usuario::find()->one();
}

if (!$usuario) {
    echo "❌ Erro: Nenhum usuário encontrado no banco.\n";
    exit(1);
}

echo "Loja selecionada: {$usuario->nome} (ID: {$usuario->id})\n";

// 2. Obtém/cria configuração da loja
$loja = BridgeWhatsappService::getConfigLoja($usuario->id);
echo "Token do Agente : {$loja->token_agente}\n";
echo "Status Atual    : {$loja->status_conexao}\n\n";

// 3. Testa Handshake via API
$baseUrl = !empty($argv[1]) ? rtrim($argv[1], '/') : 'https://catalogos.oncode.app.br';
echo "Usando Servidor: {$baseUrl}\n";
echo "📡 1. Testando POST /api/bridge-whatsapp/handshake...\n";

$ch = curl_init("{$baseUrl}/api/bridge-whatsapp/handshake");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'token' => $loja->token_agente,
    'version' => '1.0.0',
    'os' => 'test-runner'
]));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "Resposta: {$response}\n\n";

$handshakeData = json_decode($response, true);
if (!$handshakeData || empty($handshakeData['success'])) {
    echo "❌ Falha no Handshake.\n";
    exit(1);
}
echo "✅ Handshake validado com sucesso!\n\n";

// 4. Testa Enfileiramento de Mensagem
echo "📝 2. Enfileirando mensagem de teste via BridgeWhatsappService...\n";
$resEnfileirar = BridgeWhatsappService::enfileirarMensagem(
    $usuario->id,
    '5511999998888',
    'Mensagem de teste unitário do Pulse Bridge WhatsApp'
);
print_r($resEnfileirar);

if (empty($resEnfileirar['success'])) {
    echo "❌ Falha ao enfileirar mensagem.\n";
    exit(1);
}
$msgId = $resEnfileirar['mensagem_id'];
echo "✅ Mensagem ID {$msgId} enfileirada com sucesso!\n\n";

// 5. Simula o Agente conectando o WhatsApp para poder receber tarefas
echo "🟢 3. Simulando status 'connected' no agente...\n";
$loja->status_conexao = BridgeWhatsappLoja::STATUS_CONNECTED;
$loja->telefone_conectado = '5511999998888';
$loja->push_name = 'Loja Teste Pulse';
$loja->save(false);

// 6. Testa Poll via API
echo "🔄 4. Testando GET /api/bridge-whatsapp/poll...\n";
$ch = curl_init("{$baseUrl}/api/bridge-whatsapp/poll?token=" . urlencode($loja->token_agente));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "Resposta: {$response}\n\n";

$pollData = json_decode($response, true);
if (!$pollData || empty($pollData['success']) || ($pollData['type'] ?? '') !== 'send_message') {
    echo "❌ Falha: Poll não retornou a mensagem enfileirada.\n";
    exit(1);
}
echo "✅ Poll retornou a mensagem perfeitamente para envio!\n\n";

// 7. Testa ACK de Entrega
echo "📨 5. Testando POST /api/bridge-whatsapp/ack...\n";
$ch = curl_init("{$baseUrl}/api/bridge-whatsapp/ack");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'token' => $loja->token_agente,
    'id' => $msgId,
    'status' => 'delivered',
    'whatsapp_id' => 'WA_TEST_MSG_' . time()
]));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "Resposta: {$response}\n\n";

// Verifica no banco se o status virou delivered
$msgBanco = BridgeWhatsappMensagem::findOne($msgId);
if ($msgBanco && $msgBanco->status === 'delivered') {
    echo "✅ ACK registrado no banco! Status final: {$msgBanco->status}, WA ID: {$msgBanco->mensagem_id_whatsapp}\n\n";
} else {
    echo "❌ Falha: Status da mensagem não foi atualizado para delivered.\n";
    exit(1);
}

echo "🎉 TODOS OS TESTES DOS ENDPOINTS DO BRIDGE WHATSAPP PASSARAM COM SUCESSO!\n";
