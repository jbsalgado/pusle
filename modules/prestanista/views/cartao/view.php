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
$idCurto = strlen($cartao->id) > 8 ? strtoupper(substr($cartao->id, 0, 8)) : str_pad($cartao->id, 5, '0', STR_PAD_LEFT);
$publicUrl = \app\modules\prestanista\controllers\CartaoController::getPublicUrl($cartao->id);
$msgWhats = "Olá " . ($cliente->nome ?? $cliente->nome_completo ?? '') . "! Segue o link para você acompanhar o seu Cartão de Crediário #" . $idCurto . " na " . $lojaNome . ":\n" . $publicUrl . "\n\nSaldo restante: R$ " . number_format($saldoDevedor, 2, ',', '.') . "\nVocê pode consultar suas parcelas e baixar o cartão em PDF ou imagem a qualquer momento.";

$this->title = 'Cartão #' . $cartao->id . ' - ' . ($cliente->nome ?? 'Cliente');
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Barra Superior com Ações Rápidas -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-slate-950/90 border border-slate-800 p-4 rounded-2xl shadow-sm">
        <a href="<?= Url::to(['/prestanista/cartao/index']) ?>" class="text-xs font-bold text-slate-400 hover:text-white flex items-center gap-1.5">
            <span>←</span> Voltar aos Cartões
        </a>

        <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
            <!-- Botão Copiar Link Público -->
            <button type="button" onclick="navigator.clipboard.writeText('<?= $publicUrl ?>'); const t = document.getElementById('toastCopiado'); t.classList.remove('hidden'); setTimeout(() => t.classList.add('hidden'), 3000);" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-amber-400 hover:text-amber-300 font-bold text-xs rounded-xl border border-slate-700 transition flex items-center gap-1.5 active:scale-95" title="Copiar Link Público de Visualização">
                <span>🔗</span> Copiar Link
            </button>

            <!-- Abrir Visão Pública -->
            <a href="<?= $publicUrl ?>" target="_blank" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 transition flex items-center gap-1.5" title="Abrir consulta como o cliente vê">
                <span>👁️</span> Ver Online
            </a>

            <!-- Alterar Frequência -->
            <button type="button" onclick="abrirModalAjustarFrequencia()" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 transition flex items-center gap-1.5 cursor-pointer">
                <span>🔄</span> Frequência
            </button>

            <!-- Imprimir Cartão -->
            <a href="<?= Url::to(['/prestanista/cartao/imprimir', 'id' => $cartao->id]) ?>" target="_blank" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 transition flex items-center gap-1.5">
                <span>🖨️</span> Imprimir
            </a>

            <!-- Enviar WhatsApp com Link Público -->
            <a href="https://api.whatsapp.com/send?phone=55<?= preg_replace('/\D/', '', $cliente->telefone ?? '') ?>&text=<?= urlencode($msgWhats) ?>" target="_blank" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl transition flex items-center gap-1.5 active:scale-95 shadow-md">
                <span>📱</span> Enviar WhatsApp
            </a>
        </div>
    </div>

    <!-- Toast de Feedback para Cópia -->
    <div id="toastCopiado" class="hidden bg-emerald-500 text-slate-950 font-black text-xs px-4 py-2.5 rounded-xl shadow-lg text-center animate-bounce">
        ✓ Link público do cartão copiado para a área de transferência!
    </div>

    <!-- O CARTÃO FÍSICO DIGITAL (LAYOUT RESPONSIVO PARA TELAS GRANDES E CELULARES) -->
    <div class="bg-amber-50/95 text-slate-900 border-2 sm:border-4 border-slate-900 rounded-2xl sm:rounded-3xl p-3 sm:p-8 shadow-2xl font-serif relative overflow-hidden">
        
        <!-- Marca d'água / Efeito Papel timbrado -->
        <div class="border sm:border-2 border-slate-900 rounded-xl sm:rounded-2xl p-2.5 sm:p-6 bg-white/60">

            <!-- Cabeçalho do Cartão -->
            <div class="text-center border-b-2 border-slate-900 pb-2.5 sm:pb-3 mb-3 sm:mb-4">
                <p class="text-[9px] sm:text-[10px] uppercase font-bold tracking-widest text-slate-600">Nosso prazer é atendê-lo bem</p>
                <h2 class="text-lg sm:text-3xl font-black uppercase tracking-tight text-slate-950 mt-0.5 leading-tight">
                    <?= Html::encode($lojaNome) ?>
                </h2>
                <p class="text-[11px] sm:text-xs uppercase font-bold text-slate-700 tracking-wider">CREDIÁRIOS & UTILIDADES</p>

                <?php 
                    $idCurto = strlen($cartao->id) > 8 ? strtoupper(substr($cartao->id, 0, 8)) : str_pad($cartao->id, 5, '0', STR_PAD_LEFT);
                ?>
                <div class="grid grid-cols-3 text-left text-[11px] sm:text-xs font-mono font-bold mt-2.5 pt-2 border-t border-slate-400 items-center">
                    <div>DATA: <span class="font-sans font-black whitespace-nowrap"><?= date('d/m/Y', strtotime($cartao->data_venda)) ?></span></div>
                    <div class="text-center">FLS: <span class="font-sans font-black">01</span></div>
                    <div class="text-right whitespace-nowrap">Nº: <span class="font-sans font-black text-amber-800" title="ID Completo: <?= Html::encode($cartao->id) ?>">#<?= $idCurto ?></span></div>
                </div>
            </div>

            <!-- Tabela de Objetos / Mercadorias Vendidas -->
            <div class="mb-3 sm:mb-4">
                <div class="overflow-x-auto -mx-1 px-1 sm:mx-0 sm:px-0">
                    <table class="w-full text-[10px] sm:text-xs font-mono border-collapse">
                        <thead>
                            <tr class="border-b-2 border-slate-900 text-left text-[9px] sm:text-xs">
                                <th class="py-1 uppercase font-bold pr-1">OBJETOS</th>
                                <th class="py-1 text-center uppercase font-bold w-9 sm:w-14 px-1">QTD</th>
                                <th class="py-1 text-right uppercase font-bold w-18 sm:w-24 px-1 whitespace-nowrap">VL. UNIT.</th>
                                <th class="py-1 text-right uppercase font-bold w-20 sm:w-28 pl-1 whitespace-nowrap">TOTAL R$</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-300">
                            <?php foreach ($itens as $item): ?>
                                <?php 
                                    $qtd = (float)$item->quantidade;
                                    $qtdFormatada = (floor($qtd) == $qtd) ? number_format($qtd, 0, ',', '.') : rtrim(rtrim(number_format($qtd, 2, ',', '.'), '0'), ',');
                                    $vlUnit = (float)($item->preco_unitario_venda ?: ($qtd > 0 ? $item->valor_total_item / $qtd : $item->valor_total_item));
                                ?>
                                <tr>
                                    <td class="py-1 sm:py-1.5 font-bold uppercase text-slate-900 pr-1 break-words">
                                        <?= Html::encode($item->produto->nome ?? 'Mercadoria') ?>
                                    </td>
                                    <td class="py-1 sm:py-1.5 text-center font-bold text-slate-800 px-1 whitespace-nowrap">
                                        <?= $qtdFormatada ?>
                                    </td>
                                    <td class="py-1 sm:py-1.5 text-right font-medium text-slate-700 px-1 whitespace-nowrap">
                                        R$ <?= number_format($vlUnit, 2, ',', '.') ?>
                                    </td>
                                    <td class="py-1 sm:py-1.5 text-right font-black text-slate-950 pl-1 whitespace-nowrap">
                                        R$ <?= number_format($item->valor_total_item, 2, ',', '.') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <!-- Linhas vazias de preenchimento manuscrito como na foto -->
                            <?php for ($i = count($itens); $i < 2; $i++): ?>
                                <tr class="text-slate-300">
                                    <td class="py-1 sm:py-1.5 pr-1">___________________________</td>
                                    <td class="py-1 sm:py-1.5 text-center px-1">___</td>
                                    <td class="py-1 sm:py-1.5 text-right px-1">R$ _______</td>
                                    <td class="py-1 sm:py-1.5 text-right pl-1">R$ _______</td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-slate-900 font-sans">
                                <td colspan="3" class="pt-2 font-black uppercase text-[10px] sm:text-sm text-right pr-2">TOTAL DO CARTÃO:</td>
                                <td class="pt-2 text-right font-black text-xs sm:text-base text-amber-900 whitespace-nowrap">
                                    R$ <?= number_format($cartao->valor_total, 2, ',', '.') ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Dados do Comprador / Cliente -->
            <div class="border-y-2 border-slate-900 py-2.5 sm:py-3 text-[11px] sm:text-xs leading-relaxed space-y-1 font-mono">
                <div class="flex items-center justify-between gap-1 flex-wrap">
                    <div><span class="font-bold">Sr.(a):</span> <span class="font-sans font-black text-xs sm:text-sm"><?= Html::encode($cliente->nome ?? '—') ?></span></div>
                    <div class="whitespace-nowrap"><span class="font-bold">Nº:</span> <span class="font-sans font-bold"><?= Html::encode($cliente->numero ?? 'S/N') ?></span></div>
                </div>
                <div><span class="font-bold">Rua:</span> <span class="font-sans font-bold"><?= Html::encode($cliente->endereco ?? '—') ?></span></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><span class="font-bold">BAIRRO:</span> <span class="font-sans font-bold"><?= Html::encode($cliente->bairro ?? '—') ?></span></div>
                    <div><span class="font-bold">CIDADE:</span> <span class="font-sans font-bold"><?= Html::encode($cliente->cidade ?? '—') ?></span></div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1 sm:gap-2 pt-1 border-t border-slate-300">
                    <div><span class="font-bold">VENDEDOR:</span> <span class="font-sans font-bold"><?= Html::encode($cartao->vendedor->nome ?? 'Ambulante') ?></span></div>
                    <div><span class="font-bold">TEL.:</span> <span class="font-sans font-bold"><?= Html::encode($cliente->telefone ?? '—') ?></span></div>
                </div>
                <?php $freqCartao = $cartao->getFrequenciaPrestanista(); ?>
                <div class="flex items-center gap-2 sm:gap-4 pt-1.5 text-[10px] sm:text-[11px] font-sans font-black uppercase text-amber-950 flex-wrap justify-between">
                    <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
                        <span class="text-amber-900 font-bold">Frequência:</span>
                        <span class="<?= $freqCartao === 'DIÁRIA' ? 'text-amber-950 font-black underline decoration-amber-600 decoration-2' : 'text-slate-500' ?>">[<?= $freqCartao === 'DIÁRIA' ? 'X' : ' ' ?>] DIÁRIA</span>
                        <span class="<?= $freqCartao === 'SEMANAL' ? 'text-amber-950 font-black underline decoration-amber-600 decoration-2' : 'text-slate-500' ?>">[<?= $freqCartao === 'SEMANAL' ? 'X' : ' ' ?>] SEMANAL</span>
                        <span class="<?= $freqCartao === 'QUINZENAL' ? 'text-amber-950 font-black underline decoration-amber-600 decoration-2' : 'text-slate-500' ?>">[<?= $freqCartao === 'QUINZENAL' ? 'X' : ' ' ?>] QUINZENAL</span>
                        <span class="<?= $freqCartao === 'MENSAL' ? 'text-amber-950 font-black underline decoration-amber-600 decoration-2' : 'text-slate-500' ?>">[<?= $freqCartao === 'MENSAL' ? 'X' : ' ' ?>] MENSAL</span>
                    </div>
                    <button type="button" onclick="abrirModalAjustarFrequencia()" title="Alterar Frequência" class="text-[10px] lowercase font-bold text-amber-700 hover:text-amber-900 underline flex items-center gap-1 cursor-pointer">
                        <span>✏️</span> <span>alterar</span>
                    </button>
                </div>
            </div>

            <!-- GRADE DE PRESTAÇÕES & CONTROLE DE BAIXAS -->
            <div class="mt-3 sm:mt-4">
                <div class="flex items-center justify-between mb-1.5 flex-wrap gap-1">
                    <div class="font-bold text-[10px] sm:text-[11px] uppercase tracking-wider text-slate-800">
                        Grade de Prestações & Baixas
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Alternador Mobile entre Tabela e Fichas -->
                        <div class="sm:hidden flex items-center bg-slate-200 p-0.5 rounded-lg border border-slate-400 text-[10px] font-bold">
                            <button type="button" onclick="alternarVisaoMobile('tabela')" id="btnAbaTabela" class="px-2 py-0.5 rounded bg-slate-900 text-white transition cursor-pointer">
                                Tabela
                            </button>
                            <button type="button" onclick="alternarVisaoMobile('fichas')" id="btnAbaFichas" class="px-2 py-0.5 rounded text-slate-700 hover:text-slate-900 transition cursor-pointer">
                                Fichas
                            </button>
                        </div>
                        <span class="text-[10px] font-mono text-slate-600 font-bold"><?= count($parcelas) ?> Prestações</span>
                    </div>
                </div>

                <!-- Dica visual de rolagem para mobile -->
                <div id="dicaScrollMobile" class="sm:hidden flex items-center justify-between text-[9px] font-bold text-amber-900 bg-amber-200/60 px-2 py-1 rounded border border-amber-300 mb-1.5">
                    <span>👈 Deslize para o lado para ver Saldo e Baixar 👉</span>
                </div>

                <!-- Visão em Tabela Tradicional -->
                <div id="visaoTabelaMobile" class="overflow-x-auto border-2 border-slate-900 rounded-lg shadow-inner -mx-1 sm:mx-0">
                    <table class="w-full text-[10px] sm:text-[11px] font-mono border-collapse text-center whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-200 border-b-2 border-slate-900 font-black text-slate-900">
                                <th class="p-1 sm:p-1.5 border-r border-slate-900 w-8 sm:w-10">Nº</th>
                                <th class="p-1 sm:p-1.5 border-r border-slate-900">DATA PREST.</th>
                                <th class="p-1 sm:p-1.5 border-r border-slate-900">VL. PREST.</th>
                                <th class="p-1 sm:p-1.5 border-r border-slate-900">VL. COMPRA</th>
                                <th class="p-1 sm:p-1.5 border-r border-slate-900">DATA PAG</th>
                                <th class="p-1 sm:p-1.5 border-r border-slate-900">VL. RECEB.</th>
                                <th class="p-1 sm:p-1.5 border-r border-slate-900">TIPO</th>
                                <th class="p-1 sm:p-1.5 border-r border-slate-900 text-left pl-2">SALDO</th>
                                <th class="p-1 sm:p-1.5 print:hidden">AÇÕES</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-400 bg-white/60">
                            <?php 
                                $saldoAcumulado = (float)$cartao->valor_total;
                                foreach ($parcelas as $idx => $p): 
                                    $isPaga = ($p->status_parcela_codigo === 'PAGA');
                                    $tipoNome = '—';
                                    $saldoExibido = '—';
                                    if ($isPaga) {
                                        $valorBaixado = (float)($p->valor_pago ?: $p->valor_parcela);
                                        $saldoAcumulado = max(0, $saldoAcumulado - $valorBaixado);
                                        $saldoExibido = 'R$ ' . number_format($saldoAcumulado, 2, ',', '.');
                                        $tipoNome = $p->formaPagamento ? ($p->formaPagamento->nome ?: $p->formaPagamento->tipo) : 'DINHEIRO';
                                    }
                            ?>
                                <tr class="h-8 hover:bg-amber-100/60 transition <?= $isPaga ? 'bg-emerald-50/60' : '' ?>">
                                    <td class="p-1 border-r border-slate-400 font-bold text-slate-700">
                                        <?= str_pad($p->numero_parcela ?: ($idx + 1), 2, '0', STR_PAD_LEFT) ?>
                                    </td>
                                    <td class="p-1 border-r border-slate-400 font-bold text-slate-900">
                                        <?= date('d/m/Y', strtotime($p->data_vencimento)) ?>
                                    </td>
                                    <td class="p-1 border-r border-slate-400 font-bold text-slate-900">
                                        <?= number_format($p->valor_parcela, 2, ',', '.') ?>
                                    </td>
                                    <td class="p-1 border-r border-slate-400 font-medium text-slate-600">
                                        <?= number_format($cartao->valor_total, 2, ',', '.') ?>
                                    </td>
                                    <td class="p-1 border-r border-slate-400 font-bold <?= $isPaga ? 'text-emerald-800' : 'text-slate-400' ?>">
                                        <?= $isPaga ? date('d/m/Y', strtotime($p->data_pagamento ?: $p->data_vencimento)) : '—' ?>
                                    </td>
                                    <td class="p-1 border-r border-slate-400 font-black <?= $isPaga ? 'text-emerald-700' : 'text-slate-400' ?>">
                                        <?= $isPaga ? number_format($p->valor_pago ?: $p->valor_parcela, 2, ',', '.') : '—' ?>
                                    </td>
                                    <td class="p-1 border-r border-slate-400 font-bold uppercase text-[9px] sm:text-[10px] <?= $isPaga ? 'text-blue-900' : 'text-slate-400' ?>">
                                        <?= Html::encode($tipoNome) ?>
                                    </td>
                                    <td class="p-1 border-r border-slate-900 font-black text-left pl-2 <?= $isPaga ? 'text-amber-950' : 'text-slate-400' ?>">
                                        <?= $saldoExibido ?>
                                    </td>
                                    <td class="p-1 text-center print:hidden">
                                        <?php if ($isPaga): ?>
                                            <div class="flex items-center justify-center gap-1">
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                    ✓ Paga
                                                </span>
                                                <a href="<?= Url::to(['/prestanista/cartao/estornar-parcela', 'id' => $cartao->id, 'parcela_id' => $p->id]) ?>" 
                                                   onclick="return confirm('Deseja realmente estornar a baixa desta prestação?');"
                                                   class="text-[9px] text-rose-600 hover:text-rose-800 underline font-bold px-1" title="Estornar baixa">
                                                    Estornar
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <button type="button" 
                                                    onclick="abrirModalReceberParcela('<?= $p->id ?>', '<?= $p->numero_parcela ?>', '<?= number_format($p->valor_parcela, 2, ',', '.') ?>', '<?= date('d/m/Y', strtotime($p->data_vencimento)) ?>')"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] rounded shadow transition active:scale-95 cursor-pointer">
                                                <span>💵</span> <span>Receber</span>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Visão em Fichas (Cards) para telas pequenas / smartphones -->
                <div id="visaoFichasMobile" class="hidden sm:hidden space-y-2">
                    <?php 
                        $saldoCard = (float)$cartao->valor_total;
                        foreach ($parcelas as $idx => $p): 
                            $isPaga = ($p->status_parcela_codigo === 'PAGA');
                            $saldoCardStr = '—';
                            $tipoCard = '—';
                            if ($isPaga) {
                                $valorBaixado = (float)($p->valor_pago ?: $p->valor_parcela);
                                $saldoCard = max(0, $saldoCard - $valorBaixado);
                                $saldoCardStr = 'R$ ' . number_format($saldoCard, 2, ',', '.');
                                $tipoCard = $p->formaPagamento ? ($p->formaPagamento->nome ?: $p->formaPagamento->tipo) : 'DINHEIRO';
                            }
                    ?>
                        <div class="border border-slate-800 rounded-xl p-3 bg-white/90 shadow-sm space-y-2 <?= $isPaga ? 'border-emerald-500 bg-emerald-50/50' : '' ?>">
                            <div class="flex items-center justify-between font-mono text-xs">
                                <span class="font-black text-slate-900 bg-slate-200 px-2 py-0.5 rounded">
                                    Prestação #<?= str_pad($p->numero_parcela ?: ($idx + 1), 2, '0', STR_PAD_LEFT) ?>
                                </span>
                                <span class="font-bold text-slate-700">
                                    Venc.: <?= date('d/m/Y', strtotime($p->data_vencimento)) ?>
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-[11px] font-mono pt-1 border-t border-slate-300">
                                <div>
                                    <span class="text-slate-500 block text-[9px] uppercase font-bold">Vl. Parcela:</span>
                                    <span class="font-black text-slate-900 text-xs">R$ <?= number_format($p->valor_parcela, 2, ',', '.') ?></span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block text-[9px] uppercase font-bold">Saldo Restante:</span>
                                    <span class="font-black <?= $isPaga ? 'text-amber-950 text-xs' : 'text-slate-400' ?>"><?= $saldoCardStr ?></span>
                                </div>
                            </div>
                            <?php if ($isPaga): ?>
                                <div class="flex items-center justify-between pt-1 border-t border-slate-200 text-[10px]">
                                    <div class="text-emerald-800 font-bold flex items-center gap-1">
                                        <span>✓ Paga em <?= date('d/m/Y', strtotime($p->data_pagamento ?: $p->data_vencimento)) ?> via <?= Html::encode($tipoCard) ?></span>
                                    </div>
                                    <a href="<?= Url::to(['/prestanista/cartao/estornar-parcela', 'id' => $cartao->id, 'parcela_id' => $p->id]) ?>" 
                                       onclick="return confirm('Deseja realmente estornar esta prestação?');"
                                       class="text-rose-600 underline font-bold px-1">
                                        Estornar
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="pt-1">
                                    <button type="button" 
                                            onclick="abrirModalReceberParcela('<?= $p->id ?>', '<?= $p->numero_parcela ?>', '<?= number_format($p->valor_parcela, 2, ',', '.') ?>', '<?= date('d/m/Y', strtotime($p->data_vencimento)) ?>')"
                                            class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-lg shadow transition active:scale-95 flex items-center justify-center gap-1.5 cursor-pointer">
                                        <span>💵</span> <span>Receber Parcela (R$ <?= number_format($p->valor_parcela, 2, ',', '.') ?>)</span>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Rodapé com avisos tradicionais do cartão prestanista -->
            <div class="mt-4 pt-2 border-t-2 border-slate-900 flex flex-col sm:flex-row items-center justify-between text-[9px] sm:text-[10px] text-slate-600 font-mono text-center sm:text-left gap-1">
                <span>Obs.: Não aceitamos devolução.</span>
                <span class="font-bold uppercase tracking-wider text-slate-900">« Deus é Fiel »</span>
                <span>Em caso de devolução paga 20% do valor da mercadoria.</span>
            </div>

        </div>

    </div>

