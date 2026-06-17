<?php
/**
 * Layout Principal do Módulo Caixa
 * @var \yii\web\View $this
 * @var string $content
 */

use yii\helpers\Html;
$isGuest = Yii::$app->user->isGuest;
// A variável $usuario só será definida se o utilizador não for um convidado
$usuario = !$isGuest ? Yii::$app->user->identity : null;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <?php $this->head() ?>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
<?php $this->beginBody() ?>

<header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">
    <div class="px-4 py-3">
        <div class="flex items-center justify-between">
            <?= Html::a('<span class="text-xl font-bold text-green-600">💰 Caixa</span>', ['/caixa/caixa/index']) ?>
            
            <div class="flex items-center gap-3">
                <?= Html::a(
                    '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
                    ['/vendas/inicio/index'],
                    [
                        'class' => 'text-gray-600 hover:text-gray-900 transition-colors',
                        'title' => 'Voltar ao Dashboard'
                    ]
                ) ?>
                
                <?php if (!$isGuest && $usuario): ?>
                    <div class="bg-green-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-semibold text-sm">
                        <?= Html::encode($usuario->getIniciais()) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<!-- Flash Messages -->
<?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-start">
            <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <div>
                <p class="font-medium">Sucesso!</p>
                <p class="text-sm"><?= Yii::$app->session->getFlash('success') ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-start">
            <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <div>
                <p class="font-medium">Erro!</p>
                <p class="text-sm"><?= Yii::$app->session->getFlash('error') ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<main class="pb-6">
    <?= $content ?>
</main>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

