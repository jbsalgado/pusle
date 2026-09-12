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

    <!-- Extrato de Split Mercado Pago / SaaS -->
    <div class="bg-white p-6 rounded-lg shadow-sm lg:col-span-2 border border-gray-100">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <span class="mr-2">💳</span> Extrato de Split da Plataforma (Mercado Pago)
                </h3>
                <p class="text-xs text-gray-500">Histórico de liquidações automáticas com taxa retida pelo SaaS</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-xs bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full font-bold border border-indigo-200">
                    <?= count($splitsSaaS ?? []) ?> transação(ões) registrada(s)
                </span>
            </div>
        </div>

        <?php if (empty($splitsSaaS)): ?>
            <p class="text-gray-400 text-center py-8 italic">Nenhuma transação com split registrada até o momento.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase font-bold border-b border-gray-100">
                            <th class="py-3 px-4">Data</th>
                            <th class="py-3 px-4">Venda</th>
                            <th class="py-3 px-4">ID Mercado Pago</th>
                            <th class="py-3 px-4 text-right">Bruto</th>
                            <th class="py-3 px-4 text-right">Taxa SaaS</th>
                            <th class="py-3 px-4 text-right">Líquido Loja</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($splitsSaaS as $log): ?>
                            <?php
                            $isRefunded = in_array(strtolower($log->status), ['refunded', 'charged_back']);
                            $badgeClass = $isRefunded ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800';
                            $badgeLabel = $isRefunded ? 'Estornado' : 'Aprovado';
                            ?>
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-3 px-4 text-xs text-gray-600">
                                    <?= date('d/m/Y H:i', strtotime($log->created_at)) ?>
                                </td>
                                <td class="py-3 px-4 font-mono text-xs font-bold text-gray-900">
                                    #<?= substr($log->order_id, 0, 8) ?>
                                </td>
                                <td class="py-3 px-4 font-mono text-xs text-gray-500">
                                    <?= Html::encode($log->mp_payment_id ?: 'N/A') ?>
                                </td>
                                <td class="py-3 px-4 text-right font-semibold text-gray-900 tabular-nums">
                                    R$ <?= number_format($log->total_amount, 2, ',', '.') ?>
                                </td>
                                <td class="py-3 px-4 text-right font-semibold text-rose-600 tabular-nums">
                                    - R$ <?= number_format($log->platform_fee, 2, ',', '.') ?>
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-emerald-600 tabular-nums">
                                    R$ <?= number_format($log->getLiquidoLojista(), 2, ',', '.') ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider <?= $badgeClass ?>">
                                        <?= $badgeLabel ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="<?= Url::to(['/vendas/venda/view', 'id' => $log->order_id]) ?>" class="text-xs text-blue-600 hover:text-blue-800 font-bold hover:underline">
                                        Ver Venda →
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>