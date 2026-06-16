<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\contas_pagar\models\ContaPagar */

$this->title = 'Pagar Conta: ' . $model->descricao;
$this->params['breadcrumbs'][] = ['label' => 'Contas a Pagar', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md mx-auto">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
            <div class="p-6 sm:p-8">
                <?= $this->render('_form_pagar', [
                    'model' => $model,
                ]) ?>
            </div>
        </div>

        <div class="mt-6 text-center">
            <?= Html::a('← Voltar para a lista', ['index'], ['class' => 'text-sm font-medium text-gray-500 hover:text-gray-700 transition duration-150']) ?>
        </div>
    </div>
</div>