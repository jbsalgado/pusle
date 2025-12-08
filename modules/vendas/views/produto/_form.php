<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use app\modules\vendas\models\Categoria;

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
            <?= $form->field($model, 'categoria_id')->dropDownList(
                Categoria::getListaDropdown(),
                [
                    'prompt' => 'Selecione uma categoria',
                    'class' => 'w-full px-3 py-2.5 sm:px-4 sm:py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'id' => 'categoria-select'
                ]
            )->label(false) ?>
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
                    'class' => 'w-full px-3 py-2.5 sm:px-4 sm:py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'placeholder' => '0.00',
                    'id' => 'preco-custo'
                ])->label(false) ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Valor do Frete (R$)</label>
                <?= $form->field($model, 'valor_frete')->textInput([
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => '0',
                    'class' => 'w-full px-3 py-2.5 sm:px-4 sm:py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'placeholder' => '0.00',
                    'id' => 'valor-frete'
                ])->label(false) ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Preço de Venda (R$) *</label>
                <?= $form->field($model, 'preco_venda_sugerido')->textInput([
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => '0',
                    'class' => 'w-full px-3 py-2.5 sm:px-4 sm:py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'placeholder' => '0.00',
                    'id' => 'preco-venda'
                ])->label(false) ?>
            </div>
        </div>

        <!-- Margem e Markup (calculados automaticamente) -->
        <div id="margem-markup-container" class="hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="text-sm font-medium text-gray-700 block">Margem de Lucro</span>
                            <span class="text-xs text-gray-500">(sobre o preço de venda)</span>
                        </div>
                        <span id="margem-valor" class="text-lg font-bold text-blue-600">0.00%</span>
                    </div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="text-sm font-medium text-gray-700 block">Markup</span>
                            <span class="text-xs text-gray-500">(sobre o custo)</span>
                        </div>
                        <span id="markup-valor" class="text-lg font-bold text-green-600">0.00%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Campos ocultos para salvar margem e markup calculados -->
        <?= $form->field($model, 'margem_lucro_percentual')->hiddenInput(['id' => 'margem-lucro-percentual'])->label(false) ?>
        <?= $form->field($model, 'markup_percentual')->hiddenInput(['id' => 'markup-percentual'])->label(false) ?>

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

        <!-- Opção: Calcular preço por margem desejada -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 sm:p-4">
            <div class="flex items-start sm:items-center mb-3 gap-2">
                <input type="checkbox" id="calcular-por-margem" class="mt-1 sm:mt-0 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 flex-shrink-0">
                <label for="calcular-por-margem" class="text-sm sm:text-base font-medium text-gray-700 cursor-pointer">
                    Calcular preço de venda pela margem desejada
                </label>
            </div>
            <div id="margem-desejada-container" class="hidden">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Margem Desejada (%)</label>
                        <input type="number" 
                               id="margem-desejada" 
                               step="0.01" 
                               min="0" 
                               max="99.99"
                               class="w-full px-3 py-2.5 sm:px-4 sm:py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               placeholder="Ex: 30 para 30%">
                    </div>
                    <div class="flex items-end">
                        <button type="button" 
                                id="btn-calcular-preco" 
                                class="w-full px-4 py-2.5 sm:py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold rounded-lg transition-colors duration-200 text-sm sm:text-base">
                            Calcular Preço
                        </button>
                    </div>
                </div>
            </div>
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
    let descricaoUsuarioOriginal = ''; // Armazena a parte do usuário original
    
    /**
     * Extrai a parte do usuário da descrição (remove o nome do produto)
     */
    function extrairParteUsuario(nome, descricao) {
        if (!nome || !descricao) {
            return descricao || '';
        }
        
        const prefixo = nome + ' - ';
        if (descricao.startsWith(prefixo)) {
            return descricao.substring(prefixo.length);
        } else if (descricao === nome) {
            return '';
        } else if (descricao.startsWith(nome)) {
            // Se começa com nome mas sem ' - ', assume que é tudo do usuário
            return descricao.substring(nome.length).replace(/^[\s-]+/, '');
        }
        
        // Se não começa com o nome, assume que é tudo do usuário
        return descricao;
    }
    
    /**
     * Atualiza a descrição concatenando nome + ' - ' + descrição do usuário
     */
    function atualizarDescricaoComNome() {
        if (!produtoNomeInput || !produtoDescricaoInput || isUpdatingDescricao) {
            return;
        }
        
        isUpdatingDescricao = true;
        
        const nome = produtoNomeInput.value.trim();
        const descricaoAtual = produtoDescricaoInput.value.trim();
        
        // Se não há nome, mantém a descrição como está
        if (!nome) {
            isUpdatingDescricao = false;
            return;
        }
        
        // Extrai a parte do usuário
        const parteUsuario = extrairParteUsuario(nome, descricaoAtual);
        descricaoUsuarioOriginal = parteUsuario;
        
        // Constrói a descrição final
        let descricaoFinal = nome;
        if (parteUsuario) {
            descricaoFinal = nome + ' - ' + parteUsuario;
        }
        
        // Atualiza apenas se realmente mudou
        if (produtoDescricaoInput.value !== descricaoFinal) {
            produtoDescricaoInput.value = descricaoFinal;
        }
        
        isUpdatingDescricao = false;
    }
    
    // Atualiza descrição quando o nome muda (apenas no blur para evitar loops)
    if (produtoNomeInput && produtoDescricaoInput) {
        produtoNomeInput.addEventListener('blur', function() {
            atualizarDescricaoComNome();
        });
        
        // Quando o usuário digita na descrição, preserva apenas a parte após ' - '
        produtoDescricaoInput.addEventListener('input', function() {
            if (isUpdatingDescricao) {
                return;
            }
            
            const nome = produtoNomeInput.value.trim();
            const descricaoAtual = this.value.trim();
            
            if (nome) {
                const parteUsuario = extrairParteUsuario(nome, descricaoAtual);
                descricaoUsuarioOriginal = parteUsuario;
                
                // Reconstrói com o nome se necessário
                const prefixo = nome + ' - ';
                if (!descricaoAtual.startsWith(prefixo) && descricaoAtual !== nome) {
                    isUpdatingDescricao = true;
                    if (parteUsuario) {
                        this.value = nome + ' - ' + parteUsuario;
                    } else {
                        this.value = nome;
                    }
                    isUpdatingDescricao = false;
                }
            }
        });
        
        // Inicializa ao carregar (para edição)
        if (produtoNomeInput.value && produtoDescricaoInput.value) {
            const nome = produtoNomeInput.value.trim();
            const descricao = produtoDescricaoInput.value.trim();
            
            // Se a descrição não começa com o nome, adiciona
            if (nome && !descricao.startsWith(nome)) {
                const parteUsuario = descricao;
                if (parteUsuario) {
                    produtoDescricaoInput.value = nome + ' - ' + parteUsuario;
                } else {
                    produtoDescricaoInput.value = nome;
                }
                descricaoUsuarioOriginal = parteUsuario;
            } else if (nome && descricao.startsWith(nome + ' - ')) {
                // Extrai a parte do usuário
                descricaoUsuarioOriginal = descricao.substring((nome + ' - ').length);
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
            
            margemValor.textContent = margem.toFixed(2) + '%';
            markupValor.textContent = markup.toFixed(2) + '%';
            
            // Atualizar campos ocultos
            if (margemLucroPercentualInput) margemLucroPercentualInput.value = margem;
            if (markupPercentualInput) markupPercentualInput.value = markup;
            
            margemMarkupContainer.classList.remove('hidden');
        } else {
            margemMarkupContainer.classList.add('hidden');
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
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => {
            dataTransfer.items.add(file);
        });
        fotosInput.files = dataTransfer.files;
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
            hideError();
            
            // Tenta acessar a câmera traseira primeiro (environment), depois frontal (user)
            const constraints = {
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            };
            
            stream = await navigator.mediaDevices.getUserMedia(constraints);
            cameraVideo.srcObject = stream;
            cameraVideo.play();
            
            // Mostra o vídeo e esconde o preview
            cameraVideo.classList.remove('hidden');
            cameraPreview.classList.add('hidden');
            btnCapture.classList.remove('hidden');
            btnRetake.classList.add('hidden');
            btnUsePhoto.classList.add('hidden');
            
        } catch (err) {
            console.error('Erro ao acessar câmera:', err);
            showError('Não foi possível acessar a câmera. Verifique as permissões do navegador.');
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
        cameraModal.classList.remove('hidden');
        startCamera();
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
    
    // Event listeners
    if (btnCamera) {
        btnCamera.addEventListener('click', openCamera);
    }
    
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
});
</script>