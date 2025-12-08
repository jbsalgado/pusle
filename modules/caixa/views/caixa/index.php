<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Caixas';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    
    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <p class="mt-1 text-sm text-gray-600">Gerencie abertura e fechamento de caixas</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                    ['/vendas/inicio/index'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Abrir Caixa',
                    ['create'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
                ) ?>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">
        
        <!-- Avisos -->
        <?php if (isset($temCaixaDiaAnterior) && $temCaixaDiaAnterior): ?>
            <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Atenção:</strong> Existe um caixa aberto do dia anterior. Ele será fechado automaticamente ao abrir um novo caixa ou ao registrar uma venda.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($caixaAbertoHoje) && $caixaAbertoHoje): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">
                            <strong>Caixa Aberto:</strong> Há um caixa aberto para hoje. As vendas serão registradas automaticamente neste caixa.
                        </p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded-r-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">
                            <strong>Atenção:</strong> Não há caixa aberto para hoje. As vendas não serão registradas automaticamente no caixa até que um caixa seja aberto.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Lista de Caixas -->
        <div class="space-y-4">
            <?php if ($dataProvider->getTotalCount() > 0): ?>
                <?php foreach ($dataProvider->getModels() as $model): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300">
                        <div class="p-6">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-xl font-semibold text-gray-900">
                                            Caixa #<?= substr($model->id, 0, 8) ?>
                                        </h3>
                                        <?php
                                        $statusColors = [
                                            'ABERTO' => 'bg-green-100 text-green-800',
                                            'FECHADO' => 'bg-gray-100 text-gray-800',
                                            'CANCELADO' => 'bg-red-100 text-red-800',
                                        ];
                                        $statusColor = $statusColors[$model->status] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold <?= $statusColor ?>">
                                            <?= Html::encode($model->status) ?>
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm text-gray-600">
                                        <div>
                                            <span class="font-medium">Abertura:</span>
                                            <?= Yii::$app->formatter->asDatetime($model->data_abertura) ?>
                                        </div>
                                        <?php if ($model->data_fechamento): ?>
                                            <div>
                                                <span class="font-medium">Fechamento:</span>
                                                <?= Yii::$app->formatter->asDatetime($model->data_fechamento) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($model->colaborador): ?>
                                            <div>
                                                <span class="font-medium">Colaborador:</span>
                                                <?= Html::encode($model->colaborador->nome_completo) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <?php
                                    $valorEsperado = $model->calcularValorEsperado();
                                    ?>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-500">Valor Inicial</div>
                                        <div class="text-lg font-bold text-gray-900">R$ <?= number_format($model->valor_inicial, 2, ',', '.') ?></div>
                                        <?php if ($model->isAberto()): ?>
                                            <div class="text-sm text-gray-500 mt-1">Valor Esperado</div>
                                            <div class="text-lg font-bold text-green-600">R$ <?= number_format($valorEsperado, 2, ',', '.') ?></div>
                                        <?php else: ?>
                                            <div class="text-sm text-gray-500 mt-1">Valor Final</div>
                                            <div class="text-lg font-bold text-gray-900">R$ <?= number_format($model->valor_final ?? 0, 2, ',', '.') ?></div>
                                            <?php if ($model->diferenca !== null): ?>
                                                <div class="text-sm <?= $model->diferenca >= 0 ? 'text-green-600' : 'text-red-600' ?> mt-1">
                                                    Diferença: R$ <?= number_format($model->diferenca, 2, ',', '.') ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Ações -->
                            <div class="flex flex-wrap gap-2 pt-4 border-t border-gray-200">
                                <?= Html::a(
                                    '<svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>Ver Detalhes',
                                    ['view', 'id' => $model->id],
                                    ['class' => 'inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition duration-300']
                                ) ?>
                                <?php if ($model->isAberto()): ?>
                                    <?= Html::a(
                                        '<svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar',
                                        ['update', 'id' => $model->id],
                                        ['class' => 'inline-flex items-center px-3 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-lg transition duration-300']
                                    ) ?>
                                    <?= Html::beginForm(['fechar', 'id' => $model->id], 'post', [
                                        'style' => 'display: inline-block;',
                                        'onsubmit' => 'return confirm("Tem certeza que deseja fechar este caixa?");'
                                    ]) ?>
                                        <?= Html::submitButton(
                                            '<svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Fechar Caixa',
                                            [
                                                'class' => 'inline-flex items-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition duration-300',
                                            ]
                                        ) ?>
                                    <?= Html::endForm() ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Paginação -->
                <div class="mt-6">
                    <?= LinkPager::widget([
                        'pagination' => $dataProvider->pagination,
                        'options' => ['class' => 'flex justify-center'],
                        'linkOptions' => ['class' => 'px-3 py-2 mx-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50'],
                        'activePageCssClass' => 'bg-blue-600 text-white border-blue-600',
                        'disabledPageCssClass' => 'opacity-50 cursor-not-allowed',
                    ]) ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow-md p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum caixa encontrado</h3>
                    <p class="mt-1 text-sm text-gray-500">Comece abrindo um novo caixa.</p>
                    <div class="mt-6">
                        <?= Html::a(
                            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Abrir Primeiro Caixa',
                            ['create'],
                            ['class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
                        ) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

