<?php

use yii\helpers\Html;
use yii\helpers\Url;
?>

<!-- Modal de Cadastro de Nova Mesa -->
<div id="modalCriarMesa" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-70 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-emerald-600 to-teal-700 px-6 py-4 flex items-center justify-between text-white">
            <h3 class="text-lg font-bold flex items-center gap-2">
                <span>➕</span>
                <span>Adicionar Nova Mesa</span>
            </h3>
            <button type="button" onclick="fecharModalCriarMesa()" class="text-white hover:text-gray-200 text-2xl font-bold px-2 focus:outline-none">
                &times;
            </button>
        </div>

        <!-- Body -->
        <?= Html::beginForm(Url::to(['/vendas/mesa/criar-mesa']), 'post', ['class' => 'p-6 space-y-4']) ?>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Número da Mesa *</label>
                <input type="text" name="numero_mesa" placeholder="Ex: 11 ou 05" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-bold text-gray-900" required>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Capacidade (Lugares)</label>
                    <input type="number" name="lugares" value="4" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-semibold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Ambiente / Local</label>
                    <input type="text" name="nome_identificador" placeholder="Ex: Varanda, Salão" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-semibold">
                </div>
            </div>

            <!-- Footer -->
            <div class="pt-4 border-t border-gray-100 flex items-center justify-end space-x-3">
                <button type="button" onclick="fecharModalCriarMesa()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold text-xs rounded-xl">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow transition">
                    ➕ Salvar Nova Mesa
                </button>
            </div>
        <?= Html::endForm() ?>

    </div>
</div>

<script>
function abrirModalCriarMesa() {
    document.getElementById('modalCriarMesa').classList.remove('hidden');
}

function fecharModalCriarMesa() {
    document.getElementById('modalCriarMesa').classList.add('hidden');
}
</script>
