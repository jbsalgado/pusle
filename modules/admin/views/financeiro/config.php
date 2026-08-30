<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Configurações Globais do SaaS | PULSE SAAS</title>
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
        .card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 32px; max-width: 800px; }
        .form-group { margin-bottom: 24px; }
        label { display: block; font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
        .help-text { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
        .form-control { width: 100%; background: var(--bg-input); border: 1px solid var(--border); color: var(--text); padding: 10px 14px; border-radius: 8px; font-size: 14px; outline: none; }
        .form-control:focus { border-color: var(--primary); }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; border: none; }
        .btn-success { background: var(--green); color: #0D0E1F; }
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
            <a href="/admin/financeiro/planos" class="nav-item"><i class="fa fa-tags nav-icon"></i> Planos & Comissões</a>
            <a href="/admin/financeiro/config" class="nav-item active"><i class="fa fa-sliders nav-icon"></i> Configurações Master</a>
        </nav>
    </aside>

    <div class="main">
        <header class="topbar">
            <h1><i class="fa fa-sliders" style="color: var(--primary);"></i> Configurações Master do SaaS</h1>
        </header>

        <div class="content">
            <?php if (Yii::$app->session->hasFlash('success')): ?>
                <div style="background: rgba(67,233,123,0.15); border: 1px solid var(--green); color: var(--green); padding: 14px; border-radius: 8px; margin-bottom: 20px; max-width: 800px;">
                    <i class="fa fa-check-circle"></i> <?= Yii::$app->session->getFlash('success') ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <form method="post">
                    <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">

                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 16px; color: var(--primary);"><i class="fa fa-credit-card"></i> Credenciais Master do Mercado Pago (Split de Pagamento)</h3>
                    
                    <div class="form-group">
                        <label>Mercado Pago Master Access Token</label>
                        <input type="password" name="Config[mercado_pago_master_access_token]" value="<?= htmlspecialchars($configs['mercado_pago_master_access_token'] ?? '') ?>" class="form-control" placeholder="APP_USR-...">
                        <div class="help-text">Token da conta bancária master do dono do SaaS para receber os splits em tempo real.</div>
                    </div>

                    <div class="form-group">
                        <label>Mercado Pago Master Sponsor / User ID</label>
                        <input type="text" name="Config[mercado_pago_master_sponsor_id]" value="<?= htmlspecialchars($configs['mercado_pago_master_sponsor_id'] ?? '') ?>" class="form-control" placeholder="Ex: 436828978">
                        <div class="help-text">Identificador numérico do sponsor / marketplace do SaaS.</div>
                    </div>

                    <hr style="border-color: var(--border); margin: 28px 0;">

                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 16px; color: var(--yellow);"><i class="fa fa-clock"></i> Régua de Cobrança & Prazos</h3>

                    <div class="form-group">
                        <label>Dias de Carência antes do Bloqueio Automático</label>
                        <input type="number" name="Config[dias_carencia_inadimplencia]" value="<?= htmlspecialchars($configs['dias_carencia_inadimplencia'] ?? '5') ?>" class="form-control" style="max-width: 150px;">
                        <div class="help-text">Quantidade de dias após o vencimento da fatura que o sistema aguarda antes de pausar os acessos e sincronizações do lojista.</div>
                    </div>

                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Salvar Configurações Master</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
