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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Voltar
            </a>
            <div class="flex gap-3">
                <button onclick="window.print()"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Imprimir
                </button>

                <?php $ultimoCupom = $venda->ultimoCupomFiscal; ?>
                <?php if (!$ultimoCupom): ?>
                    <a href="<?= Url::to(['emitir-fiscal', 'id' => $venda->id]) ?>"
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Emitir Cupom Fiscal
                    </a>
                <?php endif; ?>

                <button onclick="imprimirTermica()"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Imp. Térmica
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
                    <?php
                    $subtotal = 0;
                    $totalDescontos = 0;

                    foreach ($venda->itens as $item):
                        $produto = $item->produto;
                        $valorItem = $item->quantidade * $item->preco_unitario_venda;
                        $subtotal += $valorItem;
                        $totalDescontos += $item->desconto_valor ?? 0;
                    ?>
                        <div class="flex justify-between items-start py-2 border-b border-gray-100">
                            <div class="flex-1">
                                <p class="text-lg font-bold text-gray-900">
                                    <?= Html::encode($produto ? $produto->nome : 'Produto não encontrado') ?>
                                </p>
                                <p class="text-base text-gray-700">
                                    <?= $item->quantidade ?> x R$ <?= number_format($item->preco_unitario_venda, 2, ',', '.') ?>
                                </p>
                                <?php if ($item->desconto_valor > 0): ?>
                                    <p class="text-sm text-red-600">
                                        Desconto: -R$ <?= number_format($item->desconto_valor, 2, ',', '.') ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <p class="text-base font-bold text-gray-900 ml-4">
                                R$ <?= number_format($item->valor_total_item ?? ($valorItem - ($item->desconto_valor ?? 0)), 2, ',', '.') ?>
                            </p>
                        </div>
                    <?php endforeach;

                    $acrescimo = $venda->acrescimo_valor ?? 0;
                    $totalFinal = $subtotal - $totalDescontos + $acrescimo;
                    ?>
                </div>
            </div>

            <!-- Totais e Forma de Pagamento -->
            <div class="space-y-2 mb-6">
                <!-- Subtotal (só mostra se houver descontos ou acréscimos) -->
                <?php if ($totalDescontos > 0 || $acrescimo > 0): ?>
                    <div class="flex justify-between items-center text-gray-600">
                        <span>Subtotal:</span>
                        <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                    </div>
                <?php endif; ?>

                <!-- Descontos -->
                <?php if ($totalDescontos > 0): ?>
                    <div class="flex justify-between items-center text-red-600">
                        <span>Descontos:</span>
                        <span>-R$ <?= number_format($totalDescontos, 2, ',', '.') ?></span>
                    </div>
                <?php endif; ?>

                <!-- Acréscimos -->
                <?php if ($acrescimo > 0): ?>
                    <div class="flex justify-between items-center text-gray-600">
                        <span>Acréscimos:</span>
                        <span>+R$ <?= number_format($acrescimo, 2, ',', '.') ?></span>
                    </div>
                <?php endif; ?>

                <div class="flex justify-between items-center text-xl font-bold text-gray-900 border-t border-gray-300 pt-3 mt-2">
                    <span>Total:</span>
                    <span>R$ <?= number_format($totalFinal, 2, ',', '.') ?></span>
                </div>

                <?php if ($venda->formaPagamento): ?>
                    <div class="flex justify-between items-center pt-2">
                        <span class="text-sm text-gray-600">Forma de Pagamento:</span>
                        <span class="text-base font-semibold text-gray-900">
                            <?= Html::encode($venda->formaPagamento->nome) ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ($venda->numero_parcelas > 1): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Parcelas:</span>
                        <span class="text-base font-semibold text-gray-900">
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

            <?php if ($venda->observacao_acrescimo): ?>
                <div class="border-t border-gray-300 pt-4 mb-4">
                    <p class="text-sm text-gray-600 mb-1">Motivo do Acréscimo:</p>
                    <p class="text-sm text-gray-900"><?= Html::encode($venda->observacao_acrescimo) ?></p>
                </div>
            <?php endif; ?>

            <!-- Informação Fiscal -->
            <?php if ($ultimoCupom): ?>
                <div class="border-t border-gray-300 pt-4 mb-4 bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-sm font-bold text-gray-700 mb-2 uppercase tracking-wider">Informação Fiscal (NFCe)</h3>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-gray-500">Status:</span>
                            <span class="font-semibold <?= $ultimoCupom->status == 'AUTORIZADA' ? 'text-green-600' : 'text-red-600' ?>">
                                <?= Html::encode($ultimoCupom->status) ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">Chave:</span>
                            <span class="font-mono"><?= Html::encode($ultimoCupom->chave_acesso) ?></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Data Emissão:</span>
                            <span><?= date('d/m/Y H:i', strtotime($ultimoCupom->data_emissao)) ?></span>
                        </div>
                        <?php if ($ultimoCupom->xml_path): ?>
                            <div class="col-span-2 mt-2">
                                <a href="<?= Url::to(['/../' . $ultimoCupom->xml_path]) ?>" target="_blank" class="text-blue-600 hover:underline">Baixar XML</a>
                            </div>
                        <?php endif; ?>
                    </div>
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

