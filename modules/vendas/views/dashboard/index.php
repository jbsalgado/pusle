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

    <?php 
    $ehAdministrador = isset($ehAdministrador) ? (bool)$ehAdministrador : false;
    if (!$ehAdministrador): 
    ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
            <p class="text-yellow-800 font-medium">
                Você não tem permissão para visualizar os dados do dashboard. Apenas administradores podem acessar essas informações.
            </p>
        </div>
    <?php else: ?>
    
    <!-- KPIs Principais -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Receita Hoje -->
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm font-medium">Receita Hoje</p>
                    <p class="text-3xl font-bold mt-2">R$ <?= number_format($kpis['receita_hoje'] ?? 0, 2, ',', '.') ?></p>
                </div>
                <div class="bg-emerald-400 bg-opacity-30 rounded-full p-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Receita Semana -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Receita Semana</p>
                    <p class="text-3xl font-bold mt-2">R$ <?= number_format($kpis['receita_semana'] ?? 0, 2, ',', '.') ?></p>
                </div>
                <div class="bg-blue-400 bg-opacity-30 rounded-full p-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Receita Mês -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Receita Mês</p>
                    <p class="text-3xl font-bold mt-2">R$ <?= number_format($kpis['receita_mes'] ?? 0, 2, ',', '.') ?></p>
                    <p class="text-sm text-purple-100 mt-1"><?= $kpis['qtd_vendas_mes'] ?? 0 ?> vendas</p>
                </div>
                <div class="bg-purple-400 bg-opacity-30 rounded-full p-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Ticket Médio -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm font-medium">Ticket Médio</p>
                    <p class="text-3xl font-bold mt-2">R$ <?= number_format($kpis['ticket_medio'] ?? 0, 2, ',', '.') ?></p>
                </div>
                <div class="bg-orange-400 bg-opacity-30 rounded-full p-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- KPIs Secundários -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mt-6">
        
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Parcelas Pendentes</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= number_format($kpis['parcelas_pendentes'] ?? 0, 0, ',', '.') ?></p>
                    <p class="text-xs text-gray-500 mt-1">R$ <?= number_format($kpis['valor_pendente'] ?? 0, 2, ',', '.') ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Estoque Baixo</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= $kpis['produtos_estoque_baixo'] ?? 0 ?></p>
                    <p class="text-xs text-gray-500 mt-1">produtos</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Clientes</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= $stats['total_clientes'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Produtos</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= $stats['total_produtos'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Vendas</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= $stats['total_vendas'] ?? 0 ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        
        <!-- Gráfico: Vendas por Dia (últimos 30 dias) -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Vendas por Dia (Últimos 30 dias)</h3>
            <canvas id="graficoVendasDia" height="100"></canvas>
        </div>

        <!-- Gráfico: Vendas por Forma de Pagamento -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Vendas por Forma de Pagamento</h3>
            <canvas id="graficoFormaPagamento" height="100"></canvas>
        </div>

        <!-- Gráfico: Vendas por Mês (últimos 12 meses) -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Vendas por Mês (Últimos 12 meses)</h3>
            <canvas id="graficoVendasMes" height="100"></canvas>
        </div>

        <!-- Gráfico: Parceladas vs À Vista -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Parceladas vs À Vista</h3>
            <canvas id="graficoParceladasVsVista" height="100"></canvas>
        </div>
    </div>

    <!-- Gráfico: Produtos Mais Vendidos -->
    <div class="bg-white rounded-lg shadow-sm p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top 10 Produtos Mais Vendidos</h3>
        <canvas id="graficoProdutosVendidos" height="60"></canvas>
    </div>

    <!-- Rank de Vendedores e Alerta de Estoque -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        
        <!-- Rank de Vendedores -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">🏆 Rank de Vendedores (Mês)</h2>
                    <?= Html::a('Ver todos', ['/vendas/colaborador/index'], ['class' => 'text-blue-600 hover:text-blue-800 text-sm font-medium']) ?>
                </div>
            </div>
            <div class="p-6">
                <?php if (empty($rankVendedores)): ?>
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <p class="mt-2 text-gray-500">Nenhuma venda registrada por vendedores este mês</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php 
                        $posicao = 1;
                        $medalhas = ['🥇', '🥈', '🥉'];
                        foreach ($rankVendedores as $vendedor): 
                            $medalha = isset($medalhas[$posicao - 1]) ? $medalhas[$posicao - 1] : $posicao . 'º';
                        ?>
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r <?= $posicao <= 3 ? 'from-yellow-50 to-yellow-100 border-2 border-yellow-300' : 'from-gray-50 to-white border border-gray-200' ?> rounded-lg hover:shadow-md transition">
                                <div class="flex items-center space-x-3 flex-1">
                                    <div class="flex-shrink-0 w-10 h-10 <?= $posicao <= 3 ? 'bg-yellow-400' : 'bg-gray-300' ?> rounded-full flex items-center justify-center font-bold text-white text-sm">
                                        <?= $medalha ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-gray-900 truncate"><?= Html::encode($vendedor['nome_completo']) ?></p>
                                        <p class="text-sm text-gray-600">
                                            <?= $vendedor['total_vendas'] ?> venda<?= $vendedor['total_vendas'] != 1 ? 's' : '' ?>
                                            • Ticket médio: R$ <?= number_format($vendedor['ticket_medio'], 2, ',', '.') ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right ml-4">
                                    <p class="text-lg font-bold text-gray-900">R$ <?= number_format($vendedor['valor_total'], 2, ',', '.') ?></p>
                                    <p class="text-xs text-gray-500">Total</p>
                                </div>
                            </div>
                        <?php 
                        $posicao++;
                        endforeach; 
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alerta: Produtos com Estoque Baixo -->
        <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-500">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <h2 class="text-xl font-semibold text-gray-900">⚠️ Alerta de Estoque</h2>
                    </div>
                    <?= Html::a('Ver produtos', ['/vendas/produto/index'], ['class' => 'text-blue-600 hover:text-blue-800 text-sm font-medium']) ?>
                </div>
                <p class="text-sm text-gray-500 mt-1">Produtos próximos do estoque mínimo</p>
            </div>
            <div class="p-6">
                <?php if (empty($produtosEstoqueBaixo)): ?>
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="mt-2 text-gray-500 font-medium">Todos os produtos com estoque adequado</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <?php foreach ($produtosEstoqueBaixo as $produto): 
                            $estoqueMinimo = $produto->estoque_minimo ?? 10;
                            $pontoCorte = $produto->ponto_corte ?? 5;
                            
                            // Calcula percentual baseado no estoque mínimo
                            $percentualEstoque = $estoqueMinimo > 0 ? min(100, ($produto->estoque_atual / $estoqueMinimo) * 100) : 0;
                            
                            // Define cor baseado na proximidade do ponto de corte
                            $estaNoPontoCorte = $produto->estoque_atual <= $pontoCorte;
                            $estaAbaixoMinimo = $produto->estoque_atual < $estoqueMinimo;
                            
                            if ($estaNoPontoCorte) {
                                $corBarra = 'bg-red-500';
                                $corTexto = 'text-red-600';
                                $statusAlerta = 'URGENTE';
                            } elseif ($estaAbaixoMinimo) {
                                $corBarra = 'bg-yellow-500';
                                $corTexto = 'text-yellow-600';
                                $statusAlerta = 'ATENÇÃO';
                            } else {
                                $corBarra = 'bg-orange-500';
                                $corTexto = 'text-orange-600';
                                $statusAlerta = '';
                            }
                        ?>
                            <div class="p-4 bg-gray-50 rounded-lg border-2 <?= $estaNoPontoCorte ? 'border-red-300' : 'border-gray-200' ?> hover:bg-gray-100 transition">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2">
                                            <p class="font-semibold text-gray-900 truncate"><?= Html::encode($produto->nome) ?></p>
                                            <?php if ($estaNoPontoCorte): ?>
                                                <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded">URGENTE</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-xs text-gray-500">
                                            <?= $produto->categoria ? Html::encode($produto->categoria->nome) : 'Sem categoria' ?>
                                        </p>
                                    </div>
                                    <div class="text-right ml-4">
                                        <p class="text-lg font-bold <?= $corTexto ?>">
                                            <?= $produto->estoque_atual ?>
                                        </p>
                                        <p class="text-xs text-gray-500">unidades</p>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                                    <div class="<?= $corBarra ?> h-2 rounded-full" style="width: <?= $percentualEstoque ?>%"></div>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center space-x-4">
                                        <span class="text-gray-600">
                                            <span class="font-semibold">Atual:</span> <?= $produto->estoque_atual ?> un.
                                        </span>
                                        <span class="text-gray-500">
                                            <span class="font-semibold">Mínimo:</span> <?= $estoqueMinimo ?> un.
                                        </span>
                                        <?php if ($pontoCorte > 0): ?>
                                            <span class="text-red-600">
                                                <span class="font-semibold">Ponto Corte:</span> <?= $pontoCorte ?> un.
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?= Html::a('Resuprir', ['/vendas/compra/create', 'produto_id' => $produto->id], [
                                        'class' => 'bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded font-medium transition'
                                    ]) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        
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
    <?php endif; ?>

    <?php if ($ehAdministrador): ?>
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
    <?php endif; ?>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- Chart.js DataLabels Plugin -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($ehAdministrador && !empty($graficos)): ?>
    
    // Dados dos gráficos
    const dadosGraficos = <?= json_encode($graficos) ?>;
    
    // Gráfico: Vendas por Dia
    if (dadosGraficos.vendas_por_dia && dadosGraficos.vendas_por_dia.length > 0) {
        const ctxVendasDia = document.getElementById('graficoVendasDia');
        if (ctxVendasDia) {
            new Chart(ctxVendasDia, {
                type: 'line',
                data: {
                    labels: dadosGraficos.vendas_por_dia.map(item => {
                        const date = new Date(item.dia);
                        return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
                    }),
                    datasets: [{
                        label: 'Valor (R$)',
                        data: dadosGraficos.vendas_por_dia.map(item => parseFloat(item.valor_total)),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            formatter: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                            },
                            font: {
                                size: 9,
                                weight: 'bold'
                            },
                            color: '#1f2937',
                            display: function(context) {
                                // Mostra apenas labels para valores acima de 0
                                return context.dataset.data[context.dataIndex] > 0;
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }
    }
    
    // Gráfico: Vendas por Forma de Pagamento
    if (dadosGraficos.vendas_por_forma_pagamento && dadosGraficos.vendas_por_forma_pagamento.length > 0) {
        const ctxFormaPagamento = document.getElementById('graficoFormaPagamento');
        if (ctxFormaPagamento) {
            new Chart(ctxFormaPagamento, {
                type: 'doughnut',
                data: {
                    labels: dadosGraficos.vendas_por_forma_pagamento.map(item => item.forma_pagamento || 'Não informado'),
                    datasets: [{
                        data: dadosGraficos.vendas_por_forma_pagamento.map(item => parseFloat(item.valor_total)),
                        backgroundColor: [
                            'rgb(59, 130, 246)',
                            'rgb(16, 185, 129)',
                            'rgb(245, 158, 11)',
                            'rgb(239, 68, 68)',
                            'rgb(139, 92, 246)',
                            'rgb(236, 72, 153)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    return label + ': R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                        },
                        datalabels: {
                            formatter: function(value, context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percent = ((value / total) * 100).toFixed(1);
                                return percent + '%';
                            },
                            font: {
                                size: 11,
                                weight: 'bold'
                            },
                            color: '#ffffff'
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }
    }
    
    // Gráfico: Vendas por Mês
    if (dadosGraficos.vendas_por_mes && dadosGraficos.vendas_por_mes.length > 0) {
        const ctxVendasMes = document.getElementById('graficoVendasMes');
        if (ctxVendasMes) {
            new Chart(ctxVendasMes, {
                type: 'bar',
                data: {
                    labels: dadosGraficos.vendas_por_mes.map(item => {
                        const [ano, mes] = item.mes.split('-');
                        const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
                        return meses[parseInt(mes) - 1] + '/' + ano;
                    }),
                    datasets: [{
                        label: 'Valor (R$)',
                        data: dadosGraficos.vendas_por_mes.map(item => parseFloat(item.valor_total)),
                        backgroundColor: 'rgba(139, 92, 246, 0.8)',
                        borderColor: 'rgb(139, 92, 246)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'end',
                            formatter: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                            },
                            font: {
                                size: 10,
                                weight: 'bold'
                            },
                            color: '#1f2937'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }
    }
    
    // Gráfico: Parceladas vs À Vista
    if (dadosGraficos.vendas_parceladas_vs_vista && dadosGraficos.vendas_parceladas_vs_vista.length > 0) {
        const ctxParceladasVsVista = document.getElementById('graficoParceladasVsVista');
        if (ctxParceladasVsVista) {
            new Chart(ctxParceladasVsVista, {
                type: 'pie',
                data: {
                    labels: dadosGraficos.vendas_parceladas_vs_vista.map(item => item.tipo),
                    datasets: [{
                        data: dadosGraficos.vendas_parceladas_vs_vista.map(item => parseFloat(item.valor_total)),
                        backgroundColor: [
                            'rgb(59, 130, 246)',
                            'rgb(16, 185, 129)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    return label + ': R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                        },
                        datalabels: {
                            formatter: function(value, context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percent = ((value / total) * 100).toFixed(1);
                                return percent + '%';
                            },
                            font: {
                                size: 11,
                                weight: 'bold'
                            },
                            color: '#ffffff'
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }
    }
    
    // Gráfico: Produtos Mais Vendidos
    if (dadosGraficos.produtos_mais_vendidos && dadosGraficos.produtos_mais_vendidos.length > 0) {
        const ctxProdutosVendidos = document.getElementById('graficoProdutosVendidos');
        if (ctxProdutosVendidos) {
            // Limita a 10 produtos e ordena por quantidade
            const produtos = dadosGraficos.produtos_mais_vendidos.slice(0, 10);
            
            new Chart(ctxProdutosVendidos, {
                type: 'bar',
                data: {
                    labels: produtos.map(item => item.nome.length > 20 ? item.nome.substring(0, 20) + '...' : item.nome),
                    datasets: [{
                        label: 'Quantidade Vendida',
                        data: produtos.map(item => parseInt(item.quantidade_total)),
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderColor: 'rgb(16, 185, 129)',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'end',
                            formatter: function(value) {
                                return value;
                            },
                            font: {
                                size: 10,
                                weight: 'bold'
                            },
                            color: '#1f2937'
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }
    }
    
    <?php endif; ?>
});
</script>