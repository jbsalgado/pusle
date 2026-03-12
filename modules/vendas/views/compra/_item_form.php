<div class="item-compra bg-gray-50 p-4 rounded-lg border border-gray-200" data-index="<?= $index ?>">
    <div class="flex justify-between items-center mb-3">
        <h4 class="font-semibold text-gray-900">Item <?= $index + 1 ?></h4>
        <button type="button" class="btn-remover-item px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded transition duration-300">
            Remover
        </button>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="sm:col-span-1 lg:col-span-2 relative autocomplete-container">
            <label class="block text-sm font-medium text-gray-700 mb-2">Produto *</label>

            <?php
            $nomeProduto = $item->nome_produto_temp ?? '';
            if ($item->produto_id) {
                // Find product name efficiently
                foreach ($produtos as $p) {
                    if ($p->id == $item->produto_id) {
                        $nomeProduto = $p->nome;
                        break;
                    }
                }
            }
            ?>

            <input type="text"
                class="input-search w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Digite para buscar..."
                value="<?= \yii\helpers\Html::encode($nomeProduto) ?>"
                autocomplete="off">

            <?= $form->field($item, "[$index]produto_id", ['template' => '{input}', 'enableClientValidation' => false])->hiddenInput(['class' => 'input-produto-id', 'required' => false]) ?>
            <?= $form->field($item, "[$index]nome_produto_temp", ['template' => '{input}', 'enableClientValidation' => false])->hiddenInput(['class' => 'input-nome-produto-temp']) ?>
            <?= $form->field($item, "[$index]codigo_referencia_temp", ['template' => '{input}', 'enableClientValidation' => false])->hiddenInput(['class' => 'input-codigo-referencia-temp']) ?>

            <?= $form->field($item, "[$index]preco_venda_sugerido_temp", ['template' => '{input}'])->hiddenInput(['class' => 'input-preco-venda-sugerido-temp']) ?>
            <?= $form->field($item, "[$index]estoque_minimo_temp", ['template' => '{input}'])->hiddenInput(['class' => 'input-estoque-minimo-temp']) ?>
            <?= $form->field($item, "[$index]estoque_maximo_temp", ['template' => '{input}'])->hiddenInput(['class' => 'input-estoque-maximo-temp']) ?>
            <?= $form->field($item, "[$index]ponto_corte_temp", ['template' => '{input}'])->hiddenInput(['class' => 'input-ponto-corte-temp']) ?>

            <div class="autocomplete-results hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg shadow-lg max-h-60 overflow-y-auto top-[70px]"></div>
        </div>
        <div class="relative autocomplete-container-categoria">
            <label class="block text-sm font-medium text-gray-700 mb-2">Categoria *</label>

            <?php
            $nomeCategoria = '';
            if ($item->categoria_id && isset($categorias[$item->categoria_id])) {
                $nomeCategoria = $categorias[$item->categoria_id];
            }
            ?>

            <input type="text"
                class="input-search-categoria w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Buscar categoria..."
                value="<?= \yii\helpers\Html::encode($nomeCategoria) ?>"
                autocomplete="off">

            <?= $form->field($item, "[$index]categoria_id", ['template' => '{input}', 'enableClientValidation' => true])->hiddenInput(['class' => 'input-categoria-id', 'required' => true]) ?>

            <div class="autocomplete-results-categoria hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg shadow-lg max-h-60 overflow-y-auto top-[70px]"></div>
        </div>
        <div class="grid grid-cols-2 gap-4 sm:col-span-1">
            <div>
                <?= $form->field($item, "[$index]quantidade", ['enableClientValidation' => false])->textInput([
                    'type' => 'text',
                    'inputmode' => 'decimal',
                    'class' => 'input-quantidade w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'value' => $item->quantidade ?? ''
                ])->label('Qtd.') ?>
            </div>
            <div>
                <?= $form->field($item, "[$index]preco_unitario", ['enableClientValidation' => false])->textInput([
                    'class' => 'input-preco currency-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'value' => $item->preco_unitario ?? '',
                    'placeholder' => '0,00'
                ])->label('Preço') ?>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
        <div class="sm:col-span-1 lg:col-span-2">
            <?= $form->field($item, "[$index]codigo_barras", ['enableClientValidation' => false])->textInput([
                'class' => 'input-codigo-barras w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                'value' => $item->codigo_barras ?? '',
                'placeholder' => 'EAN/GTIN'
            ])->label('Cód. Barras') ?>
        </div>
        <div class="sm:col-span-1 lg:col-span-2">
            <?= $form->field($item, "[$index]marca", ['enableClientValidation' => false])->textInput([
                'class' => 'input-marca w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                'value' => $item->marca ?? '',
                'placeholder' => 'Marca do produto'
            ])->label('Marca') ?>
        </div>
    </div>
    <div class="mt-2 flex justify-between items-center">
        <div class="preco-sugerido-container <?= empty($item->preco_venda_sugerido_temp) ? 'hidden' : '' ?>">
            <span class="text-xs font-medium text-blue-600 uppercase tracking-wider">Sugestão de Venda: </span>
            <span class="text-sm font-bold text-blue-700 span-preco-sugerido">
                R$ <?= number_format($item->preco_venda_sugerido_temp ?? 0, 2, ',', '.') ?>
            </span>
        </div>
        <div class="text-right">
            <span class="text-sm text-gray-600">Subtotal: </span>
            <span class="text-base font-semibold text-gray-900 item-subtotal">R$ 0,00</span>
        </div>
    </div>
</div>