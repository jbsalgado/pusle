<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Permissões de Módulos | PULSE</title>
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
            --radius: 14px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

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
            transition: color 0.2s, background 0.2s;
            margin: 2px 8px;
            border-radius: 10px;
        }
        .nav-item:hover { color: var(--text); background: rgba(255,255,255,0.04); }
        .nav-item.active { color: var(--primary); background: var(--primary-light); }
        .nav-icon { font-size: 18px; width: 24px; text-align: center; }

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

        .header-loja {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .header-info h2 { font-size: 20px; font-weight: 800; color: #fff; }
        .header-info p { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

        .btn-voltar {
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            color: var(--text);
            background: var(--bg-input);
            border: 1px solid var(--border);
            transition: all 0.2s;
        }
        .btn-voltar:hover { background: rgba(255,255,255,0.08); }

        .grupo-titulo {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--primary);
            margin: 28px 0 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .grupo-titulo::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
        }

        .module-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            transition: all 0.2s;
        }
        .module-card:hover { border-color: rgba(108,99,255,0.4); }
        .module-card.desativado { opacity: 0.55; background: rgba(20,22,39,0.5); }

        .module-details { flex: 1; min-width: 0; }
        .module-label { font-size: 14px; font-weight: 700; color: #fff; display: flex; items-center; gap: 8px; }
        .module-desc { font-size: 12px; color: var(--text-muted); margin-top: 4px; line-height: 1.3; }

        /* Switch Toggle Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 26px;
            flex-shrink: 0;
        }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: var(--bg-input);
            border: 1px solid var(--border);
            transition: .3s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: #7A7998;
            transition: .3s;
            border-radius: 50%;
        }
        input:checked + .slider { background-color: var(--green); border-color: var(--green); }
        input:checked + .slider:before { transform: translateX(22px); background-color: #0D0E1F; }

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
            max-width: 380px;
        }
        #toast.show { transform: translateY(0); opacity: 1; }
        #toast.success { background: rgba(67,233,123,0.18); border: 1px solid rgba(67,233,123,0.4); color: var(--green); }
        #toast.error   { background: rgba(255,101,132,0.18); border: 1px solid rgba(255,101,132,0.4); color: var(--red); }

        /* Card de Gestão de PIX Estático */
        .pix-control-card {
            background: linear-gradient(135deg, rgba(20,22,39,0.98), rgba(30,27,75,0.45));
            border: 1px solid rgba(108,99,255,0.3);
            border-radius: var(--radius);
            padding: 24px;
            margin-bottom: 28px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }
        .pix-control-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, #6C63FF, #43E97B, #00D2FF);
        }
        .pix-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 12px;
        }
        .pix-title {
            font-size: 16px;
            font-weight: 800;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .pix-desc {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 20px;
            max-width: 860px;
        }
        .pix-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            align-items: flex-start;
        }
        .pix-preset-btn {
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid var(--border);
            background: var(--bg-input);
            color: var(--text);
            cursor: pointer;
            transition: all 0.2s;
        }
        .pix-preset-btn:hover {
            border-color: var(--primary);
            background: var(--primary-light);
            color: #fff;
        }
        .pix-preset-btn.active {
            border-color: var(--green);
            background: rgba(67,233,123,0.15);
            color: var(--green);
        }
        .progress-bar-bg {
            background: rgba(255,255,255,0.06);
            height: 12px;
            border-radius: 50px;
            overflow: hidden;
            margin-top: 8px;
            border: 1px solid var(--border);
            position: relative;
        }
        .progress-bar-fill {
            height: 100%;
            border-radius: 50px;
            transition: width 0.4s ease;
        }
    </style>
</head>
<body>
<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var app\models\Usuario $loja */
/** @var array $modulosDisponiveis */
/** @var array $permissoesAtuais */

$admin = Yii::$app->user->identity;

