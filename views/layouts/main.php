<?php

/**
 * Layout de Autenticação - Sistema Global
 * Localização: app/views/layouts/auth.php
 * 
 * @var \yii\web\View $this
 * @var string $content
 */

use yii\helpers\Html;
use yii\helpers\Url;

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-full">

<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>

    <!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <?php $this->head() ?>

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
    </style>
</head>

<body class="h-full bg-gray-50">
    <?php $this->beginBody() ?>

    <!-- Flash Messages -->
    <?php
    $flashTypes = [
        'success' => ['bg' => 'bg-green-50', 'border' => 'border-green-500', 'text' => 'text-green-800', 'icon' => 'text-green-500', 'svg' => 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z'],
        'error'   => ['bg' => 'bg-red-50', 'border' => 'border-red-500', 'text' => 'text-red-800', 'icon' => 'text-red-500', 'svg' => 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z'],
        'info'    => ['bg' => 'bg-blue-50', 'border' => 'border-blue-500', 'text' => 'text-blue-800', 'icon' => 'text-blue-500', 'svg' => 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z'],
        'warning' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-500', 'text' => 'text-yellow-800', 'icon' => 'text-yellow-500', 'svg' => 'M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z'],
    ];

    $offset = 0;
    foreach ($flashTypes as $type => $styles):
        $messages = Yii::$app->session->getFlash($type);
        if (!$messages) continue;

        // Normaliza para array caso seja string
        $messages = (array) $messages;

        foreach ($messages as $message):
    ?>
            <div id="flash-<?= $type ?>-<?= $offset++ ?>" class="fixed top-<?= 4 + ($offset * 1) ?> right-4 z-50 max-w-md animate-slide-in-right">
                <div class="<?= $styles['bg'] ?> border-l-4 <?= $styles['border'] ?> p-4 rounded-lg shadow-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 <?= $styles['icon'] ?>" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="<?= $styles['svg'] ?>" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium <?= $styles['text'] ?>">
                                <?= $message ?>
                            </p>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-4 flex-shrink-0">
                            <svg class="h-4 w-4 <?= $styles['icon'] ?> hover:opacity-75" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
    <?php
        endforeach;
    endforeach;
    ?>

    <!-- Conteúdo Principal -->
    <?= $content ?>

    <?php $this->endBody() ?>

    <!-- Script para auto-fechar flash messages -->
    <script>
        // Auto-fechar flash messages após 5 segundos
        setTimeout(function() {
            const flashMessages = document.querySelectorAll('[id^="flash-"]');
            flashMessages.forEach(function(flash) {
                flash.style.transition = 'opacity 0.5s, transform 0.5s';
                flash.style.opacity = '0';
                flash.style.transform = 'translateX(100%)';
                setTimeout(function() {
                    flash.remove();
                }, 500);
            });
        }, 5000);
    </script>

    <style>
        @keyframes slide-in-right {
            from {
                opacity: 0;
                transform: translateX(100%);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-slide-in-right {
            animation: slide-in-right 0.5s ease-out;
        }
    </style>

</body>

</html>
<?php $this->endPage() ?>