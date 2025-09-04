<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndMetasIndicadores */
?>
<div class="ind-metas-indicadores-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id_meta',
            'id_indicador',
            'descricao_meta',
            'valor_meta_referencia_1',
            'valor_meta_referencia_2',
            'tipo_de_meta',
            'data_inicio_vigencia',
            'data_fim_vigencia',
            'id_nivel_abrangencia_aplicavel',
            'justificativa_meta:ntext',
            'fonte_meta',
            'data_criacao',
            'data_atualizacao',
        ],
    ]) ?>

</div>
