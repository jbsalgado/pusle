<?php
/**
 * View: Confirmar Pagamentos - Vendas Pendentes do Catálogo
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var bool $ehAdministrador
 * @var bool $ehDonoLoja
 */

use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\vendas\models\Venda;

$this->title = 'Confirmar Pagamentos - Catálogo';
?>

<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 shadow-sm">
        <div class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <a href="<?= Url::to(['/vendas/inicio/index']) ?>" 
                       class="text-gray-500 hover:text-gray-700 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Confirmar Pagamentos</h1>
                        <p class="text-sm text-gray-600 mt-1">Vendas pendentes do catálogo</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Conteúdo -->
    <div class="px-4 sm:px-6 lg:px-8 py-6 max-w-7xl mx-auto">
        <?php if ($dataProvider->getTotalCount() > 0): ?>
            <div class="mb-4 flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    <strong><?= $dataProvider->getTotalCount() ?></strong> venda(s) pendente(s)
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <?php foreach ($dataProvider->getModels() as $venda): 
                    $cliente = $venda->cliente;
                    $formaPagamento = $venda->formaPagamento;
                ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-5 hover:shadow-md transition-shadow">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h3 class="text-base sm:text-lg font-semibold text-gray-900">
                                            Venda #<?= substr($venda->id, 0, 8) ?>
                                        </h3>
                                        <p class="text-xs sm:text-sm text-gray-500 mt-1">
                                            <?= date('d/m/Y H:i', strtotime($venda->data_criacao)) ?>
                                        </p>
                                    </div>
                                    <span class="px-2.5 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">
                                        Aguardando Pagamento
                                    </span>
                                </div>
                                
                                <?php if ($cliente): ?>
                                    <p class="text-sm text-gray-700 mb-1">
                                        <strong>Cliente:</strong> <?= Html::encode($cliente->nome_completo ?? $cliente->nome ?? 'N/A') ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($formaPagamento): ?>
                                    <p class="text-sm text-gray-700 mb-1">
                                        <strong>Forma de Pagamento:</strong> <?= Html::encode($formaPagamento->nome) ?>
                                    </p>
                                <?php endif; ?>
                                
                                <!-- Itens da Venda com Estoque -->
                                <div class="mt-3 mb-2">
                                    <p class="text-xs font-semibold text-gray-700 mb-1">Itens do Pedido:</p>
                                    <div class="space-y-1">
                                        <?php 
                                        $temEstoqueInsuficiente = false;
                                        foreach ($venda->itens as $item): 
                                            $produto = $item->produto;
                                            $estoqueDisponivel = $produto ? $produto->estoque_atual : 0;
                                            $estoqueSuficiente = $estoqueDisponivel >= $item->quantidade;
                                            if (!$estoqueSuficiente) {
                                                $temEstoqueInsuficiente = true;
                                            }
                                        ?>
                                            <div class="text-xs <?= $estoqueSuficiente ? 'text-gray-600' : 'text-red-600 font-semibold' ?>">
                                                • <?= Html::encode($produto ? $produto->nome : 'Produto não encontrado') ?> 
                                                (Qtd: <?= $item->quantidade ?>) 
                                                - Estoque: <strong><?= $estoqueDisponivel ?></strong>
                                                <?php if (!$estoqueSuficiente): ?>
                                                    <span class="text-red-600 font-bold">⚠️ INSUFICIENTE</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if ($temEstoqueInsuficiente): ?>
                                        <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700">
                                            ⚠️ <strong>Atenção:</strong> Alguns itens têm estoque insuficiente. A confirmação será bloqueada.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="text-lg sm:text-xl font-bold text-green-600 mt-2">
                                    R$ <?= number_format($venda->valor_total, 2, ',', '.') ?>
                                </p>
                            </div>
                            
                            <?php if ($ehAdministrador || $ehDonoLoja): 
                                // Verifica se há estoque suficiente para todos os itens
                                $podeConfirmar = true;
                                $mensagemConfirmacao = 'Tem certeza que deseja confirmar o pagamento desta venda? O estoque será atualizado, a entrada será registrada no caixa e o comprovante será gerado.';
                                foreach ($venda->itens as $item) {
                                    $produto = $item->produto;
                                    if (!$produto || ($produto->estoque_atual < $item->quantidade)) {
                                        $podeConfirmar = false;
                                        $mensagemConfirmacao = 'ATENÇÃO: Esta venda contém itens com estoque insuficiente. A confirmação será bloqueada.';
                                        break;
                                    }
                                }
                            ?>
                            <div class="flex justify-end">
                                <?php if ($podeConfirmar): ?>
                                    <?= Html::a('✅ Confirmar Pagamento', ['confirmar-pagamento', 'id' => $venda->id], [
                                        'class' => 'px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors text-sm text-center',
                                        'data' => [
                                            'confirm' => $mensagemConfirmacao,
                                            'method' => 'post',
                                        ],
                                    ]) ?>
                                <?php else: ?>
                                    <span class="px-4 py-2 bg-red-500 text-white font-semibold rounded-lg text-sm text-center cursor-not-allowed" title="Estoque insuficiente para confirmar esta venda">
                                        ⚠️ Estoque Insuficiente
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="flex justify-end">
                                <span class="px-4 py-2 bg-gray-400 text-white font-semibold rounded-lg text-sm text-center cursor-not-allowed">
                                    Apenas administradores podem confirmar
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginação -->
            <?= \yii\widgets\LinkPager::widget([
                'pagination' => $dataProvider->pagination,
                'options' => ['class' => 'flex justify-center mt-6 space-x-2'],
                'linkOptions' => ['class' => 'px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-sm'],
                'activePageCssClass' => 'bg-blue-600 text-white border-blue-600',
            ]) ?>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma venda pendente</h3>
                <p class="text-sm text-gray-600">Não há vendas do catálogo aguardando confirmação de pagamento.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

