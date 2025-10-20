<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\StatusVenda */

$this->title = 'Status: ' . $model->descricao;
$this->params['breadcrumbs'][] = ['label' => 'Status de Vendas', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<div class="status-venda-view min-h-screen bg-gray-50">
    <div class="px-4 sm:px-6 lg:px-8 py-6 sm:py-8 max-w-5xl mx-auto">
        
        <!-- Breadcrumbs -->
        <nav class="flex mb-4 sm:mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="<?= Url::to(['/vendas/default/index']) ?>" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="<?= Url::to(['index']) ?>" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2">Status de Vendas</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 truncate max-w-xs"><?= Html::encode($model->codigo) ?></span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="mb-6 sm:mb-8">
            <div class="sm:flex sm:items-start sm:justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg p-2">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
                                <?= Html::encode($model->descricao) ?>
                            </h1>
                        </div>
                    </div>
                    <div class="ml-0 sm:ml-12">
                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-blue-100 text-blue-800">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            Código: <?= Html::encode($model->codigo) ?>
                        </span>
                    </div>
                </div>

                <!-- Botões de Ação (Desktop) -->
                <div class="hidden sm:flex sm:items-center sm:space-x-2 mt-4 sm:mt-0">
                    <?= Html::a(
                        '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar',
                        ['update', 'id' => $model->codigo],
                        ['class' => 'inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-yellow-500 to-orange-600 rounded-lg shadow-md hover:from-yellow-600 hover:to-orange-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transform hover:-translate-y-0.5 transition-all duration-150']
                    ) ?>
                    <?= Html::a(
                        '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Apagar',
                        ['delete', 'id' => $model->codigo],
                        [
                            'class' => 'inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-red-500 to-red-600 rounded-lg shadow-md hover:from-red-600 hover:to-red-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transform hover:-translate-y-0.5 transition-all duration-150',
                            'data' => [
                                'confirm' => 'Tem a certeza que quer apagar este status? Esta ação não pode ser revertida.',
                                'method' => 'post',
                            ],
                        ]
                    ) ?>
                    <a href="<?= Url::to(['index']) ?>" 
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Voltar
                    </a>
                </div>
            </div>
        </div>

        <!-- Detalhes Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Informações do Status
                </h3>
            </div>
            
            <div class="px-4 py-5 sm:p-6">
                <?= DetailView::widget([
                    'model' => $model,
                    'options' => ['class' => 'table-auto w-full'],
                    'template' => '<tr class="border-b border-gray-100 last:border-0"><th class="px-4 py-4 text-left text-sm font-semibold text-gray-700 bg-gray-50 w-1/3">{label}</th><td class="px-4 py-4 text-sm text-gray-900">{value}</td></tr>',
                    'attributes' => [
                        [
                            'attribute' => 'codigo',
                            'format' => 'raw',
                            'value' => '<span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-mono font-medium bg-blue-100 text-blue-800">' . 
                                Html::encode($model->codigo) . 
                                '</span>',
                        ],
                        [
                            'attribute' => 'descricao',
                            'format' => 'raw',
                            'value' => '<div class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="text-base font-medium">' . Html::encode($model->descricao) . '</span>
                            </div>',
                        ],
                    ],
                ]) ?>
            </div>
        </div>

        <!-- Botões de Ação (Mobile) -->
        <div class="mt-6 space-y-3 sm:hidden">
            <?= Html::a(
                '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar Status',
                ['update', 'id' => $model->codigo],
                ['class' => 'flex items-center justify-center w-full px-4 py-3 text-sm font-semibold text-white bg-gradient-to-r from-yellow-500 to-orange-600 rounded-lg shadow-md active:scale-95 transition-transform duration-150']
            ) ?>
            <?= Html::a(
                '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Apagar Status',
                ['delete', 'id' => $model->codigo],
                [
                    'class' => 'flex items-center justify-center w-full px-4 py-3 text-sm font-semibold text-white bg-gradient-to-r from-red-500 to-red-600 rounded-lg shadow-md active:scale-95 transition-transform duration-150',
                    'data' => [
                        'confirm' => 'Tem a certeza que quer apagar este status? Esta ação não pode ser revertida.',
                        'method' => 'post',
                    ],
                ]
            ) ?>
            <a href="<?= Url::to(['index']) ?>" 
               class="flex items-center justify-center w-full px-4 py-3 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg active:scale-95 transition-transform duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar para Lista
            </a>
        </div>

        <!-- Info adicional -->
        <div class="mt-6 flex items-start space-x-3 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-medium text-blue-900">Informação</h3>
                <p class="mt-1 text-sm text-blue-700">
                    Este status é usado para categorizar o estado das vendas no sistema. Certifique-se de que não existem vendas associadas antes de apagar.
                </p>
            </div>
        </div>

    </div>
</div>

<style>
/* Melhorias visuais para o DetailView */
.detail-view th {
    background-color: #f9fafb;
}

.detail-view tr:hover td {
    background-color: #f9fafb;
}

/* Mobile improvements */
@media (max-width: 640px) {
    .detail-view th,
    .detail-view td {
        display: block;
        width: 100% !important;
        padding: 0.75rem 1rem;
    }
    
    .detail-view th {
        border-bottom: 0;
        padding-bottom: 0.5rem;
        background-color: transparent !important;
    }
    
    .detail-view td {
        padding-top: 0;
        padding-bottom: 1rem;
    }
    
    .detail-view tr {
        display: block;
        margin-bottom: 1rem;
    }
}
</style>