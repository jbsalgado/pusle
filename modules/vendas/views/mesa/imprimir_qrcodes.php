<?php

/** @var yii\web\View $this */
/** @var app\models\Usuario $usuario */
/** @var app\modules\vendas\models\Mesa[] $mesas */
/** @var string $baseUrl */
/** @var string $slug */

use yii\helpers\Html;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plaquinhas QR Code das Mesas — <?= Html::encode($usuario->nome_loja ?? 'Restaurante') ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; padding: 0 !important; }
            .page-break { page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-gray-100 p-8 font-sans antialiased text-gray-900">

    <!-- Barra Superior com Ações de Impressão -->
    <div class="max-w-5xl mx-auto mb-8 bg-white p-4 rounded-xl shadow-sm flex items-center justify-between no-print border border-gray-200">
        <div>
            <h1 class="text-lg font-bold text-gray-900 m-0">Displays de Mesa com QR Code</h1>
            <p class="text-xs text-gray-500 m-0">Imprima e coloque nas mesas para seus clientes acessarem a Comanda Digital e o Cardápio.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-lg shadow-sm transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Imprimir Displays (PDF)
            </button>
            <button onclick="window.close()" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-sm rounded-lg transition-colors">
                Fechar
            </button>
        </div>
    </div>

    <!-- Grid de Plaquinhas / Displays -->
    <div class="max-w-5xl mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
        <?php foreach ($mesas as $m): ?>
            <?php 
                $mesaUrl = "{$baseUrl}/m/{$slug}?mesa=" . urlencode($m->numero_mesa);
                $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&margin=10&data=" . urlencode($mesaUrl);
            ?>
            <div class="page-break bg-white border-2 border-emerald-600 rounded-3xl p-6 text-center flex flex-col items-center justify-between shadow-md relative overflow-hidden">
                <!-- Top Ribbon -->
                <div class="w-full bg-emerald-600 text-white py-1.5 px-4 -mt-6 -mx-6 mb-4 font-bold text-xs uppercase tracking-widest">
                    <?= Html::encode($usuario->nome_loja ?? 'Cardápio Digital') ?>
                </div>

                <div class="mb-2">
                    <span class="text-3xl font-black text-gray-900 tracking-tight block">
                        MESA <?= Html::encode($m->numero_mesa) ?>
                    </span>
                    <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200 inline-block mt-1">
                        Comanda Digital &bull; Cardápio
                    </span>
                </div>

                <!-- Imagem do QR Code -->
                <div class="my-3 p-3 bg-white rounded-2xl border border-gray-200 shadow-inner">
                    <img src="<?= $qrUrl ?>" alt="QR Code Mesa <?= Html::encode($m->numero_mesa) ?>" class="w-44 h-44 object-contain">
                </div>

                <div class="space-y-1">
                    <p class="text-xs font-bold text-gray-800 m-0">
                        📱 Aponte a câmera do seu celular
                    </p>
                    <p class="text-[10px] text-gray-500 m-0 leading-tight">
                        Faça pedidos, veja sua comanda em tempo real e chame o garçom com 1 clique.
                    </p>
                </div>

                <!-- Footer Discreto com a URL -->
                <div class="mt-4 pt-2 border-t border-gray-100 w-full text-[9px] text-gray-400 font-mono truncate">
                    <?= Html::encode($mesaUrl) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>
