<?php

use yii\helpers\Html;
use yii\helpers\Url;
?>

<!-- Modal de Geração de Múltiplas Mesas em Lote -->
<div id="modalGerarLoteMesas" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-70 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-600 to-indigo-700 px-6 py-4 flex items-center justify-between text-white">
            <h3 class="text-lg font-bold flex items-center gap-2">
                <span>🚀</span>
                <span>Acrescentar Múltiplas Mesas</span>
            </h3>
            <button type="button" onclick="fecharModalLoteMesas()" class="text-white hover:text-gray-200 text-2xl font-bold px-2 focus:outline-none">
                &times;
            </button>
        </div>

        <!-- Body -->
        <?= Html::beginForm(Url::to(['/vendas/mesa/gerar-lote-mesas']), 'post', ['class' => 'p-6 space-y-4']) ?>
            <div class="bg-purple-50 border border-purple-200 rounded-xl p-3 text-xs text-purple-900">
                Gere automaticamente um conjunto de novas mesas em sequência numérica para o seu salão.
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Quantas mesas deseja acrescentar?</label>
                <div class="flex items-center space-x-2">
                    <input type="number" name="quantidade" value="5" min="1" max="50" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-base font-black text-purple-900 text-center" required>
                </div>
                <span class="text-[11px] text-gray-500 mt-1 block">Exemplo: Digite 5 para gerar automaticamente 5 novas mesas numeradas em sequência.</span>
            </div>

            <!-- Botões de Seleção Rápida -->
            <div class="flex items-center space-x-2 pt-1">
                <button type="button" onclick="setQtdLote(5)" class="px-3 py-1 bg-gray-100 hover:bg-purple-100 text-gray-800 text-xs font-bold rounded-lg border border-gray-300">
                    +5 Mesas
                </button>
                <button type="button" onclick="setQtdLote(10)" class="px-3 py-1 bg-gray-100 hover:bg-purple-100 text-gray-800 text-xs font-bold rounded-lg border border-gray-300">
                    +10 Mesas
                </button>
                <button type="button" onclick="setQtdLote(20)" class="px-3 py-1 bg-gray-100 hover:bg-purple-100 text-gray-800 text-xs font-bold rounded-lg border border-gray-300">
                    +20 Mesas
                </button>
            </div>

            <!-- Footer -->
            <div class="pt-4 border-t border-gray-100 flex items-center justify-end space-x-3">
                <button type="button" onclick="fecharModalLoteMesas()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold text-xs rounded-xl">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-extrabold text-xs rounded-xl shadow transition">
                    🚀 Gerar Mesas
                </button>
            </div>
        <?= Html::endForm() ?>

    </div>
</div>

<script>
function abrirModalLoteMesas() {
    document.getElementById('modalGerarLoteMesas').classList.remove('hidden');
}

function fecharModalLoteMesas() {
    document.getElementById('modalGerarLoteMesas').classList.add('hidden');
}

function setQtdLote(qtd) {
    document.querySelector('input[name="quantidade"]').value = qtd;
}
</script>
