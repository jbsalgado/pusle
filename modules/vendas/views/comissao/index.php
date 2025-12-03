<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use app\modules\vendas\models\Comissao;

$this->title = 'Comissões';
$this->params['breadcrumbs'][] = $this->title;

$viewMode = Yii::$app->request->get('view', 'cards');
?>

<div class="min-h-screen bg-gray-50 py-4 px-3 sm:py-6 sm:px-4 lg:px-8">
    
    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-4 sm:mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
            <div class="flex flex-wrap gap-2">
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                    ['/vendas/inicio/index'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm w-full sm:w-auto justify-center']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Nova Comissão',
                    ['create'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm w-full sm:w-auto justify-center']
                ) ?>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">
        
        <!-- Toggle View e Contador -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4 mb-4 sm:mb-6">
            <span class="text-sm sm:text-base text-gray-600">
                <?= $dataProvider->getTotalCount() ?> comissão(ões) encontrada(s)
            </span>
            <div class="flex gap-2">
                <?= Html::a(
                    '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>',
                    ['index', 'view' => 'cards'] + Yii::$app->request->get(),
                    ['class' => 'p-2 rounded-lg transition duration-300 ' . ($viewMode == 'cards' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')]
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/></svg>',
                    ['index', 'view' => 'grid'] + Yii::$app->request->get(),
                    ['class' => 'p-2 rounded-lg transition duration-300 ' . ($viewMode == 'grid' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')]
                ) ?>
            </div>
        </div>

        <?php if ($viewMode == 'cards'): ?>
            
            <!-- Visualização em Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                <?php foreach ($dataProvider->getModels() as $model): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        
                        <!-- Header do Card -->
                        <div class="p-4 sm:p-5 border-b border-gray-200">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h3 class="text-base sm:text-lg font-semibold text-gray-900 truncate mb-1">
                                        <?= Html::encode($model->colaborador->nome_completo ?? '-') ?>
                                    </h3>
                                    <p class="text-xs sm:text-sm text-gray-500">
                                        <?= Html::encode($model->tipo_comissao == Comissao::TIPO_VENDA ? 'Comissão de Venda' : 'Comissão de Cobrança') ?>
                                    </p>
                                </div>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full ml-2 flex-shrink-0 <?= 
                                    $model->status == Comissao::STATUS_PAGA ? 'bg-green-100 text-green-800' : 
                                    ($model->status == Comissao::STATUS_CANCELADA ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')
                                ?>">
                                    <?= Html::encode($model->status) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Conteúdo -->
                        <div class="p-4 sm:p-5">
                            <div class="space-y-3 mb-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs sm:text-sm text-gray-600">Valor Base:</span>
                                    <span class="text-sm sm:text-base font-medium text-gray-900">
                                        R$ <?= Yii::$app->formatter->asDecimal($model->valor_base, 2) ?>
                                    </span>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-xs sm:text-sm text-gray-600">Percentual:</span>
                                    <span class="text-sm sm:text-base font-medium text-blue-600">
                                        <?= Yii::$app->formatter->asDecimal($model->percentual_aplicado, 2) ?>%
                                    </span>
                                </div>
                                
                                <div class="pt-2 border-t border-gray-200">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm sm:text-base font-semibold text-gray-900">Comissão:</span>
                                        <span class="text-lg sm:text-xl font-bold text-green-600">
                                            R$ <?= Yii::$app->formatter->asDecimal($model->valor_comissao, 2) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if ($model->data_pagamento): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs sm:text-sm text-gray-600">Data Pagamento:</span>
                                        <span class="text-xs sm:text-sm font-medium text-gray-700">
                                            <?= Yii::$app->formatter->asDate($model->data_pagamento) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-xs sm:text-sm text-gray-600">Data Criação:</span>
                                    <span class="text-xs sm:text-sm text-gray-500">
                                        <?= Yii::$app->formatter->asDate($model->data_criacao) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Ações -->
                            <div class="flex flex-col sm:flex-row gap-2 pt-3 border-t border-gray-200">
                                <?= Html::a('Ver', ['view', 'id' => $model->id], 
                                    ['class' => 'flex-1 text-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-semibold rounded transition duration-300']) ?>
                                <?= Html::a('Editar', ['update', 'id' => $model->id], 
                                    ['class' => 'flex-1 text-center px-3 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-semibold rounded transition duration-300']) ?>
                                <?= Html::a('Excluir', ['delete', 'id' => $model->id], [
                                    'class' => 'flex-1 text-center px-3 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded transition duration-300',
                                    'data' => [
                                        'confirm' => 'Tem certeza que deseja excluir esta comissão?',
                                        'method' => 'post',
                                    ],
                                ]) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            
            <!-- Visualização em Grid/Tabela -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Colaborador</th>
                                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Tipo</th>
                                <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Valor Base</th>
                                <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Comissão</th>
                                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Status</th>
                                <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($dataProvider->getModels() as $model): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= Html::encode($model->colaborador->nome_completo ?? '-') ?>
                                        </div>
                                        <div class="text-xs text-gray-500 md:hidden">
                                            <?= Html::encode($model->tipo_comissao == Comissao::TIPO_VENDA ? 'Venda' : 'Cobrança') ?>
                                        </div>
                                    </td>
                                    <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= Html::encode($model->tipo_comissao == Comissao::TIPO_VENDA ? 'Venda' : 'Cobrança') ?>
                                    </td>
                                    <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                        R$ <?= Yii::$app->formatter->asDecimal($model->valor_base, 2) ?>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm sm:text-base font-bold text-green-600">
                                            R$ <?= Yii::$app->formatter->asDecimal($model->valor_comissao, 2) ?>
                                        </div>
                                        <div class="text-xs text-gray-500 lg:hidden">
                                            Base: R$ <?= Yii::$app->formatter->asDecimal($model->valor_base, 2) ?>
                                        </div>
                                    </td>
                                    <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= 
                                            $model->status == Comissao::STATUS_PAGA ? 'bg-green-100 text-green-800' : 
                                            ($model->status == Comissao::STATUS_CANCELADA ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')
                                        ?>">
                                            <?= Html::encode($model->status) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end gap-2">
                                            <?= Html::a('Ver', ['view', 'id' => $model->id], ['class' => 'text-blue-600 hover:text-blue-900']) ?>
                                            <?= Html::a('Editar', ['update', 'id' => $model->id], ['class' => 'text-yellow-600 hover:text-yellow-900']) ?>
                                            <?= Html::a('Excluir', ['delete', 'id' => $model->id], [
                                                'class' => 'text-red-600 hover:text-red-900',
                                                'data' => [
                                                    'confirm' => 'Tem certeza que deseja excluir esta comissão?',
                                                    'method' => 'post',
                                                ],
                                            ]) ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>

        <?php if ($dataProvider->totalCount == 0): ?>
            <div class="text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-gray-500 text-lg mb-4">Nenhuma comissão cadastrada</p>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Cadastrar Comissão',
                    ['create'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300']
                ) ?>
            </div>
        <?php endif; ?>

        <!-- Paginação -->
        <?php if ($dataProvider->pagination->pageCount > 1): ?>
            <div class="mt-6">
                <?= LinkPager::widget([
                    'pagination' => $dataProvider->pagination,
                    'options' => ['class' => 'flex justify-center space-x-2'],
                    'linkOptions' => ['class' => 'px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50 text-sm'],
                    'activePageCssClass' => 'bg-blue-600 text-white border-blue-600',
                    'disabledPageCssClass' => 'opacity-50 cursor-not-allowed',
                    'prevPageLabel' => '←',
                    'nextPageLabel' => '→',
                ]) ?>
            </div>
        <?php endif; ?>

    </div>
</div>
