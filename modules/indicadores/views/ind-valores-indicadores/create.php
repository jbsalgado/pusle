<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndValoresIndicadores */
/* @var $indicadores array */
/* @var $niveisAbrangencia array */
/* @var $fontesDados array */

$this->title = 'Registrar Valor de Indicador';
$this->params['breadcrumbs'][] = ['label' => 'Valores de Indicadores', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ind-valores-indicadores-create">

    <h1 class="text-3xl font-extrabold text-gray-900 mb-6"><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'indicadores' => $indicadores,
        'niveisAbrangencia' => $niveisAbrangencia,
        'fontesDados' => $fontesDados,
    ]) ?>

</div>