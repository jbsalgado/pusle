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

        <?php
        $saasLog = $model->saasFinancialLog;
        $mpPaymentId = $model->getMpPaymentId();
        $temMercadoPago = !empty($saasLog) || !empty($mpPaymentId);

        if ($temMercadoPago):
            $totalBruto = $saasLog ? (float)$saasLog->total_amount : (float)$model->valor_total;
            $taxaSaaS = $saasLog ? (float)$saasLog->platform_fee : 0.0;
            $liquidoLojista = max(0, $totalBruto - $taxaSaaS);
            $percentualTaxa = $totalBruto > 0 ? round(($taxaSaaS / $totalBruto) * 100, 2) : 0;
            $statusMp = $saasLog ? $saasLog->status : ($model->status_venda_codigo === 'CANCELADA' ? 'refunded' : 'approved');
            $estaEstornado = in_array(strtolower($statusMp), ['refunded', 'charged_back']) || $model->status_venda_codigo === 'CANCELADA';
        ?>
        <!-- Bloco Detalhamento Split Mercado Pago & SaaS -->
        <div class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white rounded-3xl p-6 sm:p-8 shadow-2xl border border-indigo-500/20 relative overflow-hidden">
            <!-- Glow background effect -->
            <div class="absolute -top-24 -right-24 w-72 h-72 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-cyan-500/15 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 space-y-6">
                <!-- Header do Extrato -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-indigo-500/20 pb-5">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 rounded-2xl bg-cyan-500/20 border border-cyan-400/30 flex items-center justify-center text-cyan-300 shadow-inner">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="5" width="20" height="14" rx="2" />
                                <line x1="2" y1="10" x2="22" y2="10" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="text-base font-black uppercase tracking-wider text-white">Extrato de Split Mercado Pago</h3>
                                <span class="bg-cyan-400/20 text-cyan-300 text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-widest border border-cyan-400/30">SaaS Split</span>
                            </div>
                            <p class="text-xs text-indigo-200/70 mt-0.5">Divisão e liquidação financeira automatizada da transação</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <?php if ($estaEstornado): ?>
                            <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-black bg-rose-500/20 text-rose-300 border border-rose-500/40 uppercase tracking-widest">
                                <span class="w-2 h-2 rounded-full bg-rose-400 mr-2"></span> Estornado / Cancelado
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 uppercase tracking-widest shadow-sm">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 mr-2 animate-pulse"></span> Pagamento Aprovado
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($mpPaymentId)): ?>
                            <span class="text-xs font-mono bg-white/10 px-3 py-1.5 rounded-xl text-indigo-200 border border-white/10" title="ID da Transação Mercado Pago">
                                MP #<?= Html::encode($mpPaymentId) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Grid de Valores do Split -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Bruto -->
                    <div class="bg-white/5 border border-white/10 rounded-2xl p-4 backdrop-blur-sm">
                        <span class="text-[10px] font-black uppercase tracking-widest text-indigo-300/80 block mb-1">Valor Bruto Transacionado</span>
                        <p class="text-2xl font-black text-white tabular-nums tracking-tight">R$ <?= number_format($totalBruto, 2, ',', '.') ?></p>
                        <p class="text-[10px] text-gray-400 mt-1">Pago pelo cliente no checkout</p>
                    </div>

                    <!-- Split Taxa SaaS -->
                    <div class="bg-rose-500/10 border border-rose-500/20 rounded-2xl p-4 backdrop-blur-sm">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-black uppercase tracking-widest text-rose-300/90 block">Comissão Plataforma (SaaS)</span>
                            <?php if ($percentualTaxa > 0): ?>
                                <span class="text-[10px] font-bold text-rose-300 bg-rose-500/20 px-1.5 py-0.5 rounded"><?= $percentualTaxa ?>%</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-2xl font-black text-rose-400 tabular-nums tracking-tight">- R$ <?= number_format($taxaSaaS, 2, ',', '.') ?></p>
                        <p class="text-[10px] text-rose-300/60 mt-1">Retido automaticamente no split</p>
                    </div>

                    <!-- Líquido Lojista -->
                    <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-4 backdrop-blur-sm">
                        <span class="text-[10px] font-black uppercase tracking-widest text-emerald-300/90 block mb-1">Líquido Creditado na sua Conta</span>
                        <p class="text-2xl font-black text-emerald-400 tabular-nums tracking-tight">R$ <?= number_format($liquidoLojista, 2, ',', '.') ?></p>
                        <p class="text-[10px] text-emerald-300/60 mt-1">Disponível no seu Mercado Pago</p>
                    </div>
                </div>

                <!-- Ações e Mensagens de Estorno -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pt-2 gap-4">
                    <div class="text-xs text-indigo-200/70 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-cyan-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <?php if ($estaEstornado): ?>
                            <span>Esta transação foi estornada. O valor foi devolvido ao comprador e o estoque foi restaurado.</span>
                        <?php else: ?>
                            <span>O estorno automático reverte o valor ao cliente, restitui a taxa do split e restaura o estoque.</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!$estaEstornado && $model->status_venda_codigo === 'QUITADA'): ?>
                        <button type="button" onclick="abrirModalEstornoMP()" class="inline-flex items-center justify-center px-4 py-2.5 bg-rose-600/90 hover:bg-rose-600 active:scale-95 text-white text-xs font-black rounded-xl transition-all shadow-lg border border-rose-500/50 uppercase tracking-wider">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Solicitar Estorno Mercado Pago
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

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

        </div>
    </div>
