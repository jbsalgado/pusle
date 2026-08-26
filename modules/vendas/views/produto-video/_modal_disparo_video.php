<?php

use yii\helpers\Url;
?>

<!-- Modal Disparo em Massa de Vídeos -->
<div id="modalDisparoVideo" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-80 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-slate-900 text-slate-100 rounded-3xl shadow-2xl max-w-3xl w-full overflow-hidden transform transition-all border border-slate-700/60 flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="bg-gradient-to-r from-sky-900 via-indigo-900 to-purple-950 px-6 py-5 text-white flex items-center justify-between border-b border-slate-800">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-sky-500/20 rounded-2xl border border-sky-400/30 text-sky-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h3 class="text-xl font-extrabold tracking-tight text-white">Disparo de Vídeo Promocional</h3>
                    <p class="text-xs text-sky-300 font-medium" id="lblVideosDisparoResumo">Envie vídeos promocionais 9:16 via WhatsApp Status, Mensagens Diretas e E-mail</p>
                </div>
            </div>
            <button onclick="fecharModalDisparoVideo()" class="text-slate-400 hover:text-white hover:bg-slate-800/80 p-2 rounded-xl transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Body / Content -->
        <div class="p-6 space-y-6 overflow-y-auto flex-1">

            <!-- Banner de Status do WhatsApp -->
            <div id="bannerStatusWhatsappVideo" class="bg-slate-800/70 border border-slate-700 p-3.5 rounded-2xl flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span id="indicadorDotWhatsappVideo" class="w-3.5 h-3.5 rounded-full bg-slate-500 animate-pulse inline-block"></span>
                    <div>
                        <div class="text-xs font-bold text-slate-200" id="textoStatusWhatsappVideo">Verificando conexão da Evolution API...</div>
                        <div class="text-[11px] text-slate-400" id="subtextoStatusWhatsappVideo">Consultando a instância da sua loja.</div>
                    </div>
                </div>
                <a href="<?= Url::to(['/evolution/default/index']) ?>" target="_blank" id="btnConectarWhatsappVideo" class="hidden text-xs font-bold px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl transition shadow-sm">
                    Conectar WhatsApp
                </a>
            </div>

            <!-- Formulário Configurações de Disparo de Vídeos -->
            <div id="secaoDisparoWhatsappVideo" class="space-y-6">

                <!-- Preview de Vídeos Selecionados -->
                <div class="bg-slate-800/50 border border-slate-700/60 p-4 rounded-2xl flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex -space-x-2 overflow-hidden" id="containerThumbnailsDisparoVideo"></div>
                        <div>
                            <div class="font-bold text-sm text-sky-400">Vídeo(s) Selecionado(s)</div>
                            <div class="text-xs text-slate-400">Pronto para ser transmitido aos seus contatos e Status.</div>
                        </div>
                    </div>
                </div>

                <!-- 1. Seleção dos Canais de Envio -->
                <div class="border-b border-slate-800 pb-5">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">1. Selecione os Canais de Envio</label>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="checkbox" id="canal_video_status" checked onchange="calcularResumoEnvioVideo()" class="peer sr-only">
                            <div class="p-3.5 border-2 border-slate-700 peer-checked:border-sky-500 peer-checked:bg-sky-950/40 rounded-2xl transition flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-sm text-slate-100">📲 Status do WhatsApp</span>
                                    <span class="w-4 h-4 rounded-full border border-sky-500 flex items-center justify-center text-sky-400 peer-checked:bg-sky-500 peer-checked:text-white text-xs">✓</span>
                                </div>
                                <p class="text-xs text-slate-400">Posta o vídeo MP4 9:16 no Status/Stories da conta WhatsApp oficial da loja.</p>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="checkbox" id="canal_video_whatsapp" checked onchange="calcularResumoEnvioVideo()" class="peer sr-only">
                            <div class="p-3.5 border-2 border-slate-700 peer-checked:border-sky-500 peer-checked:bg-sky-950/40 rounded-2xl transition flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-sm text-slate-100">💬 WhatsApp Direto</span>
                                    <span class="w-4 h-4 rounded-full border border-sky-500 flex items-center justify-center text-sky-400 peer-checked:bg-sky-500 peer-checked:text-white text-xs">✓</span>
                                </div>
                                <p class="text-xs text-slate-400">Envia o vídeo MP4 + mensagem para contatos via Evolution API com delay anti-ban.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 2. Seleção de Destinatários -->
                <div class="border-b border-slate-800 pb-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">2. Clientes e Destinatários de Envio</label>
                        <div class="flex gap-2">
                            <button type="button" onclick="alternarTodosClientesVideo()" class="text-xs text-sky-400 hover:underline font-bold" id="btnToggleTodosClientesVideo">Marcar Todos</button>
                        </div>
                    </div>

                    <!-- Busca de Clientes -->
                    <input type="text" id="buscaClienteVideoInput" onkeyup="filtrarClientesNaTelaVideo(this.value)" placeholder="Buscar cliente por nome, telefone ou e-mail..." class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-slate-200 focus:ring-2 focus:ring-sky-500">

                    <!-- Lista de Clientes -->
                    <div class="bg-slate-950/80 border border-slate-800 rounded-xl p-3 max-h-44 overflow-y-auto space-y-2" id="listaClientesVideoContainer">
                        <div class="text-xs text-slate-500 text-center py-4">Carregando lista de clientes...</div>
                    </div>

                    <!-- Entrada Manual de Números -->
                    <div class="pt-2">
                        <label class="block text-[11px] font-bold text-slate-300 mb-1">💬 Números de WhatsApp Adicionais (Manuais)</label>
                        <textarea id="telefones_manuais_video" onkeyup="calcularResumoEnvioVideo()" rows="2" class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-slate-200 focus:ring-2 focus:ring-sky-500" placeholder="Cole números adicionais (ex: 81999998888, 81988887777)"></textarea>
                    </div>
                </div>

                <!-- 3. Controle Anti-Ban (Delay & Pausas) -->
                <div class="border-b border-slate-800 pb-5">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">3. Configurações Anti-Ban & Ritmo de Envio</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 mb-1">Intervalo Entre Envios</label>
                            <select id="antiban_delay_video" onchange="calcularResumoEnvioVideo()" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-slate-200">
                                <option value="5">5 segundos (Mais Rápido)</option>
                                <option value="10" selected>10 segundos (Recomendado)</option>
                                <option value="15">15 segundos (Mais Seguro)</option>
                                <option value="30">30 segundos (Ultra Seguro)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 mb-1">Pausa por Lote</label>
                            <select id="antiban_lote_video" onchange="calcularResumoEnvioVideo()" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-slate-200">
                                <option value="10_60" selected>10 mensagens -> Pausa 1 min</option>
                                <option value="20_120">20 mensagens -> Pausa 2 min</option>
                                <option value="50_300">50 mensagens -> Pausa 5 min</option>
                            </select>
                        </div>
                        <div class="flex items-end pb-2">
                            <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
                                <input type="checkbox" id="antiban_optout_video" class="rounded text-sky-500 focus:ring-sky-400">
                                <span>Incluir rodapé de Opt-out ("PARAR")</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 4. Texto da Mensagem Promocional -->
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">4. Mensagem Promocional Customizada</label>
                    <textarea id="disparo_mensagem_texto_video" rows="4" class="w-full p-3 bg-slate-950 border border-slate-700 rounded-xl text-xs text-slate-200 focus:ring-2 focus:ring-sky-500" placeholder="Digite o texto da legenda do vídeo. Variáveis: {NOME}, {PRODUTO}, {PRECO}, {LINK}">🎬 VÍDEO PROMOCIONAL IMPERDÍVEL! 🚀

