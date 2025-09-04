<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndNiveisAbrangencia */
?>
<div class="ind-niveis-abrangencia-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id_nivel_abrangencia',
            'nome_nivel',
            'descricao:ntext',
            'tipo_nivel',
            'id_nivel_pai',
            'data_criacao',
            'data_atualizacao',
        ],
    ]) ?>

</div>
