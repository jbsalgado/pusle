<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\marketplace\models\MarketplaceConfig */

$this->title = $model->getMarketplaceNome() . ' - ' . ($model->apelido_conta ?: 'Principal');

// Cores e badges por canal
$badgeCores = [
    'MERCADO_LIVRE' => 'bg-yellow-100 text-yellow-900 border-yellow-300',
    'SHOPEE' => 'bg-orange-100 text-orange-900 border-orange-300',
    'MAGAZINE_LUIZA' => 'bg-blue-100 text-blue-900 border-blue-300',
    'TEMU' => 'bg-amber-100 text-amber-900 border-amber-300',
    'IFOOD' => 'bg-red-100 text-red-900 border-red-300',
    'AMAZON' => 'bg-slate-100 text-slate-900 border-slate-300',
];
$canalBadge = $badgeCores[$model->marketplace] ?? 'bg-indigo-100 text-indigo-900 border-indigo-300';
?>

<div class="min-h-screen bg-slate-50/60 py-4 sm:py-8 px-3 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-4 sm:space-y-6">

        <!-- Top Header & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200/80">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border <?= $canalBadge ?>">
                        <?= $model->getMarketplaceNome() ?>
                    </span>
                    <span class="text-xs text-slate-400">&bull;</span>
                    <span class="text-xs font-semibold text-slate-500">ID da Configuração: <?= substr($model->id, 0, 8) ?>...</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                    <?= Html::encode($model->apelido_conta ?: 'Conta Principal') ?>
                </h1>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <?= Html::a(
                    '<svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar',
                    ['update', 'id' => $model->id],
                    ['class' => 'inline-flex items-center px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shadow-sm transition']
                ) ?>

                <?= Html::a(
                    $model->ativo
                        ? '<svg class="w-4 h-4 inline-block mr-1 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Pausar'
                        : '<svg class="w-4 h-4 inline-block mr-1 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Ativar',
                    ['toggle', 'id' => $model->id],
                    [
                        'class' => 'inline-flex items-center px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shadow-sm transition',
                        'data-method' => 'post',
                    ]
                ) ?>

                <?php if ($model->isConectado()): ?>
                    <?= Html::a(
                        '<svg class="w-4 h-4 inline-block mr-1 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6"/></svg>Desconectar',
                        ['disconnect', 'id' => $model->id],
                        [
                            'class' => 'inline-flex items-center px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-white border border-rose-200 text-rose-700 hover:bg-rose-50 shadow-sm transition',
                            'data' => [
                                'confirm' => "Tem certeza que deseja desconectar esta conta? Os tokens de sincronização serão revogados.",
                                'method' => 'post',
                            ],
                        ]
                    ) ?>
                <?php endif; ?>

                <?= Html::a(
                    '<svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                    ['index'],
                    ['class' => 'inline-flex items-center px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition']
                ) ?>
            </div>
        </div>

        <!-- Alerta de Conexão OAuth Necessária -->
        <?php if (in_array($model->marketplace, ['MERCADO_LIVRE', 'SHOPEE'])): ?>
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl p-4 sm:p-6 text-white shadow-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/20 text-white text-xs font-bold uppercase tracking-wider">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        Autorização OAuth 1-Clique
                    </div>
                    <h3 class="text-lg sm:text-xl font-extrabold leading-tight">
                        Conectar Loja Oficial do <?= $model->getMarketplaceNome() ?>
                    </h3>
                    <p class="text-xs sm:text-sm text-indigo-100 max-w-xl">
                        Clique no botão ao lado para autorizar o acesso da sua loja. O Pulse receberá os tokens seguros e iniciará a sincronização de estoque e pedidos.
                    </p>
                </div>

                <?= Html::a(
                    '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>Conectar / Autorizar OAuth',
                    ['auth', 'id' => $model->id],
                    [
                        'class' => 'w-full sm:w-auto px-6 py-3.5 bg-white text-indigo-700 hover:bg-indigo-50 font-bold text-sm rounded-xl shadow-md transition-all flex items-center justify-center flex-shrink-0 cursor-pointer',
                    ]
                ) ?>
            </div>
        <?php endif; ?>

        <!-- Status Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
            <!-- Status Conexão -->
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center <?= $model->ativo ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <span class="text-xs text-slate-400 font-medium block">Status da Conexão</span>
                    <span class="text-sm font-bold <?= $model->ativo ? 'text-emerald-700' : 'text-rose-700' ?>">
                        <?= $model->ativo ? 'Conexão Ativa' : 'Pausada / Inativa' ?>
                    </span>
                </div>
            </div>

            <!-- Seller ID -->
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                    <span class="text-xs text-slate-400 font-medium block">Seller ID / Shop ID</span>
                    <span class="text-sm font-bold text-slate-800 font-mono">
                        <?= $model->seller_id_externo ?: 'Pendente de login' ?>
                    </span>
                </div>
            </div>

            <!-- Token Validade -->
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <span class="text-xs text-slate-400 font-medium block">Validade do Acesso</span>
                    <span class="text-sm font-bold <?= ($model->token_expira_em && $model->isTokenExpired()) ? 'text-rose-600' : 'text-slate-800' ?>">
                        <?= $model->token_expira_em ? ($model->isTokenExpired() ? 'Expirado' : 'Válido') : 'Não sincronizado' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Detalhes do Canal Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-6 space-y-4">
            <h3 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Parâmetros e Automações Cadastradas
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div class="space-y-3">
                    <div class="flex justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-500">Marketplace:</span>
                        <span class="font-semibold text-slate-800"><?= $model->getMarketplaceNome() ?></span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-500">Client ID / App ID:</span>
                        <span class="font-mono font-semibold text-slate-800"><?= $model->client_id ?: 'Não informado' ?></span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-500">Acréscimo Percentual (Markup):</span>
                        <span class="font-semibold text-indigo-600">+<?= number_format((float)$model->markup_percentual, 2, ',', '.') ?>%</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-500">Acréscimo Fixo por Produto:</span>
                        <span class="font-semibold text-slate-800">R$ <?= number_format((float)$model->markup_valor_fixo, 2, ',', '.') ?></span>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-500">Sincronização de Estoque:</span>
                        <span class="font-semibold <?= $model->sincronizar_estoque ? 'text-emerald-600' : 'text-slate-400' ?>">
                            <?= $model->sincronizar_estoque ? 'Ativa (Fila Assíncrona)' : 'Desativada' ?>
                        </span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-500">Importação de Pedidos:</span>
                        <span class="font-semibold <?= $model->sincronizar_pedidos ? 'text-emerald-600' : 'text-slate-400' ?>">
                            <?= $model->sincronizar_pedidos ? 'Ativa (Webhooks Fast-ACK)' : 'Desativada' ?>
                        </span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-500">Arredondamento:</span>
                        <span class="font-semibold text-slate-800"><?= $model->arredondar_centavos_99 ? 'Sim (R$ xx,99)' : 'Preço exato' ?></span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-500">Data de Criação:</span>
                        <span class="text-slate-700"><?= Yii::$app->formatter->asDatetime($model->data_criacao) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Links Rodapé -->
        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
            <?= Html::a(
                '<svg class="w-4 h-4 inline-block mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Ver Histórico e Logs de Sincronização',
                ['/marketplace/sync/index'],
                ['class' => 'text-xs sm:text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition']
            ) ?>

            <?= Html::a(
                '<svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>Remover Conexão',
                ['delete', 'id' => $model->id],
                [
                    'class' => 'text-xs sm:text-sm font-semibold text-rose-600 hover:text-rose-800 transition',
                    'data' => [
                        'confirm' => 'Tem certeza que deseja remover esta conexão de marketplace?',
                        'method' => 'post',
                    ],
                ]
            ) ?>
        </div>

    </div>
</div>