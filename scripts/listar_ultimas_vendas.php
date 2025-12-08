<?php
/**
 * Script para Listar Últimas Vendas
 * 
 * Uso: php scripts/listar_ultimas_vendas.php [LIMITE]
 * 
 * Lista as últimas vendas para facilitar encontrar o ID
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/console.php';
new yii\console\Application($config);

$limite = isset($argv[1]) ? (int)$argv[1] : 5;

echo "📋 ÚLTIMAS VENDAS\n";
echo str_repeat("=", 80) . "\n\n";

$vendas = \app\modules\vendas\models\Venda::find()
    ->orderBy(['data_venda' => SORT_DESC])
    ->limit($limite)
    ->all();

if (empty($vendas)) {
    echo "❌ Nenhuma venda encontrada.\n";
    exit(0);
}

echo sprintf("%-36s | %-12s | %-10s | %-15s | %-19s\n", 
    "ID", "Valor", "Status", "Cliente ID", "Data");
echo str_repeat("-", 80) . "\n";

foreach ($vendas as $venda) {
    $clienteId = $venda->cliente_id ? substr($venda->cliente_id, 0, 8) . '...' : 'NULL (Direta)';
    $status = $venda->status_venda_codigo;
    $valor = 'R$ ' . number_format($venda->valor_total, 2, ',', '.');
    $data = date('d/m/Y H:i', strtotime($venda->data_venda));
    
    echo sprintf("%-36s | %-12s | %-10s | %-15s | %-19s\n",
        $venda->id,
        $valor,
        $status,
        $clienteId,
        $data
    );
}

echo "\n";
echo "💡 Para diagnosticar uma venda, use:\n";
echo "   php scripts/diagnostico_venda_caixa.php [ID_DA_VENDA]\n";
echo "\n";

