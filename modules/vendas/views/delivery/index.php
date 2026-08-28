<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Gestão de Delivery & Entregas — PULSE Food Service';
?>

<div class="min-h-screen bg-gray-900 text-gray-100 p-4 sm:p-6">
    
    <!-- Top Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-gray-800">
        <div>
            <h1 class="text-2xl font-black text-white flex items-center gap-2">
                <span>🛵</span>
                <span>Gestão de Delivery & Entregas</span>
                <span class="bg-emerald-500/20 text-emerald-400 text-xs font-extrabold px-3 py-1 rounded-full uppercase border border-emerald-500/30">Ao Vivo</span>
            </h1>
            <p class="text-xs text-gray-400 mt-1">Expedição unificada com avisos automáticos de status por WhatsApp via Evolution API.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="abrirModalNovoPedidoDelivery()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs sm:text-sm rounded-xl shadow transition flex items-center gap-1.5">
                <span>➕</span>
                <span>Novo Pedido Delivery</span>
            </button>

            <a href="<?= Url::to(['/vendas/totem/index']) ?>" target="_blank" class="px-3.5 py-2 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs sm:text-sm rounded-xl transition flex items-center gap-1.5" title="Abrir Totem Kiosk Fast-Food">
                <span>🖥️</span>
                <span>Totem Kiosk</span>
            </a>

            <a href="<?= Url::to(['/vendas/kds/painel-senhas']) ?>" target="_blank" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs sm:text-sm rounded-xl transition flex items-center gap-1.5" title="Abrir Painel da TV de Chamada de Senhas">
                <span>📺</span>
                <span>TV Senhas</span>
            </a>

            <a href="<?= Url::to(['/vendas/delivery/motoboy']) ?>" target="_blank" class="px-3.5 py-2 bg-teal-600 hover:bg-teal-500 text-white font-bold text-xs sm:text-sm rounded-xl transition flex items-center gap-1.5" title="Abrir App de GPS do Motoboy">
                <span>📡</span>
                <span>App Motoboy</span>
            </a>

            <a href="<?= Url::to(['/vendas/mesa/index']) ?>" class="px-3.5 py-2 bg-gray-800 hover:bg-gray-700 text-gray-200 font-bold text-xs sm:text-sm rounded-xl transition flex items-center gap-1.5">
                <span>🍽️</span>
                <span>Mapa de Mesas</span>
            </a>

            <a href="<?= Url::to(['/vendas/kds/index']) ?>" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs sm:text-sm rounded-xl transition flex items-center gap-1.5">
                <span>🍳</span>
                <span>Monitor KDS</span>
            </a>
        </div>
    </div>

    <!-- Kanban Grid -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-6 overflow-x-auto pb-6">
        
        <!-- Coluna 1: Recebidos -->
        <div class="bg-gray-800/60 rounded-2xl p-3 border border-gray-700/50 flex flex-col min-h-[500px]">
            <div class="flex items-center justify-between pb-3 border-b border-gray-700">
                <h3 class="text-xs font-black text-blue-400 uppercase tracking-wider flex items-center gap-1.5">
                    <span>📥</span>
                    <span>1. Recebidos</span>
                </h3>
                <span id="badgeCountRecebido" class="bg-blue-500/20 text-blue-300 font-black text-xs px-2 py-0.5 rounded-full">0</span>
            </div>
            <div id="colRecebido" class="space-y-3 mt-3 flex-1 overflow-y-auto max-h-[70vh]"></div>
        </div>

        <!-- Coluna 2: Em Preparo -->
        <div class="bg-gray-800/60 rounded-2xl p-3 border border-gray-700/50 flex flex-col min-h-[500px]">
            <div class="flex items-center justify-between pb-3 border-b border-gray-700">
                <h3 class="text-xs font-black text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
                    <span>🍳</span>
                    <span>2. Em Preparo</span>
                </h3>
                <span id="badgeCountEmPreparo" class="bg-amber-500/20 text-amber-300 font-black text-xs px-2 py-0.5 rounded-full">0</span>
            </div>
            <div id="colEmPreparo" class="space-y-3 mt-3 flex-1 overflow-y-auto max-h-[70vh]"></div>
        </div>

        <!-- Coluna 3: Prontos / Expedição -->
        <div class="bg-gray-800/60 rounded-2xl p-3 border border-gray-700/50 flex flex-col min-h-[500px]">
            <div class="flex items-center justify-between pb-3 border-b border-gray-700">
                <h3 class="text-xs font-black text-purple-400 uppercase tracking-wider flex items-center gap-1.5">
                    <span>📦</span>
                    <span>3. Pronto / Expedição</span>
                </h3>
                <span id="badgeCountPronto" class="bg-purple-500/20 text-purple-300 font-black text-xs px-2 py-0.5 rounded-full">0</span>
            </div>
            <div id="colPronto" class="space-y-3 mt-3 flex-1 overflow-y-auto max-h-[70vh]"></div>
        </div>

        <!-- Coluna 4: Em Rota -->
        <div class="bg-gray-800/60 rounded-2xl p-3 border border-gray-700/50 flex flex-col min-h-[500px]">
            <div class="flex items-center justify-between pb-3 border-b border-gray-700">
                <h3 class="text-xs font-black text-teal-400 uppercase tracking-wider flex items-center gap-1.5">
                    <span>🛵</span>
                    <span>4. Em Rota</span>
                </h3>
                <span id="badgeCountEmRota" class="bg-teal-500/20 text-teal-300 font-black text-xs px-2 py-0.5 rounded-full">0</span>
            </div>
            <div id="colEmRota" class="space-y-3 mt-3 flex-1 overflow-y-auto max-h-[70vh]"></div>
        </div>

        <!-- Coluna 5: Entregues -->
        <div class="bg-gray-800/60 rounded-2xl p-3 border border-gray-700/50 flex flex-col min-h-[500px]">
            <div class="flex items-center justify-between pb-3 border-b border-gray-700">
                <h3 class="text-xs font-black text-emerald-400 uppercase tracking-wider flex items-center gap-1.5">
                    <span>✅</span>
                    <span>5. Entregues</span>
                </h3>
                <span id="badgeCountEntregue" class="bg-emerald-500/20 text-emerald-300 font-black text-xs px-2 py-0.5 rounded-full">0</span>
            </div>
            <div id="colEntregue" class="space-y-3 mt-3 flex-1 overflow-y-auto max-h-[70vh]"></div>
        </div>

    </div>

