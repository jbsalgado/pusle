<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\vendas\models\BridgeWhatsappLoja;
use app\modules\vendas\models\BridgeWhatsappMensagem;

$this->title = 'WhatsApp Local — Pulse Agent';
$this->params['breadcrumbs'][] = ['label' => 'Vendas', 'url' => ['/vendas/inicio/index']];
$this->params['breadcrumbs'][] = $this->title;

$serverUrl = Yii::$app->request->hostInfo;
$token = $loja->token_agente;
?>

<div class="min-h-screen bg-slate-950 text-slate-100 py-6 px-4 sm:px-8">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Topo & Indicador de Status Geral -->
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 bg-slate-900/90 border border-slate-800 p-6 rounded-3xl shadow-2xl backdrop-blur-md">
            <div class="flex items-center gap-4">
                <a href="<?= Url::to(['/vendas/inicio/index']) ?>" class="p-3 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-2xl border border-slate-700 transition flex items-center justify-center shadow group">
                    <svg class="w-5 h-5 transform group-hover:-translate-x-0.5 transition text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-2">
                            <span>WhatsApp Local</span>
                            <span class="text-emerald-400 font-medium text-lg">Pulse Agent</span>
                        </h1>
                        <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">Zero Custo Meta API</span>
                        <span class="bg-cyan-500/10 text-cyan-400 border border-cyan-500/30 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">IP Residencial Antiban</span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Dispare campanhas, encartes e atenda clientes usando a conexão de internet e o chip da sua própria loja.
                    </p>
                </div>
            </div>

            <!-- Badge de Status do Agente Local -->
            <div class="flex items-center gap-3">
                <div id="badge-agente-container" class="flex items-center gap-2 px-4 py-2 rounded-2xl border <?= $loja->isAgenteOnline() ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-rose-500/10 border-rose-500/30 text-rose-400' ?> shadow">
                    <span class="relative flex h-3 w-3">
                        <span id="badge-agente-ping" class="<?= $loja->isAgenteOnline() ? 'animate-ping' : '' ?> absolute inline-flex h-full w-full rounded-full <?= $loja->isAgenteOnline() ? 'bg-emerald-400 opacity-75' : 'bg-rose-400 opacity-0' ?>"></span>
                        <span id="badge-agente-dot" class="relative inline-flex rounded-full h-3 w-3 <?= $loja->isAgenteOnline() ? 'bg-emerald-500' : 'bg-rose-500' ?>"></span>
                    </span>
                    <span id="badge-agente-texto" class="text-xs font-bold uppercase tracking-wider">
                        <?= $loja->isAgenteOnline() ? 'Agente Online' : 'Agente Offline' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Grid Principal de 3 Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Card 1: Computador da Loja / Agente -->
            <div class="bg-slate-900/80 border border-slate-800 p-6 rounded-3xl shadow-xl flex flex-col justify-between relative overflow-hidden group">
                <div class="space-y-4">
                    <div class="flex items-center gap-3.5">
                        <div class="p-3 bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 rounded-2xl shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white leading-tight">Computador da Loja</h2>
                            <p class="text-xs text-slate-400">Serviço de borda local (Pulse Agent)</p>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-800/80 text-xs">
                        <div class="flex justify-between items-center py-2.5">
                            <span class="text-slate-400">Status do Agente:</span>
                            <span id="info-agente-status" class="font-bold <?= $loja->isAgenteOnline() ? 'text-emerald-400' : 'text-rose-400' ?>">
                                <?= $loja->isAgenteOnline() ? '🟢 Conectado à VPS' : '🔴 Desconectado' ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2.5">
                            <span class="text-slate-400">IP Residencial:</span>
                            <span id="info-agente-ip" class="font-mono text-slate-200 bg-slate-950 px-2 py-0.5 rounded border border-slate-800">
                                <?= Html::encode($loja->ip_origem_agente ?: 'Não identificado') ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2.5">
                            <span class="text-slate-400">Último Sinal:</span>
                            <span id="info-agente-heartbeat" class="text-slate-300">
                                <?= $loja->ultimo_heartbeat ? date('d/m/Y H:i:s', strtotime($loja->ultimo_heartbeat)) : 'Aguardando' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="pt-6">
                    <button onclick="abrirModalInstalacao()" class="w-full bg-slate-800 hover:bg-slate-700 text-cyan-400 hover:text-cyan-300 border border-slate-700 hover:border-cyan-500/40 font-bold py-3 px-4 rounded-2xl transition flex items-center justify-center gap-2 text-xs shadow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Baixar & Instalar Agente no PC</span>
                    </button>
                </div>
            </div>

            <!-- Card 2: Conexão WhatsApp -->
            <div class="bg-slate-900/80 border border-slate-800 p-6 rounded-3xl shadow-xl flex flex-col justify-between relative overflow-hidden group">
                <div class="space-y-4">
                    <div class="flex items-center gap-3.5">
                        <div class="p-3 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-2xl shadow-inner">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.699c.971.54 1.831.828 2.796.829 3.183 0 5.77-2.587 5.77-5.766.001-3.182-2.585-5.771-5.77-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.071.376-.043.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-10.416c-4.417 0-8 3.583-8 8 0 1.542.441 2.981 1.203 4.204l-1.278 4.671 4.79-1.256c1.173.705 2.548 1.114 4.015 1.114 4.417 0 8-3.583 8-8 0-4.417-3.583-8-8-8z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white leading-tight">Sessão WhatsApp</h2>
                            <p class="text-xs text-slate-400">Motor Whatsmeow no Computador da Loja</p>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-800/80 text-xs">
                        <div class="flex justify-between items-center py-2.5">
                            <span class="text-slate-400">Status Conexão:</span>
                            <span id="info-wa-status" class="font-bold <?= $loja->isWhatsappConectado() ? 'text-emerald-400' : 'text-amber-400' ?>">
                                <?= $loja->isWhatsappConectado() ? '🟢 Conectado' : '⚪ ' . ucfirst($loja->status_conexao) ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2.5">
                            <span class="text-slate-400">Telefone Conectado:</span>
                            <span id="info-wa-phone" class="font-mono text-slate-200">
                                <?= Html::encode($loja->telefone_conectado ?: 'Nenhum chip pareado') ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2.5">
                            <span class="text-slate-400">Nome do Perfil:</span>
                            <span id="info-wa-name" class="text-slate-300 font-semibold">
                                <?= Html::encode($loja->push_name ?: '-') ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="pt-6 flex gap-2.5">
                    <button onclick="conectarWhatsapp()" class="flex-1 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-bold py-3 px-4 rounded-2xl transition flex items-center justify-center gap-2 text-xs shadow-lg shadow-emerald-900/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                        <span>Conectar / QR Code</span>
                    </button>
                    <button onclick="desconectarWhatsapp()" class="p-3 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 rounded-2xl transition shadow" title="Desconectar Sessão">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Card 3: Disparo de Teste Rápido -->
            <div class="bg-slate-900/80 border border-slate-800 p-6 rounded-3xl shadow-xl flex flex-col justify-between relative overflow-hidden group">
                <div class="space-y-4">
                    <div class="flex items-center gap-3.5">
                        <div class="p-3 bg-amber-500/10 text-amber-400 border border-amber-500/20 rounded-2xl shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white leading-tight">Disparo de Teste</h2>
                            <p class="text-xs text-slate-400">Valide o envio pelo chip da sua loja</p>
                        </div>
                    </div>

                    <form id="form-teste" onsubmit="enviarTeste(event)" class="space-y-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Telefone com DDD:</label>
                            <input type="text" id="teste-numero" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-slate-100 placeholder-slate-600 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-amber-500" placeholder="Ex: 5511999998888" required />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Mensagem:</label>
                            <input type="text" id="teste-texto" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-slate-100 text-xs focus:outline-none focus:ring-2 focus:ring-amber-500" value="Teste de conexão via Pulse Bridge WhatsApp Local!" required />
                        </div>
                        <div class="pt-1">
                            <button type="submit" id="btn-enviar-teste" class="w-full bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-slate-950 font-black py-3 px-4 rounded-2xl transition flex items-center justify-center gap-2 text-xs shadow-lg shadow-amber-900/20">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                <span>Enviar Mensagem de Teste</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabela de Histórico Recente de Mensagens -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-slate-800 text-slate-400 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-white">Histórico Recente de Mensagens</h2>
                        <p class="text-xs text-slate-400">Últimos disparos e mensagens recebidas via agente local</p>
                    </div>
                </div>
                <span class="text-xs font-bold px-3 py-1 bg-slate-800 text-slate-300 rounded-full border border-slate-700">
                    <?= count($mensagens) ?> mensagens
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-950/80 text-[10px] font-extrabold uppercase text-slate-400 tracking-wider border-b border-slate-800">
                            <th class="p-4 pl-6">Data / Hora</th>
                            <th class="p-4">Direção</th>
                            <th class="p-4">Número</th>
                            <th class="p-4">Conteúdo</th>
                            <th class="p-4 pr-6">Status</th>
                        </tr>
                    </thead>
                    <tbody id="lista-mensagens" class="divide-y divide-slate-800/60">
                        <?php if (empty($mensagens)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-8 text-slate-500 italic">
                                    Nenhuma mensagem registrada ainda. Faça um teste de envio acima!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mensagens as $m): ?>
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="p-4 pl-6 text-slate-400 font-mono">
                                        <?= date('d/m/Y H:i:s', strtotime($m->created_at)) ?>
                                    </td>
                                    <td class="p-4">
                                        <?php if ($m->direcao === BridgeWhatsappMensagem::DIRECAO_OUTBOUND): ?>
                                            <span class="inline-flex items-center gap-1 bg-cyan-500/10 text-cyan-400 border border-cyan-500/30 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                                Enviada
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                                Recebida
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 font-mono text-slate-200">
                                        <?= Html::encode($m->direcao === BridgeWhatsappMensagem::DIRECAO_OUTBOUND ? $m->numero_destino : $m->numero_remetente) ?>
                                    </td>
                                    <td class="p-4 text-slate-300 max-w-xs sm:max-w-md truncate" title="<?= Html::encode($m->conteudo_texto) ?>">
                                        <?= Html::encode($m->conteudo_texto) ?>
                                    </td>
                                    <td class="p-4 pr-6">
                                        <?php
                                            $badgeClasses = 'bg-slate-800 text-slate-400 border border-slate-700';
                                            if ($m->status === 'delivered') $badgeClasses = 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/30';
                                            if ($m->status === 'read') $badgeClasses = 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30';
                                            if ($m->status === 'failed') $badgeClasses = 'bg-rose-500/10 text-rose-400 border border-rose-500/30';
                                            if ($m->status === 'pending') $badgeClasses = 'bg-amber-500/10 text-amber-400 border border-amber-500/30';
                                        ?>
                                        <span class="inline-block px-2.5 py-0.5 text-[10px] font-black uppercase rounded-full tracking-wider <?= $badgeClasses ?>">
                                            <?= Html::encode(strtoupper($m->status)) ?>
                                        </span>
                                        <?php if ($m->status === 'failed' && !empty($m->erro_motivo)): ?>
                                            <span class="block mt-1 text-[10px] text-rose-400 font-normal italic max-w-xs" title="<?= Html::encode($m->erro_motivo) ?>">
                                                <?= Html::encode($m->erro_motivo) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Modal: QR Code (Tailwind Nativo) -->
