<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\marketplace\models\MarketplaceConfig */

$this->title = 'Nova Conexão de Marketplace';
?>

<div class="min-h-screen bg-slate-50/60 py-4 sm:py-8 px-3 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-4 sm:space-y-6">
        
        <!-- Top Navigation / Breadcrumb Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200/80">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 uppercase tracking-wider mb-1">
                    <span>Hub de Marketplaces</span>
                    <span class="text-slate-300">&bull;</span>
                    <span class="text-slate-500">Configuração Multi-Tenant</span>
                </div>
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                    <svg class="w-7 h-7 text-indigo-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span><?= Html::encode($this->title) ?></span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Conecte a sua loja do Mercado Livre, Shopee, Magalu ou outro canal para unificar pedidos e estoque no Pulse ERP.
                </p>
            </div>

            <div class="flex-shrink-0">
                <?= Html::a(
                    '<svg class="w-4 h-4 inline-block mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Ver Conexões',
                    ['index'],
                    ['class' => 'inline-flex items-center justify-center px-4 py-2.5 text-xs sm:text-sm font-semibold rounded-xl bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 hover:text-slate-900 shadow-sm transition']
                ) ?>
            </div>
        </div>

        <!-- Render do Formulário Tailwind Mobile-First -->
        <?= $this->render('_form', [
            'model' => $model,
        ]) ?>

    </div>
</div>