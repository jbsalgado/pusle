<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\Regiao */

$this->title = 'Editar Região: ' . $model->nome;
$this->params['breadcrumbs'][] = ['label' => 'Regiões', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Editar';
?>
<div class="min-h-screen bg-gray-50 py-4 px-3 sm:py-6 sm:px-4 lg:px-8">
    <div class="max-w-3xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-gray-900 truncate pr-4"><?= Html::encode($this->title) ?></h1>
            <?= Html::a(
                '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                ['index'],
                ['class' => 'inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-300 flex-shrink-0']
            ) ?>
        </div>

        <?= $this->render('_form', [
            'model' => $model,
        ]) ?>

    </div>
</div>