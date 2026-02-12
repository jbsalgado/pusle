<?php

/**
 * Partial: Tabelas de Contas a Pagar e Receber
 * @var array $contasPagar
 * @var array $parcelasReceber
 */

use yii\helpers\Html;
use yii\helpers\Url;
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Próximas Contas a Pagar -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">📋 Próximas Contas a Pagar</h3>
            <a href="<?= Url::to(['/contas-pagar/conta-pagar/index']) ?>" class="text-sm text-blue-600 hover:text-blue-800">Ver todas →</a>
        </div>

        <?php if (empty($contasPagar)): ?>
            <p class="text-gray-400 text-center py-8 italic">Nenhuma conta pendente</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($contasPagar as $conta): ?>
                    <?php
                    $dias = (strtotime($conta->data_vencimento) - time()) / (60 * 60 * 24);
                    $corStatus = $dias < 0 ? 'red' : ($dias <= 3 ? 'yellow' : 'green');
                    ?>
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900"><?= Html::encode($conta->descricao) ?></p>
                            <p class="text-xs text-gray-500">
                                <?= Yii::$app->formatter->asDate($conta->data_vencimento) ?>
                                <?php if ($dias < 0): ?>
                                    <span class="text-red-600 font-semibold">(Vencida há <?= abs(floor($dias)) ?> dias)</span>
                                <?php elseif ($dias <= 3): ?>
                                    <span class="text-yellow-600 font-semibold">(Vence em <?= floor($dias) ?> dias)</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-<?= $corStatus ?>-600">R$ <?= number_format($conta->valor, 2, ',', '.') ?></p>
                            <a href="<?= Url::to(['/contas-pagar/conta-pagar/view', 'id' => $conta->id]) ?>" class="text-xs text-blue-600 hover:underline">Ver</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Próximas Parcelas a Receber -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">💰 Próximas Parcelas a Receber</h3>
            <a href="<?= Url::to(['/vendas/parcela/index']) ?>" class="text-sm text-blue-600 hover:text-blue-800">Ver todas →</a>
        </div>

        <?php if (empty($parcelasReceber)): ?>
            <p class="text-gray-400 text-center py-8 italic">Nenhuma parcela pendente</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($parcelasReceber as $parcela): ?>
                    <?php
                    $dias = (strtotime($parcela->data_vencimento) - time()) / (60 * 60 * 24);
                    $corStatus = $dias < 0 ? 'red' : ($dias <= 3 ? 'yellow' : 'green');
                    ?>
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">
                                <?= Html::encode($parcela->venda->cliente->nome ?? 'Cliente não informado') ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                Parcela <?= $parcela->numero_parcela ?> - <?= Yii::$app->formatter->asDate($parcela->data_vencimento) ?>
                                <?php if ($dias < 0): ?>
                                    <span class="text-red-600 font-semibold">(Atrasada)</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-<?= $corStatus ?>-600">R$ <?= number_format($parcela->valor_parcela, 2, ',', '.') ?></p>
                            <a href="<?= Url::to(['/vendas/venda/view', 'id' => $parcela->venda_id]) ?>" class="text-xs text-blue-600 hover:underline">Ver venda</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>