<?php

use app\assets\ChartJsAsset;
use yii\helpers\Html;
use yii\helpers\Json;

// Registra o asset do Chart.js para que o JS esteja disponível na página
ChartJsAsset::register($this);

$this->title = 'Dashboard de Indicadores da Atenção Primária à Saúde (APS)';

/**
 * Função auxiliar para determinar a cor do card com base no desempenho.
 * Usa as faixas de avaliação (crítico, alerta, satisfatório).
 */
function getPerformanceColor($valor, $atributosQualidade, $polaridade) {
    if (empty($atributosQualidade)) {
        return 'bg-info'; // Azul (sem dados de performance para avaliar)
    }

    $valor = (float) $valor;
    $faixaSatisfatoriaInf = (float) ($atributosQualidade['faixa_satisfatoria_inferior'] ?? null);
    $faixaSatisfatoriaSup = (float) ($atributosQualidade['faixa_satisfatoria_superior'] ?? null);
    $faixaAlertaInf = (float) ($atributosQualidade['faixa_alerta_inferior'] ?? null);
    $faixaAlertaSup = (float) ($atributosQualidade['faixa_alerta_superior'] ?? null);
    $faixaCriticaInf = (float) ($atributosQualidade['faixa_critica_inferior'] ?? null);
    $faixaCriticaSup = (float) ($atributosQualidade['faixa_critica_superior'] ?? null);

    // Lógica para polaridade "Dentro da Faixa é Melhor"
    if ($polaridade === 'DENTRO_DA_FAIXA_MELHOR') {
        if ($valor >= $faixaSatisfatoriaInf && $valor <= $faixaSatisfatoriaSup) return 'bg-success';
        if ($valor >= $faixaAlertaInf && $valor <= $faixaAlertaSup) return 'bg-warning';
        return 'bg-danger';
    }

    // Lógica para polaridades "Maior é Melhor" e "Menor é Melhor"
    if ($valor >= $faixaSatisfatoriaInf) return 'bg-success'; // Verde (Ótimo/Bom)
    if ($valor >= $faixaAlertaInf) return 'bg-warning';     // Amarelo (Suficiente)
    
    return 'bg-danger'; // Vermelho (Regular/Crítico)
}
?>

<div class="dashboard-index">
    <h1><?= Html::encode($this->title) ?></h1>
    <p class="lead">Visão geral do desempenho nacional com base nos dados de referência mais recentes.</p>
    
    <hr>

    <h3>Desempenho Atual dos Indicadores</h3>
    <div class="row">
        <?php if (!empty($dadosIndicadores)): ?>
            <?php foreach ($dadosIndicadores as $codigo => $data): ?>
                <?php if (empty($data['ultimoValor'])) continue; // Pula se não houver dados

                // Extrai as variáveis para facilitar a leitura
                $definicao = $data['definicao'];
                $ultimoValor = $data['ultimoValor'];
                $atributos = $data['atributos_qualidade'] ?? [];
                
                $valor = $ultimoValor['valor'];
                $nomeIndicador = Html::encode($definicao['nome_indicador']);
                $unidade = Html::encode($definicao['unidade_medida']['sigla_unidade'] ?? '');
                $dataReferencia = Yii::$app->formatter->asDate($ultimoValor['data_referencia'], 'MMM/yyyy');
                
                // Determina a cor do card
                $corCard = getPerformanceColor($valor, $atributos, $definicao['polaridade']);
                $metodoPontuacao = Html::encode($atributos['metodo_pontuacao'] ?? 'Meta não definida.');
                ?>

                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card text-white <?= $corCard ?> shadow-sm h-100">
                        <div class="card-header" title="<?= $nomeIndicador ?>">
                            <small><b><?= Html::encode($codigo) ?></b></small>
                        </div>
                        <div class="card-body">
                            <h2 class="card-title display-4"><?= number_format($valor, 2) ?><small class="h5 align-top ml-1"><?= $unidade ?></small></h2>
                            <p class="card-text mb-0" style="font-size: 0.9em;">
                                <?= $nomeIndicador ?>
                            </p>
                        </div>
                        <div class="card-footer" data-toggle="tooltip" data-placement="top" title="<?= $metodoPontuacao ?>">
                            <small>Referência: <?= $dataReferencia ?></small>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning" role="alert">
                    Nenhum dado de indicador foi encontrado para exibição.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <hr class="my-4">

    <h3>Evolução Histórica dos Indicadores</h3>
    <div class="row">
        <?php if (!empty($dadosIndicadores)): ?>
            <?php foreach ($dadosIndicadores as $codigo => $data): ?>
                <?php if (empty($data['historicoChart']['values'])) continue; // Pula se não houver histórico

                $nomeIndicador = Html::encode($data['definicao']['nome_indicador']);
                $unidade = Html::encode($data['definicao']['unidade_medida']['sigla_unidade'] ?? '');
                $jsChartData = Json::encode($data['historicoChart']);
                $canvasId = 'chart-' . str_replace('_', '-', $codigo); // ID único para o canvas
                ?>

                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?= $nomeIndicador ?></h5>
                             <canvas id="<?= $canvasId ?>"></canvas>
                        </div>
                    </div>
                </div>
                
                <?php
                // Registra o script do Chart.js para CADA gráfico, dentro do loop
                $this->registerJs(<<<JS
                    (function() {
                        var ctx = document.getElementById('$canvasId').getContext('2d');
                        var chartData = $jsChartData;

                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: chartData.labels,
                                datasets: [{
                                    label: 'Valor ($unidade)',
                                    data: chartData.values,
                                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                    borderColor: 'rgba(54, 162, 235, 1)',
                                    borderWidth: 2,
                                    pointBackgroundColor: 'rgba(54, 162, 235, 1)'
                                }]
                            },
                            options: {
                                scales: {
                                    y: { // yAxes no Chart.js v3+ é 'y'
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value, index, values) {
                                                return value + '$unidade';
                                            }
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: false // O título do card já informa o que é o gráfico
                                    }
                                }
                            }
                        });
                    })();
                JS
                , \yii\web\View::POS_READY, 'chart-script-' . $codigo); // Key única para o script
                ?>

            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>