<?php
/**
 * View de Cadastro/Registro - Módulo Vendas
 * Design moderno com TailwindCSS - Mobile First
 * Cadastro com CPF, Telefone obrigatórios e Email opcional
 * 
 * Localização: app/modules/vendas/views/auth/signup.php
 * 
 * @var yii\web\View $this
 * @var app\modules\vendas\models\SignupForm $model
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\helpers\Url;

$this->title = 'Cadastro - Sistema de Vendas';
?>

<!-- Container Principal -->
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-blue-50 flex items-center justify-center p-4 py-12">
    
    <!-- Card de Cadastro -->
    <div class="w-full max-w-lg">
        
        <!-- Logo/Marca -->
        <div class="text-center mb-8 animate-fade-in">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-500 to-blue-600 rounded-2xl shadow-lg mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Crie Sua Conta</h1>
            <p class="text-gray-600">Comece a gerenciar suas vendas gratuitamente</p>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8 animate-slide-up">
            
            <?php $form = ActiveForm::begin([
                'id' => 'signup-form',
                'options' => ['class' => 'space-y-5'],
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'block text-sm font-semibold text-gray-700 mb-2'],
                    'inputOptions' => ['class' => 'w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring focus:ring-purple-200 focus:ring-opacity-50 transition duration-200 outline-none'],
                    'errorOptions' => ['class' => 'text-red-500 text-sm mt-1'],
                ],
            ]); ?>

            <!-- Nome Completo -->
            <div class="relative">
                <?= $form->field($model, 'nome')->textInput([
                    'placeholder' => 'Seu nome completo',
                    'autocomplete' => 'name',
                    'class' => 'w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring focus:ring-purple-200 focus:ring-opacity-50 transition duration-200 outline-none'
                ])->label('Nome Completo') ?>
                <div class="absolute left-4 top-[38px] text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>

            <!-- CPF -->
            <div class="relative">
                <?= $form->field($model, 'cpf')->textInput([
                    'placeholder' => '000.000.000-00',
                    'maxlength' => 14,
                    'autocomplete' => 'off',
                    'class' => 'w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring focus:ring-purple-200 focus:ring-opacity-50 transition duration-200 outline-none',
                    'id' => 'cpf-input'
                ])->label('CPF') ?>
                <div class="absolute left-4 top-[38px] text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                    </svg>
                </div>
            </div>

            <!-- Telefone/WhatsApp -->
            <div class="relative">
                <?= $form->field($model, 'telefone')->textInput([
                    'placeholder' => '(00) 00000-0000',
                    'maxlength' => 15,
                    'autocomplete' => 'tel',
                    'class' => 'w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring focus:ring-purple-200 focus:ring-opacity-50 transition duration-200 outline-none',
                    'id' => 'telefone-input'
                ])->label('Telefone/WhatsApp') ?>
                <div class="absolute left-4 top-[38px] text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>

            <!-- Email (opcional) -->
            <div class="relative">
                <?= $form->field($model, 'email')->textInput([
                    'placeholder' => 'seu@email.com (opcional)',
                    'autocomplete' => 'email',
                    'class' => 'w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring focus:ring-purple-200 focus:ring-opacity-50 transition duration-200 outline-none'
                ])->label('E-mail <span class="text-gray-400 font-normal">(opcional)</span>') ?>
                <div class="absolute left-4 top-[38px] text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                </div>
            </div>

            <!-- Senha -->
            <div class="relative">
                <?= $form->field($model, 'senha')->passwordInput([
                    'placeholder' => 'Mínimo 6 caracteres',
                    'autocomplete' => 'new-password',
                    'class' => 'w-full pl-12 pr-12 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring focus:ring-purple-200 focus:ring-opacity-50 transition duration-200 outline-none',
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

            <!-- Indicador de Força da Senha -->
            <div id="senha-strength" class="hidden">
                <div class="flex items-center gap-2 mb-2">
                    <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div id="senha-bar" class="h-full transition-all duration-300 rounded-full"></div>
                    </div>
                    <span id="senha-text" class="text-xs font-medium"></span>
                </div>
                <p class="text-xs text-gray-500">Use letras maiúsculas, minúsculas e números</p>
            </div>

            <!-- Confirmar Senha -->
            <div class="relative">
                <?= $form->field($model, 'senha_confirmacao')->passwordInput([
                    'placeholder' => 'Digite a senha novamente',
                    'autocomplete' => 'new-password',
                    'class' => 'w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring focus:ring-purple-200 focus:ring-opacity-50 transition duration-200 outline-none'
                ])->label('Confirmar Senha') ?>
                <div class="absolute left-4 top-[38px] text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
            </div>

            <!-- Termos de Uso -->
            <div class="bg-gray-50 rounded-xl p-4">
                <label class="flex items-start cursor-pointer group">
                    <?= Html::activeCheckbox($model, 'termos_aceitos', [
                        'class' => 'w-5 h-5 mt-0.5 text-purple-600 border-gray-300 rounded focus:ring-purple-500 cursor-pointer',
                        'label' => false
                    ]) ?>
                    <span class="ml-3 text-sm text-gray-600 group-hover:text-gray-800">
                        Li e concordo com os 
                        <a href="#" class="text-purple-600 hover:text-purple-700 font-semibold">Termos de Uso</a>
                        e 
                        <a href="#" class="text-purple-600 hover:text-purple-700 font-semibold">Política de Privacidade</a>
                    </span>
                </label>
                <?= Html::error($model, 'termos_aceitos', ['class' => 'text-red-500 text-sm mt-2 ml-8']) ?>
            </div>

            <!-- Botão de Cadastro -->
            <div class="pt-2">
                <?= Html::submitButton('Criar Conta Grátis', [
                    'class' => 'w-full bg-gradient-to-r from-purple-500 to-blue-600 text-white font-bold py-3 px-4 rounded-xl hover:from-purple-600 hover:to-blue-700 focus:outline-none focus:ring-4 focus:ring-purple-300 transition duration-300 transform hover:scale-[1.02] active:scale-[0.98]',
                    'id' => 'btn-signup'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>

            <!-- Link para Login -->
            <div class="text-center mt-6 pt-6 border-t border-gray-200">
                <p class="text-gray-600">
                    Já tem uma conta? 
                    <a href="<?= Url::to(['auth/login']) ?>" class="text-purple-600 hover:text-purple-700 font-semibold">
                        Faça login
                    </a>
                </p>
            </div>

        </div>

        <!-- Benefícios -->
        <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4 text-center animate-fade-in-delay">
            <div class="bg-white/80 backdrop-blur rounded-xl p-4 shadow-sm">
                <div class="text-purple-600 mb-2">
                    <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-700">100% Grátis</p>
                <p class="text-xs text-gray-500 mt-1">Sem taxas ocultas</p>
            </div>
            <div class="bg-white/80 backdrop-blur rounded-xl p-4 shadow-sm">
                <div class="text-blue-600 mb-2">
                    <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-700">100% Seguro</p>
                <p class="text-xs text-gray-500 mt-1">Dados protegidos</p>
            </div>
            <div class="bg-white/80 backdrop-blur rounded-xl p-4 shadow-sm">
                <div class="text-green-600 mb-2">
                    <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-700">Início Rápido</p>
                <p class="text-xs text-gray-500 mt-1">Em 2 minutos</p>
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

// Máscara de Telefone
document.getElementById('telefone-input').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    
    if (value.length <= 11) {
        if (value.length > 10) {
            value = value.replace(/(\d{2})(\d{5})(\d{4})/, '(\$1) \$2-\$3');
        } else if (value.length > 6) {
            value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '(\$1) \$2-\$3');
        } else if (value.length > 2) {
            value = value.replace(/(\d{2})(\d{0,5})/, '(\$1) \$2');
        }
    }
    
    e.target.value = value;
});

// Ao submeter o form, remover formatação
document.getElementById('signup-form').addEventListener('submit', function(e) {
    const cpfInput = document.getElementById('cpf-input');
    const telefoneInput = document.getElementById('telefone-input');
    
    cpfInput.value = cpfInput.value.replace(/\D/g, '');
    telefoneInput.value = telefoneInput.value.replace(/\D/g, '');
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

// Indicador de força da senha
document.getElementById('senha-input').addEventListener('input', function(e) {
    const senha = e.target.value;
    const strengthDiv = document.getElementById('senha-strength');
    const bar = document.getElementById('senha-bar');
    const text = document.getElementById('senha-text');
    
    if (senha.length === 0) {
        strengthDiv.classList.add('hidden');
        return;
    }
    
    strengthDiv.classList.remove('hidden');
    
    let strength = 0;
    if (senha.length >= 6) strength++;
    if (senha.length >= 10) strength++;
    if (/[a-z]/.test(senha) && /[A-Z]/.test(senha)) strength++;
    if (/[0-9]/.test(senha)) strength++;
    if (/[^a-zA-Z0-9]/.test(senha)) strength++;
    
    const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500'];
    const texts = ['Muito Fraca', 'Fraca', 'Média', 'Boa', 'Forte'];
    const widths = ['20%', '40%', '60%', '80%', '100%'];
    
    bar.className = 'h-full transition-all duration-300 rounded-full ' + colors[strength - 1];
    bar.style.width = widths[strength - 1];
    text.textContent = texts[strength - 1];
    text.className = 'text-xs font-medium ' + colors[strength - 1].replace('bg-', 'text-');
});

// Animação no botão de cadastro ao submeter
document.getElementById('signup-form').addEventListener('submit', function() {
    const btn = document.getElementById('btn-signup');
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

.animate-fade-in-delay {
    animation: fade-in 0.8s ease-out 0.3s both;
}
");
?>