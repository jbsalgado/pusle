<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Fornecedor: ' . $model->nome_fantasia;
$this->params['breadcrumbs'][] = ['label' => 'Fornecedores', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        
        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <div class="flex flex-wrap gap-2">
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                        ['index'],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm']
                    ) ?>
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar',
                        ['update', 'id' => $model->id],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm']
                    ) ?>
                </div>
            </div>
        </div>

        <!-- Card Principal -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white">Informações do Fornecedor</h2>
                    <?php if ($model->ativo): ?>
                        <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 text-sm font-semibold rounded-full">
                            Ativo
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-800 text-sm font-semibold rounded-full">
                            Inativo
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="p-6 space-y-6">
                
                <!-- Dados Básicos -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        Dados Básicos
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Nome Fantasia</label>
                            <p class="text-base text-gray-900 font-semibold"><?= Html::encode($model->nome_fantasia) ?></p>
                        </div>
                        <?php if ($model->razao_social): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Razão Social</label>
                                <p class="text-base text-gray-900"><?= Html::encode($model->razao_social) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($model->getDocumentoFormatado()): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1"><?= $model->cnpj ? 'CNPJ' : 'CPF' ?></label>
                                <p class="text-base text-gray-900"><?= Html::encode($model->getDocumentoFormatado()) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($model->inscricao_estadual): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Inscrição Estadual</label>
                                <p class="text-base text-gray-900"><?= Html::encode($model->inscricao_estadual) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Contato -->
                <?php if ($model->telefone || $model->email): ?>
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            Contato
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <?php if ($model->telefone): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Telefone</label>
                                    <p class="text-base text-gray-900"><?= Html::encode($model->telefone) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($model->email): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">E-mail</label>
                                    <p class="text-base text-gray-900"><?= Html::encode($model->email) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Endereço -->
                <?php if ($model->endereco || $model->cidade): ?>
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Endereço
                        </h3>
                        <div class="text-base text-gray-900">
                            <?php if ($model->endereco): ?>
                                <?= Html::encode($model->endereco) ?>
                                <?php if ($model->numero): ?>, <?= Html::encode($model->numero) ?><?php endif; ?>
                                <?php if ($model->complemento): ?> - <?= Html::encode($model->complemento) ?><?php endif; ?>
                                <br>
                            <?php endif; ?>
                            <?php if ($model->bairro): ?><?= Html::encode($model->bairro) ?><?php endif; ?>
                            <?php if ($model->cidade): ?>
                                <?php if ($model->bairro): ?> - <?php endif; ?>
                                <?= Html::encode($model->cidade) ?>
                            <?php endif; ?>
                            <?php if ($model->estado): ?>/<?= Html::encode($model->estado) ?><?php endif; ?>
                            <?php if ($model->cep): ?>
                                <br>CEP: <?= Html::encode($model->cep) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Observações -->
                <?php if ($model->observacoes): ?>
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Observações
                        </h3>
                        <p class="text-base text-gray-700 whitespace-pre-line"><?= Html::encode($model->observacoes) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Estatísticas -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Estatísticas
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-blue-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">Total de Compras</p>
                            <p class="text-2xl font-bold text-blue-600"><?= count($model->compras) ?></p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">Compras Concluídas</p>
                            <p class="text-2xl font-bold text-green-600">
                                <?= count(array_filter($model->compras, function($c) { return $c->status_compra === 'CONCLUIDA'; })) ?>
                            </p>
                        </div>
                        <div class="bg-yellow-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">Compras Pendentes</p>
                            <p class="text-2xl font-bold text-yellow-600">
                                <?= count(array_filter($model->compras, function($c) { return $c->status_compra === 'PENDENTE'; })) ?>
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Ações -->
        <div class="flex flex-wrap gap-2">
            <?= Html::a(
                '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar',
                ['update', 'id' => $model->id],
                ['class' => 'inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
            ) ?>
            <?= Html::a(
                '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>Ver Compras',
                ['/vendas/compra/index', 'fornecedor_id' => $model->id],
                ['class' => 'inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
            ) ?>
            <?= Html::a(
                '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>Excluir',
                ['delete', 'id' => $model->id],
                [
                    'class' => 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-300',
                    'data' => [
                        'confirm' => 'Tem certeza que deseja excluir este fornecedor?',
                        'method' => 'post',
                    ],
                ]
            ) ?>
        </div>

    </div>
</div>