Olá {NOME}! Confira este produto incrível:
* {PRODUTO} por apenas {PRECO}!

🛒 Acesse e compre online: {LINK}

Peça agora mesmo pelo nosso atendimento!</textarea>
                    <p class="text-[11px] text-slate-400 mt-1">Variáveis dinâmicas: <code class="bg-slate-800 text-sky-300 px-1 rounded">{NOME}</code>, <code class="bg-slate-800 text-sky-300 px-1 rounded">{PRODUTO}</code>, <code class="bg-slate-800 text-sky-300 px-1 rounded">{PRECO}</code>, <code class="bg-slate-800 text-emerald-400 font-bold px-1 rounded">{LINK}</code> 🔗 (Link Clicável do Produto)</p>
                </div>

                <!-- Resumo Dinâmico -->
                <div id="lblEstimativaEnvioVideo" class="text-xs text-slate-300 bg-slate-800/90 p-3 rounded-xl border border-slate-700/80">
                    📊 <strong>Resumo do Lote:</strong> Calculando destinatários...
                </div>

                <!-- Botão de Início do Disparo -->
                <button onclick="iniciarDisparoVideoWhatsappExec()" class="w-full py-4 px-6 bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 text-white font-extrabold rounded-2xl transition duration-300 shadow-xl flex items-center justify-center gap-3 text-base">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    <span>Iniciar Disparo de Vídeo via WhatsApp</span>
                </button>

            </div>

            <!-- Progresso do Disparo -->
            <div id="secaoProgressoDisparoVideo" class="hidden py-8 space-y-5 text-center">
                <div class="relative w-16 h-16 mx-auto">
                    <div class="w-16 h-16 border-4 border-slate-700 border-t-sky-500 rounded-full animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-xl">🚀</div>
                </div>

                <div>
                    <h4 class="font-extrabold text-slate-100 text-xl" id="tituloStatusDisparoVideo">Processando Disparo de Vídeo...</h4>
                    <p class="text-sm text-slate-400 mt-1" id="subtituloStatusDisparoVideo">Enviando vídeo para o Status e destinatários na fila em background.</p>
                </div>

                <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 max-w-md mx-auto">
                    <div class="w-full bg-slate-800 h-3 rounded-full overflow-hidden">
                        <div id="barraProgressoDisparoVideo" class="bg-gradient-to-r from-sky-500 to-indigo-500 h-full w-0 transition-all duration-300"></div>
                    </div>
                    <div class="flex justify-between text-xs text-slate-400 mt-2 font-mono">
                        <span id="lblProgressoItensVideo">0 / 0 enviados</span>
                        <span id="lblProgressoPercentualVideo">0%</span>
                    </div>
                </div>

                <button id="btnFecharDisparoVideoConcluido" onclick="fecharModalDisparoVideo()" class="hidden px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl transition">
                    Concluir e Fechar
                </button>
            </div>

        </div>
    </div>
</div>
