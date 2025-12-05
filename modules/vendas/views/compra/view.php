<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Compra #' . substr($model->id, 0, 8);
$this->params['breadcrumbs'][] = ['label' => 'Compras', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        
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
                    <?php if ($model->status_compra === 'PENDENTE'): ?>
                        <?= Html::a(
                            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar',
                            ['update', 'id' => $model->id],
                            ['class' => 'inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm']
                        ) ?>
                        <?= Html::a(
                            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Concluir',
                            ['concluir', 'id' => $model->id],
                            [
                                'class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm',
                                'data' => [
                                    'confirm' => 'Tem certeza que deseja concluir esta compra? O estoque será atualizado.',
                                    'method' => 'post',
                                ],
                            ]
                        ) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Card Principal -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white">Informações da Compra</h2>
                    <?php
                    $statusColors = [
                        'PENDENTE' => 'bg-yellow-100 text-yellow-800',
                        'CONCLUIDA' => 'bg-green-100 text-green-800',
                        'CANCELADA' => 'bg-red-100 text-red-800',
                    ];
                    $statusColor = $statusColors[$model->status_compra] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold <?= $statusColor ?>">
                        <?= Html::encode($model->getStatusLabel()) ?>
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
                            <label class="block text-sm font-medium text-gray-500 mb-1">Fornecedor</label>
                            <p class="text-base text-gray-900 font-semibold"><?= Html::encode($model->fornecedor->nome_fantasia) ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Data da Compra</label>
                            <p class="text-base text-gray-900"><?= Yii::$app->formatter->asDate($model->data_compra) ?></p>
                        </div>
                        <?php if ($model->data_vencimento): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Data de Vencimento</label>
                                <p class="text-base text-gray-900"><?= Yii::$app->formatter->asDate($model->data_vencimento) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($model->numero_nota_fiscal): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Número da NF</label>
                                <p class="text-base text-gray-900"><?= Html::encode($model->numero_nota_fiscal) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($model->serie_nota_fiscal): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Série da NF</label>
                                <p class="text-base text-gray-900"><?= Html::encode($model->serie_nota_fiscal) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($model->forma_pagamento): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Forma de Pagamento</label>
                                <p class="text-base text-gray-900"><?= Html::encode($model->forma_pagamento) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Itens da Compra -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        Itens da Compra
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantidade</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Preço Unit.</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($model->itens as $item): ?>
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900"><?= Html::encode($item->produto->nome) ?></td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900"><?= number_format($item->quantidade, 3, ',', '.') ?></td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900">R$ <?= number_format($item->preco_unitario, 2, ',', '.') ?></td>
                                        <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">R$ <?= number_format($item->valor_total_item, 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-gray-900">Subtotal:</td>
                                    <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">R$ <?= number_format($model->valor_total, 2, ',', '.') ?></td>
                                </tr>
                                <?php if ($model->valor_desconto > 0): ?>
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right text-sm text-gray-600">Desconto:</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-600">- R$ <?= number_format($model->valor_desconto, 2, ',', '.') ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($model->valor_frete > 0): ?>
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right text-sm text-gray-600">Frete:</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-600">+ R$ <?= number_format($model->valor_frete, 2, ',', '.') ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right text-base font-bold text-gray-900">Total:</td>
                                    <td class="px-4 py-3 text-right text-base font-bold text-gray-900">R$ <?= number_format($model->getValorLiquido(), 2, ',', '.') ?></td>
                                </tr>
                            </tfoot>
                        </table>
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

        <!-- Ações -->
        <div class="flex flex-wrap gap-2">
            <?php if ($model->status_compra === 'PENDENTE'): ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar',
                    ['update', 'id' => $model->id],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Concluir',
                    ['concluir', 'id' => $model->id],
                    [
                        'class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300',
                        'data' => [
                            'confirm' => 'Tem certeza que deseja concluir esta compra? O estoque será atualizado.',
                            'method' => 'post',
                        ],
                    ]
                ) ?>
            <?php endif; ?>
            <?php if ($model->status_compra !== 'CANCELADA' && $model->status_compra !== 'CONCLUIDA'): ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Cancelar',
                    ['cancelar', 'id' => $model->id],
                    [
                        'class' => 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-300',
                        'data' => [
                            'confirm' => 'Tem certeza que deseja cancelar esta compra?',
                            'method' => 'post',
                        ],
                    ]
                ) ?>
            <?php endif; ?>
        </div>

    </div>
</div>

