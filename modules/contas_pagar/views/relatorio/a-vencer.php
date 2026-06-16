<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;
use app\modules\contas_pagar\models\ContaPagar;

$this->title = "Contas a Vencer ({$dias} dias)";
$this->params['breadcrumbs'][] = ['label' => 'Contas a Pagar', 'url' => ['/contas-pagar/conta-pagar/index']];
$this->params['breadcrumbs'][] = ['label' => 'Relatórios', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 lg:px-8">

    <div class="max-w-7xl mx-auto">

        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                    <p class="text-gray-600 mt-1">Contas com vencimento nos próximos <?= $dias ?> dias</p>
                </div>
                <div class="flex gap-2">
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                        ['index'],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300']
                    ) ?>
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>Exportar PDF',
                        ['export-pdf', 'tipo' => 'a-vencer', 'dias' => $dias],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
                    ) ?>
                </div>
            </div>
        </div>

        <!-- Filtros de Período -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex flex-wrap gap-2">
                <span class="text-sm text-gray-600 self-center mr-2">Período:</span>
                <?= Html::a('7 dias', ['a-vencer', 'dias' => 7], ['class' => 'px-4 py-2 rounded-lg transition ' . ($dias == 7 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')]) ?>
                <?= Html::a('15 dias', ['a-vencer', 'dias' => 15], ['class' => 'px-4 py-2 rounded-lg transition ' . ($dias == 15 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')]) ?>
                <?= Html::a('30 dias', ['a-vencer', 'dias' => 30], ['class' => 'px-4 py-2 rounded-lg transition ' . ($dias == 30 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')]) ?>
                <?= Html::a('60 dias', ['a-vencer', 'dias' => 60], ['class' => 'px-4 py-2 rounded-lg transition ' . ($dias == 60 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')]) ?>
                <?= Html::a('90 dias', ['a-vencer', 'dias' => 90], ['class' => 'px-4 py-2 rounded-lg transition ' . ($dias == 90 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')]) ?>
            </div>
        </div>

        <!-- Resumo -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-lg">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <p class="font-semibold text-blue-900">Total a Vencer: <?= Yii::$app->formatter->asCurrency($totalValor) ?></p>
                    <p class="text-sm text-blue-700"><?= $dataProvider->getTotalCount() ?> conta(s) encontrada(s)</p>
                </div>
            </div>
        </div>

        <!-- Tabela -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fornecedor</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimento</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Dias Restantes</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($dataProvider->getModels() as $model): ?>
                            <?php
                            $hoje = new DateTime();
                            $vencimento = new DateTime($model->data_vencimento);
                            $diff = $hoje->diff($vencimento);
                            $diasRestantes = $diff->days;

                            // Cor baseada em dias restantes
                            $corDias = 'text-gray-700';
                            if ($diasRestantes <= 3) {
                                $corDias = 'text-red-600 font-bold';
                            } elseif ($diasRestantes <= 7) {
                                $corDias = 'text-orange-600 font-semibold';
                            } elseif ($diasRestantes <= 15) {
                                $corDias = 'text-yellow-600';
                            }
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?= Html::encode($model->descricao) ?></div>
                                    <?php if ($model->compra_id): ?>
                                        <div class="text-xs text-gray-500">Ref. Compra #<?= substr($model->compra_id, 0, 8) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?= $model->fornecedor ? Html::encode($model->fornecedor->nome) : '-' ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-sm font-bold text-gray-900"><?= Yii::$app->formatter->asCurrency($model->valor) ?></span>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-900">
                                    <?= Yii::$app->formatter->asDate($model->data_vencimento) ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm <?= $corDias ?>"><?= $diasRestantes ?> dia(s)</span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <?= Html::a('Ver', ['/contas-pagar/conta-pagar/view', 'id' => $model->id], ['class' => 'text-blue-600 hover:text-blue-900']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginação -->
        <div class="mt-6">
            <?= LinkPager::widget([
                'pagination' => $dataProvider->pagination,
                'options' => ['class' => 'flex justify-center flex-wrap gap-2'],
                'linkOptions' => ['class' => 'px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50 text-sm'],
                'activePageCssClass' => 'bg-blue-600 text-white border-blue-600',
                'disabledPageCssClass' => 'opacity-50 cursor-not-allowed',
                'prevPageLabel' => '←',
                'nextPageLabel' => '→',
            ]) ?>
        </div>

    </div>

</div>