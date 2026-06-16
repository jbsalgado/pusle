<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $model->nome;
$this->params['breadcrumbs'][] = ['label' => 'Formas de Pagamento', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$tipoIcons = [
    'DINHEIRO' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>',
    'PIX' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>',
    'CARTAO' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>',
    'CARTAO_CREDITO' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>',
    'CARTAO_DEBITO' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>',
    'BOLETO' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
    'TRANSFERENCIA' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>',
    'CHEQUE' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
    'OUTRO' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>',
    'OUTROS' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>',
];

$tipoBadges = [
    'DINHEIRO' => 'bg-green-100 text-green-800',
    'PIX' => 'bg-blue-100 text-blue-800',
    'CARTAO' => 'bg-purple-100 text-purple-800',
    'CARTAO_CREDITO' => 'bg-purple-100 text-purple-800',
    'CARTAO_DEBITO' => 'bg-purple-100 text-purple-800',
    'BOLETO' => 'bg-orange-100 text-orange-800',
    'TRANSFERENCIA' => 'bg-indigo-100 text-indigo-800',
    'CHEQUE' => 'bg-yellow-100 text-yellow-800',
    'OUTRO' => 'bg-gray-100 text-gray-800',
    'OUTROS' => 'bg-gray-100 text-gray-800',
];
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center mb-4">
                <a href="<?= Url::to(['index']) ?>" 
                   class="mr-4 text-gray-600 hover:text-gray-900 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div class="flex-1">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
                        Detalhes da Forma de Pagamento
                    </h1>
                </div>
            </div>
        </div>

        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <?= Yii::$app->session->getFlash('success') ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Card Principal -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
            <!-- Header do Card com Ícone -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-8">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-16 h-16 bg-white bg-opacity-20 rounded-xl flex items-center justify-center text-white">
                        <?= $tipoIcons[$model->tipo] ?? $tipoIcons['OUTRO'] ?>
                    </div>
                    <div class="ml-4 flex-1">
                        <h2 class="text-2xl font-bold text-white">
                            <?= Html::encode($model->nome) ?>
                        </h2>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $tipoBadges[$model->tipo] ?? $tipoBadges['OUTRO'] ?>">
                                <?= Html::encode($model->tipo) ?>
                            </span>
                            <?php if ($model->ativo): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    Ativo
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-200 text-gray-800">
                                    Inativo
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informações Detalhadas -->
            <div class="px-6 py-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">ID</dt>
                        <dd class="text-base font-semibold text-gray-900"><?= Html::encode($model->id) ?></dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Tipo de Pagamento</dt>
                        <dd class="text-base font-semibold text-gray-900"><?= Html::encode($model->tipo) ?></dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Status</dt>
                        <dd class="text-base font-semibold text-gray-900">
                            <?= $model->ativo ? 'Ativo' : 'Inativo' ?>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Aceita Parcelamento</dt>
                        <dd class="text-base font-semibold text-gray-900">
                            <?= $model->aceita_parcelamento ? 'Sim' : 'Não' ?>
                        </dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500 mb-1">Data de Criação</dt>
                        <dd class="text-base font-semibold text-gray-900">
                            <?= Yii::$app->formatter->asDatetime($model->data_criacao) ?>
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Ações -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row sm:justify-between gap-3">
                <a href="<?= Url::to(['index']) ?>" 
                   class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    Voltar à Lista
                </a>

                <div class="flex flex-col sm:flex-row gap-3">
                    <?= Html::beginForm(['delete', 'id' => $model->id], 'post', ['id' => 'delete-form']) ?>
                    <?= Html::button('Excluir', [
                        'class' => 'inline-flex items-center justify-center px-6 py-3 border border-red-300 text-base font-medium rounded-lg text-red-700 bg-white hover:bg-red-50 transition-colors',
                        'onclick' => 'return confirmDelete()',
                        'disabled' => $model->getParcelas()->count() > 0
                    ]) ?>
                    <?= Html::endForm() ?>

                    <a href="<?= Url::to(['update', 'id' => $model->id]) ?>" 
                       class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Editar
                    </a>
                </div>
            </div>
        </div>

        <!-- Informações sobre Parcelas -->
        <?php 
        $totalParcelas = $model->getParcelas()->count();
        if ($totalParcelas > 0):
        ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Parcelas Associadas</h3>
                        <p class="mt-1 text-sm text-blue-700">
                            Esta forma de pagamento possui <strong><?= $totalParcelas ?></strong> parcela(s) associada(s) e não pode ser excluída.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete() {
    if (confirm('Tem certeza que deseja excluir esta forma de pagamento? Esta ação não pode ser desfeita.')) {
        return true;
    }
    return false;
}
</script>