<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\FinanceiroMensal */

$this->title = 'Novo Registro Financeiro';
$this->params['breadcrumbs'][] = ['label' => 'Análise Financeira', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="financeiro-mensal-create max-w-4xl mx-auto py-6">

    <h1 class="text-2xl font-bold text-gray-900 mb-6"><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>