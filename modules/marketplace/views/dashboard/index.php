<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Marketplaces - Pulse Control';

// Registrar Google Fonts (Inter)
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
?>

<div class="marketplace-dashboard-v2">
    <!-- Header Premium -->
    <div class="header-premium">
        <div class="header-info">
            <h1 class="title-gradient">Hub de Marketplaces</h1>
            <p class="subtitle">Gerencie suas conexões multicanal em um único lugar.</p>
        </div>
        <div class="header-actions">
            <?= Html::a('<i class="fa fa-plus-circle"></i> Nova Conexão', ['config/create'], ['class' => 'btn-premium']) ?>
        </div>
    </div>

    <?php if (!app\modules\marketplace\Module::isEnabled()): ?>
        <div class="alert-premium warning">
            <div class="alert-icon"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="alert-content">
                <strong>Atenção:</strong> O módulo de Marketplaces está desativado no momento.
            </div>
        </div>
    <?php endif; ?>

    <!-- Grid de Métricas Glassmorphism -->
    <div class="metrics-grid">
        <div class="metric-card gold">
            <div class="metric-icon"><i class="fa fa-shopping-bag"></i></div>
            <div class="metric-data">
                <span class="metric-label">Vendas Totais</span>
                <span class="metric-value"><?= number_format($stats['total_pedidos']) ?></span>
            </div>
            <div class="metric-footer">Sincronizado agora</div>
        </div>

        <div class="metric-card amber">
            <div class="metric-icon"><i class="fa fa-clock-o"></i></div>
            <div class="metric-data">
                <span class="metric-label">Pendentes</span>
                <span class="metric-value"><?= number_format($stats['pedidos_pendentes']) ?></span>
            </div>
            <div class="metric-footer">Aguardando importação</div>
        </div>

        <div class="metric-card emerald">
            <div class="metric-icon"><i class="fa fa-bolt"></i></div>
            <div class="metric-data">
                <span class="metric-label">Vendas Hoje</span>
                <span class="metric-value"><?= number_format($stats['pedidos_hoje']) ?></span>
            </div>
            <div class="metric-footer">+15% vs ontem</div>
        </div>

        <div class="metric-card sky">
            <div class="metric-icon"><i class="fa fa-cubes"></i></div>
            <div class="metric-data">
                <span class="metric-label">Produtos Ativos</span>
                <span class="metric-value"><?= number_format($stats['total_produtos']) ?></span>
            </div>
            <div class="metric-footer">Em 3 canais</div>
        </div>
    </div>

    <div class="main-content-row">
        <!-- Conexões Ativas -->
        <div class="content-column connections">
            <div class="glass-section">
                <div class="section-header">
                    <h3><i class="fa fa-plug"></i> Conexões de Marketplace</h3>
                    <?= Html::a('Ver Todas <i class="fa fa-arrow-right"></i>', ['config/index'], ['class' => 'view-all']) ?>
                </div>
                <div class="section-body">
                    <?php if (empty($configs)): ?>
                        <div class="empty-state">
                            <i class="fa fa-link"></i>
                            <p>Nenhuma conta conectada no momento.</p>
                            <?= Html::a('<i class="fa fa-plus-circle"></i> Conectar Primeiro Canal', ['config/create'], [
                                'class' => 'btn-premium',
                                'style' => 'margin-top: 15px; font-size: 0.85rem; padding: 10px 18px; display: inline-flex;'
                            ]) ?>
                        </div>
                    <?php else: ?>
                        <div class="connection-list">
                            <?php foreach ($configs as $config): 
                                $status = $config->getStatusFormatado();
                                $conectado = $config->isConectado();
                            ?>
                                <div class="connection-item">
                                    <div class="connection-logo">
                                        <div class="logo-placeholder <?= strtolower($config->marketplace) ?>">
                                            <?= strtoupper(substr($config->marketplace, 0, 1)) ?>
                                        </div>
                                    </div>
                                    <div class="connection-info">
                                        <div class="conn-name">
                                            <?= Html::encode($config->getMarketplaceNome()) ?>
                                            <?php if (!empty($config->apelido_conta)): ?>
                                                <span class="conn-subname">(<?= Html::encode($config->apelido_conta) ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="conn-status">
                                            <span class="status-dot <?= $status['dot_class'] === 'bg-emerald-500' ? 'online' : ($status['dot_class'] === 'bg-slate-400' ? 'paused' : 'offline') ?>"></span>
                                            <span class="status-badge <?= $status['status'] ?>">
                                                <?= $status['label'] ?>
                                            </span>
                                            <?php if (!empty($config->seller_id_externo)): ?>
                                                <span class="seller-pill" title="Seller ID">ID: <?= Html::encode($config->seller_id_externo) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="connection-sync">
                                        <div class="last-sync"><?= $config->ultima_sync ? date('d/m H:i', strtotime($config->ultima_sync)) : '--:--' ?></div>
                                        <div class="sync-label">Última Sync</div>
                                    </div>
                                    <div class="connection-actions">
                                        <!-- AÇÃO VER -->
                                        <?= Html::a('<i class="fa fa-eye"></i> <span class="btn-text">Ver</span>', ['config/view', 'id' => $config->id], [
                                            'class' => 'btn-action btn-view',
                                            'title' => 'Visualizar detalhes da conexão',
                                        ]) ?>

                                        <!-- AÇÃO EDITAR -->
                                        <?= Html::a('<i class="fa fa-pencil"></i> <span class="btn-text">Editar</span>', ['config/update', 'id' => $config->id], [
                                            'class' => 'btn-action btn-edit',
                                            'title' => 'Editar credenciais e configurações',
                                        ]) ?>

                                        <!-- AÇÃO CONECTAR OU DESCONECTAR -->
                                        <?php if ($conectado): ?>
                                            <?= Html::a('<i class="fa fa-chain-broken"></i> <span class="btn-text">Desconectar</span>', ['config/disconnect', 'id' => $config->id], [
                                                'class' => 'btn-action btn-disconnect',
                                                'title' => 'Desconectar conta e revogar tokens de acesso',
                                                'data' => [
                                                    'confirm' => "Tem certeza que deseja desconectar a conta {$config->getMarketplaceNome()}? Os tokens de sincronização serão revogados.",
                                                    'method' => 'post',
                                                ],
                                            ]) ?>
                                        <?php else: ?>
                                            <?= Html::a('<i class="fa fa-plug"></i> <span class="btn-text">Conectar</span>', ['config/auth', 'id' => $config->id], [
                                                'class' => 'btn-action btn-connect',
                                                'title' => 'Autenticar e conectar loja via OAuth',
                                            ]) ?>
                                        <?php endif; ?>

                                        <!-- AÇÃO SINCRONIZAR -->
                                        <?= Html::a('<i class="fa fa-refresh"></i>', ['sync/run', 'id' => $config->id], [
                                            'class' => 'icon-btn refresh',
                                            'title' => 'Sincronizar agora',
                                        ]) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Performance por Canal -->
        <div class="content-column performance">
            <div class="glass-section">
                <div class="section-header">
                    <h3><i class="fa fa-line-chart"></i> Performance por Canal (30 dias)</h3>
                </div>
                <div class="section-body">
                    <?php if (empty($performance)): ?>
                        <div class="empty-state">
                            <i class="fa fa-bar-chart"></i>
                            <p>Aguardando dados de vendas...</p>
                        </div>
                    <?php else: ?>
                        <div class="performance-list">
                            <?php foreach ($performance as $p):
                                $percent = $maxVendas > 0 ? ($p['valor_total'] / $maxVendas) * 100 : 0;
                            ?>
                                <div class="perf-item">
                                    <div class="perf-info">
                                        <span class="perf-name"><?= Html::encode($p['marketplace']) ?></span>
                                        <span class="perf-value">R$ <?= number_format($p['valor_total'], 2, ',', '.') ?></span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar-premium <?= strtolower($p['marketplace']) ?>" style="width: <?= $percent ?>%"></div>
                                    </div>
                                    <div class="perf-meta"><?= $p['total_pedidos'] ?> pedidos realizados</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Próximos Passos / Canais Disponíveis -->
                    <div class="real-integration-notice">
                        <h4><i class="fa fa-rocket"></i> Expanda seu Negócio</h4>
                        <p>Conecte suas contas para sincronização multicanal de estoque e pedidos.</p>
                        <div class="action-grid">
                            <?= Html::a('<i class="fa fa-shopping-bag"></i><span>Mercado Livre</span>', ['config/auth', 'm' => 'ML'], ['class' => 'action-card', 'title' => 'Conectar Mercado Livre']) ?>
                            <?= Html::a('<i class="fa fa-shopping-cart"></i><span>Shopee</span>', ['config/auth', 'm' => 'SHOPEE'], ['class' => 'action-card', 'title' => 'Conectar Shopee']) ?>
                            <?= Html::a('<i class="fa fa-cube"></i><span>Magalu</span>', ['config/create', 'marketplace' => 'MAGAZINE_LUIZA'], ['class' => 'action-card', 'title' => 'Conectar Magazine Luiza']) ?>
                            <?= Html::a('<i class="fa fa-amazon"></i><span>Amazon</span>', ['config/create', 'marketplace' => 'AMAZON'], ['class' => 'action-card', 'title' => 'Conectar Amazon']) ?>
                            <?= Html::a('<i class="fa fa-cutlery"></i><span>iFood</span>', ['config/create', 'marketplace' => 'IFOOD'], ['class' => 'action-card', 'title' => 'Conectar iFood']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --primary: #6366f1;
        --success: #22c55e;
        --warning: #f59e0b;
        --danger: #ef4444;
        --bg-glass: rgba(255, 255, 255, 0.7);
        --border-glass: rgba(255, 255, 255, 0.5);
    }

    .marketplace-dashboard-v2 {
        font-family: 'Inter', sans-serif;
        padding: 20px;
        color: #1f2937;
        background: #f8fafc;
        min-height: 100vh;
    }

    .header-premium {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .title-gradient {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 800;
        font-size: 2.5rem;
        margin-bottom: 5px;
    }

    .subtitle {
        color: #64748b;
        font-size: 1.1rem;
    }

    .btn-premium {
        background: #4f46e5;
        color: white;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
    }

    .btn-premium:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
        color: white;
        text-decoration: none;
    }

    /* Metrics Grid */
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .metric-card {
        background: white;
        padding: 24px;
        border-radius: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        transition: transform 0.3s;
    }

    .metric-card:hover {
        transform: translateY(-5px);
    }

    .metric-icon {
        background: #f1f5f9;
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 15px;
    }

    .metric-card.gold .metric-icon {
        color: #f59e0b;
        background: #fffbeb;
    }

    .metric-card.amber .metric-icon {
        color: #d97706;
        background: #fff7ed;
    }

    .metric-card.emerald .metric-icon {
        color: #10b981;
        background: #ecfdf5;
    }

    .metric-card.sky .metric-icon {
        color: #0ea5e9;
        background: #f0f9ff;
    }

    .metric-label {
        display: block;
        font-size: 0.875rem;
        color: #64748b;
        font-weight: 500;
    }

    .metric-value {
        display: block;
        font-size: 1.8rem;
        font-weight: 800;
        color: #0f172a;
    }

    .metric-footer {
        font-size: 0.75rem;
        color: #94a3b8;
        margin-top: 10px;
    }

    /* Layout Sections */
    .main-content-row {
        display: grid;
        grid-template-columns: 3fr 2fr;
        gap: 25px;
    }

    .glass-section {
        background: white;
        border-radius: 24px;
        border: 1px solid #e2e8f0;
        padding: 25px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        height: 100%;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .section-header h3 {
        margin: 0;
        font-weight: 700;
        font-size: 1.25rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .view-all {
        color: var(--primary);
        font-weight: 600;
        font-size: 0.875rem;
    }

    /* Connection List */
    .connection-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .connection-item {
        display: flex;
        align-items: center;
        padding: 15px;
        background: #f8fafc;
        border-radius: 16px;
        border: 1px solid #f1f5f9;
        transition: all 0.2s;
    }

    .connection-item:hover {
        background: white;
        border-color: #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .logo-placeholder {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        color: white;
        margin-right: 15px;
    }

    .logo-placeholder.mercado_livre {
        background: #fee227;
        color: #1f2937;
    }

    .logo-placeholder.shopee {
        background: #ee4d2d;
    }

    .logo-placeholder.ifood {
        background: #ea1d2c;
    }

    .conn-name {
        font-weight: 700;
        font-size: 1.05rem;
    }

    .conn-status {
        font-size: 0.8rem;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .status-dot.online {
        background: var(--success);
        box-shadow: 0 0 8px var(--success);
    }

    .status-dot.offline {
        background: #94a3b8;
    }

    .connection-info {
        flex: 1;
    }

    .connection-sync {
        text-align: right;
        margin: 0 20px;
    }

    .last-sync {
        font-weight: 700;
        color: #0f172a;
    }

    .sync-label {
        font-size: 0.7rem;
        color: #94a3b8;
        text-transform: uppercase;
    }

    .connection-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .conn-subname {
        font-size: 0.8rem;
        color: #64748b;
        font-weight: 500;
        margin-left: 4px;
    }

    .status-badge {
        font-size: 0.725rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 9999px;
        letter-spacing: 0.02em;
    }

    .status-badge.connected {
        background: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .status-badge.paused {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }

    .status-badge.disconnected, .status-badge.expired {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .seller-pill {
        font-size: 0.7rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        padding: 1px 6px;
        border-radius: 6px;
        font-family: monospace;
    }

    .status-dot.paused {
        background: #94a3b8;
    }

    /* Action Buttons (Ver, Editar, Conectar, Desconectar) */
    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 11px;
        border-radius: 8px;
        font-size: 0.775rem;
        font-weight: 600;
        transition: all 0.2s ease;
        text-decoration: none !important;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .btn-action.btn-view {
        background: #f8fafc;
        border-color: #e2e8f0;
        color: #334155;
    }

    .btn-action.btn-view:hover {
        background: #4f46e5;
        color: white;
        border-color: #4f46e5;
    }

    .btn-action.btn-edit {
        background: #f8fafc;
        border-color: #e2e8f0;
        color: #334155;
    }

    .btn-action.btn-edit:hover {
        background: #0284c7;
        color: white;
        border-color: #0284c7;
    }

    .btn-action.btn-connect {
        background: #4f46e5;
        color: white;
        border-color: #4338ca;
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
    }

    .btn-action.btn-connect:hover {
        background: #4338ca;
        color: white;
        transform: translateY(-1px);
    }

    .btn-action.btn-disconnect {
        background: #fff1f2;
        color: #e11d48;
        border-color: #fecdd3;
    }

    .btn-action.btn-disconnect:hover {
        background: #e11d48;
        color: white;
        border-color: #e11d48;
    }

    .icon-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border: 1px solid #e2e8f0;
        color: #64748b;
        transition: all 0.2s;
    }

    .icon-btn:hover {
        background: #4f46e5;
        color: white;
        border-color: #4f46e5;
    }

    .icon-btn.refresh:hover {
        background: #10b981;
        border-color: #10b981;
    }

    /* Timeline Logs */
    .log-timeline {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .log-entry {
        display: flex;
        gap: 15px;
    }

    .log-icon {
        width: 32px;
        height: 32px;
        min-width: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }

    .log-icon.sucesso {
        background: #ecfdf5;
        color: #10b981;
    }

    .log-icon.erro {
        background: #fef2f2;
        color: #ef4444;
    }

    .log-title {
        font-weight: 600;
        font-size: 0.95rem;
    }

    .log-meta {
        font-size: 0.8rem;
        color: #94a3b8;
    }

    .separator {
        margin: 0 5px;
    }

    /* Performance Visuals */
    .performance-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-bottom: 30px;
    }

    .perf-item {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .perf-info {
        display: flex;
        justify-content: space-between;
        font-weight: 700;
    }

    .progress-container {
        height: 8px;
        background: #f1f5f9;
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-bar-premium {
        height: 100%;
        border-radius: 4px;
        transition: width 1s ease-out;
    }

    .progress-bar-premium.mercado_livre {
        background: #fee227;
    }

    .progress-bar-premium.shopee {
        background: #ee4d2d;
    }

    .progress-bar-premium.ifood {
        background: #ea1d2c;
    }

    .perf-meta {
        font-size: 0.75rem;
        color: #94a3b8;
    }

    .real-integration-notice {
        margin-top: 30px;
        padding-top: 25px;
        border-top: 1px dashed #e2e8f0;
    }

    .real-integration-notice h4 {
        font-weight: 800;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }

    .real-integration-notice p {
        color: #64748b;
        font-size: 0.9rem;
        margin-bottom: 15px;
    }

    .action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
        gap: 10px;
    }

    .action-card {
        padding: 12px 10px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: white;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.2s;
        font-weight: 600;
        font-size: 0.75rem;
        text-align: center;
        text-decoration: none !important;
        color: #334155;
    }

    .action-card:hover:not(.disabled) {
        border-color: var(--primary);
        color: var(--primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .action-card.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        grayscale: 1;
    }

    @media (max-width: 1024px) {
        .main-content-row {
            grid-template-columns: 1fr;
        }

        .title-gradient {
            font-size: 2rem;
        }
    }

    @media (max-width: 768px) {
        .connection-item {
            flex-wrap: wrap;
            gap: 12px;
        }

        .connection-actions {
            width: 100%;
            justify-content: flex-start;
            margin-top: 8px;
            padding-top: 10px;
            border-top: 1px dashed #e2e8f0;
        }

        .connection-sync {
            margin: 0 10px;
        }
    }
</style>