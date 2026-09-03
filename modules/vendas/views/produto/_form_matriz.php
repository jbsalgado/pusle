<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\modules\vendas\models\Produto $model */
/** @var app\modules\vendas\models\Categoria[] $categorias */
/** @var app\modules\vendas\models\ProdutoVariante[] $variantes */
/** @var app\modules\vendas\models\ProdutoFoto[] $fotos */

$isUpdate = !$model->isNewRecord;
?>

<div class="max-w-4xl mx-auto">
    <?php $form = ActiveForm::begin([
        'id' => 'form-produto-matriz',
        'options' => ['enctype' => 'multipart/form-data', 'class' => 'space-y-6'],
    ]); ?>

    <!-- CARD 1: DADOS BASE DO PRODUTO (COMPACTO E ERGONÔMICO) -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="bg-gradient-to-r from-slate-900 to-indigo-950 px-4 py-3.5 text-white flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-indigo-500/30 flex items-center justify-center text-indigo-300 font-black text-sm">1</span>
                <div>
                    <h2 class="text-sm sm:text-base font-bold leading-tight">Dados Principais do Produto</h2>
                    <p class="text-[11px] text-slate-300">Informações gerais que valem para todas as variações</p>
                </div>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-500/20 text-indigo-200 border border-indigo-400/20">
                Modo Matriz
            </span>
        </div>

        <div class="p-4 sm:p-5 space-y-4">
            <!-- Linha 1: Nome do Produto -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Nome do Produto <span class="text-red-500">*</span></label>
                <?= $form->field($model, 'nome', ['template' => '{input}{error}'])->textInput([
                    'placeholder' => 'Ex: Tênis Esportivo Runner Air',
                    'class' => 'w-full text-sm font-semibold px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none uppercase transition',
                    'required' => true,
                    'id' => 'input-nome-produto'
                ]) ?>
            </div>

            <!-- Linha 2: Categoria e Código de Referência -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Categoria <span class="text-red-500">*</span></label>
                    <select name="Produto[categoria_id]" required class="w-full text-sm font-medium px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                        <option value="">Selecione uma categoria...</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat->id ?>" <?= ($model->categoria_id == $cat->id) ? 'selected' : '' ?>>
                                <?= Html::encode($cat->nome) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Código de Referência / SKU</label>
                    <?= $form->field($model, 'codigo_referencia', ['template' => '{input}{error}'])->textInput([
                        'placeholder' => 'Ex: TEN-RUN-01',
                        'class' => 'w-full text-sm font-semibold px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none uppercase transition',
                        'id' => 'input-codigo-ref'
                    ]) ?>
                </div>
            </div>

            <!-- Linha 3: Preço de Venda e Preço de Custo -->
            <div class="grid grid-cols-2 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Preço de Venda (R$) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-gray-400 font-bold text-xs">R$</span>
                        <?= $form->field($model, 'preco_venda_sugerido', ['template' => '{input}{error}'])->input('number', [
                            'step' => '0.01',
                            'min' => '0',
                            'placeholder' => '0,00',
                            'class' => 'w-full pl-9 pr-3 py-2 text-sm font-bold text-emerald-600 bg-gray-50 border border-gray-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none transition',
                            'required' => true,
                            'id' => 'input-preco-venda'
                        ]) ?>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Preço de Custo (R$)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-gray-400 font-bold text-xs">R$</span>
                        <?= $form->field($model, 'preco_custo', ['template' => '{input}{error}'])->input('number', [
                            'step' => '0.01',
                            'min' => '0',
                            'placeholder' => '0,00',
                            'class' => 'w-full pl-9 pr-3 py-2 text-sm font-bold text-gray-700 bg-gray-50 border border-gray-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none transition',
                            'id' => 'input-preco-custo'
                        ]) ?>
                    </div>
                </div>
            </div>

            <!-- Foto de Capa Geral (Opcional) -->
            <div class="pt-2 border-t border-gray-100">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Foto Principal do Produto (Capa Geral)</label>
                <div class="flex items-center gap-3">
                    <label class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-gray-300 bg-gray-50 hover:bg-gray-100 text-gray-700 text-xs font-semibold active:scale-95 transition">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Escolher Foto Geral</span>
                        <input type="file" name="Produto[fotos][]" id="input-foto-principal" accept="image/*" class="hidden">
                    </label>
                    <span id="label-foto-principal" class="text-xs text-gray-500 truncate max-w-xs">Nenhum arquivo selecionado</span>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD 2: MATRIZ DE VARIAÇÕES (CORES X TAMANHOS X FOTOS POR COR) -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        
        <!-- Topo com Indicador de Estoque Consolidado -->
        <div class="bg-gradient-to-r from-indigo-900 to-indigo-800 px-4 py-3.5 text-white flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-indigo-500/30 flex items-center justify-center text-indigo-200 font-black text-sm">2</span>
                <div>
                    <h2 class="text-sm sm:text-base font-bold leading-tight">Grade de Variações & Fotos por Modelo/Cor</h2>
                    <p class="text-[11px] text-indigo-200">Defina modelos ou cores, fotos correspondentes e os tamanhos ativos</p>
                </div>
            </div>
            <div class="text-right shrink-0">
                <span class="text-[10px] uppercase font-bold text-indigo-300 block tracking-wider">Estoque Total</span>
                <span id="badge-total-consolidado" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-emerald-500 text-white shadow-xs">
                    0 un
                </span>
            </div>
        </div>

        <div class="p-4 sm:p-5 space-y-5">

            <!-- ETAPA A: ADICIONAR E SELECIONAR MODELO/CORES (CHIPS CARROSSEL) -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-700">A. Modelo/Cores do Produto</label>
                    <span id="label-status-cor" class="text-xs font-bold text-indigo-600">Selecione ou adicione um modelo/cor</span>
                </div>

                <!-- Input para Adicionar Novo Modelo/Cor -->
                <div class="flex gap-2 mb-1.5">
                    <input type="text" id="input-add-cor" placeholder="Ex: PRETO, AZUL, SLIM PRETO, ESTAMPADO FLORAL, MODELO A..."
                           class="flex-1 text-sm font-semibold px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none uppercase">
                    <button type="button" id="btn-confirmar-cor"
                            class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs sm:text-sm font-bold rounded-xl shadow-xs transition flex items-center gap-1.5 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Adicionar Modelo/Cor</span>
                    </button>
                </div>
                <p class="text-[11px] text-gray-500 mb-3 flex items-center gap-1">
                    <span>💡</span> <span>Adicione um modelo/cor por vez ou <strong>vários separados por vírgula</strong> de uma só vez.</span>
                </p>

                <!-- Carrossel de Chips de Modelos/Cores -->
                <div id="wrapper-chips-cores" class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-thin">
                    <span class="text-xs text-gray-400 italic py-1" id="msg-sem-cores">Nenhum modelo/cor cadastrado. Digite acima e clique em "Adicionar Modelo/Cor".</span>
                </div>
            </div>

            <!-- ETAPA B: PAINEL DO MODELO/COR ATIVO (FOTOS E TAMANHOS) -->
            <div id="painel-cor-ativa" class="border-t border-gray-100 pt-4 hidden space-y-4">
                
                <!-- B.1: VÍNCULO DE FOTOS DO MODELO/COR SELECIONADO -->
                <div class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-3.5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-950 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Fotos para o modelo/cor: <span id="label-cor-fotos" class="text-indigo-600 underline font-black"></span>
                            </h3>
                            <p class="text-[11px] text-indigo-700">Imagens anexadas aqui serão exibidas quando o cliente escolher este modelo/cor</p>
                        </div>

                        <!-- Botão de Upload do Modelo/Cor -->
                        <label class="cursor-pointer inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs active:scale-95 transition shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>+ Anexar Fotos deste Modelo/Cor</span>
                            <input type="file" id="input-fotos-cor-temp" accept="image/*" multiple class="hidden">
                        </label>
                    </div>

                    <!-- Galeria de Fotos do Modelo/Cor Ativo -->
                    <div id="galeria-fotos-cor" class="flex flex-wrap gap-2.5 min-h-[50px] items-center">
                        <span class="text-xs text-gray-400 italic">Nenhuma foto adicionada para este modelo/cor ainda.</span>
                    </div>
                </div>

                <!-- B.2: PRESETS DE TAMANHOS (LETRAS P/M/G E NÚMEROS 12 A 50) -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-gray-700">Tamanhos disponíveis para este modelo/cor</label>
                        <span class="text-[11px] text-gray-400">Letras (P, M, G) ou Números (12-50)</span>
                    </div>

                    <!-- Presets Rápidos: Vestuário e Calçados -->
                    <div class="space-y-1.5 mb-2.5">
                        <!-- Linha Vestuário (Letras) -->
                        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-thin">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider shrink-0 mr-1">Vestuário:</span>
                            <button type="button" class="btn-grade-preset px-2.5 py-1.5 text-xs font-bold rounded-lg border border-purple-200 bg-purple-50 hover:bg-purple-100 active:scale-95 transition text-purple-800 shrink-0"
                                    data-sizes="P,M,G,GG">
                                👕 P, M, G, GG
                            </button>
                            <button type="button" class="btn-grade-preset px-2.5 py-1.5 text-xs font-bold rounded-lg border border-purple-200 bg-purple-50 hover:bg-purple-100 active:scale-95 transition text-purple-800 shrink-0"
                                    data-sizes="PP,P,M,G,GG,XG,G1">
                                👗 PP ao G1
                            </button>
                            <button type="button" class="btn-grade-preset px-2.5 py-1.5 text-xs font-bold rounded-lg border border-purple-200 bg-purple-50 hover:bg-purple-100 active:scale-95 transition text-purple-800 shrink-0"
                                    data-sizes="UN">
                                ✨ Tamanho Único (UN)
                            </button>
                        </div>

                        <!-- Linha Calçados e Numeração (Pares & Ímpares) -->
                        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-thin">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider shrink-0 mr-1">Calçados:</span>
                            <button type="button" class="btn-grade-preset px-2.5 py-1.5 text-xs font-bold rounded-lg border border-indigo-200 bg-indigo-50 hover:bg-indigo-100 active:scale-95 transition text-indigo-800 shrink-0"
                                    data-sizes="34,35,36,37,38,39,40,41,42,43,44">
                                👟 Adulto (34 ao 44)
                            </button>
                            <button type="button" class="btn-grade-preset px-2.5 py-1.5 text-xs font-bold rounded-lg border border-indigo-200 bg-indigo-50 hover:bg-indigo-100 active:scale-95 transition text-indigo-800 shrink-0"
                                    data-sizes="27,28,29,30,31,32,33,34">
                                👦 Juvenil (27 ao 34)
                            </button>
                            <button type="button" class="btn-grade-preset px-2.5 py-1.5 text-xs font-bold rounded-lg border border-indigo-200 bg-indigo-50 hover:bg-indigo-100 active:scale-95 transition text-indigo-800 shrink-0"
                                    data-sizes="18,19,20,21,22,23,24,25,26">
                                👶 Infantil (18 ao 26)
                            </button>
                            <button type="button" class="btn-grade-preset px-2.5 py-1.5 text-xs font-bold rounded-lg border border-indigo-200 bg-indigo-50 hover:bg-indigo-100 active:scale-95 transition text-indigo-800 shrink-0"
                                    data-sizes="46,48,50">
                                ⭐ Especial (46-50)
                            </button>
                        </div>
                    </div>

                    <!-- Inclusão de Tamanho Avulso (Letras ou Números, 1 a 1 ou por vírgula) -->
                    <div class="flex items-center gap-2">
                        <input type="text" id="input-tam-avulso" placeholder="Ex: P, M, G ou 37, 39, 41..."
                               class="flex-1 sm:w-56 text-xs font-bold px-3 py-2 bg-gray-50 border border-gray-300 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 outline-none uppercase">
                        <button type="button" id="btn-add-tam-avulso" class="px-3.5 py-2 bg-gray-200 hover:bg-gray-300 active:bg-gray-400 text-gray-800 text-xs font-bold rounded-xl transition shrink-0">
                            + Incluir Tamanho(s)
                        </button>

                        <div class="ml-auto">
                            <!-- Acelerador de Produtividade -->
                            <button type="button" id="btn-replicar-grade" class="px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-xl transition flex items-center gap-1.5 border border-indigo-200/60 shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                                <span>Replicar p/ outros modelos/cores</span>
                            </button>
                        </div>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-1">
                        Dica: Digite letras (ex: P, M, G, GG) ou números ímpares/pares, individuais ou separados por vírgula.
                    </p>
                </div>

                <!-- B.3: GRADE COMPACTA COM STEPPERS DE TOQUE (2 a 3 COLUNAS) -->
                <div>
                    <div id="grid-cards-tamanhos" class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 mt-3">
                        <!-- Cards gerados via JS com stepper [-] Qtd [+] -->
                    </div>

                    <!-- Subtotal deste Modelo/Cor -->
                    <div class="mt-3 p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs">
                        <span class="text-gray-600 font-medium">Subtotal do modelo/cor <strong id="nome-cor-subtotal" class="text-gray-900"></strong>:</span>
                        <span id="badge-subtotal-cor" class="font-black text-gray-900 bg-white px-2 py-0.5 rounded-md border border-gray-200">0 itens</span>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Inputs Ocultos Gerados Dinamicamente para o POST -->
    <div id="container-hidden-inputs"></div>

    <!-- CONTAINER DE UPLOADS DE FOTOS POR COR (INPUTS MULTIPART) -->
    <div id="container-inputs-file-cores" class="hidden"></div>

    <!-- BARRA DE AÇÃO FIXA / BOTÃO DE SALVAMENTO -->
    <div class="flex items-center justify-between pt-2">
        <a href="<?= Url::to(['index']) ?>" class="px-4 py-2.5 border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-xl text-xs sm:text-sm font-bold transition">
            Cancelar
        </a>

        <button type="submit" id="btn-submeter-produto" class="px-6 py-3 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white font-bold text-sm sm:text-base rounded-xl shadow-md hover:shadow-lg active:scale-95 transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>Salvar Produto na Matriz</span>
        </button>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT REATIVO: ESTADO DA MATRIZ, FOTOS POR COR E STEPPERS ERGONÔMICOS -->
