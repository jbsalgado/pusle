<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

$this->title = 'Parcelas';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                    <p class="mt-1 text-sm text-gray-600">Gerencie parcelas de vendas</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                        ['/vendas/inicio/index'],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300']
                    ) ?>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Filtros</h2>
            <?php $form = ActiveForm::begin([
                'method' => 'get',
                'action' => ['index'],
                'options' => ['class' => 'space-y-4'],
            ]); ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Cliente Nome -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Comprador</label>
                    <?= Html::textInput('cliente_nome', Yii::$app->request->get('cliente_nome'), [
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                        'placeholder' => 'Nome do cliente'
                    ]) ?>
                </div>

                <!-- CPF -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
                    <?= Html::textInput('cliente_cpf', Yii::$app->request->get('cliente_cpf'), [
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                        'placeholder' => '000.000.000-00'
                    ]) ?>
                </div>

                <!-- Data Compra -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Compra</label>
                    <?= Html::input('date', 'data_compra', Yii::$app->request->get('data_compra'), [
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                    ]) ?>
                </div>

                <!-- Data Vencimento -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Vencimento</label>
                    <?= Html::input('date', 'data_vencimento', Yii::$app->request->get('data_vencimento'), [
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                    ]) ?>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <?= Html::dropDownList('status', Yii::$app->request->get('status'), ['' => 'Todos'] + $statusList, [
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                    ]) ?>
                </div>

                <!-- Valor Mínimo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor Mínimo</label>
                    <?= Html::input('number', 'valor_min', Yii::$app->request->get('valor_min'), [
                        'step' => '0.01',
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                        'placeholder' => '0.00'
                    ]) ?>
                </div>

                <!-- Valor Máximo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor Máximo</label>
                    <?= Html::input('number', 'valor_max', Yii::$app->request->get('valor_max'), [
                        'step' => '0.01',
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                        'placeholder' => '0.00'
                    ]) ?>
                </div>
            </div>

            <div class="flex gap-2 pt-4">
                <?= Html::submitButton('Filtrar', [
                    'class' => 'px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300'
                ]) ?>
                <?= Html::a('Limpar', ['index'], [
                    'class' => 'px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
        
        <!-- Tabela de Parcelas -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comprador</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Compra</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parcela</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($dataProvider->totalCount > 0): ?>
                            <?php foreach ($dataProvider->getModels() as $model): ?>
                                <?php
                                $cliente = $model->venda->cliente ?? null;
                                $estaVencida = $model->getEstaVencida();
                                $estaPaga = $model->status_parcela_codigo === \app\modules\vendas\models\StatusParcela::PAGA;
                                $estaCancelada = $model->status_parcela_codigo === \app\modules\vendas\models\StatusParcela::CANCELADA;
                                
                                $statusClass = $estaPaga ? 'bg-green-100 text-green-800' : 
                                             ($estaVencida ? 'bg-red-100 text-red-800' : 
                                             ($estaCancelada ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800'));
                                ?>
                                <tr class="hover:bg-gray-50 <?= $estaVencida && !$estaPaga ? 'bg-red-50' : '' ?>">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= Html::encode($cliente ? $cliente->nome_completo : 'Venda Direta') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $cliente && $cliente->cpf ? Html::encode($cliente->cpf) : '-' ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= Yii::$app->formatter->asDate($model->venda->data_venda) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= $model->numero_parcela ?>/<?= ($model->venda->parcelas ? count($model->venda->parcelas) : '-') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-green-600">
                                        R$ <?= Yii::$app->formatter->asDecimal($model->valor_parcela, 2) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?= $estaVencida && !$estaPaga ? 'text-red-600 font-semibold' : 'text-gray-500' ?>">
                                        <?= Yii::$app->formatter->asDate($model->data_vencimento) ?>
                                        <?php if ($estaVencida && !$estaPaga): ?>
                                            <span class="ml-2 text-xs">(<?= $model->getDiasAtraso() ?> dias)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusClass ?>">
                                            <?= Html::encode($model->statusParcela->descricao ?? $model->status_parcela_codigo) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex justify-center gap-2">
                                            <?= Html::a('Ver', ['view', 'id' => $model->id], [
                                                'class' => 'text-blue-600 hover:text-blue-900'
                                            ]) ?>
                                            
                                            <?php if (!$estaPaga && !$estaCancelada): ?>
                                                <?= Html::beginForm(['receber', 'id' => $model->id], 'post', [
                                                    'style' => 'display: inline-block;',
                                                    'onsubmit' => 'return confirm("Tem certeza que deseja marcar esta parcela como recebida?");'
                                                ]) ?>
                                                    <?= Html::submitButton('Receber', [
                                                        'class' => 'text-green-600 hover:text-green-900 bg-transparent border-0 p-0 cursor-pointer'
                                                    ]) ?>
                                                <?= Html::endForm() ?>
                                                
                                                <?= Html::a('Editar', ['update', 'id' => $model->id], [
                                                    'class' => 'text-yellow-600 hover:text-yellow-900'
                                                ]) ?>
                                                
                                                <?= Html::beginForm(['cancelar', 'id' => $model->id], 'post', [
                                                    'style' => 'display: inline-block;',
                                                    'onsubmit' => 'return confirm("Tem certeza que deseja cancelar esta parcela?");'
                                                ]) ?>
                                                    <?= Html::submitButton('Cancelar', [
                                                        'class' => 'text-red-600 hover:text-red-900 bg-transparent border-0 p-0 cursor-pointer'
                                                    ]) ?>
                                                <?= Html::endForm() ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    Nenhuma parcela encontrada
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Paginação -->
        <?php if ($dataProvider->pagination->pageCount > 1): ?>
            <div class="mt-6">
                <?= LinkPager::widget([
                    'pagination' => $dataProvider->pagination,
                    'options' => ['class' => 'flex justify-center space-x-2'],
                    'linkOptions' => ['class' => 'px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50'],
                    'activePageCssClass' => 'bg-blue-600 text-white border-blue-600',
                    'prevPageLabel' => '←',
                    'nextPageLabel' => '→',
                ]) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
