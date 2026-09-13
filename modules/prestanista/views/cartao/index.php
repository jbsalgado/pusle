<?php
/** @var yii\web\View $this */
/** @var app\modules\vendas\models\Venda[] $cartoes */
/** @var yii\data\Pagination $pages */
/** @var string $q */
/** @var string $status */
/** @var int $vendedor_id */
/** @var app\modules\vendas\models\Colaborador[] $vendedores */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Cartões de Crediário';
?>

<div class="space-y-6">

    <!-- Topo / Filtros -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <span>📇</span>
                <span>Cartões de Crediário</span>
            </h1>
            <p class="text-xs text-slate-400">
                Fichas de compras a prazo e cartelas de cobrança dos clientes.
            </p>
        </div>

        <a href="<?= Url::to(['novo']) ?>" class="px-4 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black text-xs sm:text-sm rounded-xl shadow-md transition active:scale-95 flex items-center gap-2">
            <span>➕</span>
            <span>Emitir Novo Cartão</span>
        </a>
    </div>

    <!-- Barra de Busca & Filtros -->
    <form method="get" action="<?= Url::to(['index']) ?>" class="bg-slate-950/80 border border-slate-800 p-4 rounded-2xl grid grid-cols-1 sm:grid-cols-4 gap-3">
        <div class="sm:col-span-2">
            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Buscar Cliente / Rua / Cartão</label>
            <input type="text" name="q" value="<?= Html::encode($q) ?>" placeholder="Digite nome, CPF, telefone ou nº do cartão..." class="w-full h-11 px-3.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:border-amber-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Status</label>
            <select name="status" class="w-full h-11 px-3 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:border-amber-500 focus:outline-none">
                <option value="">Todos os Status</option>
                <option value="ABERTO" <?= $status === 'ABERTO' ? 'selected' : '' ?>>Em Aberto (Na Rua)</option>
                <option value="QUITADO" <?= $status === 'QUITADO' ? 'selected' : '' ?>>Quitados</option>
            </select>
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 h-11 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl border border-slate-700 transition flex items-center justify-center gap-1.5">
                <span>🔍</span> Filtrar
            </button>
            <?php if ($q || $status): ?>
                <a href="<?= Url::to(['index']) ?>" class="h-11 px-3 bg-slate-900 hover:bg-slate-800 text-slate-400 font-bold text-xs rounded-xl border border-slate-800 transition flex items-center justify-center">
                    ✕
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Lista de Cartões -->
    <?php if (empty($cartoes)): ?>
        <div class="bg-slate-950/60 border border-slate-800/80 rounded-3xl p-12 text-center text-slate-400">
            <span class="text-4xl block mb-2">📇</span>
            <p class="text-base font-bold text-white">Nenhum cartão encontrado</p>
            <p class="text-xs mt-1 text-slate-500">Tente buscar por outro termo ou cadastre um novo cartão.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($cartoes as $c): ?>
                <?php
                    $estaQuitado = $c->status_venda_codigo === 'FINALIZADA';
                    $saldoDevedor = (float)$c->getSaldoDevedor();
                    $cliente = $c->cliente;
                ?>
                <div class="bg-slate-950 border border-slate-800 hover:border-amber-500/40 rounded-3xl p-5 shadow-sm transition flex flex-col justify-between group">
                    <div>
                        <!-- Cabeçalho do Card -->
                        <div class="flex items-center justify-between border-b border-slate-800/80 pb-3 mb-3">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 bg-amber-500/15 text-amber-400 font-black text-xs rounded-xl border border-amber-500/30">
                                    Nº #<?= $c->id ?>
                                </span>
                                <span class="text-[11px] text-slate-400">
                                    <?= date('d/m/Y', strtotime($c->data_venda)) ?>
                                </span>
                            </div>
                            <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full <?= $estaQuitado ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' ?>">
                                <?= $estaQuitado ? '✓ Quitado' : 'Em Cobrança' ?>
                            </span>
                        </div>

                        <!-- Dados do Cliente -->
                        <div class="mb-3">
                            <h3 class="text-sm font-black text-white group-hover:text-amber-400 transition truncate">
                                <?= Html::encode($cliente->nome ?? 'Cliente Avulso') ?>
                            </h3>
                            <p class="text-xs text-slate-400 truncate mt-0.5">
                                📍 <?= Html::encode($cliente->endereco ?? 'Sem endereço cadastrado') ?>
                                <?= $cliente->numero ? ', ' . Html::encode($cliente->numero) : '' ?>
                            </p>
                            <p class="text-xs text-slate-500 truncate">
                                <?= Html::encode($cliente->bairro ?? '') ?> • <?= Html::encode($cliente->cidade ?? '') ?>
                            </p>
                        </div>

                        <!-- Valores -->
                        <div class="bg-slate-900/80 border border-slate-800/80 rounded-2xl p-3 grid grid-cols-2 gap-2 text-xs mb-4">
                            <div>
                                <span class="block text-[10px] text-slate-400 uppercase font-bold">Valor Total</span>
                                <span class="text-sm font-bold text-slate-200">R$ <?= number_format($c->valor_total, 2, ',', '.') ?></span>
                            </div>
                            <div class="text-right">
                                <span class="block text-[10px] text-slate-400 uppercase font-bold">Saldo Restante</span>
                                <span class="text-sm font-black <?= $saldoDevedor > 0 ? 'text-amber-400' : 'text-emerald-400' ?>">
                                    R$ <?= number_format($saldoDevedor, 2, ',', '.') ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Rodapé de Ações do Cartão -->
                    <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between gap-2">
                        <a href="<?= Url::to(['view', 'id' => $c->id]) ?>" class="flex-1 py-2 px-3 bg-amber-500/15 hover:bg-amber-500/25 text-amber-400 hover:text-amber-300 font-bold text-xs rounded-xl text-center border border-amber-500/30 transition">
                            👁️ Ver Cartão Físico
                        </a>
                        <a href="<?= Url::to(['imprimir', 'id' => $c->id]) ?>" target="_blank" class="p-2 bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white rounded-xl border border-slate-700 transition" title="Imprimir Cartão de Papel">
                            🖨️
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginação -->
        <div class="pt-4 flex justify-center">
            <?= LinkPager::widget([
                'pagination' => $pages,
                'options' => ['class' => 'flex items-center gap-1.5'],
                'linkOptions' => ['class' => 'px-3 py-1.5 bg-slate-900 border border-slate-800 text-xs font-bold text-slate-300 hover:bg-slate-800 rounded-lg'],
                'activePageCssClass' => '!bg-amber-500 !text-slate-950 !border-amber-500',
            ]) ?>
        </div>
    <?php endif; ?>

</div>
