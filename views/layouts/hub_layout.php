<?php

/** @var yii\web\View $this */
/** @var string $content */

use yii\helpers\Html;

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#10B981">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= Html::encode($this->title ?: 'Direct Hub & Comanda Digital') ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <?php $this->head() ?>
    <style>
        [x-cloak] { display: none !important; }
        /* Suavização de scroll em smartphones */
        html { scroll-behavior: smooth; -webkit-tap-highlight-color: transparent; }
    </style>
</head>
<body class="h-full text-gray-900 antialiased flex flex-col font-sans selection:bg-emerald-500 selection:text-white">
<?php $this->beginBody() ?>

    <main class="flex-1 pb-20">
        <?= $content ?>
    </main>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
