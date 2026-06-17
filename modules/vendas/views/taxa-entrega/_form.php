<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\TaxaEntrega */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="max-w-4xl mx-auto px-4 py-8">

    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
        
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-8 py-6 text-white text-center">
            <h2 class="text-xl font-bold">Configuração da Regra</h2>
            <p class="text-blue-100 text-sm mt-1">Preencha os dados abaixo para definir sua taxa de entrega.</p>
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

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div>
                    <?= $form->field($model, 'cep')->textInput([
                        'id' => 'input-cep-frete',
                        'placeholder' => '00000-000',
                    ])->label('CEP <span class="text-gray-400 font-normal ml-1">(TAB p/ buscar)</span>') ?>
                </div>
                <div>
                    <?= $form->field($model, 'cidade')->textInput([
                        'id' => 'input-cidade-frete',
                        'placeholder' => 'Ex: São Paulo'
                    ]) ?>
                </div>
                <div>
                    <?= $form->field($model, 'bairro')->textInput([
                        'id' => 'input-bairro-frete',
                        'placeholder' => 'Ex: Centro'
                    ]) ?>
                </div>
                <div>
                    <?= $form->field($model, 'porte')->dropDownList(\app\modules\vendas\models\Produto::getPortesList(), [
                        'class' => 'block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all sm:text-sm'
                    ]) ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 p-6 bg-gray-50 rounded-2xl border border-gray-100">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 pt-9 flex items-center pointer-events-none">
                        <span class="text-gray-400 sm:text-sm font-bold">R$</span>
                    </div>
                    <?= $form->field($model, 'valor')->textInput([
                        'type' => 'number',
                        'step' => '0.01',
                        'min' => '0',
                        'class' => 'pl-10 block w-full px-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition-all sm:text-sm',
                        'placeholder' => '0.00'
                    ])->label('Valor da Taxa') ?>
                </div>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 pt-9 flex items-center pointer-events-none">
                        <span class="text-gray-400 sm:text-sm font-bold">R$</span>
                    </div>
                    <?= $form->field($model, 'valor_minimo_frete_gratis')->textInput([
                        'type' => 'number',
                        'step' => '0.01',
                        'min' => '0',
                        'class' => 'pl-10 block w-full px-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition-all sm:text-sm',
                        'placeholder' => 'Opcional'
                    ])->label('Frete Grátis acima de') ?>
                    <p class="text-[10px] text-gray-400 mt-1 uppercase font-bold tracking-wider">Deixe vazio se não houver promoção</p>
                </div>
            </div>

            <div class="mb-8">
                <?= $form->field($model, 'observacoes')->textarea([
                    'rows' => 2,
                    'placeholder' => 'Ex: Entrega apenas em dias úteis, taxa extra para feriados, etc.'
                ])->label('Observações Internas <span class="text-gray-400 font-normal ml-1">(Opcional)</span>') ?>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-3">
                <button type="submit" class="w-full sm:flex-1 py-4 bg-gray-900 text-white font-bold rounded-xl hover:bg-gray-800 shadow-lg transition-all active:scale-95">
                    <i class="fas fa-save mr-2"></i> Salvar Regra
                </button>
                <a href="<?= Url::to(['index']) ?>" class="w-full sm:w-auto px-8 py-4 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition-all text-center">
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
                Hierarquia de Prioridade:
            </h5>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-blue-700 leading-relaxed">
                <p>• O <strong>CEP</strong> tem prioridade máxima (regra específica).</p>
                <p>• <strong>Bairro + Cidade</strong> vale para uma região específica.</p>
                <p>• A <strong>Cidade</strong> funciona como taxa padrão para todo o município.</p>
                <p>• Deixe campos vazios para regras mais abrangentes.</p>
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
                // Dispara o evento de input para o Yii validar se necessário
                $('#input-cidade-frete, #input-bairro-frete').trigger('change');
            }
            input.removeClass('ring-2 ring-blue-300');
        });
    }
});
JS;
$this->registerJs($js);
?>
