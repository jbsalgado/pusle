<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Relatórios - Contas a Pagar';
$this->params['breadcrumbs'][] = ['label' => 'Contas a Pagar', 'url' => ['/contas-pagar/conta-pagar/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 lg:px-8">

    <div class="max-w-7xl mx-auto">

        <!-- Header -->
        <div class="mb-6">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                    ['/contas-pagar/conta-pagar/index'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300']
                ) ?>
            </div>
        </div>

        <!-- Estatísticas Gerais -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            <!-- Total Pendente -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Pendente</p>
                        <p class="text-2xl font-bold text-gray-900"><?= Yii::$app->formatter->asCurrency($stats['total_pendente']) ?></p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Contas Vencidas -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Contas Vencidas</p>
                        <p class="text-2xl font-bold text-red-600"><?= Yii::$app->formatter->asCurrency($stats['total_vencidas']) ?></p>
                        <p class="text-xs text-gray-500 mt-1"><?= $stats['qtd_vencidas'] ?> conta(s)</p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Próximos 7 Dias -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Próximos 7 Dias</p>
                        <p class="text-2xl font-bold text-blue-600"><?= Yii::$app->formatter->asCurrency($stats['proximos_7_dias']) ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pago Este Mês -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Pago Este Mês</p>
                        <p class="text-2xl font-bold text-green-600"><?= Yii::$app->formatter->asCurrency($stats['total_pago_mes']) ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

        </div>

        <!-- Cards de Relatórios -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Contas a Vencer -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6 text-white">
                    <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-xl font-bold">Contas a Vencer</h3>
                    <p class="text-sm text-blue-100 mt-2">Visualize contas com vencimento futuro</p>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <?= Html::a('Próximos 7 dias', ['a-vencer', 'dias' => 7], ['class' => 'block w-full text-center px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition']) ?>
                        <?= Html::a('Próximos 15 dias', ['a-vencer', 'dias' => 15], ['class' => 'block w-full text-center px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition']) ?>
                        <?= Html::a('Próximos 30 dias', ['a-vencer', 'dias' => 30], ['class' => 'block w-full text-center px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition']) ?>
                    </div>
                </div>
            </div>

            <!-- Contas Vencidas -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                <div class="bg-gradient-to-r from-red-500 to-red-600 p-6 text-white">
                    <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 class="text-xl font-bold">Contas Vencidas</h3>
                    <p class="text-sm text-red-100 mt-2">Contas com pagamento atrasado</p>
                </div>
                <div class="p-6">
                    <?= Html::a(
                        'Ver Contas Vencidas',
                        ['vencidas'],
                        ['class' => 'block w-full text-center px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition']
                    ) ?>
                    <p class="text-center text-sm text-gray-600 mt-4">
                        <?= $stats['qtd_vencidas'] ?> conta(s) vencida(s)
                    </p>
                </div>
            </div>

            <!-- Por Fornecedor -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-6 text-white">
                    <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="text-xl font-bold">Por Fornecedor</h3>
                    <p class="text-sm text-purple-100 mt-2">Análise por fornecedor</p>
                </div>
                <div class="p-6">
                    <?= Html::a(
                        'Ver Relatório',
                        ['por-fornecedor'],
                        ['class' => 'block w-full text-center px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition']
                    ) ?>
                </div>
            </div>

            <!-- Fluxo de Pagamentos -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                <div class="bg-gradient-to-r from-green-500 to-green-600 p-6 text-white">
                    <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <h3 class="text-xl font-bold">Fluxo de Pagamentos</h3>
                    <p class="text-sm text-green-100 mt-2">Análise mensal de pagamentos</p>
                </div>
                <div class="p-6">
                    <?= Html::a(
                        'Ver Fluxo',
                        ['fluxo'],
                        ['class' => 'block w-full text-center px-4 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition']
                    ) ?>
                </div>
            </div>

        </div>

    </div>

</div>