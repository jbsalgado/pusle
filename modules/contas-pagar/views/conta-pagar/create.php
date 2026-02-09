<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\contas_pagar\models\ContaPagar */

$this->title = 'Nova Conta a Pagar';
$this->params['breadcrumbs'][] = ['label' => 'Contas a Pagar', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-4 px-3 sm:py-6 sm:px-4 lg:px-8">
    <div class="max-w-5xl mx-auto">
        <div class="mb-4 sm:mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
        </div>

        <?= $this->render('_form', [
            'model' => $model,
        ]) ?>
    </div>
</div>