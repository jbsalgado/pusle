<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\modules\vendas\models\Produto $model */
/** @var app\modules\vendas\models\Categoria[] $categorias */

$this->title = 'Novo Produto (Grade de Variações Mobile)';
$this->params['breadcrumbs'][] = ['label' => 'Produtos', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Novo Produto com Grade';
?>

<div class="min-h-screen bg-slate-50 py-4 px-3 sm:py-6 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto mb-4">
        <!-- Header com Título e Alternador -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 flex items-center gap-2">
                    <span class="text-indigo-600">⚡</span>
                    <?= Html::encode($this->title) ?>
                </h1>
                <p class="text-xs text-gray-500 mt-0.5">Cadastre dados base, modelo/cores, fotos e grade de tamanhos em uma única tela</p>
            </div>

            <!-- Botão de Alternância para Cadastro Clássico -->
            <a href="<?= \yii\helpers\Url::to(['create']) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-gray-200 bg-gray-50 hover:bg-gray-100 text-gray-600 text-xs font-semibold transition shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Usar Formulário Clássico</span>
            </a>
        </div>
    </div>

    <?= $this->render('_form_matriz', [
        'model' => $model,
        'categorias' => $categorias,
        'variantes' => [],
        'fotos' => [],
    ]) ?>
</div>
