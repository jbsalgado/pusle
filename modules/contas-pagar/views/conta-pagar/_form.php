<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use app\modules\vendas\models\Fornecedor;
use app\modules\vendas\models\FormaPagamento;

/* @var $this yii\web\View */
/* @var $model app\modules\contas_pagar\models\ContaPagar */
/* @var $form yii\widgets\ActiveForm */

// Fetch lists for dropdowns
$fornecedores = ArrayHelper::map(Fornecedor::find()->orderBy('nome')->all(), 'id', 'nome');
$formasPagamento = ArrayHelper::map(FormaPagamento::find()->where(['ativo' => true])->orderBy('nome')->all(), 'id', 'nome');

?>

<div class="bg-white rounded-lg shadow-md overflow-hidden">

    <?php $form = ActiveForm::begin([
        'id' => 'conta-pagar-form',
        'options' => [
            'class' => 'space-y-6 p-4 sm:p-6 lg:p-8',
            'enctype' => 'multipart/form-data'
        ],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{hint}\n{error}",
            'labelOptions' => ['class' => 'block text-sm font-medium text-gray-700 mb-2'],
            'inputOptions' => ['class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'],
            'errorOptions' => ['class' => 'text-red-600 text-sm mt-1'],
        ],
    ]); ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">

        <div class="sm:col-span-2">
            <?= $form->field($model, 'descricao')->textInput([
                'maxlength' => true,
                'placeholder' => 'Ex: Aluguel Fevereiro/2026',
                'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
            ]) ?>
        </div>

        <div>
            <?= $form->field($model, 'valor')->textInput([
                'type' => 'number',
                'step' => '0.01',
                'min' => '0.01',
                'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base font-bold'
            ]) ?>
        </div>

        <div>
            <?= $form->field($model, 'data_vencimento')->input('date', [
                'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
            ]) ?>
        </div>

        <div>
            <?= $form->field($model, 'fornecedor_id')->dropDownList(
                $fornecedores,
                [
                    'prompt' => 'Selecione um Fornecedor...',
                    'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
                ]
            ) ?>
            <p class="text-xs text-gray-500 mt-1">Opcional. Se não selecionar, será uma despesa avulsa.</p>
        </div>

        <div>
            <?= $form->field($model, 'forma_pagamento_id')->dropDownList(
                $formasPagamento,
                [
                    'prompt' => 'Selecione a Forma de Pagamento...',
                    'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
                ]
            ) ?>
        </div>

        <div class="sm:col-span-2">
            <?= $form->field($model, 'comprovanteFile')->fileInput([
                'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
            ])->hint('Formatos aceitos: PDF, JPG, PNG (Max: 5MB)') ?>

            <?php if ($model->arquivo_comprovante): ?>
                <div class="mt-2 text-sm text-gray-600 flex items-center">
                    <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    Arquivo atual: <span class="font-medium ml-1"><?= basename($model->arquivo_comprovante) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="sm:col-span-2">
            <?= $form->field($model, 'observacoes')->textarea([
                'rows' => 3,
                'placeholder' => 'Detalhes adicionais...',
                'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none text-sm sm:text-base'
            ]) ?>
        </div>

    </div>

    <!-- Botões de Ação -->
    <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-gray-200">
        <?= Html::submitButton(
            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' . ($model->isNewRecord ? 'Registrar Conta' : 'Salvar Alterações'),
            ['class' => 'w-full sm:flex-1 inline-flex items-center justify-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm sm:text-base']
        ) ?>
        <?= Html::a(
            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Cancelar',
            ['index'],
            ['class' => 'w-full sm:flex-1 inline-flex items-center justify-center px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold rounded-lg transition duration-300 text-sm sm:text-base']
        ) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>