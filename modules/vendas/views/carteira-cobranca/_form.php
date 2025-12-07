<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\vendas\models\PeriodoCobranca;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\RotaCobranca;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\CarteiraCobranca */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="carteira-cobranca-form bg-white rounded-lg shadow-md p-6">
    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'space-y-6'],
    ]); ?>

    <!-- Informações Básicas -->
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Informações Básicas
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?= $form->field($model, 'periodo_id')->dropDownList(
                PeriodoCobranca::getListaDropdown($model->usuario_id),
                ['prompt' => 'Selecione o período', 'class' => 'form-control']
            )->label('Período de Cobrança') ?>

            <?= $form->field($model, 'cobrador_id')->dropDownList(
                Colaborador::getListaCobradores($model->usuario_id),
                ['prompt' => 'Selecione o cobrador', 'class' => 'form-control']
            )->label('Cobrador') ?>

            <?= $form->field($model, 'cliente_id')->dropDownList(
                Cliente::getListaDropdown($model->usuario_id),
                ['prompt' => 'Selecione o cliente', 'class' => 'form-control']
            )->label('Cliente') ?>

            <?= $form->field($model, 'rota_id')->dropDownList(
                RotaCobranca::find()
                    ->where(['usuario_id' => $model->usuario_id])
                    ->select(['nome_rota', 'id'])
                    ->indexBy('id')
                    ->column(),
                ['prompt' => 'Selecione a rota (opcional)', 'class' => 'form-control']
            )->label('Rota') ?>
        </div>
    </div>

    <!-- Valores e Parcelas -->
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Valores e Parcelas
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?= $form->field($model, 'valor_total')->textInput(['type' => 'number', 'step' => '0.01', 'min' => '0', 'class' => 'form-control'])->label('Valor Total') ?>

            <?= $form->field($model, 'total_parcelas')->textInput(['type' => 'number', 'min' => '0', 'class' => 'form-control'])->label('Total de Parcelas') ?>

            <?= $form->field($model, 'parcelas_pagas')->textInput(['type' => 'number', 'min' => '0', 'class' => 'form-control'])->label('Parcelas Pagas') ?>

            <?= $form->field($model, 'valor_recebido')->textInput(['type' => 'number', 'step' => '0.01', 'min' => '0', 'class' => 'form-control'])->label('Valor Recebido') ?>
        </div>
    </div>

    <!-- Configurações -->
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Configurações
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?= $form->field($model, 'data_distribuicao')->textInput(['type' => 'date', 'class' => 'form-control'])->label('Data de Distribuição') ?>

            <div class="form-group">
                <?= $form->field($model, 'ativo')->checkbox([
                    'class' => 'form-check-input',
                    'label' => 'Carteira Ativa'
                ]) ?>
            </div>
        </div>

        <?= $form->field($model, 'observacoes')->textarea(['rows' => 4, 'class' => 'form-control'])->label('Observações') ?>
    </div>

    <!-- Botões de Ação -->
    <div class="flex flex-col sm:flex-row gap-3 pt-4">
        <?= Html::submitButton('Salvar', ['class' => 'flex-1 sm:flex-none px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300']) ?>
        <?= Html::a('Cancelar', ['index'], ['class' => 'flex-1 sm:flex-none px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300 text-center']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<?php
$usuarioId = $model->usuario_id ?: Yii::$app->user->id;
$apiUrl = Yii::$app->urlManager->createAbsoluteUrl(['/api/cliente/dados-cobranca']);
$this->registerJs("
(function() {
    const clienteSelect = document.getElementById('carteiracobranca-cliente_id');
    const valorTotalInput = document.getElementById('carteiracobranca-valor_total');
    const totalParcelasInput = document.getElementById('carteiracobranca-total_parcelas');
    const parcelasPagasInput = document.getElementById('carteiracobranca-parcelas_pagas');
    const valorRecebidoInput = document.getElementById('carteiracobranca-valor_recebido');
    const usuarioId = '{$usuarioId}';
    const apiUrl = '{$apiUrl}';
    
    if (!clienteSelect || !valorTotalInput || !totalParcelasInput || !parcelasPagasInput || !valorRecebidoInput) {
        console.warn('[CarteiraCobranca] Campos do formulário não encontrados');
        return;
    }
    
    let carregando = false;
    
    clienteSelect.addEventListener('change', async function() {
        const clienteId = this.value;
        
        if (!clienteId) {
            // Limpa os campos se nenhum cliente for selecionado
            valorTotalInput.value = '';
            totalParcelasInput.value = '';
            parcelasPagasInput.value = '';
            valorRecebidoInput.value = '';
            return;
        }
        
        if (carregando) {
            return; // Evita múltiplas requisições simultâneas
        }
        
        carregando = true;
        
        // Mostra indicador de carregamento
        const campos = [valorTotalInput, totalParcelasInput, parcelasPagasInput, valorRecebidoInput];
        campos.forEach(campo => {
            campo.disabled = true;
            campo.style.opacity = '0.6';
        });
        
        try {
            const url = apiUrl + '?cliente_id=' + encodeURIComponent(clienteId) + '&usuario_id=' + encodeURIComponent(usuarioId);
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error('Erro ao buscar dados: ' + response.statusText);
            }
            
            const dados = await response.json();
            
            // Preenche os campos automaticamente
            valorTotalInput.value = dados.valor_total || '0';
            totalParcelasInput.value = dados.total_parcelas || '0';
            parcelasPagasInput.value = dados.parcelas_pagas || '0';
            valorRecebidoInput.value = dados.valor_recebido || '0';
            
            // Dispara evento change para garantir que o Yii2 valide os campos
            campos.forEach(campo => {
                const event = new Event('change', { bubbles: true });
                campo.dispatchEvent(event);
            });
            
        } catch (error) {
            console.error('[CarteiraCobranca] Erro ao buscar dados de cobrança:', error);
            alert('Erro ao buscar dados de cobrança do cliente. Verifique o console para mais detalhes.');
        } finally {
            carregando = false;
            campos.forEach(campo => {
                campo.disabled = false;
                campo.style.opacity = '1';
            });
        }
    });
})();
", \yii\web\View::POS_READY);
?>

