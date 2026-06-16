<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Histórico de Compras por Produto';
$this->params['breadcrumbs'][] = ['label' => 'Compras', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    
    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
            <div class="flex flex-wrap gap-2">
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                    ['index'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300']
                ) ?>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">
        
        <!-- Seleção de Produto -->
        <div class="bg-white rounded-lg shadow-md mb-6 p-6">
            <form method="get" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Selecione o Produto</label>
                    <select name="produto_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="this.form.submit()">
                        <option value="">Selecione um produto...</option>
                        <?php foreach ($produtos as $p): ?>
                            <option value="<?= $p->id ?>" <?= $produto && $produto->id == $p->id ? 'selected' : '' ?>>
                                <?= Html::encode($p->nome) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($produto && !empty($historico)): ?>
            
            <!-- Informações do Produto -->
            <div class="bg-white rounded-lg shadow-md mb-6 p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4"><?= Html::encode($produto->nome) ?></h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600 mb-1">Preço de Custo Atual</p>
                        <p class="text-2xl font-bold text-blue-600">R$ <?= number_format($produto->preco_custo, 2, ',', '.') ?></p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600 mb-1">Estoque Atual</p>
                        <p class="text-2xl font-bold text-green-600"><?= number_format($produto->estoque_atual, 0, ',', '.') ?> un</p>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600 mb-1">Total de Compras</p>
                        <p class="text-2xl font-bold text-yellow-600"><?= count($historico) ?></p>
                    </div>
                </div>
            </div>

            <!-- Comparação de Preços por Fornecedor -->
            <div class="bg-white rounded-lg shadow-md mb-6 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Comparação de Preços por Fornecedor
                </h3>
                
                <?php
                // Agrupa por fornecedor e pega a última compra de cada um
                $fornecedoresPrecos = [];
                foreach ($historico as $h) {
                    if ($h['ordem_compra_fornecedor'] == 1) { // Última compra deste fornecedor
                        $fornecedoresPrecos[] = $h;
                    }
                }
                
                // Ordena por preço (menor primeiro)
                usort($fornecedoresPrecos, function($a, $b) {
                    return $a['preco_unitario'] <=> $b['preco_unitario'];
                });
                ?>
                
                <?php if (!empty($fornecedoresPrecos)): ?>
                    <div class="space-y-3">
                        <?php foreach ($fornecedoresPrecos as $index => $fp): ?>
                            <div class="p-4 border rounded-lg <?= $index === 0 ? 'bg-green-50 border-green-300' : 'bg-gray-50 border-gray-200' ?>">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="font-semibold text-gray-900">
                                            <?= Html::encode($fp['nome_fornecedor']) ?>
                                            <?php if ($index === 0): ?>
                                                <span class="ml-2 px-2 py-1 bg-green-600 text-white text-xs font-semibold rounded-full">⭐ MELHOR PREÇO</span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            Última compra: <?= Yii::$app->formatter->asDate($fp['data_compra']) ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold <?= $index === 0 ? 'text-green-600' : 'text-gray-900' ?>">
                                            R$ <?= number_format($fp['preco_unitario'], 2, ',', '.') ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            Quantidade: <?= number_format($fp['quantidade'], 3, ',', '.') ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Histórico Completo -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Histórico Completo de Compras
                </h3>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fornecedor</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantidade</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Preço Unit.</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">NF</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($historico as $h): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?= Yii::$app->formatter->asDate($h['data_compra']) ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?= Html::encode($h['nome_fornecedor']) ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900">
                                        <?= number_format($h['quantidade'], 3, ',', '.') ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">
                                        R$ <?= number_format($h['preco_unitario'], 2, ',', '.') ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">
                                        R$ <?= number_format($h['valor_total_item'], 2, ',', '.') ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <?= Html::encode($h['numero_nota_fiscal'] ?: '-') ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <?php
                                        $statusColors = [
                                            'PENDENTE' => 'bg-yellow-100 text-yellow-800',
                                            'CONCLUIDA' => 'bg-green-100 text-green-800',
                                            'CANCELADA' => 'bg-red-100 text-red-800',
                                        ];
                                        $statusColor = $statusColors[$h['status_compra']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusColor ?>">
                                            <?= Html::encode($h['status_compra']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($produto && empty($historico)): ?>
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p class="text-lg text-gray-600 mb-2">Nenhuma compra registrada para este produto.</p>
                <p class="text-sm text-gray-500">Registre uma compra para começar a acompanhar o histórico.</p>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-lg text-gray-600">Selecione um produto para ver o histórico de compras.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

