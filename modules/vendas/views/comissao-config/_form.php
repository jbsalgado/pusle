<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use app\modules\vendas\models\ComissaoConfig;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\Categoria;

?>

<div class="comissao-config-form">

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
                    ComissaoConfig::TIPO_VENDA => 'Comissão de Venda',
                    ComissaoConfig::TIPO_COBRANCA => 'Comissão de Cobrança',
                ],
                [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
                    'required' => true,
                ]
            )->label(false)->hint('Selecione o tipo de comissão', ['class' => 'text-sm text-gray-500 mt-1']) ?>
        </div>

        <!-- Categoria -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
            <?php
            $categoriasList = ArrayHelper::map(
                Categoria::find()->where(['usuario_id' => Yii::$app->user->id, 'ativo' => true])->orderBy('nome')->all(),
                'id',
                'nome'
            );
            ?>
            <?= $form->field($model, 'categoria_id')->dropDownList(
                $categoriasList,
                [
                    'prompt' => 'Todas as Categorias',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
                ]
            )->label(false)->hint('Selecione "Todas as Categorias" para aplicar a todas, ou escolha uma categoria específica', ['class' => 'text-sm text-gray-500 mt-1']) ?>
        </div>

        <!-- Percentual -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Percentual (%) *</label>
            <?= $form->field($model, 'percentual')->textInput([
                'type' => 'number',
                'step' => '0.01',
                'min' => '0',
                'max' => '100',
                'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
                'placeholder' => '0.00',
                'required' => true,
            ])->label(false)->hint('Percentual de comissão (0 a 100)', ['class' => 'text-sm text-gray-500 mt-1']) ?>
        </div>

        <!-- Período de Vigência -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Início</label>
                <?= $form->field($model, 'data_inicio')->textInput([
                    'type' => 'date',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
                ])->label(false)->hint('Data de início da vigência (opcional)', ['class' => 'text-sm text-gray-500 mt-1']) ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Fim</label>
                <?= $form->field($model, 'data_fim')->textInput([
                    'type' => 'date',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
                ])->label(false)->hint('Data de fim da vigência (opcional)', ['class' => 'text-sm text-gray-500 mt-1']) ?>
            </div>
        </div>

        <!-- Status Ativo -->
        <div class="flex items-center">
            <label class="flex items-center cursor-pointer">
                <?= Html::activeCheckbox($model, 'ativo', [
                    'class' => 'w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500',
                    'label' => null
                ]) ?>
                <span class="ml-2 text-sm font-medium text-gray-700">Configuração Ativa</span>
            </label>
        </div>

        <!-- Observações -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
            <?= $form->field($model, 'observacoes')->textarea([
                'rows' => 4,
                'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none text-sm sm:text-base',
                'placeholder' => 'Observações sobre esta configuração de comissão...',
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
        </div>

    </div>

    <?php ActiveForm::end(); ?>

</div>

<script>
// Validação de datas
document.addEventListener('DOMContentLoaded', function() {
    const dataInicioField = document.querySelector('input[name="ComissaoConfig[data_inicio]"]');
    const dataFimField = document.querySelector('input[name="ComissaoConfig[data_fim]"]');
    
    function validarDatas() {
        if (dataInicioField && dataFimField && dataInicioField.value && dataFimField.value) {
            if (dataFimField.value < dataInicioField.value) {
                alert('A data de fim deve ser maior que a data de início!');
                dataFimField.value = '';
            }
        }
    }
    
    if (dataFimField) {
        dataFimField.addEventListener('change', validarDatas);
    }
    
    if (dataInicioField) {
        dataInicioField.addEventListener('change', function() {
            if (dataFimField && dataFimField.value && dataFimField.value < this.value) {
                dataFimField.value = '';
            }
        });
    }
});
</script>

