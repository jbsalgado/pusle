<?php

use app\modules\indicadores\models\IndCategoriasDesagregacao;
use app\modules\indicadores\models\IndDefinicoesIndicadores;
use app\modules\indicadores\models\IndFontesDados;
use app\modules\indicadores\models\IndNiveisAbrangencia;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $model app\models\IndValoresIndicadores */
/* @var $form yii\widgets\ActiveForm */

// Registrar Tailwind CSS via CDN
$this->registerJsFile('https://cdn.tailwindcss.com', ['position' => View::POS_HEAD]);

// Registrar JavaScript personalizado
$this->registerJs("
document.addEventListener('DOMContentLoaded', function() {
    // Animação de entrada dos cards
    const cards = document.querySelectorAll('.animate-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.remove('opacity-0', 'translate-y-4');
        }, index * 100);
    });

    // Atualizar informações do indicador quando selecionado
    const indicadorSelect = document.getElementById('indvaloresindicadores-id_indicador');
    if (indicadorSelect) {
        indicadorSelect.addEventListener('change', function() {
            const indicadorId = this.value;
            if (indicadorId) {
                fetch('/ind-valores-indicadores/get-indicador-info?id=' + indicadorId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const infoDiv = document.getElementById('indicador-info');
                            infoDiv.innerHTML = `
                                <div class='bg-blue-50 border-l-4 border-blue-400 p-4 rounded'>
                                    <div class='flex'>
                                        <div class='ml-3'>
                                            <p class='text-sm text-blue-700'>
                                                <strong>Nome:</strong> \${data.data.nome}<br>
                                                <strong>Unidade:</strong> \${data.data.unidade}<br>
                                                <strong>Polaridade:</strong> \${data.data.polaridade}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            `;
                            infoDiv.classList.remove('hidden');
                        }
                    });
            } else {
                document.getElementById('indicador-info').classList.add('hidden');
            }
        });
    }

    // Toggle para campos opcionais
    const toggleBtn = document.getElementById('toggle-optional');
    const optionalFields = document.getElementById('optional-fields');
    if (toggleBtn && optionalFields) {
        toggleBtn.addEventListener('click', function() {
            optionalFields.classList.toggle('hidden');
            const icon = this.querySelector('svg');
            icon.classList.toggle('rotate-180');
        });
    }
});
", \yii\web\View::POS_END);

