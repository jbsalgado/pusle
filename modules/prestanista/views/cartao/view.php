<?php
/** @var yii\web\View $this */
/** @var app\modules\vendas\models\Venda $cartao */
/** @var app\modules\vendas\models\HistoricoCobranca[] $historico */

use yii\helpers\Html;
use yii\helpers\Url;

$usuario = Yii::$app->user->identity;
$lojaNome = $usuario->nome_loja ?? $usuario->nome ?? 'CREDIÁRIOS PULSE';
$cliente = $cartao->cliente;
$itens = $cartao->itens;
$parcelas = $cartao->parcelas;

$totalPago = 0;
foreach ($parcelas as $p) {
    if ($p->status_parcela_codigo === 'PAGA') {
        $totalPago += (float)($p->valor_pago ?: $p->valor_parcela);
    }
}
$saldoDevedor = max(0, (float)$cartao->valor_total - $totalPago);

$this->title = 'Cartão #' . $cartao->id . ' - ' . ($cliente->nome ?? 'Cliente');
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Barra Superior com Ações Rápidas -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-slate-950/90 border border-slate-800 p-4 rounded-2xl shadow-sm">
        <a href="<?= Url::to(['index']) ?>" class="text-xs font-bold text-slate-400 hover:text-white flex items-center gap-1.5">
            <span>←</span> Voltar aos Cartões
        </a>

        <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
            <a href="<?= Url::to(['imprimir', 'id' => $cartao->id]) ?>" target="_blank" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 transition flex items-center gap-1.5">
                <span>🖨️</span> Imprimir Cartão
            </a>
            <a href="https://api.whatsapp.com/send?phone=55<?= preg_replace('/\D/', '', $cliente->telefone ?? '') ?>&text=<?= urlencode("Olá " . ($cliente->nome ?? '') . "! Segue o resumo do seu Cartão de Crediário #" . $cartao->id . " da " . $lojaNome . ". Saldo restante: R$ " . number_format($saldoDevedor, 2, ',', '.')) ?>" target="_blank" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl transition flex items-center gap-1.5">
                <span>📱</span> WhatsApp
            </a>
        </div>
    </div>

    <!-- O CARTÃO FÍSICO DIGITAL (LAYOUT IDÊNTICO À FOTO ANEXADA) -->
    <div class="bg-amber-50/95 text-slate-900 border-4 border-slate-900 rounded-3xl p-5 sm:p-8 shadow-2xl font-serif relative overflow-hidden">
        
        <!-- Marca d'água / Efeito Papel timbrado -->
        <div class="border-2 border-slate-900 rounded-2xl p-4 sm:p-6 bg-white/60">

            <!-- Cabeçalho do Cartão -->
            <div class="text-center border-b-2 border-slate-900 pb-3 mb-4">
                <p class="text-[10px] uppercase font-bold tracking-widest text-slate-600">Nosso prazer é atendê-lo bem</p>
                <h2 class="text-xl sm:text-3xl font-black uppercase tracking-tight text-slate-950 mt-0.5">
                    <?= Html::encode($lojaNome) ?>
                </h2>
                <p class="text-xs uppercase font-bold text-slate-700 tracking-wider">CREDIÁRIOS & UTILIDADES</p>

                <div class="grid grid-cols-3 text-left text-xs font-mono font-bold mt-3 pt-2 border-t border-slate-400">
                    <div>DATA: <span class="font-sans font-black"><?= date('d/m/Y', strtotime($cartao->data_venda)) ?></span></div>
                    <div class="text-center">FLS: <span class="font-sans font-black">01</span></div>
                    <div class="text-right">Nº: <span class="font-sans font-black text-amber-800">#<?= str_pad($cartao->id, 5, '0', STR_PAD_LEFT) ?></span></div>
                </div>
            </div>

            <!-- Tabela de Objetos / Mercadorias Vendidas -->
            <div class="mb-4">
                <table class="w-full text-xs font-mono border-collapse">
                    <thead>
                        <tr class="border-b-2 border-slate-900 text-left">
                            <th class="py-1 uppercase font-bold">OBJETOS</th>
                            <th class="py-1 text-right uppercase font-bold w-28">VALOR R$</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-300">
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td class="py-1.5 font-bold uppercase text-slate-900">
                                    <?= Html::encode($item->produto->nome ?? 'Mercadoria') ?> 
                                    <span class="text-slate-500 font-normal">(Qtd: <?= $item->quantidade ?>)</span>
                                </td>
                                <td class="py-1.5 text-right font-black text-slate-950">
                                    R$ <?= number_format($item->valor_total_item, 2, ',', '.') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <!-- Linhas vazias de preenchimento manuscrito como na foto -->
                        <?php for ($i = count($itens); $i < 3; $i++): ?>
                            <tr class="text-slate-300">
                                <td class="py-1.5">_________________________________________</td>
                                <td class="py-1.5 text-right">R$ _________</td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-slate-900 font-sans">
                            <td class="pt-2 font-black uppercase text-sm">TOTAL DO CARTÃO:</td>
                            <td class="pt-2 text-right font-black text-base text-amber-900">
                                R$ <?= number_format($cartao->valor_total, 2, ',', '.') ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Dados do Comprador / Cliente -->
            <div class="border-y-2 border-slate-900 py-3 text-xs leading-relaxed space-y-1 font-mono">
                <div class="flex flex-wrap items-center justify-between gap-1">
                    <div><span class="font-bold">Sr.(a):</span> <span class="font-sans font-black text-sm"><?= Html::encode($cliente->nome ?? '—') ?></span></div>
                    <div><span class="font-bold">Nº:</span> <span class="font-sans font-bold"><?= Html::encode($cliente->numero ?? 'S/N') ?></span></div>
                </div>
                <div><span class="font-bold">Rua:</span> <span class="font-sans font-bold"><?= Html::encode($cliente->endereco ?? '—') ?></span></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><span class="font-bold">BAIRRO:</span> <span class="font-sans font-bold"><?= Html::encode($cliente->bairro ?? '—') ?></span></div>
                    <div><span class="font-bold">CIDADE:</span> <span class="font-sans font-bold"><?= Html::encode($cliente->cidade ?? '—') ?></span></div>
                </div>
                <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-300">
                    <div><span class="font-bold">VENDEDOR:</span> <span class="font-sans font-bold"><?= Html::encode($cartao->vendedor->nome ?? 'Ambulante') ?></span></div>
                    <div><span class="font-bold">TEL.:</span> <span class="font-sans font-bold"><?= Html::encode($cliente->telefone ?? '—') ?></span></div>
                </div>
                <div class="flex items-center gap-6 pt-1 text-[11px] font-sans font-black uppercase text-amber-900">
                    <span>Frequência:</span>
                    <span>[<?= $cartao->numero_parcelas > 4 ? 'X' : ' ' ?>] SEMANAL</span>
                    <span>[ ] QUINZENAL</span>
                    <span>[<?= $cartao->numero_parcelas <= 4 ? 'X' : ' ' ?>] MENSAL</span>
                </div>
            </div>

            <!-- GRADE DE BAIXAS / RECEBIMENTOS (COLUNAS DUPLAS IGUAL À FOTO) -->
            <div class="mt-4">
                <div class="text-center font-bold text-[10px] uppercase tracking-wider text-slate-700 mb-1">
                    Grade de Cobranças & Baixas Semanais / Mensais
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-[11px] font-mono border-2 border-slate-900 text-center border-collapse">
                        <thead>
                            <tr class="bg-slate-200/90 border-b-2 border-slate-900 font-bold">
                                <th class="p-1.5 border-r border-slate-900 w-1/6">DATA</th>
                                <th class="p-1.5 border-r border-slate-900 w-1/6">DINHEIRO</th>
                                <th class="p-1.5 border-r-2 border-slate-900 w-1/6">SALDO</th>
                                <th class="p-1.5 border-r border-slate-900 w-1/6">DATA</th>
                                <th class="p-1.5 border-r border-slate-900 w-1/6">DINHEIRO</th>
                                <th class="p-1.5 w-1/6">SALDO</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-400">
                            <?php 
                                // Divide as parcelas / baixas em duas colunas de até 10 linhas
                                $maxLinhas = max(6, ceil(count($parcelas) / 2));
                                $saldoCorrente = (float)$cartao->valor_total;
                            ?>
                            <?php for ($l = 0; $l < $maxLinhas; $l++): ?>
                                <?php 
                                    $pEsq = $parcelas[$l] ?? null;
                                    $pDir = $parcelas[$l + $maxLinhas] ?? null;
                                ?>
                                <tr class="h-7 hover:bg-amber-100/50">
                                    <!-- Lado Esquerdo -->
                                    <td class="p-1 border-r border-slate-400 font-bold text-slate-800">
                                        <?= $pEsq ? ($pEsq->status_parcela_codigo === 'PAGA' ? date('d/m', strtotime($pEsq->data_pagamento ?: $pEsq->data_vencimento)) : date('d/m', strtotime($pEsq->data_vencimento))) : '' ?>
                                    </td>
                                    <td class="p-1 border-r border-slate-400 font-bold text-emerald-800">
                                        <?= $pEsq && $pEsq->status_parcela_codigo === 'PAGA' ? 'R$ ' . number_format($pEsq->valor_pago ?: $pEsq->valor_parcela, 2, ',', '.') : ($pEsq ? 'R$ ' . number_format($pEsq->valor_parcela, 2, ',', '.') : '') ?>
                                    </td>
                                    <td class="p-1 border-r-2 border-slate-900 font-black text-amber-900">
                                        <?php if ($pEsq): ?>
                                            <?php 
                                                if ($pEsq->status_parcela_codigo === 'PAGA') {
                                                    $saldoCorrente -= (float)($pEsq->valor_pago ?: $pEsq->valor_parcela);
                                                }
                                            ?>
                                            R$ <?= number_format(max(0, $saldoCorrente), 2, ',', '.') ?>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Lado Direito -->
                                    <td class="p-1 border-r border-slate-400 font-bold text-slate-800">
                                        <?= $pDir ? ($pDir->status_parcela_codigo === 'PAGA' ? date('d/m', strtotime($pDir->data_pagamento ?: $pDir->data_vencimento)) : date('d/m', strtotime($pDir->data_vencimento))) : '' ?>
                                    </td>
                                    <td class="p-1 border-r border-slate-400 font-bold text-emerald-800">
                                        <?= $pDir && $pDir->status_parcela_codigo === 'PAGA' ? 'R$ ' . number_format($pDir->valor_pago ?: $pDir->valor_parcela, 2, ',', '.') : ($pDir ? 'R$ ' . number_format($pDir->valor_parcela, 2, ',', '.') : '') ?>
                                    </td>
                                    <td class="p-1 font-black text-amber-900">
                                        <?php if ($pDir): ?>
                                            <?php 
                                                if ($pDir->status_parcela_codigo === 'PAGA') {
                                                    $saldoCorrente -= (float)($pDir->valor_pago ?: $pDir->valor_parcela);
                                                }
                                            ?>
                                            R$ <?= number_format(max(0, $saldoCorrente), 2, ',', '.') ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Rodapé com avisos tradicionais do cartão prestanista -->
            <div class="mt-4 pt-2 border-t-2 border-slate-900 flex flex-col sm:flex-row items-center justify-between text-[10px] text-slate-600 font-mono text-center sm:text-left gap-1">
                <span>Obs.: Não aceitamos devolução.</span>
                <span class="font-bold uppercase tracking-wider text-slate-900">« Deus é Fiel »</span>
                <span>Em caso de devolução paga 20% do valor da mercadoria.</span>
            </div>

        </div>

    </div>

</div>
