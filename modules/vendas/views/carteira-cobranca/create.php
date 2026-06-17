<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\CarteiraCobranca */

$this->title = 'Nova Carteira de Cobrança';
$this->params['breadcrumbs'][] = ['label' => 'Carteiras de Cobrança', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
        </div>

        <?= $this->render('_form', [
            'model' => $model,
        ]) ?>
    </div>
</div>

