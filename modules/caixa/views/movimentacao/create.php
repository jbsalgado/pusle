<?php

use yii\helpers\Html;

$this->title = 'Nova Movimentação';
$this->params['breadcrumbs'][] = ['label' => 'Caixas', 'url' => ['caixa/index']];
$this->params['breadcrumbs'][] = ['label' => 'Caixa #' . substr($caixa->id, 0, 8), 'url' => ['caixa/view', 'id' => $caixa->id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center mb-4">
                <a href="<?= yii\helpers\Url::to(['caixa/view', 'id' => $caixa->id]) ?>" 
                   class="mr-4 text-gray-600 hover:text-gray-900 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
                        <?= Html::encode($this->title) ?>
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Registre uma nova movimentação no caixa #<?= substr($caixa->id, 0, 8) ?>
                    </p>
                </div>
            </div>

            <!-- Info Banner -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Dica:</strong> Registre todas as entradas e saídas de dinheiro do caixa. Isso ajudará no fechamento e controle financeiro.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form -->
        <?= $this->render('_form', [
            'model' => $model,
            'caixa' => $caixa,
        ]) ?>
    </div>
</div>

