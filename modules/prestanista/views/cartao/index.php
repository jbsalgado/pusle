<?php
/** @var yii\web\View $this */
/** @var app\modules\vendas\models\Venda[] $cartoes */
/** @var yii\data\Pagination $pages */
/** @var int $totalCount */
/** @var string $q */
/** @var string $status */
/** @var int|string $vendedor_id */
/** @var int|string $cobrador_id */
/** @var string $cidade */
/** @var string $bairro */
/** @var string $data_inicio */
/** @var string $data_fim */
/** @var app\modules\vendas\models\Colaborador[] $vendedores */
/** @var app\modules\vendas\models\Colaborador[] $cobradores */
/** @var string[] $cidades */
/** @var string[] $bairros */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Cartões de Crediário';

// Helper para gerar URL removendo um determinado parâmetro
$removerParamUrl = function ($paramToRemove) use ($q, $status, $vendedor_id, $cobrador_id, $cidade, $bairro, $data_inicio, $data_fim) {
    $params = [
        '/prestanista/cartao/index',
        'q' => $q,
        'status' => $status,
        'vendedor_id' => $vendedor_id,
        'cobrador_id' => $cobrador_id,
        'cidade' => $cidade,
        'bairro' => $bairro,
        'data_inicio' => $data_inicio,
        'data_fim' => $data_fim,
    ];
    unset($params[$paramToRemove]);
    if ($paramToRemove === 'periodo') {
        unset($params['data_inicio'], $params['data_fim']);
    }
    return Url::to(array_filter($params, fn($val) => $val !== null && $val !== ''));
};

$temFiltrosAvancados = !empty($vendedor_id) || !empty($cobrador_id) || !empty($cidade) || !empty($bairro) || !empty($data_inicio) || !empty($data_fim);
$temAlgumFiltro = $temFiltrosAvancados || !empty($q) || !empty($status);

// Mapeamentos para exibição amigável nas tags
$vendedorNome = null;
if ($vendedor_id) {
    foreach ($vendedores as $v) {
        if ((string)$v->id === (string)$vendedor_id) {
            $vendedorNome = $v->nome_completo;
            break;
        }
    }
}

$cobradorNome = null;
if ($cobrador_id) {
    foreach ($cobradores as $cob) {
        if ((string)$cob->id === (string)$cobrador_id) {
            $cobradorNome = $cob->nome_completo;
            break;
        }
    }
}
?>

