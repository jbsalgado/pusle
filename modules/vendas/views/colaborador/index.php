<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Colaboradores';
$this->params['breadcrumbs'][] = $this->title;

$viewMode = Yii::$app->request->get('view', 'cards');
?>

<div class="min-h-screen bg-gray-50 py-4 px-3 sm:py-6 sm:px-4 lg:px-8">
    
    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-4 sm:mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
            <div class="flex flex-wrap gap-2">
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                    ['/vendas/inicio/index'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm sm:text-base w-full sm:w-auto justify-center']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Novo Colaborador',
                    ['create'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm sm:text-base w-full sm:w-auto justify-center']
                ) ?>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">
        
        <!-- Filtros e Busca -->
        <div class="bg-white rounded-lg shadow-md mb-4 sm:mb-6 p-4 sm:p-6">
            <form method="get" class="space-y-4">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    
                    <!-- Busca -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                        <input type="text" name="busca" 
                               class="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base"
                               placeholder="Nome, CPF ou e-mail..."
                               value="<?= Html::encode(Yii::$app->request->get('busca', '')) ?>">
                    </div>

                    <!-- Papel/Função -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Função</label>
                        <select name="papel" class="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                            <option value="">Todos</option>
                            <option value="vendedor" <?= Yii::$app->request->get('papel') == 'vendedor' ? 'selected' : '' ?>>Vendedor</option>
                            <option value="cobrador" <?= Yii::$app->request->get('papel') == 'cobrador' ? 'selected' : '' ?>>Cobrador</option>
                            <option value="ambos" <?= Yii::$app->request->get('papel') == 'ambos' ? 'selected' : '' ?>>Vendedor e Cobrador</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="ativo" class="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                            <option value="">Todos</option>
                            <option value="1" <?= Yii::$app->request->get('ativo') === '1' ? 'selected' : '' ?>>Ativos</option>
                            <option value="0" <?= Yii::$app->request->get('ativo') === '0' ? 'selected' : '' ?>>Inativos</option>
                        </select>
                    </div>

                </div>

                <div class="flex flex-col sm:flex-row gap-2">
                    <button type="submit" class="w-full sm:w-auto px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300 text-sm sm:text-base">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Buscar
                    </button>
                    <?php if (Yii::$app->request->queryParams): ?>
                        <?= Html::a('Limpar Filtros', ['index'], ['class' => 'w-full sm:w-auto px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition duration-300 text-center text-sm sm:text-base']) ?>
                    <?php endif; ?>
                </div>

            </form>
        </div>

        <!-- Toggle View e Contador -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
            <span class="text-sm sm:text-base text-gray-600">
                <?= $dataProvider->getTotalCount() ?> colaborador(es) encontrado(s)
            </span>
            <div class="flex gap-2">
                <?= Html::a(
                    '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>',
                    ['index', 'view' => 'cards'] + Yii::$app->request->get(),
                    ['class' => 'p-2 rounded-lg transition duration-300 ' . ($viewMode == 'cards' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'), 'title' => 'Visualização em Cards']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/></svg>',
                    ['index', 'view' => 'grid'] + Yii::$app->request->get(),
                    ['class' => 'p-2 rounded-lg transition duration-300 ' . ($viewMode == 'grid' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'), 'title' => 'Visualização em Lista']
                ) ?>
            </div>
        </div>

        <?php if ($viewMode == 'cards'): ?>
            
            <!-- Visualização em Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                <?php foreach ($dataProvider->getModels() as $model): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        
                        <!-- Header do Card -->
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-4 sm:p-6 text-white">
                            <div class="flex items-center justify-center mb-3">
                                <div class="w-16 h-16 sm:w-20 sm:h-20 bg-white rounded-full flex items-center justify-center text-blue-600 text-2xl sm:text-3xl font-bold shadow-lg">
                                    <?= strtoupper(substr($model->nome_completo, 0, 2)) ?>
                                </div>
                            </div>
                            <h3 class="text-center text-lg sm:text-xl font-bold truncate">
                                <?= Html::encode($model->nome_completo) ?>
                            </h3>
                            <p class="text-center text-xs sm:text-sm text-blue-100 mt-1">
                                <?= Html::encode($model->getPapel()) ?>
                            </p>
                        </div>

                        <!-- Badges de Status -->
                        <div class="px-4 pt-3 pb-2 flex flex-wrap gap-2 justify-center">
                            <?php if (!$model->ativo): ?>
                                <span class="px-2 py-1 bg-gray-600 text-white text-xs font-semibold rounded-full">
                                    Inativo
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-green-600 text-white text-xs font-semibold rounded-full">
                                    Ativo
                                </span>
                            <?php endif; ?>
                            <?php if ($model->eh_vendedor): ?>
                                <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded-full">
                                    Vendedor
                                </span>
                            <?php endif; ?>
                            <?php if ($model->eh_cobrador): ?>
                                <span class="px-2 py-1 bg-orange-100 text-orange-800 text-xs font-semibold rounded-full">
                                    Cobrador
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Conteúdo -->
                        <div class="p-4 space-y-3">
                            
                            <?php if ($model->cpf): ?>
                                <div class="flex items-center text-sm">
                                    <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                                    </svg>
                                    <span class="text-gray-600 truncate">
                                        <?= Yii::$app->formatter->format($model->cpf, ['decimal', 0]) ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <?php if ($model->telefone): ?>
                                <div class="flex items-center text-sm">
                                    <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    <span class="text-gray-600 truncate"><?= Html::encode($model->telefone) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($model->email): ?>
                                <div class="flex items-center text-sm">
                                    <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-gray-600 truncate"><?= Html::encode($model->email) ?></span>
                                </div>
                            <?php endif; ?>

                            <!-- Comissões -->
                            <div class="border-t pt-3 mt-3">
                                <?php if ($model->eh_vendedor && $model->percentual_comissao_venda > 0): ?>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs text-gray-600">Comissão Venda:</span>
                                        <span class="text-sm font-semibold text-purple-600">
                                            <?= Yii::$app->formatter->asDecimal($model->percentual_comissao_venda, 2) ?>%
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($model->eh_cobrador && $model->percentual_comissao_cobranca > 0): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs text-gray-600">Comissão Cobrança:</span>
                                        <span class="text-sm font-semibold text-orange-600">
                                            <?= Yii::$app->formatter->asDecimal($model->percentual_comissao_cobranca, 2) ?>%
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($model->data_admissao): ?>
                                <div class="text-xs text-gray-500 text-center pt-2 border-t">
                                    Admitido em: <?= Yii::$app->formatter->asDate($model->data_admissao, 'dd/MM/yyyy') ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Ações -->
                        <div class="p-4 bg-gray-50 flex gap-2">
                            <?= Html::a('Ver', ['view', 'id' => $model->id], 
                                ['class' => 'flex-1 text-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs sm:text-sm font-semibold rounded transition duration-300']) ?>
                            <?= Html::a('Editar', ['update', 'id' => $model->id], 
                                ['class' => 'flex-1 text-center px-3 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-xs sm:text-sm font-semibold rounded transition duration-300']) ?>
                            <?= Html::a('Excluir', ['delete', 'id' => $model->id], [
                                'class' => 'flex-1 text-center px-3 py-2 bg-red-500 hover:bg-red-600 text-white text-xs sm:text-sm font-semibold rounded transition duration-300',
                                'data' => [
                                    'confirm' => 'Tem certeza que deseja excluir este colaborador?',
                                    'method' => 'post',
                                ],
                            ]) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            
            <!-- Visualização em Grid/Tabela -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Colaborador</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Função</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Contato</th>
                                <th class="px-3 sm:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">Comissões</th>
                                <th class="px-3 sm:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-3 sm:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($dataProvider->getModels() as $model): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 sm:px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm mr-3">
                                                <?= strtoupper(substr($model->nome_completo, 0, 2)) ?>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate"><?= Html::encode($model->nome_completo) ?></div>
                                                <?php if ($model->cpf): ?>
                                                    <div class="text-xs text-gray-500 truncate">CPF: <?= Yii::$app->formatter->format($model->cpf, ['decimal', 0]) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 hidden md:table-cell">
                                        <div class="flex flex-wrap gap-1">
                                            <?php if ($model->eh_vendedor): ?>
                                                <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full whitespace-nowrap">Vendedor</span>
                                            <?php endif; ?>
                                            <?php if ($model->eh_cobrador): ?>
                                                <span class="px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full whitespace-nowrap">Cobrador</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 hidden lg:table-cell">
                                        <div class="text-sm text-gray-900">
                                            <?php if ($model->telefone): ?>
                                                <div class="truncate"><?= Html::encode($model->telefone) ?></div>
                                            <?php endif; ?>
                                            <?php if ($model->email): ?>
                                                <div class="text-xs text-gray-500 truncate"><?= Html::encode($model->email) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 text-center hidden xl:table-cell">
                                        <div class="text-sm space-y-1">
                                            <?php if ($model->eh_vendedor && $model->percentual_comissao_venda > 0): ?>
                                                <div class="text-purple-600 font-semibold">V: <?= Yii::$app->formatter->asDecimal($model->percentual_comissao_venda, 1) ?>%</div>
                                            <?php endif; ?>
                                            <?php if ($model->eh_cobrador && $model->percentual_comissao_cobranca > 0): ?>
                                                <div class="text-orange-600 font-semibold">C: <?= Yii::$app->formatter->asDecimal($model->percentual_comissao_cobranca, 1) ?>%</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 text-center">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full whitespace-nowrap <?= $model->ativo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                            <?= $model->ativo ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 text-right text-sm font-medium">
                                        <div class="flex justify-end gap-2">
                                            <?= Html::a('Ver', ['view', 'id' => $model->id], ['class' => 'text-blue-600 hover:text-blue-900']) ?>
                                            <?= Html::a('Editar', ['update', 'id' => $model->id], ['class' => 'text-yellow-600 hover:text-yellow-900']) ?>
                                            <?= Html::a('Excluir', ['delete', 'id' => $model->id], [
                                                'class' => 'text-red-600 hover:text-red-900',
                                                'data' => [
                                                    'confirm' => 'Tem certeza que deseja excluir este colaborador?',
                                                    'method' => 'post',
                                                ],
                                            ]) ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>

        <!-- Paginação -->
        <div class="mt-6">
            <?= LinkPager::widget([
                'pagination' => $dataProvider->pagination,
                'options' => ['class' => 'flex justify-center flex-wrap gap-1 sm:gap-2'],
                'linkOptions' => ['class' => 'px-2 sm:px-3 py-1 sm:py-2 bg-white border border-gray-300 rounded hover:bg-gray-50 text-sm'],
                'activePageCssClass' => 'bg-blue-600 text-white border-blue-600',
                'disabledPageCssClass' => 'opacity-50 cursor-not-allowed',
                'prevPageLabel' => '←',
                'nextPageLabel' => '→',
            ]) ?>
        </div>

    </div>
</div>