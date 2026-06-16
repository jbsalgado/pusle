<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crie Sua Loja — PULSE Sistema de Gestão</title>
    <meta name="description" content="Abra sua loja virtual em minutos. Gerencie vendas, estoque, clientes e cobranças em um único sistema.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6C63FF;
            --primary-dark: #5A52D5;
            --primary-light: rgba(108, 99, 255, 0.12);
            --secondary: #FF6584;
            --accent: #43E97B;
            --bg: #0A0B1E;
            --bg-card: #141527;
            --bg-input: #1C1D35;
            --text: #F0F0FF;
            --text-muted: #8887A8;
            --border: rgba(108, 99, 255, 0.25);
            --success: #43E97B;
            --error: #FF6584;
            --radius: 16px;
            --radius-sm: 10px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── Animated background ── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 10%, rgba(108,99,255,0.18) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 80% 80%, rgba(255,101,132,0.10) 0%, transparent 60%);
            pointer-events: none;
        }

        /* ── HERO ── */
        .hero {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            max-width: 1280px;
            margin: 0 auto;
            padding: 40px 32px;
            gap: 60px;
            align-items: center;
        }

        @media (max-width: 900px) {
            .hero { grid-template-columns: 1fr; padding: 32px 20px; gap: 40px; }
            .hero-left { order: 2; }
            .hero-right { order: 1; }
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary-light);
            border: 1px solid var(--border);
            color: var(--primary);
            font-size: 13px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 50px;
            margin-bottom: 24px;
            letter-spacing: 0.5px;
        }

        .hero-badge::before { content: '✦'; font-size: 10px; }

        .hero-title {
            font-size: clamp(38px, 5vw, 60px);
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 20px;
            letter-spacing: -1.5px;
        }

        .hero-title span {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-sub {
            font-size: 18px;
            color: var(--text-muted);
            line-height: 1.7;
            margin-bottom: 40px;
            max-width: 460px;
        }

        .features-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 15px;
            color: var(--text-muted);
        }

        .feature-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        /* ── FORM CARD ── */
        .form-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 40px 80px rgba(0,0,0,0.5), 0 0 0 1px rgba(108,99,255,0.1);
            position: relative;
            overflow: hidden;
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .form-title {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .form-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 32px;
        }

        /* ── Steps indicator ── */
        .steps-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 36px;
        }

        .step-dot {
            width: 28px;
            height: 6px;
            border-radius: 3px;
            background: var(--bg-input);
            transition: background 0.3s, width 0.3s;
        }

        .step-dot.active { background: var(--primary); width: 48px; }
        .step-dot.done   { background: var(--accent); }

        .step-label {
            font-size: 12px;
            color: var(--text-muted);
            margin-left: auto;
        }

        /* ── Form steps ── */
        .form-step { display: none; }
        .form-step.active { display: block; }

        /* ── Inputs ── */
        .field-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .field-group.one { grid-template-columns: 1fr; }

        .field-wrap {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 20px;
        }

        .field-wrap label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 0.3px;
        }

        .field-wrap input,
        .field-wrap select {
            width: 100%;
            background: var(--bg-input);
            border: 1.5px solid rgba(255,255,255,0.08);
            border-radius: var(--radius-sm);
            padding: 14px 16px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            color: var(--text);
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .field-wrap input::placeholder { color: var(--text-muted); opacity: 0.5; }

        .field-wrap input:focus,
        .field-wrap select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108,99,255,0.15);
        }

        .field-wrap input.is-error,
        .field-wrap select.is-error { border-color: var(--error); }

        .field-error {
            font-size: 12px;
            color: var(--error);
            margin-top: -12px;
            margin-bottom: 8px;
        }

        /* ── Checkbox ── */
        .check-wrap {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 24px;
            cursor: pointer;
        }

        .check-wrap input[type=checkbox] {
            width: 20px;
            height: 20px;
            accent-color: var(--primary);
            flex-shrink: 0;
            margin-top: 2px;
        }

        .check-wrap span { font-size: 13px; color: var(--text-muted); line-height: 1.5; }
        .check-wrap a { color: var(--primary); text-decoration: none; }

        /* ── Buttons ── */
        .btn-primary {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            font-weight: 700;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s, opacity 0.2s;
            letter-spacing: 0.3px;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(108,99,255,0.45);
        }

        .btn-primary:active { transform: translateY(0); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        .btn-ghost {
            background: none;
            border: 1.5px solid var(--border);
            color: var(--text-muted);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            padding: 12px 24px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: border-color 0.2s, color 0.2s;
        }

        .btn-ghost:hover { border-color: var(--primary); color: var(--primary); }

        .btn-row {
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }

        .btn-row .btn-ghost { flex: 1; }
        .btn-row .btn-primary { flex: 2; }

        .login-link {
            text-align: center;
            margin-top: 28px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .login-link a { color: var(--primary); text-decoration: none; font-weight: 600; }

        /* ── Alert server errors ── */
        .alert-errors {
            background: rgba(255,101,132,0.12);
            border: 1px solid rgba(255,101,132,0.35);
            border-radius: var(--radius-sm);
            padding: 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: var(--error);
            line-height: 1.6;
        }

        /* ── Responsive ── */
        @media (max-width: 600px) {
            .form-card { padding: 32px 24px; }
            .field-group { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>

<div class="hero">

    <!-- LEFT: Marketing -->
    <div class="hero-left">
        <div class="hero-badge">Sistema de Gestão para Lojistas</div>

        <h1 class="hero-title">
            Sua loja, <br>
            sua <span>regra</span>.
        </h1>

        <p class="hero-sub">
            Do cadastro de produtos à cobrança automatizada. O PULSE é o sistema
            completo que cresce com o seu negócio — sem complicação.
        </p>

        <div class="features-list">
            <div class="feature-item">
                <div class="feature-icon">🛒</div>
                <span>Vendas, orçamentos e PDV em um único painel</span>
            </div>
            <div class="feature-item">
                <div class="feature-icon">📦</div>
                <span>Controle de estoque em tempo real com alertas</span>
            </div>
            <div class="feature-item">
                <div class="feature-icon">💬</div>
                <span>Cobranças e recibos automáticos via WhatsApp</span>
            </div>
            <div class="feature-item">
                <div class="feature-icon">📊</div>
                <span>Dashboard financeiro completo com relatórios</span>
            </div>
            <div class="feature-item">
                <div class="feature-icon">👥</div>
                <span>Gerencie colaboradores com controle de comissões</span>
            </div>
        </div>
    </div>

    <!-- RIGHT: Form -->
    <div class="hero-right">
        <div class="form-card">
            <div class="form-title">Crie sua loja grátis</div>
            <div class="form-subtitle">Configuração em 3 minutos. Sem cartão de crédito.</div>

            <!-- Steps bar -->
            <div class="steps-bar">
                <div class="step-dot active" id="dot-1"></div>
                <div class="step-dot" id="dot-2"></div>
                <div class="step-dot" id="dot-3"></div>
                <span class="step-label" id="step-label">Passo 1 de 3</span>
            </div>

            <?php if (!empty($model->errors)): ?>
            <div class="alert-errors">
                <?php foreach ($model->errors as $field => $errs): ?>
                    <?php foreach ($errs as $err): ?>
                        ⚠️ <?= Html::encode($err) ?><br>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php
            $form = ActiveForm::begin([
                'id'     => 'form-nova-loja',
                'action' => ['/loja-cadastro/salvar'],
                'method' => 'POST',
                'enableClientValidation' => false,
                'enableAjaxValidation'   => false,
                'options' => ['autocomplete' => 'on'],
                'fieldConfig' => [
                    'template' => '{input}{error}',
                    'errorOptions' => ['class' => 'field-error'],
                ],
            ]);
            ?>

            <!-- STEP 1: Dados Pessoais -->
            <div class="form-step active" id="step-1">
                <div class="field-group">
                    <div class="field-wrap">
                        <label for="signupform-nome">Nome Completo *</label>
                        <?= $form->field($model, 'nome')->textInput([
                            'id'          => 'signupform-nome',
                            'placeholder' => 'Seu nome',
                            'autofocus'   => true,
                        ])->label(false) ?>
                    </div>
                    <div class="field-wrap">
                        <label for="signupform-cpf">CPF *</label>
                        <?= $form->field($model, 'cpf')->textInput([
                            'id'          => 'signupform-cpf',
                            'placeholder' => '00000000000',
                            'inputmode'   => 'numeric',
                            'maxlength'   => 11,
                        ])->label(false) ?>
                    </div>
                </div>
                <div class="field-group">
                    <div class="field-wrap">
                        <label for="signupform-email">E-mail *</label>
                        <?= $form->field($model, 'email')->input('email', [
                            'id'          => 'signupform-email',
                            'placeholder' => 'seu@email.com',
                        ])->label(false) ?>
                    </div>
                    <div class="field-wrap">
                        <label for="signupform-telefone">WhatsApp *</label>
                        <?= $form->field($model, 'telefone')->textInput([
                            'id'          => 'signupform-telefone',
                            'placeholder' => '81999990000',
                            'inputmode'   => 'tel',
                            'maxlength'   => 11,
                        ])->label(false) ?>
                    </div>
                </div>
                <button type="button" class="btn-primary" onclick="goStep(2)">
                    Próximo → Dados da Loja
                </button>
            </div>

            <!-- STEP 2: Dados da Loja -->
            <div class="form-step" id="step-2">
                <div class="field-group one">
                    <div class="field-wrap">
                        <label for="signupform-nome_loja">Nome da sua Loja *</label>
                        <?= $form->field($model, 'nome_loja')->textInput([
                            'id'          => 'signupform-nome_loja',
                            'placeholder' => 'Ex: Bazar da Maria, Loja do João...',
                        ])->label(false) ?>
                    </div>
                </div>
                <div class="field-group">
                    <div class="field-wrap">
                        <label for="signupform-cidade">Cidade *</label>
                        <?= $form->field($model, 'cidade')->textInput([
                            'id'          => 'signupform-cidade',
                            'placeholder' => 'Sua cidade',
                        ])->label(false) ?>
                    </div>
                    <div class="field-wrap">
                        <label for="signupform-estado">Estado *</label>
                        <?= $form->field($model, 'estado')->dropDownList(
                            ['AC'=>'AC','AL'=>'AL','AP'=>'AP','AM'=>'AM','BA'=>'BA','CE'=>'CE','DF'=>'DF',
                             'ES'=>'ES','GO'=>'GO','MA'=>'MA','MT'=>'MT','MS'=>'MS','MG'=>'MG','PA'=>'PA',
                             'PB'=>'PB','PR'=>'PR','PE'=>'PE','PI'=>'PI','RJ'=>'RJ','RN'=>'RN','RS'=>'RS',
                             'RO'=>'RO','RR'=>'RR','SC'=>'SC','SP'=>'SP','SE'=>'SE','TO'=>'TO'],
                            ['id' => 'signupform-estado', 'prompt' => 'Selecione...']
                        )->label(false) ?>
                    </div>
                </div>
                <div class="btn-row">
                    <button type="button" class="btn-ghost" onclick="goStep(1)">← Voltar</button>
                    <button type="button" class="btn-primary" onclick="goStep(3)">Próximo → Senha</button>
                </div>
            </div>

            <!-- STEP 3: Senha e Termos -->
            <div class="form-step" id="step-3">
                <div class="field-group">
                    <div class="field-wrap">
                        <label for="signupform-senha">Senha *</label>
                        <?= $form->field($model, 'senha')->passwordInput([
                            'id'          => 'signupform-senha',
                            'placeholder' => 'Mínimo 6 caracteres',
                        ])->label(false) ?>
                    </div>
                    <div class="field-wrap">
                        <label for="signupform-senha_confirmacao">Confirmar Senha *</label>
                        <?= $form->field($model, 'senha_confirmacao')->passwordInput([
                            'id'          => 'signupform-senha_confirmacao',
                            'placeholder' => 'Repita a senha',
                        ])->label(false) ?>
                    </div>
                </div>

                <label class="check-wrap">
                    <?= $form->field($model, 'termos_aceitos')->checkbox([
                        'id'       => 'signupform-termos_aceitos',
                        'template' => '{input}',
                        'value'    => 1,
                    ])->label(false) ?>
                    <span>Concordo com os <a href="#" target="_blank">Termos de Uso</a> e a
                        <a href="#" target="_blank">Política de Privacidade</a></span>
                </label>

                <div class="btn-row">
                    <button type="button" class="btn-ghost" onclick="goStep(2)">← Voltar</button>
                    <button type="submit" id="btn-submit" class="btn-primary">
                        🚀 Criar Minha Loja
                    </button>
                </div>
            </div>

            <?php ActiveForm::end(); ?>

            <div class="login-link">
                Já tem uma conta? <a href="<?= \yii\helpers\Url::to(['/auth/login']) ?>">Entrar</a>
            </div>
        </div>
    </div>
</div>

<script>
    let currentStep = 1;
    const totalSteps = 3;

    function goStep(step) {
        if (step > currentStep) {
            // Valida antes de avançar
            if (!validateStep(currentStep)) return;
        }

        document.getElementById('step-' + currentStep).classList.remove('active');
        document.getElementById('step-' + step).classList.add('active');

        // Atualiza dots
        for (let i = 1; i <= totalSteps; i++) {
            const dot = document.getElementById('dot-' + i);
            dot.classList.remove('active', 'done');
            if (i < step) dot.classList.add('done');
            else if (i === step) dot.classList.add('active');
        }

        document.getElementById('step-label').textContent = 'Passo ' + step + ' de ' + totalSteps;
        currentStep = step;
    }

    function validateStep(step) {
        const required = {
            1: ['signupform-nome', 'signupform-cpf', 'signupform-email', 'signupform-telefone'],
            2: ['signupform-nome_loja', 'signupform-cidade', 'signupform-estado'],
            3: ['signupform-senha', 'signupform-senha_confirmacao', 'signupform-termos_aceitos'],
        };

        let valid = true;
        (required[step] || []).forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            const val = el.type === 'checkbox' ? el.checked : el.value.trim();
            if (!val) {
                el.classList.add('is-error');
                el.addEventListener('input', () => el.classList.remove('is-error'), { once: true });
                valid = false;
            }
        });

        if (!valid) {
            const firstError = document.querySelector('#step-' + step + ' .is-error');
            if (firstError) firstError.focus();
        }
        return valid;
    }

    // Máscara CPF
    document.getElementById('signupform-cpf')?.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '').slice(0, 11);
    });

    // Máscara Telefone
    document.getElementById('signupform-telefone')?.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '').slice(0, 11);
    });

    // Loading no submit
    document.getElementById('form-nova-loja')?.addEventListener('submit', function() {
        const btn = document.getElementById('btn-submit');
        btn.disabled = true;
        btn.textContent = '⏳ Criando sua loja...';
    });

    // Se houver erros do servidor, vai para o step 1 por padrão
    <?php if (!empty($model->errors)): ?>
    goStep(1);
    <?php endif; ?>
</script>
</body>
</html>
