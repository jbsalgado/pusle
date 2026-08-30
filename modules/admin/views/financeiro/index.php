<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Dashboard Financeiro & Comissões | PULSE SAAS</title>
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
        .sidebar {
            width: 260px;
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
            transition: color 0.2s, background 0.2s;
            margin: 2px 8px;
            border-radius: 10px;
        }
        .nav-item:hover { color: var(--text); background: rgba(255,255,255,0.04); }
        .nav-item.active { color: var(--primary); background: var(--primary-light); }
        .nav-icon { font-size: 16px; width: 20px; text-align: center; }
        
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
        .content { padding: 32px; overflow-y: auto; flex: 1; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }
        .stat-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 4px; height: 100%;
            background: var(--primary);
        }
        .stat-card.green::after { background: var(--green); }
        .stat-card.yellow::after { background: var(--yellow); }
        .stat-card.blue::after { background: var(--blue); }

        .stat-label { font-size: 13px; color: var(--text-muted); font-weight: 500; }
        .stat-val { font-size: 26px; font-weight: 800; color: var(--text); }
        .stat-desc { font-size: 12px; color: var(--text-muted); }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            margin-bottom: 32px;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .card-title { font-size: 17px; font-weight: 700; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: opacity 0.2s;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-success { background: var(--green); color: #0D0E1F; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn:hover { opacity: 0.88; }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 14px 16px; font-size: 12px; text-transform: uppercase; color: var(--text-muted); border-bottom: 1px solid var(--border); }
        td { padding: 16px; font-size: 14px; border-bottom: 1px solid var(--border); }
        tr:hover td { background: rgba(255,255,255,0.02); }

        .badge-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-paga { background: rgba(67,233,123,0.15); color: var(--green); }
        .badge-pendente { background: rgba(255,209,102,0.15); color: var(--yellow); }
        .badge-atrasada { background: rgba(255,101,132,0.15); color: var(--red); }
    </style>
</head>
<body>
<div class="layout">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="logo">PULSE <span>SAAS</span></div>
            <div class="badge">PAINEL SUPERADMIN</div>
        </div>

        <nav>
            <a href="/admin/loja/index" class="nav-item">
                <i class="fa fa-store nav-icon"></i> Gerenciar Lojas
            </a>
            <a href="/admin/financeiro/index" class="nav-item active">
                <i class="fa fa-chart-line nav-icon"></i> Dashboard Financeiro
            </a>
            <a href="/admin/financeiro/faturas" class="nav-item">
                <i class="fa fa-file-invoice-dollar nav-icon"></i> Faturas das Lojas
            </a>
            <a href="/admin/financeiro/planos" class="nav-item">
                <i class="fa fa-tags nav-icon"></i> Planos & Comissões
            </a>
            <a href="/admin/financeiro/config" class="nav-item">
                <i class="fa fa-sliders nav-icon"></i> Configurações Master
            </a>
        </nav>

        <div style="margin-top: auto; padding: 20px;">
            <a href="/vendas/inicio/index" class="btn btn-outline" style="width: 100%; justify-content: center;">
                <i class="fa fa-arrow-left"></i> Voltar ao ERP
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <div class="main">
        <header class="topbar">
            <h1><i class="fa fa-chart-pie" style="color: var(--primary);"></i> Faturamento Global & Monetização</h1>
            <div>
                <form action="/admin/financeiro/gerar-faturas" method="post" style="display: inline;" onsubmit="return confirm('Deseja calcular e gerar o fechamento de faturas para todas as lojas?');">
                    <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
                    <input type="hidden" name="mes" value="<?= $mesAnterior ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-sync"></i> Rodar Fechamento (<?= $mesAnterior ?>)
                    </button>
                </form>
            </div>
        </header>

        <div class="content">
            <?php if (Yii::$app->session->hasFlash('success')): ?>
                <div style="background: rgba(67,233,123,0.15); border: 1px solid var(--green); color: var(--green); padding: 16px; border-radius: var(--radius); margin-bottom: 24px;">
                    <i class="fa fa-check-circle"></i> <?= Yii::$app->session->getFlash('success') ?>
                </div>
            <?php endif; ?>

            <!-- STATS CARDS -->
            <div class="stats-grid">
                <div class="stat-card green">
                    <div class="stat-label">Faturamento do SaaS (<?= $mesAnterior ?>)</div>
                    <div class="stat-val">R$ <?= number_format($totalFaturadoMes, 2, ',', '.') ?></div>
                    <div class="stat-desc">Recebido: R$ <?= number_format($totalRecebidoMes, 2, ',', '.') ?> | A receber: R$ <?= number_format($totalPendenteMes, 2, ',', '.') ?></div>
                </div>

                <div class="stat-card blue">
                    <div class="stat-label">GMV Transacionado nas Lojas</div>
                    <div class="stat-val">R$ <?= number_format($gmvMarketplaceTotal + $gmvCatalogoTotal, 2, ',', '.') ?></div>
                    <div class="stat-desc">Marketplaces: R$ <?= number_format($gmvMarketplaceTotal, 2, ',', '.') ?> | Catálogo: R$ <?= number_format($gmvCatalogoTotal, 2, ',', '.') ?></div>
                </div>

                <div class="stat-card yellow">
                    <div class="stat-label">Total de Lojas Conectadas</div>
                    <div class="stat-val"><?= $totalLojas ?></div>
                    <div class="stat-desc">Tenants ativos no Pulse ERP</div>
                </div>
            </div>

            <!-- TABELA DE FATURAS RECENTES -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa fa-receipt"></i> Últimas Faturas Geradas</h3>
                    <a href="/admin/financeiro/faturas" class="btn btn-outline btn-sm">Ver Todas</a>
                </div>

                <?php if (empty($ultimasFaturas)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 30px;">
                        Nenhuma fatura apurada ainda. Clique em <b>"Rodar Fechamento"</b> no topo para calcular o mês anterior.
                    </p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Loja / Tenant</th>
                                <th>Mês Ref.</th>
                                <th>GMV Vendas (Mktp + Catálogo)</th>
                                <th>Comissões</th>
                                <th>Mensalidade</th>
                                <th>Total a Pagar</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimasFaturas as $f): ?>
                                <tr>
                                    <td>
                                        <b><?= htmlspecialchars($f->usuario ? $f->usuario->nome : 'Loja') ?></b><br>
                                        <small style="color: var(--text-muted);"><?= htmlspecialchars($f->usuario ? $f->usuario->email : '') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($f->mes_referencia) ?></td>
                                    <td>R$ <?= number_format($f->gmv_marketplace + $f->gmv_catalogo, 2, ',', '.') ?></td>
                                    <td>R$ <?= number_format($f->valor_comissao_marketplace + $f->valor_comissao_catalogo, 2, ',', '.') ?></td>
                                    <td>R$ <?= number_format($f->valor_mensalidade, 2, ',', '.') ?></td>
                                    <td style="font-weight: 800; color: var(--green);">R$ <?= number_format($f->valor_total, 2, ',', '.') ?></td>
                                    <td>
                                        <span class="badge-status badge-<?= $f->status ?>">
                                            <?= htmlspecialchars($f->status) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="/admin/financeiro/fatura-view?id=<?= $f->id ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px;">
                                            <i class="fa fa-eye"></i> Detalhes
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
