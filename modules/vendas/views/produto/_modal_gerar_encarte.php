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

            <!-- Banner do Lote Selecionado -->
            <div class="bg-gradient-to-r from-amber-50 to-red-50 border border-amber-200 p-4 rounded-2xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-red-600 text-white font-extrabold text-lg flex items-center justify-center shadow" id="badgeQtdEncarte">0</span>
                    <div>
                        <div class="font-extrabold text-sm text-red-950">Produto(s) Selecionado(s)</div>
                        <div class="text-xs text-red-700">Este lote será diagramado em lâminas de tabloide no encarte.</div>
                    </div>
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
                        </select>
                    </div>
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
                    <h4 class="font-extrabold text-sm text-gray-900 flex items-center gap-2">
                        <span class="p-1.5 bg-green-100 text-green-700 rounded-lg">💬</span>
                        Disparar Link + PDF via WhatsApp (Evolution API)
                    </h4>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Telefones Adicionais de Destino (opcional)</label>
                        <textarea id="encarte_telefones" rows="2" class="w-full p-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-red-500" placeholder="Digite ou cole telefones separados por vírgula ou linha (ex: 81999998888, 81988887777)"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Mensagem de Acompanhamento</label>
                        <textarea id="encarte_mensagem" rows="2" class="w-full p-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-red-500">🔥 *CONFIRA NOSSO NOVO ENCARTE DE OFERTAS!* 🔥

Aproveite nossos preços especiais válidos esta semana!</textarea>
                    </div>

                    <button type="button" id="btnEnviarEncarteWp" onclick="dispararEncarteEvolution()" class="w-full py-3.5 bg-green-600 hover:bg-green-700 text-white font-extrabold rounded-2xl transition shadow-lg flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Disparar Encarte e PDF via WhatsApp (Evolution)
                    </button>
                </div>

            </div>

        </div>
    </div>
</div>

<script>
    let produtosEncarteSelecionados = [];
    let ultimoEncarteId = null;

    function abrirModalGerarEncarte(ids = []) {
        produtosEncarteSelecionados = ids;
        document.getElementById('badgeQtdEncarte').textContent = produtosEncarteSelecionados.length;
        document.getElementById('modalGerarEncarte').classList.remove('hidden');
        document.getElementById('boxResultadoEncarte').classList.add('hidden');
    }

    function fecharModalGerarEncarte() {
        document.getElementById('modalGerarEncarte').classList.add('hidden');
    }

    function gerarLinkEncartePublico() {
        if (produtosEncarteSelecionados.length === 0) {
            alert('Nenhum produto selecionado.');
            return;
        }

        const payload = {
            produtos_ids: produtosEncarteSelecionados,
            titulo: document.getElementById('encarte_titulo').value,
            subtitulo: document.getElementById('encarte_subtitulo').value,
            cor_tema: document.getElementById('encarte_cor_tema').value,
            produtos_por_pagina: document.getElementById('encarte_ppp').value,
            '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
        };

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
        if (produtosEncarteSelecionados.length === 0) {
            alert('Nenhum produto selecionado.');
            return;
        }

        if (ultimoEncarteId) {
            window.open('<?= Url::to(['/vendas/encarte/download-pdf']) ?>?id=' + ultimoEncarteId, '_blank');
            return;
        }

        // Se ainda não gerou no banco, gera primeiro
        const payload = {
            produtos_ids: produtosEncarteSelecionados,
            titulo: document.getElementById('encarte_titulo').value,
            subtitulo: document.getElementById('encarte_subtitulo').value,
            cor_tema: document.getElementById('encarte_cor_tema').value,
            produtos_por_pagina: document.getElementById('encarte_ppp').value,
            '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
        };

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
            const payload = {
                produtos_ids: produtosEncarteSelecionados,
                titulo: document.getElementById('encarte_titulo').value,
                subtitulo: document.getElementById('encarte_subtitulo').value,
                cor_tema: document.getElementById('encarte_cor_tema').value,
                produtos_por_pagina: document.getElementById('encarte_ppp').value,
                '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
            };

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
</script>
