<?php
/** @var yii\web\View $this */
/** @var app\modules\vendas\models\Venda $cartao */
/** @var string $formato */

use yii\helpers\Html;

$usuario = Yii::$app->user->identity;
$lojaNome = $usuario->nome_loja ?? $usuario->nome ?? 'CREDIÁRIOS PULSE';
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Imprimir Cartão #<?= $cartao->id ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Courier New', Courier, monospace; }
        body { background: #fff; color: #000; padding: 12px; }
        .cartao-container {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            border: 2px solid #000;
            padding: 10px;
            font-size: 11px;
        }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 6px; }
        .header h1 { font-size: 16px; font-weight: 900; text-transform: uppercase; }
        .header p { font-size: 9px; text-transform: uppercase; }
        .meta-grid { display: flex; justify-content: space-between; border-top: 1px solid #000; padding-top: 4px; margin-top: 4px; font-size: 10px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th, td { border: 1px solid #000; padding: 3px 4px; text-align: left; }
        th { background: #eee; font-weight: bold; }
        .grade-tabela th, .grade-tabela td { text-align: center; font-size: 9px; height: 18px; }
        .cliente-box { border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 6px 0; margin: 6px 0; font-size: 10px; line-height: 1.4; }
        .footer { text-align: center; font-size: 8px; margin-top: 6px; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print" style="text-align: center; margin-bottom: 12px;">
    <button onclick="window.print()" style="padding: 8px 18px; font-weight: bold; background: #000; color: #fff; border: none; border-radius: 6px; cursor: pointer;">
        🖨️ Imprimir Agora
    </button>
</div>

<div class="cartao-container">
    <div class="header">
        <p>Nosso prazer é atendê-lo bem</p>
        <h1><?= Html::encode($lojaNome) ?></h1>
        <p>CREDIÁRIOS & UTILIDADES</p>
        <div class="meta-grid">
            <span>DATA: <?= date('d/m/Y', strtotime($cartao->data_venda)) ?></span>
            <span>FLS: 01</span>
            <span>Nº: #<?= str_pad($cartao->id, 5, '0', STR_PAD_LEFT) ?></span>
        </div>
    </div>

    <!-- Objetos -->
    <table>
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
        <div><strong>Sr.(a):</strong> <?= Html::encode($cliente->nome ?? '—') ?> Nº <?= Html::encode($cliente->numero ?? 'S/N') ?></div>
        <div><strong>Rua:</strong> <?= Html::encode($cliente->endereco ?? '—') ?></div>
        <div><strong>Bairro:</strong> <?= Html::encode($cliente->bairro ?? '—') ?> - <?= Html::encode($cliente->cidade ?? '—') ?></div>
        <div><strong>Vendedor:</strong> <?= Html::encode($cartao->vendedor->nome ?? 'Ambulante') ?> | <strong>Tel:</strong> <?= Html::encode($cliente->telefone ?? '—') ?></div>
        <?php $freqCartao = $cartao->getFrequenciaPrestanista(); ?>
        <div style="font-size: 9px; font-weight: bold; margin-top: 3px;">
            [<?= $freqCartao === 'DIÁRIA' ? 'X' : ' ' ?>] DIÁRIA &nbsp;&nbsp;
            [<?= $freqCartao === 'SEMANAL' ? 'X' : ' ' ?>] SEMANAL &nbsp;&nbsp;
            [<?= $freqCartao === 'QUINZENAL' ? 'X' : ' ' ?>] QUINZENAL &nbsp;&nbsp;
            [<?= $freqCartao === 'MENSAL' ? 'X' : ' ' ?>] MENSAL
        </div>
    </div>

    <!-- Grade de Baixas com 7 Colunas -->
    <table class="grade-tabela">
        <thead>
            <tr>
                <th>DATA PREST.</th>
                <th>VL. PREST.</th>
                <th>VL. COMPRA</th>
                <th>DATA PAG.</th>
                <th>VL. RECEB.</th>
                <th>TIPO</th>
                <th>SALDO</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                $saldo = (float)$cartao->valor_total;
                foreach ($parcelas as $p): 
                    $isPaga = ($p->status_parcela_codigo === 'PAGA');
                    $saldo = max(0, $saldo - (float)$p->valor_parcela);
                    $tipoNome = $isPaga ? ($p->formaPagamento ? ($p->formaPagamento->nome ?: $p->formaPagamento->tipo) : 'DINHEIRO') : '—';
            ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($p->data_vencimento)) ?></td>
                    <td><?= number_format($p->valor_parcela, 2, ',', '.') ?></td>
                    <td><?= number_format($cartao->valor_total, 2, ',', '.') ?></td>
                    <td><?= $isPaga ? date('d/m/Y', strtotime($p->data_pagamento ?: $p->data_vencimento)) : '—' ?></td>
                    <td><?= $isPaga ? number_format($p->valor_pago ?: $p->valor_parcela, 2, ',', '.') : '—' ?></td>
                    <td><?= Html::encode($tipoNome) ?></td>
                    <td><?= number_format($saldo, 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Obs.: Não aceitamos devolução. &nbsp; • &nbsp; « Deus é Fiel » &nbsp; • &nbsp; Devolução paga 20%</p>
    </div>
</div>

<script>
    window.addEventListener('DOMContentLoaded', () => {
        // Auto-print opcional se solicitado via URL
        if (window.location.search.includes('autoprint=1')) {
            window.print();
        }
    });
</script>
</body>
</html>
