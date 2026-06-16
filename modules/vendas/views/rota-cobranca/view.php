<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\RotaCobranca */

$this->title = 'Rota de Cobrança';
$this->params['breadcrumbs'][] = ['label' => 'Rotas de Cobrança', 'url' => ['index']];
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
                            'confirm' => 'Tem certeza que deseja excluir esta rota?',
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
                    'nome_rota',
                    [
                        'attribute' => 'cobrador_id',
                        'value' => $model->cobrador ? $model->cobrador->nome_completo : '-',
                    ],
                    [
                        'attribute' => 'periodo_id',
                        'value' => $model->periodo ? $model->periodo->descricao : '-',
                    ],
                    [
                        'attribute' => 'dia_semana',
                        'value' => $model->getNomeDiaSemana(),
                    ],
                    'ordem_execucao',
                    [
                        'label' => 'Total de Clientes',
                        'value' => $model->getTotalClientes(),
                    ],
                    [
                        'attribute' => 'data_criacao',
                        'value' => Yii::$app->formatter->asDatetime($model->data_criacao),
                    ],
                    'descricao:ntext',
                ],
            ]) ?>
        </div>
    </div>
</div>

