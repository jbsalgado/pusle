<?php

/**
 * View: Detalhes do Cupom Fiscal
 * @var yii\web\View $this
 * @var app\modules\vendas\models\CupomFiscal $model
 */

use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\vendas\models\CupomFiscal;

$this->title = 'Cupom Fiscal #' . $model->numero;
$this->params['breadcrumbs'][] = ['label' => 'Central Fiscal', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6 flex justify-between items-center">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                <?php
                $colors = [
                    CupomFiscal::STATUS_AUTORIZADA => 'bg-green-100 text-green-800',
                    CupomFiscal::STATUS_CANCELADA => 'bg-red-100 text-red-800',
                    CupomFiscal::STATUS_ERRO => 'bg-orange-100 text-orange-800',
                    CupomFiscal::STATUS_PENDENTE => 'bg-blue-100 text-blue-800',
                ];
                $class = $colors[$model->status] ?? 'bg-gray-100 text-gray-800';
                ?>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?= $class ?>"><?= $model->status ?></span>
            </div>
            <p class="text-gray-500 mt-1">Detalhes da transação fiscal efetuada na venda #<?= substr($model->venda_id, 0, 8) ?></p>
        </div>
        <div class="flex space-x-2">
            <a href="<?= Url::to(['pdf', 'id' => $model->id]) ?>" target="_blank" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    <path d="M9 11l3 3L15 11" />
                    <path d="M12 14V3" />
                </svg>
                Ver DANFE (PDF)
            </a>
            <a href="<?= Url::to(['xml', 'id' => $model->id]) ?>" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                </svg>
                Baixar XML
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Detalhes do Documento -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Dados do Documento</h3>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Chave de Acesso</dt>
                            <dd class="mt-1 text-sm font-mono text-gray-900 break-all bg-gray-50 p-2 rounded"><?= $model->chave_acesso ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Número / Série</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= $model->numero ?> / <?= $model->serie ?? '1' ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Modelo</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= $model->modelo == '65' ? 'NFCe (65)' : 'NFe (55)' ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Ambiente</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= $model->ambiente == 1 ? 'PRODUÇÃO' : 'HOMOLOGAÇÃO' ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Data de Emissão</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= date('d/m/Y H:i:s', strtotime($model->data_emissao)) ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Último Retorno SEFAZ</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= Html::encode($model->mensagem_retorno) ?></dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Dados da Venda -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Venda Associada</h3>
                    <a href="<?= Url::to(['/vendas/inicio/comprovante', 'id' => $model->venda_id]) ?>" class="text-sm text-blue-600 hover:underline">Ver Comprovante da Venda</a>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-4 text-sm text-gray-600">
                        <div class="bg-gray-50 p-3 rounded">
                            <dt class="font-medium text-gray-500">Valor Total da Venda</dt>
                            <dd class="mt-1 text-lg font-bold text-emerald-600">R$ <?= number_format($model->venda->valor_total, 2, ',', '.') ?></dd>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <dt class="font-medium text-gray-500">Cliente</dt>
                            <dd class="mt-1 text-gray-900"><?= Html::encode($model->venda->cliente->nome ?? 'Consumidor Final') ?></dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Sidebar / Actions -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Ações Disponíveis</h3>
                <div class="space-y-3">
                    <button class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition font-medium text-sm flex items-center justify-center opacity-50 cursor-not-allowed" disabled>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Consultar na SEFAZ
                    </button>
                    <button class="w-full px-4 py-2 border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition font-medium text-sm flex items-center justify-center opacity-50 cursor-not-allowed" disabled>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Solicitar Cancelamento
                    </button>
                    <div class="pt-4 border-t mt-4">
                        <p class="text-xs text-gray-400 italic text-center">Ações de cancelamento e consulta serão habilitadas em breve.</p>
                    </div>
                </div>
            </div>

            <!-- Ajuda -->
            <div class="bg-blue-50 border border-blue-100 p-4 rounded-lg">
                <h4 class="text-sm font-bold text-blue-800 mb-2 flex items-center">
                    <svg class="w-4 h-4 mr-1 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" />
                    </svg>
                    Dica Fiscal
                </h4>
                <p class="text-xs text-blue-700">
                    O cancelamento de NFCe pode ser feito em até 30 minutos após a autorização. Após esse prazo, é necessário emitir uma nota de devolução.
                </p>
            </div>
        </div>
    </div>
</div>