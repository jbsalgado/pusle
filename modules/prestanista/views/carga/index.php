<?php
/** @var yii\web\View $this */
/** @var app\modules\vendas\models\Produto[] $produtos */
/** @var app\modules\vendas\models\Colaborador[] $vendedores */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Carga do Carrinho (Estoque Consignado)';
?>

<div class="space-y-6">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <span>🛒</span>
                <span>Carga do Carrinho & Consignação</span>
            </h1>
            <p class="text-xs text-slate-400">
                Acompanhamento das mercadorias que saem com os ambulantes para venda de porta em porta.
            </p>
        </div>
    </div>

    <!-- Tabela de Produtos Disponíveis para Carregar no Carrinho -->
    <div class="bg-slate-950 border border-slate-800 rounded-3xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-black text-white uppercase tracking-wider">Produtos em Estoque para a Rua</h2>
            <span class="text-xs text-slate-400"><?= count($produtos) ?> produtos cadastrados</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-900/80 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="p-3">Mercadoria</th>
                        <th class="p-3 text-center">Estoque Atual</th>
                        <th class="p-3 text-right">Preço de Venda</th>
                        <th class="p-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php foreach ($produtos as $p): ?>
                        <tr class="hover:bg-slate-900/50 transition">
                            <td class="p-3 font-bold text-white">
                                <?= Html::encode($p->nome) ?>
                            </td>
                            <td class="p-3 text-center font-black <?= (float)$p->estoque > 0 ? 'text-emerald-400' : 'text-rose-400' ?>">
                                <?= (float)$p->estoque ?> un
                            </td>
                            <td class="p-3 text-right font-black text-amber-400 whitespace-nowrap">
                                R$ <?= number_format($p->preco, 2, ',', '.') ?>
                            </td>
                            <td class="p-3 text-right">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase <?= (float)$p->estoque > 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' ?>">
                                    <?= (float)$p->estoque > 0 ? 'Disponível' : 'Esgotado' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
