<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\LinkPager;

$this->title = $model->nome;
$this->params['breadcrumbs'][] = ['label' => 'Categorias', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        
        <!-- Header com ações -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
            <div class="flex flex-wrap gap-2">
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar',
                    ['update', 'id' => $model->id],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold rounded-lg transition duration-300']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>Excluir',
                    ['delete', 'id' => $model->id],
                    [
                        'class' => 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition duration-300',
                        'data' => [
                            'confirm' => 'Tem certeza que deseja excluir esta categoria?',
                            'method' => 'post',
                        ],
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
            
            <!-- Coluna Esquerda - Informações da Categoria -->
            <div class="lg:col-span-1 space-y-6">
                
                <!-- Card de Informações -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">Informações</h3>
                    </div>
                    <div class="p-6">
                        <?= DetailView::widget([
                            'model' => $model,
                            'options' => ['class' => 'w-full'],
                            'template' => '<div class="py-3 border-b last:border-b-0"><div class="text-sm font-medium text-gray-500 mb-1">{label}</div><div class="text-sm text-gray-900">{value}</div></div>',
                            'attributes' => [
                                'id',
                                'nome',
                                [
                                    'attribute' => 'descricao',
                                    'format' => 'ntext',
                                    'value' => $model->descricao ?: 'Sem descrição'
                                ],
                                'ordem',
                                [
                                    'attribute' => 'ativo',
                                    'format' => 'raw',
                                    'value' => $model->ativo 
                                        ? '<span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>Ativo</span>' 
                                        : '<span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded-full">Inativo</span>',
                                ],
                                'data_criacao:datetime',
                                'data_atualizacao:datetime',
                            ],
                        ]) ?>
                    </div>
                </div>

                <!-- Card de Estatísticas -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-md p-6 text-white">
                    <h3 class="text-lg font-semibold mb-4">Estatísticas</h3>
                    <div class="text-center">
                        <div class="text-6xl font-bold mb-2">
                            <?= $model->totalProdutos ?>
                        </div>
                        <p class="text-blue-100">
                            <?= $model->totalProdutos == 1 ? 'produto cadastrado' : 'produtos cadastrados' ?>
                        </p>
                    </div>
                </div>

            </div>

            <!-- Coluna Direita - Produtos da Categoria -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">Produtos desta Categoria</h3>
                        <?= Html::a(
                            '<svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Novo Produto',
                            ['/vendas/produto/create', 'categoria_id' => $model->id],
                            ['class' => 'inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded transition duration-300']
                        ) ?>
                    </div>
                    
                    <div class="p-6">
                        <?php if ($produtosProvider->totalCount > 0): ?>
                            
                            <div class="space-y-4">
                                <?php foreach ($produtosProvider->getModels() as $produto): ?>
                                    <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 hover:shadow-md transition-all duration-300">
                                        <div class="flex items-start gap-4">
                                            
                                            <!-- Foto do Produto -->
                                            <div class="flex-shrink-0">
                                                <?php if ($fotoPrincipal = $produto->fotoPrincipal): ?>
                                                    <img src="<?= Yii::getAlias('@web') . '/' . $fotoPrincipal->arquivo_path ?>" 
                                                         alt="<?= Html::encode($produto->nome) ?>"
                                                         class="w-20 h-20 object-cover rounded-lg">
                                                <?php else: ?>
                                                    <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center">
                                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                        </svg>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Informações do Produto -->
                                            <div class="flex-1 min-w-0">
                                                <h4 class="text-lg font-semibold text-gray-900 mb-1">
                                                    <?= Html::a(Html::encode($produto->nome), ['/vendas/produto/view', 'id' => $produto->id], ['class' => 'hover:text-blue-600']) ?>
                                                </h4>
                                                <p class="text-sm text-gray-500 mb-2">
                                                    <?= Html::encode($produto->codigo_referencia) ?>
                                                </p>
                                                
                                                <div class="flex flex-wrap gap-3 text-sm">
                                                    <div class="flex items-center text-green-600 font-semibold">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        R$ <?= Yii::$app->formatter->asDecimal($produto->preco_venda_sugerido, 2) ?>
                                                    </div>
                                                    
                                                    <div class="flex items-center <?= $produto->estoque_atual > 0 ? 'text-green-600' : 'text-red-600' ?> font-semibold">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                        </svg>
                                                        <?= $produto->estoque_atual ?> un
                                                    </div>
                                                    
                                                    <?php if (!$produto->ativo): ?>
                                                        <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded-full">
                                                            Inativo
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Paginação -->
                            <?php if ($produtosProvider->pagination->pageCount > 1): ?>
                                <div class="mt-6">
                                    <?= LinkPager::widget([
                                        'pagination' => $produtosProvider->pagination,
                                        'options' => ['class' => 'flex justify-center space-x-2'],
                                        'linkOptions' => ['class' => 'px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50'],
                                        'activePageCssClass' => 'bg-blue-600 text-white border-blue-600',
                                        'prevPageLabel' => '←',
                                        'nextPageLabel' => '→',
                                    ]) ?>
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            
                            <!-- Mensagem quando não há produtos -->
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="text-gray-500 text-lg mb-4">Nenhum produto cadastrado nesta categoria</p>
                                <?= Html::a(
                                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Cadastrar Produto',
                                    ['/vendas/produto/create', 'categoria_id' => $model->id],
                                    ['class' => 'inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300']
                                ) ?>
                            </div>

                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>