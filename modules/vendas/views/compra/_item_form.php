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

            <?= $form->field($item, "[$index]categoria_id", ['template' => '{input}', 'enableClientValidation' => false])->hiddenInput(['class' => 'input-categoria-id', 'required' => false]) ?>

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
    <div class="mt-2 text-right">
        <span class="text-sm text-gray-600">Subtotal: </span>
        <span class="text-base font-semibold text-gray-900 item-subtotal">R$ 0,00</span>
    </div>
</div>