<?php

use yii\helpers\Url;
use yii\helpers\Html;
use app\modules\vendas\models\Categoria;

$categoriasLoja = Categoria::find()
    ->where(['usuario_id' => $lojaId, 'ativo' => true])
    ->orderBy(['ordem' => SORT_ASC, 'nome' => SORT_ASC])
    ->all();
?>

<!-- Modal Hub de Enriquecimento e Busca Web de Mídias -->
<div id="modalEnriquecimentoWeb" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-75 backdrop-blur-md flex items-center justify-center p-3 sm:p-5">
    <div class="bg-white rounded-3xl shadow-2xl max-w-5xl w-full overflow-hidden transform transition-all border border-gray-100 flex flex-col max-h-[94vh]">
        
        <!-- Header do Modal -->
        <div class="bg-gradient-to-r from-blue-700 via-indigo-700 to-purple-800 px-6 py-5 text-white flex items-center justify-between flex-shrink-0 shadow-md">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-white/20 backdrop-blur-md rounded-2xl border border-white/30 text-2xl">
                    🌐
                </div>
                <div>
                    <h3 class="text-xl font-extrabold tracking-tight">Hub de Busca Web & Mídias de Produtos</h3>
                    <p class="text-xs text-blue-100 font-medium">Busque imagens em alta resolução, vídeos promocionais e EAN para seus produtos ou popule o catálogo por marcas e links</p>
                </div>
            </div>
            <button onclick="fecharModalEnriquecimentoWeb()" class="text-blue-100 hover:text-white hover:bg-white/10 p-2 rounded-xl transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Barra de Navegação por Abas -->
        <div class="bg-slate-100 border-b border-slate-200 px-6 py-2 flex items-center gap-2 overflow-x-auto flex-shrink-0">
            <button type="button" onclick="alternarAbaEnriquecimento('existentes')" id="tabBtnExistentes" class="px-4 py-2 rounded-xl font-extrabold text-xs transition flex items-center gap-2 bg-white text-indigo-700 shadow-sm border border-slate-200">
                <span>🔄</span>
                <span>Atualizar Produtos Cadastrados</span>
            </button>
            <button type="button" onclick="alternarAbaEnriquecimento('marcas')" id="tabBtnMarcas" class="px-4 py-2 rounded-xl font-bold text-xs transition flex items-center gap-2 text-slate-600 hover:bg-white/60">
                <span>🚀</span>
                <span>Popular Catálogo por Marcas</span>
            </button>
            <button type="button" onclick="alternarAbaEnriquecimento('link')" id="tabBtnLink" class="px-4 py-2 rounded-xl font-bold text-xs transition flex items-center gap-2 text-slate-600 hover:bg-white/60">
                <span>🔗</span>
                <span>Importar por Link / URL</span>
            </button>
        </div>

        <!-- Conteúdo do Modal com Scroll -->
        <div class="p-6 space-y-6 overflow-y-auto flex-1">

            <!-- Banner de Progresso Geral / Status com botão de Interromper -->
            <div id="bannerProgressoEnriquecimento" class="hidden bg-indigo-50 border-2 border-indigo-300 p-4 rounded-2xl space-y-2">
                <div class="flex items-center justify-between text-xs font-bold text-indigo-900">
                    <span id="textoProgressoEnriquecimento">Processando requisições na Web...</span>
                    <div class="flex items-center gap-2">
                        <span id="contadorProgressoEnriquecimento" class="bg-indigo-200 px-2 py-0.5 rounded-full">0%</span>
                        <button type="button" onclick="cancelarLoteEnriquecimento()" class="px-2.5 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg text-[10px] font-extrabold shadow-sm transition">
                            🛑 Interromper
                        </button>
                    </div>
                </div>
                <div class="w-full bg-indigo-200 rounded-full h-2.5 overflow-hidden">
                    <div id="barraProgressoEnriquecimento" class="bg-indigo-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>

            <!-- ========================================================================================= -->
            <!-- ABA 1: ATUALIZAR PRODUTOS EXISTENTES -->
            <!-- ========================================================================================= -->
            <div id="abaConteudoExistentes" class="space-y-5">
                
                <!-- Filtros e Controles de Busca -->
                <div class="bg-slate-50 border border-slate-200 p-4 rounded-2xl space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">🔍 Buscar no Catálogo:</label>
                            <input type="text" id="filtroTextoExistentes" oninput="debounceBuscarProdutosExistentes()" placeholder="Digite nome, marca ou código de barras..." class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Status de Mídia:</label>
                            <select id="filtroStatusMidiaExistentes" onchange="carregarProdutosExistentesParaEnriquecer()" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option value="sem_foto" selected>⚠️ Apenas Sem Foto</option>
                                <option value="sem_ean">📦 Sem Código de Barras (EAN)</option>
                                <option value="todos">📋 Todos os Produtos</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Filtrar Categoria:</label>
                            <select id="filtroCategoriaExistentes" onchange="carregarProdutosExistentesParaEnriquecer()" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option value="TODAS">Todas as Categorias</option>
                                <?php foreach ($categoriasLoja as $cat): ?>
                                    <option value="<?= $cat->id ?>"><?= Html::encode($cat->nome) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-between flex-wrap gap-2 pt-2 border-t border-slate-200">
                        <span id="badgeContadorExistentes" class="text-xs font-extrabold text-slate-600">0 produto(s) listado(s)</span>
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="flex items-center gap-1.5 bg-white px-2.5 py-1 rounded-xl border border-slate-300 shadow-sm">
                                <label class="text-[11px] font-bold text-slate-600">Qtd do Lote:</label>
                                <select id="selectTamanhoLoteEnriquecimento" class="bg-transparent text-xs font-extrabold text-indigo-700 focus:outline-none cursor-pointer">
                                    <option value="TODOS" selected>Todos os Listados</option>
                                    <option value="10">10 produtos</option>
                                    <option value="25">25 produtos</option>
                                    <option value="50">50 produtos</option>
                                    <option value="100">100 produtos</option>
                                    <option value="200">200 produtos</option>
                                </select>
                            </div>
                            <button type="button" onclick="enriquecerLoteSelecionados()" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer">
                                <span>⚡</span>
                                <span>Enriquecer Lista Automaticamente</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabela de Produtos Existentes -->
                <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-white">
                    <div class="max-h-72 overflow-y-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead class="bg-slate-100 text-slate-700 font-extrabold uppercase sticky top-0 border-b border-slate-200 z-10">
                                <tr>
                                    <th class="p-3">Produto</th>
                                    <th class="p-3">Marca / EAN</th>
                                    <th class="p-3">Preço</th>
                                    <th class="p-3 text-center">Fotos</th>
                                    <th class="p-3 text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyProdutosExistentes" class="divide-y divide-slate-100 font-medium text-slate-800">
                                <tr>
                                    <td colspan="5" class="p-4 text-center text-slate-400 italic">Carregando catálogo...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Painel de Detalhes e Aplicação de Mídias do Produto Selecionado -->
                <div id="painelMidiasEncontradas" class="hidden bg-slate-50 border-2 border-indigo-200 p-5 rounded-3xl space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded-md">Mídias Encontradas na Web</span>
                            <h4 class="font-extrabold text-base text-slate-900 mt-1" id="tituloProdSelecionadoMidias">Produto</h4>
                            <p class="text-xs text-slate-500" id="subtituloProdSelecionadoMidias">Selecione as imagens e vídeos que deseja adicionar ao cadastro</p>
                        </div>
                        <button type="button" onclick="fecharPainelMidiasEncontradas()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200">
                            ✕
                        </button>
                    </div>

                    <!-- Sugestões de Dados Cadastrais -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-white p-3 rounded-2xl border border-slate-200 text-xs">
                        <div>
                            <label class="flex items-center gap-1.5 font-bold text-slate-700 mb-1">
                                <input type="checkbox" id="chkAtualizarNome" checked class="rounded text-indigo-600">
                                <span>Atualizar Nome:</span>
                            </label>
                            <input type="text" id="inputNovoNomeProd" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold">
                        </div>
                        <div>
                            <label class="flex items-center gap-1.5 font-bold text-slate-700 mb-1">
                                <input type="checkbox" id="chkAtualizarMarca" checked class="rounded text-indigo-600">
                                <span>Atualizar Marca:</span>
                            </label>
                            <input type="text" id="inputNovaMarcaProd" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold">
                        </div>
                        <div>
                            <label class="flex items-center gap-1.5 font-bold text-slate-700 mb-1">
                                <input type="checkbox" id="chkAtualizarEan" checked class="rounded text-indigo-600">
                                <span>Código de Barras (EAN):</span>
                            </label>
                            <input type="text" id="inputNovoEanProd" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-mono font-bold">
                        </div>
                    </div>

                    <!-- Galeria de Fotos Encontradas -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase">📸 Fotos Encontradas (Clique para marcar/desmarcar):</label>
                            <span class="text-[10px] text-indigo-600 font-bold" id="badgeTotalFotosEncontradas">0 fotos</span>
                        </div>
                        <div id="gridFotosEncontradas" class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3 max-h-48 overflow-y-auto p-1">
                            <!-- Injetado dinamicamente -->
                        </div>
                    </div>

                    <!-- Galeria de Vídeos Promocionais Encontrados -->
                    <div id="secaoVideosEncontrados" class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase">🎬 Vídeos Promocionais Encontrados:</label>
                        <div id="gridVideosEncontrados" class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-44 overflow-y-auto p-1">
                            <!-- Injetado dinamicamente -->
                        </div>
                    </div>

                    <!-- Botão de Ação -->
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button type="button" onclick="fecharPainelMidiasEncontradas()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl text-xs transition">
                            Cancelar
                        </button>
                        <button type="button" id="btnSalvarEnriquecimentoProd" onclick="confirmarAplicarEnriquecimento()" class="px-5 py-2 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white font-extrabold rounded-xl text-xs shadow-md transition flex items-center gap-2">
                            <span>💾</span>
                            <span>Salvar Mídias no Cadastro</span>
                        </button>
                    </div>
                </div>

            </div>

            <!-- ========================================================================================= -->
            <!-- ABA 2: POPULAR CATÁLOGO POR MARCAS -->
            <!-- ========================================================================================= -->
            <div id="abaConteudoMarcas" class="hidden space-y-5">
                
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-100 p-4 rounded-2xl space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🏷️</span>
                        <div>
                            <h4 class="font-extrabold text-sm text-indigo-950">Pesquisar Catálogo em Massa por Marcas</h4>
                            <p class="text-xs text-indigo-700">Digite marcas separadas por vírgula para buscar produtos oficiais e imagens</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Marcas (separadas por vírgula):</label>
                            <input type="text" id="inputMarcasSeparadas" placeholder="Ex: Bauducco, Nestlé, Coca-Cola, Ypê, Tramontina" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Categoria Padrão:</label>
                            <select id="selectCategoriaPadraoMarcas" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <?php foreach ($categoriasLoja as $cat): ?>
                                    <option value="<?= $cat->id ?>"><?= Html::encode($cat->nome) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Itens por Marca:</label>
                            <select id="selectItensPorMarca" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option value="4">4 Produtos</option>
                                <option value="8" selected>8 Produtos</option>
                                <option value="12">12 Produtos</option>
                                <option value="20">20 Produtos</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button type="button" id="btnBuscarCatalogoMarcas" onclick="pesquisarCatalogoMarcasWeb()" class="w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow transition flex items-center justify-center gap-2">
                            <span>🔍</span>
                            <span>Buscar Produtos das Marcas</span>
                        </button>
                    </div>
                </div>

                <!-- Resultado dos Produtos Encontrados por Marca -->
                <div id="containerResultadoMarcas" class="space-y-4">
                    <div class="text-slate-400 italic text-xs text-center py-6 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        Informe as marcas acima e clique em "Buscar Produtos das Marcas" para visualizar as sugestões.
                    </div>
                </div>

                <!-- Botão de Ação para Cadastro em Lote -->
                <div id="boxAcaoCadastroLoteMarcas" class="hidden flex items-center justify-between bg-slate-50 p-4 rounded-2xl border border-slate-200">
                    <span id="badgeContadorSelecionadosMarcas" class="text-xs font-bold text-slate-700">0 produto(s) selecionado(s)</span>
                    <button type="button" id="btnCadastrarLoteMarcas" onclick="confirmarCadastroLoteMarcas()" class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-2">
                        <span>📥</span>
                        <span>Cadastrar Selecionados na Base</span>
                    </button>
                </div>

            </div>

            <!-- ========================================================================================= -->
            <!-- ABA 3: IMPORTAR POR LINK / URL -->
            <!-- ========================================================================================= -->
            <div id="abaConteudoLink" class="hidden space-y-5">
                
                <div class="bg-slate-50 border border-slate-200 p-4 rounded-2xl space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🔗</span>
                        <div>
                            <h4 class="font-extrabold text-sm text-slate-900">Importar Produto via Link / URL Direta</h4>
                            <p class="text-xs text-slate-500">Cole o link de uma página do fabricante, distribuidor ou e-commerce</p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="url" id="inputUrlImportar" placeholder="https://exemplo.com.br/produto/arroz-tio-joao-5kg..." class="flex-1 px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <button type="button" id="btnExtrairUrl" onclick="extrairDadosUrlWeb()" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow transition flex items-center justify-center gap-2">
                            <span>⚡</span>
                            <span>Extrair Mídias e Dados</span>
                        </button>
                    </div>
                </div>

                <!-- Resultado da Extração da URL -->
                <div id="cardResultadoExtracaoUrl" class="hidden bg-white border-2 border-indigo-200 p-5 rounded-3xl space-y-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <h4 class="font-extrabold text-base text-slate-900">Dados Extraídos da Página</h4>
                        <span class="text-[10px] font-bold text-green-700 bg-green-100 px-2.5 py-1 rounded-full">Pronto para Cadastro</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nome do Produto:</label>
                            <input type="text" id="urlExtrNome" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Marca:</label>
                            <input type="text" id="urlExtrMarca" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Código de Barras (EAN):</label>
                            <input type="text" id="urlExtrEan" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono font-bold text-slate-900">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Preço de Venda (R$):</label>
                            <input type="text" id="urlExtrPreco" placeholder="0,00" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-red-600">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Categoria:</label>
                            <select id="urlExtrCategoria" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold">
                                <?php foreach ($categoriasLoja as $cat): ?>
                                    <option value="<?= $cat->id ?>"><?= Html::encode($cat->nome) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Fotos da URL -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2">📸 Fotos Extraídas (Selecione as que deseja salvar):</label>
                        <div id="gridFotosUrlExtraidas" class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3 max-h-48 overflow-y-auto p-1">
                            <!-- Injetado dinamicamente -->
                        </div>
                    </div>

                    <!-- Botão de Cadastro -->
                    <div class="flex justify-end pt-2 border-t border-slate-200">
                        <button type="button" id="btnSalvarProdutoUrl" onclick="confirmarCadastroProdutoUrl()" class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-2">
                            <span>💾</span>
                            <span>Cadastrar Este Produto na Base</span>
                        </button>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

