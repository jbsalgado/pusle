<?php
/**
 * View: Comprovante de Venda
 * @var yii\web\View $this
 * @var app\modules\vendas\models\Venda $venda
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Comprovante de Venda';
$this->registerCss('
    @media print {
        body { margin: 0; padding: 0; }
        .no-print { display: none !important; }
        .comprovante-container { 
            max-width: 100% !important; 
            margin: 0 !important; 
            padding: 20px !important;
            box-shadow: none !important;
        }
    }
');
?>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-4xl mx-auto px-4">
        <!-- Botões de ação (não aparecem na impressão) -->
        <div class="no-print mb-4 flex flex-wrap gap-3 justify-between items-center">
            <a href="<?= Url::to(['confirmar-pagamentos']) ?>" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar
            </a>
            <div class="flex gap-3">
                <button onclick="window.print()" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimir
                </button>
            </div>
        </div>

        <!-- Comprovante -->
        <div class="comprovante-container bg-white rounded-lg shadow-lg p-6 sm:p-8">
            <!-- Cabeçalho -->
            <div class="text-center border-b border-gray-300 pb-4 mb-4">
                <?php
                // Busca dados da loja (mesma lógica do AuthController)
                $configuracao = \app\modules\vendas\models\Configuracao::find()
                    ->where(['usuario_id' => $venda->usuario_id])
                    ->orderBy(['data_atualizacao' => SORT_DESC, 'data_criacao' => SORT_DESC])
                    ->one();
                
                $usuario = $venda->usuario;
                $nomeLoja = 'Loja';
                if ($configuracao && $configuracao->nome_loja) {
                    $nomeLoja = $configuracao->nome_loja;
                } elseif ($usuario && $usuario->nome) {
                    $nomeLoja = $usuario->nome;
                }
                ?>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2"><?= Html::encode($nomeLoja) ?></h1>
                <p class="text-sm text-gray-600">COMPROVANTE DE VENDA</p>
            </div>

            <!-- Informações da Venda -->
            <div class="space-y-3 mb-6">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Venda #<?= substr($venda->id, 0, 8) ?></span>
                    <span class="text-sm font-semibold text-gray-900">
                        <?= date('d/m/Y H:i', strtotime($venda->data_venda)) ?>
                    </span>
                </div>
                
                <?php if ($venda->cliente): ?>
                <div class="border-t border-gray-200 pt-3">
                    <p class="text-sm text-gray-600 mb-1">Cliente:</p>
                    <p class="text-base font-semibold text-gray-900">
                        <?= Html::encode($venda->cliente->nome_completo ?? $venda->cliente->nome ?? 'N/A') ?>
                    </p>
                    <?php if ($venda->cliente->cpf): ?>
                        <p class="text-sm text-gray-600">CPF: <?= Html::encode($venda->cliente->cpf) ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Itens -->
            <div class="border-t border-b border-gray-300 py-4 mb-4">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Itens</h2>
                <div class="space-y-2">
                    <?php foreach ($venda->itens as $item): 
                        $produto = $item->produto;
                    ?>
                        <div class="flex justify-between items-start py-2 border-b border-gray-100">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">
                                    <?= Html::encode($produto ? $produto->nome : 'Produto não encontrado') ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <?= $item->quantidade ?> x R$ <?= number_format($item->preco_unitario_venda, 2, ',', '.') ?>
                                </p>
                            </div>
                            <p class="font-semibold text-gray-900 ml-4">
                                R$ <?= number_format($item->valor_total_item ?? ($item->quantidade * $item->preco_unitario_venda), 2, ',', '.') ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Totais e Forma de Pagamento -->
            <div class="space-y-3 mb-6">
                <div class="flex justify-between items-center text-lg font-bold text-gray-900 border-t border-gray-300 pt-3">
                    <span>Total:</span>
                    <span>R$ <?= number_format($venda->valor_total, 2, ',', '.') ?></span>
                </div>
                
                <?php if ($venda->formaPagamento): ?>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Forma de Pagamento:</span>
                    <span class="text-sm font-semibold text-gray-900">
                        <?= Html::encode($venda->formaPagamento->nome) ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <?php if ($venda->numero_parcelas > 1): ?>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Parcelas:</span>
                    <span class="text-sm font-semibold text-gray-900">
                        <?= $venda->numero_parcelas ?>x
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Observações -->
            <?php if ($venda->observacoes): ?>
            <div class="border-t border-gray-300 pt-4 mb-4">
                <p class="text-sm text-gray-600 mb-1">Observações:</p>
                <p class="text-sm text-gray-900"><?= Html::encode($venda->observacoes) ?></p>
            </div>
            <?php endif; ?>

            <!-- Rodapé -->
            <div class="border-t border-gray-300 pt-4 text-center">
                <p class="text-xs text-gray-500">
                    Pagamento confirmado em <?= date('d/m/Y H:i') ?>
                </p>
                <p class="text-xs text-gray-500 mt-2">
                    Este comprovante foi gerado automaticamente após a confirmação do pagamento.
                </p>
            </div>
        </div>
    </div>
</div>

