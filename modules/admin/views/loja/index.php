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

        .date-info { font-size: 12px; color: var(--text-muted); }

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
                    <div class="stat-value"><?= $contadores['pendente'] ?></div>
                    <div class="stat-label">⏳ Aguardando Aprovação</div>
                </a>
                <a href="?status=ativa" class="stat-card ativa <?= $status === 'ativa' ? 'active-filter' : '' ?>">
                    <div class="stat-value"><?= $contadores['ativa'] ?></div>
                    <div class="stat-label">✅ Lojas Ativas</div>
                </a>
                <a href="?status=suspensa" class="stat-card suspensa <?= $status === 'suspensa' ? 'active-filter' : '' ?>">
                    <div class="stat-value"><?= $contadores['suspensa'] ?></div>
                    <div class="stat-label">⚠️ Suspensas</div>
                </a>
                <a href="?status=todos" class="stat-card rejeitada <?= $status === 'todos' ? 'active-filter' : '' ?>">
                    <div class="stat-value"><?= array_sum($contadores) ?></div>
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
                            </td>
                            <td>
                                <?php if ($loja->status_loja === 'pendente'): ?>
                                    <button class="btn-action btn-approve" onclick="acao('aprovar', '<?= Html::encode($loja->id) ?>', '<?= Html::encode($loja->nome) ?>')">
                                        ✅ Aprovar
                                    </button>
                                    <button class="btn-action btn-reject" onclick="acao('rejeitar', '<?= Html::encode($loja->id) ?>', '<?= Html::encode($loja->nome) ?>')">
                                        ✕ Rejeitar
                                    </button>
                                <?php elseif ($loja->status_loja === 'ativa'): ?>
                                    <button class="btn-action btn-suspend" onclick="acao('suspender', '<?= Html::encode($loja->id) ?>', '<?= Html::encode($loja->nome) ?>')">
                                        ⏸ Suspender
                                    </button>
                                <?php else: ?>
                                    <button class="btn-action btn-reactivate" onclick="acao('reativar', '<?= Html::encode($loja->id) ?>', '<?= Html::encode($loja->nome) ?>')">
                                        ▶ Reativar
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

<script>
const urls = {
    aprovar:   '<?= Url::to(['/admin/loja/aprovar']) ?>',
    suspender: '<?= Url::to(['/admin/loja/suspender']) ?>',
    rejeitar:  '<?= Url::to(['/admin/loja/rejeitar']) ?>',
    reativar:  '<?= Url::to(['/admin/loja/reativar']) ?>',
};

async function acao(tipo, id, nome) {
    const labels = {
        aprovar: `Aprovar a loja "${nome}"?`,
        suspender: `Suspender a loja "${nome}"?`,
        rejeitar: `Rejeitar o cadastro de "${nome}"? Esta ação não pode ser desfeita facilmente.`,
        reativar: `Reativar a loja "${nome}"?`,
    };

    if (!confirm(labels[tipo])) return;

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
            setTimeout(() => location.reload(), 1800);
        }
    } catch (e) {
        showToast(false, 'Erro de conexão. Tente novamente.');
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
