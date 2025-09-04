<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndPeriodicidades */
?>
<div class="ind-periodicidades-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id_periodicidade',
            'nome_periodicidade',
            'descricao:ntext',
            'intervalo_em_dias',
            'data_criacao',
            'data_atualizacao',
        ],
    ]) ?>

</div>
