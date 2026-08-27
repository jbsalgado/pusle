<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = '⚡ Venda Expressa (Encarte & Catálogo)';
$this->params['breadcrumbs'][] = ['label' => 'Vendas', 'url' => ['/vendas/venda/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-slate-900 text-slate-100 py-6 px-3 sm:px-6">
    
    <div class="max-w-6xl mx-auto space-y-6">

        <!-- Topo & Indicadores Relâmpago de Vendas do Dia -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-slate-800/90 border border-slate-700 p-5 rounded-3xl shadow-2xl backdrop-blur-md">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-2xl">⚡</span>
                    <h1 class="text-2xl font-black text-white tracking-tight">Venda Expressa</h1>
                    <span class="bg-amber-400/20 text-amber-300 border border-amber-400/30 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">Modo Encarte</span>
                </div>
                <p class="text-xs text-slate-400 mt-1">Registre suas vendas do WhatsApp ou balcão em 5 segundos</p>
            </div>

            <!-- Resumo Financeiro de Hoje -->
            <div class="grid grid-cols-3 gap-3 w-full md:w-auto">
                <div class="bg-slate-900/80 p-3 rounded-2xl border border-slate-700 text-center">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Vendas Hoje</div>
                    <div class="text-lg font-montserrat font-black text-amber-400">R$ <span id="resumoValor"><?= $resumoHoje['valor_total'] ?></span></div>
                </div>

                <div class="bg-slate-900/80 p-3 rounded-2xl border border-slate-700 text-center">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Total Vendas</div>
                    <div class="text-lg font-montserrat font-black text-emerald-400"><span id="resumoQtd"><?= $resumoHoje['total_vendas'] ?></span> un</div>
                </div>

                <div class="bg-slate-900/80 p-3 rounded-2xl border border-slate-700 text-center">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Top Item Hoje</div>
                    <div id="resumoTop" class="text-xs font-bold text-slate-200 truncate max-w-[120px]" title="<?= Html::encode($resumoHoje['top_produto']) ?>"><?= Html::encode($resumoHoje['top_produto']) ?></div>
                </div>
            </div>
        </div>

        <!-- Área Principal de Registro da Venda -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Coluna Esquerda: Seletor de Produtos e Lista da Venda -->
            <div class="lg:col-span-8 space-y-4">
                
                <!-- Card Busca Rápida de Produto -->
                <div class="bg-slate-800 border border-slate-700 p-4 rounded-3xl shadow-xl space-y-3">
                    <label class="block text-xs font-bold text-amber-400 uppercase tracking-wider">🔍 Selecionar Produto para Venda</label>
                    
                    <div class="relative">
                        <select id="selectProduto" onchange="adicionarProdutoSelecionado()" class="w-full bg-slate-900 border border-slate-700 text-white rounded-2xl p-3.5 text-sm font-semibold focus:ring-2 focus:ring-amber-400 focus:outline-none cursor-pointer">
                            <option value="" selected disabled>Clique ou comece a digitar para selecionar o produto...</option>
                            <?php foreach ($produtos as $p): 
                                $foto = $p->fotoPrincipal ?: ($p->fotos[0] ?? null);
                                $urlFoto = $foto ? Url::to('@web/' . ltrim($foto->arquivo_path, '/'), true) : '';
                                $precoStr = number_format($p->preco_venda_sugerido, 2, ',', '.');
                            ?>
                                <option value="<?= $p->id ?>" data-nome="<?= Html::encode($p->nome) ?>" data-preco="<?= $p->preco_venda_sugerido ?>" data-preco-str="<?= $precoStr ?>" data-unidade="<?= Html::encode($p->unidade_medida ?: 'UN') ?>" data-foto="<?= $urlFoto ?>">
                                    <?= Html::encode($p->nome) ?> — R$ <?= $precoStr ?> / <?= Html::encode($p->unidade_medida ?: 'UN') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Tabela de Itens Selecionados da Venda -->
                <div class="bg-slate-800 border border-slate-700 p-4 rounded-3xl shadow-xl space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-700 pb-3">
                        <h3 class="font-extrabold text-sm text-white flex items-center gap-2">
                            🛒 Itens da Venda
                            <span id="badgeCountItens" class="bg-amber-400 text-slate-900 text-xs font-black px-2 py-0.5 rounded-full">0</span>
                        </h3>
                        <button type="button" onclick="limparItensVenda()" class="text-xs font-bold text-slate-400 hover:text-red-400 transition">Esvaziar Itens</button>
                    </div>

                    <!-- Lista de Itens -->
                    <div id="listaItensVenda" class="space-y-2 max-h-80 overflow-y-auto pr-1">
                        <div id="emptyStateVenda" class="text-center py-10 text-slate-400 space-y-2">
                            <span class="text-4xl block">🛍️</span>
                            <p class="text-xs font-bold">Nenhum produto adicionado ainda.</p>
                            <p class="text-[10px]">Selecione um produto acima para registrar a venda em 5 segundos!</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Coluna Direita: Checkout Relâmpago e Forma de Pagamento -->
            <div class="lg:col-span-4 space-y-4">
                
                <div class="bg-slate-800 border border-slate-700 p-5 rounded-3xl shadow-xl space-y-4">
                    
                    <h3 class="font-extrabold text-sm text-white border-b border-slate-700 pb-2">💳 Finalização Relâmpago</h3>

                    <!-- Totalizador -->
                    <div class="bg-slate-900 p-4 rounded-2xl border border-slate-700 text-center">
                        <div class="text-[10px] uppercase font-extrabold text-slate-400">Total a Receber</div>
                        <div class="text-3xl font-montserrat font-black text-emerald-400 mt-1">
                            R$ <span id="displayTotalFinal">0,00</span>
                        </div>
                    </div>

                    <!-- Seleção Rápida de Pagamento (Chips 1-Clique) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Forma de Pagamento</label>
                        <div class="grid grid-cols-2 gap-2">
                            <?php foreach ($formasPagamento as $index => $fp): 
                                $isPix = (mb_stripos($fp->nome, 'pix') !== false || $index === 0);
                            ?>
                                <button type="button" onclick="selecionarFormaPagamento('<?= $fp->id ?>', this)" class="btn-forma-pagamento p-2.5 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5 <?= $isPix ? 'bg-amber-400 text-slate-900 border-amber-300 shadow-md' : 'bg-slate-900 text-slate-300 border-slate-700 hover:bg-slate-700' ?>" data-id="<?= $fp->id ?>">
                                    <span><?= $fp->nome ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Observação Opcional -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Observações (Opcional)</label>
                        <input type="text" id="inputObservacoes" placeholder="Ex: Cliente do WhatsApp..." class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-amber-400">
                    </div>

                    <!-- Botão de Efetivação Relâmpago -->
                    <button type="button" id="btnEfetivarVenda" onclick="efetivarVendaExpressa()" class="w-full py-4 bg-gradient-to-r from-emerald-500 via-green-500 to-emerald-600 hover:from-emerald-600 hover:to-green-700 text-white font-montserrat font-black text-base rounded-2xl shadow-xl transition transform active:scale-95 flex items-center justify-center gap-2 border border-white/20">
                        <span>⚡ Efetivar Venda (R$ <span id="totalFinalBtn">0,00</span>)</span>
                    </button>

                </div>

            </div>

        </div>

    </div>
</div>

<script>
    let itensVendaMap = {};
    let formaPagamentoSelecionadaId = '<?= count($formasPagamento) > 0 ? $formasPagamento[0]->id : "" ?>';

    function adicionarProdutoSelecionado() {
        const select = document.getElementById('selectProduto');
        const opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) return;

        const id = opt.value;
        const nome = opt.getAttribute('data-nome');
        const precoVal = parseFloat(opt.getAttribute('data-preco')) || 0;
        const unidade = opt.getAttribute('data-unidade') || 'UN';
        const foto = opt.getAttribute('data-foto');

        if (!itensVendaMap[id]) {
            // Quantidade padrão igual a 1
            itensVendaMap[id] = {
                id: id,
                nome: nome,
                precoVal: precoVal,
                unidade: unidade,
                foto: foto,
                qtd: 1
            };
        } else {
            itensVendaMap[id].qtd += 1;
        }

        select.selectedIndex = 0;
        renderizarItensVenda();
    }

    function alterarQtdItem(id, delta) {
        if (itensVendaMap[id]) {
            itensVendaMap[id].qtd += delta;
            if (itensVendaMap[id].qtd <= 0) {
                delete itensVendaMap[id];
            }
            renderizarItensVenda();
        }
    }

    function atualizarQtdDireta(id, valStr) {
        const val = parseFloat(valStr);
        if (!isNaN(val) && val > 0 && itensVendaMap[id]) {
            itensVendaMap[id].qtd = val;
            renderizarItensVenda();
        }
    }

    function atualizarPrecoDireta(id, valStr) {
        const val = parseFloat(valStr.replace(',', '.'));
        if (!isNaN(val) && val >= 0 && itensVendaMap[id]) {
            itensVendaMap[id].precoVal = val;
            renderizarItensVenda();
        }
    }

    function removerItem(id) {
        delete itensVendaMap[id];
        renderizarItensVenda();
    }

    function limparItensVenda() {
        itensVendaMap = {};
        renderizarItensVenda();
    }

    function selecionarFormaPagamento(id, btn) {
        formaPagamentoSelecionadaId = id;
        document.querySelectorAll('.btn-forma-pagamento').forEach(b => {
            b.className = 'btn-forma-pagamento p-2.5 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5 bg-slate-900 text-slate-300 border-slate-700 hover:bg-slate-700';
        });
        btn.className = 'btn-forma-pagamento p-2.5 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5 bg-amber-400 text-slate-900 border-amber-300 shadow-md';
    }

    function renderizarItensVenda() {
        const container = document.getElementById('listaItensVenda');
        const emptyState = document.getElementById('emptyStateVenda');
        const lista = Object.values(itensVendaMap);

        let totalCalculado = 0;
        let totalQtdItens = 0;

        if (lista.length === 0) {
            container.innerHTML = '';
            container.appendChild(emptyState);
            emptyState.style.display = 'block';
        } else {
            container.innerHTML = '';
            lista.forEach(item => {
                const sub = item.precoVal * item.qtd;
                totalCalculado += sub;
                totalQtdItens += item.qtd;

                const div = document.createElement('div');
                div.className = 'flex items-center justify-between p-3 bg-slate-900 border border-slate-700 rounded-2xl gap-3';
                div.innerHTML = `
                    <div class="flex items-center gap-2.5 flex-1 min-w-0">
                        ${item.foto ? `<img src="${item.foto}" class="w-10 h-10 object-contain rounded-lg bg-white p-0.5 flex-shrink-0">` : `<div class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center text-[9px] font-bold text-slate-500 flex-shrink-0">FOTO</div>`}
                        <div class="truncate">
                            <div class="font-extrabold text-xs text-white truncate">${item.nome}</div>
                            <div class="flex items-center gap-1 mt-0.5 text-[11px] text-slate-400">
                                <span>R$</span>
                                <input type="text" value="${item.precoVal.toFixed(2).replace('.', ',')}" onchange="atualizarPrecoDireta('${item.id}', this.value)" class="w-16 bg-slate-800 text-amber-400 font-bold px-1 py-0.5 rounded border border-slate-700 text-center">
                                <span>/${item.unidade}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Controle de Quantidade -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <div class="flex items-center bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
                            <button type="button" onclick="alterarQtdItem('${item.id}', -1)" class="px-2.5 py-1 text-slate-300 hover:bg-slate-700 font-bold text-sm">-</button>
                            <input type="number" step="any" min="0.01" value="${item.qtd}" onchange="atualizarQtdDireta('${item.id}', this.value)" class="w-12 bg-transparent text-center text-xs font-black text-white focus:outline-none">
                            <button type="button" onclick="alterarQtdItem('${item.id}', 1)" class="px-2.5 py-1 text-slate-300 hover:bg-slate-700 font-bold text-sm">+</button>
                        </div>

                        <!-- Subtotal Item -->
                        <div class="text-right w-20">
                            <div class="text-xs font-montserrat font-black text-emerald-400">R$ ${sub.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                        </div>

                        <!-- Deletar Item -->
                        <button type="button" onclick="removerItem('${item.id}')" class="text-slate-500 hover:text-red-400 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                `;
                container.appendChild(div);
            });
        }

        const totalFormatted = totalCalculado.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('badgeCountItens').textContent = lista.length;
        document.getElementById('displayTotalFinal').textContent = totalFormatted;
        document.getElementById('totalFinalBtn').textContent = totalFormatted;
    }

    function efetivarVendaExpressa() {
        const lista = Object.values(itensVendaMap);
        if (lista.length === 0) {
            alert('Adicione pelo menos um produto antes de efetivar a venda.');
            return;
        }

        const btn = document.getElementById('btnEfetivarVenda');
        btn.disabled = true;
        btn.innerHTML = '⚡ Registrando Venda Expressa...';

        const payloadItens = lista.map(item => ({
            produto_id: item.id,
            quantidade: item.qtd,
            preco_unitario: item.precoVal
        }));

        const payload = {
            itens: payloadItens,
            forma_pagamento_id: formaPagamentoSelecionadaId,
            observacoes: document.getElementById('inputObservacoes').value,
            '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
        };

        fetch('<?= Url::to(['/vendas/venda-expressa/salvar']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = `<span>⚡ Efetivar Venda (R$ <span id="totalFinalBtn">0,00</span>)</span>`;

            if (data.success) {
                limparItensVenda();
                document.getElementById('inputObservacoes').value = '';

                // Atualizar Indicadores de Venda em Tempo Real
                if (data.resumoHoje) {
                    document.getElementById('resumoValor').textContent = data.resumoHoje.valor_total;
                    document.getElementById('resumoQtd').textContent = data.resumoHoje.total_vendas;
                    document.getElementById('resumoTop').textContent = data.resumoHoje.top_produto;
                }

                alert('✅ Venda Expressa de R$ ' + data.valor_total + ' registrada com sucesso!');
            } else {
                alert('Erro ao registrar venda: ' + (data.message || 'Falha na conexão.'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = `<span>⚡ Efetivar Venda (R$ <span id="totalFinalBtn">0,00</span>)</span>`;
            alert('Erro ao comunicar com o servidor: ' + err.message);
        });
    }
</script>
