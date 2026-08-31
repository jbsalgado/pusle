<?php

use yii\helpers\Html;
use yii\helpers\Url;
?>

<!-- Modal de Lançamento de Pedidos & Extrato da Mesa -->
<div id="modalLancamentoItem" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-70 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        
        <!-- Header do Modal -->
        <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 px-6 py-4 flex items-center justify-between text-white flex-shrink-0">
            <div>
                <h3 class="text-lg font-bold flex items-center gap-2">
                    <span>🍺</span>
                    <span id="modalLancamentoMesaTitulo">Mesa 01 — Consumo & Pedidos</span>
                </h3>
                <p class="text-xs text-emerald-100 mt-0.5" id="modalLancamentoClienteSubtitulo">Cliente: João Silva</p>
            </div>

            <div class="flex items-center space-x-2">
                <!-- Atalho para Chat da Mesa -->
                <button type="button" onclick="abrirChatDaMesaAtualLancamento()" class="px-3 py-1.5 bg-indigo-500 hover:bg-indigo-600 text-white font-bold text-xs rounded-xl shadow transition duration-150 flex items-center gap-1" title="Abrir Chat em Tempo Real com a Mesa">
                    <span>💬</span>
                    <span>Chat Mesa</span>
                </button>

                <!-- Botão de Impressão Térmica -->
                <a id="btnImprimirCupomModal" href="#" target="_blank" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-900 text-white font-bold text-xs rounded-xl shadow transition duration-150 flex items-center gap-1" title="Imprimir Cupom Térmico (80mm / 58mm)">
                    <span>🖨️</span>
                    <span>Imprimir Cupom</span>
                </a>

                <!-- Atalho para Cadastro Rápido de Produto -->
                <button type="button" onclick="abrirCadastroRapidoDoModal()" class="px-3 py-1.5 bg-amber-400 hover:bg-amber-500 text-gray-900 font-bold text-xs rounded-xl shadow transition duration-150 flex items-center gap-1">
                    <span>⚡</span>
                    <span>Novo Produto</span>
                </button>
                <button type="button" onclick="fecharModalLancamentoItem()" class="text-white hover:text-gray-200 text-2xl font-bold px-2 focus:outline-none">
                    &times;
                </button>
            </div>
        </div>

        <!-- Conteúdo do Modal (Abas ou Layout Dividido) -->
        <div class="p-4 sm:p-6 overflow-y-auto space-y-6 flex-1">
            
            <!-- Formulário de Adicionar Pedido -->
            <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-200 shadow-sm space-y-4">
                <h4 class="text-sm font-bold text-emerald-900 flex items-center gap-2">
                    <span>➕</span>
                    <span>Adicionar Novo Item à Mesa</span>
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <!-- Produto Dropdown / Busca -->
                    <div class="sm:col-span-7">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Selecione o Produto</label>
                        <select id="selectProdutoLancamento" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 text-sm font-medium bg-white">
                            <option value="">Carregando produtos...</option>
                        </select>
                    </div>

                    <!-- Quantidade -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Qtd</label>
                        <input type="number" id="inputQtdLancamento" value="1" min="1" step="1" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-center font-bold text-sm">
                    </div>

                    <!-- Destino Preparo -->
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Estação</label>
                        <select id="selectDestinoLancamento" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-medium bg-white">
                            <option value="cozinha">🍳 Cozinha</option>
                            <option value="bar">🍹 Bar/Copa</option>
                            <option value="chapa">🍔 Chapa</option>
                        </select>
                    </div>

                    <!-- Container Dinâmico de Opcionais / Adicionais -->
                    <div id="containerOpcionaisLancamento" class="sm:col-span-12 hidden bg-white p-3 rounded-xl border border-emerald-300 space-y-2">
                        <span class="text-xs font-bold text-emerald-900 block">Adicionais & Opcionais:</span>
                        <div id="listOpcionaisCheckboxes" class="flex flex-wrap gap-2 text-xs"></div>
                    </div>

                    <!-- Observação -->
                    <div class="sm:col-span-9">
                        <input type="text" id="inputObsLancamento" placeholder="Obs: Sem cebola, com gelo e limão..." class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs">
                    </div>

                    <!-- Botão Adicionar -->
                    <div class="sm:col-span-3">
                        <button type="button" onclick="salvarItemComanda()" id="btnSalvarItem" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition duration-150 flex items-center justify-center gap-1">
                            <span>⚡</span>
                            <span>Lançar Pedido</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Extrato dos Itens Lançados -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                        <span>🧾</span>
                        <span>Consumo Atual da Mesa</span>
                    </h4>
                    <span id="labelTotalExtrato" class="text-base font-black text-emerald-600">Total: R$ 0,00</span>
                </div>

                <div class="border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-100 text-gray-600 uppercase font-bold text-[10px]">
                            <tr>
                                <th class="p-3">Item / Produto</th>
                                <th class="p-3 text-center">Qtd</th>
                                <th class="p-3 text-right">Unitário</th>
                                <th class="p-3 text-right">Subtotal</th>
                                <th class="p-3 text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyExtratoConsumo" class="divide-y divide-gray-100 bg-white">
                            <tr>
                                <td colspan="5" class="p-4 text-center text-gray-400">Nenhum item consumido ainda nesta mesa.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Footer Modal -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between flex-shrink-0">
            <span class="text-xs text-gray-500">Pedidos entram diretamente na fila de preparo.</span>
            <button type="button" onclick="fecharModalLancamentoItem()" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold text-xs rounded-xl">
                Fechar Janela
            </button>
        </div>
    </div>
