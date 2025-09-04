<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\DefinicaoIndicador */

$this->title = 'Editar: ' . $model->nome_indicador;
$this->params['breadcrumbs'][] = ['label' => 'Indicadores', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->nome_indicador, 'url' => ['view', 'id' => $model->id_indicador]];
$this->params['breadcrumbs'][] = 'Editar';
?>

<div class="definicao-indicador-update min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <!-- Botão Voltar -->
                        <?= Html::a('', ['view', 'id' => $model->id_indicador], [
                            'class' => 'inline-flex items-center p-2 border border-transparent rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500',
                            'title' => 'Voltar para detalhes'
                        ]) ?>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        
                        <div class="flex-1 min-w-0">
                            <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">
                                Editar Indicador
                            </h1>
                            <p class="mt-1 text-sm text-gray-500">
                                <?= Html::encode($model->cod_indicador) ?> • 
                                Versão <?= $model->versao ?> •
                                <span class="<?= $model->ativo ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= $model->ativo ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Ações Rápidas -->
                    <div class="flex items-center space-x-3">
                        <!-- Histórico de Alterações -->
                        <button type="button" id="history-btn" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Histórico
                        </button>
                        
                        <!-- Preview -->
                        <?= Html::a('Preview', ['view', 'id' => $model->id_indicador], [
                            'class' => 'inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500',
                            'target' => '_blank'
                        ]) ?>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Container -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white shadow-xl rounded-lg">
            
            <!-- Alert de Informações Importantes -->
            <?php if ($model->data_fim_validade && strtotime($model->data_fim_validade) < time()): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-t-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <strong>Atenção:</strong> Este indicador expirou em <?= Yii::$app->formatter->asDate($model->data_fim_validade) ?>. 
                                Considere atualizar a data de validade ou criar uma nova versão.
                            </p>
                        </div>
                    </div>
                </div>
            <?php elseif ($model->data_fim_validade && strtotime($model->data_fim_validade) < strtotime('+30 days')): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-t-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>Aviso:</strong> Este indicador expirará em <?= Yii::$app->formatter->asDate($model->data_fim_validade) ?>. 
                                Planeje as atualizações necessárias.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="px-6 py-8 sm:px-8">
                
                <!-- Informações da Última Edição -->
                <div class="mb-8 bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">
                                Informações da Edição
                            </h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>
                                    <strong>Última atualização:</strong> 
                                    <?= Yii::$app->formatter->asDatetime($model->data_atualizacao) ?>
                                </p>
                                <?php if ($model->responsavel_tecnico): ?>
                                    <p class="mt-1">
                                        <strong>Responsável técnico:</strong> <?= Html::encode($model->responsavel_tecnico) ?>
                                    </p>
                                <?php endif; ?>
                                <div class="mt-3">
                                    <p class="font-medium">Dicas para edição:</p>
                                    <ul class="list-disc list-inside mt-1 space-y-1">
                                        <li>Alterar informações críticas pode impactar análises existentes</li>
                                        <li>Considere criar uma nova versão para mudanças significativas</li>
                                        <li>Documente as alterações no campo de observações</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Opções de Versionamento -->
                <div class="mb-6 flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <input type="checkbox" id="create-new-version" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="create-new-version" class="ml-2 block text-sm text-gray-900">
                            Criar nova versão (recomendado para mudanças significativas)
                        </label>
                    </div>
                    <div class="text-sm text-gray-500">
                        Versão atual: <?= $model->versao ?>
                    </div>
                </div>

                <!-- Form -->
                <?= $this->render('_form', [
                    'model' => $model,
                ]) ?>
                
            </div>
        </div>
        
        <!-- Painel de Comparação (lateral) -->
        <div id="comparison-panel" class="hidden fixed top-0 right-0 h-full w-1/3 bg-white shadow-2xl border-l border-gray-200 z-40 overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Comparar Alterações</h3>
                    <button type="button" id="close-comparison" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="comparison-content">
                    <!-- Conteúdo de comparação será carregado via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Histórico -->
