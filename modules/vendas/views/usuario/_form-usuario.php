<?php

use yii\helpers\Html;

?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?= $form->field($model, 'nome')->textInput([
        'maxlength' => true,
        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
        'placeholder' => 'Nome completo',
        'id' => 'usuario-nome'
    ]) ?>

    <?= $form->field($model, 'username')->textInput([
        'maxlength' => true,
        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
        'placeholder' => 'Nome de usuário para login (único)',
        'title' => 'Nome de usuário único para login. Pode ser email ou CPF.'
    ]) ?>

    <?= $form->field($model, 'email')->textInput([
        'type' => 'email',
        'maxlength' => true,
        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
        'placeholder' => 'email@exemplo.com',
        'id' => 'usuario-email'
    ]) ?>

    <?= $form->field($model, 'cpf')->textInput([
        'maxlength' => true,
        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
        'placeholder' => '000.000.000-00',
        'id' => 'usuario-cpf',
        'data-mask' => 'cpf'
    ]) ?>

    <?= $form->field($model, 'telefone')->textInput([
        'maxlength' => true,
        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
        'placeholder' => '(00) 00000-0000',
        'id' => 'usuario-telefone',
        'data-mask' => 'telefone'
    ]) ?>
</div>

<div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Senha *</label>
        <?= Html::passwordInput('Usuario[senha]', '', [
            'id' => 'usuario-senha',
            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
            'placeholder' => 'Mínimo 6 caracteres',
            'required' => true
        ]) ?>
        <p class="mt-1 text-sm text-gray-500">A senha será criptografada automaticamente.</p>
    </div>
    
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar Senha *</label>
        <?= Html::passwordInput('Usuario[senha_confirmacao]', '', [
            'id' => 'usuario-senha-confirmacao',
            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
            'placeholder' => 'Digite a senha novamente',
            'required' => true
        ]) ?>
        <p class="mt-1 text-sm text-red-500 hidden" id="senha-erro">As senhas não coincidem.</p>
    </div>
</div>

<div class="mt-4 p-3 bg-blue-50 rounded-md">
    <p class="text-sm text-blue-800">
        <strong>ℹ️ Nota:</strong> Este usuário será criado como colaborador (não como dono da loja). Os dados pessoais serão automaticamente sincronizados com o colaborador.
    </p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Máscara CPF
    const cpfInput = document.getElementById('usuario-cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                e.target.value = value;
            }
        });
    }
    
    // Máscara Telefone
    const telefoneInput = document.getElementById('usuario-telefone');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                if (value.length <= 10) {
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                } else {
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                }
                e.target.value = value;
            }
        });
    }
    
    // Validação de senha
    const senhaInput = document.getElementById('usuario-senha');
    const senhaConfirmacaoInput = document.getElementById('usuario-senha-confirmacao');
    const senhaErro = document.getElementById('senha-erro');
    
    function validarSenhas() {
        if (senhaInput.value && senhaConfirmacaoInput.value) {
            if (senhaInput.value !== senhaConfirmacaoInput.value) {
                senhaErro.classList.remove('hidden');
                senhaConfirmacaoInput.setCustomValidity('As senhas não coincidem');
            } else {
                senhaErro.classList.add('hidden');
                senhaConfirmacaoInput.setCustomValidity('');
            }
        }
    }
    
    if (senhaInput && senhaConfirmacaoInput) {
        senhaInput.addEventListener('input', validarSenhas);
        senhaConfirmacaoInput.addEventListener('input', validarSenhas);
    }
});
</script>

