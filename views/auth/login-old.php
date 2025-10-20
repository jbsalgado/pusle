<?php
/**
 * View de Login - Módulo Vendas
 * Design moderno com TailwindCSS - Mobile First
 * Login por CPF e Senha
 * 
 * Localização: app/modules/vendas/views/auth/login.php
 * 
 * @var yii\web\View $this
 * @var app\modules\vendas\models\LoginForm $model
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\helpers\Url;

$this->title = 'Login - Sistema de Vendas';
?>

<!-- Container Principal -->
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 flex items-center justify-center p-4">
    
    <!-- Card de Login -->
    <div class="w-full max-w-md">
        
        <!-- Logo/Marca -->
        <div class="text-center mb-8 animate-fade-in">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl shadow-lg mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Bem-vindo de Volta</h1>
            <p class="text-gray-600">Entre para gerenciar suas vendas</p>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8 animate-slide-up">
            
            <?php $form = ActiveForm::begin([
                'id' => 'login-form',
                'options' => ['class' => 'space-y-6'],
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'block text-sm font-semibold text-gray-700 mb-2'],
                    'inputOptions' => ['class' => 'w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition duration-200 outline-none'],
                    'errorOptions' => ['class' => 'text-red-500 text-sm mt-1'],
                ],
            ]); ?>

            <!-- CPF -->
            <div class="relative">
                <?= $form->field($model, 'cpf')->textInput([
                    'placeholder' => '000.000.000-00',
                    'maxlength' => 14,
                    'autocomplete' => 'username',
                    'class' => 'w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition duration-200 outline-none',
                    'id' => 'cpf-input'
                ])->label('CPF') ?>
                <div class="absolute left-4 top-[38px] text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                    </svg>
                </div>
            </div>

            <!-- Senha -->
            <div class="relative">
                <?= $form->field($model, 'senha')->passwordInput([
                    'placeholder' => '••••••••',
                    'autocomplete' => 'current-password',
                    'class' => 'w-full pl-12 pr-12 py-3 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition duration-200 outline-none',
                    'id' => 'senha-input'
                ])->label('Senha') ?>
                <div class="absolute left-4 top-[38px] text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <!-- Toggle Mostrar/Ocultar Senha -->
                <button type="button" id="toggle-senha" class="absolute right-4 top-[38px] text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg id="eye-open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg id="eye-closed" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>

            <!-- Lembrar-me e Esqueci a Senha -->
            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center cursor-pointer">
                    <?= Html::activeCheckbox($model, 'lembrar_me', [
                        'class' => 'w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer',
                        'label' => false
                    ]) ?>
                    <span class="ml-2 text-gray-600">Lembrar-me</span>
                </label>
                <a href="<?= Url::to(['auth/forgot-password']) ?>" class="text-blue-600 hover:text-blue-700 font-medium">
                    Esqueci a senha
                </a>
            </div>

            <!-- Botão de Login -->
            <div>
                <?= Html::submitButton('Entrar', [
                    'class' => 'w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold py-3 px-4 rounded-xl hover:from-blue-600 hover:to-purple-700 focus:outline-none focus:ring-4 focus:ring-blue-300 transition duration-300 transform hover:scale-[1.02] active:scale-[0.98]',
                    'id' => 'btn-login'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>

            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-white text-gray-500">ou</span>
                </div>
            </div>

            <!-- Link para Cadastro -->
            <div class="text-center">
                <p class="text-gray-600">
                    Ainda não tem conta? 
                    <a href="<?= Url::to(['auth/signup']) ?>" class="text-blue-600 hover:text-blue-700 font-semibold">
                        Cadastre-se grátis
                    </a>
                </p>
            </div>

        </div>

        <!-- Rodapé -->
        <div class="text-center mt-8 text-sm text-gray-500">
            <p>© <?= date('Y') ?> Sistema de Vendas. Todos os direitos reservados.</p>
        </div>

    </div>

</div>

<?php
// JavaScript para funcionalidades
$this->registerJs("
// Máscara de CPF
document.getElementById('cpf-input').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    
    if (value.length <= 11) {
        if (value.length > 9) {
            value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '\$1.\$2.\$3-\$4');
        } else if (value.length > 6) {
            value = value.replace(/(\d{3})(\d{3})(\d{1,3})/, '\$1.\$2.\$3');
        } else if (value.length > 3) {
            value = value.replace(/(\d{3})(\d{1,3})/, '\$1.\$2');
        }
    }
    
    e.target.value = value;
});

// Ao submeter o form, remover formatação do CPF
document.getElementById('login-form').addEventListener('submit', function(e) {
    const cpfInput = document.getElementById('cpf-input');
    cpfInput.value = cpfInput.value.replace(/\D/g, '');
});

// Toggle mostrar/ocultar senha
document.getElementById('toggle-senha').addEventListener('click', function() {
    const senhaInput = document.getElementById('senha-input');
    const eyeOpen = document.getElementById('eye-open');
    const eyeClosed = document.getElementById('eye-closed');
    
    if (senhaInput.type === 'password') {
        senhaInput.type = 'text';
        eyeOpen.classList.add('hidden');
        eyeClosed.classList.remove('hidden');
    } else {
        senhaInput.type = 'password';
        eyeOpen.classList.remove('hidden');
        eyeClosed.classList.add('hidden');
    }
});

// Animação no botão de login ao submeter
document.getElementById('login-form').addEventListener('submit', function() {
    const btn = document.getElementById('btn-login');
    btn.innerHTML = '<svg class=\"animate-spin h-5 w-5 mx-auto\" xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\"><circle class=\"opacity-25\" cx=\"12\" cy=\"12\" r=\"10\" stroke=\"currentColor\" stroke-width=\"4\"></circle><path class=\"opacity-75\" fill=\"currentColor\" d=\"M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\"></path></svg>';
    btn.disabled = true;
});
", \yii\web\View::POS_END);

// CSS Customizado
$this->registerCss("
@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slide-up {
    from { 
        opacity: 0;
        transform: translateY(20px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.6s ease-out;
}

.animate-slide-up {
    animation: slide-up 0.6s ease-out;
}
");
?>