</div>

<!-- Modal Alterar Frequência do Cartão -->
<div id="modalAjustarFrequencia" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div class="relative w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-sm font-black text-white flex items-center gap-2">
                <span>🔄</span>
                <span>Alterar Frequência do Cartão</span>
            </h3>
            <button type="button" onclick="fecharModalAjustarFrequencia()" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition">
                ✕
            </button>
        </div>

        <form method="post" action="<?= Url::to(['/prestanista/cartao/ajustar-frequencia', 'id' => $cartao->id]) ?>" class="space-y-4">
            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>" />

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5">Frequência da Cobrança:</label>
                <select name="nova_frequencia" id="selectNovaFrequencia" class="w-full h-11 px-3.5 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-white font-bold focus:border-amber-500 focus:outline-none">
                    <option value="1" <?= $freqCartao === 'DIÁRIA' ? 'selected' : '' ?>>DIÁRIA (A cada 1 dia)</option>
                    <option value="7" <?= $freqCartao === 'SEMANAL' ? 'selected' : '' ?>>SEMANAL (A cada 7 dias)</option>
                    <option value="15" <?= $freqCartao === 'QUINZENAL' ? 'selected' : '' ?>>QUINZENAL (A cada 15 dias)</option>
                    <option value="30" <?= $freqCartao === 'MENSAL' ? 'selected' : '' ?>>MENSAL (A cada 30 dias)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5">Data Base para Próximo Vencimento:</label>
                <input type="date" name="data_base_vencimento" value="<?= $cartao->data_primeiro_vencimento ?: date('Y-m-d') ?>" class="w-full h-11 px-3.5 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-white font-bold focus:border-amber-500 focus:outline-none">
                <p class="text-[11px] text-slate-400 mt-1">As parcelas pendentes serão recalculadas a partir desta data com o novo intervalo de cobrança.</p>
            </div>

            <div class="pt-2 border-t border-slate-800 flex items-center justify-end gap-2">
                <button type="button" onclick="fecharModalAjustarFrequencia()" class="px-4 py-2.5 rounded-xl border border-slate-700 text-slate-300 hover:text-white text-xs font-bold transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black text-xs rounded-xl shadow-lg transition active:scale-95">
                    Salvar e Recalcular
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Receber Parcela / Prestação -->
<div id="modalReceberParcela" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div class="relative w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-sm font-black text-white flex items-center gap-2">
                <span>💵</span>
                <span>Registrar Recebimento da Prestação</span>
            </h3>
            <button type="button" onclick="fecharModalReceberParcela()" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition cursor-pointer">
                ✕
            </button>
        </div>

        <form method="post" action="<?= Url::to(['/prestanista/cartao/receber-parcela', 'id' => $cartao->id]) ?>" class="space-y-4">
            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>" />
            <input type="hidden" name="parcela_id" id="receber_parcela_id" value="" />

            <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-3 text-xs space-y-1">
                <div class="text-slate-400">Prestação a Baixar: <span class="text-amber-400 font-bold" id="receber_num_parcela">#</span></div>
                <div class="text-slate-400">Vencimento Original: <span class="text-white font-bold" id="receber_vencimento">—</span></div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">Data Pagamento:</label>
                    <input type="date" name="data_pagamento" id="receber_data_pagamento" value="<?= date('Y-m-d') ?>" class="w-full h-11 px-3.5 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-white font-bold focus:border-amber-500 focus:outline-none" required />
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">Valor Recebido (R$):</label>
                    <input type="text" name="valor_pago" id="receber_valor_pago" class="w-full h-11 px-3.5 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-white font-bold focus:border-amber-500 focus:outline-none text-right font-mono" required />
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5">Forma / Tipo de Pagamento:</label>
                <select name="tipo_pagamento" id="receber_tipo_pagamento" class="w-full h-11 px-3.5 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-white font-bold focus:border-amber-500 focus:outline-none">
                    <option value="PIX">PIX</option>
                    <option value="DINHEIRO" selected>DINHEIRO (Em mãos)</option>
                    <option value="CARTAO_DEBITO">CARTÃO DE DÉBITO</option>
                    <option value="CARTAO_CREDITO">CARTÃO DE CRÉDITO</option>
                    <option value="OUTRO">OUTRO</option>
                </select>
            </div>

            <div class="pt-2 border-t border-slate-800 flex items-center justify-end gap-2">
                <button type="button" onclick="fecharModalReceberParcela()" class="px-4 py-2.5 rounded-xl border border-slate-700 text-slate-300 hover:text-white text-xs font-bold transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-black text-xs rounded-xl shadow-lg transition active:scale-95 cursor-pointer">
                    Confirmar Recebimento
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalAjustarFrequencia() {
        document.getElementById('modalAjustarFrequencia').classList.remove('hidden');
    }
    function fecharModalAjustarFrequencia() {
        document.getElementById('modalAjustarFrequencia').classList.add('hidden');
    }
    function abrirModalReceberParcela(parcelaId, numParcela, valorParcela, vencimento) {
        document.getElementById('receber_parcela_id').value = parcelaId;
        document.getElementById('receber_num_parcela').innerText = '#' + numParcela;
        document.getElementById('receber_valor_pago').value = valorParcela;
        document.getElementById('receber_vencimento').innerText = vencimento;
        document.getElementById('modalReceberParcela').classList.remove('hidden');
    }
    function fecharModalReceberParcela() {
        document.getElementById('modalReceberParcela').classList.add('hidden');
    }
    function alternarVisaoMobile(tipo) {
        const tabela = document.getElementById('visaoTabelaMobile');
        const fichas = document.getElementById('visaoFichasMobile');
        const dica = document.getElementById('dicaScrollMobile');
        const btnTab = document.getElementById('btnAbaTabela');
        const btnFich = document.getElementById('btnAbaFichas');

        if (tipo === 'fichas') {
            tabela.classList.add('hidden');
            fichas.classList.remove('hidden');
            if (dica) dica.classList.add('hidden');
            btnFich.classList.add('bg-slate-900', 'text-white');
            btnFich.classList.remove('text-slate-700');
            btnTab.classList.remove('bg-slate-900', 'text-white');
            btnTab.classList.add('text-slate-700');
        } else {
            tabela.classList.remove('hidden');
            fichas.classList.add('hidden');
            if (dica) dica.classList.remove('hidden');
            btnTab.classList.add('bg-slate-900', 'text-white');
            btnTab.classList.remove('text-slate-700');
            btnFich.classList.remove('bg-slate-900', 'text-white');
            btnFich.classList.add('text-slate-700');
        }
    }
</script>
