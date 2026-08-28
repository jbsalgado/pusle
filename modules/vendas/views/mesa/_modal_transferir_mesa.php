<?php

use yii\helpers\Html;
use yii\helpers\Url;
?>

<!-- Modal de Transferência de Mesa -->
<div id="modalTransferirMesa" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-70 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-sky-600 to-indigo-700 px-6 py-4 flex items-center justify-between text-white">
            <h3 class="text-lg font-bold flex items-center gap-2">
                <span>🔀</span>
                <span id="modalTransferirMesaTitulo">Transferir Mesa</span>
            </h3>
            <button type="button" onclick="fecharModalTransferirMesa()" class="text-white hover:text-gray-200 text-2xl font-bold px-2 focus:outline-none">
                &times;
            </button>
        </div>

        <!-- Body -->
        <?= Html::beginForm(Url::to(['/vendas/mesa/transferir-mesa']), 'post', ['id' => 'formTransferirMesa', 'class' => 'p-6 space-y-4']) ?>
            <input type="hidden" name="mesa_origem_id" id="inputMesaOrigemId">

            <div class="bg-sky-50 border border-sky-200 rounded-xl p-3 text-xs text-sky-900">
                Transfere todo o extrato de consumo e o atendimento da mesa atual para outra mesa de destino.
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Mesa de Destino</label>
                <select name="mesa_destino_id" id="selectMesaDestino" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-bold text-gray-800" required>
                    <option value="">Selecione a mesa de destino...</option>
                    <?php foreach ($mesas as $m): ?>
                        <option value="<?= $m->id ?>">Mesa <?= Html::encode($m->numero_mesa) ?> (<?= $m->getStatusBadge()['label'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Footer -->
            <div class="pt-4 border-t border-gray-100 flex items-center justify-end space-x-3">
                <button type="button" onclick="fecharModalTransferirMesa()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold text-xs rounded-xl">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2 bg-sky-600 hover:bg-sky-700 text-white font-extrabold text-xs rounded-xl shadow transition">
                     Confirmar Transferência
                </button>
            </div>
        <?= Html::endForm() ?>

    </div>
</div>

<script>
function abrirModalTransferir(mesaOrigemId, numeroMesa) {
    document.getElementById('inputMesaOrigemId').value = mesaOrigemId;
    document.getElementById('modalTransferirMesaTitulo').innerText = 'Transferir Consumo — Mesa ' + numeroMesa;
    document.getElementById('modalTransferirMesa').classList.remove('hidden');
}

function fecharModalTransferirMesa() {
    document.getElementById('modalTransferirMesa').classList.add('hidden');
}
</script>
