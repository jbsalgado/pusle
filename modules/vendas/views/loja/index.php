<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Minhas Lojas/Filiais';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                    <p class="mt-1 text-sm text-gray-600">Gerencie suas lojas e filiais</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?= Html::a('Voltar', ['/vendas/inicio/index'], [
                        'class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300'
                    ]) ?>
                    <?= Html::a('Nova Loja/Filial', ['create'], [
                        'class' => 'inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300'
                    ]) ?>
                </div>
            </div>
        </div>

        <!-- Lojas como Dono -->
        <?php if (!empty($lojasComoDono)): ?>
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Lojas que você é dono</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($lojasComoDono as $loja): ?>
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2"><?= Html::encode($loja->nome) ?></h3>
                            <p class="text-sm text-gray-600 mb-1">
                                <strong>Username:</strong> <?= Html::encode($loja->username) ?>
                            </p>
                            <p class="text-sm text-gray-600 mb-1">
                                <strong>Email:</strong> <?= Html::encode($loja->email ?? 'Não informado') ?>
                            </p>
                            <p class="text-sm text-gray-600 mb-1">
                                <strong>CPF:</strong> <?= Html::encode($loja->cpf ?? 'Não informado') ?>
                            </p>
                            <p class="text-sm text-gray-600 mb-3">
                                <strong>Telefone:</strong> <?= Html::encode($loja->telefone ?? 'Não informado') ?>
                            </p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Dono da Loja
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lojas como Colaborador -->
        <?php if (!empty($lojasComoColaborador)): ?>
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Lojas onde você é colaborador</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($lojasComoColaborador as $loja): ?>
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2"><?= Html::encode($loja->nome) ?></h3>
                            <p class="text-sm text-gray-600 mb-1">
                                <strong>Username:</strong> <?= Html::encode($loja->username) ?>
                            </p>
                            <p class="text-sm text-gray-600 mb-1">
                                <strong>Email:</strong> <?= Html::encode($loja->email ?? 'Não informado') ?>
                            </p>
                            <p class="text-sm text-gray-600 mb-3">
                                <strong>CPF:</strong> <?= Html::encode($loja->cpf ?? 'Não informado') ?>
                            </p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Colaborador
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Mensagem se não tiver lojas -->
        <?php if (empty($lojasComoDono) && empty($lojasComoColaborador)): ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma loja encontrada</h3>
            <p class="mt-1 text-sm text-gray-500">Comece criando uma nova loja/filial.</p>
            <div class="mt-6">
                <?= Html::a('Criar Nova Loja/Filial', ['create'], [
                    'class' => 'inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300'
                ]) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

