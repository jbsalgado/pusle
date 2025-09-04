<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndOpcoesDesagregacao */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="ind-opcoes-desagregacao-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'id_categoria_desagregacao')->textInput() ?>

    <?= $form->field($model, 'valor_opcao')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'codigo_opcao')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'descricao_opcao')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'ordem_apresentacao')->textInput() ?>

    <?= $form->field($model, 'data_criacao')->textInput() ?>

    <?= $form->field($model, 'data_atualizacao')->textInput() ?>

  
	<?php if (!Yii::$app->request->isAjax){ ?>
	  	<div class="form-group">
	        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
	    </div>
	<?php } ?>

    <?php ActiveForm::end(); ?>
    
</div>
