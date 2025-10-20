<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\StatusParcela */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="status-parcela-form">
    <?php $form = ActiveForm::begin([
        'id' => 'status-parcela-form',
        'options' => ['class' => 'space-y-6'],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{hint}\n{error}",
            'labelOptions' => ['class' => 'block text-sm font-medium text-gray-700 mb-2'],
            'inputOptions' => ['class' => 'block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 text-sm sm:text-base'],
            'errorOptions' => ['class' => 'mt-2 text-sm text-red-600'],
            'hintOptions' => ['class' => 'mt-2 text-sm text-gray-500'],
        ],
    ]); ?>

    <!-- Campo Código -->
    <div class="form-group">
        <?= $form->field($model, 'codigo')->textInput([
            'maxlength' => true,
            'readonly' => !$model->isNewRecord,
            'class' => 'block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 text-sm sm:text-base' . (!$model->isNewRecord ? ' bg-gray-100 cursor-not-allowed' : ''),
            'placeholder' => 'Ex: PAGO, PENDENTE, VENCIDO',
        ])->label('Código do Status') ?>
        
        <?php if (!$model->isNewRecord): ?>
            <p class="mt-2 text-xs text-gray-500 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                O código não pode ser alterado após a criação
            </p>
        <?php endif; ?>
    </div>

    <!-- Campo Descrição -->
    <div class="form-group">
        <?= $form->field($model, 'descricao')->textInput([
            'maxlength' => true,
            'placeholder' => 'Digite uma descrição clara e objetiva',
        ])->label('Descrição') ?>
    </div>

    <!-- Botões de Ação -->
    <div class="form-group pt-4 border-t border-gray-200">
        <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
            <?= Html::a(
                '<svg class="w-5 h-5 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>Cancelar',
                ['index'],
                ['class' => 'inline-flex justify-center items-center px-6 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200 w-full sm:w-auto']
            ) ?>
            
            <?= Html::submitButton(
                '<svg class="w-5 h-5 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' . ($model->isNewRecord ? 'Criar Status' : 'Salvar Alterações'),
                ['class' => 'inline-flex justify-center items-center px-6 py-3 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 w-full sm:w-auto']
            ) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<style>
    /* Estilos adicionais para campos do Yii2 com Tailwind */
    .field-statusparcela-codigo.has-error input,
    .field-statusparcela-descricao.has-error input {
        border-color: #ef4444 !important;
        background-color: #fef2f2 !important;
    }
    
    .field-statusparcela-codigo.has-success input,
    .field-statusparcela-descricao.has-success input {
        border-color: #10b981 !important;
    }
    
    .help-block {
        margin-top: 0.5rem;
        font-size: 0.875rem;
        color: #ef4444;
    }
    
    /* Animação de foco */
    input:focus {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    /* Loading state para o botão de submit */
    button[type="submit"]:active {
        transform: scale(0.98);
    }
</style>