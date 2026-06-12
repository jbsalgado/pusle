<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitação Enviada — PULSE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0A0B1E;
            color: #F0F0FF;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: radial-gradient(ellipse 60% 50% at 50% 30%, rgba(67,233,123,0.12) 0%, transparent 60%);
            pointer-events: none;
        }
        .card {
            background: #141527;
            border: 1px solid rgba(67,233,123,0.25);
            border-radius: 24px;
            padding: 60px 48px;
            max-width: 540px;
            width: 100%;
            text-align: center;
            box-shadow: 0 40px 80px rgba(0,0,0,0.5);
            position: relative;
            overflow: hidden;
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #43E97B, #6C63FF);
        }
        .icon-wrap {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: rgba(67,233,123,0.12);
            border: 2px solid rgba(67,233,123,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 32px;
            animation: pulse-icon 2s infinite;
        }
        @keyframes pulse-icon {
            0%, 100% { box-shadow: 0 0 0 0 rgba(67,233,123,0.3); }
            50% { box-shadow: 0 0 0 16px rgba(67,233,123,0); }
        }
        h1 {
            font-size: 32px;
            font-weight: 900;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }
        h1 span {
            background: linear-gradient(135deg, #43E97B, #6C63FF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .subtitle {
            font-size: 16px;
            color: #8887A8;
            line-height: 1.7;
            margin-bottom: 36px;
        }
        .steps {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 40px;
            text-align: left;
        }
        .step-item {
            display: flex;
            align-items: center;
            gap: 16px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: 16px;
        }
        .step-num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(108,99,255,0.2);
            color: #6C63FF;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .step-text { font-size: 14px; color: #8887A8; line-height: 1.5; }
        .step-text strong { color: #F0F0FF; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 32px;
            background: linear-gradient(135deg, #6C63FF, #5A52D5);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(108,99,255,0.45);
        }
    </style>
</head>
<body>

<?php use yii\helpers\Html; ?>

<div class="card">
    <div class="icon-wrap">🎉</div>

    <h1>Solicitação <span>enviada!</span></h1>

    <p class="subtitle">
        Olá, <strong><?= Html::encode($nome) ?>!</strong> Sua loja foi cadastrada com sucesso
        e já está na fila de aprovação. Você será notificado assim que ela for ativada.
    </p>

    <div class="steps">
        <div class="step-item">
            <div class="step-num">1</div>
            <div class="step-text">
                <strong>Solicitação recebida ✅</strong><br>
                Sua loja foi cadastrada e o administrador foi notificado.
            </div>
        </div>
        <div class="step-item">
            <div class="step-num">2</div>
            <div class="step-text">
                <strong>Confirmação de pagamento</strong><br>
                O time irá verificar e confirmar o pagamento da assinatura.
            </div>
        </div>
        <div class="step-item">
            <div class="step-num">3</div>
            <div class="step-text">
                <strong>Loja ativada 🚀</strong><br>
                Você receberá um WhatsApp assim que sua loja estiver pronta!
            </div>
        </div>
    </div>

    <a href="<?= \yii\helpers\Url::to(['/auth/login']) ?>" class="btn">
        🔑 Acessar o Sistema
    </a>
</div>

</body>
</html>
