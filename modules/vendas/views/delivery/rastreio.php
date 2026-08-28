<?php

use yii\helpers\Html;
use yii\helpers\Url;

$nomeLoja = $loja ? ($loja->nome ?: 'PULSE Delivery') : 'PULSE Delivery';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rastreio ao Vivo — Pedido <?= Html::encode($comanda->numero_comanda) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Leaflet CSS & JS (OpenStreetMap Gratuito) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; }
        #mapaRastreio { height: 50vh; width: 100%; }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col justify-between">

    <!-- Top Header -->
    <div class="bg-gray-950 p-4 border-b border-gray-800 flex items-center justify-between">
        <div>
            <h1 class="text-base font-black text-emerald-400"><?= Html::encode($nomeLoja) ?></h1>
            <span class="text-xs text-gray-400">Rastreamento de Entrega ao Vivo</span>
        </div>
        <span class="text-xs font-black bg-emerald-500/20 text-emerald-300 px-3 py-1 rounded-full border border-emerald-500/30">
            <?= Html::encode($comanda->numero_comanda) ?>
        </span>
    </div>

    <!-- Mapa Interativo OpenStreetMap (Leaflet.js) -->
    <div id="mapaRastreio" class="bg-gray-800 relative">
        <div id="loadingMapa" class="absolute inset-0 z-10 bg-gray-900/80 flex items-center justify-center text-xs font-bold text-gray-300">
            Aguardando sinal de GPS do entregador...
        </div>
    </div>

    <!-- Card de Detalhes da Entrega -->
    <div class="p-6 bg-gray-950 rounded-t-3xl border-t border-gray-800 space-y-4 flex-1">
        <div class="flex items-center justify-between border-b border-gray-800 pb-3">
            <div>
                <span class="text-xs text-gray-400 font-semibold block">Entregador Responsável</span>
                <span class="text-base font-black text-white" id="lblMotoboyNome"><?= Html::encode($comanda->motoboy_nome ?: 'Entregador da Casa') ?></span>
            </div>
            <div class="text-3xl animate-bounce">🛵</div>
        </div>

        <div>
            <span class="text-xs text-gray-400 font-semibold block">Endereço de Entrega</span>
            <p class="text-xs font-bold text-gray-200 mt-0.5"><?= Html::encode($comanda->endereco_entrega ?: 'Endereço Registrado') ?></p>
        </div>

        <!-- Lista Resumida do Pedido -->
        <div class="bg-gray-900 p-3 rounded-2xl border border-gray-800 space-y-1">
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Itens do Pedido:</span>
            <?php foreach ($comanda->itens as $item): ?>
                <div class="text-xs text-gray-300 flex justify-between">
                    <span><strong><?= (float)$item->quantidade ?>x</strong> <?= Html::encode($item->produto ? $item->produto->nome : 'Produto') ?></span>
                    <span class="font-bold text-emerald-400">R$ <?= number_format($item->getSubtotal(), 2, ',', '.') ?></span>
                </div>
            <?php endforeach; ?>
            <div class="pt-2 border-t border-gray-800 flex justify-between font-black text-sm text-white">
                <span>Total Pedido:</span>
                <span class="text-emerald-400">R$ <?= number_format($comanda->getValorTotal() + (float)$comanda->taxa_entrega, 2, ',', '.') ?></span>
            </div>
        </div>
    </div>

    <script>
    const pedidoId = '<?= $comanda->id ?>';
    let map = null;
    let motoMarker = null;

    function initMap(lat, lng) {
        if (!map) {
            document.getElementById('loadingMapa').classList.add('hidden');
            map = L.map('mapaRastreio').setView([lat, lng], 16);

            // Camada OpenStreetMap gratuita
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            // Ícone da Motinha
            const motoIcon = L.divIcon({
                html: '<div style="font-size: 32px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.4));">🛵</div>',
                className: 'moto-leaflet-icon',
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            });

            motoMarker = L.marker([lat, lng], { icon: motoIcon }).addTo(map);
        } else {
            motoMarker.setLatLng([lat, lng]);
            map.panTo([lat, lng]);
        }
    }

    async function consultarGpsAoVivo() {
        try {
            const resp = await fetch('<?= Url::to(['/vendas/delivery/gps-json']) ?>?id=' + pedidoId);
            const data = await resp.json();

            if (data.success && data.lat && data.lng && data.lat !== 0) {
                initMap(data.lat, data.lng);
            }
        } catch(e) {
            console.error('Erro ao buscar sinal GPS:', e);
        }
    }

    consultarGpsAoVivo();
    setInterval(consultarGpsAoVivo, 5000);
    </script>
</body>
</html>
