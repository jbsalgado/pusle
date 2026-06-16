<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Criar Nova Loja/Filial';
$this->params['breadcrumbs'][] = ['label' => 'Lojas/Filiais', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
            <p class="mt-1 text-sm text-gray-600">Crie uma nova loja/filial. Você será automaticamente adicionado como colaborador administrador.</p>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <?php $form = ActiveForm::begin([
                'options' => ['class' => 'space-y-6'],
            ]); ?>

            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Importante:</strong> Após criar a nova loja/filial, você será automaticamente adicionado como colaborador com permissões de vendedor, cobrador e administrador.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?= $form->field($model, 'nome')->textInput([
                    'maxlength' => true,
                    'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                    'placeholder' => 'Nome da loja/filial'
                ])->label('Nome da Loja/Filial *') ?>

                <?= $form->field($model, 'username')->textInput([
                    'maxlength' => true,
                    'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                    'placeholder' => 'Nome de usuário único para login'
                ])->label('Username *') ?>

                <?= $form->field($model, 'email')->textInput([
                    'type' => 'email',
                    'maxlength' => true,
                    'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                    'placeholder' => 'email@exemplo.com'
                ])->label('Email *') ?>

                <?= $form->field($model, 'cpf')->textInput([
                    'maxlength' => true,
                    'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                    'placeholder' => '00000000000'
                ])->label('CPF *') ?>

                <?= $form->field($model, 'telefone')->textInput([
                    'maxlength' => true,
                    'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                    'placeholder' => '11999999999'
                ])->label('Telefone *') ?>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha *</label>
                    <?= Html::passwordInput('Usuario[hash_senha]', '', [
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                        'required' => true,
                        'minlength' => 6,
                        'placeholder' => 'Mínimo 6 caracteres'
                    ]) ?>
                    <p class="mt-1 text-sm text-gray-500">A senha deve ter no mínimo 6 caracteres.</p>
                </div>

                <?= $form->field($model, 'endereco')->textInput([
                    'maxlength' => true,
                    'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                    'placeholder' => 'Rua, Avenida, etc.'
                ])->label('Endereço') ?>

                <?= $form->field($model, 'bairro')->textInput([
                    'maxlength' => true,
                    'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                ])->label('Bairro') ?>

                <?= $form->field($model, 'cidade')->textInput([
                    'maxlength' => true,
                    'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                ])->label('Cidade') ?>

                <?= $form->field($model, 'estado')->textInput([
                    'maxlength' => 2,
                    'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 uppercase',
                    'placeholder' => 'SP',
                    'style' => 'text-transform: uppercase;'
                ])->label('Estado (UF)') ?>
            </div>

            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                <?= Html::a('Cancelar', ['index'], [
                    'class' => 'px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg shadow transition duration-300'
                ]) ?>
                <?= Html::submitButton('Criar Loja/Filial', [
                    'class' => 'px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow transition duration-300'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<script>
// Converte estado para maiúsculas
document.addEventListener('DOMContentLoaded', function() {
    const estadoInput = document.querySelector('input[name="Usuario[estado]"]');
    if (estadoInput) {
        estadoInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
});
</script>

