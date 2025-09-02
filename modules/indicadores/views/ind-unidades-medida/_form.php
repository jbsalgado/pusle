<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndUnidadesMedida */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="ind-unidades-medida-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'sigla_unidade')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'descricao_unidade')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'tipo_dado_associado')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'data_criacao')->textInput() ?>

    <?= $form->field($model, 'data_atualizacao')->textInput() ?>

  
	<?php if (!Yii::$app->request->isAjax){ ?>
	  	<div class="form-group">
	        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
	    </div>
	<?php } ?>

    <?php ActiveForm::end(); ?>
    
</div>
