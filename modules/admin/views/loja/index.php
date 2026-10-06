<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Gerenciar Lojas | PULSE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0D0E1F;
            --bg-card: #141627;
            --bg-input: #1C1E35;
            --primary: #6C63FF;
            --primary-light: rgba(108,99,255,0.12);
            --text: #F0F0FF;
            --text-muted: #7A7998;
            --border: rgba(255,255,255,0.07);
            --green: #43E97B;
            --yellow: #FFD166;
            --red: #FF6584;
            --blue: #43AFFF;
            --radius: 14px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

        /* SIDEBAR */
        .layout { display: flex; min-height: 100vh; }
        .sidebar {
            width: 240px;
            background: var(--bg-card);
            border-right: 1px solid var(--border);
            padding: 24px 0;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
        }
        .sidebar-brand {
            padding: 0 24px 28px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 16px;
        }
        .sidebar-brand .logo { font-size: 22px; font-weight: 900; letter-spacing: -0.5px; }
        .sidebar-brand .logo span { color: var(--primary); }
        .sidebar-brand .badge {
            display: inline-block;
            background: var(--primary-light);
            color: var(--primary);
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 50px;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 0;
            transition: color 0.2s, background 0.2s;
            margin: 2px 8px;
            border-radius: 10px;
        }
        .nav-item:hover { color: var(--text); background: rgba(255,255,255,0.04); }
        .nav-item.active { color: var(--primary); background: var(--primary-light); }
        .nav-icon { font-size: 18px; width: 24px; text-align: center; }
        .sidebar-footer {
            margin-top: auto;
            padding: 16px 24px;
            border-top: 1px solid var(--border);
        }
        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13px;
        }
        .sidebar-footer a:hover { color: var(--red); }

        /* MAIN */
        .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 32px;
            border-bottom: 1px solid var(--border);
            background: var(--bg-card);
        }
        .topbar h1 { font-size: 20px; font-weight: 700; }
        .topbar-admin {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--text-muted);
        }
        .avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: var(--primary-light);
            color: var(--primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
        }

        .content { padding: 32px; overflow-y: auto; flex: 1; }

        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }
        @media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            cursor: pointer;
            transition: border-color 0.2s, transform 0.2s;
            text-decoration: none;
            display: block;
        }
        .stat-card:hover { border-color: var(--primary); transform: translateY(-2px); }
        .stat-card.active-filter { border-color: var(--primary); background: var(--primary-light); }

        .stat-value { font-size: 36px; font-weight: 800; margin-bottom: 4px; }
        .stat-label { font-size: 13px; color: var(--text-muted); font-weight: 500; }
        .stat-card.pendente .stat-value { color: var(--yellow); }
        .stat-card.ativa    .stat-value { color: var(--green); }
        .stat-card.suspensa .stat-value { color: var(--red); }
        .stat-card.rejeitada .stat-value { color: var(--text-muted); }

        /* TABLE */
        .table-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }
        .table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }
        .table-header h2 { font-size: 16px; font-weight: 700; }
        .filter-tabs {
            display: flex;
            gap: 8px;
        }
        .tab {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            color: var(--text-muted);
            background: transparent;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
        }
        .tab:hover { color: var(--text); background: rgba(255,255,255,0.04); }
        .tab.active { color: var(--primary); background: var(--primary-light); border-color: rgba(108,99,255,0.3); }

        table { width: 100%; border-collapse: collapse; }
        th {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.8px;
            text-transform: uppercase;
            padding: 14px 24px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        td {
            padding: 16px 24px;
            font-size: 14px;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            vertical-align: middle;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,0.02); }

        .loja-name { font-weight: 600; }
        .loja-email { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .loja-phone { font-size: 12px; color: var(--text-muted); }

        /* Badges status */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-status::before { content: '●'; font-size: 8px; }
        .badge-status.pendente  { background: rgba(255,209,102,0.15); color: var(--yellow); }
        .badge-status.ativa     { background: rgba(67,233,123,0.15);  color: var(--green); }
        .badge-status.suspensa  { background: rgba(255,101,132,0.15); color: var(--red); }
        .badge-status.rejeitada { background: rgba(122,121,152,0.15); color: var(--text-muted); }

        /* Action buttons */
        .btn-action {
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: 'Inter', sans-serif;
        }
        .btn-approve { background: rgba(67,233,123,0.15); color: var(--green); }
        .btn-approve:hover { background: rgba(67,233,123,0.3); }
        .btn-suspend { background: rgba(255,209,102,0.12); color: var(--yellow); }
        .btn-suspend:hover { background: rgba(255,209,102,0.25); }
        .btn-reject  { background: rgba(255,101,132,0.12); color: var(--red); }
        .btn-reject:hover { background: rgba(255,101,132,0.25); }
        .btn-reactivate { background: var(--primary-light); color: var(--primary); }
        .btn-reactivate:hover { background: rgba(108,99,255,0.25); }
        .btn-delete { background: rgba(239,68,68,0.12); color: #f87171; border: 1px solid rgba(239,68,68,0.2); }
        .btn-delete:hover { background: rgba(239,68,68,0.28); color: #fff; }

        .date-info { font-size: 12px; color: var(--text-muted); }

        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .spinner-sm {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            vertical-align: middle;
        }

        /* Empty state */
        .empty-state { text-align: center; padding: 60px 24px; color: var(--text-muted); }
        .empty-state .icon { font-size: 48px; margin-bottom: 16px; }
        .empty-state p { font-size: 15px; }

        /* Toast notification */
        #toast {
            position: fixed;
            bottom: 32px;
            right: 32px;
            padding: 16px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 8px 30px rgba(0,0,0,0.4);
            transform: translateY(80px);
            opacity: 0;
            transition: all 0.3s;
            z-index: 9999;
            max-width: 360px;
        }
        #toast.show { transform: translateY(0); opacity: 1; }
        #toast.success { background: rgba(67,233,123,0.15); border: 1px solid rgba(67,233,123,0.35); color: var(--green); }
        #toast.error   { background: rgba(255,101,132,0.15); border: 1px solid rgba(255,101,132,0.35); color: var(--red); }
    </style>
</head>
<body>
<?php
use yii\helpers\Html;
use yii\helpers\Url;
/** @var $lojas app\models\Usuario[] */
/** @var $status string */
/** @var $contadores array */
$admin = Yii::$app->user->identity;
?>

<div class="layout">
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-brand">
            <div class="logo">PULSE<span>.</span></div>
            <div class="badge">ADMIN</div>
        </div>
        <a href="<?= Url::to(['/admin/loja/index']) ?>" class="nav-item active">
            <span class="nav-icon">🏪</span> Lojas
        </a>
        <a href="<?= Url::to(['/admin/financeiro/index']) ?>" class="nav-item">
            <span class="nav-icon">💰</span> Financeiro SaaS
        </a>
        <a href="<?= Url::to(['/admin/financeiro/faturas']) ?>" class="nav-item">
            <span class="nav-icon">📄</span> Faturas Lojas
        </a>
        <a href="<?= Url::to(['/admin/financeiro/planos']) ?>" class="nav-item">
            <span class="nav-icon">🏷️</span> Planos & Taxas
        </a>
        <a href="<?= Url::to(['/admin/financeiro/config']) ?>" class="nav-item">
            <span class="nav-icon">⚙️</span> Config. Master
        </a>
        <a href="<?= Url::to(['/vendas/inicio']) ?>" class="nav-item">
            <span class="nav-icon">🔙</span> Voltar ao Sistema
        </a>
        <div class="sidebar-footer">
            <a href="<?= Url::to(['/auth/logout']) ?>">
                <span>⏻</span> Sair
            </a>
        </div>
    </nav>

    <!-- Main content -->
    <div class="main">
        <div class="topbar">
            <h1>Gerenciar Lojas</h1>
            <div class="topbar-admin">
                <div class="avatar">👑</div>
                <span><?= Html::encode($admin->nome) ?></span>
            </div>
        </div>

        <div class="content">

            <!-- Stats Cards -->
            <div class="stats-grid">
                <a href="?status=pendente" class="stat-card pendente <?= $status === 'pendente' ? 'active-filter' : '' ?>">
                    <div class="stat-value" id="val-pendente"><?= $contadores['pendente'] ?></div>
                    <div class="stat-label">⏳ Aguardando Aprovação</div>
                </a>
                <a href="?status=ativa" class="stat-card ativa <?= $status === 'ativa' ? 'active-filter' : '' ?>">
                    <div class="stat-value" id="val-ativa"><?= $contadores['ativa'] ?></div>
                    <div class="stat-label">✅ Lojas Ativas</div>
                </a>
                <a href="?status=suspensa" class="stat-card suspensa <?= $status === 'suspensa' ? 'active-filter' : '' ?>">
                    <div class="stat-value" id="val-suspensa"><?= $contadores['suspensa'] ?></div>
                    <div class="stat-label">⚠️ Suspensas</div>
                </a>
                <a href="?status=todos" class="stat-card rejeitada <?= $status === 'todos' ? 'active-filter' : '' ?>">
                    <div class="stat-value" id="val-total"><?= array_sum($contadores) ?></div>
                    <div class="stat-label">🏪 Total de Lojas</div>
                </a>
            </div>

            <!-- Table -->
            <div class="table-card">
                <div class="table-header">
                    <h2>
                        <?= $status === 'todos' ? 'Todas as Lojas' : 'Lojas: ' . ucfirst($status) ?>
                    </h2>
                    <div class="filter-tabs">
                        <a href="?status=todos"     class="tab <?= $status === 'todos'     ? 'active' : '' ?>">Todas</a>
                        <a href="?status=pendente"  class="tab <?= $status === 'pendente'  ? 'active' : '' ?>">Pendentes</a>
                        <a href="?status=ativa"     class="tab <?= $status === 'ativa'     ? 'active' : '' ?>">Ativas</a>
                        <a href="?status=suspensa"  class="tab <?= $status === 'suspensa'  ? 'active' : '' ?>">Suspensas</a>
                        <a href="?status=rejeitada" class="tab <?= $status === 'rejeitada' ? 'active' : '' ?>">Rejeitadas</a>
                    </div>
                </div>

                <?php if (empty($lojas)): ?>
                    <div class="empty-state">
                        <div class="icon">🏪</div>
                        <p>Nenhuma loja encontrada para este filtro.</p>
                    </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Loja / Responsável</th>
                            <th>Contato</th>
                            <th>Localização</th>
                            <th>Cadastro</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($lojas as $loja): ?>
                        <tr id="row-<?= Html::encode($loja->id) ?>">
                            <td>
                                <div class="loja-name"><?= Html::encode($loja->nome) ?></div>
                                <div class="loja-email"><?= Html::encode($loja->email) ?></div>
                            </td>
                            <td>
                                <div class="loja-phone">📱 <?= Html::encode($loja->telefone ?? '—') ?></div>
                            </td>
                            <td>
                                <div class="date-info">
                                    <?= Html::encode(($loja->cidade ?? '') . ($loja->estado ? '/' . $loja->estado : '')) ?: '—' ?>
                                </div>
                            </td>
                            <td>
                                <div class="date-info">
                                    <?= $loja->data_criacao ? date('d/m/Y H:i', strtotime($loja->data_criacao)) : '—' ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge-status <?= Html::encode($loja->status_loja) ?>">
                                    <?= ucfirst($loja->status_loja) ?>
                                </span>
                                <?php if ($loja->is_admin): ?>
                                    <span class="badge-status" style="background: rgba(139,92,246,0.2); color: #a78bfa; border: 1px solid rgba(139,92,246,0.3); margin-top: 4px; display: inline-flex;">🛡️ Super Admin</span>
                                <?php endif; ?>
                                <?php 
                                    $temMpLoja = $loja->temMercadoPagoConfigurado();
                                    $stPix = $loja->getStatusPixEstatico();
                                ?>
                                <?php if ($temMpLoja): ?>
                                    <?php if ($stPix['bloqueado']): ?>
                                        <span class="badge-status" style="background: rgba(239,68,68,0.12); color: #f87171; border: 1px solid rgba(239,68,68,0.25); margin-top: 4px; display: inline-flex; font-size: 10px;" title="Mercado Pago ativo - PIX Estático Bloqueado (Sem Cota)">🔒 PIX MP Exclusivo</span>
                                    <?php else: ?>
                                        <span class="badge-status" style="background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.25); margin-top: 4px; display: inline-flex; font-size: 10px;" title="Cota liberada: <?= $stPix['realizadas'] ?> de <?= $stPix['limite'] ?> vendas">📱 PIX Loja (<?= $stPix['ilimitado'] ? 'Ilimitado' : $stPix['restantes'] . ' rest.' ?>)</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= Url::to(['/admin/loja/modulos', 'id' => $loja->id]) ?>" class="btn-action" style="background: var(--primary-light); color: var(--primary);" title="Gerenciar Módulos e Acessos">
                                    ⚙️ Permissões
                                </a>
                                <a href="<?= Url::to(['/admin/loja/modulos', 'id' => $loja->id]) ?>" class="btn-action" style="background: rgba(6,182,212,0.15); color: #06b6d4;" title="Configurar Cota de PIX Estático">
                                    ⚡ Cota PIX
                                </a>
                                <?php if ($loja->is_admin): ?>
                                    <button class="btn-action" style="background: rgba(239,68,68,0.15); color: #ef4444;" onclick="abrirModalToggleAdmin('<?= Html::encode($loja->id) ?>', '<?= Html::encode($loja->nome) ?>', true)" title="Revogar privilégio de Super Admin">
                                        🛡️ Revogar Admin
                                    </button>
                                <?php else: ?>
                                    <button class="btn-action" style="background: rgba(139,92,246,0.15); color: #a78bfa;" onclick="abrirModalToggleAdmin('<?= Html::encode($loja->id) ?>', '<?= Html::encode($loja->nome) ?>', false)" title="Tornar Super Administrador">
                                        🛡️ Tornar Admin
                                    </button>
                                <?php endif; ?>

                                <?php if ($loja->status_loja === 'pendente'): ?>
                                    <button class="btn-action btn-approve" onclick="acao('aprovar', '<?= Html::encode($loja->id) ?>', '<?= Html::encode($loja->nome) ?>', this)">
                                        ✅ Aprovar
                                    </button>
                                    <button class="btn-action btn-reject" onclick="acao('rejeitar', '<?= Html::encode($loja->id) ?>', '<?= Html::encode($loja->nome) ?>', this)">
                                        ✕ Rejeitar
                                    </button>
                                <?php elseif ($loja->status_loja === 'ativa'): ?>
                                    <button class="btn-action btn-suspend" onclick="acao('suspender', '<?= Html::encode($loja->id) ?>', '<?= Html::encode($loja->nome) ?>', this)">
                                        ⏸ Suspender
                                    </button>
                                <?php else: ?>
                                    <button class="btn-action btn-reactivate" onclick="acao('reativar', '<?= Html::encode($loja->id) ?>', '<?= Html::encode($loja->nome) ?>', this)">
                                        ▶ Reativar
                                    </button>
                                <?php endif; ?>

                                <?php if (!$loja->is_admin): ?>
                                    <button class="btn-action btn-delete" onclick="abrirModalExcluirLoja('<?= Html::encode($loja->id) ?>', '<?= Html::encode($loja->nome) ?>')" title="Excluir loja por completo">
                                        🗑️ Excluir
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<div id="toast"></div>

<!-- Modal de Confirmação por Senha para Atribuição de Super Admin -->
<div id="modalToggleAdmin" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); backdrop-filter:blur(6px); z-index:99999; align-items:center; justify-content:center; padding: 20px;">
    <div style="background:#141627; border:1px solid rgba(139,92,246,0.3); border-radius:18px; padding:28px; width:100%; max-width:440px; color:#fff; text-align:center; box-shadow: 0 20px 50px rgba(0,0,0,0.6);">
        <div style="font-size:42px; margin-bottom:12px;" id="iconModalAdmin">🛡️</div>
        <h3 id="tituloModalAdmin" style="font-size:18px; font-weight:700; margin-bottom:8px; color:#fff;">Privilégio Super Admin</h3>
        <p id="descModalAdmin" style="font-size:13px; color:#7A7998; margin-bottom:20px; line-height:1.5;"></p>
        
        <div style="text-align:left; margin-bottom:20px;">
            <label style="font-size:12px; font-weight:600; color:#a78bfa; display:block; margin-bottom:6px;">Digite SUA Senha Atual para Confirmar:</label>
            <input type="password" id="inputSenhaAdminConfirm" placeholder="Sua senha atual de Super Admin" style="width:100%; padding:12px 16px; background:#1C1E35; border:1px solid rgba(255,255,255,0.12); border-radius:12px; color:#fff; font-size:14px; outline:none;">
        </div>

        <div style="display:flex; gap:12px; justify-content:flex-end;">
            <button type="button" onclick="fecharModalAdmin()" style="padding:10px 18px; background:#1C1E35; color:#aaa; border:none; border-radius:10px; font-weight:600; cursor:pointer;">Cancelar</button>
            <button type="button" id="btnConfirmarToggleAdmin" onclick="executarToggleAdmin()" style="padding:10px 20px; background:#6C63FF; color:#fff; border:none; border-radius:10px; font-weight:700; cursor:pointer;">Confirmar Alteração</button>
        </div>
    </div>
</div>

<!-- Modal de Confirmação para Exclusão Completa de Loja -->
<div id="modalExcluirLoja" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); backdrop-filter:blur(6px); z-index:99999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#141627; border:1px solid rgba(239,68,68,0.4); border-radius:18px; padding:28px; width:100%; max-width:480px; color:#fff; text-align:center; box-shadow:0 20px 50px rgba(0,0,0,0.7);">
        <div style="font-size:46px; margin-bottom:12px;">⚠️</div>
        <h3 style="font-size:18px; font-weight:700; margin-bottom:8px; color:#f87171;">Excluir Loja por Completo</h3>
        <p style="font-size:13px; color:#c7d2fe; margin-bottom:12px; line-height:1.5;">
            Você está prestes a excluir definitivamente a loja: <br>
            <strong id="nomeExcluirLoja" style="font-size:15px; color:#fff; background:rgba(239,68,68,0.2); padding:2px 8px; border-radius:6px; display:inline-block; margin-top:4px;"></strong>
        </p>
        <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25); border-radius:10px; padding:12px; font-size:12px; color:#fca5a5; text-align:left; line-height:1.4; margin-bottom:18px;">
            🚨 <strong>ATENÇÃO: AÇÃO IRREVERSÍVEL!</strong><br>
            Todos os produtos, vendas, clientes, notas fiscais, cobranças, movimentações e mídias vinculadas a esta loja serão <strong>eliminados permanentemente do banco de dados e do servidor</strong>.
        </div>
        
        <div style="text-align:left; margin-bottom:20px;">
            <label style="font-size:12px; font-weight:600; color:#f87171; display:block; margin-bottom:6px;">
                Digite a palavra <span style="text-decoration:underline; font-weight:800;">EXCLUIR</span> em maiúsculas para confirmar:
            </label>
            <input type="text" id="inputConfirmarExcluirTexto" placeholder="EXCLUIR" oninput="validarTextoExclusao()" style="width:100%; padding:12px 16px; background:#1C1E35; border:1px solid rgba(239,68,68,0.3); border-radius:12px; color:#fff; font-size:14px; outline:none; text-transform:uppercase; letter-spacing:1px; text-align:center;">
        </div>

        <div style="display:flex; gap:12px; justify-content:flex-end;">
            <button type="button" onclick="fecharModalExcluirLoja()" style="padding:10px 18px; background:#1C1E35; color:#aaa; border:none; border-radius:10px; font-weight:600; cursor:pointer;">Cancelar</button>
            <button type="button" id="btnConfirmarExcluirLoja" disabled onclick="executarExcluirLoja()" style="padding:10px 20px; background:#ef4444; color:#fff; border:none; border-radius:10px; font-weight:700; cursor:not-allowed; opacity:0.5; transition:all 0.2s;">Excluir Definitivamente</button>
        </div>
    </div>
