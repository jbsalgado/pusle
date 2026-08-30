<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatura <?= $fatura->mes_referencia ?> — <?= htmlspecialchars($fatura->usuario ? $fatura->usuario->nome : 'Loja') ?> | PULSE SAAS</title>
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
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; padding: 40px 20px; }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border); padding-bottom: 24px; margin-bottom: 24px; }
        .logo { font-size: 24px; font-weight: 900; }
        .logo span { color: var(--primary); }
        .badge-status { display: inline-block; padding: 6px 14px; border-radius: 50px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .badge-paga { background: rgba(67,233,123,0.15); color: var(--green); }
        .badge-pendente { background: rgba(255,209,102,0.15); color: var(--yellow); }
        .badge-atrasada { background: rgba(255,101,132,0.15); color: var(--red); }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px; }
        .info-label { font-size: 12px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px; }
        .info-val { font-size: 15px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { text-align: left; padding: 12px; font-size: 12px; text-transform: uppercase; color: var(--text-muted); border-bottom: 1px solid var(--border); }
        td { padding: 14px 12px; font-size: 14px; border-bottom: 1px solid var(--border); }
        .total-box { display: flex; justify-content: flex-end; margin-top: 20px; }
        .total-card { width: 300px; background: var(--bg-input); border-radius: 10px; padding: 20px; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: var(--text-muted); }
        .total-final { display: flex; justify-content: space-between; font-size: 18px; font-weight: 800; color: var(--green); border-top: 1px solid var(--border); padding-top: 12px; margin-top: 8px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; border: none; }
        .btn-success { background: var(--green); color: #0D0E1F; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
    </style>
</head>
<body>
<div class="invoice-box">
    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div style="background: rgba(67,233,123,0.15); border: 1px solid var(--green); color: var(--green); padding: 14px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fa fa-check-circle"></i> <?= Yii::$app->session->getFlash('success') ?>
        </div>
    <?php endif; ?>

    <div class="header">
        <div>
            <div class="logo">PULSE <span>SAAS</span></div>
            <p style="color: var(--text-muted); font-size: 13px; margin-top: 4px;">Fatura de Serviços de Software & Integração</p>
        </div>
        <div style="text-align: right;">
            <span class="badge-status badge-<?= $fatura->status ?>"><?= htmlspecialchars($fatura->status) ?></span>
            <p style="color: var(--text-muted); font-size: 12px; margin-top: 6px;">Ref: <b><?= htmlspecialchars($fatura->mes_referencia) ?></b></p>
        </div>
    </div>

    <div class="info-grid">
        <div>
            <div class="info-label">Dados do Lojista (Tenant)</div>
            <div class="info-val"><?= htmlspecialchars($fatura->usuario ? $fatura->usuario->nome : 'Loja') ?></div>
            <div style="color: var(--text-muted); font-size: 13px;"><?= htmlspecialchars($fatura->usuario ? $fatura->usuario->email : '') ?></div>
            <div style="color: var(--text-muted); font-size: 13px;">Plano: <b><?= htmlspecialchars($lojaConfig->plano ? $lojaConfig->plano->nome : 'Personalizado') ?></b></div>
        </div>
        <div>
            <div class="info-label">Datas & Prazos</div>
            <div class="info-val">Fechamento: <?= date('d/m/Y', strtotime($fatura->data_fechamento)) ?></div>
            <div class="info-val" style="color: var(--yellow);">Vencimento: <?= date('d/m/Y', strtotime($fatura->data_vencimento)) ?></div>
            <?php if ($fatura->data_pagamento): ?>
                <div style="color: var(--green); font-size: 13px; margin-top: 4px;"><i class="fa fa-check"></i> Pago em: <?= date('d/m/Y H:i', strtotime($fatura->data_pagamento)) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Descrição do Serviço / Apuração</th>
                <th>Base de Cálculo (GMV)</th>
                <th>Alíquota</th>
                <th style="text-align: right;">Valor</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><b>Mensalidade do Plano SaaS</b></td>
                <td>-</td>
                <td>Fixa</td>
                <td style="text-align: right;">R$ <?= number_format($fatura->valor_mensalidade, 2, ',', '.') ?></td>
            </tr>
            <tr>
                <td><b>Comissão sobre Vendas em Marketplaces</b> (MELI, Shopee, etc.)</td>
                <td>R$ <?= number_format($fatura->gmv_marketplace, 2, ',', '.') ?> (<?= $fatura->total_pedidos_marketplace ?> pedidos)</td>
                <td><?= $lojaConfig->getTaxaMarketplaceEfetiva() ?>%</td>
                <td style="text-align: right;">R$ <?= number_format($fatura->valor_comissao_marketplace, 2, ',', '.') ?></td>
            </tr>
            <?php if ($fatura->valor_pedidos_excedentes > 0): ?>
                <tr>
                    <td><b>Tarifa de Pedidos Excedentes da Franquia</b></td>
                    <td>-</td>
                    <td>Adicional</td>
                    <td style="text-align: right;">R$ <?= number_format($fatura->valor_pedidos_excedentes, 2, ',', '.') ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="total-box">
        <div class="total-card">
            <div class="total-row"><span>Mensalidade:</span><span>R$ <?= number_format($fatura->valor_mensalidade, 2, ',', '.') ?></span></div>
            <div class="total-row"><span>Comissões:</span><span>R$ <?= number_format($fatura->valor_comissao_marketplace, 2, ',', '.') ?></span></div>
            <div class="total-final"><span>Total a Pagar:</span><span>R$ <?= number_format($fatura->valor_total, 2, ',', '.') ?></span></div>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; border-top: 1px solid var(--border); padding-top: 20px;">
        <a href="/admin/financeiro/faturas" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Voltar</a>
        
        <?php if ($fatura->status !== \app\modules\admin\models\SaasFatura::STATUS_PAGA): ?>
            <a href="/admin/financeiro/fatura-pagar-manual?id=<?= $fatura->id ?>" class="btn btn-success" onclick="return confirm('Confirmar baixa manual desta fatura como PAGA?');">
                <i class="fa fa-check"></i> Marcar como Paga (Baixa Manual)
            </a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