<script>
    function imprimirTermica() {
        const largura = 32;
        const linhaSeparadora = '-'.repeat(largura);

        const removerAcentos = (str) => {
            if (!str) return '';
            return str.toString().normalize('NFD').replace(/[\u0300-\u036f]/g, "");
        };

        const center = (str) => {
            str = removerAcentos(str);
            const spaces = Math.max(0, Math.floor((largura - str.length) / 2));
            return ' '.repeat(spaces) + str;
        };

        const row = (left, right) => {
            left = removerAcentos(left);
            right = removerAcentos(right);
            const lLen = left.length;
            const rLen = right.length;
            const spaces = Math.max(1, largura - lLen - rLen);
            return left + ' '.repeat(spaces) + right;
        };

        let texto = '';
        texto += center("<?= Html::encode($nomeLoja) ?>").toUpperCase() + '\n';
        <?php if ($configuracao && ($configuracao->cnpj || $configuracao->cpf)): ?>
            texto += center("<?= $configuracao->cnpj ?: $configuracao->cpf ?>") + '\n';
        <?php endif; ?>
        texto += linhaSeparadora + '\n';

        texto += "VENDA #<?= substr($venda->id, 0, 8) ?>\n";
        texto += "DATA: <?= date('d/m/Y H:i', strtotime($venda->data_venda)) ?>\n";
        texto += linhaSeparadora + '\n';

        <?php foreach ($venda->itens as $item): ?>
            texto += removerAcentos("<?= $item->produto ? Html::encode($item->produto->nome) : 'PRODUTO' ?>").substring(0, largura).toUpperCase() + '\n';
            texto += row("<?= $item->quantidade ?>x <?= number_format($item->preco_unitario_venda, 2, '.', '') ?>", "R$ <?= number_format($item->valor_total_item ?? ($item->quantidade * $item->preco_unitario_venda - ($item->desconto_valor ?? 0)), 2, '.', '') ?>") + '\n';
            <?php if ($item->desconto_valor > 0): ?>
                texto += row("  DESCONTO ITEM", "-R$ <?= number_format($item->desconto_valor, 2, '.', '') ?>") + '\n';
            <?php endif; ?>
        <?php endforeach; ?>

        texto += linhaSeparadora + '\n';

        <?php if ($totalDescontos > 0 || $acrescimo > 0): ?>
            texto += row("SUBTOTAL", "R$ <?= number_format($subtotal, 2, '.', '') ?>") + '\n';
        <?php endif; ?>

        <?php if ($totalDescontos > 0): ?>
            texto += row("DESCONTOS", "-R$ <?= number_format($totalDescontos, 2, '.', '') ?>") + '\n';
        <?php endif; ?>

        <?php if ($acrescimo > 0): ?>
            texto += row("ACRESCIMOS", "+R$ <?= number_format($acrescimo, 2, '.', '') ?>") + '\n';
            <?php if ($venda->observacao_acrescimo): ?>
                texto += removerAcentos("Obs: <?= Html::encode($venda->observacao_acrescimo) ?>").substring(0, largura) + '\n';
            <?php endif; ?>
        <?php endif; ?>

        texto += row("TOTAL", "R$ <?= number_format($totalFinal, 2, '.', '') ?>") + '\n';

        texto += row("PAGAMENTO", "<?= $venda->formaPagamento ? Html::encode($venda->formaPagamento->nome) : 'DINHEIRO' ?>").toUpperCase() + '\n';

        texto += '\n\n' + center("OBRIGADO PELA PREFERENCIA!") + '\n\n\n';

        const encodedText = encodeURIComponent(texto);

        // Tenta obter URL absoluta do logo
        let logoUrl = '';
        <?php if ($configuracao && $configuracao->logo_path): ?>
            logoUrl = "<?= Url::to(['@web/' . $configuracao->logo_path], true) ?>";
        <?php endif; ?>

        let urlLogoParam = logoUrl ? '&logo=' + encodeURIComponent(logoUrl) : '';

        const deepLink = `printapp://print?data=${encodedText}${urlLogoParam}`;
        console.log('[Print] Abrindo Deep Link:', deepLink);
        window.location.href = deepLink;
    }
</script>