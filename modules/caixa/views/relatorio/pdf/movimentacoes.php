<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Movimentações de Caixa</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #4472C4;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            color: #4472C4;
            font-size: 22px;
        }

        .period {
            text-align: center;
            font-size: 13px;
            color: #666;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #4472C4;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }

        td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
            font-size: 10px;
        }

        .entrada {
            color: #28a745;
            font-weight: bold;
        }

        .saida {
            color: #dc3545;
            font-weight: bold;
        }

        .summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #e8f4f8;
            border-radius: 5px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .summary-total {
            font-size: 16px;
            font-weight: bold;
            color: #4472C4;
            border-top: 2px solid #4472C4;
            padding-top: 8px;
            margin-top: 8px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Movimentações de Caixa</h1>
        <p>Gerado em: <?= date('d/m/Y H:i') ?></p>
    </div>

    <div class="period">
        <strong>Período:</strong> <?= Yii::$app->formatter->asDate($dataInicio) ?> a <?= Yii::$app->formatter->asDate($dataFim) ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>Data/Hora</th>
                <th>Tipo</th>
                <th>Categoria</th>
                <th>Descrição</th>
                <th>Forma Pgto</th>
                <th style="text-align: right;">Valor</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalVendas = 0;
            $totalAportes = 0;
            $totalEntradas = 0;
            $totalSaidas = 0;
            foreach ($movimentacoes as $mov):
                $val = (float)$mov->valor;
                if ($mov->tipo === 'ENTRADA') {
                    $totalEntradas += $val;
                    if ($mov->isAporteConta()) {
                        $totalAportes += $val;
                    } else {
                        $totalVendas += $val;
                    }
                } else {
                    $totalSaidas += $val;
                }
            ?>
                <tr>
                    <td><?= Yii::$app->formatter->asDatetime($mov->data_movimento, 'php:d/m/Y H:i') ?></td>
                    <td><?= $mov->tipo ?></td>
                    <td><?= $mov->getCategoriaNome() ?></td>
                    <td><?= Html::encode($mov->descricao) ?></td>
                    <td><?= $mov->formaPagamento ? $mov->formaPagamento->nome : 'N/A' ?></td>
                    <td style="text-align: right;" class="<?= $mov->tipo === 'ENTRADA' ? 'entrada' : 'saida' ?>">
                        <?= Yii::$app->formatter->asCurrency($mov->valor) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-item">
            <span>Vendas (Operacional):</span>
            <span class="entrada"><?= Yii::$app->formatter->asCurrency($totalVendas) ?></span>
        </div>
        <?php if ($totalAportes > 0): ?>
            <div class="summary-item">
                <span>Aportes p/ Contas:</span>
                <span><?= Yii::$app->formatter->asCurrency($totalAportes) ?></span>
            </div>
        <?php endif; ?>
        <div class="summary-item">
            <span>Total Geral Entradas:</span>
            <span class="entrada"><?= Yii::$app->formatter->asCurrency($totalEntradas) ?></span>
        </div>
        <div class="summary-item">
            <span>Total Saídas:</span>
            <span class="saida"><?= Yii::$app->formatter->asCurrency($totalSaidas) ?></span>
        </div>
        <div class="summary-item summary-total">
            <span>Saldo do Período:</span>
            <span><?= Yii::$app->formatter->asCurrency($totalEntradas - $totalSaidas) ?></span>
        </div>
    </div>
</body>

</html>