</div>

<script>
const urls = {
    aprovar:   '<?= Url::to(['/admin/loja/aprovar']) ?>',
    suspender: '<?= Url::to(['/admin/loja/suspender']) ?>',
    rejeitar:  '<?= Url::to(['/admin/loja/rejeitar']) ?>',
    reativar:  '<?= Url::to(['/admin/loja/reativar']) ?>',
    excluir:   '<?= Url::to(['/admin/loja/excluir']) ?>',
};

let targetAdminUserId = null;
let targetAdminIsRevoking = false;

function abrirModalToggleAdmin(id, nome, isRevoking) {
    targetAdminUserId = id;
    targetAdminIsRevoking = isRevoking;
    document.getElementById('inputSenhaAdminConfirm').value = '';

    const icon = document.getElementById('iconModalAdmin');
    const titulo = document.getElementById('tituloModalAdmin');
    const desc = document.getElementById('descModalAdmin');
    const btn = document.getElementById('btnConfirmarToggleAdmin');

    if (isRevoking) {
        icon.innerText = '⚠️';
        titulo.innerText = 'Revogar Privilégio Super Admin';
        desc.innerText = `Você está prestes a revogar os privilégios de Super Admin do usuário "${nome}". Ele perderá o acesso global ao Painel SaaS.`;
        btn.style.background = '#ef4444';
        btn.innerText = 'Revogar Acesso';
    } else {
        icon.innerText = '🛡️';
        titulo.innerText = 'Conceder Privilégio Super Admin';
        desc.innerText = `ATENÇÃO: Você está prestes a tornar "${nome}" um Super Administrador! Ele terá acesso total e irrestrito a todas as lojas, dados financeiros e configurações da plataforma SaaS.`;
        btn.style.background = '#6C63FF';
        btn.innerText = 'Conceder Acesso Super Admin';
    }

    const modal = document.getElementById('modalToggleAdmin');
    modal.style.display = 'flex';
    document.getElementById('inputSenhaAdminConfirm').focus();
}

