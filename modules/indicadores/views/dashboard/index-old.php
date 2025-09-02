<?php

use app\assets\ChartJsAsset;
use yii\helpers\Html;


// Registra o asset do Chart.js para que o JS esteja disponível na página
ChartJsAsset::register($this);

$this->title = 'Dashboard de Desempenho';
?>

<div class="dashboard-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row">
        <?php if (!empty($churnData) && !empty($churnData['ultimoValor'])): ?>
            <?php
                $valor = $churnData['ultimoValor']['valor'];
                $meta = $churnData['meta']['valor_meta_referencia_1'] ?? null;
                $polaridade = $churnData['definicao']['polaridade'];
                $nomeIndicador = $churnData['definicao']['nome_indicador'];
                
                // Lógica de cor baseada na meta e polaridade
                $cor = 'bg-secondary'; // Cor padrão
                if ($meta !== null) {
                    if (($polaridade == 'QUANTO_MENOR_MELHOR' && $valor <= $meta) || ($polaridade == 'QUANTO_MAIOR_MELHOR' && $valor >= $meta)) {
                        $cor = 'bg-success'; // Verde
                    } else {
                        $cor = 'bg-danger'; // Vermelho
                    }
                }
            ?>
            <div class="col-md-4">
                <div class="card text-white <?= $cor ?> mb-3">
                    <div class="card-header"><?= Html::encode($nomeIndicador) ?></div>
                    <div class="card-body">
                        <h2 class="card-title"><?= number_format($valor, 2) ?>%</h2>
                        <p class="card-text">
                            Meta: <?= $meta !== null ? ('≤ ' . $meta . '%') : 'N/A' ?> <br>
                            Referência: <?= Yii::$app->formatter->asDate($churnData['ultimoValor']['data_referencia'], 'MMM/yyyy') ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        </div>

    <hr>

    <div class="row">
        <div class="col-md-12">
            <h3>Evolução da Taxa de Churn</h3>
            <canvas id="churnChart"></canvas>
        </div>
    </div>
</div>

<?php
// Passa os dados do PHP para o JavaScript de forma segura
$jsChartData = json_encode($churnChartData);

$this->registerJs(<<<JS
    var ctx = document.getElementById('churnChart').getContext('2d');
    var chartData = $jsChartData;

    var myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Taxa de Churn (%)',
                data: chartData.values,
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });
JS
);
?>