<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

/**
 * View: Histórico de Cobranças
 * 
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $stats
 * @var array $filtros
 */

$this->title = 'Histórico de Cobranças';
$this->params['breadcrumbs'][] = ['label' => 'Cobranças', 'url' => ['/cobranca/configuracao/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="cobranca-historico-index">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="text-muted">Acompanhe todas as mensagens enviadas</p>
    </div>

    <!-- Estatísticas -->
    <div class="row" style="margin-bottom: 20px;">
        <div class="col-md-3">
            <div class="panel panel-default">
                <div class="panel-body text-center">
                    <h3 class="text-primary"><?= $stats['total'] ?></h3>
                    <p class="text-muted">Total de Envios</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="panel panel-success">
                <div class="panel-body text-center">
                    <h3 class="text-success"><?= $stats['enviadas'] ?></h3>
                    <p class="text-muted">Enviadas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="panel panel-danger">
                <div class="panel-body text-center">
                    <h3 class="text-danger"><?= $stats['falhas'] ?></h3>
                    <p class="text-muted">Falhas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="panel panel-warning">
                <div class="panel-body text-center">
                    <h3 class="text-warning"><?= $stats['pendentes'] ?></h3>
                    <p class="text-muted">Pendentes</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Filtros</h3>
        </div>
        <div class="panel-body">
            <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['index']]); ?>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Tipo</label>
                        <?= Html::dropDownList('tipo', $filtros['tipo'], [
                            '' => 'Todos',
                            'ANTES' => '3 Dias Antes',
                            'DIA' => 'Dia do Vencimento',
                            'APOS' => 'Após Vencimento',
                        ], ['class' => 'form-control']) ?>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Status</label>
                        <?= Html::dropDownList('status', $filtros['status'], [
                            '' => 'Todos',
                            'ENVIADO' => 'Enviado',
                            'FALHA' => 'Falha',
                            'PENDENTE' => 'Pendente',
                        ], ['class' => 'form-control']) ?>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>Data Início</label>
                        <?= Html::input('date', 'data_inicio', $filtros['data_inicio'], ['class' => 'form-control']) ?>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>Data Fim</label>
                        <?= Html::input('date', 'data_fim', $filtros['data_fim'], ['class' => 'form-control']) ?>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label><br>
                        <?= Html::submitButton('<i class="fa fa-filter"></i> Filtrar', ['class' => 'btn btn-primary']) ?>
                        <?= Html::a('Limpar', ['index'], ['class' => 'btn btn-default']) ?>
                    </div>
                </div>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <!-- Grid -->
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped table-bordered'],
        'columns' => [
            [
                'attribute' => 'data_criacao',
                'label' => 'Data',
                'format' => 'datetime',
                'headerOptions' => ['style' => 'width: 150px'],
            ],
            [
                'attribute' => 'tipo',
                'label' => 'Tipo',
                'value' => function ($model) {
                    $tipos = [
                        'ANTES' => '<span class="label label-info">3 Dias Antes</span>',
                        'DIA' => '<span class="label label-warning">Dia Vencimento</span>',
                        'APOS' => '<span class="label label-danger">Após Vencimento</span>',
                    ];
                    return $tipos[$model->tipo] ?? $model->tipo;
                },
                'format' => 'raw',
                'headerOptions' => ['style' => 'width: 120px'],
            ],
            [
                'attribute' => 'telefone',
                'label' => 'Telefone',
                'headerOptions' => ['style' => 'width: 120px'],
            ],
            [
                'attribute' => 'mensagem',
                'label' => 'Mensagem',
                'value' => function ($model) {
                    return mb_substr($model->mensagem, 0, 80) . (mb_strlen($model->mensagem) > 80 ? '...' : '');
                },
            ],
            [
                'attribute' => 'status',
                'label' => 'Status',
                'value' => function ($model) {
                    $cores = [
                        'ENVIADO' => 'success',
                        'FALHA' => 'danger',
                        'PENDENTE' => 'warning',
                    ];
                    $cor = $cores[$model->status] ?? 'default';
                    return '<span class="label label-' . $cor . '">' . $model->getStatusNome() . '</span>';
                },
                'format' => 'raw',
                'headerOptions' => ['style' => 'width: 100px'],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {reenviar}',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a('<i class="fa fa-eye"></i>', ['view', 'id' => $model->id], [
                            'class' => 'btn btn-xs btn-info',
                            'title' => 'Ver Detalhes',
                        ]);
                    },
                    'reenviar' => function ($url, $model) {
                        if ($model->status === 'FALHA') {
                            return Html::button('<i class="fa fa-refresh"></i>', [
                                'class' => 'btn btn-xs btn-warning btn-reenviar',
                                'data-id' => $model->id,
                                'title' => 'Reenviar',
                            ]);
                        }
                        return '';
                    },
                ],
                'headerOptions' => ['style' => 'width: 80px'],
            ],
        ],
    ]); ?>
</div>

<?php
$this->registerJs(
    <<<JS
$('.btn-reenviar').click(function() {
    var btn = $(this);
    var id = btn.data('id');
    
    if (!confirm('Deseja realmente reenviar esta cobrança?')) {
        return;
    }
    
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
    
    $.ajax({
        url: '/cobranca/historico/reenviar',
        method: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erro: ' + data.message);
                btn.prop('disabled', false).html('<i class="fa fa-refresh"></i>');
            }
        },
        error: function() {
            alert('Erro ao reenviar cobrança');
            btn.prop('disabled', false).html('<i class="fa fa-refresh"></i>');
        }
    });
});
JS
);
?>