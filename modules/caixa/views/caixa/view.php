<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Caixa #' . substr($model->id, 0, 8);
$this->params['breadcrumbs'][] = ['label' => 'Caixas', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$valorEsperado = $model->calcularValorEsperado();
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        
        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <div class="flex flex-wrap gap-2">
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                        ['index'],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm']
                    ) ?>
                    <?php if ($model->isAberto()): ?>
                        <?= Html::a(
                            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar',
                            ['update', 'id' => $model->id],
                            ['class' => 'inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm']
                        ) ?>
                        <?= Html::a(
                            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Nova Movimentação',
                            ['movimentacao/create', 'caixa_id' => $model->id],
                            ['class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm']
                        ) ?>
                        <?= Html::beginForm(['fechar', 'id' => $model->id], 'post', [
                            'style' => 'display: inline-block;',
                            'onsubmit' => 'return confirm("Tem certeza que deseja fechar este caixa?");'
                        ]) ?>
                            <?= Html::submitButton(
                                '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Fechar Caixa',
                                [
                                    'class' => 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm',
                                ]
                            ) ?>
                        <?= Html::endForm() ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Card Principal -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white">Informações do Caixa</h2>
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
            </div>

            <div class="p-6 space-y-6">
                
                <!-- Dados Básicos -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Dados Básicos
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Data de Abertura</label>
                            <p class="text-base text-gray-900 font-semibold"><?= Yii::$app->formatter->asDatetime($model->data_abertura) ?></p>
                        </div>
                        <?php if ($model->data_fechamento): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Data de Fechamento</label>
                                <p class="text-base text-gray-900 font-semibold"><?= Yii::$app->formatter->asDatetime($model->data_fechamento) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($model->colaborador): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Colaborador</label>
                                <p class="text-base text-gray-900 font-semibold"><?= Html::encode($model->colaborador->nome_completo) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Valores Financeiros -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Valores Financeiros
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Valor Inicial</label>
                            <p class="text-2xl font-bold text-gray-900">R$ <?= number_format($model->valor_inicial, 2, ',', '.') ?></p>
                        </div>
                        <?php if ($model->isAberto()): ?>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <label class="block text-sm font-medium text-green-700 mb-1">Valor Esperado</label>
                                <p class="text-2xl font-bold text-green-700">R$ <?= number_format($valorEsperado, 2, ',', '.') ?></p>
                            </div>
                        <?php else: ?>
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <label class="block text-sm font-medium text-blue-700 mb-1">Valor Esperado</label>
                                <p class="text-2xl font-bold text-blue-700">R$ <?= number_format($model->valor_esperado ?? 0, 2, ',', '.') ?></p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Valor Final</label>
                                <p class="text-2xl font-bold text-gray-900">R$ <?= number_format($model->valor_final ?? 0, 2, ',', '.') ?></p>
                            </div>
                            <?php if ($model->diferenca !== null): ?>
                                <?php
                                if ($model->diferenca >= 0) {
                                    $diferencaBgClass = 'bg-green-50';
                                    $diferencaTextClass = 'text-green-700';
                                } else {
                                    $diferencaBgClass = 'bg-red-50';
                                    $diferencaTextClass = 'text-red-700';
                                }
                                ?>
                                <div class="<?= $diferencaBgClass ?> p-4 rounded-lg">
                                    <label class="block text-sm font-medium <?= $diferencaTextClass ?> mb-1">Diferença</label>
                                    <p class="text-2xl font-bold <?= $diferencaTextClass ?>">
                                        <?= $model->diferenca >= 0 ? '+' : '' ?>R$ <?= number_format($model->diferenca, 2, ',', '.') ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Observações -->
                <?php if ($model->observacoes): ?>
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                            Observações
                        </h3>
                        <p class="text-base text-gray-700 whitespace-pre-line"><?= Html::encode($model->observacoes) ?></p>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Movimentações -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white">Movimentações</h2>
                    <?php if ($model->isAberto()): ?>
                        <?= Html::a(
                            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Nova Movimentação',
                            ['movimentacao/create', 'caixa_id' => $model->id],
                            ['class' => 'inline-flex items-center px-3 py-1 bg-white text-green-600 font-semibold rounded-lg shadow-sm hover:bg-gray-50 transition duration-300 text-sm']
                        ) ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="p-6">
                <?php if ($movimentacoesDataProvider->getTotalCount() > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data/Hora</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoria</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
                                    <?php if ($model->isAberto()): ?>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ações</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($movimentacoesDataProvider->getModels() as $mov): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            <?= Yii::$app->formatter->asDatetime($mov->data_movimento) ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php
                                            $tipoColors = [
                                                'ENTRADA' => 'bg-green-100 text-green-800',
                                                'SAIDA' => 'bg-red-100 text-red-800',
                                            ];
                                            $tipoColor = $tipoColors[$mov->tipo] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold <?= $tipoColor ?>">
                                                <?= Html::encode($mov->tipo) ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            <?= Html::encode($mov->categoria ?? '-') ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            <?= Html::encode($mov->descricao) ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right font-semibold <?= $mov->tipo === 'ENTRADA' ? 'text-green-600' : 'text-red-600' ?>">
                                            <?= $mov->tipo === 'ENTRADA' ? '+' : '-' ?> R$ <?= number_format($mov->valor, 2, ',', '.') ?>
                                        </td>
                                        <?php if ($model->isAberto()): ?>
                                            <td class="px-4 py-3 text-center">
                                                <div class="flex justify-center gap-2">
                                                    <?= Html::a(
                                                        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
                                                        ['movimentacao/update', 'id' => $mov->id],
                                                        ['class' => 'text-blue-600 hover:text-blue-800', 'title' => 'Editar']
                                                    ) ?>
                                                    <?= Html::a(
                                                        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>',
                                                        ['movimentacao/delete', 'id' => $mov->id],
                                                        [
                                                            'class' => 'text-red-600 hover:text-red-800',
                                                            'title' => 'Deletar',
                                                            'data' => [
                                                                'confirm' => 'Tem certeza que deseja deletar esta movimentação?',
                                                                'method' => 'post',
                                                            ],
                                                        ]
                                                    ) ?>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div class="mt-4">
                        <?= LinkPager::widget([
                            'pagination' => $movimentacoesDataProvider->pagination,
                            'options' => ['class' => 'flex justify-center'],
                            'linkOptions' => ['class' => 'px-3 py-2 mx-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50'],
                            'activePageCssClass' => 'bg-blue-600 text-white border-blue-600',
                            'disabledPageCssClass' => 'opacity-50 cursor-not-allowed',
                        ]) ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma movimentação registrada</h3>
                        <p class="mt-1 text-sm text-gray-500">Comece registrando uma nova movimentação.</p>
                        <?php if ($model->isAberto()): ?>
                            <div class="mt-6">
                                <?= Html::a(
                                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Nova Movimentação',
                                    ['movimentacao/create', 'caixa_id' => $model->id],
                                    ['class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
                                ) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

