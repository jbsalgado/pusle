<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = 'Parcela #' . $model->numero_parcela;
$this->params['breadcrumbs'][] = ['label' => 'Parcelas', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                    <p class="mt-1 text-sm text-gray-600">Detalhes da parcela</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?= Html::a('Voltar', ['index'], [
                        'class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300'
                    ]) ?>
                    
                    <?php if ($model->status_parcela_codigo !== \app\modules\vendas\models\StatusParcela::PAGA && 
                              $model->status_parcela_codigo !== \app\modules\vendas\models\StatusParcela::CANCELADA): ?>
                        <?= Html::a('Editar', ['update', 'id' => $model->id], [
                            'class' => 'inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg shadow-md transition duration-300'
                        ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Informações da Parcela -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Informações da Parcela</h2>
            </div>
            <div class="px-6 py-4">
                <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        'numero_parcela',
                        [
                            'attribute' => 'valor_parcela',
                            'value' => 'R$ ' . Yii::$app->formatter->asDecimal($model->valor_parcela, 2),
                            'format' => 'raw',
                        ],
                        'data_vencimento:date',
                        [
                            'attribute' => 'status_parcela_codigo',
                            'value' => $model->statusParcela->descricao ?? $model->status_parcela_codigo,
                            'format' => 'raw',
                            'contentOptions' => [
                                'class' => $model->status_parcela_codigo === \app\modules\vendas\models\StatusParcela::PAGA ? 'text-green-600 font-semibold' : 
                                         ($model->getEstaVencida() ? 'text-red-600 font-semibold' : 'text-yellow-600 font-semibold')
                            ],
                        ],
                        'data_pagamento:date',
                        [
                            'attribute' => 'valor_pago',
                            'value' => $model->valor_pago ? 'R$ ' . Yii::$app->formatter->asDecimal($model->valor_pago, 2) : '-',
                            'format' => 'raw',
                        ],
                        [
                            'attribute' => 'forma_pagamento_id',
                            'value' => $model->formaPagamento->nome ?? '-',
                        ],
                        [
                            'attribute' => 'cobrador_id',
                            'value' => $model->cobrador ? $model->cobrador->nome_completo : '-',
                        ],
                        'observacoes:ntext',
                    ],
                ]) ?>
            </div>
        </div>

        <!-- Informações da Venda -->
        <?php if ($model->venda): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Informações da Venda</h2>
            </div>
            <div class="px-6 py-4">
                <?= DetailView::widget([
                    'model' => $model->venda,
                    'attributes' => [
                        'data_venda:date',
                        [
                            'attribute' => 'valor_total',
                            'value' => 'R$ ' . Yii::$app->formatter->asDecimal($model->venda->valor_total, 2),
                            'format' => 'raw',
                        ],
                        'numero_parcelas',
                        [
                            'attribute' => 'cliente_id',
                            'value' => $model->venda->cliente ? $model->venda->cliente->nome_completo : 'Venda Direta',
                        ],
                        [
                            'attribute' => 'cliente_id',
                            'label' => 'CPF',
                            'value' => $model->venda->cliente && $model->venda->cliente->cpf ? $model->venda->cliente->cpf : '-',
                        ],
                    ],
                ]) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Ações -->
        <?php if ($model->status_parcela_codigo !== \app\modules\vendas\models\StatusParcela::PAGA && 
                  $model->status_parcela_codigo !== \app\modules\vendas\models\StatusParcela::CANCELADA): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Ações</h2>
            </div>
            <div class="px-6 py-4">
                <div class="flex flex-wrap gap-3">
                    <?= Html::beginForm(['receber', 'id' => $model->id], 'post', [
                        'style' => 'display: inline-block;',
                        'onsubmit' => 'return confirm("Tem certeza que deseja marcar esta parcela como recebida?");'
                    ]) ?>
                        <?= Html::submitButton('Marcar como Recebida', [
                            'class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300'
                        ]) ?>
                    <?= Html::endForm() ?>
                    
                    <?= Html::beginForm(['cancelar', 'id' => $model->id], 'post', [
                        'style' => 'display: inline-block;',
                        'onsubmit' => 'return confirm("Tem certeza que deseja cancelar esta parcela?");'
                    ]) ?>
                        <?= Html::submitButton('Cancelar Parcela', [
                            'class' => 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-300'
                        ]) ?>
                    <?= Html::endForm() ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

