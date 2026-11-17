<?php
/** @var string $tenantId */
/** @var string $lojaNome */
/** @var string $operadorNome */
/** @var app\modules\caixa\models\Caixa|null $caixaAberto */
/** @var app\modules\vendas\models\FormaPagamento[] $formasPagamento */
/** @var app\modules\pdv\models\PdvConfiguracao $pdvConfig */

use yii\helpers\Html;
use yii\helpers\Url;

$formasJson = [];
foreach ($formasPagamento as $fp) {
    $formasJson[] = [
        'id' => (string)$fp->id,
        'nome' => $fp->nome,
        'tipo' => $fp->tipo,
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDV Frente de Caixa - <?= Html::encode($lojaNome) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page {
                size: 80mm auto;
                margin: 0mm;
            }
            html, body {
                background: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: auto !important;
                overflow: visible !important;
            }
            body > * {
                display: none !important;
            }
            #area-cupom-impressao {
                display: block !important;
                visibility: visible !important;
                position: relative !important;
                width: 76mm !important;
                margin: 0 auto !important;
                padding: 4mm !important;
                background: #ffffff !important;
                color: #000000 !important;
                font-family: 'Courier New', monospace !important;
                font-size: 11px !important;
                line-height: 1.35 !important;
            }
        }
        .cupom-scroll::-webkit-scrollbar { width: 6px; }
        .cupom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    </style>
