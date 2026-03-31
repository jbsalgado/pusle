<?php

/**
 * View: Detalhes da Venda
 * @var yii\web\View $this
 * @var app\modules\vendas\models\Venda $model
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Detalhes da Venda #' . substr($model->id, 0, 8);
?>

<div class="min-h-screen bg-gray-50 pb-12">
    <!-- Cabeçalho Fixo/Sticky -->
    <div class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-10">
        <div class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="<?= Url::to(['index']) ?>" class="p-2 hover:bg-gray-100 rounded-full transition-colors text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <div>
                        <div class="flex items-center space-x-3">
                            <h1 class="text-xl font-black text-gray-900 uppercase tracking-tight">Venda #<?= substr($model->id, 0, 8) ?></h1>
                            <?php
                            $status = $model->status_venda_codigo;
                            $statusClass = 'bg-blue-100 text-blue-800 border-blue-200';
                            if ($status === 'QUITADA') $statusClass = 'bg-emerald-100 text-emerald-800 border-emerald-200';
                            if ($status === 'CANCELADA') $statusClass = 'bg-rose-100 text-rose-800 border-rose-200';
                            if ($status === 'ORCAMENTO') $statusClass = 'bg-amber-100 text-amber-800 border-amber-200';
                            ?>
                            <span class="<?= $statusClass ?> px-3 py-1 rounded-full text-[10px] font-black uppercase border tracking-widest">
                                <?= $model->status_venda_codigo ?>
                            </span>
                        </div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mt-0.5">Realizada em <?= date('d/m/Y H:i', strtotime($model->data_criacao)) ?></p>
                    </div>
                </div>

                <button data-venda-id="<?= $model->id ?>" class="js-venda-imprimir-btn inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-black text-white text-xs font-black rounded-xl transition-all shadow-lg uppercase tracking-widest active:scale-95">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2-2v4h10z" />
                    </svg>
                    Imprimir
                </button>
            </div>
        </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="px-4 sm:px-6 lg:px-8 py-8 max-w-7xl mx-auto space-y-8">

        <!-- Cards de Resumo -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Bloco Cliente -->
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <div>
                    <span class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.2em] mb-4 block">Dados do Cliente</span>
                    <h2 class="text-xl font-black text-gray-900 mb-1">
                        <?= $model->cliente ? ($model->cliente->nome_completo ?? $model->cliente->nome) : 'Consumidor Final' ?>
                    </h2>
                    <?php if ($model->cliente): ?>
                        <p class="text-xs text-gray-500 font-medium"><?= $model->cliente->cpf ?? 'CPF Não informado' ?></p>
                        <div class="mt-4 space-y-1">
                            <p class="text-xs text-gray-600 flex items-center">
                                <svg class="w-3 h-3 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <?= $model->cliente->telefone ?? 'Sem telefone' ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bloco Pagamento -->
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <div>
                    <span class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.2em] mb-4 block">Informações Financeiras</span>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="p-3 bg-indigo-50 rounded-2xl text-indigo-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-tight">Forma de Pagamento</p>
                            <p class="text-lg font-black text-gray-900 uppercase italic tracking-tighter">
                                <?= $model->formaPagamento ? $model->formaPagamento->nome : 'N/A' ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bloco Total -->
            <div class="bg-indigo-600 p-6 rounded-3xl border border-indigo-700 shadow-xl flex flex-col justify-center text-white relative overflow-hidden">
                <svg class="absolute -right-4 -bottom-4 w-32 h-32 text-indigo-500 opacity-20" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.242.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.184a4.535 4.535 0 00-1.676.662C6.602 13.234 6 14.009 6 15c0 .99.602 1.765 1.324 2.246A4.535 4.535 0 009 17.908V18a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 16.766 14 15.991 14 15c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 12.092V10.908a4.535 4.535 0 001.676-.662C13.398 9.766 14 8.991 14 8c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 5.092V5z" clip-rule="evenodd" />
                </svg>
                <span class="text-[9px] font-black uppercase tracking-[0.3em] mb-1 opacity-80">Valor Total Efetivado</span>
                <p class="text-3xl font-black tabular-nums tracking-tighter">R$ <?= number_format($model->valor_total, 2, ',', '.') ?></p>
            </div>
        </div>

        <!-- Tabela de Itens -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-50 flex items-center justify-between">
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest flex items-center">
                    <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    Itens da Venda
                </h3>
                <span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-[10px] font-black uppercase">
                    <?= count($model->itens) ?> <?= count($model->itens) === 1 ? 'Produto' : 'Produtos' ?>
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Produto</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Quant.</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Unitário</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($model->itens as $item): ?>
                            <tr class="hover:bg-gray-50/30 transition-colors">
                                <td class="px-6 py-5">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center mr-3 text-gray-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-gray-900 leading-tight uppercase tracking-tight">
                                                <?= $item->produto ? $item->produto->nome : 'Produto Não Identificado' ?>
                                            </p>
                                            <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">REF: <?= $item->produto ? ($item->produto->codigo_referencia ?? 'N/A') : 'N/A' ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <span class="text-sm font-bold text-gray-700 bg-gray-50 px-2 py-1 rounded-md">
                                        <?= (float)$item->quantidade ?>
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-right text-sm font-medium text-gray-600 tabular-nums">
                                    R$ <?= number_format($item->preco_unitario_venda, 2, ',', '.') ?>
                                </td>
                                <td class="px-6 py-5 text-right text-sm font-black text-gray-900 tabular-nums">
                                    R$ <?= number_format($item->valor_total_item, 2, ',', '.') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50/20">
                            <td colspan="3" class="px-6 py-6 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Valor Total Líquido</td>
                            <td class="px-6 py-6 text-right text-xl font-black text-indigo-600 tabular-nums tracking-tighter">
                                R$ <?= number_format($model->valor_total, 2, ',', '.') ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Informações Adicionais -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4 block">Vendedor Responsável</span>
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center font-black text-sm uppercase mr-3">
                        <?= substr($model->vendedor->nome_completo ?? 'S', 0, 1) ?>
                    </div>
                    <div>
                        <p class="text-sm font-black text-gray-900 uppercase italic tracking-tight">
                            <?= $model->vendedor ? $model->vendedor->nome_completo : 'Administrador' ?>
                        </p>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mt-0.5">Operador do Sistema</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center justify-between group cursor-pointer hover:border-indigo-200 transition-colors" onclick="history.back()">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center mr-3 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="text-sm font-black text-gray-500 uppercase tracking-widest group-hover:text-indigo-600 transition-colors">Voltar para Listagem</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Registra scripts necessários para impressão (reaproveitando do index se necessário)
$this->registerJsFile('@web/js/venda-list.js', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerJs("window.BASE_URL = '" . Url::base(true) . "';", \yii\web\View::POS_HEAD);
?>