<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Monitor KDS — Cozinha & Bar';
$this->params['breadcrumbs'][] = ['label' => 'Vendas', 'url' => ['/vendas/inicio/index']];
$this->params['breadcrumbs'][] = ['label' => 'Mesas', 'url' => ['/vendas/mesa/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-950 text-gray-100 p-4 sm:p-6 font-sans">
    
    <!-- Top Header Bar -->
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-start md:items-center gap-4 pb-6 border-b border-gray-800">
        <div>
            <h1 class="text-2xl sm:text-4xl font-black text-white tracking-tight flex items-center gap-3">
                <span class="text-amber-400">🍳</span>
                <span>Monitor KDS — Produção</span>
                <span class="bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">Ao Vivo</span>
            </h1>
            <p class="text-xs sm:text-sm text-gray-400 mt-1">Gestão de prepapração em tempo real para Cozinha & Bar.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Filtro de Destino -->
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-1 flex items-center space-x-1">
                <button type="button" onclick="setFiltroDestino('todos')" id="btnFiltro_todos" class="px-3 py-1.5 rounded-lg text-xs font-bold transition duration-150 bg-amber-600 text-white">
                    🍽️ Todos
                </button>
                <button type="button" onclick="setFiltroDestino('cozinha')" id="btnFiltro_cozinha" class="px-3 py-1.5 rounded-lg text-xs font-bold transition duration-150 text-gray-400 hover:text-white">
                    🍳 Cozinha
                </button>
                <button type="button" onclick="setFiltroDestino('bar')" id="btnFiltro_bar" class="px-3 py-1.5 rounded-lg text-xs font-bold transition duration-150 text-gray-400 hover:text-white">
                    🍺 Bar / Bebidas
                </button>
            </div>

            <!-- Botão Voltar ao Mapa -->
            <a href="<?= Url::to(['/vendas/mesa/index']) ?>" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-200 font-bold rounded-xl text-xs transition duration-150 flex items-center gap-2 border border-gray-700">
                <span>🍺</span>
                <span>Mapa de Mesas</span>
            </a>
        </div>
    </div>

    <!-- Indicador de Status & Totalizadores -->
    <div class="max-w-7xl mx-auto my-4 flex items-center justify-between text-xs text-gray-400">
        <div class="flex items-center space-x-4">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span> OK (&lt;10m)</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span> Atenção (10-20m)</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse inline-block"></span> Atrasado (&gt;20m)</span>
        </div>
        <div id="kdsStatusAtualizacao" class="font-mono text-gray-500">
            Atualizado agora
        </div>
    </div>

    <!-- Container do Grid de Pedidos -->
    <div class="max-w-7xl mx-auto">
        <div id="kdsContainerGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <!-- Renderizado dinamicamente via JavaScript -->
        </div>

        <!-- State Vazio -->
        <div id="kdsStateVazio" class="hidden text-center py-20 border-2 border-dashed border-gray-800 rounded-3xl my-8">
            <span class="text-5xl block mb-3">👨‍🍳</span>
            <h3 class="text-lg font-bold text-gray-300">Tudo limpo na produção!</h3>
            <p class="text-xs text-gray-500 mt-1">Nenhum pedido pendente na fila no momento.</p>
        </div>
    </div>

</div>

<!-- Audio para Alerta de Novo Pedido -->
<audio id="audioBeepKds" preload="auto">
    <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
</audio>

<script>
let destinoAtual = 'todos';
let totalItensAnterior = 0;

function setFiltroDestino(destino) {
    destinoAtual = destino;
    ['todos', 'cozinha', 'bar'].forEach(d => {
        const btn = document.getElementById('btnFiltro_' + d);
        if (d === destino) {
            btn.className = 'px-3 py-1.5 rounded-lg text-xs font-bold transition duration-150 bg-amber-600 text-white';
        } else {
            btn.className = 'px-3 py-1.5 rounded-lg text-xs font-bold transition duration-150 text-gray-400 hover:text-white';
        }
    });
    carregarKds();
}

async function carregarKds() {
    try {
        const resp = await fetch('<?= Url::to(['/vendas/kds/listar-pedidos-json']) ?>?destino=' + destinoAtual);
        const data = await resp.json();

        if (!data.success) return;

        const container = document.getElementById('kdsContainerGrid');
        const emptyState = document.getElementById('kdsStateVazio');
        
        let totalItensAtual = 0;
        data.comandas.forEach(c => totalItensAtual += c.itens.length);

        // Se houver novos pedidos que entraram na fila, toca efeito sonoro
        if (totalItensAtual > totalItensAnterior && totalItensAnterior !== 0) {
            const audio = document.getElementById('audioBeepKds');
            if (audio) audio.play().catch(e => {});
        }
        totalItensAnterior = totalItensAtual;

        if (data.comandas.length === 0) {
            container.innerHTML = '';
            emptyState.classList.remove('hidden');
            return;
        }

        emptyState.classList.add('hidden');
        let html = '';

        data.comandas.forEach(comanda => {
            // Define a cor de urgência com base no item mais antigo
            let maxMinutos = 0;
            comanda.itens.forEach(i => {
                if (i.minutos_decorridos > maxMinutos) maxMinutos = i.minutos_decorridos;
            });

            let timerColorClass = 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30';
            if (maxMinutos >= 20) {
                timerColorClass = 'bg-rose-500/20 text-rose-300 border-rose-500/30 animate-pulse';
            } else if (maxMinutos >= 10) {
                timerColorClass = 'bg-amber-500/20 text-amber-300 border-amber-500/30';
            }

            html += `
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4 flex flex-col justify-between shadow-xl relative overflow-hidden">
                    
                    <!-- Header Card -->
                    <div class="flex items-center justify-between pb-3 border-b border-gray-800">
                        <div>
                            <span class="text-base font-black text-white block">${comanda.mesa_numero}</span>
                            <span class="text-xs text-gray-400 font-medium">${comanda.cliente_nome}</span>
                        </div>
                        <div class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold border ${timerColorClass}">
                            ⏱️ ${maxMinutos} min
                        </div>
                    </div>

                    <!-- Lista de Itens -->
                    <div class="my-3 space-y-2.5">
            `;

            comanda.itens.forEach(item => {
                let badgeStatus = '';
                let btnAction = '';

                if (item.status === 'pendente') {
                    badgeStatus = '<span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-amber-500/20 text-amber-300 border border-amber-500/30">⏳ Pendente</span>';
                    btnAction = `<button type="button" onclick="mudarStatusItem('${item.item_id}', 'em_preparo')" class="w-full py-1.5 bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs rounded-xl shadow transition flex items-center justify-center gap-1"><span>🔥</span> Iniciar Preparo</button>`;
                } else if (item.status === 'em_preparo') {
                    badgeStatus = '<span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-blue-500/20 text-blue-300 border border-blue-500/30">🔥 Em Preparo</span>';
                    btnAction = `<button type="button" onclick="mudarStatusItem('${item.item_id}', 'pronto')" class="w-full py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow transition flex items-center justify-center gap-1"><span>✅</span> Concluir Pedido</button>`;
                } else if (item.status === 'pronto') {
                    badgeStatus = '<span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">✅ Pronto</span>';
                    btnAction = `<button type="button" onclick="mudarStatusItem('${item.item_id}', 'entregue')" class="w-full py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-300 font-bold text-xs rounded-xl transition flex items-center justify-center gap-1"><span>🚀</span> Marcar Entregue</button>`;
                }

                html += `
                    <div class="bg-gray-950 p-3 rounded-xl border border-gray-800/80 space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="font-extrabold text-sm text-white">${item.quantidade}x ${item.produto_nome}</span>
                                ${item.observacoes ? `<p class="text-xs text-amber-300 font-semibold mt-0.5 bg-amber-950/40 px-2 py-0.5 rounded border border-amber-800/40">⚠️ ${item.observacoes}</p>` : ''}
                            </div>
                            ${badgeStatus}
                        </div>
                        <div class="pt-1">
                            ${btnAction}
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
        document.getElementById('kdsStatusAtualizacao').innerText = 'Atualizado às ' + new Date().toLocaleTimeString();
    } catch(e) {
        console.error('Erro ao atualizar KDS', e);
    }
}

async function mudarStatusItem(itemId, novoStatus) {
    try {
        const formData = new FormData();
        formData.append('item_id', itemId);
        formData.append('novo_status', novoStatus);

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) formData.append('_csrf', csrfToken);

        const resp = await fetch('<?= Url::to(['/vendas/kds/atualizar-status']) ?>', {
            method: 'POST',
            body: formData
        });

        const data = await resp.json();
        if (data.success) {
            carregarKds();
        } else {
            alert(data.message || 'Erro ao mudar status.');
        }
    } catch(e) {
        alert('Erro ao comunicar com o servidor.');
    }
}

// Inicia polling automático a cada 5 segundos
carregarKds();
setInterval(carregarKds, 5000);
</script>
