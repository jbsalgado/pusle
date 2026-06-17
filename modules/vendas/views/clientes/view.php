<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\Clientes */

$this->title = $model->nome_completo;
$this->params['breadcrumbs'][] = ['label' => 'Clientes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-4 px-3 sm:py-6 sm:px-4 lg:px-8">
    <div class="max-w-5xl mx-auto">

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
            <div class="flex flex-wrap gap-2">
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                    ['index'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar',
                    ['update', 'id' => $model->id],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
                ) ?>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">

            <!-- Dados Pessoais -->
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Dados Pessoais
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <span class="block text-sm font-medium text-gray-500">Nome Completo</span>
                        <span class="block text-gray-900 mt-1"><?= Html::encode($model->nome_completo) ?></span>
                    </div>
                    <div>
                        <span class="block text-sm font-medium text-gray-500">CPF</span>
                        <span class="block text-gray-900 mt-1"><?= Html::encode($model->cpf) ?></span>
                    </div>
                    <div>
                        <span class="block text-sm font-medium text-gray-500">Região</span>
                        <span class="block text-gray-900 mt-1">
                            <?php if ($model->regiao): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?= Html::encode($model->regiao->nome) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400 italic">Nenhuma região selecionada</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div>
                        <span class="block text-sm font-medium text-gray-500">Status</span>
                        <span class="block mt-1">
                            <?php if ($model->ativo): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Ativo</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inativo</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Contato -->
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    Informações de Contato
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <span class="block text-sm font-medium text-gray-500">Telefone</span>
                        <span class="block text-gray-900 mt-1"><?= Html::encode($model->telefone) ?: '<span class="text-gray-400 italic">Não informado</span>' ?></span>
                    </div>
                    <div>
                        <span class="block text-sm font-medium text-gray-500">Email</span>
                        <span class="block text-gray-900 mt-1"><?= Html::encode($model->email) ?: '<span class="text-gray-400 italic">Não informado</span>' ?></span>
                    </div>
                </div>
            </div>

            <!-- Endereço -->
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Endereço
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-3">
                        <span class="block text-sm font-medium text-gray-500">Logradouro Completo</span>
                        <span class="block text-gray-900 mt-1">
                            <?= Html::encode($model->endereco_logradouro) ?>
                            <?= $model->endereco_numero ? ', ' . Html::encode($model->endereco_numero) : '' ?>
                            <?= $model->endereco_complemento ? ' - ' . Html::encode($model->endereco_complemento) : '' ?>
                        </span>
                    </div>
                    <div>
                        <span class="block text-sm font-medium text-gray-500">Bairro</span>
                        <span class="block text-gray-900 mt-1"><?= Html::encode($model->endereco_bairro) ?></span>
                    </div>
                    <div>
                        <span class="block text-sm font-medium text-gray-500">Cidade/UF</span>
                        <span class="block text-gray-900 mt-1">
                            <?= Html::encode($model->endereco_cidade) ?>
                            <?= $model->endereco_estado ? '/' . Html::encode($model->endereco_estado) : '' ?>
                        </span>
                    </div>
                    <div>
                        <span class="block text-sm font-medium text-gray-500">CEP</span>
                        <span class="block text-gray-900 mt-1"><?= Html::encode($model->endereco_cep) ?></span>
                    </div>
                    <?php if ($model->ponto_referencia): ?>
                        <div class="md:col-span-3">
                            <span class="block text-sm font-medium text-gray-500">Ponto de Referência</span>
                            <span class="block text-gray-900 mt-1"><?= nl2br(Html::encode($model->ponto_referencia)) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($model->observacoes): ?>
                <!-- Observações -->
                <div class="p-6 border-t border-gray-100 bg-yellow-50/30">
                    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Observações
                    </h2>
                    <div class="prose prose-sm text-gray-800">
                        <?= nl2br(Html::encode($model->observacoes)) ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>