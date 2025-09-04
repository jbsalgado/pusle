<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndMetasIndicadores */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="ind-metas-indicadores-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'id_indicador')->textInput() ?>

    <?= $form->field($model, 'descricao_meta')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'valor_meta_referencia_1')->textInput() ?>

    <?= $form->field($model, 'valor_meta_referencia_2')->textInput() ?>

    <?= $form->field($model, 'tipo_de_meta')->dropDownList([ 'MINIMO_ACEITAVEL' => 'MINIMO ACEITAVEL', 'MAXIMO_ACEITAVEL' => 'MAXIMO ACEITAVEL', 'VALOR_EXATO_ESPERADO' => 'VALOR EXATO ESPERADO', 'FAIXA_IDEAL' => 'FAIXA IDEAL', 'PERCENTUAL_MELHORIA' => 'PERCENTUAL MELHORIA', ], ['prompt' => '']) ?>

    <?= $form->field($model, 'data_inicio_vigencia')->textInput() ?>

    <?= $form->field($model, 'data_fim_vigencia')->textInput() ?>

    <?= $form->field($model, 'id_nivel_abrangencia_aplicavel')->textInput() ?>

    <?= $form->field($model, 'justificativa_meta')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'fonte_meta')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'data_criacao')->textInput() ?>

    <?= $form->field($model, 'data_atualizacao')->textInput() ?>

  
	<?php if (!Yii::$app->request->isAjax){ ?>
	  	<div class="form-group">
	        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
	    </div>
	<?php } ?>

    <?php ActiveForm::end(); ?>
    
</div>
