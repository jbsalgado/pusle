<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Compra #' . substr($model->id, 0, 8);
$this->params['breadcrumbs'][] = ['label' => 'Compras', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">

        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <div class="flex flex-wrap gap-2">
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                        ['index'],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm']
                    ) ?>
                    <?php if ($model->status_compra === 'PENDENTE'): ?>
                        <?= Html::a(
                            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar',
                            ['update', 'id' => $model->id],
                            ['class' => 'inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm']
                        ) ?>
                        <?= Html::beginForm(['concluir', 'id' => $model->id], 'post', ['class' => 'inline']) ?>
                        <?= Html::submitButton(
                            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Concluir',
                            [
                                'class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm',
                                'onclick' => 'return confirm("Tem certeza que deseja concluir esta compra? O estoque será atualizado.");'
                            ]
                        ) ?>
                        <?= Html::endForm() ?>
                    <?php endif; ?>
                    <?= Html::button(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.73-1.455L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.37 9.864-9.799.002-2.63-1.023-5.101-2.885-6.965C16.59 2.012 14.125.99 11.5.99c-5.44 0-9.866 4.372-9.87 9.802 0 1.714.47 3.387 1.357 4.847l-.994 3.63 3.771-.975zm11.077-4.57c-.27-.136-1.602-.79-1.85-.88-.25-.09-.43-.136-.61.136-.18.27-.69.88-.845 1.06-.15.18-.3.2-.57.064-.27-.136-1.14-.42-2.172-1.34-.803-.717-1.345-1.603-1.502-1.874-.158-.27-.017-.417.118-.552.12-.12.27-.315.4-.472.13-.158.18-.27.27-.45.09-.18.044-.337-.02-.472-.063-.136-.61-1.47-.837-2.013-.218-.528-.44-.457-.61-.466-.156-.008-.336-.01-.515-.01-.18 0-.47.067-.716.337-.245.27-.938.917-.938 2.24 0 1.32.96 2.593 1.093 2.772.134.18 1.9 2.898 4.593 4.06.64.277 1.14.442 1.53.566.643.204 1.228.175 1.69.106.514-.077 1.602-.656 1.83-1.28.228-.623.228-1.157.16-1.282-.07-.124-.25-.176-.52-.312z"/></svg>Enviar WhatsApp',
                        [
                            'class' => 'inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm cursor-pointer',
                            'onclick' => 'abrirModalWhatsapp();'
                        ]
                    ) ?>
                </div>
            </div>
        </div>

        <!-- Card Principal -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white">Informações da Compra</h2>
                    <?php
                    $statusColors = [
                        'PENDENTE' => 'bg-yellow-100 text-yellow-800',
                        'CONCLUIDA' => 'bg-green-100 text-green-800',
                        'CANCELADA' => 'bg-red-100 text-red-800',
                    ];
                    $statusColor = $statusColors[$model->status_compra] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold <?= $statusColor ?>">
                        <?= Html::encode($model->getStatusLabel()) ?>
                    </span>
                </div>
            </div>

            <div class="p-6 space-y-6">

                <!-- Dados Básicos -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Dados Básicos
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Fornecedor</label>
                            <p class="text-base text-gray-900 font-semibold"><?= Html::encode($model->fornecedor->nome_fantasia) ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Data da Compra</label>
                            <p class="text-base text-gray-900"><?= Yii::$app->formatter->asDate($model->data_compra) ?></p>
                        </div>
                        <?php if ($model->data_vencimento): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Data de Vencimento</label>
                                <p class="text-base text-gray-900"><?= Yii::$app->formatter->asDate($model->data_vencimento) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($model->numero_nota_fiscal): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Número da NF</label>
                                <p class="text-base text-gray-900"><?= Html::encode($model->numero_nota_fiscal) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($model->serie_nota_fiscal): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Série da NF</label>
                                <p class="text-base text-gray-900"><?= Html::encode($model->serie_nota_fiscal) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($model->forma_pagamento): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Forma de Pagamento</label>
                                <p class="text-base text-gray-900"><?= Html::encode($model->forma_pagamento) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Itens da Compra -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Itens da Compra
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantidade</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Preço Unit.</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($model->itens as $item): ?>
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900"><?= Html::encode($item->produto->nome) ?></td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900"><?= number_format($item->quantidade, $item->produto->venda_fracionada ? 3 : 0, ',', '.') ?></td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900">R$ <?= number_format($item->preco_unitario, 2, ',', '.') ?></td>
                                        <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">R$ <?= number_format($item->valor_total_item, 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-gray-900">Subtotal:</td>
                                    <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">R$ <?= number_format($model->valor_total, 2, ',', '.') ?></td>
                                </tr>
                                <?php if ($model->valor_desconto > 0): ?>
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right text-sm text-gray-600">Desconto:</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-600">- R$ <?= number_format($model->valor_desconto, 2, ',', '.') ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($model->valor_frete > 0): ?>
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right text-sm text-gray-600">Frete:</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-600">+ R$ <?= number_format($model->valor_frete, 2, ',', '.') ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right text-base font-bold text-gray-900">Total:</td>
                                    <td class="px-4 py-3 text-right text-base font-bold text-gray-900">R$ <?= number_format($model->getValorLiquido(), 2, ',', '.') ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Observações -->
                <?php if ($model->observacoes): ?>
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                            Observações
                        </h3>
                        <p class="text-base text-gray-700 whitespace-pre-line"><?= Html::encode($model->observacoes) ?></p>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Ações -->
        <div class="flex flex-wrap gap-2">
            <?php if ($model->status_compra === 'PENDENTE'): ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar',
                    ['update', 'id' => $model->id],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
                ) ?>
                <?= Html::beginForm(['concluir', 'id' => $model->id], 'post', ['class' => 'inline']) ?>
                <?= Html::submitButton(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Concluir',
                    [
                        'class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300',
                        'onclick' => 'return confirm("Tem certeza que deseja concluir esta compra? O estoque será atualizado.");'
                    ]
                ) ?>
                <?= Html::endForm() ?>
            <?php endif; ?>
            <?php if ($model->status_compra !== 'CANCELADA' && $model->status_compra !== 'CONCLUIDA'): ?>
                <?= Html::beginForm(['cancelar', 'id' => $model->id], 'post', ['class' => 'inline']) ?>
                <?= Html::submitButton(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Cancelar',
                    [
                        'class' => 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-300',
                        'onclick' => 'return confirm("Tem certeza que deseja cancelar esta compra?");'
                    ]
                ) ?>
                <?= Html::endForm() ?>
            <?php endif; ?>
            <?= Html::button(
                '<svg class="w-5 h-5 inline-block mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.73-1.455L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.37 9.864-9.799.002-2.63-1.023-5.101-2.885-6.965C16.59 2.012 14.125.99 11.5.99c-5.44 0-9.866 4.372-9.87 9.802 0 1.714.47 3.387 1.357 4.847l-.994 3.63 3.771-.975zm11.077-4.57c-.27-.136-1.602-.79-1.85-.88-.25-.09-.43-.136-.61.136-.18.27-.69.88-.845 1.06-.15.18-.3.2-.57.064-.27-.136-1.14-.42-2.172-1.34-.803-.717-1.345-1.603-1.502-1.874-.158-.27-.017-.417.118-.552.12-.12.27-.315.4-.472.13-.158.18-.27.27-.45.09-.18.044-.337-.02-.472-.063-.136-.61-1.47-.837-2.013-.218-.528-.44-.457-.61-.466-.156-.008-.336-.01-.515-.01-.18 0-.47.067-.716.337-.245.27-.938.917-.938 2.24 0 1.32.96 2.593 1.093 2.772.134.18 1.9 2.898 4.593 4.06.64.277 1.14.442 1.53.566.643.204 1.228.175 1.69.106.514-.077 1.602-.656 1.83-1.28.228-.623.228-1.157.16-1.282-.07-.124-.25-.176-.52-.312z"/></svg>Enviar WhatsApp',
                [
                    'class' => 'inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg shadow-md transition duration-300 cursor-pointer',
                    'onclick' => 'abrirModalWhatsapp();'
                ]
            ) ?>
        </div>

    </div>
