<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Relatório de Faturas | PULSE SAAS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: var(--bg-card); border-right: 1px solid var(--border); padding: 24px 0; flex-shrink: 0; display: flex; flex-direction: column; }
        .sidebar-brand { padding: 0 24px 28px; border-bottom: 1px solid var(--border); margin-bottom: 16px; }
        .sidebar-brand .logo { font-size: 22px; font-weight: 900; letter-spacing: -0.5px; }
        .sidebar-brand .logo span { color: var(--primary); }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 24px; color: var(--text-muted); text-decoration: none; font-size: 14px; font-weight: 500; margin: 2px 8px; border-radius: 10px; }
        .nav-item:hover { color: var(--text); background: rgba(255,255,255,0.04); }
        .nav-item.active { color: var(--primary); background: var(--primary-light); }
        .nav-icon { font-size: 16px; width: 20px; text-align: center; }
        .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 20px 32px; border-bottom: 1px solid var(--border); background: var(--bg-card); }
        .topbar h1 { font-size: 20px; font-weight: 700; }
        .content { padding: 32px; overflow-y: auto; flex: 1; }
        .card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; border: none; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
        table { width: 100%; border-collapse: collapse; text-align: left; margin-top: 16px; }
        th { padding: 14px 16px; font-size: 12px; text-transform: uppercase; color: var(--text-muted); border-bottom: 1px solid var(--border); }
        td { padding: 16px; font-size: 14px; border-bottom: 1px solid var(--border); }
        tr:hover td { background: rgba(255,255,255,0.02); }
        .badge-status { display: inline-block; padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-paga { background: rgba(67,233,123,0.15); color: var(--green); }
        .badge-pendente { background: rgba(255,209,102,0.15); color: var(--yellow); }
        .badge-atrasada { background: rgba(255,101,132,0.15); color: var(--red); }
        .filter-bar { display: flex; gap: 12px; margin-bottom: 20px; align-items: center; flex-wrap: wrap; }
        .select-input, .text-input { background: var(--bg-input); border: 1px solid var(--border); color: var(--text); padding: 8px 14px; border-radius: 8px; font-size: 13px; outline: none; }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="logo">PULSE <span>SAAS</span></div>
        </div>
        <nav>
            <a href="/admin/loja/index" class="nav-item"><i class="fa fa-store nav-icon"></i> Gerenciar Lojas</a>
            <a href="/admin/financeiro/index" class="nav-item"><i class="fa fa-chart-line nav-icon"></i> Dashboard Financeiro</a>
            <a href="/admin/financeiro/faturas" class="nav-item active"><i class="fa fa-file-invoice-dollar nav-icon"></i> Faturas das Lojas</a>
            <a href="/admin/financeiro/planos" class="nav-item"><i class="fa fa-tags nav-icon"></i> Planos & Comissões</a>
            <a href="/admin/financeiro/config" class="nav-item"><i class="fa fa-sliders nav-icon"></i> Configurações Master</a>
        </nav>
    </aside>

    <div class="main">
        <header class="topbar">
            <h1><i class="fa fa-file-invoice-dollar" style="color: var(--primary);"></i> Faturas de Lojistas</h1>
        </header>

        <div class="content">
            <div class="card">
                <form method="get" class="filter-bar">
                    <select name="status" class="select-input">
                        <option value="todos" <?= $status === 'todos' ? 'selected' : '' ?>>Todos os Status</option>
                        <option value="pendente" <?= $status === 'pendente' ? 'selected' : '' ?>>Pendentes</option>
                        <option value="paga" <?= $status === 'paga' ? 'selected' : '' ?>>Pagas</option>
                        <option value="atrasada" <?= $status === 'atrasada' ? 'selected' : '' ?>>Atrasadas</option>
                    </select>

                    <input type="text" name="mes" value="<?= htmlspecialchars($mes) ?>" placeholder="Mês (Ex: 2026-08)" class="text-input" style="width: 160px;">

                    <button type="submit" class="btn btn-outline"><i class="fa fa-filter"></i> Filtrar</button>
                </form>

                <?php if (empty($faturas)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 40px;">Nenhuma fatura encontrada com os filtros selecionados.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Lojista / Tenant</th>
                                <th>Mês Ref.</th>
                                <th>Fechamento</th>
                                <th>Vencimento</th>
                                <th>GMV Vendas</th>
                                <th>Valor Total</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($faturas as $f): ?>
                                <tr>
                                    <td>
                                        <b><?= htmlspecialchars($f->usuario ? $f->usuario->nome : 'Loja') ?></b><br>
                                        <small style="color: var(--text-muted);"><?= htmlspecialchars($f->usuario ? $f->usuario->email : '') ?></small>
                                    </td>
                                    <td><b><?= htmlspecialchars($f->mes_referencia) ?></b></td>
                                    <td><?= date('d/m/Y', strtotime($f->data_fechamento)) ?></td>
                                    <td><?= date('d/m/Y', strtotime($f->data_vencimento)) ?></td>
                                    <td>R$ <?= number_format($f->gmv_marketplace + $f->gmv_catalogo, 2, ',', '.') ?></td>
                                    <td style="font-weight: 800; color: var(--green);">R$ <?= number_format($f->valor_total, 2, ',', '.') ?></td>
                                    <td>
                                        <span class="badge-status badge-<?= $f->status ?>">
                                            <?= htmlspecialchars($f->status) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="/admin/financeiro/fatura-view?id=<?= $f->id ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px;">
                                            <i class="fa fa-eye"></i> Ver / Baixar
                                        </a>
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
</body>
</html>
