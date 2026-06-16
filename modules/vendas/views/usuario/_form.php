<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?= $form->field($model, 'nome')->textInput([
        'maxlength' => true,
        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
    ]) ?>

    <?= $form->field($model, 'username')->textInput([
        'maxlength' => true,
        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
        'placeholder' => 'Nome de usuário para login (único)',
        'title' => 'Nome de usuário único para login. Pode ser email ou CPF.'
    ]) ?>

    <?= $form->field($model, 'email')->textInput([
        'type' => 'email',
        'maxlength' => true,
        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
    ]) ?>

    <?= $form->field($model, 'cpf')->textInput([
        'maxlength' => true,
        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
        'placeholder' => '000.000.000-00'
    ]) ?>

    <?= $form->field($model, 'telefone')->textInput([
        'maxlength' => true,
        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
        'placeholder' => '(00) 00000-0000'
    ]) ?>

    <?= $form->field($model, 'eh_dono_loja')->checkbox([
        'class' => 'h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded',
        'label' => 'É dono da loja',
        'labelOptions' => ['class' => 'text-sm font-medium text-gray-700']
    ]) ?>
</div>

<?php if ($model->isNewRecord): ?>
    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Senha *</label>
        <?= Html::passwordInput('Usuario[senha]', '', [
            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
            'placeholder' => 'Mínimo 6 caracteres',
            'required' => true
        ]) ?>
        <p class="mt-1 text-sm text-gray-500">A senha será criptografada automaticamente.</p>
    </div>
<?php endif; ?>

