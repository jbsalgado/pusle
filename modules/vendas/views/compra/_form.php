<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\vendas\models\ItemCompra;

?>

<div class="compra-form">
    <?php $form = ActiveForm::begin([
        'id' => 'compra-form',
        'action' => $model->isNewRecord ? ['create'] : ['update', 'id' => $model->id],
        'options' => [
            'class' => 'space-y-6 p-4 sm:p-6 lg:p-8',
            'novalidate' => true, // Previne que o navegador bloqueie o envio por causa de hidden fields
        ],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{hint}\n{error}",
            'labelOptions' => ['class' => 'block text-sm font-medium text-gray-700 mb-2'],
            'inputOptions' => ['class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'],
            'errorOptions' => ['class' => 'text-red-600 text-sm mt-1'],
        ],
    ]); ?>

    <!-- Dados Básicos da Compra -->
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Dados da Compra
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <div class="sm:col-span-2">
                <?= $form->field($model, 'fornecedor_id')->dropDownList(
                    $fornecedores,
                    ['prompt' => 'Selecione o fornecedor...', 'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent']
                ) ?>
            </div>

            <div>
                <?= $form->field($model, 'data_compra')->textInput(['type' => 'date']) ?>
            </div>

            <div>
                <?= $form->field($model, 'data_vencimento')->textInput(['type' => 'date']) ?>
            </div>

            <div>
                <?= $form->field($model, 'numero_nota_fiscal')->textInput(['maxlength' => true, 'placeholder' => 'Número da NF']) ?>
            </div>

            <div>
                <?= $form->field($model, 'serie_nota_fiscal')->textInput(['maxlength' => true, 'placeholder' => 'Série da NF']) ?>
            </div>

            <div>
                <?= $form->field($model, 'forma_pagamento')->dropDownList([
                    'DINHEIRO' => 'Dinheiro',
                    'CREDITO' => 'Cartão de Crédito',
                    'DEBITO' => 'Cartão de Débito',
                    'PIX' => 'PIX',
                    'BOLETO' => 'Boleto Bancário',
                ], [
                    'prompt' => 'Selecione a forma de pagamento...',
                    'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'
                ]) ?>
            </div>

            <div>
                <?= $form->field($model, 'valor_frete')->textInput(['class' => 'currency-input w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent', 'placeholder' => '0,00', 'inputmode' => 'numeric', 'id' => 'input-frete']) ?>
            </div>

            <div>
                <?= $form->field($model, 'valor_desconto')->textInput(['class' => 'currency-input w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent', 'placeholder' => '0,00', 'inputmode' => 'numeric', 'id' => 'input-desconto']) ?>
            </div>

            <div>
                <?= $form->field($model, 'status_compra')->dropDownList(
                    \app\modules\vendas\models\Compra::getStatusList(),
                    ['class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent']
                ) ?>
            </div>
        </div>

        <div class="mt-4">
            <?= $form->field($model, 'observacoes')->textarea(['rows' => 3, 'placeholder' => 'Observações sobre a compra...']) ?>
        </div>
    </div>

    <!-- Itens da Compra -->
    <div class="border-b border-gray-200 pb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg sm:text-xl font-bold text-gray-900 flex items-center">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                Itens da Compra
            </h2>
            <button type="button" id="btn-adicionar-item" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-300 text-sm">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Adicionar Item
            </button>
        </div>

        <div id="itens-container" class="space-y-4">
            <?php if (empty($itens)): ?>
                <div class="text-center py-8 text-gray-500">
                    <p>Nenhum item adicionado. Clique em "Adicionar Item" para começar.</p>
                </div>
            <?php else: ?>
                <?php foreach ($itens as $index => $item): ?>
                    <?= $this->render('_item_form', [
                        'form' => $form,
                        'item' => $item,
                        'index' => $index,
                        'produtos' => $produtos,
                        'categorias' => $categorias,
                    ]) ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="mt-4 p-4 bg-gray-50 rounded-lg">
            <div class="flex justify-between items-center">
                <span class="text-lg font-semibold text-gray-900">Total:</span>
                <span id="total-compra" class="text-2xl font-bold text-gray-900">R$ 0,00</span>
            </div>
        </div>
    </div>

    <!-- Botões de Ação -->
    <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-gray-200">
        <?= Html::submitButton(
            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' . ($model->isNewRecord ? 'Cadastrar' : 'Atualizar'),
            [
                'id' => 'btn-submit-compra',
                'class' => 'w-full sm:flex-1 inline-flex items-center justify-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300'
            ]
        ) ?>
        <?= Html::a(
            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Cancelar',
            $model->isNewRecord ? ['index'] : ['view', 'id' => $model->id],
            ['class' => 'w-full sm:flex-1 inline-flex items-center justify-center px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold rounded-lg transition duration-300']
        ) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let itemIndex = <?= count($itens) ?>;
        const categorias = <?= json_encode($categorias) ?>;
        const produtos = <?= json_encode(array_map(function ($p) {
                                return ['id' => $p->id, 'nome' => $p->nome, 'preco_custo' => $p->preco_custo, 'categoria_id' => $p->categoria_id];
                            }, $produtos)) ?>;

        // Função de Máscara de Moeda (Right-to-Left)
        function maskCurrency(event) {
            let value = event.target.value.replace(/\D/g, "");
            if (value === "") {
                event.target.value = "";
                return;
            }

            // Converte para número e divide por 100 para centavos
            let numberValue = parseInt(value) / 100;

            // Formata usando Intl nativo do navegador
            event.target.value = new Intl.NumberFormat('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(numberValue);

            // Recalcular total se for campo global
            if (event.target.id === 'input-frete' || event.target.id === 'input-desconto') {
                calcularTotal();
            }
        }

        // Função para desmascarar (1.234,56 -> 1234.56)
        function unmaskCurrency(value) {
            if (!value) return 0;
            if (typeof value === 'number') return value;
            return parseFloat(value.replace(/\./g, '').replace(',', '.')) || 0;
        }

        // Aplica máscara em inputs existentes
        document.querySelectorAll('.currency-input').forEach(input => {
            input.addEventListener('input', maskCurrency);
            // Formata valor inicial se existir (e for numérico padrão do BD)
            if (input.value && !input.value.includes(',')) {
                input.value = new Intl.NumberFormat('pt-BR', {
                    minimumFractionDigits: 2
                }).format(parseFloat(input.value));
            }
        });

        document.getElementById('btn-adicionar-item').addEventListener('click', function() {
            const container = document.getElementById('itens-container');

            const itemHtml = `
        <div class="item-compra bg-gray-50 p-4 rounded-lg border border-gray-200" data-index="${itemIndex}">
            <div class="flex justify-between items-center mb-3">
                <h4 class="font-semibold text-gray-900">Item ${itemIndex + 1}</h4>
                <button type="button" class="btn-remover-item px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded transition duration-300">
                    Remover
                </button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="sm:col-span-1 lg:col-span-2 relative autocomplete-container">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Produto *</label>
                    
                    <input type="text" class="input-search w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Digite para buscar..." autocomplete="off">
                    <input type="hidden" name="ItemCompra[${itemIndex}][produto_id]" class="input-produto-id">
                    
                    <div class="autocomplete-results hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg shadow-lg max-h-60 overflow-y-auto top-[70px]"></div>
                </div>
                <div class="relative autocomplete-container-categoria">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Categoria *</label>
                    <input type="text" class="input-search-categoria w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Buscar categoria..." autocomplete="off">
                    <input type="hidden" name="ItemCompra[${itemIndex}][categoria_id]" class="input-categoria-id">
                    
                    <div class="autocomplete-results-categoria hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg shadow-lg max-h-60 overflow-y-auto top-[70px]"></div>
                </div>
                <div class="grid grid-cols-2 gap-4 sm:col-span-1">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantidade *</label>
                        <input type="text" inputmode="decimal" name="ItemCompra[${itemIndex}][quantidade]" class="input-quantidade w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Preço Unit. *</label>
                        <input type="text" name="ItemCompra[${itemIndex}][preco_unitario]" class="input-preco currency-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required placeholder="0,00">
                    </div>
                </div>
            </div>
            <div class="mt-2 text-right">
                <span class="text-sm text-gray-600">Subtotal: </span>
                <span class="text-base font-semibold text-gray-900 item-subtotal">R$ 0,00</span>
            </div>
        </div>
    `;
            container.insertAdjacentHTML('beforeend', itemHtml);

            // Remove mensagem de "nenhum item"
            const emptyMsg = container.querySelector('.text-center');
            if (emptyMsg) emptyMsg.remove();

            // Adiciona listeners
            const newItem = container.lastElementChild;
            // Apply mask to new input
            newItem.querySelector('.currency-input').addEventListener('input', maskCurrency);

            attachItemListeners(newItem);
            itemIndex++;
        });

        // Remove item
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-remover-item')) {
                e.target.closest('.item-compra').remove();
                calcularTotal();
            }
        });

        // Fecha resultados se clicar fora
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.autocomplete-container')) {
                document.querySelectorAll('.autocomplete-results').forEach(el => el.classList.add('hidden'));
            }
            if (!e.target.closest('.autocomplete-container-categoria')) {
                document.querySelectorAll('.autocomplete-results-categoria').forEach(el => el.classList.add('hidden'));
            }
        });

        // Listeners para cálculo e autocomplete
        function attachItemListeners(itemElement) {
            const inputSearch = itemElement.querySelector('.input-search');
            const inputId = itemElement.querySelector('.input-produto-id');
            const resultsContainer = itemElement.querySelector('.autocomplete-results');

            const inputSearchCat = itemElement.querySelector('.input-search-categoria');
            const inputIdCat = itemElement.querySelector('.input-categoria-id');
            const resultsContainerCat = itemElement.querySelector('.autocomplete-results-categoria');

            const inputQuantidade = itemElement.querySelector('.input-quantidade');
            const inputPreco = itemElement.querySelector('.input-preco');
            const subtotalElement = itemElement.querySelector('.item-subtotal');

            function calcularSubtotal() {
                const quantidade = parseFloat(inputQuantidade.value) || 0;
                const preco = unmaskCurrency(inputPreco.value); // Use unmask
                const subtotal = quantidade * preco;
                subtotalElement.textContent = 'R$ ' + new Intl.NumberFormat('pt-BR', {
                    minimumFractionDigits: 2
                }).format(subtotal);
                calcularTotal();
            }

            // Product Autocomplete Logic
            inputSearch.addEventListener('input', function() {
                const term = this.value.toLowerCase();
                resultsContainer.innerHTML = '';

                if (term.length < 1) {
                    resultsContainer.classList.add('hidden');
                    return;
                }

                const filtered = produtos.filter(p => p.nome.toLowerCase().includes(term));

                if (filtered.length === 0) {
                    resultsContainer.innerHTML = '<div class="p-2 text-gray-500 text-sm">Nenhum produto encontrado</div>';
                } else {
                    filtered.forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'p-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-0 text-sm';
                        div.textContent = p.nome;
                        div.dataset.id = p.id;
                        div.dataset.preco = p.preco_custo;

                        div.addEventListener('click', function() {
                            inputSearch.value = p.nome;
                            inputId.value = p.id;

                            // Select category if product has one
                            if (inputIdCat && p.categoria_id && categorias[p.categoria_id]) {
                                inputIdCat.value = p.categoria_id;
                                inputSearchCat.value = categorias[p.categoria_id];
                            }

                            if (p.preco_custo) {
                                // Format price for display
                                const precoFormatted = new Intl.NumberFormat('pt-BR', {
                                    minimumFractionDigits: 2
                                }).format(parseFloat(p.preco_custo));
                                inputPreco.value = precoFormatted;
                                inputPreco.dispatchEvent(new Event('input')); // Trigger mask
                            }
                            resultsContainer.classList.add('hidden');
                            calcularSubtotal();
                        });

                        resultsContainer.appendChild(div);
                    });
                }

                resultsContainer.classList.remove('hidden');
            });

            // Category Autocomplete Logic
            inputSearchCat.addEventListener('input', function() {
                const term = this.value.toLowerCase();
                resultsContainerCat.innerHTML = '';

                if (term.length < 1) {
                    resultsContainerCat.classList.add('hidden');
                    return;
                }

                // Filter local categories object
                const filtered = Object.entries(categorias).filter(([id, nome]) => nome.toLowerCase().includes(term));

                if (filtered.length === 0) {
                    resultsContainerCat.innerHTML = '<div class="p-2 text-gray-500 text-sm">Nenhuma categoria encontrada</div>';
                } else {
                    filtered.forEach(([id, nome]) => {
                        const div = document.createElement('div');
                        div.className = 'p-2 hover:bg-green-50 cursor-pointer border-b border-gray-100 last:border-0 text-sm';
                        div.textContent = nome;

                        div.addEventListener('click', function() {
                            inputSearchCat.value = nome;
                            inputIdCat.value = id;
                            resultsContainerCat.classList.add('hidden');
                        });

                        resultsContainerCat.appendChild(div);
                    });
                }

                resultsContainerCat.classList.remove('hidden');
            });

            inputSearch.addEventListener('focus', function() {
                if (this.value.length >= 1) {
                    this.dispatchEvent(new Event('input'));
                }
            });

            inputSearchCat.addEventListener('focus', function() {
                if (this.value.length >= 1) {
                    this.dispatchEvent(new Event('input'));
                }
            });

            inputQuantidade.addEventListener('input', calcularSubtotal);
            inputPreco.addEventListener('input', calcularSubtotal);

            // Dispara cálculo inicial se já houver valores preenchidos (ex: XML)
            if (inputQuantidade.value || inputPreco.value) {
                calcularSubtotal();
            }
        }

        // Calcula total geral
        function calcularTotal() {
            let total = 0;
            document.querySelectorAll('.item-subtotal').forEach(function(el) {
                const text = el.textContent.replace('R$ ', '').replace('R$', '').trim();
                total += unmaskCurrency(text);
            });

            // Add Frete
            const frete = unmaskCurrency(document.getElementById('input-frete').value);
            total += frete;

            // Subtract Discount
            const desconto = unmaskCurrency(document.getElementById('input-desconto').value);
            total -= desconto;

            // Prevent negative total
            if (total < 0) total = 0;

            document.getElementById('total-compra').textContent = 'R$ ' + new Intl.NumberFormat('pt-BR', {
                minimumFractionDigits: 2
            }).format(total);
        }

        // Processamento seguro do formulário via Yii2
        $('#compra-form').on('beforeSubmit', function(e) {
            const btnSubmit = document.getElementById('btn-submit-compra');

            if (btnSubmit.disabled) return false;

            // Feedback visual
            btnSubmit.disabled = true;
            btnSubmit.classList.remove('bg-green-600', 'hover:bg-green-700');
            btnSubmit.classList.add('bg-gray-400', 'cursor-not-allowed');
            btnSubmit.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Processando...
        `;

            // Desmascara valores de moeda antes do envio final
            document.querySelectorAll('.currency-input').forEach(input => {
                input.value = unmaskCurrency(input.value);
            });

            return true; // Continua com o submit final
        });

        // Anexa listeners aos itens existentes e força cálculo inicial
        try {
            document.querySelectorAll('.item-compra').forEach(item => {
                attachItemListeners(item);
            });

            // Cálculo final do total geral
            calcularTotal();
        } catch (e) {
            console.error('Erro na inicialização dos itens:', e);
        }
    }); // Fim do DOMContentLoaded
</script>