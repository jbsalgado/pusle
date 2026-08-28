<?php

use yii\helpers\Html;

$nomeLoja = $loja ? ($loja->nome ?: 'PULSE Food Service') : 'PULSE Food Service';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Imprimir Comprovante — Mesa <?= Html::encode($mesa->numero_mesa) ?></title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 78mm;
            margin: 0 auto;
            padding: 5mm;
            color: #000;
            background: #fff;
            font-size: 12px;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }
        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .title {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            margin: 5px 0;
        }
        .info {
            font-size: 11px;
            margin-bottom: 8px;
        }
        .table-items {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px dashed #000;
            margin-bottom: 8px;
        }
        .table-items th {
            text-align: left;
            border-bottom: 1px solid #000;
            font-size: 10px;
            padding-bottom: 3px;
        }
        .table-items td {
            padding: 3px 0;
            vertical-align: top;
        }
        .obs {
            font-size: 10px;
            font-weight: bold;
            padding-left: 10px;
        }
        .total-box {
            font-size: 15px;
            font-weight: bold;
            text-align: right;
            border-top: 1px dashed #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 15px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 10px; text-align: center;">
        <button onclick="window.print()" style="padding: 8px 16px; font-weight: bold; background: #000; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
            🖨️ IMPRIMIR CUPOM
        </button>
    </div>

    <div class="header">
        <h1><?= Html::encode($nomeLoja) ?></h1>
        <div style="font-size: 10px; font-weight: bold;">FOOD SERVICE & GESTÃO</div>
    </div>

    <div class="title">
        *** CONSUMO DE MESA ***<br>
        MESA <?= Html::encode($mesa->numero_mesa) ?>
    </div>

    <div class="info">
        <div><strong>Cliente:</strong> <?= Html::encode($comanda->cliente_nome ?: 'Cliente') ?></div>
        <div><strong>Comanda:</strong> <?= Html::encode($comanda->numero_comanda) ?></div>
        <div><strong>Data:</strong> <?= date('d/m/Y H:i:s') ?></div>
    </div>

    <table class="table-items">
        <thead>
            <tr>
                <th>QTD ITEM</th>
                <th style="text-align: right;">UNIT</th>
                <th style="text-align: right;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($comanda->itens as $item): ?>
                <?php $prodNome = $item->produto ? $item->produto->nome : 'Produto'; ?>
                <tr>
                    <td><strong><?= (float)$item->quantidade ?>x</strong> <?= Html::encode($prodNome) ?></td>
                    <td style="text-align: right;"><?= number_format($item->valor_unitario, 2, ',', '.') ?></td>
                    <td style="text-align: right; font-weight: bold;"><?= number_format($item->getSubtotal(), 2, ',', '.') ?></td>
                </tr>
                <?php if ($item->observacoes): ?>
                    <tr>
                        <td colspan="3" class="obs">>> <?= Html::encode($item->observacoes) ?></td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-box">
        TOTAL CONSUMO: R$ <?= number_format($comanda->getValorTotal(), 2, ',', '.') ?>
    </div>

    <div class="footer">
        Obrigado pela preferência!<br>
        Documento Não Fiscal
    </div>

</body>
</html>
