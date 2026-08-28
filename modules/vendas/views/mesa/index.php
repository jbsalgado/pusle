<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Mapa de Mesas & Comandas';
$this->params['breadcrumbs'][] = ['label' => 'Vendas', 'url' => ['/vendas/inicio/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Mensagens Flash -->
        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-xl shadow-sm flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="text-xl">⚠️</span>
                    <p class="font-medium text-sm sm:text-base"><?= Yii::$app->session->getFlash('error') ?></p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-800 font-bold">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-xl shadow-sm flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="text-xl">✅</span>
                    <p class="font-medium text-sm sm:text-base"><?= Yii::$app->session->getFlash('success') ?></p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 font-bold">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('info')): ?>
            <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-4 rounded-xl shadow-sm flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="text-xl">ℹ️</span>
                    <p class="font-medium text-sm sm:text-base"><?= Yii::$app->session->getFlash('info') ?></p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-amber-600 hover:text-amber-800 font-bold">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Cabeçalho -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-200 pb-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-3">
                    <span>🍺</span>
                    <span>Mapa de Mesas & Comandas</span>
                    <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2.5 py-1 rounded-full uppercase">Food Service</span>
                </h1>
                <p class="text-sm text-gray-500 mt-1">Gestão gráfica em tempo real para bares, lanchonetes e restaurantes.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <?= Html::beginForm(Url::to(['/vendas/mesa/adicionar-mesa-rapida']), 'post', ['class' => 'm-0']) ?>
                <button type="submit"
                    class="inline-flex items-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-xl shadow-md transition duration-150 text-xs sm:text-sm" title="Adicionar +1 mesa sequencial instantaneamente com 1 clique">
                    <span class="mr-1.5">⚡</span>
                    <span>+1 Mesa</span>
                </button>
                <?= Html::endForm() ?>

                <button type="button" onclick="abrirModalLoteMesas()"
                    class="inline-flex items-center px-3.5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-black rounded-xl shadow-md transition duration-150 text-xs sm:text-sm" title="Gerar um conjunto de várias mesas de uma vez só">
                    <span class="mr-1.5">🚀</span>
                    <span>+Várias Mesas</span>
                </button>

                <button type="button" onclick="abrirModalCriarMesa()"
                    class="inline-flex items-center px-3.5 py-2 bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-xl shadow-md transition duration-150 text-xs sm:text-sm">
                    <span class="mr-1.5">⚙️</span>
                    <span>Personalizada</span>
                </button>

                <a href="<?= Url::to(['/vendas/delivery/index']) ?>"
                    class="inline-flex items-center px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-xl shadow-md transition duration-150 text-xs sm:text-sm">
                    <span class="mr-1.5">🛵</span>
                    <span>Delivery</span>
                </a>

                <a href="<?= Url::to(['/vendas/kds/index']) ?>"
                    class="inline-flex items-center px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-extrabold rounded-xl shadow-md transition duration-150 text-xs sm:text-sm">
                    <span class="mr-1.5">🍳</span>
                    <span>Monitor KDS</span>
                </a>

                <a href="<?= Url::to(['/vendas/mesa/relatorio']) ?>"
                    class="inline-flex items-center px-3.5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-extrabold rounded-xl shadow-md transition duration-150 text-xs sm:text-sm">
                    <span class="mr-1.5">📊</span>
                    <span>Analytics</span>
                </a>

                <a href="<?= Url::to(['/vendas/mesa/comissoes']) ?>"
                    class="inline-flex items-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-md transition duration-150 text-xs sm:text-sm">
                    <span class="mr-1.5">💰</span>
                    <span>Comissões</span>
                </a>

                <a href="<?= Url::to(['/vendas/inicio/index']) ?>"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition duration-150 text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Voltar ao Painel
                </a>
            </div>
        </div>

        <!-- Cards de Estatísticas & Indicadores Rápidos -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
            <!-- Total de Mesas -->
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Mesas</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-1"><?= $totalMesas ?></p>
            </div>

            <!-- Livres -->
            <div class="bg-emerald-50 rounded-2xl p-4 shadow-sm border border-emerald-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-emerald-800 uppercase tracking-wider">🟢 Livres</p>
                    <span class="text-xl">🟢</span>
                </div>
                <p class="text-2xl sm:text-3xl font-extrabold text-emerald-900 mt-1"><?= $livres ?></p>
            </div>

            <!-- Ocupadas -->
            <div class="bg-rose-50 rounded-2xl p-4 shadow-sm border border-rose-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-rose-800 uppercase tracking-wider">🔴 Ocupadas</p>
                    <span class="text-xl">🔴</span>
                </div>
                <p class="text-2xl sm:text-3xl font-extrabold text-rose-900 mt-1"><?= $ocupadas ?></p>
            </div>

            <!-- Conta Solicitada -->
            <div class="bg-amber-50 rounded-2xl p-4 shadow-sm border border-amber-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-amber-800 uppercase tracking-wider">🟡 Conta</p>
                    <span class="text-xl">🟡</span>
                </div>
                <p class="text-2xl sm:text-3xl font-extrabold text-amber-900 mt-1"><?= $aguardandoConta ?></p>
            </div>

            <!-- Consumo Acumulado -->
            <div class="col-span-2 sm:col-span-1 bg-gradient-to-br from-indigo-600 to-purple-700 text-white rounded-2xl p-4 shadow-md">
                <p class="text-xs font-semibold text-indigo-100 uppercase tracking-wider">💰 Consumo em Aberto</p>
                <p class="text-xl sm:text-2xl font-black mt-1">R$ <?= number_format($faturamentoAcumulado, 2, ',', '.') ?></p>
            </div>
        </div>

        <!-- Grid de Mesas Gráfico -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <span>📋 Salão Principal</span>
                    <span class="text-xs text-gray-500 font-normal">(Clique na mesa para ações rápidas)</span>
                </h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                <?php foreach ($mesas as $mesa): ?>
                    <?php
                    $badge = $mesa->getStatusBadge();
                    $consumo = $mesa->getConsumoTotal();
                    $comanda = $mesa->comandaAtiva;
                    ?>
                    <div class="group bg-white rounded-2xl border-2 <?= $badge['border'] ?> p-4 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between relative overflow-hidden">
                        
                        <!-- Top Header do Card -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-2">
                                <span class="text-xl"><?= $badge['icon'] ?></span>
                                <span class="font-extrabold text-lg text-gray-900">Mesa <?= Html::encode($mesa->numero_mesa) ?></span>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold <?= $badge['badge'] ?>">
                                <?= $badge['label'] ?>
                            </span>
                        </div>

                        <!-- Info do Consumo / Cliente -->
                        <div class="my-2 py-3 px-3 bg-gray-50 rounded-xl space-y-1">
                            <div class="flex justify-between items-center text-xs text-gray-500">
                                <span>Capacidade:</span>
                                <span class="font-semibold text-gray-700"><?= $mesa->lugares ?> pessoas</span>
                            </div>

                            <?php if ($comanda): ?>
                                <div class="flex justify-between items-center text-xs text-gray-500">
                                    <span>Cliente:</span>
                                    <span class="font-bold text-gray-900 truncate max-w-[110px]"><?= Html::encode($comanda->cliente_nome ?: 'Cliente') ?></span>
                                </div>
                                <div class="pt-2 border-t border-gray-200 flex justify-between items-center">
                                    <span class="text-xs font-bold text-gray-700">Consumo:</span>
                                    <span class="text-base font-black text-emerald-600">R$ <?= number_format($consumo, 2, ',', '.') ?></span>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-2 text-xs text-gray-400 font-medium">
                                    Mesa sem consumo ativo
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Alerta Visual: Garçom Chamado -->
                        <?php if ($mesa->chamada_garcom): ?>
                            <div class="mt-2 bg-amber-500 text-gray-950 font-black text-center text-xs py-1 rounded-lg animate-pulse shadow flex items-center justify-center gap-1">
                                <span>🔔</span>
                                <span>GARÇOM CHAMADO!</span>
                            </div>
                        <?php endif; ?>

                        <!-- Botões de Ação Dinâmicos -->
                        <div class="mt-3 pt-2 border-t border-gray-100 flex flex-col gap-2">
                            <button type="button" onclick="abrirModalQrCodeMesa('<?= $mesa->id ?>', '<?= Html::encode($mesa->numero_mesa) ?>')" class="w-full py-1 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs rounded-xl flex items-center justify-center gap-1 border border-gray-300 transition">
                                <span>📱</span>
                                <span>QR Code da Mesa</span>
                            </button>

                            <?php if ($mesa->status === \app\modules\vendas\models\Mesa::STATUS_LIVRE): ?>
                                <button type="button" onclick="abrirModalMesa('<?= $mesa->id ?>', '<?= Html::encode($mesa->numero_mesa) ?>')"
                                    class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition duration-150 flex items-center justify-center gap-1">
                                    <span>🚀</span>
                                    <span>Abrir Mesa</span>
                                </button>

                                <?= Html::beginForm(Url::to(['/vendas/mesa/excluir-mesa']), 'post', ['class' => 'm-0', 'onbeforeSubmit' => 'return confirm("Tem certeza que deseja excluir a Mesa ' . Html::encode($mesa->numero_mesa) . '?")']) ?>
                                <input type="hidden" name="mesa_id" value="<?= $mesa->id ?>">
                                <button type="submit" class="w-full py-1 text-rose-600 hover:text-rose-800 font-semibold text-[11px] flex items-center justify-center gap-1 hover:underline">
                                    <span>🗑️ Excluir Mesa</span>
                                </button>
                                <?= Html::endForm() ?>

                            <?php elseif ($mesa->status === \app\modules\vendas\models\Mesa::STATUS_OCUPADA || $mesa->status === \app\modules\vendas\models\Mesa::STATUS_AGUARDANDO_CONTA): ?>
                                <button type="button" onclick="abrirModalLancamento('<?= $mesa->id ?>', '<?= Html::encode($mesa->numero_mesa) ?>', '<?= Html::encode($comanda ? $comanda->cliente_nome : '') ?>')"
                                    class="w-full py-2 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white font-bold text-xs rounded-xl shadow transition duration-150 flex items-center justify-center gap-1">
                                    <span>➕</span>
                                    <span>Lançar Pedidos / Extrato</span>
                                </button>

                                <div class="grid grid-cols-2 gap-1.5 mt-1">
                                    <?php if ($mesa->status === \app\modules\vendas\models\Mesa::STATUS_OCUPADA): ?>
                                        <?php if ($consumo > 0): ?>
                                            <?= Html::beginForm(Url::to(['/vendas/mesa/solicitar-conta']), 'post', ['class' => 'm-0']) ?>
                                            <input type="hidden" name="mesa_id" value="<?= $mesa->id ?>">
                                            <button type="submit" class="w-full py-1.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px] rounded-lg transition duration-150">
                                                🟡 Pedir Conta
                                            </button>
                                            <?= Html::endForm() ?>
                                        <?php else: ?>
                                            <button type="button" onclick="alert('⚠️ Não é possível solicitar conta para uma mesa sem pedidos/consumo lançados.')" class="w-full py-1.5 bg-gray-200 text-gray-400 font-bold text-[11px] rounded-lg cursor-not-allowed">
                                                🟡 Pedir Conta
                                            </button>
                                        <?php endif; ?>

                                        <button type="button" onclick="abrirModalFechamento('<?= $mesa->id ?>', '<?= Html::encode($mesa->numero_mesa) ?>', '<?= Html::encode($comanda ? $comanda->cliente_nome : '') ?>')"
                                            class="w-full py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-[11px] rounded-lg transition duration-150 shadow-sm">
                                            🧾 Fechar & Dividir
                                        </button>

                                    <?php elseif ($mesa->status === \app\modules\vendas\models\Mesa::STATUS_AGUARDANDO_CONTA): ?>
                                        <button type="button" onclick="abrirModalFechamento('<?= $mesa->id ?>', '<?= Html::encode($mesa->numero_mesa) ?>', '<?= Html::encode($comanda ? $comanda->cliente_nome : '') ?>')"
                                            class="w-full py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-lg transition duration-150 shadow-sm">
                                            🧾 Fechar & Dividir
                                        </button>

                                        <?= Html::beginForm(Url::to(['/vendas/mesa/reverter-mesa']), 'post', ['class' => 'm-0']) ?>
                                        <input type="hidden" name="mesa_id" value="<?= $mesa->id ?>">
                                        <button type="submit" class="w-full py-1.5 bg-sky-600 hover:bg-sky-700 text-white font-bold text-[11px] rounded-lg transition duration-150" title="Reabrir mesa para continuar lançando consumos">
                                            🔄 Reabrir Mesa
                                        </button>
                                        <?= Html::endForm() ?>
                                    <?php endif; ?>
                                </div>

                                <button type="button" onclick="abrirModalTransferir('<?= $mesa->id ?>', '<?= Html::encode($mesa->numero_mesa) ?>')"
                                    class="w-full mt-1.5 py-1 bg-sky-100 hover:bg-sky-200 text-sky-800 font-bold text-[11px] rounded-lg transition duration-150 flex items-center justify-center gap-1 border border-sky-200">
                                    <span>🔀</span>
                                    <span>Transferir Mesa</span>
                                </button>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<?= $this->render('_modal_abrir_mesa', ['colaboradores' => $colaboradores ?? []]) ?>
<?= $this->render('_modal_lancamento_item') ?>
<?= $this->render('_modal_fechamento_mesa') ?>
<?= $this->render('_modal_transferir_mesa', ['mesas' => $mesas]) ?>
<?= $this->render('_modal_criar_mesa') ?>
<?= $this->render('_modal_gerar_lote_mesas') ?>
<?= $this->render('_modal_qr_code_mesa') ?>
