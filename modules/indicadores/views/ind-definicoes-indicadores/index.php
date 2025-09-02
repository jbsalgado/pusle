<?php

use app\modules\indicadores\models\IndDefinicoesIndicadores;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\LinkPager;
use yii\widgets\ListView;
use yii\widgets\Pjax;


/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Indicadores';
$this->params['breadcrumbs'][] = $this->title;
// Registra o CDN do Tailwind CSS diretamente nesta view
$this->registerJsFile('https://cdn.tailwindcss.com', ['position' => View::POS_HEAD]);
?>

<div class="definicao-indicador-index min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex-1 min-w-0">
                        <!-- AJUSTE: Fontes do título e subtítulo aumentadas -->
                        <h1 class="text-3xl font-bold text-gray-900 sm:text-4xl">
                            <?= Html::encode($this->title) ?>
                        </h1>
                        <p class="mt-2 text-base text-gray-600">
                            Gerencie os indicadores do sistema
                        </p>
                    </div>
                    <div class="mt-4 sm:mt-0 sm:ml-4">
                        <!-- AJUSTE: Fonte do botão aumentada -->
                        <?= Html::a('Novo Indicador', ['create'], [
                            'class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200',
                            'id' => 'create-btn'
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4">
                    <div class="flex-1">
                        <label for="search" class="sr-only">Buscar indicadores</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <!-- AJUSTE: Fonte do campo de busca aumentada -->
                            <input 
                                type="text" 
                                id="search" 
                                name="search" 
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-base" 
                                placeholder="Buscar por nome, código, descrição ou palavras-chave..."
                                autocomplete="off"
                            >
                        </div>
                    </div>
                    <div class="mt-3 sm:mt-0">
                        <!-- AJUSTE: Fonte do botão aumentada -->
                        <button type="button" id="filter-btn" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-base font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="h-5 w-5 mr-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
                            </svg>
                            Filtros
                        </button>
                    </div>
                </div>

                <!-- Filtros Avançados (inicialmente ocultos) -->
                <div id="advanced-filters" class="hidden mt-4 pt-4 border-t border-gray-200">
                    <!-- AJUSTE: Grid ajustado para acomodar o novo filtro -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- NOVO FILTRO: Filtro por Dimensão -->
                        <div>
                            <label for="dimensao-filter" class="block text-base font-medium text-gray-700 mb-1">Dimensão</label>
                            <select id="dimensao-filter" class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-base">
                                <option value="">Todas as dimensões</option>
                                <?php 
                                // Assumindo que existe um método getDimensaoOptions() no seu model
                                // Se o método tiver outro nome ou não existir, ajuste a linha abaixo.
                                // Exemplo: ['1' => 'Estratégico', '2' => 'Tático', '3' => 'Operacional']
                                $dimensaoOptions = method_exists(IndDefinicoesIndicadores::class, 'getDimensaoOptions') 
                                    ? IndDefinicoesIndicadores::getDimensaoOptions() 
                                    : [];
                                foreach ($dimensaoOptions as $value => $label): ?>
                                    <option value="<?= Html::encode($value) ?>"><?= Html::encode($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <!-- AJUSTE: Fonte do label e select aumentada -->
                            <label for="tipo-filter" class="block text-base font-medium text-gray-700 mb-1">Tipo</label>
                            <select id="tipo-filter" class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-base">
                                <option value="">Todos os tipos</option>
                                <?php foreach (IndDefinicoesIndicadores::getTipoEspecificoOptions() as $value => $label): ?>
                                    <option value="<?= Html::encode($value) ?>"><?= Html::encode($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <!-- AJUSTE: Fonte do label e select aumentada -->
                            <label for="polaridade-filter" class="block text-base font-medium text-gray-700 mb-1">Polaridade</label>
                            <select id="polaridade-filter" class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-base">
                                <option value="">Todas as polaridades</option>
                                <?php foreach (IndDefinicoesIndicadores::getPolaridadeOptions() as $value => $label): ?>
                                    <option value="<?= Html::encode($value) ?>"><?= Html::encode($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <!-- AJUSTE: Fonte do label e select aumentada -->
                            <label for="status-filter" class="block text-base font-medium text-gray-700 mb-1">Status</label>
                            <select id="status-filter" class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-base">
                                <option value="">Todos</option>
                                <option value="1">Ativos</option>
                                <option value="0">Inativos</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                             <!-- AJUSTE: Fonte do botão aumentada -->
                            <button type="button" id="clear-filters" class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-base font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Limpar Filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Container -->
        <?php Pjax::begin([
            'id' => 'indicadores-pjax',
            'enablePushState' => false,
            'timeout' => 10000,
        ]); ?>

        <div id="results-container" class="mt-6">
            <?= ListView::widget([
                'dataProvider' => $dataProvider,
                'itemView' => '_item',
                'layout' => "{summary}\n{items}\n{pager}",
                // AJUSTE: Fonte do texto de sumário aumentada
                'summary' => '<div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 rounded-t-lg">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <p class="text-base text-gray-700">
                            Mostrando <span class="font-medium">{begin}</span> até <span class="font-medium">{end}</span>
                            de <span class="font-medium">{totalCount}</span> resultados
                        </p>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-base text-gray-700">
                                Mostrando <span class="font-medium">{begin}</span> até <span class="font-medium">{end}</span>
                                de <span class="font-medium">{totalCount}</span> resultados
                            </p>
                        </div>
                    </div>
                </div>',
                'itemOptions' => ['class' => 'mb-4'],
                'options' => ['class' => 'space-y-4'],
                'pager' => [
                    'class' => 'yii\widgets\LinkPager',
                    'options' => ['class' => 'pagination justify-content-center mt-4'],
                    'linkOptions' => ['class' => 'page-link'],
                    'activePageCssClass' => 'active',
                    'disabledPageCssClass' => 'disabled',
                    'prevPageLabel' => '‹ Anterior',
                    'nextPageLabel' => 'Próxima ›',
                    'firstPageLabel' => 'Primeira',
                    'lastPageLabel' => 'Última',
                ],
            ]); ?>
        </div>

        <?php Pjax::end(); ?>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="flex items-center justify-center min-h-screen">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
        </div>
    </div>
</div>

<!-- Modal para visualização rápida -->
<div id="quick-view-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <!-- AJUSTE: Fonte do título do modal aumentada -->
            <h3 class="text-xl font-medium text-gray-900">Detalhes do Indicador</h3>
            <button type="button" class="close-modal text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="modal-content">
            <!-- Conteúdo será carregado aqui -->
        </div>
    </div>
</div>

<?php
$this->registerJs("
// Busca em tempo real
let searchTimeout;
$('#search').on('input', function() {
    clearTimeout(searchTimeout);
    const query = $(this).val();
    
    searchTimeout = setTimeout(function() {
        $('#loading-overlay').removeClass('hidden');
        // Aplica todos os filtros atuais junto com a busca
        applyFilters();
    }, 500);
});

// Toggle filtros avançados
$('#filter-btn').on('click', function() {
    $('#advanced-filters').toggleClass('hidden');
});

// Limpar filtros
$('#clear-filters').on('click', function() {
    // AJUSTE: Adicionado #dimensao-filter para ser limpo
    $('#dimensao-filter, #tipo-filter, #polaridade-filter, #status-filter, #search').val('');
    applyFilters();
});

// Aplicar filtros ao mudar a seleção
// AJUSTE: Adicionado #dimensao-filter para disparar o evento
$('#dimensao-filter, #tipo-filter, #polaridade-filter, #status-filter').on('change', function() {
    applyFilters();
});

// Função centralizada para aplicar filtros e busca
function applyFilters() {
    // AJUSTE: Adicionado o valor de 'dimensao' aos dados do filtro
    const filters = {
        dimensao: $('#dimensao-filter').val(),
        tipo: $('#tipo-filter').val(),
        polaridade: $('#polaridade-filter').val(),
        status: $('#status-filter').val(),
        q: $('#search').val()
    };
    
    $('#loading-overlay').removeClass('hidden');
    $.pjax.reload({
        container: '#indicadores-pjax',
        data: filters,
        timeout: 10000, // Aumenta o timeout para evitar erros em requisições longas
        complete: function() {
            $('#loading-overlay').addClass('hidden');
        }
    });
}

// Modal de visualização rápida
$(document).on('click', '.quick-view-btn', function(e) {
    e.preventDefault();
    const url = $(this).attr('href');
    
    $.get(url)
        .done(function(data) {
            $('#modal-content').html(data);
            $('#quick-view-modal').removeClass('hidden');
        })
        .fail(function() {
            showNotification('Erro ao carregar os dados do indicador.', 'error');
        });
});

// Fechar modal
$(document).on('click', '.close-modal, #quick-view-modal', function(e) {
    if (e.target === this || $(e.target).closest('.close-modal').length) {
        $('#quick-view-modal').addClass('hidden');
    }
});

// Toggle status
$(document).on('click', '.toggle-status-btn', function(e) {
    e.preventDefault();
    const btn = $(this);
    const url = btn.attr('href');
    
    $.post(url)
        .done(function(response) {
            if (response.success) {
                showNotification(response.message, 'success');
                // Recarrega o Pjax para refletir a mudança de status consistentemente
                $.pjax.reload({container: '#indicadores-pjax'});
            } else {
                showNotification(response.message, 'error');
            }
        })
        .fail(function() {
            showNotification('Erro ao alterar status do indicador', 'error');
        });
});

// Função para mostrar notificações (Toast)
function showNotification(message, type) {
    const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    const notification = $('<div class=\"fixed top-5 right-5 z-[1055] px-4 py-3 rounded-md text-white text-base shadow-lg transform transition-all duration-300 translate-x-full opacity-0\">' + message + '</div>');
    
    $('body').append(notification);
    
    // Animação de entrada
    setTimeout(() => {
        notification.removeClass('translate-x-full opacity-0');
    }, 100);
    
    // Animação de saída
    setTimeout(() => {
        notification.addClass('translate-x-full opacity-0');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Confirmação de exclusão
$(document).on('click', '.delete-btn', function(e) {
    e.preventDefault();
    const url = $(this).attr('href');
    const indicatorName = $(this).data('name');
    
    // Substituindo o confirm() por um modal customizado no futuro seria o ideal
    if (confirm('Tem certeza que deseja excluir o indicador \"' + indicatorName + '\"?\\n\\nEsta ação não pode ser desfeita.')) {
        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json', // Espera uma resposta JSON do servidor
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    $.pjax.reload({container: '#indicadores-pjax'});
                } else {
                    showNotification(response.message || 'Ocorreu um erro.', 'error');
                }
            },
            error: function() {
                showNotification('Erro de comunicação ao tentar excluir o indicador.', 'error');
            }
        });
    }
});
");
?>
