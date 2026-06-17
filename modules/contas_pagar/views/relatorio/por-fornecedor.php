<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;
use app\modules\vendas\models\Fornecedor;

$this->title = 'Relatório por Fornecedor';
$this->params['breadcrumbs'][] = ['label' => 'Contas a Pagar', 'url' => ['/contas-pagar/conta-pagar/index']];
$this->params['breadcrumbs'][] = ['label' => 'Relatórios', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Buscar todos os fornecedores para o filtro
$fornecedores = Fornecedor::find()->orderBy(['nome' => SORT_ASC])->all();
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 lg:px-8">

    <div class="max-w-7xl mx-auto">

        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                    <p class="text-gray-600 mt-1">Análise de contas agrupadas por fornecedor</p>
                </div>
                <div class="flex gap-2">
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                        ['index'],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300']
                    ) ?>
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>Exportar PDF',
                        ['export-pdf', 'tipo' => 'por-fornecedor', 'fornecedor_id' => $fornecedorId],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
                    ) ?>
                </div>
            </div>
        </div>

        <!-- Filtro por Fornecedor -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <form method="get" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filtrar por Fornecedor</label>
                    <select name="fornecedor_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">Todos os Fornecedores</option>
                        <?php foreach ($fornecedores as $fornecedor): ?>
                            <option value="<?= $fornecedor->id ?>" <?= $fornecedorId == $fornecedor->id ? 'selected' : '' ?>>
                                <?= Html::encode($fornecedor->nome) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition duration-300">
                    Filtrar
                </button>
                <?php if ($fornecedorId): ?>
                    <?= Html::a('Limpar', ['por-fornecedor'], ['class' => 'px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition duration-300']) ?>
                <?php endif; ?>
            </form>
        </div>

        <!-- Resumo por Fornecedor -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <?php foreach ($resumo as $item): ?>
                <?php
                $fornecedor = $item['fornecedor_id'] ? Fornecedor::findOne($item['fornecedor_id']) : null;
                $nomeFornecedor = $fornecedor ? $fornecedor->nome : 'Sem Fornecedor';
                ?>
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition-shadow">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 truncate" title="<?= Html::encode($nomeFornecedor) ?>">
                        <?= Html::encode($nomeFornecedor) ?>
                    </h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total de Contas:</span>
                            <span class="font-semibold text-gray-900"><?= $item['total_contas'] ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Pendente:</span>
                            <span class="font-semibold text-yellow-600"><?= Yii::$app->formatter->asCurrency($item['total_pendente']) ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Pago:</span>
                            <span class="font-semibold text-green-600"><?= Yii::$app->formatter->asCurrency($item['total_pago']) ?></span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                            <span class="text-sm font-semibold text-gray-700">Total Geral:</span>
                            <span class="font-bold text-gray-900"><?= Yii::$app->formatter->asCurrency($item['total_geral']) ?></span>
                        </div>
                    </div>
                    <?php if ($fornecedor): ?>
                        <div class="mt-4">
                            <?= Html::a('Ver Detalhes', ['por-fornecedor', 'fornecedor_id' => $fornecedor->id], ['class' => 'block w-full text-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Detalhamento de Contas -->
        <?php if ($fornecedorId): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 bg-purple-50 border-b border-purple-200">
                    <h2 class="text-xl font-bold text-purple-900">Detalhamento de Contas</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimento</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($dataProvider->getModels() as $model): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?= Html::encode($model->descricao) ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="text-sm font-bold text-gray-900"><?= Yii::$app->formatter->asCurrency($model->valor) ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-900">
                                        <?= Yii::$app->formatter->asDate($model->data_vencimento) ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php
                                        $badgeClass = 'bg-gray-100 text-gray-800';
                                        if ($model->isPaga()) $badgeClass = 'bg-green-100 text-green-800';
                                        elseif ($model->isVencida()) $badgeClass = 'bg-red-100 text-red-800';
                                        elseif ($model->isPendente()) $badgeClass = 'bg-yellow-100 text-yellow-800';
                                        ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $badgeClass ?>">
                                            <?= Html::encode($model->status) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium">
                                        <?= Html::a('Ver', ['/contas-pagar/conta-pagar/view', 'id' => $model->id], ['class' => 'text-blue-600 hover:text-blue-900']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginação -->
            <div class="mt-6">
                <?= LinkPager::widget([
                    'pagination' => $dataProvider->pagination,
                    'options' => ['class' => 'flex justify-center flex-wrap gap-2'],
                    'linkOptions' => ['class' => 'px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50 text-sm'],
                    'activePageCssClass' => 'bg-blue-600 text-white border-blue-600',
                    'disabledPageCssClass' => 'opacity-50 cursor-not-allowed',
                    'prevPageLabel' => '←',
                    'nextPageLabel' => '→',
                ]) ?>
            </div>
        <?php endif; ?>

    </div>

</div>