// Agrupa módulos por grupo
$grupos = [];
foreach ($modulosDisponiveis as $chave => $m) {
    $g = $m['grupo'] ?? 'Geral';
    $grupos[$g][$chave] = $m;
}
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
            <h1>⚙️ Permissões de Módulos da Loja</h1>
            <a href="<?= Url::to(['/admin/loja/index']) ?>" class="btn-voltar">
                ← Voltar às Lojas
            </a>
        </div>

        <div class="content">

            <!-- Cabeçalho com dados do Cliente / Loja -->
            <div class="header-loja">
                <div class="header-info">
                    <h2>🏪 <?= Html::encode($loja->nome) ?></h2>
                    <p>Responsável: <strong><?= Html::encode($loja->nome) ?></strong> • Email: <?= Html::encode($loja->email) ?> • WhatsApp: <?= Html::encode($loja->telefone ?: '—') ?></p>
                    <p style="margin-top: 6px; font-size: 11px; color: var(--primary);">
                        💡 Módulos com chave desativada ficarão ocultos na tela inicial (vendas/inicio/index) desta loja.
                    </p>
                </div>
                <div>
                    <span style="font-size: 12px; font-weight: 700; background: var(--primary-light); color: var(--primary); padding: 6px 14px; border-radius: 50px;">
                        STATUS: <?= strtoupper(Html::encode($loja->status_loja)) ?>
                    </span>
                </div>
            </div>

            <!-- CARD EXCLUSIVO: CONTROLE DE PIX ESTATICO COM COTA DE VENDAS -->
            <?php 
                $temMp = $loja->temMercadoPagoConfigurado();
                $statusPix = $loja->getStatusPixEstatico();
                $limiteAtual = $loja->pix_estatico_limite_vendas;
                $liberadoAtual = (bool)$loja->pix_estatico_liberado_admin;
                $vendasRealizadas = (int)($loja->pix_estatico_vendas_realizadas ?? 0);
                
                $pctUso = 0;
                if ($limiteAtual > 0) {
                    $pctUso = min(100, round(($vendasRealizadas / $limiteAtual) * 100));
                } elseif ($statusPix['ilimitado']) {
                    $pctUso = 100;
                }
                $corProgresso = $pctUso >= 100 && !$statusPix['ilimitado'] ? 'linear-gradient(90deg, #FF6584, #f43f5e)' : ($pctUso > 70 ? 'linear-gradient(90deg, #FFD166, #f59e0b)' : 'linear-gradient(90deg, #43E97B, #10b981)');
            ?>
            <div class="pix-control-card">
                <div class="pix-header">
                    <div class="pix-title">
                        <span style="font-size: 20px;">🛡️</span>
                        <span>Controle de PIX Estático (Chave da Loja sem Taxa)</span>
                        <?php if ($temMp): ?>
                            <span style="font-size: 11px; font-weight: 700; background: rgba(6,182,212,0.15); color: #06b6d4; border: 1px solid rgba(6,182,212,0.3); padding: 3px 10px; border-radius: 50px;">
                                ⚡ Mercado Pago Conectado
                            </span>
                        <?php else: ?>
                            <span style="font-size: 11px; font-weight: 700; background: rgba(255,255,255,0.08); color: var(--text-muted); border: 1px solid var(--border); padding: 3px 10px; border-radius: 50px;">
                                ℹ️ Mercado Pago Não Conectado (PIX Loja Livre)
                            </span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span id="badge-status-pix" style="font-size: 11px; font-weight: 800; padding: 5px 12px; border-radius: 50px; <?= $statusPix['bloqueado'] ? 'background: rgba(255,101,132,0.15); color: var(--red); border: 1px solid rgba(255,101,132,0.3);' : 'background: rgba(67,233,123,0.15); color: var(--green); border: 1px solid rgba(67,233,123,0.3);' ?>">
                            <?= $statusPix['bloqueado'] ? '🔒 PIX ESTÁTICO BLOQUEADO' : '✅ PIX ESTÁTICO LIBERADO' ?>
                        </span>
                    </div>
                </div>

                <p class="pix-desc">
                    Quando o lojista conecta o <strong>Mercado Pago</strong>, o PIX Estático (chave Pix direta da loja) é <strong>bloqueado por padrão</strong> no PDV, Catálogo e Venda Direta para que as transações ocorram com baixa automática e split da SaaS. Você pode autorizar uma <strong>cota controlada de vendas diretas</strong> (50, 100, 500 etc.). Ao atingir o limite, o sistema volta a exigir o Mercado Pago.
                </p>

                <div class="pix-grid">
                    <!-- Coluna 1: Autorização e Presets de Cota -->
                    <div style="background: rgba(0,0,0,0.25); padding: 18px; border-radius: 12px; border: 1px solid var(--border);">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                            <span style="font-size: 13px; font-weight: 700; color: #fff;">Liberar PIX Estático para esta Loja</span>
                            <label class="switch">
                                <input type="checkbox" id="switch-pix-liberado" <?= $liberadoAtual ? 'checked' : '' ?> onchange="atualizarPreviewPix()">
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">
                                Definir Cota de Vendas Permitidas:
                            </label>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <button type="button" class="pix-preset-btn <?= $limiteAtual == 50 ? 'active' : '' ?>" onclick="selecionarCotaPreset(50)">50 Vendas</button>
                                <button type="button" class="pix-preset-btn <?= $limiteAtual == 100 ? 'active' : '' ?>" onclick="selecionarCotaPreset(100)">100 Vendas</button>
                                <button type="button" class="pix-preset-btn <?= $limiteAtual == 500 ? 'active' : '' ?>" onclick="selecionarCotaPreset(500)">500 Vendas</button>
                                <button type="button" class="pix-preset-btn <?= ($limiteAtual === null || $limiteAtual < 0) ? 'active' : '' ?>" onclick="selecionarCotaPreset('ilimitado')">Ilimitado</button>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 12px; color: var(--text-muted);">Ou digite o limite:</span>
                            <input type="number" id="input-cota-personalizada" min="1" max="999999" value="<?= ($limiteAtual > 0) ? $limiteAtual : '' ?>" placeholder="Ex: 250" oninput="limparPresetsAtivos()" style="width: 100px; padding: 6px 10px; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; color: #fff; font-size: 12px; font-weight: 700;">
                        </div>
                    </div>

                    <!-- Coluna 2: Consumo e Ações Rápidas -->
                    <div style="background: rgba(0,0,0,0.25); padding: 18px; border-radius: 12px; border: 1px solid var(--border);">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-size: 12px; font-weight: 700; color: #fff;">Consumo da Cota Atual</span>
                            <span id="texto-consumo-cota" style="font-size: 12px; font-weight: 800; color: <?= $statusPix['esgotado'] ? 'var(--red)' : 'var(--green)' ?>;">
                                <?php if ($statusPix['ilimitado']): ?>
                                    <?= $vendasRealizadas ?> vendas (Sem limite máximo)
                                <?php else: ?>
                                    <?= $vendasRealizadas ?> de <?= max(0, (int)$limiteAtual) ?> vendas (<?= $statusPix['restantes'] ?> restantes)
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="progress-bar-bg">
                            <div id="bar-consumo-cota" class="progress-bar-fill" style="width: <?= $pctUso ?>%; background: <?= $corProgresso ?>;"></div>
                        </div>

                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px;">
                            <button type="button" class="btn-voltar" style="padding: 7px 12px; font-size: 11px; background: rgba(99,102,241,0.15); color: var(--primary); border-color: rgba(99,102,241,0.3);" onclick="adicionarCotaRapida(50)">
                                +50 Vendas
                            </button>
                            <button type="button" class="btn-voltar" style="padding: 7px 12px; font-size: 11px; background: rgba(99,102,241,0.15); color: var(--primary); border-color: rgba(99,102,241,0.3);" onclick="adicionarCotaRapida(100)">
                                +100 Vendas
                            </button>
                            <button type="button" class="btn-voltar" style="padding: 7px 12px; font-size: 11px; background: rgba(255,209,102,0.15); color: var(--yellow); border-color: rgba(255,209,102,0.3);" onclick="salvarConfiguracaoPix(true)">
                                🔄 Zerar Contador
                            </button>
                            <button type="button" class="btn-voltar" style="padding: 7px 16px; font-size: 11px; background: var(--green); color: #0D0E1F; border: none; font-weight: 800; margin-left: auto;" onclick="salvarConfiguracaoPix(false)">
                                💾 Salvar Cota
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Módulos divididos por Categorias -->
            <?php foreach ($grupos as $nomeGrupo => $modulos): ?>
                <div class="grupo-titulo">
                    <span><?= Html::encode($nomeGrupo) ?></span>
                </div>

                <div class="modules-grid">
                    <?php foreach ($modulos as $chave => $mod): 
                        $isAtivo = !isset($permissoesAtuais[$chave]) || $permissoesAtuais[$chave] === true;
                    ?>
                        <div class="module-card <?= $isAtivo ? '' : 'desativado' ?>" id="card-<?= Html::encode($chave) ?>">
                            <div class="module-details">
                                <div class="module-label">
                                    <span><?= $mod['icone'] ?></span>
                                    <span><?= Html::encode($mod['label']) ?></span>
                                </div>
                                <div class="module-desc"><?= Html::encode($mod['descricao']) ?></div>
                            </div>
                            <div>
                                <label class="switch">
                                    <input type="checkbox" 
                                           id="toggle-<?= Html::encode($chave) ?>" 
                                           <?= $isAtivo ? 'checked' : '' ?>
                                           onchange="alternarModulo('<?= Html::encode($loja->id) ?>', '<?= Html::encode($chave) ?>', this.checked)">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

        </div>
    </div>
