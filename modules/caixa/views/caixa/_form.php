<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\vendas\models\Colaborador;

?>

<div class="max-w-2xl mx-auto">
    <?php $form = ActiveForm::begin([
        'id' => 'caixa-form',
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
            
            <!-- Valor Inicial -->
            <div>
                <?= $form->field($model, 'valor_inicial')->textInput([
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => '0',
                    'placeholder' => '0.00',
                    'class' => 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base px-4 py-3'
                ])->label('Valor Inicial (R$)') ?>
                <p class="mt-1 text-sm text-gray-500">Valor em dinheiro no caixa no momento da abertura</p>
            </div>

            <!-- Colaborador -->
            <div>
                <?= $form->field($model, 'colaborador_id')->dropDownList(
                    \yii\helpers\ArrayHelper::map(
                        Colaborador::find()
                            ->where(['usuario_id' => Yii::$app->user->id, 'ativo' => true])
                            ->orderBy('nome_completo')
                            ->all(),
                        'id',
                        'nome_completo'
                    ),
                    [
                        'prompt' => 'Selecione um colaborador (opcional)',
                        'class' => 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base px-4 py-3'
                    ]
                )->label('Colaborador Responsável') ?>
                <p class="mt-1 text-sm text-gray-500">Opcional: atribua o caixa a um colaborador específico</p>
            </div>

            <!-- Observações -->
            <div>
                <?= $form->field($model, 'observacoes')->textarea([
                    'rows' => 4,
                    'placeholder' => 'Observações sobre a abertura do caixa...',
                    'class' => 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base px-4 py-3'
                ])->label('Observações') ?>
            </div>

            <!-- Status (apenas para edição) -->
            <?php if (!$model->isNewRecord): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <div class="mt-1">
                        <?php
                        $statusColors = [
                            'ABERTO' => 'bg-green-100 text-green-800',
                            'FECHADO' => 'bg-gray-100 text-gray-800',
                            'CANCELADO' => 'bg-red-100 text-red-800',
                        ];
                        $statusColor = $statusColors[$model->status] ?? 'bg-gray-100 text-gray-800';
                        ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold <?= $statusColor ?>">
                            <?= Html::encode($model->status) ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Actions -->
        <div class="px-4 py-4 bg-gray-50 sm:px-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <?= Html::a('Cancelar', ['index'], [
                'class' => 'w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors'
            ]) ?>
            
            <?= Html::submitButton($model->isNewRecord ? 'Abrir Caixa' : 'Salvar Alterações', [
                'class' => 'w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors'
            ]) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

