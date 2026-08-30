<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->getMarketplaceNome() . ' - ' . ($model->apelido_conta ?: 'Principal');
?>

<div class="marketplace-config-view">
    <div class="page-header">
        <h1><i class="fa fa-store"></i> <?= Html::encode($this->title) ?></h1>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Detalhes da Integração</h3>
            <div class="box-tools pull-right">
                <?php if (in_array($model->marketplace, ['MERCADO_LIVRE', 'SHOPEE'])): ?>
                    <?= Html::a('<i class="fa fa-key"></i> Conectar / Autorizar OAuth', ['auth', 'id' => $model->id], [
                        'class' => 'btn btn-success btn-sm',
                        'target' => '_blank',
                    ]) ?>
                <?php endif; ?>

                <?= Html::a('<i class="fa fa-edit"></i> Editar', ['update', 'id' => $model->id], [
                    'class' => 'btn btn-primary btn-sm'
                ]) ?>

                <?= Html::a(
                    '<i class="fa fa-' . ($model->ativo ? 'pause' : 'play') . '"></i> ' . ($model->ativo ? 'Desativar' : 'Ativar'),
                    ['toggle', 'id' => $model->id],
                    [
                        'class' => 'btn btn-' . ($model->ativo ? 'warning' : 'success') . ' btn-sm',
                        'data-method' => 'post',
                    ]
                ) ?>

                <?= Html::a('<i class="fa fa-trash"></i> Excluir', ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger btn-sm',
                    'data' => [
                        'confirm' => 'Tem certeza que deseja remover esta configuração?',
                        'method' => 'post',
                    ],
                ]) ?>

                <?= Html::a('<i class="fa fa-arrow-left"></i> Voltar', ['index'], [
                    'class' => 'btn btn-default btn-sm'
                ]) ?>
            </div>
        </div>

        <div class="box-body">
            <?php if (!$model->ativo): ?>
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Integração Inativa!</strong> Esta conta está desativada e não processará pedidos ou estoque.
                </div>
            <?php endif; ?>

            <?php if ($model->token_expira_em && $model->isTokenExpired()): ?>
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-circle"></i>
                    <strong>Token Expirado!</strong> O token de acesso expirou em <?= Yii::$app->formatter->asDatetime($model->token_expira_em) ?>.
                    Clique em <b>"Conectar / Autorizar OAuth"</b> para renovar a autorização da conta.
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <h4><i class="fa fa-info-circle"></i> Identificação</h4>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            [
                                'attribute' => 'marketplace',
                                'value' => $model->getMarketplaceNome(),
                            ],
                            [
                                'attribute' => 'apelido_conta',
                                'value' => $model->apelido_conta ?: 'Conta Padrão',
                            ],
                            [
                                'attribute' => 'seller_id_externo',
                                'value' => $model->seller_id_externo ?: 'Não vinculado (autorize o OAuth para preencher automaticamente)',
                            ],
                            [
                                'attribute' => 'ativo',
                                'format' => 'raw',
                                'value' => $model->ativo
                                    ? '<span class="label label-success"><i class="fa fa-check"></i> Ativo</span>'
                                    : '<span class="label label-danger"><i class="fa fa-times"></i> Inativo</span>',
                            ],
                            [
                                'attribute' => 'data_criacao',
                                'format' => 'datetime',
                            ],
                        ],
                    ]) ?>
                </div>

                <div class="col-md-6">
                    <h4><i class="fa fa-sync"></i> Sincronização & Automação</h4>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            [
                                'attribute' => 'sincronizar_estoque',
                                'format' => 'raw',
                                'value' => $model->sincronizar_estoque
                                    ? '<span class="label label-success"><i class="fa fa-check"></i> Ativo (Fila Assíncrona)</span>'
                                    : '<span class="label label-default"><i class="fa fa-times"></i> Desativado</span>',
                            ],
                            [
                                'attribute' => 'sincronizar_pedidos',
                                'format' => 'raw',
                                'value' => $model->sincronizar_pedidos
                                    ? '<span class="label label-success"><i class="fa fa-check"></i> Ativo (Webhooks)</span>'
                                    : '<span class="label label-default"><i class="fa fa-times"></i> Desativado</span>',
                            ],
                            [
                                'attribute' => 'token_expira_em',
                                'format' => 'raw',
                                'value' => $model->token_expira_em
                                    ? Yii::$app->formatter->asDatetime($model->token_expira_em) . ' (' . ($model->isTokenExpired() ? '<span class="text-danger">Expirado</span>' : '<span class="text-success">Válido</span>') . ')'
                                    : '<span class="text-muted">Token estático ou não gerado</span>',
                            ],
                            [
                                'attribute' => 'ultima_sync',
                                'format' => 'raw',
                                'value' => $model->ultima_sync
                                    ? Yii::$app->formatter->asDatetime($model->ultima_sync)
                                    : '<span class="text-muted">Nenhuma sincronização recente</span>',
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="box-footer">
            <div class="btn-group" role="group">
                <?php if (in_array($model->marketplace, ['MERCADO_LIVRE', 'SHOPEE'])): ?>
                    <?= Html::a('<i class="fa fa-key"></i> Conectar / Autorizar OAuth', ['auth', 'id' => $model->id], [
                        'class' => 'btn btn-success',
                        'target' => '_blank',
                    ]) ?>
                <?php endif; ?>

                <?= Html::a('<i class="fa fa-history"></i> Ver Logs de Sincronização', ['/marketplace/sync/logs', 'config_id' => $model->id], [
                    'class' => 'btn btn-default',
                ]) ?>
            </div>
        </div>
    </div>
</div>