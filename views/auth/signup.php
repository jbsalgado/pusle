<?php
/**
 * View: Cadastro
 * Localização: app/views/auth/signup.php
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\SignupForm */

$this->title = 'Cadastro';
?>

<div class="auth-signup" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px;">
    
    <div style="background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 550px; width: 100%; padding: 50px 40px;">
        
        <!-- Logo/Título -->
        <div style="text-align: center; margin-bottom: 40px;">
            <h1 style="margin: 0 0 10px 0; font-size: 36px; color: #667eea;">
                ✨ Criar Conta
            </h1>
            <p style="margin: 0; color: #666; font-size: 16px;">
                Preencha os dados para começar
            </p>
        </div>

        <!-- Mensagens de Erro/Sucesso -->
        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div style="margin-bottom: 20px; padding: 15px; background: #fee; border: 2px solid #fcc; border-radius: 8px; color: #c33;">
                <strong>❌ Erro:</strong> <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
            </div>
        <?php endif; ?>
        
        <!-- Exibe erros de validação do modelo -->
        <?php if ($model->hasErrors()): ?>
            <div style="margin-bottom: 20px; padding: 15px; background: #fee; border: 2px solid #fcc; border-radius: 8px; color: #c33;">
                <strong>❌ Erros de Validação:</strong>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <?php foreach ($model->errors as $attribute => $errors): ?>
                        <?php foreach ($errors as $error): ?>
                            <li><?= Html::encode($model->getAttributeLabel($attribute) . ': ' . $error) ?></li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div style="margin-bottom: 20px; padding: 15px; background: #efe; border: 2px solid #cfc; border-radius: 8px; color: #3c3;">
                <strong>✅ Sucesso:</strong> <?= Yii::$app->session->getFlash('success') ?>
            </div>
        <?php endif; ?>

        <!-- Formulário -->
        <?php $form = ActiveForm::begin([
            'id' => 'signup-form',
            'enableClientValidation' => true,
            'enableAjaxValidation' => false,
            'fieldConfig' => [
                'template' => "{label}\n{input}\n{error}",
                'labelOptions' => ['style' => 'display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px;'],
                'inputOptions' => ['style' => 'width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 15px;'],
                'errorOptions' => ['style' => 'color: #dc3545; font-size: 13px; margin-top: 5px; display: block;'],
            ],
        ]); ?>

        <!-- Nome -->
        <div style="margin-bottom: 18px;">
            <?= $form->field($model, 'nome')->textInput([
                'placeholder' => 'Ex: João da Silva',
                'autofocus' => true,
                'maxlength' => 100,
            ]) ?>
        </div>

        <!-- CPF e Telefone (Grid) -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 18px;">
            <div>
                <?= $form->field($model, 'cpf')->textInput([
                    'placeholder' => 'Apenas números',
                    'maxlength' => 11,
                ]) ?>
            </div>
            <div>
                <?= $form->field($model, 'telefone')->textInput([
                    'placeholder' => 'DDD + Número',
                    'maxlength' => 11,
                ]) ?>
            </div>
        </div>

        <!-- Email -->
        <div style="margin-bottom: 18px;">
            <?= $form->field($model, 'email')->textInput([
                'placeholder' => 'seu@email.com',
                'type' => 'email',
                'maxlength' => 100,
            ]) ?>
        </div>

        <!-- Senha e Confirmação (Grid) -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 18px;">
            <div>
                <?= $form->field($model, 'senha')->passwordInput([
                    'placeholder' => 'Mínimo 6 caracteres',
                ]) ?>
            </div>
            <div>
                <?= $form->field($model, 'senha_confirmacao')->passwordInput([
                    'placeholder' => 'Repita a senha',
                ]) ?>
            </div>
        </div>

        <!-- Separador: Dados da Empresa -->
        <div style="margin: 30px 0 20px 0; padding: 15px; background: #f0f4ff; border-radius: 8px; border-left: 4px solid #667eea;">
            <h3 style="margin: 0 0 10px 0; font-size: 16px; color: #667eea; font-weight: 600;">
                📍 Dados da Empresa (Opcional)
            </h3>
            <p style="margin: 0; color: #666; font-size: 13px;">
                Esses dados serão usados em comprovantes e documentos fiscais
            </p>
        </div>

        <!-- Endereço -->
        <div style="margin-bottom: 18px;">
            <?= $form->field($model, 'endereco')->textInput([
                'placeholder' => 'Ex: Rua das Flores, 123',
                'maxlength' => 255,
            ]) ?>
        </div>

        <!-- Bairro, Cidade e Estado (Grid) -->
        <div style="display: grid; grid-template-columns: 1fr 1fr 80px; gap: 15px; margin-bottom: 18px;">
            <div>
                <?= $form->field($model, 'bairro')->textInput([
                    'placeholder' => 'Bairro',
                    'maxlength' => 100,
                ]) ?>
            </div>
            <div>
                <?= $form->field($model, 'cidade')->textInput([
                    'placeholder' => 'Cidade',
                    'maxlength' => 100,
                ]) ?>
            </div>
            <div>
                <?= $form->field($model, 'estado')->textInput([
                    'placeholder' => 'UF',
                    'maxlength' => 2,
                    'style' => 'text-transform: uppercase;',
                ]) ?>
            </div>
        </div>

        <!-- Logo da Empresa -->
        <div style="margin-bottom: 18px;">
            <?= $form->field($model, 'logo_path')->textInput([
                'placeholder' => 'Ex: https://exemplo.com/logo.png ou /uploads/logo.png',
                'maxlength' => 500,
            ])->hint('URL ou caminho da logo da empresa (opcional)', ['style' => 'font-size: 12px; color: #666; margin-top: 5px;']) ?>
        </div>

        <!-- Termos -->
        <div style="margin-bottom: 25px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <?= $form->field($model, 'termos_aceitos')->checkbox([
                'template' => '<div style="display: flex; align-items: start; gap: 10px;">{input} {label}</div>{error}',
                'labelOptions' => ['style' => 'margin: 0; color: #666; font-size: 13px; line-height: 1.5;'],
                'label' => 'Li e aceito os <a href="#" style="color: #667eea;">Termos de Uso</a> e a <a href="#" style="color: #667eea;">Política de Privacidade</a>',
            ]) ?>
        </div>

        <!-- Botão Cadastrar -->
        <div style="margin-bottom: 20px;">
            <?= Html::submitButton('Criar Minha Conta', [
                'class' => 'btn-signup',
                'style' => 'width: 100%; padding: 15px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; transition: transform 0.2s;',
                'onmouseover' => 'this.style.transform="translateY(-2px)"',
                'onmouseout' => 'this.style.transform="translateY(0)"'
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>

        <!-- Link para Login -->
        <div style="text-align: center; margin-top: 25px; padding-top: 25px; border-top: 1px solid #e0e0e0;">
            <p style="margin: 0; color: #666;">
                Já tem uma conta? 
                <?= Html::a('Faça login aqui', ['auth/login'], ['style' => 'color: #667eea; text-decoration: none; font-weight: bold;']) ?>
            </p>
        </div>

    </div>

</div>

<style>
body {
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.auth-signup input:focus,
.auth-signup input[type="checkbox"]:focus {
    outline: none;
    border-color: #667eea !important;
}

.auth-signup .has-error input {
    border-color: #dc3545 !important;
}

.auth-signup input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    flex-shrink: 0;
}
</style>

<script>
// Máscara de CPF e validações
document.addEventListener('DOMContentLoaded', function() {
    const cpfInput = document.getElementById('signupform-cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            e.target.value = value.slice(0, 11);
        });
    }
    
    const telefoneInput = document.getElementById('signupform-telefone');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            e.target.value = value.slice(0, 11);
        });
    }
    
    // Garante que o checkbox de termos envia o valor correto
    const termosCheckbox = document.getElementById('signupform-termos_aceitos');
    if (termosCheckbox) {
        // Adiciona um input hidden que será enviado junto
        termosCheckbox.addEventListener('change', function(e) {
            // Remove input hidden anterior se existir
            const hiddenInput = document.getElementById('signupform-termos_aceitos-hidden');
            if (hiddenInput) {
                hiddenInput.remove();
            }
            
            // Cria input hidden com valor 1 se marcado, 0 se desmarcado
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'SignupForm[termos_aceitos]';
            hidden.id = 'signupform-termos_aceitos-hidden';
            hidden.value = e.target.checked ? '1' : '0';
            termosCheckbox.parentNode.appendChild(hidden);
        });
        
        // Dispara o evento change para criar o input hidden inicial
        termosCheckbox.dispatchEvent(new Event('change'));
    }
    
    // Converte estado para maiúsculas
    const estadoInput = document.getElementById('signupform-estado');
    if (estadoInput) {
        estadoInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase().slice(0, 2);
        });
    }
    
    // 🔍 DEBUG: Log do formulário ao submeter
    const form = document.getElementById('signup-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('📝 Formulário sendo submetido...');
            const formData = new FormData(form);
            console.log('📝 Dados do formulário:');
            for (let [key, value] of formData.entries()) {
                console.log('  -', key, ':', value);
            }
        });
    }
});
</script>