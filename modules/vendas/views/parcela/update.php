<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

$this->title = 'Editar Parcela';
$this->params['breadcrumbs'][] = ['label' => 'Parcelas', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Parcela #' . $model->numero_parcela, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                    <p class="mt-1 text-sm text-gray-600">Edite os dados da parcela</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?= Html::a('Voltar', ['view', 'id' => $model->id], [
                        'class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300'
                    ]) ?>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Dados da Parcela</h2>
            </div>
            <div class="px-6 py-8">
                <?php $form = ActiveForm::begin(); ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?= $form->field($model, 'valor_parcela')->textInput([
                        'type' => 'number',
                        'step' => '0.01',
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                    ]) ?>

                    <?= $form->field($model, 'data_vencimento')->input('date', [
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                    ]) ?>

                    <?= $form->field($model, 'status_parcela_codigo')->dropDownList(
                        ArrayHelper::map(
                            \app\modules\vendas\models\StatusParcela::find()->all(),
                            'codigo',
                            'descricao'
                        ),
                        [
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                            'prompt' => 'Selecione o status',
                        ]
                    ) ?>

                    <?= $form->field($model, 'forma_pagamento_id')->dropDownList(
                        ['' => 'Nenhuma'] + $formasPagamento,
                        [
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                        ]
                    ) ?>
                </div>

                <?= $form->field($model, 'observacoes')->textarea([
                    'rows' => 4,
                    'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                ]) ?>

                <div class="flex gap-3 pt-6">
                    <?= Html::submitButton('Salvar', [
                        'class' => 'px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300'
                    ]) ?>
                    <?= Html::a('Cancelar', ['view', 'id' => $model->id], [
                        'class' => 'px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300'
                    ]) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>

