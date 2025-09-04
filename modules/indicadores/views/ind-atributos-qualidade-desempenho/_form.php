<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndAtributosQualidadeDesempenho */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="ind-atributos-qualidade-desempenho-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'id_indicador')->textInput() ?>

    <?= $form->field($model, 'padrao_ouro_referencia')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'faixa_critica_inferior')->textInput() ?>

    <?= $form->field($model, 'faixa_critica_superior')->textInput() ?>

    <?= $form->field($model, 'faixa_alerta_inferior')->textInput() ?>

    <?= $form->field($model, 'faixa_alerta_superior')->textInput() ?>

    <?= $form->field($model, 'faixa_satisfatoria_inferior')->textInput() ?>

    <?= $form->field($model, 'faixa_satisfatoria_superior')->textInput() ?>

    <?= $form->field($model, 'metodo_pontuacao')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'peso_indicador')->textInput() ?>

    <?= $form->field($model, 'fator_impacto')->textInput() ?>

    <?= $form->field($model, 'data_criacao')->textInput() ?>

    <?= $form->field($model, 'data_atualizacao')->textInput() ?>

  
	<?php if (!Yii::$app->request->isAjax){ ?>
	  	<div class="form-group">
	        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
	    </div>
	<?php } ?>

    <?php ActiveForm::end(); ?>
    
</div>
