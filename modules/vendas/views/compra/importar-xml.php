<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model yii\base\DynamicModel */

$this->title = 'Importar XML de NFe';
$this->params['breadcrumbs'][] = ['label' => 'Compras', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="compra-importar-xml max-w-2xl mx-auto mt-8">

    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6"><?= Html::encode($this->title) ?></h1>

        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        Selecione o arquivo XML da Nota Fiscal Eletrônica (NFe).
                        <br>O sistema tentará identificar o fornecedor e os produtos automaticamente.
                    </p>
                </div>
            </div>
        </div>

        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Arquivo XML</label>
            <?= $form->field($model, 'file')->fileInput([
                'class' => 'block w-full text-sm text-gray-500
                  file:mr-4 file:py-2 file:px-4
                  file:rounded-full file:border-0
                  file:text-sm file:font-semibold
                  file:bg-blue-50 file:text-blue-700
                  hover:file:bg-blue-100',
                'accept' => '.xml'
            ])->label(false) ?>
        </div>

        <div class="flex items-center justify-end mt-6">
            <?= Html::a('Cancelar', ['index'], ['class' => 'text-gray-600 hover:text-gray-800 mr-4 font-medium']) ?>
            <?= Html::submitButton('Importar XML', ['class' => 'bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition duration-300 shadow-md']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>