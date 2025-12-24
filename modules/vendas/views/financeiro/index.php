<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Análise Financeira Mensal';
$this->params['breadcrumbs'][] = ['label' => 'Vendas', 'url' => ['/vendas/inicio']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="financeiro-mensal-index max-w-7xl mx-auto py-6">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Histórico Financeiro</h1>
        <div class="flex gap-2">
            <?= Html::a('Configuração Global', ['/vendas/dados-financeiros/global'], ['class' => 'bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-lg']) ?>
            <?= Html::a('+ Novo Mês', ['create'], ['class' => 'bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg']) ?>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'min-w-full divide-y divide-gray-200'],
            'headerRowOptions' => ['class' => 'bg-gray-50'],
            'rowOptions' => ['class' => 'hover:bg-gray-50'],
            'summary' => '',
            'columns' => [
                [
                    'attribute' => 'mes_referencia',
                    'format' => ['date', 'MM/yyyy'],
                    'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'],
                    'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'],
                ],
                [
                    'attribute' => 'faturamento_total',
                    'format' => ['currency'],
                    'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'],
                    'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-500'],
                ],
                [
                    'label' => 'Indicadores Reais',
                    'format' => 'raw',
                    'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'],
                    'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm'],
                    'value' => function ($model) {
                        $ind = $model->getIndicadores();
                        return "
                            <div class='text-xs'>
                                <span class='block text-blue-600'>Fixas: " . number_format($ind['taxa_fixa_percentual'], 2, ',', '.') . "%</span>
                                <span class='block text-orange-600'>Variáveis: " . number_format($ind['taxa_variavel_percentual'], 2, ',', '.') . "%</span>
                                <span class='block " . ($ind['margem_lucro_real'] > 0 ? 'text-green-600' : 'text-red-600') . " font-bold'>
                                    Lucro: " . number_format($ind['margem_lucro_real'], 2, ',', '.') . "%
                                </span>
                            </div>
                        ";
                    }
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'header' => 'Ações',
                    'template' => '{update} {delete}',
                    'headerOptions' => ['class' => 'px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider'],
                    'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-center text-sm font-medium'],
                    'buttons' => [
                        'update' => function ($url, $model) {
                            return Html::a('Editar', $url, ['class' => 'text-blue-600 hover:text-blue-900 mr-3']);
                        },
                        'delete' => function ($url, $model) {
                            return Html::a('Excluir', $url, [
                                'class' => 'text-red-600 hover:text-red-900',
                                'data-confirm' => 'Tem certeza?',
                                'data-method' => 'post',
                            ]);
                        },
                    ],
                ],
            ],
        ]); ?>
    </div>
</div>