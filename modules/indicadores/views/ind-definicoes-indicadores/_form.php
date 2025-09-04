<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\DefinicaoIndicador */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="definicao-indicador-form">

    <?php $form = ActiveForm::begin([
        'id' => 'indicador-form',
        'options' => ['class' => 'space-y-8 divide-y divide-gray-200'],
        'fieldConfig' => [
            'template' => '{label}<div class="mt-1">{input}</div>{error}',
            'labelOptions' => ['class' => 'block text-sm font-medium text-gray-700 mb-1'],
            'inputOptions' => ['class' => 'shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md'],
            'errorOptions' => ['class' => 'mt-2 text-sm text-red-600'],
        ],
    ]); ?>

    <!-- Seção: Informações Básicas -->
    <div class="pt-8">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">Informações Básicas</h3>
            <p class="mt-1 text-sm text-gray-500">Dados essenciais do indicador.</p>
        </div>
        <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
            
            <div class="sm:col-span-2">
                <?= $form->field($model, 'cod_indicador')->textInput([
                    'maxlength' => true,
                    'placeholder' => 'Ex: IND001'
                ]) ?>
            </div>

            <div class="sm:col-span-2">
                <?= $form->field($model, 'tipo_especifico')->dropDownList(
                    $model->getTipoEspecificoOptions(),
                    ['prompt' => 'Selecione o tipo...']
                ) ?>
            </div>

            <div class="sm:col-span-2">
                <?= $form->field($model, 'versao')->textInput([
                    'type' => 'number',
                    'min' => 1
                ]) ?>
            </div>

            <div class="sm:col-span-6">
                <?= $form->field($model, 'nome_indicador')->textInput([
                    'maxlength' => true,
                    'placeholder' => 'Digite o nome completo do indicador'
                ]) ?>
            </div>

            <div class="sm:col-span-6">
                <?= $form->field($model, 'descricao_completa')->textarea([
                    'rows' => 4,
                    'placeholder' => 'Descrição detalhada do indicador, seus objetivos e aplicações'
                ]) ?>
            </div>

            <div class="sm:col-span-6">
                <?= $form->field($model, 'conceito')->textarea([
                    'rows' => 3,
                    'placeholder' => 'Definição conceitual do indicador'
                ]) ?>
            </div>

            <div class="sm:col-span-6">
                <?= $form->field($model, 'justificativa')->textarea([
                    'rows' => 3,
                    'placeholder' => 'Justificativa para a criação e uso deste indicador'
                ]) ?>
            </div>

        </div>
    </div>

    <!-- Seção: Configurações Técnicas -->
    <div class="pt-8">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">Configurações Técnicas</h3>
            <p class="mt-1 text-sm text-gray-500">Parâmetros técnicos e metodológicos do indicador.</p>
        </div>
        <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">

            <div class="sm:col-span-2">
                <?= $form->field($model, 'id_unidade_medida')->dropDownList(
                    [], // Será preenchido via AJAX ou dados do controller
                    ['prompt' => 'Selecione a unidade...']
                ) ?>
            </div>

            <div class="sm:col-span-2">
                <?= $form->field($model, 'polaridade')->dropDownList(
                    $model->getPolaridadeOptions(),
                    ['prompt' => 'Selecione a polaridade...']
                ) ?>
            </div>

            <div class="sm:col-span-2">
                <?= $form->field($model, 'id_dimensao')->dropDownList(
                    [], // Será preenchido via AJAX ou dados do controller
                    ['prompt' => 'Selecione a dimensão...']
                ) ?>
            </div>

            <div class="sm:col-span-3">
                <?= $form->field($model, 'id_periodicidade_ideal_medicao')->dropDownList(
                    [], // Será preenchido via AJAX ou dados do controller
                    ['prompt' => 'Selecione...']
                ) ?>
            </div>

            <div class="sm:col-span-3">
                <?= $form->field($model, 'id_periodicidade_ideal_divulgacao')->dropDownList(
                    [], // Será preenchido via AJAX ou dados do controller
                    ['prompt' => 'Selecione...']
                ) ?>
            </div>

            <div class="sm:col-span-6">
                <?= $form->field($model, 'id_fonte_padrao')->dropDownList(
                    [], // Será preenchido via AJAX ou dados do controller
                    ['prompt' => 'Selecione a fonte padrão...']
                ) ?>
            </div>

            <div class="sm:col-span-6">
                <?= $form->field($model, 'metodo_calculo')->textarea([
                    'rows' => 4,
                    'placeholder' => 'Descreva detalhadamente como o indicador deve ser calculado'
                ]) ?>
            </div>

            <div class="sm:col-span-3">
                <?= $form->field($model, 'descricao_numerador')->textarea([
                    'rows' => 3,
                    'placeholder' => 'Descrição do numerador da fórmula'
                ]) ?>
            </div>

            <div class="sm:col-span-3">
                <?= $form->field($model, 'descricao_denominador')->textarea([
                    'rows' => 3,
                    'placeholder' => 'Descrição do denominador da fórmula'
                ]) ?>
            </div>

        </div>
    </div>

    <!-- Seção: Interpretação e Limitações -->
    <div class="pt-8">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">Interpretação e Limitações</h3>
            <p class="mt-1 text-sm text-gray-500">Como interpretar os resultados e quais são as limitações do indicador.</p>
        </div>
        <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">

            <div class="sm:col-span-6">
                <?= $form->field($model, 'interpretacao')->textarea([
                    'rows' => 4,
                    'placeholder' => 'Como interpretar os valores e resultados deste indicador'
                ]) ?>
            </div>

            <div class="sm:col-span-6">
                <?= $form->field($model, 'limitacoes')->textarea([
                    'rows' => 4,
                    'placeholder' => 'Limitações, restrições e cuidados na interpretação'
                ]) ?>
            </div>

            <div class="sm:col-span-6">
                <?= $form->field($model, 'observacoes_gerais')->textarea([
                    'rows' => 3,
                    'placeholder' => 'Observações adicionais relevantes'
                ]) ?>
            </div>

        </div>
    </div>

    <!-- Seção: Dados Administrativos -->
    <div class="pt-8">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">Dados Administrativos</h3>
            <p class="mt-1 text-sm text-gray-500">Informações sobre responsabilidade e vigência.</p>
        </div>
        <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">

            <div class="sm:col-span-3">
                <?= $form->field($model, 'responsavel_tecnico')->textInput([
                    'maxlength' => true,
                    'placeholder' => 'Nome do responsável técnico'
                ]) ?>
            </div>

            <div class="sm:col-span-3">
                <?= $form->field($model, 'nota_tecnica_url')->textInput([
                    'maxlength' => true,
                    'placeholder' => 'https://exemplo.com/nota-tecnica',
                    'type' => 'url'
                ]) ?>
            </div>

            <div class="sm:col-span-3">
                <?= $form->field($model, 'data_inicio_validade')->textInput([
                    'type' => 'date',
                    'value' => $model->data_inicio_validade ? date('Y-m-d', strtotime($model->data_inicio_validade)) : date('Y-m-d')
                ]) ?>
            </div>

            <div class="sm:col-span-3">
                <?= $form->field($model, 'data_fim_validade')->textInput([
                    'type' => 'date',
                    'value' => $model->data_fim_validade ? date('Y-m-d', strtotime($model->data_fim_validade)) : null
                ]) ?>
            </div>

            <div class="sm:col-span-6">
                <?= $form->field($model, 'palavras_chave')->textarea([
                    'rows' => 2,
                    'placeholder' => 'Palavras-chave separadas por vírgula (ex: saúde, qualidade, eficiência)'
                ]) ?>
            </div>

            <div class="sm:col-span-6">
                <fieldset class="space-y-5">
                    <legend class="text-sm font-medium text-gray-700">Status</legend>
                    <div class="relative flex items-start">
                        <div class="flex items-center h-5">
                            <?= $form->field($model, 'ativo')->checkbox([
                                'class' => 'focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded',
                                'template' => '{input}',
                                'checked' => $model->ativo !== false
                            ]) ?>
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="<?= Html::getInputId($model, 'ativo') ?>" class="font-medium text-gray-700">Indicador Ativo</label>
                            <p class="text-gray-500">O indicador estará disponível para uso no sistema</p>
                        </div>
                    </div>
                </fieldset>
            </div>

        </div>
    </div>

    <!-- Botões de Ação -->
    <div class="pt-8">
        <div class="flex justify-end space-x-3">
            <?= Html::a('Cancelar', ['index'], [
                'class' => 'bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
            ]) ?>
            
            <?= Html::submitButton($model->isNewRecord ? 'Criar Indicador' : 'Atualizar Indicador', [
                'class' => 'inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed',
                'id' => 'submit-btn'
            ]) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<!-- Loading overlay para o formulário -->
