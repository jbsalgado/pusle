<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndDimensoesIndicadores */
?>
<div class="ind-dimensoes-indicadores-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id_dimensao',
            'nome_dimensao',
            'descricao:ntext',
            'id_dimensao_pai',
            'data_criacao',
            'data_atualizacao',
        ],
    ]) ?>

</div>
