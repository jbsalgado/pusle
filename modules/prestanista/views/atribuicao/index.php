<?php
/** @var yii\web\View $this */
/** @var app\modules\vendas\models\Venda[] $cartoes */
/** @var app\modules\vendas\models\Colaborador[] $cobradores */
/** @var app\modules\vendas\models\Colaborador[] $vendedores */
/** @var string[] $cidades */
/** @var string[] $bairros */
/** @var string|null $cobrador_id */
/** @var string|null $cidade */
/** @var string|null $bairro */
/** @var string|null $vendedor_id */
/** @var string $status */
/** @var string|null $sem_cobrador */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Atribuição de Cobrança aos Cobradores';
?>

<div class="space-y-6">

    <!-- Topo da Página -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="<?= Url::to(['/prestanista/default/index']) ?>" class="text-xs text-amber-500 hover:text-amber-400 font-bold flex items-center gap-1">
                    <span>←</span> Módulo Prestanista
                </a>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <span>🛵</span>
                <span>Atribuição e Distribuição de Rotas</span>
            </h1>
            <p class="text-xs text-slate-400">
                Vincule cartões de cobrança a cobradores de rua individualmente ou em lote por bairro e cidade.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= Url::to(['/prestanista/cobrador/index']) ?>" target="_blank" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-cyan-400 border border-cyan-500/30 font-bold text-xs rounded-xl shadow-sm transition flex items-center gap-1.5">
                <span>📱</span>
                <span>Abrir App Cobrador</span>
            </a>
            <a href="<?= Url::to(['/prestanista/cartao/index']) ?>" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl shadow-sm transition flex items-center gap-1.5">
                <span>📇</span>
                <span>Lista de Cartões</span>
            </a>
        </div>
    </div>

    <!-- Guia Explicativo para o Lojista / Administrador -->
    <div class="bg-gradient-to-r from-indigo-950/60 via-slate-900 to-slate-900 border border-indigo-500/30 rounded-2xl p-4 sm:p-5 shadow-lg">
        <div class="flex items-start gap-3">
            <span class="text-2xl mt-0.5">ℹ️</span>
            <div class="space-y-2 text-xs w-full">
                <div class="flex items-center justify-between">
                    <h3 class="font-black text-white text-sm">Como funciona a Distribuição de Clientes aos Cobradores:</h3>
                    <span class="text-[10px] font-bold text-indigo-300 bg-indigo-950/80 px-2 py-0.5 rounded border border-indigo-500/30">Instruções para o Lojista</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-slate-300 mt-2">
                    <div class="bg-slate-950/60 p-3 rounded-xl border border-indigo-500/20 space-y-1">
                        <p class="font-bold text-indigo-400">1. Vendas Fechadas no Campo</p>
                        <p class="text-[11px] text-slate-400">Os vendedores fecham os crediários e emitem os cartões físicos. Esses novos cartões começam como <strong class="text-amber-300">Sem Cobrador</strong>.</p>
                    </div>
                    <div class="bg-slate-950/60 p-3 rounded-xl border border-indigo-500/20 space-y-1">
                        <p class="font-bold text-amber-400">2. Você Atribui as Rotas</p>
                        <p class="text-[11px] text-slate-400">Filtre por bairro/cidade, marque os cartões na tabela abaixo e selecione o cobrador responsável na barra de ação em lote.</p>
                    </div>
                    <div class="bg-slate-950/60 p-3 rounded-xl border border-indigo-500/20 space-y-1">
                        <p class="font-bold text-emerald-400">3. Celular do Cobrador Sincronizado</p>
                        <p class="text-[11px] text-slate-400">O cobrador acessa o sistema no celular (<code class="text-white">/prestanista/cobrador</code>) e vê <strong>somente</strong> os clientes atribuídos a ele.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros de Busca -->
    <div class="bg-slate-900 border border-slate-800 p-4 sm:p-5 rounded-2xl shadow-xl">
        <form method="get" action="<?= Url::to(['/prestanista/atribuicao/index']) ?>" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                
                <!-- Cobrador Atual -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                        Cobrador Atual
                    </label>
                    <select name="cobrador_id" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:border-amber-500 outline-none">
                        <option value="">-- Todos os Cobradores --</option>
                        <?php foreach ($cobradores as $cob): ?>
                            <option value="<?= Html::encode($cob->id) ?>" <?= ((string)$cobrador_id === (string)$cob->id) ? 'selected' : '' ?>>
                                <?= Html::encode($cob->nome_completo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Cidade -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                        Cidade
                    </label>
                    <select name="cidade" id="filtro-cidade" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:border-amber-500 outline-none">
                        <option value="">-- Todas as Cidades --</option>
                        <?php foreach ($cidades as $cid): ?>
                            <option value="<?= Html::encode($cid) ?>" <?= ($cidade === $cid) ? 'selected' : '' ?>>
                                <?= Html::encode($cid) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Bairro -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                        Bairro
                    </label>
                    <select name="bairro" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:border-amber-500 outline-none">
                        <option value="">-- Todos os Bairros --</option>
                        <?php foreach ($bairros as $bai): ?>
                            <option value="<?= Html::encode($bai) ?>" <?= ($bairro === $bai) ? 'selected' : '' ?>>
                                <?= Html::encode($bai) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Vendedor -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                        Vendedor
                    </label>
                    <select name="vendedor_id" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:border-amber-500 outline-none">
                        <option value="">-- Todos os Vendedores --</option>
                        <?php foreach ($vendedores as $vend): ?>
                            <option value="<?= Html::encode($vend->id) ?>" <?= ((string)$vendedor_id === (string)$vend->id) ? 'selected' : '' ?>>
                                <?= Html::encode($vend->nome_completo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro Especial: Sem Cobrador -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                        Pendência de Vínculo
                    </label>
                    <select name="sem_cobrador" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:border-amber-500 outline-none">
                        <option value="">Qualquer Situação</option>
                        <option value="1" <?= $sem_cobrador ? 'selected' : '' ?>>⚠️ Somente Sem Cobrador</option>
                    </select>
                </div>

            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-800">
                <div class="text-xs text-slate-400">
                    Mostrando <strong class="text-amber-400"><?= count($cartoes) ?></strong> cartões encontrados
                </div>
                <div class="flex items-center gap-2">
                    <a href="<?= Url::to(['/prestanista/atribuicao/index']) ?>" class="px-3 py-1.5 text-xs text-slate-400 hover:text-white transition">
                        Limpar Filtros
                    </a>
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow transition active:scale-95 flex items-center gap-1.5">
                        <span>🔍</span>
                        <span>Filtrar</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Painel de Execução da Atribuição -->
    <form id="form-atribuir" method="post" action="<?= Url::to(['/prestanista/atribuicao/vincular']) ?>" class="space-y-4">
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
        <input type="hidden" name="modo" id="modo_atribuicao" value="individual">
        <input type="hidden" name="bairro_alvo" value="<?= Html::encode($bairro) ?>">
        <input type="hidden" name="cidade_alvo" value="<?= Html::encode($cidade) ?>">

        <!-- Barra de Ação Rápida Superior -->
        <div class="bg-gradient-to-r from-slate-900 via-slate-850 to-slate-900 border border-amber-500/30 p-4 rounded-2xl shadow-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full md:w-auto">
                <div class="flex items-center gap-2">
                    <span class="text-lg">🎯</span>
                    <span class="text-xs font-bold text-white uppercase tracking-wider whitespace-nowrap">Cobrador Destino:</span>
                </div>
                <select name="cobrador_id" required class="w-full sm:w-64 bg-slate-950 border border-amber-500/50 rounded-xl px-3 py-2 text-xs text-white font-medium focus:ring-2 focus:ring-amber-500 outline-none">
                    <option value="">Selecione o Cobrador...</option>
                    <?php foreach ($cobradores as $cob): ?>
                        <option value="<?= Html::encode($cob->id) ?>">
                            🛵 <?= Html::encode($cob->nome_completo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Botões de Ação em Massa -->
            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto justify-end">
                
                <?php if (!empty($bairro)): ?>
                    <button type="button" onclick="submeterAtribuicao('bairro')" class="px-3.5 py-2 bg-cyan-600/20 hover:bg-cyan-600/30 text-cyan-300 border border-cyan-500/40 text-xs font-bold rounded-xl transition flex items-center gap-1.5 active:scale-95">
                        <span>🏘️</span>
                        <span>Vincular Todo o Bairro "<?= Html::encode($bairro) ?>"</span>
                    </button>
                <?php endif; ?>

                <?php if (!empty($cidade)): ?>
                    <button type="button" onclick="submeterAtribuicao('cidade')" class="px-3.5 py-2 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/40 text-xs font-bold rounded-xl transition flex items-center gap-1.5 active:scale-95">
                        <span>🏙️</span>
                        <span>Vincular Toda a Cidade "<?= Html::encode($cidade) ?>"</span>
                    </button>
                <?php endif; ?>

                <button type="button" onclick="submeterAtribuicao('individual')" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs rounded-xl shadow-md transition flex items-center gap-1.5 active:scale-95">
                    <span>✓</span>
                    <span>Vincular Selecionados (<span id="contador-selecionados">0</span>)</span>
                </button>
            </div>
        </div>

        <!-- Tabela de Cartões e Clientes -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300 border-collapse">
                    <thead class="bg-slate-950 text-slate-400 uppercase tracking-wider text-[10px] font-black border-b border-slate-800">
                        <tr>
                            <th class="p-3.5 w-10 text-center">
                                <input type="checkbox" id="check-todos" class="rounded bg-slate-800 border-slate-700 text-amber-500 focus:ring-0 cursor-pointer">
                            </th>
                            <th class="p-3.5">Cliente / Contato</th>
                            <th class="p-3.5">Endereço / Bairro / Cidade</th>
                            <th class="p-3.5">Vendedor</th>
                            <th class="p-3.5">Cobrador Atual</th>
                            <th class="p-3.5 text-right">Saldo Pendente</th>
                            <th class="p-3.5 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php if (empty($cartoes)): ?>
                            <tr>
                                <td colspan="7" class="p-8 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <span class="text-3xl">📭</span>
                                        <span>Nenhum cartão encontrado para os filtros selecionados.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cartoes as $venda): 
                                $cliente = $venda->cliente;
                                $vendedor = $venda->vendedor;
                                
                                // Determina cobrador atual a partir das parcelas pendentes
                                $cobradorAtual = null;
                                $saldoPendente = 0;
                                $totalPendentes = 0;
                                if (!empty($venda->parcelas)) {
                                    foreach ($venda->parcelas as $p) {
                                        if ($p->status_parcela_codigo === 'PENDENTE') {
                                            $saldoPendente += (float)$p->valor_parcela;
                                            $totalPendentes++;
                                            if (!$cobradorAtual && $p->cobrador) {
                                                $cobradorAtual = $p->cobrador;
                                            }
                                        }
                                    }
                                }
                            ?>
                                <tr class="hover:bg-slate-850/50 transition duration-150">
                                    <td class="p-3.5 text-center">
                                        <input type="checkbox" name="venda_ids[]" value="<?= Html::encode($venda->id) ?>" class="venda-check rounded bg-slate-800 border-slate-700 text-amber-500 focus:ring-0 cursor-pointer">
                                    </td>
                                    <td class="p-3.5">
                                        <div class="font-bold text-white text-sm">
                                            <?= Html::encode($cliente ? $cliente->nome : 'Sem Cliente') ?>
                                        </div>
                                        <div class="text-[11px] text-slate-400 flex items-center gap-2 mt-0.5">
                                            <span>Cartão: #<?= Html::encode(substr($venda->id, 0, 8)) ?></span>
                                            <?php if ($cliente && $cliente->telefone_formatado): ?>
                                                <span>•</span>
                                                <span class="text-cyan-400 font-mono"><?= Html::encode($cliente->telefone_formatado) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="p-3.5">
                                        <?php if ($cliente): ?>
                                            <div class="text-white font-medium">
                                                <?= Html::encode($cliente->endereco_logradouro ?: 'Sem logradouro') ?><?= $cliente->endereco_numero ? ', ' . Html::encode($cliente->endereco_numero) : '' ?>
                                            </div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">
                                                <span class="text-amber-300 font-bold"><?= Html::encode($cliente->endereco_bairro ?: 'Sem bairro') ?></span>
                                                <?php if ($cliente->endereco_cidade): ?>
                                                    - <?= Html::encode($cliente->endereco_cidade) ?><?= $cliente->endereco_estado ? '/' . Html::encode($cliente->endereco_estado) : '' ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-slate-500">Endereço não disponível</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3.5">
                                        <span class="text-slate-300">
                                            <?= Html::encode($vendedor ? $vendedor->nome_completo : 'Venda Direta') ?>
                                        </span>
                                    </td>
                                    <td class="p-3.5">
                                        <?php if ($cobradorAtual): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 rounded-lg text-[11px] font-bold">
                                                <span>🛵</span>
                                                <span><?= Html::encode($cobradorAtual->nome_completo) ?></span>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-rose-500/10 text-rose-400 border border-rose-500/30 rounded-lg text-[10px] font-bold">
                                                <span>⚠️</span>
                                                <span>Não atribuído</span>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3.5 text-right">
                                        <div class="font-mono font-bold text-amber-400 text-sm">
                                            R$ <?= number_format($saldoPendente, 2, ',', '.') ?>
                                        </div>
                                        <div class="text-[10px] text-slate-500">
                                            <?= $totalPendentes ?> parc. pendentes
                                        </div>
                                    </td>
                                    <td class="p-3.5 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="<?= Url::to(['/prestanista/cartao/view', 'id' => $venda->id]) ?>" target="_blank" title="Visualizar Cartão Completo" class="p-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition">
                                                👁️
                                            </a>
                                            <a href="<?= Url::to(['/prestanista/cartao/imprimir', 'id' => $venda->id]) ?>" target="_blank" title="Imprimir Cartão 1/4 A4" class="p-1.5 bg-slate-800 hover:bg-slate-700 text-amber-400 rounded-lg transition">
                                                🖨️
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkTodos = document.getElementById('check-todos');
    const checkboxes = document.querySelectorAll('.venda-check');
    const contador = document.getElementById('contador-selecionados');

    function atualizarContador() {
        const selecionados = document.querySelectorAll('.venda-check:checked').length;
        if (contador) contador.textContent = selecionados;
    }

    if (checkTodos) {
        checkTodos.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = checkTodos.checked);
            atualizarContador();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            atualizarContador();
            if (checkTodos) {
                checkTodos.checked = document.querySelectorAll('.venda-check:checked').length === checkboxes.length;
            }
        });
    });

    atualizarContador();
});

