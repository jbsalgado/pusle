<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\Regiao */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden">

    <?php $form = ActiveForm::begin([
        'id' => 'regiao-form',
        'options' => ['class' => 'space-y-6 p-6'],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{hint}\n{error}",
            'labelOptions' => ['class' => 'block text-sm font-medium text-gray-700 mb-1'],
            'inputOptions' => ['class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'],
            'errorOptions' => ['class' => 'text-red-600 text-sm mt-1'],
        ],
    ]); ?>

    <div class="grid grid-cols-1 gap-6">

        <!-- Nome -->
        <div>
            <?= $form->field($model, 'nome')->textInput([
                'maxlength' => true,
                'placeholder' => 'Nome da Região',
                'autofocus' => true
            ]) ?>
        </div>

        <!-- Descrição -->
        <div>
            <?= $form->field($model, 'descricao')->textarea([
                'rows' => 3,
                'placeholder' => 'Descrição opcional da região (bairros abrangidos, etc.)'
            ]) ?>
        </div>

        <!-- Cor de Identificação -->
        <div>
            <?= $form->field($model, 'cor_identificacao')->input('color', [
                'class' => 'h-10 w-20 p-1 border border-gray-300 rounded cursor-pointer',
                'title' => 'Escolha uma cor para identificar esta região no mapa/lista'
            ])->label('Cor de Identificação') ?>
        </div>

        <!-- Ativo -->
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <?= Html::activeCheckbox($model, 'ativo', [
                        'class' => 'w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 focus:ring-2',
                        'label' => false,
                        'id' => 'ativo_checkbox'
                    ]) ?>
                </div>
                <div class="ml-3">
                    <label for="ativo_checkbox" class="font-medium text-gray-900 cursor-pointer">Região Ativa</label>
                    <p class="text-sm text-gray-500">Desmarque para desativar esta região (não aparecerá em novas seleções)</p>
                </div>
            </div>
        </div>

    </div>

    <!-- Botões -->
    <div class="flex items-center gap-3 pt-6 border-t border-gray-200 mt-6">
        <?= Html::submitButton(
            $model->isNewRecord ? 'Criar Região' : 'Salvar Alterações',
            ['class' => 'px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
        ) ?>
        <?= Html::a(
            'Cancelar',
            ['index'],
            ['class' => 'px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition duration-300']
        ) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>