</div>

<div id="toast"></div>

<script>
async function alternarModulo(usuarioId, moduloChave, ativo) {
    const card = document.getElementById('card-' + moduloChave);
    
    try {
        const formData = new FormData();
        formData.append('usuario_id', usuarioId);
        formData.append('modulo_chave', moduloChave);
        formData.append('ativo', ativo ? '1' : '0');

        const res = await fetch('<?= Url::to(['/admin/loja/toggle-modulo']) ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
            },
            body: formData
        });

        const data = await res.json();

        if (data.success) {
            if (ativo) {
                card.classList.remove('desativado');
            } else {
                card.classList.add('desativado');
            }
            showToast(true, data.message);
        } else {
            // Reverte o switch se deu erro
            document.getElementById('toggle-' + moduloChave).checked = !ativo;
            showToast(false, data.message || 'Erro ao alterar permissão.');
        }

    } catch (e) {
        document.getElementById('toggle-' + moduloChave).checked = !ativo;
        showToast(false, 'Erro de conexão com o servidor.');
    }
}

function showToast(success, msg) {
    const t = document.getElementById('toast');
    t.textContent = (success ? '✅ ' : '❌ ') + msg;
    t.className = 'show ' + (success ? 'success' : 'error');
    setTimeout(() => t.className = '', 3500);
}

