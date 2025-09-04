<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndOpcoesDesagregacao */
?>
<div class="ind-opcoes-desagregacao-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id_opcao_desagregacao',
            'id_categoria_desagregacao',
            'valor_opcao',
            'codigo_opcao',
            'descricao_opcao:ntext',
            'ordem_apresentacao',
            'data_criacao',
            'data_atualizacao',
        ],
    ]) ?>

</div>
