<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Unidades de Medida e Volume';
$this->params['breadcrumbs'][] = ['label' => 'Vendas', 'url' => ['/vendas/inicio/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">

    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <p class="text-sm text-gray-500 mt-1">Gerencie as unidades de medida disponíveis para os produtos.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Nova Unidade',
                    ['create'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
                ) ?>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="max-w-7xl mx-auto mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
            <form method="get" action="<?= Url::to(['index']) ?>" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-4 items-end">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-500 uppercase">Código/Nome</label>
                    <input type="text" name="nome" value="<?= Html::encode($filtros['nome']) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                           placeholder="Ex: M3, KG...">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-500 uppercase">Descrição</label>
                    <input type="text" name="descricao" value="<?= Html::encode($filtros['descricao']) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
                           placeholder="Ex: Metro, Litro...">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-500 uppercase">Status</label>
                    <select name="ativo" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Todos</option>
                        <option value="1" <?= $filtros['ativo'] === '1' ? 'selected' : '' ?>>Ativos</option>
                        <option value="0" <?= $filtros['ativo'] === '0' ? 'selected' : '' ?>>Inativos</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white font-semibold rounded-lg shadow-sm transition duration-200 text-sm">
                        Filtrar
                    </button>
                    <a href="<?= Url::to(['index']) ?>" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold rounded-lg transition duration-200 text-sm text-center">
                        Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código/Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($dataProvider->getModels() as $model): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 bg-gray-100 text-gray-800 text-sm font-bold rounded-lg border border-gray-200 font-mono">
                                        <?= Html::encode($model->nome) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?= Html::encode($model->descricao) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($model->ativo): ?>
                                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Ativo</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <?= Html::a('Editar', ['update', 'id' => $model->nome], ['class' => 'text-blue-600 hover:text-blue-900']) ?>
                                        <?= Html::a('Excluir', ['delete', 'id' => $model->nome], [
                                            'class' => 'text-red-600 hover:text-red-900',
                                            'data' => [
                                                'confirm' => 'Tem certeza que deseja excluir esta unidade?',
                                                'method' => 'post',
                                            ],
                                        ]) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($dataProvider->getCount() === 0): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-gray-500">Nenhuma unidade encontrada.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginação -->
        <div class="mt-6">
            <?= LinkPager::widget([
                'pagination' => $dataProvider->pagination,
                'options' => ['class' => 'flex justify-center space-x-2'],
                'linkOptions' => ['class' => 'px-3 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-700'],
                'activePageCssClass' => 'bg-blue-600 text-white border-blue-600',
                'disabledPageCssClass' => 'opacity-50 cursor-not-allowed',
            ]) ?>
        </div>
    </div>
</div>
