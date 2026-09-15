<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\marketplace\models\MarketplaceConfig;

/* @var $this yii\web\View */
/* @var $model app\modules\marketplace\models\MarketplaceConfig */
/* @var $form yii\widgets\ActiveForm */

$marketplacesDisponiveis = MarketplaceConfig::getMarketplacesDisponiveis();

// Canais com metadados visuais (ícones, cores, descrições)
$canaisMeta = [
    MarketplaceConfig::MARKETPLACE_MERCADO_LIVRE => [
        'nome' => 'Mercado Livre',
        'sigla' => 'MELI',
        'icon_svg' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'bg_light' => 'bg-yellow-50',
        'border' => 'border-yellow-400',
        'text' => 'text-yellow-900',
        'badge' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
        'dica' => 'Use o App ID e Secret Key do portal Meli Developers.',
    ],
    MarketplaceConfig::MARKETPLACE_SHOPEE => [
        'nome' => 'Shopee',
        'sigla' => 'SHOPEE',
        'icon_svg' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>',
        'bg_light' => 'bg-orange-50',
        'border' => 'border-orange-500',
        'text' => 'text-orange-900',
        'badge' => 'bg-orange-100 text-orange-800 border-orange-300',
        'dica' => 'Use o Partner ID e Partner Key da Shopee Open Platform.',
    ],
    MarketplaceConfig::MARKETPLACE_MAGAZINE_LUIZA => [
        'nome' => 'Magazine Luiza',
        'sigla' => 'MAGALU',
        'icon_svg' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
        'bg_light' => 'bg-blue-50',
        'border' => 'border-blue-500',
        'text' => 'text-blue-900',
        'badge' => 'bg-blue-100 text-blue-800 border-blue-300',
        'dica' => 'Token Bearer da API IntegraCommerce / LuizaLabs.',
    ],
    MarketplaceConfig::MARKETPLACE_TEMU => [
        'nome' => 'Temu Brasil',
        'sigla' => 'TEMU L2L',
        'icon_svg' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>',
        'bg_light' => 'bg-amber-50',
        'border' => 'border-amber-600',
        'text' => 'text-amber-900',
        'badge' => 'bg-amber-100 text-amber-800 border-amber-300',
        'dica' => 'App Key e Secret do programa Temu Local-to-Local.',
    ],
    MarketplaceConfig::MARKETPLACE_IFOOD => [
        'nome' => 'iFood',
        'sigla' => 'IFOOD',
        'icon_svg' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>',
        'bg_light' => 'bg-red-50',
        'border' => 'border-red-500',
        'text' => 'text-red-900',
        'badge' => 'bg-red-100 text-red-800 border-red-300',
        'dica' => 'Merchant ID e Client Credentials do iFood Developer.',
    ],
    MarketplaceConfig::MARKETPLACE_AMAZON => [
        'nome' => 'Amazon',
        'sigla' => 'AMAZON',
        'icon_svg' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
        'bg_light' => 'bg-slate-50',
        'border' => 'border-slate-400',
        'text' => 'text-slate-800',
        'badge' => 'bg-slate-100 text-slate-800 border-slate-300',
        'dica' => 'Chaves SP-API de vendedor Amazon.',
    ],
];

