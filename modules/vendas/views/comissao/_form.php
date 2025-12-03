<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use app\modules\vendas\models\Comissao;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\Parcela;

?>

<div class="comissao-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="space-y-6">
        
        <!-- Colaborador -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Colaborador *</label>
            <?= $form->field($model, 'colaborador_id')->dropDownList(
                ArrayHelper::map(
                    Colaborador::find()->where(['usuario_id' => Yii::$app->user->id, 'ativo' => true])->orderBy('nome_completo')->all(),
                    'id',
                    'nome_completo'
                ),
                [
                    'prompt' => 'Selecione um colaborador',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
                    'required' => true,
                ]
            )->label(false)->hint('Selecione o colaborador que receberá a comissão', ['class' => 'text-sm text-gray-500 mt-1']) ?>
        </div>

        <!-- Tipo de Comissão -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Comissão *</label>
            <?= $form->field($model, 'tipo_comissao')->dropDownList(
                [
                    Comissao::TIPO_VENDA => 'Comissão de Venda',
                    Comissao::TIPO_COBRANCA => 'Comissão de Cobrança',
                ],
                [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
                    'required' => true,
                ]
            )->label(false)->hint('Selecione o tipo de comissão', ['class' => 'text-sm text-gray-500 mt-1']) ?>
        </div>

        <!-- Venda (condicional) -->
        <div id="venda-field" style="<?= $model->tipo_comissao == Comissao::TIPO_VENDA ? '' : 'display: none;' ?>">
            <label class="block text-sm font-medium text-gray-700 mb-2">Venda</label>
            <?= $form->field($model, 'venda_id')->dropDownList(
                ArrayHelper::map(
                    Venda::find()
                        ->where(['usuario_id' => Yii::$app->user->id])
                        ->with(['cliente'])
                        ->orderBy('data_venda DESC')
                        ->limit(100)
                        ->all(),
                    'id',
                    function($venda) {
                        $clienteNome = $venda->cliente ? $venda->cliente->nome_completo : 'Sem cliente';
                        $dataVenda = $venda->data_venda ? Yii::$app->formatter->asDate($venda->data_venda) : 'Sem data';
                        $valor = Yii::$app->formatter->asCurrency($venda->valor_total);
                        return $dataVenda . ' - ' . $clienteNome . ' - ' . $valor;
                    }
                ),
                [
                    'prompt' => 'Selecione uma venda (opcional)',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
                ]
            )->label(false)->hint('Selecione a venda relacionada (opcional)', ['class' => 'text-sm text-gray-500 mt-1']) ?>
        </div>

        <!-- Parcela (condicional) -->
        <div id="parcela-field" style="<?= $model->tipo_comissao == Comissao::TIPO_COBRANCA ? '' : 'display: none;' ?>">
            <label class="block text-sm font-medium text-gray-700 mb-2">Parcela</label>
            <?= $form->field($model, 'parcela_id')->dropDownList(
                ArrayHelper::map(
                    Parcela::find()->where(['usuario_id' => Yii::$app->user->id])->orderBy('data_vencimento DESC')->limit(100)->all(),
                    'id',
                    function($parcela) {
                        return 'Parcela #' . $parcela->numero_parcela . ' - ' . Yii::$app->formatter->asCurrency($parcela->valor_parcela);
                    }
                ),
                [
                    'prompt' => 'Selecione uma parcela (opcional)',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
                ]
            )->label(false)->hint('Selecione a parcela relacionada (opcional)', ['class' => 'text-sm text-gray-500 mt-1']) ?>
        </div>

        <!-- Valor Base -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Valor Base *</label>
            <?= $form->field($model, 'valor_base')->textInput([
                'type' => 'number',
                'step' => '0.01',
                'min' => '0',
                'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
                'placeholder' => '0.00',
                'required' => true,
            ])->label(false)->hint('Valor sobre o qual a comissão será calculada', ['class' => 'text-sm text-gray-500 mt-1']) ?>
        </div>

        <!-- Percentual Aplicado -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Percentual Aplicado (%) *</label>
            <?= $form->field($model, 'percentual_aplicado')->textInput([
                'type' => 'number',
                'step' => '0.01',
                'min' => '0',
                'max' => '100',
                'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
                'placeholder' => '0.00',
                'required' => true,
            ])->label(false)->hint('Percentual de comissão a ser aplicado', ['class' => 'text-sm text-gray-500 mt-1']) ?>
        </div>

        <!-- Valor da Comissão -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Valor da Comissão *</label>
            <?= $form->field($model, 'valor_comissao')->textInput([
                'type' => 'number',
                'step' => '0.01',
                'min' => '0',
                'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
                'placeholder' => '0.00',
                'required' => true,
            ])->label(false)->hint('Valor total da comissão', ['class' => 'text-sm text-gray-500 mt-1']) ?>
        </div>

        <!-- Status -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
            <?= $form->field($model, 'status')->dropDownList(
                [
                    Comissao::STATUS_PENDENTE => 'Pendente',
                    Comissao::STATUS_PAGA => 'Paga',
                    Comissao::STATUS_CANCELADA => 'Cancelada',
                ],
                [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
                    'required' => true,
                ]
            )->label(false) ?>
        </div>

        <!-- Data de Pagamento (condicional) -->
        <div id="data-pagamento-field" style="<?= $model->status == Comissao::STATUS_PAGA ? '' : 'display: none;' ?>">
            <label class="block text-sm font-medium text-gray-700 mb-2">Data de Pagamento</label>
            <?= $form->field($model, 'data_pagamento')->textInput([
                'type' => 'date',
                'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
            ])->label(false)->hint('Data em que a comissão foi paga', ['class' => 'text-sm text-gray-500 mt-1']) ?>
        </div>

        <!-- Observações -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
            <?= $form->field($model, 'observacoes')->textarea([
                'rows' => 4,
                'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none text-sm sm:text-base',
                'placeholder' => 'Observações sobre a comissão...',
            ])->label(false) ?>
        </div>

        <!-- Botões -->
        <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-gray-200">
            <?= Html::submitButton(
                $model->isNewRecord 
                    ? '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Cadastrar' 
                    : '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Salvar',
                ['class' => 'flex-1 inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300 text-sm sm:text-base']
            ) ?>
            <?= Html::a(
                '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Cancelar',
                ['index'], 
                ['class' => 'flex-1 text-center inline-flex items-center justify-center px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold rounded-lg transition duration-300 text-sm sm:text-base']
            ) ?>
            <?= Html::a(
                '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>Produtos',
                ['/vendas/produto/index'], 
                ['class' => 'flex-1 text-center inline-flex items-center justify-center px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg transition duration-300 text-sm sm:text-base']
            ) ?>
        </div>

    </div>

    <?php ActiveForm::end(); ?>

