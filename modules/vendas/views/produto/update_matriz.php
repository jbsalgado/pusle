<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\modules\vendas\models\Produto $model */
/** @var app\modules\vendas\models\Categoria[] $categorias */
/** @var app\modules\vendas\models\ProdutoVariante[] $variantes */
/** @var app\modules\vendas\models\ProdutoFoto[] $fotos */

$this->title = 'Editar Produto (Grade Matriz): ' . $model->nome;
$this->params['breadcrumbs'][] = ['label' => 'Produtos', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->nome, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Editar Grade Matriz';
?>

<div class="min-h-screen bg-slate-50 py-4 px-3 sm:py-6 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto mb-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 flex items-center gap-2">
                    <span class="text-indigo-600">✏️</span>
                    <?= Html::encode($this->title) ?>
                </h1>
                <p class="text-xs text-gray-500 mt-0.5">Gerencie modelo/cores, fotos e quantidades de estoque por combinação</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="<?= \yii\helpers\Url::to(['view', 'id' => $model->id]) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-gray-200 bg-gray-50 hover:bg-gray-100 text-gray-600 text-xs font-semibold transition shrink-0">
                    <span>Ver Produto</span>
                </a>
                <a href="<?= \yii\helpers\Url::to(['update', 'id' => $model->id]) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-gray-200 bg-gray-50 hover:bg-gray-100 text-gray-600 text-xs font-semibold transition shrink-0">
                    <span>Edição Clássica</span>
                </a>
            </div>
        </div>
    </div>

    <?= $this->render('_form_matriz', [
        'model' => $model,
        'categorias' => $categorias,
        'variantes' => $variantes,
        'fotos' => $fotos,
    ]) ?>
</div>
