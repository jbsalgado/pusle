<?php
use yii\helpers\Url;
use yii\helpers\Html;
use app\modules\vendas\models\Categoria;

$categorias = Categoria::find()
    ->where(['usuario_id' => $lojaId, 'ativo' => true])
    ->orderBy(['nome' => SORT_ASC])
    ->all();
?>

<!-- Modal Cadastro Rápido Express -->
<div id="modalCadastroRapido" class="fixed inset-0 z-[120] hidden bg-slate-900/80 backdrop-blur-md flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full overflow-hidden text-slate-900 border border-slate-100 transform transition-all my-8 relative flex flex-col max-h-[90vh]">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-red-600 via-amber-600 to-red-600 text-white p-4 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center text-xl font-bold">
                    ⚡
                </div>
                <div>
                    <h3 class="font-extrabold text-base leading-tight">Cadastro Rápido de Produto</h3>
                    <p class="text-xs text-amber-100 font-medium">Cadastre em 10 segundos focado em Encartes e Catálogos</p>
                </div>
            </div>
            <button type="button" onclick="fecharModalCadastroRapido()" class="text-white/80 hover:text-white bg-black/20 hover:bg-black/40 rounded-full p-2 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Form Body -->
        <form id="formCadastroRapido" onsubmit="salvarProdutoRapido(event)" class="p-5 space-y-4 overflow-y-auto flex-1">
            
            <!-- 1. Nome do Produto -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nome do Produto <span class="text-red-500">*</span></label>
                <input type="text" id="rapido_nome" required placeholder="Ex: Arroz Tio João Tipo 1 5kg" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-red-500 focus:bg-white focus:outline-none">
            </div>

            <!-- 2. Preço de Venda e Unidade -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Preço de Venda / Oferta (R$) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-2.5 text-xs font-bold text-slate-500">R$</span>
                        <input type="text" id="rapido_preco" required placeholder="0,00" class="w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-montserrat font-black text-red-600 focus:ring-2 focus:ring-red-500 focus:bg-white focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Unidade de Medida</label>
                    <select id="rapido_unidade" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold focus:ring-2 focus:ring-red-500 focus:outline-none">
                        <option value="UN" selected>UN (Unidade)</option>
                        <option value="KG">KG (Quilograma)</option>
                        <option value="PCT">PCT (Pacote)</option>
                        <option value="CX">CX (Caixa)</option>
                        <option value="L">L (Litro)</option>
                        <option value="M">M (Metro)</option>
                        <option value="PAR">PAR (Par)</option>
                    </select>
                </div>
            </div>

            <!-- 3. Categoria e Marca -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Categoria</label>
                    <div class="flex gap-1.5">
                        <select id="rapido_categoria_id" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-red-500 focus:outline-none">
                            <option value="">Geral / Ofertas</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat->id ?>"><?= Html::encode($cat->nome) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Marca (Opcional)</label>
                    <input type="text" id="rapido_marca" placeholder="Ex: Nestle, Ypê..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-red-500 focus:bg-white focus:outline-none">
                </div>
            </div>

            <!-- 4. Upload Múltiplo de Fotos -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">📸 Fotos do Produto (Múltiplas Fotos)</label>
                <div onclick="document.getElementById('rapido_fotos').click()" class="border-2 border-dashed border-slate-300 hover:border-red-500 bg-slate-50 hover:bg-red-50/50 p-4 rounded-2xl text-center cursor-pointer transition flex flex-col items-center justify-center gap-1.5">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <div class="text-xs font-bold text-slate-700">Clique para selecionar ou arraste várias fotos</div>
                    <div class="text-[10px] text-slate-400">JPG, PNG ou WEBP • A primeira foto será a capa</div>
                </div>
                <input type="file" id="rapido_fotos" multiple accept="image/*" class="hidden" onchange="previewFotosRapidas(this)">

                <!-- Container de Preview de Fotos -->
                <div id="containerPreviewFotos" class="flex items-center gap-2 overflow-x-auto py-2 empty:hidden"></div>
            </div>

            <!-- Preço de Custo Opcional (Oculto por padrão, expansível) -->
            <details class="text-xs text-slate-500 border-t border-slate-100 pt-2">
                <summary class="font-bold text-slate-600 cursor-pointer hover:text-red-600">➕ Mais Detalhes Opcionais (Preço de Custo / Código de Barras)</summary>
                <div class="grid grid-cols-2 gap-3 pt-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase mb-1">Preço de Custo (R$)</label>
                        <input type="text" id="rapido_preco_custo" placeholder="0,00" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase mb-1">Código de Barras / Ref</label>
                        <input type="text" id="rapido_codigo_barras" placeholder="789..." class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs">
                    </div>
                </div>
            </details>

            <!-- Rodapé e Ações -->
            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-3">
                <button type="button" onclick="fecharModalCadastroRapido()" class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                    Cancelar
                </button>
                <button type="submit" id="btnSalvarRapido" class="px-5 py-3 bg-gradient-to-r from-red-600 to-amber-600 hover:from-red-700 hover:to-amber-700 text-white font-extrabold text-xs rounded-xl shadow-lg transition flex items-center gap-2">
                    <span>⚡ Salvar &amp; Adicionar ao Encarte</span>
                </button>
            </div>

        </form>

    </div>
