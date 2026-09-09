<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = '⚡ Venda Expressa (Encarte & Catálogo)';
$this->params['breadcrumbs'][] = ['label' => 'Vendas', 'url' => ['/vendas/venda/index']];
$this->params['breadcrumbs'][] = $this->title;

$pixChaveConfig = $lojaConfig ? $lojaConfig->pix_chave : '';
$pixNomeConfig = $lojaConfig ? $lojaConfig->pix_nome : '';
$pixCidadeConfig = $lojaConfig ? $lojaConfig->pix_cidade : '';
?>

<div class="min-h-screen bg-slate-900 text-slate-100 py-6 px-3 sm:px-6">
    
    <div class="max-w-6xl mx-auto space-y-6">

        <!-- Topo & Indicadores Relâmpago de Vendas do Dia -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-slate-800/90 border border-slate-700 p-5 rounded-3xl shadow-2xl backdrop-blur-md">
            <div class="flex items-center gap-3">
                <a href="<?= Url::to(['/vendas/produto/index']) ?>" class="p-2.5 bg-slate-900 hover:bg-slate-700 text-slate-300 hover:text-white rounded-2xl border border-slate-700 transition flex items-center gap-1.5 text-xs font-bold shadow-md group">
                    <svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span class="hidden sm:inline">Voltar aos Produtos</span>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">⚡</span>
                        <h1 class="text-2xl font-black text-white tracking-tight">Venda Expressa</h1>
                        <span class="bg-amber-400/20 text-amber-300 border border-amber-400/30 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">Modo Encarte</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">Registre suas vendas do WhatsApp ou balcão com cadastro de clientes para Evolution API</p>
                </div>
            </div>

            <!-- Resumo Financeiro de Hoje -->
            <div class="grid grid-cols-3 gap-3 w-full md:w-auto">
                <div class="bg-slate-900/80 p-3 rounded-2xl border border-slate-700 text-center">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Vendas Hoje</div>
                    <div class="text-lg font-montserrat font-black text-amber-400">R$ <span id="resumoValor"><?= $resumoHoje['valor_total'] ?></span></div>
                </div>

                <div class="bg-slate-900/80 p-3 rounded-2xl border border-slate-700 text-center">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Total Vendas</div>
                    <div class="text-lg font-montserrat font-black text-emerald-400"><span id="resumoQtd"><?= $resumoHoje['total_vendas'] ?></span> un</div>
                </div>

                <div class="bg-slate-900/80 p-3 rounded-2xl border border-slate-700 text-center">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Top Item Hoje</div>
                    <div id="resumoTop" class="text-xs font-bold text-slate-200 truncate max-w-[120px]" title="<?= Html::encode($resumoHoje['top_produto']) ?>"><?= Html::encode($resumoHoje['top_produto']) ?></div>
                </div>
            </div>
        </div>

        <!-- Área Principal de Registro da Venda -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Coluna Esquerda: Seletor de Produtos e Lista da Venda -->
            <div class="lg:col-span-8 space-y-4">
                
                <!-- Card Busca Rápida por Digitação de Produto -->
                <div class="bg-slate-800 border border-slate-700 p-4 rounded-3xl shadow-xl space-y-2 relative" id="containerBuscaProduto">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-amber-400 uppercase tracking-wider">🔍 Digitar Nome ou Marca do Produto</label>
                        <span class="text-[10px] text-slate-400 font-semibold">Pressione Enter ou clique para incluir</span>
                    </div>
                    
                    <div class="relative">
                        <input type="text" id="inputBuscaProduto" 
                               placeholder="🔍 Digite para consultar (ex: Arroz, Feijão, Nestlé)..." 
                               autocomplete="off"
                               oninput="filtrarProdutosBusca(this.value)"
                               onfocus="filtrarProdutosBusca(this.value)"
                               onkeydown="tratarTeclasBusca(event)"
                               class="w-full bg-slate-900 border border-slate-700 text-white rounded-2xl py-3.5 pl-4 pr-10 text-sm font-semibold focus:ring-2 focus:ring-amber-400 focus:outline-none placeholder-slate-500 shadow-inner">
                        
                        <button type="button" id="btnLimparBusca" onclick="limparBuscaProduto()" class="absolute right-3.5 top-3.5 text-slate-400 hover:text-white hidden transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Dropdown de Resultados da Busca -->
                    <div id="dropdownBuscaResultados" class="absolute left-4 right-4 top-full mt-1.5 bg-slate-800/95 border border-slate-600 rounded-2xl shadow-2xl z-50 max-h-72 overflow-y-auto hidden divide-y divide-slate-700/60 backdrop-blur-lg">
                        <!-- Renderizado dinamicamente via JS -->
                    </div>
                </div>

                <!-- Tabela de Itens Selecionados da Venda -->
                <div class="bg-slate-800 border border-slate-700 p-4 rounded-3xl shadow-xl space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-700 pb-3">
                        <h3 class="font-extrabold text-sm text-white flex items-center gap-2">
                            🛒 Itens da Venda
                            <span id="badgeCountItens" class="bg-amber-400 text-slate-900 text-xs font-black px-2 py-0.5 rounded-full">0</span>
                        </h3>
                        <button type="button" onclick="limparItensVenda()" class="text-xs font-bold text-slate-400 hover:text-red-400 transition">Esvaziar Itens</button>
                    </div>

                    <!-- Lista de Itens -->
                    <div id="listaItensVenda" class="space-y-2 max-h-80 overflow-y-auto pr-1">
                        <div id="emptyStateVenda" class="text-center py-10 text-slate-400 space-y-2">
                            <span class="text-4xl block">🛍️</span>
                            <p class="text-xs font-bold">Nenhum produto adicionado ainda.</p>
                            <p class="text-[10px]">Digite no campo acima para pesquisar e adicionar em 1 clique!</p>
                        </div>
                    </div>
                </div>

                <!-- Seção Dados do Cliente (Cadastro para Disparos Evolution API) -->
                <div class="bg-slate-800 border border-slate-700 p-4 rounded-3xl shadow-xl space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-700 pb-2">
                        <h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider flex items-center gap-2">
                            <span>👤 Cliente (Disparos WhatsApp / Evolution API)</span>
                            <span id="badgeClienteObrigatorioFiado" class="hidden text-[10px] font-extrabold px-2 py-0.5 rounded-md bg-amber-500 text-slate-950 uppercase tracking-normal animate-pulse">Obrigatório no Boleto/Fiado</span>
                        </h3>
                        <span class="text-[10px] text-slate-400 font-medium">Cadastra e busca automaticamente</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 relative" id="containerInputsCliente">
                        <!-- WhatsApp -->
                        <div class="relative">
                            <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1">📱 WhatsApp / Fone</label>
                            <input type="text" id="clienteWhatsapp" placeholder="(81) 99999-9999" oninput="aplicarMascaraTelefone(this); buscarClientesAutocomplete(this.value)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs font-semibold text-white focus:outline-none focus:border-amber-400">
                        </div>

                        <!-- Nome Completo -->
                        <div class="relative">
                            <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1">👤 Nome Completo</label>
                            <input type="text" id="clienteNome" placeholder="Ex: Maria Silva" oninput="buscarClientesAutocomplete(this.value)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs font-semibold text-white focus:outline-none focus:border-amber-400">
                        </div>

                        <!-- CPF -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1">📄 CPF (Opcional)</label>
                            <input type="text" id="clienteCpf" placeholder="000.000.000-00" oninput="aplicarMascaraCpf(this)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs font-semibold text-white focus:outline-none focus:border-amber-400">
                        </div>

                        <!-- Dropdown de Sugestões de Clientes Cadastrados -->
                        <div id="dropdownClientesSugestoes" class="hidden absolute top-full left-0 right-0 z-50 mt-1 bg-slate-900 border border-amber-400/60 rounded-2xl shadow-2xl overflow-hidden max-h-56 overflow-y-auto">
                        </div>
                    </div>
                </div>

            </div>

            <!-- Coluna Direita: Checkout Relâmpago, Desconto/Acréscimo e Pagamento -->
            <div class="lg:col-span-4 space-y-4">
                
                <div class="bg-slate-800 border border-slate-700 p-5 rounded-3xl shadow-xl space-y-4">
                    
                    <h3 class="font-extrabold text-sm text-white border-b border-slate-700 pb-2">💳 Finalização Relâmpago</h3>

                    <!-- Ajustes Finos: Desconto Geral e Acréscimo Geral -->
                    <div class="grid grid-cols-2 gap-2 bg-slate-900/60 p-3 rounded-2xl border border-slate-700">
                        <!-- Desconto Geral -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-[10px] font-bold text-rose-400 uppercase">🏷️ Desconto</label>
                                <select id="descontoTipo" onchange="limparEMascarar('descontoGeral'); renderizarItensVenda()" class="bg-slate-800 text-[10px] font-bold text-rose-300 rounded px-1 py-0.5 border border-slate-700">
                                    <option value="VALOR">R$</option>
                                    <option value="PERCENTUAL">%</option>
                                </select>
                            </div>
                            <input type="text" id="descontoGeral" placeholder="0,00" oninput="aplicarMascaraMoedaInput(this, 'descontoTipo'); renderizarItensVenda()" class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-xl text-xs font-bold text-rose-400 focus:outline-none text-right">
                        </div>

                        <!-- Acréscimo Geral -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-[10px] font-bold text-blue-400 uppercase">➕ Acréscimo</label>
                                <select id="acrescimoTipo" onchange="limparEMascarar('acrescimoGeral'); renderizarItensVenda()" class="bg-slate-800 text-[10px] font-bold text-blue-300 rounded px-1 py-0.5 border border-slate-700">
                                    <option value="VALOR">R$</option>
                                    <option value="PERCENTUAL">%</option>
                                </select>
                            </div>
                            <input type="text" id="acrescimoGeral" placeholder="0,00" oninput="aplicarMascaraMoedaInput(this, 'acrescimoTipo'); renderizarItensVenda()" class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-xl text-xs font-bold text-blue-400 focus:outline-none text-right">
                        </div>
                    </div>

                    <!-- Totalizador com Subtotal, Desconto e Acréscimo -->
                    <div class="bg-slate-900 p-4 rounded-2xl border border-slate-700 space-y-1.5">
                        <div class="flex items-center justify-between text-xs text-slate-400">
                            <span>Subtotal Itens:</span>
                            <span class="font-bold text-white">R$ <span id="displaySubtotal">0,00</span></span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-rose-400 hidden" id="rowDisplayDesconto">
                            <span>(-) Desconto Geral:</span>
                            <span class="font-bold">- R$ <span id="displayDesconto">0,00</span></span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-blue-400 hidden" id="rowDisplayAcrescimo">
                            <span>(+) Acréscimo Geral:</span>
                            <span class="font-bold">+ R$ <span id="displayAcrescimo">0,00</span></span>
                        </div>
                        <div class="border-t border-slate-800 pt-2 flex items-center justify-between">
                            <span class="text-xs uppercase font-extrabold text-slate-300">Total a Receber:</span>
                            <span class="text-2xl font-montserrat font-black text-emerald-400">R$ <span id="displayTotalFinal">0,00</span></span>
                        </div>
                    </div>

                    <!-- Toggle: Múltiplas Formas de Pagamento -->
                    <div class="flex items-center justify-between bg-slate-900/60 border border-slate-700 p-3 rounded-2xl">
                        <label class="text-xs font-bold text-slate-300 cursor-pointer select-none" for="usar-multiplos-pagamentos">💳 Dividir em várias formas de pagamento</label>
                        <input type="checkbox" id="usar-multiplos-pagamentos" onchange="toggleMultiplosPagamentos()" class="w-5 h-5 accent-amber-400 cursor-pointer">
                    </div>

                    <!-- Seleção Única de Pagamento (Chips 1-Clique) -->
                    <div id="container-pagamento-unico" class="space-y-3">
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Forma de Pagamento</label>
                        <?php
                            // Identifica a forma padrão prioritária (Dinheiro)
                            $fpPadrao = null;
                            foreach ($formasPagamento as $fItem) {
                                if ($fItem->tipo === \app\modules\vendas\models\FormaPagamento::TIPO_DINHEIRO || mb_stripos($fItem->nome, 'dinheiro') !== false) {
                                    $fpPadrao = $fItem;
                                    break;
                                }
                            }
                            if (!$fpPadrao && !empty($formasPagamento)) {
                                foreach ($formasPagamento as $fItem) {
                                    if ($fItem->tipo !== \app\modules\vendas\models\FormaPagamento::TIPO_BOLETO && mb_stripos($fItem->nome, 'boleto') === false && mb_stripos($fItem->nome, 'fiado') === false) {
                                        $fpPadrao = $fItem;
                                        break;
                                    }
                                }
                                if (!$fpPadrao) {
                                    $fpPadrao = $formasPagamento[0];
                                }
                            }
                        ?>
                        <div class="grid grid-cols-2 gap-2">
                            <?php foreach ($formasPagamento as $index => $fp): 
                                $nomeExibicao = $fp->nome;
                                $isBoleto = (mb_stripos($nomeExibicao, 'boleto') !== false || mb_stripos($nomeExibicao, 'fiado') !== false || $fp->tipo === 'BOLETO');
                                if ($isBoleto) {
                                    $nomeExibicao = '📄 Boleto / Fiado';
                                }
                                $isAtivoInicial = ($fpPadrao && $fp->id === $fpPadrao->id);
                                $isMercadoPago = (mb_stripos($fp->nome, 'mercado') !== false || $fp->tipo === 'MERCADOPAGO');
                                $mpDesativado = ($isMercadoPago && !$temMercadoPago);
                            ?>
                                <?php if ($mpDesativado): ?>
                                    <button type="button" onclick="alert('O Mercado Pago não está conectado nas configurações da sua loja. Conecte sua conta do Mercado Pago para ativar este canal de pagamento.')" 
                                             class="p-2.5 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1 bg-slate-900/40 text-slate-500 border-slate-800 opacity-60 cursor-not-allowed" 
                                             title="Requer Integração Mercado Pago">
                                        <span><?= Html::encode($nomeExibicao) ?> <span class="text-[9px] text-rose-400 font-extrabold">(Requer MP)</span></span>
                                    </button>
                                <?php else: ?>
                                    <button type="button" onclick="selecionarFormaPagamento('<?= $fp->id ?>', this)" 
                                             class="btn-forma-pagamento p-2.5 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5 <?= $isAtivoInicial ? 'bg-amber-400 text-slate-900 border-amber-300 shadow-md' : 'bg-slate-900 text-slate-300 border-slate-700 hover:bg-slate-700' ?>" 
                                             data-id="<?= $fp->id ?>"
                                             data-tipo="<?= $fp->tipo ?>"
                                             data-nome="<?= Html::encode($nomeExibicao) ?>">
                                        <span><?= Html::encode($nomeExibicao) ?></span>
                                    </button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <!-- Seletor de Vencimento para Boleto / Fiado (Venda a Prazo) -->
                        <div id="container-vencimento-fiado" class="hidden bg-amber-500/10 border border-amber-500/30 p-3 rounded-2xl space-y-2 transition-all">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-black text-amber-400 uppercase tracking-wide flex items-center gap-1.5">
                                    <span>📅 Vencimento / Promessa de Pagamento</span>
                                </label>
                                <span class="text-[10px] text-amber-300/80 font-bold bg-amber-500/20 px-2 py-0.5 rounded-full">Contas a Receber</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="date" id="dataVencimentoFiado" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" onchange="checarExibicaoVencimentoFiado()" class="w-full px-3 py-2 bg-slate-900 border border-amber-500/50 rounded-xl text-xs font-bold text-white focus:outline-none focus:border-amber-400">
                            </div>
                            <!-- Chips de Vencimento Rápido -->
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-[10px] text-slate-400 font-semibold">Atalhos:</span>
                                <button type="button" onclick="definirVencimentoDias(7)" id="chip-venc-7" class="chip-vencimento px-2 py-0.5 rounded-lg bg-slate-800 hover:bg-amber-500/20 text-slate-300 hover:text-amber-300 border border-slate-700 text-[10px] font-bold transition">+7 dias</button>
                                <button type="button" onclick="definirVencimentoDias(15)" id="chip-venc-15" class="chip-vencimento px-2 py-0.5 rounded-lg bg-slate-800 hover:bg-amber-500/20 text-slate-300 hover:text-amber-300 border border-slate-700 text-[10px] font-bold transition">+15 dias</button>
                                <button type="button" onclick="definirVencimentoDias(30)" id="chip-venc-30" class="chip-vencimento px-2 py-0.5 rounded-lg bg-amber-400 text-slate-950 border border-amber-300 text-[10px] font-black transition">+30 dias</button>
                                <button type="button" onclick="definirVencimentoDias(45)" id="chip-venc-45" class="chip-vencimento px-2 py-0.5 rounded-lg bg-slate-800 hover:bg-amber-500/20 text-slate-300 hover:text-amber-300 border border-slate-700 text-[10px] font-bold transition">+45 dias</button>
                                <button type="button" onclick="definirVencimentoDias(60)" id="chip-venc-60" class="chip-vencimento px-2 py-0.5 rounded-lg bg-slate-800 hover:bg-amber-500/20 text-slate-300 hover:text-amber-300 border border-slate-700 text-[10px] font-bold transition">+60 dias</button>
                            </div>
                            <p class="text-[10px] text-slate-400 leading-tight">
                                ℹ️ Esta venda entrará como pendente em <strong>Contas a Receber</strong> e poderá ser baixada quando o cliente quitar o débito.
                            </p>
                        </div>
                    </div>

                    <!-- Divisão em Múltiplas Formas de Pagamento -->
                    <div id="container-multiplos-pagamentos" class="hidden space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-300 uppercase">Meios de Pagamento</label>
                            <button type="button" onclick="adicionarLinhaPagamentoMultiplo()" class="text-[11px] font-bold text-amber-400 hover:text-amber-300 transition">+ Adicionar forma</button>
                        </div>

                        <div id="lista-pagamentos-multiplos" class="space-y-2">
                            <!-- Linhas de pagamento renderizadas dinamicamente -->
                        </div>

                        <!-- Resumo da divisão -->
                        <div class="bg-slate-900/70 border border-slate-700 p-3 rounded-2xl space-y-1.5 text-xs">
                            <div class="flex items-center justify-between text-slate-400">
                                <span>Total da venda:</span>
                                <span class="font-bold text-white" id="multiplo-total-venda">R$ 0,00</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-400">
                                <span>Total informado:</span>
                                <span class="font-bold text-emerald-400" id="multiplo-total-informado">R$ 0,00</span>
                            </div>
                            <div class="flex items-center justify-between font-bold">
                                <span id="label-multiplo-restante" class="text-slate-400">Restante:</span>
                                <span id="valor-multiplo-restante" class="text-rose-400">R$ 0,00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Observação Opcional -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Observações (Opcional)</label>
                        <input type="text" id="inputObservacoes" placeholder="Ex: Cliente do WhatsApp..." class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-amber-400">
                    </div>

                    <!-- Botão de Efetivação Relâmpago -->
                    <button type="button" id="btnEfetivarVenda" onclick="iniciarProcessoEfetivacao()" class="w-full py-4 bg-gradient-to-r from-emerald-500 via-green-500 to-emerald-600 hover:from-emerald-600 hover:to-green-700 text-white font-montserrat font-black text-base rounded-2xl shadow-xl transition transform active:scale-95 flex items-center justify-center gap-2 border border-white/20">
                        <span>⚡ Efetivar Venda (R$ <span id="totalFinalBtn">0,00</span>)</span>
                    </button>

                </div>

            </div>

        </div>

    </div>
