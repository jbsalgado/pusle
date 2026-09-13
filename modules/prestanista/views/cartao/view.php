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
        <a href="<?= Url::to(['/prestanista/cartao/index']) ?>" class="text-xs font-bold text-slate-400 hover:text-white flex items-center gap-1.5">
            <span>←</span> Voltar aos Cartões
        </a>

        <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
            <button type="button" onclick="abrirModalAjustarFrequencia()" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-amber-400 font-bold text-xs rounded-xl border border-slate-700 transition flex items-center gap-1.5 cursor-pointer">
                <span>🔄</span> Alterar Frequência
            </button>
            <a href="<?= Url::to(['/prestanista/cartao/imprimir', 'id' => $cartao->id]) ?>" target="_blank" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 transition flex items-center gap-1.5">
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
                            <?php 
                                $qtd = (float)$item->quantidade;
                                $qtdFormatada = (floor($qtd) == $qtd) ? number_format($qtd, 0, ',', '.') : rtrim(rtrim(number_format($qtd, 2, ',', '.'), '0'), ',');
                            ?>
                            <tr>
                                <td class="py-1.5 font-bold uppercase text-slate-900">
                                    <?= Html::encode($item->produto->nome ?? 'Mercadoria') ?> 
                                    <span class="text-slate-500 font-normal">(Qtd: <?= $qtdFormatada ?>)</span>
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
                <?php $freqCartao = $cartao->getFrequenciaPrestanista(); ?>
                <div class="flex items-center gap-4 sm:gap-6 pt-1 text-[11px] font-sans font-black uppercase text-amber-950 flex-wrap">
                    <span class="text-amber-900 font-bold">Frequência:</span>
                    <span class="<?= $freqCartao === 'DIÁRIA' ? 'text-amber-950 font-black underline decoration-amber-600 decoration-2' : 'text-slate-500' ?>">[<?= $freqCartao === 'DIÁRIA' ? 'X' : ' ' ?>] DIÁRIA</span>
                    <span class="<?= $freqCartao === 'SEMANAL' ? 'text-amber-950 font-black underline decoration-amber-600 decoration-2' : 'text-slate-500' ?>">[<?= $freqCartao === 'SEMANAL' ? 'X' : ' ' ?>] SEMANAL</span>
                    <span class="<?= $freqCartao === 'QUINZENAL' ? 'text-amber-950 font-black underline decoration-amber-600 decoration-2' : 'text-slate-500' ?>">[<?= $freqCartao === 'QUINZENAL' ? 'X' : ' ' ?>] QUINZENAL</span>
                    <span class="<?= $freqCartao === 'MENSAL' ? 'text-amber-950 font-black underline decoration-amber-600 decoration-2' : 'text-slate-500' ?>">[<?= $freqCartao === 'MENSAL' ? 'X' : ' ' ?>] MENSAL</span>
                    <button type="button" onclick="abrirModalAjustarFrequencia()" title="Alterar Frequência" class="text-[10px] lowercase font-bold text-amber-700 hover:text-amber-900 underline ml-auto flex items-center gap-1 cursor-pointer">
                        <span>✏️</span> <span>alterar</span>
                    </button>
                </div>
            </div>

            <!-- GRADE DE PRESTAÇÕES & CONTROLE DE BAIXAS (LINHA COMPLETA COM 7 COLUNAS) -->
            <div class="mt-4">
                <div class="flex items-center justify-between mb-1.5">
                    <div class="text-center sm:text-left font-bold text-[10px] uppercase tracking-wider text-slate-800">
                        Grade de Prestações & Baixas de Pagamento
                    </div>
                    <span class="text-[10px] font-mono text-slate-600 font-bold"><?= count($parcelas) ?> Prestações</span>
                </div>

                <div class="overflow-x-auto border-2 border-slate-900 rounded-lg">
                    <table class="w-full text-[11px] font-mono border-collapse text-center whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-200 border-b-2 border-slate-900 font-black text-slate-900">
                                <th class="p-1.5 border-r border-slate-900 w-10">Nº</th>
                                <th class="p-1.5 border-r border-slate-900">DATA PREST.</th>
                                <th class="p-1.5 border-r border-slate-900">VL. PREST.</th>
                                <th class="p-1.5 border-r border-slate-900">VL. COMPRA</th>
                                <th class="p-1.5 border-r border-slate-900">DATA PAG</th>
                                <th class="p-1.5 border-r border-slate-900">VL. RECEB.</th>
                                <th class="p-1.5 border-r border-slate-900">TIPO</th>
                                <th class="p-1.5 border-r border-slate-900">SALDO</th>
                                <th class="p-1.5 print:hidden">AÇÕES</th>
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
                                    <td class="p-1 border-r border-slate-400 font-bold uppercase text-[10px] <?= $isPaga ? 'text-blue-900' : 'text-slate-400' ?>">
                                        <?= Html::encode($tipoNome) ?>
                                    </td>
                                    <td class="p-1 border-r border-slate-900 font-black <?= $isPaga ? 'text-amber-950' : 'text-slate-400' ?>">
                                        <?= $saldoExibido ?>
                                    </td>
                                    <td class="p-1 text-center print:hidden">
                                        <?php if ($isPaga): ?>
                                            <div class="flex items-center justify-center gap-1.5">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                    ✓ Paga
                                                </span>
                                                <a href="<?= Url::to(['/prestanista/cartao/estornar-parcela', 'id' => $cartao->id, 'parcela_id' => $p->id]) ?>" 
                                                   onclick="return confirm('Deseja realmente estornar a baixa desta prestação?');"
                                                   class="text-[10px] text-rose-600 hover:text-rose-800 underline font-bold px-1" title="Estornar baixa">
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
</script>