<div id="modalQrCode" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-md p-4 transition-opacity">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl text-center relative animate-in fade-in zoom-in duration-200">
        <button onclick="fecharModalQr()" class="absolute top-5 right-5 text-slate-400 hover:text-white p-2 rounded-xl bg-slate-800/80 hover:bg-slate-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <div class="flex items-center justify-center gap-2 mb-2">
            <svg class="w-6 h-6 text-emerald-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.699c.971.54 1.831.828 2.796.829 3.183 0 5.77-2.587 5.77-5.766.001-3.182-2.585-5.771-5.77-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.071.376-.043.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-10.416c-4.417 0-8 3.583-8 8 0 1.542.441 2.981 1.203 4.204l-1.278 4.671 4.79-1.256c1.173.705 2.548 1.114 4.015 1.114 4.417 0 8-3.583 8-8 0-4.417-3.583-8-8-8z"/>
            </svg>
            <h3 class="text-lg font-bold text-white">Escaneie o QR Code</h3>
        </div>

        <!-- Banner Dinâmico de Alerta do Agente -->
        <div id="qr-agent-banner" class="mb-4 p-3 rounded-2xl text-xs font-bold flex items-center justify-center gap-2 bg-rose-500/10 text-rose-400 border border-rose-500/30">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span id="qr-agent-banner-text">Iniciando conexão...</span>
        </div>

        <p class="text-xs text-slate-400 mb-4">
            Abra o WhatsApp no celular &rarr; <b>Aparelhos Conectados</b> &rarr; <b>Conectar um aparelho</b>
        </p>

        <div class="bg-white p-4 rounded-2xl inline-block shadow-2xl border-4 border-slate-800">
            <div id="qr-spinner" class="py-12 px-6 flex flex-col items-center justify-center">
                <svg class="animate-spin h-8 w-8 text-emerald-500 mb-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-xs font-semibold text-slate-700">Aguardando geração pelo Agente...</p>
            </div>
            <img id="qr-image" src="" alt="QR Code WhatsApp" class="hidden w-64 h-64 mx-auto rounded-lg" />
        </div>

        <div class="mt-4">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/30">
                <span class="animate-pulse w-2 h-2 rounded-full bg-amber-400"></span>
                Aguardando leitura pelo celular
            </span>
        </div>
    </div>