</div>

<!-- Modal PIX Estático com QR Code e Copia e Cola -->
<div id="modalPixEstatico" class="fixed inset-0 z-[150] hidden bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-slate-900 border border-slate-700 rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-5 text-white relative">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <span class="text-2xl">📱</span>
                <div>
                    <h3 class="font-extrabold text-base text-amber-400">Pagamento via PIX</h3>
                    <p class="text-[10px] text-slate-400">Apresente o QR Code ou copie a chave abaixo</p>
                </div>
            </div>
            <button type="button" onclick="fecharModalPixEstatico()" class="text-slate-400 hover:text-white p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Valor Total -->
        <div class="bg-slate-800/80 border border-slate-700 p-3 rounded-2xl text-center space-y-0.5">
            <div class="text-[10px] uppercase font-bold text-slate-400">Valor a Pagar</div>
            <div class="text-2xl font-montserrat font-black text-emerald-400">R$ <span id="pixModalValor">0,00</span></div>
        </div>

        <!-- QR Code Visual -->
        <div class="bg-white p-4 rounded-2xl shadow-inner flex items-center justify-center min-h-[220px]" id="pixQrCodeContainer">
            <div class="text-slate-500 text-xs font-bold py-8">Gerando QR Code PIX...</div>
        </div>

        <!-- Código Copia e Cola -->
        <div class="space-y-1.5">
            <label class="block text-[10px] font-bold text-slate-400 uppercase">PIX Copia e Cola</label>
            <div class="relative">
                <textarea id="pixCodigoCopiaCola" readonly rows="2" class="w-full bg-slate-950 border border-slate-800 text-slate-300 rounded-xl p-2.5 text-[10px] font-mono select-all focus:outline-none resize-none"></textarea>
            </div>
            <button type="button" onclick="copiarCodigoPixEstatico()" id="btnCopiarPix" class="w-full py-2.5 bg-slate-800 hover:bg-slate-700 text-amber-400 font-bold text-xs rounded-xl border border-slate-700 transition flex items-center justify-center gap-1.5">
                <span>📋 Copiar Código PIX</span>
            </button>
        </div>

        <!-- Ações do Modal -->
        <div class="space-y-2 pt-2 border-t border-slate-800">
            <button type="button" onclick="confirmarEEfetivarVendaPix()" class="w-full py-3.5 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-montserrat font-black text-sm rounded-xl shadow-lg transition flex items-center justify-center gap-2 border border-white/20">
                <span>✅ Confirmar Recebimento &amp; Efetivar Venda</span>
            </button>
            <button type="button" onclick="fecharModalPixEstatico()" class="w-full py-2.5 text-xs font-bold text-slate-400 hover:text-white transition">
                Cancelar
            </button>
        </div>
    </div>
</div>

<!-- Modal Mercado Pago Integrado (Point Maquininha & Pix Dinâmico) -->
<div id="modalMercadoPagoPDV" class="fixed inset-0 z-[150] hidden bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-slate-900 border border-cyan-500/40 rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-4 text-white relative">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <span class="text-2xl">💳</span>
                <div>
                    <h3 class="font-extrabold text-base text-cyan-400">Mercado Pago Integrado</h3>
                    <p class="text-[10px] text-slate-400">Cobrança no balcão com baixa automática</p>
                </div>
            </div>
            <button type="button" onclick="fecharModalMercadoPagoPDV()" class="text-slate-400 hover:text-white p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Valor a Pagar -->
        <div class="bg-slate-800/80 border border-slate-700 p-3 rounded-2xl text-center space-y-0.5">
            <div class="text-[10px] uppercase font-bold text-slate-400">Total a Cobrar</div>
            <div class="text-2xl font-montserrat font-black text-cyan-400">R$ <span id="mpModalValor">0,00</span></div>
        </div>

        <!-- Abas: Maquininha Point / Pix Dinâmico -->
        <div class="flex bg-slate-950 p-1 rounded-xl border border-slate-800 gap-1 text-xs font-bold">
            <button type="button" id="tabBtnPoint" onclick="trocarAbaMp('point')" class="flex-1 py-2 rounded-lg transition bg-cyan-500 text-slate-950 shadow">
                💳 Maquininha Point
            </button>
            <button type="button" id="tabBtnPixMp" onclick="trocarAbaMp('pix')" class="flex-1 py-2 rounded-lg transition text-slate-400 hover:text-white">
                ⚡ Pix Dinâmico
            </button>
        </div>

        <!-- Conteúdo Aba 1: Maquininha Point -->
        <div id="mpConteudoPoint" class="space-y-3">
            <div class="space-y-1">
                <label class="block text-[10px] font-bold text-slate-400 uppercase">Selecione a Maquininha (Point)</label>
                <select id="mpSelectDevice" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs font-bold text-white focus:outline-none focus:border-cyan-400">
                    <!-- Opções inseridas dinamicamente via JS -->
                </select>
            </div>

            <!-- Status do Terminal -->
            <div id="mpStatusTerminal" class="hidden bg-slate-950/80 border border-cyan-500/30 p-3 rounded-xl text-center space-y-2">
                <div class="inline-block animate-spin text-xl text-cyan-400">⏳</div>
                <div class="text-xs font-bold text-cyan-300" id="mpStatusTerminalTexto">Aguardando inserção ou aproximação do cartão no terminal...</div>
                <div class="text-[10px] text-slate-400">Solicite ao cliente que insira o cartão ou aproxime (NFC).</div>
            </div>

            <button type="button" id="btnDispararPoint" onclick="dispararCobrancaPoint()" class="w-full py-3.5 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-slate-950 font-montserrat font-black text-sm rounded-xl shadow-lg transition flex items-center justify-center gap-2">
                <span>🚀 Enviar para Maquininha</span>
            </button>

            <button type="button" id="btnCancelarPoint" onclick="cancelarCobrancaPoint()" class="hidden w-full py-2 bg-rose-500/20 text-rose-300 hover:bg-rose-500/30 text-xs font-bold rounded-xl border border-rose-500/30 transition">
                ❌ Cancelar no Terminal
            </button>
        </div>

        <!-- Conteúdo Aba 2: Pix Dinâmico -->
        <div id="mpConteudoPix" class="hidden space-y-3">
            <!-- QR Code do Mercado Pago -->
            <div class="bg-white p-3 rounded-2xl flex items-center justify-center min-h-[190px]" id="mpPixQrCodeContainer">
                <div class="text-slate-500 text-xs font-bold py-6 text-center">
                    Clique abaixo para gerar o Pix Dinâmico com baixa automática
                </div>
            </div>

            <!-- Código Copia e Cola -->
            <div id="mpPixCopiaColaContainer" class="hidden space-y-1.5">
                <label class="block text-[10px] font-bold text-slate-400 uppercase">Pix Copia e Cola</label>
                <textarea id="mpPixCodigo" readonly rows="2" class="w-full bg-slate-950 border border-slate-800 text-slate-300 rounded-xl p-2 text-[10px] font-mono select-all focus:outline-none resize-none"></textarea>
                <button type="button" onclick="copiarCodigoPixMp()" id="btnCopiarPixMp" class="w-full py-2 bg-slate-800 hover:bg-slate-700 text-cyan-400 font-bold text-xs rounded-xl border border-slate-700 transition">
                    📋 Copiar Código PIX
                </button>
            </div>

            <button type="button" id="btnGerarPixMp" onclick="gerarPixDinamicoMp()" class="w-full py-3.5 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-montserrat font-black text-sm rounded-xl shadow-lg transition flex items-center justify-center gap-2">
                <span>⚡ Gerar Pix Mercado Pago</span>
            </button>

            <div id="mpPixStatusWaiting" class="hidden text-center text-xs font-bold text-emerald-400 animate-pulse">
                Aguardando confirmação do pagamento...
            </div>
        </div>

        <!-- Opção de Concluir Manualmente ou Fechar -->
        <div class="pt-2 border-t border-slate-800 flex items-center justify-between gap-2">
            <button type="button" onclick="concluirVendaMercadoPagoManualmente()" class="text-[11px] text-slate-400 hover:text-amber-400 font-bold underline transition">
                Registrar sem acionar terminal
            </button>
            <button type="button" onclick="fecharModalMercadoPagoPDV()" class="text-xs font-bold text-slate-400 hover:text-white px-3 py-1.5 rounded-lg hover:bg-slate-800 transition">
                Fechar
            </button>
        </div>
    </div>
