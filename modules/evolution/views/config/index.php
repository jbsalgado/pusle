<?php

/** @var \yii\web\View $this */
/** @var \app\modules\evolution\models\WhatsappConfig|null $config */
/** @var bool $connected */
/** @var string|null $connectedNumber */
/** @var \app\modules\evolution\models\WhatsappTemplate[] $templates */

use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\evolution\models\WhatsappConfig;
use app\modules\evolution\models\WhatsappTemplate;

$this->title = 'Integração WhatsApp (Híbrida) — PULSE-PLUS';

$isMeta = ($config !== null && $config->isMetaOficial());
$activeTab = $isMeta ? 'meta' : 'evolution';
?>

<div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8" x-data="{ tab: '<?= $activeTab ?>', showModalTemplate: false }">

    <!-- Cabeçalho da Página -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <!-- WhatsApp SVG Icon -->
                <svg class="w-8 h-8 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
                <h1 class="text-2xl font-bold text-gray-900 m-0">
                    WhatsApp Gateway Multi-Provedor
                </h1>
            </div>
            <p class="text-gray-500 text-sm m-0">Gerencie a conexão WhatsApp da sua loja via motor QR Code ou API Oficial da Meta.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold px-3 py-1.5 rounded-full <?= $isMeta ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' ?>">
                <?= $isMeta ? '⭐ Provedor: Meta Cloud API Oficial' : '⚡ Provedor: QR Code (Evolution Go)' ?>
            </span>
            <a href="<?= Url::to(['/vendas/inicio/index']) ?>" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 text-xs font-bold shadow-xs transition-colors" title="Voltar para a página inicial de Vendas">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Voltar para Vendas
            </a>
        </div>
    </div>

    <!-- Navegação por Abas -->
    <div class="flex border-b border-gray-200 mb-6 bg-white rounded-t-xl px-4 pt-2 shadow-sm">
        <button type="button" @click="tab = 'evolution'" :class="tab === 'evolution' ? 'border-green-600 text-green-700 border-b-2 font-bold' : 'text-gray-500 hover:text-gray-700'" class="py-3 px-4 text-sm transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
            WhatsApp Padrão (QR Code / Gratuito)
        </button>
        <button type="button" @click="tab = 'meta'" :class="tab === 'meta' ? 'border-purple-600 text-purple-700 border-b-2 font-bold' : 'text-gray-500 hover:text-gray-700'" class="py-3 px-4 text-sm transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            WhatsApp Oficial (Meta Cloud API)
        </button>
        <button type="button" @click="tab = 'templates'" :class="tab === 'templates' ? 'border-blue-600 text-blue-700 border-b-2 font-bold' : 'text-gray-500 hover:text-gray-700'" class="py-3 px-4 text-sm transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
            Templates HSM (Meta Oficial)
        </button>
    </div>

    <!-- ABA 1: WHATSAPP PADRÃO (EVOLUTION / QR CODE) -->
    <div x-show="tab === 'evolution'" class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            
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
                        <p class="m-0 text-sm text-red-600 mt-0.5">Clique em "Conectar WhatsApp" para gerar o QR Code.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detalhes da Conexão -->
            <?php if ($connected && $config !== null): ?>
                <div class="mb-8">
                    <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">
                        Detalhes da Conexão QR Code
                    </h2>
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 gap-x-4 text-sm">
                        <dt class="text-gray-500">Número Conectado</dt>
                        <dd class="sm:col-span-2 text-gray-900 font-bold text-base flex items-center gap-2">
                            <span class="text-emerald-600 font-mono">
                                <?php if (!empty($connectedNumber)): ?>
                                    +<?= Html::encode($connectedNumber) ?>
                                <?php else: ?>
                                    Conectado
                                <?php endif; ?>
                            </span>
                        </dd>

                        <dt class="text-gray-500">Instância</dt>
                        <dd class="sm:col-span-2 text-gray-900 font-mono text-xs"><code class="bg-gray-100 px-2 py-1 rounded"><?= Html::encode($config->instance_name) ?></code></dd>

                        <dt class="text-gray-500">Status</dt>
                        <dd class="sm:col-span-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                <?= Html::encode($config->status) ?>
                            </span>
                        </dd>
                    </dl>
                </div>
            <?php endif; ?>

            <!-- Ações QR Code -->
            <div class="flex flex-col space-y-3 mb-6">
                <?php if (!$connected): ?>
                    <?= Html::a(
                        '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg> Conectar WhatsApp (QR Code)',
                        ['/evolution/config/connect'],
                        ['class' => 'w-full inline-flex justify-center items-center px-4 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors']
                    ) ?>
                <?php else: ?>
                    <div class="flex gap-3">
                        <?= Html::a(
                            '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Reconectar QR Code',
                            ['/evolution/config/connect'],
                            ['class' => 'w-1/2 inline-flex justify-center items-center px-4 py-2 border border-green-600 text-sm font-medium rounded-md text-green-700 bg-white hover:bg-green-50 transition-colors']
                        ) ?>
                        <?= Html::a(
                            '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg> Desconectar',
                            ['/evolution/config/disconnect'],
                            [
                                'class' => 'w-1/2 inline-flex justify-center items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 transition-colors',
                                'data'  => [
                                    'confirm' => 'Tem certeza que deseja desconectar o WhatsApp?',
                                    'method'  => 'post',
                                ],
                            ]
                        ) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Configurações de Anti-Banimento e Proteção -->
            <div class="border-t border-gray-100 pt-6">
                <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-2">
                    <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider m-0">
                        Configurações Anti-Banimento & Limites Diários (Modo QR Code)
                    </h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        Envios Hoje: <?= (int)($config->mensagens_enviadas_hoje ?? 0) ?> / <?= (int)($config->limite_diario_mensagens ?? 150) ?>
                    </span>
                </div>

                <?= Html::beginForm(['/evolution/config/save-settings'], 'post', ['class' => 'text-sm']) ?>
                    <div class="bg-gray-50 p-4 rounded-lg mb-4 border border-gray-200">
                        <h3 class="text-xs font-bold text-gray-700 uppercase mb-3">1. Intervalo Humano (Jitter) & Presença</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                            <div>
                                <label for="delay_min" class="block text-xs font-medium text-gray-700 mb-1">Delay Mínimo (ms) — Recomendado: 15000</label>
                                <?= Html::input('number', 'delay_min', $config->delay_min ?? 15000, [
                                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm p-2 border outline-none bg-white',
                                    'id' => 'delay_min',
                                    'min' => 1000,
                                    'step' => 1000
                                ]) ?>
                            </div>
                            <div>
                                <label for="delay_max" class="block text-xs font-medium text-gray-700 mb-1">Delay Máximo (ms) — Recomendado: 45000</label>
                                <?= Html::input('number', 'delay_max', $config->delay_max ?? 45000, [
                                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm p-2 border outline-none bg-white',
                                    'id' => 'delay_max',
                                    'min' => 1000,
                                    'step' => 1000
                                ]) ?>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="flex items-center h-5">
                                <?= Html::checkbox('simular_digitacao', $config->simular_digitacao ?? true, [
                                    'class' => 'h-4 w-4 text-blue-600 border-gray-300 rounded cursor-pointer',
                                    'id' => 'simular_digitacao',
                                    'value' => 1,
                                    'uncheck' => 0
                                ]) ?>
                            </div>
                            <div class="ml-3 text-xs">
                                <label for="simular_digitacao" class="font-medium text-gray-700 cursor-pointer">
                                    Simular Digitação Humana ("Digitando..." / presence composing antes do envio)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg mb-4 border border-gray-200">
                        <h3 class="text-xs font-bold text-gray-700 uppercase mb-3">2. Teto Diário & Pausas de Lote</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="limite_diario_mensagens" class="block text-xs font-medium text-gray-700 mb-1">Limite Máximo Diário</label>
                                <?= Html::input('number', 'limite_diario_mensagens', $config->limite_diario_mensagens ?? 150, [
                                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm p-2 border outline-none bg-white',
                                    'id' => 'limite_diario_mensagens',
                                    'min' => 10,
                                    'max' => 1000
                                ]) ?>
                            </div>
                            <div>
                                <label for="lote_tamanho" class="block text-xs font-medium text-gray-700 mb-1">Tamanho do Lote</label>
                                <?= Html::input('number', 'lote_tamanho', $config->lote_tamanho ?? 15, [
                                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm p-2 border outline-none bg-white',
                                    'id' => 'lote_tamanho',
                                    'min' => 5,
                                    'max' => 100
                                ]) ?>
                            </div>
                            <div>
                                <label for="lote_pausa_segundos" class="block text-xs font-medium text-gray-700 mb-1">Pausa do Lote (segundos)</label>
                                <?= Html::input('number', 'lote_pausa_segundos', $config->lote_pausa_segundos ?? 120, [
                                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm p-2 border outline-none bg-white',
                                    'id' => 'lote_pausa_segundos',
                                    'min' => 30,
                                    'max' => 900
                                ]) ?>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg mb-6 border border-gray-200">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-bold text-gray-700 uppercase m-0">3. Proxy Dedicado (Opcional)</h3>
                            <span class="text-[11px] text-emerald-600 font-medium">Deixe vazio para usar a conexão padrão</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="proxy_host" class="block text-xs font-medium text-gray-700 mb-1">Host/IP e Porta</label>
                                <?= Html::input('text', 'proxy_host', $config->proxy_host ?? '', [
                                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm p-2 border outline-none bg-white',
                                    'id' => 'proxy_host',
                                    'placeholder' => 'ex: 177.54.12.8:8080'
                                ]) ?>
                            </div>
                            <div>
                                <label for="proxy_user" class="block text-xs font-medium text-gray-700 mb-1">Usuário do Proxy</label>
                                <?= Html::input('text', 'proxy_user', $config->proxy_user ?? '', [
                                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm p-2 border outline-none bg-white',
                                    'id' => 'proxy_user',
                                    'placeholder' => 'Opcional'
                                ]) ?>
                            </div>
                            <div>
                                <label for="proxy_pass" class="block text-xs font-medium text-gray-700 mb-1">Senha do Proxy</label>
                                <?= Html::input('password', 'proxy_pass', $config->proxy_pass ?? '', [
                                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm p-2 border outline-none bg-white',
                                    'id' => 'proxy_pass',
                                    'placeholder' => 'Opcional'
                                ]) ?>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        Salvar Configurações Anti-Banimento
                    </button>
                <?= Html::endForm() ?>
            </div>

        </div>
    </div>

    <!-- ABA 2: WHATSAPP OFICIAL (META CLOUD API) -->
    <div x-show="tab === 'meta'" class="space-y-6" style="display: none;">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
                <div>
                    <h2 class="text-base font-bold text-gray-900 m-0 flex items-center gap-2">
                        <span class="inline-block w-2.5 h-2.5 rounded-full <?= $isMeta ? 'bg-purple-600' : 'bg-gray-300' ?>"></span>
                        Configuração da API Oficial da Meta (Cloud API)
                    </h2>
                    <p class="text-xs text-gray-500 mt-1 m-0">Zero risco de bloqueio de socket, alta taxa de entrega e velocidade máxima.</p>
                </div>

                <div class="text-right">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full <?= $isMeta ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-700' ?>">
                        <?= $isMeta ? 'Ativo como Provedor Principal' : 'Inativo' ?>
                    </span>
                </div>
            </div>

            <?= Html::beginForm(['/evolution/config/save-meta'], 'post', ['class' => 'space-y-4']) ?>
                
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Provedor Ativo para esta Loja:</label>
                    <div class="flex items-center gap-6">
                        <label class="inline-flex items-center cursor-pointer text-sm">
                            <input type="radio" name="provider" value="evolution" <?= !$isMeta ? 'checked' : '' ?> class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500">
                            <span class="ml-2 font-medium text-gray-700">WhatsApp Padrão (QR Code / Evolution)</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer text-sm">
                            <input type="radio" name="provider" value="meta_cloud" <?= $isMeta ? 'checked' : '' ?> class="h-4 w-4 text-purple-600 border-gray-300 focus:ring-purple-500">
                            <span class="ml-2 font-medium text-gray-700">WhatsApp Oficial (Meta Cloud API)</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                    <div>
                        <label for="meta_waba_id" class="block text-xs font-medium text-gray-700 mb-1">WhatsApp Business Account ID (WABA ID) *</label>
                        <?= Html::input('text', 'meta_waba_id', $config->meta_waba_id ?? '', [
                            'class' => 'block w-full rounded-md border-gray-300 shadow-sm sm:text-sm p-2.5 border outline-none bg-white font-mono',
                            'id' => 'meta_waba_id',
                            'placeholder' => 'Ex: 104829384918234'
                        ]) ?>
                        <span class="text-[11px] text-gray-500">Encontrado no painel do Meta Business Manager</span>
                    </div>

                    <div>
                        <label for="meta_phone_number_id" class="block text-xs font-medium text-gray-700 mb-1">Phone Number ID (ID do Número de Telefone) *</label>
                        <?= Html::input('text', 'meta_phone_number_id', $config->meta_phone_number_id ?? '', [
                            'class' => 'block w-full rounded-md border-gray-300 shadow-sm sm:text-sm p-2.5 border outline-none bg-white font-mono',
                            'id' => 'meta_phone_number_id',
                            'placeholder' => 'Ex: 109283746592817'
                        ]) ?>
                        <span class="text-[11px] text-gray-500">ID do número gerado dentro do seu App na Meta</span>
                    </div>
                </div>

                <div>
                    <label for="meta_access_token" class="block text-xs font-medium text-gray-700 mb-1">Access Token Permanente (System User Token) *</label>
                    <?= Html::textarea('meta_access_token', $config->meta_access_token ?? '', [
                        'class' => 'block w-full rounded-md border-gray-300 shadow-sm sm:text-sm p-2.5 border outline-none bg-white font-mono',
                        'id' => 'meta_access_token',
                        'rows' => 3,
                        'placeholder' => 'EAAG...'
                    ]) ?>
                    <span class="text-[11px] text-gray-500">Token gerado em Usuários do Sistema com permissões `whatsapp_business_messaging` e `whatsapp_business_management`</span>
                </div>

                <div>
                    <label for="meta_webhook_verify_token" class="block text-xs font-medium text-gray-700 mb-1">Webhook Verify Token (Opcional)</label>
                    <?= Html::input('text', 'meta_webhook_verify_token', $config->meta_webhook_verify_token ?? 'pulse_meta_webhook_token_2026', [
                        'class' => 'block w-full rounded-md border-gray-300 shadow-sm sm:text-sm p-2.5 border outline-none bg-white font-mono',
                        'id' => 'meta_webhook_verify_token'
                    ]) ?>
                </div>

                <!-- URL do Webhook -->
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-3 text-xs text-purple-900">
                    <p class="font-bold mb-1">📡 URL de Callback do Webhook na Meta:</p>
                    <code class="bg-white px-2 py-1 rounded border border-purple-200 font-mono text-[11px] select-all block break-all">
                        <?= Url::to(['/evolution/meta-webhook/index'], true) ?>
                    </code>
                    <p class="mt-1 text-purple-700 m-0">Insira esta URL no painel do WhatsApp no Meta for Developers e assine o campo <code>messages</code>.</p>
                </div>

                <div class="pt-2">
                    <button type="submit" class="inline-flex justify-center items-center px-5 py-2.5 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 transition-colors">
                        Salvar Credenciais da Meta
                    </button>
                </div>
            <?= Html::endForm() ?>

        </div>
    </div>

    <!-- ABA 3: TEMPLATES HSM (META OFICIAL) -->
    <div x-show="tab === 'templates'" class="space-y-6" style="display: none;">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 pb-4 border-b border-gray-100">
                <div>
                    <h2 class="text-base font-bold text-gray-900 m-0">Templates HSM Aprovados pela Meta</h2>
                    <p class="text-xs text-gray-500 mt-1 m-0">Mensagens pré-aprovadas obrigatórias para envios fora da janela de 24h (cobranças, orçamentos, promoções).</p>
                </div>

                <div class="flex items-center gap-3">
                    <?= Html::a(
                        '<svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Sincronizar da Meta',
                        ['/evolution/config/sync-templates'],
                        ['class' => 'inline-flex items-center px-3 py-2 border border-gray-300 text-xs font-semibold rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors']
                    ) ?>
                    <button type="button" @click="showModalTemplate = true" class="inline-flex items-center px-3 py-2 border border-transparent text-xs font-semibold rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> Novo Template
                    </button>
                </div>
            </div>

            <!-- Tabela de Templates -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase">Nome do Template</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase">Categoria</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase">Idioma</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase">Status Meta</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase">Corpo do Texto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <?php if (empty($templates)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    Nenhum template encontrado. Clique em <strong>"Sincronizar da Meta"</strong> para puxar os modelos da sua conta WABA ou crie um novo.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($templates as $tmpl): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono font-bold text-gray-900"><?= Html::encode($tmpl->name) ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-700"><?= Html::encode($tmpl->category) ?></span>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-gray-500"><?= Html::encode($tmpl->language) ?></td>
                                    <td class="px-4 py-3"><?= $tmpl->getStatusBadge() ?></td>
                                    <td class="px-4 py-3 text-gray-700 max-w-xs truncate" title="<?= Html::encode($tmpl->body_text) ?>">
                                        <?= Html::encode($tmpl->body_text) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>

        <!-- Modal Criação de Template -->
        <div x-show="showModalTemplate" class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6" @click.away="showModalTemplate = false">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                    <h3 class="text-sm font-bold text-gray-900 m-0">Criar Novo Template HSM (Meta)</h3>
                    <button type="button" @click="showModalTemplate = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <?= Html::beginForm(['/evolution/config/create-template'], 'post', ['class' => 'space-y-4 text-xs']) ?>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Nome do Template (apenas minúsculas e _)</label>
                        <input type="text" name="name" required placeholder="ex: notificacao_pedido_confirmado" class="w-full rounded border-gray-300 p-2 border font-mono">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Categoria</label>
                            <select name="category" class="w-full rounded border-gray-300 p-2 border">
                                <option value="UTILITY">UTILITY (Cobranças / Pedidos)</option>
                                <option value="MARKETING">MARKETING (Ofertas / Promoções)</option>
                                <option value="AUTHENTICATION">AUTHENTICATION (OTP / 2FA)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Idioma</label>
                            <input type="text" name="language" value="pt_BR" class="w-full rounded border-gray-300 p-2 border font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Texto do Corpo (use {{1}}, {{2}} para variáveis)</label>
                        <textarea name="body_text" required rows="4" placeholder="Olá {{1}}, seu pedido #{{2}} no valor de R$ {{3}} foi confirmado!" class="w-full rounded border-gray-300 p-2 border"></textarea>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Rodapé (Opcional)</label>
                        <input type="text" name="footer_text" placeholder="Ex: Atendimento Pulse" class="w-full rounded border-gray-300 p-2 border">
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                        <button type="button" @click="showModalTemplate = false" class="px-3 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-50">Cancelar</button>
                        <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white font-semibold hover:bg-blue-700">Submeter à Meta</button>
                    </div>
                <?= Html::endForm() ?>
            </div>
        </div>
    </div>

</div>
