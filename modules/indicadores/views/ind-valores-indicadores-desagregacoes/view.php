<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndValoresIndicadoresDesagregacoes */
?>
<div class="ind-valores-indicadores-desagregacoes-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id_valor_indicador',
            'id_opcao_desagregacao',
        ],
    ]) ?>

</div>
