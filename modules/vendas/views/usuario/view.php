<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = 'Usuário: ' . $model->nome;
$this->params['breadcrumbs'][] = ['label' => 'Usuários', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                    <p class="mt-1 text-sm text-gray-600">Detalhes do usuário</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?= Html::a('Voltar', ['index'], [
                        'class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300'
                    ]) ?>
                    <?= Html::a('Editar', ['update', 'id' => $model->id], [
                        'class' => 'inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg shadow-md transition duration-300'
                    ]) ?>
                    <?= Html::a('Mudar Senha', ['mudar-senha', 'id' => $model->id], [
                        'class' => 'inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg shadow-md transition duration-300'
                    ]) ?>
                </div>
            </div>
        </div>

        <!-- Informações do Usuário -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Informações do Usuário</h2>
            </div>
            <div class="px-6 py-4">
                <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        'nome',
                        'email',
                        'cpf',
                        'telefone',
                        [
                            'attribute' => 'data_criacao',
                            'value' => $model->data_criacao ? Yii::$app->formatter->asDate($model->data_criacao) : '-',
                            'format' => 'raw',
                        ],
                        [
                            'attribute' => 'data_atualizacao',
                            'value' => $model->data_atualizacao ? Yii::$app->formatter->asDate($model->data_atualizacao) : '-',
                            'format' => 'raw',
                        ],
                    ],
                ]) ?>
            </div>
        </div>

        <!-- Informações do Colaborador -->
        <?php if ($colaborador): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Informações do Colaborador</h2>
            </div>
            <div class="px-6 py-4">
                <?= DetailView::widget([
                    'model' => $colaborador,
                    'attributes' => [
                        'nome_completo',
                        [
                            'attribute' => 'eh_administrador',
                            'value' => $colaborador->eh_administrador ? 'Sim' : 'Não',
                            'format' => 'raw',
                            'contentOptions' => [
                                'class' => $colaborador->eh_administrador ? 'text-blue-600 font-semibold' : ''
                            ],
                        ],
                        [
                            'attribute' => 'eh_vendedor',
                            'value' => $colaborador->eh_vendedor ? 'Sim' : 'Não',
                        ],
                        [
                            'attribute' => 'eh_cobrador',
                            'value' => $colaborador->eh_cobrador ? 'Sim' : 'Não',
                        ],
                        [
                            'attribute' => 'ativo',
                            'value' => $colaborador->ativo ? 'Ativo' : 'Bloqueado',
                            'format' => 'raw',
                            'contentOptions' => [
                                'class' => $colaborador->ativo ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold'
                            ],
                        ],
                        [
                            'attribute' => 'percentual_comissao_venda',
                            'value' => $colaborador->percentual_comissao_venda ? number_format($colaborador->percentual_comissao_venda, 2, ',', '.') . '%' : '-',
                        ],
                        [
                            'attribute' => 'percentual_comissao_cobranca',
                            'value' => $colaborador->percentual_comissao_cobranca ? number_format($colaborador->percentual_comissao_cobranca, 2, ',', '.') . '%' : '-',
                        ],
                        [
                            'attribute' => 'data_admissao',
                            'value' => $colaborador->data_admissao ? Yii::$app->formatter->asDate($colaborador->data_admissao) : '-',
                        ],
                    ],
                ]) ?>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <p class="text-yellow-800">
                <strong>Atenção:</strong> Este usuário não possui colaborador associado. 
                <?= Html::a('Crie um colaborador', ['/vendas/colaborador/create', 'usuario_id' => $model->id], [
                    'class' => 'underline font-semibold'
                ]) ?> para que ele possa acessar o sistema.
            </p>
        </div>
        <?php endif; ?>

        <!-- Ações -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Ações</h2>
            </div>
            <div class="px-6 py-4">
                <div class="flex flex-wrap gap-3">
                    <?php if ($colaborador): ?>
                        <?php if ($colaborador->ativo): ?>
                            <?= Html::beginForm(['bloquear', 'id' => $model->id], 'post', [
                                'style' => 'display: inline-block;',
                                'onsubmit' => 'return confirm("Tem certeza que deseja bloquear este usuário?");'
                            ]) ?>
                                <?= Html::submitButton('Bloquear Usuário', [
                                    'class' => 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-300'
                                ]) ?>
                            <?= Html::endForm() ?>
                        <?php else: ?>
                            <?= Html::beginForm(['ativar', 'id' => $model->id], 'post', [
                                'style' => 'display: inline-block;',
                                'onsubmit' => 'return confirm("Tem certeza que deseja ativar este usuário?");'
                            ]) ?>
                                <?= Html::submitButton('Ativar Usuário', [
                                    'class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300'
                                ]) ?>
                            <?= Html::endForm() ?>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?= Html::a('Editar Colaborador', ['/vendas/colaborador/update', 'id' => $colaborador->id ?? ''], [
                        'class' => 'inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300',
                        'style' => $colaborador ? '' : 'display: none;'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
</div>