</div>

<!-- Modal Novo Pedido Delivery -->
<div id="modalNovoPedidoDelivery" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg bg-gray-900 border border-gray-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col text-gray-100">
        
        <div class="bg-gradient-to-r from-emerald-600 to-teal-700 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-bold flex items-center gap-2 text-white">
                <span>🛵</span>
                <span>Novo Pedido de Delivery</span>
            </h3>
            <button type="button" onclick="fecharModalNovoPedidoDelivery()" class="text-white text-2xl font-bold px-2 focus:outline-none">&times;</button>
        </div>

        <?= Html::beginForm(Url::to(['/vendas/delivery/novo-pedido']), 'post', ['class' => 'p-6 space-y-4']) ?>
            <div>
                <label class="block text-xs font-bold text-gray-300 mb-1">Nome do Cliente *</label>
                <input type="text" name="cliente_nome" placeholder="Ex: Maria Oliveira" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-xl text-sm font-bold text-white" required>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">WhatsApp / Telefone</label>
                    <input type="text" name="cliente_telefone" placeholder="Ex: 11999998888" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-xl text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Taxa de Entrega (R$)</label>
                    <input type="text" name="taxa_entrega" value="5.00" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-xl text-sm font-bold text-emerald-400">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1">Endereço Completo de Entrega</label>
                <textarea name="endereco_entrega" rows="2" placeholder="Rua, número, complemento, bairro..." class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-xl text-sm text-white"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1">Entregador / Motoboy (Opcional)</label>
                <input type="text" name="motoboy_nome" placeholder="Ex: João Motoboy" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-xl text-sm text-white">
            </div>

            <div class="pt-4 border-t border-gray-800 flex items-center justify-end space-x-3">
                <button type="button" onclick="fecharModalNovoPedidoDelivery()" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 font-bold text-xs rounded-xl">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs rounded-xl shadow transition">➕ Salvar & Lançar Itens</button>
            </div>
        <?= Html::endForm() ?>

    </div>
</div>

