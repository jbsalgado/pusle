<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\vendas\models\PeriodoCobranca;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\PeriodoCobranca */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="periodo-cobranca-form bg-white rounded-lg shadow-md p-6">
    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'space-y-6'],
    ]); ?>

    <!-- Informações do Período -->
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Informações do Período
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?= $form->field($model, 'mes_referencia')->dropDownList(
                [
                    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                ],
                ['class' => 'form-control']
            )->label('Mês de Referência') ?>

            <?= $form->field($model, 'ano_referencia')->textInput(['type' => 'number', 'min' => '2020', 'max' => '2099', 'class' => 'form-control'])->label('Ano de Referência') ?>

            <?= $form->field($model, 'data_inicio')->textInput([
                'type' => 'date', 
                'class' => 'form-control',
                'id' => 'periodo-data-inicio',
                'onchange' => 'validarPeriodoDatas()'
            ])->label('Data de Início')->hint('Data inicial do período de cobrança') ?>

            <?= $form->field($model, 'data_fim')->textInput([
                'type' => 'date', 
                'class' => 'form-control',
                'id' => 'periodo-data-fim',
                'onchange' => 'validarPeriodoDatas()'
            ])->label('Data de Fim')->hint('Data final do período de cobrança (deve ser >= data de início)') ?>
            
            <!-- Alerta de validação em tempo real -->
            <div id="periodo-alerta-datas" class="hidden mt-2"></div>
        </div>
    </div>

    <!-- Status e Descrição -->
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Status e Descrição
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?= $form->field($model, 'status')->dropDownList(
                [
                    PeriodoCobranca::STATUS_ABERTO => 'Aberto',
                    PeriodoCobranca::STATUS_EM_COBRANCA => 'Em Cobrança',
                    PeriodoCobranca::STATUS_FECHADO => 'Fechado',
                ],
                ['class' => 'form-control']
            )->label('Status') ?>

            <?= $form->field($model, 'descricao')->textInput(['maxlength' => true, 'class' => 'form-control', 'placeholder' => 'Deixe em branco para gerar automaticamente'])->label('Descrição (opcional)') ?>
        </div>
    </div>

    <!-- Botões de Ação -->
    <div class="flex flex-col sm:flex-row gap-3 pt-4">
        <?= Html::submitButton('Salvar', ['class' => 'flex-1 sm:flex-none px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300']) ?>
        <?= Html::a('Cancelar', ['index'], ['class' => 'flex-1 sm:flex-none px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300 text-center']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<script>
/**
 * Validação em tempo real das datas do período
 */
function validarPeriodoDatas() {
    const dataInicio = document.getElementById('periodo-data-inicio');
    const dataFim = document.getElementById('periodo-data-fim');
    const alertaDiv = document.getElementById('periodo-alerta-datas');
    
    if (!dataInicio || !dataFim || !alertaDiv) return;
    
    const valorInicio = dataInicio.value;
    const valorFim = dataFim.value;
    
    // Limpa alerta anterior
    alertaDiv.className = 'hidden mt-2';
    alertaDiv.innerHTML = '';
    
    // Só valida se ambas as datas estiverem preenchidas
    if (!valorInicio || !valorFim) return;
    
    const inicio = new Date(valorInicio);
    const fim = new Date(valorFim);
    
    // Validação 1: data_fim >= data_inicio
    if (fim < inicio) {
        alertaDiv.className = 'mt-2 p-3 bg-red-50 border border-red-200 rounded-lg';
        alertaDiv.innerHTML = `
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-red-800 font-semibold text-sm">❌ Data inválida</p>
                    <p class="text-red-700 text-xs mt-1">A data de fim deve ser maior ou igual à data de início.</p>
                </div>
            </div>
        `;
        return;
    }
    
    // Validação 2: Alerta se cruzar anos (não bloqueia)
    const anoInicio = inicio.getFullYear();
    const anoFim = fim.getFullYear();
    
    if (anoInicio !== anoFim) {
        alertaDiv.className = 'mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg';
        alertaDiv.innerHTML = `
            <div class="flex items-start">
                <svg class="w-5 h-5 text-yellow-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <p class="text-yellow-800 font-semibold text-sm">⚠️ Período cruza anos</p>
                    <p class="text-yellow-700 text-xs mt-1">Este período começa em ${anoInicio} e termina em ${anoFim}. Isso é permitido, mas verifique se está correto.</p>
                </div>
            </div>
        `;
        return;
    }
    
    // Tudo OK - mostra confirmação visual (opcional)
    const diasDiferenca = Math.ceil((fim - inicio) / (1000 * 60 * 60 * 24)) + 1;
    if (diasDiferenca > 0) {
        alertaDiv.className = 'mt-2 p-3 bg-green-50 border border-green-200 rounded-lg';
        alertaDiv.innerHTML = `
            <div class="flex items-start">
                <svg class="w-5 h-5 text-green-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-green-800 font-semibold text-sm">✅ Período válido</p>
                    <p class="text-green-700 text-xs mt-1">Período de ${diasDiferenca} dia(s).</p>
                </div>
            </div>
        `;
    }
}

// Valida ao carregar a página se já houver valores
document.addEventListener('DOMContentLoaded', function() {
    validarPeriodoDatas();
});
</script>

