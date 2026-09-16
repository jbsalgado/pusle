<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $busca string|null */
/* @var $tipo string|null */
/* @var $lojaConfig app\modules\vendas\models\LojaConfiguracao|null */

$this->title = 'Gestão de Fretes e Entregas';
$models = $dataProvider->getModels();
$meAtivo = $lojaConfig && $lojaConfig->melhor_envio_ativo && !empty($lojaConfig->melhor_envio_token);
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- Cabeçalho -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight"><?= Html::encode($this->title) ?></h1>
            <p class="mt-1 text-sm text-gray-500">Defina regras por Estado, faixas de preço de pedido ou conecte APIs externas de transportadoras.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <!-- Botão Integração Melhor Envio -->
            <a href="<?= Url::to(['integracao']) ?>" 
               class="inline-flex items-center px-4 py-2.5 rounded-xl text-sm font-bold border <?= $meAtivo ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' ?> shadow-sm transition">
                <span class="w-2 h-2 rounded-full mr-2 <?= $meAtivo ? 'bg-emerald-500' : 'bg-gray-400' ?>"></span>
                ⚙️ APIs Externas (Melhor Envio)
            </a>

            <!-- Botão Popular Tabela Brasil -->
            <form method="post" action="<?= Url::to(['popular-estados-padrao']) ?>" 
                  onsubmit="return confirm('Deseja preencher automaticamente as taxas médias padrão para os 27 estados do Brasil? (Regras já existentes serão atualizadas com as médias)')" 
                  class="inline-block">
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2.5 rounded-xl text-sm font-bold bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 shadow-sm transition">
                    🇧🇷 Gerar Tabela Médias Brasil
                </button>
            </form>

            <!-- Botão Nova Regra -->
            <a href="<?= Url::to(['create']) ?>" 
               class="inline-flex items-center justify-center px-5 py-2.5 border border-transparent text-sm font-bold rounded-xl text-white bg-blue-600 hover:bg-blue-700 shadow-md hover:shadow-lg transition active:scale-95">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nova Regra
            </a>
        </div>
    </div>

    <!-- Abas / Filtros Rápidos -->
    <div class="flex items-center gap-2 border-b border-gray-200 pb-2">
        <a href="<?= Url::to(['index']) ?>" 
           class="px-4 py-2 text-sm font-bold rounded-lg transition <?= empty($tipo) ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' ?>">
            Todas as Regras (<?= $dataProvider->getTotalCount() ?>)
        </a>
        <a href="<?= Url::to(['index', 'tipo' => 'ESTADO']) ?>" 
           class="px-4 py-2 text-sm font-bold rounded-lg transition <?= $tipo === 'ESTADO' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' ?>">
            Por Estado (UF)
        </a>
        <a href="<?= Url::to(['index', 'tipo' => 'LOCAL']) ?>" 
           class="px-4 py-2 text-sm font-bold rounded-lg transition <?= $tipo === 'LOCAL' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' ?>">
            Locais (Cidade / Bairro / CEP)
        </a>
    </div>

    <!-- Filtro de Busca -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
        <form method="get" action="<?= Url::to(['index']) ?>" class="flex flex-col md:flex-row gap-3">
            <?php if ($tipo): ?>
                <input type="hidden" name="tipo" value="<?= Html::encode($tipo) ?>">
            <?php endif; ?>
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="busca" value="<?= Html::encode($busca ?? '') ?>" 
                       class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white sm:text-sm transition-all" 
                       placeholder="Buscar por Estado (ex: SP), Cidade, Bairro, CEP ou Serviço...">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-6 py-2.5 bg-gray-900 text-white font-bold text-sm rounded-xl hover:bg-gray-800 transition-colors">
                    Filtrar
                </button>
                <a href="<?= Url::to(['index']) ?>" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-200 text-center transition-colors">
                    Limpar
                </a>
            </div>
        </form>
    </div>

    <!-- Listagem -->
    <?php if (empty($models)): ?>
        <div class="bg-white rounded-3xl p-12 text-center border-2 border-dashed border-gray-200 space-y-4">
            <div class="bg-blue-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto text-blue-600">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900">Nenhuma regra de frete encontrada</h3>
                <p class="text-gray-500 text-sm mt-1">Você pode criar regras específicas por Estado, faixas de preço ou gerar as médias do Brasil com 1 clique.</p>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                <form method="post" action="<?= Url::to(['popular-estados-padrao']) ?>">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <button type="submit" class="px-5 py-3 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-sm shadow transition">
                        🇧🇷 Preencher Automaticamente com Médias do Brasil
                    </button>
                </form>
                <a href="<?= Url::to(['create']) ?>" class="px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-sm shadow transition">
                    + Cadastrar Regra Manual
                </a>
            </div>
        </div>
    <?php else: ?>
        
        <!-- Desktop Table -->
        <div class="hidden md:block bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Região / Local</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Serviço</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Faixa de Preço</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Prazo Estimado</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Valor do Frete</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Frete Grátis Acima</th>
                        <th class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <?php foreach ($models as $model): ?>
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <?php if ($model->estado): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-black bg-blue-100 text-blue-800">
                                            <?= Html::encode($model->estado) ?>
                                        </span>
                                        <span class="text-sm font-semibold text-gray-900">Estado de <?= Html::encode($model->estado) ?></span>
                                    <?php elseif ($model->cidade || $model->bairro || $model->cep): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-800">
                                            Local
                                        </span>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900"><?= Html::encode($model->cidade ?: 'Qualquer Cidade') ?></p>
                                            <p class="text-xs text-gray-500"><?= Html::encode($model->bairro ?: ($model->cep ? 'CEP: ' . $model->cep : 'Geral')) ?></p>
                                        </div>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-100 text-purple-800">
                                            Brasil Geral
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-800"><?= Html::encode($model->nome_servico ?: 'Entrega Padrão') ?></span>
                                <span class="ml-1 text-[10px] font-bold px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 uppercase">Porte <?= $model->porte ?></span>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <?php if ($model->faixa_preco_min > 0 || $model->faixa_preco_max > 0): ?>
                                    R$ <?= number_format($model->faixa_preco_min, 2, ',', '.') ?> até <?= $model->faixa_preco_max ? 'R$ ' . number_format($model->faixa_preco_max, 2, ',', '.') : 'sem limite' ?>
                                <?php else: ?>
                                    <span class="text-gray-400">Qualquer valor</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">
                                <?= $model->prazo_dias_min ?> a <?= $model->prazo_dias_max ?> dias úteis
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-base font-black text-blue-600">R$ <?= number_format($model->valor, 2, ',', '.') ?></span>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if ($model->valor_minimo_frete_gratis > 0): ?>
                                    <span class="text-emerald-700 font-bold">R$ <?= number_format($model->valor_minimo_frete_gratis, 2, ',', '.') ?></span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?= Url::to(['update', 'id' => $model->id]) ?>" 
                                       class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form method="post" action="<?= Url::to(['delete', 'id' => $model->id]) ?>" onsubmit="return confirm('Deseja excluir esta regra?')">
                                        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                                        <button type="submit" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Excluir">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="space-y-4 md:hidden">
            <?php foreach ($models as $model): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-2">
                            <?php if ($model->estado): ?>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-blue-100 text-blue-800 uppercase">
                                    <?= Html::encode($model->estado) ?>
                                </span>
                            <?php endif; ?>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900"><?= Html::encode($model->nome_servico ?: 'Entrega Padrão') ?></h3>
                                <p class="text-xs text-gray-500"><?= Html::encode($model->estado ? 'Estado de ' . $model->estado : ($model->cidade ?: 'Geral')) ?></p>
                            </div>
                        </div>
                        <span class="text-lg font-black text-blue-600">R$ <?= number_format($model->valor, 2, ',', '.') ?></span>
                    </div>

                    <div class="text-xs text-gray-600 grid grid-cols-2 gap-2 pt-2 border-t border-gray-50">
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Prazo:</span>
                            <span><?= $model->prazo_dias_min ?> a <?= $model->prazo_dias_max ?> dias úteis</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Frete Grátis:</span>
                            <span><?= $model->valor_minimo_frete_gratis > 0 ? 'Acima de R$ ' . number_format($model->valor_minimo_frete_gratis, 2, ',', '.') : 'Não aplicável' ?></span>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-50">
                        <a href="<?= Url::to(['update', 'id' => $model->id]) ?>" 
                           class="px-3 py-1.5 bg-gray-100 hover:bg-blue-50 hover:text-blue-600 text-gray-700 rounded-lg text-xs font-bold transition">
                            Editar
                        </a>
                        <form method="post" action="<?= Url::to(['delete', 'id' => $model->id]) ?>" onsubmit="return confirm('Deseja excluir?')">
                            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                            <button type="submit" class="px-3 py-1.5 bg-gray-100 hover:bg-red-50 hover:text-red-600 text-gray-700 rounded-lg text-xs font-bold transition">
                                Excluir
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</div>
