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
                <?= $form->field($model, 'status_compra')->dropDownList(
                    \app\modules\vendas\models\Compra::getStatusList(),
                    ['class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent']
                ) ?>
            </div>

            <div class="flex items-center h-full pt-6">
                <?= $form->field($model, 'com_nota')->checkbox([
                    'class' => 'h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mr-2',
                    'labelOptions' => ['class' => 'text-sm font-medium text-gray-700 flex items-center cursor-pointer']
                ]) ?>
            </div>
        </div>

        <div class="mt-4">
            <?= $form->field($model, 'observacoes')->textarea(['rows' => 3, 'placeholder' => 'Observações sobre a compra...']) ?>
        </div>
    </div>

    <!-- Valores da Nota Fiscal, Impostos e Despesas -->
    <div class="border-b border-gray-200 pb-6 mt-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            Valores, Impostos e Despesas da Nota Fiscal
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <div>
                <?= $form->field($model, 'valor_frete')->textInput([
                    'class' => 'currency-input w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => '0,00',
                    'inputmode' => 'numeric',
                    'id' => 'input-frete'
                ]) ?>
            </div>

            <div>
                <?= $form->field($model, 'valor_seguro')->textInput([
                    'class' => 'currency-input w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => '0,00',
                    'inputmode' => 'numeric',
                    'id' => 'input-seguro'
                ]) ?>
            </div>

            <div>
                <?= $form->field($model, 'valor_outras_despesas')->textInput([
                    'class' => 'currency-input w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => '0,00',
                    'inputmode' => 'numeric',
                    'id' => 'input-outras-despesas'
                ]) ?>
            </div>

            <div>
                <?= $form->field($model, 'valor_desconto')->textInput([
                    'class' => 'currency-input w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => '0,00',
                    'inputmode' => 'numeric',
                    'id' => 'input-desconto'
                ]) ?>
            </div>

            <div>
                <?= $form->field($model, 'valor_ipi')->textInput([
                    'class' => 'currency-input w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => '0,00',
                    'inputmode' => 'numeric',
                    'id' => 'input-ipi'
                ]) ?>
            </div>

            <div>
                <?= $form->field($model, 'valor_icms_st')->textInput([
                    'class' => 'currency-input w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => '0,00',
                    'inputmode' => 'numeric',
                    'id' => 'input-icms-st'
                ]) ?>
            </div>

            <div>
                <?= $form->field($model, 'valor_fcp_st')->textInput([
                    'class' => 'currency-input w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => '0,00',
                    'inputmode' => 'numeric',
                    'id' => 'input-fcp-st'
                ]) ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Chave de Acesso (44 dígitos)</label>
                <?= $form->field($model, 'chave_acesso', ['template' => '{input}{error}'])->textInput([
                    'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-xs',
                    'placeholder' => 'Chave de 44 dígitos da NFe',
                    'maxlength' => 44,
                    'id' => 'input-chave-acesso'
                ]) ?>
            </div>
        </div>

        <!-- Tributos Informativos Adicionais (Colapsável) -->
        <details class="mt-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
            <summary class="cursor-pointer text-sm font-semibold text-gray-700 hover:text-blue-600 flex items-center">
                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Tributos Complementares e Informativos (ICMS Próprio, Base de Cálculo, PIS, COFINS)
            </summary>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                <div>
                    <?= $form->field($model, 'valor_base_icms')->textInput([
                        'class' => 'currency-input w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm',
                        'placeholder' => '0,00',
                        'inputmode' => 'numeric',
                        'id' => 'input-base-icms'
                    ]) ?>
                </div>
                <div>
                    <?= $form->field($model, 'valor_icms')->textInput([
                        'class' => 'currency-input w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm',
                        'placeholder' => '0,00',
                        'inputmode' => 'numeric',
                        'id' => 'input-icms'
                    ]) ?>
                </div>
                <div>
                    <?= $form->field($model, 'valor_pis')->textInput([
                        'class' => 'currency-input w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm',
                        'placeholder' => '0,00',
                        'inputmode' => 'numeric',
                        'id' => 'input-pis'
                    ]) ?>
                </div>
                <div>
                    <?= $form->field($model, 'valor_cofins')->textInput([
                        'class' => 'currency-input w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm',
                        'placeholder' => '0,00',
                        'inputmode' => 'numeric',
                        'id' => 'input-cofins'
                    ]) ?>
                </div>
            </div>
        </details>
    </div>

    <!-- Financeiro / Parcelamento -->
    <div class="border-b border-gray-200 pb-6 mt-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Financeiro / Parcelamento
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Parcelamento</label>
                <select id="tipo-parcelamento" class="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="vista">À Vista (1 Parcela)</option>
                    <option value="auto">Parcelamento Automático (Intervalo Fixo)</option>
                    <option value="manual">Parcelamento Manual (Informar Vencimentos e Valores)</option>
                </select>
            </div>

            <div id="col-num-parcelas" class="hidden">
                <?= $form->field($model, 'num_parcelas')->textInput([
                    'type' => 'number',
                    'min' => 1,
                    'max' => 120,
                    'id' => 'input-num-parcelas',
                    'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500'
                ]) ?>
            </div>

            <div id="col-intervalo-parcelas" class="hidden">
                <?= $form->field($model, 'intervalo_parcelas')->textInput([
                    'type' => 'number',
                    'min' => 1,
                    'max' => 365,
                    'id' => 'input-intervalo-parcelas',
                    'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500'
                ]) ?>
            </div>
        </div>

        <!-- Bloco de Parcelas Manuais -->
        <div id="container-parcelas-manuais" class="hidden mt-6 space-y-4">
            <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg border">
                <span class="text-sm font-semibold text-gray-800">Datas de Vencimento e Valores das Parcelas</span>
                <button type="button" id="btn-adicionar-parcela-manual" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-200 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Adicionar Parcela
                </button>
            </div>

            <div id="lista-parcelas-manuais" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Parcelas injetadas dinamicamente -->
            </div>

            <div class="flex justify-end pt-4">
                <div class="p-4 bg-gray-50 border rounded-lg text-sm flex flex-col gap-2 w-full sm:w-80 shadow-inner">
                    <div class="flex justify-between text-gray-600">
                        <span>Total Líquido da Compra:</span>
                        <strong id="manual-total-liquido">R$ 0,00</strong>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Total das Parcelas:</span>
                        <strong id="manual-total-parcelas" class="text-blue-600">R$ 0,00</strong>
                    </div>
                    <div class="flex justify-between border-t pt-2 font-bold text-gray-900" id="box-manual-restante">
                        <span id="label-manual-restante">Diferença a Parcelar:</span>
                        <span id="valor-manual-restante" class="text-red-600">R$ 0,00</span>
                    </div>
                </div>
            </div>
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
            <button type="button" id="btn-adicionar-item" class="btn-adicionar-item-trigger px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-300 text-sm">
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

        <!-- Botão Adicionar Item no final da lista de itens -->
        <div class="mt-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 pt-2">
            <button type="button" class="btn-adicionar-item-trigger inline-flex items-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-sm hover:shadow transition duration-200 text-sm">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                + Adicionar Mais um Item
            </button>
            <span class="text-xs text-gray-500">Adicione novos itens conforme os produtos da nota fiscal.</span>
        </div>

        <div class="mt-4 p-5 bg-gradient-to-r from-gray-50 to-slate-100 rounded-xl border border-gray-200 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1.5 text-sm text-gray-600">
                    <div class="flex justify-between items-center py-1 border-b border-gray-200">
                        <span class="font-medium text-gray-700">Subtotal dos Produtos:</span>
                        <strong id="resumo-subtotal-produtos" class="text-gray-900 font-semibold">R$ 0,00</strong>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span>(+) Frete:</span>
                        <span id="resumo-frete" class="font-medium text-gray-700">R$ 0,00</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span>(+) Seguro:</span>
                        <span id="resumo-seguro" class="font-medium text-gray-700">R$ 0,00</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span>(+) Outras Despesas:</span>
                        <span id="resumo-outras" class="font-medium text-gray-700">R$ 0,00</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span>(+) IPI:</span>
                        <span id="resumo-ipi" class="font-medium text-gray-700">R$ 0,00</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span>(+) ICMS ST / FCP ST:</span>
                        <span id="resumo-st" class="font-medium text-gray-700">R$ 0,00</span>
                    </div>
                    <div class="flex justify-between text-xs text-red-600">
                        <span>(-) Desconto:</span>
                        <span id="resumo-desconto" class="font-medium text-red-600">R$ 0,00</span>
                    </div>
                </div>

                <div class="flex flex-col justify-center items-end border-t md:border-t-0 md:border-l md:pl-6 border-gray-200 pt-3 md:pt-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Total da Nota Fiscal (vNF)</span>
                    <span id="total-compra" class="text-3xl font-extrabold text-blue-700 my-1">R$ 0,00</span>
                    <span class="text-[11px] text-gray-500 text-right">
                        Calculado automaticamente: Itens + Frete + Seguro + Outras + IPI + ST - Desconto
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Botões de Ação -->
    <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-gray-200">
        <button type="button" id="btn-adicionar-item-rodape" class="btn-adicionar-item-trigger w-full sm:w-auto inline-flex items-center justify-center px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300">
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Novo Item
        </button>
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
            ['class' => 'w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold rounded-lg transition duration-300']
        ) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<!-- Modal Cadastro Rápido de Produto -->
