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
            color: #1e40af;
            font-size: 20px;
            margin-bottom: 5px;
        }

        h2 {
            color: #374151;
            font-size: 14px;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 10px;
        }

        .info {
            margin-bottom: 15px;
        }

        .info strong {
            color: #374151;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #f3f4f6;
            color: #374151;
            padding: 8px;
            text-align: left;
            border: 1px solid #d1d5db;
            font-size: 11px;
        }

        td {
            padding: 6px 8px;
            border: 1px solid #d1d5db;
            font-size: 11px;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .total {
            background-color: #dbeafe;
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }

        .alert {
            background-color: #dbeafe;
            border-left: 4px solid #1e40af;
            padding: 10px;
            margin: 15px 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Relatório de Contas a Vencer</h1>
        <p>Próximos <?= $dias ?> dias</p>
        <p style="font-size: 10px; color: #6b7280;">Gerado em: <?= date('d/m/Y H:i') ?></p>
    </div>

    <div class="alert">
        <strong>Total a Vencer:</strong> <?= Yii::$app->formatter->asCurrency(array_sum(array_column($contas, 'valor'))) ?><br>
        <strong>Quantidade de Contas:</strong> <?= count($contas) ?>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 35%;">Descrição</th>
                <th style="width: 25%;">Fornecedor</th>
                <th style="width: 15%; text-align: right;">Valor</th>
                <th style="width: 15%; text-align: center;">Vencimento</th>
                <th style="width: 10%; text-align: center;">Dias</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contas as $conta): ?>
                <?php
                $hoje = new DateTime();
                $vencimento = new DateTime($conta->data_vencimento);
                $diff = $hoje->diff($vencimento);
                $diasRestantes = $diff->days;
                ?>
                <tr>
                    <td><?= htmlspecialchars($conta->descricao) ?></td>
                    <td><?= $conta->fornecedor ? htmlspecialchars($conta->fornecedor->nome) : '-' ?></td>
                    <td style="text-align: right;"><?= Yii::$app->formatter->asCurrency($conta->valor) ?></td>
                    <td style="text-align: center;"><?= Yii::$app->formatter->asDate($conta->data_vencimento) ?></td>
                    <td style="text-align: center;"><?= $diasRestantes ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td colspan="2" style="text-align: right;"><strong>TOTAL:</strong></td>
                <td style="text-align: right;"><strong><?= Yii::$app->formatter->asCurrency(array_sum(array_column($contas, 'valor'))) ?></strong></td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Sistema de Gestão - Contas a Pagar</p>
    </div>
</body>

</html>