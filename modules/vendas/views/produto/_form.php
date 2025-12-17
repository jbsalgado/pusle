<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use app\modules\vendas\models\Categoria;
use app\modules\vendas\models\DadosFinanceiros;
use kartik\select2\Select2;

// Carrega dados financeiros (global ou específico do produto)
// Se não foi passado pelo controller, detecta automaticamente o ID correto da loja
if (!isset($dadosFinanceiros)) {
    $lojaId = \app\modules\vendas\models\Categoria::getLojaIdParaQuery();
    $dadosFinanceiros = DadosFinanceiros::getConfiguracaoGlobal($lojaId);
}
$temConfiguracaoEspecifica = !$model->isNewRecord && $model->dadosFinanceiros !== null;

// ✅ Exibe erros de validação do modelo de forma destacada
if ($model->hasErrors()): ?>
    <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-800 px-4 py-3 rounded-lg shadow-lg">
        <div class="flex items-start">
            <svg class="w-6 h-6 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <div class="flex-1">
                <p class="font-bold text-lg mb-2">❌ Erros de Validação:</p>
                <ul class="list-disc list-inside space-y-1 text-sm">
                    <?php foreach ($model->getErrors() as $attribute => $errors): ?>
                        <?php foreach ($errors as $error): ?>
                            <li><strong><?= $model->getAttributeLabel($attribute) ?>:</strong> <?= Html::encode($error) ?></li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="produto-form">
    <style>
        /* Mobile-first sizing for touch targets */
        .produto-form input,
        .produto-form select,
        .produto-form textarea,
        .produto-form .select2-selection {
            font-size: 16px;
            padding: 14px 16px;
            min-height: 52px;
        }
        .produto-form .select2-selection {
            display: flex;
            align-items: center;
        }
        .produto-form button {
            font-size: 16px;
            padding: 14px 16px;
            min-height: 52px;
        }
        @media (min-width: 640px) {
            .produto-form input,
            .produto-form select,
            .produto-form textarea,
            .produto-form .select2-selection {
                font-size: 15px;
                padding: 12px 14px;
                min-height: 48px;
            }
            .produto-form button {
                font-size: 15px;
                padding: 12px 14px;
                min-height: 48px;
            }
        }
    </style>

    <?php $form = ActiveForm::begin([
        'options' => [
            'enctype' => 'multipart/form-data',
            'id' => 'form-produto', // ✅ Adiciona ID explícito para debug
        ],
        'enableClientValidation' => false, // ✅ DESABILITADO: Pode estar bloqueando o submit
        'enableAjaxValidation' => false, // ✅ Desabilita validação AJAX
    ]); ?>

    <div class="space-y-4 sm:space-y-6">
        
        <!-- Categoria (Primeiro campo) -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Categoria *</label>
            <?= $form->field($model, 'categoria_id')->widget(Select2::class, [
                'data' => Categoria::getListaDropdown(),
                'options' => [
                    'placeholder' => 'Selecione ou pesquise uma categoria...',
                    'id' => 'categoria-select',
                    'class' => 'w-full'
                ],
                'pluginOptions' => [
                    'allowClear' => true,
                    'minimumInputLength' => 0,
                    'dropdownAutoWidth' => true,
                ],
                'hideSearch' => false,
            ])->label(false) ?>
        </div>

        <!-- Código de Referência -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Código de Referência</label>
            <div class="flex flex-col sm:flex-row gap-2">
                <?= $form->field($model, 'codigo_referencia')->textInput([
                    'class' => 'flex-1 px-3 py-2.5 sm:px-4 sm:py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'placeholder' => 'Ex: ELET-0000',
                    'id' => 'codigo-referencia-input'
                ])->label(false) ?>
                <button type="button" id="btn-gerar-codigo" class="w-full sm:w-auto px-4 py-2.5 sm:py-2 bg-gray-200 hover:bg-gray-300 active:bg-gray-400 text-gray-700 font-medium rounded-lg transition-colors duration-200 flex items-center justify-center gap-2" title="Gerar código automático">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span class="sm:hidden">Gerar Código</span>
                </button>
            </div>
            <p class="mt-1.5 text-xs text-gray-500">O código será gerado automaticamente ao selecionar a categoria</p>
        </div>

        <!-- Nome do Produto -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Nome do Produto *</label>
            <?= $form->field($model, 'nome')->textInput([
                'class' => 'w-full px-3 py-2.5 sm:px-4 sm:py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                'placeholder' => 'Ex: Notebook Dell Inspiron',
                'id' => 'produto-nome'
            ])->label(false) ?>
        </div>

        <!-- Descrição -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Descrição</label>
            <?= $form->field($model, 'descricao')->textarea([
                'rows' => 4,
                'class' => 'w-full px-3 py-2.5 sm:px-4 sm:py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-y',
                'placeholder' => 'Descreva as características do produto...',
                'id' => 'produto-descricao'
            ])->label(false) ?>
            <p class="mt-1.5 text-xs text-gray-500">O nome do produto será automaticamente incluído no início da descrição</p>
        </div>

        <!-- Preços, Frete e Estoque -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Preço de Custo (R$)</label>
                <?= $form->field($model, 'preco_custo')->textInput([
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => '0',
                    'class' => 'money-auto w-full px-3 py-2.5 sm:px-4 sm:py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'placeholder' => '0.00',
                    'id' => 'preco-custo',
                    'inputmode' => 'numeric',
                    'pattern' => '\d*'
                ])->label(false) ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Valor do Frete (R$)</label>
                <?= $form->field($model, 'valor_frete')->textInput([
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => '0',
                    'class' => 'money-auto w-full px-3 py-2.5 sm:px-4 sm:py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'placeholder' => '0.00',
                    'id' => 'valor-frete',
                    'inputmode' => 'numeric',
                    'pattern' => '\d*'
                ])->label(false) ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Preço de Venda (R$) *</label>
                <?= $form->field($model, 'preco_venda_sugerido')->textInput([
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => '0',
                    'class' => 'money-auto w-full px-3 py-2.5 sm:px-4 sm:py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'placeholder' => '0.00',
                    'id' => 'preco-venda',
                    'inputmode' => 'numeric',
                    'pattern' => '\d*'
                ])->label(false) ?>
            </div>
        </div>


        <!-- Campos ocultos para salvar margem e markup calculados -->
        <?= $form->field($model, 'margem_lucro_percentual')->hiddenInput(['id' => 'margem-lucro-percentual'])->label(false) ?>
        <?= $form->field($model, 'markup_percentual')->hiddenInput(['id' => 'markup-percentual'])->label(false) ?>

        <!-- ============================================
             SEÇÃO: CÁLCULOS DE PRECIFICAÇÃO
             ============================================ -->
        
        <!-- Margem e Markup (calculados automaticamente dos preços acima) -->
        <div id="margem-markup-container" class="mt-4 sm:mt-6" style="display: none;">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 sm:p-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="text-sm font-medium text-gray-700 block">Margem de Lucro</span>
                            <span class="text-xs text-gray-500">(sobre o preço de venda)</span>
                        </div>
                        <span id="margem-valor" class="text-lg sm:text-xl font-bold text-blue-600">0.00%</span>
                    </div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-3 sm:p-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="text-sm font-medium text-gray-700 block">Markup</span>
                            <span class="text-xs text-gray-500">(sobre o custo)</span>
                        </div>
                        <span id="markup-valor" class="text-lg sm:text-xl font-bold text-green-600">0.00%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================
             PRECIFICAÇÃO INTELIGENTE (MARKUP DIVISOR)
             MÉTODO RECOMENDADO: Considera todas as taxas
             ============================================ -->
        <div class="bg-gradient-to-r from-purple-50 to-blue-50 border-2 border-purple-200 rounded-lg p-4 sm:p-6 mt-4 sm:mt-6">
            <div class="flex items-start sm:items-center gap-2 sm:gap-3 mb-3 sm:mb-4">
                <div class="bg-purple-600 rounded-lg p-2 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-base sm:text-lg font-bold text-gray-800 mb-1">Precificação Inteligente (Markup Divisor)</h3>
                    <p class="text-xs sm:text-sm text-gray-600">⭐ Método recomendado: Calcula o preço considerando todas as taxas e o lucro líquido desejado.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5">
                <!-- Coluna Esquerda: Inputs (Mobile First) -->
                <div class="space-y-3 sm:space-y-4">
                    <!-- Opção: Usar configuração específica ou global -->
                    <?php if (!$model->isNewRecord): ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                        <label class="flex items-start cursor-pointer gap-2">
                            <input type="checkbox" 
                                   name="DadosFinanceiros[usar_configuracao_especifica]" 
                                   value="1"
                                   id="usar-config-especifica"
                                   <?= $temConfiguracaoEspecifica ? 'checked' : '' ?>
                                   class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 flex-shrink-0">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-700 block">
                                    Usar configuração específica para este produto
                                </span>
                                <p class="text-xs text-gray-600 mt-1">
                                    <?php if ($temConfiguracaoEspecifica): ?>
                                        <span class="text-green-600 font-medium">✓ Configuração específica ativa</span>
                                    <?php else: ?>
                                        <span class="text-gray-500">Usando configuração global da loja</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </label>
                    </div>
                    <?php endif; ?>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Taxas Fixas (%)</label>
                        <p class="text-xs text-gray-500 mb-1.5">Impostos fixos, taxas de plataforma, etc.</p>
                        <?= Html::activeTextInput($dadosFinanceiros, 'taxa_fixa_percentual', [
                            'type' => 'number',
                            'step' => '0.01',
                            'min' => '0',
                            'max' => '99.99',
                            'class' => 'w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors',
                            'placeholder' => '0.00',
                            'id' => 'taxa-fixa',
                            'name' => 'DadosFinanceiros[taxa_fixa_percentual]'
                        ]) ?>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Taxas Variáveis (%)</label>
                        <p class="text-xs text-gray-500 mb-1.5">Comissões, taxas de pagamento, etc.</p>
                        <?= Html::activeTextInput($dadosFinanceiros, 'taxa_variavel_percentual', [
                            'type' => 'number',
                            'step' => '0.01',
                            'min' => '0',
                            'max' => '99.99',
                            'class' => 'w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors',
                            'placeholder' => '0.00',
                            'id' => 'taxa-variavel',
                            'name' => 'DadosFinanceiros[taxa_variavel_percentual]'
                        ]) ?>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lucro Líquido Desejado (%)</label>
                        <p class="text-xs text-gray-500 mb-1.5">Margem líquida após todos os custos e taxas</p>
                        <?= Html::activeTextInput($dadosFinanceiros, 'lucro_liquido_percentual', [
                            'type' => 'number',
                            'step' => '0.01',
                            'min' => '0',
                            'max' => '99.99',
                            'class' => 'w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors',
                            'placeholder' => '0.00',
                            'id' => 'lucro-liquido',
                            'name' => 'DadosFinanceiros[lucro_liquido_percentual]'
                        ]) ?>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs sm:text-sm font-medium text-gray-700">Fator Divisor:</span>
                            <span id="fator-divisor-valor" class="text-base sm:text-lg font-bold text-blue-600">1.0000</span>
                        </div>
                        <p class="text-xs text-gray-600">Fator = 1 - ((Fixas + Variáveis + Lucro) / 100)</p>
                    </div>

                    <button type="button" 
                            id="btn-calcular-markup-divisor" 
                            class="w-full px-4 py-2.5 bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white font-semibold rounded-lg transition-colors duration-200 text-sm flex items-center justify-center gap-2 shadow-md hover:shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Calcular Preço de Venda
                    </button>
                </div>

                <!-- Coluna Direita: Resultados e A Prova Real (Mobile First) -->
                <div class="space-y-3 sm:space-y-4">
                    <!-- Preço Sugerido -->
                    <div class="bg-green-50 border-2 border-green-300 rounded-lg p-3 sm:p-4">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1 sm:gap-2 mb-1 sm:mb-2">
                            <span class="text-xs sm:text-sm font-medium text-gray-700">Preço de Venda Sugerido:</span>
                            <span id="preco-sugerido-valor" class="text-xl sm:text-2xl font-bold text-green-600">R$ 0,00</span>
                        </div>
                        <p class="text-xs text-gray-600">Preço = Custo / Fator Divisor</p>
                    </div>

                    <!-- A Prova Real -->
                    <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4">
                        <h4 class="text-xs sm:text-sm font-bold text-gray-800 mb-2 sm:mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            A Prova Real
                        </h4>
                        <div class="overflow-x-auto -mx-3 sm:mx-0">
                            <table class="w-full text-xs sm:text-sm min-w-full">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left py-1.5 sm:py-2 px-2 font-medium text-gray-700">Item</th>
                                        <th class="text-right py-1.5 sm:py-2 px-2 font-medium text-gray-700">Valor</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600">
                                    <tr class="border-b border-gray-100">
                                        <td class="py-1.5 sm:py-2 px-2">Preço de Venda</td>
                                        <td id="prova-preco-venda" class="text-right py-1.5 sm:py-2 px-2 font-medium">R$ 0,00</td>
                                    </tr>
                                    <tr class="border-b border-gray-100">
                                        <td class="py-1.5 sm:py-2 px-2 text-red-600">(-) Taxas Fixas</td>
                                        <td id="prova-taxas-fixas" class="text-right py-1.5 sm:py-2 px-2 text-red-600">R$ 0,00</td>
                                    </tr>
                                    <tr class="border-b border-gray-100">
                                        <td class="py-1.5 sm:py-2 px-2 text-red-600">(-) Taxas Variáveis</td>
                                        <td id="prova-taxas-variaveis" class="text-right py-1.5 sm:py-2 px-2 text-red-600">R$ 0,00</td>
                                    </tr>
                                    <tr class="border-b border-gray-100">
                                        <td class="py-1.5 sm:py-2 px-2 text-red-600">(-) Custo Total</td>
                                        <td id="prova-custo-total" class="text-right py-1.5 sm:py-2 px-2 text-red-600">R$ 0,00</td>
                                    </tr>
                                    <tr class="bg-gray-50 font-bold">
                                        <td class="py-1.5 sm:py-2 px-2">(=) Lucro Real</td>
                                        <td id="prova-lucro-real" class="text-right py-1.5 sm:py-2 px-2 text-green-600">R$ 0,00</td>
                                    </tr>
                                    <tr class="bg-gray-50">
                                        <td class="py-1.5 sm:py-2 px-2 text-xs">Margem Real</td>
                                        <td id="prova-margem-real" class="text-right py-1.5 sm:py-2 px-2 text-green-600 text-xs">0.00%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Alerta de Prejuízo -->
                    <div id="alerta-prejuizo" class="hidden bg-red-50 border-2 border-red-300 rounded-lg p-2.5 sm:p-3">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-xs sm:text-sm font-bold text-red-800">⚠️ ATENÇÃO: Prejuízo Detectado!</p>
                                <p id="mensagem-prejuizo" class="text-xs text-red-700 mt-1"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================
             CALCULADORA SIMPLES (ALTERNATIVA RÁPIDA)
             Método simplificado: Apenas margem, sem taxas
             ============================================ -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 sm:p-4 mt-4 sm:mt-6">
            <div class="flex items-start gap-2 mb-3">
                <svg class="w-5 h-5 text-gray-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <div class="flex-1">
                    <div class="flex items-start sm:items-center gap-2 mb-2">
                        <input type="checkbox" id="calcular-por-margem" class="mt-1 sm:mt-0 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 flex-shrink-0">
                        <label for="calcular-por-margem" class="text-sm sm:text-base font-medium text-gray-700 cursor-pointer">
                            Calculadora Rápida: Preço por Margem Simples
                        </label>
                    </div>
                    <p class="text-xs text-gray-600 ml-6">
                        Método simplificado que calcula apenas pela margem, sem considerar taxas. 
                        <span class="text-purple-600 font-medium">Recomendado: Use a Precificação Inteligente acima para cálculos mais precisos.</span>
                    </p>
                </div>
            </div>
            <div id="margem-desejada-container" class="hidden mt-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Margem Desejada (%)</label>
                        <input type="number" 
                               id="margem-desejada" 
                               step="0.01" 
                               min="0" 
                               max="99.99"
                               class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               placeholder="Ex: 30 para 30%">
                    </div>
                    <div class="flex items-end">
                        <button type="button" 
                                id="btn-calcular-preco" 
                                class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold rounded-lg transition-colors duration-200 text-sm">
                            Calcular Preço
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================
             PROMOÇÕES E OFERTAS
             ============================================ -->
        <div class="bg-gradient-to-r from-red-50 to-orange-50 border-2 border-red-200 rounded-lg p-4 sm:p-6 mt-4 sm:mt-6">
            <div class="flex items-start sm:items-center gap-2 sm:gap-3 mb-3 sm:mb-4">
                <div class="bg-red-600 rounded-lg p-2 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-base sm:text-lg font-bold text-gray-800 mb-1">Promoções e Ofertas</h3>
                    <p class="text-xs sm:text-sm text-gray-600">Configure preços promocionais com período de validade</p>
                </div>
            </div>

            <div class="space-y-4">
                <!-- Status da Promoção (se já existe) -->
                <?php if (!$model->isNewRecord && !empty($model->preco_promocional)): ?>
                    <?php
                    $emPromocao = $model->emPromocao;
                    $agora = new \DateTime();
                    $inicio = $model->data_inicio_promocao ? new \DateTime($model->data_inicio_promocao) : null;
                    $fim = $model->data_fim_promocao ? new \DateTime($model->data_fim_promocao) : null;
                    
                    $statusPromocao = 'inativa';
                    $statusText = 'Inativa';
                    $statusClasses = 'border-gray-200 bg-gray-100 text-gray-800 border-gray-300';
                    
                    if ($inicio && $fim) {
                        if ($agora < $inicio) {
                            $statusPromocao = 'agendada';
                            $statusText = 'Agendada';
                            $statusClasses = 'border-blue-200 bg-blue-100 text-blue-800 border-blue-300';
                        } elseif ($agora >= $inicio && $agora <= $fim) {
                            $statusPromocao = 'ativa';
                            $statusText = 'Em Promoção';
                            $statusClasses = 'border-green-200 bg-green-100 text-green-800 border-green-300';
                        } else {
                            $statusPromocao = 'expirada';
                            $statusText = 'Expirada';
                            $statusClasses = 'border-red-200 bg-red-100 text-red-800 border-red-300';
                        }
                    }
                    ?>
                    <div class="bg-white border border-gray-200 rounded-lg p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs sm:text-sm font-medium text-gray-700">Status da Promoção:</span>
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?= $statusClasses ?>">
                                <?= $statusText ?>
                            </span>
                        </div>
                        <?php if ($inicio && $fim): ?>
                            <div class="text-xs text-gray-600">
                                <span class="font-medium">Período:</span> 
                                <?= Yii::$app->formatter->asDate($model->data_inicio_promocao, 'dd/MM/yyyy') ?> 
                                até 
                                <?= Yii::$app->formatter->asDate($model->data_fim_promocao, 'dd/MM/yyyy') ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($emPromocao): ?>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-xs text-gray-600">Desconto:</span>
                                <span class="text-base font-bold text-red-600" id="desconto-percentual-display">
                                    <?= number_format($model->descontoPromocional, 2, ',', '.') ?>%
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Preço Promocional -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Preço Promocional (R$)</label>
                    <p class="text-xs text-gray-500 mb-1.5">Preço com desconto durante o período da promoção</p>
                    <?= $form->field($model, 'preco_promocional')->textInput([
                        'type' => 'number',
                        'step' => '0.01',
                        'min' => '0',
                        'class' => 'w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors',
                        'placeholder' => '0.00',
                        'id' => 'preco-promocional'
                    ])->label(false) ?>
                    <p class="text-xs text-gray-500 mt-1">Deve ser menor que o preço de venda normal</p>
                </div>

                <!-- Datas da Promoção -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Início</label>
                        <?php
                        $dataInicio = $model->data_inicio_promocao;
                        if ($dataInicio && !empty($dataInicio)) {
                            try {
                                $dateTime = new \DateTime($dataInicio);
                                $dataInicio = $dateTime->format('Y-m-d\TH:i');
                            } catch (\Exception $e) {
                                $dataInicio = '';
                            }
                        } else {
                            $dataInicio = '';
                        }
                        ?>
                        <?= $form->field($model, 'data_inicio_promocao')->textInput([
                            'type' => 'datetime-local',
                            'value' => $dataInicio,
                            'class' => 'w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors',
                            'id' => 'data-inicio-promocao'
                        ])->label(false) ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Fim</label>
                        <?php
                        $dataFim = $model->data_fim_promocao;
                        if ($dataFim && !empty($dataFim)) {
                            try {
                                $dateTime = new \DateTime($dataFim);
                                $dataFim = $dateTime->format('Y-m-d\TH:i');
                            } catch (\Exception $e) {
                                $dataFim = '';
                            }
                        } else {
                            $dataFim = '';
                        }
                        ?>
                        <?= $form->field($model, 'data_fim_promocao')->textInput([
                            'type' => 'datetime-local',
                            'value' => $dataFim,
                            'class' => 'w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors',
                            'id' => 'data-fim-promocao'
                        ])->label(false) ?>
                    </div>
                </div>

                <!-- Preview do Desconto e Preços -->
                <div id="promocao-preview" class="hidden bg-white border border-gray-200 rounded-lg p-3 sm:p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-2.5 sm:p-3">
                            <div class="text-xs text-gray-600 mb-1">Preço Normal</div>
                            <div id="preco-normal-display" class="text-base sm:text-lg font-semibold text-gray-700 line-through">R$ 0,00</div>
                        </div>
                        <div class="bg-red-50 border-2 border-red-300 rounded-lg p-2.5 sm:p-3">
                            <div class="text-xs text-gray-600 mb-1">Preço Promocional</div>
                            <div id="preco-promocional-display" class="text-lg sm:text-xl font-bold text-red-600">R$ 0,00</div>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Desconto:</span>
                            <span id="desconto-percentual" class="text-lg font-bold text-red-600">0%</span>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-xs text-gray-600">Economia:</span>
                            <span id="economia-valor" class="text-sm font-semibold text-green-600">R$ 0,00</span>
                        </div>
                    </div>
                </div>

                <!-- Alerta de Prejuízo na Promoção -->
                <div id="alerta-prejuizo-promocao" class="hidden bg-red-50 border-2 border-red-300 rounded-lg p-2.5 sm:p-3">
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div class="flex-1">
                            <p class="text-xs sm:text-sm font-bold text-red-800">⚠️ ATENÇÃO: Prejuízo Detectado na Promoção!</p>
                            <p id="mensagem-prejuizo-promocao" class="text-xs text-red-700 mt-1"></p>
                            <p class="text-xs text-red-600 mt-2 font-medium">💡 Dica: Ajuste o preço promocional ou reduza as taxas para evitar prejuízo.</p>
                        </div>
                    </div>
                </div>

                <!-- Tag de Promoção (Badge) -->
                <div id="tag-promocao-container" class="hidden">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-xs sm:text-sm font-medium text-yellow-800">
                                    Este produto terá uma tag de <span class="font-bold">"PROMOÇÃO"</span> visível quando a promoção estiver ativa.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estoque -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Estoque Atual (un)</label>
                <?= $form->field($model, 'estoque_atual')->textInput([
                    'type' => 'number',
                    'min' => '0',
                    'step' => '1',
                    'class' => 'w-full px-3 py-2.5 sm:px-4 sm:py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'placeholder' => '0',
                    'id' => 'produto-estoque-atual'
                ])->label(false) ?>
                <p class="mt-1 text-xs text-gray-500">Quantidade atual em estoque</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Estoque Mínimo (un)</label>
                <?= $form->field($model, 'estoque_minimo')->textInput([
                    'type' => 'number',
                    'min' => '0',
                    'step' => '1',
                    'class' => 'w-full px-3 py-2.5 sm:px-4 sm:py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'placeholder' => '10',
                    'id' => 'produto-estoque-minimo'
                ])->label(false) ?>
                <p class="mt-1 text-xs text-gray-500">Alerta quando estoque ficar abaixo deste valor</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Ponto de Corte (un)</label>
                <?= $form->field($model, 'ponto_corte')->textInput([
                    'type' => 'number',
                    'min' => '0',
                    'step' => '1',
                    'class' => 'w-full px-3 py-2.5 sm:px-4 sm:py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'placeholder' => '5',
                    'id' => 'produto-ponto-corte'
                ])->label(false) ?>
                <p class="mt-1 text-xs text-gray-500">Resuprimento urgente quando chegar neste valor</p>
            </div>
        </div>
        
        <!-- Localização -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Localização</label>
            <?= $form->field($model, 'localizacao')->textInput([
                'class' => 'w-full px-3 py-2.5 sm:px-4 sm:py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                'placeholder' => 'Ex: Prateleira A3, Estoque 2, etc.',
                'maxlength' => 30
            ])->label(false) ?>
            <p class="mt-1.5 text-xs text-gray-500">Onde o produto está armazenado fisicamente</p>
        </div>
        
        <!-- Aviso sobre ponto de corte -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 sm:p-4 mt-2">
            <p class="text-xs sm:text-sm text-yellow-800 leading-relaxed">
                <strong>💡 Dica:</strong> O ponto de corte deve ser maior ou igual ao estoque mínimo. 
                Quando o estoque atual chegar ao ponto de corte, é recomendado fazer resuprimento urgente.
            </p>
        </div>

        <!-- Upload de Fotos -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Fotos do Produto</label>
            
            <!-- Botões de ação -->
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 mb-3 sm:mb-4">
                <button type="button" id="btn-camera" class="w-full sm:flex-1 px-4 py-2.5 sm:py-2 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-semibold rounded-lg transition-colors duration-200 flex items-center justify-center gap-2 text-sm sm:text-base">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Capturar da Câmera</span>
                </button>
                <label class="w-full sm:flex-1 cursor-pointer">
                    <span class="w-full px-4 py-2.5 sm:py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold rounded-lg inline-block transition-colors duration-200 flex items-center justify-center gap-2 text-sm sm:text-base">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <span>Fazer Upload</span>
                    </span>
                    <input type="file" name="fotos[]" multiple accept="image/*" class="hidden" id="fotos-input">
                </label>
            </div>
            
            <!-- Área de preview -->
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 sm:p-6 text-center hover:border-blue-500 transition-colors duration-200">
                <svg class="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <p class="text-xs text-gray-500 mt-2">PNG, JPG, JPEG até 5MB cada</p>
            </div>
            <div id="preview-container" class="mt-3 sm:mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4"></div>
        </div>
        
        <!-- Modal da Câmera -->
        <div id="camera-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-75 flex items-center justify-center p-2 sm:p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-2 sm:mx-0">
                <div class="p-3 sm:p-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900">Capturar Foto</h3>
                    <button type="button" id="close-camera-modal" class="text-gray-400 hover:text-gray-600 active:text-gray-800 transition-colors p-1">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-3 sm:p-4">
                    <div class="relative bg-black rounded-lg overflow-hidden" style="aspect-ratio: 4/3;">
                        <video id="camera-video" autoplay playsinline class="w-full h-full object-cover"></video>
                        <canvas id="camera-canvas" class="hidden"></canvas>
                        <div id="camera-preview" class="hidden absolute inset-0">
                            <img id="captured-image" class="w-full h-full object-cover" alt="Foto capturada">
                        </div>
                    </div>
                    <div class="mt-3 sm:mt-4 flex flex-col sm:flex-row gap-2 sm:gap-3 justify-center">
                        <button type="button" id="btn-capture" class="w-full sm:w-auto px-4 sm:px-6 py-2.5 sm:py-3 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-semibold rounded-lg transition-colors duration-200 flex items-center justify-center gap-2 text-sm sm:text-base">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Capturar Foto</span>
                        </button>
                        <button type="button" id="btn-retake" class="hidden w-full sm:w-auto px-4 sm:px-6 py-2.5 sm:py-3 bg-gray-600 hover:bg-gray-700 active:bg-gray-800 text-white font-semibold rounded-lg transition-colors duration-200 flex items-center justify-center gap-2 text-sm sm:text-base">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span>Tirar Outra</span>
                        </button>
                        <button type="button" id="btn-use-photo" class="hidden w-full sm:w-auto px-4 sm:px-6 py-2.5 sm:py-3 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold rounded-lg transition-colors duration-200 flex items-center justify-center gap-2 text-sm sm:text-base">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Usar Esta Foto</span>
                        </button>
                    </div>
                    <div id="camera-error" class="hidden mt-3 sm:mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-800 text-xs sm:text-sm"></div>
                </div>
            </div>
        </div>

        <!-- Fotos Existentes (se estiver editando) -->
        <?php if (!$model->isNewRecord && $model->fotos): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Fotos Cadastradas</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    <?php foreach ($model->fotos as $foto): ?>
                        <div class="relative group">
                            <img src="<?= Yii::getAlias('@web') . '/' . $foto->arquivo_path ?>" 
                                 class="w-full h-32 object-cover rounded-lg"
                                 alt="<?= Html::encode($foto->arquivo_nome) ?>">
                            
                            <?php if ($foto->eh_principal): ?>
                                <span class="absolute top-2 left-2 px-2 py-1 bg-blue-600 text-white text-xs font-semibold rounded">
                                    Principal
                                </span>
                            <?php else: ?>
                                <?php
                                    $redirectTo = !$model->isNewRecord ? 'update' : 'view';
                                    $setPrincipalUrl = Url::to(['set-foto-principal', 'id' => $foto->id, 'redirect' => $redirectTo]);
                                ?>
                                <?= Html::a('Definir Principal', $setPrincipalUrl, [
                                    'class' => 'absolute top-2 left-2 px-2 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300',
                                    'data-method' => 'post'
                                ]) ?>
                            <?php endif; ?>

                            <?php
                                $redirectTo = !$model->isNewRecord ? 'update' : 'view';
                                $deleteUrl = Url::to(['delete-foto', 'id' => $foto->id, 'redirect' => $redirectTo]);
                            ?>
                            <?= Html::a('✕', $deleteUrl, [
                                'class' => 'absolute top-2 right-2 w-6 h-6 flex items-center justify-center bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300',
                                'data' => [
                                    'confirm' => $foto->eh_principal ? 'Esta é a foto principal. Ao excluir, outra foto será definida como principal automaticamente. Deseja continuar?' : 'Tem certeza que deseja excluir esta foto?',
                                    'method' => 'post',
                                ],
                            ]) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Status Ativo -->
        <div class="flex items-center">
            <label class="flex items-center cursor-pointer">
                <?= Html::activeCheckbox($model, 'ativo', [
                    'class' => 'w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500',
                    'label' => null
                ]) ?>
                <span class="ml-2 text-sm font-medium text-gray-700">Produto Ativo</span>
            </label>
        </div>

        <!-- Botões -->
        <div class="flex flex-col sm:flex-row gap-3 pt-4 sm:pt-6 border-t border-gray-200">
            <?= Html::submitButton(
                $model->isNewRecord 
                    ? '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Cadastrar' 
                    : '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Salvar',
                [
                    'class' => 'w-full sm:flex-1 inline-flex items-center justify-center px-6 py-3 sm:py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold rounded-lg transition-colors duration-200 text-sm sm:text-base',
                    'id' => 'btn-salvar-produto',
                    'onclick' => 'console.log("🔍 Botão clicado via onclick"); return true;'
                ]
            ) ?>
            <?= Html::a('Cancelar', ['index'], 
                ['class' => 'w-full sm:flex-1 text-center px-6 py-3 sm:py-2.5 bg-gray-300 hover:bg-gray-400 active:bg-gray-500 text-gray-700 font-semibold rounded-lg transition-colors duration-200 text-sm sm:text-base']) ?>
        </div>

    </div>

    <?php ActiveForm::end(); ?>

