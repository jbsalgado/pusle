<?php

use yii\helpers\Html;
use yii\helpers\Url;
?>

<!-- Modal de Fechamento & Divisão de Conta -->
<div id="modalFechamentoMesa" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-70 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-amber-500 via-orange-600 to-amber-700 px-6 py-4 flex items-center justify-between text-white flex-shrink-0">
            <div>
                <h3 class="text-lg font-bold flex items-center gap-2">
                    <span>🧾</span>
                    <span id="modalFechamentoMesaTitulo">Fechar Conta — Mesa 01</span>
                </h3>
                <p class="text-xs text-amber-100 mt-0.5" id="modalFechamentoClienteSubtitulo">Cliente: João Silva</p>
            </div>
            <button type="button" onclick="fecharModalFechamentoMesa()" class="text-white hover:text-gray-200 text-2xl font-bold px-2 focus:outline-none">
                &times;
            </button>
        </div>

        <!-- Body -->
        <div class="p-4 sm:p-6 overflow-y-auto space-y-5 flex-1">

            <!-- Card de Resumo de Valor -->
            <div class="bg-gradient-to-br from-gray-900 to-gray-800 text-white rounded-2xl p-4 shadow-inner flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Total Consumido na Mesa</p>
                    <p class="text-2xl sm:text-3xl font-black text-emerald-400 mt-0.5" id="fechamentoTotalConsumo">R$ 0,00</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-gray-400 block">Saldo Restante</span>
                    <span class="text-lg font-extrabold text-amber-300" id="fechamentoSaldoRestante">R$ 0,00</span>
                </div>
            </div>

            <!-- Calculadora de Divisão de Conta -->
            <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200 space-y-4">
                <h4 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                    <span>🧮</span>
                    <span>Divisão de Conta entre Pessoas</span>
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Número de Pessoas -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Dividir igualmente por N pessoas</label>
                        <div class="flex items-center space-x-2">
                            <button type="button" onclick="alterarPessoas(-1)" class="w-10 h-10 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold rounded-xl text-lg flex items-center justify-center">-</button>
                            <input type="number" id="inputNumPessoas" value="1" min="1" step="1" onchange="recalcularDivisao()" class="w-16 h-10 border border-gray-300 rounded-xl text-center font-extrabold text-base">
                            <button type="button" onclick="alterarPessoas(1)" class="w-10 h-10 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold rounded-xl text-lg flex items-center justify-center">+</button>
                        </div>
                    </div>

                    <!-- Valor por Pessoa -->
                    <div class="bg-amber-50 p-3 rounded-xl border border-amber-200 flex flex-col justify-center">
                        <span class="text-xs font-semibold text-amber-800">Valor por pessoa:</span>
                        <span class="text-xl font-black text-amber-900" id="labelValorPorPessoa">R$ 0,00</span>
                    </div>
                </div>
            </div>

            <!-- Lançamento de Pagamentos (Múltiplos Meios / Valores Diferentes) -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                        <span>💳</span>
                        <span>Pagamentos Registrados</span>
                    </h4>
                    <button type="button" onclick="adicionarLinhaPagamento()" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition duration-150 flex items-center gap-1">
                        <span>+ Adicionar Pagamento</span>
                    </button>
                </div>

                <div id="containerPagamentos" class="space-y-2">
                    <!-- Linhas de Pagamento dinâmicas via JS -->
                </div>
            </div>

            <!-- Canais de Envio do Comprovante (Canal Próprio & WhatsApp) -->
            <div class="bg-gradient-to-br from-indigo-50/70 via-blue-50/60 to-slate-50 rounded-2xl p-4 border border-blue-200/80 space-y-3.5">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs sm:text-sm font-extrabold text-blue-950 flex items-center gap-2 m-0">
                        <span class="text-base">📬</span>
                        <span>Envio de Comprovante & Notificação ao Cliente</span>
                    </h4>
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-[10px] font-black rounded-full uppercase">Multicanal</span>
                </div>

                <div class="space-y-3 pt-1">
                    <!-- Opção 1: Canal Próprio Direct Hub -->
                    <div class="bg-white p-3 rounded-xl border border-indigo-100 shadow-sm flex items-start justify-between gap-3">
                        <div class="flex items-start gap-2.5">
                            <input type="checkbox" id="checkEnviarHub" checked class="mt-0.5 w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                            <div>
                                <label for="checkEnviarHub" class="text-xs font-bold text-gray-900 cursor-pointer flex items-center gap-1.5">
                                    <span>🌐 Canal Próprio (Direct Hub & Recibo Digital)</span>
                                    <span class="px-1.5 py-0.2 bg-emerald-100 text-emerald-800 text-[9px] font-extrabold rounded">Sem Custo / Ilimitado</span>
                                </label>
                                <p class="text-[11px] text-gray-500 mt-0.5 m-0">Notifica o Inbox do cliente no Direct Hub e disponibiliza o comprovante digital online.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Opção 2: WhatsApp Evolution API -->
                    <div class="bg-white p-3 rounded-xl border border-emerald-100 shadow-sm space-y-2.5">
                        <div class="flex items-center justify-between">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="checkEnviarWhatsapp" checked class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                                <span class="ml-2 text-xs font-bold text-gray-900 flex items-center gap-1.5">
                                    <span>📲 Disparo via WhatsApp (Evolution API)</span>
                                </span>
                            </label>
                            <span id="badgeStatusWpFechamento" class="text-[10px] px-2 py-0.5 rounded-full font-bold"></span>
                        </div>

                        <div id="boxInputWhatsapp" class="pl-6">
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">WhatsApp do Cliente (DDD + Número):</label>
                            <input type="text" id="inputWhatsappFechamento" placeholder="(81) 99288-8872" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-medium focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50">
                        </div>
                    </div>
                    <!-- Opção 3: Liberar e Desocupar Mesa -->
                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="checkDesocuparMesa" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                            <span class="ml-2 text-xs font-bold text-gray-800 flex items-center gap-1.5">
                                <span>🧹 Liberar e desocupar a mesa física imediatamente</span>
                            </span>
                        </label>
                        <span class="text-[10px] text-gray-400 font-medium hidden sm:inline">Desmarcado = Mantém 'Conta Paga' para o cliente no celular</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer Actions -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between flex-shrink-0">
            <button type="button" onclick="fecharModalFechamentoMesa()" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold text-xs rounded-xl">
                Cancelar
            </button>

            <button type="button" onclick="processarFechamentoMesa()" id="btnProcessarFechamento" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs sm:text-sm rounded-xl shadow-lg hover:shadow-xl transition duration-150 flex items-center gap-2">
                <span>💳</span>
                <span>Processar Pagamento & Fechar Conta</span>
            </button>
        </div>

    </div>