<div class="space-y-5">

    <!-- Topo: Título e Botão Novo Cartão -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <span>📇</span>
                <span>Cartões de Crediário</span>
            </h1>
            <p class="text-xs text-slate-400">
                Fichas de compras a prazo, cartelas de cobrança e controle de recebimentos.
            </p>
        </div>

        <a href="<?= Url::to(['/prestanista/cartao/novo']) ?>" class="px-4 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black text-xs sm:text-sm rounded-xl shadow-md transition active:scale-95 flex items-center gap-2">
            <span>➕</span>
            <span>Emitir Novo Cartão</span>
        </a>
    </div>

    <!-- Container do Formulário de Filtros -->
    <form method="get" action="<?= Url::to(['/prestanista/cartao/index']) ?>" class="bg-slate-950/90 border border-slate-800 p-4 sm:p-5 rounded-2xl sm:rounded-3xl shadow-lg space-y-4">
        
        <!-- Linha Principal de Filtros -->
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            
            <!-- Campo de Busca Textual -->
            <div class="sm:col-span-6 lg:col-span-5">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    Buscar Cliente / Rua / Cartão
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                        🔍
                    </span>
                    <input type="text" name="q" value="<?= Html::encode($q) ?>" placeholder="Nome, CPF, fone, rua ou nº do cartão..." class="w-full h-11 pl-10 pr-3.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none transition">
                </div>
            </div>

            <!-- Filtro de Status -->
            <div class="sm:col-span-3 lg:col-span-3">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    Situação / Status
                </label>
                <select name="status" class="w-full h-11 px-3 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none transition">
                    <option value="">Todos os Status</option>
                    <option value="ABERTO" <?= $status === 'ABERTO' ? 'selected' : '' ?>>🟡 Em Aberto (Na Rua)</option>
                    <option value="ATRASADO" <?= $status === 'ATRASADO' ? 'selected' : '' ?>>🔴 Em Atraso (Vencidos)</option>
                    <option value="QUITADO" <?= $status === 'QUITADO' ? 'selected' : '' ?>>🟢 Quitados / Finalizados</option>
                </select>
            </div>

            <!-- Botões de Ação da Linha Principal -->
            <div class="sm:col-span-3 lg:col-span-4 flex items-end gap-2">
                <button type="submit" class="flex-1 h-11 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs rounded-xl shadow transition active:scale-95 flex items-center justify-center gap-1.5">
                    <span>🔍</span>
                    <span>Filtrar</span>
                </button>

                <!-- Botão Toggle de Filtros Avançados -->
                <button type="button" onclick="document.getElementById('painel-filtros-avancados').classList.toggle('hidden');" class="h-11 px-3 bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white font-bold text-xs rounded-xl border border-slate-700 transition flex items-center gap-1.5" title="Mais Filtros">
                    <span>⚙️</span>
                    <span class="hidden md:inline">Mais Filtros</span>
                    <?php if ($temFiltrosAvancados): ?>
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    <?php endif; ?>
                </button>

                <!-- Botão Limpar Filtros -->
                <?php if ($temAlgumFiltro): ?>
                    <a href="<?= Url::to(['/prestanista/cartao/index']) ?>" class="h-11 px-3 bg-rose-500/15 hover:bg-rose-500/25 text-rose-400 font-bold text-xs rounded-xl border border-rose-500/30 transition flex items-center justify-center" title="Limpar todos os filtros">
                        ✕
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Painel de Filtros Avançados (Colapsável) -->
        <div id="painel-filtros-avancados" class="<?= $temFiltrosAvancados ? '' : 'hidden' ?> pt-3 border-t border-slate-800/90 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            
            <!-- Vendedor -->
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    🛒 Vendedor Ambulante
                </label>
                <select name="vendedor_id" class="w-full h-10 px-3 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none">
                    <option value="">Todos os Vendedores</option>
                    <?php foreach ($vendedores as $v): ?>
                        <option value="<?= $v->id ?>" <?= (string)$vendedor_id === (string)$v->id ? 'selected' : '' ?>>
                            <?= Html::encode($v->nome_completo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Cobrador -->
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    🛵 Cobrador de Rua
                </label>
                <select name="cobrador_id" class="w-full h-10 px-3 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none">
                    <option value="">Todos os Cobradores</option>
                    <?php foreach ($cobradores as $cob): ?>
                        <option value="<?= $cob->id ?>" <?= (string)$cobrador_id === (string)$cob->id ? 'selected' : '' ?>>
                            <?= Html::encode($cob->nome_completo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Cidade -->
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    🏙️ Cidade
                </label>
                <select name="cidade" class="w-full h-10 px-3 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none">
                    <option value="">Todas as Cidades</option>
                    <?php foreach ($cidades as $cid): ?>
                        <option value="<?= Html::encode($cid) ?>" <?= $cidade === $cid ? 'selected' : '' ?>>
                            <?= Html::encode($cid) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Bairro -->
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    🏘️ Bairro
                </label>
                <select name="bairro" class="w-full h-10 px-3 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none">
                    <option value="">Todos os Bairros</option>
                    <?php foreach ($bairros as $b): ?>
                        <option value="<?= Html::encode($b) ?>" <?= $bairro === $b ? 'selected' : '' ?>>
                            <?= Html::encode($b) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Data Início -->
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    📅 Venda a partir de
                </label>
                <input type="date" name="data_inicio" value="<?= Html::encode($data_inicio) ?>" class="w-full h-10 px-3 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none">
            </div>

            <!-- Data Fim -->
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    📅 Venda até
                </label>
                <input type="date" name="data_fim" value="<?= Html::encode($data_fim) ?>" class="w-full h-10 px-3 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none">
            </div>

            <!-- Botão aplicar filtros avançados dentro do painel -->
            <div class="sm:col-span-2 flex items-end gap-2 pt-1">
                <button type="submit" class="h-10 px-5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl border border-slate-600 transition flex items-center justify-center gap-1.5">
                    <span>✓</span> Aplicar Filtros Avançados
                </button>
            </div>
        </div>

        <!-- Linha de Badges / Chips de Filtros Ativos -->
        <?php if ($temAlgumFiltro): ?>
            <div class="pt-2 border-t border-slate-800/60 flex items-center gap-1.5 flex-wrap text-xs">
                <span class="text-slate-500 text-[11px] font-bold mr-1">Filtros Ativos:</span>

                <?php if ($q): ?>
                    <a href="<?= $removerParamUrl('q') ?>" class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-500/15 hover:bg-amber-500/25 text-amber-400 text-[11px] font-bold rounded-lg border border-amber-500/30 transition">
                        <span>Busca: "<?= Html::encode($q) ?>"</span>
                        <span class="text-amber-300">✕</span>
                    </a>
                <?php endif; ?>

                <?php if ($status): ?>
                    <a href="<?= $removerParamUrl('status') ?>" class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-900 hover:bg-slate-800 text-slate-300 text-[11px] font-bold rounded-lg border border-slate-700 transition">
                        <span>Status: <?= $status === 'ABERTO' ? 'Em Aberto' : ($status === 'ATRASADO' ? 'Em Atraso' : 'Quitado') ?></span>
                        <span class="text-slate-400">✕</span>
                    </a>
                <?php endif; ?>

                <?php if ($vendedorNome): ?>
                    <a href="<?= $removerParamUrl('vendedor_id') ?>" class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-500/15 hover:bg-blue-500/25 text-blue-400 text-[11px] font-bold rounded-lg border border-blue-500/30 transition">
                        <span>🛒 Vendedor: <?= Html::encode($vendedorNome) ?></span>
                        <span class="text-blue-300">✕</span>
                    </a>
                <?php endif; ?>

                <?php if ($cobradorNome): ?>
                    <a href="<?= $removerParamUrl('cobrador_id') ?>" class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-400 text-[11px] font-bold rounded-lg border border-emerald-500/30 transition">
                        <span>🛵 Cobrador: <?= Html::encode($cobradorNome) ?></span>
                        <span class="text-emerald-300">✕</span>
                    </a>
                <?php endif; ?>

                <?php if ($cidade): ?>
                    <a href="<?= $removerParamUrl('cidade') ?>" class="inline-flex items-center gap-1 px-2.5 py-1 bg-purple-500/15 hover:bg-purple-500/25 text-purple-400 text-[11px] font-bold rounded-lg border border-purple-500/30 transition">
                        <span>🏙️ Cidade: <?= Html::encode($cidade) ?></span>
                        <span class="text-purple-300">✕</span>
                    </a>
                <?php endif; ?>

                <?php if ($bairro): ?>
                    <a href="<?= $removerParamUrl('bairro') ?>" class="inline-flex items-center gap-1 px-2.5 py-1 bg-cyan-500/15 hover:bg-cyan-500/25 text-cyan-400 text-[11px] font-bold rounded-lg border border-cyan-500/30 transition">
                        <span>🏘️ Bairro: <?= Html::encode($bairro) ?></span>
                        <span class="text-cyan-300">✕</span>
                    </a>
                <?php endif; ?>

                <?php if ($data_inicio || $data_fim): ?>
                    <a href="<?= $removerParamUrl('periodo') ?>" class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-900 hover:bg-slate-800 text-slate-300 text-[11px] font-bold rounded-lg border border-slate-700 transition">
                        <span>📅 Período: <?= $data_inicio ? date('d/m/Y', strtotime($data_inicio)) : 'Início' ?> até <?= $data_fim ? date('d/m/Y', strtotime($data_fim)) : 'Hoje' ?></span>
                        <span class="text-slate-400">✕</span>
                    </a>
                <?php endif; ?>

                <a href="<?= Url::to(['/prestanista/cartao/index']) ?>" class="text-[11px] text-rose-400 hover:underline font-bold ml-1">
                    Limpar Todos
                </a>
            </div>
        <?php endif; ?>
    </form>

    <!-- Resumo dos Resultados -->
    <div class="flex items-center justify-between text-xs text-slate-400 px-1">
        <div>
            Exibindo <span class="font-black text-white"><?= count($cartoes) ?></span> de <span class="font-black text-white"><?= $totalCount ?></span> cartões encontrados.
        </div>
        <?php if ($temAlgumFiltro): ?>
            <div class="text-[11px] text-amber-400/80">
                Filtros aplicados
            </div>
        <?php endif; ?>
    </div>

    <!-- Lista de Cartões (Grid) -->
    <?php if (empty($cartoes)): ?>
        <div class="bg-slate-950/60 border border-slate-800/80 rounded-3xl p-12 text-center text-slate-400">
            <span class="text-4xl block mb-2">📇</span>
            <p class="text-base font-bold text-white">Nenhum cartão encontrado</p>
            <p class="text-xs mt-1 text-slate-500">Tente ajustar os filtros acima ou cadastre um novo crediário.</p>
            <?php if ($temAlgumFiltro): ?>
                <div class="mt-4">
                    <a href="<?= Url::to(['/prestanista/cartao/index']) ?>" class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 font-bold text-xs rounded-xl border border-slate-700 transition">
                        <span>✕</span> Limpar Filtros
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($cartoes as $c): ?>
                <?php
                    $estaQuitado = in_array($c->status_venda_codigo, ['FINALIZADA', 'QUITADA']);
                    $saldoDevedor = (float)$c->getSaldoDevedor();
                    $cliente = $c->cliente;
                    $vendedor = $c->vendedor;
                    
                    // Identificar se há parcelas atrasadas
                    $temAtraso = false;
                    if (!$estaQuitado) {
                        foreach ($c->parcelas as $p) {
                            if ($p->status_parcela_codigo === 'PENDENTE' && $p->data_vencimento < date('Y-m-d')) {
                                $temAtraso = true;
                                break;
                            }
                        }
                    }
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
                            
                            <div class="flex items-center gap-1.5">
                                <?php if ($temAtraso): ?>
                                    <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20" title="Possui parcelas vencidas">
                                        ⚠️ Atrasado
                                    </span>
                                <?php endif; ?>
                                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full <?= $estaQuitado ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' ?>">
                                    <?= $estaQuitado ? '✓ Quitado' : 'Em Cobrança' ?>
                                </span>
                            </div>
                        </div>

                        <!-- Dados do Cliente -->
                        <div class="mb-3">
                            <h3 class="text-sm font-black text-white group-hover:text-amber-400 transition truncate">
                                <?= Html::encode($cliente ? ($cliente->nome ?? $cliente->nome_completo) : 'Cliente Avulso') ?>
                            </h3>
                            <p class="text-xs text-slate-400 truncate mt-0.5">
                                📍 <?= Html::encode($cliente ? ($cliente->logradouro ?: $cliente->endereco_logradouro ?: 'Sem endereço') : 'Sem endereço cadastrado') ?>
                                <?= ($cliente && !empty($cliente->numero)) ? ', ' . Html::encode($cliente->numero) : '' ?>
                            </p>
                            <p class="text-xs text-slate-500 truncate mt-0.5">
                                <?= Html::encode($cliente ? ($cliente->bairro ?: $cliente->endereco_bairro) : '') ?> <?= ($cliente && ($cliente->bairro || $cliente->cidade)) ? '•' : '' ?> <?= Html::encode($cliente ? ($cliente->cidade ?: $cliente->endereco_cidade) : '') ?>
                            </p>
                        </div>

                        <!-- Vendedor Vinculado -->
                        <?php if ($vendedor): ?>
                            <div class="mb-3">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-blue-500/10 text-blue-400 border border-blue-500/20 rounded-lg text-[10px] font-bold">
                                    🛒 Vendedor: <?= Html::encode($vendedor->nome_completo) ?>
                                </span>
                            </div>
                        <?php endif; ?>

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
                        <a href="<?= Url::to(['/prestanista/cartao/view', 'id' => $c->id]) ?>" class="flex-1 py-2 px-3 bg-amber-500/15 hover:bg-amber-500/25 text-amber-400 hover:text-amber-300 font-bold text-xs rounded-xl text-center border border-amber-500/30 transition">
                            👁️ Ver Cartão Físico
                        </a>
                        <a href="<?= Url::to(['/prestanista/cartao/imprimir', 'id' => $c->id]) ?>" target="_blank" class="p-2 bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white rounded-xl border border-slate-700 transition" title="Imprimir Cartão de Papel">
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
                'options' => ['class' => 'flex items-center gap-1.5 flex-wrap justify-center'],
                'linkOptions' => ['class' => 'px-3 py-1.5 bg-slate-900 border border-slate-800 text-xs font-bold text-slate-300 hover:bg-slate-800 rounded-lg'],
                'activePageCssClass' => '!bg-amber-500 !text-slate-950 !border-amber-500',
            ]) ?>
        </div>
    <?php endif; ?>

</div>

