<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;
use kartik\select2\Select2;

$this->title = 'Login';

// Adicionar CSS customizado para melhorar o design
$this->registerCss('
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .login-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        overflow: hidden;
        max-width: 450px;
        width: 100%;
        animation: slideUp 0.6s ease;
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .login-header {
        text-align: center;
        padding: 40px 30px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        position: relative;
    }
    
    .login-header::after {
        content: "";
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 0;
        border-left: 15px solid transparent;
        border-right: 15px solid transparent;
        border-top: 15px solid #764ba2;
    }
    
    .logo-icon {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        backdrop-filter: blur(10px);
    }
    
    .logo-icon i {
        font-size: 35px;
        color: white;
    }
    
    .login-body {
        padding: 40px 30px;
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .form-control {
        height: 50px;
        border-radius: 12px;
        border: 2px solid #e1e5e9;
        font-size: 16px;
        transition: all 0.3s ease;
        padding: 0 20px;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        transform: translateY(-2px);
    }
    
    .form-control-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .btn-login {
        height: 50px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .btn-login:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
    }
    
    .btn-login:active {
        transform: translateY(-1px);
    }
    
    .btn-login .loading-spinner {
        display: none;
    }
    
    .btn-login.loading .loading-spinner {
        display: inline-block;
        margin-right: 10px;
    }
    
    .btn-login.loading .button-text {
        display: none;
    }
    
    .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: none;
        color: #6c757d;
        cursor: pointer;
        padding: 5px;
        z-index: 10;
    }
    
    .password-toggle:hover {
        color: #495057;
    }
    
    .remember-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 25px 0;
        font-size: 14px;
    }
    
    .checkbox-custom {
        display: flex;
        align-items: center;
    }
    
    .checkbox-custom input[type="checkbox"] {
        width: 18px;
        height: 18px;
        margin-right: 8px;
        accent-color: #667eea;
    }
    
    .forgot-link {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }
    
    .forgot-link:hover {
        color: #5a6fd8;
        text-decoration: underline;
    }
    
    .divider {
        text-align: center;
        margin: 30px 0;
        position: relative;
        color: #6c757d;
        font-size: 14px;
    }
    
    .divider::before {
        content: "";
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: #dee2e6;
        z-index: 1;
    }
    
    .divider span {
        background: white;
        padding: 0 20px;
        position: relative;
        z-index: 2;
    }
    
    .signup-section {
        text-align: center;
        padding: 20px 30px;
        background: #f8f9fa;
        color: #6c757d;
        font-size: 14px;
    }
    
    .signup-link {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }
    
    .signup-link:hover {
        color: #5a6fd8;
    }
    
    .footer-text {
        text-align: center;
        margin-top: 30px;
        color: rgba(255, 255, 255, 0.8);
        font-size: 12px;
    }
    
    .error-message {
        color: #dc3545;
        font-size: 13px;
        margin-top: 5px;
    }
    
    .form-group.has-error .form-control {
        border-color: #dc3545;
    }
    
    .form-group.has-error .form-control:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15);
    }
    
    /* Loading Overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    
    .loading-content {
        background: white;
        border-radius: 15px;
        padding: 30px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    
    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 15px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .select2-container {
        width: 100% !important;
    }
    
    .select2-container .select2-selection--single {
        height: 50px !important;
        border: 2px solid #e1e5e9 !important;
        border-radius: 12px !important;
        padding: 0 10px;
        display: flex;
        align-items: center;
    }
    
    .select2-container--default .select2-selection--single .select2-rendered {
        line-height: 46px !important;
        padding-left: 10px;
        color: #495057;
        font-size: 16px;
    }
    
    .select2-container--default .select2-selection--single .select2-arrow {
        height: 46px !important;
        right: 10px;
    }
    
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #667eea !important;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15) !important;
    }
    
    .select2-dropdown {
        border: 2px solid #667eea !important;
        border-radius: 12px !important;
        margin-top: 5px;
    }
    
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #667eea !important;
    }
    
    .select2-container--default .select2-selection--single:focus {
        outline: none;
    }
    
    /* Responsividade */
    @media (max-width: 768px) {
        .login-card {
            margin: 10px;
            border-radius: 15px;
        }
        
        .login-header, .login-body, .signup-section {
            padding: 25px 20px;
        }
        
        .logo-icon {
            width: 60px;
            height: 60px;
        }
        
        .logo-icon i {
            font-size: 25px;
        }
    }
');

