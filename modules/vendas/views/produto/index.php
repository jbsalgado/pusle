<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Produtos';
$this->params['breadcrumbs'][] = $this->title;

$viewMode = Yii::$app->request->get('view', 'cards');
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    
    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
            <div class="flex flex-wrap gap-2">
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

    <div class="max-w-7xl mx-auto">
        
        <!-- Filtros e Busca -->
        <div class="bg-white rounded-lg shadow-md mb-6 p-6">
            <form method="get" class="space-y-4">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    
                    <!-- Busca -->
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                        <input type="text" name="busca" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Nome ou código..."
                               value="<?= Html::encode(Yii::$app->request->get('busca', '')) ?>">
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

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="ativo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Todos</option>
                            <option value="1" <?= Yii::$app->request->get('ativo') === '1' ? 'selected' : '' ?>>Ativos</option>
                            <option value="0" <?= Yii::$app->request->get('ativo') === '0' ? 'selected' : '' ?>>Inativos</option>
                        </select>
                    </div>

                </div>

                <div class="flex gap-2">
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Buscar
                    </button>
                    <?php if (Yii::$app->request->queryParams): ?>
                        <?= Html::a('Limpar Filtros', ['index'], ['class' => 'px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition duration-300']) ?>
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
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
                            <h3 class="text-lg font-semibold text-gray-800 truncate mb-1">
                                <?= Html::encode($model->nome) ?>
                            </h3>
                            <p class="text-xs text-gray-500 mb-2"><?= Html::encode($model->codigo_referencia) ?></p>
                            
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
                                        <?= $model->estoque_atual ?> un
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
                                <?= Html::a('Ver', ['view', 'id' => $model->id], 
                                    ['class' => 'flex-1 text-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-semibold rounded transition duration-300']) ?>
                                <?= Html::a('Editar', ['update', 'id' => $model->id], 
                                    ['class' => 'flex-1 text-center px-3 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-semibold rounded transition duration-300']) ?>
                                <?= Html::beginForm(['delete', 'id' => $model->id], 'post', ['id' => 'delete-form-' . $model->id, 'style' => 'display: inline;']) ?>
                                    <?= Html::button('Excluir', [
                                    'class' => 'flex-1 text-center px-3 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded transition duration-300',
                                        'onclick' => 'return confirmDelete(\'' . $model->id . '\')',
                                ]) ?>
                                <?= Html::endForm() ?>
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
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            <?php else: ?>
                                                <div class="w-10 h-10 rounded bg-gray-200 mr-3 flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?= Html::encode($model->nome) ?></div>
                                                <div class="text-xs text-gray-500"><?= Html::encode($model->codigo_referencia) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap hidden md:table-cell">
                                        <span class="text-sm text-gray-900"><?= $model->categoria ? Html::encode($model->categoria->nome) : '-' ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span class="text-sm font-semibold text-green-600">
                                            R$ <?= Yii::$app->formatter->asDecimal($model->preco_venda_sugerido, 2) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center hidden lg:table-cell">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $model->estoque_atual > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= $model->estoque_atual ?> un
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

<script>
function confirmDelete(id) {
    if (confirm('Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.')) {
        document.getElementById('delete-form-' + id).submit();
    }
    return false;
}
</script>