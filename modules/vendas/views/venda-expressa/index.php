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
                        </h3>
                        <span class="text-[10px] text-slate-400 font-medium">Cadastra e salva na base automaticamente</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- WhatsApp -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1">📱 WhatsApp / Fone</label>
                            <input type="text" id="clienteWhatsapp" placeholder="(81) 99999-9999" oninput="aplicarMascaraTelefone(this)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs font-semibold text-white focus:outline-none focus:border-amber-400">
                        </div>

                        <!-- Nome Completo -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1">👤 Nome Completo</label>
                            <input type="text" id="clienteNome" placeholder="Ex: Maria Silva" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs font-semibold text-white focus:outline-none focus:border-amber-400">
                        </div>

                        <!-- CPF -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1">📄 CPF (Opcional)</label>
                            <input type="text" id="clienteCpf" placeholder="000.000.000-00" oninput="aplicarMascaraCpf(this)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs font-semibold text-white focus:outline-none focus:border-amber-400">
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

                    <!-- Seleção Rápida de Pagamento (Chips 1-Clique) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Forma de Pagamento</label>
                        <div class="grid grid-cols-2 gap-2">
                            <?php foreach ($formasPagamento as $index => $fp): 
                                $nomeExibicao = $fp->nome;
                                if (mb_stripos($nomeExibicao, 'boleto') !== false && mb_stripos($nomeExibicao, 'fiado') === false) {
                                    $nomeExibicao = 'Boleto / Carnê / Fiado';
                                }
                                $isPix = (mb_stripos($nomeExibicao, 'pix') !== false || $index === 0);
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
                                            class="btn-forma-pagamento p-2.5 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5 <?= $isPix ? 'bg-amber-400 text-slate-900 border-amber-300 shadow-md' : 'bg-slate-900 text-slate-300 border-slate-700 hover:bg-slate-700' ?>" 
                                            data-id="<?= $fp->id ?>"
                                            data-tipo="<?= $fp->tipo ?>"
                                            data-nome="<?= Html::encode($nomeExibicao) ?>">
                                        <span><?= Html::encode($nomeExibicao) ?></span>
                                    </button>
                                <?php endif; ?>
                            <?php endforeach; ?>
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

