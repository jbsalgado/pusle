<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = $model->isNewRecord ? 'Novo Colaborador' : 'Editar Colaborador';
$this->params['breadcrumbs'][] = ['label' => 'Colaboradores', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-4 px-3 sm:py-6 sm:px-4 lg:px-8">
    <div class="max-w-4xl mx-auto">
        
        <!-- Header -->
        <div class="mb-4 sm:mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                    ['index'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm w-full sm:w-auto justify-center']
                ) ?>
            </div>
        </div>

        <!-- Formulário -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            
            <?php $form = ActiveForm::begin([
                'id' => 'colaborador-form',
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
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Dados Pessoais
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    
                    <div class="sm:col-span-2">
                        <?= $form->field($model, 'nome_completo')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Nome completo do colaborador',
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
                        <?= $form->field($model, 'data_admissao')->textInput([
                            'type' => 'date',
                            'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
                        ]) ?>
                    </div>

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

            <!-- Seção: Função e Permissões -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Função e Comissões
                </h2>

                <div class="space-y-4">
                    
                    <!-- Checkboxes de Função -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Funções do Colaborador</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <?= Html::activeCheckbox($model, 'eh_vendedor', [
                                        'class' => 'w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 focus:ring-2',
                                        'label' => false,
                                        'id' => 'eh_vendedor_checkbox'
                                    ]) ?>
                                </div>
                                <div class="ml-3">
                                    <label for="eh_vendedor_checkbox" class="font-medium text-gray-900 text-sm sm:text-base cursor-pointer">É Vendedor</label>
                                    <p class="text-xs text-gray-500">Pode realizar vendas e receber comissões</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <?= Html::activeCheckbox($model, 'eh_cobrador', [
                                        'class' => 'w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 focus:ring-2',
                                        'label' => false,
                                        'id' => 'eh_cobrador_checkbox'
                                    ]) ?>
                                </div>
                                <div class="ml-3">
                                    <label for="eh_cobrador_checkbox" class="font-medium text-gray-900 text-sm sm:text-base cursor-pointer">É Cobrador</label>
                                    <p class="text-xs text-gray-500">Pode realizar cobranças e receber comissões</p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Comissões -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        
                        <div id="comissao_venda_field">
                            <?= $form->field($model, 'percentual_comissao_venda')->textInput([
                                'type' => 'number',
                                'min' => 0,
                                'max' => 100,
                                'step' => '0.01',
                                'placeholder' => '0.00',
                                'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
                            ])->hint('Percentual de 0 a 100') ?>
                        </div>

                        <div id="comissao_cobranca_field">
                            <?= $form->field($model, 'percentual_comissao_cobranca')->textInput([
                                'type' => 'number',
                                'min' => 0,
                                'max' => 100,
                                'step' => '0.01',
                                'placeholder' => '0.00',
                                'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base'
                            ])->hint('Percentual de 0 a 100') ?>
                        </div>

                    </div>

                </div>
            </div>

            <!-- Seção: Observações e Status -->
            <div class="pb-2">
                <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Informações Adicionais
                </h2>

                <div class="space-y-4">
                    
                    <?= $form->field($model, 'observacoes')->textarea([
                        'rows' => 4,
                        'placeholder' => 'Observações sobre o colaborador...',
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
                                <label for="ativo_checkbox" class="font-medium text-gray-900 text-sm sm:text-base cursor-pointer">Colaborador Ativo</label>
                                <p class="text-xs text-gray-500">Desmarque para desativar o colaborador no sistema</p>
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
            </div>

            <?php ActiveForm::end(); ?>

        </div>

    </div>
</div>

<script>
// Toggle visibilidade dos campos de comissão baseado nos checkboxes
document.addEventListener('DOMContentLoaded', function() {
    const vendedorCheck = document.getElementById('eh_vendedor_checkbox');
    const cobradorCheck = document.getElementById('eh_cobrador_checkbox');
    const comissaoVendaField = document.getElementById('comissao_venda_field');
    const comissaoCobrancaField = document.getElementById('comissao_cobranca_field');

    function toggleComissoes() {
        if (vendedorCheck.checked) {
            comissaoVendaField.style.display = 'block';
        } else {
            comissaoVendaField.style.display = 'none';
        }

        if (cobradorCheck.checked) {
            comissaoCobrancaField.style.display = 'block';
        } else {
            comissaoCobrancaField.style.display = 'none';
        }
    }

    vendedorCheck.addEventListener('change', toggleComissoes);
    cobradorCheck.addEventListener('change', toggleComissoes);

    // Executa ao carregar a página
    toggleComissoes();
});
</script>