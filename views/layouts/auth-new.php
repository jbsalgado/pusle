<?php
/**
 * Layout Mobile First - SEM MENU (Módulo Vendas)
 * Localização: app/modules/vendas/views/layouts/main.php
 * 
 * @var \yii\web\View $this
 * @var string $content
 */

use yii\helpers\Html;
use yii\helpers\Url;

$isGuest = Yii::$app->user->isGuest;
$usuario = $isGuest ? null : Yii::$app->user->identity;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    
    <!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com">
    
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <?php $this->head() ?>
    
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        
        /* Melhor experiência de toque */
        * {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
        }
        
        /* Cards com efeito de toque */
        .card-touch {
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }
        
        .card-touch:active {
            transform: scale(0.98);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
<?php $this->beginBody() ?>

<!-- Header Fixo Mobile -->
<header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">
    <div class="px-4 py-3">
        <div class="flex items-center justify-between">
            <!-- Logo/Voltar -->
            <div class="flex items-center space-x-3">
                <?php if (!$isGuest && Yii::$app->controller->id !== 'inicio'): ?>
                    <button onclick="window.history.back()" class="text-gray-600 active:text-gray-900 p-2 -ml-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                <?php endif; ?>
                
                <?= Html::a(
                    '<span class="text-xl font-bold text-blue-600">🎯 Prestanista</span>',
                    ['/vendas/inicio/index']
                ) ?>
            </div>
            
            <!-- Avatar do Usuário (apenas se logado) -->
            <?php if (!$isGuest && $usuario): ?>
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center space-x-2 active:opacity-70">
                        <div class="bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-semibold text-sm">
                            <?= $usuario->getIniciais() ?>
                        </div>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div x-show="open" 
                         @click.away="open = false"
                         x-transition
                         style="display: none;"
                         class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-50">
                        
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-semibold text-gray-900"><?= Html::encode($usuario->nome) ?></p>
                            <p class="text-xs text-gray-500 mt-1"><?= Html::encode($usuario->getTelefoneFormatado()) ?></p>
                        </div>
                        
                        <?= Html::a(
                            '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span>Meu Perfil</span>',
                            ['/vendas/usuario/perfil'],
                            ['class' => 'flex items-center space-x-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 active:bg-gray-100']
                        ) ?>
                        
                        <?= Html::a(
                            '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>Configurações</span>',
                            ['/vendas/configuracao/index'],
                            ['class' => 'flex items-center space-x-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 active:bg-gray-100']
                        ) ?>
                        
                        <div class="border-t border-gray-100 my-1"></div>
                        
                        <?= Html::beginForm(['/vendas/auth/logout'], 'post') ?>
                            <?= Html::submitButton(
                                '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                <span>Sair</span>',
                                ['class' => 'flex items-center space-x-3 w-full text-left px-4 py-3 text-sm text-red-600 hover:bg-red-50 active:bg-red-100 border-0 bg-transparent']
                            ) ?>
                        <?= Html::endForm() ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Ícone para usuários não logados -->
                <div class="text-gray-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Flash Messages -->
<?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="fixed top-20 left-4 right-4 z-50 animate-slide-down">
        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg shadow-lg">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-green-800">
                        <?= Yii::$app->session->getFlash('success') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="fixed top-20 left-4 right-4 z-50 animate-slide-down">
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-lg">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-red-800">
                        <?= Yii::$app->session->getFlash('error') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (Yii::$app->session->hasFlash('info')): ?>
    <div class="fixed top-20 left-4 right-4 z-50 animate-slide-down">
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg shadow-lg">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-blue-800">
                        <?= Yii::$app->session->getFlash('info') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Conteúdo Principal -->
<main class="pb-6">
    <?= $content ?>
</main>

<?php $this->endBody() ?>

<!-- Alpine.js -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<style>
@keyframes slide-down {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-slide-down {
    animation: slide-down 0.3s ease-out;
}
</style>

</body>
</html>
<?php $this->endPage() ?>