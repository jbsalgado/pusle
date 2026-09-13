<?php
/** @var yii\web\View $this */
/** @var app\modules\vendas\models\HistoricoCobranca[] $pagamentos */
/** @var float $totalArrecadado */
/** @var string $dataFiltro */
/** @var int $cobrador_id */
/** @var app\modules\vendas\models\Colaborador[] $cobradores */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Acerto de Caixa com Cobradores';
?>

<div class="space-y-6">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <span>💰</span>
                <span>Acerto de Caixa Diário</span>
            </h1>
            <p class="text-xs text-slate-400">
                Conferência e prestação de contas dos valores arrecadados em dinheiro vivo e Pix pelos cobradores.
            </p>
        </div>
    </div>

    <!-- Filtros por Data e Cobrador -->
    <form method="get" action="<?= Url::to(['/prestanista/acerto/index']) ?>" class="bg-slate-950 border border-slate-800 p-4 rounded-2xl grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Data do Acerto</label>
            <input type="date" name="data" value="<?= Html::encode($dataFiltro) ?>" class="w-full h-11 px-3.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:border-amber-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Cobrador</label>
            <select name="cobrador_id" class="w-full h-11 px-3 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:border-amber-500 focus:outline-none">
                <option value="">Todos os Cobradores</option>
                <?php foreach ($cobradores as $c): ?>
                    <option value="<?= $c->id ?>" <?= $cobrador_id == $c->id ? 'selected' : '' ?>><?= Html::encode($c->nome) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex items-end">
            <button type="submit" class="w-full h-11 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs rounded-xl transition flex items-center justify-center gap-1.5">
                <span>🔍</span> Filtrar Acertos
            </button>
        </div>
    </form>

    <!-- Card de Total Arrecadado no Dia Selecionado -->
    <div class="bg-gradient-to-r from-emerald-950/60 to-slate-950 border border-emerald-500/30 p-6 rounded-3xl flex items-center justify-between shadow-lg">
        <div>
            <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider block">Total Arrecadado no Dia (<?= date('d/m/Y', strtotime($dataFiltro)) ?>)</span>
            <span class="text-3xl font-black text-white mt-1 block">R$ <?= number_format($totalArrecadado, 2, ',', '.') ?></span>
            <span class="text-xs text-slate-400 mt-0.5 block"><?= count($pagamentos) ?> cobrança(s) recebida(s)</span>
        </div>
        <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-2xl font-black">
            💵
        </div>
    </div>

    <!-- Lista Detalhada das Baixas Recebidas -->
    <div class="bg-slate-950 border border-slate-800 rounded-3xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-black text-white uppercase tracking-wider">Recibos do Dia</h2>
        </div>

        <?php if (empty($pagamentos)): ?>
            <p class="text-slate-500 text-xs text-center py-10">Nenhum pagamento registrado para esta data.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/80 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="p-3">Hora</th>
                            <th class="p-3">Cliente</th>
                            <th class="p-3">Cobrador</th>
                            <th class="p-3">Parcela</th>
                            <th class="p-3 text-right">Valor Pago</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php foreach ($pagamentos as $p): ?>
                            <tr class="hover:bg-slate-900/50 transition">
                                <td class="p-3 font-mono text-slate-400">
                                    <?= date('H:i', strtotime($p->data_acao)) ?>
                                </td>
                                <td class="p-3 font-bold text-white">
                                    <?= Html::encode($p->cliente->nome ?? 'Cliente #'.$p->cliente_id) ?>
                                </td>
                                <td class="p-3 text-slate-300">
                                    <?= Html::encode($p->cobrador->nome ?? 'Cobrador') ?>
                                </td>
                                <td class="p-3 text-slate-400">
                                    Parcela #<?= $p->parcela_id ?>
                                </td>
                                <td class="p-3 text-right font-black text-emerald-400 whitespace-nowrap">
                                    R$ <?= number_format($p->valor_recebido, 2, ',', '.') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>
