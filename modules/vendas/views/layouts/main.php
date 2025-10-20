<?php
/**
 * Layout - VERSÃO SEGURA (À PROVA DE FALHAS)
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
            <?= Html::a('<span class="text-xl font-bold text-blue-600">🎯 Prestanista</span>', ['/vendas/inicio/index']) ?>
            
            <?php if (!$isGuest && $usuario): ?>
                <div class="bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-semibold text-sm">
                    <?= Html::encode($usuario->getIniciais()) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="pb-6">
    <?= $content ?>
</main>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>