<!-- Modal de Confirmação de Estorno Mercado Pago -->
<div id="modal-estorno-mp" class="fixed inset-0 z-[110] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" onclick="fecharModalEstornoMP()"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-100">
            <!-- Cabeçalho de Alerta -->
            <div class="bg-rose-50 px-6 py-5 border-b border-rose-100 flex items-center space-x-3">
                <div class="w-10 h-10 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center font-black">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-black text-gray-900 uppercase tracking-tight">Confirmar Estorno no Mercado Pago</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Venda #<?= substr($model->id, 0, 8) ?> • Total R$ <?= number_format($model->valor_total, 2, ',', '.') ?></p>
                </div>
            </div>

            <!-- Corpo Informativo -->
            <div class="px-6 py-6 space-y-4">
                <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100 space-y-2 text-xs text-gray-700">
                    <p class="font-black text-gray-900 flex items-center">
                        <span class="w-2 h-2 rounded-full bg-rose-500 mr-2"></span> O que acontecerá ao confirmar:
                    </p>
                    <ul class="list-disc list-inside space-y-1 text-gray-600 pl-1">
                        <li>O valor de <strong>R$ <?= number_format($model->valor_total, 2, ',', '.') ?></strong> será devolvido à conta/cartão do cliente.</li>
                        <li>A taxa de split da SaaS será estornada proporcionalmente.</li>
                        <li>Os itens vendidos retornarão automaticamente ao estoque.</li>
                        <li>O lançamento financeiro desta venda no Caixa será revertido.</li>
                        <li>O status da venda passará para <strong>CANCELADA</strong>.</li>
                    </ul>
                </div>

                <div>
                    <label for="mp-motivo-estorno" class="block text-[11px] font-black uppercase tracking-wider text-gray-700 mb-1">
                        Motivo do Estorno (Opcional)
                    </label>
                    <input type="text" id="mp-motivo-estorno" class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-rose-500 focus:outline-none transition-all" placeholder="Ex: Devolução solicitada pelo cliente, desistência, etc.">
                </div>

                <div id="mp-estorno-erro" class="hidden p-3 rounded-xl bg-rose-100 text-rose-800 text-xs font-semibold"></div>
            </div>

            <!-- Rodapé / Botões -->
            <div class="bg-gray-50 px-6 py-4 flex flex-col sm:flex-row sm:justify-end gap-2 border-t border-gray-100">
                <button type="button" onclick="fecharModalEstornoMP()" class="px-4 py-2.5 bg-white hover:bg-gray-100 text-gray-700 text-xs font-black rounded-xl border border-gray-200 uppercase tracking-wider transition-all">
                    Cancelar
                </button>
                <button type="button" id="btn-confirmar-estorno-mp" onclick="executarEstornoMercadoPago()" class="inline-flex items-center justify-center px-5 py-2.5 bg-rose-600 hover:bg-rose-700 active:scale-95 text-white text-xs font-black rounded-xl uppercase tracking-wider shadow-lg shadow-rose-600/30 transition-all">
                    <span id="btn-estorno-texto">Confirmar Estorno Imediato</span>
                    <svg id="btn-estorno-spinner" class="hidden animate-spin ml-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function abrirModalEstornoMP() {
    const modal = document.getElementById('modal-estorno-mp');
    const erroDiv = document.getElementById('mp-estorno-erro');
    if (erroDiv) {
        erroDiv.classList.add('hidden');
        erroDiv.innerText = '';
    }
    if (modal) modal.classList.remove('hidden');
}

function fecharModalEstornoMP() {
    const modal = document.getElementById('modal-estorno-mp');
    if (modal) modal.classList.add('hidden');
}

function executarEstornoMercadoPago() {
    const btn = document.getElementById('btn-confirmar-estorno-mp');
    const btnTexto = document.getElementById('btn-estorno-texto');
    const spinner = document.getElementById('btn-estorno-spinner');
    const erroDiv = document.getElementById('mp-estorno-erro');
    const motivo = document.getElementById('mp-motivo-estorno') ? document.getElementById('mp-motivo-estorno').value.trim() : '';

    if (btn) btn.disabled = true;
    if (btnTexto) btnTexto.innerText = 'Processando Estorno...';
    if (spinner) spinner.classList.remove('hidden');
    if (erroDiv) erroDiv.classList.add('hidden');

    const payload = {
        venda_id: '<?= $model->id ?>',
        motivo: motivo || 'Cancelamento solicitado pelo lojista'
    };

    fetch(window.BASE_URL + '/api/mercado-pago/estornar-pagamento', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.sucesso) {
            if (btnTexto) btnTexto.innerText = 'Estorno Concluído!';
            setTimeout(() => {
                window.location.reload();
            }, 800);
        } else {
            throw new Error(data.mensagem || 'Falha ao processar estorno no Mercado Pago.');
        }
    })
    .catch(err => {
        if (erroDiv) {
            erroDiv.innerText = err.message || 'Erro de comunicação ao estornar.';
            erroDiv.classList.remove('hidden');
        }
        if (btn) btn.disabled = false;
        if (btnTexto) btnTexto.innerText = 'Tentar Novamente';
        if (spinner) spinner.classList.add('hidden');
    });
}
</script>

<?php
$this->registerJsFile('@web/js/venda-list.js?v=5', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerJs("window.BASE_URL = '" . Url::base(true) . "';", \yii\web\View::POS_HEAD);
?>