<div id="history-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Histórico de Alterações</h3>
            <button type="button" class="close-history text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="history-content" class="max-h-96 overflow-y-auto">
            <!-- Timeline do histórico -->
            <div class="flow-root">
                <ul class="-mb-8">
                    <li>
                        <div class="relative pb-8">
                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"></span>
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                                        <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                    <div>
                                        <p class="text-sm text-gray-500">
                                            Indicador criado por <a href="#" class="font-medium text-gray-900">João Silva</a>
                                        </p>
                                    </div>
                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                        <?= Yii::$app->formatter->asRelativeTime($model->data_criacao) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    
                    <?php if ($model->data_atualizacao != $model->data_criacao): ?>
                        <li>
                            <div class="relative pb-8">
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                            <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                            </svg>
                                        </span>
                                    </div>
                                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p class="text-sm text-gray-500">
                                                Última atualização por <a href="#" class="font-medium text-gray-900">Sistema</a>
                                            </p>
                                        </div>
                                        <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                            <?= Yii::$app->formatter->asRelativeTime($model->data_atualizacao) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJs("
// Sistema de versionamento
$('#create-new-version').on('change', function() {
    if ($(this).is(':checked')) {
        // Incrementar versão
        const currentVersion = parseInt($('input[name*=\"versao\"]').val()) || 1;
        $('input[name*=\"versao\"]').val(currentVersion + 1);
        
        // Mostrar aviso
        if (!$('.version-warning').length) {
            $('input[name*=\"versao\"]').closest('.field').after(
                '<div class=\"version-warning mt-2 p-3 bg-blue-50 border border-blue-200 rounded-md\">' +
                '<p class=\"text-sm text-blue-700\">Uma nova versão será criada. A versão atual permanecerá no histórico.</p>' +
                '</div>'
            );
        }
    } else {
        // Voltar à versão original
        const originalVersion = parseInt($('input[name*=\"versao\"]').data('original-value')) || 1;
        $('input[name*=\"versao\"]').val(originalVersion);
        $('.version-warning').remove();
    }
});

// Salvar valor original da versão
$(document).ready(function() {
    const versionInput = $('input[name*=\"versao\"]');
    versionInput.data('original-value', versionInput.val());
});

// Modal de histórico
$('#history-btn').on('click', function() {
    $('#history-modal').removeClass('hidden');
});

$('.close-history, #history-modal').on('click', function(e) {
    if (e.target === this) {
        $('#history-modal').addClass('hidden');
    }
});

// Painel de comparação
$('#compare-btn').on('click', function() {
    $('#comparison-panel').removeClass('hidden');
    // Carregar dados de comparação via AJAX
    loadComparisonData();
});

$('#close-comparison').on('click', function() {
    $('#comparison-panel').addClass('hidden');
});

function loadComparisonData() {
    // Implementar carregamento de dados para comparação
    $('#comparison-content').html('<div class=\"animate-pulse\">Carregando comparação...</div>');
    
    // Simular carregamento
    setTimeout(() => {
        $('#comparison-content').html(`
            <div class=\"space-y-4\">
                <div class=\"border-l-4 border-red-400 pl-4\">
                    <h4 class=\"text-sm font-medium text-red-800\">Alterações detectadas:</h4>
                    <ul class=\"mt-2 text-sm text-red-700 space-y-1\">
                        <li>• Nome do indicador modificado</li>
                        <li>• Descrição atualizada</li>
                        <li>• Método de cálculo alterado</li>
                    </ul>
                </div>
                <div class=\"border-l-4 border-green-400 pl-4\">
                    <h4 class=\"text-sm font-medium text-green-800\">Campos adicionados:</h4>
                    <ul class=\"mt-2 text-sm text-green-700 space-y-1\">
                        <li>• Nova observação técnica</li>
                    </ul>
                </div>
            </div>
        `);
    }, 1000);
}

// Aviso para alterações críticas
const criticalFields = ['metodo_calculo', 'polaridade', 'id_unidade_medida'];
criticalFields.forEach(field => {
    $('[name*=\"' + field + '\"]').on('change', function() {
        if (!$('.critical-change-warning').length) {
            $(this).closest('.field').after(
                '<div class=\"critical-change-warning mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-md\">' +
                '<div class=\"flex\">' +
                '<svg class=\"h-5 w-5 text-yellow-400 mr-2\" fill=\"currentColor\" viewBox=\"0 0 20 20\">' +
                '<path fill-rule=\"evenodd\" d=\"M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z\" clip-rule=\"evenodd\"></path>' +
                '</svg>' +
                '<p class=\"text-sm text-yellow-700\"><strong>Atenção:</strong> Esta alteração pode impactar análises e relatórios existentes.</p>' +
                '</div>' +
                '</div>'
            );
        }
    });
});

// Auto-save melhorado para edição
let autoSaveTimer;
let hasUnsavedChanges = false;

$('#indicador-form input, #indicador-form textarea, #indicador-form select').on('input change', function() {
    hasUnsavedChanges = true;
    clearTimeout(autoSaveTimer);
    
    // Auto-save após 10 segundos de inatividade
    autoSaveTimer = setTimeout(() => {
        saveAsDraft();
    }, 10000);
});

function saveAsDraft() {
    const formData = $('#indicador-form').serialize();
    // Implementar auto-save
    console.log('Rascunho salvo automaticamente');
    
    // Mostrar indicador visual
    showNotification('Alterações salvas automaticamente', 'info');
}

function showNotification(message, type) {
    const colors = {
        info: 'bg-blue-500',
        success: 'bg-green-500',
        warning: 'bg-yellow-500',
        error: 'bg-red-500'
    };
    
    const notification = $(`
        <div class=\"fixed top-4 right-4 z-50 px-4 py-2 rounded-md text-white shadow-lg transform transition-all duration-300 translate-x-full opacity-0 ${colors[type] || colors.info}\">
            ${message}
        </div>
    `);
    
    $('body').append(notification);
    
    setTimeout(() => {
        notification.removeClass('translate-x-full opacity-0');
    }, 100);
    
    setTimeout(() => {
        notification.addClass('translate-x-full opacity-0');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
");

// CSS específico para a página de edição
$this->registerCss("
/* Animações para painéis */
#comparison-panel {
    transform: translateX(100%);
    transition: transform 0.3s ease-in-out;
}

#comparison-panel:not(.hidden) {
    transform: translateX(0);
}

/* Destaque para campos alterados */
.field-changed input,
.field-changed textarea,
.field-changed select {
    border-color: #f59e0b;
    box-shadow: 0 0 0 1px #f59e0b;
}

/* Indicador de versão */
.version-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    background-color: #e0e7ff;
    color: #3730a3;
}

/* Animação para warnings */
.critical-change-warning,
.version-warning {
    animation: slideInAlert 0.3s ease-out;
}

@keyframes slideInAlert {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Status de salvamento */
.save-status {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    z-index: 30;
}

.save-status.saving {
    background-color: #fbbf24;
    color: #92400e;
}

.save-status.saved {
    background-color: #10b981;
    color: #064e3b;
}
");
?>