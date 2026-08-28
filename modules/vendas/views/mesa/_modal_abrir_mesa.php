<?php

use yii\helpers\Html;
use yii\helpers\Url;
?>

<!-- Modal de Abertura de Mesa -->
<div id="modalAbrirMesa" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all">
        <!-- Header -->
        <div class="bg-gradient-to-r from-emerald-600 to-teal-700 px-6 py-4 flex items-center justify-between text-white">
            <div class="flex items-center space-x-2">
                <span class="text-2xl">🍺</span>
                <h3 class="text-lg font-bold" id="modalMesaTitulo">Abrir Mesa</h3>
            </div>
            <button type="button" onclick="fecharModalAbrirMesa()" class="text-white hover:text-gray-200 focus:outline-none text-2xl font-bold">
                &times;
            </button>
        </div>

        <!-- Body Form -->
        <?= Html::beginForm(Url::to(['/vendas/mesa/abrir-mesa']), 'post', ['id' => 'formAbrirMesa', 'class' => 'p-6 space-y-4']) ?>
        <input type="hidden" name="mesa_id" id="inputMesaId">

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Mesa Selecionada</label>
            <input type="text" id="inputMesaNumero" readonly class="w-full px-4 py-2.5 bg-gray-100 border border-gray-300 rounded-xl text-gray-800 font-bold text-lg cursor-not-allowed">
        </div>

        <div>
            <label for="inputClienteNome" class="block text-sm font-semibold text-gray-700 mb-1">Nome do Cliente / Comanda (Opcional)</label>
            <input type="text" name="cliente_nome" id="inputClienteNome" placeholder="Ex: João Silva ou Mesa Central" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-gray-900">
        </div>

        <!-- Footer Actions -->
        <div class="pt-4 flex items-center justify-end space-x-3 border-t border-gray-100">
            <button type="button" onclick="fecharModalAbrirMesa()" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-xl transition duration-150">
                Cancelar
            </button>
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition duration-200">
                🚀 Abrir Atendimento
            </button>
        </div>
        <?= Html::endForm() ?>
    </div>
</div>

<script>
function abrirModalMesa(mesaId, numeroMesa) {
    document.getElementById('inputMesaId').value = mesaId;
    document.getElementById('inputMesaNumero').value = 'Mesa ' + numeroMesa;
    document.getElementById('modalMesaTitulo').innerText = 'Abrir Mesa ' + numeroMesa;
    document.getElementById('inputClienteNome').value = '';
    document.getElementById('modalAbrirMesa').classList.remove('hidden');
    setTimeout(() => document.getElementById('inputClienteNome').focus(), 150);
}

function fecharModalAbrirMesa() {
    document.getElementById('modalAbrirMesa').classList.add('hidden');
}
</script>
