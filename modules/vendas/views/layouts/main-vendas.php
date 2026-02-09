<?php

/** 
 * Exemplo de Layout Principal com Tailwind CSS
 * Copie e adapte para seu projeto
 */

use yii\helpers\Html;

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">

<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>

    <!-- Tailwind CSS via CDN (Desenvolvimento) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Configuração do Tailwind -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>

    <?php $this->head() ?>

    <style>
        /* Estilos customizados adicionais */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php $this->beginBody() ?>

    <!-- Navbar -->
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <?= Html::a('Sistema Vendas', ['/site/index'], ['class' => 'text-xl font-bold text-blue-600']) ?>
                </div>

                <div class="hidden md:flex items-center space-x-4">
                    <?= Html::a('Produtos', ['/vendas/produto/index'], ['class' => 'text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md font-medium']) ?>
                    <?= Html::a('Categorias', ['/vendas/categoria/index'], ['class' => 'text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md font-medium']) ?>
                    <?= Html::a('Financeiro', ['/vendas/dashboard-financeiro/index'], ['class' => 'text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md font-medium']) ?>
                    <?= Html::a('Fiscal', ['/vendas/cupom-fiscal/index'], ['class' => 'text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md font-medium']) ?>

                    <?php if (!Yii::$app->user->isGuest): ?>
                        <span class="text-gray-600">Olá, <?= Html::encode(Yii::$app->user->identity->username) ?></span>
                        <?= Html::beginForm(['/auth/logout'], 'post')
                            . Html::submitButton('Sair', ['class' => 'px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md font-medium'])
                            . Html::endForm() ?>
                    <?php else: ?>
                        <?= Html::a('Login', ['/site/login'], ['class' => 'px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium']) ?>
                    <?php endif; ?>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-button" class="text-gray-700 hover:text-blue-600 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <?= Html::a('Produtos', ['/vendas/produto/index'], ['class' => 'block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100']) ?>
                <?= Html::a('Categorias', ['/vendas/categoria/index'], ['class' => 'block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100']) ?>

                <?php if (!Yii::$app->user->isGuest): ?>
                    <span class="block px-3 py-2 text-gray-600">Olá, <?= Html::encode(Yii::$app->user->identity->username) ?></span>
                    <?= Html::beginForm(['/auth/logout'], 'post', ['class' => 'px-3'])
                        . Html::submitButton('Sair', ['class' => 'w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md font-medium'])
                        . Html::endForm() ?>
                <?php else: ?>
                    <?= Html::a('Login', ['/site/login'], ['class' => 'block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100']) ?>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Breadcrumbs -->
    <?php if (isset($this->params['breadcrumbs'])): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2">
                    <li>
                        <?= Html::a('Início', ['/site/index'], ['class' => 'text-gray-500 hover:text-blue-600']) ?>
                    </li>
                    <?php foreach ($this->params['breadcrumbs'] as $breadcrumb): ?>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400 mx-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                            <?php if (is_array($breadcrumb) && isset($breadcrumb['url'])): ?>
                                <?= Html::a($breadcrumb['label'], $breadcrumb['url'], ['class' => 'text-gray-500 hover:text-blue-600']) ?>
                            <?php else: ?>
                                <span class="text-gray-700 font-medium"><?= is_array($breadcrumb) ? $breadcrumb['label'] : $breadcrumb ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        </div>
    <?php endif; ?>

    <!-- Flash Messages -->
    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-start">
                <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
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
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <div>
                    <p class="font-medium">Erro!</p>
                    <p class="text-sm"><?= Yii::$app->session->getFlash('error') ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Conteúdo Principal -->
    <main class="min-h-screen">
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <p class="text-center text-gray-500 text-sm">
                © <?= date('Y') ?> Sistema de Vendas. Desenvolvido com Yii2 + Tailwind CSS.
            </p>
        </div>
    </footer>

    <!-- Mobile Menu Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');

            if (menuButton && mobileMenu) {
                menuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });
            }
        });
    </script>

    <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>