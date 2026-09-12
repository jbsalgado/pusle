<?php
use yii\helpers\Url;
use yii\helpers\Html;
use app\modules\vendas\models\Categoria;

$lojaId = $lojaId ?? (\class_exists('\app\components\TenantHelper') ? \app\components\TenantHelper::getId() : (Yii::$app->user->id ?? null));

$categorias = Categoria::find()
    ->where(['usuario_id' => $lojaId, 'ativo' => true])
    ->orderBy(['nome' => SORT_ASC])
    ->all();
// Top 4 categorias para chips rápidos
$topCategorias = array_slice($categorias, 0, 4);
?>

<!-- Modal Cadastro Rápido Mobile-First / Acessibilidade para Idosos -->
<div id="modalCadastroRapido" class="fixed inset-0 z-[120] hidden bg-slate-950/80 backdrop-blur-sm flex flex-col justify-end sm:justify-center items-center p-0 sm:p-4 overflow-hidden transition-all duration-300">
    <div class="bg-white rounded-t-[32px] sm:rounded-3xl shadow-2xl w-full sm:max-w-xl text-slate-900 border border-slate-100 relative flex flex-col max-h-[94vh] sm:max-h-[90vh] overflow-hidden transition-transform">
        
        <!-- Puxador tátil superior para mobile -->
        <div class="pt-2.5 pb-1 sm:hidden flex justify-center flex-shrink-0 bg-slate-50 cursor-grab" onclick="fecharModalCadastroRapido()">
            <div class="w-12 h-1.5 bg-slate-300 rounded-full"></div>
        </div>

        <!-- Cabeçalho de Alto Contraste -->
        <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 text-white p-4 sm:p-5 flex items-center justify-between flex-shrink-0 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-white/20 flex items-center justify-center text-2xl font-black shadow-inner">
                    ⚡
                </div>
                <div>
                    <h3 class="font-black text-lg sm:text-xl leading-tight">Cadastro Rápido</h3>
                    <p class="text-xs sm:text-sm text-emerald-100 font-medium">Cadastre seu produto de forma simples e rápida</p>
                </div>
            </div>
            <button type="button" onclick="fecharModalCadastroRapido()" aria-label="Fechar" class="w-11 h-11 flex items-center justify-center text-white/80 hover:text-white bg-black/20 hover:bg-black/30 active:scale-95 rounded-2xl transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Estado de Sucesso (Exibido após salvar) -->
        <div id="sucessoCadastroRapido" class="hidden p-6 sm:p-8 text-center flex-col items-center justify-center space-y-5 overflow-y-auto flex-1">
            <div class="w-20 h-20 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-4xl shadow-inner mx-auto animate-bounce">
                ✓
            </div>
            <div>
                <h4 class="text-xl sm:text-2xl font-black text-slate-900">Produto Cadastrado com Sucesso!</h4>
                <p id="msgSucessoNomeProduto" class="text-base text-slate-600 font-bold mt-1.5"></p>
                <p id="msgSucessoDetalhesGrade" class="text-xs sm:text-sm text-emerald-700 font-bold mt-1"></p>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">O produto já está disponível para vendas e encartes.</p>
            </div>

            <!-- Preview do produto recém salvo -->
            <div id="previewCardRecemSalvo" class="w-full max-w-sm bg-slate-50 border border-slate-200 rounded-2xl p-3.5 flex items-center gap-3.5 mx-auto text-left shadow-xs">
                <div id="imgRecemSalvo" class="w-16 h-16 rounded-xl bg-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center text-2xl">
                    📦
                </div>
                <div class="flex-1 min-w-0">
                    <p id="nomeRecemSalvo" class="text-sm font-bold text-slate-900 truncate"></p>
                    <p id="precoRecemSalvo" class="text-base font-black text-emerald-600"></p>
                    <p id="tamanhosRecemSalvo" class="text-xs text-indigo-700 font-bold mt-0.5 truncate"></p>
                </div>
            </div>

            <!-- Ações após salvar -->
            <div class="w-full max-w-sm space-y-2.5 pt-2">
                <button type="button" onclick="reiniciarFormCadastroRapido()" class="w-full py-4 px-5 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white font-extrabold text-base rounded-2xl shadow-lg transition flex items-center justify-center gap-2">
                    <span class="text-xl">📸</span>
                    <span>Cadastrar Mais Um Produto</span>
                </button>
                <button type="button" onclick="concluirEFecharCadastroRapido()" class="w-full py-3.5 px-5 bg-slate-100 hover:bg-slate-200 active:scale-[0.98] text-slate-700 font-bold text-sm rounded-2xl transition">
                    Concluir e Voltar
                </button>
            </div>
        </div>

        <!-- Formulário Principal -->
        <form id="formCadastroRapido" onsubmit="salvarProdutoRapido(event, false)" class="p-4 sm:p-6 space-y-5 overflow-y-auto flex-1 overscroll-contain">
            
            <!-- 1. FOTO DO PRODUTO (Câmera ao Vivo Real e Galeria) -->
            <div class="bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl p-4 transition hover:border-emerald-500/70">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm sm:text-base font-black text-slate-800 uppercase tracking-wide flex items-center gap-2">
                        <span>📸</span>
                        <span>Foto do Produto</span>
                    </label>
                    <span class="text-xs font-semibold text-slate-500">Recomendado</span>
                </div>

                <!-- Visor da Câmera ao Vivo (WebRTC) -->
                <div id="containerCameraAoVivo" class="hidden mb-3 p-3 bg-black rounded-2xl relative flex flex-col items-center shadow-lg">
                    <div class="relative w-full aspect-video max-h-60 rounded-xl overflow-hidden bg-black flex items-center justify-center">
                        <video id="videoCameraAoVivo" autoplay playsinline muted class="w-full h-full object-cover"></video>
                        <canvas id="canvasCameraSnapshot" class="hidden"></canvas>
                    </div>

                    <!-- Botões de Controle da Câmera -->
                    <div class="flex items-center justify-between gap-2.5 mt-3 w-full px-1">
                        <button type="button" onclick="alternarCameraAoVivo()" class="px-3.5 py-2.5 bg-white/20 hover:bg-white/30 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 active:scale-95">
                            <span class="text-base">🔄</span>
                            <span>Virar</span>
                        </button>

                        <button type="button" onclick="capturarFotoCameraAoVivo()" class="h-13 sm:h-14 px-5 bg-emerald-500 hover:bg-emerald-600 active:bg-emerald-700 text-white font-black rounded-2xl shadow-lg active:scale-95 transition flex items-center gap-2 text-sm sm:text-base">
                            <span class="text-xl">📸</span>
                            <span>Tirar Foto Agora</span>
                        </button>

                        <button type="button" onclick="fecharCameraAoVivo()" class="px-3.5 py-2.5 bg-red-600/80 hover:bg-red-600 text-white rounded-xl text-xs font-bold transition active:scale-95">
                            <span>✕ Cancelar</span>
                        </button>
                    </div>
                </div>

                <!-- Preview Grande da Foto Selecionada/Capturada -->
                <div id="previewFotoPrincipalContainer" class="hidden mb-3 relative rounded-2xl overflow-hidden border-2 border-emerald-500 bg-white aspect-video max-h-48 flex items-center justify-center group shadow-sm">
                    <img id="imgPreviewPrincipal" src="" alt="Foto Principal" class="w-full h-full object-contain">
                    <div class="absolute top-2 left-2 bg-emerald-600 text-white text-[11px] font-black px-2.5 py-1 rounded-full uppercase shadow">
                        Foto Principal
                    </div>
                    <button type="button" onclick="removerFotoPrincipal()" class="absolute top-2 right-2 bg-red-600/90 hover:bg-red-700 text-white p-2 rounded-xl shadow active:scale-95 transition flex items-center gap-1 text-xs font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Trocar</span>
                    </button>
                </div>

                <!-- Botões de Ação para Foto (Amigáveis para Idosos) -->
                <div id="botoesEscolhaFoto" class="grid grid-cols-2 gap-2.5">
                    <!-- Botão 1: Aciona Câmera ao Vivo Real -->
                    <button type="button" onclick="abrirCameraOuFallback()" class="h-14 sm:h-16 bg-white hover:bg-emerald-50 active:bg-emerald-100 border-2 border-emerald-500/50 hover:border-emerald-600 rounded-2xl flex items-center justify-center gap-2.5 px-3 shadow-xs active:scale-95 transition text-slate-800">
                        <span class="text-2xl">📸</span>
                        <div class="text-left">
                            <span class="block text-xs sm:text-sm font-black text-emerald-800 leading-tight">Tirar Foto</span>
                            <span class="block text-[10px] sm:text-xs text-slate-500 font-medium">Abrir Câmera</span>
                        </div>
                    </button>

                    <!-- Botão 2: Galeria do Celular/PC -->
                    <button type="button" onclick="document.getElementById('rapido_fotos_galeria').click()" class="h-14 sm:h-16 bg-white hover:bg-slate-100 active:bg-slate-200 border-2 border-slate-200 hover:border-slate-400 rounded-2xl flex items-center justify-center gap-2.5 px-3 shadow-xs active:scale-95 transition text-slate-800">
                        <span class="text-2xl">🖼️</span>
                        <div class="text-left">
                            <span class="block text-xs sm:text-sm font-black text-slate-800 leading-tight">Galeria</span>
                            <span class="block text-[10px] sm:text-xs text-slate-500 font-medium">Escolher foto</span>
                        </div>
                    </button>
                </div>

                <!-- Inputs Nativos Ocultos -->
                <input type="file" id="rapido_foto_camera_fallback" accept="image/*" capture="environment" class="hidden" onchange="adicionarFotoRapida(this)">
                <input type="file" id="rapido_fotos_galeria" multiple accept="image/*" class="hidden" onchange="adicionarFotoRapida(this)">

                <!-- Miniaturas adicionais caso selecione várias fotos -->
                <div id="containerMiniaturasExtras" class="flex items-center gap-2 overflow-x-auto pt-2.5 empty:hidden"></div>
            </div>

            <!-- 2. NOME DO PRODUTO (Grande e Legível) -->
            <div>
                <label for="rapido_nome" class="block text-sm sm:text-base font-black text-slate-800 uppercase tracking-wide mb-1.5 flex items-center justify-between">
                    <span>🏷️ Nome do Produto <span class="text-red-500">*</span></span>
                    <span class="text-xs font-normal text-slate-400">Obrigatório</span>
                </label>
                <input type="text" id="rapido_nome" required placeholder="Ex: Camisa Polo Azul, Arroz 5kg, Tênis..." autocomplete="off" class="w-full h-14 px-4 bg-slate-50 border-2 border-slate-200 rounded-2xl text-base sm:text-lg font-bold text-slate-900 placeholder:text-slate-400 placeholder:font-normal focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 focus:outline-none transition">
            </div>

            <!-- 3. PREÇO DE VENDA (Gigante e com Máscara Automática de Centavos) -->
            <div>
                <label for="rapido_preco" class="block text-sm sm:text-base font-black text-slate-800 uppercase tracking-wide mb-1.5 flex items-center justify-between">
                    <span>💰 Preço de Venda (R$) <span class="text-red-500">*</span></span>
                    <span class="text-xs font-normal text-slate-400">Valor ao cliente</span>
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-black text-emerald-600 pointer-events-none">R$</span>
                    <input type="text" id="rapido_preco" required placeholder="0,00" inputmode="numeric" oninput="aplicarMascaraMoedaModal(this)" class="w-full h-16 pl-14 pr-4 bg-slate-50 border-2 border-slate-200 rounded-2xl text-2xl sm:text-3xl font-black text-emerald-600 placeholder:text-slate-300 focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 focus:outline-none transition tracking-tight">
                </div>
                <p class="text-xs text-slate-500 mt-1 font-medium">Basta digitar os números que os centavos se ajustam automaticamente.</p>
            </div>

            <!-- 4. COMO É VENDIDO? (CHIPS TÁTEIS DE 1 TOQUE) -->
            <div>
                <label class="block text-sm sm:text-base font-black text-slate-800 uppercase tracking-wide mb-2 flex items-center gap-1.5">
                    <span>📦</span>
                    <span>Como é Vendido? (Unidade)</span>
                </label>
                <div class="grid grid-cols-3 sm:grid-cols-5 gap-2" id="gridUnidadesChips">
                    <button type="button" onclick="selecionarUnidadeRapida('UN', this)" class="btn-chip-unidade h-12 bg-emerald-500 text-white font-black text-sm rounded-xl border-2 border-emerald-500 shadow-xs flex items-center justify-center gap-1 active:scale-95 transition">
                        <span>📦</span> <span>UN</span>
                    </button>
                    <button type="button" onclick="selecionarUnidadeRapida('KG', this)" class="btn-chip-unidade h-12 bg-slate-50 text-slate-700 font-bold text-sm rounded-xl border-2 border-slate-200 hover:border-slate-300 shadow-xs flex items-center justify-center gap-1 active:scale-95 transition">
                        <span>⚖️</span> <span>KG</span>
                    </button>
                    <button type="button" onclick="selecionarUnidadeRapida('PCT', this)" class="btn-chip-unidade h-12 bg-slate-50 text-slate-700 font-bold text-sm rounded-xl border-2 border-slate-200 hover:border-slate-300 shadow-xs flex items-center justify-center gap-1 active:scale-95 transition">
                        <span>🛍️</span> <span>PCT</span>
                    </button>
                    <button type="button" onclick="selecionarUnidadeRapida('L', this)" class="btn-chip-unidade h-12 bg-slate-50 text-slate-700 font-bold text-sm rounded-xl border-2 border-slate-200 hover:border-slate-300 shadow-xs flex items-center justify-center gap-1 active:scale-95 transition">
                        <span>🥤</span> <span>Litro</span>
                    </button>
                    <button type="button" onclick="mostrarMaisUnidades(this)" class="btn-chip-unidade col-span-2 sm:col-span-1 h-12 bg-slate-50 text-slate-600 font-bold text-sm rounded-xl border-2 border-slate-200 hover:border-slate-300 shadow-xs flex items-center justify-center gap-1 active:scale-95 transition">
                        <span>➕</span> <span>Outro...</span>
                    </button>
                </div>

                <!-- Select de Fallback para Outras Unidades (Oculto até clicar em Outro) -->
                <div id="wrapperUnidadeOutro" class="hidden mt-2">
                    <select id="rapido_unidade_select" onchange="document.getElementById('rapido_unidade').value = this.value" class="w-full h-12 px-3.5 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm font-bold text-slate-800 focus:bg-white focus:border-emerald-500 focus:outline-none">
                        <option value="CX">CX (Caixa)</option>
                        <option value="PAR">PAR (Par)</option>
                        <option value="M">M (Metro)</option>
                        <option value="G">G (Grama)</option>
                        <option value="FD">FD (Fardo)</option>
                    </select>
                </div>
                <input type="hidden" id="rapido_unidade" value="UN">
            </div>

            <!-- 5. CATEGORIA DO PRODUTO -->
            <div>
                <label for="rapido_categoria_id" class="block text-sm sm:text-base font-black text-slate-800 uppercase tracking-wide mb-1.5 flex items-center gap-1.5">
                    <span>📂</span>
                    <span>Categoria do Produto</span>
                </label>

                <!-- Chips das Categorias Mais Usadas -->
                <?php if (!empty($topCategorias)): ?>
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-2 mb-1.5 scrollbar-thin">
                        <button type="button" onclick="selecionarCategoriaChip('', this)" class="btn-chip-categoria px-3 py-1.5 text-xs font-bold rounded-lg border-2 border-emerald-500 bg-emerald-50 text-emerald-800 shrink-0 transition">
                            ⭐ Geral / Ofertas
                        </button>
                        <?php foreach ($topCategorias as $cat): ?>
                            <button type="button" onclick="selecionarCategoriaChip('<?= $cat->id ?>', this)" class="btn-chip-categoria px-3 py-1.5 text-xs font-bold rounded-lg border-2 border-slate-200 bg-slate-50 text-slate-700 shrink-0 hover:bg-slate-100 transition">
                                <?= Html::encode($cat->nome) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <select id="rapido_categoria_id" class="w-full h-13 px-3.5 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm font-bold text-slate-800 focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 focus:outline-none transition">
                    <option value="">Geral / Ofertas</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat->id ?>"><?= Html::encode($cat->nome) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 6. GRADE DE TAMANHOS INTEGRADA (SEM REDIRECIONAMENTO DE TELA!) -->
            <div id="cardGradeTamanhosIntegrada" class="bg-gradient-to-br from-indigo-50 to-purple-50 border-2 border-indigo-200/90 rounded-2xl p-4 transition-all">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-xl flex-shrink-0 shadow-sm">
                            👕
                        </div>
                        <div>
                            <span class="block text-sm sm:text-base font-black text-indigo-950 leading-tight">Tem tamanhos diferentes?</span>
                            <span class="block text-xs text-indigo-700 font-medium">Roupas (P, M, G) ou Calçados</span>
                        </div>
                    </div>
                    
                    <!-- Botão de Ativar Grade em 1 Toque -->
                    <button type="button" id="btnToggleGradeTamanhos" onclick="toggleGradeTamanhos()" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-xs sm:text-sm font-black rounded-xl shadow-xs transition flex items-center gap-1.5 shrink-0">
                        <span id="textoBtnToggleGrade">+ Adicionar Tamanhos</span>
                    </button>
                </div>

                <!-- Painel Expansível de Tamanhos (Abre no mesmo modal) -->
                <div id="painelGradeTamanhos" class="hidden mt-4 pt-3.5 border-t border-indigo-200/80 space-y-3.5">
                    
                    <!-- Presets Rápidos -->
                    <div>
                        <label class="block text-xs font-black uppercase text-indigo-900 mb-1.5 tracking-wider">Escolha rápida por tipo:</label>
                        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-thin">
                            <button type="button" onclick="aplicarPresetGrade(['P', 'M', 'G', 'GG'])" class="btn-preset-grade px-3 py-2 bg-white hover:bg-indigo-100 text-indigo-900 font-black text-xs rounded-xl border-2 border-indigo-200 shadow-xs active:scale-95 transition shrink-0">
                                👕 P, M, G, GG
                            </button>
                            <button type="button" onclick="aplicarPresetGrade(['PP', 'P', 'M', 'G', 'GG', 'XG'])" class="btn-preset-grade px-3 py-2 bg-white hover:bg-indigo-100 text-indigo-900 font-black text-xs rounded-xl border-2 border-indigo-200 shadow-xs active:scale-95 transition shrink-0">
                                👗 PP ao XG
                            </button>
                            <button type="button" onclick="aplicarPresetGrade(['36', '37', '38', '39', '40', '41', '42'])" class="btn-preset-grade px-3 py-2 bg-white hover:bg-indigo-100 text-indigo-900 font-black text-xs rounded-xl border-2 border-indigo-200 shadow-xs active:scale-95 transition shrink-0">
                                👟 Calçados (36 ao 42)
                            </button>
                            <button type="button" onclick="aplicarPresetGrade(['ÚNICO'])" class="btn-preset-grade px-3 py-2 bg-white hover:bg-indigo-100 text-indigo-900 font-black text-xs rounded-xl border-2 border-indigo-200 shadow-xs active:scale-95 transition shrink-0">
                                ✨ Tamanho Único
                            </button>
                        </div>
                    </div>

                    <!-- Lista de Tamanhos Ativos com Steppers de Quantidade -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-black uppercase text-indigo-900">Tamanhos Ativos & Quantidades:</label>
                            <span id="badgeTotalItensGrade" class="text-xs font-black bg-indigo-600 text-white px-2.5 py-0.5 rounded-full">0 peças no total</span>
                        </div>

                        <!-- Grid dinâmico de cards por tamanho -->
                        <div id="gridChipsTamanhosAtivos" class="grid grid-cols-1 sm:grid-cols-2 gap-3 empty:hidden"></div>
                        
                        <p id="msgNenhumTamanhoSelecionado" class="text-xs text-indigo-800/80 italic py-2 text-center bg-white/70 rounded-xl border border-indigo-100">
                            Toque em um dos botões acima ou digite um tamanho abaixo.
                        </p>
                    </div>

                    <!-- Inclusão de Tamanho Avulso -->
                    <div class="flex items-center gap-2 pt-1">
                        <input type="text" id="inputNovoTamanhoAvulso" placeholder="Outro tamanho (Ex: 44, G1, 38...)" class="flex-1 h-11 px-3.5 bg-white border-2 border-indigo-200 rounded-xl text-xs sm:text-sm font-bold text-slate-800 uppercase focus:border-indigo-500 focus:outline-none">
                        <button type="button" onclick="adicionarTamanhoAvulso()" class="h-11 px-4 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-xs font-black rounded-xl transition shrink-0">
                            + Incluir
                        </button>
                    </div>
                </div>
            </div>

            <!-- 7. MAIS DETALHES OPCIONAIS (GAVETA EXPANSÍVEL ACCORDION) -->
            <details class="group bg-slate-50 border-2 border-slate-200 rounded-2xl p-3.5 transition">
                <summary class="font-black text-xs sm:text-sm text-slate-700 cursor-pointer hover:text-emerald-700 flex items-center justify-between list-none select-none">
                    <span class="flex items-center gap-2">
                        <span>➕</span>
                        <span>Mais Detalhes Opcionais (Estoque, Custo, Cód. Barras)</span>
                    </span>
                    <span class="text-xs text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                </summary>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3.5 mt-2 border-t border-slate-200">
                    <div id="blocoEstoqueSimples">
                        <label for="rapido_estoque" class="block text-xs font-bold text-slate-700 uppercase mb-1">Estoque Inicial</label>
                        <input type="number" id="rapido_estoque" placeholder="0" min="0" step="1" class="w-full h-12 px-3 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label for="rapido_preco_custo" class="block text-xs font-bold text-slate-700 uppercase mb-1">Preço de Custo (R$)</label>
                        <input type="text" id="rapido_preco_custo" placeholder="0,00" inputmode="numeric" oninput="aplicarMascaraMoedaModal(this)" class="w-full h-12 px-3 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label for="rapido_codigo_barras" class="block text-xs font-bold text-slate-700 uppercase mb-1">Código de Barras ou Ref.</label>
                        <input type="text" id="rapido_codigo_barras" placeholder="789..." class="w-full h-12 px-3 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label for="rapido_marca" class="block text-xs font-bold text-slate-700 uppercase mb-1">Marca / Fabricante</label>
                        <input type="text" id="rapido_marca" placeholder="Ex: Nestlé, Nike, Caseiro..." class="w-full h-12 px-3 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                </div>
            </details>

            <!-- Rodapé Fixo / Botão Principal de Salvamento -->
            <div class="pt-2 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-end gap-2.5">
                <button type="button" onclick="fecharModalCadastroRapido()" class="w-full sm:w-auto px-5 py-3.5 text-sm font-bold text-slate-500 hover:text-slate-800 rounded-xl hover:bg-slate-100 transition order-2 sm:order-1 text-center">
                    Cancelar
                </button>
                <button type="submit" id="btnSalvarRapido" class="w-full sm:w-auto sm:min-w-[240px] py-4 px-6 bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-600 hover:from-emerald-700 hover:to-teal-700 text-white font-black text-base sm:text-lg rounded-2xl shadow-lg hover:shadow-xl active:scale-[0.98] transition flex items-center justify-center gap-2.5 order-1 sm:order-2">
                    <span class="text-xl">✅</span>
                    <span>Salvar Produto Agora</span>
                </button>
            </div>

        </form>

    </div>
