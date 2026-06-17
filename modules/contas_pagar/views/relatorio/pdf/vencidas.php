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
            color: #dc2626;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #dc2626;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 8px;
            text-align: left;
            border: 1px solid #fca5a5;
            font-size: 11px;
        }

        td {
            padding: 6px 8px;
            border: 1px solid #d1d5db;
            font-size: 11px;
        }

        tr:nth-child(even) {
            background-color: #fef2f2;
        }

        .total {
            background-color: #fee2e2;
            font-weight: bold;
        }

        .alert {
            background-color: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 10px;
            margin: 15px 0;
        }

        .critico {
            background-color: #fef2f2;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>⚠️ Relatório de Contas Vencidas</h1>
        <p style="font-size: 10px; color: #6b7280;">Gerado em: <?= date('d/m/Y H:i') ?></p>
    </div>

    <div class="alert">
        <strong>Total em Atraso:</strong> <?= Yii::$app->formatter->asCurrency(array_sum(array_column($contas, 'valor'))) ?><br>
        <strong>Quantidade de Contas:</strong> <?= count($contas) ?>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30%;">Descrição</th>
                <th style="width: 20%;">Fornecedor</th>
                <th style="width: 15%; text-align: right;">Valor</th>
                <th style="width: 15%; text-align: center;">Vencimento</th>
                <th style="width: 10%; text-align: center;">Atraso</th>
                <th style="width: 10%;">Observações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contas as $conta): ?>
                <?php
                $diasAtraso = $conta->getDiasAtraso();
                $critico = $diasAtraso > 30;
                ?>
                <tr <?= $critico ? 'class="critico"' : '' ?>>
                    <td><?= htmlspecialchars($conta->descricao) ?></td>
                    <td><?= $conta->fornecedor ? htmlspecialchars($conta->fornecedor->nome) : '-' ?></td>
                    <td style="text-align: right;"><?= Yii::$app->formatter->asCurrency($conta->valor) ?></td>
                    <td style="text-align: center;"><?= Yii::$app->formatter->asDate($conta->data_vencimento) ?></td>
                    <td style="text-align: center; color: #dc2626; font-weight: bold;"><?= $diasAtraso ?> dia(s)</td>
                    <td style="font-size: 9px;"><?= $critico ? 'CRÍTICO' : '' ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td colspan="2" style="text-align: right;"><strong>TOTAL:</strong></td>
                <td style="text-align: right;"><strong><?= Yii::$app->formatter->asCurrency(array_sum(array_column($contas, 'valor'))) ?></strong></td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Sistema de Gestão - Contas a Pagar</p>
        <p style="color: #dc2626; font-weight: bold;">ATENÇÃO: Contas com mais de 30 dias de atraso marcadas como CRÍTICO</p>
    </div>
</body>

</html>