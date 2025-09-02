<?php

use yii\widgets\ActiveForm;

 $form = \yii\widgets\ActiveForm::begin(); ?>

<?= $form->field($model, 'username') ?>
<?= $form->field($model, 'email') ?>
<?= $form->field($model, 'password')->passwordInput() ?>

<div class="form-group">
    <button type="submit" class="btn btn-primary">Cadastrar</button>
</div>

<?php ActiveForm::end(); ?>