<?php
/** @var \yii\web\View $this */
/** @var string $content */

use yii\helpers\Html;
use yii\helpers\Url;

$usuario = Yii::$app->user->identity;
$lojaNome = $usuario->nome_loja ?? $usuario->nome ?? 'Pulse Prestanista';
$routeAtual = Yii::$app->controller->id . '/' . Yii::$app->controller->action->id;
$controllerAtual = Yii::$app->controller->id;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-900 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title ? $this->title . ' - Prestanista' : 'Módulo Prestanista | Crediário de Porta em Porta') ?></title>
    
    <!-- Google Fonts & Tailwind CDN -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        }
                    }
                }
            }
        }
    </script>
    <?php $this->head() ?>
</head>
<body class="h-full font-sans antialiased bg-slate-900 text-slate-100 selection:bg-amber-500 selection:text-white">
<?php $this->beginBody() ?>

<div class="min-h-full flex flex-col">
    <!-- Barra Superior / Header de Navegação -->
    <header class="sticky top-0 z-40 bg-slate-950/90 backdrop-blur-md border-b border-slate-800 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Brand & Nome da Loja -->
                <div class="flex items-center gap-3">
                    <a href="<?= Url::to(['/prestanista/default/index']) ?>" class="flex items-center gap-2.5 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 via-orange-500 to-amber-400 flex items-center justify-center text-xl shadow-lg shadow-amber-500/20 group-hover:scale-105 transition-transform">
                            📇
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-black text-lg text-white tracking-tight">PRESTANISTA</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-400/10 text-amber-400 border border-amber-400/20">
                                    Crediário
                                </span>
                            </div>
                            <span class="text-xs text-slate-400 block -mt-0.5 truncate max-w-[160px] sm:max-w-xs"><?= Html::encode($lojaNome) ?></span>
                        </div>
                    </a>
                </div>

                <!-- Menu de Navegação Desktop -->
                <nav class="hidden md:flex items-center gap-1.5">
                    <a href="<?= Url::to(['/prestanista/default/index']) ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 <?= $controllerAtual === 'default' ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                        <span>📊</span> Painel
                    </a>
                    <a href="<?= Url::to(['/prestanista/cartao/index']) ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 <?= $controllerAtual === 'cartao' ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                        <span>📇</span> Cartões
                    </a>
                    <a href="<?= Url::to(['/prestanista/equipe/index']) ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 <?= $controllerAtual === 'equipe' ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                        <span>👥</span> Equipes
                    </a>
                    <a href="<?= Url::to(['/prestanista/carga/index']) ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 <?= $controllerAtual === 'carga' ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                        <span>🛒</span> Carga Carrinho
                    </a>
                    <a href="<?= Url::to(['/prestanista/acerto/index']) ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 <?= $controllerAtual === 'acerto' ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                        <span>💰</span> Acerto Caixa
                    </a>
                    <a href="<?= Url::to(['/prestanista/comissao/index']) ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 <?= $controllerAtual === 'comissao' ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                        <span>💎</span> Comissões
                    </a>
                </nav>

                <!-- Botões de Ação Direta -->
                <div class="flex items-center gap-2">
                    <!-- Botão para abrir o App Mobile / PWA -->
                    <a href="<?= Url::to(['/prestanista/']) ?>" target="_blank" class="px-3 sm:px-4 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-slate-950 font-black text-xs sm:text-sm shadow-md flex items-center gap-1.5 transition active:scale-95">
                        <span>📱</span>
                        <span class="hidden sm:inline">Abrir App Mobile</span>
                        <span class="sm:hidden">App</span>
                    </a>

                    <!-- Voltar ao Painel Geral do ERP -->
                    <a href="<?= Url::to(['/vendas/inicio']) ?>" class="p-2 sm:px-3 sm:py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-bold border border-slate-700 transition flex items-center gap-1.5" title="Voltar ao Sistema Geral">
                        <span>🔙</span>
                        <span class="hidden md:inline">Painel Geral</span>
                    </a>
                </div>

            </div>
            
            <!-- Menu de Abas Mobile (Scroll Horizontal) -->
            <div class="md:hidden flex items-center gap-1.5 overflow-x-auto py-2.5 border-t border-slate-800/80 no-scrollbar">
                <a href="<?= Url::to(['/prestanista/default/index']) ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold shrink-0 <?= $controllerAtual === 'default' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'text-slate-400 bg-slate-800/60' ?>">📊 Painel</a>
                <a href="<?= Url::to(['/prestanista/cartao/index']) ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold shrink-0 <?= $controllerAtual === 'cartao' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'text-slate-400 bg-slate-800/60' ?>">📇 Cartões</a>
                <a href="<?= Url::to(['/prestanista/equipe/index']) ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold shrink-0 <?= $controllerAtual === 'equipe' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'text-slate-400 bg-slate-800/60' ?>">👥 Equipes</a>
                <a href="<?= Url::to(['/prestanista/carga/index']) ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold shrink-0 <?= $controllerAtual === 'carga' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'text-slate-400 bg-slate-800/60' ?>">🛒 Carga</a>
                <a href="<?= Url::to(['/prestanista/acerto/index']) ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold shrink-0 <?= $controllerAtual === 'acerto' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'text-slate-400 bg-slate-800/60' ?>">💰 Caixa</a>
                <a href="<?= Url::to(['/prestanista/comissao/index']) ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold shrink-0 <?= $controllerAtual === 'comissao' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'text-slate-400 bg-slate-800/60' ?>">💎 Comissões</a>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Notificações Flash -->
        <?php foreach (Yii::$app->session->getAllFlashes() as $type => $message): ?>
            <div class="mb-5 p-4 rounded-2xl border text-sm font-semibold flex items-center justify-between <?= $type === 'success' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : ($type === 'error' ? 'bg-rose-500/10 border-rose-500/30 text-rose-400' : 'bg-amber-500/10 border-amber-500/30 text-amber-400') ?>">
                <div class="flex items-center gap-2">
                    <span><?= $type === 'success' ? '✅' : ($type === 'error' ? '❌' : '⚠️') ?></span>
                    <span><?= Html::encode($message) ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white text-xs">✕</button>
            </div>
        <?php endforeach; ?>

        <?= $content ?>
    </main>

    <!-- Rodapé -->
    <footer class="border-t border-slate-800/80 bg-slate-950 py-4 text-center text-xs text-slate-400">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <span>📇 <strong>PULSE Prestanista</strong> • Vendas e Crediário de Porta em Porta</span>
            <span>Ambiente Seguro • <?= date('Y') ?></span>
        </div>
    </footer>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