$canalAtual = $model->marketplace ?: MarketplaceConfig::MARKETPLACE_MERCADO_LIVRE;
?>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <?php $form = ActiveForm::begin([
        'id' => 'form-marketplace-config',
        'enableClientValidation' => true,
        'options' => ['class' => 'p-4 sm:p-8 space-y-8'],
    ]); ?>

    <!-- ─── 1. SELEÇÃO VISUAL DO CANAL (MARKETPLACE) ────────────────────────── -->
    <div>
        <label class="block text-sm font-semibold text-slate-800 mb-1.5">
            Selecione o Canal de Venda <span class="text-rose-500">*</span>
        </label>
        <p class="text-xs text-slate-500 mb-3">Escolha a plataforma que deseja conectar com o estoque e vendas do Pulse ERP.</p>

        <!-- Grid de Cards de Marketplace (Mobile First) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 sm:gap-3" id="marketplace-cards">
            <?php foreach ($canaisMeta as $key => $meta): ?>
                <?php $isSelected = ($canalAtual === $key); ?>
                <button type="button"
                        onclick="selecionarCanal('<?= $key ?>')"
                        data-marketplace="<?= $key ?>"
                        class="channel-btn flex flex-col items-center text-center p-3 rounded-xl border-2 transition-all duration-200 cursor-pointer <?= $isSelected ? $meta['border'] . ' ' . $meta['bg_light'] . ' ring-2 ring-offset-1 ring-indigo-500 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50' ?>">
                    <div class="p-2 rounded-lg <?= $meta['badge'] ?> mb-2">
                        <?= $meta['icon_svg'] ?>
                    </div>
                    <span class="text-xs font-bold text-slate-800 leading-tight"><?= $meta['nome'] ?></span>
                    <span class="text-[10px] text-slate-400 font-mono mt-0.5"><?= $meta['sigla'] ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Input Oculto / Select Real do Yii para garantir envio no POST -->
        <div class="mt-3">
            <?= $form->field($model, 'marketplace', [
                'template' => "{input}\n{error}",
            ])->dropDownList($marketplacesDisponiveis, [
                'id' => 'select-marketplace',
                'class' => 'w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition',
                'onchange' => "atualizarVisualCanal(this.value)",
            ]) ?>
        </div>
    </div>

    <hr class="border-slate-200">

    <!-- ─── 2. DADOS DA CONTA & IDENTIFICAÇÃO ────────────────────────────────── -->
    <div>
        <div class="flex items-center gap-2 mb-4">
            <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 text-xs font-bold">1</span>
            <h3 class="text-base sm:text-lg font-bold text-slate-900">Identificação da Conta no Pulse</h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">
                    Nome / Apelido da Conta <span class="text-rose-500">*</span>
                </label>
                <?= $form->field($model, 'apelido_conta', [
                    'template' => "{input}\n{error}",
                ])->textInput([
                    'maxlength' => true,
                    'placeholder' => 'Ex: Loja Matriz, Pulse Oficial, Filial SP',
                    'class' => 'w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition',
                ]) ?>
                <p class="text-xs text-slate-400 mt-1">Nome interno para diferenciar caso você tenha mais de uma conta no mesmo canal.</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">
                    ID Externo do Vendedor (Opcional)
                </label>
                <?= $form->field($model, 'seller_id_externo', [
                    'template' => "{input}\n{error}",
                ])->textInput([
                    'maxlength' => true,
                    'placeholder' => 'Ex: User ID do Mercado Livre ou Shop ID da Shopee',
                    'class' => 'w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition',
                ]) ?>
                <p class="text-xs text-slate-400 mt-1">Geralmente preenchido automaticamente ao realizar a autorização OAuth.</p>
            </div>
        </div>
    </div>

    <hr class="border-slate-200">

    <!-- ─── 3. CREDENCIAIS DE API / CHAVES DO APLICATIVO ─────────────────────── -->
    <div>
        <div class="flex items-center gap-2 mb-2">
            <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 text-xs font-bold">2</span>
            <h3 class="text-base sm:text-lg font-bold text-slate-900">Credenciais de API do Marketplace</h3>
        </div>

        <!-- Banner Informativo -->
        <div class="bg-indigo-50/70 border border-indigo-200/80 rounded-xl p-3.5 sm:p-4 mb-4 flex items-start gap-3">
            <svg class="w-5 h-5 text-indigo-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-xs sm:text-sm text-indigo-900 leading-relaxed">
                <strong>Onde encontrar essas chaves?</strong> Informe as chaves mestras obtidas no portal de desenvolvedores do marketplace.
                Para o <strong>Mercado Livre</strong>, utilize o <code>ID do aplicativo (Client ID)</code> e a <code>Chave secreta (Client Secret)</code> criados no seu painel Meli Developers.
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">
                    Client ID / App ID / Partner ID <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                    </div>
                    <?= $form->field($model, 'client_id', [
                        'template' => "{input}\n{error}",
                    ])->textInput([
                        'maxlength' => true,
                        'placeholder' => 'Ex: 436828978780262',
                        'class' => 'w-full pl-9 pr-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-mono text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition',
                    ]) ?>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">
                    Client Secret / Chave Secreta <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <?= $form->field($model, 'client_secret', [
                        'template' => "{input}\n{error}",
                    ])->passwordInput([
                        'id' => 'input-client-secret',
                        'maxlength' => true,
                        'placeholder' => $model->isNewRecord ? 'Cole a chave secreta gerada no portal' : 'Deixe em branco para manter a chave atual',
                        'class' => 'w-full pl-9 pr-10 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-mono text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition',
                    ]) ?>
                    <button type="button"
                            onclick="togglePasswordVisibility()"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                        <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <hr class="border-slate-200">

    <!-- ─── 4. REGRAS DE PRECIFICAÇÃO & MARKUP NO CANAL ──────────────────────── -->
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 text-xs font-bold">3</span>
            <h3 class="text-base sm:text-lg font-bold text-slate-900">Precificação Inteligente & Margem no Canal</h3>
        </div>
        <p class="text-xs text-slate-500 mb-4">Ajuste os preços dos seus produtos enviados a este marketplace para absorver comissões e taxas automaticamente.</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 items-start">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">
                    Acréscimo Percentual (%)
                </label>
                <div class="relative">
                    <?= $form->field($model, 'markup_percentual', [
                        'template' => "{input}\n{error}",
                    ])->textInput([
                        'type' => 'number',
                        'step' => '0.01',
                        'min' => '0',
                        'placeholder' => 'Ex: 16.00',
                        'class' => 'w-full pl-3.5 pr-8 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition',
                    ]) ?>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 text-sm font-bold">
                        %
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Ex: +16% para cobrir a comissão clássica do canal.</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">
                    Acréscimo Fixo por Produto (R$)
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs font-bold">
                        R$
                    </div>
                    <?= $form->field($model, 'markup_valor_fixo', [
                        'template' => "{input}\n{error}",
                    ])->textInput([
                        'type' => 'number',
                        'step' => '0.01',
                        'min' => '0',
                        'placeholder' => 'Ex: 6.00',
                        'class' => 'w-full pl-9 pr-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition',
                    ]) ?>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Ex: +R$ 6,00 para taxa fixa por item.</p>
            </div>

            <div class="sm:pt-6">
                <label class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-100 transition">
                    <?= Html::activeCheckbox($model, 'arredondar_centavos_99', [
                        'class' => 'w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500 accent-indigo-600 cursor-pointer',
                        'label' => false,
                    ]) ?>
                    <div>
                        <span class="text-xs sm:text-sm font-semibold text-slate-800 block">Arredondar para R$ xx,99</span>
                        <span class="text-[11px] text-slate-400 block">Estratégia de preço psicológico (ex: R$ 49,99)</span>
                    </div>
                </label>
            </div>
        </div>
    </div>

    <hr class="border-slate-200">

    <!-- ─── 5. CONFIGURAÇÕES DE AUTOMAÇÃO & SINCRONIZAÇÃO ─────────────────────── -->
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 text-xs font-bold">4</span>
            <h3 class="text-base sm:text-lg font-bold text-slate-900">Automações & Sincronização em Tempo Real</h3>
        </div>
        <p class="text-xs text-slate-500 mb-4">Selecione quais rotinas do Pulse ERP devem atuar sobre essa conexão de marketplace.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
            <!-- Ativo -->
            <label class="flex items-start gap-3 p-3.5 bg-white border border-slate-200 rounded-xl hover:border-indigo-300 hover:bg-indigo-50/20 cursor-pointer transition">
                <?= Html::activeCheckbox($model, 'ativo', [
                    'class' => 'mt-0.5 w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500 accent-indigo-600 cursor-pointer',
                    'label' => false,
                ]) ?>
                <div>
                    <span class="text-sm font-semibold text-slate-900 block">Conexão Ativa</span>
                    <span class="text-xs text-slate-500 block">Habilita o recebimento e processamento de chamadas dessa loja.</span>
                </div>
            </label>

            <!-- Sincronizar Estoque -->
            <label class="flex items-start gap-3 p-3.5 bg-white border border-slate-200 rounded-xl hover:border-indigo-300 hover:bg-indigo-50/20 cursor-pointer transition">
                <?= Html::activeCheckbox($model, 'sincronizar_estoque', [
                    'class' => 'mt-0.5 w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500 accent-indigo-600 cursor-pointer',
                    'label' => false,
                ]) ?>
                <div>
                    <span class="text-sm font-semibold text-slate-900 block">Sincronização de Estoque Físico</span>
                    <span class="text-xs text-slate-500 block">Atualiza o saldo no marketplace quando houver venda física no PDV.</span>
                </div>
            </label>

            <!-- Sincronizar Pedidos -->
            <label class="flex items-start gap-3 p-3.5 bg-white border border-slate-200 rounded-xl hover:border-indigo-300 hover:bg-indigo-50/20 cursor-pointer transition">
                <?= Html::activeCheckbox($model, 'sincronizar_pedidos', [
                    'class' => 'mt-0.5 w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500 accent-indigo-600 cursor-pointer',
                    'label' => false,
                ]) ?>
                <div>
                    <span class="text-sm font-semibold text-slate-900 block">Importação Automática de Pedidos</span>
                    <span class="text-xs text-slate-500 block">Cria a venda no ERP e baixa estoque assim que o pedido é aprovado.</span>
                </div>
            </label>

            <!-- Sincronizar Produtos -->
            <label class="flex items-start gap-3 p-3.5 bg-white border border-slate-200 rounded-xl hover:border-indigo-300 hover:bg-indigo-50/20 cursor-pointer transition">
                <?= Html::activeCheckbox($model, 'sincronizar_produtos', [
                    'class' => 'mt-0.5 w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500 accent-indigo-600 cursor-pointer',
                    'label' => false,
                ]) ?>
                <div>
                    <span class="text-sm font-semibold text-slate-900 block">Sincronização de Catálogo</span>
                    <span class="text-xs text-slate-500 block">Permite exportar e vincular anúncios diretamente do Pulse ERP.</span>
                </div>
            </label>
        </div>
    </div>

    <!-- ─── 6. BOTÕES DE AÇÃO (MOBILE-FIRST) ─────────────────────────────────── -->
    <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
        <?= Html::a(
            '<svg class="w-4 h-4 inline-block mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Cancelar e Voltar',
            ['index'],
            ['class' => 'w-full sm:w-auto order-2 sm:order-1 px-5 py-3 text-center border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 rounded-xl font-semibold text-sm transition shadow-sm']
        ) ?>

        <button type="submit"
                class="w-full sm:w-auto order-1 sm:order-2 px-7 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-sm shadow-md shadow-indigo-200 hover:shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
            </svg>
            <span><?= $model->isNewRecord ? 'Salvar e Conectar Canal' : 'Atualizar Configurações' ?></span>
        </button>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<!-- Scripts Interativos da View -->
<script>
    function selecionarCanal(key) {
        const select = document.getElementById('select-marketplace');
        if (select) {
            select.value = key;
            atualizarVisualCanal(key);
        }
    }

    function atualizarVisualCanal(selectedKey) {
        const buttons = document.querySelectorAll('.channel-btn');
        buttons.forEach(btn => {
            const mKey = btn.getAttribute('data-marketplace');
            if (mKey === selectedKey) {
                btn.classList.add('ring-2', 'ring-offset-1', 'ring-indigo-500', 'shadow-sm');
                btn.classList.remove('border-slate-200', 'bg-white');
            } else {
                btn.classList.remove('ring-2', 'ring-offset-1', 'ring-indigo-500', 'shadow-sm');
                btn.classList.add('border-slate-200', 'bg-white');
            }
        });
    }

    function togglePasswordVisibility() {
        const input = document.getElementById('input-client-secret');
        if (!input) return;
        if (input.type === 'password') {
            input.type = 'text';
        } else {
            input.type = 'password';
        }
    }
</script>
