<?php

use yii\helpers\Html;
use app\modules\vendas\models\ComissaoConfig;

$this->title = 'Configuração de Comissão';
$this->params['breadcrumbs'][] = ['label' => 'Config. Comissões', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-4 px-3 sm:py-6 sm:px-4 lg:px-8">
    <div class="max-w-4xl mx-auto">
        
        <!-- Header -->
        <div class="mb-4 sm:mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <div class="flex flex-wrap gap-2">
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                        ['index'],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm w-full sm:w-auto justify-center']
                    ) ?>
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar',
                        ['update', 'id' => $model->id],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm w-full sm:w-auto justify-center']
                    ) ?>
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>Excluir',
                        ['delete', 'id' => $model->id],
                        [
                            'class' => 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm w-full sm:w-auto justify-center',
                            'data' => [
                                'confirm' => 'Tem certeza que deseja excluir esta configuração de comissão?',
                                'method' => 'post',
                            ],
                        ]
                    ) ?>
                </div>
            </div>
        </div>

        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="mb-4 sm:mb-6 bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <?= Yii::$app->session->getFlash('success') ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Card Principal -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-4 sm:mb-6">
            
            <!-- Header do Card -->
            <div class="bg-gradient-to-r <?= 
                $model->tipo_comissao == ComissaoConfig::TIPO_VENDA ? 'from-blue-500 to-blue-600' : 'from-purple-500 to-purple-600'
            ?> px-4 sm:px-6 py-6 sm:py-8">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-white backdrop-blur-sm">
                                <?= Html::encode($model->tipo_comissao == ComissaoConfig::TIPO_VENDA ? 'COMISSÃO DE VENDA' : 'COMISSÃO DE COBRANÇA') ?>
                            </span>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= 
                                $model->ativo && $model->isVigente() ? 'bg-green-500 text-white' : 'bg-gray-500 text-white'
                            ?>">
                                <?= $model->ativo && $model->isVigente() ? 'ATIVO' : 'INATIVO' ?>
                            </span>
                        </div>
                        <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold text-white">
                            <?= Yii::$app->formatter->asDecimal($model->percentual, 2) ?>%
                        </h2>
                        <p class="text-sm sm:text-base text-white/90 mt-1">
                            <?= Html::encode($model->colaborador->nome_completo ?? '-') ?>
                        </p>
                    </div>
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white/20 flex items-center justify-center backdrop-blur-sm">
                        <svg class="w-8 h-8 sm:w-10 sm:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Conteúdo -->
            <div class="p-4 sm:p-6 lg:p-8">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Colaborador</dt>
                        <dd class="text-base font-semibold text-gray-900">
                            <?= Html::encode($model->colaborador->nome_completo ?? '-') ?>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Tipo de Comissão</dt>
                        <dd class="text-base font-semibold text-gray-900">
                            <?= Html::encode($model->tipo_comissao == ComissaoConfig::TIPO_VENDA ? 'Venda' : 'Cobrança') ?>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Categoria</dt>
                        <dd class="text-base font-semibold text-gray-900">
                            <?= $model->categoria ? Html::encode($model->categoria->nome) : '<span class="text-gray-500 italic">Todas as Categorias</span>' ?>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Percentual</dt>
                        <dd class="text-lg sm:text-xl font-bold text-blue-600">
                            <?= Yii::$app->formatter->asDecimal($model->percentual, 2) ?>%
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Status</dt>
                        <dd>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold <?= 
                                $model->ativo && $model->isVigente() ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                            ?>">
                                <?= $model->ativo && $model->isVigente() ? 'Ativo e Vigente' : ($model->ativo ? 'Ativo (Fora de Vigência)' : 'Inativo') ?>
                            </span>
                        </dd>
                    </div>

                    <?php if ($model->data_inicio || $model->data_fim): ?>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 mb-2">Período de Vigência</dt>
                            <dd class="text-base text-gray-700">
                                <div class="bg-gray-50 rounded-lg p-3 sm:p-4 space-y-2">
                                    <?php if ($model->data_inicio): ?>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span class="font-medium">Início:</span>
                                            <span><?= Yii::$app->formatter->asDate($model->data_inicio) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($model->data_fim): ?>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span class="font-medium">Fim:</span>
                                            <span><?= Yii::$app->formatter->asDate($model->data_fim) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!$model->data_inicio && !$model->data_fim): ?>
                                        <span class="text-gray-500 italic">Sem período específico (sempre vigente)</span>
                                    <?php endif; ?>
                                </div>
                            </dd>
                        </div>
                    <?php endif; ?>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Data de Criação</dt>
                        <dd class="text-base font-semibold text-gray-900">
                            <?= Yii::$app->formatter->asDatetime($model->data_criacao) ?>
                        </dd>
                    </div>

                    <?php if ($model->data_atualizacao): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1">Última Atualização</dt>
                            <dd class="text-base font-semibold text-gray-900">
                                <?= Yii::$app->formatter->asDatetime($model->data_atualizacao) ?>
                            </dd>
                        </div>
                    <?php endif; ?>

                    <?php if ($model->observacoes): ?>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 mb-1">Observações</dt>
                            <dd class="text-base text-gray-700 bg-gray-50 rounded-lg p-3 sm:p-4">
                                <?= nl2br(Html::encode($model->observacoes)) ?>
                            </dd>
                        </div>
                    <?php endif; ?>

                </dl>
            </div>

            <!-- Ações -->
            <div class="px-4 sm:px-6 lg:px-8 py-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row sm:justify-between gap-3">
                <div class="flex flex-wrap gap-2">
                    <?= Html::a(
                        '<svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar',
                        ['update', 'id' => $model->id],
                        ['class' => 'inline-flex items-center justify-center px-4 sm:px-6 py-2 sm:py-3 border border-transparent text-sm sm:text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors']
                    ) ?>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= Html::beginForm(['delete', 'id' => $model->id], 'post', ['id' => 'delete-form']) ?>
                    <?= Html::button(
                        '<svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>Excluir',
                        [
                            'class' => 'inline-flex items-center justify-center px-4 sm:px-6 py-2 sm:py-3 border border-red-300 text-sm sm:text-base font-medium rounded-lg text-red-700 bg-white hover:bg-red-50 transition-colors',
                            'onclick' => 'return confirmDelete()',
                        ]
                    ) ?>
                    <?= Html::endForm() ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function confirmDelete() {
    if (confirm('Tem certeza que deseja excluir esta configuração de comissão?')) {
        document.getElementById('delete-form').submit();
    }
    return false;
}
</script>

