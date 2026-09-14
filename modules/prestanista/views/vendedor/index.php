<?php
/** @var yii\web\View $this */
/** @var string $lojaNome */
/** @var app\modules\vendas\models\Colaborador[] $vendedores */
/** @var app\modules\vendas\models\Produto[] $produtos */
/** @var app\modules\vendas\models\Cliente[] $clientes */
/** @var string|null $usuarioId */
/** @var app\modules\vendas\models\Colaborador|null $colaboradorLogado */
/** @var bool $ehSupervisor */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'App do Vendedor Ambulante | Pulse Prestanista';
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

    <!-- Client-side PDF e Imagem para funcionar 100% offline -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        /* Tipografia de Cartão 1/4 A4 */
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
            <span class="text-2xl">🛒</span>
            <div>
                <h1 class="text-sm font-black text-white leading-tight">Vendedor de Rua</h1>
                <p class="text-[10px] text-amber-400 font-bold leading-tight" id="lbl-loja-nome"><?= Html::encode($lojaNome) ?></p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Indicador Online / Offline -->
            <div id="badge-status-rede" class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span id="lbl-status-rede">Online</span>
            </div>

            <!-- Botão Sincronizar -->
            <button onclick="sincronizarVendas()" id="btn-sync-topo" class="relative p-2 bg-slate-800 hover:bg-slate-700 text-amber-400 rounded-xl border border-slate-700 active:scale-95 transition" title="Sincronizar Vendas">
                <span class="text-base">🔄</span>
                <span id="badge-pendentes-count" class="hidden absolute -top-1 -right-1 bg-rose-500 text-white font-black text-[9px] w-4 h-4 rounded-full flex items-center justify-center">0</span>
            </button>

            <?php if (!Yii::$app->user->isGuest): ?>
            <a href="<?= Url::to(['/auth/logout']) ?>" data-method="post" class="p-2 bg-slate-800 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 rounded-xl border border-slate-700 active:scale-95 transition" title="Sair do Sistema">
                <span class="text-base">🚪</span>
            </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Navegação por Abas -->
    <nav class="bg-slate-900/90 border-b border-slate-800 px-3 flex gap-2 overflow-x-auto">
        <button onclick="trocarAba('nova-venda')" id="tab-btn-nova-venda" class="tab-btn py-2.5 px-4 text-xs font-bold border-b-2 border-amber-500 text-amber-400 flex items-center gap-1.5 whitespace-nowrap">
            <span>➕</span> <span>Nova Venda</span>
        </button>
        <button onclick="trocarAba('vendas-salvas')" id="tab-btn-vendas-salvas" class="tab-btn py-2.5 px-4 text-xs font-bold border-b-2 border-transparent text-slate-400 hover:text-slate-200 flex items-center gap-1.5 whitespace-nowrap">
            <span>📋</span> <span>Minhas Vendas (<span id="cont-vendas-locais">0</span>)</span>
        </button>
        <button onclick="trocarAba('config-dados')" id="tab-btn-config-dados" class="tab-btn py-2.5 px-4 text-xs font-bold border-b-2 border-transparent text-slate-400 hover:text-slate-200 flex items-center gap-1.5 whitespace-nowrap">
            <span>⚙️</span> <span>Catálogo Offline</span>
        </button>
    </nav>

    <!-- Conteúdo Principal com Scroll -->
    <main class="flex-1 overflow-y-auto p-4 max-w-lg mx-auto w-full pb-20">

        <!-- ========================================================================= -->
        <!-- ABA 1: NOVA VENDA -->
        <!-- ========================================================================= -->
        <section id="aba-nova-venda" class="space-y-4">
            
            <!-- Vendedor Responsável -->
            <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    Vendedor
                </label>
                <?php if (!empty($colaboradorLogado) && empty($ehSupervisor)): ?>
                    <div class="flex items-center justify-between bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                            <span class="font-bold text-amber-400"><?= Html::encode($colaboradorLogado->nome_completo) ?></span>
                        </div>
                        <span class="text-[10px] text-slate-500 font-mono">ID #<?= Html::encode($colaboradorLogado->id) ?></span>
                    </div>
                    <input type="hidden" id="sel-vendedor" value="<?= Html::encode($colaboradorLogado->id) ?>" data-nome="<?= Html::encode($colaboradorLogado->nome_completo) ?>">
                <?php else: ?>
                    <select id="sel-vendedor" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-500 font-medium">
                        <?php foreach ($vendedores as $v): ?>
                            <option value="<?= Html::encode($v->id) ?>"><?= Html::encode($v->nome_completo) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <!-- Identificação do Cliente -->
            <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl space-y-3">
                <div class="flex items-center justify-between">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        Cliente
                    </label>
                    <button type="button" onclick="alternarTipoCliente()" id="btn-tipo-cliente" class="text-[11px] font-bold text-amber-400 hover:text-amber-300">
                        ➕ Novo Cadastro
                    </button>
                </div>

                <!-- Busca de Cliente Existente -->
                <div id="box-cliente-existente" class="space-y-2">
                    <input type="text" id="busca-cliente" oninput="filtrarClientesOffline(this.value)" placeholder="🔍 Digite o nome ou telefone do cliente..." class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 outline-none focus:border-amber-500">
                    <div id="lista-clientes-sugestoes" class="max-h-40 overflow-y-auto bg-slate-950 rounded-xl border border-slate-800 divide-y divide-slate-850 hidden"></div>
                    <div id="cliente-selecionado-card" class="p-2.5 bg-slate-950 border border-amber-500/40 rounded-xl hidden">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-bold text-white text-xs" id="cliente-sel-nome"></span>
                                <p class="text-[10px] text-slate-400" id="cliente-sel-end"></p>
                            </div>
                            <button onclick="removerClienteSelecionado()" class="text-rose-400 text-xs font-bold p-1">✕</button>
                        </div>
                    </div>
                </div>

                <!-- Cadastro Rápido de Novo Cliente -->
                <div id="box-cliente-novo" class="space-y-2.5 hidden">
                    <input type="text" id="novo-cliente-nome" placeholder="Nome Completo do Cliente *" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 outline-none focus:border-amber-500">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="tel" id="novo-cliente-telefone" placeholder="WhatsApp / Telefone *" class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 outline-none focus:border-amber-500">
                        <input type="text" id="novo-cliente-cpf" placeholder="CPF (Opcional)" class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 outline-none focus:border-amber-500">
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <input type="text" id="novo-cliente-rua" placeholder="Rua / Logradouro" class="col-span-2 bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 outline-none focus:border-amber-500">
                        <input type="text" id="novo-cliente-numero" placeholder="Nº" class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 outline-none focus:border-amber-500">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" id="novo-cliente-bairro" placeholder="Bairro *" class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 outline-none focus:border-amber-500">
                        <input type="text" id="novo-cliente-cidade" placeholder="Cidade *" value="Franca" class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 outline-none focus:border-amber-500">
                    </div>
                </div>
            </div>

            <!-- Itens / Produtos da Venda -->
            <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl space-y-3">
                <div class="flex items-center justify-between">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        Produtos da Venda
                    </label>
                    <button type="button" onclick="mostrarModalAdicionarItem()" class="text-[11px] font-bold text-amber-400 hover:text-amber-300">
                        ➕ Adicionar Produto
                    </button>
                </div>

                <!-- Lista de itens adicionados -->
                <div id="carrinho-itens" class="space-y-2">
                    <div id="carrinho-vazio" class="p-4 text-center text-slate-500 text-xs border border-dashed border-slate-800 rounded-xl">
                        Nenhum produto adicionado ainda.
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-bold">Total dos Produtos:</span>
                    <span id="lbl-total-venda" class="text-base font-black text-amber-400 font-mono">R$ 0,00</span>
                </div>
            </div>

            <!-- Condições de Pagamento e Parcelamento -->
            <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl space-y-3">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                    Condições do Crediário
                </label>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] text-slate-400 font-bold mb-1">Data da Venda</label>
                        <input type="date" id="campo-data-venda" value="<?= date('Y-m-d') ?>" onchange="recalcularPlano()" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-400 font-bold mb-1">Data da 1ª Parcela</label>
                        <input type="date" id="campo-data-primeira" onchange="recalcularPlano()" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] text-slate-400 font-bold mb-1">Frequência</label>
                        <select id="campo-frequencia" onchange="atualizarDataPrimeiraPorFrequencia(); recalcularPlano();" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-500">
                            <option value="7">Semanal (7 dias)</option>
                            <option value="15">Quinzenal (15 dias)</option>
                            <option value="30">Mensal (30 dias)</option>
                            <option value="1">Diária (1 dia)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-400 font-bold mb-1">Nº de Parcelas</label>
                        <input type="number" id="campo-numero-parcelas" min="1" max="100" value="10" oninput="recalcularPlano()" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-500 font-bold">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] text-slate-400 font-bold mb-1">Valor de Entrada (R$)</label>
                    <input type="number" step="0.01" id="campo-valor-entrada" value="0.00" oninput="recalcularPlano()" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-500 font-bold">
                </div>

                <!-- Resumo Instantâneo das Parcelas -->
                <div class="p-3 bg-slate-950 rounded-xl border border-slate-800/80 space-y-1 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Saldo a Parcelar:</span>
                        <span id="lbl-saldo-financiar" class="font-bold text-white font-mono">R$ 0,00</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Valor de Cada Parcela:</span>
                        <span id="lbl-valor-parcela" class="font-black text-amber-400 font-mono text-sm">R$ 0,00</span>
                    </div>
                </div>
            </div>

            <!-- Botão de Emissão de Cartão -->
            <button type="button" onclick="emitirCartaoVenda()" class="w-full py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black text-sm rounded-2xl shadow-xl transition active:scale-98 flex items-center justify-center gap-2">
                <span>📇</span>
                <span>Gerar e Emitir Cartão Agora</span>
            </button>
        </section>

        <!-- ========================================================================= -->
        <!-- ABA 2: MINHAS VENDAS (LOCAL + SINCRONIZADAS) -->
        <!-- ========================================================================= -->
        <section id="aba-vendas-salvas" class="space-y-4 hidden">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-black text-slate-300 uppercase tracking-wider">Histórico de Vendas Gravadas</h2>
                <button onclick="sincronizarVendas()" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow transition flex items-center gap-1 active:scale-95">
                    <span>🔄</span> <span>Sincronizar Todas</span>
                </button>
            </div>

            <div id="lista-vendas-gravadas" class="space-y-3">
                <!-- Preenchido via JS -->
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- ABA 3: CONFIG / CATÁLOGO OFFLINE -->
        <!-- ========================================================================= -->
        <section id="aba-config-dados" class="space-y-4 hidden">
            <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl space-y-3">
                <h2 class="text-xs font-black text-white uppercase tracking-wider">Base Offline do Vendedor</h2>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Você pode atualizar seus produtos e clientes para trabalhar na rua mesmo sem conexão com a internet.
                </p>
                
                <div class="grid grid-cols-2 gap-2 text-center text-xs">
                    <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
                        <div class="text-slate-400 text-[10px] uppercase font-bold">Produtos Salvos</div>
                        <div id="stat-produtos-count" class="text-lg font-black text-amber-400">0</div>
                    </div>
                    <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
                        <div class="text-slate-400 text-[10px] uppercase font-bold">Clientes Salvos</div>
                        <div id="stat-clientes-count" class="text-lg font-black text-cyan-400">0</div>
                    </div>
                </div>

                <button onclick="baixarCatalogoCompleto()" class="w-full py-2.5 bg-slate-800 hover:bg-slate-700 text-amber-400 border border-slate-700 font-bold text-xs rounded-xl transition flex items-center justify-center gap-2">
                    <span>📥</span> <span>Atualizar Catálogo da Central</span>
                </button>
                <button onclick="limparVendasSincronizadas()" class="w-full py-2 bg-slate-950 hover:bg-slate-900 text-slate-400 text-[11px] rounded-xl transition">
                    Limpar vendas já sincronizadas da memória
                </button>
            </div>
        </section>

    </main>

    <!-- ========================================================================= -->
    <!-- MODAL: ADICIONAR PRODUTO -->
    <!-- ========================================================================= -->
    <div id="modal-adicionar-produto" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-slate-900 border border-slate-800 w-full max-w-md rounded-t-3xl sm:rounded-3xl p-5 space-y-4 max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-sm font-black text-white">Adicionar Produto</h3>
                <button onclick="fecharModalProduto()" class="text-slate-400 hover:text-white p-1 text-lg">✕</button>
            </div>

            <!-- Busca Rápida de Produto -->
            <input type="text" id="modal-busca-prod" oninput="filtrarProdutosModal(this.value)" placeholder="🔍 Buscar produto no estoque..." class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 outline-none focus:border-amber-500">

            <!-- Lista de Produtos Filtrados -->
            <div id="modal-lista-produtos" class="flex-1 overflow-y-auto max-h-48 space-y-1.5 divide-y divide-slate-850"></div>

            <!-- Ou item personalizado -->
            <div class="pt-3 border-t border-slate-800 space-y-2">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Ou Produto Avulso:</span>
                <input type="text" id="modal-item-avulso-nome" placeholder="Nome do produto avulso" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-500">
                <div class="grid grid-cols-2 gap-2">
                    <input type="number" step="0.01" id="modal-item-avulso-preco" placeholder="Preço (R$)" class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-500">
                    <input type="number" min="1" id="modal-item-avulso-qtd" value="1" placeholder="Qtd" class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-500">
                </div>
                <button onclick="adicionarItemAvulso()" class="w-full py-2 bg-slate-800 hover:bg-slate-700 text-amber-400 font-bold text-xs rounded-xl transition">
                    + Inserir Produto Avulso
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: VISUALIZAÇÃO E EXPORTAÇÃO DO CARTÃO 1/4 A4 -->
    <!-- ========================================================================= -->
    <div id="modal-cartao-gerado" class="fixed inset-0 z-50 bg-black/90 backdrop-blur-sm hidden flex flex-col items-center justify-start p-3 overflow-y-auto">
        
        <!-- Barra Superior de Ações do Cartão -->
        <div class="w-full max-w-sm flex items-center justify-between mb-3 pt-2">
            <button onclick="fecharModalCartao()" class="px-3 py-1.5 bg-slate-800 text-slate-300 text-xs font-bold rounded-xl active:scale-95 transition">
                ← Voltar
            </button>
            <div class="flex items-center gap-2">
                <button onclick="baixarCartaoImagem()" class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs rounded-xl shadow active:scale-95 transition flex items-center gap-1">
                    <span>🖼️</span> <span>PNG</span>
                </button>
                <button onclick="baixarCartaoPdf()" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs rounded-xl shadow active:scale-95 transition flex items-center gap-1">
                    <span>📄</span> <span>PDF</span>
                </button>
                <button onclick="compartilharWhatsAppCartao()" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow active:scale-95 transition flex items-center gap-1">
                    <span>💬</span> <span>Zap</span>
                </button>
            </div>
        </div>

        <!-- O Cartão 1/4 A4 Oficial Renderizado -->
        <div id="area-cartao-render" class="cartao-preview w-[105mm] min-h-[148mm] bg-white text-black p-3 rounded shadow-2xl border border-slate-300 flex flex-col justify-between select-text mb-6">
            <!-- Topo / Cabeçalho -->
            <div>
                <div class="text-center font-bold text-[11px] pb-1 border-b border-black uppercase tracking-wider">
                    <div id="card-empresa-nome"><?= Html::encode($lojaNome) ?></div>
                    <div class="text-[8px] font-normal">CONTROLE DE CREDIÁRIO / PRESTANISTA</div>
                </div>

                <!-- Dados do Cliente e Venda -->
                <div class="text-[9px] py-1 border-b border-black leading-tight space-y-0.5">
                    <div><strong>CLIENTE:</strong> <span id="card-cli-nome"></span></div>
                    <div><strong>ENDEREÇO:</strong> <span id="card-cli-endereco"></span></div>
                    <div><strong>CIDADE:</strong> <span id="card-cli-cidade"></span> &nbsp; <strong>FONE:</strong> <span id="card-cli-fone"></span></div>
                    <div><strong>PRODUTOS:</strong> <span id="card-produtos-desc"></span></div>
                    <div><strong>VALOR TOTAL:</strong> <span id="card-vl-total"></span> &nbsp; <strong>ENTRADA:</strong> <span id="card-vl-entrada"></span> &nbsp; <strong>SALDO:</strong> <span id="card-vl-saldo-inicial"></span></div>
                    <div class="text-[8px] text-slate-700"><strong>EMISSÃO:</strong> <span id="card-dt-emissao"></span> &nbsp; <strong>VENDEDOR:</strong> <span id="card-vendedor-nome"></span></div>
                </div>

                <!-- Tabela de Parcelas no Formato 1/4 A4 com Linha Completa -->
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
                    <tbody id="card-tabela-parcelas">
                        <!-- Gerado dinamicamente -->
                    </tbody>
                </table>
            </div>

            <!-- Rodapé com Link / Informação de Autenticidade -->
            <div class="pt-2 border-t border-black text-[7px] text-center leading-tight">
                <div>Conserve este cartão com os devidos pagamentos autenticados pelo cobrador.</div>
                <div id="card-public-link-box" class="font-mono text-[6.5px] mt-0.5"></div>
            </div>
        </div>

    </div>

    <!-- Bootstrap de Dados Pré-carregados pelo PHP -->
    <script>
        const INITIAL_TENANT_ID = <?= json_encode($usuarioId) ?>;
        const INITIAL_LOJA_NOME = <?= json_encode($lojaNome) ?>;
        const INITIAL_VENDEDORES = <?= json_encode(array_map(fn($v) => ['id' => (string)$v->id, 'nome' => $v->nome_completo], $vendedores)) ?>;
        const INITIAL_PRODUTOS = <?= json_encode(array_map(fn($p) => ['id' => (string)$p->id, 'nome' => $p->nome, 'preco' => (float)($p->preco_venda_sugerido ?: $p->preco_custo ?: 0), 'codigo' => $p->codigo_referencia ?: substr($p->id, 0, 6)], $produtos)) ?>;
        const INITIAL_CLIENTES = <?= json_encode(array_map(fn($c) => [
            'id' => (string)$c->id,
            'nome' => $c->nome_completo,
            'telefone' => $c->getTelefoneFormatado() ?: $c->telefone,
            'logradouro' => $c->endereco_logradouro,
            'numero' => $c->endereco_numero,
            'bairro' => $c->endereco_bairro,
            'cidade' => $c->endereco_cidade,
            'estado' => $c->endereco_estado,
        ], $clientes)) ?>;
    </script>

    <!-- App Logic Offline & Mobile First -->
    <script>
        let appState = {
            tenantId: INITIAL_TENANT_ID || 'tenant_default',
            lojaNome: INITIAL_LOJA_NOME,
            vendedores: INITIAL_VENDEDORES || [],
            produtos: INITIAL_PRODUTOS || [],
            clientes: INITIAL_CLIENTES || [],
            carrinho: [],
            clienteSelecionado: null,
            tipoCliente: 'existente', // 'existente' ou 'novo'
            vendaAtivaParaCartao: null
        };

        // Iniciação
        document.addEventListener('DOMContentLoaded', () => {
            carregarStorage();
            configurarRede();
            atualizarDataPrimeiraPorFrequencia();
            renderizarCarrinho();
            renderizarVendasGravadas();
            atualizarBadgesContadores();
        });

        function carregarStorage() {
            try {
                const storedProd = localStorage.getItem('pulse_produtos');
                if (storedProd) appState.produtos = JSON.parse(storedProd);
                else localStorage.setItem('pulse_produtos', JSON.stringify(appState.produtos));

                const storedCli = localStorage.getItem('pulse_clientes');
                if (storedCli) appState.clientes = JSON.parse(storedCli);
                else localStorage.setItem('pulse_clientes', JSON.stringify(appState.clientes));

                const storedVend = localStorage.getItem('pulse_vendedores');
                if (storedVend) appState.vendedores = JSON.parse(storedVend);
                else localStorage.setItem('pulse_vendedores', JSON.stringify(appState.vendedores));

                document.getElementById('stat-produtos-count').textContent = appState.produtos.length;
                document.getElementById('stat-clientes-count').textContent = appState.clientes.length;
            } catch (e) {
                console.warn('Erro ao ler localStorage', e);
            }
        }

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

        function trocarAba(abaId) {
            ['nova-venda', 'vendas-salvas', 'config-dados'].forEach(a => {
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
            if (abaId === 'vendas-salvas') renderizarVendasGravadas();
        }

        // =========================================================================
        // CLIENTES
        // =========================================================================
        function alternarTipoCliente() {
            const btn = document.getElementById('btn-tipo-cliente');
            const boxExistente = document.getElementById('box-cliente-existente');
            const boxNovo = document.getElementById('box-cliente-novo');

            if (appState.tipoCliente === 'existente') {
                appState.tipoCliente = 'novo';
                btn.textContent = '🔍 Buscar Cadastrado';
                boxExistente.classList.add('hidden');
                boxNovo.classList.remove('hidden');
            } else {
                appState.tipoCliente = 'existente';
                btn.textContent = '➕ Novo Cadastro';
                boxNovo.classList.add('hidden');
                boxExistente.classList.remove('hidden');
            }
        }

        function filtrarClientesOffline(termo) {
            const container = document.getElementById('lista-clientes-sugestoes');
            termo = (termo || '').toLowerCase().trim();
            if (!termo) {
                container.classList.add('hidden');
                return;
            }

            const resultados = appState.clientes.filter(c => 
                (c.nome && c.nome.toLowerCase().includes(termo)) ||
                (c.telefone && c.telefone.includes(termo)) ||
                (c.bairro && c.bairro.toLowerCase().includes(termo))
            ).slice(0, 10);

            if (resultados.length === 0) {
                container.innerHTML = '<div class="p-2 text-xs text-slate-500 text-center">Nenhum cliente offline encontrado</div>';
                container.classList.remove('hidden');
                return;
            }

            container.innerHTML = resultados.map(c => `
                <div onclick='selecionarCliente(${JSON.stringify(c).replace(/'/g, "&#39;")})' class="p-2.5 hover:bg-slate-800 cursor-pointer transition">
                    <div class="font-bold text-white text-xs">${c.nome}</div>
                    <div class="text-[10px] text-slate-400">${c.bairro || ''} ${c.cidade ? '- ' + c.cidade : ''} • ${c.telefone || 'Sem fone'}</div>
                </div>
            `).join('');
            container.classList.remove('hidden');
        }

        function selecionarCliente(cli) {
            appState.clienteSelecionado = cli;
            document.getElementById('busca-cliente').value = '';
            document.getElementById('lista-clientes-sugestoes').classList.add('hidden');

            document.getElementById('cliente-sel-nome').textContent = cli.nome;
            document.getElementById('cliente-sel-end').textContent = `${cli.logradouro || ''} ${cli.numero || ''} ${cli.bairro ? '- ' + cli.bairro : ''}`;
            document.getElementById('cliente-selecionado-card').classList.remove('hidden');
        }

        function removerClienteSelecionado() {
            appState.clienteSelecionado = null;
            document.getElementById('cliente-selecionado-card').classList.add('hidden');
        }

        // =========================================================================
        // PRODUTOS & CARRINHO
        // =========================================================================
        function mostrarModalAdicionarItem() {
            document.getElementById('modal-adicionar-produto').classList.remove('hidden');
            filtrarProdutosModal('');
        }

        function fecharModalProduto() {
            document.getElementById('modal-adicionar-produto').classList.add('hidden');
        }

        function filtrarProdutosModal(termo) {
            const container = document.getElementById('modal-lista-produtos');
            termo = (termo || '').toLowerCase().trim();
            const list = appState.produtos.filter(p => !termo || p.nome.toLowerCase().includes(termo)).slice(0, 15);

            if (list.length === 0) {
                container.innerHTML = '<div class="p-3 text-xs text-slate-500 text-center">Nenhum produto cadastrado no catálogo offline</div>';
                return;
            }

            container.innerHTML = list.map(p => `
                <div onclick='adicionarProdutoAoCarrinho(${JSON.stringify(p).replace(/'/g, "&#39;")})' class="p-2.5 hover:bg-slate-800 flex items-center justify-between cursor-pointer rounded-xl transition">
                    <div>
                        <div class="font-bold text-xs text-white">${p.nome}</div>
                        <div class="text-[10px] text-slate-400">Cód: ${p.codigo || 'S/N'}</div>
                    </div>
                    <div class="font-mono font-bold text-xs text-amber-400">
                        R$ ${Number(p.preco || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                    </div>
                </div>
            `).join('');
        }

        function adicionarProdutoAoCarrinho(prod) {
            const itemExistente = appState.carrinho.find(i => i.produto_id === prod.id);
            if (itemExistente) {
                itemExistente.quantidade += 1;
            } else {
                appState.carrinho.push({
                    produto_id: prod.id,
                    nome: prod.nome,
                    preco: Number(prod.preco || 0),
                    quantidade: 1
                });
            }
            fecharModalProduto();
            renderizarCarrinho();
            recalcularPlano();
        }

        function adicionarItemAvulso() {
            const nome = document.getElementById('modal-item-avulso-nome').value.trim();
            const preco = parseFloat(document.getElementById('modal-item-avulso-preco').value || '0');
            const qtd = parseInt(document.getElementById('modal-item-avulso-qtd').value || '1');

            if (!nome || preco <= 0) {
                alert('Informe o nome e o preço válido do produto avulso.');
                return;
            }

            appState.carrinho.push({
                produto_id: null,
                nome: nome,
                preco: preco,
                quantidade: Math.max(1, qtd)
            });

            document.getElementById('modal-item-avulso-nome').value = '';
            document.getElementById('modal-item-avulso-preco').value = '';
            document.getElementById('modal-item-avulso-qtd').value = '1';

            fecharModalProduto();
            renderizarCarrinho();
            recalcularPlano();
        }

        function alterarQtdItem(index, delta) {
            if (!appState.carrinho[index]) return;
            appState.carrinho[index].quantidade += delta;
            if (appState.carrinho[index].quantidade <= 0) {
                appState.carrinho.splice(index, 1);
            }
            renderizarCarrinho();
            recalcularPlano();
        }

        function renderizarCarrinho() {
            const container = document.getElementById('carrinho-itens');
            const vazio = document.getElementById('carrinho-vazio');

            if (appState.carrinho.length === 0) {
                container.innerHTML = '<div class="p-4 text-center text-slate-500 text-xs border border-dashed border-slate-800 rounded-xl">Nenhum produto adicionado ainda.</div>';
                document.getElementById('lbl-total-venda').textContent = 'R$ 0,00';
                return;
            }

            let total = 0;
            container.innerHTML = appState.carrinho.map((item, idx) => {
                const subtotal = item.quantidade * item.preco;
                total += subtotal;
                return `
                    <div class="p-2.5 bg-slate-950 border border-slate-800 rounded-xl flex items-center justify-between">
                        <div class="flex-1 pr-2">
                            <div class="font-bold text-xs text-white leading-tight">${item.nome}</div>
                            <div class="text-[10px] text-slate-400 font-mono">
                                ${item.quantidade}x R$ ${item.preco.toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex items-center bg-slate-900 border border-slate-800 rounded-lg">
                                <button type="button" onclick="alterarQtdItem(${idx}, -1)" class="px-2 py-1 text-slate-400 hover:text-white text-xs font-bold">-</button>
                                <span class="px-1.5 text-xs font-bold text-white">${item.quantidade}</span>
                                <button type="button" onclick="alterarQtdItem(${idx}, 1)" class="px-2 py-1 text-slate-400 hover:text-white text-xs font-bold">+</button>
                            </div>
                            <div class="text-right font-mono font-bold text-xs text-amber-400 min-w-[70px]">
                                R$ ${subtotal.toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            document.getElementById('lbl-total-venda').textContent = `R$ ${total.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
        }

        // =========================================================================
        // PLANO DE PAGAMENTO / DATAS / PARCELAS
        // =========================================================================
        function atualizarDataPrimeiraPorFrequencia() {
            const dtVendaStr = document.getElementById('campo-data-venda').value || new Date().toISOString().split('T')[0];
            const freqDias = parseInt(document.getElementById('campo-frequencia').value || '7');
            
            const dt = new Date(dtVendaStr + 'T00:00:00');
            dt.setDate(dt.getDate() + freqDias);
            
            const ano = dt.getFullYear();
            const mes = String(dt.getMonth() + 1).padStart(2, '0');
            const dia = String(dt.getDate()).padStart(2, '0');
            document.getElementById('campo-data-primeira').value = `${ano}-${mes}-${dia}`;
        }

        function recalcularPlano() {
            const total = appState.carrinho.reduce((acc, i) => acc + (i.quantidade * i.preco), 0);
            const entrada = parseFloat(document.getElementById('campo-valor-entrada').value || '0');
            const numParcelas = Math.max(1, parseInt(document.getElementById('campo-numero-parcelas').value || '1'));

            const saldoFinanciar = Math.max(0, total - entrada);
            const valorParcela = saldoFinanciar / numParcelas;

            document.getElementById('lbl-saldo-financiar').textContent = `R$ ${saldoFinanciar.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
            document.getElementById('lbl-valor-parcela').textContent = `R$ ${valorParcela.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
        }

        // =========================================================================
        // EMISSÃO DO CARTÃO (OFFLINE FIRST)
        // =========================================================================
        function emitirCartaoVenda() {
            // Validações
            let cliente = null;
            if (appState.tipoCliente === 'existente') {
                if (!appState.clienteSelecionado) {
                    alert('Selecione um cliente para a venda.');
                    return;
                }
                cliente = appState.clienteSelecionado;
            } else {
                const nome = document.getElementById('novo-cliente-nome').value.trim();
                const tel = document.getElementById('novo-cliente-telefone').value.trim();
                const bairro = document.getElementById('novo-cliente-bairro').value.trim();
                const cidade = document.getElementById('novo-cliente-cidade').value.trim();

                if (!nome || !tel || !bairro) {
                    alert('Preencha os campos obrigatórios do novo cliente (Nome, Telefone e Bairro).');
                    return;
                }

                cliente = {
                    id: null,
                    nome: nome,
                    telefone: tel,
                    cpf: document.getElementById('novo-cliente-cpf').value.trim(),
                    logradouro: document.getElementById('novo-cliente-rua').value.trim(),
                    numero: document.getElementById('novo-cliente-numero').value.trim(),
                    bairro: bairro,
                    cidade: cidade || 'Franca',
                    estado: 'SP'
                };
            }

            if (appState.carrinho.length === 0) {
                alert('Adicione ao menos um produto no carrinho.');
                return;
            }

            const total = appState.carrinho.reduce((acc, i) => acc + (i.quantidade * i.preco), 0);
            const entrada = parseFloat(document.getElementById('campo-valor-entrada').value || '0');
            const numParcelas = Math.max(1, parseInt(document.getElementById('campo-numero-parcelas').value || '1'));
            const frequencia = parseInt(document.getElementById('campo-frequencia').value || '7');
            const dataVenda = document.getElementById('campo-data-venda').value;
            const dataPrimeira = document.getElementById('campo-data-primeira').value;
            const vendedorSelect = document.getElementById('sel-vendedor');
            const vendedorId = vendedorSelect ? vendedorSelect.value : null;
            const vendedorNome = (vendedorSelect && vendedorSelect.tagName === 'SELECT' && vendedorSelect.selectedIndex >= 0)
                ? vendedorSelect.options[vendedorSelect.selectedIndex].text
                : (vendedorSelect ? (vendedorSelect.getAttribute('data-nome') || vendedorSelect.value) : 'Vendedor');

            const tempId = 'venda_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);

            // Monta lista de parcelas calculadas
            const saldoAFinanciar = Math.max(0, total - entrada);
            const valorUnitarioParcela = saldoAFinanciar / numParcelas;
            const parcelasCalculadas = [];

            let dtCorrente = new Date(dataPrimeira + 'T00:00:00');
            let saldoDevedorAcumulado = total;

            for (let i = 1; i <= numParcelas; i++) {
                let dtVencFormatada = dtCorrente.toLocaleDateString('pt-BR');
                
                let dataPag = '';
                let vlReceb = '';
                let tipoPag = '';
                let saldoLinha = '';

                // Se houver entrada e for a 1ª parcela
                if (i === 1 && entrada > 0) {
                    dataPag = new Date(dataVenda + 'T00:00:00').toLocaleDateString('pt-BR');
                    vlReceb = entrada.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                    tipoPag = 'ENTRADA';
                    saldoDevedorAcumulado -= entrada;
                    saldoLinha = saldoDevedorAcumulado.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                }

                parcelasCalculadas.push({
                    numero: i,
                    data_vencimento: dtVencFormatada,
                    valor_parcela: valorUnitarioParcela,
                    valor_compra: total,
                    data_pagamento: dataPag,
                    valor_recebido: vlReceb,
                    tipo_pagamento: tipoPag,
                    saldo_devedor: saldoLinha
                });

                // Avança pela frequência
                dtCorrente.setDate(dtCorrente.getDate() + frequencia);
            }

            const registroVenda = {
                temp_id: tempId,
                sincronizado: false,
                data_criacao: new Date().toISOString(),
                cliente: cliente,
                vendedor_id: vendedorId,
                vendedor_nome: vendedorNome,
                itens: [...appState.carrinho],
                valor_total: total,
                valor_entrada: entrada,
                numero_parcelas: numParcelas,
                frequencia: frequencia,
                data_venda: dataVenda,
                data_primeiro_vencimento: dataPrimeira,
                parcelas: parcelasCalculadas,
                public_url: null,
                venda_id: null
            };

            // Salva na lista offline local
            salvarVendaOffline(registroVenda);

            // Exibe Cartão no Modal
            abrirModalCartao(registroVenda);

            // Reseta formulário da venda
            appState.carrinho = [];
            appState.clienteSelecionado = null;
            removerClienteSelecionado();
            renderizarCarrinho();
            recalcularPlano();
            atualizarBadgesContadores();
        }

        function salvarVendaOffline(venda) {
            const vendas = obterVendasOffline();
            vendas.unshift(venda);
            localStorage.setItem('pulse_vendas_offline', JSON.stringify(vendas));
        }

        function obterVendasOffline() {
            try {
                return JSON.parse(localStorage.getItem('pulse_vendas_offline') || '[]');
            } catch (e) {
                return [];
            }
        }

        function abrirModalCartao(venda) {
            appState.vendaAtivaParaCartao = venda;
            const modal = document.getElementById('modal-cartao-gerado');

            document.getElementById('card-empresa-nome').textContent = appState.lojaNome;
            document.getElementById('card-cli-nome').textContent = venda.cliente.nome;
            document.getElementById('card-cli-endereco').textContent = `${venda.cliente.logradouro || ''} ${venda.cliente.numero || ''} - ${venda.cliente.bairro || ''}`;
            document.getElementById('card-cli-cidade').textContent = venda.cliente.cidade || '';
            document.getElementById('card-cli-fone').textContent = venda.cliente.telefone || '';

            const descProdutos = venda.itens.map(i => `${i.quantidade}x ${i.nome}`).join(', ');
            document.getElementById('card-produtos-desc').textContent = descProdutos;
            document.getElementById('card-vl-total').textContent = `R$ ${venda.valor_total.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
            document.getElementById('card-vl-entrada').textContent = `R$ ${venda.valor_entrada.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
            document.getElementById('card-vl-saldo-inicial').textContent = `R$ ${(venda.valor_total - venda.valor_entrada).toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
            document.getElementById('card-dt-emissao').textContent = new Date(venda.data_venda + 'T00:00:00').toLocaleDateString('pt-BR');
            document.getElementById('card-vendedor-nome').textContent = venda.vendedor_nome || '';

            // Tabela de parcelas
            const tbody = document.getElementById('card-tabela-parcelas');
            tbody.innerHTML = venda.parcelas.map(p => `
                <tr>
                    <td class="font-bold">${p.numero}</td>
                    <td>${p.data_vencimento}</td>
                    <td class="font-bold font-mono">${p.valor_parcela.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                    <td class="font-mono">${p.valor_compra.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                    <td>${p.data_pagamento || ''}</td>
                    <td class="font-mono">${p.valor_recebido || ''}</td>
                    <td>${p.tipo_pagamento || ''}</td>
                    <td class="text-left font-mono pl-1 font-bold">${p.saldo_devedor || ''}</td>
                </tr>
            `).join('');

            // Link público se já sincronizado
            const boxLink = document.getElementById('card-public-link-box');
            if (venda.public_url) {
                boxLink.innerHTML = `Link On-line: <a href="${venda.public_url}" target="_blank" class="underline">${venda.public_url}</a>`;
            } else {
                boxLink.innerHTML = `Cartão emitido em rota ambulante • Sincronização pendente`;
            }

            modal.classList.remove('hidden');
        }

        function fecharModalCartao() {
            document.getElementById('modal-cartao-gerado').classList.add('hidden');
            appState.vendaAtivaParaCartao = null;
        }

        // =========================================================================
        // EXPORTAÇÃO: IMAGEM PNG, PDF E WHATSAPP
        // =========================================================================
        async function baixarCartaoImagem() {
            const elem = document.getElementById('area-cartao-render');
            if (!elem) return;

            try {
                const canvas = await html2canvas(elem, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
                const link = document.createElement('a');
                const cliNome = (appState.vendaAtivaParaCartao?.cliente?.nome || 'cartao').replace(/[^a-zA-Z0-9]/g, '_');
                link.download = `cartao_${cliNome}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
            } catch (e) {
                alert('Erro ao gerar imagem do cartão: ' + e.message);
            }
        }

        async function baixarCartaoPdf() {
            const elem = document.getElementById('area-cartao-render');
            if (!elem) return;

            try {
                const canvas = await html2canvas(elem, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
                const imgData = canvas.toDataURL('image/png');

                // Dimensões exatas de 1/4 da folha A4 (105mm x 148mm)
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: [105, 148]
                });

                pdf.addImage(imgData, 'PNG', 0, 0, 105, 148);
                const cliNome = (appState.vendaAtivaParaCartao?.cliente?.nome || 'cartao').replace(/[^a-zA-Z0-9]/g, '_');
                pdf.save(`cartao_${cliNome}.pdf`);
            } catch (e) {
                alert('Erro ao gerar PDF: ' + e.message);
            }
        }

        function compartilharWhatsAppCartao() {
            const venda = appState.vendaAtivaParaCartao;
            if (!venda) return;

            const cli = venda.cliente;
            const fone = (cli.telefone || '').replace(/\D/g, '');
            const numParcelas = venda.numero_parcelas;
            const valorParcela = (venda.valor_total - venda.valor_entrada) / numParcelas;

            let msg = `*${appState.lojaNome.toUpperCase()}*\n`;
            msg += `Olá ${cli.nome}, segue o comprovante do seu crediário:\n\n`;
            msg += `📦 *Produtos:* ${venda.itens.map(i => `${i.quantidade}x ${i.nome}`).join(', ')}\n`;
            msg += `💰 *Valor Total:* R$ ${venda.valor_total.toLocaleString('pt-BR', {minimumFractionDigits: 2})}\n`;
            if (venda.valor_entrada > 0) {
                msg += `💵 *Entrada:* R$ ${venda.valor_entrada.toLocaleString('pt-BR', {minimumFractionDigits: 2})}\n`;
            }
            msg += `📅 *Parcelamento:* ${numParcelas}x de R$ ${valorParcela.toLocaleString('pt-BR', {minimumFractionDigits: 2})}\n`;
            msg += `🗓️ *1º Vencimento:* ${new Date(venda.data_primeiro_vencimento + 'T00:00:00').toLocaleDateString('pt-BR')}\n`;

            if (venda.public_url) {
                msg += `\n🔗 *Acompanhe seu Cartão On-line:* ${venda.public_url}\n`;
            } else {
                msg += `\n_Cartão físico emitido na sua porta. Guarde este comprovante._\n`;
            }

            const urlZap = fone 
                ? `https://api.whatsapp.com/send?phone=55${fone}&text=${encodeURIComponent(msg)}`
                : `https://api.whatsapp.com/send?text=${encodeURIComponent(msg)}`;

            window.open(urlZap, '_blank');
        }

        // =========================================================================
        // HISTÓRICO LOCAL E SINCRONIZAÇÃO COM A CENTRAL
        // =========================================================================
        function renderizarVendasGravadas() {
            const container = document.getElementById('lista-vendas-gravadas');
            const vendas = obterVendasOffline();

            document.getElementById('cont-vendas-locais').textContent = vendas.length;

            if (vendas.length === 0) {
                container.innerHTML = '<div class="p-8 text-center text-slate-500 text-xs">Nenhuma venda gravada neste aparelho.</div>';
                return;
            }

            container.innerHTML = vendas.map(v => {
                const cliNome = v.cliente ? v.cliente.nome : 'Cliente';
                const statusBadge = v.sincronizado 
                    ? `<span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold rounded-lg">✓ Sincronizado</span>`
                    : `<span class="px-2 py-0.5 bg-amber-500/10 text-amber-400 border border-amber-500/30 text-[10px] font-bold rounded-lg">🟡 Gravada no Celular</span>`;

                return `
                    <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl flex flex-col gap-2">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="font-bold text-white text-xs">${cliNome}</div>
                                <div class="text-[10px] text-slate-400">${new Date(v.data_criacao).toLocaleString('pt-BR')} • ${v.itens.length} produto(s)</div>
                            </div>
                            <div>${statusBadge}</div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-slate-850 text-xs">
                            <span class="font-mono font-bold text-amber-400">R$ ${v.valor_total.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                            <div class="flex items-center gap-2">
                                <button onclick='reabrirCartaoVenda("${v.temp_id}")' class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-[11px] rounded-lg transition">
                                    📇 Ver Cartão
                                </button>
                                ${!v.sincronizado ? `
                                    <button onclick="sincronizarVendas()" class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-[11px] rounded-lg transition">
                                        Enviar
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function reabrirCartaoVenda(tempId) {
            const vendas = obterVendasOffline();
            const venda = vendas.find(v => v.temp_id === tempId);
            if (venda) abrirModalCartao(venda);
        }

        async function sincronizarVendas() {
            if (!navigator.onLine) {
                alert('Aparelho sem conexão com a internet no momento. As vendas continuam salvas no celular.');
                return;
            }

            const vendas = obterVendasOffline();
            const pendentes = vendas.filter(v => !v.sincronizado);

            if (pendentes.length === 0) {
                alert('Tudo sincronizado! Não há vendas pendentes no aparelho.');
                return;
            }

            const btn = document.getElementById('btn-sync-topo');
            btn.classList.add('animate-spin');

            try {
                const response = await fetch('<?= Url::to(['/prestanista/vendedor/sincronizar']) ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        usuario_id: appState.tenantId,
                        vendas_offline: pendentes
                    })
                });

                const data = await response.json();
                if (data.success && data.sincronizados) {
                    // Atualiza status local
                    data.sincronizados.forEach(item => {
                        const local = vendas.find(v => v.temp_id === item.temp_id);
                        if (local) {
                            local.sincronizado = true;
                            local.venda_id = item.venda_id;
                            local.public_url = item.public_url;
                        }
                    });

                    localStorage.setItem('pulse_vendas_offline', JSON.stringify(vendas));
                    renderizarVendasGravadas();
                    atualizarBadgesContadores();
                    alert(`✓ Sucesso! ${data.total_sincronizados} venda(s) foram enviadas para a central com sucesso.`);
                } else {
                    alert('Erro na resposta do servidor: ' + (data.mensagem || 'Falha ao sincronizar'));
                }
            } catch (e) {
                alert('Não foi possível conectar ao servidor. Tente novamente mais tarde.');
            } finally {
                btn.classList.remove('animate-spin');
            }
        }

        async function baixarCatalogoCompleto() {
            if (!navigator.onLine) {
                alert('Você precisa estar conectado à internet para atualizar o catálogo.');
                return;
            }

            try {
                const response = await fetch('<?= Url::to(['/prestanista/vendedor/dados-iniciais']) ?>');
                const data = await response.json();
                if (data.success) {
                    appState.produtos = data.produtos || [];
                    appState.clientes = data.clientes || [];
                    appState.vendedores = data.vendedores || [];
                    localStorage.setItem('pulse_produtos', JSON.stringify(appState.produtos));
                    localStorage.setItem('pulse_clientes', JSON.stringify(appState.clientes));
                    localStorage.setItem('pulse_vendedores', JSON.stringify(appState.vendedores));

                    document.getElementById('stat-produtos-count').textContent = appState.produtos.length;
                    document.getElementById('stat-clientes-count').textContent = appState.clientes.length;
                    alert('✓ Catálogo offline e clientes atualizados com sucesso!');
                }
            } catch (e) {
                alert('Erro ao atualizar dados: ' + e.message);
            }
        }

        function limparVendasSincronizadas() {
            if (!confirm('Deseja remover da memória do celular as vendas que já foram enviadas para o servidor?')) return;
            const vendas = obterVendasOffline();
            const pendentes = vendas.filter(v => !v.sincronizado);
            localStorage.setItem('pulse_vendas_offline', JSON.stringify(pendentes));
            renderizarVendasGravadas();
            atualizarBadgesContadores();
            alert('Memória limpa com sucesso!');
        }

        function atualizarBadgesContadores() {
            const vendas = obterVendasOffline();
            const pendentes = vendas.filter(v => !v.sincronizado).length;
            const badge = document.getElementById('badge-pendentes-count');
            if (pendentes > 0) {
                badge.textContent = pendentes;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
            document.getElementById('cont-vendas-locais').textContent = vendas.length;
        }
    </script>
</body>
</html>
