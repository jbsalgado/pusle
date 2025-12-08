<?php

use yii\helpers\Html;

$this->title = 'Novo Produto';
$this->params['breadcrumbs'][] = ['label' => 'Produtos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-4 px-3 sm:py-6 sm:px-4 lg:px-8">
    <div class="max-w-4xl mx-auto">
        
        <div class="bg-white rounded-lg shadow-sm sm:shadow-md overflow-hidden">
            <div class="bg-green-600 px-4 py-3 sm:px-6 sm:py-4">
                <h2 class="text-xl sm:text-2xl font-bold text-white flex items-center">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span><?= Html::encode($this->title) ?></span>
                </h2>
            </div>
            
            <div class="p-4 sm:p-6">
                <?= $this->render('_form', [
                    'model' => $model,
                ]) ?>
            </div>
        </div>

    </div>
</div>