<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\contas_pagar\models\TipoDespesa */
/* @var $gruposMap array */

$this->title = 'Novo Tipo de Despesa';
$this->params['breadcrumbs'][] = ['label' => 'Contas a Pagar', 'url' => ['/contas-pagar/conta-pagar/index']];
$this->params['breadcrumbs'][] = ['label' => 'Tipos de Despesa', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-4 px-3 sm:py-6 sm:px-4 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-5">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
            <p class="text-sm text-gray-500 mt-1">Crie uma categoria genérica e reutilizável para classificar despesas.</p>
        </div>

        <?= $this->render('_form', [
            'model'     => $model,
            'gruposMap' => $gruposMap,
        ]) ?>
    </div>
</div>
