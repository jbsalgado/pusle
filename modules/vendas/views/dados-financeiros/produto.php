<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\vendas\models\Produto;

$this->title = 'Configuração de Produto - Precificação Inteligente';
$this->params['breadcrumbs'][] = ['label' => 'Precificação Inteligente', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Configuração de Produto';

$produto = Produto::findOne($model->produto_id);
?>

<div class="min-h-screen bg-gray-50 py-4 px-3 sm:py-6 sm:px-4 lg:px-8">
    <div class="max-w-4xl mx-auto">
        
        <div class="bg-white rounded-lg shadow-sm sm:shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-blue-600 px-4 py-3 sm:px-6 sm:py-4">
                <h2 class="text-xl sm:text-2xl font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span>Configuração Específica do Produto</span>
                </h2>
                <?php if ($produto): ?>
                    <p class="text-sm text-purple-100 mt-1"><?= Html::encode($produto->nome) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="p-4 sm:p-6">
                <?php $form = ActiveForm::begin(); ?>

                <div class="space-y-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Taxas Fixas (%)</label>
                        <p class="text-xs text-gray-600 mb-3">Impostos fixos, taxas de plataforma, etc.</p>
                        <?= $form->field($model, 'taxa_fixa_percentual')->textInput([
                            'type' => 'number',
                            'step' => '0.01',
                            'min' => '0',
                            'max' => '99.99',
                            'class' => 'w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
                        ])->label(false) ?>
                    </div>

                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Taxas Variáveis (%)</label>
                        <p class="text-xs text-gray-600 mb-3">Comissões, taxas de pagamento, etc.</p>
                        <?= $form->field($model, 'taxa_variavel_percentual')->textInput([
                            'type' => 'number',
                            'step' => '0.01',
                            'min' => '0',
                            'max' => '99.99',
                            'class' => 'w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500',
                        ])->label(false) ?>
                    </div>

                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lucro Líquido Desejado (%)</label>
                        <p class="text-xs text-gray-600 mb-3">Margem líquida após todos os custos e taxas</p>
                        <?= $form->field($model, 'lucro_liquido_percentual')->textInput([
                            'type' => 'number',
                            'step' => '0.01',
                            'min' => '0',
                            'max' => '99.99',
                            'class' => 'w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500',
                        ])->label(false) ?>
                    </div>

                    <!-- Preview do Fator Divisor -->
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="text-sm font-medium text-gray-700 block">Fator Divisor:</span>
                                <span class="text-xs text-gray-600">Fator = 1 - ((Fixas + Variáveis + Lucro) / 100)</span>
                            </div>
                            <span id="fator-divisor-preview" class="text-2xl font-bold text-purple-600">1.0000</span>
                        </div>
                    </div>
                </div>

                <!-- Botões -->
                <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-gray-200 mt-6">
                    <?= Html::submitButton('Salvar Configuração', [
                        'class' => 'w-full sm:flex-1 px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-colors text-sm sm:text-base'
                    ]) ?>
                    <?= Html::a('Cancelar', ['index'], [
                        'class' => 'w-full sm:flex-1 text-center px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold rounded-lg transition-colors text-sm sm:text-base'
                    ]) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const taxaFixa = document.getElementById('dadosfinanceiros-taxa_fixa_percentual');
    const taxaVariavel = document.getElementById('dadosfinanceiros-taxa_variavel_percentual');
    const lucroLiquido = document.getElementById('dadosfinanceiros-lucro_liquido_percentual');
    const fatorDivisorPreview = document.getElementById('fator-divisor-preview');

    function atualizarFatorDivisor() {
        const fixa = parseFloat(taxaFixa.value) || 0;
        const variavel = parseFloat(taxaVariavel.value) || 0;
        const lucro = parseFloat(lucroLiquido.value) || 0;
        
        const soma = fixa + variavel + lucro;
        if (soma >= 100) {
            fatorDivisorPreview.textContent = 'ERRO';
            fatorDivisorPreview.className = 'text-2xl font-bold text-red-600';
            return;
        }
        
        const fator = 1 - (soma / 100);
        fatorDivisorPreview.textContent = fator.toFixed(4);
        fatorDivisorPreview.className = 'text-2xl font-bold text-purple-600';
    }

    if (taxaFixa && taxaVariavel && lucroLiquido) {
        taxaFixa.addEventListener('input', atualizarFatorDivisor);
        taxaVariavel.addEventListener('input', atualizarFatorDivisor);
        lucroLiquido.addEventListener('input', atualizarFatorDivisor);
        atualizarFatorDivisor();
    }
});
</script>

