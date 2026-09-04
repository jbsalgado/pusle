<?php

use yii\helpers\Url;
?>

<!-- Modal Disparo em Massa de Cards -->
<div id="modalDisparoMassa" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-70 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full overflow-hidden transform transition-all border border-gray-100 flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-800 via-indigo-800 to-purple-900 px-6 py-5 text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-600/40 rounded-xl border border-purple-400/30">
                    <svg class="w-6 h-6 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </div>
                <div>
                    <h3 class="text-xl font-extrabold tracking-tight">Disparo em Massa de Cards</h3>
                    <p class="text-xs text-purple-200 font-medium">Envie cards promocionais via WhatsApp Status, Mensagens Diretas e E-mail</p>
                </div>
            </div>
            <button onclick="fecharModalDisparoMassa()" class="text-purple-200 hover:text-white hover:bg-white/10 p-2 rounded-xl transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Body / Content -->
        <div class="p-6 space-y-6 overflow-y-auto flex-1">

            <!-- Seletor de Modo: Disparo Automático vs Download / Envio Manual -->
            <div class="bg-gray-100 p-1.5 rounded-2xl flex gap-2 border border-gray-200 shadow-inner">
                <button type="button" id="btnModoDisparoAuto" onclick="alternarModoOperacao('automatico')" class="flex-1 py-3 px-4 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-3 bg-white text-purple-900 border border-purple-200 shadow-sm">
                    <span class="text-xl">🚀</span>
                    <div class="text-left">
                        <div class="font-extrabold leading-tight text-sm">Disparo Automático</div>
                        <div class="text-[11px] text-purple-600 font-medium">WhatsApp API (Status/Direct) e E-mail</div>
                    </div>
                </button>
                <button type="button" id="btnModoDownloadManual" onclick="alternarModoOperacao('manual')" class="flex-1 py-3 px-4 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-3 text-gray-600 hover:text-gray-900 hover:bg-white/60">
                    <span class="text-xl">📥</span>
                    <div class="text-left">
                        <div class="font-extrabold leading-tight text-sm">Gerar Cards para Baixar / Enviar Manual</div>
                        <div class="text-[11px] text-gray-500 font-medium">Download em ZIP e WhatsApp Manual</div>
                    </div>
                </button>
            </div>

            <!-- Banner de Status do WhatsApp -->
            <div id="bannerStatusWhatsapp" class="bg-gray-50 border border-gray-200 p-3 rounded-2xl flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span id="indicadorDotWhatsapp" class="w-3.5 h-3.5 rounded-full bg-gray-400 animate-pulse inline-block"></span>
                    <div>
                        <div class="text-xs font-bold text-gray-800" id="textoStatusWhatsapp">Verificando conexão da Evolution API...</div>
                        <div class="text-[11px] text-gray-500" id="subtextoStatusWhatsapp">Consultando a instância da sua loja.</div>
                    </div>
                </div>
                <a href="<?= Url::to(['/evolution/default/index']) ?>" target="_blank" id="btnConectarWhatsapp" class="hidden text-xs font-bold px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-xl transition shadow-sm">
                    Conectar WhatsApp
                </a>
            </div>

            <!-- Banner de Consumo de Armazenamento de Cards (50 MB) (Exibido no Modo Download / Envio Manual) -->
            <div id="bannerArmazenamentoCards" class="hidden bg-gradient-to-r from-slate-50 to-indigo-50/40 border border-slate-200 p-4 rounded-2xl space-y-2.5 transition-all shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-lg shadow-sm border border-purple-200/50">
                            💾
                        </div>
                        <div>
                            <div class="text-xs font-black text-gray-900 flex items-center gap-2">
                                <span>Armazenamento de Cards</span>
                                <span id="badgePorcentagemEspaco" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 transition-colors">0%</span>
                            </div>
                            <div class="text-[11px] text-gray-600 font-medium" id="textoConsumoEspaco">
                                Carregando consumo... (Limite máximo de 50 MB por loja)
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="carregarStatusEspaco()" class="p-1.5 text-gray-400 hover:text-gray-700 rounded-xl hover:bg-white border border-transparent hover:border-gray-200 transition" title="Atualizar espaço em disco">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                        <button type="button" onclick="excluirTodosCards()" class="px-2.5 py-1.5 text-[11px] font-bold text-red-600 hover:text-white hover:bg-red-600 border border-red-200 hover:border-red-600 rounded-xl transition flex items-center gap-1 shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Excluir Todos</span>
                        </button>
                    </div>
                </div>

                <!-- Barra de Progresso -->
                <div class="w-full bg-gray-200/80 rounded-full h-2.5 overflow-hidden p-0.5 border border-gray-200">
                    <div id="barraProgressoEspaco" class="bg-emerald-500 h-1.5 transition-all duration-500 rounded-full" style="width: 0%"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-500">
                    <span>⏱️ Não baixados em até 24h são excluídos automaticamente.</span>
                    <span id="textoDetalheCardsSalvos" class="font-bold text-gray-700">0 cards</span>
                </div>
            </div>

            <!-- Formulário Configurações -->
            <div id="secaoConfigDisparo" class="space-y-6">

                <!-- Info de produtos selecionados -->
                <div class="bg-purple-50 border border-purple-200 p-4 rounded-2xl flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-full bg-purple-700 text-white font-bold text-sm flex items-center justify-center" id="qtdProdutosBadge">0</span>
                        <div>
                            <div class="font-bold text-sm text-purple-900">Produto(s) Selecionado(s)</div>
                            <div class="text-xs text-purple-700">Os cards serão gerados automaticamente para este lote.</div>
                        </div>
                    </div>
                </div>

                <!-- 1. Modelo & Estilo Visual -->
                <div class="border-b border-gray-100 pb-5">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">1. Estilo do Card Visual</label>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Template</label>
                            <select id="disparo_template" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-medium focus:ring-2 focus:ring-purple-600">
                                <option value="modern_dark">Modern Dark (Glassmorphism)</option>
                                <option value="vibrant_gradient">Vibrant Gradient</option>
                                <option value="minimalist_light">Minimalist Light</option>
                                <option value="neon_promo">Neon Promo</option>
                                <option value="bold_banner">Bold Banner</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Paleta de Cores</label>
                            <select id="disparo_cor_tema" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-medium focus:ring-2 focus:ring-purple-600">
                                <option value="dark">Dark Slate</option>
                                <option value="ocean">Ocean Blue</option>
                                <option value="emerald">Emerald Green</option>
                                <option value="purple">Purple Sunset</option>
                                <option value="sunset">Sunset Orange</option>
                                <option value="rose">Rose Pink</option>
                                <option value="gold">Premium Gold</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Estilo de Fundo</label>
                            <select id="disparo_fundo_estilo" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-medium focus:ring-2 focus:ring-purple-600">
                                <option value="gradient">Gradiente Suave</option>
                                <option value="mesh">Mesh Fluid</option>
                                <option value="geometric">Geométrico</option>
                                <option value="dots">Grid Pontos</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Enquadramento & Rotação da Foto do Produto -->
                <div class="border-b border-gray-100 pb-5">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">
                            🖼️ Enquadramento & Rotação da Foto
                        </label>
                        <span class="text-[11px] text-purple-700 bg-purple-50 px-2 py-0.5 rounded-full font-medium">Auto-Ajuste Inteligente</span>
                    </div>

                    <!-- Opções de Enquadramento -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="disparo_enquadramento_foto" value="auto" checked class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-50/70 rounded-xl transition flex flex-col gap-1 h-full">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs text-gray-900 flex items-center gap-1">
                                        <span>✨</span> Auto-Girar & Otimizar
                                    </span>
                                    <span class="text-[9px] bg-purple-600 text-white font-bold px-1.5 py-0.5 rounded">Recomendado</span>
                                </div>
                                <p class="text-[11px] text-gray-500 leading-tight">Deita calçados e fotos verticais para preencher o card sem cortes e sem laterais vazias.</p>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="disparo_enquadramento_foto" value="cover" class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-50/70 rounded-xl transition flex flex-col gap-1 h-full">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs text-gray-900 flex items-center gap-1">
                                        <span>🔍</span> Zoom Total (Cover)
                                    </span>
                                </div>
                                <p class="text-[11px] text-gray-500 leading-tight">A foto ocupa 100% da caixa reservada sem sobrar nenhum espaço em branco nas laterais.</p>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="disparo_enquadramento_foto" value="contain" class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-50/70 rounded-xl transition flex flex-col gap-1 h-full">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs text-gray-900 flex items-center gap-1">
                                        <span>🎯</span> Centralizado (Contain)
                                    </span>
                                </div>
                                <p class="text-[11px] text-gray-500 leading-tight">Foto original exibida inteira ao centro com margens de respiro naturais.</p>
                            </div>
                        </label>
                    </div>

                    <!-- Ajuste Manual de Rotação (Avançado/Opcional) -->
                    <div class="flex items-center gap-3 bg-gray-50 p-2.5 rounded-xl border border-gray-200 text-xs">
                        <span class="font-semibold text-gray-700 whitespace-nowrap flex items-center gap-1">
                            <span>🔄</span> Rotação:
                        </span>
                        <select id="disparo_rotacao_foto" class="w-full bg-white border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-700 focus:ring-2 focus:ring-purple-600">
                            <option value="auto" selected>Automático (O sistema detecta e deita se necessário)</option>
                            <option value="0">0° - Padrão Original (Sem Giro)</option>
                            <option value="-90">-90° - Deitar Calçado (Anti-horário / Sola Embaixo)</option>
                            <option value="90">90° - Girar 90° (Sentido Horário)</option>
                            <option value="180">180° - Inverter (De Ponta Cabeça)</option>
                        </select>
                    </div>
                </div>

                <!-- Mensagem Promocional Customizada no Card (Gatilho de Vendas) -->
                <div class="border-b border-gray-100 pb-5">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                            <span>📢</span> Mensagem Promocional no Card
                        </label>
                        <span class="text-[11px] text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full font-bold flex items-center gap-1">
                            <span>🔥</span> Gatilho de Vendas
                        </span>
                    </div>
                    <p class="text-[11px] text-gray-500 mb-3 leading-snug">
                        Texto desenhado em destaque na imagem do card para atrair atenção e criar senso de urgência imediata.
                    </p>

                    <!-- Presets Rápidos de 1 Clique -->
                    <div class="mb-3">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                            <span>Gatilhos Rápidos de Alta Conversão:</span>
                            <span class="text-[10px] text-gray-400 font-normal">Clique para aplicar</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5" id="chipsGatilhosPromocionais">
                            <button type="button" onclick="aplicarGatilhoPromocional('⚡ Oferta Relâmpago • Só Hoje')" class="text-xs px-2.5 py-1 rounded-lg border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-900 font-bold transition flex items-center gap-1 shadow-2xs">
                                <span>⚡</span> Oferta Relâmpago
                            </button>
                            <button type="button" onclick="aplicarGatilhoPromocional('🔥 Queima de Estoque')" class="text-xs px-2.5 py-1 rounded-lg border border-red-300 bg-red-50 hover:bg-red-100 text-red-900 font-bold transition flex items-center gap-1 shadow-2xs">
                                <span>🔥</span> Queima de Estoque
                            </button>
                            <button type="button" onclick="aplicarGatilhoPromocional('🚚 Frete Grátis')" class="text-xs px-2.5 py-1 rounded-lg border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-900 font-bold transition flex items-center gap-1 shadow-2xs">
                                <span>🚚</span> Frete Grátis
                            </button>
                            <button type="button" onclick="aplicarGatilhoPromocional('⏳ Últimas Unidades!')" class="text-xs px-2.5 py-1 rounded-lg border border-orange-300 bg-orange-50 hover:bg-orange-100 text-orange-900 font-bold transition flex items-center gap-1 shadow-2xs">
                                <span>⏳</span> Últimas Unidades
                            </button>
                            <button type="button" onclick="aplicarGatilhoPromocional('💳 Até 10x Sem Juros')" class="text-xs px-2.5 py-1 rounded-lg border border-blue-300 bg-blue-50 hover:bg-blue-100 text-blue-900 font-bold transition flex items-center gap-1 shadow-2xs">
                                <span>💳</span> 10x Sem Juros
                            </button>
                            <button type="button" onclick="aplicarGatilhoPromocional('💥 15% OFF no PIX')" class="text-xs px-2.5 py-1 rounded-lg border border-purple-300 bg-purple-50 hover:bg-purple-100 text-purple-900 font-bold transition flex items-center gap-1 shadow-2xs">
                                <span>💥</span> 15% OFF no PIX
                            </button>
                            <button type="button" onclick="aplicarGatilhoPromocional('⭐ Lançamento Exclusivo')" class="text-xs px-2.5 py-1 rounded-lg border border-indigo-300 bg-indigo-50 hover:bg-indigo-100 text-indigo-900 font-bold transition flex items-center gap-1 shadow-2xs">
                                <span>⭐</span> Lançamento
                            </button>
                            <button type="button" onclick="aplicarGatilhoPromocional('💎 Qualidade Premium Garantida')" class="text-xs px-2.5 py-1 rounded-lg border border-teal-300 bg-teal-50 hover:bg-teal-100 text-teal-900 font-bold transition flex items-center gap-1 shadow-2xs">
                                <span>💎</span> Qualidade Premium
                            </button>
                            <button type="button" onclick="aplicarGatilhoPromocional('')" class="text-xs px-2 py-1 rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100 text-gray-600 font-medium transition flex items-center gap-1" title="Limpar mensagem">
                                <span>🚫</span> Limpar
                            </button>
                        </div>
                    </div>

                    <!-- Input Customizado com Contador de Caracteres -->
                    <div class="relative">
                        <input type="text" id="disparo_mensagem_card" maxlength="50" oninput="atualizarContadorMensagemCard(this.value)" placeholder="Ex: ⚡ SÓ HOJE: FRETE GRÁTIS ACIMA DE R$ 199" class="w-full px-3 py-2 pr-14 border border-gray-300 rounded-xl text-xs font-semibold text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-purple-600">
                        <span id="contadorCaracteresMensagemCard" class="absolute right-3 top-2.5 text-[10px] font-bold text-gray-400 select-none">
                            0/50
                        </span>
                    </div>
                </div>

                <!-- Formato dos Cards (Exibido no Modo Manual / Download) -->
                <div id="secaoFormatoCardsManual" class="border-b border-gray-100 pb-5 hidden">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Formato dos Cards para Geração</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="formato_card_manual" value="feed" checked class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-emerald-600 peer-checked:bg-emerald-50 rounded-xl transition flex flex-col gap-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-sm text-gray-900">📱 Feed Quadrado</span>
                                    <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-1.5 py-0.5 rounded">1080x1080 (1:1)</span>
                                </div>
                                <p class="text-[11px] text-gray-500">Ideal para Feed do Instagram, Facebook e WhatsApp.</p>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="formato_card_manual" value="stories" class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-emerald-600 peer-checked:bg-emerald-50 rounded-xl transition flex flex-col gap-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-sm text-gray-900">📲 Stories / Status</span>
                                    <span class="text-[10px] bg-indigo-100 text-indigo-800 font-bold px-1.5 py-0.5 rounded">1080x1920 (9:16)</span>
                                </div>
                                <p class="text-[11px] text-gray-500">Perfeito para Status do WhatsApp e Stories.</p>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="formato_card_manual" value="ambos" class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-emerald-600 peer-checked:bg-emerald-50 rounded-xl transition flex flex-col gap-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-sm text-gray-900">📦 Ambos Formatos</span>
                                    <span class="text-[10px] bg-purple-100 text-purple-800 font-bold px-1.5 py-0.5 rounded">Feed + Stories</span>
                                </div>
                                <p class="text-[11px] text-gray-500">Gera as duas versões para cada produto selecionado.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Agrupamento de Produtos com Matriz (Exibido no Modo Manual / Download) -->
                <div id="secaoAgrupamentoMatrizManual" class="border-b border-gray-100 pb-5 hidden">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Produtos com Matriz / Grade de Variações</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="modo_matriz_manual" value="por_cor" checked class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-emerald-600 peer-checked:bg-emerald-50 rounded-xl transition flex flex-col gap-1 h-full">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-sm text-gray-900 flex items-center gap-1.5">
                                        <span>🎨</span> 1 Card por Cor / Modelo
                                    </span>
                                    <span class="text-[10px] bg-emerald-600 text-white font-bold px-1.5 py-0.5 rounded shadow-sm">Recomendado</span>
                                </div>
                                <p class="text-[11px] text-gray-500 leading-snug">Gera 1 card para cada cor/modelo com a grade de tamanhos e preços desenhada dentro da imagem. Rápido e econômico!</p>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="modo_matriz_manual" value="por_item" class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-emerald-600 peer-checked:bg-emerald-50 rounded-xl transition flex flex-col gap-1 h-full">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-sm text-gray-900 flex items-center gap-1.5">
                                        <span>📦</span> 1 Card por Variação Individual
                                    </span>
                                    <span class="text-[10px] bg-gray-200 text-gray-700 font-bold px-1.5 py-0.5 rounded">Individual</span>
                                </div>
                                <p class="text-[11px] text-gray-500 leading-snug">Gera um card exclusivo para cada número/tamanho isoladamente (pode gerar dezenas de imagens).</p>
                            </div>
                        </label>
                    </div>

                    <!-- Filtro de Estoque Disponível -->
                    <div class="mt-3 p-3 bg-emerald-50/70 border border-emerald-200 rounded-xl flex items-center justify-between">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input type="checkbox" id="check_apenas_com_estoque" checked class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                            <span class="text-xs font-bold text-gray-900">
                                📦 Apenas tamanhos com estoque disponível <span class="text-[10px] bg-emerald-600 text-white font-bold px-1.5 py-0.5 rounded ml-1">Recomendado</span>
                            </span>
                        </label>
                        <span class="text-[11px] text-gray-500 hidden sm:inline">Oculta números esgotados ou zerados</span>
                    </div>
                </div>

                <!-- 2. Canais de Disparo -->
                <div id="secaoCanaisDisparo" class="border-b border-gray-100 pb-5">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">2. Selecione os Canais de Envio</label>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="cursor-pointer">
                            <input type="checkbox" id="canal_status" checked class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-50 rounded-xl transition flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-sm text-gray-900">📲 Status WhatsApp</span>
                                    <span class="w-4 h-4 rounded-full border border-purple-600 flex items-center justify-center text-purple-700 peer-checked:bg-purple-600 peer-checked:text-white text-xs">✓</span>
                                </div>
                                <p class="text-xs text-gray-500">Posta a imagem do card no Status/Stories da conta WhatsApp oficial da loja.</p>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="checkbox" id="canal_whatsapp" checked class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-50 rounded-xl transition flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-sm text-gray-900">💬 WhatsApp Direto</span>
                                    <span class="w-4 h-4 rounded-full border border-purple-600 flex items-center justify-center text-purple-700 peer-checked:bg-purple-600 peer-checked:text-white text-xs">✓</span>
                                </div>
                                <p class="text-xs text-gray-500">Envia o card + texto para os contatos via Evolution API com delay anti-ban.</p>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="checkbox" id="canal_email" checked class="peer sr-only">
                            <div class="p-3 border-2 border-gray-200 peer-checked:border-purple-600 peer-checked:bg-purple-50 rounded-xl transition flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-sm text-gray-900">✉️ E-mail Marketing</span>
                                    <span class="w-4 h-4 rounded-full border border-purple-600 flex items-center justify-center text-purple-700 peer-checked:bg-purple-600 peer-checked:text-white text-xs">✓</span>
                                </div>
                                <p class="text-xs text-gray-500">Dispara e-mail promocional responsivo com o card em destaque.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 3. Seleção e Entrada de Destinatários -->
                <div id="containerSelecaoClientes" class="border-b border-gray-100 pb-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">3. Clientes e Destinatários de Envio</label>
                        <div class="flex gap-2">
                            <button type="button" onclick="alternarTodosClientes()" class="text-xs text-purple-700 hover:underline font-bold" id="btnToggleTodosClientes">Marcar Todos</button>
                        </div>
                    </div>

                    <!-- Busca de Clientes -->
                    <input type="text" id="buscaClienteInput" onkeyup="filtrarClientesNaTela(this.value)" placeholder="Buscar cliente por nome, telefone ou e-mail..." class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-purple-600">

                    <!-- Lista de Clientes -->
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 max-h-44 overflow-y-auto space-y-2" id="listaClientesContainer">
                        <div class="text-xs text-gray-500 text-center py-4">Carregando lista de clientes...</div>
                    </div>

                    <!-- Entrada Manual de Números e E-mails -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 mb-1">💬 Números de WhatsApp Adicionais (Manuais)</label>
                            <textarea id="telefones_manuais" rows="2" class="w-full p-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-purple-600" placeholder="Cole números adicionais separados por espaço, vírgula ou linha (ex: 81999998888 81988887777, 11977776666)"></textarea>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 mb-1">✉️ E-mails Adicionais (Manuais)</label>
                            <textarea id="emails_manuais" rows="2" class="w-full p-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-purple-600" placeholder="Cole e-mails adicionais separados por espaço, vírgula ou linha (ex: cliente1@email.com cliente2@email.com)"></textarea>
                        </div>
                    </div>
                </div>

                <!-- 4. Texto da Mensagem Promocional -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">4. Mensagem Promocional Customizada</label>
                    <textarea id="disparo_mensagem_texto" rows="3" class="w-full p-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-purple-600" placeholder="Digite o texto da mensagem. Variáveis disponíveis: {NOME}, {PRODUTO}, {PRECO}">🔥 OFERTA IMPERDÍVEL 🔥

