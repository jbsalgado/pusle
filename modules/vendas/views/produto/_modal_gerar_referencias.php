<?php

use yii\helpers\Url;
use yii\helpers\Html;

?>

<!-- Modal de Geração Automática de Referências em Lote -->
<div id="modalGerarReferencias" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full overflow-hidden transform transition-all border border-gray-100 flex flex-col max-h-[92vh]">
        
        <!-- Header do Modal -->
        <div class="bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-700 px-6 py-5 text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-white/20 backdrop-blur-md rounded-2xl border border-white/30 text-xl">
                    🏷️
                </div>
                <div>
                    <h3 class="text-xl font-extrabold tracking-tight">Gerar Referências em Lote</h3>
                    <p class="text-xs text-cyan-100 font-medium">Crie e padronize códigos de referência automáticos para seus produtos</p>
                </div>
            </div>
            <button type="button" onclick="fecharModalGerarReferencias()" class="text-cyan-100 hover:text-white hover:bg-white/10 p-2 rounded-xl transition cursor-pointer">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Conteúdo do Modal -->
        <div class="p-6 space-y-5 overflow-y-auto flex-1">

            <!-- Cards de Estatísticas em Tempo Real -->
            <div class="grid grid-cols-3 gap-3" id="statsReferenciasContainer">
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3 text-center">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-0.5">Total Produtos</span>
                    <span class="text-xl font-black text-slate-800" id="statTotalProdutos">...</span>
                </div>
                <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-3 text-center">
                    <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block mb-0.5">Com Referência</span>
                    <span class="text-xl font-black text-emerald-600" id="statComReferencia">...</span>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-3 text-center">
                    <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wider block mb-0.5">Sem Referência</span>
                    <span class="text-xl font-black text-amber-600" id="statSemReferencia">...</span>
                </div>
            </div>

            <!-- Formulário de Opções -->
            <form id="formGerarReferencias" class="space-y-4">

                <!-- Escopo da Geração -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Qual grupo de produtos deseja atualizar?
                    </label>

                    <div class="space-y-2">
                        <!-- Opção 1: Apenas sem referência (Recomendado) -->
                        <label class="flex items-start p-3 bg-cyan-50/70 border-2 border-cyan-400 rounded-2xl cursor-pointer hover:bg-cyan-50 transition group" id="cardModoSemRef">
                            <input type="radio" name="modo_geracao_ref" value="apenas_sem_referencia" checked class="mt-0.5 text-cyan-600 focus:ring-cyan-500 cursor-pointer" onchange="atualizarEstiloModoRef(this.value)">
                            <div class="ml-3 text-left">
                                <div class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                    <span>Apenas produtos SEM referência</span>
                                    <span class="bg-cyan-600 text-white text-[9px] font-extrabold px-1.5 py-0.2 rounded-full uppercase">Recomendado</span>
                                </div>
                                <p class="text-[11px] text-slate-600 mt-0.5">
                                    Preserva todas as referências já cadastradas e gera códigos sequenciais apenas para os itens pendentes.
                                </p>
                            </div>
                        </label>

                        <!-- Opção 2: Apenas itens selecionados -->
                        <label class="flex items-start p-3 bg-white border-2 border-slate-200 rounded-2xl cursor-pointer hover:bg-slate-50 transition group" id="cardModoSelecionados">
                            <input type="radio" name="modo_geracao_ref" value="selecionados" class="mt-0.5 text-cyan-600 focus:ring-cyan-500 cursor-pointer" onchange="atualizarEstiloModoRef(this.value)">
                            <div class="ml-3 text-left">
                                <div class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                    <span>Apenas itens selecionados na página</span>
                                    <span class="text-xs font-black text-indigo-600" id="badgeQtdRefsSelecionadas">(0 marcados)</span>
                                </div>
                                <p class="text-[11px] text-slate-600 mt-0.5">
                                    Aplica a geração exclusivamente aos produtos que você marcou com a caixinha de seleção.
                                </p>
                            </div>
                        </label>

                        <!-- Opção 3: Todos os produtos (Sobrescrever tudo) -->
                        <label class="flex items-start p-3 bg-white border-2 border-slate-200 rounded-2xl cursor-pointer hover:bg-slate-50 transition group" id="cardModoTodos">
                            <input type="radio" name="modo_geracao_ref" value="todos" class="mt-0.5 text-cyan-600 focus:ring-cyan-500 cursor-pointer" onchange="atualizarEstiloModoRef(this.value)">
                            <div class="ml-3 text-left">
                                <div class="text-xs font-bold text-red-600 flex items-center gap-1.5">
                                    <span>Todos os produtos da loja</span>
                                    <span class="bg-red-100 text-red-700 text-[9px] font-extrabold px-1.5 py-0.2 rounded-full uppercase border border-red-200">Sobrescrever Tudo</span>
                                </div>
                                <p class="text-[11px] text-slate-600 mt-0.5">
                                    Recalcula e padroniza o catálogo inteiro a partir do sequencial 0001 para cada categoria.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Configuração de Padrão e Prefixo Fallback -->
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Regra de Nomenclatura:</span>
                        <span class="text-[11px] font-mono font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">SIGLA-0001</span>
                    </div>
                    <p class="text-[11px] text-slate-500">
                        O código será gerado automaticamente utilizando a sigla da categoria (ex: Categoria <strong>Eletrônicos</strong> gera <strong>ELET-0001</strong>).
                    </p>
                    
                    <div class="pt-2 border-t border-slate-200 flex items-center justify-between gap-3">
                        <label for="inputPrefixoFallback" class="text-xs font-semibold text-slate-700">
                            Prefixo para itens sem categoria:
                        </label>
                        <input type="text" id="inputPrefixoFallback" name="prefixo_sem_categoria" value="GER" maxlength="6"
                               class="w-24 px-3 py-1.5 text-xs font-mono font-black uppercase text-center border border-slate-300 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none">
                    </div>
                </div>

                <!-- Alerta de Segurança (visível ao selecionar "Todos") -->
                <div id="alertaSobrescrever" class="hidden p-3 bg-red-50 border border-red-200 rounded-2xl flex items-start gap-2.5 text-red-800 text-xs">
                    <span class="text-base">⚠️</span>
                    <div>
                        <strong>Atenção:</strong> Esta opção substituirá as referências de todos os produtos cadastrados por novos códigos sequenciais.
                    </div>
                </div>

            </form>

            <!-- Loading State -->
            <div id="loadingGerarRefs" class="hidden p-6 text-center space-y-3">
                <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-cyan-600 border-t-transparent"></div>
                <div class="text-sm font-bold text-slate-800">Gerando referências no banco de dados...</div>
                <div class="text-xs text-slate-500">Por favor, aguarde alguns segundos sem fechar esta janela.</div>
            </div>

        </div>

        <!-- Footer do Modal -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between" id="footerModalGerarRefs">
            <button type="button" onclick="fecharModalGerarReferencias()" class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 hover:bg-slate-200/60 rounded-xl transition cursor-pointer">
                Cancelar
            </button>
            <button type="button" onclick="executarGeracaoReferencias()" id="btnConfirmarGerarRefs" class="px-6 py-2.5 bg-gradient-to-r from-cyan-600 to-blue-700 hover:from-cyan-700 hover:to-blue-800 text-white font-bold text-xs sm:text-sm rounded-xl shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer flex items-center gap-2">
                <span>⚡</span>
                <span>Iniciar Geração</span>
            </button>
        </div>

    </div>
