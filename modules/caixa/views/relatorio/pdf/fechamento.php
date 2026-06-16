<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Fechamento de Caixa</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4472C4;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            color: #4472C4;
            font-size: 24px;
        }

        .info-box {
            background-color: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background-color: #4472C4;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .entrada {
            color: #28a745;
            font-weight: bold;
        }

        .saida {
            color: #dc3545;
            font-weight: bold;
        }

        .total-row {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 14px;
        }

        .summary {
            margin-top: 30px;
            padding: 15px;
            background-color: #e8f4f8;
            border-radius: 5px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .summary-total {
            font-size: 18px;
            font-weight: bold;
            color: #4472C4;
            border-top: 2px solid #4472C4;
            padding-top: 10px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Fechamento de Caixa</h1>
        <p>Data: <?= date('d/m/Y H:i') ?></p>
    </div>

    <div class="info-box">
        <h3 style="margin-top: 0;">Informações do Caixa</h3>
        <div class="info-row">
            <span class="info-label">Data Abertura:</span>
            <span><?= Yii::$app->formatter->asDatetime($caixa->data_abertura) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Data Fechamento:</span>
            <span><?= $caixa->data_fechamento ? Yii::$app->formatter->asDatetime($caixa->data_fechamento) : 'Em aberto' ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Saldo Inicial:</span>
            <span><?= Yii::$app->formatter->asCurrency($caixa->saldo_inicial) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span><?= $caixa->status ?></span>
        </div>
    </div>

    <h3>Movimentações</h3>
    <table>
        <thead>
            <tr>
                <th>Data/Hora</th>
                <th>Tipo</th>
                <th>Categoria</th>
                <th>Descrição</th>
                <th style="text-align: right;">Valor</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalEntradas = 0;
            $totalSaidas = 0;
            foreach ($movimentacoes as $mov):
                if ($mov->tipo_movimentacao === 'ENTRADA') {
                    $totalEntradas += $mov->valor;
                } else {
                    $totalSaidas += $mov->valor;
                }
            ?>
                <tr>
                    <td><?= Yii::$app->formatter->asDatetime($mov->data_movimentacao) ?></td>
                    <td><?= $mov->tipo_movimentacao ?></td>
                    <td><?= $mov->categoria ?? 'N/A' ?></td>
                    <td><?= $mov->descricao ?></td>
                    <td style="text-align: right;" class="<?= $mov->tipo_movimentacao === 'ENTRADA' ? 'entrada' : 'saida' ?>">
                        <?= Yii::$app->formatter->asCurrency($mov->valor) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-item">
            <span>Total Entradas:</span>
            <span class="entrada"><?= Yii::$app->formatter->asCurrency($totalEntradas) ?></span>
        </div>
        <div class="summary-item">
            <span>Total Saídas:</span>
            <span class="saida"><?= Yii::$app->formatter->asCurrency($totalSaidas) ?></span>
        </div>
        <div class="summary-item summary-total">
            <span>Saldo Final:</span>
            <span><?= Yii::$app->formatter->asCurrency($caixa->saldo_final ?? ($caixa->saldo_inicial + $totalEntradas - $totalSaidas)) ?></span>
        </div>
    </div>
</body>

</html>