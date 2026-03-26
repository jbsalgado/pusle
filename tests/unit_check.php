<?php

// Script de Verificação Unitária (Standalone)
// Valida a lógica dos modelos sem depender do Codeception

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';
(new yii\web\Application($config));

use app\modules\vendas\models\Produto;
use app\modules\vendas\models\VendaItem;

echo "--- Iniciando Verificação de Modelos (Venda Fracionada) ---\n\n";

// 1. Teste de Produto
echo "1. Testando Modelo Produto:\n";
$produto = new Produto();
$produto->venda_fracionada = true;
$produto->estoque_atual = 12.555;

if ($produto->validate(['estoque_atual'])) {
    echo "  [OK] Validação de estoque_atual aceitou 12.555\n";
} else {
    echo "  [FALHA] Validação de estoque_atual rejeitou 12.555: " . json_encode($produto->errors['estoque_atual']) . "\n";
}

if ($produto->venda_fracionada === true) {
    echo "  [OK] Flag venda_fracionada persistida no modelo\n";
} else {
    echo "  [FALHA] Flag venda_fracionada não persistida corretamente\n";
}

// 2. Teste de VendaItem
echo "\n2. Testando Modelo VendaItem:\n";
$item = new VendaItem();
$item->quantidade = 0.535;
$item->preco_unitario_venda = 100.00;

if ($item->validate(['quantidade'])) {
    echo "  [OK] Validação de quantidade aceitou 0.535\n";
} else {
    echo "  [FALHA] Validação de quantidade rejeitou 0.535: " . json_encode($item->errors['quantidade']) . "\n";
}

$subtotal = $item->quantidade * $item->preco_unitario_venda;
if (abs($subtotal - 53.5) < 0.0001) {
    echo "  [OK] Cálculo de subtotal preciso: $subtotal\n";
} else {
    echo "  [FALHA] Cálculo de subtotal impreciso: $subtotal (esperado 53.5)\n";
}

echo "\n--- Verificação Concluída ---\n";
