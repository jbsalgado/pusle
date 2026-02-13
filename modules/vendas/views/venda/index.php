<?php

/**
 * View: Listagem de Vendas - Grid e Card
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

$this->title = 'Vendas Efetivadas';
?>

<div class="min-h-screen bg-gray-50 pb-12">
    <!-- Cabeçalho -->
    <div class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-10">
        <div class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto py-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <a href="<?= Url::to(['/vendas/inicio/index']) ?>" class="text-gray-500 hover:text-gray-700 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Vendas Efetivadas</h1>
                        <p class="text-sm text-gray-600 mt-0.5">Histórico completo de vendas da sua loja</p>
                    </div>
                </div>

                <!-- Toggle de Visualização -->
                <div class="flex bg-gray-100 p-1 rounded-lg self-start">
                    <button id="btn-view-grid"
                        class="flex items-center px-4 py-1.5 rounded-md text-sm font-medium transition-all duration-200 active">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        Grid
                    </button>
                    <button id="btn-view-card"
                        class="flex items-center px-4 py-1.5 rounded-md text-sm font-medium transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Cards
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Conteúdo -->
    <div class="px-4 sm:px-6 lg:px-8 py-8 max-w-7xl mx-auto">

        <!-- View Grid (Tabela) -->
        <div id="view-grid" class="view-container">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'summary' => false,
                    'layout' => '{items}',
                    'tableOptions' => ['class' => 'min-w-full divide-y divide-gray-200'],
                    'headerRowOptions' => ['class' => 'bg-gray-50'],
                    'options' => ['class' => 'overflow-x-auto'],
                    'columns' => [
                        [
                            'attribute' => 'id',
                            'label' => 'Nº Venda',
                            'value' => function ($model) {
                                return '#' . substr($model->id, 0, 8);
                            },
                            'contentOptions' => ['class' => 'px-6 py-4 text-sm font-medium text-gray-900'],
                        ],
                        [
                            'attribute' => 'data_criacao',
                            'label' => 'Data',
                            'value' => function ($model) {
                                return date('d/m/Y H:i', strtotime($model->data_criacao));
                            },
                            'contentOptions' => ['class' => 'px-6 py-4 text-sm text-gray-500'],
                        ],
                        [
                            'attribute' => 'cliente_id',
                            'label' => 'Cliente',
                            'value' => function ($model) {
                                return $model->cliente ? ($model->cliente->nome_completo ?? $model->cliente->nome) : 'Não Informado';
                            },
                            'contentOptions' => ['class' => 'px-6 py-4 text-sm text-gray-700'],
                        ],
                        [
                            'attribute' => 'valor_total',
                            'label' => 'Valor',
                            'format' => ['currency', 'BRL'],
                            'contentOptions' => ['class' => 'px-6 py-4 text-sm font-bold text-gray-900'],
                        ],
                        [
                            'label' => 'Status',
                            'format' => 'raw',
                            'value' => function ($model) {
                                $status = $model->status_venda_codigo;
                                $class = 'bg-blue-100 text-blue-800';
                                if ($status === 'QUITADA') $class = 'bg-green-100 text-green-800';
                                if ($status === 'CANCELADA') $class = 'bg-red-100 text-red-800';
                                return "<span class='px-2 py-1 text-xs font-semibold rounded-full $class'>$status</span>";
                            },
                            'contentOptions' => ['class' => 'px-6 py-4 text-sm'],
                        ],
                        [
                            'label' => 'Ações',
                            'format' => 'raw',
                            'value' => function ($model) {
                                return "<button onclick='imprimirVenda(\"{$model->id}\")' class='inline-flex items-center px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium rounded-md transition-colors shadow-sm'>
                                    <svg class='w-3.5 h-3.5 mr-1.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2-2v4h10z'/>
                                    </svg>
                                    Imprimir
                                </button>";
                            },
                            'contentOptions' => ['class' => 'px-6 py-4 text-right'],
                        ],
                    ],
                ]); ?>
            </div>
        </div>

        <!-- View Card (Cards) -->
        <div id="view-card" class="view-container hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($dataProvider->getModels() as $venda): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-all duration-300">
                        <!-- Header do Card -->
                        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-500 tracking-wider">VENDA #<?= substr($venda->id, 0, 8) ?></span>
                            <?php
                            $status = $venda->status_venda_codigo;
                            $statusClass = 'bg-blue-100 text-blue-800';
                            if ($status === 'QUITADA') $statusClass = 'bg-green-100 text-green-800';
                            if ($status === 'CANCELADA') $statusClass = 'bg-red-100 text-red-800';
                            ?>
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full cursor-default <?= $statusClass ?>"><?= $status ?></span>
                        </div>

                        <!-- Corpo do Card -->
                        <div class="p-5 space-y-4">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 line-clamp-1">
                                    <?= $venda->cliente ? ($venda->cliente->nome_completo ?? $venda->cliente->nome) : 'Venda Direta / Não Informado' ?>
                                </h3>
                                <div class="flex items-center text-xs text-gray-500 mt-1">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <?= date('d/m/Y H:i', strtotime($venda->data_criacao)) ?>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-2">
                                <span class="text-xs text-gray-500 font-medium">Forma: <?= $venda->formaPagamento ? $venda->formaPagamento->nome : 'N/A' ?></span>
                                <div class="text-right">
                                    <p class="text-xs text-gray-400 font-medium uppercase tracking-tight">Total</p>
                                    <p class="text-lg font-black text-gray-900 leading-none">R$ <?= number_format($venda->valor_total, 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer do Card -->
                        <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end">
                            <button onclick='imprimirVenda("<?= $venda->id ?>")' class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold rounded-lg transition-colors shadow-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2-2v4h10z" />
                                </svg>
                                Imprimir Recibo
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Paginação -->
        <div class="mt-8 flex justify-center">
            <?= \yii\widgets\LinkPager::widget([
                'pagination' => $dataProvider->pagination,
                'options' => ['class' => 'flex space-x-2'],
                'linkOptions' => ['class' => 'px-3 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium text-gray-700'],
                'activePageCssClass' => 'bg-purple-600 border-purple-600 text-white',
                'disabledPageCssClass' => 'opacity-50 cursor-not-allowed',
                'firstPageLabel' => '<<',
                'lastPageLabel' => '>>',
                'prevPageLabel' => '<',
                'nextPageLabel' => '>',
            ]) ?>
        </div>
    </div>
</div>

<!-- Modal Comprovante -->
<div id="modal-comprovante" class="fixed inset-0 z-[100] hidden overflow-y-auto">
    <!-- Overlay -->
    <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity"></div>

    <!-- Modal Content -->
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
            <!-- Header Modal -->
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-b border-gray-100">
                <div class="flex items-center">
                    <div class="bg-purple-100 rounded-full p-2 mr-3">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2-2v4h10z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Comprovante de Venda</h3>
                </div>
                <button onclick="fecharModalComprovante()" class="text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500 rounded-lg transition-colors p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Comprovante Renderizado -->
            <div id="comprovante-container" class="px-6 py-8 max-h-[70vh] overflow-y-auto bg-white">
                <!-- Conteúdo via JS -->
            </div>

            <!-- Ações Modal -->
            <div class="bg-gray-50 px-6 py-4 flex flex-col sm:flex-row-reverse gap-3 border-t border-gray-100">
                <button type="button" onclick="imprimirNormal()" class="inline-flex w-full sm:w-auto justify-center rounded-xl bg-purple-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-purple-700 transition-all focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2-2v4h10z" />
                    </svg>
                    Imprimir Normal
                </button>
                <button type="button" onclick="imprimirTermica()" class="inline-flex w-full sm:w-auto justify-center rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700 transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h8a1 1 0 001-1zM11 10a1 1 0 11-2 0 1 1 0 012 0z" />
                    </svg>
                    Imprimir Térmica
                </button>
                <button type="button" onclick="fecharModalComprovante()" class="inline-flex w-full sm:w-auto justify-center rounded-xl bg-white px-6 py-2.5 text-sm font-bold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition-all sm:ml-auto">
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// Passa URL base para o JavaScript
$this->registerJs("window.BASE_URL = '" . Url::base(true) . "';", \yii\web\View::POS_HEAD);
?>