<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;
use app\modules\vendas\models\PeriodoCobranca;

$this->title = 'Períodos de Cobrança';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <div class="flex flex-wrap gap-2">
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Novo Período',
                        ['create'],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
                    ) ?>
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                        ['/vendas/inicio/index'],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300']
                    ) ?>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data Início</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data Fim</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($dataProvider->totalCount > 0): ?>
                            <?php foreach ($dataProvider->getModels() as $model): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= Html::encode($model->descricao) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= Yii::$app->formatter->asDate($model->data_inicio) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= Yii::$app->formatter->asDate($model->data_fim) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $statusClass = $model->status === PeriodoCobranca::STATUS_FECHADO ? 'bg-gray-100 text-gray-800' : 
                                                      ($model->status === PeriodoCobranca::STATUS_EM_COBRANCA ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800');
                                        ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusClass ?>">
                                            <?= Html::encode($model->status) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex justify-center gap-2">
                                            <?= Html::a('Ver', ['view', 'id' => $model->id], ['class' => 'text-blue-600 hover:text-blue-900']) ?>
                                            <?= Html::a('Editar', ['update', 'id' => $model->id], ['class' => 'text-yellow-600 hover:text-yellow-900']) ?>
                                            <?= Html::a('Excluir', ['delete', 'id' => $model->id], [
                                                'class' => 'text-red-600 hover:text-red-900',
                                                'data' => [
                                                    'confirm' => 'Tem certeza que deseja excluir este período?',
                                                    'method' => 'post',
                                                ],
                                            ]) ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    Nenhum período encontrado. Crie um novo período para começar.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
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

