<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\vendas\models\FormaPagamento;

/** @var yii\web\View $this */
/** @var app\modules\contas_pagar\models\ContaPagar $model */

// Buscar formas de pagamento
$formasPagamento = FormaPagamento::find()->orderBy(['nome' => SORT_ASC])->all();
$formasPagamentoList = \yii\helpers\ArrayHelper::map($formasPagamento, 'id', 'nome');

// Verificar saldo do caixa
$caixa = \app\modules\caixa\helpers\CaixaHelper::getCaixaAberto();
$saldoAtual = $caixa ? $caixa->calcularValorEsperado() : 0;
$saldoSuficiente = $saldoAtual >= $model->valor;
?>

<div class="pagar-conta-form">

    <div class="mb-4">
        <h4 class="text-lg font-bold text-gray-900">Confirmar Pagamento</h4>
        <p class="text-sm text-gray-600 mt-1">Conta: <?= Html::encode($model->descricao) ?></p>
        <p class="text-lg font-bold text-gray-900 mt-2">Valor: <?= Yii::$app->formatter->asCurrency($model->valor) ?></p>
    </div>

    <!-- Alerta de Saldo -->
    <?php if (!$caixa): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4">
            <div class="flex">
                <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <p class="text-sm font-semibold text-yellow-800">Caixa Fechado</p>
                    <p class="text-xs text-yellow-700 mt-1">O pagamento será registrado, mas não será debitado do caixa automaticamente.</p>
                </div>
            </div>
        </div>
    <?php elseif (!$saldoSuficiente): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
            <div class="flex">
                <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <p class="text-sm font-semibold text-red-800">Saldo Insuficiente</p>
                    <p class="text-xs text-red-700 mt-1">
                        Saldo atual: <?= Yii::$app->formatter->asCurrency($saldoAtual) ?> |
                        Necessário: <?= Yii::$app->formatter->asCurrency($model->valor) ?>
                    </p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
            <div class="flex">
                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <p class="text-sm font-semibold text-green-800">Saldo Disponível</p>
                    <p class="text-xs text-green-700 mt-1">Saldo atual: <?= Yii::$app->formatter->asCurrency($saldoAtual) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php $form = ActiveForm::begin([
        'id' => 'form-pagar-conta',
        'action' => ['pagar', 'id' => $model->id],
        'options' => ['class' => 'space-y-4'],
    ]); ?>

    <!-- Data de Pagamento -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Data de Pagamento</label>
        <input type="date"
            name="data_pagamento"
            value="<?= date('Y-m-d') ?>"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            required>
    </div>

    <!-- Forma de Pagamento -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Forma de Pagamento</label>
        <select name="forma_pagamento_id"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">Selecione...</option>
            <?php foreach ($formasPagamentoList as $id => $nome): ?>
                <option value="<?= $id ?>" <?= $model->forma_pagamento_id == $id ? 'selected' : '' ?>>
                    <?= Html::encode($nome) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="text-xs text-gray-500 mt-1">Opcional - será registrada na movimentação do caixa</p>
    </div>

    <!-- Validar Saldo -->
    <?php if ($caixa): ?>
        <div class="flex items-center">
            <input type="checkbox"
                name="validar_saldo"
                value="1"
                id="validar_saldo"
                <?= $saldoSuficiente ? 'checked' : '' ?>
                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
            <label for="validar_saldo" class="ml-2 text-sm text-gray-700">
                Validar saldo antes de pagar
            </label>
        </div>
        <p class="text-xs text-gray-500 -mt-2 ml-6">
            Se marcado, o pagamento será bloqueado caso não haja saldo suficiente no caixa
        </p>
    <?php endif; ?>

    <!-- Botões -->
    <div class="flex justify-end gap-2 pt-4 border-t border-gray-200">
        <button type="button"
            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition"
            onclick="$('#modal-pagar').modal('hide');">
            Cancelar
        </button>
        <button type="submit"
            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
            <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Confirmar Pagamento
        </button>
    </div>

    <?php ActiveForm::end(); ?>

</div>