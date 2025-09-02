<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndRelacoesIndicadores */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="ind-relacoes-indicadores-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'id_indicador_origem')->textInput() ?>

    <?= $form->field($model, 'id_indicador_destino')->textInput() ?>

    <?= $form->field($model, 'tipo_relacao')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'descricao_relacao')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'peso_relacao')->textInput() ?>

    <?= $form->field($model, 'data_criacao')->textInput() ?>

    <?= $form->field($model, 'data_atualizacao')->textInput() ?>

  
	<?php if (!Yii::$app->request->isAjax){ ?>
	  	<div class="form-group">
	        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
	    </div>
	<?php } ?>

    <?php ActiveForm::end(); ?>
    
</div>
