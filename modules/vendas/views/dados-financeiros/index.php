<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\vendas\models\DadosFinanceiros;

$this->title = 'Precificação Inteligente';
$this->params['breadcrumbs'][] = ['label' => 'Vendas', 'url' => ['/vendas/inicio']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-4 px-3 sm:py-6 sm:px-4 lg:px-8">
    <div class="max-w-7xl mx-auto">
        
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm sm:shadow-md overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-purple-600 to-blue-600 px-4 py-3 sm:px-6 sm:py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <h2 class="text-xl sm:text-2xl font-bold text-white">Precificação Inteligente (Markup Divisor)</h2>
                    </div>
                    <?= Html::a('Configurar Global', ['global'], [
                        'class' => 'px-4 py-2 bg-white text-purple-600 font-semibold rounded-lg hover:bg-gray-100 transition-colors text-sm sm:text-base'
                    ]) ?>
                </div>
            </div>
        </div>

        <!-- Configuração Global -->
        <div class="bg-white rounded-lg shadow-sm sm:shadow-md overflow-hidden mb-6">
            <div class="px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Configuração Global da Loja
                </h3>
                <p class="text-sm text-gray-600 mt-1">Aplicada a todos os produtos que não possuem configuração específica</p>
            </div>
            <div class="px-4 py-4 sm:px-6 sm:py-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-700 mb-1">Taxas Fixas</div>
                        <div class="text-2xl font-bold text-blue-600"><?= number_format($configuracaoGlobal->taxa_fixa_percentual, 2, ',', '.') ?>%</div>
                    </div>
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-700 mb-1">Taxas Variáveis</div>
                        <div class="text-2xl font-bold text-orange-600"><?= number_format($configuracaoGlobal->taxa_variavel_percentual, 2, ',', '.') ?>%</div>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-700 mb-1">Lucro Líquido</div>
                        <div class="text-2xl font-bold text-green-600"><?= number_format($configuracaoGlobal->lucro_liquido_percentual, 2, ',', '.') ?>%</div>
                    </div>
                </div>
                <div class="mt-4">
                    <?= Html::a('Editar Configuração Global', ['global'], [
                        'class' => 'inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-colors text-sm sm:text-base'
                    ]) ?>
                </div>
            </div>
        </div>

        <!-- Configurações Específicas -->
        <div class="bg-white rounded-lg shadow-sm sm:shadow-md overflow-hidden">
            <div class="px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        Configurações Específicas por Produto
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">Produtos com configurações personalizadas</p>
                </div>
            </div>
            
            <?php if (empty($configuracoesEspecificas)): ?>
                <div class="px-4 py-8 sm:px-6 sm:py-10 text-center">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <p class="text-gray-500 text-sm sm:text-base">Nenhuma configuração específica encontrada.</p>
                    <p class="text-gray-400 text-xs sm:text-sm mt-2">As configurações específicas são criadas automaticamente ao editar um produto e marcar "Usar configuração específica".</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produto</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Taxa Fixa</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Taxa Variável</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Lucro Líquido</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($configuracoesEspecificas as $config): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= Html::encode($config->produto->nome ?? 'Produto não encontrado') ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="text-sm text-blue-600 font-semibold"><?= number_format($config->taxa_fixa_percentual, 2, ',', '.') ?>%</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="text-sm text-orange-600 font-semibold"><?= number_format($config->taxa_variavel_percentual, 2, ',', '.') ?>%</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="text-sm text-green-600 font-semibold"><?= number_format($config->lucro_liquido_percentual, 2, ',', '.') ?>%</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium">
                                        <?= Html::a('Editar', ['produto', 'produto_id' => $config->produto_id], [
                                            'class' => 'text-purple-600 hover:text-purple-900 mr-3'
                                        ]) ?>
                                        <?= Html::a('Remover', ['delete', 'id' => $config->id], [
                                            'class' => 'text-red-600 hover:text-red-900',
                                            'data' => [
                                                'confirm' => 'Tem certeza que deseja remover esta configuração específica? O produto passará a usar a configuração global.',
                                                'method' => 'post',
                                            ],
                                        ]) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Botão Voltar -->
        <div class="mt-6">
            <?= Html::a('← Voltar ao Painel', ['/vendas/inicio'], [
                'class' => 'inline-flex items-center px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold rounded-lg transition-colors text-sm sm:text-base'
            ]) ?>
        </div>

    </div>
</div>

