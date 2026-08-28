<?php

use yii\helpers\Html;
use yii\helpers\Url;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Entregador — GPS ao Vivo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="bg-gray-950 text-white min-h-screen p-4 flex flex-col justify-between space-y-4">

    <!-- Header -->
    <div class="bg-gray-900 p-4 rounded-2xl border border-gray-800 flex items-center justify-between shadow-lg">
        <div>
            <h1 class="text-lg font-black text-emerald-400 flex items-center gap-2">
                <span>🛵</span>
                <span>Painel do Entregador</span>
            </h1>
            <span class="text-xs text-gray-400">Transmissão de Sinal GPS ao Vivo</span>
        </div>
        <div id="badgeGpsStatus" class="px-3 py-1 bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-bold rounded-full">
            GPS Desconectado
        </div>
    </div>

    <!-- Lista de Entregas em Rota -->
    <div class="flex-1 space-y-4 overflow-y-auto">
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Entregas em Andamento</h2>

        <?php if (empty($pedidosEmRota)): ?>
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 text-center text-xs text-gray-500">
                Nenhuma entrega em rota no momento.
            </div>
        <?php else: ?>
            <?php foreach ($pedidosEmRota as $p): ?>
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4 space-y-3 shadow-md">
                    <div class="flex items-center justify-between border-b border-gray-800 pb-2">
                        <span class="font-black text-white text-sm"><?= Html::encode($p->numero_comanda) ?></span>
                        <span class="font-black text-emerald-400 text-sm">R$ <?= number_format($p->getValorTotal() + (float)$p->taxa_entrega, 2, ',', '.') ?></span>
                    </div>

                    <div>
                        <span class="text-xs text-gray-400 block font-semibold">Cliente:</span>
                        <div class="font-bold text-white text-sm"><?= Html::encode($p->cliente_nome ?: 'Cliente') ?></div>
                        <?php if ($p->cliente_telefone): ?>
                            <a href="https://wa.me/55<?= preg_replace('/\D/', '', $p->cliente_telefone) ?>" target="_blank" class="text-xs text-emerald-400 hover:underline">
                                💬 <?= Html::encode($p->cliente_telefone) ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <div>
                        <span class="text-xs text-gray-400 block font-semibold">Endereço de Entrega:</span>
                        <div class="text-xs font-bold text-gray-200"><?= Html::encode($p->endereco_entrega ?: 'Balcão') ?></div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-800">
                        <button type="button" onclick="iniciarGpsMotoboy('<?= $p->id ?>')" class="py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs rounded-xl shadow">
                            📡 Iniciar GPS
                        </button>
                        <button type="button" onclick="finalizarEntregaMotoboy('<?= $p->id ?>')" class="py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-extrabold text-xs rounded-xl shadow">
                            ✅ Concluir
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Footer Status GPS -->
    <div class="bg-gray-900 p-4 rounded-2xl border border-gray-800 text-center text-xs text-gray-400">
        Mantendo o GPS ativo, o cliente acompanha sua moto ao vivo pelo mapa.
    </div>

    <script>
    let watchId = null;
    let pedidoIdGpsAtivo = null;

    function iniciarGpsMotoboy(pedidoId) {
        if (!navigator.geolocation) {
            alert('Geolocalização não é suportada pelo seu celular.');
            return;
        }

        pedidoIdGpsAtivo = pedidoId;
        const badge = document.getElementById('badgeGpsStatus');
        badge.innerText = '📡 GPS Transmitindo...';
        badge.className = 'px-3 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-bold rounded-full animate-pulse';

        if (watchId) navigator.geolocation.clearWatch(watchId);

        watchId = navigator.geolocation.watchPosition(
            (pos) => {
                enviarCoordenadas(pos.coords.latitude, pos.coords.longitude);
            },
            (err) => {
                console.error('Erro de GPS:', err);
            },
            { enableHighAccuracy: true, maximumAge: 0, timeout: 10000 }
        );

        alert('Transmissão GPS ativada! O cliente já pode ver a sua localização no mapa.');
    }

    async function enviarCoordenadas(lat, lng) {
        if (!pedidoIdGpsAtivo) return;

        try {
            const formData = new FormData();
            formData.append('pedido_id', pedidoIdGpsAtivo);
            formData.append('lat', lat);
            formData.append('lng', lng);

            const resp = await fetch('<?= Url::to(['/vendas/delivery/atualizar-gps']) ?>', {
                method: 'POST',
                body: formData
            });

            const data = await resp.json();
            console.log('GPS Atualizado:', data);
        } catch(e) {
            console.error('Erro ao enviar GPS:', e);
        }
    }

    async function finalizarEntregaMotoboy(pedidoId) {
        if (!confirm('Confirmar entrega do pedido?')) return;

        try {
            const formData = new FormData();
            formData.append('pedido_id', pedidoId);
            formData.append('novo_status', 'entregue');

            const resp = await fetch('<?= Url::to(['/vendas/delivery/atualizar-status']) ?>', {
                method: 'POST',
                body: formData
            });

            const data = await resp.json();
            if (data.success) {
                if (watchId) navigator.geolocation.clearWatch(watchId);
                alert('Entrega finalizada com sucesso!');
                window.location.reload();
            } else {
                alert(data.message || 'Erro ao finalizar entrega.');
            }
        } catch(e) {
            alert('Erro ao finalizar entrega.');
        }
    }
    </script>
</body>
</html>
