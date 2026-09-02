<?php

use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var \app\models\Usuario|null $usuarioLoja
 * @var \app\modules\vendas\models\LojaConfiguracao|null $lojaConfig
 * @var string $hubUrlCompleta
 */

$nomeLoja = $lojaConfig->nome_fantasia ?? ($usuarioLoja->nome_loja ?? 'Minha Loja');
$slugLoja = $usuarioLoja->slug ?? ($usuarioLoja->nome_loja ?? ($usuarioLoja->id ?? 'loja'));
$whatsappLoja = $lojaConfig->telefone ?? ($usuarioLoja->telefone ?? '');
?>

<!-- Modal Central do Canal de Comunicação Interno (Direct Hub & Pulse Inbox) -->
<div id="modalCanalInterno" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6 transition-all duration-300">
    <div class="relative w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-200 transform transition-all flex flex-col max-h-[92vh]">
        
        <!-- Header do Modal -->
        <div class="bg-gradient-to-r from-teal-900 via-slate-900 to-emerald-950 text-white p-5 sm:p-6 flex items-center justify-between border-b border-teal-800/40 relative">
            <div class="flex items-center gap-3 sm:gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-teal-500 to-emerald-400 text-slate-950 flex items-center justify-center font-black text-2xl shadow-lg">
                    🌐
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl sm:text-2xl font-black tracking-tight">Central do Canal de Comunicação Interno</h2>
                        <span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider">
                            Direct Hub &amp; Pulse Inbox
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-teal-200/80 mt-0.5">
                        Canal direto da sua loja com clientes para pedidos de encarte, catálogo interativo e mensagens em tempo real.
                    </p>
                </div>
            </div>

            <button type="button" onclick="fecharModalCanalInterno()" class="text-slate-400 hover:text-white bg-slate-800/80 hover:bg-slate-700 p-2.5 rounded-full transition cursor-pointer shadow">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Conteúdo do Modal com Abas / Seções -->
        <div class="p-5 sm:p-6 space-y-6 overflow-y-auto flex-1 bg-slate-50/50">
            
            <!-- Card 1: Link Oficial e Acesso do Cliente -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-teal-600 bg-teal-50 px-2 py-0.5 rounded-md">Endereço Público do Canal</span>
                        <h3 class="text-base font-extrabold text-slate-900 mt-1 flex items-center gap-2">
                            <span>Link do Direct Hub da Loja</span>
                            <span class="text-xs font-semibold text-slate-500">(Acesso direto dos clientes via Web)</span>
                        </h3>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <div class="relative flex-1 w-full">
                        <input type="text" id="inputUrlCanalInterno" readonly value="<?= Html::encode($hubUrlCompleta) ?>" class="w-full pl-3 pr-24 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono text-slate-700 select-all focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <span class="absolute right-2.5 top-2.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider pointer-events-none">URL Oficial</span>
                    </div>
                    <button type="button" onclick="toggleQrCodeCanalInterno()" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl shadow transition gap-1.5 cursor-pointer">
                        <span>📱 QR Code do Canal</span>
                    </button>
                </div>

                <!-- Container Colapsável de QR Code -->
                <div id="containerQrCodeCanal" class="hidden pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-center gap-6 p-4 bg-slate-50 rounded-2xl">
                    <div class="p-3 bg-white rounded-2xl shadow border border-slate-200">
                        <img id="imgQrCodeCanal" src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($hubUrlCompleta) ?>" alt="QR Code Direct Hub" class="w-36 h-36 rounded-lg object-contain">
                    </div>
                    <div class="space-y-2 text-center sm:text-left">
                        <h4 class="font-extrabold text-sm text-slate-900">QR Code Oficial do Canal de Atendimento</h4>
                        <p class="text-xs text-slate-500 max-w-sm">
                            Imprima este QR Code para fixar no balcão da loja, mesas, banners de ofertas ou encartes físicos para que os clientes acessem seu catálogo interativo diretamente.
                        </p>
                        <a href="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=<?= urlencode($hubUrlCompleta) ?>" download="qrcode_canal_proprio.png" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-lg shadow-sm transition gap-1">
                            📥 Baixar Imagem QR Code
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card 2: Painel de Pedidos & Mensagens Recebidas (Pulse Inbox) -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-3.5">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg">
                            📥
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                                <span>Pedidos &amp; Mensagens Recebidas</span>
                                <span id="badgeContadorNaoLidosModal" class="bg-red-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full">0</span>
                            </h3>
                            <p class="text-xs text-slate-500">Histórico de pedidos recebidos pelo Encarte Digital e chamados de clientes.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <button type="button" onclick="carregarMensagensCanalInterno()" class="flex-1 sm:flex-initial inline-flex items-center justify-center px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-lg border border-slate-300 transition gap-1 cursor-pointer" title="Recarregar">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Atualizar</span>
                        </button>
                        <button type="button" onclick="marcarTodasMensagensLidas()" class="flex-1 sm:flex-initial inline-flex items-center justify-center px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs rounded-lg border border-emerald-300 transition gap-1 cursor-pointer">
                            <span>✅ Marcar Lidos</span>
                        </button>
                    </div>
                </div>

                <!-- Barra de Consulta e Filtros de Mensagens -->
                <div class="flex flex-col sm:flex-row items-center gap-2 pt-1">
                    <!-- Input de Busca Instantânea -->
                    <div class="relative flex-1 w-full">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text" 
                               id="inputBuscaInbox" 
                               oninput="onBuscaInboxInput(this.value)" 
                               placeholder="Buscar por cliente, telefone, produto ou mensagem..." 
                               class="w-full pl-9 pr-8 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-800 placeholder-slate-400 outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 transition">
                        <button type="button" 
                                id="btnLimparBuscaInbox" 
                                onclick="limparBuscaInbox()" 
                                class="hidden absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 font-bold text-sm cursor-pointer" 
                                title="Limpar busca">&times;</button>
                    </div>

                    <!-- Seletor de Categoria / Tipo -->
                    <select id="selectTipoInbox" onchange="onFiltroTipoInbox(this.value)" class="w-full sm:w-auto px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:border-teal-500 transition cursor-pointer">
                        <option value="todos">Todos os Tipos</option>
                        <option value="card">🛒 Pedidos de Encarte</option>
                        <option value="texto">💬 Chat Direct Hub</option>
                        <option value="chamado">🔔 Chamados</option>
                        <option value="conta">🧾 Contas/Caixa</option>
                    </select>
                </div>

                <!-- Seletor de Abas: Não Lidas / Lidas / Todas -->
                <div class="flex items-center gap-2 border-b border-slate-200 pb-2.5 pt-1 overflow-x-auto">
                    <button type="button" 
                            id="tabBtnNaoLidas" 
                            onclick="setAbaInbox('nao_lidos')" 
                            class="px-3.5 py-1.5 rounded-xl text-xs font-black transition flex items-center gap-1.5 cursor-pointer bg-emerald-600 text-white shadow-xs">
                        <span>🔔 Não Lidas</span>
                        <span id="tabCountNaoLidas" class="bg-white text-emerald-800 text-[10px] font-black px-1.5 py-0.2 rounded-full">0</span>
                    </button>
                    
                    <button type="button" 
                            id="tabBtnLidas" 
                            onclick="setAbaInbox('lidos')" 
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer bg-slate-100 hover:bg-slate-200 text-slate-700">
                        <span>✅ Lidas</span>
                        <span id="tabCountLidas" class="bg-slate-200 text-slate-700 text-[10px] font-bold px-1.5 py-0.2 rounded-full">0</span>
                    </button>
                    
                    <button type="button" 
                            id="tabBtnTodas" 
                            onclick="setAbaInbox('todas')" 
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer bg-slate-100 hover:bg-slate-200 text-slate-700">
                        <span>📋 Todas</span>
                        <span id="tabCountTodas" class="bg-slate-200 text-slate-700 text-[10px] font-bold px-1.5 py-0.2 rounded-full">0</span>
                    </button>
                </div>

                <!-- Lista Dinâmica de Mensagens / Pedidos -->
                <div id="listaMensagensCanalInterno" class="space-y-3 max-h-80 overflow-y-auto pr-1">
                    <div class="text-center py-8 text-slate-400 space-y-2">
                        <span class="text-3xl block animate-spin">⏳</span>
                        <p class="text-xs font-bold">Carregando mensagens do canal interno...</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Rodapé do Modal -->
        <div class="p-4 bg-slate-100 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500 font-medium">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                <span class="font-bold text-slate-700">Canal Online &amp; Pronto para Atendimento</span>
            </div>
            <button type="button" onclick="fecharModalCanalInterno()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl transition cursor-pointer">
                Fechar
            </button>
        </div>

    </div>
