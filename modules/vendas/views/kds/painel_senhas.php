<?php

use yii\helpers\Html;
use yii\helpers\Url;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel da TV — Chamada de Senhas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="bg-gray-950 text-white h-screen overflow-hidden flex flex-col justify-between p-6">

    <!-- Top Bar TV -->
    <div class="flex items-center justify-between border-b border-gray-800 pb-4">
        <h1 class="text-3xl font-black text-emerald-400 flex items-center gap-3">
            <span>📢</span>
            <span>CHAMADA DE SENHAS</span>
        </h1>
        <span class="text-sm font-bold text-gray-400 bg-gray-900 px-4 py-2 rounded-xl border border-gray-800" id="txtHoraTv">00:00:00</span>
    </div>

    <!-- Main Grid (2 Colunas Grandes) -->
    <div class="grid grid-cols-2 gap-8 flex-1 py-6 overflow-hidden">
        
        <!-- Coluna 1: EM PREPARO -->
        <div class="bg-gray-900/80 border-2 border-amber-500/40 rounded-3xl p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between pb-4 border-b border-gray-800">
                <h2 class="text-2xl font-black text-amber-400 flex items-center gap-2">
                    <span>🍳</span>
                    <span>EM PREPARO</span>
                </h2>
                <span id="badgePreparoTv" class="bg-amber-500/20 text-amber-300 text-lg font-black px-4 py-1 rounded-full">0</span>
            </div>

            <div id="gridPreparoTv" class="grid grid-cols-2 gap-4 my-auto py-4"></div>

            <div class="text-xs text-gray-500 text-center">Seu pedido está sendo preparado pela nossa equipe</div>
        </div>

        <!-- Coluna 2: PRONTO PARA RETIRADA -->
        <div class="bg-gray-900/80 border-2 border-emerald-500/60 rounded-3xl p-6 flex flex-col justify-between shadow-2xl shadow-emerald-950">
            <div class="flex items-center justify-between pb-4 border-b border-gray-800">
                <h2 class="text-2xl font-black text-emerald-400 flex items-center gap-2">
                    <span>✅</span>
                    <span>PRONTO PARA RETIRADA</span>
                </h2>
                <span id="badgeProntoTv" class="bg-emerald-500/20 text-emerald-300 text-lg font-black px-4 py-1 rounded-full">0</span>
            </div>

            <div id="gridProntoTv" class="grid grid-cols-2 gap-4 my-auto py-4"></div>

            <div class="text-xs text-emerald-400 font-bold text-center">Dirija-se ao balcão para retirar seu pedido!</div>
        </div>

    </div>

    <!-- Footer Status Bar -->
    <div class="text-center text-xs text-gray-600 border-t border-gray-900 pt-3">
        PULSE TV KIOSK • Atualização automática a cada 5s • Pressione F11 para Tela Cheia
    </div>

    <script>
    let senhasProntasAnteriores = [];

    function atualizarRelogio() {
        const ag = new Date();
        document.getElementById('txtHoraTv').innerText = ag.toLocaleTimeString('pt-BR');
    }
    setInterval(atualizarRelogio, 1000);
    atualizarRelogio();

    async function carregarSenhasTv() {
        try {
            const resp = await fetch('<?= Url::to(['/vendas/kds/senhas-json']) ?>');
            const data = await resp.json();

            if (!data.success) return;

            const gridPreparo = document.getElementById('gridPreparoTv');
            const gridPronto = document.getElementById('gridProntoTv');

            gridPreparo.innerHTML = '';
            gridPronto.innerHTML = '';

            // Renderiza Em Preparo
            data.preparo.forEach(p => {
                gridPreparo.innerHTML += `
                    <div class="bg-gray-800/80 border border-gray-700/80 rounded-2xl p-4 text-center">
                        <div class="text-3xl font-black text-amber-400">${p.senha}</div>
                        <div class="text-xs text-gray-300 font-bold mt-1 truncate">${p.cliente}</div>
                    </div>
                `;
            });

            // Renderiza Prontas
            const novasSenhasProntas = [];
            data.pronto.forEach(p => {
                novasSenhasProntas.push(p.senha);
                gridPronto.innerHTML += `
                    <div class="bg-emerald-950/60 border-2 border-emerald-500 rounded-2xl p-4 text-center animate-pulse shadow-lg">
                        <div class="text-4xl font-black text-white">${p.senha}</div>
                        <div class="text-xs text-emerald-300 font-extrabold mt-1 truncate">${p.cliente}</div>
                    </div>
                `;
            });

            document.getElementById('badgePreparoTv').innerText = data.preparo.length;
            document.getElementById('badgeProntoTv').innerText = data.pronto.length;

            // Verifica se tem nova senha pronta para anunciar voz
            novasSenhasProntas.forEach(senha => {
                if (!senhasProntasAnteriores.includes(senha)) {
                    anunciarVozSenha(senha);
                }
            });
            senhasProntasAnteriores = novasSenhasProntas;

        } catch(e) {
            console.error('Erro ao carregar senhas da TV:', e);
        }
    }

    function anunciarVozSenha(senha) {
        try {
            const txt = `Atenção! Senha ${senha.replace('#', '')} pronta para retirada no balcão.`;
            const utt = new SpeechSynthesisUtterance(txt);
            utt.lang = 'pt-BR';
            utt.rate = 0.95;
            window.speechSynthesis.speak(utt);
        } catch(e) {}
    }

    carregarSenhasTv();
    setInterval(carregarSenhasTv, 5000);
    </script>
</body>
</html>
