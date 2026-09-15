<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\marketplace\models\MarketplaceConfig */

$this->title = 'Editar Conexão: ' . $model->getMarketplaceNome() . ' (' . $model->apelido_conta . ')';
?>

<div class="min-h-screen bg-slate-50/60 py-4 sm:py-8 px-3 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-4 sm:space-y-6">
        
        <!-- Header / Breadcrumb -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200/80">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 uppercase tracking-wider mb-1">
                    <span>Hub de Marketplaces</span>
                    <span class="text-slate-300">&bull;</span>
                    <span class="text-slate-500">Configurações da Loja</span>
                </div>
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                    <svg class="w-7 h-7 text-indigo-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span><?= Html::encode($this->title) ?></span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Atualize credenciais, markups de comissão ou regras de sincronização desta conta.
                </p>
            </div>

            <!-- Quick Action Links -->
            <div class="flex flex-wrap items-center gap-2">
                <?= Html::a(
                    '<svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>Detalhes',
                    ['view', 'id' => $model->id],
                    ['class' => 'inline-flex items-center px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shadow-sm transition']
                ) ?>
                
                <?= Html::a(
                    '<svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                    ['index'],
                    ['class' => 'inline-flex items-center px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition']
                ) ?>
            </div>
        </div>

        <!-- Token Status Alert (se existir token expira em) -->
        <?php if ($model->token_expira_em): ?>
            <div class="p-4 rounded-xl border flex items-center gap-3 <?= $model->isTokenExpired() ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-emerald-50 border-emerald-200 text-emerald-900' ?>">
                <?php if ($model->isTokenExpired()): ?>
                    <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div class="text-xs sm:text-sm font-medium">
                        <strong>Token de Acesso Expirado:</strong> Expirou em <?= Yii::$app->formatter->asDatetime($model->token_expira_em) ?>. Conecte novamente via OAuth para renovar o acesso.
                    </div>
                <?php else: ?>
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="text-xs sm:text-sm font-medium">
                        <strong>Token de Acesso Válido:</strong> Expira em <?= Yii::$app->formatter->asDatetime($model->token_expira_em) ?>.
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Render do Formulário Tailwind Mobile-First -->
        <?= $this->render('_form', [
            'model' => $model,
        ]) ?>

    </div>
</div>