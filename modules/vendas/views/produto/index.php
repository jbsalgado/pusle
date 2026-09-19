<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Produtos';
$this->params['breadcrumbs'][] = $this->title;
$viewMode = Yii::$app->request->get('view', 'cards');

// ✅ Carrega biblioteca para leitura de código de barras via webcam
echo '<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>';
?>
<?php $this->registerJsFile('https://cdn.jsdelivr.net/npm/alpinejs@3.12.0/dist/cdn.min.js', ['position' => \yii\web\View::POS_HEAD]); ?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">

    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <p class="text-sm text-gray-500 mt-1">Gerencie seu catálogo de produtos e estoque</p>
            </div>
            
            <!-- Filtro Rápido (Toggles) -->
            <div class="bg-white px-4 py-2 rounded-xl shadow-xs border border-gray-200 flex flex-wrap items-center gap-3 self-stretch sm:self-auto justify-between sm:justify-start">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Filtro Rápido:</span>
                <label class="relative inline-flex items-center cursor-pointer group">
                    <input type="checkbox" id="quick-toggle-ativo" class="sr-only peer" 
                        <?= (Yii::$app->request->get('ativo', '1') === '1') ? 'checked' : '' ?>
                        onchange="syncAndSubmit(this.checked)">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    <span class="ml-2 text-sm font-semibold text-gray-700 group-hover:text-blue-600 transition-colors">
                        Ativos
                    </span>
                </label>
                <div class="h-4 w-px bg-gray-200 hidden sm:block"></div>
                <label class="relative inline-flex items-center cursor-pointer group" title="Filtrar apenas produtos com promoção ativa">
                    <input type="checkbox" id="quick-toggle-promocao" class="sr-only peer" 
                        <?= (Yii::$app->request->get('promocao') === '1') ? 'checked' : '' ?>
                        onchange="syncPromoAndSubmit(this.checked)">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-rose-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-500"></div>
                    <span class="ml-2 text-sm font-semibold text-gray-700 group-hover:text-rose-600 transition-colors flex items-center gap-1">
                        <span>🔥</span>
                        <span>Promoções</span>
                    </span>
                </label>
            </div>
        </div>

        <!-- Barra de Ações e Botões Operacionais (Responsivo) -->
        <div class="flex flex-wrap items-center gap-2 sm:gap-2.5 mt-4 w-full">
            <!-- 1. Cadastrar Produto Rápido -->
            <button type="button" onclick="abrirModalCadastroRapido()" class="inline-flex items-center justify-center px-3.5 py-2.5 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 gap-1.5 sm:gap-2 border border-emerald-400/30 cursor-pointer whitespace-nowrap">
                <span class="text-amber-300 text-base">⚡</span>
                <span>Cadastrar Produto Rápido</span>
            </button>

            <!-- 2. Venda Expressa -->
            <a href="<?= Url::to(['/vendas/venda-expressa/index']) ?>" class="inline-flex items-center justify-center px-3.5 py-2.5 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 gap-1.5 sm:gap-2 border border-amber-400/30 whitespace-nowrap">
                <span class="text-amber-200 text-base">⚡</span>
                <span>Venda Expressa</span>
            </a>

            <!-- 3. Gerar Encarte Digital -->
            <button type="button" onclick="gerarEncarteSelecionados()" class="inline-flex items-center justify-center px-3.5 py-2.5 bg-gradient-to-r from-red-600 to-amber-600 hover:from-red-700 hover:to-amber-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 gap-1.5 sm:gap-2 border border-red-400/30 cursor-pointer whitespace-nowrap">
                <span class="text-amber-200 text-base">📖</span>
                <span>Gerar Encarte Digital</span>
            </button>

            <!-- 4. Gerar Referências em Lote -->
            <button type="button" onclick="abrirModalGerarReferencias()" class="inline-flex items-center justify-center px-3.5 py-2.5 bg-gradient-to-r from-cyan-600 to-blue-700 hover:from-cyan-700 hover:to-blue-800 text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 gap-1.5 sm:gap-2 border border-cyan-400/30 cursor-pointer whitespace-nowrap" title="Gerar e atualizar códigos de referências automáticos">
                <span class="text-cyan-200 text-base">🏷️</span>
                <span>Gerar Referências em Lote</span>
            </button>

            <!-- 4. Gestão de Encartes -->
            <?= Html::a(
                '<svg class="w-4 h-4 inline-block text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><span>Gestão de Encartes</span>',
                ['/vendas/encarte/index'],
                ['class' => 'inline-flex items-center justify-center px-3.5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 gap-1.5 sm:gap-2 border border-slate-700 whitespace-nowrap']
            ) ?>

            <!-- 5. Disparar Cards em Massa -->
            <button type="button" onclick="dispararMassaSelecionados()" class="inline-flex items-center justify-center px-3.5 py-2.5 bg-gradient-to-r from-purple-700 to-indigo-700 hover:from-purple-800 hover:to-indigo-800 text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 gap-1.5 sm:gap-2 border border-purple-400/30 cursor-pointer whitespace-nowrap">
                <svg class="w-4 h-4 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                <span>Disparar Cards em Massa</span>
            </button>

            <!-- 6. Studio de Vídeos -->
            <button type="button" onclick="gerarVideosStudioSelecionados()" class="inline-flex items-center justify-center px-3.5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 gap-1.5 sm:gap-2 border border-indigo-400/30 whitespace-nowrap cursor-pointer">
                <svg class="w-4 h-4 inline-block text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <span>Studio de Vídeos</span>
            </button>

            <!-- 7. Cadastro Categorias -->
            <?= Html::a(
                '<svg class="w-4 h-4 inline-block text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg><span>Cadastro Categorias</span>',
                ['/vendas/categoria/index'],
                ['class' => 'inline-flex items-center justify-center px-3.5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 gap-1.5 sm:gap-2 border border-purple-400/30 whitespace-nowrap']
            ) ?>

            <!-- 8. Novo Produto (Grade Mobile) -->
            <?= Html::a(
                '<span class="text-amber-300 text-base">⚡</span><span>Novo Produto (Grade Mobile)</span>',
                ['/vendas/produto/create-matriz'],
                ['class' => 'inline-flex items-center justify-center px-3.5 py-2.5 bg-gradient-to-r from-indigo-600 to-indigo-800 hover:from-indigo-700 hover:to-indigo-900 text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 gap-1.5 sm:gap-2 border border-indigo-400/30 whitespace-nowrap']
            ) ?>

            <!-- 9. Novo Produto Clássico -->
            <?= Html::a(
                '<svg class="w-4 h-4 inline-block text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg><span>Novo Produto Clássico</span>',
                ['/vendas/produto/create'],
                ['class' => 'inline-flex items-center justify-center px-3.5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 gap-1.5 sm:gap-2 border border-green-400/30 whitespace-nowrap']
            ) ?>

            <!-- 9. Canal Próprio (Direct Hub) -->
            <button type="button" onclick="abrirModalCanalInterno()" class="inline-flex items-center justify-center px-3.5 py-2.5 bg-gradient-to-r from-teal-600 via-emerald-600 to-green-600 hover:from-teal-700 hover:to-green-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 gap-1.5 sm:gap-2 border border-emerald-400/30 cursor-pointer relative group whitespace-nowrap">
                <span class="text-teal-200 text-base">🌐</span>
                <span>Canal Próprio (Direct Hub)</span>
                <?php if (!empty($inboxNaoLidosCount) && $inboxNaoLidosCount > 0): ?>
                    <span id="badgeInboxNaoLidosHeader" class="bg-red-500 text-white text-[10px] font-black px-1.5 py-0.5 rounded-full border border-white shadow-xs animate-pulse">
                        <?= $inboxNaoLidosCount ?>
                    </span>
                <?php else: ?>
                    <span id="badgeInboxNaoLidosHeader" class="bg-red-500 text-white text-[10px] font-black px-1.5 py-0.5 rounded-full border border-white shadow-xs hidden">
                        0
                    </span>
                <?php endif; ?>
            </button>

            <!-- 10. Buscar Mídias & Popular Web -->
            <button type="button" onclick="abrirModalEnriquecimentoWeb()" class="inline-flex items-center justify-center px-3.5 py-2.5 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 gap-1.5 sm:gap-2 border border-blue-400/30 cursor-pointer whitespace-nowrap">
                <span class="text-blue-200 text-base">🌐</span>
                <span>Buscar Mídias & Popular Web</span>
            </button>

            <!-- 11. Voltar -->
            <?= Html::a(
                '<svg class="w-4 h-4 inline-block text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg><span>Voltar</span>',
                ['/vendas/inicio/index'],
                ['class' => 'inline-flex items-center justify-center px-3.5 py-2.5 bg-gray-500 hover:bg-gray-600 text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 gap-1.5 sm:gap-2 border border-gray-400/30 whitespace-nowrap']
            ) ?>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">

        <!-- Filtros e Busca -->
        <div class="bg-white rounded-lg shadow-md mb-6 p-6">
            <form id="filtro-produtos-form" method="get" class="space-y-4">
                <input type="hidden" name="promocao" id="filter-promocao-hidden" value="<?= Html::encode(Yii::$app->request->get('promocao', '')) ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">

                    <!-- Busca -->
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                        <div class="flex gap-2">
                            <input type="text" name="busca" id="busca-produto-index"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Nome ou código..."
                                value="<?= Html::encode(Yii::$app->request->get('busca', '')) ?>">
                            <button type="button" onclick="abrirScannerCamera()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center gap-2 shadow-sm" title="Escanear com a câmera">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Filtro por Referência -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center justify-between">
                            <span>Referência</span>
                            <?php if (Yii::$app->request->get('referencia')): ?>
                                <span class="text-[10px] text-cyan-600 font-bold uppercase">Ativo</span>
                            <?php endif; ?>
                        </label>
                        <input type="text" name="referencia" id="filtro-referencia-index"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase font-mono text-sm"
                            placeholder="Ex: ELET-0001"
                            value="<?= Html::encode(Yii::$app->request->get('referencia', '')) ?>">
                        <label class="inline-flex items-center text-[11px] text-gray-500 mt-1 cursor-pointer select-none hover:text-blue-600 transition">
                            <input type="checkbox" name="sem_referencia" value="1" <?= (Yii::$app->request->get('sem_referencia') === '1') ? 'checked' : '' ?> class="rounded text-blue-600 focus:ring-blue-500 mr-1" onchange="this.form.submit()">
                            <span>Apenas sem ref.</span>
                        </label>
                    </div>

                    <!-- Categoria -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
                        <select name="categoria_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat->id ?>" <?= Yii::$app->request->get('categoria_id') == $cat->id ? 'selected' : '' ?>>
                                    <?= Html::encode($cat->nome) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Estoque -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estoque</label>
                        <select name="estoque" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Todos</option>
                            <option value="com" <?= Yii::$app->request->get('estoque') == 'com' ? 'selected' : '' ?>>Com estoque</option>
                            <option value="zerado" <?= Yii::$app->request->get('estoque') == 'zerado' ? 'selected' : '' ?>>Estoque Zerado</option>
                            <option value="corte" <?= Yii::$app->request->get('estoque') == 'corte' ? 'selected' : '' ?>>Abaixo do Ponto de Corte</option>
                        </select>
                    </div>

                    <!-- Status Dropdown (Original) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="ativo" id="filter-ativo-dropdown" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="" <?= Yii::$app->request->get('ativo') === '' ? 'selected' : '' ?>>Todos</option>
                            <option value="1" <?= (Yii::$app->request->get('ativo', '1') === '1') ? 'selected' : '' ?>>Ativos</option>
                            <option value="0" <?= Yii::$app->request->get('ativo') === '0' ? 'selected' : '' ?>>Inativos</option>
                        </select>
                    </div>

                </div>

                <div class="flex gap-2 flex-wrap">
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Buscar
                    </button>
                    <?php if (count(Yii::$app->request->queryParams) > 1 || (count(Yii::$app->request->queryParams) == 1 && !isset(Yii::$app->request->queryParams['ativo']))): ?>
                        <?= Html::a('Limpar Filtros', ['index', 'ativo' => '1'], ['class' => 'px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition duration-300']) ?>
                    <?php endif; ?>
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>Imprimir Relatório',
                        '#',
                        [
                            'class' => 'inline-flex items-center px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white font-semibold rounded-lg shadow-md transition duration-300',
                            'onclick' => '
                                event.preventDefault();
                                var form = document.getElementById("filtro-produtos-form");
                                var qs = new URLSearchParams(new FormData(form)).toString();
                                var actionUrl = "' . \yii\helpers\Url::to(['/vendas/produto/imprimir-relatorio']) . '";
                                var separator = actionUrl.indexOf("?") !== -1 ? "&" : "?";
                                window.open(actionUrl + separator + qs, "_blank");
                            '
                        ]
                    ) ?>
                </div>

            </form>
        </div>

        <!-- Toggle View e Contador -->
        <div class="flex justify-between items-center mb-4">
            <span class="text-gray-600">
                <?= $dataProvider->getTotalCount() ?> produto(s) encontrado(s)
            </span>
            <div class="flex gap-2">
                <?= Html::a(
                    '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>',
                    ['index', 'view' => 'cards'] + Yii::$app->request->get(),
                    ['class' => 'p-2 rounded-lg transition duration-300 ' . ($viewMode == 'cards' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')]
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/></svg>',
                    ['index', 'view' => 'grid'] + Yii::$app->request->get(),
                    ['class' => 'p-2 rounded-lg transition duration-300 ' . ($viewMode == 'grid' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')]
                ) ?>
            </div>
        </div>

        <?php if ($viewMode == 'cards'): ?>

            <!-- Visualização em Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($dataProvider->getModels() as $model): ?>
                    <?php
                        $emPromocao = (bool)$model->emPromocao;
                        $descontoPromo = $emPromocao ? (float)$model->descontoPromocional : 0;
                        $precoNormal = (float)$model->preco_venda_sugerido;
                        $precoPromo = (float)$model->preco_promocional;
                        $precoFinal = $emPromocao ? $precoPromo : $precoNormal;
                        $economia = max(0, $precoNormal - $precoPromo);
                    ?>
                    <div 
                        class="relative bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 <?= $model->ativo ? '' : 'opacity-60 ring-2 ring-red-300' ?>"
                        id="card-produto-<?= $model->id ?>">

<?php
    // Monta array de URLs das fotos do produto
    $gerarUrlFoto = function($arquivoPath) {
        if (empty($arquivoPath)) {
            return null;
        }
        $caminho = ltrim($arquivoPath, '/');
        try {
            $caminhoFisico = Yii::getAlias('@webroot/' . $caminho);
            if (!is_file($caminhoFisico)) {
                $caminhoFisicoApp = Yii::getAlias('@app/web/' . $caminho);
                if (!is_file($caminhoFisicoApp)) {
                    $directPath = dirname(dirname(dirname(__DIR__))) . '/web/' . $caminho;
                    if (!is_file($directPath)) {
                        return null;
                    }
                }
            }
        } catch (\Throwable $e) {}

        try {
            $webAlias = Yii::getAlias('@web');
            if (!empty($webAlias) && $webAlias !== '@web') {
                return rtrim($webAlias, '/') . '/' . $caminho;
            }
        } catch (\Throwable $e) {}

        if (Yii::$app->has('request')) {
            $base = Yii::$app->request->baseUrl;
            if (!empty($base)) {
                return rtrim($base, '/') . '/' . $caminho;
            }
        }
        return '/' . $caminho;
    };
    $photos = [];
    if ($model->fotoPrincipal && !empty($model->fotoPrincipal->arquivo_path)) {
        $urlFoto = $gerarUrlFoto($model->fotoPrincipal->arquivo_path);
        if ($urlFoto) {
            $photos[] = $urlFoto;
        }
    }
    if (!empty($model->fotos)) {
        foreach ($model->fotos as $f) {
            if ($f && !empty($f->arquivo_path)) {
                $url = $gerarUrlFoto($f->arquivo_path);
                if ($url && !in_array($url, $photos)) {
                    $photos[] = $url;
                }
            }
        }
    }
?>
<?php
    $placeholderSvg = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'><rect width='48' height='48' fill='%23f3f4f6'/><text x='24' y='28' font-family='sans-serif' font-size='8' fill='%239ca3af' text-anchor='middle'>SEM FOTO</text></svg>";
    $fotoInicial = !empty($photos) ? $photos[0] : $placeholderSvg;
    $carouselConfig = [
        'photos' => !empty($photos) ? array_values($photos) : [$placeholderSvg],
        'index' => 0,
    ];
?>
<div class="relative h-48 bg-gray-100 overflow-hidden group flex items-center justify-center p-2" x-data="<?= Html::encode(json_encode($carouselConfig)) ?>">
    <!-- Badges Canto Superior Esquerdo -->
    <div class="absolute top-2 left-2 z-20 flex items-center gap-1.5">
        <div class="bg-white/90 backdrop-blur-sm p-1 rounded-lg shadow border border-gray-200">
            <input type="checkbox" name="produto_massa_chk" value="<?= $model->id ?>" data-nome="<?= Html::encode($model->nome) ?>" class="w-5 h-5 rounded text-purple-600 focus:ring-purple-500 cursor-pointer block">
        </div>
        <?php if (!$model->ativo): ?>
            <div class="flex items-center gap-1 bg-red-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full shadow-md badge-inativo-<?= $model->id ?>">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                INATIVO
            </div>
        <?php endif; ?>
    </div>

    <!-- Badges Canto Superior Direito -->
    <div class="absolute top-2 right-2 z-20 flex flex-col items-end gap-1 pointer-events-none">
        <?php if ($emPromocao): ?>
            <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-black bg-gradient-to-r from-rose-600 via-pink-600 to-red-600 text-white shadow-lg border border-white/40 tracking-wider shadow-rose-500/30 animate-pulse">
                <span>🔥</span>
                <span>-<?= round($descontoPromo) ?>% OFF</span>
            </div>
        <?php endif; ?>

        <?php if ($model->estoque_atual == 0): ?>
            <span class="px-2 py-0.5 bg-red-600 text-white text-[10px] font-bold rounded-md shadow">
                Sem Estoque
            </span>
        <?php endif; ?>

        <?php if (count($photos) > 1): ?>
            <div class="bg-black/60 backdrop-blur-xs text-white text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 shadow">
                <svg class="w-3 h-3 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span x-text="(index + 1) + '/<?= count($photos) ?>'"><?= '1/' . count($photos) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <img :src="photos[index]" 
         src="<?= Html::encode($fotoInicial) ?>" 
         alt="<?= Html::encode($model->nome) ?>" 
         class="w-full h-full object-contain select-none transition-transform duration-300 group-hover:scale-105" 
         onerror="this.onerror=null; this.src='<?= $placeholderSvg ?>';">

    <?php if (count($photos) > 1): ?>
        <button type="button" 
                x-show="photos.length > 1" 
                @click.stop="index = (index === 0 ? photos.length - 1 : index - 1)" 
                class="absolute left-2 top-1/2 -translate-y-1/2 z-10 bg-white/80 hover:bg-white text-gray-800 rounded-full p-1.5 focus:outline-none transition-all duration-200 shadow-md hover:scale-110">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button type="button" 
                x-show="photos.length > 1" 
                @click.stop="index = (index === photos.length - 1 ? 0 : index + 1)" 
                class="absolute right-2 top-1/2 -translate-y-1/2 z-10 bg-white/80 hover:bg-white text-gray-800 rounded-full p-1.5 focus:outline-none transition-all duration-200 shadow-md hover:scale-110">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div x-show="photos.length > 1" class="absolute bottom-2 left-1/2 transform -translate-x-1/2 z-10 flex space-x-1.5 bg-black/40 backdrop-blur-xs px-2 py-1 rounded-full">
            <template x-for="(photo, i) in photos" :key="i">
                <span @click.stop="index = i" 
                      :class="{'bg-white w-3.5': i === index, 'bg-white/50 w-1.5': i !== index}" 
                      class="h-1.5 rounded-full cursor-pointer transition-all duration-300 block"></span>
            </template>
        </div>
    <?php endif; ?>
</div>

                        <!-- Conteúdo -->
                        <div class="p-4">
                            <div class="text-xs font-mono mb-1 truncate flex flex-col gap-0.5" title="Código de Barras / Ref">
                                <?php if ($model->codigo_barras): ?>
                                    <span class="text-blue-600 font-bold">
                                        EAN: <?= Html::encode($model->codigo_barras) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="text-gray-400 text-[10px]">
                                    Ref: <?= Html::encode($model->codigo_referencia ?: '-') ?>
                                </span>
                                <?php if ($model->com_nota): ?>
                                    <div class="mt-1">
                                        <span class="inline-flex items-center px-1 rounded-[2px] text-[9px] font-bold bg-blue-100 text-blue-700 border border-blue-200" title="Última compra com Nota Fiscal">
                                            NF
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1 truncate" title="<?= Html::encode($model->nome) ?>">
                                <?= Html::encode($model->nome) ?>
                            </h3>
                            <?php if ($model->marca): ?>
                                <div class="text-xs text-gray-500 mb-2 font-medium">
                                    Marca: <?= Html::encode($model->marca) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($model->categoria): ?>
                                <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full mb-3">
                                    <?= Html::encode($model->categoria->nome) ?>
                                </span>
                            <?php endif; ?>

                            <!-- Preços e Descontos -->
                            <?php if ($emPromocao): ?>
                                <div class="bg-gradient-to-br from-rose-50 via-pink-50/50 to-orange-50/40 rounded-xl p-3 border border-rose-200/80 mb-3 space-y-1.5 shadow-2xs">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-rose-600 flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                            Em Promoção
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-black bg-rose-600 text-white shadow-2xs">
                                            -<?= round($descontoPromo) ?>% OFF
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-baseline justify-between gap-2">
                                        <div>
                                            <span class="text-xs text-gray-400 line-through mr-1 font-medium">
                                                De R$ <?= Yii::$app->formatter->asDecimal($precoNormal, 2) ?>
                                            </span>
                                            <span class="text-xs font-bold text-rose-500">Por</span>
                                        </div>
                                        <div class="text-xl font-black text-rose-600 tracking-tight">
                                            R$ <?= Yii::$app->formatter->asDecimal($precoPromo, 2) ?>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between text-[11px] pt-1.5 border-t border-rose-200/60 text-rose-700">
                                        <span class="font-medium">Economia:</span>
                                        <span class="font-bold text-emerald-600">
                                            R$ <?= Yii::$app->formatter->asDecimal($economia, 2) ?>
                                        </span>
                                    </div>

                                    <?php if (!empty($model->data_fim_promocao)): ?>
                                        <div class="text-[10px] text-gray-500 flex items-center justify-between pt-0.5">
                                            <span>⏳ Válido até:</span>
                                            <strong class="text-gray-700"><?= Yii::$app->formatter->asDatetime($model->data_fim_promocao, 'php:d/m/Y H:i') ?></strong>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="space-y-2 mb-4">
                                <?php if (!$emPromocao): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Preço:</span>
                                        <span class="text-lg font-bold text-green-600">
                                            R$ <?= Yii::$app->formatter->asDecimal($precoNormal, 2) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Estoque:</span>
                                    <span class="font-semibold <?= $model->estoque_atual > 0 ? 'text-green-600' : 'text-red-600' ?>">
                                        <?= Yii::$app->formatter->asDecimal($model->estoque_atual, $model->venda_fracionada ? 3 : 0) ?> <?= Html::encode($model->unidade_medida ?: 'un') ?>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Margem:</span>
                                    <span class="text-sm font-semibold text-blue-600">
                                        <?= Yii::$app->formatter->asDecimal($model->margemLucro, 2) ?>%
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Markup:</span>
                                    <span class="text-sm font-semibold text-green-600">
                                        <?= Yii::$app->formatter->asDecimal($model->markup, 2) ?>%
                                    </span>
                                </div>
                            </div>

                            <!-- Ações -->
                            <div class="flex gap-2">
                                <?= Html::a(
                                    'Ver',
                                    ['view', 'id' => $model->id],
                                    ['class' => 'flex-1 text-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-semibold rounded transition duration-300']
                                ) ?>
                                <?= Html::a(
                                    'Editar',
                                    ['update', 'id' => $model->id],
                                    ['class' => 'flex-1 text-center px-3 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-semibold rounded transition duration-300']
                                ) ?>
                                <?= Html::beginForm(['delete', 'id' => $model->id], 'post', ['id' => 'delete-form-' . $model->id, 'style' => 'display: inline;']) ?>
                                <?= Html::button('Excluir', [
                                    'class' => 'flex-1 text-center px-3 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded transition duration-300',
                                    'onclick' => 'return confirmDelete(\'' . $model->id . '\')',
                                ]) ?>
                                <?= Html::endForm() ?>
                            </div>

                            <div class="mt-2">
                                <?= Html::button(
                                    '<svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>IMPRIMIR CODIGO DE BARRAS(termica)',
                                    [
                                        'class' => 'w-full flex items-center justify-center px-3 py-2 bg-slate-700 hover:bg-slate-800 text-white text-xs font-bold rounded transition duration-300 shadow-sm',
                                        'onclick' => "imprimirEtiqueta(this.dataset.nome, this.dataset.codigo, this.dataset.preco)",
                                        'data-nome' => $model->nome,
                                        'data-codigo' => $model->codigo_barras ?: $model->codigo_referencia ?: '',
                                        'data-preco' => number_format($precoFinal, 2, ',', '.')
                                    ]
                                ) ?>
                            </div>

                            <!-- Toggle: Visível no Catálogo -->
                            <div class="mt-2 px-1 pb-1 flex items-center justify-between border-t border-gray-100 pt-2">
                                <span class="text-xs text-gray-400 font-medium select-none">Catálogo:</span>
                                <label class="relative inline-flex items-center cursor-pointer group" title="<?= $model->ativo ? 'Clique para desativar no catálogo' : 'Clique para ativar no catálogo' ?>">
                                    <input 
                                        type="checkbox" 
                                        class="sr-only peer"
                                        id="toggle-ativo-<?= $model->id ?>"
                                        data-id="<?= $model->id ?>"
                                        data-nome="<?= Html::encode($model->nome) ?>"
                                        <?= $model->ativo ? 'checked' : '' ?>
                                        onchange="toggleAtivoProduto(this)">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-300
                                                rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full
                                                peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                                after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4
                                                after:transition-all peer-checked:bg-emerald-500 transition-colors duration-300 shadow-inner">
                                    </div>
                                    <span class="ml-1.5 text-xs font-bold select-none
                                                 <?= $model->ativo ? 'text-emerald-600' : 'text-red-500' ?>"
                                          id="toggle-label-<?= $model->id ?>">
                                        <?= $model->ativo ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>

            <!-- Visualização em Grid/Tabela -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="w-10 px-4 py-3 text-center">
                                    <input type="checkbox" id="chk_select_all_grid" onclick="toggleSelectAllGrid(this.checked)" class="w-4 h-4 rounded text-purple-600 focus:ring-purple-500 cursor-pointer">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Categoria</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Marca</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Preço</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Estoque</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Margem</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($dataProvider->getModels() as $model): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 text-center whitespace-nowrap">
                                        <input type="checkbox" name="produto_massa_chk" value="<?= $model->id ?>" data-nome="<?= Html::encode($model->nome) ?>" class="w-4 h-4 rounded text-purple-600 focus:ring-purple-500 cursor-pointer">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <?php
                                            // Carrega foto principal (getFotoPrincipal já valida existência física do arquivo)
                                            $fotoPrincipal = $model->fotoPrincipal;
                                            ?>
                                            <?php if ($fotoPrincipal && !empty($fotoPrincipal->arquivo_path)): ?>
                                                <?php
                                                // Constrói URL da foto de forma robusta (funciona em localhost e VPS)
                                                $caminhoFoto = ltrim($fotoPrincipal->arquivo_path, '/');
                                                $urlFoto = null;

                                                try {
                                                    $webAlias = Yii::getAlias('@web');
                                                    if (!empty($webAlias) && $webAlias !== '@web') {
                                                        $urlFoto = rtrim($webAlias, '/') . '/' . $caminhoFoto;
                                                    }
                                                } catch (\Throwable $e) {}

                                                if (empty($urlFoto) && Yii::$app->has('request')) {
                                                    $baseUrl = Yii::$app->request->baseUrl;
                                                    if (!empty($baseUrl)) {
                                                        $urlFoto = rtrim($baseUrl, '/') . '/' . $caminhoFoto;
                                                    }
                                                }

                                                if (empty($urlFoto)) {
                                                    $urlFoto = '/' . $caminhoFoto;
                                                }
                                                ?>
                                                <img src="<?= $urlFoto ?>"
                                                    class="w-10 h-10 rounded object-cover mr-3"
                                                    alt="<?= Html::encode($model->nome) ?>"
                                                    onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="w-10 h-10 rounded bg-gray-200 mr-3 flex items-center justify-center" style="display: none;">
                                                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            <?php else: ?>
                                                <div class="w-10 h-10 rounded bg-gray-200 mr-3 flex items-center justify-center">
                                                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 flex items-center gap-1.5">
                                                    <span><?= Html::encode($model->nome) ?></span>
                                                    <?php if ($model->emPromocao): ?>
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-black bg-rose-600 text-white shadow-2xs">
                                                            🔥 -<?= round($model->descontoPromocional) ?>%
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($model->marca): ?>
                                                    <div class="text-[10px] text-gray-500 font-medium">Marca: <?= Html::encode($model->marca) ?></div>
                                                <?php endif; ?>
                                                <div class="text-xs font-mono mt-1">
                                                    <?php if ($model->codigo_barras): ?>
                                                        <span class="text-blue-600 font-bold mr-2">EAN: <?= Html::encode($model->codigo_barras) ?></span>
                                                    <?php endif; ?>
                                                    <span class="text-gray-400">Ref: <?= Html::encode($model->codigo_referencia ?: '-') ?></span>
                                                    <?php if ($model->com_nota): ?>
                                                        <span class="inline-flex items-center px-1 rounded-[2px] text-[8px] font-bold bg-blue-100 text-blue-700 border border-blue-200 ml-1" title="Última compra com Nota Fiscal">
                                                            NF
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap hidden md:table-cell">
                                        <span class="text-sm text-gray-900"><?= $model->categoria ? Html::encode($model->categoria->nome) : '-' ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap hidden lg:table-cell text-sm text-gray-500">
                                        <?= Html::encode($model->marca ?: '-') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <?php if ($model->emPromocao): ?>
                                            <div class="flex flex-col items-end">
                                                <span class="text-xs text-gray-400 line-through font-medium">
                                                    De R$ <?= Yii::$app->formatter->asDecimal($model->preco_venda_sugerido, 2) ?>
                                                </span>
                                                <span class="text-sm font-black text-rose-600">
                                                    R$ <?= Yii::$app->formatter->asDecimal($model->preco_promocional, 2) ?>
                                                </span>
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-700 border border-rose-200 mt-0.5">
                                                    -<?= round($model->descontoPromocional) ?>% OFF
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-sm font-semibold text-green-600">
                                                R$ <?= Yii::$app->formatter->asDecimal($model->preco_venda_sugerido, 2) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center hidden lg:table-cell">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $model->estoque_atual > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= Yii::$app->formatter->asDecimal($model->estoque_atual, $model->venda_fracionada ? 3 : 0) ?> <?= Html::encode($model->unidade_medida ?: 'un') ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center hidden lg:table-cell">
                                        <span class="text-sm text-blue-600"><?= Yii::$app->formatter->asDecimal($model->margemLucro, 2) ?>%</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end gap-2">
                                            <?= Html::a('Ver', ['view', 'id' => $model->id], ['class' => 'text-blue-600 hover:text-blue-900']) ?>
                                            <?= Html::a('Editar', ['update', 'id' => $model->id], ['class' => 'text-yellow-600 hover:text-yellow-900']) ?>
                                            <?= Html::beginForm(['delete', 'id' => $model->id], 'post', ['id' => 'delete-form-' . $model->id, 'style' => 'display: inline;']) ?>
                                            <?= Html::button('Excluir', [
                                                'class' => 'text-red-600 hover:text-red-900 bg-transparent border-0 p-0 cursor-pointer underline',
                                                'onclick' => 'return confirmDelete(\'' . $model->id . '\')',
                                                'type' => 'button',
                                            ]) ?>
                                            <?= Html::endForm() ?>
                                            <button type="button" 
                                                    onclick="imprimirEtiqueta(this.dataset.nome, this.dataset.codigo, this.dataset.preco)"
                                                    data-nome="<?= Html::encode($model->nome) ?>"
                                                    data-codigo="<?= Html::encode($model->codigo_barras ?: $model->codigo_referencia ?: "") ?>"
                                                    data-preco="<?= number_format($model->getPrecoFinal(), 2, ",", ".") ?>"
                                                    class="text-slate-600 hover:text-slate-900" 
                                                    title="Imprimir Etiqueta">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>

        <!-- Paginação -->
        <div class="mt-6">
            <?= LinkPager::widget([
                'pagination' => $dataProvider->pagination,
                'options' => ['class' => 'flex justify-center space-x-2'],
                'linkOptions' => ['class' => 'px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50'],
                'activePageCssClass' => 'bg-blue-600 text-white border-blue-600',
                'disabledPageCssClass' => 'opacity-50 cursor-not-allowed',
                'prevPageLabel' => '←',
                'nextPageLabel' => '→',
            ]) ?>
        </div>

    </div>
</div>

<!-- Modal de Scanner de Código de Barras (Webcam) -->
<div id="modal-scanner-ean-index" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75 p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h2M4 8h16M4 16h16M4 20h4M4 4h4" />
                </svg>
                Escanear Código de Barras
            </h3>
            <button type="button" onclick="fecharScannerCamera()" class="text-gray-400 hover:text-red-500 transition-colors p-2 rounded-full hover:bg-red-50">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-4 bg-gray-900 relative aspect-video flex items-center justify-center">
            <div id="reader-ean-index" class="w-full"></div>
            <div class="absolute inset-x-8 inset-y-8 border-2 border-blue-500 border-dashed rounded-lg pointer-events-none opacity-50"></div>
        </div>
        <div class="px-6 py-4 bg-gray-50 flex flex-col gap-3">
            <div id="scanner-ean-feedback-index" class="hidden text-center py-2 px-3 rounded-lg text-sm font-medium"></div>
            <button type="button" onclick="fecharScannerCamera()" class="w-full py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold rounded-lg transition-colors">
                Cancelar
            </button>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id) {
        if (confirm('Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.')) {
            document.getElementById('delete-form-' + id).submit();
        }
        return false;
    }

    function imprimirEtiqueta(nome, codigo, preco) {
        if (!codigo) {
            alert('Produto sem código de barras ou referência para geração da etiqueta.');
            return;
        }

        var printWindow = window.open('', '_blank', 'width=400,height=600');
        if (!printWindow) {
            alert('Por favor, permita pop-ups para imprimir a etiqueta.');
            return;
        }

        printWindow.document.write('<html><head><title>Etiqueta - ' + nome + '</title>' +
            '<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>' +
            '<style>' +
            'body { font-family: Arial, sans-serif; text-align: center; margin: 0; padding: 5mm; width: 80mm; color: #000; }' +
            '.header { border-bottom: 1px dashed #000; margin-bottom: 3mm; padding-bottom: 2mm; font-size: 10px; font-family: monospace; }' +
            '.nome { font-size: 14px; font-weight: bold; margin-bottom: 2mm; text-transform: uppercase; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }' +
            '#barcode { width: 100%; max-height: 80px; }' +
            '.preco-container { margin-top: 3mm; border-top: 1px dashed #000; padding-top: 2mm; }' +
            '.preco-label { font-size: 10px; font-family: monospace; }' +
            '.preco { font-size: 24px; font-weight: 900; }' +
            '@page { margin: 0; size: auto; } @media print { body { width: 100%; } }' +
            '</style></head><body>' +
            '<div class="header">ETIQUETA DE PRODUTO</div>' +
            '<div class="nome">' + nome + '</div>' +
            '<svg id="barcode"></svg>' +
            '<div class="preco-container">' +
            '<div class="preco-label">PREÇO DE VENDA</div>' +
            '<div class="preco">R$ ' + preco + '</div>' +
            '</div>' +
            '<script>' +
            'window.onload = function() {' +
            '    try {' +
            '        JsBarcode("#barcode", "' + codigo + '", {' +
                '            format: "CODE128", width: 2, height: 60, displayValue: true, fontSize: 14, margin: 5' +
                '        });' +
                '        setTimeout(function() { window.print(); window.close(); }, 800);' +
                '    } catch (e) { ' +
                '        console.error("Erro ao gerar barcode:", e);' +
                '        document.body.innerHTML += "<p style=\'color:red\'>Erro ao gerar código de barras: " + e.message + "</p>";' +
                '    }' +
                '};<\/script></body></html>');
        printWindow.document.close();
    }

    // --- SUPORTE A LEITOR DE CÓDIGO DE BARRAS (USB/Scanner) ---
    (function() {
        var barcodeAccumulator = "";
        var lastKeyTime = Date.now();

        window.addEventListener("keydown", function(e) {
            var currentTime = Date.now();
            
            if (currentTime - lastKeyTime > 100) {
                barcodeAccumulator = "";
            }

            if (e.key.length === 1) {
                barcodeAccumulator += e.key;
                lastKeyTime = currentTime;
            }

            if (e.key === "Enter" && barcodeAccumulator.length >= 3) {
                var potentialBarcode = barcodeAccumulator.trim();
                var activeEl = document.activeElement;
                var isInput = activeEl.tagName === "INPUT" || activeEl.tagName === "TEXTAREA";
                
                if (!isInput || activeEl.id === "busca-produto-index") {
                    e.preventDefault();
                    var inputBusca = document.getElementById('busca-produto-index');
                    if (inputBusca) {
                        inputBusca.value = potentialBarcode;
                        inputBusca.form.submit();
                    }
                    barcodeAccumulator = "";
                }
            }
        });
        console.log("[Scanner] Leitor USB inicializado.");
    })();

    // --- SUPORTE A SCANNER VIA WEBCAM ---
    var html5QrCodeIndex = null;

    window.abrirScannerCamera = function() {
        var modal = document.getElementById('modal-scanner-ean-index');
        if (!modal) return;

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        var feedback = document.getElementById('scanner-ean-feedback-index');
        if (feedback) feedback.classList.add('hidden');

        if (!html5QrCodeIndex) {
            html5QrCodeIndex = new Html5Qrcode("reader-ean-index");
        }

        var config = {
            fps: 10,
            qrbox: { width: 250, height: 150 },
            aspectRatio: 1.0
        };

        html5QrCodeIndex.start(
            { facingMode: "environment" },
            config,
            onScanSuccessIndex
        ).catch(function(err) {
            console.error("[Scanner] Erro ao iniciar câmera:", err);
            alert("Não foi possível acessar a câmera.");
            fecharScannerCamera();
        });
    };

    window.fecharScannerCamera = function() {
        var modal = document.getElementById('modal-scanner-ean-index');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        if (html5QrCodeIndex && html5QrCodeIndex.isScanning) {
            html5QrCodeIndex.stop().catch(function(err) { console.error("[Scanner] Erro ao parar:", err); });
        }
    };

    function onScanSuccessIndex(decodedText, decodedResult) {
        console.log("[Scanner] Código detectado: " + decodedText);

        var feedback = document.getElementById('scanner-ean-feedback-index');
        if (feedback) {
            feedback.textContent = "Lido: " + decodedText;
            feedback.classList.remove('hidden', 'bg-red-100', 'text-red-700');
            feedback.classList.add('bg-green-100', 'text-green-700');
        }

        var inputBusca = document.getElementById('busca-produto-index');
        if (inputBusca) {
            inputBusca.value = decodedText;
            setTimeout(function() {
                inputBusca.form.submit();
            }, 500);
        }
    }

    // --- SINCRONIZAÇÃO DE FILTRO RÁPIDO (TOGGLE) ---
    window.syncAndSubmit = function(isEnabled) {
        const dropdown = document.getElementById('filter-ativo-dropdown');
        const form = document.getElementById('filtro-produtos-form');
        if (dropdown && form) {
            dropdown.value = isEnabled ? '1' : '0';
            form.submit();
        }
    };

    window.syncPromoAndSubmit = function(isEnabled) {
        const input = document.getElementById('filter-promocao-hidden');
        const form = document.getElementById('filtro-produtos-form');
        if (input && form) {
            input.value = isEnabled ? '1' : '';
            form.submit();
        }
    };

    // Sincroniza o toggle se o dropdown for alterado manualmente
    const dropdownAtivo = document.getElementById('filter-ativo-dropdown');
    if (dropdownAtivo) {
        dropdownAtivo.addEventListener('change', function() {
            const toggle = document.getElementById('quick-toggle-ativo');
            if (toggle) {
                toggle.checked = (this.value === '1');
            }
        });
    }

    // --- FUNÇÕES DE DISPARO EM MASSA DE CARDS ---
    window.toggleSelectAllGrid = function(checked) {
        const checkboxes = document.querySelectorAll('input[name="produto_massa_chk"]');
        checkboxes.forEach(c => c.checked = checked);
    };

    window.dispararMassaSelecionados = function() {
        const checkboxes = document.querySelectorAll('input[name="produto_massa_chk"]:checked');
        const ids = Array.from(checkboxes).map(c => c.value);

        if (ids.length === 0) {
            // Se nenhum marcado especificamente, pergunta se deseja enviar todos visíveis na página
            const todosNaPagina = Array.from(document.querySelectorAll('input[name="produto_massa_chk"]')).map(c => c.value);
            if (todosNaPagina.length === 0) {
                alert('Nenhum produto encontrado para o disparo.');
                return;
            }

            if (confirm('Nenhum produto marcado especificamente. Deseja selecionar todos os ' + todosNaPagina.length + ' produtos exibidos nesta página?')) {
                document.querySelectorAll('input[name="produto_massa_chk"]').forEach(c => c.checked = true);
                abrirModalDisparoMassa(todosNaPagina);
            }
            return;
        }

        abrirModalDisparoMassa(ids);
    };

    window.gerarEncarteSelecionados = function() {
        const checkboxes = document.querySelectorAll('input[name="produto_massa_chk"]:checked');
        const ids = Array.from(checkboxes).map(c => c.value);

        if (ids.length === 0) {
            const todosNaPagina = Array.from(document.querySelectorAll('input[name="produto_massa_chk"]')).map(c => c.value);
            abrirModalGerarEncarte(todosNaPagina);
            return;
        }

        abrirModalGerarEncarte(ids);
    };

    window.gerarVideosStudioSelecionados = function() {
        const checkboxes = document.querySelectorAll('input[name="produto_massa_chk"]:checked');
        const ids = Array.from(checkboxes).map(c => c.value);

        if (ids.length > 0) {
            window.location.href = '<?= Url::to(['/vendas/produto-video/studio']) ?>?produto_ids=' + encodeURIComponent(ids.join(','));
        } else {
            window.location.href = '<?= Url::to(['/vendas/produto-video/studio']) ?>';
        }
    };
</script>

<?= $this->render('_modal_disparo_massa') ?>
<?= $this->render('_modal_gerar_encarte') ?>
<?= $this->render('_modal_gerar_referencias') ?>
<?= $this->render('_modal_cadastro_rapido', ['lojaId' => Yii::$app->user->id]) ?>
<?= $this->render('_modal_enriquecimento_web', ['lojaId' => Yii::$app->user->id]) ?>
<?php
$slugLoja = $usuarioLoja ? $usuarioLoja->slug : 'loja';
$hubUrlCompleta = Url::to(['/hub/index', 'slug' => $slugLoja], true);
?>
<?= $this->render('_modal_canal_interno', [
    'usuarioLoja' => $usuarioLoja ?? null,
    'lojaConfig' => $lojaConfig ?? null,
    'hubUrlCompleta' => $hubUrlCompleta,
]) ?>

<script>
    // Polling em segundo plano para o contador de pedidos não lidos no Header da tela de produtos
    (function() {
        setInterval(function() {
            if (document.hidden) return;
            fetch('<?= Url::to(['/vendas/produto/get-inbox']) ?>')
                .then(r => r.json())
                .then(data => {
                    if (data && data.success) {
                        const total = data.total_nao_lidos || 0;
                        const badge = document.getElementById('badgeInboxNaoLidosHeader');
                        if (badge) {
                            if (total > 0) {
                                badge.textContent = total;
                                badge.classList.remove('hidden');
                                badge.style.display = 'inline-flex';
                            } else {
                                badge.classList.add('hidden');
                                badge.style.display = 'none';
                            }
                        }
                    }
                })
                .catch(() => {});
        }, 30000);
    })();
</script>

<script>
    // Polling em segundo plano para o contador de pedidos não lidos no Header da tela de produtos
    (function() {
        setInterval(function() {
            if (document.hidden) return;
            fetch('<?= Url::to(['/vendas/produto/get-inbox']) ?>')
                .then(r => r.json())
                .then(data => {
                    if (data && data.success) {
                        const total = data.total_nao_lidos || 0;
                        const badge = document.getElementById('badgeInboxNaoLidosHeader');
                        if (badge) {
                            if (total > 0) {
                                badge.textContent = total;
                                badge.classList.remove('hidden');
                                badge.style.display = 'inline-flex';
                            } else {
                                badge.classList.add('hidden');
                                badge.style.display = 'none';
                            }
                        }
                    }
                })
                .catch(() => {});
        }, 30000);
    })();

    // ==========================================================
    // Toggle Ativo/Inativo do produto no catálogo público
    // ==========================================================
    async function toggleAtivoProduto(checkbox) {
        const id      = checkbox.dataset.id;
        const nome    = checkbox.dataset.nome;
        const card    = document.getElementById('card-produto-' + id);
        const label   = document.getElementById('toggle-label-' + id);
        const badges  = card ? card.querySelectorAll('[class*="badge-inativo-' + id + '"]') : [];

        checkbox.disabled = true; // Bloqueia duplo-clique

        try {
            const formData = new FormData();
            formData.append('id', id);
            // CSRF do Yii2 disponível globalmente via yii.getCsrfParam() / getCsrfToken()
            const csrfParam = (typeof yii !== 'undefined' && yii.getCsrfParam)
                ? yii.getCsrfParam() : '_csrf';
            const csrfToken = (typeof yii !== 'undefined' && yii.getCsrfToken)
                ? yii.getCsrfToken()
                : (document.querySelector('meta[name="csrf-token"]')?.content || '');
            formData.append(csrfParam, csrfToken);

            const resp = await fetch('<?= Url::to(['/vendas/produto/toggle-ativo']) ?>', {
                method: 'POST',
                body: formData,
            });
            const data = await resp.json();

            if (data.success) {
                // Atualiza visual do card instantaneamente
                if (data.ativo) {
                    // ATIVAR: remover opacidade e borda vermelha
                    card?.classList.remove('opacity-60', 'ring-2', 'ring-red-300');
                    if (label) {
                        label.textContent = 'Ativo';
                        label.classList.remove('text-red-500');
                        label.classList.add('text-emerald-600');
                    }
                    // Remove badges INATIVO do card
                    card?.querySelectorAll('[class*="badge-inativo"]').forEach(el => el.remove());
                } else {
                    // DESATIVAR: adicionar opacidade e borda vermelha
                    card?.classList.add('opacity-60', 'ring-2', 'ring-red-300');
                    if (label) {
                        label.textContent = 'Inativo';
                        label.classList.remove('text-emerald-600');
                        label.classList.add('text-red-500');
                    }
                    // Cria badge INATIVO dinamicamente se não existir
                    if (card && !card.querySelector('[class*="badge-inativo"]')) {
                        const badge = document.createElement('div');
                        badge.className = 'absolute top-2 left-10 z-20 flex items-center gap-1 bg-red-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full shadow-md badge-inativo-' + id;
                        badge.innerHTML = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg> INATIVO';
                        card.querySelector('.relative')?.prepend(badge) || card.prepend(badge);
                    }
                }
                // Toast de confirmação
                mostrarToastProduto(data.message, data.ativo ? 'success' : 'warning');
            } else {
                // Reverte o checkbox em caso de erro
                checkbox.checked = !checkbox.checked;
                mostrarToastProduto(data.message || 'Erro ao atualizar produto.', 'error');
            }
        } catch (e) {
            checkbox.checked = !checkbox.checked;
            mostrarToastProduto('Erro de conexão. Tente novamente.', 'error');
            console.error('[toggleAtivoProduto]', e);
        } finally {
            checkbox.disabled = false;
        }
    }

    function mostrarToastProduto(msg, tipo = 'success') {
        // Remove toast anterior se existir
        document.getElementById('toast-produto-toggle')?.remove();

        const cores = {
            success: 'bg-emerald-600',
            warning: 'bg-amber-500',
            error:   'bg-red-600',
        };
        const icones = {
            success: '✅',
            warning: '🔴',
            error:   '❌',
        };

        const toast = document.createElement('div');
        toast.id = 'toast-produto-toggle';
        toast.className = `fixed bottom-6 right-6 z-[9999] flex items-center gap-3 px-5 py-3 rounded-xl shadow-xl text-white text-sm font-semibold
                           ${cores[tipo] || cores.success} transition-all duration-300 opacity-0 translate-y-4`;
        toast.innerHTML = `<span class="text-base">${icones[tipo] || '✅'}</span><span>${msg}</span>`;
        document.body.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.remove('opacity-0', 'translate-y-4');
        });
        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-4');
            setTimeout(() => toast.remove(), 400);
        }, 3500);
    }
</script>

