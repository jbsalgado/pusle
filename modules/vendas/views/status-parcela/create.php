<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\StatusParcela */

$this->title = 'Criar Status de Parcela';
$this->params['breadcrumbs'][] = ['label' => 'Status de Parcelas', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Registrar Tailwind CSS
$this->registerCssFile('https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css', ['position' => \yii\web\View::POS_HEAD]);
?>

<div class="status-parcela-create min-h-screen bg-gray-50 py-4 sm:py-6 lg:py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <!-- Breadcrumb/Voltar -->
        <div class="mb-4 sm:mb-6">
            <?= Html::a(
                '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>Voltar',
                ['index'],
                ['class' => 'inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors duration-200']
            ) ?>
        </div>

        <!-- Card Principal -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <!-- Header do Card -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-8 sm:px-8">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-12 w-12 rounded-lg bg-white/10 flex items-center justify-center backdrop-blur-sm">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">
                            <?= Html::encode($this->title) ?>
                        </h1>
                        <p class="mt-1 text-sm text-blue-100">Preencha os campos abaixo para criar um novo status</p>
                    </div>
                </div>
            </div>

            <!-- Formulário -->
            <div class="px-6 py-8 sm:px-8">
                <?= $this->render('_form', [
                    'model' => $model,
                ]) ?>
            </div>
        </div>

        <!-- Informação Adicional -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Dica</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>O código do status será utilizado como identificador único no sistema. Escolha um código descritivo e fácil de lembrar.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>