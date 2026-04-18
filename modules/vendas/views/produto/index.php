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

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">

    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <p class="text-sm text-gray-500 mt-1">Gerencie seu catálogo de produtos e estoque</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-4 w-full md:w-auto">
                <!-- Filtro Rápido (Toggle) -->
                <div class="bg-white px-4 py-2 rounded-lg shadow-sm border border-gray-200 flex items-center gap-3">
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
                </div>

                <div class="flex gap-2">
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                        ['/vendas/inicio/index'],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300']
                    ) ?>
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Novo Produto',
                        ['create'],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
                    ) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">

        <!-- Filtros e Busca -->
        <div class="bg-white rounded-lg shadow-md mb-6 p-6">
            <form id="filtro-produtos-form" method="get" class="space-y-4">

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

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
                            <option value="sem" <?= Yii::$app->request->get('estoque') == 'sem' ? 'selected' : '' ?>>Sem estoque</option>
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

                <div class="flex gap-2">
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Buscar
                    </button>
                    <?php if (count(Yii::$app->request->queryParams) > 1 || (count(Yii::$app->request->queryParams) == 1 && !isset(Yii::$app->request->queryParams['ativo']))): ?>
                        <?= Html::a('Limpar Filtros', ['index', 'ativo' => '1'], ['class' => 'px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition duration-300']) ?>
                    <?php endif; ?>
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
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">

                        <!-- Imagem -->
                        <div class="relative h-48 bg-gray-200">
                            <?php
                            // Carrega foto principal
                            $fotoPrincipal = $model->fotoPrincipal;
                            if (!$fotoPrincipal && $model->fotos) {
                                $fotoPrincipal = $model->fotos[0] ?? null;
                            }
                            ?>
                            <?php if ($fotoPrincipal && !empty($fotoPrincipal->arquivo_path)): ?>
                                <?php
                                // Constrói URL da foto de forma robusta (funciona em localhost e VPS)
                                $caminhoFoto = ltrim($fotoPrincipal->arquivo_path, '/');

                                // Tenta múltiplas formas de construir a URL
                                $urlFoto = null;

                                // Método 1: Usa Url::to() com schema absoluto
                                try {
                                    $urlFoto = Url::to('@web/' . $caminhoFoto, true);
                                    if (empty($urlFoto) || $urlFoto === '@web/' . $caminhoFoto) {
                                        $urlFoto = null;
                                    }
                                } catch (\Exception $e) {
                                    $urlFoto = null;
                                }

                                // Método 2: Se falhou, usa getAlias('@web')
                                if (empty($urlFoto)) {
                                    try {
                                        $webAlias = Yii::getAlias('@web');
                                        if (!empty($webAlias) && $webAlias !== '@web') {
                                            $urlFoto = rtrim($webAlias, '/') . '/' . ltrim($caminhoFoto, '/');
                                        }
                                    } catch (\Exception $e) {
                                        $urlFoto = null;
                                    }
                                }

                                // Método 3: Fallback usando baseUrl do request
                                if (empty($urlFoto)) {
                                    $request = Yii::$app->request;
                                    $baseUrl = $request->baseUrl;
                                    if (!empty($baseUrl)) {
                                        $urlFoto = rtrim($baseUrl, '/') . '/' . ltrim($caminhoFoto, '/');
                                    } else {
                                        // Último fallback: caminho relativo
                                        $urlFoto = '/' . ltrim($caminhoFoto, '/');
                                    }
                                }
                                ?>
                                <img src="<?= $urlFoto ?>"
                                    alt="<?= Html::encode($model->nome) ?>"
                                    class="w-full h-full object-cover"
                                    onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center\'><svg class=\'w-16 h-16 text-gray-400\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'/></svg></div>';">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            <?php endif; ?>

                            <!-- Badges -->
                            <?php if (!$model->ativo): ?>
                                <span class="absolute top-2 left-2 px-2 py-1 bg-gray-600 text-white text-xs font-semibold rounded">
                                    Inativo
                                </span>
                            <?php endif; ?>
                            <?php if ($model->estoque_atual == 0): ?>
                                <span class="absolute top-2 right-2 px-2 py-1 bg-red-600 text-white text-xs font-semibold rounded">
                                    Sem Estoque
                                </span>
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

                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Preço:</span>
                                    <span class="text-lg font-bold text-green-600">
                                        R$ <?= Yii::$app->formatter->asDecimal($model->preco_venda_sugerido, 2) ?>
                                    </span>
                                </div>
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
                                        'data-preco' => number_format($model->preco_venda_sugerido, 2, ',', '.')
                                    ]
                                ) ?>
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
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <?php
                                            // Carrega foto principal
                                            $fotoPrincipal = $model->fotoPrincipal;
                                            if (!$fotoPrincipal && $model->fotos) {
                                                $fotoPrincipal = $model->fotos[0] ?? null;
                                            }
                                            ?>
                                            <?php if ($fotoPrincipal && !empty($fotoPrincipal->arquivo_path)): ?>
                                                <?php
                                                // Constrói URL da foto de forma robusta (funciona em localhost e VPS)
                                                $caminhoFoto = ltrim($fotoPrincipal->arquivo_path, '/');

                                                // Tenta múltiplas formas de construir a URL
                                                $urlFoto = null;

                                                // Método 1: Usa Url::to() com schema absoluto
                                                try {
                                                    $urlFoto = Url::to('@web/' . $caminhoFoto, true);
                                                    if (empty($urlFoto) || $urlFoto === '@web/' . $caminhoFoto) {
                                                        $urlFoto = null;
                                                    }
                                                } catch (\Exception $e) {
                                                    $urlFoto = null;
                                                }

                                                // Método 2: Se falhou, usa getAlias('@web')
                                                if (empty($urlFoto)) {
                                                    try {
                                                        $webAlias = Yii::getAlias('@web');
                                                        if (!empty($webAlias) && $webAlias !== '@web') {
                                                            $urlFoto = rtrim($webAlias, '/') . '/' . ltrim($caminhoFoto, '/');
                                                        }
                                                    } catch (\Exception $e) {
                                                        $urlFoto = null;
                                                    }
                                                }

                                                // Método 3: Fallback usando baseUrl do request
                                                if (empty($urlFoto)) {
                                                    $request = Yii::$app->request;
                                                    $baseUrl = $request->baseUrl;
                                                    if (!empty($baseUrl)) {
                                                        $urlFoto = rtrim($baseUrl, '/') . '/' . ltrim($caminhoFoto, '/');
                                                    } else {
                                                        // Último fallback: caminho relativo
                                                        $urlFoto = '/' . ltrim($caminhoFoto, '/');
                                                    }
                                                }
                                                ?>
                                                <img src="<?= $urlFoto ?>"
                                                    class="w-10 h-10 rounded object-cover mr-3"
                                                    alt="<?= Html::encode($model->nome) ?>"
                                                    onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="w-10 h-10 rounded bg-gray-200 mr-3 flex items-center justify-center" style="display: none;">
                                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            <?php else: ?>
                                                <div class="w-10 h-10 rounded bg-gray-200 mr-3 flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?= Html::encode($model->nome) ?></div>
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
                                        <span class="text-sm font-semibold text-green-600">
                                            R$ <?= Yii::$app->formatter->asDecimal($model->preco_venda_sugerido, 2) ?>
                                        </span>
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
                                                    data-preco="<?= number_format($model->preco_venda_sugerido, 2, ",", ".") ?>"
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
</script>
