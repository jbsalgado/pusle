<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/console.php';
$application = new yii\console\Application($config);

use app\modules\marketplace\components\MagaluService;
use app\modules\marketplace\components\WebhookSignatureValidator;
use app\modules\marketplace\models\MarketplaceConfig;
use app\modules\marketplace\models\MarketplacePedido;
use app\modules\vendas\models\Produto;
use app\modules\vendas\services\EstoqueService;
use app\modules\vendas\services\EstoqueInsuficienteException;

echo "===============================================================\n";
echo " TESTE DE INTEGRAÇÃO MAGAZINE LUIZA (PULSE ERP) \n";
echo "===============================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($condition, $testName) {
    global $passCount, $failCount;
    if ($condition) {
        echo "[PASS] $testName\n";
        $passCount++;
    } else {
        echo "[FAIL] $testName\n";
        $failCount++;
    }
}

// -------------------------------------------------------------
// TESTE 1: Webhook Signature & Token Validation (Inbound)
// -------------------------------------------------------------
echo "\n--- Frente 1: Ingestão de Webhook & Validador de Assinatura ---\n";
$validator = new WebhookSignatureValidator();
$secret = 'magalu_secret_key_123';
$payload = json_encode(['order_id' => 'LU-987654321', 'status' => 'approved']);

// 1.1 Token Header
$headersToken = ['x-auth-token' => $secret];
$validToken = $validator->validateMagalu($headersToken, $payload, $secret);
assertTest($validToken === true, "Validação com header X-Auth-Token");

// 1.2 Bearer Token
$headersBearer = ['authorization' => 'Bearer ' . $secret];
$validBearer = $validator->validateMagalu($headersBearer, $payload, $secret);
assertTest($validBearer === true, "Validação com Authorization: Bearer <token>");

// 1.3 HMAC-SHA256 Signature
$hmac = hash_hmac('sha256', $payload, $secret);
$headersHmac = ['x-signature' => $hmac];
$validHmac = $validator->validateMagalu($headersHmac, $payload, $secret);
assertTest($validHmac === true, "Validação com header X-Signature (HMAC-SHA256)");

// 1.4 Invalid Signature
$headersInvalid = ['x-signature' => 'invalid_hash'];
$invalidHmac = $validator->validateMagalu($headersInvalid, $payload, $secret);
assertTest($invalidHmac === false, "Rejeição de assinatura inválida");

// -------------------------------------------------------------
// TESTE 2: Separação de Logística (Magalu Entregas vs Envio Próprio)
// -------------------------------------------------------------
echo "\n--- Frente 2: Separação de Logística (Dual Logistics) ---\n";

$mockMagaluConfig = new MarketplaceConfig();
$mockMagaluConfig->marketplace = MarketplaceConfig::MARKETPLACE_MAGAZINE_LUIZA;
$mockMagaluConfig->access_token = 'mock_token';
$magaluService = new MagaluService($mockMagaluConfig);

// 2.1 Payload Magalu Entregas
$orderMagaluEntregas = [
    'id' => 'ORD-ML-1001',
    'status' => 'approved',
    'total_amount' => 150.00,
    'shipping' => [
        'carrier' => 'MAGALU',
        'type' => 'MAGALU_ENTREGAS',
        'cost' => 15.00,
        'tracking_code' => 'MG123456789BR'
    ],
    'customer' => [
        'name' => 'Consumidor Magalu',
        'cpf' => '12345678901'
    ],
    'items' => [
        [
            'id' => 'ITEM-1',
            'sku' => 'SKU-TESTE-1',
            'title' => 'Produto Teste',
            'quantity' => 1,
            'price' => 135.00
        ]
    ]
];

$dtoEntregas = $magaluService->normalizeOrderToDTO($orderMagaluEntregas);
assertTest($dtoEntregas->logisticType === 'MAGALU_ENTREGAS', "DTO detectou logisticType MAGALU_ENTREGAS");
assertTest($dtoEntregas->shippingCarrier === 'Magalu Entregas', "DTO definiu transportadora como 'Magalu Entregas'");
assertTest($magaluService->isMagaluEntregas($orderMagaluEntregas) === true, "MagaluService::isMagaluEntregas() retornou true");

