<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\Colaborador */

$this->title = 'Colaborador';
$this->params['breadcrumbs'][] = ['label' => 'Colaboradores', 'url' => ['index']];
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
                            'confirm' => 'Tem certeza que deseja excluir este colaborador?',
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
                    'nome_completo',
                    'cpf',
                    'telefone',
                    'email:email',
                    [
                        'attribute' => 'eh_vendedor',
                        'value' => $model->eh_vendedor ? 'Sim' : 'Não',
                    ],
                    [
                        'attribute' => 'eh_cobrador',
                        'value' => $model->eh_cobrador ? 'Sim' : 'Não',
                    ],
                    [
                        'attribute' => 'eh_administrador',
                        'value' => $model->eh_administrador ? 'Sim' : 'Não',
                    ],
                    [
                        'attribute' => 'ativo',
                        'value' => $model->ativo ? 'Sim' : 'Não',
                        'format' => 'raw',
                        'value' => function($model) {
                            return $model->ativo 
                                ? '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Ativo</span>'
                                : '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inativo</span>';
                        },
                    ],
                    'percentual_comissao_venda',
                    'percentual_comissao_cobranca',
                    'endereco:ntext',
                    'observacoes:ntext',
                    [
                        'attribute' => 'data_criacao',
                        'value' => Yii::$app->formatter->asDatetime($model->data_criacao),
                    ],
                    [
                        'attribute' => 'data_atualizacao',
                        'value' => $model->data_atualizacao ? Yii::$app->formatter->asDatetime($model->data_atualizacao) : '-',
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>
