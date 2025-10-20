<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\vendas\models\search\StatusParcelaSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Status de Parcelas';
$this->params['breadcrumbs'][] = $this->title;

// Registrar Tailwind CSS via CDN
$this->registerCssFile('https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css', ['position' => \yii\web\View::POS_HEAD]);

// JavaScript para alternar visualização
$this->registerJs("
    function toggleView(view) {
        document.getElementById('card-view').classList.toggle('hidden', view !== 'cards');
        document.getElementById('grid-view').classList.toggle('hidden', view !== 'grid');
        
        // Atualizar botões ativos
        document.getElementById('btn-cards').classList.toggle('bg-blue-600', view === 'cards');
        document.getElementById('btn-cards').classList.toggle('bg-gray-200', view !== 'cards');
        document.getElementById('btn-cards').classList.toggle('text-white', view === 'cards');
        document.getElementById('btn-cards').classList.toggle('text-gray-700', view !== 'cards');
        
        document.getElementById('btn-grid').classList.toggle('bg-blue-600', view === 'grid');
        document.getElementById('btn-grid').classList.toggle('bg-gray-200', view !== 'grid');
        document.getElementById('btn-grid').classList.toggle('text-white', view === 'grid');
        document.getElementById('btn-grid').classList.toggle('text-gray-700', view !== 'grid');
        
        localStorage.setItem('statusParcelaView', view);
    }
    
    // Restaurar visualização preferida
    window.addEventListener('DOMContentLoaded', function() {
        const savedView = localStorage.getItem('statusParcelaView') || 'cards';
        toggleView(savedView);
    });
", \yii\web\View::POS_END);
?>

<div class="status-parcela-index min-h-screen bg-gray-50 py-4 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="max-w-7xl mx-auto">
        <!-- Título e Botão Criar -->
        <div class="mb-6 sm:mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900">
                        <?= Html::encode($this->title) ?>
                    </h1>
                    <p class="mt-2 text-sm text-gray-600">Gerencie os status das parcelas do sistema</p>
                </div>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>Criar Novo', 
                    ['create'], 
                    ['class' => 'inline-flex items-center justify-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 shadow-sm w-full sm:w-auto']
                ) ?>
            </div>
        </div>

        <!-- Barra de Pesquisa e Controles -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <!-- Campo de Pesquisa -->
                <div class="flex-1 max-w-md">
                    <?php $form = \yii\widgets\ActiveForm::begin([
                        'action' => ['index'],
                        'method' => 'get',
                        'options' => ['class' => 'relative']
                    ]); ?>
                    
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <?= $form->field($searchModel, 'codigo', [
                            'template' => '{input}',
                            'options' => ['class' => '']
                        ])->textInput([
                            'class' => 'block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm',
                            'placeholder' => 'Buscar por código ou descrição...'
                        ]) ?>
                    </div>
                    
                    <?php \yii\widgets\ActiveForm::end(); ?>
                </div>

                <!-- Controles de Visualização -->
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600 hidden sm:inline">Visualizar como:</span>
                    <div class="inline-flex rounded-lg shadow-sm" role="group">
                        <button 
                            id="btn-cards" 
                            onclick="toggleView('cards')" 
                            type="button" 
                            class="px-4 py-2 text-sm font-medium rounded-l-lg border border-gray-300 hover:bg-gray-100 focus:z-10 focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                            </svg>
                        </button>
                        <button 
                            id="btn-grid" 
                            onclick="toggleView('grid')" 
                            type="button" 
                            class="px-4 py-2 text-sm font-medium rounded-r-lg border border-gray-300 hover:bg-gray-100 focus:z-10 focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visualização em Cards -->
        <div id="card-view" class="hidden">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                <?php foreach ($dataProvider->models as $model): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200 overflow-hidden group">
                        <div class="p-5">
                            <!-- Código com Badge -->
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                    <?= Html::encode($model->codigo) ?>
                                </span>
                                <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            <!-- Descrição -->
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 line-clamp-2">
                                <?= Html::encode($model->descricao) ?>
                            </h3>
                            
                            <!-- Ações -->
                            <div class="flex gap-2 pt-3 border-t border-gray-100">
                                <?= Html::a(
                                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg><span class="hidden sm:inline ml-1">Ver</span>',
                                    ['view', 'id' => $model->codigo],
                                    ['class' => 'flex-1 inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-md transition-colors duration-200']
                                ) ?>
                                <?= Html::a(
                                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg><span class="hidden sm:inline ml-1">Editar</span>',
                                    ['update', 'id' => $model->codigo],
                                    ['class' => 'flex-1 inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors duration-200']
                                ) ?>
                                <?= Html::a(
                                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>',
                                    ['delete', 'id' => $model->codigo],
                                    [
                                        'class' => 'inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 rounded-md transition-colors duration-200',
                                        'data' => [
                                            'confirm' => 'Tem a certeza que quer apagar este item?',
                                            'method' => 'post',
                                        ],
                                        'title' => 'Apagar'
                                    ]
                                ) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($dataProvider->models)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum registro encontrado</h3>
                    <p class="mt-1 text-sm text-gray-500">Comece criando um novo status de parcela.</p>
                    <div class="mt-6">
                        <?= Html::a('Criar Status de Parcela', ['create'], ['class' => 'inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700']) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Visualização em Grid (Tabela) -->
        <div id="grid-view" class="hidden">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                                    #
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Código
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Descrição
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            $index = 1;
                            foreach ($dataProvider->models as $model): 
                            ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $index++ ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?= Html::encode($model->codigo) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= Html::encode($model->descricao) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <?= Html::a(
                                                '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>',
                                                ['view', 'id' => $model->codigo],
                                                ['class' => 'text-gray-600 hover:text-gray-900 transition-colors duration-200', 'title' => 'Ver']
                                            ) ?>
                                            <?= Html::a(
                                                '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
                                                ['update', 'id' => $model->codigo],
                                                ['class' => 'text-blue-600 hover:text-blue-900 transition-colors duration-200', 'title' => 'Editar']
                                            ) ?>
                                            <?= Html::a(
                                                '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>',
                                                ['delete', 'id' => $model->codigo],
                                                [
                                                    'class' => 'text-red-600 hover:text-red-900 transition-colors duration-200',
                                                    'data' => [
                                                        'confirm' => 'Tem a certeza que quer apagar este item?',
                                                        'method' => 'post',
                                                    ],
                                                    'title' => 'Apagar'
                                                ]
                                            ) ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($dataProvider->models)): ?>
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum registro encontrado</h3>
                        <p class="mt-1 text-sm text-gray-500">Comece criando um novo status de parcela.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Paginação -->
        <?php if (!empty($dataProvider->models)): ?>
            <div class="mt-6">
                <?= \yii\widgets\LinkPager::widget([
                    'pagination' => $dataProvider->pagination,
                    'options' => ['class' => 'flex flex-wrap justify-center gap-1 sm:gap-2'],
                    'linkOptions' => ['class' => 'px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200'],
                    'activePageCssClass' => 'bg-blue-600 text-white border-blue-600 hover:bg-blue-700',
                    'disabledPageCssClass' => 'opacity-50 cursor-not-allowed',
                    'maxButtonCount' => 5,
                ]) ?>
            </div>
        <?php endif; ?>
    </div>
</div>