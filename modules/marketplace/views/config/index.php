<?php

use yii\helpers\Html;
use app\modules\marketplace\models\MarketplaceConfig;

$this->title = 'Configurações de Marketplaces';
?>

<div class="marketplace-config-index">
    <div class="page-header">
        <h1><i class="fa fa-store"></i> <?= Html::encode($this->title) ?></h1>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Marketplaces Configurados</h3>
            <div class="box-tools pull-right">
                <?= Html::a('<i class="fa fa-plus"></i> Nova Configuração', ['create'], [
                    'class' => 'btn btn-success btn-sm'
                ]) ?>
            </div>
        </div>

        <div class="box-body">
            <?php if (empty($configs)): ?>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    <strong>Nenhum marketplace configurado.</strong>
                    Clique em "Nova Configuração" para adicionar seu primeiro marketplace.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($configs as $config): ?>
                        <div class="col-md-6">
                            <div class="box box-widget widget-user-2">
                                <div class="widget-user-header <?= $config->ativo ? 'bg-green' : 'bg-gray' ?>">
                                    <div class="widget-user-image">
                                        <i class="fa fa-store fa-3x"></i>
                                    </div>
                                    <h3 class="widget-user-username"><?= Html::encode($config->getMarketplaceNome()) ?></h3>
                                    <h5 class="widget-user-desc">
                                        <?php if ($config->ativo): ?>
                                            <i class="fa fa-check-circle"></i> Ativo
                                        <?php else: ?>
                                            <i class="fa fa-times-circle"></i> Inativo
                                        <?php endif; ?>
                                    </h5>
                                </div>

                                <div class="box-footer no-padding">
                                    <ul class="nav nav-stacked">
                                        <li>
                                            <a href="#">
                                                <i class="fa fa-cube"></i> Sincronizar Produtos
                                                <span class="pull-right badge bg-<?= $config->sincronizar_produtos ? 'green' : 'gray' ?>">
                                                    <?= $config->sincronizar_produtos ? 'Sim' : 'Não' ?>
                                                </span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#">
                                                <i class="fa fa-boxes"></i> Sincronizar Estoque
                                                <span class="pull-right badge bg-<?= $config->sincronizar_estoque ? 'green' : 'gray' ?>">
                                                    <?= $config->sincronizar_estoque ? 'Sim' : 'Não' ?>
                                                </span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#">
                                                <i class="fa fa-shopping-cart"></i> Sincronizar Pedidos
                                                <span class="pull-right badge bg-<?= $config->sincronizar_pedidos ? 'green' : 'gray' ?>">
                                                    <?= $config->sincronizar_pedidos ? 'Sim' : 'Não' ?>
                                                </span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#">
                                                <i class="fa fa-clock-o"></i> Intervalo de Sync
                                                <span class="pull-right badge bg-blue">
                                                    <?= $config->intervalo_sync_minutos ?> min
                                                </span>
                                            </a>
                                        </li>
                                        <?php if ($config->ultima_sync): ?>
                                            <li>
                                                <a href="#">
                                                    <i class="fa fa-history"></i> Última Sincronização
                                                    <span class="pull-right text-muted">
                                                        <?= Yii::$app->formatter->asRelativeTime($config->ultima_sync) ?>
                                                    </span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php if ($config->token_expira_em): ?>
                                            <li>
                                                <a href="#">
                                                    <i class="fa fa-key"></i> Token
                                                    <span class="pull-right badge bg-<?= $config->isTokenExpired() ? 'red' : 'green' ?>">
                                                        <?= $config->isTokenExpired() ? 'Expirado' : 'Válido' ?>
                                                    </span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>

                                <div class="box-footer">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <?= Html::a('<i class="fa fa-eye"></i> Ver', ['view', 'id' => $config->id], [
                                            'class' => 'btn btn-info'
                                        ]) ?>

                                        <?= Html::a('<i class="fa fa-edit"></i> Editar', ['update', 'id' => $config->id], [
                                            'class' => 'btn btn-primary'
                                        ]) ?>

                                        <?= Html::a(
                                            '<i class="fa fa-' . ($config->ativo ? 'pause' : 'play') . '"></i> ' . ($config->ativo ? 'Desativar' : 'Ativar'),
                                            ['toggle', 'id' => $config->id],
                                            [
                                                'class' => 'btn btn-' . ($config->ativo ? 'warning' : 'success'),
                                                'data-method' => 'post',
                                            ]
                                        ) ?>

                                        <?= Html::a('<i class="fa fa-plug"></i> Testar', ['test', 'id' => $config->id], [
                                            'class' => 'btn btn-default',
                                            'data-method' => 'post',
                                        ]) ?>

                                        <?= Html::a('<i class="fa fa-trash"></i>', ['delete', 'id' => $config->id], [
                                            'class' => 'btn btn-danger',
                                            'data' => [
                                                'confirm' => 'Tem certeza que deseja remover esta configuração?',
                                                'method' => 'post',
                                            ],
                                        ]) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-info-circle"></i> Informações</h3>
        </div>
        <div class="box-body">
            <p><strong>Marketplaces Disponíveis:</strong></p>
            <ul>
                <li><strong>Mercado Livre:</strong> Maior marketplace da América Latina</li>
                <li><strong>Shopee:</strong> Marketplace focado em mobile commerce</li>
                <li><strong>Magazine Luiza:</strong> Marketplace brasileiro integrado</li>
                <li><strong>Amazon:</strong> Marketplace global (em desenvolvimento)</li>
            </ul>

            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i>
                <strong>Atenção:</strong> Para utilizar as integrações, você precisa ter uma conta de vendedor ativa em cada marketplace
                e obter as credenciais de API (Client ID e Client Secret) no painel de desenvolvedor de cada plataforma.
            </div>
        </div>
    </div>
</div>