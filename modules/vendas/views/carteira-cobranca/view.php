<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\CarteiraCobranca */

$this->title = 'Carteira de Cobrança';
$this->params['breadcrumbs'][] = ['label' => 'Carteiras de Cobrança', 'url' => ['index']];
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
                            'confirm' => 'Tem certeza que deseja excluir esta carteira?',
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
                    [
                        'attribute' => 'cliente_id',
                        'value' => $model->cliente ? $model->cliente->nome_completo : '-',
                    ],
                    [
                        'attribute' => 'cobrador_id',
                        'value' => $model->cobrador ? $model->cobrador->nome_completo : '-',
                    ],
                    [
                        'attribute' => 'rota_id',
                        'value' => $model->rota ? $model->rota->nome_rota : '-',
                    ],
                    [
                        'attribute' => 'periodo_id',
                        'value' => $model->periodo ? $model->periodo->descricao : '-',
                    ],
                    [
                        'attribute' => 'valor_total',
                        'value' => 'R$ ' . Yii::$app->formatter->asDecimal($model->valor_total, 2),
                    ],
                    [
                        'attribute' => 'valor_recebido',
                        'value' => 'R$ ' . Yii::$app->formatter->asDecimal($model->valor_recebido, 2),
                    ],
                    [
                        'label' => 'Saldo Pendente',
                        'value' => 'R$ ' . Yii::$app->formatter->asDecimal($model->getSaldoPendente(), 2),
                    ],
                    [
                        'attribute' => 'parcelas_pagas',
                        'value' => $model->parcelas_pagas . ' / ' . $model->total_parcelas,
                    ],
                    [
                        'label' => 'Percentual Pago',
                        'value' => number_format($model->getPercentualPago(), 1) . '%',
                    ],
                    [
                        'label' => 'Status',
                        'value' => $model->getStatusCobranca(),
                        'format' => 'raw',
                        'value' => function($model) {
                            $status = $model->getStatusCobranca();
                            $class = $status === 'QUITADO' ? 'bg-green-100 text-green-800' : 
                                    ($status === 'PENDENTE' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800');
                            return '<span class="px-2 py-1 text-xs font-semibold rounded-full ' . $class . '">' . $status . '</span>';
                        },
                    ],
                    [
                        'attribute' => 'ativo',
                        'value' => $model->ativo ? 'Sim' : 'Não',
                    ],
                    [
                        'attribute' => 'data_distribuicao',
                        'value' => Yii::$app->formatter->asDate($model->data_distribuicao),
                    ],
                    'observacoes:ntext',
                ],
            ]) ?>
        </div>
    </div>
</div>

