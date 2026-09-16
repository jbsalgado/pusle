<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use app\modules\vendas\models\TaxaEntrega;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\TaxaEntrega */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="max-w-4xl mx-auto px-4 py-8">

    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
        
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-8 py-6 text-white flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold"><?= $model->isNewRecord ? 'Nova Regra de Frete' : 'Editar Regra de Frete' ?></h2>
                <p class="text-blue-100 text-sm mt-1">Configure o valor por Estado, faixa de preço ou localidade específica.</p>
            </div>
            <a href="<?= Url::to(['index']) ?>" class="text-white/80 hover:text-white p-2 rounded-lg transition">
                &times; Fechar
            </a>
        </div>

        <div class="p-8">
            <?php $form = ActiveForm::begin([
                'id' => 'form-taxa-entrega',
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'block text-sm font-bold text-gray-700 mb-1.5'],
                    'inputOptions' => ['class' => 'block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all sm:text-sm'],
                    'errorOptions' => ['class' => 'text-xs text-red-500 mt-1 font-medium'],
                ],
            ]); ?>

            <!-- Identificação da Regra e Serviço -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="md:col-span-2">
                    <?= $form->field($model, 'nome_servico')->textInput([
                        'placeholder' => 'Ex: Frete Econômico Estadual, Expresso, Entrega Local',
                    ])->label('Nome da Modalidade de Entrega *') ?>
                </div>
                <div>
                    <?= $form->field($model, 'porte')->dropDownList(\app\modules\vendas\models\Produto::getPortesList(), [
                        'class' => 'block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all sm:text-sm'
                    ])->label('Porte / Volume Máximo') ?>
                </div>
            </div>

            <!-- Seção de Localização (Estado ou Cidade/Bairro/CEP) -->
            <div class="p-6 bg-slate-50 rounded-2xl border border-slate-200/70 mb-8 space-y-6">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Abrangência Geográfica</h3>
                    <span class="text-xs text-slate-500">Preencha Estado para regra estadual OU CEP/Cidade para regra local</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <?= $form->field($model, 'estado')->dropDownList(
                            ['' => 'Todos / Não limitar por Estado'] + TaxaEntrega::getEstadosList(),
                            ['class' => 'block w-full px-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all sm:text-sm font-semibold']
                        )->label('Estado (UF)') ?>
                    </div>
                    <div>
                        <?= $form->field($model, 'cep')->textInput([
                            'id' => 'input-cep-frete',
                            'placeholder' => '00000-000',
                        ])->label('CEP <span class="text-gray-400 font-normal ml-1">(Opcional)</span>') ?>
                    </div>
                    <div>
                        <?= $form->field($model, 'cidade')->textInput([
                            'id' => 'input-cidade-frete',
                            'placeholder' => 'Ex: São Paulo'
                        ])->label('Cidade <span class="text-gray-400 font-normal ml-1">(Opcional)</span>') ?>
                    </div>
                    <div>
                        <?= $form->field($model, 'bairro')->textInput([
                            'id' => 'input-bairro-frete',
                            'placeholder' => 'Ex: Centro'
                        ])->label('Bairro <span class="text-gray-400 font-normal ml-1">(Opcional)</span>') ?>
                    </div>
                </div>
            </div>

            <!-- Valores e Faixa de Preço do Pedido -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 p-6 bg-blue-50/40 rounded-2xl border border-blue-100">
                <!-- Valor do Frete -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 pt-9 flex items-center pointer-events-none">
                        <span class="text-gray-400 sm:text-sm font-bold">R$</span>
                    </div>
                    <?= $form->field($model, 'valor')->textInput([
                        'type' => 'number',
                        'step' => '0.01',
                        'min' => '0',
                        'class' => 'pl-10 block w-full px-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition-all sm:text-sm font-bold text-blue-600',
                        'placeholder' => '0.00'
                    ])->label('Valor Cobrado do Frete (R$) *') ?>
                </div>

                <!-- Frete Grátis acima de -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 pt-9 flex items-center pointer-events-none">
                        <span class="text-gray-400 sm:text-sm font-bold">R$</span>
                    </div>
                    <?= $form->field($model, 'valor_minimo_frete_gratis')->textInput([
                        'type' => 'number',
                        'step' => '0.01',
                        'min' => '0',
                        'class' => 'pl-10 block w-full px-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition-all sm:text-sm font-semibold text-emerald-600',
                        'placeholder' => 'Ex: 250.00'
                    ])->label('Frete Grátis para Pedidos Acima de (R$)') ?>
                    <p class="text-[10px] text-gray-400 mt-1 uppercase font-bold tracking-wider">Deixe vazio se não houver isenção</p>
                </div>

                <!-- Faixa Mínima de Pedido -->
                <div>
                    <?= $form->field($model, 'faixa_preco_min')->textInput([
                        'type' => 'number',
                        'step' => '0.01',
                        'min' => '0',
                        'class' => 'block w-full px-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition-all sm:text-sm',
                        'placeholder' => '0.00'
                    ])->label('Subtotal Mínimo do Pedido (R$) <span class="text-gray-400 font-normal ml-1">(Opcional)</span>') ?>
                    <p class="text-[10px] text-gray-400 mt-1">Regra só vale se o pedido for igual ou maior que este valor.</p>
                </div>

                <!-- Faixa Máxima de Pedido -->
                <div>
                    <?= $form->field($model, 'faixa_preco_max')->textInput([
                        'type' => 'number',
                        'step' => '0.01',
                        'min' => '0',
                        'class' => 'block w-full px-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition-all sm:text-sm',
                        'placeholder' => 'Ex: 99.99'
                    ])->label('Subtotal Máximo do Pedido (R$) <span class="text-gray-400 font-normal ml-1">(Opcional)</span>') ?>
                    <p class="text-[10px] text-gray-400 mt-1">Deixe vazio para sem limite superior.</p>
                </div>
            </div>

            <!-- Prazo de Entrega Estimado -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <?= $form->field($model, 'prazo_dias_min')->textInput([
                        'type' => 'number',
                        'min' => '0',
                        'class' => 'block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition-all sm:text-sm font-semibold',
                        'placeholder' => '1'
                    ])->label('Prazo Mínimo de Entrega (Dias Úteis)') ?>
                </div>
                <div>
                    <?= $form->field($model, 'prazo_dias_max')->textInput([
                        'type' => 'number',
                        'min' => '0',
                        'class' => 'block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition-all sm:text-sm font-semibold',
                        'placeholder' => '5'
                    ])->label('Prazo Máximo de Entrega (Dias Úteis)') ?>
                </div>
            </div>

            <!-- Observações -->
            <div class="mb-8">
                <?= $form->field($model, 'observacoes')->textarea([
                    'rows' => 2,
                    'placeholder' => 'Ex: Entrega realizada pelos Correios ou transportadora parceira em horário comercial.'
                ])->label('Observações Internas <span class="text-gray-400 font-normal ml-1">(Opcional)</span>') ?>
            </div>

            <!-- Botões -->
            <div class="flex flex-col sm:flex-row items-center gap-3">
                <button type="submit" class="w-full sm:flex-1 py-4 bg-gray-900 text-white font-bold rounded-xl hover:bg-gray-800 shadow-lg transition-all active:scale-95 text-base">
                    Salvar Regra de Frete
                </button>
                <a href="<?= Url::to(['index']) ?>" class="w-full sm:w-auto px-8 py-4 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition-all text-center text-sm">
                    Cancelar
                </a>
            </div>

            <?php ActiveForm::end(); ?>
        </div>

        <div class="bg-blue-50 px-8 py-6 border-t border-blue-100">
            <h5 class="flex items-center text-blue-800 font-bold mb-3">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Hierarquia de Aplicação de Regras:
            </h5>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-blue-700 leading-relaxed">
                <p>• <strong>CEP ou Bairro:</strong> prioridade máxima para entregas locais.</p>
                <p>• <strong>Estado + Faixa de Preço:</strong> permite fretes escalonados por valor do carrinho.</p>
                <p>• <strong>Estado Geral:</strong> taxa média para qualquer cidade daquele estado.</p>
                <p>• <strong>Regra Sem Estado:</strong> funciona como taxa padrão para todo o território nacional.</p>
            </div>
        </div>
    </div>

</div>

<?php
$js = <<<JS
$('#input-cep-frete').on('blur', function() {
    var cep = $(this).val().replace(/\D/g, '');
    if (cep.length == 8) {
        var input = $(this);
        input.addClass('ring-2 ring-blue-300');
        $.getJSON('https://viacep.com.br/ws/' + cep + '/json/', function(dados) {
            if (!("erro" in dados)) {
                $('#input-cidade-frete').val(dados.localidade);
                $('#input-bairro-frete').val(dados.bairro);
                if (dados.uf) {
                    $('select[name="TaxaEntrega[estado]"]').val(dados.uf);
                }
                $('#input-cidade-frete, #input-bairro-frete').trigger('change');
            }
            input.removeClass('ring-2 ring-blue-300');
        });
    }
});
JS;
$this->registerJs($js);
?>