// ==========================================
// GESTÃO DE COTA DE PIX ESTÁTICO VIA AJAX
// ==========================================
let cotaPresetSelecionada = '<?= ($limiteAtual === null || $limiteAtual < 0) ? "ilimitado" : ($limiteAtual > 0 ? $limiteAtual : "") ?>';
const usuarioLojaId = '<?= Html::encode($loja->id) ?>';

function selecionarCotaPreset(val) {
    cotaPresetSelecionada = val;
    document.querySelectorAll('.pix-preset-btn').forEach(b => b.classList.remove('active'));
    
    if (val === 'ilimitado') {
        document.getElementById('input-cota-personalizada').value = '';
    } else {
        document.getElementById('input-cota-personalizada').value = val;
    }
    
    // Marca o botão clicado
    event.target.classList.add('active');
    document.getElementById('switch-pix-liberado').checked = true;
    atualizarPreviewPix();
}

function limparPresetsAtivos() {
    cotaPresetSelecionada = null;
    document.querySelectorAll('.pix-preset-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('switch-pix-liberado').checked = true;
    atualizarPreviewPix();
}

function atualizarPreviewPix() {
    const liberado = document.getElementById('switch-pix-liberado').checked;
    const badge = document.getElementById('badge-status-pix');
    if (!liberado) {
        badge.textContent = '🔒 PIX ESTÁTICO BLOQUEADO';
        badge.style.background = 'rgba(255,101,132,0.15)';
        badge.style.color = 'var(--red)';
        badge.style.border = '1px solid rgba(255,101,132,0.3)';
    } else {
        badge.textContent = '✅ PIX ESTÁTICO LIBERADO';
        badge.style.background = 'rgba(67,233,123,0.15)';
        badge.style.color = 'var(--green)';
        badge.style.border = '1px solid rgba(67,233,123,0.3)';
    }
}

async function salvarConfiguracaoPix(zerarContador = false) {
    const liberado = document.getElementById('switch-pix-liberado').checked;
    let limite = document.getElementById('input-cota-personalizada').value.trim();
    if (cotaPresetSelecionada === 'ilimitado') {
        limite = 'ilimitado';
    } else if (!limite && liberado) {
        limite = 50; // fallback padrão se liberado sem limite
    }

    if (zerarContador && !confirm('Tem certeza que deseja zerar o contador de vendas realizadas via PIX Estático?')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('usuario_id', usuarioLojaId);
        formData.append('liberado', liberado ? '1' : '0');
        formData.append('limite', limite);
        formData.append('zerar_contador', zerarContador ? '1' : '0');

        const res = await fetch('<?= Url::to(['/admin/loja/atualizar-pix-estatico']) ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
            },
            body: formData
        });

        const data = await res.json();
        if (data.success) {
            showToast(true, data.message);
            const s = data.status;
            const textoConsumo = document.getElementById('texto-consumo-cota');
            const bar = document.getElementById('bar-consumo-cota');
            
            if (s.ilimitado) {
                textoConsumo.textContent = `${s.realizadas} vendas (Sem limite máximo)`;
                textoConsumo.style.color = 'var(--green)';
                bar.style.width = '100%';
                bar.style.background = 'linear-gradient(90deg, #43E97B, #10b981)';
            } else {
                textoConsumo.textContent = `${s.realizadas} de ${s.limite} vendas (${s.restantes} restantes)`;
                const pct = s.limite > 0 ? Math.min(100, Math.round((s.realizadas / s.limite) * 100)) : 0;
                bar.style.width = pct + '%';
                if (s.esgotado) {
                    textoConsumo.style.color = 'var(--red)';
                    bar.style.background = 'linear-gradient(90deg, #FF6584, #f43f5e)';
                } else if (pct > 70) {
                    textoConsumo.style.color = 'var(--yellow)';
                    bar.style.background = 'linear-gradient(90deg, #FFD166, #f59e0b)';
                } else {
                    textoConsumo.style.color = 'var(--green)';
                    bar.style.background = 'linear-gradient(90deg, #43E97B, #10b981)';
                }
            }
            atualizarPreviewPix();
        } else {
            showToast(false, data.message || 'Erro ao salvar cota.');
        }
    } catch (e) {
        showToast(false, 'Erro de comunicação ao salvar cota.');
    }
}

async function adicionarCotaRapida(qtd) {
    if (!confirm(`Deseja adicionar +${qtd} vendas à cota de PIX Estático de ${qtd}?`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('usuario_id', usuarioLojaId);
        formData.append('liberado', '1');
        formData.append('adicionar_cota', qtd);

        const res = await fetch('<?= Url::to(['/admin/loja/atualizar-pix-estatico']) ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
            },
            body: formData
        });

        const data = await res.json();
        if (data.success) {
            showToast(true, data.message);
            document.getElementById('input-cota-personalizada').value = data.status.limite;
            document.getElementById('switch-pix-liberado').checked = true;
            salvarConfiguracaoPix(false);
        } else {
            showToast(false, data.message || 'Erro ao adicionar cota.');
        }
    } catch (e) {
        showToast(false, 'Erro ao adicionar cota.');
    }
}
</script>
</body>
</html>
