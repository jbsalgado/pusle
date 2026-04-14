<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Gestão de Fretes';
$models = $dataProvider->getModels();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-8 gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight"><?= Html::encode($this->title) ?></h1>
            <p class="mt-1 text-sm text-gray-500">Controle total sobre as taxas de entrega da sua loja.</p>
        </div>
        <a href="<?= Url::to(['create']) ?>" 
           class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-bold rounded-xl text-white bg-blue-600 hover:bg-blue-700 shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 active:scale-95">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Nova Regra
        </a>
    </div>

    <!-- Filtro de Busca -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-8">
        <form method="get" action="<?= Url::to(['index']) ?>" class="flex flex-col md:flex-row gap-3">
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="busca" value="<?= Html::encode($busca ?? '') ?>" 
                       class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white sm:text-sm transition-all" 
                       placeholder="Buscar Cidade, Bairro ou CEP...">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 md:flex-none px-6 py-3 bg-gray-900 text-white font-bold rounded-xl hover:bg-gray-800 transition-colors">
                    Filtrar
                </button>
                <a href="<?= Url::to(['index']) ?>" class="flex-1 md:flex-none px-6 py-3 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 text-center transition-colors">
                    Limpar
                </a>
            </div>
        </form>
    </div>

    <!-- Listagem -->
    <?php if (empty($models)): ?>
        <div class="bg-white rounded-3xl p-12 text-center border-2 border-dashed border-gray-100">
            <div class="bg-gray-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Nenhuma regra encontrada</h3>
            <p class="text-gray-500 mt-1">Experimente mudar o filtro ou crie uma nova taxa.</p>
        </div>
    <?php else: ?>
        <!-- Mobile Version (Cards) - Hidden on desktop -->
        <div class="space-y-4 md:hidden">
            <?php foreach ($models as $model): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 relative overflow-hidden group">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center">
                            <div class="bg-blue-100 text-blue-600 rounded-lg p-2 mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 leading-tight">
                                    <?= Html::encode($model->bairro ?: ($model->cidade ?: 'Global')) ?>
                                </h3>
                                <p class="text-xs text-gray-500"><?= Html::encode($model->cidade ?: 'Todas as Cidades') ?></p>
                            </div>
                        </div>
                        <div class="text-right flex flex-col items-end">
                            <span class="text-lg font-black text-blue-600">R$ <?= number_format($model->valor, 2, ',', '.') ?></span>
                            <span class="mt-1 px-2 py-0.5 text-[10px] font-bold rounded bg-blue-50 text-blue-600 border border-blue-100 uppercase">
                                Porte <?= $model->porte ?>
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                        <div class="flex flex-col">
                            <span class="text-[10px] uppercase font-bold text-gray-400">CEP</span>
                            <span class="text-sm font-medium text-gray-700"><?= Html::encode($model->cep ?: 'Geral') ?></span>
                        </div>
                        <div class="flex gap-2">
                            <a href="<?= Url::to(['update', 'id' => $model->id]) ?>" 
                               class="bg-gray-50 p-2.5 rounded-xl text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </a>
                            <a href="<?= Url::to(['delete', 'id' => $model->id]) ?>" 
                               class="bg-gray-50 p-2.5 rounded-xl text-gray-600 hover:bg-red-50 hover:text-red-600 transition-colors"
                               data-confirm="Tem certeza que deseja excluir esta regra?" data-method="post">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <?php if ($model->valor_minimo_frete_gratis): ?>
                        <div class="mt-3 bg-green-50 rounded-lg p-2 flex items-center">
                            <svg class="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                            </svg>
                            <span class="text-[11px] font-bold text-green-700 uppercase">Grátis acima de R$ <?= number_format($model->valor_minimo_frete_gratis, 2, ',', '.') ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Desktop Version (Table) - Hidden on mobile -->
        <div class="hidden md:block overflow-hidden bg-white rounded-2xl shadow-sm border border-gray-100">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Localidade</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Porte</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">CEP</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Valor</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Frete Grátis</th>
                        <th class="px-6 py-4 text-right"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php foreach ($models as $model): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-bold text-gray-900"><?= Html::encode($model->bairro ?: 'Todos os Bairros') ?></div>
                                        <div class="text-xs text-gray-500"><?= Html::encode($model->cidade ?: 'Geral') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php 
                                    $porteColors = [
                                        'P' => 'bg-blue-50 text-blue-700 border-blue-100',
                                        'M' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                                        'G' => 'bg-purple-50 text-purple-700 border-purple-100',
                                        'X' => 'bg-red-50 text-red-700 border-red-100',
                                    ];
                                    $colorClass = $porteColors[$model->porte] ?? 'bg-gray-50 text-gray-700 border-gray-100';
                                ?>
                                <span class="px-2.5 py-1 text-xs font-bold rounded-lg border <?= $colorClass ?> uppercase shadow-sm">
                                    <?= $model->porte ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 text-xs font-medium rounded-lg bg-gray-100 text-gray-600">
                                    <?= Html::encode($model->cep ?: 'Qualquer') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm font-black text-blue-600">R$ <?= number_format($model->valor, 2, ',', '.') ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($model->valor_minimo_frete_gratis): ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-green-50 text-green-700">
                                        Acima de R$ <?= number_format($model->valor_minimo_frete_gratis, 2, ',', '.') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">Não configurado</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <a href="<?= Url::to(['update', 'id' => $model->id]) ?>" 
                                       class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </a>
                                    <a href="<?= Url::to(['delete', 'id' => $model->id]) ?>" 
                                       class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all"
                                       data-confirm="Tem certeza?" data-method="post">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