function fecharModalAdmin() {
    document.getElementById('modalToggleAdmin').style.display = 'none';
    targetAdminUserId = null;
}

async function executarToggleAdmin() {
    if (!targetAdminUserId) return;

    const senha = document.getElementById('inputSenhaAdminConfirm').value.trim();
    if (!senha) {
        showToast(false, 'Por favor, digite sua senha atual para confirmar.');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('senha_admin', senha);

        const res = await fetch('<?= Url::to(['/admin/loja/toggle-admin']) ?>?id=' + encodeURIComponent(targetAdminUserId), {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
            },
            body: formData
        });

        const data = await res.json();
        showToast(data.success, data.message);

        if (data.success) {
            fecharModalAdmin();
            setTimeout(() => location.reload(), 1500);
        }
    } catch (e) {
        showToast(false, 'Erro de comunicação com o servidor.');
    }
}

async function acao(tipo, id, nome, btn) {
    const labels = {
        aprovar: `Aprovar a loja "${nome}"?`,
        suspender: `Suspender a loja "${nome}"?`,
        rejeitar: `Rejeitar o cadastro de "${nome}"? Esta ação não pode ser desfeita facilmente.`,
        reativar: `Reativar a loja "${nome}"?`,
    };

    if (!confirm(labels[tipo])) return;

    const row = document.getElementById('row-' + id);
    const actionCell = btn ? btn.parentElement : (row ? row.cells[5] : null);
    const allButtons = actionCell ? actionCell.querySelectorAll('button') : [];

    // Desabilita botões da linha e mostra indicador de loading instantâneo
    allButtons.forEach(b => {
        b.disabled = true;
        b.style.pointerEvents = 'none';
        b.style.opacity = '0.5';
    });

    const originalBtnHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.innerHTML = '<span class="spinner-sm"></span> Aguarde...';
    }

    try {
        const res = await fetch(urls[tipo] + '?id=' + encodeURIComponent(id), {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
            },
        });
        const data = await res.json();
        showToast(data.success, data.message);

        if (data.success) {
            // Atualização dinâmica do DOM (sem congelamento nem delay de reload)
            atualizarLinhaLoja(id, nome, tipo);
        } else {
            // Reverte estado do botão em caso de erro retornado pela API
            restaurarBotoes(allButtons, btn, originalBtnHtml);
        }
    } catch (e) {
        showToast(false, 'Erro de conexão com o servidor. Tente novamente.');
        restaurarBotoes(allButtons, btn, originalBtnHtml);
    }
}

