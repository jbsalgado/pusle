<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/console.php';
new yii\console\Application($config);

echo "🧪 Iniciando Testes Phase 3...\n";

// 1. Testar TelegramHelper (Simulação)
echo "\n1. Testando TelegramHelper...\n";
$msg = "🧪 Teste de Alerta Pulse: " . date('H:i:s');
// Como não temos tokens reais, esperamos que retorne false/warning, mas não erro fatal
$res = \app\components\TelegramHelper::sendMessage($msg);
echo "   - Resultado envio: " . ($res ? "✅ SUCESSO" : "⚠️ ESPERADO (Sem tokens)") . "\n";

// 2. Testar Formatação de Alerta
echo "\n2. Testando Formatação de Alerta...\n";
$mockVenda = new \stdClass();
$mockVenda->valor_total = 150.50;
$mockVenda->is_pwa = true;
$mockVenda->cliente = null;

$msgFormatada = \app\components\TelegramHelper::formatVendaAlerta($mockVenda);
echo "   - Mensagem Formatada:\n$msgFormatada\n";
if (strpos($msgFormatada, '150,50') !== false && strpos($msgFormatada, 'PWA') !== false) {
    echo "   ✅ Formatação OK\n";
} else {
    echo "   ❌ Erro na Formatação\n";
}

// 3. Testar Lógica de Webhook (Simulando find no banco)
echo "\n3. Testando Lógica de Webhook (Mock)...\n";
// Criamos uma parcela de teste se possível ou apenas validamos a existência do campo
$parcela = new \app\modules\vendas\models\Parcela();
if ($parcela->hasAttribute('id_integracao_externa')) {
    echo "   ✅ Campo id_integracao_externa existe no modelo (via hasAttribute)\n";
} else {
    echo "   ❌ Campo id_integracao_externa NÃO encontrado em attributes()\n";
    echo "   Atributos disponíveis: " . implode(', ', array_keys($parcela->attributes)) . "\n";
}

echo "\n✨ Testes Concluídos!\n";
