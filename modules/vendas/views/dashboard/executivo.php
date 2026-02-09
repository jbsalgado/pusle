<?php

/**
 * View: Dashboard Executivo (BI)
 * @var yii\web\View $this
 * @var array $kpis
 * @var array $indicadores
 * @var array $vendasMes
 * @var array $meta
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;

$this->title = 'BI Executivo - Pulse';

// Cores para os indicadores baseadas na polaridade e faixas
function getColorByPerformance($valor, $atributos, $polaridade)
{
    if (!$atributos) return '#3b82f6'; // Blue default

    $v = (float)$valor;
    $sInf = (float)($atributos['faixa_satisfatoria_inferior'] ?? 0);
    $sSup = (float)($atributos['faixa_satisfatoria_superior'] ?? 0);
    $aInf = (float)($atributos['faixa_alerta_inferior'] ?? 0);
    $aSup = (float)($atributos['faixa_alerta_superior'] ?? 0);

    if ($polaridade === 'QUANTO_MAIOR_MELHOR') {
        if ($v >= $sInf) return '#10b981'; // Emerald
        if ($v >= $aInf) return '#f59e0b'; // Amber
        return '#ef4444'; // Red
    }

    if ($polaridade === 'QUANTO_MENOR_MELHOR') {
        if ($v <= $sSup) return '#10b981';
        if ($v <= $aSup) return '#f59e0b';
        return '#ef4444';
    }

    return '#3b82f6';
}
?>

<div class="min-h-screen bg-[#f8fafc] p-4 lg:p-8 space-y-8">
    <!-- Header Executivo -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/70 backdrop-blur-md p-6 rounded-2xl border border-white shadow-sm">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Executive Dashboard <span class="text-indigo-600">BI</span></h1>
            <p class="text-slate-500 font-medium">Análise consolidada de performance e metas.</p>
        </div>
        <div class="flex items-center gap-2 bg-indigo-50 px-4 py-2 rounded-xl text-indigo-700 font-bold border border-indigo-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <?= date('M / Y') ?>
        </div>
    </div>

    <!-- Seção de Metas (WOW Factor) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Gauge de Meta Global -->
        <div class="lg:col-span-1 bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center">
            <h3 class="text-lg font-bold text-slate-800 self-start mb-2">Meta de Faturamento</h3>
            <div id="chartMetaGlobal" class="w-full h-[300px]"></div>
            <div class="text-center mt-[-40px]">
                <p class="text-sm font-medium text-slate-500">Realizado: <span class="text-slate-900 font-bold">R$ <?= number_format($meta['realizado'], 2, ',', '.') ?></span></p>
                <p class="text-sm font-medium text-slate-500">Meta: <span class="text-indigo-600 font-bold">R$ <?= number_format($meta['faturamento'], 2, ',', '.') ?></span></p>
            </div>
        </div>

        <!-- Trend de Faturamento (ECharts) -->
        <div class="lg:col-span-2 bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
            <h3 class="text-lg font-bold text-slate-800 mb-6">Tendência de Receita (6 meses)</h3>
            <div id="chartVendasTrend" class="w-full h-[320px]"></div>
        </div>
    </div>

    <!-- Indicadores de Negócio (BI Cards) -->
    <div>
        <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
            <span class="w-2 h-8 bg-indigo-600 rounded-full"></span>
            Performance de Negócio
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($indicadores as $id => $data): ?>
                <?php
                $valor = $data['ultimoValor']['valor'] ?? 0;
                $unidade = $data['definicao']['unidadeMedida']['sigla_unidade'] ?? '';
                $cor = getColorByPerformance($valor, $data['atributos_qualidade'], $data['definicao']['polaridade']);
                $historico = $data['historico'] ?? [];
                $sparklineId = "sparkline-" . $id;
                ?>
                <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 hover:shadow-md transition-all group overflow-hidden relative">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider"><?= Html::encode($data['definicao']['nome_indicador']) ?></p>
                            <h4 class="text-2xl font-black text-slate-800 mt-1">
                                <?= number_format($valor, 1, ',', '.') ?> <span class="text-xs font-normal text-slate-400"><?= Html::encode($unidade) ?></span>
                            </h4>
                        </div>
                        <div class="p-2 rounded-xl bg-slate-50 text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    </div>

                    <!-- Sparkline para mini tendência -->
                    <div id="<?= $sparklineId ?>" class="w-full h-16 opacity-70 group-hover:opacity-100 transition-opacity"></div>

                    <!-- Meta Progress Bar -->
                    <?php if ($data['meta']): ?>
                        <div class="mt-4 pt-4 border-t border-slate-50">
                            <div class="flex justify-between text-[10px] font-bold text-slate-500 mb-1">
                                <span>PROGRESSO META</span>
                                <span><?= round(($valor / $data['meta']['valor_meta_referencia_1']) * 100, 1) ?>%</span>
                            </div>
                            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-1000" style="width: <?= min(100, ($valor / $data['meta']['valor_meta_referencia_1']) * 100) ?>%; background-color: <?= $cor ?>"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tabela de KPIs Financeiros -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-50 flex justify-between items-center bg-slate-50/30">
            <h3 class="text-lg font-bold text-slate-800">Métricas Consolidadas (Mês)</h3>
            <button class="text-indigo-600 font-bold text-sm hover:underline">Ver Detalhes</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-100">
            <div class="p-8 text-center">
                <p class="text-slate-400 text-sm font-bold uppercase tracking-widest mb-2">Vendas Totais</p>
                <p class="text-3xl font-black text-slate-800"><?= $kpis['qtd_vendas_mes'] ?></p>
            </div>
            <div class="p-8 text-center">
                <p class="text-slate-400 text-sm font-bold uppercase tracking-widest mb-2">Ticket Médio</p>
                <p class="text-3xl font-black text-slate-800">R$ <?= number_format($kpis['ticket_medio'], 2, ',', '.') ?></p>
            </div>
            <div class="p-8 text-center">
                <p class="text-slate-400 text-sm font-bold uppercase tracking-widest mb-2">Pendente Recebimento</p>
                <p class="text-3xl font-black text-rose-600">R$ <?= number_format($kpis['valor_pendente'], 2, ',', '.') ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Scripts do ECharts -->
<script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Meta Global (Gauge)
        const chartMeta = echarts.init(document.getElementById('chartMetaGlobal'));
        const metaPercent = <?= $meta['percentual'] ?>;

        chartMeta.setOption({
            series: [{
                type: 'gauge',
                startAngle: 180,
                endAngle: 0,
                min: 0,
                max: 100,
                splitNumber: 8,
                axisLine: {
                    lineStyle: {
                        width: 6,
                        color: [
                            [0.3, '#ef4444'],
                            [0.7, '#f59e0b'],
                            [1, '#10b981']
                        ]
                    }
                },
                pointer: {
                    icon: 'path://M12.8,0.7l12,40.1H0.7L12.7,0.7z',
                    length: '12%',
                    width: 20,
                    offsetCenter: [0, '-60%'],
                    itemStyle: {
                        color: 'auto'
                    }
                },
                axisTick: {
                    show: false
                },
                splitLine: {
                    show: false
                },
                axisLabel: {
                    show: false
                },
                detail: {
                    valueAnimation: true,
                    formatter: '{value}%',
                    color: 'inherit',
                    fontSize: 34,
                    fontWeight: 'bold',
                    offsetCenter: [0, '-20%']
                },
                data: [{
                    value: metaPercent
                }]
            }]
        });

        // 2. Tendência de Vendas (Bar + Line)
        const chartTrend = echarts.init(document.getElementById('chartVendasTrend'));
        const dataVendas = <?= Json::encode($vendasMes) ?>;

        chartTrend.setOption({
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: [{
                type: 'category',
                data: dataVendas.map(i => i.mes),
                axisTick: {
                    alignWithLabel: true
                },
                axisLine: {
                    lineStyle: {
                        color: '#cbd5e1'
                    }
                }
            }],
            yAxis: [{
                type: 'value',
                axisLabel: {
                    formatter: (v) => 'R$ ' + (v / 1000) + 'k'
                },
                splitLine: {
                    lineStyle: {
                        type: 'dashed',
                        color: '#f1f5f9'
                    }
                }
            }],
            series: [{
                    name: 'Faturamento',
                    type: 'bar',
                    barWidth: '40%',
                    data: dataVendas.map(i => parseFloat(i.valor_total)),
                    itemStyle: {
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
                                offset: 0,
                                color: '#6366f1'
                            },
                            {
                                offset: 1,
                                color: '#a855f7'
                            }
                        ]),
                        borderRadius: [8, 8, 0, 0]
                    }
                },
                {
                    name: 'Quantidade',
                    type: 'line',
                    smooth: true,
                    data: dataVendas.map(i => parseFloat(i.valor_total) * 0.9), // Mock de linha de tendência
                    lineStyle: {
                        color: '#10b981',
                        width: 3
                    },
                    symbol: 'circle',
                    symbolSize: 8,
                    itemStyle: {
                        color: '#10b981'
                    }
                }
            ]
        });

        // 3. Sparklines para os indicadores
        <?php foreach ($indicadores as $id => $data): ?>
            <?php
            $historicoValores = array_map(function ($h) {
                return (float)$h['valor'];
            }, $data['historico'] ?? []);
            $corSpark = getColorByPerformance(end($historicoValores) ?: 0, $data['atributos_qualidade'], $data['definicao']['polaridade']);
            ?>
                (function() {
                    const chart = echarts.init(document.getElementById('sparkline-<?= $id ?>'));
                    chart.setOption({
                        grid: {
                            left: 0,
                            right: 0,
                            top: 10,
                            bottom: 0
                        },
                        xAxis: {
                            type: 'category',
                            show: false
                        },
                        yAxis: {
                            type: 'value',
                            show: false,
                            min: 'dataMin'
                        },
                        series: [{
                            data: <?= Json::encode($historicoValores) ?>,
                            type: 'line',
                            smooth: true,
                            symbol: 'none',
                            lineStyle: {
                                color: '<?= $corSpark ?>',
                                width: 2
                            },
                            areaStyle: {
                                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
                                        offset: 0,
                                        color: '<?= $corSpark ?>44'
                                    },
                                    {
                                        offset: 1,
                                        color: '<?= $corSpark ?>00'
                                    }
                                ])
                            }
                        }]
                    });
                    window.addEventListener('resize', () => chart.resize());
                })();
        <?php endforeach; ?>

        window.addEventListener('resize', () => {
            chartMeta.resize();
            chartTrend.resize();
        });
    });
</script>

<style>
    /* Glassmorphism subtle effect */
    .backdrop-blur-md {
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }
</style>