// 2.2 Payload Envio Próprio
$orderEnvioProprio = [
    'id' => 'ORD-ML-1002',
    'status' => 'approved',
    'total_amount' => 200.00,
    'shipping' => [
        'carrier' => 'Jadlog',
        'type' => 'DIRECT',
        'cost' => 20.00,
        'tracking_code' => 'JAD987654'
    ],
    'customer' => [
        'name' => 'Cliente Envio Proprio',
        'cpf' => '98765432100'
    ],
    'items' => []
];

$dtoProprio = $magaluService->normalizeOrderToDTO($orderEnvioProprio);
assertTest($dtoProprio->logisticType === 'DIRECT', "DTO detectou logisticType DIRECT (Envio Próprio)");
assertTest($dtoProprio->shippingCarrier === 'Jadlog', "DTO definiu transportadora correta para Envio Próprio");
assertTest($magaluService->isMagaluEntregas($orderEnvioProprio) === false, "MagaluService::isMagaluEntregas() retornou false");

// 2.3 Model MarketplacePedido - Bloqueio de Rastreio Manual em Magalu Entregas
$pedidoEntregas = new MarketplacePedido();
$pedidoEntregas->marketplace = MarketplaceConfig::MARKETPLACE_MAGAZINE_LUIZA;
$pedidoEntregas->transportadora = 'Magalu Entregas';
$pedidoEntregas->dados_completos = $orderMagaluEntregas;
assertTest($pedidoEntregas->isMagaluEntregas() === true, "MarketplacePedido::isMagaluEntregas() retornou true");
assertTest($pedidoEntregas->permiteRastreioManual() === false, "MarketplacePedido::permiteRastreioManual() bloqueou rastreio manual");
assertTest($pedidoEntregas->getMarketplaceNome() === 'Magazine Luiza', "MarketplacePedido::getMarketplaceNome() retornou 'Magazine Luiza'");

// Teste de validação em cenário manual
$pedidoEntregas->scenario = MarketplacePedido::SCENARIO_MANUAL;
$pedidoEntregas->codigo_rastreio = 'RASTREIO_MANUAL_PROIBIDO';
$pedidoEntregas->validate(['codigo_rastreio']);
assertTest($pedidoEntregas->hasErrors('codigo_rastreio'), "Validação impediu inserção manual de rastreio em Magalu Entregas");

// -------------------------------------------------------------
// TESTE 3: Catálogo e Variações (Pai x Filhos & Pré-validações)
// -------------------------------------------------------------
echo "\n--- Frente 3: Catálogo, Variações e Pré-validações ---\n";

// 3.1 NCM Inválido (deve lançar exceção)
$produtoSemNcm = new Produto();
$produtoSemNcm->id = 'mock-prod-1';
$produtoSemNcm->nome = 'Furadeira de Impacto';
$produtoSemNcm->ncm = '123'; // menos de 8 dígitos
$produtoSemNcm->populateRelation('fotos', ['https://catalogo.cloud/fotos/furadeira.jpg']);

try {
    $magaluService->buildProdutoPayload($produtoSemNcm);
    assertTest(false, "buildProdutoPayload deveria rejeitar NCM inválido");
} catch (\Throwable $e) {
    assertTest(strpos($e->getMessage(), 'NCM') !== false, "Rejeição correta de NCM inválido: " . $e->getMessage());
}

// 3.2 Sem Foto (deve lançar exceção)
$produtoSemFoto = new Produto();
$produtoSemFoto->id = 'mock-prod-2';
$produtoSemFoto->nome = 'Furadeira Sem Imagem';
$produtoSemFoto->ncm = '84672100'; // NCM 8 dígitos válido
$produtoSemFoto->populateRelation('fotos', []);

try {
    $magaluService->buildProdutoPayload($produtoSemFoto);
    assertTest(false, "buildProdutoPayload deveria rejeitar produto sem foto");
} catch (\Throwable $e) {
    assertTest(strpos($e->getMessage(), 'imagem') !== false, "Rejeição correta de produto sem foto: " . $e->getMessage());
}

