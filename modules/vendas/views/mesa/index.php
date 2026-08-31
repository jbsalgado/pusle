<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Mapa de Mesas & Comandas';
$this->params['breadcrumbs'][] = ['label' => 'Vendas', 'url' => ['/vendas/inicio/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Mensagens Flash -->
        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-xl shadow-sm flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="text-xl">⚠️</span>
                    <p class="font-medium text-sm sm:text-base"><?= Yii::$app->session->getFlash('error') ?></p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-800 font-bold">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-xl shadow-sm flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="text-xl">✅</span>
                    <p class="font-medium text-sm sm:text-base"><?= Yii::$app->session->getFlash('success') ?></p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 font-bold">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('info')): ?>
            <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-4 rounded-xl shadow-sm flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="text-xl">ℹ️</span>
                    <p class="font-medium text-sm sm:text-base"><?= Yii::$app->session->getFlash('info') ?></p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-amber-600 hover:text-amber-800 font-bold">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Cabeçalho -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-200 pb-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-3">
                    <span>🍺</span>
                    <span>Mapa de Mesas & Comandas</span>
                    <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2.5 py-1 rounded-full uppercase">Food Service</span>
                </h1>
                <p class="text-sm text-gray-500 mt-1">Gestão gráfica em tempo real para bares, lanchonetes e restaurantes.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <?= Html::beginForm(Url::to(['/vendas/mesa/adicionar-mesa-rapida']), 'post', ['class' => 'm-0']) ?>
                <button type="submit"
                    class="inline-flex items-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-xl shadow-md transition duration-150 text-xs sm:text-sm" title="Adicionar +1 mesa sequencial instantaneamente com 1 clique">
                    <span class="mr-1.5">⚡</span>
                    <span>+1 Mesa</span>
                </button>
                <?= Html::endForm() ?>

                <button type="button" onclick="abrirModalLoteMesas()"
                    class="inline-flex items-center px-3.5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-black rounded-xl shadow-md transition duration-150 text-xs sm:text-sm" title="Gerar um conjunto de várias mesas de uma vez só">
                    <span class="mr-1.5">🚀</span>
                    <span>+Várias Mesas</span>
                </button>

                <button type="button" onclick="abrirModalCriarMesa()"
                    class="inline-flex items-center px-3.5 py-2 bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-xl shadow-md transition duration-150 text-xs sm:text-sm">
                    <span class="mr-1.5">⚙️</span>
                    <span>Personalizada</span>
                </button>

                <a href="<?= Url::to(['/vendas/delivery/index']) ?>"
                    class="inline-flex items-center px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-xl shadow-md transition duration-150 text-xs sm:text-sm">
                    <span class="mr-1.5">🛵</span>
                    <span>Delivery</span>
                </a>

                <a href="<?= Url::to(['/vendas/kds/index']) ?>"
                    class="inline-flex items-center px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-extrabold rounded-xl shadow-md transition duration-150 text-xs sm:text-sm">
                    <span class="mr-1.5">🍳</span>
                    <span>Monitor KDS</span>
                </a>

                <a href="<?= Url::to(['/vendas/mesa/imprimir-qrcodes']) ?>" target="_blank"
                    class="inline-flex items-center px-3.5 py-2 bg-teal-600 hover:bg-teal-700 text-white font-extrabold rounded-xl shadow-md transition duration-150 text-xs sm:text-sm" title="Imprimir plaquinhas de QR Code para as mesas">
                    <span class="mr-1.5">📱</span>
                    <span>QR Codes</span>
                </a>

                <a href="<?= Url::to(['/vendas/mesa/relatorio']) ?>"
                    class="inline-flex items-center px-3.5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-extrabold rounded-xl shadow-md transition duration-150 text-xs sm:text-sm">
                    <span class="mr-1.5">📊</span>
                    <span>Analytics</span>
                </a>

                <a href="<?= Url::to(['/vendas/mesa/comissoes']) ?>"
                    class="inline-flex items-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-md transition duration-150 text-xs sm:text-sm">
                    <span class="mr-1.5">💰</span>
                    <span>Comissões</span>
                </a>

                <a href="<?= Url::to(['/vendas/inicio/index']) ?>"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition duration-150 text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Voltar ao Painel
                </a>
            </div>
        </div>

        <!-- Banner Compacto de Alerta de Chamados (Suporta 50+ mesas) -->
        <div id="hub-chamados-container" class="hidden">
            <div class="bg-gradient-to-r from-amber-500 via-amber-600 to-orange-600 text-white p-3.5 sm:p-4 rounded-2xl shadow-xl border border-amber-600 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="relative flex-shrink-0">
                        <span class="text-2xl sm:text-3xl animate-bounce block">🔔</span>
                        <span class="absolute -top-1 -right-1 w-3 h-3 bg-rose-500 rounded-full animate-ping"></span>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-xs sm:text-sm font-black uppercase tracking-wider m-0">Chamados de Mesas</h3>
                            <span class="px-2 py-0.5 bg-amber-950 text-amber-200 font-mono text-[11px] rounded-full font-black" id="hub-chamados-badge-total">0</span>
                        </div>
                        <div class="flex items-center gap-2 mt-0.5 text-xs text-amber-100 flex-wrap">
                            <span id="hub-resumo-garcom" class="font-bold">0 Garçom</span>
                            <span>&bull;</span>
                            <span id="hub-resumo-conta" class="font-bold">0 Contas</span>
                        </div>
                    </div>
                </div>

                <!-- Ações e Chips das 3 primeiras mesas -->
                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-start sm:justify-end">
                    <div id="hub-chamados-chips-recentes" class="flex flex-wrap gap-1.5"></div>
                    
                    <button type="button" onclick="abrirDrawerChamados()" class="px-3.5 py-2 bg-white hover:bg-amber-50 text-amber-950 font-black text-xs rounded-xl shadow-md transition-all flex items-center gap-1.5 active:scale-95 border border-amber-200" title="Ver lista completa de todas as mesas aguardando">
                        <span>📋</span>
                        <span>Fila Completa</span>
                        <span id="hub-drawer-btn-badge" class="px-1.5 py-0.2 bg-amber-600 text-white rounded-full text-[10px] font-black">0</span>
                    </button>

                    <button type="button" onclick="atenderTodosChamados()" class="px-3 py-2 bg-amber-950 hover:bg-black text-amber-200 hover:text-white font-bold text-xs rounded-xl shadow transition-all flex items-center gap-1 active:scale-95" title="Marcar todos como atendidos">
                        <span>✓</span>
                        <span class="hidden md:inline">Limpar</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- GAVETA LATERAL DESLIZANTE (DRAWER DE FILA DE ATENDIMENTO DE MESAS) -->
        <!-- ========================================================================= -->
        <div id="drawerChamados" class="fixed inset-0 z-50 hidden">
            <!-- Backdrop escuro -->
            <div onclick="fecharDrawerChamados()" class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity"></div>

            <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
                <div class="w-screen max-w-md bg-white shadow-2xl border-l border-gray-200 flex flex-col">
                    
                    <!-- Cabeçalho da Gaveta -->
                    <div class="p-5 border-b border-gray-200 bg-gradient-to-r from-amber-500 to-orange-500 text-white flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xl">🔔</span>
                                <h3 class="text-base font-black uppercase tracking-wider m-0">Fila de Atendimento</h3>
                            </div>
                            <p class="text-xs text-amber-100 mt-0.5 m-0" id="drawerSubtitulo">0 mesas aguardando atendimento</p>
                        </div>
                        <button type="button" onclick="fecharDrawerChamados()" class="p-2 text-white hover:bg-white/20 rounded-xl transition text-xl font-bold leading-none">&times;</button>
                    </div>

                    <!-- Abas de Filtro na Gaveta -->
                    <div class="flex border-b border-gray-200 px-3 pt-2 bg-gray-50 gap-1.5 text-xs font-bold overflow-x-auto scrollbar-none">
                        <button type="button" onclick="filtrarDrawer('todos')" id="drawerTabTodos" class="py-2 px-2.5 border-b-2 border-amber-600 text-amber-900 font-extrabold flex-shrink-0">
                            Todos (<span id="drawerCountTodos">0</span>)
                        </button>
                        <button type="button" onclick="filtrarDrawer('chat')" id="drawerTabChat" class="py-2 px-2.5 border-b-2 border-transparent text-gray-500 hover:text-gray-900 flex-shrink-0">
                            💬 Mensagens (<span id="drawerCountChat">0</span>)
                        </button>
                        <button type="button" onclick="filtrarDrawer('garcom')" id="drawerTabGarcom" class="py-2 px-2.5 border-b-2 border-transparent text-gray-500 hover:text-gray-900 flex-shrink-0">
                            👋 Garçom (<span id="drawerCountGarcom">0</span>)
                        </button>
                        <button type="button" onclick="filtrarDrawer('conta')" id="drawerTabConta" class="py-2 px-2.5 border-b-2 border-transparent text-gray-500 hover:text-gray-900 flex-shrink-0">
                            💳 Conta (<span id="drawerCountConta">0</span>)
                        </button>
                    </div>

                    <!-- Lista Completa de Chamados (Scrollável) -->
                    <div id="drawerListaChamados" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
                        <div class="text-center py-12 text-gray-400">
                            <span class="text-4xl">⏳</span>
                            <p class="text-xs mt-2">Carregando chamados...</p>
                        </div>
                    </div>

                    <!-- Rodapé da Gaveta -->
                    <div class="p-4 border-t border-gray-200 bg-white flex items-center justify-between gap-3">
                        <button type="button" onclick="checarChamadosHub()" class="p-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-xs flex items-center gap-1.5 transition">
                            <span>🔄</span>
                            <span>Atualizar</span>
                        </button>

                        <button type="button" onclick="atenderTodosChamados()" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow transition flex items-center justify-center gap-1.5">
                            <span>✓</span>
                            <span>Atender Todas as Mesas</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            let chamadosCache = [];
            let filtroDrawerAtual = 'todos';

            // Polling de Chamados do Direct Hub / Garçom a cada 5 segundos
            function checarChamadosHub() {
                fetch('<?= Url::to(['/vendas/mesa/chamados-pendentes']) ?>')
                    .then(r => r.json())
                    .then(data => {
                        chamadosCache = data.chamados || [];
                        const container = document.getElementById('hub-chamados-container');
                        const chipsContainer = document.getElementById('hub-chamados-chips-recentes');
                        const badgeTotal = document.getElementById('hub-chamados-badge-total');
                        const badgeDrawerBtn = document.getElementById('hub-drawer-btn-badge');
                        const resumoGarcom = document.getElementById('hub-resumo-garcom');
                        const resumoConta = document.getElementById('hub-resumo-conta');

                        if (data.total > 0) {
                            container.classList.remove('hidden');
                            if (badgeTotal) badgeTotal.innerText = data.total;
                            if (badgeDrawerBtn) badgeDrawerBtn.innerText = data.total;
                            if (resumoGarcom) resumoGarcom.innerText = `${data.garcom_total || 0} Garçom &bull; ${data.chat_total || 0} Chat`;
                            if (resumoConta) resumoConta.innerText = `${data.conta_total || 0} Conta(s)`;

                            // Renderiza até 3 chips mais recentes no banner
                            if (chipsContainer) {
                                chipsContainer.innerHTML = '';
                                const previewList = chamadosCache.slice(0, 3);
                                previewList.forEach(ch => {
                                    const btn = document.createElement('button');
                                    btn.className = 'px-3 py-1.5 bg-white text-gray-900 font-extrabold text-xs rounded-xl shadow hover:bg-amber-50 hover:text-amber-900 transition-all flex items-center gap-1.5 border border-amber-200 active:scale-95';
                                    btn.title = 'Clique para dar baixa no chamado';
                                    btn.innerHTML = `<span>${ch.tipo_icon || (ch.tipo === 'conta' ? '💳' : '👋')}</span> <span>Mesa ${ch.mesa_numero}</span> <span class="text-gray-400 hover:text-rose-600 font-black">&times;</span>`;
                                    btn.onclick = () => atenderChamado(ch.id);
                                    chipsContainer.appendChild(btn);
                                });
                            }

                            atualizarConteudoDrawer(data);
                        } else {
                            container.classList.add('hidden');
                            fecharDrawerChamados();
                        }
                    })
                    .catch(() => {});
            }

            function abrirDrawerChamados() {
                document.getElementById('drawerChamados').classList.remove('hidden');
            }

            function fecharDrawerChamados() {
                document.getElementById('drawerChamados').classList.add('hidden');
            }

            function filtrarDrawer(tipo) {
                filtroDrawerAtual = tipo;
                document.getElementById('drawerTabTodos').className = tipo === 'todos' ? 'py-2 px-2.5 border-b-2 border-amber-600 text-amber-900 font-extrabold flex-shrink-0' : 'py-2 px-2.5 border-b-2 border-transparent text-gray-500 hover:text-gray-900 flex-shrink-0';
                document.getElementById('drawerTabChat').className = tipo === 'chat' ? 'py-2 px-2.5 border-b-2 border-amber-600 text-amber-900 font-extrabold flex-shrink-0' : 'py-2 px-2.5 border-b-2 border-transparent text-gray-500 hover:text-gray-900 flex-shrink-0';
                document.getElementById('drawerTabGarcom').className = tipo === 'garcom' ? 'py-2 px-2.5 border-b-2 border-amber-600 text-amber-900 font-extrabold flex-shrink-0' : 'py-2 px-2.5 border-b-2 border-transparent text-gray-500 hover:text-gray-900 flex-shrink-0';
                document.getElementById('drawerTabConta').className = tipo === 'conta' ? 'py-2 px-2.5 border-b-2 border-amber-600 text-amber-900 font-extrabold flex-shrink-0' : 'py-2 px-2.5 border-b-2 border-transparent text-gray-500 hover:text-gray-900 flex-shrink-0';
                renderizarListaDrawer();
            }

            function atualizarConteudoDrawer(data) {
                document.getElementById('drawerSubtitulo').innerText = `${data.total} mesa(s) aguardando atendimento`;
                document.getElementById('drawerCountTodos').innerText = data.total;
                document.getElementById('drawerCountChat').innerText = data.chat_total || 0;
                document.getElementById('drawerCountGarcom').innerText = data.garcom_total || 0;
                document.getElementById('drawerCountConta').innerText = data.conta_total || 0;
                renderizarListaDrawer();
            }

            function renderizarListaDrawer() {
                const lista = document.getElementById('drawerListaChamados');
                let itensFiltrados = chamadosCache;
                if (filtroDrawerAtual === 'chat') {
                    itensFiltrados = chamadosCache.filter(c => c.tipo === 'chat_cliente');
                } else if (filtroDrawerAtual === 'garcom') {
                    itensFiltrados = chamadosCache.filter(c => c.tipo === 'chamado');
                } else if (filtroDrawerAtual === 'conta') {
                    itensFiltrados = chamadosCache.filter(c => c.tipo === 'conta');
                }

                if (itensFiltrados.length === 0) {
                    lista.innerHTML = `
                        <div class="text-center py-12 text-gray-400 bg-white rounded-2xl border border-gray-200 p-6">
                            <span class="text-4xl">✅</span>
                            <h4 class="text-sm font-bold text-gray-900 mt-2">Nenhum chamado pendente</h4>
                            <p class="text-xs text-gray-500 mt-1">Todas as mesas foram atendidas!</p>
                        </div>
                    `;
                    return;
                }

                lista.innerHTML = '';
                itensFiltrados.forEach(ch => {
                    if (ch.tipo === 'chat_cliente') {
                        const temMidia = ch.midia_url && ch.midia_url.trim() !== '';
                        lista.innerHTML += `
                            <div class="bg-white border-2 border-indigo-200 hover:border-indigo-400 rounded-2xl p-4 shadow-sm transition-all space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-200 flex items-center justify-center text-xl flex-shrink-0 font-black">
                                            💬
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="text-sm font-black text-gray-900 truncate m-0">Mesa ${ch.mesa_numero}</h4>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-xs font-bold text-indigo-600">Mensagem da Mesa</span>
                                                <span class="text-[11px] text-gray-400">&bull; ${ch.created_at}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" onclick="atenderChamado('${ch.id}')" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 text-[11px] font-bold rounded-lg transition" title="Marcar como lido">
                                        ✕ Dispensar
                                    </button>
                                </div>

                                ${temMidia ? `
                                    <div class="rounded-xl overflow-hidden border border-indigo-100 bg-indigo-50/50 p-1">
                                        <img src="${ch.midia_url}" onclick="abrirZoomImagemAdmin('${ch.midia_url}')" class="max-w-full max-h-44 rounded-lg object-cover cursor-pointer hover:opacity-90 transition shadow-sm" alt="Foto da Mesa">
                                        <span class="text-[10px] text-indigo-500 font-bold block mt-1 px-1">🔍 Clique para ampliar</span>
                                    </div>
                                ` : ''}

                                <div class="bg-indigo-50/70 border border-indigo-100 rounded-xl p-3 text-xs text-indigo-950 font-medium whitespace-pre-wrap">
                                    "${ch.texto}"
                                </div>

                                <!-- Respostas Rápidas do Garçom -->
                                <div class="flex items-center gap-1.5 flex-wrap pt-1">
                                    <button type="button" onclick="responderMesa('${ch.mesa_id}', 'A caminho com seu pedido! 👍', '${ch.id}')" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-lg shadow-sm transition">
                                        👍 "A caminho!"
                                    </button>
                                    <button type="button" onclick="responderMesa('${ch.mesa_id}', 'Um momento, já estou levando! 😊', '${ch.id}')" class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[11px] rounded-lg shadow-sm transition">
                                        ⚡ "Já estou levando!"
                                    </button>
                                    <button type="button" onclick="abrirModalRespostaGarcom('${ch.mesa_id}', '${ch.mesa_numero}', '${ch.id}', '${ch.texto ? encodeURIComponent(ch.texto) : ''}', '${ch.midia_url || ''}')" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-900 text-white font-bold text-[11px] rounded-lg shadow-sm transition flex items-center gap-1">
                                        <span>✏️ Responder</span>
                                        <span>📷</span>
                                    </button>
                                </div>
                            </div>
                        `;
                    } else {
                        lista.innerHTML += `
                            <div class="bg-white border border-gray-200 hover:border-amber-400 rounded-2xl p-4 shadow-sm transition-all flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-12 h-12 rounded-2xl ${ch.tipo === 'conta' ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'bg-amber-50 text-amber-600 border border-amber-200'} flex items-center justify-center text-2xl flex-shrink-0 font-black">
                                        ${ch.tipo === 'conta' ? '💳' : '👋'}
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-sm font-black text-gray-900 truncate m-0">Mesa ${ch.mesa_numero}</h4>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-xs font-bold ${ch.tipo === 'conta' ? 'text-rose-600' : 'text-amber-700'}">${ch.tipo_label}</span>
                                            <span class="text-[11px] text-gray-400">&bull; ${ch.created_at}</span>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" onclick="atenderChamado('${ch.id}')" class="px-3.5 py-2 bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white font-black text-xs rounded-xl border border-emerald-300 transition-all flex items-center gap-1 flex-shrink-0 active:scale-95 shadow-sm">
                                    <span>✓</span>
                                    <span>Atender</span>
                                </button>
                            </div>
                        `;
                    }
                });
            }

            const csrfToken = '<?= Yii::$app->request->csrfToken ?>';

            function atenderChamado(id) {
                fetch('<?= Url::to(['/vendas/mesa/atender-chamado']) ?>?id=' + id, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': csrfToken,
                        'Content-Type': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(() => checarChamadosHub())
                .catch(e => console.error('Erro ao atender chamado:', e));
            }

            function atenderTodosChamados() {
                fetch('<?= Url::to(['/vendas/mesa/atender-todos-chamados']) ?>', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': csrfToken,
                        'Content-Type': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(() => checarChamadosHub())
                .catch(e => console.error('Erro ao atender todos os chamados:', e));
            }

            function responderMesa(mesaId, mensagem, chamadoId, imagemFile = null) {
                if (!mensagem && !imagemFile) return;

                const formData = new FormData();
                formData.append('mesa_id', mesaId);
                if (mensagem) formData.append('mensagem', mensagem);
                if (chamadoId) formData.append('chamado_id', chamadoId);
                if (imagemFile) formData.append('imagem', imagemFile);

                fetch('<?= Url::to(['/vendas/mesa/responder-mensagem-mesa']) ?>', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': csrfToken
                    },
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        fecharModalRespostaGarcom();
                        checarChamadosHub();
                    } else {
                        alert(data.message || 'Erro ao enviar resposta.');
                    }
                })
                .catch(e => console.error('Erro ao responder mesa:', e));
            }

            let mesaRespostaAtual = null;
            let chamadoRespostaAtual = null;
            let fotoGarcomSelecionada = null;

            function abrirModalRespostaGarcom(mesaId, mesaNumero, chamadoId, textoMsg, midiaUrl) {
                mesaRespostaAtual = mesaId;
                chamadoRespostaAtual = chamadoId;
                fotoGarcomSelecionada = null;

                document.getElementById('lblModalRespMesaNumero').innerText = mesaNumero;
                document.getElementById('lblModalRespMsgCliente').innerText = decodeURIComponent(textoMsg || '');
                
                const boxMidia = document.getElementById('boxModalRespMidiaCliente');
                const imgMidia = document.getElementById('imgModalRespMidiaCliente');
                if (midiaUrl && midiaUrl.trim() !== '') {
                    imgMidia.src = midiaUrl;
                    boxMidia.classList.remove('hidden');
                } else {
                    boxMidia.classList.add('hidden');
                }

                document.getElementById('txtModalRespTexto').value = '';
                cancelarFotoGarcom();
                document.getElementById('modalRespostaGarcom').classList.remove('hidden');
                document.getElementById('txtModalRespTexto').focus();
            }

            function fecharModalRespostaGarcom() {
                document.getElementById('modalRespostaGarcom').classList.add('hidden');
            }

            function inserirEmojiGarcom(emoji) {
                const txt = document.getElementById('txtModalRespTexto');
                if (txt) {
                    txt.value += emoji;
                    txt.focus();
                }
            }

            function selecionarFotoGarcom(input) {
                if (input.files && input.files[0]) {
                    const file = input.files[0];
                    fotoGarcomSelecionada = file;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        document.getElementById('imgPreviewFotoGarcom').src = e.target.result;
                        document.getElementById('boxPreviewFotoGarcom').classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                }
            }

            function cancelarFotoGarcom() {
                fotoGarcomSelecionada = null;
                const input = document.getElementById('inputFotoGarcom');
                if (input) input.value = '';
                const box = document.getElementById('boxPreviewFotoGarcom');
                if (box) box.classList.add('hidden');
            }

            function enviarRespostaGarcomModal() {
                const msg = document.getElementById('txtModalRespTexto').value.trim();
                if (!msg && !fotoGarcomSelecionada) {
                    alert('Digite uma mensagem ou anexe uma foto.');
                    return;
                }
                responderMesa(mesaRespostaAtual, msg, chamadoRespostaAtual, fotoGarcomSelecionada);
            }

            function abrirZoomImagemAdmin(url) {
                const modal = document.getElementById('modalZoomImagemAdmin');
                const img = document.getElementById('imgZoomAdmin');
                if (modal && img) {
                    img.src = url;
                    modal.classList.remove('hidden');
                }
            }

            function fecharZoomImagemAdmin() {
                const modal = document.getElementById('modalZoomImagemAdmin');
                if (modal) modal.classList.add('hidden');
            }

            window.responderMesa = responderMesa;
            window.abrirModalRespostaGarcom = abrirModalRespostaGarcom;
            window.fecharModalRespostaGarcom = fecharModalRespostaGarcom;
            window.inserirEmojiGarcom = inserirEmojiGarcom;
            window.selecionarFotoGarcom = selecionarFotoGarcom;
            window.cancelarFotoGarcom = cancelarFotoGarcom;
            window.enviarRespostaGarcomModal = enviarRespostaGarcomModal;
            window.abrirZoomImagemAdmin = abrirZoomImagemAdmin;
            window.fecharZoomImagemAdmin = fecharZoomImagemAdmin;
            window.atenderChamado = atenderChamado;
            window.atenderTodosChamados = atenderTodosChamados;

            setInterval(checarChamadosHub, 5000);
            checarChamadosHub();
        </script>

        <!-- MODAL DE RESPOSTA RICA DO GARÇOM COM FOTOS E EMOJIS -->
        <div id="modalRespostaGarcom" class="fixed inset-0 z-50 hidden bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">💬</span>
                        <div>
                            <h3 class="text-base font-extrabold text-gray-900 m-0">Responder Mesa <span id="lblModalRespMesaNumero">01</span></h3>
                            <p class="text-xs text-gray-500 m-0">Canal Próprio Direct Hub</p>
                        </div>
                    </div>
                    <button type="button" onclick="fecharModalRespostaGarcom()" class="text-gray-400 hover:text-gray-700 text-2xl font-bold p-1">&times;</button>
                </div>

                <!-- Mensagem Original do Cliente -->
                <div class="bg-indigo-50/60 border border-indigo-100 rounded-2xl p-3.5 space-y-2">
                    <span class="text-[10px] font-black uppercase tracking-wider text-indigo-600 block">Mensagem do Cliente:</span>
                    <div id="boxModalRespMidiaCliente" class="hidden">
                        <img id="imgModalRespMidiaCliente" src="" class="max-w-full max-h-36 rounded-xl object-cover border border-indigo-200 cursor-pointer shadow-sm" onclick="abrirZoomImagemAdmin(this.src)">
                    </div>
                    <p id="lblModalRespMsgCliente" class="text-xs text-indigo-950 font-medium whitespace-pre-wrap m-0"></p>
                </div>

                <!-- Seletor de Emojis Populares -->
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-gray-400 block mb-1.5">⚡ Inserir Emojis:</span>
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-base">
                        <?php
                        $emojisGarcom = ['👍', '😊', '👋', '⚡', '🍔', '🥤', '🍺', '🧊', '🧂', '💳', '🧾', '⏱️', '🚀', '✅', '🙏', '❤️'];
                        foreach ($emojisGarcom as $eg):
                        ?>
                            <button type="button" onclick="inserirEmojiGarcom('<?= $eg ?>')" class="w-8 h-8 rounded-xl bg-gray-100 hover:bg-indigo-100 flex items-center justify-center transition flex-shrink-0 active:scale-90">
                                <?= $eg ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Preview de Foto do Garçom -->
                <div id="boxPreviewFotoGarcom" class="hidden bg-gray-50 border border-indigo-200 rounded-2xl p-2 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <img id="imgPreviewFotoGarcom" src="" class="w-12 h-12 rounded-xl object-cover border border-gray-200">
                        <div class="min-w-0">
                            <span class="text-xs font-bold text-gray-900 block truncate">Foto anexada</span>
                            <span class="text-[10px] text-indigo-600 font-medium">Pronta para enviar</span>
                        </div>
                    </div>
                    <button type="button" onclick="cancelarFotoGarcom()" class="p-1.5 text-rose-500 hover:text-rose-700 font-black text-xs hover:bg-rose-50 rounded-lg transition" title="Remover foto">
                        ✕
                    </button>
                </div>

                <!-- Campo de Resposta -->
                <div>
                    <input type="file" id="inputFotoGarcom" accept="image/*" class="hidden" onchange="selecionarFotoGarcom(this)">
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="document.getElementById('inputFotoGarcom').click()" class="p-3 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-base transition active:scale-95 flex-shrink-0" title="Anexar Foto">
                            📷
                        </button>
                        <input type="text" id="txtModalRespTexto" placeholder="Digite sua resposta..." class="flex-1 px-4 py-3 bg-gray-50 border border-gray-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-xs text-gray-900 placeholder-gray-400">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" onclick="fecharModalRespostaGarcom()" class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 text-xs font-bold transition">
                        Cancelar
                    </button>
                    <button type="button" onclick="enviarRespostaGarcomModal()" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-extrabold shadow-md transition flex items-center gap-1.5 active:scale-95">
                        <span>Enviar Resposta</span>
                        <span>🚀</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL LIGHTBOX DE ZOOM DE IMAGEM ADMIN -->
        <div id="modalZoomImagemAdmin" class="fixed inset-0 z-50 hidden bg-gray-950/90 backdrop-blur-md flex items-center justify-center p-4" onclick="fecharZoomImagemAdmin()">
            <div class="relative max-w-full max-h-full" onclick="event.stopPropagation()">
                <img id="imgZoomAdmin" src="" class="max-w-full max-h-[85vh] rounded-3xl object-contain shadow-2xl border border-gray-700">
                <button type="button" onclick="fecharZoomImagemAdmin()" class="absolute top-3 right-3 bg-gray-900/80 text-white p-2 rounded-full hover:bg-gray-800 transition font-black text-sm">
                    &times;
                </button>
            </div>
        </div>

        <!-- Cards de Estatísticas & Indicadores Rápidos -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
            <!-- Total de Mesas -->
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Mesas</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-1"><?= $totalMesas ?></p>
            </div>

            <!-- Livres -->
            <div class="bg-emerald-50 rounded-2xl p-4 shadow-sm border border-emerald-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-emerald-800 uppercase tracking-wider">🟢 Livres</p>
                    <span class="text-xl">🟢</span>
                </div>
                <p class="text-2xl sm:text-3xl font-extrabold text-emerald-900 mt-1"><?= $livres ?></p>
            </div>

            <!-- Ocupadas -->
            <div class="bg-rose-50 rounded-2xl p-4 shadow-sm border border-rose-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-rose-800 uppercase tracking-wider">🔴 Ocupadas</p>
                    <span class="text-xl">🔴</span>
                </div>
                <p class="text-2xl sm:text-3xl font-extrabold text-rose-900 mt-1"><?= $ocupadas ?></p>
            </div>

            <!-- Conta Solicitada -->
            <div class="bg-amber-50 rounded-2xl p-4 shadow-sm border border-amber-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-amber-800 uppercase tracking-wider">🟡 Conta</p>
                    <span class="text-xl">🟡</span>
                </div>
                <p class="text-2xl sm:text-3xl font-extrabold text-amber-900 mt-1"><?= $aguardandoConta ?></p>
            </div>

            <!-- Consumo Acumulado -->
            <div class="col-span-2 sm:col-span-1 bg-gradient-to-br from-indigo-600 to-purple-700 text-white rounded-2xl p-4 shadow-md">
                <p class="text-xs font-semibold text-indigo-100 uppercase tracking-wider">💰 Consumo em Aberto</p>
                <p class="text-xl sm:text-2xl font-black mt-1">R$ <?= number_format($faturamentoAcumulado, 2, ',', '.') ?></p>
            </div>
        </div>

        <!-- Grid de Mesas Gráfico -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <span>📋 Salão Principal</span>
                    <span class="text-xs text-gray-500 font-normal">(Clique na mesa para ações rápidas)</span>
                </h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                <?php foreach ($mesas as $mesa): ?>
                    <?php
                    $badge = $mesa->getStatusBadge();
                    $consumo = $mesa->getConsumoTotal();
                    $comanda = $mesa->comandaAtiva;
                    ?>
                    <div class="group bg-white rounded-2xl border-2 <?= $badge['border'] ?> p-4 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between relative overflow-hidden">
                        
                        <!-- Top Header do Card -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-2">
                                <span class="text-xl"><?= $badge['icon'] ?></span>
                                <span class="font-extrabold text-lg text-gray-900">Mesa <?= Html::encode($mesa->numero_mesa) ?></span>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold <?= $badge['badge'] ?>">
                                <?= $badge['label'] ?>
                            </span>
                        </div>

                        <!-- Info do Consumo / Cliente -->
                        <div class="my-2 py-3 px-3 bg-gray-50 rounded-xl space-y-1">
                            <div class="flex justify-between items-center text-xs text-gray-500">
                                <span>Capacidade:</span>
                                <span class="font-semibold text-gray-700"><?= $mesa->lugares ?> pessoas</span>
                            </div>

                            <?php if ($comanda): ?>
                                <div class="flex justify-between items-center text-xs text-gray-500">
                                    <span>Cliente:</span>
                                    <span class="font-bold text-gray-900 truncate max-w-[110px]"><?= Html::encode($comanda->cliente_nome ?: 'Cliente') ?></span>
                                </div>
                                <div class="pt-2 border-t border-gray-200 flex justify-between items-center">
                                    <span class="text-xs font-bold text-gray-700">Consumo:</span>
                                    <span class="text-base font-black text-emerald-600">R$ <?= number_format($consumo, 2, ',', '.') ?></span>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-2 text-xs text-gray-400 font-medium">
                                    Mesa sem consumo ativo
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Alerta Visual: Garçom Chamado -->
                        <?php if ($mesa->chamada_garcom): ?>
                            <div class="mt-2 bg-amber-500 text-gray-950 font-black text-center text-xs py-1 rounded-lg animate-pulse shadow flex items-center justify-center gap-1">
                                <span>🔔</span>
                                <span>GARÇOM CHAMADO!</span>
                            </div>
                        <?php endif; ?>

                        <!-- Botões de Ação Dinâmicos -->
                        <div class="mt-3 pt-2 border-t border-gray-100 flex flex-col gap-2">
                            <button type="button" onclick="abrirModalQrCodeMesa('<?= $mesa->id ?>', '<?= Html::encode($mesa->numero_mesa) ?>')" class="w-full py-1 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs rounded-xl flex items-center justify-center gap-1 border border-gray-300 transition">
                                <span>📱</span>
                                <span>QR Code da Mesa</span>
                            </button>

                            <?php if ($mesa->status === \app\modules\vendas\models\Mesa::STATUS_LIVRE): ?>
                                <button type="button" onclick="abrirModalMesa('<?= $mesa->id ?>', '<?= Html::encode($mesa->numero_mesa) ?>')"
                                    class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition duration-150 flex items-center justify-center gap-1">
                                    <span>🚀</span>
                                    <span>Abrir Mesa</span>
                                </button>

                                <?= Html::beginForm(Url::to(['/vendas/mesa/excluir-mesa']), 'post', ['class' => 'm-0', 'onbeforeSubmit' => 'return confirm("Tem certeza que deseja excluir a Mesa ' . Html::encode($mesa->numero_mesa) . '?")']) ?>
                                <input type="hidden" name="mesa_id" value="<?= $mesa->id ?>">
                                <button type="submit" class="w-full py-1 text-rose-600 hover:text-rose-800 font-semibold text-[11px] flex items-center justify-center gap-1 hover:underline">
                                    <span>🗑️ Excluir Mesa</span>
                                </button>
                                <?= Html::endForm() ?>

                            <?php elseif ($mesa->status === \app\modules\vendas\models\Mesa::STATUS_OCUPADA || $mesa->status === \app\modules\vendas\models\Mesa::STATUS_AGUARDANDO_CONTA): ?>
                                <button type="button" onclick="abrirModalLancamento('<?= $mesa->id ?>', '<?= Html::encode($mesa->numero_mesa) ?>', '<?= Html::encode($comanda ? $comanda->cliente_nome : '') ?>')"
                                    class="w-full py-2 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white font-bold text-xs rounded-xl shadow transition duration-150 flex items-center justify-center gap-1">
                                    <span>➕</span>
                                    <span>Lançar Pedidos / Extrato</span>
                                </button>

                                <div class="grid grid-cols-2 gap-1.5 mt-1">
                                    <?php if ($mesa->status === \app\modules\vendas\models\Mesa::STATUS_OCUPADA): ?>
                                        <?php if ($consumo > 0): ?>
                                            <?= Html::beginForm(Url::to(['/vendas/mesa/solicitar-conta']), 'post', ['class' => 'm-0']) ?>
                                            <input type="hidden" name="mesa_id" value="<?= $mesa->id ?>">
                                            <button type="submit" class="w-full py-1.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px] rounded-lg transition duration-150">
                                                🟡 Pedir Conta
                                            </button>
                                            <?= Html::endForm() ?>
                                        <?php else: ?>
                                            <button type="button" onclick="alert('⚠️ Não é possível solicitar conta para uma mesa sem pedidos/consumo lançados.')" class="w-full py-1.5 bg-gray-200 text-gray-400 font-bold text-[11px] rounded-lg cursor-not-allowed">
                                                🟡 Pedir Conta
                                            </button>
                                        <?php endif; ?>

                                        <button type="button" onclick="abrirModalFechamento('<?= $mesa->id ?>', '<?= Html::encode($mesa->numero_mesa) ?>', '<?= Html::encode($comanda ? $comanda->cliente_nome : '') ?>')"
                                            class="w-full py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-[11px] rounded-lg transition duration-150 shadow-sm">
                                            🧾 Fechar & Dividir
                                        </button>

                                    <?php elseif ($mesa->status === \app\modules\vendas\models\Mesa::STATUS_AGUARDANDO_CONTA): ?>
                                        <button type="button" onclick="abrirModalFechamento('<?= $mesa->id ?>', '<?= Html::encode($mesa->numero_mesa) ?>', '<?= Html::encode($comanda ? $comanda->cliente_nome : '') ?>')"
                                            class="w-full py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-lg transition duration-150 shadow-sm">
                                            🧾 Fechar & Dividir
                                        </button>

                                        <?= Html::beginForm(Url::to(['/vendas/mesa/reverter-mesa']), 'post', ['class' => 'm-0']) ?>
                                        <input type="hidden" name="mesa_id" value="<?= $mesa->id ?>">
                                        <button type="submit" class="w-full py-1.5 bg-sky-600 hover:bg-sky-700 text-white font-bold text-[11px] rounded-lg transition duration-150" title="Reabrir mesa para continuar lançando consumos">
                                            🔄 Reabrir Mesa
                                        </button>
                                        <?= Html::endForm() ?>
                                    <?php endif; ?>
                                </div>

                                <button type="button" onclick="abrirModalTransferir('<?= $mesa->id ?>', '<?= Html::encode($mesa->numero_mesa) ?>')"
                                    class="w-full mt-1.5 py-1 bg-sky-100 hover:bg-sky-200 text-sky-800 font-bold text-[11px] rounded-lg transition duration-150 flex items-center justify-center gap-1 border border-sky-200">
                                    <span>🔀</span>
                                    <span>Transferir Mesa</span>
                                </button>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<?= $this->render('_modal_abrir_mesa', ['colaboradores' => $colaboradores ?? []]) ?>
<?= $this->render('_modal_lancamento_item') ?>
<?= $this->render('_modal_fechamento_mesa') ?>
<?= $this->render('_modal_transferir_mesa', ['mesas' => $mesas]) ?>
<?= $this->render('_modal_criar_mesa') ?>
<?= $this->render('_modal_gerar_lote_mesas') ?>
<?= $this->render('_modal_qr_code_mesa') ?>
