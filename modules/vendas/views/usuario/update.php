<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Editar Usuário';
$this->params['breadcrumbs'][] = ['label' => 'Usuários', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->nome, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                    <p class="mt-1 text-sm text-gray-600">Edite os dados do usuário</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?= Html::a('Voltar', ['view', 'id' => $model->id], [
                        'class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300'
                    ]) ?>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Dados do Usuário</h2>
            </div>
            <div class="px-6 py-8">
                <?php $form = ActiveForm::begin(); ?>

                <?= $this->render('_form', ['model' => $model, 'form' => $form]) ?>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nova Senha (deixe em branco para manter a atual)</label>
                    <?= Html::passwordInput('Usuario[nova_senha]', '', [
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                        'placeholder' => 'Mínimo 6 caracteres'
                    ]) ?>
                    <p class="mt-1 text-sm text-gray-500">Preencha apenas se desejar alterar a senha.</p>
                </div>

                <div class="flex gap-3 pt-6">
                    <?= Html::submitButton('Salvar', [
                        'class' => 'px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300'
                    ]) ?>
                    <?= Html::a('Cancelar', ['view', 'id' => $model->id], [
                        'class' => 'px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300'
                    ]) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>

