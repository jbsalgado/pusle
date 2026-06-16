<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        h1 {
            color: #7c3aed;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #7c3aed;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #ede9fe;
            color: #5b21b6;
            padding: 8px;
            text-align: left;
            border: 1px solid #c4b5fd;
            font-size: 11px;
        }

        td {
            padding: 6px 8px;
            border: 1px solid #d1d5db;
            font-size: 11px;
        }

        tr:nth-child(even) {
            background-color: #faf5ff;
        }

        .total {
            background-color: #ede9fe;
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }

        .badge-paga {
            background-color: #d1fae5;
            color: #065f46;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }

        .badge-pendente {
            background-color: #fef3c7;
            color: #92400e;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }

        .badge-vencida {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Relatório por Fornecedor</h1>
        <?php if ($fornecedorId): ?>
            <?php $fornecedor = \app\modules\vendas\models\Fornecedor::findOne($fornecedorId); ?>
            <p><strong>Fornecedor:</strong> <?= $fornecedor ? htmlspecialchars($fornecedor->nome) : 'N/A' ?></p>
        <?php else: ?>
            <p>Todos os Fornecedores</p>
        <?php endif; ?>
        <p style="font-size: 10px; color: #6b7280;">Gerado em: <?= date('d/m/Y H:i') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 35%;">Descrição</th>
                <th style="width: 15%; text-align: right;">Valor</th>
                <th style="width: 15%; text-align: center;">Vencimento</th>
                <th style="width: 15%; text-align: center;">Pagamento</th>
                <th style="width: 10%; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalGeral = 0;
            $totalPendente = 0;
            $totalPago = 0;
            foreach ($contas as $conta):
                $totalGeral += $conta->valor;
                if ($conta->isPaga()) {
                    $totalPago += $conta->valor;
                } else {
                    $totalPendente += $conta->valor;
                }
            ?>
                <tr>
                    <td><?= htmlspecialchars($conta->descricao) ?></td>
                    <td style="text-align: right;"><?= Yii::$app->formatter->asCurrency($conta->valor) ?></td>
                    <td style="text-align: center;"><?= Yii::$app->formatter->asDate($conta->data_vencimento) ?></td>
                    <td style="text-align: center;"><?= $conta->data_pagamento ? Yii::$app->formatter->asDate($conta->data_pagamento) : '-' ?></td>
                    <td style="text-align: center;">
                        <?php if ($conta->isPaga()): ?>
                            <span class="badge-paga">PAGA</span>
                        <?php elseif ($conta->isVencida()): ?>
                            <span class="badge-vencida">VENCIDA</span>
                        <?php else: ?>
                            <span class="badge-pendente">PENDENTE</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td style="text-align: right;"><strong>TOTAL GERAL:</strong></td>
                <td style="text-align: right;"><strong><?= Yii::$app->formatter->asCurrency($totalGeral) ?></strong></td>
                <td colspan="3"></td>
            </tr>
            <tr>
                <td style="text-align: right;">Total Pendente:</td>
                <td style="text-align: right; color: #d97706;"><?= Yii::$app->formatter->asCurrency($totalPendente) ?></td>
                <td colspan="3"></td>
            </tr>
            <tr>
                <td style="text-align: right;">Total Pago:</td>
                <td style="text-align: right; color: #059669;"><?= Yii::$app->formatter->asCurrency($totalPago) ?></td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Sistema de Gestão - Contas a Pagar</p>
    </div>
</body>

</html>