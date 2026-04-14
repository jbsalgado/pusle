<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\UnidadeMedidaVolume */

$this->title = 'Editar Unidade: ' . $model->nome;
$this->params['breadcrumbs'][] = ['label' => 'Vendas', 'url' => ['/vendas/inicio/index']];
$this->params['breadcrumbs'][] = ['label' => 'Unidades', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->nome, 'url' => ['view', 'id' => $model->nome]];
$this->params['breadcrumbs'][] = 'Editar';
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">

    <div class="max-w-3xl mx-auto">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
            <?= Html::a('Voltar', ['index'], ['class' => 'text-sm font-medium text-blue-600 hover:text-blue-500']) ?>
        </div>

        <?= $this->render('_form', [
            'model' => $model,
        ]) ?>
    </div>
</div>
