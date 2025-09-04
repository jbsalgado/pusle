<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/** @var yii\web\View $this */
/** @var app\modules\indicadores\models\ManySysModulosHasManyUser $model */
/** @var yii\widgets\ActiveForm $form */
/** @var array $allUsers */
/** @var array $allModules */
/** @var app\modules\indicadores\models\User $user */

?>

<div class="permissao-form">

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">
            
            <?php $form = ActiveForm::begin(); ?>

            <?= $form->field($model, 'id_user')->widget(Select2::class, [
                'data' => $allUsers,
                'options' => [
                    'placeholder' => 'Selecione um usuário...',
                    // Na tela de update, o usuário não pode ser alterado.
                    // A variável $user é passada pelo controller na actionUpdate.
                    'disabled' => !$model->isNewRecord,
                ],
                'pluginOptions' => [
                    'allowClear' => false,
                ],
            ])->label('Usuário') ?>

            <?= $form->field($model, 'modulos_selecionados')->widget(Select2::class, [
                'data' => $allModules,
                'options' => [
                    'placeholder' => 'Selecione um ou mais módulos...',
                    'multiple' => true,
                ],
                'pluginOptions' => [
                    'allowClear' => true,
                    'closeOnSelect' => false, // Mantém o dropdown aberto para múltiplas seleções
                ],
            ])->label('Módulos a serem associados') ?>

        </div>
        <div class="card-footer bg-light text-end border-0">
            <?= Html::a('Cancelar', ['index'], ['class' => 'btn btn-secondary']) ?>
            <?= Html::submitButton(
                $model->isNewRecord ? 'Vincular Usuário' : 'Salvar Alterações',
                ['class' => $model->isNewRecord ? 'btn btn-primary' : 'btn btn-success']
            ) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>

</div>
