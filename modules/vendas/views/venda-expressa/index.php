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
                <p class="text-xs text-slate-400 mt-1">Registre suas vendas do WhatsApp ou balcão com cadastro de clientes para Evolution API</p>
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
                
                <!-- Card Busca Rápida por Digitação de Produto -->
                <div class="bg-slate-800 border border-slate-700 p-4 rounded-3xl shadow-xl space-y-2 relative" id="containerBuscaProduto">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-amber-400 uppercase tracking-wider">🔍 Digitar Nome ou Marca do Produto</label>
                        <span class="text-[10px] text-slate-400 font-semibold">Pressione Enter ou clique para incluir</span>
                    </div>
                    
                    <div class="relative">
                        <input type="text" id="inputBuscaProduto" 
                               placeholder="🔍 Digite para consultar (ex: Arroz, Feijão, Nestlé)..." 
                               autocomplete="off"
                               oninput="filtrarProdutosBusca(this.value)"
                               onfocus="filtrarProdutosBusca(this.value)"
                               onkeydown="tratarTeclasBusca(event)"
                               class="w-full bg-slate-900 border border-slate-700 text-white rounded-2xl py-3.5 pl-4 pr-10 text-sm font-semibold focus:ring-2 focus:ring-amber-400 focus:outline-none placeholder-slate-500 shadow-inner">
                        
                        <button type="button" id="btnLimparBusca" onclick="limparBuscaProduto()" class="absolute right-3.5 top-3.5 text-slate-400 hover:text-white hidden transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Dropdown de Resultados da Busca -->
                    <div id="dropdownBuscaResultados" class="absolute left-4 right-4 top-full mt-1.5 bg-slate-800/95 border border-slate-600 rounded-2xl shadow-2xl z-50 max-h-72 overflow-y-auto hidden divide-y divide-slate-700/60 backdrop-blur-lg">
                        <!-- Renderizado dinamicamente via JS -->
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
                            <p class="text-[10px]">Digite no campo acima para pesquisar e adicionar em 1 clique!</p>
                        </div>
                    </div>
                </div>

                <!-- Seção Dados do Cliente (Cadastro para Disparos Evolution API) -->
                <div class="bg-slate-800 border border-slate-700 p-4 rounded-3xl shadow-xl space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-700 pb-2">
                        <h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider flex items-center gap-2">
                            <span>👤 Cliente (Disparos WhatsApp / Evolution API)</span>
                        </h3>
                        <span class="text-[10px] text-slate-400 font-medium">Cadastra e salva na base automaticamente</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- WhatsApp -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1">📱 WhatsApp / Fone</label>
                            <input type="text" id="clienteWhatsapp" placeholder="(81) 99999-9999" oninput="aplicarMascaraTelefone(this)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs font-semibold text-white focus:outline-none focus:border-amber-400">
                        </div>

                        <!-- Nome Completo -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1">👤 Nome Completo</label>
                            <input type="text" id="clienteNome" placeholder="Ex: Maria Silva" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs font-semibold text-white focus:outline-none focus:border-amber-400">
                        </div>

                        <!-- CPF -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1">📄 CPF (Opcional)</label>
                            <input type="text" id="clienteCpf" placeholder="000.000.000-00" oninput="aplicarMascaraCpf(this)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs font-semibold text-white focus:outline-none focus:border-amber-400">
                        </div>
                    </div>
                </div>

            </div>

            <!-- Coluna Direita: Checkout Relâmpago, Desconto/Acréscimo e Pagamento -->
            <div class="lg:col-span-4 space-y-4">
                
                <div class="bg-slate-800 border border-slate-700 p-5 rounded-3xl shadow-xl space-y-4">
                    
                    <h3 class="font-extrabold text-sm text-white border-b border-slate-700 pb-2">💳 Finalização Relâmpago</h3>

                    <!-- Ajustes Finos: Desconto Geral e Acréscimo Geral -->
                    <div class="grid grid-cols-2 gap-2 bg-slate-900/60 p-3 rounded-2xl border border-slate-700">
                        <!-- Desconto Geral -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-[10px] font-bold text-rose-400 uppercase">🏷️ Desconto</label>
                                <select id="descontoTipo" onchange="renderizarItensVenda()" class="bg-slate-800 text-[10px] font-bold text-rose-300 rounded px-1 py-0.5 border border-slate-700">
                                    <option value="VALOR">R$</option>
                                    <option value="PERCENTUAL">%</option>
                                </select>
                            </div>
                            <input type="text" id="descontoGeral" placeholder="0,00" oninput="renderizarItensVenda()" class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-xl text-xs font-bold text-rose-400 focus:outline-none text-right">
                        </div>

                        <!-- Acréscimo Geral -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-[10px] font-bold text-blue-400 uppercase">➕ Acréscimo</label>
                                <select id="acrescimoTipo" onchange="renderizarItensVenda()" class="bg-slate-800 text-[10px] font-bold text-blue-300 rounded px-1 py-0.5 border border-slate-700">
                                    <option value="VALOR">R$</option>
                                    <option value="PERCENTUAL">%</option>
                                </select>
                            </div>
                            <input type="text" id="acrescimoGeral" placeholder="0,00" oninput="renderizarItensVenda()" class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-xl text-xs font-bold text-blue-400 focus:outline-none text-right">
                        </div>
                    </div>

                    <!-- Totalizador com Subtotal, Desconto e Acréscimo -->
                    <div class="bg-slate-900 p-4 rounded-2xl border border-slate-700 space-y-1.5">
                        <div class="flex items-center justify-between text-xs text-slate-400">
                            <span>Subtotal Itens:</span>
                            <span class="font-bold text-white">R$ <span id="displaySubtotal">0,00</span></span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-rose-400 hidden" id="rowDisplayDesconto">
                            <span>(-) Desconto Geral:</span>
                            <span class="font-bold">- R$ <span id="displayDesconto">0,00</span></span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-blue-400 hidden" id="rowDisplayAcrescimo">
                            <span>(+) Acréscimo Geral:</span>
                            <span class="font-bold">+ R$ <span id="displayAcrescimo">0,00</span></span>
                        </div>
                        <div class="border-t border-slate-800 pt-2 flex items-center justify-between">
                            <span class="text-xs uppercase font-extrabold text-slate-300">Total a Receber:</span>
                            <span class="text-2xl font-montserrat font-black text-emerald-400">R$ <span id="displayTotalFinal">0,00</span></span>
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
    const produtosArray = [
        <?php foreach ($produtos as $p): 
            $foto = $p->fotoPrincipal ?: ($p->fotos[0] ?? null);
            $urlFoto = $foto ? Url::to('@web/' . ltrim($foto->arquivo_path, '/'), true) : '';
            $precoStr = number_format($p->preco_venda_sugerido, 2, ',', '.');
        ?>
        {
            id: <?= json_encode($p->id) ?>,
            nome: <?= json_encode($p->nome) ?>,
            marca: <?= json_encode($p->marca ?: '') ?>,
            precoVal: <?= (float)$p->preco_venda_sugerido ?>,
            precoStr: <?= json_encode($precoStr) ?>,
            unidade: <?= json_encode($p->unidade_medida ?: 'UN') ?>,
            foto: <?= json_encode($urlFoto) ?>
        },
        <?php endforeach; ?>
    ];

    let itensVendaMap = {};
    let formaPagamentoSelecionadaId = '<?= count($formasPagamento) > 0 ? $formasPagamento[0]->id : "" ?>';
    let indexItemFocado = -1;

    function aplicarMascaraTelefone(input) {
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

    function aplicarMascaraCpf(input) {
        let v = input.value.replace(/\D/g, '');
        if (v.length > 11) v = v.substring(0, 11);
        if (v.length > 9) {
            input.value = v.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
        } else if (v.length > 6) {
            input.value = v.replace(/^(\d{3})(\d{3})(\d{0,3})$/, '$1.$2.$3');
        } else if (v.length > 3) {
            input.value = v.replace(/^(\d{3})(\d{0,3})$/, '$1.$2');
        } else {
            input.value = v;
        }
    }

    function filtrarProdutosBusca(termo) {
        const dropdown = document.getElementById('dropdownBuscaResultados');
        const btnLimpar = document.getElementById('btnLimparBusca');
        const termoClean = (termo || '').trim().toLowerCase();

        btnLimpar.style.display = termoClean ? 'block' : 'none';

        if (!termoClean) {
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
            indexItemFocado = -1;
            return;
        }

        const resultados = produtosArray.filter(p => 
            p.nome.toLowerCase().includes(termoClean) || 
            (p.marca && p.marca.toLowerCase().includes(termoClean))
        );

        if (resultados.length === 0) {
            dropdown.innerHTML = `<div class="p-4 text-xs font-bold text-slate-400 text-center">Nenhum produto encontrado com "${termoClean}"</div>`;
            dropdown.classList.remove('hidden');
            indexItemFocado = -1;
            return;
        }

        dropdown.innerHTML = '';
        indexItemFocado = -1;

        resultados.forEach((prod, idx) => {
            const item = document.createElement('div');
            item.className = 'item-resultado-busca p-3 hover:bg-amber-400/20 cursor-pointer flex items-center justify-between transition gap-3 group border-b border-slate-700/40 last:border-0';
            item.setAttribute('data-index', idx);
            item.onclick = function() {
                selecionarProdutoDireto(prod);
            };

            const nomeHighlighted = highlightTermo(prod.nome, termoClean);

            item.innerHTML = `
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    ${prod.foto ? `<img src="${prod.foto}" class="w-9 h-9 object-contain rounded-lg bg-white p-0.5 flex-shrink-0">` : `<div class="w-9 h-9 rounded-lg bg-slate-900 flex items-center justify-center text-[9px] font-bold text-slate-500 flex-shrink-0">FOTO</div>`}
                    <div class="truncate">
                        <div class="font-extrabold text-xs text-white group-hover:text-amber-300 truncate">${nomeHighlighted}</div>
                        ${prod.marca ? `<div class="text-[10px] text-slate-400 font-semibold">${prod.marca}</div>` : ''}
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <div class="font-montserrat font-black text-xs text-emerald-400">R$ ${prod.precoStr}</div>
                    <div class="text-[10px] text-slate-400 uppercase font-bold">/${prod.unidade}</div>
                </div>
            `;
            dropdown.appendChild(item);
        });

        dropdown.classList.remove('hidden');
    }

    function highlightTermo(texto, termo) {
        if (!termo) return texto;
        const re = new RegExp('(' + termo.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return texto.replace(re, '<span class="bg-amber-400/30 text-amber-200 px-0.5 rounded font-black">$1</span>');
    }

    function tratarTeclasBusca(e) {
        const dropdown = document.getElementById('dropdownBuscaResultados');
        const itens = dropdown.querySelectorAll('.item-resultado-busca');

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (itens.length === 0) return;
            indexItemFocado = (indexItemFocado + 1) % itens.length;
            atualizarItemFocado(itens);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (itens.length === 0) return;
            indexItemFocado = (indexItemFocado - 1 + itens.length) % itens.length;
            atualizarItemFocado(itens);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (indexItemFocado >= 0 && itens[indexItemFocado]) {
                itens[indexItemFocado].click();
            } else if (itens.length > 0) {
                itens[0].click();
            }
        } else if (e.key === 'Escape') {
            limparBuscaProduto();
        }
    }

    function atualizarItemFocado(itens) {
        itens.forEach((it, i) => {
            if (i === indexItemFocado) {
                it.classList.add('bg-amber-400/20', 'border-l-4', 'border-amber-400');
                it.scrollIntoView({ block: 'nearest' });
            } else {
                it.classList.remove('bg-amber-400/20', 'border-l-4', 'border-amber-400');
            }
        });
    }

    function selecionarProdutoDireto(prod) {
        if (!itensVendaMap[prod.id]) {
            // Quantidade padrão igual a 1
            itensVendaMap[prod.id] = {
                id: prod.id,
                nome: prod.nome,
                precoVal: prod.precoVal,
                unidade: prod.unidade,
                foto: prod.foto,
                qtd: 1
            };
        } else {
            itensVendaMap[prod.id].qtd += 1;
        }

        limparBuscaProduto();
        renderizarItensVenda();
        document.getElementById('inputBuscaProduto').focus();
    }

    function limparBuscaProduto() {
        const input = document.getElementById('inputBuscaProduto');
        input.value = '';
        document.getElementById('dropdownBuscaResultados').classList.add('hidden');
        document.getElementById('btnLimparBusca').style.display = 'none';
        indexItemFocado = -1;
    }

    // Fechar dropdown se clicar fora
    document.addEventListener('click', function(e) {
        const container = document.getElementById('containerBuscaProduto');
        if (container && !container.contains(e.target)) {
            document.getElementById('dropdownBuscaResultados').classList.add('hidden');
        }
    });

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
        document.getElementById('descontoGeral').value = '';
        document.getElementById('acrescimoGeral').value = '';
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

        let subtotalCalculado = 0;
        let totalQtdItens = 0;

        if (lista.length === 0) {
            container.innerHTML = '';
            container.appendChild(emptyState);
            emptyState.style.display = 'block';
        } else {
            container.innerHTML = '';
            lista.forEach(item => {
                const sub = item.precoVal * item.qtd;
                subtotalCalculado += sub;
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

        // Cálculo do Desconto Geral
        const descInput = parseFloat((document.getElementById('descontoGeral').value || '0').replace(',', '.')) || 0;
        const descTipo = document.getElementById('descontoTipo').value;
        let valDesconto = 0;
        if (descInput > 0) {
            valDesconto = (descTipo === 'PERCENTUAL') ? subtotalCalculado * (descInput / 100) : descInput;
        }

        // Cálculo do Acréscimo Geral
        $acresInput = parseFloat((document.getElementById('acrescimoGeral').value || '0').replace(',', '.')) || 0;
        $acresTipo = document.getElementById('acrescimoTipo').value;
        let valAcrescimo = 0;
        if ($acresInput > 0) {
            valAcrescimo = ($acresTipo === 'PERCENTUAL') ? subtotalCalculado * ($acresInput / 100) : $acresInput;
        }

        const totalFinalCalculado = Math.max(0, subtotalCalculado - valDesconto + valAcrescimo);

        document.getElementById('badgeCountItens').textContent = lista.length;
        document.getElementById('displaySubtotal').textContent = subtotalCalculado.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Exibição condicional de Desconto e Acréscimo no Resumo
        const rowDesc = document.getElementById('rowDisplayDesconto');
        if (valDesconto > 0) {
            rowDesc.classList.remove('hidden');
            document.getElementById('displayDesconto').textContent = valDesconto.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } else {
            rowDesc.classList.add('hidden');
        }

        const rowAcres = document.getElementById('rowDisplayAcrescimo');
        if (valAcrescimo > 0) {
            rowAcres.classList.remove('hidden');
            document.getElementById('displayAcrescimo').textContent = valAcrescimo.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } else {
            rowAcres.classList.add('hidden');
        }

        const totalFormatted = totalFinalCalculado.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
            cliente_nome: document.getElementById('clienteNome').value,
            cliente_cpf: document.getElementById('clienteCpf').value,
            cliente_whatsapp: document.getElementById('clienteWhatsapp').value,
            desconto_valor: document.getElementById('descontoGeral').value,
            desconto_tipo: document.getElementById('descontoTipo').value,
            acrescimo_valor: document.getElementById('acrescimoGeral').value,
            acrescimo_tipo: document.getElementById('acrescimoTipo').value,
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
                document.getElementById('clienteNome').value = '';
                document.getElementById('clienteCpf').value = '';
                document.getElementById('clienteWhatsapp').value = '';

                // Atualizar Indicadores de Venda em Tempo Real
                if (data.resumoHoje) {
                    document.getElementById('resumoValor').textContent = data.resumoHoje.valor_total;
                    document.getElementById('resumoQtd').textContent = data.resumoHoje.total_vendas;
                    document.getElementById('resumoTop').textContent = data.resumoHoje.top_produto;
                }

                let msg = '✅ Venda Expressa de R$ ' + data.valor_total + ' registrada com sucesso!';
                if (data.cliente_nome) {
                    msg += '\n👤 Cliente: ' + data.cliente_nome + ' (Cadastrado/Atualizado para Evolution API)';
                }
                alert(msg);
                document.getElementById('inputBuscaProduto').focus();
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
