<?php

use yii\helpers\Url;
?>

<!-- Modal de Configuração e Geração do Encarte Digital (Flipsnack) -->
<div id="modalGerarEncarte" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-70 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full overflow-hidden transform transition-all border border-gray-100 flex flex-col max-h-[92vh]">
        
        <!-- Header do Modal -->
        <div class="bg-gradient-to-r from-red-600 via-amber-600 to-red-700 px-6 py-5 text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-white/20 backdrop-blur-md rounded-2xl border border-white/30">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <div>
                    <h3 class="text-xl font-extrabold tracking-tight">Gerar Encarte Digital (Estilo Flipsnack)</h3>
                    <p class="text-xs text-red-100 font-medium">Crie um folheto promocional interativo público para WhatsApp, PDF e Redes Sociais</p>
                </div>
            </div>
            <button onclick="fecharModalGerarEncarte()" class="text-red-100 hover:text-white hover:bg-white/10 p-2 rounded-xl transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Conteúdo do Modal -->
        <div class="p-6 space-y-6 overflow-y-auto flex-1">

            <!-- Seletor do Modo de Origem de Produtos (Lote / Categoria / Quantidade / Todos) -->
            <div class="bg-slate-50 border border-slate-200 p-4 rounded-2xl space-y-3">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">📦 Origem dos Produtos para o Encarte:</label>
                    <span class="text-[11px] text-slate-500 font-medium">Selecione o critério de inclusão dos produtos</span>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Opção 1: Selecionados na Página -->
                    <label onclick="alterarModoOrigemProdutos('PAGINA')" class="cursor-pointer border-2 border-amber-400 bg-amber-50/60 p-3 rounded-xl flex items-start gap-2.5 hover:shadow-md transition item-modo-origem" id="cardModoPagina">
                        <input type="radio" name="modo_origem_prod" value="PAGINA" checked class="mt-0.5 text-red-600 focus:ring-red-500">
                        <div class="min-w-0 flex-1">
                            <div class="font-extrabold text-xs text-slate-900 truncate">Itens Marcados</div>
                            <div class="text-[11px] text-slate-600 font-semibold mt-0.5"><span id="badgeQtdEncarte">0</span> produto(s)</div>
                        </div>
                    </label>

                    <!-- Opção 2: Por Categoria -->
                    <label onclick="alterarModoOrigemProdutos('CATEGORIA')" class="cursor-pointer border-2 border-slate-200 bg-white p-3 rounded-xl flex items-start gap-2.5 hover:shadow-md transition item-modo-origem" id="cardModoCategoria">
                        <input type="radio" name="modo_origem_prod" value="CATEGORIA" class="mt-0.5 text-red-600 focus:ring-red-500">
                        <div class="min-w-0 flex-1">
                            <div class="font-extrabold text-xs text-slate-900 truncate">Por Categoria</div>
                            <div class="text-[11px] text-indigo-600 font-semibold mt-0.5 truncate" id="badgeCatSelecionada">Escolher Categoria</div>
                        </div>
                    </label>

                    <!-- Opção 3: Quantidade Personalizada -->
                    <label onclick="alterarModoOrigemProdutos('QUANTIDADE')" class="cursor-pointer border-2 border-slate-200 bg-white p-3 rounded-xl flex items-start gap-2.5 hover:shadow-md transition item-modo-origem" id="cardModoQuantidade">
                        <input type="radio" name="modo_origem_prod" value="QUANTIDADE" class="mt-0.5 text-red-600 focus:ring-red-500">
                        <div class="min-w-0 flex-1">
                            <div class="font-extrabold text-xs text-slate-900 truncate">Qtd Personalizada</div>
                            <div class="text-[11px] text-slate-500 font-medium mt-0.5">Digitar quantos</div>
                        </div>
                    </label>

                    <!-- Opção 4: Todos os Produtos do Catálogo -->
                    <label onclick="alterarModoOrigemProdutos('TODOS')" class="cursor-pointer border-2 border-slate-200 bg-white p-3 rounded-xl flex items-start gap-2.5 hover:shadow-md transition item-modo-origem" id="cardModoTodos">
                        <input type="radio" name="modo_origem_prod" value="TODOS" class="mt-0.5 text-red-600 focus:ring-red-500">
                        <div class="min-w-0 flex-1">
                            <div class="font-extrabold text-xs text-slate-900 truncate">TODOS os Produtos</div>
                            <div class="text-[11px] text-slate-500 font-medium mt-0.5">Catálogo Completo</div>
                        </div>
                    </label>
                </div>

                <!-- Painel Expansível de Filtro por Categoria -->
                <div id="painelCategoriaEncarte" class="hidden pt-3 border-t border-slate-200 space-y-3 bg-indigo-50/40 p-3.5 rounded-xl border border-indigo-100">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
                        <div>
                            <label class="block text-xs font-bold text-indigo-900 uppercase mb-1">📁 Categoria de Produtos:</label>
                            <select id="selectCategoriaEncarte" onchange="aoMudarCategoriaEncarte()" class="w-full px-3 py-2 bg-white border border-indigo-200 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
                                <option value="TODAS">Carregando categorias...</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-indigo-900 uppercase mb-1">🎯 Escopo da Categoria:</label>
                            <div class="flex items-center gap-2 bg-white p-1 rounded-xl border border-indigo-200 text-xs">
                                <label class="flex-1 flex items-center justify-center gap-1.5 py-1 px-2 rounded-lg cursor-pointer transition font-bold text-slate-700 bg-indigo-100/70" id="labelEscopoCatTodos">
                                    <input type="radio" name="escopo_categoria" value="TODOS" checked onchange="alterarEscopoCategoria('TODOS')" class="sr-only">
                                    <span>Todos da Categoria</span>
                                </label>
                                <label class="flex-1 flex items-center justify-center gap-1.5 py-1 px-2 rounded-lg cursor-pointer transition font-bold text-slate-500 hover:bg-slate-50" id="labelEscopoCatQtd">
                                    <input type="radio" name="escopo_categoria" value="QUANTIDADE" onchange="alterarEscopoCategoria('QUANTIDADE')" class="sr-only">
                                    <span>Limitar Quantidade</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Sub-painel de Limite de Quantidade por Categoria -->
                    <div id="subpainelQtdCategoria" class="hidden pt-2 border-t border-indigo-100/80 space-y-2">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <span class="text-xs font-bold text-indigo-900">Quantos produtos incluir desta categoria:</span>
                            <div class="flex items-center gap-1">
                                <span class="text-[10px] text-slate-500 font-semibold">Atalhos:</span>
                                <button type="button" onclick="definirQtdCatAtalho(4)" class="px-2 py-0.5 bg-indigo-100 hover:bg-indigo-200 rounded font-bold text-[10px] text-indigo-900">4</button>
                                <button type="button" onclick="definirQtdCatAtalho(6)" class="px-2 py-0.5 bg-indigo-100 hover:bg-indigo-200 rounded font-bold text-[10px] text-indigo-900">6</button>
                                <button type="button" onclick="definirQtdCatAtalho(8)" class="px-2 py-0.5 bg-indigo-100 hover:bg-indigo-200 rounded font-bold text-[10px] text-indigo-900">8</button>
                                <button type="button" onclick="definirQtdCatAtalho(12)" class="px-2 py-0.5 bg-indigo-100 hover:bg-indigo-200 rounded font-bold text-[10px] text-indigo-900">12</button>
                                <button type="button" onclick="definirQtdCatAtalho(18)" class="px-2 py-0.5 bg-indigo-100 hover:bg-indigo-200 rounded font-bold text-[10px] text-indigo-900">18</button>
                                <button type="button" onclick="definirQtdCatAtalho(24)" class="px-2 py-0.5 bg-indigo-100 hover:bg-indigo-200 rounded font-bold text-[10px] text-indigo-900">24</button>
                            </div>
                        </div>
                        <input type="number" id="inputQtdDesejadaCat" min="1" max="500" value="6" oninput="aoMudarCategoriaEncarte()" class="w-full px-3 py-2 bg-white border border-indigo-200 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>

                <!-- Painel Expansível de Quantidade Personalizada Geral -->
                <div id="painelQtdPersonalizada" class="hidden pt-2 border-t border-slate-200 space-y-2">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <label class="block text-xs font-bold text-slate-700">Informe Quantos Produtos Incluir no Encarte:</label>
                        <div class="flex items-center gap-1">
                            <span class="text-[10px] text-slate-500 font-semibold">Atalhos rápidos:</span>
                            <button type="button" onclick="definirQtdAtalho(6)" class="px-2 py-0.5 bg-slate-200 hover:bg-slate-300 rounded font-bold text-[10px] text-slate-800">6</button>
                            <button type="button" onclick="definirQtdAtalho(12)" class="px-2 py-0.5 bg-slate-200 hover:bg-slate-300 rounded font-bold text-[10px] text-slate-800">12</button>
                            <button type="button" onclick="definirQtdAtalho(24)" class="px-2 py-0.5 bg-slate-200 hover:bg-slate-300 rounded font-bold text-[10px] text-slate-800">24</button>
                            <button type="button" onclick="definirQtdAtalho(50)" class="px-2 py-0.5 bg-slate-200 hover:bg-slate-300 rounded font-bold text-[10px] text-slate-800">50</button>
                            <button type="button" onclick="definirQtdAtalho(100)" class="px-2 py-0.5 bg-slate-200 hover:bg-slate-300 rounded font-bold text-[10px] text-slate-800">100</button>
                        </div>
                    </div>
                    <input type="number" id="inputQtdDesejadaProd" min="1" max="500" value="12" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:ring-2 focus:ring-red-500 focus:outline-none">
                </div>
            </div>

            <!-- Seletor do Filtro de Mídia (Com Foto / Sem Foto / Todos) -->
            <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-indigo-50 border border-emerald-200/80 p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
                <div>
                    <label class="block text-xs font-extrabold text-emerald-950 uppercase tracking-wider flex items-center gap-1.5">
                        <span>📸</span>
                        <span>Filtro de Mídia (Fotos):</span>
                    </label>
                    <p class="text-[11px] text-emerald-800 font-medium">Defina se deseja incluir apenas produtos com fotos no encarte</p>
                </div>
                
                <div class="flex items-center gap-1 bg-white p-1 rounded-xl border border-emerald-300 shadow-sm text-xs">
                    <button type="button" onclick="alterarFiltroFotoEncarte('COM_FOTO')" id="btnFiltroFotoCom" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-extrabold text-xs transition bg-emerald-600 text-white shadow-sm cursor-pointer">
                        <span>📸</span>
                        <span>Com Foto (Recomendado)</span>
                    </button>
                    <button type="button" onclick="alterarFiltroFotoEncarte('SEM_FOTO')" id="btnFiltroFotoSem" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-bold text-xs transition text-slate-600 hover:bg-slate-100 cursor-pointer">
                        <span>⚠️</span>
                        <span>Sem Foto</span>
                    </button>
                    <button type="button" onclick="alterarFiltroFotoEncarte('TODOS')" id="btnFiltroFotoTodos" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-bold text-xs transition text-slate-600 hover:bg-slate-100 cursor-pointer">
                        <span>📋</span>
                        <span>Todos</span>
                    </button>
                </div>
            </div>

            <!-- Formulário de Configuração -->
            <div class="space-y-5">
                
                <!-- 1. Título e Subtítulo -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Título do Encarte</label>
                        <input type="text" id="encarte_titulo" value="OFERTA IMBATÍVEL DA SEMANA" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Subtítulo / Período de Validade</label>
                        <input type="text" id="encarte_subtitulo" value="Ofertas válidas enquanto durarem os estoques!" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-red-500">
                    </div>
                </div>

                <!-- 2. Tema e Layout -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-b border-gray-100 py-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Estilo Visual do Tema</label>
                        <select id="encarte_cor_tema" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-medium focus:ring-2 focus:ring-red-500">
                            <option value="red_gold">Supermercado Clássico (Vermelho / Ouro)</option>
                            <option value="emerald_fresh">Hortifruti / Fresh (Verde Esmeralda)</option>
                            <option value="ocean_blue">Varejo Premium (Azul Oceano)</option>
                            <option value="dark_vip">Vip Club (Dark / Gold)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Produtos por Lâmina (Página)</label>
                        <select id="encarte_ppp" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-medium focus:ring-2 focus:ring-red-500">
                            <option value="4">4 Produtos por Página (Grande Destaque)</option>
                            <option value="6" selected>6 Produtos por Página (Recomendado)</option>
                            <option value="8">8 Produtos por Página (Compacto)</option>
                            <option value="12">12 Produtos por Página (Grade Densada)</option>
                            <option value="15">15 Produtos por Página (Grade 5x3)</option>
                            <option value="18">18 Produtos por Página (Grade Max 6x3)</option>
                        </select>
                    </div>
                </div>

                <!-- 3. Personalização Opcional de Tags por Produto -->
                <div class="border-t border-gray-100 pt-3 space-y-2">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                        <label class="block text-xs font-bold text-gray-700 uppercase">🏷️ Tags dos Produtos (Opcional)</label>
                        <span class="text-[10px] text-gray-500 font-medium">Deixe em 'Automático' para usar o padrão</span>
                    </div>

                    <!-- Campo de Busca/Filtro Rápido de Produtos nas Tags -->
                    <div class="relative">
                        <input type="text" id="inputBuscaTagsModal" oninput="filtrarTagsProdutosModal()" placeholder="🔍 Consultar produto por nome para aplicar tag..." class="w-full px-3 py-2 bg-white border border-gray-300 rounded-xl text-xs font-medium text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 focus:outline-none shadow-sm transition">
                    </div>

                    <div id="containerTagsProdutos" class="max-h-48 overflow-y-auto space-y-1.5 p-2.5 bg-gray-50 rounded-xl border border-gray-200 text-xs">
                        <div class="text-gray-400 italic text-[11px] text-center py-2">Carregando itens...</div>
                    </div>
                </div>

                <!-- Opção de Inativar Encartes Anteriores -->
                <div class="bg-amber-50 border border-amber-200 p-3.5 rounded-2xl flex items-center justify-between gap-3 shadow-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="text-xl">🔄</span>
                        <div>
                            <div class="text-xs font-black text-amber-950">Inativar Encartes Anteriores</div>
                            <div class="text-[10px] text-amber-800 font-medium">Clientes em links antigos serão avisados e redirecionados para esta nova edição</div>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="checkInativarAnteriores" checked class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                    </label>
                </div>

                <!-- Opções de Ação Rápida (Gerar / Copiar Link / PDF) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button type="button" onclick="gerarLinkEncartePublico()" class="w-full py-3.5 px-4 bg-gradient-to-r from-red-600 to-amber-600 hover:from-red-700 hover:to-amber-700 text-white font-extrabold rounded-2xl transition shadow-lg flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        Gerar e Abrir Link Público (Flipsnack)
                    </button>

                    <button type="button" onclick="baixarPdfEncarteDirect()" class="w-full py-3.5 px-4 bg-slate-800 hover:bg-slate-900 text-white font-extrabold rounded-2xl transition shadow-lg flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Baixar PDF do Encarte
                    </button>
                </div>

                <!-- Seção Histórico e Gestão de Status dos Encartes -->
                <div class="border-t border-slate-200 pt-4">
                    <button type="button" onclick="toggleHistoricoEncartes()" class="w-full py-2.5 px-4 bg-slate-100 hover:bg-slate-200 rounded-xl text-xs font-bold text-slate-800 flex items-center justify-between transition cursor-pointer border border-slate-300">
                        <span class="flex items-center gap-2">
                            <span>📚</span>
                            <span>Histórico de Encartes &amp; Gestão de Status (Ativar / Inativar)</span>
                        </span>
                        <span id="setaHistoricoEncartes" class="text-slate-500 text-xs transition-transform duration-200">▼</span>
                    </button>
                    <div id="painelHistoricoEncartes" class="hidden mt-3 space-y-2 max-h-60 overflow-y-auto p-1 bg-slate-50 rounded-xl border border-slate-200">
                        <div class="text-center py-4 text-xs text-slate-400 font-semibold">Carregando encartes...</div>
                    </div>
                </div>

                <!-- Seção Resultado do Link Gerado -->
                <div id="boxResultadoEncarte" class="hidden bg-gray-50 border border-gray-200 p-4 rounded-2xl space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-700 uppercase">🔗 Link Público Gerado:</span>
                        <span class="text-xs text-green-600 font-bold" id="statusCopiadoLink"></span>
                    </div>
                    <div class="flex gap-2">
                        <input type="text" id="inputUrlEncarteGerado" readonly class="w-full px-3 py-2 bg-white border border-gray-300 rounded-xl text-xs font-mono text-gray-800">
                        <button onclick="copiarLinkEncarte()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl transition">
                            Copiar Link
                        </button>
                    </div>
                </div>

                <!-- Seção Evolution API (Envio via WhatsApp) -->
                <div class="border-t border-gray-100 pt-5 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <h4 class="font-extrabold text-sm text-gray-900 flex items-center gap-2">
                            <span class="p-1.5 bg-green-100 text-green-700 rounded-lg">💬</span>
                            Disparar Link + PDF via WhatsApp (Evolution API)
                        </h4>
                        <span id="badgeContadorTelefones" class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-full border border-slate-200">
                            📊 0 número(s) selecionado(s)
                        </span>
                    </div>

                    <!-- Controles para Carregar Clientes da Base e Limpeza -->
                    <div class="bg-slate-50 border border-slate-200 p-3 rounded-2xl space-y-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-bold text-slate-700 uppercase">👥 Clientes da Base (Lote Seguro):</span>
                                <div class="inline-flex rounded-xl shadow-sm border border-slate-300 bg-white overflow-hidden text-xs">
                                    <button type="button" onclick="carregarClientesBaseZap(5)" class="px-2.5 py-1.5 hover:bg-slate-100 border-r border-slate-200 font-extrabold text-slate-800 transition">5</button>
                                    <button type="button" onclick="carregarClientesBaseZap(10)" class="px-2.5 py-1.5 hover:bg-slate-100 border-r border-slate-200 font-extrabold text-slate-800 transition">10</button>
                                    <button type="button" onclick="carregarClientesBaseZap(20)" class="px-2.5 py-1.5 hover:bg-slate-100 border-r border-slate-200 font-extrabold text-green-700 transition">20 (Recomendado)</button>
                                    <button type="button" onclick="carregarClientesBaseZap(50)" class="px-2.5 py-1.5 hover:bg-slate-100 font-extrabold text-slate-800 transition">50</button>
                                </div>
                            </div>
                            <button type="button" onclick="limparTelefonesEncarte()" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-bold text-xs rounded-xl transition flex items-center gap-1">
                                🗑️ Limpar Lista
                            </button>
                        </div>
                        <p class="text-[10px] text-slate-500 font-medium italic">
                            * Seleciona clientes aleatórios da sua base com pausas automáticas entre envios para evitar bloqueios no WhatsApp.
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Telefones / Contatos de Destino (Inclusão manual ou via base):</label>
                        <textarea id="encarte_telefones" oninput="atualizarContadorTelefones()" rows="3" class="w-full p-2.5 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-red-500" placeholder="Cole ou digite números separados por vírgula ou linha (ex: 81999998888, 81988887777 ou 5581999998888 # Nome Cliente)"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Mensagem de Acompanhamento</label>
                        <textarea id="encarte_mensagem" rows="2" class="w-full p-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-red-500">🔥 *CONFIRA NOSSO NOVO ENCARTE DE OFERTAS!* 🔥

