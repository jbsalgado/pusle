<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndAtributosQualidadeDesempenho */
?>
<div class="ind-atributos-qualidade-desempenho-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id_atributo_qd',
            'id_indicador',
            'padrao_ouro_referencia',
            'faixa_critica_inferior',
            'faixa_critica_superior',
            'faixa_alerta_inferior',
            'faixa_alerta_superior',
            'faixa_satisfatoria_inferior',
            'faixa_satisfatoria_superior',
            'metodo_pontuacao:ntext',
            'peso_indicador',
            'fator_impacto',
            'data_criacao',
            'data_atualizacao',
        ],
    ]) ?>

</div>
