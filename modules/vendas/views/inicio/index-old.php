<?php
/**
 * View: Dashboard do Módulo de Vendas - Versão Melhorada
 * @var yii\web\View $this
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Painel de Vendas';

// Obtém o utilizador para a saudação
$usuario = Yii::$app->user->identity;

// Mapeamento de cores para evitar classes dinâmicas do Tailwind
$colorMap = [
    'blue' => [
        'bg' => 'bg-blue-100',
        'text' => 'text-blue-600',
        'border' => 'hover:border-blue-500',
        'ring' => 'focus:ring-blue-500'
    ],
    'green' => [
        'bg' => 'bg-green-100',
        'text' => 'text-green-600',
        'border' => 'hover:border-green-500',
        'ring' => 'focus:ring-green-500'
    ],
    'orange' => [
        'bg' => 'bg-orange-100',
        'text' => 'text-orange-600',
        'border' => 'hover:border-orange-500',
        'ring' => 'focus:ring-orange-500'
    ],
    'indigo' => [
        'bg' => 'bg-indigo-100',
        'text' => 'text-indigo-600',
        'border' => 'hover:border-indigo-500',
        'ring' => 'focus:ring-indigo-500'
    ],
    'teal' => [
        'bg' => 'bg-teal-100',
        'text' => 'text-teal-600',
        'border' => 'hover:border-teal-500',
        'ring' => 'focus:ring-teal-500'
    ],
    'purple' => [
        'bg' => 'bg-purple-100',
        'text' => 'text-purple-600',
        'border' => 'hover:border-purple-500',
        'ring' => 'focus:ring-purple-500'
    ],
    'yellow' => [
        'bg' => 'bg-amber-100',
        'text' => 'text-amber-600',
        'border' => 'hover:border-amber-500',
        'ring' => 'focus:ring-amber-500'
    ],
    'gray' => [
        'bg' => 'bg-gray-100',
        'text' => 'text-gray-600',
        'border' => 'hover:border-gray-500',
        'ring' => 'focus:ring-gray-500'
    ],
    'red' => [
        'bg' => 'bg-red-100',
        'text' => 'text-red-600',
        'border' => 'hover:border-red-500',
        'ring' => 'focus:ring-red-500'
    ],
    'pink' => [
        'bg' => 'bg-pink-100',
        'text' => 'text-pink-600',
        'border' => 'hover:border-pink-500',
        'ring' => 'focus:ring-pink-500'
    ],
    'cyan' => [
        'bg' => 'bg-cyan-100',
        'text' => 'text-cyan-600',
        'border' => 'hover:border-cyan-500',
        'ring' => 'focus:ring-cyan-500'
    ],
];

/**
 * Array de configuração para os cards do dashboard.
 * 
 * IMPORTANTE: 
 * - 'order': Define a ordem de exibição (menor número aparece primeiro)
 * - 'visible': Define se o card deve ser exibido (true/false)
 * - Para alterar a ordem, basta modificar o valor de 'order'
 * - Cards com mesmo 'order' serão ordenados pela ordem no array
 */
