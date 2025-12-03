<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = $model->isNewRecord ? 'Novo Cliente' : 'Editar Cliente';
$this->params['breadcrumbs'][] = ['label' => 'Clientes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-4 px-3 sm:py-6 sm:px-4 lg:px-8">
    <div class="max-w-5xl mx-auto">
        
        <!-- Header -->
        <div class="mb-4 sm:mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                    ['index'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm w-full sm:w-auto justify-center']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>Produtos',
                    ['/vendas/produto/index'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm w-full sm:w-auto justify-center']
                ) ?>
            </div>
        </div>

        <!-- Formulário -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            
            <?php $form = ActiveForm::begin([
                'id' => 'cliente-form',
                'options' => ['class' => 'space-y-6 p-4 sm:p-6 lg:p-8'],
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{hint}\n{error}",
                    'labelOptions' => ['class' => 'block text-sm font-medium text-gray-700 mb-2'],
                    'inputOptions' => ['class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'],
                    'errorOptions' => ['class' => 'text-red-600 text-sm mt-1'],
                ],
            ]); ?>

            <!-- Seção: Dados Pessoais -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Dados Pessoais
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    
                    <div class="sm:col-span-2">
                        <?= $form->field($model, 'nome_completo')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Nome completo do cliente',
                            'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
                        ]) ?>
                    </div>

                    <div>
                        <?= $form->field($model, 'cpf')->textInput([
                            'maxlength' => true,
                            'placeholder' => '00000000000',
                            'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
                        ])->hint('Apenas números, sem pontos ou traços') ?>
                    </div>

                    <div>
                        <?= $form->field($model, 'regiao_id')->dropDownList(
                            \yii\helpers\ArrayHelper::map($regioes, 'id', 'nome'),
                            [
                                'prompt' => 'Selecione uma região',
                                'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
                            ]
                        ) ?>
                    </div>

                </div>
            </div>

            <!-- Seção: Contato -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    Informações de Contato
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    
                    <div>
                        <?= $form->field($model, 'telefone')->textInput([
                            'maxlength' => true,
                            'placeholder' => '(00) 00000-0000',
                            'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
                        ]) ?>
                    </div>

                    <div>
                        <?= $form->field($model, 'email')->textInput([
                            'maxlength' => true,
                            'type' => 'email',
                            'placeholder' => 'email@exemplo.com',
                            'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
                        ]) ?>
                    </div>

                </div>
            </div>

            <!-- Seção: Endereço -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Endereço
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    
                    <div class="sm:col-span-1 lg:col-span-1">
                        <?= $form->field($model, 'endereco_cep')->textInput([
                            'maxlength' => true,
                            'placeholder' => '00000000',
                            'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
                        ])->hint('Apenas números') ?>
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3">
                        <?= $form->field($model, 'endereco_logradouro')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Rua, Avenida, etc.',
                            'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
                        ]) ?>
                    </div>

                    <div class="sm:col-span-1">
                        <?= $form->field($model, 'endereco_numero')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Nº',
                            'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
                        ]) ?>
                    </div>

                    <div class="sm:col-span-1">
                        <?= $form->field($model, 'endereco_complemento')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Apto, Bloco, etc.',
                            'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
                        ]) ?>
                    </div>

                    <div class="sm:col-span-2">
                        <?= $form->field($model, 'endereco_bairro')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Bairro',
                            'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
                        ]) ?>
                    </div>

                    <div class="sm:col-span-1">
                        <?= $form->field($model, 'endereco_cidade')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Cidade',
                            'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
                        ]) ?>
                    </div>

                    <div class="sm:col-span-1">
                        <?= $form->field($model, 'endereco_estado')->textInput([
                            'maxlength' => 2,
                            'placeholder' => 'UF',
                            'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base uppercase'
                        ])->hint('Ex: SP, RJ, MG') ?>
                    </div>

                    <div class="sm:col-span-2 lg:col-span-4">
                        <?= $form->field($model, 'ponto_referencia')->textarea([
                            'rows' => 2,
                            'placeholder' => 'Ponto de referência para localização...',
                            'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none text-sm sm:text-base'
                        ]) ?>
                    </div>

                </div>
            </div>

            <!-- Seção: Observações e Status -->
            <div class="pb-2">
                <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Informações Adicionais
                </h2>

                <div class="space-y-4">
                    
                    <?= $form->field($model, 'observacoes')->textarea([
                        'rows' => 4,
                        'placeholder' => 'Observações sobre o cliente...',
                        'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none text-sm sm:text-base'
                    ]) ?>

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
                                <label for="ativo_checkbox" class="font-medium text-gray-900 text-sm sm:text-base cursor-pointer">Cliente Ativo</label>
                                <p class="text-xs text-gray-500">Desmarque para desativar o cliente no sistema</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-gray-200">
                <?= Html::submitButton(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' . ($model->isNewRecord ? 'Cadastrar' : 'Atualizar'),
                    ['class' => 'w-full sm:flex-1 inline-flex items-center justify-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm sm:text-base']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Cancelar',
                    ['index'],
                    ['class' => 'w-full sm:flex-1 inline-flex items-center justify-center px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold rounded-lg transition duration-300 text-sm sm:text-base']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>Produtos',
                    ['/vendas/produto/index'],
                    ['class' => 'w-full sm:flex-1 inline-flex items-center justify-center px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg transition duration-300 text-sm sm:text-base']
                ) ?>
            </div>

            <?php ActiveForm::end(); ?>

        </div>

    </div>
</div>

<script>
// Busca CEP via API ViaCEP
document.addEventListener('DOMContentLoaded', function() {
    const cepInput = document.querySelector('input[name="PrestClientes[endereco_cep]"]');
    
    if (cepInput) {
        cepInput.addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.erro) {
                            document.querySelector('input[name="PrestClientes[endereco_logradouro]"]').value = data.logradouro || '';
                            document.querySelector('input[name="PrestClientes[endereco_bairro]"]').value = data.bairro || '';
                            document.querySelector('input[name="PrestClientes[endereco_cidade]"]').value = data.localidade || '';
                            document.querySelector('input[name="PrestClientes[endereco_estado]"]').value = data.uf || '';
                            document.querySelector('input[name="PrestClientes[endereco_complemento]"]').focus();
                        }
                    })
                    .catch(error => console.error('Erro ao buscar CEP:', error));
            }
        });
    }
});
</script>