</div>

<!-- Modal: Instruções de Instalação (Tailwind Nativo) -->
<div id="modalInstalacao" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-md p-4 transition-opacity">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 max-w-2xl w-full shadow-2xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="fecharModalInstalacao()" class="absolute top-5 right-5 text-slate-400 hover:text-white p-2 rounded-xl bg-slate-800/80 hover:bg-slate-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <div class="flex items-center gap-3 mb-6">
            <div class="p-3 bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 rounded-2xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-xl font-bold text-white">Como Rodar o Agente no Computador da Loja</h3>
                <p class="text-xs text-slate-400">Instalação descomplicada em arquivo único (sem Docker)</p>
            </div>
        </div>

        <div class="space-y-6 text-xs">
            <!-- Passo Recomendado: 1 Clique -->
            <div class="p-4 bg-gradient-to-r from-emerald-500/10 to-teal-500/10 border border-emerald-500/30 rounded-2xl">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-500 text-slate-950 flex items-center justify-center font-black text-[10px]">★</span>
                    <h4 class="text-sm font-black text-emerald-400">Opção Mais Fácil: Inicializador de 1 Clique</h4>
                </div>
                <p class="text-slate-300 text-xs mb-3">
                    Baixe o arquivo abaixo para a pasta da sua preferência e dê <b>dois cliques</b> para iniciar. Ele baixa o agente e conecta automaticamente com seu token!
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <a href="<?= Url::to(['/vendas/bridge-whatsapp/baixar-bat']) ?>" class="flex items-center justify-center gap-2 p-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-2xl transition shadow-lg shadow-emerald-950/40">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M0 3.449L9.75 2.1v9.451H0m10.949-9.602L24 0v11.4H10.949M0 12.6h9.75v9.451L0 20.699M10.949 12.6H24V24l-12.949-1.801"/></svg>
                        <span>Baixar iniciar_whatsapp.bat (Windows)</span>
                    </a>
                    <a href="<?= Url::to(['/vendas/bridge-whatsapp/baixar-sh']) ?>" class="flex items-center justify-center gap-2 p-3 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold rounded-2xl transition shadow">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.003 0C5.373 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/></svg>
                        <span>Baixar iniciar_whatsapp.sh (Linux)</span>
                    </a>
                </div>
            </div>

            <!-- Passo 1: Download Manual -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-5 h-5 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-bold text-[10px]">1</span>
                    <h4 class="text-sm font-bold text-slate-200">Ou Baixe Apenas o Executável Diretamente:</h4>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <a href="/downloads/bridge/pulse-agent.exe" download class="flex items-center justify-center gap-2 p-3 bg-slate-800 hover:bg-slate-700 text-cyan-400 border border-slate-700 hover:border-cyan-500 rounded-2xl font-bold transition shadow">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M0 3.449L9.75 2.1v9.451H0m10.949-9.602L24 0v11.4H10.949M0 12.6h9.75v9.451L0 20.699M10.949 12.6H24V24l-12.949-1.801"/></svg>
                        <span>Executável pulse-agent.exe</span>
                    </a>
                    <a href="/downloads/bridge/pulse-agent-linux" download class="flex items-center justify-center gap-2 p-3 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 rounded-2xl font-bold transition shadow">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.003 0C5.373 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/></svg>
                        <span>Executável pulse-agent-linux</span>
                    </a>
                </div>
            </div>

            <!-- Passo 2: Token -->
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-5 h-5 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-bold text-[10px]">2</span>
                    <h4 class="text-sm font-bold text-slate-200">Token de Autenticação Exclusivo da sua Loja:</h4>
                </div>
                <div class="flex items-center gap-2 bg-slate-950 p-2 pl-3 rounded-2xl border border-slate-800">
                    <span class="font-mono text-amber-400 flex-1 truncate select-all"><?= Html::encode($token) ?></span>
                    <button onclick="navigator.clipboard.writeText('<?= Html::encode($token) ?>'); alert('Token copiado para a área de transferência!');" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-xl font-bold transition flex items-center gap-1.5 text-[11px]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <span>Copiar</span>
                    </button>
                </div>
            </div>

            <!-- Passo 3: Execução Manual -->
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-5 h-5 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-bold text-[10px]">3</span>
                    <h4 class="text-sm font-bold text-slate-200">Ou execute manualmente pelo Prompt / Terminal:</h4>
                </div>
                <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 font-mono text-emerald-400 space-y-2 overflow-x-auto text-[11px]">
                    <div class="text-slate-500"># No Windows (Prompt de Comando ou PowerShell):</div>
                    <div class="select-all">.\pulse-agent.exe --token="<?= Html::encode($token) ?>" --server="<?= Html::encode($serverUrl) ?>"</div>
                    <div class="text-slate-500 pt-2"># No Linux:</div>
                    <div class="select-all">chmod +x pulse-agent-linux && ./pulse-agent-linux --token="<?= Html::encode($token) ?>" --server="<?= Html::encode($serverUrl) ?>"</div>
                </div>
            </div>

            <div class="p-3 bg-slate-800/60 rounded-2xl border border-slate-700/60 text-slate-400 flex items-center gap-2.5">
                <svg class="w-5 h-5 text-cyan-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>O agente se conecta com a VPS de forma reversa (sem abrir portas no roteador da loja). As mensagens sairão pelo seu IP residencial real.</span>
            </div>
        </div>
    </div>