?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 py-4 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8 animate-card opacity-0 translate-y-4 transition-all duration-500">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-full mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Cadastro de Valores</h1>
            <p class="text-lg text-gray-600">Registre os valores dos indicadores de desempenho</p>
        </div>

        <?php $form = ActiveForm::begin([
            'id' => 'ind-valores-indicadores-form',
            'options' => [
                'class' => 'space-y-6',
                'enctype' => 'multipart/form-data',
            ],
            'fieldConfig' => [
                'template' => '{label}{input}{error}',
                'labelOptions' => ['class' => 'block text-sm font-medium text-gray-700 mb-2'],
                'inputOptions' => ['class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors'],
                'errorOptions' => ['class' => 'mt-1 text-sm text-red-600'],
            ],
        ]); ?>

        <!-- Card Principal: Informações do Indicador -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-card opacity-0 translate-y-4 transition-all duration-500">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                <h2 class="text-xl font-semibold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Informações do Indicador
                </h2>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="lg:col-span-2">
                        <?= $form->field($model, 'id_indicador')->dropDownList(
                            ArrayHelper::map(
                                IndDefinicoesIndicadores::find()
                                    ->where(['ativo' => true])
                                    ->orderBy('nome_indicador')
                                    ->all(),
                                'id_indicador',
                                function($model) {
                                    return $model->cod_indicador . ' - ' . $model->nome_indicador;
                                }
                            ),
                            [
                                'prompt' => 'Selecione um indicador...',
                                'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            ]
                        )->label('Indicador <span class="text-red-500">*</span>') ?>
                        
                        <div id="indicador-info" class="hidden mt-4"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card: Valores e Localização -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-card opacity-0 translate-y-4 transition-all duration-500">
            <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4">
                <h2 class="text-xl font-semibold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Valores e Localização
                </h2>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <?= $form->field($model, 'valor')->textInput([
                            'type' => 'number',
                            'step' => 'any',
                            'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors text-lg font-semibold text-center',
                            'placeholder' => '0.00'
                        ])->label('Valor Principal <span class="text-red-500">*</span>') ?>
                    </div>
                    
                    <div>
                        <?= $form->field($model, 'numerador')->textInput([
                            'type' => 'number',
                            'step' => 'any',
                            'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                        ])->label('Numerador') ?>
                    </div>
                    
                    <div>
                        <?= $form->field($model, 'denominador')->textInput([
                            'type' => 'number',
                            'step' => 'any',
                            'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                        ])->label('Denominador') ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <?= $form->field($model, 'data_referencia')->textInput([
                            'type' => 'date',
                            'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'value' => date('Y-m-d')
                        ])->label('Data de Referência <span class="text-red-500">*</span>') ?>
                    </div>
                    
                    <div>
                        <?= $form->field($model, 'id_nivel_abrangencia')->dropDownList(
                            ArrayHelper::map(
                                IndNiveisAbrangencia::find()->orderBy('nome_nivel')->all(),
                                'id_nivel_abrangencia',
                                'nome_nivel'
                            ),
                            [
                                'prompt' => 'Selecione o nível...',
                                'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            ]
                        )->label('Nível de Abrangência <span class="text-red-500">*</span>') ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <?= $form->field($model, 'codigo_especifico_abrangencia')->textInput([
                            'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'placeholder' => 'Ex: BR, SP, 3550308'
                        ])->label('Código Específico') ?>
                    </div>
                    
                    <div>
                        <?= $form->field($model, 'localidade_especifica_nome')->textInput([
                            'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'placeholder' => 'Nome da localidade'
                        ])->label('Nome da Localidade') ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card: Campos Opcionais (Colapsável) -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-card opacity-0 translate-y-4 transition-all duration-500">
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4">
                <button type="button" id="toggle-optional" class="w-full flex items-center justify-between text-xl font-semibold text-white">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                        </svg>
                        Informações Adicionais
                    </span>
                    <svg class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            </div>
            <div id="optional-fields" class="hidden p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <?= $form->field($model, 'id_fonte_dado_especifica')->dropDownList(
                            ArrayHelper::map(
                                IndFontesDados::find()->orderBy('nome_fonte')->all(),
                                'id_fonte',
                                'nome_fonte'
                            ),
                            [
                                'prompt' => 'Selecione a fonte...',
                                'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            ]
                        )->label('Fonte de Dados') ?>
                    </div>
                    
                    <div>
                        <?= $form->field($model, 'data_coleta_dado')->textInput([
                            'type' => 'date',
                            'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                        ])->label('Data da Coleta') ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <?= $form->field($model, 'confianca_intervalo_inferior')->textInput([
                            'type' => 'number',
                            'step' => 'any',
                            'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                        ])->label('Intervalo de Confiança - Inferior') ?>
                    </div>
                    
                    <div>
                        <?= $form->field($model, 'confianca_intervalo_superior')->textInput([
                            'type' => 'number',
                            'step' => 'any',
                            'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                        ])->label('Intervalo de Confiança - Superior') ?>
                    </div>
                </div>

                <div>
                    <?= $form->field($model, 'analise_qualitativa_valor')->textarea([
                        'rows' => 4,
                        'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none',
                        'placeholder' => 'Análise qualitativa do valor, contexto, observações relevantes...'
                    ])->label('Análise Qualitativa') ?>
                </div>

                <!-- Desagregações -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Desagregações</label>
                    <div class="space-y-3">
                        <?php 
                        $categorias = IndCategoriasDesagregacao::find()->with('indOpcoesDesagregacaos')->all();
                        foreach ($categorias as $categoria): 
                        ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-2"><?= Html::encode($categoria->nome_categoria) ?></h4>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                <?php foreach ($categoria->indOpcoesDesagregacaos as $opcao): ?>
                                <label class="flex items-center space-x-2 p-2 hover:bg-gray-100 rounded">
                                    <input type="checkbox" 
                                           name="desagregacoes[]" 
                                           value="<?= $opcao->id_opcao_desagregacao ?>"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <span class="text-sm text-gray-700"><?= Html::encode($opcao->valor_opcao) ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="flex flex-col sm:flex-row gap-4 justify-end animate-card opacity-0 translate-y-4 transition-all duration-500">
            <?= Html::a('Cancelar', ['index'], [
                'class' => 'w-full sm:w-auto px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 focus:ring-2 focus:ring-gray-200 transition-colors text-center'
            ]) ?>
            
            <?= Html::submitButton('Salvar Valor', [
                'class' => 'w-full sm:w-auto px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-indigo-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all transform hover:scale-105 shadow-lg'
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>