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

                <!-- Vídeos do Produto -->
                <?php
                // Garante o carregamento dos vídeos ativos do produto
                $videosProduto = $model->videos ?: [];
                if (empty($videosProduto) && !$model->isRelationPopulated('videos')) {
                    $videosProduto = $model->getVideos()->all();
                }
                ?>

                <div class="bg-white rounded-lg shadow-md overflow-hidden mt-6">
                    <div class="px-6 py-4 bg-gray-50 border-b flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900">Vídeos do Produto (<?= count($videosProduto) ?>)</h3>
                        </div>
                        <?php if (count($videosProduto) < 2): ?>
                            <?= Html::a(
                                '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Adicionar Vídeo',
                                ['update', 'id' => $model->id, '#' => 'secao-videos'],
                                ['class' => 'inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-lg transition shadow-sm']
                            ) ?>
                        <?php endif; ?>
                    </div>

                    <div class="p-6">
                        <?php if (!empty($videosProduto)): ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <?php foreach ($videosProduto as $idx => $video): ?>
                                    <?php
                                        $meta = is_array($video->metadata) ? $video->metadata : json_decode($video->metadata ?? '', true);
                                        $tamanhoBytes = $meta['tamanho_bytes'] ?? null;
                                        $tamanhoFormatted = $tamanhoBytes ? number_format($tamanhoBytes / (1024 * 1024), 2, ',', '.') . ' MB' : null;
                                        $origem = $meta['origem'] ?? 'upload_manual';
                                        $urlVideo = $video->getUrl();
                                    ?>
                                    <div class="bg-gray-900 rounded-xl overflow-hidden p-3 border border-gray-800 shadow-md flex flex-col justify-between">
                                        <video src="<?= Html::encode($urlVideo) ?>" controls preload="metadata" class="w-full aspect-video rounded-lg bg-black object-cover mb-3"></video>
                                        
                                        <div class="space-y-2 pt-2 border-t border-gray-800">
                                            <div class="flex items-center justify-between text-xs text-gray-300">
                                                <span class="bg-purple-600/30 text-purple-300 px-2 py-0.5 rounded border border-purple-500/30 font-bold text-[11px]">
                                                    🎥 Vídeo <?= $idx + 1 ?>
                                                </span>
                                                <?php if ($tamanhoFormatted): ?>
                                                    <span class="text-gray-400 font-medium text-[11px]"><?= $tamanhoFormatted ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="flex items-center justify-between text-[11px] text-gray-400">
                                                <span>Origem: <strong class="text-gray-300 font-semibold"><?= $origem === 'studio_916' ? 'Estúdio 9:16' : 'Upload Manual' ?></strong></span>
                                                <span>Status: <strong class="text-emerald-400 font-semibold">Concluído</strong></span>
                                            </div>

                                            <div class="flex items-center gap-2 pt-1">
                                                <a href="<?= Html::encode($urlVideo) ?>" target="_blank" class="flex-1 text-center px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-200 text-xs font-semibold rounded-lg transition">
                                                    🔗 Nova Aba
                                                </a>
                                                <a href="<?= Html::encode($urlVideo) ?>" download class="flex-1 text-center px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-lg transition">
                                                    ⬇️ Baixar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="p-8 text-center bg-purple-50/40 rounded-xl border border-dashed border-purple-200 flex flex-col items-center justify-center">
                                <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center mb-3">
                                    <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h4 class="text-base font-bold text-gray-800 mb-1">Nenhum vídeo cadastrado para este produto</h4>
                                <p class="text-xs text-gray-500 max-w-sm mb-4">Adicione até 2 vídeos demonstrativos de até 5MB cada para aumentar o engajamento e as vendas do produto.</p>
                                <div class="flex flex-wrap items-center justify-center gap-3">
                                    <?= Html::a(
                                        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Fazer Upload de Vídeos',
                                        ['update', 'id' => $model->id, '#' => 'secao-videos'],
                                        ['class' => 'inline-flex items-center gap-1.5 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-lg transition shadow-md']
                                    ) ?>
                                    <?= Html::a(
                                        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg> Estúdio de Vídeo 9:16',
                                        ['/vendas/produto-video/studio', 'produto_id' => $model->id],
                                        ['class' => 'inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition shadow-md']
                                    ) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

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
                        <span>🖼️ Armazenamento Total da Loja (Todos os Produtos):</span>
                        <span id="card-lbl-uso-mb" class="text-purple-400 font-extrabold"><?= $cardStats['usado_mb'] ?> MB</span> / <span id="card-lbl-limite-mb" class="text-gray-400"><?= $cardStats['limite_mb'] ?> MB</span>
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="otimizarCardsLegadosAjax()" class="text-[10px] font-bold px-2.5 py-1 rounded-lg bg-purple-600 hover:bg-purple-700 text-white transition flex items-center gap-1 shadow cursor-pointer" title="Otimizar cards PNG antigos e liberar espaço em disco">
                            🧹 Otimizar Cards Antigos
                        </button>
                        <span id="card-lbl-percentual" class="text-[10px] font-bold px-2 py-0.5 rounded-full text-white <?= $cardStats['excedido'] ? 'bg-red-500' : ($cardStats['percentual'] > 80 ? 'bg-amber-500' : 'bg-emerald-500') ?>">
                            <?= $cardStats['percentual'] ?>% utilizado
                        </span>
                    </div>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden mb-1">
                    <div id="card-bar-cota-progresso" class="h-full transition-all duration-300 <?= $cardStats['excedido'] ? 'bg-red-500' : ($cardStats['percentual'] > 80 ? 'bg-amber-500' : 'bg-purple-500') ?>" style="width: <?= $cardStats['percentual'] ?>%"></div>
                </div>
                <div id="card-alerta-excedido" class="mt-2 text-xs text-red-300 bg-red-900/40 p-2.5 rounded-xl border border-red-500/30 <?= $cardStats['excedido'] ? '' : 'hidden' ?>">
                    ⚠️ <strong>Limite de Armazenamento Excedido (<?= $cardStats['limite_mb'] ?> MB)!</strong> Para gerar novos cards, apague cards antigos para liberar espaço em disco.
                </div>
            </div>

            <!-- Grid / Galeria de Cards Criados Anteriormente -->
            <?php 
            $cardsList = $cardsHistorico ?? \app\modules\vendas\models\ProdutoCard::find()->where(['produto_id' => $model->id, 'usuario_id' => $model->usuario_id])->orderBy(['data_criacao' => SORT_DESC])->all();
            ?>
            <div id="secao-historico-cards" class="bg-gray-50 border border-gray-200 rounded-2xl p-4 mb-4">
                <!-- Abas de Filtro de Cards -->
                <div class="flex items-center gap-2 border-b border-gray-200 pb-2.5 mb-3 flex-wrap">
                    <button type="button" id="tab-cards-produto" onclick="carregarCardsGaleria('produto')" class="px-3 py-1.5 text-xs font-extrabold rounded-lg bg-purple-600 text-white transition shadow-sm flex items-center gap-1.5 cursor-pointer">
                        <span>📌 Cards deste Produto</span>
                        <span id="badge-total-cards" class="px-2 py-0.5 bg-white/20 text-white text-[10px] font-bold rounded-full"><?= count($cardsList) ?></span>
                    </button>
                    <button type="button" id="tab-cards-loja" onclick="carregarCardsGaleria('todos')" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition flex items-center gap-1.5 cursor-pointer">
                        <span>🌐 Todos os Cards da Loja</span>
                    </button>
                </div>

                <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                    <div class="flex items-center gap-3">
                        <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs text-gray-600 font-bold bg-white px-2.5 py-1 rounded-lg border border-gray-200 hover:bg-gray-100 transition shadow-sm">
                            <input type="checkbox" id="chk-selecionar-todos-cards" onchange="toggleSelecionarTodosCards(this.checked)" class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                            <span>Selecionar Todos</span>
                        </label>
                    </div>

                    <div class="flex items-center gap-2">
                        <span id="lbl-cards-selecionados" class="text-xs font-bold text-gray-500 hidden">0 selecionados</span>
                        <button id="btn-disparar-selecionados-cards" onclick="abrirDisparoCardsExistentesSelecionados()" type="button" class="hidden py-1 px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg transition flex items-center gap-1 shadow">
                            📱 Enviar Selecionados (<span id="count-disparo">0</span>)
                        </button>
                        <button id="btn-excluir-selecionados-cards" onclick="excluirCardsSelecionados()" type="button" class="hidden py-1 px-3 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-lg transition flex items-center gap-1 shadow">
                            🗑️ Excluir (<span id="count-selecionados">0</span>)
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
                                <div onclick="toggleCardSelection('<?= $c->id ?>', event)" class="relative bg-gray-950 aspect-square flex items-center justify-center overflow-hidden cursor-pointer">
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
                                        <button onclick="abrirDisparoCardUnico('<?= $c->id ?>', '<?= Html::encode($urlCard) ?>')" type="button" class="p-1.5 text-xs text-emerald-600 hover:bg-emerald-50 rounded-lg font-bold transition" title="Enviar este Card via WhatsApp">
                                            📱
                                        </button>
                                        <a href="<?= Html::encode($urlCard) ?>" download class="p-1.5 text-xs text-purple-600 hover:bg-purple-50 rounded-lg font-bold transition" title="Baixar Imagem do Card">
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
                        Baixar PNG
                    </a>
                    <button id="btnDispararCardGerado" onclick="abrirDisparoCardGeradoAtual()" type="button" class="flex-1 text-center py-4 px-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-extrabold rounded-xl transition shadow-lg flex items-center justify-center gap-2 text-base">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Enviar via WhatsApp
                    </button>
                </div>
            </div>

            <!-- Seção de Envio do Card para WhatsApp via Evolution API (com Anti-Ban) -->
            <div id="secaoDisparoWhatsappCard" class="hidden space-y-5">
                <div class="flex items-center justify-between bg-purple-50 border border-purple-200 p-3.5 rounded-2xl">
                    <span class="text-sm font-extrabold text-purple-900 flex items-center gap-2">
                        <span>📱 Disparo de Cards via Evolution API</span>
                    </span>
                    <button onclick="voltarSelecaoFormato()" class="text-xs text-purple-700 hover:underline font-bold">← Voltar para Personalização</button>
                </div>

                <!-- Banner Status Conexão WhatsApp -->
                <div id="bannerStatusWhatsappCard" class="bg-gray-50 border border-gray-200 p-3.5 rounded-2xl flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-3">
                        <span id="indicadorDotWhatsappCard" class="w-3.5 h-3.5 rounded-full bg-gray-400 animate-pulse inline-block"></span>
                        <div>
                            <div class="text-xs font-bold text-gray-800" id="textoStatusWhatsappCard">Verificando Evolution API...</div>
                            <div class="text-[11px] text-gray-500" id="subtextoStatusWhatsappCard">Consultando status da instância da sua loja.</div>
                        </div>
                    </div>
                    <a href="<?= Url::to(['/evolution/default/index']) ?>" target="_blank" id="btnConectarWhatsappCard" class="hidden text-xs font-bold px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-xl transition shadow-sm">
                        Conectar WhatsApp
                    </a>
                </div>

                <!-- Preview dos Cards Selecionados -->
                <div class="bg-slate-900 border border-slate-800 text-white p-3.5 rounded-2xl flex items-center gap-4">
                    <div id="containerThumbnailsDisparo" class="flex gap-2 overflow-x-auto max-w-[180px] p-1">
                        <!-- Thumbnails injetadas via JS -->
                    </div>
                    <div class="flex-1">
                        <div class="text-xs font-bold text-purple-300 uppercase tracking-wider">Lote Selecionado</div>
                        <div class="text-sm font-extrabold text-white" id="lblCardsDisparoResumo">1 card selecionado para envio</div>
                        <div class="text-[11px] text-gray-400">Produto: <strong><?= Html::encode($model->nome) ?></strong> (R$ <?= number_format($model->preco_venda_sugerido, 2, ',', '.') ?>)</div>
                        <div class="text-[11px] text-emerald-400 mt-1" id="lblEstimativaEnvioCard">📊 Resumo do Lote: 1 card selecionado.</div>
                    </div>
                </div>

                <!-- Canais de Envio -->
                <div class="border-b border-gray-100 pb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">1. Selecione os Canais de Envio</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="checkbox" id="canal_card_whatsapp" checked onchange="calcularResumoEnvioCard()" class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-emerald-600 peer-checked:bg-emerald-50 rounded-xl transition flex flex-col gap-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-sm text-gray-900">💬 WhatsApp Direto (Conversas)</span>
                                    <span class="w-4 h-4 rounded-full border border-emerald-600 flex items-center justify-center text-emerald-700 peer-checked:bg-emerald-600 peer-checked:text-white text-xs">✓</span>
                                </div>
                                <p class="text-xs text-gray-500">Envia o card + legenda com variáveis dinâmicas para os contatos.</p>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="checkbox" id="canal_card_status" checked onchange="calcularResumoEnvioCard()" class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-emerald-600 peer-checked:bg-emerald-50 rounded-xl transition flex flex-col gap-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-sm text-gray-900">📲 Status WhatsApp (Stories)</span>
                                    <span class="w-4 h-4 rounded-full border border-emerald-600 flex items-center justify-center text-emerald-700 peer-checked:bg-emerald-600 peer-checked:text-white text-xs">✓</span>
                                </div>
                                <p class="text-xs text-gray-500">Posta a imagem do card no Status da sua conta WhatsApp (100% orgânico).</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Destinatários -->
                <div class="border-b border-gray-100 pb-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">2. Escolha os Destinatários</label>
                        <button type="button" onclick="alternarTodosClientesCard()" class="text-xs text-purple-700 hover:underline font-bold" id="btnToggleTodosClientesCard">Marcar Todos</button>
                    </div>

                    <input type="text" id="buscaClienteCardInput" onkeyup="filtrarClientesNaTelaCard(this.value)" placeholder="Buscar cliente por nome ou telefone..." class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-purple-600">

                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-2.5 max-h-36 overflow-y-auto space-y-1.5" id="listaClientesCardContainer">
                        <div class="text-xs text-gray-500 text-center py-3">Carregando lista de clientes...</div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">💬 Números de WhatsApp Adicionais (Manuais)</label>
                        <textarea id="telefones_manuais_card" onkeyup="calcularResumoEnvioCard()" rows="2" class="w-full p-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-purple-600" placeholder="Cole telefones separados por vírgula, espaço ou linha (ex: 81999998888, 81988887777)"></textarea>
                    </div>
                </div>

                <!-- Mensagem Promocional & SpinTax -->
                <div class="border-b border-gray-100 pb-4 space-y-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">3. Legenda da Mensagem Promocional</label>
                    <textarea id="mensagem_texto_card" rows="3" class="w-full p-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-purple-600" placeholder="Digite a legenda da promoção...">🔥 {OFERTA IMPERDÍVEL|PROMOÇÃO EXCLUSIVA|DESCONTO ESPECIAL} 🔥

