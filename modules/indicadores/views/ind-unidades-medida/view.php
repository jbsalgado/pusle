<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndUnidadesMedida */
?>
<div class="ind-unidades-medida-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id_unidade',
            'sigla_unidade',
            'descricao_unidade',
            'tipo_dado_associado',
            'data_criacao',
            'data_atualizacao',
        ],
    ]) ?>

</div>