Aproveite nossos preços especiais válidos esta semana!</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button type="button" id="btnEnviarEncarteWp" onclick="dispararEncarteEvolution()" class="w-full py-3.5 bg-green-600 hover:bg-green-700 text-white font-extrabold rounded-2xl transition shadow-lg flex items-center justify-center gap-2 text-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            Disparar Contatos (Link + PDF)
                        </button>

                        <button type="button" id="btnPostarStatusWp" onclick="postarEncarteStatusEvolution()" class="w-full py-3.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-extrabold rounded-2xl transition shadow-lg flex items-center justify-center gap-2 text-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            📸 Postar Imagem no Status do WhatsApp
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<script>
    let modoOrigemProdutosAtual = 'PAGINA';
    let escopoCategoriaAtual = 'TODOS';
    let filtroFotoEncarteAtual = 'COM_FOTO';
    let produtosEncarteSelecionados = [];
    let ultimoEncarteId = null;
    let listaCategoriasCache = [];
    let produtosDinamicosCategoria = [];

    function alterarFiltroFotoEncarte(filtro) {
        filtroFotoEncarteAtual = filtro;

        const btnCom = document.getElementById('btnFiltroFotoCom');
        const btnSem = document.getElementById('btnFiltroFotoSem');
        const btnTodos = document.getElementById('btnFiltroFotoTodos');

        const classAtivoCom = 'flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-extrabold text-xs transition bg-emerald-600 text-white shadow-sm cursor-pointer';
        const classAtivoSem = 'flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-extrabold text-xs transition bg-amber-600 text-white shadow-sm cursor-pointer';
        const classAtivoTodos = 'flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-extrabold text-xs transition bg-indigo-600 text-white shadow-sm cursor-pointer';
        const classInativo = 'flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-bold text-xs transition text-slate-600 hover:bg-slate-100 cursor-pointer';

        if (btnCom) btnCom.className = (filtro === 'COM_FOTO') ? classAtivoCom : classInativo;
        if (btnSem) btnSem.className = (filtro === 'SEM_FOTO') ? classAtivoSem : classInativo;
        if (btnTodos) btnTodos.className = (filtro === 'TODOS') ? classAtivoTodos : classInativo;

        // Limpa cache para atualizar contagens
        listaCategoriasCache = [];
        carregarCategoriasModal();

        if (modoOrigemProdutosAtual === 'CATEGORIA') {
            aoMudarCategoriaEncarte();
        }
    }

    function alterarModoOrigemProdutos(modo) {
        modoOrigemProdutosAtual = modo;
        
        const radios = document.querySelectorAll('input[name="modo_origem_prod"]');
        radios.forEach(r => { r.checked = (r.value === modo); });

        const cardPag = document.getElementById('cardModoPagina');
        const cardCat = document.getElementById('cardModoCategoria');
        const cardQtd = document.getElementById('cardModoQuantidade');
        const cardTodos = document.getElementById('cardModoTodos');

        const classesPadrao = 'cursor-pointer border-2 border-slate-200 bg-white p-3 rounded-xl flex items-start gap-2.5 hover:shadow-md transition item-modo-origem';
        if (cardPag) cardPag.className = classesPadrao;
        if (cardCat) cardCat.className = classesPadrao;
        if (cardQtd) cardQtd.className = classesPadrao;
        if (cardTodos) cardTodos.className = classesPadrao;

        const painelCat = document.getElementById('painelCategoriaEncarte');
        const painelQtd = document.getElementById('painelQtdPersonalizada');

        if (painelCat) painelCat.classList.add('hidden');
        if (painelQtd) painelQtd.classList.add('hidden');

        if (modo === 'PAGINA') {
            if (cardPag) cardPag.className = 'cursor-pointer border-2 border-amber-400 bg-amber-50/60 p-3 rounded-xl flex items-start gap-2.5 hover:shadow-md transition item-modo-origem';
            renderizarTagsProdutosModal();
        } else if (modo === 'CATEGORIA') {
            if (cardCat) cardCat.className = 'cursor-pointer border-2 border-indigo-500 bg-indigo-50/70 p-3 rounded-xl flex items-start gap-2.5 hover:shadow-md transition item-modo-origem';
            if (painelCat) painelCat.classList.remove('hidden');
            carregarCategoriasModal();
        } else if (modo === 'QUANTIDADE') {
            if (cardQtd) cardQtd.className = 'cursor-pointer border-2 border-red-500 bg-red-50/60 p-3 rounded-xl flex items-start gap-2.5 hover:shadow-md transition item-modo-origem';
            if (painelQtd) painelQtd.classList.remove('hidden');
            renderizarTagsGenericasModal();
        } else if (modo === 'TODOS') {
            if (cardTodos) cardTodos.className = 'cursor-pointer border-2 border-emerald-500 bg-emerald-50/60 p-3 rounded-xl flex items-start gap-2.5 hover:shadow-md transition item-modo-origem';
            renderizarTagsGenericasModal();
        }
    }

    function alterarEscopoCategoria(escopo) {
        escopoCategoriaAtual = escopo;
        const subpainel = document.getElementById('subpainelQtdCategoria');
        const lblTodos = document.getElementById('labelEscopoCatTodos');
        const lblQtd = document.getElementById('labelEscopoCatQtd');

        if (escopo === 'QUANTIDADE') {
            if (subpainel) subpainel.classList.remove('hidden');
            if (lblQtd) {
                lblQtd.className = 'flex-1 flex items-center justify-center gap-1.5 py-1 px-2 rounded-lg cursor-pointer transition font-bold text-indigo-900 bg-indigo-100/70';
            }
            if (lblTodos) {
                lblTodos.className = 'flex-1 flex items-center justify-center gap-1.5 py-1 px-2 rounded-lg cursor-pointer transition font-bold text-slate-500 hover:bg-slate-50';
            }
        } else {
            if (subpainel) subpainel.classList.add('hidden');
            if (lblTodos) {
                lblTodos.className = 'flex-1 flex items-center justify-center gap-1.5 py-1 px-2 rounded-lg cursor-pointer transition font-bold text-indigo-900 bg-indigo-100/70';
            }
            if (lblQtd) {
                lblQtd.className = 'flex-1 flex items-center justify-center gap-1.5 py-1 px-2 rounded-lg cursor-pointer transition font-bold text-slate-500 hover:bg-slate-50';
            }
        }

        aoMudarCategoriaEncarte();
    }

    function definirQtdCatAtalho(val) {
        const inp = document.getElementById('inputQtdDesejadaCat');
        if (inp) {
            inp.value = val;
            aoMudarCategoriaEncarte();
        }
    }

    function definirQtdAtalho(val) {
        const inp = document.getElementById('inputQtdDesejadaProd');
        if (inp) inp.value = val;
    }

    let totalGeralCategoriasCache = 0;

    function carregarCategoriasModal(catParaSelecionar = null) {
        const sel = document.getElementById('selectCategoriaEncarte');
        if (sel && sel.options.length <= 1) {
            sel.innerHTML = '<option value="TODAS">⌛ Carregando categorias...</option>';
        }

        if (listaCategoriasCache.length > 0) {
            popularSelectCategorias(listaCategoriasCache, totalGeralCategoriasCache, catParaSelecionar);
            return;
        }

        fetch('<?= Url::to(['/vendas/encarte/categorias-com-contagem']) ?>?filtro_foto=' + encodeURIComponent(filtroFotoEncarteAtual), {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.categorias) {
                listaCategoriasCache = data.categorias;
                totalGeralCategoriasCache = data.total_geral || 0;
                popularSelectCategorias(listaCategoriasCache, totalGeralCategoriasCache, catParaSelecionar);
            } else {
                if (sel) sel.innerHTML = '<option value="TODAS">Todas as Categorias</option>';
            }
        })
        .catch(err => {
            console.error('Erro ao carregar categorias:', err);
            if (sel) sel.innerHTML = '<option value="TODAS">Todas as Categorias</option>';
        });
    }

    function popularSelectCategorias(categorias, totalGeral = 0, catParaSelecionar = null) {
        const sel = document.getElementById('selectCategoriaEncarte');
        if (!sel) return;

        const catAnterior = catParaSelecionar || sel.value;
        const textoFiltro = (filtroFotoEncarteAtual === 'COM_FOTO') ? 'com foto' : ((filtroFotoEncarteAtual === 'SEM_FOTO') ? 'sem foto' : 'total');

        let html = `<option value="TODAS">📂 Todas as Categorias (${totalGeral} produtos ${textoFiltro})</option>`;
        categorias.forEach(c => {
            html += `<option value="${c.id}">📁 ${c.nome} (${c.total_produtos} ${textoFiltro})</option>`;
        });
        sel.innerHTML = html;

        if (catAnterior && Array.from(sel.options).some(o => o.value === catAnterior)) {
            sel.value = catAnterior;
        }

        aoMudarCategoriaEncarte();
    }

    function aoMudarCategoriaEncarte() {
        const sel = document.getElementById('selectCategoriaEncarte');
        const badge = document.getElementById('badgeCatSelecionada');
        if (!sel || !badge) return;

        const catId = sel.value;
        const textoOption = sel.options[sel.selectedIndex]?.text || 'Escolher Categoria';
        
        if (catId === 'TODAS') {
            badge.textContent = 'Todas as Categorias';
        } else {
            const nomeCurto = textoOption.replace(/^📁\s*/, '').split('(')[0].trim();
            badge.textContent = nomeCurto;
        }

        // Se estiver em modo Categoria, carrega produtos para personalização de tags
        if (modoOrigemProdutosAtual === 'CATEGORIA') {
            const qtd = (escopoCategoriaAtual === 'QUANTIDADE') ? parseInt(document.getElementById('inputQtdDesejadaCat')?.value || '6', 10) : 0;
            carregarProdutosDaCategoriaParaTags(catId, qtd);
        }
    }

    function carregarProdutosDaCategoriaParaTags(catId, qtd = 0) {
        const container = document.getElementById('containerTagsProdutos');
        if (container) {
            container.innerHTML = '<div class="text-indigo-600 font-semibold italic text-[11px] text-center py-2 flex items-center justify-center gap-2"><span class="animate-spin inline-block">⌛</span> Consultando produtos da categoria...</div>';
        }

        const url = '<?= Url::to(['/vendas/encarte/produtos-por-categoria']) ?>?categoria_id=' + encodeURIComponent(catId) + '&qtd=' + qtd + '&filtro_foto=' + encodeURIComponent(filtroFotoEncarteAtual);

        fetch(url, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.produtos) {
                produtosDinamicosCategoria = data.produtos;
                renderizarTagsListaObjetos(produtosDinamicosCategoria);
            } else {
                if (container) container.innerHTML = '<div class="text-gray-400 italic text-[11px] text-center py-2">Nenhum produto encontrado com este filtro.</div>';
            }
        })
        .catch(err => {
            if (container) container.innerHTML = '<div class="text-red-500 italic text-[11px] text-center py-2">Erro ao carregar produtos. O encarte será gerado normalmente pelo backend.</div>';
        });
    }

    function abrirModalGerarEncarte(ids = []) {
        produtosEncarteSelecionados = ids;
        document.getElementById('badgeQtdEncarte').textContent = produtosEncarteSelecionados.length;
        document.getElementById('modalGerarEncarte').classList.remove('hidden');
        document.getElementById('boxResultadoEncarte').classList.add('hidden');

        // Herda categoria da URL ativa se existir
        const urlParams = new URLSearchParams(window.location.search);
        const catUrl = urlParams.get('categoria_id');

        if (ids.length > 0) {
            alterarModoOrigemProdutos('PAGINA');
            carregarCategoriasModal(catUrl);
        } else {
            alterarModoOrigemProdutos('CATEGORIA');
            carregarCategoriasModal(catUrl);
        }
    }

    function obterPayloadGerarEncarte() {
        let catId = null;
        let qtd = 0;

        if (modoOrigemProdutosAtual === 'CATEGORIA') {
            catId = document.getElementById('selectCategoriaEncarte')?.value || 'TODAS';
            if (escopoCategoriaAtual === 'QUANTIDADE') {
                qtd = parseInt(document.getElementById('inputQtdDesejadaCat')?.value || '6', 10);
            }
        } else if (modoOrigemProdutosAtual === 'QUANTIDADE') {
            qtd = parseInt(document.getElementById('inputQtdDesejadaProd')?.value || '12', 10);
        }

        return {
            modo_selecao: modoOrigemProdutosAtual,
            filtro_foto: filtroFotoEncarteAtual,
            categoria_id: catId,
            produtos_ids: produtosEncarteSelecionados,
            qtd_desejada: qtd,
            produtos_tags: coletarTagsProdutosMap(),
            inativar_anteriores: document.getElementById('checkInativarAnteriores')?.checked ? 1 : 0,
            titulo: document.getElementById('encarte_titulo').value,
            subtitulo: document.getElementById('encarte_subtitulo').value,
            cor_tema: document.getElementById('encarte_cor_tema').value,
            produtos_por_pagina: document.getElementById('encarte_ppp').value,
            '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
        };
    }

    function renderizarTagsGenericasModal() {
        const container = document.getElementById('containerTagsProdutos');
        if (!container) return;
        container.innerHTML = '<div class="text-slate-500 italic text-[11px] text-center py-3 bg-white rounded-lg border border-slate-200">ℹ️ Neste modo (' + (modoOrigemProdutosAtual === 'TODOS' ? 'Catálogo Completo' : 'Quantidade Automática') + '), os produtos selecionados usarão automaticamente as tags e promoções configuradas no cadastro.</div>';
    }

    function renderizarTagsListaObjetos(lista) {
        const container = document.getElementById('containerTagsProdutos');
        if (!container) return;
        container.innerHTML = '';

        const inputBusca = document.getElementById('inputBuscaTagsModal');
        if (inputBusca) inputBusca.value = '';

        if (lista.length === 0) {
            container.innerHTML = '<div class="text-gray-400 italic text-[11px] text-center py-2">Nenhum produto ativo encontrado nesta seleção.</div>';
            return;
        }

        lista.forEach((p, index) => {
            const div = document.createElement('div');
            div.className = 'item-tag-row flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-2 bg-white border border-gray-200 rounded-xl text-xs shadow-sm hover:border-indigo-300 transition';
            div.setAttribute('data-nome-busca', (p.nome || '').toLowerCase());
            div.innerHTML = `
                <div class="flex items-center gap-2 flex-1 min-w-0 pr-1">
                    <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-800 text-[10px] font-extrabold flex items-center justify-center flex-shrink-0">${index + 1}</span>
                    <span class="font-bold text-gray-900 truncate flex-1 text-xs" title="${p.nome}">${p.nome}</span>
                    <span class="text-[10px] font-extrabold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">R$ ${p.preco_venda_formatado || '0,00'}</span>
                </div>
                <select data-prod-id="${p.id}" class="select-tag-item px-2.5 py-1.5 bg-gray-50 border border-gray-300 rounded-lg text-xs font-semibold text-gray-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="AUTO" selected>⚡ Automático</option>
                    <option value="OFERTA">🏷️ Oferta</option>
                    <option value="OFERTA_ESPECIAL">🌟 Oferta Especial</option>
                    <option value="SUPER_OFERTA">🔥 Super Oferta</option>
                    <option value="MAIS_VENDIDO">⭐ Mais Vendido</option>
                    <option value="NENHUMA">🚫 Sem Tag</option>
                </select>
            `;
            container.appendChild(div);
        });
    }

    function renderizarTagsProdutosModal() {
        const container = document.getElementById('containerTagsProdutos');
        if (!container) return;
        container.innerHTML = '';

        const inputBusca = document.getElementById('inputBuscaTagsModal');
        if (inputBusca) inputBusca.value = '';

        if (produtosEncarteSelecionados.length === 0) {
            container.innerHTML = '<div class="text-gray-400 italic text-[11px] text-center py-2">Nenhum produto selecionado manualmente na página. Escolha uma Categoria ou outra opção acima.</div>';
            return;
        }

        produtosEncarteSelecionados.forEach((id, index) => {
            let nomeProd = '';

            // 1. Busca na checkbox (cards ou tabela) com data-nome
            const chk = document.querySelector(`input[name="produto_massa_chk"][value="${id}"]`);
            if (chk && chk.dataset.nome) {
                nomeProd = chk.dataset.nome.trim();
            }

            // 2. Fallback: Busca na linha da tabela
            if (!nomeProd) {
                const row = document.querySelector(`tr[data-key="${id}"]`) || (chk ? chk.closest('tr') : null);
                if (row) {
                    const elNome = row.querySelector('.nome-produto, td:nth-child(2) .font-medium, td:nth-child(3), td.font-bold');
                    if (elNome) nomeProd = elNome.textContent.trim();
                }
            }

            // 3. Fallback: Busca no card
            if (!nomeProd && chk) {
                const card = chk.closest('.bg-white');
                if (card) {
                    const elNome = card.querySelector('h3, .font-bold');
                    if (elNome) nomeProd = elNome.textContent.trim();
                }
            }

            if (!nomeProd) {
                nomeProd = 'Produto #' + (index + 1);
            }

            const div = document.createElement('div');
            div.className = 'item-tag-row flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-2 bg-white border border-gray-200 rounded-xl text-xs shadow-sm hover:border-amber-300 transition';
            div.setAttribute('data-nome-busca', nomeProd.toLowerCase());
            div.innerHTML = `
                <div class="flex items-center gap-2 flex-1 min-w-0 pr-1">
                    <span class="w-5 h-5 rounded-full bg-red-100 text-red-800 text-[10px] font-extrabold flex items-center justify-center flex-shrink-0">${index + 1}</span>
                    <span class="font-bold text-gray-900 truncate flex-1 text-xs" title="${nomeProd}">${nomeProd}</span>
                </div>
                <select data-prod-id="${id}" class="select-tag-item px-2.5 py-1.5 bg-gray-50 border border-gray-300 rounded-lg text-xs font-semibold text-gray-800 focus:ring-2 focus:ring-red-500 focus:outline-none">
                    <option value="AUTO" selected>⚡ Automático (Padrão)</option>
                    <option value="OFERTA">🏷️ Oferta</option>
                    <option value="OFERTA_ESPECIAL">🌟 Oferta Especial</option>
                    <option value="SUPER_OFERTA">🔥 Super Oferta</option>
                    <option value="MAIS_VENDIDO">⭐ Mais Vendido</option>
                    <option value="NENHUMA">🚫 Sem Tag</option>
                </select>
            `;
            container.appendChild(div);
        });
    }

    function filtrarTagsProdutosModal() {
        const termo = (document.getElementById('inputBuscaTagsModal')?.value || '').toLowerCase().trim();
        const rows = document.querySelectorAll('#containerTagsProdutos .item-tag-row');
        
        rows.forEach(row => {
            const nomeBusca = row.getAttribute('data-nome-busca') || '';
            if (nomeBusca.includes(termo)) {
                row.style.display = 'flex';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function coletarTagsProdutosMap() {
        const map = {};
        document.querySelectorAll('.select-tag-item').forEach(sel => {
            const id = sel.getAttribute('data-prod-id');
            if (id) map[id] = sel.value;
        });
        return map;
    }

    function fecharModalGerarEncarte() {
        document.getElementById('modalGerarEncarte').classList.add('hidden');
    }


    function gerarLinkEncartePublico() {
        if (modoOrigemProdutosAtual === 'PAGINA' && produtosEncarteSelecionados.length === 0) {
            alert('Nenhum produto foi selecionado na página. Escolha a opção "Informe a Quantidade" ou "TODOS os Produtos".');
            return;
        }

        const payload = obterPayloadGerarEncarte();

        fetch('<?= Url::to(['/vendas/encarte/gerar']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.url_publica) {
                ultimoEncarteId = data.encarte_id;
                document.getElementById('inputUrlEncarteGerado').value = data.url_publica;
                document.getElementById('boxResultadoEncarte').classList.remove('hidden');
                
                // Abre o encarte público em nova aba
                window.open(data.url_publica, '_blank');
            } else {
                alert('Erro ao gerar encarte: ' + (data.message || 'Falha na requisição'));
            }
        })
        .catch(err => {
            alert('Erro de conexão: ' + err.message);
        });
    }

    function baixarPdfEncarteDirect() {
        if (modoOrigemProdutosAtual === 'PAGINA' && produtosEncarteSelecionados.length === 0) {
            alert('Nenhum produto foi selecionado na página. Escolha a opção "Informe a Quantidade" ou "TODOS os Produtos".');
            return;
        }

        if (ultimoEncarteId && modoOrigemProdutosAtual === 'PAGINA') {
            window.open('<?= Url::to(['/vendas/encarte/download-pdf']) ?>?id=' + ultimoEncarteId, '_blank');
            return;
        }

        const payload = obterPayloadGerarEncarte();

        fetch('<?= Url::to(['/vendas/encarte/gerar']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.encarte_id) {
                ultimoEncarteId = data.encarte_id;
                document.getElementById('inputUrlEncarteGerado').value = data.url_publica;
                document.getElementById('boxResultadoEncarte').classList.remove('hidden');
                window.open('<?= Url::to(['/vendas/encarte/download-pdf']) ?>?id=' + data.encarte_id, '_blank');
            } else {
                alert('Erro ao gerar encarte: ' + (data.message || 'Falha na requisição'));
            }
        });
    }

    function copiarLinkEncarte() {
        const input = document.getElementById('inputUrlEncarteGerado');
        input.select();
        document.execCommand('copy');
        
        const status = document.getElementById('statusCopiadoLink');
        status.textContent = '✓ Copiado para a área de transferência!';
        setTimeout(() => { status.textContent = ''; }, 3000);
    }

    function dispararEncarteEvolution() {
        if (!ultimoEncarteId) {
            // Gera primeiro antes de disparar
            const payload = obterPayloadGerarEncarte();

            fetch('<?= Url::to(['/vendas/encarte/gerar']) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.encarte_id) {
                    ultimoEncarteId = data.encarte_id;
                    executarDisparoEvolutionServico();
                } else {
                    alert('Erro ao gerar encarte prévio: ' + data.message);
                }
            });
            return;
        }

        executarDisparoEvolutionServico();
    }

    function executarDisparoEvolutionServico() {
        const btn = document.getElementById('btnEnviarEncarteWp');
        btn.disabled = true;
        btn.innerHTML = '⌛ Enviando via Evolution API...';

        const payload = {
            encarte_id: ultimoEncarteId,
            telefones_manuais: document.getElementById('encarte_telefones').value,
            mensagem_texto: document.getElementById('encarte_mensagem').value,
            '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
        };

        fetch('<?= Url::to(['/vendas/encarte/enviar-whatsapp']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg> Disparar Encarte e PDF via WhatsApp (Evolution)';
            
            if (data.success) {
                alert(data.message);
            } else {
                alert('Erro no envio: ' + (data.message || 'Falha na comunicação com a API.'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg> Disparar Encarte e PDF via WhatsApp (Evolution)';
            alert('Erro de conexão: ' + err.message);
        });
    }

    function carregarClientesBaseZap(qtd = 20) {
        const txt = document.getElementById('encarte_telefones');
        if (!txt) return;

        txt.placeholder = '⌛ Carregando clientes aleatórios da base...';

        fetch('<?= Url::to(['/vendas/encarte/carregar-clientes-zap']) ?>?qtd=' + qtd, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(r => r.json())
        .then(data => {
            txt.placeholder = 'Cole ou digite números separados por vírgula ou linha (ex: 81999998888, 81988887777 ou 5581999998888 # Nome Cliente)';
            if (data.success && data.texto_formatado) {
                let conteudoAtual = txt.value.trim();
                if (conteudoAtual) {
                    txt.value = conteudoAtual + "\n" + data.texto_formatado;
                } else {
                    txt.value = data.texto_formatado;
                }
                atualizarContadorTelefones();
                alert(`✅ ${data.qtd} cliente(s) aleatório(s) carregado(s) da base com sucesso!`);
            } else {
                alert('Aviso: ' + (data.message || 'Nenhum cliente com telefone cadastrado foi encontrado.'));
            }
        })
        .catch(err => {
            txt.placeholder = 'Cole ou digite números separados por vírgula ou linha...';
            alert('Erro ao carregar clientes: ' + err.message);
        });
    }

    function limparTelefonesEncarte() {
        const txt = document.getElementById('encarte_telefones');
        if (txt) {
            txt.value = '';
            atualizarContadorTelefones();
        }
    }

    function atualizarContadorTelefones() {
        const txt = document.getElementById('encarte_telefones');
        const badge = document.getElementById('badgeContadorTelefones');
        if (!txt || !badge) return;

        const val = txt.value.trim();
        if (!val) {
            badge.textContent = '📊 0 número(s) selecionado(s)';
            return;
        }

        const linhas = val.split(/[\n,;]+/);
        let count = 0;
        linhas.forEach(l => {
            const numClean = l.split('#')[0].replace(/\D/g, '');
            if (numClean.length >= 10) count++;
        });

        badge.textContent = `📊 ${count} número(s) selecionado(s)`;
    }

    function postarEncarteStatusEvolution() {
        if (!ultimoEncarteId) {
            const btnStatus = document.getElementById('btnPostarStatusWp');
            btnStatus.disabled = true;
            btnStatus.innerHTML = '⌛ Gerando Encarte Previo...';

            const payload = obterPayloadGerarEncarte();

            fetch('<?= Url::to(['/vendas/encarte/gerar']) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.encarte_id) {
                    ultimoEncarteId = data.encarte_id;
                    executarPostagemStatusServico();
                } else {
                    btnStatus.disabled = false;
                    btnStatus.innerHTML = '📸 Postar Imagem no Status do WhatsApp';
                    alert('Erro ao gerar encarte prévio: ' + data.message);
                }
            })
            .catch(err => {
                btnStatus.disabled = false;
                btnStatus.innerHTML = '📸 Postar Imagem no Status do WhatsApp';
                alert('Erro: ' + err.message);
            });
            return;
        }

        executarPostagemStatusServico();
    }

    function executarPostagemStatusServico() {
        const btnStatus = document.getElementById('btnPostarStatusWp');
        btnStatus.disabled = true;
        btnStatus.innerHTML = '⌛ Publicando no Status do WhatsApp...';

        const payload = {
            encarte_id: ultimoEncarteId,
            mensagem_texto: document.getElementById('encarte_mensagem').value,
            '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
        };

        fetch('<?= Url::to(['/vendas/encarte/postar-status-whatsapp']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            btnStatus.disabled = false;
            btnStatus.innerHTML = '📸 Postar Imagem no Status do WhatsApp';

            if (data.success) {
                alert('🎉 ' + data.message);
            } else {
                alert('Aviso ao postar no Status: ' + (data.message || 'Falha na publicação.'));
            }
        })
        .catch(err => {
            btnStatus.disabled = false;
            btnStatus.innerHTML = '📸 Postar Imagem no Status do WhatsApp';
            alert('Erro de comunicação: ' + err.message);
        });
    }

    function toggleHistoricoEncartes() {
        const p = document.getElementById('painelHistoricoEncartes');
        const seta = document.getElementById('setaHistoricoEncartes');
        if (!p) return;
        const oculto = p.classList.contains('hidden');
        if (oculto) {
            p.classList.remove('hidden');
            if (seta) seta.style.transform = 'rotate(180deg)';
            carregarHistoricoEncartes();
        } else {
            p.classList.add('hidden');
            if (seta) seta.style.transform = 'rotate(0deg)';
        }
    }

    function carregarHistoricoEncartes() {
        const p = document.getElementById('painelHistoricoEncartes');
        if (!p) return;
        p.innerHTML = '<div class="text-center py-4 text-xs text-slate-400 font-semibold flex items-center justify-center gap-2"><span class="animate-spin inline-block">⌛</span> Carregando lista de encartes...</div>';

        fetch('<?= Url::to(['/vendas/encarte/listar']) ?>', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.encartes || data.encartes.length === 0) {
                p.innerHTML = '<div class="text-center py-4 text-xs text-slate-400 font-bold">Nenhum encarte gerado ainda.</div>';
                return;
            }

            p.innerHTML = '';
            data.encartes.forEach(enc => {
                const isAtivo = enc.status === 'ativo';
                const div = document.createElement('div');
                div.className = 'p-3 bg-white rounded-xl border border-slate-200 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs';
                div.innerHTML = `
                    <div class="flex-1 min-w-0 pr-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider ${isAtivo ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-red-100 text-red-800 border border-red-300'}">
                                ${isAtivo ? '● Ativo' : '✕ Inativo'}
                            </span>
                            <span class="font-extrabold text-slate-900 truncate">${enc.titulo}</span>
                            <span class="text-[10px] text-slate-400 font-semibold">• ${enc.data_criacao}</span>
                        </div>
                        <div class="text-[11px] text-slate-500 font-medium mt-0.5 truncate">
                            ${enc.total_produtos} produto(s) • ${enc.visualizacoes} visualização(ões)
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <a href="${enc.url_publica}" target="_blank" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-bold text-[10px] transition">
                            👁️ Ver
                        </a>
                        <button type="button" onclick="copiarTextoGenerico('${enc.url_publica}')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-bold text-[10px] transition cursor-pointer">
                            🔗 Copiar
                        </button>
                        <button type="button" onclick="alternarStatusEncarteItem('${enc.id}')" class="px-2.5 py-1 ${isAtivo ? 'bg-red-50 hover:bg-red-100 text-red-700 border border-red-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200'} rounded-lg font-bold text-[10px] transition cursor-pointer">
                            ${isAtivo ? 'Inativar' : 'Ativar'}
                        </button>
                    </div>
                `;
                p.appendChild(div);
            });
        })
        .catch(err => {
            p.innerHTML = '<div class="text-center py-4 text-xs text-red-500 font-bold">Erro ao carregar lista de encartes.</div>';
        });
    }

    function alternarStatusEncarteItem(id) {
        fetch('<?= Url::to(['/vendas/encarte/alternar-status']) ?>?id=' + encodeURIComponent(id), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                carregarHistoricoEncartes();
            } else {
                alert(data.message || 'Erro ao alterar status.');
            }
        })
        .catch(err => alert('Erro de conexão: ' + err.message));
    }

    function copiarTextoGenerico(texto) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(texto).then(() => alert('Link copiado com sucesso!'));
        } else {
            prompt('Copie o link:', texto);
        }
    }
</script>