</div>

<script>
window.abrirModalGerarReferencias = function() {
    const modal = document.getElementById('modalGerarReferencias');
    if (!modal) return;
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    // Atualiza contagem de selecionados
    const checkboxes = document.querySelectorAll('input[name="produto_massa_chk"]:checked');
    const badgeQtd = document.getElementById('badgeQtdRefsSelecionadas');
    if (badgeQtd) {
        badgeQtd.innerText = `(${checkboxes.length} marcados)`;
    }

    // Carrega estatísticas atualizadas via AJAX
    fetch('<?= Url::to(['/vendas/produto/stats-referencias']) ?>')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('statTotalProdutos').innerText = data.total;
                document.getElementById('statComReferencia').innerText = data.com_referencia;
                document.getElementById('statSemReferencia').innerText = data.sem_referencia;
            }
        })
        .catch(err => {
            console.error('Erro ao buscar stats de referências:', err);
        });
};

window.fecharModalGerarReferencias = function() {
    const modal = document.getElementById('modalGerarReferencias');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
};

window.atualizarEstiloModoRef = function(modo) {
    const cardSemRef = document.getElementById('cardModoSemRef');
    const cardSelecionados = document.getElementById('cardModoSelecionados');
    const cardTodos = document.getElementById('cardModoTodos');
    const alertaSobrescrever = document.getElementById('alertaSobrescrever');

    [cardSemRef, cardSelecionados, cardTodos].forEach(card => {
        if (card) {
            card.classList.remove('border-cyan-400', 'bg-cyan-50/70', 'border-red-400', 'bg-red-50/70', 'border-indigo-400', 'bg-indigo-50/70');
            card.classList.add('border-slate-200', 'bg-white');
        }
    });

    if (modo === 'apenas_sem_referencia' && cardSemRef) {
        cardSemRef.classList.remove('border-slate-200', 'bg-white');
        cardSemRef.classList.add('border-cyan-400', 'bg-cyan-50/70');
        if (alertaSobrescrever) alertaSobrescrever.classList.add('hidden');
    } else if (modo === 'selecionados' && cardSelecionados) {
        cardSelecionados.classList.remove('border-slate-200', 'bg-white');
        cardSelecionados.classList.add('border-indigo-400', 'bg-indigo-50/70');
        if (alertaSobrescrever) alertaSobrescrever.classList.add('hidden');
    } else if (modo === 'todos' && cardTodos) {
        cardTodos.classList.remove('border-slate-200', 'bg-white');
        cardTodos.classList.add('border-red-400', 'bg-red-50/70');
        if (alertaSobrescrever) alertaSobrescrever.classList.remove('hidden');
    }
};