</div>

<script>
let mesaIdFechamento = null;
let valorTotalMesa = 0.00;
let formasPagamentoLista = [];

async function abrirModalFechamento(mesaId, numeroMesa, clienteNome) {
    mesaIdFechamento = mesaId;
    document.getElementById('modalFechamentoMesaTitulo').innerText = 'Fechar Conta — Mesa ' + numeroMesa;
    document.getElementById('modalFechamentoClienteSubtitulo').innerText = 'Cliente: ' + (clienteNome || 'Cliente');

    // Carrega dados da mesa e formas de pagamento
    try {
        const resp = await fetch('<?= Url::to(['/vendas/mesa/dados-fechamento-json']) ?>?mesa_id=' + mesaId);
        const data = await resp.json();

        if (!data.success) {
            alert(data.message || 'Erro ao carregar dados da mesa.');
            return;
        }

        valorTotalMesa = data.valor_total;
        formasPagamentoLista = data.formas_pagamento || [];

        document.getElementById('fechamentoTotalConsumo').innerText = 'R$ ' + data.valor_total_formatado;
        document.getElementById('inputNumPessoas').value = 1;
        document.getElementById('containerPagamentos').innerHTML = '';

        const badgeWp = document.getElementById('badgeStatusWpFechamento');
        if (badgeWp) {
            if (data.whatsapp_conectado) {
                badgeWp.className = 'text-[10px] px-2 py-0.5 rounded-full font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200';
                badgeWp.innerText = '🟢 Conectado';
            } else {
                badgeWp.className = 'text-[10px] px-2 py-0.5 rounded-full font-extrabold bg-amber-100 text-amber-800 border border-amber-200';
                badgeWp.innerText = '⚠️ Desconectado';
            }
        }

        // Cria a primeira linha de pagamento com o valor total
        adicionarLinhaPagamento(valorTotalMesa);
        recalcularDivisao();

        document.getElementById('modalFechamentoMesa').classList.remove('hidden');
    } catch(e) {
        alert('Erro ao abrir fechamento da mesa.');
    }
}

function fecharModalFechamentoMesa() {
    document.getElementById('modalFechamentoMesa').classList.add('hidden');
}

function alterarPessoas(delta) {
    const input = document.getElementById('inputNumPessoas');
    let val = parseInt(input.value) || 1;
    val += delta;
    if (val < 1) val = 1;
    input.value = val;
    recalcularDivisao();
}

