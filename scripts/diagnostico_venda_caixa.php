<?php
/**
 * Script de Diagnóstico - Venda não entrou no Caixa
 * 
 * Uso: php scripts/diagnostico_venda_caixa.php [VENDA_ID]
 * 
 * Este script verifica:
 * 1. Se a venda existe e seus dados
 * 2. Se há caixa aberto para o usuário da venda
 * 3. Se há movimentação criada para a venda
 * 4. Logs relacionados
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/console.php';
new yii\console\Application($config);

$vendaId = $argv[1] ?? null;

if (!$vendaId) {
    echo "❌ Uso: php scripts/diagnostico_venda_caixa.php [VENDA_ID]\n";
    exit(1);
}

echo "🔍 DIAGNÓSTICO - VENDA NÃO ENTROU NO CAIXA\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Buscar venda
echo "1️⃣ VERIFICANDO VENDA...\n";
$venda = \app\modules\vendas\models\Venda::findOne($vendaId);

if (!$venda) {
    echo "❌ Venda não encontrada: {$vendaId}\n";
    exit(1);
}

echo "✅ Venda encontrada:\n";
echo "   - ID: {$venda->id}\n";
echo "   - Usuário ID: {$venda->usuario_id}\n";
echo "   - Cliente ID: " . ($venda->cliente_id ?? 'NULL') . "\n";
echo "   - Status: {$venda->status_venda_codigo}\n";
echo "   - Valor Total: R$ " . number_format($venda->valor_total, 2, ',', '.') . "\n";
echo "   - Data: {$venda->data_venda}\n";
echo "   - Forma Pagamento ID: " . ($venda->forma_pagamento_id ?? 'NULL') . "\n\n";

// 2. Verificar se é venda direta
$isVendaDireta = ($venda->cliente_id === null);
echo "2️⃣ VERIFICANDO TIPO DE VENDA...\n";
if ($isVendaDireta) {
    echo "✅ É VENDA DIRETA (cliente_id é NULL)\n";
} else {
    echo "❌ NÃO É VENDA DIRETA (cliente_id = {$venda->cliente_id})\n";
    echo "   ⚠️  A integração só funciona para vendas diretas!\n";
}
echo "\n";

// 3. Verificar caixa aberto
echo "3️⃣ VERIFICANDO CAIXA ABERTO...\n";
$caixa = \app\modules\caixa\models\Caixa::find()
    ->where(['usuario_id' => $venda->usuario_id, 'status' => \app\modules\caixa\models\Caixa::STATUS_ABERTO])
    ->orderBy(['data_abertura' => SORT_DESC])
    ->one();

if ($caixa) {
    echo "✅ Caixa aberto encontrado:\n";
    echo "   - ID: {$caixa->id}\n";
    echo "   - Valor Inicial: R$ " . number_format($caixa->valor_inicial, 2, ',', '.') . "\n";
    echo "   - Data Abertura: {$caixa->data_abertura}\n";
    $valorEsperado = $caixa->calcularValorEsperado();
    echo "   - Valor Esperado: R$ " . number_format($valorEsperado, 2, ',', '.') . "\n";
} else {
    echo "❌ NENHUM CAIXA ABERTO encontrado para o usuário!\n";
    echo "   ⚠️  Esta é a causa mais provável do problema.\n";
    echo "   💡 Solução: Abrir um caixa em /caixa/caixa/create\n";
}
echo "\n";

// 4. Verificar movimentação
echo "4️⃣ VERIFICANDO MOVIMENTAÇÃO...\n";
$movimentacao = \app\modules\caixa\models\CaixaMovimentacao::find()
    ->where(['venda_id' => $vendaId])
    ->one();

if ($movimentacao) {
    echo "✅ Movimentação encontrada:\n";
    echo "   - ID: {$movimentacao->id}\n";
    echo "   - Caixa ID: {$movimentacao->caixa_id}\n";
    echo "   - Tipo: {$movimentacao->tipo}\n";
    echo "   - Categoria: {$movimentacao->categoria}\n";
    echo "   - Valor: R$ " . number_format($movimentacao->valor, 2, ',', '.') . "\n";
    echo "   - Data: {$movimentacao->data_movimento}\n";
} else {
    echo "❌ NENHUMA MOVIMENTAÇÃO encontrada para esta venda!\n";
}
echo "\n";

// 5. Resumo e diagnóstico
echo "📊 RESUMO E DIAGNÓSTICO:\n";
echo str_repeat("-", 60) . "\n";

$problemas = [];

if (!$isVendaDireta) {
    $problemas[] = "A venda não é venda direta (tem cliente_id). A integração só funciona para vendas diretas.";
}

if (!$caixa) {
    $problemas[] = "Não há caixa aberto. A movimentação não pode ser registrada sem caixa aberto.";
}

if ($isVendaDireta && $caixa && !$movimentacao) {
    $problemas[] = "Venda é direta e há caixa aberto, mas movimentação não foi criada. Verificar logs do sistema.";
}

if (empty($problemas)) {
    echo "✅ Tudo parece estar correto!\n";
    if ($movimentacao) {
        echo "   A movimentação foi criada com sucesso.\n";
    }
} else {
    echo "❌ PROBLEMAS ENCONTRADOS:\n";
    foreach ($problemas as $i => $problema) {
        echo "   " . ($i + 1) . ". {$problema}\n";
    }
}

echo "\n";

// 6. Sugestões
echo "💡 SUGESTÕES:\n";
echo str_repeat("-", 60) . "\n";

if (!$caixa) {
    echo "1. Abrir um caixa: /caixa/caixa/create\n";
    echo "2. Registrar movimentação manualmente para esta venda:\n";
    echo "   - Acessar: /caixa/movimentacao/create?caixa_id=[caixa_id]\n";
    echo "   - Tipo: ENTRADA\n";
    echo "   - Categoria: VENDA\n";
    echo "   - Valor: R$ " . number_format($venda->valor_total, 2, ',', '.') . "\n";
    echo "   - Descrição: Venda #" . substr($vendaId, 0, 8) . "\n";
    echo "   - Venda ID: {$vendaId}\n";
}

if (!$isVendaDireta) {
    echo "1. Verificar como a venda foi criada\n";
    echo "2. Se foi via venda-direta, verificar se cliente foi enviado incorretamente\n";
}

echo "\n";
echo "📝 Verificar logs: tail -f runtime/logs/app.log | grep -i 'caixa\|{$vendaId}'\n";
echo "\n";

