<?php
/** @var yii\web\View $this */
/** @var app\models\Usuario $usuario */
/** @var app\modules\vendas\models\Colaborador|null $colaborador */
/** @var bool $ehDono */
/** @var string $nomeLoja */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Central de Campo Prestanista';
$nomeUsuario = $colaborador ? $colaborador->nome_completo : ($usuario->nome ?? 'Colaborador');
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="h-full bg-slate-950 text-slate-100 flex flex-col justify-between p-4 max-w-md mx-auto">

    <!-- Topo -->
    <div>
        <header class="flex items-center justify-between py-4 border-b border-slate-800/80 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-600 flex items-center justify-center text-xl shadow-lg shadow-amber-500/20">
                    📇
                </div>
                <div>
                    <h1 class="text-xs font-black uppercase tracking-wider text-amber-400 leading-tight"><?= Html::encode($nomeLoja) ?></h1>
                    <span class="text-sm font-bold text-white block truncate max-w-[180px]"><?= Html::encode($nomeUsuario) ?></span>
                </div>
            </div>

            <a href="<?= Url::to(['/auth/logout']) ?>" data-method="post" class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white text-xs font-bold transition flex items-center gap-1.5" title="Sair da Conta">
                <span>🚪</span> <span>Sair</span>
            </a>
        </header>

        <!-- Título da Ação -->
        <div class="text-center mb-6">
            <h2 class="text-xl font-black text-white tracking-tight">Qual é o seu momento agora?</h2>
            <p class="text-xs text-slate-400 mt-1">
                Escolha a ferramenta de trabalho para iniciar seu turno na rua.
            </p>
        </div>

        <!-- Opções de Modo de Trabalho -->
        <div class="space-y-4">

            <!-- Card 1: Vendedor Ambulante -->
            <a href="<?= Url::to(['/prestanista/vendedor/index']) ?>" class="group block p-5 rounded-3xl bg-gradient-to-br from-slate-900 via-slate-900 to-blue-950/30 border border-blue-500/30 hover:border-blue-500 transition shadow-xl active:scale-95">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-2xl bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        🛒
                    </div>
                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-500/20 text-blue-300 border border-blue-500/30">
                        Momento Venda
                    </span>
                </div>
                <h3 class="text-base font-black text-white group-hover:text-blue-400 transition-colors">
                    App Vendedor Ambulante
                </h3>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                    Vender mercadorias de porta em porta, cadastrar clientes novos e emitir cartões de crediário.
                </p>
                <div class="mt-4 flex items-center text-xs font-bold text-blue-400 gap-1">
                    <span>Acessar aplicativo de vendas</span>
                    <span>→</span>
                </div>
            </a>

            <!-- Card 2: Cobrador de Rua -->
            <a href="<?= Url::to(['/prestanista/cobrador/index']) ?>" class="group block p-5 rounded-3xl bg-gradient-to-br from-slate-900 via-slate-900 to-emerald-950/30 border border-emerald-500/30 hover:border-emerald-500 transition shadow-xl active:scale-95">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        🛵
                    </div>
                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        Momento Cobrança
                    </span>
                </div>
                <h3 class="text-base font-black text-white group-hover:text-emerald-400 transition-colors">
                    App Cobrador de Rua
                </h3>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                    Acessar sua rota diária de visitas, receber pagamentos de prestações e emitir recibos aos clientes.
                </p>
                <div class="mt-4 flex items-center text-xs font-bold text-emerald-400 gap-1">
                    <span>Acessar aplicativo de cobrança</span>
                    <span>→</span>
                </div>
            </a>

        </div>

        <?php if ($ehDono): ?>
            <!-- Atalho de Retorno para o Lojista -->
            <div class="mt-6 pt-6 border-t border-slate-900 text-center">
                <a href="<?= Url::to(['/prestanista/default/index']) ?>" class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-amber-400 font-bold transition">
                    <span>📊</span>
                    <span>Voltar ao Painel Gestor do Prestanista</span>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Rodapé -->
    <footer class="text-center py-4 text-[11px] text-slate-600 border-t border-slate-900/60 mt-6">
        Pulse Prestanista • Crediário de Rua Mobile
    </footer>

</body>
</html>
