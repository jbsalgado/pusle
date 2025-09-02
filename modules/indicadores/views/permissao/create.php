<?php

use yii\helpers\Html;

$this->title = 'Gerenciar Permissões: ' . $user->username;
$this->params['breadcrumbs'][] = ['label' => 'Permissões', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Gerenciar';
?>

<div class="permissao-update p-4 sm:p-6 bg-gray-100 min-h-screen">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6"><?= Html::encode($this->title) ?></h1>
        
        <?= $this->render('_form', [
            'model' => $model,
            'user' => $user,
            'allUsers' => $allUsers,
            'allModules' => $allModules,
        ]) ?>
    </div>
</div>