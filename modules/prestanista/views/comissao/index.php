<?php
/** @var yii\web\View $this */
/** @var array $dadosComissao */
/** @var string $mesFiltro */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Comissões de Venda e Cobrança';
?>

<div class="space-y-6">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <span>💎</span>
                <span>Comissões de Venda & Cobrança</span>
            </h1>
            <p class="text-xs text-slate-400">
                Apuração das comissões devidas aos vendedores ambulantes e aos cobradores de rua.
            </p>
        </div>

        <form method="get" action="<?= Url::to(['/prestanista/comissao/index']) ?>" class="flex items-center gap-2">
            <input type="month" name="mes" value="<?= Html::encode($mesFiltro) ?>" onchange="this.form.submit()" class="h-10 px-3 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white font-bold">
        </form>
    </div>

    <!-- Tabela de Comissões por Colaborador -->
    <div class="bg-slate-950 border border-slate-800 rounded-3xl p-5 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-900/80 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="p-3">Colaborador</th>
                        <th class="p-3 text-right">Total Vendido</th>
                        <th class="p-3 text-right">Comissão Venda</th>
                        <th class="p-3 text-right">Total Arrecadado</th>
                        <th class="p-3 text-right">Comissão Cobrança</th>
                        <th class="p-3 text-right font-black text-amber-400">Total a Pagar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php foreach ($dadosComissao as $d): ?>
                        <tr class="hover:bg-slate-900/50 transition">
                            <td class="p-3 font-bold text-white">
                                <?= Html::encode($d['colaborador']->nome) ?>
                                <span class="text-[10px] text-slate-500 block"><?= Html::encode($d['colaborador']->funcao ?: 'Colaborador') ?></span>
                            </td>
                            <td class="p-3 text-right font-bold text-slate-300">
                                R$ <?= number_format($d['totalVendido'], 2, ',', '.') ?>
                            </td>
                            <td class="p-3 text-right text-blue-400 font-bold">
                                R$ <?= number_format($d['comissaoVenda'], 2, ',', '.') ?>
                            </td>
                            <td class="p-3 text-right font-bold text-slate-300">
                                R$ <?= number_format($d['totalCobrado'], 2, ',', '.') ?>
                            </td>
                            <td class="p-3 text-right text-emerald-400 font-bold">
                                R$ <?= number_format($d['comissaoCobranca'], 2, ',', '.') ?>
                            </td>
                            <td class="p-3 text-right font-black text-amber-400 text-sm whitespace-nowrap">
                                R$ <?= number_format($d['totalComissao'], 2, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