<script>
    let abaEnriquecimentoAtiva = 'existentes';
    let produtosExistentesCache = [];
    let produtoSelecionadoAtivo = null;
    let fotosEncontradasAtuais = [];
    let videosEncontradosAtuais = [];
    let produtosMarcasEncontrados = [];
    let debounceTimerExistentes = null;
    let cancelamentoLoteSolicitado = false;

    function abrirModalEnriquecimentoWeb(ids = []) {
        document.getElementById('modalEnriquecimentoWeb').classList.remove('hidden');
        alternarAbaEnriquecimento('existentes');

        // Herda parâmetros ativos da URL da página principal se existirem
        const urlParams = new URLSearchParams(window.location.search);
        const catUrl = urlParams.get('categoria_id');
        const buscaUrl = urlParams.get('busca');

        const selCat = document.getElementById('filtroCategoriaExistentes');
        const inpBusca = document.getElementById('filtroTextoExistentes');

        if (catUrl && selCat) {
            selCat.value = catUrl;
        }
        if (buscaUrl && inpBusca) {
            inpBusca.value = buscaUrl;
        }

        carregarProdutosExistentesParaEnriquecer(ids);
    }

    function fecharModalEnriquecimentoWeb() {
        document.getElementById('modalEnriquecimentoWeb').classList.add('hidden');
    }

    function alternarAbaEnriquecimento(aba) {
        abaEnriquecimentoAtiva = aba;

        const tabBtnExistentes = document.getElementById('tabBtnExistentes');
        const tabBtnMarcas = document.getElementById('tabBtnMarcas');
        const tabBtnLink = document.getElementById('tabBtnLink');

        const abaExistentes = document.getElementById('abaConteudoExistentes');
        const abaMarcas = document.getElementById('abaConteudoMarcas');
        const abaLink = document.getElementById('abaConteudoLink');

        const classAtiva = 'px-4 py-2 rounded-xl font-extrabold text-xs transition flex items-center gap-2 bg-white text-indigo-700 shadow-sm border border-slate-200';
        const classInativa = 'px-4 py-2 rounded-xl font-bold text-xs transition flex items-center gap-2 text-slate-600 hover:bg-white/60';

        tabBtnExistentes.className = (aba === 'existentes') ? classAtiva : classInativa;
        tabBtnMarcas.className = (aba === 'marcas') ? classAtiva : classInativa;
        tabBtnLink.className = (aba === 'link') ? classAtiva : classInativa;

        abaExistentes.classList.toggle('hidden', aba !== 'existentes');
        abaMarcas.classList.toggle('hidden', aba !== 'marcas');
        abaLink.classList.toggle('hidden', aba !== 'link');
    }

    function debounceBuscarProdutosExistentes() {
        clearTimeout(debounceTimerExistentes);
        debounceTimerExistentes = setTimeout(() => {
            carregarProdutosExistentesParaEnriquecer();
        }, 300);
    }

    function carregarProdutosExistentesParaEnriquecer(ids = []) {
        const tbody = document.getElementById('tbodyProdutosExistentes');
        tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-slate-400 italic"><span class="animate-spin inline-block">⌛</span> Consultando produtos...</td></tr>';

        const statusMidia = document.getElementById('filtroStatusMidiaExistentes')?.value || 'sem_foto';
        const categoriaId = document.getElementById('filtroCategoriaExistentes')?.value || 'TODAS';
        const busca = document.getElementById('filtroTextoExistentes')?.value || '';

        let url = '<?= Url::to(['/vendas/produto-enriquecimento/listar-produtos-para-enriquecer']) ?>'
            + '?filtro=' + encodeURIComponent(statusMidia)
            + '&categoria_id=' + encodeURIComponent(categoriaId)
            + '&busca=' + encodeURIComponent(busca)
            + '&limit=250';

        if (ids && ids.length > 0) {
            ids.forEach(id => { url += '&ids[]=' + encodeURIComponent(id); });
        }

        fetch(url, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.produtos) {
                produtosExistentesCache = data.produtos;
                renderizarTabelaProdutosExistentes(produtosExistentesCache);
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-slate-400 italic">Nenhum produto encontrado com os filtros selecionados.</td></tr>';
            }
        })
        .catch(err => {
            tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-red-500 italic">Erro ao carregar produtos: ' + err.message + '</td></tr>';
        });
    }

    function renderizarTabelaProdutosExistentes(produtos) {
        const tbody = document.getElementById('tbodyProdutosExistentes');
        const badge = document.getElementById('badgeContadorExistentes');
        badge.textContent = `${produtos.length} produto(s) listado(s)`;

        if (produtos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-slate-400 italic">Nenhum produto corresponde aos filtros informados.</td></tr>';
            return;
        }

        let html = '';
        produtos.forEach((p, idx) => {
            const badgeFoto = p.tem_foto
                ? `<span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 font-bold px-2 py-0.5 rounded-md border border-emerald-200">${p.total_fotos} foto(s)</span>`
                : `<span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 font-bold px-2 py-0.5 rounded-md border border-amber-200">⚠️ Sem foto</span>`;

            html += `
                <tr class="hover:bg-slate-50/80 transition">
                    <td class="p-3">
                        <div class="font-extrabold text-slate-900 text-xs">${escapeHtml(p.nome)}</div>
                        <div class="text-[10px] text-slate-500 font-semibold">${escapeHtml(p.categoria_nome)}</div>
                    </td>
                    <td class="p-3">
                        <div class="font-bold text-slate-800 text-xs">${p.marca ? escapeHtml(p.marca) : '<span class="text-slate-400 italic">Sem marca</span>'}</div>
                        <div class="font-mono text-[10px] text-slate-500">${p.codigo_barras || '<span class="text-slate-400 italic">Sem EAN</span>'}</div>
                    </td>
                    <td class="p-3 font-extrabold text-slate-800 text-xs">
                        R$ ${p.preco_venda_formatado}
                    </td>
                    <td class="p-3 text-center">
                        ${badgeFoto}
                    </td>
                    <td class="p-3 text-right">
                        <button type="button" onclick="abrirBuscaMidiasParaProduto('${p.id}')" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200 font-extrabold rounded-xl transition flex items-center gap-1 text-[11px] ml-auto">
                            <span>🔍</span>
                            <span>Buscar Mídias</span>
                        </button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    function abrirBuscaMidiasParaProduto(prodId) {
        const prod = produtosExistentesCache.find(x => x.id === prodId);
        if (!prod) return;

        produtoSelecionadoAtivo = prod;
        const painel = document.getElementById('painelMidiasEncontradas');
        painel.classList.remove('hidden');

        document.getElementById('tituloProdSelecionadoMidias').textContent = prod.nome;
        document.getElementById('subtituloProdSelecionadoMidias').textContent = `Marca: ${prod.marca || 'Não informada'} • EAN: ${prod.codigo_barras || 'Não informado'}`;

        document.getElementById('inputNovoNomeProd').value = prod.nome;
        document.getElementById('inputNovaMarcaProd').value = prod.marca || '';
        document.getElementById('inputNovoEanProd').value = prod.codigo_barras || '';

        const gridFotos = document.getElementById('gridFotosEncontradas');
        gridFotos.innerHTML = '<div class="col-span-full text-indigo-600 font-semibold italic text-xs text-center py-4"><span class="animate-spin inline-block">⌛</span> Buscando fotos e vídeos em alta resolução na Web...</div>';

        fetch('<?= Url::to(['/vendas/produto-enriquecimento/buscar-midias-produto']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                produto_id: prod.id,
                nome: prod.nome,
                marca: prod.marca,
                ean: prod.codigo_barras,
                '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                fotosEncontradasAtuais = data.fotos || [];
                videosEncontradosAtuais = data.videos || [];
                
                if (data.dados) {
                    if (data.dados.nome_sugerido && !prod.nome) document.getElementById('inputNovoNomeProd').value = data.dados.nome_sugerido;
                    if (data.dados.marca && !prod.marca) document.getElementById('inputNovaMarcaProd').value = data.dados.marca;
                    if (data.dados.ean && !prod.codigo_barras) document.getElementById('inputNovoEanProd').value = data.dados.ean;
                }

                renderizarGridFotosEncontradas(fotosEncontradasAtuais);
                renderizarGridVideosEncontrados(videosEncontradosAtuais);
            } else {
                gridFotos.innerHTML = '<div class="col-span-full text-slate-400 italic text-xs text-center py-4">Nenhuma imagem encontrada na web para este produto.</div>';
            }
        })
        .catch(err => {
            gridFotos.innerHTML = '<div class="col-span-full text-red-500 italic text-xs text-center py-4">Erro na busca: ' + err.message + '</div>';
        });
    }

    function renderizarGridFotosEncontradas(fotos) {
        const grid = document.getElementById('gridFotosEncontradas');
        const badge = document.getElementById('badgeTotalFotosEncontradas');
        badge.textContent = `${fotos.length} foto(s) encontrada(s)`;

        if (fotos.length === 0) {
            grid.innerHTML = '<div class="col-span-full text-slate-400 italic text-xs text-center py-4">Nenhuma foto encontrada.</div>';
            return;
        }

        let html = '';
        fotos.forEach((url, idx) => {
            const isChecked = (idx === 0) ? 'checked' : '';
            html += `
                <label class="relative group cursor-pointer border-2 border-slate-200 rounded-2xl overflow-hidden bg-white p-1 hover:border-indigo-500 transition shadow-sm block">
                    <input type="checkbox" name="fotos_web_chk" value="${escapeHtml(url)}" ${isChecked} class="absolute top-2 left-2 z-10 rounded text-indigo-600 focus:ring-indigo-500">
                    <img src="${escapeHtml(url)}" alt="Foto" class="w-full h-24 object-contain rounded-xl bg-slate-50" onerror="this.src='<?= Url::base() ?>/img/encarte-cover-placeholder.png'">
                    <span class="block text-[10px] text-center text-slate-600 font-bold mt-1 truncate">Foto #${idx + 1}</span>
                </label>
            `;
        });
        grid.innerHTML = html;
    }

    function renderizarGridVideosEncontrados(videos) {
        const grid = document.getElementById('gridVideosEncontrados');
        const secao = document.getElementById('secaoVideosEncontrados');

        if (!videos || videos.length === 0) {
            secao.classList.add('hidden');
            return;
        }
        secao.classList.remove('hidden');

        let html = '';
        videos.forEach((v, idx) => {
            html += `
                <div class="flex items-center gap-3 p-2.5 bg-white border border-slate-200 rounded-2xl shadow-sm text-xs">
                    <input type="checkbox" name="videos_web_chk" value="${escapeHtml(v.url)}" data-titulo="${escapeHtml(v.titulo)}" class="rounded text-purple-600">
                    <div class="min-w-0 flex-1">
                        <div class="font-extrabold text-slate-900 truncate" title="${escapeHtml(v.titulo)}">${escapeHtml(v.titulo)}</div>
                        <div class="text-[10px] text-slate-500 font-semibold">${escapeHtml(v.origem || 'Web')} • ${v.duracao || 'Promocional'}</div>
                    </div>
                    <a href="${escapeHtml(v.url)}" target="_blank" class="px-2 py-1 bg-purple-50 hover:bg-purple-100 text-purple-700 font-extrabold rounded-lg text-[10px]">
                        Assistir ↗
                    </a>
                </div>
            `;
        });
        grid.innerHTML = html;
    }

    function fecharPainelMidiasEncontradas() {
        document.getElementById('painelMidiasEncontradas').classList.add('hidden');
        produtoSelecionadoAtivo = null;
    }

    function confirmarAplicarEnriquecimento() {
        if (!produtoSelecionadoAtivo) return;

        const fotosChecked = Array.from(document.querySelectorAll('input[name="fotos_web_chk"]:checked')).map(c => c.value);
        const videosChecked = Array.from(document.querySelectorAll('input[name="videos_web_chk"]:checked')).map(c => ({
            url: c.value,
            titulo: c.getAttribute('data-titulo') || 'Vídeo Promocional'
        }));

        const btn = document.getElementById('btnSalvarEnriquecimentoProd');
        btn.disabled = true;
        btn.innerHTML = '⌛ Salvando mídias...';

        const payload = {
            produto_id: produtoSelecionadoAtivo.id,
            fotos: fotosChecked,
            videos: videosChecked,
            atualizar_nome: document.getElementById('chkAtualizarNome').checked,
            novo_nome: document.getElementById('inputNovoNomeProd').value,
            atualizar_marca: document.getElementById('chkAtualizarMarca').checked,
            nova_marca: document.getElementById('inputNovaMarcaProd').value,
            atualizar_ean: document.getElementById('chkAtualizarEan').checked,
            novo_ean: document.getElementById('inputNovoEanProd').value,
            '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
        };

        fetch('<?= Url::to(['/vendas/produto-enriquecimento/aplicar-enriquecimento']) ?>', {
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
            btn.innerHTML = '<span>💾</span><span>Salvar Mídias no Cadastro</span>';

            if (data.success) {
                alert('✅ ' + data.message);
                fecharPainelMidiasEncontradas();
                carregarProdutosExistentesParaEnriquecer();
            } else {
                alert('Erro ao aplicar: ' + (data.message || 'Falha no processamento.'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<span>💾</span><span>Salvar Mídias no Cadastro</span>';
            alert('Erro de conexão: ' + err.message);
        });
    }

    function cancelarLoteEnriquecimento() {
        cancelamentoLoteSolicitado = true;
        const txt = document.getElementById('textoProgressoEnriquecimento');
        if (txt) txt.textContent = '🛑 Interrompendo processamento...';
    }

    async function enriquecerLoteSelecionados() {
        if (produtosExistentesCache.length === 0) {
            alert('Nenhum produto listado para enriquecer.');
            return;
        }

        const optLote = document.getElementById('selectTamanhoLoteEnriquecimento')?.value || 'TODOS';
        let total = produtosExistentesCache.length;
        if (optLote !== 'TODOS') {
            total = Math.min(produtosExistentesCache.length, parseInt(optLote, 10));
        }

        if (!confirm(`Deseja buscar fotos e códigos de barras automaticamente para os ${total} produtos selecionados da lista?`)) {
            return;
        }

        cancelamentoLoteSolicitado = false;
        const banner = document.getElementById('bannerProgressoEnriquecimento');
        const txtProgresso = document.getElementById('textoProgressoEnriquecimento');
        const numProgresso = document.getElementById('contadorProgressoEnriquecimento');
        const barProgresso = document.getElementById('barraProgressoEnriquecimento');

        banner.classList.remove('hidden');

        let sucessos = 0;
        for (let i = 0; i < total; i++) {
            if (cancelamentoLoteSolicitado) {
                break;
            }

            const p = produtosExistentesCache[i];
            const pct = Math.round(((i + 1) / total) * 100);

            txtProgresso.textContent = `[${i + 1}/${total}] Buscando fotos para: ${p.nome}...`;
            numProgresso.textContent = `${pct}%`;
            barProgresso.style.width = `${pct}%`;

            try {
                const resBusca = await fetch('<?= Url::to(['/vendas/produto-enriquecimento/buscar-midias-produto']) ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        produto_id: p.id,
                        nome: p.nome,
                        marca: p.marca,
                        ean: p.codigo_barras,
                        '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
                    })
                }).then(r => r.json());

                if (resBusca.success && resBusca.fotos && resBusca.fotos.length > 0) {
                    await fetch('<?= Url::to(['/vendas/produto-enriquecimento/aplicar-enriquecimento']) ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            produto_id: p.id,
                            fotos: resBusca.fotos.slice(0, 3),
                            videos: (resBusca.videos && resBusca.videos.length > 0) ? [resBusca.videos[0]] : [],
                            atualizar_nome: false,
                            atualizar_marca: Boolean(resBusca.dados?.marca && !p.marca),
                            nova_marca: resBusca.dados?.marca || '',
                            atualizar_ean: Boolean(resBusca.dados?.ean && !p.codigo_barras),
                            novo_ean: resBusca.dados?.ean || '',
                            '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
                        })
                    });
                    sucessos++;
                }
            } catch (e) {
                console.error('Erro no item:', p.nome, e);
            }
        }

        banner.classList.add('hidden');
        if (cancelamentoLoteSolicitado) {
            alert(`⚠️ Processamento interrompido pelo usuário. ${sucessos} produto(s) foram enriquecidos.`);
        } else {
            alert(`🎉 Processamento em lote concluído! ${sucessos} de ${total} produto(s) foram enriquecidos com fotos e dados da Web.`);
        }
        carregarProdutosExistentesParaEnriquecer();
    }

    // =========================================================================
    // ABA 2: MARCAS
    // =========================================================================
    function pesquisarCatalogoMarcasWeb() {
        const marcasStr = document.getElementById('inputMarcasSeparadas')?.value.trim();
        if (!marcasStr) {
            alert('Informe ao menos uma marca separada por vírgula.');
            return;
        }

        const btn = document.getElementById('btnBuscarCatalogoMarcas');
        btn.disabled = true;
        btn.innerHTML = '⌛ Consultando catálogo das marcas...';

        const itensPorMarca = document.getElementById('selectItensPorMarca')?.value || 8;
        const container = document.getElementById('containerResultadoMarcas');
        container.innerHTML = '<div class="text-indigo-600 font-semibold italic text-xs text-center py-8"><span class="animate-spin inline-block">⌛</span> Pesquisando produtos oficiais para as marcas informadas...</div>';

        fetch('<?= Url::to(['/vendas/produto-enriquecimento/buscar-por-marcas']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                marcas: marcasStr,
                itens_por_marca: itensPorMarca,
                '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
            })
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<span>🔍</span><span>Buscar Produtos das Marcas</span>';

            if (data.success && data.marcas) {
                produtosMarcasEncontrados = [];
                renderizarResultadoMarcas(data.marcas);
            } else {
                container.innerHTML = '<div class="text-slate-400 italic text-xs text-center py-6">Nenhum produto encontrado para as marcas informadas.</div>';
                document.getElementById('boxAcaoCadastroLoteMarcas').classList.add('hidden');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<span>🔍</span><span>Buscar Produtos das Marcas</span>';
            container.innerHTML = '<div class="text-red-500 italic text-xs text-center py-6">Erro: ' + err.message + '</div>';
        });
    }

    function renderizarResultadoMarcas(marcasObj) {
        const container = document.getElementById('containerResultadoMarcas');
        let html = '';
        let totalEncontrados = 0;

        Object.keys(marcasObj).forEach(marcaNome => {
            const produtos = marcasObj[marcaNome];
            if (!produtos || produtos.length === 0) return;

            html += `
                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <h5 class="font-extrabold text-xs uppercase text-indigo-900 flex items-center gap-1.5">
                            <span>🏷️</span>
                            <span>Marca: ${escapeHtml(marcaNome)}</span>
                        </h5>
                        <span class="text-[10px] font-bold text-slate-500">${produtos.length} sugestão(ões)</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            `;

            produtos.forEach((prod, pIdx) => {
                const idGlobal = produtosMarcasEncontrados.length;
                produtosMarcasEncontrados.push(prod);
                totalEncontrados++;

                const fotoUrl = prod.fotos && prod.fotos[0] ? prod.fotos[0] : '';

                html += `
                    <div class="border border-slate-200 rounded-xl p-2.5 bg-slate-50 hover:bg-white hover:border-indigo-300 transition space-y-2 text-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-1 mb-1">
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="checkbox" name="chk_marca_prod" value="${idGlobal}" checked onchange="atualizarContadorMarcasSelecionadas()" class="rounded text-indigo-600">
                                    <span class="font-bold text-[11px] text-slate-900 truncate flex-1" title="${escapeHtml(prod.nome)}">${escapeHtml(prod.nome)}</span>
                                </label>
                            </div>
                            <img src="${escapeHtml(fotoUrl)}" class="w-full h-24 object-contain rounded-lg bg-white border border-slate-100 p-1" onerror="this.src='<?= Url::base() ?>/img/encarte-cover-placeholder.png'">
                        </div>

                        <div class="space-y-1.5 pt-1">
                            <input type="text" id="nome_marca_${idGlobal}" value="${escapeHtml(prod.nome)}" placeholder="Nome do produto" class="w-full px-2 py-1 bg-white border border-slate-300 rounded-lg text-[11px] font-bold text-slate-800">
                            <div class="flex gap-1">
                                <input type="text" id="ean_marca_${idGlobal}" value="${escapeHtml(prod.ean || '')}" placeholder="EAN" class="w-1/2 px-2 py-1 bg-white border border-slate-300 rounded-lg text-[10px] font-mono">
                                <input type="text" id="preco_marca_${idGlobal}" value="${prod.preco_sugerido ? prod.preco_sugerido.toFixed(2) : '0,00'}" placeholder="R$ 0,00" class="w-1/2 px-2 py-1 bg-white border border-slate-300 rounded-lg text-[10px] font-bold text-red-600">
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        });

        if (totalEncontrados === 0) {
            container.innerHTML = '<div class="text-slate-400 italic text-xs text-center py-6">Nenhum produto com fotos válidas foi encontrado.</div>';
            document.getElementById('boxAcaoCadastroLoteMarcas').classList.add('hidden');
            return;
        }

        container.innerHTML = html;
        document.getElementById('boxAcaoCadastroLoteMarcas').classList.remove('hidden');
        atualizarContadorMarcasSelecionadas();
    }

    function atualizarContadorMarcasSelecionadas() {
        const checkeds = document.querySelectorAll('input[name="chk_marca_prod"]:checked');
        const badge = document.getElementById('badgeContadorSelecionadosMarcas');
        badge.textContent = `${checkeds.length} produto(s) selecionado(s) para cadastro`;
    }

    function confirmarCadastroLoteMarcas() {
        const checkeds = document.querySelectorAll('input[name="chk_marca_prod"]:checked');
        if (checkeds.length === 0) {
            alert('Selecione ao menos um produto para cadastrar.');
            return;
        }

        const catPadrao = document.getElementById('selectCategoriaPadraoMarcas')?.value;
        const listaParaSalvar = [];

        checkeds.forEach(chk => {
            const idx = parseInt(chk.value, 10);
            const original = produtosMarcasEncontrados[idx];
            if (original) {
                const nomeEdit = document.getElementById(`nome_marca_${idx}`)?.value || original.nome;
                const eanEdit = document.getElementById(`ean_marca_${idx}`)?.value || original.ean;
                const precoEdit = document.getElementById(`preco_marca_${idx}`)?.value || '0.00';

                listaParaSalvar.push({
                    nome: nomeEdit,
                    marca: original.marca,
                    ean: eanEdit,
                    preco_venda: precoEdit,
                    categoria_id: catPadrao,
                    fotos: original.fotos || [],
                    videos: []
                });
            }
        });

        const btn = document.getElementById('btnCadastrarLoteMarcas');
        btn.disabled = true;
        btn.innerHTML = '⌛ Cadastrando produtos e baixando fotos...';

        fetch('<?= Url::to(['/vendas/produto-enriquecimento/cadastrar-lote-sugerido']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                produtos: listaParaSalvar,
                categoria_id: catPadrao,
                '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
            })
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<span>📥</span><span>Cadastrar Selecionados na Base</span>';

            if (data.success) {
                alert('🎉 ' + data.message);
                document.getElementById('inputMarcasSeparadas').value = '';
                document.getElementById('containerResultadoMarcas').innerHTML = '<div class="text-emerald-600 font-bold text-xs text-center py-6 bg-emerald-50 rounded-2xl border border-emerald-200">✅ Produtos cadastrados com sucesso!</div>';
                document.getElementById('boxAcaoCadastroLoteMarcas').classList.add('hidden');
                
                // Atualiza listagem da página
                if (typeof window.location.reload === 'function') {
                    // opcional reload
                }
            } else {
                alert('Erro no cadastro: ' + (data.message || 'Falha ao salvar.'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<span>📥</span><span>Cadastrar Selecionados na Base</span>';
            alert('Erro de comunicação: ' + err.message);
        });
    }

    // =========================================================================
    // ABA 3: LINK / URL
    // =========================================================================
    let dadosUrlExtraidosAtuais = null;

    function extrairDadosUrlWeb() {
        const url = document.getElementById('inputUrlImportar')?.value.trim();
        if (!url) {
            alert('Cole uma URL válida para extrair os dados.');
            return;
        }

        const btn = document.getElementById('btnExtrairUrl');
        btn.disabled = true;
        btn.innerHTML = '⌛ Extraindo dados e fotos...';

        const cardResultado = document.getElementById('cardResultadoExtracaoUrl');
        cardResultado.classList.add('hidden');

        fetch('<?= Url::to(['/vendas/produto-enriquecimento/extrair-url']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                url: url,
                '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
            })
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<span>⚡</span><span>Extrair Mídias e Dados</span>';

            if (data.success) {
                dadosUrlExtraidosAtuais = data;
                cardResultado.classList.remove('hidden');

                document.getElementById('urlExtrNome').value = data.dados?.nome || '';
                document.getElementById('urlExtrMarca').value = data.dados?.marca || '';
                document.getElementById('urlExtrEan').value = data.dados?.ean || '';
                if (data.dados?.preco) {
                    document.getElementById('urlExtrPreco').value = Number(data.dados.preco).toFixed(2).replace('.', ',');
                }

                // Renderiza fotos da URL
                const gridFotos = document.getElementById('gridFotosUrlExtraidas');
                let htmlFotos = '';
                const fotos = data.fotos || [];
                fotos.forEach((fUrl, fIdx) => {
                    htmlFotos += `
                        <label class="relative group cursor-pointer border-2 border-slate-200 rounded-2xl overflow-hidden bg-white p-1 hover:border-indigo-500 transition shadow-sm block">
                            <input type="checkbox" name="fotos_url_chk" value="${escapeHtml(fUrl)}" checked class="absolute top-2 left-2 z-10 rounded text-indigo-600">
                            <img src="${escapeHtml(fUrl)}" class="w-full h-24 object-contain rounded-xl bg-slate-50" onerror="this.src='<?= Url::base() ?>/img/encarte-cover-placeholder.png'">
                            <span class="block text-[10px] text-center text-slate-600 font-bold mt-1 truncate">Foto #${fIdx + 1}</span>
                        </label>
                    `;
                });
                gridFotos.innerHTML = htmlFotos || '<div class="col-span-full text-slate-400 italic text-xs">Nenhuma imagem direta encontrada.</div>';

            } else {
                alert('Erro ao extrair da URL: ' + (data.message || 'Página inacessível.'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<span>⚡</span><span>Extrair Mídias e Dados</span>';
            alert('Erro de conexão: ' + err.message);
        });
    }

    function confirmarCadastroProdutoUrl() {
        const nome = document.getElementById('urlExtrNome')?.value.trim();
        if (!nome) {
            alert('Informe o nome do produto.');
            return;
        }

        const marca = document.getElementById('urlExtrMarca')?.value.trim();
        const ean = document.getElementById('urlExtrEan')?.value.trim();
        const preco = document.getElementById('urlExtrPreco')?.value.trim();
        const catId = document.getElementById('urlExtrCategoria')?.value;

        const fotosChecked = Array.from(document.querySelectorAll('input[name="fotos_url_chk"]:checked')).map(c => c.value);
        const videos = dadosUrlExtraidosAtuais?.videos || [];

        const btn = document.getElementById('btnSalvarProdutoUrl');
        btn.disabled = true;
        btn.innerHTML = '⌛ Cadastrando produto...';

        const payload = {
            produtos: [{
                nome: nome,
                marca: marca,
                ean: ean,
                preco_venda: preco,
                categoria_id: catId,
                fotos: fotosChecked,
                videos: videos
            }],
            categoria_id: catId,
            '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
        };

        fetch('<?= Url::to(['/vendas/produto-enriquecimento/cadastrar-lote-sugerido']) ?>', {
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
            btn.innerHTML = '<span>💾</span><span>Cadastrar Este Produto na Base</span>';

            if (data.success) {
                alert('🎉 Produto cadastrado com sucesso!');
                document.getElementById('inputUrlImportar').value = '';
                document.getElementById('cardResultadoExtracaoUrl').classList.add('hidden');
                fecharModalEnriquecimentoWeb();
                window.location.reload();
            } else {
                alert('Erro ao cadastrar: ' + (data.message || 'Falha ao salvar produto.'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<span>💾</span><span>Cadastrar Este Produto na Base</span>';
            alert('Erro de conexão: ' + err.message);
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
</script>