// 3.3 Produto Válido Pai
$produtoValido = new Produto();
$produtoValido->id = '00000000-0000-0000-0000-000000000003';
$produtoValido->codigo_referencia = 'SKU-POLO-3';
$produtoValido->nome = 'Camisa Polo Pulse';
$produtoValido->ncm = '61051000';
$produtoValido->populateRelation('fotos', ['https://catalogo.cloud/fotos/camisa.jpg']);
$produtoValido->preco_venda_sugerido = 100.00;
$produtoValido->estoque_atual = 25;
$produtoValido->marca = 'Pulse Wear';
$produtoValido->descricao = 'Camisa polo 100% algodão pima';
$produtoValido->peso_bruto = 0.350;
$produtoValido->largura_cm = 20.0;
$produtoValido->altura_cm = 5.0;
$produtoValido->comprimento_cm = 25.0;

$payloadPai = $magaluService->buildProdutoPayload($produtoValido);
assertTest($payloadPai['sku'] === 'SKU-POLO-3', "Payload Pai: SKU configurado");
assertTest($payloadPai['title'] === 'Camisa Polo Pulse', "Payload Pai: Título configurado");
assertTest($payloadPai['ncm'] === '61051000', "Payload Pai: NCM 8 dígitos validado");
assertTest($payloadPai['brand'] === 'Pulse Wear', "Payload Pai: Marca configurada");
assertTest(isset($payloadPai['variations'][0]), "Payload Pai: Variações filhas presentes");
assertTest($payloadPai['variations'][0]['dimensions']['weight'] === 0.35, "Variação Filha: Peso configurado (0.35kg)");
assertTest(!empty($payloadPai['variations'][0]['images']), "Variação Filha: Imagens presentes");

// -------------------------------------------------------------
// TESTE 4: Concorrência e Bloqueio Pessimista PostgreSQL
// -------------------------------------------------------------
echo "\n--- Frente 4: Concorrência, Pessimistic Lock & Baixa de Estoque ---\n";

// Criar ou buscar um produto de teste no banco real
$db = Yii::$app->db;
$testProdId = '00000000-0000-0000-0000-000000000999';

// Limpar fixture anterior
$db->createCommand("DELETE FROM prest_produtos WHERE id = :id", [':id' => $testProdId])->execute();

// Obter um usuario_id válido existente para FK
$usuarioId = $db->createCommand("SELECT id FROM prest_usuarios LIMIT 1")->queryScalar();
if (!$usuarioId) {
    // Se não tiver usuário na tabela, usa um ID fictício
    $usuarioId = '00000000-0000-0000-0000-000000000001';
}

$db->createCommand("
    INSERT INTO prest_produtos (id, usuario_id, nome, preco_venda_sugerido, estoque_atual, permite_estoque_negativo, ativo, data_criacao)
    VALUES (:id, :uid, 'Produto Teste Magalu Concorrencia', 50.00, 10, false, true, NOW())
    ON CONFLICT (id) DO UPDATE SET estoque_atual = 10, permite_estoque_negativo = false;
", [':id' => $testProdId, ':uid' => $usuarioId])->execute();

// 4.1 Baixa bem-sucedida com pessimistic lock
$novoSaldo = EstoqueService::baixarEstoque($testProdId, 3, "Pedido Teste Magalu #1");
assertTest($novoSaldo == 7, "Baixa atômica com SELECT FOR UPDATE: saldo reduzido de 10 para 7 (atual: $novoSaldo)");

// 4.2 Baixa de mais 7 (saldo zera)
$novoSaldo2 = EstoqueService::baixarEstoque($testProdId, 7, "Pedido Teste Magalu #2");
assertTest($novoSaldo2 == 0, "Baixa atômica zerando estoque com precisão (atual: $novoSaldo2)");

// 4.3 Tentativa de overselling (deve lançar EstoqueInsuficienteException)
try {
    EstoqueService::baixarEstoque($testProdId, 1, "Tentativa de Overselling PDV Simultaneo");
    assertTest(false, "Deveria ter impedido overselling lançando EstoqueInsuficienteException");
} catch (EstoqueInsuficienteException $e) {
    assertTest(true, "Overselling prevenido com sucesso: " . $e->getMessage());
}

// Limpar produto de teste
$db->createCommand("DELETE FROM prest_produtos WHERE id = :id", [':id' => $testProdId])->execute();

echo "\n===============================================================\n";
echo " RESULTADO DOS TESTES: $passCount PASSOU | $failCount FALHOU \n";
echo "===============================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);