{Olá|Oi|Tudo bem} {NOME}! Confira este produto incrível:
* {PRODUTO} por apenas {PRECO}!

Garanta o seu antes que acabe o estoque!</textarea>
                    <div class="flex items-center justify-between flex-wrap gap-1 text-[10px] text-gray-500">
                        <span>Variáveis: <code class="bg-gray-100 text-purple-800 px-1 rounded">{NOME}</code>, <code class="bg-gray-100 text-purple-800 px-1 rounded">{PRODUTO}</code>, <code class="bg-gray-100 text-purple-800 px-1 rounded">{PRECO}</code>, <code class="bg-gray-100 text-purple-800 px-1 rounded">{MARCA}</code></span>
                        <span class="text-emerald-700 font-bold">✨ SpinTax ativado: <code class="bg-emerald-50 text-emerald-800 px-1 rounded">{Opção 1|Opção 2}</code></span>
                    </div>
                </div>

                <!-- Painel de Proteção Anti-Banimento (Configurações Avançadas) -->
                <div class="bg-slate-900 text-white rounded-2xl p-4 border border-slate-800 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-base">🛡️</span>
                            <h4 class="font-extrabold text-xs tracking-wide uppercase text-emerald-400">Proteção Avançada Anti-Banimento de Chip</h4>
                        </div>
                        <span class="text-[10px] bg-emerald-950 text-emerald-400 border border-emerald-800 px-2 py-0.5 rounded-full font-bold">Segurança Ativa</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block font-bold text-gray-300 mb-1">⏱️ Intervalo Aleatório (Random Delay)</label>
                            <select id="antiban_delay" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-medium focus:ring-2 focus:ring-emerald-500">
                                <option value="5" selected>5 a 10 segundos (Recomendado)</option>
                                <option value="10">10 a 20 segundos (Ultra Seguro)</option>
                                <option value="15">15 a 30 segundos (Lento e Discreto)</option>
                                <option value="2">2 a 5 segundos (Rápido - Usar com Cuidado)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-300 mb-1">☕ Lotes e Micro-Pausas de Descanso</label>
                            <select id="antiban_lote" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-medium focus:ring-2 focus:ring-emerald-500">
                                <option value="10_60" selected>Pausar 60s a cada 10 disparos</option>
                                <option value="15_120">Pausar 120s a cada 15 disparos</option>
                                <option value="20_180">Pausar 180s a cada 20 disparos</option>
                                <option value="0_0">Sem micro-pausa por lote</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="antiban_optout" checked class="w-4 h-4 rounded text-emerald-500 focus:ring-emerald-400 bg-slate-800 border-slate-700">
                            <span class="text-xs text-gray-300 font-medium">Incluir aviso de descadastro (<code class="text-emerald-400 font-mono">PARAR</code>) no final da mensagem</span>
                        </label>
                    </div>

                    <div class="bg-slate-800/80 p-3 rounded-xl border border-slate-700/60 text-[11px] text-gray-300 space-y-1">
                        <p class="font-bold text-amber-300 flex items-center gap-1">
                            <span>💡 Boas Práticas Recomendadas pela Meta/WhatsApp:</span>
                        </p>
                        <ul class="list-disc list-inside space-y-0.5 text-gray-400 pl-1">
                            <li>Dispare prioritariamente para clientes cadastrados que têm o seu número salvo no celular.</li>
                            <li>Use variação SpinTax na mensagem para que nenhuma mensagem seja idêntica à outra.</li>
                            <li>Evite disparar mais de 100 mensagens por hora no mesmo número.</li>
                        </ul>
                    </div>
                </div>

                <!-- Botão de Disparo -->
                <button onclick="iniciarDisparoCardWhatsappExec()" class="w-full py-4 px-6 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-extrabold rounded-2xl transition duration-300 shadow-xl flex items-center justify-center gap-3 text-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Iniciar Envio dos Cards via WhatsApp
                </button>
            </div>

            <!-- Progresso ao Vivo do Disparo de Cards -->
            <div id="secaoProgressoDisparoCard" class="hidden py-6 space-y-5 text-center">
                <div class="relative w-16 h-16 mx-auto">
                    <div class="w-16 h-16 border-4 border-emerald-200 border-t-emerald-600 rounded-full animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-xl" id="iconeStatusDisparoCard">🚀</div>
                </div>

                <div>
                    <h4 class="font-extrabold text-gray-900 text-xl" id="tituloStatusDisparoCard">Disparando Cards via Evolution API...</h4>
                    <p class="text-sm text-gray-500 mt-1" id="subtituloStatusDisparoCard">Aplicando delays anti-ban e enviando mensagens nas filas.</p>
                </div>

                <!-- Barra de Progresso -->
                <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                    <div id="barraProgressoDisparoCard" class="bg-gradient-to-r from-emerald-600 to-teal-600 h-4 transition-all duration-500 rounded-full" style="width: 0%"></div>
                </div>

                <div class="grid grid-cols-3 gap-4 bg-gray-50 p-4 rounded-2xl border border-gray-200">
                    <div>
                        <div class="text-xs text-gray-500 font-bold uppercase">Total Agendado</div>
                        <div class="text-xl font-extrabold text-gray-800" id="statTotalItensCard">0</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 font-bold uppercase">Enviados</div>
                        <div class="text-xl font-extrabold text-green-600" id="statItensEnviadosCard">0</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 font-bold uppercase">Falhas / Erros</div>
                        <div class="text-xl font-extrabold text-red-600" id="statItensErroCard">0</div>
                    </div>
                </div>

                <!-- Relatório Detalhado de Envios (Sucessos e Falhas) -->
                <div id="containerErrosDisparoCard" class="hidden text-left bg-slate-900 border border-slate-800 rounded-2xl p-4 space-y-3">
                    <div class="flex items-center justify-between flex-wrap gap-2 pb-2 border-b border-slate-800">
                        <h5 class="text-xs font-extrabold text-white uppercase tracking-wider flex items-center gap-1.5">
                            <span>📊 Relatório Detalhado de Envios</span>
                        </h5>
                        <div class="flex items-center gap-1 text-[11px] font-bold">
                            <button onclick="filtrarRelatorioModal('todos')" id="btnFiltroRelatorioTodos" type="button" class="px-2.5 py-1 rounded-lg bg-slate-700 text-white transition hover:bg-slate-600">
                                Todos (<span id="cntRelatorioTodos">0</span>)
                            </button>
                            <button onclick="filtrarRelatorioModal('enviado')" id="btnFiltroRelatorioSucesso" type="button" class="px-2.5 py-1 rounded-lg bg-emerald-950 text-emerald-400 border border-emerald-800/60 transition hover:bg-emerald-900/50">
                                🟢 Sucessos (<span id="cntRelatorioSucesso">0</span>)
                            </button>
                            <button onclick="filtrarRelatorioModal('erro')" id="btnFiltroRelatorioErro" type="button" class="px-2.5 py-1 rounded-lg bg-red-950 text-red-400 border border-red-800/60 transition hover:bg-red-900/50">
                                🔴 Falhas (<span id="cntRelatorioErro">0</span>)
                            </button>
                        </div>
                    </div>

                    <div id="listaErrosDisparoCard" class="text-xs space-y-1.5 max-h-60 overflow-y-auto pr-1"></div>

                    <button id="btnReenviarErrosCard" onclick="reenviarErrosDisparoCard()" class="hidden w-full py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition text-xs shadow flex items-center justify-center gap-2 mt-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Reenviar Apenas Itens com Falha
                    </button>
                </div>

                <div class="pt-4 flex gap-3">
                    <button id="btnFecharDisparoCardConcluido" onclick="voltarSelecaoFormato()" class="hidden w-full py-3 bg-purple-700 hover:bg-purple-800 text-white font-bold rounded-xl transition">
                        Concluir e Voltar ao Estúdio
                    </button>
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
        const secaoWp = document.getElementById('secaoDisparoWhatsappCard');
        if (secaoWp) secaoWp.classList.add('hidden');
        const secaoProg = document.getElementById('secaoProgressoDisparoCard');
        if (secaoProg) secaoProg.classList.add('hidden');
        if (typeof intervalMonitoramentoCard !== 'undefined' && intervalMonitoramentoCard) {
            clearInterval(intervalMonitoramentoCard);
            intervalMonitoramentoCard = null;
        }
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
                cardRecemGeradoId = data.card_id;
                cardRecemGeradoUrl = data.card_url;
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

    function toggleCardSelection(cardId, event) {
        if (event && (event.target.type === 'checkbox' || event.target.tagName === 'BUTTON' || event.target.tagName === 'A')) {
            return;
        }
        const chk = document.querySelector(`#card-item-${cardId} .chk-card-item`);
        if (chk) {
            chk.checked = !chk.checked;
            atualizarSelecaoCards();
        }
    }

    function atualizarSelecaoCards() {
        const checkboxes = document.querySelectorAll('.chk-card-item');
        const marcados = document.querySelectorAll('.chk-card-item:checked');
        const total = checkboxes.length;
        const qtdMarcados = marcados.length;

        // Destaque visual nos cards selecionados no grid
        checkboxes.forEach(chk => {
            const itemDiv = document.getElementById('card-item-' + chk.value);
            if (itemDiv) {
                if (chk.checked) {
                    itemDiv.classList.add('ring-2', 'ring-emerald-500', 'border-emerald-500', 'bg-emerald-50/20');
                    itemDiv.classList.remove('border-gray-200');
                } else {
                    itemDiv.classList.remove('ring-2', 'ring-emerald-500', 'border-emerald-500', 'bg-emerald-50/20');
                    itemDiv.classList.add('border-gray-200');
                }
            }
        });

        const chkTodos = document.getElementById('chk-selecionar-todos-cards');
        if (chkTodos) {
            chkTodos.checked = total > 0 && qtdMarcados === total;
        }

        const lblSelecionados = document.getElementById('lbl-cards-selecionados');
        const btnExcluir = document.getElementById('btn-excluir-selecionados-cards');
        const btnDisparar = document.getElementById('btn-disparar-selecionados-cards');
        const countSpan = document.getElementById('count-selecionados');
        const countDisparoSpan = document.getElementById('count-disparo');

        if (qtdMarcados > 0) {
            if (lblSelecionados) {
                lblSelecionados.innerText = qtdMarcados + (qtdMarcados === 1 ? ' card selecionado' : ' cards selecionados');
                lblSelecionados.classList.remove('hidden');
            }
            if (btnExcluir) {
                btnExcluir.classList.remove('hidden');
            }
            if (btnDisparar) {
                btnDisparar.classList.remove('hidden');
            }
            if (countSpan) {
                countSpan.innerText = qtdMarcados;
            }
            if (countDisparoSpan) {
                countDisparoSpan.innerText = qtdMarcados;
            }
        } else {
            if (lblSelecionados) lblSelecionados.classList.add('hidden');
            if (btnExcluir) btnExcluir.classList.add('hidden');
            if (btnDisparar) btnDisparar.classList.add('hidden');
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

    let filtroCardsAtual = 'produto';

    function carregarCardsGaleria(filtro) {
        filtroCardsAtual = filtro;
        const btnProd = document.getElementById('tab-cards-produto');
        const btnLoja = document.getElementById('tab-cards-loja');
        const grid = document.getElementById('grid-cards-historico');

        if (filtro === 'todos') {
            if (btnProd) btnProd.className = 'px-3 py-1.5 text-xs font-bold rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition flex items-center gap-1.5 cursor-pointer';
            if (btnLoja) btnLoja.className = 'px-3 py-1.5 text-xs font-extrabold rounded-lg bg-purple-600 text-white transition shadow-sm flex items-center gap-1.5 cursor-pointer';

            if (grid) grid.innerHTML = '<div class="col-span-full py-8 text-center text-xs text-purple-600 font-bold">Carregando todos os cards da loja...</div>';

            const urlList = '<?= Url::to(['listar-cards-loja']) ?>?produto_id=<?= $model->id ?>&filtro=todos';
            fetch(urlList)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.items) {
                        renderizarCardsGrid(data.items);
                    } else {
                        if (grid) grid.innerHTML = '<div class="col-span-full py-6 text-center text-xs text-red-500 font-bold">Erro ao carregar cards da loja.</div>';
                    }
                })
                .catch(err => {
                    if (grid) grid.innerHTML = '<div class="col-span-full py-6 text-center text-xs text-red-500 font-bold">Erro de conexão ao carregar cards da loja.</div>';
                });
        } else {
            if (btnProd) btnProd.className = 'px-3 py-1.5 text-xs font-extrabold rounded-lg bg-purple-600 text-white transition shadow-sm flex items-center gap-1.5 cursor-pointer';
            if (btnLoja) btnLoja.className = 'px-3 py-1.5 text-xs font-bold rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition flex items-center gap-1.5 cursor-pointer';

            if (grid) grid.innerHTML = '<div class="col-span-full py-8 text-center text-xs text-purple-600 font-bold">Carregando cards deste produto...</div>';

            const urlList = '<?= Url::to(['listar-cards-loja']) ?>?produto_id=<?= $model->id ?>&filtro=produto';
            fetch(urlList)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.items) {
                        renderizarCardsGrid(data.items);
                        const badgeTotal = document.getElementById('badge-total-cards');
                        if (badgeTotal) badgeTotal.innerText = data.items.length;
                    } else {
                        if (grid) grid.innerHTML = '<div class="col-span-full py-6 text-center text-xs text-red-500 font-bold">Erro ao carregar cards do produto.</div>';
                    }
                })
                .catch(err => {
                    if (grid) grid.innerHTML = '<div class="col-span-full py-6 text-center text-xs text-red-500 font-bold">Erro de conexão ao carregar cards.</div>';
                });
        }
    }

    function renderizarCardsGrid(items) {
        const grid = document.getElementById('grid-cards-historico');
        if (!grid) return;

        if (!items || items.length === 0) {
            grid.innerHTML = `
                <div id="msg-sem-cards" class="col-span-full py-6 text-center text-xs text-gray-400 font-medium bg-white rounded-xl border border-dashed border-gray-200">
                    Nenhum card encontrado nesta visualização.
                </div>
            `;
            return;
        }

        let html = '';
        items.forEach(c => {
            const fmtBadge = c.formato === 'stories' ? 'bg-indigo-600' : 'bg-purple-600';
            const prodTag = c.produto_nome ? `<div class="text-[9px] font-bold text-gray-600 truncate mt-1 bg-gray-100 px-1.5 py-0.5 rounded">📦 ${escapeHtml(c.produto_nome)}</div>` : '';

            html += `
                <div id="card-item-${c.id}" class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm flex flex-col justify-between transition-all hover:shadow-md relative group">
                    <div onclick="toggleCardSelection('${c.id}', event)" class="relative bg-gray-950 aspect-square flex items-center justify-center overflow-hidden cursor-pointer">
                        <input type="checkbox" value="${c.id}" onchange="atualizarSelecaoCards()" class="chk-card-item absolute top-2 left-2 z-10 w-4.5 h-4.5 text-purple-600 rounded border-gray-300 focus:ring-purple-500 cursor-pointer shadow-md">
                        <img src="${escapeHtml(c.url)}" alt="Card ${escapeHtml(c.formato_label)}" class="max-h-full max-w-full object-contain">
                        <span class="absolute top-2 left-8 text-[9px] font-bold text-white px-2 py-0.5 rounded shadow ${fmtBadge}">
                            ${escapeHtml(c.formato_label)}
                        </span>
                        <span class="absolute top-2 right-2 text-[9px] font-bold text-gray-200 bg-black/70 px-1.5 py-0.5 rounded backdrop-blur">
                            ${escapeHtml(c.tamanho)}
                        </span>
                    </div>
                    <div class="p-2.5 bg-white border-t border-gray-100 flex flex-col gap-1">
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-[10px] text-gray-500 font-medium">${c.data_criacao}</div>
                            <div class="flex items-center gap-1">
                                <button onclick="abrirDisparoCardUnico('${c.id}', '${escapeHtml(c.url)}')" type="button" class="p-1.5 text-xs text-emerald-600 hover:bg-emerald-50 rounded-lg font-bold transition" title="Enviar este Card via WhatsApp">
                                    📱
                                </button>
                                <a href="${escapeHtml(c.url)}" download class="p-1.5 text-xs text-purple-600 hover:bg-purple-50 rounded-lg font-bold transition" title="Baixar Imagem do Card">
                                    📥
                                </a>
                                <button onclick="excluirCardHistorico('${c.id}')" type="button" class="p-1.5 text-xs text-red-600 hover:bg-red-50 rounded-lg font-bold transition" title="Excluir Card e Liberar Espaço">
                                    🗑️
                                </button>
                            </div>
                        </div>
                        ${prodTag}
                    </div>
                </div>
            `;
        });

        grid.innerHTML = html;
        atualizarSelecaoCards();
    }

    function otimizarCardsLegadosAjax() {
        if (!confirm('Deseja otimizar os cards antigos da loja? Isso reduzirá o consumo de armazenamento convertendo PNGs pesados em WebP ultra leves e limpando arquivos antigos soltos.')) {
            return;
        }

        const urlOpt = '<?= Url::to(['otimizar-cards-legados']) ?>';
        fetch(urlOpt, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: '<?= Yii::$app->request->csrfParam ?>=<?= Yii::$app->request->csrfToken ?>'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Otimização concluída com sucesso! Espaço liberado em disco: ' + (data.resultado ? data.resultado.espaco_liberado_mb : 0) + ' MB.');
                if (data.stats) {
                    atualizarBarraCotaCards(data.stats);
                }
                carregarCardsGaleria(filtroCardsAtual);
            } else {
                alert('Erro na otimização: ' + (data.message || 'Erro desconhecido.'));
            }
        })
        .catch(err => alert('Erro de conexão ao otimizar cards: ' + err.message));
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
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
            <div onclick="toggleCardSelection('${cardId}', event)" class="relative bg-gray-950 aspect-square flex items-center justify-center overflow-hidden cursor-pointer">
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
                    <button onclick="abrirDisparoCardUnico('${cardId}', '${cardUrl}')" type="button" class="p-1.5 text-xs text-emerald-600 hover:bg-emerald-50 rounded-lg font-bold transition" title="Enviar este Card via WhatsApp">
                        📱
                    </button>
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

    // =========================================================================
    // LÓGICA DE DISPARO DE CARDS VIA WHATSAPP (EVOLUTION API + ANTI-BAN)
    // =========================================================================
    let cardsSelecionadosParaDisparo = [];
    let cardRecemGeradoId = null;
    let cardRecemGeradoUrl = null;
    let listaClientesCardCache = [];
    let whatsappCardConectadoCache = false;
    let intervalMonitoramentoCard = null;
    let ultimoDisparoCardIdAtivo = null;

    function abrirDisparoCardGeradoAtual() {
        if (!cardRecemGeradoId) {
            alert('Aguarde a geração do card...');
            return;
        }
        abrirDisparoComCards([cardRecemGeradoId], [cardRecemGeradoUrl]);
    }

    function abrirDisparoCardUnico(cardId, cardUrl) {
        abrirDisparoComCards([cardId], [cardUrl]);
    }

    function abrirDisparoCardsExistentesSelecionados() {
        const marcadosCheckboxes = document.querySelectorAll('.chk-card-item:checked');
        const ids = Array.from(marcadosCheckboxes).map(c => c.value);
        const urls = Array.from(marcadosCheckboxes).map(c => {
            const img = c.closest('.group')?.querySelector('img');
            return img ? img.src : '';
        });

        if (ids.length === 0) {
            alert('Nenhum card foi selecionado.');
            return;
        }

        abrirDisparoComCards(ids, urls);
    }

    function calcularResumoEnvioCard() {
        const totalCards = cardsSelecionadosParaDisparo.length || 1;
        const qtdClientes = document.querySelectorAll('input[name="cliente_card_chk"]:checked').length;
        const telefonesManuais = document.getElementById('telefones_manuais_card')?.value || '';
        const qtdManuais = (telefonesManuais.match(/\d{10,13}/g) || []).length;
        const totalDestinatarios = qtdClientes + qtdManuais;
        
        const canalWp = document.getElementById('canal_card_whatsapp')?.checked ? 1 : 0;
        const canalStatus = document.getElementById('canal_card_status')?.checked ? 1 : 0;

        const enviosTotais = (totalCards * totalDestinatarios * canalWp) + (totalCards * canalStatus);
        
        const elemEstimativa = document.getElementById('lblEstimativaEnvioCard');
        if (elemEstimativa) {
            elemEstimativa.innerHTML = `📊 <strong>Resumo do Lote:</strong> ${totalCards} card(s) × ${totalDestinatarios} destinatários = <span class="text-emerald-400 font-extrabold font-mono text-xs">${enviosTotais} envio(s)</span> agendados via Evolution API.`;
        }
    }

    function abrirDisparoComCards(cardIds = [], cardUrls = []) {
        cardsSelecionadosParaDisparo = cardIds;

        document.getElementById('secaoSelecaoFormato').classList.add('hidden');
        document.getElementById('secaoResultadoCard').classList.add('hidden');
        document.getElementById('secaoLoadingCard').classList.add('hidden');
        document.getElementById('secaoProgressoDisparoCard').classList.add('hidden');
        document.getElementById('containerErrosDisparoCard').classList.add('hidden');
        document.getElementById('btnFecharDisparoCardConcluido').classList.add('hidden');
        document.getElementById('secaoDisparoWhatsappCard').classList.remove('hidden');

        const lblResumo = document.getElementById('lblCardsDisparoResumo');
        if (lblResumo) {
            lblResumo.innerText = cardIds.length === 1 ? '1 card selecionado para envio' : cardIds.length + ' cards selecionados para envio';
        }

        const containerThumbs = document.getElementById('containerThumbnailsDisparo');
        if (containerThumbs) {
            containerThumbs.innerHTML = cardUrls.map((url, i) => url ? `<img src="${url}" class="w-10 h-10 object-cover rounded-lg border border-purple-400/50 shadow">` : '').join('');
        }

        verificarStatusWhatsappCard();
        carregarListaClientesCard();
        setTimeout(calcularResumoEnvioCard, 200);
    }

    function verificarStatusWhatsappCard() {
        const dot = document.getElementById('indicadorDotWhatsappCard');
        const texto = document.getElementById('textoStatusWhatsappCard');
        const subtexto = document.getElementById('subtextoStatusWhatsappCard');
        const btnConectar = document.getElementById('btnConectarWhatsappCard');

        dot.className = 'w-3.5 h-3.5 rounded-full bg-gray-400 animate-pulse inline-block';
        texto.textContent = 'Verificando Evolution API...';
        subtexto.textContent = 'Consultando status da instância da loja.';
        btnConectar.classList.add('hidden');

        fetch('<?= Url::to(['/vendas/disparo/status-whatsapp']) ?>')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.connected) {
                whatsappCardConectadoCache = true;
                dot.className = 'w-3.5 h-3.5 rounded-full bg-green-500 inline-block shadow';
                texto.textContent = '🟢 WhatsApp Conectado via Evolution API';
                subtexto.textContent = 'Instância: ' + (data.instance_name || 'Ativa') + ' (Pronto para disparos no Status e Mensagens)';
            } else {
                whatsappCardConectadoCache = false;
                dot.className = 'w-3.5 h-3.5 rounded-full bg-red-500 inline-block shadow';
                texto.textContent = '🔴 WhatsApp Desconectado';
                subtexto.textContent = 'Conecte sua instância da Evolution API antes de disparar via WhatsApp.';
                btnConectar.classList.remove('hidden');
            }
        })
        .catch(err => {
            whatsappCardConectadoCache = false;
            dot.className = 'w-3.5 h-3.5 rounded-full bg-yellow-500 inline-block';
            texto.textContent = '⚠️ Falha ao verificar Evolution API';
            subtexto.textContent = 'Não foi possível consultar o status da conexão.';
        });
    }

    function carregarListaClientesCard() {
        const container = document.getElementById('listaClientesCardContainer');
        fetch('<?= Url::to(['/vendas/disparo/clientes']) ?>')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.clientes) {
                listaClientesCardCache = data.clientes;
                renderizarListaClientesCard(listaClientesCardCache);
            }
        })
        .catch(err => {
            container.innerHTML = '<div class="text-xs text-red-500 text-center py-3">Erro ao carregar clientes.</div>';
        });
    }

    function renderizarListaClientesCard(clientes) {
        const container = document.getElementById('listaClientesCardContainer');
        if (clientes.length === 0) {
            container.innerHTML = '<div class="text-xs text-gray-500 text-center py-3">Nenhum cliente cadastrado.</div>';
            return;
        }

        container.innerHTML = clientes.map(c => {
            const badgeWp = c.tem_whatsapp ? '<span class="px-1.5 py-0.5 bg-green-100 text-green-800 text-[10px] font-bold rounded">📱 WhatsApp</span>' : '';
            return `
                <label class="flex items-center justify-between p-2 hover:bg-white rounded-lg transition cursor-pointer border border-transparent hover:border-gray-200">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="cliente_card_chk" value="${c.id}" checked onchange="calcularResumoEnvioCard()" class="rounded text-emerald-600 focus:ring-emerald-500">
                        <div class="text-xs">
                            <span class="font-bold text-gray-800">${c.nome}</span>
                            <span class="text-gray-500 text-[11px]">(${c.celular || c.telefone || 'Sem tel'})</span>
                        </div>
                    </div>
                    <div>${badgeWp}</div>
                </label>
            `;
        }).join('');
        calcularResumoEnvioCard();
    }

    function filtrarClientesNaTelaCard(termo) {
        const termoLimpo = termo.toLowerCase().trim();
        if (!termoLimpo) {
            renderizarListaClientesCard(listaClientesCardCache);
            return;
        }
        const filtrados = listaClientesCardCache.filter(c => 
            (c.nome && c.nome.toLowerCase().includes(termoLimpo)) ||
            (c.celular && c.celular.includes(termoLimpo)) ||
            (c.telefone && c.telefone.includes(termoLimpo))
        );
        renderizarListaClientesCard(filtrados);
    }

    function alternarTodosClientesCard() {
        const chks = document.querySelectorAll('input[name="cliente_card_chk"]');
        const algumDesmarcado = Array.from(chks).some(c => !c.checked);
        chks.forEach(c => c.checked = algumDesmarcado);
        document.getElementById('btnToggleTodosClientesCard').textContent = algumDesmarcado ? 'Desmarcar Todos' : 'Marcar Todos';
        calcularResumoEnvioCard();
    }

    function iniciarDisparoCardWhatsappExec() {
        if (cardsSelecionadosParaDisparo.length === 0) {
            alert('Nenhum card foi selecionado para disparo.');
            return;
        }

        const canais = [];
        if (document.getElementById('canal_card_whatsapp').checked) canais.push('whatsapp');
        if (document.getElementById('canal_card_status').checked) canais.push('status');

        if (canais.length === 0) {
            alert('Selecione pelo menos um canal de envio.');
            return;
        }

        if (!whatsappCardConectadoCache) {
            if (!confirm('⚠️ Atenção: A instância do WhatsApp da sua loja na Evolution API parece estar DESCONECTADA. Deseja tentar o envio mesmo assim?')) {
                return;
            }
        }

        const clientesIds = Array.from(document.querySelectorAll('input[name="cliente_card_chk"]:checked')).map(c => c.value);
        const telefonesManuais = document.getElementById('telefones_manuais_card').value;
        const mensagemTexto = document.getElementById('mensagem_texto_card').value;

        // Anti-ban settings
        const delayVal = parseInt(document.getElementById('antiban_delay').value || '5');
        const loteVal = document.getElementById('antiban_lote').value || '10_60';
        const parts = loteVal.split('_');
        const loteTamanho = parseInt(parts[0] || '10');
        const pausaLote = parseInt(parts[1] || '60');
        const incluirOptout = document.getElementById('antiban_optout').checked;

        const payload = {
            cards_ids: cardsSelecionadosParaDisparo,
            canais: canais,
            clientes_ids: clientesIds,
            telefones_manuais: telefonesManuais,
            mensagem_texto: mensagemTexto,
            delay_segundos: delayVal,
            lote_tamanho: loteTamanho,
            pausa_lote_segundos: pausaLote,
            incluir_optout: incluirOptout,
            '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
        };

        document.getElementById('secaoDisparoWhatsappCard').classList.add('hidden');
        document.getElementById('secaoProgressoDisparoCard').classList.remove('hidden');

        fetch('<?= Url::to(['/vendas/disparo/criar']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(async r => {
            const text = await r.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error(text.replace(/<[^>]*>?/gm, '').trim().substring(0, 150) || 'Falha no servidor.');
            }
        })
        .then(data => {
            if (data.success && data.disparo_id) {
                monitorarProgressoCardDisparo(data.disparo_id);
            } else {
                alert('Erro ao criar disparo: ' + (data.message || 'Falha na requisição.'));
                document.getElementById('secaoDisparoWhatsappCard').classList.remove('hidden');
                document.getElementById('secaoProgressoDisparoCard').classList.add('hidden');
            }
        })
        .catch(err => {
            alert('Erro de comunicação: ' + err.message);
            document.getElementById('secaoDisparoWhatsappCard').classList.remove('hidden');
            document.getElementById('secaoProgressoDisparoCard').classList.add('hidden');
        });
    }

    let relatorioItensCache = [];
    let filtroRelatorioAtual = 'todos';

    function filtrarRelatorioModal(filtro) {
        filtroRelatorioAtual = filtro;
        renderizarRelatorioModal(relatorioItensCache, filtro);
    }

    function renderizarRelatorioModal(itens, filtro) {
        const listaErros = document.getElementById('listaErrosDisparoCard');
        if (!listaErros) return;

        // Atualizar estado visual dos botões de filtro
        const btnTodos = document.getElementById('btnFiltroRelatorioTodos');
        const btnSuc = document.getElementById('btnFiltroRelatorioSucesso');
        const btnErr = document.getElementById('btnFiltroRelatorioErro');

        if (btnTodos) btnTodos.className = 'px-2.5 py-1 rounded-lg transition ' + (filtro === 'todos' ? 'bg-slate-600 text-white font-black ring-2 ring-slate-400' : 'bg-slate-800 text-slate-300 hover:bg-slate-700');
        if (btnSuc) btnSuc.className = 'px-2.5 py-1 rounded-lg transition border ' + (filtro === 'enviado' ? 'bg-emerald-900 text-emerald-300 font-black border-emerald-500 ring-2 ring-emerald-500/50' : 'bg-emerald-950/60 text-emerald-400 border-emerald-800/60 hover:bg-emerald-900/50');
        if (btnErr) btnErr.className = 'px-2.5 py-1 rounded-lg transition border ' + (filtro === 'erro' ? 'bg-red-900 text-red-300 font-black border-red-500 ring-2 ring-red-500/50' : 'bg-red-950/60 text-red-400 border-red-800/60 hover:bg-red-900/50');

        let filtrados = itens;
        if (filtro === 'enviado') {
            filtrados = itens.filter(i => i.status === 'enviado');
        } else if (filtro === 'erro') {
            filtrados = itens.filter(i => i.status === 'erro');
        }

        if (filtrados.length === 0) {
            listaErros.innerHTML = '<div class="text-center py-3 text-slate-400 text-xs italic">Nenhum item neste filtro.</div>';
            return;
        }

        listaErros.innerHTML = filtrados.map(e => {
            const isOk = e.status === 'enviado';
            const bgClass = isOk ? 'bg-emerald-950/40 border-emerald-800/60 text-emerald-200' : 'bg-red-950/40 border-red-800/60 text-red-200';
            const badgeClass = isOk ? 'bg-emerald-800 text-emerald-100' : 'bg-red-800 text-red-100';
            const icon = isOk ? '🟢' : '🔴';
            const statusTxt = isOk 
                ? 'Enviado com sucesso via Evolution API' + (e.enviado_em ? ' (' + e.enviado_em.substring(11, 16) + 'h)' : '') 
                : (e.erro_mensagem || 'Falha ao enviar mensagem de mídia.');

            return `
                <div class="p-2 border rounded-xl flex items-center justify-between gap-2 shadow-sm transition ${bgClass}">
                    <div class="flex items-center gap-2 overflow-hidden">
                        <span class="text-xs flex-shrink-0">${icon}</span>
                        <span class="font-extrabold uppercase text-[10px] px-1.5 py-0.5 rounded ${badgeClass}">${e.canal || 'whatsapp'}</span>
                        <span class="font-bold font-mono text-white text-xs truncate">${e.destino || 'Geral'}</span>
                    </div>
                    <div class="text-[11px] font-medium text-right truncate max-w-[60%]">
                        ${statusTxt}
                    </div>
                </div>
            `;
        }).join('');
    }

    function monitorarProgressoCardDisparo(disparoId) {
        ultimoDisparoCardIdAtivo = disparoId;
        function checarStatus() {
            fetch('<?= Url::to(['/vendas/disparo/status']) ?>?id=' + disparoId)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('statTotalItensCard').textContent = data.total_itens;
                    document.getElementById('statItensEnviadosCard').textContent = data.itens_enviados;
                    document.getElementById('statItensErroCard').textContent = data.itens_erro;

                    const percent = data.progresso_percentual || 0;
                    document.getElementById('barraProgressoDisparoCard').style.width = percent + '%';

                    relatorioItensCache = data.itens || data.erros || [];
                    if (relatorioItensCache.length > 0) {
                        const containerErros = document.getElementById('containerErrosDisparoCard');
                        if (containerErros) containerErros.classList.remove('hidden');
                        
                        const cntSucesso = relatorioItensCache.filter(i => i.status === 'enviado').length;
                        const cntErro = relatorioItensCache.filter(i => i.status === 'erro').length;
                        
                        const elTodos = document.getElementById('cntRelatorioTodos');
                        const elSuc = document.getElementById('cntRelatorioSucesso');
                        const elErr = document.getElementById('cntRelatorioErro');

                        if (elTodos) elTodos.textContent = relatorioItensCache.length;
                        if (elSuc) elSuc.textContent = cntSucesso;
                        if (elErr) elErr.textContent = cntErro;

                        const btnReenviar = document.getElementById('btnReenviarErrosCard');
                        if (btnReenviar) {
                            if (cntErro > 0) btnReenviar.classList.remove('hidden');
                            else btnReenviar.classList.add('hidden');
                        }

                        renderizarRelatorioModal(relatorioItensCache, filtroRelatorioAtual);
                    }

                    if (data.status === 'concluido' || percent >= 100) {
                        if (intervalMonitoramentoCard) {
                            clearInterval(intervalMonitoramentoCard);
                            intervalMonitoramentoCard = null;
                        }
                        document.getElementById('iconeStatusDisparoCard').textContent = (data.itens_erro === 0) ? '🎉' : '⚠️';
                        document.getElementById('tituloStatusDisparoCard').textContent = (data.itens_erro === 0) ? 'Disparo de Cards Concluído!' : 'Disparo Finalizado com Avisos';
                        document.getElementById('subtituloStatusDisparoCard').textContent = 'Todos os cards foram processados pela Evolution API.';
                        document.getElementById('btnFecharDisparoCardConcluido').classList.remove('hidden');
                    }
                }
            });
        }

        checarStatus();
        intervalMonitoramentoCard = setInterval(checarStatus, 2500);
    }

    function reenviarErrosDisparoCard() {
        if (!ultimoDisparoCardIdAtivo) return;
        const btn = document.getElementById('btnReenviarErrosCard');
        btn.disabled = true;
        btn.innerHTML = '⌛ Reenviando falhas...';
        fetch('<?= Url::to(['/vendas/disparo/reenviar-erros']) ?>?id=' + ultimoDisparoCardIdAtivo, { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Reenviar Apenas Itens com Falha`;
            if (data.success) {
                document.getElementById('containerErrosDisparoCard').classList.add('hidden');
                monitorarProgressoCardDisparo(ultimoDisparoCardIdAtivo);
            } else {
                alert('Erro ao reenviar: ' + (data.message || 'Falha na requisição.'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Reenviar Apenas Itens com Falha`;
            alert('Erro de comunicação: ' + err.message);
        });
    }
</script>