<script>
async function carregarKanbanDelivery() {
    try {
        const resp = await fetch('<?= Url::to(['/vendas/delivery/listar-pedidos-json']) ?>');
        const data = await resp.json();

        if (!data.success) return;

        const cols = {
            recebido: document.getElementById('colRecebido'),
            em_preparo: document.getElementById('colEmPreparo'),
            pronto: document.getElementById('colPronto'),
            em_rota: document.getElementById('colEmRota'),
            entregue: document.getElementById('colEntregue'),
        };

        const counts = { recebido: 0, em_preparo: 0, pronto: 0, em_rota: 0, entregue: 0 };

        // Limpa colunas
        Object.keys(cols).forEach(k => cols[k].innerHTML = '');

        data.pedidos.forEach(p => {
            const st = p.status_delivery || 'recebido';
            if (counts[st] !== undefined) counts[st]++;

            const card = document.createElement('div');
            card.className = "bg-gray-800 border border-gray-700 rounded-xl p-3 shadow hover:border-gray-600 transition flex flex-col justify-between text-xs space-y-2";
            
            let itensHtml = '';
            p.itens.forEach(it => {
                const obs = it.observacoes ? ` <span class="text-amber-400">(${it.observacoes})</span>` : '';
                itensHtml += `<div><strong>${it.quantidade}x</strong> ${it.produto_nome}${obs}</div>`;
            });

            const phoneClean = p.cliente_telefone ? p.cliente_telefone.replace(/\D/g, '') : '';
            const waBtn = phoneClean ? `<a href="https://wa.me/55${phoneClean}" target="_blank" class="text-emerald-400 hover:underline">💬 ${p.cliente_telefone}</a>` : '';

            card.innerHTML = `
                <div>
                    <div class="flex items-center justify-between pb-1 border-b border-gray-700/60 font-bold">
                        <span class="text-gray-100">${p.numero_comanda}</span>
                        <span class="text-emerald-400 font-black">R$ ${p.valor_total.toFixed(2).replace('.', ',')}</span>
                    </div>
                    <div class="mt-1 font-bold text-white">${p.cliente_nome}</div>
                    <div class="text-[11px] text-gray-400 mt-0.5">${waBtn}</div>
                    <div class="text-[11px] text-gray-400 mt-1 flex items-start gap-1">
                        <span>📍</span>
                        <span>${p.endereco_entrega}</span>
                    </div>
                    ${itensHtml ? `<div class="mt-2 bg-gray-900/60 p-2 rounded-lg text-[11px] space-y-0.5 text-gray-300 border border-gray-700/40">${itensHtml}</div>` : ''}
                </div>

                <div class="pt-2 border-t border-gray-700/60 flex flex-col gap-1">
                    ${getAcoesStatus(p)}
                </div>
            `;

            if (cols[st]) {
                cols[st].appendChild(card);
            }
        });

        // Atualiza contadores
        document.getElementById('badgeCountRecebido').innerText = counts.recebido;
        document.getElementById('badgeCountEmPreparo').innerText = counts.em_preparo;
        document.getElementById('badgeCountPronto').innerText = counts.pronto;
        document.getElementById('badgeCountEmRota').innerText = counts.em_rota;
        document.getElementById('badgeCountEntregue').innerText = counts.entregue;

    } catch(e) {
        console.error('Erro no polling do delivery:', e);
    }
}

function getAcoesStatus(p) {
    const st = p.status_delivery || 'recebido';
    if (st === 'recebido') {
        return `<button onclick="mudarStatus('${p.id}', 'em_preparo')" class="w-full py-1 bg-amber-600 hover:bg-amber-500 text-white font-bold rounded-lg text-[11px]">🍳 Iniciar Preparo</button>`;
    } else if (st === 'em_preparo') {
        return `<button onclick="mudarStatus('${p.id}', 'pronto')" class="w-full py-1 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-lg text-[11px]">📦 Marcar Pronto</button>`;
    } else if (st === 'pronto') {
        return `<button onclick="mudarStatusComMotoboy('${p.id}', 'em_rota')" class="w-full py-1 bg-teal-600 hover:bg-teal-500 text-white font-bold rounded-lg text-[11px]">🛵 Despachar p/ Entrega</button>`;
    } else if (st === 'em_rota') {
        return `<button onclick="mudarStatus('${p.id}', 'entregue')" class="w-full py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg text-[11px]">✅ Confirmar Entregue</button>`;
    } else {
        return `<span class="text-center text-emerald-400 font-bold">✅ Concluído</span>`;
    }
}

async function mudarStatus(pedidoId, novoStatus, motoboy = '') {
    try {
        const formData = new FormData();
        formData.append('pedido_id', pedidoId);
        formData.append('novo_status', novoStatus);
        if (motoboy) formData.append('motoboy_nome', motoboy);

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) formData.append('_csrf', csrfToken);

        const resp = await fetch('<?= Url::to(['/vendas/delivery/atualizar-status']) ?>', {
            method: 'POST',
            body: formData
        });

        const data = await resp.json();
        if (data.success) {
            carregarKanbanDelivery();
        } else {
            alert(data.message || 'Erro ao atualizar status.');
        }
    } catch(e) {
        alert('Erro ao atualizar status.');
    }
}

function mudarStatusComMotoboy(pedidoId, novoStatus) {
    const motoboy = prompt("Nome do Entregador / Motoboy (Opcional):", "");
    mudarStatus(pedidoId, novoStatus, motoboy);
}

function abrirModalNovoPedidoDelivery() {
    document.getElementById('modalNovoPedidoDelivery').classList.remove('hidden');
}

function fecharModalNovoPedidoDelivery() {
    document.getElementById('modalNovoPedidoDelivery').classList.add('hidden');
}

// Inicialização & Polling Automático a cada 10s
carregarKanbanDelivery();
setInterval(carregarKanbanDelivery, 10000);
</script>
