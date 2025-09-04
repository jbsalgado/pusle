<?php

use yii\helpers\Html;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $model app\models\DefinicaoIndicador */

$this->title = 'Novo Indicador';
$this->params['breadcrumbs'][] = ['label' => 'Indicadores', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
// Registra o CDN do Tailwind CSS diretamente nesta view
$this->registerJsFile('https://cdn.tailwindcss.com', ['position' => View::POS_HEAD]);
?>

<div class="definicao-indicador-create min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <div class="flex items-center space-x-4">
                    <!-- Botão Voltar -->
                    <?= Html::a('', ['index'], [
                        'class' => 'inline-flex items-center p-2 border border-transparent rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500',
                        'title' => 'Voltar para lista'
                    ]) ?>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    
                    <div class="flex-1 min-w-0">
                        <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">
                            <?= Html::encode($this->title) ?>
                        </h1>
                        <p class="mt-1 text-sm text-gray-500">
                            Criar um novo indicador no sistema
                        </p>
                    </div>
                    
                    <!-- Indicador de Progresso -->
                    <div class="hidden lg:flex items-center space-x-2 text-sm text-gray-500">
                        <span class="flex items-center">
                            <span class="w-2 h-2 bg-indigo-600 rounded-full mr-2"></span>
                            Criação
                        </span>
                        <span class="w-4 h-px bg-gray-300"></span>
                        <span class="flex items-center opacity-50">
                            <span class="w-2 h-2 bg-gray-300 rounded-full mr-2"></span>
                            Revisão
                        </span>
                        <span class="w-4 h-px bg-gray-300"></span>
                        <span class="flex items-center opacity-50">
                            <span class="w-2 h-2 bg-gray-300 rounded-full mr-2"></span>
                            Publicação
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Container -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white shadow-xl rounded-lg">
            <div class="px-6 py-8 sm:px-8">
                
                <!-- Dicas de Preenchimento -->
                <div class="mb-8 bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">
                                Dicas para criação do indicador
                            </h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Use um nome claro e descritivo para o indicador</li>
                                    <li>O código deve ser único e seguir o padrão da organização</li>
                                    <li>Descreva detalhadamente o método de cálculo</li>
                                    <li>Defina claramente a polaridade (maior/menor é melhor)</li>
                                    <li>Indique limitações e cuidados na interpretação</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <?= $this->render('_form', [
                    'model' => $model,
                ]) ?>
                
            </div>
        </div>
        
        <!-- Card de Ajuda Lateral (apenas desktop) -->
        <div class="hidden xl:block fixed top-1/2 right-4 transform -translate-y-1/2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 p-4">
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Central de Ajuda</h4>
            
            <div class="space-y-4 text-xs text-gray-600">
                <div>
                    <h5 class="font-medium text-gray-800 mb-1">Código do Indicador</h5>
                    <p>Use apenas letras maiúsculas, números e underscores. Ex: SAUDE_001</p>
                </div>
                
                <div>
                    <h5 class="font-medium text-gray-800 mb-1">Polaridade</h5>
                    <ul class="list-disc list-inside space-y-1 ml-2">
                        <li><strong>Maior Melhor:</strong> Quanto maior o valor, melhor</li>
                        <li><strong>Menor Melhor:</strong> Quanto menor o valor, melhor</li>
                        <li><strong>Dentro da Faixa:</strong> Há uma faixa ideal de valores</li>
                        <li><strong>Neutro:</strong> Não há interpretação de melhor/pior</li>
                    </ul>
                </div>
                
                <div>
                    <h5 class="font-medium text-gray-800 mb-1">Método de Cálculo</h5>
                    <p>Seja específico sobre fórmulas, filtros e condições. Use exemplos quando necessário.</p>
                </div>
            </div>
            
            <div class="mt-4 pt-4 border-t border-gray-200">
                <button type="button" id="help-contact" class="text-xs text-indigo-600 hover:text-indigo-500">
                    Precisa de mais ajuda? →
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Saída -->
<div id="exit-confirmation-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-yellow-100 rounded-full">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.732 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <div class="mt-2 px-7 py-3">
                <h3 class="text-lg font-medium text-gray-900 text-center">Confirmar Saída</h3>
                <p class="mt-2 text-sm text-gray-500 text-center">
                    Você tem alterações não salvas. Deseja realmente sair sem salvar?
                </p>
            </div>
            <div class="flex items-center px-4 py-3 space-x-2">
                <button id="confirm-exit" class="flex-1 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                    Sair sem Salvar
                </button>
                <button id="cancel-exit" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Continuar Editando
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJs("
// Sistema de ajuda contextual
$('#help-contact').on('click', function() {
    // Aqui você pode abrir um chat, modal de contato, ou redirecionar para documentação
    alert('Entre em contato com o suporte técnico:\\nEmail: suporte@exemplo.com\\nTelefone: (11) 1234-5678');
});

// Auto-scroll para campos com erro após validação
$(document).on('ajaxComplete', function(event, xhr, settings) {
    if (settings.url.includes('create') || settings.url.includes('update')) {
        const firstError = $('.field.has-error').first();
        if (firstError.length) {
            $('html, body').animate({
                scrollTop: firstError.offset().top - 100
            }, 500);
        }
    }
});

// Salvar como rascunho (funcionalidade futura)
let draftSaveTimeout;
function saveDraft() {
    const formData = $('#indicador-form').serialize();
    // Implementar salvamento de rascunho via AJAX
    console.log('Rascunho salvo automaticamente');
}

// Indicador visual de progresso do preenchimento
function updateProgressIndicator() {
    const requiredFields = $('#indicador-form').find('input[required], textarea[required], select[required]');
    const filledFields = requiredFields.filter(function() {
        return $(this).val().trim() !== '';
    });
    
    const progress = (filledFields.length / requiredFields.length) * 100;
    // Atualizar indicador visual se necessário
}

// Chamar quando campos mudarem
$('#indicador-form input, #indicador-form textarea, #indicador-form select').on('change', updateProgressIndicator);

// Inicializar indicador de progresso
updateProgressIndicator();

// Validação específica para novo indicador
$('#indicador-form').on('beforeSubmit', function(e) {
    e.preventDefault();
    
    // Validações personalizadas antes do envio
    let isValid = true;
    
    // Verificar se código já existe (via AJAX)
    const codigo = $('input[name*=\"cod_indicador\"]').val();
    if (codigo) {
        // Implementar verificação de unicidade via AJAX
    }
    
    if (isValid) {
        $(this).off('beforeSubmit').submit();
    }
    
    return false;
});
");

// CSS específico para a página de criação
$this->registerCss("
/* Animação para os cards de seção */
.form-section {
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInSection 0.6s ease-out forwards;
}

.form-section:nth-child(1) { animation-delay: 0.1s; }
.form-section:nth-child(2) { animation-delay: 0.2s; }
.form-section:nth-child(3) { animation-delay: 0.3s; }
.form-section:nth-child(4) { animation-delay: 0.4s; }

@keyframes fadeInSection {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Estilo para campos obrigatórios */
.required > label::after {
    content: ' *';
    color: #ef4444;
}

/* Hover effect para o card de ajuda */
.xl\\:block:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
}

/* Indicador de progresso visual */
.progress-indicator {
    height: 4px;
    background: linear-gradient(to right, #4f46e5 var(--progress, 0%), #e5e7eb var(--progress, 0%));
    transition: all 0.3s ease;
}

/* Efeitos de foco aprimorados */
.focus-ring:focus {
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    transform: scale(1.02);
    transition: all 0.2s ease;
}
");
?>