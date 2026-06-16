<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>

<div class="produto-form">
    <?php $form = ActiveForm::begin([
        'id' => 'fornecedor-form',
        'options' => ['class' => 'space-y-6 p-4 sm:p-6 lg:p-8'],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{hint}\n{error}",
            'labelOptions' => ['class' => 'block text-sm font-medium text-gray-700 mb-2'],
            'inputOptions' => ['class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'],
            'errorOptions' => ['class' => 'text-red-600 text-sm mt-1'],
        ],
    ]); ?>

    <!-- Seção: Dados Básicos -->
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            Dados Básicos
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div class="sm:col-span-2">
                <?= $form->field($model, 'nome_fantasia')->textInput(['maxlength' => true, 'placeholder' => 'Nome fantasia do fornecedor']) ?>
            </div>

            <div>
                <?= $form->field($model, 'razao_social')->textInput(['maxlength' => true, 'placeholder' => 'Razão social (opcional)']) ?>
            </div>

            <div>
                <?= $form->field($model, 'inscricao_estadual')->textInput(['maxlength' => true, 'placeholder' => 'Inscrição Estadual (opcional)']) ?>
            </div>

            <div>
                <?= $form->field($model, 'cnpj')->textInput(['maxlength' => true, 'placeholder' => '00.000.000/0000-00']) ?>
            </div>

            <div>
                <?= $form->field($model, 'cpf')->textInput(['maxlength' => true, 'placeholder' => '000.000.000-00']) ?>
            </div>
        </div>
    </div>

    <!-- Seção: Contato -->
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
            Contato
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div>
                <?= $form->field($model, 'telefone')->textInput(['maxlength' => true, 'placeholder' => '(00) 00000-0000']) ?>
            </div>

            <div>
                <?= $form->field($model, 'email')->textInput(['maxlength' => true, 'type' => 'email', 'placeholder' => 'email@exemplo.com']) ?>
            </div>
        </div>
    </div>

    <!-- Seção: Endereço -->
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Endereço (Opcional)
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div class="sm:col-span-2">
                <?= $form->field($model, 'endereco')->textInput(['maxlength' => true, 'placeholder' => 'Rua, Avenida, etc.']) ?>
            </div>

            <div>
                <?= $form->field($model, 'numero')->textInput(['maxlength' => true, 'placeholder' => 'Número']) ?>
            </div>

            <div>
                <?= $form->field($model, 'complemento')->textInput(['maxlength' => true, 'placeholder' => 'Complemento (opcional)']) ?>
            </div>

            <div>
                <?= $form->field($model, 'bairro')->textInput(['maxlength' => true, 'placeholder' => 'Bairro']) ?>
            </div>

            <div>
                <?= $form->field($model, 'cidade')->textInput(['maxlength' => true, 'placeholder' => 'Cidade']) ?>
            </div>

            <div>
                <?= $form->field($model, 'estado')->textInput(['maxlength' => 2, 'placeholder' => 'UF', 'style' => 'text-transform: uppercase;']) ?>
            </div>

            <div>
                <?= $form->field($model, 'cep')->textInput(['maxlength' => true, 'placeholder' => '00000-000']) ?>
            </div>
        </div>
    </div>

    <!-- Seção: Observações e Status -->
    <div class="pb-2">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Informações Adicionais
        </h2>

        <div class="space-y-4">
            <?= $form->field($model, 'observacoes')->textarea(['rows' => 4, 'placeholder' => 'Observações sobre o fornecedor...']) ?>

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <?= Html::activeCheckbox($model, 'ativo', [
                            'class' => 'w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 focus:ring-2',
                            'label' => false,
                            'id' => 'ativo_checkbox',
                            'checked' => $model->isNewRecord ? true : $model->ativo
                        ]) ?>
                    </div>
                    <div class="ml-3">
                        <label for="ativo_checkbox" class="font-medium text-gray-900 text-sm sm:text-base cursor-pointer">Fornecedor Ativo</label>
                        <p class="text-xs text-gray-500">Desmarque para desativar o fornecedor no sistema</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botões de Ação -->
    <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-gray-200">
        <?= Html::submitButton(
            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' . ($model->isNewRecord ? 'Cadastrar' : 'Atualizar'),
            ['class' => 'w-full sm:flex-1 inline-flex items-center justify-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
        ) ?>
        <?= Html::a(
            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Cancelar',
            $model->isNewRecord ? ['index'] : ['view', 'id' => $model->id],
            ['class' => 'w-full sm:flex-1 inline-flex items-center justify-center px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold rounded-lg transition duration-300']
        ) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<script>
// Máscara para CPF/CNPJ
document.addEventListener('DOMContentLoaded', function() {
    const cpfInput = document.getElementById('fornecedor-cpf');
    const cnpjInput = document.getElementById('fornecedor-cnpj');
    const estadoInput = document.getElementById('fornecedor-estado');

    // Máscara CPF
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                e.target.value = value;
            }
        });
    }

    // Máscara CNPJ
    if (cnpjInput) {
        cnpjInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 14) {
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
                e.target.value = value;
            }
        });
    }

    // Estado em maiúsculas
    if (estadoInput) {
        estadoInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase().slice(0, 2);
        });
    }
});
</script>

