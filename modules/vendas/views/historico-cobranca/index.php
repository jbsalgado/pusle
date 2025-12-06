<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;
use app\modules\vendas\models\HistoricoCobranca;

$this->title = 'Histórico de Cobrança';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <div class="flex flex-wrap gap-2">
                    <?= Html::a(
                        '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                        ['/vendas/inicio/index'],
                        ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300']
                    ) ?>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-md mb-6 p-4">
            <form method="get" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Ação</label>
                        <select name="tipo_acao" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Todos</option>
                            <option value="PAGAMENTO" <?= Yii::$app->request->get('tipo_acao') === 'PAGAMENTO' ? 'selected' : '' ?>>Pagamento</option>
                            <option value="VISITA" <?= Yii::$app->request->get('tipo_acao') === 'VISITA' ? 'selected' : '' ?>>Visita</option>
                            <option value="AUSENTE" <?= Yii::$app->request->get('tipo_acao') === 'AUSENTE' ? 'selected' : '' ?>>Ausente</option>
                            <option value="RECUSA" <?= Yii::$app->request->get('tipo_acao') === 'RECUSA' ? 'selected' : '' ?>>Recusa</option>
                            <option value="NEGOCIACAO" <?= Yii::$app->request->get('tipo_acao') === 'NEGOCIACAO' ? 'selected' : '' ?>>Negociação</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data Início</label>
                        <input type="date" name="data_inicio" value="<?= Html::encode(Yii::$app->request->get('data_inicio', '')) ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data Fim</label>
                        <input type="date" name="data_fim" value="<?= Html::encode(Yii::$app->request->get('data_fim', '')) ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300">
                            Filtrar
                        </button>
                    </div>
                </div>
                <?php if (Yii::$app->request->queryParams): ?>
                    <?= Html::a('Limpar Filtros', ['index'], ['class' => 'text-sm text-blue-600 hover:text-blue-800']) ?>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data/Hora</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cobrador</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo de Ação</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor Recebido</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Observação</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($dataProvider->totalCount > 0): ?>
                            <?php foreach ($dataProvider->getModels() as $model): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= Yii::$app->formatter->asDatetime($model->data_acao) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= Html::encode($model->cliente->nome_completo ?? '-') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= Html::encode($model->cobrador->nome_completo ?? '-') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= 
                                            $model->tipo_acao == HistoricoCobranca::TIPO_PAGAMENTO ? 'bg-green-100 text-green-800' : 
                                            ($model->tipo_acao == HistoricoCobranca::TIPO_VISITA ? 'bg-blue-100 text-blue-800' : 
                                            ($model->tipo_acao == HistoricoCobranca::TIPO_RECUSA ? 'bg-red-100 text-red-800' : 
                                            'bg-yellow-100 text-yellow-800')) 
                                        ?>">
                                            <?= Html::encode($model->getDescricaoTipoAcao()) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-green-600">
                                        <?= $model->valor_recebido > 0 ? 'R$ ' . Yii::$app->formatter->asDecimal($model->valor_recebido, 2) : '-' ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?= Html::encode(mb_substr($model->observacao ?? '', 0, 50)) ?><?= mb_strlen($model->observacao ?? '') > 50 ? '...' : '' ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <?= Html::a('Ver', ['view', 'id' => $model->id], ['class' => 'text-blue-600 hover:text-blue-900']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    Nenhum histórico encontrado
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if ($dataProvider->pagination->pageCount > 1): ?>
            <div class="mt-6">
                <?= LinkPager::widget([
                    'pagination' => $dataProvider->pagination,
                    'options' => ['class' => 'flex justify-center space-x-2'],
                    'linkOptions' => ['class' => 'px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50'],
                    'activePageCssClass' => 'bg-blue-600 text-white border-blue-600',
                    'prevPageLabel' => '←',
                    'nextPageLabel' => '→',
                ]) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

