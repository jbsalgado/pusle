<?php

use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var \app\models\Usuario|null $usuarioLoja
 * @var \app\modules\vendas\models\LojaConfiguracao|null $lojaConfig
 * @var \app\modules\vendas\models\CanalSetor[] $setoresPermitidos
 * @var bool $ehDono
 * @var \app\modules\vendas\models\Colaborador[] $colaboradores
 * @var string $hubUrlCompleta
 */

$nomeLoja = !empty($lojaConfig->nome_fantasia) 
    ? $lojaConfig->nome_fantasia 
    : (!empty($lojaConfig->nome_loja) 
        ? $lojaConfig->nome_loja 
        : ($usuarioLoja ? $usuarioLoja->nome_loja : 'Minha Loja'));

$lojaId = $usuarioLoja ? $usuarioLoja->id : \app\components\TenantHelper::getId();
if (empty($hubUrlCompleta) && $usuarioLoja) {
    $slug = !empty($usuarioLoja->slug) ? $usuarioLoja->slug : $usuarioLoja->id;
    $hubUrlCompleta = Url::to(['/hub/index', 'slug' => $slug], true);
}
?>

<!-- Modal Central do Canal de Comunicação Interno (Estilo WhatsApp Multi-Setor) -->
<div id="modalChatWhatsApp" class="fixed inset-0 z-50 hidden bg-slate-950/85 flex items-center justify-center p-2 sm:p-4">
    <div class="relative w-full max-w-6xl h-[94vh] bg-[#f0f2f5] rounded-3xl shadow-2xl overflow-hidden border border-slate-700/50 flex flex-col">
        
        <!-- HEADER WHATSAPP STYLE -->
        <div class="bg-[#008069] text-white px-4 py-3 flex items-center justify-between border-b border-[#006e5a] shadow-sm select-none shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-xl font-bold border border-white/20 shadow-inner">
                    💬
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-base sm:text-lg font-black tracking-tight leading-tight">
                            <?= Html::encode($nomeLoja) ?> &bull; Canal Interno
                        </h2>
                        <span class="bg-emerald-400 text-slate-950 text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider shadow-xs">
                            Direct Hub WhatsApp
                        </span>
                    </div>
                    <p class="text-xs text-emerald-100/90 flex items-center gap-1.5 mt-0.5">
                        <span id="chatWsStatusIndicator" class="w-2 h-2 rounded-full bg-emerald-300 animate-pulse" title="Conectando ao canal em tempo real..."></span>
                        <span id="chatWsStatusText">Atendimento por Setores • Isolado por Loja</span>
                    </p>
                </div>
            </div>

            <!-- Ações do Header -->
            <div class="flex items-center gap-1.5 sm:gap-2">
                <!-- Botão Link Direct Hub / QR Code -->
                <button type="button" onclick="toggleLinkHubInfo()" class="px-2.5 py-1.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer border border-white/10" title="Ver Link do Canal do Cliente">
                    <span>🌐</span>
                    <span class="hidden md:inline">Link do Canal</span>
                </button>

                <!-- Botão Gerenciar Setores (EXCLUSIVO DO DONO DA LOJA) -->
                <?php if ($ehDono): ?>
                <button type="button" onclick="abrirGerenciadorSetores()" class="px-3 py-1.5 bg-amber-400 hover:bg-amber-300 text-slate-950 rounded-xl text-xs font-black transition flex items-center gap-1.5 cursor-pointer shadow-md" title="Gerenciar Setores e Funcionários">
                    <span>⚙️</span>
                    <span class="hidden sm:inline">Gerenciar Setores</span>
                </button>
                <?php endif; ?>

                <!-- Fechar Modal -->
                <button type="button" onclick="fecharModalCanalInterno()" class="w-9 h-9 rounded-full bg-black/20 hover:bg-black/35 text-white flex items-center justify-center transition cursor-pointer" title="Fechar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <!-- PAINEL COLAPSÁVEL DE LINK DO CLIENTE / QR CODE -->
        <div id="painelLinkHubInfo" class="hidden bg-slate-900 text-white px-4 py-3 border-b border-slate-800 transition-all shrink-0">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 max-w-4xl mx-auto">
                <div class="flex-1 w-full">
                    <div class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider mb-1">
                        Link Público para os Clientes Acessarem o Canal Direct Hub
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="text" id="inputUrlDirectHub" readonly value="<?= Html::encode($hubUrlCompleta) ?>" class="w-full bg-slate-800 text-xs font-mono px-3 py-1.5 rounded-lg border border-slate-700 text-emerald-300 select-all outline-none">
                        <button type="button" onclick="copiarLinkDirectHub()" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg cursor-pointer whitespace-nowrap">
                            Copiar Link
                        </button>
                        <a href="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=<?= urlencode($hubUrlCompleta) ?>" target="_blank" class="px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold rounded-lg cursor-pointer whitespace-nowrap">
                            📱 Baixar QR Code
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- CORPO PRINCIPAL DO CHAT (TWO COLUMNS) -->
        <div class="flex-1 flex overflow-hidden relative">
            
            <!-- ========================================================= -->
            <!-- COLUNA ESQUERDA: LISTA DE SETORES E CONVERSAS (35% / 100%) -->
            <!-- ========================================================= -->
            <div id="colunaListaConversas" class="w-full md:w-[380px] lg:w-[420px] bg-white border-r border-slate-200 flex flex-col h-full shrink-0 z-10">
                
                <!-- Barra de Pesquisa -->
                <div class="p-2.5 bg-[#f0f2f5] border-b border-slate-200">
                    <div class="relative flex items-center">
                        <span class="absolute left-3 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text" 
                               id="chatBuscaInput" 
                               oninput="aoBuscarConversas(this.value)" 
                               placeholder="Pesquisar conversa ou cliente..." 
                               class="w-full pl-9 pr-8 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 placeholder-slate-400 outline-none focus:border-[#008069] focus:ring-1 focus:ring-[#008069] transition">
                        <button type="button" 
                                id="btnLimparBuscaChat" 
                                onclick="limparBuscaConversas()" 
                                class="hidden absolute right-2.5 text-slate-400 hover:text-slate-600 font-bold text-sm cursor-pointer">&times;</button>
                    </div>
                </div>

                <!-- CHIPS DE FILTRO POR SETOR (ROLAGEM HORIZONTAL) -->
                <div class="px-2.5 py-2 bg-[#f0f2f5] border-b border-slate-200 overflow-x-auto flex items-center gap-1.5 no-scrollbar select-none">
                    <button type="button" 
                            onclick="selecionarSetorFiltro('todos', this)" 
                            class="chip-setor px-3 py-1 rounded-full text-xs font-bold transition flex items-center gap-1 cursor-pointer bg-[#008069] text-white shadow-xs" 
                            data-setor="todos">
                        <span>💬</span>
                        <span>Todos</span>
                        <span id="badgeContadorSetorTodos" class="ml-1 bg-white/20 text-white text-[10px] px-1.5 py-0.2 rounded-full hidden">0</span>
                    </button>

                    <?php foreach ($setoresPermitidos as $st): ?>
                    <button type="button" 
                            onclick="selecionarSetorFiltro('<?= $st->id ?>', this)" 
                            class="chip-setor px-3 py-1 rounded-full text-xs font-bold transition flex items-center gap-1 cursor-pointer bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 whitespace-nowrap" 
                            data-setor="<?= $st->id ?>">
                        <span><?= Html::encode($st->icone ?: '📁') ?></span>
                        <span><?= Html::encode($st->nome) ?></span>
                        <span id="badgeSetor_<?= $st->id ?>" class="ml-1 bg-emerald-600 text-white text-[10px] px-1.5 py-0.2 rounded-full hidden">0</span>
                    </button>
                    <?php endforeach; ?>
                </div>

                <!-- LISTA DE CONVERSAS (SCROLL) -->
                <div id="listaConversasChat" class="flex-1 overflow-y-auto divide-y divide-slate-100">
                    <div class="p-8 text-center text-slate-400 space-y-2">
                        <span class="text-3xl block animate-spin">⏳</span>
                        <p class="text-xs font-semibold">Carregando conversas do canal...</p>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- COLUNA DIREITA: JANELA DA CONVERSA ATIVA (65% / 100%)     -->
            <!-- ========================================================= -->
            <div id="colunaConversaAtiva" class="flex-1 flex flex-col h-full bg-[#efeae2] relative hidden md:flex">
                
                <!-- ESTADO VAZIO (NENHUMA CONVERSA SELECIONADA) -->
                <div id="estadoVazioChat" class="flex-1 flex flex-col items-center justify-center p-8 text-center bg-[#f0f2f5] space-y-4">
                    <div class="w-20 h-20 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-4xl shadow-md">
                        📱
                    </div>
                    <div class="max-w-md">
                        <h3 class="text-lg font-black text-slate-800">Canal de Comunicação Interno</h3>
                        <p class="text-xs text-slate-500 mt-1">
                            Selecione uma conversa ao lado para responder aos seus clientes. Suas mensagens são enviadas em tempo real com isolamento estrito por setor.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 text-[11px] text-slate-400">
                        <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                        <span>Protegido com criptografia e isolamento por loja</span>
                    </div>
                </div>

                <!-- CONVERSA SELECIONADA (HEADER, MENSAGENS E INPUT) -->
                <div id="painelConversaAberta" class="flex-1 flex flex-col h-full hidden relative">
                    
                    <!-- HEADER DA CONVERSA ATIVA -->
                    <div class="px-4 py-2.5 bg-[#f0f2f5] border-b border-slate-300 flex items-center justify-between shrink-0 shadow-xs">
                        <div class="flex items-center gap-3">
                            <!-- Botão Voltar no Mobile -->
                            <button type="button" onclick="voltarParaListaMobile()" class="md:hidden text-slate-600 hover:text-slate-900 p-1 -ml-1">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                            </button>

                            <!-- Avatar do Cliente -->
                            <div id="conversaAtivaAvatar" class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center font-black text-sm shadow-sm">
                                C
                            </div>

                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 id="conversaAtivaNome" class="text-sm font-black text-slate-900 leading-none">Cliente</h3>
                                    <span id="conversaAtivaSetorBadge" class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2 py-0.5 rounded-full border border-emerald-200">
                                        💬 Vendas
                                    </span>
                                </div>
                                <p id="conversaAtivaSubtitulo" class="text-[11px] text-slate-500 mt-0.5">Online via Direct Hub</p>
                            </div>
                        </div>

                        <!-- Ações rápidas da conversa -->
                        <div class="flex items-center gap-2">
                            <!-- Seletor de Setor para Transferência/Atribuição -->
                            <div class="flex items-center gap-1.5">
                                <span class="text-[11px] font-bold text-slate-500 hidden lg:inline">Setor:</span>
                                <select id="conversaAtivaSelectSetor" onchange="aoMudarSetorConversa(this.value)" class="text-xs bg-white border border-slate-300 rounded-lg px-2 py-1 font-semibold text-slate-700 outline-none focus:border-[#008069] cursor-pointer">
                                    <option value="">Sem Setor</option>
                                    <?php foreach ($setoresPermitidos as $st): ?>
                                    <option value="<?= $st->id ?>"><?= Html::encode($st->icone . ' ' . $st->nome) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Botão Encerrar Atendimento -->
                            <button type="button" 
                                    onclick="encerrarAtendimentoAtivo()" 
                                    class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-bold rounded-lg transition flex items-center gap-1 cursor-pointer" 
                                    title="Concluir e encerrar este atendimento">
                                <span>✅</span>
                                <span class="hidden sm:inline">Encerrar</span>
                            </button>

                            <!-- Botão Limpar Conversa -->
                            <button type="button" 
                                    onclick="limparConversaAtiva()" 
                                    class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-xs font-bold rounded-lg transition flex items-center gap-1 cursor-pointer" 
                                    title="Apagar histórico desta conversa">
                                <span>🗑️</span>
                                <span class="hidden sm:inline">Limpar</span>
                            </button>
                        </div>
                    </div>

                    <!-- ÁREA DE MENSAGENS (COM FUNDO WHATSAPP DOODLE) -->
                    <div id="containerMensagensChat" class="flex-1 overflow-y-auto p-4 space-y-3" style="background-color: #efeae2; background-image: radial-gradient(#d1d7db 0.75px, transparent 0.75px); background-size: 16px 16px;">
                        <!-- Mensagens renderizadas dinamicamente via JS -->
                    </div>

                    <!-- BARRA DE PREVIEW DE FOTO SELECIONADA -->
                    <div id="previewFotoContainer" class="hidden px-4 py-2 bg-slate-100 border-t border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <img id="previewFotoImg" src="" alt="Preview" class="w-14 h-14 object-cover rounded-xl border border-slate-300 shadow-sm">
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">Foto selecionada para envio</span>
                                <span id="previewFotoNome" class="text-[10px] text-slate-500 font-mono">imagem.jpg</span>
                            </div>
                        </div>
                        <button type="button" onclick="cancelarEnvioFoto()" class="text-red-600 hover:text-red-800 text-xs font-bold px-2 py-1 bg-red-50 hover:bg-red-100 rounded-lg cursor-pointer">
                            Remover Foto
                        </button>
                    </div>

                    <!-- PAINEL FLUTUANTE DE EMOJIS ESTILO WHATSAPP -->
                    <div id="painelEmojiPickerChat" class="hidden absolute bottom-16 left-3 sm:left-4 z-40 w-72 sm:w-88 bg-white rounded-2xl shadow-2xl border border-slate-200 flex flex-col overflow-hidden select-none">
                        <!-- Topo: Busca e Fechar -->
                        <div class="p-2.5 bg-slate-50 border-b border-slate-200 space-y-2">
                            <div class="flex items-center gap-2">
                                <div class="relative flex-1">
                                    <input type="text" 
                                           id="inputBuscaEmojiChat" 
                                           oninput="aoBuscarEmojiChat(this.value)" 
                                           placeholder="Pesquisar emoji..." 
                                           class="w-full pl-7 pr-3 py-1 bg-white border border-slate-300 rounded-xl text-xs text-slate-700 outline-none focus:border-[#008069]">
                                    <span class="absolute left-2 top-1 text-xs text-slate-400">🔍</span>
                                </div>
                                <button type="button" 
                                        onclick="fecharEmojiPickerChat()" 
                                        class="text-slate-400 hover:text-slate-600 p-1 text-sm font-bold cursor-pointer" 
                                        title="Fechar">
                                    ✕
                                </button>
                            </div>

                            <!-- Barra de Categorias -->
                            <div class="flex items-center justify-between text-base px-0.5">
                                <button type="button" onclick="selecionarCategoriaEmoji('recentes')" id="tabEmoji_recentes" class="tab-emoji-btn p-1.5 rounded-xl hover:bg-slate-200 text-slate-600 transition cursor-pointer" title="Recentes / Populares">🕒</button>
                                <button type="button" onclick="selecionarCategoriaEmoji('rostos')" id="tabEmoji_rostos" class="tab-emoji-btn p-1.5 rounded-xl hover:bg-slate-200 text-slate-600 transition cursor-pointer bg-emerald-100 text-[#008069]" title="Carinhas e Emoções">😃</button>
                                <button type="button" onclick="selecionarCategoriaEmoji('gestos')" id="tabEmoji_gestos" class="tab-emoji-btn p-1.5 rounded-xl hover:bg-slate-200 text-slate-600 transition cursor-pointer" title="Mãos e Gestos">👍</button>
                                <button type="button" onclick="selecionarCategoriaEmoji('coracoes')" id="tabEmoji_coracoes" class="tab-emoji-btn p-1.5 rounded-xl hover:bg-slate-200 text-slate-600 transition cursor-pointer" title="Corações e Símbolos">❤️</button>
                                <button type="button" onclick="selecionarCategoriaEmoji('comida')" id="tabEmoji_comida" class="tab-emoji-btn p-1.5 rounded-xl hover:bg-slate-200 text-slate-600 transition cursor-pointer" title="Comidas e Bebidas">🍕</button>
                                <button type="button" onclick="selecionarCategoriaEmoji('comercio')" id="tabEmoji_comercio" class="tab-emoji-btn p-1.5 rounded-xl hover:bg-slate-200 text-slate-600 transition cursor-pointer" title="Vendas e Objetos">💼</button>
                            </div>
                        </div>

                        <!-- Título da Categoria Ativa -->
                        <div class="px-3 py-1 bg-slate-100/70 border-b border-slate-200/60 flex items-center justify-between">
                            <span id="tituloCategoriaEmoji" class="text-[10px] font-black uppercase tracking-wider text-slate-500">Carinhas e Emoções</span>
                            <span id="contagemEmojis" class="text-[10px] text-slate-400 font-semibold"></span>
                        </div>

                        <!-- Grade de Emojis -->
                        <div id="gridEmojisChat" class="p-2 grid grid-cols-8 gap-1 max-h-52 overflow-y-auto">
                            <!-- Emojis renderizados via JS -->
                        </div>
                    </div>

                    <!-- FOOTER: BARRA DE DIGITAÇÃO WHATSAPP -->
                    <div class="p-2.5 bg-[#f0f2f5] border-t border-slate-300 flex items-center gap-2 shrink-0">
                        <!-- Input invisível de Upload de Foto -->
                        <input type="file" id="inputUploadMidiaChat" accept="image/*" class="hidden" onchange="aoSelecionarFotoChat(this)">

                        <!-- Botão Inserir Emojis -->
                        <button type="button" 
                                id="btnEmojiChat" 
                                onclick="toggleEmojiPickerChat()" 
                                class="w-9 h-9 rounded-full hover:bg-slate-200 text-slate-600 flex items-center justify-center transition cursor-pointer text-xl select-none shrink-0" 
                                title="Inserir Emoji">
                            😊
                        </button>

                        <!-- Botão Anexar Foto -->
                        <button type="button" onclick="document.getElementById('inputUploadMidiaChat').click()" class="w-9 h-9 rounded-full hover:bg-slate-200 text-slate-600 flex items-center justify-center transition cursor-pointer" title="Anexar Imagem/Foto">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        </button>

                        <!-- Input de Texto da Mensagem -->
                        <div class="flex-1 relative">
                            <textarea id="inputTextoMensagemChat" 
                                      rows="1" 
                                      onkeydown="aoPressionarTeclaChat(event)" 
                                      placeholder="Digite uma mensagem..." 
                                      class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-2xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 outline-none focus:border-[#008069] focus:ring-1 focus:ring-[#008069] resize-none max-h-28"></textarea>
                        </div>

                        <!-- Botão Enviar Mensagem -->
                        <button type="button" 
                                id="btnEnviarMensagemChat" 
                                onclick="enviarMensagemChat()" 
                                class="w-10 h-10 rounded-full bg-[#008069] hover:bg-[#006e5a] text-white flex items-center justify-center transition shadow-md cursor-pointer shrink-0" 
                                title="Enviar (Enter)">
                            <svg class="w-5 h-5 translate-x-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                        </button>
                    </div>

                </div>

            </div>

        </div>

    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL DE GERENCIAMENTO DE SETORES (EXCLUSIVO DO DONO DA LOJA)             -->
<!-- ========================================================================= -->
<?php if ($ehDono): ?>
<div id="modalGerenciarSetores" class="fixed inset-0 z-[60] hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6 transition-all duration-300">
    <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-200 flex flex-col max-h-[90vh]">
        
        <!-- Header do Gerenciador -->
        <div class="bg-gradient-to-r from-slate-900 to-teal-900 text-white p-5 flex items-center justify-between border-b border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-400 text-slate-950 flex items-center justify-center font-black text-xl shadow">
                    ⚙️
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-black tracking-tight">Gerenciador de Grupos e Setores</h3>
                    <p class="text-xs text-teal-200/80">Configure quem pode visualizar e responder cada setor da sua loja</p>
                </div>
            </div>
            <button type="button" onclick="fecharGerenciadorSetores()" class="text-slate-400 hover:text-white p-2 rounded-full cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Conteúdo do Gerenciador -->
        <div class="p-5 overflow-y-auto space-y-6 flex-1 bg-slate-50">
            
            <!-- Botão Adicionar Novo Setor -->
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-500">Setores Cadastrados</h4>
                <button type="button" onclick="abrirFormNovoSetor()" class="px-3 py-1.5 bg-[#008069] hover:bg-[#006e5a] text-white text-xs font-black rounded-xl shadow-xs transition flex items-center gap-1 cursor-pointer">
                    <span>➕</span>
                    <span>Novo Setor</span>
                </button>
            </div>

            <!-- Lista de Setores Existentes -->
            <div id="listaSetoresConfig" class="space-y-3">
                <div class="text-center py-6 text-slate-400 text-xs font-semibold">Carregando setores...</div>
            </div>

            <!-- Formulário de Criação / Edição de Setor (Colapsável) -->
            <div id="formSetorContainer" class="hidden bg-white p-4 rounded-2xl border border-teal-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h4 id="formSetorTitulo" class="text-xs font-black text-teal-800 uppercase tracking-wider">Criar Novo Setor</h4>
                    <button type="button" onclick="fecharFormSetor()" class="text-slate-400 hover:text-slate-600 text-xs font-bold cursor-pointer">Cancelar</button>
                </div>

                <input type="hidden" id="inputSetorId" value="">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-[11px] font-bold text-slate-600 block mb-1">Nome do Setor *</label>
                        <input type="text" id="inputSetorNome" placeholder="Ex: Vendas, Cobrança, Expedição" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-800 outline-none focus:border-[#008069]">
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-slate-600 block mb-1">Ícone / Emoji</label>
                        <select id="inputSetorIcone" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-800 outline-none focus:border-[#008069]">
                            <option value="💼">💼 Maleta / Vendas</option>
                            <option value="💰">💰 Saco de Dinheiro / Cobrança</option>
                            <option value="🎧">🎧 Headset / Atendimento</option>
                            <option value="📦">📦 Caixa / Expedição</option>
                            <option value="🚚">🚚 Caminhão / Entregas</option>
                            <option value="🛠️">🛠️ Ferramentas / Suporte</option>
                            <option value="💬">💬 Chat Geral</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-slate-600 block mb-1">Descrição Curta</label>
                    <input type="text" id="inputSetorDescricao" placeholder="Breve explicação para os clientes e equipe" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-800 outline-none focus:border-[#008069]">
                </div>

                <!-- Seleção de Colaboradores com Acesso a este Setor -->
                <div>
                    <label class="text-[11px] font-bold text-slate-600 block mb-1.5">
                        Funcionários / Colaboradores com Acesso a este Setor:
                    </label>
                    <div id="containerColaboradoresCheck" class="max-h-36 overflow-y-auto p-2 bg-slate-50 rounded-xl border border-slate-200 space-y-1.5">
                        <!-- Checkboxes gerados dinamicamente via JS -->
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">
                        * O dono da loja e administradores sempre têm acesso a todos os setores automaticamente.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="fecharFormSetor()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl cursor-pointer">
                        Cancelar
                    </button>
                    <button type="button" onclick="salvarSetorConfig()" class="px-4 py-1.5 bg-[#008069] hover:bg-[#006e5a] text-white text-xs font-black rounded-xl shadow cursor-pointer">
                        Salvar Setor
                    </button>
                </div>
            </div>

        </div>

        <!-- Rodapé do Gerenciador -->
        <div class="p-4 bg-slate-100 border-t border-slate-200 flex items-center justify-end">
            <button type="button" onclick="fecharGerenciadorSetores()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl transition cursor-pointer">
                Concluído
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- TOAST FLUTUANTE DE NOTIFICAÇÃO -->
<div id="toastChatWhatsApp" class="fixed top-6 right-6 z-[9999] hidden px-4 py-2.5 rounded-xl text-xs font-bold shadow-xl transition-all duration-300 transform flex items-center gap-2 pointer-events-none text-white bg-slate-900">
    <span id="toastChatWhatsAppIcon">💬</span>
    <span id="toastChatWhatsAppMsg">Mensagem</span>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT DO CHAT WHATSAPP MULTI-SETOR                                   -->
<!-- ========================================================================= -->
<script>
(function() {
    // Variáveis de Estado
    window._chatConversas = [];
    window._chatConversasHash = '';
    window._chatConversaAtiva = null;
    window._chatSetorFiltro = 'todos';
    window._chatTermoBusca = '';
    window._chatPollingTimer = null;
    window._chatFotoUrlPendente = null;
    window._chatColaboradoresStore = [];
    window._chatLastMsgTs = 0;
    window._chatWs = null;
    window._chatWsConectado = false;
    window._chatWsReconnectTimer = null;

    const CHAT_LOJA_ID = '<?= $lojaId ?>';

    // URLs de Endpoints
    const URL_CONVERSAS = '<?= Url::to(['/vendas/canal-comunicacao/get-conversas']) ?>';
    const URL_MENSAGENS = '<?= Url::to(['/vendas/canal-comunicacao/get-mensagens']) ?>';
    const URL_ENVIAR = '<?= Url::to(['/vendas/canal-comunicacao/enviar-mensagem']) ?>';
    const URL_UPLOAD = '<?= Url::to(['/vendas/canal-comunicacao/upload-midia']) ?>';
    const URL_NAO_LIDOS = '<?= Url::to(['/vendas/canal-comunicacao/get-nao-lidos-count']) ?>';
    const URL_LISTAR_SETORES = '<?= Url::to(['/vendas/canal-comunicacao/listar-setores']) ?>';
    const URL_SALVAR_SETOR = '<?= Url::to(['/vendas/canal-comunicacao/salvar-setor']) ?>';
    const URL_EXCLUIR_SETOR = '<?= Url::to(['/vendas/canal-comunicacao/excluir-setor']) ?>';
    const URL_ENCERRAR = '<?= Url::to(['/vendas/canal-comunicacao/encerrar-atendimento']) ?>';
    const URL_LIMPAR = '<?= Url::to(['/vendas/canal-comunicacao/limpar-conversa']) ?>';

    // Utilitário Toast
    window.exibirToastChat = function(msg, tipo = 'sucesso') {
        const toast = document.getElementById('toastChatWhatsApp');
        const icon = document.getElementById('toastChatWhatsAppIcon');
        const text = document.getElementById('toastChatWhatsAppMsg');
        if (!toast) return;

        text.textContent = msg;
        if (tipo === 'sucesso') {
            toast.className = 'fixed top-6 right-6 z-[9999] px-4 py-2.5 rounded-xl text-xs font-bold shadow-xl transition-all duration-300 flex items-center gap-2 pointer-events-none text-white bg-emerald-600';
            icon.textContent = '✅';
        } else if (tipo === 'aviso') {
            toast.className = 'fixed top-6 right-6 z-[9999] px-4 py-2.5 rounded-xl text-xs font-bold shadow-xl transition-all duration-300 flex items-center gap-2 pointer-events-none text-white bg-amber-600';
            icon.textContent = '⚠️';
        } else {
            toast.className = 'fixed top-6 right-6 z-[9999] px-4 py-2.5 rounded-xl text-xs font-bold shadow-xl transition-all duration-300 flex items-center gap-2 pointer-events-none text-white bg-red-600';
            icon.textContent = '❌';
        }
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3500);
    };

    // Abertura e Fechamento do Modal do Canal
    window.abrirModalCanalInterno = function() {
        const modal = document.getElementById('modalChatWhatsApp');
        if (!modal) return;
        modal.classList.remove('hidden');
        carregarConversasChat();
        conectarWebSocketChat();
        iniciarPollingChat();
    };

    window.fecharModalCanalInterno = function() {
        const modal = document.getElementById('modalChatWhatsApp');
        if (!modal) return;
        modal.classList.add('hidden');
        pararPollingChat();
        fecharEmojiPickerChat();
    };

    // Alternar painel de link do hub
    window.toggleLinkHubInfo = function() {
        const p = document.getElementById('painelLinkHubInfo');
        if (p) p.classList.toggle('hidden');
    };

    window.copiarLinkDirectHub = function() {
        const inp = document.getElementById('inputUrlDirectHub');
        if (!inp) return;
        inp.select();
        navigator.clipboard.writeText(inp.value).then(() => {
            exibirToastChat('Link copiado para a área de transferência!', 'sucesso');
        }).catch(() => {
            document.execCommand('copy');
            exibirToastChat('Link copiado!', 'sucesso');
        });
    };

    // Filtros e Pesquisa
    window.selecionarSetorFiltro = function(setorId, btnElement) {
        window._chatSetorFiltro = setorId;
        
        // Atualiza estilo dos chips
        document.querySelectorAll('.chip-setor').forEach(el => {
            el.className = 'chip-setor px-3 py-1 rounded-full text-xs font-bold transition flex items-center gap-1 cursor-pointer bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 whitespace-nowrap';
        });
        if (btnElement) {
            btnElement.className = 'chip-setor px-3 py-1 rounded-full text-xs font-bold transition flex items-center gap-1 cursor-pointer bg-[#008069] text-white shadow-xs whitespace-nowrap';
        }

        carregarConversasChat();
    };

    window.aoBuscarConversas = function(termo) {
        window._chatTermoBusca = termo ? termo.trim() : '';
        const btnLimpar = document.getElementById('btnLimparBuscaChat');
        if (btnLimpar) {
            btnLimpar.classList.toggle('hidden', window._chatTermoBusca.length === 0);
        }
        renderizarListaConversas();
    };

    window.limparBuscaConversas = function() {
        const inp = document.getElementById('chatBuscaInput');
        if (inp) inp.value = '';
        window._chatTermoBusca = '';
        const btnLimpar = document.getElementById('btnLimparBuscaChat');
        if (btnLimpar) btnLimpar.classList.add('hidden');
        renderizarListaConversas();
    };

    // Carregamento de Conversas via AJAX
    window.carregarConversasChat = function(isPolling = false) {
        const url = URL_CONVERSAS + '?setor_id=' + encodeURIComponent(window._chatSetorFiltro) + '&q=' + encodeURIComponent(window._chatTermoBusca);
        
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const novoHash = JSON.stringify((data.conversas || []).map(c => [c.conversa_id, c.ultimo_envio_formatado, c.nao_lidos_count, c.ultima_mensagem]));
                    if (window._chatConversasHash !== novoHash || !isPolling) {
                        window._chatConversasHash = novoHash;
                        window._chatConversas = data.conversas || [];
                        renderizarListaConversas();
                    }
                    atualizarBadgesHeader(data.total_nao_lidos);

                    // Se há conversa ativa aberta, recarrega mensagens em background
                    if (window._chatConversaAtiva && isPolling) {
                        carregarMensagensConversa(window._chatConversaAtiva, true);
                    }
                }
            })
            .catch(err => {
                if (!isPolling) console.error('Erro ao carregar conversas:', err);
            });
    };

    function atualizarBadgesHeader(totalNaoLidos) {
        // Atualiza contador no card do início (se existir)
        const badgeInicio = document.getElementById('badgeCanalInternoInicio');
        if (badgeInicio) {
            if (totalNaoLidos > 0) {
                badgeInicio.textContent = totalNaoLidos + ' nova(s)';
                badgeInicio.classList.remove('hidden');
            } else {
                badgeInicio.classList.add('hidden');
            }
        }
    }

    // Renderização da Lista de Conversas
    function renderizarListaConversas() {
        const container = document.getElementById('listaConversasChat');
        if (!container) return;

        let lista = window._chatConversas;

        // Filtro local de busca adicional
        if (window._chatTermoBusca.length > 0) {
            const t = window._chatTermoBusca.toLowerCase();
            lista = lista.filter(c => {
                return (c.cliente_nome && c.cliente_nome.toLowerCase().includes(t)) ||
                       (c.cliente_telefone && c.cliente_telefone.toLowerCase().includes(t)) ||
                       (c.ultima_mensagem && c.ultima_mensagem.toLowerCase().includes(t));
            });
        }

        if (lista.length === 0) {
            container.innerHTML = `
                <div class="p-8 text-center text-slate-400 space-y-2">
                    <span class="text-3xl block">📭</span>
                    <p class="text-xs font-bold text-slate-600">Nenhuma conversa encontrada</p>
                    <p class="text-[11px] text-slate-400 max-w-xs mx-auto">
                        Compartilhe o link do Direct Hub com seus clientes para receber pedidos e mensagens.
                    </p>
                </div>
            `;
            return;
        }

        let html = '';
        lista.forEach(c => {
            const isAtiva = (window._chatConversaAtiva && window._chatConversaAtiva.conversa_id === c.conversa_id);
            const bgClass = isAtiva ? 'bg-[#f0f2f5]' : 'hover:bg-slate-50';
            const inicial = c.cliente_nome ? c.cliente_nome.charAt(0).toUpperCase() : 'C';
            const setorNome = c.setor ? c.setor.nome : 'Geral';
            const setorIcone = c.setor ? c.setor.icone : '💬';
            
            html += `
                <div onclick="abrirConversa('${c.conversa_id}')" class="flex items-center gap-3 p-3 transition cursor-pointer ${bgClass} select-none">
                    <div class="relative shrink-0">
                        <div class="w-12 h-12 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-base shadow-xs">
                            ${inicial}
                        </div>
                        <span class="absolute -bottom-1 -right-1 text-xs bg-white rounded-full p-0.5 shadow-xs" title="${setorNome}">
                            ${setorIcone}
                        </span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-slate-900 truncate flex items-center gap-1.5">
                                <span>${escapeHtml(c.cliente_nome)}</span>
                            </h4>
                            <span class="text-[10px] text-slate-400 font-medium shrink-0 ml-1">
                                ${c.ultimo_envio_formatado || ''}
                            </span>
                        </div>

                        <div class="flex items-center justify-between mt-1">
                            <p class="text-xs text-slate-500 truncate pr-2">
                                ${escapeHtml(c.ultima_mensagem)}
                            </p>
                            ${c.nao_lidos_count > 0 ? `
                                <span class="shrink-0 bg-[#25d366] text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center shadow-xs">
                                    ${c.nao_lidos_count}
                                </span>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // Abertura de Conversa
    window.abrirConversa = function(conversaId) {
        const conversa = window._chatConversas.find(c => c.conversa_id === conversaId);
        if (!conversa) return;

        window._chatConversaAtiva = conversa;
        window._chatLastMsgTs = 0; // Reset para carga completa da conversa aberta
        fecharEmojiPickerChat();

        // No mobile, oculta a lista e mostra o chat
        const colLista = document.getElementById('colunaListaConversas');
        const colChat = document.getElementById('colunaConversaAtiva');
        if (colLista && colChat) {
            colLista.classList.add('hidden', 'md:flex');
            colChat.classList.remove('hidden');
        }

        // Atualiza Header da Conversa Aberta
        document.getElementById('estadoVazioChat').classList.add('hidden');
        document.getElementById('painelConversaAberta').classList.remove('hidden');

        document.getElementById('conversaAtivaNome').textContent = conversa.cliente_nome;
        document.getElementById('conversaAtivaAvatar').textContent = conversa.cliente_nome ? conversa.cliente_nome.charAt(0).toUpperCase() : 'C';

        const setorBadge = document.getElementById('conversaAtivaSetorBadge');
        if (setorBadge) {
            setorBadge.textContent = (conversa.setor ? (conversa.setor.icone + ' ' + conversa.setor.nome) : '💬 Geral');
        }

        const selSetor = document.getElementById('conversaAtivaSelectSetor');
        if (selSetor) {
            selSetor.value = conversa.setor_id || '';
        }

        // Carrega mensagens
        carregarMensagensConversa(conversa);
        renderizarListaConversas();
    };

    window.voltarParaListaMobile = function() {
        const colLista = document.getElementById('colunaListaConversas');
        const colChat = document.getElementById('colunaConversaAtiva');
        if (colLista && colChat) {
            colLista.classList.remove('hidden');
            colChat.classList.add('hidden', 'md:flex');
        }
        window._chatConversaAtiva = null;
        fecharEmojiPickerChat();
    };

    // Carrega mensagens da conversa
    function carregarMensagensConversa(conversa, isPolling = false) {
        const container = document.getElementById('containerMensagensChat');
        if (!container) return;

        if (!isPolling) {
            container.innerHTML = `
                <div class="text-center py-12 text-slate-400 space-y-2">
                    <span class="text-2xl block animate-spin">⏳</span>
                    <p class="text-xs font-semibold">Carregando histórico da conversa...</p>
                </div>
            `;
        }

        let url = URL_MENSAGENS + '?conversa_id=' + encodeURIComponent(conversa.conversa_id) +
                    (conversa.cliente_id ? '&cliente_id=' + encodeURIComponent(conversa.cliente_id) : '') +
                    (conversa.mesa_id ? '&mesa_id=' + encodeURIComponent(conversa.mesa_id) : '');

        if (isPolling && window._chatLastMsgTs > 0) {
            url += '&since_ts=' + encodeURIComponent(window._chatLastMsgTs);
        }

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.changed === false) {
                        // Nenhuma mensagem nova: não toca no DOM, digitação permanece 100% fluida
                        return;
                    }
                    if (data.last_ts) {
                        window._chatLastMsgTs = data.last_ts;
                    }
                    renderizarMensagens(data.mensagens || [], isPolling);
                    // Zera contador de não lidos localmente
                    conversa.nao_lidos_count = 0;
                    renderizarListaConversas();
                }
            })
            .catch(err => {
                if (!isPolling) console.error('Erro ao carregar mensagens:', err);
            });
    }

    // Renderiza balões estilo WhatsApp
    function renderizarMensagens(mensagens, isPolling = false) {
        const container = document.getElementById('containerMensagensChat');
        if (!container) return;

        if (mensagens.length === 0) {
            container.innerHTML = `
                <div class="text-center py-12 text-slate-400 space-y-2">
                    <span class="text-2xl block">💬</span>
                    <p class="text-xs font-bold text-slate-600">Nenhuma mensagem nesta conversa ainda</p>
                    <p class="text-[11px] text-slate-400">Digite uma resposta abaixo para iniciar o atendimento.</p>
                </div>
            `;
            return;
        }

        // Verifica se o usuário já estava perto do final do scroll antes da atualização
        const scrollProximoFim = (container.scrollHeight - container.scrollTop - container.clientHeight < 120);

        let html = '';
        let ultimaData = '';

        mensagens.forEach(m => {
            // Divisor de Data (ex: Hoje / Ontem / 21/09)
            if (m.data !== ultimaData) {
                ultimaData = m.data;
                html += `
                    <div class="flex justify-center my-3 select-none">
                        <span class="bg-white/80 backdrop-blur-xs text-slate-600 border border-slate-200/60 shadow-xs text-[10px] font-extrabold px-3 py-1 rounded-full uppercase tracking-wider">
                            ${m.data}
                        </span>
                    </div>
                `;
            }

            const isDireita = (m.lado === 'direita');
            const bubbleBg = isDireita ? 'bg-[#d9fdd3] text-slate-900 ml-auto' : 'bg-white text-slate-900 mr-auto';
            const tailRadius = isDireita ? 'rounded-2xl rounded-tr-xs' : 'rounded-2xl rounded-tl-xs';

            html += `
                <div id="msg_chat_${m.id}" class="flex flex-col ${isDireita ? 'items-end' : 'items-start'} max-w-[85%] sm:max-w-[70%]">
                    <div class="${bubbleBg} ${tailRadius} px-3 py-2 shadow-xs border border-black/5 space-y-1 relative">
                        
                        <!-- Identificação do Atendente / Setor -->
                        <div class="flex items-center gap-1.5 text-[11px] font-bold ${isDireita ? 'text-[#008069]' : 'text-teal-800'} select-none">
                            <span>${escapeHtml(m.autor)}</span>
                            ${m.setor_nome ? `
                                <span class="text-[9px] font-extrabold bg-black/5 px-1.5 py-0.2 rounded-md">
                                    ${escapeHtml(m.setor_icone || '')} ${escapeHtml(m.setor_nome)}
                                </span>
                            ` : ''}
                        </div>

                        <!-- Foto / Imagem anexada -->
                        ${m.midia_url ? `
                            <div class="pt-1">
                                <a href="${escapeHtml(m.midia_url)}" target="_blank" class="block">
                                    <img src="${escapeHtml(m.midia_url)}" alt="Mídia" class="max-h-60 rounded-xl object-contain shadow-xs hover:opacity-95 transition">
                                </a>
                            </div>
                        ` : ''}

                        <!-- Texto da Mensagem -->
                        ${m.texto ? `
                            <p class="text-xs sm:text-[13px] leading-relaxed break-words whitespace-pre-wrap">
                                ${escapeHtml(m.texto)}
                            </p>
                        ` : ''}

                        <!-- Horário e Check Duplo WhatsApp -->
                        <div class="flex items-center justify-end gap-1 text-[10px] text-slate-400 select-none pt-0.5">
                            <span>${m.hora || ''}</span>
                            ${isDireita ? `
                                <span class="text-[#53bdeb]" title="Enviado e Entregue">✓✓</span>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;

        // Faz auto-scroll para baixo se for primeira carga ou se o usuário estava perto do final
        if (!isPolling || scrollProximoFim) {
            container.scrollTop = container.scrollHeight;
        }
    }

    // Tecla Enter no chat
    window.aoPressionarTeclaChat = function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            enviarMensagemChat();
        }
    };

    // Enviar mensagem
    window.enviarMensagemChat = function() {
        if (!window._chatConversaAtiva) {
            exibirToastChat('Selecione uma conversa primeiro.', 'aviso');
            return;
        }

        const input = document.getElementById('inputTextoMensagemChat');
        const texto = input ? input.value.trim() : '';
        const midiaUrl = window._chatFotoUrlPendente;

        if (!texto && !midiaUrl) {
            return;
        }

        const btn = document.getElementById('btnEnviarMensagemChat');
        if (btn) btn.disabled = true;

        const payload = {
            cliente_id: window._chatConversaAtiva.cliente_id || null,
            mesa_id: window._chatConversaAtiva.mesa_id || null,
            setor_id: window._chatConversaAtiva.setor_id || null,
            mensagem: texto,
            midia_url: midiaUrl || null,
        };

        fetch(URL_ENVIAR, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (btn) btn.disabled = false;
            if (data.success) {
                if (input) input.value = '';
                cancelarEnvioFoto();
                fecharEmojiPickerChat();
                // Reset ts para buscar mensagens atualizadas com a nova mensagem enviada
                window._chatLastMsgTs = 0;
                carregarMensagensConversa(window._chatConversaAtiva);
                carregarConversasChat();
            } else {
                exibirToastChat(data.message || 'Erro ao enviar mensagem', 'erro');
            }
        })
        .catch(err => {
            if (btn) btn.disabled = false;
            console.error('Erro no envio:', err);
            exibirToastChat('Falha na comunicação com o servidor.', 'erro');
        });
    };

    // Anexo de Foto
    window.aoSelecionarFotoChat = function(inp) {
        if (!inp.files || !inp.files[0]) return;
        const file = inp.files[0];

        const formData = new FormData();
        formData.append('foto', file);

        exibirToastChat('Enviando foto para o servidor...', 'aviso');

        fetch(URL_UPLOAD, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window._chatFotoUrlPendente = data.url;
                const container = document.getElementById('previewFotoContainer');
                const img = document.getElementById('previewFotoImg');
                const nome = document.getElementById('previewFotoNome');
                if (container && img) {
                    img.src = data.url;
                    if (nome) nome.textContent = file.name;
                    container.classList.remove('hidden');
                }
                exibirToastChat('Foto carregada! Clique em Enviar.', 'sucesso');
            } else {
                exibirToastChat(data.message || 'Erro no upload da foto.', 'erro');
            }
        })
        .catch(err => {
            console.error('Erro upload foto:', err);
            exibirToastChat('Erro ao fazer upload da imagem.', 'erro');
        });
    };

    window.cancelarEnvioFoto = function() {
        window._chatFotoUrlPendente = null;
        const container = document.getElementById('previewFotoContainer');
        if (container) container.classList.add('hidden');
        const inp = document.getElementById('inputUploadMidiaChat');
        if (inp) inp.value = '';
    };

    // Mudança de setor na conversa aberta
    window.aoMudarSetorConversa = function(novoSetorId) {
        if (!window._chatConversaAtiva) return;
        window._chatConversaAtiva.setor_id = novoSetorId || null;
        exibirToastChat('Setor alterado para esta conversa.', 'sucesso');
    };

    // Encerrar Atendimento
    window.encerrarAtendimentoAtivo = function() {
        if (!window._chatConversaAtiva) {
            exibirToastChat('Selecione uma conversa primeiro.', 'aviso');
            return;
        }

        if (!confirm('Deseja realmente encerrar este atendimento? Todas as mensagens serão marcadas como lidas e uma notificação de encerramento será registrada.')) {
            return;
        }

        const payload = {
            conversa_id: window._chatConversaAtiva.conversa_id,
            cliente_id: window._chatConversaAtiva.cliente_id || null,
            mesa_id: window._chatConversaAtiva.mesa_id || null
        };

        fetch(URL_ENCERRAR, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                exibirToastChat(data.message || 'Atendimento encerrado com sucesso!', 'sucesso');
                window._chatLastMsgTs = 0;
                carregarMensagensConversa(window._chatConversaAtiva);
                carregarConversasChat();
            } else {
                exibirToastChat(data.message || 'Erro ao encerrar atendimento.', 'erro');
            }
        })
        .catch(err => {
            console.error('Erro ao encerrar atendimento:', err);
            exibirToastChat('Erro ao conectar com o servidor.', 'erro');
        });
    };

    // Limpar Conversa
    window.limparConversaAtiva = function() {
        if (!window._chatConversaAtiva) {
            exibirToastChat('Selecione uma conversa primeiro.', 'aviso');
            return;
        }

        if (!confirm('ATENÇÃO: Deseja apagar todas as mensagens desta conversa? Esta ação não pode ser desfeita.')) {
            return;
        }

        const payload = {
            conversa_id: window._chatConversaAtiva.conversa_id,
            cliente_id: window._chatConversaAtiva.cliente_id || null,
            mesa_id: window._chatConversaAtiva.mesa_id || null
        };

        fetch(URL_LIMPAR, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                exibirToastChat(data.message || 'Conversa limpa com sucesso!', 'sucesso');
                window._chatLastMsgTs = 0;
                const container = document.getElementById('containerMensagensChat');
                if (container) {
                    container.innerHTML = `
                        <div class="text-center py-12 text-slate-400 space-y-2">
                            <span class="text-2xl block">💬</span>
                            <p class="text-xs font-bold text-slate-600">Nenhuma mensagem nesta conversa ainda</p>
                            <p class="text-[11px] text-slate-400">Histórico de mensagens foi apagado.</p>
                        </div>
                    `;
                }
                window._chatConversasHash = '';
                carregarConversasChat();
            } else {
                exibirToastChat(data.message || 'Erro ao limpar conversa.', 'erro');
            }
        })
        .catch(err => {
            console.error('Erro ao limpar conversa:', err);
            exibirToastChat('Erro ao conectar com o servidor.', 'erro');
        });
    };

    // =========================================================================
    // WEBSOCKET: CONEXÃO EM TEMPO REAL E SINCRONIZAÇÃO HÍBRIDA
    // =========================================================================
    function conectarWebSocketChat() {
        if (window._chatWs && (window._chatWs.readyState === WebSocket.OPEN || window._chatWs.readyState === WebSocket.CONNECTING)) {
            return;
        }

        try {
            const wsProtocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const wsHost = window.location.host;
            const wsUrl = `${wsProtocol}//${wsHost}/ws/?loja_id=${encodeURIComponent(CHAT_LOJA_ID)}`;

            window._chatWs = new WebSocket(wsUrl);

            window._chatWs.onopen = function() {
                window._chatWsConectado = true;
                atualizarIndicadorWsStatus(true);
                iniciarPollingChat();
                carregarConversasChat(true);
            };

            window._chatWs.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    processarEventoWebSocket(data);
                } catch (e) {
                    console.error('Erro ao processar evento WebSocket:', e);
                }
            };

            window._chatWs.onerror = function() {
                window._chatWsConectado = false;
                atualizarIndicadorWsStatus(false);
            };

            window._chatWs.onclose = function() {
                window._chatWsConectado = false;
                atualizarIndicadorWsStatus(false);
                iniciarPollingChat();
                // Tenta reconectar a cada 5 segundos
                if (window._chatWsReconnectTimer) clearTimeout(window._chatWsReconnectTimer);
                window._chatWsReconnectTimer = setTimeout(() => {
                    conectarWebSocketChat();
                }, 5000);
            };
        } catch (e) {
            console.error('Falha ao inicializar WebSocket:', e);
            window._chatWsConectado = false;
            atualizarIndicadorWsStatus(false);
        }
    }

    function atualizarIndicadorWsStatus(conectado) {
        const ind = document.getElementById('chatWsStatusIndicator');
        const txt = document.getElementById('chatWsStatusText');
        if (ind) {
            if (conectado) {
                ind.className = 'w-2 h-2 rounded-full bg-emerald-400 animate-pulse';
                ind.title = 'Conectado em tempo real via WebSocket';
            } else {
                ind.className = 'w-2 h-2 rounded-full bg-amber-400';
                ind.title = 'Modo sincronizado (fallback HTTP ativo)';
            }
        }
        if (txt) {
            txt.textContent = conectado 
                ? 'Tempo Real Ativo • Isolado por Loja' 
                : 'Atendimento por Setores • Isolado por Loja';
        }
    }

    function processarEventoWebSocket(data) {
        if (!data || !data.type) return;

        if (data.type === 'atendimento_encerrado') {
            if (window._chatConversaAtiva && window._chatConversaAtiva.conversa_id === data.conversa_id) {
                window._chatLastMsgTs = 0;
                carregarMensagensConversa(window._chatConversaAtiva, true);
            }
            carregarConversasChat(true);
            return;
        }

        if (data.type === 'conversa_limpa') {
            if (window._chatConversaAtiva && window._chatConversaAtiva.conversa_id === data.conversa_id) {
                window._chatLastMsgTs = 0;
                const container = document.getElementById('containerMensagensChat');
                if (container) {
                    container.innerHTML = `
                        <div class="text-center py-12 text-slate-400 space-y-2">
                            <span class="text-2xl block">💬</span>
                            <p class="text-xs font-bold text-slate-600">Nenhuma mensagem nesta conversa ainda</p>
                            <p class="text-[11px] text-slate-400">Histórico de mensagens foi apagado.</p>
                        </div>
                    `;
                }
            }
            carregarConversasChat(true);
            return;
        }

        if (data.type === 'nova_mensagem') {
            const item = data.item;
            const conversaId = data.conversa_id;

            if (window._chatConversaAtiva && window._chatConversaAtiva.conversa_id === conversaId) {
                anexarMensagemEmTempoReal(item);
            }

            atualizarConversaNaLista(conversaId, item);
        }
    }

    function anexarMensagemEmTempoReal(item) {
        if (!item || !item.id) return;
        const container = document.getElementById('containerMensagensChat');
        if (!container) return;

        // Evita duplicidade se a mensagem já estiver no DOM
        if (document.getElementById('msg_chat_' + item.id)) {
            return;
        }

        // Limpa estado vazio ou indicador de carregamento
        if (container.querySelector('.animate-spin') || container.querySelector('span.text-2xl')) {
            container.innerHTML = '';
        }

        const isDireita = (item.lado === 'direita');
        const bubbleBg = isDireita ? 'bg-[#d9fdd3] text-slate-900 ml-auto' : 'bg-white text-slate-900 mr-auto';
        const tailRadius = isDireita ? 'rounded-2xl rounded-tr-xs' : 'rounded-2xl rounded-tl-xs';

        const wrapper = document.createElement('div');
        wrapper.id = 'msg_chat_' + item.id;
        wrapper.className = `flex flex-col ${isDireita ? 'items-end' : 'items-start'} max-w-[85%] sm:max-w-[70%]`;

        wrapper.innerHTML = `
            <div class="${bubbleBg} ${tailRadius} px-3 py-2 shadow-xs border border-black/5 space-y-1 relative">
                <div class="flex items-center gap-1.5 text-[11px] font-bold ${isDireita ? 'text-[#008069]' : 'text-teal-800'} select-none">
                    <span>${escapeHtml(item.autor)}</span>
                    ${item.setor_nome ? `
                        <span class="text-[9px] font-extrabold bg-black/5 px-1.5 py-0.2 rounded-md">
                            ${escapeHtml(item.setor_icone || '')} ${escapeHtml(item.setor_nome)}
                        </span>
                    ` : ''}
                </div>

                ${item.midia_url ? `
                    <div class="pt-1">
                        <a href="${escapeHtml(item.midia_url)}" target="_blank" class="block">
                            <img src="${escapeHtml(item.midia_url)}" alt="Mídia" class="max-h-60 rounded-xl object-contain shadow-xs hover:opacity-95 transition">
                        </a>
                    </div>
                ` : ''}

                ${item.texto ? `
                    <p class="text-xs sm:text-[13px] leading-relaxed break-words whitespace-pre-wrap">
                        ${escapeHtml(item.texto)}
                    </p>
                ` : ''}

                <div class="flex items-center justify-end gap-1 text-[10px] text-slate-400 select-none pt-0.5">
                    <span>${item.hora || ''}</span>
                    ${isDireita ? `
                        <span class="text-[#53bdeb]" title="Enviado e Entregue">✓✓</span>
                    ` : ''}
                </div>
            </div>
        `;

        container.appendChild(wrapper);
        container.scrollTop = container.scrollHeight;
    }

    function atualizarConversaNaLista(conversaId, item) {
        const conv = window._chatConversas.find(c => c.conversa_id === conversaId);
        if (conv) {
            conv.ultima_mensagem = item.texto || (item.midia_url ? '📷 Foto enviada' : '');
            conv.ultimo_envio_formatado = item.hora || 'Agora';
            if (!window._chatConversaAtiva || window._chatConversaAtiva.conversa_id !== conversaId) {
                conv.nao_lidos_count = (conv.nao_lidos_count || 0) + 1;
            }
            renderizarListaConversas();
        } else {
            carregarConversasChat(true);
        }
    }

    // Polling inteligente: quando WebSocket está ativo, atua como heartbeat leve (30s);
    // Caso contrário, opera no fallback de 8s.
    function iniciarPollingChat() {
        pararPollingChat();
        const intervalo = window._chatWsConectado ? 30000 : 8000;
        window._chatPollingTimer = setInterval(() => {
            if (document.hidden) return;
            const modal = document.getElementById('modalChatWhatsApp');
            if (modal && !modal.classList.contains('hidden')) {
                carregarConversasChat(true);
            }
        }, intervalo);
    }

    function pararPollingChat() {
        if (window._chatPollingTimer) {
            clearInterval(window._chatPollingTimer);
            window._chatPollingTimer = null;
        }
    }

    // Retoma polling imediato ao voltar para a aba
    document.addEventListener('visibilitychange', () => {
        const modal = document.getElementById('modalChatWhatsApp');
        if (!modal || modal.classList.contains('hidden')) return;
        if (!document.hidden) {
            carregarConversasChat(true);
        }
    });

    // =========================================================================
    // GERENCIADOR DE SETORES (APENAS DONO DA LOJA)
    // =========================================================================
    <?php if ($ehDono): ?>
    window.abrirGerenciadorSetores = function() {
        const modal = document.getElementById('modalGerenciarSetores');
        if (!modal) return;
        modal.classList.remove('hidden');
        carregarListaSetoresConfig();
    };

    window.fecharGerenciadorSetores = function() {
        const modal = document.getElementById('modalGerenciarSetores');
        if (!modal) return;
        modal.classList.add('hidden');
        fecharFormSetor();
    };

    window.carregarListaSetoresConfig = function() {
        const container = document.getElementById('listaSetoresConfig');
        if (!container) return;

        fetch(URL_LISTAR_SETORES)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window._chatColaboradoresStore = data.colaboradores || [];
                    renderizarSetoresConfig(data.setores || []);
                }
            })
            .catch(err => console.error('Erro ao listar setores:', err));
    };

    function renderizarSetoresConfig(setores) {
        const container = document.getElementById('listaSetoresConfig');
        if (!container) return;

        if (setores.length === 0) {
            container.innerHTML = '<div class="text-center py-6 text-slate-400 text-xs">Nenhum setor cadastrado. Crie um novo acima.</div>';
            return;
        }

        let html = '';
        setores.forEach(s => {
            html += `
                <div class="flex items-center justify-between p-3.5 bg-white rounded-2xl border border-slate-200 shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-xl shadow-inner">
                            ${s.icone || '💬'}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h5 class="text-xs font-bold text-slate-900">${escapeHtml(s.nome)}</h5>
                                <span class="text-[10px] font-black px-2 py-0.2 rounded-full ${s.ativo ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'}">
                                    ${s.ativo ? 'Ativo' : 'Inativo'}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                ${escapeHtml(s.descricao || 'Sem descrição')} &bull; 
                                <span class="text-teal-700 font-bold">${s.total_colaboradores} colaborador(es)</span>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick='editarSetorConfig(${JSON.stringify(s)})' class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg cursor-pointer">
                            Editar
                        </button>
                        <button type="button" onclick="excluirSetorConfig('${s.id}')" class="px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold rounded-lg cursor-pointer">
                            Excluir
                        </button>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    window.abrirFormNovoSetor = function() {
        document.getElementById('formSetorTitulo').textContent = 'Criar Novo Setor';
        document.getElementById('inputSetorId').value = '';
        document.getElementById('inputSetorNome').value = '';
        document.getElementById('inputSetorDescricao').value = '';
        document.getElementById('inputSetorIcone').value = '💬';
        
        renderizarColaboradoresCheck([]);
        document.getElementById('formSetorContainer').classList.remove('hidden');
    };

    window.editarSetorConfig = function(s) {
        document.getElementById('formSetorTitulo').textContent = 'Editar Setor: ' + s.nome;
        document.getElementById('inputSetorId').value = s.id;
        document.getElementById('inputSetorNome').value = s.nome;
        document.getElementById('inputSetorDescricao').value = s.descricao || '';
        document.getElementById('inputSetorIcone').value = s.icone || '💬';

        renderizarColaboradoresCheck(s.colaborador_ids || []);
        document.getElementById('formSetorContainer').classList.remove('hidden');
    };

    window.fecharFormSetor = function() {
        const form = document.getElementById('formSetorContainer');
        if (form) form.classList.add('hidden');
    };

    function renderizarColaboradoresCheck(selecionados = []) {
        const container = document.getElementById('containerColaboradoresCheck');
        if (!container) return;

        if (window._chatColaboradoresStore.length === 0) {
            container.innerHTML = '<div class="text-xs text-slate-400">Nenhum outro colaborador ativo na loja.</div>';
            return;
        }

        let html = '';
        window._chatColaboradoresStore.forEach(c => {
            const checked = selecionados.includes(c.id) ? 'checked' : '';
            html += `
                <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer select-none hover:text-slate-900">
                    <input type="checkbox" name="setor_colaboradores[]" value="${c.id}" ${checked} class="rounded border-slate-300 text-[#008069] focus:ring-[#008069]">
                    <span class="font-bold">${escapeHtml(c.nome)}</span>
                    <span class="text-[10px] text-slate-400">(${escapeHtml(c.funcao)})</span>
                </label>
            `;
        });

        container.innerHTML = html;
    }

    window.salvarSetorConfig = function() {
        const id = document.getElementById('inputSetorId').value;
        const nome = document.getElementById('inputSetorNome').value.trim();
        const descricao = document.getElementById('inputSetorDescricao').value.trim();
        const icone = document.getElementById('inputSetorIcone').value;

        if (!nome) {
            exibirToastChat('Por favor, informe o nome do setor.', 'aviso');
            return;
        }

        const checks = document.querySelectorAll('input[name="setor_colaboradores[]"]:checked');
        const colaboradorIds = Array.from(checks).map(c => c.value);

        const payload = {
            id: id || null,
            nome: nome,
            descricao: descricao,
            icone: icone,
            ativo: true,
            colaborador_ids: colaboradorIds
        };

        fetch(URL_SALVAR_SETOR, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                exibirToastChat('Setor salvo com sucesso!', 'sucesso');
                fecharFormSetor();
                carregarListaSetoresConfig();
                carregarConversasChat();
            } else {
                exibirToastChat(data.message || 'Erro ao salvar setor.', 'erro');
            }
        })
        .catch(err => {
            console.error('Erro ao salvar setor:', err);
            exibirToastChat('Erro de conexão ao salvar setor.', 'erro');
        });
    };

    window.excluirSetorConfig = function(id) {
        if (!confirm('Deseja realmente remover ou desativar este setor?')) return;

        fetch(URL_EXCLUIR_SETOR, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>'
            },
            body: JSON.stringify({ id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                exibirToastChat(data.message || 'Setor removido.', 'sucesso');
                carregarListaSetoresConfig();
                carregarConversasChat();
            } else {
                exibirToastChat(data.message || 'Erro ao remover setor.', 'erro');
            }
        })
        .catch(err => {
            console.error('Erro ao excluir setor:', err);
            exibirToastChat('Erro ao remover setor.', 'erro');
        });
    };
    <?php endif; ?>

    // =========================================================================
    // SELETOR E GERENCIADOR DE EMOJIS (ESTILO WHATSAPP)
    // =========================================================================
    const EMOJIS_DB = {
        rostos: [
            { e: '😀', t: 'sorriso dentes alegre feliz' },
            { e: '😃', t: 'sorriso grande alegre' },
            { e: '😄', t: 'sorridente olhos felizes' },
            { e: '😁', t: 'sorriso largo' },
            { e: '😆', t: 'risada gargalhada' },
            { e: '😅', t: 'suor riso alivio' },
            { e: '😂', t: 'chorando de rir risada lagrimas' },
            { e: '🤣', t: 'rolando de rir gargalhada' },
            { e: '😊', t: 'sorriso simpatico bochechas' },
            { e: '😇', t: 'anjo inocente' },
            { e: '🙂', t: 'sorriso leve' },
            { e: '🙃', t: 'cabeca para baixo ironia' },
            { e: '😉', t: 'piscadinha flerte' },
            { e: '😌', t: 'aliviado tranquilo paz' },
            { e: '😍', t: 'olhos coracao apaixonado amor' },
            { e: '🥰', t: 'carinho amor apaixonado' },
            { e: '😘', t: 'beijo coracao amor carinho' },
            { e: '😗', t: 'beijinho' },
            { e: '😙', t: 'beijo sorriso' },
            { e: '😚', t: 'beijo bochechas' },
            { e: '😋', t: 'delicia gostoso comida fome' },
            { e: '😛', t: 'lingua' },
            { e: '😜', t: 'lingua piscadinha brincadeira' },
            { e: '🤪', t: 'louco biruta doido' },
            { e: '😝', t: 'lingua olhos fechados' },
            { e: '🤑', t: 'dinheiro cifrao rico venda' },
            { e: '🤗', t: 'abraco carinho' },
            { e: '🤭', t: 'mao na boca risinho' },
            { e: '🤫', t: 'silencio segredo' },
            { e: '🤔', t: 'pensando duvida reflexao' },
            { e: '🤐', t: 'boca fechada ziper segredo' },
            { e: '🤨', t: 'sobrancelha desconfiado duvida' },
            { e: '😐', t: 'neutro sem expressao' },
            { e: '😑', t: 'inexpressivo serio' },
            { e: '😶', t: 'sem boca mudo' },
            { e: '😏', t: 'sorriso maroto sarcasmo' },
            { e: '😒', t: 'descontente desanimo' },
            { e: '🙄', t: 'olhos revirados saco cheio' },
            { e: '😬', t: 'careta desconforto tenso' },
            { e: '🤥', t: 'mentira pinocchio nariz' },
            { e: '😌', t: 'calmo sereno' },
            { e: '😔', t: 'triste cabisbaixo pensativo' },
            { e: '😪', t: 'sonolento cansado sono' },
            { e: '🤤', t: 'babando delicia vontade' },
            { e: '😴', t: 'dormindo sono' },
            { e: '😷', t: 'mascara saude gripe' },
            { e: '🤒', t: 'doente febre termometro' },
            { e: '🤕', t: 'machucado curativo faixa' },
            { e: '🤢', t: 'enjoado nojo vomito' },
            { e: '🤮', t: 'vomitando doente' },
            { e: '🤧', t: 'espirro lenco gripe' },
            { e: '🥵', t: 'calor quente vermelho' },
            { e: '🥶', t: 'frio gelado congelando' },
            { e: '🥴', t: 'tonto embriagado desorientado' },
            { e: '😵', t: 'tonto atordoado zonzo' },
            { e: '🤯', t: 'mente explodindo impressionado choque' },
            { e: '🤠', t: 'cowboy chapeu sertanejo' },
            { e: '🥳', t: 'festa comemoracao aniversario confete' },
            { e: '😎', t: 'oculos escuros legal top estilo' },
            { e: '🤓', t: 'nerd oculos inteligente' },
            { e: '🧐', t: 'monoculo observador atento' },
            { e: '😕', t: 'confuso incerto' },
            { e: '😟', t: 'preocupado apreensivo' },
            { e: '🙁', t: 'levemente triste' },
            { e: '😮', t: 'boca aberta surpreso' },
            { e: '😯', t: 'surpreso surpresa' },
            { e: '😲', t: 'chocado espanto' },
            { e: '😳', t: 'envergonhado corado timido' },
            { e: '🥺', t: 'olhos pidoes por favor suplica' },
            { e: '😦', t: 'boca aberta aflito' },
            { e: '😧', t: 'angustiado sofrendo' },
            { e: '😨', t: 'assustado medo' },
            { e: '😰', t: 'ansioso suor medo nervoso' },
            { e: '😥', t: 'triste alivio ufa' },
            { e: '😢', t: 'chorando lagrima triste' },
            { e: '😭', t: 'choro desespero chorando muito' },
            { e: '😱', t: 'grito medo panico' },
            { e: '😖', t: 'desesperado chateado' },
            { e: '😣', t: 'perseverante dor' },
            { e: '😞', t: 'decepcionado desapontado' },
            { e: '😓', t: 'suor frio cansaco' },
            { e: '😩', t: 'exausto cansaco' },
            { e: '😫', t: 'cansado fadigado' },
            { e: '🥱', t: 'bocejo sono tedio' },
            { e: '😤', t: 'triunfo bufando bravo' },
            { e: '😡', t: 'bravo irritado raiva vermelho' },
            { e: '😠', t: 'zangado raiva' },
            { e: '🤬', t: 'xingamento palavrao furioso' },
            { e: '😈', t: 'diabinho sorriso travessura' },
            { e: '👿', t: 'diabo bravo malvado' },
            { e: '💀', t: 'caveira esqueleto morto morri' },
            { e: '💩', t: 'coco fezes cocô' },
            { e: '🤡', t: 'palhaco circo bobeira' },
            { e: '👻', t: 'fantasma halloween' },
            { e: '👽', t: 'alien et alienigena' },
            { e: '🤖', t: 'robo bot tecnologia' }
        ],
        gestos: [
            { e: '👍', t: 'positivo joinha sim concordo ok legal top' },
            { e: '👎', t: 'negativo desaprovo nao discordo ruim' },
            { e: '👌', t: 'perfeito ok tudo certo excelente' },
            { e: '✌️', t: 'paz amor vitoria dois' },
            { e: '🤞', t: 'figas dedos cruzados sorte torcida' },
            { e: '🤟', t: 'te amo libras rock' },
            { e: '🤘', t: 'rock metal maneiro' },
            { e: '🤙', t: 'shaka hangloose liga pra mim suave' },
            { e: '👈', t: 'apontando esquerda' },
            { e: '👉', t: 'apontando direita olha veja aqui' },
            { e: '👆', t: 'apontando cima acima veja' },
            { e: '👇', t: 'apontando baixo abaixo clique aqui' },
            { e: '☝️', t: 'um dedo indicador atencao' },
            { e: '✋', t: 'pare mao aberta cinco calma' },
            { e: '🤚', t: 'costas da mao pare' },
            { e: '🖐️', t: 'cinco dedos espalmados' },
            { e: '🖖', t: 'saudacao vulcana vida longa' },
            { e: '👋', t: 'tchau ola aceno oi adeus' },
            { e: '🤝', t: 'aperto de maos acordo fechado parceria negocio' },
            { e: '🙏', t: 'oracao amem gratidao obrigado por favor' },
            { e: '👏', t: 'palmas parabens aplausos aprovado' },
            { e: '🙌', t: 'maos para o alto celebracao amem gloria' },
            { e: '👐', t: 'maos abertas acolhimento' },
            { e: '🤲', t: 'palmas juntas suplica' },
            { e: '💪', t: 'forca musculo treino foco forte' },
            { e: '👊', t: 'soco murro toque de mao parceria' },
            { e: '✊', t: 'punho fechado forca resistencia' },
            { e: '🤛', t: 'punho esquerda toque' },
            { e: '🤜', t: 'punho direita toque' },
            { e: '✍️', t: 'escrevendo caneta assinatura contrato' },
            { e: '🤳', t: 'selfie foto celular' },
            { e: '👀', t: 'olhos olhando atento vigilante' },
            { e: '👁️', t: 'olho visao' },
            { e: '👂', t: 'ouvido ouvindo escuta' },
            { e: '👃', t: 'nariz cheiro' },
            { e: '🧠', t: 'cerebro inteligente pensar' },
            { e: '🗣️', t: 'falando voz comunicacao' },
            { e: '👤', t: 'pessoa usuario perfil cliente' },
            { e: '👥', t: 'pessoas usuarios grupo equipe' },
            { e: '💃', t: 'dancando mulher festa alegria' },
            { e: '🕺', t: 'dancando homem balada' }
        ],
        coracoes: [
            { e: '❤️', t: 'coracao vermelho amor carinho paixao' },
            { e: '🧡', t: 'coracao laranja energia amizade' },
            { e: '💛', t: 'coracao amarelo luz alegria ouro' },
            { e: '💚', t: 'coracao verde esperanca saude' },
            { e: '💙', t: 'coracao azul confianca lealdade' },
            { e: '💜', t: 'coracao roxo violeta afeto' },
            { e: '🖤', t: 'coracao preto estilo elegancia luto' },
            { e: '🤍', t: 'coracao branco paz pureza' },
            { e: '🤎', t: 'coracao marrom chocolate' },
            { e: '💔', t: 'coracao partido desilusao tristeza' },
            { e: '❣️', t: 'coracao exclamacao atencao amor' },
            { e: '💕', t: 'dois coracoes amor flerte' },
            { e: '💞', t: 'coracoes girando paixao' },
            { e: '💓', t: 'coracao batendo pulsando' },
            { e: '💗', t: 'coracao crescendo emocao' },
            { e: '💖', t: 'coracao brilhando brilho carinho' },
            { e: '💘', t: 'coracao com flecha cupido paixao' },
            { e: '💝', t: 'coracao presente laco fita' },
            { e: '✨', t: 'brilho estrelas novidade novo' },
            { e: '⭐', t: 'estrela destaque avaliacao nota' },
            { e: '🌟', t: 'estrela brilhante top ouro' },
            { e: '💫', t: 'vertigem estrela cadente' },
            { e: '🔥', t: 'fogo chama quente sucesso promocao oferta' },
            { e: '💥', t: 'explosao choque impacto' },
            { e: '💯', t: 'cem nota 100 perfeito cem por cento' },
            { e: '✅', t: 'check verde confirmado correto sucesso ok sim' },
            { e: '✔️', t: 'check marca feito' },
            { e: '❌', t: 'x vermelho cancelado errado nao erro' },
            { e: '⚠️', t: 'aviso alerta perigo atencao cuidado' },
            { e: '🚨', t: 'sirene policia emergencia urgente alarme' },
            { e: '🔔', t: 'sino notificacao aviso alerta lembrete' },
            { e: '📢', t: 'megafone anuncio novidade comunicacao aviso' },
            { e: '📣', t: 'alto-falante comunicado aviso novidade' },
            { e: '💬', t: 'balao mensagem chat conversa direct' },
            { e: '💭', t: 'balao pensamento pensando' },
            { e: '🎯', t: 'alvo objetivo meta acertou' },
            { e: '🎉', t: 'festa confete comemoracao parabens sucesso' },
            { e: '🎊', t: 'confete celebracao carnaval' },
            { e: '🏆', t: 'trofeu campeao vitoria primeiro lugar' },
            { e: '🥇', t: 'medalha ouro primeiro' },
            { e: '🥈', t: 'medalha prata segundo' },
            { e: '🥉', t: 'medalha bronze terceiro' }
        ],
        comida: [
            { e: '🍕', t: 'pizza lanche comida queijo fatia' },
            { e: '🍔', t: 'hamburguer burger lanche carne fastfood' },
            { e: '🍟', t: 'batata frita lanche' },
            { e: '🌭', t: 'cachorro quente hotdog lanche' },
            { e: '🥪', t: 'sanduiche lanche natural' },
            { e: '🌮', t: 'taco comida mexicana' },
            { e: '🌯', t: 'burrito comida' },
            { e: '🥗', t: 'salada comida saudavel fitness verde' },
            { e: '🥩', t: 'carne bife churrasco alcatra' },
            { e: '🍗', t: 'frango coxa assado comida' },
            { e: '🍖', t: 'carne no osso costela' },
            { e: '🍣', t: 'sushi comida japonesa peixe salmao' },
            { e: '🍱', t: 'bento comida japonesa prato' },
            { e: '🥟', t: 'guioza pastel salgado' },
            { e: '🍜', t: 'lamen macarrao sopa miojo' },
            { e: '🍲', t: 'sopa caldo panela' },
            { e: '🍝', t: 'espaguete massa macarrao italiano' },
            { e: '🍞', t: 'pao padaria cafe fatiado' },
            { e: '🥐', t: 'croissant padaria cafe massa folhada' },
            { e: '🥖', t: 'baguete pao frances' },
            { e: '🧀', t: 'queijo laticinio' },
            { e: '🥚', t: 'ovo caipira frito' },
            { e: '🍳', t: 'frigideira ovo frito cafe' },
            { e: '🥞', t: 'panquecas cafe da manha doce' },
            { e: '🧇', t: 'waffle doce' },
            { e: '🎂', t: 'bolo aniversario festa comemoracao parabens' },
            { e: '🍰', t: 'fatia de bolo torta doce' },
            { e: '🧁', t: 'cupcake bolinho doce' },
            { e: '🍫', t: 'chocolate doce bombom barra' },
            { e: '🍬', t: 'bala doce' },
            { e: '🍭', t: 'pirulito doce confeito' },
            { e: '🍩', t: 'donut rosquinha doce' },
            { e: '🍪', t: 'cookie biscoito bolacha doce' },
            { e: '🍦', t: 'sorvete casquinha sobremesa gelado' },
            { e: '🍧', t: 'raspadinha gelo' },
            { e: '🍨', t: 'taca de sorvete sobremesa' },
            { e: '☕', t: 'cafe cafezinho xicara quente pausa' },
            { e: '🧃', t: 'suco caixinha bebida refresco' },
            { e: '🥤', t: 'copo com canudo refrigerante suco' },
            { e: '🍺', t: 'cerveja chopp caneco bar happyhour' },
            { e: '🍻', t: 'cervejas brinde chopp festa bar' },
            { e: '🥂', t: 'brinde champanhe tacas comemoracao festa' },
            { e: '🍷', t: 'vinho taca tinto bar jantar' },
            { e: '🍾', t: 'champanhe espumante garrafa estouro festa' },
            { e: '🍹', t: 'drink coquetel praia verao bebida' },
            { e: '🧊', t: 'gelo cubo gelado frio' }
        ],
        comercio: [
            { e: '💰', t: 'saco de dinheiro grana valor preco pagamento' },
            { e: '💵', t: 'nota de dolar dinheiro cedula nota valor' },
            { e: '💳', t: 'cartao de credito debito pagamento maquininha parcelado' },
            { e: '🪙', t: 'moeda troco centavos dinheiro' },
            { e: '💸', t: 'dinheiro voando gasto promocao desconto' },
            { e: '🛍️', t: 'sacolas de compras shopping loja pedido' },
            { e: '🛒', t: 'carrinho de compras mercado pedido itens' },
            { e: '🏷️', t: 'etiqueta de preco desconto oferta promocao' },
            { e: '📦', t: 'caixa encomenda pacote envio entrega correios' },
            { e: '🚚', t: 'caminhao entrega frete envio transportadora' },
            { e: '🛵', t: 'moto motoboy entrega delivery rapido motinha' },
            { e: '🎁', t: 'presente brinde mimo surpresa pacote' },
            { e: '🧾', t: 'recibo cupom fiscal nota comprovante fatura' },
            { e: '📱', t: 'celular smartphone zap whatsapp contato telefone' },
            { e: '💻', t: 'notebook computador pc tela online' },
            { e: '📞', t: 'telefone ligacao chamada contato' },
            { e: '✉️', t: 'carta email envelope mensagem correspondencia' },
            { e: '📧', t: 'email eletronico correio contato' },
            { e: '📍', t: 'pin localizacao mapa endereco onde fica' },
            { e: '📌', t: 'alfinete fixado importante lembrete aviso' },
            { e: '🕒', t: 'relogio horas tempo horario expediente funcionamento' },
            { e: '📅', t: 'calendario data agendamento dia mes ano' },
            { e: '📆', t: 'calendario folhinha prazo agendamento' },
            { e: '📊', t: 'grafico barras relatorio estatistica vendas' },
            { e: '📈', t: 'grafico subindo crescimento lucro alta aumento' },
            { e: '📉', t: 'grafico descendo queda baixa reducao' },
            { e: '🔒', t: 'cadeado fechado seguranca protegido' },
            { e: '🔓', t: 'cadeado aberto liberado desbloqueado' },
            { e: '🔑', t: 'chave acesso entrada segredo liberado' },
            { e: '🚀', t: 'foguete lancamento rapido decolou agilidade sucesso' },
            { e: '💎', t: 'diamante joia precioso valioso qualidade top vip' },
            { e: '👑', t: 'coroa rei rainha premium lider melhor' },
            { e: '⭐', t: 'estrela avaliacao cliente feedback top' },
            { e: '💼', t: 'maleta trabalho negocio empresa vendas' }
        ]
    };

    window._chatEmojiCatAtiva = 'rostos';

    window.toggleEmojiPickerChat = function() {
        const p = document.getElementById('painelEmojiPickerChat');
        if (!p) return;
        if (p.classList.contains('hidden')) {
            abrirEmojiPickerChat();
        } else {
            fecharEmojiPickerChat();
        }
    };

    window.abrirEmojiPickerChat = function() {
        const p = document.getElementById('painelEmojiPickerChat');
        if (!p) return;
        p.classList.remove('hidden');
        renderizarGridEmojis(window._chatEmojiCatAtiva);
    };

    window.fecharEmojiPickerChat = function() {
        const p = document.getElementById('painelEmojiPickerChat');
        if (p) p.classList.add('hidden');
        const inpBusca = document.getElementById('inputBuscaEmojiChat');
        if (inpBusca) inpBusca.value = '';
    };

    window.selecionarCategoriaEmoji = function(cat) {
        window._chatEmojiCatAtiva = cat;
        
        document.querySelectorAll('.tab-emoji-btn').forEach(btn => {
            btn.classList.remove('bg-emerald-100', 'text-[#008069]');
        });
        const btnAtivo = document.getElementById('tabEmoji_' + cat);
        if (btnAtivo) {
            btnAtivo.classList.add('bg-emerald-100', 'text-[#008069]');
        }

        const inpBusca = document.getElementById('inputBuscaEmojiChat');
        if (inpBusca) inpBusca.value = '';

        const titulos = {
            recentes: 'Mais Usados / Recentes',
            rostos: 'Carinhas e Emoções',
            gestos: 'Mãos e Gestos',
            coracoes: 'Corações e Símbolos',
            comida: 'Comidas e Bebidas',
            comercio: 'Vendas e Objetos'
        };
        const elTitulo = document.getElementById('tituloCategoriaEmoji');
        if (elTitulo) elTitulo.textContent = titulos[cat] || 'Emojis';

        renderizarGridEmojis(cat);
    };

    window.aoBuscarEmojiChat = function(termo) {
        termo = (termo || '').trim().toLowerCase();
        const elTitulo = document.getElementById('tituloCategoriaEmoji');

        if (!termo) {
            selecionarCategoriaEmoji(window._chatEmojiCatAtiva);
            return;
        }

        if (elTitulo) elTitulo.textContent = `Resultados para "${termo}"`;

        let resultados = [];
        const visto = new Set();
        Object.keys(EMOJIS_DB).forEach(k => {
            EMOJIS_DB[k].forEach(item => {
                if (!visto.has(item.e) && (item.t.includes(termo) || item.e.includes(termo))) {
                    visto.add(item.e);
                    resultados.push(item);
                }
            });
        });

        renderizarListaItensEmoji(resultados);
    };

    function renderizarGridEmojis(cat) {
        let itens = [];
        if (cat === 'recentes') {
            itens = obterEmojisRecentes();
        } else {
            itens = EMOJIS_DB[cat] || [];
        }
        renderizarListaItensEmoji(itens);
    }

    function renderizarListaItensEmoji(lista) {
        const grid = document.getElementById('gridEmojisChat');
        const cont = document.getElementById('contagemEmojis');
        if (!grid) return;

        if (cont) {
            cont.textContent = lista.length > 0 ? `${lista.length} emojis` : '';
        }

        if (lista.length === 0) {
            grid.innerHTML = '<div class="col-span-8 py-8 text-center text-xs text-slate-400 font-medium">Nenhum emoji encontrado</div>';
            return;
        }

        let html = '';
        lista.forEach(item => {
            const emojiChar = typeof item === 'string' ? item : item.e;
            const tooltip = typeof item === 'string' ? item : (item.t ? item.t.split(' ')[0] : item.e);
            html += `
                <button type="button" 
                        onclick="inserirEmojiChat('${emojiChar}')" 
                        class="h-8 w-8 text-xl flex items-center justify-center rounded-xl hover:bg-slate-100 hover:scale-125 transition-transform duration-100 cursor-pointer select-none" 
                        title="${tooltip}">
                    ${emojiChar}
                </button>
            `;
        });

        grid.innerHTML = html;
    }

    window.inserirEmojiChat = function(emoji) {
        const textarea = document.getElementById('inputTextoMensagemChat');
        if (!textarea) return;

        const start = textarea.selectionStart || 0;
        const end = textarea.selectionEnd || 0;
        const valorAtual = textarea.value;

        textarea.value = valorAtual.substring(0, start) + emoji + valorAtual.substring(end);
        
        const novoPos = start + emoji.length;
        textarea.selectionStart = novoPos;
        textarea.selectionEnd = novoPos;
        textarea.focus();

        salvarEmojiRecente(emoji);
    };

    const CHAT_EMOJI_STORAGE_KEY = 'pulse_chat_recent_emojis';
    function obterEmojisRecentes() {
        try {
            const salvos = localStorage.getItem(CHAT_EMOJI_STORAGE_KEY);
            if (salvos) {
                const arr = JSON.parse(salvos);
                if (Array.isArray(arr) && arr.length > 0) {
                    return arr.map(e => ({ e: e, t: 'recente' }));
                }
            }
        } catch (err) {}
        return [
            { e: '😀', t: 'recente' }, { e: '😂', t: 'recente' }, { e: '😍', t: 'recente' }, { e: '🥰', t: 'recente' },
            { e: '👍', t: 'recente' }, { e: '👏', t: 'recente' }, { e: '🙏', t: 'recente' }, { e: '🤝', t: 'recente' },
            { e: '❤️', t: 'recente' }, { e: '🔥', t: 'recente' }, { e: '✨', t: 'recente' }, { e: '✅', t: 'recente' },
            { e: '💰', t: 'recente' }, { e: '📦', t: 'recente' }, { e: '🚀', t: 'recente' }, { e: '🎉', t: 'recente' }
        ];
    }

    function salvarEmojiRecente(emoji) {
        try {
            let atuais = [];
            const salvos = localStorage.getItem(CHAT_EMOJI_STORAGE_KEY);
            if (salvos) {
                atuais = JSON.parse(salvos) || [];
            }
            atuais = [emoji, ...atuais.filter(x => x !== emoji)].slice(0, 32);
            localStorage.setItem(CHAT_EMOJI_STORAGE_KEY, JSON.stringify(atuais));
        } catch (err) {}
    }

    document.addEventListener('click', function(e) {
        const picker = document.getElementById('painelEmojiPickerChat');
        const btnEmoji = document.getElementById('btnEmojiChat');
        if (!picker || picker.classList.contains('hidden')) return;

        if (!picker.contains(e.target) && (!btnEmoji || !btnEmoji.contains(e.target))) {
            fecharEmojiPickerChat();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            fecharEmojiPickerChat();
        }
    });

    // Helper anti-XSS
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    // Inicia conexão WebSocket em background para receber alertas em tempo real
    if (typeof window !== 'undefined') {
        conectarWebSocketChat();
    }

})();
</script>
