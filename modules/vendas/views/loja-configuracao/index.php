<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\LojaConfiguracao */

$this->title = 'Configuração da Loja';
$this->params['breadcrumbs'][] = $this->title;
?>

<!-- Tailwind CDN -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Container Principal Mobile-First -->
<div class="min-h-screen bg-gray-50 py-4 px-4 sm:px-6 lg:px-8">
    <!-- Container com largura máxima -->
    <div class="max-w-5xl mx-auto space-y-6">

        <!-- Header com botão voltar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
                    <?= Html::encode($this->title) ?>
                </h1>
                <p class="mt-1 text-sm text-gray-600">
                    Configure os dados da sua loja que aparecerão em comprovantes e relatórios
                </p>
            </div>

            <a href="<?= Url::to(['/vendas/inicio']) ?>"
                class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors duration-200 text-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Voltar
            </a>
        </div>

        <?php $form = ActiveForm::begin([
            'options' => ['class' => 'space-y-6'],
            'fieldConfig' => [
                'template' => "{label}\n{input}\n{error}",
                'labelOptions' => ['class' => 'block text-sm font-medium text-gray-700 mb-1'],
                'inputOptions' => ['class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors'],
                'errorOptions' => ['class' => 'mt-1 text-sm text-red-600'],
            ],
        ]); ?>

        <!-- Card: Dados Básicos -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-4 sm:px-6 py-4">
                <h2 class="text-lg font-semibold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Dados Básicos
                </h2>
            </div>
            <div class="p-4 sm:p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <?= $form->field($model, 'nome_loja')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Ex: Minha Loja',
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors'
                        ]) ?>
                    </div>
                    <div>
                        <?= $form->field($model, 'nome_fantasia')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Nome fantasia',
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors'
                        ]) ?>
                    </div>
                </div>
                <div>
                    <?= $form->field($model, 'razao_social')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'Razão social completa',
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors'
                    ]) ?>
                </div>
            </div>
        </div>

        <!-- Card: Documentos -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-green-500 to-green-600 px-4 sm:px-6 py-4">
                <h2 class="text-lg font-semibold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Documentos
                </h2>
            </div>
            <div class="p-4 sm:p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <?= $form->field($model, 'cpf_cnpj')->textInput([
                            'maxlength' => true,
                            'placeholder' => '00.000.000/0000-00',
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors'
                        ]) ?>
                    </div>
                    <div>
                        <?= $form->field($model, 'inscricao_estadual')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Inscrição estadual',
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors'
                        ]) ?>
                    </div>
                    <div>
                        <?= $form->field($model, 'inscricao_municipal')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Inscrição municipal',
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors'
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card: Contato -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-4 sm:px-6 py-4">
                <h2 class="text-lg font-semibold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Contato
                </h2>
            </div>
            <div class="p-4 sm:p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <?= $form->field($model, 'telefone')->textInput([
                            'maxlength' => true,
                            'placeholder' => '(00) 0000-0000',
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors'
                        ]) ?>
                    </div>
                    <div>
                        <?= $form->field($model, 'celular')->textInput([
                            'maxlength' => true,
                            'placeholder' => '(00) 00000-0000',
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors'
                        ]) ?>
                    </div>
                    <div>
                        <?= $form->field($model, 'email')->textInput([
                            'maxlength' => true,
                            'type' => 'email',
                            'placeholder' => 'contato@minhaloja.com',
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors'
                        ]) ?>
                    </div>
                    <div>
                        <?= $form->field($model, 'site')->textInput([
                            'maxlength' => true,
                            'type' => 'url',
                            'placeholder' => 'https://minhaloja.com.br',
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors'
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card: Endereço -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-4 sm:px-6 py-4">
                <h2 class="text-lg font-semibold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Endereço
                </h2>
            </div>
            <div class="p-4 sm:p-6 space-y-4">
                <!-- Linha 1: CEP, Logradouro, Número -->
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                    <div class="sm:col-span-3">
                        <?= $form->field($model, 'cep')->textInput([
                            'maxlength' => true,
                            'placeholder' => '00000-000',
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors'
                        ]) ?>
                    </div>
                    <div class="sm:col-span-7">
                        <?= $form->field($model, 'logradouro')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Rua, Avenida, etc.',
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors'
                        ]) ?>
                    </div>
                    <div class="sm:col-span-2">
                        <?= $form->field($model, 'numero')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Nº',
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors'
                        ]) ?>
                    </div>
                </div>

                <!-- Linha 2: Complemento, Bairro, Cidade, Estado -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <?= $form->field($model, 'complemento')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Apto, Sala, etc.',
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors'
                        ]) ?>
                    </div>
                    <div>
                        <?= $form->field($model, 'bairro')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Bairro',
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors'
                        ]) ?>
                    </div>
                    <div>
                        <?= $form->field($model, 'cidade')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Cidade',
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors'
                        ]) ?>
                    </div>
                    <div>
                        <?= $form->field($model, 'estado')->dropDownList([
                            '' => 'UF',
                            'AC' => 'AC',
                            'AL' => 'AL',
                            'AP' => 'AP',
                            'AM' => 'AM',
                            'BA' => 'BA',
                            'CE' => 'CE',
                            'DF' => 'DF',
                            'ES' => 'ES',
                            'GO' => 'GO',
                            'MA' => 'MA',
                            'MT' => 'MT',
                            'MS' => 'MS',
                            'MG' => 'MG',
                            'PA' => 'PA',
                            'PB' => 'PB',
                            'PR' => 'PR',
                            'PE' => 'PE',
                            'PI' => 'PI',
                            'RJ' => 'RJ',
                            'RN' => 'RN',
                            'RS' => 'RS',
                            'RO' => 'RO',
                            'RR' => 'RR',
                            'SC' => 'SC',
                            'SP' => 'SP',
                            'SE' => 'SE',
                            'TO' => 'TO'
                        ], [
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors'
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botão Salvar -->
        <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
            <?= Html::submitButton('Salvar Configuração', [
                'class' => 'w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5 active:scale-95'
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>

<style>
    /* Melhorias de acessibilidade e interatividade */
    input:focus,
    select:focus {
        outline: none;
    }

    /* Animação suave para transições */
    * {
        -webkit-tap-highlight-color: transparent;
    }

    /* Melhoria para campos obrigatórios */
    .required label:after {
        content: " *";
        color: #ef4444;
    }
</style>