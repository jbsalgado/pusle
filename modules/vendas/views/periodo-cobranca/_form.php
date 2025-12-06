<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\vendas\models\PeriodoCobranca;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\PeriodoCobranca */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="periodo-cobranca-form bg-white rounded-lg shadow-md p-6">
    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'space-y-6'],
    ]); ?>

    <!-- Informações do Período -->
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Informações do Período
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?= $form->field($model, 'mes_referencia')->dropDownList(
                [
                    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                ],
                ['class' => 'form-control']
            )->label('Mês de Referência') ?>

            <?= $form->field($model, 'ano_referencia')->textInput(['type' => 'number', 'min' => '2020', 'max' => '2099', 'class' => 'form-control'])->label('Ano de Referência') ?>

            <?= $form->field($model, 'data_inicio')->textInput(['type' => 'date', 'class' => 'form-control'])->label('Data de Início') ?>

            <?= $form->field($model, 'data_fim')->textInput(['type' => 'date', 'class' => 'form-control'])->label('Data de Fim') ?>
        </div>
    </div>

    <!-- Status e Descrição -->
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Status e Descrição
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?= $form->field($model, 'status')->dropDownList(
                [
                    PeriodoCobranca::STATUS_ABERTO => 'Aberto',
                    PeriodoCobranca::STATUS_EM_COBRANCA => 'Em Cobrança',
                    PeriodoCobranca::STATUS_FECHADO => 'Fechado',
                ],
                ['class' => 'form-control']
            )->label('Status') ?>

            <?= $form->field($model, 'descricao')->textInput(['maxlength' => true, 'class' => 'form-control', 'placeholder' => 'Deixe em branco para gerar automaticamente'])->label('Descrição (opcional)') ?>
        </div>
    </div>

    <!-- Botões de Ação -->
    <div class="flex flex-col sm:flex-row gap-3 pt-4">
        <?= Html::submitButton('Salvar', ['class' => 'flex-1 sm:flex-none px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300']) ?>
        <?= Html::a('Cancelar', ['index'], ['class' => 'flex-1 sm:flex-none px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300 text-center']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

