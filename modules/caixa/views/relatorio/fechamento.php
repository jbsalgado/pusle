<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Fechamento de Caixa';
$this->params['breadcrumbs'][] = ['label' => 'Relatórios', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$totalVendas = $totalVendas ?? 0;
$totalAportes = $totalAportes ?? 0;
$totalSuprimentos = $totalSuprimentos ?? 0;
$totalEntradas = $totalEntradas ?? 0;
$totalSaidas = $totalSaidas ?? 0;

if (!isset($totalVendas) || $totalEntradas == 0 && !empty($movimentacoes)) {
    $totalVendas = 0;
    $totalAportes = 0;
    $totalEntradas = 0;
    $totalSaidas = 0;
    foreach ($movimentacoes as $mov) {
        $val = (float)$mov->valor;
        if ($mov->tipo === 'ENTRADA') {
            $totalEntradas += $val;
            if ($mov->isAporteConta()) {
                $totalAportes += $val;
            } else {
                $totalVendas += $val;
            }
        } else {
            $totalSaidas += $val;
        }
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
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Informações do Caixa
                    </h3>
                    <dl class="space-y-2.5">
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <dt class="text-gray-500 text-sm">Data Abertura:</dt>
                            <dd class="font-bold text-gray-800 text-sm"><?= Yii::$app->formatter->asDatetime($caixa->data_abertura) ?></dd>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <dt class="text-gray-500 text-sm">Data Fechamento:</dt>
                            <dd class="font-bold text-gray-800 text-sm"><?= $caixa->data_fechamento ? Yii::$app->formatter->asDatetime($caixa->data_fechamento) : '-' ?></dd>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <dt class="text-gray-500 text-sm">Saldo Inicial da Gaveta:</dt>
                            <dd class="font-bold text-blue-600 text-sm"><?= Yii::$app->formatter->asCurrency($caixa->saldo_inicial) ?></dd>
                        </div>
                        <div class="flex justify-between py-1 items-center">
                            <dt class="text-gray-500 text-sm">Status:</dt>
                            <dd>
                                <span class="px-3 py-1 rounded-full text-xs font-extrabold <?= $caixa->status === 'ABERTO' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                    <?= $caixa->status ?>
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Resumo Financeiro e Contábil
                    </h3>
                    <dl class="space-y-2">
                        <div class="flex justify-between text-sm py-1 border-b border-gray-50">
                            <dt class="text-emerald-700 font-semibold flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span> Vendas / Recebimentos (Operacional):
                            </dt>
                            <dd class="font-black text-emerald-600"><?= Yii::$app->formatter->asCurrency($totalVendas) ?></dd>
                        </div>
                        <div class="flex justify-between text-sm py-1 border-b border-gray-50">
                            <dt class="text-sky-700 font-medium flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-sky-500 inline-block"></span> Aportes p/ Contas (Não Faturamento):
                            </dt>
                            <dd class="font-bold text-sky-600"><?= Yii::$app->formatter->asCurrency($totalAportes) ?></dd>
                        </div>
                        <div class="flex justify-between text-sm py-1 border-b border-gray-50">
                            <dt class="text-gray-500 font-medium">Total Geral Entradas (Caixa):</dt>
                            <dd class="font-bold text-gray-700"><?= Yii::$app->formatter->asCurrency($totalEntradas) ?></dd>
                        </div>
                        <div class="flex justify-between text-sm py-1 border-b border-gray-50">
                            <dt class="text-rose-700 font-medium flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-rose-500 inline-block"></span> Total Saídas (Despesas/Contas):
                            </dt>
                            <dd class="font-black text-rose-600"><?= Yii::$app->formatter->asCurrency($totalSaidas) ?></dd>
                        </div>
                        <div class="flex justify-between border-t-2 border-gray-100 pt-2.5 mt-2">
                            <dt class="text-gray-900 font-extrabold text-base">Saldo Final da Gaveta:</dt>
                            <dd class="font-black text-xl text-indigo-700"><?= Yii::$app->formatter->asCurrency($caixa->saldo_final ?? ($caixa->saldo_inicial + $totalEntradas - $totalSaidas)) ?></dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Movimentações -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-gray-50/80 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-base font-bold text-gray-800">Extrato de Movimentações</h3>
                <span class="text-xs text-gray-500"><?= count($movimentacoes) ?> lançamento(s)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Data/Hora</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Categoria</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Descrição</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100 text-sm">
                        <?php foreach ($movimentacoes as $mov): ?>
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
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100 text-sky-800">
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
                                        <div class="text-[11px] text-sky-600 font-medium">⚠️ Lançamento contábil de contrapartida (não afeta faturamento do dia)</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap text-right font-black font-mono <?= $mov->tipo === 'ENTRADA' ? 'text-emerald-600' : 'text-rose-600' ?>">
                                    <?= $mov->tipo === 'ENTRADA' ? '+' : '-' ?> <?= Yii::$app->formatter->asCurrency($mov->valor) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50/80">
                        <tr>
                            <td colspan="4" class="px-6 py-3.5 text-right font-bold text-gray-700">Saldo das Movimentações:</td>
                            <td class="px-6 py-3.5 text-right font-black text-base font-mono text-indigo-700">
                                <?= Yii::$app->formatter->asCurrency($totalEntradas - $totalSaidas) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>

</div>