</div>

<script>
// 🔍 DEBUG IMEDIATO: Executa antes do DOMContentLoaded
console.log('🔍 Script carregado');

// Calcular margem e markup em tempo real
document.addEventListener('DOMContentLoaded', function() {
    /**
     * Mascara de moeda: ao digitar números, desloca para esquerda formando os centavos.
     * Ex: 1 -> 0.01, 10 -> 0.10, 100 -> 1.00
     */
    function formatMoneyInput(input) {
        const digits = (input.value || '').replace(/\D/g, '');
        const padded = digits.padStart(3, '0'); // garante pelo menos 0.00
        const intPart = padded.slice(0, -2).replace(/^0+(?=\d)/, '') || '0';
        const decPart = padded.slice(-2);
        input.value = `${intPart}.${decPart}`;
    }

    // Aplica a máscara aos campos de dinheiro
    document.querySelectorAll('.money-auto').forEach((input) => {
        formatMoneyInput(input);
        input.addEventListener('input', () => formatMoneyInput(input));
        input.addEventListener('blur', () => formatMoneyInput(input));
    });
    console.log('🔍 DOMContentLoaded executado');
    
    // Geração automática de código de referência
    const categoriaSelect = document.getElementById('categoria-select');
    const codigoReferenciaInput = document.getElementById('codigo-referencia-input');
    const btnGerarCodigo = document.getElementById('btn-gerar-codigo');
    let codigoReferenciaTimeout = null;
    let codigoReferenciaOriginal = ''; // Armazena o código original gerado
    
    /**
     * Gera código de referência baseado na categoria
     */
    async function gerarCodigoReferencia(categoriaId, forceGenerate = false) {
        if (!categoriaId || !codigoReferenciaInput) {
            return;
        }
        
        // Só gera se o campo estiver vazio ou se forçado
        if (codigoReferenciaInput.value && !forceGenerate) {
            return;
        }
        
        try {
            // Mostra indicador de carregamento
            codigoReferenciaInput.disabled = true;
            codigoReferenciaInput.value = 'Gerando...';
            
            const response = await fetch('<?= Url::to(['gerar-codigo-referencia']) ?>?categoria_id=' + categoriaId);
            const data = await response.json();
            
            if (data.success && data.codigo) {
                codigoReferenciaInput.value = data.codigo;
                codigoReferenciaOriginal = data.codigo; // Armazena o código original
                // Feedback visual
                codigoReferenciaInput.classList.remove('border-red-500', 'bg-red-50');
                codigoReferenciaInput.classList.add('bg-green-50', 'border-green-500');
                setTimeout(() => {
                    codigoReferenciaInput.classList.remove('bg-green-50', 'border-green-500');
                }, 2000);
                // Remove mensagem de erro se existir
                removeCodigoReferenciaError();
            } else {
                codigoReferenciaInput.value = '';
            }
        } catch (error) {
            console.error('Erro ao gerar código de referência:', error);
            codigoReferenciaInput.value = '';
        } finally {
            codigoReferenciaInput.disabled = false;
        }
    }
    
    /**
     * Verifica se o código de referência é único
     */
    async function verificarCodigoReferenciaUnico(codigo) {
        if (!codigo || !codigoReferenciaInput) {
            return;
        }
        
        // Se for o código original gerado, não precisa verificar
        if (codigo === codigoReferenciaOriginal) {
            removeCodigoReferenciaError();
            codigoReferenciaInput.classList.remove('border-red-500', 'bg-red-50');
            return;
        }
        
        try {
            const produtoId = '<?= $model->isNewRecord ? "" : $model->id ?>';
            const url = '<?= Url::to(['verificar-codigo-referencia']) ?>?codigo=' + encodeURIComponent(codigo) + 
                       (produtoId ? '&produto_id=' + produtoId : '');
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success) {
                if (data.disponivel) {
                    // Código disponível
                    codigoReferenciaInput.classList.remove('border-red-500', 'bg-red-50');
                    codigoReferenciaInput.classList.add('border-green-500', 'bg-green-50');
                    removeCodigoReferenciaError();
                } else {
                    // Código já existe
                    codigoReferenciaInput.classList.remove('border-green-500', 'bg-green-50');
                    codigoReferenciaInput.classList.add('border-red-500', 'bg-red-50');
                    showCodigoReferenciaError(data.message);
                }
            }
        } catch (error) {
            console.error('Erro ao verificar código de referência:', error);
        }
    }
    
    /**
     * Mostra mensagem de erro abaixo do campo
     */
    function showCodigoReferenciaError(message) {
        removeCodigoReferenciaError();
        
        const errorDiv = document.createElement('div');
        errorDiv.id = 'codigo-referencia-error';
        errorDiv.className = 'mt-1 text-xs text-red-600';
        errorDiv.textContent = message;
        
        const inputContainer = codigoReferenciaInput.closest('.field');
        if (inputContainer) {
            inputContainer.appendChild(errorDiv);
        }
    }
    
    /**
     * Remove mensagem de erro
     */
    function removeCodigoReferenciaError() {
        const errorDiv = document.getElementById('codigo-referencia-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
    
    // Gera código quando categoria é selecionada (apenas em criação)
    if (categoriaSelect && codigoReferenciaInput) {
        const isNewRecord = <?= $model->isNewRecord ? 'true' : 'false' ?>;
        
        if (isNewRecord) {
            // Gera código automaticamente se a categoria já vier pré-preenchida
            const categoriaPreenchida = categoriaSelect.value;
            if (categoriaPreenchida && !codigoReferenciaInput.value) {
                gerarCodigoReferencia(categoriaPreenchida);
            }
            
            categoriaSelect.addEventListener('change', function() {
                const categoriaId = this.value;
                if (categoriaId) {
                    gerarCodigoReferencia(categoriaId);
                } else {
                    codigoReferenciaInput.value = '';
                }
            });
        }
        
        // Botão para gerar código manualmente
        if (btnGerarCodigo) {
            btnGerarCodigo.addEventListener('click', function() {
                const categoriaId = categoriaSelect.value;
                if (categoriaId) {
                    gerarCodigoReferencia(categoriaId, true);
                } else {
                    alert('Por favor, selecione uma categoria primeiro.');
                }
            });
        }
        
        // Validação em tempo real quando o usuário digita o código
        if (codigoReferenciaInput) {
            codigoReferenciaInput.addEventListener('input', function() {
                const codigo = this.value.trim();
                
                // Limpa timeout anterior
                if (codigoReferenciaTimeout) {
                    clearTimeout(codigoReferenciaTimeout);
                }
                
                // Remove feedback visual anterior
                this.classList.remove('border-red-500', 'bg-red-50', 'border-green-500', 'bg-green-50');
                removeCodigoReferenciaError();
                
                // Se o campo estiver vazio, não valida
                if (!codigo) {
                    return;
                }
                
                // Aguarda 500ms após o usuário parar de digitar para validar
                codigoReferenciaTimeout = setTimeout(() => {
                    verificarCodigoReferenciaUnico(codigo);
                }, 500);
            });
            
            // Valida ao perder o foco também
            codigoReferenciaInput.addEventListener('blur', function() {
                const codigo = this.value.trim();
                if (codigo) {
                    verificarCodigoReferenciaUnico(codigo);
                }
            });
        }
    }
    
    // Concatenação automática do nome do produto na descrição
    const produtoNomeInput = document.getElementById('produto-nome');
    const produtoDescricaoInput = document.getElementById('produto-descricao');
    let isUpdatingDescricao = false; // Flag para evitar loops
    let nomeAnterior = produtoNomeInput ? produtoNomeInput.value.trim() : ''; // Armazena o nome anterior
    let descricaoUsuarioOriginal = ''; // Armazena apenas a parte do usuário (sem o nome)
    
    /**
     * Extrai a parte do usuário da descrição removendo o nome do produto
     * Esta função remove o nome do início da descrição, considerando variações
     */
    function extrairParteUsuario(nome, descricao) {
        if (!nome || !descricao) {
            return descricao || '';
        }
        
        // Remove espaços extras
        const descricaoTrim = descricao.trim();
        const nomeTrim = nome.trim();
        
        if (!nomeTrim) {
            return descricaoTrim;
        }
        
        // Caso 1: Descrição começa com "NOME - "
        const prefixoComHifen = nomeTrim + ' - ';
        if (descricaoTrim.startsWith(prefixoComHifen)) {
            return descricaoTrim.substring(prefixoComHifen.length);
        }
        
        // Caso 2: Descrição é exatamente igual ao nome
        if (descricaoTrim === nomeTrim) {
            return '';
        }
        
        // Caso 3: Descrição começa apenas com o nome (sem " - ")
        if (descricaoTrim.startsWith(nomeTrim)) {
            const resto = descricaoTrim.substring(nomeTrim.length);
            // Remove espaços, hífens e caracteres separadores do início
            return resto.replace(/^[\s\-–—]+/, '');
        }
        
        // Caso 4: Descrição não começa com o nome - retorna tudo como parte do usuário
        return descricaoTrim;
    }
    
    /**
     * Remove o nome da descrição usando o nome ANTERIOR (para quando o nome muda)
     * Retorna apenas a parte do usuário, removendo qualquer ocorrência do nome antigo
     */
    function limparNomeDaDescricao(nomeAntigo, descricao) {
        if (!nomeAntigo || !descricao) {
            return descricao || '';
        }
        
        let resultado = descricao.trim();
        const nomeTrim = nomeAntigo.trim();
        
        if (!nomeTrim) {
            return resultado;
        }
        
        // Escapa caracteres especiais para regex
        const nomeEscapado = nomeTrim.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        
        // Remove do início: "NOME" ou "NOME - " ou "NOME -" etc
        resultado = resultado.replace(new RegExp('^' + nomeEscapado + '\\s*-?\\s*', 'i'), '');
        
        // Se ainda há conteúdo, pode ter ocorrências no meio ou fim - vamos tentar limpar
        // Remove ocorrências intermediárias: " - NOME - " ou " - NOME"
        resultado = resultado.replace(new RegExp('\\s*-\\s*' + nomeEscapado + '(\\s*-\\s*|\\s*$)', 'gi'), '');
        
        // Limpa múltiplos espaços, hífens duplicados e espaços nas extremidades
        resultado = resultado.replace(/\s+/g, ' ').replace(/\s*-\s*-\s*/g, ' - ').trim();
        
        return resultado;
    }
    
    /**
     * Atualiza a descrição concatenando nome + ' - ' + descrição do usuário
     * IMPORTANTE: Sempre limpa a descrição completamente antes de reconstruir
     */
    function atualizarDescricaoComNome() {
        if (!produtoNomeInput || !produtoDescricaoInput || isUpdatingDescricao) {
            return;
        }
        
        isUpdatingDescricao = true;
        
        const nomeAtual = produtoNomeInput.value.trim();
        const descricaoAtual = produtoDescricaoInput.value.trim();
        
        // Se não há nome, mantém a descrição como está
        if (!nomeAtual) {
            isUpdatingDescricao = false;
            return;
        }
        
        // Detecta se o nome mudou
        const nomeMudou = nomeAnterior && nomeAnterior !== nomeAtual;
        
        let parteUsuario = '';
        
        if (nomeMudou) {
            // Nome mudou: usa o nome ANTERIOR para extrair a parte do usuário
            // Isso é crítico porque a descrição atual pode ter o nome antigo, não o novo
            const parteExtraida = extrairParteUsuario(nomeAnterior, descricaoAtual);
            
            // Limpa qualquer ocorrência remanescente do nome antigo
            parteUsuario = limparNomeDaDescricao(nomeAnterior, parteExtraida);
            
            // Se a parte extraída ainda contém o nome antigo ou parece duplicada, reseta
            // Detecta duplicações: se a parte extraída é muito longa ou contém o nome antigo
            if (parteUsuario.length > 100 || parteUsuario.toLowerCase().includes(nomeAnterior.toLowerCase())) {
                // Parece haver duplicações, reseta para vazio
                parteUsuario = '';
            }
        } else {
            // Nome não mudou: extrai a parte do usuário normalmente
            parteUsuario = extrairParteUsuario(nomeAtual, descricaoAtual);
        }
        
        // Limpa espaços extras da parte do usuário
        parteUsuario = parteUsuario.trim();
        
        // Armazena a parte do usuário limpa
        descricaoUsuarioOriginal = parteUsuario;
        
        // Constrói a descrição final: sempre limpa e nova
        let descricaoFinal = nomeAtual;
        if (parteUsuario) {
            descricaoFinal = nomeAtual + ' - ' + parteUsuario;
        }
        
        // Atualiza a descrição (sempre, para garantir que está correta)
        produtoDescricaoInput.value = descricaoFinal;
        
        // Atualiza o nome anterior para a próxima comparação
        nomeAnterior = nomeAtual;
        
        isUpdatingDescricao = false;
    }
    
    // Atualiza descrição quando o nome muda (apenas no blur para evitar loops)
    if (produtoNomeInput && produtoDescricaoInput) {
        // Inicializa nomeAnterior com o valor atual ao carregar
        nomeAnterior = produtoNomeInput.value.trim();
        
        // Atualiza nomeAnterior quando o campo perde o foco (depois da atualização)
        produtoNomeInput.addEventListener('blur', function() {
            atualizarDescricaoComNome();
            // Atualiza nomeAnterior após processar
            nomeAnterior = produtoNomeInput.value.trim();
        });
        
        // Também monitora mudanças durante a digitação (para atualizar nomeAnterior)
        produtoNomeInput.addEventListener('input', function() {
            // Não atualiza descrição durante digitação, apenas no blur
            // Mas atualiza nomeAnterior se detectar que mudou significativamente
            const nomeAtual = this.value.trim();
            if (nomeAnterior && nomeAnterior !== nomeAtual && nomeAtual.length > 0) {
                // Nome mudou, mas aguarda blur para atualizar descrição
            }
        });
        
        // Quando o usuário digita na descrição, limpa e reconstrói para evitar duplicações
        produtoDescricaoInput.addEventListener('input', function() {
            if (isUpdatingDescricao) {
                return;
            }
            
            const nome = produtoNomeInput.value.trim();
            const descricaoAtual = this.value.trim();
            
            if (nome) {
                // Extrai a parte do usuário da descrição atual
                const parteUsuario = extrairParteUsuario(nome, descricaoAtual);
                
                // Se extraiu algo diferente, reconstrói completamente
                const prefixoEsperado = nome + ' - ';
                const descricaoEsperada = parteUsuario ? prefixoEsperado + parteUsuario : nome;
                
                // Se a descrição não está no formato esperado, reconstrói
                if (descricaoAtual !== descricaoEsperada && descricaoAtual !== nome) {
                    isUpdatingDescricao = true;
                    produtoDescricaoInput.value = descricaoEsperada;
                    descricaoUsuarioOriginal = parteUsuario;
                    isUpdatingDescricao = false;
                } else {
                    // Atualiza a parte do usuário armazenada
                    descricaoUsuarioOriginal = parteUsuario;
                }
            }
        });
        
        // Inicializa ao carregar (para edição)
        if (produtoNomeInput.value && produtoDescricaoInput.value) {
            const nome = produtoNomeInput.value.trim();
            const descricao = produtoDescricaoInput.value.trim();
            
            // Extrai a parte do usuário corretamente
            if (nome) {
                const parteUsuario = extrairParteUsuario(nome, descricao);
                descricaoUsuarioOriginal = parteUsuario;
                
                // Reconstroi a descrição no formato correto se necessário
                const prefixoEsperado = nome + ' - ';
                if (parteUsuario) {
                    if (!descricao.startsWith(prefixoEsperado)) {
                        produtoDescricaoInput.value = prefixoEsperado + parteUsuario;
                    }
                } else {
                    if (descricao !== nome) {
                        produtoDescricaoInput.value = nome;
                    }
                }
            }
        }
    }
    
    // 🔍 DEBUG: Adiciona listener para debug do formulário
    const form = document.querySelector('.produto-form form');
    const formById = document.getElementById('form-produto');
    
    console.log('🔍 Form encontrado (querySelector):', form);
    console.log('🔍 Form encontrado (getElementById):', formById);
    
    if (form) {
        console.log('🔍 Adicionando listener de submit ao formulário');
        
        form.addEventListener('submit', function(e) {
            console.log('🔍 Formulário sendo submetido...');
            
            // 🔍 DEBUG: Verifica se há fotos no input antes de enviar
            if (fotosInput) {
                console.log('📷 Fotos no input antes do submit:', fotosInput.files.length);
                console.log('📷 selectedFiles array:', selectedFiles.length);
                
                // Garante que os arquivos estão no input antes de enviar
                if (selectedFiles.length > 0 && fotosInput.files.length === 0) {
                    console.log('⚠️ Arquivos no array mas não no input! Atualizando...');
                    updateFileInput();
                    console.log('📷 Fotos no input após atualização:', fotosInput.files.length);
                }
            }
            
            console.log('Dados do formulário:', new FormData(form));
            
            // Verifica se há erros de validação HTML5
            const invalidFields = form.querySelectorAll(':invalid');
            if (invalidFields.length > 0) {
                console.error('❌ Campos inválidos encontrados:', invalidFields);
                invalidFields.forEach(function(field) {
                    console.error('Campo inválido:', field.name, field.validationMessage);
                });
            } else {
                console.log('✅ Todos os campos são válidos');
            }
        });
        
        // Debug do botão de submit - múltiplas formas
        const submitButton = form.querySelector('button[type="submit"]');
        const submitButtonById = document.getElementById('btn-salvar-produto');
        
        console.log('🔍 Botão encontrado (querySelector):', submitButton);
        console.log('🔍 Botão encontrado (getElementById):', submitButtonById);
        
        if (submitButton) {
            console.log('🔍 Adicionando listener de click ao botão');
            submitButton.addEventListener('click', function(e) {
                console.log('🔍 Botão Salvar clicado (addEventListener)');
                console.log('Tipo do botão:', this.type);
                console.log('Formulário:', form);
                console.log('Event:', e);
            });
        }
        
        if (submitButtonById) {
            submitButtonById.addEventListener('click', function(e) {
                console.log('🔍 Botão Salvar clicado (por ID)');
            });
        }
        
        // Debug adicional: captura todos os cliques no formulário
        form.addEventListener('click', function(e) {
            if (e.target.type === 'submit' || e.target.closest('button[type="submit"]')) {
                console.log('🔍 Clique detectado em botão submit (captura de eventos)');
                console.log('Target:', e.target);
            }
        });
    } else {
        console.error('❌ Formulário não encontrado!');
    }
    
    const custoInput = document.getElementById('preco-custo');
    const freteInput = document.getElementById('valor-frete');
    const vendaInput = document.getElementById('preco-venda');
    const margemMarkupContainer = document.getElementById('margem-markup-container');
    const margemValor = document.getElementById('margem-valor');
    const markupValor = document.getElementById('markup-valor');
    const margemLucroPercentualInput = document.getElementById('margem-lucro-percentual');
    const markupPercentualInput = document.getElementById('markup-percentual');
    const calcularPorMargemCheckbox = document.getElementById('calcular-por-margem');
    const margemDesejadaContainer = document.getElementById('margem-desejada-container');
    const margemDesejadaInput = document.getElementById('margem-desejada');
    const btnCalcularPreco = document.getElementById('btn-calcular-preco');
    
    // Elementos de promoção
    const precoPromocionalInput = document.getElementById('preco-promocional');
    const dataInicioPromocaoInput = document.getElementById('data-inicio-promocao');
    const dataFimPromocaoInput = document.getElementById('data-fim-promocao');
    const promocaoPreview = document.getElementById('promocao-preview');
    const tagPromocaoContainer = document.getElementById('tag-promocao-container');
    const precoNormalDisplay = document.getElementById('preco-normal-display');
    const precoPromocionalDisplay = document.getElementById('preco-promocional-display');
    const descontoPercentual = document.getElementById('desconto-percentual');
    const economiaValor = document.getElementById('economia-valor');
    const alertaPrejuizoPromocao = document.getElementById('alerta-prejuizo-promocao');
    const mensagemPrejuizoPromocao = document.getElementById('mensagem-prejuizo-promocao');
    
    // Elementos de taxas (para validação de prejuízo)
    const taxaFixaInput = document.getElementById('taxa-fixa');
    const taxaVariavelInput = document.getElementById('taxa-variavel');
    
    /**
     * Valida se o preço promocional causa prejuízo
     */
    function validarPrejuizoPromocao() {
        if (!precoPromocionalInput || !custoInput || !freteInput) return;
        
        const precoPromo = parseFloat(precoPromocionalInput.value) || 0;
        const custo = parseFloat(custoInput.value) || 0;
        const frete = parseFloat(freteInput.value) || 0;
        const custoTotal = custo + frete;
        
        // Busca taxas da precificação inteligente
        const taxaFixa = parseFloat(taxaFixaInput?.value) || 0;
        const taxaVariavel = parseFloat(taxaVariavelInput?.value) || 0;
        
        if (precoPromo > 0 && custoTotal > 0) {
            // Calcula "Prova Real" do preço promocional
            const provaReal = calcularProvaReal(precoPromo, custoTotal, taxaFixa, taxaVariavel);
            
            if (provaReal.lucroReal < 0) {
                // Mostra alerta de prejuízo
                if (alertaPrejuizoPromocao) {
                    alertaPrejuizoPromocao.classList.remove('hidden');
                }
                if (mensagemPrejuizoPromocao) {
                    const prejuizo = Math.abs(provaReal.lucroReal);
                    mensagemPrejuizoPromocao.textContent = 
                        `Este preço promocional resultará em PREJUÍZO de ${formatarMoeda(prejuizo)}. ` +
                        `Após descontar as taxas fixas (${formatarMoeda(provaReal.impostosFixos)}), ` +
                        `taxas variáveis (${formatarMoeda(provaReal.impostosVariaveis)}) e o custo total (${formatarMoeda(custoTotal)}), ` +
                        `o lucro real será negativo.`;
                }
                
                // Destaca o campo de preço promocional
                precoPromocionalInput.classList.add('border-red-500', 'bg-red-50');
            } else {
                // Esconde alerta se não houver prejuízo
                if (alertaPrejuizoPromocao) {
                    alertaPrejuizoPromocao.classList.add('hidden');
                }
                precoPromocionalInput.classList.remove('border-red-500', 'bg-red-50');
            }
        } else {
            // Esconde alerta se não houver valores
            if (alertaPrejuizoPromocao) {
                alertaPrejuizoPromocao.classList.add('hidden');
            }
            precoPromocionalInput.classList.remove('border-red-500', 'bg-red-50');
        }
    }
    
    /**
     * Calcula desconto percentual: ((Preço Normal - Preço Promocional) / Preço Normal) * 100
     */
    function calcularDescontoPercentual(precoNormal, precoPromocional) {
        if (precoNormal <= 0 || precoPromocional <= 0) return 0;
        if (precoPromocional >= precoNormal) return 0;
        return ((precoNormal - precoPromocional) / precoNormal) * 100;
    }
    
    /**
     * Calcula economia: Preço Normal - Preço Promocional
     */
    function calcularEconomia(precoNormal, precoPromocional) {
        if (precoNormal <= 0 || precoPromocional <= 0) return 0;
        return Math.max(0, precoNormal - precoPromocional);
    }
    
    /**
     * Atualiza preview da promoção
     */
    function atualizarPreviewPromocao() {
        const precoNormal = parseFloat(vendaInput.value) || 0;
        const precoPromo = parseFloat(precoPromocionalInput.value) || 0;
        
        if (precoNormal > 0 && precoPromo > 0 && precoPromo < precoNormal) {
            const desconto = calcularDescontoPercentual(precoNormal, precoPromo);
            const economia = calcularEconomia(precoNormal, precoPromo);
            
            // Atualiza displays
            if (precoNormalDisplay) {
                precoNormalDisplay.textContent = formatarMoeda(precoNormal);
            }
            if (precoPromocionalDisplay) {
                precoPromocionalDisplay.textContent = formatarMoeda(precoPromo);
            }
            if (descontoPercentual) {
                descontoPercentual.textContent = desconto.toFixed(2) + '%';
            }
            if (economiaValor) {
                economiaValor.textContent = formatarMoeda(economia);
            }
            
            // Mostra preview
            if (promocaoPreview) {
                promocaoPreview.classList.remove('hidden');
            }
            if (tagPromocaoContainer) {
                tagPromocaoContainer.classList.remove('hidden');
            }
            
            // ✅ Valida prejuízo na promoção
            validarPrejuizoPromocao();
        } else {
            // Esconde preview se não houver valores válidos
            if (promocaoPreview) {
                promocaoPreview.classList.add('hidden');
            }
            if (tagPromocaoContainer) {
                tagPromocaoContainer.classList.add('hidden');
            }
            
            // Esconde alerta de prejuízo
            if (alertaPrejuizoPromocao) {
                alertaPrejuizoPromocao.classList.add('hidden');
            }
        }
    }
    
    /**
     * Valida datas da promoção
     */
    function validarDatasPromocao() {
        if (!dataInicioPromocaoInput || !dataFimPromocaoInput) return;
        
        const inicio = dataInicioPromocaoInput.value;
        const fim = dataFimPromocaoInput.value;
        
        if (inicio && fim) {
            const dataInicio = new Date(inicio);
            const dataFim = new Date(fim);
            
            if (dataFim < dataInicio) {
                dataFimPromocaoInput.setCustomValidity('A data de fim deve ser posterior à data de início.');
                dataFimPromocaoInput.classList.add('border-red-500');
            } else {
                dataFimPromocaoInput.setCustomValidity('');
                dataFimPromocaoInput.classList.remove('border-red-500');
            }
        } else {
            dataFimPromocaoInput.setCustomValidity('');
            dataFimPromocaoInput.classList.remove('border-red-500');
        }
    }
    
    // Event listeners para promoção
    if (precoPromocionalInput && vendaInput) {
        precoPromocionalInput.addEventListener('input', function() {
            atualizarPreviewPromocao();
            
            // Valida se preço promocional é menor que preço normal
            const precoNormal = parseFloat(vendaInput.value) || 0;
            const precoPromo = parseFloat(this.value) || 0;
            
            if (precoPromo > 0 && precoNormal > 0) {
                if (precoPromo >= precoNormal) {
                    this.setCustomValidity('O preço promocional deve ser menor que o preço de venda normal.');
                    this.classList.add('border-red-500');
                } else {
                    this.setCustomValidity('');
                    // Remove border-red-500 apenas se não houver prejuízo (será adicionado pela validação de prejuízo)
                    if (!this.classList.contains('bg-red-50')) {
                        this.classList.remove('border-red-500');
                    }
                }
            }
        });
        
        vendaInput.addEventListener('input', atualizarPreviewPromocao);
        
        // Valida prejuízo quando custo ou frete mudarem
        if (custoInput) {
            custoInput.addEventListener('input', validarPrejuizoPromocao);
        }
        if (freteInput) {
            freteInput.addEventListener('input', validarPrejuizoPromocao);
        }
        
        // Valida prejuízo quando taxas mudarem
        if (taxaFixaInput) {
            taxaFixaInput.addEventListener('input', validarPrejuizoPromocao);
        }
        if (taxaVariavelInput) {
            taxaVariavelInput.addEventListener('input', validarPrejuizoPromocao);
        }
    }
    
    if (dataInicioPromocaoInput) {
        dataInicioPromocaoInput.addEventListener('change', validarDatasPromocao);
    }
    
    if (dataFimPromocaoInput) {
        dataFimPromocaoInput.addEventListener('change', validarDatasPromocao);
    }
    
    // Atualiza preview ao carregar se houver valores
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', atualizarPreviewPromocao);
    } else {
        atualizarPreviewPromocao();
    }
    
    /**
     * Calcula margem de lucro: (Preço Venda - Custo Total) / Preço Venda * 100
     */
    function calcularMargemLucro(custoTotal, precoVenda) {
        if (precoVenda <= 0) return 0;
        const margem = ((precoVenda - custoTotal) / precoVenda) * 100;
        return Math.max(0, Math.min(99.99, parseFloat(margem.toFixed(2))));
    }
    
    /**
     * Calcula markup: (Preço Venda - Custo Total) / Custo Total * 100
     */
    function calcularMarkup(custoTotal, precoVenda) {
        if (custoTotal <= 0) return 0;
        const markup = ((precoVenda - custoTotal) / custoTotal) * 100;
        return Math.max(0, parseFloat(markup.toFixed(2)));
    }
    
    /**
     * Calcula preço de venda pela margem desejada: Custo / (1 - (Margem / 100))
     */
    function calcularPrecoPorMargem(custoTotal, margemPercentual) {
        if (margemPercentual >= 100 || margemPercentual < 0) return 0;
        if (custoTotal <= 0) return 0;
        const preco = custoTotal / (1 - (margemPercentual / 100));
        return parseFloat(preco.toFixed(2));
    }
    
    /**
     * Atualiza os cálculos de margem e markup
     */
    function atualizarCalculos() {
        const custo = parseFloat(custoInput.value) || 0;
        const frete = parseFloat(freteInput.value) || 0;
        const venda = parseFloat(vendaInput.value) || 0;
        const custoTotal = custo + frete;
        
        if (custoTotal > 0 && venda > 0) {
            const margem = calcularMargemLucro(custoTotal, venda);
            const markup = calcularMarkup(custoTotal, venda);
            
            if (margemValor) margemValor.textContent = margem.toFixed(2) + '%';
            if (markupValor) markupValor.textContent = markup.toFixed(2) + '%';
            
            // Atualizar campos ocultos
            if (margemLucroPercentualInput) margemLucroPercentualInput.value = margem;
            if (markupPercentualInput) markupPercentualInput.value = markup;
            
            // Mostra sempre que houver valores
            if (margemMarkupContainer) {
                margemMarkupContainer.style.display = 'block';
            }
        } else {
            if (margemMarkupContainer) {
                margemMarkupContainer.style.display = 'none';
            }
            if (margemValor) margemValor.textContent = '0.00%';
            if (markupValor) markupValor.textContent = '0.00%';
            if (margemLucroPercentualInput) margemLucroPercentualInput.value = '';
            if (markupPercentualInput) markupPercentualInput.value = '';
        }
    }
    
    // Event listeners para calcular automaticamente
    if (custoInput && freteInput && vendaInput) {
        custoInput.addEventListener('input', atualizarCalculos);
        freteInput.addEventListener('input', atualizarCalculos);
        vendaInput.addEventListener('input', atualizarCalculos);
        atualizarCalculos(); // Calcular ao carregar se houver valores
    }
    
    // Toggle para calcular por margem desejada
    if (calcularPorMargemCheckbox) {
        calcularPorMargemCheckbox.addEventListener('change', function() {
            if (this.checked) {
                margemDesejadaContainer.classList.remove('hidden');
            } else {
                margemDesejadaContainer.classList.add('hidden');
            }
        });
    }
    
    // Botão para calcular preço pela margem desejada
    if (btnCalcularPreco) {
        btnCalcularPreco.addEventListener('click', function() {
            const custo = parseFloat(custoInput.value) || 0;
            const frete = parseFloat(freteInput.value) || 0;
            const margemDesejada = parseFloat(margemDesejadaInput.value) || 0;
            const custoTotal = custo + frete;
            
            if (custoTotal > 0 && margemDesejada > 0 && margemDesejada < 100) {
                const precoCalculado = calcularPrecoPorMargem(custoTotal, margemDesejada);
                vendaInput.value = precoCalculado.toFixed(2);
                atualizarCalculos();
                
                // Feedback visual
                vendaInput.classList.add('bg-green-50', 'border-green-500');
                setTimeout(() => {
                    vendaInput.classList.remove('bg-green-50', 'border-green-500');
                }, 2000);
            } else {
                alert('Por favor, informe o custo e uma margem válida (entre 0 e 99.99%).');
            }
        });
    }

    // Preview de fotos e gerenciamento de arquivos
    const fotosInput = document.getElementById('fotos-input');
    const previewContainer = document.getElementById('preview-container');
    let selectedFiles = []; // Array para armazenar todos os arquivos selecionados
    
    // Elementos do modal da câmera
    const cameraModal = document.getElementById('camera-modal');
    const btnCamera = document.getElementById('btn-camera');
    const closeCameraModal = document.getElementById('close-camera-modal');
    const cameraVideo = document.getElementById('camera-video');
    const cameraCanvas = document.getElementById('camera-canvas');
    const cameraPreview = document.getElementById('camera-preview');
    const capturedImage = document.getElementById('captured-image');
    const btnCapture = document.getElementById('btn-capture');
    const btnRetake = document.getElementById('btn-retake');
    const btnUsePhoto = document.getElementById('btn-use-photo');
    const cameraError = document.getElementById('camera-error');
    
    // 🔍 DEBUG: Verifica se os elementos foram encontrados
    if (!cameraModal) console.error('❌ camera-modal não encontrado');
    if (!btnCamera) console.error('❌ btn-camera não encontrado');
    if (!cameraVideo) console.error('❌ camera-video não encontrado');
    if (!cameraCanvas) console.error('❌ camera-canvas não encontrado');
    
    let stream = null; // Stream da câmera
    let capturedBlob = null; // Foto capturada
    
    /**
     * Converte blob para File object
     */
    function blobToFile(blob, filename) {
        return new File([blob], filename, {type: blob.type});
    }
    
    /**
     * Redimensiona e comprime imagem para tamanho otimizado (50-200KB)
     */
    async function optimizeImage(file, maxWidth = 1920, maxHeight = 1920, minSizeKB = 50, maxSizeKB = 200) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const img = new Image();
                
                img.onload = function() {
                    // Calcula novas dimensões mantendo proporção
                    let width = img.width;
                    let height = img.height;
                    
                    if (width > maxWidth || height > maxHeight) {
                        const ratio = Math.min(maxWidth / width, maxHeight / height);
                        width = Math.round(width * ratio);
                        height = Math.round(height * ratio);
                    }
                    
                    // Cria canvas para redimensionar
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    
                    // Desenha imagem redimensionada
                    ctx.drawImage(img, 0, 0, width, height);
                    
                    // Função para comprimir com qualidade ajustável
                    function compressWithQuality(quality) {
                        return new Promise((resolveCompress) => {
                            canvas.toBlob(function(blob) {
                                if (!blob) {
                                    reject(new Error('Erro ao comprimir imagem'));
                                    return;
                                }
                                
                                const sizeKB = blob.size / 1024;
                                
                                // Se o tamanho está dentro do range desejado, retorna
                                if (sizeKB >= minSizeKB && sizeKB <= maxSizeKB) {
                                    resolveCompress(blob);
                                    return;
                                }
                                
                                // Se está muito grande, reduz qualidade
                                if (sizeKB > maxSizeKB && quality > 0.3) {
                                    compressWithQuality(Math.max(0.3, quality - 0.1)).then(resolveCompress);
                                    return;
                                }
                                
                                // Se está muito pequena, aumenta qualidade (mas não muito)
                                if (sizeKB < minSizeKB && quality < 0.9) {
                                    compressWithQuality(Math.min(0.9, quality + 0.1)).then(resolveCompress);
                                    return;
                                }
                                
                                // Aceita o resultado atual
                                resolveCompress(blob);
                            }, 'image/jpeg', quality);
                        });
                    }
                    
                    // Inicia compressão com qualidade inicial de 0.85
                    compressWithQuality(0.85).then(function(optimizedBlob) {
                        const timestamp = new Date().getTime();
                        const filename = file.name.replace(/\.[^/.]+$/, '') || 'foto';
                        const optimizedFile = blobToFile(optimizedBlob, `${filename}_${timestamp}.jpg`);
                        resolve(optimizedFile);
                    }).catch(reject);
                };
                
                img.onerror = function() {
                    reject(new Error('Erro ao carregar imagem'));
                };
                
                img.src = e.target.result;
            };
            
            reader.onerror = function() {
                reject(new Error('Erro ao ler arquivo'));
            };
            
            reader.readAsDataURL(file);
        });
    }
    
    /**
     * Adiciona arquivo ao array e atualiza o input (com otimização)
     */
    async function addFileToInput(file, isFromCamera = false) {
        try {
            // Mostra indicador de processamento
            const processingIndicator = document.createElement('div');
            processingIndicator.className = 'fixed top-4 right-4 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
            processingIndicator.innerHTML = '🔄 Otimizando imagem...';
            document.body.appendChild(processingIndicator);
            
            // Otimiza a imagem
            const optimizedFile = await optimizeImage(file);
            
            // Remove indicador
            processingIndicator.remove();
            
            selectedFiles.push(optimizedFile);
            updateFileInput();
            showPreview(optimizedFile);
        } catch (error) {
            console.error('Erro ao otimizar imagem:', error);
            alert('Erro ao processar imagem. Tentando adicionar sem otimização...');
            // Em caso de erro, adiciona o arquivo original
            selectedFiles.push(file);
            updateFileInput();
            showPreview(file);
        }
    }
    
    /**
     * Atualiza o input de arquivos com DataTransfer
     */
    function updateFileInput() {
        try {
            if (!fotosInput) {
                console.error('❌ fotosInput não encontrado');
                return;
            }
            
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => {
                try {
            dataTransfer.items.add(file);
                } catch (err) {
                    console.error('❌ Erro ao adicionar arquivo ao DataTransfer:', err, file);
                }
        });
            
        fotosInput.files = dataTransfer.files;
            console.log('✅ Input atualizado com', fotosInput.files.length, 'arquivo(s)');
        } catch (err) {
            console.error('❌ Erro ao atualizar input de arquivos:', err);
            // Fallback: tenta usar o método antigo se DataTransfer não funcionar
            if (selectedFiles.length > 0) {
                console.warn('⚠️ Tentando método alternativo para enviar arquivos...');
            }
        }
    }
    
    /**
     * Formata tamanho do arquivo
     */
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }
    
    /**
     * Mostra preview de um arquivo
     */
    function showPreview(file, index) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'relative group';
            div.dataset.fileIndex = index !== undefined ? index : selectedFiles.length - 1;
            const fileSize = formatFileSize(file.size);
            div.innerHTML = `
                <img src="${e.target.result}" class="w-full h-32 object-cover rounded-lg">
                <span class="absolute top-2 left-2 px-2 py-1 bg-gray-900 bg-opacity-75 text-white text-xs rounded">
                    ${fileSize}
                </span>
                <button type="button" class="remove-photo absolute top-2 right-2 w-6 h-6 flex items-center justify-center bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    ✕
                </button>
            `;
            
            // Adiciona listener para remover foto
            const removeBtn = div.querySelector('.remove-photo');
            removeBtn.addEventListener('click', function() {
                const fileIndex = parseInt(div.dataset.fileIndex);
                selectedFiles.splice(fileIndex, 1);
                updateFileInput();
                updateAllPreviews(); // Atualiza todos os previews para corrigir índices
            });
            
            previewContainer.appendChild(div);
        }
        
        reader.readAsDataURL(file);
    }
    
    /**
     * Atualiza preview de todos os arquivos
     */
    function updateAllPreviews() {
        previewContainer.innerHTML = '';
        selectedFiles.forEach((file, index) => {
            showPreview(file, index);
        });
    }
    
    /**
     * Inicia a câmera
     */
    async function startCamera() {
        try {
            // Verifica se os elementos necessários existem
            if (!cameraVideo || !cameraCanvas) {
                console.error('❌ Elementos da câmera não encontrados');
                showError('Erro: Elementos da câmera não foram encontrados. Recarregue a página.');
                return;
            }
            
            // Verifica se o navegador suporta getUserMedia
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                console.error('❌ getUserMedia não suportado');
                showError('Seu navegador não suporta acesso à câmera. Use um navegador moderno ou HTTPS.');
                return;
            }
            
            hideError();
            
            // Tenta acessar a câmera traseira primeiro (environment), depois frontal (user)
            const constraints = {
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            };
            
            console.log('📷 Tentando acessar câmera...');
            stream = await navigator.mediaDevices.getUserMedia(constraints);
            console.log('✅ Câmera acessada com sucesso');
            
            if (!cameraVideo) {
                console.error('❌ cameraVideo não encontrado após acesso à câmera');
                return;
            }
            
            cameraVideo.srcObject = stream;
            await cameraVideo.play();
            
            // Mostra o vídeo e esconde o preview
            if (cameraVideo) cameraVideo.classList.remove('hidden');
            if (cameraPreview) cameraPreview.classList.add('hidden');
            if (btnCapture) btnCapture.classList.remove('hidden');
            if (btnRetake) btnRetake.classList.add('hidden');
            if (btnUsePhoto) btnUsePhoto.classList.add('hidden');
            
        } catch (err) {
            console.error('❌ Erro ao acessar câmera:', err);
            let errorMessage = 'Não foi possível acessar a câmera. ';
            
            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                errorMessage += 'Permissão negada. Permita o acesso à câmera nas configurações do navegador.';
            } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                errorMessage += 'Nenhuma câmera encontrada. Verifique se há uma câmera conectada.';
            } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                errorMessage += 'A câmera está sendo usada por outro aplicativo.';
            } else if (err.name === 'OverconstrainedError' || err.name === 'ConstraintNotSatisfiedError') {
                errorMessage += 'Configurações da câmera não suportadas.';
            } else {
                errorMessage += 'Erro: ' + err.message;
            }
            
            showError(errorMessage);
        }
    }
    
    /**
     * Para a câmera
     */
    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        if (cameraVideo.srcObject) {
            cameraVideo.srcObject = null;
        }
    }
    
    /**
     * Captura foto da câmera
     */
    function capturePhoto() {
        try {
            const video = cameraVideo;
            const canvas = cameraCanvas;
            
            // Define o tamanho do canvas igual ao do vídeo
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Desenha o frame atual do vídeo no canvas
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Converte para blob
            canvas.toBlob(function(blob) {
                if (blob) {
                    capturedBlob = blob;
                    const imageUrl = URL.createObjectURL(blob);
                    capturedImage.src = imageUrl;
                    
                    // Mostra o preview e esconde o vídeo
                    cameraVideo.classList.add('hidden');
                    cameraPreview.classList.remove('hidden');
                    btnCapture.classList.add('hidden');
                    btnRetake.classList.remove('hidden');
                    btnUsePhoto.classList.remove('hidden');
                }
            }, 'image/jpeg', 0.95);
            
        } catch (err) {
            console.error('Erro ao capturar foto:', err);
            showError('Erro ao capturar a foto. Tente novamente.');
        }
    }
    
    /**
     * Usa a foto capturada
     */
    async function useCapturedPhoto() {
        if (capturedBlob) {
            const timestamp = new Date().getTime();
            const filename = `camera_${timestamp}.jpg`;
            const file = blobToFile(capturedBlob, filename);
            
            await addFileToInput(file, true);
            closeCamera();
        }
    }
    
    /**
     * Tira outra foto (volta para o modo de captura)
     */
    function retakePhoto() {
        if (stream) {
            cameraVideo.classList.remove('hidden');
            cameraPreview.classList.add('hidden');
            btnCapture.classList.remove('hidden');
            btnRetake.classList.add('hidden');
            btnUsePhoto.classList.add('hidden');
            capturedBlob = null;
        }
    }
    
    /**
     * Abre o modal da câmera
     */
    function openCamera() {
        console.log('📷 openCamera chamado');
        
        if (!cameraModal) {
            console.error('❌ cameraModal não encontrado');
            alert('Erro: Modal da câmera não encontrado. Recarregue a página.');
            return;
        }
        
        try {
        cameraModal.classList.remove('hidden');
            console.log('✅ Modal da câmera aberto');
        startCamera();
        } catch (err) {
            console.error('❌ Erro ao abrir câmera:', err);
            alert('Erro ao abrir a câmera: ' + err.message);
        }
    }
    
    /**
     * Fecha o modal da câmera
     */
    function closeCamera() {
        stopCamera();
        cameraModal.classList.add('hidden');
        cameraVideo.classList.remove('hidden');
        cameraPreview.classList.add('hidden');
        btnCapture.classList.remove('hidden');
        btnRetake.classList.add('hidden');
        btnUsePhoto.classList.add('hidden');
        capturedBlob = null;
        hideError();
    }
    
    /**
     * Mostra erro
     */
    function showError(message) {
        cameraError.textContent = message;
        cameraError.classList.remove('hidden');
    }
    
    /**
     * Esconde erro
     */
    function hideError() {
        cameraError.classList.add('hidden');
    }
    
    // Event listeners - Executa após um pequeno delay para garantir que o DOM está pronto
    setTimeout(function() {
    if (btnCamera) {
            console.log('✅ btnCamera encontrado, adicionando listener');
            btnCamera.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('📷 Botão câmera clicado');
                openCamera();
            });
            
            // Teste adicional: verifica se o listener foi adicionado
            console.log('✅ Listener adicionado ao botão câmera');
        } else {
            console.error('❌ btnCamera não encontrado - botão de câmera não funcionará');
            console.error('Tentando encontrar novamente...');
            const btnCameraRetry = document.getElementById('btn-camera');
            if (btnCameraRetry) {
                console.log('✅ btnCamera encontrado na segunda tentativa');
                btnCameraRetry.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('📷 Botão câmera clicado (retry)');
                    openCamera();
                });
            }
        }
    }, 100);
    
    if (closeCameraModal) {
        closeCameraModal.addEventListener('click', closeCamera);
    }
    
    if (btnCapture) {
        btnCapture.addEventListener('click', capturePhoto);
    }
    
    if (btnRetake) {
        btnRetake.addEventListener('click', retakePhoto);
    }
    
    if (btnUsePhoto) {
        btnUsePhoto.addEventListener('click', useCapturedPhoto);
    }
    
    // Fecha modal ao clicar fora
    if (cameraModal) {
        cameraModal.addEventListener('click', function(e) {
            if (e.target === cameraModal) {
                closeCamera();
            }
        });
    }
    
    console.log('✅ Event listeners da câmera configurados');
    
    // Input de upload de arquivos
    if (fotosInput) {
        fotosInput.addEventListener('change', async function(e) {
            const files = Array.from(e.target.files);
            
            // Adiciona novos arquivos ao array (evita duplicatas e otimiza)
            for (const file of files) {
                const exists = selectedFiles.some(f => f.name === file.name && f.size === file.size && f.lastModified === file.lastModified);
                if (!exists) {
                    await addFileToInput(file, false);
                }
            }
            
            // Limpa o input para permitir selecionar os mesmos arquivos novamente
            fotosInput.value = '';
        });
    }
    
    // Carrega arquivos existentes se houver (para edição)
    if (fotosInput && fotosInput.files.length > 0) {
        selectedFiles = Array.from(fotosInput.files);
        updateAllPreviews();
    }

    // ============================================
    // PRECIFICAÇÃO INTELIGENTE (MARKUP DIVISOR)
    // ============================================
    // Nota: taxaFixaInput e taxaVariavelInput já foram declarados acima (linha ~1200)
    const lucroLiquidoInput = document.getElementById('lucro-liquido');
    const btnCalcularMarkupDivisor = document.getElementById('btn-calcular-markup-divisor');
    const fatorDivisorValor = document.getElementById('fator-divisor-valor');
    const precoSugeridoValor = document.getElementById('preco-sugerido-valor');
    const alertaPrejuizo = document.getElementById('alerta-prejuizo');
    const mensagemPrejuizo = document.getElementById('mensagem-prejuizo');

    // Elementos da tabela "A Prova Real"
    const provaPrecoVenda = document.getElementById('prova-preco-venda');
    const provaTaxasFixas = document.getElementById('prova-taxas-fixas');
    const provaTaxasVariaveis = document.getElementById('prova-taxas-variaveis');
    const provaCustoTotal = document.getElementById('prova-custo-total');
    const provaLucroReal = document.getElementById('prova-lucro-real');
    const provaMargemReal = document.getElementById('prova-margem-real');

    /**
     * Calcula o Fator Divisor
     * Fator Divisor = 1 - ((%Fixas + %Variáveis + %Lucro) / 100)
     */
    function calcularFatorDivisor(taxaFixa, taxaVariavel, lucroLiquido) {
        const soma = taxaFixa + taxaVariavel + lucroLiquido;
        if (soma >= 100) {
            throw new Error('A soma das taxas e lucro não pode ser 100% ou mais.');
        }
        return 1 - (soma / 100);
    }

    /**
     * Calcula o preço de venda usando Markup Divisor
     * Preço Venda = Custo / Fator Divisor
     */
    function calcularPrecoPorMarkupDivisor(custoTotal, fatorDivisor) {
        if (fatorDivisor <= 0) {
            return 0;
        }
        return custoTotal / fatorDivisor;
    }

    /**
     * Calcula a "Prova Real" (engenharia reversa)
     */
    function calcularProvaReal(precoVenda, custoTotal, taxaFixa, taxaVariavel) {
        const impostosFixos = (precoVenda * taxaFixa) / 100;
        const impostosVariaveis = (precoVenda * taxaVariavel) / 100;
        const lucroReal = precoVenda - impostosFixos - impostosVariaveis - custoTotal;
        const margemReal = (lucroReal / precoVenda) * 100;

        return {
            precoVenda: precoVenda,
            impostosFixos: impostosFixos,
            impostosVariaveis: impostosVariaveis,
            custoTotal: custoTotal,
            lucroReal: lucroReal,
            margemReal: margemReal
        };
    }

    /**
     * Formata valor monetário
     */
    function formatarMoeda(valor) {
        if (isNaN(valor) || valor === null || valor === undefined) {
            return 'R$ 0,00';
        }
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(valor);
    }

    /**
     * Atualiza todos os cálculos da precificação inteligente
     */
    function atualizarPrecificacaoInteligente() {
        try {
            const custo = parseFloat(custoInput.value) || 0;
            const frete = parseFloat(freteInput.value) || 0;
            const custoTotal = custo + frete;
            const taxaFixa = parseFloat(taxaFixaInput.value) || 0;
            const taxaVariavel = parseFloat(taxaVariavelInput.value) || 0;
            const lucroLiquido = parseFloat(lucroLiquidoInput.value) || 0;

            // Calcula o fator divisor
            const fatorDivisor = calcularFatorDivisor(taxaFixa, taxaVariavel, lucroLiquido);
            fatorDivisorValor.textContent = fatorDivisor.toFixed(4);

            // Calcula o preço sugerido
            if (custoTotal > 0 && fatorDivisor > 0) {
                const precoSugerido = calcularPrecoPorMarkupDivisor(custoTotal, fatorDivisor);
                precoSugeridoValor.textContent = formatarMoeda(precoSugerido);

                // Atualiza a "Prova Real"
                const provaReal = calcularProvaReal(precoSugerido, custoTotal, taxaFixa, taxaVariavel);
                
                provaPrecoVenda.textContent = formatarMoeda(provaReal.precoVenda);
                provaTaxasFixas.textContent = formatarMoeda(provaReal.impostosFixos);
                provaTaxasVariaveis.textContent = formatarMoeda(provaReal.impostosVariaveis);
                provaCustoTotal.textContent = formatarMoeda(provaReal.custoTotal);
                
                // Lucro real com cor semântica
                if (provaReal.lucroReal < 0) {
                    provaLucroReal.textContent = formatarMoeda(provaReal.lucroReal);
                    provaLucroReal.className = 'text-right py-2 px-2 text-red-600 font-bold';
                    provaMargemReal.textContent = provaReal.margemReal.toFixed(2) + '%';
                    provaMargemReal.className = 'text-right py-2 px-2 text-red-600 text-xs';
                    
                    // Mostra alerta de prejuízo
                    alertaPrejuizo.classList.remove('hidden');
                    mensagemPrejuizo.textContent = `Este preço resultará em PREJUÍZO de ${formatarMoeda(Math.abs(provaReal.lucroReal))}. Ajuste o preço de venda ou reduza as taxas.`;
                } else {
                    provaLucroReal.textContent = formatarMoeda(provaReal.lucroReal);
                    provaLucroReal.className = 'text-right py-2 px-2 text-green-600 font-bold';
                    provaMargemReal.textContent = provaReal.margemReal.toFixed(2) + '%';
                    provaMargemReal.className = 'text-right py-2 px-2 text-green-600 text-xs';
                    
                    // Esconde alerta de prejuízo
                    alertaPrejuizo.classList.add('hidden');
                }
            } else {
                precoSugeridoValor.textContent = 'R$ 0,00';
                provaPrecoVenda.textContent = 'R$ 0,00';
                provaTaxasFixas.textContent = 'R$ 0,00';
                provaTaxasVariaveis.textContent = 'R$ 0,00';
                provaCustoTotal.textContent = 'R$ 0,00';
                provaLucroReal.textContent = 'R$ 0,00';
                provaLucroReal.className = 'text-right py-2 px-2 text-green-600 font-bold';
                provaMargemReal.textContent = '0.00%';
                provaMargemReal.className = 'text-right py-2 px-2 text-green-600 text-xs';
                alertaPrejuizo.classList.add('hidden');
            }
        } catch (error) {
            console.error('Erro ao calcular precificação inteligente:', error);
            fatorDivisorValor.textContent = '0.0000';
            precoSugeridoValor.textContent = 'R$ 0,00';
            alertaPrejuizo.classList.remove('hidden');
            mensagemPrejuizo.textContent = error.message || 'Erro ao calcular. Verifique os valores informados.';
        }
    }

    /**
     * Aplica o preço sugerido ao campo de preço de venda
     */
    function aplicarPrecoSugerido() {
        try {
            const custo = parseFloat(custoInput.value) || 0;
            const frete = parseFloat(freteInput.value) || 0;
            const custoTotal = custo + frete;
            const taxaFixa = parseFloat(taxaFixaInput.value) || 0;
            const taxaVariavel = parseFloat(taxaVariavelInput.value) || 0;
            const lucroLiquido = parseFloat(lucroLiquidoInput.value) || 0;

            if (custoTotal <= 0) {
                alert('Por favor, informe o preço de custo primeiro.');
                return;
            }

            const fatorDivisor = calcularFatorDivisor(taxaFixa, taxaVariavel, lucroLiquido);
            const precoSugerido = calcularPrecoPorMarkupDivisor(custoTotal, fatorDivisor);

            if (precoSugerido > 0) {
                vendaInput.value = precoSugerido.toFixed(2);
                
                // Feedback visual
                vendaInput.classList.add('bg-green-50', 'border-green-500');
                setTimeout(() => {
                    vendaInput.classList.remove('bg-green-50', 'border-green-500');
                }, 2000);

                // Atualiza cálculos de margem/markup
                atualizarCalculos();
                
                // Atualiza precificação inteligente
                atualizarPrecificacaoInteligente();
            }
        } catch (error) {
            alert('Erro ao calcular: ' + error.message);
        }
    }

    // Event listeners para cálculo em tempo real
    if (taxaFixaInput && taxaVariavelInput && lucroLiquidoInput) {
        taxaFixaInput.addEventListener('input', atualizarPrecificacaoInteligente);
        taxaVariavelInput.addEventListener('input', atualizarPrecificacaoInteligente);
        lucroLiquidoInput.addEventListener('input', atualizarPrecificacaoInteligente);
        
        // Também atualiza quando custo ou frete mudam
        if (custoInput) custoInput.addEventListener('input', atualizarPrecificacaoInteligente);
        if (freteInput) freteInput.addEventListener('input', atualizarPrecificacaoInteligente);
        if (vendaInput) vendaInput.addEventListener('input', function() {
            // Quando o preço de venda muda manualmente, atualiza a prova real
            const custo = parseFloat(custoInput.value) || 0;
            const frete = parseFloat(freteInput.value) || 0;
            const custoTotal = custo + frete;
            const taxaFixa = parseFloat(taxaFixaInput.value) || 0;
            const taxaVariavel = parseFloat(taxaVariavelInput.value) || 0;
            const precoVenda = parseFloat(this.value) || 0;

            if (precoVenda > 0 && custoTotal > 0) {
                const provaReal = calcularProvaReal(precoVenda, custoTotal, taxaFixa, taxaVariavel);
                
                provaPrecoVenda.textContent = formatarMoeda(provaReal.precoVenda);
                provaTaxasFixas.textContent = formatarMoeda(provaReal.impostosFixos);
                provaTaxasVariaveis.textContent = formatarMoeda(provaReal.impostosVariaveis);
                provaCustoTotal.textContent = formatarMoeda(provaReal.custoTotal);
                
                if (provaReal.lucroReal < 0) {
                    provaLucroReal.textContent = formatarMoeda(provaReal.lucroReal);
                    provaLucroReal.className = 'text-right py-2 px-2 text-red-600 font-bold';
                    provaMargemReal.textContent = provaReal.margemReal.toFixed(2) + '%';
                    provaMargemReal.className = 'text-right py-2 px-2 text-red-600 text-xs';
                    
                    alertaPrejuizo.classList.remove('hidden');
                    mensagemPrejuizo.textContent = `Este preço resultará em PREJUÍZO de ${formatarMoeda(Math.abs(provaReal.lucroReal))}. Ajuste o preço de venda ou reduza as taxas.`;
                } else {
                    provaLucroReal.textContent = formatarMoeda(provaReal.lucroReal);
                    provaLucroReal.className = 'text-right py-2 px-2 text-green-600 font-bold';
                    provaMargemReal.textContent = provaReal.margemReal.toFixed(2) + '%';
                    provaMargemReal.className = 'text-right py-2 px-2 text-green-600 text-xs';
                    alertaPrejuizo.classList.add('hidden');
                }
            }
        });
    }

    // Botão para calcular e aplicar preço sugerido
    if (btnCalcularMarkupDivisor) {
        btnCalcularMarkupDivisor.addEventListener('click', aplicarPrecoSugerido);
    }

    // Inicializa cálculos ao carregar
    if (taxaFixaInput && taxaVariavelInput && lucroLiquidoInput && custoInput) {
        atualizarPrecificacaoInteligente();
    }
    
    // Corrige problema do Select2 - garante que está inicializado corretamente
    // Aguarda um pouco para garantir que o Select2 do Kartik foi carregado
    setTimeout(function() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            const categoriaSelect = jQuery('#categoria-select');
            if (categoriaSelect.length) {
                // Verifica se o Select2 está inicializado
                const select2Instance = categoriaSelect.data('select2');
                if (!select2Instance) {
                    // Re-inicializa o Select2 se não estiver inicializado
                    categoriaSelect.select2({
                        allowClear: true,
                        minimumInputLength: 0,
                    });
                } else {
                    // Se já está inicializado, verifica se há problema com loadingMore
                    if (select2Instance.results && typeof select2Instance.results.loadingMore !== 'function') {
                        // Adiciona a função loadingMore se não existir
                        select2Instance.results.loadingMore = function() {
                            return false;
                        };
                    }
                }
                
                // Se há categoria pré-preenchida e código ainda não foi gerado, gera automaticamente
                const isNewRecord = <?= $model->isNewRecord ? 'true' : 'false' ?>;
                if (isNewRecord) {
                    const categoriaId = categoriaSelect.val();
                    const codigoInput = document.getElementById('produto-codigo_referencia');
                    if (categoriaId && codigoInput && !codigoInput.value) {
                        // Aguarda um pouco mais para garantir que tudo está pronto
                        setTimeout(function() {
                            gerarCodigoReferencia(categoriaId);
                        }, 300);
                    }
                }
            }
        }
    }, 500);
});
</script>