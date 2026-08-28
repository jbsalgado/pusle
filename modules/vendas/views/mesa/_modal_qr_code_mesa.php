<?php

use yii\helpers\Html;
use yii\helpers\Url;
?>

<!-- Modal de QR Code da Mesa -->
<div id="modalQrCodeMesa" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-70 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col text-center p-6 space-y-4">
        
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 id="lblQrCodeTitulo" class="text-base font-black text-gray-900">QR Code — Mesa 01</h3>
            <button type="button" onclick="fecharModalQrCodeMesa()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold px-2">&times;</button>
        </div>

        <p class="text-xs text-gray-500">O cliente pode apontar a câmera do celular para pedir diretamente do próprio smartphone!</p>

        <div class="flex justify-center py-2">
            <img id="imgQrCodeMesa" src="" class="w-48 h-48 rounded-xl border border-gray-200 shadow-sm p-2 bg-white" alt="QR Code Mesa">
        </div>

        <div class="bg-gray-50 p-2 rounded-xl text-[11px] font-mono text-gray-600 break-all" id="txtLinkQrCodeMesa">
            https://...
        </div>

        <div class="flex items-center justify-center space-x-2 pt-2 border-t border-gray-100">
            <button type="button" onclick="copiarLinkQrCode()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold text-xs rounded-xl transition">
                📋 Copiar Link
            </button>
            <a id="btnImprimirPlaqueta" href="#" target="_blank" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow transition">
                🖨️ Imprimir Plaqueta
            </a>
        </div>

    </div>
</div>

<script>
let linkQrCodeAtual = '';

function abrirModalQrCodeMesa(mesaId, numeroMesa) {
    const url = '<?= Url::to(['/vendas/cardapio/mesa'], true) ?>?id=' + mesaId;
    linkQrCodeAtual = url;

    document.getElementById('lblQrCodeTitulo').innerText = 'QR Code — Mesa ' + numeroMesa;
    document.getElementById('txtLinkQrCodeMesa').innerText = url;
    document.getElementById('imgQrCodeMesa').src = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(url);
    document.getElementById('btnImprimirPlaqueta').href = url;

    document.getElementById('modalQrCodeMesa').classList.remove('hidden');
}

function fecharModalQrCodeMesa() {
    document.getElementById('modalQrCodeMesa').classList.add('hidden');
}

function copiarLinkQrCode() {
    navigator.clipboard.writeText(linkQrCodeAtual);
    alert('Link do Cardápio da Mesa copiado com sucesso!');
}
</script>
