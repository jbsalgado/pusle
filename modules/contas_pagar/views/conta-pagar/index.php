<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use app\modules\contas_pagar\models\ContaPagar;

$this->title = 'Contas a Pagar';
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
                    ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm sm:text-base w-full sm:w-auto justify-center']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>Exportar CSV',
                    ['export'] + Yii::$app->request->get(),
                    ['class' => 'inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm sm:text-base w-full sm:w-auto justify-center']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Nova Conta',
                    ['create'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm sm:text-base w-full sm:w-auto justify-center']
                ) ?>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">

        <!-- Filtros e Busca -->
        <div class="bg-white rounded-lg shadow-md mb-4 sm:mb-6 p-4 sm:p-6">
            <form method="get" class="space-y-4">

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                            <option value="">Todos</option>
                            <option value="<?= ContaPagar::STATUS_PENDENTE ?>" <?= Yii::$app->request->get('status') === ContaPagar::STATUS_PENDENTE ? 'selected' : '' ?>>Pendente</option>
                            <option value="<?= ContaPagar::STATUS_PAGA ?>" <?= Yii::$app->request->get('status') === ContaPagar::STATUS_PAGA ? 'selected' : '' ?>>Paga</option>
                            <option value="<?= ContaPagar::STATUS_VENCIDA ?>" <?= Yii::$app->request->get('status') === ContaPagar::STATUS_VENCIDA ? 'selected' : '' ?>>Vencida</option>
                            <option value="<?= ContaPagar::STATUS_CANCELADA ?>" <?= Yii::$app->request->get('status') === ContaPagar::STATUS_CANCELADA ? 'selected' : '' ?>>Cancelada</option>
                        </select>
                    </div>

                </div>

                <div class="flex flex-col sm:flex-row gap-2">
                    <button type="submit" class="w-full sm:w-auto px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300 text-sm sm:text-base">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Filtrar
                    </button>
                    <?php if (Yii::$app->request->queryParams): ?>
                        <?= Html::a('Limpar Filtros', ['index'], ['class' => 'w-full sm:w-auto px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition duration-300 text-center text-sm sm:text-base']) ?>
                    <?php endif; ?>
                </div>

            </form>
        </div>

        <!-- Toggle View e Contador -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
            <span class="text-sm sm:text-base text-gray-600">
                <?= $dataProvider->getTotalCount() ?> conta(s) encontrada(s)
            </span>
            <div class="flex gap-2">
                <?= Html::a(
                    '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>',
                    ['index', 'view' => 'cards'] + Yii::$app->request->get(),
                    ['class' => 'p-2 rounded-lg transition duration-300 ' . ($viewMode == 'cards' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'), 'title' => 'Visualização em Cards']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/></svg>',
                    ['index', 'view' => 'grid'] + Yii::$app->request->get(),
                    ['class' => 'p-2 rounded-lg transition duration-300 ' . ($viewMode == 'grid' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'), 'title' => 'Visualização em Lista']
                ) ?>
            </div>
        </div>

        <?php if ($viewMode == 'cards'): ?>

            <!-- Visualização em Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                <?php foreach ($dataProvider->getModels() as $model): ?>
                    <?php
                    // Status Colors
                    $statusBg = 'bg-gray-600';
                    $statusText = 'text-white';
                    if ($model->isPaga()) {
                        $statusBg = 'bg-green-600';
                    } elseif ($model->isVencida()) {
                        $statusBg = 'bg-red-600';
                    } elseif ($model->isPendente()) {
                        $statusBg = 'bg-yellow-500';
                    }
                    ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100">

                        <!-- Header do Card -->
                        <div class="p-4 border-b border-gray-100 flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="text-base font-bold text-gray-900 truncate" title="<?= Html::encode($model->descricao) ?>">
                                    <?= Html::encode($model->descricao) ?>
                                </h3>
                                <div class="text-xs text-gray-500 mt-1">
                                    <?= $model->fornecedor ? Html::encode($model->fornecedor->nome) : 'Sem Fornecedor' ?>
                                </div>
                            </div>
                            <span class="px-2 py-1 <?= $statusBg ?> <?= $statusText ?> text-xs font-semibold rounded-full ml-2">
                                <?= Html::encode($model->status) ?>
                            </span>
                        </div>

                        <!-- Conteúdo -->
                        <div class="p-4 space-y-3">
                            <div class="flex justify-between items-center">
                                <div class="text-sm text-gray-500">Valor</div>
                                <div class="text-lg font-bold text-gray-900">
                                    <?= Yii::$app->formatter->asCurrency($model->valor) ?>
                                </div>
                            </div>

                            <div class="flex justify-between items-center">
                                <div class="text-sm text-gray-500">Vencimento</div>
                                <div class="text-sm font-medium <?= $model->isVencida() ? 'text-red-600' : 'text-gray-700' ?>">
                                    <?= Yii::$app->formatter->asDate($model->data_vencimento) ?>
                                </div>
                            </div>

                            <?php if ($model->data_pagamento): ?>
                                <div class="flex justify-between items-center bg-green-50 p-2 rounded">
                                    <div class="text-xs text-green-700">Pago em</div>
                                    <div class="text-sm font-bold text-green-700">
                                        <?= Yii::$app->formatter->asDate($model->data_pagamento) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Ações -->
                        <div class="p-4 bg-gray-50 flex gap-2">
                            <?= Html::a(
                                'Ver',
                                ['view', 'id' => $model->id],
                                ['class' => 'flex-1 text-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold rounded transition duration-300']
                            ) ?>

                            <?php if (!$model->isPaga() && $model->status !== ContaPagar::STATUS_CANCELADA): ?>
                                <?= Html::a('Pagar', ['pagar', 'id' => $model->id], [
                                    'class' => 'flex-1 text-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded transition duration-300',
                                    'data' => [
                                        'method' => 'post',
                                        'confirm' => 'Confirmar pagamento desta conta?'
                                    ]
                                ]) ?>
                            <?php endif; ?>

                            <?= Html::a(
                                'Editar',
                                ['update', 'id' => $model->id],
                                ['class' => 'flex-1 text-center px-3 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-semibold rounded transition duration-300' . ($model->isPaga() ? ' opacity-50 cursor-not-allowed pointer-events-none' : '')]
                            ) ?>
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
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Fornecedor</th>
                                <th class="px-3 sm:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                <th class="px-3 sm:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimento</th>
                                <th class="px-3 sm:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-3 sm:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($dataProvider->getModels() as $model): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 sm:px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?= Html::encode($model->descricao) ?></div>
                                        <?php if ($model->compra_id): ?>
                                            <div class="text-xs text-gray-500">Ref. Compra #<?= substr($model->compra_id, 0, 8) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 hidden md:table-cell">
                                        <div class="text-sm text-gray-900">
                                            <?= $model->fornecedor ? Html::encode($model->fornecedor->nome) : '-' ?>
                                        </div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 text-right">
                                        <span class="text-sm font-bold text-gray-900"><?= Yii::$app->formatter->asCurrency($model->valor) ?></span>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 text-center">
                                        <span class="text-sm <?= $model->isVencida() ? 'text-red-600 font-bold' : 'text-gray-900' ?>">
                                            <?= Yii::$app->formatter->asDate($model->data_vencimento) ?>
                                        </span>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 text-center">
                                        <?php
                                        // Status Badge
                                        $badgeClass = 'bg-gray-100 text-gray-800';
                                        if ($model->isPaga()) $badgeClass = 'bg-green-100 text-green-800';
                                        elseif ($model->isVencida()) $badgeClass = 'bg-red-100 text-red-800';
                                        elseif ($model->isPendente()) $badgeClass = 'bg-yellow-100 text-yellow-800';
                                        ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full whitespace-nowrap <?= $badgeClass ?>">
                                            <?= Html::encode($model->status) ?>
                                        </span>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 text-right text-sm font-medium">
                                        <div class="flex justify-end gap-2">
                                            <?= Html::a('Ver', ['view', 'id' => $model->id], ['class' => 'text-blue-600 hover:text-blue-900']) ?>

                                            <?php if (!$model->isPaga() && $model->status !== ContaPagar::STATUS_CANCELADA): ?>
                                                <?= Html::a('Pagar', ['pagar', 'id' => $model->id], [
                                                    'class' => 'text-green-600 hover:text-green-900',
                                                    'data' => [
                                                        'method' => 'post',
                                                        'confirm' => 'Confirmar pagamento?'
                                                    ]
                                                ]) ?>
                                            <?php endif; ?>

                                            <?= Html::a('Editar', ['update', 'id' => $model->id], [
                                                'class' => 'text-yellow-600 hover:text-yellow-900' . ($model->isPaga() ? ' invisible' : '')
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

        <!-- Paginação -->
        <div class="mt-6">
            <?= LinkPager::widget([
                'pagination' => $dataProvider->pagination,
                'options' => ['class' => 'flex justify-center flex-wrap gap-1 sm:gap-2'],
                'linkOptions' => ['class' => 'px-2 sm:px-3 py-1 sm:py-2 bg-white border border-gray-300 rounded hover:bg-gray-50 text-sm'],
                'activePageCssClass' => 'bg-blue-600 text-white border-blue-600',
                'disabledPageCssClass' => 'opacity-50 cursor-not-allowed',
                'prevPageLabel' => '←',
                'nextPageLabel' => '→',
            ]) ?>
        </div>

    </div>
</div>