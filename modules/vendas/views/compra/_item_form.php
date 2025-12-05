<div class="item-compra bg-gray-50 p-4 rounded-lg border border-gray-200" data-index="<?= $index ?>">
    <div class="flex justify-between items-center mb-3">
        <h4 class="font-semibold text-gray-900">Item <?= $index + 1 ?></h4>
        <button type="button" class="btn-remover-item px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded transition duration-300">
            Remover
        </button>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="sm:col-span-2">
            <?= $form->field($item, "[$index]produto_id")->dropDownList(
                array_map(function($p) { return $p->nome; }, $produtos),
                ['prompt' => 'Selecione...', 'class' => 'select-produto w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent']
            )->label('Produto') ?>
        </div>
        <div>
            <?= $form->field($item, "[$index]quantidade")->textInput([
                'type' => 'number',
                'step' => '0.001',
                'min' => '0.001',
                'class' => 'input-quantidade w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                'value' => $item->quantidade ?? ''
            ])->label('Quantidade') ?>
        </div>
        <div>
            <?= $form->field($item, "[$index]preco_unitario")->textInput([
                'type' => 'number',
                'step' => '0.01',
                'min' => '0',
                'class' => 'input-preco w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                'value' => $item->preco_unitario ?? ''
            ])->label('Preço Unit.') ?>
        </div>
    </div>
    <div class="mt-2 text-right">
        <span class="text-sm text-gray-600">Subtotal: </span>
        <span class="text-base font-semibold text-gray-900 item-subtotal">R$ 0,00</span>
    </div>
</div>

