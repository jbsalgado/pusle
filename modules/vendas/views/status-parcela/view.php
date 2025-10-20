<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\StatusParcela */

$this->title = $model->codigo;
$this->params['breadcrumbs'][] = ['label' => 'Status de Parcelas', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

// Registrar Tailwind CSS
$this->registerCssFile('https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css', ['position' => \yii\web\View::POS_HEAD]);
?>

<div class="status-parcela-view min-h-screen bg-gray-50 py-4 sm:py-6 lg:py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
        <!-- Breadcrumb/Voltar -->
        <div class="mb-4 sm:mb-6">
            <?= Html::a(
                '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>Voltar para Lista',
                ['index'],
                ['class' => 'inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors duration-200']
            ) ?>
        </div>

        <!-- Header com Badge e Título -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-8 sm:px-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-16 w-16 rounded-lg bg-white/10 flex items-center justify-center backdrop-blur-sm">
                                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-white backdrop-blur-sm">
                                    STATUS
                                </span>
                            </div>
                            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white">
                                <?= Html::encode($this->title) ?>
                            </h1>
                        </div>
                    </div>
                    
                    <!-- Ações Quick no Header (mobile hidden) -->
                    <div class="hidden lg:flex items-center gap-2">
                        <?= Html::a(
                            '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
                            ['update', 'id' => $model->codigo],
                            ['class' => 'p-2 bg-white/10 backdrop-blur-sm rounded-lg text-white hover:bg-white/20 transition-colors duration-200', 'title' => 'Editar']
                        ) ?>
                        <?= Html::a(
                            '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>',
                            ['delete', 'id' => $model->codigo],
                            [
                                'class' => 'p-2 bg-white/10 backdrop-blur-sm rounded-lg text-white hover:bg-red-500/90 transition-colors duration-200',
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
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Coluna Principal - Detalhes -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Card de Informações -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Informações do Status
                        </h2>
                    </div>
                    
                    <div class="px-6 py-6">
                        <dl class="space-y-6">
                            <!-- Código -->
                            <div class="flex flex-col sm:flex-row sm:items-start">
                                <dt class="text-sm font-medium text-gray-500 sm:w-40 sm:flex-shrink-0 mb-1 sm:mb-0">
                                    Código
                                </dt>
                                <dd class="text-sm text-gray-900 sm:ml-6 flex-1">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-md text-sm font-semibold bg-blue-100 text-blue-800">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                            </svg>
                                            <?= Html::encode($model->codigo) ?>
                                        </span>
                                    </div>
                                </dd>
                            </div>
                            
                            <!-- Descrição -->
                            <div class="flex flex-col sm:flex-row sm:items-start">
                                <dt class="text-sm font-medium text-gray-500 sm:w-40 sm:flex-shrink-0 mb-1 sm:mb-0">
                                    Descrição
                                </dt>
                                <dd class="text-sm text-gray-900 sm:ml-6 flex-1">
                                    <div class="prose prose-sm max-w-none">
                                        <p class="text-base"><?= Html::encode($model->descricao) ?></p>
                                    </div>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Card de Informações Técnicas (exemplo) -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Metadados
                        </h2>
                    </div>
                    
                    <div class="px-6 py-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">
                                    Registro ID
                                </dt>
                                <dd class="text-sm font-mono text-gray-900 bg-gray-50 px-3 py-2 rounded-md">
                                    <?= Html::encode($model->codigo) ?>
                                </dd>
                            </div>
                            
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">
                                    Status
                                </dt>
                                <dd class="text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Ativo
                                    </span>
                                </dd>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna Lateral - Ações e Info -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Card de Ações Rápidas -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">Ações</h2>
                    </div>
                    
                    <div class="px-6 py-5 space-y-3">
                        <?= Html::a(
                            '<svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>Editar Status',
                            ['update', 'id' => $model->codigo],
                            ['class' => 'w-full inline-flex items-center justify-center px-4 py-3 border border-blue-300 shadow-sm text-sm font-medium rounded-lg text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200']
                        ) ?>
                        
                        <?= Html::a(
                            '<svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>Apagar Status',
                            ['delete', 'id' => $model->codigo],
                            [
                                'class' => 'w-full inline-flex items-center justify-center px-4 py-3 border border-red-300 shadow-sm text-sm font-medium rounded-lg text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200',
                                'data' => [
                                    'confirm' => 'Tem a certeza que quer apagar este item?',
                                    'method' => 'post',
                                ],
                            ]
                        ) ?>
                        
                        <div class="pt-3 border-t border-gray-200">
                            <?= Html::a(
                                '<svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>Ver Todos',
                                ['index'],
                                ['class' => 'w-full inline-flex items-center justify-center px-4 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200']
                            ) ?>
                        </div>
                    </div>
                </div>

                <!-- Card de Ajuda -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg overflow-hidden">
                    <div class="px-6 py-5">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800 mb-2">Sobre este Status</h3>
                                <div class="text-sm text-blue-700 space-y-2">
                                    <p>Este status é utilizado para identificar e categorizar o estado de parcelas no sistema.</p>
                                    <p class="text-xs">Edite com cuidado para não afetar registros existentes.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card de Estatísticas (exemplo) -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">Uso</h2>
                    </div>
                    
                    <div class="px-6 py-5">
                        <div class="text-center">
                            <div class="text-4xl font-bold text-gray-900">-</div>
                            <div class="text-sm text-gray-500 mt-1">Parcelas com este status</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>