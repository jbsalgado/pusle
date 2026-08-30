<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Planos Comerciais & Comissões | PULSE SAAS</title>
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
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; border: none; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-top: 24px; }
        .plan-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 28px; position: relative; display: flex; flex-direction: column; justify-content: space-between; }
        .plan-card.featured { border-color: var(--primary); box-shadow: 0 0 30px rgba(108,99,255,0.2); }
        .plan-badge { position: absolute; top: 16px; right: 16px; background: var(--primary); color: #fff; font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 50px; text-transform: uppercase; }
        .plan-name { font-size: 20px; font-weight: 800; margin-bottom: 8px; }
        .plan-price { font-size: 32px; font-weight: 900; color: var(--green); margin-bottom: 16px; }
        .plan-price span { font-size: 14px; font-weight: 500; color: var(--text-muted); }
        .plan-feature { display: flex; align-items: center; gap: 10px; font-size: 14px; color: var(--text); margin-bottom: 12px; }
        .plan-feature i { color: var(--primary); width: 16px; }
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
            <a href="/admin/financeiro/faturas" class="nav-item"><i class="fa fa-file-invoice-dollar nav-icon"></i> Faturas das Lojas</a>
            <a href="/admin/financeiro/planos" class="nav-item active"><i class="fa fa-tags nav-icon"></i> Planos & Comissões</a>
            <a href="/admin/financeiro/config" class="nav-item"><i class="fa fa-sliders nav-icon"></i> Configurações Master</a>
        </nav>
    </aside>

    <div class="main">
        <header class="topbar">
            <h1><i class="fa fa-tags" style="color: var(--primary);"></i> Planos Comerciais e Taxas do SaaS</h1>
            <a href="/admin/financeiro/plano-form" class="btn btn-primary"><i class="fa fa-plus"></i> Novo Plano</a>
        </header>

        <div class="content">
            <?php if (Yii::$app->session->hasFlash('success')): ?>
                <div style="background: rgba(67,233,123,0.15); border: 1px solid var(--green); color: var(--green); padding: 14px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fa fa-check-circle"></i> <?= Yii::$app->session->getFlash('success') ?>
                </div>
            <?php endif; ?>

            <div class="plans-grid">
                <?php foreach ($planos as $p): ?>
                    <div class="plan-card <?= $p->destaque ? 'featured' : '' ?>">
                        <?php if ($p->destaque): ?>
                            <div class="plan-badge">Mais Popular</div>
                        <?php endif; ?>

                        <div>
                            <div class="plan-name"><?= htmlspecialchars($p->nome) ?></div>
                            <div class="plan-price">R$ <?= number_format($p->valor_mensalidade, 2, ',', '.') ?> <span>/ mês</span></div>
                            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;"><?= htmlspecialchars($p->descricao) ?></p>

                            <div class="plan-feature">
                                <i class="fa fa-percentage"></i>
                                <span>Comissão Catálogo (Split): <b><?= $p->percentual_comissao_catalogo ?>%</b></span>
                            </div>
                            <div class="plan-feature">
                                <i class="fa fa-store"></i>
                                <span>Comissão Marketplaces: <b><?= $p->percentual_comissao_marketplace ?>%</b></span>
                            </div>
                            <div class="plan-feature">
                                <i class="fa fa-box"></i>
                                <span>Franquia de Pedidos: <b><?= $p->limite_pedidos_inclusos ?> pedidos/mês</b></span>
                            </div>
                            <div class="plan-feature">
                                <i class="fa fa-plus-circle"></i>
                                <span>Excedente: <b>R$ <?= number_format($p->valor_pedido_excedente, 2, ',', '.') ?>/pedido</b></span>
                            </div>
                        </div>

                        <div style="margin-top: 24px;">
                            <a href="/admin/financeiro/plano-form?id=<?= $p->id ?>" class="btn btn-outline" style="width: 100%; justify-content: center;">
                                <i class="fa fa-edit"></i> Editar Plano
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
