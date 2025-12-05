<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Compras / Resuprimentos';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    
    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
            <div class="flex flex-wrap gap-2">
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                    ['/vendas/inicio/index'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Nova Compra',
                    ['create'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>Histórico por Produto',
                    ['historico-produto'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
                ) ?>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">
        
        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-md mb-6 p-6">
            <form method="get" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fornecedor</label>
                        <select name="fornecedor_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Todos</option>
                            <?php foreach ($fornecedores as $id => $nome): ?>
                                <option value="<?= $id ?>" <?= Yii::$app->request->get('fornecedor_id') == $id ? 'selected' : '' ?>>
                                    <?= Html::encode($nome) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Todos</option>
                            <option value="PENDENTE" <?= Yii::$app->request->get('status') == 'PENDENTE' ? 'selected' : '' ?>>Pendente</option>
                            <option value="CONCLUIDA" <?= Yii::$app->request->get('status') == 'CONCLUIDA' ? 'selected' : '' ?>>Concluída</option>
                            <option value="CANCELADA" <?= Yii::$app->request->get('status') == 'CANCELADA' ? 'selected' : '' ?>>Cancelada</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data Início</label>
                        <input type="date" name="data_inicio" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?= Html::encode(Yii::$app->request->get('data_inicio', '')) ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data Fim</label>
                        <input type="date" name="data_fim" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?= Html::encode(Yii::$app->request->get('data_fim', '')) ?>">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300">
                        Buscar
                    </button>
                    <?php if (Yii::$app->request->queryParams): ?>
                        <?= Html::a('Limpar Filtros', ['index'], ['class' => 'px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition duration-300']) ?>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Lista de Compras -->
        <div class="space-y-4">
            <?php foreach ($dataProvider->getModels() as $model): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300">
                    <div class="p-6">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-xl font-semibold text-gray-900">
                                        Compra #<?= substr($model->id, 0, 8) ?>
                                    </h3>
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
                                <div class="text-sm text-gray-600 space-y-1">
                                    <p><strong>Fornecedor:</strong> <?= Html::encode($model->fornecedor->nome_fantasia) ?></p>
                                    <p><strong>Data:</strong> <?= Yii::$app->formatter->asDate($model->data_compra) ?></p>
                                    <?php if ($model->numero_nota_fiscal): ?>
                                        <p><strong>NF:</strong> <?= Html::encode($model->numero_nota_fiscal) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-gray-900">
                                    R$ <?= number_format($model->valor_total, 2, ',', '.') ?>
                                </p>
                                <p class="text-sm text-gray-500">
                                    <?= count($model->itens) ?> item(ns)
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 pt-4 border-t border-gray-200">
                            <?= Html::a('Ver Detalhes', ['view', 'id' => $model->id], ['class' => 'px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300 text-sm']) ?>
                            <?php if ($model->status_compra === 'PENDENTE'): ?>
                                <?= Html::a('Editar', ['update', 'id' => $model->id], ['class' => 'px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg transition duration-300 text-sm']) ?>
                                <?= Html::a('Concluir', ['concluir', 'id' => $model->id], [
                                    'class' => 'px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-300 text-sm',
                                    'data' => [
                                        'confirm' => 'Tem certeza que deseja concluir esta compra? O estoque será atualizado.',
                                        'method' => 'post',
                                    ],
                                ]) ?>
                            <?php endif; ?>
                            <?php if ($model->status_compra !== 'CANCELADA' && $model->status_compra !== 'CONCLUIDA'): ?>
                                <?= Html::a('Cancelar', ['cancelar', 'id' => $model->id], [
                                    'class' => 'px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition duration-300 text-sm',
                                    'data' => [
                                        'confirm' => 'Tem certeza que deseja cancelar esta compra?',
                                        'method' => 'post',
                                    ],
                                ]) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginação -->
        <?php if ($dataProvider->pagination->pageCount > 1): ?>
            <div class="mt-6 flex justify-center">
                <?= \yii\widgets\LinkPager::widget([
                    'pagination' => $dataProvider->pagination,
                    'options' => ['class' => 'flex gap-2'],
                    'linkOptions' => ['class' => 'px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50'],
                    'activePageCssClass' => 'bg-blue-600 text-white border-blue-600',
                ]) ?>
            </div>
        <?php endif; ?>

    </div>
</div>