</div>

<!-- Modal Comprovante de Venda Expressa (Envio Evolution API) -->
<div id="modalComprovanteVenda" class="fixed inset-0 z-[160] hidden bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
    <div class="bg-slate-900 border border-slate-700 rounded-3xl shadow-2xl max-w-md w-full p-5 space-y-4 text-white relative my-8">
        
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <span class="text-2xl">⚡</span>
                <div>
                    <h3 class="font-extrabold text-base text-emerald-400">Venda Concluída com Sucesso!</h3>
                    <p class="text-[10px] text-slate-400">Comprovante de Venda gerado automaticamente</p>
                </div>
            </div>
            <button type="button" onclick="fecharModalComprovanteVenda()" class="text-slate-400 hover:text-white p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Contêiner do Recibo Térmico (Capturado pelo HTML2Canvas) -->
        <div id="comprovanteReciboContainer" class="bg-white text-slate-900 p-5 rounded-2xl shadow-inner font-sans text-xs space-y-3 border border-slate-200">
            <!-- Renderizado dinamicamente via JS pós-venda -->
        </div>

        <!-- Botões de Ação -->
        <div class="space-y-2 pt-2 border-t border-slate-800">
            <button type="button" id="btnEnviarWhatsappEvolution" onclick="enviarComprovanteWhatsAppEvolution()" class="w-full py-3.5 bg-purple-600 hover:bg-purple-700 text-white font-montserrat font-extrabold text-xs rounded-xl shadow-lg transition flex items-center justify-center gap-2">
                <span>📱 Enviar Comprovante via WhatsApp (Evolution API)</span>
            </button>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" onclick="imprimirRecibo80mm()" class="py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 transition">
                    🖨️ Recibo 80mm
                </button>
                <button type="button" onclick="fecharModalComprovanteVenda()" class="py-2.5 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-bold text-xs rounded-xl shadow transition">
                    ⚡ Próxima Venda
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Modal Seletor de Matriz / Variações de Produto (Grade de Modelo/Cor x Tamanho) -->
<div id="modalSeletorMatriz" class="fixed inset-0 z-[150] hidden bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
    <div class="bg-slate-900 border border-slate-700 rounded-3xl shadow-2xl max-w-lg w-full p-5 space-y-4 text-white relative my-8">
        
        <!-- Header do Modal -->
        <div class="flex items-start justify-between border-b border-slate-800 pb-3 gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <img id="matrizModalFoto" src="" alt="Produto" class="w-12 h-12 object-contain rounded-xl bg-white p-1 flex-shrink-0 shadow">
                <div class="min-w-0">
                    <span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider">📦 Escolha a Grade / Variação</span>
                    <h3 id="matrizModalNome" class="font-extrabold text-sm sm:text-base text-white truncate mt-0.5">Produto</h3>
                    <p id="matrizModalPrecoBase" class="text-xs font-bold text-emerald-400">R$ 0,00</p>
                </div>
            </div>
            <button type="button" onclick="fecharModalSeletorMatriz()" class="text-slate-400 hover:text-white p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Seletor de Modelo / Cor -->
        <div class="space-y-1.5" id="matrizContainerCores">
            <label class="block text-[11px] font-bold text-amber-400 uppercase tracking-wider">1. Modelo / Cor:</label>
            <div id="matrizListaCores" class="flex flex-wrap gap-2 max-h-32 overflow-y-auto pr-1">
                <!-- Renderizado dinamicamente via JS -->
            </div>
        </div>

        <!-- Seletor de Tamanho -->
        <div class="space-y-1.5">
            <label class="block text-[11px] font-bold text-amber-400 uppercase tracking-wider">2. Tamanho / Opção:</label>
            <div id="matrizListaTamanhos" class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-48 overflow-y-auto pr-1">
                <!-- Renderizado dinamicamente via JS -->
            </div>
        </div>

        <!-- Resumo da Seleção Atual & Quantidade -->
        <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-3 flex items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="text-[10px] text-slate-400 uppercase font-bold">Item Selecionado:</div>
                <div id="matrizItemSelecionadoNome" class="text-xs font-black text-amber-300 truncate">Selecione modelo e tamanho</div>
                <div id="matrizItemSelecionadoEstoque" class="text-[10px] text-slate-400 font-semibold">Estoque disponível: -</div>
            </div>

            <!-- Quantidade a Adicionar -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <div class="text-[10px] text-slate-400 uppercase font-bold hidden sm:block">Qtd:</div>
                <div class="flex items-center bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
                    <button type="button" onclick="alterarQtdModalMatriz(-1)" class="px-2.5 py-1 text-slate-300 hover:bg-slate-700 font-bold text-sm">-</button>
                    <input type="text" id="matrizQtdInput" value="1" class="w-14 bg-transparent text-center text-xs font-black text-white focus:outline-none">
                    <button type="button" onclick="alterarQtdModalMatriz(1)" class="px-2.5 py-1 text-slate-300 hover:bg-slate-700 font-bold text-sm">+</button>
                </div>
            </div>
        </div>

        <!-- Botões de Ação do Modal -->
        <div class="space-y-2 pt-2 border-t border-slate-800">
            <button type="button" id="btnConfirmarVarianteMatriz" onclick="confirmarAdicionarVarianteMatriz()" disabled class="w-full py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 disabled:opacity-40 disabled:cursor-not-allowed text-slate-950 font-montserrat font-black text-xs sm:text-sm rounded-xl shadow-lg transition flex items-center justify-center gap-2">
                <span>🛒 Adicionar ao Carrinho</span>
            </button>
            <button type="button" onclick="fecharModalSeletorMatriz()" class="w-full py-2 text-xs font-bold text-slate-400 hover:text-white transition">
                Cancelar
            </button>
        </div>
    </div>
</div>

