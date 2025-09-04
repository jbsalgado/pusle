<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\AuthRule */
?>
<div class="auth-rule-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'name',
            'data',
            'created_at',
            'updated_at',
        ],
    ]) ?>

</div>