</div>

<script>
let pollTimer = null;
let ultimoAgenteOnline = false;

document.addEventListener('DOMContentLoaded', () => {
    iniciarPolling();
});

function iniciarPolling() {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(atualizarStatus, 2500);
}

function abrirModalQr() {
    const modal = document.getElementById('modalQrCode');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function fecharModalQr() {
    const modal = document.getElementById('modalQrCode');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}

function abrirModalInstalacao() {
    const modal = document.getElementById('modalInstalacao');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function fecharModalInstalacao() {
    const modal = document.getElementById('modalInstalacao');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}

function atualizarStatus() {
    fetch('<?= Url::to(['/vendas/bridge-whatsapp/status-json']) ?>')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            ultimoAgenteOnline = !!data.agente_online;

            // Agente status badge
            const badgeContainer = document.getElementById('badge-agente-container');
            const badgePing = document.getElementById('badge-agente-ping');
            const badgeDot = document.getElementById('badge-agente-dot');
            const badgeTexto = document.getElementById('badge-agente-texto');
            const infoAgenteStatus = document.getElementById('info-agente-status');
            const infoAgenteIp = document.getElementById('info-agente-ip');
            const infoAgenteHeartbeat = document.getElementById('info-agente-heartbeat');

            if (data.agente_online) {
                badgeContainer.className = 'flex items-center gap-2 px-4 py-2 rounded-2xl border bg-emerald-500/10 border-emerald-500/30 text-emerald-400 shadow';
                badgePing.className = 'animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75';
                badgeDot.className = 'relative inline-flex rounded-full h-3 w-3 bg-emerald-500';
                badgeTexto.innerText = 'Agente Online';
                infoAgenteStatus.className = 'font-bold text-emerald-400';
                infoAgenteStatus.innerText = '🟢 Conectado à VPS';
            } else {
                badgeContainer.className = 'flex items-center gap-2 px-4 py-2 rounded-2xl border bg-rose-500/10 border-rose-500/30 text-rose-400 shadow';
                badgePing.className = 'absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-0';
                badgeDot.className = 'relative inline-flex rounded-full h-3 w-3 bg-rose-500';
                badgeTexto.innerText = 'Agente Offline';
                infoAgenteStatus.className = 'font-bold text-rose-400';
                infoAgenteStatus.innerText = '🔴 Desconectado';
            }

            if (data.ip_agente) infoAgenteIp.innerText = data.ip_agente;
            if (data.ultimo_heartbeat) infoAgenteHeartbeat.innerText = data.ultimo_heartbeat;

            // Atualiza Banner dentro do Modal de QR Code
            const qrAgentBanner = document.getElementById('qr-agent-banner');
            const qrAgentBannerText = document.getElementById('qr-agent-banner-text');
            if (data.agente_online) {
                qrAgentBanner.className = 'mb-4 p-3 rounded-2xl text-xs font-bold flex items-center justify-center gap-2 bg-emerald-500/10 text-emerald-400 border border-emerald-500/30';
                qrAgentBannerText.innerText = '🟢 Agente Local Conectado à VPS. Gerando QR Code...';
            } else {
                qrAgentBanner.className = 'mb-4 p-3 rounded-2xl text-xs font-bold flex items-center justify-center gap-2 bg-rose-500/10 text-rose-400 border border-rose-500/30';
                qrAgentBannerText.innerText = '⚠️ Pulse Agent Desconectado no PC. Abra o arquivo iniciar_whatsapp no computador da loja.';
            }

            // WhatsApp status
            const infoWaStatus = document.getElementById('info-wa-status');
            const infoWaPhone = document.getElementById('info-wa-phone');
            const infoWaName = document.getElementById('info-wa-name');

            if (data.whatsapp_conectado) {
                infoWaStatus.className = 'font-bold text-emerald-400';
                infoWaStatus.innerText = '🟢 Conectado';
                infoWaPhone.innerText = data.telefone || 'Nenhum';
                infoWaName.innerText = data.push_name || '-';
                fecharModalQr();
            } else {
                infoWaStatus.className = 'font-bold text-amber-400';
                infoWaStatus.innerText = '⚪ ' + (data.status ? data.status.charAt(0).toUpperCase() + data.status.slice(1) : 'Desconectado');
            }

            // QR Code se modal estiver aberto
            const modalQr = document.getElementById('modalQrCode');
            if (data.qr_code && !modalQr.classList.contains('hidden')) {
                const img = document.getElementById('qr-image');
                const spinner = document.getElementById('qr-spinner');
                img.src = data.qr_code.startsWith('data:') ? data.qr_code : 'data:image/png;base64,' + data.qr_code;
                img.classList.remove('hidden');
                spinner.classList.add('hidden');
            }
        })
        .catch(err => console.error('Erro polling:', err));
}

