<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use app\modules\vendas\models\ComissaoConfig;

$this->title = 'Configurações de Comissões';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-4 px-3 sm:py-6 sm:px-4 lg:px-8">
    
    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-4 sm:mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
            <div class="flex flex-wrap gap-2">
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                    ['/vendas/inicio/index'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm w-full sm:w-auto justify-center']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Nova Configuração',
                    ['create'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm w-full sm:w-auto justify-center']
                ) ?>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">
        
        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-md mb-4 sm:mb-6 p-4 sm:p-6">
            <form method="get" class="space-y-4">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    
                    <!-- Colaborador -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Colaborador</label>
                        <select name="colaborador_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                            <option value="">Todos</option>
                            <?php foreach ($colaboradores as $colab): ?>
                                <option value="<?= $colab->id ?>" <?= Yii::$app->request->get('colaborador_id') == $colab->id ? 'selected' : '' ?>>
                                    <?= Html::encode($colab->nome_completo) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Categoria -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
                        <select name="categoria_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                            <option value="">Todas</option>
                            <option value="null" <?= Yii::$app->request->get('categoria_id') === 'null' ? 'selected' : '' ?>>Todas as Categorias (geral)</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat->id ?>" <?= Yii::$app->request->get('categoria_id') == $cat->id ? 'selected' : '' ?>>
                                    <?= Html::encode($cat->nome) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Tipo de Comissão -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                        <select name="tipo_comissao" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                            <option value="">Todos</option>
                            <option value="<?= ComissaoConfig::TIPO_VENDA ?>" <?= Yii::$app->request->get('tipo_comissao') == ComissaoConfig::TIPO_VENDA ? 'selected' : '' ?>>Venda</option>
                            <option value="<?= ComissaoConfig::TIPO_COBRANCA ?>" <?= Yii::$app->request->get('tipo_comissao') == ComissaoConfig::TIPO_COBRANCA ? 'selected' : '' ?>>Cobrança</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="ativo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                            <option value="">Todos</option>
                            <option value="1" <?= Yii::$app->request->get('ativo') === '1' ? 'selected' : '' ?>>Ativos</option>
                            <option value="0" <?= Yii::$app->request->get('ativo') === '0' ? 'selected' : '' ?>>Inativos</option>
                        </select>
                    </div>

                    <!-- Botões -->
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300 text-sm sm:text-base">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            Buscar
                        </button>
                    </div>

                </div>

                <?php if (Yii::$app->request->queryParams): ?>
                    <div class="flex gap-2">
                        <?= Html::a(
                            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Limpar Filtros',
                            ['index'],
                            ['class' => 'px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition duration-300 text-sm sm:text-base']
                        ) ?>
                    </div>
                <?php endif; ?>

            </form>
        </div>
        
        <!-- Contador -->
        <div class="mb-4 sm:mb-6">
            <span class="text-sm sm:text-base text-gray-600">
                <?= $dataProvider->getTotalCount() ?> configuração(ões) encontrada(s)
            </span>
        </div>

        <!-- Cards de Configurações -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
            <?php foreach ($dataProvider->getModels() as $model): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    
                    <!-- Header do Card -->
                    <div class="p-4 sm:p-5 border-b border-gray-200 bg-gradient-to-r <?= 
                        $model->tipo_comissao == ComissaoConfig::TIPO_VENDA ? 'from-blue-50 to-blue-100' : 'from-purple-50 to-purple-100'
                    ?>">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <h3 class="text-base sm:text-lg font-semibold text-gray-900 truncate mb-1">
                                    <?= Html::encode($model->colaborador->nome_completo ?? '-') ?>
                                </h3>
                                <p class="text-xs sm:text-sm text-gray-600 font-medium">
                                    <?= Html::encode($model->tipo_comissao == ComissaoConfig::TIPO_VENDA ? 'Comissão de Venda' : 'Comissão de Cobrança') ?>
                                </p>
                            </div>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full ml-2 flex-shrink-0 <?= 
                                $model->ativo && $model->isVigente() ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                            ?>">
                                <?= $model->ativo && $model->isVigente() ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </div>
                    </div>

                    <!-- Conteúdo -->
                    <div class="p-4 sm:p-5">
                        <div class="space-y-3 mb-4">
                            <!-- Percentual -->
                            <div class="bg-blue-50 rounded-lg p-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs sm:text-sm text-gray-600 font-medium">Percentual:</span>
                                    <span class="text-lg sm:text-xl font-bold text-blue-600">
                                        <?= Yii::$app->formatter->asDecimal($model->percentual, 2) ?>%
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Categoria -->
                            <div class="flex justify-between items-center">
                                <span class="text-xs sm:text-sm text-gray-600">Categoria:</span>
                                <span class="text-xs sm:text-sm font-medium text-gray-900">
                                    <?= $model->categoria ? Html::encode($model->categoria->nome) : '<span class="text-gray-500 italic">Todas</span>' ?>
                                </span>
                            </div>
                            
                            <!-- Vigência -->
                            <?php if ($model->data_inicio || $model->data_fim): ?>
                                <div class="pt-2 border-t border-gray-200">
                                    <div class="space-y-1">
                                        <?php if ($model->data_inicio): ?>
                                            <div class="flex justify-between items-center">
                                                <span class="text-xs text-gray-600">Início:</span>
                                                <span class="text-xs font-medium text-gray-700">
                                                    <?= Yii::$app->formatter->asDate($model->data_inicio) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($model->data_fim): ?>
                                            <div class="flex justify-between items-center">
                                                <span class="text-xs text-gray-600">Fim:</span>
                                                <span class="text-xs font-medium text-gray-700">
                                                    <?= Yii::$app->formatter->asDate($model->data_fim) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Observações -->
                            <?php if ($model->observacoes): ?>
                                <div class="pt-2 border-t border-gray-200">
                                    <p class="text-xs text-gray-600 line-clamp-2">
                                        <?= Html::encode($model->observacoes) ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Data Criação -->
                            <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                                <span class="text-xs text-gray-500">Criado em:</span>
                                <span class="text-xs text-gray-500">
                                    <?= Yii::$app->formatter->asDate($model->data_criacao) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Ações -->
                        <div class="flex flex-col sm:flex-row gap-2 pt-3 border-t border-gray-200">
                            <?= Html::a('Ver', ['view', 'id' => $model->id], 
                                ['class' => 'flex-1 text-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-semibold rounded transition duration-300']) ?>
                            <?= Html::a('Editar', ['update', 'id' => $model->id], 
                                ['class' => 'flex-1 text-center px-3 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-semibold rounded transition duration-300']) ?>
                            <?= Html::a('Excluir', ['delete', 'id' => $model->id], [
                                'class' => 'flex-1 text-center px-3 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded transition duration-300',
                                'data' => [
                                    'confirm' => 'Tem certeza que deseja excluir esta configuração de comissão?',
                                    'method' => 'post',
                                ],
                            ]) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($dataProvider->totalCount == 0): ?>
            <div class="text-center py-12 bg-white rounded-lg shadow-md">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <p class="text-gray-500 text-lg mb-4">Nenhuma configuração de comissão cadastrada</p>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Nova Configuração',
                    ['create'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300']
                ) ?>
            </div>
        <?php endif; ?>

        <!-- Paginação -->
        <?php if ($dataProvider->pagination->pageCount > 1): ?>
            <div class="mt-6">
                <?= LinkPager::widget([
                    'pagination' => $dataProvider->pagination,
                    'options' => ['class' => 'flex justify-center space-x-2'],
                    'linkOptions' => ['class' => 'px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50 text-sm'],
                    'activePageCssClass' => 'bg-blue-600 text-white border-blue-600',
                    'disabledPageCssClass' => 'opacity-50 cursor-not-allowed',
                    'prevPageLabel' => '←',
                    'nextPageLabel' => '→',
                ]) ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

