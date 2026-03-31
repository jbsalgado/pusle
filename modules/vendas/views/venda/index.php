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
                                $listaStatus = \app\modules\vendas\models\StatusVenda::find()->all();

                                $bgClass = 'bg-blue-100 text-blue-800 border-blue-200';
                                if ($status === 'QUITADA') $bgClass = 'bg-green-100 text-green-800 border-green-200';
                                if ($status === 'CANCELADA') $bgClass = 'bg-red-100 text-red-800 border-red-200';
                                if ($status === 'ORCAMENTO') $bgClass = 'bg-amber-100 text-amber-800 border-amber-200';

                                // Se já estiver QUITADA ou CANCELADA, não permite mudar (apenas exibe label)
                                if (in_array($status, ['QUITADA', 'CANCELADA'])) {
                                    return "<div class='text-center'>
                                                <span class='px-3 py-1.5 text-xs font-black rounded-lg border-2 $bgClass uppercase tracking-widest'>
                                                    $status
                                                </span>
                                            </div>";
                                }

                                $html = "<div class='relative inline-block w-full min-w-[140px]'>";
                                $html .= "<select data-venda-id='{$model->id}'
                                            class='js-venda-status-select w-full appearance-none px-3 py-1.5 text-xs font-bold rounded-lg border-2 transition-all cursor-pointer $bgClass focus:outline-none focus:ring-2 focus:ring-offset-1'>";

                                foreach ($listaStatus as $s) {
                                    $selected = ($s->codigo === $status) ? 'selected' : '';
                                    $html .= "<option value='{$s->codigo}' $selected>{$s->descricao}</option>";
                                }

                                $html .= "</select>";
                                // Ícone de seta customizado
                                $html .= "<div class='pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-current opacity-60'>
                                            <svg class='h-3 w-3 fill-current' viewBox='0 0 20 20'><path d='M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z'/></svg>
                                          </div>";
                                $html .= "</div>";

                                return $html;
                            },
                            'contentOptions' => ['class' => 'px-6 py-4'],
                        ],
                        [
                            'label' => 'Ações',
                            'format' => 'raw',
                            'value' => function ($model) {
                                return "<button data-venda-id='{$model->id}' class='js-venda-imprimir-btn inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition-colors shadow-sm'>
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
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-2xl hover:border-indigo-100 transition-all duration-500 group flex flex-col h-full">
                        <!-- Header do Card -->
                        <div class="px-5 py-4 border-b border-gray-50 bg-gray-50/30 flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-[9px] font-black text-indigo-400 tracking-widest uppercase italic">Pulse System</span>
                                <span class="text-[11px] font-black text-gray-900 tracking-tighter uppercase">VENDA #<?= substr($venda->id, 0, 8) ?></span>
                            </div>
                            <?php
                            $status = $venda->status_venda_codigo;
                            $statusClass = 'bg-blue-50 text-blue-700 border-blue-200';
                            if ($status === 'QUITADA') $statusClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            if ($status === 'CANCELADA') $statusClass = 'bg-rose-50 text-rose-700 border-rose-200';
                            if ($status === 'ORCAMENTO') $statusClass = 'bg-amber-50 text-amber-700 border-amber-200';

                            $listaStatus = \app\modules\vendas\models\StatusVenda::find()->all();
                            ?>
                            <div class="relative inline-block w-32">
                                <?php if (in_array($status, ['QUITADA', 'CANCELADA'])): ?>
                                    <span class="block w-full text-center px-3 py-1.5 text-[10px] font-black rounded-full border-2 <?= $statusClass ?> uppercase tracking-widest">
                                        <?= $status ?>
                                    </span>
                                <?php else: ?>
                                    <select data-venda-id="<?= $venda->id ?>"
                                        class='js-venda-status-select w-full appearance-none px-3 py-1.5 text-[10px] font-black rounded-full border-2 transition-all cursor-pointer <?= $statusClass ?> focus:outline-none focus:ring-2 focus:ring-indigo-500/20'>
                                        <?php foreach ($listaStatus as $s): ?>
                                            <option value="<?= $s->codigo ?>" <?= $s->codigo === $status ? 'selected' : '' ?>><?= $s->descricao ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class='pointer-events-none absolute inset-y-0 right-0 flex items-center px-1.5 text-current opacity-40 uppercase'>
                                        <svg class='h-2.5 w-2.5 fill-current' viewBox='0 0 20 20'>
                                            <path d='M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z' />
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Corpo do Card -->
                        <div class="p-6 flex-grow space-y-5">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Cliente</span>
                                    <span class="text-[10px] bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-bold uppercase"><?= count($venda->itens) ?> <?= count($venda->itens) === 1 ? 'Item' : 'Itens' ?></span>
                                </div>
                                <h3 class="text-lg font-black text-gray-900 line-clamp-1 group-hover:text-indigo-600 transition-colors">
                                    <?= $venda->cliente ? ($venda->cliente->nome_completo ?? $venda->cliente->nome) : 'Consumidor Final' ?>
                                </h3>
                                <div class="flex items-center text-[10px] text-indigo-300 font-black mt-1 uppercase tracking-widest">
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <?= date('d M, Y \à\s H:i', strtotime($venda->data_criacao)) ?>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                                <div class="flex flex-col">
                                    <span class="text-[9px] text-gray-400 font-black uppercase tracking-widest leading-none mb-1.5">Méto. Pagamento</span>
                                    <div class="flex items-center">
                                        <div class="w-1.5 h-1.5 rounded-full bg-indigo-500 mr-2"></div>
                                        <span class="text-xs text-gray-900 font-black uppercase tracking-tight"><?= $venda->formaPagamento ? $venda->formaPagamento->nome : 'N/A' ?></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-[9px] text-indigo-400 font-black uppercase tracking-widest leading-none mb-1.5">Total Líquido</p>
                                    <p class="text-2xl font-black text-gray-900 tabular-nums tracking-tighter">R$ <?= number_format($venda->valor_total, 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer do Card -->
                        <div class="px-5 py-4 bg-gray-50/30 flex items-center justify-between border-t border-gray-50/50">
                            <a href="<?= Url::to(['view', 'id' => $venda->id]) ?>" class="text-[10px] font-black text-gray-400 hover:text-indigo-600 uppercase tracking-widest flex items-center transition-all">
                                Detalhes
                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                            <button data-venda-id="<?= $venda->id ?>" class="js-venda-imprimir-btn inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-black text-white text-[10px] font-black rounded-2xl transition-all shadow-lg hover:shadow-indigo-200 active:scale-95 uppercase tracking-widest">
                                <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2-2v4h10z" />
                                </svg>
                                Imprimir Cupom
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Paginação -->
        <div class="mt-12 flex justify-center">
            <?= \yii\widgets\LinkPager::widget([
                'pagination' => $dataProvider->pagination,
                'options' => ['class' => 'flex items-center space-x-1.5'],
                'linkOptions' => ['class' => 'px-4 py-2 bg-white border-2 border-gray-100 rounded-2xl hover:border-indigo-600 hover:text-indigo-600 transition-all text-xs font-black text-gray-500 uppercase tracking-widest'],
                'activePageCssClass' => 'bg-indigo-600 border-indigo-600 text-white',
                'disabledPageCssClass' => 'opacity-30 cursor-not-allowed',
                'firstPageLabel' => '<<',
                'lastPageLabel' => '>>',
                'prevPageLabel' => '<',
                'nextPageLabel' => '>',
            ]) ?>
        </div>
    </div>
</div>

<!-- Modal Comprovante -->
<?php
// Registra o script necessário
$this->registerJsFile('@web/js/venda-list.js', ['depends' => [\yii\web\JqueryAsset::class]]);
?>
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