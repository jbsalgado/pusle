<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>

<div class="categoria-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="space-y-6">
        
        <!-- Nome da Categoria -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nome da Categoria *</label>
            <?= $form->field($model, 'nome')->textInput([
                'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                'placeholder' => 'Ex: Eletrônicos',
                'autofocus' => true
            ])->label(false) ?>
        </div>

        <!-- Descrição -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
            <?= $form->field($model, 'descricao')->textarea([
                'rows' => 4,
                'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                'placeholder' => 'Descreva esta categoria...'
            ])->label(false) ?>
        </div>

        <!-- Ordem de Exibição -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Ordem de Exibição</label>
            <?= $form->field($model, 'ordem')->textInput([
                'type' => 'number',
                'min' => '0',
                'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                'placeholder' => '0'
            ])->label(false)->hint('Define a ordem de exibição nas listagens', ['class' => 'text-sm text-gray-500 mt-1']) ?>
        </div>

        <!-- Status Ativo -->
        <div class="flex items-center">
            <label class="flex items-center cursor-pointer">
                <?= Html::activeCheckbox($model, 'ativo', [
                    'class' => 'w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500',
                    'label' => null
                ]) ?>
                <span class="ml-2 text-sm font-medium text-gray-700">Categoria Ativa</span>
            </label>
        </div>

        <!-- Botões -->
        <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t">
            <?= Html::submitButton(
                $model->isNewRecord 
                    ? '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Cadastrar' 
                    : '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Salvar',
                ['class' => 'flex-1 inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300']
            ) ?>
            <?= Html::a('Cancelar', ['index'], 
                ['class' => 'flex-1 text-center px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold rounded-lg transition duration-300']) ?>
        </div>

    </div>

    <?php ActiveForm::end(); ?>

</div>