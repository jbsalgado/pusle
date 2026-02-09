<?php

/**
 * View: Dashboard Financeiro
 * @var yii\web\View $this
 * @var array $kpis
 * @var array $charts
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Painel Financeiro Avançado';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Métricas Financeiras 📊</h1>
            <p class="text-gray-500">Gestão de receitas, comissões e splits.</p>
        </div>
        <div class="flex space-x-2">
            <a href="<?= Url::to(['/vendas/dashboard/index']) ?>" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition font-medium">Dashboard Geral</a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-emerald-500">
            <p class="text-xs font-medium text-gray-500 uppercase">Receita Bruta</p>
            <p class="text-xl font-bold text-gray-900 mt-1">R$ <?= number_format($kpis['receita_total'], 2, ',', '.') ?></p>
        </div>
        <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-blue-500">
            <p class="text-xs font-medium text-gray-500 uppercase">Recebido (Asaas)</p>
            <p class="text-xl font-bold text-gray-900 mt-1">R$ <?= number_format($kpis['valor_recebido_asaas'], 2, ',', '.') ?></p>
        </div>
        <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-indigo-500">
            <p class="text-xs font-medium text-gray-500 uppercase">Taxas PULSE (SaaS)</p>
            <p class="text-xl font-bold text-indigo-600 mt-1">R$ <?= number_format($kpis['taxas_plataforma'], 2, ',', '.') ?></p>
        </div>
        <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-orange-500">
            <p class="text-xs font-medium text-gray-500 uppercase">Comissões Outras</p>
            <p class="text-xl font-bold text-orange-600 mt-1">R$ <?= number_format($kpis['comissoes_pendentes'], 2, ',', '.') ?></p>
        </div>
        <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-red-500">
            <p class="text-xs font-medium text-gray-500 uppercase">Inadimplência</p>
            <p class="text-xl font-bold text-red-600 mt-1">R$ <?= number_format($kpis['inadimplencia'], 2, ',', '.') ?></p>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Receita Mensal -->
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-semibold mb-4">Evolução da Receita (12 meses)</h3>
            <canvas id="chartReceitaMensal" height="200"></canvas>
        </div>

        <!-- Taxas Pulse -->
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-semibold mb-4">Investimento Pulse (SaaS)</h3>
            <?php if (empty($charts['taxas_plataforma'])): ?>
                <p class="text-gray-400 text-center py-10 italic">Nenhuma taxa registrada nos últimos meses.</p>
            <?php else: ?>
                <canvas id="chartTaxasPulse" height="200"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Status Comissões -->
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-semibold mb-4">Status de Comissões</h3>
            <canvas id="chartComissoesStatus" height="200"></canvas>
        </div>

        <!-- Informativo SaaS -->
        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 p-6 rounded-lg shadow-sm text-white flex flex-col justify-center">
            <h3 class="text-xl font-bold mb-2">Sobre o Modelo SaaS Pulse 💳</h3>
            <p class="opacity-90">
                O Pulse opera com um modelo de Split Automático da plataforma. Uma pequena taxa de manutenção (padrão 0.5%) é retida automaticamente para garantir a evolução contínua da sua ferramenta.
            </p>
            <ul class="mt-4 space-y-2 text-sm opacity-80">
                <li>• Sem mensalidades fixas abusivas</li>
                <li>• Pague apenas pelo que vender</li>
                <li>• Split direto na fonte (Asaas/MercadoPago)</li>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Receita Mensal
        const dataReceita = <?= json_encode($charts['receita_receita_mensal'] ?? $charts['receita_mensal']) ?>;
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

        // 2. Comissões Status
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
                responsive: true
            }
        });

        // 3. Taxas Pulse
        const dataTaxas = <?= json_encode($charts['taxas_plataforma'] ?? []) ?>;
        if (dataTaxas.length > 0) {
            new Chart(document.getElementById('chartTaxasPulse'), {
                type: 'bar',
                data: {
                    labels: dataTaxas.map(d => d.mes),
                    datasets: [{
                        label: 'Taxas Pulse (R$)',
                        data: dataTaxas.map(d => parseFloat(d.total)),
                        backgroundColor: '#6366f1'
                    }]
                },
                options: {
                    responsive: true
                }
            });
        }
    });
</script>