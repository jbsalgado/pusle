<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndRelacoesIndicadores */
?>
<div class="ind-relacoes-indicadores-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id_relacao',
            'id_indicador_origem',
            'id_indicador_destino',
            'tipo_relacao',
            'descricao_relacao:ntext',
            'peso_relacao',
            'data_criacao',
            'data_atualizacao',
        ],
    ]) ?>

</div>
