<?php
/** @var yii\web\View $this */
/** @var app\modules\vendas\models\Colaborador[] $colaboradores */
/** @var app\modules\vendas\models\RotaCobranca[] $rotas */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Equipes: Vendedores e Cobradores';
?>

<div class="space-y-6">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <span>👥</span>
                <span>Equipes de Venda & Cobrança</span>
            </h1>
            <p class="text-xs text-slate-400">
                Divisão dos profissionais de rua entre os que vendem (ambulantes) e os que cobram as prestações.
            </p>
        </div>

        <a href="<?= Url::to(['/vendas/colaborador/create']) ?>" class="px-4 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black text-xs sm:text-sm rounded-xl shadow-md transition active:scale-95 flex items-center gap-2">
            <span>➕</span>
            <span>Novo Colaborador</span>
        </a>
    </div>

    <!-- Cards dos Colaboradores Cadastrados -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($colaboradores as $c): ?>
            <?php 
                $ehCobrador = !empty($c->eh_cobrador);
                $ehVendedor = !empty($c->eh_vendedor);
            ?>
            <div class="bg-slate-950 border border-slate-800 rounded-3xl p-5 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800/80 mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-slate-900 border border-slate-700 flex items-center justify-center font-black text-amber-400 text-sm">
                                <?= strtoupper(substr($c->nome ?? 'C', 0, 2)) ?>
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-white truncate max-w-[170px]"><?= Html::encode($c->nome) ?></h3>
                                <span class="text-xs text-slate-400 block"><?= Html::encode($c->telefone ?? 'Sem telefone') ?></span>
                            </div>
                        </div>

                        <span class="w-2.5 h-2.5 rounded-full <?= $c->ativo ? 'bg-emerald-500' : 'bg-slate-600' ?>" title="<?= $c->ativo ? 'Ativo' : 'Inativo' ?>"></span>
                    </div>

                    <!-- Tags de Função Prestanista -->
                    <div class="flex items-center gap-1.5 flex-wrap mb-4">
                        <span class="px-2.5 py-1 rounded-xl text-[11px] font-black <?= $ehVendedor ? 'bg-blue-500/15 text-blue-400 border border-blue-500/30' : 'bg-slate-900 text-slate-500 border border-slate-800' ?>">
                            🛒 Vendedor Ambulante
                        </span>
                        <span class="px-2.5 py-1 rounded-xl text-[11px] font-black <?= $ehCobrador ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'bg-slate-900 text-slate-500 border border-slate-800' ?>">
                            🛵 Cobrador de Rua
                        </span>
                    </div>

                    <!-- Dados adicionais -->
                    <div class="bg-slate-900/60 border border-slate-800/60 rounded-2xl p-3 text-xs space-y-1">
                        <div class="flex justify-between text-slate-400">
                            <span>CPF:</span>
                            <span class="font-bold text-slate-200"><?= Html::encode($c->cpf ?: '—') ?></span>
                        </div>
                        <div class="flex justify-between text-slate-400">
                            <span>Função:</span>
                            <span class="font-bold text-slate-200"><?= Html::encode($c->funcao ?: 'Ambulante') ?></span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-800/80 mt-4 flex items-center justify-between gap-2">
                    <a href="<?= Url::to(['/vendas/colaborador/update', 'id' => $c->id]) ?>" class="flex-1 py-2 px-3 bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white font-bold text-xs rounded-xl text-center border border-slate-800 transition">
                        ✏️ Editar Cadastro
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>
