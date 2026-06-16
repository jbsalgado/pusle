<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Relatórios - Caixa';
$this->params['breadcrumbs'][] = ['label' => 'Caixa', 'url' => ['/caixa/caixa/index']];
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
                    ['/caixa/caixa/index'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300']
                ) ?>
            </div>
        </div>

        <!-- Status do Caixa -->
        <?php if ($caixaAberto): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-green-800">
                            Caixa Aberto - Saldo Atual: <strong><?= Yii::$app->formatter->asCurrency($stats['saldo_atual']) ?></strong>
                        </p>
                        <p class="text-xs text-green-700 mt-1">
                            Aberto em: <?= Yii::$app->formatter->asDatetime($caixaAberto->data_abertura) ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-sm font-medium text-red-800">Nenhum caixa aberto no momento</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Estatísticas Gerais -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

            <!-- Entradas Hoje -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Entradas Hoje</p>
                        <p class="text-2xl font-bold text-green-600"><?= Yii::$app->formatter->asCurrency($stats['entradas_hoje']) ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Saídas Hoje -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Saídas Hoje</p>
                        <p class="text-2xl font-bold text-red-600"><?= Yii::$app->formatter->asCurrency($stats['saidas_hoje']) ?></p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Mês -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Este Mês</p>
                        <p class="text-2xl font-bold text-blue-600"><?= Yii::$app->formatter->asCurrency($stats['total_mes']) ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>

        </div>

        <!-- Cards de Relatórios -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Fechamento de Caixa -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-6 text-white">
                    <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-xl font-bold">Fechamento de Caixa</h3>
                    <p class="text-sm text-purple-100 mt-2">Relatório completo de fechamento</p>
                </div>
                <div class="p-6">
                    <?= Html::a(
                        'Ver Último Fechamento',
                        ['fechamento'],
                        ['class' => 'block w-full text-center px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition']
                    ) ?>
                </div>
            </div>

            <!-- Movimentações -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6 text-white">
                    <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <h3 class="text-xl font-bold">Movimentações</h3>
                    <p class="text-sm text-blue-100 mt-2">Entradas e saídas por período</p>
                </div>
                <div class="p-6">
                    <?= Html::a(
                        'Ver Movimentações',
                        ['movimentacoes'],
                        ['class' => 'block w-full text-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition']
                    ) ?>
                </div>
            </div>

            <!-- Por Categoria -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                <div class="bg-gradient-to-r from-green-500 to-green-600 p-6 text-white">
                    <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <h3 class="text-xl font-bold">Por Categoria</h3>
                    <p class="text-sm text-green-100 mt-2">Análise por categoria de movimentação</p>
                </div>
                <div class="p-6">
                    <?= Html::a(
                        'Ver Análise',
                        ['por-categoria'],
                        ['class' => 'block w-full text-center px-4 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition']
                    ) ?>
                </div>
            </div>

        </div>

    </div>

</div>