<!-- ========================================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Estrutura de Estado:
    // matrixState[COR] = {
    //     sizes: { "38": { id: "uuid", estoque: 4, ean: "", preco: 0 } },
    //     existingPhotos: [ { id: "uuid", url: "..." } ],
    //     newFiles: [] // File objects
    // }
    const matrixState = {};
    let activeColor = null;

    // Elementos do DOM
    const inputAddCor = document.getElementById('input-add-cor');
    const btnConfirmarCor = document.getElementById('btn-confirmar-cor');
    const wrapperChips = document.getElementById('wrapper-chips-cores');
    const msgSemCores = document.getElementById('msg-sem-cores');
    const painelCorAtiva = document.getElementById('painel-cor-ativa');
    const labelStatusCor = document.getElementById('label-status-cor');
    const labelCorFotos = document.getElementById('label-cor-fotos');
    const galeriaFotosCor = document.getElementById('galeria-fotos-cor');
    const inputFotosCorTemp = document.getElementById('input-fotos-cor-temp');
    const gridCards = document.getElementById('grid-cards-tamanhos');
    const badgeTotalConsolidado = document.getElementById('badge-total-consolidado');
    const nomeCorSubtotal = document.getElementById('nome-cor-subtotal');
    const badgeSubtotalCor = document.getElementById('badge-subtotal-cor');
    const containerHiddenInputs = document.getElementById('container-hidden-inputs');
    const containerInputsFile = document.getElementById('container-inputs-file-cores');
    const inputFotoPrincipal = document.getElementById('input-foto-principal');
    const labelFotoPrincipal = document.getElementById('label-foto-principal');

    // Label do input de foto principal
    if (inputFotoPrincipal) {
        inputFotoPrincipal.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                labelFotoPrincipal.textContent = this.files[0].name;
                labelFotoPrincipal.classList.add('text-indigo-600', 'font-bold');
            } else {
                labelFotoPrincipal.textContent = 'Nenhum arquivo selecionado';
                labelFotoPrincipal.classList.remove('text-indigo-600', 'font-bold');
            }
        });
    }

    // 1. ADICIONAR NOVA COR (Aceita uma a uma OU separadas por vírgula em lote)
    function adicionarCor(entrada) {
        if (!entrada) return;

        // Divide por vírgula para permitir cadastro em lote
        const cores = entrada.split(',')
            .map(c => c.trim().toUpperCase())
            .filter(c => c !== '');

        if (cores.length === 0) return;

        let ultimaCor = null;
        cores.forEach(cor => {
            if (!matrixState[cor]) {
                matrixState[cor] = {
                    sizes: {},
                    existingPhotos: [],
                    newFiles: []
                };
            }
            ultimaCor = cor;
        });

        inputAddCor.value = '';
        renderizarChips();

        // Foca na última cor adicionada
        if (ultimaCor) {
            selecionarCor(ultimaCor);
        }
    }

    btnConfirmarCor.addEventListener('click', () => adicionarCor(inputAddCor.value));
    inputAddCor.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            adicionarCor(inputAddCor.value);
        }
    });

    // 2. RENDERIZAR CHIPS DE MODELOS/CORES
    function renderizarChips() {
        const cores = Object.keys(matrixState);
        if (cores.length === 0) {
            msgSemCores.classList.remove('hidden');
            painelCorAtiva.classList.add('hidden');
            labelStatusCor.textContent = 'Selecione ou adicione um modelo/cor';
            return;
        }

        msgSemCores.classList.add('hidden');
        wrapperChips.innerHTML = '';

        cores.forEach(cor => {
            const isActive = (cor === activeColor);
            const totalCor = Object.values(matrixState[cor].sizes).reduce((acc, s) => acc + (parseInt(s.estoque) || 0), 0);
            const totalFotos = (matrixState[cor].existingPhotos.length + matrixState[cor].newFiles.length);

            const chip = document.createElement('div');
            chip.className = `flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold cursor-pointer transition select-none shrink-0 ${
                isActive 
                    ? 'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-300' 
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`;

            chip.innerHTML = `
                <span>${cor}</span>
                ${totalFotos > 0 ? `<span class="text-[10px]" title="${totalFotos} fotos">📷${totalFotos}</span>` : ''}
                <span class="px-1.5 py-0.2 rounded-full text-[10px] ${isActive ? 'bg-indigo-800 text-indigo-100' : 'bg-gray-200 text-gray-600'}">
                    ${totalCor} un
                </span>
                <button type="button" class="ml-1 hover:text-red-300 font-bold p-0.5" title="Remover Modelo/Cor" data-cor="${cor}">
                    &times;
                </button>
            `;

            chip.addEventListener('click', (e) => {
                if (e.target.tagName.toLowerCase() === 'button') {
                    e.stopPropagation();
                    removerCor(cor);
                } else {
                    selecionarCor(cor);
                }
            });

            wrapperChips.appendChild(chip);
        });

        atualizarTotaisConsolidados();
    }

    // 3. SELECIONAR MODELO/COR ATIVO
    function selecionarCor(cor) {
        activeColor = cor;
        labelStatusCor.textContent = `Modelo/Cor selecionado: ${cor}`;
        labelCorFotos.textContent = cor;
        nomeCorSubtotal.textContent = cor;
        painelCorAtiva.classList.remove('hidden');

        renderizarChips();
        renderizarFotosDaCor();
        renderizarCardsTamanhos();
    }

    // 4. REMOVER MODELO/COR
    function removerCor(cor) {
        if (confirm(`Deseja remover o modelo/cor "${cor}" e todos os tamanhos e fotos vinculadas?`)) {
            delete matrixState[cor];
            if (activeColor === cor) {
                const restantes = Object.keys(matrixState);
                activeColor = restantes.length > 0 ? restantes[0] : null;
            }
            renderizarChips();
            if (activeColor) {
                selecionarCor(activeColor);
            } else {
                painelCorAtiva.classList.add('hidden');
            }
            atualizarTotaisConsolidados();
        }
    }

    // 5. GERENCIAMENTO DE FOTOS DA COR ATIVA
    if (inputFotosCorTemp) {
        inputFotosCorTemp.addEventListener('change', function() {
            if (!activeColor || !this.files) return;

            for (let i = 0; i < this.files.length; i++) {
                matrixState[activeColor].newFiles.push(this.files[i]);
            }
            this.value = '';
            renderizarFotosDaCor();
            renderizarChips();
            sincronizarArquivosFormulario();
        });
    }

    function renderizarFotosDaCor() {
        if (!activeColor) return;
        galeriaFotosCor.innerHTML = '';

        const existing = matrixState[activeColor].existingPhotos;
        const newFiles = matrixState[activeColor].newFiles;

        if (existing.length === 0 && newFiles.length === 0) {
            galeriaFotosCor.innerHTML = `<span class="text-xs text-gray-400 italic">Nenhuma foto adicionada para ${activeColor} ainda.</span>`;
            return;
        }

        // Fotos já salvas no banco
        existing.forEach(foto => {
            const thumb = document.createElement('div');
            thumb.className = 'relative group w-14 h-14 rounded-lg overflow-hidden border border-indigo-200 bg-white shadow-xs';
            thumb.innerHTML = `
                <img src="${foto.url}" class="w-full h-full object-cover">
                <button type="button" class="absolute inset-0 bg-red-600/80 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-xs font-bold" title="Remover Foto">
                    Excluir
                </button>
            `;
            thumb.querySelector('button').addEventListener('click', () => {
                if (confirm('Deseja excluir esta foto?')) {
                    fetch('<?= Url::to(["delete-foto"]) ?>?id=' + encodeURIComponent(foto.id), { credentials: 'same-origin' })
                        .then(() => {
                            matrixState[activeColor].existingPhotos = matrixState[activeColor].existingPhotos.filter(f => f.id !== foto.id);
                            renderizarFotosDaCor();
                            renderizarChips();
                        });
                }
            });
            galeriaFotosCor.appendChild(thumb);
        });

        // Novas fotos selecionadas (prévia local)
        newFiles.forEach((file, idx) => {
            const thumb = document.createElement('div');
            thumb.className = 'relative group w-14 h-14 rounded-lg overflow-hidden border border-emerald-300 bg-white shadow-xs';
            const reader = new FileReader();
            reader.onload = function(e) {
                thumb.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover">
                    <button type="button" class="absolute inset-0 bg-red-600/80 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-xs font-bold" title="Remover">
                        &times;
                    </button>
                `;
                thumb.querySelector('button').addEventListener('click', () => {
                    matrixState[activeColor].newFiles.splice(idx, 1);
                    renderizarFotosDaCor();
                    renderizarChips();
                    sincronizarArquivosFormulario();
                });
            };
            reader.readAsDataURL(file);
            galeriaFotosCor.appendChild(thumb);
        });
    }

    // Sincroniza arquivos de upload das cores no DOM antes do envio
    function sincronizarArquivosFormulario() {
        containerInputsFile.innerHTML = '';
        for (const cor in matrixState) {
            const files = matrixState[cor].newFiles;
            if (files.length > 0) {
                const dt = new DataTransfer();
                files.forEach(f => dt.items.add(f));
                const fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.name = `FotosCor[${cor}][]`;
                fileInput.multiple = true;
                fileInput.files = dt.files;
                containerInputsFile.appendChild(fileInput);
            }
        }
    }

    // 6. PRESETS DE TAMANHOS
    document.querySelectorAll('.btn-grade-preset').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!activeColor) {
                alert('Adicione e selecione um modelo/cor primeiro.');
                inputAddCor.focus();
                return;
            }

            const sizes = this.dataset.sizes.split(',').map(s => s.trim());
            sizes.forEach(tam => {
                if (!matrixState[activeColor].sizes[tam]) {
                    matrixState[activeColor].sizes[tam] = { estoque: 0, ean: '', preco: '' };
                }
            });

            renderizarCardsTamanhos();
            atualizarTotaisConsolidados();
        });
    });

    // Inclusão de tamanho avulso (aceita 1 a 1 ou vários por vírgula: P, M, G ou 37, 39, 41)
    function incluirTamanhosAvulsos() {
        if (!activeColor) {
            alert('Selecione um modelo/cor antes de incluir tamanhos.');
            return;
        }
        const input = document.getElementById('input-tam-avulso');
        const raw = input.value.trim();
        if (!raw) return;

        const listaTams = raw.split(',')
            .map(t => t.trim().toUpperCase())
            .filter(t => t !== '');

        listaTams.forEach(tam => {
            if (!matrixState[activeColor].sizes[tam]) {
                matrixState[activeColor].sizes[tam] = { estoque: 0, ean: '', preco: '' };
            }
        });

        input.value = '';
        renderizarCardsTamanhos();
        atualizarTotaisConsolidados();
    }

    const btnAddTamAvulso = document.getElementById('btn-add-tam-avulso');
    const inputTamAvulso = document.getElementById('input-tam-avulso');
    if (btnAddTamAvulso) {
        btnAddTamAvulso.addEventListener('click', incluirTamanhosAvulsos);
    }
    if (inputTamAvulso) {
        inputTamAvulso.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                incluirTamanhosAvulsos();
            }
        });
    }

    // 7. RENDERIZAR CARDS COM STEPPER NA COR ATIVA (ORDENAÇÃO INTELIGENTE DE LETRAS E NÚMEROS)
    function renderizarCardsTamanhos() {
        if (!activeColor || !matrixState[activeColor]) return;

        gridCards.innerHTML = '';

        // Ordem padrão de moda/confecção têxtil
        const ordemVestuario = [
            'RN', '0-3M', '3-6M', '6-9M', '9-12M', '1', '2', '3', '4',
            'PP', 'P', 'M', 'G', 'GG', 'XG', 'XXG', 'XGG', 
            'G1', 'G2', 'G3', 'G4', 'G5', 
            'XS', 'S', 'L', 'XL', '2XL', '3XL',
            'UN', 'U'
        ];

        const sizes = Object.keys(matrixState[activeColor].sizes).sort((a, b) => {
            const numA = parseFloat(a);
            const numB = parseFloat(b);

            // Se ambos forem números (ex: 36 e 37), ordena crescentemente
            if (!isNaN(numA) && !isNaN(numB)) {
                return numA - numB;
            }

            // Se ambos forem letras de vestuário conhecidas
            const idxA = ordemVestuario.indexOf(a);
            const idxB = ordemVestuario.indexOf(b);
            if (idxA !== -1 && idxB !== -1) {
                return idxA - idxB;
            }
            if (idxA !== -1) return -1;
            if (idxB !== -1) return 1;

            return a.localeCompare(b);
        });

        if (sizes.length === 0) {
            gridCards.innerHTML = `
                <div class="col-span-full py-6 text-center text-xs text-gray-400 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                    Nenhum tamanho ativo para a cor <strong>${activeColor}</strong>.<br>Clique em um preset acima ou digite um tamanho avulso.
                </div>
            `;
            badgeSubtotalCor.textContent = '0 itens';
            return;
        }

        let subtotal = 0;

        const inputPrecoPrincipal = document.getElementById('input-preco-venda');
        let precoBaseNum = 0;
        if (inputPrecoPrincipal && inputPrecoPrincipal.value) {
            precoBaseNum = parseFloat(inputPrecoPrincipal.value.replace(',', '.')) || 0;
        }
        const precoBaseFormatado = precoBaseNum > 0 ? precoBaseNum.toFixed(2).replace('.', ',') : '0,00';

        sizes.forEach(tam => {
            const item = matrixState[activeColor].sizes[tam];
            const qtd = parseInt(item.estoque) || 0;
            subtotal += qtd;
            const precoVariante = (item.preco !== undefined && item.preco !== null) ? String(item.preco).trim() : '';
            const temPrecoProprio = precoVariante !== '' && parseFloat(precoVariante.replace(',', '.')) > 0;

            const card = document.createElement('div');
            card.className = 'bg-white border border-gray-200 rounded-xl p-2.5 shadow-2xs hover:border-indigo-300 transition flex flex-col justify-between';

            card.innerHTML = `
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-black text-gray-900 bg-gray-100 px-2 py-0.5 rounded-md">
                        Tam: ${tam}
                    </span>
                    <div class="flex items-center gap-1.5">
                        <span class="badge-preco text-[10px] font-bold px-1.5 py-0.5 rounded ${temPrecoProprio ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-gray-100 text-gray-500'}" title="${temPrecoProprio ? 'Preço específico desta variação' : 'Usando preço base do produto'}">
                            ${temPrecoProprio ? 'Próprio' : 'Padrão'}
                        </span>
                        <button type="button" class="text-gray-300 hover:text-red-500 text-xs p-1" title="Remover tamanho" data-tam="${tam}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Stepper Ergonômico de Toque de Estoque -->
                <div class="mb-2">
                    <div class="text-[10px] font-bold uppercase text-gray-500 mb-1 flex items-center justify-between">
                        <span>Estoque</span>
                        <span class="text-[10px] text-gray-400 font-normal">Qtd</span>
                    </div>
                    <div class="flex items-center gap-1 bg-gray-50 p-1 rounded-lg border border-gray-200">
                        <button type="button" class="btn-minus w-8 h-8 rounded-md bg-white hover:bg-gray-100 active:bg-gray-200 border border-gray-300 font-black text-gray-700 text-sm flex items-center justify-center select-none">
                            -
                        </button>
                        <input type="number" min="0" value="${qtd}" 
                               class="input-val w-full text-center bg-transparent font-bold text-gray-900 text-sm outline-none">
                        <button type="button" class="btn-plus w-8 h-8 rounded-md bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-black text-sm flex items-center justify-center select-none">
                            +
                        </button>
                    </div>
                </div>

                <!-- Preço Individual da Variação (Opcional - padrão é o valor principal) -->
                <div class="pt-2 border-t border-gray-100">
                    <div class="text-[10px] font-bold uppercase text-gray-500 mb-1 flex items-center justify-between">
                        <span>Preço Venda</span>
                        <span class="text-[9px] text-gray-400 font-normal">Vazio = padrão</span>
                    </div>
                    <div class="relative flex items-center">
                        <span class="absolute left-2.5 text-gray-400 font-bold text-xs pointer-events-none">R$</span>
                        <input type="text" 
                               class="input-preco w-full pl-7 pr-2 py-1.5 bg-gray-50 hover:bg-white focus:bg-white border ${temPrecoProprio ? 'border-emerald-300 bg-emerald-50/30' : 'border-gray-200'} focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 rounded-lg text-xs font-bold text-gray-800 transition outline-none" 
                               placeholder="${precoBaseFormatado}" 
                               value="${precoVariante}">
                    </div>
                </div>
            `;

            // Eventos do Stepper
            const minus = card.querySelector('.btn-minus');
            const plus = card.querySelector('.btn-plus');
            const inputVal = card.querySelector('.input-val');
            const removeBtn = card.querySelector('button[data-tam]');
            const inputPreco = card.querySelector('.input-preco');
            const badgePreco = card.querySelector('.badge-preco');

            minus.addEventListener('click', () => {
                let v = Math.max(0, (parseInt(inputVal.value) || 0) - 1);
                inputVal.value = v;
                item.estoque = v;
                atualizarTotaisConsolidados();
            });

            plus.addEventListener('click', () => {
                let v = (parseInt(inputVal.value) || 0) + 1;
                inputVal.value = v;
                item.estoque = v;
                atualizarTotaisConsolidados();
            });

            inputVal.addEventListener('change', () => {
                let v = Math.max(0, parseInt(inputVal.value) || 0);
                inputVal.value = v;
                item.estoque = v;
                atualizarTotaisConsolidados();
            });

            inputPreco.addEventListener('input', (e) => {
                const val = e.target.value.trim();
                item.preco = val;
                const temProprio = val !== '' && parseFloat(val.replace(',', '.')) > 0;
                if (temProprio) {
                    badgePreco.textContent = 'Próprio';
                    badgePreco.className = 'badge-preco text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-300';
                    inputPreco.classList.add('border-emerald-300', 'bg-emerald-50/30');
                    inputPreco.classList.remove('border-gray-200');
                } else {
                    badgePreco.textContent = 'Padrão';
                    badgePreco.className = 'badge-preco text-[10px] font-bold px-1.5 py-0.5 rounded bg-gray-100 text-gray-500';
                    inputPreco.classList.remove('border-emerald-300', 'bg-emerald-50/30');
                    inputPreco.classList.add('border-gray-200');
                }
                atualizarTotaisConsolidados();
            });

            removeBtn.addEventListener('click', () => {
                delete matrixState[activeColor].sizes[tam];
                renderizarCardsTamanhos();
                atualizarTotaisConsolidados();
            });

            gridCards.appendChild(card);
        });

        badgeSubtotalCor.textContent = `${subtotal} itens`;
    }

    // 8. REPLICAR GRADE DO MODELO/COR ATIVO PARA OS DEMAIS
    document.getElementById('btn-replicar-grade').addEventListener('click', () => {
        if (!activeColor) return;
        const currentSizes = matrixState[activeColor].sizes;
        const cores = Object.keys(matrixState);

        if (cores.length <= 1) {
            alert('Adicione outros modelos/cores primeiro para poder replicar.');
            return;
        }

        if (confirm(`Deseja replicar a grade e quantidades do modelo/cor "${activeColor}" para todos os outros modelos/cores?`)) {
            cores.forEach(cor => {
                if (cor !== activeColor) {
                    matrixState[cor].sizes = JSON.parse(JSON.stringify(currentSizes));
                    for (const s in matrixState[cor].sizes) {
                        delete matrixState[cor].sizes[s].id;
                    }
                }
            });
            renderizarChips();
            atualizarTotaisConsolidados();
            alert('Grade replicada para os demais modelos/cores com sucesso!');
        }
    });

    // 9. ATUALIZAR TOTAIS CONSOLIDADOS E INPUTS HIDDEN DO POST
    function atualizarTotaisConsolidados() {
        let totalGeral = 0;
        containerHiddenInputs.innerHTML = '';
        let itemIndex = 0;

        for (const cor in matrixState) {
            for (const tam in matrixState[cor].sizes) {
                const item = matrixState[cor].sizes[tam];
                const qtd = parseInt(item.estoque) || 0;
                totalGeral += qtd;

                // Inputs para MatrizGrade[index]
                const prefix = `MatrizGrade[${itemIndex}]`;
                let hiddenHtml = `
                    <input type="hidden" name="${prefix}[cor]" value="${cor}">
                    <input type="hidden" name="${prefix}[tamanho]" value="${tam}">
                    <input type="hidden" name="${prefix}[estoque]" value="${qtd}">
                    <input type="hidden" name="${prefix}[preco]" value="${item.preco || ''}">
                    <input type="hidden" name="${prefix}[ean]" value="${item.ean || ''}">
                `;
                if (item.id) {
                    hiddenHtml += `<input type="hidden" name="${prefix}[id]" value="${item.id}">`;
                }
                containerHiddenInputs.insertAdjacentHTML('beforeend', hiddenHtml);
                itemIndex++;
            }
        }

        badgeTotalConsolidado.textContent = `${totalGeral} un`;
        if (activeColor && matrixState[activeColor]) {
            const sub = Object.values(matrixState[activeColor].sizes).reduce((acc, s) => acc + (parseInt(s.estoque) || 0), 0);
            badgeSubtotalCor.textContent = `${sub} itens`;
        }
    }

    // Atualização dinâmica do placeholder dos cards quando o preço de venda principal muda
    const inputPrecoPrincipal = document.getElementById('input-preco-venda');
    if (inputPrecoPrincipal) {
        inputPrecoPrincipal.addEventListener('input', () => {
            let valNum = parseFloat(inputPrecoPrincipal.value.replace(',', '.')) || 0;
            const formatado = valNum > 0 ? valNum.toFixed(2).replace('.', ',') : '0,00';
            document.querySelectorAll('.input-preco').forEach(inp => {
                inp.placeholder = formatado;
            });
        });
    }

    // Intercepta o envio do formulário para garantir que as fotos anexadas vão no FormData
    document.getElementById('form-produto-matriz').addEventListener('submit', function() {
        sincronizarArquivosFormulario();
    });

    // 10. CARREGAMENTO INICIAL SE MODO EDIÇÃO (UPDATE)
    <?php if ($isUpdate && !empty($variantes)): ?>
        <?php foreach ($variantes as $v): ?>
            (function() {
                const c = '<?= addslashes($v->cor) ?>';
                const t = '<?= addslashes($v->tamanho) ?>';
                if (!matrixState[c]) {
                    matrixState[c] = { sizes: {}, existingPhotos: [], newFiles: [] };
                }
                matrixState[c].sizes[t] = {
                    id: '<?= $v->id ?>',
                    estoque: <?= (int)$v->estoque_atual ?>,
                    ean: '<?= addslashes($v->codigo_barras ?: "") ?>',
                    preco: '<?= ($v->preco_venda_sugerido !== null && (float)$v->preco_venda_sugerido > 0) ? number_format((float)$v->preco_venda_sugerido, 2, ',', '.') : '' ?>'
                };
            })();
        <?php endforeach; ?>

        <?php if (!empty($fotos)): ?>
            <?php foreach ($fotos as $f): ?>
                <?php if (!empty($f->cor)): ?>
                    (function() {
                        const c = '<?= addslashes($f->cor) ?>';
                        if (!matrixState[c]) {
                            matrixState[c] = { sizes: {}, existingPhotos: [], newFiles: [] };
                        }
                        matrixState[c].existingPhotos.push({
                            id: '<?= $f->id ?>',
                            url: '<?= addslashes($f->getUrl()) ?>'
                        });
                    })();
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        const initialColors = Object.keys(matrixState);
        if (initialColors.length > 0) {
            renderizarChips();
            selecionarCor(initialColors[0]);
        }
    <?php endif; ?>

});
</script>