function restaurarBotoes(buttons, btn, originalHtml) {
    buttons.forEach(b => {
        b.disabled = false;
        b.style.pointerEvents = '';
        b.style.opacity = '';
    });
    if (btn && originalHtml) {
        btn.innerHTML = originalHtml;
    }
}

function atualizarLinhaLoja(id, nome, tipo) {
    const row = document.getElementById('row-' + id);
    if (!row) return;

    const badgeCell = row.cells[4];
    const actionCell = row.cells[5];
    const badge = badgeCell ? badgeCell.querySelector('.badge-status') : null;

    // Atualiza contadores numéricos dos cards
    const valPendente = document.getElementById('val-pendente');
    const valAtiva = document.getElementById('val-ativa');
    const valSuspensa = document.getElementById('val-suspensa');

    const modulosUrl = '<?= Url::to(['/admin/loja/modulos']) ?>?id=' + encodeURIComponent(id);
    const btnAdmin = actionCell ? actionCell.querySelector('button[title*="Super Admin"]') : null;
    const isSuperAdmin = (row.querySelector('.badge-status[style*="Super Admin"]') !== null) || (btnAdmin && btnAdmin.innerText.includes('Revogar'));
    const deleteHtml = isSuperAdmin ? '' : `<button class="btn-action btn-delete" onclick="abrirModalExcluirLoja('${id}', '${nome}')" title="Excluir loja por completo">🗑️ Excluir</button>`;

    if (tipo === 'aprovar' || tipo === 'reativar') {
        if (badge) {
            badge.className = 'badge-status ativa';
            badge.textContent = 'Ativa';
        }
        if (tipo === 'aprovar' && valPendente && valAtiva) {
            valPendente.textContent = Math.max(0, parseInt(valPendente.textContent || 0) - 1);
            valAtiva.textContent = parseInt(valAtiva.textContent || 0) + 1;
        }

        const adminHtml = btnAdmin ? btnAdmin.outerHTML : '';

        actionCell.innerHTML = `
            <a href="${modulosUrl}" class="btn-action" style="background: var(--primary-light); color: var(--primary);" title="Gerenciar Módulos e Acessos">⚙️ Permissões</a>
            <a href="${modulosUrl}" class="btn-action" style="background: rgba(6,182,212,0.15); color: #06b6d4;" title="Configurar Cota de PIX Estático">⚡ Cota PIX</a>
            ${adminHtml}
            <button class="btn-action btn-suspend" onclick="acao('suspender', '${id}', '${nome}', this)">⏸ Suspender</button>
            ${deleteHtml}
        `;
    } else if (tipo === 'suspender') {
        if (badge) {
            badge.className = 'badge-status suspensa';
            badge.textContent = 'Suspensa';
        }
        if (valAtiva && valSuspensa) {
            valAtiva.textContent = Math.max(0, parseInt(valAtiva.textContent || 0) - 1);
            valSuspensa.textContent = parseInt(valSuspensa.textContent || 0) + 1;
        }

        const adminHtml = btnAdmin ? btnAdmin.outerHTML : '';

        actionCell.innerHTML = `
            <a href="${modulosUrl}" class="btn-action" style="background: var(--primary-light); color: var(--primary);" title="Gerenciar Módulos e Acessos">⚙️ Permissões</a>
            <a href="${modulosUrl}" class="btn-action" style="background: rgba(6,182,212,0.15); color: #06b6d4;" title="Configurar Cota de PIX Estático">⚡ Cota PIX</a>
            ${adminHtml}
            <button class="btn-action btn-reactivate" onclick="acao('reativar', '${id}', '${nome}', this)">▶ Reativar</button>
            ${deleteHtml}
        `;
    } else if (tipo === 'rejeitar') {
        if (badge) {
            badge.className = 'badge-status rejeitada';
            badge.textContent = 'Rejeitada';
        }
        if (valPendente) {
            valPendente.textContent = Math.max(0, parseInt(valPendente.textContent || 0) - 1);
        }

        const adminHtml = btnAdmin ? btnAdmin.outerHTML : '';

        actionCell.innerHTML = `
            <a href="${modulosUrl}" class="btn-action" style="background: var(--primary-light); color: var(--primary);" title="Gerenciar Módulos e Acessos">⚙️ Permissões</a>
            <a href="${modulosUrl}" class="btn-action" style="background: rgba(6,182,212,0.15); color: #06b6d4;" title="Configurar Cota de PIX Estático">⚡ Cota PIX</a>
            ${adminHtml}
            <button class="btn-action btn-reactivate" onclick="acao('reativar', '${id}', '${nome}', this)">▶ Reativar</button>
            ${deleteHtml}
        `;
    }

    // Se estiver na aba 'pendente', esvazia a linha suavemente
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'pendente' && (tipo === 'aprovar' || tipo === 'rejeitar')) {
        row.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        row.style.opacity = '0';
        row.style.transform = 'translateX(20px)';
        setTimeout(() => row.remove(), 400);
    }
}