</div>

<script>
    let arquivosFotosRapidas = [];

    function abrirModalCadastroRapido() {
        document.getElementById('formCadastroRapido').reset();
        arquivosFotosRapidas = [];
        document.getElementById('containerPreviewFotos').innerHTML = '';
        document.getElementById('modalCadastroRapido').classList.remove('hidden');
    }

    function fecharModalCadastroRapido() {
        document.getElementById('modalCadastroRapido').classList.add('hidden');
    }

    function previewFotosRapidas(input) {
        const container = document.getElementById('containerPreviewFotos');
        container.innerHTML = '';
        arquivosFotosRapidas = Array.from(input.files);

        arquivosFotosRapidas.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative w-16 h-16 rounded-xl border-2 border-slate-200 overflow-hidden flex-shrink-0 group bg-slate-100';
                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-contain">
                    ${index === 0 ? '<span class="absolute bottom-0 inset-x-0 bg-red-600 text-white text-[8px] font-bold text-center py-0.5">CAPA</span>' : ''}
                `;
                container.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    function salvarProdutoRapido(e) {
        e.preventDefault();
        
        const btn = document.getElementById('btnSalvarRapido');
        btn.disabled = true;
        btn.innerHTML = '⚡ Salvando Produto...';

        const formData = new FormData();
        formData.append('nome', document.getElementById('rapido_nome').value);
        formData.append('preco', document.getElementById('rapido_preco').value);
        formData.append('unidade', document.getElementById('rapido_unidade').value);
        formData.append('categoria_id', document.getElementById('rapido_categoria_id').value);
        formData.append('marca', document.getElementById('rapido_marca').value);
        formData.append('preco_custo', document.getElementById('rapido_preco_custo').value);
        formData.append('codigo_barras', document.getElementById('rapido_codigo_barras').value);
        formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

        arquivosFotosRapidas.forEach((file, i) => {
            formData.append('fotos[]', file);
        });

        fetch('<?= Url::to(['/vendas/produto/cadastro-rapido']) ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '⚡ Salvar & Adicionar ao Encarte';

            if (data.success) {
                fecharModalCadastroRapido();
                
                // Atualiza ou insere a linha na tabela index do Yii2 se existir
                if (typeof window.inserirProdutoNaTabelaIndex === 'function') {
                    window.inserirProdutoNaTabelaIndex(data.produto);
                } else {
                    location.reload();
                }
            } else {
                alert('Erro ao cadastrar produto: ' + (data.message || 'Falha ao salvar.'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '⚡ Salvar & Adicionar ao Encarte';
            alert('Erro de conexão ao salvar: ' + err.message);
        });
    }
</script>
