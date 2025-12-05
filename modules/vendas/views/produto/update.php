<?php

use yii\helpers\Html;

$this->title = 'Editar Produto: ' . $model->nome;
$this->params['breadcrumbs'][] = ['label' => 'Produtos', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->nome, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Editar';
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        
        <!-- ✅ Mensagens Flash Melhoradas (fixas no topo) -->
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div id="flash-success" class="mb-4 bg-green-50 border-l-4 border-green-500 text-green-800 px-4 py-3 rounded-lg shadow-lg flex items-start sticky top-4 z-50">
                <svg class="w-6 h-6 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <p class="font-bold text-lg">✅ Sucesso!</p>
                    <p class="text-sm mt-1"><?= Yii::$app->session->getFlash('success') ?></p>
                </div>
                <button onclick="document.getElementById('flash-success').remove()" class="ml-4 text-green-600 hover:text-green-800">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div id="flash-error" class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-800 px-4 py-3 rounded-lg shadow-lg flex items-start sticky top-4 z-50">
                <svg class="w-6 h-6 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <p class="font-bold text-lg">❌ Erro!</p>
                    <p class="text-sm mt-1 whitespace-pre-line"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></p>
                </div>
                <button onclick="document.getElementById('flash-error').remove()" class="ml-4 text-red-600 hover:text-red-800">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('warning')): ?>
            <div id="flash-warning" class="mb-4 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800 px-4 py-3 rounded-lg shadow-lg flex items-start sticky top-4 z-50">
                <svg class="w-6 h-6 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <p class="font-bold text-lg">⚠️ Atenção!</p>
                    <p class="text-sm mt-1"><?= Yii::$app->session->getFlash('warning') ?></p>
                </div>
                <button onclick="document.getElementById('flash-warning').remove()" class="ml-4 text-yellow-600 hover:text-yellow-800">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-yellow-500 px-6 py-4">
                <h2 class="text-2xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <?= Html::encode($this->title) ?>
                </h2>
            </div>
            
            <div class="p-6">
                <?= $this->render('_form', [
                    'model' => $model,
                ]) ?>
            </div>
        </div>

    </div>
</div>

<!-- ✅ Script para manter mensagens visíveis e destacadas -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mantém mensagens flash visíveis por mais tempo (15 segundos)
    const flashMessages = ['flash-success', 'flash-error', 'flash-warning'];
    
    flashMessages.forEach(function(id) {
        const element = document.getElementById(id);
        if (element) {
            // Adiciona animação de entrada
            element.style.animation = 'slideInDown 0.5s ease-out';
            
            // Auto-fecha após 15 segundos (em vez de desaparecer imediatamente)
            setTimeout(function() {
                if (element && element.parentNode) {
                    element.style.animation = 'fadeOut 0.5s ease-out';
                    setTimeout(function() {
                        if (element && element.parentNode) {
                            element.remove();
                        }
                    }, 500);
                }
            }, 15000); // 15 segundos
            
            // Scroll suave para a mensagem
            element.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
    
    // Adiciona estilos CSS para animações
    if (!document.getElementById('flash-animations-style')) {
        const style = document.createElement('style');
        style.id = 'flash-animations-style';
        style.textContent = `
            @keyframes slideInDown {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            @keyframes fadeOut {
                from {
                    opacity: 1;
                }
                to {
                    opacity: 0;
                }
            }
            #flash-success, #flash-error, #flash-warning {
                animation: slideInDown 0.5s ease-out;
            }
        `;
        document.head.appendChild(style);
    }
});
</script>