<?php

/** @var \yii\web\View $this */
/** @var \app\modules\evolution\models\WhatsappConfig|null $config */
/** @var bool $connected */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Integração WhatsApp — PULSE-PLUS';
?>

<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    <!-- Cabeçalho da Página -->
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
            <!-- WhatsApp SVG Icon -->
            <svg class="w-8 h-8 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
            </svg>
            <h1 class="text-2xl font-bold text-gray-900 m-0">
                WhatsApp Business
            </h1>
        </div>
        <p class="text-gray-500 m-0">Gerencie a conexão WhatsApp da sua loja via Evolution API.</p>
    </div>

    <!-- Card de Status -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6">

            <!-- Indicador de Status -->
            <div class="flex items-center mb-6 p-4 rounded-xl <?= $connected ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
                <div class="mr-4 flex-shrink-0">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $connected ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <?php if ($connected): ?>
                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            Conectado
                        <?php else: ?>
                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                            Desconectado
                        <?php endif; ?>
                    </span>
                </div>
                <div>
                    <?php if ($connected): ?>
                        <p class="m-0 text-green-700 font-semibold">Sua loja está ativa no WhatsApp.</p>
                        <p class="m-0 text-sm text-green-600 mt-0.5">
                            Instância: <code class="bg-green-100 px-1.5 py-0.5 rounded font-mono text-xs"><?= Html::encode($config->instance_name ?? '—') ?></code>
                        </p>
                    <?php else: ?>
                        <p class="m-0 text-red-700 font-semibold">Nenhum dispositivo pareado.</p>
                        <p class="m-0 text-sm text-red-600 mt-0.5">Clique em "Conectar WhatsApp" para iniciar o pareamento.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Informações da conexão (somente quando conectado) -->
            <?php if ($connected && $config !== null): ?>
                <div class="mb-8">
                    <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">
                        Detalhes da Conexão
                    </h2>
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 gap-x-4 text-sm">
                        <dt class="text-gray-500">Instância</dt>
                        <dd class="sm:col-span-2 text-gray-900 font-mono text-xs"><code class="bg-gray-100 px-2 py-1 rounded"><?= Html::encode($config->instance_name) ?></code></dd>

                        <dt class="text-gray-500">Status</dt>
                        <dd class="sm:col-span-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                <?= Html::encode($config->status) ?>
                            </span>
                        </dd>

                        <dt class="text-gray-500">Sincronizado em</dt>
                        <dd class="sm:col-span-2 text-gray-900"><?= Html::encode(Yii::$app->formatter->asDatetime($config->updated_at)) ?></dd>
                    </dl>
                </div>

                <!-- Configurações de Anti-Banimento -->
                <div class="mb-6 border-t border-gray-100 pt-6">
                    <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">
                        Configurações de Anti-Banimento (Intervalo e Digitação)
                    </h2>
                    <?= Html::beginForm(['save-settings'], 'post', ['class' => 'text-sm']) ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                            <div>
                                <label for="delay_min" class="block text-sm font-medium text-gray-700 mb-1">Delay Mínimo (ms)</label>
                                <?= Html::input('number', 'delay_min', $config->delay_min ?? 1500, [
                                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border outline-none',
                                    'id' => 'delay_min',
                                    'min' => 0,
                                    'step' => 100
                                ]) ?>
                            </div>
                            <div>
                                <label for="delay_max" class="block text-sm font-medium text-gray-700 mb-1">Delay Máximo (ms)</label>
                                <?= Html::input('number', 'delay_max', $config->delay_max ?? 2500, [
                                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border outline-none',
                                    'id' => 'delay_max',
                                    'min' => 0,
                                    'step' => 100
                                ]) ?>
                            </div>
                        </div>
                        <div class="flex items-center mb-6">
                            <div class="flex items-center h-5">
                                <?= Html::checkbox('simular_digitacao', $config->simular_digitacao ?? true, [
                                    'class' => 'focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded cursor-pointer',
                                    'id' => 'simular_digitacao',
                                    'value' => 1,
                                    'uncheck' => 0
                                ]) ?>
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="simular_digitacao" class="font-medium text-gray-700 cursor-pointer">
                                    Simular Digitação ("Digitando..." / presence composing)
                                </label>
                            </div>
                        </div>
                        <div>
                            <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                <svg class="mr-2 -ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                Salvar Configurações
                            </button>
                        </div>
                    <?= Html::endForm() ?>
                </div>
            <?php endif; ?>

            <!-- Ações -->
            <div class="flex flex-col space-y-3 mt-6">
                <?php if (!$connected): ?>
                    <?= Html::a(
                        '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg> Conectar WhatsApp',
                        ['connect'],
                        ['class' => 'w-full inline-flex justify-center items-center px-4 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors']
                    ) ?>
                <?php else: ?>
                    <?= Html::a(
                        '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Reconectar / Atualizar QR Code',
                        ['connect'],
                        ['class' => 'w-full inline-flex justify-center items-center px-4 py-2 border border-green-600 text-base font-medium rounded-md text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors']
                    ) ?>
                    <?= Html::a(
                        '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg> Desconectar',
                        ['disconnect'],
                        [
                            'class' => 'w-full inline-flex justify-center items-center px-4 py-2 border border-red-300 text-base font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors',
                            'data'  => [
                                'confirm' => 'Tem certeza que deseja desconectar o WhatsApp? O dispositivo perderá a sessão.',
                                'method'  => 'post',
                            ],
                        ]
                    ) ?>
                <?php endif; ?>
            </div>

            <!-- Botão Voltar para Vendas -->
            <div class="mt-4 border-t border-gray-100 pt-4">
                <?= Html::a(
                    '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg> Ir para Vendas',
                    ['/vendas'],
                    ['class' => 'w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors']
                ) ?>
            </div>

        </div>
    </div>

    <!-- Aviso informativo -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4 flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
        </div>
        <div class="ml-3">
            <p class="text-sm text-blue-800 m-0 leading-relaxed">
                <strong class="font-semibold text-blue-900">Como funciona:</strong> Escaneie o QR Code com o aplicativo WhatsApp do seu dispositivo
                para parear. Após a conexão, sua loja poderá enviar notificações automáticas de vendas,
                cobranças e confirmações.
            </p>
        </div>
    </div>

</div>