function submeterAtribuicao(modo) {
    const form = document.getElementById('form-atribuir');
    const cobradorSelect = form.querySelector('select[name="cobrador_id"]');
    
    if (!cobradorSelect.value) {
        alert('Por favor, selecione o Cobrador de Destino no topo antes de vincular.');
        cobradorSelect.focus();
        return;
    }

    const cobradorNome = cobradorSelect.options[cobradorSelect.selectedIndex].text;
    document.getElementById('modo_atribuicao').value = modo;

    if (modo === 'individual') {
        const selecionados = document.querySelectorAll('.venda-check:checked').length;
        if (selecionados === 0) {
            alert('Selecione pelo menos um cartão na lista para vincular.');
            return;
        }
        if (!confirm(`Deseja atribuir os ${selecionados} cartão(ões) selecionado(s) para ${cobradorNome}?`)) {
            return;
        }
    } else if (modo === 'bairro') {
        const bairro = document.querySelector('input[name="bairro_alvo"]').value;
        if (!confirm(`Deseja vincular TODOS os cartões do bairro "${bairro}" para ${cobradorNome}?`)) {
            return;
        }
    } else if (modo === 'cidade') {
        const cidade = document.querySelector('input[name="cidade_alvo"]').value;
        if (!confirm(`Deseja vincular TODOS os cartões da cidade "${cidade}" para ${cobradorNome}?`)) {
            return;
        }
    }

    form.submit();
}
</script>