window.executarGeracaoReferencias = function() {
    const modoChecked = document.querySelector('input[name="modo_geracao_ref"]:checked');
    const modo = modoChecked ? modoChecked.value : 'apenas_sem_referencia';
    const prefixo = document.getElementById('inputPrefixoFallback').value.trim() || 'GER';
    
    let ids = [];
    if (modo === 'selecionados') {
        const checkboxes = document.querySelectorAll('input[name="produto_massa_chk"]:checked');
        ids = Array.from(checkboxes).map(c => c.value);
        if (ids.length === 0) {
            alert('Nenhum produto está selecionado na página! Marque os produtos desejados ou escolha a opção "Apenas produtos SEM referência".');
            return;
        }
    }

    if (modo === 'todos') {
        if (!confirm('CONFIRMAÇÃO:\n\nVocê escolheu a opção para SOBRESCREVER as referências de TODOS os produtos do catálogo.\n\nTem certeza de que deseja prosseguir?')) {
            return;
        }
    }

    // Exibe loading
    const form = document.getElementById('formGerarReferencias');
    const loading = document.getElementById('loadingGerarRefs');
    const footer = document.getElementById('footerModalGerarRefs');

    if (form) form.classList.add('hidden');
    if (loading) loading.classList.remove('hidden');
    if (footer) footer.classList.add('hidden');

    const formData = new FormData();
    formData.append('modo', modo);
    formData.append('prefixo_sem_categoria', prefixo);
    ids.forEach(id => formData.append('ids[]', id));

    // Token CSRF do Yii2
    const csrfParam = '<?= Yii::$app->request->csrfParam ?>';
    const csrfToken = '<?= Yii::$app->request->csrfToken ?>';
    formData.append(csrfParam, csrfToken);

    fetch('<?= Url::to(['/vendas/produto/gerar-referencias-lote']) ?>', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            window.location.reload();
        } else {
            alert('❌ Erro: ' + (data.message || 'Falha ao gerar referências.'));
            if (form) form.classList.remove('hidden');
            if (loading) loading.classList.add('hidden');
            if (footer) footer.classList.remove('hidden');
        }
    })
    .catch(err => {
        alert('❌ Erro de comunicação com o servidor: ' + err.message);
        if (form) form.classList.remove('hidden');
        if (loading) loading.classList.add('hidden');
        if (footer) footer.classList.remove('hidden');
    });
};
</script>
