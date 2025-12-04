<?php
/**
 * View: Dashboard Principal
 * Localização: app/modules/vendas/views/dashboard/index.php
 * * @var yii\web\View $this
 * @var app\modules\vendas\models\Usuario $usuario
 * @var array $stats
 * @var float $totalVendasMes
 * @var int $quantidadeVendasMes
 * @var array $clientesRecentes
 * @var array $produtosMaisVendidos
 * @var array $vendasRecentes
 */

use yii\helpers\Html;
use yii\helpers\Url;

// ✅ CORREÇÃO: O método getPrimeiroNome() está correto e existe no Usuario.php
$this->title = 'Dashboard - ' . $usuario->getPrimeiroNome(); 
?>

<div class="space-y-6">
    
    <!-- Barra de ações no topo -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center space-x-3">
                <a href="<?= Url::to(['/vendas/inicio']) ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Início
                </a>
            </div>
            <div class="flex items-center space-x-3">
                <?= Html::beginForm(['/auth/logout'], 'post', ['class' => 'm-0']) ?>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Sair
                    </button>
                <?= Html::endForm() ?>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    Olá, <?= Html::encode($usuario->getPrimeiroNome()) ?>! 👋
                </h1>
                <p class="text-gray-600 mt-1">
                    Bem-vindo ao seu painel de controle
                </p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">Hoje é</p>
                <p class="text-lg font-semibold text-gray-900">
                    <?= date('d/m/Y') ?>
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total de Clientes</p>
                    <p class="text-3xl font-bold mt-2"><?= $stats['total_clientes'] ?></p>
                </div>
                <div class="bg-blue-400 bg-opacity-30 rounded-full p-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <?= Html::a('Ver todos →', ['/vendas/clientes/index'], ['class' => 'text-sm text-blue-100 hover:text-white']) ?>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Total de Produtos</p>
                    <p class="text-3xl font-bold mt-2"><?= $stats['total_produtos'] ?></p>
                </div>
                <div class="bg-green-400 bg-opacity-30 rounded-full p-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <?= Html::a('Ver todos →', ['/vendas/produto/index'], ['class' => 'text-sm text-green-100 hover:text-white']) ?>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Total de Vendas</p>
                    <p class="text-3xl font-bold mt-2"><?= $stats['total_vendas'] ?></p>
                </div>
                <div class="bg-purple-400 bg-opacity-30 rounded-full p-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <?= Html::a('Ver todas →', ['/vendas/venda/index'], ['class' => 'text-sm text-purple-100 hover:text-white']) ?>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm font-medium">Vendas Este Mês</p>
                    <p class="text-3xl font-bold mt-2">R$ <?= number_format($totalVendasMes, 2, ',', '.') ?></p>
                    <p class="text-sm text-orange-100 mt-1"><?= $quantidadeVendasMes ?> vendas</p>
                </div>
                <div class="bg-orange-400 bg-opacity-30 rounded-full p-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <?= Html::a('Nova venda →', ['/vendas/venda/create'], ['class' => 'text-sm text-orange-100 hover:text-white']) ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">Clientes Recentes</h2>
                    <?= Html::a('Ver todos', ['/vendas/cliente/index'], ['class' => 'text-blue-600 hover:text-blue-800 text-sm font-medium']) ?>
                </div>
            </div>
            <div class="p-6">
                <?php if (empty($clientesRecentes)): ?>
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <p class="mt-2 text-gray-500">Nenhum cliente cadastrado</p>
                        <?= Html::a('Cadastrar primeiro cliente', ['/vendas/cliente/create'], ['class' => 'mt-4 inline-block text-blue-600 hover:text-blue-800']) ?>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($clientesRecentes as $cliente): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-blue-500 text-white rounded-full w-10 h-10 flex items-center justify-center font-semibold">
                                        <?= strtoupper(substr($cliente->nome_completo, 0, 2)) ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900"><?= Html::encode($cliente->nome_completo) ?></p>
                                        <p class="text-sm text-gray-500"><?= Html::encode($cliente->telefone) ?></p>
                                    </div>
                                </div>
                                <?= Html::a('Ver', ['/vendas/cliente/view', 'id' => $cliente->id], ['class' => 'text-blue-600 hover:text-blue-800 text-sm']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">Produtos Mais Vendidos</h2>
                    <?= Html::a('Ver todos', ['/vendas/produto/index'], ['class' => 'text-blue-600 hover:text-blue-800 text-sm font-medium']) ?>
                </div>
            </div>
            <div class="p-6">
                <?php if (empty($produtosMaisVendidos)): ?>
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <p class="mt-2 text-gray-500">Nenhuma venda registrada</p>
                        <?= Html::a('Cadastrar produtos', ['/vendas/produto/create'], ['class' => 'mt-4 inline-block text-blue-600 hover:text-blue-800']) ?>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($produtosMaisVendidos as $produto): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900"><?= Html::encode($produto['nome']) ?></p>
                                    <p class="text-sm text-gray-500">
                                        R$ <?= number_format($produto['preco_venda_sugerido'], 2, ',', '.') ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-green-600"><?= $produto['quantidade_total'] ?></p>
                                    <p class="text-xs text-gray-500">vendidos</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900">Vendas Recentes</h2>
                <?= Html::a('Ver todas', ['/vendas/venda/index'], ['class' => 'text-blue-600 hover:text-blue-800 text-sm font-medium']) ?>
            </div>
        </div>
        <div class="overflow-x-auto">
            <?php if (empty($vendasRecentes)): ?>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="mt-2 text-gray-500">Nenhuma venda registrada</p>
                    <?= Html::a('Registrar primeira venda', ['/vendas/venda/create'], ['class' => 'mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700']) ?>
                </div>
            <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($vendasRecentes as $venda): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= date('d/m/Y', strtotime($venda->data_venda)) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= Html::encode($venda->cliente->nome_completo ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusClass = [
                                        'pendente' => 'bg-yellow-100 text-yellow-800',
                                        'pago' => 'bg-green-100 text-green-800',
                                        'cancelado' => 'bg-red-100 text-red-800',
                                    ];
                                    // ✅ CORREÇÃO: Model Venda usa 'status_venda_codigo'
                                    $class = $statusClass[$venda->status_venda_codigo] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $class ?>">
                                        <?= ucfirst($venda->status_venda_codigo) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                    R$ <?= number_format($venda->valor_total, 2, ',', '.') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <?= Html::a('Ver', ['/vendas/venda/view', 'id' => $venda->id], ['class' => 'text-blue-600 hover:text-blue-900']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
        <h2 class="text-2xl font-bold mb-4">Ações Rápidas</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <?= Html::a(
                '<svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg><span>Nova Venda</span>',
                ['/vendas/venda/create'],
                ['class' => 'bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-4 text-center transition flex flex-col items-center justify-center']
            ) ?>
            
            <?= Html::a(
                '<svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg><span>Novo Cliente</span>',
                ['/vendas/cliente/create'],
                ['class' => 'bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-4 text-center transition flex flex-col items-center justify-center']
            ) ?>
            
            <?= Html::a(
                '<svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg><span>Novo Produto</span>',
                ['/vendas/produto/create'],
                ['class' => 'bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-4 text-center transition flex flex-col items-center justify-center']
            ) ?>
            
            <?= Html::a(
                '<svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826 3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span>Configurações</span>',
                ['/vendas/configuracao/index'],
                ['class' => 'bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-4 text-center transition flex flex-col items-center justify-center']
            ) ?>
        </div>
    </div>

</div>