<div id="form-loading" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center space-x-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                <span class="text-gray-700">Salvando indicador...</span>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJs("
// Validação em tempo real
$('#indicador-form input, #indicador-form textarea, #indicador-form select').on('blur change', function() {
    const field = $(this);
    const fieldContainer = field.closest('.field');
    
    // Remove mensagens de erro anteriores
    fieldContainer.find('.text-red-600').remove();
    fieldContainer.removeClass('has-error');
    
    // Validações específicas
    if (field.attr('name').includes('nome_indicador') && field.val().length < 3) {
        showFieldError(fieldContainer, 'O nome deve ter pelo menos 3 caracteres');
    }
    
    if (field.attr('name').includes('cod_indicador') && field.val() && !/^[A-Z0-9_-]+$/.test(field.val())) {
        showFieldError(fieldContainer, 'Use apenas letras maiúsculas, números, hífen e underscore');
    }
    
    if (field.attr('type') === 'url' && field.val() && !/^https?:\\/\\/.+/.test(field.val())) {
        showFieldError(fieldContainer, 'URL deve começar com http:// ou https://');
    }
});

// Função para mostrar erro no campo
function showFieldError(container, message) {
    container.addClass('has-error');
    container.find('.mt-1').after('<div class=\"mt-2 text-sm text-red-600\">' + message + '</div>');
}

// Auto-save (rascunho) - opcional
let autoSaveTimeout;
$('#indicador-form input, #indicador-form textarea, #indicador-form select').on('input change', function() {
    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(function() {
        // Implementar auto-save se necessário
        console.log('Auto-save triggered');
    }, 30000); // 30 segundos
});

// Confirmação antes de sair da página se houver alterações
let formChanged = false;
$('#indicador-form input, #indicador-form textarea, #indicador-form select').on('change', function() {
    formChanged = true;
});

$(window).on('beforeunload', function(e) {
    if (formChanged) {
        const message = 'Você tem alterações não salvas. Deseja realmente sair?';
        e.returnValue = message;
        return message;
    }
});

// Remover aviso após submit
$('#indicador-form').on('submit', function() {
    formChanged = false;
    $('#form-loading').removeClass('hidden');
    $('#submit-btn').prop('disabled', true);
});

// Máscara para código do indicador
$('input[name*=\"cod_indicador\"]').on('input', function() {
    this.value = this.value.replace(/[^A-Z0-9_-]/g, '').toUpperCase();
});

// Contador de caracteres para campos de texto longos
$('textarea').each(function() {
    const textarea = $(this);
    const maxLength = textarea.attr('maxlength');
    
    if (maxLength) {
        const counter = $('<div class=\"mt-1 text-xs text-gray-500 text-right\">0/' + maxLength + '</div>');
        textarea.after(counter);
        
        textarea.on('input', function() {
            const current = $(this).val().length;
            counter.text(current + '/' + maxLength);
            
            if (current > maxLength * 0.9) {
                counter.addClass('text-yellow-600');
            }
            if (current >= maxLength) {
                counter.addClass('text-red-600').removeClass('text-yellow-600');
            }
            if (current < maxLength * 0.9) {
                counter.removeClass('text-yellow-600 text-red-600').addClass('text-gray-500');
            }
        });
        
        textarea.trigger('input'); // Inicializar contador
    }
});

// Expansão automática de textareas
$('textarea').on('input', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
});

