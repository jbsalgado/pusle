<?php
/** @var yii\web\View $this */
/** @var app\modules\vendas\models\Venda[] $cartoes */
/** @var app\modules\vendas\models\Colaborador[] $vendedores */
/** @var string|null $vendedor_id */
/** @var string $status */

use yii\helpers\Html;
use yii\helpers\Url;

$usuario = Yii::$app->user->identity;
$lojaNome = $usuario->nome_loja ?? $usuario->nome ?? 'CREDIÁRIOS PULSE';
$this->title = 'Impressão de Cartões em Lote';
$modoPapel = Yii::$app->request->get('modo') === 'papel';
?>

<?php if (!$modoPapel): ?>
<div class="space-y-6 no-print">
    <!-- Cabeçalho com Filtros -->
    <div class="bg-slate-950/80 border border-slate-800 p-5 rounded-3xl shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-2xl">🖨️</span>
                <h1 class="text-xl font-black text-white">Impressão de Cartões em Lote</h1>
            </div>
            <p class="text-xs text-slate-400">
                Selecione os filtros e envie múltiplos cartões para impressão gráfica ou em impressora comum.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="<?= Url::to(['/prestanista/cartao/index']) ?>" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 font-bold text-xs rounded-xl border border-slate-700 transition">
                ← Voltar
            </a>
            <?php if (!empty($cartoes)): ?>
                <button onclick="window.print()" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black text-sm rounded-xl shadow-lg transition flex items-center gap-2 active:scale-95">
                    <span>🖨️</span>
                    <span>Imprimir Lote Agora (<?= count($cartoes) ?>)</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filtros de Lote -->
    <form method="get" action="<?= Url::to(['/prestanista/cartao/imprimir-lote']) ?>" class="bg-slate-950 border border-slate-800 p-4 rounded-2xl grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Vendedor Ambulante</label>
            <select name="vendedor_id" class="w-full h-11 px-3 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:border-amber-500 focus:outline-none">
                <option value="">Todos os Vendedores</option>
                <?php foreach ($vendedores as $v): ?>
                    <option value="<?= $v->id ?>" <?= $vendedor_id == $v->id ? 'selected' : '' ?>><?= Html::encode($v->nome) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Status</label>
            <select name="status" class="w-full h-11 px-3 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:border-amber-500 focus:outline-none">
                <option value="ABERTO" <?= $status === 'ABERTO' ? 'selected' : '' ?>>Em Aberto (Na Rua)</option>
                <option value="QUITADO" <?= $status === 'QUITADO' ? 'selected' : '' ?>>Quitados</option>
                <option value="TODOS" <?= $status === 'TODOS' ? 'selected' : '' ?>>Todos</option>
            </select>
        </div>

        <div class="flex items-end">
            <button type="submit" class="w-full h-11 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl border border-slate-700 transition flex items-center justify-center gap-1.5">
                <span>🔍</span> Filtrar Cartões
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<style>
    @media screen {
        .folha-impressao {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            justify-content: center;
            margin-top: 24px;
        }
    }
    @media print {
        body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
        .no-print { display: none !important; }
        .folha-impressao { display: block; }
        .cartao-container {
            page-break-inside: avoid;
            page-break-after: always;
            margin: 0 auto 20px auto;
        }
    }
    .cartao-container {
        width: 100%;
        max-width: 480px;
        background: #fff;
        color: #000;
        border: 2px solid #000;
        padding: 10px;
        font-family: 'Courier New', Courier, monospace;
        font-size: 11px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 4px; margin-bottom: 4px; }
    .header h2 { font-size: 15px; font-weight: 900; text-transform: uppercase; margin: 2px 0; }
    .header p { font-size: 9px; text-transform: uppercase; margin: 0; }
    .meta-grid { display: flex; justify-content: space-between; border-top: 1px solid #000; padding-top: 3px; margin-top: 3px; font-size: 10px; font-weight: bold; }
    .cartao-tabela { width: 100%; border-collapse: collapse; font-size: 10px; margin: 4px 0; }
    .cartao-tabela th, .cartao-tabela td { border: 1px solid #000; padding: 2px 4px; }
    .cartao-tabela th { background: #f3f3f3; font-weight: bold; }
    .grade-tabela th, .grade-tabela td { text-align: center; font-size: 9px; height: 17px; }
    .cliente-box { border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 4px 0; margin: 4px 0; font-size: 10px; line-height: 1.3; }
    .footer { text-align: center; font-size: 8px; margin-top: 4px; }
</style>

<div class="folha-impressao">
    <?php if (empty($cartoes)): ?>
        <div class="no-print text-center py-16 text-slate-500">
            <span class="text-3xl block mb-2">📇</span>
            <p class="text-sm font-bold text-white">Nenhum cartão prestanista encontrado com os filtros selecionados.</p>
            <p class="text-xs text-slate-400 mt-1">Gere novos cartões pelo menu ou altere os filtros de busca.</p>
        </div>
    <?php else: ?>
        <?php foreach ($cartoes as $cartao): ?>
            <?php
            $cliente = $cartao->cliente;
            $itens = $cartao->itens;
            $parcelas = $cartao->parcelas;
            $totalPago = 0;
            foreach ($parcelas as $p) {
                if ($p->status_parcela_codigo === 'PAGA') {
                    $totalPago += (float)($p->valor_pago ?: $p->valor_parcela);
                }
            }
            $saldoDevedor = max(0, (float)$cartao->valor_total - $totalPago);
            $totalLinhas = max(5, ceil(count($parcelas) / 2));
            ?>
            <div class="cartao-container">
                <div class="header">
                    <p>Nosso prazer é atendê-lo bem</p>
                    <h2><?= Html::encode($lojaNome) ?></h2>
                    <p>CREDIÁRIOS & UTILIDADES</p>
                    <div class="meta-grid">
                        <span>DATA: <?= date('d/m/Y', strtotime($cartao->data_venda)) ?></span>
                        <span>FLS: 01</span>
                        <span>Nº: #<?= str_pad($cartao->id, 5, '0', STR_PAD_LEFT) ?></span>
                    </div>
                </div>

                <!-- Objetos -->
                <table class="cartao-tabela">
                    <thead>
                        <tr>
                            <th>OBJETOS</th>
                            <th style="width: 80px; text-align: right;">VALOR R$</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td><?= Html::encode($item->produto->nome ?? 'Mercadoria') ?> (<?= $item->quantidade ?>x)</td>
                                <td style="text-align: right;">R$ <?= number_format($item->valor_total_item, 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php for ($i = count($itens); $i < 3; $i++): ?>
                            <tr>
                                <td>___________________________</td>
                                <td style="text-align: right;">R$ ________</td>
                            </tr>
                        <?php endfor; ?>
                        <tr style="font-weight: bold;">
                            <td>TOTAL DO CARTÃO:</td>
                            <td style="text-align: right;">R$ <?= number_format($cartao->valor_total, 2, ',', '.') ?></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Cliente -->
                <div class="cliente-box">
                    <div><strong>Sr.(a):</strong> <?= Html::encode($cliente ? ($cliente->nome ?? $cliente->nome_completo) : '—') ?> Nº <?= Html::encode($cliente->numero ?? 'S/N') ?></div>
                    <div><strong>Rua:</strong> <?= Html::encode($cliente ? ($cliente->logradouro ?: $cliente->endereco_logradouro ?: '—') : '—') ?></div>
                    <div><strong>Bairro:</strong> <?= Html::encode($cliente ? ($cliente->bairro ?: $cliente->endereco_bairro ?: '—') : '—') ?> - <?= Html::encode($cliente ? ($cliente->cidade ?: $cliente->endereco_cidade ?: '—') : '—') ?></div>
                    <div><strong>Vendedor:</strong> <?= Html::encode($cartao->vendedor->nome ?? 'Ambulante') ?> | <strong>Tel:</strong> <?= Html::encode($cliente->telefone ?? '—') ?></div>
                    <div style="font-size: 9px; font-weight: bold; margin-top: 3px;">
                        [<?= $cartao->numero_parcelas > 4 ? 'X' : ' ' ?>] SEMANAL &nbsp;&nbsp; [ ] QUINZENAL &nbsp;&nbsp; [<?= $cartao->numero_parcelas <= 4 ? 'X' : ' ' ?>] MENSAL
                    </div>
                </div>

                <!-- Grade de Baixas (Dupla Coluna) -->
                <table class="cartao-tabela grade-tabela">
                    <thead>
                        <tr>
                            <th>DATA</th>
                            <th>DINHEIRO</th>
                            <th>SALDO</th>
                            <th>DATA</th>
                            <th>DINHEIRO</th>
                            <th>SALDO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $saldoCorrente = (float)$cartao->valor_total;
                        for ($i = 0; $i < $totalLinhas; $i++):
                            $p1 = $parcelas[$i] ?? null;
                            $p2 = $parcelas[$i + $totalLinhas] ?? null;
                        ?>
                            <tr>
                                <?php if ($p1): ?>
                                    <?php
                                    $estaPaga1 = $p1->status_parcela_codigo === 'PAGA';
                                    $vp1 = (float)($p1->valor_pago ?: $p1->valor_parcela);
                                    if ($estaPaga1) $saldoCorrente -= $vp1;
                                    ?>
                                    <td><?= $estaPaga1 ? date('d/m/Y', strtotime($p1->data_pagamento ?: $p1->data_vencimento)) : date('d/m/Y', strtotime($p1->data_vencimento)) ?></td>
                                    <td><?= $estaPaga1 ? number_format($vp1, 2, ',', '.') : '-' ?></td>
                                    <td style="font-weight: bold;"><?= number_format(max(0, $saldoCorrente), 2, ',', '.') ?></td>
                                <?php else: ?>
                                    <td></td><td></td><td></td>
                                <?php endif; ?>

                                <?php if ($p2): ?>
                                    <?php
                                    $estaPaga2 = $p2->status_parcela_codigo === 'PAGA';
                                    $vp2 = (float)($p2->valor_pago ?: $p2->valor_parcela);
                                    if ($estaPaga2) $saldoCorrente -= $vp2;
                                    ?>
                                    <td><?= $estaPaga2 ? date('d/m/Y', strtotime($p2->data_pagamento ?: $p2->data_vencimento)) : date('d/m/Y', strtotime($p2->data_vencimento)) ?></td>
                                    <td><?= $estaPaga2 ? number_format($vp2, 2, ',', '.') : '-' ?></td>
                                    <td style="font-weight: bold;"><?= number_format(max(0, $saldoCorrente), 2, ',', '.') ?></td>
                                <?php else: ?>
                                    <td></td><td></td><td></td>
                                <?php endif; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>

                <div class="footer">
                    Agradecemos a sua preferência! Ficha de crediário impressa pelo sistema.
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
