<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\vendas\models\search\StatusVendaSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Status de Vendas';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="status-venda-index min-h-screen bg-gray-50">
    <div class="px-4 sm:px-6 lg:px-8 py-6 sm:py-8 max-w-7xl mx-auto">
        
        <!-- Breadcrumbs -->
        <nav class="flex mb-4 sm:mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="<?= Url::to(['/vendas/default/index']) ?>" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Status de Vendas</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="mb-6 sm:mb-8">
            <div class="sm:flex sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">
                        <?= Html::encode($this->title) ?>
                    </h1>
                    <p class="text-sm sm:text-base text-gray-600">
                        Gerir os estados de vendas do sistema
                    </p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <?= Html::a(
                        '<svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <span class="hidden sm:inline">Criar Novo Status</span>
                        <span class="sm:hidden">Novo</span>',
                        ['create'],
                        ['class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white text-sm font-semibold rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2']
                    ) ?>
                </div>
            </div>
        </div>

        <!-- Barra de Pesquisa -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6 mb-6">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <label for="search-input" class="block text-sm font-medium text-gray-700 mb-2">
                        Pesquisar Status
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" 
                               id="search-input" 
                               class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                               placeholder="Buscar por código ou descrição...">
                    </div>
                </div>
                <div class="flex items-end">
                    <button id="clear-search" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-150">
                        Limpar
                    </button>
                </div>
            </div>
        </div>

        <!-- Lista de Cards -->
        <?php 
        $models = $dataProvider->getModels();
        $totalCount = $dataProvider->getTotalCount();
        ?>

        <?php if (empty($models)): ?>
            <!-- Empty State -->
            <div class="bg-white rounded-xl shadow-sm border-2 border-dashed border-gray-300 p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum status encontrado</h3>
                <p class="text-sm text-gray-500 mb-6">Comece criando um novo status de venda para organizar o sistema.</p>
                <?= Html::a(
                    'Criar Primeiro Status',
                    ['create'],
                    ['class' => 'inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-150']
                ) ?>
            </div>
        <?php else: ?>
            <!-- Contador de Resultados -->
            <div class="mb-4 flex items-center justify-between text-sm text-gray-600">
                <span>
                    A exibir <strong class="font-semibold text-gray-900"><?= count($models) ?></strong> de 
                    <strong class="font-semibold text-gray-900"><?= $totalCount ?></strong> status
                </span>
            </div>

            <!-- Grid de Cards -->
            <div id="cards-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <?php foreach ($models as $model): ?>
                    <div class="status-card bg-white rounded-xl shadow-sm border-2 border-gray-200 hover:border-blue-400 hover:shadow-lg transition-all duration-300 overflow-hidden"
                         data-codigo="<?= strtolower(Html::encode($model->codigo)) ?>"
                         data-descricao="<?= strtolower(Html::encode($model->descricao)) ?>">
                        
                        <!-- Header do Card com Badge -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-5 py-4 border-b border-gray-200">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-semibold bg-blue-600 text-white shadow-sm">
                                        <?= Html::encode($model->codigo) ?>
                                    </span>
                                </div>
                                <svg class="w-5 h-5 text-blue-400 flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Conteúdo do Card -->
                        <div class="p-5">
                            <h3 class="text-lg font-bold text-gray-900 mb-3 line-clamp-2">
                                <?= Html::encode($model->descricao) ?>
                            </h3>

                            <!-- Informações Adicionais -->
                            <div class="space-y-2 mb-4 text-sm text-gray-600">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    <span class="font-medium">Código:</span>
                                    <span class="ml-1 font-mono text-xs"><?= Html::encode($model->codigo) ?></span>
                                </div>
                            </div>

                            <!-- Botões de Ação -->
                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <div class="flex space-x-2">
                                    <?= Html::a(
                                        '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>',
                                        ['view', 'id' => $model->codigo],
                                        [
                                            'class' => 'inline-flex items-center justify-center w-9 h-9 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors duration-150',
                                            'title' => 'Ver Detalhes'
                                        ]
                                    ) ?>
                                    <?= Html::a(
                                        '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>',
                                        ['update', 'id' => $model->codigo],
                                        [
                                            'class' => 'inline-flex items-center justify-center w-9 h-9 text-yellow-600 hover:text-yellow-700 hover:bg-yellow-50 rounded-lg transition-colors duration-150',
                                            'title' => 'Editar'
                                        ]
                                    ) ?>
                                </div>
                                <?= Html::a(
                                    '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>',
                                    ['delete', 'id' => $model->codigo],
                                    [
                                        'class' => 'inline-flex items-center justify-center w-9 h-9 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors duration-150',
                                        'title' => 'Apagar',
                                        'data' => [
                                            'confirm' => 'Tem a certeza que quer apagar este status? Esta ação não pode ser revertida.',
                                            'method' => 'post',
                                        ],
                                    ]
                                ) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Mensagem quando não há resultados da pesquisa -->
            <div id="no-results" class="hidden bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum resultado encontrado</h3>
                <p class="text-sm text-gray-500">Tente ajustar os termos de pesquisa</p>
            </div>
        <?php endif; ?>

        <!-- Info Footer -->
        <div class="mt-6 flex items-center justify-center text-sm text-gray-500">
            <div class="flex items-center space-x-2 bg-blue-50 px-4 py-2 rounded-lg border border-blue-100">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-blue-700 font-medium">Use a barra de pesquisa para filtrar os status</span>
            </div>
        </div>

    </div>
</div>

<style>
/* Animações para os cards */
.status-card {
    animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Efeito de hover nos cards */
.status-card:hover {
    transform: translateY(-4px);
}

/* Line clamp para descrições longas */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Transições suaves */
* {
    -webkit-tap-highlight-color: transparent;
}

/* Mobile improvements */
@media (max-width: 640px) {
    .status-card {
        font-size: 0.9375rem;
    }
}
</style>

<script>
// Função de pesquisa em tempo real
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const clearButton = document.getElementById('clear-search');
    const cardsContainer = document.getElementById('cards-container');
    const noResults = document.getElementById('no-results');
    
    if (!searchInput || !cardsContainer) return;
    
    // Função para filtrar cards
    function filterCards() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.status-card');
        let visibleCount = 0;
        
        cards.forEach(card => {
            const codigo = card.dataset.codigo || '';
            const descricao = card.dataset.descricao || '';
            
            const matches = codigo.includes(searchTerm) || descricao.includes(searchTerm);
            
            if (matches || searchTerm === '') {
                card.style.display = 'block';
                visibleCount++;
                // Animação de entrada
                card.style.animation = 'fadeInUp 0.3s ease-out';
            } else {
                card.style.display = 'none';
            }
        });
        
        // Mostrar/ocultar mensagem de "nenhum resultado"
        if (noResults) {
            if (visibleCount === 0 && searchTerm !== '') {
                cardsContainer.style.display = 'none';
                noResults.style.display = 'block';
            } else {
                cardsContainer.style.display = 'grid';
                noResults.style.display = 'none';
            }
        }
    }
    
    // Event listener para pesquisa
    searchInput.addEventListener('input', filterCards);
    
    // Event listener para limpar pesquisa
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            filterCards();
            searchInput.focus();
        });
    }
    
    // Limpar pesquisa com tecla ESC
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchInput.value = '';
            filterCards();
        }
    });
});
</script>