</div>

<script>
    window._inboxMensagens = [];
    window._abaInboxAtiva = 'nao_lidos';
    window._termoBuscaInbox = '';
    window._tipoInboxFiltro = 'todos';

    window.abrirModalCanalInterno = function() {
        document.getElementById('modalCanalInterno').classList.remove('hidden');
        carregarMensagensCanalInterno();
    };

    window.fecharModalCanalInterno = function() {
        document.getElementById('modalCanalInterno').classList.add('hidden');
    };

    window.copiarLinkCanalInterno = function(url) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(() => {
                alert('✅ Link do Canal de Atendimento copiado com sucesso!\n' + url);
            }).catch(() => {
                prompt('Copie o link abaixo:', url);
            });
        } else {
            prompt('Copie o link abaixo:', url);
        }
    };

    window.toggleQrCodeCanalInterno = function() {
        const el = document.getElementById('containerQrCodeCanal');
        if (el) {
            el.classList.toggle('hidden');
        }
    };

    window.setAbaInbox = function(aba) {
        window._abaInboxAtiva = aba;
        atualizarBotoesAbas();
        filtrarERenderizarInbox();
    };

    window.onBuscaInboxInput = function(val) {
        window._termoBuscaInbox = (val || '').trim().toLowerCase();
        const btnLimpar = document.getElementById('btnLimparBuscaInbox');
        if (btnLimpar) {
            if (window._termoBuscaInbox) {
                btnLimpar.classList.remove('hidden');
            } else {
                btnLimpar.classList.add('hidden');
            }
        }
        filtrarERenderizarInbox();
    };

    window.limparBuscaInbox = function() {
        const input = document.getElementById('inputBuscaInbox');
        if (input) input.value = '';
        const btnLimpar = document.getElementById('btnLimparBuscaInbox');
        if (btnLimpar) btnLimpar.classList.add('hidden');
        window._termoBuscaInbox = '';
        filtrarERenderizarInbox();
    };

    window.onFiltroTipoInbox = function(tipo) {
        window._tipoInboxFiltro = tipo || 'todos';
        filtrarERenderizarInbox();
    };

    function atualizarBotoesAbas() {
        const tabBtnNaoLidas = document.getElementById('tabBtnNaoLidas');
        const tabBtnLidas    = document.getElementById('tabBtnLidas');
        const tabBtnTodas    = document.getElementById('tabBtnTodas');

        const activeClass = 'bg-emerald-600 text-white shadow-xs font-black';
        const inactiveClass = 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold';

        if (tabBtnNaoLidas && tabBtnLidas && tabBtnTodas) {
            tabBtnNaoLidas.className = `px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer ${window._abaInboxAtiva === 'nao_lidos' ? activeClass : inactiveClass}`;
            tabBtnLidas.className    = `px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer ${window._abaInboxAtiva === 'lidos' ? activeClass : inactiveClass}`;
            tabBtnTodas.className    = `px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer ${window._abaInboxAtiva === 'todas' ? activeClass : inactiveClass}`;
        }
    }

    window.carregarMensagensCanalInterno = async function() {
        const container = document.getElementById('listaMensagensCanalInterno');
        if (!container) return;

        container.innerHTML = `
            <div class="text-center py-8 text-slate-400 space-y-2">
                <span class="text-3xl block animate-spin">⏳</span>
                <p class="text-xs font-bold">Carregando mensagens e pedidos...</p>
            </div>
        `;

        try {
            const resp = await fetch('<?= Url::to(['/vendas/produto/get-inbox']) ?>');
            const data = await resp.json();

            if (!data.success) {
                container.innerHTML = `<div class="p-4 text-center text-xs text-red-500 font-bold">Erro ao carregar mensagens.</div>`;
                return;
            }

            window._inboxMensagens = data.mensagens || [];

            const totalNaoLidos = window._inboxMensagens.filter(m => !m.lido).length;

            const badgeHeader = document.getElementById('badgeInboxNaoLidosHeader');
            if (badgeHeader) {
                if (totalNaoLidos > 0) {
                    badgeHeader.textContent = totalNaoLidos;
                    badgeHeader.style.display = 'inline-flex';
                } else {
                    badgeHeader.style.display = 'none';
                }
            }

            const badgeModal = document.getElementById('badgeContadorNaoLidosModal');
            if (badgeModal) {
                badgeModal.textContent = totalNaoLidos;
            }

            // Se não houver não lidos, abre na aba 'todas' por conveniência
            if (totalNaoLidos === 0 && window._abaInboxAtiva === 'nao_lidos') {
                window._abaInboxAtiva = 'todas';
            }

            atualizarBotoesAbas();
            filtrarERenderizarInbox();

        } catch (err) {
            container.innerHTML = `<div class="p-4 text-center text-xs text-red-500 font-bold">Falha de conexão com a central de mensagens.</div>`;
        }
    };

    window.filtrarERenderizarInbox = function() {
        const container = document.getElementById('listaMensagensCanalInterno');
        if (!container) return;

        const todas = window._inboxMensagens || [];

        // Atualiza contadores das abas
        const countNaoLidas = todas.filter(m => !m.lido).length;
        const countLidas    = todas.filter(m => m.lido).length;
        const countTodas    = todas.length;

        const elCountNaoLidas = document.getElementById('tabCountNaoLidas');
        const elCountLidas    = document.getElementById('tabCountLidas');
        const elCountTodas    = document.getElementById('tabCountTodas');

        if (elCountNaoLidas) elCountNaoLidas.textContent = countNaoLidas;
        if (elCountLidas) elCountLidas.textContent = countLidas;
        if (elCountTodas) elCountTodas.textContent = countTodas;

        // 1. Filtro por Aba de Status
        let filtradas = todas.filter(m => {
            if (window._abaInboxAtiva === 'nao_lidos') return !m.lido;
            if (window._abaInboxAtiva === 'lidos') return m.lido;
            return true; // 'todas'
        });

        // 2. Filtro por Tipo de Atendimento
        if (window._tipoInboxFiltro && window._tipoInboxFiltro !== 'todos') {
            filtradas = filtradas.filter(m => {
                if (window._tipoInboxFiltro === 'card') return m.tipo === 'card' || (m.titulo && m.titulo.includes('Pedido'));
                if (window._tipoInboxFiltro === 'texto') return m.tipo === 'texto' || (m.titulo && m.titulo.includes('Mensagem de'));
                if (window._tipoInboxFiltro === 'chamado') return m.tipo === 'chamado';
                if (window._tipoInboxFiltro === 'conta') return m.tipo === 'conta';
                return true;
            });
        }

        // 3. Filtro por Termo de Consulta / Busca
        if (window._termoBuscaInbox) {
            const q = window._termoBuscaInbox;
            filtradas = filtradas.filter(m => {
                const titulo = (m.titulo || '').toLowerCase();
                const conteudo = (m.conteudo || '').toLowerCase();
                const autor = (m.autor || '').toLowerCase();
                const acoesStr = JSON.stringify(m.acoes_json || {}).toLowerCase();
                const respStr = JSON.stringify(m.respostas || []).toLowerCase();

                return titulo.includes(q) || conteudo.includes(q) || autor.includes(q) || acoesStr.includes(q) || respStr.includes(q);
            });
        }

        // Se a lista resultante estiver vazia, renderiza estado vazio personalizado
        if (filtradas.length === 0) {
            let msgVazia = 'Nenhum pedido ou mensagem recente.';
            let iconeVazio = '📭';
            let subVazio = 'Os pedidos do Encarte Digital e chamados dos clientes aparecerão aqui automaticamente.';

            if (window._termoBuscaInbox) {
                iconeVazio = '🔍';
                msgVazia = 'Nenhum resultado encontrado para a busca.';
                subVazio = `Não localizamos mensagens contendo "<strong>${window._termoBuscaInbox}</strong>". Tente outro termo ou limpe o campo.`;
            } else if (window._abaInboxAtiva === 'nao_lidos') {
                iconeVazio = '🎉';
                msgVazia = 'Tudo em dia! Nenhuma mensagem não lida.';
                subVazio = 'Todas as mensagens e pedidos recebidos já foram visualizados ou respondidos.';
            } else if (window._abaInboxAtiva === 'lidos') {
                iconeVazio = '📁';
                msgVazia = 'Nenhuma mensagem arquivada como lida.';
                subVazio = 'Quando você marcar mensagens como lidas ou responder clientes, elas aparecerão aqui.';
            }

            container.innerHTML = `
                <div class="text-center py-8 text-slate-400 space-y-2">
                    <span class="text-4xl block">${iconeVazio}</span>
                    <p class="text-xs font-bold text-slate-700">${msgVazia}</p>
                    <p class="text-[11px] text-slate-500 max-w-sm mx-auto">${subVazio}</p>
                </div>
            `;
            return;
        }

        container.innerHTML = '';
        filtradas.forEach(msg => {
            const card = document.createElement('div');
            const isNaoLido = !msg.lido;
            card.className = `p-4 rounded-2xl border transition flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs ${isNaoLido ? 'bg-emerald-50/70 border-emerald-300 shadow-xs' : 'bg-slate-50 border-slate-200'}`;

            let badgeTipo = `<span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-slate-200 text-slate-700">Mensagem</span>`;
            if (msg.tipo === 'card' || (msg.titulo && msg.titulo.includes('Pedido'))) {
                badgeTipo = `<span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-emerald-600 text-white">🛒 Pedido Encarte</span>`;
            } else if (msg.tipo === 'texto' || (msg.titulo && msg.titulo.includes('Mensagem de'))) {
                badgeTipo = `<span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-teal-600 text-white">💬 Chat Direct Hub</span>`;
            } else if (msg.tipo === 'chamado') {
                badgeTipo = `<span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-amber-500 text-white">🔔 Chamado Atendimento</span>`;
            } else if (msg.tipo === 'conta') {
                badgeTipo = `<span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-blue-600 text-white">🧾 Conta / Caixa</span>`;
            }

            let conteudoFormatado = msg.conteudo || '';
            // Transforma **texto** em negrito
            conteudoFormatado = conteudoFormatado.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900">$1</strong>');
            conteudoFormatado = conteudoFormatado.replace(/\n/g, '<br>');

            const acoes = (msg.acoes_json && typeof msg.acoes_json === 'object') ? msg.acoes_json : {};
            const zapTel = acoes.telefone ? String(acoes.telefone).replace(/\D/g, '') : '';
            const isOrigemCliente = (acoes.origem === 'cliente' || msg.tipo === 'chamado' || msg.tipo === 'card' || msg.tipo === 'texto');

            let midiaHtml = '';
            if (msg.midia_url) {
                midiaHtml = `
                    <div class="mt-2">
                        <img src="${msg.midia_url}" alt="Foto" class="rounded-xl max-h-36 w-auto object-cover border border-slate-200 shadow-2xs cursor-pointer hover:opacity-90 transition" onclick="window.open('${msg.midia_url}', '_blank')">
                    </div>
                `;
            }

            let respostasHtml = '';
            if (msg.respostas && msg.respostas.length > 0) {
                respostasHtml = `
                    <div class="mt-3 pt-2.5 border-t border-slate-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase text-teal-800 tracking-wider flex items-center gap-1">
                                <span>📢</span> Histórico de Respostas da Loja (${msg.respostas.length})
                            </span>
                        </div>
                        <div class="space-y-1.5">
                            ${msg.respostas.map(resp => {
                                let respConteudo = resp.conteudo || '';
                                respConteudo = respConteudo.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-teal-950">$1</strong>');
                                respConteudo = respConteudo.replace(/\n/g, '<br>');
                                return `
                                    <div class="bg-teal-50 border border-teal-200/90 rounded-xl p-2.5 space-y-1">
                                        <div class="flex items-center justify-between text-[10px]">
                                            <span class="font-extrabold text-teal-900 flex items-center gap-1">
                                                <span>✓</span> ${resp.autor || 'Loja'}
                                            </span>
                                            <span class="text-teal-700 font-semibold">${resp.tempo_relativo || resp.data_formatada}</span>
                                        </div>
                                        <div class="text-[11px] text-teal-950 leading-relaxed font-normal">
                                            ${respConteudo}
                                        </div>
                                        ${resp.midia_url ? `
                                            <div class="mt-1.5">
                                                <img src="${resp.midia_url}" alt="Foto" class="rounded-lg max-h-32 object-cover border border-teal-200 cursor-pointer" onclick="window.open('${resp.midia_url}', '_blank')">
                                            </div>
                                        ` : ''}
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `;
            }

            card.innerHTML = `
                <div class="flex-1 space-y-1 w-full">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <div class="flex items-center gap-2 flex-wrap">
                            ${badgeTipo}
                            <span class="font-extrabold text-slate-900">${msg.titulo || 'Nova Mensagem'}</span>
                            <span class="text-[10px] text-slate-400 font-semibold">• ${msg.tempo_relativo || msg.data_formatada}</span>
                        </div>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            ${isOrigemCliente ? `
                                <button type="button" onclick="toggleFormResposta('${msg.id}')" class="px-2.5 py-1 bg-teal-600 hover:bg-teal-700 text-white font-bold text-[10px] rounded-lg transition flex items-center gap-1 shadow-2xs cursor-pointer">
                                    💬 Responder no Hub
                                </button>
                            ` : ''}
                            ${zapTel ? `
                                <a href="https://wa.me/55${zapTel}" target="_blank" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] rounded-lg transition flex items-center gap-1 shadow-2xs">
                                    📱 WhatsApp
                                </a>
                            ` : ''}
                            ${isNaoLido ? `
                                <button type="button" onclick="marcarMensagemLida('${msg.id}')" class="px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 font-bold text-[10px] rounded-lg border border-slate-300 transition cursor-pointer shadow-2xs">
                                    Marcar Lido
                                </button>
                            ` : `
                                <span class="text-[10px] font-bold text-slate-400">✓ Lido</span>
                            `}
                        </div>
                    </div>

                    <div class="text-[11px] text-slate-600 font-normal leading-relaxed mt-1 bg-white/70 p-2.5 rounded-xl border border-slate-200/70">
                        ${conteudoFormatado}
                        ${midiaHtml}
                        ${respostasHtml}
                    </div>

                    <!-- Formulário Inline de Resposta via Direct Hub -->
                    <div id="box-resposta-${msg.id}" class="hidden mt-2 p-3 bg-white rounded-xl border border-teal-200 shadow-sm space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-extrabold text-teal-800 flex items-center gap-1">
                                <span>📢</span> Responder para o Direct Hub do Cliente
                            </span>
                            <button type="button" onclick="toggleFormResposta('${msg.id}')" class="text-slate-400 hover:text-slate-600 text-xs font-bold">&times; Fechar</button>
                        </div>

                        <!-- Atalhos de Emojis da Loja -->
                        <div class="flex items-center gap-1 overflow-x-auto py-1 scrollbar-thin">
                            ${['👍', '❤️', '😊', '🔥', '👏', '🎉', '📦', '🍽️', '💬', '✅', '🛵', '📍', '⏳', '🙏', '🧾', '💳', '📸', '🤝'].map(em => `
                                <button type="button" onclick="adicionarEmojiLoja('${msg.id}', '${em}')" class="text-xs p-1 hover:bg-slate-100 rounded transition cursor-pointer flex-shrink-0">${em}</button>
                            `).join('')}
                        </div>

                        <!-- Preview Foto Loja -->
                        <div id="preview-foto-loja-${msg.id}" class="hidden relative inline-block border-2 border-teal-500 rounded-lg overflow-hidden">
                            <img id="img-preview-loja-${msg.id}" src="" class="h-14 w-14 object-cover">
                            <button type="button" onclick="removerFotoLoja('${msg.id}')" class="absolute top-0.5 right-0.5 bg-black/70 text-white rounded-full p-0.5 text-[9px] leading-none">&times;</button>
                        </div>

                        <div class="flex items-start gap-1.5">
                            <input type="file" id="file-foto-loja-${msg.id}" onchange="selecionarFotoLoja('${msg.id}', this)" accept="image/*" class="hidden">
                            <button type="button" onclick="document.getElementById('file-foto-loja-${msg.id}').click()" class="p-2 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-lg text-xs font-bold text-slate-700 transition" title="Anexar Imagem">
                                📷
                            </button>
                            <textarea id="input-resposta-${msg.id}" rows="2" placeholder="Digite sua resposta oficial para o cliente..." class="flex-1 bg-slate-50 border border-slate-300 rounded-lg p-2 text-xs text-slate-800 outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500/20"></textarea>
                            <button type="button" id="btn-env-resp-${msg.id}" onclick="enviarRespostaLoja('${msg.id}')" class="px-3.5 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-lg transition shadow-xs flex items-center gap-1 cursor-pointer">
                                <span>Enviar</span>
                                <span>➔</span>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
    };

    window.toggleFormResposta = function(msgId) {
        const el = document.getElementById('box-resposta-' + msgId);
        if (el) {
            el.classList.toggle('hidden');
            if (!el.classList.contains('hidden')) {
                const txt = document.getElementById('input-resposta-' + msgId);
                if (txt) txt.focus();
            }
        }
    };

    window.adicionarEmojiLoja = function(msgId, emoji) {
        const txt = document.getElementById('input-resposta-' + msgId);
        if (txt) {
            txt.value += emoji;
            txt.focus();
        }
    };

    window.selecionarFotoLoja = function(msgId, input) {
        const file = input.files && input.files[0];
        if (!file) return;
        const previewBox = document.getElementById('preview-foto-loja-' + msgId);
        const previewImg = document.getElementById('img-preview-loja-' + msgId);
        if (previewBox && previewImg) {
            previewImg.src = URL.createObjectURL(file);
            previewBox.classList.remove('hidden');
        }
    };

    window.removerFotoLoja = function(msgId) {
        const fileInput = document.getElementById('file-foto-loja-' + msgId);
        if (fileInput) fileInput.value = '';
        const previewBox = document.getElementById('preview-foto-loja-' + msgId);
        if (previewBox) previewBox.classList.add('hidden');
    };

    window.enviarRespostaLoja = async function(msgId) {
        const txtInput = document.getElementById('input-resposta-' + msgId);
        const txt = txtInput ? txtInput.value.trim() : '';
        const fileInput = document.getElementById('file-foto-loja-' + msgId);
        const file = fileInput && fileInput.files && fileInput.files[0];

        if (!txt && !file) {
            alert('Por favor, digite uma resposta ou selecione uma foto.');
            return;
        }

        const btn = document.getElementById('btn-env-resp-' + msgId);
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span>Enviando...</span>';
        }

        let midiaUrl = '';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        if (file) {
            try {
                const formData = new FormData();
                formData.append('foto', file);
                const upResp = await fetch('<?= Url::to(['/vendas/produto/upload-inbox-midia']) ?>', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                const upData = await upResp.json();
                if (upData.success) {
                    midiaUrl = upData.url;
                }
            } catch(e) {
                console.error('Erro no upload da foto da loja:', e);
            }
        }

        try {
            const resp = await fetch('<?= Url::to(['/vendas/produto/responder-inbox']) ?>', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    mensagem_id: msgId,
                    resposta: txt,
                    midia_url: midiaUrl
                })
            });

            if (!resp.ok) {
                const errText = await resp.text();
                throw new Error('Status ' + resp.status + ': ' + errText.substring(0, 80));
            }

            const data = await resp.json();

            if (data.success) {
                alert('✅ ' + (data.message || 'Resposta enviada com sucesso ao cliente!'));
                carregarMensagensCanalInterno();
            } else {
                alert('⚠️ ' + (data.message || 'Erro ao enviar resposta.'));
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<span>Enviar</span><span>➔</span>';
                }
            }
        } catch(e) {
            console.error('Erro ao enviar resposta:', e);
            alert('Não foi possível enviar a resposta: ' + (e.message || 'Erro de comunicação com o servidor.'));
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<span>Enviar</span><span>➔</span>';
            }
        }
    };

    window.marcarMensagemLida = async function(id) {
        try {
            await fetch('<?= Url::to(['/vendas/produto/marcar-inbox-lido']) ?>?id=' + encodeURIComponent(id));
            carregarMensagensCanalInterno();
        } catch(e) {}
    };

    window.marcarTodasMensagensLidas = async function() {
        try {
            await fetch('<?= Url::to(['/vendas/produto/marcar-inbox-lido']) ?>?id=todos');
            carregarMensagensCanalInterno();
        } catch(e) {}
    };
</script>