</div>

<script>
let mesaIdAtual = null;
let numeroMesaAtual = null;

function abrirModalLancamento(mesaId, numeroMesa, clienteNome) {
    mesaIdAtual = mesaId;
    numeroMesaAtual = numeroMesa;
    document.getElementById('modalLancamentoMesaTitulo').innerText = 'Mesa ' + numeroMesa + ' — Consumo & Pedidos';
    document.getElementById('modalLancamentoClienteSubtitulo').innerText = 'Cliente: ' + (clienteNome || 'Cliente');
    document.getElementById('btnImprimirCupomModal').href = '<?= Url::to(['/vendas/mesa/imprimir-comprovante']) ?>?mesa_id=' + mesaId;
    
    // Carrega produtos no select
    carregarProdutosLancamento();
    
    // Carrega extrato atual da mesa
    carregarExtratoMesa(mesaId);

    document.getElementById('modalLancamentoItem').classList.remove('hidden');
}

function abrirChatDaMesaAtualLancamento() {
    if (mesaIdAtual && numeroMesaAtual) {
        document.getElementById('modalLancamentoItem').classList.add('hidden');
        if (typeof window.abrirModalRespostaGarcom === 'function') {
            window.abrirModalRespostaGarcom(mesaIdAtual, numeroMesaAtual);
        }
    }
}

function fecharModalLancamentoItem() {
    document.getElementById('modalLancamentoItem').classList.add('hidden');
    // Atualiza a página para refletir valores atualizados nos cards
    window.location.reload();
}

async function carregarProdutosLancamento() {
    try {
        const resp = await fetch('<?= Url::to(['/vendas/mesa/buscar-produtos-json']) ?>');
        const produtos = await resp.json();
        
        const select = document.getElementById('selectProdutoLancamento');
        select.innerHTML = '<option value="">-- Selecione o Produto --</option>';
        
        produtos.forEach(p => {
            select.innerHTML += `<option value="${p.id}">[R$ ${p.preco_formatado}] ${p.nome}</option>`;
        });
    } catch(e) {
        console.error('Erro ao carregar produtos:', e);
    }
}

document.getElementById('selectProdutoLancamento').addEventListener('change', async function() {
    const prodId = this.value;
    const container = document.getElementById('containerOpcionaisLancamento');
    const list = document.getElementById('listOpcionaisCheckboxes');
    list.innerHTML = '';
    container.classList.add('hidden');

    if (!prodId) return;

    try {
        const resp = await fetch('<?= Url::to(['/vendas/produto/opcionais-json']) ?>?produto_id=' + prodId);
        const data = await resp.json();

        if (data.success && data.opcionais && data.opcionais.length > 0) {
            container.classList.remove('hidden');
            let html = '';
            data.opcionais.forEach(op => {
                html += `
                    <label class="inline-flex items-center gap-1.5 bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-lg cursor-pointer hover:bg-emerald-50">
                        <input type="checkbox" class="chk-opcional text-emerald-600 rounded" data-id="${op.id}" data-nome="${op.nome}" data-valor="${op.valor_adicional}">
                        <span class="font-bold text-gray-800">${op.nome}</span>
                        <span class="text-emerald-700 font-extrabold">(+R$ ${op.valor_formatado})</span>
                    </label>
                `;
            });
            list.innerHTML = html;
        }
    } catch(e) {}
});

