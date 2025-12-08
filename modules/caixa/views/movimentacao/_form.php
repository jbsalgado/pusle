<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\caixa\models\CaixaMovimentacao;
use app\modules\vendas\models\FormaPagamento;

?>

<div class="max-w-2xl mx-auto">
    <?php $form = ActiveForm::begin([
        'id' => 'movimentacao-form',
        'options' => ['class' => 'space-y-6'],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{error}",
            'labelOptions' => ['class' => 'block text-sm font-medium text-gray-700 mb-2'],
            'inputOptions' => ['class' => 'mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm'],
            'errorOptions' => ['class' => 'mt-2 text-sm text-red-600'],
        ],
    ]); ?>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:p-6 space-y-6">
            
            <!-- Tipo -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Tipo de Movimentação</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative cursor-pointer">
                        <input type="radio" 
                               name="CaixaMovimentacao[tipo]" 
                               value="<?= CaixaMovimentacao::TIPO_ENTRADA ?>" 
                               <?= $model->tipo === CaixaMovimentacao::TIPO_ENTRADA ? 'checked' : '' ?>
                               class="peer sr-only">
                        <div class="flex flex-col items-center justify-center p-4 border-2 rounded-lg transition-all duration-200 
                                    hover:border-green-400 peer-checked:border-green-500 peer-checked:bg-green-50 
                                    border-gray-300 bg-white text-gray-700">
                            <svg class="w-8 h-8 mb-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span class="text-sm font-medium">ENTRADA</span>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" 
                               name="CaixaMovimentacao[tipo]" 
                               value="<?= CaixaMovimentacao::TIPO_SAIDA ?>" 
                               <?= $model->tipo === CaixaMovimentacao::TIPO_SAIDA ? 'checked' : '' ?>
                               class="peer sr-only">
                        <div class="flex flex-col items-center justify-center p-4 border-2 rounded-lg transition-all duration-200 
                                    hover:border-red-400 peer-checked:border-red-500 peer-checked:bg-red-50 
                                    border-gray-300 bg-white text-gray-700">
                            <svg class="w-8 h-8 mb-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                            </svg>
                            <span class="text-sm font-medium">SAÍDA</span>
                        </div>
                    </label>
                </div>
                <?= Html::error($model, 'tipo', ['class' => 'mt-2 text-sm text-red-600']) ?>
            </div>

            <!-- Valor -->
            <div>
                <?= $form->field($model, 'valor')->textInput([
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => '0.01',
                    'placeholder' => '0.00',
                    'class' => 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base px-4 py-3'
                ])->label('Valor (R$)') ?>
            </div>

            <!-- Categoria -->
            <div>
                <?= $form->field($model, 'categoria')->dropDownList(
                    [
                        CaixaMovimentacao::CATEGORIA_VENDA => 'VENDA',
                        CaixaMovimentacao::CATEGORIA_PAGAMENTO => 'PAGAMENTO',
                        CaixaMovimentacao::CATEGORIA_SUPRIMENTO => 'SUPRIMENTO',
                        CaixaMovimentacao::CATEGORIA_SANGRIA => 'SANGRIA',
                        CaixaMovimentacao::CATEGORIA_CONTA_PAGAR => 'CONTA_PAGAR',
                        CaixaMovimentacao::CATEGORIA_OUTRO => 'OUTRO',
                    ],
                    [
                        'prompt' => 'Selecione uma categoria (opcional)',
                        'class' => 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base px-4 py-3'
                    ]
                )->label('Categoria') ?>
            </div>

            <!-- Descrição -->
            <div>
                <?= $form->field($model, 'descricao')->textInput([
                    'maxlength' => true,
                    'placeholder' => 'Ex: Venda realizada, Pagamento de fornecedor, etc.',
                    'class' => 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base px-4 py-3'
                ])->label('Descrição') ?>
            </div>

            <!-- Forma de Pagamento -->
            <div>
                <?= $form->field($model, 'forma_pagamento_id')->dropDownList(
                    \yii\helpers\ArrayHelper::map(
                        FormaPagamento::find()
                            ->where(['usuario_id' => Yii::$app->user->id, 'ativo' => true])
                            ->orderBy('nome')
                            ->all(),
                        'id',
                        'nome'
                    ),
                    [
                        'prompt' => 'Selecione uma forma de pagamento (opcional)',
                        'class' => 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base px-4 py-3'
                    ]
                )->label('Forma de Pagamento') ?>
            </div>

            <!-- Data do Movimento -->
            <div>
                <?php
                // Converte a data para o formato datetime-local se existir
                $dataMovimento = $model->data_movimento;
                if ($dataMovimento && !empty($dataMovimento)) {
                    try {
                        $dateTime = new \DateTime($dataMovimento);
                        $dataMovimento = $dateTime->format('Y-m-d\TH:i');
                    } catch (\Exception $e) {
                        // Se falhar, usa a data atual
                        $dataMovimento = date('Y-m-d\TH:i');
                    }
                } else {
                    $dataMovimento = date('Y-m-d\TH:i');
                }
                ?>
                <?= $form->field($model, 'data_movimento')->textInput([
                    'type' => 'datetime-local',
                    'value' => $dataMovimento,
                    'class' => 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base px-4 py-3'
                ])->label('Data e Hora do Movimento') ?>
            </div>

            <!-- Observações -->
            <div>
                <?= $form->field($model, 'observacoes')->textarea([
                    'rows' => 3,
                    'placeholder' => 'Observações adicionais sobre a movimentação...',
                    'class' => 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base px-4 py-3'
                ])->label('Observações') ?>
            </div>

        </div>

        <!-- Actions -->
        <div class="px-4 py-4 bg-gray-50 sm:px-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <?= Html::a('Cancelar', ['caixa/view', 'id' => $caixa->id], [
                'class' => 'w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors'
            ]) ?>
            
            <?= Html::submitButton($model->isNewRecord ? 'Registrar Movimentação' : 'Salvar Alterações', [
                'class' => 'w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors'
            ]) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