function conectarWhatsapp() {
    // Se o agente estiver offline, avisa e abre o modal com o download de 1 clique
    if (!ultimoAgenteOnline) {
        alert('⚠️ O Pulse Agent está desligado no computador da sua loja!\n\nPara conectar o WhatsApp, você precisa abrir o aplicativo no seu PC primeiro.\n\nBaixe o arquivo de 1 clique e dê dois cliques nele para iniciar.');
        abrirModalInstalacao();
        return;
    }

    abrirModalQr();
    document.getElementById('qr-spinner').classList.remove('hidden');
    document.getElementById('qr-image').classList.add('hidden');

    fetch('<?= Url::to(['/vendas/bridge-whatsapp/conectar']) ?>', { method: 'POST' })
        .then(r => r.json())
        .then(d => {
            console.log('Comando conectar:', d);
        });
}

function desconectarWhatsapp() {
    if (!confirm('Deseja realmente desconectar a sessão do WhatsApp?')) return;
    fetch('<?= Url::to(['/vendas/bridge-whatsapp/desconectar']) ?>', { method: 'POST' })
        .then(r => r.json())
        .then(d => {
            alert(d.message);
            atualizarStatus();
        });
}

function enviarTeste(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-enviar-teste');
    btn.disabled = true;
    btn.innerHTML = '<span>Enfileirando...</span>';

    const numero = document.getElementById('teste-numero').value;
    const texto = document.getElementById('teste-texto').value;

    const fd = new FormData();
    fd.append('numero', numero);
    fd.append('texto', texto);

    fetch('<?= Url::to(['/vendas/bridge-whatsapp/enviar-teste']) ?>', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false;
        btn.innerHTML = '<span>Enviar Mensagem de Teste</span>';
        if (d.success) {
            alert('Mensagem enfileirada com sucesso! O Agente Local fará o envio.');
            location.reload();
        } else {
            alert('Erro: ' + d.message);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<span>Enviar Mensagem de Teste</span>';
        alert('Falha na comunicação com o servidor.');
    });
}
</script>
