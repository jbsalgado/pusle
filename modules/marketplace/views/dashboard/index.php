<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Marketplaces - Dashboard';
?>

<div class="marketplace-dashboard">
    <div class="page-header">
        <h1>
            <i class="fa fa-shopping-cart"></i> Integração com Marketplaces
        </h1>
        <p class="text-muted">Gerencie suas integrações com Mercado Livre, Shopee, Magazine Luiza e Amazon</p>
    </div>

    <?php if (!app\modules\marketplace\Module::isEnabled()): ?>
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i>
            <strong>Módulo Desabilitado:</strong> O módulo de Marketplaces está desabilitado.
            Para habilitar, configure <code>marketplace.enabled = true</code> em <code>config/params.php</code>
        </div>
    <?php endif; ?>

    <!-- Estatísticas -->
    <div class="row" style="margin-bottom: 30px;">
        <div class="col-md-3">
            <div class="info-box bg-aqua">
                <span class="info-box-icon"><i class="fa fa-cubes"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Produtos Vinculados</span>
                    <span class="info-box-number"><?= number_format($stats['total_produtos']) ?></span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="info-box bg-green">
                <span class="info-box-icon"><i class="fa fa-shopping-bag"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total de Pedidos</span>
                    <span class="info-box-number"><?= number_format($stats['total_pedidos']) ?></span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="info-box bg-yellow">
                <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pedidos Pendentes</span>
                    <span class="info-box-number"><?= number_format($stats['pedidos_pendentes']) ?></span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="info-box bg-blue">
                <span class="info-box-icon"><i class="fa fa-calendar-check-o"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pedidos Hoje</span>
                    <span class="info-box-number"><?= number_format($stats['pedidos_hoje']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Marketplaces Configurados -->
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-cogs"></i> Marketplaces Configurados</h3>
            <div class="box-tools">
                <?= Html::a('<i class="fa fa-plus"></i> Adicionar Marketplace', ['config/create'], ['class' => 'btn btn-success btn-sm']) ?>
            </div>
        </div>
        <div class="box-body">
            <?php if (empty($configs)): ?>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    Nenhum marketplace configurado ainda.
                    <?= Html::a('Clique aqui para adicionar', ['config/create']) ?>
                </div>
            <?php else: ?>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Marketplace</th>
                            <th>Status</th>
                            <th>Última Sincronização</th>
                            <th>Sincronizar</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($configs as $config): ?>
                            <tr>
                                <td>
                                    <strong><?= Html::encode($config->getMarketplaceNome()) ?></strong>
                                </td>
                                <td>
                                    <?php if ($config->ativo): ?>
                                        <span class="label label-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="label label-default">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $config->ultima_sync ? Yii::$app->formatter->asDatetime($config->ultima_sync) : '<em>Nunca</em>' ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-xs">
                                        <button class="btn btn-default" title="Produtos">
                                            <i class="fa fa-cubes"></i> <?= $config->sincronizar_produtos ? 'Sim' : 'Não' ?>
                                        </button>
                                        <button class="btn btn-default" title="Estoque">
                                            <i class="fa fa-database"></i> <?= $config->sincronizar_estoque ? 'Sim' : 'Não' ?>
                                        </button>
                                        <button class="btn btn-default" title="Pedidos">
                                            <i class="fa fa-shopping-cart"></i> <?= $config->sincronizar_pedidos ? 'Sim' : 'Não' ?>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-xs">
                                        <?= Html::a('<i class="fa fa-edit"></i>', ['config/update', 'id' => $config->id], [
                                            'class' => 'btn btn-primary',
                                            'title' => 'Editar',
                                        ]) ?>
                                        <?= Html::a('<i class="fa fa-sync"></i>', ['sync/run', 'id' => $config->id], [
                                            'class' => 'btn btn-info',
                                            'title' => 'Sincronizar Agora',
                                        ]) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Pedidos Pendentes -->
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock-o"></i> Pedidos Pendentes de Importação</h3>
                </div>
                <div class="box-body">
                    <?php if (empty($pedidosPendentes)): ?>
                        <p class="text-muted"><em>Nenhum pedido pendente</em></p>
                    <?php else: ?>
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <th>Marketplace</th>
                                    <th>ID Pedido</th>
                                    <th>Cliente</th>
                                    <th>Valor</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pedidosPendentes as $pedido): ?>
                                    <tr>
                                        <td><?= Html::encode($pedido->marketplace) ?></td>
                                        <td><code><?= Html::encode($pedido->marketplace_pedido_id) ?></code></td>
                                        <td><?= Html::encode($pedido->cliente_nome) ?></td>
                                        <td>R$ <?= number_format($pedido->valor_total, 2, ',', '.') ?></td>
                                        <td><?= Yii::$app->formatter->asDate($pedido->data_pedido) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Últimos Logs -->
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-list"></i> Últimas Sincronizações</h3>
                    <div class="box-tools">
                        <?= Html::a('Ver Todos', ['dashboard/sync'], ['class' => 'btn btn-box-tool']) ?>
                    </div>
                </div>
                <div class="box-body">
                    <?php if (empty($ultimosLogs)): ?>
                        <p class="text-muted"><em>Nenhuma sincronização realizada</em></p>
                    <?php else: ?>
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <th>Marketplace</th>
                                    <th>Tipo</th>
                                    <th>Status</th>
                                    <th>Itens</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimosLogs as $log): ?>
                                    <tr>
                                        <td><?= Html::encode($log->marketplace) ?></td>
                                        <td><small><?= Html::encode($log->tipo_sync) ?></small></td>
                                        <td><?= $log->getStatusBadge() ?></td>
                                        <td><?= $log->itens_sucesso ?>/<?= $log->itens_processados ?></td>
                                        <td><small><?= Yii::$app->formatter->asDatetime($log->data_inicio, 'short') ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .info-box {
        min-height: 90px;
        background: #fff;
        width: 100%;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
        border-radius: 2px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
    }

    .info-box-icon {
        border-top-left-radius: 2px;
        border-bottom-left-radius: 2px;
        display: block;
        float: left;
        height: 90px;
        width: 90px;
        text-align: center;
        font-size: 45px;
        line-height: 90px;
        background: rgba(0, 0, 0, 0.2);
    }

    .info-box-content {
        padding: 5px 10px;
        margin-left: 90px;
    }

    .info-box-text {
        display: block;
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .info-box-number {
        display: block;
        font-weight: bold;
        font-size: 24px;
    }

    .bg-aqua {
        background-color: #00c0ef !important;
        color: #fff;
    }

    .bg-green {
        background-color: #00a65a !important;
        color: #fff;
    }

    .bg-yellow {
        background-color: #f39c12 !important;
        color: #fff;
    }

    .bg-blue {
        background-color: #3c8dbc !important;
        color: #fff;
    }
</style>