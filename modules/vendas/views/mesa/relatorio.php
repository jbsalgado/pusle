<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Relatório & Analytics do Food Service';
?>

<div class="space-y-6">

    <!-- Header da Página -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <h1 class="text-2xl font-black text-gray-900 flex items-center gap-2">
                <span>📊</span>
                <span>Relatório & Analytics do Food Service</span>
            </h1>
            <p class="text-sm text-gray-500 mt-1">Métricas de desempenho, giro de mesas, ticket médio e análise de horários de pico.</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="<?= Url::to(['/vendas/mesa/index']) ?>" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-sm transition">
                ← Voltar ao Mapa de Mesas
            </a>
        </div>
    </div>

    <!-- Cards de Métricas Principais (KPIs) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- KPI 1: Ticket Médio -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Ticket Médio / Atendimento</span>
                <div class="text-2xl font-black text-emerald-600 mt-1">R$ <?= number_format($ticketMedio, 2, ',', '.') ?></div>
                <span class="text-[11px] text-gray-500 mt-1 block">Média gasta por comanda</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                💰
            </div>
        </div>

        <!-- KPI 2: Tempo Médio de Permanência -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Permanência Média</span>
                <div class="text-2xl font-black text-blue-600 mt-1"><?= $tempoMedioPermanencia ?> min</div>
                <span class="text-[11px] text-gray-500 mt-1 block">Tempo médio de consumo</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold">
                ⏱️
            </div>
        </div>

        <!-- KPI 3: Total de Atendimentos -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Atendimentos</span>
                <div class="text-2xl font-black text-purple-600 mt-1"><?= $totalComandas ?></div>
                <span class="text-[11px] text-gray-500 mt-1 block"><?= $mesasCount ?> Mesas | <?= $deliveryCount ?> Delivery</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl font-bold">
                🍽️
            </div>
        </div>

        <!-- KPI 4: Faturamento Total -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Faturamento Consolidado</span>
                <div class="text-2xl font-black text-gray-900 mt-1">R$ <?= number_format($faturamentoTotal, 2, ',', '.') ?></div>
                <span class="text-[11px] text-gray-500 mt-1 block">Total de comandas encerradas</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold">
                📈
            </div>
        </div>

    </div>

    <!-- Análise de Horário de Pico & Top Produtos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Bloco 1: Horários de Pico -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <span>🔥</span>
                    <span>Análise de Horário de Pico</span>
                </h3>
                <span class="text-xs text-gray-400">Distribuição por Faixa Horária</span>
            </div>

            <div class="space-y-3">
                <?php foreach ($faixasHorarias as $faixa => $qtd): ?>
                    <?php 
                        $perc = $totalComandas > 0 ? round(($qtd / $totalComandas) * 100) : 0;
                    ?>
                    <div>
                        <div class="flex justify-between text-xs font-bold text-gray-700 mb-1">
                            <span><?= Html::encode($faixa) ?></span>
                            <span><?= $qtd ?> pedidos (<?= $perc ?>%)</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2.5 rounded-full overflow-hidden">
                            <div class="bg-emerald-500 h-2.5 rounded-full transition-all duration-500" style="width: <?= $perc ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Bloco 2: Top 5 Produtos Mais Vendidos -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <span>🏆</span>
                    <span>Top 5 Produtos Mais Vendidos</span>
                </h3>
                <span class="text-xs text-gray-400">Ranking do Salão & Delivery</span>
            </div>

            <?php if (empty($topProdutos)): ?>
                <p class="text-xs text-gray-400 py-4 text-center">Nenhuma venda encerrada gravada no histórico ainda.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($topProdutos as $index => $prod): ?>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <div class="flex items-center space-x-3">
                                <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-800 text-xs font-black flex items-center justify-center">#<?= $index + 1 ?></span>
                                <span class="text-xs font-bold text-gray-900"><?= Html::encode($prod['nome']) ?></span>
                            </div>
                            <div class="text-right">
                                <div class="text-xs font-black text-emerald-600"><?= (float)$prod['qtd_total'] ?> un.</div>
                                <span class="text-[10px] text-gray-400">R$ <?= number_format((float)$prod['total_vendas'], 2, ',', '.') ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>
