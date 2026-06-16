<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\vendas\models\FormaPagamento;

?>

<div class="max-w-2xl mx-auto">
    <?php $form = ActiveForm::begin([
        'id' => 'forma-pagamento-form',
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
            
            <!-- Nome -->
            <div>
                <?= $form->field($model, 'nome')->textInput([
                    'maxlength' => true,
                    'placeholder' => 'Ex: Dinheiro, PIX BB, Cartão Nubank',
                    'class' => 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base px-4 py-3'
                ])->label('Nome da Forma de Pagamento') ?>
            </div>

            <!-- Tipo -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Tipo de Pagamento</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    <?php
                    $tipos = [
                        FormaPagamento::TIPO_DINHEIRO => [
                            'label' => 'Dinheiro',
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>',
                            'color' => 'green'
                        ],
                        FormaPagamento::TIPO_PIX => [
                            'label' => 'PIX (Dinâmico)',
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>',
                            'color' => 'blue',
                            'description' => 'Com gateway'
                        ],
                        FormaPagamento::TIPO_PIX_ESTATICO => [
                            'label' => 'PIX Estático',
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>',
                            'color' => 'indigo',
                            'description' => 'QR Code fixo'
                        ],
                        FormaPagamento::TIPO_CARTAO => [
                            'label' => 'Cartão',
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>',
                            'color' => 'purple'
                        ],
                        FormaPagamento::TIPO_CARTAO_CREDITO => [
                            'label' => 'Cartão de Crédito',
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>',
                            'color' => 'purple',
                            'description' => 'Com gateway'
                        ],
                        FormaPagamento::TIPO_CARTAO_DEBITO => [
                            'label' => 'Cartão de Débito',
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>',
                            'color' => 'purple',
                            'description' => 'Com gateway'
                        ],
                        FormaPagamento::TIPO_BOLETO => [
                            'label' => 'Boleto',
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
                            'color' => 'orange',
                            'description' => 'Com gateway'
                        ],
                        FormaPagamento::TIPO_PAGAR_AO_ENTREGADOR => [
                            'label' => 'Pagar na Entrega',
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>',
                            'color' => 'green',
                            'description' => 'DINHEIRO ou PIX'
                        ]
                    ];
                    
                    foreach ($tipos as $valor => $info):
                        $isSelected = $model->tipo === $valor;
                        $colorClasses = [
                            'green' => 'border-green-500 bg-green-50 text-green-700',
                            'blue' => 'border-blue-500 bg-blue-50 text-blue-700',
                            'indigo' => 'border-indigo-500 bg-indigo-50 text-indigo-700',
                            'purple' => 'border-purple-500 bg-purple-50 text-purple-700',
                            'orange' => 'border-orange-500 bg-orange-50 text-orange-700',
                            'red' => 'border-red-500 bg-red-50 text-red-700',
                        ];
                    ?>
                        <label class="relative cursor-pointer">
                            <input type="radio" 
                                   name="FormaPagamento[tipo]" 
                                   value="<?= $valor ?>" 
                                   <?= $isSelected ? 'checked' : '' ?>
                                   class="peer sr-only">
                            <div class="flex flex-col items-center justify-center p-4 border-2 rounded-lg transition-all duration-200 
                                        hover:border-gray-400 peer-checked:<?= $colorClasses[$info['color']] ?> 
                                        border-gray-300 bg-white text-gray-700">
                                <div class="mb-2">
                                    <?= $info['icon'] ?>
                                </div>
                                <span class="text-sm font-medium text-center"><?= $info['label'] ?></span>
                                <?php if (isset($info['description'])): ?>
                                    <span class="text-xs text-gray-500 mt-1 text-center"><?= $info['description'] ?></span>
                                <?php endif; ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?= Html::error($model, 'tipo', ['class' => 'mt-2 text-sm text-red-600']) ?>
            </div>

            <!-- Checkboxes -->
            <div class="space-y-4">
                <!-- Ativo -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <?= Html::activeCheckbox($model, 'ativo', [
                            'class' => 'w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500',
                            'label' => null,
                        ]) ?>
                    </div>
                    <div class="ml-3">
                        <label for="formapagamento-ativo" class="font-medium text-gray-700">Forma de Pagamento Ativa</label>
                        <p class="text-sm text-gray-500">Quando inativa, esta forma de pagamento não aparecerá nas opções de seleção</p>
                    </div>
                </div>

                <!-- Aceita Parcelamento -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <?= Html::activeCheckbox($model, 'aceita_parcelamento', [
                            'class' => 'w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500',
                            'label' => null,
                        ]) ?>
                    </div>
                    <div class="ml-3">
                        <label for="formapagamento-aceita_parcelamento" class="font-medium text-gray-700">Aceita Parcelamento</label>
                        <p class="text-sm text-gray-500">Permite dividir pagamentos nesta forma em múltiplas parcelas</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Actions -->
        <div class="px-4 py-4 bg-gray-50 sm:px-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <?= Html::a('Cancelar', ['index'], [
                'class' => 'w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors'
            ]) ?>
            
            <?= Html::submitButton($model->isNewRecord ? 'Criar Forma de Pagamento' : 'Salvar Alterações', [
                'class' => 'w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors'
            ]) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<style>
/* Ajustes para o checkbox do Yii2 */
.field-formapagamento-ativo,
.field-formapagamento-aceita_parcelamento {
    margin-bottom: 0 !important;
}
.field-formapagamento-ativo .help-block,
.field-formapagamento-aceita_parcelamento .help-block {
    display: none;
}
</style>