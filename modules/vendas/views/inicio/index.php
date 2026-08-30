<?php

/**
 * View: Dashboard do Módulo de Vendas - Versão Mobile First com Logout
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

$permissoesModulo = $permissoesModulo ?? [];
$tokenJwt = $usuario ? $usuario->generateJwt() : '';

/**
 * Array unificado de todos os acessos/cards do sistema.
 */
$cards = [
    // ⚡ AÇÕES RÁPIDAS
    [
        'key' => 'super-admin-lojas',
        'grupo' => 'Ações Rápidas',
        'order' => 1.05,
        'visible' => \app\components\TenantHelper::isAdmin(),
        'label' => 'Painel Super Admin',
        'description' => 'Gerenciar lojas, clientes e ativadores do SaaS',
        'color' => 'purple',
        'url' => ['/admin/loja/index'],
        'badge' => 'Super Admin',
        'badge_bg' => 'bg-purple-200 text-purple-900',
        'icon_emoji' => '👑',
        'card_bg' => 'bg-gradient-to-br from-purple-600 via-indigo-600 to-purple-800 text-white',
    ],
    [
        'key' => 'cadastrar-produto-rapido',
        'grupo' => 'Ações Rápidas',
        'order' => 1.1,
        'label' => 'Cadastrar Produto Rápido',
        'description' => 'Cadastre novo produto em 10 segundos',
        'color' => 'green',
        'type' => 'button',
        'onclick' => 'abrirModalCadastroRapido()',
        'badge' => 'Expresso',
        'badge_bg' => 'bg-amber-300 text-gray-900',
        'icon_emoji' => '⚡',
        'card_bg' => 'bg-gradient-to-br from-emerald-500 via-green-600 to-emerald-700 text-white',
    ],
    [
        'key' => 'venda-expressa',
        'grupo' => 'Ações Rápidas',
        'order' => 1.2,
        'label' => 'Venda Expressa',
        'description' => 'Lançamento relâmpago de vendas',
        'color' => 'orange',
        'url' => ['/vendas/venda-expressa/index'],
        'badge' => 'Modo Encarte',
        'badge_bg' => 'bg-emerald-300 text-gray-900',
        'icon_emoji' => '⚡',
        'card_bg' => 'bg-gradient-to-br from-amber-500 via-orange-600 to-amber-700 text-white',
    ],
    [
        'key' => 'gestao-mesas-comandas',
        'grupo' => 'Ações Rápidas',
        'order' => 1.25,
        'label' => 'Mapa de Mesas & Comandas',
        'description' => 'Gestão de consumo por mesa em tempo real',
        'color' => 'green',
        'url' => ['/vendas/mesa/index'],
        'badge' => 'Food Service',
        'badge_bg' => 'bg-emerald-300 text-gray-900',
        'icon_emoji' => '🍺',
        'card_bg' => 'bg-gradient-to-br from-emerald-600 via-teal-600 to-emerald-800 text-white',
    ],
    [
        'key' => 'nova-venda',
        'grupo' => 'Ações Rápidas',
        'order' => 1.3,
        'label' => 'Nova Venda',
        'description' => 'Registar uma nova venda direta',
        'color' => 'blue',
        'url' => Yii::getAlias('@web') . '/venda-direta/?token=' . $tokenJwt,
        'card_bg' => 'bg-gradient-to-br from-blue-500 to-indigo-600 text-white',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />',
    ],
    [
        'key' => 'novo-orcamento',
        'grupo' => 'Ações Rápidas',
        'order' => 1.4,
        'label' => 'Novo Orçamento',
        'description' => 'Criar cotação (sem baixar estoque)',
        'color' => 'orange',
        'url' => Yii::getAlias('@web') . '/orcamento/',
        'card_bg' => 'bg-gradient-to-br from-orange-500 to-orange-600 text-white',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />',
    ],
    [
        'key' => 'confirmar-pagamentos',
        'grupo' => 'Ações Rápidas',
        'order' => 1.5,
        'label' => 'Confirmar Pagamentos On-line',
        'description' => (isset($countVendasPendentes) && $countVendasPendentes > 0 ? "$countVendasPendentes venda(s) aguardando" : "Nenhuma venda pendente"),
        'color' => 'green',
        'url' => ['/vendas/inicio/confirmar-pagamentos'],
        'card_bg' => 'bg-gradient-to-br from-green-500 to-emerald-600 text-white',
        'visible' => (isset($ehAdministrador) || isset($ehDonoLoja)),
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
    ],
    [
        'key' => 'dashboard-geral',
        'grupo' => 'Ações Rápidas',
        'order' => 1.6,
        'label' => 'Dashboard Geral',
        'description' => 'Visão geral de estatísticas',
        'color' => 'indigo',
        'url' => ['/vendas/dashboard/index'],
        'card_bg' => 'bg-gradient-to-br from-indigo-500 to-indigo-700 text-white',
        'visible' => isset($ehDonoLoja) && $ehDonoLoja,
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2" />',
    ],
    [
        'key' => 'dashboard-executivo',
        'grupo' => 'Ações Rápidas',
        'order' => 1.7,
        'label' => 'Dashboard Executivo',
        'description' => 'Indicadores de BI e Metas',
        'color' => 'purple',
        'url' => ['/vendas/dashboard/executivo'],
        'card_bg' => 'bg-gradient-to-br from-purple-600 to-indigo-800 text-white',
        'visible' => isset($ehDonoLoja) && $ehDonoLoja,
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />',
    ],
    [
        'key' => 'fluxo-caixa',
        'grupo' => 'Ações Rápidas',
        'order' => 1.8,
        'label' => 'Fluxo de Caixa',
        'description' => 'Dashboard Financeiro Real',
        'color' => 'teal',
        'url' => Yii::getAlias('@web') . '/financeiro/index.html',
        'card_bg' => 'bg-gradient-to-br from-emerald-600 to-teal-700 text-white',
        'visible' => isset($ehDonoLoja) && $ehDonoLoja,
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
    ],

    // 👥 GESTÃO GERAL
    [
        'key' => 'clientes',
        'grupo' => 'Gestão Geral',
        'order' => 2.1,
        'visible' => true,
        'label' => 'Clientes',
        'url' => ['/vendas/clientes/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
        'color' => 'blue',
        'description' => 'Gerir clientes'
    ],
    [
        'key' => 'produtos',
        'grupo' => 'Gestão Geral',
        'order' => 2.2,
        'visible' => true,
        'label' => 'Produtos',
        'url' => ['/vendas/produto/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
        'color' => 'green',
        'description' => 'Gerir produtos e estoque'
    ],
    [
        'key' => 'categorias',
        'grupo' => 'Gestão Geral',
        'order' => 2.3,
        'visible' => true,
        'label' => 'Categorias',
        'url' => ['/vendas/categoria/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>',
        'color' => 'purple',
        'description' => 'Gerir categorias de produtos'
    ],
    [
        'key' => 'trilha-sonora',
        'grupo' => 'Gestão Geral',
        'order' => 2.4,
        'visible' => true,
        'label' => 'Gestão de Músicas',
        'url' => ['/vendas/trilha-sonora/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 .895-2 3-2 3 .895 3 2zm12 0c0 1.105-1.343 2-3 2s-3-.895-3-2 .895-2 3-2 3 .895 3 2zM9 10l12-3"/>',
        'color' => 'purple',
        'description' => 'Trilhas e efeitos para vídeos 9:16'
    ],
    [
        'key' => 'fornecedores',
        'grupo' => 'Gestão Geral',
        'order' => 2.5,
        'visible' => true,
        'label' => 'Fornecedores',
        'url' => ['/vendas/fornecedor/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
        'color' => 'orange',
        'description' => 'Gerir fornecedores'
    ],
    [
        'key' => 'compras',
        'grupo' => 'Gestão Geral',
        'order' => 2.6,
        'visible' => true,
        'label' => 'Compras',
        'url' => ['/vendas/compra/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>',
        'color' => 'teal',
        'description' => 'Gerir compras e resuprimentos'
    ],
    [
        'key' => 'dados-financeiros',
        'grupo' => 'Gestão Geral',
        'order' => 2.7,
        'visible' => true,
        'label' => 'Precificação',
        'url' => ['/vendas/dados-financeiros/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
        'color' => 'purple',
        'description' => 'Precificação inteligente (Markup)'
    ],
    [
        'key' => 'lojas',
        'grupo' => 'Gestão Geral',
        'order' => 2.8,
        'visible' => true,
        'label' => 'Lojas / Filiais',
        'url' => ['/vendas/loja/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
        'color' => 'indigo',
        'description' => 'Criar e gerenciar filiais'
    ],
    [
        'key' => 'unidades-medida',
        'grupo' => 'Gestão Geral',
        'order' => 2.9,
        'visible' => true,
        'label' => 'Unidades de Medida',
        'url' => ['/vendas/unidade-medida/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>',
        'color' => 'cyan',
        'description' => 'Gerir unidades e escalas'
    ],
    [
        'key' => 'colaboradores',
        'grupo' => 'Gestão Geral',
        'order' => 2.10,
        'visible' => true,
        'label' => 'Colaboradores',
        'url' => ['/vendas/colaborador/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
        'color' => 'indigo',
        'description' => 'Gerir equipe e permissões'
    ],

    // 💰 FINANCEIRO & CAIXA
    [
        'key' => 'caixa',
        'grupo' => 'Financeiro & Caixa',
        'order' => 3.1,
        'visible' => true,
        'label' => 'Caixa',
        'url' => ['/caixa/caixa/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>',
        'color' => 'green',
        'description' => 'Abertura, fechamento e fluxo de caixa'
    ],
    [
        'key' => 'contas-pagar',
        'grupo' => 'Financeiro & Caixa',
        'order' => 3.2,
        'visible' => true,
        'label' => 'Contas a Pagar',
        'url' => ['/contas-pagar/conta-pagar/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'color' => 'red',
        'description' => 'Lançamento e quitação de despesas'
    ],
    [
        'key' => 'tipos-despesa',
        'grupo' => 'Financeiro & Caixa',
        'order' => 3.3,
        'visible' => true,
        'label' => 'Tipos de Despesa',
        'url' => ['/contas-pagar/tipo-despesa/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>',
        'color' => 'pink',
        'description' => 'Categorias de custos e despesas'
    ],

    // 💳 VENDAS & COBRANÇA
    [
        'key' => 'orcamentos',
        'grupo' => 'Vendas & Cobrança',
        'order' => 4.1,
        'visible' => true,
        'label' => 'Orçamentos (Histórico)',
        'url' => ['/vendas/orcamento/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'color' => 'yellow',
        'description' => 'Histórico e gestão de cotações'
    ],
    [
        'key' => 'vendas',
        'grupo' => 'Vendas & Cobrança',
        'order' => 4.2,
        'visible' => true,
        'label' => 'Vendas Efetivadas',
        'url' => ['/vendas/venda/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />',
        'color' => 'blue',
        'description' => 'Histórico de vendas realizadas'
    ],
    [
        'key' => 'formas-pagamento',
        'grupo' => 'Vendas & Cobrança',
        'order' => 4.3,
        'visible' => true,
        'label' => 'Formas de Pgto.',
        'url' => ['/vendas/forma-pagamento/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>',
        'color' => 'teal',
        'description' => 'Formas de pagamento da loja'
    ],
    [
        'key' => 'comissoes',
        'grupo' => 'Vendas & Cobrança',
        'order' => 4.4,
        'visible' => true,
        'label' => 'Comissões',
        'url' => ['/vendas/comissao/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>',
        'color' => 'purple',
        'description' => 'Relatórios e pagamento de comissões'
    ],
    [
        'key' => 'comissao-config',
        'grupo' => 'Vendas & Cobrança',
        'order' => 4.5,
        'visible' => true,
        'label' => 'Config. Comissões',
        'url' => ['/vendas/comissao-config/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'color' => 'purple',
        'description' => 'Regras e percentuais de comissão'
    ],
    [
        'key' => 'periodo-cobranca',
        'grupo' => 'Vendas & Cobrança',
        'order' => 4.6,
        'visible' => true,
        'label' => 'Período Cobrança',
        'url' => ['/vendas/periodo-cobranca/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
        'color' => 'orange',
        'description' => 'Ciclos e prazos de cobrança'
    ],
    [
        'key' => 'carteira-cobranca',
        'grupo' => 'Vendas & Cobrança',
        'order' => 4.7,
        'visible' => true,
        'label' => 'Carteira Cobrança',
        'url' => ['/vendas/carteira-cobranca/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>',
        'color' => 'red',
        'description' => 'Gestão de títulos e carteira'
    ],
    [
        'key' => 'itens-avulsos',
        'grupo' => 'Vendas & Cobrança',
        'order' => 4.8,
        'visible' => true,
        'label' => 'Itens Avulsos / Pendentes',
        'url' => ['/vendas/produto/itens-avulsos'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
        'color' => 'yellow',
        'description' => 'Gerir itens vendidos sem cadastro'
    ],
    [
        'key' => 'historico-cobranca',
        'grupo' => 'Vendas & Cobrança',
        'order' => 4.9,
        'visible' => true,
        'label' => 'Histórico Cobrança',
        'url' => ['/vendas/historico-cobranca/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'color' => 'pink',
        'description' => 'Registro de cobranças realizadas'
    ],
    [
        'key' => 'parcelas',
        'grupo' => 'Vendas & Cobrança',
        'order' => 4.10,
        'visible' => true,
        'label' => 'Parcelas',
        'url' => ['/vendas/parcela/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
        'color' => 'cyan',
        'description' => 'Gestão detalhada de parcelas'
    ],

    // 🚚 LOGÍSTICA & REGIÕES
    [
        'key' => 'taxa-entrega',
        'grupo' => 'Logística & Regiões',
        'order' => 5.1,
        'visible' => true,
        'label' => 'Gestão de Fretes',
        'url' => ['/vendas/taxa-entrega/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>',
        'color' => 'orange',
        'description' => 'Taxas por Cidade, Bairro e CEP'
    ],
    [
        'key' => 'regioes',
        'grupo' => 'Logística & Regiões',
        'order' => 5.2,
        'visible' => true,
        'label' => 'Região',
        'url' => ['/vendas/regiao/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'color' => 'green',
        'description' => 'Territórios e regiões atendidas'
    ],
    [
        'key' => 'rotas-cobranca',
        'grupo' => 'Logística & Regiões',
        'order' => 5.3,
        'visible' => true,
        'label' => 'Rotas Cobrança',
        'url' => ['/vendas/rota-cobranca/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>',
        'color' => 'indigo',
        'description' => 'Roteiros de cobrança em campo'
    ],

    // 🌐 INTEGRAÇÕES
    [
        'key' => 'marketplaces',
        'grupo' => 'Integrações',
        'order' => 6.1,
        'visible' => true,
        'label' => 'Marketplaces',
        'url' => ['/marketplace/dashboard/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />',
        'color' => 'cyan',
        'description' => 'Integrações multicanal e e-commerce'
    ],
    [
        'key' => 'whatsapp-evolution',
        'grupo' => 'Integrações',
        'order' => 6.2,
        'visible' => true,
        'label' => 'WhatsApp Evolution',
        'url' => ['/evolution/config/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>',
        'color' => 'green',
        'description' => 'Instância e conexões do WhatsApp'
    ],

    // ⚙️ CONFIGURAÇÕES DO SISTEMA
    [
        'key' => 'status-parcela',
        'grupo' => 'Configurações do Sistema',
        'order' => 7.1,
        'visible' => true,
        'label' => 'Status Parcela',
        'url' => ['/vendas/status-parcela/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'color' => 'blue',
        'description' => 'Tipos de status de parcelas'
    ],
    [
        'key' => 'status-venda',
        'grupo' => 'Configurações do Sistema',
        'order' => 7.2,
        'visible' => true,
        'label' => 'Status Vendas',
        'url' => ['/vendas/status-venda/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
        'color' => 'purple',
        'description' => 'Tipos de status de vendas'
    ],
    [
        'key' => 'usuarios',
        'grupo' => 'Configurações do Sistema',
        'order' => 7.3,
        'visible' => true,
        'label' => 'Usuários',
        'url' => ['/vendas/usuario/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
        'color' => 'indigo',
        'description' => 'Acessos e logins de usuários'
    ],
    [
        'key' => 'configuracoes',
        'grupo' => 'Configurações do Sistema',
        'order' => 7.4,
        'visible' => true,
        'label' => 'Configurações',
        'url' => ['/vendas/configuracao/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'color' => 'gray',
        'description' => 'Ajustes gerais do sistema'
    ],
    [
        'key' => 'dados-loja',
        'grupo' => 'Configurações do Sistema',
        'order' => 7.5,
        'visible' => true,
        'label' => 'Dados da Loja',
        'url' => ['/vendas/loja-configuracao/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
        'color' => 'blue',
        'description' => 'Nome, CNPJ, endereço e marca da loja'
    ],
];

// Filtra cards ativos (visíveis e com permissão TRUE no Liga/Desliga)
$visibleCards = array_filter($cards, function ($card) use ($ehAdministrador, $permissoesModulo) {
    if (isset($card['visible']) && $card['visible'] === false) {
        return false;
    }
    $key = $card['key'] ?? null;
    if ($key && isset($permissoesModulo[$key]) && $permissoesModulo[$key] === false) {
        return false;
    }
    return true;
});

// Ordena por 'order'
usort($visibleCards, function ($a, $b) {
    return ($a['order'] ?? 999) <=> ($b['order'] ?? 999);
});

// Agrupa por grupo
$cardsPorGrupo = [];
foreach ($visibleCards as $card) {
    $g = $card['grupo'] ?? 'Outros';
    $cardsPorGrupo[$g][] = $card;
}

$gruposOrdenados = [
    'Ações Rápidas' => ['icone' => '⚡', 'subtitulo' => 'Atalhos principais e lançamentos relâmpago'],
    'Gestão Geral' => ['icone' => '👥', 'subtitulo' => 'Catálogo, clientes, fornecedores e filiais'],
    'Financeiro & Caixa' => ['icone' => '💰', 'subtitulo' => 'Fluxo financeiro, caixas e despesas'],
    'Vendas & Cobrança' => ['icone' => '💳', 'subtitulo' => 'Relatórios de vendas, comissões e carteira'],
    'Logística & Regiões' => ['icone' => '🚚', 'subtitulo' => 'Entregas, fretes por CEP e rotas'],
    'Integrações' => ['icone' => '🌐', 'subtitulo' => 'Conexões multicanal e disparo de mensagens'],
    'Configurações do Sistema' => ['icone' => '⚙️', 'subtitulo' => 'Parâmetros de status, usuários e dados da loja'],
];

?>

<!-- Container Principal com responsividade mobile-first -->
<div class="min-h-screen bg-gray-50">
    <!-- Header fixo com menu de usuário -->
    <div class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <div class="flex items-center justify-between h-14 sm:h-16">
                <!-- Logo/Título da Loja (SaaS) -->
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <?php if (isset($lojaConfig) && !empty($lojaConfig->logo_path)): ?>
                        <?php
                        $logoUrl = trim($lojaConfig->logo_path);
                        if (!preg_match('/^https?:\/\//', $logoUrl)) {
                            $logoUrl = Yii::getAlias('@web') . '/' . ltrim($logoUrl, '/');
                        }
                        ?>
                        <img src="<?= Html::encode($logoUrl) ?>" alt="Logo" class="h-8 sm:h-10 object-contain rounded">
                    <?php else: ?>
                        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg p-1.5 sm:p-2">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    <?php endif; ?>
                    <span class="text-base sm:text-lg font-bold text-gray-900 hidden sm:inline">
                        <?= isset($lojaConfig) && !empty($lojaConfig->nome_loja) ? Html::encode($lojaConfig->nome_loja) : 'PULSE SaaS' ?>
                    </span>
                </div>

                <!-- Menu de Usuário & Atalhos -->
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <?php if (\app\components\TenantHelper::isAdmin()): ?>
                        <!-- Botão de Destaque Super Admin -->
                        <a href="<?= Url::to(['/admin/loja/index']) ?>"
                            class="inline-flex items-center space-x-1.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-xs sm:text-sm font-bold px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg sm:rounded-xl shadow transition duration-200 border border-purple-400/30">
                            <span>👑</span>
                            <span class="hidden xs:inline">Painel Super Admin</span>
                        </a>
                    <?php endif; ?>

                    <div class="relative" id="userMenuContainer">
                        <button type="button"
                            id="userMenuButton"
                            class="flex items-center space-x-2 sm:space-x-3 bg-gray-50 hover:bg-gray-100 rounded-lg sm:rounded-xl px-2.5 sm:px-4 py-1.5 sm:py-2 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <!-- Avatar -->
                            <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center">
                                <span class="text-white text-xs sm:text-sm font-bold">
                                    <?= $usuario ? strtoupper(substr($usuario->getPrimeiroNome(), 0, 1)) : 'U' ?>
                                </span>
                            </div>
                            <!-- Nome do usuário (oculto em mobile pequeno) -->
                            <span class="text-sm font-medium text-gray-700 hidden md:inline max-w-32 truncate">
                                <?= $usuario ? Html::encode($usuario->getPrimeiroNome()) : 'Utilizador' ?>
                            </span>
                            <!-- Ícone dropdown -->
                            <svg class="w-4 h-4 text-gray-500 transition-transform duration-200" id="chevronIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="userMenu"
                            class="hidden absolute right-0 mt-2 w-56 sm:w-64 bg-white rounded-xl shadow-lg border border-gray-200 py-1 z-50">
                            <!-- Informações do usuário -->
                            <div class="px-4 py-3 border-b border-gray-100">
                                <p class="text-sm font-medium text-gray-900">
                                    <?= $usuario ? Html::encode($usuario->username ?? $usuario->getPrimeiroNome()) : 'Utilizador' ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    <?= $usuario && isset($usuario->email) ? Html::encode($usuario->email) : 'utilizador@sistema.com' ?>
                                </p>
                            </div>

                            <!-- Opções do menu -->
                            <div class="py-1">
                                <?php if (\app\components\TenantHelper::isAdmin()): ?>
                                    <!-- Painel Super Admin -->
                                    <a href="<?= Url::to(['/admin/loja/index']) ?>"
                                        class="flex items-center px-4 py-2.5 text-sm font-bold text-purple-700 hover:bg-purple-50 transition-colors duration-150 border-b border-gray-100">
                                        <span class="mr-3 text-base">👑</span>
                                        Painel Super Admin
                                    </a>
                                <?php endif; ?>

                                <!-- Perfil -->
                                <?php if (\app\components\TenantHelper::isAdmin()): ?>
                                    <!-- Painel SaaS Superadmin -->
                                    <a href="<?= Url::to(['/admin/financeiro/index']) ?>"
                                        class="flex items-center px-4 py-2.5 text-sm font-bold text-indigo-600 hover:bg-indigo-50 transition-colors duration-150 border-b border-indigo-100">
                                        <svg class="w-5 h-5 mr-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        👑 Painel Gestor SaaS
                                    </a>
                                <?php endif; ?>

                                <!-- Meu Perfil -->
                                <a href="<?= Url::to(['/site/perfil']) ?>"
                                    class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-150">
                                    <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Meu Perfil
                                </a>

                                <!-- Configurações -->
                                <a href="<?= Url::to(['/site/configuracoes']) ?>"
                                    class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-150">
                                    <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Configurações
                                </a>
                            </div>

                        <!-- Logout -->
                        <div class="border-t border-gray-100 py-1">
                            <?= Html::beginForm(['/auth/logout'], 'post', ['id' => 'logout-form', 'class' => 'm-0']) ?>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200 w-full justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Sair
                            </button>
                            <?= Html::endForm() ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="px-4 sm:px-6 lg:px-8 py-6 sm:py-8 max-w-7xl mx-auto space-y-6 sm:space-y-8">

        <!-- Botão Voltar -->
        <div class="flex items-center">
            <a href="<?= Url::to(['/vendas/dashboard']) ?>"
                class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Voltar ao Dashboard
            </a>
        </div>

        <!-- Cabeçalho com Saudação -->
        <div class="text-left space-y-2">
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900">
                Olá, <?= $usuario ? Html::encode($usuario->getPrimeiroNome()) : 'Utilizador' ?>! 👋
            </h1>
            <p class="text-sm sm:text-base text-gray-600">Bem-vindo ao seu painel de vendas.</p>
        </div>

        <!-- Loop pelas Categorias Agrupadas -->
        <div class="space-y-8 sm:space-y-10">
            <?php foreach ($gruposOrdenados as $nomeGrupo => $infoGrupo): ?>
                <?php if (!empty($cardsPorGrupo[$nomeGrupo])): ?>
                    <div class="space-y-4">
                        <!-- Cabeçalho da Categoria -->
                        <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                            <div class="flex items-center space-x-3">
                                <span class="text-2xl sm:text-3xl"><?= $infoGrupo['icone'] ?></span>
                                <div>
                                    <h2 class="text-lg sm:text-xl font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                                        <span><?= Html::encode($nomeGrupo) ?></span>
                                        <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2.5 py-0.5 rounded-full">
                                            <?= count($cardsPorGrupo[$nomeGrupo]) ?>
                                        </span>
                                    </h2>
                                    <p class="text-xs sm:text-sm text-gray-500 font-normal"><?= Html::encode($infoGrupo['subtitulo']) ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Grid de Cards da Categoria -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <?php foreach ($cardsPorGrupo[$nomeGrupo] as $card): ?>
                                <?php
                                $colors = $colorMap[$card['color'] ?? 'blue'] ?? $colorMap['blue'];
                                $isGradient = isset($card['card_bg']);
                                ?>

                                <?php if (isset($card['type']) && $card['type'] === 'button'): ?>
                                    <!-- Botão Interativo Expresso -->
                                    <button type="button" onclick="<?= Html::encode($card['onclick']) ?>"
                                        class="group text-left block w-full <?= $isGradient ? $card['card_bg'] : 'bg-white border border-gray-200 ' . $colors['border'] ?> rounded-xl p-5 shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 active:scale-95 cursor-pointer relative overflow-hidden flex flex-col justify-between min-h-[120px]">
                                        
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="<?= $isGradient ? 'bg-white bg-opacity-20 text-white' : $colors['bg'] . ' ' . $colors['text'] ?> rounded-xl p-3 group-hover:scale-110 transition-transform">
                                                <?php if (isset($card['icon_emoji'])): ?>
                                                    <span class="text-2xl"><?= $card['icon_emoji'] ?></span>
                                                <?php else: ?>
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <?= $card['icon'] ?>
                                                    </svg>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (isset($card['badge'])): ?>
                                                <span class="<?= $card['badge_bg'] ?? 'bg-amber-300 text-gray-900' ?> text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider">
                                                    <?= Html::encode($card['badge']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h3 class="text-base font-bold <?= $isGradient ? 'text-white' : 'text-gray-900 group-hover:' . $colors['text'] ?> transition-colors">
                                                <?= Html::encode($card['label']) ?>
                                            </h3>
                                            <p class="text-xs <?= $isGradient ? 'opacity-90 text-white' : 'text-gray-500' ?> mt-1 line-clamp-2">
                                                <?= Html::encode($card['description']) ?>
                                            </p>
                                        </div>
                                    </button>

                                <?php else: ?>
                                    <!-- Link Normal para o Módulo -->
                                    <?php
                                    $targetUrl = is_array($card['url']) ? Url::to($card['url']) : $card['url'];
                                    ?>
                                    <a href="<?= Html::encode($targetUrl) ?>"
                                        class="group block w-full <?= $isGradient ? $card['card_bg'] : 'bg-white border border-gray-200 ' . $colors['border'] ?> rounded-xl p-5 shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 active:scale-95 relative overflow-hidden flex flex-col justify-between min-h-[120px]">
                                        
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="<?= $isGradient ? 'bg-white bg-opacity-20 text-white' : $colors['bg'] . ' ' . $colors['text'] ?> rounded-xl p-3 group-hover:scale-110 transition-transform">
                                                <?php if (isset($card['icon_emoji'])): ?>
                                                    <span class="text-2xl"><?= $card['icon_emoji'] ?></span>
                                                <?php else: ?>
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <?= $card['icon'] ?>
                                                    </svg>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (isset($card['counter']) && $card['counter'] > 0): ?>
                                                <span class="bg-red-500 text-white text-xs font-bold rounded-full h-6 w-6 flex items-center justify-center shadow-lg">
                                                    <?= $card['counter'] ?>
                                                </span>
                                            <?php elseif (isset($card['badge'])): ?>
                                                <span class="<?= $card['badge_bg'] ?? 'bg-emerald-300 text-gray-900' ?> text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider">
                                                    <?= Html::encode($card['badge']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h3 class="text-base font-bold <?= $isGradient ? 'text-white' : 'text-gray-900 group-hover:' . $colors['text'] ?> transition-colors">
                                                <?= Html::encode($card['label']) ?>
                                            </h3>
                                            <p class="text-xs <?= $isGradient ? 'opacity-90 text-white' : 'text-gray-500' ?> mt-1 line-clamp-2">
                                                <?= Html::encode($card['description']) ?>
                                            </p>
                                        </div>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Rodapé com informações adicionais -->
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

<script>
    // JavaScript para controlar o menu dropdown do usuário
    document.addEventListener('DOMContentLoaded', function() {
        const userMenuButton = document.getElementById('userMenuButton');
        const userMenu = document.getElementById('userMenu');
        const chevronIcon = document.getElementById('chevronIcon');
        const userMenuContainer = document.getElementById('userMenuContainer');

        if (userMenuButton && userMenu) {
            // Toggle do menu ao clicar no botão
            userMenuButton.addEventListener('click', function(e) {
                e.stopPropagation();
                const isHidden = userMenu.classList.contains('hidden');

                if (isHidden) {
                    userMenu.classList.remove('hidden');
                    chevronIcon.style.transform = 'rotate(180deg)';
                } else {
                    userMenu.classList.add('hidden');
                    chevronIcon.style.transform = 'rotate(0deg)';
                }
            });

            // Fechar menu ao clicar fora
            document.addEventListener('click', function(e) {
                if (!userMenuContainer.contains(e.target)) {
                    userMenu.classList.add('hidden');
                    chevronIcon.style.transform = 'rotate(0deg)';
                }
            });

            // Prevenir que cliques dentro do menu o fechem
            userMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            // Fechar menu ao pressionar ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !userMenu.classList.contains('hidden')) {
                    userMenu.classList.add('hidden');
                    chevronIcon.style.transform = 'rotate(0deg)';
                }
            });
        }

        // Confirmação de logout (opcional, mas recomendado para segurança)
        const logoutForm = document.getElementById('logout-form');
        if (logoutForm) {
            logoutForm.addEventListener('submit', function(e) {
                const confirmLogout = confirm('Tem certeza que deseja sair do sistema?');
                if (!confirmLogout) {
                    e.preventDefault();
                }
            });
        }
    });
</script>

<?= $this->render('@app/modules/vendas/views/produto/_modal_cadastro_rapido', ['lojaId' => \app\components\TenantHelper::getId()]) ?>