Olá {NOME}! Confira este produto incrível:
* {PRODUTO} por apenas {PRECO}!

Garanta o seu antes que acabe o estoque!</textarea>
                    <p class="text-[11px] text-gray-400 mt-1">Variáveis que serão substituídas automaticamente: <code class="bg-gray-100 text-purple-800 px-1 rounded">{NOME}</code>, <code class="bg-gray-100 text-purple-800 px-1 rounded">{PRODUTO}</code>, <code class="bg-gray-100 text-purple-800 px-1 rounded">{PRECO}</code></p>
                </div>

                <!-- Botão de Ação: Disparo Automático -->
                <button id="btnAcaoDisparoAuto" onclick="iniciarDisparoEmMassa()" class="w-full py-4 px-6 bg-gradient-to-r from-purple-700 to-indigo-700 hover:from-purple-800 hover:to-indigo-800 text-white font-extrabold rounded-2xl transition duration-300 shadow-xl flex items-center justify-center gap-3 text-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Iniciar Disparo em Massa
                </button>

                <!-- Botão de Ação: Download / Envio Manual -->
                <button id="btnAcaoDownloadManual" onclick="gerarCardsManual()" class="hidden w-full py-4 px-6 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-extrabold rounded-2xl transition duration-300 shadow-xl flex items-center justify-center gap-3 text-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    ✨ Gerar Cards para Download / Envio Manual
                </button>

            </div>

            <!-- Progresso ao Vivo do Disparo -->
            <div id="secaoProgressoDisparo" class="hidden py-6 space-y-5 text-center">
                <div class="relative w-16 h-16 mx-auto">
                    <div class="w-16 h-16 border-4 border-purple-200 border-t-purple-700 rounded-full animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-xl" id="iconeStatusDisparo">🚀</div>
                </div>

                <div>
                    <h4 class="font-extrabold text-gray-900 text-xl" id="tituloStatusDisparo">Processando Disparo em Massa...</h4>
                    <p class="text-sm text-gray-500 mt-1" id="subtituloStatusDisparo">Gerando cards e enviando mensagens nas filas em background.</p>
                </div>

                <!-- Barra de Progresso -->
                <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                    <div id="barraProgressoDisparo" class="bg-gradient-to-r from-purple-600 to-indigo-600 h-4 transition-all duration-500 rounded-full" style="width: 0%"></div>
                </div>

                <div class="grid grid-cols-3 gap-4 bg-gray-50 p-4 rounded-2xl border border-gray-200">
                    <div>
                        <div class="text-xs text-gray-500 font-bold uppercase">Total Agendado</div>
                        <div class="text-xl font-extrabold text-gray-800" id="statTotalItens">0</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 font-bold uppercase">Enviados</div>
                        <div class="text-xl font-extrabold text-green-600" id="statItensEnviados">0</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 font-bold uppercase">Falhas / Erros</div>
                        <div class="text-xl font-extrabold text-red-600" id="statItensErro">0</div>
                    </div>
                </div>

                <!-- Relatório de Erros se Houver -->
                <div id="containerErrosDisparo" class="hidden text-left bg-red-50 border border-red-200 rounded-2xl p-4 space-y-3 max-h-52 overflow-y-auto">
                    <h5 class="text-xs font-bold text-red-800 uppercase tracking-wider flex items-center gap-1">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Relatório Detalhado de Falhas
                    </h5>
                    <div id="listaErrosDisparo" class="text-xs text-red-700 space-y-1"></div>
                    <button id="btnReenviarErros" onclick="reenviarErrosDisparo()" class="w-full py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition text-xs shadow flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Reenviar Apenas Itens com Falha
                    </button>
                </div>

                <div class="pt-4 flex gap-3">
                    <button id="btnFecharDisparoConcluido" onclick="fecharModalDisparoMassa()" class="hidden w-full py-3 bg-purple-700 hover:bg-purple-800 text-white font-bold rounded-xl transition">
                        Concluir e Fechar
                    </button>
                </div>
            </div>

            <!-- Painel de Resultados do Modo Download / Envio Manual -->
            <div id="secaoResultadoManual" class="hidden py-4 space-y-6">
                <!-- Estado de Carregamento Progressivo com Feedback em Tempo Real -->
                <div id="loadingCardsManual" class="py-8 space-y-5 text-center max-w-lg mx-auto">
                    <div class="relative w-20 h-20 mx-auto">
                        <div class="w-20 h-20 border-4 border-emerald-100 border-t-emerald-600 rounded-full animate-spin"></div>
                        <div class="absolute inset-0 flex items-center justify-center text-2xl" id="iconeLoadingCards">🎨</div>
                    </div>

                    <div class="space-y-1">
                        <h4 class="font-extrabold text-gray-900 text-xl" id="tituloProgressoCards">Gerando Cards em Alta Resolução...</h4>
                        <p class="text-xs text-gray-500" id="subtituloProgressoCards">Processando variações da matriz e renderizando com Puppeteer.</p>
                    </div>

                    <!-- Barra de Progresso Real com Percentual -->
                    <div class="space-y-1.5 bg-gray-50 p-4 rounded-2xl border border-gray-200 shadow-inner text-left">
                        <div class="flex items-center justify-between text-xs font-bold text-gray-700">
                            <span id="labelProgressoContador">Card 0 de 0</span>
                            <span id="labelProgressoPorcentagem" class="text-emerald-700 font-extrabold">0%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden p-0.5 border border-gray-300/60">
                            <div id="barraProgressoCardsManual" class="bg-gradient-to-r from-emerald-500 via-teal-500 to-emerald-600 h-2 transition-all duration-300 rounded-full" style="width: 0%"></div>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-gray-500 pt-1">
                            <span id="labelProgressoItemAtual" class="truncate max-w-[280px] font-medium">Preparando lista de itens...</span>
                            <span id="labelProgressoTempo" class="font-mono text-gray-400">⏱️ 0s</span>
                        </div>
                    </div>

                    <!-- Botão de Cancelamento -->
                    <div class="pt-1">
                        <button type="button" id="btnCancelarGeracaoCards" onclick="cancelarGeracaoCardsManual()" class="px-4 py-2 text-xs font-bold text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-xl transition border border-gray-300 hover:border-red-200">
                            ✕ Cancelar Geração
                        </button>
                    </div>
                </div>

                <!-- Conteúdo quando concluído -->
                <div id="conteudoCardsProntos" class="hidden space-y-5">
                    <!-- Banner de Destaque com Botão de Download ZIP -->
                    <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-50 border-2 border-emerald-300 p-4 sm:p-5 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm">
                        <div class="flex items-center gap-3 text-center sm:text-left">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-2xl shadow">📦</div>
                            <div>
                                <div class="font-black text-lg text-emerald-950" id="tituloSucessoCards">Cards Gerados com Sucesso!</div>
                                <div class="text-xs text-emerald-700 font-medium" id="subtituloSucessoCards">Baixe o pacote ZIP com todas as artes ou compartilhe individualmente.</div>
                            </div>
                        </div>
                        <a id="btnBaixarZipTopo" href="#" target="_blank" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white text-sm font-extrabold rounded-xl shadow-lg hover:shadow-xl transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span>Baixar Todos em ZIP</span>
                            <span id="badgeTamanhoZip" class="text-[11px] bg-white/20 px-2 py-0.5 rounded-md font-bold"></span>
                        </a>
                    </div>

                    <!-- Grade de Cards Gerados -->
                    <div>
                        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Cards Gerados</span>
                                <span id="badgeContagemCards" class="text-xs font-bold bg-gray-100 text-gray-700 px-2.5 py-1 rounded-lg"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="alternarTodosCardsManual()" class="px-2.5 py-1 text-xs text-purple-700 hover:bg-purple-50 rounded-lg font-bold transition border border-purple-200" id="btnToggleTodosCards">
                                    Marcar Todos
                                </button>
                                <button type="button" id="btnExcluirSelecionadosCards" onclick="excluirCardsSelecionados()" disabled class="px-2.5 py-1 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-600 hover:text-white border border-red-200 rounded-lg transition opacity-50 cursor-not-allowed flex items-center gap-1 shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>Excluir Seleção (<span id="contagemSelecionadosCards">0</span>)</span>
                                </button>
                            </div>
                        </div>

                        <div id="gradeCardsGerados" class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[380px] overflow-y-auto p-1">
                            <!-- Inserido dinamicamente via JS -->
                        </div>
                    </div>

                    <!-- Botões de Ação Final -->
                    <div class="pt-3 border-t border-gray-100 flex flex-col sm:flex-row gap-3">
                        <button type="button" onclick="voltarParaConfigCardsManual()" class="py-3 px-5 border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold rounded-xl transition text-sm flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Alterar Opções / Gerar Mais
                        </button>
                        <button type="button" onclick="fecharModalDisparoMassa()" class="flex-1 py-3 px-6 bg-purple-700 hover:bg-purple-800 text-white font-bold rounded-xl transition shadow text-sm flex items-center justify-center gap-2">
                            Concluir e Fechar
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    let produtosSelecionadosDisparo = [];
    let intervalMonitoramento = null;
    let listaClientesCache = [];
    let whatsappConectadoCache = false;
    let ultimoDisparoIdAtivo = null;
    let modoOperacaoAtual = 'automatico';

    function aplicarGatilhoPromocional(texto) {
        const input = document.getElementById('disparo_mensagem_card');
        if (input) {
            input.value = texto;
            atualizarContadorMensagemCard(texto);
            input.focus();
        }
    }

    function atualizarContadorMensagemCard(texto) {
        const contador = document.getElementById('contadorCaracteresMensagemCard');
        if (contador) {
            const tamanho = (texto || '').length;
            contador.textContent = `${tamanho}/50`;
            if (tamanho >= 45) {
                contador.className = 'absolute right-3 top-2.5 text-[10px] font-bold text-amber-600 select-none';
            } else {
                contador.className = 'absolute right-3 top-2.5 text-[10px] font-bold text-gray-400 select-none';
            }
        }
    }

    function abrirModalDisparoMassa(produtosIds = []) {
        produtosSelecionadosDisparo = produtosIds;
        document.getElementById('qtdProdutosBadge').textContent = produtosSelecionadosDisparo.length;
        
        document.getElementById('modalDisparoMassa').classList.remove('hidden');
        document.getElementById('secaoConfigDisparo').classList.remove('hidden');
        document.getElementById('secaoProgressoDisparo').classList.add('hidden');
        document.getElementById('secaoResultadoManual').classList.add('hidden');
        document.getElementById('containerErrosDisparo').classList.add('hidden');
        document.getElementById('btnFecharDisparoConcluido').classList.add('hidden');

        if (document.getElementById('disparo_mensagem_card')) {
            atualizarContadorMensagemCard(document.getElementById('disparo_mensagem_card').value);
        }

        alternarModoOperacao(modoOperacaoAtual);
        verificarStatusWhatsapp();
        carregarListaClientes();
    }

    function alternarModoOperacao(modo) {
        modoOperacaoAtual = modo;
        const btnAuto = document.getElementById('btnModoDisparoAuto');
        const btnManual = document.getElementById('btnModoDownloadManual');
        const bannerWp = document.getElementById('bannerStatusWhatsapp');
        const secaoCanais = document.getElementById('secaoCanaisDisparo');
        const secaoClientes = document.getElementById('containerSelecaoClientes');
        const secaoFormatoManual = document.getElementById('secaoFormatoCardsManual');
        const secaoAgrupamentoManual = document.getElementById('secaoAgrupamentoMatrizManual');
        const btnAcaoAuto = document.getElementById('btnAcaoDisparoAuto');
        const btnAcaoManual = document.getElementById('btnAcaoDownloadManual');

        if (modo === 'manual') {
            btnAuto.className = 'flex-1 py-3 px-4 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-3 text-gray-600 hover:text-gray-900 hover:bg-white/60';
            btnManual.className = 'flex-1 py-3 px-4 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-3 bg-white text-emerald-900 border border-emerald-200 shadow-sm';

            bannerWp.classList.add('hidden');
            secaoCanais.classList.add('hidden');
            secaoClientes.classList.add('hidden');
            secaoFormatoManual.classList.remove('hidden');
            if (secaoAgrupamentoManual) secaoAgrupamentoManual.classList.remove('hidden');

            btnAcaoAuto.classList.add('hidden');
            btnAcaoManual.classList.remove('hidden');

            const bannerEspaco = document.getElementById('bannerArmazenamentoCards');
            if (bannerEspaco) bannerEspaco.classList.remove('hidden');
            carregarStatusEspaco();
        } else {
            btnAuto.className = 'flex-1 py-3 px-4 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-3 bg-white text-purple-900 border border-purple-200 shadow-sm';
            btnManual.className = 'flex-1 py-3 px-4 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-3 text-gray-600 hover:text-gray-900 hover:bg-white/60';

            bannerWp.classList.remove('hidden');
            secaoCanais.classList.remove('hidden');
            secaoClientes.classList.remove('hidden');
            secaoFormatoManual.classList.add('hidden');
            if (secaoAgrupamentoManual) secaoAgrupamentoManual.classList.add('hidden');

            btnAcaoAuto.classList.remove('hidden');
            btnAcaoManual.classList.add('hidden');

            const bannerEspaco = document.getElementById('bannerArmazenamentoCards');
            if (bannerEspaco) bannerEspaco.classList.add('hidden');
        }
    }

    function fecharModalDisparoMassa() {
        if (intervalMonitoramento) {
            clearInterval(intervalMonitoramento);
            intervalMonitoramento = null;
        }
        document.getElementById('modalDisparoMassa').classList.add('hidden');
    }

    function verificarStatusWhatsapp() {
        const dot = document.getElementById('indicadorDotWhatsapp');
        const texto = document.getElementById('textoStatusWhatsapp');
        const subtexto = document.getElementById('subtextoStatusWhatsapp');
        const btnConectar = document.getElementById('btnConectarWhatsapp');

        dot.className = 'w-3.5 h-3.5 rounded-full bg-gray-400 animate-pulse inline-block';
        texto.textContent = 'Verificando conexão da Evolution API...';
        subtexto.textContent = 'Consultando status da instância da loja.';
        btnConectar.classList.add('hidden');

        fetch('<?= Url::to(['/vendas/disparo/status-whatsapp']) ?>')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.connected) {
                whatsappConectadoCache = true;
                dot.className = 'w-3.5 h-3.5 rounded-full bg-green-500 inline-block shadow';
                texto.textContent = '🟢 WhatsApp Conectado via Evolution API';
                subtexto.textContent = 'Instância: ' + (data.instance_name || 'Ativa') + ' (Pronto para disparos no Status e Mensagens)';
            } else {
                whatsappConectadoCache = false;
                dot.className = 'w-3.5 h-3.5 rounded-full bg-red-500 inline-block shadow';
                texto.textContent = '🔴 WhatsApp Desconectado';
                subtexto.textContent = 'Conecte sua instância da Evolution API antes de disparar via WhatsApp.';
                btnConectar.classList.remove('hidden');
            }
        })
        .catch(err => {
            whatsappConectadoCache = false;
            dot.className = 'w-3.5 h-3.5 rounded-full bg-yellow-500 inline-block';
            texto.textContent = '⚠️ Falha ao verificar Evolution API';
            subtexto.textContent = 'Não foi possível consultar o status da conexão.';
        });
    }

    function carregarListaClientes() {
        const container = document.getElementById('listaClientesContainer');
        fetch('<?= Url::to(['/vendas/disparo/clientes']) ?>')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.clientes) {
                listaClientesCache = data.clientes;
                renderizarListaClientes(listaClientesCache);
            }
        })
        .catch(err => {
            container.innerHTML = '<div class="text-xs text-red-500 text-center py-3">Erro ao carregar clientes.</div>';
        });
    }

    function renderizarListaClientes(clientes) {
        const container = document.getElementById('listaClientesContainer');
        if (clientes.length === 0) {
            container.innerHTML = '<div class="text-xs text-gray-500 text-center py-3">Nenhum cliente cadastrado com os critérios.</div>';
            return;
        }

        container.innerHTML = clientes.map(c => {
            const badgeWp = c.tem_whatsapp ? '<span class="px-1.5 py-0.5 bg-green-100 text-green-800 text-[10px] font-bold rounded">📱 WhatsApp</span>' : '';
            const badgeMail = c.tem_email ? '<span class="px-1.5 py-0.5 bg-blue-100 text-blue-800 text-[10px] font-bold rounded">✉️ E-mail</span>' : '';

            return `
                <label class="flex items-center justify-between p-2 hover:bg-white rounded-lg transition cursor-pointer border border-transparent hover:border-gray-200">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="cliente_item_chk" value="${c.id}" checked class="rounded text-purple-600 focus:ring-purple-500">
                        <div class="text-xs">
                            <span class="font-bold text-gray-800">${c.nome}</span>
                            <span class="text-gray-500 text-[11px]">(${c.celular || c.telefone || 'Sem tel'} | ${c.email || 'Sem e-mail'})</span>
                        </div>
                    </div>
                    <div class="flex gap-1">
                        ${badgeWp}
                        ${badgeMail}
                    </div>
                </label>
            `;
        }).join('');
    }

    function filtrarClientesNaTela(termo) {
        const termoLimpo = termo.toLowerCase().trim();
        if (!termoLimpo) {
            renderizarListaClientes(listaClientesCache);
            return;
        }
        const filtrados = listaClientesCache.filter(c => 
            (c.nome && c.nome.toLowerCase().includes(termoLimpo)) ||
            (c.celular && c.celular.includes(termoLimpo)) ||
            (c.telefone && c.telefone.includes(termoLimpo)) ||
            (c.email && c.email.toLowerCase().includes(termoLimpo))
        );
        renderizarListaClientes(filtrados);
    }

    function alternarTodosClientes() {
        const chks = document.querySelectorAll('input[name="cliente_item_chk"]');
        const algumDesmarcado = Array.from(chks).some(c => !c.checked);
        chks.forEach(c => c.checked = algumDesmarcado);
        document.getElementById('btnToggleTodosClientes').textContent = algumDesmarcado ? 'Desmarcar Todos' : 'Marcar Todos';
    }

    function iniciarDisparoEmMassa() {
        if (produtosSelecionadosDisparo.length === 0) {
            alert('Nenhum produto selecionado para o disparo.');
            return;
        }

        const canais = [];
        const statusChecked = document.getElementById('canal_status').checked;
        const whatsappChecked = document.getElementById('canal_whatsapp').checked;
        const emailChecked = document.getElementById('canal_email').checked;

        if (statusChecked) canais.push('status');
        if (whatsappChecked) canais.push('whatsapp');
        if (emailChecked) canais.push('email');

        if (canais.length === 0) {
            alert('Selecione pelo menos um canal de envio.');
            return;
        }

        if ((statusChecked || whatsappChecked) && !whatsappConectadoCache) {
            if (!confirm('⚠️ Atenção: A instância do WhatsApp da sua loja na Evolution API parece estar DESCONECTADA. Deseja continuar mesmo assim?')) {
                return;
            }
        }

        const clientesIds = Array.from(document.querySelectorAll('input[name="cliente_item_chk"]:checked')).map(c => c.value);
        const telefonesManuais = document.getElementById('telefones_manuais').value;
        const emailsManuais = document.getElementById('emails_manuais').value;

        const enquadramentoRadio = document.querySelector('input[name="disparo_enquadramento_foto"]:checked');
        const enquadramentoEscolhido = enquadramentoRadio ? enquadramentoRadio.value : 'auto';
        const rotacaoEscolhida = document.getElementById('disparo_rotacao_foto') ? document.getElementById('disparo_rotacao_foto').value : 'auto';
        const apenasEstoque = document.getElementById('check_apenas_com_estoque') ? (document.getElementById('check_apenas_com_estoque').checked ? 1 : 0) : 1;

        const payload = {
            produtos_ids: produtosSelecionadosDisparo,
            canais: canais,
            clientes_ids: clientesIds,
            telefones_manuais: telefonesManuais,
            emails_manuais: emailsManuais,
            template: document.getElementById('disparo_template').value,
            cor_tema: document.getElementById('disparo_cor_tema').value,
            fundo_estilo: document.getElementById('disparo_fundo_estilo').value,
            enquadramento_foto: enquadramentoEscolhido,
            rotacao_foto: rotacaoEscolhida,
            apenas_com_estoque: apenasEstoque,
            mensagem_card: (document.getElementById('disparo_mensagem_card') ? document.getElementById('disparo_mensagem_card').value.trim() : ''),
            mensagem_texto: document.getElementById('disparo_mensagem_texto').value,
            '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
        };

        document.getElementById('secaoConfigDisparo').classList.add('hidden');
        document.getElementById('secaoProgressoDisparo').classList.remove('hidden');

        fetch('<?= Url::to(['/vendas/disparo/criar']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(async r => {
            const text = await r.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                const textSnippet = text.replace(/<[^>]*>?/gm, '').trim().substring(0, 150);
                throw new Error(textSnippet || 'O servidor retornou uma resposta inválida.');
            }
        })
        .then(data => {
            if (data.success && data.disparo_id) {
                iniciarMonitoramentoStatus(data.disparo_id);
            } else {
                alert('Erro ao criar disparo: ' + (data.message || 'Falha na requisição.'));
                document.getElementById('secaoConfigDisparo').classList.remove('hidden');
                document.getElementById('secaoProgressoDisparo').classList.add('hidden');
            }
        })
        .catch(err => {
            alert('Erro de comunicação: ' + err.message);
            document.getElementById('secaoConfigDisparo').classList.remove('hidden');
            document.getElementById('secaoProgressoDisparo').classList.add('hidden');
        });
    }

    function iniciarMonitoramentoStatus(disparoId) {
        ultimoDisparoIdAtivo = disparoId;
        function checarStatus() {
            fetch('<?= Url::to(['/vendas/disparo/status']) ?>?id=' + disparoId)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('statTotalItens').textContent = data.total_itens;
                    document.getElementById('statItensEnviados').textContent = data.itens_enviados;
                    document.getElementById('statItensErro').textContent = data.itens_erro;

                    const percent = data.progresso_percentual || 0;
                    document.getElementById('barraProgressoDisparo').style.width = percent + '%';

                    if (data.erros && data.erros.length > 0) {
                        const containerErros = document.getElementById('containerErrosDisparo');
                        const listaErros = document.getElementById('listaErrosDisparo');
                        containerErros.classList.remove('hidden');
                        listaErros.innerHTML = data.erros.map(e => `
                            <div class="p-1.5 bg-white border border-red-200 rounded-lg">
                                <span class="font-bold uppercase">[${e.canal}]</span> 
                                <span>${e.destino || 'Geral'}:</span> 
                                <span class="italic text-red-600">${e.erro_mensagem}</span>
                            </div>
                        `).join('');
                    }

                    if (data.status === 'concluido' || percent >= 100) {
                        clearInterval(intervalMonitoramento);
                        document.getElementById('iconeStatusDisparo').textContent = (data.itens_erro === 0) ? '🎉' : '⚠️';
                        document.getElementById('tituloStatusDisparo').textContent = (data.itens_erro === 0) ? 'Disparo em Massa Concluído com Sucesso!' : 'Disparo em Massa Finalizado com Avisos';
                        document.getElementById('subtituloStatusDisparo').textContent = 'Todos os cards e mensagens foram processados pelas filas.';
                        document.getElementById('btnFecharDisparoConcluido').classList.remove('hidden');
                    }
                }
            });
        }

        checarStatus();
        intervalMonitoramento = setInterval(checarStatus, 2500);
    }

    function reenviarErrosDisparo() {
        if (!ultimoDisparoIdAtivo) return;
        const btn = document.getElementById('btnReenviarErros');
        btn.disabled = true;
        btn.innerHTML = '⌛ Reenviando falhas...';
        fetch('<?= Url::to(['/vendas/disparo/reenviar-erros']) ?>?id=' + ultimoDisparoIdAtivo, { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Reenviar Apenas Itens com Falha`;
            if (data.success) {
                document.getElementById('containerErrosDisparo').classList.add('hidden');
                iniciarMonitoramentoStatus(ultimoDisparoIdAtivo);
            } else {
                alert('Erro ao reenviar: ' + (data.message || 'Falha na requisição.'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Reenviar Apenas Itens com Falha`;
            alert('Erro de comunicação: ' + err.message);
        });
    }

    let cancelamentoGeracaoSolicitado = false;
    let timerProgressoInterval = null;
    let tempoInicioGeracao = null;

    function cancelarGeracaoCardsManual() {
        cancelamentoGeracaoSolicitado = true;
        const btn = document.getElementById('btnCancelarGeracaoCards');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '⌛ Cancelando geração...';
        }
        const itemAtual = document.getElementById('labelProgressoItemAtual');
        if (itemAtual) itemAtual.textContent = 'Cancelando e finalizando cards já gerados...';
    }

    async function gerarCardsManual() {
        if (produtosSelecionadosDisparo.length === 0) {
            alert('Nenhum produto selecionado para gerar os cards.');
            return;
        }

        const formatoRadio = document.querySelector('input[name="formato_card_manual"]:checked');
        const formatoEscolhido = formatoRadio ? formatoRadio.value : 'feed';

        const modoMatrizRadio = document.querySelector('input[name="modo_matriz_manual"]:checked');
        const modoMatrizEscolhido = modoMatrizRadio ? modoMatrizRadio.value : 'por_cor';

        const enquadramentoRadio = document.querySelector('input[name="disparo_enquadramento_foto"]:checked');
        const enquadramentoEscolhido = enquadramentoRadio ? enquadramentoRadio.value : 'auto';
        const rotacaoEscolhida = document.getElementById('disparo_rotacao_foto') ? document.getElementById('disparo_rotacao_foto').value : 'auto';
        const apenasEstoque = document.getElementById('check_apenas_com_estoque') ? (document.getElementById('check_apenas_com_estoque').checked ? 1 : 0) : 1;

        const payloadPreparacao = {
            produtos_ids: produtosSelecionadosDisparo,
            formato: formatoEscolhido,
            modo_matriz: modoMatrizEscolhido,
            template: document.getElementById('disparo_template').value,
            cor_tema: document.getElementById('disparo_cor_tema').value,
            fundo_estilo: document.getElementById('disparo_fundo_estilo').value,
            enquadramento_foto: enquadramentoEscolhido,
            rotacao_foto: rotacaoEscolhida,
            apenas_com_estoque: apenasEstoque,
            mensagem_card: (document.getElementById('disparo_mensagem_card') ? document.getElementById('disparo_mensagem_card').value.trim() : ''),
            mensagem_texto: document.getElementById('disparo_mensagem_texto').value,
            '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
        };

        cancelamentoGeracaoSolicitado = false;
        const btnCancelar = document.getElementById('btnCancelarGeracaoCards');
        if (btnCancelar) {
            btnCancelar.disabled = false;
            btnCancelar.innerHTML = '✕ Cancelar Geração';
        }

        document.getElementById('secaoConfigDisparo').classList.add('hidden');
        document.getElementById('secaoProgressoDisparo').classList.add('hidden');
        document.getElementById('secaoResultadoManual').classList.remove('hidden');

        document.getElementById('loadingCardsManual').classList.remove('hidden');
        document.getElementById('conteudoCardsProntos').classList.add('hidden');

        // Resetar UI de progresso
        document.getElementById('tituloProgressoCards').textContent = 'Preparando Geração de Cards...';
        document.getElementById('subtituloProgressoCards').textContent = 'Verificando variações da matriz e espaço disponível.';
        document.getElementById('labelProgressoContador').textContent = 'Iniciando...';
        document.getElementById('labelProgressoPorcentagem').textContent = '0%';
        document.getElementById('barraProgressoCardsManual').style.width = '0%';
        document.getElementById('labelProgressoItemAtual').textContent = 'Consultando itens selecionados...';
        document.getElementById('labelProgressoTempo').textContent = '⏱️ 0s';

        tempoInicioGeracao = Date.now();
        if (timerProgressoInterval) clearInterval(timerProgressoInterval);
        timerProgressoInterval = setInterval(() => {
            if (tempoInicioGeracao) {
                const segundos = Math.floor((Date.now() - tempoInicioGeracao) / 1000);
                const el = document.getElementById('labelProgressoTempo');
                if (el) el.textContent = `⏱️ ${segundos}s`;
            }
        }, 1000);

        try {
            // 1. Chamar endpoint de preparação rápida (< 100ms)
            const respPrep = await fetch('<?= Url::to(['/vendas/disparo/preparar-lote-cards']) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payloadPreparacao)
            });

            const dataPrep = await respPrep.json();

            if (!dataPrep.success || !dataPrep.itens || dataPrep.itens.length === 0) {
                clearInterval(timerProgressoInterval);
                if (dataPrep.stats) atualizarBarraEspacoUI(dataPrep.stats);
                alert('Aviso: ' + (dataPrep.message || 'Nenhum item válido para gerar cards.'));
                voltarParaConfigCardsManual();
                return;
            }

            const totalItens = dataPrep.itens.length;
            document.getElementById('tituloProgressoCards').textContent = 'Renderizando Cards em Alta Resolução...';
            document.getElementById('subtituloProgressoCards').textContent = `Total de ${totalItens} card(s) a ser(em) gerado(s).`;
            
            const cardsGeradosSucesso = [];
            const idsGeradosParaZip = [];
            let limiteAtingidoAviso = false;

            // 2. Processar itens progressivamente um a um (cada um leva ~2 segundos)
            for (let i = 0; i < totalItens; i++) {
                if (cancelamentoGeracaoSolicitado) {
                    break;
                }

                const item = dataPrep.itens[i];
                const numeroAtual = i + 1;
                const percentual = Math.round((i / totalItens) * 100);

                document.getElementById('labelProgressoContador').textContent = `Card ${numeroAtual} de ${totalItens}`;
                document.getElementById('labelProgressoPorcentagem').textContent = `${percentual}%`;
                document.getElementById('barraProgressoCardsManual').style.width = `${percentual}%`;
                document.getElementById('labelProgressoItemAtual').textContent = `🎨 Renderizando: ${item.nome} (${item.formato_label})...`;

                try {
                    const respItem = await fetch('<?= Url::to(['/vendas/disparo/gerar-card-item']) ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(item)
                    });

                    const dataItem = await respItem.json();

                    if (dataItem.success && dataItem.card) {
                        cardsGeradosSucesso.push(dataItem.card);
                        idsGeradosParaZip.push(dataItem.card.id);
                        if (dataItem.stats) {
                            atualizarBarraEspacoUI(dataItem.stats);
                        }
                    } else if (dataItem.limite_atingido) {
                        limiteAtingidoAviso = true;
                        if (dataItem.stats) atualizarBarraEspacoUI(dataItem.stats);
                        break;
                    } else {
                        console.warn('Falha ao gerar card do item ' + item.nome, dataItem.message);
                    }
                } catch (errItem) {
                    console.error('Erro de requisição no item ' + item.nome, errItem);
                }
            }

            clearInterval(timerProgressoInterval);

            // Atualiza para 100% no progresso
            document.getElementById('barraProgressoCardsManual').style.width = '100%';
            document.getElementById('labelProgressoPorcentagem').textContent = '100%';

            if (cardsGeradosSucesso.length === 0) {
                alert('Não foi possível gerar nenhum card promocional. Verifique as fotos dos produtos selecionados ou espaço em disco.');
                voltarParaConfigCardsManual();
                return;
            }

            // 3. Finalizar e criar pacote ZIP com os cards gerados
            document.getElementById('labelProgressoItemAtual').textContent = 'Empacotando arquivo compactado ZIP...';
            let zipUrl = null;
            let zipTamanho = '';

            if (idsGeradosParaZip.length > 0) {
                try {
                    const respZip = await fetch('<?= Url::to(['/vendas/disparo/finalizar-lote-zip']) ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ cards_ids: idsGeradosParaZip })
                    });
                    const dataZip = await respZip.json();
                    if (dataZip.success) {
                        zipUrl = dataZip.zip_url;
                        zipTamanho = dataZip.zip_tamanho || '';
                        if (dataZip.stats) atualizarBarraEspacoUI(dataZip.stats);
                    }
                } catch (eZip) {
                    console.error('Erro ao empacotar ZIP:', eZip);
                }
            }

            // 4. Exibir tela de resultados
            document.getElementById('loadingCardsManual').classList.add('hidden');
            document.getElementById('conteudoCardsProntos').classList.remove('hidden');

            const tituloSucesso = cancelamentoGeracaoSolicitado 
                ? 'Geração Interrompida (Cards Parciais Prontos)'
                : (limiteAtingidoAviso ? 'Cards Gerados até o Limite de 50 MB' : `${cardsGeradosSucesso.length} Card(s) Gerado(s) com Sucesso!`);

            document.getElementById('tituloSucessoCards').textContent = tituloSucesso;
            document.getElementById('badgeContagemCards').textContent = cardsGeradosSucesso.length + ' card(s) pronto(s)';

            const btnZip = document.getElementById('btnBaixarZipTopo');
            const badgeTamanhoZip = document.getElementById('badgeTamanhoZip');
            if (zipUrl) {
                btnZip.href = zipUrl;
                btnZip.classList.remove('hidden');
                badgeTamanhoZip.textContent = zipTamanho ? '(' + zipTamanho + ')' : '';
                btnZip.onclick = function() {
                    setTimeout(() => { carregarStatusEspaco(); }, 2500);
                };
            } else {
                btnZip.classList.add('hidden');
            }

            if (limiteAtingidoAviso) {
                alert('⚠️ Atenção: A cota máxima de 50 MB de armazenamento para cards foi atingida. Baixe ou exclua cards para liberar espaço.');
            }

            renderizarGradeCardsGerados(cardsGeradosSucesso);
            carregarStatusEspaco();

        } catch (errGeral) {
            clearInterval(timerProgressoInterval);
            alert('Erro de comunicação durante a geração dos cards: ' + errGeral.message);
            voltarParaConfigCardsManual();
        }
    }

    function renderizarGradeCardsGerados(cards) {
        const container = document.getElementById('gradeCardsGerados');
        if (!cards || cards.length === 0) {
            container.innerHTML = '<div class="text-xs text-gray-500 text-center py-6 col-span-2">Nenhum card foi gerado.</div>';
            return;
        }

        container.innerHTML = cards.map(c => {
            const formatoClass = c.formato === 'stories' 
                ? 'bg-indigo-100 text-indigo-800' 
                : 'bg-emerald-100 text-emerald-800';

            const msgCodificada = encodeURIComponent(c.mensagem_texto || '');

            const badgePromo = c.mensagem_card ? `
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1" title="Mensagem Promocional no Card">
                    <span>⚡</span>
                    <span>${escapeHtml(c.mensagem_card)}</span>
                </span>
            ` : '';

            const gradeContagem = c.grade_tamanhos && c.grade_tamanhos.length ? c.grade_tamanhos.length : 0;
            const badgeMatriz = c.eh_matriz ? `
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-900 border border-amber-200 inline-flex items-center gap-1" title="Variação da Matriz">
                    <span>🧩 Matriz</span>
                    ${c.cor ? `<span>• Cor: ${escapeHtml(c.cor)}</span>` : ''}
                    ${gradeContagem > 0 ? `<span class="bg-amber-200/90 text-amber-950 px-1 rounded font-bold">${gradeContagem} tam.</span>` : (c.tamanho_grade ? `<span>• Tam: ${escapeHtml(c.tamanho_grade)}</span>` : '')}
                </span>
            ` : '';

            const gradeTamanhosTexto = gradeContagem > 0 ? `
                <div class="mt-1 flex flex-wrap gap-1 items-center">
                    <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">Tamanhos:</span>
                    ${c.grade_tamanhos.map(g => `<span class="text-[10px] font-semibold px-1 py-0.2 bg-purple-50 text-purple-700 border border-purple-200/60 rounded">${escapeHtml(g.tamanho)}${!c.mesmo_preco && g.preco ? ': ' + escapeHtml(g.preco) : ''}</span>`).join('')}
                </div>
            ` : '';

            return `
                <div id="card_item_${c.id}" class="bg-gray-50 border border-gray-200 hover:border-purple-300 rounded-2xl p-3 flex flex-col justify-between transition-all shadow-sm hover:shadow-md relative">
                    <div class="flex gap-3 items-start">
                        <div class="pt-1">
                            <input type="checkbox" name="card_item_chk" value="${c.id}" onchange="atualizarSelecaoCardsManual()" class="rounded text-purple-600 focus:ring-purple-500 w-4 h-4 cursor-pointer">
                        </div>
                        <a href="${c.card_url}" target="_blank" class="w-20 h-20 rounded-xl overflow-hidden bg-black/5 flex-shrink-0 border border-gray-200 relative group cursor-pointer block" title="Clique para ampliar">
                            <img src="${c.card_url}" alt="${escapeHtml(c.produto_nome)}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[11px] font-bold">Ver</div>
                        </a>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 mb-1 flex-wrap">
                                <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded ${formatoClass}">${c.formato_label}</span>
                                ${c.peso_arquivo ? `<span class="text-[10px] text-gray-400 font-medium">${escapeHtml(c.peso_arquivo)}</span>` : ''}
                                ${badgePromo}
                                ${badgeMatriz}
                            </div>
                            <h5 class="text-xs font-bold text-gray-900 truncate" title="${escapeHtml(c.produto_nome)}">${escapeHtml(c.produto_nome)}</h5>
                            ${gradeTamanhosTexto}
                            <p class="text-[11px] text-gray-500 line-clamp-2 mt-1.5 leading-snug font-mono bg-white p-1.5 rounded-lg border border-gray-200">${escapeHtml(c.mensagem_texto)}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 mt-3 pt-3 border-t border-gray-200/80">
                        <button type="button" id="btnBaixarCard_${c.id}" onclick="baixarCardIndividual(this, '${c.id}', '${c.download_url}', '${escapeHtml(c.nome_arquivo || 'card.webp')}')" class="py-1.5 px-2 bg-white hover:bg-gray-100 border border-gray-300 text-gray-700 text-xs font-bold rounded-xl text-center transition flex items-center justify-center gap-1" title="Baixar card e liberar do servidor">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span>Baixar</span>
                        </button>
                        <a href="${c.whatsapp_link}" target="_blank" class="py-1.5 px-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl text-center transition flex items-center justify-center gap-1 shadow-sm" title="Enviar no WhatsApp">
                            <span>💬</span>
                            WhatsApp
                        </a>
                        <button type="button" onclick="copiarTextoCardPromo(this, decodeURIComponent('${msgCodificada}'))" class="py-1.5 px-2 bg-white hover:bg-gray-100 border border-gray-300 text-gray-700 text-xs font-bold rounded-xl text-center transition flex items-center justify-center gap-1" title="Copiar texto da oferta">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                            Copiar
                        </button>
                    </div>
                </div>
            `;
        }).join('');
        atualizarSelecaoCardsManual();
    }

    function carregarStatusEspaco() {
        fetch('<?= Url::to(['/vendas/disparo/status-espaco']) ?>')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.stats) {
                atualizarBarraEspacoUI(data.stats);
            }
        })
        .catch(e => console.error('Erro ao buscar status de espaço:', e));
    }

    function atualizarBarraEspacoUI(stats) {
        const badgePorc = document.getElementById('badgePorcentagemEspaco');
        const barra = document.getElementById('barraProgressoEspaco');
        const textoConsumo = document.getElementById('textoConsumoEspaco');
        const textoDetalhes = document.getElementById('textoDetalheCardsSalvos');

        if (!badgePorc || !barra) return;

        const porc = stats.porcentagem || 0;
        badgePorc.textContent = porc + '%';
        barra.style.width = Math.min(porc, 100) + '%';

        if (porc >= 90) {
            badgePorc.className = 'px-2 py-0.5 rounded-full text-[10px] font-black bg-red-100 text-red-800 transition-colors animate-pulse';
            barra.className = 'bg-red-600 h-1.5 transition-all duration-500 rounded-full';
        } else if (porc >= 70) {
            badgePorc.className = 'px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 transition-colors';
            barra.className = 'bg-amber-500 h-1.5 transition-all duration-500 rounded-full';
        } else {
            badgePorc.className = 'px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 transition-colors';
            barra.className = 'bg-emerald-500 h-1.5 transition-all duration-500 rounded-full';
        }

        textoConsumo.textContent = `${stats.usado_mb} MB de ${stats.limite_mb} MB utilizados (${porc}%)`;
        if (textoDetalhes) {
            textoDetalhes.textContent = `${stats.total_cards || 0} card(s) armazenado(s)`;
        }
    }

    function baixarCardIndividual(btn, cardId, downloadUrl, nomeArquivo) {
        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="text-[10px] animate-pulse">Baixando...</span>';

        // Cria link oculto para download
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = nomeArquivo;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // O backend exclui o arquivo e registro no Response::EVENT_AFTER_SEND
        setTimeout(() => {
            btn.innerHTML = '<span class="text-emerald-700">✓ Baixado</span>';
            btn.className = 'py-1.5 px-2 bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-extrabold rounded-xl text-center transition flex items-center justify-center gap-1';
            btn.title = 'Card baixado e excluído do servidor para liberar espaço.';
            
            // Adicionar badge no card informando liberação
            const cardItemEl = document.getElementById('card_item_' + cardId);
            if (cardItemEl) {
                const badgeLiberado = document.createElement('div');
                badgeLiberado.className = 'text-[10px] font-bold text-emerald-700 bg-emerald-100/80 px-1.5 py-0.5 rounded mt-1 text-center';
                badgeLiberado.textContent = '✓ Liberado do disco';
                const infoCol = cardItemEl.querySelector('.min-w-0');
                if (infoCol) infoCol.appendChild(badgeLiberado);
            }

            // Atualiza a barra de espaço após liberação
            carregarStatusEspaco();
        }, 1500);
    }

    function atualizarSelecaoCardsManual() {
        const chks = document.querySelectorAll('input[name="card_item_chk"]:checked');
        const btnExcluir = document.getElementById('btnExcluirSelecionadosCards');
        const badgeCont = document.getElementById('contagemSelecionadosCards');
        
        if (badgeCont) badgeCont.textContent = chks.length;

        if (btnExcluir) {
            if (chks.length > 0) {
                btnExcluir.disabled = false;
                btnExcluir.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                btnExcluir.disabled = true;
                btnExcluir.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    function alternarTodosCardsManual() {
        const chks = document.querySelectorAll('input[name="card_item_chk"]');
        const algumDesmarcado = Array.from(chks).some(c => !c.checked);
        chks.forEach(c => c.checked = algumDesmarcado);
        const btnToggle = document.getElementById('btnToggleTodosCards');
        if (btnToggle) btnToggle.textContent = algumDesmarcado ? 'Desmarcar Todos' : 'Marcar Todos';
        atualizarSelecaoCardsManual();
    }

    function excluirCardsSelecionados() {
        const chks = document.querySelectorAll('input[name="card_item_chk"]:checked');
        const ids = Array.from(chks).map(c => c.value);
        if (ids.length === 0) return;

        if (!confirm(`Deseja realmente excluir ${ids.length} card(s) selecionado(s)? Os arquivos serão excluídos do servidor imediatamente.`)) {
            return;
        }

        const btn = document.getElementById('btnExcluirSelecionadosCards');
        btn.disabled = true;
        btn.innerHTML = '⌛ Excluindo...';

        fetch('<?= Url::to(['/vendas/disparo/excluir-cards']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ ids: ids })
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg> <span>Excluir Seleção (<span id="contagemSelecionadosCards">0</span>)</span>`;
            if (data.success) {
                // Remove os cards excluídos do DOM
                ids.forEach(id => {
                    const el = document.getElementById('card_item_' + id);
                    if (el) el.remove();
                });
                atualizarSelecaoCardsManual();
                if (data.stats) {
                    atualizarBarraEspacoUI(data.stats);
                } else {
                    carregarStatusEspaco();
                }
                // Atualizar contagem restante
                const restantes = document.querySelectorAll('input[name="card_item_chk"]').length;
                document.getElementById('badgeContagemCards').textContent = restantes + ' card(s) restante(s)';
                if (restantes === 0) {
                    document.getElementById('gradeCardsGerados').innerHTML = '<div class="text-xs text-gray-500 text-center py-6 col-span-2">Todos os cards selecionados foram excluídos do servidor.</div>';
                    document.getElementById('btnBaixarZipTopo').classList.add('hidden');
                }
            } else {
                alert('Erro ao excluir cards: ' + (data.message || 'Falha na requisição.'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            alert('Erro de comunicação: ' + err.message);
        });
    }

    function excluirTodosCards() {
        if (!confirm('⚠️ Tem certeza que deseja excluir TODOS os cards promocionais gerados desta loja? Esta ação apagará os arquivos físicos do servidor e liberará o limite de 50 MB.')) {
            return;
        }

        fetch('<?= Url::to(['/vendas/disparo/excluir-cards']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ todos: true })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('gradeCardsGerados').innerHTML = '<div class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center col-span-2 font-bold">✨ Todos os cards foram excluídos e o armazenamento foi 100% liberado.</div>';
                document.getElementById('badgeContagemCards').textContent = '0 cards';
                const btnZip = document.getElementById('btnBaixarZipTopo');
                if (btnZip) btnZip.classList.add('hidden');
                atualizarSelecaoCardsManual();
                if (data.stats) {
                    atualizarBarraEspacoUI(data.stats);
                } else {
                    carregarStatusEspaco();
                }
            } else {
                alert('Erro ao excluir cards: ' + (data.message || 'Falha na requisição.'));
            }
        })
        .catch(err => alert('Erro de comunicação: ' + err.message));
    }

    function copiarTextoCardPromo(btn, texto) {
        if (!texto) return;
        const textoOriginal = btn.innerHTML;

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(texto).then(() => {
                mostrarFeedbackCopiado(btn, textoOriginal);
            }).catch(() => fallbackCopiarTexto(btn, texto, textoOriginal));
        } else {
            fallbackCopiarTexto(btn, texto, textoOriginal);
        }
    }

    function fallbackCopiarTexto(btn, texto, textoOriginal) {
        const area = document.createElement('textarea');
        area.value = texto;
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.focus();
        area.select();
        try {
            document.execCommand('copy');
            mostrarFeedbackCopiado(btn, textoOriginal);
        } catch (err) {
            alert('Não foi possível copiar o texto automaticamente.');
        }
        document.body.removeChild(area);
    }

    function mostrarFeedbackCopiado(btn, textoOriginal) {
        btn.innerHTML = '<span class="text-emerald-600 font-extrabold text-xs">Copiado! ✓</span>';
        setTimeout(() => {
            btn.innerHTML = textoOriginal;
        }, 2000);
    }

    function voltarParaConfigCardsManual() {
        document.getElementById('secaoResultadoManual').classList.add('hidden');
        document.getElementById('secaoConfigDisparo').classList.remove('hidden');
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
</script>
