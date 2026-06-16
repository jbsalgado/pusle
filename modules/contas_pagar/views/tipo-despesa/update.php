<?php

use yii\helpers\Html;
use app\modules\contas_pagar\models\TipoDespesa;

/* @var $this yii\web\View */
/* @var $model app\modules\contas_pagar\models\TipoDespesa */
/* @var $gruposMap array */

$this->title = 'Editar: ' . Html::encode($model->nome);
$this->params['breadcrumbs'][] = ['label' => 'Contas a Pagar', 'url' => ['/contas-pagar/conta-pagar/index']];
$this->params['breadcrumbs'][] = ['label' => 'Tipos de Despesa', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-4 px-3 sm:py-6 sm:px-4 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-5">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Editar Tipo de Despesa</h1>
            <div class="flex items-center gap-2 mt-1">
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold <?= TipoDespesa::getGrupoBadgeClass($model->grupo) ?>">
                    <?= TipoDespesa::getGrupoIcon($model->grupo) ?>
                    <?= Html::encode(TipoDespesa::getGrupoLabel($model->grupo)) ?>
                </span>
                <span class="text-sm text-gray-500"><?= Html::encode($model->nome) ?></span>
            </div>
        </div>

        <?= $this->render('_form', [
            'model'     => $model,
            'gruposMap' => $gruposMap,
        ]) ?>
    </div>
</div>
