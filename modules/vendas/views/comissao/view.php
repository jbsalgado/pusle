<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\vendas\models\Comissao;

$this->title = 'Comissão #' . substr($model->id, 0, 8);
$this->params['breadcrumbs'][] = ['label' => 'Comissões', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-4 px-3 sm:py-6 sm:px-4 lg:px-8">
    <div class="max-w-4xl mx-auto">
        
        <!-- Header -->
        <div class="mb-4 sm:mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <div class="flex flex-wrap gap-2">
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                        ['index'],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm w-full sm:w-auto justify-center']
                    ) ?>
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>Produtos',
                        ['/vendas/produto/index'],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm w-full sm:w-auto justify-center']
                    ) ?>
                </div>
            </div>
        </div>

        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="mb-4 sm:mb-6 bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <?= Yii::$app->session->getFlash('success') ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="mb-4 sm:mb-6 bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <?= Yii::$app->session->getFlash('error') ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Card Principal -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-4 sm:mb-6">
            
            <!-- Header do Card -->
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-4 sm:px-6 py-6 sm:py-8">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-white backdrop-blur-sm">
                                <?= Html::encode($model->tipo_comissao == Comissao::TIPO_VENDA ? 'COMISSÃO DE VENDA' : 'COMISSÃO DE COBRANÇA') ?>
                            </span>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= 
                                $model->status == Comissao::STATUS_PAGA ? 'bg-green-500 text-white' : 
                                ($model->status == Comissao::STATUS_CANCELADA ? 'bg-red-500 text-white' : 'bg-yellow-500 text-white')
                            ?>">
                                <?= Html::encode($model->status) ?>
                            </span>
                        </div>
                        <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold text-white">
                            R$ <?= Yii::$app->formatter->asDecimal($model->valor_comissao, 2) ?>
                        </h2>
                        <p class="text-sm sm:text-base text-purple-100 mt-1">
                            Colaborador: <?= Html::encode($model->colaborador->nome_completo ?? '-') ?>
                        </p>
                    </div>
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white/20 flex items-center justify-center backdrop-blur-sm">
                        <svg class="w-8 h-8 sm:w-10 sm:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Conteúdo -->
            <div class="p-4 sm:p-6 lg:p-8">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Colaborador</dt>
                        <dd class="text-base font-semibold text-gray-900">
                            <?= Html::encode($model->colaborador->nome_completo ?? '-') ?>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Tipo de Comissão</dt>
                        <dd class="text-base font-semibold text-gray-900">
                            <?= Html::encode($model->tipo_comissao == Comissao::TIPO_VENDA ? 'Venda' : 'Cobrança') ?>
                        </dd>
                    </div>

                    <?php if ($model->venda): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1">Venda Relacionada</dt>
                            <dd class="text-base font-semibold text-gray-900">
                                <?= Html::a(
                                    'Venda de ' . ($model->venda->data_venda ? Yii::$app->formatter->asDate($model->venda->data_venda) : 'N/A') . ' - ' . Yii::$app->formatter->asCurrency($model->venda->valor_total),
                                    ['/vendas/venda/view', 'id' => $model->venda_id],
                                    ['class' => 'text-blue-600 hover:text-blue-800']
                                ) ?>
                            </dd>
                        </div>
                    <?php endif; ?>

                    <?php if ($model->parcela): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1">Parcela Relacionada</dt>
                            <dd class="text-base font-semibold text-gray-900">
                                Parcela #<?= $model->parcela->numero_parcela ?>
                            </dd>
                        </div>
                    <?php endif; ?>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Valor Base</dt>
                        <dd class="text-base font-semibold text-gray-900">
                            R$ <?= Yii::$app->formatter->asDecimal($model->valor_base, 2) ?>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Percentual Aplicado</dt>
                        <dd class="text-base font-semibold text-blue-600">
                            <?= Yii::$app->formatter->asDecimal($model->percentual_aplicado, 2) ?>%
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Valor da Comissão</dt>
                        <dd class="text-lg sm:text-xl font-bold text-green-600">
                            R$ <?= Yii::$app->formatter->asDecimal($model->valor_comissao, 2) ?>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Status</dt>
                        <dd>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold <?= 
                                $model->status == Comissao::STATUS_PAGA ? 'bg-green-100 text-green-800' : 
                                ($model->status == Comissao::STATUS_CANCELADA ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')
                            ?>">
                                <?= Html::encode($model->status) ?>
                            </span>
                        </dd>
                    </div>

                    <?php if ($model->data_pagamento): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1">Data de Pagamento</dt>
                            <dd class="text-base font-semibold text-gray-900">
                                <?= Yii::$app->formatter->asDate($model->data_pagamento) ?>
                            </dd>
                        </div>
                    <?php endif; ?>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Data de Criação</dt>
                        <dd class="text-base font-semibold text-gray-900">
                            <?= Yii::$app->formatter->asDatetime($model->data_criacao) ?>
                        </dd>
                    </div>

                    <?php if ($model->observacoes): ?>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 mb-1">Observações</dt>
                            <dd class="text-base text-gray-700 bg-gray-50 rounded-lg p-3 sm:p-4">
                                <?= nl2br(Html::encode($model->observacoes)) ?>
                            </dd>
                        </div>
                    <?php endif; ?>

                </dl>
            </div>

            <!-- Ações -->
            <div class="px-4 sm:px-6 lg:px-8 py-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row sm:justify-between gap-3">
                <div class="flex flex-wrap gap-2">
                    <?= Html::a(
                        '<svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>Ver Detalhes',
                        ['view', 'id' => $model->id],
                        ['class' => 'hidden']
                    ) ?>
                    <?= Html::a(
                        '<svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar',
                        ['update', 'id' => $model->id],
                        ['class' => 'inline-flex items-center justify-center px-4 sm:px-6 py-2 sm:py-3 border border-transparent text-sm sm:text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors']
                    ) ?>
                    
                    <?php if ($model->status == Comissao::STATUS_PENDENTE): ?>
                        <?= Html::a(
                            '<svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Marcar como Paga',
                            ['marcar-paga', 'id' => $model->id],
                            [
                                'class' => 'inline-flex items-center justify-center px-4 sm:px-6 py-2 sm:py-3 border border-transparent text-sm sm:text-base font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 transition-colors',
                                'data' => [
                                    'confirm' => 'Deseja marcar esta comissão como paga?',
                                    'method' => 'post',
                                ],
                            ]
                        ) ?>
                    <?php endif; ?>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= Html::beginForm(['delete', 'id' => $model->id], 'post', ['id' => 'delete-form']) ?>
                    <?= Html::button(
                        '<svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>Excluir',
                        [
                            'class' => 'inline-flex items-center justify-center px-4 sm:px-6 py-2 sm:py-3 border border-red-300 text-sm sm:text-base font-medium rounded-lg text-red-700 bg-white hover:bg-red-50 transition-colors',
                            'onclick' => 'return confirmDelete()',
                        ]
                    ) ?>
                    <?= Html::endForm() ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function confirmDelete() {
    if (confirm('Tem certeza que deseja excluir esta comissão?')) {
        document.getElementById('delete-form').submit();
    }
    return false;
}
</script>