let targetExcluirLojaId = null;
let targetExcluirLojaNome = '';

function abrirModalExcluirLoja(id, nome) {
    targetExcluirLojaId = id;
    targetExcluirLojaNome = nome;
    document.getElementById('nomeExcluirLoja').textContent = nome;
    const input = document.getElementById('inputConfirmarExcluirTexto');
    input.value = '';
    const btn = document.getElementById('btnConfirmarExcluirLoja');
    btn.disabled = true;
    btn.style.opacity = '0.5';
    btn.style.cursor = 'not-allowed';
    btn.innerHTML = 'Excluir Definitivamente';
    
    const modal = document.getElementById('modalExcluirLoja');
    modal.style.display = 'flex';
    input.focus();
}

function fecharModalExcluirLoja() {
    document.getElementById('modalExcluirLoja').style.display = 'none';
    targetExcluirLojaId = null;
}

function validarTextoExclusao() {
    const val = document.getElementById('inputConfirmarExcluirTexto').value.trim();
    const btn = document.getElementById('btnConfirmarExcluirLoja');
    if (val === 'EXCLUIR') {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    } else {
        btn.disabled = true;
        btn.style.opacity = '0.5';
        btn.style.cursor = 'not-allowed';
    }
}

async function executarExcluirLoja() {
    if (!targetExcluirLojaId) return;
    const val = document.getElementById('inputConfirmarExcluirTexto').value.trim();
    if (val !== 'EXCLUIR') return;

    const btn = document.getElementById('btnConfirmarExcluirLoja');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-sm"></span> Excluindo registros...';

    try {
        const res = await fetch(urls.excluir + '?id=' + encodeURIComponent(targetExcluirLojaId), {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
            },
        });
        const data = await res.json();
        showToast(data.success, data.message);

        if (data.success) {
            fecharModalExcluirLoja();
            const row = document.getElementById('row-' + targetExcluirLojaId);
            if (row) {
                // Diminui contadores
                const badge = row.querySelector('.badge-status');
                const match = badge ? badge.className.match(/(pendente|ativa|suspensa|rejeitada)/) : null;
                const status = match ? match[0] : null;
                if (status) {
                    const el = document.getElementById('val-' + status);
                    if (el) el.textContent = Math.max(0, parseInt(el.textContent || 0) - 1);
                }
                const elTotal = document.getElementById('val-total');
                if (elTotal) elTotal.textContent = Math.max(0, parseInt(elTotal.textContent || 0) - 1);

                row.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                row.style.opacity = '0';
                row.style.transform = 'scale(0.95)';
                setTimeout(() => row.remove(), 400);
            }
        } else {
            btn.disabled = false;
            btn.innerHTML = 'Excluir Definitivamente';
        }
    } catch (e) {
        showToast(false, 'Erro ao comunicar com o servidor.');
        btn.disabled = false;
        btn.innerHTML = 'Excluir Definitivamente';
    }
}

function showToast(success, msg) {
    const t = document.getElementById('toast');
    t.textContent = (success ? '✅ ' : '❌ ') + msg;
    t.className = 'show ' + (success ? 'success' : 'error');
    setTimeout(() => t.className = '', 4000);
}
</script>
</body>
</html>
