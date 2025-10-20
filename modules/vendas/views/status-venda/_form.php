<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\StatusVenda */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="status-venda-form">

    <?php $form = ActiveForm::begin([
        'id' => 'status-venda-form',
        'options' => ['class' => 'space-y-6'],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{hint}\n{error}",
            'labelOptions' => ['class' => 'block text-sm font-semibold text-gray-700 mb-2'],
            'inputOptions' => ['class' => 'w-full px-4 py-2.5 text-sm text-gray-900 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-150'],
            'errorOptions' => ['class' => 'mt-2 text-sm text-red-600'],
            'hintOptions' => ['class' => 'mt-2 text-sm text-gray-500'],
        ],
    ]); ?>

    <div class="space-y-6">
        
        <!-- Código do Status -->
        <div class="relative">
            <?= $form->field($model, 'codigo')->textInput([
                'maxlength' => true,
                'readonly' => !$model->isNewRecord,
                'placeholder' => 'Ex: EM_ABERTO, CONCLUIDA, CANCELADA',
                'class' => 'w-full px-4 py-2.5 text-sm text-gray-900 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-150 ' . 
                    (!$model->isNewRecord ? 'bg-gray-100 cursor-not-allowed' : ''),
            ])->label('Código do Status <span class="text-red-500">*</span>')->hint(
                !$model->isNewRecord 
                    ? '<span class="flex items-center text-amber-600"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>O código não pode ser alterado após a criação</span>'
                    : 'Use letras maiúsculas e sublinhado. Ex: VENDA_CONCLUIDA'
            ) ?>
            
            <?php if (!$model->isNewRecord): ?>
                <div class="absolute top-0 right-0 mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Bloqueado
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Descrição -->
        <div>
            <?= $form->field($model, 'descricao')->textInput([
                'maxlength' => true,
                'placeholder' => 'Ex: Venda em Aberto, Venda Concluída',
            ])->label('Descrição do Status <span class="text-red-500">*</span>')->hint('Nome descritivo do status que será exibido no sistema') ?>
        </div>

    </div>

    <!-- Divider -->
    <div class="border-t border-gray-200 pt-6 mt-8">
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3 space-y-3 space-y-reverse sm:space-y-0">
            
            <!-- Botão Cancelar -->
            <a href="<?= \yii\helpers\Url::to(['index']) ?>" 
               class="inline-flex justify-center items-center px-6 py-2.5 text-sm font-semibold text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Cancelar
            </a>

            <!-- Botão Guardar -->
            <?= Html::submitButton(
                '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>' . ($model->isNewRecord ? 'Criar Status' : 'Guardar Alterações') . '</span>',
                [
                    'class' => 'inline-flex justify-center items-center px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg shadow-md hover:from-blue-700 hover:to-blue-800 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform hover:-translate-y-0.5 transition-all duration-150',
                    'id' => 'submit-button'
                ]
            ) ?>

        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<style>
/* Melhorias visuais para campos do formulário */
.status-venda-form input:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.status-venda-form .has-error input {
    border-color: #ef4444;
}

.status-venda-form .has-error input:focus {
    ring-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

/* Animação no botão de submit */
#submit-button:active {
    transform: translateY(0) !important;
}

/* Estilo para campos readonly */
input[readonly] {
    background-color: #f9fafb !important;
    cursor: not-allowed !important;
}

/* Mobile improvements */
@media (max-width: 640px) {
    .status-venda-form {
        font-size: 0.9375rem;
    }
}
</style>

<script>
// Adicionar validação visual em tempo real
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('status-venda-form');
    const submitButton = document.getElementById('submit-button');
    
    if (form && submitButton) {
        form.addEventListener('submit', function() {
            // Desabilita o botão e mostra loading
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                A processar...
            `;
        });

        // Validação do código (apenas letras maiúsculas, números e underscore)
        const codigoInput = document.querySelector('input[name="StatusVenda[codigo]"]');
        if (codigoInput && !codigoInput.readOnly) {
            codigoInput.addEventListener('input', function(e) {
                let value = e.target.value;
                // Remove caracteres não permitidos e converte para maiúsculas
                e.target.value = value.toUpperCase().replace(/[^A-Z0-9_]/g, '');
            });
        }
    }
});
</script>