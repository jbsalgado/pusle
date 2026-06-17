<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use app\modules\contas_pagar\models\TipoDespesa;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $grupoFiltro string|null */
/* @var $gruposMap array */

$this->title = 'Tipos de Despesa';
$this->params['breadcrumbs'][] = ['label' => 'Contas a Pagar', 'url' => ['/contas-pagar/conta-pagar/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-4 px-3 sm:py-6 sm:px-4 lg:px-8">
    <div class="max-w-6xl mx-auto">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Tipos de Despesa</h1>
                <p class="text-sm text-gray-500 mt-1">Categorias genéricas e reutilizáveis para classificar suas despesas.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <?= Html::a(
                    '<svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Contas a Pagar',
                    ['/contas-pagar/conta-pagar/index'],
                    ['class' => 'inline-flex items-center px-3 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm font-semibold rounded-lg shadow transition duration-300']
                ) ?>
                <?= Html::a(
                    '<svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Novo Tipo',
                    ['create'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg shadow transition duration-300']
                ) ?>
            </div>
        </div>

        <!-- Alertas -->
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <?= Yii::$app->session->getFlash('success') ?>
            </div>
        <?php endif; ?>
        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?= Yii::$app->session->getFlash('error') ?>
            </div>
        <?php endif; ?>

        <!-- Filtro por Grupo -->
        <div class="bg-white rounded-lg shadow-md mb-4 p-4">
            <div class="flex flex-wrap gap-2 items-center">
                <span class="text-sm font-medium text-gray-600 mr-1">Filtrar por grupo:</span>
                <?= Html::a('Todos', ['index'],
                    ['class' => 'px-3 py-1 rounded-full text-sm font-medium transition duration-200 ' . (!$grupoFiltro ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')]
                ) ?>
                <?php foreach ($gruposMap as $chave => $label): ?>
                    <?php
                    $badgeActive = $grupoFiltro === $chave;
                    $colorMap = [
                        TipoDespesa::GRUPO_FIXA       => 'bg-red-600 text-white',
                        TipoDespesa::GRUPO_VARIAVEL   => 'bg-yellow-500 text-white',
                        TipoDespesa::GRUPO_MERCADORIA => 'bg-blue-600 text-white',
                    ];
                    $colorInactive = [
                        TipoDespesa::GRUPO_FIXA       => 'bg-red-50 text-red-700 hover:bg-red-100',
                        TipoDespesa::GRUPO_VARIAVEL   => 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100',
                        TipoDespesa::GRUPO_MERCADORIA => 'bg-blue-50 text-blue-700 hover:bg-blue-100',
                    ];
                    $cls = $badgeActive ? ($colorMap[$chave] ?? 'bg-gray-800 text-white') : ($colorInactive[$chave] ?? 'bg-gray-100 text-gray-700');
                    ?>
                    <?= Html::a(
                        TipoDespesa::getGrupoIcon($chave) . ' ' . $label,
                        ['index', 'grupo' => $chave],
                        ['class' => 'px-3 py-1 rounded-full text-sm font-medium transition duration-200 ' . $cls]
                    ) ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tabela de Tipos -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <?php if ($dataProvider->getTotalCount() === 0): ?>
                <div class="p-12 text-center text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <p class="font-medium text-lg">Nenhum tipo de despesa cadastrado</p>
                    <p class="text-sm mt-1 mb-4">Crie tipos genéricos como "Aluguel", "Energia Elétrica" ou "Compra de Mercadoria".</p>
                    <?= Html::a('Criar primeiro tipo', ['create'], ['class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition duration-300']) ?>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nome</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Grupo</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Descrição</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($dataProvider->getModels() as $model): ?>
                                <tr class="hover:bg-gray-50 transition duration-150 <?= !$model->ativo ? 'opacity-60' : '' ?>">
                                    <td class="px-4 py-3">
                                        <span class="font-medium text-gray-900"><?= Html::encode($model->nome) ?></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold <?= TipoDespesa::getGrupoBadgeClass($model->grupo) ?>">
                                            <?= TipoDespesa::getGrupoIcon($model->grupo) ?>
                                            <?= Html::encode(TipoDespesa::getGrupoLabel($model->grupo)) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 hidden md:table-cell">
                                        <span class="text-sm text-gray-500"><?= $model->descricao ? Html::encode(mb_strimwidth($model->descricao, 0, 60, '...')) : '-' ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <?php if ($model->ativo): ?>
                                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Ativo</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-2">
                                            <?= Html::a('Editar', ['update', 'id' => $model->id],
                                                ['class' => 'text-sm text-yellow-600 hover:text-yellow-800 font-medium']
                                            ) ?>

                                            <?php
                                            $toggleLabel = $model->ativo ? 'Desativar' : 'Ativar';
                                            $toggleCls   = $model->ativo ? 'text-gray-500 hover:text-gray-800' : 'text-green-600 hover:text-green-800';
                                            echo Html::beginForm(['toggle-ativo', 'id' => $model->id], 'post', ['class' => 'inline']);
                                            echo Html::submitButton($toggleLabel, ['class' => "text-sm {$toggleCls} font-medium bg-transparent border-0 p-0 cursor-pointer"]);
                                            echo Html::endForm();
                                            ?>

                                            <?php if (!$model->temContasVinculadas()): ?>
                                                <?php
                                                echo Html::beginForm(['delete', 'id' => $model->id], 'post', ['class' => 'inline']);
                                                echo Html::submitButton('Excluir', [
                                                    'class'   => 'text-sm text-red-500 hover:text-red-700 font-medium bg-transparent border-0 p-0 cursor-pointer',
                                                    'onclick' => "return confirm('Excluir o tipo \"{$model->nome}\"? Esta ação não pode ser desfeita.')",
                                                ]);
                                                echo Html::endForm();
                                                ?>
                                            <?php else: ?>
                                                <span class="text-sm text-gray-300 cursor-not-allowed" title="Possui contas vinculadas">Excluir</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <div class="p-4 border-t border-gray-100">
                    <?= LinkPager::widget([
                        'pagination'          => $dataProvider->pagination,
                        'options'             => ['class' => 'flex justify-center flex-wrap gap-1'],
                        'linkOptions'         => ['class' => 'px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-50 text-sm'],
                        'activePageCssClass'  => 'bg-blue-600 text-white border-blue-600',
                        'disabledPageCssClass'=> 'opacity-50 cursor-not-allowed',
                        'prevPageLabel'       => '←',
                        'nextPageLabel'       => '→',
                    ]) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info box -->
        <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg flex gap-3">
            <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-blue-800">
                <strong>Dica:</strong> Use nomes <strong>genéricos</strong> nos tipos (ex: "Compra de Mercadoria", "Aluguel").
                O detalhe específico de cada lançamento — como número de NF, mês ou fornecedor — deve ser registrado
                no campo <strong>Descrição</strong> da conta a pagar.
            </div>
        </div>

    </div>
</div>
