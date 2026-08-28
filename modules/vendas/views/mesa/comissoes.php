<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Apuração de Comissões — Garçons & Motoboys';
?>

<div class="space-y-6">

    <!-- Header da Página -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <h1 class="text-2xl font-black text-gray-900 flex items-center gap-2">
                <span>💰</span>
                <span>Apuração de Comissões — Garçons & Motoboys</span>
            </h1>
            <p class="text-sm text-gray-500 mt-1">Fechamento de comissões de atendimento do salão e acerto de taxas de entrega dos motoboys.</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="<?= Url::to(['/vendas/mesa/index']) ?>" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-sm transition">
                ← Voltar ao Mapa de Mesas
            </a>
        </div>
    </div>

    <!-- Seção 1: Comissões dos Garçons (Taxa de Serviço 10%) -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200/80 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                <span>🍽️</span>
                <span>Comissões dos Garçons (Salão)</span>
            </h3>
            <span class="text-xs text-gray-400">Calculado sobre a Taxa de Serviço (10%)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-100 text-gray-600 uppercase font-bold text-[10px]">
                    <tr>
                        <th class="p-3">Garçom / Atendente</th>
                        <th class="p-3 text-center">Mesas Atendidas</th>
                        <th class="p-3 text-right">Consumo Total (R$)</th>
                        <th class="p-3 text-right text-emerald-700">Comissão a Pagar (R$)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($garconsData)): ?>
                        <tr>
                            <td colspan="4" class="p-4 text-center text-gray-400">Nenhum atendimento de mesa encerrado ainda.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($garconsData as $g): ?>
                            <tr class="hover:bg-gray-50 font-semibold text-gray-900">
                                <td class="p-3 flex items-center gap-2">
                                    <span>👤</span>
                                    <span><?= Html::encode($g['nome']) ?></span>
                                </td>
                                <td class="p-3 text-center font-bold text-gray-700"><?= $g['qtd_atendimentos'] ?></td>
                                <td class="p-3 text-right text-gray-600">R$ <?= number_format($g['total_consumo'], 2, ',', '.') ?></td>
                                <td class="p-3 text-right font-black text-emerald-600 text-sm">R$ <?= number_format($g['total_comissao'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Seção 2: Taxas de Entrega dos Motoboys (Acerto de Corridas) -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200/80 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                <span>🛵</span>
                <span>Acerto de Corridas & Taxas de Motoboys (Delivery)</span>
            </h3>
            <span class="text-xs text-gray-400">Repasse de Taxas de Entrega Coletadas</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-100 text-gray-600 uppercase font-bold text-[10px]">
                    <tr>
                        <th class="p-3">Motoboy / Entregador</th>
                        <th class="p-3 text-center">Corridas Concluídas</th>
                        <th class="p-3 text-right">Valor em Pedidos (R$)</th>
                        <th class="p-3 text-right text-teal-700">Total Taxas a Pagar (R$)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($motoboysData)): ?>
                        <tr>
                            <td colspan="4" class="p-4 text-center text-gray-400">Nenhuma entrega de motoboy concluída no histórico ainda.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($motoboysData as $m): ?>
                            <tr class="hover:bg-gray-50 font-semibold text-gray-900">
                                <td class="p-3 flex items-center gap-2">
                                    <span>🛵</span>
                                    <span><?= Html::encode($m['nome']) ?></span>
                                </td>
                                <td class="p-3 text-center font-bold text-gray-700"><?= $m['qtd_corridas'] ?></td>
                                <td class="p-3 text-right text-gray-600">R$ <?= number_format($m['total_pedidos_valor'], 2, ',', '.') ?></td>
                                <td class="p-3 text-right font-black text-teal-600 text-sm">R$ <?= number_format($m['total_taxas_entrega'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
