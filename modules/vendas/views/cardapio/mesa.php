<?php

use yii\helpers\Html;
use yii\helpers\Url;

$nomeLoja = ($loja && !empty($loja->nome)) ? $loja->nome : 'PULSE Food Service';
$logoLoja = ($loja && !empty($loja->logo_path)) ? $loja->logo_path : null;
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Cardápio Digital &bull; Mesa <?= Html::encode($mesa->numero_mesa) ?> — <?= Html::encode($nomeLoja) ?></title>
    
    <?= Html::csrfMetaTags() ?>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            900: '#064e3b',
                            950: '#022c22',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .scrollbar-none::-webkit-scrollbar { display: none; }
        .scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }
        .tab-active {
            background-color: #059669;
            color: #ffffff;
            box-shadow: 0 4px 14px 0 rgba(5, 150, 105, 0.39);
        }
        .tab-inactive {
            color: #94a3b8;
            background-color: rgba(30, 41, 59, 0.6);
        }
    </style>
</head>
<body class="h-full text-slate-100 font-sans antialiased selection:bg-emerald-500 selection:text-white pb-32">

    <!-- ========================================================================= -->
    <!-- HEADER FIXO (BRANDING & MESAS) -->
    <!-- ========================================================================= -->
    <header class="sticky top-0 z-40 bg-slate-900/90 backdrop-blur-md border-b border-slate-800">
        <div class="max-w-xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center font-black text-slate-950 text-base shadow-lg shadow-emerald-500/20 flex-shrink-0">
                    <?= mb_substr($nomeLoja, 0, 1, 'UTF-8') ?>
                </div>
                <div class="min-w-0">
                    <h1 class="text-sm font-extrabold text-white truncate tracking-tight m-0">
                        <?= Html::encode($nomeLoja) ?>
                    </h1>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Mesa <?= Html::encode($mesa->numero_mesa) ?>
                        </span>
                        <?php if ($mesa->nome_identificador): ?>
                            <span class="text-[11px] text-slate-400 truncate">&bull; <?= Html::encode($mesa->nome_identificador) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Ações Rápidas Topo: Chamar Garçom e Pedir Conta -->
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <button type="button" onclick="chamarGarcomAction()" id="btnTopGarcom" class="p-2 sm:px-3 sm:py-1.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 text-xs font-bold transition active:scale-95 flex items-center gap-1.5" title="Chamar Garçom">
                    <span class="text-sm">🔔</span>
                    <span class="hidden sm:inline">Garçom</span>
                </button>

                <button type="button" onclick="pedirContaAction()" id="btnTopConta" class="p-2 sm:px-3 sm:py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-bold transition active:scale-95 flex items-center gap-1.5" title="Pedir Conta">
                    <span class="text-sm">💳</span>
                    <span class="hidden sm:inline">Conta</span>
                </button>
            </div>
        </div>

        <!-- ABAS DE NAVEGAÇÃO PRINCIPAL (CARDÁPIO vs MEUS PEDIDOS vs CHAT GARÇOM) -->
        <div class="max-w-xl mx-auto px-4 pb-2">
            <div class="grid grid-cols-3 gap-1.5 p-1 bg-slate-950/80 rounded-2xl border border-slate-800">
                <button type="button" onclick="alternarAba('cardapio')" id="tabBtnCardapio" class="py-2.5 px-2 rounded-xl text-xs font-extrabold transition flex items-center justify-center gap-1.5 tab-active">
                    <span>🍽️</span>
                    <span>Cardápio</span>
                </button>
                
                <button type="button" onclick="alternarAba('pedidos')" id="tabBtnPedidos" class="py-2.5 px-2 rounded-xl text-xs font-extrabold transition flex items-center justify-center gap-1.5 tab-inactive">
                    <span>📋</span>
                    <span>Mesa</span>
                    <span id="badgeContagemExtrato" class="hidden px-1.5 py-0.2 bg-emerald-500 text-slate-950 rounded-full text-[9px] font-black">0</span>
                </button>

                <button type="button" onclick="alternarAba('chat')" id="tabBtnChat" class="py-2.5 px-2 rounded-xl text-xs font-extrabold transition flex items-center justify-center gap-1.5 tab-inactive relative">
                    <span>💬</span>
                    <span>Garçom</span>
                    <span id="badgeNovasMensagensChat" class="hidden w-2 h-2 bg-amber-400 rounded-full animate-ping"></span>
                </button>
            </div>
        </div>
    </header>

    <!-- ========================================================================= -->
    <!-- ABA 1: CARDÁPIO DIGITAL (LISTAGEM DE PRODUTOS) -->
    <!-- ========================================================================= -->
    <main id="abaCardapio" class="max-w-xl mx-auto px-4 pt-4 space-y-4">

        <!-- Campo de Busca Instantânea -->
        <div class="relative">
            <input type="text" id="inputBusca" oninput="filtrarCardapio()" placeholder="Buscar pratos, bebidas, sobremesas..." class="w-full pl-10 pr-4 py-2.5 bg-slate-900 border border-slate-800 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded-2xl text-xs text-white placeholder-slate-500 transition">
            <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>

        <!-- Categorias (Carrossel Horizontal) -->
        <div class="overflow-x-auto whitespace-nowrap scrollbar-none flex space-x-2 py-1">
            <button type="button" onclick="filtrarCategoria('todas', this)" class="btn-cat px-3.5 py-2 rounded-xl text-xs font-extrabold transition bg-emerald-600 text-white shadow-sm shadow-emerald-600/30">
                ⭐ Todas
            </button>
            <?php foreach ($categorias as $cat): ?>
                <button type="button" onclick="filtrarCategoria('cat-<?= $cat->id ?>', this)" class="btn-cat px-3.5 py-2 rounded-xl text-xs font-bold transition bg-slate-900 border border-slate-800 text-slate-300 hover:text-white hover:border-slate-700">
                    <?= Html::encode($cat->nome) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Grid de Produtos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="gridProdutos">
            <?php foreach ($produtos as $p): ?>
                <?php
                $fotoObj = $p->fotoPrincipal ?: ($p->fotos[0] ?? null);
                $fotoUrl = null;
                if ($fotoObj && !empty($fotoObj->arquivo_path)) {
                    $caminhoLimpo = ltrim($fotoObj->arquivo_path, '/');
                    $fotoUrl = '/' . $caminhoLimpo;
                } elseif ($fotoObj && method_exists($fotoObj, 'getUrl')) {
                    $fotoUrl = $fotoObj->getUrl();
                }

                $precoFmt = number_format($p->getPrecoFinal(), 2, ',', '.');
                $catClass = $p->categoria_id ? "cat-{$p->categoria_id}" : "";
                $temOps = count($p->opcionais) > 0;
                ?>
                <div class="card-produto <?= $catClass ?> bg-slate-900/90 border border-slate-800/80 rounded-3xl p-3 flex flex-col justify-between shadow-sm hover:border-slate-700 transition"
                    data-id="<?= $p->id ?>"
                    data-nome="<?= strtolower(Html::encode($p->nome)) ?>"
                    data-desc="<?= strtolower(Html::encode($p->descricao ?: '')) ?>">
                    
                    <div class="flex items-start gap-3">
                        <!-- Imagem do Produto -->
                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-slate-950 border border-slate-800 flex-shrink-0 flex items-center justify-center overflow-hidden">
                            <?php if ($fotoUrl): ?>
                                <img src="<?= Html::encode($fotoUrl) ?>" alt="<?= Html::encode($p->nome) ?>" class="w-full h-full object-cover" loading="lazy" onerror="this.onerror=null; this.parentElement.innerHTML='<span class=\'text-3xl\'>🍲</span>';">
                            <?php else: ?>
                                <span class="text-3xl">🍲</span>
                            <?php endif; ?>
                        </div>

                        <!-- Detalhes do Produto -->
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xs sm:text-sm font-black text-white truncate m-0"><?= Html::encode($p->nome) ?></h3>
                            <p class="text-[11px] text-slate-400 mt-1 line-clamp-2 m-0"><?= Html::encode($p->descricao ?: 'Sem descrição informada.') ?></p>
                            
                            <?php if ($temOps): ?>
                                <span class="inline-block text-[10px] font-bold text-amber-400 bg-amber-400/10 px-2 py-0.5 rounded-full mt-1.5">
                                    + Opções disponíveis
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Preço e Botão Adicionar -->
                    <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-slate-800/60">
                        <div>
                            <span class="text-[10px] text-slate-400 block font-semibold">A partir de</span>
                            <span class="text-sm sm:text-base font-black text-emerald-400 font-mono">R$ <?= $precoFmt ?></span>
                        </div>

                        <button type="button" onclick="abrirModalItem(<?= Html::encode(json_encode([
                            'id' => $p->id,
                            'nome' => $p->nome,
                            'foto' => $fotoUrl,
                            'preco' => (float)$p->getPrecoFinal(),
                            'preco_formatado' => $precoFmt,
                            'descricao' => $p->descricao,
                            'opcionais' => array_map(function($op) {
                                return [
                                    'id' => $op->id,
                                    'nome' => $op->nome,
                                    'valor_adicional' => (float)$op->valor_adicional,
                                    'valor_formatado' => number_format($op->valor_adicional, 2, ',', '.')
                                ];
                            }, $p->opcionais)
                        ])) ?>)" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs rounded-xl shadow-md transition active:scale-95 flex items-center gap-1">
                            <span>+</span>
                            <span>Pedir</span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </main>

    <!-- ========================================================================= -->
    <!-- ABA 2: MINHA MESA (EXTRATO & STATUS DOS PEDIDOS EM TEMPO REAL) -->
    <!-- ========================================================================= -->
    <main id="abaPedidos" class="max-w-xl mx-auto px-4 pt-4 space-y-4 hidden pb-24">

        <!-- Card Resumo de Consumo -->
        <div class="bg-gradient-to-br from-slate-900 via-slate-900 to-slate-950 border border-slate-800 rounded-3xl p-5 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Consumo da Mesa</span>
                    <h2 class="text-2xl font-black text-emerald-400 font-mono mt-0.5" id="lblExtratoTotal">R$ 0,00</h2>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        Mesa <?= Html::encode($mesa->numero_mesa) ?>
                    </span>
                    <p class="text-[11px] text-slate-400 mt-1 m-0" id="lblExtratoQtdItens">0 itens pedidos</p>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-slate-800 flex items-center justify-between gap-2">
                <button type="button" onclick="carregarExtratoMesa()" class="text-xs text-slate-400 hover:text-white flex items-center gap-1 transition">
                    <span class="animate-spin text-sm" id="iconReloadExtrato">🔄</span>
                    <span>Atualizar status</span>
                </button>

                <button type="button" onclick="pedirContaAction()" class="px-4 py-2 bg-rose-500 hover:bg-rose-600 text-white font-bold text-xs rounded-xl shadow-lg shadow-rose-500/20 transition active:scale-95">
                    💳 Fechar Conta da Mesa
                </button>
            </div>
        </div>

        <!-- Lista de Itens Já Pedidos -->
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400 m-0">Histórico de Pedidos da Mesa</h3>
                <span class="text-[11px] text-emerald-400 font-bold flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Tempo Real
                </span>
            </div>

            <div id="containerItensExtrato" class="space-y-2.5">
                <div class="text-center py-10 bg-slate-900/50 border border-slate-800 rounded-3xl p-6">
                    <span class="text-3xl">⏳</span>
                    <p class="text-xs text-slate-400 mt-2">Carregando pedidos da mesa...</p>
                </div>
            </div>
        </div>

    </main>

    <!-- ========================================================================= -->
    <!-- ABA 3: CHAT DIRETO COM O GARÇOM (CANAL PRÓPRIO DIRECT HUB) -->
    <!-- ========================================================================= -->
    <main id="abaChat" class="max-w-xl mx-auto px-4 pt-4 space-y-4 hidden pb-28">

        <!-- Card de Status do Canal Próprio -->
        <div class="bg-gradient-to-br from-indigo-950/40 via-slate-900/60 to-slate-900/90 border border-indigo-500/20 rounded-3xl p-4 shadow-xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center text-xl">
                        🧑‍🍳
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-black text-white m-0 flex items-center gap-1.5">
                            <span>Atendimento &bull; Mesa <?= Html::encode($mesa->numero_mesa) ?></span>
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        </h3>
                        <p class="text-[11px] text-slate-400 m-0">Direct Hub &bull; Equipe Online</p>
                    </div>
                </div>

                <div class="flex items-center gap-1">
                    <button type="button" onclick="limparChatMesaAction()" class="p-2 text-slate-400 hover:text-rose-400 rounded-xl bg-slate-800/50 border border-slate-700 text-xs transition" title="Limpar conversa">
                        🗑️
                    </button>
                    <button type="button" onclick="carregarMensagensChat()" class="p-2 text-slate-400 hover:text-white rounded-xl bg-slate-800/50 border border-slate-700 text-xs transition" title="Atualizar mensagens">
                        🔄
                    </button>
                </div>
            </div>

            <!-- Chips de Solicitações Rápidas (1 Toque) -->
            <div class="mt-3 pt-3 border-t border-slate-800/80">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-2">⚡ Pedidos Rápidos (1 Toque):</span>
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none text-xs">
                    <button type="button" onclick="enviarSolicitacaoRapida('🧊 Por favor, trazer mais gelo e limão.')" class="px-3 py-1.5 bg-slate-800/90 hover:bg-indigo-600 hover:text-white text-slate-300 font-bold rounded-xl border border-slate-700 flex-shrink-0 transition active:scale-95">
                        🧊 Gelo e Limão
                    </button>
                    <button type="button" onclick="enviarSolicitacaoRapida('🍽️ Por favor, trazer pratos e talheres extras.')" class="px-3 py-1.5 bg-slate-800/90 hover:bg-indigo-600 hover:text-white text-slate-300 font-bold rounded-xl border border-slate-700 flex-shrink-0 transition active:scale-95">
                        🍽️ Pratos / Talheres
                    </button>
                    <button type="button" onclick="enviarSolicitacaoRapida('🧂 Por favor, trazer azeite, sal e molhos.')" class="px-3 py-1.5 bg-slate-800/90 hover:bg-indigo-600 hover:text-white text-slate-300 font-bold rounded-xl border border-slate-700 flex-shrink-0 transition active:scale-95">
                        🧂 Azeite / Molhos
                    </button>
                    <button type="button" onclick="enviarSolicitacaoRapida('🧻 Por favor, trazer mais guardanapos.')" class="px-3 py-1.5 bg-slate-800/90 hover:bg-indigo-600 hover:text-white text-slate-300 font-bold rounded-xl border border-slate-700 flex-shrink-0 transition active:scale-95">
                        🧻 Guardanapos
                    </button>
                    <button type="button" onclick="enviarSolicitacaoRapida('💳 Por favor, trazer a maquininha de cartão na mesa.')" class="px-3 py-1.5 bg-slate-800/90 hover:bg-indigo-600 hover:text-white text-slate-300 font-bold rounded-xl border border-slate-700 flex-shrink-0 transition active:scale-95">
                        💳 Maquininha
                    </button>
                </div>
            </div>
        </div>

        <!-- Feed de Conversa / Balões de Mensagem -->
        <div id="containerFeedChat" class="space-y-3 min-h-[220px] max-h-[48vh] overflow-y-auto pr-1">
            <div class="text-center py-10 text-slate-500 text-xs">
                <span class="text-3xl block">💬</span>
                <p class="mt-1">Inicie uma conversa ou use os atalhos rápidos acima!</p>
            </div>
        </div>

        <!-- Popover de Emojis -->
        <div id="popoverEmojisChat" class="hidden bg-slate-900 border border-slate-700 rounded-3xl p-3 shadow-2xl space-y-2 mb-2 transition-all">
            <div class="flex items-center justify-between border-b border-slate-800 pb-1.5 px-1">
                <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Emojis Rápidos</span>
                <button type="button" onclick="fecharEmojisChat()" class="text-slate-400 hover:text-white text-xs font-bold p-1">&times;</button>
            </div>
            <div class="grid grid-cols-8 gap-1.5 text-lg p-1 max-h-36 overflow-y-auto">
                <?php
                $emojisLista = [
                    '👍', '😊', '👋', '❤️', '🔥', '🚀', '✅', '🙏',
                    '🍔', '🍕', '🍟', '🌭', '🥪', '🌮', '🍗', '🥩',
                    '🥗', '🍲', '🍣', '🍦', '🍰', '🍫', '🍿', '🍽️',
                    '🥤', '🍺', '🍻', '🍷', '🍸', '🍹', '☕', '🧃',
                    '🧊', '🍋', '🧂', '🧻', '💳', '🧾', '🔔', '⏱️'
                ];
                foreach ($emojisLista as $em):
                ?>
                    <button type="button" onclick="inserirEmojiChat('<?= $em ?>')" class="w-8 h-8 rounded-xl hover:bg-slate-800 flex items-center justify-center transition active:scale-90">
                        <?= $em ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Box de Preview de Imagem Selecionada -->
        <div id="boxPreviewFotoChat" class="hidden bg-slate-950/90 border border-indigo-500/40 rounded-2xl p-2 flex items-center justify-between gap-3 mb-2 shadow-lg">
            <div class="flex items-center gap-2 min-w-0">
                <img id="imgPreviewFotoChat" src="" class="w-12 h-12 rounded-xl object-cover border border-slate-700">
                <div class="min-w-0">
                    <span class="text-xs font-bold text-white block truncate">Foto selecionada</span>
                    <span class="text-[10px] text-indigo-400 font-medium">Pronta para envio</span>
                </div>
            </div>
            <button type="button" onclick="cancelarFotoChat()" class="p-1.5 text-rose-400 hover:text-rose-300 font-black text-xs hover:bg-rose-500/10 rounded-lg transition" title="Remover foto">
                ✕
            </button>
        </div>

        <!-- Barra Inferior Fixa de Digitação & Anexos -->
        <div class="fixed bottom-0 inset-x-0 z-40 bg-slate-900/95 backdrop-blur-xl border-t border-slate-800 p-3 max-w-xl mx-auto">
            <!-- Input File Oculto -->
            <input type="file" id="inputFotoChat" accept="image/*" class="hidden" onchange="selecionarFotoChat(this)">

            <form onsubmit="event.preventDefault(); enviarMensagemChat();" class="flex items-center gap-1.5 m-0">
                <!-- Botão Emoji -->
                <button type="button" onclick="toggleEmojisChat()" class="p-2.5 rounded-2xl bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-300 text-base transition active:scale-95 flex-shrink-0" title="Inserir Emoji">
                    😊
                </button>

                <!-- Botão Foto / Câmera -->
                <button type="button" onclick="document.getElementById('inputFotoChat').click()" class="p-2.5 rounded-2xl bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-300 text-base transition active:scale-95 flex-shrink-0" title="Anexar Foto">
                    📷
                </button>

                <input type="text" id="inputTextoChat" placeholder="Digite uma mensagem..." class="flex-1 px-3.5 py-2.5 bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-xs text-white placeholder-slate-500 transition">
                
                <button type="submit" id="btnEnviarChat" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold text-xs rounded-2xl shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-1 active:scale-95 flex-shrink-0">
                    <span>Enviar</span>
                    <span>🚀</span>
                </button>
            </form>
        </div>

    </main>

    <!-- MODAL LIGHTBOX DE ZOOM DE IMAGEM -->
    <div id="modalZoomImagemChat" class="fixed inset-0 z-50 hidden bg-slate-950/90 backdrop-blur-md flex items-center justify-center p-4" onclick="fecharZoomImagem()">
        <div class="relative max-w-full max-h-full" onclick="event.stopPropagation()">
            <img id="imgZoomChat" src="" class="max-w-full max-h-[85vh] rounded-3xl object-contain shadow-2xl border border-slate-700">
            <button type="button" onclick="fecharZoomImagem()" class="absolute top-3 right-3 bg-slate-900/80 text-white p-2 rounded-full hover:bg-slate-800 transition font-black text-sm">
                &times;
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- BARRA FLUTUANTE DE CARRINHO (BOTTOM BAR) -->
    <!-- ========================================================================= -->
    <div id="barCarrinho" class="fixed bottom-0 inset-x-0 z-40 bg-slate-900/95 backdrop-blur-xl border-t border-slate-800 p-4 hidden shadow-2xl transition transform">
        <div class="max-w-xl mx-auto flex items-center justify-between gap-4">
            <div onclick="abrirModalCarrinho()" class="cursor-pointer">
                <div class="flex items-center gap-1.5">
                    <span class="text-sm">🛒</span>
                    <span id="txtCountCarrinho" class="text-xs font-black text-white">1 item no carrinho</span>
                </div>
                <div id="txtTotalCarrinho" class="text-base font-black text-emerald-400 font-mono">Total: R$ 0,00</div>
            </div>

            <button type="button" onclick="abrirModalCarrinho()" id="btnVerCarrinho" class="px-5 py-3 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs rounded-2xl shadow-lg shadow-emerald-500/25 transition flex items-center gap-1.5 active:scale-95">
                <span>Ver Pedido</span>
                <span>&rarr;</span>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: PERSONALIZAR ITEM (OPCIONAIS + OBSERVAÇÃO + QUANTIDADE) -->
    <!-- ========================================================================= -->
    <div id="modalItemMesa" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-md flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-t-3xl sm:rounded-3xl p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
            
            <div class="flex items-start justify-between gap-3 border-b border-slate-800 pb-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div id="boxModalFotoProduto" class="w-14 h-14 rounded-2xl bg-slate-950 border border-slate-800 flex-shrink-0 flex items-center justify-center overflow-hidden hidden">
                        <img id="imgModalFotoProduto" src="" class="w-full h-full object-cover">
                    </div>
                    <div class="min-w-0">
                        <h3 id="lblModalNomeProduto" class="text-base font-black text-white m-0 truncate">Nome do Produto</h3>
                        <p id="lblModalDescProduto" class="text-xs text-slate-400 mt-0.5 m-0 line-clamp-2"></p>
                    </div>
                </div>
                <button type="button" onclick="fecharModalItemMesa()" class="text-slate-400 hover:text-white text-2xl font-bold p-1 leading-none">&times;</button>
            </div>

            <!-- Opcionais / Adicionais -->
            <div id="boxOpcionaisModal" class="space-y-2 hidden">
                <span class="text-xs font-bold text-slate-300 block uppercase tracking-wider">Adicionais & Opcionais</span>
                <div id="listOpcionaisModal" class="space-y-2 text-xs"></div>
            </div>

            <!-- Observações para a Cozinha -->
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5">Instruções para a Cozinha (Opcional)</label>
                <input type="text" id="txtObsModal" placeholder="Ex: Sem cebola, ponto da carne bem passado, gelo e limão..." class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 focus:border-emerald-500 rounded-xl text-xs text-white placeholder-slate-500">
            </div>

            <!-- Quantidade & Preço Total do Item -->
            <div class="flex items-center justify-between pt-3 border-t border-slate-800">
                <div class="flex items-center space-x-2 bg-slate-950 border border-slate-800 rounded-xl p-1">
                    <button type="button" onclick="alterarQtdModal(-1)" class="w-8 h-8 bg-slate-800 text-white font-black rounded-lg hover:bg-slate-700 transition">-</button>
                    <span id="lblQtdModal" class="font-black text-sm w-6 text-center text-white font-mono">1</span>
                    <button type="button" onclick="alterarQtdModal(1)" class="w-8 h-8 bg-slate-800 text-white font-black rounded-lg hover:bg-slate-700 transition">+</button>
                </div>

                <button type="button" onclick="confirmarAdicionarCarrinho()" class="px-5 py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs rounded-xl shadow-lg shadow-emerald-600/25 transition active:scale-95 flex items-center gap-1.5">
                    <span>Adicionar</span>
                    <span>&bull;</span>
                    <span class="font-mono">R$ <span id="lblTotalItemModal">0,00</span></span>
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: REVISAR & ENVIAR CARRINHO PARA A COZINHA -->
    <!-- ========================================================================= -->
    <div id="modalCarrinho" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-md flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-t-3xl sm:rounded-3xl p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
            
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                    <h3 class="text-base font-black text-white m-0">Confirmar Pedido da Mesa</h3>
                    <p class="text-xs text-slate-400 m-0">Mesa <?= Html::encode($mesa->numero_mesa) ?></p>
                </div>
                <button type="button" onclick="fecharModalCarrinho()" class="text-slate-400 hover:text-white text-2xl font-bold p-1 leading-none">&times;</button>
            </div>

            <!-- Lista de Itens do Carrinho -->
            <div id="listaItensCarrinho" class="space-y-2.5 max-h-60 overflow-y-auto pr-1"></div>

            <!-- Identificação Opcional do Cliente na Mesa -->
            <div class="pt-2 border-t border-slate-800">
                <label class="block text-xs font-bold text-slate-300 mb-1">Seu Nome na Mesa (Opcional)</label>
                <input type="text" id="txtNomeClienteMesa" placeholder="Ex: João, Maria..." class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500">
            </div>

            <div class="pt-3 border-t border-slate-800 flex items-center justify-between">
                <div>
                    <span class="text-xs text-slate-400 block">Total do Pedido:</span>
                    <span id="lblModalCarrinhoTotal" class="text-lg font-black text-emerald-400 font-mono">R$ 0,00</span>
                </div>

                <button type="button" onclick="enviarPedidoCarrinho()" id="btnEnviarPedidoMesa" class="px-6 py-3.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs rounded-2xl shadow-xl shadow-emerald-500/25 transition flex items-center gap-2 active:scale-95">
                    <span>🚀 Enviar Pedido</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- LOGICA JAVASCRIPT & ESTADOS -->
    <!-- ========================================================================= -->
    <script>
    const mesaId = '<?= $mesa->id ?>';
    let produtoAtual = null;
    let qtdAtual = 1;
    let carrinho = [];
    let pollingExtratoInterval = null;

    let pollingChatInterval = null;

    // Alternar Abas (Cardápio vs Meus Pedidos vs Chat Garçom)
    function alternarAba(aba) {
        const tabCardapio = document.getElementById('abaCardapio');
        const tabPedidos = document.getElementById('abaPedidos');
        const tabChat = document.getElementById('abaChat');
        const btnCardapio = document.getElementById('tabBtnCardapio');
        const btnPedidos = document.getElementById('tabBtnPedidos');
        const btnChat = document.getElementById('tabBtnChat');

        // Reset
        tabCardapio.classList.add('hidden');
        tabPedidos.classList.add('hidden');
        tabChat.classList.add('hidden');
        btnCardapio.className = 'py-2.5 px-2 rounded-xl text-xs font-extrabold transition flex items-center justify-center gap-1.5 tab-inactive';
        btnPedidos.className = 'py-2.5 px-2 rounded-xl text-xs font-extrabold transition flex items-center justify-center gap-1.5 tab-inactive';
        btnChat.className = 'py-2.5 px-2 rounded-xl text-xs font-extrabold transition flex items-center justify-center gap-1.5 tab-inactive relative';
        clearInterval(pollingExtratoInterval);
        clearInterval(pollingChatInterval);

        if (aba === 'cardapio') {
            tabCardapio.classList.remove('hidden');
            btnCardapio.className = 'py-2.5 px-2 rounded-xl text-xs font-extrabold transition flex items-center justify-center gap-1.5 tab-active';
        } else if (aba === 'pedidos') {
            tabPedidos.classList.remove('hidden');
            btnPedidos.className = 'py-2.5 px-2 rounded-xl text-xs font-extrabold transition flex items-center justify-center gap-1.5 tab-active';
            carregarExtratoMesa();
            pollingExtratoInterval = setInterval(carregarExtratoMesa, 6000);
        } else if (aba === 'chat') {
            tabChat.classList.remove('hidden');
            btnChat.className = 'py-2.5 px-2 rounded-xl text-xs font-extrabold transition flex items-center justify-center gap-1.5 tab-active relative';
            const badgeNovas = document.getElementById('badgeNovasMensagensChat');
            if (badgeNovas) badgeNovas.classList.add('hidden');
            carregarMensagensChat();
            pollingChatInterval = setInterval(carregarMensagensChat, 4000);
        }
    }

    let fotoSelecionadaChat = null;

    function toggleEmojisChat() {
        const pop = document.getElementById('popoverEmojisChat');
        if (pop) pop.classList.toggle('hidden');
    }

    function fecharEmojisChat() {
        const pop = document.getElementById('popoverEmojisChat');
        if (pop) pop.classList.add('hidden');
    }

    function inserirEmojiChat(emoji) {
        const input = document.getElementById('inputTextoChat');
        if (input) {
            input.value += emoji;
            input.focus();
        }
    }

    function selecionarFotoChat(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            fotoSelecionadaChat = file;
            const reader = new FileReader();
            reader.onload = (e) => {
                const imgPrev = document.getElementById('imgPreviewFotoChat');
                if (imgPrev) imgPrev.src = e.target.result;
                const box = document.getElementById('boxPreviewFotoChat');
                if (box) box.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    }

    function cancelarFotoChat() {
        fotoSelecionadaChat = null;
        const input = document.getElementById('inputFotoChat');
        if (input) input.value = '';
        const box = document.getElementById('boxPreviewFotoChat');
        if (box) box.classList.add('hidden');
    }

    function abrirZoomImagem(url) {
        const modal = document.getElementById('modalZoomImagemChat');
        const img = document.getElementById('imgZoomChat');
        if (modal && img) {
            img.src = url;
            modal.classList.remove('hidden');
        }
    }

    function fecharZoomImagem() {
        const modal = document.getElementById('modalZoomImagemChat');
        if (modal) modal.classList.add('hidden');
    }

    async function carregarMensagensChat() {
        try {
            const resp = await fetch('<?= Url::to(['/vendas/cardapio/mensagens-mesa']) ?>?id=' + mesaId, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await resp.json();
            if (!data.success) return;

            const feed = document.getElementById('containerFeedChat');
            if (data.mensagens.length === 0) {
                feed.innerHTML = `
                    <div class="text-center py-10 text-slate-500 text-xs">
                        <span class="text-3xl block">💬</span>
                        <p class="mt-1 font-bold text-slate-400">Nenhuma mensagem no chat ainda.</p>
                        <p class="text-[11px] text-slate-500">Envie uma mensagem ou use os atalhos rápidos acima!</p>
                    </div>
                `;
                return;
            }

            feed.innerHTML = '';
            data.mensagens.forEach(msg => {
                const isCli = msg.remetente === 'cliente';
                const temMidia = msg.midia_url && msg.midia_url.trim() !== '';

                feed.innerHTML += `
                    <div class="flex flex-col ${isCli ? 'items-end' : 'items-start'}">
                        <div class="max-w-[85%] rounded-2xl p-3.5 ${isCli ? 'bg-indigo-600 text-white rounded-br-none shadow-md shadow-indigo-600/20' : 'bg-slate-900 border border-slate-800 text-slate-200 rounded-bl-none shadow-sm'}">
                            <div class="flex items-center justify-between gap-3 text-[10px] ${isCli ? 'text-indigo-200' : 'text-slate-400'} font-bold mb-1">
                                <span>${msg.autor}</span>
                                <span>${msg.hora}</span>
                            </div>
                            ${temMidia ? `
                                <div class="mb-2">
                                    <img src="${msg.midia_url}" onclick="abrirZoomImagem('${msg.midia_url}')" class="max-w-full max-h-48 rounded-xl object-cover cursor-pointer border border-white/20 hover:opacity-90 shadow transition" alt="Foto">
                                </div>
                            ` : ''}
                            ${msg.texto ? `<p class="text-xs leading-relaxed m-0 font-medium whitespace-pre-wrap">${msg.texto}</p>` : ''}
                        </div>
                    </div>
                `;
            });

            feed.scrollTop = feed.scrollHeight;
        } catch(e) {
            console.error('Erro ao carregar chat:', e);
        }
    }

    async function enviarSolicitacaoRapida(texto) {
        await enviarMensagemChat(texto);
    }

    async function enviarMensagemChat(textoCustom = null) {
        const input = document.getElementById('inputTextoChat');
        const msg = textoCustom || (input ? input.value.trim() : '');
        
        if (!msg && !fotoSelecionadaChat) return;

        const btn = document.getElementById('btnEnviarChat');
        if (btn) btn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('mesa_id', mesaId);
            if (msg) formData.append('mensagem', msg);
            if (fotoSelecionadaChat) formData.append('imagem', fotoSelecionadaChat);
            formData.append('cliente_nome', document.getElementById('txtNomeClienteMesa')?.value || 'Cliente');

            const resp = await fetch('<?= Url::to(['/vendas/cardapio/enviar-mensagem-mesa']) ?>', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData
            });

            const data = await resp.json();
            if (data.success) {
                if (!textoCustom && input) input.value = '';
                cancelarFotoChat();
                fecharEmojisChat();
                await carregarMensagensChat();
            } else {
                alert(data.message || 'Erro ao enviar mensagem.');
            }
        } catch(e) {
            console.error(e);
            alert('Erro de conexão ao enviar mensagem.');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    async function limparChatMesaAction() {
        if (!confirm('Deseja realmente limpar todas as mensagens deste chat?')) return;
        try {
            const formData = new FormData();
            formData.append('mesa_id', mesaId);

            const resp = await fetch('<?= Url::to(['/vendas/cardapio/limpar-chat-mesa']) ?>', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData
            });

            const data = await resp.json();
            if (data.success) {
                await carregarMensagensChat();
            } else {
                alert(data.message || 'Erro ao limpar chat.');
            }
        } catch(e) {
            console.error(e);
            alert('Erro ao limpar histórico do chat.');
        }
    }

    // Filtrar Produtos por Categoria
    function filtrarCategoria(catClass, btn) {
        document.querySelectorAll('.btn-cat').forEach(b => {
            b.className = 'btn-cat px-3.5 py-2 rounded-xl text-xs font-bold transition bg-slate-900 border border-slate-800 text-slate-300 hover:text-white hover:border-slate-700';
        });
        btn.className = 'btn-cat px-3.5 py-2 rounded-xl text-xs font-extrabold transition bg-emerald-600 text-white shadow-sm shadow-emerald-600/30';

        document.querySelectorAll('.card-produto').forEach(c => {
            if (catClass === 'todas' || c.classList.contains(catClass)) {
                c.classList.remove('hidden');
            } else {
                c.classList.add('hidden');
            }
        });
    }

    // Filtrar por Texto de Busca
    function filtrarCardapio() {
        const termo = document.getElementById('inputBusca').value.toLowerCase().trim();
        document.querySelectorAll('.card-produto').forEach(c => {
            const nome = c.getAttribute('data-nome') || '';
            const desc = c.getAttribute('data-desc') || '';
            if (nome.includes(termo) || desc.includes(termo)) {
                c.classList.remove('hidden');
            } else {
                c.classList.add('hidden');
            }
        });
    }

    // Modal de Item
    function abrirModalItem(p) {
        produtoAtual = p;
        qtdAtual = 1;
        document.getElementById('lblModalNomeProduto').innerText = p.nome;
        document.getElementById('lblModalDescProduto').innerText = p.descricao || '';
        document.getElementById('txtObsModal').value = '';
        document.getElementById('lblQtdModal').innerText = 1;

        const boxFoto = document.getElementById('boxModalFotoProduto');
        const imgFoto = document.getElementById('imgModalFotoProduto');
        if (p.foto && p.foto.trim() !== '') {
            imgFoto.src = p.foto;
            boxFoto.classList.remove('hidden');
        } else {
            boxFoto.classList.add('hidden');
        }

        const boxOps = document.getElementById('boxOpcionaisModal');
        const listOps = document.getElementById('listOpcionaisModal');
        listOps.innerHTML = '';

        if (p.opcionais && p.opcionais.length > 0) {
            boxOps.classList.remove('hidden');
            p.opcionais.forEach(op => {
                listOps.innerHTML += `
                    <label class="flex items-center justify-between bg-slate-950 border border-slate-800 p-3 rounded-2xl cursor-pointer hover:border-slate-700 transition">
                        <span class="font-bold text-slate-200 flex items-center">
                            <input type="checkbox" class="chk-modal-op w-4 h-4 text-emerald-500 rounded bg-slate-900 border-slate-700 mr-2.5 focus:ring-0" data-id="${op.id}" data-nome="${op.nome}" data-valor="${op.valor_adicional}" onchange="recalcularModal()">
                            ${op.nome}
                        </span>
                        <span class="font-black text-emerald-400 font-mono">+R$ ${op.valor_formatado}</span>
                    </label>
                `;
            });
        } else {
            boxOps.classList.add('hidden');
        }

        recalcularModal();
        document.getElementById('modalItemMesa').classList.remove('hidden');
    }

    function fecharModalItemMesa() {
        document.getElementById('modalItemMesa').classList.add('hidden');
    }

    function alterarQtdModal(delta) {
        qtdAtual += delta;
        if (qtdAtual < 1) qtdAtual = 1;
        document.getElementById('lblQtdModal').innerText = qtdAtual;
        recalcularModal();
    }

    function recalcularModal() {
        if (!produtoAtual) return;
        let base = produtoAtual.preco;
        document.querySelectorAll('.chk-modal-op:checked').forEach(c => {
            base += parseFloat(c.getAttribute('data-valor')) || 0;
        });
        const total = base * qtdAtual;
        document.getElementById('lblTotalItemModal').innerText = total.toFixed(2).replace('.', ',');
    }

    function confirmarAdicionarCarrinho() {
        let valAdicional = 0;
        const opsNomes = [];
        document.querySelectorAll('.chk-modal-op:checked').forEach(c => {
            valAdicional += parseFloat(c.getAttribute('data-valor')) || 0;
            opsNomes.push(c.getAttribute('data-nome'));
        });

        let obs = document.getElementById('txtObsModal').value.trim();
        if (opsNomes.length > 0) {
            obs = 'Adicionais: ' + opsNomes.join(', ') + (obs ? ' | ' + obs : '');
        }

        carrinho.push({
            produto_id: produtoAtual.id,
            nome: produtoAtual.nome,
            quantidade: qtdAtual,
            valor_unitario: produtoAtual.preco + valAdicional,
            valor_adicional: valAdicional,
            observacoes: obs,
        });

        fecharModalItemMesa();
        atualizarBarCarrinho();
    }

    function atualizarBarCarrinho() {
        const bar = document.getElementById('barCarrinho');
        if (carrinho.length === 0) {
            bar.classList.add('hidden');
            return;
        }

        bar.classList.remove('hidden');
        let total = 0;
        let qtdTotal = 0;
        carrinho.forEach(item => {
            total += item.valor_unitario * item.quantidade;
            qtdTotal += item.quantidade;
        });

        document.getElementById('txtCountCarrinho').innerText = `${qtdTotal} item(ns) no carrinho`;
        document.getElementById('txtTotalCarrinho').innerText = `Total: R$ ${total.toFixed(2).replace('.', ',')}`;
    }

    function abrirModalCarrinho() {
        const list = document.getElementById('listaItensCarrinho');
        list.innerHTML = '';
        let total = 0;

        carrinho.forEach((item, idx) => {
            const sub = item.valor_unitario * item.quantidade;
            total += sub;
            list.innerHTML += `
                <div class="bg-slate-950 border border-slate-800 rounded-2xl p-3 flex items-center justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="font-extrabold text-white text-xs truncate">${item.quantidade}x ${item.nome}</div>
                        ${item.observacoes ? `<div class="text-[10px] text-slate-400 truncate mt-0.5">${item.observacoes}</div>` : ''}
                        <div class="text-xs font-black text-emerald-400 font-mono mt-1">R$ ${sub.toFixed(2).replace('.', ',')}</div>
                    </div>
                    <button type="button" onclick="removerItemCarrinho(${idx})" class="p-2 text-rose-400 hover:text-rose-300 text-xs font-bold rounded-lg hover:bg-rose-500/10 transition">
                        🗑️
                    </button>
                </div>
            `;
        });

        document.getElementById('lblModalCarrinhoTotal').innerText = `R$ ${total.toFixed(2).replace('.', ',')}`;
        document.getElementById('modalCarrinho').classList.remove('hidden');
    }

    function fecharModalCarrinho() {
        document.getElementById('modalCarrinho').classList.add('hidden');
    }

    function removerItemCarrinho(idx) {
        carrinho.splice(idx, 1);
        atualizarBarCarrinho();
        if (carrinho.length === 0) {
            fecharModalCarrinho();
        } else {
            abrirModalCarrinho();
        }
    }

    // Enviar Pedido para a Cozinha
    async function enviarPedidoCarrinho() {
        if (carrinho.length === 0) return;

        const btn = document.getElementById('btnEnviarPedidoMesa');
        btn.disabled = true;
        btn.innerHTML = '<span>⏳ Enviando...</span>';

        try {
            const formData = new FormData();
            formData.append('mesa_id', mesaId);
            formData.append('itens', JSON.stringify(carrinho));
            formData.append('cliente_nome', document.getElementById('txtNomeClienteMesa').value);

            const csrfParam = document.querySelector('meta[name="csrf-param"]')?.content;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfParam && csrfToken) {
                formData.append(csrfParam, csrfToken);
            }

            const resp = await fetch('<?= Url::to(['/vendas/cardapio/fazer-pedido-mesa']) ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
                },
                body: formData
            });

            const data = await resp.json();
            if (data.success) {
                carrinho = [];
                atualizarBarCarrinho();
                fecharModalCarrinho();
                alert('🚀 ' + data.message);
                alternarAba('pedidos'); // Vai direto para a aba de acompanhamento
            } else {
                alert('Atenção: ' + data.message);
            }
        } catch(e) {
            console.error(e);
            alert('Erro ao enviar pedido para a mesa.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span>🚀 Enviar Pedido</span>';
        }
    }

    // Chamar Garçom
    async function chamarGarcomAction() {
        const btn = document.getElementById('btnTopGarcom');
        btn.disabled = true;
        try {
            const formData = new FormData();
            formData.append('mesa_id', mesaId);
            const csrfParam = document.querySelector('meta[name="csrf-param"]')?.content;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfParam && csrfToken) formData.append(csrfParam, csrfToken);

            const resp = await fetch('<?= Url::to(['/vendas/cardapio/chamar-garcom']) ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: formData
            });
            const data = await resp.json();
            alert('🔔 ' + (data.message || 'Garçom chamado! Atendente a caminho.'));
        } catch(e) {
            console.error(e);
            alert('Erro ao chamar garçom.');
        } finally {
            btn.disabled = false;
        }
    }

    // Pedir Conta
    async function pedirContaAction() {
        const btn = document.getElementById('btnTopConta');
        if (!confirm('Deseja realmente solicitar o fechamento da conta desta mesa?')) return;
        btn.disabled = true;
        try {
            const formData = new FormData();
            formData.append('mesa_id', mesaId);
            const csrfParam = document.querySelector('meta[name="csrf-param"]')?.content;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfParam && csrfToken) formData.append(csrfParam, csrfToken);

            const resp = await fetch('<?= Url::to(['/vendas/cardapio/pedir-conta']) ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: formData
            });
            const data = await resp.json();
            alert('🧾 ' + (data.message || 'Conta solicitada! O garçom trará em instantes.'));
        } catch(e) {
            console.error(e);
            alert('Erro ao pedir conta.');
        } finally {
            btn.disabled = false;
        }
    }

    // Carregar Extrato e Status de Pedidos em Tempo Real
    async function carregarExtratoMesa() {
        const reloadIcon = document.getElementById('iconReloadExtrato');
        if (reloadIcon) reloadIcon.classList.add('animate-spin');

        try {
            const resp = await fetch('<?= Url::to(['/vendas/cardapio/extrato-mesa']) ?>?id=' + mesaId, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await resp.json();
            if (!data.success) return;

            document.getElementById('lblExtratoTotal').innerText = `R$ ${data.total_formatado}`;
            document.getElementById('lblExtratoQtdItens').innerText = `${data.count} itens pedidos`;

            const badgeTop = document.getElementById('badgeContagemExtrato');
            if (data.count > 0) {
                badgeTop.innerText = data.count;
                badgeTop.classList.remove('hidden');
            } else {
                badgeTop.classList.add('hidden');
            }

            const container = document.getElementById('containerItensExtrato');
            if (data.itens.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-10 bg-slate-900/50 border border-slate-800 rounded-3xl p-6">
                        <span class="text-3xl">🍽️</span>
                        <h4 class="text-sm font-bold text-white mt-2">Nenhum pedido realizado ainda</h4>
                        <p class="text-xs text-slate-400 mt-1">Acesse a aba <strong>Cardápio</strong> e faça seu primeiro pedido!</p>
                        <button type="button" onclick="alternarAba('cardapio')" class="mt-3 px-4 py-2 bg-emerald-600 text-white font-bold text-xs rounded-xl shadow">
                            Ver Cardápio
                        </button>
                    </div>
                `;
                return;
            }

            container.innerHTML = '';
            data.itens.forEach(item => {
                container.innerHTML += `
                    <div class="bg-slate-900/90 border border-slate-800/90 rounded-2xl p-3.5 flex items-start gap-3 shadow-sm">
                        <div class="w-12 h-12 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-center text-xl flex-shrink-0">
                            ${item.foto ? `<img src="${item.foto}" class="w-full h-full object-cover rounded-xl">` : '🍲'}
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <h4 class="text-xs font-black text-white truncate m-0">${item.quantidade}x ${item.nome}</h4>
                                <span class="font-mono font-black text-xs text-emerald-400 flex-shrink-0">R$ ${item.subtotal_formatado}</span>
                            </div>

                            ${item.observacoes ? `<p class="text-[11px] text-slate-400 mt-0.5 line-clamp-2 m-0">${item.observacoes}</p>` : ''}

                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-800/60">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border ${item.status_badge}">
                                        <span>${item.status_icon}</span>
                                        <span>${item.status_label}</span>
                                    </span>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border ${item.destino_badge}">
                                        <span>${item.destino_label}</span>
                                    </span>
                                </div>
                                <span class="text-[10px] text-slate-500 font-mono">${item.data_pedido}</span>
                            </div>
                        </div>
                    </div>
                `;
            });

        } catch(e) {
            console.error(e);
        } finally {
            if (reloadIcon) reloadIcon.classList.remove('animate-spin');
        }
    }

    // Carrega contagem inicial em background
    document.addEventListener('DOMContentLoaded', () => {
        carregarExtratoMesa();
    });
    </script>
</body>
</html>
