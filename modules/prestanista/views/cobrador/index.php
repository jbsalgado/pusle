<?php
/** @var yii\web\View $this */
/** @var string $lojaNome */
/** @var app\modules\vendas\models\Colaborador[] $cobradores */
/** @var string|null $cobradorId */
/** @var string|null $usuarioId */
/** @var app\modules\vendas\models\Colaborador|null $colaboradorLogado */
/** @var bool $ehSupervisor */
/** @var bool $mpConectado */
/** @var string $mpPublicKey */

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

    <!-- Mercado Pago SDK v2 Oficial para Checkout Transparente e Tokenização de Cartão -->
    <script src="https://sdk.mercadopago.com/js/v2"></script>

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
    <!-- MODAL: RECEBER PAGAMENTO DE PARCELA (OFFLINE READY & MERCADO PAGO INTEGRADO) -->
    <!-- ========================================================================= -->
    <div id="modal-receber-pagamento" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-slate-900 border border-slate-800 w-full max-w-md rounded-t-3xl sm:rounded-3xl p-5 space-y-4 max-h-[92vh] flex flex-col overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-black text-white">Registrar Recebimento</h3>
                        <span id="badge-modal-mp" class="hidden text-[9px] px-2 py-0.5 rounded-full font-bold bg-sky-500/10 text-sky-400 border border-sky-500/30 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span> MP Conectado
                        </span>
                    </div>
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
                    <input type="number" step="0.01" id="modal-rec-valor" oninput="aoMudarValorRecebido(this.value)" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-amber-400 font-mono font-bold outline-none focus:border-amber-500">
                </div>

                <!-- Forma de Pagamento -->
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

                <!-- ================= SEÇÃO: DINHEIRO / BAIXA MANUAL ================= -->
                <div id="secao-rec-dinheiro" class="space-y-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                            Data do Recebimento
                        </label>
                        <input type="date" id="modal-rec-data-dinheiro" value="<?= date('Y-m-d') ?>" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-500">
                    </div>

                    <button type="button" onclick="confirmarRecebimentoOffline('DINHEIRO')" class="w-full py-3.5 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-slate-950 font-black text-sm rounded-2xl shadow-xl transition active:scale-98 flex items-center justify-center gap-2">
                        <span>✓</span>
                        <span>Confirmar Recebimento em Dinheiro</span>
                    </button>
                </div>

                <!-- ================= SEÇÃO: PIX (MERCADO PAGO DINÂMICO OU MANUAL) ================= -->
                <div id="secao-rec-pix" class="hidden space-y-3">
                    <!-- Se MP Conectado: Modo Dinâmico com Polling -->
                    <div id="box-pix-mp-ativo" class="space-y-3">
                        <!-- Estado 1: Gerar QR Code -->
                        <div id="box-pix-mp-gerar" class="p-3 bg-slate-950 border border-slate-800 rounded-2xl text-center space-y-2">
                            <span class="text-3xl block">⚡</span>
                            <h4 class="text-xs font-bold text-white">Cobrança via Pix Dinâmico</h4>
                            <p class="text-[11px] text-slate-400">Gere um QR Code exclusivo com confirmação automática na sua conta Mercado Pago.</p>
                            <button type="button" onclick="gerarPixMp()" id="btn-gerar-pix-mp" class="w-full py-3 bg-sky-500 hover:bg-sky-400 text-slate-950 font-black text-xs rounded-xl shadow transition active:scale-98 flex items-center justify-center gap-2">
                                <span>⚡</span> <span>Gerar QR Code Pix Mercado Pago</span>
                            </button>
                        </div>

                        <!-- Estado 2: QR Code Exibido e Polling Ativo -->
                        <div id="box-pix-mp-exibicao" class="hidden space-y-3 p-3 bg-slate-950 border border-sky-500/30 rounded-2xl text-center">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] text-sky-400 font-bold uppercase tracking-wider">Pix Mercado Pago</span>
                                <span id="pix-mp-valor-badge" class="text-xs font-mono font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-lg border border-amber-500/30"></span>
                            </div>

                            <div class="bg-white p-2.5 rounded-2xl max-w-[200px] mx-auto shadow-xl flex items-center justify-center">
                                <img id="pix-mp-qrcode-img" src="" alt="QR Code Pix" class="w-full h-auto rounded-lg">
                            </div>

                            <!-- Botão Copia e Cola -->
                            <button type="button" onclick="copiarPixCopiaECola()" id="btn-copiar-pix" class="w-full py-2.5 bg-slate-800 hover:bg-slate-700 text-amber-400 font-bold text-xs rounded-xl border border-slate-700 transition active:scale-98 flex items-center justify-center gap-2">
                                <span>📋</span> <span id="lbl-copiar-pix">Copiar Código Pix (Copia e Cola)</span>
                            </button>

                            <!-- Indicador de Polling em tempo real -->
                            <div class="flex items-center justify-center gap-2 text-xs font-semibold text-emerald-400 bg-emerald-950/40 border border-emerald-500/30 py-2 px-3 rounded-xl">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                                <span>Aguardando pagamento do cliente...</span>
                            </div>
                            <p class="text-[10px] text-slate-400">Verificando aprovação automática no Mercado Pago a cada 3 segundos.</p>
                        </div>
                    </div>

                    <!-- Fallback / Baixa Manual de Pix -->
                    <div class="pt-2 border-t border-slate-800 space-y-2">
                        <details class="text-[11px] text-slate-400">
                            <summary class="cursor-pointer hover:text-amber-400 font-medium py-1">
                                ⚙️ Cliente pagou fora ou está sem sinal? Baixa Manual Pix
                            </summary>
                            <div class="pt-2 space-y-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                                        Data do Recebimento
                                    </label>
                                    <input type="date" id="modal-rec-data-pix" value="<?= date('Y-m-d') ?>" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-500">
                                </div>
                                <button type="button" onclick="confirmarRecebimentoOffline('PIX')" class="w-full py-2.5 bg-slate-800 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl border border-slate-700 transition">
                                    Registrar Baixa Manual Pix
                                </button>
                            </div>
                        </details>
                    </div>
                </div>

                <!-- ================= SEÇÃO: CARTÃO (MERCADO PAGO OU MAQUININHA) ================= -->
                <div id="secao-rec-cartao" class="hidden space-y-3">
                    <!-- Formulário Cartão Transparente MP -->
                    <div id="box-cartao-mp-ativo" class="space-y-2.5 p-3 bg-slate-950 border border-slate-800 rounded-2xl">
                        <!-- Toggle Crédito / Débito -->
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" onclick="setTipoCartaoMp('credit_card')" id="btn-tipo-credito" class="py-1.5 text-xs font-bold rounded-xl bg-amber-500 text-slate-950 border border-amber-500">
                                Crédito
                            </button>
                            <button type="button" onclick="setTipoCartaoMp('debit_card')" id="btn-tipo-debito" class="py-1.5 text-xs font-bold rounded-xl bg-slate-900 text-slate-300 border border-slate-700">
                                Débito
                            </button>
                        </div>

                        <!-- Número do Cartão com detector de bandeira -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 flex justify-between items-center">
                                <span>Número do Cartão</span>
                                <span id="mp-card-brand-badge" class="text-amber-400 font-black"></span>
                            </label>
                            <input type="tel" id="mp-card-number" placeholder="0000 0000 0000 0000" maxlength="19" oninput="aoDigitarNumeroCartao(this)" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white font-mono outline-none focus:border-amber-500">
                        </div>

                        <!-- Validade e CVV -->
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Validade</label>
                                <input type="tel" id="mp-card-expiry" placeholder="MM/AA" maxlength="5" oninput="formatarValidadeCartao(this)" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono outline-none focus:border-amber-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">CVV</label>
                                <input type="tel" id="mp-card-cvv" placeholder="123" maxlength="4" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono outline-none focus:border-amber-500">
                            </div>
                        </div>

                        <!-- Nome impresso e CPF -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Nome no Cartão</label>
                            <input type="text" id="mp-card-holder" placeholder="Nome completo como impresso" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white uppercase outline-none focus:border-amber-500">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">CPF do Titular</label>
                            <input type="tel" id="mp-card-cpf" placeholder="000.000.000-00" maxlength="14" oninput="formatarCpfInput(this)" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono outline-none focus:border-amber-500">
                        </div>

                        <!-- Caixa de Erro amigável -->
                        <div id="box-erro-cartao-mp" class="hidden p-2.5 bg-rose-950/40 border border-rose-500/50 rounded-xl text-rose-300 text-[11px] leading-relaxed"></div>

                        <!-- Botão Cobrar no Cartão -->
                        <button type="button" onclick="processarPagamentoCartaoMp()" id="btn-cobrar-cartao-mp" class="w-full py-3.5 bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-600 hover:to-blue-700 text-white font-black text-xs rounded-xl shadow-lg transition active:scale-98 flex items-center justify-center gap-2">
                            <span>💳</span> <span id="lbl-cobrar-cartao">Cobrar no Cartão via Mercado Pago</span>
                        </button>
                    </div>

                    <!-- Fallback / Maquininha Própria / Baixa Manual -->
                    <div class="pt-2 border-t border-slate-800 space-y-2">
                        <details class="text-[11px] text-slate-400">
                            <summary class="cursor-pointer hover:text-amber-400 font-medium py-1">
                                ⚙️ Cobrou em maquininha física do cobrador? Baixa Manual Cartão
                            </summary>
                            <div class="pt-2 space-y-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                                        Data do Recebimento
                                    </label>
                                    <input type="date" id="modal-rec-data-cartao" value="<?= date('Y-m-d') ?>" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-500">
                                </div>
                                <button type="button" onclick="confirmarRecebimentoOffline('CARTAO')" class="w-full py-2.5 bg-slate-800 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl border border-slate-700 transition">
                                    Registrar Baixa Manual Cartão
                                </button>
                            </div>
                        </details>
                    </div>
                </div>

            </div>
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
                <button onclick="compartilharWhatsAppCobrador()" id="btn-zap-cobrador" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow active:scale-95 transition flex items-center gap-1">
                    <span>💬</span> <span>Zap</span>
                </button>
            </div>
        </div>

        <!-- Toast de Aviso do Envio WhatsApp com Imagem (Cobrador) -->
        <div id="toast-zap-cobrador" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[100] max-w-sm w-[92%] bg-slate-900/95 border border-emerald-500/60 shadow-2xl rounded-2xl p-4 text-white text-xs space-y-2 backdrop-blur-md hidden transition-all duration-300">
            <div class="flex items-start gap-3">
                <span class="text-2xl">📋</span>
                <div class="flex-1">
                    <h4 class="font-bold text-emerald-400">Cartão Pronto para Envio!</h4>
                    <p id="msg-toast-zap-cobrador" class="text-slate-200 mt-1 leading-relaxed text-[11px]"></p>
                </div>
                <button onclick="document.getElementById('toast-zap-cobrador').classList.add('hidden')" class="text-slate-400 hover:text-white font-bold p-1 text-sm">✕</button>
            </div>
        </div>

        <!-- Cartão Atualizado Renderizado 1/4 A4 Conforme Imagem Oficial -->
        <div id="area-cartao-cobrador" class="cartao-preview w-[105mm] min-h-[148mm] bg-white text-black p-3 rounded shadow-2xl border border-slate-300 flex flex-col justify-between select-text mb-6">
            <div>
                <div class="text-center font-bold text-[11px] pb-1 border-b border-black uppercase tracking-wider">
                    <div id="cob-card-empresa"><?= Html::encode($lojaNome) ?></div>
                    <div class="text-[8px] font-normal">CONTROLE DE CREDIÁRIO / PRESTANISTA</div>
                </div>

                <div class="text-[9px] py-1 border-b border-black leading-tight space-y-0.5">
                    <div><strong>CLIENTE:</strong> <span id="cob-card-cli-nome"></span></div>
                    <div><strong>ENDEREÇO:</strong> <span id="cob-card-cli-endereco"></span></div>
                    <div><strong>CIDADE:</strong> <span id="cob-card-cli-cidade"></span> &nbsp; <strong>FONE:</strong> <span id="cob-card-cli-fone"></span></div>
                    <div><strong>PRODUTOS:</strong> <span id="cob-card-produtos"></span></div>
                    <div><strong>VALOR TOTAL:</strong> <span id="cob-card-vl-total"></span> &nbsp; <strong>ENTRADA:</strong> <span id="cob-card-vl-entrada">R$ 0,00</span> &nbsp; <strong>SALDO:</strong> <span id="cob-card-saldo-atual" class="font-bold"></span></div>
                    <div class="text-[8px] text-slate-700"><strong>EMISSÃO:</strong> <span id="cob-card-dt-emissao"></span> &nbsp; <strong>VENDEDOR:</strong> <span id="cob-card-vendedor"></span></div>
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
                <div>Conserve este cartão com os devidos pagamentos autenticados pelo cobrador.</div>
                <div id="cob-card-public-link-box" class="font-mono text-[6.5px] mt-0.5 text-blue-700"></div>
            </div>
        </div>

    </div>

    <!-- Script de Bootstrap e Lógica Offline do Cobrador -->
    <script>
        const TENANT_ID = <?= json_encode($usuarioId) ?>;
        const LOJA_NOME = <?= json_encode($lojaNome) ?>;
        const INITIAL_COBRADOR_ID = <?= json_encode($cobradorId) ?>;
        const INITIAL_MP_CONECTADO = <?= json_encode((bool)($mpConectado ?? false)) ?>;
        const INITIAL_MP_PUBLIC_KEY = <?= json_encode((string)($mpPublicKey ?? '')) ?>;

        let cobradorState = {
            tenantId: TENANT_ID,
            lojaNome: LOJA_NOME,
            cobradorId: INITIAL_COBRADOR_ID || '',
            mpConectado: INITIAL_MP_CONECTADO,
            mpPublicKey: INITIAL_MP_PUBLIC_KEY,
            cartoesRota: [],
            formaPagamentoAtiva: 'DINHEIRO',
            tipoCartaoMp: 'credit_card',
            cartaoSelecionadoParaReceber: null,
            cartaoAtivoParaVisualizar: null,
            pixPollingTimer: null,
            pixPaymentId: null,
            pixCopiaECola: '',
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
                    if (data.mp_conectado !== undefined) {
                        cobradorState.mpConectado = !!data.mp_conectado;
                        cobradorState.mpPublicKey = data.mp_public_key || '';
                        atualizarBadgeMpConectado();
                    }
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
        // RECEBIMENTO OFFLINE & MERCADO PAGO INTEGRADO (PIX DINÂMICO & CARTÃO)
        // =========================================================================
        function atualizarBadgeMpConectado() {
            const badge = document.getElementById('badge-modal-mp');
            if (!badge) return;
            if (cobradorState.mpConectado) {
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }

        function aoMudarValorRecebido(valStr) {
            const val = parseFloat(valStr || '0');
            const txt = `Cobrar R$ ${val.toLocaleString('pt-BR', {minimumFractionDigits: 2})} no Cartão`;
            const lblCartao = document.getElementById('lbl-cobrar-cartao');
            if (lblCartao) lblCartao.textContent = txt;

            const badgePix = document.getElementById('pix-mp-valor-badge');
            if (badgePix) badgePix.textContent = `R$ ${val.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
        }

        function setFormaPagamento(forma) {
            cobradorState.formaPagamentoAtiva = forma;

            // Se mudou de aba, para polling do PIX anterior
            pararPollingPixMp();

            ['DINHEIRO', 'PIX', 'CARTAO'].forEach(f => {
                const btn = document.getElementById('btn-forma-' + f);
                if (f === forma) {
                    btn.className = 'forma-btn py-2 text-xs font-bold rounded-xl bg-amber-500 text-slate-950 border border-amber-500';
                } else {
                    btn.className = 'forma-btn py-2 text-xs font-bold rounded-xl bg-slate-950 text-slate-300 border border-slate-700';
                }
            });

            const secDinheiro = document.getElementById('secao-rec-dinheiro');
            const secPix = document.getElementById('secao-rec-pix');
            const secCartao = document.getElementById('secao-rec-cartao');

            if (secDinheiro) secDinheiro.classList.add('hidden');
            if (secPix) secPix.classList.add('hidden');
            if (secCartao) secCartao.classList.add('hidden');

            if (forma === 'DINHEIRO') {
                if (secDinheiro) secDinheiro.classList.remove('hidden');
            } else if (forma === 'PIX') {
                if (secPix) secPix.classList.remove('hidden');
                // Reseta estado do QR Code PIX para 'gerar'
                const boxGerar = document.getElementById('box-pix-mp-gerar');
                const boxExib = document.getElementById('box-pix-mp-exibicao');
                if (boxGerar) boxGerar.classList.remove('hidden');
                if (boxExib) boxExib.classList.add('hidden');
            } else if (forma === 'CARTAO') {
                if (secCartao) secCartao.classList.remove('hidden');
                // Preenche dados padrão do cliente
                const cartao = cobradorState.cartaoSelecionadoParaReceber;
                if (cartao && cartao.cliente) {
                    const elHolder = document.getElementById('mp-card-holder');
                    if (elHolder && !elHolder.value) elHolder.value = (cartao.cliente.nome || '').toUpperCase();
                }
                const errBox = document.getElementById('box-erro-cartao-mp');
                if (errBox) errBox.classList.add('hidden');
                aoMudarValorRecebido(document.getElementById('modal-rec-valor').value);
            }
        }

        function abrirModalRecebimento(vendaId) {
            const cartao = cobradorState.cartoesRota.find(c => c.venda_id === vendaId);
            if (!cartao) return;

            cobradorState.cartaoSelecionadoParaReceber = cartao;
            document.getElementById('modal-rec-cliente-nome').textContent = cartao.cliente.nome;
            atualizarBadgeMpConectado();

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

            const hoje = new Date().toISOString().split('T')[0];
            const dtDin = document.getElementById('modal-rec-data-dinheiro');
            if (dtDin) dtDin.value = hoje;
            const dtPix = document.getElementById('modal-rec-data-pix');
            if (dtPix) dtPix.value = hoje;
            const dtCar = document.getElementById('modal-rec-data-cartao');
            if (dtCar) dtCar.value = hoje;

            // Limpa campos do cartão
            ['mp-card-number', 'mp-card-expiry', 'mp-card-cvv', 'mp-card-cpf'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            const brandBadge = document.getElementById('mp-card-brand-badge');
            if (brandBadge) brandBadge.textContent = '';
            const errBox = document.getElementById('box-erro-cartao-mp');
            if (errBox) errBox.classList.add('hidden');

            document.getElementById('modal-receber-pagamento').classList.remove('hidden');
        }

        function atualizarValorParcelaSelecionada() {
            const sel = document.getElementById('modal-rec-sel-parcela');
            const opt = sel.options[sel.selectedIndex];
            if (opt) {
                const val = parseFloat(opt.getAttribute('data-valor') || '0');
                const inp = document.getElementById('modal-rec-valor');
                if (inp) {
                    inp.value = val.toFixed(2);
                    aoMudarValorRecebido(inp.value);
                }
            }
        }

        function fecharModalRecebimento() {
            pararPollingPixMp();
            document.getElementById('modal-receber-pagamento').classList.add('hidden');
            cobradorState.cartaoSelecionadoParaReceber = null;
        }

        // =========================================================================
        // PIX DINÂMICO MERCADO PAGO
        // =========================================================================
        async function gerarPixMp() {
            const cartao = cobradorState.cartaoSelecionadoParaReceber;
            if (!cartao) return;

            const sel = document.getElementById('modal-rec-sel-parcela');
            const parcelaId = sel.value;
            const valorPago = parseFloat(document.getElementById('modal-rec-valor').value || '0');

            if (valorPago <= 0) {
                alert('Informe um valor válido para gerar o Pix.');
                return;
            }

            const btn = document.getElementById('btn-gerar-pix-mp');
            const originalHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span>⏳</span> <span>Gerando Pix na API Mercado Pago...</span>';
            }

            try {
                const res = await fetch('<?= Url::to(['/prestanista/cobrador/gerar-pix-parcela']) ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        parcela_id: parcelaId,
                        valor: valorPago,
                        cobrador_id: cobradorState.cobradorId,
                        usuario_id: cobradorState.tenantId
                    })
                });

                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.mensagem || 'Não foi possível gerar o Pix.');
                }

                // Exibe o QR Code e valor
                cobradorState.pixPaymentId = data.payment_id;
                cobradorState.pixCopiaECola = data.qr_code || '';

                const img = document.getElementById('pix-mp-qrcode-img');
                if (img) {
                    img.src = 'data:image/png;base64,' + data.qr_code_base64;
                }

                const badge = document.getElementById('pix-mp-valor-badge');
                if (badge) {
                    badge.textContent = `R$ ${valorPago.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
                }

                document.getElementById('box-pix-mp-gerar').classList.add('hidden');
                document.getElementById('box-pix-mp-exibicao').classList.remove('hidden');

                // Inicia polling automático a cada 3s
                iniciarPollingPixMp(data.payment_id, parcelaId);

            } catch (e) {
                alert('⚠️ Erro ao gerar Pix: ' + e.message);
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            }
        }

        function iniciarPollingPixMp(paymentId, parcelaId) {
            pararPollingPixMp();

            cobradorState.pixPollingTimer = setInterval(async () => {
                try {
                    const res = await fetch(`<?= Url::to(['/prestanista/cobrador/consultar-pix-parcela']) ?>?payment_id=${paymentId}&parcela_id=${parcelaId}&cobrador_id=${cobradorState.cobradorId}`);
                    const data = await res.json();

                    if (data.success && data.status === 'approved') {
                        pararPollingPixMp();
                        const valor = parseFloat(document.getElementById('modal-rec-valor').value || '0');
                        finalizarBaixaComSucesso(parcelaId, valor, 'PIX', cobradorState.cartaoSelecionadoParaReceber.venda_id, data.public_url);
                    }
                } catch (err) {
                    console.warn('[Pix Polling] Falha momentânea na consulta:', err);
                }
            }, 3000);
        }

        function pararPollingPixMp() {
            if (cobradorState.pixPollingTimer) {
                clearInterval(cobradorState.pixPollingTimer);
                cobradorState.pixPollingTimer = null;
            }
        }

        async function copiarPixCopiaECola() {
            if (!cobradorState.pixCopiaECola) return;

            try {
                await navigator.clipboard.writeText(cobradorState.pixCopiaECola);
                const lbl = document.getElementById('lbl-copiar-pix');
                if (lbl) {
                    const original = lbl.textContent;
                    lbl.textContent = '✓ Código Pix Copiado com Sucesso!';
                    setTimeout(() => { lbl.textContent = original; }, 3000);
                }
            } catch (e) {
                prompt('Copie o código Pix abaixo:', cobradorState.pixCopiaECola);
            }
        }

        // =========================================================================
        // CARTÃO TRANSPARENTE MERCADO PAGO SDK v2
        // =========================================================================
        function setTipoCartaoMp(tipo) {
            cobradorState.tipoCartaoMp = tipo;
            const btnCred = document.getElementById('btn-tipo-credito');
            const btnDeb = document.getElementById('btn-tipo-debito');

            if (tipo === 'credit_card') {
                if (btnCred) btnCred.className = 'py-1.5 text-xs font-bold rounded-xl bg-amber-500 text-slate-950 border border-amber-500';
                if (btnDeb) btnDeb.className = 'py-1.5 text-xs font-bold rounded-xl bg-slate-900 text-slate-300 border border-slate-700';
            } else {
                if (btnCred) btnCred.className = 'py-1.5 text-xs font-bold rounded-xl bg-slate-900 text-slate-300 border border-slate-700';
                if (btnDeb) btnDeb.className = 'py-1.5 text-xs font-bold rounded-xl bg-amber-500 text-slate-950 border border-amber-500';
            }
        }

        function detectarBandeiraCartao(numeroLimpo) {
            const badge = document.getElementById('mp-card-brand-badge');
            if (!badge) return;

            if (/^4/.test(numeroLimpo)) {
                badge.textContent = 'VISA';
            } else if (/^(5[1-5]|2[2-7])/.test(numeroLimpo)) {
                badge.textContent = 'MASTERCARD';
            } else if (/^(4011|4312|4389|4514|4576|5041|5066|5067|5090|6277|6362|6363|650|651|655)/.test(numeroLimpo)) {
                badge.textContent = 'ELO';
            } else if (/^3[47]/.test(numeroLimpo)) {
                badge.textContent = 'AMEX';
            } else if (/^(606282|3841)/.test(numeroLimpo)) {
                badge.textContent = 'HIPERCARD';
            } else {
                badge.textContent = '';
            }
        }

        function aoDigitarNumeroCartao(input) {
            let v = input.value.replace(/\D/g, '').substring(0, 16);
            v = v.replace(/(\d{4})(?=\d)/g, '$1 ');
            input.value = v;
            detectarBandeiraCartao(v.replace(/\D/g, ''));
        }

        function formatarValidadeCartao(input) {
            let v = input.value.replace(/\D/g, '').substring(0, 4);
            if (v.length >= 3) {
                v = v.substring(0, 2) + '/' + v.substring(2, 4);
            }
            input.value = v;
        }

        function formatarCpfInput(input) {
            let v = input.value.replace(/\D/g, '').substring(0, 11);
            if (v.length > 9) {
                v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
            } else if (v.length > 6) {
                v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
            } else if (v.length > 3) {
                v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
            }
            input.value = v;
        }

        async function processarPagamentoCartaoMp() {
            const cartao = cobradorState.cartaoSelecionadoParaReceber;
            if (!cartao) return;

            const errBox = document.getElementById('box-erro-cartao-mp');
            if (errBox) errBox.classList.add('hidden');

            if (!cobradorState.mpPublicKey) {
                alert('A loja não possui chave pública do Mercado Pago configurada.');
                return;
            }

            if (typeof window.MercadoPago === 'undefined') {
                alert('O SDK do Mercado Pago não pôde ser carregado. Verifique a conexão com a internet.');
                return;
            }

            const sel = document.getElementById('modal-rec-sel-parcela');
            const parcelaId = sel.value;
            const valorPago = parseFloat(document.getElementById('modal-rec-valor').value || '0');

            if (valorPago <= 0) {
                alert('Informe um valor válido.');
                return;
            }

            const rawNum = (document.getElementById('mp-card-number')?.value || '').replace(/\D/g, '');
            const rawExp = (document.getElementById('mp-card-expiry')?.value || '').split('/');
            const cvv = (document.getElementById('mp-card-cvv')?.value || '').trim();
            const holder = (document.getElementById('mp-card-holder')?.value || '').trim();
            const cpf = (document.getElementById('mp-card-cpf')?.value || '').replace(/\D/g, '');

            if (rawNum.length < 13) {
                if (errBox) { errBox.textContent = '⚠️ Informe um número de cartão válido.'; errBox.classList.remove('hidden'); }
                return;
            }
            if (rawExp.length !== 2 || rawExp[0].length !== 2 || rawExp[1].length !== 2) {
                if (errBox) { errBox.textContent = '⚠️ Informe a validade no formato MM/AA.'; errBox.classList.remove('hidden'); }
                return;
            }
            if (cvv.length < 3) {
                if (errBox) { errBox.textContent = '⚠️ Informe o código de segurança (CVV).'; errBox.classList.remove('hidden'); }
                return;
            }
            if (!holder) {
                if (errBox) { errBox.textContent = '⚠️ Informe o nome impresso no cartão.'; errBox.classList.remove('hidden'); }
                return;
            }
            if (cpf.length !== 11) {
                if (errBox) { errBox.textContent = '⚠️ Informe um CPF válido com 11 dígitos.'; errBox.classList.remove('hidden'); }
                return;
            }

            const btn = document.getElementById('btn-cobrar-cartao-mp');
            const originalHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span>⏳</span> <span>Tokenizando e Processando no MP...</span>';
            }

            try {
                const mp = new window.MercadoPago(cobradorState.mpPublicKey, { locale: 'pt-BR' });

                const expMonth = rawExp[0];
                const expYear = '20' + rawExp[1];

                // Identifica bandeira
                let paymentMethodId = 'visa';
                try {
                    const bin = rawNum.substring(0, 6);
                    const binResp = await mp.getPaymentMethods({ bin });
                    if (binResp && binResp.results && binResp.results.length > 0) {
                        paymentMethodId = binResp.results[0].id;
                    }
                } catch (_) {}

                // Tokeniza via SDK oficial
                const cardToken = await mp.createCardToken({
                    cardNumber: rawNum,
                    cardholderName: holder,
                    cardExpirationMonth: expMonth,
                    cardExpirationYear: expYear,
                    securityCode: cvv,
                    identification: {
                        type: 'CPF',
                        number: cpf
                    }
                });

                if (!cardToken || !cardToken.id) {
                    throw new Error('Falha ao gerar token de segurança do cartão. Verifique os dados digitados.');
                }

                // Envia para o backend processar cobrança direta
                const res = await fetch('<?= Url::to(['/prestanista/cobrador/pagar-cartao-parcela']) ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        parcela_id: parcelaId,
                        token: cardToken.id,
                        payment_method_id: paymentMethodId,
                        tipo_cartao: cobradorState.tipoCartaoMp,
                        cobrador_id: cobradorState.cobradorId,
                        amount: valorPago,
                        cardholder_name: holder,
                        identification_number: cpf
                    })
                });

                const data = await res.json();
                if (data.success && data.status === 'approved') {
                    const tipoNome = (cobradorState.tipoCartaoMp === 'debit_card') ? 'CARTAO_DEBITO' : 'CARTAO_CREDITO';
                    finalizarBaixaComSucesso(parcelaId, valorPago, tipoNome, cartao.venda_id, data.public_url);
                } else {
                    const msg = data.mensagem || 'Pagamento recusado pela operadora do cartão.';
                    if (errBox) {
                        errBox.textContent = '❌ ' + msg;
                        errBox.classList.remove('hidden');
                    } else {
                        alert('❌ ' + msg);
                    }
                }

            } catch (e) {
                if (errBox) {
                    errBox.textContent = '❌ ' + e.message;
                    errBox.classList.remove('hidden');
                } else {
                    alert('❌ ' + e.message);
                }
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            }
        }

        // =========================================================================
        // FINALIZAÇÃO DE BAIXA (ONLINE OU OFFLINE) E ATUALIZAÇÃO DO CARTÃO
        // =========================================================================
        function finalizarBaixaComSucesso(parcelaId, valorPago, formaNome, vendaId, publicUrl) {
            const cartao = cobradorState.cartoesRota.find(c => c.venda_id === vendaId);
            if (!cartao) return;

            const parcela = cartao.parcelas.find(p => p.id === parcelaId);
            if (parcela) {
                parcela.status = 'PAGA';
                parcela.valor_pago = valorPago;
                parcela.data_pagamento = new Date().toLocaleDateString('pt-BR');
                parcela.tipo_pagamento = formaNome;
            }

            cartao.total_pago = (cartao.total_pago || 0) + valorPago;
            cartao.saldo_devedor = Math.max(0, cartao.valor_total - cartao.total_pago);
            if (publicUrl) cartao.public_url = publicUrl;

            const novaProx = cartao.parcelas.find(p => p.status !== 'PAGA');
            cartao.proxima_parcela = novaProx ? {
                id: novaProx.id,
                numero: novaProx.numero,
                data_vencimento: novaProx.data_vencimento,
                valor: novaProx.valor_parcela
            } : null;

            salvarRotaLocalStorage();
            fecharModalRecebimento();
            renderizarRota(document.getElementById('campo-busca-rota').value);
            atualizarResumoDoDia();

            // Abre o cartão atualizado oficial em tela
            abrirCartaoAtualizado(cartao.venda_id);
        }

        function confirmarRecebimentoOffline(tipoManual = 'DINHEIRO') {
            const cartao = cobradorState.cartaoSelecionadoParaReceber;
            if (!cartao) return;

            const sel = document.getElementById('modal-rec-sel-parcela');
            const parcelaId = sel.value;
            const valorPago = parseFloat(document.getElementById('modal-rec-valor').value || '0');
            
            let dataPagamento = new Date().toISOString().split('T')[0];
            if (tipoManual === 'DINHEIRO') {
                dataPagamento = document.getElementById('modal-rec-data-dinheiro')?.value || dataPagamento;
            } else if (tipoManual === 'PIX') {
                dataPagamento = document.getElementById('modal-rec-data-pix')?.value || dataPagamento;
            } else if (tipoManual === 'CARTAO') {
                dataPagamento = document.getElementById('modal-rec-data-cartao')?.value || dataPagamento;
            }

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
                parcela.tipo_pagamento = tipoManual;
            }

            cartao.total_pago = (cartao.total_pago || 0) + valorPago;
            cartao.saldo_devedor = Math.max(0, cartao.valor_total - cartao.total_pago);

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
                tipo_pagamento: tipoManual,
                cobrador_id: cobradorState.cobradorId,
                data_registro: new Date().toISOString()
            });
            localStorage.setItem('pulse_pagamentos_offline', JSON.stringify(pagamentosPendentes));

            fecharModalRecebimento();
            renderizarRota(document.getElementById('campo-busca-rota').value);
            atualizarResumoDoDia();
            atualizarBadgesSync();

            // Abre o cartão atualizado
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
        // VISUALIZAÇÃO E EXPORTAÇÃO DO CARTÃO ATUALIZADO (CONFORME IMAGEM OFICIAL)
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
            
            const elVlTotal = document.getElementById('cob-card-vl-total') || document.getElementById('cob-card-vl-compra');
            if (elVlTotal) elVlTotal.textContent = `R$ ${cartao.valor_total.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
            
            const elVlEntrada = document.getElementById('cob-card-vl-entrada');
            if (elVlEntrada) elVlEntrada.textContent = `R$ 0,00`;

            document.getElementById('cob-card-saldo-atual').textContent = `R$ ${cartao.saldo_devedor.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
            
            const elDtEmissao = document.getElementById('cob-card-dt-emissao');
            if (elDtEmissao) elDtEmissao.textContent = cartao.data_venda || '';

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

        function mostrarAvisoZapCobrador(texto) {
            const toast = document.getElementById('toast-zap-cobrador');
            const msgElem = document.getElementById('msg-toast-zap-cobrador');
            if (!toast || !msgElem) return;

            msgElem.innerHTML = texto;
            toast.classList.remove('hidden');

            clearTimeout(window._toastZapCobradorTimer);
            window._toastZapCobradorTimer = setTimeout(() => {
                toast.classList.add('hidden');
            }, 12000);
        }

        async function compartilharWhatsAppCobrador() {
            const cartao = cobradorState.cartaoAtivoParaVisualizar;
            if (!cartao) return;

            const elem = document.getElementById('area-cartao-cobrador');
            if (!elem) return;

            const btnZap = document.getElementById('btn-zap-cobrador');
            const originalHtml = btnZap ? btnZap.innerHTML : '';
            if (btnZap) {
                btnZap.innerHTML = '<span>⏳</span> <span>Gerando...</span>';
                btnZap.disabled = true;
            }

            try {
                // 1. Renderiza o cartão de cobrança atualizado com alta resolução (scale 2)
                const canvas = await html2canvas(elem, { 
                    scale: 2, 
                    useCORS: true, 
                    backgroundColor: '#ffffff' 
                });

                const cli = cartao.cliente || {};
                const fone = (cli.telefone || '').replace(/\D/g, '');
                const cliNome = (cli.nome || 'cliente').replace(/[^a-zA-Z0-9]/g, '_');

                let msg = `*${cobradorState.lojaNome.toUpperCase()}*\n`;
                msg += `Olá ${cli.nome || 'Cliente'}, seu pagamento de crediário foi recebido e autenticado com sucesso pelo cobrador!\n\n`;
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

                // 2. Converte o canvas para Blob PNG
                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
                const file = new File([blob], `cartao_atualizado_${cliNome}.png`, { type: 'image/png' });

                // 3. FLUXO MOBILE NATIVO (Web Share API com Arquivo de Imagem)
                // Abre o WhatsApp no celular com a IMAGEM REAL DO CARTÃO ATUALIZADO anexada no chat!
                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    try {
                        await navigator.share({
                            files: [file],
                            text: msg,
                            title: 'Cartão de Crediário Atualizado'
                        });
                        return; // Enviado via folha nativa
                    } catch (shareErr) {
                        if (shareErr.name === 'AbortError') return; // Cancelado pelo usuário
                        console.warn('Falha no Web Share nativo, aplicando fallback:', shareErr);
                    }
                }

                // 4. FLUXO DESKTOP / COMPUTADOR (Clipboard API + Download + WhatsApp Web)
                let copiadoClipboard = false;
                try {
                    if (navigator.clipboard && window.ClipboardItem) {
                        await navigator.clipboard.write([
                            new ClipboardItem({ 'image/png': blob })
                        ]);
                        copiadoClipboard = true;
                    }
                } catch (clipErr) {
                    console.warn('Clipboard write fallback:', clipErr);
                }

                // Dispara download do arquivo PNG
                const linkDownload = document.createElement('a');
                linkDownload.download = `cartao_atualizado_${cliNome}.png`;
                linkDownload.href = canvas.toDataURL('image/png');
                linkDownload.click();

                // Abre a conversa no WhatsApp Web / Desktop
                const urlZap = fone 
                    ? `https://api.whatsapp.com/send?phone=55${fone}&text=${encodeURIComponent(msg)}`
                    : `https://api.whatsapp.com/send?text=${encodeURIComponent(msg)}`;

                window.open(urlZap, '_blank');

                if (copiadoClipboard) {
                    mostrarAvisoZapCobrador('✓ <b>A imagem do cartão foi copiada para a área de transferência!</b><br>Na janela aberta do WhatsApp, pressione <b>Ctrl + V</b> para colar a foto do cartão e enviar com o texto.');
                } else {
                    mostrarAvisoZapCobrador('✓ <b>Imagem do cartão baixada com sucesso!</b><br>Na janela aberta do WhatsApp, anexe o arquivo da imagem para enviar ao cliente.');
                }
            } catch (e) {
                console.error('Erro ao compartilhar WhatsApp:', e);
                alert('Erro ao preparar envio da imagem do cartão: ' + e.message);
            } finally {
                if (btnZap) {
                    btnZap.innerHTML = originalHtml;
                    btnZap.disabled = false;
                }
            }
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
