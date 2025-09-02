<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndCategoriasDesagregacao */
?>
<div class="ind-categorias-desagregacao-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id_categoria_desagregacao',
            'nome_categoria',
            'descricao:ntext',
            'data_criacao',
            'data_atualizacao',
        ],
    ]) ?>

</div>
