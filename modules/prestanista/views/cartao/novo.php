<?php
/** @var yii\web\View $this */
/** @var app\modules\vendas\models\Cliente[] $clientes */
/** @var app\modules\vendas\models\Produto[] $produtos */
/** @var app\modules\vendas\models\Colaborador[] $vendedores */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Emitir Novo Cartão de Crediário';
?>

<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <span>➕</span>
                <span>Emitir Novo Cartão de Crediário</span>
            </h1>
            <p class="text-xs text-slate-400">
                Preencha os dados do cliente, as mercadorias vendidas e as condições de pagamento a prazo.
            </p>
        </div>
        <a href="<?= Url::to(['/prestanista/cartao/index']) ?>" class="text-xs font-bold text-slate-400 hover:text-white">
            ✕ Cancelar
        </a>
    </div>

    <form method="post" action="<?= Url::to(['/prestanista/cartao/novo']) ?>" id="formNovoCartao" class="bg-slate-950 border border-slate-800 p-6 rounded-3xl shadow-xl space-y-6">
        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>" />

        <!-- 1. Cliente & Vendedor -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="min-w-0">
                <div class="flex items-center justify-between mb-1.5 gap-2">
                    <label class="block text-xs font-black text-slate-300 uppercase tracking-wider truncate">Cliente (Comprador) *</label>
                    <button type="button" onclick="abrirModalNovoCliente()" class="shrink-0 text-[11px] font-black text-amber-400 hover:text-amber-300 flex items-center gap-1 bg-amber-500/10 hover:bg-amber-500/20 px-2.5 py-1 rounded-lg border border-amber-500/30 transition">
                        <span>➕</span> <span>Novo Cliente</span>
                    </button>
                </div>
                <div class="flex gap-2 min-w-0 items-center">
                    <select name="cliente_id" id="select-cliente" required class="flex-1 min-w-0 h-12 px-3.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:border-amber-500 focus:outline-none truncate">
                        <option value="">Selecione o Cliente...</option>
                        <?php foreach ($clientes as $cl): ?>
                            <option value="<?= $cl->id ?>"><?= Html::encode($cl->nome) ?> (<?= Html::encode($cl->bairro ?? 'Sem bairro') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" onclick="abrirModalNovoCliente()" title="Cadastrar Cliente Novo Sem Sair da Tela" class="shrink-0 w-11 h-12 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-xl flex items-center justify-center text-sm shadow-md transition active:scale-95">
                        ➕
                    </button>
                </div>
                <p id="clienteCadastradoFeedback" class="hidden text-xs text-emerald-400 font-bold mt-1.5 flex items-center gap-1 truncate">
                    <span>✅</span> <span id="clienteFeedbackTexto"></span>
                </p>
            </div>

            <div class="min-w-0">
                <label class="block text-xs font-black text-slate-300 uppercase tracking-wider mb-1.5 truncate">Vendedor Ambulante</label>
                <select name="vendedor_id" class="w-full min-w-0 h-12 px-3.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:border-amber-500 focus:outline-none truncate">
                    <option value="">Selecione o Vendedor...</option>
                    <?php foreach ($vendedores as $v): ?>
                        <option value="<?= $v->id ?>"><?= Html::encode($v->nome) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- 2. Adicionar Objetos / Mercadorias -->
        <div class="border-t border-slate-800 pt-4 space-y-3">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <label class="text-xs font-black text-slate-300 uppercase tracking-wider">Objetos Vendidos (Mercadorias)</label>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="abrirModalCadastroRapido()" class="text-[11px] font-black text-emerald-400 hover:text-emerald-300 flex items-center gap-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 px-2.5 py-1 rounded-lg border border-emerald-500/30 transition shadow-xs active:scale-95">
                        <span>⚡</span>
                        <span>Cadastrar Produto Expresso</span>
                    </button>
                    <button type="button" onclick="adicionarLinhaProduto()" class="text-xs font-black text-amber-400 hover:text-amber-300 flex items-center gap-1 bg-amber-500/10 hover:bg-amber-500/20 px-2.5 py-1 rounded-lg border border-amber-500/30 transition shadow-xs active:scale-95">
                        <span>+</span>
                        <span>Adicionar Mercadoria</span>
                    </button>
                </div>
            </div>

            <div id="containerItensVenda" class="space-y-2.5">
                <!-- Linha 1 padrão -->
                <div class="grid grid-cols-12 gap-2 item-venda-linha">
                    <div class="col-span-7 min-w-0">
                        <select name="itens[0][produto_id]" required onchange="atualizarPrecoProduto(this, 0)" class="w-full min-w-0 h-11 px-3 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none truncate">
                            <option value="">Selecione a mercadoria...</option>
                            <?php foreach ($produtos as $p): ?>
                                <option value="<?= $p->id ?>" data-preco="<?= (float)$p->preco ?>"><?= Html::encode($p->nome) ?> (R$ <?= number_format($p->preco, 2, ',', '.') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <input type="number" name="itens[0][quantidade]" value="1" min="1" required oninput="calcularTotalCartao()" class="w-full h-11 text-center bg-slate-900 border border-slate-700 rounded-xl text-xs text-white font-bold" placeholder="Qtd">
                    </div>
                    <div class="col-span-3">
                        <input type="number" step="0.01" name="itens[0][preco]" id="preco_item_0" required oninput="calcularTotalCartao()" class="w-full h-11 px-3 text-right bg-slate-900 border border-slate-700 rounded-xl text-xs text-white font-bold" placeholder="R$ Valor">
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Datas Chave e Condições de Pagamento -->
        <div class="border-t border-slate-800 pt-4 space-y-4">
            
            <div class="flex items-center justify-between">
                <label class="text-xs font-black text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                    <span>🗓️</span>
                    <span>Datas e Condições de Pagamento</span>
                </label>
                <span class="text-[11px] text-amber-400 font-bold bg-amber-500/10 border border-amber-500/20 px-2.5 py-0.5 rounded-full">
                    Cálculo Automático
                </span>
            </div>

            <!-- Linha 1: Datas Chave -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-900/60 p-3.5 rounded-2xl border border-slate-800/80">
                <div class="min-w-0">
                    <label class="block text-xs font-black text-slate-300 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                        <span>📅 Data da Venda</span>
                        <span class="text-[10px] text-slate-400 font-normal">Realização da venda</span>
                    </label>
                    <input type="date" name="data_venda" id="inputDataVenda" value="<?= date('Y-m-d') ?>" onchange="aoMudarDataVenda()" class="w-full min-w-0 h-11 px-3 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-white font-bold focus:border-amber-500 focus:outline-none transition">
                </div>

                <div class="min-w-0">
                    <label class="block text-xs font-black text-amber-400 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                        <span>🗓️ Data da 1ª Parcela *</span>
                        <span class="text-[10px] text-amber-400/80 font-normal">Base dos próximos vencimentos</span>
                    </label>
                    <input type="date" name="primeiro_vencimento" id="inputPrimeiroVencimento" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" onchange="aoMudarPrimeiroVencimento()" required class="w-full min-w-0 h-11 px-3 bg-slate-950 border-2 border-amber-500/80 focus:border-amber-400 rounded-xl text-xs sm:text-sm text-amber-300 font-black shadow-sm focus:outline-none transition">
                </div>
            </div>

            <!-- Linha 2: Frequência, Prestações e Entrada -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="min-w-0">
                    <label class="block text-xs font-black text-slate-300 uppercase tracking-wider mb-1.5 truncate">Frequência da Cobrança</label>
                    <select name="frequencia" id="selectFrequencia" onchange="aoMudarFrequencia()" class="w-full min-w-0 h-11 px-3 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none truncate font-bold">
                        <option value="1">DIÁRIA (A cada 1 dia)</option>
                        <option value="7" selected>SEMANAL (A cada 7 dias)</option>
                        <option value="15">QUINZENAL (A cada 15 dias)</option>
                        <option value="30">MENSAL (A cada 30 dias)</option>
                    </select>
                </div>

                <div class="min-w-0">
                    <label class="block text-xs font-black text-slate-300 uppercase tracking-wider mb-1.5 truncate">Nº de Prestações</label>
                    <input type="number" name="numero_parcelas" id="inputNumeroParcelas" value="10" min="1" max="100" oninput="recalcularCronogramaParcelas()" class="w-full min-w-0 h-11 px-3 text-center bg-slate-900 border border-slate-700 rounded-xl text-xs text-white font-black">
                </div>

                <div class="min-w-0">
                    <label class="block text-xs font-black text-slate-300 uppercase tracking-wider mb-1.5 truncate">Entrada no Ato (R$)</label>
                    <input type="text" name="valor_entrada" id="inputValorEntrada" value="0,00" oninput="formatarMoedaInput(this); recalcularCronogramaParcelas()" class="w-full min-w-0 h-11 px-3 text-right bg-slate-900 border border-slate-700 rounded-xl text-xs text-white font-bold" placeholder="0,00">
                </div>
            </div>

            <!-- Painel Visual: Cronograma Previsto das Parcelas (Live Schedule) -->
            <div id="painelCronogramaParcelas" class="bg-gradient-to-br from-slate-900/90 to-slate-950 border border-slate-800 rounded-2xl p-4 space-y-3 shadow-inner">
                <div class="flex items-center justify-between flex-wrap gap-2 border-b border-slate-800/80 pb-2.5">
                    <div>
                        <span class="block text-xs font-black text-white flex items-center gap-1.5">
                            <span>📋</span>
                            <span id="cronogramaTituloResumo">Cronograma: 10 parcelas de R$ 0,00</span>
                        </span>
                        <span class="block text-[11px] text-slate-400" id="cronogramaSubtitulo">Cobrança semanal calculada a partir da 1ª parcela</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-bold text-amber-400 bg-amber-500/10 px-2.5 py-1 rounded-lg border border-amber-500/20" id="badgePrimeiroUltimoVencimento">
                            1º: --/--/---- • Fim: --/--/----
                        </span>
                    </div>
                </div>

                <!-- Lista de Parcelas Dinâmica (Grid de chips/cards) -->
                <div id="containerGradeParcelasPrevistas" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2 max-h-48 overflow-y-auto pr-1 scrollbar-thin">
                    <!-- Gerado dinamicamente via JS -->
                </div>
            </div>

        </div>

        <!-- 4. Resumo do Cartão & Botão de Emissão -->
        <div class="border-t border-slate-800 pt-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <span class="block text-xs text-slate-400">Total das Mercadorias:</span>
                <span id="labelTotalCalculado" class="text-2xl font-black text-amber-400">R$ 0,00</span>
            </div>

            <button type="submit" class="w-full sm:w-auto px-6 py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black text-sm rounded-2xl shadow-lg transition active:scale-95 flex items-center justify-center gap-2">
                <span>📇</span>
                <span>Salvar e Emitir Cartão</span>
            </button>
        </div>

    </form>

</div>

<!-- Modal Cadastro Rápido de Cliente -->
<div id="modalNovoCliente" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4" onclick="handleBackdropClick(event)">
    <div class="relative w-full max-w-lg bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
        
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/80">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 text-lg shadow-inner">
                    👤
                </div>
                <div>
                    <h3 class="text-base font-black text-white tracking-tight">Cadastro Rápido de Cliente</h3>
                    <p class="text-[11px] text-slate-400">Cadastre o comprador sem sair da tela e sem perder os itens</p>
                </div>
            </div>
            <button type="button" onclick="fecharModalNovoCliente()" class="w-8 h-8 rounded-full bg-slate-800/80 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition">
                ✕
            </button>
        </div>

        <!-- Form Modal -->
        <form id="formCadastroClienteRapido" onsubmit="salvarClienteRapido(event)" class="p-6 space-y-4">
            
            <div id="modalClienteErro" class="hidden p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-medium"></div>

            <!-- Dados Pessoais -->
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nome Completo do Cliente *</label>
                    <input type="text" name="nome_completo" id="modal_nome_completo" required placeholder="Ex: Maria José da Silva" class="w-full h-11 px-3.5 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:border-amber-500 focus:outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">WhatsApp / Telefone *</label>
                        <input type="tel" name="telefone" id="modal_telefone" required placeholder="(83) 98888-7777" oninput="formatarTelefone(this)" class="w-full h-11 px-3.5 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:border-amber-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">CPF (Opcional)</label>
                        <input type="text" name="cpf" id="modal_cpf" placeholder="000.000.000-00" oninput="formatarCpf(this)" maxlength="14" class="w-full h-11 px-3.5 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:border-amber-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Endereço e Rota de Cobrança -->
            <div class="pt-3 border-t border-slate-800 space-y-3">
                <div class="flex items-center gap-1.5">
                    <span class="text-xs">📍</span>
                    <span class="text-[11px] font-black text-amber-400 uppercase tracking-wider">Localização & Rota de Cobrança</span>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div class="col-span-2">
                        <label class="block text-[11px] font-bold text-slate-300 mb-1">Rua / Logradouro *</label>
                        <input type="text" name="endereco_logradouro" id="modal_endereco_logradouro" required placeholder="Ex: Rua São Sebastião" class="w-full h-11 px-3 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 mb-1">Nº</label>
                        <input type="text" name="endereco_numero" id="modal_endereco_numero" placeholder="120 ou S/N" class="w-full h-11 px-3 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 mb-1">Bairro *</label>
                        <input type="text" name="endereco_bairro" id="modal_endereco_bairro" required placeholder="Ex: Centro" class="w-full h-11 px-3 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 mb-1">Cidade *</label>
                        <input type="text" name="endereco_cidade" id="modal_endereco_cidade" required placeholder="Ex: Campina Grande" class="w-full h-11 px-3 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-300 mb-1">Ponto de Referência (Cobrança)</label>
                    <input type="text" name="ponto_referencia" id="modal_ponto_referencia" placeholder="Ex: Ao lado da padaria, portão azul" class="w-full h-11 px-3 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <!-- Botões de Ação do Modal -->
            <div class="pt-3 border-t border-slate-800 flex items-center justify-end gap-2.5">
                <button type="button" onclick="fecharModalNovoCliente()" class="px-4 py-2.5 rounded-xl border border-slate-700 text-slate-300 hover:text-white text-xs font-bold transition">
                    Cancelar
                </button>
                <button type="submit" id="btnSalvarClienteModal" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black text-xs rounded-xl shadow-lg transition active:scale-95 flex items-center gap-2">
                    <span>💾</span>
                    <span id="btnSalvarClienteTexto">Salvar e Selecionar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let contadorLinhas = 1;

    // ==========================================
    // DATAS E CRONOGRAMA DE PARCELAS PRESTANISTA
    // ==========================================
    function formatarDateISO(d) {
        const ano = d.getFullYear();
        const mes = String(d.getMonth() + 1).padStart(2, '0');
        const dia = String(d.getDate()).padStart(2, '0');
        return `${ano}-${mes}-${dia}`;
    }

    function formatarDateBR(d) {
        const dia = String(d.getDate()).padStart(2, '0');
        const mes = String(d.getMonth() + 1).padStart(2, '0');
        const ano = d.getFullYear();
        return `${dia}/${mes}/${ano}`;
    }

    function criarDataSemTimezone(strDate) {
        if (!strDate) return new Date();
        const partes = strDate.split('-');
        if (partes.length === 3) {
            return new Date(parseInt(partes[0], 10), parseInt(partes[1], 10) - 1, parseInt(partes[2], 10));
        }
        return new Date(strDate);
    }

    function formatarMoedaInput(input) {
        let v = input.value.replace(/\D/g, '');
        if (!v || v === '0') {
            input.value = '0,00';
            return;
        }
        let num = (parseInt(v, 10) / 100).toFixed(2);
        let parts = num.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        input.value = parts.join(',');
    }

    function aoMudarDataVenda() {
        const inputVenda = document.getElementById('inputDataVenda');
        const inputPrimeira = document.getElementById('inputPrimeiroVencimento');
        const selectFreq = document.getElementById('selectFrequencia');
        
        if (!inputVenda || !inputPrimeira || !selectFreq) return;
        
        const frequencia = parseInt(selectFreq.value, 10) || 7;
        const dataVenda = criarDataSemTimezone(inputVenda.value);
        
        // Adiciona a frequência em dias à data da venda para sugerir a 1ª parcela
        const dataSugerida = new Date(dataVenda.getFullYear(), dataVenda.getMonth(), dataVenda.getDate() + frequencia);
        inputPrimeira.value = formatarDateISO(dataSugerida);
        
        recalcularCronogramaParcelas();
    }

    function aoMudarFrequencia() {
        const inputVenda = document.getElementById('inputDataVenda');
        const inputPrimeira = document.getElementById('inputPrimeiroVencimento');
        const selectFreq = document.getElementById('selectFrequencia');
        
        if (!inputVenda || !inputPrimeira || !selectFreq) return;
        
        const frequencia = parseInt(selectFreq.value, 10) || 7;
        const dataVenda = criarDataSemTimezone(inputVenda.value);
        
        const dataSugerida = new Date(dataVenda.getFullYear(), dataVenda.getMonth(), dataVenda.getDate() + frequencia);
        inputPrimeira.value = formatarDateISO(dataSugerida);
        
        recalcularCronogramaParcelas();
    }

    function aoMudarPrimeiroVencimento() {
        recalcularCronogramaParcelas();
    }

    function recalcularCronogramaParcelas(totalParam) {
        const inputPrimeira = document.getElementById('inputPrimeiroVencimento');
        const selectFreq = document.getElementById('selectFrequencia');
        const inputParcelas = document.getElementById('inputNumeroParcelas');
        const inputEntrada = document.getElementById('inputValorEntrada');
        const containerGrid = document.getElementById('containerGradeParcelasPrevistas');
        const tituloResumo = document.getElementById('cronogramaTituloResumo');
        const subtitulo = document.getElementById('cronogramaSubtitulo');
        const badgeDatas = document.getElementById('badgePrimeiroUltimoVencimento');

        if (!inputPrimeira || !containerGrid) return;

        // Total
        let total = (typeof totalParam === 'number') ? totalParam : 0;
        if (typeof totalParam !== 'number') {
            document.querySelectorAll('.item-venda-linha').forEach(linha => {
                const qtd = parseFloat(linha.querySelector('input[name*="[quantidade]"]')?.value) || 0;
                const preco = parseFloat(linha.querySelector('input[name*="[preco]"]')?.value) || 0;
                total += (qtd * preco);
            });
        }

        const numParcelas = Math.max(1, Math.min(100, parseInt(inputParcelas?.value, 10) || 1));
        const frequencia = parseInt(selectFreq?.value, 10) || 7;
        const dataPrimeira = criarDataSemTimezone(inputPrimeira.value || formatarDateISO(new Date()));

        const entradaStr = inputEntrada?.value ? inputEntrada.value.replace(/\D/g, '') : '0';
        const valorEntrada = (parseInt(entradaStr, 10) || 0) / 100;

        const nomesDiasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        const labelFreq = frequencia === 1 ? 'diária' : (frequencia === 7 ? 'semanal' : (frequencia === 15 ? 'quinzenal' : 'mensal'));

        const valorParcelaBase = numParcelas > 0 ? (total / numParcelas) : total;
        const valorParcelaFormatado = valorParcelaBase.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        containerGrid.innerHTML = '';

        let ultimaDataFormatada = '';
        let primeiraDataFormatada = '';

        for (let i = 1; i <= numParcelas; i++) {
            // Data da parcela atual = dataPrimeira + (i - 1) * frequencia dias
            const diasAdicionais = (i - 1) * frequencia;
            const dataVenc = new Date(dataPrimeira.getFullYear(), dataPrimeira.getMonth(), dataPrimeira.getDate() + diasAdicionais);
            
            const dataFormatada = formatarDateBR(dataVenc);
            const diaSemana = nomesDiasSemana[dataVenc.getDay()];

            if (i === 1) primeiraDataFormatada = dataFormatada;
            if (i === numParcelas) ultimaDataFormatada = dataFormatada;

            const isEntradaPaga = (i === 1 && valorEntrada > 0 && valorEntrada >= valorParcelaBase);
            const card = document.createElement('div');
            card.className = `p-2.5 rounded-xl border flex flex-col justify-between text-xs transition ${
                i === 1 
                    ? 'bg-amber-500/10 border-amber-500/40 text-amber-300 shadow-xs' 
                    : 'bg-slate-900/80 border-slate-800 text-slate-300 hover:border-slate-700'
            }`;

            card.innerHTML = `
                <div class="flex items-center justify-between gap-1 mb-1">
                    <span class="font-black ${i === 1 ? 'text-amber-400' : 'text-slate-400'} text-[11px]">${i}ª Prestaç.</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded font-bold ${i === 1 ? 'bg-amber-500/20 text-amber-300' : 'bg-slate-800 text-slate-400'}">${diaSemana}</span>
                </div>
                <div class="font-black text-xs text-white">
                    ${dataFormatada}
                </div>
                <div class="mt-1 pt-1 border-t ${i === 1 ? 'border-amber-500/20' : 'border-slate-800/80'} flex items-center justify-between">
                    <span class="font-bold text-[11px] ${i === 1 ? 'text-amber-200' : 'text-slate-400'}">R$ ${valorParcelaFormatado}</span>
                    ${isEntradaPaga ? '<span class="text-[9px] font-black text-emerald-400 bg-emerald-500/10 px-1 rounded">Paga</span>' : ''}
                </div>
            `;
            containerGrid.appendChild(card);
        }

        if (tituloResumo) {
            tituloResumo.textContent = `Cronograma: ${numParcelas}x de R$ ${valorParcelaFormatado}`;
        }
        if (subtitulo) {
            subtitulo.textContent = `Cobrança ${labelFreq} (a cada ${frequencia} dias) calculada a partir da 1ª parcela`;
        }
        if (badgeDatas) {
            badgeDatas.textContent = `1º: ${primeiraDataFormatada} • Fim: ${ultimaDataFormatada}`;
        }
    }

    function atualizarPrecoProduto(select, idx) {
        const option = select.options[select.selectedIndex];
        const preco = option.getAttribute('data-preco');
        const inputPreco = document.getElementById('preco_item_' + idx);
        if (inputPreco && preco) {
            inputPreco.value = parseFloat(preco).toFixed(2);
        }
        calcularTotalCartao();
    }

    function calcularTotalCartao() {
        let total = 0;
        document.querySelectorAll('.item-venda-linha').forEach(linha => {
            const qtd = parseFloat(linha.querySelector('input[name*="[quantidade]"]')?.value) || 0;
            const preco = parseFloat(linha.querySelector('input[name*="[preco]"]')?.value) || 0;
            total += (qtd * preco);
        });
        document.getElementById('labelTotalCalculado').textContent = 'R$ ' + total.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        recalcularCronogramaParcelas(total);
    }

    function adicionarLinhaProduto() {
        const container = document.getElementById('containerItensVenda');
        const novaLinha = document.createElement('div');
        novaLinha.className = 'grid grid-cols-12 gap-2 item-venda-linha';
        novaLinha.innerHTML = `
            <div class="col-span-7 min-w-0">
                <select name="itens[${contadorLinhas}][produto_id]" required onchange="atualizarPrecoProduto(this, ${contadorLinhas})" class="w-full min-w-0 h-11 px-3 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none truncate">
                    <option value="">Selecione a mercadoria...</option>
                    <?php foreach ($produtos as $p): ?>
                        <option value="<?= $p->id ?>" data-preco="<?= (float)$p->preco ?>"><?= Html::encode($p->nome) ?> (R$ <?= number_format($p->preco, 2, ',', '.') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-span-2">
                <input type="number" name="itens[${contadorLinhas}][quantidade]" value="1" min="1" required oninput="calcularTotalCartao()" class="w-full h-11 text-center bg-slate-900 border border-slate-700 rounded-xl text-xs text-white font-bold" placeholder="Qtd">
            </div>
            <div class="col-span-3 flex items-center gap-1">
                <input type="number" step="0.01" name="itens[${contadorLinhas}][preco]" id="preco_item_${contadorLinhas}" required oninput="calcularTotalCartao()" class="w-full h-11 px-3 text-right bg-slate-900 border border-slate-700 rounded-xl text-xs text-white font-bold" placeholder="R$ Valor">
                <button type="button" onclick="this.closest('.item-venda-linha').remove(); calcularTotalCartao();" class="text-rose-400 hover:text-rose-300 p-1 text-xs">✕</button>
            </div>
        `;
        container.appendChild(novaLinha);
        contadorLinhas++;
    }

    // Modal de Cadastro Rápido de Cliente
    function abrirModalNovoCliente() {
        const modal = document.getElementById('modalNovoCliente');
        const erroDiv = document.getElementById('modalClienteErro');
        erroDiv.classList.add('hidden');
        erroDiv.textContent = '';
        modal.classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('modal_nome_completo')?.focus();
        }, 100);
    }

    function fecharModalNovoCliente() {
        const modal = document.getElementById('modalNovoCliente');
        modal.classList.add('hidden');
    }

    function handleBackdropClick(event) {
        if (event.target === document.getElementById('modalNovoCliente')) {
            fecharModalNovoCliente();
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            fecharModalNovoCliente();
        }
    });

    // Formatação de Máscaras
    function formatarTelefone(input) {
        let v = input.value.replace(/\D/g, '');
        if (v.length > 11) v = v.substring(0, 11);
        if (v.length > 10) {
            input.value = v.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
        } else if (v.length > 6) {
            input.value = v.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3');
        } else if (v.length > 2) {
            input.value = v.replace(/^(\d{2})(\d{0,5})$/, '($1) $2');
        } else {
            input.value = v;
        }
    }

    function formatarCpf(input) {
        let v = input.value.replace(/\D/g, '');
        if (v.length > 11) v = v.substring(0, 11);
        if (v.length > 9) {
            input.value = v.replace(/^(\d{3})(\d{3})(\d{3})(\d{1,2})$/, '$1.$2.$3-$4');
        } else if (v.length > 6) {
            input.value = v.replace(/^(\d{3})(\d{3})(\d{0,3})$/, '$1.$2.$3');
        } else if (v.length > 3) {
            input.value = v.replace(/^(\d{3})(\d{0,3})$/, '$1.$2');
        } else {
            input.value = v;
        }
    }

    async function salvarClienteRapido(event) {
        event.preventDefault();
        const form = document.getElementById('formCadastroClienteRapido');
        const erroDiv = document.getElementById('modalClienteErro');
        const btn = document.getElementById('btnSalvarClienteModal');
        const btnTexto = document.getElementById('btnSalvarClienteTexto');

        erroDiv.classList.add('hidden');
        erroDiv.textContent = '';
        btn.disabled = true;
        btnTexto.textContent = 'Salvando...';

        const formData = new FormData(form);
        // Adicionar token CSRF
        formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

        try {
            const response = await fetch('<?= Url::to(['/prestanista/cartao/cadastrar-cliente-rapido']) ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success && data.cliente) {
                // Adiciona o novo cliente no select
                const select = document.getElementById('select-cliente');
                const novaOpcao = document.createElement('option');
                novaOpcao.value = data.cliente.id;
                novaOpcao.textContent = `${data.cliente.nome} (${data.cliente.bairro || 'Sem bairro'})`;
                novaOpcao.selected = true;

                // Insere logo após o placeholder "Selecione o Cliente..."
                if (select.children.length > 1) {
                    select.insertBefore(novaOpcao, select.children[1]);
                } else {
                    select.appendChild(novaOpcao);
                }

                select.value = data.cliente.id;

                // Feedback visual na tela principal
                const feedbackP = document.getElementById('clienteCadastradoFeedback');
                const feedbackTexto = document.getElementById('clienteFeedbackTexto');
                feedbackTexto.textContent = `Cliente ${data.cliente.nome} cadastrado e selecionado!`;
                feedbackP.classList.remove('hidden');

                // Limpa form do modal e fecha
                form.reset();
                fecharModalNovoCliente();

            } else {
                erroDiv.textContent = data.message || 'Erro ao cadastrar cliente. Verifique os campos.';
                erroDiv.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Erro na requisição:', error);
            erroDiv.textContent = 'Ocorreu um erro ao processar a requisição. Tente novamente.';
            erroDiv.classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btnTexto.textContent = 'Salvar e Selecionar';
        }
    }

    // Ouvinte para quando um produto expresso for cadastrado pelo modal rápido
    window.addEventListener('produtoCadastradoRapido', function(e) {
        const prod = e.detail;
        if (!prod || !prod.id) return;

        const precoNumerico = parseFloat(prod.preco ? prod.preco.toString().replace(/\./g, '').replace(',', '.') : 0) || 0;
        const precoFormatado = precoNumerico.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const textoOption = `${prod.nome} (R$ ${precoFormatado})`;

        // 1. Atualiza todos os selects de mercadoria da tela adicionando a nova option
        const selects = document.querySelectorAll('select[name*="[produto_id]"]');
        selects.forEach(sel => {
            const opt = document.createElement('option');
            opt.value = prod.id;
            opt.setAttribute('data-preco', precoNumerico);
            opt.textContent = textoOption;
            if (sel.children.length > 1) {
                sel.insertBefore(opt, sel.children[1]);
            } else {
                sel.appendChild(opt);
            }
        });

        // 2. Localiza a primeira linha com produto vazio ou adiciona uma nova linha
        let linhaAlvo = null;
        document.querySelectorAll('.item-venda-linha').forEach(linha => {
            const sel = linha.querySelector('select[name*="[produto_id]"]');
            if (sel && !sel.value && !linhaAlvo) {
                linhaAlvo = linha;
            }
        });

        if (!linhaAlvo) {
            adicionarLinhaProduto();
            const linhas = document.querySelectorAll('.item-venda-linha');
            linhaAlvo = linhas[linhas.length - 1];
        }

        if (linhaAlvo) {
            const sel = linhaAlvo.querySelector('select[name*="[produto_id]"]');
            const inputPreco = linhaAlvo.querySelector('input[name*="[preco]"]');
            if (sel) sel.value = prod.id;
            if (inputPreco) inputPreco.value = precoNumerico.toFixed(2);
            calcularTotalCartao();
        }
    });

    // Inicialização do cronograma na carga da página
    document.addEventListener('DOMContentLoaded', function() {
        calcularTotalCartao();
    });
    // Fallback caso DOM já esteja carregado
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(calcularTotalCartao, 50);
    }
</script>

<!-- Renderização do Modal de Cadastro Rápido de Produto Expresso -->
<?= $this->render('@app/modules/vendas/views/produto/_modal_cadastro_rapido') ?>

