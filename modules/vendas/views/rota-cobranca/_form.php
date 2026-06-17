<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\vendas\models\PeriodoCobranca;
use app\modules\vendas\models\Colaborador;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\RotaCobranca */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="rota-cobranca-form bg-white rounded-lg shadow-md p-6">
    <?php 
    // Debug: Verificar se há períodos e cobradores disponíveis
    $periodos = PeriodoCobranca::getListaDropdown($model->usuario_id);
    $cobradores = Colaborador::getListaCobradores($model->usuario_id);
    
    if (empty($periodos)) {
        echo '<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <p class="text-yellow-800"><strong>Atenção:</strong> Nenhum período de cobrança encontrado. <a href="' . \yii\helpers\Url::to(['/vendas/periodo-cobranca/create']) . '" class="underline">Crie um período primeiro</a>.</p>
        </div>';
    }
    
    if (empty($cobradores)) {
        echo '<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <p class="text-yellow-800"><strong>Atenção:</strong> Nenhum cobrador encontrado. <a href="' . \yii\helpers\Url::to(['/vendas/colaborador/create']) . '" class="underline">Cadastre um colaborador como cobrador primeiro</a>.</p>
        </div>';
    }
    
    $form = ActiveForm::begin([
        'id' => 'rota-cobranca-form',
        'options' => [
            'class' => 'space-y-6',
        ],
        'enableClientValidation' => false, // Desabilita validação JS para forçar submit
        'enableAjaxValidation' => false,
        'validateOnSubmit' => false, // Desabilita validação no submit
        'validateOnChange' => false,
        'validateOnBlur' => false,
    ]); ?>

    <?php if ($model->hasErrors()): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-red-800 font-semibold">Erros de Validação</h3>
            </div>
            <ul class="mt-2 list-disc list-inside text-sm text-red-700">
                <?php foreach ($model->getErrors() as $attribute => $errors): ?>
                    <?php foreach ($errors as $error): ?>
                        <li><?= Html::encode($model->getAttributeLabel($attribute)) ?>: <?= Html::encode($error) ?></li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Informações Básicas -->
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            Informações Básicas
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?= $form->field($model, 'nome_rota')->textInput(['maxlength' => true, 'class' => 'form-control'])->label('Nome da Rota') ?>

            <?= $form->field($model, 'periodo_id')->dropDownList(
                $periodos,
                [
                    'prompt' => 'Selecione o período', 
                    'class' => 'form-control',
                    'required' => true,
                    'id' => 'rota-periodo-id'
                ]
            )->label('Período de Cobrança <span class="text-red-500">*</span>') ?>

            <?= $form->field($model, 'cobrador_id')->dropDownList(
                $cobradores,
                [
                    'prompt' => 'Selecione o cobrador', 
                    'class' => 'form-control',
                    'required' => true,
                    'id' => 'rota-cobrador-id'
                ]
            )->label('Cobrador <span class="text-red-500">*</span>') ?>

            <?= $form->field($model, 'dia_semana')->dropDownList(
                [
                    0 => 'Domingo',
                    1 => 'Segunda-feira',
                    2 => 'Terça-feira',
                    3 => 'Quarta-feira',
                    4 => 'Quinta-feira',
                    5 => 'Sexta-feira',
                    6 => 'Sábado',
                ],
                [
                    'prompt' => 'Selecione o dia da semana (opcional)', 
                    'class' => 'form-control',
                    'options' => [
                        '' => ['value' => '']
                    ]
                ]
            )->label('Dia da Semana (opcional)') ?>

            <?= $form->field($model, 'ordem_execucao')->textInput(['type' => 'number', 'min' => '0', 'class' => 'form-control'])->label('Ordem de Execução') ?>
        </div>
    </div>

    <!-- Descrição -->
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            Descrição
        </h2>

        <?= $form->field($model, 'descricao')->textarea(['rows' => 4, 'class' => 'form-control'])->label('Descrição da Rota') ?>
    </div>

    <!-- Botões de Ação -->
    <div class="flex flex-col sm:flex-row gap-3 pt-4">
        <?= Html::submitButton('Salvar', ['class' => 'flex-1 sm:flex-none px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300']) ?>
        <?= Html::a('Cancelar', ['index'], ['class' => 'flex-1 sm:flex-none px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300 text-center']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('rota-cobranca-form');
    
    if (!form) {
        console.error('[RotaCobranca] Formulário não encontrado!');
        return;
    }
    
    console.log('[RotaCobranca] Formulário encontrado:', form.id);
    
    // Remove qualquer listener de validação do Yii2 que possa estar bloqueando
    const yiiForm = form.yiiActiveForm;
    if (yiiForm) {
        console.log('[RotaCobranca] Yii2 ActiveForm detectado, desabilitando validação');
        yiiForm.settings.validateOnSubmit = false;
        yiiForm.settings.validateOnChange = false;
        yiiForm.settings.validateOnBlur = false;
    }
    
    // Listener no botão de submit
    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton) {
        submitButton.addEventListener('click', function(e) {
            console.log('[RotaCobranca] Botão Salvar clicado');
            
            // Não previne o default - deixa o formulário submeter
            const periodoId = document.getElementById('rota-periodo-id');
            const cobradorId = document.getElementById('rota-cobrador-id');
            const nomeRota = form.querySelector('input[name*="nome_rota"]');
            
            console.log('[RotaCobranca] Valores:', {
                periodo: periodoId ? periodoId.value : 'não encontrado',
                cobrador: cobradorId ? cobradorId.value : 'não encontrado',
                nomeRota: nomeRota ? nomeRota.value : 'não encontrado'
            });
            
            // Validação básica antes de enviar
            if (!periodoId || !periodoId.value || periodoId.value === '') {
                e.preventDefault();
                alert('Por favor, selecione um período de cobrança.');
                if (periodoId) periodoId.focus();
                return false;
            }
            
            if (!cobradorId || !cobradorId.value || cobradorId.value === '') {
                e.preventDefault();
                alert('Por favor, selecione um cobrador.');
                if (cobradorId) cobradorId.focus();
                return false;
            }
            
            if (!nomeRota || !nomeRota.value || nomeRota.value.trim() === '') {
                e.preventDefault();
                alert('Por favor, informe o nome da rota.');
                if (nomeRota) nomeRota.focus();
                return false;
            }
            
            console.log('[RotaCobranca] Validação passou, permitindo submit...');
        });
    }
    
    // Listener no submit do formulário (backup)
    form.addEventListener('submit', function(e) {
        console.log('[RotaCobranca] Evento submit do formulário disparado');
        console.log('[RotaCobranca] Form action:', form.action);
        console.log('[RotaCobranca] Form method:', form.method);
        
        // Não previne o default - permite o submit normal
    }, false);
});
</script>


