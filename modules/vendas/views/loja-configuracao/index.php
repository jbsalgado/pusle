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

        <!-- Card: Visibilidade e Status do Catálogo (Modo Implantação) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-amber-500 to-orange-600 px-4 sm:px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                    Status do Catálogo Online (Modo Implantação)
                </h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $model->catalogo_ativo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                    <?= $model->catalogo_ativo ? '● Online (Público)' : '○ Pausado (Em Implantação)' ?>
                </span>
            </div>
            <div class="p-4 sm:p-6 space-y-4">
                <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-amber-800">
                                <strong>Dica de Implantação:</strong> Desative a exibição do catálogo enquanto cadastra seus produtos, ajusta preços ou organiza estoque. 
                                Quando desativado, o público externo verá uma página amigável de <em>"Em Implantação / Em Breve"</em> e os produtos não serão exibidos nem acessíveis via API pública.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <?= Html::activeCheckbox($model, 'catalogo_ativo', [
                            'class' => 'sr-only peer',
                            'label' => false,
                            'id' => 'switch-catalogo-ativo'
                        ]) ?>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                        <span class="ml-3 text-sm font-bold text-gray-900">
                            Catálogo Online Habilitado para Clientes
                        </span>
                    </label>
                </div>

                <div>
                    <?= $form->field($model, 'mensagem_manutencao')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'Ex: Estamos preparando novidades e cadastrando novos itens. Em breve nossa loja estará disponível!',
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors'
                    ])->hint('Mensagem customizada exibida aos visitantes quando o catálogo estiver desativado (opcional).', ['class' => 'text-xs text-gray-500 mt-1']) ?>
                </div>
            </div>
        </div>

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

        <!-- Card: Dados de PIX -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-red-500 to-red-600 px-4 sm:px-6 py-4">
                <h2 class="text-lg font-semibold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Configuração de PIX (QR Code Estático)
                </h2>
            </div>
            <div class="p-4 sm:p-6 space-y-4">
                <p class="text-sm text-gray-600 italic">
                    Esses dados são usados para gerar o QR Code PIX nos pedidos. Forneça o nome e cidade exatamente como registrados no seu banco.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <?= $form->field($model, 'pix_chave')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Email, CPF, Telefone ou Chave Aleatória',
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors'
                        ]) ?>
                    </div>
                    <div>
                        <?= $form->field($model, 'pix_nome')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Nome do recebedor',
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors'
                        ]) ?>
                    </div>
                    <div>
                        <?= $form->field($model, 'pix_cidade')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Cidade do recebedor',
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors'
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