<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

$this->title = $model->nome;
$this->params['breadcrumbs'][] = ['label' => 'Produtos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">

        <!-- Header com ações -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
            <div class="flex flex-wrap gap-2">
                <?php
                // URL para criar novo produto, pré-preenchendo a categoria se existir
                $createUrl = ['create'];
                if ($model->categoria_id) {
                    $createUrl['categoria_id'] = $model->categoria_id;
                }
                ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Novo Produto',
                    $createUrl,
                    ['class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-300']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar',
                    ['update', 'id' => $model->id],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold rounded-lg transition duration-300']
                ) ?>
                <?= Html::beginForm(['delete', 'id' => $model->id], 'post', ['id' => 'delete-form']) ?>
                <?= Html::button(
                    '<svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>Excluir',
                    [
                        'class' => 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition duration-300',
                        'onclick' => 'return confirmDelete()',
                    ]
                ) ?>
                <?= Html::endForm() ?>
                <?= Html::button(
                    '<svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>Gerar Card Social',
                    [
                        'class' => 'inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition duration-300 shadow-md',
                        'onclick' => 'abrirModalCardSocial()',
                    ]
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>Criar Vídeo 9:16',
                    ['/vendas/produto-video/studio', 'produto_id' => $model->id],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition duration-300 shadow-md']
                ) ?>
                <?= Html::button(
                    '<svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>Imprimir Etiqueta',
                    [
                        'class' => 'inline-flex items-center px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white font-semibold rounded-lg transition duration-300',
                        'onclick' => "imprimirEtiqueta(this.dataset.nome, this.dataset.codigo, this.dataset.preco)",
                        'data-nome' => $model->nome,
                        'data-codigo' => $model->codigo_barras ?: $model->codigo_referencia ?: '',
                        'data-preco' => number_format($model->preco_venda_sugerido, 2, ',', '.')
                    ]
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                    ['index'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg transition duration-300']
                ) ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Coluna Esquerda - Fotos e Info Rápida -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Foto Principal -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <?php
                    // Tenta carregar foto principal primeiro
                    $fotoPrincipal = null;

                    // Se as fotos foram carregadas com eager loading, usa-as
                    if ($model->isRelationPopulated('fotos') && !empty($model->fotos)) {
                        // Busca foto marcada como principal
                        foreach ($model->fotos as $foto) {
                            if ($foto->eh_principal) {
                                $fotoPrincipal = $foto;
                                break;
                            }
                        }
                        // Se não encontrou principal, pega a primeira
                        if (!$fotoPrincipal) {
                            $fotoPrincipal = $model->fotos[0] ?? null;
                        }
                    } else {
                        // Se não foram carregadas, tenta usar o método getFotoPrincipal
                        $fotoPrincipal = $model->fotoPrincipal;
                        // Se não encontrou principal, busca qualquer foto
                        if (!$fotoPrincipal) {
                            $fotos = $model->getFotos()->limit(1)->all();
                            $fotoPrincipal = $fotos[0] ?? null;
                        }
                    }
                    ?>
                    <?php if ($fotoPrincipal && !empty($fotoPrincipal->arquivo_path)): ?>
                        <?php
                        // Constrói URL da foto usando o método do modelo que já tem fallbacks
                        $urlFoto = $fotoPrincipal->url ?? null;

                        // Se não conseguir usar o método do modelo, constrói manualmente
                        if (empty($urlFoto)) {
                            $caminhoFoto = ltrim($fotoPrincipal->arquivo_path, '/');

                            // Tenta Url::to primeiro
                            try {
                                $urlFoto = Url::to('@web/' . $caminhoFoto, true);
                                if (empty($urlFoto) || $urlFoto === '@web/' . $caminhoFoto) {
                                    $urlFoto = null;
                                }
                            } catch (\Exception $e) {
                                $urlFoto = null;
                            }

                            // Fallback: usa getAlias
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

                            // Último fallback: usa baseUrl do request
                            if (empty($urlFoto)) {
                                $request = Yii::$app->request;
                                $baseUrl = $request->baseUrl;
                                $urlFoto = !empty($baseUrl)
                                    ? rtrim($baseUrl, '/') . '/' . ltrim($caminhoFoto, '/')
                                    : '/' . ltrim($caminhoFoto, '/');
                            }
                        }
                        ?>
                        <img src="<?= Html::encode($urlFoto) ?>"
                            alt="<?= Html::encode($model->nome) ?>"
                            class="w-full h-64 object-cover"
                            onerror="console.error('Erro ao carregar imagem:', this.src); this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'200\'%3E%3Crect fill=\'%23e5e7eb\' width=\'300\' height=\'200\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%239ca3af\' font-family=\'sans-serif\' font-size=\'14\'%3EErro ao carregar imagem%3C/text%3E%3C/svg%3E';">
                    <?php else: ?>
                        <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                            <svg class="w-24 h-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    <?php endif; ?>

                    <div class="p-4">
                        <?php if ($model->categoria): ?>
                            <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-sm font-semibold rounded-full">
                                <?= Html::encode($model->categoria->nome) ?>
                            </span>
                        <?php endif; ?>

                        <p class="text-sm text-gray-500 mt-2">
                            Código Ref: <?= Html::encode($model->codigo_referencia) ?>
                        </p>
                        <?php if ($model->codigo_barras): ?>
                            <p class="text-sm font-bold text-gray-700 mt-1 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                EAN: <?= Html::encode($model->codigo_barras) ?>
                            </p>
                        <?php endif; ?>

                        <div class="mt-3">
                            <?php if ($model->ativo): ?>
                                <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 text-sm font-semibold rounded-full">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Ativo
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-800 text-sm font-semibold rounded-full">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                    Inativo
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Card de Preços -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informações Financeiras</h3>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center pb-2 border-b">
                            <span class="text-sm text-gray-600">Preço de Custo:</span>
                            <span class="text-base font-semibold text-gray-900">
                                R$ <?= Yii::$app->formatter->asDecimal($model->preco_custo, 2) ?>
                            </span>
                        </div>
                        <?php if ($model->valor_frete > 0): ?>
                            <div class="flex justify-between items-center pb-2 border-b">
                                <span class="text-sm text-gray-600">Valor do Frete:</span>
                                <span class="text-base font-semibold text-gray-700">
                                    R$ <?= Yii::$app->formatter->asDecimal($model->valor_frete, 2) ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center pb-2 border-b">
                                <span class="text-sm text-gray-600">Custo Total:</span>
                                <span class="text-base font-semibold text-gray-900">
                                    R$ <?= Yii::$app->formatter->asDecimal($model->custoTotal, 2) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between items-center pb-2 border-b">
                            <span class="text-sm text-gray-600">Preço de Venda:</span>
                            <span class="text-xl font-bold text-green-600">
                                R$ <?= Yii::$app->formatter->asDecimal($model->preco_venda_sugerido, 2) ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b">
                            <span class="text-sm text-gray-600">Margem de Lucro:</span>
                            <span class="text-base font-semibold text-blue-600">
                                <?= Yii::$app->formatter->asDecimal($model->margemLucro, 2) ?>%
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Markup:</span>
                            <span class="text-base font-semibold text-green-600">
                                <?= Yii::$app->formatter->asDecimal($model->markup, 2) ?>%
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Card de Estoque -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Estoque</h3>
                    <div class="text-center">
                        <div class="text-5xl font-bold <?= $model->estoque_atual > 0 ? 'text-green-600' : 'text-red-600' ?> mb-2">
                            <?= Yii::$app->formatter->asDecimal($model->estoque_atual, $model->venda_fracionada ? 3 : 0) ?>
                        </div>
                        <p class="text-sm text-gray-600"><?= Html::encode($model->unidade_medida ?: 'unidades') ?> disponíveis</p>
                        <?php if ($model->estoque_atual == 0): ?>
                            <div class="mt-3 px-3 py-2 bg-red-100 text-red-800 text-sm font-semibold rounded">
                                Produto sem estoque
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Coluna Direita - Detalhes Completos -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Descrição -->
                <?php if ($model->descricao): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Descrição</h3>
                        <p class="text-gray-700 leading-relaxed"><?= Html::encode($model->descricao) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Informações Detalhadas -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">Informações Detalhadas</h3>
                    </div>
                    <div class="p-6">
                        <?= DetailView::widget([
                            'model' => $model,
                            'options' => ['class' => 'w-full'],
                            'template' => '<div class="grid grid-cols-3 gap-4 py-3 border-b last:border-b-0"><dt class="text-sm font-medium text-gray-500">{label}</dt><dd class="col-span-2 text-sm text-gray-900">{value}</dd></div>',
                            'attributes' => [
                                'id',
                                'nome',
                                'marca',
                                'codigo_referencia',
                                'codigo_barras',
                                [
                                    'attribute' => 'categoria_id',
                                    'value' => $model->categoria ? $model->categoria->nome : '-',
                                    'label' => 'Categoria'
                                ],
                                [
                                    'attribute' => 'preco_custo',
                                    'value' => 'R$ ' . Yii::$app->formatter->asDecimal($model->preco_custo, 2),
                                ],
                                [
                                    'attribute' => 'valor_frete',
                                    'value' => $model->valor_frete > 0 ? 'R$ ' . Yii::$app->formatter->asDecimal($model->valor_frete, 2) : '-',
                                    'label' => 'Valor do Frete',
                                ],
                                [
                                    'label' => 'Custo Total',
                                    'value' => 'R$ ' . Yii::$app->formatter->asDecimal($model->custoTotal, 2),
                                ],
                                [
                                    'attribute' => 'preco_venda_sugerido',
                                    'value' => 'R$ ' . Yii::$app->formatter->asDecimal($model->preco_venda_sugerido, 2),
                                ],
                                [
                                    'label' => 'Margem de Lucro',
                                    'value' => Yii::$app->formatter->asDecimal($model->margemLucro, 2) . '%',
                                ],
                                [
                                    'label' => 'Markup',
                                    'value' => Yii::$app->formatter->asDecimal($model->markup, 2) . '%',
                                ],
                                [
                                    'attribute' => 'estoque_atual',
                                    'value' => Yii::$app->formatter->asDecimal($model->estoque_atual, $model->venda_fracionada ? 3 : 0) . ' ' . ($model->unidade_medida ?: 'un'),
                                    'label' => 'Estoque Atual',
                                ],
                                'data_criacao:datetime',
                                'data_atualizacao:datetime',
                            ],
                        ]) ?>
                    </div>
                </div>

                <!-- Galeria de Fotos -->
                <?php
                // Garante que as fotos sejam carregadas
                $fotos = $model->fotos ?: [];
                if (empty($fotos) && !$model->isRelationPopulated('fotos')) {
                    $fotos = $model->getFotos()->all();
                }
                ?>
                <?php if (!empty($fotos)): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b">
                            <h3 class="text-lg font-semibold text-gray-900">Galeria de Fotos (<?= count($fotos) ?>)</h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                <?php foreach ($fotos as $foto): ?>
                                    <div class="relative group">
                                        <?php
                                        // Constrói URL da foto de forma robusta (funciona em localhost e VPS)
                                        $caminhoFoto = ltrim($foto->arquivo_path, '/');

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
                                            alt="<?= Html::encode($foto->arquivo_nome) ?>"
                                            class="w-full h-32 object-cover rounded-lg"
                                            onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'200\'%3E%3Crect fill=\'%23e5e7eb\' width=\'300\' height=\'200\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%239ca3af\' font-family=\'sans-serif\' font-size=\'14\'%3EErro ao carregar imagem%3C/text%3E%3C/svg%3E';">

                                        <?php if ($foto->eh_principal): ?>
                                            <span class="absolute top-2 left-2 px-2 py-1 bg-blue-600 text-white text-xs font-semibold rounded">
                                                Principal
                                            </span>
                                        <?php endif; ?>

                                        <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-lg flex items-center justify-center gap-2">
                                            <?php if (!$foto->eh_principal): ?>
                                                <?= Html::beginForm(['set-foto-principal', 'id' => $foto->id, 'redirect' => 'view'], 'post', ['class' => 'inline']) ?>
                                                <?= Html::submitButton('Principal', [
                                                    'class' => 'px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded pointer-events-auto cursor-pointer'
                                                ]) ?>
                                                <?= Html::endForm() ?>
                                            <?php endif; ?>
                                            <?= Html::beginForm(['delete-foto', 'id' => $foto->id, 'redirect' => 'view'], 'post', [
                                                'class' => 'inline',
                                                'onsubmit' => "return confirm('" . ($foto->eh_principal ? 'Esta é a foto principal. Ao excluir, outra foto será definida como principal automaticamente. Deseja continuar?' : 'Tem certeza que deseja excluir esta foto?') . "')"
                                            ]) ?>
                                            <?= Html::submitButton('Excluir', [
                                                'class' => 'px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded pointer-events-auto cursor-pointer'
                                            ]) ?>
                                            <?= Html::endForm() ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<!-- Modal Gerador de Card Social -->
<div id="modalCardSocial" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-70 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden transform transition-all border border-gray-100">
        <!-- Header Modal -->
        <div class="bg-gradient-to-r from-purple-800 via-indigo-800 to-purple-900 px-6 py-5 text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-600/40 rounded-xl border border-purple-400/30">
                    <svg class="w-6 h-6 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h3 class="text-xl font-extrabold tracking-tight">Estúdio de Cards Profissionais</h3>
                    <p class="text-xs text-purple-200 font-medium">Personalize modelo, cores e fundo do seu card de alta conversão</p>
                </div>
            </div>
            <button onclick="fecharModalCardSocial()" class="text-purple-200 hover:text-white hover:bg-white/10 p-2 rounded-xl transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Body Modal -->
        <div class="p-6 space-y-6 max-h-[80vh] overflow-y-auto">
            
            <!-- Card de Cota de Armazenamento de Cards -->
            <?php $cardStats = \app\modules\vendas\services\MediaStorageService::getEstatisticasCards($model->usuario_id); ?>
            <div id="container-cota-cards" class="bg-slate-900 text-white rounded-2xl p-4 border border-slate-700 mb-4">
                <div class="flex justify-between items-center mb-2 flex-wrap gap-2">
                    <span class="text-xs font-bold text-gray-200 flex items-center gap-2">
                        <span>🖼️ Armazenamento de Cards da Loja:</span>
                        <span id="card-lbl-uso-mb" class="text-purple-400 font-extrabold"><?= $cardStats['usado_mb'] ?> MB</span> / <span id="card-lbl-limite-mb" class="text-gray-400"><?= $cardStats['limite_mb'] ?> MB</span>
                    </span>
                    <span id="card-lbl-percentual" class="text-[10px] font-bold px-2 py-0.5 rounded-full text-white <?= $cardStats['excedido'] ? 'bg-red-500' : ($cardStats['percentual'] > 80 ? 'bg-amber-500' : 'bg-emerald-500') ?>">
                        <?= $cardStats['percentual'] ?>% utilizado
                    </span>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden mb-1">
                    <div id="card-bar-cota-progresso" class="h-full transition-all duration-300 <?= $cardStats['excedido'] ? 'bg-red-500' : ($cardStats['percentual'] > 80 ? 'bg-amber-500' : 'bg-purple-500') ?>" style="width: <?= $cardStats['percentual'] ?>%"></div>
                </div>
                <div id="card-alerta-excedido" class="mt-2 text-xs text-red-300 bg-red-900/40 p-2.5 rounded-xl border border-red-500/30 <?= $cardStats['excedido'] ? '' : 'hidden' ?>">
                    ⚠️ <strong>Limite de Armazenamento Excedido (<?= $cardStats['limite_mb'] ?> MB)!</strong> Para gerar novos cards, apague cards antigos para liberar espaço em disco.
                </div>
            </div>

            <!-- Grid / Galeria de Cards Criados Anteriormente para este Produto -->
            <?php 
            $cardsList = $cardsHistorico ?? \app\modules\vendas\models\ProdutoCard::find()->where(['produto_id' => $model->id, 'usuario_id' => $model->usuario_id])->orderBy(['data_criacao' => SORT_DESC])->all();
            ?>
            <div id="secao-historico-cards" class="bg-gray-50 border border-gray-200 rounded-2xl p-4 mb-4">
                <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                    <div class="flex items-center gap-3">
                        <h4 class="font-extrabold text-sm text-gray-900 flex items-center gap-2">
                            <span>🖼️ Galeria de Cards Gerados</span>
                            <span id="badge-total-cards" class="px-2 py-0.5 bg-purple-100 text-purple-700 text-xs font-bold rounded-full"><?= count($cardsList) ?></span>
                        </h4>
                        <?php if (!empty($cardsList)): ?>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs text-gray-600 font-bold bg-white px-2.5 py-1 rounded-lg border border-gray-200 hover:bg-gray-100 transition shadow-sm">
                                <input type="checkbox" id="chk-selecionar-todos-cards" onchange="toggleSelecionarTodosCards(this.checked)" class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                <span>Selecionar Todos</span>
                            </label>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center gap-2">
                        <span id="lbl-cards-selecionados" class="text-xs font-bold text-gray-500 hidden">0 selecionados</span>
                        <button id="btn-excluir-selecionados-cards" onclick="excluirCardsSelecionados()" type="button" class="hidden py-1 px-3 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-lg transition flex items-center gap-1 shadow">
                            🗑️ Excluir Selecionados (<span id="count-selecionados">0</span>)
                        </button>
                    </div>
                </div>

                <div id="grid-cards-historico" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    <?php if (empty($cardsList)): ?>
                        <div id="msg-sem-cards" class="col-span-full py-6 text-center text-xs text-gray-400 font-medium bg-white rounded-xl border border-dashed border-gray-200">
                            Nenhum card gerado ainda para este produto. Escolha as opções abaixo e clique em "Gerar Card Agora"!
                        </div>
                    <?php else: ?>
                        <?php foreach ($cardsList as $c): ?>
                            <?php 
                            $urlCard = $c->getUrlCompleta();
                            $fmtLabel = $c->formato === 'stories' ? 'Stories 9:16' : 'Feed 1:1';
                            $fmtBadge = $c->formato === 'stories' ? 'bg-indigo-600' : 'bg-purple-600';
                            $tamanho = $c->getTamanhoFormatado();
                            ?>
                            <div id="card-item-<?= $c->id ?>" class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm flex flex-col justify-between transition-all hover:shadow-md relative group">
                                <div class="relative bg-gray-950 aspect-square flex items-center justify-center overflow-hidden">
                                    <input type="checkbox" value="<?= $c->id ?>" onchange="atualizarSelecaoCards()" class="chk-card-item absolute top-2 left-2 z-10 w-4.5 h-4.5 text-purple-600 rounded border-gray-300 focus:ring-purple-500 cursor-pointer shadow-md">
                                    <img src="<?= Html::encode($urlCard) ?>" alt="Card <?= Html::encode($fmtLabel) ?>" class="max-h-full max-w-full object-contain">
                                    <span class="absolute top-2 left-8 text-[9px] font-bold text-white px-2 py-0.5 rounded shadow <?= $fmtBadge ?>">
                                        <?= $fmtLabel ?>
                                    </span>
                                    <span class="absolute top-2 right-2 text-[9px] font-bold text-gray-200 bg-black/70 px-1.5 py-0.5 rounded backdrop-blur">
                                        <?= $tamanho ?>
                                    </span>
                                </div>
                                <div class="p-2.5 bg-white border-t border-gray-100 flex items-center justify-between gap-2">
                                    <div class="text-[10px] text-gray-500 font-medium">
                                        <?= date('d/m/Y H:i', strtotime($c->data_criacao)) ?>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <a href="<?= Html::encode($urlCard) ?>" download class="p-1.5 text-xs text-purple-600 hover:bg-purple-50 rounded-lg font-bold transition" title="Baixar Imagem PNG">
                                            📥
                                        </a>
                                        <button onclick="excluirCardHistorico('<?= $c->id ?>')" type="button" class="p-1.5 text-xs text-red-600 hover:bg-red-50 rounded-lg font-bold transition" title="Excluir Card e Liberar Espaço">
                                            🗑️
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Formulário de Personalização -->
            <div id="secaoSelecaoFormato" class="space-y-6">
                
                <!-- 0. Escolha da Foto do Produto -->
                <?php if (!empty($model->fotos)): ?>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                            Escolha a Foto para o Card (<?= count($model->fotos) ?> disponível(is))
                        </label>
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-3">
                            <?php foreach ($model->fotos as $idx => $foto): ?>
                                <?php
                                $caminhoFoto = ltrim($foto->arquivo_path, '/');
                                $urlFoto = Url::to('@web/' . $caminhoFoto, true);
                                $isPrincipal = $foto->eh_principal || ($idx === 0);
                                ?>
                                <label class="cursor-pointer relative group">
                                    <input type="radio" name="card_foto_id" value="<?= $foto->id ?>" <?= $isPrincipal ? 'checked' : '' ?> class="peer sr-only">
                                    <div class="aspect-square rounded-xl border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:ring-4 peer-checked:ring-purple-200 overflow-hidden bg-gray-100 transition relative">
                                        <img src="<?= $urlFoto ?>" class="w-full h-full object-cover" alt="Foto <?= $idx + 1 ?>">
                                        <?php if ($isPrincipal): ?>
                                            <span class="absolute top-1 left-1 bg-purple-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow">Principal</span>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 1. Formato da Publicação -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">1. Formato da Publicação</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="card_formato" value="feed" checked class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-50 rounded-xl flex items-center gap-3 transition">
                                <div class="w-10 h-10 border-2 border-purple-600 rounded-lg flex items-center justify-center bg-white font-bold text-xs text-purple-700">1:1</div>
                                <div>
                                    <div class="font-bold text-sm text-gray-900">Feed / Post</div>
                                    <div class="text-xs text-gray-500">1080 x 1080 px</div>
                                </div>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="card_formato" value="stories" class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-50 rounded-xl flex items-center gap-3 transition">
                                <div class="w-8 h-10 border-2 border-purple-600 rounded-lg flex items-center justify-center bg-white font-bold text-xs text-purple-700">9:16</div>
                                <div>
                                    <div class="font-bold text-sm text-gray-900">Stories / Reels</div>
                                    <div class="text-xs text-gray-500">1080 x 1920 px</div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 2. Modelo de Layout (Templates) -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">2. Modelo de Layout</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="card_template" value="modern_dark" checked class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-50/60 rounded-xl transition flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-900 border border-slate-700 flex items-center justify-center text-blue-400 font-bold text-xs">💎</div>
                                <div>
                                    <div class="font-bold text-sm text-gray-900">Modern Dark</div>
                                    <div class="text-xs text-gray-500">Glassmorphism elegante escuro</div>
                                </div>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="card_template" value="vibrant_gradient" class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-50/60 rounded-xl transition flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xs">🌈</div>
                                <div>
                                    <div class="font-bold text-sm text-gray-900">Vibrant Gradient</div>
                                    <div class="text-xs text-gray-500">Colorido e alto contraste</div>
                                </div>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="card_template" value="minimalist_light" class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-50/60 rounded-xl transition flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gray-100 border border-gray-300 flex items-center justify-center text-gray-800 font-bold text-xs">✨</div>
                                <div>
                                    <div class="font-bold text-sm text-gray-900">Minimalist Light</div>
                                    <div class="text-xs text-gray-500">Limpo, claro e sofisticado</div>
                                </div>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="card_template" value="neon_promo" class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-50/60 rounded-xl transition flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-black border border-green-400 flex items-center justify-center text-green-400 font-bold text-xs">⚡</div>
                                <div>
                                    <div class="font-bold text-sm text-gray-900">Neon Promo</div>
                                    <div class="text-xs text-gray-500">Futurista / Destaque Ofertas</div>
                                </div>
                            </div>
                        </label>

                        <label class="cursor-pointer sm:col-span-2">
                            <input type="radio" name="card_template" value="full_bleed_banner" class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-50/60 rounded-xl transition flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-700 border border-emerald-500 flex items-center justify-center text-white font-bold text-xs">🖼️</div>
                                <div>
                                    <div class="font-bold text-sm text-gray-900">Foto em Tela Cheia (Banners Topo/Rodapé)</div>
                                    <div class="text-xs text-gray-500">Imagem em 100% da tela com faixas de destaque superior e inferior</div>
                                </div>
                            </div>
                        </label>

                        <label class="cursor-pointer sm:col-span-2">
                            <input type="radio" name="card_template" value="bold_banner" class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-50/60 rounded-xl transition flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-indigo-900 border border-indigo-500 flex items-center justify-center text-yellow-300 font-bold text-xs">📣</div>
                                <div>
                                    <div class="font-bold text-sm text-gray-900">Bold Banner</div>
                                    <div class="text-xs text-gray-500">Faixa dupla de grande impacto visual</div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 3. Paleta de Cores do Tema -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">3. Paleta de Cores</label>
                    <div class="flex flex-wrap gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="card_cor" value="dark" checked class="peer sr-only">
                            <div class="px-3 py-2 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-100 rounded-xl flex items-center gap-2 text-xs font-bold text-gray-800">
                                <span class="w-4 h-4 rounded-full bg-slate-900 inline-block border border-slate-700"></span> Dark Slate
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="card_cor" value="ocean" class="peer sr-only">
                            <div class="px-3 py-2 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-100 rounded-xl flex items-center gap-2 text-xs font-bold text-gray-800">
                                <span class="w-4 h-4 rounded-full bg-sky-600 inline-block"></span> Ocean Blue
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="card_cor" value="emerald" class="peer sr-only">
                            <div class="px-3 py-2 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-100 rounded-xl flex items-center gap-2 text-xs font-bold text-gray-800">
                                <span class="w-4 h-4 rounded-full bg-emerald-600 inline-block"></span> Emerald Green
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="card_cor" value="purple" class="peer sr-only">
                            <div class="px-3 py-2 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-100 rounded-xl flex items-center gap-2 text-xs font-bold text-gray-800">
                                <span class="w-4 h-4 rounded-full bg-purple-600 inline-block"></span> Purple Sunset
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="card_cor" value="sunset" class="peer sr-only">
                            <div class="px-3 py-2 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-100 rounded-xl flex items-center gap-2 text-xs font-bold text-gray-800">
                                <span class="w-4 h-4 rounded-full bg-orange-600 inline-block"></span> Sunset Orange
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="card_cor" value="rose" class="peer sr-only">
                            <div class="px-3 py-2 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-100 rounded-xl flex items-center gap-2 text-xs font-bold text-gray-800">
                                <span class="w-4 h-4 rounded-full bg-rose-600 inline-block"></span> Rose Pink
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="card_cor" value="gold" class="peer sr-only">
                            <div class="px-3 py-2 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-100 rounded-xl flex items-center gap-2 text-xs font-bold text-gray-800">
                                <span class="w-4 h-4 rounded-full bg-amber-500 inline-block"></span> Premium Gold
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 4. Estilo de Fundo -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">4. Estilo de Fundo</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="card_fundo" value="gradient" checked class="peer sr-only">
                            <div class="p-2 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-50 rounded-lg text-center text-xs font-bold text-gray-800">Gradiente</div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="card_fundo" value="mesh" class="peer sr-only">
                            <div class="p-2 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-50 rounded-lg text-center text-xs font-bold text-gray-800">Mesh Fluid</div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="card_fundo" value="geometric" class="peer sr-only">
                            <div class="p-2 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-50 rounded-lg text-center text-xs font-bold text-gray-800">Geométrico</div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="card_fundo" value="dots" class="peer sr-only">
                            <div class="p-2 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-50 rounded-lg text-center text-xs font-bold text-gray-800">Grid Pontos</div>
                        </label>
                    </div>
                </div>

                <!-- Botão Disparar Geração -->
                <button onclick="dispararGeracaoCard()" class="w-full py-4 px-6 bg-gradient-to-r from-purple-700 to-indigo-700 hover:from-purple-800 hover:to-indigo-800 text-white font-extrabold rounded-2xl transition duration-300 shadow-xl flex items-center justify-center gap-3 text-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Gerar Card Agora
                </button>

            </div>

            <!-- Loader State -->
            <div id="secaoLoadingCard" class="hidden py-12 flex flex-col items-center justify-center gap-4 text-center">
                <div class="relative w-16 h-16">
                    <div class="w-16 h-16 border-4 border-purple-200 border-t-purple-700 rounded-full animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-xl">✨</div>
                </div>
                <div>
                    <h4 class="font-extrabold text-gray-900 text-xl">Gerando Card com Puppeteer Headless...</h4>
                    <p class="text-sm text-gray-500 mt-1">Renderizando imagem em alta densidade (deviceScaleFactor: 2). Aguarde...</p>
                </div>
            </div>

            <!-- Resultado / Preview State -->
            <div id="secaoResultadoCard" class="hidden space-y-4">
                <div class="flex items-center justify-between bg-green-50 border border-green-200 p-3 rounded-xl">
                    <span class="text-sm font-bold text-green-700 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Card Renderizado com Sucesso!
                    </span>
                    <button onclick="voltarSelecaoFormato()" class="text-xs text-purple-700 hover:underline font-bold">Personalizar Novamente</button>
                </div>

                <div class="bg-gray-950 rounded-2xl p-3 flex items-center justify-center max-h-[500px] overflow-hidden shadow-inner border border-gray-800">
                    <img id="imgPreviewCard" src="" alt="Preview do Card" class="max-h-[460px] object-contain rounded-lg shadow-2xl">
                </div>

                <div class="flex gap-3 pt-2">
                    <a id="btnBaixarCard" href="#" download class="flex-1 text-center py-4 px-4 bg-purple-700 hover:bg-purple-800 text-white font-extrabold rounded-xl transition shadow-lg flex items-center justify-center gap-2 text-base">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Baixar PNG Alta Qualidade
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete() {
        if (confirm('Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.')) {
            document.getElementById('delete-form').submit();
        }
        return false;
    }

    function abrirModalCardSocial() {
        document.getElementById('modalCardSocial').classList.remove('hidden');
        voltarSelecaoFormato();
    }

    function fecharModalCardSocial() {
        document.getElementById('modalCardSocial').classList.add('hidden');
    }

    function voltarSelecaoFormato() {
        document.getElementById('secaoSelecaoFormato').classList.remove('hidden');
        document.getElementById('secaoLoadingCard').classList.add('hidden');
        document.getElementById('secaoResultadoCard').classList.add('hidden');
    }

    function dispararGeracaoCard() {
        const formato = document.querySelector('input[name="card_formato"]:checked')?.value || 'feed';
        const template = document.querySelector('input[name="card_template"]:checked')?.value || 'modern_dark';
        const corTema = document.querySelector('input[name="card_cor"]:checked')?.value || 'dark';
        const fundoEstilo = document.querySelector('input[name="card_fundo"]:checked')?.value || 'gradient';
        const fotoId = document.querySelector('input[name="card_foto_id"]:checked')?.value || '';

        document.getElementById('secaoSelecaoFormato').classList.add('hidden');
        document.getElementById('secaoLoadingCard').classList.remove('hidden');
        document.getElementById('secaoResultadoCard').classList.add('hidden');

        const produtoId = '<?= $model->id ?>';
        const urlAction = '<?= Url::to(['gerar-card']) ?>?id=' + produtoId;

        const formData = new FormData();
        formData.append('formato', formato);
        formData.append('template', template);
        formData.append('cor_tema', corTema);
        formData.append('fundo_estilo', fundoEstilo);
        if (fotoId) {
            formData.append('foto_id', fotoId);
        }
        formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

        fetch(urlAction, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('secaoLoadingCard').classList.add('hidden');
            if (data.success) {
                document.getElementById('secaoResultadoCard').classList.remove('hidden');
                document.getElementById('imgPreviewCard').src = data.card_url;
                const btnBaixar = document.getElementById('btnBaixarCard');
                btnBaixar.href = data.card_url;
                btnBaixar.download = 'card_' + produtoId + '_' + template + '_' + formato + '.png';
                if (data.stats) {
                    atualizarBarraCotaCards(data.stats);
                }
                if (data.card_id) {
                    adicionarCardAoGridHistorico(data.card_id, data.card_url, data.formato);
                }
            } else {
                alert('Erro ao gerar card: ' + (data.message || 'Falha na requisição.'));
                voltarSelecaoFormato();
            }
        })
        .catch(err => {
            console.error(err);
            document.getElementById('secaoLoadingCard').classList.add('hidden');
            alert('Erro de comunicação com o servidor: ' + err.message);
            voltarSelecaoFormato();
        });
    }

    function toggleSelecionarTodosCards(checked) {
        document.querySelectorAll('.chk-card-item').forEach(chk => {
            chk.checked = checked;
        });
        atualizarSelecaoCards();
    }

    function atualizarSelecaoCards() {
        const checkboxes = document.querySelectorAll('.chk-card-item');
        const marcados = document.querySelectorAll('.chk-card-item:checked');
        const total = checkboxes.length;
        const qtdMarcados = marcados.length;

        const chkTodos = document.getElementById('chk-selecionar-todos-cards');
        if (chkTodos) {
            chkTodos.checked = total > 0 && qtdMarcados === total;
        }

        const lblSelecionados = document.getElementById('lbl-cards-selecionados');
        const btnExcluir = document.getElementById('btn-excluir-selecionados-cards');
        const countSpan = document.getElementById('count-selecionados');

        if (qtdMarcados > 0) {
            if (lblSelecionados) {
                lblSelecionados.innerText = qtdMarcados + (qtdMarcados === 1 ? ' selecionado' : ' selecionados');
                lblSelecionados.classList.remove('hidden');
            }
            if (btnExcluir) {
                btnExcluir.classList.remove('hidden');
            }
            if (countSpan) {
                countSpan.innerText = qtdMarcados;
            }
        } else {
            if (lblSelecionados) lblSelecionados.classList.add('hidden');
            if (btnExcluir) btnExcluir.classList.add('hidden');
        }
    }

    function excluirCardsSelecionados() {
        const marcados = Array.from(document.querySelectorAll('.chk-card-item:checked')).map(c => c.value);
        if (marcados.length === 0) {
            alert('Nenhum card foi selecionado.');
            return;
        }

        const texto = marcados.length === 1 
            ? 'Deseja realmente excluir o card selecionado?' 
            : 'Deseja realmente excluir os ' + marcados.length + ' cards selecionados? As imagens PNG serão removidas permanentemente do servidor.';

        if (!confirm(texto)) {
            return;
        }

        const urlAction = '<?= Url::to(['delete-cards-batch']) ?>';
        const formData = new FormData();
        marcados.forEach(id => formData.append('ids[]', id));
        formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

        fetch(urlAction, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                marcados.forEach(cardId => {
                    const elem = document.getElementById('card-item-' + cardId);
                    if (elem) {
                        elem.style.transition = 'all 0.3s ease';
                        elem.style.opacity = '0';
                        elem.style.transform = 'scale(0.8)';
                        setTimeout(() => elem.remove(), 300);
                    }
                });

                const badgeTotal = document.getElementById('badge-total-cards');
                if (badgeTotal) {
                    const atual = Math.max(0, parseInt(badgeTotal.innerText || '0') - (data.deletados_count || marcados.length));
                    badgeTotal.innerText = atual;
                }

                setTimeout(() => {
                    atualizarSelecaoCards();
                }, 350);

                if (data.stats) {
                    atualizarBarraCotaCards(data.stats);
                }
            } else {
                alert('Erro ao excluir cards: ' + (data.message || 'Erro desconhecido.'));
            }
        })
        .catch(err => alert('Erro de conexão ao excluir cards em lote: ' + err.message));
    }

    function excluirCardHistorico(cardId) {
        if (!confirm('Deseja realmente excluir este card? A imagem PNG será removida permanentemente do servidor para liberar cota de armazenamento.')) {
            return;
        }

        const urlAction = '<?= Url::to(['delete-card']) ?>?id=' + encodeURIComponent(cardId);
        fetch(urlAction, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: '<?= Yii::$app->request->csrfParam ?>=<?= Yii::$app->request->csrfToken ?>'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const elem = document.getElementById('card-item-' + cardId);
                if (elem) {
                    elem.style.transition = 'all 0.3s ease';
                    elem.style.opacity = '0';
                    elem.style.transform = 'scale(0.8)';
                    setTimeout(() => elem.remove(), 300);
                }
                const badgeTotal = document.getElementById('badge-total-cards');
                if (badgeTotal) {
                    const atual = Math.max(0, parseInt(badgeTotal.innerText || '0') - 1);
                    badgeTotal.innerText = atual;
                }
                setTimeout(() => {
                    atualizarSelecaoCards();
                }, 350);
                if (data.stats) {
                    atualizarBarraCotaCards(data.stats);
                }
            } else {
                alert('Erro ao excluir card: ' + (data.message || 'Erro desconhecido.'));
            }
        })
        .catch(err => alert('Erro de conexão ao excluir card: ' + err.message));
    }

    function adicionarCardAoGridHistorico(cardId, cardUrl, formato) {
        const grid = document.getElementById('grid-cards-historico');
        if (!grid) return;

        const msgSemCards = document.getElementById('msg-sem-cards');
        if (msgSemCards) msgSemCards.remove();

        const fmtLabel = formato === 'stories' ? 'Stories 9:16' : 'Feed 1:1';
        const fmtBadge = formato === 'stories' ? 'bg-indigo-600' : 'bg-purple-600';
        const dataAtual = new Date().toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });

        const div = document.createElement('div');
        div.id = 'card-item-' + cardId;
        div.className = 'bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm flex flex-col justify-between transition-all hover:shadow-md relative group';
        div.innerHTML = `
            <div class="relative bg-gray-950 aspect-square flex items-center justify-center overflow-hidden">
                <input type="checkbox" value="${cardId}" onchange="atualizarSelecaoCards()" class="chk-card-item absolute top-2 left-2 z-10 w-4.5 h-4.5 text-purple-600 rounded border-gray-300 focus:ring-purple-500 cursor-pointer shadow-md">
                <img src="${cardUrl}" alt="Card ${fmtLabel}" class="max-h-full max-w-full object-contain">
                <span class="absolute top-2 left-8 text-[9px] font-bold text-white px-2 py-0.5 rounded shadow ${fmtBadge}">
                    ${fmtLabel}
                </span>
                <span class="absolute top-2 right-2 text-[9px] font-bold text-gray-200 bg-black/70 px-1.5 py-0.5 rounded backdrop-blur">
                    Novo
                </span>
            </div>
            <div class="p-2.5 bg-white border-t border-gray-100 flex items-center justify-between gap-2">
                <div class="text-[10px] text-gray-500 font-medium">${dataAtual}</div>
                <div class="flex items-center gap-1">
                    <a href="${cardUrl}" download class="p-1.5 text-xs text-purple-600 hover:bg-purple-50 rounded-lg font-bold transition" title="Baixar Imagem PNG">
                        📥
                    </a>
                    <button onclick="excluirCardHistorico('${cardId}')" type="button" class="p-1.5 text-xs text-red-600 hover:bg-red-50 rounded-lg font-bold transition" title="Excluir Card e Liberar Espaço">
                        🗑️
                    </button>
                </div>
            </div>
        `;

        grid.insertBefore(div, grid.firstChild);

        const badgeTotal = document.getElementById('badge-total-cards');
        if (badgeTotal) {
            badgeTotal.innerText = parseInt(badgeTotal.innerText || '0') + 1;
        }

        atualizarSelecaoCards();
    }

    function atualizarBarraCotaCards(stats) {
        if (!stats) return;
        const usoElem = document.getElementById('card-lbl-uso-mb');
        const limiteElem = document.getElementById('card-lbl-limite-mb');
        const percElem = document.getElementById('card-lbl-percentual');
        const progressBar = document.getElementById('card-bar-cota-progresso');
        const alertaElem = document.getElementById('card-alerta-excedido');
        const btnDisparar = document.querySelector('button[onclick="dispararGeracaoCard()"]');

        if (usoElem) usoElem.innerText = stats.usado_mb + ' MB';
        if (limiteElem) limiteElem.innerText = stats.limite_mb + ' MB';
        if (percElem) {
            percElem.innerText = stats.percentual + '% utilizado';
            percElem.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full text-white ' + (stats.excedido ? 'bg-red-500' : (stats.percentual > 80 ? 'bg-amber-500' : 'bg-emerald-500'));
        }
        if (progressBar) {
            progressBar.style.width = stats.percentual + '%';
            progressBar.className = 'h-full transition-all duration-300 ' + (stats.excedido ? 'bg-red-500' : (stats.percentual > 80 ? 'bg-amber-500' : 'bg-purple-500'));
        }
        if (alertaElem) {
            if (stats.excedido) alertaElem.classList.remove('hidden');
            else alertaElem.classList.add('hidden');
        }
        if (btnDisparar) {
            btnDisparar.disabled = stats.excedido;
            btnDisparar.style.opacity = stats.excedido ? '0.5' : '1';
        }
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
</script>