<div id="modal-cadastro-rapido-produto" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop com blur suave -->
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" id="backdrop-cadastro-rapido"></div>

    <div class="flex min-h-screen items-center justify-center p-3 sm:p-4 text-center">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all w-full max-w-2xl border border-gray-100 animate-in fade-in zoom-in-95 duration-200">
            <!-- Header do Modal -->
            <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 px-5 py-4 text-white flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/15 backdrop-blur flex items-center justify-center text-white border border-white/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold leading-tight" id="modal-title">Cadastro Rápido de Produto</h3>
                        <p class="text-xs text-blue-100">Inclua dados essenciais de estoque e venda sem sair da tela de compras</p>
                    </div>
                </div>
                <button type="button" class="btn-fechar-modal-rapido text-white/80 hover:text-white hover:bg-white/10 p-1.5 rounded-lg transition" title="Fechar">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Formulário do Modal -->
            <form id="form-cadastro-rapido-produto" class="p-5 sm:p-6 space-y-4">
                <!-- Alerta de Erro -->
                <div id="alerta-erro-cadastro-rapido" class="hidden p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs sm:text-sm flex items-start gap-2">
                    <svg class="w-5 h-5 flex-shrink-0 text-red-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span id="texto-erro-cadastro-rapido" class="flex-1"></span>
                </div>

                <!-- Linha 1: Nome e Categoria -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                            Nome do Produto <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="rapido-nome" name="nome" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium"
                            placeholder="Ex: Cimento CP II 50kg Votoran">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                            Categoria <span class="text-red-500">*</span>
                        </label>
                        <select id="rapido-categoria-id" name="categoria_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">Selecione...</option>
                            <?php foreach ($categorias as $catId => $catNome): ?>
                                <option value="<?= Html::encode($catId) ?>"><?= Html::encode($catNome) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Linha 2: Preço de Custo e Preço de Venda Sugerido -->
                <div class="bg-blue-50/50 p-3.5 rounded-xl border border-blue-100 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                                Preço de Custo (R$) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm font-semibold">R$</span>
                                <input type="text" id="rapido-preco-custo" name="preco_custo" required
                                    class="currency-input w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-bold text-gray-900"
                                    placeholder="0,00" value="0,00" inputmode="numeric">
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    Preço de Venda (R$) <span class="text-red-500">*</span>
                                </label>
                                <span id="rapido-indicador-margem" class="text-[11px] font-semibold text-blue-700">Margem: 50%</span>
                            </div>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm font-semibold">R$</span>
                                <input type="text" id="rapido-preco-venda" name="preco_venda_sugerido" required
                                    class="currency-input w-full pl-9 pr-3 py-2 border border-blue-400 bg-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-bold text-blue-900"
                                    placeholder="0,00" value="0,00" inputmode="numeric">
                            </div>
                        </div>
                    </div>

                    <!-- Botões de Atalho de Margem -->
                    <div class="flex items-center gap-1.5 pt-1 border-t border-blue-100 flex-wrap">
                        <span class="text-[11px] font-medium text-gray-500">Atalhos de margem rápida:</span>
                        <button type="button" class="btn-margem-rapida px-2 py-0.5 bg-white hover:bg-blue-100 border border-blue-200 text-blue-700 rounded text-xs font-semibold transition" data-percent="30">+30%</button>
                        <button type="button" class="btn-margem-rapida px-2 py-0.5 bg-white hover:bg-blue-100 border border-blue-200 text-blue-700 rounded text-xs font-semibold transition" data-percent="40">+40%</button>
                        <button type="button" class="btn-margem-rapida px-2 py-0.5 bg-white hover:bg-blue-100 border border-blue-200 text-blue-700 rounded text-xs font-semibold transition" data-percent="50">+50%</button>
                        <button type="button" class="btn-margem-rapida px-2 py-0.5 bg-white hover:bg-blue-100 border border-blue-200 text-blue-700 rounded text-xs font-semibold transition" data-percent="70">+70%</button>
                        <button type="button" class="btn-margem-rapida px-2 py-0.5 bg-white hover:bg-blue-100 border border-blue-200 text-blue-700 rounded text-xs font-semibold transition" data-percent="100">+100%</button>
                    </div>
                </div>

                <!-- Linha 3: Estoques (Atual, Mínimo, Ponto de Corte) -->
                <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200 space-y-2">
                    <div class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        Parâmetros de Estoque
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1" title="Saldo físico existente na loja antes de dar entrada nesta nota">
                                Estoque Atual (Inicial)
                            </label>
                            <input type="number" step="0.01" min="0" id="rapido-estoque-atual" name="estoque_atual" value="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm font-semibold text-gray-900"
                                placeholder="0">
                            <span class="text-[10px] text-gray-500">Saldo já existente na loja</span>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1" title="Gera alerta no dashboard quando estoque atingir este valor">
                                Estoque Mínimo
                            </label>
                            <input type="number" step="0.01" min="0" id="rapido-estoque-minimo" name="estoque_minimo" value="1"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm font-semibold text-gray-900"
                                placeholder="1">
                            <span class="text-[10px] text-gray-500">Alerta de ressuprimento</span>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1" title="Limite de segurança crítico (deve ser >= Estoque Mínimo)">
                                Ponto de Corte
                            </label>
                            <input type="number" step="0.01" min="0" id="rapido-ponto-corte" name="ponto_corte" value="1"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm font-semibold text-gray-900"
                                placeholder="1">
                            <span class="text-[10px] text-gray-500">Segurança (&ge; Est. Mínimo)</span>
                        </div>
                    </div>
                </div>

                <!-- Detalhes Fiscais / Opcionais -->
                <details class="group border border-gray-200 rounded-xl overflow-hidden bg-white text-xs">
                    <summary class="cursor-pointer px-4 py-2.5 font-semibold text-gray-700 bg-gray-50 hover:bg-gray-100 flex items-center justify-between transition select-none">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            Dados Complementares (Código de Barras, Unidade, Marca, NCM)
                        </span>
                        <svg class="w-4 h-4 text-gray-500 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="p-4 grid grid-cols-2 sm:grid-cols-4 gap-3 bg-white">
                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Unidade</label>
                            <select id="rapido-unidade-medida" name="unidade_medida" class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs">
                                <option value="UN" selected>UN (Unidade)</option>
                                <option value="KG">KG (Quilo)</option>
                                <option value="MT">MT (Metro)</option>
                                <option value="M2">M² (Metro Quadrado)</option>
                                <option value="CX">CX (Caixa)</option>
                                <option value="LT">LT (Litro)</option>
                                <option value="PCT">PCT (Pacote)</option>
                                <option value="PAR">PAR (Par)</option>
                                <option value="SC">SC (Saco)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Cód. Barras (EAN)</label>
                            <input type="text" id="rapido-codigo-barras" name="codigo_barras" class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs" placeholder="789...">
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Marca</label>
                            <input type="text" id="rapido-marca" name="marca" class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs" placeholder="Marca...">
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 mb-1">NCM</label>
                            <input type="text" id="rapido-ncm" name="ncm" class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs" placeholder="Ex: 8481.80.19">
                        </div>
                    </div>
                </details>

                <!-- Ações do Modal -->
                <div class="flex flex-col-reverse sm:flex-row justify-end items-center gap-2 pt-3 border-t border-gray-200">
                    <button type="button" class="btn-fechar-modal-rapido w-full sm:w-auto px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg text-sm transition">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-salvar-cadastro-rapido" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-sm shadow-md hover:shadow-lg transition gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Salvar e Vincular à Compra</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let itemIndex = <?= count($itens) ?>;
        const isNewRecord = <?= $model->isNewRecord ? 'true' : 'false' ?>;
        const contasPagarExistentes = <?= json_encode(array_map(function ($c) {
            return [
                'valor' => $c->valor,
                'data_vencimento' => $c->data_vencimento
            ];
        }, $contasPagar)) ?>;
        let parcelaIndex = 0;

        const categorias = <?= json_encode($categorias) ?>;
        let produtos = <?= json_encode(array_map(function ($p) {
                            return [
                                'id' => $p->id,
                                'nome' => $p->nome,
                                'preco_custo' => (float)$p->preco_custo,
                                'preco_venda_sugerido' => (float)$p->preco_venda_sugerido,
                                'categoria_id' => $p->categoria_id,
                                'codigo_barras' => $p->codigo_barras ?: '',
                                'marca' => $p->marca ?: '',
                                'unidade_medida' => $p->unidade_medida ?: 'UN',
                                'estoque_atual' => (float)$p->estoque_atual,
                                'estoque_minimo' => (float)$p->estoque_minimo,
                                'ponto_corte' => (float)$p->ponto_corte,
                            ];
                        }, $produtos)) ?>;

        // Funções para Parcelas Manuais
        function obterTotalLiquido() {
            let total = 0;
            document.querySelectorAll('.item-subtotal').forEach(function(el) {
                const text = el.textContent.replace('R$ ', '').replace('R$', '').trim();
                total += unmaskCurrency(text);
            });
            const frete = unmaskCurrency(document.getElementById('input-frete')?.value || 0);
            const seguro = unmaskCurrency(document.getElementById('input-seguro')?.value || 0);
            const outras = unmaskCurrency(document.getElementById('input-outras-despesas')?.value || 0);
            const ipi = unmaskCurrency(document.getElementById('input-ipi')?.value || 0);
            const icmsSt = unmaskCurrency(document.getElementById('input-icms-st')?.value || 0);
            const fcpSt = unmaskCurrency(document.getElementById('input-fcp-st')?.value || 0);
            const desconto = unmaskCurrency(document.getElementById('input-desconto')?.value || 0);

            total = total + frete + seguro + outras + ipi + icmsSt + fcpSt - desconto;
            return total < 0 ? 0 : total;
        }

        function recalcularResumoManual() {
            const totalLiquido = obterTotalLiquido();
            let totalParcelas = 0;
            document.querySelectorAll('.parcela-valor').forEach(function(input) {
                totalParcelas += unmaskCurrency(input.value);
            });

            document.getElementById('manual-total-liquido').textContent = 'R$ ' + new Intl.NumberFormat('pt-BR', {
                minimumFractionDigits: 2
            }).format(totalLiquido);

            document.getElementById('manual-total-parcelas').textContent = 'R$ ' + new Intl.NumberFormat('pt-BR', {
                minimumFractionDigits: 2
            }).format(totalParcelas);

            const diferenca = totalLiquido - totalParcelas;
            const valorRestanteEl = document.getElementById('valor-manual-restante');
            const labelRestanteEl = document.getElementById('label-manual-restante');

            if (valorRestanteEl) {
                valorRestanteEl.classList.remove('text-red-600', 'text-green-600');

                if (Math.abs(diferenca) < 0.01) {
                    labelRestanteEl.textContent = 'Diferença:';
                    valorRestanteEl.textContent = 'R$ 0,00 (Valores Batem!)';
                    valorRestanteEl.classList.add('text-green-600');
                } else if (diferenca < 0) {
                    labelRestanteEl.textContent = 'Excedente:';
                    valorRestanteEl.textContent = 'R$ ' + new Intl.NumberFormat('pt-BR', {
                        minimumFractionDigits: 2
                    }).format(Math.abs(diferenca));
                    valorRestanteEl.classList.add('text-red-600');
                } else {
                    labelRestanteEl.textContent = 'Restante a Parcelar:';
                    valorRestanteEl.textContent = 'R$ ' + new Intl.NumberFormat('pt-BR', {
                        minimumFractionDigits: 2
                    }).format(diferenca);
                    valorRestanteEl.classList.add('text-red-600');
                }
            }
        }

        function adicionarParcelaManual(vencimento = '', valor = '') {
            const container = document.getElementById('lista-parcelas-manuais');
            if (!container) return;
            
            // Se vencimento não for informado, sugere data baseado na parcela anterior
            if (!vencimento) {
                const datasExistentes = Array.from(document.querySelectorAll('.parcela-data')).map(el => el.value);
                if (datasExistentes.length > 0) {
                    const ultimaData = new Date(datasExistentes[datasExistentes.length - 1] + 'T12:00:00');
                    ultimaData.setDate(ultimaData.getDate() + 30);
                    vencimento = ultimaData.toISOString().split('T')[0];
                } else {
                    // Primeira parcela
                    const dataCompraInput = document.getElementById('compra-data_compra') || document.querySelector('[name="Compra[data_compra]"]');
                    const dataCompraStr = (dataCompraInput && dataCompraInput.value) ? dataCompraInput.value : new Date().toISOString().split('T')[0];
                    const dataCompra = new Date(dataCompraStr + 'T12:00:00');
                    dataCompra.setDate(dataCompra.getDate() + 30);
                    vencimento = dataCompra.toISOString().split('T')[0];
                }
            }

            // Se valor não for informado, sugere a diferença restante
            if (!valor) {
                const totalLiquido = obterTotalLiquido();
                let totalParcelas = 0;
                document.querySelectorAll('.parcela-valor').forEach(function(input) {
                    totalParcelas += unmaskCurrency(input.value);
                });
                const restante = totalLiquido - totalParcelas;
                if (restante > 0) {
                    valor = new Intl.NumberFormat('pt-BR', {
                        minimumFractionDigits: 2
                    }).format(restante);
                }
            }

            const html = `
                <div class="parcela-manual-row border p-4 rounded-lg bg-gray-50 relative flex gap-3 items-end" data-index="${parcelaIndex}">
                    <div class="flex-1">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Vencimento da Parcela *</label>
                        <input type="date" name="ParcelasManuais[${parcelaIndex}][data_vencimento]" class="parcela-data w-full px-3 py-2 border rounded-lg focus:ring-1 focus:ring-blue-500 text-sm" value="${vencimento}" required>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Valor da Parcela (R$) *</label>
                        <input type="text" name="ParcelasManuais[${parcelaIndex}][valor]" class="parcela-valor currency-input w-full px-3 py-2 border rounded-lg focus:ring-1 focus:ring-blue-500 text-sm" value="${valor}" placeholder="0,00" required>
                    </div>
                    <button type="button" class="btn-remover-parcela-manual p-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg transition" title="Remover Parcela">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', html);
            const newRow = container.lastElementChild;
            newRow.querySelector('.parcela-valor').addEventListener('input', maskCurrency);
            newRow.querySelector('.parcela-valor').addEventListener('input', recalcularResumoManual);
            newRow.querySelector('.parcela-data').addEventListener('change', recalcularResumoManual);
            
            newRow.querySelector('.btn-remover-parcela-manual').addEventListener('click', function() {
                newRow.remove();
                recalcularResumoManual();
            });

            parcelaIndex++;
            recalcularResumoManual();
        }

        // Evento de alteração de Tipo de Parcelamento
        const selectTipoParcelamento = document.getElementById('tipo-parcelamento');
        const colNumParcelas = document.getElementById('col-num-parcelas');
        const colIntervaloParcelas = document.getElementById('col-intervalo-parcelas');
        const containerParcelasManuais = document.getElementById('container-parcelas-manuais');

        if (selectTipoParcelamento) {
            selectTipoParcelamento.addEventListener('change', function() {
                const val = this.value;
                if (val === 'vista') {
                    colNumParcelas.classList.add('hidden');
                    colIntervaloParcelas.classList.add('hidden');
                    containerParcelasManuais.classList.add('hidden');
                    document.getElementById('input-num-parcelas').value = 1;
                } else if (val === 'auto') {
                    colNumParcelas.classList.remove('hidden');
                    colIntervaloParcelas.classList.remove('hidden');
                    containerParcelasManuais.classList.add('hidden');
                } else if (val === 'manual') {
                    colNumParcelas.classList.add('hidden');
                    colIntervaloParcelas.classList.add('hidden');
                    containerParcelasManuais.classList.remove('hidden');
                    
                    // Se a lista de parcelas estiver vazia, adiciona a primeira automaticamente
                    if (document.querySelectorAll('.parcela-manual-row').length === 0) {
                        adicionarParcelaManual();
                    }
                }
            });
        }

        const btnAddParcelaManual = document.getElementById('btn-adicionar-parcela-manual');
        if (btnAddParcelaManual) {
            btnAddParcelaManual.addEventListener('click', function() {
                adicionarParcelaManual();
            });
        }

        // Função de Máscara de Moeda Global
        function maskCurrency(event) {
            let value = event.target.value.replace(/\D/g, "");
            if (value === "") {
                event.target.value = "";
                calcularTotal();
                return;
            }

            let numberValue = parseInt(value) / 100;

            event.target.value = new Intl.NumberFormat('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(numberValue);

            const taxInputIds = [
                'input-frete', 'input-seguro', 'input-outras-despesas',
                'input-ipi', 'input-icms-st', 'input-fcp-st', 'input-desconto'
            ];
            if (taxInputIds.includes(event.target.id)) {
                calcularTotal();
            }
        }

        // Função para desmascarar (1.234,56789 -> 1234.56789 ou 0,04561 -> 0.04561)
        function unmaskCurrency(value) {
            if (!value) return 0;
            if (typeof value === 'number') return value;
            let str = value.toString().trim();
            if (str.indexOf(',') !== -1) {
                str = str.replace(/\./g, '').replace(',', '.');
            }
            return parseFloat(str) || 0;
        }

        // Formata preço unitário (sempre 5 casas decimais estilo calculadora)
        function formatUnitPrice(value) {
            if (value === null || value === undefined || value === '') return '0,00000';
            const num = typeof value === 'number' ? value : unmaskCurrency(value);
            if (isNaN(num)) return '0,00000';
            
            return new Intl.NumberFormat('pt-BR', {
                minimumFractionDigits: 5,
                maximumFractionDigits: 5
            }).format(num);
        }

        function handleUnitPriceInput(event) {
            let input = event.target;
            let digits = input.value.replace(/\D/g, '');
            
            if (digits === '') {
                input.value = '0,00000';
            } else {
                let numberVal = parseInt(digits, 10) / 100000;
                input.value = new Intl.NumberFormat('pt-BR', {
                    minimumFractionDigits: 5,
                    maximumFractionDigits: 5
                }).format(numberVal);
            }

            atualizarSubtotalDoItem(input);
        }

        function atualizarSubtotalDoItem(input) {
            const itemElement = input.closest('.item-compra');
            if (itemElement) {
                const inputQtd = itemElement.querySelector('.input-quantidade');
                const subtotalEl = itemElement.querySelector('.item-subtotal');
                if (inputQtd && subtotalEl) {
                    const quantidade = parseFloat(inputQtd.value) || 0;
                    const preco = unmaskCurrency(input.value);
                    const subtotal = Math.round((quantidade * preco) * 100) / 100;
                    subtotalEl.textContent = 'R$ ' + new Intl.NumberFormat('pt-BR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(subtotal);
                    calcularTotal();
                }
            }
        }

        function handleUnitPriceBlur(event) {
            if (event.target.value) {
                event.target.value = formatUnitPrice(event.target.value);
                atualizarSubtotalDoItem(event.target);
            }
        }

        function handleUnitPriceFocus(event) {
            event.target.select();
        }

        // Aplica máscara de moeda global
        document.querySelectorAll('.currency-input').forEach(input => {
            input.addEventListener('input', maskCurrency);
            if (input.value && !input.value.includes(',')) {
                input.value = new Intl.NumberFormat('pt-BR', {
                    minimumFractionDigits: 2
                }).format(parseFloat(input.value));
            }
        });

        // Aplica listeners nos inputs de preço unitário existentes
        document.querySelectorAll('.input-preco-unitario').forEach(input => {
            input.addEventListener('input', handleUnitPriceInput);
            input.addEventListener('blur', handleUnitPriceBlur);
            input.addEventListener('focus', handleUnitPriceFocus);
            if (input.value) {
                input.value = formatUnitPrice(input.value);
            }
        });

        function adicionarNovoItemCompra(scrollIntoView = false) {
            const container = document.getElementById('itens-container');
            if (!container) return;

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
                    
                    <div class="flex gap-1.5">
                        <input type="text" class="input-search w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" placeholder="Digite para buscar..." autocomplete="off">
                        <button type="button" class="btn-abrir-cadastro-rapido px-2.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg text-xs font-bold flex items-center gap-1 transition flex-shrink-0" title="Cadastrar Novo Produto">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span class="hidden sm:inline">Novo</span>
                        </button>
                    </div>
                    <input type="hidden" name="ItemCompra[${itemIndex}][produto_id]" class="input-produto-id">
                    <input type="hidden" name="ItemCompra[${itemIndex}][nome_produto_temp]" class="input-nome-produto-temp">
                    <input type="hidden" name="ItemCompra[${itemIndex}][codigo_referencia_temp]" class="input-codigo-referencia-temp">
                    <input type="hidden" name="ItemCompra[${itemIndex}][preco_venda_sugerido_temp]" class="input-preco-venda-sugerido-temp">
                    <input type="hidden" name="ItemCompra[${itemIndex}][estoque_minimo_temp]" class="input-estoque-minimo-temp">
                    <input type="hidden" name="ItemCompra[${itemIndex}][estoque_maximo_temp]" class="input-estoque-maximo-temp">
                    <input type="hidden" name="ItemCompra[${itemIndex}][ponto_corte_temp]" class="input-ponto-corte-temp">
                    <input type="hidden" name="ItemCompra[${itemIndex}][venda_fracionada_temp]" class="input-venda-fracionada-temp">
                    <input type="hidden" name="ItemCompra[${itemIndex}][unidade_medida_temp]" class="input-unidade-medida-temp">

                    <input type="hidden" name="ItemCompra[${itemIndex}][ncm]" class="input-ncm">
                    <input type="hidden" name="ItemCompra[${itemIndex}][cfop]" class="input-cfop">
                    <input type="hidden" name="ItemCompra[${itemIndex}][valor_desconto]" class="input-item-desconto">
                    <input type="hidden" name="ItemCompra[${itemIndex}][valor_frete]" class="input-item-frete">
                    <input type="hidden" name="ItemCompra[${itemIndex}][valor_seguro]" class="input-item-seguro">
                    <input type="hidden" name="ItemCompra[${itemIndex}][valor_outras_despesas]" class="input-item-outras-despesas">
                    <input type="hidden" name="ItemCompra[${itemIndex}][valor_ipi]" class="input-item-ipi">
                    <input type="hidden" name="ItemCompra[${itemIndex}][valor_icms_st]" class="input-item-icms-st">
                    <input type="hidden" name="ItemCompra[${itemIndex}][custo_unitario_real]" class="input-custo-unitario-real">
                    
                    <div class="autocomplete-results hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg shadow-lg max-h-60 overflow-y-auto top-[70px]"></div>
                </div>
                <div class="relative autocomplete-container-categoria">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Categoria *</label>
                    <div class="relative">
                        <input type="text" class="input-search-categoria w-full pl-3 pr-8 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Selecione ou busque a categoria..." autocomplete="off">
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                            <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                            </svg>
                        </div>
                    </div>
                    <input type="hidden" name="ItemCompra[${itemIndex}][categoria_id]" class="input-categoria-id">
                    
                    <div class="autocomplete-results-categoria hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg shadow-lg max-h-60 overflow-y-auto top-[70px]"></div>
                </div>
                <div class="grid grid-cols-2 gap-4 sm:col-span-1">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantidade *</label>
                        <input type="number" step="0.001" min="0.001" name="ItemCompra[${itemIndex}][quantidade]" class="input-quantidade w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Preço Unit. *</label>
                        <input type="text" name="ItemCompra[${itemIndex}][preco_unitario]" class="input-preco input-preco-unitario w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required value="0,00000" placeholder="0,00000" inputmode="numeric">
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cód. Barras</label>
                    <input type="text" name="ItemCompra[${itemIndex}][codigo_barras]" class="input-codigo-barras w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="EAN/GTIN">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Marca</label>
                    <input type="text" name="ItemCompra[${itemIndex}][marca]" class="input-marca w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Marca do produto">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">NCM</label>
                    <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-700 text-sm" placeholder="Ex: 8481.80.19" onchange="this.closest('.item-compra').querySelector('.input-ncm').value = this.value;">
                </div>
            </div>
            <div class="mt-2 flex justify-between items-center pt-2 border-t border-gray-100">
                <div class="preco-sugerido-container hidden">
                    <span class="text-xs font-medium text-blue-600 uppercase tracking-wider">Sugestão de Venda: </span>
                    <span class="text-sm font-bold text-blue-700 span-preco-sugerido">R$ 0,00</span>
                </div>
                <div class="text-right">
                    <span class="text-sm text-gray-600">Subtotal: </span>
                    <span class="text-base font-semibold text-gray-900 item-subtotal">R$ 0,00</span>
                </div>
            </div>
        </div>
    `;
            container.insertAdjacentHTML('beforeend', itemHtml);

            // Remove mensagem de "nenhum item"
            const emptyMsg = container.querySelector('.text-center');
            if (emptyMsg) emptyMsg.remove();

            // Adiciona listeners
            const newItem = container.lastElementChild;
            const inputPrecoNew = newItem.querySelector('.input-preco-unitario');
            if (inputPrecoNew) {
                inputPrecoNew.addEventListener('input', handleUnitPriceInput);
                inputPrecoNew.addEventListener('blur', handleUnitPriceBlur);
                inputPrecoNew.addEventListener('focus', handleUnitPriceFocus);
            }

            attachItemListeners(newItem);
            itemIndex++;

            if (scrollIntoView) {
                newItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                const inputSearch = newItem.querySelector('.input-search');
                if (inputSearch) {
                    setTimeout(() => inputSearch.focus(), 250);
                }
            }
        }

        // Vincula evento de clique a todos os botões de adicionar item (topo, após a lista e rodapé)
        document.querySelectorAll('.btn-adicionar-item-trigger, #btn-adicionar-item, #btn-adicionar-item-rodape').forEach(btn => {
            btn.addEventListener('click', function() {
                adicionarNovoItemCompra(true);
            });
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

        function escapeHtml(text) {
            if (!text) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatarBrlMoeda(valor) {
            return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(valor || 0);
        }

        // Listeners para cálculo e autocomplete
        function attachItemListeners(itemElement) {
            const inputSearch = itemElement.querySelector('.input-search');
            const inputId = itemElement.querySelector('.input-produto-id');
            const inputNomeTemp = itemElement.querySelector('.input-nome-produto-temp');
            const inputRefTemp = itemElement.querySelector('.input-codigo-referencia-temp');
            const inputPrecoSugeridoTemp = itemElement.querySelector('.input-preco-venda-sugerido-temp');
            const inputEstoqueMinTemp = itemElement.querySelector('.input-estoque-minimo-temp');
            const inputEstoqueMaxTemp = itemElement.querySelector('.input-estoque-maximo-temp');
            const inputPontoCorteTemp = itemElement.querySelector('.input-ponto-corte-temp');
            const inputVendaFracionadaTemp = itemElement.querySelector('.input-venda-fracionada-temp');
            const inputUnidadeMedidaTemp = itemElement.querySelector('.input-unidade-medida-temp');

            const spanPrecoSugerido = itemElement.querySelector('.span-preco-sugerido');
            const containerPrecoSugerido = itemElement.querySelector('.preco-sugerido-container');
            const inputMarca = itemElement.querySelector('.input-marca');
            const inputCodigoBarras = itemElement.querySelector('.input-codigo-barras');
            const resultsContainer = itemElement.querySelector('.autocomplete-results');

            const inputSearchCat = itemElement.querySelector('.input-search-categoria');
            const inputIdCat = itemElement.querySelector('.input-categoria-id');
            const resultsContainerCat = itemElement.querySelector('.autocomplete-results-categoria');

            const inputQuantidade = itemElement.querySelector('.input-quantidade');
            const inputPreco = itemElement.querySelector('.input-preco');
            const subtotalElement = itemElement.querySelector('.item-subtotal');

            function calcularSubtotal() {
                const quantidade = parseFloat(inputQuantidade.value) || 0;
                const preco = unmaskCurrency(inputPreco.value);
                const subtotal = Math.round((quantidade * preco) * 100) / 100;
                subtotalElement.textContent = 'R$ ' + new Intl.NumberFormat('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(subtotal);
                calcularTotal();
            }

            function atualizarEstadoCategoria() {
                if (inputId.value) {
                    // Produto cadastrado selecionado: busca a categoria dele
                    const p = produtos.find(prod => prod.id == inputId.value);
                    if (p && p.categoria_id && categorias[p.categoria_id]) {
                        inputIdCat.value = p.categoria_id;
                        inputSearchCat.value = categorias[p.categoria_id];
                        inputSearchCat.readOnly = true;
                        inputSearchCat.classList.add('bg-gray-100', 'cursor-not-allowed');
                    } else {
                        // Se o produto no banco não tem categoria cadastrada, permite escolher!
                        inputSearchCat.readOnly = false;
                        inputSearchCat.classList.remove('bg-gray-100', 'cursor-not-allowed');
                    }
                } else {
                    // Produto novo
                    inputSearchCat.readOnly = false;
                    inputSearchCat.classList.remove('bg-gray-100', 'cursor-not-allowed');
                }
            }

            // Botão Novo Produto (Cadastro Rápido)
            const btnCadastroRapido = itemElement.querySelector('.btn-abrir-cadastro-rapido');
            if (btnCadastroRapido) {
                btnCadastroRapido.addEventListener('click', function(e) {
                    e.preventDefault();
                    abrirModalCadastroRapido(itemElement, inputSearch.value.trim());
                });
            }

            // Product Autocomplete Logic
            inputSearch.addEventListener('input', function() {
                const term = this.value.toLowerCase().trim();
                resultsContainer.innerHTML = '';

                // Limpa ID ao digitar para forçar novo cadastro se não selecionar do autocomplete
                inputId.value = '';
                // Sincroniza nome temporário para o auto-cadastro no backend
                if (inputNomeTemp) inputNomeTemp.value = this.value;

                atualizarEstadoCategoria();

                const filtered = !term ? produtos.slice(0, 15) : produtos.filter(p => p.nome.toLowerCase().includes(term));

                if (filtered.length === 0) {
                    const nomeBuscado = inputSearch.value.trim();
                    resultsContainer.innerHTML = `
                        <div class="p-2.5 bg-amber-50 border-b border-amber-100 text-amber-800 text-xs">
                            <span class="font-medium">Nenhum produto cadastrado encontrado</span>
                        </div>
                        <div class="p-2.5 bg-blue-50/80 hover:bg-blue-100 cursor-pointer text-blue-700 text-xs font-bold flex items-center gap-2 transition btn-cadastrar-rapido-opt">
                            <svg class="w-4 h-4 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>+ Cadastrar Produto Rápido</span>
                        </div>
                    `;
                    const btnCad = resultsContainer.querySelector('.btn-cadastrar-rapido-opt');
                    if (btnCad) {
                        btnCad.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            resultsContainer.classList.add('hidden');
                            abrirModalCadastroRapido(itemElement, nomeBuscado);
                        });
                    }
                } else {
                    filtered.forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'p-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-0 text-sm flex items-center justify-between';
                        div.innerHTML = `
                            <span class="font-medium text-gray-800">${escapeHtml(p.nome)}</span>
                            <span class="text-xs text-gray-500">Custo: R$ ${formatarBrlMoeda(parseFloat(p.preco_custo || 0))}</span>
                        `;
                        div.dataset.id = p.id;
                        div.dataset.preco = p.preco_custo;

                        div.addEventListener('click', function() {
                            inputSearch.value = p.nome;
                            inputId.value = p.id;
                            if (inputNomeTemp) inputNomeTemp.value = p.nome;
                            if (inputCodigoBarras) inputCodigoBarras.value = p.codigo_barras || '';
                            if (inputMarca) inputMarca.value = p.marca || '';

                            if (p.preco_custo) {
                                inputPreco.value = formatUnitPrice(parseFloat(p.preco_custo));
                            }
                            resultsContainer.classList.add('hidden');
                            atualizarEstadoCategoria();
                            calcularSubtotal();
                        });

                        resultsContainer.appendChild(div);
                    });

                    // Atalho no rodapé para cadastrar novo
                    const divNovo = document.createElement('div');
                    divNovo.className = 'p-2 bg-gray-50 hover:bg-blue-50 cursor-pointer border-t border-gray-200 text-blue-600 text-xs font-semibold flex items-center gap-1.5 transition';
                    const textoCadastrar = inputSearch.value.trim() ? `+ Cadastrar "${escapeHtml(inputSearch.value.trim())}" como novo produto` : '+ Cadastrar Novo Produto';
                    divNovo.innerHTML = `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> ${textoCadastrar}`;
                    divNovo.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        resultsContainer.classList.add('hidden');
                        abrirModalCadastroRapido(itemElement, inputSearch.value.trim());
                    });
                    resultsContainer.appendChild(divNovo);
                }

                resultsContainer.classList.remove('hidden');
            });

            inputSearch.addEventListener('focus', function() {
                if (!this.value.trim()) {
                    this.dispatchEvent(new Event('input'));
                }
            });

            // Category Autocomplete Logic
            function renderCategoryResults(term = '') {
                if (inputSearchCat.readOnly) return;

                resultsContainerCat.innerHTML = '';
                const cleanTerm = (term || '').trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

                const entries = Object.entries(categorias);
                const filtered = entries.filter(([id, nome]) => {
                    if (!cleanTerm) return true; // Mostra todas se vazio
                    const cleanNome = (nome || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                    return cleanNome.includes(cleanTerm);
                });

                if (filtered.length === 0) {
                    resultsContainerCat.innerHTML = '<div class="p-3 text-gray-500 text-sm italic">Nenhuma categoria encontrada</div>';
                } else {
                    filtered.forEach(([id, nome]) => {
                        const div = document.createElement('div');
                        div.className = 'p-2.5 hover:bg-green-50 hover:text-green-800 cursor-pointer border-b border-gray-100 last:border-0 text-sm flex items-center justify-between transition-colors';
                        div.textContent = nome;

                        div.addEventListener('mousedown', function(e) {
                            e.preventDefault(); // Evita perder foco antes de registrar
                            inputSearchCat.value = nome;
                            inputIdCat.value = id;
                            resultsContainerCat.classList.add('hidden');
                        });

                        resultsContainerCat.appendChild(div);
                    });
                }

                resultsContainerCat.classList.remove('hidden');
            }

            inputSearchCat.addEventListener('focus', function() {
                renderCategoryResults(this.value);
            });

            inputSearchCat.addEventListener('click', function() {
                renderCategoryResults(this.value);
            });

            inputSearchCat.addEventListener('input', function() {
                if (!this.value.trim()) {
                    inputIdCat.value = '';
                }
                renderCategoryResults(this.value);
            });

            inputQuantidade.addEventListener('input', calcularSubtotal);
            inputPreco.addEventListener('input', calcularSubtotal);

            // Sincroniza categoria inicial e define readonly se já houver produto_id
            atualizarEstadoCategoria();

            // Dispara cálculo inicial se já houver valores preenchidos (ex: XML)
            if (inputQuantidade.value || inputPreco.value) {
                calcularSubtotal();
            }
        }

        // Calcula total geral e desdobramento da nota fiscal
        function calcularTotal() {
            let totalProdutos = 0;
            document.querySelectorAll('.item-subtotal').forEach(function(el) {
                const text = el.textContent.replace('R$ ', '').replace('R$', '').trim();
                totalProdutos += unmaskCurrency(text);
            });

            const frete = unmaskCurrency(document.getElementById('input-frete')?.value || 0);
            const seguro = unmaskCurrency(document.getElementById('input-seguro')?.value || 0);
            const outras = unmaskCurrency(document.getElementById('input-outras-despesas')?.value || 0);
            const ipi = unmaskCurrency(document.getElementById('input-ipi')?.value || 0);
            const icmsSt = unmaskCurrency(document.getElementById('input-icms-st')?.value || 0);
            const fcpSt = unmaskCurrency(document.getElementById('input-fcp-st')?.value || 0);
            const desconto = unmaskCurrency(document.getElementById('input-desconto')?.value || 0);

            const totalNota = Math.max(0, totalProdutos + frete + seguro + outras + ipi + icmsSt + fcpSt - desconto);

            const formatBRL = (val) => 'R$ ' + new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val);

            const elResumoProdutos = document.getElementById('resumo-subtotal-produtos');
            if (elResumoProdutos) elResumoProdutos.textContent = formatBRL(totalProdutos);

            const elResumoFrete = document.getElementById('resumo-frete');
            if (elResumoFrete) elResumoFrete.textContent = formatBRL(frete);

            const elResumoSeguro = document.getElementById('resumo-seguro');
            if (elResumoSeguro) elResumoSeguro.textContent = formatBRL(seguro);

            const elResumoOutras = document.getElementById('resumo-outras');
            if (elResumoOutras) elResumoOutras.textContent = formatBRL(outras);

            const elResumoIpi = document.getElementById('resumo-ipi');
            if (elResumoIpi) elResumoIpi.textContent = formatBRL(ipi);

            const elResumoSt = document.getElementById('resumo-st');
            if (elResumoSt) elResumoSt.textContent = formatBRL(icmsSt + fcpSt);

            const elResumoDesconto = document.getElementById('resumo-desconto');
            if (elResumoDesconto) elResumoDesconto.textContent = formatBRL(desconto);

            const elTotalCompra = document.getElementById('total-compra');
            if (elTotalCompra) elTotalCompra.textContent = formatBRL(totalNota);

            // Atualiza resumo do parcelamento manual se estiver visível
            recalcularResumoManual();
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

            // Se for parcelamento manual, valida se os valores batem
            const tipo = document.getElementById('tipo-parcelamento').value;
            if (tipo === 'manual') {
                const totalLiquido = obterTotalLiquido();
                let totalParcelas = 0;
                document.querySelectorAll('.parcela-valor').forEach(function(input) {
                    totalParcelas += unmaskCurrency(input.value);
                });

                if (document.querySelectorAll('.parcela-manual-row').length === 0) {
                    alert('Por favor, adicione pelo menos uma parcela para o parcelamento manual.');
                    btnSubmit.disabled = false;
                    btnSubmit.classList.add('bg-green-600', 'hover:bg-green-700');
                    btnSubmit.classList.remove('bg-gray-400', 'cursor-not-allowed');
                    btnSubmit.innerHTML = `<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>` + (isNewRecord ? 'Cadastrar' : 'Atualizar');
                    return false;
                }

                if (Math.abs(totalLiquido - totalParcelas) >= 0.01) {
                    alert('A soma das parcelas (R$ ' + new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2 }).format(totalParcelas) + ') não corresponde ao total líquido da compra (R$ ' + new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2 }).format(totalLiquido) + '). Ajuste os valores antes de salvar.');
                    btnSubmit.disabled = false;
                    btnSubmit.classList.add('bg-green-600', 'hover:bg-green-700');
                    btnSubmit.classList.remove('bg-gray-400', 'cursor-not-allowed');
                    btnSubmit.innerHTML = `<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>` + (isNewRecord ? 'Cadastrar' : 'Atualizar');
                    return false;
                }
            }

            // Desmascara valores de moeda antes do envio final
            document.querySelectorAll('.currency-input').forEach(input => {
                input.value = unmaskCurrency(input.value);
            });

            return true; // Continua com o submit final
        });

        // Inicialização de parcelas existentes se houver
        if (contasPagarExistentes && contasPagarExistentes.length > 1) {
            selectTipoParcelamento.value = 'manual';
            selectTipoParcelamento.dispatchEvent(new Event('change'));
            // Limpa qualquer linha adicionada por padrão
            document.getElementById('lista-parcelas-manuais').innerHTML = '';
            
            contasPagarExistentes.forEach(c => {
                // Formata o valor com vírgula para centavos
                const valorFormatado = new Intl.NumberFormat('pt-BR', {
                    minimumFractionDigits: 2
                }).format(c.valor);
                adicionarParcelaManual(c.data_vencimento, valorFormatado);
            });
            recalcularResumoManual();
        } else if (contasPagarExistentes && contasPagarExistentes.length === 1) {
            // Se for apenas uma parcela, podemos manter "À Vista"
            selectTipoParcelamento.value = 'vista';
            selectTipoParcelamento.dispatchEvent(new Event('change'));
        } else {
            // Nova compra sem contas existentes, inicializa como "À Vista"
            if (selectTipoParcelamento) {
                selectTipoParcelamento.value = 'vista';
                selectTipoParcelamento.dispatchEvent(new Event('change'));
            }
        }

        // ==========================================
        // CADASTRO RÁPIDO DE PRODUTO VIA MODAL
        // ==========================================
        let activeItemElementParaCadastro = null;
        const modalCadastroRapido = document.getElementById('modal-cadastro-rapido-produto');
        const formCadastroRapido = document.getElementById('form-cadastro-rapido-produto');
        const alertaErroRapido = document.getElementById('alerta-erro-cadastro-rapido');
        const textoErroRapido = document.getElementById('texto-erro-cadastro-rapido');
        const btnSalvarRapido = document.getElementById('btn-salvar-cadastro-rapido');
        const rapidoNome = document.getElementById('rapido-nome');
        const rapidoCategoriaId = document.getElementById('rapido-categoria-id');
        const rapidoPrecoCusto = document.getElementById('rapido-preco-custo');
        const rapidoPrecoVenda = document.getElementById('rapido-preco-venda');
        const rapidoEstoqueAtual = document.getElementById('rapido-estoque-atual');
        const rapidoEstoqueMinimo = document.getElementById('rapido-estoque-minimo');
        const rapidoPontoCorte = document.getElementById('rapido-ponto-corte');
        const rapidoIndicadorMargem = document.getElementById('rapido-indicador-margem');

        function atualizarIndicadorMargem() {
            if (!rapidoPrecoCusto || !rapidoPrecoVenda || !rapidoIndicadorMargem) return;
            const custo = unmaskCurrency(rapidoPrecoCusto.value);
            const venda = unmaskCurrency(rapidoPrecoVenda.value);
            if (custo > 0 && venda > 0) {
                const lucro = venda - custo;
                const margem = (lucro / venda) * 100;
                if (lucro >= 0) {
                    rapidoIndicadorMargem.textContent = `Margem: ${margem.toFixed(1)}% (Lucro R$ ${formatarBrlMoeda(lucro)})`;
                    rapidoIndicadorMargem.className = 'text-[11px] font-semibold text-emerald-700';
                } else {
                    rapidoIndicadorMargem.textContent = `Prejuízo: R$ ${formatarBrlMoeda(Math.abs(lucro))}`;
                    rapidoIndicadorMargem.className = 'text-[11px] font-semibold text-red-600';
                }
            } else {
                rapidoIndicadorMargem.textContent = 'Margem: -';
                rapidoIndicadorMargem.className = 'text-[11px] font-semibold text-gray-500';
            }
        }

        if (rapidoPrecoCusto) {
            rapidoPrecoCusto.addEventListener('input', function() {
                atualizarIndicadorMargem();
            });
        }

        if (rapidoPrecoVenda) {
            rapidoPrecoVenda.addEventListener('input', function() {
                atualizarIndicadorMargem();
            });
        }

        // Atalhos de margem rápida
        document.querySelectorAll('.btn-margem-rapida').forEach(btn => {
            btn.addEventListener('click', function() {
                const pct = parseFloat(this.dataset.percent) || 0;
                const custo = unmaskCurrency(rapidoPrecoCusto.value);
                if (custo > 0) {
                    const venda = Math.round(custo * (1 + (pct / 100)) * 100) / 100;
                    rapidoPrecoVenda.value = formatarBrlMoeda(venda);
                    atualizarIndicadorMargem();
                } else {
                    alert('Informe primeiro o preço de custo para calcular a margem.');
                    rapidoPrecoCusto.focus();
                }
            });
        });

        // Sincronizar Ponto de Corte >= Estoque Mínimo
        if (rapidoEstoqueMinimo && rapidoPontoCorte) {
            rapidoEstoqueMinimo.addEventListener('input', function() {
                const min = parseFloat(this.value) || 0;
                const corte = parseFloat(rapidoPontoCorte.value) || 0;
                if (corte < min) {
                    rapidoPontoCorte.value = min;
                }
            });
        }

        function abrirModalCadastroRapido(itemElement, nomeInicial = '') {
            activeItemElementParaCadastro = itemElement;
            if (formCadastroRapido) formCadastroRapido.reset();
            if (alertaErroRapido) alertaErroRapido.classList.add('hidden');

            if (rapidoNome) rapidoNome.value = (nomeInicial || '').trim();

            // Se o item já tiver preço ou código de barras preenchido, aproveita
            if (itemElement) {
                const inputPrecoItem = itemElement.querySelector('.input-preco');
                if (inputPrecoItem && unmaskCurrency(inputPrecoItem.value) > 0) {
                    const preco = unmaskCurrency(inputPrecoItem.value);
                    if (rapidoPrecoCusto) rapidoPrecoCusto.value = formatarBrlMoeda(preco);
                    if (rapidoPrecoVenda) rapidoPrecoVenda.value = formatarBrlMoeda(Math.round(preco * 1.5 * 100) / 100);
                } else {
                    if (rapidoPrecoCusto) rapidoPrecoCusto.value = '0,00';
                    if (rapidoPrecoVenda) rapidoPrecoVenda.value = '0,00';
                }

                const codBarrasItem = itemElement.querySelector('.input-codigo-barras');
                const modalCodBarras = document.getElementById('rapido-codigo-barras');
                if (codBarrasItem && codBarrasItem.value && modalCodBarras) {
                    modalCodBarras.value = codBarrasItem.value;
                }

                const marcaItem = itemElement.querySelector('.input-marca');
                const modalMarca = document.getElementById('rapido-marca');
                if (marcaItem && marcaItem.value && modalMarca) {
                    modalMarca.value = marcaItem.value;
                }

                const catItem = itemElement.querySelector('.input-categoria-id');
                if (catItem && catItem.value && rapidoCategoriaId) {
                    rapidoCategoriaId.value = catItem.value;
                }
            } else {
                if (rapidoPrecoCusto) rapidoPrecoCusto.value = '0,00';
                if (rapidoPrecoVenda) rapidoPrecoVenda.value = '0,00';
            }

            if (rapidoEstoqueAtual) rapidoEstoqueAtual.value = '0';
            if (rapidoEstoqueMinimo) rapidoEstoqueMinimo.value = '1';
            if (rapidoPontoCorte) rapidoPontoCorte.value = '1';

            atualizarIndicadorMargem();

            if (modalCadastroRapido) {
                modalCadastroRapido.classList.remove('hidden');
                setTimeout(() => {
                    if (!rapidoNome.value) {
                        rapidoNome.focus();
                    } else if (!rapidoCategoriaId.value) {
                        rapidoCategoriaId.focus();
                    } else {
                        rapidoPrecoCusto.focus();
                    }
                }, 100);
            }
        }

        function fecharModalCadastroRapido() {
            if (modalCadastroRapido) modalCadastroRapido.classList.add('hidden');
            activeItemElementParaCadastro = null;
        }

        document.querySelectorAll('.btn-fechar-modal-rapido, #backdrop-cadastro-rapido').forEach(el => {
            el.addEventListener('click', fecharModalCadastroRapido);
        });

        // Submit do formulário rápido
        if (formCadastroRapido) {
            formCadastroRapido.addEventListener('submit', function(e) {
                e.preventDefault();

                if (alertaErroRapido) alertaErroRapido.classList.add('hidden');
                if (textoErroRapido) textoErroRapido.textContent = '';

                const nome = rapidoNome.value.trim();
                const categoriaId = rapidoCategoriaId.value;
                const estMin = parseFloat(rapidoEstoqueMinimo.value) || 0;
                const pontoCorte = parseFloat(rapidoPontoCorte.value) || 0;

                if (!nome) {
                    if (alertaErroRapido) alertaErroRapido.classList.remove('hidden');
                    if (textoErroRapido) textoErroRapido.textContent = 'Informe o nome do produto.';
                    rapidoNome.focus();
                    return;
                }
                if (!categoriaId) {
                    if (alertaErroRapido) alertaErroRapido.classList.remove('hidden');
                    if (textoErroRapido) textoErroRapido.textContent = 'Selecione uma categoria para o produto.';
                    rapidoCategoriaId.focus();
                    return;
                }
                if (pontoCorte < estMin) {
                    rapidoPontoCorte.value = estMin;
                }

                if (btnSalvarRapido) {
                    btnSalvarRapido.disabled = true;
                    btnSalvarRapido.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Salvando...
                    `;
                }

                const formData = new FormData(formCadastroRapido);
                formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->getCsrfToken() ?>');

                fetch('<?= \yii\helpers\Url::to(['cadastro-rapido-produto']) ?>', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': '<?= Yii::$app->request->getCsrfToken() ?>'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (btnSalvarRapido) {
                        btnSalvarRapido.disabled = false;
                        btnSalvarRapido.innerHTML = `
                            <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Salvar e Vincular à Compra
                        `;
                    }

                    if (!data.success) {
                        if (alertaErroRapido) alertaErroRapido.classList.remove('hidden');
                        if (textoErroRapido) textoErroRapido.textContent = data.message || 'Erro ao salvar produto.';
                        return;
                    }

                    const p = data.produto;

                    // Adiciona ou atualiza no array local de produtos para autocompletes futuros
                    const idxExistente = produtos.findIndex(item => item.id == p.id);
                    if (idxExistente >= 0) {
                        produtos[idxExistente] = p;
                    } else {
                        produtos.push(p);
                    }

                    // Vincula imediatamente ao item ativo na tela
                    if (activeItemElementParaCadastro) {
                        const inputSearch = activeItemElementParaCadastro.querySelector('.input-search');
                        const inputId = activeItemElementParaCadastro.querySelector('.input-produto-id');
                        const inputNomeTemp = activeItemElementParaCadastro.querySelector('.input-nome-produto-temp');
                        const inputPreco = activeItemElementParaCadastro.querySelector('.input-preco');
                        const inputMarca = activeItemElementParaCadastro.querySelector('.input-marca');
                        const inputCodigoBarras = activeItemElementParaCadastro.querySelector('.input-codigo-barras');
                        const inputPrecoSugeridoTemp = activeItemElementParaCadastro.querySelector('.input-preco-venda-sugerido-temp');
                        const inputEstoqueMinTemp = activeItemElementParaCadastro.querySelector('.input-estoque-minimo-temp');
                        const inputPontoCorteTemp = activeItemElementParaCadastro.querySelector('.input-ponto-corte-temp');
                        const inputUnidadeMedidaTemp = activeItemElementParaCadastro.querySelector('.input-unidade-medida-temp');
                        const containerPrecoSugerido = activeItemElementParaCadastro.querySelector('.preco-sugerido-container');
                        const spanPrecoSugerido = activeItemElementParaCadastro.querySelector('.span-preco-sugerido');

                        if (inputSearch) inputSearch.value = p.nome;
                        if (inputId) inputId.value = p.id;
                        if (inputNomeTemp) inputNomeTemp.value = p.nome;
                        if (inputMarca && p.marca) inputMarca.value = p.marca;
                        if (inputCodigoBarras && p.codigo_barras) inputCodigoBarras.value = p.codigo_barras;

                        if (inputPrecoSugeridoTemp) inputPrecoSugeridoTemp.value = p.preco_venda_sugerido;
                        if (inputEstoqueMinTemp) inputEstoqueMinTemp.value = p.estoque_minimo;
                        if (inputPontoCorteTemp) inputPontoCorteTemp.value = p.ponto_corte;
                        if (inputUnidadeMedidaTemp) inputUnidadeMedidaTemp.value = p.unidade_medida;

                        if (spanPrecoSugerido && p.preco_venda_sugerido > 0) {
                            spanPrecoSugerido.textContent = 'R$ ' + formatarBrlMoeda(p.preco_venda_sugerido);
                            if (containerPrecoSugerido) containerPrecoSugerido.classList.remove('hidden');
                        }

                        // Se o preço de custo foi informado, preenche o preço unitário do item
                        if (inputPreco && p.preco_custo > 0) {
                            inputPreco.value = formatUnitPrice(p.preco_custo);
                            const inputQtd = activeItemElementParaCadastro.querySelector('.input-quantidade');
                            if (inputQtd && (!inputQtd.value || parseFloat(inputQtd.value) <= 0)) {
                                inputQtd.value = 1;
                            }
                        }

                        // Sincroniza categoria
                        const inputIdCat = activeItemElementParaCadastro.querySelector('.input-categoria-id');
                        const inputSearchCat = activeItemElementParaCadastro.querySelector('.input-search-categoria');
                        if (inputIdCat) inputIdCat.value = p.categoria_id;
                        if (inputSearchCat) {
                            inputSearchCat.value = p.categoria_nome || categorias[p.categoria_id] || '';
                            inputSearchCat.readOnly = true;
                            inputSearchCat.classList.add('bg-gray-100', 'cursor-not-allowed');
                        }

                        // Atualiza subtotal do item
                        const inputQtd = activeItemElementParaCadastro.querySelector('.input-quantidade');
                        const subtotalEl = activeItemElementParaCadastro.querySelector('.item-subtotal');
                        if (inputQtd && subtotalEl && inputPreco) {
                            const qtd = parseFloat(inputQtd.value) || 0;
                            const prc = unmaskCurrency(inputPreco.value);
                            subtotalEl.textContent = 'R$ ' + formatarBrlMoeda(Math.round(qtd * prc * 100) / 100);
                        }

                        calcularTotal();
                    }

                    fecharModalCadastroRapido();

                    // Notificação Toast
                    mostrarToastSucesso(`Produto "${p.nome}" cadastrado e vinculado com sucesso!`);
                })
                .catch(err => {
                    if (btnSalvarRapido) {
                        btnSalvarRapido.disabled = false;
                        btnSalvarRapido.innerHTML = `
                            <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Salvar e Vincular à Compra
                        `;
                    }
                    if (alertaErroRapido) alertaErroRapido.classList.remove('hidden');
                    if (textoErroRapido) textoErroRapido.textContent = 'Erro ao se comunicar com o servidor: ' + err.message;
                });
            });
        }

        // Toast de Notificação
        function mostrarToastSucesso(mensagem) {
            let toast = document.getElementById('toast-feedback-compra');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'toast-feedback-compra';
                toast.className = 'fixed bottom-5 right-5 z-50 bg-emerald-600 text-white px-4 py-3 rounded-xl shadow-2xl flex items-center gap-3 transition-all transform duration-300 translate-y-10 opacity-0';
                document.body.appendChild(toast);
            }
            toast.innerHTML = `
                <svg class="w-5 h-5 flex-shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span class="text-sm font-semibold">${mensagem}</span>
            `;
            toast.classList.remove('translate-y-10', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-10', 'opacity-0');
            }, 4000);
        }

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