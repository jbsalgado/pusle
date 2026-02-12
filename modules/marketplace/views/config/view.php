<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->getMarketplaceNome();
?>

<div class="marketplace-config-view">
    <div class="page-header">
        <h1><i class="fa fa-store"></i> <?= Html::encode($this->title) ?></h1>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Detalhes da Configuração</h3>
            <div class="box-tools pull-right">
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
                    <strong>Marketplace Inativo!</strong> Esta configuração está desativada e não realizará sincronizações.
                </div>
            <?php endif; ?>

            <?php if ($model->token_expira_em && $model->isTokenExpired()): ?>
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-circle"></i>
                    <strong>Token Expirado!</strong> O token de acesso expirou em <?= Yii::$app->formatter->asDatetime($model->token_expira_em) ?>.
                    É necessário renovar as credenciais.
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <h4><i class="fa fa-info-circle"></i> Informações Gerais</h4>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            [
                                'attribute' => 'marketplace',
                                'value' => $model->getMarketplaceNome(),
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
                            [
                                'attribute' => 'data_atualizacao',
                                'format' => 'datetime',
                            ],
                        ],
                    ]) ?>
                </div>

                <div class="col-md-6">
                    <h4><i class="fa fa-key"></i> Credenciais</h4>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            [
                                'attribute' => 'client_id',
                                'value' => $model->client_id ?: 'Não configurado',
                            ],
                            [
                                'attribute' => 'client_secret',
                                'format' => 'raw',
                                'value' => $model->client_secret
                                    ? '<span class="text-muted"><i class="fa fa-lock"></i> ••••••••••••</span>'
                                    : 'Não configurado',
                            ],
                            [
                                'attribute' => 'access_token',
                                'format' => 'raw',
                                'value' => $model->access_token
                                    ? '<span class="text-success"><i class="fa fa-check-circle"></i> Configurado</span>'
                                    : '<span class="text-muted">Não configurado</span>',
                            ],
                            [
                                'attribute' => 'token_expira_em',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    if (!$model->token_expira_em) {
                                        return '<span class="text-muted">N/A</span>';
                                    }

                                    $expired = $model->isTokenExpired();
                                    $class = $expired ? 'danger' : 'success';
                                    $icon = $expired ? 'times-circle' : 'check-circle';
                                    $text = Yii::$app->formatter->asDatetime($model->token_expira_em);

                                    return "<span class=\"text-{$class}\"><i class=\"fa fa-{$icon}\"></i> {$text}</span>";
                                },
                            ],
                        ],
                    ]) ?>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col-md-12">
                    <h4><i class="fa fa-sync"></i> Configurações de Sincronização</h4>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            [
                                'attribute' => 'sincronizar_produtos',
                                'format' => 'raw',
                                'value' => $model->sincronizar_produtos
                                    ? '<span class="label label-success"><i class="fa fa-check"></i> Sim</span>'
                                    : '<span class="label label-default"><i class="fa fa-times"></i> Não</span>',
                            ],
                            [
                                'attribute' => 'sincronizar_estoque',
                                'format' => 'raw',
                                'value' => $model->sincronizar_estoque
                                    ? '<span class="label label-success"><i class="fa fa-check"></i> Sim</span>'
                                    : '<span class="label label-default"><i class="fa fa-times"></i> Não</span>',
                            ],
                            [
                                'attribute' => 'sincronizar_pedidos',
                                'format' => 'raw',
                                'value' => $model->sincronizar_pedidos
                                    ? '<span class="label label-success"><i class="fa fa-check"></i> Sim</span>'
                                    : '<span class="label label-default"><i class="fa fa-times"></i> Não</span>',
                            ],
                            [
                                'attribute' => 'intervalo_sync_minutos',
                                'value' => $model->intervalo_sync_minutos . ' minutos',
                            ],
                            [
                                'attribute' => 'ultima_sync',
                                'format' => 'raw',
                                'value' => $model->ultima_sync
                                    ? Yii::$app->formatter->asDatetime($model->ultima_sync) . ' <span class="text-muted">(' . Yii::$app->formatter->asRelativeTime($model->ultima_sync) . ')</span>'
                                    : '<span class="text-muted">Nunca sincronizado</span>',
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="box-footer">
            <div class="btn-group" role="group">
                <?= Html::a('<i class="fa fa-plug"></i> Testar Conexão', ['test', 'id' => $model->id], [
                    'class' => 'btn btn-info',
                    'data-method' => 'post',
                ]) ?>

                <?= Html::a('<i class="fa fa-sync"></i> Sincronizar Agora', ['/marketplace/sync/run', 'id' => $model->id], [
                    'class' => 'btn btn-primary',
                    'data-method' => 'post',
                    'data-confirm' => 'Deseja iniciar a sincronização agora?',
                ]) ?>

                <?= Html::a('<i class="fa fa-history"></i> Ver Logs', ['/marketplace/sync/logs', 'config_id' => $model->id], [
                    'class' => 'btn btn-default',
                ]) ?>
            </div>
        </div>
    </div>

    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-question-circle"></i> Ajuda</h3>
        </div>
        <div class="box-body">
            <h5><strong>Como obter as credenciais?</strong></h5>
            <p>Para configurar a integração com <?= Html::encode($model->getMarketplaceNome()) ?>, você precisa:</p>
            <ol>
                <li>Acessar o painel de desenvolvedor do marketplace</li>
                <li>Criar uma aplicação/app</li>
                <li>Copiar o Client ID e Client Secret</li>
                <li>Colar as credenciais nesta configuração</li>
                <li>Autorizar o acesso (quando solicitado)</li>
            </ol>

            <h5><strong>Links Úteis:</strong></h5>
            <ul>
                <?php if ($model->marketplace === 'MERCADO_LIVRE'): ?>
                    <li><a href="https://developers.mercadolivre.com.br/" target="_blank">Mercado Livre Developers</a></li>
                <?php elseif ($model->marketplace === 'SHOPEE'): ?>
                    <li><a href="https://open.shopee.com/" target="_blank">Shopee Open Platform</a></li>
                <?php elseif ($model->marketplace === 'MAGAZINE_LUIZA'): ?>
                    <li><a href="https://api.magazineluiza.com.br/" target="_blank">Magazine Luiza API</a></li>
                <?php elseif ($model->marketplace === 'AMAZON'): ?>
                    <li><a href="https://developer.amazonservices.com/" target="_blank">Amazon MWS</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>