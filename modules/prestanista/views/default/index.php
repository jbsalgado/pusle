<?php
/** @var yii\web\View $this */
/** @var float $totalAReceber */
/** @var float $recebidoHoje */
/** @var float $recebidoMes */
/** @var int $totalCartoes */
/** @var int $cartoesAtivos */
/** @var int $cartoesQuitados */
/** @var int $parcelasAtrasadasQtd */
/** @var float $valorAtrasado */
/** @var array $ultimosPagamentos */
/** @var array $cobradores */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Painel Prestanista - Crediário Ambulante';
?>

<div class="space-y-6">

    <!-- Cabeçalho de Boas-Vindas com Ações Rápidas -->
    <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-amber-950/40 border border-slate-800 p-5 sm:p-6 rounded-3xl shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-2xl">📇</span>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Gestão de Vendas Prestanistas</h1>
            </div>
            <p class="text-sm text-slate-400">
                Controle integral de cartões de crediário, rotas de cobrança de rua, ambulantes e acertos diários.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap w-full md:w-auto">
            <a href="<?= Url::to(['/prestanista/atribuicao/index']) ?>" class="px-3.5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black text-xs sm:text-sm rounded-xl shadow-md transition active:scale-95 flex items-center justify-center gap-1.5">
                <span>🛵</span>
                <span>Atribuir Cobrança</span>
            </a>
            <a href="<?= Url::to(['/prestanista/cartao/novo']) ?>" class="px-3.5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs sm:text-sm rounded-xl border border-slate-700 transition active:scale-95 flex items-center justify-center gap-1.5">
                <span>➕</span>
                <span>Novo Cartão</span>
            </a>
            <a href="<?= Url::to(['/prestanista/vendedor/index']) ?>" target="_blank" class="px-3.5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-black text-xs sm:text-sm rounded-xl shadow-md transition active:scale-95 flex items-center justify-center gap-1.5" title="Abrir Aplicativo do Vendedor Ambulante">
                <span>🛒</span>
                <span>App Vendedor</span>
            </a>
            <a href="<?= Url::to(['/prestanista/cobrador/index']) ?>" target="_blank" class="px-3.5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs sm:text-sm rounded-xl shadow-md transition active:scale-95 flex items-center justify-center gap-1.5" title="Abrir Aplicativo do Cobrador de Rua">
                <span>🛵</span>
                <span>App Cobrador</span>
            </a>
            <a href="<?= Url::to(['/prestanista/acerto/index']) ?>" class="px-3.5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs sm:text-sm rounded-xl border border-slate-700 transition active:scale-95 flex items-center justify-center gap-1.5">
                <span>💰</span>
                <span>Acerto de Caixa</span>
            </a>
        </div>
    </div>

    <!-- Cards de Métricas Principais -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total a Receber -->
        <div class="bg-slate-950/80 border border-slate-800/80 p-5 rounded-3xl shadow-sm hover:border-amber-500/30 transition">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider">Total a Receber</span>
                <span class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-sm font-black">💰</span>
            </div>
            <div class="text-2xl font-black text-white">
                R$ <?= number_format($totalAReceber, 2, ',', '.') ?>
            </div>
            <p class="text-xs text-amber-400 font-semibold mt-1 flex items-center gap-1">
                <span><?= $cartoesAtivos ?></span> cartões em aberto na rua
            </p>
        </div>

        <!-- Recebido Hoje -->
        <div class="bg-slate-950/80 border border-slate-800/80 p-5 rounded-3xl shadow-sm hover:border-emerald-500/30 transition">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider">Recebido Hoje</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-sm font-black">⚡</span>
            </div>
            <div class="text-2xl font-black text-emerald-400">
                R$ <?= number_format($recebidoHoje, 2, ',', '.') ?>
            </div>
            <p class="text-xs text-slate-400 mt-1">
                Baixas realizadas pelos cobradores hoje
            </p>
        </div>

        <!-- Recebido no Mês -->
        <div class="bg-slate-950/80 border border-slate-800/80 p-5 rounded-3xl shadow-sm hover:border-blue-500/30 transition">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider">Arrecadado no Mês</span>
                <span class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-sm font-black">📈</span>
            </div>
            <div class="text-2xl font-black text-blue-400">
                R$ <?= number_format($recebidoMes, 2, ',', '.') ?>
            </div>
            <p class="text-xs text-slate-400 mt-1">
                Total acumulado no mês corrente
            </p>
        </div>

        <!-- Atrasados / Inadimplentes -->
        <div class="bg-slate-950/80 border border-slate-800/80 p-5 rounded-3xl shadow-sm hover:border-rose-500/30 transition">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider">Parcelas Atrasadas</span>
                <span class="w-8 h-8 rounded-xl bg-rose-500/10 text-rose-400 flex items-center justify-center text-sm font-black">⚠️</span>
            </div>
            <div class="text-2xl font-black text-rose-400">
                R$ <?= number_format($valorAtrasado, 2, ',', '.') ?>
            </div>
            <p class="text-xs text-rose-300/80 mt-1">
                <?= $parcelasAtrasadasQtd ?> parcela(s) vencida(s)
            </p>
        </div>

    </div>

    <!-- Seção de Ações Rápidas & Atalhos de Gestão -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <a href="<?= Url::to(['/prestanista/cartao/index']) ?>" class="p-4 rounded-2xl bg-slate-950 border border-slate-800/90 hover:border-amber-500/50 hover:bg-slate-900/80 transition flex flex-col items-center text-center group">
            <span class="text-3xl mb-2 group-hover:scale-110 transition-transform">📇</span>
            <span class="text-xs font-black text-white">Cartões</span>
            <span class="text-[11px] text-slate-400 mt-0.5">Fichas e parcelas</span>
        </a>

        <a href="<?= Url::to(['/prestanista/equipe/index']) ?>" class="p-4 rounded-2xl bg-slate-950 border border-slate-800/90 hover:border-amber-500/50 hover:bg-slate-900/80 transition flex flex-col items-center text-center group">
            <span class="text-3xl mb-2 group-hover:scale-110 transition-transform">👥</span>
            <span class="text-xs font-black text-white">Equipes</span>
            <span class="text-[11px] text-slate-400 mt-0.5">Vendedores/Cobradores</span>
        </a>

        <a href="<?= Url::to(['/prestanista/carga/index']) ?>" class="p-4 rounded-2xl bg-slate-950 border border-slate-800/90 hover:border-amber-500/50 hover:bg-slate-900/80 transition flex flex-col items-center text-center group">
            <span class="text-3xl mb-2 group-hover:scale-110 transition-transform">🛒</span>
            <span class="text-xs font-black text-white">Carga Carrinho</span>
            <span class="text-[11px] text-slate-400 mt-0.5">Consignação ambulante</span>
        </a>

        <a href="<?= Url::to(['/prestanista/acerto/index']) ?>" class="p-4 rounded-2xl bg-slate-950 border border-slate-800/90 hover:border-amber-500/50 hover:bg-slate-900/80 transition flex flex-col items-center text-center group">
            <span class="text-3xl mb-2 group-hover:scale-110 transition-transform">💰</span>
            <span class="text-xs font-black text-white">Acerto Caixa</span>
            <span class="text-[11px] text-slate-400 mt-0.5">Prestação de contas</span>
        </a>

        <a href="<?= Url::to(['/prestanista/comissao/index']) ?>" class="p-4 rounded-2xl bg-slate-950 border border-slate-800/90 hover:border-amber-500/50 hover:bg-slate-900/80 transition flex flex-col items-center text-center group">
            <span class="text-3xl mb-2 group-hover:scale-110 transition-transform">💎</span>
            <span class="text-xs font-black text-white">Comissões</span>
            <span class="text-[11px] text-slate-400 mt-0.5">Venda e cobrança</span>
        </a>

        <a href="<?= Url::to(['/prestanista/cartao/imprimir-lote']) ?>" class="p-4 rounded-2xl bg-slate-950 border border-slate-800/90 hover:border-amber-500/50 hover:bg-slate-900/80 transition flex flex-col items-center text-center group">
            <span class="text-3xl mb-2 group-hover:scale-110 transition-transform">🖨️</span>
            <span class="text-xs font-black text-white">Imprimir</span>
            <span class="text-[11px] text-slate-400 mt-0.5">Cartões em papel</span>
        </a>
    </div>

    <!-- Tabela de Últimos Pagamentos e Equipe de Cobrança -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Últimos Recebimentos de Rua (2 Colunas) -->
        <div class="lg:col-span-2 bg-slate-950/80 border border-slate-800 p-5 rounded-3xl shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-lg">💵</span>
                    <h2 class="text-base font-bold text-white">Últimas Baixas na Rua</h2>
                </div>
                <a href="<?= Url::to(['/prestanista/acerto/index']) ?>" class="text-xs text-amber-400 hover:underline font-bold">Ver todos</a>
            </div>

            <?php if (empty($ultimosPagamentos)): ?>
                <div class="text-center py-10 text-slate-500 text-sm">
                    Nenhum pagamento registrado recentemente.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-900/80 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                            <tr>
                                <th class="p-3">Data/Hora</th>
                                <th class="p-3">Cliente</th>
                                <th class="p-3">Cobrador</th>
                                <th class="p-3 text-right">Valor Recebido</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <?php foreach ($ultimosPagamentos as $pag): ?>
                                <tr class="hover:bg-slate-900/50 transition">
                                    <td class="p-3 font-medium text-slate-400 whitespace-nowrap">
                                        <?= date('d/m/Y H:i', strtotime($pag->data_acao)) ?>
                                    </td>
                                    <td class="p-3 font-bold text-white">
                                        <?= Html::encode($pag->cliente->nome ?? 'Cliente #'.$pag->cliente_id) ?>
                                    </td>
                                    <td class="p-3 text-slate-300">
                                        <?= Html::encode($pag->cobrador->nome ?? 'Cobrador') ?>
                                    </td>
                                    <td class="p-3 text-right font-black text-emerald-400 whitespace-nowrap">
                                        R$ <?= number_format($pag->valor_recebido, 2, ',', '.') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Equipe de Cobrança em Campo (1 Coluna) -->
        <div class="bg-slate-950/80 border border-slate-800 p-5 rounded-3xl shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🛵</span>
                        <h2 class="text-base font-bold text-white">Equipe em Campo</h2>
                    </div>
                    <a href="<?= Url::to(['/prestanista/equipe/index']) ?>" class="text-xs text-amber-400 hover:underline font-bold">Gerenciar</a>
                </div>

                <?php if (empty($cobradores)): ?>
                    <p class="text-xs text-slate-500 italic py-6 text-center">Nenhum cobrador ou vendedor cadastrado.</p>
                <?php else: ?>
                    <div class="space-y-2.5">
                        <?php foreach (array_slice($cobradores, 0, 5) as $c): ?>
                            <div class="p-3 rounded-2xl bg-slate-900/70 border border-slate-800/80 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-slate-800 text-amber-400 font-black text-xs flex items-center justify-center border border-slate-700">
                                        <?= strtoupper(substr($c->nome ?? 'C', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold text-white"><?= Html::encode($c->nome) ?></span>
                                        <span class="block text-[10px] text-slate-400"><?= Html::encode($c->funcao ?? 'Colaborador de Campo') ?></span>
                                    </div>
                                </div>
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-xs shadow-emerald-500/50" title="Ativo"></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Banner do PWA Mobile com QR Code ou Link -->
            <div class="mt-6 p-4 rounded-2xl bg-gradient-to-br from-amber-500/10 via-orange-500/10 to-transparent border border-amber-500/20 text-center">
                <span class="text-2xl mb-1 block">📱</span>
                <h3 class="text-xs font-black text-amber-300 uppercase tracking-wide">PWA Offline no Celular</h3>
                <p class="text-[11px] text-slate-400 mt-1 mb-3">
                    Os ambulantes e cobradores podem instalar o app direto no celular para usar sem internet.
                </p>
                <a href="<?= Url::to(['/prestanista/']) ?>" target="_blank" class="inline-block w-full py-2 px-3 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs rounded-xl shadow transition">
                    Acessar PWA de Campo
                </a>
            </div>
        </div>

    </div>

</div>
