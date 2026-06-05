<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\contas_pagar\models\TipoDespesa;

/* @var $this yii\web\View */
/* @var $model app\modules\contas_pagar\models\TipoDespesa */
/* @var $form yii\widgets\ActiveForm */
/* @var $gruposMap array */
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden">

    <!-- Dica informativa -->
    <div class="px-6 py-4 bg-amber-50 border-b border-amber-200 flex gap-3">
        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm text-amber-800">
            <strong>Use nomes genéricos</strong> para o tipo de despesa (ex: "Compra de Mercadoria", "Aluguel", "Energia Elétrica").
            O detalhe de cada lançamento — como número de NF, mês de referência ou fornecedor — deve ser informado
            no campo <strong>Descrição</strong> da conta a pagar.
        </p>
    </div>

    <?php $form = ActiveForm::begin([
        'id'      => 'tipo-despesa-form',
        'options' => ['class' => 'p-6 space-y-5'],
        'fieldConfig' => [
            'template'     => "{label}\n{input}\n{hint}\n{error}",
            'labelOptions' => ['class' => 'block text-sm font-semibold text-gray-700 mb-1'],
            'inputOptions' => ['class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm'],
            'errorOptions' => ['class' => 'text-red-600 text-xs mt-1'],
        ],
    ]); ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

        <!-- Nome -->
        <div class="sm:col-span-2">
            <?= $form->field($model, 'nome')->textInput([
                'maxlength'   => true,
                'placeholder' => 'Ex: Aluguel, Energia Elétrica, Compra de Mercadoria',
                'class'       => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm',
            ]) ?>
        </div>

        <!-- Grupo -->
        <div>
            <?= $form->field($model, 'grupo')->dropDownList(
                $gruposMap,
                [
                    'prompt' => 'Selecione o grupo...',
                    'id'     => 'tipo-despesa-grupo',
                    'class'  => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm',
                ]
            )->hint('<span class="text-xs text-gray-500">Fixas = valor previsível e recorrente · Variáveis = valor oscila · Compras = aquisição de estoque</span>') ?>
        </div>

        <!-- Ativo -->
        <div class="flex items-center mt-6">
            <?= $form->field($model, 'ativo')->checkbox([
                'class'         => 'h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500',
                'labelOptions'  => ['class' => 'ml-2 text-sm font-medium text-gray-700'],
                'template'      => '<div class="flex items-center gap-2">{input}{label}{error}</div>',
            ]) ?>
        </div>

        <!-- Descrição interna -->
        <div class="sm:col-span-2">
            <?= $form->field($model, 'descricao')->textarea([
                'rows'        => 2,
                'placeholder' => 'Descrição interna opcional para este tipo de despesa...',
                'class'       => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm resize-none',
            ])->hint('<span class="text-xs text-gray-400">Explicação interna do tipo. Não aparece nas contas a pagar.</span>') ?>
        </div>

    </div>

    <!-- Botões -->
    <div class="flex flex-col sm:flex-row gap-3 pt-5 border-t border-gray-200 mt-5">
        <?= Html::submitButton(
            '<svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' .
            ($model->isNewRecord ? 'Salvar Tipo' : 'Salvar Alterações'),
            ['class' => 'inline-flex items-center justify-center px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg shadow transition duration-300']
        ) ?>
        <?= Html::a(
            '<svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Cancelar',
            ['index'],
            ['class' => 'inline-flex items-center justify-center px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-semibold rounded-lg transition duration-300']
        ) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