<script>
    const produtosArray = [
        <?php foreach ($produtos as $p): 
            $foto = $p->fotoPrincipal ?: ($p->fotos[0] ?? null);
            $urlFoto = $foto ? Url::to('@web/' . ltrim($foto->arquivo_path, '/'), true) : '';
            $precoStr = number_format($p->preco_venda_sugerido, 2, ',', '.');
        ?>
        {
            id: <?= json_encode($p->id) ?>,
            nome: <?= json_encode($p->nome) ?>,
            marca: <?= json_encode($p->marca ?: '') ?>,
            precoVal: <?= (float)$p->preco_venda_sugerido ?>,
            precoStr: <?= json_encode($precoStr) ?>,
            unidade: <?= json_encode($p->unidade_medida ?: 'UN') ?>,
            estoqueVal: <?= (float)($p->estoque_atual ?? 0) ?>,
            foto: <?= json_encode($urlFoto) ?>
        },
        <?php endforeach; ?>
    ];

    const lojaPixConfig = {
        chave: <?= json_encode($pixChaveConfig) ?>,
        nome: <?= json_encode($pixNomeConfig) ?>,
        cidade: <?= json_encode($pixCidadeConfig) ?>
    };

    let itensVendaMap = {};
    let formaPagamentoSelecionadaId = '<?= count($formasPagamento) > 0 ? $formasPagamento[0]->id : "" ?>';
    let formaPagamentoSelecionadaNome = '<?= count($formasPagamento) > 0 ? Html::encode($formasPagamento[0]->nome) : "" ?>';
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

        const resultados = produtosArray.filter(p => 
            p.nome.toLowerCase().includes(termoClean) || 
            (p.marca && p.marca.toLowerCase().includes(termoClean))
        );

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

            item.innerHTML = `
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    ${prod.foto ? `<img src="${prod.foto}" class="w-9 h-9 object-contain rounded-lg bg-white p-0.5 flex-shrink-0">` : `<div class="w-9 h-9 rounded-lg bg-slate-900 flex items-center justify-center text-[9px] font-bold text-slate-500 flex-shrink-0">FOTO</div>`}
                    <div class="truncate">
                        <div class="font-extrabold text-xs text-white group-hover:text-amber-300 truncate">${nomeHighlighted}</div>
                        <div class="flex items-center gap-2 mt-0.5">
                            ${prod.marca ? `<span class="text-[10px] text-slate-400 font-semibold">${prod.marca}</span>` : ''}
                            ${estoqueBadge}
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

    function selecionarProdutoDireto(prod) {
        if (!itensVendaMap[prod.id]) {
            // Quantidade padrão igual a 1
            itensVendaMap[prod.id] = {
                id: prod.id,
                nome: prod.nome,
                precoVal: prod.precoVal,
                unidade: prod.unidade,
                estoqueVal: prod.estoqueVal,
                foto: prod.foto,
                qtd: 1
            };
        } else {
            itensVendaMap[prod.id].qtd += 1;
        }

        limparBuscaProduto();
        renderizarItensVenda();
        document.getElementById('inputBuscaProduto').focus();
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

    function alterarQtdItem(id, delta) {
        if (itensVendaMap[id]) {
            itensVendaMap[id].qtd += delta;
            if (itensVendaMap[id].qtd <= 0) {
                delete itensVendaMap[id];
            }
            renderizarItensVenda();
        }
    }

    function atualizarQtdDireta(id, valStr) {
        const val = parseFloat(valStr);
        if (!isNaN(val) && val > 0 && itensVendaMap[id]) {
            itensVendaMap[id].qtd = val;
            renderizarItensVenda();
        }
    }

    function atualizarPrecoDireta(id, valStr) {
        const val = parseFloat(valStr.replace(',', '.'));
        if (!isNaN(val) && val >= 0 && itensVendaMap[id]) {
            itensVendaMap[id].precoVal = val;
            renderizarItensVenda();
        }
    }

    function removerItem(id) {
        delete itensVendaMap[id];
        renderizarItensVenda();
    }

    function limparItensVenda() {
        itensVendaMap = {};
        document.getElementById('descontoGeral').value = '';
        document.getElementById('acrescimoGeral').value = '';
        renderizarItensVenda();
    }

    function selecionarFormaPagamento(id, btn) {
        formaPagamentoSelecionadaId = id;
        formaPagamentoSelecionadaNome = btn.getAttribute('data-nome') || btn.innerText;
        document.querySelectorAll('.btn-forma-pagamento').forEach(b => {
            b.className = 'btn-forma-pagamento p-2.5 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5 bg-slate-900 text-slate-300 border-slate-700 hover:bg-slate-700';
        });
        btn.className = 'btn-forma-pagamento p-2.5 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5 bg-amber-400 text-slate-900 border-amber-300 shadow-md';
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
                div.innerHTML = `
                    <div class="flex items-center gap-2.5 flex-1 min-w-0">
                        ${item.foto ? `<img src="${item.foto}" class="w-10 h-10 object-contain rounded-lg bg-white p-0.5 flex-shrink-0">` : `<div class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center text-[9px] font-bold text-slate-500 flex-shrink-0">FOTO</div>`}
                        <div class="truncate">
                            <div class="font-extrabold text-xs text-white truncate flex items-center flex-wrap gap-1">
                                <span>${item.nome}</span>
                                ${avisoEstoque}
                            </div>
                            <div class="flex items-center gap-1 mt-0.5 text-[11px] text-slate-400">
                                <span>R$</span>
                                <input type="text" value="${item.precoVal.toFixed(2).replace('.', ',')}" oninput="aplicarMascaraMoedaInput(this); atualizarPrecoDireta('${item.id}', this.value)" class="w-20 bg-slate-800 text-amber-400 font-bold px-1 py-0.5 rounded border border-slate-700 text-center">
                                <span>/${item.unidade}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Controle de Quantidade -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <div class="flex items-center bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
                            <button type="button" onclick="alterarQtdItem('${item.id}', -1)" class="px-2.5 py-1 text-slate-300 hover:bg-slate-700 font-bold text-sm">-</button>
                            <input type="number" step="any" min="0.01" value="${item.qtd}" onchange="atualizarQtdDireta('${item.id}', this.value)" class="w-12 bg-transparent text-center text-xs font-black text-white focus:outline-none">
                            <button type="button" onclick="alterarQtdItem('${item.id}', 1)" class="px-2.5 py-1 text-slate-300 hover:bg-slate-700 font-bold text-sm">+</button>
                        </div>

                        <!-- Subtotal Item -->
                        <div class="text-right w-20">
                            <div class="text-xs font-montserrat font-black text-emerald-400">R$ ${sub.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                        </div>

                        <!-- Deletar Item -->
                        <button type="button" onclick="removerItem('${item.id}')" class="text-slate-500 hover:text-red-400 p-1">
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
    }

    function iniciarProcessoEfetivacao() {
        const lista = Object.values(itensVendaMap);
        if (lista.length === 0) {
            alert('Adicione pelo menos um produto antes de efetivar a venda.');
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
            produto_id: item.id,
            quantidade: item.qtd,
            preco_unitario: item.precoVal
        }));

        const clienteNomeInput = document.getElementById('clienteNome').value;
        const clienteCpfInput = document.getElementById('clienteCpf').value;
        const clienteWhatsappInput = document.getElementById('clienteWhatsapp').value;

        const payload = {
            itens: payloadItens,
            forma_pagamento_id: formaPagamentoSelecionadaId,
            observacoes: document.getElementById('inputObservacoes').value,
            cliente_nome: clienteNomeInput,
            cliente_cpf: clienteCpfInput,
            cliente_whatsapp: clienteWhatsappInput,
            desconto_valor: document.getElementById('descontoGeral').value,
            desconto_tipo: document.getElementById('descontoTipo').value,
            acrescimo_valor: document.getElementById('acrescimoGeral').value,
            acrescimo_tipo: document.getElementById('acrescimoTipo').value,
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
            btn.disabled = false;
            btn.innerHTML = `<span>⚡ Efetivar Venda (R$ <span id="totalFinalBtn">0,00</span>)</span>`;

            if (data.success) {
                // Guarda dados da venda para o comprovante
                dadosUltimaVendaFinalizada = {
                    venda_id: data.venda_id,
                    valor_total: data.valor_total,
                    cliente_nome: data.cliente_nome || clienteNomeInput || 'Cliente Balcão',
                    cliente_telefone: data.cliente_telefone || clienteWhatsappInput || '',
                    data_hora: new Date().toLocaleString('pt-BR'),
                    forma_pagamento: formaPagamentoSelecionadaNome,
                    itens: [...lista],
                    desconto_valor: payload.desconto_valor,
                    desconto_tipo: payload.desconto_tipo,
                    acrescimo_valor: payload.acrescimo_valor,
                    acrescimo_tipo: payload.acrescimo_tipo
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
            btn.disabled = false;
            btn.innerHTML = `<span>⚡ Efetivar Venda (R$ <span id="totalFinalBtn">0,00</span>)</span>`;
            alert('Erro ao comunicar com o servidor: ' + err.message);
        });
    }

    function exibirModalComprovanteVenda(vendaData) {
        const container = document.getElementById('comprovanteReciboContainer');
        const modal = document.getElementById('modalComprovanteVenda');

        let subtotalBruto = 0;
        let itensHtml = (vendaData.itens || []).map(i => {
            const sub = (parseFloat(i.precoVal) || 0) * (parseFloat(i.qtd) || 0);
            subtotalBruto += sub;
            return `
                <tr class="border-b border-slate-100">
                    <td class="py-1 text-left font-semibold">${i.nome}</td>
                    <td class="py-1 text-center font-bold">${i.qtd} ${i.unidade || 'UN'}</td>
                    <td class="py-1 text-right">R$ ${(parseFloat(i.precoVal) || 0).toFixed(2).replace('.', ',')}</td>
                    <td class="py-1 text-right font-black">R$ ${sub.toFixed(2).replace('.', ',')}</td>
                </tr>
            `;
        }).join('');

        // Converte valor_total pago para número float seguro
        let valorTotalStr = (vendaData.valor_total || '0').toString().replace(/\./g, '').replace(',', '.');
        let totalPagoNum = parseFloat(valorTotalStr) || 0;

        // 1. Extração de Desconto (Explícito ou Rateado)
        let valDesconto = 0;
        let descInputStr = (vendaData.desconto_valor || '0').toString().replace(/\./g, '').replace(',', '.');
        let descInputVal = parseFloat(descInputStr) || 0;
        if (descInputVal > 0) {
            valDesconto = (vendaData.desconto_tipo === 'PERCENTUAL') ? subtotalBruto * (descInputVal / 100) : descInputVal;
        }

        // 2. Extração de Acréscimo (Explícito ou Rateado)
        let valAcrescimo = 0;
        let acresInputStr = (vendaData.acrescimo_valor || '0').toString().replace(/\./g, '').replace(',', '.');
        let acresInputVal = parseFloat(acresInputStr) || 0;
        if (acresInputVal > 0) {
            valAcrescimo = (vendaData.acrescimo_tipo === 'PERCENTUAL') ? subtotalBruto * (acresInputVal / 100) : acresInputVal;
        }

        // 3. Fallback inteligente de diferença líquida se ambos forem 0 mas subtotalBruto != totalPagoNum
        if (valDesconto <= 0.009 && valAcrescimo <= 0.009) {
            let diferenca = subtotalBruto - totalPagoNum;
            if (diferenca > 0.009) {
                valDesconto = diferenca;
            } else if (diferenca < -0.009) {
                valAcrescimo = Math.abs(diferenca);
            }
        }

        // Exibe detalhamento se houver qualquer ajuste (desconto ou acréscimo)
        let temAjuste = (valDesconto > 0.009 || valAcrescimo > 0.009 || Math.abs(subtotalBruto - totalPagoNum) > 0.009);

        container.innerHTML = `
            <div class="text-center border-b border-slate-200 pb-3 mb-3">
                <h2 class="text-base font-black uppercase text-slate-900">${lojaPixConfig.nome || 'COMPROVANTE DE VENDA'}</h2>
                <p class="text-[10px] text-slate-500">${lojaPixConfig.cidade || 'Caruaru/PE'} • ${vendaData.data_hora}</p>
                <p class="text-[10px] font-mono font-bold text-slate-600 mt-0.5">VENDA ID: ${vendaData.venda_id.substring(0, 8).toUpperCase()}</p>
            </div>

            <div class="border-b border-slate-200 pb-2 mb-2 text-[11px]">
                <p><strong>CLIENTE:</strong> ${vendaData.cliente_nome}</p>
                ${vendaData.cliente_telefone ? `<p><strong>WHATSAPP:</strong> ${vendaData.cliente_telefone}</p>` : ''}
                <p><strong>FORMA DE PAGAMENTO:</strong> ${vendaData.forma_pagamento}</p>
            </div>

            <table class="w-full text-[11px] mb-3">
                <thead>
                    <tr class="border-b-2 border-slate-300 uppercase text-[9px] text-slate-500 font-bold">
                        <th class="text-left py-1">Item</th>
                        <th class="text-center py-1">Qtd</th>
                        <th class="text-right py-1">Vlr Unit</th>
                        <th class="text-right py-1">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    ${itensHtml}
                </tbody>
            </table>

            <div class="border-t-2 border-slate-900 pt-2 space-y-1 text-xs">
                ${temAjuste ? `
                <div class="flex items-center justify-between text-slate-600 font-semibold">
                    <span>SUBTOTAL BRUTO:</span>
                    <span class="font-bold text-slate-800">R$ ${subtotalBruto.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                </div>` : ''}
                ${valDesconto > 0.009 ? `
                <div class="flex items-center justify-between text-rose-600 font-bold">
                    <span>(-) DESCONTO APLICADO:</span>
                    <span>- R$ ${valDesconto.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                </div>` : ''}
                ${valAcrescimo > 0.009 ? `
                <div class="flex items-center justify-between text-blue-600 font-bold">
                    <span>(+) ACRÉSCIMO APLICADO:</span>
                    <span>+ R$ ${valAcrescimo.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                </div>` : ''}
                <div class="border-t border-slate-300 pt-1.5 flex items-center justify-between font-black text-sm">
                    <span>TOTAL PAGO:</span>
                    <span class="text-emerald-700">R$ ${vendaData.valor_total}</span>
                </div>
            </div>

            <div class="text-center pt-3 border-t border-slate-200 text-[10px] text-slate-500 italic">
                Obrigado pela preferência! Venda registrada no ERP.
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

            const response = await fetch(`${baseUrl}/api/whatsapp/send`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": "<?= Yii::$app->request->csrfToken ?>"
                },
                body: JSON.stringify({
                    numero: numFinal,
                    mensagem: "Olá! Segue o comprovante da sua compra. Obrigado pela preferência!",
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
</script>
