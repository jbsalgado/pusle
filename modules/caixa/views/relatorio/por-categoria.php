<?php

use yii\helpers\Html;

$this->title = 'Relatório por Categoria';
$this->params['breadcrumbs'][] = ['label' => 'Relatórios', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$totalEntradas = array_sum(array_column($entradas, 'total_valor'));
$totalSaidas = array_sum(array_column($saidas, 'total_valor'));
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 lg:px-8">

    <div class="max-w-7xl mx-auto">

        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
            <?= Html::a(
                '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                ['index'],
                ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition']
            ) ?>
        </div>

        <!-- Filtro de Mês -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <form method="get" class="flex items-end space-x-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mês/Ano</label>
                    <input type="month" name="mes" value="<?= Html::encode($mes) ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Filtrar
                </button>
            </form>
        </div>

        <!-- Resumo Geral -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <p class="text-sm text-gray-600 mb-1">Total Entradas</p>
                <p class="text-2xl font-bold text-green-600"><?= Yii::$app->formatter->asCurrency($totalEntradas) ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
                <p class="text-sm text-gray-600 mb-1">Total Saídas</p>
                <p class="text-2xl font-bold text-red-600"><?= Yii::$app->formatter->asCurrency($totalSaidas) ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                <p class="text-sm text-gray-600 mb-1">Saldo</p>
                <p class="text-2xl font-bold text-blue-600"><?= Yii::$app->formatter->asCurrency($totalEntradas - $totalSaidas) ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Entradas por Categoria -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4 text-white">
                    <h3 class="text-lg font-semibold">Entradas por Categoria</h3>
                </div>
                <div class="p-6">
                    <?php if (empty($entradas)): ?>
                        <p class="text-gray-500 text-center py-8">Nenhuma entrada neste período</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($entradas as $entrada): ?>
                                <div class="border-b pb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-semibold text-gray-700"><?= Html::encode($entrada['categoria'] ?? 'Sem Categoria') ?></span>
                                        <span class="text-green-600 font-bold"><?= Yii::$app->formatter->asCurrency($entrada['total_valor']) ?></span>
                                    </div>
                                    <div class="flex justify-between text-sm text-gray-500">
                                        <span><?= $entrada['total_movimentacoes'] ?> movimentação(ões)</span>
                                        <span><?= number_format(($entrada['total_valor'] / $totalEntradas) * 100, 1) ?>%</span>
                                    </div>
                                    <!-- Barra de progresso -->
                                    <div class="mt-2 bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-500 h-2 rounded-full" style="width: <?= ($entrada['total_valor'] / $totalEntradas) * 100 ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Saídas por Categoria -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4 text-white">
                    <h3 class="text-lg font-semibold">Saídas por Categoria</h3>
                </div>
                <div class="p-6">
                    <?php if (empty($saidas)): ?>
                        <p class="text-gray-500 text-center py-8">Nenhuma saída neste período</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($saidas as $saida): ?>
                                <div class="border-b pb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-semibold text-gray-700"><?= Html::encode($saida['categoria'] ?? 'Sem Categoria') ?></span>
                                        <span class="text-red-600 font-bold"><?= Yii::$app->formatter->asCurrency($saida['total_valor']) ?></span>
                                    </div>
                                    <div class="flex justify-between text-sm text-gray-500">
                                        <span><?= $saida['total_movimentacoes'] ?> movimentação(ões)</span>
                                        <span><?= number_format(($saida['total_valor'] / $totalSaidas) * 100, 1) ?>%</span>
                                    </div>
                                    <!-- Barra de progresso -->
                                    <div class="mt-2 bg-gray-200 rounded-full h-2">
                                        <div class="bg-red-500 h-2 rounded-full" style="width: <?= ($saida['total_valor'] / $totalSaidas) * 100 ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>

</div>