<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Fechamento de Caixa';
$this->params['breadcrumbs'][] = ['label' => 'Relatórios', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$totalEntradas = 0;
$totalSaidas = 0;
foreach ($movimentacoes as $mov) {
    if ($mov->tipo_movimentacao === 'ENTRADA') {
        $totalEntradas += $mov->valor;
    } else {
        $totalSaidas += $mov->valor;
    }
}
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 lg:px-8">

    <div class="max-w-6xl mx-auto">

        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
            <div class="space-x-2">
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>PDF',
                    ['export-pdf', 'tipo' => 'fechamento', 'id' => $caixa->id],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition', 'target' => '_blank']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                    ['index'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition']
                ) ?>
            </div>
        </div>

        <!-- Informações do Caixa -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Informações do Caixa</h3>
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Data Abertura:</dt>
                            <dd class="font-semibold"><?= Yii::$app->formatter->asDatetime($caixa->data_abertura) ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Data Fechamento:</dt>
                            <dd class="font-semibold"><?= $caixa->data_fechamento ? Yii::$app->formatter->asDatetime($caixa->data_fechamento) : '-' ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Saldo Inicial:</dt>
                            <dd class="font-semibold text-blue-600"><?= Yii::$app->formatter->asCurrency($caixa->saldo_inicial) ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Status:</dt>
                            <dd>
                                <span class="px-3 py-1 rounded-full text-sm font-semibold <?= $caixa->status === 'ABERTO' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                    <?= $caixa->status ?>
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Resumo Financeiro</h3>
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Total Entradas:</dt>
                            <dd class="font-semibold text-green-600"><?= Yii::$app->formatter->asCurrency($totalEntradas) ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Total Saídas:</dt>
                            <dd class="font-semibold text-red-600"><?= Yii::$app->formatter->asCurrency($totalSaidas) ?></dd>
                        </div>
                        <div class="flex justify-between border-t pt-2 mt-2">
                            <dt class="text-gray-700 font-semibold">Saldo Final:</dt>
                            <dd class="font-bold text-xl text-blue-600"><?= Yii::$app->formatter->asCurrency($caixa->saldo_final ?? ($caixa->saldo_inicial + $totalEntradas - $totalSaidas)) ?></dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Movimentações -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b">
                <h3 class="text-lg font-semibold text-gray-700">Movimentações</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data/Hora</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($movimentacoes as $mov): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= Yii::$app->formatter->asDatetime($mov->data_movimentacao) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($mov->tipo_movimentacao === 'ENTRADA'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            ENTRADA
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                            SAÍDA
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= Html::encode($mov->categoria ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?= Html::encode($mov->descricao) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold <?= $mov->tipo_movimentacao === 'ENTRADA' ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= Yii::$app->formatter->asCurrency($mov->valor) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-right font-semibold text-gray-700">Total:</td>
                            <td class="px-6 py-4 text-right font-bold text-lg text-blue-600">
                                <?= Yii::$app->formatter->asCurrency($totalEntradas - $totalSaidas) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>

</div>