function recalcularDivisao() {
    const numPessoas = parseInt(document.getElementById('inputNumPessoas').value) || 1;
    const valorPorPessoa = valorTotalMesa / numPessoas;
    document.getElementById('labelValorPorPessoa').innerText = 'R$ ' + valorPorPessoa.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    recalcularSaldo();
}

function adicionarLinhaPagamento(valorSugerido = 0.00) {
    const container = document.getElementById('containerPagamentos');
    const index = container.children.length;

    let optionsHtml = '';
    formasPagamentoLista.forEach(f => {
        optionsHtml += `<option value="${f.id}">${f.nome}</option>`;
    });

    const div = document.createElement('div');
    div.className = 'grid grid-cols-1 sm:grid-cols-12 gap-2 items-center bg-white p-2.5 rounded-xl border border-gray-200 shadow-sm';
    div.id = 'linhaPagamento_' + index;

    div.innerHTML = `
        <div class="sm:col-span-6">
            <select class="select-forma-pagamento w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-semibold">
                ${optionsHtml}
            </select>
        </div>
        <div class="sm:col-span-5">
            <input type="number" step="0.01" value="${valorSugerido.toFixed(2)}" onchange="recalcularSaldo()" class="input-valor-pagamento w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-bold text-emerald-600">
        </div>
        <div class="sm:col-span-1 text-center">
            <button type="button" onclick="removerLinhaPagamento('${div.id}')" class="text-rose-600 hover:text-rose-800 font-bold text-xs">🗑️</button>
        </div>
    `;

    container.appendChild(div);
    recalcularSaldo();
}

function removerLinhaPagamento(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
    recalcularSaldo();
}

function recalcularSaldo() {
    let totalPago = 0.00;
    const inputsValores = document.querySelectorAll('.input-valor-pagamento');
    inputsValores.forEach(inp => {
        totalPago += parseFloat(inp.value) || 0.00;
    });

    const restante = valorTotalMesa - totalPago;
    const labelRestante = document.getElementById('fechamentoSaldoRestante');
    
    labelRestante.innerText = 'R$ ' + Math.max(0, restante).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (Math.abs(restante) < 0.01) {
        labelRestante.className = 'text-lg font-black text-emerald-400';
    } else {
        labelRestante.className = 'text-lg font-black text-amber-300';
    }
}

async function processarFechamentoMesa() {
    const inputsForma = document.querySelectorAll('.select-forma-pagamento');
    const inputsValores = document.querySelectorAll('.input-valor-pagamento');

    const pagamentos = [];
    let somaPagos = 0.00;

    inputsForma.forEach((select, i) => {
        const val = parseFloat(inputsValores[i].value) || 0.00;
        if (val > 0) {
            pagamentos.push({
                forma_pagamento_id: select.value,
                valor: val
            });
            somaPagos += val;
        }
    });

    if (pagamentos.length === 0) {
        alert('Adicione pelo menos um meio de pagamento com valor maior que zero!');
        return;
    }

    if (somaPagos < valorTotalMesa - 0.05) {
        alert('O valor total informado nos pagamentos (R$ ' + somaPagos.toFixed(2) + ') é menor que o consumo da mesa (R$ ' + valorTotalMesa.toFixed(2) + ')!');
        return;
    }

    const btn = document.getElementById('btnProcessarFechamento');
    btn.disabled = true;
    btn.innerText = 'Processando Fechamento...';

    try {
        const formData = new FormData();
        formData.append('mesa_id', mesaIdFechamento);
        formData.append('pagamentos', JSON.stringify(pagamentos));
        formData.append('num_pessoas', document.getElementById('inputNumPessoas').value);
        formData.append('enviar_hub', document.getElementById('checkEnviarHub')?.checked ? 1 : 0);
        formData.append('whatsapp', document.getElementById('inputWhatsappFechamento').value);
        formData.append('enviar_whatsapp', document.getElementById('checkEnviarWhatsapp').checked ? 1 : 0);
        formData.append('desocupar_mesa', document.getElementById('checkDesocuparMesa')?.checked ? 1 : 0);

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            formData.append('_csrf', csrfToken);
        }

        const resp = await fetch('<?= Url::to(['/vendas/mesa/processar-fechamento']) ?>', {
            method: 'POST',
            body: formData
        });

        const data = await resp.json();
        if (data.success) {
            alert(data.message || 'Conta da mesa finalizada com sucesso!');
            fecharModalFechamentoMesa();
            window.location.reload();
        } else {
            alert(data.message || 'Erro ao processar fechamento.');
        }
    } catch(e) {
        alert('Erro de conexão ao fechar mesa.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<span>💳</span><span>Processar Pagamento & Fechar Conta</span>';
    }
}
</script>
