<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\UnidadeMedidaVolume */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">

    <?php $form = ActiveForm::begin(); ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Código/Nome (Ex: M3, KG, UN)</label>
            <?= $form->field($model, 'nome')->textInput([
                'maxlength' => true,
                'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-mono uppercase',
                'placeholder' => 'Ex: M3',
                'readonly' => !$model->isNewRecord // Chave primária não deve ser alterada no update
            ])->label(false) ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Descrição Completa</label>
            <?= $form->field($model, 'descricao')->textInput([
                'maxlength' => true,
                'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all',
                'placeholder' => 'Ex: Metro Cúbico'
            ])->label(false) ?>
        </div>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <?= $form->field($model, 'ativo')->checkbox([
            'class' => 'w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500',
            'labelOptions' => ['class' => 'ml-2 text-sm font-medium text-gray-700']
        ]) ?>
    </div>

    <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end gap-3">
        <?= Html::a('Cancelar', ['index'], ['class' => 'px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition-all']) ?>
        <?= Html::submitButton('Salvar Unidade', ['class' => 'px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition-all']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
