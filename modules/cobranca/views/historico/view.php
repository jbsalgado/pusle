<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/**
 * View: Detalhes do Histórico
 * 
 * @var yii\web\View $this
 * @var app\modules\cobranca\models\CobrancaHistorico $model
 */

$this->title = 'Detalhes do Envio';
$this->params['breadcrumbs'][] = ['label' => 'Cobranças', 'url' => ['/cobranca/configuracao/index']];
$this->params['breadcrumbs'][] = ['label' => 'Histórico', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="cobranca-historico-view">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Informações do Envio</h3>
                </div>
                <div class="panel-body">
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            [
                                'attribute' => 'tipo',
                                'value' => function ($model) {
                                    $tipos = [
                                        'ANTES' => '3 Dias Antes do Vencimento',
                                        'DIA' => 'Dia do Vencimento',
                                        'APOS' => 'Após Vencimento',
                                    ];
                                    return $tipos[$model->tipo] ?? $model->tipo;
                                },
                            ],
                            [
                                'attribute' => 'status',
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
                            ],
                            'telefone',
                            'tentativas',
                            [
                                'attribute' => 'data_envio',
                                'format' => 'datetime',
                            ],
                            [
                                'attribute' => 'data_criacao',
                                'format' => 'datetime',
                            ],
                        ],
                    ]) ?>
                </div>
            </div>

            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title">Mensagem Enviada</h3>
                </div>
                <div class="panel-body">
                    <div class="well" style="white-space: pre-wrap; font-family: monospace;">
                        <?= Html::encode($model->mensagem) ?>
                    </div>
                </div>
            </div>

            <?php if ($model->resposta_api): ?>
                <div class="panel panel-<?= $model->status === 'ENVIADO' ? 'success' : 'danger' ?>">
                    <div class="panel-heading">
                        <h3 class="panel-title">Resposta da API</h3>
                    </div>
                    <div class="panel-body">
                        <pre style="max-height: 300px; overflow-y: auto;"><?= Html::encode(json_encode(json_decode($model->resposta_api), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">Parcela</h3>
                </div>
                <div class="panel-body">
                    <?php if ($model->parcela): ?>
                        <p><strong>Cliente:</strong> <?= Html::encode($model->parcela->venda->cliente->nome ?? 'N/A') ?></p>
                        <p><strong>Venda:</strong> #<?= $model->parcela->venda_id ?></p>
                        <p><strong>Parcela:</strong> <?= $model->parcela->numero_parcela ?>/<?= $model->parcela->venda->numero_parcelas ?></p>
                        <p><strong>Valor:</strong> R$ <?= number_format($model->parcela->valor_parcela, 2, ',', '.') ?></p>
                        <p><strong>Vencimento:</strong> <?= Yii::$app->formatter->asDate($model->parcela->data_vencimento) ?></p>
                        <p><strong>Status:</strong> <?= $model->parcela->status_parcela_codigo ?></p>

                        <hr>

                        <?= Html::a('Ver Venda', ['/vendas/venda/view', 'id' => $model->parcela->venda_id], ['class' => 'btn btn-sm btn-primary']) ?>
                    <?php else: ?>
                        <p class="text-muted">Parcela não encontrada</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Ações</h3>
                </div>
                <div class="panel-body">
                    <?= Html::a('<i class="fa fa-arrow-left"></i> Voltar', ['index'], ['class' => 'btn btn-default btn-block']) ?>

                    <?php if ($model->status === 'FALHA'): ?>
                        <?= Html::button('<i class="fa fa-refresh"></i> Reenviar', [
                            'class' => 'btn btn-warning btn-block btn-reenviar',
                            'data-id' => $model->id,
                        ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
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
    
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Reenviando...');
    
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
                btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Reenviar');
            }
        },
        error: function() {
            alert('Erro ao reenviar cobrança');
            btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Reenviar');
        }
    });
});
JS
);
?>