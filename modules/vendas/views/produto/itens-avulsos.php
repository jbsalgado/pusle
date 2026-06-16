<?php

/**
 * View: Relatório de Itens Avulsos / Pendentes de Cadastro
 * @var yii\web\View $this
 * @var yii\data\ArrayDataProvider $dataProvider
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

$this->title = 'Itens Pendentes de Cadastro';
$this->params['breadcrumbs'][] = ['label' => 'Produtos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Função auxiliar para formatar moeda
function formatarMoedaLocal($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

?>

<div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        
        <!-- Cabeçalho -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                    <?= Html::encode($this->title) ?>
                </h1>
                <p class="mt-2 text-sm text-gray-600 max-w-2xl">
                    Estes itens foram vendidos manualmente na Venda Direta (PWA). 
                    Utilize esta ferramenta para identificar produtos recorrentes e cadastrá-los formalmente para controle de estoque.
                </p>
            </div>
            <div class="flex gap-3">
                <?= Html::a(
                    '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>Imprimir Pendências',
                    ['imprimir-itens-avulsos'],
                    ['class' => 'inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500', 'target' => '_blank']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                    ['inicio/index'],
                    ['class' => 'inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500']
                ) ?>
            </div>
        </div>

        <!-- Alerta de Recomendação -->
        <div class="mb-8 bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r-lg shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-amber-700 font-medium">
                        Dica do Gestor: Itens com alta frequência de venda devem ser cadastrados imediatamente para garantir a precisão do seu fluxo de caixa e inventário.
                    </p>
                </div>
            </div>
        </div>

        <!-- Tabela Progressiva -->
        <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Descrição Manual</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Vendas</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Qtd. Total</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Receita Gerada</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Última Venda</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100 italic-last-child">
                        <?php if ($dataProvider->getCount() === 0): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 font-medium">
                                    Excelente! Não há itens avulsos pendentes de cadastro no momento.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($dataProvider->getModels() as $model): ?>
                            <tr class="hover:bg-blue-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900"><?= Html::encode($model['nome_item_manual']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?= $model['total_vendas'] > 5 ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' ?>">
                                        <?= $model['total_vendas'] ?>x
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-600">
                                    <?= number_format($model['total_quantidade'], 2, ',', '.') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-green-600">
                                    <?= formatarMoedaLocal($model['total_receita']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-xs text-gray-500">
                                    <?= date('d/m/Y H:i', strtotime($model['ultima_venda'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center flex justify-center gap-2">
                                    <?= Html::a(
                                        'Cadastrar Produto',
                                        ['create', 'nome_manual' => $model['nome_item_manual']],
                                        ['class' => 'inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-bold rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all']
                                    ) ?>
                                    <?= Html::a(
                                        '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Resolver',
                                        ['resolver-item-avulso', 'nome_manual' => $model['nome_item_manual']],
                                        [
                                            'class' => 'inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-bold rounded-lg shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all',
                                            'data' => [
                                                'confirm' => 'Marcar este item como resolvido? Ele não aparecerá mais nesta lista, mas o histórico de vendas será mantido.',
                                                'method' => 'post',
                                            ],
                                        ]
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rodapé da Tabela -->
        <?php if ($dataProvider->pagination): ?>
            <div class="mt-4">
                <?= \yii\widgets\LinkPager::widget([
                    'pagination' => $dataProvider->pagination,
                    'options' => ['class' => 'flex justify-center gap-2'],
                    'linkContainerOptions' => ['class' => ''],
                    'linkOptions' => ['class' => 'px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50'],
                ]) ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<style>
    .italic-last-child tr:last-child {
        border-bottom: none;
    }
</style>
