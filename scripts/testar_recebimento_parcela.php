#!/usr/bin/env php
<?php
/**
 * Script para testar recebimento de parcela e integração com caixa
 * 
 * Uso: php scripts/testar_recebimento_parcela.php [PARCELA_ID]
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/console.php';
new yii\console\Application($config);

use app\modules\vendas\models\Parcela;
use app\modules\caixa\models\Caixa;
use app\modules\caixa\models\CaixaMovimentacao;

// ID da parcela para testar
$parcelaId = $argv[1] ?? null;

if (!$parcelaId) {
    echo "❌ Uso: php scripts/testar_recebimento_parcela.php [PARCELA_ID]\n";
    echo "\n💡 Dica: Use o script listar_ultimas_parcelas.php para encontrar IDs de parcelas pendentes.\n";
    exit(1);
}

echo "🧪 Testando recebimento de parcela: {$parcelaId}\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Buscar parcela
$parcela = Parcela::findOne($parcelaId);
if (!$parcela) {
    echo "❌ Parcela não encontrada!\n";
    exit(1);
}

echo "📋 Parcela encontrada:\n";
echo "   - ID: {$parcela->id}\n";
echo "   - Número: {$parcela->numero_parcela}\n";
echo "   - Valor: R$ " . number_format($parcela->valor_parcela, 2, ',', '.') . "\n";
echo "   - Status: {$parcela->status_parcela_codigo}\n";
echo "   - Vencimento: {$parcela->data_vencimento}\n";
echo "   - Usuário: {$parcela->usuario_id}\n\n";

// 2. Verificar caixa aberto
$caixa = Caixa::find()
    ->where(['usuario_id' => $parcela->usuario_id, 'status' => Caixa::STATUS_ABERTO])
    ->orderBy(['data_abertura' => SORT_DESC])
    ->one();

if (!$caixa) {
    echo "⚠️ Nenhum caixa aberto encontrado!\n";
    echo "   A parcela será marcada como paga, mas não será registrada no caixa.\n\n";
} else {
    $saldoAtual = $caixa->calcularValorEsperado();
    echo "✅ Caixa aberto encontrado:\n";
    echo "   - ID: {$caixa->id}\n";
    echo "   - Valor Inicial: R$ " . number_format($caixa->valor_inicial, 2, ',', '.') . "\n";
    echo "   - Saldo Atual: R$ " . number_format($saldoAtual, 2, ',', '.') . "\n";
    echo "   - Data Abertura: {$caixa->data_abertura}\n\n";
}

// 3. Verificar se já existe movimentação
$movimentacaoExistente = CaixaMovimentacao::find()
    ->where(['parcela_id' => $parcelaId])
    ->one();

if ($movimentacaoExistente) {
    echo "ℹ️ Movimentação já existe para esta parcela:\n";
    echo "   - ID: {$movimentacaoExistente->id}\n";
    echo "   - Valor: R$ " . number_format($movimentacaoExistente->valor, 2, ',', '.') . "\n";
    echo "   - Tipo: {$movimentacaoExistente->tipo}\n";
    echo "   - Categoria: {$movimentacaoExistente->categoria}\n";
    echo "   - Data: {$movimentacaoExistente->data_movimento}\n\n";
    echo "✅ Teste: Prevenção de duplicação funcionando!\n";
    exit(0);
}

// 4. Verificar se parcela já está paga
if ($parcela->status_parcela_codigo === 'PAGA') {
    echo "⚠️ Parcela já está paga!\n";
    echo "   - Data Pagamento: {$parcela->data_pagamento}\n";
    echo "   - Valor Pago: R$ " . number_format($parcela->valor_pago, 2, ',', '.') . "\n\n";
    
    // Verificar se tem movimentação
    if (!$movimentacaoExistente) {
        echo "⚠️ Parcela está paga mas não tem movimentação no caixa.\n";
        echo "   Isso pode acontecer se:\n";
        echo "   - A parcela foi paga antes da integração ser implementada\n";
        echo "   - Não havia caixa aberto quando foi paga\n";
        echo "   - Houve erro ao registrar no caixa\n\n";
    }
    
    echo "💡 Para testar novamente, use uma parcela com status PENDENTE.\n";
    exit(0);
}

// 5. Confirmar ação
echo "🔄 Pronto para marcar parcela como paga.\n";
echo "   Isso irá:\n";
echo "   1. Marcar parcela como PAGA\n";
echo "   2. Registrar entrada no caixa (se houver caixa aberto)\n\n";

// 6. Marcar parcela como paga
echo "🔄 Marcando parcela como paga...\n";
$sucesso = $parcela->registrarPagamento($parcela->valor_parcela);

if (!$sucesso) {
    echo "❌ Erro ao marcar parcela como paga!\n";
    if ($parcela->hasErrors()) {
        echo "   Erros:\n";
        foreach ($parcela->errors as $campo => $erros) {
            foreach ($erros as $erro) {
                echo "   - {$campo}: {$erro}\n";
            }
        }
    }
    exit(1);
}

echo "✅ Parcela marcada como paga!\n\n";

// 7. Verificar movimentação criada
$movimentacao = CaixaMovimentacao::find()
    ->where(['parcela_id' => $parcelaId])
    ->one();

if ($movimentacao) {
    echo "✅ Movimentação criada no caixa:\n";
    echo "   - ID: {$movimentacao->id}\n";
    echo "   - Tipo: {$movimentacao->tipo}\n";
    echo "   - Categoria: {$movimentacao->categoria}\n";
    echo "   - Valor: R$ " . number_format($movimentacao->valor, 2, ',', '.') . "\n";
    echo "   - Descrição: {$movimentacao->descricao}\n";
    echo "   - Data: {$movimentacao->data_movimento}\n\n";
    
    if ($caixa) {
        $novoSaldo = $caixa->calcularValorEsperado();
        echo "💰 Novo saldo do caixa: R$ " . number_format($novoSaldo, 2, ',', '.') . "\n";
        echo "   (Valor inicial: R$ " . number_format($caixa->valor_inicial, 2, ',', '.') . ")\n";
        echo "   (Entradas: R$ " . number_format($novoSaldo - $caixa->valor_inicial, 2, ',', '.') . ")\n\n";
    }
    
    echo "✅ Teste concluído com sucesso!\n";
} else {
    echo "⚠️ Nenhuma movimentação foi criada.\n";
    if (!$caixa) {
        echo "   Motivo: Não há caixa aberto.\n";
        echo "   A parcela foi marcada como paga, mas não foi registrada no caixa.\n";
        echo "   Para registrar manualmente:\n";
        echo "   1. Abra um caixa\n";
        echo "   2. Crie uma movimentação manual associada à parcela\n\n";
    } else {
        echo "   Motivo desconhecido. Verifique os logs do sistema.\n\n";
    }
    echo "⚠️ Teste concluído com aviso.\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 Resumo:\n";
echo "   - Parcela ID: {$parcela->id}\n";
echo "   - Status: {$parcela->status_parcela_codigo}\n";
echo "   - Movimentação: " . ($movimentacao ? "✅ Criada" : "❌ Não criada") . "\n";
echo "   - Caixa: " . ($caixa ? "✅ Aberto" : "❌ Fechado") . "\n";