</div>

<script>
    let arquivosFotosRapidas = [];
    let tamanhosGradeAtivos = {}; // { 'P': 1, 'M': 2, ... }
    let streamCameraAoVivo = null;
    let cameraFacingModeAtual = 'environment'; // 'environment' (traseira) ou 'user' (frontal)

    // Formatação de Moeda em Tempo Real amigável
    function aplicarMascaraMoedaModal(input) {
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

    // Abertura do modal
    function abrirModalCadastroRapido() {
        document.getElementById('formCadastroRapido').reset();
        document.getElementById('formCadastroRapido').classList.remove('hidden');
        document.getElementById('sucessoCadastroRapido').classList.add('hidden');
        arquivosFotosRapidas = [];
        tamanhosGradeAtivos = {};
        fecharCameraAoVivo();
        atualizarVisualFotos();
        renderizarChipsTamanhos();
        
        // Reset da grade de tamanhos (fechada por padrão)
        const painelGrade = document.getElementById('painelGradeTamanhos');
        painelGrade.classList.add('hidden');
        document.getElementById('textoBtnToggleGrade').textContent = '+ Adicionar Tamanhos';
        document.getElementById('btnToggleGradeTamanhos').classList.remove('bg-red-600');
        document.getElementById('btnToggleGradeTamanhos').classList.add('bg-indigo-600');
        
        // Unidade padrão UN
        selecionarUnidadeRapida('UN');
        
        // Exibir modal
        const modal = document.getElementById('modalCadastroRapido');
        modal.classList.remove('hidden');
        
        // Focar no campo de nome após pequena transição
        setTimeout(() => {
            const inputNome = document.getElementById('rapido_nome');
            if (inputNome && window.innerWidth >= 640) {
                inputNome.focus();
            }
        }, 150);
    }

    // Fechamento do modal
    function fecharModalCadastroRapido() {
        fecharCameraAoVivo();
        document.getElementById('modalCadastroRapido').classList.add('hidden');
    }

    // ==========================================
    // CÂMERA AO VIVO REAL (WebRTC)
    // ==========================================
    function abrirCameraOuFallback() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            // Fallback nativo
            document.getElementById('rapido_foto_camera_fallback').click();
            return;
        }
        iniciarCameraAoVivo();
    }

    function iniciarCameraAoVivo() {
        const container = document.getElementById('containerCameraAoVivo');
        const botoesEscolha = document.getElementById('botoesEscolhaFoto');
        const video = document.getElementById('videoCameraAoVivo');

        botoesEscolha.classList.add('hidden');
        container.classList.remove('hidden');

        if (streamCameraAoVivo) {
            streamCameraAoVivo.getTracks().forEach(t => t.stop());
        }

        const constraints = {
            video: {
                facingMode: cameraFacingModeAtual ? { ideal: cameraFacingModeAtual } : 'environment',
                width: { ideal: 1280 },
                height: { ideal: 720 }
            },
            audio: false
        };

        navigator.mediaDevices.getUserMedia(constraints)
            .then(stream => {
                streamCameraAoVivo = stream;
                video.srcObject = stream;
                video.play();
            })
            .catch(err => {
                console.warn('Erro ao abrir câmera WebRTC:', err);
                fecharCameraAoVivo();
                // Aciona o input tradicional caso o usuário bloqueie permissão ou dê erro
                document.getElementById('rapido_foto_camera_fallback').click();
            });
    }

    function alternarCameraAoVivo() {
        cameraFacingModeAtual = (cameraFacingModeAtual === 'environment') ? 'user' : 'environment';
        iniciarCameraAoVivo();
    }

    function fecharCameraAoVivo() {
        if (streamCameraAoVivo) {
            streamCameraAoVivo.getTracks().forEach(t => t.stop());
            streamCameraAoVivo = null;
        }
        const video = document.getElementById('videoCameraAoVivo');
        if (video) video.srcObject = null;
        const container = document.getElementById('containerCameraAoVivo');
        if (container) container.classList.add('hidden');
        const botoesEscolha = document.getElementById('botoesEscolhaFoto');
        if (botoesEscolha && arquivosFotosRapidas.length === 0) {
            botoesEscolha.classList.remove('hidden');
        }
    }

    function capturarFotoCameraAoVivo() {
        const video = document.getElementById('videoCameraAoVivo');
        const canvas = document.getElementById('canvasCameraSnapshot');
        if (!video || !video.videoWidth) return;

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        canvas.toBlob(blob => {
            if (!blob) return;
            const file = new File([blob], 'foto_camera_' + Date.now() + '.jpg', { type: 'image/jpeg' });
            arquivosFotosRapidas.unshift(file); // Coloca como foto principal
            fecharCameraAoVivo();
            atualizarVisualFotos();
        }, 'image/jpeg', 0.88);
    }

    // Gerenciador de Fotos via Input de Arquivo (Galeria / Fallback)
    function adicionarFotoRapida(input) {
        if (!input.files || input.files.length === 0) return;
        
        Array.from(input.files).forEach(f => {
            arquivosFotosRapidas.push(f);
        });
        
        fecharCameraAoVivo();
        atualizarVisualFotos();
        input.value = '';
    }

    function removerFotoPrincipal() {
        arquivosFotosRapidas.shift();
        atualizarVisualFotos();
    }

    function removerFotoIndex(index) {
        arquivosFotosRapidas.splice(index, 1);
        atualizarVisualFotos();
    }

    function atualizarVisualFotos() {
        const containerPrincipal = document.getElementById('previewFotoPrincipalContainer');
        const imgPrincipal = document.getElementById('imgPreviewPrincipal');
        const containerExtras = document.getElementById('containerMiniaturasExtras');
        const botoesEscolha = document.getElementById('botoesEscolhaFoto');
        
        containerExtras.innerHTML = '';

        if (arquivosFotosRapidas.length === 0) {
            containerPrincipal.classList.add('hidden');
            imgPrincipal.src = '';
            if (document.getElementById('containerCameraAoVivo').classList.contains('hidden')) {
                botoesEscolha.classList.remove('hidden');
            }
            return;
        }

        botoesEscolha.classList.add('hidden');

        // Exibe a foto principal
        const primeiraFoto = arquivosFotosRapidas[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            imgPrincipal.src = e.target.result;
            containerPrincipal.classList.remove('hidden');
        };
        reader.readAsDataURL(primeiraFoto);

        // Se houver mais de uma foto, exibe as miniaturas extras
        if (arquivosFotosRapidas.length > 1) {
            for (let i = 1; i < arquivosFotosRapidas.length; i++) {
                const file = arquivosFotosRapidas[i];
                const rExtra = new FileReader();
                const idx = i;
                rExtra.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative w-14 h-14 rounded-xl border-2 border-slate-200 overflow-hidden flex-shrink-0 bg-slate-100 group';
                    div.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-full object-cover">
                        <button type="button" onclick="removerFotoIndex(${idx})" class="absolute top-0.5 right-0.5 bg-red-600 text-white rounded-full p-0.5 opacity-90 hover:opacity-100">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    `;
                    containerExtras.appendChild(div);
                };
                rExtra.readAsDataURL(file);
            }
        }
    }

    // ==========================================
    // GRADE DE TAMANHOS INTEGRADA (SEM REDIRECIONAMENTO)
    // ==========================================
    function toggleGradeTamanhos() {
        const painel = document.getElementById('painelGradeTamanhos');
        const btnTexto = document.getElementById('textoBtnToggleGrade');
        const btn = document.getElementById('btnToggleGradeTamanhos');

        if (painel.classList.contains('hidden')) {
            painel.classList.remove('hidden');
            btnTexto.textContent = '✕ Fechar Tamanhos';
            btn.classList.remove('bg-indigo-600');
            btn.classList.add('bg-slate-700');
            
            // Se ainda não tiver tamanhos, aplica o preset de roupas automaticamente como sugestão
            if (Object.keys(tamanhosGradeAtivos).length === 0) {
                aplicarPresetGrade(['P', 'M', 'G', 'GG']);
            }
        } else {
            painel.classList.add('hidden');
            btnTexto.textContent = '+ Adicionar Tamanhos';
            btn.classList.remove('bg-slate-700');
            btn.classList.add('bg-indigo-600');
        }
    }

    function aplicarPresetGrade(listaTamanhos) {
        tamanhosGradeAtivos = {};
        listaTamanhos.forEach(tam => {
            tamanhosGradeAtivos[tam] = 1; // 1 peça de cada por padrão
        });
        renderizarChipsTamanhos();
    }

    function adicionarTamanhoAvulso() {
        const input = document.getElementById('inputNovoTamanhoAvulso');
        const val = input.value.trim().toUpperCase();
        if (!val) return;

        // Se digitou múltiplos separados por vírgula
        const partes = val.split(',').map(s => s.trim().toUpperCase()).filter(s => s.length > 0);
        partes.forEach(p => {
            if (!tamanhosGradeAtivos[p]) {
                tamanhosGradeAtivos[p] = 1;
            }
        });

        input.value = '';
        renderizarChipsTamanhos();
    }

    function alterarQtdTamanho(tam, delta) {
        if (!tamanhosGradeAtivos[tam]) return;
        tamanhosGradeAtivos[tam] = Math.max(1, tamanhosGradeAtivos[tam] + delta);
        renderizarChipsTamanhos();
    }

    function removerTamanhoGrade(tam) {
        delete tamanhosGradeAtivos[tam];
        renderizarChipsTamanhos();
    }

    function renderizarChipsTamanhos() {
        const grid = document.getElementById('gridChipsTamanhosAtivos');
        const msgVazio = document.getElementById('msgNenhumTamanhoSelecionado');
        const badgeTotal = document.getElementById('badgeTotalItensGrade');
        grid.innerHTML = '';

        const chaves = Object.keys(tamanhosGradeAtivos);
        let totalPecas = 0;

        if (chaves.length === 0) {
            msgVazio.classList.remove('hidden');
            badgeTotal.textContent = '0 peças no total';
            return;
        }

        msgVazio.classList.add('hidden');

        chaves.forEach(tam => {
            const qtd = tamanhosGradeAtivos[tam];
            totalPecas += qtd;

            const card = document.createElement('div');
            card.className = 'bg-white border-2 border-indigo-200 hover:border-indigo-400 rounded-2xl p-3 flex flex-col gap-2.5 shadow-xs transition';
            card.innerHTML = `
                <div class="flex items-center justify-between gap-2 border-b border-indigo-100/80 pb-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-gradient-to-r from-indigo-600 via-indigo-700 to-purple-700 text-white font-black text-xs sm:text-sm rounded-xl shadow-xs">
                        <span>🏷️</span>
                        <span class="tracking-wide">TAMANHO:</span>
                        <span class="text-amber-300 text-sm sm:text-base font-black ml-0.5 underline decoration-amber-400 decoration-2">${tam}</span>
                    </span>
                    <button type="button" onclick="removerTamanhoGrade('${tam}')" title="Remover tamanho ${tam}" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-red-50 text-slate-400 hover:text-red-600 font-bold text-xs flex items-center justify-center transition active:scale-90">✕</button>
                </div>
                
                <div class="flex items-center justify-between gap-2 pt-0.5">
                    <span class="text-xs font-bold text-slate-600">Qtd de Peças:</span>
                    <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl p-1">
                        <button type="button" onclick="alterarQtdTamanho('${tam}', -1)" title="Diminuir" class="w-8 h-8 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 font-black text-base flex items-center justify-center active:scale-95 shadow-2xs transition">-</button>
                        <span class="min-w-[28px] text-center text-sm font-black text-indigo-950">${qtd}</span>
                        <button type="button" onclick="alterarQtdTamanho('${tam}', 1)" title="Aumentar" class="w-8 h-8 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-black text-base flex items-center justify-center active:scale-95 shadow-2xs transition">+</button>
                    </div>
                </div>
            `;
            grid.appendChild(card);
        });

        badgeTotal.textContent = `${totalPecas} peça${totalPecas > 1 ? 's' : ''} no total`;
    }

    // ==========================================
    // UNIDADES & CATEGORIAS
    // ==========================================
    function selecionarUnidadeRapida(unidade, el) {
        document.getElementById('rapido_unidade').value = unidade;
        const wrapperOutro = document.getElementById('wrapperUnidadeOutro');
        if (wrapperOutro) wrapperOutro.classList.add('hidden');

        document.querySelectorAll('.btn-chip-unidade').forEach(btn => {
            btn.classList.remove('bg-emerald-500', 'text-white', 'border-emerald-500');
            btn.classList.add('bg-slate-50', 'text-slate-700', 'border-slate-200');
        });

        if (el) {
            el.classList.remove('bg-slate-50', 'text-slate-700', 'border-slate-200');
            el.classList.add('bg-emerald-500', 'text-white', 'border-emerald-500');
        } else {
            const btnUN = document.querySelector('.btn-chip-unidade');
            if (btnUN) {
                btnUN.classList.remove('bg-slate-50', 'text-slate-700', 'border-slate-200');
                btnUN.classList.add('bg-emerald-500', 'text-white', 'border-emerald-500');
            }
        }
    }

    function mostrarMaisUnidades(el) {
        document.querySelectorAll('.btn-chip-unidade').forEach(btn => {
            btn.classList.remove('bg-emerald-500', 'text-white', 'border-emerald-500');
            btn.classList.add('bg-slate-50', 'text-slate-700', 'border-slate-200');
        });
        if (el) {
            el.classList.remove('bg-slate-50', 'text-slate-700', 'border-slate-200');
            el.classList.add('bg-emerald-500', 'text-white', 'border-emerald-500');
        }
        const wrapper = document.getElementById('wrapperUnidadeOutro');
        wrapper.classList.remove('hidden');
        const sel = document.getElementById('rapido_unidade_select');
        document.getElementById('rapido_unidade').value = sel.value;
    }

    function selecionarCategoriaChip(catId, el) {
        document.getElementById('rapido_categoria_id').value = catId;
        document.querySelectorAll('.btn-chip-categoria').forEach(btn => {
            btn.classList.remove('border-emerald-500', 'bg-emerald-50', 'text-emerald-800');
            btn.classList.add('border-slate-200', 'bg-slate-50', 'text-slate-700');
        });
        if (el) {
            el.classList.remove('border-slate-200', 'bg-slate-50', 'text-slate-700');
            el.classList.add('border-emerald-500', 'bg-emerald-50', 'text-emerald-800');
        }
    }

    function reiniciarFormCadastroRapido() {
        document.getElementById('sucessoCadastroRapido').classList.add('hidden');
        document.getElementById('formCadastroRapido').classList.remove('hidden');
        document.getElementById('formCadastroRapido').reset();
        arquivosFotosRapidas = [];
        tamanhosGradeAtivos = {};
        fecharCameraAoVivo();
        atualizarVisualFotos();
        renderizarChipsTamanhos();
        selecionarUnidadeRapida('UN');
        const inputNome = document.getElementById('rapido_nome');
        if (inputNome) inputNome.focus();
    }

    function concluirEFecharCadastroRapido() {
        fecharModalCadastroRapido();
        if (typeof window.inserirProdutoNaTabelaIndex === 'function') {
            // já inserido na tabela dinamicamente
        } else if (window.location.pathname.includes('/vendas/produto')) {
            window.location.reload();
        }
    }

    // ==========================================
    // SALVAR PRODUTO RAPIDO (AJAX UNIFICADO)
    // ==========================================
    function salvarProdutoRapido(e, irParaMatriz = false) {
        if (e && e.preventDefault) e.preventDefault();
        
        const nomeInput = document.getElementById('rapido_nome');
        const precoInput = document.getElementById('rapido_preco');

        if (!nomeInput.value.trim()) {
            alert('Por favor, informe o nome do produto.');
            nomeInput.focus();
            return;
        }

        if (!precoInput.value.trim()) {
            alert('Por favor, informe o preço de venda do produto.');
            precoInput.focus();
            return;
        }

        const btn = document.getElementById('btnSalvarRapido');
        const btnTextoOriginal = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline-block" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Salvando Produto...</span>
        `;

        const csrfParam = document.querySelector('meta[name="csrf-param"]')?.content || '_csrf';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '<?= Yii::$app->request->csrfToken ?>';

        const formData = new FormData();
        formData.append('nome', nomeInput.value.trim());
        formData.append('preco', precoInput.value.trim());
        formData.append('unidade', document.getElementById('rapido_unidade').value);
        formData.append('categoria_id', document.getElementById('rapido_categoria_id').value);
        formData.append('marca', document.getElementById('rapido_marca')?.value || '');
        formData.append('preco_custo', document.getElementById('rapido_preco_custo')?.value || '');
        formData.append('codigo_barras', document.getElementById('rapido_codigo_barras')?.value || '');
        formData.append('estoque', document.getElementById('rapido_estoque')?.value || '0');
        formData.append('ir_para_matriz', irParaMatriz ? '1' : '0');
        formData.append(csrfParam, csrfToken);

        // Anexa os tamanhos da grade integrada caso selecionados
        const listaTamanhosEnvio = Object.keys(tamanhosGradeAtivos).map(tam => ({
            tamanho: tam,
            qtd: tamanhosGradeAtivos[tam]
        }));
        if (listaTamanhosEnvio.length > 0) {
            formData.append('tamanhos_json', JSON.stringify(listaTamanhosEnvio));
        }

        // Anexa fotos
        arquivosFotosRapidas.forEach((file) => {
            formData.append('fotos[]', file);
        });

        fetch('<?= Url::to(['/vendas/produto/cadastro-rapido']) ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            }
        })
        .then(async r => {
            const contentType = r.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                throw new Error('Sessão expirada ou resposta inválida do servidor.');
            }
            return await r.json();
        })
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = btnTextoOriginal;

            if (data.success) {
                // Vibração háptica de sucesso no celular
                if (navigator.vibrate) {
                    try { navigator.vibrate([40, 30, 40]); } catch(e) {}
                }

                // Notifica qualquer listener do sistema (mesas, encartes, catálogo)
                window.dispatchEvent(new CustomEvent('produtoCadastradoRapido', { detail: data.produto }));

                if (typeof window.inserirProdutoNaTabelaIndex === 'function') {
                    window.inserirProdutoNaTabelaIndex(data.produto);
                }

                // Exibe tela de sucesso amigável (SEM REDIRECIONAR O IDOSO!)
                document.getElementById('formCadastroRapido').classList.add('hidden');
                const sucessoDiv = document.getElementById('sucessoCadastroRapido');
                sucessoDiv.classList.remove('hidden');

                document.getElementById('msgSucessoNomeProduto').textContent = data.produto.nome;
                document.getElementById('nomeRecemSalvo').textContent = data.produto.nome;
                document.getElementById('precoRecemSalvo').textContent = 'R$ ' + data.produto.preco;

                const msgGrade = document.getElementById('msgSucessoDetalhesGrade');
                const tamanhosRecem = document.getElementById('tamanhosRecemSalvo');
                if (listaTamanhosEnvio.length > 0) {
                    const nomesTams = listaTamanhosEnvio.map(t => `${t.tamanho} (${t.qtd})`).join(', ');
                    msgGrade.textContent = `Grade criada: ${nomesTams}`;
                    tamanhosRecem.textContent = `Tamanhos: ${nomesTams}`;
                } else {
                    msgGrade.textContent = '';
                    tamanhosRecem.textContent = '';
                }

                const imgCard = document.getElementById('imgRecemSalvo');
                if (data.produto.foto) {
                    imgCard.innerHTML = `<img src="${data.produto.foto}" class="w-full h-full object-cover">`;
                } else {
                    imgCard.innerHTML = '📦';
                }

            } else {
                alert('Atenção: ' + (data.message || 'Falha ao salvar produto. Verifique os dados.'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = btnTextoOriginal;
            alert(err.message || 'Erro de comunicação ao salvar produto.');
        });
    }
</script>

