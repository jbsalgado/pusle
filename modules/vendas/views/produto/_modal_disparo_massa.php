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

                <!-- 2. Canais de Disparo -->
                <div class="border-b border-gray-100 pb-5">
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

                <!-- Botão de Início do Disparo -->
                <button onclick="iniciarDisparoEmMassa()" class="w-full py-4 px-6 bg-gradient-to-r from-purple-700 to-indigo-700 hover:from-purple-800 hover:to-indigo-800 text-white font-extrabold rounded-2xl transition duration-300 shadow-xl flex items-center justify-center gap-3 text-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Iniciar Disparo em Massa
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

        </div>
    </div>
</div>

<script>
    let produtosSelecionadosDisparo = [];
    let intervalMonitoramento = null;
    let listaClientesCache = [];
    let whatsappConectadoCache = false;
    let ultimoDisparoIdAtivo = null;

    function abrirModalDisparoMassa(produtosIds = []) {
        produtosSelecionadosDisparo = produtosIds;
        document.getElementById('qtdProdutosBadge').textContent = produtosSelecionadosDisparo.length;
        
        document.getElementById('modalDisparoMassa').classList.remove('hidden');
        document.getElementById('secaoConfigDisparo').classList.remove('hidden');
        document.getElementById('secaoProgressoDisparo').classList.add('hidden');
        document.getElementById('containerErrosDisparo').classList.add('hidden');
        document.getElementById('btnFecharDisparoConcluido').classList.add('hidden');

        verificarStatusWhatsapp();
        carregarListaClientes();
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

        const payload = {
            produtos_ids: produtosSelecionadosDisparo,
            canais: canais,
            clientes_ids: clientesIds,
            telefones_manuais: telefonesManuais,
            emails_manuais: emailsManuais,
            template: document.getElementById('disparo_template').value,
            cor_tema: document.getElementById('disparo_cor_tema').value,
            fundo_estilo: document.getElementById('disparo_fundo_estilo').value,
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
</script>