// Validação de datas
$('input[name*=\"data_fim_validade\"]').on('change', function() {
    const dataFim = new Date($(this).val());
    const dataInicio = new Date($('input[name*=\"data_inicio_validade\"]').val());
    
    if (dataFim <= dataInicio) {
        showFieldError($(this).closest('.field'), 'Data fim deve ser posterior à data de início');
    }
});

$('input[name*=\"data_inicio_validade\"]').on('change', function() {
    const dataInicio = new Date($(this).val());
    const dataFimInput = $('input[name*=\"data_fim_validade\"]');
    const dataFim = new Date(dataFimInput.val());
    
    if (dataFimInput.val() && dataFim <= dataInicio) {
        showFieldError(dataFimInput.closest('.field'), 'Data fim deve ser posterior à data de início');
    }
});
");

// CSS customizado para o formulário
$this->registerCss("
.field.has-error input,
.field.has-error textarea,
.field.has-error select {
    border-color: #ef4444;
    box-shadow: 0 0 0 1px #ef4444;
}

.field.has-error input:focus,
.field.has-error textarea:focus,
.field.has-error select:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
}

textarea {
    resize: vertical;
    min-height: 80px;
}

.form-section {
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Loading state para botão submit */
#submit-btn:disabled {
    position: relative;
    overflow: hidden;
}

#submit-btn:disabled::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 16px;
    height: 16px;
    margin: -8px 0 0 -8px;
    border: 2px solid transparent;
    border-top: 2px solid #ffffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
");
?>