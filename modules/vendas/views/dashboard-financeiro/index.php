<?php

/**
 * View: Dashboard Financeiro Consolidado
 * @var yii\web\View $this
 * @var array $kpis
 * @var array $charts
 * @var array $alertas
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Dashboard Financeiro';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Dashboard Financeiro 💰</h1>
            <p class="text-gray-500">Visão consolidada de caixa, contas a pagar e receber.</p>
        </div>
        <div class="flex space-x-2">
            <a href="<?= Url::to(['/caixa/caixa/index']) ?>" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition font-medium">Caixa</a>
            <a href="<?= Url::to(['/contas-pagar/conta-pagar/index']) ?>" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition font-medium">Contas a Pagar</a>
            <a href="<?= Url::to(['/contas-pagar/relatorio/index']) ?>" class="px-4 py-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition font-medium">Relatórios</a>
        </div>
    </div>

    <!-- Alertas -->
    <?php if (!empty($alertas)): ?>
        <div class="space-y-3">
            <?php foreach ($alertas as $alerta): ?>
                <div class="bg-<?= $alerta['tipo'] == 'danger' ? 'red' : 'yellow' ?>-50 border-l-4 border-<?= $alerta['tipo'] == 'danger' ? 'red' : 'yellow' ?>-500 p-4 rounded-r-lg">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-<?= $alerta['tipo'] == 'danger' ? 'red' : 'yellow' ?>-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-<?= $alerta['tipo'] == 'danger' ? 'red' : 'yellow' ?>-800"><?= $alerta['titulo'] ?></p>
                                <p class="text-xs text-<?= $alerta['tipo'] == 'danger' ? 'red' : 'yellow' ?>-700 mt-1"><?= $alerta['mensagem'] ?></p>
                            </div>
                        </div>
                        <?php if ($alerta['acao']): ?>
                            <a href="<?= Url::to($alerta['acao']) ?>" class="px-3 py-1 bg-<?= $alerta['tipo'] == 'danger' ? 'red' : 'yellow' ?>-600 text-white rounded-md text-sm hover:bg-<?= $alerta['tipo'] == 'danger' ? 'red' : 'yellow' ?>-700 transition">
                                <?= $alerta['acao_texto'] ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- KPIs Row 1: Caixa e Contas a Pagar -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Saldo do Caixa -->
        <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 <?= $kpis['caixa_aberto'] ? 'border-blue-500' : 'border-gray-400' ?>">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">Saldo do Caixa</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">R$ <?= number_format($kpis['saldo_caixa'], 2, ',', '.') ?></p>
                    <p class="text-xs text-gray-500 mt-1">
                        <?= $kpis['caixa_aberto'] ? '🟢 Aberto' : '🔴 Fechado' ?>
                    </p>
                </div>
                <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
        </div>

        <!-- Contas a Pagar Pendentes -->
        <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-orange-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">A Pagar (Pendente)</p>
                    <p class="text-2xl font-bold text-orange-600 mt-1">R$ <?= number_format($kpis['contas_pagar_pendente'], 2, ',', '.') ?></p>
                </div>
                <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                </svg>
            </div>
        </div>

        <!-- Contas Vencidas -->
        <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-red-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">Contas Vencidas</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">R$ <?= number_format($kpis['contas_pagar_vencidas'], 2, ',', '.') ?></p>
                    <p class="text-xs text-red-600 mt-1"><?= $kpis['contas_pagar_vencidas_qtd'] ?> conta(s)</p>
                </div>
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- Próximos 7 Dias -->
        <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-yellow-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">Próximos 7 Dias</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1">R$ <?= number_format($kpis['contas_pagar_proximos_7dias'], 2, ',', '.') ?></p>
                </div>
                <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- KPIs Row 2: Receitas e Vendas -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-emerald-500">
            <p class="text-xs font-medium text-gray-500 uppercase">Receita Bruta</p>
            <p class="text-xl font-bold text-gray-900 mt-1">R$ <?= number_format($kpis['receita_total'], 2, ',', '.') ?></p>
        </div>
        <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-green-500">
            <p class="text-xs font-medium text-gray-500 uppercase">A Receber (Parcelas)</p>
            <p class="text-xl font-bold text-green-600 mt-1">R$ <?= number_format($kpis['contas_receber_pendente'], 2, ',', '.') ?></p>
        </div>
        <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-blue-500">
            <p class="text-xs font-medium text-gray-500 uppercase">Recebido (Asaas)</p>
            <p class="text-xl font-bold text-gray-900 mt-1">R$ <?= number_format($kpis['valor_recebido_asaas'], 2, ',', '.') ?></p>
        </div>
        <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-indigo-500">
            <p class="text-xs font-medium text-gray-500 uppercase">Taxas PULSE (SaaS)</p>
            <p class="text-xl font-bold text-indigo-600 mt-1">R$ <?= number_format($kpis['taxas_plataforma'], 2, ',', '.') ?></p>
        </div>
        <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-red-500">
            <p class="text-xs font-medium text-gray-500 uppercase">Inadimplência</p>
            <p class="text-xl font-bold text-red-600 mt-1">R$ <?= number_format($kpis['inadimplencia'], 2, ',', '.') ?></p>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Fluxo de Caixa (Novo) -->
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-semibold mb-4">Fluxo de Caixa (Entradas x Saídas)</h3>
            <?php if (empty($charts['fluxo_caixa'])): ?>
                <p class="text-gray-400 text-center py-10 italic">Nenhum dado disponível.</p>
            <?php else: ?>
                <canvas id="chartFluxoCaixa" height="200"></canvas>
            <?php endif; ?>
        </div>

        <!-- Receita Mensal -->
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-semibold mb-4">Evolução da Receita (12 meses)</h3>
            <canvas id="chartReceitaMensal" height="200"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Contas a Pagar por Status (Novo) -->
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-semibold mb-4">Contas a Pagar por Status</h3>
            <?php if (empty($charts['contas_pagar_status'])): ?>
                <p class="text-gray-400 text-center py-10 italic">Nenhuma conta cadastrada.</p>
            <?php else: ?>
                <canvas id="chartContasPagarStatus" height="200"></canvas>
            <?php endif; ?>
        </div>

        <!-- Status Comissões -->
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-semibold mb-4">Status de Comissões</h3>
            <canvas id="chartComissoesStatus" height="200"></canvas>
        </div>
    </div>

    <!-- Novos Gráficos: Fluxo Projetado -->
    <div class="grid grid-cols-1 gap-6">
        <!-- Fluxo de Caixa Projetado (30 dias) -->
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-semibold mb-4">📊 Fluxo de Caixa Projetado (30 dias)</h3>
            <?php if (empty($charts['fluxo_projetado'])): ?>
                <p class="text-gray-400 text-center py-10 italic">Nenhum dado disponível.</p>
            <?php else: ?>
                <canvas id="chartFluxoProjetado" height="100"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabelas de Contas -->
    <?= $this->render('_tabelas', [
        'contasPagar' => $contasPagar,
        'parcelasReceber' => $parcelasReceber,
    ]) ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Fluxo de Caixa (Entradas x Saídas)
        <?php if (!empty($charts['fluxo_caixa'])): ?>
            const dataFluxo = <?= json_encode($charts['fluxo_caixa']) ?>;
            const mesesFluxo = Object.keys(dataFluxo).reverse();
            const entradas = mesesFluxo.map(mes => parseFloat(dataFluxo[mes].entradas) || 0);
            const saidas = mesesFluxo.map(mes => parseFloat(dataFluxo[mes].saidas) || 0);

            new Chart(document.getElementById('chartFluxoCaixa'), {
                type: 'bar',
                data: {
                    labels: mesesFluxo,
                    datasets: [{
                            label: 'Entradas (R$)',
                            data: entradas,
                            backgroundColor: '#10b981',
                        },
                        {
                            label: 'Saídas (R$)',
                            data: saidas,
                            backgroundColor: '#ef4444',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    }
                }
            });
        <?php endif; ?>

        // 2. Receita Mensal
        const dataReceita = <?= json_encode($charts['receita_mensal']) ?>;
        new Chart(document.getElementById('chartReceitaMensal'), {
            type: 'line',
            data: {
                labels: dataReceita.map(d => d.mes),
                datasets: [{
                    label: 'Receita (R$)',
                    data: dataReceita.map(d => parseFloat(d.total)),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // 3. Contas a Pagar por Status
        <?php if (!empty($charts['contas_pagar_status'])): ?>
            const dataContasPagar = <?= json_encode($charts['contas_pagar_status']) ?>;
            new Chart(document.getElementById('chartContasPagarStatus'), {
                type: 'doughnut',
                data: {
                    labels: dataContasPagar.map(d => d.status),
                    datasets: [{
                        data: dataContasPagar.map(d => parseFloat(d.total)),
                        backgroundColor: ['#f59e0b', '#10b981', '#ef4444', '#6b7280']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        <?php endif; ?>

        // 4. Comissões Status
        const dataComissoes = <?= json_encode($charts['comissoes_status']) ?>;
        new Chart(document.getElementById('chartComissoesStatus'), {
            type: 'doughnut',
            data: {
                labels: dataComissoes.map(d => d.status),
                datasets: [{
                    data: dataComissoes.map(d => parseFloat(d.total)),
                    backgroundColor: ['#ef4444', '#10b981', '#f59e0b']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // 5. Fluxo de Caixa Projetado
        <?php if (!empty($charts['fluxo_projetado'])): ?>
            const dataProjetado = <?= json_encode($charts['fluxo_projetado']) ?>;
            new Chart(document.getElementById('chartFluxoProjetado'), {
                type: 'line',
                data: {
                    labels: dataProjetado.map(d => d.data),
                    datasets: [{
                            label: 'Saldo Projetado (R$)',
                            data: dataProjetado.map(d => parseFloat(d.saldo)),
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Entradas (R$)',
                            data: dataProjetado.map(d => parseFloat(d.entradas)),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.3,
                            fill: false
                        },
                        {
                            label: 'Saídas (R$)',
                            data: dataProjetado.map(d => parseFloat(d.saidas)),
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.3,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false
                        }
                    }
                }
            });
        <?php endif; ?>

        // 6. Despesas por Categoria
        <?php if (!empty($charts['despesas_categoria'])): ?>
            const dataDespesas = <?= json_encode($charts['despesas_categoria']) ?>;
            new Chart(document.getElementById('chartDespesasCategoria'), {
                type: 'doughnut',
                data: {
                    labels: dataDespesas.map(d => d.categoria || 'Sem categoria'),
                    datasets: [{
                        data: dataDespesas.map(d => parseFloat(d.total)),
                        backgroundColor: [
                            '#ef4444',
                            '#f59e0b',
                            '#10b981',
                            '#3b82f6',
                            '#6366f1',
                            '#8b5cf6',
                            '#ec4899',
                            '#14b8a6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        <?php endif; ?>
    });
</script>