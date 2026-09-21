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
<div id="modalChatWhatsApp" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-2 sm:p-4 transition-all duration-300">
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
                        <span class="w-2 h-2 rounded-full bg-emerald-300 animate-pulse"></span>
                        <span>Atendimento por Setores • Isolado por Loja</span>
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
                <div id="painelConversaAberta" class="flex-1 flex flex-col h-full hidden">
                    
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

                    <!-- FOOTER: BARRA DE DIGITAÇÃO WHATSAPP -->
                    <div class="p-2.5 bg-[#f0f2f5] border-t border-slate-300 flex items-center gap-2 shrink-0">
                        <!-- Input invisível de Upload de Foto -->
                        <input type="file" id="inputUploadMidiaChat" accept="image/*" class="hidden" onchange="aoSelecionarFotoChat(this)">

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
                                      class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-2xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 outline-none focus:border-[#008069] focus:ring-1 focus:ring-[#008069] resize-none max-h-28 transition"></textarea>
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
    window._chatConversaAtiva = null;
    window._chatSetorFiltro = 'todos';
    window._chatTermoBusca = '';
    window._chatPollingTimer = null;
    window._chatFotoUrlPendente = null;
    window._chatColaboradoresStore = [];

    // URLs de Endpoints
    const URL_CONVERSAS = '<?= Url::to(['/vendas/canal-comunicacao/get-conversas']) ?>';
    const URL_MENSAGENS = '<?= Url::to(['/vendas/canal-comunicacao/get-mensagens']) ?>';
    const URL_ENVIAR = '<?= Url::to(['/vendas/canal-comunicacao/enviar-mensagem']) ?>';
    const URL_UPLOAD = '<?= Url::to(['/vendas/canal-comunicacao/upload-midia']) ?>';
    const URL_NAO_LIDOS = '<?= Url::to(['/vendas/canal-comunicacao/get-nao-lidos-count']) ?>';
    const URL_LISTAR_SETORES = '<?= Url::to(['/vendas/canal-comunicacao/listar-setores']) ?>';
    const URL_SALVAR_SETOR = '<?= Url::to(['/vendas/canal-comunicacao/salvar-setor']) ?>';
    const URL_EXCLUIR_SETOR = '<?= Url::to(['/vendas/canal-comunicacao/excluir-setor']) ?>';

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
        iniciarPollingChat();
    };

    window.fecharModalCanalInterno = function() {
        const modal = document.getElementById('modalChatWhatsApp');
        if (!modal) return;
        modal.classList.add('hidden');
        pararPollingChat();
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
                    window._chatConversas = data.conversas || [];
                    atualizarBadgesHeader(data.total_nao_lidos);
                    renderizarListaConversas();

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

        const url = URL_MENSAGENS + '?conversa_id=' + encodeURIComponent(conversa.conversa_id) +
                    (conversa.cliente_id ? '&cliente_id=' + encodeURIComponent(conversa.cliente_id) : '') +
                    (conversa.mesa_id ? '&mesa_id=' + encodeURIComponent(conversa.mesa_id) : '');

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
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
                <div class="flex flex-col ${isDireita ? 'items-end' : 'items-start'} max-w-[85%] sm:max-w-[70%]">
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
                // Recarrega conversa imediatamente
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

    // Polling contínuo
    function iniciarPollingChat() {
        pararPollingChat();
        window._chatPollingTimer = setInterval(() => {
            const modal = document.getElementById('modalChatWhatsApp');
            if (modal && !modal.classList.contains('hidden')) {
                carregarConversasChat(true);
            }
        }, 5000);
    }

    function pararPollingChat() {
        if (window._chatPollingTimer) {
            clearInterval(window._chatPollingTimer);
            window._chatPollingTimer = null;
        }
    }

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

})();
</script>