$cards = [
    [
        'order' => 1,
        'visible' => true,
        'label' => 'Clientes',
        'url' => ['/vendas/clientes/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
        'color' => 'blue',
        'description' => 'Gerir clientes'
    ],
    [
        'order' => 2,
        'visible' => true,
        'label' => 'Produtos',
        'url' => ['/vendas/produto/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
        'color' => 'green',
        'description' => 'Gerir produtos'
    ],
    [
        'order' => 3,
        'visible' => true,
        'label' => 'Categorias',
        'url' => ['/vendas/categoria/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>',
        'color' => 'orange',
        'description' => 'Gerir categorias'
    ],
    [
        'order' => 4,
        'visible' => true,
        'label' => 'Colaboradores',
        'url' => ['/vendas/colaborador/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
        'color' => 'indigo',
        'description' => 'Gerir colaboradores'
    ],
    [
        'order' => 7,
        'visible' => true,
        'label' => 'Orçamentos',
        'url' => ['/vendas/orcamento/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'color' => 'yellow',
        'description' => 'Gerir orçamentos'
    ],
    [
        'order' => 5,
        'visible' => true,
        'label' => 'Formas de Pgto.',
        'url' => ['/vendas/forma-pagamento/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>',
        'color' => 'teal',
        'description' => 'Formas de pagamento'
    ],
    [
        'order' => 6,
        'visible' => true,
        'label' => 'Comissões',
        'url' => ['/vendas/comissao/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>',
        'color' => 'purple',
        'description' => 'Gerir comissões'
    ],
    [
        'order' => 8,
        'visible' => true,
        'label' => 'Carteira Cobrança',
        'url' => ['/vendas/carteira-cobranca/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>',
        'color' => 'red',
        'description' => 'Carteira de cobrança'
    ],
    [
        'order' => 9,
        'visible' => true,
        'label' => 'Histórico Cobrança',
        'url' => ['/vendas/historico-cobranca/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'color' => 'pink',
        'description' => 'Histórico de cobranças'
    ],
    [
        'order' => 10,
        'visible' => true,
        'label' => 'Parcelas',
        'url' => ['/vendas/parcela/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
        'color' => 'cyan',
        'description' => 'Gerir parcelas'
    ],
    [
        'order' => 11,
        'visible' => true,
        'label' => 'Região',
        'url' => ['/vendas/regiao/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'color' => 'green',
        'description' => 'Gerir regiões'
    ],
    [
        'order' => 12,
        'visible' => true,
        'label' => 'Rotas Cobrança',
        'url' => ['/vendas/rota-cobranca/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>',
        'color' => 'indigo',
        'description' => 'Rotas de cobrança'
    ],
    [
        'order' => 13,
        'visible' => true,
        'label' => 'Status Parcela',
        'url' => ['/vendas/status-parcela/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'color' => 'blue',
        'description' => 'Status de parcelas'
    ],
    [
        'order' => 14,
        'visible' => true,
        'label' => 'Status Vendas',
        'url' => ['/vendas/status-venda/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
        'color' => 'purple',
        'description' => 'Status de vendas'
    ],
    [
        'order' => 99,
        'visible' => true,
        'label' => 'Configurações',
        'url' => ['/vendas/configuracao/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'color' => 'gray',
        'description' => 'Configurações do sistema'
    ],
];

// Filtra cards visíveis e ordena pelo índice 'order'
$visibleCards = array_filter($cards, function($card) {
    return isset($card['visible']) && $card['visible'] === true;
});

usort($visibleCards, function($a, $b) {
    return ($a['order'] ?? 999) <=> ($b['order'] ?? 999);
});
?>

<!-- Container Principal com responsividade mobile-first -->
<div class="min-h-screen bg-gray-50">
    <div class="px-4 sm:px-6 lg:px-8 py-6 sm:py-8 max-w-7xl mx-auto space-y-6 sm:space-y-8">
        
        <!-- Cabeçalho com Saudação -->
        <div class="text-left space-y-2">
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900">
                Olá, <?= $usuario ? Html::encode($usuario->getPrimeiroNome()) : 'Utilizador' ?>! 👋
            </h1>
            <p class="text-sm sm:text-base text-gray-600">Bem-vindo ao seu painel de vendas.</p>
        </div>

        <!-- Cards de Ação Rápida (Nova Venda e Listar Vendas) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
            <!-- Nova Venda -->
            <a href="<?= Url::to(['/vendas/venda/create']) ?>" 
               class="group block bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl sm:rounded-2xl p-5 sm:p-6 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 active:scale-95">
                <div class="flex items-center space-x-3 sm:space-x-4">
                    <div class="bg-white bg-opacity-20 rounded-lg sm:rounded-xl p-2.5 sm:p-3 group-hover:bg-opacity-30 transition-all">
                        <svg class="w-7 h-7 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    <div class="flex-1 text-white">
                        <h3 class="text-lg sm:text-xl font-bold mb-0.5">Nova Venda</h3>
                        <p class="text-xs sm:text-sm opacity-90">Registar uma nova venda</p>
                    </div>
                </div>
            </a>

            <!-- Listar Vendas -->
            <a href="<?= Url::to(['/vendas/venda/index']) ?>" 
               class="group block bg-white rounded-xl sm:rounded-2xl p-5 sm:p-6 shadow-md hover:shadow-xl border border-gray-200 transition-all duration-300 transform hover:-translate-y-1 active:scale-95">
                <div class="flex items-center space-x-3 sm:space-x-4">
                    <div class="bg-gray-100 rounded-lg sm:rounded-xl p-2.5 sm:p-3 group-hover:bg-gray-200 transition-all">
                        <svg class="w-7 h-7 sm:w-8 sm:h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-0.5">Listar Vendas</h3>
                        <p class="text-xs sm:text-sm text-gray-600">Ver histórico de vendas</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Seção de Gerenciamento -->
        <div class="space-y-4 sm:space-y-5">
            <div class="flex items-center justify-between">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Gerenciamento</h2>
                <span class="text-xs sm:text-sm text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full">
                    <?= count($visibleCards) ?> módulos
                </span>
            </div>

            <!-- Grid de Cards de Gerenciamento -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-4">
                <?php foreach ($visibleCards as $card): 
                    $colors = $colorMap[$card['color']] ?? $colorMap['gray'];
                ?>
                    <a href="<?= Url::to($card['url']) ?>" 
                       class="group block bg-white rounded-xl sm:rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-lg border-2 border-transparent <?= $colors['border'] ?> transition-all duration-300 transform hover:-translate-y-1 active:scale-95 focus:outline-none focus:ring-2 <?= $colors['ring'] ?> focus:ring-offset-2">
                        <div class="flex flex-col items-center text-center space-y-2 sm:space-y-3">
                            <!-- Ícone -->
                            <div class="<?= $colors['bg'] ?> rounded-lg sm:rounded-xl p-2.5 sm:p-3 inline-flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6 sm:w-7 sm:h-7 lg:w-8 lg:h-8 <?= $colors['text'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <?= $card['icon'] ?>
                                </svg>
                            </div>
                            
                            <!-- Texto -->
                            <div class="space-y-0.5 sm:space-y-1">
                                <h3 class="font-bold text-gray-900 text-sm sm:text-base leading-tight">
                                    <?= Html::encode($card['label']) ?>
                                </h3>
                                <p class="text-xs text-gray-500 font-medium hidden sm:block">
                                    <?= Html::encode($card['description'] ?? 'Gerir') ?>
                                </p>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Rodapé com informações adicionais (opcional) -->
        <div class="pt-4 sm:pt-6 border-t border-gray-200">
            <p class="text-xs sm:text-sm text-center text-gray-500">
                Sistema de Gestão de Vendas • Versão 2.0
            </p>
        </div>

    </div>
</div>

<style>
/* Otimizações de performance e interatividade */
@media (hover: hover) {
    .group:hover {
        cursor: pointer;
    }
}

/* Melhoria de toque em dispositivos móveis */
@media (hover: none) {
    .group:active {
        opacity: 0.8;
    }
}

/* Animações suaves */
* {
    -webkit-tap-highlight-color: transparent;
}
</style>