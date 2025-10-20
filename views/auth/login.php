<?php
/**
 * View: Login
 * Localização: app/views/auth/login.php
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\LoginForm */

$this->title = 'Login';
?>

<div class="auth-login" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px;">
    
    <div style="background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 450px; width: 100%; padding: 50px 40px;">
        
        <!-- Logo/Título -->
        <div style="text-align: center; margin-bottom: 40px;">
            <h1 style="margin: 0 0 10px 0; font-size: 36px; color: #667eea;">
                🚀 THAUSZ-PULSE
            </h1>
            <p style="margin: 0; color: #666; font-size: 16px;">
                Sistema de Gestão
            </p>
        </div>

        <!-- Mensagens Flash -->
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= Yii::$app->session->getFlash('success') ?>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= Yii::$app->session->getFlash('error') ?>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('info')): ?>
            <div style="background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= Yii::$app->session->getFlash('info') ?>
            </div>
        <?php endif; ?>

        <!-- Formulário -->
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['style' => 'margin-top: 30px;'],
            'fieldConfig' => [
                'template' => "{label}\n{input}\n{error}",
                'labelOptions' => ['style' => 'display: block; margin-bottom: 8px; color: #333; font-weight: 500;'],
                'inputOptions' => ['style' => 'width: 100%; padding: 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;'],
                'errorOptions' => ['style' => 'color: #dc3545; font-size: 14px; margin-top: 5px;'],
            ],
        ]); ?>

        <div style="margin-bottom: 20px;">
            <?= $form->field($model, 'login')->textInput([
                'placeholder' => 'Digite seu CPF ou E-mail',
                'autofocus' => true,
                'style' => 'width: 100%; padding: 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px;'
            ])->label('CPF ou E-mail') ?>
        </div>

        <div style="margin-bottom: 20px;">
            <?= $form->field($model, 'senha')->passwordInput([
                'placeholder' => 'Digite sua senha',
                'style' => 'width: 100%; padding: 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px;'
            ])->label('Senha') ?>
        </div>

        <div style="margin-bottom: 25px; display: flex; align-items: center;">
            <?= $form->field($model, 'lembrar_me')->checkbox([
                'template' => '<div style="display: flex; align-items: center;">{input} {label}</div>{error}',
                'labelOptions' => ['style' => 'margin: 0 0 0 8px; color: #666;'],
            ])->label('Lembrar-me por 30 dias') ?>
        </div>

        <div style="margin-bottom: 20px;">
            <?= Html::submitButton('Entrar', [
                'class' => 'btn-login',
                'style' => 'width: 100%; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; transition: transform 0.2s;',
                'onmouseover' => 'this.style.transform="translateY(-2px)"',
                'onmouseout' => 'this.style.transform="translateY(0)"'
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>

        <!-- Links -->
        <div style="text-align: center; margin-top: 25px; padding-top: 25px; border-top: 1px solid #e0e0e0;">
            <p style="margin: 0 0 15px 0; color: #666;">
                <?= Html::a('Esqueci minha senha', ['auth/forgot-password'], ['style' => 'color: #667eea; text-decoration: none;']) ?>
            </p>
            <p style="margin: 0; color: #666;">
                Não tem uma conta? 
                <?= Html::a('Cadastre-se aqui', ['auth/signup'], ['style' => 'color: #667eea; text-decoration: none; font-weight: bold;']) ?>
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

.auth-login input:focus {
    outline: none;
    border-color: #667eea !important;
}

.auth-login .has-error input {
    border-color: #dc3545 !important;
}
</style>