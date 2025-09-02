<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndValoresIndicadores */
?>
<div class="ind-valores-indicadores-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id_valor',
            'id_indicador',
            'data_referencia',
            'id_nivel_abrangencia',
            'codigo_especifico_abrangencia',
            'localidade_especifica_nome',
            'valor',
            'numerador',
            'denominador',
            'id_fonte_dado_especifica',
            'data_coleta_dado',
            'confianca_intervalo_inferior',
            'confianca_intervalo_superior',
            'analise_qualitativa_valor:ntext',
            'data_publicacao_valor',
            'data_atualizacao',
        ],
    ]) ?>

</div>
