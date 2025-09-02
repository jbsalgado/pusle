<?php

use yii\helpers\Html;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $model app\models\DefinicaoIndicador */
?>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
    <div class="p-6">
        <!-- Cabeçalho do Card -->
        <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-2">
                    <?php if ($model->cod_indicador): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <?= Html::encode($model->cod_indicador) ?>
                        </span>
                    <?php endif; ?>
                    
                    <span class="status-indicator inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $model->ativo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <?= $model->ativo ? 'Ativo' : 'Inativo' ?>
                    </span>
                    
                    <?php if ($model->tipo_especifico): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <?= Html::encode($model->getTipoEspecificoOptions()[$model->tipo_especifico] ?? $model->tipo_especifico) ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <h3 class="mt-2 text-lg font-semibold text-gray-900 leading-6">
                    <?= Html::encode(StringHelper::truncate($model->nome_indicador, 80)) ?>
                </h3>
                
                <?php if ($model->descricao_completa): ?>
                    <p class="mt-1 text-sm text-gray-600 leading-5">
                        <?= Html::encode(StringHelper::truncate($model->descricao_completa, 150)) ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Menu de Ações -->
            <div class="ml-4 flex-shrink-0">
                <div class="flex items-center space-x-2">
                    <!-- Toggle Status -->
                    <?= Html::a('', ['toggle-status', 'id' => $model->id_indicador], [
                        'class' => 'toggle-status-btn p-1 rounded-full ' . ($model->ativo ? 'text-red-600 hover:text-red-500' : 'text-green-600 hover:text-green-500') . ' hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500',
                        'title' => $model->ativo ? 'Desativar indicador' : 'Ativar indicador',
                        'data-method' => 'post',
                    ]) ?>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <?php if ($model->ativo): ?>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        <?php else: ?>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        <?php endif; ?>
                    </svg>
                    
                    <!-- Dropdown Menu -->
                    <div class="relative inline-block text-left">
                        <button type="button" class="dropdown-toggle p-1 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" data-toggle="dropdown">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                            </svg>
                        </button>
                        
                        <div class="dropdown-menu hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none z-10">
                            <div class="py-1">
                                <?= Html::a('Visualizar', ['view', 'id' => $model->id_indicador], [
                                    'class' => 'quick-view-btn group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900',
                                ]) ?>
                                <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                
                                <?= Html::a('Editar', ['update', 'id' => $model->id_indicador], [
                                    'class' => 'group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900',
                                ]) ?>
                                <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </div>
                            <div class="py-1">
                                <?= Html::a('Excluir', ['delete', 'id' => $model->id_indicador], [
                                    'class' => 'delete-btn group flex items-center px-4 py-2 text-sm text-red-700 hover:bg-red-50 hover:text-red-900',
                                    'data-name' => $model->nome_indicador,
                                    'data-method' => 'post',
                                ]) ?>
                                <svg class="mr-3 h-5 w-5 text-red-400 group-hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informações Adicionais -->
        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php if ($model->unidadeMedida): ?>
                <div class="flex items-center text-sm text-gray-500">
                    <svg class="flex-shrink-0 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <span class="truncate"><?= Html::encode($model->unidadeMedida->sigla_unidade) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($model->polaridade): ?>
                <div class="flex items-center text-sm text-gray-500">
                    <svg class="flex-shrink-0 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <span class="truncate"><?= Html::encode($model->getPolaridadeOptions()[$model->polaridade] ?? $model->polaridade) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($model->dimensao): ?>
                <div class="flex items-center text-sm text-gray-500">
                    <svg class="flex-shrink-0 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <span class="truncate"><?= Html::encode($model->dimensao->nome_dimensao) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($model->responsavel_tecnico): ?>
                <div class="flex items-center text-sm text-gray-500">
                    <svg class="flex-shrink-0 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="truncate"><?= Html::encode($model->responsavel_tecnico) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer com Data de Atualização -->
        <?php if ($model->data_atualizacao): ?>
            <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex items-center justify-between text-xs text-gray-500">
                    <div class="flex items-center">
                        <svg class="flex-shrink-0 mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Atualizado em <?= Yii::$app->formatter->asDatetime($model->data_atualizacao, 'short') ?>
                    </div>
                    <?php if ($model->versao): ?>
                        <span class="font-medium">v<?= $model->versao ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$this->registerJs("
// Toggle dropdown menu
$(document).on('click', '.dropdown-toggle', function(e) {
    e.stopPropagation();
    const menu = $(this).next('.dropdown-menu');
    $('.dropdown-menu').not(menu).addClass('hidden');
    menu.toggleClass('hidden');
});

// Fechar dropdown ao clicar fora
$(document).on('click', function() {
    $('.dropdown-menu').addClass('hidden');
});

// Prevenir fechamento ao clicar dentro do dropdown
$(document).on('click', '.dropdown-menu', function(e) {
    e.stopPropagation();
});
");
?>