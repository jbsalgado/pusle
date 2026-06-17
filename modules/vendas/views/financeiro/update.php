<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\FinanceiroMensal */

$this->title = 'Atualizar Registro: ' . Yii::$app->formatter->asDate($model->mes_referencia, 'MM/yyyy');
$this->params['breadcrumbs'][] = ['label' => 'Análise Financeira', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Atualizar';
?>
<div class="financeiro-mensal-update max-w-4xl mx-auto py-6">

    <h1 class="text-2xl font-bold text-gray-900 mb-6"><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>