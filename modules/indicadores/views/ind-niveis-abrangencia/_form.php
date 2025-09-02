<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndNiveisAbrangencia */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="ind-niveis-abrangencia-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'nome_nivel')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'descricao')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'tipo_nivel')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'id_nivel_pai')->textInput() ?>

    <?= $form->field($model, 'data_criacao')->textInput() ?>

    <?= $form->field($model, 'data_atualizacao')->textInput() ?>

  
	<?php if (!Yii::$app->request->isAjax){ ?>
	  	<div class="form-group">
	        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
	    </div>
	<?php } ?>

    <?php ActiveForm::end(); ?>
    
</div>
