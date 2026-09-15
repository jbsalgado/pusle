<?php

use yii\helpers\Html;
use app\modules\marketplace\models\MarketplaceConfig;

/* @var $this yii\web\View */
/* @var $configs app\modules\marketplace\models\MarketplaceConfig[] */

$this->title = 'Conexões de Marketplaces';

$badgeCores = [
    'MERCADO_LIVRE' => ['badge' => 'bg-yellow-100 text-yellow-900 border-yellow-300', 'border' => 'hover:border-yellow-400'],
    'SHOPEE' => ['badge' => 'bg-orange-100 text-orange-900 border-orange-300', 'border' => 'hover:border-orange-400'],
    'MAGAZINE_LUIZA' => ['badge' => 'bg-blue-100 text-blue-900 border-blue-300', 'border' => 'hover:border-blue-400'],
    'TEMU' => ['badge' => 'bg-amber-100 text-amber-900 border-amber-300', 'border' => 'hover:border-amber-400'],
    'IFOOD' => ['badge' => 'bg-red-100 text-red-900 border-red-300', 'border' => 'hover:border-red-400'],
    'AMAZON' => ['badge' => 'bg-slate-100 text-slate-900 border-slate-300', 'border' => 'hover:border-slate-400'],
];
?>

<div class="min-h-screen bg-slate-50/60 py-4 sm:py-8 px-3 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto space-y-4 sm:space-y-6">

        <!-- Top Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200/80">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 uppercase tracking-wider mb-1">
                    <span>Hub de Marketplaces</span>
                    <span class="text-slate-300">&bull;</span>
                    <span class="text-slate-500">Canais Conectados</span>
                </div>
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                    <svg class="w-7 h-7 text-indigo-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span><?= Html::encode($this->title) ?></span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Gerencie suas lojas integradas no Mercado Livre, Shopee, Magalu e outros canais.
                </p>
            </div>

            <div>
                <?= Html::a(
                    '<svg class="w-4 h-4 inline-block mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Nova Conexão',
                    ['create'],
                    ['class' => 'w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md shadow-indigo-200 transition']
                ) ?>
            </div>
        </div>

        <?php if (empty($configs)): ?>
            <!-- Estado Vazio -->
            <div class="bg-white rounded-2xl border border-slate-200 p-8 sm:p-12 text-center space-y-4 shadow-sm max-w-md mx-auto">
                <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900">Nenhum canal conectado ainda</h3>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Conecte sua primeira loja do Mercado Livre ou Shopee para começar a sincronizar produtos, estoques e vendas.
                    </p>
                </div>
                <?= Html::a(
                    '<svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Conectar Meu Primeiro Canal',
                    ['create'],
                    ['class' => 'inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs sm:text-sm font-semibold rounded-xl shadow transition']
                ) ?>
            </div>
        <?php else: ?>
            <!-- Grid Responsivo de Canais Conectados -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <?php foreach ($configs as $config): ?>
                    <?php
                    $meta = $badgeCores[$config->marketplace] ?? ['badge' => 'bg-indigo-100 text-indigo-900 border-indigo-300', 'border' => 'hover:border-indigo-400'];
                    ?>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4 hover:shadow-md transition duration-200 flex flex-col justify-between">
                        <div class="space-y-3">
                            <!-- Header do Card -->
                            <div class="flex items-start justify-between gap-2">
                                <div class="space-y-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border <?= $meta['badge'] ?>">
                                        <?= $config->getMarketplaceNome() ?>
                                    </span>
                                    <h3 class="text-base font-bold text-slate-900 leading-tight">
                                        <?= Html::encode($config->apelido_conta ?: 'Principal') ?>
                                    </h3>
                                </div>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold <?= $config->ativo ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $config->ativo ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>
                                    <?= $config->ativo ? 'Ativo' : 'Pausado' ?>
                                </span>
                            </div>

                            <!-- Dados Rápidos -->
                            <div class="bg-slate-50/80 rounded-xl p-3 space-y-1.5 text-xs text-slate-600">
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Seller ID:</span>
                                    <span class="font-mono font-semibold text-slate-800"><?= $config->seller_id_externo ?: 'Pendente OAuth' ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Markup Configurado:</span>
                                    <span class="font-semibold text-indigo-600">+<?= number_format((float)$config->markup_percentual, 1) ?>%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Estoque / Pedidos:</span>
                                    <span class="font-semibold text-slate-800">
                                        <?= $config->sincronizar_estoque ? '📦 Sim' : '❌ Não' ?> &bull; <?= $config->sincronizar_pedidos ? '🛒 Sim' : '❌ Não' ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Rodapé de Ações do Card -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                            <?= Html::a(
                                'Detalhes & OAuth',
                                ['view', 'id' => $config->id],
                                ['class' => 'text-xs font-bold text-indigo-600 hover:text-indigo-800 transition']
                            ) ?>

                            <div class="flex items-center gap-1.5">
                                <?= Html::a(
                                    '<svg class="w-4 h-4 text-slate-500 hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
                                    ['update', 'id' => $config->id],
                                    ['class' => 'p-1.5 rounded-lg hover:bg-slate-100 transition', 'title' => 'Editar']
                                ) ?>

                                <?= Html::a(
                                    '<svg class="w-4 h-4 text-slate-500 hover:text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>',
                                    ['delete', 'id' => $config->id],
                                    [
                                        'class' => 'p-1.5 rounded-lg hover:bg-rose-50 transition',
                                        'title' => 'Excluir',
                                        'data' => [
                                            'confirm' => 'Deseja remover esta conexão?',
                                            'method' => 'post',
                                        ],
                                    ]
                                ) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>