async function carregarExtratoMesa(mesaId) {
    try {
        const resp = await fetch('<?= Url::to(['/vendas/mesa/ver-consumo-json']) ?>?mesa_id=' + mesaId);
        const data = await resp.json();
        
        const tbody = document.getElementById('tbodyExtratoConsumo');
        const labelTotal = document.getElementById('labelTotalExtrato');
        
        if (!data.success || !data.itens || data.itens.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-400">Nenhum item consumido ainda nesta mesa.</td></tr>';
            labelTotal.innerText = 'Total: R$ 0,00';
            return;
        }

        labelTotal.innerText = 'Total: R$ ' + data.valor_total_formatado;

        let html = '';
        data.itens.forEach(item => {
            const obsText = item.observacoes ? `<br><span class="text-[10px] text-amber-600 font-semibold">Obs: ${item.observacoes}</span>` : '';
            html += `
                <tr class="hover:bg-gray-50">
                    <td class="p-3 font-semibold text-gray-900">
                        ${item.produto_nome}
                        ${obsText}
                    </td>
                    <td class="p-3 text-center font-bold text-gray-700">${item.quantidade}</td>
                    <td class="p-3 text-right text-gray-500">R$ ${item.valor_unitario_formatado}</td>
                    <td class="p-3 text-right font-black text-emerald-600">R$ ${item.subtotal_formatado}</td>
                    <td class="p-3 text-center">
                        <button type="button" onclick="removerItemExtrato('${item.id}')" class="text-rose-600 hover:text-rose-800 font-bold text-xs" title="Remover item">
                            🗑️
                        </button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    } catch(e) {
        console.error('Erro ao carregar extrato:', e);
    }
}

async function salvarItemComanda() {
    const produtoId = document.getElementById('selectProdutoLancamento').value;
    const quantidade = document.getElementById('inputQtdLancamento').value;
    const destino = document.getElementById('selectDestinoLancamento').value;
    let observacoes = document.getElementById('inputObsLancamento').value.trim();

    if (!produtoId) {
        alert('Selecione um produto!');
        return;
    }

    // Coleta adicionais/opcionais selecionados
    let valorAdicionalTotal = 0.00;
    const opcionaisNomes = [];
    const chks = document.querySelectorAll('.chk-opcional:checked');
    chks.forEach(chk => {
        const val = parseFloat(chk.getAttribute('data-valor')) || 0.00;
        const nome = chk.getAttribute('data-nome');
        valorAdicionalTotal += val;
        opcionaisNomes.push(nome);
    });

    if (opcionaisNomes.length > 0) {
        const strOps = 'Adicionais: ' + opcionaisNomes.join(', ');
        observacoes = observacoes ? (strOps + ' | ' + observacoes) : strOps;
    }

    const btn = document.getElementById('btnSalvarItem');
    btn.disabled = true;
    btn.innerText = 'Lançando...';

    try {
        const formData = new FormData();
        formData.append('mesa_id', mesaIdAtual);
        formData.append('produto_id', produtoId);
        formData.append('quantidade', quantidade);
        formData.append('destino_preparo', destino);
        formData.append('observacoes', observacoes);
        formData.append('valor_adicional', valorAdicionalTotal);

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            formData.append('_csrf', csrfToken);
        }

        const resp = await fetch('<?= Url::to(['/vendas/mesa/adicionar-item']) ?>', {
            method: 'POST',
            body: formData
        });

        const data = await resp.json();
        if (data.success) {
            // Limpa formulário
            document.getElementById('selectProdutoLancamento').value = '';
            document.getElementById('inputObsLancamento').value = '';
            document.getElementById('inputQtdLancamento').value = '1';
            document.getElementById('containerOpcionaisLancamento').classList.add('hidden');
            document.getElementById('listOpcionaisCheckboxes').innerHTML = '';
            
            // Recarrega extrato
            carregarExtratoMesa(mesaIdAtual);
        } else {
            alert(data.message || 'Erro ao lançar item.');
        }
    } catch(e) {
        alert('Erro ao salvar item na mesa.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<span>⚡</span><span>Lançar Pedido</span>';
    }
}

async function removerItemExtrato(itemId) {
    if (!confirm('Deseja cancelar/remover este item da comanda?')) return;

    try {
        const formData = new FormData();
        formData.append('item_id', itemId);

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            formData.append('_csrf', csrfToken);
        }

        const resp = await fetch('<?= Url::to(['/vendas/mesa/remover-item']) ?>', {
            method: 'POST',
            body: formData
        });

        const data = await resp.json();
        if (data.success) {
            carregarExtratoMesa(mesaIdAtual);
        } else {
            alert(data.message || 'Erro ao remover item.');
        }
    } catch(e) {
        alert('Erro ao remover item.');
    }
}

function abrirCadastroRapidoDoModal() {
    if (typeof abrirModalCadastroRapido === 'function') {
        abrirModalCadastroRapido();
    } else {
        alert('Cadastro Rápido disponível no painel.');
    }
}
</script>
