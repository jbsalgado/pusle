<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\HistoricoCobranca */

$this->title = 'Histórico de Cobrança';
$this->params['breadcrumbs'][] = ['label' => 'Histórico de Cobrança', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <div class="flex flex-wrap gap-2">
                    <?= Html::a('Voltar', ['index'], ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300']) ?>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    [
                        'attribute' => 'cliente_id',
                        'value' => $model->cliente ? $model->cliente->nome_completo : '-',
                    ],
                    [
                        'attribute' => 'cobrador_id',
                        'value' => $model->cobrador ? $model->cobrador->nome_completo : '-',
                    ],
                    [
                        'attribute' => 'parcela_id',
                        'value' => $model->parcela ? 'Parcela #' . $model->parcela->numero_parcela . ' - R$ ' . Yii::$app->formatter->asDecimal($model->parcela->valor_parcela, 2) : '-',
                    ],
                    [
                        'attribute' => 'tipo_acao',
                        'value' => $model->getDescricaoTipoAcao(),
                        'format' => 'raw',
                        'value' => function($model) {
                            $tipos = [
                                HistoricoCobranca::TIPO_PAGAMENTO => 'bg-green-100 text-green-800',
                                HistoricoCobranca::TIPO_VISITA => 'bg-blue-100 text-blue-800',
                                HistoricoCobranca::TIPO_AUSENTE => 'bg-yellow-100 text-yellow-800',
                                HistoricoCobranca::TIPO_RECUSA => 'bg-red-100 text-red-800',
                                HistoricoCobranca::TIPO_NEGOCIACAO => 'bg-purple-100 text-purple-800',
                            ];
                            $class = $tipos[$model->tipo_acao] ?? 'bg-gray-100 text-gray-800';
                            return '<span class="px-2 py-1 text-xs font-semibold rounded-full ' . $class . '">' . $model->getDescricaoTipoAcao() . '</span>';
                        },
                    ],
                    [
                        'attribute' => 'valor_recebido',
                        'value' => $model->valor_recebido > 0 ? 'R$ ' . Yii::$app->formatter->asDecimal($model->valor_recebido, 2) : '-',
                    ],
                    [
                        'attribute' => 'data_acao',
                        'value' => Yii::$app->formatter->asDatetime($model->data_acao),
                    ],
                    [
                        'label' => 'Localização',
                        'value' => $model->localizacao_lat && $model->localizacao_lng 
                            ? number_format($model->localizacao_lat, 6) . ', ' . number_format($model->localizacao_lng, 6)
                            : '-',
                    ],
                    'observacao:ntext',
                ],
            ]) ?>
        </div>
    </div>
</div>