</head>
<body class="bg-slate-950 text-slate-900 h-screen w-screen overflow-hidden flex flex-col select-none">

    <!-- BARRA SUPERIOR ESTILO SYSPDV -->
    <header class="bg-gradient-to-r from-blue-950 via-blue-900 to-indigo-950 text-white px-4 py-2 flex items-center justify-between border-b border-blue-800 shrink-0 shadow-md">
        <div class="flex items-center gap-3">
            <div class="font-black text-lg tracking-tight flex items-center gap-2">
                <span class="bg-amber-400 text-blue-950 px-2 py-0.5 rounded font-black text-sm">PDV</span>
                <span><?= Html::encode($lojaNome) ?></span>
            </div>
            <span id="badge-status-caixa" class="px-3 py-1 rounded-full text-xs font-black tracking-wide <?= $caixaAberto ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/40' : 'bg-rose-500/20 text-rose-300 border border-rose-400/40 cursor-pointer' ?>" <?= !$caixaAberto ? 'onclick="abrirModalCaixa()"' : '' ?>>
                <?= $caixaAberto ? '● CAIXA ABERTO' : '● CAIXA FECHADO (Clique p/ abrir)' ?>
            </span>
        </div>

        <div class="flex items-center gap-3">
            <!-- Visor Balança ao vivo -->
            <div class="flex items-center gap-2 bg-slate-950/60 border border-blue-700/60 rounded-xl px-3 py-1">
                <span class="text-[11px] text-blue-200 uppercase font-bold">Balança:</span>
                <span id="visor-peso-balanca" class="font-mono font-black text-amber-300 text-sm">0,000 kg</span>
                <button type="button" onclick="conectarBalancaSerial()" id="btn-conectar-balanca" class="px-2 py-0.5 bg-blue-600 hover:bg-blue-500 text-white text-[11px] font-bold rounded-lg transition">
                    Conectar
                </button>
            </div>

            <button type="button" onclick="abrirModalConfig()" class="px-2.5 py-1.5 bg-blue-800/70 hover:bg-blue-700 text-xs font-bold rounded-lg border border-blue-600/50 transition" title="Configurações de Leitor e Balança">
                ⚙️ Config
            </button>

            <button type="button" onclick="atualizarCatalogoOffline(true)" class="px-2.5 py-1.5 bg-blue-800/70 hover:bg-blue-700 text-xs font-bold rounded-lg border border-blue-600/50 transition" title="Sincronizar catálogo offline">
                🔄 Catálogo
            </button>

            <button type="button" onclick="sincronizarVendasOffline(false)" id="btn-sync-pdv-offline" class="hidden px-2.5 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded-lg transition" title="Sincronizar vendas realizadas offline">
                ⏳ <span id="badge-qtd-pdv-offline">0</span> Offline
            </button>

            <div class="text-right hidden sm:block">
                <div id="relogio-pdv" class="font-mono font-bold text-xs text-blue-100"><?= date('d/m/Y H:i') ?></div>
                <div class="text-[10px] text-blue-300">Operador: <?= Html::encode($operadorNome) ?></div>
            </div>

            <a href="<?= Url::to(['/vendas/inicio/index']) ?>" class="px-2.5 py-1.5 bg-rose-600/80 hover:bg-rose-600 text-white text-xs font-bold rounded-lg transition">
                Sair
            </a>
        </div>
    </header>

    <!-- CORPO PRINCIPAL (COLUNA ESQUERDA INSTITUCIONAL/FOTO + DIREITA VISOR & CUPOM) -->
    <main class="flex-1 flex overflow-hidden bg-slate-100">

        <!-- COLUNA ESQUERDA: IDENTIDADE, INPUT DO LEITOR E ATALHOS -->
        <aside class="w-80 lg:w-96 bg-gradient-to-b from-blue-900 via-blue-800 to-indigo-950 text-white flex flex-col justify-between p-4 shrink-0 border-r border-blue-950">
            <!-- Banner / Foto do Produto -->
            <div class="space-y-3">
                <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-2xl p-4 text-center min-h-[210px] flex flex-col items-center justify-center">
                    <img id="pdv-produto-foto" src="" alt="" class="hidden max-h-40 rounded-xl object-contain bg-white p-1 mb-2 shadow">
                    <div id="pdv-banner-institucional" class="space-y-2">
                        <div class="text-3xl font-serif italic text-amber-300">Obrigado</div>
                        <p class="text-xs text-blue-100 leading-relaxed">
                            Sua preferência é a nossa maior motivação.
                        </p>
                        <div class="text-[11px] text-blue-300 font-semibold pt-2 border-t border-white/10">
                            Passe o produto no leitor de código de barras ou informe o código abaixo
                        </div>
                    </div>
                </div>

                <!-- Input Principal do Leitor de Código de Barras -->
                <div class="bg-slate-950/70 border-2 border-amber-400/70 rounded-2xl p-3 shadow-lg">
                    <label for="input-codigo-barras" class="block text-[10px] font-black uppercase tracking-wider text-amber-300 mb-1">
                        Código de Barras / PLU / Qtd*Código
                    </label>
                    <div class="flex gap-1.5">
                        <input
                            type="text"
                            id="input-codigo-barras"
                            autocomplete="off"
                            autofocus
                            placeholder="Bipe ou digite (ex: 3*789...)"
                            class="w-full bg-white text-slate-950 font-mono font-black text-base px-3 py-2.5 rounded-xl outline-none focus:ring-2 focus:ring-amber-400"
                        >
                        <button type="button" onclick="processarInputCodigo()" class="px-3 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black rounded-xl text-sm transition">
                            OK
                        </button>
                    </div>
                    <div id="pdv-feedback-leitura" class="mt-1.5 text-[11px] font-bold text-blue-200 min-h-[16px]">
                        Pronto para leitura...
                    </div>
                </div>
            </div>

            <!-- Grade de Atalhos Rápidos -->
            <div class="grid grid-cols-2 gap-1.5 text-[11px] pt-3 border-t border-white/15">
                <button type="button" onclick="abrirModalBusca()" class="bg-white/10 hover:bg-white/20 px-2.5 py-2 rounded-lg text-left font-bold transition">
                    <span class="text-amber-300 font-mono">F2</span> Buscar Produto
                </button>
                <button type="button" onclick="abrirModalAlterarQtd()" class="bg-white/10 hover:bg-white/20 px-2.5 py-2 rounded-lg text-left font-bold transition">
                    <span class="text-amber-300 font-mono">F3</span> Alterar Qtd/Peso
                </button>
                <button type="button" onclick="removerItemSelecionado()" class="bg-white/10 hover:bg-white/20 px-2.5 py-2 rounded-lg text-left font-bold transition">
                    <span class="text-amber-300 font-mono">F4</span> Cancelar Item
                </button>
                <button type="button" onclick="reimprimirUltimoCupom()" class="bg-white/10 hover:bg-white/20 px-2.5 py-2 rounded-lg text-left font-bold transition">
                    <span class="text-amber-300 font-mono">F6</span> Reimprimir
                </button>
                <button type="button" onclick="abrirModalDesconto()" class="bg-white/10 hover:bg-white/20 px-2.5 py-2 rounded-lg text-left font-bold transition">
                    <span class="text-amber-300 font-mono">F7</span> Desconto
                </button>
                <button type="button" onclick="usarPesoBalancaNoUltimoItem()" class="bg-white/10 hover:bg-white/20 px-2.5 py-2 rounded-lg text-left font-bold transition">
                    <span class="text-amber-300 font-mono">F8</span> Ler Balança
                </button>
                <button type="button" onclick="abrirModalMovimentacao()" class="col-span-2 bg-white/10 hover:bg-white/20 px-2.5 py-2 rounded-lg text-left font-bold transition">
                    <span class="text-amber-300 font-mono">F9</span> Sangria / Suprimento de Caixa
                </button>
                <button type="button" onclick="abrirModalFinalizar()" class="col-span-2 bg-emerald-500 hover:bg-emerald-400 text-slate-950 px-3 py-2.5 rounded-xl font-black text-xs text-center shadow transition">
                    <span class="font-mono">F10</span> FINALIZAR VENDA
                </button>
            </div>
        </aside>

        <!-- ÁREA PRINCIPAL DIREITA: VISOR DO ÚLTIMO ITEM + CUPOM + SUBTOTAL -->
        <section class="flex-1 flex flex-col bg-white overflow-hidden">

            <!-- VISOR GIGANTE DO ÚLTIMO ITEM BIPADO -->
            <div class="bg-slate-50 border-b-2 border-slate-300 px-6 py-4 shrink-0">
                <div id="visor-nome-produto" class="text-2xl sm:text-3xl font-black text-rose-800 tracking-tight uppercase truncate">
                    CAIXA LIVRE — AGUARDANDO PRODUTO
                </div>
                <div class="grid grid-cols-3 gap-4 mt-3 pt-2 border-t border-slate-200">
                    <div>
                        <div class="text-[11px] font-black uppercase text-slate-500 tracking-wider">Quantidade</div>
                        <div id="visor-qtd-produto" class="text-2xl sm:text-3xl font-mono font-black text-slate-900">0,000</div>
                    </div>
                    <div>
                        <div class="text-[11px] font-black uppercase text-slate-500 tracking-wider">Preço Unitário</div>
                        <div id="visor-unit-produto" class="text-2xl sm:text-3xl font-mono font-black text-slate-900">R$ 0,00</div>
                    </div>
                    <div class="text-right">
                        <div class="text-[11px] font-black uppercase text-slate-500 tracking-wider">Preço Total</div>
                        <div id="visor-total-produto" class="text-2xl sm:text-3xl font-mono font-black text-blue-900">R$ 0,00</div>
                    </div>
                </div>
            </div>

            <!-- LISTA DE ITENS DO CUPOM -->
            <div class="flex-1 overflow-y-auto cupom-scroll px-4 py-2">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-white border-b border-slate-300 text-[11px] font-black uppercase text-slate-500">
                        <tr>
                            <th class="py-2 px-2 w-12">#</th>
                            <th class="py-2 px-2">Produto / Código</th>
                            <th class="py-2 px-2 text-right">Qtd / Peso</th>
                            <th class="py-2 px-2 text-right">Unitário</th>
                            <th class="py-2 px-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody id="tabela-itens-cupom" class="divide-y divide-slate-200 text-sm">
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400 font-semibold">
                                Nenhum item registrado neste cupom.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- RODAPÉ DE TOTAIS -->
            <footer class="bg-slate-100 border-t-2 border-slate-300 px-6 py-4 flex items-center justify-between shrink-0">
                <div class="space-y-1">
                    <div class="text-xs font-bold text-slate-600">
                        Atendido por: <span class="text-slate-900 font-black uppercase"><?= Html::encode($operadorNome) ?></span>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-slate-500 font-semibold">
                        <span>Itens: <strong id="rodape-qtd-itens" class="text-slate-900">0</strong></span>
                        <span id="rodape-desconto-wrap" class="hidden text-rose-700 font-bold">
                            Desconto: -R$ <span id="rodape-desconto-val">0,00</span>
                        </span>
                        <button type="button" onclick="cancelarCupomAtual()" class="text-rose-600 hover:underline font-bold">
                            Limpar Cupom (ESC)
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-5">
                    <div class="text-right">
                        <div class="text-xs font-black uppercase text-slate-500 tracking-wider">Subtotal</div>
                        <div id="rodape-subtotal" class="text-4xl sm:text-5xl font-mono font-black text-slate-950 tracking-tight">
                            0,00
                        </div>
                    </div>
                    <button type="button" onclick="abrirModalFinalizar()" class="px-6 py-4 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-base rounded-2xl shadow-lg transition active:scale-95">
                        Receber (F10)
                    </button>
                </div>
            </footer>
        </section>
    </main>

    <!-- MODAL F2: BUSCA DE PRODUTOS -->
    <div id="modal-busca" class="fixed inset-0 z-50 bg-black/70 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
            <div class="bg-blue-950 text-white px-5 py-3 flex items-center justify-between">
                <h3 class="font-black text-sm">Buscar Produto (F2)</h3>
                <button type="button" onclick="fecharModais()" class="text-blue-200 hover:text-white font-bold">✕</button>
            </div>
            <div class="p-4 border-b">
                <input type="text" id="input-busca-produto" placeholder="Digite nome, código de barras ou PLU..." class="w-full border-2 border-slate-300 focus:border-blue-600 rounded-xl px-4 py-2.5 text-sm font-bold outline-none">
            </div>
            <div id="lista-resultado-busca" class="flex-1 overflow-y-auto divide-y divide-slate-200 p-2"></div>
        </div>
    </div>

    <!-- MODAL F3: ALTERAR QUANTIDADE / PESO -->
    <div id="modal-qtd" class="fixed inset-0 z-50 bg-black/70 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden">
            <div class="bg-blue-950 text-white px-5 py-3 flex items-center justify-between">
                <h3 class="font-black text-sm">Alterar Quantidade / Peso (F3)</h3>
                <button type="button" onclick="fecharModais()" class="text-blue-200 hover:text-white font-bold">✕</button>
            </div>
            <div class="p-5 space-y-4">
                <div id="modal-qtd-nome" class="font-black text-slate-800 text-sm"></div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Nova Quantidade / Peso (kg)</label>
                    <input type="number" step="0.001" min="0.001" id="input-nova-qtd" class="w-full border-2 border-slate-300 focus:border-blue-600 rounded-xl px-4 py-2.5 font-mono font-black text-xl outline-none">
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="capturarPesoParaModalQtd()" class="flex-1 py-2.5 bg-blue-100 hover:bg-blue-200 text-blue-900 font-bold text-xs rounded-xl">
                        ⚖️ Pegar da Balança
                    </button>
                    <button type="button" onclick="confirmarAlteracaoQtd()" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs rounded-xl">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL F7: DESCONTO -->
    <div id="modal-desconto" class="fixed inset-0 z-50 bg-black/70 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden">
            <div class="bg-blue-950 text-white px-5 py-3 flex items-center justify-between">
                <h3 class="font-black text-sm">Aplicar Desconto (F7)</h3>
                <button type="button" onclick="fecharModais()" class="text-blue-200 hover:text-white font-bold">✕</button>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Desconto em R$</label>
                    <input type="number" step="0.01" min="0" id="input-desconto-reais" class="w-full border-2 border-slate-300 rounded-xl px-4 py-2.5 font-mono font-black text-xl outline-none">
                </div>
                <button type="button" onclick="confirmarDesconto()" class="w-full py-3 bg-blue-900 hover:bg-blue-800 text-white font-black text-xs rounded-xl">
                    Aplicar Desconto
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL F9: SANGRIA / SUPRIMENTO -->
    <div id="modal-movimentacao" class="fixed inset-0 z-50 bg-black/70 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden">
            <div class="bg-blue-950 text-white px-5 py-3 flex items-center justify-between">
                <h3 class="font-black text-sm">Movimentação de Caixa (F9)</h3>
                <button type="button" onclick="fecharModais()" class="text-blue-200 hover:text-white font-bold">✕</button>
            </div>
            <div class="p-5 space-y-3">
                <select id="mov-tipo" class="w-full border-2 border-slate-300 rounded-xl px-3 py-2 text-sm font-bold">
                    <option value="SANGRIA">Sangria (Retirada)</option>
                    <option value="SUPRIMENTO">Suprimento (Entrada)</option>
                </select>
                <input type="number" step="0.01" min="0.01" id="mov-valor" placeholder="Valor (R$)" class="w-full border-2 border-slate-300 rounded-xl px-3 py-2 font-mono font-black text-lg">
                <input type="text" id="mov-desc" placeholder="Motivo / Observação" class="w-full border-2 border-slate-300 rounded-xl px-3 py-2 text-sm">
                <button type="button" onclick="salvarMovimentacaoCaixa()" class="w-full py-3 bg-blue-900 hover:bg-blue-800 text-white font-black text-xs rounded-xl">
                    Registrar Movimentação
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL ABERTURA DE CAIXA -->
    <div id="modal-abrir-caixa" class="fixed inset-0 z-50 bg-black/75 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden">
            <div class="bg-blue-950 text-white px-5 py-3 flex items-center justify-between">
                <h3 class="font-black text-sm">Abertura de Caixa</h3>
                <button type="button" onclick="fecharModais()" class="text-blue-200 hover:text-white font-bold">✕</button>
            </div>
            <div class="p-5 space-y-3">
                <label class="block text-xs font-bold text-slate-600">Fundo de Troco Inicial (R$)</label>
                <input type="number" step="0.01" min="0" value="0.00" id="caixa-valor-inicial" class="w-full border-2 border-slate-300 rounded-xl px-3 py-2 font-mono font-black text-xl">
                <button type="button" onclick="confirmarAberturaCaixa()" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs rounded-xl">
                    Abrir Caixa Agora
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL F10: FINALIZAÇÃO / PAGAMENTO MISTO -->
    <div id="modal-finalizar" class="fixed inset-0 z-50 bg-black/75 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-xl shadow-2xl overflow-hidden">
            <div class="bg-emerald-700 text-white px-5 py-3.5 flex items-center justify-between">
                <h3 class="font-black text-base">Finalizar Venda (F10)</h3>
                <button type="button" onclick="fecharModais()" class="text-emerald-100 hover:text-white font-bold">✕</button>
            </div>
            <div class="p-5 space-y-4">
                <div class="bg-slate-100 rounded-xl p-4 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-black uppercase text-slate-500 block">Total a Pagar</span>
                        <span id="fin-falta-pagar" class="text-xs font-bold text-rose-600 hidden"></span>
                    </div>
                    <span id="fin-total-pagar" class="text-3xl font-mono font-black text-slate-950">R$ 0,00</span>
                </div>

                <!-- Lista de Formas de Pagamento (suporta pagamento misto) -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-black uppercase text-slate-600">Pagamentos</label>
                        <button type="button" onclick="adicionarLinhaPagamento()" class="text-xs font-bold text-blue-700 hover:underline">
                            + Adicionar Forma (Pagamento Misto)
                        </button>
                    </div>
                    <div id="fin-lista-pagamentos" class="space-y-2 max-h-48 overflow-y-auto"></div>
                </div>

                <div class="grid grid-cols-2 gap-3 items-center">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">CPF na Nota (Opcional)</label>
                        <input type="text" id="fin-cpf" maxlength="14" placeholder="000.000.000-00" class="w-full border-2 border-slate-300 rounded-xl px-3 py-2 text-sm font-mono">
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-2.5 text-right">
                        <div class="text-[10px] font-black uppercase text-amber-700">Troco</div>
                        <div id="fin-troco" class="text-xl font-mono font-black text-amber-900">R$ 0,00</div>
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="confirmarFinalizacaoVenda(true)" class="flex-1 py-3.5 bg-blue-900 hover:bg-blue-800 text-white font-black text-xs rounded-xl shadow">
                        Confirmar + Imprimir Cupom
                    </button>
                    <button type="button" onclick="confirmarFinalizacaoVenda(false)" class="flex-1 py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs rounded-xl shadow">
                        Confirmar Venda
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL CONFIGURAÇÃO DE BALANÇA E ETIQUETA -->
    <div id="modal-config" class="fixed inset-0 z-50 bg-black/75 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden">
            <div class="bg-blue-950 text-white px-5 py-3 flex items-center justify-between">
                <h3 class="font-black text-sm">Configuração de Leitor e Balança</h3>
                <button type="button" onclick="fecharModais()" class="text-blue-200 hover:text-white font-bold">✕</button>
            </div>
            <div class="p-5 space-y-3 text-sm">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Prefixo Etiqueta Balança</label>
                        <input type="text" id="cfg-prefixo" value="<?= Html::encode($pdvConfig->etiqueta_prefixo) ?>" class="w-full border rounded-xl px-3 py-2 font-mono font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Dígitos do PLU (4, 5 ou 6)</label>
                        <input type="number" min="4" max="6" id="cfg-tam-codigo" value="<?= (int)$pdvConfig->etiqueta_tamanho_codigo ?>" class="w-full border rounded-xl px-3 py-2 font-mono font-bold">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Etiqueta Codifica</label>
                        <select id="cfg-tipo-valor" class="w-full border rounded-xl px-3 py-2 font-bold">
                            <option value="PESO" <?= $pdvConfig->etiqueta_tipo_valor === 'PESO' ? 'selected' : '' ?>>Peso (gramas)</option>
                            <option value="PRECO" <?= $pdvConfig->etiqueta_tipo_valor === 'PRECO' ? 'selected' : '' ?>>Preço (centavos)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Protocolo Serial</label>
                        <select id="cfg-protocolo" class="w-full border rounded-xl px-3 py-2 font-bold">
                            <?php foreach (['TOLEDO', 'FILIZOLA', 'URANO', 'ELGIN'] as $proto): ?>
                                <option value="<?= $proto ?>" <?= $pdvConfig->balanca_protocolo === $proto ? 'selected' : '' ?>><?= $proto ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Baud Rate</label>
                        <select id="cfg-baud" class="w-full border rounded-xl px-3 py-2 font-mono font-bold">
                            <?php foreach ([4800, 9600, 19200, 115200] as $b): ?>
                                <option value="<?= $b ?>" <?= (int)$pdvConfig->balanca_baud_rate === $b ? 'selected' : '' ?>><?= $b ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex flex-col justify-center gap-1.5 pt-3">
                        <label class="flex items-center gap-2 text-xs font-bold">
                            <input type="checkbox" id="cfg-som" <?= $pdvConfig->som_leitor_ativo ? 'checked' : '' ?>> Som ao bipar
                        </label>
                        <label class="flex items-center gap-2 text-xs font-bold">
                            <input type="checkbox" id="cfg-imp-auto" <?= $pdvConfig->imprimir_cupom_auto ? 'checked' : '' ?>> Imprimir automático
                        </label>
                    </div>
                </div>
                <button type="button" onclick="salvarConfiguracoesPdv()" class="w-full py-3 bg-blue-900 hover:bg-blue-800 text-white font-black text-xs rounded-xl">
                    Salvar Configurações
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL PÓS-VENDA: COMPROVANTE / NFC-E / NOVA VENDA -->
    <div id="modal-pos-venda" class="fixed inset-0 z-50 bg-black/75 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden">
            <div class="bg-emerald-700 text-white px-5 py-3.5 flex items-center justify-between">
                <h3 class="font-black text-base">✓ Venda Concluída</h3>
                <button type="button" onclick="fecharModais()" class="text-emerald-100 hover:text-white font-bold">✕</button>
            </div>
            <div class="p-5 space-y-4 text-center">
                <div>
                    <div class="text-xs font-bold uppercase text-slate-500">Cupom</div>
                    <div id="pos-numero-cupom" class="text-2xl font-mono font-black text-slate-900">#000000</div>
                    <div id="pos-valor-total" class="text-lg font-mono font-black text-emerald-700 mt-0.5">R$ 0,00</div>
                </div>
                <div id="pos-nfce-status" class="hidden text-xs font-bold rounded-xl p-2.5"></div>
                <div class="space-y-2">
                    <button type="button" onclick="reimprimirUltimoCupom()" class="w-full py-3 bg-blue-900 hover:bg-blue-800 text-white font-black text-xs rounded-xl shadow">
                        🖨️ Imprimir Comprovante 80mm
                    </button>
                    <button type="button" id="btn-pos-comprovante-oficial" onclick="abrirComprovanteOficialPdv()" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl border border-slate-300">
                        📄 Abrir Comprovante Oficial (Nova Aba)
                    </button>
                    <button type="button" id="btn-pos-emitir-nfce" onclick="emitirNfcePdv()" class="w-full py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs rounded-xl shadow">
                        🧾 Emitir / Imprimir Cupom Fiscal (NFC-e)
                    </button>
                    <button type="button" onclick="fecharModais()" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs rounded-xl">
                        ✓ Nova Venda (ESC)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ÁREA OCULTA PARA IMPRESSÃO TÉRMICA 80MM -->
    <div id="area-cupom-impressao" class="hidden"></div>

    <script>
        const pdvState = {
            lojaNome: <?= json_encode($lojaNome) ?>,
            lojaDados: <?= json_encode($lojaDados ?? ['nome' => $lojaNome, 'documento' => '', 'endereco' => '', 'telefone' => '']) ?>,
            operadorNome: <?= json_encode($operadorNome) ?>,
            caixaAberto: <?= $caixaAberto ? 'true' : 'false' ?>,
            itens: [],
            indiceSelecionado: -1,
            desconto: 0,
            ultimaVenda: null,
            pesoAtualBalanca: 0,
            balancaConectada: false,
            config: {
                etiqueta_prefixo: <?= json_encode((string)$pdvConfig->etiqueta_prefixo) ?>,
                etiqueta_tamanho_codigo: <?= (int)$pdvConfig->etiqueta_tamanho_codigo ?>,
                etiqueta_tipo_valor: <?= json_encode((string)$pdvConfig->etiqueta_tipo_valor) ?>,
                balanca_protocolo: <?= json_encode((string)$pdvConfig->balanca_protocolo) ?>,
                balanca_baud_rate: <?= (int)$pdvConfig->balanca_baud_rate ?>,
                som_leitor_ativo: <?= $pdvConfig->som_leitor_ativo ? 'true' : 'false' ?>,
                imprimir_cupom_auto: <?= $pdvConfig->imprimir_cupom_auto ? 'true' : 'false' ?>,
            },
            formasPagamento: <?= json_encode($formasJson) ?>,
        };

        const inputCodigo = document.getElementById('input-codigo-barras');

        // Relógio ao vivo
        setInterval(() => {
            const el = document.getElementById('relogio-pdv');
            if (el) {
                const agora = new Date();
                el.textContent = agora.toLocaleDateString('pt-BR') + ' ' + agora.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            }
        }, 15000);

        // Foco permanente no leitor quando nenhum modal está aberto
        document.addEventListener('click', (e) => {
            const algumModalAberto = Array.from(document.querySelectorAll('[id^="modal-"]')).some(m => !m.classList.contains('hidden'));
            if (!algumModalAberto && e.target.tagName !== 'BUTTON' && e.target.tagName !== 'A' && e.target.tagName !== 'SELECT') {
                setTimeout(() => inputCodigo.focus(), 50);
            }
        });

        // Enter no campo de código de barras
        inputCodigo.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                processarInputCodigo();
            }
        });

        // Atalhos globais de teclado (F2..F10, ESC)
        document.addEventListener('keydown', (e) => {
            if (e.key === 'F2') { e.preventDefault(); abrirModalBusca(); }
            else if (e.key === 'F3') { e.preventDefault(); abrirModalAlterarQtd(); }
            else if (e.key === 'F4') { e.preventDefault(); removerItemSelecionado(); }
            else if (e.key === 'F6') { e.preventDefault(); reimprimirUltimoCupom(); }
            else if (e.key === 'F7') { e.preventDefault(); abrirModalDesconto(); }
            else if (e.key === 'F8') { e.preventDefault(); usarPesoBalancaNoUltimoItem(); }
            else if (e.key === 'F9') { e.preventDefault(); abrirModalMovimentacao(); }
            else if (e.key === 'F10') { e.preventDefault(); abrirModalFinalizar(); }
            else if (e.key === 'Escape') {
                const algumModalAberto = Array.from(document.querySelectorAll('[id^="modal-"]')).some(m => !m.classList.contains('hidden'));
                if (algumModalAberto) fecharModais();
                else cancelarCupomAtual();
            }
        });

        function tocarBeep(tipo = 'ok') {
            if (!pdvState.config.som_leitor_ativo) return;
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                if (tipo === 'ok') {
                    osc.frequency.value = 1400;
                    gain.gain.value = 0.08;
                    osc.start();
                    osc.stop(ctx.currentTime + 0.08);
                } else {
                    osc.frequency.value = 280;
                    gain.gain.value = 0.12;
                    osc.start();
                    osc.stop(ctx.currentTime + 0.25);
                }
            } catch (_) {}
        }

        async function processarInputCodigo() {
            const raw = inputCodigo.value.trim();
            if (!raw) return;
            inputCodigo.value = '';
            setFeedback('Consultando código...', 'text-amber-300');

            try {
                const res = await fetch(`<?= Url::to(['/pdv/frente/buscar-codigo']) ?>?codigo=${encodeURIComponent(raw)}`);
                const data = await res.json();
                if (data.success && data.item) {
                    adicionarItemAoCupom(data.item);
                    tocarBeep('ok');
                    setFeedback(`✓ ${data.item.nome}`, 'text-emerald-300');
                } else {
                    tocarBeep('erro');
                    setFeedback(data.message || 'Código não encontrado', 'text-rose-300');
                }
            } catch (err) {
                // Fallback offline via catálogo em cache
                const itemLocal = buscarNoCatalogoOffline(raw);
                if (itemLocal) {
                    adicionarItemAoCupom(itemLocal);
                    tocarBeep('ok');
                    setFeedback(`✓ (Offline) ${itemLocal.nome}`, 'text-amber-300');
                } else {
                    tocarBeep('erro');
                    setFeedback('Falha de conexão e item fora do cache offline.', 'text-rose-300');
                }
            }
            inputCodigo.focus();
        }

        function setFeedback(msg, cls = 'text-blue-200') {
            const el = document.getElementById('pdv-feedback-leitura');
            el.className = `mt-1.5 text-[11px] font-bold min-h-[16px] ${cls}`;
            el.textContent = msg;
        }

        function adicionarItemAoCupom(item) {
            // Se o produto exige pesagem na balança conectada e veio com peso 1.000
            if (item.balanca_pesavel && pdvState.balancaConectada && pdvState.pesoAtualBalanca > 0) {
                const pesoLiquido = Math.max(0.001, pdvState.pesoAtualBalanca - (item.tara_padrao || 0));
                item.quantidade = Number(pesoLiquido.toFixed(3));
                item.subtotal = Number((item.quantidade * item.preco_unitario).toFixed(2));
            }

            // Agrupa se for mesmo produto/variante e não for item de etiqueta pesada diferente
            const idxExistente = (!item.balanca_pesavel && !item.venda_fracionada)
                ? pdvState.itens.findIndex(i => i.produto_id === item.produto_id && i.variante_id === item.variante_id)
                : -1;

            if (idxExistente >= 0) {
                pdvState.itens[idxExistente].quantidade = Number((pdvState.itens[idxExistente].quantidade + item.quantidade).toFixed(3));
                pdvState.itens[idxExistente].subtotal = Number((pdvState.itens[idxExistente].quantidade * pdvState.itens[idxExistente].preco_unitario).toFixed(2));
                pdvState.indiceSelecionado = idxExistente;
            } else {
                pdvState.itens.push(item);
                pdvState.indiceSelecionado = pdvState.itens.length - 1;
            }

            renderizarCupom();
        }

        function renderizarCupom() {
            const tbody = document.getElementById('tabela-itens-cupom');
            if (pdvState.itens.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="py-12 text-center text-slate-400 font-semibold">Nenhum item registrado neste cupom.</td></tr>';
                document.getElementById('visor-nome-produto').textContent = 'CAIXA LIVRE — AGUARDANDO PRODUTO';
                document.getElementById('visor-qtd-produto').textContent = '0,000';
                document.getElementById('visor-unit-produto').textContent = 'R$ 0,00';
                document.getElementById('visor-total-produto').textContent = 'R$ 0,00';
                document.getElementById('pdv-produto-foto').classList.add('hidden');
                document.getElementById('pdv-banner-institucional').classList.remove('hidden');
            } else {
                const ativo = pdvState.itens[pdvState.indiceSelecionado] || pdvState.itens[pdvState.itens.length - 1];
                document.getElementById('visor-nome-produto').textContent = ativo.nome;
                document.getElementById('visor-qtd-produto').textContent = ativo.quantidade.toLocaleString('pt-BR', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
                document.getElementById('visor-unit-produto').textContent = `R$ ${ativo.preco_unitario.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
                document.getElementById('visor-total-produto').textContent = `R$ ${ativo.subtotal.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;

                const img = document.getElementById('pdv-produto-foto');
                const banner = document.getElementById('pdv-banner-institucional');
                if (ativo.foto_url) {
                    img.src = ativo.foto_url;
                    img.classList.remove('hidden');
                    banner.classList.add('hidden');
                } else {
                    img.classList.add('hidden');
                    banner.classList.remove('hidden');
                }

                tbody.innerHTML = pdvState.itens.map((it, idx) => `
                    <tr onclick="selecionarItem(${idx})" class="cursor-pointer ${idx === pdvState.indiceSelecionado ? 'bg-blue-50 font-bold' : 'hover:bg-slate-50'}">
                        <td class="py-2 px-2 font-mono text-xs text-slate-500">${String(idx + 1).padStart(3, '0')}</td>
                        <td class="py-2 px-2">
                            <div class="text-slate-900 uppercase">${it.nome}</div>
                            <div class="text-[11px] font-mono text-slate-400">${it.codigo || ''}</div>
                        </td>
                        <td class="py-2 px-2 text-right font-mono">${it.quantidade.toLocaleString('pt-BR', { minimumFractionDigits: 3, maximumFractionDigits: 3 })} ${it.unidade || 'UN'}</td>
                        <td class="py-2 px-2 text-right font-mono">R$ ${it.preco_unitario.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
                        <td class="py-2 px-2 text-right font-mono font-black text-slate-950">R$ ${it.subtotal.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
                    </tr>
                `).join('');
                tbody.lastElementChild?.scrollIntoView({ block: 'nearest' });
            }

            const bruto = pdvState.itens.reduce((acc, i) => acc + i.subtotal, 0);
            const total = Math.max(0, bruto - pdvState.desconto);
            document.getElementById('rodape-qtd-itens').textContent = pdvState.itens.length;
            document.getElementById('rodape-subtotal').textContent = total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const wrapDesc = document.getElementById('rodape-desconto-wrap');
            if (pdvState.desconto > 0) {
                wrapDesc.classList.remove('hidden');
                document.getElementById('rodape-desconto-val').textContent = pdvState.desconto.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            } else {
                wrapDesc.classList.add('hidden');
            }
        }

        function selecionarItem(idx) {
            pdvState.indiceSelecionado = idx;
            renderizarCupom();
        }

        function removerItemSelecionado() {
            if (pdvState.itens.length === 0) return;
            const idx = pdvState.indiceSelecionado >= 0 ? pdvState.indiceSelecionado : pdvState.itens.length - 1;
            pdvState.itens.splice(idx, 1);
            pdvState.indiceSelecionado = pdvState.itens.length - 1;
            renderizarCupom();
        }

        function cancelarCupomAtual() {
            if (pdvState.itens.length === 0) return;
            if (!confirm('Cancelar e limpar todos os itens do cupom atual?')) return;
            pdvState.itens = [];
            pdvState.indiceSelecionado = -1;
            pdvState.desconto = 0;
            renderizarCupom();
            inputCodigo.focus();
        }

        // =========================================================================
        // BALANÇA VIA WEB SERIAL API (TOLEDO / FILIZOLA / URANO / ELGIN)
        // =========================================================================
        async function conectarBalancaSerial() {
            if (!('serial' in navigator)) {
                alert('Seu navegador não suporta Web Serial API. Use Google Chrome ou Microsoft Edge.');
                return;
            }
            try {
                const port = await navigator.serial.requestPort();
                await port.open({ baudRate: pdvState.config.balanca_baud_rate || 9600 });
                pdvState.balancaConectada = true;
                const btn = document.getElementById('btn-conectar-balanca');
                btn.textContent = 'Conectada';
                btn.className = 'px-2 py-0.5 bg-emerald-600 text-white text-[11px] font-bold rounded-lg';

                const decoder = new TextDecoderStream();
                port.readable.pipeTo(decoder.writable);
                const reader = decoder.readable.getReader();
                let buffer = '';

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;
                    if (value) {
                        buffer += value;
                        const match = buffer.match(/(\d{1,3}[.,]\d{3}|\d{5,6})/);
                        if (match) {
                            let valStr = match[1].replace(',', '.');
                            let kg = valStr.includes('.') ? parseFloat(valStr) : parseInt(valStr, 10) / 1000;
                            if (!isNaN(kg)) {
                                pdvState.pesoAtualBalanca = kg;
                                document.getElementById('visor-peso-balanca').textContent = `${kg.toLocaleString('pt-BR', { minimumFractionDigits: 3, maximumFractionDigits: 3 })} kg`;
                            }
                            buffer = '';
                        }
                    }
                }
            } catch (e) {
                console.warn('Erro ao conectar balança serial:', e);
            }
        }

        function usarPesoBalancaNoUltimoItem() {
            if (pdvState.itens.length === 0) return;
            if (pdvState.pesoAtualBalanca <= 0) {
                abrirModalAlterarQtd();
                return;
            }
            const idx = pdvState.indiceSelecionado >= 0 ? pdvState.indiceSelecionado : pdvState.itens.length - 1;
            const item = pdvState.itens[idx];
            const pesoLiq = Math.max(0.001, pdvState.pesoAtualBalanca - (item.tara_padrao || 0));
            item.quantidade = Number(pesoLiq.toFixed(3));
            item.subtotal = Number((item.quantidade * item.preco_unitario).toFixed(2));
            renderizarCupom();
        }

        // =========================================================================
        // MODAIS E AÇÕES DE OPERAÇÃO
        // =========================================================================
        function fecharModais() {
            document.querySelectorAll('[id^="modal-"]').forEach(m => m.classList.add('hidden'));
            setTimeout(() => inputCodigo.focus(), 50);
        }

        function abrirModalBusca() {
            document.getElementById('modal-busca').classList.remove('hidden');
            const inp = document.getElementById('input-busca-produto');
            inp.value = '';
            inp.focus();
            buscarProdutosModal('');
        }

        let timerBusca = null;
        document.getElementById('input-busca-produto').addEventListener('input', (e) => {
            clearTimeout(timerBusca);
            timerBusca = setTimeout(() => buscarProdutosModal(e.target.value), 200);
        });

        async function buscarProdutosModal(q) {
            const lista = document.getElementById('lista-resultado-busca');
            try {
                const res = await fetch(`<?= Url::to(['/pdv/frente/buscar-produtos']) ?>?q=${encodeURIComponent(q)}`);
                const data = await res.json();
                const prods = data.produtos || [];
                if (prods.length === 0) {
                    lista.innerHTML = '<div class="p-6 text-center text-slate-400 text-sm">Nenhum produto encontrado.</div>';
                    return;
                }
                lista.innerHTML = prods.map(p => `
                    <button type="button" onclick='selecionarProdutoBusca(${JSON.stringify(p)})' class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center justify-between transition">
                        <div>
                            <div class="font-bold text-slate-900 text-sm">${p.nome}</div>
                            <div class="text-xs font-mono text-slate-400">${p.codigo || 'Sem código'} • Estoque: ${p.estoque_atual} ${p.unidade}</div>
                        </div>
                        <div class="font-mono font-black text-blue-900 text-sm">
                            R$ ${p.preco_unitario.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                        </div>
                    </button>
                `).join('');
            } catch (_) {
                lista.innerHTML = '<div class="p-6 text-center text-rose-500 text-sm">Erro ao buscar produtos.</div>';
            }
        }

        function selecionarProdutoBusca(p) {
            adicionarItemAoCupom({
                produto_id: p.produto_id,
                variante_id: p.variante_id || null,
                codigo: p.codigo,
                nome: p.nome,
                unidade: p.unidade,
                preco_unitario: p.preco_unitario,
                quantidade: 1,
                subtotal: p.preco_unitario,
                balanca_pesavel: p.balanca_pesavel,
                venda_fracionada: p.venda_fracionada,
                tara_padrao: p.tara_padrao || 0,
                foto_url: p.foto_url || null,
            });
            tocarBeep('ok');
            fecharModais();
        }

        function abrirModalAlterarQtd() {
            if (pdvState.itens.length === 0) return;
            const idx = pdvState.indiceSelecionado >= 0 ? pdvState.indiceSelecionado : pdvState.itens.length - 1;
            const item = pdvState.itens[idx];
            document.getElementById('modal-qtd-nome').textContent = item.nome;
            document.getElementById('input-nova-qtd').value = item.quantidade;
            document.getElementById('modal-qtd').classList.remove('hidden');
            document.getElementById('input-nova-qtd').select();
        }

        function capturarPesoParaModalQtd() {
            if (pdvState.pesoAtualBalanca > 0) {
                document.getElementById('input-nova-qtd').value = pdvState.pesoAtualBalanca.toFixed(3);
            } else {
                alert('Balança sem leitura de peso no momento.');
            }
        }

        function confirmarAlteracaoQtd() {
            const qtd = parseFloat(document.getElementById('input-nova-qtd').value || '0');
            if (qtd <= 0) return;
            const idx = pdvState.indiceSelecionado >= 0 ? pdvState.indiceSelecionado : pdvState.itens.length - 1;
            const item = pdvState.itens[idx];
            item.quantidade = Number(qtd.toFixed(3));
            item.subtotal = Number((item.quantidade * item.preco_unitario).toFixed(2));
            renderizarCupom();
            fecharModais();
        }

        function abrirModalDesconto() {
            document.getElementById('input-desconto-reais').value = pdvState.desconto.toFixed(2);
            document.getElementById('modal-desconto').classList.remove('hidden');
            document.getElementById('input-desconto-reais').select();
        }

        function confirmarDesconto() {
            pdvState.desconto = Math.max(0, parseFloat(document.getElementById('input-desconto-reais').value || '0'));
            renderizarCupom();
            fecharModais();
        }

        function abrirModalMovimentacao() { document.getElementById('modal-movimentacao').classList.remove('hidden'); }
        function abrirModalCaixa() { document.getElementById('modal-abrir-caixa').classList.remove('hidden'); }
        function abrirModalConfig() { document.getElementById('modal-config').classList.remove('hidden'); }

        async function confirmarAberturaCaixa() {
            const valor = parseFloat(document.getElementById('caixa-valor-inicial').value || '0');
            const res = await fetch('<?= Url::to(['/pdv/frente/abrir-caixa']) ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ valor_inicial: valor })
            });
            const data = await res.json();
            if (data.success) {
                pdvState.caixaAberto = true;
                const badge = document.getElementById('badge-status-caixa');
                badge.textContent = '● CAIXA ABERTO';
                badge.className = 'px-3 py-1 rounded-full text-xs font-black tracking-wide bg-emerald-500/20 text-emerald-300 border border-emerald-400/40';
                badge.onclick = null;
                fecharModais();
            } else {
                alert(data.message || 'Erro ao abrir caixa');
            }
        }

        async function salvarMovimentacaoCaixa() {
            const tipo = document.getElementById('mov-tipo').value;
            const valor = parseFloat(document.getElementById('mov-valor').value || '0');
            const descricao = document.getElementById('mov-desc').value;
            const res = await fetch('<?= Url::to(['/pdv/frente/movimentacao-caixa']) ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tipo, valor, descricao })
            });
            const data = await res.json();
            alert(data.message || 'Registrado');
            if (data.success) fecharModais();
        }

        async function salvarConfiguracoesPdv() {
            const payload = {
                etiqueta_prefixo: document.getElementById('cfg-prefixo').value,
                etiqueta_tamanho_codigo: parseInt(document.getElementById('cfg-tam-codigo').value, 10),
                etiqueta_tipo_valor: document.getElementById('cfg-tipo-valor').value,
                balanca_protocolo: document.getElementById('cfg-protocolo').value,
                balanca_baud_rate: parseInt(document.getElementById('cfg-baud').value, 10),
                som_leitor_ativo: document.getElementById('cfg-som').checked,
                imprimir_cupom_auto: document.getElementById('cfg-imp-auto').checked,
            };
            const res = await fetch('<?= Url::to(['/pdv/frente/salvar-config']) ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                pdvState.config = Object.assign(pdvState.config, payload);
                fecharModais();
            } else {
                alert(data.message || 'Erro ao salvar');
            }
        }

        let pagamentosMisto = [];

        function getTotalCupomAtual() {
            const bruto = pdvState.itens.reduce((acc, i) => acc + i.subtotal, 0);
            return Math.max(0.01, Number((bruto - pdvState.desconto).toFixed(2)));
        }

        function abrirModalFinalizar() {
            if (pdvState.itens.length === 0) {
                alert('O cupom está vazio.');
                return;
            }
            const total = getTotalCupomAtual();
            document.getElementById('fin-total-pagar').textContent = `R$ ${total.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;

            const fpPadrao = pdvState.formasPagamento[0] || { id: '', nome: 'DINHEIRO' };
            pagamentosMisto = [{
                forma_pagamento_id: fpPadrao.id,
                nome: fpPadrao.nome,
                valor: total,
            }];
            renderizarLinhasPagamento();
            document.getElementById('modal-finalizar').classList.remove('hidden');
            setTimeout(() => {
                const primeiroInput = document.querySelector('#fin-lista-pagamentos input[type="number"]');
                if (primeiroInput) primeiroInput.select();
            }, 40);
        }

        function adicionarLinhaPagamento() {
            const total = getTotalCupomAtual();
            const somaAtual = pagamentosMisto.reduce((acc, p) => acc + (parseFloat(p.valor) || 0), 0);
            const restante = Math.max(0, Number((total - somaAtual).toFixed(2)));
            const fp = pdvState.formasPagamento[Math.min(pagamentosMisto.length, pdvState.formasPagamento.length - 1)] || { id: '', nome: 'PIX' };

            pagamentosMisto.push({
                forma_pagamento_id: fp.id,
                nome: fp.nome,
                valor: restante,
            });
            renderizarLinhasPagamento();
        }

        function removerLinhaPagamento(idx) {
            if (pagamentosMisto.length <= 1) return;
            pagamentosMisto.splice(idx, 1);
            renderizarLinhasPagamento();
        }

        function renderizarLinhasPagamento() {
            const container = document.getElementById('fin-lista-pagamentos');
            container.innerHTML = pagamentosMisto.map((pg, idx) => `
                <div class="flex items-center gap-2">
                    <select onchange="atualizarFormaLinhaPagamento(${idx}, this)" class="flex-1 border-2 border-slate-300 rounded-xl px-3 py-2 text-sm font-bold">
                        ${pdvState.formasPagamento.map(fp => `
                            <option value="${fp.id}" data-nome="${fp.nome}" ${String(fp.id) === String(pg.forma_pagamento_id) ? 'selected' : ''}>${fp.nome}</option>
                        `).join('')}
                    </select>
                    <input type="number" step="0.01" min="0" value="${Number(pg.valor || 0).toFixed(2)}" oninput="atualizarValorLinhaPagamento(${idx}, this.value)" class="w-36 border-2 border-slate-300 rounded-xl px-3 py-2 font-mono font-black text-base text-right">
                    ${pagamentosMisto.length > 1 ? `<button type="button" onclick="removerLinhaPagamento(${idx})" class="px-2.5 py-2 bg-rose-100 hover:bg-rose-200 text-rose-700 font-black rounded-xl text-xs">✕</button>` : ''}
                </div>
            `).join('');
            recalcularTroco();
        }

        function atualizarFormaLinhaPagamento(idx, sel) {
            const opt = sel.options[sel.selectedIndex];
            pagamentosMisto[idx].forma_pagamento_id = sel.value || null;
            pagamentosMisto[idx].nome = opt ? opt.dataset.nome : 'DINHEIRO';
        }

        function atualizarValorLinhaPagamento(idx, val) {
            pagamentosMisto[idx].valor = Math.max(0, parseFloat(val || '0'));
            recalcularTroco();
        }

        function recalcularTroco() {
            const total = getTotalCupomAtual();
            const recebido = pagamentosMisto.reduce((acc, p) => acc + (parseFloat(p.valor) || 0), 0);
            const troco = Math.max(0, recebido - total);
            const falta = Math.max(0, total - recebido);
            document.getElementById('fin-troco').textContent = `R$ ${troco.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;

            const elFalta = document.getElementById('fin-falta-pagar');
            if (falta > 0.009) {
                elFalta.textContent = `Falta pagar: R$ ${falta.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
                elFalta.classList.remove('hidden');
            } else {
                elFalta.classList.add('hidden');
            }
        }

        async function confirmarFinalizacaoVenda(imprimir) {
            const total = getTotalCupomAtual();
            const recebido = pagamentosMisto.reduce((acc, p) => acc + (parseFloat(p.valor) || 0), 0);
            if (recebido + 0.009 < total) {
                alert(`Valor informado (R$ ${recebido.toFixed(2)}) é menor que o total da venda (R$ ${total.toFixed(2)}).`);
                return;
            }

            const pagamentosValidos = pagamentosMisto
                .filter(p => (parseFloat(p.valor) || 0) > 0)
                .map(p => ({
                    forma_pagamento_id: p.forma_pagamento_id || null,
                    nome: p.nome || 'DINHEIRO',
                    valor: Number(parseFloat(p.valor).toFixed(2)),
                }));

            const agoraIso = new Date().toISOString().slice(0, 19).replace('T', ' ');
            const offlineId = 'pdv_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);
            const payload = {
                offline_id: offlineId,
                data_venda: agoraIso,
                itens: pdvState.itens,
                desconto: pdvState.desconto,
                cpf_consumidor: document.getElementById('fin-cpf').value,
                pagamentos: pagamentosValidos
            };

            const itensCopia = [...pdvState.itens];
            const descCopia = pdvState.desconto;
            const pagamentosCopia = [...pagamentosValidos];
            const cpfCopia = document.getElementById('fin-cpf').value.trim();
            const trocoCopia = Math.max(0, Number((recebido - total).toFixed(2)));

            const concluirLocal = (vendaInfo, msgFeedback, clsFeedback) => {
                pdvState.ultimaVenda = {
                    venda: vendaInfo,
                    itens: itensCopia,
                    desconto: descCopia,
                    pagamentos: pagamentosCopia,
                    cpf: cpfCopia,
                    troco: trocoCopia,
                };
                try {
                    localStorage.setItem('pulse_pdv_ultima_venda', JSON.stringify(pdvState.ultimaVenda));
                } catch (_) {}

                pdvState.itens = [];
                pdvState.indiceSelecionado = -1;
                pdvState.desconto = 0;
                document.getElementById('fin-cpf').value = '';
                renderizarCupom();
                fecharModais();
                setFeedback(msgFeedback, clsFeedback);

                // Exibe modal pós-venda com opções de comprovante e NFC-e
                abrirModalPosVenda(vendaInfo);

                if (imprimir || pdvState.config.imprimir_cupom_auto) {
                    imprimirCupomTermico(pdvState.ultimaVenda);
                }
            };

            if (!navigator.onLine) {
                salvarVendaOffline(payload);
                concluirLocal(
                    { numero_cupom: 'OFF-' + offlineId.slice(-4).toUpperCase(), valor_total: total, data_hora: new Date().toLocaleString('pt-BR') },
                    '⏳ Sem internet: venda salva na fila offline!',
                    'text-amber-300'
                );
                return;
            }

            try {
                const res = await fetch('<?= Url::to(['/pdv/frente/finalizar-venda']) ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                if (data.success) {
                    concluirLocal(data, `✓ Venda #${data.numero_cupom} finalizada!`, 'text-emerald-300');
                } else {
                    alert(data.message || 'Erro ao finalizar venda');
                }
            } catch (_) {
                salvarVendaOffline(payload);
                concluirLocal(
                    { numero_cupom: 'OFF-' + offlineId.slice(-4).toUpperCase(), valor_total: total, data_hora: new Date().toLocaleString('pt-BR') },
                    '⏳ Conexão instável: venda salva na fila offline!',
                    'text-amber-300'
                );
            }
        }

        function obterVendasOffline() {
            try {
                return JSON.parse(localStorage.getItem('pulse_pdv_vendas_offline') || '[]');
            } catch (_) {
                return [];
            }
        }

        function salvarVendaOffline(payload) {
            const fila = obterVendasOffline();
            fila.push(payload);
            localStorage.setItem('pulse_pdv_vendas_offline', JSON.stringify(fila));
            atualizarBadgeVendasOffline();
        }

        function atualizarBadgeVendasOffline() {
            const qtd = obterVendasOffline().length;
            const btn = document.getElementById('btn-sync-pdv-offline');
            const badge = document.getElementById('badge-qtd-pdv-offline');
            if (!btn || !badge) return;
            badge.textContent = qtd;
            if (qtd > 0) btn.classList.remove('hidden');
            else btn.classList.add('hidden');
        }

        async function sincronizarVendasOffline(silencioso = true) {
            const fila = obterVendasOffline();
            if (!fila.length || !navigator.onLine) return;

            try {
                const res = await fetch('<?= Url::to(['/pdv/frente/sincronizar-offline']) ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ vendas_offline: fila })
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                const idsOk = new Set((data.sincronizados || []).map(s => s.offline_id));
                const restantes = fila.filter(v => !idsOk.has(v.offline_id));
                if (restantes.length > 0) {
                    localStorage.setItem('pulse_pdv_vendas_offline', JSON.stringify(restantes));
                } else {
                    localStorage.removeItem('pulse_pdv_vendas_offline');
                }
                atualizarBadgeVendasOffline();

                if (!silencioso) {
                    const erros = data.erros || [];
                    if (erros.length > 0) {
                        alert(`Sincronizadas: ${data.total_sincronizados || 0}. Falhas: ${erros.map(e => e.erro).join('; ')}`);
                    } else {
                        alert(`✓ ${data.total_sincronizados || 0} venda(s) offline sincronizada(s) com sucesso!`);
                    }
                }
            } catch (e) {
                if (!silencioso) alert('Não foi possível sincronizar as vendas offline agora.');
            }
        }

        function abrirModalPosVenda(vendaInfo) {
            document.getElementById('pos-numero-cupom').textContent = `#${vendaInfo.numero_cupom}`;
            document.getElementById('pos-valor-total').textContent = `R$ ${Number(vendaInfo.valor_total || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;

            const temVendaId = !!vendaInfo.venda_id;
            document.getElementById('btn-pos-comprovante-oficial').classList.toggle('hidden', !temVendaId);
            document.getElementById('btn-pos-emitir-nfce').classList.toggle('hidden', !temVendaId);

            const st = document.getElementById('pos-nfce-status');
            st.classList.add('hidden');
            st.textContent = '';

            document.getElementById('modal-pos-venda').classList.remove('hidden');
        }

        function reimprimirUltimoCupom() {
            let dados = pdvState.ultimaVenda;
            if (!dados) {
                try {
                    dados = JSON.parse(localStorage.getItem('pulse_pdv_ultima_venda') || 'null');
                } catch (_) {}
            }
            if (!dados || !dados.itens || !dados.itens.length) {
                alert('Nenhum cupom recente disponível para reimpressão.');
                return;
            }
            imprimirCupomTermico(dados);
        }

        function abrirComprovanteOficialPdv() {
            const vendaId = pdvState.ultimaVenda && pdvState.ultimaVenda.venda && pdvState.ultimaVenda.venda.venda_id;
            if (!vendaId) {
                alert('Esta venda foi salva offline e ainda não possui ID no servidor.');
                return;
            }
            window.open(`<?= Url::to(['/vendas/venda/imprimir']) ?>?id=${encodeURIComponent(vendaId)}`, '_blank');
        }

        async function emitirNfcePdv() {
            const vendaId = pdvState.ultimaVenda && pdvState.ultimaVenda.venda && pdvState.ultimaVenda.venda.venda_id;
            if (!vendaId) {
                alert('Sincronize a venda offline antes de emitir NFC-e.');
                return;
            }
            const btn = document.getElementById('btn-pos-emitir-nfce');
            const st = document.getElementById('pos-nfce-status');
            btn.disabled = true;
            btn.textContent = 'Emitindo NFC-e...';

            try {
                const res = await fetch('<?= Url::to(['/pdv/frente/emitir-nfce']) ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ venda_id: vendaId })
                });
                const data = await res.json();
                st.classList.remove('hidden');
                if (data.success) {
                    st.className = 'text-xs font-bold rounded-xl p-2.5 bg-emerald-50 text-emerald-800 border border-emerald-200';
                    st.textContent = `✓ ${data.message}${data.chave_acesso ? ' Chave: ' + data.chave_acesso : ''}`;
                    if (data.pdf_url) {
                        window.open(data.pdf_url, '_blank');
                    }
                } else {
                    st.className = 'text-xs font-bold rounded-xl p-2.5 bg-rose-50 text-rose-800 border border-rose-200';
                    st.textContent = data.message || 'Falha ao emitir NFC-e.';
                }
            } catch (_) {
                st.classList.remove('hidden');
                st.className = 'text-xs font-bold rounded-xl p-2.5 bg-rose-50 text-rose-800 border border-rose-200';
                st.textContent = 'Erro de comunicação ao emitir NFC-e.';
            } finally {
                btn.disabled = false;
                btn.textContent = '🧾 Emitir / Imprimir Cupom Fiscal (NFC-e)';
            }
        }

        function imprimirCupomTermico(registro) {
            const venda = registro.venda || {};
            const itens = registro.itens || [];
            const desconto = Number(registro.desconto || 0);
            const pagamentos = registro.pagamentos || [];
            const cpf = registro.cpf || '';
            const troco = Number(registro.troco || 0);
            const loja = pdvState.lojaDados || { nome: pdvState.lojaNome };

            const area = document.getElementById('area-cupom-impressao');
            area.innerHTML = `
                <div style="text-align:center;font-weight:bold;font-size:13px;margin-bottom:2px;">${loja.nome || pdvState.lojaNome}</div>
                ${loja.documento ? `<div style="text-align:center;font-size:10px;">CNPJ/CPF: ${loja.documento}</div>` : ''}
                ${loja.endereco ? `<div style="text-align:center;font-size:10px;">${loja.endereco}</div>` : ''}
                ${loja.telefone ? `<div style="text-align:center;font-size:10px;">Tel: ${loja.telefone}</div>` : ''}
                <div style="text-align:center;font-size:10px;margin:6px 0;">
                    <strong>CUPOM NAO FISCAL #${venda.numero_cupom || ''}</strong><br>
                    ${venda.data_hora || ''}
                </div>
                ${cpf ? `<div style="font-size:10px;margin-bottom:4px;">CONSUMIDOR CPF/CNPJ: ${cpf}</div>` : ''}
                <hr style="border-top:1px dashed #000;margin:4px 0;">
                ${itens.map((i, idx) => `
                    <div style="margin-bottom:4px;">
                        <div style="font-weight:bold;">${String(idx + 1).padStart(2, '0')}. ${i.nome}</div>
                        <div style="display:flex;justify-content:space-between;">
                            <span>${Number(i.quantidade).toFixed(3)} ${i.unidade || 'UN'} x R$ ${Number(i.preco_unitario).toFixed(2)}</span>
                            <span>R$ ${Number(i.subtotal).toFixed(2)}</span>
                        </div>
                    </div>
                `).join('')}
                <hr style="border-top:1px dashed #000;margin:4px 0;">
                ${desconto > 0 ? `<div style="display:flex;justify-content:space-between;"><span>Desconto:</span><span>-R$ ${desconto.toFixed(2)}</span></div>` : ''}
                <div style="display:flex;justify-content:space-between;font-weight:bold;font-size:13px;margin-bottom:4px;">
                    <span>TOTAL:</span><span>R$ ${Number(venda.valor_total || 0).toFixed(2)}</span>
                </div>
                ${pagamentos.map(p => `
                    <div style="display:flex;justify-content:space-between;font-size:10px;">
                        <span>${p.nome || 'Pagamento'}:</span>
                        <span>R$ ${Number(p.valor || 0).toFixed(2)}</span>
                    </div>
                `).join('')}
                ${troco > 0 ? `<div style="display:flex;justify-content:space-between;font-weight:bold;font-size:11px;margin-top:2px;"><span>TROCO:</span><span>R$ ${troco.toFixed(2)}</span></div>` : ''}
                <hr style="border-top:1px dashed #000;margin:6px 0;">
                <div style="text-align:center;font-size:10px;">
                    Atendido por: ${pdvState.operadorNome}<br>
                    Obrigado pela preferencia!
                </div>
            `;

            area.classList.remove('hidden');
            const limparPosImpressao = () => {
                area.classList.add('hidden');
                window.removeEventListener('afterprint', limparPosImpressao);
            };
            window.addEventListener('afterprint', limparPosImpressao);
            setTimeout(() => {
                window.print();
            }, 150);
        }

        // =========================================================================
        // CACHE OFFLINE DE CATÁLOGO (SUPORTA QTD*COD E ETIQUETA DE BALANÇA)
        // =========================================================================
        async function atualizarCatalogoOffline(comAlerta = false) {
            try {
                const res = await fetch('<?= Url::to(['/pdv/frente/catalogo-offline']) ?>');
                const data = await res.json();
                if (data.success) {
                    localStorage.setItem('pulse_pdv_catalogo', JSON.stringify(data.catalogo || []));
                    if (comAlerta) alert(`✓ Catálogo offline atualizado (${data.total} produtos).`);
                }
            } catch (_) {
                if (comAlerta) alert('Sem conexão para atualizar catálogo.');
            }
        }

        function buscarNoCatalogoOffline(rawInput) {
            try {
                let raw = (rawInput || '').trim();
                let multiplicador = 1;
                if (raw.includes('*')) {
                    const [qStr, codStr] = raw.split('*', 2);
                    const qParsed = parseFloat(qStr.replace(',', '.'));
                    if (qParsed > 0) multiplicador = qParsed;
                    raw = (codStr || '').trim();
                }

                const cat = JSON.parse(localStorage.getItem('pulse_pdv_catalogo') || '[]');
                const prefixo = String(pdvState.config.etiqueta_prefixo || '2');
                const tamCod = parseInt(pdvState.config.etiqueta_tamanho_codigo || 5, 10);

                let p = null;
                let qtdFinal = multiplicador;

                // Etiqueta de balança (13 dígitos iniciando com prefixo configurado)
                if (/^\d{13}$/.test(raw) && prefixo && raw.startsWith(prefixo)) {
                    const pluBruto = raw.substring(prefixo.length, prefixo.length + tamCod);
                    const pluSemZero = pluBruto.replace(/^0+/, '') || '0';
                    const valOuPeso = parseInt(raw.substring(7, 12), 10);

                    p = cat.find(i => i.codigo_balanca === pluBruto || i.codigo_balanca === pluSemZero || i.codigo_barras === pluBruto || i.codigo_referencia === pluBruto);
                    if (p) {
                        if (pdvState.config.etiqueta_tipo_valor === 'PRECO' && p.preco_unitario > 0) {
                            qtdFinal = Number((((valOuPeso / 100) / p.preco_unitario) * multiplicador).toFixed(3));
                        } else {
                            qtdFinal = Number(((valOuPeso / 1000) * multiplicador).toFixed(3));
                        }
                        if (qtdFinal <= 0) qtdFinal = 0.001;
                    }
                }

                if (!p) {
                    p = cat.find(i => i.codigo_barras === raw || i.codigo_balanca === raw || i.codigo_referencia === raw);
                }
                if (!p) return null;

                return {
                    produto_id: p.produto_id,
                    variante_id: null,
                    codigo: p.codigo_barras || p.codigo_balanca || raw,
                    nome: p.nome,
                    unidade: p.unidade,
                    preco_unitario: p.preco_unitario,
                    quantidade: qtdFinal,
                    subtotal: Number((p.preco_unitario * qtdFinal).toFixed(2)),
                    balanca_pesavel: p.balanca_pesavel,
                    venda_fracionada: p.venda_fracionada,
                    tara_padrao: p.tara_padrao || 0,
                    foto_url: null,
                };
            } catch (_) {
                return null;
            }
        }

        window.addEventListener('online', () => {
            sincronizarVendasOffline(true);
            atualizarCatalogoOffline(false);
        });

        document.addEventListener('DOMContentLoaded', () => {
            atualizarBadgeVendasOffline();
            if (navigator.onLine) {
                sincronizarVendasOffline(true);
                atualizarCatalogoOffline(false);
            }
        });
    </script>
</body>
</html>

