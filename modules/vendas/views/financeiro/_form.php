<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\FinanceiroMensal */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="financeiro-mensal-form bg-white rounded-lg shadow-sm p-6">

    <?php $form = ActiveForm::begin(); ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <?= $form->field($model, 'mes_referencia')->textInput(['type' => 'date', 'class' => 'w-full px-3 py-2 border rounded-lg']) ?>
        </div>

        <div>
            <?= $form->field($model, 'faturamento_total')->textInput([
                'type' => 'number',
                'step' => '0.01',
                'class' => 'w-full px-3 py-2 border rounded-lg'
            ]) ?>
        </div>

        <div>
            <?= $form->field($model, 'despesas_fixas_total')->textInput([
                'type' => 'number',
                'step' => '0.01',
                'class' => 'w-full px-3 py-2 border rounded-lg'
            ]) ?>
            <p class="text-xs text-gray-500 mt-1">Aluguel, salários fixos, internet, contador, etc.</p>
        </div>

        <div>
            <?= $form->field($model, 'despesas_variaveis_total')->textInput([
                'type' => 'number',
                'step' => '0.01',
                'class' => 'w-full px-3 py-2 border rounded-lg'
            ]) ?>
            <p class="text-xs text-gray-500 mt-1">Impostos sobre nota, taxas de cartão, comissões.</p>
        </div>

        <div>
            <?= $form->field($model, 'custo_mercadoria_vendida')->textInput([
                'type' => 'number',
                'step' => '0.01',
                'class' => 'w-full px-3 py-2 border rounded-lg'
            ]) ?>
            <p class="text-xs text-gray-500 mt-1">Valor de compra dos produtos vendidos no mês.</p>
        </div>
    </div>

    <div class="form-group mt-6">
        <?= Html::submitButton('Salvar', ['class' => 'bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg']) ?>
        <?= Html::a('Cancelar', ['index'], ['class' => 'ml-2 text-gray-600 hover:text-gray-800']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>