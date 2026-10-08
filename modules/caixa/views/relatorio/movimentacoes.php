<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Movimentações de Caixa';
$this->params['breadcrumbs'][] = ['label' => 'Relatórios', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 lg:px-8">

    <div class="max-w-7xl mx-auto">

        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
            <?= Html::a(
                '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                ['index'],
                ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition']
            ) ?>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <form method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data Início</label>
                    <input type="date" name="data_inicio" value="<?= Html::encode($dataInicio) ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data Fim</label>
                    <input type="date" name="data_fim" value="<?= Html::encode($dataFim) ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Resumo Segregado -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
            <div class="bg-white rounded-2xl shadow-sm p-5 border-l-4 border-emerald-500">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md">Vendas (Operacional)</span>
                <p class="text-2xl font-black text-gray-900 mt-2"><?= Yii::$app->formatter->asCurrency($totalVendas ?? $totalEntradas) ?></p>
                <p class="text-[11px] text-gray-400 mt-1">Faturamento de vendas no período</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-5 border-l-4 border-sky-500">
                <span class="text-xs font-bold uppercase tracking-wider text-sky-700 bg-sky-50 px-2 py-0.5 rounded-md">Aportes Contábeis</span>
                <p class="text-2xl font-black text-gray-900 mt-2"><?= Yii::$app->formatter->asCurrency($totalAportes ?? 0) ?></p>
                <p class="text-[11px] text-gray-400 mt-1">Coberturas p/ contas (sem faturamento)</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-5 border-l-4 border-rose-500">
                <span class="text-xs font-bold uppercase tracking-wider text-rose-700 bg-rose-50 px-2 py-0.5 rounded-md">Total Saídas</span>
                <p class="text-2xl font-black text-rose-600 mt-2"><?= Yii::$app->formatter->asCurrency($totalSaidas) ?></p>
                <p class="text-[11px] text-gray-400 mt-1">Despesas e pagamentos efetuados</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-5 border-l-4 border-indigo-500">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md">Saldo do Período</span>
                <p class="text-2xl font-black text-indigo-700 mt-2"><?= Yii::$app->formatter->asCurrency($totalEntradas - $totalSaidas) ?></p>
                <p class="text-[11px] text-gray-400 mt-1">Impacto líquido nas gavetas</p>
            </div>
        </div>

        <!-- Botões de Exportação -->
        <div class="mb-4 flex justify-end space-x-2">
            <?= Html::a(
                '<svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>PDF',
                ['export-pdf', 'tipo' => 'movimentacoes', 'data_inicio' => $dataInicio, 'data_fim' => $dataFim],
                ['class' => 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition', 'target' => '_blank']
            ) ?>
            <?= Html::a(
                '<svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Excel',
                ['export-excel', 'tipo' => 'movimentacoes', 'data_inicio' => $dataInicio, 'data_fim' => $dataFim],
                ['class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition']
            ) ?>
        </div>

        <!-- Tabela de Movimentações -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/60">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Data/Hora</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Categoria</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Descrição</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Forma Pagamento</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100 text-sm">
                        <?php foreach ($dataProvider->models as $mov): ?>
                            <tr class="hover:bg-gray-50/80 transition-colors">
                                <td class="px-6 py-3.5 whitespace-nowrap text-gray-600 text-xs font-mono">
                                    <?= Yii::$app->formatter->asDatetime($mov->data_movimento) ?>
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap">
                                    <?php if ($mov->tipo === 'ENTRADA'): ?>
                                        <span class="px-2.5 py-1 text-xs font-extrabold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            + ENTRADA
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 text-xs font-extrabold rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                                            - SAÍDA
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap">
                                    <?php if ($mov->isAporteConta()): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100 text-sky-800">
                                            Aporte Não Operacional
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs font-semibold text-gray-700">
                                            <?= Html::encode($mov->getCategoriaNome()) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-3.5 text-gray-700">
                                    <div class="font-medium"><?= Html::encode($mov->descricao) ?></div>
                                    <?php if ($mov->isAporteConta()): ?>
                                        <div class="text-[11px] text-sky-600 font-medium">⚠️ Lançamento contábil de contrapartida (não afeta faturamento)</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap text-gray-600 text-xs">
                                    <?= Html::encode($mov->formaPagamento ? $mov->formaPagamento->nome : 'N/A') ?>
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap text-right font-black font-mono <?= $mov->tipo === 'ENTRADA' ? 'text-emerald-600' : 'text-rose-600' ?>">
                                    <?= $mov->tipo === 'ENTRADA' ? '+' : '-' ?> <?= Yii::$app->formatter->asCurrency($mov->valor) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div class="bg-gray-50 px-6 py-4">
                <?= LinkPager::widget([
                    'pagination' => $dataProvider->pagination,
                    'options' => ['class' => 'flex justify-center space-x-2'],
                    'linkOptions' => ['class' => 'px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50'],
                    'activePageCssClass' => 'bg-blue-600 text-white border-blue-600',
                ]) ?>
            </div>
        </div>

    </div>

</div>