// Registrar JavaScript para funcionalidades interativas
$this->registerJs('
    $(document).ready(function() {
        // Toggle Password Visibility
        $("#toggle-password").click(function() {
            var passwordField = $("#loginformnew-password");
            var fieldType = passwordField.attr("type");
            var icon = $(this).find("i");
            
            if (fieldType === "password") {
                passwordField.attr("type", "text");
                icon.removeClass("glyphicon-eye-open").addClass("glyphicon-eye-close");
            } else {
                passwordField.attr("type", "password");
                icon.removeClass("glyphicon-eye-close").addClass("glyphicon-eye-open");
            }
        });
        
        // Form Loading State
        $("#login-form").on("submit", function(e) {
            var submitBtn = $("#login-button");
            var loadingOverlay = $(".loading-overlay");
            
            submitBtn.addClass("loading").prop("disabled", true);
            loadingOverlay.css("display", "flex");
            
            // Se houver erro, remover loading após 3 segundos
            setTimeout(function() {
                if ($(".has-error").length > 0) {
                    submitBtn.removeClass("loading").prop("disabled", false);
                    loadingOverlay.hide();
                }
            }, 3000);
        });
        
        // Focus no primeiro campo com erro
        var firstError = $(".has-error").first().find(".form-control");
        if (firstError.length > 0) {
            firstError.focus();
        }
        
        // Animação suave nos campos
        $(".form-control").on("focus", function() {
            $(this).parent().addClass("focused");
        });
        
        $(".form-control").on("blur", function() {
            if (!$(this).val()) {
                $(this).parent().removeClass("focused");
            }
        });
    });
');
?>

<div class="login-container">
    <div class="login-card">
        <!-- Header -->
        <div class="login-header">
            <div class="logo-icon">
                <i class="glyphicon glyphicon-lock"></i>
            </div>
            <h2 style="margin: 0; font-size: 28px; font-weight: 300;">Bem-vindo</h2>
            <p style="margin: 8px 0 0; opacity: 0.9; font-size: 16px;">Entre com suas credenciais para continuar</p>
        </div>

        <!-- Body -->
        <div class="login-body">
            <?php $form = ActiveForm::begin([
                'id' => 'login-form',
                'options' => ['class' => ''],
            ]); ?>

            <!-- Username Field -->
            <div class="form-group">
                <?= Html::activeLabel($model, 'username', ['class' => 'form-control-label']) ?>
                <?= Html::activeTextInput($model, 'username', [
                    'class' => 'form-control',
                    'placeholder' => 'Digite seu usuário',
                    'autofocus' => true,
                    'autocomplete' => 'username'
                ]) ?>
                <?= Html::error($model, 'username', ['class' => 'error-message']) ?>
            </div>

            <!-- Password Field -->
            <div class="form-group" style="position: relative;">
                <?= Html::activeLabel($model, 'password', ['class' => 'form-control-label']) ?>
                <?= Html::activePasswordInput($model, 'password', [
                    'class' => 'form-control',
                    'placeholder' => 'Digite sua senha',
                    'autocomplete' => 'current-password',
                    'style' => 'padding-right: 50px;'
                ]) ?>
                <button type="button" id="toggle-password" class="password-toggle">
                    <i class="glyphicon glyphicon-eye-open"></i>
                </button>
                <?= Html::error($model, 'password', ['class' => 'error-message']) ?>
            </div>

            <!-- Module Selection Field - CORREÇÃO PRINCIPAL -->
            <div class="form-group">
                <?= Html::activeLabel($model, 'modulo', ['class' => 'form-control-label']) ?>
                <?= Select2::widget([
                    'model' => $model,          // ← ADICIONADO: Referência ao modelo
                    'attribute' => 'modulo',    // ← ALTERADO: De 'name' para 'attribute'
                    'data' => $modulos,
                    'options' => [
                        'placeholder' => 'Selecione o módulo...',
                        'id' => 'modulo-select'
                    ],
                    'pluginOptions' => [
                        'allowClear' => false,
                        'theme' => 'default',
                        'width' => '100%',
                        'containerCssClass' => 'select2-custom',
                    ],
                ]) ?>
                <?= Html::error($model, 'modulo', ['class' => 'error-message']) ?>
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="remember-section">
                <label class="checkbox-custom">
                    <?= Html::activeCheckbox($model, 'rememberMe', ['label' => false]) ?>
                    <span>Lembrar-me</span>
                </label>
                <a href="#" class="forgot-link">Esqueceu a senha?</a>
            </div>

            <!-- Submit Button -->
            <?= Html::submitButton('Entrar', [
                'class' => 'btn btn-primary btn-block btn-login',
                'id' => 'login-button'
            ]) ?>

            <?php ActiveForm::end(); ?>
        </div>

        <!-- Divider -->
        <div class="divider">
            <span>ou</span>
        </div>

        <!-- Sign Up Section -->
        <div class="signup-section">
            <p style="margin: 0;">
                Não tem uma conta? 
                <a href="<?= Url::to(['signup/index']) ?>" class="signup-link">Cadastre-se aqui</a>
            </p>
        </div>
    </div>

    <!-- Footer -->
    <!-- <div class="footer-text">
        © <?= date('Y') ?> Todos os direitos reservados
    </div> -->
</div>

<!-- Loading Overlay -->
<div class="loading-overlay">
    <div class="loading-content">
        <div class="spinner"></div>
        <p style="margin: 0; color: #495057; font-weight: 500;">Entrando no sistema...</p>
    </div>
</div>

<script>
// Adicionar funcionalidade ao botão de login
document.getElementById('login-button').innerHTML = `
    <div class="loading-spinner spinner" style="width: 20px; height: 20px; border-width: 2px; margin: 0;"></div>
    <span class="button-text">Entrar</span>
`;
</script>