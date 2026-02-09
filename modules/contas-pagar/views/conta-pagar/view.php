<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\modules\contas_pagar\models\ContaPagar;

/* @var $this yii\web\View */
/* @var $model app\modules\contas_pagar\models\ContaPagar */

$this->title = $model->descricao;
$this->params['breadcrumbs'][] = ['label' => 'Contas a Pagar', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="min-h-screen bg-gray-50 py-4 px-3 sm:py-6 sm:px-4 lg:px-8">
    <div class="max-w-4xl mx-auto">

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <div class="mt-1 flex items-center gap-2">
                    <span class="text-gray-500">ID: #<?= substr($model->id, 0, 8) ?></span>
                    <?php
                    $badgeClass = 'bg-gray-100 text-gray-800';
                    if ($model->isPaga()) $badgeClass = 'bg-green-100 text-green-800';
                    elseif ($model->isVencida()) $badgeClass = 'bg-red-100 text-red-800';
                    elseif ($model->isPendente()) $badgeClass = 'bg-yellow-100 text-yellow-800';
                    ?>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badgeClass ?>">
                        <?= Html::encode($model->status) ?>
                    </span>
                </div>
            </div>

            <div class="flex gap-2 w-full sm:w-auto">
                <?= Html::a('Voltar', ['index'], ['class' => 'flex-1 sm:flex-none text-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500']) ?>

                <?php if (!$model->isPaga() && $model->status !== ContaPagar::STATUS_CANCELADA): ?>
                    <?= Html::a('Pagar', ['pagar', 'id' => $model->id], [
                        'class' => 'flex-1 sm:flex-none text-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500',
                        'data' => [
                            'method' => 'post',
                            'confirm' => 'Tem certeza que deseja marcar esta conta como PAGA?'
                        ]
                    ]) ?>
                <?php endif; ?>

                <?= Html::a('Editar', ['update', 'id' => $model->id], ['class' => 'flex-1 sm:flex-none text-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500']) ?>

                <?= Html::a('Excluir', ['delete', 'id' => $model->id], [
                    'class' => 'flex-1 sm:flex-none text-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500',
                    'data' => [
                        'confirm' => 'Tem certeza que deseja excluir este item?',
                        'method' => 'post',
                    ],
                ]) ?>
            </div>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Detalhes da Conta
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    Informações financeiras e de fornecedor.
                </p>
            </div>

            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Valor</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-bold text-lg">
                            <?= Yii::$app->formatter->asCurrency($model->valor) ?>
                        </dd>
                    </div>

                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Fornecedor</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?= $model->fornecedor ? Html::encode($model->fornecedor->nome) : '<span class="text-gray-400 italic">Não informado / Despesa Avulsa</span>' ?>
                        </dd>
                    </div>

                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Data de Vencimento</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 <?= $model->isVencida() ? 'text-red-600 font-bold' : '' ?>">
                            <?= Yii::$app->formatter->asDate($model->data_vencimento, 'long') ?>
                            <?php if ($model->getDiasAtraso()): ?>
                                <span class="bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded ml-2">
                                    <?= $model->getDiasAtraso() ?> dias de atraso
                                </span>
                            <?php endif; ?>
                        </dd>
                    </div>

                    <?php if ($model->data_pagamento): ?>
                        <div class="bg-green-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-green-700">Data de Pagamento</dt>
                            <dd class="mt-1 text-sm text-green-700 sm:mt-0 sm:col-span-2 font-semibold">
                                <?= Yii::$app->formatter->asDate($model->data_pagamento, 'long') ?>
                            </dd>
                        </div>
                    <?php endif; ?>

                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Forma de Pagamento</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?= $model->formaPagamento ? Html::encode($model->formaPagamento->nome) : '-' ?>
                        </dd>
                    </div>

                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Observações</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?= $model->observacoes ? nl2br(Html::encode($model->observacoes)) : '-' ?>
                        </dd>
                    </div>

                    <?php if ($model->arquivo_comprovante): ?>
                        <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Comprovante / Anexo</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                <?= Html::a(
                                    '<svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> Baixar Arquivo (' . basename($model->arquivo_comprovante) . ')',
                                    '@web/' . $model->arquivo_comprovante,
                                    ['class' => 'text-blue-600 hover:text-blue-800 hover:underline', 'target' => '_blank']
                                ) ?>
                            </dd>
                        </div>
                    <?php endif; ?>

                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Registrado por</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?= $model->usuario ? Html::encode($model->usuario->getPrimeiroNome()) : 'Sistema' ?>
                            <span class="text-gray-400 text-xs ml-1">em <?= Yii::$app->formatter->asDatetime($model->data_criacao) ?></span>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>