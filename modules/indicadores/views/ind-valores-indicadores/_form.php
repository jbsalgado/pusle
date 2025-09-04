<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker; // Usaremos o DatePicker do Kartik-v para as datas
use kartik\select2\Select2; // Usaremos o Select2 do Kartik-v para os dropdowns
use yii\web\View; // Para registrar o CDN do Tailwind (se preferir por view)

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndValoresIndicadores */
/* @var $form yii\widgets\ActiveForm */
/* @var $indicadores array */
/* @var $niveisAbrangencia array */
/* @var $fontesDados array */

// Se você optou por registrar o Tailwind via CDN apenas nesta view:
$this->registerJsFile('https://cdn.tailwindcss.com', ['position' => View::POS_HEAD]);
?>

<div class="bg-white rounded-xl shadow-lg p-6 sm:p-8 lg:p-10 mb-8">

    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'space-y-6'], // Adiciona classes Tailwind ao formulário
        'fieldConfig' => [
            'template' => '
                <div class="mb-4">
                    {label}
                    {input}
                    {error}
                    {hint}
                </div>
            ',
            'labelOptions' => ['class' => 'block text-sm font-medium text-gray-700 mb-1'],
            'inputOptions' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2'],
            'errorOptions' => ['class' => 'mt-1 text-sm text-red-600'],
        ],
    ]); ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Indicador -->
        <?= $form->field($model, 'id_indicador')->widget(Select2::class, [
            'data' => $indicadores,
            'options' => ['placeholder' => 'Selecione um indicador...'],
            'pluginOptions' => [
                'allowClear' => true
            ],
            'theme' => Select2::THEME_KRAJEE, // Estilo para combinar com Tailwind
            'options' => ['class' => 'rounded-md shadow-sm'],
        ])->label('Indicador <span class="text-red-500">*</span>') ?>

        <!-- Data de Referência -->
        <?= $form->field($model, 'data_referencia')->widget(DatePicker::class, [
            'options' => ['placeholder' => 'Selecione a data...'],
            'pluginOptions' => [
                'autoclose' => true,
                'format' => 'yyyy-mm-dd',
                'todayHighlight' => true
            ],
            'type' => DatePicker::TYPE_INPUT,
            'pluginEvents' => [
                "changeDate" => "function(e) { /* Lógica para reatividade, se necessário */ }",
            ],
            'options' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm p-2'],
        ])->label('Data de Referência <span class="text-red-500">*</span>') ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Nível de Abrangência -->
        <?= $form->field($model, 'id_nivel_abrangencia')->widget(Select2::class, [
            'data' => $niveisAbrangencia,
            'options' => ['placeholder' => 'Selecione o nível de abrangência...'],
            'pluginOptions' => [
                'allowClear' => true
            ],
            'theme' => Select2::THEME_KRAJEE,
            'options' => ['class' => 'rounded-md shadow-sm'],
        ])->label('Nível de Abrangência <span class="text-red-500">*</span>') ?>

        <!-- Código Específico de Abrangência (Opcional) -->
        <?= $form->field($model, 'codigo_especifico_abrangencia')->textInput(['maxlength' => true]) ?>
    </div>

    <!-- Nome da Localidade Específica (Opcional) -->
    <?= $form->field($model, 'localidade_especifica_nome')->textInput(['maxlength' => true]) ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Valor Principal -->
        <?= $form->field($model, 'valor')->textInput(['type' => 'number', 'step' => 'any'])->label('Valor <span class="text-red-500">*</span>') ?>

        <!-- Numerador -->
        <?= $form->field($model, 'numerador')->textInput(['type' => 'number', 'step' => 'any']) ?>

        <!-- Denominador -->
        <?= $form->field($model, 'denominador')->textInput(['type' => 'number', 'step' => 'any']) ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Fonte do Dado Específico (Opcional) -->
        <?= $form->field($model, 'id_fonte_dado_especifica')->widget(Select2::class, [
            'data' => $fontesDados,
            'options' => ['placeholder' => 'Selecione a fonte do dado...'],
            'pluginOptions' => [
                'allowClear' => true
            ],
            'theme' => Select2::THEME_KRAJEE,
            'options' => ['class' => 'rounded-md shadow-sm'],
        ]) ?>

        <!-- Data de Coleta do Dado (Opcional) -->
        <?= $form->field($model, 'data_coleta_dado')->widget(DatePicker::class, [
            'options' => ['placeholder' => 'Selecione a data de coleta...'],
            'pluginOptions' => [
                'autoclose' => true,
                'format' => 'yyyy-mm-dd',
                'todayHighlight' => true
            ],
            'type' => DatePicker::TYPE_INPUT,
            'options' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm p-2'],
        ]) ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Intervalo de Confiança Inferior (Opcional) -->
        <?= $form->field($model, 'confianca_intervalo_inferior')->textInput(['type' => 'number', 'step' => 'any']) ?>

        <!-- Intervalo de Confiança Superior (Opcional) -->
        <?= $form->field($model, 'confianca_intervalo_superior')->textInput(['type' => 'number', 'step' => 'any']) ?>
    </div>

    <!-- Análise Qualitativa (Opcional) -->
    <?= $form->field($model, 'analise_qualitativa_valor')->textarea(['rows' => 4, 'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2']) ?>

    <div class="form-group pt-4">
        <?= Html::submitButton('Salvar Registro', ['class' => 'w-full md:w-auto px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>