<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\modules\vendas\models\PeriodoCobranca;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\PeriodoCobranca */

$this->title = 'Período de Cobrança';
$this->params['breadcrumbs'][] = ['label' => 'Períodos de Cobrança', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <div class="flex flex-wrap gap-2">
                    <?= Html::a('Editar', ['update', 'id' => $model->id], ['class' => 'inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg shadow-md transition duration-300']) ?>
                    <?= Html::a('Excluir', ['delete', 'id' => $model->id], [
                        'class' => 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-300',
                        'data' => [
                            'confirm' => 'Tem certeza que deseja excluir este período?',
                            'method' => 'post',
                        ],
                    ]) ?>
                    <?= Html::a('Voltar', ['index'], ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300']) ?>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    'descricao',
                    [
                        'attribute' => 'mes_referencia',
                        'value' => function($model) {
                            $meses = [
                                1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                                5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                                9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                            ];
                            return $meses[$model->mes_referencia] ?? $model->mes_referencia;
                        },
                    ],
                    'ano_referencia',
                    [
                        'attribute' => 'data_inicio',
                        'value' => Yii::$app->formatter->asDate($model->data_inicio),
                    ],
                    [
                        'attribute' => 'data_fim',
                        'value' => Yii::$app->formatter->asDate($model->data_fim),
                    ],
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function($model) {
                            $statusClass = $model->status === PeriodoCobranca::STATUS_FECHADO ? 'bg-gray-100 text-gray-800' : 
                                          ($model->status === PeriodoCobranca::STATUS_EM_COBRANCA ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800');
                            return '<span class="px-2 py-1 text-xs font-semibold rounded-full ' . $statusClass . '">' . Html::encode($model->status) . '</span>';
                        },
                    ],
                    [
                        'label' => 'Total de Clientes',
                        'value' => $model->getTotalClientes(),
                    ],
                    [
                        'label' => 'Total de Cobradores',
                        'value' => $model->getTotalCobradores(),
                    ],
                    [
                        'attribute' => 'data_criacao',
                        'value' => Yii::$app->formatter->asDatetime($model->data_criacao),
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>

