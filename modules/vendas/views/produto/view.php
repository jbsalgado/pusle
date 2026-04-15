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
                                                <?php
                                                $setPrincipalUrl = Url::to(['set-foto-principal', 'id' => $foto->id, 'redirect' => 'view']);
                                                ?>
                                                <?= Html::a('Principal', $setPrincipalUrl, [
                                                    'class' => 'px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded',
                                                    'data-method' => 'post'
                                                ]) ?>
                                            <?php endif; ?>
                                            <?php
                                            $deleteUrl = Url::to(['delete-foto', 'id' => $foto->id, 'redirect' => 'view']);
                                            ?>
                                            <?= Html::a('Excluir', $deleteUrl, [
                                                'class' => 'px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded',
                                                'data' => [
                                                    'confirm' => $foto->eh_principal ? 'Esta é a foto principal. Ao excluir, outra foto será definida como principal automaticamente. Deseja continuar?' : 'Tem certeza que deseja excluir esta foto?',
                                                    'method' => 'post',
                                                ],
                                            ]) ?>
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

<script>
    function confirmDelete() {
        if (confirm('Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.')) {
            document.getElementById('delete-form').submit();
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
</script>