<script>
    const produtosArray = [
        <?php foreach ($produtos as $p): 
            $foto = $p->fotoPrincipal ?: ($p->fotos[0] ?? null);
            $urlFoto = $foto ? Url::to('@web/' . ltrim($foto->arquivo_path, '/'), true) : '';
            $precoStr = number_format($p->preco_venda_sugerido, 2, ',', '.');
            $vars = [];
            if (!empty($p->variantesNovas)) {
                foreach ($p->variantesNovas as $v) {
                    if (!$v->ativo) continue;
                    $vars[] = [
                        'id' => $v->id,
                        'produto_id' => $p->id,
                        'cor' => $v->cor,
                        'tamanho' => $v->tamanho,
                        'nome_formatado' => $v->getNomeFormatado(),
                        'estoque' => (float)$v->estoque_atual,
                        'preco' => $v->getPrecoVendaEfetivo(),
                        'preco_str' => number_format($v->getPrecoVendaEfetivo(), 2, ',', '.'),
                        'codigo_barras' => $v->codigo_barras ?: '',
                        'sku' => $v->codigo_referencia ?: '',
                    ];
                }
            }
        ?>
        {
            id: <?= json_encode($p->id) ?>,
            nome: <?= json_encode($p->nome) ?>,
            marca: <?= json_encode($p->marca ?: '') ?>,
            precoVal: <?= (float)$p->preco_venda_sugerido ?>,
            precoStr: <?= json_encode($precoStr) ?>,
            unidade: <?= json_encode($p->unidade_medida ?: 'UN') ?>,
            estoqueVal: <?= (float)($p->estoque_atual ?? 0) ?>,
            foto: <?= json_encode($urlFoto) ?>,
            vendaFracionada: <?= json_encode((bool)$p->venda_fracionada) ?>,
            temMatriz: <?= json_encode(count($vars) > 0) ?>,
            variantes: <?= json_encode($vars) ?>
        },
        <?php endforeach; ?>
    ];

    const lojaPixConfig = {
        chave: <?= json_encode($pixChaveConfig) ?>,
        nome: <?= json_encode($pixNomeConfig) ?>,
        cidade: <?= json_encode($pixCidadeConfig) ?>
    };

    let itensVendaMap = {};
    let formaPagamentoSelecionadaId = '<?= $fpPadrao ? $fpPadrao->id : (count($formasPagamento) > 0 ? $formasPagamento[0]->id : "") ?>';
    let formaPagamentoSelecionadaNome = '<?= $fpPadrao ? Html::encode($fpPadrao->nome) : (count($formasPagamento) > 0 ? Html::encode($formasPagamento[0]->nome) : "") ?>';
    let formaPagamentoSelecionadaTipo = '<?= $fpPadrao ? Html::encode($fpPadrao->tipo) : "" ?>';
    const temMercadoPagoConfig = <?= json_encode((bool)($temMercadoPago ?? false)) ?>;
    const lojaIdAtual = <?= json_encode((string)($lojaId ?? '')) ?>;
    const dispositivosPointDisponiveis = <?= json_encode($dispositivosPoint ?? []) ?>;
    const baseUrlApp = '<?= Yii::$app->request->baseUrl ?>';
    let mpPollingInterval = null;
    let mpIntentIdAtual = null;
    let mpPaymentIdAtual = null;
    let indexItemFocado = -1;
    let dadosUltimaVendaFinalizada = null;

    function aplicarMascaraMoedaInput(input, tipoSelectId) {
        const tipo = tipoSelectId ? document.getElementById(tipoSelectId).value : 'VALOR';
        if (tipo === 'PERCENTUAL') {
            let v = input.value.replace(/[^0-9,.]/g, '');
            input.value = v;
            return;
        }
        let v = input.value.replace(/\D/g, '');
        if (!v || v === '0') {
            input.value = '';
            return;
        }
        let num = (parseInt(v, 10) / 100).toFixed(2);
        let parts = num.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        input.value = parts.join(',');
    }

    function limparEMascarar(inputId) {
        const input = document.getElementById(inputId);
        if (input) {
            input.value = '';
        }
    }

    function aplicarMascaraTelefone(input) {
        let v = input.value.replace(/\D/g, '');
        if (v.length > 11) v = v.substring(0, 11);
        if (v.length > 10) {
            input.value = v.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
        } else if (v.length > 6) {
            input.value = v.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3');
        } else if (v.length > 2) {
            input.value = v.replace(/^(\d{2})(\d{0,5})$/, '($1) $2');
        } else {
            input.value = v;
        }
    }

    function aplicarMascaraCpf(input) {
        let v = input.value.replace(/\D/g, '');
        if (v.length > 11) v = v.substring(0, 11);
        if (v.length > 9) {
            input.value = v.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
        } else if (v.length > 6) {
            input.value = v.replace(/^(\d{3})(\d{3})(\d{0,3})$/, '$1.$2.$3');
        } else if (v.length > 3) {
            input.value = v.replace(/^(\d{3})(\d{0,3})$/, '$1.$2');
        } else {
            input.value = v;
        }
    }

    function filtrarProdutosBusca(termo) {
        const dropdown = document.getElementById('dropdownBuscaResultados');
        const btnLimpar = document.getElementById('btnLimparBusca');
        const termoClean = (termo || '').trim().toLowerCase();

        btnLimpar.style.display = termoClean ? 'block' : 'none';

        if (!termoClean) {
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
            indexItemFocado = -1;
            return;
        }

        const resultados = produtosArray.filter(p => {
            if (p.nome.toLowerCase().includes(termoClean) || (p.marca && p.marca.toLowerCase().includes(termoClean))) {
                return true;
            }
            if (p.temMatriz && p.variantes && p.variantes.length > 0) {
                return p.variantes.some(v => 
                    (v.codigo_barras && v.codigo_barras.toLowerCase() === termoClean) ||
                    (v.sku && v.sku.toLowerCase() === termoClean) ||
                    v.cor.toLowerCase().includes(termoClean) ||
                    v.tamanho.toLowerCase().includes(termoClean) ||
                    v.nome_formatado.toLowerCase().includes(termoClean)
                );
            }
            return false;
        });

        if (resultados.length === 0) {
            dropdown.innerHTML = `<div class="p-4 text-xs font-bold text-slate-400 text-center">Nenhum produto encontrado com "${termoClean}"</div>`;
            dropdown.classList.remove('hidden');
            indexItemFocado = -1;
            return;
        }

        dropdown.innerHTML = '';
        indexItemFocado = -1;

        resultados.forEach((prod, idx) => {
            const item = document.createElement('div');
            item.className = 'item-resultado-busca p-3 hover:bg-amber-400/20 cursor-pointer flex items-center justify-between transition gap-3 group border-b border-slate-700/40 last:border-0';
            item.setAttribute('data-index', idx);
            item.onclick = function() {
                selecionarProdutoDireto(prod);
            };

            const nomeHighlighted = highlightTermo(prod.nome, termoClean);
            const estoqueBadge = prod.estoqueVal > 0 
                ? `<span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[9px] font-extrabold px-1.5 py-0.5 rounded">Estoque: ${prod.estoqueVal}</span>`
                : `<span class="bg-rose-500/20 text-rose-300 border border-rose-500/30 text-[9px] font-extrabold px-1.5 py-0.5 rounded">⚠️ Sem estoque (${prod.estoqueVal})</span>`;

            const matrizBadge = (prod.temMatriz && prod.variantes && prod.variantes.length > 0)
                ? `<span class="bg-indigo-500/25 text-indigo-300 border border-indigo-500/40 text-[9px] font-extrabold px-2 py-0.5 rounded-full">📦 Grade (${prod.variantes.length})</span>`
                : '';

            const fracionadoBadge = prod.vendaFracionada
                ? `<span class="bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[9px] font-extrabold px-1.5 py-0.5 rounded">⚡ Fracionado</span>`
                : '';

            item.innerHTML = `
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    ${prod.foto ? `<img src="${prod.foto}" class="w-9 h-9 object-contain rounded-lg bg-white p-0.5 flex-shrink-0">` : `<div class="w-9 h-9 rounded-lg bg-slate-900 flex items-center justify-center text-[9px] font-bold text-slate-500 flex-shrink-0">FOTO</div>`}
                    <div class="truncate">
                        <div class="font-extrabold text-xs text-white group-hover:text-amber-300 truncate">${nomeHighlighted}</div>
                        <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            ${prod.marca ? `<span class="text-[10px] text-slate-400 font-semibold">${prod.marca}</span>` : ''}
                            ${estoqueBadge}
                            ${matrizBadge}
                            ${fracionadoBadge}
                        </div>
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <div class="font-montserrat font-black text-xs text-emerald-400">R$ ${prod.precoStr}</div>
                    <div class="text-[10px] text-slate-400 uppercase font-bold">/${prod.unidade}</div>
                </div>
            `;
            dropdown.appendChild(item);
        });

        dropdown.classList.remove('hidden');
    }

    function highlightTermo(texto, termo) {
        if (!termo) return texto;
        const re = new RegExp('(' + termo.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return texto.replace(re, '<span class="bg-amber-400/30 text-amber-200 px-0.5 rounded font-black">$1</span>');
    }

    function tratarTeclasBusca(e) {
        const dropdown = document.getElementById('dropdownBuscaResultados');
        const itens = dropdown.querySelectorAll('.item-resultado-busca');

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (itens.length === 0) return;
            indexItemFocado = (indexItemFocado + 1) % itens.length;
            atualizarItemFocado(itens);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (itens.length === 0) return;
            indexItemFocado = (indexItemFocado - 1 + itens.length) % itens.length;
            atualizarItemFocado(itens);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (indexItemFocado >= 0 && itens[indexItemFocado]) {
                itens[indexItemFocado].click();
            } else if (itens.length > 0) {
                itens[0].click();
            }
        } else if (e.key === 'Escape') {
            limparBuscaProduto();
        }
    }

    function atualizarItemFocado(itens) {
        itens.forEach((it, i) => {
            if (i === indexItemFocado) {
                it.classList.add('bg-amber-400/20', 'border-l-4', 'border-amber-400');
                it.scrollIntoView({ block: 'nearest' });
            } else {
                it.classList.remove('bg-amber-400/20', 'border-l-4', 'border-amber-400');
            }
        });
    }

    // ============================================================
    // SELETOR DE MATRIZ / VARIAÇÕES (Modelo/Cor x Tamanho)
    // ============================================================
    let produtoMatrizAtual = null;
    let corMatrizSelecionada = null;
    let varianteMatrizSelecionada = null;

    function abrirModalSeletorMatriz(prod) {
        produtoMatrizAtual = prod;
        corMatrizSelecionada = null;
        varianteMatrizSelecionada = null;

        document.getElementById('matrizModalNome').textContent = prod.nome;
        document.getElementById('matrizModalPrecoBase').textContent = 'A partir de R$ ' + prod.precoStr + ' /' + prod.unidade;
        
        const img = document.getElementById('matrizModalFoto');
        if (prod.foto) {
            img.src = prod.foto;
            img.style.display = 'block';
        } else {
            img.style.display = 'none';
        }

        document.getElementById('matrizQtdInput').value = '1';
        document.getElementById('btnConfirmarVarianteMatriz').disabled = true;

        // Extrai Cores / Modelos Únicos
        const cores = [...new Set(prod.variantes.map(v => v.cor))];
        corMatrizSelecionada = cores[0] || null;

        renderizarCoresMatriz(cores);
        renderizarTamanhosMatriz();
        atualizarResumoSelecaoMatriz();

        document.getElementById('modalSeletorMatriz').classList.remove('hidden');
    }

    function fecharModalSeletorMatriz() {
        document.getElementById('modalSeletorMatriz').classList.add('hidden');
        produtoMatrizAtual = null;
        corMatrizSelecionada = null;
        varianteMatrizSelecionada = null;
    }

    function renderizarCoresMatriz(cores) {
        const container = document.getElementById('matrizListaCores');
        container.innerHTML = '';

        cores.forEach(cor => {
            const btn = document.createElement('button');
            btn.type = 'button';
            const isActive = cor === corMatrizSelecionada;
            btn.className = 'btn-cor-matriz px-3 py-1.5 rounded-xl border text-xs font-bold transition flex items-center gap-1.5 ' + 
                (isActive 
                    ? 'bg-amber-400 text-slate-950 border-amber-300 shadow-md font-black' 
                    : 'bg-slate-800 text-slate-300 border-slate-700 hover:bg-slate-700');
            btn.innerHTML = `<span>🎨</span> <span>${cor}</span>`;
            btn.onclick = function() {
                selecionarCorMatriz(cor);
            };
            container.appendChild(btn);
        });
    }

    function selecionarCorMatriz(cor) {
        corMatrizSelecionada = cor;
        varianteMatrizSelecionada = null;
        document.getElementById('btnConfirmarVarianteMatriz').disabled = true;

        // Atualiza botões de cor
        document.querySelectorAll('.btn-cor-matriz').forEach(btn => {
            if (btn.innerText.includes(cor)) {
                btn.className = 'btn-cor-matriz px-3 py-1.5 rounded-xl border text-xs font-black transition flex items-center gap-1.5 bg-amber-400 text-slate-950 border-amber-300 shadow-md';
            } else {
                btn.className = 'btn-cor-matriz px-3 py-1.5 rounded-xl border text-xs font-bold transition flex items-center gap-1.5 bg-slate-800 text-slate-300 border-slate-700 hover:bg-slate-700';
            }
        });

        renderizarTamanhosMatriz();
        atualizarResumoSelecaoMatriz();
    }

    function renderizarTamanhosMatriz() {
        const container = document.getElementById('matrizListaTamanhos');
        container.innerHTML = '';

        if (!produtoMatrizAtual || !corMatrizSelecionada) return;

        const variantesCor = produtoMatrizAtual.variantes.filter(v => v.cor === corMatrizSelecionada);

        variantesCor.forEach(v => {
            const card = document.createElement('div');
            const isSelected = varianteMatrizSelecionada && varianteMatrizSelecionada.id === v.id;
            const temEstoque = v.estoque > 0;
            
            card.className = 'card-variante-matriz p-2.5 rounded-2xl border cursor-pointer transition flex flex-col justify-between ' +
                (isSelected
                    ? 'bg-amber-400/20 border-amber-400 ring-2 ring-amber-400/50'
                    : 'bg-slate-800/80 border-slate-700 hover:border-slate-500 hover:bg-slate-800');

            card.innerHTML = `
                <div class="flex items-center justify-between">
                    <span class="text-sm font-black text-white">${v.tamanho}</span>
                    <span class="text-[9px] font-extrabold px-1.5 py-0.5 rounded ${temEstoque ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30'}">
                        ${temEstoque ? v.estoque + ' un' : '0 un'}
                    </span>
                </div>
                <div class="mt-2 flex items-center justify-between text-[11px]">
                    <span class="text-slate-400 font-semibold">R$ ${v.preco_str}</span>
                    <span class="text-[10px] text-amber-300 font-bold">${isSelected ? '✓ Escolhido' : '+ Selecionar'}</span>
                </div>
            `;

            card.onclick = function() {
                selecionarVarianteMatriz(v);
            };

            // Duplo clique adiciona imediatamente 1 unidade ao carrinho
            card.ondblclick = function() {
                selecionarVarianteMatriz(v);
                confirmarAdicionarVarianteMatriz();
            };

            container.appendChild(card);
        });
    }

    function selecionarVarianteMatriz(variante) {
        varianteMatrizSelecionada = variante;
        document.getElementById('btnConfirmarVarianteMatriz').disabled = false;

        renderizarTamanhosMatriz();
        atualizarResumoSelecaoMatriz();
    }

    function atualizarResumoSelecaoMatriz() {
        const elNome = document.getElementById('matrizItemSelecionadoNome');
        const elEstoque = document.getElementById('matrizItemSelecionadoEstoque');

        if (varianteMatrizSelecionada) {
            elNome.textContent = varianteMatrizSelecionada.nome_formatado;
            const corEstoque = varianteMatrizSelecionada.estoque > 0 ? 'text-emerald-400 font-bold' : 'text-rose-400 font-bold';
            elEstoque.innerHTML = `Estoque: <span class="${corEstoque}">${varianteMatrizSelecionada.estoque} un</span> • Preço: <span class="text-amber-300 font-bold">R$ ${varianteMatrizSelecionada.preco_str}</span>`;
        } else {
            elNome.textContent = corMatrizSelecionada ? `Modelo ${corMatrizSelecionada} - Escolha o tamanho` : 'Selecione modelo e tamanho';
            elEstoque.textContent = 'Estoque disponível: -';
        }
    }

    function alterarQtdModalMatriz(delta) {
        const input = document.getElementById('matrizQtdInput');
        let val = parseFloat(String(input.value).replace(',', '.')) || 1;
        val = Math.round((val + delta) * 1000) / 1000;
        if (val < 0.001) val = 0.001;
        input.value = (val % 1 === 0) ? val : val.toString().replace('.', ',');
    }

    function confirmarAdicionarVarianteMatriz() {
        if (!produtoMatrizAtual || !varianteMatrizSelecionada) {
            alert('Por favor, selecione um tamanho/variação antes de adicionar.');
            return;
        }

        const inputQtd = document.getElementById('matrizQtdInput');
        const qtd = parseFloat(String(inputQtd.value).replace(',', '.')) || 1;

        if (qtd <= 0) {
            alert('A quantidade deve ser maior que zero.');
            return;
        }

        adicionarVarianteAoCarrinho(produtoMatrizAtual, varianteMatrizSelecionada, qtd);
    }

    function adicionarVarianteAoCarrinho(prod, variante, qtd) {
        const itemKey = prod.id + '_' + variante.id;
        if (!itensVendaMap[itemKey]) {
            itensVendaMap[itemKey] = {
                itemKey: itemKey,
                id: prod.id,
                produto_id: prod.id,
                variante_id: variante.id,
                nome: variante.nome_formatado,
                cor: variante.cor,
                tamanho: variante.tamanho,
                precoVal: variante.preco > 0 ? variante.preco : prod.precoVal,
                unidade: prod.unidade,
                estoqueVal: variante.estoque,
                foto: prod.foto,
                qtd: qtd
            };
        } else {
            itensVendaMap[itemKey].qtd = Math.round((itensVendaMap[itemKey].qtd + qtd) * 1000) / 1000;
        }

        fecharModalSeletorMatriz();
        limparBuscaProduto();
        renderizarItensVenda();
        document.getElementById('inputBuscaProduto').focus();
    }

    function adicionarProdutoAoCarrinho(prod, qtd) {
        const itemKey = prod.id;
        if (!itensVendaMap[itemKey]) {
            itensVendaMap[itemKey] = {
                itemKey: itemKey,
                id: prod.id,
                produto_id: prod.id,
                variante_id: null,
                nome: prod.nome,
                precoVal: prod.precoVal,
                unidade: prod.unidade,
                estoqueVal: prod.estoqueVal,
                foto: prod.foto,
                qtd: qtd
            };
        } else {
            itensVendaMap[itemKey].qtd = Math.round((itensVendaMap[itemKey].qtd + qtd) * 1000) / 1000;
        }

        limparBuscaProduto();
        renderizarItensVenda();
        document.getElementById('inputBuscaProduto').focus();
    }

    function selecionarProdutoDireto(prod) {
        const inputBusca = document.getElementById('inputBuscaProduto');
        const termo = (inputBusca?.value || '').trim().toLowerCase();

        // Se o produto possui matriz e variantes cadastradas
        if (prod.temMatriz && prod.variantes && prod.variantes.length > 0) {
            // Verifica se o leitor de código de barras ou busca encontrou uma variante específica
            const matchExato = prod.variantes.find(v => 
                (v.codigo_barras && v.codigo_barras.toLowerCase() === termo) ||
                (v.sku && v.sku.toLowerCase() === termo)
            );

            if (matchExato) {
                adicionarVarianteAoCarrinho(prod, matchExato, 1);
                return;
            }

            // Abre modal interativo de escolha de cor e tamanho
            abrirModalSeletorMatriz(prod);
            return;
        }

        adicionarProdutoAoCarrinho(prod, 1);
    }

    function limparBuscaProduto() {
        const input = document.getElementById('inputBuscaProduto');
        input.value = '';
        document.getElementById('dropdownBuscaResultados').classList.add('hidden');
        document.getElementById('btnLimparBusca').style.display = 'none';
        indexItemFocado = -1;
    }

    // Fechar dropdown se clicar fora
    document.addEventListener('click', function(e) {
        const container = document.getElementById('containerBuscaProduto');
        if (container && !container.contains(e.target)) {
            document.getElementById('dropdownBuscaResultados').classList.add('hidden');
        }
    });

    function alterarQtdItem(key, delta) {
        if (itensVendaMap[key]) {
            let novaQtd = (parseFloat(itensVendaMap[key].qtd) || 0) + delta;
            novaQtd = Math.round(novaQtd * 1000) / 1000;
            if (novaQtd <= 0) {
                delete itensVendaMap[key];
            } else {
                itensVendaMap[key].qtd = novaQtd;
            }
            renderizarItensVenda();
        }
    }

    function atualizarQtdDireta(key, valStr) {
        const val = parseFloat(String(valStr).replace(',', '.'));
        if (!isNaN(val) && val > 0 && itensVendaMap[key]) {
            itensVendaMap[key].qtd = Math.round(val * 1000) / 1000;
            renderizarItensVenda();
        }
    }

    function atualizarPrecoDireta(key, valStr) {
        const val = parseFloat(String(valStr).replace(',', '.'));
        if (!isNaN(val) && val >= 0 && itensVendaMap[key]) {
            itensVendaMap[key].precoVal = val;
            renderizarItensVenda();
        }
    }

    function removerItem(key) {
        delete itensVendaMap[key];
        renderizarItensVenda();
    }

    function limparItensVenda() {
        itensVendaMap = {};
        document.getElementById('descontoGeral').value = '';
        document.getElementById('acrescimoGeral').value = '';
        renderizarItensVenda();
    }

    function isFormaBoletoFiado(nomeOuTipo) {
        const s = (nomeOuTipo || '').toLowerCase();
        return s.includes('boleto') || s.includes('fiado') || s.includes('carne') || s.includes('carnê');
    }

    function selecionarFormaPagamento(id, btn) {
        formaPagamentoSelecionadaId = id;
        formaPagamentoSelecionadaNome = btn.getAttribute('data-nome') || btn.innerText;
        formaPagamentoSelecionadaTipo = btn.getAttribute('data-tipo') || '';
        document.querySelectorAll('.btn-forma-pagamento').forEach(b => {
            b.className = 'btn-forma-pagamento p-2.5 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5 bg-slate-900 text-slate-300 border-slate-700 hover:bg-slate-700';
        });
        btn.className = 'btn-forma-pagamento p-2.5 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5 bg-amber-400 text-slate-900 border-amber-300 shadow-md';

        checarExibicaoVencimentoFiado();
    }

    function checarExibicaoVencimentoFiado() {
        const ehFiado = isFormaBoletoFiado(formaPagamentoSelecionadaNome);
        const containerVenc = document.getElementById('container-vencimento-fiado');
        const badgeCliente = document.getElementById('badgeClienteObrigatorioFiado');
        const btnEfetivar = document.getElementById('btnEfetivarVenda');
        const totalStr = document.getElementById('displayTotalFinal')?.textContent || '0,00';

        if (ehFiado) {
            containerVenc?.classList.remove('hidden');
            badgeCliente?.classList.remove('hidden');
            if (btnEfetivar && !btnEfetivar.disabled) {
                const vencVal = document.getElementById('dataVencimentoFiado')?.value;
                let vencFormatado = '';
                if (vencVal) {
                    const partes = vencVal.split('-');
                    if (partes.length === 3) vencFormatado = ` (Venc: ${partes[2]}/${partes[1]})`;
                }
                btnEfetivar.innerHTML = `<span>⚡ Efetivar Venda a Prazo (R$ <span id="totalFinalBtn">${totalStr}</span>)${vencFormatado}</span>`;
            }
        } else {
            containerVenc?.classList.add('hidden');
            badgeCliente?.classList.add('hidden');
            if (btnEfetivar && !btnEfetivar.disabled) {
                btnEfetivar.innerHTML = `<span>⚡ Efetivar Venda (R$ <span id="totalFinalBtn">${totalStr}</span>)</span>`;
            }
        }
    }

    function definirVencimentoDias(dias) {
        const d = new Date();
        d.setDate(d.getDate() + parseInt(dias));
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        const dataStr = `${yyyy}-${mm}-${dd}`;
        
        const inputVenc = document.getElementById('dataVencimentoFiado');
        if (inputVenc) {
            inputVenc.value = dataStr;
        }

        document.querySelectorAll('.chip-vencimento').forEach(btn => {
            btn.className = 'chip-vencimento px-2 py-0.5 rounded-lg bg-slate-800 hover:bg-amber-500/20 text-slate-300 hover:text-amber-300 border border-slate-700 text-[10px] font-bold transition';
        });
        const btnAtivo = document.getElementById(`chip-venc-${dias}`);
        if (btnAtivo) {
            btnAtivo.className = 'chip-vencimento px-2 py-0.5 rounded-lg bg-amber-400 text-slate-950 border border-amber-300 text-[10px] font-black transition';
        }

        checarExibicaoVencimentoFiado();
    }

    // Autocomplete inteligente de clientes
    let timerBuscaCliente = null;
    function buscarClientesAutocomplete(termo) {
        clearTimeout(timerBuscaCliente);
        const dropdown = document.getElementById('dropdownClientesSugestoes');
        if (!termo || termo.trim().length < 2) {
            if (dropdown) dropdown.classList.add('hidden');
            return;
        }

        timerBuscaCliente = setTimeout(() => {
            fetch(`<?= Url::to(['/vendas/venda-expressa/buscar-clientes']) ?>?q=${encodeURIComponent(termo)}`)
                .then(r => r.json())
                .then(data => {
                    if (!dropdown) return;
                    const results = data.results || [];
                    if (results.length === 0) {
                        dropdown.classList.add('hidden');
                        return;
                    }

                    dropdown.innerHTML = results.map(c => `
                        <div onclick='selecionarClienteSugerido(${JSON.stringify(c).replace(/'/g, "&apos;")})' class="p-2.5 hover:bg-slate-800 cursor-pointer border-b border-slate-800 last:border-0 flex items-center justify-between transition">
                            <div>
                                <span class="text-xs font-bold text-white block">${c.nome}</span>
                                <span class="text-[11px] text-slate-400 font-mono">${c.telefone || 'Sem telefone'} ${c.cpf ? '• CPF: ' + c.cpf : ''}</span>
                            </div>
                            <span class="text-[10px] bg-amber-400/20 text-amber-300 font-bold px-2 py-0.5 rounded">Selecionar</span>
                        </div>
                    `).join('');
                    dropdown.classList.remove('hidden');
                })
                .catch(() => {
                    if (dropdown) dropdown.classList.add('hidden');
                });
        }, 250);
    }

    function selecionarClienteSugerido(cliente) {
        if (!cliente) return;
        if (cliente.nome) document.getElementById('clienteNome').value = cliente.nome;
        if (cliente.telefone) document.getElementById('clienteWhatsapp').value = cliente.telefone;
        if (cliente.cpf) document.getElementById('clienteCpf').value = cliente.cpf;
        const dropdown = document.getElementById('dropdownClientesSugestoes');
        if (dropdown) dropdown.classList.add('hidden');
    }

    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('dropdownClientesSugestoes');
        const container = document.getElementById('containerInputsCliente');
        if (dropdown && container && !container.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // ============================================================
    // MÚLTIPLAS FORMAS DE PAGAMENTO (PIX + Dinheiro + Cartão, etc.)
    // ============================================================
    const formasPagamentoDisponiveis = [
        <?php foreach ($formasPagamento as $fp): ?>
        { id: <?= json_encode($fp->id) ?>, nome: <?= json_encode($fp->nome) ?>, tipo: <?= json_encode($fp->tipo) ?> },
        <?php endforeach; ?>
    ];

    function toggleMultiplosPagamentos() {
        const usarMultiplos = document.getElementById('usar-multiplos-pagamentos')?.checked || false;
        const containerMultiplos = document.getElementById('container-multiplos-pagamentos');
        const containerUnico = document.getElementById('container-pagamento-unico');

        if (usarMultiplos) {
            containerUnico?.classList.add('hidden');
            containerMultiplos?.classList.remove('hidden');
            const lista = document.getElementById('lista-pagamentos-multiplos');
            if (lista && lista.children.length === 0) {
                adicionarLinhaPagamentoMultiplo();
            }
        } else {
            containerUnico?.classList.remove('hidden');
            containerMultiplos?.classList.add('hidden');
        }
        recalcularResumoMultiplo();
    }

    function getTotalVendaNumerico() {
        const totalStr = document.getElementById('displayTotalFinal')?.textContent || '0,00';
        return parseFloat(totalStr.replace(/\./g, '').replace(',', '.')) || 0;
    }

    function obterTotalInformadoMultiplo() {
        let total = 0;
        document.querySelectorAll('#lista-pagamentos-multiplos .input-valor-pagamento').forEach(input => {
            const v = parseFloat((input.value || '0').replace(/\./g, '').replace(',', '.')) || 0;
            total += v;
        });
        return total;
    }

    function recalcularResumoMultiplo() {
        const usarMultiplos = document.getElementById('usar-multiplos-pagamentos')?.checked || false;
        if (!usarMultiplos) return;

        const totalVenda = getTotalVendaNumerico();
        const totalInformado = obterTotalInformadoMultiplo();
        const restante = totalVenda - totalInformado;

        const elTotalVenda = document.getElementById('multiplo-total-venda');
        const elTotalInformado = document.getElementById('multiplo-total-informado');
        const elRestanteValor = document.getElementById('valor-multiplo-restante');
        const elRestanteLabel = document.getElementById('label-multiplo-restante');
        const btnConfirmar = document.getElementById('btnEfetivarVenda');

        if (elTotalVenda) elTotalVenda.textContent = totalVenda.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        if (elTotalInformado) elTotalInformado.textContent = totalInformado.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

        if (elRestanteValor) {
            elRestanteValor.textContent = Math.abs(restante).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            elRestanteValor.classList.remove('text-red-400', 'text-green-400', 'text-yellow-400');

            if (Math.abs(restante) < 0.01) {
                elRestanteValor.textContent = 'Pago!';
                elRestanteValor.classList.add('text-green-400');
                if (elRestanteLabel) elRestanteLabel.textContent = 'Status:';
                if (btnConfirmar) btnConfirmar.disabled = false;
            } else if (restante > 0) {
                elRestanteValor.classList.add('text-red-400');
                if (elRestanteLabel) elRestanteLabel.textContent = 'Restante:';
                if (btnConfirmar) btnConfirmar.disabled = true;
            } else {
                elRestanteValor.classList.add('text-yellow-400');
                if (elRestanteLabel) elRestanteLabel.textContent = 'Excedente:';
                if (btnConfirmar) btnConfirmar.disabled = true;
            }
        }
    }

    function adicionarLinhaPagamentoMultiplo() {
        const lista = document.getElementById('lista-pagamentos-multiplos');
        if (!lista) return;

        let optionsHtml = '<option value="">Selecione o pagamento...</option>';
        formasPagamentoDisponiveis.forEach(fp => {
            optionsHtml += `<option value="${fp.id}">${fp.nome}</option>`;
        });

        const row = document.createElement('div');
        row.className = 'flex items-center gap-2 bg-slate-900/70 border border-slate-700 p-2 rounded-2xl';

        row.innerHTML = `
            <select class="select-forma-pagamento flex-1 bg-slate-800 border border-slate-700 text-slate-200 text-xs font-bold p-2 rounded-xl focus:outline-none focus:border-amber-400">
                ${optionsHtml}
            </select>
            <input type="text" inputmode="decimal" placeholder="0,00" class="input-valor-pagamento w-28 bg-slate-800 border border-slate-700 text-right text-xs font-bold text-amber-400 p-2 rounded-xl focus:outline-none focus:border-amber-400">
            <button type="button" class="btn-remover-pagamento text-slate-500 hover:text-red-400 p-1.5 text-base" title="Remover">🗑️</button>
        `;

        row.querySelector('.select-forma-pagamento').addEventListener('change', recalcularResumoMultiplo);
        row.querySelector('.input-valor-pagamento').addEventListener('input', function() {
            aplicarMascaraMoedaInput(this, null);
            recalcularResumoMultiplo();
        });
        row.querySelector('.btn-remover-pagamento').addEventListener('click', function() {
            row.remove();
            recalcularResumoMultiplo();
        });

        lista.appendChild(row);
        recalcularResumoMultiplo();
    }

    function coletarPagamentosMultiplos() {
        const pagamentos = [];
        let erro = false;

        document.querySelectorAll('#lista-pagamentos-multiplos .select-forma-pagamento').forEach(select => {
            if (erro) return;

            const row = select.closest('div');
            const fId = select.value;
            const inputValor = row ? row.querySelector('.input-valor-pagamento') : null;
            const val = parseFloat((inputValor?.value || '0').replace(/\./g, '').replace(',', '.')) || 0;

            if (!fId) {
                alert('Selecione a forma de pagamento para todas as linhas adicionadas.');
                erro = true;
                return;
            }
            if (val <= 0) {
                alert('O valor de cada forma de pagamento deve ser maior que zero.');
                erro = true;
                return;
            }

            pagamentos.push({ forma_pagamento_id: fId, valor: val });
        });

        if (erro) {
            return null;
        }

        if (pagamentos.length === 0) {
            alert('Adicione pelo menos uma forma de pagamento.');
            return null;
        }

        return pagamentos;
    }

    function renderizarItensVenda() {
        const container = document.getElementById('listaItensVenda');
        const lista = Object.values(itensVendaMap);
        let subtotalCalculado = 0;
        let totalQtdItens = 0;

        if (lista.length === 0) {
            container.innerHTML = `
                <div id="emptyStateVenda" class="text-center py-10 text-slate-400 space-y-2">
                    <span class="text-4xl block">🛍️</span>
                    <p class="text-xs font-bold">Nenhum produto adicionado ainda.</p>
                    <p class="text-[10px]">Digite no campo acima para pesquisar e adicionar em 1 clique!</p>
                </div>
            `;
        } else {
            container.innerHTML = '';
            lista.forEach(item => {
                const sub = item.precoVal * item.qtd;
                subtotalCalculado += sub;
                totalQtdItens += item.qtd;

                const avisoEstoque = (item.estoqueVal <= 0 || item.qtd > item.estoqueVal)
                    ? `<span class="bg-rose-500/20 text-rose-300 border border-rose-500/30 text-[9px] font-bold px-1.5 py-0.5 rounded ml-1">⚠️ Sem estoque (saldo: ${item.estoqueVal})</span>`
                    : '';

                const div = document.createElement('div');
                div.className = 'flex items-center justify-between p-3 bg-slate-900 border border-slate-700 rounded-2xl gap-3';
                const itemKey = item.itemKey || item.id;
                const badgeVariante = item.variante_id 
                    ? `<span class="bg-indigo-500/25 text-indigo-300 border border-indigo-500/40 text-[9px] font-extrabold px-1.5 py-0.5 rounded">${item.cor} • ${item.tamanho}</span>` 
                    : '';
                const isFracionado = (Math.abs(item.qtd - Math.round(item.qtd)) > 0.0001);
                const badgeFracionado = isFracionado 
                    ? `<span class="bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[9px] font-extrabold px-1.5 py-0.5 rounded">⚡ Fracionado</span>` 
                    : '';

                div.innerHTML = `
                    <div class="flex items-center gap-2.5 flex-1 min-w-0">
                        ${item.foto ? `<img src="${item.foto}" class="w-10 h-10 object-contain rounded-lg bg-white p-0.5 flex-shrink-0">` : `<div class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center text-[9px] font-bold text-slate-500 flex-shrink-0">FOTO</div>`}
                        <div class="truncate">
                            <div class="font-extrabold text-xs text-white truncate flex items-center flex-wrap gap-1">
                                <span>${item.nome}</span>
                                ${badgeVariante}
                                ${badgeFracionado}
                                ${avisoEstoque}
                            </div>
                            <div class="flex items-center gap-1 mt-0.5 text-[11px] text-slate-400">
                                <span>R$</span>
                                <input type="text" value="${item.precoVal.toFixed(2).replace('.', ',')}" oninput="aplicarMascaraMoedaInput(this); atualizarPrecoDireta('${itemKey}', this.value)" class="w-20 bg-slate-800 text-amber-400 font-bold px-1 py-0.5 rounded border border-slate-700 text-center">
                                <span>/${item.unidade}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Controle de Quantidade -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <div class="flex items-center bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
                            <button type="button" onclick="alterarQtdItem('${itemKey}', -1)" class="px-2.5 py-1 text-slate-300 hover:bg-slate-700 font-bold text-sm">-</button>
                            <input type="text" inputmode="decimal" value="${item.qtd}" onchange="atualizarQtdDireta('${itemKey}', this.value)" class="w-16 bg-transparent text-center text-xs font-black text-white focus:outline-none border-x border-slate-700">
                            <button type="button" onclick="alterarQtdItem('${itemKey}', 1)" class="px-2.5 py-1 text-slate-300 hover:bg-slate-700 font-bold text-sm">+</button>
                        </div>

                        <!-- Subtotal Item -->
                        <div class="text-right w-20">
                            <div class="text-xs font-montserrat font-black text-emerald-400">R$ ${sub.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                        </div>

                        <!-- Deletar Item -->
                        <button type="button" onclick="removerItem('${itemKey}')" class="text-slate-500 hover:text-red-400 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                `;
                container.appendChild(div);
            });
        }

        // Cálculo do Desconto Geral
        const descInput = parseFloat((document.getElementById('descontoGeral').value || '0').replace(',', '.')) || 0;
        const descTipo = document.getElementById('descontoTipo').value;
        let valDesconto = 0;
        if (descInput > 0) {
            valDesconto = (descTipo === 'PERCENTUAL') ? subtotalCalculado * (descInput / 100) : descInput;
        }

        // Cálculo do Acréscimo Geral
        $acresInput = parseFloat((document.getElementById('acrescimoGeral').value || '0').replace(',', '.')) || 0;
        $acresTipo = document.getElementById('acrescimoTipo').value;
        let valAcrescimo = 0;
        if ($acresInput > 0) {
            valAcrescimo = ($acresTipo === 'PERCENTUAL') ? subtotalCalculado * ($acresInput / 100) : $acresInput;
        }

        const totalFinalCalculado = Math.max(0, subtotalCalculado - valDesconto + valAcrescimo);

        document.getElementById('badgeCountItens').textContent = lista.length;
        document.getElementById('displaySubtotal').textContent = subtotalCalculado.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Exibição condicional de Desconto e Acréscimo no Resumo
        const rowDesc = document.getElementById('rowDisplayDesconto');
        if (valDesconto > 0) {
            rowDesc.classList.remove('hidden');
            document.getElementById('displayDesconto').textContent = valDesconto.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } else {
            rowDesc.classList.add('hidden');
        }

        const rowAcres = document.getElementById('rowDisplayAcrescimo');
        if (valAcrescimo > 0) {
            rowAcres.classList.remove('hidden');
            document.getElementById('displayAcrescimo').textContent = valAcrescimo.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } else {
            rowAcres.classList.add('hidden');
        }

        const totalFormatted = totalFinalCalculado.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('displayTotalFinal').textContent = totalFormatted;
        document.getElementById('totalFinalBtn').textContent = totalFormatted;

        recalcularResumoMultiplo();
    }

    function iniciarProcessoEfetivacao() {
        const lista = Object.values(itensVendaMap);
        if (lista.length === 0) {
            alert('Adicione pelo menos um produto antes de efetivar a venda.');
            return;
        }

        const usarMultiplos = document.getElementById('usar-multiplos-pagamentos')?.checked || false;

        if (usarMultiplos) {
            // Validação local antes de enviar
            const pagamentos = coletarPagamentosMultiplos();
            if (!pagamentos) return;

            const totalVenda = getTotalVendaNumerico();
            const soma = pagamentos.reduce((acc, p) => acc + p.valor, 0);
            if (Math.abs(soma - totalVenda) >= 0.01) {
                alert('A soma das formas de pagamento não bate com o total da venda.');
                return;
            }

            efetivarVendaExpressa();
            return;
        }

        const ehFiado = isFormaBoletoFiado(formaPagamentoSelecionadaNome);
        if (ehFiado) {
            const clienteNomeVal = document.getElementById('clienteNome')?.value.trim() || '';
            const clienteWhatsappVal = document.getElementById('clienteWhatsapp')?.value.trim() || '';
            const clienteCpfVal = document.getElementById('clienteCpf')?.value.trim() || '';

            if (!clienteNomeVal && !clienteWhatsappVal && !clienteCpfVal) {
                alert('⚠️ Para vendas no Boleto / Fiado, é obrigatório informar os dados do Cliente (Nome ou WhatsApp) para registro no Contas a Receber.');
                document.getElementById('clienteNome')?.focus();
                document.getElementById('containerInputsCliente')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            const dataVencVal = document.getElementById('dataVencimentoFiado')?.value;
            if (!dataVencVal) {
                alert('⚠️ Por favor, informe a Data de Vencimento da venda no Boleto / Fiado.');
                document.getElementById('dataVencimentoFiado')?.focus();
                return;
            }
        }

        const ehMercadoPago = (formaPagamentoSelecionadaNome || '').toLowerCase().includes('mercado') || (formaPagamentoSelecionadaTipo || '').toUpperCase() === 'MERCADOPAGO';
        if (ehMercadoPago && temMercadoPagoConfig) {
            abrirModalMercadoPagoPDV();
            return;
        }

        const ehPix = (formaPagamentoSelecionadaNome || '').toLowerCase().includes('pix');

        if (ehPix) {
            abrirModalPixEstatico();
        } else {
            efetivarVendaExpressa();
        }
    }

    function gerarEMVPixString(chave, nome, cidade, valor) {
        if (!chave) return '';
        let chaveLimpa = chave.trim();
        if (!chaveLimpa.includes('@') && !chaveLimpa.startsWith('+55')) {
            let nums = chaveLimpa.replace(/\D/g, '');
            if (nums.length === 10 || nums.length === 11) {
                chaveLimpa = '+55' + nums;
            } else if (nums.length === 14) {
                chaveLimpa = nums;
            }
        }
        
        function cleanStr(s) {
            return (s || '').normalize("NFD").replace(/[\u0300-\u036f]/g, "").toUpperCase().trim();
        }

        let payload = [];
        payload.push('000201');
        let merchantAccount = '0014br.gov.bcb.pix' + '01' + String(chaveLimpa.length).padStart(2, '0') + chaveLimpa;
        payload.push('26' + String(merchantAccount.length).padStart(2, '0') + merchantAccount);
        payload.push('52040000');
        payload.push('5303986');
        if (valor && parseFloat(valor) > 0) {
            let vStr = parseFloat(valor).toFixed(2);
            payload.push('54' + String(vStr.length).padStart(2, '0') + vStr);
        }
        payload.push('5802BR');
        let nomeTratado = cleanStr(nome || 'LOJA').substring(0, 25);
        payload.push('59' + String(nomeTratado.length).padStart(2, '0') + nomeTratado);
        let cidadeTratada = cleanStr(cidade || 'CARUARU').substring(0, 15);
        payload.push('60' + String(cidadeTratada.length).padStart(2, '0') + cidadeTratada);
        payload.push('62070503***');
        
        let strSemCRC = payload.join('') + '6304';
        
        // CRC16
        let crc = 0xFFFF;
        for (let i = 0; i < strSemCRC.length; i++) {
            crc ^= (strSemCRC.charCodeAt(i) << 8);
            for (let j = 0; j < 8; j++) {
                if (crc & 0x8000) {
                    crc = ((crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    crc = (crc << 1) & 0xFFFF;
                }
            }
        }
        let crcHex = crc.toString(16).toUpperCase().padStart(4, '0');
        return strSemCRC + crcHex;
    }

    function abrirModalPixEstatico() {
        const totalStr = document.getElementById('displayTotalFinal').textContent;
        const valorNum = parseFloat(totalStr.replace('.', '').replace(',', '.')) || 0;

        document.getElementById('pixModalValor').textContent = totalStr;

        const chave = lojaPixConfig.chave || '81992888872';
        const nome = lojaPixConfig.nome || 'ONLY CODE';
        const cidade = lojaPixConfig.cidade || 'CARUARU';

        const codigoPix = gerarEMVPixString(chave, nome, cidade, valorNum);

        document.getElementById('pixCodigoCopiaCola').value = codigoPix;

        const qrContainer = document.getElementById('pixQrCodeContainer');
        qrContainer.innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(codigoPix)}" alt="QR Code PIX" class="mx-auto rounded-xl shadow-md border border-slate-200">`;

        const modal = document.getElementById('modalPixEstatico');
        modal.classList.remove('hidden');
    }

    function fecharModalPixEstatico() {
        document.getElementById('modalPixEstatico').classList.add('hidden');
    }

    function copiarCodigoPixEstatico() {
        const textarea = document.getElementById('pixCodigoCopiaCola');
        textarea.select();
        textarea.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(textarea.value).then(() => {
            const btn = document.getElementById('btnCopiarPix');
            btn.innerHTML = '<span>✅ Código PIX Copiado!</span>';
            setTimeout(() => {
                btn.innerHTML = '<span>📋 Copiar Código PIX</span>';
            }, 2500);
        }).catch(err => {
            alert('Código selecionado! Use Ctrl+C para copiar.');
        });
    }

    function confirmarEEfetivarVendaPix() {
        fecharModalPixEstatico();
        efetivarVendaExpressa();
    }

    // ==========================================
    // MERCADO PAGO INTEGRADO NO PDV (POINT & PIX)
    // ==========================================
    function abrirModalMercadoPagoPDV() {
        const totalStr = document.getElementById('displayTotalFinal').textContent;
        document.getElementById('mpModalValor').textContent = totalStr;

        const selectDevice = document.getElementById('mpSelectDevice');
        selectDevice.innerHTML = '';
        if (dispositivosPointDisponiveis && dispositivosPointDisponiveis.length > 0) {
            dispositivosPointDisponiveis.forEach(dev => {
                const opt = document.createElement('option');
                opt.value = dev.device_id;
                opt.textContent = `${dev.nome || 'Point'} (${dev.device_id})`;
                selectDevice.appendChild(opt);
            });
        } else {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'Nenhuma maquininha cadastrada (Cadastre em Configurações)';
            selectDevice.appendChild(opt);
        }

        trocarAbaMp('point');
        document.getElementById('mpStatusTerminal')?.classList.add('hidden');
        const btnDisparar = document.getElementById('btnDispararPoint');
        if (btnDisparar) {
            btnDisparar.disabled = false;
            btnDisparar.classList.remove('hidden');
            btnDisparar.innerHTML = '<span>🚀 Enviar para Maquininha</span>';
        }
        document.getElementById('btnCancelarPoint')?.classList.add('hidden');
        document.getElementById('mpPixQrCodeContainer').innerHTML = '<div class="text-slate-500 text-xs font-bold py-6 text-center">Clique abaixo para gerar o Pix Dinâmico com baixa automática</div>';
        document.getElementById('mpPixCopiaColaContainer')?.classList.add('hidden');
        document.getElementById('mpPixStatusWaiting')?.classList.add('hidden');
        const btnGerar = document.getElementById('btnGerarPixMp');
        if (btnGerar) {
            btnGerar.disabled = false;
            btnGerar.classList.remove('hidden');
            btnGerar.innerHTML = '<span>⚡ Gerar Pix Mercado Pago</span>';
        }

        document.getElementById('modalMercadoPagoPDV').classList.remove('hidden');
    }

    function fecharModalMercadoPagoPDV() {
        if (mpPollingInterval) {
            clearInterval(mpPollingInterval);
            mpPollingInterval = null;
        }
        document.getElementById('modalMercadoPagoPDV').classList.add('hidden');
    }

    function trocarAbaMp(aba) {
        const btnPoint = document.getElementById('tabBtnPoint');
        const btnPix = document.getElementById('tabBtnPixMp');
        const conteudoPoint = document.getElementById('mpConteudoPoint');
        const conteudoPix = document.getElementById('mpConteudoPix');

        if (aba === 'point') {
            btnPoint.className = 'flex-1 py-2 rounded-lg transition bg-cyan-500 text-slate-950 shadow';
            btnPix.className = 'flex-1 py-2 rounded-lg transition text-slate-400 hover:text-white';
            conteudoPoint.classList.remove('hidden');
            conteudoPix.classList.add('hidden');
        } else {
            btnPix.className = 'flex-1 py-2 rounded-lg transition bg-cyan-500 text-slate-950 shadow';
            btnPoint.className = 'flex-1 py-2 rounded-lg transition text-slate-400 hover:text-white';
            conteudoPix.classList.remove('hidden');
            conteudoPoint.classList.add('hidden');
        }
    }

    async function dispararCobrancaPoint() {
        const selectDevice = document.getElementById('mpSelectDevice');
        const deviceId = selectDevice ? selectDevice.value : '';

        if (!deviceId) {
            alert('Por favor, selecione uma maquininha Point ou cadastre o número de série em Configurações > Mercado Pago.');
            return;
        }

        const totalNum = getTotalVendaNumerico();
        if (totalNum <= 0) {
            alert('Valor da venda inválido.');
            return;
        }

        const btnDisparar = document.getElementById('btnDispararPoint');
        const btnCancelar = document.getElementById('btnCancelarPoint');
        const statusDiv = document.getElementById('mpStatusTerminal');
        const statusTexto = document.getElementById('mpStatusTerminalTexto');

        btnDisparar.disabled = true;
        btnDisparar.innerHTML = '<span>⏳ Enviando comando ao terminal...</span>';

        try {
            const resp = await fetch(`${baseUrlApp}/index.php/api/mercado-pago/criar-pagamento-point`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    tenant_id: lojaIdAtual,
                    device_id: deviceId,
                    amount: totalNum,
                    order_id: 'PDV-' + Date.now()
                })
            });

            const data = await resp.json();
            if (!resp.ok || !data.sucesso) {
                throw new Error(data.message || 'Falha ao enviar cobrança para a maquininha Point.');
            }

            mpIntentIdAtual = data.data?.id;
            btnDisparar.classList.add('hidden');
            btnCancelar.classList.remove('hidden');
            statusDiv.classList.remove('hidden');
            statusTexto.textContent = 'Cobrança enviada! Aguardando o cliente passar o cartão...';

            iniciarPollingPoint(mpIntentIdAtual, deviceId);
        } catch (e) {
            alert('Erro ao acionar Maquininha: ' + e.message);
            btnDisparar.disabled = false;
            btnDisparar.innerHTML = '<span>🚀 Enviar para Maquininha</span>';
        }
    }

    function iniciarPollingPoint(intentId, deviceId) {
        if (mpPollingInterval) clearInterval(mpPollingInterval);

        mpPollingInterval = setInterval(async () => {
            try {
                const resp = await fetch(`${baseUrlApp}/index.php/api/mercado-pago/consultar-status-point?intent_id=${intentId}&tenant_id=${lojaIdAtual}`);
                const data = await resp.json();

                if (data.sucesso && data.status) {
                    const st = data.status.toUpperCase();
                    if (st === 'FINISHED' || st === 'PROCESSED') {
                        clearInterval(mpPollingInterval);
                        mpPollingInterval = null;
                        document.getElementById('mpStatusTerminalTexto').textContent = '✅ Pagamento Aprovado na Maquininha!';
                        setTimeout(() => {
                            fecharModalMercadoPagoPDV();
                            efetivarVendaExpressa();
                        }, 1000);
                    } else if (['CANCELED', 'CANCELLED', 'FAILED', 'ABORTED', 'ERROR'].includes(st)) {
                        clearInterval(mpPollingInterval);
                        mpPollingInterval = null;
                        alert('Pagamento cancelado ou recusado na maquininha.');
                        document.getElementById('mpStatusTerminal')?.classList.add('hidden');
                        const btnDisparar = document.getElementById('btnDispararPoint');
                        if (btnDisparar) {
                            btnDisparar.disabled = false;
                            btnDisparar.classList.remove('hidden');
                            btnDisparar.innerHTML = '<span>🚀 Enviar para Maquininha</span>';
                        }
                        document.getElementById('btnCancelarPoint')?.classList.add('hidden');
                    }
                }
            } catch (err) {
                console.warn('Erro ao checar status Point:', err);
            }
        }, 2000);
    }

    async function cancelarCobrancaPoint() {
        const selectDevice = document.getElementById('mpSelectDevice');
        const deviceId = selectDevice ? selectDevice.value : '';

        if (!mpIntentIdAtual || !deviceId) return;

        if (mpPollingInterval) {
            clearInterval(mpPollingInterval);
            mpPollingInterval = null;
        }

        try {
            await fetch(`${baseUrlApp}/index.php/api/mercado-pago/cancelar-pagamento-point`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    tenant_id: lojaIdAtual,
                    device_id: deviceId,
                    intent_id: mpIntentIdAtual
                })
            });
        } catch (e) {
            console.error('Erro ao cancelar:', e);
        }

        document.getElementById('mpStatusTerminal')?.classList.add('hidden');
        const btnDisparar = document.getElementById('btnDispararPoint');
        if (btnDisparar) {
            btnDisparar.disabled = false;
            btnDisparar.classList.remove('hidden');
            btnDisparar.innerHTML = '<span>🚀 Enviar para Maquininha</span>';
        }
        document.getElementById('btnCancelarPoint')?.classList.add('hidden');
    }

    async function gerarPixDinamicoMp() {
        const totalNum = getTotalVendaNumerico();
        if (totalNum <= 0) {
            alert('Valor da venda inválido.');
            return;
        }

        const btnGerar = document.getElementById('btnGerarPixMp');
        btnGerar.disabled = true;
        btnGerar.innerHTML = '<span>⏳ Gerando Pix Mercado Pago...</span>';

        const orderIdTemporario = 'PDV-' + Date.now();

        try {
            const resp = await fetch(`${baseUrlApp}/index.php/api/mercado-pago/criar-pagamento-pix-split`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    tenant_id: lojaIdAtual,
                    order_id: orderIdTemporario,
                    amount: totalNum,
                    description: 'Venda Expressa PDV'
                })
            });

            const data = await resp.json();
            if (!resp.ok || !data.sucesso) {
                throw new Error(data.message || 'Falha ao gerar Pix dinâmico.');
            }

            mpPaymentIdAtual = data.payment_id;

            const containerQr = document.getElementById('mpPixQrCodeContainer');
            containerQr.innerHTML = '';
            if (data.qr_code_base64) {
                const img = document.createElement('img');
                img.src = 'data:image/png;base64,' + data.qr_code_base64;
                img.className = 'w-48 h-48 rounded-xl border border-slate-200 shadow-md';
                containerQr.appendChild(img);
            } else if (data.qr_code && typeof QRCode !== 'undefined') {
                new QRCode(containerQr, {
                    text: data.qr_code,
                    width: 190,
                    height: 190
                });
            } else {
                containerQr.innerHTML = '<div class="text-xs text-slate-700 font-bold p-4">QR Code pronto! Utilize o Copia e Cola abaixo.</div>';
            }

            if (data.qr_code) {
                document.getElementById('mpPixCodigo').value = data.qr_code;
                document.getElementById('mpPixCopiaColaContainer').classList.remove('hidden');
            }

            btnGerar.classList.add('hidden');
            document.getElementById('mpPixStatusWaiting').classList.remove('hidden');

            iniciarPollingPixMp(mpPaymentIdAtual);
        } catch (e) {
            alert('Erro ao gerar Pix Mercado Pago: ' + e.message);
            btnGerar.disabled = false;
            btnGerar.innerHTML = '<span>⚡ Gerar Pix Mercado Pago</span>';
        }
    }

    function iniciarPollingPixMp(paymentId) {
        if (mpPollingInterval) clearInterval(mpPollingInterval);

        mpPollingInterval = setInterval(async () => {
            try {
                const resp = await fetch(`${baseUrlApp}/index.php/api/mercado-pago/consultar-status-pix?payment_id=${paymentId}&tenant_id=${lojaIdAtual}`);
                const data = await resp.json();

                if (data.sucesso && data.status) {
                    if (data.status === 'approved') {
                        clearInterval(mpPollingInterval);
                        mpPollingInterval = null;
                        document.getElementById('mpPixStatusWaiting').textContent = '✅ Pagamento Pix Confirmado!';
                        document.getElementById('mpPixStatusWaiting').className = 'text-center text-sm font-black text-emerald-400';
                        setTimeout(() => {
                            fecharModalMercadoPagoPDV();
                            efetivarVendaExpressa();
                        }, 1000);
                    }
                }
            } catch (err) {
                console.warn('Erro ao checar status Pix:', err);
            }
        }, 2500);
    }

    function copiarCodigoPixMp() {
        const textarea = document.getElementById('mpPixCodigo');
        if (!textarea || !textarea.value) return;
        textarea.select();
        navigator.clipboard.writeText(textarea.value).then(() => {
            const btn = document.getElementById('btnCopiarPixMp');
            btn.innerHTML = '<span>✅ Código Copiado!</span>';
            setTimeout(() => {
                btn.innerHTML = '<span>📋 Copiar Código PIX</span>';
            }, 2000);
        });
    }

    function concluirVendaMercadoPagoManualmente() {
        if (confirm('Deseja registrar a venda diretamente sem acionar o terminal/Pix do Mercado Pago?')) {
            fecharModalMercadoPagoPDV();
            efetivarVendaExpressa();
        }
    }

    function restaurarBotaoEfetivarVenda() {
        const totalStr = document.getElementById('displayTotalFinal').textContent || '0,00';
        const btn = document.getElementById('btnEfetivarVenda');
        btn.disabled = false;
        btn.innerHTML = `<span>⚡ Efetivar Venda (R$ <span id="totalFinalBtn">${totalStr}</span>)</span>`;
    }

    function efetivarVendaExpressa() {
        const lista = Object.values(itensVendaMap);
        if (lista.length === 0) {
            alert('Adicione pelo menos um produto antes de efetivar a venda.');
            return;
        }

        const btn = document.getElementById('btnEfetivarVenda');
        btn.disabled = true;
        btn.innerHTML = '⚡ Registrando Venda Expressa...';

        const payloadItens = lista.map(item => ({
            produto_id: item.produto_id || item.id,
            variante_id: item.variante_id || null,
            quantidade: item.qtd,
            preco_unitario: item.precoVal
        }));

        const clienteNomeInput = document.getElementById('clienteNome').value;
        const clienteCpfInput = document.getElementById('clienteCpf').value;
        const clienteWhatsappInput = document.getElementById('clienteWhatsapp').value;

        const usarMultiplos = document.getElementById('usar-multiplos-pagamentos')?.checked || false;
        const pagamentosMultiplosArray = usarMultiplos ? coletarPagamentosMultiplos() : [];

        const ehFiado = isFormaBoletoFiado(formaPagamentoSelecionadaNome);
        const dataVencInput = document.getElementById('dataVencimentoFiado')?.value || '';

        const payload = {
            itens: payloadItens,
            forma_pagamento_id: usarMultiplos && pagamentosMultiplosArray.length > 0 ? pagamentosMultiplosArray[0].forma_pagamento_id : formaPagamentoSelecionadaId,
            a_prazo: ehFiado,
            data_vencimento: dataVencInput,
            observacoes: document.getElementById('inputObservacoes').value,
            cliente_nome: clienteNomeInput,
            cliente_cpf: clienteCpfInput,
            cliente_whatsapp: clienteWhatsappInput,
            desconto_valor: document.getElementById('descontoGeral').value,
            desconto_tipo: document.getElementById('descontoTipo').value,
            acrescimo_valor: document.getElementById('acrescimoGeral').value,
            acrescimo_tipo: document.getElementById('acrescimoTipo').value,
            pagamentos_multiplos: usarMultiplos ? pagamentosMultiplosArray : [],
            '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
        };

        fetch('<?= Url::to(['/vendas/venda-expressa/salvar']) ?>', {
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
            restaurarBotaoEfetivarVenda();

            if (data.success) {
                // Guarda dados da venda para o comprovante
                dadosUltimaVendaFinalizada = {
                    ...data,
                    venda_id: data.venda_id,
                    valor_total: data.valor_total,
                    subtotal_bruto: data.subtotal_bruto,
                    total_desconto: data.total_desconto,
                    acrescimo_valor: data.acrescimo_valor,
                    acrescimo_tipo: data.acrescimo_tipo || payload.acrescimo_tipo,
                    cliente_nome: data.cliente_nome || clienteNomeInput || 'Cliente Balcão',
                    cliente_telefone: data.cliente_telefone || clienteWhatsappInput || '',
                    data_hora: new Date().toLocaleString('pt-BR'),
                    forma_pagamento: data.forma_pagamento || formaPagamentoSelecionadaNome,
                    a_prazo: data.a_prazo || ehFiado,
                    data_vencimento: data.data_vencimento || dataVencInput,
                    status_venda: data.status_venda || (ehFiado ? 'EM_ABERTO' : 'QUITADA'),
                    observacoes: data.observacoes || document.getElementById('inputObservacoes').value,
                    itens: (data.itens && data.itens.length > 0) ? data.itens : [...lista]
                };

                // Atualizar Indicadores de Venda em Tempo Real
                if (data.resumoHoje) {
                    document.getElementById('resumoValor').textContent = data.resumoHoje.valor_total;
                    document.getElementById('resumoQtd').textContent = data.resumoHoje.total_vendas;
                    document.getElementById('resumoTop').textContent = data.resumoHoje.top_produto;
                }

                // Esvaziar carrinho de entrada
                limparItensVenda();
                document.getElementById('inputObservacoes').value = '';
                document.getElementById('clienteNome').value = '';
                document.getElementById('clienteCpf').value = '';
                document.getElementById('clienteWhatsapp').value = '';

                // Abrir Modal de Comprovante de Venda com disparo por Evolution API
                exibirModalComprovanteVenda(dadosUltimaVendaFinalizada);

            } else {
                alert('Erro ao registrar venda: ' + (data.message || 'Falha na conexão.'));
            }
        })
        .catch(err => {
            restaurarBotaoEfetivarVenda();
            alert('Erro ao comunicar com o servidor: ' + err.message);
        });
    }

    function formatarQtdDisplay(qtd) {
        const num = parseFloat(qtd) || 0;
        if (Math.abs(num - Math.round(num)) < 0.0001) {
            return num.toString();
        }
        return num.toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 3 });
    }

    function exibirModalComprovanteVenda(vendaData) {
        const container = document.getElementById('comprovanteReciboContainer');
        const modal = document.getElementById('modalComprovanteVenda');

        let subtotalBruto = 0;
        let totalDescontosItens = 0;
        let totalPecas = 0;

        let itensHtml = (vendaData.itens || []).map(i => {
            const qtd = parseFloat(i.qtd || i.quantidade) || 0;
            const unit = parseFloat(i.preco_unitario || i.precoVal) || 0;
            const subBrutoItem = qtd * unit;
            subtotalBruto += subBrutoItem;
            totalPecas += qtd;

            const descVal = parseFloat(i.desconto_valor) || 0;
            const descPerc = parseFloat(i.desconto_percentual) || 0;
            totalDescontosItens += descVal;

            let descHtml = '';
            if (descVal > 0.009) {
                let percText = descPerc > 0 ? `${descPerc.toFixed(2)}%` : '';
                descHtml = `
                    <div class="flex justify-between items-center text-[10px] italic text-slate-500 pl-2">
                        <span>(-) DESCONTO ${percText}</span>
                        <span>- R$ ${descVal.toFixed(2).replace('.', ',')}</span>
                    </div>
                `;
            }

            return `
                <div class="py-1.5 border-b border-dashed border-slate-200">
                    <div class="font-bold text-slate-900 text-xs uppercase leading-tight">${i.nome}</div>
                    <div class="flex justify-between items-center text-[11px] text-slate-700 mt-0.5">
                        <span>${formatarQtdDisplay(qtd)} ${i.unidade || 'un'} x R$ ${unit.toFixed(2).replace('.', ',')}</span>
                        <span class="font-bold">R$ ${subBrutoItem.toFixed(2).replace('.', ',')}</span>
                    </div>
                    ${descHtml}
                </div>
            `;
        }).join('');

        // Parse totals
        let totalPagoNum = parseFloat((vendaData.valor_total || '0').toString().replace(/\./g, '').replace(',', '.')) || 0;
        let subtotalBrutoBackend = parseFloat((vendaData.subtotal_bruto || '0').toString().replace(/\./g, '').replace(',', '.')) || subtotalBruto;
        let totalDescontoBackend = parseFloat((vendaData.total_desconto || '0').toString().replace(/\./g, '').replace(',', '.')) || totalDescontosItens;
        let acrescimoValBackend = parseFloat((vendaData.acrescimo_valor || '0').toString().replace(/\./g, '').replace(',', '.')) || 0;

        let valDescontoFinal = Math.max(totalDescontoBackend, totalDescontosItens);
        let valAcrescimoFinal = acrescimoValBackend;

        // Fallback de diferença se backend não retornou explícito
        if (valDescontoFinal <= 0.009 && valAcrescimoFinal <= 0.009) {
            let diferenca = subtotalBrutoBackend - totalPagoNum;
            if (diferenca > 0.009) valDescontoFinal = diferenca;
            else if (diferenca < -0.009) valAcrescimoFinal = Math.abs(diferenca);
        }

        let temAjuste = (valDescontoFinal > 0.009 || valAcrescimoFinal > 0.009 || Math.abs(subtotalBrutoBackend - totalPagoNum) > 0.009);

        container.innerHTML = `
            <div class="text-center border-b border-dashed border-slate-300 pb-3 mb-2 font-mono">
                <h2 class="text-base font-black uppercase text-slate-900 leading-tight">${lojaPixConfig.nome || 'COMPROVANTE DE VENDA'}</h2>
                <p class="text-[10px] text-slate-500 mt-0.5">${lojaPixConfig.cidade || 'Caruaru, PE'} ${vendaData.data_hora ? '• ' + vendaData.data_hora : ''}</p>
                <p class="text-[11px] font-bold text-slate-700 mt-1">VENDA Nº: ${(vendaData.venda_id || '').substring(0, 8).toUpperCase()}</p>
                ${vendaData.cliente_nome ? `<p class="text-[10px] text-slate-600 uppercase mt-0.5">CLIENTE: ${vendaData.cliente_nome}</p>` : ''}
            </div>

            <div class="space-y-1 mb-2 font-mono">
                ${itensHtml}
            </div>

            <div class="border-t border-dashed border-slate-400 pt-2 space-y-1 text-xs font-mono">
                ${temAjuste ? `
                <div class="flex items-center justify-between text-slate-700">
                    <span>SUBTOTAL BRUTO:</span>
                    <span class="font-bold">R$ ${subtotalBrutoBackend.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                </div>` : ''}
                ${valDescontoFinal > 0.009 ? `
                <div class="flex items-center justify-between text-rose-600 font-bold">
                    <span>TOTAL DESCONTOS:</span>
                    <span>- R$ ${valDescontoFinal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                </div>` : ''}
                ${valAcrescimoFinal > 0.009 ? `
                <div class="flex items-center justify-between text-blue-600 font-bold">
                    <span>ACRESCIMO (${vendaData.acrescimo_tipo || 'VALOR'}):</span>
                    <span>+ R$ ${valAcrescimoFinal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                </div>` : ''}
                <div class="flex items-center justify-between font-black text-sm text-slate-900 pt-1 border-t border-slate-300">
                    <span>TOTAL LIQUIDO:</span>
                    <span class="text-emerald-700">R$ ${vendaData.valor_total || totalPagoNum.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                </div>
                <div class="text-[10px] text-slate-500 font-bold pt-0.5 space-y-0.5">
                    <div>TOTAL DE ITENS: ${(vendaData.itens || []).length}</div>
                    <div>TOTAL DE PEÇAS / FRAÇÕES: ${formatarQtdDisplay(totalPecas)}</div>
                </div>
            </div>

            <div class="border-t border-dashed border-slate-300 pt-2 text-[11px] font-mono space-y-1">
                ${(() => {
                    const meios = Array.isArray(vendaData.pagamentos) ? vendaData.pagamentos : [];
                    if (meios.length > 1) {
                        const linhas = meios.map(pg => {
                            const fp = formasPagamentoDisponiveis.find(f => f.id == pg.forma_pagamento_id);
                            const nome = fp ? fp.nome : 'Meio';
                            const val = parseFloat(pg.valor || 0);
                            return `<div class="flex justify-between items-center"><span>${nome}</span><span class="font-bold">R$ ${val.toFixed(2).replace('.', ',')}</span></div>`;
                        }).join('');
                        return `<p><strong>PAGAMENTOS:</strong></p>${linhas}`;
                    }
                    return `<p><strong>FORMA DE PAGAMENTO:</strong> ${vendaData.forma_pagamento || 'Dinheiro'}</p>`;
                })()}
                ${vendaData.a_prazo || vendaData.status_venda === 'EM_ABERTO' ? `
                <div class="bg-amber-50 p-2.5 rounded border border-amber-300 text-amber-950 font-mono text-[11px] space-y-1 my-1">
                    <p class="text-rose-700 font-black uppercase">⚠️ CONDIÇÃO: A PRAZO (BOLETO / FIADO)</p>
                    <p>VENCIMENTO: <strong>${vendaData.data_vencimento || 'A definir'}</strong></p>
                    <p>VALOR A PAGAR: <strong>R$ ${vendaData.valor_total || totalPagoNum.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong></p>
                </div>
                ` : `
                <p class="underline font-bold">VALOR PAGO: R$ ${vendaData.valor_total || totalPagoNum.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>
                `}
            </div>

            ${vendaData.a_prazo || vendaData.status_venda === 'EM_ABERTO' ? `
            <div class="border-t border-dashed border-slate-300 pt-3 text-[10px] font-mono text-center space-y-1 text-slate-700">
                <p class="text-[9px] uppercase text-slate-500 font-semibold">Reconheço a dívida acima e declaro que pagarei na data aprazada:</p>
                <div class="pt-7 border-b border-slate-500 w-4/5 mx-auto"></div>
                <p class="font-bold text-[10px] text-slate-900 uppercase pt-1">${vendaData.cliente_nome || 'Assinatura do Cliente'}</p>
                <p class="text-[8px] text-slate-400 uppercase">Confissão de Dívida</p>
            </div>
            ` : ''}

            ${vendaData.observacoes ? `
            <div class="border-t border-dashed border-slate-300 pt-2 text-[10px] font-mono text-slate-600">
                <p class="font-bold uppercase text-slate-800">OBSERVACOES:</p>
                <p class="break-words">${vendaData.observacoes}</p>
            </div>` : ''}

            <div class="text-center pt-3 border-t border-dashed border-slate-300 text-[10px] text-slate-500 italic font-mono">
                Obrigado pela preferência!<br>
                <span class="font-bold uppercase">${lojaPixConfig.nome || ''}</span>
            </div>
        `;

        modal.classList.remove('hidden');
    }

    function fecharModalComprovanteVenda() {
        document.getElementById('modalComprovanteVenda').classList.add('hidden');
        document.getElementById('inputBuscaProduto').focus();
    }

    function imprimirRecibo80mm() {
        if (dadosUltimaVendaFinalizada && dadosUltimaVendaFinalizada.venda_id) {
            window.open(`<?= Url::to(['/vendas/venda/imprimir']) ?>?id=${dadosUltimaVendaFinalizada.venda_id}`, '_blank');
        }
    }

    async function enviarComprovanteWhatsAppEvolution() {
        if (!dadosUltimaVendaFinalizada) {
            alert('Nenhuma venda finalizada encontrada.');
            return;
        }

        let telPadrao = dadosUltimaVendaFinalizada.cliente_telefone || '';
        let telLimpo = telPadrao.replace(/\D/g, '');
        if (telLimpo.startsWith('55') && telLimpo.length > 11) telLimpo = telLimpo.substring(2);

        let inputTel = prompt("Digite o número de WhatsApp do cliente (com DDD):", telLimpo);
        if (inputTel === null) return;
        let numFinal = inputTel.replace(/\D/g, '');
        if (!numFinal) {
            alert("Número de telefone é obrigatório.");
            return;
        }
        if (numFinal.length <= 11) numFinal = "55" + numFinal;

        const btn = document.getElementById('btnEnviarWhatsappEvolution');
        btn.disabled = true;
        btn.innerHTML = '<span>⏳ Fotografando recibo e enviando via Evolution API...</span>';

        try {
            if (typeof html2canvas === 'undefined') {
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
                    script.onload = resolve;
                    script.onerror = () => reject(new Error('Falha ao carregar html2canvas'));
                    document.head.appendChild(script);
                });
            }

            const container = document.getElementById('comprovanteReciboContainer');
            const canvas = await html2canvas(container, {
                scale: 2,
                useCORS: true,
                backgroundColor: "#ffffff",
                logging: false
            });

            const base64data = canvas.toDataURL("image/jpeg", 0.9);
            const baseUrl = window.location.origin;

            const msgPadrao = dadosUltimaVendaFinalizada.a_prazo
                ? `Olá ${dadosUltimaVendaFinalizada.cliente_nome}! Segue o comprovante da sua compra no valor de R$ ${dadosUltimaVendaFinalizada.valor_total} (Boleto/Fiado com vencimento para ${dadosUltimaVendaFinalizada.data_vencimento}). Obrigado pela preferência!`
                : "Olá! Segue o comprovante da sua compra. Obrigado pela preferência!";

            const response = await fetch(`${baseUrl}/api/whatsapp/send`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": "<?= Yii::$app->request->csrfToken ?>"
                },
                body: JSON.stringify({
                    numero: numFinal,
                    mensagem: msgPadrao,
                    base64: base64data
                })
            });

            const resData = await response.json();
            btn.disabled = false;
            btn.innerHTML = '<span>📱 Enviar Comprovante via WhatsApp (Evolution API)</span>';

            if (response.ok && resData.success) {
                alert('✅ Comprovante enviado com sucesso via WhatsApp / Evolution API!');
            } else {
                alert('❌ Erro ao enviar imagem: ' + (resData.message || resData.name || 'Falha na conexão com a Evolution API'));
            }

        } catch (err) {
            btn.disabled = false;
            btn.innerHTML = '<span>📱 Enviar Comprovante via WhatsApp (Evolution API)</span>';
            alert('❌ Erro de comunicação ao gerar o comprovante: ' + err.message);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        checarExibicaoVencimentoFiado();
    });
</script>
