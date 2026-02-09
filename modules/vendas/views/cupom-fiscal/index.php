<?php

/**
 * View: Listagem de Cupons Fiscais (NFCe/NFe)
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use app\modules\vendas\models\CupomFiscal;

$this->title = 'Central Fiscal - Cupons Emitidos';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Gerenciamento Fiscal 📑</h1>
            <p class="text-gray-500">Histórico de NFe/NFCe emitidas, downloads e status SEFAZ.</p>
        </div>
        <div class="flex space-x-2">
            <a href="<?= Url::to(['/vendas/dashboard/index']) ?>" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition font-medium">Voltar ao Dashboard</a>
        </div>
    </div>

    <!-- Grid -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-6">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'min-w-full divide-y divide-gray-200'],
                'headerRowOptions' => ['class' => 'bg-gray-50'],
                'summary' => '<div class="text-sm text-gray-500 mb-4">Mostrando {begin} - {end} de {totalCount} cupons</div>',
                'columns' => [
                    [
                        'attribute' => 'numero',
                        'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'],
                    ],
                    [
                        'attribute' => 'modelo',
                        'value' => function ($model) {
                            return $model->modelo == '65' ? 'NFCe (65)' : 'NFe (55)';
                        },
                        'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-500'],
                    ],
                    [
                        'attribute' => 'chave_acesso',
                        'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-xs font-mono text-gray-500'],
                    ],
                    [
                        'attribute' => 'data_emissao',
                        'format' => ['datetime', 'php:d/m/Y H:i'],
                        'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-500'],
                    ],
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $colors = [
                                CupomFiscal::STATUS_AUTORIZADA => 'bg-green-100 text-green-800',
                                CupomFiscal::STATUS_CANCELADA => 'bg-red-100 text-red-800',
                                CupomFiscal::STATUS_ERRO => 'bg-orange-100 text-orange-800',
                                CupomFiscal::STATUS_PENDENTE => 'bg-blue-100 text-blue-800',
                            ];
                            $class = $colors[$model->status] ?? 'bg-gray-100 text-gray-800';
                            return '<span class="px-2.5 py-0.5 rounded-full text-xs font-medium ' . $class . '">' . $model->status . '</span>';
                        },
                        'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view} {pdf} {xml}',
                        'buttons' => [
                            'view' => function ($url, $model) {
                                return Html::a(
                                    '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>',
                                    ['view', 'id' => $model->id],
                                    ['class' => 'text-indigo-600 hover:text-indigo-900 mx-1', 'title' => 'Ver Detalhes']
                                );
                            },
                            'pdf' => function ($url, $model) {
                                return Html::a(
                                    '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path d="M9 11l3 3L15 11"/><path d="M12 14V3"/></svg>',
                                    ['pdf', 'id' => $model->id],
                                    ['class' => 'text-red-600 hover:text-red-900 mx-1', 'title' => 'Ver DANFE (PDF)', 'target' => '_blank']
                                );
                            },
                            'xml' => function ($url, $model) {
                                return Html::a(
                                    '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>',
                                    ['xml', 'id' => $model->id],
                                    ['class' => 'text-green-600 hover:text-green-900 mx-1', 'title' => 'Baixar XML']
                                );
                            },
                        ],
                        'header' => 'Ações',
                        'headerOptions' => ['class' => 'px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-right text-sm font-medium'],
                    ],
                ],
                'pager' => [
                    'options' => ['class' => 'bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6'],
                    'linkContainerOptions' => ['class' => 'relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition'],
                ],
            ]); ?>
        </div>
    </div>
</div>