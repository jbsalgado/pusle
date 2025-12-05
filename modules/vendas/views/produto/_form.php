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

    <div class="space-y-6">
        
        <!-- Nome do Produto -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Produto *</label>
            <?= $form->field($model, 'nome')->textInput([
                'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                'placeholder' => 'Ex: Notebook Dell Inspiron'
            ])->label(false) ?>
        </div>

        <!-- Código e Categoria -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Código de Referência</label>
                <?= $form->field($model, 'codigo_referencia')->textInput([
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => 'Ex: NB-DELL-001'
                ])->label(false) ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Categoria *</label>
                <?= $form->field($model, 'categoria_id')->dropDownList(
                    Categoria::getListaDropdown(),
                    [
                        'prompt' => 'Selecione uma categoria',
                        'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'
                    ]
                )->label(false) ?>
            </div>
        </div>

        <!-- Descrição -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
            <?= $form->field($model, 'descricao')->textarea([
                'rows' => 4,
                'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                'placeholder' => 'Descreva as características do produto...'
            ])->label(false) ?>
        </div>

        <!-- Preços, Frete e Estoque -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Preço de Custo (R$)</label>
                <?= $form->field($model, 'preco_custo')->textInput([
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => '0',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => '0.00',
                    'id' => 'preco-custo'
                ])->label(false) ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Valor do Frete (R$)</label>
                <?= $form->field($model, 'valor_frete')->textInput([
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => '0',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => '0.00',
                    'id' => 'valor-frete'
                ])->label(false) ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Preço de Venda (R$) *</label>
                <?= $form->field($model, 'preco_venda_sugerido')->textInput([
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => '0',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Estoque Atual (un)</label>
                <?= $form->field($model, 'estoque_atual')->textInput([
                    'type' => 'number',
                    'min' => '0',
                    'step' => '1',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => '0',
                    'id' => 'produto-estoque-atual'
                ])->label(false) ?>
            </div>
        </div>

        <!-- Opção: Calcular preço por margem desejada -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <div class="flex items-center mb-3">
                <input type="checkbox" id="calcular-por-margem" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label for="calcular-por-margem" class="ml-2 text-sm font-medium text-gray-700">
                    Calcular preço de venda pela margem desejada
                </label>
            </div>
            <div id="margem-desejada-container" class="hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Margem Desejada (%)</label>
                        <input type="number" 
                               id="margem-desejada" 
                               step="0.01" 
                               min="0" 
                               max="99.99"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Ex: 30 para 30%">
                    </div>
                    <div class="flex items-end">
                        <button type="button" 
                                id="btn-calcular-preco" 
                                class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300">
                            Calcular Preço
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload de Fotos -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Fotos do Produto</label>
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition duration-300">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <div class="mt-4">
                    <label class="cursor-pointer">
                        <span class="mt-2 text-base leading-normal px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg inline-block transition duration-300">
                            Selecionar Fotos
                        </span>
                        <input type="file" name="fotos[]" multiple accept="image/*" class="hidden" id="fotos-input">
                    </label>
                </div>
                <p class="text-xs text-gray-500 mt-2">PNG, JPG, JPEG até 5MB cada</p>
            </div>
            <div id="preview-container" class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4"></div>
        </div>

        <!-- Fotos Existentes (se estiver editando) -->
        <?php if (!$model->isNewRecord && $model->fotos): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fotos Cadastradas</label>
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
        <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t">
            <?= Html::submitButton(
                $model->isNewRecord 
                    ? '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Cadastrar' 
                    : '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Salvar',
                [
                    'class' => 'flex-1 inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300',
                    'id' => 'btn-salvar-produto', // ✅ ID explícito para debug
                    'onclick' => 'console.log("🔍 Botão clicado via onclick"); return true;' // ✅ Debug direto
                ]
            ) ?>
            <?= Html::a('Cancelar', ['index'], 
                ['class' => 'flex-1 text-center px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold rounded-lg transition duration-300']) ?>
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

    // Preview de fotos
    const fotosInput = document.getElementById('fotos-input');
    const previewContainer = document.getElementById('preview-container');
    
    if (fotosInput) {
        fotosInput.addEventListener('change', function(e) {
            previewContainer.innerHTML = '';
            const files = e.target.files;
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative';
                    div.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-32 object-cover rounded-lg">
                        <span class="absolute top-2 left-2 px-2 py-1 bg-gray-900 bg-opacity-75 text-white text-xs rounded">
                            ${file.name}
                        </span>
                    `;
                    previewContainer.appendChild(div);
                }
                
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>