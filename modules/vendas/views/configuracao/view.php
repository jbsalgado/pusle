<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Configurações da Loja';
$this->params['breadcrumbs'][] = ['label' => 'Configurações', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        
        <!-- Mensagens Flash -->
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="mb-4 bg-green-50 border-l-4 border-green-500 text-green-800 px-4 py-3 rounded-lg shadow-lg flex items-start sticky top-4 z-50">
                <svg class="w-6 h-6 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <p class="font-bold text-lg">✅ Sucesso!</p>
                    <p class="text-sm mt-1"><?= Yii::$app->session->getFlash('success') ?></p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-green-600 hover:text-green-800">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('info')): ?>
            <div class="mb-4 bg-blue-50 border-l-4 border-blue-500 text-blue-800 px-4 py-3 rounded-lg shadow-lg flex items-start sticky top-4 z-50">
                <svg class="w-6 h-6 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <p class="font-bold text-lg">ℹ️ Informação</p>
                    <p class="text-sm mt-1"><?= Yii::$app->session->getFlash('info') ?></p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-blue-600 hover:text-blue-800">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <div class="flex flex-wrap gap-2">
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                        ['/vendas/inicio/index'],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm']
                    ) ?>
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar',
                        ['update'],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm']
                    ) ?>
                </div>
            </div>
        </div>

        <!-- Card Principal -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white">Informações da Loja</h2>
            </div>

            <div class="p-6 space-y-6">
                
                <!-- Identificação -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        Identificação
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Nome da Loja</label>
                            <p class="text-base text-gray-900 font-semibold"><?= Html::encode($model->nome_loja ?: 'Não informado') ?></p>
                        </div>
                        <?php if ($model->logo_path): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Logo</label>
                                <?php
                                // Se o caminho não começar com http://, https:// ou /, adiciona @web
                                $logoUrl = $model->logo_path;
                                if (!preg_match('/^(https?:\/\/|\/)/', $logoUrl)) {
                                    $logoUrl = Yii::getAlias('@web') . '/' . ltrim($logoUrl, '/');
                                }
                                ?>
                                <img src="<?= Html::encode($logoUrl) ?>" alt="Logo" class="h-20 object-contain border border-gray-300 rounded-lg p-2 bg-gray-50" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <div style="display: none;" class="h-20 border border-gray-300 rounded-lg p-2 bg-gray-50 flex items-center justify-center text-gray-400 text-xs">
                                    <span>Logo não encontrada</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Cores -->
                <?php if ($model->cor_primaria || $model->cor_secundaria): ?>
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                            </svg>
                            Cores
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <?php if ($model->cor_primaria): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Cor Primária</label>
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-lg border-2 border-gray-300" style="background-color: <?= Html::encode($model->cor_primaria) ?>"></div>
                                        <span class="text-base text-gray-900 font-mono"><?= Html::encode($model->cor_primaria) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($model->cor_secundaria): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Cor Secundária</label>
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-lg border-2 border-gray-300" style="background-color: <?= Html::encode($model->cor_secundaria) ?>"></div>
                                        <span class="text-base text-gray-900 font-mono"><?= Html::encode($model->cor_secundaria) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Redes Sociais -->
                <?php if ($model->whatsapp || $model->instagram || $model->facebook): ?>
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                            Redes Sociais
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <?php if ($model->whatsapp): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">WhatsApp</label>
                                    <p class="text-base text-gray-900"><?= Html::encode($model->whatsapp) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($model->instagram): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Instagram</label>
                                    <p class="text-base text-gray-900"><?= Html::encode($model->instagram) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($model->facebook): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Facebook</label>
                                    <p class="text-base text-gray-900"><?= Html::encode($model->facebook) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Endereço -->
                <?php if ($model->endereco_completo): ?>
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Endereço
                        </h3>
                        <p class="text-base text-gray-900"><?= Html::encode($model->endereco_completo) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Mensagem de Boas-Vindas -->
                <?php if ($model->mensagem_boas_vindas): ?>
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                            Mensagem de Boas-Vindas
                        </h3>
                        <p class="text-base text-gray-700 whitespace-pre-line"><?= Html::encode($model->mensagem_boas_vindas) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Configurações -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Configurações do Sistema
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex items-center">
                            <span class="text-sm font-medium text-gray-700 mr-3">Catálogo Público:</span>
                            <?php if ($model->catalogo_publico): ?>
                                <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-semibold rounded-full">Ativado</span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-gray-100 text-gray-800 text-sm font-semibold rounded-full">Desativado</span>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center">
                            <span class="text-sm font-medium text-gray-700 mr-3">Aceita Orçamentos:</span>
                            <?php if ($model->aceita_orcamentos): ?>
                                <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-semibold rounded-full">Sim</span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-gray-100 text-gray-800 text-sm font-semibold rounded-full">Não</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Ações -->
        <div class="flex flex-wrap gap-2">
            <?= Html::a(
                '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar Configurações',
                ['update'],
                ['class' => 'inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
            ) ?>
        </div>

    </div>
</div>

