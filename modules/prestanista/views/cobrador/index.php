<?php
/** @var yii\web\View $this */
/** @var string $lojaNome */
/** @var app\modules\vendas\models\Colaborador[] $cobradores */
/** @var string|null $cobradorId */
/** @var string|null $usuarioId */
/** @var app\modules\vendas\models\Colaborador|null $colaboradorLogado */
/** @var bool $ehSupervisor */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'App do Cobrador de Rua | Pulse Prestanista';
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= Html::encode($this->title) ?></title>

    <!-- Google Fonts & Tailwind -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        brand: { 500: '#f59e0b', 600: '#d97706', 700: '#b45309' }
                    }
                }
            }
        }
    </script>

    <!-- Bibliotecas para PDF e Imagem Client-side 100% Offline -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        .cartao-preview {
            font-family: 'Courier New', Courier, monospace;
            background: #fff;
            color: #000;
        }
        .cartao-table th, .cartao-table td {
            border: 1px solid #000;
            padding: 3px 2px;
            font-size: 8px;
            line-height: 1.1;
        }
    </style>
</head>
<body class="h-full bg-slate-950 text-slate-100 flex flex-col font-sans select-none">

    <!-- Header do App -->
    <header class="sticky top-0 z-40 bg-slate-900 border-b border-slate-800 px-4 py-3 flex items-center justify-between shadow-md">
        <div class="flex items-center gap-2">
            <span class="text-2xl">🛵</span>
            <div>
                <h1 class="text-sm font-black text-white leading-tight">Cobrador de Rua</h1>
                <p class="text-[10px] text-amber-400 font-bold leading-tight"><?= Html::encode($lojaNome) ?></p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Status de Rede -->
            <div id="badge-status-rede" class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span id="lbl-status-rede">Online</span>
            </div>

            <!-- Botão Sincronizar Baixas -->
            <button onclick="sincronizarBaixas()" id="btn-sync-topo" class="relative p-2 bg-slate-800 hover:bg-slate-700 text-amber-400 rounded-xl border border-slate-700 active:scale-95 transition" title="Sincronizar Baixas">
                <span class="text-base">🔄</span>
                <span id="badge-pendentes-sync" class="hidden absolute -top-1 -right-1 bg-rose-500 text-white font-black text-[9px] w-4 h-4 rounded-full flex items-center justify-center">0</span>
            </button>

            <?php if (!Yii::$app->user->isGuest): ?>
            <a href="<?= Url::to(['/auth/logout']) ?>" data-method="post" class="p-2 bg-slate-800 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 rounded-xl border border-slate-700 active:scale-95 transition" title="Sair do Sistema">
                <span class="text-base">🚪</span>
            </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Barra de Filtro de Cobrador e Busca na Rota -->
    <div class="bg-slate-900/90 border-b border-slate-800 p-3 flex flex-col sm:flex-row gap-2 items-center justify-between">
        <div class="w-full sm:w-60">
            <?php if (!empty($colaboradorLogado) && empty($ehSupervisor)): ?>
                <div class="flex items-center justify-between bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span class="font-bold text-emerald-400"><?= Html::encode($colaboradorLogado->nome_completo) ?></span>
                    </div>
                    <span class="text-[10px] text-slate-500 font-mono">Rota Pessoal</span>
                </div>
                <input type="hidden" id="sel-cobrador-ativo" value="<?= Html::encode($colaboradorLogado->id) ?>">
            <?php else: ?>
                <select id="sel-cobrador-ativo" onchange="trocarCobrador(this.value)" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white outline-none focus:border-amber-500 font-medium">
                    <option value="">🛵 Toda a Carteira da Loja</option>
                    <?php foreach ($cobradores as $cob): ?>
                        <option value="<?= Html::encode($cob->id) ?>" <?= ((string)$cobradorId === (string)$cob->id) ? 'selected' : '' ?>>
                            <?= Html::encode($cob->nome_completo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>

        <div class="w-full sm:flex-1 flex gap-2">
            <input type="text" id="campo-busca-rota" oninput="filtrarCardsRota(this.value)" placeholder="🔍 Buscar cliente, rua ou bairro na rota..." class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white placeholder-slate-500 outline-none focus:border-amber-500">
            <button onclick="baixarRotaServidor(true)" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-amber-400 font-bold text-xs rounded-xl border border-slate-700 active:scale-95 transition whitespace-nowrap flex items-center gap-1" title="Sincronizar rota com o servidor">
                <span>📥</span> <span>Atualizar</span>
            </button>
            <button onclick="limparCacheRota()" class="px-2.5 py-1.5 bg-slate-800 hover:bg-rose-950/50 text-slate-400 hover:text-rose-300 font-bold text-xs rounded-xl border border-slate-700 active:scale-95 transition whitespace-nowrap flex items-center gap-1" title="Limpar Cache Local e Recarregar">
                <span>🗑️</span>
            </button>
        </div>
    </div>

    <!-- Navegação de Abas Inferior / Superior -->
    <nav class="bg-slate-900 border-b border-slate-800 px-3 flex gap-2 overflow-x-auto">
        <button onclick="trocarAbaCobrador('minha-rota')" id="tab-btn-minha-rota" class="tab-btn py-2.5 px-4 text-xs font-bold border-b-2 border-amber-500 text-amber-400 flex items-center gap-1.5 whitespace-nowrap">
            <span>🗺️</span> <span>Minha Rota (<span id="cont-clientes-rota">0</span>)</span>
        </button>
        <button onclick="trocarAbaCobrador('resumo-dia')" id="tab-btn-resumo-dia" class="tab-btn py-2.5 px-4 text-xs font-bold border-b-2 border-transparent text-slate-400 hover:text-slate-200 flex items-center gap-1.5 whitespace-nowrap">
            <span>📊</span> <span>Recebidos Hoje</span>
        </button>
        <button onclick="trocarAbaCobrador('ajuda-rotas')" id="tab-btn-ajuda-rotas" class="tab-btn py-2.5 px-4 text-xs font-bold border-b-2 border-transparent text-slate-400 hover:text-slate-200 flex items-center gap-1.5 whitespace-nowrap">
            <span>⚙️</span> <span>Ordem da Rota</span>
        </button>
    </nav>

    <!-- Conteúdo Principal -->
    <main class="flex-1 overflow-y-auto p-4 max-w-lg mx-auto w-full pb-20">

        <!-- ========================================================================= -->
        <!-- ABA 1: MINHA ROTA (LISTA DE CLIENTES / VISITAS) -->
        <!-- ========================================================================= -->
        <section id="aba-minha-rota" class="space-y-3">
            <div id="lista-clientes-rota" class="space-y-3">
                <div class="p-8 text-center text-slate-500 text-xs">Carregando rota...</div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- ABA 2: RESUMO DO DIA -->
        <!-- ========================================================================= -->
        <section id="aba-resumo-dia" class="space-y-4 hidden">
            <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl space-y-4">
                <h2 class="text-xs font-black text-white uppercase tracking-wider">Resumo de Cobranças de Hoje</h2>
                
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
                        <div class="text-slate-400 text-[10px] uppercase font-bold">Total Recebido</div>
                        <div id="resumo-total-recebido" class="text-lg font-black text-emerald-400 font-mono">R$ 0,00</div>
                    </div>
                    <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
                        <div class="text-slate-400 text-[10px] uppercase font-bold">Baixas Feitas</div>
                        <div id="resumo-qtd-baixas" class="text-lg font-black text-amber-400">0</div>
                    </div>
                </div>

                <div class="p-3 bg-slate-950 rounded-xl border border-slate-800 space-y-2 text-xs">
                    <div class="flex items-center justify-between text-slate-400">
                        <span>💵 Em Dinheiro:</span>
                        <span id="resumo-dinheiro" class="font-bold text-white font-mono">R$ 0,00</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-400">
                        <span>⚡ Em Pix:</span>
                        <span id="resumo-pix" class="font-bold text-white font-mono">R$ 0,00</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-400">
                        <span>💳 Cartão / Outros:</span>
                        <span id="resumo-outros" class="font-bold text-white font-mono">R$ 0,00</span>
                    </div>
                </div>

                <button onclick="sincronizarBaixas()" class="w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black text-xs rounded-xl shadow-lg transition active:scale-98 flex items-center justify-center gap-1.5">
                    <span>🔄</span> <span>Enviar Baixas para a Central</span>
                </button>
            </div>

            <!-- Lista de Baixas Recentes -->
            <div class="space-y-2">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Últimos Recebimentos Gravados</h3>
                <div id="lista-baixas-recentes" class="space-y-2"></div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- ABA 3: INSTRUÇÕES E ORGANIZAÇÃO DA ROTA -->
        <!-- ========================================================================= -->
        <section id="aba-ajuda-rotas" class="space-y-4 hidden">
            <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl space-y-3 text-xs text-slate-300">
                <h2 class="text-xs font-black text-white uppercase tracking-wider">Como Construir e Reordenar Sua Rota</h2>
                <p class="leading-relaxed">
                    Você pode alterar a ordem das visitas usando os botões <strong>⬆️ Subir</strong> e <strong>⬇️ Descer</strong> em cada cliente na lista de rotas.
                </p>
                <p class="leading-relaxed">
                    A sequência de visitação fica salva na memória do seu celular e você pode seguir a rua cliente a cliente sem precisar de sinal de internet!
                </p>

                <div class="pt-2 border-t border-slate-800 space-y-2">
                    <button onclick="ordenarPorBairro()" class="w-full py-2 bg-slate-800 hover:bg-slate-700 text-cyan-400 font-bold rounded-xl transition">
                        🏘️ Reorganizar Rota por Bairro & Rua
                    </button>
                    <button onclick="limparHistoricoBaixas()" class="w-full py-2 bg-slate-950 hover:bg-slate-900 text-slate-400 rounded-xl transition">
                        Limpar registros de baixas antigas já enviadas
                    </button>
                </div>
            </div>
        </section>

    </main>

    <!-- ========================================================================= -->
    <!-- MODAL: RECEBER PAGAMENTO DE PARCELA (OFFLINE READY) -->
    <!-- ========================================================================= -->
    <div id="modal-receber-pagamento" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-slate-900 border border-slate-800 w-full max-w-md rounded-t-3xl sm:rounded-3xl p-5 space-y-4 max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                    <h3 class="text-sm font-black text-white">Registrar Recebimento</h3>
                    <p class="text-[10px] text-amber-400 font-bold" id="modal-rec-cliente-nome"></p>
                </div>
                <button onclick="fecharModalRecebimento()" class="text-slate-400 hover:text-white p-1 text-lg">✕</button>
            </div>

            <div class="space-y-3">
                <!-- Seletor de Parcela -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                        Parcela a Baixar
                    </label>
                    <select id="modal-rec-sel-parcela" onchange="atualizarValorParcelaSelecionada()" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-500 font-medium">
                        <!-- Preenchido via JS -->
                    </select>
                </div>

                <!-- Valor Pago -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                        Valor Recebido (R$)
                    </label>
                    <input type="number" step="0.01" id="modal-rec-valor" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-amber-400 font-mono font-bold outline-none focus:border-amber-500">
                </div>

                <!-- Tipo de Pagamento -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                        Forma de Pagamento
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" onclick="setFormaPagamento('DINHEIRO')" id="btn-forma-DINHEIRO" class="forma-btn py-2 text-xs font-bold rounded-xl bg-amber-500 text-slate-950 border border-amber-500">
                            💵 Dinheiro
                        </button>
                        <button type="button" onclick="setFormaPagamento('PIX')" id="btn-forma-PIX" class="forma-btn py-2 text-xs font-bold rounded-xl bg-slate-950 text-slate-300 border border-slate-700">
                            ⚡ Pix
                        </button>
                        <button type="button" onclick="setFormaPagamento('CARTAO')" id="btn-forma-CARTAO" class="forma-btn py-2 text-xs font-bold rounded-xl bg-slate-950 text-slate-300 border border-slate-700">
                            💳 Cartão
                        </button>
                    </div>
                </div>

                <!-- Data do Pagamento -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                        Data do Recebimento
                    </label>
                    <input type="date" id="modal-rec-data" value="<?= date('Y-m-d') ?>" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-500">
                </div>
            </div>

            <button type="button" onclick="confirmarRecebimentoOffline()" class="w-full py-3.5 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-slate-950 font-black text-sm rounded-2xl shadow-xl transition active:scale-98 flex items-center justify-center gap-2">
                <span>✓</span>
                <span>Confirmar Recebimento & Atualizar Cartão</span>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: CARTÃO ATUALIZADO (1/4 A4, PDF, PNG, WHATSAPP, LINK ON-LINE) -->
    <!-- ========================================================================= -->
    <div id="modal-cartao-cobrador" class="fixed inset-0 z-50 bg-black/90 backdrop-blur-sm hidden flex flex-col items-center justify-start p-3 overflow-y-auto">
        
        <!-- Barra de Ações Superior -->
        <div class="w-full max-w-sm flex items-center justify-between mb-3 pt-2">
            <button onclick="fecharModalCartaoCobrador()" class="px-3 py-1.5 bg-slate-800 text-slate-300 text-xs font-bold rounded-xl active:scale-95 transition">
                ← Voltar à Rota
            </button>
            <div class="flex items-center gap-2">
                <button onclick="baixarCartaoCobradorImagem()" class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs rounded-xl shadow active:scale-95 transition flex items-center gap-1">
                    <span>🖼️</span> <span>PNG</span>
                </button>
                <button onclick="baixarCartaoCobradorPdf()" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs rounded-xl shadow active:scale-95 transition flex items-center gap-1">
                    <span>📄</span> <span>PDF</span>
                </button>
                <button onclick="compartilharWhatsAppCobrador()" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow active:scale-95 transition flex items-center gap-1">
                    <span>💬</span> <span>Zap</span>
                </button>
            </div>
        </div>

        <!-- Cartão Atualizado Renderizado 1/4 A4 -->
        <div id="area-cartao-cobrador" class="cartao-preview w-[105mm] min-h-[148mm] bg-white text-black p-3 rounded shadow-2xl border border-slate-300 flex flex-col justify-between select-text mb-6">
            <div>
                <div class="text-center font-bold text-[11px] pb-1 border-b border-black uppercase tracking-wider">
                    <div id="cob-card-empresa"><?= Html::encode($lojaNome) ?></div>
                    <div class="text-[8px] font-normal">CARTÃO DE COBRANÇA ATUALIZADO</div>
                </div>

                <div class="text-[9px] py-1 border-b border-black leading-tight space-y-0.5">
                    <div><strong>CLIENTE:</strong> <span id="cob-card-cli-nome"></span></div>
                    <div><strong>ENDEREÇO:</strong> <span id="cob-card-cli-endereco"></span></div>
                    <div><strong>CIDADE:</strong> <span id="cob-card-cli-cidade"></span> &nbsp; <strong>FONE:</strong> <span id="cob-card-cli-fone"></span></div>
                    <div><strong>PRODUTOS:</strong> <span id="cob-card-produtos"></span></div>
                    <div><strong>VALOR COMPRA:</strong> <span id="cob-card-vl-compra"></span> &nbsp; <strong>SALDO RESTANTE:</strong> <span id="cob-card-saldo-atual" class="font-bold"></span></div>
                    <div class="text-[8px] text-slate-700"><strong>CARTÃO Nº:</strong> <span id="cob-card-num"></span> &nbsp; <strong>VENDEDOR:</strong> <span id="cob-card-vendedor"></span></div>
                </div>

                <!-- Tabela de Parcelas Atualizadas -->
                <table class="w-full cartao-table border-collapse mt-1 text-center">
                    <thead>
                        <tr class="bg-gray-100 font-bold text-[7px]">
                            <th class="w-7">Nº</th>
                            <th>DATA PREST.</th>
                            <th>VL. PREST.</th>
                            <th>VL. COMPRA</th>
                            <th>DATA PAG.</th>
                            <th>VL. RECEB.</th>
                            <th>TIPO</th>
                            <th class="text-left pl-1">SALDO</th>
                        </tr>
                    </thead>
                    <tbody id="cob-card-tabela-parcelas">
                        <!-- Gerado dinamicamente -->
                    </tbody>
                </table>
            </div>

            <!-- Rodapé e Link On-line -->
            <div class="pt-2 border-t border-black text-[7px] text-center leading-tight">
                <div>Comprovante autêntico emitido pelo cobrador. Saldo atualizado em tempo real.</div>
                <div id="cob-card-public-link-box" class="font-mono text-[6.5px] mt-0.5 text-blue-700"></div>
            </div>
        </div>

    </div>

    <!-- Script de Bootstrap e Lógica Offline do Cobrador -->
    <script>
        const TENANT_ID = <?= json_encode($usuarioId) ?>;
        const LOJA_NOME = <?= json_encode($lojaNome) ?>;
        const INITIAL_COBRADOR_ID = <?= json_encode($cobradorId) ?>;

        let cobradorState = {
            tenantId: TENANT_ID,
            lojaNome: LOJA_NOME,
            cobradorId: INITIAL_COBRADOR_ID || '',
            cartoesRota: [],
            formaPagamentoAtiva: 'DINHEIRO',
            cartaoSelecionadoParaReceber: null,
            cartaoAtivoParaVisualizar: null
        };

        const CACHE_VERSION = 'v2_prestanista_clean';

        function verificarVersaoCache() {
            try {
                const ver = localStorage.getItem('pulse_rota_version');
                if (ver !== CACHE_VERSION) {
                    console.log('Versão de cache antiga detectada. Limpando rotas obsoletas...');
                    Object.keys(localStorage).forEach(k => {
                        if (k.startsWith('pulse_rota_')) {
                            localStorage.removeItem(k);
                        }
                    });
                    localStorage.setItem('pulse_rota_version', CACHE_VERSION);
                }
            } catch (e) {
                console.warn('Erro ao checar versão do cache', e);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            verificarVersaoCache();
            configurarRede();
            carregarRotaLocalStorage();
            // Se estiver online, sempre busca a rota oficial atualizada no servidor
            if (navigator.onLine) {
                baixarRotaServidor(false);
            } else {
                renderizarRota();
            }
            atualizarResumoDoDia();
            atualizarBadgesSync();
        });

        function configurarRede() {
            function atualizarStatusRede() {
                const badge = document.getElementById('badge-status-rede');
                const lbl = document.getElementById('lbl-status-rede');
                if (navigator.onLine) {
                    badge.className = 'flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30';
                    lbl.textContent = 'Online';
                } else {
                    badge.className = 'flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30';
                    lbl.textContent = 'Offline';
                }
            }
            window.addEventListener('online', atualizarStatusRede);
            window.addEventListener('offline', atualizarStatusRede);
            atualizarStatusRede();
        }

        function carregarRotaLocalStorage() {
            try {
                const chave = `pulse_rota_${cobradorState.cobradorId || 'geral'}`;
                const data = localStorage.getItem(chave);
                if (data) {
                    cobradorState.cartoesRota = JSON.parse(data);
                } else {
                    cobradorState.cartoesRota = [];
                }
            } catch (e) {
                console.warn('Erro ao ler rota do localStorage', e);
                cobradorState.cartoesRota = [];
            }
        }

        function salvarRotaLocalStorage() {
            const chave = `pulse_rota_${cobradorState.cobradorId || 'geral'}`;
            localStorage.setItem(chave, JSON.stringify(cobradorState.cartoesRota));
            document.getElementById('cont-clientes-rota').textContent = cobradorState.cartoesRota.length;
        }

        function limparCacheRota() {
            if (confirm('Deseja limpar o cache local e recarregar a rota do servidor?')) {
                const chave = `pulse_rota_${cobradorState.cobradorId || 'geral'}`;
                localStorage.removeItem(chave);
                cobradorState.cartoesRota = [];
                renderizarRota();
                baixarRotaServidor(true);
            }
        }

        async function baixarRotaServidor(comFeedback = true) {
            if (!navigator.onLine) {
                if (comFeedback) alert('Aparelho offline. Usando rota gravada na memória.');
                renderizarRota();
                return;
            }

            const params = new URLSearchParams();
            if (cobradorState.cobradorId) params.append('cobrador_id', cobradorState.cobradorId);
            if (cobradorState.tenantId) params.append('loja_id', cobradorState.tenantId);
            const queryStr = params.toString();
            const url = '<?= Url::to(['/prestanista/cobrador/dados-rota']) ?>' + (queryStr ? '?' + queryStr : '');

            try {
                const res = await fetch(url);
                const data = await res.json();
                if (data.success) {
                    cobradorState.cartoesRota = data.rotas || [];
                    salvarRotaLocalStorage();
                    renderizarRota();
                    if (comFeedback) {
                        if (cobradorState.cartoesRota.length > 0) {
                            alert(`✓ Rota atualizada com ${cobradorState.cartoesRota.length} cliente(s) a visitar!`);
                        } else {
                            alert('Nenhuma rota atribuída para este cobrador no momento.');
                        }
                    }
                }
            } catch (e) {
                if (comFeedback) alert('Não foi possível conectar ao servidor. Exibindo rota local.');
                renderizarRota();
            }
        }

        function trocarCobrador(novoId) {
            cobradorState.cobradorId = novoId;
            carregarRotaLocalStorage();
            if (navigator.onLine) {
                baixarRotaServidor(false);
            } else {
                renderizarRota();
            }
        }

        function trocarAbaCobrador(abaId) {
            ['minha-rota', 'resumo-dia', 'ajuda-rotas'].forEach(a => {
                const sec = document.getElementById('aba-' + a);
                const btn = document.getElementById('tab-btn-' + a);
                if (a === abaId) {
                    sec.classList.remove('hidden');
                    btn.className = 'tab-btn py-2.5 px-4 text-xs font-bold border-b-2 border-amber-500 text-amber-400 flex items-center gap-1.5 whitespace-nowrap';
                } else {
                    sec.classList.add('hidden');
                    btn.className = 'tab-btn py-2.5 px-4 text-xs font-bold border-b-2 border-transparent text-slate-400 hover:text-slate-200 flex items-center gap-1.5 whitespace-nowrap';
                }
            });
            if (abaId === 'resumo-dia') atualizarResumoDoDia();
        }

        // =========================================================================
        // RENDERIZAÇÃO E REORDENAÇÃO DA ROTA
        // =========================================================================
        function renderizarRota(filtro = '') {
            const container = document.getElementById('lista-clientes-rota');
            filtro = (filtro || '').toLowerCase().trim();

            const filtrados = cobradorState.cartoesRota.filter(c => {
                if (!filtro) return true;
                const cli = c.cliente || {};
                return (cli.nome && cli.nome.toLowerCase().includes(filtro)) ||
                       (cli.logradouro && cli.logradouro.toLowerCase().includes(filtro)) ||
                       (cli.bairro && cli.bairro.toLowerCase().includes(filtro)) ||
                       (cli.telefone && cli.telefone.includes(filtro));
            });

            document.getElementById('cont-clientes-rota').textContent = cobradorState.cartoesRota.length;

            if (filtrados.length === 0) {
                if (cobradorState.cartoesRota.length === 0) {
                    container.innerHTML = `
                        <div class="p-8 text-center text-slate-500 space-y-2">
                            <span class="text-4xl block">🛵</span>
                            <h4 class="font-black text-slate-300 text-sm">Nenhuma rota atribuída a este cobrador</h4>
                            <p class="text-xs text-slate-400 max-w-sm mx-auto">
                                Não existem compras a prestação atribuídas a este cobrador no momento. Novas rotas são atribuídas pelo painel de gestão em "Atribuir Cobrança".
                            </p>
                        </div>
                    `;
                } else {
                    container.innerHTML = '<div class="p-8 text-center text-slate-500 text-xs">Nenhum cliente na rota para os filtros informados.</div>';
                }
                return;
            }

            container.innerHTML = filtrados.map((item, index) => {
                const cli = item.cliente || {};
                const prox = item.proxima_parcela;
                const foneLimpo = (cli.telefone || '').replace(/\D/g, '');

                return `
                    <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl shadow space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-slate-800 text-amber-400 font-black text-[10px] flex items-center justify-center border border-slate-700">
                                        ${index + 1}
                                    </span>
                                    <h3 class="font-black text-white text-sm leading-tight">${cli.nome}</h3>
                                </div>
                                <div class="text-[11px] text-slate-300 mt-1 flex items-start gap-1">
                                    <span>📍</span>
                                    <span>${cli.logradouro || ''} ${cli.numero || ''} - <strong class="text-amber-400">${cli.bairro || 'Sem Bairro'}</strong></span>
                                </div>
                                ${cli.telefone ? `
                                    <div class="text-[10px] text-slate-400 mt-0.5 flex items-center gap-2">
                                        <span>📞 ${cli.telefone}</span>
                                        <a href="tel:${foneLimpo}" class="text-cyan-400 font-bold hover:underline">Ligar</a>
                                        <span>•</span>
                                        <a href="https://api.whatsapp.com/send?phone=55${foneLimpo}" target="_blank" class="text-emerald-400 font-bold hover:underline">Zap</a>
                                    </div>
                                ` : ''}
                            </div>

                            <!-- Botões de Reordenar Rota ⬆️ ⬇️ -->
                            <div class="flex flex-col gap-1">
                                <button onclick="moverItemRota(${index}, -1)" class="p-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded text-xs leading-none active:scale-90" title="Subir na rota">▲</button>
                                <button onclick="moverItemRota(${index}, 1)" class="p-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded text-xs leading-none active:scale-90" title="Descer na rota">▼</button>
                            </div>
                        </div>

                        <!-- Card de Parcela Pendente / Saldo -->
                        <div class="p-2.5 bg-slate-950 rounded-xl border border-slate-800/80 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-slate-400 text-[10px] uppercase font-bold block">
                                    ${prox ? `${prox.numero}ª Parcela (${prox.data_vencimento})` : 'Sem pendências'}
                                </span>
                                <span class="font-mono font-black text-amber-400 text-sm">
                                    ${prox ? `R$ ${prox.valor.toLocaleString('pt-BR', {minimumFractionDigits: 2})}` : 'QUITADO'}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-slate-400 text-[10px] uppercase font-bold block">Saldo Restante</span>
                                <span class="font-mono font-bold text-white text-xs">
                                    R$ ${item.saldo_devedor.toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                                </span>
                            </div>
                        </div>

                        <!-- Botões de Ação Imediata -->
                        <div class="flex items-center gap-2 pt-1">
                            <button onclick='abrirModalRecebimento("${item.venda_id}")' class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow transition active:scale-95 flex items-center justify-center gap-1">
                                <span>💵</span> <span>Receber</span>
                            </button>
                            <button onclick='abrirCartaoAtualizado("${item.venda_id}")' class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-amber-400 font-bold text-xs rounded-xl border border-slate-700 transition active:scale-95 flex items-center gap-1">
                                <span>📇</span> <span>Ver Cartão</span>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function filtrarCardsRota(val) {
            renderizarRota(val);
        }

        function moverItemRota(index, delta) {
            const novoIdx = index + delta;
            if (novoIdx < 0 || novoIdx >= cobradorState.cartoesRota.length) return;
            const temp = cobradorState.cartoesRota[index];
            cobradorState.cartoesRota[index] = cobradorState.cartoesRota[novoIdx];
            cobradorState.cartoesRota[novoIdx] = temp;
            salvarRotaLocalStorage();
            renderizarRota(document.getElementById('campo-busca-rota').value);
        }

        function ordenarPorBairro() {
            cobradorState.cartoesRota.sort((a, b) => {
                const bA = (a.cliente?.bairro || '').localeCompare(b.cliente?.bairro || '');
                if (bA !== 0) return bA;
                return (a.cliente?.logradouro || '').localeCompare(b.cliente?.logradouro || '');
            });
            salvarRotaLocalStorage();
            renderizarRota();
            alert('✓ Rota reordenada por Bairro e Rua com sucesso!');
        }

        // =========================================================================
        // RECEBIMENTO OFFLINE DE PARCELAS
        // =========================================================================
        function setFormaPagamento(forma) {
            cobradorState.formaPagamentoAtiva = forma;
            ['DINHEIRO', 'PIX', 'CARTAO'].forEach(f => {
                const btn = document.getElementById('btn-forma-' + f);
                if (f === forma) {
                    btn.className = 'forma-btn py-2 text-xs font-bold rounded-xl bg-amber-500 text-slate-950 border border-amber-500';
                } else {
                    btn.className = 'forma-btn py-2 text-xs font-bold rounded-xl bg-slate-950 text-slate-300 border border-slate-700';
                }
            });
        }

        function abrirModalRecebimento(vendaId) {
            const cartao = cobradorState.cartoesRota.find(c => c.venda_id === vendaId);
            if (!cartao) return;

            cobradorState.cartaoSelecionadoParaReceber = cartao;
            document.getElementById('modal-rec-cliente-nome').textContent = cartao.cliente.nome;

            // Preenche opções de parcelas pendentes
            const sel = document.getElementById('modal-rec-sel-parcela');
            const pendentes = cartao.parcelas.filter(p => p.status !== 'PAGA');

            if (pendentes.length === 0) {
                alert('Todas as parcelas deste cartão já foram pagas!');
                return;
            }

            sel.innerHTML = pendentes.map(p => `
                <option value="${p.id}" data-valor="${p.valor_parcela}">
                    ${p.numero}ª Parcela - R$ ${p.valor_parcela.toLocaleString('pt-BR', {minimumFractionDigits: 2})} (Venc: ${p.data_vencimento})
                </option>
            `).join('');

            atualizarValorParcelaSelecionada();
            setFormaPagamento('DINHEIRO');
            document.getElementById('modal-rec-data').value = new Date().toISOString().split('T')[0];
            document.getElementById('modal-receber-pagamento').classList.remove('hidden');
        }

        function atualizarValorParcelaSelecionada() {
            const sel = document.getElementById('modal-rec-sel-parcela');
            const opt = sel.options[sel.selectedIndex];
            if (opt) {
                const val = parseFloat(opt.getAttribute('data-valor') || '0');
                document.getElementById('modal-rec-valor').value = val.toFixed(2);
            }
        }

        function fecharModalRecebimento() {
            document.getElementById('modal-receber-pagamento').classList.add('hidden');
            cobradorState.cartaoSelecionadoParaReceber = null;
        }

        function confirmarRecebimentoOffline() {
            const cartao = cobradorState.cartaoSelecionadoParaReceber;
            if (!cartao) return;

            const sel = document.getElementById('modal-rec-sel-parcela');
            const parcelaId = sel.value;
            const valorPago = parseFloat(document.getElementById('modal-rec-valor').value || '0');
            const dataPagamento = document.getElementById('modal-rec-data').value;
            const forma = cobradorState.formaPagamentoAtiva;

            if (valorPago <= 0) {
                alert('Informe um valor válido de recebimento.');
                return;
            }

            const offlineId = 'pag_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);

            // 1. Atualiza estado local da parcela e cartão
            const parcela = cartao.parcelas.find(p => p.id === parcelaId);
            if (parcela) {
                parcela.status = 'PAGA';
                parcela.valor_pago = valorPago;
                parcela.data_pagamento = new Date(dataPagamento + 'T00:00:00').toLocaleDateString('pt-BR');
                parcela.tipo_pagamento = forma;
            }

            cartao.total_pago = (cartao.total_pago || 0) + valorPago;
            cartao.saldo_devedor = Math.max(0, cartao.valor_total - cartao.total_pago);

            // Atualiza próxima pendente
            const novaProx = cartao.parcelas.find(p => p.status !== 'PAGA');
            cartao.proxima_parcela = novaProx ? {
                id: novaProx.id,
                numero: novaProx.numero,
                data_vencimento: novaProx.data_vencimento,
                valor: novaProx.valor_parcela
            } : null;

            salvarRotaLocalStorage();

            // 2. Salva recebimento na fila de sincronização
            const pagamentosPendentes = obterPagamentosOffline();
            pagamentosPendentes.push({
                offline_id: offlineId,
                parcela_id: parcelaId,
                venda_id: cartao.venda_id,
                cliente_nome: cartao.cliente.nome,
                numero_parcela: parcela ? parcela.numero : 1,
                valor_pago: valorPago,
                data_pagamento: dataPagamento,
                tipo_pagamento: forma,
                cobrador_id: cobradorState.cobradorId,
                data_registro: new Date().toISOString()
            });
            localStorage.setItem('pulse_pagamentos_offline', JSON.stringify(pagamentosPendentes));

            fecharModalRecebimento();
            renderizarRota(document.getElementById('campo-busca-rota').value);
            atualizarResumoDoDia();
            atualizarBadgesSync();

            // Abre o cartão já atualizado para o cliente ver ou enviar no WhatsApp
            abrirCartaoAtualizado(cartao.venda_id);
        }

        function obterPagamentosOffline() {
            try {
                return JSON.parse(localStorage.getItem('pulse_pagamentos_offline') || '[]');
            } catch (e) {
                return [];
            }
        }

        // =========================================================================
        // VISUALIZAÇÃO E EXPORTAÇÃO DO CARTÃO ATUALIZADO
        // =========================================================================
        function abrirCartaoAtualizado(vendaId) {
            const cartao = cobradorState.cartoesRota.find(c => c.venda_id === vendaId);
            if (!cartao) return;

            cobradorState.cartaoAtivoParaVisualizar = cartao;
            const modal = document.getElementById('modal-cartao-cobrador');

            document.getElementById('cob-card-empresa').textContent = cobradorState.lojaNome;
            document.getElementById('cob-card-cli-nome').textContent = cartao.cliente.nome;
            document.getElementById('cob-card-cli-endereco').textContent = `${cartao.cliente.logradouro || ''} ${cartao.cliente.numero || ''} - ${cartao.cliente.bairro || ''}`;
            document.getElementById('cob-card-cli-cidade').textContent = cartao.cliente.cidade || '';
            document.getElementById('cob-card-cli-fone').textContent = cartao.cliente.telefone || '';
            document.getElementById('cob-card-produtos').textContent = cartao.produtos_descricao || '';
            document.getElementById('cob-card-vl-compra').textContent = `R$ ${cartao.valor_total.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
            document.getElementById('cob-card-saldo-atual').textContent = `R$ ${cartao.saldo_devedor.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
            document.getElementById('cob-card-num').textContent = cartao.numero_cartao;
            document.getElementById('cob-card-vendedor').textContent = cartao.vendedor_nome || '';

            // Tabela de parcelas com linha completa e saldo impresso apenas após baixa
            let saldoAcumulado = cartao.valor_total;
            const tbody = document.getElementById('cob-card-tabela-parcelas');
            tbody.innerHTML = cartao.parcelas.map(p => {
                const foiPaga = (p.status === 'PAGA');
                let saldoExibido = '';
                if (foiPaga) {
                    saldoAcumulado -= (p.valor_pago || p.valor_parcela);
                    saldoExibido = Math.max(0, saldoAcumulado).toLocaleString('pt-BR', {minimumFractionDigits: 2});
                }

                return `
                    <tr>
                        <td class="font-bold">${p.numero}</td>
                        <td>${p.data_vencimento}</td>
                        <td class="font-bold font-mono">${p.valor_parcela.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                        <td class="font-mono">${cartao.valor_total.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                        <td>${foiPaga ? (p.data_pagamento || '') : ''}</td>
                        <td class="font-mono">${foiPaga ? (p.valor_pago ? p.valor_pago.toLocaleString('pt-BR', {minimumFractionDigits: 2}) : '') : ''}</td>
                        <td>${foiPaga ? (p.tipo_pagamento || '') : ''}</td>
                        <td class="text-left font-mono pl-1 font-bold">${saldoExibido}</td>
                    </tr>
                `;
            }).join('');

            // Link On-line Oficial
            const boxLink = document.getElementById('cob-card-public-link-box');
            if (cartao.public_url) {
                boxLink.innerHTML = `Link On-line: <a href="${cartao.public_url}" target="_blank" class="underline">${cartao.public_url}</a>`;
            } else {
                boxLink.innerHTML = `Cartão atualizado em campo`;
            }

            modal.classList.remove('hidden');
        }

        function fecharModalCartaoCobrador() {
            document.getElementById('modal-cartao-cobrador').classList.add('hidden');
            cobradorState.cartaoAtivoParaVisualizar = null;
        }

        async function baixarCartaoCobradorImagem() {
            const elem = document.getElementById('area-cartao-cobrador');
            if (!elem) return;

            try {
                const canvas = await html2canvas(elem, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
                const link = document.createElement('a');
                const cliNome = (cobradorState.cartaoAtivoParaVisualizar?.cliente?.nome || 'cartao').replace(/[^a-zA-Z0-9]/g, '_');
                link.download = `cartao_atualizado_${cliNome}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
            } catch (e) {
                alert('Erro ao gerar imagem: ' + e.message);
            }
        }

        async function baixarCartaoCobradorPdf() {
            const elem = document.getElementById('area-cartao-cobrador');
            if (!elem) return;

            try {
                const canvas = await html2canvas(elem, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
                const imgData = canvas.toDataURL('image/png');

                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: [105, 148]
                });

                pdf.addImage(imgData, 'PNG', 0, 0, 105, 148);
                const cliNome = (cobradorState.cartaoAtivoParaVisualizar?.cliente?.nome || 'cartao').replace(/[^a-zA-Z0-9]/g, '_');
                pdf.save(`cartao_atualizado_${cliNome}.pdf`);
            } catch (e) {
                alert('Erro ao gerar PDF: ' + e.message);
            }
        }

        function compartilharWhatsAppCobrador() {
            const cartao = cobradorState.cartaoAtivoParaVisualizar;
            if (!cartao) return;

            const cli = cartao.cliente;
            const fone = (cli.telefone || '').replace(/\D/g, '');

            let msg = `*${cobradorState.lojaNome.toUpperCase()}*\n`;
            msg += `Olá ${cli.nome}, seu pagamento de crediário foi recebido e autenticado com sucesso pelo cobrador!\n\n`;
            msg += `🧾 *Cartão Nº:* #${cartao.numero_cartao}\n`;
            msg += `💰 *Saldo Devedor Atual:* R$ ${cartao.saldo_devedor.toLocaleString('pt-BR', {minimumFractionDigits: 2})}\n`;
            if (cartao.proxima_parcela) {
                msg += `🗓️ *Próximo Vencimento:* ${cartao.proxima_parcela.data_vencimento} (R$ ${cartao.proxima_parcela.valor.toLocaleString('pt-BR', {minimumFractionDigits: 2})})\n`;
            } else {
                msg += `🎉 *Parabéns! Seu crediário está totalmente quitado!*\n`;
            }

            if (cartao.public_url) {
                msg += `\n🔗 *Veja seu Cartão Atualizado On-line:* ${cartao.public_url}\n`;
            }

            const urlZap = fone 
                ? `https://api.whatsapp.com/send?phone=55${fone}&text=${encodeURIComponent(msg)}`
                : `https://api.whatsapp.com/send?text=${encodeURIComponent(msg)}`;

            window.open(urlZap, '_blank');
        }

        // =========================================================================
        // SINCRONIZAÇÃO DAS BAIXAS COM O SERVIDOR
        // =========================================================================
        async function sincronizarBaixas() {
            if (!navigator.onLine) {
                alert('Aparelho sem conexão à internet. As baixas continuam gravadas e seguras no celular.');
                return;
            }

            const pagamentos = obterPagamentosOffline();
            if (pagamentos.length === 0) {
                alert('Tudo em dia! Não há baixas pendentes de envio.');
                return;
            }

            const btn = document.getElementById('btn-sync-topo');
            btn.classList.add('animate-spin');

            try {
                const response = await fetch('<?= Url::to(['/prestanista/cobrador/sincronizar']) ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        usuario_id: cobradorState.tenantId,
                        pagamentos_offline: pagamentos
                    })
                });

                const data = await response.json();
                if (data.success) {
                    // Limpa fila de pendentes
                    localStorage.removeItem('pulse_pagamentos_offline');
                    atualizarResumoDoDia();
                    atualizarBadgesSync();
                    alert(`✓ Sucesso! ${data.total_sincronizados} baixa(s) enviadas e registradas na central.`);
                } else {
                    alert('Erro ao sincronizar: ' + (data.mensagem || 'Falha no servidor'));
                }
            } catch (e) {
                alert('Falha na comunicação com o servidor. Tente novamente mais tarde.');
            } finally {
                btn.classList.remove('animate-spin');
            }
        }

        function atualizarResumoDoDia() {
            const pagamentos = obterPagamentosOffline();
            let total = 0;
            let dinheiro = 0;
            let pix = 0;
            let outros = 0;

            pagamentos.forEach(p => {
                const v = p.valor_pago || 0;
                total += v;
                if (p.tipo_pagamento === 'DINHEIRO') dinheiro += v;
                else if (p.tipo_pagamento === 'PIX') pix += v;
                else outros += v;
            });

            document.getElementById('resumo-total-recebido').textContent = `R$ ${total.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
            document.getElementById('resumo-qtd-baixas').textContent = pagamentos.length;
            document.getElementById('resumo-dinheiro').textContent = `R$ ${dinheiro.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
            document.getElementById('resumo-pix').textContent = `R$ ${pix.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
            document.getElementById('resumo-outros').textContent = `R$ ${outros.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;

            const container = document.getElementById('lista-baixas-recentes');
            if (pagamentos.length === 0) {
                container.innerHTML = '<div class="p-4 text-center text-slate-500 text-xs">Nenhum pagamento registrado no aparelho.</div>';
                return;
            }

            container.innerHTML = pagamentos.slice().reverse().map(p => `
                <div class="bg-slate-900 border border-slate-800 p-3 rounded-xl flex items-center justify-between text-xs">
                    <div>
                        <div class="font-bold text-white">${p.cliente_nome}</div>
                        <div class="text-[10px] text-slate-400">${p.numero_parcela}ª Parcela • Forma: ${p.tipo_pagamento}</div>
                    </div>
                    <div class="font-mono font-bold text-emerald-400">
                        R$ ${p.valor_pago.toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                    </div>
                </div>
            `).join('');
        }

        function atualizarBadgesSync() {
            const pagamentos = obterPagamentosOffline();
            const badge = document.getElementById('badge-pendentes-sync');
            if (pagamentos.length > 0) {
                badge.textContent = pagamentos.length;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }

        function limparHistoricoBaixas() {
            if (!confirm('Deseja limpar as baixas da memória local do celular?')) return;
            localStorage.removeItem('pulse_pagamentos_offline');
            atualizarResumoDoDia();
            atualizarBadgesSync();
            alert('Memória limpa!');
        }
    </script>
</body>
</html>
