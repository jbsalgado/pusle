<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndFontesDados */
?>
<div class="ind-fontes-dados-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id_fonte',
            'nome_fonte',
            'descricao:ntext',
            'url_referencia:url',
            'confiabilidade_estimada',
            'data_criacao',
            'data_atualizacao',
        ],
    ]) ?>

</div>