</div>

<!-- Modal WhatsApp -->
<div id="modalWhatsapp" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="fecharModalWhatsapp()"></div>

        <!-- Spacer para alinhar ao centro -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Conteúdo do Modal -->
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6 border border-gray-200">
            <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 sm:mx-0 sm:h-10 sm:w-10">
                    <svg class="h-6 w-6 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.73-1.455L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.37 9.864-9.799.002-2.63-1.023-5.101-2.885-6.965C16.59 2.012 14.125.99 11.5.99c-5.44 0-9.866 4.372-9.87 9.802 0 1.714.47 3.387 1.357 4.847l-.994 3.63 3.771-.975zm11.077-4.57c-.27-.136-1.602-.79-1.85-.88-.25-.09-.43-.136-.61.136-.18.27-.69.88-.845 1.06-.15.18-.3.2-.57.064-.27-.136-1.14-.42-2.172-1.34-.803-.717-1.345-1.603-1.502-1.874-.158-.27-.017-.417.118-.552.12-.12.27-.315.4-.472.13-.158.18-.27.27-.45.09-.18.044-.337-.02-.472-.063-.136-.61-1.47-.837-2.013-.218-.528-.44-.457-.61-.466-.156-.008-.336-.01-.515-.01-.18 0-.47.067-.716.337-.245.27-.938.917-.938 2.24 0 1.32.96 2.593 1.093 2.772.134.18 1.9 2.898 4.593 4.06.64.277 1.14.442 1.53.566.643.204 1.228.175 1.69.106.514-.077 1.602-.656 1.83-1.28.228-.623.228-1.157.16-1.282-.07-.124-.25-.176-.52-.312z"/>
                    </svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                    <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">
                        Enviar Pedido via WhatsApp
                    </h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500 mb-4">
                            O pedido será gerado em formato PDF profissional e enviado como documento.
                        </p>
                        
                        <?= Html::beginForm(['enviar-whatsapp', 'id' => $model->id], 'post', ['id' => 'formEnviarWhatsapp']) ?>
                        
                        <div class="mb-4">
                            <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1">Telefone do Fornecedor:</label>
                            <input type="text" name="telefone" id="telefone" value="<?= Html::encode($model->fornecedor->telefone) ?>" 
                                   class="shadow-sm focus:ring-emerald-500 focus:border-emerald-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border" 
                                   placeholder="Ex: 5581999998888" required />
                        </div>
                        
                        <div class="mb-4">
                            <label for="legenda" class="block text-sm font-medium text-gray-700 mb-1">Mensagem/Legenda:</label>
                            <textarea name="legenda" id="legenda" rows="3" 
                                      class="shadow-sm focus:ring-emerald-500 focus:border-emerald-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border" 
                                      placeholder="Digite uma mensagem para acompanhar o PDF..."><?= Html::encode("Olá! Segue em anexo o nosso Pedido de Compra #" . strtoupper(substr($model->id, 0, 8)) . ".") ?></textarea>
                        </div>
                        
                        <?= Html::endForm() ?>
                    </div>
                </div>
            </div>
            
            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                <button type="button" onclick="submeterFormWhatsapp()" 
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-semibold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:w-auto sm:text-sm cursor-pointer">
                    Confirmar Envio
                </button>
                <button type="button" onclick="fecharModalWhatsapp()" 
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:mt-0 sm:w-auto sm:text-sm cursor-pointer">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function abrirModalWhatsapp() {
    document.getElementById('modalWhatsapp').classList.remove('hidden');
}

function fecharModalWhatsapp() {
    document.getElementById('modalWhatsapp').classList.add('hidden');
}

function submeterFormWhatsapp() {
    var telefone = document.getElementById('telefone').value.trim();
    if (!telefone) {
        alert('Por favor, informe o telefone do fornecedor.');
        return;
    }
    
    // Desabilitar botões para evitar cliques duplos
    var btns = document.querySelectorAll('#modalWhatsapp button');
    btns.forEach(function(btn) {
        btn.disabled = true;
    });
    
    document.getElementById('formEnviarWhatsapp').submit();
}
</script>