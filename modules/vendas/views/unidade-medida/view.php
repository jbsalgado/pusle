<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\vendas\models\UnidadeMedidaVolume */

$this->title = $model->nome;
$this->params['breadcrumbs'][] = ['label' => 'Vendas', 'url' => ['/vendas/inicio/index']];
$this->params['breadcrumbs'][] = ['label' => 'Unidades', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
            <div class="flex gap-2">
                <?= Html::a('Editar', ['update', 'id' => $model->nome], ['class' => 'px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold rounded-lg shadow-md transition duration-300']) ?>
                <?= Html::a('Excluir', ['delete', 'id' => $model->nome], [
                    'class' => 'px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-300',
                    'data' => [
                        'confirm' => 'Tem certeza que deseja excluir esta unidade?',
                        'method' => 'post',
                    ],
                ]) ?>
                <?= Html::a('Voltar', ['index'], ['class' => 'px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300']) ?>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
            <div class="p-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <tbody class="bg-white divide-y divide-gray-100">
                        <tr>
                            <td class="px-6 py-4 text-sm font-bold text-gray-700 bg-gray-50 w-1/3">Código/Nome</td>
                            <td class="px-6 py-4 text-sm text-gray-900 font-mono"><?= Html::encode($model->nome) ?></td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm font-bold text-gray-700 bg-gray-50">Descrição</td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= Html::encode($model->descricao) ?></td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm font-bold text-gray-700 bg-gray-50">Status</td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php if ($model->ativo): ?>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Ativo</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">Inativo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
