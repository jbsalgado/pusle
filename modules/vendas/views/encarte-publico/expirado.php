<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\modules\vendas\models\Encarte $encarteAntigo */
/** @var app\modules\vendas\models\Encarte|null $novoEncarte */
/** @var app\models\Usuario $loja */
/** @var app\modules\vendas\models\LojaConfiguracao|null $lojaConfig */

$nomeLoja = ($lojaConfig && !empty($lojaConfig->nome_fantasia)) 
    ? $lojaConfig->nome_fantasia 
    : (($lojaConfig && !empty($lojaConfig->razao_social)) 
        ? $lojaConfig->razao_social 
        : ($loja ? ($loja->nome_loja ?: $loja->nome) : 'Loja Oficial'));
$logoLoja = $lojaConfig ? $lojaConfig->getLogoUrl() : null;
$whatsappLoja = $lojaConfig ? preg_replace('/\D/', '', (string)$lojaConfig->getWhatsappSuporte()) : null;
$urlNovoEncarte = $novoEncarte ? $novoEncarte->getUrlPublica() : null;
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Html::encode($nomeLoja) ?> — Encarte Expirado</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .font-montserrat {
            font-family: 'Montserrat', sans-serif;
        }
        .glass-panel {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        @keyframes pulse-glow {
            0%, 100% { opacity: 0.6; transform: scale(1); }
            50% { opacity: 0.9; transform: scale(1.05); }
        }
        .glow-effect {
            animation: pulse-glow 3s infinite ease-in-out;
        }
    </style>
</head>
<body class="h-full flex items-center justify-center p-4 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-white relative overflow-hidden">

    <!-- Efeitos de iluminação de fundo -->
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-red-600/20 rounded-full blur-3xl pointer-events-none glow-effect"></div>
    <div class="absolute bottom-1/4 left-1/2 -translate-x-1/2 w-80 h-80 bg-emerald-600/15 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-md w-full glass-panel rounded-3xl p-6 sm:p-8 shadow-2xl relative z-10 text-center flex flex-col items-center border border-slate-700/60">
        
        <!-- Logo ou Identificação da Loja -->
        <div class="mb-5 flex flex-col items-center">
            <?php if ($logoLoja): ?>
                <img src="<?= Html::encode($logoLoja) ?>" alt="<?= Html::encode($nomeLoja) ?>" class="h-16 max-w-full object-contain mb-2 drop-shadow-md rounded-xl">
            <?php else: ?>
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-amber-500 to-red-600 flex items-center justify-center text-2xl font-black shadow-lg mb-2">
                    🏪
                </div>
            <?php endif; ?>
            <h2 class="text-sm font-extrabold text-slate-300 uppercase tracking-wider"><?= Html::encode($nomeLoja) ?></h2>
        </div>

        <?php if ($novoEncarte): ?>
            <!-- Ícone de Alerta com Badge de Redirecionamento -->
            <div class="relative mb-4">
                <div class="w-20 h-20 rounded-3xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-4xl shadow-inner">
                    ⏳
                </div>
                <div class="absolute -top-1 -right-1 bg-red-600 text-white text-[9px] font-black uppercase px-2 py-0.5 rounded-full shadow-md animate-bounce">
                    Expirado
                </div>
            </div>

            <!-- Textos de Informação -->
            <h1 class="text-xl sm:text-2xl font-montserrat font-black text-white tracking-tight mb-2">
                Este Encarte Expirou!
            </h1>
            
            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed mb-4">
                A edição <strong>"<?= Html::encode($encarteAntigo->titulo) ?>"</strong> foi finalizada para evitar compras com ofertas fora do prazo.
            </p>

            <!-- Card de Redirecionamento com Contador -->
            <div class="w-full bg-slate-900/80 border border-emerald-500/40 rounded-2xl p-4 mb-6 shadow-inner text-left flex flex-col gap-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] uppercase font-black tracking-wider text-emerald-400 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                        Edição Atual Disponível
                    </span>
                    <span class="text-xs font-bold text-slate-400">
                        Redirecionando em <strong id="contadorSegundos" class="text-emerald-400 text-sm font-black font-montserrat">5</strong>s
                    </span>
                </div>

                <div class="bg-slate-800/80 p-2.5 rounded-xl border border-slate-700">
                    <h3 class="font-extrabold text-xs text-white truncate">📖 <?= Html::encode($novoEncarte->titulo) ?></h3>
                    <?php if ($novoEncarte->subtitulo): ?>
                        <p class="text-[10px] text-slate-400 truncate"><?= Html::encode($novoEncarte->subtitulo) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Barra de Progresso Visual -->
                <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                    <div id="barraProgresso" class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full w-full transition-all duration-1000 ease-linear"></div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="w-full space-y-2.5">
                <a href="<?= Html::encode($urlNovoEncarte) ?>" class="w-full py-3.5 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-500 hover:to-green-500 text-white font-extrabold text-sm rounded-2xl shadow-xl transition transform active:scale-98 flex items-center justify-center gap-2 cursor-pointer">
                    <span>🚀 Acessar Encarte Mais Recente</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>

        <?php else: ?>
            <!-- Caso a loja não tenha nenhum encarte ativo no momento -->
            <div class="w-20 h-20 rounded-3xl bg-slate-800/80 border border-slate-700 flex items-center justify-center text-4xl shadow-inner mb-4">
                📢
            </div>

            <h1 class="text-xl sm:text-2xl font-montserrat font-black text-white tracking-tight mb-2">
                Promoção Encerrada
            </h1>
            
            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed mb-6">
                As ofertas do encarte <strong>"<?= Html::encode($encarteAntigo->titulo) ?>"</strong> foram encerradas. Fique atento às nossas próximas novidades e promoções!
            </p>

            <?php if ($whatsappLoja): ?>
                <a href="https://wa.me/55<?= $whatsappLoja ?>?text=Ol%C3%A1%21+Gostaria+de+saber+sobre+as+ofertas+e+produtos+da+loja." target="_blank" class="w-full py-3.5 bg-green-600 hover:bg-green-700 text-white font-extrabold text-sm rounded-2xl shadow-xl transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.705 1.754zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-1.15 4.2 4.293-1.125z"/></svg>
                    <span>Falar com Atendimento no WhatsApp</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <div class="mt-6 pt-4 border-t border-slate-800 w-full text-[10px] text-slate-500 font-semibold">
            Plataforma Pulse Digital • Encartes &amp; Catálogos Interativos
        </div>

    </div>

    <?php if ($novoEncarte && $urlNovoEncarte): ?>
    <script>
        let segundosRestantes = 5;
        const totalSegundos = 5;
        const elContador = document.getElementById('contadorSegundos');
        const elBarra = document.getElementById('barraProgresso');
        const targetUrl = <?= json_encode($urlNovoEncarte) ?>;

        const interval = setInterval(() => {
            segundosRestantes--;
            if (elContador) elContador.textContent = segundosRestantes;
            if (elBarra) {
                const percent = (segundosRestantes / totalSegundos) * 100;
                elBarra.style.width = percent + '%';
            }

            if (segundosRestantes <= 0) {
                clearInterval(interval);
                window.location.href = targetUrl;
            }
        }, 1000);
    </script>
    <?php endif; ?>

</body>
</html>
