<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Criar Usuário e Colaborador';
$this->params['breadcrumbs'][] = ['label' => 'Usuários', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                    <p class="mt-1 text-sm text-gray-600">Crie um novo usuário e configure como colaborador em uma única ação</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?= Html::a('Voltar', ['index'], [
                        'class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300'
                    ]) ?>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <?php $form = ActiveForm::begin(); ?>

            <!-- Erros de validação -->
            <div class="px-6 pt-4">
                <?= $form->errorSummary($usuario, [
                    'class' => 'mb-4 p-4 border border-red-300 bg-red-50 text-red-800 rounded'
                ]) ?>
                <?= $form->errorSummary($colaborador, [
                    'class' => 'mb-4 p-4 border border-red-300 bg-red-50 text-red-800 rounded'
                ]) ?>
            </div>
            
            <!-- Dados do Usuário -->
            <div class="px-6 py-4 bg-blue-50 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">📋 Dados do Usuário (Login)</h2>
                <p class="text-sm text-gray-600 mt-1">Informações para acesso ao sistema</p>
            </div>
            <div class="px-6 py-8">
                <?= $this->render('_form-usuario', ['model' => $usuario, 'form' => $form]) ?>
            </div>

            <!-- Dados do Colaborador -->
            <div class="px-6 py-4 bg-green-50 border-t border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">👤 Dados do Colaborador</h2>
                <p class="text-sm text-gray-600 mt-1">Informações profissionais e permissões</p>
            </div>
            <div class="px-6 py-8">
                <?= $this->render('_form-colaborador', ['model' => $colaborador, 'form' => $form]) ?>
            </div>

            <!-- Botões -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                <div class="flex gap-3 justify-end">
                    <?= Html::submitButton('Criar Usuário e Colaborador', [
                        'class' => 'px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300'
                    ]) ?>
                    <?= Html::a('Cancelar', ['index'], [
                        'class' => 'px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300'
                    ]) ?>
                </div>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sincroniza dados do usuário para o colaborador em tempo real
    const usuarioNome = document.getElementById('usuario-nome');
    const usuarioCpf = document.getElementById('usuario-cpf');
    const usuarioTelefone = document.getElementById('usuario-telefone');
    const usuarioEmail = document.getElementById('usuario-email');
    
    const colaboradorNome = document.getElementById('colaborador-nome_completo');
    const colaboradorCpf = document.getElementById('colaborador-cpf');
    const colaboradorTelefone = document.getElementById('colaborador-telefone');
    const colaboradorEmail = document.getElementById('colaborador-email');
    
    function sincronizarDados() {
        if (usuarioNome && colaboradorNome) {
            colaboradorNome.value = usuarioNome.value;
        }
        if (usuarioCpf && colaboradorCpf) {
            // Remove formatação do CPF antes de salvar
            colaboradorCpf.value = usuarioCpf.value.replace(/\D/g, '');
        }
        if (usuarioTelefone && colaboradorTelefone) {
            // Remove formatação do telefone antes de salvar
            colaboradorTelefone.value = usuarioTelefone.value.replace(/\D/g, '');
        }
        if (usuarioEmail && colaboradorEmail) {
            colaboradorEmail.value = usuarioEmail.value;
        }
    }
    
    // Sincroniza quando os campos do usuário mudam
    if (usuarioNome) {
        usuarioNome.addEventListener('input', sincronizarDados);
        usuarioNome.addEventListener('blur', sincronizarDados);
    }
    if (usuarioCpf) {
        usuarioCpf.addEventListener('input', sincronizarDados);
        usuarioCpf.addEventListener('blur', sincronizarDados);
    }
    if (usuarioTelefone) {
        usuarioTelefone.addEventListener('input', sincronizarDados);
        usuarioTelefone.addEventListener('blur', sincronizarDados);
    }
    if (usuarioEmail) {
        usuarioEmail.addEventListener('input', sincronizarDados);
        usuarioEmail.addEventListener('blur', sincronizarDados);
    }
    
    // Sincroniza antes de submeter o formulário
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            sincronizarDados();
        });
    }
    
    // Sincroniza na carga inicial
    sincronizarDados();
});
</script>