</div>

<script>
// Mostrar/ocultar campos baseado no tipo de comissão
document.addEventListener('DOMContentLoaded', function() {
    const tipoComissaoField = document.querySelector('select[name="Comissao[tipo_comissao]"]');
    const vendaField = document.getElementById('venda-field');
    const parcelaField = document.getElementById('parcela-field');
    const statusField = document.querySelector('select[name="Comissao[status]"]');
    const dataPagamentoField = document.getElementById('data-pagamento-field');
    
    if (tipoComissaoField) {
        tipoComissaoField.addEventListener('change', function() {
            if (this.value === '<?= Comissao::TIPO_VENDA ?>') {
                vendaField.style.display = 'block';
                parcelaField.style.display = 'none';
            } else if (this.value === '<?= Comissao::TIPO_COBRANCA ?>') {
                vendaField.style.display = 'none';
                parcelaField.style.display = 'block';
            } else {
                vendaField.style.display = 'none';
                parcelaField.style.display = 'none';
            }
        });
    }
    
    if (statusField) {
        statusField.addEventListener('change', function() {
            if (this.value === '<?= Comissao::STATUS_PAGA ?>') {
                dataPagamentoField.style.display = 'block';
            } else {
                dataPagamentoField.style.display = 'none';
            }
        });
    }
    
    // Calcular valor da comissão automaticamente
    const valorBaseField = document.querySelector('input[name="Comissao[valor_base]"]');
    const percentualField = document.querySelector('input[name="Comissao[percentual_aplicado]"]');
    const valorComissaoField = document.querySelector('input[name="Comissao[valor_comissao]"]');
    
    function calcularComissao() {
        if (valorBaseField && percentualField && valorComissaoField) {
            const valorBase = parseFloat(valorBaseField.value) || 0;
            const percentual = parseFloat(percentualField.value) || 0;
            const valorComissao = (valorBase * percentual) / 100;
            
            if (valorBase > 0 && percentual > 0) {
                valorComissaoField.value = valorComissao.toFixed(2);
            }
        }
    }
    
    if (valorBaseField) valorBaseField.addEventListener('input', calcularComissao);
    if (percentualField) percentualField.addEventListener('input', calcularComissao);
});
</script>

