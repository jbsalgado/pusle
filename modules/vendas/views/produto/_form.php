<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\vendas\models\Categoria;

?>

<div class="produto-form">

    <?php $form = ActiveForm::begin([
        'options' => ['enctype' => 'multipart/form-data'],
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

        <!-- Preços e Estoque -->
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

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Estoque Atual (un)</label>
                <?= $form->field($model, 'estoque_atual')->textInput([
                    'type' => 'number',
                    'min' => '0',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => '0'
                ])->label(false) ?>
            </div>
        </div>

        <!-- Margem de Lucro (calculada) -->
        <div id="margem-container" class="hidden">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700">Margem de Lucro:</span>
                    <span id="margem-valor" class="text-lg font-bold text-blue-600">0.00%</span>
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
                                <?= Html::a('Definir Principal', ['set-foto-principal', 'id' => $foto->id], [
                                    'class' => 'absolute top-2 left-2 px-2 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300',
                                    'data-method' => 'post'
                                ]) ?>
                            <?php endif; ?>

                            <?= Html::a('✕', ['delete-foto', 'id' => $foto->id], [
                                'class' => 'absolute top-2 right-2 w-6 h-6 flex items-center justify-center bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300',
                                'data' => [
                                    'confirm' => 'Tem certeza que deseja excluir esta foto?',
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
                ['class' => 'flex-1 inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300']
            ) ?>
            <?= Html::a('Cancelar', ['index'], 
                ['class' => 'flex-1 text-center px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold rounded-lg transition duration-300']) ?>
        </div>

    </div>

    <?php ActiveForm::end(); ?>

</div>

<script>
// Calcular margem em tempo real
document.addEventListener('DOMContentLoaded', function() {
    const custoInput = document.getElementById('preco-custo');
    const vendaInput = document.getElementById('preco-venda');
    const margemContainer = document.getElementById('margem-container');
    const margemValor = document.getElementById('margem-valor');
    
    function calcularMargem() {
        const custo = parseFloat(custoInput.value) || 0;
        const venda = parseFloat(vendaInput.value) || 0;
        
        if (custo > 0 && venda > 0) {
            const margem = ((venda - custo) / custo * 100).toFixed(2);
            margemValor.textContent = margem + '%';
            margemContainer.classList.remove('hidden');
        } else {
            margemContainer.classList.add('hidden');
        }
    }
    
    if (custoInput && vendaInput) {
        custoInput.addEventListener('input', calcularMargem);
        vendaInput.addEventListener('input', calcularMargem);
        calcularMargem(); // Calcular ao carregar se houver valores
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