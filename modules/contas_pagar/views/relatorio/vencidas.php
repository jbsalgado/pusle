<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;
use app\modules\contas_pagar\models\ContaPagar;

$this->title = 'Contas Vencidas';
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
                    <h1 class="text-3xl font-bold text-red-600"><?= Html::encode($this->title) ?></h1>
                    <p class="text-gray-600 mt-1">Contas com pagamento atrasado</p>
                </div>
                <div class="flex gap-2">
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                        ['index'],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300']
                    ) ?>
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>Exportar PDF',
                        ['export-pdf', 'tipo' => 'vencidas'],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
                    ) ?>
                </div>
            </div>
        </div>

        <!-- Alerta -->
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <p class="font-semibold text-red-900">Total em Atraso: <?= Yii::$app->formatter->asCurrency($totalValor) ?></p>
                    <p class="text-sm text-red-700"><?= $dataProvider->getTotalCount() ?> conta(s) vencida(s)</p>
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
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Dias de Atraso</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($dataProvider->getModels() as $model): ?>
                            <?php
                            $diasAtraso = $model->getDiasAtraso();

                            // Cor baseada em dias de atraso
                            $corAtraso = 'text-orange-600';
                            $bgRow = '';
                            if ($diasAtraso > 30) {
                                $corAtraso = 'text-red-700 font-bold';
                                $bgRow = 'bg-red-50';
                            } elseif ($diasAtraso > 15) {
                                $corAtraso = 'text-red-600 font-semibold';
                            }
                            ?>
                            <tr class="hover:bg-gray-50 <?= $bgRow ?>">
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
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm text-red-600 font-semibold"><?= Yii::$app->formatter->asDate($model->data_vencimento) ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $corAtraso ?> bg-red-100">
                                        <?= $diasAtraso ?> dia(s)
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                                    <?= Html::a('Ver', ['/contas-pagar/conta-pagar/view', 'id' => $model->id], ['class' => 'text-blue-600 hover:text-blue-900']) ?>
                                    <?= Html::a('Pagar', ['/contas-pagar/conta-pagar/pagar', 'id' => $model->id], [
                                        'class' => 'text-green-600 hover:text-green-900',
                                        'data' => [
                                            'method' => 'post',
                                            'confirm' => 'Confirmar pagamento desta conta?'
                                        ]
                                    ]) ?>
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